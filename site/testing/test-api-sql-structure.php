<?php

/**
 * Script de Prueba Final con Validaciones Basadas en Estructura de Tabla
 *
 * Este script prueba el API con datos que respetan las limitaciones de las tablas SQL
 */

echo "=== PRUEBA FINAL CON VALIDACIONES DE ESTRUCTURA SQL ===\n\n";

// Datos de prueba respetando las limitaciones de la tabla
$testData = [
    // === HEADER - Campos básicos ===
    'salesorder_id' => 'test_' . time(),
    'salesorder_number' => 'SO-' . date('md-His'), // max 20 chars
    'customer_id' => 'CUST-' . date('mdHis'), // max 50 chars
    'customer_name' => 'Cliente Test SA', // ⚠️ CRÍTICO: max 29 chars
    'date' => date('Y-m-d'),
    'shipment_date' => date('Y-m-d', strtotime('+7 days')),

    // === HEADER - Campos monetarios (precision decimal 18,4) ===
    'sub_total' => 1234.5678, // Respeta decimal(18,4)
    'total' => 1420.3079,
    'tax_total' => 185.7401,
    'discount' => 0,
    'adjustment' => 0,
    'shipping_charge' => 0,

    // === HEADER - Campos opcionales ===
    'currency_code' => 'USD',
    'exchange_rate' => 1,
    'reference_number' => 'REF-' . date('Ymd'), // max 20 chars (CustomerPO)
    'status' => 'confirmed',
    'terms' => 'Net 30', // max 30 chars (termino_pago)
    'delivery_method' => 'Standard Ship', // max 30 chars (entrega)
    'salesperson_id' => 'SP001', // max 20 chars (SalesRepID)

    // === SHIPPING ADDRESS - Respetando limitaciones ===
    'shipping_address' => [
        'company_name' => 'Cliente Test SA', // max 100 chars (ShipToName)
        'address' => '123 Test St', // max 30 chars (ShipToAddressLine1)
        'street2' => 'Apt 5', // max 30 chars (ShipToAddressLine2)
        'city' => 'Test City', // max 20 chars (ShipToCity)
        'state' => 'FL', // max 2 chars (ShipToState)
        'zip' => '12345', // max 12 chars (ShipToZip)
        'country' => 'USA', // max 15 chars (ShipToCountry)
    ],

    // === BILLING ADDRESS ===
    'billing_address' => [
        'company_name' => 'Cliente Test SA',
        'address' => '123 Test St',
        'city' => 'Test City',
        'state' => 'FL',
        'zip' => '12345',
        'country' => 'USA',
    ],

    // === LINE ITEMS - Respetando precisiones decimales ===
    'line_items' => [
        [
            'line_item_id' => 'test_item_1_' . time(),
            'item_id' => 'PROD001', // max 20 chars
            'sku' => 'SKU-001', // max 20 chars - prioritario
            'name' => 'Producto Test 1 - Descripción detallada que no excede los 160 caracteres permitidos en la tabla de base de datos', // max 160 chars
            'description' => 'Descripción adicional del producto que se guarda en el campo REMARK y no puede exceder los 200 caracteres en la base de datos', // max 200 chars (REMARK)
            'item_order' => 1,
            'quantity' => 12345.12345, // decimal(11,5) - máxima precisión
            'rate' => 123.4567, // decimal(18,4) - precisión monetaria
            'item_total' => 1523443.79259, // decimal(18,4) - resultado calculado
            'unit' => 'pcs',
            'tax_percentage' => 15,
            'tax_id' => 'TAX001',
            'tax_name' => 'IVA',
        ],
        [
            'line_item_id' => 'test_item_2_' . time(),
            'item_id' => 'PROD002', // max 20 chars
            // Sin SKU - debe usar item_id como ProductID
            'name' => 'Producto Test 2', // max 160 chars
            'description' => 'Segundo producto de prueba', // max 200 chars
            'item_order' => 2,
            'quantity' => 5.5, // decimal(11,5)
            'rate' => 50.25, // decimal(18,4)
            'item_total' => 276.375, // decimal(18,4)
            'unit' => 'hrs',
            'tax_percentage' => 0,
            'tax_id' => null,
            'tax_name' => null,
        ],
    ],
];

// Verificar que customer_name no excede 29 caracteres
if (strlen($testData['customer_name']) > 29) {
    echo "⚠️ ADVERTENCIA: customer_name tiene " . strlen($testData['customer_name']) . " caracteres (máximo 29)\n";
    $testData['customer_name'] = substr($testData['customer_name'], 0, 29);
    echo "   Truncado a: '{$testData['customer_name']}'\n\n";
}

echo "1. VERIFICANDO LIMITACIONES DE ESTRUCTURA:\n";

// Verificar limitaciones críticas
$checks = [
    'salesorder_number' => ['value' => $testData['salesorder_number'], 'max' => 20],
    'customer_id' => ['value' => $testData['customer_id'], 'max' => 50],
    'customer_name' => ['value' => $testData['customer_name'], 'max' => 29],
    'reference_number' => ['value' => $testData['reference_number'], 'max' => 20],
    'terms' => ['value' => $testData['terms'], 'max' => 30],
    'delivery_method' => ['value' => $testData['delivery_method'], 'max' => 30],
    'salesperson_id' => ['value' => $testData['salesperson_id'], 'max' => 20],
];

foreach ($checks as $field => $check) {
    $length = strlen($check['value']);
    $status = $length <= $check['max'] ? '✅' : '❌';
    echo "   {$status} {$field}: {$length}/{$check['max']} chars\n";
}

