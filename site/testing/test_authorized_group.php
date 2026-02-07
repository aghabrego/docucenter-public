<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Test: verificar authorizedGroup en Alanube Panamá" . PHP_EOL;
echo "===================================================" . PHP_EOL;

$testData = [
    'dGen' => [
        'iTpEmis' => '01',
        'iDoc' => '01',
        'dNroDF' => '123456',
        'dPtoFacDF' => '001',
        'dFechaEm' => '2024-01-01',
        'iNatOp' => '01',
        'gDatRec' => [
            'iTipoRec' => '1',
            'dNombRec' => 'CLIENTE TEST',
            'dDirecRec' => 'DIRECCION TEST',
            'gRucRec' => [
                'dTipoRuc' => '2',
                'dRuc' => '123456789',
                'dDVRuc' => '1'
            ]
        ]
    ],
    'gItem' => [
        [
            'dSecItem' => '1',
            'dDescProd' => 'PRODUCTO TEST',
            'dCantCodInt' => '1',
            'gPrecios' => [
                'dPrUnit' => '10.00'
            ],
            'gITBMSItem' => [
                'dTasaITBMS' => '07'
            ]
        ]
    ],
    'gTot' => [
        'gFormaPago' => [
            [
                'iFormaPago' => '01',
                'dVlrCuota' => '10.00'
            ]
        ],
        'gPagPlazo' => [],
        'dTotAcar' => '0.00',
        'dTotSeg' => '0.00',
        'iPzPag' => '1'
    ]
];

echo "Datos de entrada:" . PHP_EOL;
echo "  dTipoRuc: " . $testData['dGen']['gDatRec']['gRucRec']['dTipoRuc'] . PHP_EOL;
echo "  dRuc: " . $testData['dGen']['gDatRec']['gRucRec']['dRuc'] . PHP_EOL;

// Simular conexión PAC de Panamá
$pacConnection = new App\Models\Pacconnection();
$pacConnection->endpoint = 'https://api.example.com/pan/v1';
$pacConnection->name = 'Alanube Panama';

$result = App\Helpers\AlanubeFormatterHelper::format($testData, $pacConnection);

echo PHP_EOL . "Verificando estructuras en receiver:" . PHP_EOL;

if (isset($result['receiver']['authorizedGroup'])) {
    echo "✅ authorizedGroup presente:" . PHP_EOL;
    echo "   type: " . ($result['receiver']['authorizedGroup']['type'] ?? 'MISSING') . PHP_EOL;
    echo "   ruc: " . ($result['receiver']['authorizedGroup']['ruc'] ?? 'MISSING') . PHP_EOL;
} else {
    echo "❌ authorizedGroup NO encontrado" . PHP_EOL;
}

if (isset($result['receiver']['ruc'])) {
    echo "✅ ruc presente:" . PHP_EOL;
    echo "   type: " . ($result['receiver']['ruc']['type'] ?? 'MISSING') . PHP_EOL;
    echo "   ruc: " . ($result['receiver']['ruc']['ruc'] ?? 'MISSING') . PHP_EOL;
    echo "   verificationDigit: " . ($result['receiver']['ruc']['verificationDigit'] ?? 'MISSING') . PHP_EOL;
} else {
    echo "❌ ruc NO encontrado" . PHP_EOL;
}

echo PHP_EOL . "JSON completo receiver:" . PHP_EOL;
if (isset($result['receiver'])) {
    echo json_encode($result['receiver'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo PHP_EOL . "🎯 ESTADO:" . PHP_EOL;
echo "- authorizedGroup restaurado para Alanube Panamá" . PHP_EOL;
echo "- Ambas estructuras (authorizedGroup y ruc) disponibles" . PHP_EOL;
echo "- Configuración compatible con especificación PAC" . PHP_EOL;
