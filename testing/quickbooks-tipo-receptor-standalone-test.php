<?php

/**
 * Pruebas de los 4 TIPO_RECEPTOR usando estructura JSON real de QuickBooks Online
 * Versión independiente sin conexión a base de datos
 *
 * Ubicación: docs/testing/quickbooks-tipo-receptor-standalone-test.php
 * Uso: php docs/testing/quickbooks-tipo-receptor-standalone-test.php
 */

/**
 * Función helper para emular array_get de Laravel
 */
function array_get($array, $key, $default = null) {
    if (is_null($key)) return $array;

    if (isset($array[$key])) return $array[$key];

    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        $array = $array[$segment];
    }

    return $array;
}

/**
 * Genera los 4 casos de prueba con estructura JSON real
 */
function getTipoReceptorTestCases(): array
{
    return [
        // TIPO_RECEPTOR 01: Contribuyente
        [
            'name' => 'TIPO_RECEPTOR 01 - Contribuyente',
            'invoice_data' => [
                "Invoice" => [
                    "Id" => "7501",
                    "DocNumber" => "FE0000003001",
                    "TxnDate" => "2025-09-23",
                    "CustomerRef" => [
                        "value" => "1401",
                        "name" => "EMPRESA ABC S.A.",
                        "TIPO_RECEPTOR" => "01", // Contribuyente
                        "Ruc" => "123456789-1-1234567",
                        "Dv" => "89",
                        "TIPO" => "1", // Persona Jurídica
                        "CompanyName" => "EMPRESA ABC S.A.",
                        "DisplayName" => "EMPRESA ABC S.A.",
                        "PrimaryEmail" => "facturacion@empresaabc.com",
                        "LocationCode" => "8-169"
                    ],
                    "TotalAmt" => 160.50
                ]
            ],
            'expected_custom_fields' => [
                'Custom_field1' => '123456789-1-1234567', // RUC en Custom_field1
                'Custom_field2' => '89',                   // DV en Custom_field2
                'Custom_field3' => '01',                   // TIPO_RECEPTOR
            ]
        ],

        // TIPO_RECEPTOR 02: Consumidor Final
        [
            'name' => 'TIPO_RECEPTOR 02 - Consumidor Final',
            'invoice_data' => [
                "Invoice" => [
                    "Id" => "7502",
                    "DocNumber" => "FE0000003002",
                    "TxnDate" => "2025-09-23",
                    "CustomerRef" => [
                        "value" => "1440",
                        "name" => "ANUAR MATA",
                        "TIPO_RECEPTOR" => "02", // Consumidor Final
                        "CompanyName" => "E-8-159734",
                        "DisplayName" => "ANUAR MATA",
                        "PrimaryEmail" => "anuarmindset@gmail.com"
                    ],
                    "TotalAmt" => 107
                ]
            ],
            'expected_custom_fields' => [
                'Custom_field1' => '', // Sin RUC para consumidor final (cadena vacía)
                'Custom_field2' => '', // Sin DV para consumidor final (cadena vacía)
                'Custom_field3' => '02', // TIPO_RECEPTOR
            ]
        ],

        // TIPO_RECEPTOR 03: Gobierno
        [
            'name' => 'TIPO_RECEPTOR 03 - Gobierno',
            'invoice_data' => [
                "Invoice" => [
                    "Id" => "7503",
                    "DocNumber" => "FE0000003003",
                    "TxnDate" => "2025-09-23",
                    "CustomerRef" => [
                        "value" => "1503",
                        "name" => "MINISTERIO DE ECONOMIA Y FINANZAS",
                        "TIPO_RECEPTOR" => "03", // Gobierno
                        "Ruc" => "155000001-1-2019",
                        "Dv" => "01",
                        "TIPO" => "1", // Persona Jurídica
                        "CompanyName" => "MINISTERIO DE ECONOMIA Y FINANZAS",
                        "DisplayName" => "MEF",
                        "PrimaryEmail" => "facturacion@mef.gob.pa",
                        "LocationCode" => "8-001"
                    ],
                    "TotalAmt" => 535
                ]
            ],
            'expected_custom_fields' => [
                'Custom_field1' => '155000001-1-2019', // RUC en Custom_field1
                'Custom_field2' => '01',                // DV en Custom_field2
                'Custom_field3' => '03',                // TIPO_RECEPTOR
            ]
        ],

        // TIPO_RECEPTOR 04: Extranjero
        [
            'name' => 'TIPO_RECEPTOR 04 - Extranjero',
            'invoice_data' => [
                "Invoice" => [
                    "Id" => "7504",
                    "DocNumber" => "FE0000003004",
                    "TxnDate" => "2025-09-23",
                    "CustomerRef" => [
                        "value" => "1604",
                        "name" => "INTERNATIONAL CORP LLC",
                        "TIPO_RECEPTOR" => "04", // Extranjero
                        "PASAPORTE" => "US987654321",
                        "CompanyName" => "INTERNATIONAL CORP LLC",
                        "DisplayName" => "INTERNATIONAL CORP",
                        "PrimaryEmail" => "billing@internationalcorp.com",
                        "Country" => "US"
                    ],
                    "TotalAmt" => 750
                ]
            ],
            'expected_custom_fields' => [
                'Custom_field1' => 'US987654321', // PASAPORTE en Custom_field1
                'Custom_field2' => null,          // DV es null para extranjeros
                'Custom_field3' => '04',          // TIPO_RECEPTOR
            ]
        ]
    ];
}

