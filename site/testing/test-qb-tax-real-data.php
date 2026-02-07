<?php
/**
 * Prueba específica del cálculo de impuestos con datos reales de QuickBooks
 */

require_once 'vendor/autoload.php';

use App\Models\Organization;
use App\Models\Connection;
use App\Contracts\QuickBooksOnlineServiceContract;
use Illuminate\Support\Facades\DB;

$organizationId = 2;

echo "🧮 PRUEBA CÁLCULO DE IMPUESTOS - DATOS REALES QB\n";
echo "═══════════════════════════════════════════════════\n\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Configurar conexión de BD
    $organization = Organization::find($organizationId);
    if (!$organization) {
        throw new Exception("Organización no encontrada: {$organizationId}");
    }

    DB::connection()->useDatabase($organization->database);
    echo "✅ Conectado a BD: {$organization->database}\n";

    // Verificar conexión QB
    $connection = Connection::where('organization_id', $organizationId)
        ->where('application', 'acicloud')
        ->first();

    if (!$connection) {
        throw new Exception("Conexión QB no encontrada");
    }

    echo "✅ Conexión QB encontrada: ID {$connection->id}\n\n";

    // Datos reales de QuickBooks con el problema identificado
    $requestData = [
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
            "DocNumber" => "TEST-REAL-" . date('YmdHis'),
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

    echo "📋 DATOS DE QUICKBOOKS:\n";
    echo "───────────────────────────\n";
    echo "• DocNumber: " . $requestData['Invoice']['DocNumber'] . "\n";
    echo "• Cliente: " . $requestData['Invoice']['CustomerRef']['name'] . "\n";
    echo "• Total Factura: $" . number_format($requestData['Invoice']['TotalAmt'], 2) . "\n";
    echo "• Total Impuesto: $" . number_format($requestData['Invoice']['TxnTaxDetail']['TotalTax'], 2) . "\n";

    $line1 = $requestData['Invoice']['Line'][0];
    echo "• Línea 1 - Item: " . $line1['SalesItemLineDetail']['ItemRef']['name'] . "\n";
    echo "• Línea 1 - UnitPrice: $" . number_format($line1['SalesItemLineDetail']['UnitPrice'], 2) . "\n";
    echo "• Línea 1 - Qty: " . $line1['SalesItemLineDetail']['Qty'] . "\n";
    echo "• Línea 1 - TaxCode Rate: " . $line1['SalesItemLineDetail']['TaxCode']['rateValue'] . "%\n\n";

    echo "🔧 PROCESANDO CON QUICKBOOKS ONLINE SERVICE...\n";
    echo "──────────────────────────────────────────────────\n";

    // Instanciar el servicio
    /** @var \App\Services\QuickBooksOnlineService $quickbooksService */
    $quickbooksService = app()->make(QuickBooksOnlineServiceContract::class);
    $quickbooksService->setOrganization($organization);

    echo "✅ Servicio inicializado\n\n";

    // Ejecutar storeOrder
    /** @var \App\Models\SalesHeaderImp $sale */
    $sale = $quickbooksService->storeOrder($organization, $requestData);

    if ($sale) {
        echo "✅ ¡ORDEN PROCESADA EXITOSAMENTE!\n\n";

        echo "📊 RESULTADO DEL PROCESAMIENTO:\n";
        echo "─────────────────────────────────\n";
        echo "• ID Venta: {$sale->ID}\n";
        echo "• Número Factura: {$sale->InvoiceNumber}\n";
        echo "• Cliente: {$sale->CustomerName}\n";
        echo "• Subtotal: $" . number_format($sale->Subtotal, 2) . "\n";
        echo "• Impuesto Total: $" . number_format($sale->TotalTaxInvupos, 2) . "\n";
        echo "• Total: $" . number_format($sale->Net_due, 2) . "\n";
        echo "• Tax ID: {$sale->TaxID}\n\n";

        // Verificar detalles de líneas
        $saleDetails = $sale->salesDetails;
        if ($saleDetails && $saleDetails->count() > 0) {
            echo "📝 DETALLES DE LÍNEAS:\n";
            echo "─────────────────────────\n";

            foreach ($saleDetails as $detail) {
                echo "• Item: {$detail->Description}\n";
                echo "  - Cantidad: {$detail->Quantity}\n";
                echo "  - Precio Unit: $" . number_format($detail->Unit_Price, 2) . "\n";
                echo "  - Subtotal: $" . number_format($detail->Sub_Total, 2) . "\n";
                echo "  - Impuesto: $" . number_format($detail->Itbms, 2) . "\n";
                echo "  - Total con Impuesto: $" . number_format($detail->Net_line, 2) . "\n";
                echo "  - Gravable: " . ($detail->Taxable == 1 ? 'SÍ' : 'NO') . "\n";
                echo "\n";
            }
        }

        echo "🎯 VALIDACIÓN DE CÁLCULOS:\n";
        echo "─────────────────────────────\n";

        $expectedSubtotal = 100.00;
        $expectedTax = 7.00;
        $expectedTotal = 107.00;

        $actualSubtotal = $sale->Subtotal;
        $actualTax = $sale->TotalTaxInvupos;
        $actualTotal = $sale->Net_due;

        echo "• Subtotal: Esperado $" . number_format($expectedSubtotal, 2);
        echo " vs Actual $" . number_format($actualSubtotal, 2);
        echo ($actualSubtotal == $expectedSubtotal ? " ✅" : " ❌") . "\n";

        echo "• Impuesto: Esperado $" . number_format($expectedTax, 2);
        echo " vs Actual $" . number_format($actualTax, 2);
        echo ($actualTax == $expectedTax ? " ✅" : " ❌") . "\n";

        echo "• Total: Esperado $" . number_format($expectedTotal, 2);
        echo " vs Actual $" . number_format($actualTotal, 2);
        echo ($actualTotal == $expectedTotal ? " ✅" : " ❌") . "\n";

        // Verificar cálculo de impuesto por línea
        if ($saleDetails && $saleDetails->count() > 0) {
            echo "\n🔍 ANÁLISIS POR LÍNEA:\n";
            echo "────────────────────────\n";

            foreach ($saleDetails as $detail) {
                if ($detail->Taxable == 1) {
                    $expectedLineTax = $detail->Sub_Total * 0.07; // 7%
                    echo "• {$detail->Description}:\n";
                    echo "  - Subtotal Línea: $" . number_format($detail->Sub_Total, 2) . "\n";
                    echo "  - Impuesto Esperado (7%): $" . number_format($expectedLineTax, 2) . "\n";
                    echo "  - Impuesto Actual: $" . number_format($detail->Itbms, 2) . "\n";
                    echo "  - Estado: " . (abs($detail->Itbms - $expectedLineTax) < 0.01 ? "✅ CORRECTO" : "❌ INCORRECTO") . "\n";
                }
            }
        }

    } else {
        echo "❌ ERROR: No se pudo procesar la orden\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Prueba de cálculo de impuestos completada\n";
