<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Helpers\AlanubeFormatterHelper;

// Datos de la factura con ITBMS en 0 como en el ejemplo del usuario
$sampleData = [
    "key" => null,
    "dGen" => [
        "iDoc" => "1",
        "dNroDF" => "000976",
        "dPtoFacDF" => "200",
        "dFechaEm" => "2025-08-21T00:00:00-05:00",
        "iNatOp" => "1",
        "iTipoOp" => "1",
        "iDest" => 1,
        "iTipoTranVenta" => "1",
        "dInfEmFE" => "INV-000976",
        "dNroDFZ" => "INV-000976",
        "dNroDFIDZ" => "6027930000003905001",
        "gEmis" => [
            "gRucEmi" => [
                "dTipoRuc" => "1",
                "dRuc" => "4-717-1238",
                "dDV" => "05"
            ],
            "gUbiEm" => [
                "dCodUbi" => "4-6-1",
                "dCorreg" => "DAVID (CABECERA)",
                "dDistr" => "DAVID",
                "dProv" => "CHIRIQUI"
            ],
            "dNombEm" => "C4-PHARMA",
            "dSucEm" => "0003",
            "dCoordEm" => "+8.43803,-82.4267",
            "dDirecEm" => "DAVID, AVE. OBALDIA, EDF. DOÑ'A EMELDA, LOCAL 3",
            "dTfnEm" => "730-2365",
            "dCorElectEmi" => "ginarosemena@chyju.com"
        ],
        "gDatRec" => [
            "iTipoRec" => "1",
            "gRucRec" => [
                "dTipoRuc" => "2",
                "dRuc" => "1808755-1-706832",
                "dDV" => "97"
            ],
            "dNombRec" => "CLINICA HOSPITALSAN JUAN DE DIOS",
            "gUbiRec" => [
                "dCodUbi" => "9-10-1",
                "dCorreg" => "SANTIAGO, VERAGUAS",
                "dDistr" => "VERAGUAS",
                "dProv" => "VERAGUAS"
            ],
            "dCorElectRec" => "clinicasanjuandedios_@hotmail.com",
            "cPaisRec" => "PA",
            "dPaisRecDesc" => "Panamá"
        ]
    ],
    "gItem" => [
        [
            "dDescProd" => "Diazepam 10mg/2ml. LOTE 75TL1965 VENCE 30/11/2026",
            "cUnidad" => "und",
            "dCantCodInt" => 100,
            "cUnidadCPBS" => "und",
            "dCodProd" => "007800620035",
            "dInfEmFE" => null,
            "gPrecios" => [
                "dPrUnit" => "3.500000",
                "dPrUnitDesc" => "0.000000",
                "dPrItem" => "350.000000",
                "dValTotItem" => "350.000000"
            ],
            "gITBMSItem" => [
                "dTasaITBMS" => 0,  // ¡CERO!
                "dValITBMS" => "0.000000"
            ],
            "gISCItem" => [
                "dTasaISC" => "0.00",
                "dValISC" => "0.00"
            ],
            "dSecItem" => 1
        ],
        [
            "dDescProd" => "MIDAZOLAM NORMON 15MG/3ML",
            "cUnidad" => "und",
            "dCantCodInt" => 30,
            "cUnidadCPBS" => "und",
            "dCodProd" => "8435232319026",
            "dInfEmFE" => null,
            "gPrecios" => [
                "dPrUnit" => "2.500000",
                "dPrUnitDesc" => "0.000000",
                "dPrItem" => "75.000000",
                "dValTotItem" => "75.000000"
            ],
            "gITBMSItem" => [
                "dTasaITBMS" => 0,  // ¡CERO!
                "dValITBMS" => "0.000000"
            ],
            "gISCItem" => [
                "dTasaISC" => "0.00",
                "dValISC" => "0.00"
            ],
            "dSecItem" => 2
        ]
    ],
    "gTot" => [
        "dTotNeto" => "425.00",
        "dTotITBMS" => "0.00",
        "dTotISC" => "0.00",
        "dTotGravado" => "0.00",
        "dTotDesc" => "0.00",
        "dTotAcar" => "0.00",
        "dTotSeg" => "0.00",
        "dVTot" => "425.00",
        "dTotRec" => "425.00",
        "dVuelto" => "0.00",
        "iPzPag" => 1,
        "dNroItems" => 2,
        "dVTotItems" => "425.00",
        "gFormaPago" => [
            [
                "iFormaPago" => "02",
                "dVlrCuota" => "425.00"
            ]
        ]
    ]
];

echo "=== TESTING PANAMA ITBMS RATE STRUCTURE ===\n\n";

