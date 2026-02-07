#!/bin/bash
# Script de prueba para items de facturas PlusMovil
# Endpoint: GET /com-invoices/{id}
# Autenticación: AWS Cognito Bearer Token

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Cargar token
if [ ! -f ".plusmovil-token-QA.txt" ]; then
    echo -e "${RED}❌ Error: Token no encontrado${NC}"
    echo "Ejecuta primero: ./get-plusmovil-token.sh"
    exit 1
fi

source .plusmovil-token-QA.txt

# Base URL
BASE_URL="https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa"

echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Testing PlusMovil Invoice Items Endpoint   ${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo

# Función para hacer request
make_request() {
    local invoice_id=$1
    curl -s -X GET \
        "${BASE_URL}/com-invoices/${invoice_id}" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Content-Type: application/json"
}

# Test 1: Buscar factura con items (monto > $100)
echo -e "${YELLOW}📋 Test 1: Buscar facturas con items (total > \$100)${NC}"
echo "────────────────────────────────────────────────"

INVOICE_WITH_ITEMS=$(curl -s -X GET \
    "${BASE_URL}/com-invoices?limit=10&total_gte=100&order_by=id&order_dir=desc" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    | jq -r '.data[0] | "\(.id)|\(.total)|\(.customer_name)"')

if [ -z "$INVOICE_WITH_ITEMS" ] || [ "$INVOICE_WITH_ITEMS" == "null" ]; then
    echo -e "${RED}❌ No se encontraron facturas con monto mayor a \$100${NC}"
    exit 1
fi

IFS='|' read -r INVOICE_ID TOTAL CUSTOMER <<< "$INVOICE_WITH_ITEMS"

echo -e "${GREEN}✅ Factura encontrada:${NC}"
echo "   ID: $INVOICE_ID"
echo "   Total: \$$TOTAL"
echo "   Cliente: $CUSTOMER"
echo

# Test 2: Obtener detalles completos de la factura
echo -e "${YELLOW}📦 Test 2: Obtener items de factura #${INVOICE_ID}${NC}"
echo "────────────────────────────────────────────────"

INVOICE_DATA=$(make_request "$INVOICE_ID")

# Verificar si tiene items
ITEMS_COUNT=$(echo "$INVOICE_DATA" | jq '.data.com_invoice_item | length')

if [ "$ITEMS_COUNT" -eq 0 ]; then
    echo -e "${RED}⚠️  Esta factura no tiene items (array vacío)${NC}"
    echo "   Buscando otra factura con items..."
    echo

    # Buscar varias facturas y encontrar una con items
    for id in {50..100}; do
        TEST_DATA=$(make_request "$id")
        TEST_ITEMS=$(echo "$TEST_DATA" | jq '.data.com_invoice_item | length')

        if [ "$TEST_ITEMS" -gt 0 ]; then
            INVOICE_ID=$id
            INVOICE_DATA=$TEST_DATA
            ITEMS_COUNT=$TEST_ITEMS
            echo -e "${GREEN}✅ Encontrada factura #${id} con ${ITEMS_COUNT} items${NC}"
            break
        fi
    done
fi

if [ "$ITEMS_COUNT" -eq 0 ]; then
    echo -e "${RED}❌ No se encontraron facturas con items en el rango probado${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Factura con ${ITEMS_COUNT} items encontrada${NC}"
echo

# Test 3: Mostrar resumen de la factura
echo -e "${YELLOW}📊 Test 3: Resumen de factura #${INVOICE_ID}${NC}"
echo "────────────────────────────────────────────────"

echo "$INVOICE_DATA" | jq '{
    id: .data.id,
    invoice_number: .data.invoice_number,
    invoice_date: .data.invoice_date,
    customer: .data.customer_name,
    distributor: .data.com_distributor.name,
    branch: .data.com_branch.name,
    seller: .data.com_seller.name,
    totals: {
        sub_total: .data.sub_total,
        discount: .data.discount,
        tax_amount: .data.tax_amount,
        total: .data.total,
        balance: .data.balance
    },
    items_count: .data.com_invoice_item | length,
    status: .data.status
}'

echo

# Test 4: Mostrar items detallados
echo -e "${YELLOW}📦 Test 4: Items de la factura${NC}"
echo "────────────────────────────────────────────────"

echo "$INVOICE_DATA" | jq '.data.com_invoice_item[] | {
    id,
    product_code,
    product_name,
    product_category,
    product_brand,
    range_start,
    range_end,
    quantity,
    unit_price,
    subtotal
}'