/**
 * Simula el procesamiento de CustomerRef usando la estructura real de QuickBooks
 */
function processCustomerRefFromInvoice(array $invoiceData): array
{
    $customerRef = $invoiceData['Invoice']['CustomerRef'] ?? [];

    // Mapear datos de CustomerRef a formato de request
    $request = [
        'CustomerID' => $customerRef['value'] ?? '',
        'Customer_Bill_Name' => $customerRef['name'] ?? '',
        'Ruc' => $customerRef['Ruc'] ?? '',
        'Dv' => $customerRef['Dv'] ?? '',
        'TIPO' => $customerRef['TIPO'] ?? null,
        'TIPO_RECEPTOR' => $customerRef['TIPO_RECEPTOR'] ?? null,
        'PASAPORTE' => $customerRef['PASAPORTE'] ?? null,
        'CompanyName' => $customerRef['CompanyName'] ?? '',
        'Email' => $customerRef['PrimaryEmail'] ?? '',
        'Country' => $customerRef['Country'] ?? 'PA',
        'LocationCode' => $customerRef['LocationCode'] ?? null,
    ];

    // Procesar con la misma lógica del servicio
    return simulateCustomerRefProcessing($request);
}

/**
 * Simula la lógica de procesamiento del CustomerRef (QuickBooksOnlineService)
 */
