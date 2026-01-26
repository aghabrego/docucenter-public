<?php
/**
 * TEST: Extracción de TIPO desde PrimaryTaxIdentifier
 *
 * PROPÓSITO:
 * - Extraer TIPO:01 o TIPO:02 desde el PrimaryTaxIdentifier de QuickBooks
 * - Convertir formato 01/02 a 1/2 para nuestro sistema
 * - Manejar casos edge y validaciones
 *
 * FORMATO DE ENTRADA:
 * "XXXX155706268-2-2021 DV:07 TIPO:02 TIPO_RECEPTOR:01"
 *
 * CASOS DE PRUEBA:
 * - TIPO:01 → 1
 * - TIPO:02 → 2
 * - Sin TIPO → null
 * - TIPO inválido → null
 */

require_once __DIR__ . '/../../vendor/autoload.php';

function extractTipoFromPrimaryTaxIdentifier(string $primaryTaxIdentifier): ?string
{
    // Patrón para extraer TIPO:XX
    if (preg_match('/TIPO:(\d{2})/', $primaryTaxIdentifier, $matches)) {
        $tipoValue = $matches[1];

        // Convertir formato 01/02 a 1/2
        switch ($tipoValue) {
            case '01':
                return '1';
            case '02':
                return '2';
            default:
                return null; // TIPO no válido
        }
    }

    return null; // No se encontró TIPO
}

echo "🧪 TEST: Extracción TIPO desde PrimaryTaxIdentifier\n";
echo str_repeat("=", 60) . "\n\n";

// Casos de prueba
$testCases = [
    [
        'description' => 'TIPO:01 (Persona Jurídica)',
        'input' => 'XXXX155706268-2-2021 DV:07 TIPO:01 TIPO_RECEPTOR:01',
        'expected' => '1'
    ],
    [
        'description' => 'TIPO:02 (Persona Natural)',
        'input' => 'XXXX155706268-2-2021 DV:07 TIPO:02 TIPO_RECEPTOR:01',
        'expected' => '2'
    ],
    [
        'description' => 'Sin TIPO en la cadena',
        'input' => 'XXXX155706268-2-2021 DV:07 TIPO_RECEPTOR:01',
        'expected' => null
    ],
    [
        'description' => 'TIPO con valor inválido',
        'input' => 'XXXX155706268-2-2021 DV:07 TIPO:99 TIPO_RECEPTOR:01',
        'expected' => null
    ],
    [
        'description' => 'Múltiples TIPOs (tomar el primero)',
        'input' => 'XXXX155706268-2-2021 DV:07 TIPO:01 TIPO_RECEPTOR:02',
        'expected' => '1'
    ],
    [
        'description' => 'TIPO con formato diferente',
        'input' => 'RUC:155706268-2-2021 DV:07 TIPO:02',
        'expected' => '2'
    ],
    [
        'description' => 'Cadena vacía',
        'input' => '',
        'expected' => null
    ]
];

$totalTests = count($testCases);
$passedTests = 0;

foreach ($testCases as $index => $testCase) {
    echo "Test " . ($index + 1) . ": {$testCase['description']}\n";
    echo "Input: '{$testCase['input']}'\n";

    $result = extractTipoFromPrimaryTaxIdentifier($testCase['input']);

    echo "Resultado: " . ($result === null ? 'null' : "'$result'") . "\n";
    echo "Esperado: " . ($testCase['expected'] === null ? 'null' : "'{$testCase['expected']}'") . "\n";

    if ($result === $testCase['expected']) {
        echo "✅ PASSED\n";
        $passedTests++;
    } else {
        echo "❌ FAILED\n";
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "RESUMEN: $passedTests/$totalTests tests pasaron\n";

if ($passedTests === $totalTests) {
    echo "🎉 ¡Todos los tests pasaron! Función lista para implementar.\n";
} else {
    echo "⚠️ Algunos tests fallaron. Revisar implementación.\n";
}
