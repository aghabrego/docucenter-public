<?php
/**
 * Test para verificar el fix del campo datosFacturaExportacion
 *
 * Este script valida que el AlanubeFormatterHelper ahora genere
 * correctamente el campo 'datosFacturaExportacion' requerido por el PAC
 * en lugar del campo 'exportation' que causaba el error.
 *
 * Uso:
 * php docs/testing/test-datos-factura-exportacion-fix.php
 */

require_once 'vendor/autoload.php';

use App\Helpers\AlanubeFormatterHelper;
use App\Models\Pacconnection;

echo "=== TEST: FIX DATOS FACTURA EXPORTACIÓN ===\n\n";

// Simular datos de exportación como los genera Create.php
$testData = [
    'dGen' => [
        'iDoc' => '03', // Factura de exportación
        'iTipoDoc' => '01',
        'iDest' => '2', // Destino extranjero
        'gDatRec' => [
            'cTipoRec' => '4', // Extranjero
            'dNombRec' => 'EXPORT CLIENT USA',
            'dDirecRec' => '123 Export Street',
            'cPaisRec' => 'US',
            'gIdExt' => [
                'tipoIdentificacion' => '01',
                'dIdExt' => 'PASSPORT123456',
                'dPaisExt' => 'US'
            ]
        ]
    ],
    'gFExp' => [
        'cCondEntr' => 'FOB',
        'cMoneda' => 'USD',
        'dCambio' => 1.0,
        'dVTotEst' => 1000.50,
        'dPuertoEmbarq' => 'Puerto de Balboa',
        'cPaisDest' => 'US'
    ]
];

echo "1. DATOS DE ENTRADA:\n";
echo "   - Tipo documento: " . $testData['dGen']['iDoc'] . " (Exportación)\n";
echo "   - Tiene gFExp: " . (isset($testData['gFExp']) ? 'SÍ' : 'NO') . "\n";
echo "   - INCOTERM: " . ($testData['gFExp']['cCondEntr'] ?? 'N/A') . "\n";
echo "   - Moneda: " . ($testData['gFExp']['cMoneda'] ?? 'N/A') . "\n\n";

// Simular conexión PAC Panamá usando datos directos (sin model)
echo "2. PROCESAMIENTO:\n";

// Formatear con AlanubeFormatterHelper (usando null para connection)
// y forzando detección de Panamá via estructura de datos
try {
    // Agregar indicadores para detectar Panamá
    $testData['_country_hint'] = 'PA';

    $formattedData = AlanubeFormatterHelper::format($testData, null);

    echo "   ✅ Formateo exitoso\n";

    // Verificar que ahora tiene datosFacturaExportacion
    $hasDatosFacturaExportacion = isset($formattedData['datosFacturaExportacion']);
    $hasOldExportation = isset($formattedData['exportation']);

    echo "\n3. VERIFICACIÓN DEL FIX:\n";
    echo "   - ¿Tiene 'datosFacturaExportacion'?: " . ($hasDatosFacturaExportacion ? '✅ SÍ' : '❌ NO') . "\n";
    echo "   - ¿Tiene 'exportation' (viejo)?: " . ($hasOldExportation ? '❌ SÍ (ERROR)' : '✅ NO') . "\n";

    if ($hasDatosFacturaExportacion) {
        echo "\n4. CONTENIDO DE datosFacturaExportacion:\n";
        $exportData = $formattedData['datosFacturaExportacion'];
        echo "   - incoterm: " . ($exportData['incoterm'] ?? 'N/A') . "\n";
        echo "   - currency: " . ($exportData['currency'] ?? 'N/A') . "\n";
        echo "   - exchangeRate: " . ($exportData['exchangeRate'] ?? 'N/A') . "\n";
        echo "   - amount: " . ($exportData['amount'] ?? 'N/A') . "\n";
        echo "   - port: " . ($exportData['port'] ?? 'N/A') . "\n";
    }

    echo "\n5. ESTRUCTURA COMPLETA (JSON):\n";
    echo json_encode($formattedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // Resultado final
    if ($hasDatosFacturaExportacion && !$hasOldExportation) {
        echo "\n🎉 ¡FIX EXITOSO!\n";
        echo "   El error 'El campo datosFacturaExportacion es requerido' debería estar RESUELTO\n";
        echo "   El PAC Alanube ahora recibirá el campo con el nombre correcto\n";
    } else {
        echo "\n❌ FIX NO COMPLETADO\n";
        echo "   Revisar el AlanubeFormatterHelper\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error en formateo: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "RESUMEN:\n";
echo "- Error original: 'El campo datosFacturaExportacion es requerido'\n";
echo "- Causa: PAC esperaba 'datosFacturaExportacion' pero recibía 'exportation'\n";
echo "- Fix aplicado: Cambiar nombre del campo en AlanubeFormatterHelper\n";
echo "- Resultado esperado: Error resuelto para facturas de exportación\n";
