<?php
/**
 * Prueba del CreateSaleQuickBooksRequest para verificar que no se pierdan datos
 */

require_once 'vendor/autoload.php';

use App\Http\Requests\CreateSaleQuickBooksRequest;

echo "🧪 PRUEBA CREATE SALE QUICKBOOKS REQUEST\n";
echo "═══════════════════════════════════════════\n\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Datos de prueba - el objeto completo de QuickBooks
    $quickbooksData = [
        "Invoice" => [
            "InvoiceLink" => "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-2a0456038fa14095a460ba813f1bb0c47f9ce1ccdffd439d91dfd3bce87f9683df99a36057744f199e7b00743279b648?locale=es_PA&cta=v3invoicelink",
            "domain" => "QBO",
            "Id" => "7567",
            "MetaData" => [
                "CreateTime" => "2025-09-17T14:25:01-07:00",
                "LastModifiedByRef" => [
                    "value" => "9341454172707699"
                ],
                "LastUpdatedTime" => "2025-09-17T14:25:01-07:00"
            ],
            "DocNumber" => "FE0000003143",
            "TxnDate" => "2025-09-17",
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
                        "ServiceDate" => "2025-09-17",
                        "ItemRef" => [
                            "value" => "48",
                            "name" => "PUBLICIDAD EN PANTALLAS"
                        ],
                        "UnitPrice" => 100,
                        "Qty" => 1,
                        "ItemAccountRef" => [
                            "value" => "69",
                            "name" => "Servicios"
                        ],
                        "TaxCodeRef" => [
                            "value" => "15"
                        ],
                        "TaxCode" => [
                            "id" => "15",
                            "name" => "ITBMS7",
                            "description" => "ITBMS7 %",
                            "rateValue" => 7
                        ]
                    ]
                ],
                [
                    "Amount" => 100,
                    "DetailType" => "SubTotalLineDetail",
                    "LineNum" => 2
                ]
            ],
            "TxnTaxDetail" => [
                "TotalTax" => 7,
                "TaxLine" => [
                    [
                        "Amount" => 7,
                        "DetailType" => "TaxLineDetail",
                        "TaxLineDetail" => [
                            "TaxRateRef" => [
                                "value" => "22"
                            ],
                            "PercentBased" => true,
                            "TaxPercent" => 7,
                            "NetAmountTaxable" => 100
                        ]
                    ]
                ]
            ],
            "CustomerRef" => [
                "value" => "1440",
                "name" => "ANUAR MATA",
                "TIPO_RECEPTOR" => "02",
                "CompanyName" => "E-8-159734",
                "DisplayName" => "ANUAR MATA",
                "PrimaryEmail" => "anuarmindset@gmail.com"
            ],
            "BillAddr" => [
                "Id" => "3638"
            ],
            "ShipAddr" => [
                "Id" => "3638"
            ],
            "FreeFormAddress" => true,
            "SalesTermRef" => [
                "value" => "3"
            ],
            "DueDate" => "2025-10-17",
            "GlobalTaxCalculation" => "TaxExcluded",
            "TotalAmt" => 107,
            "PrintStatus" => "NotSet",
            "EmailStatus" => "NotSet",
            "BillEmail" => [
                "Address" => "anuarmindset@gmail.com"
            ],
            "Balance" => 107
        ]
    ];

    echo "📋 DATOS ORIGINALES DE QUICKBOOKS:\n";
    echo "─────────────────────────────────────\n";
    echo "• ID: " . $quickbooksData['Invoice']['Id'] . "\n";
    echo "• DocNumber: " . $quickbooksData['Invoice']['DocNumber'] . "\n";
    echo "• TotalAmt: $" . number_format($quickbooksData['Invoice']['TotalAmt'], 2) . "\n";
    echo "• Balance: $" . number_format($quickbooksData['Invoice']['Balance'], 2) . "\n";
    echo "• TotalTax: $" . number_format($quickbooksData['Invoice']['TxnTaxDetail']['TotalTax'], 2) . "\n";
    echo "• CustomerRef.name: " . $quickbooksData['Invoice']['CustomerRef']['name'] . "\n";
    echo "• Line items: " . count($quickbooksData['Invoice']['Line']) . "\n";
    echo "• TaxLine items: " . count($quickbooksData['Invoice']['TxnTaxDetail']['TaxLine']) . "\n\n";

    // Simular request HTTP
    $request = \Illuminate\Http\Request::create('/test', 'POST', $quickbooksData);

    // Crear instancia del Form Request
    $formRequest = new CreateSaleQuickBooksRequest();
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));

    // Reemplazar los datos del request
    $formRequest->replace($quickbooksData);

    // Ejecutar prepareForValidation manualmente
    $reflection = new \ReflectionClass($formRequest);
    $method = $reflection->getMethod('prepareForValidation');
    $method->setAccessible(true);
    $method->invoke($formRequest);

    echo "📤 DATOS DESPUÉS DEL PROCESAMIENTO:\n";
    echo "──────────────────────────────────────\n";

    $processedData = $formRequest->all();

    // Verificar campos principales
    $invoice = $processedData['Invoice'] ?? [];

    echo "• ID: " . ($invoice['Id'] ?? 'PERDIDO ❌') . "\n";
    echo "• DocNumber: " . ($invoice['DocNumber'] ?? 'PERDIDO ❌') . "\n";
    echo "• TotalAmt: " . ($invoice['TotalAmt'] ?? 'PERDIDO ❌') . "\n";
    echo "• Balance: " . ($invoice['Balance'] ?? 'PERDIDO ❌') . "\n";
    echo "• TotalTax: " . ($invoice['TxnTaxDetail']['TotalTax'] ?? 'PERDIDO ❌') . "\n";
    echo "• CustomerRef.name: " . ($invoice['CustomerRef']['name'] ?? 'PERDIDO ❌') . "\n";
    echo "• Line items: " . (isset($invoice['Line']) ? count($invoice['Line']) : 'PERDIDO ❌') . "\n";
    echo "• TaxLine items: " . (isset($invoice['TxnTaxDetail']['TaxLine']) ? count($invoice['TxnTaxDetail']['TaxLine']) : 'PERDIDO ❌') . "\n\n";

    // Mostrar estructura completa procesada
    echo "🔍 ESTRUCTURA COMPLETA PROCESADA:\n";
    echo "─────────────────────────────────────\n";
    echo json_encode($processedData, JSON_PRETTY_PRINT) . "\n\n";

    // Validación de integridad
    echo "✅ VALIDACIÓN DE INTEGRIDAD:\n";
    echo "──────────────────────────────\n";

    $originalCount = count($quickbooksData['Invoice'], COUNT_RECURSIVE);
    $processedCount = count($processedData['Invoice'] ?? [], COUNT_RECURSIVE);

    echo "• Campos originales: {$originalCount}\n";
    echo "• Campos procesados: {$processedCount}\n";
    echo "• Pérdida de datos: " . ($originalCount - $processedCount) . " campos\n";

    if ($processedCount >= $originalCount * 0.8) {
        echo "• Estado: ✅ DATOS PRESERVADOS CORRECTAMENTE\n";
    } else {
        echo "• Estado: ❌ PÉRDIDA SIGNIFICATIVA DE DATOS\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Prueba completada\n";
