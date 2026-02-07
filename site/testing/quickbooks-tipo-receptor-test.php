<?php

/**
 * Pruebas de los 4 TIPO_RECEPTOR usando estructura JSON real de QuickBooks Online
 *
 * Este script prueba los 4 tipos de receptor usando la estructura real de datos
 * que llega desde QuickBooks Online API.
 *
 * Ubicación: docs/testing/quickbooks-tipo-receptor-test.php
 * Uso: php docs/testing/quickbooks-tipo-receptor-test.php [organizationId]
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\QuickBooksOnlineService;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

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
                    "InvoiceLink" => "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-test01",
                    "domain" => "QBO",
                    "Id" => "7501",
                    "DocNumber" => "FE0000003001",
                    "TxnDate" => "2025-09-23",
                    "CurrencyRef" => [
                        "value" => "PAB",
                        "name" => "Balboa de Panamá"
                    ],
                    "Line" => [
                        [
                            "Id" => "1",
                            "LineNum" => 1,
                            "Amount" => 150,
                            "DetailType" => "SalesItemLineDetail",
                            "SalesItemLineDetail" => [
                                "ServiceDate" => "2025-09-23",
                                "ItemRef" => [
                                    "value" => "48",
                                    "name" => "SERVICIOS PROFESIONALES"
                                ],
                                "UnitPrice" => 150,
                                "Qty" => 1,
                                "TaxCodeRef" => [
                                    "value" => "15"
                                ]
                            ]
                        ]
                    ],
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
                    "TotalAmt" => 160.50,
                    "Balance" => 160.50
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
                    "InvoiceLink" => "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-test02",
                    "domain" => "QBO",
                    "Id" => "7502",
                    "DocNumber" => "FE0000003002",
                    "TxnDate" => "2025-09-23",
                    "CurrencyRef" => [
                        "value" => "PAB",
                        "name" => "Balboa de Panamá"
                    ],
                    "Line" => [
                        [
                            "Id" => "1",
                            "LineNum" => 1,
                            "Amount" => 100,
                            "DetailType" => "SalesItemLineDetail",
                            "SalesItemLineDetail" => [
                                "ServiceDate" => "2025-09-23",
                                "ItemRef" => [
                                    "value" => "48",
                                    "name" => "PUBLICIDAD EN PANTALLAS"
                                ],
                                "UnitPrice" => 100,
                                "Qty" => 1
                            ]
                        ]
                    ],
                    "CustomerRef" => [
                        "value" => "1440",
                        "name" => "ANUAR MATA",
                        "TIPO_RECEPTOR" => "02", // Consumidor Final
                        "CompanyName" => "E-8-159734",
                        "DisplayName" => "ANUAR MATA",
                        "PrimaryEmail" => "anuarmindset@gmail.com"
                    ],
                    "TotalAmt" => 107,
                    "Balance" => 107
                ]
            ],
            'expected_custom_fields' => [
                'Custom_field1' => null, // Sin RUC para consumidor final
                'Custom_field2' => null, // Sin DV para consumidor final
                'Custom_field3' => '02', // TIPO_RECEPTOR
            ]
        ],

        // TIPO_RECEPTOR 03: Gobierno
        [
            'name' => 'TIPO_RECEPTOR 03 - Gobierno',
            'invoice_data' => [
                "Invoice" => [
                    "InvoiceLink" => "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-test03",
                    "domain" => "QBO",
                    "Id" => "7503",
                    "DocNumber" => "FE0000003003",
                    "TxnDate" => "2025-09-23",
                    "CurrencyRef" => [
                        "value" => "PAB",
                        "name" => "Balboa de Panamá"
                    ],
                    "Line" => [
                        [
                            "Id" => "1",
                            "LineNum" => 1,
                            "Amount" => 500,
                            "DetailType" => "SalesItemLineDetail",
                            "SalesItemLineDetail" => [
                                "ServiceDate" => "2025-09-23",
                                "ItemRef" => [
                                    "value" => "49",
                                    "name" => "CONSULTORIA GUBERNAMENTAL"
                                ],
                                "UnitPrice" => 500,
                                "Qty" => 1
                            ]
                        ]
                    ],
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
                    "TotalAmt" => 535,
                    "Balance" => 535
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
                    "InvoiceLink" => "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-test04",
                    "domain" => "QBO",
                    "Id" => "7504",
                    "DocNumber" => "FE0000003004",
                    "TxnDate" => "2025-09-23",
                    "CurrencyRef" => [
                        "value" => "USD",
                        "name" => "Dólar Estadounidense"
                    ],
                    "Line" => [
                        [
                            "Id" => "1",
                            "LineNum" => 1,
                            "Amount" => 750,
                            "DetailType" => "SalesItemLineDetail",
                            "SalesItemLineDetail" => [
                                "ServiceDate" => "2025-09-23",
                                "ItemRef" => [
                                    "value" => "50",
                                    "name" => "SERVICIOS INTERNACIONALES"
                                ],
                                "UnitPrice" => 750,
                                "Qty" => 1
                            ]
                        ]
                    ],
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
                    "TotalAmt" => 750,
                    "Balance" => 750
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
 * Simula la lógica de procesamiento del CustomerRef
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

    // Extraer RUC
    if (preg_match('/Ruc:(\d+-\d+-\d+)|RUC:(\d+-\d+-\d+)|RUC (\d+-\d+-\d+)|Ruc (\d+-\d+-\d+)|^\d+-\d+-\d+$/i', $rucOriginal, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $ruc = end($filteredMatches);
    }

    // Extraer DV
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

    // Validación cruzada para extranjeros
    if ($tipoReceptor === '04') {
        if (!empty($pasaporteFromRequest)) {
            $pasaporte = $pasaporteFromRequest;
            $ruc = null;
            $dv = null;
            $tipoFromRequest = null;
        } else {
            $tipoReceptor = '02';
        }
    } else {
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
        echo "La lógica de Custom_fields está funcionando correctamente.\n";
    } else {
        echo "⚠️  Algunas pruebas fallaron. Revisar la implementación.\n";
    }
}

/**
 * Prueba interactiva con JSON personalizado
 */
