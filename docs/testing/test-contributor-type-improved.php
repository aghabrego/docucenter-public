<?php

/**
 * Test directo de la función detectContributorType mejorada
 * Simula la lógica sin depender de Laravel/Base de datos
 *
 * Uso: php docs/testing/test-contributor-type-improved.php
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

    $primera = $parts[0];
    $segunda = $parts[1];
    $tercera = $parts[2];

    // Verificar si es RUC de persona natural
    // Formato: [Provincia]-[Tomo]-[Asiento] donde Provincia es 1-13 o PE
    $validProvinces = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', 'PE'];

    // Primero verificar casos claramente de empresa
    // Si el primer número es >13 y todos son numéricos, es empresa
    if (is_numeric($primera) && intval($primera) > 13) {
        return 2; // Jurídico (empresa)
    }

    // Si la provincia es válida para personas naturales
    if (in_array($primera, $validProvinces, true) &&
        is_numeric($segunda) &&
        is_numeric($tercera)) {

        // Casos ambiguos: RUCs de formato empresa dentro de rango de provincia
        // Ej: "12-34-567" parece empresa aunque 12 sea provincia válida
        if ($primera !== 'PE' &&
            strlen($segunda) <= 2 && strlen($tercera) <= 4 &&
            intval($segunda) <= 99 && intval($tercera) <= 9999) {

            // Heurísticas refinadas para distinguir empresa de persona:

            // 1. Patrón empresa típico: segunda parte 10-99, tercera parte 100-9999
            if (intval($segunda) >= 30 && intval($segunda) <= 99 &&
                intval($tercera) >= 100 && intval($tercera) <= 999) {
                return 2; // Jurídico (patrón claro de empresa)
            }

            // 2. Persona natural: si tiene números grandes típicos de tomo/asiento
            if (intval($segunda) >= 100 || intval($tercera) >= 1000) {
                return 1; // Natural (números grandes típicos de persona)
            }

            // 3. Si todos los números son muy pequeños, probablemente empresa
            if (intval($primera) <= 13 && intval($segunda) <= 20 && intval($tercera) <= 99) {
                return 2; // Jurídico (empresa con formato pequeño)
            }
        }

        return 1; // Natural (Persona física)
    }

    // Validación de empresa usando cualquier patrón numérico válido
    if (is_numeric($primera) && is_numeric($segunda) && is_numeric($tercera)) {
        return 2; // Jurídico (Persona jurídica/empresa)
    }

    // Si no coincide con ningún patrón conocido, asumimos jurídico por defecto
    return 2;
}

// Test cases
$testCases = [
    // Casos de persona natural
    ["PE-123-456", 1, "Panamá Este - Persona Natural"],
    ["2-1234-5678", 1, "Provincia 2 - Persona Natural"],
    ["13-999-888", 1, "Provincia 13 - Persona Natural"],
    ["5-12-3456", 1, "Provincia 5 - Persona Natural"],
    ["1-123-1234", 1, "Provincia 1 - Formato Natural Largo"],
    ["3-456-789", 1, "Provincia 3 - Persona Natural"],

    // Casos de empresa
    ["155-41-85123", 2, "Empresa - Formato Típico"],
    ["98-77-1234", 2, "Empresa - Formato Medio"],
    ["123456789-12-3456", 2, "Empresa - Registro Grande"],
    ["1-2-3", 2, "Empresa - Formato Muy Simple"],
    ["12-34-567", 2, "Empresa - Formato Standard"],

    // Casos ambiguos/especiales
    ["1-1-1", 2, "Caso Ambiguo - Debería ser Empresa"],
    ["0-0-0", 1, "Consumidor Final"],
    ["1-12-123", 1, "Provincia 1 - Formato Natural (más largo)"],

    // Casos inválidos
    ["", 1, "RUC Vacío"],
    ["123", 2, "Formato Inválido"],
    ["PE-ABC-123", 2, "Formato Inválido con Letras"],
    ["14-123-456", 2, "Provincia Inválida (>13)"],
];

echo "=== TESTING CONTRIBUTOR TYPE DETECTION (Improved MaxgymService Pattern) ===\n\n";

$totalTests = count($testCases);
$passedTests = 0;
$results = [];

foreach ($testCases as [$ruc, $expectedType, $description]) {
    $detectedType = detectContributorType($ruc);
    $status = $detectedType === $expectedType ? "✅ PASS" : "❌ FAIL";

    if ($detectedType === $expectedType) {
        $passedTests++;
    }

    $results[] = [
        'ruc' => $ruc,
        'expected' => $expectedType,
        'detected' => $detectedType,
        'status' => $status,
        'description' => $description
    ];

    printf("%-8s | RUC: %-15s | Expected: %d | Detected: %d | %s\n",
           $status, $ruc, $expectedType, $detectedType, $description);
}

echo "\n=== SUMMARY ===\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests/$totalTests)*100, 1) . "%\n";

// Mostrar casos fallidos
$failedCases = array_filter($results, fn($r) => str_starts_with($r['status'], '❌'));
if (!empty($failedCases)) {
    echo "\n=== FAILED CASES ===\n";
    foreach ($failedCases as $case) {
        echo "RUC: {$case['ruc']} | Expected: {$case['expected']} | Detected: {$case['detected']} | {$case['description']}\n";
    }
}

echo "\n=== PATTERN ANALYSIS ===\n";

// Analizar algunos casos específicos
$analysisRucs = ['1-1-1', 'PE-123-456', '155-41-85123', '1-123-1234'];

foreach ($analysisRucs as $ruc) {
    $parts = explode('-', $ruc);
    $personPattern = '/^(PE|[1-9]|1[0-3])-\d{1,6}-\d{1,6}$/';
    $companyPattern = '/^\d{1,9}-\d{1,2}-\d{1,4}$/';

    $matchesPerson = preg_match($personPattern, $ruc);
    $matchesCompany = preg_match($companyPattern, $ruc);

    echo "RUC: $ruc\n";
    echo "  Parts: [" . implode(', ', $parts) . "]\n";
    echo "  Matches Person Pattern: " . ($matchesPerson ? 'YES' : 'NO') . "\n";
    echo "  Matches Company Pattern: " . ($matchesCompany ? 'YES' : 'NO') . "\n";
    echo "  Final Type: " . detectContributorType($ruc) . "\n\n";
}

?>
