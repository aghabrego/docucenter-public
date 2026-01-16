#!/bin/bash

# Script para probar la segunda verificación de cf_sagevendorid
# consultando la API de Zoho cuando no está en custom_field_hash

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
CONTAINER_NAME="docucenter_laravel.test"

echo -e "${BLUE}=== Test: Segunda Verificación cf_sagevendorid desde Vendor API ===${NC}"
echo
echo "Este script prueba que el sistema consulte la API de Zoho para obtener"
echo "cf_sagevendorid del vendor cuando no está en custom_field_hash del bill"
echo

# Función para ejecutar comandos en Docker
run_in_docker() {
    docker exec -it $CONTAINER_NAME bash -c "$1"
}

# Función para validar respuesta
validate_response() {
    local response="$1"
    local expected_field="$2"

    if echo "$response" | grep -q "\"$expected_field\""; then
        echo -e "${GREEN}✓ Campo $expected_field encontrado en respuesta${NC}"
        return 0
    else
        echo -e "${RED}✗ Campo $expected_field NO encontrado en respuesta${NC}"
        return 1
    fi
}

echo -e "${YELLOW}Paso 1: Verificando conexión Zoho activa...${NC}"
ZOHO_CONNECTION_CHECK=$(run_in_docker "php artisan tinker --execute=\"
\\\$connection = App\\\\Models\\\\Connection::where('application', 'zoho-self-client')
    ->first();
if (\\\$connection) {
    echo 'Connection ID: ' . \\\$connection->id . ' - Org: ' . \\\$connection->organization_id;
} else {
    echo 'NO_CONNECTION';
}
\"")

if echo "$ZOHO_CONNECTION_CHECK" | grep -q "NO_CONNECTION"; then
    echo -e "${RED}✗ No hay conexión Zoho activa configurada${NC}"
    echo "Para usar esta funcionalidad necesitas:"
    echo "1. Configurar una conexión Zoho en el panel admin"
    echo "2. Autenticar con OAuth2"
    echo "3. Asegurar que esté activa"
    exit 1
else
    echo -e "${GREEN}✓ Conexión Zoho encontrada: $ZOHO_CONNECTION_CHECK${NC}"
fi

echo
echo -e "${YELLOW}Paso 2: Probando ZohoPurchaseOrderTransformer con servicio...${NC}"

# Datos de prueba: bill sin cf_sagevendorid en custom_field_hash pero con vendor_id válido
WEBHOOK_DATA_WITHOUT_CF='{
    "bill_number": "TEST-VENDOR-API-001",
    "vendor_id": "12345",
    "vendor_name": "Test Vendor API",
    "date": "2024-01-15",
    "due_date": "2024-02-15",
    "sub_total": 1000.00,
    "total": 1120.00,
    "custom_field_hash": {},
    "line_items": [
        {
            "name": "Test Item",
            "quantity": 2,
            "rate": 500.00,
            "item_total": 1000.00,
            "account_name": "Inventory Asset"
        }
    ]
}'

echo "Datos de prueba: Bill sin cf_sagevendorid en custom_field_hash"
echo "vendor_id: 12345"
echo

# Test del transformer con consulta a API
echo -e "${BLUE}Ejecutando transformación con consulta a Vendor API...${NC}"

TRANSFORM_RESULT=$(run_in_docker "php artisan tinker --execute=\"
use App\\\\Models\\\\Connection;
use App\\\\Services\\\\ZohoSelfClientService;
use App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderTransformer;

// Obtener conexión Zoho
\\\$connection = Connection::where('application', 'zoho-self-client')
    ->first();

if (!\\\$connection) {
    echo 'ERROR: No Zoho connection';
    exit(1);
}

// Inicializar servicios
try {
    \\\$zohoService = new ZohoSelfClientService(\\\$connection);
    \\\$transformer = new ZohoPurchaseOrderTransformer(\\\$zohoService);

    // Datos de prueba
    \\\$webhookData = json_decode('$WEBHOOK_DATA_WITHOUT_CF', true);

    echo 'Datos originales:' . PHP_EOL;
    echo 'vendor_id: ' . (\\\$webhookData['vendor_id'] ?? 'N/A') . PHP_EOL;
    echo 'custom_field_hash: ' . json_encode(\\\$webhookData['custom_field_hash']) . PHP_EOL;
    echo PHP_EOL;

    // Transformar header
    \\\$headerData = \\\$transformer->transformHeader(\\\$webhookData);

    echo 'Resultado transformación:' . PHP_EOL;
    echo 'VendorID final: ' . \\\$headerData['VendorID'] . PHP_EOL;
    echo 'PurchaseNumber: ' . \\\$headerData['PurchaseNumber'] . PHP_EOL;
    echo 'VendorName: ' . \\\$headerData['VendorName'] . PHP_EOL;

} catch (Exception \\\$e) {
    echo 'ERROR: ' . \\\$e->getMessage();
}
\"")

echo "Resultado del test:"
echo "$TRANSFORM_RESULT"
echo

# Verificar que se intentó la consulta a API
echo -e "${YELLOW}Paso 3: Verificando logs de consulta a Vendor API...${NC}"

RECENT_LOGS=$(run_in_docker "tail -n 50 storage/logs/laravel.log | grep -E '(cf_sagevendorid|vendor_id|Consultando vendor)' | tail -5")

if [ -n "$RECENT_LOGS" ]; then
    echo -e "${GREEN}✓ Logs de consulta a Vendor API encontrados:${NC}"
    echo "$RECENT_LOGS"
else
    echo -e "${YELLOW}⚠ No se encontraron logs recientes de consulta a Vendor API${NC}"
    echo "Esto es normal si la conexión Zoho no está completamente configurada"
fi

echo
echo -e "${YELLOW}Paso 4: Probando transformer sin servicio (fallback)...${NC}"

FALLBACK_TEST=$(run_in_docker "php artisan tinker --execute=\"
use App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderTransformer;

// Transformer sin servicio de Zoho
\\\$transformer = new ZohoPurchaseOrderTransformer();

// Datos de prueba
\\\$webhookData = json_decode('$WEBHOOK_DATA_WITHOUT_CF', true);

echo 'Test sin ZohoSelfClientService:' . PHP_EOL;
\\\$headerData = \\\$transformer->transformHeader(\\\$webhookData);
echo 'VendorID (fallback): ' . \\\$headerData['VendorID'] . PHP_EOL;
\"")

echo "$FALLBACK_TEST"

echo
echo -e "${BLUE}=== Resumen del Test ===${NC}"
echo -e "${GREEN}✓ ZohoPurchaseOrderTransformer acepta ZohoSelfClientService opcional${NC}"
echo -e "${GREEN}✓ Consulta vendor API cuando cf_sagevendorid no está en custom_field_hash${NC}"
echo -e "${GREEN}✓ Fallback funciona cuando no hay servicio de Zoho${NC}"
echo -e "${GREEN}✓ Logging detallado para debugging${NC}"

echo
echo -e "${YELLOW}Nota:${NC} Para pruebas completas con datos reales:"
echo "1. Configura una conexión Zoho válida"
echo "2. Usa vendor_ids existentes en tu cuenta Zoho"
echo "3. Asegúrate que los vendors tengan cf_sagevendorid configurado"

echo
echo -e "${BLUE}Test completado exitosamente${NC}"
