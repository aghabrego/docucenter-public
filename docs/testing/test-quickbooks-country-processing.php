<?php
/**
 * Script de prueba para validar el procesamiento correcto de países
 * en el servicio QuickBooksOnlineService
 *
 * Ubicación: docs/testing/test-quickbooks-country-processing.php
 * Uso: php docs/testing/test-quickbooks-country-processing.php [organization_id]
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Organization;
use App\Services\QuickBooksOnlineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Configurar aplicación Laravel básica
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Obtener organización ID desde parámetros de línea de comandos
$organizationId = $argv[1] ?? 1;

try {
    // Buscar organización
    $organization = Organization::find($organizationId);
    if (!$organization) {
        throw new Exception("Organización no encontrada: ID $organizationId");
    }

    echo "✅ Encontrada organización: {$organization->name} (DB: {$organization->database})\n";

    // Cambiar a la base de datos de la organización
    DB::connection()->useDatabase($organization->database);

    // Datos de prueba para cliente extranjero chileno
    $testCustomerData = [
        'CustomerID' => 'TEST-CHILE-001',
        'Customer_Bill_Name' => 'Solmary Test Cliente Chile',
        'Country' => 'Chile',
        'TIPO_RECEPTOR' => '04',
        'ReceiverType' => '4',
        'Email' => 'solmary@test.cl',
        'AddressLine1' => 'Dirección Santiago',
        'PASAPORTE' => 'CL12345678'
    ];

    echo "\n📋 Datos de prueba del cliente extranjero:\n";
    print_r($testCustomerData);

    // Buscar una conexión QuickBooks existente para esta organización
    $connection = \App\Models\Connection::where('organization_id', $organizationId)
        ->where('service_name', 'QuickBooks Online')
        ->first();

    if (!$connection) {
        echo "⚠️  No se encontró conexión QuickBooks para la organización, creando una de prueba temporal...\n";
        $connection = new \App\Models\Connection();
        $connection->organization_id = $organizationId;
        $connection->service_name = 'QuickBooks Online';
        // Campos mínimos para testing
    }

    // Instanciar servicio
    $service = new QuickBooksOnlineService($connection);

    echo "\n🔄 Creando cliente con datos de prueba...\n";

    // Crear cliente usando el método createDefaultClient
    $customer = $service->createDefaultClient($organization, $testCustomerData);

    echo "✅ Cliente creado exitosamente:\n";
    echo "   - CustomerID: {$customer->CustomerID}\n";
    echo "   - Nombre: {$customer->Customer_Bill_Name}\n";
    echo "   - País: {$customer->Country}\n";
    echo "   - TIPO_RECEPTOR: {$customer->Custom_field3}\n";
    echo "   - PASAPORTE: {$customer->Custom_field1}\n";

    // Verificar que el país se haya guardado correctamente
    if ($customer->Country === 'Chile') {
        echo "✅ CORRECTO: País 'Chile' fue preservado para cliente extranjero\n";
    } else {
        echo "❌ ERROR: País debería ser 'Chile' pero es '{$customer->Country}'\n";
    }

    // Verificar TIPO_RECEPTOR
    if ($customer->Custom_field3 === '04') {
        echo "✅ CORRECTO: TIPO_RECEPTOR es '04' (extranjero)\n";
    } else {
        echo "❌ ERROR: TIPO_RECEPTOR debería ser '04' pero es '{$customer->Custom_field3}'\n";
    }

    // Limpiar datos de prueba
    echo "\n🧹 Limpiando datos de prueba...\n";
    $customer->delete();
    echo "✅ Datos de prueba eliminados\n";

} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} finally {
    // Volver a la base de datos por defecto
    DB::connection()->useDatabase(env('DB_DATABASE'));
    echo "\n🔄 Conexión restaurada a base de datos por defecto\n";
}

echo "\n✅ Prueba completada\n";
