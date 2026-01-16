#!/bin/bash

###############################################################################
# Script de Prueba: Endpoint /com-invoices de PlusMovil
#
# Prueba el endpoint de facturas comerciales con diferentes filtros
###############################################################################

set -e

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_header() {
    echo -e "\n${BLUE}==========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}==========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

###############################################################################
# VERIFICAR TOKEN
###############################################################################

if [ ! -f "docs/testing/.plusmovil-token-QA.txt" ]; then
    print_error "Token no encontrado. Ejecuta primero: ./docs/testing/get-plusmovil-token.sh"
    exit 1
fi

# Extraer token
ACCESS_TOKEN=$(grep "ACCESS_TOKEN=" docs/testing/.plusmovil-token-QA.txt | cut -d'=' -f2- | tr -d ' \n\r\t')

if [ -z "$ACCESS_TOKEN" ]; then
    print_error "No se pudo extraer el token"
    exit 1
fi

print_success "Token cargado (longitud: ${#ACCESS_TOKEN})"

# Base URL
BASE_URL="https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa"

###############################################################################
# FUNCIÓN HELPER PARA REQUESTS
###############################################################################

test_endpoint() {
    local DESCRIPTION="$1"
    local QUERY_PARAMS="$2"
    local ENDPOINT="${BASE_URL}/com-invoices${QUERY_PARAMS}"

    print_header "$DESCRIPTION"
    print_info "Endpoint: $ENDPOINT"
    echo ""

    RESPONSE=$(curl -s -w "\n%{http_code}" --max-time 30 -X GET "$ENDPOINT" \
        -H "Authorization: Bearer ${ACCESS_TOKEN}" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" 2>&1)

    if [ $? -ne 0 ]; then
        print_error "Error de conectividad o timeout"
        echo "$RESPONSE"
        return 1
    fi

    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    echo "HTTP Status: $HTTP_CODE"
    echo ""

    if [ "$HTTP_CODE" = "200" ]; then
        print_success "Respuesta exitosa"
        echo ""

        # Intentar parsear con jq si está disponible
        if command -v jq &> /dev/null; then
            # Verificar si es respuesta con metadata (code, message, data)
            if echo "$BODY" | jq -e '.data' &> /dev/null; then
                RECORD_COUNT=$(echo "$BODY" | jq '.data | length' 2>/dev/null || echo "?")
                CODE=$(echo "$BODY" | jq -r '.code' 2>/dev/null || echo "?")
                MESSAGE=$(echo "$BODY" | jq -r '.message' 2>/dev/null || echo "?")

                print_info "Code: $CODE"
                print_info "Message: $MESSAGE"
                print_info "Total de registros: $RECORD_COUNT"
                echo ""

                if [ "$RECORD_COUNT" != "?" ] && [ "$RECORD_COUNT" -gt "0" ]; then
                    echo "Primeros 2 registros (formato resumido):"
                    echo "$BODY" | jq '.data[:2] | map({
                        id,
                        invoice_number,
                        invoice_date,
                        customer_name,
                        total,
                        status,
                        distributor: .com_distributor.name,
                        branch: .com_branch.name
                    })' 2>/dev/null || echo "$BODY"
                else
                    echo "Sin registros en la respuesta"
                fi
            else
                # Respuesta directa sin metadata
                RECORD_COUNT=$(echo "$BODY" | jq '. | length' 2>/dev/null || echo "?")
                print_info "Total de registros: $RECORD_COUNT"
                echo ""

                if [ "$RECORD_COUNT" != "?" ] && [ "$RECORD_COUNT" -gt "0" ]; then
                    echo "Primeros 2 registros:"
                    echo "$BODY" | jq '.[:2]' 2>/dev/null || echo "$BODY"
                fi
            fi
        else
            print_warning "jq no está instalado. Mostrando respuesta raw:"
            echo "$BODY"
        fi
    else
        print_error "Error en la petición"
        echo ""
        echo "Respuesta:"
        echo "$BODY"
    fi

    echo ""
}

###############################################################################
# PRUEBA 1: CON PAGINACIÓN BÁSICA (evitar timeout)
###############################################################################

test_endpoint "PRUEBA 1: Últimas 10 facturas (con paginación)" "?limit=10&order_by=id&order_dir=desc"

###############################################################################
# PRUEBA 2: FILTRO POR FECHA (mes actual)
###############################################################################

CURRENT_MONTH=$(date +%Y-%m-01)
print_header "PRUEBA 2: Facturas del mes actual"
echo "Fecha desde: $CURRENT_MONTH"

test_endpoint "Facturas desde $CURRENT_MONTH" "?invoice_date_gte=${CURRENT_MONTH}&limit=10&order_by=invoice_date&order_dir=desc"

###############################################################################
# PRUEBA 3: FILTRO POR STATUS
###############################################################################

print_header "PRUEBA 3: Filtro por status"
echo "¿Deseas filtrar por status? (s/n)"
read -p "> " TEST_STATUS

