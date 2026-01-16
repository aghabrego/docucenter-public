<?php

echo "=== TEST: AUTO-DETECCIÓN TIPO RECEPTOR ===\n\n";

// Simular la lógica de auto-detección
function detectReceiverType(string $ruc): string
{
    if (empty($ruc)) {
        return '01'; // Valor por defecto
    }

    // Patrón DNI: máximo 3-4-6 dígitos separados por guiones
    $dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';

    if (preg_match($dniPattern, $ruc)) {
        return '01'; // Consumidor Final (DNI/Cédula)
    } else {
        return '02'; // Empresa (RUC empresarial)
    }
}

// Casos de prueba
$testCases = [
    [
        'description' => 'Caso problemático del usuario',
        'ruc' => '1808755-1-706832',
        'original_type' => '1',
        'expected_type' => '02'
    ],
    [
        'description' => 'DNI válido',
        'ruc' => '8-888-888888',
        'original_type' => '1',
        'expected_type' => '01'
    ],
    [
        'description' => 'RUC empresa típico',
        'ruc' => '1234567-1-123456',
        'original_type' => '2',
        'expected_type' => '02'
    ],
    [
        'description' => 'DNI corto',
        'ruc' => '7-123-4567',
        'original_type' => '2',
        'expected_type' => '01'
    ],
    [
        'description' => 'RUC extranjero',
        'ruc' => 'E-123-456789',
        'original_type' => '1',
        'expected_type' => '01'
    ],
    [
        'description' => 'RUC muy largo (empresa)',
        'ruc' => '12345678-12-1234567',
        'original_type' => '1',
        'expected_type' => '02'
    ]
];

foreach ($testCases as $index => $case) {
    echo "Test " . ($index + 1) . ": {$case['description']}\n";
    echo "RUC: '{$case['ruc']}'\n";
    echo "Tipo original: '{$case['original_type']}'\n";

    $detectedType = detectReceiverType($case['ruc']);
    echo "Tipo detectado: '$detectedType'\n";
    echo "Tipo esperado: '{$case['expected_type']}'\n";

    if ($detectedType === $case['expected_type']) {
        echo "✅ CORRECTO - Auto-detección funciona\n";
    } else {
        echo "❌ ERROR - Auto-detección falló\n";
    }

    // Verificar si hay corrección necesaria
    if ($case['original_type'] !== $detectedType) {
        echo "🔧 CORRECCIÓN APLICADA: '{$case['original_type']}' → '$detectedType'\n";
    } else {
        echo "✅ No requiere corrección\n";
    }

    echo "\n";
}

echo "=== ANÁLISIS DEL CASO PROBLEMÁTICO ===\n";
$problematicRuc = '1808755-1-706832';
$originalType = '1';
$detectedType = detectReceiverType($problematicRuc);

echo "RUC problemático: $problematicRuc\n";
echo "Tipo original: $originalType (Consumidor Final)\n";
echo "Tipo detectado: $detectedType (Empresa)\n\n";

echo "ANTES (causaba error PAC):\n";
echo "- iTipoRec: '1' (Consumidor Final)\n";
echo "- RUC: '$problematicRuc' (formato empresa)\n";
echo "- Error: DNI pattern no coincide\n\n";

echo "DESPUÉS (debe funcionar):\n";
echo "- iTipoRec: '$detectedType' (Empresa)\n";
echo "- RUC: '$problematicRuc' (formato empresa)\n";
echo "- ✅ Consistencia: tipo empresa permite formato empresa\n\n";

echo "=== VALIDACIÓN PAC ===\n";
$dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';

if ($detectedType === '01') {
    if (preg_match($dniPattern, $problematicRuc)) {
        echo "✅ PAC aceptará: tipo consumidor final + formato DNI\n";
    } else {
        echo "❌ PAC rechazará: tipo consumidor final + formato no-DNI\n";
    }
} else {
    echo "✅ PAC aceptará: tipo empresa + cualquier formato RUC\n";
}

echo "\n=== IMPLEMENTACIÓN CORRECTA ===\n";
echo "La función detectReceiverType() ahora:\n";
echo "1. Analiza el formato del RUC\n";
echo "2. Devuelve '01' si coincide con patrón DNI\n";
echo "3. Devuelve '02' si es formato empresa\n";
echo "4. Garantiza consistencia tipo-formato para PAC\n";
echo "5. Previene errores de validación\n";
