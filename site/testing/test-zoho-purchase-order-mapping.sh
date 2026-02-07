#!/bin/bash

# Script de Prueba para Mapeo de Órdenes de Compra Zoho
# Ubicación: docs/testing/test-zoho-purchase-order-mapping.sh
# Uso: ./test-zoho-purchase-order-mapping.sh [modo]
# Modos: structure, transformation, import, complete

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
DOCKER_CONTAINER="docucenter-app-1"
LOG_FILE="/var/www/html/storage/logs/laravel.log"

echo -e "${BLUE}=== Script de Prueba: Mapeo Zoho Purchase Orders ===${NC}"
echo "Fecha: $(date)"
echo "Modo: ${1:-complete}"
echo

# Función para ejecutar comandos en Docker
run_in_docker() {
    docker exec -it $DOCKER_CONTAINER bash -c "$1"
}

# Función para mostrar logs recientes
show_recent_logs() {
    echo -e "${YELLOW}Logs recientes (últimas 20 líneas):${NC}"
    run_in_docker "tail -20 $LOG_FILE | grep -E '(Zoho|createPurchaseOrderZoho|PurchaseHeaderImp|PurchaseDetailImp)' || echo 'No hay logs de Zoho recientes'"
    echo
}

# Función para limpiar logs
clear_logs() {
    echo -e "${YELLOW}Limpiando logs...${NC}"
    run_in_docker "echo '' > $LOG_FILE"
    echo "Logs limpiados"
    echo
}

# Test 1: Verificar estructura de clases
test_structure() {
    echo -e "${BLUE}=== Test 1: Verificando Estructura de Clases ===${NC}"

    echo "1. Verificando ZohoPurchaseOrderTransformer..."
    if run_in_docker "php artisan tinker --execute=\"class_exists('App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderTransformer')\"" | grep -q "true"; then
        echo -e "${GREEN}✓ ZohoPurchaseOrderTransformer encontrado${NC}"
    else
        echo -e "${RED}✗ ZohoPurchaseOrderTransformer NO encontrado${NC}"
    fi

    echo "2. Verificando ZohoPurchaseOrderImporter..."
    if run_in_docker "php artisan tinker --execute=\"class_exists('App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderImporter')\"" | grep -q "true"; then
        echo -e "${GREEN}✓ ZohoPurchaseOrderImporter encontrado${NC}"
    else
        echo -e "${RED}✗ ZohoPurchaseOrderImporter NO encontrado${NC}"
    fi

    echo "3. Verificando modelos..."
    run_in_docker "php artisan tinker --execute=\"
        echo 'PurchaseHeaderImp: ' . (class_exists('App\\\\Models\\\\PurchaseHeaderImp') ? 'OK' : 'FAIL') . PHP_EOL;
        echo 'PurchaseDetailImp: ' . (class_exists('App\\\\Models\\\\PurchaseDetailImp') ? 'OK' : 'FAIL') . PHP_EOL;
    \""
    echo
}

# Test 2: Validar transformación de datos
test_transformation() {
    echo -e "${BLUE}=== Test 2: Validando Transformación de Datos ===${NC}"

    run_in_docker "php artisan tinker --execute=\"
        \$transformer = new App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderTransformer();

        // Datos de prueba basados en el log real
        \$zohoData = [
            'bill_number' => '88931',
            'vendor_id' => '6088114000000487410',
            'vendor_name' => 'Medtrition',
            'date' => '2025-10-02',
            'due_date' => '2025-10-02',
            'sub_total' => '60.0',
            'total' => '60.0',
            'line_items' => '[{\"sku\":\"V504413\",\"name\":\"DIENAT G Vainilla\",\"quantity\":20,\"rate\":3,\"item_total\":60,\"account_name\":\"Activo de inventario\"}]'
        ];

        echo '1. Validando datos de entrada...' . PHP_EOL;
        \$validation = \$transformer->validateZohoData(\$zohoData);
        echo 'Validación: ' . (\$validation['valid'] ? 'VÁLIDO' : 'INVÁLIDO') . PHP_EOL;
        if (!\$validation['valid']) {
            echo 'Errores: ' . implode(', ', \$validation['errors']) . PHP_EOL;
        }

        echo PHP_EOL . '2. Transformando header...' . PHP_EOL;
        \$headerData = \$transformer->transformHeader(\$zohoData);
        echo 'PurchaseNumber: ' . \$headerData['PurchaseNumber'] . PHP_EOL;
        echo 'VendorName: ' . \$headerData['VendorName'] . PHP_EOL;
        echo 'Net_due: ' . \$headerData['Net_due'] . PHP_EOL;

        echo PHP_EOL . '3. Transformando line items...' . PHP_EOL;
        \$lineItems = json_decode(\$zohoData['line_items'], true);
        \$detailsData = \$transformer->transformLineItems(\$lineItems, 12345);
        echo 'Items transformados: ' . count(\$detailsData) . PHP_EOL;
        if (!empty(\$detailsData)) {
            echo 'Primer item - SKU: ' . \$detailsData[0]['Item_id'] . PHP_EOL;
            echo 'Primer item - Cantidad: ' . \$detailsData[0]['Quantity'] . PHP_EOL;
            echo 'Primer item - Total: ' . \$detailsData[0]['Net_line'] . PHP_EOL;
        }
    \""
    echo
}

