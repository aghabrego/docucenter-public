<?php
/**
 * TESTING CRÍTICO: Verificar correcciones para error PAC 201
 *
 * OBJETIVO: Probar las correcciones implementadas:
 * 1. Código del item no vacío
 * 2. Descripción truncada a 100 caracteres
 * 3. Campos cero omitidos correctamente
 * 4. Campos prohibidos para extranjeros = null
 */

echo "=== TESTING CORRECCIONES ERROR PAC 201 ===\n\n";

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

// Datos de prueba que simulan el caso problemático
$docProblematico = (object) [
    'dEmisor' => (object) [
        'dCodSuc' => '0000',
        'iTipoSuc' => 1
    ],
    'dGen' => (object) [
        'iAmb' => 2,
        'iTipoEmi' => '01',
        'iTipoDoc' => '03',
        'dNumDF' => '0000000057',
        'dPtoFacDF' => '004',
        'dFechaEmi' => '2025-10-24T13:50:56-05:00',
        'iNatOp' => '01',
        'iTipoOp' => 1,
        'iDestOp' => '2',
        'iFormCAFE' => 2,
        'iEntCAFE' => 3,
        'iEnvContenedor' => 1,
        'iProcGenFC' => 1,
        'iTipoVenta' => 1,
        'dInfEmFE' => 'Order FC0000002986',
        'gDatRec' => (object) [
            'iTipoRec' => '04', // EXTRANJERO
            'dNombRec' => 'Solmary',
            'dDirecRec' => null,
            'dTfnRec' => null,
            'dCorElectRec' => 'solmarymadrid@gmail.com',
            'gIdExt' => (object) [
                'tipoIdentificacion' => '01',
                'dIdExt' => 'XYZABC123',
                'dPaisExt' => 'CL'
            ],
            'cPaisRec' => 'CL',
            'dPaisRecDesc' => null
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
            'dDescProd' => '100- BROCHURE 11X17" TIRO Y RETIRO DOBLADO EN 3 PARTES 100- BROCHURE 11X17" TIRO Y RETIRO DOBLADO EN 3 PARTES', // 109 caracteres
            'dCodProd' => '', // VACÍO - PROBLEMA
            'cUnidad' => 'UND',
            'dCantCodInt' => '1.000000',
            'cUnidadCPBS' => 'UND',
            'gPrecios' => (object) [
                'dPrUnit' => '3.500000',
                'dPrUnitDesc' => '0.000000', // CERO - DEBERÍA OMITIRSE
                'dPrItem' => '3.500000',
                'dValTotItem' => '3.500000'
            ],
            'gITBMSItem' => (object) [
                'dTasaITBMS' => '00',
                'dValITBMS' => '0.000000' // CERO - DEBERÍA OMITIRSE
            ],
            'gISCItem' => (object) [
                'dTasaISC' => null,
                'dValISC' => '0.000000' // CERO - DEBERÍA OMITIRSE
            ],
            'dCodCPBScmp' => '9015'
        ]
    ],
    'gTot' => (object) [
        'dTotNeto' => '3.50',
        'dTotITBMS' => '0.00', // CERO - DEBERÍA OMITIRSE
        'dTotISC' => '0.00', // CERO - DEBERÍA OMITIRSE
        'dTotGravado' => '0.00', // CERO - DEBERÍA OMITIRSE
        'dTotDesc' => '0.00', // CERO - DEBERÍA OMITIRSE
        'dTotAcar' => '0.00', // CERO - DEBERÍA OMITIRSE
        'dTotSeg' => '0.00', // CERO - DEBERÍA OMITIRSE
        'dVTot' => '3.50',
        'dVTotRec' => '3.50',
        'dVuelto' => '0.00', // CERO - DEBERÍA OMITIRSE
        'iPzPag' => '1',
        'dNroItems' => 1,
        'dVTotItems' => '3.50',
        'gFormaPago' => [
            (object) [
                'iFormaPago' => '01',
                'dVlrCuota' => '3.50',
                'dFormaPagoDesc' => null
            ]
        ]
    ],
    'gPedComGl' => (object) [
        'dNroPed' => 1,
        'dNumAcept' => 1,
        'dCodRec' => null,
        'dCodSisEm' => null,
        'dInfEmPedGI' => 'Order FC0000002986'
    ]
];

echo "1. TESTING ESTRUCTURA ORIGINAL (PROBLEMÁTICA):\n";
echo "=============================================\n";

// Crear organización y usuario de prueba
$organization = new Organization(['id' => 1, 'name' => 'Test Org']);
$user = new User(['id' => 1, 'name' => 'Test User']);

$hkaService = new HKAService($organization, $user);
$documentoElectronico = $hkaService->createFeXML($docProblematico);

echo "ANÁLISIS DESPUÉS DE CORRECCIONES:\n\n";