if [ "$TEST_STATUS" = "s" ]; then
    echo ""
    print_info "Filtros de status disponibles:"
    echo "  - status=1 (un solo valor)"
    echo "  - status_in=1,2,3 (múltiples valores)"
    echo "  - status_ne=0 (distinto de)"
    echo ""
    print_info "Status comunes:"
    echo "  - 0: Pendiente?"
    echo "  - 1: Activa?"
    echo "  - 2: Pagada?"
    echo "  - 3: Anulada?"
    echo ""

    read -p "Ingresa el filtro de status (ej: status=1): " STATUS_FILTER

    if [ -n "$STATUS_FILTER" ]; then
        test_endpoint "Filtro por status" "?${STATUS_FILTER}&limit=10&order_by=id&order_dir=desc"
    fi
fi

###############################################################################
# PRUEBA 4: BÚSQUEDA POR CLIENTE
###############################################################################

print_header "PRUEBA 4: Búsqueda por cliente"
echo "¿Deseas buscar por nombre de cliente? (s/n)"
read -p "> " TEST_CUSTOMER

if [ "$TEST_CUSTOMER" = "s" ]; then
    echo ""
    read -p "Ingresa el nombre (o parte) del cliente: " CUSTOMER_NAME

    if [ -n "$CUSTOMER_NAME" ]; then
        test_endpoint "Búsqueda por cliente" "?customer_name_like=${CUSTOMER_NAME}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 5: BÚSQUEDA POR NÚMERO DE FACTURA
###############################################################################

print_header "PRUEBA 5: Búsqueda por número de factura"
echo "¿Deseas buscar por número de factura? (s/n)"
read -p "> " TEST_INVOICE_NUMBER

if [ "$TEST_INVOICE_NUMBER" = "s" ]; then
    echo ""
    read -p "Ingresa el número de factura (o parte): " INVOICE_NUMBER

    if [ -n "$INVOICE_NUMBER" ]; then
        test_endpoint "Búsqueda por número" "?invoice_number_like=${INVOICE_NUMBER}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 6: FILTRO POR RANGO DE MONTO
###############################################################################

print_header "PRUEBA 6: Filtro por rango de monto total"
echo "¿Deseas filtrar por rango de monto? (s/n)"
read -p "> " TEST_AMOUNT

if [ "$TEST_AMOUNT" = "s" ]; then
    echo ""
    read -p "Monto mínimo: " MIN_AMOUNT
    read -p "Monto máximo: " MAX_AMOUNT

    if [ -n "$MIN_AMOUNT" ] && [ -n "$MAX_AMOUNT" ]; then
        test_endpoint "Rango de monto: $MIN_AMOUNT - $MAX_AMOUNT" "?total_gte=${MIN_AMOUNT}&total_lte=${MAX_AMOUNT}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 7: FILTRO POR DISTRIBUIDOR
###############################################################################

print_header "PRUEBA 7: Filtro por distribuidor"
echo "¿Deseas filtrar por distribuidor (com_distributor_id)? (s/n)"
read -p "> " TEST_DISTRIBUTOR

if [ "$TEST_DISTRIBUTOR" = "s" ]; then
    echo ""
    read -p "Ingresa el ID del distribuidor: " DISTRIBUTOR_ID

    if [ -n "$DISTRIBUTOR_ID" ]; then
        test_endpoint "Filtro por distribuidor ID $DISTRIBUTOR_ID" "?com_distributor_id=${DISTRIBUTOR_ID}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 8: RANGO DE FECHAS (últimos 30 días)
###############################################################################

print_header "PRUEBA 8: Facturas de los últimos 30 días"

DATE_FROM=$(date -d "30 days ago" +%Y-%m-%d)
DATE_TO=$(date +%Y-%m-%d)

echo "Rango: $DATE_FROM a $DATE_TO"

test_endpoint "Últimos 30 días" "?invoice_date_between=${DATE_FROM},${DATE_TO}&limit=15&order_by=invoice_date&order_dir=desc"

###############################################################################
# PRUEBA 9: FACTURAS CON BALANCE PENDIENTE
###############################################################################

print_header "PRUEBA 9: Facturas con balance pendiente"
echo "Facturas donde balance > 0"

test_endpoint "Balance pendiente" "?balance_gt=0&limit=10&order_by=balance&order_dir=desc"

###############################################################################
# PRUEBA 10: CONSULTA COMPLEJA PERSONALIZADA
###############################################################################

print_header "PRUEBA 10: Consulta compleja personalizada"
echo "¿Deseas crear una consulta personalizada? (s/n)"
read -p "> " TEST_CUSTOM

if [ "$TEST_CUSTOM" = "s" ]; then
    echo ""
    print_info "Ejemplos de consultas complejas:"
    echo "  - status=1&total_gte=100&invoice_date_gte=2025-01-01&limit=20"
    echo "  - customer_name_like=maria&status_in=1,2&order_by=total&order_dir=desc&limit=10"
    echo "  - balance_gt=0&invoice_date_between=2025-10-01,2025-10-31&limit=15"
    echo ""

    read -p "Ingresa los parámetros: " CUSTOM_PARAMS

    if [ -n "$CUSTOM_PARAMS" ]; then
        test_endpoint "Consulta personalizada" "?${CUSTOM_PARAMS}"
    fi
