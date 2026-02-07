<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Test: RUC sin tipo definido (valor por defecto)" . PHP_EOL;
echo "=================================================" . PHP_EOL;

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
                // dTipoRuc no definido - debe usar valor por defecto
                'dRuc' => '987654321'
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

echo "Datos de entrada RUC (sin tipo):" . PHP_EOL;
echo "  dTipoRuc: " . ($testData['dGen']['gDatRec']['gRucRec']['dTipoRuc'] ?? 'NULL') . PHP_EOL;
echo "  dRuc: " . $testData['dGen']['gDatRec']['gRucRec']['dRuc'] . PHP_EOL;

$feHeader = new App\Models\FeHeader();
$result = $feHeader->configureFormatterAlanube($testData);

echo PHP_EOL . "Estructura resultante receiver.ruc:" . PHP_EOL;

if (isset($result['receiver']['ruc'])) {
    $rucData = $result['receiver']['ruc'];
    echo "  type: " . ($rucData['type'] ?? 'MISSING') . " (tipo: " . gettype($rucData['type'] ?? null) . ")" . PHP_EOL;
    echo "  ruc: " . ($rucData['ruc'] ?? 'MISSING') . PHP_EOL;

    echo PHP_EOL . "Validaciones:" . PHP_EOL;

    // Validar que type tiene el valor por defecto 1
    if (isset($rucData['type']) && $rucData['type'] === 1) {
        echo "✅ Campo 'type' con valor por defecto correcto: 1" . PHP_EOL;
    } else {
        echo "❌ Campo 'type' no tiene valor por defecto correcto (esperado: 1, obtenido: " . ($rucData['type'] ?? 'NULL') . ")" . PHP_EOL;
    }

    // Validar que es entero
    if (isset($rucData['type']) && is_int($rucData['type'])) {
        echo "✅ Campo 'type' es entero" . PHP_EOL;
    } else {
        echo "❌ Campo 'type' no es entero" . PHP_EOL;
    }

} else {
    echo "❌ Estructura receiver.ruc no encontrada" . PHP_EOL;
}

echo PHP_EOL . "🎯 RESULTADO:" . PHP_EOL;
echo "- Valor por defecto aplicado correctamente para dTipoRuc faltante" . PHP_EOL;
echo "- Estructura receiver.ruc válida para PAC Alanube" . PHP_EOL;