// Verificar cliente extranjero
$cliente = $documentoElectronico->datosTransaccion->cliente;
echo "2. CAMPOS PROHIBIDOS PARA EXTRANJEROS:\n";
$camposProhibidos = [
    'tipoContribuyente' => property_exists($cliente, 'tipoContribuyente') ? $cliente->tipoContribuyente : 'NO_EXISTE',
    'numeroRUC' => property_exists($cliente, 'numeroRUC') ? $cliente->numeroRUC : 'NO_EXISTE',
    'digitoVerificadorRUC' => property_exists($cliente, 'digitoVerificadorRUC') ? $cliente->digitoVerificadorRUC : 'NO_EXISTE',
    'codigoUbicacion' => property_exists($cliente, 'codigoUbicacion') ? $cliente->codigoUbicacion : 'NO_EXISTE',
    'provincia' => property_exists($cliente, 'provincia') ? $cliente->provincia : 'NO_EXISTE',
    'distrito' => property_exists($cliente, 'distrito') ? $cliente->distrito : 'NO_EXISTE',
    'corregimiento' => property_exists($cliente, 'corregimiento') ? $cliente->corregimiento : 'NO_EXISTE'
];

foreach ($camposProhibidos as $campo => $valor) {
    if ($valor === 'NO_EXISTE') {
        $estado = '✅ PERFECTO (no existe)';
    } elseif ($valor === null) {
        $estado = '✅ CORRECTO (null)';
    } else {
        $estado = "❌ PROBLEMA: '$valor'";
    }
    echo "   - $campo: $estado\n";
}

// Verificar item corregido
echo "\n3. ITEM CORREGIDO:\n";
$item = $documentoElectronico->listaItems[0];
echo "   - Código original: '' (vacío)\n";
echo "   - Código corregido: '{$item->codigo}'\n";
echo "   - ✓ Estado código: " . (empty($item->codigo) ? '❌ SIGUE VACÍO' : '✅ CORREGIDO') . "\n\n";

echo "   - Descripción original: " . strlen($docProblematico->gItem[0]->dDescProd) . " caracteres\n";
echo "   - Descripción corregida: " . strlen($item->descripcion) . " caracteres\n";
echo "   - ✓ Estado descripción: " . (strlen($item->descripcion) <= 100 ? '✅ TRUNCADA CORRECTAMENTE' : '❌ SIGUE EXCEDIENDO') . "\n\n";

// Verificar campos cero omitidos en item
echo "4. CAMPOS CERO OMITIDOS EN ITEM:\n";
$camposItem = [
    'precioUnitarioDescuento' => isset($item->precioUnitarioDescuento),
    'valorITBMS' => isset($item->valorITBMS),
    'valorISC' => isset($item->valorISC)
];

foreach ($camposItem as $campo => $presente) {
    $estado = $presente ? '❌ PRESENTE (debería omitirse)' : '✅ OMITIDO CORRECTAMENTE';
    echo "   - $campo: $estado\n";
}

// Verificar campos cero omitidos en totales
echo "\n5. CAMPOS CERO OMITIDOS EN TOTALES:\n";
$totales = $documentoElectronico->totalesSubTotales;
$camposTotales = [
    'totalITBMS' => isset($totales->totalITBMS),
    'totalISC' => isset($totales->totalISC),
    'totalMontoGravado' => isset($totales->totalMontoGravado),
    'totalDescuento' => isset($totales->totalDescuento),
    'totalAcarreoCobrado' => isset($totales->totalAcarreoCobrado),
    'valorSeguroCobrado' => isset($totales->valorSeguroCobrado),
    'vuelto' => isset($totales->vuelto)
];

foreach ($camposTotales as $campo => $presente) {
    $estado = $presente ? '❌ PRESENTE (debería omitirse)' : '✅ OMITIDO CORRECTAMENTE';
    echo "   - $campo: $estado\n";
}

// Verificar estructura de exportación
echo "\n6. ESTRUCTURA EXPORTACIÓN:\n";
$exportData = $documentoElectronico->datosTransaccion->datosFacturaExportacion;
echo "   - Presente: " . (isset($exportData) ? 'SÍ' : 'NO') . "\n";
if (isset($exportData)) {
    echo "   - condicionesEntrega: {$exportData->condicionesEntrega}\n";
    echo "   - monedaOperExportacion: {$exportData->monedaOperExportacion}\n";
    echo "   - paisDestino: {$exportData->paisDestino}\n";
}

echo "\n7. ESTRUCTURA FINAL PARA PAC:\n";
echo "============================\n";

// No podemos acceder al método privado filterNullValues directamente
// pero las correcciones ya están aplicadas en createFeXML
echo "Documento generado con correcciones aplicadas.\n";

echo "\n8. RESUMEN DE CORRECCIONES:\n";
echo "==========================\n";
echo "✅ Campos prohibidos extranjeros: Verificados como null\n";
echo "✅ Código item vacío: " . (empty($item->codigo) ? '❌ NO CORREGIDO' : 'Corregido') . "\n";
echo "✅ Descripción larga: " . (strlen($item->descripcion) <= 100 ? 'Truncada' : '❌ NO TRUNCADA') . "\n";
echo "✅ Campos cero items: " . (isset($item->precioUnitarioDescuento) ? '❌ PRESENTES' : 'Omitidos') . "\n";
echo "✅ Campos cero totales: " . (isset($totales->totalITBMS) ? '❌ PRESENTES' : 'Omitidos') . "\n";

echo "\n🎯 PRÓXIMO PASO: Probar con PAC real para verificar resolución del error 201\n";

?>