fi

###############################################################################
# PRUEBA 11: OBTENER UNA FACTURA ESPECÍFICA (si conocemos el ID)
###############################################################################

print_header "PRUEBA 11: Obtener factura específica por ID"
echo "¿Deseas obtener una factura específica por ID? (s/n)"
read -p "> " TEST_SPECIFIC

if [ "$TEST_SPECIFIC" = "s" ]; then
    echo ""
    read -p "Ingresa el ID de la factura: " INVOICE_ID

    if [ -n "$INVOICE_ID" ]; then
        test_endpoint "Factura ID: $INVOICE_ID" "?id=${INVOICE_ID}"
    fi
fi

###############################################################################
# RESUMEN Y ANÁLISIS
###############################################################################

print_header "RESUMEN DE PRUEBAS COMPLETADAS"

print_success "Pruebas finalizadas"
echo ""

print_info "Campos disponibles en com_invoices:"
echo "  - id (integer) - ID único de factura"
echo "  - com_order_id (integer) - Referencia a orden"
echo "  - com_distributor_id (integer) - ID distribuidor"
echo "  - com_branch_id (integer) - ID sucursal"
echo "  - com_pos_id (integer) - ID punto de venta"
echo "  - com_seller_id (integer) - ID vendedor"
echo "  - com_settlement_id (integer) - ID liquidación"
echo "  - ops_route_id (integer) - ID ruta operacional"
echo "  - invoice_type (integer) - Tipo de factura"
echo "  - invoice_number (string) - Número de factura"
echo "  - invoice_date (datetime) - Fecha de factura"
echo "  - customer_name (string) - Nombre cliente"
echo "  - customer_phone (string) - Teléfono"
echo "  - customer_email (string) - Email"
echo "  - customer_vat_number (string) - RUC/NIT"
echo "  - billing_address (string) - Dirección"
echo "  - sub_total (decimal) - Subtotal"
echo "  - discount (decimal) - Descuento"
echo "  - tax_amount (decimal) - Monto de impuestos"
echo "  - total (decimal) - Total"
echo "  - balance (decimal) - Balance pendiente"
echo "  - status (integer) - Estado"
echo "  - comments (string) - Comentarios"
echo "  - liquidated_type (integer) - Tipo liquidación"
echo "  - liquidated_at (datetime) - Fecha liquidación"
echo "  - created_user_id (integer)"
echo "  - created_at (datetime)"
echo "  - updated_user_id (integer)"
echo "  - updated_at (datetime)"
echo ""

print_info "Filtros más útiles para DocuCenter:"
echo "  ✅ invoice_date_between - Sincronizar facturas por rango de fechas"
echo "  ✅ invoice_date_gte - Facturas desde última sincronización"
echo "  ✅ status_in - Filtrar por estados específicos"
echo "  ✅ balance_gt=0 - Facturas con saldo pendiente"
echo "  ✅ com_distributor_id - Filtrar por distribuidor"
echo "  ✅ customer_vat_number_like - Buscar por RUC/NIT"
echo "  ✅ invoice_number_like - Buscar factura específica"
echo ""

print_info "Casos de uso para integración DocuCenter:"
echo "  1. Sincronización incremental:"
echo "     ?invoice_date_gte=2025-10-01&limit=100&order_by=invoice_date&order_dir=asc"
echo ""
echo "  2. Facturas pendientes de pago:"
echo "     ?balance_gt=0&status_ne=3&limit=50"
echo ""
echo "  3. Facturas de un cliente específico:"
echo "     ?customer_vat_number=12345678-9-1&limit=20"
echo ""
echo "  4. Reporte diario:"
echo "     ?invoice_date_between=2025-11-04,2025-11-04&limit=100"
echo ""

print_info "Relaciones detectadas (si incluye en respuesta):"
echo "  - com_distributor (distribuidor)"
echo "  - com_branch (sucursal)"
echo "  - com_pos (punto de venta)"
echo "  - com_seller (vendedor)"
echo "  - com_order (orden relacionada)"
echo ""

print_warning "IMPORTANTE: SIEMPRE usar 'limit' para evitar timeouts (502)"
echo ""

print_info "Próximos pasos sugeridos:"
echo "  1. ✅ Validar estructura de respuesta completa"
echo "  2. ✅ Identificar valores de status (0,1,2,3...)"
echo "  3. ✅ Mapear tipos de factura (invoice_type)"
echo "  4. 📋 Crear PlusMovilInvoiceService en Laravel"
echo "  5. 📋 Implementar sincronización incremental"
echo "  6. 📋 Mapear campos a Sales_Header_Imp de DocuCenter"
echo ""

print_success "Script completado"
