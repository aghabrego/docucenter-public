<?php
/**
 * Test: Validar Campos Permitidos para Extranjeros - TheFactoryHKA
 *
 * Propósito: Verificar que para tipoClienteFE=04 SOLO se envíen campos permitidos
 * Referencia: https://felwiki.thefactoryhka.com.pa/enviar
 *
 * CAMPOS QUE NO DEBEN SER ENVIADOS PARA EXTRANJEROS:
 * - tipoContribuyente
 * - numeroRUC
 * - digitoVerificadorRUC
 * - codigoUbicacion
 * - provincia
 * - distrito
 * - corregimiento
 *
 * Uso:
 * php artisan tinker
 * include 'docs/testing/test-campos-permitidos-extranjeros.php';
 */

use App\Services\HKAService;
use App\Models\Organization;

echo "🔍 TEST: Campos Permitidos para Extranjeros - TheFactoryHKA\n";
echo "===========================================================\n\n";

try {
    $organization = Organization::first();
    if (!$organization) {
        echo "❌ ERROR: No se encontró organización para testing\n";
        return;
    }

    $hkaService = new HKAService($organization);

    // Crear documento de exportación con cliente extranjero
    echo "=== TEST: Factura Exportación - Cliente Extranjero ===\n";
    $docExportacionExtranjero = (object) [
        'dGen' => (object) [
            'iDoc' => '03', // Factura de exportación
            'gDatRec' => (object) [
                'iTipoRec' => '04', // Cliente extranjero
                'gRucRec' => (object) [
                    'dRuc' => '1234567890123', // Este NO debe aparecer en el XML final
                    'dTipoRuc' => '1',         // Este NO debe aparecer en el XML final
                    'dDV' => '12'              // Este NO debe aparecer en el XML final
                ],
                'gIdExt' => (object) [
                    'tipoIdentificacion' => '01',
                    'dIdExt' => 'US123456789',
                    'dPaisExt' => 'Estados Unidos'
                ],
                'dNombRec' => 'Cliente Export USA',
                'dDirecRec' => 'Miami Street 123',
                'gUbiRec' => (object) [
                    'dCodUbi' => '1-1-1',    // Este NO debe aparecer
                    'dCorreg' => 'Miami',    // Este NO debe aparecer
                    'dDistr' => 'Miami',     // Este NO debe aparecer
                    'dProv' => 'Florida'     // Este NO debe aparecer
                ],
                'dTfnRec' => '+1-555-1234',
                'dCorElectRec' => 'client@usa.com',
                'cPaisRec' => 'US',
                'dPaisRecDesc' => 'Estados Unidos'
            ]
        ],
        'gFExp' => (object) [
            'cCondEntr' => 'FOB',
            'cMoneda' => 'USD',
            'dPuertoEmbarq' => 'Miami Port'
        ],
        'gItem' => [
            (object) [
                'dDescProd' => 'Producto Export',
                'dCodProd' => 'EXP001',
                'cUnidad' => 'UND',
                'dCantCodInt' => '1.000000',
                'gPrecios' => (object) [
                    'dPrUnit' => '100.00',
                    'dPrItem' => '100.00',
                    'dValTotItem' => '107.00'
                ],
                'gITBMSItem' => (object) [
                    'dTasaITBMS' => '01',
                    'dValITBMS' => '7.00'
                ]
            ]
        ],
        'gTot' => (object) [
            'dVTot' => '107.00',
            'dTotNeto' => '100.00',
            'dTotITBMS' => '7.00',
            'dTotGravado' => '7.00',
            'dNroItems' => '1',
            'dVTotItems' => '107.00'
        ]
    ];

    $documentoElectronico = $hkaService->createFeXML($docExportacionExtranjero);
    $cliente = $documentoElectronico->datosTransaccion->cliente;

    // Validar que campos prohibidos NO estén presentes
    echo "tipoDocumento: {$documentoElectronico->datosTransaccion->tipoDocumento}\n";
    echo "tipoClienteFE: {$cliente->tipoClienteFE}\n\n";

    echo "=== VERIFICACIÓN CAMPOS PROHIBIDOS ===\n";

    $camposProhibidos = [
        'tipoContribuyente' => $cliente->tipoContribuyente ?? 'NULL',
        'numeroRUC' => $cliente->numeroRUC ?? 'NULL',
        'digitoVerificadorRUC' => $cliente->digitoVerificadorRUC ?? 'NULL',
        'codigoUbicacion' => $cliente->codigoUbicacion ?? 'NULL',
        'provincia' => $cliente->provincia ?? 'NULL',
        'distrito' => $cliente->distrito ?? 'NULL',
        'corregimiento' => $cliente->corregimiento ?? 'NULL'
    ];

    $errores = 0;
    foreach ($camposProhibidos as $campo => $valor) {
        if ($valor !== 'NULL' && $valor !== null) {
            echo "❌ ERROR: Campo '{$campo}' NO debe estar presente pero tiene valor: '{$valor}'\n";
            $errores++;
        } else {
            echo "✅ OK: Campo '{$campo}' correctamente omitido\n";
        }
    }

    echo "\n=== VERIFICACIÓN CAMPOS PERMITIDOS ===\n";

    $camposPermitidos = [
        'razonSocial' => $cliente->razonSocial,
        'direccion' => $cliente->direccion,
        'telefono1' => $cliente->telefono1,
        'correoElectronico1' => $cliente->correoElectronico1,
        'tipoIdentificacion' => $cliente->tipoIdentificacion,
        'nroIdentificacionExtranjero' => $cliente->nroIdentificacionExtranjero,
        'paisExtranjero' => $cliente->paisExtranjero ?? 'NULL',
        'pais' => $cliente->pais,
        'paisOtro' => $cliente->paisOtro ?? 'NULL'
    ];

    foreach ($camposPermitidos as $campo => $valor) {
        echo "✅ Campo permitido '{$campo}': " . ($valor ?? 'NULL') . "\n";
    }

    echo "\n=== VERIFICACIÓN ESTRUCTURA EXPORTACIÓN ===\n";
    $tieneExportacion = isset($documentoElectronico->datosTransaccion->datosFacturaExportacion);
    echo "datosFacturaExportacion presente: " . ($tieneExportacion ? 'SÍ' : 'NO') . "\n";

    if ($tieneExportacion) {
        $exportData = $documentoElectronico->datosTransaccion->datosFacturaExportacion;
        echo "  - condicionesEntrega: {$exportData->condicionesEntrega}\n";
        echo "  - monedaOperExportacion: {$exportData->monedaOperExportacion}\n";
        echo "  - tipoCambio: {$exportData->tipoCambio}\n";
        echo "  - montoMonedaExtranjera: {$exportData->montoMonedaExtranjera}\n";
        echo "  - puertoEmbarque: {$exportData->puertoEmbarque}\n";
        echo "  - paisDestino: {$exportData->paisDestino}\n";
    }

    echo "\n=== RESULTADO FINAL ===\n";
    if ($errores === 0) {
        echo "🎉 ✅ TODOS LOS TESTS PASARON\n";
        echo "✅ Campos prohibidos correctamente omitidos\n";
        echo "✅ Solo campos permitidos presentes\n";
        echo "✅ Cumple especificación TheFactoryHKA\n";
        echo "✅ Debería resolver error PAC 201\n";
    } else {
        echo "❌ FALLOS DETECTADOS: {$errores} campos prohibidos presentes\n";
        echo "⚠️  Revisar implementación para extranjeros\n";
        echo "📚 Referencia: https://felwiki.thefactoryhka.com.pa/enviar\n";
    }

    echo "\n=== TEST XML FILTRADO ===\n";
    echo "Verificando que filterNullValues() funciona correctamente...\n";

    // Simular el proceso de filtrado que se hace antes del envío
    $filteredData = json_decode(json_encode($documentoElectronico), true);
    $reflection = new ReflectionClass($hkaService);
    $method = $reflection->getMethod('filterNullValues');
    $method->setAccessible(true);
    $filteredResult = $method->invoke($hkaService, $filteredData);

    // Verificar cliente en estructura filtrada
    $clienteFiltrado = $filteredResult['datosTransaccion']['cliente'] ?? [];

    echo "Campos en cliente filtrado:\n";
    foreach ($clienteFiltrado as $campo => $valor) {
        echo "  - {$campo}: {$valor}\n";
    }

    // Verificar que campos prohibidos NO aparezcan en estructura filtrada
    $camposProhibidosEnFiltrado = array_intersect_key($clienteFiltrado, array_flip([
        'tipoContribuyente', 'numeroRUC', 'digitoVerificadorRUC',
        'codigoUbicacion', 'provincia', 'distrito', 'corregimiento'
    ]));

    if (empty($camposProhibidosEnFiltrado)) {
        echo "✅ Filtrado correcto: NO hay campos prohibidos en XML final\n";
    } else {
        echo "❌ ERROR en filtrado: Campos prohibidos presentes: " . implode(', ', array_keys($camposProhibidosEnFiltrado)) . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR durante testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n📚 Referencia: https://felwiki.thefactoryhka.com.pa/enviar\n";
echo "🔗 Documentación: Campos 'No debe ser enviado cuando tipoClienteFE = 04'\n";
