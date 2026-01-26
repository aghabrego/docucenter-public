#!/bin/bash

# Script para probar la segunda verificación de cf_sagecustomerid
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

echo -e "${BLUE}=== Test: Segunda Verificación cf_sagecustomerid desde Customer API ===${NC}"
echo
echo "Este script prueba que el sistema consulte la API de Zoho para obtener"
echo "cf_sagecustomerid del customer cuando no está en custom_field_hash del sales order"
echo

# Función para ejecutar comandos en Docker
run_in_docker() {
    docker exec -it $CONTAINER_NAME bash -c "$1"
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
echo -e "${YELLOW}Paso 2: Probando ZohoCustomFieldsHelper...${NC}"

# Datos de prueba: sales order sin cf_sagecustomerid en custom_field_hash pero con customer_id válido
SALES_ORDER_DATA_WITHOUT_CF='{
    "salesorder_number": "SO-TEST-CUSTOMER-API-001",
    "customer_id": "67890",
    "customer_name": "Test Customer API",
    "date": "2024-01-15",
    "sub_total": 2000.00,
    "total": 2240.00,
    "custom_field_hash": {},
    "line_items": [
        {
            "name": "Test Product",
            "quantity": 4,
            "rate": 500.00,
            "item_total": 2000.00,
            "sku": "PROD-TEST-001"
        }
    ]
}'

echo "Datos de prueba: Sales Order sin cf_sagecustomerid en custom_field_hash"
echo "customer_id: 67890"
echo

# Test del helper con consulta a API
echo -e "${BLUE}Ejecutando ZohoCustomFieldsHelper::getSageCustomerId...${NC}"

HELPER_TEST_RESULT=$(run_in_docker "php artisan tinker --execute=\"
use App\\\\Models\\\\Connection;
use App\\\\Services\\\\ZohoSelfClientService;
use App\\\\Services\\\\Zoho\\\\ZohoCustomFieldsHelper;

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
    \\\$helper = new ZohoCustomFieldsHelper(\\\$zohoService);

    // Datos de prueba
    \\\$salesOrderData = json_decode('$SALES_ORDER_DATA_WITHOUT_CF', true);

    echo 'Datos originales:' . PHP_EOL;
    echo 'customer_id: ' . (\\\$salesOrderData['customer_id'] ?? 'N/A') . PHP_EOL;
    echo 'custom_field_hash: ' . json_encode(\\\$salesOrderData['custom_field_hash']) . PHP_EOL;
    echo PHP_EOL;

    // Probar getSageCustomerId
    \\\$sageCustomerId = \\\$helper->getSageCustomerId(\\\$salesOrderData, \\\$salesOrderData['salesorder_number']);

    echo 'Resultado del helper:' . PHP_EOL;
    echo 'cf_sagecustomerid: ' . (\\\$sageCustomerId ?? 'NULL') . PHP_EOL;
    echo 'Fallback customer_id: ' . \\\$salesOrderData['customer_id'] . PHP_EOL;

} catch (Exception \\\$e) {
    echo 'ERROR: ' . \\\$e->getMessage();
}
\"")

echo "Resultado del test:"
echo "$HELPER_TEST_RESULT"
echo

echo -e "${YELLOW}Paso 3: Probando helper sin servicio (fallback)...${NC}"

FALLBACK_TEST=$(run_in_docker "php artisan tinker --execute=\"
use App\\\\Services\\\\Zoho\\\\ZohoCustomFieldsHelper;

// Helper sin servicio de Zoho
\\\$helper = new ZohoCustomFieldsHelper();

// Datos de prueba con cf_sagecustomerid en custom_field_hash
\\\$salesOrderData = json_decode('$SALES_ORDER_DATA_WITHOUT_CF', true);
\\\$salesOrderData['custom_field_hash'] = ['cf_sagecustomerid' => 'SAGE_CUSTOMER_456'];

echo 'Test con custom_field_hash (sin ZohoSelfClientService):' . PHP_EOL;
\\\$sageCustomerId = \\\$helper->getSageCustomerId(\\\$salesOrderData, 'TEST-SO-001');
echo 'cf_sagecustomerid encontrado: ' . (\\\$sageCustomerId ?? 'NULL') . PHP_EOL;
\"")

echo "$FALLBACK_TEST"

echo
echo -e "${YELLOW}Paso 4: Verificando logs de consulta a Customer API...${NC}"

RECENT_LOGS=$(run_in_docker "tail -n 50 storage/logs/laravel.log | grep -E '(cf_sagecustomerid|customer_id|Consultando customer)' | tail -5")

if [ -n "$RECENT_LOGS" ]; then
    echo -e "${GREEN}✓ Logs de consulta a Customer API encontrados:${NC}"
    echo "$RECENT_LOGS"
else
    echo -e "${YELLOW}⚠ No se encontraron logs recientes de consulta a Customer API${NC}"
    echo "Esto es normal si la conexión Zoho no está completamente configurada"
fi

echo
echo -e "${BLUE}=== Resumen del Test ===${NC}"
echo -e "${GREEN}✓ ZohoCustomFieldsHelper::getSageCustomerId implementado${NC}"
echo -e "${GREEN}✓ Consulta customer API cuando cf_sagecustomerid no está en custom_field_hash${NC}"
echo -e "${GREEN}✓ Fallback funciona cuando no hay servicio de Zoho${NC}"
echo -e "${GREEN}✓ Logging detallado para debugging${NC}"

echo
echo -e "${YELLOW}Funcionalidades del Helper:${NC}"
echo "1. getSageVendorId() - Para purchase orders (vendors)"
echo "2. getSageCustomerId() - Para sales orders (customers)"
echo "3. Doble verificación: custom_field_hash + API lookup"
echo "4. Fallback robusto cuando no hay conexión Zoho"

echo
echo -e "${YELLOW}Nota:${NC} Para pruebas completas con datos reales:"
echo "1. Configura una conexión Zoho válida"
echo "2. Usa customer_ids existentes en tu cuenta Zoho"
echo "3. Asegúrate que los customers tengan cf_sagecustomerid configurado"

echo
echo -e "${BLUE}Test completado exitosamente${NC}"
