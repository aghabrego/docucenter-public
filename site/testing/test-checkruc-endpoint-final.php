<?php

/**
 * Test directo del endpoint checkRuc con el nuevo PanamaRucHelper
 *
 * Uso: php docs/testing/test-checkruc-endpoint-final.php
 */

// Simular el comportamiento del endpoint sin Laravel
function simulateCheckRucEndpoint($ruc) {
    // Incluir el helper
    require_once '/home/weirdolabs/code/docucenter/app/Helpers/PanamaRucHelper.php';

    $contributorType = \App\Helpers\PanamaRucHelper::detectContributorType($ruc);
    $rucInfo = \App\Helpers\PanamaRucHelper::getRucInfo($ruc);

    // Simular respuesta del endpoint
    return [
        'success' => true,
        'data' => [
            'ruc' => $ruc,
            'is_valid' => $rucInfo['isValid'],
            'type' => $contributorType,
            'type_name' => $contributorType === 1 ? 'Natural' : 'Jurídico',
            'details' => $rucInfo
        ]
    ];
}

// Test cases del endpoint
$endpointTests = [
    // Casos típicos de API
    '8-123-456',        // Persona natural
    'PE-789-012',       // Panameño extranjero
    '155-41-85123',     // Empresa típica
    '1-2-3',            // Caso ambiguo resuelto
    '12-34-567',        // Caso problemático resuelto
    '0-0-0',            // Consumidor final
    '',                 // RUC vacío
    '123',              // Formato inválido
];

echo "=== TESTING CHECKRUC ENDPOINT WITH PANAMA RUC HELPER ===\n\n";

foreach ($endpointTests as $ruc) {
    $response = simulateCheckRucEndpoint($ruc);

    echo "RUC: " . ($ruc ?: '(vacío)') . "\n";
    echo "  Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    echo "  Type: {$response['data']['type']} ({$response['data']['type_name']})\n";
    echo "  Valid: " . ($response['data']['is_valid'] ? 'true' : 'false') . "\n";

    if (isset($response['data']['details']['provinciaName'])) {
        echo "  Provincia: {$response['data']['details']['provinciaName']}\n";
    }

    if (isset($response['data']['details']['tipo'])) {
        echo "  Tipo Especial: {$response['data']['details']['tipo']}\n";
    }

    echo "\n";
}

echo "=== FORMATO JSON EJEMPLO ===\n";
$sampleResponse = simulateCheckRucEndpoint('PE-123-456');
echo json_encode($sampleResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

?>
