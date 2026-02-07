#!/bin/bash

# Script para probar el endpoint de Zoho Sales Order en ACI Cloud
# Ubicación: docs/testing/test-zoho-sales-order.sh
# Uso: ./test-zoho-sales-order.sh [organization_id] [bearer_token]

set -e

# Configuración por defecto
DEFAULT_ORG_ID="1"
DEFAULT_BASE_URL="http://localhost"
SAMPLE_FILE="docs/testing/test-zoho-sales-order-sample.json"

# Verificar argumentos
ORG_ID=${1:-$DEFAULT_ORG_ID}
BEARER_TOKEN=${2:-""}

if [ -z "$BEARER_TOKEN" ]; then
    echo "⚠️  Bearer token no proporcionado. Si falla, proporciona un token válido como segundo parámetro."
    echo "Uso: $0 [organization_id] [bearer_token]"
    echo ""
fi

echo "🧪 INICIANDO PRUEBAS DE ZOHO SALES ORDER API"
echo "=============================================="
echo "📍 Endpoint: POST /api/acicloud/create_sale_order_zoho"
echo "🏢 Organization ID: $ORG_ID"
echo "📁 Sample file: $SAMPLE_FILE"
echo ""

# Verificar que el archivo sample existe
if [ ! -f "$SAMPLE_FILE" ]; then
    echo "❌ Error: Archivo sample no encontrado: $SAMPLE_FILE"
    exit 1
fi

echo "📋 Contenido del sample JSON:"
echo "$(cat $SAMPLE_FILE | jq '.' 2>/dev/null || cat $SAMPLE_FILE)"
echo ""

# Preparar headers
HEADERS="-H 'Content-Type: application/json'"
HEADERS="$HEADERS -H 'Accept: application/json'"
HEADERS="$HEADERS -H 'X-Organization-ID: $ORG_ID'"

if [ ! -z "$BEARER_TOKEN" ]; then
    HEADERS="$HEADERS -H 'Authorization: Bearer $BEARER_TOKEN'"
fi

echo "🚀 Enviando request..."
echo "URL: $DEFAULT_BASE_URL/api/acicloud/create_sale_order_zoho"
echo ""

# Realizar la petición
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}\n" \
    -X POST \
    $HEADERS \
    -d @"$SAMPLE_FILE" \
    "$DEFAULT_BASE_URL/api/acicloud/create_sale_order_zoho")

# Separar response body y status code
HTTP_BODY=$(echo "$RESPONSE" | sed '$d')
HTTP_STATUS=$(echo "$RESPONSE" | tail -n1 | sed 's/HTTP_STATUS://')

echo "📊 RESULTADOS:"
echo "=============="
echo "🌐 HTTP Status: $HTTP_STATUS"
echo ""
echo "📄 Response Body:"
echo "$HTTP_BODY" | jq '.' 2>/dev/null || echo "$HTTP_BODY"
echo ""

# Analizar el resultado
case $HTTP_STATUS in
    200|201)
        echo "✅ SUCCESS: Sales order creado exitosamente"
        echo "💾 Revisa los logs de Laravel para ver el debug detallado"
        ;;
    400)
        echo "⚠️  BAD REQUEST: Error de validación en los datos"
        ;;
    401)
        echo "🔐 UNAUTHORIZED: Token de autenticación requerido o inválido"
        ;;
    422)
        echo "📝 VALIDATION ERROR: Errores de validación en el request"
        ;;
    500)
        echo "💥 SERVER ERROR: Error interno del servidor"
        ;;
    *)
        echo "❓ Status code inesperado: $HTTP_STATUS"
        ;;
esac

echo ""
echo "🔍 DEBUGGING:"
echo "============="
echo "Para ver logs detallados, ejecuta:"
echo "docker-compose exec laravel.test tail -f storage/logs/laravel.log | grep 'DEBUG ACIcloudService::createSaleOrderZoho'"
echo ""
echo "Para limpiar logs y ver solo esta prueba:"
echo "docker-compose exec laravel.test php artisan log:clear"
echo "# Luego ejecuta este script nuevamente"

exit 0
