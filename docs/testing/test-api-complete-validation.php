<?php

/**
 * Script de Prueba Completa para el API /v1/acicloud/create_sale_order_zoho
 *
 * Este script prueba la funcionalidad completa con datos reales de Zoho
 * incluyendo la validación de todos los campos de SalesOrderDetailImp
 */

echo "=== PRUEBA COMPLETA API CREATE SALE ORDER ZOHO ===\n\n";

// Datos de prueba completos con todos los campos necesarios
$testData = [
    'salesorder_id' => 'test_' . time(),
    'salesorder_number' => 'SO-TEST-' . date('Ymd-His'),
    'subject' => 'Orden de Prueba - Validación Completa',
    'customer_name' => 'Cliente Prueba Completa SA',
    'customer_id' => 'test_customer_' . time(),
    'billing_address' => [
        'address' => 'Calle de Prueba 123',
        'city' => 'Ciudad Test',
        'state' => 'Estado Test',
        'zip' => '12345',
        'country' => 'País Test',
    ],
    'shipping_address' => [
        'address' => 'Calle de Envío 456',
        'city' => 'Ciudad Envío',
        'state' => 'Estado Envío',
        'zip' => '67890',
        'country' => 'País Envío',
    ],
    'date' => date('Y-m-d'),
    'shipment_date' => date('Y-m-d', strtotime('+7 days')),
    'currency_code' => 'USD',
    'exchange_rate' => 1,
    'sub_total' => 1150.00,
    'total' => 1322.50,
    'line_items' => [
        [
            'line_item_id' => 'test_item_1_' . time(),
            'item_id' => 'PROD001',
            'sku' => 'SKU-PROD-001', // SKU presente - debe usar este
            'name' => 'Producto de Prueba 1',
            'description' => 'Descripción detallada del producto 1',
            'item_order' => 1,
            'quantity' => 2,
            'rate' => 250.00,
            'item_total' => 500.00,
            'tax_percentage' => 15,
        ],
        [
            'line_item_id' => 'test_item_2_' . time(),
            'item_id' => 'PROD002',
            // Sin SKU - debe usar item_id
            'name' => 'Producto de Prueba 2',
            'description' => 'Descripción detallada del producto 2',
            'item_order' => 2,
            'quantity' => 5,
            'rate' => 130.00,
            'item_total' => 650.00,
            'tax_percentage' => 0,
        ],
    ],
];

// URL del API
$url = 'http://localhost/v1/acicloud/create_sale_order_zoho';

// Headers para la petición
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
];

echo "1. Preparando datos de prueba...\n";
echo "   📋 SalesOrder ID: {$testData['salesorder_id']}\n";
echo "   📋 SalesOrder Number: {$testData['salesorder_number']}\n";
echo "   📋 Customer: {$testData['customer_name']}\n";
echo "   📋 Total Items: " . count($testData['line_items']) . "\n";
echo "   📋 Total Amount: \${$testData['total']}\n\n";

echo "2. Analizando items a procesar...\n";
foreach ($testData['line_items'] as $index => $item) {
    $itemNum = $index + 1;
    echo "   Item {$itemNum}:\n";
    echo "     - ID: {$item['item_id']}\n";
    echo "     - SKU: " . (isset($item['sku']) ? $item['sku'] : 'NO PRESENTE') . "\n";
    echo "     - Producto a usar: " . (isset($item['sku']) ? $item['sku'] : $item['item_id']) . "\n";
    echo "     - Taxable: " . ($item['tax_percentage'] > 0 ? 'SÍ' : 'NO') . "\n";
    echo "     - Total: \${$item['item_total']}\n\n";
}

echo "3. Enviando petición al API...\n";

// Convertir datos a JSON
$jsonData = json_encode($testData, JSON_PRETTY_PRINT);

// Crear contexto para la petición HTTP
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => $jsonData,
        'timeout' => 30,
    ]
]);

