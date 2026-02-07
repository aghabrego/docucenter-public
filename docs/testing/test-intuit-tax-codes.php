<?php
/**
 * Script de prueba para validar el cálculo de códigos de impuesto en UpdateIntuitOrdersJob
 */

require_once 'vendor/autoload.php';

use App\Jobs\Intuit\UpdateIntuitOrdersJob;
use App\Models\Connection;

echo "🧮 PRUEBA CÁLCULO DE CÓDIGOS DE IMPUESTO - UpdateIntuitOrdersJob\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Crear una instancia mock del job para testing
    $mockConnection = new Connection();
    $job = new class($mockConnection) extends UpdateIntuitOrdersJob {
        // Hacer público el método para testing
        public function testDetermineTaxCode(float $taxAmount, float $subtotal): string {
            return $this->determineTaxCode($taxAmount, $subtotal);
        }
    };

    echo "✅ Job inicializado para testing\n\n";

    // Casos de prueba
    $testCases = [
        // ITBMS 7%
        ['tax' => 7.00, 'subtotal' => 100.00, 'expected' => 'ITBMS7', 'description' => 'ITBMS 7% exacto'],
        ['tax' => 3.50, 'subtotal' => 50.00, 'expected' => 'ITBMS7', 'description' => 'ITBMS 7% con $50'],
        ['tax' => 6.90, 'subtotal' => 98.57, 'expected' => 'ITBMS7', 'description' => 'ITBMS 7% con redondeo'],

        // ITBMS 10%
        ['tax' => 10.00, 'subtotal' => 100.00, 'expected' => 'ITBMS10', 'description' => 'ITBMS 10% exacto'],
        ['tax' => 5.00, 'subtotal' => 50.00, 'expected' => 'ITBMS10', 'description' => 'ITBMS 10% con $50'],

        // ITBMS 15%
        ['tax' => 15.00, 'subtotal' => 100.00, 'expected' => 'ITBMS15', 'description' => 'ITBMS 15% exacto'],
        ['tax' => 7.50, 'subtotal' => 50.00, 'expected' => 'ITBMS15', 'description' => 'ITBMS 15% con $50'],

        // ITBMS 0%
        ['tax' => 0.00, 'subtotal' => 100.00, 'expected' => 'ITBMS0', 'description' => 'Sin impuesto'],
        ['tax' => 0.25, 'subtotal' => 100.00, 'expected' => 'ITBMS0', 'description' => 'Impuesto mínimo (~0%)'],

        // Casos edge
        ['tax' => 0.00, 'subtotal' => 0.00, 'expected' => 'ITBMS0', 'description' => 'Subtotal cero'],
        ['tax' => -1.00, 'subtotal' => 100.00, 'expected' => 'ITBMS0', 'description' => 'Impuesto negativo'],
        ['tax' => 8.50, 'subtotal' => 100.00, 'expected' => 'ITBMS10', 'description' => 'Tasa 8.5% (fallback a 10%)'],
        ['tax' => 20.00, 'subtotal' => 100.00, 'expected' => 'ITBMS15', 'description' => 'Tasa 20% (fallback a 15%)'],

        // Casos reales QB
        ['tax' => 7.00, 'subtotal' => 100.00, 'expected' => 'ITBMS7', 'description' => 'Caso real: PUBLICIDAD EN PANTALLAS'],
        ['tax' => 1.40, 'subtotal' => 20.00, 'expected' => 'ITBMS7', 'description' => 'Caso real: Producto básico'],
        ['tax' => 15.75, 'subtotal' => 105.00, 'expected' => 'ITBMS15', 'description' => 'Caso real: Servicio especializado'],
    ];

    echo "🎯 EJECUTANDO CASOS DE PRUEBA:\n";
    echo "─────────────────────────────────\n\n";

    $passed = 0;
    $failed = 0;

    foreach ($testCases as $index => $case) {
        $result = $job->testDetermineTaxCode($case['tax'], $case['subtotal']);
        $taxRate = $case['subtotal'] > 0 ? round(($case['tax'] / $case['subtotal']) * 100, 2) : 0;

        echo "📋 Caso " . ($index + 1) . ": {$case['description']}\n";
        echo "   • Impuesto: $" . number_format($case['tax'], 2) . "\n";
        echo "   • Subtotal: $" . number_format($case['subtotal'], 2) . "\n";
        echo "   • Tasa calculada: {$taxRate}%\n";
        echo "   • Código esperado: {$case['expected']}\n";
        echo "   • Código obtenido: {$result}\n";

        if ($result === $case['expected']) {
            echo "   • Estado: ✅ CORRECTO\n";
            $passed++;
        } else {
            echo "   • Estado: ❌ INCORRECTO\n";
            $failed++;
        }
        echo "\n";
    }

    echo "📊 RESUMEN DE RESULTADOS:\n";
    echo "─────────────────────────\n";
    echo "✅ Casos exitosos: {$passed}\n";
    echo "❌ Casos fallidos: {$failed}\n";
    echo "📈 Tasa de éxito: " . round(($passed / count($testCases)) * 100, 1) . "%\n\n";

    if ($failed === 0) {
        echo "🎉 ¡TODAS LAS PRUEBAS PASARON!\n";
        echo "La función determineTaxCode está funcionando correctamente.\n";
    } else {
        echo "⚠️ Algunas pruebas fallaron. Revisar lógica de determineTaxCode.\n";
    }

    echo "\n🔧 RECOMENDACIONES DE USO:\n";
    echo "────────────────────────────\n";
    echo "• La función maneja automáticamente typos como 'ITMBS7' → 'ITBMS7'\n";
    echo "• Usa tolerancia de ±0.5% para determinar la tasa correcta\n";
    echo "• Fallback inteligente para tasas no estándar\n";
    echo "• Logging automático para tasas no reconocidas\n";
    echo "• Manejo seguro de casos edge (impuestos negativos, subtotal cero)\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎯 Prueba completada\n";