# Test 3: Simular importación completa (sin insertar en BD)
test_import_simulation() {
    echo -e "${BLUE}=== Test 3: Simulación de Importación ===${NC}"

    echo "Ejecutando simulación de importación..."
    run_in_docker "php artisan tinker --execute=\"
        // Datos de prueba
        \$zohoData = [
            'bill_id' => '6088114000000487426',
            'bill_number' => 'TEST_88931_' . time(),
            'vendor_id' => '6088114000000487410',
            'vendor_name' => 'Medtrition Test',
            'date' => '2025-10-02',
            'due_date' => '2025-10-02',
            'sub_total' => '60.0',
            'total' => '60.0',
            'currency_code' => 'USD',
            'status' => 'open',
            'line_items' => '[{\"line_item_id\":\"6088114000000487433\",\"item_id\":\"6088114000000463033\",\"sku\":\"V504413\",\"name\":\"DIENAT G Vainilla\",\"quantity\":20,\"rate\":3,\"item_total\":60,\"account_name\":\"Activo de inventario\"}]'
        ];

        echo '1. Creando instancias de servicios...' . PHP_EOL;
        \$transformer = new App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderTransformer();
        \$importer = new App\\\\Services\\\\Zoho\\\\ZohoPurchaseOrderImporter(\$transformer);

        echo '2. Validando estructura...' . PHP_EOL;
        \$validation = \$transformer->validateZohoData(\$zohoData);
        if (!\$validation['valid']) {
            echo 'ERROR: Datos inválidos - ' . implode(', ', \$validation['errors']) . PHP_EOL;
            exit(1);
        }
        echo 'Datos válidos para importación' . PHP_EOL;

        echo '3. Verificando duplicados...' . PHP_EOL;
        \$existing = App\\\\Models\\\\PurchaseHeaderImp::where('PurchaseNumber', \$zohoData['bill_number'])
            ->where('VendorID', \$zohoData['vendor_id'])
            ->first();
        echo 'Duplicado encontrado: ' . (\$existing ? 'SÍ' : 'NO') . PHP_EOL;

        echo '4. Transformación completa...' . PHP_EOL;
        \$headerData = \$transformer->transformHeader(\$zohoData);
        \$lineItems = json_decode(\$zohoData['line_items'], true);
        \$detailsData = \$transformer->transformLineItems(\$lineItems, 999999);

        echo 'Header transformado - campos: ' . count(\$headerData) . PHP_EOL;
        echo 'Details transformados - items: ' . count(\$detailsData) . PHP_EOL;

        echo PHP_EOL . 'SIMULACIÓN COMPLETADA EXITOSAMENTE' . PHP_EOL;
    \""
    echo
}

# Test 4: Prueba del endpoint real
test_endpoint() {
    echo -e "${BLUE}=== Test 4: Prueba del Endpoint Real ===${NC}"

    echo "Limpiando logs para prueba limpia..."
    clear_logs

    echo "Enviando datos de prueba al endpoint..."

    # Datos de prueba basados en el log real
    curl -X POST "http://localhost:8000/api/acicloud/create_purchase_order_zoho" \
         -H "Content-Type: application/x-www-form-urlencoded" \
         -H "Authorization: Bearer test-token" \
         -d "date=2025-10-02&bill_number=TEST_88931_$(date +%s)&vendor_id=6088114000000487410&vendor_name=Medtrition%20Test&total=60.0&sub_total=60.0&due_date=2025-10-02&status=open&line_items=%5B%7B%22sku%22%3A%22V504413%22%2C%22name%22%3A%22DIENAT%20G%20Vainilla%22%2C%22quantity%22%3A20%2C%22rate%22%3A3%2C%22item_total%22%3A60%2C%22account_name%22%3A%22Activo%20de%20inventario%22%7D%5D" \
         -w "\nHTTP Status: %{http_code}\n" || echo -e "${RED}Error en la petición${NC}"

    echo
    echo "Verificando logs generados..."
    sleep 2
    show_recent_logs
}

# Función principal
main() {
    MODE=${1:-complete}

    case $MODE in
        structure)
            test_structure
            ;;
        transformation)
            test_transformation
            ;;
        simulation)
            test_import_simulation
            ;;
        endpoint)
            test_endpoint
            ;;
        complete)
            echo -e "${YELLOW}Ejecutando suite completa de pruebas...${NC}"
            test_structure
            test_transformation
            test_import_simulation
            test_endpoint
            ;;
        *)
            echo -e "${RED}Modo inválido. Usa: structure, transformation, simulation, endpoint, complete${NC}"
            exit 1
            ;;
    esac

    echo -e "${GREEN}=== Pruebas completadas ===${NC}"
    echo "Para más detalles, revisa los logs con:"
    echo "docker exec -it $DOCKER_CONTAINER tail -f $LOG_FILE"
}

# Ejecutar función principal
main "$@"
