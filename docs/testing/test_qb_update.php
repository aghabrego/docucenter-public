<?php

// Script de prueba para UpdateQuickBooksInvoicesJob
// Ejecutar: php test_qb_update.php

require_once 'vendor/autoload.php';

use App\Models\Connection;
use App\Models\Organization;
use App\Jobs\Intuit\UpdateQuickBooksInvoicesJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Configuración
$organizationId = 2;
$invoiceId = 69;

echo "🚀 Iniciando prueba de UpdateQuickBooksInvoicesJob\n";
echo "📊 Organización ID: {$organizationId}\n";
echo "📄 Factura ID: {$invoiceId}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "✅ Laravel inicializado\n";

    // Validar organización
    $organization = Organization::find($organizationId);
    if (!$organization) {
        echo "❌ Organización {$organizationId} no encontrada\n";
        exit(1);
    }

    echo "✅ Organización encontrada: {$organization->name}\n";
    echo "🗄️  Base de datos: {$organization->database}\n";

    // Buscar conexión QuickBooks ANTES de cambiar la BD (está en la BD principal)
    echo "🔍 Buscando conexiones disponibles en BD principal...\n";
    $connections = DB::table('connections')->get();

    if ($connections->isEmpty()) {
        echo "❌ No se encontraron conexiones en BD principal\n";
        exit(1);
    }

    echo "📋 Conexiones encontradas:\n";
    foreach ($connections as $conn) {
        $fields = get_object_vars($conn);
        echo "   • ID: {$conn->id}\n";
        foreach ($fields as $field => $value) {
            if ($field !== 'id') {
                $displayValue = is_string($value) && strlen($value) > 50
                    ? substr($value, 0, 50) . '...'
                    : $value;
                echo "     - {$field}: {$displayValue}\n";
            }
        }
        echo "\n";
    }

    // Buscar conexión específica para la organización 2
    $targetConnection = null;
    foreach ($connections as $conn) {
        if ($conn->organization_id == $organizationId && stripos($conn->application, 'acicloud') !== false) {
            $targetConnection = $conn;
            break;
        }
    }

    if ($targetConnection) {
        $connection = Connection::find($targetConnection->id);
    } else {
        // Usar primera conexión disponible como fallback
        $connection = Connection::first();
    }
    if (!$connection) {
        echo "❌ No se pudo instanciar el modelo Connection\n";
        exit(1);
    }

    echo "✅ Usando conexión: ID {$connection->id}\n";

    // Verificar si la BD de la organización existe y tiene tablas
    echo "🔍 Verificando base de datos de la organización...\n";
    if (!empty($organization->database)) {
        try {
            DB::connection()->useDatabase($organization->database);
            $tables = DB::select('SHOW TABLES');
            echo "✅ BD de organización configurada: {$organization->database}\n";
            echo "� Tablas encontradas: " . count($tables) . "\n";

            // Buscar tabla de facturas con prioridad
            $tableFound = false;
            $facturaTable = '';
            $tablePriority = ['Sales_Header_Imp', 'SalesHeaderImp', 'sales_header_imp'];

            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                echo "   • Tabla encontrada: {$tableName}\n";

                // Verificar tabla con prioridad
                if (in_array($tableName, $tablePriority)) {
                    $facturaTable = $tableName;
                    $tableFound = true;
                    echo "     ✅ Usando tabla principal de facturas: {$tableName}\n";
                    break;
                }
            }

            // Si no se encuentra la tabla principal, buscar cualquier tabla de ventas
            if (!$tableFound) {
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    if (stripos($tableName, 'sales_header') !== false || stripos($tableName, 'salesheader') !== false) {
                        $facturaTable = $tableName;
                        $tableFound = true;
                        echo "     ⚠️  Usando tabla alternativa: {$tableName}\n";
                        break;
                    }
                }
            }

            if (!$tableFound) {
                echo "⚠️  No se encontró tabla de facturas en BD de organización\n";
                echo "⚠️  Usando BD principal en su lugar\n";
                DB::connection()->useDatabase(env('DB_DATABASE'));
                $facturaTable = 'SalesHeaderImp';
            }
        } catch (\Exception $e) {
            echo "⚠️  Error accediendo BD de organización: " . $e->getMessage() . "\n";
            echo "⚠️  Usando BD principal en su lugar\n";
            DB::connection()->useDatabase(env('DB_DATABASE'));
            $facturaTable = 'SalesHeaderImp';
        }
    } else {
        echo "⚠️  BD de organización no especificada, usando BD principal\n";
        $facturaTable = 'SalesHeaderImp';
    }

    // Verificar la factura específica
    echo "🔍 Verificando factura ID {$invoiceId} en tabla {$facturaTable}...\n";
    $invoice = DB::table($facturaTable)
        ->where('ID', $invoiceId)
        ->first();

    if (!$invoice) {
        echo "❌ Factura {$invoiceId} no encontrada\n";
        exit(1);
    }

    echo "✅ Factura encontrada:\n";
    echo "   • ID: {$invoice->ID}\n";
    echo "   • Número: " . ($invoice->InvoiceNumber ?? 'N/A') . "\n";
    echo "   • EzeeIssued: " . ($invoice->EzeeIssued ?? 'N/A') . "\n";
    echo "   • InvoiceNote: " . (empty($invoice->InvoiceNote) ? 'Vacío' : 'Presente') . "\n";
    echo "   • intuit_invoice_id: " . ($invoice->intuit_invoice_id ?? 'N/A') . "\n";
    echo "   • Origin: " . ($invoice->origin ?? 'N/A') . "\n";

    // Verificar variables de entorno
    echo "\n🔐 Variables de entorno:\n";
    echo "   • ACI_EMAIL: " . (env('ACI_EMAIL') ? 'Configurado' : 'NO CONFIGURADO') . "\n";
    echo "   • ACI_PASSWORD: " . (env('ACI_PASSWORD') ? 'Configurado' : 'NO CONFIGURADO') . "\n";

    echo "\n🧪 MODO PRUEBA - Creando job sin ejecutar...\n";

    // Crear el job con la factura específica
    $job = new UpdateQuickBooksInvoicesJob($connection, $organizationId, [$invoiceId]);

    echo "✅ Job creado exitosamente\n";
    echo "📋 Configuración del job:\n";
    echo "   • Organización ID: {$job->organizationId}\n";
    echo "   • Conexión ID: {$job->connectionModel->id}\n";
    echo "   • Estado inicial: {$job->state}\n";
    echo "   • Facturas específicas: " . ($job->invoicesToUpdate ? $job->invoicesToUpdate->count() : 'Todas') . "\n";

    echo "\n¿Desea ejecutar el job? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);

    if (strtolower($response) === 'y' || strtolower($response) === 'yes') {
        echo "\n🔄 Ejecutando job...\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $startTime = microtime(true);
        $job->handle();
        $endTime = microtime(true);

        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ Job completado en " . round($endTime - $startTime, 2) . " segundos\n";
        echo "📊 Estado final: {$job->state}\n";
        echo "📈 Contadores: " . json_encode($job->processCounters, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⏹️  Ejecución cancelada\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔍 Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Script completado\n";
