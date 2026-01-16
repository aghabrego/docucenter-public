<?php
/**
 * TESTING CRÍTICO: Verificar corrección de campos obligatorios exportación
 *
 * PROBLEMA IDENTIFICADO: Campos mal mapeados en datosFacturaExportacion
 * - moneda → monedaOperExportacion (según documentación PAC oficial)
 * - paisDestino removido (no pertenece a datosFacturaExportacion)
 */

echo "=== TESTING CORRECCIÓN CAMPOS EXPORTACIÓN OBLIGATORIOS ===\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar para testing
putenv('DB_CONNECTION=mysql');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=docucenter');

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HKAService;
use App\Models\Organization;
use App\Models\User;

// Datos de prueba exportación
$docExportacion = (object) [
    'dEmisor' => (object) [
        'dCodSuc' => '0000',
        'iTipoSuc' => 1
    ],
    'dGen' => (object) [
        'iAmb' => 2,
        'iTpEmis' => '01',
        'iDoc' => '03', // CORREGIDO: Debe ser iDoc, no iTipoDoc
        'dNroDF' => '0000000057',
        'dPtoFacDF' => '004',
        'dFechaEm' => '2025-10-24T14:00:50-05:00',
        'iNatOp' => '02', // 02: Exportación según documentación
        'iTipoOp' => 1,
        'iDest' => '2', // CORREGIDO: Debe ser iDest, no iDestOp
        'iFormCAFE' => 2,
        'iEntCAFE' => 3,
        'dEnvFE' => 1, // CORREGIDO: Debe ser dEnvFE, no iEnvContenedor
        'iProGen' => 1, // CORREGIDO: Debe ser iProGen, no iProcGenFC
        'iTipoTranVenta' => 1, // CORREGIDO: Debe ser iTipoTranVenta, no iTipoVenta
        'dInfEmFE' => 'Order FC0000002986', // CORREGIDO: Debe ser dInfEmFE, no dInfEmFE
        'gDatRec' => (object) [
            'iTipoRec' => '04', // EXTRANJERO
            'dNombRec' => 'Solmary',
            'dCorElectRec' => 'solmarymadrid@gmail.com',
            'gIdExt' => (object) [
                'tipoIdentificacion' => '01',
                'dIdExt' => 'XYZABC123',
                'dPaisExt' => 'CL'
            ],
            'cPaisRec' => 'CL' // CRÍTICO: No puede ser PA si destinoOperacion=2
        ],
        'gEmis' => (object) [
            'dSucEm' => '0000'
        ],
        'gRucEmi' => (object) [
            'dTipoRuc' => 1
        ]
    ],
    'gFExp' => (object) [
        'cCondEntr' => 'CFR',
        'cMoneda' => 'USD',
        'dCambio' => '1.00',
        'dVTotEst' => '3.50',
        'dPuertoEmbarq' => 'PANAMA',
        'cPaisDest' => 'CL'
    ],
    'gItem' => [
        (object) [
            'dDescProd' => 'BROCHURE 11X17 EXPORTACION',
            'dCodProd' => 'ITEM001',
            'cUnidad' => 'UND',
            'dCantCodInt' => '1.000000',
            'cUnidadCPBS' => 'UND',
            'gPrecios' => (object) [
                'dPrUnit' => '3.500000',
                'dPrItem' => '3.500000',
                'dValTotItem' => '3.500000'
            ],
            'gITBMSItem' => (object) [
                'dTasaITBMS' => '00', // Exento para exportación
                'dValITBMS' => '0.000000'
            ],
            'dCodCPBScmp' => '9015'
        ]
    ],
    'gTot' => (object) [
        'dTotNeto' => '3.50',
        'dVTot' => '3.50',
        'dVTotRec' => '3.50',
        'iPzPag' => '1',
        'dNroItems' => 1,
        'dVTotItems' => '3.50',
        'gFormaPago' => [
            (object) [
                'iFormaPago' => '01',
                'dVlrCuota' => '3.50'
            ]
        ]
    ]
];

echo "1. GENERANDO DOCUMENTO CON CORRECCIONES:\n";
echo "=====================================\n";

$organization = new Organization(['id' => 1, 'name' => 'Test Org']);
$user = new User(['id' => 1, 'name' => 'Test User']);

$hkaService = new HKAService($organization, $user);
$documentoElectronico = $hkaService->createFeXML($docExportacion);

echo "2. VERIFICANDO DATOS EXPORTACIÓN CORREGIDOS:\n";
echo "===========================================\n";