echo

# Test 5: Análisis de rangos (productos serializados)
echo -e "${YELLOW}🔢 Test 5: Análisis de rangos de series${NC}"
echo "────────────────────────────────────────────────"

ITEMS_WITH_RANGES=$(echo "$INVOICE_DATA" | jq '[.data.com_invoice_item[] | select(.range_start != null and .range_end != null)] | length')

if [ "$ITEMS_WITH_RANGES" -gt 0 ]; then
    echo -e "${GREEN}✅ Items con rangos de series: ${ITEMS_WITH_RANGES}${NC}"
    echo

    echo "$INVOICE_DATA" | jq '.data.com_invoice_item[] | select(.range_start != null) | {
        product: .product_name,
        range: "\(.range_start) → \(.range_end)",
        quantity_from_range: ((.range_end | tonumber) - (.range_start | tonumber) + 1),
        subtotal: .subtotal
    }'
else
    echo -e "${YELLOW}⚠️  Esta factura no tiene productos serializados con rangos${NC}"
fi

echo

# Test 6: Validación de totales
echo -e "${YELLOW}💰 Test 6: Validación de totales${NC}"
echo "────────────────────────────────────────────────"

INVOICE_TOTAL=$(echo "$INVOICE_DATA" | jq -r '.data.total')
ITEMS_TOTAL=$(echo "$INVOICE_DATA" | jq '[.data.com_invoice_item[].subtotal | tonumber] | add')
TAX_AMOUNT=$(echo "$INVOICE_DATA" | jq -r '.data.tax_amount')

echo "Total de factura: \$$INVOICE_TOTAL"
echo "Suma de items: \$$ITEMS_TOTAL"
echo "Impuesto: \$$TAX_AMOUNT"

# Calcular diferencia (puede haber pequeñas diferencias de redondeo)
DIFF=$(echo "$INVOICE_DATA" | jq "(.data.total | tonumber) - ([.data.com_invoice_item[].subtotal | tonumber] | add) - (.data.tax_amount | tonumber) | fabs")

if (( $(echo "$DIFF < 0.01" | bc -l) )); then
    echo -e "${GREEN}✅ Totales coinciden correctamente${NC}"
else
    echo -e "${YELLOW}⚠️  Diferencia en totales: \$${DIFF}${NC}"
    echo "   (Puede ser por descuentos o redondeos)"
fi

echo

# Test 7: Estructura de otros arrays
echo -e "${YELLOW}📋 Test 7: Otros arrays en respuesta${NC}"
echo "────────────────────────────────────────────────"

DETAILS_COUNT=$(echo "$INVOICE_DATA" | jq '.data.com_invoice_detail | length')
PAYMENTS_COUNT=$(echo "$INVOICE_DATA" | jq '.data.com_invoice_payment_summary | length')

echo "com_invoice_item: $ITEMS_COUNT items"
echo "com_invoice_detail: $DETAILS_COUNT detalles"
echo "com_invoice_payment_summary: $PAYMENTS_COUNT pagos"

echo

# Test 8: Prueba con factura específica (modo interactivo)
echo -e "${YELLOW}🔍 Test 8: Consulta personalizada${NC}"
echo "────────────────────────────────────────────────"
echo "¿Quieres consultar otra factura específica? (Ingresa ID o Enter para salir)"
read -p "Factura ID: " CUSTOM_ID

if [ ! -z "$CUSTOM_ID" ]; then
    echo
    echo "Consultando factura #${CUSTOM_ID}..."

    CUSTOM_DATA=$(make_request "$CUSTOM_ID")

    if [ $(echo "$CUSTOM_DATA" | jq -r '.code') -eq 0 ]; then
        echo "$CUSTOM_DATA" | jq '{
            id: .data.id,
            total: .data.total,
            customer: .data.customer_name,
            items_count: .data.com_invoice_item | length,
            items: [.data.com_invoice_item[] | {
                product: .product_name,
                quantity,
                unit_price,
                subtotal,
                range: (if .range_start then "\(.range_start) → \(.range_end)" else null end)
            }]
        }'
    else
        echo -e "${RED}❌ Error al consultar factura${NC}"
        echo "$CUSTOM_DATA" | jq '{code, message, error}'
    fi
fi

echo
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Testing completado exitosamente${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo
echo -e "📚 Ver documentación completa en:"
echo -e "   ${BLUE}docs/integrations/plusmovil-invoice-items-SOLVED.md${NC}"
echo
