<?php

/**
 * Test simplificado para validar la normalización de prefijos FE/NC
 *
 * Este test valida que la función normalizeDocumentNumber() funcione correctamente
 * sin necesidad de base de datos, solo probando la lógica de regex.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Http\Controllers\V1\FeController;

class PrefixNormalizationTest
{
    private $controller;

    public function __construct()
    {
        $this->controller = new FeController();
        echo "🧪 Test: Normalización de Prefijos QuickBooks (FE/NC)\n";
        echo "=" . str_repeat("=", 55) . "\n\n";
    }

    public function run()
    {
        echo "📋 CASOS DE PRUEBA:\n";
        echo "-" . str_repeat("-", 30) . "\n";

        $testCases = [
            // Casos con prefijo FE
            ['input' => 'FE0000003636', 'expected' => '0000003636', 'description' => 'Prefijo FE estándar'],
            ['input' => 'FE123456789', 'expected' => '123456789', 'description' => 'Prefijo FE con otro número'],
            ['input' => 'FE0001', 'expected' => '0001', 'description' => 'Prefijo FE número corto'],

            // Casos con prefijo NC
            ['input' => 'NC0000003670', 'expected' => '0000003670', 'description' => 'Prefijo NC estándar'],
            ['input' => 'NC987654321', 'expected' => '987654321', 'description' => 'Prefijo NC con otro número'],

            // Casos sin prefijo (control)
            ['input' => '0000003636', 'expected' => '0000003636', 'description' => 'Sin prefijo - número directo'],
            ['input' => '123456', 'expected' => '123456', 'description' => 'Sin prefijo - número simple'],
            ['input' => 'INV-001', 'expected' => 'INV-001', 'description' => 'Sin prefijo - formato custom'],

            // Casos edge
            ['input' => 'FE', 'expected' => 'FE', 'description' => 'Solo prefijo FE sin número'],
            ['input' => 'NC', 'expected' => 'NC', 'description' => 'Solo prefijo NC sin número'],
            ['input' => '', 'expected' => '', 'description' => 'String vacío'],
            ['input' => '   FE0000003636   ', 'expected' => '0000003636', 'description' => 'Con espacios - prefijo FE'],

            // Casos que NO deberían ser modificados
            ['input' => 'FELIZ123', 'expected' => 'FELIZ123', 'description' => 'FE en medio de palabra'],
            ['input' => 'NCSOFT456', 'expected' => 'NCSOFT456', 'description' => 'NC en medio de palabra'],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($testCases as $index => $test) {
            $testNumber = $index + 1;
            echo "\n🔍 Caso {$testNumber}: {$test['description']}\n";
            echo "   Input: '{$test['input']}'\n";
            echo "   Expected: '{$test['expected']}'\n";

            try {
                $result = $this->callNormalizeDocumentNumber($test['input']);
                echo "   Resultado: '{$result}'\n";

                if ($result === $test['expected']) {
                    echo "   ✅ ÉXITO\n";
                    $passed++;
                } else {
                    echo "   ❌ FALLO - Esperado: '{$test['expected']}', Obtenido: '{$result}'\n";
                    $failed++;
                }
            } catch (\Exception $e) {
                echo "   ❌ ERROR: " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        // Resumen
        echo "\n" . str_repeat("=", 55) . "\n";
        echo "📊 RESUMEN DE PRUEBAS:\n";
        echo "   ✅ Exitosas: {$passed}\n";
        echo "   ❌ Fallidas: {$failed}\n";
        echo "   📋 Total: " . ($passed + $failed) . "\n";

        if ($failed === 0) {
            echo "\n🎉 ¡TODAS LAS PRUEBAS PASARON!\n";
            echo "✨ La función normalizeDocumentNumber() funciona correctamente\n";
        } else {
            echo "\n⚠️  HAY PRUEBAS FALLIDAS\n";
            echo "🔧 Revisar implementación de normalizeDocumentNumber()\n";
        }

        echo "\n" . str_repeat("=", 55) . "\n";
    }

    /**
     * Usar reflection para acceder al método privado normalizeDocumentNumber
     */
    private function callNormalizeDocumentNumber($input)
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('normalizeDocumentNumber');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $input);
    }
}

// Ejecutar las pruebas
echo "🚀 INICIANDO TEST DE NORMALIZACIÓN DE PREFIJOS\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$test = new PrefixNormalizationTest();
$test->run();

echo "\n🏁 TEST FINALIZADO\n";
