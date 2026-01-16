<?php
/**
 * Test de validación: Unidades de medida para facturas de exportación
 *
 * OBJETIVO: Verificar que se usan las unidades correctas según documentación TheFactoryHKA
 * PROBLEMA: Error PAC "El campo unidadMedida es inválido"
 * DOCUMENTACIÓN: https://felwiki.thefactoryhka.com.pa/factura_de_exportacion
 *
 * UNIDADES SEGÚN EJEMPLO OFICIAL:
 * - unidadMedida: "um" (NO "UND")
 * - unidadMedidaCPBS: "cm" (NO "UND")
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\HKAService;
use App\Models\Organization;
use App\Models\User;

echo "=== TEST: Unidades de Medida Facturas de Exportación ===\n";
echo "Objetivo: Verificar unidades correctas según ejemplo oficial PAC\n";
echo "Problema: Campo unidadMedida inválido en facturas exportación\n\n";

// Crear datos de prueba para factura de exportación vs factura normal
$testCases = [
    [
        'name' => 'Factura Normal (Tipo 01)',
        'tipoDoc' => '01',
        'expectedUM' => 'UND',
        'expectedUMCPBS' => 'UND'
    ],
    [
        'name' => 'Factura Exportación (Tipo 03)',
        'tipoDoc' => '03',
        'expectedUM' => 'um',
        'expectedUMCPBS' => 'cm'
    ]
];

foreach ($testCases as $testCase) {
    echo "=== {$testCase['name']} ===\n";

    $doc = (object) [
        'dGen' => (object) [
            'iDoc' => $testCase['tipoDoc'],
            'dNroDF' => 'TEST123',
            'dPtoFacDF' => '001',
            'dFechaEm' => '2024-10-24T10:00:00-05:00',
            'iNatOp' => '01',
            'iTipoOp' => $testCase['tipoDoc'] === '03' ? '2' : '1',
            'iDest' => $testCase['tipoDoc'] === '03' ? '2' : '1',
            'gDatRec' => (object) [
                'iTipoRec' => $testCase['tipoDoc'] === '03' ? '04' : '02',
                'dNombRec' => 'CLIENTE TEST',
                'dDirecRec' => 'Dirección TEST'
            ]
        ],
        'gItem' => [
            (object) [
                'dDescProd' => 'Producto de Prueba',
                'dCodProd' => 'PROD001',
                // NO especificar unidades para probar valores por defecto
                'dCantCodInt' => '1.000000',
                'gPrecios' => (object) [
                    'dPrUnit' => '100.000000',
                    'dPrItem' => '100.000000',
                    'dValTotItem' => '100.000000'
                ],
                'gITBMSItem' => (object) [
                    'dTasaITBMS' => '00',
                    'dValITBMS' => '0.000000'
                ]
            ]
        ],
        'gTot' => (object) [
            'dTotNeto' => '100.00',
            'dVTot' => '100.00',
            'dTotRec' => '100.00',
            'dNroItems' => '1',
            'dVTotItems' => '100.00',
            'iPzPag' => '1'
        ]
    ];

    // Para facturas de exportación agregar datos específicos
    if ($testCase['tipoDoc'] === '03') {
        $doc->gFExp = (object) [
            'cCondEntr' => 'EXW',
            'cMoneda' => 'USD',
            'dPuertoEmbarq' => 'PANAMA'
        ];
    }

    try {
        // Crear organización y usuario de prueba
        $organization = new Organization();
        $organization->id = 1;
        $user = new User();

        $hkaService = new HKAService($organization, $user);

        echo "1. Generando DocumentoElectronico...\n";
        $documentoElectronico = $hkaService->createFeXML($doc);

        echo "2. Verificando primer item...\n";
        if (empty($documentoElectronico->listaItems)) {
            throw new Exception("❌ ERROR: No se generaron items");
        }

        $item = $documentoElectronico->listaItems[0];

        echo "3. Validando unidadMedida...\n";
        echo "   - Esperado: '{$testCase['expectedUM']}'\n";
        echo "   - Obtenido: '{$item->unidadMedida}'\n";

        if ($item->unidadMedida === $testCase['expectedUM']) {
            echo "   ✅ unidadMedida CORRECTO\n";
        } else {
            throw new Exception("❌ ERROR: unidadMedida incorrecto. Esperado '{$testCase['expectedUM']}', obtenido '{$item->unidadMedida}'");
        }

        echo "4. Validando unidadMedidaCPBS...\n";
        echo "   - Esperado: '{$testCase['expectedUMCPBS']}'\n";
        echo "   - Obtenido: '{$item->unidadMedidaCPBS}'\n";

        if ($item->unidadMedidaCPBS === $testCase['expectedUMCPBS']) {
            echo "   ✅ unidadMedidaCPBS CORRECTO\n";
        } else {
            throw new Exception("❌ ERROR: unidadMedidaCPBS incorrecto. Esperado '{$testCase['expectedUMCPBS']}', obtenido '{$item->unidadMedidaCPBS}'");
        }

        echo "✅ ÉXITO: {$testCase['name']} usa unidades correctas\n\n";

    } catch (Exception $e) {
        echo "❌ ERROR EN {$testCase['name']}:\n";
        echo "Mensaje: " . $e->getMessage() . "\n\n";

        if (isset($item)) {
            echo "Item generado:\n";
            echo "- unidadMedida: {$item->unidadMedida}\n";
            echo "- unidadMedidaCPBS: {$item->unidadMedidaCPBS}\n\n";
        }
    }
}

// Test adicional con unidades específicas en datos de entrada
echo "=== TEST ADICIONAL: Respeto de unidades específicas ===\n";

$docConUnidades = (object) [
    'dGen' => (object) [
        'iDoc' => '03', // Exportación
        'dNroDF' => 'TEST456',
        'gDatRec' => (object) [
            'iTipoRec' => '04',
            'dNombRec' => 'CLIENTE EXTRANJERO',
        ]
    ],
    'gItem' => [
        (object) [
            'dDescProd' => 'Producto con unidades específicas',
            'dCodProd' => 'PROD002',
            'cUnidad' => 'kg', // Unidad específica
            'cUnidadCPBS' => 'mt', // Unidad CPBS específica
            'dCantCodInt' => '2.500000',
            'gPrecios' => (object) [
                'dPrUnit' => '50.000000',
                'dPrItem' => '125.000000',
                'dValTotItem' => '125.000000'
            ],
            'gITBMSItem' => (object) [
                'dTasaITBMS' => '00',
                'dValITBMS' => '0.000000'
            ]
        ]
    ],
    'gFExp' => (object) [
        'cCondEntr' => 'EXW',
        'cMoneda' => 'USD',
        'dPuertoEmbarq' => 'PANAMA'
    ],
    'gTot' => (object) [
        'dTotNeto' => '125.00',
        'dVTot' => '125.00',
        'dTotRec' => '125.00',
        'dNroItems' => '1',
        'dVTotItems' => '125.00',
        'iPzPag' => '1'
    ]
];

try {
    $organization = new Organization();
    $organization->id = 1;
    $user = new User();

    $hkaService = new HKAService($organization, $user);
    $documentoElectronico = $hkaService->createFeXML($docConUnidades);

    $item = $documentoElectronico->listaItems[0];

    echo "Unidades específicas proporcionadas:\n";
    echo "- Input cUnidad: 'kg'\n";
    echo "- Input cUnidadCPBS: 'mt'\n";
    echo "- Output unidadMedida: '{$item->unidadMedida}'\n";
    echo "- Output unidadMedidaCPBS: '{$item->unidadMedidaCPBS}'\n";

    if ($item->unidadMedida === 'kg' && $item->unidadMedidaCPBS === 'mt') {
        echo "✅ CORRECTO: Respeta unidades específicas de entrada\n";
    } else {
        echo "❌ ERROR: No respeta unidades específicas\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DOCUMENTACIÓN OFICIAL ===\n";
echo "🌐 Referencia: https://felwiki.thefactoryhka.com.pa/factura_de_exportacion\n";
echo "📋 Ejemplo oficial XML:\n";
echo "   <ser:unidadMedida>um</ser:unidadMedida>\n";
echo "   <ser:unidadMedidaCPBS>cm</ser:unidadMedidaCPBS>\n";
echo "\n✅ REGLA: Facturas exportación (tipo 03) requieren 'um' y 'cm' por defecto\n";
echo "✅ REGLA: Facturas normales pueden usar 'UND' por defecto\n";
echo "✅ REGLA: Siempre respetar unidades específicas si se proporcionan\n";

echo "\n=== FIN DEL TEST ===\n";
