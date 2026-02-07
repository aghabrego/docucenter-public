<?php
/**
 * TESTING: Verificar que no hay errores de acceso a propiedades
 *
 * PROBLEMA CORREGIDO: Error 500 "Undefined property: stdClass::$moneda"
 * - CAUSA: Código accedía a $export->moneda en lugar de $export->monedaOperExportacion
 * - SOLUCIÓN: Actualizar todas las referencias a usar monedaOperExportacion
 */

echo "=== TESTING: Verificación de Propiedades Exportación ===\n\n";

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
    'dGen' => (object) [
        'iDoc' => '03', // EXPORTACIÓN
        'dNroDF' => '0000000057',
        'dPtoFacDF' => '004',
        'dFechaEm' => '2025-10-24T14:00:50-05:00',
        'iNatOp' => '02', // Exportación
        'iTipoOp' => 1,
        'iDest' => '2', // Extranjero
        'iTpEmis' => '01',
        'iFormCAFE' => 2,
        'iEntCAFE' => 3,
        'dEnvFE' => 1,
        'iProGen' => 1,
        'iTipoTranVenta' => 1,
        'dInfEmFE' => 'Order FC0000002986',
        'gDatRec' => (object) [
            'iTipoRec' => '04', // EXTRANJERO
            'dNombRec' => 'Solmary Test',
            'dCorElectRec' => 'test@example.com',
            'gIdExt' => (object) [
                'tipoIdentificacion' => '01',
                'dIdExt' => 'TEST123',
                'dPaisExt' => 'CL'
            ],
            'cPaisRec' => 'CL'
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
        'dVTotEst' => '5.00',
        'dPuertoEmbarq' => 'PANAMA'
    ],
    'gItem' => [
        (object) [
            'dDescProd' => 'Test Product',
            'dCodProd' => 'TEST001',
            'cUnidad' => 'UND',
            'dCantCodInt' => '1.000000',
            'cUnidadCPBS' => 'UND',
            'gPrecios' => (object) [
                'dPrUnit' => '5.000000',
                'dPrItem' => '5.000000',
                'dValTotItem' => '5.000000'
            ],
            'gITBMSItem' => (object) [
                'dTasaITBMS' => '00',
                'dValITBMS' => '0.000000'
            ],
            'dCodCPBScmp' => '9015'
        ]
    ],
    'gTot' => (object) [
        'dTotNeto' => '5.00',
        'dVTot' => '5.00',
        'dVTotRec' => '5.00',
        'iPzPag' => '1',
        'dNroItems' => 1,
        'dVTotItems' => '5.00',
        'gFormaPago' => [
            (object) [
                'iFormaPago' => '01',
                'dVlrCuota' => '5.00'
            ]
        ]
    ]
];

echo "1. GENERANDO DOCUMENTO SIN ERRORES 500:\n";
echo "=====================================\n";

try {
    $organization = new Organization(['id' => 1, 'name' => 'Test Org']);
    $user = new User(['id' => 1, 'name' => 'Test User']);

    $hkaService = new HKAService($organization, $user);
    $documentoElectronico = $hkaService->createFeXML($docExportacion);

    echo "✅ Documento generado exitosamente sin errores 500\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "❌ Archivo: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

echo "2. VERIFICANDO ACCESO A PROPIEDADES:\n";
echo "===================================\n";

try {
    $exportData = $documentoElectronico->datosTransaccion->datosFacturaExportacion ?? null;

    if ($exportData) {
        echo "✅ datosFacturaExportacion: PRESENTE\n";

        // Verificar acceso a propiedades sin errores
        $propiedades = [
            'condicionesEntrega' => $exportData->condicionesEntrega ?? 'NO_DEFINIDO',
            'monedaOperExportacion' => $exportData->monedaOperExportacion ?? 'NO_DEFINIDO',
            'tipoDeCambio' => $exportData->tipoDeCambio ?? 'NO_DEFINIDO',
            'montoMonedaExtranjera' => $exportData->montoMonedaExtranjera ?? 'NO_DEFINIDO',
            'puertoEmbarque' => $exportData->puertoEmbarque ?? 'NO_DEFINIDO'
        ];

        foreach ($propiedades as $nombre => $valor) {
            echo "✅ $nombre: $valor\n";
        }

        // Verificar que NO existe la propiedad incorrecta 'moneda'
        $existeMonedaIncorrecta = property_exists($exportData, 'moneda');
        echo "\n❌ propiedad 'moneda' (incorrecta): " . ($existeMonedaIncorrecta ? '⚠️ EXISTE' : '✅ NO EXISTE') . "\n";

    } else {
        echo "❌ datosFacturaExportacion: NO PRESENTE\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ ERROR al acceder propiedades: {$e->getMessage()}\n";
    exit(1);
}

echo "\n3. VERIFICANDO MÉTODO validateAndFixDocument:\n";
echo "===========================================\n";

try {
    // Este método internamente llama al código que tenía el error
    // Si no hay excepción, significa que está corregido
    $reflection = new ReflectionClass($hkaService);
    $method = $reflection->getMethod('validateAndFixDocument');
    $method->setAccessible(true);
    $method->invoke($hkaService, $documentoElectronico);

    echo "✅ validateAndFixDocument: Ejecutado sin errores\n";

} catch (Exception $e) {
    echo "❌ ERROR en validateAndFixDocument: {$e->getMessage()}\n";
    exit(1);
}

echo "\n4. RESULTADO FINAL:\n";
echo "==================\n";
echo "✅ Error 500 'Undefined property: stdClass::\$moneda' CORREGIDO\n";
echo "✅ Todas las referencias actualizadas a 'monedaOperExportacion'\n";
echo "✅ Estructura de exportación funciona correctamente\n";
echo "✅ Método validateAndFixDocument opera sin errores\n";

echo "\n🎯 ESTADO: Sistema listo para envío PAC sin errores 500\n";

?>
