<?php

/**
 * Script de prueba para validar la lógica de PASAPORTE en QuickBooksOnlineService
 *
 * Este script simula diferentes escenarios de clientes extranjeros y nacionales
 * para verificar que la lógica de validación funciona correctamente.
 *
 * Ubicación: docs/testing/quickbooks-pasaporte-validation-test.php
 * Uso: php docs/testing/quickbooks-pasaporte-validation-test.php [organizationId]
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\QuickBooksOnlineService;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Simula la lógica de validación de CustomerRef sin crear registros en BD
 */
function simulateCustomerRefValidation(array $request): array
{
    // Simular extracción de datos del request (lógica del servicio)
    $rucOriginal = array_get($request, 'Ruc', '0-0-0');
    $dvOriginal = array_get($request, 'Dv', '00');
    $tipoFromRequest = array_get($request, 'TIPO');
    $tipoReceptorFromRequest = array_get($request, 'TIPO_RECEPTOR', '2');
    $pasaporteFromRequest = array_get($request, 'PASAPORTE', null);

    $ruc = '';
    $dv = '';
    $tipoContribuyente = '2'; // Default: Persona Natural
    $locationCode = null;
    $pasaporte = null;

    // Expresiones regulares para extraer los valores
    if (preg_match('/Ruc:(\d+-\d+-\d+)|RUC:(\d+-\d+-\d+)|RUC (\d+-\d+-\d+)|Ruc (\d+-\d+-\d+)|^\d+-\d+-\d+$/i', $rucOriginal, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $ruc = end($filteredMatches);
    }

    if (preg_match('/Dv:(\d{2})|DV:(\d{2})|Dv (\d{2})|DV (\d{2})|^\d{2}/i', $dvOriginal, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $dv = end($filteredMatches);
    }

    // Determinar TIPO_RECEPTOR
    $tipoReceptor = '02'; // Default: Consumidor final

    if (!is_null($tipoReceptorFromRequest)) {
        $tipoReceptorNormalized = str_pad((string)$tipoReceptorFromRequest, 2, '0', STR_PAD_LEFT);
        if (in_array($tipoReceptorNormalized, ['01', '02', '03', '04'])) {
            $tipoReceptor = $tipoReceptorNormalized;
        }
    } else {
        // Lógica automática
        if (!empty($pasaporteFromRequest)) {
            $tipoReceptor = '04'; // Extranjero (tiene PASAPORTE)
            $pasaporte = $pasaporteFromRequest;
        } elseif (!empty($ruc) && !empty($dv)) {
            $tipoReceptor = '01'; // Contribuyente (tiene RUC)
        }
    }

    // Lógica de validación cruzada para extranjeros (TIPO_RECEPTOR: 04)
    if ($tipoReceptor === '04') {
        if (!empty($pasaporteFromRequest)) {
            $pasaporte = $pasaporteFromRequest;
            // Limpiar campos que no aplican para extranjeros
            $ruc = null;
            $dv = null;
            $tipoFromRequest = null;
        } else {
            $tipoReceptor = '02';
        }
    } else {
        // Para no extranjeros: PASAPORTE debe ser null
        $pasaporte = null;
    }

    return [
        'ruc' => $ruc,
        'dv' => $dv,
        'tipoReceptor' => $tipoReceptor,
        'tipoContribuyente' => $tipoContribuyente,
        'pasaporte' => $pasaporte,
        'customerID' => !empty($ruc) ? $ruc : (!empty($pasaporte) ? $pasaporte : array_get($request, 'CustomerID')),
        'custom_field1' => $tipoReceptor === '04' ? $pasaporte : $ruc,    // RUC para nacionales, PASAPORTE para extranjeros
        'custom_field2' => $tipoReceptor === '04' ? null : $dv,           // DV solo para nacionales
    ];
}

/**
 * Casos de prueba para validar diferentes escenarios
 */
function getTestCases(): array
{
    return [
        // Caso 1: Cliente extranjero con PASAPORTE válido
        [
            'name' => 'Cliente extranjero con PASAPORTE válido',
            'request' => [
                'CustomerID' => 'EXT001',
                'Customer_Bill_Name' => 'John Smith',
                'PASAPORTE' => 'US123456789',
                'TIPO_RECEPTOR' => '04',
                'Country' => 'US',
                'Email' => 'john@example.com'
            ],
            'expected' => [
                'ruc' => null,
                'dv' => null,
                'tipoReceptor' => '04',
                'pasaporte' => 'US123456789',
                'customerID' => 'US123456789',
                'custom_field1' => 'US123456789',  // PASAPORTE va en Custom_field1
                'custom_field2' => null,           // DV es null para extranjeros
            ]
        ],

        // Caso 2: Cliente extranjero sin PASAPORTE (debe convertirse a consumidor final)
        [
            'name' => 'Cliente extranjero sin PASAPORTE',
            'request' => [
                'CustomerID' => 'EXT002',
                'Customer_Bill_Name' => 'Jane Doe',
                'TIPO_RECEPTOR' => '04',
                'Country' => 'US',
            ],
            'expected' => [
                'ruc' => null,
                'dv' => null,
                'tipoReceptor' => '02', // Cambió a consumidor final
                'pasaporte' => null,
                'customerID' => 'EXT002',
                'custom_field1' => null,           // No RUC ni PASAPORTE
                'custom_field2' => null,           // No DV
            ]
        ],

        // Caso 3: Cliente nacional con RUC válido
        [
            'name' => 'Cliente nacional con RUC válido',
            'request' => [
                'CustomerID' => 'NAC001',
                'Customer_Bill_Name' => 'Juan Pérez',
                'Ruc' => '8-123-456',
                'Dv' => '78',
                'TIPO_RECEPTOR' => '01',
                'Country' => 'PA',
            ],
            'expected' => [
                'ruc' => '8-123-456',
                'dv' => '78',
                'tipoReceptor' => '01',
                'pasaporte' => null, // Debe ser null para nacionales
                'customerID' => '8-123-456',
                'custom_field1' => '8-123-456',    // RUC va en Custom_field1
                'custom_field2' => '78',           // DV va en Custom_field2
            ]
        ],

        // Caso 4: Detección automática de extranjero por PASAPORTE
        [
            'name' => 'Detección automática de extranjero por PASAPORTE',
            'request' => [
                'CustomerID' => 'AUTO001',
                'Customer_Bill_Name' => 'Marie Curie',
                'PASAPORTE' => 'FR987654321',
                // Sin TIPO_RECEPTOR explícito
            ],
            'expected' => [
                'ruc' => null,
                'dv' => null,
                'tipoReceptor' => '04', // Detectado automáticamente
                'pasaporte' => 'FR987654321',
                'customerID' => 'FR987654321',
                'custom_field1' => 'FR987654321',  // PASAPORTE va en Custom_field1
                'custom_field2' => null,           // DV es null para extranjeros
            ]
        ],

        // Caso 5: Cliente con RUC y PASAPORTE (extranjero debe ganar)
        [
            'name' => 'Cliente con RUC y PASAPORTE (conflicto)',
            'request' => [
                'CustomerID' => 'CONF001',
                'Customer_Bill_Name' => 'Mixed Data',
                'Ruc' => '8-999-888',
                'Dv' => '55',
                'PASAPORTE' => 'CA111222333',
                'TIPO_RECEPTOR' => '04', // Explícitamente extranjero
            ],
            'expected' => [
                'ruc' => null, // Debe ser null porque es extranjero
                'dv' => null,  // Debe ser null porque es extranjero
                'tipoReceptor' => '04',
                'pasaporte' => 'CA111222333',
                'customerID' => 'CA111222333',
                'custom_field1' => 'CA111222333', // PASAPORTE va en Custom_field1
                'custom_field2' => null,          // DV es null para extranjeros
            ]
        ],

        // Caso 6: Consumidor final sin datos especiales
        [
            'name' => 'Consumidor final básico',
            'request' => [
                'CustomerID' => 'FINAL001',
                'Customer_Bill_Name' => 'Cliente Final',
                'Country' => 'PA',
            ],
            'expected' => [
                'ruc' => null,
                'dv' => null,
                'tipoReceptor' => '02', // Default consumidor final
                'pasaporte' => null,
                'customerID' => 'FINAL001',
                'custom_field1' => null,          // Sin RUC ni PASAPORTE
                'custom_field2' => null,          // Sin DV
            ]
        ],
    ];
}

/**
 * Ejecutar todas las pruebas
 */
function runTests(): void
{
    echo "🧪 TESTING: Validación de lógica PASAPORTE en QuickBooksOnlineService\n";
    echo "=" . str_repeat("=", 70) . "\n\n";

    $testCases = getTestCases();
    $passed = 0;
    $failed = 0;

    foreach ($testCases as $index => $testCase) {
        echo "Caso " . ($index + 1) . ": " . $testCase['name'] . "\n";
        echo str_repeat("-", 50) . "\n";

        $result = simulateCustomerRefValidation($testCase['request']);
        $expected = $testCase['expected'];

        $success = true;
        $errors = [];

        // Verificar cada campo esperado
        foreach ($expected as $field => $expectedValue) {
            if ($result[$field] !== $expectedValue) {
                $success = false;
                $errors[] = "  ❌ {$field}: esperado '{$expectedValue}', obtenido '{$result[$field]}'";
            }
        }

        if ($success) {
            echo "  ✅ PASSED\n";
            $passed++;
        } else {
            echo "  ❌ FAILED\n";
            foreach ($errors as $error) {
                echo $error . "\n";
            }
            $failed++;
        }

        echo "\n  📊 Resultado:\n";
        echo "    - RUC: " . ($result['ruc'] ?? 'null') . "\n";
        echo "    - DV: " . ($result['dv'] ?? 'null') . "\n";
        echo "    - TIPO_RECEPTOR: " . $result['tipoReceptor'] . "\n";
        echo "    - PASAPORTE: " . ($result['pasaporte'] ?? 'null') . "\n";
        echo "    - CustomerID: " . $result['customerID'] . "\n";
        echo "    - Custom_field1: " . ($result['custom_field1'] ?? 'null') . "\n";
        echo "    - Custom_field2: " . ($result['custom_field2'] ?? 'null') . "\n";
        echo "\n" . str_repeat("=", 70) . "\n\n";
    }

    // Resumen final
    $total = $passed + $failed;
    echo "📋 RESUMEN DE PRUEBAS:\n";
    echo "  Total: {$total}\n";
    echo "  ✅ Passed: {$passed}\n";
    echo "  ❌ Failed: {$failed}\n";
    echo "  📈 Success Rate: " . round(($passed / $total) * 100, 2) . "%\n\n";

    if ($failed === 0) {
        echo "🎉 ¡Todas las pruebas pasaron exitosamente!\n";
        echo "La lógica de PASAPORTE está funcionando correctamente.\n";
    } else {
        echo "⚠️  Algunas pruebas fallaron. Revisar la implementación.\n";
    }
}

/**
 * Prueba interactiva con datos personalizados
 */
function interactiveTest(): void
{
    echo "🔧 MODO INTERACTIVO\n";
    echo "Ingresa los datos del cliente para probar la validación:\n\n";

    $request = [];

    echo "CustomerID: ";
    $request['CustomerID'] = trim(fgets(STDIN));

    echo "Customer_Bill_Name: ";
    $request['Customer_Bill_Name'] = trim(fgets(STDIN));

    echo "RUC (formato 8-123-456 o vacío): ";
    $ruc = trim(fgets(STDIN));
    if (!empty($ruc)) $request['Ruc'] = $ruc;

    echo "DV (formato 78 o vacío): ";
    $dv = trim(fgets(STDIN));
    if (!empty($dv)) $request['Dv'] = $dv;

    echo "PASAPORTE (ej: US123456789 o vacío): ";
    $pasaporte = trim(fgets(STDIN));
    if (!empty($pasaporte)) $request['PASAPORTE'] = $pasaporte;

    echo "TIPO_RECEPTOR (01/02/03/04 o vacío para auto): ";
    $tipoReceptor = trim(fgets(STDIN));
    if (!empty($tipoReceptor)) $request['TIPO_RECEPTOR'] = $tipoReceptor;

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🧪 RESULTADO DE LA VALIDACIÓN:\n";

    $result = simulateCustomerRefValidation($request);

    echo "  📊 Datos procesados:\n";
    echo "    - RUC: " . ($result['ruc'] ?? 'null') . "\n";
    echo "    - DV: " . ($result['dv'] ?? 'null') . "\n";
    echo "    - TIPO_RECEPTOR: " . $result['tipoReceptor'] . "\n";
    echo "    - PASAPORTE: " . ($result['pasaporte'] ?? 'null') . "\n";
    echo "    - CustomerID final: " . $result['customerID'] . "\n";
    echo "    - Custom_field1 (Identificador): " . ($result['custom_field1'] ?? 'null') . "\n";
    echo "    - Custom_field2 (DV): " . ($result['custom_field2'] ?? 'null') . "\n";

    echo "\n  🏷️  Interpretación:\n";
    switch ($result['tipoReceptor']) {
        case '01':
            echo "    - Cliente: Contribuyente (con RUC)\n";
            break;
        case '02':
            echo "    - Cliente: Consumidor final\n";
            break;
        case '03':
            echo "    - Cliente: Gobierno\n";
            break;
        case '04':
            echo "    - Cliente: Extranjero (con PASAPORTE)\n";
            break;
    }
}

// Ejecutar script
if ($argc > 1 && $argv[1] === 'interactive') {
    interactiveTest();
} else {
    runTests();
}

echo "\n📚 Para más información, consultar:\n";
echo "  - Documentación: docs/technical/quickbooks-pasaporte-implementation.md\n";
echo "  - Código fuente: app/Services/QuickBooksOnlineService.php\n";
echo "  - Modo interactivo: php " . $argv[0] . " interactive\n";
