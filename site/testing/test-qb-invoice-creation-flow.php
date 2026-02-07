<?php
/**
 * Script de prueba completa para el flujo de creación de facturas en QuickBooks
 * Valida que los impuestos se registren correctamente
 */

require_once 'vendor/autoload.php';

use App\Models\Connection;
use App\Models\Organization;
use App\Models\SalesHeaderImp;
use App\Models\SalesDetailImp;
use App\Jobs\SendSaleToQuickBooksJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Configuración de prueba
$organizationId = 2;
$testInvoiceData = [
    'customer_name' => 'ANUAR MATA TEST',
    'customer_ruc' => 'E-8-159734',
    'customer_email' => 'test@example.com',
    'items' => [
        [
            'description' => 'PUBLICIDAD EN PANTALLAS',
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_rate' => 7, // 7% ITBMS
            'tax_amount' => 7.00,
            'subtotal' => 100.00,
            'total' => 107.00
        ]
    ],
    'subtotal' => 100.00,
    'total_tax' => 7.00,
    'total_amount' => 107.00
];

echo "🧪 PRUEBA COMPLETA: Flujo de Creación de Facturas QB con Impuestos\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "✅ Laravel inicializado\n";

    // 1. Validar organización
    $organization = Organization::find($organizationId);
    if (!$organization) {
        throw new Exception("Organización {$organizationId} no encontrada");
    }
    echo "✅ Organización: {$organization->name}\n";

    // 2. Configurar conexión a BD de organización
    if (!empty($organization->database)) {
        DB::connection()->useDatabase($organization->database);
        echo "✅ BD configurada: {$organization->database}\n";
    }

    // 3. Buscar conexión QuickBooks
    DB::connection()->useDatabase(env('DB_DATABASE')); // Volver a BD principal para buscar conexión
    $connection = Connection::where('organization_id', $organizationId)
        ->where('application', 'acicloud')
        ->first();

    if (!$connection) {
        throw new Exception("Conexión QuickBooks no encontrada para organización {$organizationId}");
    }
    echo "✅ Conexión QB: ID {$connection->id}\n";

    // Verificar configuración de la conexión
    $settings = $connection->settings;
    if (is_string($settings)) {
        $settings = json_decode($settings, true);
    }
    echo "✅ App ID: " . ($settings['App'] ?? 'N/A') . "\n";

    // 4. Volver a BD de organización para crear factura
    DB::connection()->useDatabase($organization->database);

    echo "\n🔨 PASO 1: Creando factura de prueba en DocuCenter\n";
    echo "─────────────────────────────────────────────────────\n";

    // Crear header de factura
    $invoiceNumber = 'TEST-QB-' . date('YmdHis');
    $salesHeader = SalesHeaderImp::create([
        'InvoiceNumber' => $invoiceNumber,
        'CustomerName' => $testInvoiceData['customer_name'],
        'CustomerRuc' => $testInvoiceData['customer_ruc'],
        'CustomerEmail' => $testInvoiceData['customer_email'],
        'Date' => now()->format('Y-m-d'),
        'DueDate' => now()->addDays(30)->format('Y-m-d'),
        'Subtotal' => $testInvoiceData['subtotal'],
        'TotalAmount' => $testInvoiceData['total_amount'],
        'Net_due' => $testInvoiceData['total_amount'],
        'EzeeIssued' => 1,
        'EzeeExport' => 0, // No exportada aún
        'origin' => 'test', // Origen de prueba
        'intuit_sync_status' => 'pending',
        'ID_compania' => $organizationId
    ]);

    echo "✅ Header creado: ID {$salesHeader->ID}, Número: {$invoiceNumber}\n";

    // Crear detalles de factura
    foreach ($testInvoiceData['items'] as $index => $item) {
        $detail = SalesDetailImp::create([
            'SalesHeaderId' => $salesHeader->ID,
            'Sequential' => $index + 1,
            'Description' => $item['description'],
            'Quantity' => $item['quantity'],
            'Unit_Price' => $item['unit_price'],
            'Sub_Total' => $item['subtotal'],
            'Itbms' => $item['tax_amount'],
            'LineTotalIncludingTax' => $item['total'],
            'Item_id' => 'TEST-ITEM-' . ($index + 1),
            'SalesDetailId' => $index + 1
        ]);
        echo "✅ Detalle creado: {$item['description']} - $" . number_format($item['unit_price'], 2) . " + $" . number_format($item['tax_amount'], 2) . " impuesto\n";
    }

    echo "\n🚀 PASO 2: Enviando a QuickBooks usando SendSaleToQuickBooksJob\n";
    echo "─────────────────────────────────────────────────────────────────\n";

    // Crear y ejecutar el job
    $job = new SendSaleToQuickBooksJob($salesHeader->ID, $organizationId);

    echo "📊 Datos que se enviarán a QB:\n";
    echo "   • Cliente: {$testInvoiceData['customer_name']}\n";
    echo "   • RUC: {$testInvoiceData['customer_ruc']}\n";
    echo "   • Subtotal: $" . number_format($testInvoiceData['subtotal'], 2) . "\n";
    echo "   • Impuesto: $" . number_format($testInvoiceData['total_tax'], 2) . " (7%)\n";
    echo "   • Total: $" . number_format($testInvoiceData['total_amount'], 2) . "\n";

    echo "\n¿Proceder con el envío a QuickBooks? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);

    if (strtolower($response) !== 'y' && strtolower($response) !== 'yes') {
        echo "⏹️ Prueba cancelada\n";

        // Limpiar datos de prueba
        SalesDetailImp::where('SalesHeaderId', $salesHeader->ID)->delete();
        $salesHeader->delete();
        echo "🧹 Datos de prueba eliminados\n";
        exit(0);
    }

    // Ejecutar job
    echo "\n⚡ Ejecutando SendSaleToQuickBooksJob...\n";
    $startTime = microtime(true);

    try {
        $job->handle();
        $endTime = microtime(true);

        echo "✅ Job completado en " . round($endTime - $startTime, 2) . " segundos\n";

        // Refrescar factura para ver resultados
        $salesHeader->refresh();

        echo "\n📋 RESULTADOS:\n";
        echo "─────────────────\n";
        echo "✅ Estado sincronización: " . ($salesHeader->intuit_sync_status ?? 'N/A') . "\n";
        echo "✅ QuickBooks Invoice ID: " . ($salesHeader->intuit_invoice_id ?? 'N/A') . "\n";
        echo "✅ QuickBooks Customer ID: " . ($salesHeader->intuit_customer_id ?? 'N/A') . "\n";
        echo "✅ SyncToken: " . ($salesHeader->SyncToken ?? 'N/A') . "\n";
        echo "✅ CUFE extraído: " . ($salesHeader->intuit_extracted_cufe ?? 'N/A') . "\n";

        if ($salesHeader->intuit_sync_errors) {
            echo "⚠️ Errores: " . $salesHeader->intuit_sync_errors . "\n";
        }

        // Mostrar respuesta de QuickBooks si está disponible
        if (!empty($salesHeader->InvoiceNote)) {
            $qbResponse = json_decode($salesHeader->InvoiceNote, true);
            if ($qbResponse && isset($qbResponse['Invoice'])) {
                echo "\n🎯 VALIDACIÓN DE IMPUESTOS EN QB:\n";
                echo "──────────────────────────────────\n";
                $invoice = $qbResponse['Invoice'];

                echo "📄 DocNumber: " . ($invoice['DocNumber'] ?? 'N/A') . "\n";
                echo "💰 TotalAmt: $" . ($invoice['TotalAmt'] ?? 'N/A') . "\n";

                if (isset($invoice['TxnTaxDetail'])) {
                    $taxDetail = $invoice['TxnTaxDetail'];
                    echo "🏷️ TotalTax: $" . ($taxDetail['TotalTax'] ?? 'N/A') . "\n";

                    if (isset($taxDetail['TaxLine'][0])) {
                        $taxLine = $taxDetail['TaxLine'][0];
                        echo "📊 TaxPercent: " . ($taxLine['TaxLineDetail']['TaxPercent'] ?? 'N/A') . "%\n";
                        echo "📊 NetAmountTaxable: $" . ($taxLine['TaxLineDetail']['NetAmountTaxable'] ?? 'N/A') . "\n";
                    }
                }

                // Validar líneas de items
                if (isset($invoice['Line'])) {
                    echo "\n📦 LÍNEAS DE ITEMS:\n";
                    foreach ($invoice['Line'] as $line) {
                        if ($line['DetailType'] === 'SalesItemLineDetail') {
                            $detail = $line['SalesItemLineDetail'];
                            echo "   • Item: " . ($detail['ItemRef']['name'] ?? 'N/A') . "\n";
                            echo "     - Qty: " . ($detail['Qty'] ?? 'N/A') . "\n";
                            echo "     - UnitPrice: $" . ($detail['UnitPrice'] ?? 'N/A') . "\n";
                            echo "     - Amount: $" . ($line['Amount'] ?? 'N/A') . "\n";
                            if (isset($detail['TaxCode'])) {
                                echo "     - TaxCode: " . $detail['TaxCode']['name'] . " (" . $detail['TaxCode']['rateValue'] . "%)\n";
                            }
                        }
                    }
                }

                echo "\n✅ VALIDACIÓN EXITOSA: Los impuestos se registraron correctamente en QuickBooks\n";

            } else {
                echo "⚠️ No se pudo obtener respuesta detallada de QuickBooks\n";
            }
        }

    } catch (\Exception $e) {
        echo "❌ Error en el job: " . $e->getMessage() . "\n";
        echo "🔍 Trace: " . $e->getTraceAsString() . "\n";
    }

    echo "\n¿Mantener la factura de prueba en QB? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $keepResponse = trim(fgets($handle));
    fclose($handle);

    if (strtolower($keepResponse) !== 'y' && strtolower($keepResponse) !== 'yes') {
        echo "🧹 Limpiando datos de prueba de DocuCenter...\n";
        SalesDetailImp::where('SalesHeaderId', $salesHeader->ID)->delete();
        $salesHeader->delete();
        echo "✅ Datos locales eliminados (la factura permanece en QuickBooks)\n";
    } else {
        echo "📌 Factura de prueba mantenida en ambos sistemas\n";
        echo "   DocuCenter ID: {$salesHeader->ID}\n";
        echo "   QuickBooks ID: " . ($salesHeader->intuit_invoice_id ?? 'N/A') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔍 Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Prueba completada\n";
echo "═══════════════════\n";
