#!/bin/bash

###############################################################################
# Script de Prueba: Endpoint /inv-products de PlusMovil
#
# Prueba el endpoint de inventario de productos con diferentes filtros
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
    local ENDPOINT="${BASE_URL}/inv-products${QUERY_PARAMS}"

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

        # Intentar contar registros
        if command -v jq &> /dev/null; then
            RECORD_COUNT=$(echo "$BODY" | jq '. | length' 2>/dev/null || echo "?")
            print_info "Total de registros: $RECORD_COUNT"
            echo ""

            # Mostrar primeros registros
            if [ "$RECORD_COUNT" != "?" ] && [ "$RECORD_COUNT" -gt "0" ]; then
                echo "Primeros registros:"
                echo "$BODY" | jq '.[:3]' 2>/dev/null || echo "$BODY"
            else
                echo "Respuesta completa:"
                echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
            fi
        else
            echo "Respuesta:"
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
# PRUEBA 1: SIN FILTROS (primeros registros)
###############################################################################

test_endpoint "PRUEBA 1: Sin filtros - Primeros registros" ""

###############################################################################
# PRUEBA 2: CON LIMIT Y OFFSET (paginación básica)
###############################################################################

test_endpoint "PRUEBA 2: Paginación básica (limit=10, offset=0)" "?limit=10&offset=0"

###############################################################################
# PRUEBA 3: BÚSQUEDA PARCIAL POR CÓDIGO
###############################################################################

print_header "PRUEBA 3: Búsqueda parcial por código"
echo "¿Deseas probar búsqueda por código/nombre? (s/n)"
read -p "> " TEST_SEARCH

if [ "$TEST_SEARCH" = "s" ]; then
    echo ""
    print_info "Ejemplos de búsqueda:"
    echo "  - Por código: code_like=ABC"
    echo "  - Por nombre (si existe campo): name_like=producto"
    echo "  - Por barcode: barcode_like=123"
    echo ""

    read -p "Ingresa el parámetro de búsqueda (ej: code_like=ABC): " SEARCH_PARAM

    if [ -n "$SEARCH_PARAM" ]; then
        test_endpoint "Búsqueda personalizada" "?${SEARCH_PARAM}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 4: FILTROS POR STATUS
###############################################################################

print_header "PRUEBA 4: Filtro por status"
echo "¿Deseas filtrar por status? (s/n)"
read -p "> " TEST_STATUS

if [ "$TEST_STATUS" = "s" ]; then
    echo ""
    print_info "Filtros de status disponibles:"
    echo "  - status=1 (un solo valor)"
    echo "  - status_in=1,2,3 (múltiples valores)"
    echo "  - status_ne=0 (distinto de)"
    echo ""

    read -p "Ingresa el filtro de status (ej: status_in=1,2): " STATUS_FILTER

    if [ -n "$STATUS_FILTER" ]; then
        test_endpoint "Filtro por status" "?${STATUS_FILTER}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 5: FILTROS POR RANGO DE ID
###############################################################################

test_endpoint "PRUEBA 5: Rango de IDs (id_gte=1, id_lte=100)" "?id_gte=1&id_lte=100&limit=10"

###############################################################################
# PRUEBA 6: FILTROS POR WAREHOUSE
###############################################################################

print_header "PRUEBA 6: Filtro por bodega/almacén"
echo "¿Deseas filtrar por warehouse (inv_warehouse_id)? (s/n)"
read -p "> " TEST_WAREHOUSE

if [ "$TEST_WAREHOUSE" = "s" ]; then
    echo ""
    read -p "Ingresa el ID del warehouse: " WAREHOUSE_ID

    if [ -n "$WAREHOUSE_ID" ]; then
        test_endpoint "Filtro por warehouse" "?inv_warehouse_id=${WAREHOUSE_ID}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 7: ORDENAMIENTO
###############################################################################

test_endpoint "PRUEBA 7: Ordenamiento por ID descendente" "?order_by=id&order_dir=desc&limit=10"

###############################################################################
# PRUEBA 8: ORDENAMIENTO MÚLTIPLE
###############################################################################

test_endpoint "PRUEBA 8: Ordenamiento múltiple (status ASC, id DESC)" "?order_by=status,id&order_dir=asc,desc&limit=10"

###############################################################################
# PRUEBA 9: FILTROS DE FECHA
###############################################################################

print_header "PRUEBA 9: Filtros de fecha"
echo "¿Deseas probar filtros de fecha? (s/n)"
read -p "> " TEST_DATE

if [ "$TEST_DATE" = "s" ]; then
    echo ""
    print_info "Filtros de fecha soportados:"
    echo "  - created_at_gte=2024-01-01 (mayor o igual)"
    echo "  - created_at_lte=2024-12-31 (menor o igual)"
    echo "  - created_at_between=2024-01-01,2024-12-31 (rango)"
    echo ""

    read -p "Ingresa el filtro de fecha (ej: created_at_gte=2024-01-01): " DATE_FILTER

    if [ -n "$DATE_FILTER" ]; then
        test_endpoint "Filtro por fecha" "?${DATE_FILTER}&limit=10"
    fi
fi

###############################################################################
# PRUEBA 10: CONSULTA COMPLEJA
###############################################################################

print_header "PRUEBA 10: Consulta compleja combinada"
echo "¿Deseas crear una consulta personalizada? (s/n)"
read -p "> " TEST_CUSTOM

if [ "$TEST_CUSTOM" = "s" ]; then
    echo ""
    print_info "Ingresa los parámetros completos"
    print_info "Ejemplo: status_in=1,2&code_like=ABC&order_by=created_at&order_dir=desc&limit=20"
    echo ""

    read -p "Parámetros: " CUSTOM_PARAMS

    if [ -n "$CUSTOM_PARAMS" ]; then
        test_endpoint "Consulta personalizada" "?${CUSTOM_PARAMS}"
    fi
fi

###############################################################################
# RESUMEN
###############################################################################

print_header "RESUMEN DE PRUEBAS COMPLETADAS"

print_success "Pruebas finalizadas"
echo ""

print_info "Filtros probados:"
echo "  ✅ Sin filtros (registros por defecto)"
echo "  ✅ Paginación (limit, offset)"
echo "  ✅ Rangos numéricos (id_gte, id_lte)"
echo "  ✅ Ordenamiento simple y múltiple"
echo ""

print_info "Campos disponibles en inv_products:"
echo "  - id (integer)"
echo "  - cat_product_id (integer)"
echo "  - inv_warehouse_id (integer)"
echo "  - inv_batch_id (integer)"
echo "  - batch_number (string)"
echo "  - code (string)"
echo "  - barcode (string)"
echo "  - sku (string)"
echo "  - stock_control_type (integer)"
echo "  - status (integer)"
echo "  - created_user_id (integer)"
echo "  - created_at (datetime)"
echo "  - updated_user_id (integer)"
echo "  - updated_at (datetime)"
echo ""

print_info "Próximos pasos sugeridos:"
echo "  1. Documentar estructura de datos real"
echo "  2. Identificar valores comunes de status"
echo "  3. Mapear relaciones con cat_product y warehouses"
echo "  4. Implementar servicio en DocuCenter"
echo ""

print_success "Script completado"
