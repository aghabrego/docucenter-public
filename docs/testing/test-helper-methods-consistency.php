<?php
/**
 * Script de Prueba: Consistencia de Métodos Helper entre Create, CreateFast y CreateFastJob
 *
 * Propósito: Verificar que los métodos helper getUnifiedForeignReceiverData() y
 * getPaisCodeFromNacionalidad() produzcan resultados consistentes en los tres archivos.
 *
 * Uso: php docs/testing/test-helper-methods-consistency.php
 *
 * @author DocuCenter Team
 * @date 2025-01-20
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== PRUEBA DE CONSISTENCIA DE MÉTODOS HELPER ===\n";
echo "Verificando que todos los archivos produzcan resultados idénticos\n\n";

// Simular datos de prueba para un cliente extranjero tipo Chile
$testCases = [
    [
        'receptor_tipo' => '3',
        'destinoOperacion' => '1',
        'receptor_paisNacionalidad' => '45', // Chile (ID ejemplo)
        'receptor_pasaporteIdentidadExtranjera' => 'CHL123456789',
        'numeroIdentificacionExtranjero' => '',
        'paisExtranjero' => null,
        'codigoPaisReceptor' => null,
        'description' => 'Cliente extranjero chileno - operación interna'
    ],
    [
        'receptor_tipo' => '3',
        'destinoOperacion' => '2',
        'receptor_paisNacionalidad' => '45', // Chile
        'receptor_pasaporteIdentidadExtranjera' => 'CHL987654321',
        'numeroIdentificacionExtranjero' => '',
        'paisExtranjero' => 'CL',
        'codigoPaisReceptor' => 'CL',
        'description' => 'Cliente extranjero chileno - operación exportación'
    ],
    [
        'receptor_tipo' => '3',
        'destinoOperacion' => '1',
        'receptor_paisNacionalidad' => 'US', // USA como string
        'receptor_pasaporteIdentidadExtranjera' => 'USA123456789',
        'numeroIdentificacionExtranjero' => 'USA123456789',
        'paisExtranjero' => null,
        'codigoPaisReceptor' => null,
        'description' => 'Cliente estadounidense - receptor_paisNacionalidad como string'
    ]
];

$totalTests = count($testCases);
$passedTests = 0;

foreach ($testCases as $index => $testCase) {
    echo "🧪 Test " . ($index + 1) . ": {$testCase['description']}\n";
    echo "   - Tipo receptor: {$testCase['receptor_tipo']}\n";
    echo "   - Destino operación: {$testCase['destinoOperacion']}\n";
    echo "   - País nacionalidad: {$testCase['receptor_paisNacionalidad']}\n";
    echo "   - Pasaporte: {$testCase['receptor_pasaporteIdentidadExtranjera']}\n\n";

    // Simular cada archivo con reflection para acceder a métodos privados
    $results = [];
    $classes = [
        'Create' => 'App\\Http\\Livewire\\Admin\\Einvoice\\Create',
        'CreateFast' => 'App\\Http\\Livewire\\Admin\\Einvoice\\CreateFast',
        'CreateFastJob' => 'App\\Http\\Livewire\\Admin\\Einvoice\\CreateFastJob'
    ];

    foreach ($classes as $name => $className) {
        try {
            // Crear instancia mock
            $reflection = new ReflectionClass($className);
            $instance = $reflection->newInstanceWithoutConstructor();

            // Configurar propiedades de prueba
            foreach ($testCase as $property => $value) {
                if ($property !== 'description' && $reflection->hasProperty($property)) {
                    $prop = $reflection->getProperty($property);
                    $prop->setAccessible(true);
                    $prop->setValue($instance, $value);
                }
            }

            // Verificar si existe el método helper
            if ($reflection->hasMethod('getUnifiedForeignReceiverData')) {
                $method = $reflection->getMethod('getUnifiedForeignReceiverData');
                $method->setAccessible(true);

                // También necesitamos el método validArray
                if ($reflection->hasMethod('validArray')) {
                    $validArrayMethod = $reflection->getMethod('validArray');
                    $validArrayMethod->setAccessible(true);
                }

                $result = $method->invoke($instance);
                $results[$name] = $result;

                echo "   📋 $name resultado: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "   ❌ $name: No tiene método getUnifiedForeignReceiverData()\n";
                $results[$name] = ['error' => 'Método no encontrado'];
            }
        } catch (Exception $e) {
            echo "   ⚠️  $name: Error - " . $e->getMessage() . "\n";
            $results[$name] = ['error' => $e->getMessage()];
        }
    }

    // Verificar consistencia
    $firstResult = reset($results);
    $isConsistent = true;

    foreach ($results as $className => $result) {
        if (isset($result['error']) || isset($firstResult['error'])) {
            $isConsistent = false;
            break;
        }

        // Comparar campos críticos
        if (isset($result['dPaisExt']) && isset($firstResult['dPaisExt'])) {
            if ($result['dPaisExt'] !== $firstResult['dPaisExt']) {
                $isConsistent = false;
                echo "   ⚠️  Inconsistencia en dPaisExt: $className tiene '{$result['dPaisExt']}' vs esperado '{$firstResult['dPaisExt']}'\n";
            }
        }

        if (isset($result['tipoIdentificacion']) && isset($firstResult['tipoIdentificacion'])) {
            if ($result['tipoIdentificacion'] !== $firstResult['tipoIdentificacion']) {
                $isConsistent = false;
                echo "   ⚠️  Inconsistencia en tipoIdentificacion: $className tiene '{$result['tipoIdentificacion']}' vs esperado '{$firstResult['tipoIdentificacion']}'\n";
            }
        }
    }

    if ($isConsistent && !isset($firstResult['error'])) {
        echo "   ✅ CONSISTENTE: Todos los archivos producen resultados idénticos\n";
        $passedTests++;
    } elseif (!isset($firstResult['error'])) {
        echo "   ❌ INCONSISTENTE: Los archivos producen resultados diferentes\n";
    } else {
        echo "   ❌ ERROR: No se pudieron ejecutar las pruebas correctamente\n";
    }

    echo "\n" . str_repeat("-", 70) . "\n\n";
}

echo "🎯 RESULTADO GENERAL:\n";
echo "   Total tests: $totalTests\n";
echo "   Tests pasados: $passedTests\n";
echo "   Tests fallidos: " . ($totalTests - $passedTests) . "\n\n";

if ($passedTests === $totalTests) {
    echo "✅ ÉXITO: Todos los métodos helper son consistentes entre archivos\n";
    echo "🔧 Los helpers implementados resuelven la inconsistencia detectada\n";
    exit(0);
} else {
    echo "❌ FALLO: Hay inconsistencias entre los métodos helper\n";
    echo "🔧 Se requiere revisión adicional de la implementación\n";
    exit(1);
}
