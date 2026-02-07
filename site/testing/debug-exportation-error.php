<?php

/**
 * Script de Debug para Error "instance requires property exportation"
 *
 * Este script ayuda a identificar por qué sigue apareciendo el error
 * incluso después de implementar la lógica condicional
 */

// Simular datos de ejemplo para debugging
$testData = [
    'dGen' => [
        'iDoc' => '01', // Factura de operación interna
        'iTpEmis' => '01',
        'dNroDF' => 'TEST001',
        'gDatRec' => [
            'iTipoRec' => '04', // Receptor extranjero
            'dNomRec' => 'Cliente Extranjero TEST',
            'dDirRec' => 'Dirección en USA',
            'cPaisRec' => 'US',
            'dPaisRecDesc' => 'Estados Unidos',
            'gRucRec' => [
                'dRuc' => 'N/A',
                'dTipoRuc' => null
            ],
            'gIdExt' => [
                'dIdExt' => 'US123456789',
                'dPaisExt' => 'Estados Unidos'
            ]
        ]
    ]
    // NO incluir gFExp para operación interna
];

echo "🔍 DEBUG: Error 'instance requires property exportation'\n";
echo "========================================================\n\n";

echo "📋 Datos de prueba:\n";
echo "- Tipo documento: " . ($testData['dGen']['iDoc'] ?? 'N/A') . "\n";
echo "- Tipo receptor: " . ($testData['dGen']['gDatRec']['iTipoRec'] ?? 'N/A') . "\n";
echo "- País receptor: " . ($testData['dGen']['gDatRec']['cPaisRec'] ?? 'N/A') . "\n";
echo "- ¿Tiene gFExp?: " . (isset($testData['gFExp']) ? 'SÍ' : 'NO') . "\n\n";

// Test 1: Verificar detección de país
echo "🌍 Test 1: Detección de país PAC\n";
echo "--------------------------------\n";

// Simular diferentes conexiones PAC
$connections = [
    ['endpoint' => 'https://api.alanube.co/pan/v1', 'name' => 'Alanube Panama'],
    ['endpoint' => 'https://api.alanube.co/dom/v1', 'name' => 'Alanube Dominicana'],
    ['endpoint' => 'https://api.alanube.co/v1', 'name' => 'Alanube General']
];

foreach ($connections as $conn) {
    $country = detectCountryFromEndpoint($conn['endpoint']);
    echo "- {$conn['name']} ({$conn['endpoint']}) → País: $country\n";
}
echo "\n";

// Test 2: Verificar lógica condicional
echo "📝 Test 2: Lógica condicional de exportation\n";
echo "--------------------------------------------\n";

$documentTypes = ['01', '02', '03', '04'];
$countries = ['PA', 'DO'];

foreach ($countries as $country) {
    echo "País: $country\n";
    foreach ($documentTypes as $docType) {
        $shouldInclude = shouldIncludeExportation($docType, $country, isset($testData['gFExp']));
        $status = $shouldInclude ? '✅ SÍ' : '❌ NO';
        echo "  - Tipo $docType: $status incluir exportation\n";
    }
    echo "\n";
}

// Test 3: Simular procesamiento real
echo "⚡ Test 3: Simulación de procesamiento\n";
echo "-------------------------------------\n";

foreach (['PA', 'DO'] as $country) {
    echo "Procesando como país: $country\n";

    $result = simulateFormatting($testData, $country);
    $hasExportation = isset($result['receiver']['exportation']);

    echo "- ¿Resultado incluye exportation?: " . ($hasExportation ? 'SÍ ❌ ERROR' : 'NO ✅ CORRECTO') . "\n";

    if ($hasExportation) {
        echo "  PROBLEMA: Para tipo 01 NO debe incluir exportation\n";
    } else {
        echo "  CORRECTO: Tipo 01 no incluye exportation (cumple DGI)\n";
    }
    echo "\n";
}

// Funciones auxiliares
function detectCountryFromEndpoint($endpoint) {
    if (strpos($endpoint, '/pan/v1') !== false) return 'PA';
    if (strpos($endpoint, '/dom/v1') !== false) return 'DO';
    return 'DO'; // Default
}

function shouldIncludeExportation($docType, $country, $hasGfExp) {
    if (!$hasGfExp) return false;

    if ($country === 'PA') {
        return in_array($docType, ['02', '03']);
    } else {
        return in_array($docType, ['2', '3']);
    }
}

function simulateFormatting($data, $country) {
    $documentType = $data['dGen']['iDoc'] ?? ($country === 'PA' ? '01' : '1');

    $receiver = [
        'type' => $data['dGen']['gDatRec']['iTipoRec'] ?? '1',
        'name' => $data['dGen']['gDatRec']['dNomRec'] ?? null,
        'country' => $data['dGen']['gDatRec']['cPaisRec'] ?? null,
    ];

    // Aplicar lógica condicional según implementación actual
    if ($country === 'PA') {
        $exportationRequiredTypes = ['02', '03'];
    } else {
        $exportationRequiredTypes = ['2', '3'];
    }

    if (in_array($documentType, $exportationRequiredTypes) && isset($data['gFExp'])) {
        $receiver['exportation'] = [
            'incoterm' => $data['gFExp']['cCondEntr'] ?? null,
            'currency' => $data['gFExp']['cMoneda'] ?? null,
        ];
    }

    return [
        'information' => ['documentType' => $documentType],
        'receiver' => $receiver
    ];
}

echo "🎯 CONCLUSIONES Y RECOMENDACIONES\n";
echo "=================================\n";
echo "1. Verificar qué país está detectando el PAC real\n";
echo "2. Confirmar tipo de documento que se está enviando\n";
echo "3. Revisar si el PAC Alanube tiene validaciones adicionales\n";
echo "4. Considerar agregar logs para debug en producción\n\n";

echo "📋 NEXT STEPS:\n";
echo "- Agregar logs en AlanubeFormatterHelper::format()\n";
echo "- Verificar datos reales enviados al PAC\n";
echo "- Confirmar configuración de conexión PAC\n";
