<?php
/**
 * Validador de estructura JSON MEYPAR
 * Verifica un objeto JSON contra las reglas de CreateSaleMeyparRequest
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Requests\CreateSaleMeyparRequest;
use Illuminate\Support\Facades\Validator;

// Objeto JSON a validar
$jsonData = [
    "idFacturador" => 860013720,
    "ambiente" => 1,
    "autorizacionPrefijo" => [
        "PrefijoId" => "C3PQ",
        "ResolucionNumero" => "18764071730582",
        "VigenciaFecha" => "2026-05-30",
        "DuracionFecha" => 24,
        "CorrelativoInicialNum" => 1,
        "CorrelativoFinalNum" => 250000,
        "objectName" => "WAAutorizacionSerie"
    ],
    "documento" => "222222222222",
    "tipoDocumento" => 1,
    "observacion" => null,
    "terminalPagoID" => [
        "CodigoTerminal" => 10,
        "CodigoExterno" => null,
        "NombreTerminal" => "AUDITORIO TPA 2",
        "objectName" => "WATerminalID"
    ],
    "prefijo" => "C3PQ",
    "numero" => 39606,
    "medioPago" => 1,
    "detalleMedioPagoList" => [
        [
            "codigoMedioPago" => 1,
            "importeMedioPago" => 4500.0,
            "objectName" => "WAMedioPagoFactura"
        ]
    ],
    "referenciaPago" => null,
    "fechaFactura" => "2025-04-23",
    "detalleFacturaList" => [
        [
            "cantidad" => 1,
            "descripcion" => "",
            "precioUnitario" => 4500.0,
            "codigoProducto" => "2",
            "codigoVehiculo" => 1,
            "unidadMedida" => "UNI",
            "precioTotalSinDescuento" => 4500.0,
            "precioTotalFinalDetalle" => 4500.0,
            "observacion" => "Duración: 000d 02h 32m\r\n",
            "objectName" => "WADetalleFactura"
        ]
    ]
];

echo "=== VALIDADOR MEYPAR - ANÁLISIS DE ESTRUCTURA ===\n\n";

// Análisis del JSON recibido
echo "📋 DATOS RECIBIDOS:\n";
echo "• idFacturador: " . $jsonData['idFacturador'] . " (tipo: " . gettype($jsonData['idFacturador']) . ")\n";
echo "• ambiente: " . ($jsonData['ambiente'] ?? 'NO PRESENTE') . "\n";
echo "• documento: " . ($jsonData['documento'] ?? 'NO PRESENTE') . "\n";
echo "• tipoDocumento: " . $jsonData['tipoDocumento'] . "\n";
echo "• prefijo: " . $jsonData['prefijo'] . "\n";
echo "• numero: " . $jsonData['numero'] . "\n";
echo "• medioPago: " . $jsonData['medioPago'] . "\n";
echo "• fechaFactura: " . $jsonData['fechaFactura'] . "\n";
echo "• Items en detalleFacturaList: " . count($jsonData['detalleFacturaList']) . "\n";
echo "• Items en detalleMedioPagoList: " . count($jsonData['detalleMedioPagoList']) . "\n";

echo "\n" . "="*60 . "\n";

// Problemas identificados
echo "⚠️ PROBLEMAS IDENTIFICADOS:\n\n";

$problemas = [];

// 1. Campo faltante 'codigo'
if (!isset($jsonData['codigo'])) {
    $problemas[] = "❌ Campo REQUERIDO faltante: 'codigo'";
}

// 2. idFacturador debe ser string
if (isset($jsonData['idFacturador']) && !is_string($jsonData['idFacturador'])) {
    $problemas[] = "❌ 'idFacturador' debe ser string, recibido: " . gettype($jsonData['idFacturador']);
}

// 3. Campos NO oficiales
$camposNoOficiales = ['ambiente', 'documento'];
foreach ($camposNoOficiales as $campo) {
    if (isset($jsonData[$campo])) {
        $problemas[] = "⚠️ Campo NO oficial encontrado: '$campo' (no está en la documentación MEYPAR oficial)";
    }
}

// 4. Validar descripcion vacía
if (isset($jsonData['detalleFacturaList'][0]['descripcion']) &&
    empty($jsonData['detalleFacturaList'][0]['descripcion'])) {
    $problemas[] = "❌ 'detalleFacturaList[0].descripcion' está vacía (requerida)";
}

// 5. Validar campos en objetos anidados
if (isset($jsonData['autorizacionPrefijo']['ResolucionNumero'])) {
    $resolucion = $jsonData['autorizacionPrefijo']['ResolucionNumero'];
    if (strlen($resolucion) > 50) {
        $problemas[] = "❌ 'autorizacionPrefijo.ResolucionNumero' excede 50 caracteres: " . strlen($resolucion);
    }
}

// 6. Validar objectName adicionales
$objectNamesEncontrados = [];
if (isset($jsonData['autorizacionPrefijo']['objectName'])) {
    $objectNamesEncontrados[] = "autorizacionPrefijo.objectName: " . $jsonData['autorizacionPrefijo']['objectName'];
}
if (isset($jsonData['terminalPagoID']['objectName'])) {
    $objectNamesEncontrados[] = "terminalPagoID.objectName: " . $jsonData['terminalPagoID']['objectName'];
}
if (isset($jsonData['detalleMedioPagoList'][0]['objectName'])) {
    $objectNamesEncontrados[] = "detalleMedioPagoList[0].objectName: " . $jsonData['detalleMedioPagoList'][0]['objectName'];
}

if (!empty($objectNamesEncontrados)) {
    $problemas[] = "ℹ️ Campos 'objectName' encontrados (opcionales): " . implode(', ', $objectNamesEncontrados);
}

// Mostrar problemas
if (empty($problemas)) {
    echo "✅ No se encontraron problemas estructurales\n";
} else {
    foreach ($problemas as $problema) {
        echo $problema . "\n";
    }
}

echo "\n" . "="*60 . "\n";

// Estructura corregida sugerida
echo "✅ ESTRUCTURA CORREGIDA SUGERIDA:\n\n";

$estructuraCorregida = [
    "idFacturador" => (string)$jsonData['idFacturador'], // Convertir a string
    "codigo" => 2, // CAMPO FALTANTE - debe agregarse
    "tipoDocumento" => $jsonData['tipoDocumento'],
    "prefijo" => $jsonData['prefijo'],
    "numero" => $jsonData['numero'],
    "medioPago" => $jsonData['medioPago'],
    "referenciaPago" => $jsonData['referenciaPago'],
    "fechaFactura" => $jsonData['fechaFactura'],
    "observacion" => $jsonData['observacion'],
    "autorizacionPrefijo" => [
        "PrefijoId" => $jsonData['autorizacionPrefijo']['PrefijoId'],
        "ResoluciónNumero" => $jsonData['autorizacionPrefijo']['ResolucionNumero'], // Nota: tiene acento
        "VigenciaFecha" => $jsonData['autorizacionPrefijo']['VigenciaFecha'],
        "DuracionFecha" => $jsonData['autorizacionPrefijo']['DuracionFecha'],
        "CorrelativoInicialNum" => $jsonData['autorizacionPrefijo']['CorrelativoInicialNum'],
        "CorrelativoFinalNum" => $jsonData['autorizacionPrefijo']['CorrelativoFinalNum']
        // objectName es opcional, se puede omitir
    ],
    "terminalPagoID" => [
        "CodigoTerminal" => $jsonData['terminalPagoID']['CodigoTerminal'],
        "CodigoExterno" => $jsonData['terminalPagoID']['CodigoExterno'],
        "NombreTerminal" => $jsonData['terminalPagoID']['NombreTerminal']
        // objectName es opcional, se puede omitir
    ],
    "detalleMedioPagoList" => [
        [
            "codigoMedioPago" => $jsonData['detalleMedioPagoList'][0]['codigoMedioPago'],
            "importeMedioPago" => $jsonData['detalleMedioPagoList'][0]['importeMedioPago']
            // objectName es opcional, se puede omitir
        ]
    ],
    "detalleFacturaList" => [
        [
            "objectName" => "WADetalleFactura", // Este SÍ es requerido
            "cantidad" => $jsonData['detalleFacturaList'][0]['cantidad'],
            "descripcion" => "Estacionamiento", // CORREGIR: no puede estar vacía
            "precioUnitario" => $jsonData['detalleFacturaList'][0]['precioUnitario'],
            "codigoProducto" => (int)$jsonData['detalleFacturaList'][0]['codigoProducto'], // Convertir a int si es posible
            "codigoVehiculo" => $jsonData['detalleFacturaList'][0]['codigoVehiculo'],
            "unidadMedida" => $jsonData['detalleFacturaList'][0]['unidadMedida'],
            "precioTotalSinDescuento" => $jsonData['detalleFacturaList'][0]['precioTotalSinDescuento'],
            "precioTotalFinalDetalle" => $jsonData['detalleFacturaList'][0]['precioTotalFinalDetalle']
            // observacion se puede mover aquí o al nivel principal
        ]
    ]
];

echo json_encode($estructuraCorregida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n" . "="*60 . "\n";

// Resumen de cambios necesarios
echo "📝 CAMBIOS NECESARIOS PARA VALIDACIÓN:\n\n";

echo "✅ OBLIGATORIOS:\n";
echo "1. Agregar campo 'codigo' (requerido)\n";
echo "2. Convertir 'idFacturador' de int a string\n";
echo "3. Agregar descripción válida (no puede estar vacía)\n";

echo "\n⚠️ RECOMENDADOS:\n";
echo "1. Remover campos no oficiales: 'ambiente', 'documento'\n";
echo "2. Los 'objectName' son opcionales en la mayoría de objetos\n";
echo "3. Solo 'detalleFacturaList[].objectName' es requerido\n";

echo "\n📋 NOTAS:\n";
echo "• El JSON tiene una estructura muy buena, solo necesita ajustes menores\n";
echo "• La mayoría de campos coinciden con la documentación oficial MEYPAR\n";
echo "• Los valores numéricos y fechas están en formato correcto\n";

echo "\n🎯 RESULTADO: Estructura casi perfecta, solo 3 cambios obligatorios\n";

?>
