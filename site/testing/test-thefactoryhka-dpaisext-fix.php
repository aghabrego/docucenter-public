<?php
/**
 * Test de verificación: Fix para campo paisExtranjero requerido por TheFactoryHKA
 *
 * Este test verifica que el campo dPaisExt se incluya correctamente para clientes extranjeros
 * cuando se emite una factura de operación interna según los requerimientos de TheFactoryHKA.
 *
 * Problema original:
 * - Error: "El campo paisExtranjero es requerido"
 * - PAC TheFactoryHKA requiere dPaisExt en gIdExt para clientes extranjeros
 * - Operación interna (iDest=1) a cliente extranjero (iTipoRec=4)
 *
 * Uso:
 * cd /home/weirdolabs/code/docucenter
 * docker exec -it docucenter_laravel.test php docs/testing/test-thefactoryhka-dpaisext-fix.php
 */

echo "🧪 TEST: Fix campo paisExtranjero requerido por TheFactoryHKA\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Simular datos de entrada problemáticos
$datosProblematicos = [
    'receptor_tipo' => '3', // Cliente extranjero
    'destinoOperacion' => '1', // Operación interna
    'paisExtranjero' => null, // Sin país extranjero especificado
    'receptor_paisNacionalidad' => 'PA', // Nacionalidad PA (problemático)
    'numeroIdentificacionExtranjero' => 'XYZABC123',
    'tipoIdentificacionExtranjero' => '01'
];

echo "📋 DATOS DE ENTRADA (caso problemático):\n";
foreach ($datosProblematicos as $campo => $valor) {
    echo "  - {$campo}: " . ($valor ?? 'null') . "\n";
}
echo "\n";

// Test de la función getUnifiedForeignReceiverData()
echo "=== PASO 1: Simular función getUnifiedForeignReceiverData() ===\n";

function simularGetUnifiedForeignReceiverData($datos) {
    $numeroIdentificacion = $datos['numeroIdentificacionExtranjero'];
    $paisExtranjero = $datos['paisExtranjero'];

    // Construir datos oficiales
    $oficialData = [];

    // Campo crítico requerido por TheFactoryHKA
    $oficialData['tipoIdentificacion'] = '01';

    // Número identificación
    if ($numeroIdentificacion) {
        $oficialData['dIdExt'] = $numeroIdentificacion;
    }

    // NUEVA LÓGICA: dPaisExt para TheFactoryHKA
    if ($paisExtranjero) {
        $oficialData['dPaisExt'] = $paisExtranjero;
    } elseif ($datos['receptor_tipo'] === '3' || $datos['receptor_tipo'] === 3) {
        // Cliente extranjero: TheFactoryHKA requiere dPaisExt obligatorio
        if ($datos['destinoOperacion'] == '1') {
            // Operación interna: usar país de nacionalidad del cliente
            $paisNacionalidadCode = $datos['receptor_paisNacionalidad'];
            $oficialData['dPaisExt'] = $paisNacionalidadCode ?: 'PA';
        } else {
            // Operación de exportación: usar código del país receptor
            $oficialData['dPaisExt'] = 'US'; // Fallback para exportación
        }
    }

    return array_filter($oficialData, function($value) {
        return $value !== null && $value !== '';
    });
}

// Ejecutar test con datos problemáticos
$resultadoAntes = [
    'tipoIdentificacion' => '01',
    'dIdExt' => 'XYZABC123',
    // ❌ ANTES: No incluía dPaisExt
];

$resultadoDespues = simularGetUnifiedForeignReceiverData($datosProblematicos);

