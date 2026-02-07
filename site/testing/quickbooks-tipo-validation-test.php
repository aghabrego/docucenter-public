<?php

/**
 * Test Completo para Validación de TIPO en QuickBooks
 *
 * Este test valida que el sistema acepta todos los formatos de TIPO:
 * - Enteros: 1, 2
 * - Strings: '1', '2', '01', '02'
 *
 * Ubicación: docs/testing/quickbooks-tipo-validation-test.php
 * Uso: Ejecutar desde terminal Docker con php artisan tinker
 */

use App\Services\QuickBooksOnlineService;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class QuickBooksTipoValidationTest
{
    private $qbService;
    private $organization;

    public function __construct()
    {
        $this->organization = Organization::first();

        // Obtener conexión QuickBooks de la organización
        $connection = $this->organization->quickbooksConnection ?? null;
        $this->qbService = new QuickBooksOnlineService($connection);

        echo "=== Test de Validación TIPO en QuickBooks ===\n";
        echo "Organización: {$this->organization->name}\n";
        echo "Conexión QB: " . ($connection ? 'Configurada' : 'No configurada') . "\n\n";
    }

    /**
     * Ejecuta todos los tests de validación TIPO
     */
    public function runAllTests()
    {
        $testCases = [
            ['tipo' => 1, 'expected' => '1', 'description' => 'Entero 1 (Persona Natural)'],
            ['tipo' => 2, 'expected' => '2', 'description' => 'Entero 2 (Persona Jurídica)'],
            ['tipo' => '1', 'expected' => '1', 'description' => 'String "1" (Persona Natural)'],
            ['tipo' => '2', 'expected' => '2', 'description' => 'String "2" (Persona Jurídica)'],
            ['tipo' => '01', 'expected' => '1', 'description' => 'String "01" (Persona Natural)'],
            ['tipo' => '02', 'expected' => '2', 'description' => 'String "02" (Persona Jurídica)'],
        ];

        $successCount = 0;
        $totalTests = count($testCases);

        foreach ($testCases as $test) {
            if ($this->testTipoFormat($test['tipo'], $test['expected'], $test['description'])) {
                $successCount++;
            }
        }

        echo "\n=== RESUMEN DE TESTS ===\n";
        echo "✅ Exitosos: {$successCount}/{$totalTests}\n";
        echo "❌ Fallidos: " . ($totalTests - $successCount) . "/{$totalTests}\n";

        if ($successCount === $totalTests) {
            echo "🎉 ¡TODOS LOS TESTS PASARON! El sistema acepta todos los formatos TIPO.\n";
        } else {
            echo "⚠️  Algunos tests fallaron. Revisar implementación.\n";
        }

        return $successCount === $totalTests;
    }

    /**
     * Test individual para un formato TIPO específico
     */
    public function testTipoFormat($tipo, $expectedResult, $description)
    {
        echo "Test: {$description}\n";
        echo "  Input TIPO: " . var_export($tipo, true) . "\n";

        try {
            // Simular request con TIPO específico
            $request = [
                'CompanyName' => 'Test Company',
                'PrimaryEmailAddr' => ['Address' => 'test@example.com'],
                'PrimaryPhone' => ['FreeFormNumber' => '507-1234-5678'],
                'BillAddr' => [
                    'Line1' => 'Test Address',
                    'City' => 'Panama',
                    'Country' => 'Panama'
                ],
                'PrimaryTaxIdentifier' => ['Value' => "TIPO:{$tipo}"],
                'Active' => true
            ];

            // Crear cliente usando el servicio
            $customer = $this->qbService->createDefaultClient($this->organization, $request);

            if ($customer) {
                $actualTipo = $customer->custom_field4;
                echo "  Output tipoContribuyente: {$actualTipo}\n";

                if ($actualTipo === $expectedResult) {
                    echo "  ✅ ÉXITO: TIPO mapeado correctamente\n\n";
                    return true;
                } else {
                    echo "  ❌ ERROR: Esperado '{$expectedResult}', obtenido '{$actualTipo}'\n\n";
                    return false;
                }
            } else {
                echo "  ❌ ERROR: No se pudo crear el cliente\n\n";
                return false;
            }

        } catch (\Exception $e) {
            echo "  ❌ EXCEPCIÓN: {$e->getMessage()}\n\n";
            return false;
        }
    }

    /**
     * Test específico para el caso reportado: TIPO:"02"
     */
    public function testReportedCase()
    {
        echo "=== TEST CASO ESPECÍFICO REPORTADO ===\n";
        echo "Testando TIPO:\"02\" que causaba error de validación\n\n";

        $request = [
            'CompanyName' => 'Empresa Test TIPO 02',
            'PrimaryEmailAddr' => ['Address' => 'tipo02@test.com'],
            'PrimaryPhone' => ['FreeFormNumber' => '507-9999-0002'],
            'BillAddr' => [
                'Line1' => 'Calle 50, Edificio Test',
                'City' => 'Ciudad de Panama',
                'Country' => 'Panama'
            ],
            'PrimaryTaxIdentifier' => ['Value' => 'TIPO:02'],
            'Active' => true
        ];

        try {
            $customer = $this->qbService->createDefaultClient($this->organization, $request);

            if ($customer && $customer->custom_field4 === '2') {
                echo "✅ CASO REPORTADO RESUELTO: TIPO:\"02\" ahora se acepta correctamente\n";
                echo "   Cliente creado con tipoContribuyente = '2' (Persona Jurídica)\n\n";
                return true;
            } else {
                echo "❌ CASO REPORTADO AÚN FALLA: TIPO:\"02\" no se procesa correctamente\n\n";
                return false;
            }

        } catch (\Exception $e) {
            echo "❌ CASO REPORTADO FALLA CON EXCEPCIÓN: {$e->getMessage()}\n\n";
            return false;
        }
    }

    /**
     * Test de compatibilidad hacia atrás
     */
    public function testBackwardCompatibility()
    {
        echo "=== TEST COMPATIBILIDAD HACIA ATRÁS ===\n";
        echo "Verificando que formatos anteriores siguen funcionando\n\n";

        $legacyTests = [
            ['tipo' => null, 'description' => 'Sin TIPO (lógica legacy)'],
            ['tipo' => '', 'description' => 'TIPO vacío (lógica legacy)'],
        ];

        foreach ($legacyTests as $test) {
            echo "Test: {$test['description']}\n";

            $request = [
                'CompanyName' => 'Test Legacy Company',
                'PrimaryEmailAddr' => ['Address' => 'legacy@test.com'],
                'PrimaryTaxIdentifier' => $test['tipo'] ? ['Value' => "TIPO:{$test['tipo']}"] : null,
                'Active' => true
            ];

            try {
                $customer = $this->qbService->createDefaultClient($this->organization, $request);

                if ($customer) {
                    echo "  ✅ Cliente creado exitosamente con lógica legacy\n";
                    echo "  tipoContribuyente: {$customer->custom_field4}\n\n";
                } else {
                    echo "  ❌ Error creando cliente legacy\n\n";
                }

            } catch (\Exception $e) {
                echo "  ❌ Excepción en test legacy: {$e->getMessage()}\n\n";
            }
        }
    }
}

// Función para ejecutar el test desde tinker
function runQuickBooksTipoValidationTest()
{
    $test = new QuickBooksTipoValidationTest();

    echo "Ejecutando test del caso específico reportado...\n";
    $test->testReportedCase();

    echo "Ejecutando tests completos de todos los formatos...\n";
    $allPassed = $test->runAllTests();

    echo "Ejecutando tests de compatibilidad hacia atrás...\n";
    $test->testBackwardCompatibility();

    return $allPassed;
}

echo "Test script cargado exitosamente.\n";
echo "Para ejecutar:\n";
echo "1. php artisan tinker\n";
echo "2. include 'docs/testing/quickbooks-tipo-validation-test.php';\n";
echo "3. runQuickBooksTipoValidationTest();\n";
