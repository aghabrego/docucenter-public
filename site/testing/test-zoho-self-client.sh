#!/bin/bash

# Script de Testing para Zoho Self Client Integration
# Ubicación: docs/testing/test-zoho-self-client.sh
# Uso: ./test-zoho-self-client.sh [connection_id] [organization_id]

set -e

# Configuración
DEFAULT_BASE_URL="http://localhost"
DEFAULT_CONNECTION_ID="1"
DEFAULT_ORG_ID="1"

# Parámetros
CONNECTION_ID=${1:-$DEFAULT_CONNECTION_ID}
ORG_ID=${2:-$DEFAULT_ORG_ID}
BASE_URL=${3:-$DEFAULT_BASE_URL}

echo "🧪 TESTING ZOHO SELF CLIENT INTEGRATION"
echo "========================================"
echo "📍 Base URL: $BASE_URL"
echo "🔗 Connection ID: $CONNECTION_ID"
echo "🏢 Organization ID: $ORG_ID"
echo ""

# Función para hacer requests
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4

    echo "🔄 $description"
    echo "   $method $endpoint"

    if [ -n "$data" ]; then
        curl -s -X $method \
             -H "Content-Type: application/json" \
             -H "Accept: application/json" \
             -d "$data" \
             "$BASE_URL$endpoint" | jq '.' || echo "Error: Invalid JSON response"
    else
        curl -s -X $method \
             -H "Accept: application/json" \
             "$BASE_URL$endpoint" | jq '.' || echo "Error: Invalid JSON response"
    fi

    echo ""
}

# Test 1: Verificar información de conexión
echo "1️⃣  VERIFICAR INFORMACIÓN DE CONEXIÓN"
echo "------------------------------------"
make_request "GET" "/zoho/info/$CONNECTION_ID" "" "Obtener información de conexión"

# Test 2: Probar conexión
echo "2️⃣  PROBAR CONEXIÓN"
echo "-------------------"
make_request "GET" "/zoho/test/$CONNECTION_ID" "" "Test de conectividad"

# Test 3: Simular crear conexión (ejemplo de datos)
echo "3️⃣  EJEMPLO DE CREACIÓN DE CONEXIÓN"
echo "------------------------------------"
CONNECTION_DATA='{
    "organization_id": "'$ORG_ID'",
    "name": "Zoho Self Client Test",
    "application": "zoho-self-client",
    "settings": {
        "client_id": "1000.XXXXXXXXXXXXXXXXXXXXXXXXXX",
        "client_secret": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "redirect_uri": "'$BASE_URL'/zoho/callback",
        "domain": "com",
        "scope": "ZohoBooks.fullaccess.all"
    }
}'

echo "Datos de ejemplo para crear conexión:"
echo "$CONNECTION_DATA" | jq '.'
echo ""

# Test 4: URLs importantes del flujo OAuth
echo "4️⃣  URLS DEL FLUJO OAUTH"
echo "------------------------"
echo "🔐 Iniciar autorización: $BASE_URL/zoho/auth/$CONNECTION_ID"
echo "📞 Callback URL: $BASE_URL/zoho/callback"
echo "🔄 Test conexión: $BASE_URL/zoho/test/$CONNECTION_ID"
echo "ℹ️  Info conexión: $BASE_URL/zoho/info/$CONNECTION_ID"
echo "❌ Revocar tokens: $BASE_URL/zoho/revoke/$CONNECTION_ID"
echo ""

# Test 5: Verificar configuración de Zoho
echo "5️⃣  VERIFICACIÓN DE CONFIGURACIÓN"
echo "----------------------------------"
echo "✅ Archivo de configuración: config/zoho.php"
echo "✅ Variables de entorno requeridas:"
echo "   - ZOHO_CLIENT_ID (opcional - se configura por conexión)"
echo "   - ZOHO_CLIENT_SECRET (opcional - se configura por conexión)"
echo "   - ZOHO_REDIRECT_URI (opcional - default: APP_URL/zoho/callback)"
echo "   - ZOHO_DEFAULT_DOMAIN (opcional - default: com)"
echo ""

# Test 6: Comandos útiles para debugging
echo "6️⃣  COMANDOS ÚTILES PARA DEBUGGING"
echo "----------------------------------"
echo "📋 Ver logs: tail -f storage/logs/laravel.log | grep -i zoho"
echo "🗃️  Ver conexiones: SELECT * FROM connections WHERE application='zoho-self-client';"
echo "🔑 Limpiar tokens: UPDATE connections SET settings=... WHERE id=$CONNECTION_ID;"
echo ""

# Test 7: Flujo completo recomendado
echo "7️⃣  FLUJO COMPLETO RECOMENDADO"
echo "------------------------------"
echo "1. Crear Self Client en Zoho Developer Console"
echo "   📱 https://api-console.zoho.com/"
echo ""
echo "2. Configurar datos en DocuCenter:"
echo "   - Client ID y Client Secret del Self Client"
echo "   - Redirect URI: $BASE_URL/zoho/callback"
echo "   - Scope: ZohoBooks.fullaccess.all"
echo "   - Domain: com (o eu, in según región)"
echo ""
echo "3. Iniciar autorización:"
echo "   🌐 Visitar: $BASE_URL/zoho/auth/$CONNECTION_ID"
echo ""
echo "4. Completar flujo OAuth en Zoho"
echo ""
echo "5. Verificar tokens guardados:"
echo "   🧪 $BASE_URL/zoho/test/$CONNECTION_ID"
echo ""

# Test 8: Ejemplos de datos de Zoho
echo "8️⃣  EJEMPLOS DE DATOS DE ZOHO"
echo "-----------------------------"
echo "📊 Organizaciones:"
echo '{
  "organizations": [
    {
      "organization_id": "123456789",
      "name": "Mi Empresa",
      "currency_code": "USD",
      "time_zone": "America/New_York"
    }
  ]
}'
echo ""

echo "📋 Sales Order:"
echo '{
  "salesorder": {
    "salesorder_id": "123456789",
    "salesorder_number": "SO-00001",
    "customer_name": "Cliente Test",
    "date": "2024-01-15",
    "total": 1000.00,
    "line_items": [
      {
        "item_id": "123456789",
        "name": "Producto Test",
        "quantity": 2,
        "rate": 500.00
      }
    ]
  }
}'
echo ""

echo "✅ TESTING COMPLETADO"
echo "====================="
echo "🔗 Para más información: docs/technical/zoho-self-client-implementation-plan.md"