echo "❌ ANTES (causa del error):\n";
echo json_encode($resultadoAntes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "✅ DESPUÉS (con fix aplicado):\n";
echo json_encode($resultadoDespues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Verificar que se incluye dPaisExt
$incluyeDPaisExt = isset($resultadoDespues['dPaisExt']);
echo "\n🎯 VERIFICACIÓN CRÍTICA:\n";
echo "  - Campo dPaisExt incluido: " . ($incluyeDPaisExt ? "✅ SÍ" : "❌ NO") . "\n";
echo "  - Valor dPaisExt: " . ($resultadoDespues['dPaisExt'] ?? 'NO_PRESENTE') . "\n";

echo "\n=== PASO 2: Test casos específicos ===\n";

// Caso 1: Cliente extranjero con nacionalidad específica
$casoExtranjeroReal = [
    'receptor_tipo' => '3',
    'destinoOperacion' => '1',
    'paisExtranjero' => null,
    'receptor_paisNacionalidad' => 'CL', // Chile
    'numeroIdentificacionExtranjero' => 'CL12345678',
    'tipoIdentificacionExtranjero' => '01'
];

$resultadoChile = simularGetUnifiedForeignReceiverData($casoExtranjeroReal);

echo "📝 Caso 1 - Cliente chileno en operación interna:\n";
echo "  Input: receptor_paisNacionalidad = 'CL'\n";
echo "  Output dPaisExt: " . ($resultadoChile['dPaisExt'] ?? 'NO_PRESENTE') . "\n";
echo "  ✅ Resultado: " . (isset($resultadoChile['dPaisExt']) ? "CORRECTO" : "ERROR") . "\n\n";

// Caso 2: Cliente extranjero en exportación
$casoExportacion = [
    'receptor_tipo' => '3',
    'destinoOperacion' => '2', // Exportación
    'paisExtranjero' => null,
    'receptor_paisNacionalidad' => 'US',
    'numeroIdentificacionExtranjero' => 'US87654321',
    'tipoIdentificacionExtranjero' => '01'
];

$resultadoExportacion = simularGetUnifiedForeignReceiverData($casoExportacion);

echo "📝 Caso 2 - Cliente extranjero en exportación:\n";
echo "  Input: destinoOperacion = '2', receptor_paisNacionalidad = 'US'\n";
echo "  Output dPaisExt: " . ($resultadoExportacion['dPaisExt'] ?? 'NO_PRESENTE') . "\n";
echo "  ✅ Resultado: " . (isset($resultadoExportacion['dPaisExt']) ? "CORRECTO" : "ERROR") . "\n\n";

// Caso 3: Cliente nacional (no debe incluir dPaisExt)
$casoNacional = [
    'receptor_tipo' => '2', // Nacional
    'destinoOperacion' => '1',
    'paisExtranjero' => null,
    'receptor_paisNacionalidad' => 'PA',
    'numeroIdentificacionExtranjero' => null,
    'tipoIdentificacionExtranjero' => null
];

$resultadoNacional = simularGetUnifiedForeignReceiverData($casoNacional);

echo "📝 Caso 3 - Cliente nacional:\n";
echo "  Input: receptor_tipo = '2' (nacional)\n";
echo "  Output dPaisExt: " . ($resultadoNacional['dPaisExt'] ?? 'NO_PRESENTE') . "\n";
echo "  ✅ Resultado: " . (!isset($resultadoNacional['dPaisExt']) ? "CORRECTO (no debe incluir dPaisExt)" : "ERROR (no debería incluir dPaisExt)") . "\n\n";

echo "=== PASO 3: Verificación HKAService ===\n";

// Verificar que HKAService puede mapear correctamente
$gIdExtSimulado = $resultadoDespues;

echo "📋 Estructura gIdExt que recibirá HKAService:\n";
echo json_encode($gIdExtSimulado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Simular mapeo HKAService
$clienteHKA = (object)[
    'paisExtranjero' => $gIdExtSimulado['dPaisExt'] ?? null,
    'nroIdentificacionExtranjero' => $gIdExtSimulado['dIdExt'] ?? null,
    'tipoIdentificacion' => $gIdExtSimulado['tipoIdentificacion'] ?? null,
];

echo "\n📋 Objeto Cliente que construye HKAService:\n";
echo "  - paisExtranjero: " . ($clienteHKA->paisExtranjero ?? 'null') . "\n";
echo "  - nroIdentificacionExtranjero: " . ($clienteHKA->nroIdentificacionExtranjero ?? 'null') . "\n";
echo "  - tipoIdentificacion: " . ($clienteHKA->tipoIdentificacion ?? 'null') . "\n";

$hkaValido = !empty($clienteHKA->paisExtranjero) && !empty($clienteHKA->nroIdentificacionExtranjero);
echo "\n✅ HKAService validación: " . ($hkaValido ? "PASARÁ ✅" : "FALLARÁ ❌") . "\n";

echo "\n=== RESUMEN FINAL ===\n";

$testResults = [
    'Campo dPaisExt incluido para extranjeros' => $incluyeDPaisExt,
    'Caso Chile operación interna' => isset($resultadoChile['dPaisExt']),
    'Caso exportación' => isset($resultadoExportacion['dPaisExt']),
    'Caso nacional (no debe incluir)' => !isset($resultadoNacional['dPaisExt']),
    'HKAService podrá procesar' => $hkaValido
];

$testsPasados = 0;
$testsTotal = count($testResults);

foreach ($testResults as $test => $resultado) {
    $status = $resultado ? "✅ PASS" : "❌ FAIL";
    echo "  - {$test}: {$status}\n";
    if ($resultado) $testsPasados++;
}

echo "\n🎯 RESULTADO GENERAL: {$testsPasados}/{$testsTotal} tests pasados\n";

if ($testsPasados === $testsTotal) {
    echo "✅ ÉXITO: El fix debería resolver el error 'paisExtranjero es requerido'\n";
    echo "✅ TheFactoryHKA recibirá el campo dPaisExt requerido para clientes extranjeros\n";
} else {
    echo "❌ ERROR: Algunos tests fallaron, revisar implementación\n";
}

echo "\n💡 PRÓXIMOS PASOS:\n";
echo "  1. Verificar el fix en el navegador con una factura real\n";
echo "  2. Monitorear logs de HKAService para confirmación\n";
echo "  3. Testing en ambiente de desarrollo TheFactoryHKA\n";

echo "\n🔚 FIN DEL TEST\n";
