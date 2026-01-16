<?php
/**
 * DEBUG CRÍTICO: Analizar estructura exacta enviada al PAC para error 201
 *
 * OBJETIVO: Identificar qué está causando el error 201 persistente
 * después de implementar la corrección de campos prohibidos
 */

echo "=== DEBUG PAC 201: ESTRUCTURA EXACTA ENVIADA ===\n\n";

// Simular la estructura exacta que se muestra en el output del usuario
$documentoElectronico = (object) [
    'codigoSucursalEmisor' => '0000',
    'tipoSucursal' => 1,
    'datosTransaccion' => (object) [
        'tipoEmision' => '01',
        'tipoDocumento' => '03', // EXPORTACIÓN
        'numeroDocumentoFiscal' => '0000000057',
        'puntoFacturacionFiscal' => '004',
        'fechaEmision' => '2025-10-24T13:50:56-05:00',
        'naturalezaOperacion' => '01',
        'tipoOperacion' => 1,
        'destinoOperacion' => '2', // IMPORTANTE: Exportación
        'formatoCAFE' => 2,
        'entregaCAFE' => 3,
        'envioContenedor' => 1,
        'procesoGeneracion' => 1,
        'tipoVenta' => 1,
        'informacionInteres' => 'Order FC0000002986',
        'cliente' => (object) [
            'tipoClienteFE' => '04', // EXTRANJERO
            'tipoContribuyente' => null, // ✅ CORRECTO: Campo prohibido
            'numeroRUC' => null,         // ✅ CORRECTO: Campo prohibido
            'digitoVerificadorRUC' => null, // ✅ CORRECTO: Campo prohibido
            'razonSocial' => 'Solmary',
            'tipoIdentificacion' => '01',
            'nroIdentificacionExtranjero' => 'XYZABC123',
            'paisExtranjero' => 'CL',
            'direccion' => null,
            'codigoUbicacion' => null,  // ✅ CORRECTO: Campo prohibido
            'corregimiento' => null,    // ✅ CORRECTO: Campo prohibido
            'distrito' => null,         // ✅ CORRECTO: Campo prohibido
            'provincia' => null,        // ✅ CORRECTO: Campo prohibido
            'telefono1' => null,
            'telefono2' => null,
            'telefono3' => null,
            'correoElectronico1' => 'solmarymadrid@gmail.com',
            'correoElectronico2' => 'desarrollo@apconpanama.me',
            'pais' => 'CL',
            'paisOtro' => null
        ],
        'datosFacturaExportacion' => (object) [
            // ⚠️ POSIBLE PROBLEMA: Estructura de exportación
            'condicionesEntrega' => 'CFR',
            'moneda' => 'USD',
            'tipoCambio' => '1.00',
            'montoMonedaExtranjera' => '3.50',
            'puertoEmbarque' => 'PANAMA',
            'paisDestino' => 'CL'
        ]
    ],
    'listaItems' => [
        (object) [
            'descripcion' => '100- BROCHURE 11X17" TIRO Y RETIRO DOBLADO EN 3 PARTES 100- BROCHURE 11X17" TIRO Y RETIRO DOBLADO EN 3 PARTES',
            'codigo' => '',
            'unidadMedida' => 'UND',
            'cantidad' => '1.000000',
            'fechaFabricacion' => null,
            'unidadMedidaCPBS' => 'UND',
            'precioUnitario' => '3.500000',
            'precioUnitarioDescuento' => '0.000000',
            'precioAcarreo' => null,
            'precioSeguro' => null,
            'precioItem' => '3.500000',
            'valorTotal' => '3.500000',
            'codigoGTIN' => null,
            'cantGTINCom' => null,
            'codigoGTINInv' => null,
            'cantGTINComInv' => null,
            'tasaITBMS' => '00',
            'valorITBMS' => '0.000000',
            'tasaISC' => null,
            'valorISC' => '0.000000',
            'codigoCPBS' => '9015'
        ]
    ],
    'totalesSubTotales' => (object) [
        'totalPrecioNeto' => '3.50',
        'totalITBMS' => '0.00',
        'totalISC' => '0.00',
        'totalMontoGravado' => '0.00',
        'totalDescuento' => '0.00',
        'totalAcarreoCobrado' => '0.00',
        'valorSeguroCobrado' => '0.00',
        'totalFactura' => '3.50',
        'totalValorRecibido' => '3.50',
        'vuelto' => '0.00',
        'tiempoPago' => '1',
        'nroItems' => 1,
        'totalTodosItems' => '3.50',
        'listaFormaPago' => [
            [
                'formaPagoFact' => '01',
                'valorCuotaPagada' => '3.50',
                'descFormaPago' => null
            ]
        ],
        'listaDescBonificacion' => null
    ],
    'pedidoComercialGlobal' => (object) [
        'nroPedidoCompraGlobal' => 1,
        'codigoReceptor' => null,
        'nroAceptacion' => 1,
        'codigoSistemaEmisor' => null,
        'InfoPedido' => 'Order FC0000002986'
    ]
];

echo "ANÁLISIS DETALLADO DE LA ESTRUCTURA:\n";
echo "====================================\n\n";

