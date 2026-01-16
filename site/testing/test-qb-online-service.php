<?php
/**
 * Prueba del QuickBooksOnlineService para verificar almacenamiento correcto
 * Usando exactamente el mismo patrón que SendSaleToQuickBooksJob
 */

require_once 'vendor/autoload.php';

use App\Models\Organization;
use App\Models\Connection;
use App\Contracts\QuickBooksOnlineServiceContract;
use Illuminate\Support\Facades\DB;

$organizationId = 2;

echo "🔍 PRUEBA QUICKBOOKS ONLINE SERVICE - ALMACENAMIENTO\n";
echo "══════════════════════════════════════════════════════\n\n";

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

    // Preparar datos de prueba en el formato correcto para QuickBooksOnlineService
    $requestData = [
        'Invoice' => [
            'Id' => 'TEST-' . date('YmdHis'),
            'DocNumber' => 'TEST-' . date('YmdHis'),
            'TxnDate' => date('Y-m-d'),
            'DueDate' => date('Y-m-d', strtotime('+30 days')),
            'TotalAmt' => 107.00, // $100 + 7% = $107
            'Balance' => 107.00,
            'CustomerRef' => [
                'value' => '1440',
                'name' => 'Cliente de Prueba QB',
                'RUC' => '123456789-1-DV',
                'DV' => '1',
                'TIPO' => '1',
                'TIPO_RECEPTOR' => '02',
                'ReceiverType' => '2'
            ],
            'BillEmail' => [
                'Address' => 'cliente@example.com'
            ],
            'TxnTaxDetail' => [
                'TotalTax' => 7.00,
                'TaxLine' => [
                    [
                        'Amount' => 7.00,
                        'TaxLineDetail' => [
                            'TaxPercent' => 7.00,
                            'NetAmountTaxable' => 100.00,
                            'TaxRateRef' => ['value' => '15']
                        ]
                    ]
                ]
            ],
            'Line' => [
                [
                    'Id' => '1',
                    'Amount' => 100.00,
                    'Description' => 'Servicio de Publicidad',
                    'DetailType' => 'SalesItemLineDetail',
                    'SalesItemLineDetail' => [
                        'ItemRef' => ['value' => '48', 'name' => 'PUBLICIDAD EN PANTALLAS'],
                        'UnitPrice' => 100.00,
                        'Qty' => 1,
                        'TaxCodeRef' => ['value' => '15'],
                        'TaxAmount' => 7.00,
                        'TotalWithTax' => 107.00
                    ]
                ]
            ]
        ]
    ];

    echo "📋 DATOS DE PRUEBA PREPARADOS:\n";
    echo "─────────────────────────────────\n";
    echo "• Cliente ID: {$requestData['Invoice']['CustomerRef']['value']}\n";
    echo "• Cliente Nombre: {$requestData['Invoice']['CustomerRef']['name']}\n";
    echo "• DocNumber: {$requestData['Invoice']['DocNumber']}\n";
    echo "• Item: {$requestData['Invoice']['Line'][0]['SalesItemLineDetail']['ItemRef']['name']}\n";
    echo "• Precio Unit: $" . number_format($requestData['Invoice']['Line'][0]['SalesItemLineDetail']['UnitPrice'], 2) . "\n";
    echo "• Cantidad: {$requestData['Invoice']['Line'][0]['SalesItemLineDetail']['Qty']}\n";
    echo "• Impuesto Total: $" . number_format($requestData['Invoice']['TxnTaxDetail']['TotalTax'], 2) . "\n";
    echo "• Total Factura: $" . number_format($requestData['Invoice']['TotalAmt'], 2) . "\n\n";

    echo "🔍 DEBUG - ESTRUCTURA DE DATOS ENVIADOS:\n";
    echo "─────────────────────────────────────────\n";
    echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

    echo "🔧 INICIALIZANDO QUICKBOOKS ONLINE SERVICE...\n";
    echo "──────────────────────────────────────────────\n";

    // Instanciar el servicio exactamente como en SendSaleToQuickBooksJob
    /** @var \App\Services\QuickBooksOnlineService $quickbooksService */
    $quickbooksService = app()->make(QuickBooksOnlineServiceContract::class);
    $quickbooksService->setOrganization($organization);

    echo "✅ Servicio inicializado\n";
    echo "✅ Organización configurada: {$organization->name}\n\n";

    echo "💾 EJECUTANDO STORE ORDER...\n";
    echo "─────────────────────────────\n";

    // Ejecutar storeOrder exactamente como en el job
    /** @var \App\Models\SalesHeaderImp $sale */
    $sale = $quickbooksService->storeOrder($organization, $requestData);

    if ($sale) {
        echo "✅ ¡ORDEN ALMACENADA EXITOSAMENTE!\n\n";

        // Debug: Ver todos los atributos del modelo
        echo "🔍 DEBUG - ATRIBUTOS DEL MODELO:\n";
        echo "─────────────────────────────────────\n";
        $attributes = $sale->getAttributes();
        foreach ($attributes as $key => $value) {
            echo "• {$key}: " . ($value ?? 'NULL') . "\n";
        }
        echo "\n";

        echo "📊 DETALLES DE LA VENTA CREADA:\n";
        echo "───────────────────────────────────\n";
        echo "• ID Venta: {$sale->ID}\n";
        echo "• Número Factura: " . ($sale->InvoiceNumber ?? 'NULL') . "\n";
        echo "• Cliente ID: " . ($sale->CustomerID ?? 'NULL') . "\n";
        echo "• Cliente Nombre: " . ($sale->CustomerName ?? 'NULL') . "\n";
        echo "• Total (Net_due): $" . number_format($sale->Net_due ?? 0, 2) . "\n";
        echo "• Subtotal: $" . number_format($sale->Subtotal ?? 0, 2) . "\n";
        echo "• Impuesto (TotalTaxInvupos): $" . number_format($sale->TotalTaxInvupos ?? 0, 2) . "\n";
        echo "• Fecha: " . ($sale->Date ?? 'NULL') . "\n";
        echo "• Origen: " . ($sale->origin ?? 'NULL') . "\n";
        echo "• Tipo de Venta: " . ($sale->TypeOfSale ?? 'NULL') . "\n";
        echo "• Tax ID: " . ($sale->TaxID ?? 'NULL') . "\n";

        // Verificar detalles de la venta
        $saleDetails = $sale->salesDetails;
        if ($saleDetails && $saleDetails->count() > 0) {
            echo "\n📝 DETALLES DE LÍNEAS ({$saleDetails->count()}):\n";
            echo "─────────────────────────────────────────\n";

            foreach ($saleDetails as $detail) {
                echo "• Item: {$detail->product_name}\n";
                echo "  - Cantidad: {$detail->quantity}\n";
                echo "  - Precio Unit: $" . number_format($detail->unit_price, 2) . "\n";
                echo "  - Subtotal: $" . number_format($detail->line_total, 2) . "\n";
                echo "  - Impuesto: $" . number_format($detail->tax_amount ?? 0, 2) . "\n";
                echo "\n";
            }
        } else {
            echo "\n📝 DETALLES DE LÍNEAS: No se encontraron detalles\n";
        }

        // Verificar impuestos
        $taxDetails = $sale->taxDetails ?? null;
        if ($taxDetails && $taxDetails->count() > 0) {
            echo "💰 DETALLES DE IMPUESTOS ({$taxDetails->count()}):\n";
            echo "─────────────────────────────────────────────\n";

            foreach ($taxDetails as $tax) {
                echo "• Impuesto: {$tax->tax_name} ({$tax->tax_rate}%)\n";
                echo "  - Base Gravable: $" . number_format($tax->taxable_amount, 2) . "\n";
                echo "  - Monto Impuesto: $" . number_format($tax->tax_amount, 2) . "\n";
                echo "\n";
            }
        } else {
            echo "💰 DETALLES DE IMPUESTOS: No se encontraron impuestos\n";
        }

        echo "🎯 VALIDACIÓN DE CÁLCULOS:\n";
        echo "─────────────────────────────\n";

        $expectedSubtotal = 100.00;
        $expectedTax = 7.00;
        $expectedTotal = 107.00;

        $actualSubtotal = $sale->Subtotal ?? 0;
        $actualTax = $sale->TotalTaxInvupos ?? 0;
        $actualTotal = $sale->Net_due ?? 0;

        echo "• Subtotal Esperado: $" . number_format($expectedSubtotal, 2);
        echo " | Actual: $" . number_format($actualSubtotal, 2);
        echo ($actualSubtotal == $expectedSubtotal ? " ✅" : " ❌") . "\n";

        echo "• Impuesto Esperado: $" . number_format($expectedTax, 2);
        echo " | Actual: $" . number_format($actualTax, 2);
        echo ($actualTax == $expectedTax ? " ✅" : " ❌") . "\n";

        echo "• Total Esperado: $" . number_format($expectedTotal, 2);
        echo " | Actual: $" . number_format($actualTotal, 2);
        echo ($actualTotal == $expectedTotal ? " ✅" : " ❌") . "\n";

    } else {
        echo "❌ ERROR: No se pudo almacenar la orden\n";
        echo "El método storeOrder retornó: " . var_export($sale, true) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Prueba de almacenamiento completada\n";
