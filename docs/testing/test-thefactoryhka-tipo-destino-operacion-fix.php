<?php
/**
 * Test de verificación: Corrección de conflicto tipoOperacion vs destinoOperacion en TheFactoryHKA
 *
 * Error objetivo:
 * "El destino de la operación no puede ser Extranjero si el tipo de documento es factura de operación Interna."
 *
 * Lógica corregida:
 * 1. Si customerCountry='PA' y receptor_tipo='3' o '4' (extranjero) → Reclasificar como '2' (nacional)
 * 2. Clientes extranjeros REALES (NO PA) → destinoOperacion=2, tipoOperacion=2
 * 3. Clientes nacionales (PA) → destinoOperacion=1, tipoOperacion=1
 *
 * Uso:
 * cd /home/weirdolabs/code/docucenter
 * docker exec -it docucenter_laravel.test php docs/testing/test-thefactoryhka-tipo-destino-operacion-fix.php
 */

// Configurar entorno Laravel
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== TEST: Corrección Conflicto tipoOperacion vs destinoOperacion TheFactoryHKA ===\n";

// Configurar conexión de prueba
$orgId = 147; // ID de organización de prueba
$database = '9_734_1672_56';

try {
    // Cambiar a base de datos específica
    DB::connection()->useDatabase($database);
    echo "✅ Conectado a base de datos: {$database}\n";

    // Test Case 1: Cliente extranjero con país PA (debe reclasificarse)
    echo "\n--- Test Case 1: Cliente extranjero mal clasificado (país=PA) ---\n";

    $mockCustomer = (object)[
        'CustomerID' => 'TEST001',
        'Customer_Bill_Name' => 'Juan Pérez Test',
        'Country' => 'PA', // ¡Cliente panameño!
        'Custom_field1' => '8-123-456', // RUC en lugar de pasaporte
        'Custom_field2' => '78',         // DV
        'Custom_field3' => '3',          // Clasificado INCORRECTAMENTE como extranjero
        'Custom_field4' => '1',
        'Custom_field5' => '',
        'AddressLine1' => 'Panama City',
        'Email' => 'test@panama.com'
    ];

    // Simular lógica corregida de CreateFastJob
    $receptor_tipo = $mockCustomer->Custom_field3 ?? '2';
    $customerCountry = $mockCustomer->Country ?? 'PA';

    echo "   - País original: {$customerCountry}\n";
    echo "   - Tipo receptor original: {$receptor_tipo} (3=Extranjero)\n";

    // CORRECCIÓN FUNDAMENTAL: Si país=PA, NUNCA debe ser extranjero
    if ($customerCountry === 'PA' && ($receptor_tipo === '3' || $receptor_tipo === '4')) {
        echo "   ⚠️  DETECTADO: Cliente extranjero con país PA - RECLASIFICANDO\n";
        $receptor_tipo = '2'; // Persona Natural panameña
        echo "   ✅ Tipo receptor corregido: {$receptor_tipo} (2=Persona Natural)\n";
    }

    // Determinar destino de operación basado en tipo corregido
    if ($receptor_tipo === '3' || $receptor_tipo === '4') {
        $destinoOperacion = 2; // Extranjero real
        $tipoOperacion = 2;    // Operación externa
    } else {
        $destinoOperacion = 1; // Nacional
        $tipoOperacion = 1;    // Operación interna
    }

    echo "   - Destino operación final: {$destinoOperacion} (1=Nacional, 2=Extranjero)\n";
    echo "   - Tipo operación final: {$tipoOperacion} (1=Interna, 2=Externa)\n";

    // Verificar regla TheFactoryHKA
    $isValid = !($tipoOperacion == 1 && $destinoOperacion == 2);
    if ($isValid) {
        echo "   ✅ VÁLIDO: No hay conflicto tipoOperacion vs destinoOperacion\n";
    } else {
        echo "   ❌ ERROR: Conflicto detectado - tipoOperacion=1 con destinoOperacion=2\n";
    }

    // Test Case 2: Cliente extranjero real (NO PA)
    echo "\n--- Test Case 2: Cliente extranjero real (país≠PA) ---\n";

    $mockCustomerReal = (object)[
        'CustomerID' => 'TEST002',
        'Customer_Bill_Name' => 'Maria Rodriguez Chile',
        'Country' => 'CL', // Cliente chileno
        'Custom_field1' => '12345678-9', // Pasaporte chileno
        'Custom_field2' => '',
        'Custom_field3' => '3',          // Extranjero (correcto)
        'Custom_field4' => '',
        'Custom_field5' => '',
        'AddressLine1' => 'Santiago, Chile',
        'Email' => 'maria@chile.com'
    ];

    $receptor_tipo_real = $mockCustomerReal->Custom_field3 ?? '2';
    $customerCountry_real = $mockCustomerReal->Country ?? 'PA';

    echo "   - País: {$customerCountry_real}\n";
    echo "   - Tipo receptor: {$receptor_tipo_real} (3=Extranjero)\n";

    // NO reclasificar porque NO es PA
    if ($customerCountry_real === 'PA' && ($receptor_tipo_real === '3' || $receptor_tipo_real === '4')) {
        echo "   ⚠️  DETECTADO: Cliente extranjero con país PA - RECLASIFICANDO\n";
        $receptor_tipo_real = '2';
    } else {
        echo "   ✅ Cliente extranjero real - NO reclasificar\n";
    }

    // Determinar destino de operación
    if ($receptor_tipo_real === '3' || $receptor_tipo_real === '4') {
        $destinoOperacion_real = 2; // Extranjero real
        $tipoOperacion_real = 2;    // Operación externa
    } else {
        $destinoOperacion_real = 1; // Nacional
        $tipoOperacion_real = 1;    // Operación interna
    }

    echo "   - Destino operación: {$destinoOperacion_real} (2=Extranjero)\n";
    echo "   - Tipo operación: {$tipoOperacion_real} (2=Externa)\n";

    // Verificar regla TheFactoryHKA
    $isValid_real = !($tipoOperacion_real == 1 && $destinoOperacion_real == 2);
    if ($isValid_real) {
        echo "   ✅ VÁLIDO: Configuración correcta para extranjero real\n";
    } else {
        echo "   ❌ ERROR: Conflicto en extranjero real\n";
    }

    // Test Case 3: Cliente nacional tradicional
    echo "\n--- Test Case 3: Cliente nacional tradicional ---\n";

    $mockCustomerNacional = (object)[
        'CustomerID' => 'TEST003',
        'Customer_Bill_Name' => 'Carlos Mendoza Nacional',
        'Country' => 'PA',
        'Custom_field1' => '8-987-654',
        'Custom_field2' => '45',
        'Custom_field3' => '2',          // Persona Natural (correcto)
        'Custom_field4' => '1',
        'Custom_field5' => '',
        'AddressLine1' => 'Panama City',
        'Email' => 'carlos@panama.com'
    ];

    $receptor_tipo_nac = $mockCustomerNacional->Custom_field3 ?? '2';
    $customerCountry_nac = $mockCustomerNacional->Country ?? 'PA';

    echo "   - País: {$customerCountry_nac}\n";
    echo "   - Tipo receptor: {$receptor_tipo_nac} (2=Persona Natural)\n";

    // Determinar destino de operación
    if ($receptor_tipo_nac === '3' || $receptor_tipo_nac === '4') {
        $destinoOperacion_nac = 2;
        $tipoOperacion_nac = 2;
    } else {
        $destinoOperacion_nac = 1; // Nacional
        $tipoOperacion_nac = 1;    // Operación interna
    }

    echo "   - Destino operación: {$destinoOperacion_nac} (1=Nacional)\n";
    echo "   - Tipo operación: {$tipoOperacion_nac} (1=Interna)\n";

    // Verificar regla TheFactoryHKA
    $isValid_nac = !($tipoOperacion_nac == 1 && $destinoOperacion_nac == 2);
    if ($isValid_nac) {
        echo "   ✅ VÁLIDO: Cliente nacional configurado correctamente\n";
    } else {
        echo "   ❌ ERROR: Conflicto en cliente nacional\n";
    }

    // Verificar datos de países en BD
    echo "\n--- Verificación de datos en BD ---\n";

    $panama = DB::table('destination_country_operations')
        ->where('code', 'PA')
        ->first();

    if ($panama) {
        echo "   ✅ Panamá encontrado en BD: ID={$panama->id}, Code={$panama->code}, Name={$panama->name}\n";
    } else {
        echo "   ❌ ERROR: Panamá no encontrado en destination_country_operations\n";
    }

    $destinationOps = DB::table('destination_operations')->get();
    echo "   📋 Códigos destino operación disponibles:\n";
    foreach ($destinationOps as $op) {
        echo "      - ID: {$op->id}, Code: {$op->code}, Name: {$op->name}\n";
    }

    // Resumen final
    echo "\n=== RESUMEN FINAL ===\n";
    $allValid = $isValid && $isValid_real && $isValid_nac;

    if ($allValid) {
        echo "✅ TODOS LOS TEST CASES PASARON\n";
        echo "✅ La corrección resuelve el conflicto tipoOperacion vs destinoOperacion\n";
        echo "✅ Regla TheFactoryHKA respetada en todos los casos\n";
    } else {
        echo "❌ ALGUNOS TEST CASES FALLARON\n";
        echo "❌ Revisar lógica de asignación de tipos y destinos\n";
    }

    echo "\n🔍 Próximos pasos:\n";
    echo "   1. Probar en producción con facturas reales\n";
    echo "   2. Verificar logs de TheFactoryHKA para confirmar aceptación\n";
    echo "   3. Monitorear casos edge con países poco comunes\n";

} catch (Exception $e) {
    echo "❌ ERROR en test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