// 1. Verificar campos prohibidos para extranjeros
echo "1. CAMPOS PROHIBIDOS PARA EXTRANJEROS (tipoClienteFE=04):\n";
$camposProhibidos = [
    'tipoContribuyente',
    'numeroRUC',
    'digitoVerificadorRUC',
    'codigoUbicacion',
    'provincia',
    'distrito',
    'corregimiento'
];

$cliente = $documentoElectronico->datosTransaccion->cliente;
foreach ($camposProhibidos as $campo) {
    $valor = $cliente->$campo ?? 'UNDEFINED';
    $estado = ($valor === null) ? '✅ CORRECTO (null)' : "❌ PROBLEMA: '$valor'";
    echo "   - $campo: $estado\n";
}

// 2. Verificar tipo de documento vs destinoOperacion
echo "\n2. COHERENCIA TIPO DOCUMENTO VS DESTINO:\n";
echo "   - tipoDocumento: {$documentoElectronico->datosTransaccion->tipoDocumento} (03 = Exportación)\n";
echo "   - destinoOperacion: {$documentoElectronico->datosTransaccion->destinoOperacion} (2 = Exportación)\n";
echo "   - paisDestino: {$documentoElectronico->datosTransaccion->datosFacturaExportacion->paisDestino}\n";
echo "   - paisCliente: {$cliente->pais}\n";

$coherente = ($documentoElectronico->datosTransaccion->tipoDocumento === '03' &&
              $documentoElectronico->datosTransaccion->destinoOperacion === '2');
echo "   ✓ Coherencia: " . ($coherente ? 'CORRECTA' : 'PROBLEMA') . "\n";

// 3. Verificar estructura datosFacturaExportacion
echo "\n3. DATOS FACTURA EXPORTACIÓN:\n";
$exportData = $documentoElectronico->datosTransaccion->datosFacturaExportacion;
echo "   - condicionesEntrega: {$exportData->condicionesEntrega}\n";
echo "   - monedaOperExportacion: {$exportData->monedaOperExportacion}\n";
echo "   - tipoCambio: {$exportData->tipoCambio}\n";
echo "   - montoMonedaExtranjera: {$exportData->montoMonedaExtranjera}\n";
echo "   - puertoEmbarque: {$exportData->puertoEmbarque}\n";
echo "   - paisDestino: {$exportData->paisDestino}\n";

// 4. Problemas potenciales específicos
echo "\n4. PROBLEMAS POTENCIALES IDENTIFICADOS:\n";

// 4.1 Verificar código de barras vacío
$item = $documentoElectronico->listaItems[0];
if (empty($item->codigo)) {
    echo "   ⚠️ PROBLEMA: Item sin código (código vacío)\n";
}

// 4.2 Verificar longitud descripción
if (strlen($item->descripcion) > 100) {
    echo "   ⚠️ PROBLEMA: Descripción muy larga (" . strlen($item->descripcion) . " caracteres)\n";
}

// 4.3 Verificar estructura CPBS
if (empty($item->codigoCPBS)) {
    echo "   ⚠️ PROBLEMA: codigoCPBS vacío\n";
}

// 4.4 Verificar moneda consistente
if ($exportData->monedaOperExportacion !== 'USD' && $exportData->tipoCambio === '1.00') {
    echo "   ⚠️ PROBLEMA: Moneda no USD pero tipo de cambio 1.00\n";
}

// 4.5 Verificar campos con valores '0.000000'
echo "\n5. CAMPOS CON FORMATO NUMÉRICO:\n";
$camposNumericos = [
    'precioUnitarioDescuento' => $item->precioUnitarioDescuento,
    'valorITBMS' => $item->valorITBMS,
    'valorISC' => $item->valorISC,
    'totalITBMS' => $documentoElectronico->totalesSubTotales->totalITBMS,
    'totalISC' => $documentoElectronico->totalesSubTotales->totalISC
];

foreach ($camposNumericos as $nombre => $valor) {
    if ($valor === '0.000000' || $valor === '0.00') {
        echo "   - $nombre: $valor (¿debería omitirse si es cero?)\n";
    }
}

echo "\n6. HIPÓTESIS PRINCIPALES PARA ERROR 201:\n";
echo "=======================================\n";
echo "A. Campo código del item está vacío (puede ser requerido)\n";
echo "B. Descripción del item excede límites permitidos\n";
echo "C. Campos numéricos cero deberían omitirse completamente\n";
echo "D. Estructura datosFacturaExportacion incorrecta\n";
echo "E. Formato de fecha/hora no compatible\n";
echo "F. Combinación específica tipoDocumento=03 + destinoOperacion=2\n";

echo "\n7. PRÓXIMOS PASOS RECOMENDADOS:\n";
echo "==============================\n";
echo "1. Validar que código del item no esté vacío\n";
echo "2. Truncar descripción del item si excede límites\n";
echo "3. Omitir completamente campos numéricos que sean cero\n";
echo "4. Verificar formato exacto de datosFacturaExportacion en documentación\n";
echo "5. Probar con estructura mínima de campos requeridos únicamente\n";

?>