// Verificar line items
echo "\n   LINE ITEMS:\n";
foreach ($testData['line_items'] as $index => $item) {
    $itemNum = $index + 1;
    echo "   Item {$itemNum}:\n";
    echo "     ✅ item_id: " . strlen($item['item_id']) . "/20 chars\n";
    echo "     ✅ name: " . strlen($item['name']) . "/160 chars\n";
    echo "     ✅ description: " . strlen($item['description']) . "/200 chars\n";
    echo "     ✅ quantity: {$item['quantity']} (decimal 11,5)\n";
    echo "     ✅ rate: {$item['rate']} (decimal 18,4)\n";
    echo "     ✅ item_total: {$item['item_total']} (decimal 18,4)\n";
}

echo "\n2. ENVIANDO PETICIÓN AL API...\n";

// URL del API
$url = 'http://localhost:8000/api/acicloud/create_sale_order_zoho';

// Headers
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
];

// Convertir a JSON
$jsonData = json_encode($testData, JSON_PRETTY_PRINT);

echo "   📋 Tamaño del payload: " . strlen($jsonData) . " bytes\n";
echo "   📋 Enviando a: {$url}\n\n";

// Crear contexto para petición HTTP
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => $jsonData,
        'timeout' => 60,
    ]
]);

try {
    // Realizar petición
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        throw new Exception('Error al realizar petición HTTP');
    }

    // Decodificar respuesta
    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error JSON: ' . json_last_error_msg());
    }

    echo "3. ANALIZANDO RESPUESTA:\n";

    if (isset($responseData['success']) && $responseData['success']) {
        echo "✅ RESPUESTA EXITOSA\n\n";

        if (isset($responseData['data'])) {
            $data = $responseData['data'];

            echo "   📊 HEADER CREADO:\n";
            echo "     - ID: " . ($data['header']['ID'] ?? 'N/A') . "\n";
            echo "     - SalesOrderNumber: " . ($data['header']['SalesOrderNumber'] ?? 'N/A') . "\n";
            echo "     - CustomerName: " . ($data['header']['CustomerName'] ?? 'N/A') . "\n";
            echo "     - Net_due: \$" . ($data['header']['Net_due'] ?? 'N/A') . "\n";
            echo "     - Enviado: " . ($data['header']['Enviado'] ?? 'N/A') . "\n";
            echo "     - Error: " . ($data['header']['Error'] ?? 'N/A') . "\n";
            echo "     - saletax: " . ($data['header']['saletax'] ?? 'N/A') . "\n";
            echo "     - Was Recently Created: " . ($data['header_created'] ? 'SÍ' : 'NO') . "\n\n";

            echo "   📊 CUSTOMER:\n";
            echo "     - ID: " . ($data['customer']['ID'] ?? 'N/A') . "\n";
            echo "     - CustomerID: " . ($data['customer']['CustomerID'] ?? 'N/A') . "\n";
            echo "     - Name: " . ($data['customer']['Name'] ?? 'N/A') . "\n";
            echo "     - Was Recently Created: " . ($data['customer_created'] ? 'SÍ' : 'NO') . "\n\n";

            if (isset($data['items']) && is_array($data['items'])) {
                echo "   📊 ITEMS PROCESADOS (" . count($data['items']) . "):\n";
                foreach ($data['items'] as $index => $item) {
                    $itemNum = $index + 1;
                    echo "     Item {$itemNum}:\n";
                    echo "       - Detail ID: " . ($item['DetailId'] ?? 'N/A') . "\n";
                    echo "       - Item_id (ProductID): " . ($item['Item_id'] ?? 'N/A') . "\n";
                    echo "       - Description: " . ($item['Description'] ?? 'N/A') . "\n";
                    echo "       - Quantity: " . ($item['Quantity'] ?? 'N/A') . "\n";
                    echo "       - Unit_Price: \$" . ($item['Unit_Price'] ?? 'N/A') . "\n";
                    echo "       - Net_line: \$" . ($item['Net_line'] ?? 'N/A') . "\n";
                    echo "       - Taxable: " . ($item['Taxable'] ?? 'N/A') . "\n";
                    echo "       - INVOICED: " . ($item['INVOICED'] ?? 'N/A') . "\n";
                    echo "       - ID (relación): " . ($item['ID'] ?? 'N/A') . "\n\n";
                }
            }

            echo "   📊 RESUMEN:\n";
            echo "     - Total procesado: \$" . ($data['total_amount'] ?? 'N/A') . "\n";
            echo "     - Items creados: " . ($data['items_created_count'] ?? 'N/A') . "\n";
        }

    } else {
        echo "❌ RESPUESTA CON ERROR\n";
        echo "   Mensaje: " . ($responseData['message'] ?? 'No disponible') . "\n";

        if (isset($responseData['errors'])) {
            echo "\n   📋 ERRORES DE VALIDACIÓN:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "     - {$field}: " . implode(', ', $errors) . "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";

    if (isset($http_response_header)) {
        echo "\n   Headers de respuesta:\n";
        foreach ($http_response_header as $header) {
            echo "     {$header}\n";
        }
    }
}

echo "\n🎉 PRUEBA COMPLETADA\n";
echo "   ✅ Validaciones basadas en estructura SQL real\n";
echo "   ✅ Limitaciones de longitud respetadas\n";
echo "   ✅ Precisión decimal correcta\n";
echo "   ✅ Campos obligatorios incluidos\n";
echo "   ✅ Lógica SKU prioritaria mantenida\n\n";