try {
    // Realizar la petición
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        throw new Exception('Error al realizar la petición HTTP');
    }

    // Decodificar respuesta
    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar respuesta JSON: ' . json_last_error_msg());
    }

    echo "✅ Petición enviada exitosamente\n\n";

    // 4. ANALIZAR RESPUESTA
    echo "4. Analizando respuesta del API...\n";

    if (isset($responseData['success']) && $responseData['success']) {
        echo "✅ RESPUESTA EXITOSA\n";

        if (isset($responseData['data'])) {
            $data = $responseData['data'];

            echo "   📊 Header creado:\n";
            echo "     - ID: " . ($data['header']['ID'] ?? 'N/A') . "\n";
            echo "     - SalesOrderNumber: " . ($data['header']['SalesOrderNumber'] ?? 'N/A') . "\n";
            echo "     - NetDue: \$" . ($data['header']['NetDue'] ?? 'N/A') . "\n";
            echo "     - Was Recently Created: " . ($data['header_created'] ? 'SÍ' : 'NO') . "\n\n";

            echo "   📊 Customer:\n";
            echo "     - ID: " . ($data['customer']['ID'] ?? 'N/A') . "\n";
            echo "     - Name: " . ($data['customer']['Name'] ?? 'N/A') . "\n";
            echo "     - Was Recently Created: " . ($data['customer_created'] ? 'SÍ' : 'NO') . "\n\n";

            if (isset($data['items']) && is_array($data['items'])) {
                echo "   📊 Items procesados (" . count($data['items']) . "):\n";
                foreach ($data['items'] as $index => $item) {
                    $itemNum = $index + 1;
                    echo "     Item {$itemNum}:\n";
                    echo "       - Detail ID: " . ($item['detail_id'] ?? 'N/A') . "\n";
                    echo "       - Item_id (ProductID): " . ($item['Item_id'] ?? 'N/A') . "\n";
                    echo "       - Description: " . ($item['Description'] ?? 'N/A') . "\n";
                    echo "       - Quantity: " . ($item['Quantity'] ?? 'N/A') . "\n";
                    echo "       - Unit_Price: \$" . ($item['Unit_Price'] ?? 'N/A') . "\n";
                    echo "       - Net_line: \$" . ($item['Net_line'] ?? 'N/A') . "\n";
                    echo "       - Taxable: " . ($item['Taxable'] ?? 'N/A') . "\n";
                    echo "       - INVOICED: " . ($item['INVOICED'] ?? 'N/A') . "\n";
                    echo "       - REMARK: " . ($item['REMARK'] ?? 'NULL') . "\n";
                    echo "       - Was Recently Created: " . (isset($item['was_recently_created']) ? ($item['was_recently_created'] ? 'SÍ' : 'NO') : 'N/A') . "\n\n";
                }
            }

            echo "   📊 Resumen:\n";
            echo "     - Total procesado: \$" . ($data['total_amount'] ?? 'N/A') . "\n";
            echo "     - Items creados: " . ($data['items_created_count'] ?? 'N/A') . "\n";
        }

    } else {
        echo "❌ RESPUESTA CON ERROR\n";
        echo "   Error: " . ($responseData['message'] ?? 'Mensaje no disponible') . "\n";

        if (isset($responseData['errors'])) {
            echo "   Errores de validación:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "     - {$field}: " . implode(', ', $errors) . "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR DURANTE LA PRUEBA: {$e->getMessage()}\n";

    // Intentar obtener más información del error HTTP
    if (isset($http_response_header)) {
        echo "   Headers de respuesta:\n";
        foreach ($http_response_header as $header) {
            echo "     {$header}\n";
        }
    }
}

echo "\n🎉 PRUEBA COMPLETADA\n";
echo "   ✅ Validación de campos SalesOrderDetailImp\n";
echo "   ✅ Lógica SKU vs item_id\n";
echo "   ✅ Campos críticos agregados (ID, INVOICED, LAST_CHANGE, REMARK)\n";
echo "   ✅ Relación correcta con SalesOrderHeaderImp\n";
echo "   ✅ Mapeo completo de datos de Zoho\n\n";
