<?php

/**
 * Test del campo contributorType en peticiones de Alanube
 * Verifica que se incluya el campo contributorType en los datos del receptor
 *
 * Uso: php docs/testing/test-alanube-contributor-type.php
 */

// Simular el comportamiento del AlanubeService
class MockAlanubeService
{
    public function detectContributorType(string $ruc): int
    {
        require_once '/home/weirdolabs/code/docucenter/app/Helpers/PanamaRucHelper.php';
        return \App\Helpers\PanamaRucHelper::detectContributorType($ruc);
    }

    public function buildReceiver(array $invoiceData): array
    {
        $receiver = $invoiceData['receiver'] ?? [];

        $receiverData = [
            'type' => $receiver['type'] ?? '01', // RECEIVER_CONTRIBUTOR
            'name' => $receiver['name'] ?? null,
            'address' => $receiver['address'] ?? null,
            'telephones' => $receiver['telephones'] ?? [],
            'emails' => $receiver['emails'] ?? [],
            'country' => $receiver['country'] ?? 'PA'
        ];

        // RUC del receptor (contribuyente)
        if (isset($receiver['ruc'])) {
            $rucData = [
                'type' => $receiver['ruc']['type'] ?? 1,
                'ruc' => $receiver['ruc']['ruc']
            ];

            // Detectar automáticamente el tipo de contribuyente si no se proporciona
            if (!isset($receiver['ruc']['contributorType'])) {
                $contributorType = $this->detectContributorType($receiver['ruc']['ruc']);
                $rucData['contributorType'] = $contributorType;
            } else {
                $rucData['contributorType'] = $receiver['ruc']['contributorType'];
            }

            $receiverData['ruc'] = $rucData;
        }

        return $receiverData;
    }
}

// Test cases
$testCases = [
    // Caso 1: RUC de persona natural sin contributorType especificado
    [
        'description' => 'Persona Natural - Auto-detectado',
        'input' => [
            'receiver' => [
                'type' => '01',
                'name' => 'Juan Pérez',
                'address' => 'Calle Principal 123',
                'country' => 'PA',
                'ruc' => [
                    'type' => 1,
                    'ruc' => '8-123-456'
                ]
            ]
        ],
        'expected_contributor_type' => 1
    ],

    // Caso 2: RUC de empresa sin contributorType especificado
    [
        'description' => 'Empresa - Auto-detectado',
        'input' => [
            'receiver' => [
                'type' => '01',
                'name' => 'Empresa XYZ S.A.',
                'address' => 'Av. Comercial 456',
                'country' => 'PA',
                'ruc' => [
                    'type' => 1,
                    'ruc' => '155-41-85123'
                ]
            ]
        ],
        'expected_contributor_type' => 2
    ],

    // Caso 3: contributorType especificado manualmente
    [
        'description' => 'Tipo Manual - Respetado',
        'input' => [
            'receiver' => [
                'type' => '01',
                'name' => 'Cliente Manual',
                'address' => 'Dirección Cualquiera',
                'country' => 'PA',
                'ruc' => [
                    'type' => 1,
                    'ruc' => '1-2-3',
                    'contributorType' => 1 // Forzado manualmente
                ]
            ]
        ],
        'expected_contributor_type' => 1 // Debe respetar el manual
    ],

    // Caso 4: Panameño en el extranjero
    [
        'description' => 'Panameño Extranjero',
        'input' => [
            'receiver' => [
                'type' => '01',
                'name' => 'María González',
                'address' => 'Miami, USA',
                'country' => 'PA',
                'ruc' => [
                    'type' => 1,
                    'ruc' => 'PE-123-456'
                ]
            ]
        ],
        'expected_contributor_type' => 1
    ],

    // Caso 5: Caso ambiguo resuelto
    [
        'description' => 'Caso Ambiguo - Empresa',
        'input' => [
            'receiver' => [
                'type' => '01',
                'name' => 'Empresa Ambigua',
                'address' => 'Zona Comercial',
                'country' => 'PA',
                'ruc' => [
                    'type' => 1,
                    'ruc' => '12-34-567'
                ]
            ]
        ],
        'expected_contributor_type' => 2
    ]
];

echo "=== TESTING CONTRIBUTOR TYPE IN ALANUBE REQUESTS ===\n\n";

$service = new MockAlanubeService();
$totalTests = count($testCases);
$passedTests = 0;

foreach ($testCases as $index => $testCase) {
    $result = $service->buildReceiver($testCase['input']);

    $actualContributorType = $result['ruc']['contributorType'] ?? null;
    $expectedContributorType = $testCase['expected_contributor_type'];

    $status = $actualContributorType === $expectedContributorType ? "✅ PASS" : "❌ FAIL";

    if ($actualContributorType === $expectedContributorType) {
        $passedTests++;
    }

    echo "Test " . ($index + 1) . ": {$testCase['description']}\n";
    echo "  RUC: {$testCase['input']['receiver']['ruc']['ruc']}\n";
    echo "  Expected contributorType: $expectedContributorType\n";
    echo "  Actual contributorType: $actualContributorType\n";
    echo "  Status: $status\n";

    // Mostrar estructura completa del receptor para verificar
    echo "  Receiver Data:\n";
    echo "    type: {$result['type']}\n";
    echo "    name: {$result['name']}\n";
    echo "    ruc.type: {$result['ruc']['type']}\n";
    echo "    ruc.ruc: {$result['ruc']['ruc']}\n";
    echo "    ruc.contributorType: {$result['ruc']['contributorType']}\n";
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests/$totalTests)*100, 1) . "%\n\n";

echo "=== SAMPLE JSON FOR ALANUBE API ===\n";
$sampleResult = $service->buildReceiver($testCases[0]['input']);
echo json_encode($sampleResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

?>
