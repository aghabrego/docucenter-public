<?php
/**
 * Test Command: Validar Regla tipoContribuyente TheFactoryHKA
 *
 * Propósito: Verificar que extranjeros NO incluyan tipoContribuyente
 * Referencia: https://felwiki.thefactoryhka.com.pa/
 *
 * Uso:
 * php artisan tinker
 * include 'docs/testing/test-tipoccontribuyente-simple.php';
 */

use App\Services\HKAService;
use App\Models\Organization;

echo "🔍 TEST: Campo tipoContribuyente según Regla Oficial TheFactoryHKA\n";
echo "=================================================================\n\n";

try {
    $organization = Organization::first();
    if (!$organization) {
        echo "❌ ERROR: No se encontró organización para testing\n";
        return;
    }

    $hkaService = new HKAService($organization);

    // Test 1: Cliente Nacional
    echo "=== TEST 1: Cliente Nacional (tipoClienteFE=02) ===\n";
    $docNacional = (object) [
        'dGen' => (object) [
            'gDatRec' => (object) [
                'iTipoRec' => '02', // Cliente nacional
                'gRucRec' => (object) [
                    'dRuc' => '1234567890123',
                    'dTipoRuc' => '1',
                    'dDV' => '12'
                ],
                'dNombRec' => 'Cliente Nacional SA'
            ]
        ],
        'gItem' => [],
        'gTot' => (object) ['dVTot' => '100.00']
    ];

    $docElectronicoNacional = $hkaService->createFeXML($docNacional);
    $clienteNacional = $docElectronicoNacional->datosTransaccion->cliente;

    echo "tipoClienteFE: {$clienteNacional->tipoClienteFE}\n";
    echo "numeroRUC: {$clienteNacional->numeroRUC}\n";
    echo "tipoContribuyente: " . ($clienteNacional->tipoContribuyente ?? 'NULL') . "\n";

    if ($clienteNacional->tipoClienteFE === '02' && !empty($clienteNacional->tipoContribuyente)) {
        echo "✅ CORRECTO: Cliente nacional tiene tipoContribuyente\n\n";
        $test1_passed = true;
    } else {
        echo "❌ ERROR: Cliente nacional debería tener tipoContribuyente\n\n";
        $test1_passed = false;
    }

    // Test 2: Cliente Extranjero
    echo "=== TEST 2: Cliente Extranjero (tipoClienteFE=04) ===\n";
    $docExtranjero = (object) [
        'dGen' => (object) [
            'gDatRec' => (object) [
                'iTipoRec' => '04', // Cliente extranjero
                'gRucRec' => (object) [
                    'dRuc' => null,
                    'dDV' => null
                ],
                'gIdExt' => (object) [
                    'tipoIdentificacion' => '01',
                    'dIdExt' => 'US123456789',
                    'dPaisExt' => 'Estados Unidos'
                ],
                'dNombRec' => 'Cliente Extranjero USA',
                'cPaisRec' => 'US'
            ]
        ],
        'gItem' => [],
        'gTot' => (object) ['dVTot' => '100.00']
    ];

    $docElectronicoExtranjero = $hkaService->createFeXML($docExtranjero);
    $clienteExtranjero = $docElectronicoExtranjero->datosTransaccion->cliente;

    echo "tipoClienteFE: {$clienteExtranjero->tipoClienteFE}\n";
    echo "numeroRUC: '{$clienteExtranjero->numeroRUC}'\n";
    echo "tipoContribuyente: " . ($clienteExtranjero->tipoContribuyente ?? 'NULL') . "\n";

    if ($clienteExtranjero->tipoClienteFE === '04' && $clienteExtranjero->tipoContribuyente === null) {
        echo "✅ CORRECTO: Cliente extranjero NO tiene tipoContribuyente\n\n";
        $test2_passed = true;
    } else {
        echo "❌ ERROR: Cliente extranjero NO debería tener tipoContribuyente\n\n";
        $test2_passed = false;
    }

    // Test 3: Factura Exportación
    echo "=== TEST 3: Factura Exportación (tipo 03 + extranjero) ===\n";
    $docExportacion = (object) [
        'dGen' => (object) [
            'iDoc' => '03', // Factura de exportación
            'gDatRec' => (object) [
                'iTipoRec' => '04', // Cliente extranjero
                'gRucRec' => (object) [
                    'dRuc' => '',
                    'dDV' => ''
                ],
                'gIdExt' => (object) [
                    'tipoIdentificacion' => '99',
                    'dIdExt' => '123456789'
                ],
                'dNombRec' => 'TFHKA Export Client',
                'cPaisRec' => 'VE'
            ]
        ],
        'gFExp' => (object) [
            'cCondEntr' => 'EXW',
            'cMoneda' => 'USD',
            'dPuertoEmbarq' => 'Prueba'
        ],
        'gItem' => [],
        'gTot' => (object) ['dVTot' => '5.94']
    ];

    $docElectronicoExportacion = $hkaService->createFeXML($docExportacion);
    $clienteExportacion = $docElectronicoExportacion->datosTransaccion->cliente;

    echo "tipoDocumento: {$docElectronicoExportacion->datosTransaccion->tipoDocumento}\n";
    echo "tipoClienteFE: {$clienteExportacion->tipoClienteFE}\n";
    echo "tipoContribuyente: " . ($clienteExportacion->tipoContribuyente ?? 'NULL') . "\n";
    echo "datosFacturaExportacion: " . (isset($docElectronicoExportacion->datosTransaccion->datosFacturaExportacion) ? 'PRESENTE' : 'AUSENTE') . "\n";

    if ($docElectronicoExportacion->datosTransaccion->tipoDocumento === '03' &&
        $clienteExportacion->tipoClienteFE === '04' &&
        $clienteExportacion->tipoContribuyente === null) {
        echo "✅ CORRECTO: Factura exportación + extranjero SIN tipoContribuyente\n\n";
        $test3_passed = true;
    } else {
        echo "❌ ERROR: Factura exportación con extranjero debe omitir tipoContribuyente\n\n";
        $test3_passed = false;
    }

    // Resultados Finales
    echo "=== RESULTADOS FINALES ===\n";

    if ($test1_passed) {
        echo "✅ TEST 1 PASÓ: Cliente nacional con tipoContribuyente\n";
    } else {
        echo "❌ TEST 1 FALLÓ: Cliente nacional sin tipoContribuyente\n";
    }

    if ($test2_passed) {
        echo "✅ TEST 2 PASÓ: Cliente extranjero sin tipoContribuyente\n";
    } else {
        echo "❌ TEST 2 FALLÓ: Cliente extranjero con tipoContribuyente\n";
    }

    if ($test3_passed) {
        echo "✅ TEST 3 PASÓ: Factura exportación sin tipoContribuyente\n";
    } else {
        echo "❌ TEST 3 FALLÓ: Factura exportación con tipoContribuyente\n";
    }

    echo "\n";
    if ($test1_passed && $test2_passed && $test3_passed) {
        echo "🎉 TODOS LOS TESTS PASARON\n";
        echo "✅ Regla tipoContribuyente implementada correctamente\n";
        echo "✅ Conformidad con documentación TheFactoryHKA\n";
    } else {
        echo "❌ ALGUNOS TESTS FALLARON\n";
        echo "⚠️  Revisar implementación de regla tipoContribuyente\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR durante testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n📚 Referencia: https://felwiki.thefactoryhka.com.pa/\n";
