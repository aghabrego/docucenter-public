#!/bin/bash

# Script para ejecutar UpdateQuickBooksInvoicesJob con datos reales
# Uso: ./test-quickbooks-update.sh [organization_id] [invoice_id]

ORGANIZATION_ID=${1:-2}
INVOICE_ID=${2:-69}

echo "🚀 Ejecutando UpdateQuickBooksInvoicesJob"
echo "📊 Organización ID: $ORGANIZATION_ID"
echo "📄 Factura ID: $INVOICE_ID"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Ejecutar dentro del contenedor Docker
docker exec -it docucenter_laravel.test php artisan tinker --execute="
use App\Models\Connection;
use App\Models\Organization;
use App\Jobs\Intuit\UpdateQuickBooksInvoicesJob;
use Illuminate\Support\Facades\DB;

try {
    // Validar organización
    \$org = Organization::find($ORGANIZATION_ID);
    if (!\$org) {
        echo '❌ Organización $ORGANIZATION_ID no encontrada\n';
        exit(1);
    }
    echo '✅ Organización encontrada: ' . \$org->name . \"\n\";

    // Buscar conexión QuickBooks para la organización
    \$conn = Connection::where('organization_id', $ORGANIZATION_ID)
                      ->where('application', 'acicloud')
                      ->first();

    if (!\$conn) {
        echo '❌ No se encontró conexión QuickBooks para organización $ORGANIZATION_ID\n';
        echo '🔍 Conexiones disponibles:\n';
        \$allConns = Connection::where('organization_id', $ORGANIZATION_ID)->get();
        foreach (\$allConns as \$c) {
            echo '   • ID: ' . \$c->id . ' - ' . \$c->name . ' (' . \$c->application . ')\n';
        }
        exit(1);
    }

    echo '✅ Conexión encontrada: ' . \$conn->id . ' - ' . \$conn->name . \"\n\";
    echo '   • Aplicación: ' . \$conn->application . \"\n\";

    // Verificar configuración de la conexión
    \$settings = \$conn->settings;
    if (is_string(\$settings)) {
        \$settings = json_decode(\$settings, true);
    }

    if (!empty(\$settings['App'])) {
        echo '   • App ID: ' . \$settings['App'] . \"\n\";
    }
    if (!empty(\$settings['Module'])) {
        echo '   • Módulo: ' . \$settings['Module'] . \"\n\";
    }

    echo '   • Última página procesada: ' . \$conn->last_processed_page . \"\n\";
    echo '   • Actualizada: ' . \$conn->updated_at . \"\n\";

    // Configurar BD de organización
    if (!empty(\$org->database)) {
        DB::connection()->useDatabase(\$org->database);
        echo '🔗 BD configurada: ' . \$org->database . \"\n\";
    }

    // Verificar factura
    \$invoice = DB::table('Sales_Header_Imp')->where('ID', $INVOICE_ID)->first();
    if (!\$invoice) {
        echo '❌ Factura $INVOICE_ID no encontrada\n';
        echo '🔍 Buscando facturas similares...\n';
        \$similar = DB::table('Sales_Header_Imp')
                    ->where('EzeeIssued', 1)
                    ->whereNotNull('intuit_invoice_id')
                    ->limit(5)
                    ->get(['ID', 'InvoiceNumber', 'intuit_invoice_id', 'origin']);
        foreach (\$similar as \$s) {
            echo '   • ID: ' . \$s->ID . ' - ' . \$s->InvoiceNumber . ' (QB: ' . \$s->intuit_invoice_id . ')\n';
        }
        exit(1);
    }
    echo '✅ Factura encontrada: ' . \$invoice->InvoiceNumber . \"\n\";
    echo '   • EzeeIssued: ' . \$invoice->EzeeIssued . \"\n\";
    echo '   • QB Invoice ID: ' . (\$invoice->intuit_invoice_id ?? 'N/A') . \"\n\";
    echo '   • Origin: ' . (\$invoice->origin ?? 'N/A') . \"\n\";
    echo '   • Estado Sync: ' . (\$invoice->intuit_sync_status ?? 'N/A') . \"\n\";
    echo '   • Último intento: ' . (\$invoice->intuit_last_attempt ?? 'N/A') . \"\n\";

    // Verificar si tiene InvoiceNote (necesario para CUFE)
    if (empty(\$invoice->InvoiceNote)) {
        echo '⚠️  InvoiceNote está vacío - no se podrá extraer CUFE\n';
    } else {
        echo '✅ InvoiceNote presente (' . strlen(\$invoice->InvoiceNote) . ' caracteres)\n';
    }

    // Verificar variables de entorno antes de ejecutar
    echo \"\n🔐 Verificando configuración:\n\";
    echo '   • ACI_EMAIL: ' . (env('ACI_EMAIL') ? 'Configurado (' . substr(env('ACI_EMAIL'), 0, 5) . '...)' : '❌ NO CONFIGURADO') . \"\n\";
    echo '   • ACI_PASSWORD: ' . (env('ACI_PASSWORD') ? 'Configurado' : '❌ NO CONFIGURADO') . \"\n\";

    if (!env('ACI_EMAIL') || !env('ACI_PASSWORD')) {
        echo '⚠️  Advertencia: Credenciales ACI no configuradas, el job puede fallar\n';
    }

    // Ejecutar job
    echo \"\n🔄 Ejecutando job con datos reales...\n\";
    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';

    \$startTime = microtime(true);
    \$job = new UpdateQuickBooksInvoicesJob(\$conn, $ORGANIZATION_ID, [$INVOICE_ID]);
    \$job->handle();
    \$endTime = microtime(true);

    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    echo '✅ Job completado en ' . round(\$endTime - \$startTime, 2) . ' segundos\n';
    echo '📊 Contadores finales:\n';
    echo '   • Total procesadas: ' . \$job->processCounters['total'] . \"\n\";
    echo '   • Actualizadas: ' . \$job->processCounters['updated'] . \"\n\";
    echo '   • Errores: ' . \$job->processCounters['failed'] . \"\n\";
    echo '   • Omitidas: ' . \$job->processCounters['skipped'] . \"\n\";
    echo '   • Estado final: ' . \$job->state . \"\n\";

} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . \"\n\";
    exit(1);
}
"

echo ""
echo "🎉 Ejecución completada"
