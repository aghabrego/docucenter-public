<?php

/**
 * Test del PanamaRucHelper usando patrones oficiales
 * Basado en la funcionalidad JavaScript probada
 *
 * Uso: php docs/testing/test-panama-ruc-helper.php
 */

require_once '/home/weirdolabs/code/docucenter/app/Helpers/PanamaRucHelper.php';

use App\Helpers\PanamaRucHelper;

// Test cases completos
$testCases = [
    // === PERSONAS NATURALES ===
    // Formato regular (provincia-libro-tomo)
    ["1-123-1234", 1, "Bocas del Toro - Regular"],
    ["8-4567-89012", 1, "Panamá - Regular"],
    ["13-999-888", 1, "Panamá Oeste - Regular"],

    // Panameño nacido en el extranjero
    ["PE-123-456", 1, "Panameño Extranjero"],
    ["PE-1234-12345", 1, "Panameño Extranjero - Largo"],

    // Extranjero con cédula
    ["E-123-456", 1, "Extranjero"],
    ["E-1234-12345", 1, "Extranjero - Largo"],

    // Naturalizado
    ["N-123-456", 1, "Naturalizado"],
    ["N-1234-12345", 1, "Naturalizado - Largo"],

    // Antes de la vigencia
    ["1AV-123-456", 1, "Bocas del Toro - Antes Vigencia"],
    ["8AV-1234-12345", 1, "Panamá - Antes Vigencia"],

    // Población indígena
    ["1PI-123-456", 1, "Bocas del Toro - Población Indígena"],
    ["12PI-1234-12345", 1, "Ngäbe-Buglé - Población Indígena"],

    // === EMPRESAS/JURÍDICOS ===
    ["155-41-85123", 2, "Empresa - Formato Típico"],
    ["123456789-12-3456", 2, "Empresa - Registro Grande"],
    ["98-77-1234", 2, "Empresa - Formato Medio"],
    ["1-2-3", 2, "Empresa - Formato Simple"],
    ["12-34-567", 2, "Empresa - Caso Problemático"],

    // === CASOS ESPECIALES ===
    ["0-0-0", 1, "Consumidor Final"],
    ["", 1, "RUC Vacío"],

    // === CASOS INVÁLIDOS ===
    ["123", 2, "Formato Inválido - Sin guiones"],
    ["PE-ABC-123", 2, "Formato Inválido - Letras en libro"],
    ["14-123-456", 2, "Provincia Inválida"],
    ["XY-123-456", 2, "Tipo Inválido"],
];

echo "=== TESTING PANAMA RUC HELPER (Patrones Oficiales) ===\n\n";

$totalTests = count($testCases);
$passedTests = 0;
$results = [];

foreach ($testCases as [$ruc, $expectedType, $description]) {
    $detectedType = PanamaRucHelper::detectContributorType($ruc);
    $analysis = PanamaRucHelper::verifyPersonalID($ruc);

    $status = $detectedType === $expectedType ? "✅ PASS" : "❌ FAIL";

    if ($detectedType === $expectedType) {
        $passedTests++;
    }

    $results[] = [
        'ruc' => $ruc,
        'expected' => $expectedType,
        'detected' => $detectedType,
        'status' => $status,
        'description' => $description,
        'analysis' => $analysis
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
        echo "  Analysis: " . json_encode($case['analysis'], JSON_UNESCAPED_UNICODE) . "\n";
    }
}

echo "\n=== DETAILED ANALYSIS EXAMPLES ===\n";

$detailExamples = ['PE-123-456', '8-4567-89012', '1AV-123-456', '155-41-85123', '12-34-567'];

foreach ($detailExamples as $ruc) {
    $info = PanamaRucHelper::getRucInfo($ruc);
    echo "RUC: $ruc\n";
    echo "  Tipo: {$info['type']} ({$info['typeName']})\n";
    echo "  Válido: " . ($info['isValid'] ? 'Sí' : 'No') . "\n";
    echo "  Completo: " . ($info['isComplete'] ? 'Sí' : 'No') . "\n";

    if (isset($info['provinciaName'])) {
        echo "  Provincia: {$info['provinciaName']}\n";
        echo "  Libro: {$info['libro']}, Tomo: {$info['tomo']}\n";

        if (isset($info['tipo'])) {
            echo "  Tipo Especial: {$info['tipo']}\n";
        }
    }
    echo "\n";
}

?>