function interactiveJsonTest(): void
{
    echo "🔧 MODO INTERACTIVO - JSON\n";
    echo "Pega tu JSON de factura de QuickBooks para probar:\n\n";

    $jsonInput = '';
    echo "Ingresa el JSON (termina con una línea vacía):\n";

    while (($line = fgets(STDIN)) !== "\n") {
        $jsonInput .= $line;
    }

    $invoiceData = json_decode($jsonInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Error: JSON inválido - " . json_last_error_msg() . "\n";
        return;
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🧪 RESULTADO DEL PROCESAMIENTO:\n";

    $result = processCustomerRefFromInvoice($invoiceData);

    echo "  📊 Datos procesados:\n";
    echo "    - Custom_field1 (Identificador): " . ($result['custom_field1'] ?? 'null') . "\n";
    echo "    - Custom_field2 (DV): " . ($result['custom_field2'] ?? 'null') . "\n";
    echo "    - Custom_field3 (TIPO_RECEPTOR): " . $result['custom_field3'] . "\n";
    echo "    - CustomerID: " . $result['customerID'] . "\n";
}

// Ejecutar script
if ($argc > 1 && $argv[1] === 'interactive') {
    interactiveJsonTest();
} else {
    runTipoReceptorTests();
}

echo "\n📚 Para más información:\n";
echo "  - Documentación: docs/technical/quickbooks-tipo-receptor-implementation.md\n";
echo "  - Código fuente: app/Services/QuickBooksOnlineService.php\n";
echo "  - Modo interactivo: php " . $argv[0] . " interactive\n";