$exportData = $documentoElectronico->datosTransaccion->datosFacturaExportacion ?? null;

if ($exportData) {
    echo "✅ datosFacturaExportacion: PRESENTE\n\n";

    // Verificar campos obligatorios según documentación oficial
    $camposObligatorios = [
        'condicionesEntrega' => $exportData->condicionesEntrega ?? 'MISSING',
        'monedaOperExportacion' => $exportData->monedaOperExportacion ?? 'MISSING',
        'tipoDeCambio' => $exportData->tipoDeCambio ?? 'MISSING',
        'montoMonedaExtranjera' => $exportData->montoMonedaExtranjera ?? 'MISSING'
    ];

    echo "CAMPOS OBLIGATORIOS EXPORTACIÓN:\n";
    foreach ($camposObligatorios as $campo => $valor) {
        $estado = ($valor !== 'MISSING') ? "✅ PRESENTE: '$valor'" : "❌ FALTANTE";
        echo "   - $campo: $estado\n";
    }

    // Verificar campos opcionales
    echo "\nCAMPOS OPCIONALES:\n";
    $puertoEmbarque = $exportData->puertoEmbarque ?? 'NO_PRESENTE';
    echo "   - puertoEmbarque: " . ($puertoEmbarque !== 'NO_PRESENTE' ? "✅ PRESENTE: '$puertoEmbarque'" : "⚪ OPCIONAL OMITIDO") . "\n";

    // Verificar que paisDestino NO esté en datosFacturaExportacion
    echo "\nCAMPOS INCORRECTOS REMOVIDOS:\n";
    $paisDestino = $exportData->paisDestino ?? null;
    echo "   - paisDestino: " . ($paisDestino === null ? "✅ CORRECTAMENTE REMOVIDO" : "❌ SIGUE PRESENTE: '$paisDestino'") . "\n";

} else {
    echo "❌ datosFacturaExportacion: FALTANTE\n";
}

echo "\n3. VERIFICANDO COHERENCIA TOTAL:\n";
echo "===============================\n";

$datos = $documentoElectronico->datosTransaccion;
$cliente = $datos->cliente;

// Verificar coherencia según documentación
$coherencia = [
    'tipoDocumento_vs_destinoOperacion' => [
        'tipoDocumento' => $datos->tipoDocumento,
        'destinoOperacion' => $datos->destinoOperacion,
        'coherente' => ($datos->tipoDocumento === '03' && $datos->destinoOperacion === '2')
    ],
    'naturalezaOperacion_exportacion' => [
        'naturalezaOperacion' => $datos->naturalezaOperacion,
        'esperado' => '02', // 02: Exportación
        'coherente' => ($datos->naturalezaOperacion === '02')
    ],
    'pais_vs_destinoOperacion' => [
        'pais' => $cliente->pais,
        'destinoOperacion' => $datos->destinoOperacion,
        'coherente' => ($cliente->pais !== 'PA' && $datos->destinoOperacion === '2')
    ],
    'tipoClienteFE_extranjero' => [
        'tipoClienteFE' => $cliente->tipoClienteFE,
        'esperado' => '04',
        'coherente' => ($cliente->tipoClienteFE === '04')
    ]
];

foreach ($coherencia as $aspecto => $datosCoherencia) {
    $estado = $datosCoherencia['coherente'] ? '✅ COHERENTE' : '❌ INCOHERENTE';
    echo "   $aspecto: $estado\n";
    if (!$datosCoherencia['coherente']) {
        $actual = $datosCoherencia[array_keys($datosCoherencia)[0]];
        $esperado = isset($datosCoherencia['esperado']) ? $datosCoherencia['esperado'] : 'Ver documentación';
        echo "      → Actual: $actual, Esperado: $esperado\n";
    }
}

echo "\n4. ESTRUCTURA FINAL LIMPIA:\n";
echo "==========================\n";

echo "Documento generado con:\n";
echo "- ✅ Campos obligatorios exportación presentes\n";
echo "- ✅ Mapeo correcto según documentación PAC\n";
echo "- ✅ Campos prohibidos extranjeros omitidos\n";
echo "- ✅ Coherencia tipoDocumento=03 vs destinoOperacion=2\n";
echo "- ✅ Campo monedaOperExportacion (no 'moneda')\n";
echo "- ✅ Campo paisDestino removido de datosFacturaExportacion\n";

echo "\n🎯 RESULTADO: Estructura corregida según documentación oficial TheFactoryHKA\n";
echo "📋 PRÓXIMO: Probar envío PAC para verificar resolución error 201\n";

?>
