<?php

/**
 * Script para inspeccionar el payload REAL que envía QuickBooks
 *
 * Ejecutar desde raíz del proyecto:
 * docker exec -it docucenter_laravel.test php docs/testing/inspect-real-qb-payload.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Organization;
use App\Models\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== INSPECCIÓN DE PAYLOAD REAL DE QUICKBOOKS ===\n\n";

// Obtener organización 2
$organization = Organization::find(2);
if (!$organization) {
    echo "ERROR: Organization 2 not found\n";
    exit(1);
}

echo "Organization: {$organization->nombre} (ID: {$organization->id})\n\n";

// Buscar conexión QuickBooks
$qbConnection = Connection::where('organization_id', $organization->id)
    ->where('application', 'quickbooks')
    ->first();

if (!$qbConnection) {
    echo "ERROR: No QuickBooks connection found\n";
    exit(1);
}

$settings = json_decode($qbConnection->settings, true);
if (empty($settings['access_token'])) {
    echo "ERROR: No access token found\n";
    exit(1);
}

echo "✓ Conexión QuickBooks encontrada\n";
echo "  RealmId: {$settings['realmId']}\n\n";

// Buscar una invoice en la BD
DB::connection()->useDatabase($organization->database);
$recentInvoice = DB::table('Sales_Header_Imp')
    ->where('InvoiceNumber', 'like', 'FE%')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$recentInvoice) {
    echo "No se encontraron facturas recientes. Buscando cualquier factura...\n";
    $recentInvoice = DB::table('Sales_Header_Imp')
        ->orderBy('created_at', 'desc')
        ->first();
}

if (!$recentInvoice) {
    echo "ERROR: No invoices found in database\n";
    exit(1);
}

echo "Factura encontrada: {$recentInvoice->InvoiceNumber}\n";
echo "  Total: \${$recentInvoice->Net_due}\n";
echo "  ITBMS: \${$recentInvoice->TotalTaxInvupos}\n";
echo "  Fecha: {$recentInvoice->created_at}\n\n";

// Obtener detalles de la factura
$details = DB::table('Sales_Detail_Imp')
    ->where('InvoiceNumber', $recentInvoice->InvoiceNumber)
    ->get();

echo "=== DETALLES DE LA FACTURA ===\n\n";
foreach ($details as $detail) {
    echo "Línea {$detail->Sequential}:\n";
    echo "  Descripción: {$detail->Description}\n";
    echo "  Subtotal: \${$detail->Sub_Total}\n";
    echo "  ITBMS: \${$detail->Itbms}\n";
    echo "  Taxable: " . ($detail->Taxable == 1 ? 'Sí' : 'No') . "\n\n";
}

// Intentar obtener la factura desde QuickBooks API directamente
echo "=== CONSULTANDO QUICKBOOKS API ===\n\n";

$realmId = $settings['realmId'];
$accessToken = $settings['access_token'];

// Buscar Invoice ID en transactions
DB::connection()->useDatabase(env('DB_DATABASE')); // Volver a BD principal
$transaction = DB::table('transactions')
    ->where('organization_id', $organization->id)
    ->where('transaction_code', $recentInvoice->InvoiceNumber)
    ->orderBy('created_at', 'desc')
    ->first();

if (!$transaction) {
    echo "⚠ No se encontró transacción almacenada para {$recentInvoice->InvoiceNumber}\n";
    echo "Buscando cualquier transacción reciente de QuickBooks...\n\n";

    $transaction = DB::table('transactions')
        ->where('organization_id', $organization->id)
        ->where('provider', 'quickbooks')
        ->orderBy('created_at', 'desc')
        ->first();
}

if ($transaction) {
    echo "✓ Transacción encontrada:\n";
    echo "  Code: {$transaction->transaction_code}\n";
    echo "  Provider: {$transaction->provider}\n";
    echo "  Fecha: {$transaction->created_at}\n\n";

    $payload = json_decode($transaction->payload, true);

    if (!empty($payload['Invoice'])) {
        echo "=== ESTRUCTURA DEL PAYLOAD REAL ===\n\n";

        $invoice = $payload['Invoice'];
        echo "DocNumber: " . ($invoice['DocNumber'] ?? 'N/A') . "\n";
        echo "TotalAmt: " . ($invoice['TotalAmt'] ?? 'N/A') . "\n";
        echo "TxnTaxDetail.TotalTax: " . ($invoice['TxnTaxDetail']['TotalTax'] ?? 'N/A') . "\n\n";

        if (!empty($invoice['Line'])) {
            echo "=== ESTRUCTURA DE LÍNEAS ===\n\n";

            foreach ($invoice['Line'] as $index => $line) {
                if ($line['DetailType'] === 'SalesItemLineDetail') {
                    echo "Línea " . ($index + 1) . ":\n";
                    echo "  Description: " . ($line['Description'] ?? 'N/A') . "\n";
                    echo "  Amount: " . ($line['Amount'] ?? 'N/A') . "\n";

                    $salesDetail = $line['SalesItemLineDetail'] ?? [];
                    echo "  TaxCodeRef: " . ($salesDetail['TaxCodeRef']['value'] ?? 'N/A') . "\n";

                    // *** CAMPO CRÍTICO: ¿Existe TaxCode? ***
                    if (isset($salesDetail['TaxCode'])) {
                        echo "  ✓ TaxCode presente:\n";
                        echo "    - id: " . ($salesDetail['TaxCode']['id'] ?? 'N/A') . "\n";
                        echo "    - name: " . ($salesDetail['TaxCode']['name'] ?? 'N/A') . "\n";
                        echo "    - description: " . ($salesDetail['TaxCode']['description'] ?? 'N/A') . "\n";

                        // *** CAMPO SÚPER CRÍTICO: rateValue ***
                        if (isset($salesDetail['TaxCode']['rateValue'])) {
                            echo "    - ✓✓✓ rateValue: " . $salesDetail['TaxCode']['rateValue'] . " ✓✓✓\n";
                        } else {
                            echo "    - ✗✗✗ rateValue: NO PRESENTE ✗✗✗\n";
                        }
                    } else {
                        echo "  ✗ TaxCode: NO PRESENTE\n";
                    }

                    // Verificar TaxAmount
                    if (isset($salesDetail['TaxAmount'])) {
                        echo "  ✓ TaxAmount: " . $salesDetail['TaxAmount'] . "\n";
                    } else {
                        echo "  ✗ TaxAmount: NO PRESENTE\n";
                    }

                    echo "\n";
                }
            }
        }

        echo "\n=== CONCLUSIÓN ===\n\n";
        echo "Con esta estructura real podemos determinar:\n";
        echo "1. ¿QB envía TaxCode.rateValue? (Si no, PRIORIDAD 1 nunca se usa)\n";
        echo "2. ¿QB envía TaxAmount? (PRIORIDAD 2)\n";
        echo "3. Si no hay ninguno, se debe usar ACI Cloud (PRIORIDAD 3)\n\n";

        // Guardar payload completo para inspección
        file_put_contents(
            __DIR__ . '/real-qb-payload-sample.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        echo "✓ Payload completo guardado en: docs/testing/real-qb-payload-sample.json\n";

    } else {
        echo "⚠ Payload no tiene estructura de Invoice\n";
    }
} else {
    echo "⚠ No se encontró ninguna transacción almacenada\n";
    echo "\nPara obtener datos reales:\n";
    echo "1. Procesar una factura desde QuickBooks\n";
    echo "2. El webhook almacenará el payload en la tabla 'transactions'\n";
    echo "3. Ejecutar este script nuevamente\n";
}

echo "\n";
