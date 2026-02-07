#!/bin/bash

# Script de Testing - Corrección Error PAC 201
# Ubicación: docs/testing/test-pac-error-201-fix.sh
# Ejecutar: ./docs/testing/test-pac-error-201-fix.sh

echo "=== Test de Corrección Error PAC 201 ==="
echo "Verificando correcciones para cliente extranjero, items y exportación"
echo ""

CONTAINER_NAME="docucenter_laravel.test"

echo "Ejecutando test de correcciones en contenedor Docker..."

docker exec -it $CONTAINER_NAME php -r "
echo \"=== Test de Correcciones HKAService ===\n\";

// Simular datos problemáticos como los del error original
\$docData = (object)[
    'dGen' => (object)[
        'gDatRec' => (object)[
            'iTipoRec' => '04', // Extranjero
            'gRucRec' => (object)[
                'dRuc' => null,
                'dDV' => null,
                'dTipoRuc' => null
            ],
            'gIdExt' => (object)[
                'tipoIdentificacion' => '01',
                'dIdExt' => 'XYZABC123',
                'dPaisExt' => 'CL'
            ],
            'dNombRec' => 'Solmary',
            'cPaisRec' => 'CL'
        ]
    ],
    'gItem' => [
        (object)[
            'dDescProd' => 'BROCHURE TEST',
            'dCodProd' => null,     // Problema: null
            'cUnidad' => null,      // Problema: null
            'cUnidadCPBS' => null,  // Problema: null
            'dCantCodInt' => '1.0',
            'gPrecios' => (object)[
                'dPrUnit' => '3.50',
                'dPrItem' => '3.50',
                'dValTotItem' => '3.50'
            ],
            'gITBMSItem' => (object)[
                'dTasaITBMS' => '00',
                'dValITBMS' => '0.00'
            ],
            'gISCItem' => (object)[
                'dValISC' => '0.00'
            ],
            'dCodCPBScmp' => '9015'
        ]
    ],
    'gFExp' => (object)[
        'cCondEntr' => 'CFR',
        'cMoneda' => 'USD',
        'dCambio' => null,        // Problema: null
        'dVTotEst' => null,       // Problema: null
        'dPuertoEmbarq' => null,  // Problema: null
        'cPaisDest' => 'CL'
    ],
    'gTot' => (object)[
        'dVTot' => '3.50'
    ]
];

echo \"=== PASO 1: Crear objeto con HKAService ===\n\";

