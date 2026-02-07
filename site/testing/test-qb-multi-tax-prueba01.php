<?php

/**
 * Script de prueba para verificar procesamiento de múltiples tasas de impuesto
 * Factura: PRUEBA01 con ITBMS 10% y 7%
 *
 * Ejecutar desde raíz del proyecto:
 * docker exec -it docucenter-app-1 php docs/testing/test-qb-multi-tax-prueba01.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Organization;
use App\Models\Connection;
use App\Services\QuickBooksOnlineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== TEST: QuickBooks Multi-Tax Rates - PRUEBA01 ===\n\n";

// Objeto de prueba
$testInvoice = [
    "Invoice" => [
        "domain" => "QBO",
        "Id" => "304",
        "MetaData" => [
            "CreateTime" => "2026-02-05T10:46:34-08:00",
            "LastModifiedByRef" => [
                "value" => "9341455798882440"
            ],
            "LastUpdatedTime" => "2026-02-05T10:46:34-08:00"
        ],
        "DocNumber" => "PRUEBA01",
        "TxnDate" => "2026-01-21",
        "CurrencyRef" => [
            "value" => "PAB",
            "name" => "Balboa de Panamá"
        ],
        "Line" => [
            [
                "Id" => "1",
                "LineNum" => 1,
                "Description" => "PRUEBA10%",
                "Amount" => 1,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => [
                        "value" => "1",
                        "name" => "Servicios"
                    ],
                    "UnitPrice" => 1,
                    "Qty" => 1,
                    "ItemAccountRef" => [
                        "value" => "3",
                        "name" => "Servicios"
                    ],
                    "TaxCodeRef" => [
                        "value" => "15"
                    ],
                    "TaxCode" => [
                        "id" => "15",
                        "name" => "ITBMS10",
                        "description" => "ITBMS10%",
                        "rateValue" => 10
                    ]
                ]
            ],
            [
                "Id" => "2",
                "LineNum" => 2,
                "Description" => "PRUEBA 7%",
                "Amount" => 1,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => [
                        "value" => "1",
                        "name" => "Servicios"
                    ],
                    "UnitPrice" => 1,
                    "Qty" => 1,
                    "ItemAccountRef" => [
                        "value" => "3",
                        "name" => "Servicios"
                    ],
                    "TaxCodeRef" => [
                        "value" => "13"
                    ],
                    "TaxCode" => [
                        "id" => "13",
                        "name" => "ITBMS7",
                        "description" => "ITBMS7%",
                        "rateValue" => 7
                    ]
                ]
            ],
            [
                "Amount" => 2,
                "DetailType" => "SubTotalLineDetail",
                "LineNum" => 3
            ]
        ],
        "TxnTaxDetail" => [
            "TotalTax" => 0.17,
            "TaxLine" => [
                [
                    "Amount" => 0.1,
                    "DetailType" => "TaxLineDetail",
                    "TaxLineDetail" => [
                        "TaxRateRef" => [
                            "value" => "20"
                        ],
                        "PercentBased" => true,
                        "TaxPercent" => 10,
                        "NetAmountTaxable" => 1
                    ]
                ],
                [
                    "Amount" => 0.07,
                    "DetailType" => "TaxLineDetail",
                    "TaxLineDetail" => [
                        "TaxRateRef" => [
                            "value" => "18"
                        ],
                        "PercentBased" => true,
                        "TaxPercent" => 7,
                        "NetAmountTaxable" => 1
                    ]
                ]
            ]
        ],
        "CustomerRef" => [
            "value" => "1188",
            "name" => "PRUEBA",
            "RUC" => null,
            "DV" => null,
            "TIPO_RECEPTOR" => "02",
            "PASAPORTE" => null,
            "CompanyName" => "PRUEBA APCON",
            "DisplayName" => "PRUEBA",
            "PrimaryEmail" => null,
            "BillAddr" => []
        ],
        "CustomerMemo" => [
            "value" => "Métodos de Pago:\nTransferencia bancaria:\nBanco: Banco General\nDivertimento Ecológico S.A.\nCta. Corriente\n03-02-01-059156-2"
        ],
        "BillAddr" => [
            "Id" => "488"
        ],
        "ShipAddr" => [
            "Id" => "489",
            "Line1" => "PRUEBA APCON"
        ],
        "FreeFormAddress" => true,
        "SalesTermRef" => [
            "value" => "1"
        ],
        "DueDate" => "2026-01-21",
        "GlobalTaxCalculation" => "TaxExcluded",
        "TotalAmt" => 2.17,
        "PrintStatus" => "NotSet",
        "EmailStatus" => "NotSet",
        "Balance" => 2.17
    ]
];

// Obtener organización de prueba (org 2)
$organization = Organization::find(2);
if (!$organization) {
    echo "ERROR: Organization 2 not found\n";
    exit(1);
}

echo "Organization: {$organization->nombre} (ID: {$organization->id})\n";
echo "Database: {$organization->database}\n\n";

// Buscar conexión acicloud
$connection = Connection::where('organization_id', $organization->id)
    ->where('application', 'acicloud')
    ->first();

if (!$connection) {
    echo "WARNING: No acicloud connection found for org {$organization->id}\n";
    echo "Creating test will proceed without ACI Cloud mapping\n\n";
}

echo "=== PROCESANDO FACTURA PRUEBA01 ===\n\n";

try {
    // Crear servicio
    $service = new QuickBooksOnlineService($connection);

    // Procesar factura
    $salesHeader = $service->storeOrder($organization, $testInvoice);

    echo "✓ Factura procesada exitosamente\n";
    echo "  Invoice Number: {$salesHeader->InvoiceNumber}\n";
    echo "  Subtotal: \${$salesHeader->Subtotal}\n";
    echo "  Total Tax: \${$salesHeader->TotalTaxInvupos}\n";
    echo "  Net Due: \${$salesHeader->Net_due}\n\n";

    // Verificar detalles
    DB::connection()->useDatabase($organization->database);
    $details = \App\Models\SalesDetailImp::where('InvoiceNumber', 'PRUEBA01')->get();

    echo "=== DETALLE DE LÍNEAS ===\n\n";

    $totalItbms = 0;
    foreach ($details as $detail) {
        echo "Línea {$detail->Sequential}:\n";
        echo "  Descripción: {$detail->Description}\n";
        echo "  Cantidad: {$detail->Quantity}\n";
        echo "  Precio Unit: \${$detail->Unit_Price}\n";
        echo "  Subtotal: \${$detail->Sub_Total}\n";
        echo "  ITBMS: \${$detail->Itbms}\n";
        echo "  Net Line: \${$detail->Net_line}\n";
        echo "  Taxable: " . ($detail->Taxable == 1 ? 'Sí' : 'No') . "\n";

        $totalItbms += $detail->Itbms;

        // Validar valores esperados
        if ($detail->Sequential == '1') {
            $expectedTax = 0.10;
            $isCorrect = abs($detail->Itbms - $expectedTax) < 0.001;
            echo "  ✓ Validación: " . ($isCorrect ? "CORRECTO" : "INCORRECTO") . "\n";
            echo "    Esperado: \$0.10 (10%)\n";
            echo "    Obtenido: \${$detail->Itbms}\n";
        } elseif ($detail->Sequential == '2') {
            $expectedTax = 0.07;
            $isCorrect = abs($detail->Itbms - $expectedTax) < 0.001;
            echo "  ✓ Validación: " . ($isCorrect ? "CORRECTO" : "INCORRECTO") . "\n";
            echo "    Esperado: \$0.07 (7%)\n";
            echo "    Obtenido: \${$detail->Itbms}\n";
        }

        echo "\n";
    }

    echo "=== RESUMEN ===\n\n";
    echo "Total ITBMS Calculado: \${$totalItbms}\n";
    echo "Total ITBMS Esperado: \$0.17\n";

    $isCorrectTotal = abs($totalItbms - 0.17) < 0.001;
    echo "Validación Total: " . ($isCorrectTotal ? "✓ CORRECTO" : "✗ INCORRECTO") . "\n\n";

    // Verificar logs
    echo "=== ÚLTIMOS LOGS (QuickBooks Line Tax Calculation) ===\n\n";
    echo "Revisar storage/logs/laravel.log para ver:\n";
    echo "- calculation_method (debe ser 'tax_code_rate_value')\n";
    echo "- tax_percent_applied (debe ser 10 y 7)\n";
    echo "- tax_code_rate (debe ser 10 y 7)\n\n";

    // Limpiar datos de prueba
    echo "Limpiando datos de prueba...\n";
    $salesHeader->delete();
    \App\Models\SalesDetailImp::where('InvoiceNumber', 'PRUEBA01')->delete();
    \App\Models\CustomersImp::where('CustomerID', 'PRUEBA')->delete();

    echo "✓ Datos de prueba eliminados\n\n";
    echo "=== PRUEBA COMPLETADA ===\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
