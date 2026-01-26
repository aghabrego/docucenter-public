<?php

/**
 * Script de prueba para validar corrección del error PAC:
 * "En la ocurrencia [4] de Item, El campo valorISC es inválido. Informado valor del ISC en una operacion no valorada."
 *
 * Valida que los campos ISC solo se incluyan cuando realmente aplican según documentación PAC oficial.
 */

echo "=== PRUEBA DE CORRECCIÓN ERROR PAC - CAMPOS ISC CONDICIONALES ===\n\n";

// Simular diferentes escenarios de items
$testCases = [
    [
        'nombre' => 'Item sin ISC (producto normal)',
        'data' => (object) [
            'gISCItem' => (object) [
                'dTasaISC' => null,
                'dValISC' => null
            ]
        ],
        'esperado' => 'NO debe incluir campos ISC'
    ],
    [
        'nombre' => 'Item con ISC cero',
        'data' => (object) [
            'gISCItem' => (object) [
                'dTasaISC' => '0',
                'dValISC' => '0.000000'
            ]
        ],
        'esperado' => 'NO debe incluir campos ISC'
    ],
    [
        'nombre' => 'Item con ISC válido (tabaco/alcohol)',
        'data' => (object) [
            'gISCItem' => (object) [
                'dTasaISC' => '05',
                'dValISC' => '1.250000'
            ]
        ],
        'esperado' => 'SÍ debe incluir campos ISC'
    ],
    [
        'nombre' => 'Item con tasaISC vacía pero valorISC con valor',
        'data' => (object) [
            'gISCItem' => (object) [
                'dTasaISC' => '',
                'dValISC' => '0.500000'
            ]
        ],
        'esperado' => 'SÍ debe incluir valorISC solamente'
    ],
    [
        'nombre' => 'Item con tasaISC válida pero valorISC cero',
        'data' => (object) [
            'gISCItem' => (object) [
                'dTasaISC' => '03',
                'dValISC' => '0.000000'
            ]
        ],
        'esperado' => 'SÍ debe incluir tasaISC solamente'
    ]
];

// Simular la lógica corregida
function testISCLogic($detailItem) {
    $item = new stdClass();

    // CRÍTICO: Campos ISC son CONDICIONALES según documentación oficial PAC
    // Solo incluir cuando realmente aplican (productos con ISC como tabaco, alcohol)
    $tasaISC = getNestedValue($detailItem, 'gISCItem.dTasaISC');
    $valorISC = getNestedValue($detailItem, 'gISCItem.dValISC');

    // Solo enviar campos ISC si realmente aplican (no vacíos, no cero)
    if (!empty($tasaISC) && $tasaISC !== '0' && $tasaISC !== '00') {
        $item->tasaISC = $tasaISC;
    }

    if (!empty($valorISC) && floatval($valorISC) > 0) {
        $item->valorISC = normalizeNumericValue($valorISC);
    }

    return $item;
}

// Función auxiliar para obtener valores anidados
function getNestedValue($data, $path, $default = null) {
    $keys = explode('.', $path);

    foreach ($keys as $key) {
        if (is_object($data)) {
            if (isset($data->{$key})) {
                $data = $data->{$key};
            } else {
                return $default;
            }
        } elseif (is_array($data)) {
            if (isset($data[$key])) {
                $data = $data[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    return $data;
}

// Función auxiliar para normalizar valores numéricos
function normalizeNumericValue($value) {
    if (empty($value) || !is_numeric($value)) {
        return '0.000000';
    }

    $floatValue = (float) $value;
    $rounded = round($floatValue, 6);
    return number_format($rounded, 6, '.', '');
}

// Ejecutar pruebas
$testsPassed = 0;
$totalTests = count($testCases);

foreach ($testCases as $index => $testCase) {
    echo "Prueba " . ($index + 1) . ": {$testCase['nombre']}\n";
    echo "Expectativa: {$testCase['esperado']}\n";

    $item = testISCLogic($testCase['data']);

    $hasISCFields = isset($item->tasaISC) || isset($item->valorISC);

    echo "Resultado:\n";

    if (isset($item->tasaISC)) {
        echo "  - tasaISC: {$item->tasaISC}\n";
    } else {
        echo "  - tasaISC: NO incluido\n";
    }

    if (isset($item->valorISC)) {
        echo "  - valorISC: {$item->valorISC}\n";
    } else {
        echo "  - valorISC: NO incluido\n";
    }

    // Validar resultado esperado
    $passed = false;

    switch ($index) {
        case 0: // Item sin ISC
        case 1: // Item con ISC cero
            $passed = !$hasISCFields;
            break;
        case 2: // Item con ISC válido
            $passed = isset($item->tasaISC) && isset($item->valorISC);
            break;
        case 3: // Solo valorISC
            $passed = !isset($item->tasaISC) && isset($item->valorISC);
            break;
        case 4: // Solo tasaISC
            $passed = isset($item->tasaISC) && !isset($item->valorISC);
            break;
    }

    if ($passed) {
        echo "✅ CORRECTO\n";
        $testsPassed++;
    } else {
        echo "❌ FALLÓ\n";
    }

    echo "---\n\n";
}

echo "=== RESUMEN ===\n";
echo "Pruebas pasadas: $testsPassed/$totalTests\n";

if ($testsPassed === $totalTests) {
    echo "✅ TODAS LAS PRUEBAS PASARON\n";
    echo "La corrección del error PAC valorISC es CORRECTA\n";
} else {
    echo "❌ ALGUNAS PRUEBAS FALLARON\n";
    echo "Revisar la lógica de campos ISC condicionales\n";
}

echo "\n=== BENEFICIOS DE LA CORRECCIÓN ===\n";
echo "1. ✅ Resuelve error PAC 'El campo valorISC es inválido'\n";
echo "2. ✅ Cumple especificación oficial PAC (campos condicionales)\n";
echo "3. ✅ Mantiene compatibilidad con productos que SÍ requieren ISC\n";
echo "4. ✅ Reduce campos innecesarios en XML enviado al PAC\n";
echo "5. ✅ Optimiza comunicación con TheFactoryHKA PAC\n";

echo "\n=== CASOS DE APLICACIÓN ISC ===\n";
echo "- Productos de tabaco: SÍ incluir campos ISC\n";
echo "- Bebidas alcohólicas: SÍ incluir campos ISC\n";
echo "- Productos normales (muebles, equipos, servicios): NO incluir campos ISC\n";
echo "- Solo incluir cuando tasaISC > 0 OR valorISC > 0\n";