try {
    // Crear organización de prueba
    \$organization = \App\Models\Organization::first();

    // Crear HKAService
    \$hkaService = app()->make(\App\Services\HKAService::class, ['organization' => \$organization]);

    // Crear documento electrónico
    \$documento = \$hkaService->createFeXML(\$docData);

    echo \"✅ Documento creado exitosamente\n\";

    // Verificar correcciones en cliente
    \$cliente = \$documento->datosTransaccion->cliente;
    echo \"=== VERIFICACIÓN CLIENTE ===\n\";
    echo \"tipoClienteFE: {\$cliente->tipoClienteFE}\n\";
    echo \"tipoContribuyente: \" . var_export(\$cliente->tipoContribuyente, true) . \"\n\";
    echo \"numeroRUC: \" . var_export(\$cliente->numeroRUC, true) . \"\n\";
    echo \"digitoVerificadorRUC: \" . var_export(\$cliente->digitoVerificadorRUC, true) . \"\n\";

    // Verificación cliente extranjero
    if (\$cliente->tipoClienteFE === '04') {
        \$clienteOK = true;

        if (\$cliente->tipoContribuyente === null) {
            echo \"❌ ERROR: tipoContribuyente sigue siendo null\n\";
            \$clienteOK = false;
        }

        if (\$cliente->numeroRUC === null) {
            echo \"❌ ERROR: numeroRUC sigue siendo null\n\";
            \$clienteOK = false;
        }

        if (\$cliente->digitoVerificadorRUC === null) {
            echo \"❌ ERROR: digitoVerificadorRUC sigue siendo null\n\";
            \$clienteOK = false;
        }

        if (\$clienteOK) {
            echo \"✅ Cliente extranjero corregido exitosamente\n\";
        }
    }

    // Verificar correcciones en items
    echo \"\\n=== VERIFICACIÓN ITEMS ===\n\";
    \$item = \$documento->listaItems[0];
    echo \"codigo: \" . var_export(\$item->codigo, true) . \"\n\";
    echo \"unidadMedida: \" . var_export(\$item->unidadMedida, true) . \"\n\";
    echo \"unidadMedidaCPBS: \" . var_export(\$item->unidadMedidaCPBS, true) . \"\n\";

    \$itemOK = true;
    if (\$item->codigo === null) {
        echo \"❌ ERROR: codigo sigue siendo null\n\";
        \$itemOK = false;
    }
    if (\$item->unidadMedida === null) {
        echo \"❌ ERROR: unidadMedida sigue siendo null\n\";
        \$itemOK = false;
    }
    if (\$item->unidadMedidaCPBS === null) {
        echo \"❌ ERROR: unidadMedidaCPBS sigue siendo null\n\";
        \$itemOK = false;
    }

    if (\$itemOK) {
        echo \"✅ Items corregidos exitosamente\n\";
    }

    // Verificar datos de exportación
    echo \"\\n=== VERIFICACIÓN EXPORTACIÓN ===\n\";
    if (isset(\$documento->datosTransaccion->datosFacturaExportacion)) {
        \$export = \$documento->datosTransaccion->datosFacturaExportacion;
        echo \"tipoCambio: \" . var_export(\$export->tipoCambio, true) . \"\n\";
        echo \"montoMonedaExtranjera: \" . var_export(\$export->montoMonedaExtranjera, true) . \"\n\";
        echo \"puertoEmbarque: \" . var_export(\$export->puertoEmbarque, true) . \"\n\";

        \$exportOK = true;
        if (\$export->tipoCambio === null) {
            echo \"❌ ERROR: tipoCambio sigue siendo null\n\";
            \$exportOK = false;
        }
        if (\$export->montoMonedaExtranjera === null) {
            echo \"❌ ERROR: montoMonedaExtranjera sigue siendo null\n\";
            \$exportOK = false;
        }
        if (\$export->puertoEmbarque === null) {
            echo \"❌ ERROR: puertoEmbarque sigue siendo null\n\";
            \$exportOK = false;
        }

        if (\$exportOK) {
            echo \"✅ Datos de exportación corregidos exitosamente\n\";
        }
    } else {
        echo \"❌ ERROR: No se encontraron datos de exportación\n\";
    }

    echo \"\\n=== RESULTADO FINAL ===\n\";
    if (\$clienteOK && \$itemOK && \$exportOK) {
        echo \"🎉 TODAS LAS CORRECCIONES APLICADAS EXITOSAMENTE\n\";
        echo \"El documento ahora debería ser aceptado por el PAC\n\";
    } else {
        echo \"⚠️  ALGUNAS CORRECCIONES FALLARON\n\";
    }

} catch (\Exception \$e) {
    echo \"❌ ERROR en test: {\$e->getMessage()}\n\";
    echo \"Trace: {\$e->getTraceAsString()}\n\";
}
"

echo ""
echo "=== Verificación en Código Fuente ==="
echo "Comprobando que las correcciones están implementadas..."

if docker exec $CONTAINER_NAME grep -q "tipoContribuyente.*2.*defecto" app/Services/HKAService.php; then
    echo "✅ Corrección tipoContribuyente encontrada"
else
    echo "❌ Corrección tipoContribuyente NO encontrada"
fi

if docker exec $CONTAINER_NAME grep -q "validateAndFixDocument" app/Services/HKAService.php; then
    echo "✅ Método de validación encontrado"
else
    echo "❌ Método de validación NO encontrado"
fi

echo ""
echo "=== Test Completado ==="
echo "Si todos los tests pasaron, el error PAC 201 debería estar resuelto."
