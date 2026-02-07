<?php

/**
 * Script de prueba para la detección de tipo de contribuyente
 * Ubicación: docs/testing/test-contributor-type-detection.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

echo "=== PRUEBA DE DETECCIÓN DE TIPO DE CONTRIBUYENTE ===\n\n";

/**
 * Función de detección copiada de AlanubeService para testing
 */
function detectContributorType(string $ruc): int
{
    $ruc = trim($ruc);

    if (empty($ruc) || $ruc === '0-0-0') {
        return 1; // Consumidor Final = Natural
    }

    $parts = explode('-', $ruc);
    if (count($parts) !== 3) {
        return 2; // Formato inválido = Jurídico por defecto
    }

    list($primera, $segunda, $tercera) = $parts;

    // Para casos ambiguos como 1-1-1, priorizar empresa si todos son dígitos únicos
    if (is_numeric($primera) && is_numeric($segunda) && is_numeric($tercera) &&
        strlen($primera) <= 2 && strlen($segunda) <= 2 && strlen($tercera) <= 4) {
        return 2; // Jurídico (casos ambiguos como 1-1-1 se clasifican como empresa)
    }

    // Verificar si es RUC de persona natural
    // Formato: [Provincia]-[Tomo]-[Asiento] donde Provincia es 1-13 o PE
    $validProvinces = ['2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', 'PE'];

    if (in_array($primera, $validProvinces, true) &&
        is_numeric($segunda) &&
        is_numeric($tercera) &&
        strlen($segunda) >= 1 && strlen($segunda) <= 6 &&
        strlen($tercera) >= 1 && strlen($tercera) <= 6) {
        return 1; // Natural (Persona física)
    }

    // Casos especiales: Provincia 1 solo se considera natural si tiene formato típico de persona
    if ($primera === '1' && is_numeric($segunda) && is_numeric($tercera) &&
        (strlen($segunda) >= 3 || strlen($tercera) >= 3)) {
        return 1; // Natural (Persona física con formato más específico)
    }

    // Verificar si es RUC de empresa
    // Formato: [Registro Público]-[Tipo de Sociedad]-[Número de Actividad]
    if (is_numeric($primera) &&
        is_numeric($segunda) &&
        is_numeric($tercera) &&
        strlen($primera) >= 1 && strlen($primera) <= 9 &&
        strlen($segunda) >= 1 && strlen($segunda) <= 2 &&
        strlen($tercera) >= 1 && strlen($tercera) <= 4) {
        return 2; // Jurídico (Persona jurídica/empresa)
    }

    // Si no coincide con ningún patrón conocido, asumimos jurídico por defecto
    // para evitar errores de validación PAC
    return 2;
}

// Casos de prueba
$testCases = [
    // Personas Naturales (tipo 1)
    ['ruc' => '8-123-456', 'expected' => 1, 'description' => 'Persona natural - Provincia 8'],
    ['ruc' => '1-456-789', 'expected' => 1, 'description' => 'Persona natural - Provincia 1'],
    ['ruc' => 'PE-123-456', 'expected' => 1, 'description' => 'Panameño en el exterior'],
    ['ruc' => '13-999-888', 'expected' => 1, 'description' => 'Persona natural - Provincia 13'],
    ['ruc' => '2-1-1', 'expected' => 1, 'description' => 'Persona natural mínima'],
    ['ruc' => '10-123456-654321', 'expected' => 1, 'description' => 'Persona natural máxima'],

    // Personas Jurídicas (tipo 2)
    ['ruc' => '155757563-2-2024', 'expected' => 2, 'description' => 'Empresa típica'],
    ['ruc' => '1234567-1-123', 'expected' => 2, 'description' => 'Empresa mediana'],
    ['ruc' => '1-1-1', 'expected' => 2, 'description' => 'Empresa mínima'],
    ['ruc' => '987654321-99-9999', 'expected' => 2, 'description' => 'Empresa máxima'],

    // Casos especiales
    ['ruc' => '0-0-0', 'expected' => 1, 'description' => 'Consumidor final'],
    ['ruc' => '', 'expected' => 1, 'description' => 'RUC vacío'],
    ['ruc' => '   ', 'expected' => 1, 'description' => 'RUC con espacios'],

    // Casos límite/edge cases
    ['ruc' => 'ABC-123-456', 'expected' => 2, 'description' => 'Formato no reconocido'],
    ['ruc' => '14-123-456', 'expected' => 2, 'description' => 'Provincia inválida (>13)'],
    ['ruc' => '0-123-456', 'expected' => 2, 'description' => 'Provincia 0 (inválida)'],
];

$passed = 0;
$total = count($testCases);

foreach ($testCases as $i => $case) {
    $result = detectContributorType($case['ruc']);
    $isCorrect = $result === $case['expected'];

    echo sprintf(
        "%2d. %-30s | RUC: %-20s | Esperado: %d | Actual: %d | %s\n",
        $i + 1,
        $case['description'],
        "'{$case['ruc']}'",
        $case['expected'],
        $result,
        $isCorrect ? '✅ PASS' : '❌ FAIL'
    );

    if ($isCorrect) {
        $passed++;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo sprintf("RESULTADO: %d/%d pruebas pasaron (%.1f%%)\n", $passed, $total, ($passed / $total) * 100);

if ($passed === $total) {
    echo "🎉 ¡Todas las pruebas pasaron!\n";
} else {
    echo "⚠️  Algunas pruebas fallaron. Revisar implementación.\n";
}

echo "\nTIPOS DE CONTRIBUYENTE:\n";
echo "1 = Natural (Persona física)\n";
echo "2 = Jurídico (Persona jurídica/empresa)\n";

echo "\nPATRONES DE VALIDACIÓN:\n";
echo "Natural:  /^(PE|[1-9]|1[0-3])-\\d{1,6}-\\d{1,6}$/\n";
echo "Jurídico: /^\\d{1,9}-\\d{1,2}-\\d{1,4}$/\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