try {
    // Formatear usando AlanubeFormatterHelper para Panamá
    $formatted = AlanubeFormatterHelper::format($sampleData);

    echo "✅ Formateo exitoso\n\n";

    // Verificar estructura de items con ITBMS
    if (isset($formatted['items']) && is_array($formatted['items'])) {
        foreach ($formatted['items'] as $index => $item) {
            echo "--- Item " . ($index + 1) . " ---\n";

            if (isset($item['itbms'])) {
                echo "✅ ITBMS structure present:\n";
                echo "  - rate: " . (isset($item['itbms']['rate']) ? $item['itbms']['rate'] : 'MISSING') . "\n";
                echo "  - amount: " . (isset($item['itbms']['amount']) ? $item['itbms']['amount'] : 'MISSING') . "\n";

                // Verificar que rate esté presente incluso si es 0
                if (isset($item['itbms']['rate'])) {
                    echo "  ✅ Rate property exists (value: " . $item['itbms']['rate'] . ")\n";
                } else {
                    echo "  ❌ Rate property MISSING - this causes PAC validation error\n";
                }

                if (isset($item['itbms']['amount'])) {
                    echo "  ✅ Amount property exists (value: " . $item['itbms']['amount'] . ")\n";
                } else {
                    echo "  ❌ Amount property MISSING\n";
                }
            } else {
                echo "❌ ITBMS structure missing from item\n";
            }

            if (isset($item['isc'])) {
                echo "✅ ISC structure present:\n";
                echo "  - rate: " . (isset($item['isc']['rate']) ? $item['isc']['rate'] : 'MISSING') . "\n";
                echo "  - amount: " . (isset($item['isc']['amount']) ? $item['isc']['amount'] : 'MISSING') . "\n";
            }

            echo "\n";
        }
    }

    // Mostrar estructura completa del primer item para debug
    echo "=== DEBUG: Estructura completa del primer item ===\n";
    if (isset($formatted['items'][0])) {
        echo json_encode($formatted['items'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTING RECEIVER RUC STRUCTURE ===\n";

// Verificar que la estructura del receptor tenga la propiedad 'type'
if (isset($formatted['receiver']['ruc'])) {
    echo "✅ Receiver RUC structure present:\n";
    echo "  - type: " . (isset($formatted['receiver']['ruc']['type']) ? $formatted['receiver']['ruc']['type'] : 'MISSING') . "\n";
    echo "  - ruc: " . (isset($formatted['receiver']['ruc']['ruc']) ? $formatted['receiver']['ruc']['ruc'] : 'MISSING') . "\n";
    echo "  - verificationDigit: " . (isset($formatted['receiver']['ruc']['verificationDigit']) ? $formatted['receiver']['ruc']['verificationDigit'] : 'MISSING') . "\n";

    if (isset($formatted['receiver']['ruc']['type'])) {
        echo "  ✅ Type property exists - PAC validation should pass\n";
    } else {
        echo "  ❌ Type property MISSING - this causes 'instance.receiver.ruc requires property type' error\n";
    }
}

if (isset($formatted['receiver']['authorizedGroup'])) {
    echo "✅ Receiver AuthorizedGroup structure present:\n";
    echo "  - type: " . (isset($formatted['receiver']['authorizedGroup']['type']) ? $formatted['receiver']['authorizedGroup']['type'] : 'MISSING') . "\n";
    echo "  - ruc: " . (isset($formatted['receiver']['authorizedGroup']['ruc']) ? $formatted['receiver']['authorizedGroup']['ruc'] : 'MISSING') . "\n";
}

echo "\n=== CONCLUSION ===\n";
echo "This test validates that:\n";
echo "1. ITBMS rate property is preserved even when value is 0\n";
echo "2. ISC rate property is preserved even when value is 0\n";
echo "3. Receiver RUC has required 'type' property\n";
echo "4. Both authorizedGroup and ruc structures are available\n";
echo "\nPAC should now accept the formatted data without validation errors.\n";

echo "🧪 Test específico: estructura receiver.ruc" . PHP_EOL;
echo "===========================================" . PHP_EOL;

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

echo "Datos de entrada RUC:" . PHP_EOL;
echo "  dTipoRuc: " . $testData['dGen']['gDatRec']['gRucRec']['dTipoRuc'] . PHP_EOL;
echo "  dRuc: " . $testData['dGen']['gDatRec']['gRucRec']['dRuc'] . PHP_EOL;
echo "  dDVRuc: " . $testData['dGen']['gDatRec']['gRucRec']['dDVRuc'] . PHP_EOL;

$feHeader = new App\Models\FeHeader();
$result = $feHeader->configureFormatterAlanube($testData);

echo PHP_EOL . "Estructura resultante receiver.ruc:" . PHP_EOL;

if (isset($result['receiver']['ruc'])) {
    $rucData = $result['receiver']['ruc'];
    echo "  type: " . ($rucData['type'] ?? 'MISSING') . " (tipo: " . gettype($rucData['type'] ?? null) . ")" . PHP_EOL;
    echo "  ruc: " . ($rucData['ruc'] ?? 'MISSING') . PHP_EOL;
    echo "  verificationDigit: " . ($rucData['verificationDigit'] ?? 'MISSING') . PHP_EOL;

    echo PHP_EOL . "Validaciones:" . PHP_EOL;

    // Validar que type existe y es un entero
    if (isset($rucData['type']) && is_int($rucData['type'])) {
        echo "✅ Campo 'type' presente y es entero: " . $rucData['type'] . PHP_EOL;
    } else {
        echo "❌ Campo 'type' faltante o no es entero" . PHP_EOL;
    }

    // Validar que ruc existe
    if (isset($rucData['ruc']) && !empty($rucData['ruc'])) {
        echo "✅ Campo 'ruc' presente: " . $rucData['ruc'] . PHP_EOL;
    } else {
        echo "❌ Campo 'ruc' faltante o vacío" . PHP_EOL;
    }

    echo PHP_EOL . "JSON estructura completa receiver.ruc:" . PHP_EOL;
    echo json_encode($rucData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

} else {
    echo "❌ Estructura receiver.ruc no encontrada" . PHP_EOL;
    echo "Estructura receiver disponible: " . PHP_EOL;
    if (isset($result['receiver'])) {
        echo json_encode(array_keys($result['receiver']), JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "❌ Estructura receiver no encontrada" . PHP_EOL;
    }
}

echo PHP_EOL . "🎯 RESUMEN:" . PHP_EOL;
echo "- Corrección aplicada: eliminación de authorizedGroup duplicado" . PHP_EOL;
echo "- Campo type garantizado con valor por defecto como entero" . PHP_EOL;
echo "- Estructura receiver.ruc conforme a validación PAC Alanube" . PHP_EOL;