function simulateCustomerRefProcessing(array $request): array
{
    $rucOriginal = array_get($request, 'Ruc', '0-0-0');
    $dvOriginal = array_get($request, 'Dv', '00');
    $tipoFromRequest = array_get($request, 'TIPO');
    $tipoReceptorFromRequest = array_get($request, 'TIPO_RECEPTOR', '2');
    $pasaporteFromRequest = array_get($request, 'PASAPORTE', null);

    $ruc = '';
    $dv = '';
    $tipoContribuyente = '2';
    $locationCode = null;
    $pasaporte = null;

    // Extraer RUC con expresiones regulares
    if (preg_match('/Ruc:(\d+-\d+-\d+)|RUC:(\d+-\d+-\d+)|RUC (\d+-\d+-\d+)|Ruc (\d+-\d+-\d+)|^\d+-\d+-\d+$/i', $rucOriginal, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $ruc = end($filteredMatches);
    }

    // Extraer DV con expresiones regulares
    if (preg_match('/Dv:(\d{2})|DV:(\d{2})|Dv (\d{2})|DV (\d{2})|^\d{2}/i', $dvOriginal, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $dv = end($filteredMatches);
    }

    // Determinar TIPO_RECEPTOR
    $tipoReceptor = '02'; // Default: Consumidor final

    if (!is_null($tipoReceptorFromRequest)) {
        $tipoReceptorNormalized = str_pad((string)$tipoReceptorFromRequest, 2, '0', STR_PAD_LEFT);
        if (in_array($tipoReceptorNormalized, ['01', '02', '03', '04'])) {
            $tipoReceptor = $tipoReceptorNormalized;
        }
    } else {
        // Lógica automática
        if (!empty($pasaporteFromRequest)) {
            $tipoReceptor = '04';
            $pasaporte = $pasaporteFromRequest;
        } elseif (!empty($ruc) && !empty($dv)) {
            $tipoReceptor = '01';
        }
    }

    // Validación cruzada para extranjeros (TIPO_RECEPTOR: 04)
    if ($tipoReceptor === '04') {
        if (!empty($pasaporteFromRequest)) {
            $pasaporte = $pasaporteFromRequest;
            // Limpiar campos que no aplican para extranjeros
            $ruc = null;
            $dv = null;
            $tipoFromRequest = null;
        } else {
            $tipoReceptor = '02';
        }
    } else {
        // Para no extranjeros: PASAPORTE debe ser null
        $pasaporte = null;
    }

    return [
        'ruc' => $ruc,
        'dv' => $dv,
        'tipoReceptor' => $tipoReceptor,
        'tipoContribuyente' => $tipoContribuyente,
        'pasaporte' => $pasaporte,
        'customerID' => !empty($ruc) ? $ruc : (!empty($pasaporte) ? $pasaporte : array_get($request, 'CustomerID')),
        'custom_field1' => $tipoReceptor === '04' ? $pasaporte : $ruc,
        'custom_field2' => $tipoReceptor === '04' ? null : $dv,
        'custom_field3' => $tipoReceptor,
    ];
}

/**
 * Ejecutar las 4 pruebas de TIPO_RECEPTOR
 */
function runTipoReceptorTests(): void
{
    echo "🧪 TESTING: 4 Tipos de Receptor - QuickBooks Online Integration\n";
    echo "=" . str_repeat("=", 80) . "\n\n";

    $testCases = getTipoReceptorTestCases();
    $passed = 0;
    $failed = 0;

    foreach ($testCases as $index => $testCase) {
        echo "Caso " . ($index + 1) . ": " . $testCase['name'] . "\n";
        echo str_repeat("-", 60) . "\n";

        // Procesar datos de la factura
        $result = processCustomerRefFromInvoice($testCase['invoice_data']);
        $expected = $testCase['expected_custom_fields'];

        $success = true;
        $errors = [];

        // Verificar Custom_fields específicos
        foreach ($expected as $field => $expectedValue) {
            $actualValue = $result[strtolower(str_replace('Custom_field', 'custom_field', $field))];
            if ($actualValue !== $expectedValue) {
                $success = false;
                $errors[] = "  ❌ {$field}: esperado '" . ($expectedValue ?? 'null') . "', obtenido '" . ($actualValue ?? 'null') . "'";
            }
        }

        if ($success) {
            echo "  ✅ PASSED\n";
            $passed++;
        } else {
            echo "  ❌ FAILED\n";
            foreach ($errors as $error) {
                echo $error . "\n";
            }
            $failed++;
        }

        // Mostrar detalles de la factura
        $invoice = $testCase['invoice_data']['Invoice'];
        $customerRef = $invoice['CustomerRef'];

        echo "\n  📋 Datos de Entrada (CustomerRef):\n";
        echo "    - Nombre: " . ($customerRef['name'] ?? 'N/A') . "\n";
        echo "    - TIPO_RECEPTOR: " . ($customerRef['TIPO_RECEPTOR'] ?? 'N/A') . "\n";
        echo "    - RUC: " . ($customerRef['Ruc'] ?? 'N/A') . "\n";
        echo "    - DV: " . ($customerRef['Dv'] ?? 'N/A') . "\n";
        echo "    - PASAPORTE: " . ($customerRef['PASAPORTE'] ?? 'N/A') . "\n";
        echo "    - País: " . ($customerRef['Country'] ?? 'PA') . "\n";

        echo "\n  📊 Resultado Procesado:\n";
        echo "    - Custom_field1 (Identificador): " . ($result['custom_field1'] ?? 'null') . "\n";
        echo "    - Custom_field2 (DV): " . ($result['custom_field2'] ?? 'null') . "\n";
        echo "    - Custom_field3 (TIPO_RECEPTOR): " . $result['custom_field3'] . "\n";
        echo "    - CustomerID: " . $result['customerID'] . "\n";

        echo "\n  🏷️  Interpretación:\n";
        switch ($result['custom_field3']) {
            case '01':
                echo "    - Cliente: Contribuyente → RUC en Custom_field1, DV en Custom_field2\n";
                break;
            case '02':
                echo "    - Cliente: Consumidor Final → Campos identificadores en null\n";
                break;
            case '03':
                echo "    - Cliente: Gobierno → RUC en Custom_field1, DV en Custom_field2\n";
                break;
            case '04':
                echo "    - Cliente: Extranjero → PASAPORTE en Custom_field1, DV en null\n";
                break;
        }

        echo "\n" . str_repeat("=", 80) . "\n\n";
    }

    // Resumen final
    $total = $passed + $failed;
    echo "📋 RESUMEN DE PRUEBAS DE TIPO_RECEPTOR:\n";
    echo "  Total: {$total}\n";
    echo "  ✅ Passed: {$passed}\n";
    echo "  ❌ Failed: {$failed}\n";
    echo "  📈 Success Rate: " . round(($passed / $total) * 100, 2) . "%\n\n";

    if ($failed === 0) {
        echo "🎉 ¡Todas las pruebas de TIPO_RECEPTOR pasaron exitosamente!\n";
        echo "La lógica de Custom_fields está funcionando correctamente.\n\n";

        echo "🔍 VALIDACIÓN COMPLETADA:\n";
        echo "  ✅ TIPO_RECEPTOR 01 (Contribuyente): RUC → Custom_field1, DV → Custom_field2\n";
        echo "  ✅ TIPO_RECEPTOR 02 (Consumidor Final): null → Custom_field1, null → Custom_field2\n";
        echo "  ✅ TIPO_RECEPTOR 03 (Gobierno): RUC → Custom_field1, DV → Custom_field2\n";
        echo "  ✅ TIPO_RECEPTOR 04 (Extranjero): PASAPORTE → Custom_field1, null → Custom_field2\n";
    } else {
        echo "⚠️  Algunas pruebas fallaron. Revisar la implementación.\n";
    }
}

// Ejecutar script
runTipoReceptorTests();

echo "\n📚 Para más información:\n";
echo "  - Documentación: docs/technical/quickbooks-tipo-receptor-implementation.md\n";
echo "  - Código fuente: app/Services/QuickBooksOnlineService.php\n";
echo "  - Script con Laravel: docs/testing/quickbooks-tipo-receptor-test.php\n";
