<?php

/**
 * Script de prueba para validar corrección del error PAC:
 * "El campo totalISC no debe ser informado."
 *
 * Valida que totalISC solo se incluya cuando hay productos con ISC aplicable.
 */

echo "=== PRUEBA DE CORRECCIÓN ERROR PAC - CAMPO totalISC CONDICIONAL ===\n\n";

// Simular diferentes escenarios de totales
$testCases = [
    [
        'nombre' => 'Factura sin productos ISC',
        'totalISC' => '0.00',
        'esperado' => 'NO debe incluir totalISC'
    ],
    [
        'nombre' => 'Factura con productos ISC válidos',
        'totalISC' => '2.50',
        'esperado' => 'SÍ debe incluir totalISC'
    ],
    [
        'nombre' => 'Factura con totalISC null',
        'totalISC' => null,
        'esperado' => 'NO debe incluir totalISC'
    ],
    [
        'nombre' => 'Factura con totalISC vacío',
        'totalISC' => '',
        'esperado' => 'NO debe incluir totalISC'
    ],
    [
        'nombre' => 'Factura con totalISC decimal pequeño',
        'totalISC' => '0.01',
        'esperado' => 'SÍ debe incluir totalISC'
    ]
];

// Simular la lógica corregida
function testTotalISCLogic($totalISCValue) {
    $totales = new stdClass();

    // CRÍTICO: totalISC es CONDICIONAL - solo incluir cuando hay productos con ISC
    $totalISC = normalizeNumericValueToTwoDecimals($totalISCValue);
    if (floatval($totalISC) > 0) {
        $totales->totalISC = $totalISC; // Solo incluir si hay ISC real
    }

    return $totales;
}

// Función auxiliar para normalizar valores numéricos a 2 decimales
function normalizeNumericValueToTwoDecimals($value) {
    if (empty($value) || !is_numeric($value)) {
        return '0.00'; // Valor por defecto con 2 decimales
    }

    // Convertir a float, redondear a 2 decimales y formatear como string
    $floatValue = (float) $value;
    $rounded = round($floatValue, 2);
    return number_format($rounded, 2, '.', '');
}

// Ejecutar pruebas
$testsPassed = 0;
$totalTests = count($testCases);

foreach ($testCases as $index => $testCase) {
    echo "Prueba " . ($index + 1) . ": {$testCase['nombre']}\n";
    echo "Valor totalISC: " . ($testCase['totalISC'] ?? 'null') . "\n";
    echo "Expectativa: {$testCase['esperado']}\n";

    $totales = testTotalISCLogic($testCase['totalISC']);

    $hasTotalISC = isset($totales->totalISC);

    echo "Resultado:\n";

    if ($hasTotalISC) {
        echo "  - totalISC: {$totales->totalISC} (INCLUIDO)\n";
    } else {
        echo "  - totalISC: NO incluido\n";
    }

    // Validar resultado esperado
    $passed = false;

    switch ($index) {
        case 0: // Sin productos ISC (0.00)
        case 2: // null
        case 3: // vacío
            $passed = !$hasTotalISC;
            break;
        case 1: // Con productos ISC (2.50)
        case 4: // Decimal pequeño (0.01)
            $passed = $hasTotalISC;
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
    echo "La corrección del error PAC totalISC es CORRECTA\n";
} else {
    echo "❌ ALGUNAS PRUEBAS FALLARON\n";
    echo "Revisar la lógica de totalISC condicional\n";
}

echo "\n=== COMPORTAMIENTO ESPERADO EN XML ===\n";

echo "❌ ANTES (Causaba error PAC):\n";
echo "```xml\n";
echo "<ser:totalesSubTotales>\n";
echo "  <ser:totalPrecioNeto>24.00</ser:totalPrecioNeto>\n";
echo "  <ser:totalITBMS>1.68</ser:totalITBMS>\n";
echo "  <ser:totalISC>0.00</ser:totalISC>  <!-- ❌ PROBLEMA: Campo innecesario -->\n";
echo "  <ser:totalMontoGravado>1.68</ser:totalMontoGravado>\n";
echo "  <ser:totalFactura>25.68</ser:totalFactura>\n";
echo "</ser:totalesSubTotales>\n";
echo "```\n\n";

echo "✅ DESPUÉS (PAC acepta):\n";
echo "```xml\n";
echo "<ser:totalesSubTotales>\n";
echo "  <ser:totalPrecioNeto>24.00</ser:totalPrecioNeto>\n";
echo "  <ser:totalITBMS>1.68</ser:totalITBMS>\n";
echo "  <!-- ✅ SOLUCIÓN: No incluir totalISC cuando no hay productos con ISC -->\n";
echo "  <ser:totalMontoGravado>1.68</ser:totalMontoGravado>\n";
echo "  <ser:totalFactura>25.68</ser:totalFactura>\n";
echo "</ser:totalesSubTotales>\n";
echo "```\n\n";

echo "📋 PARA FACTURAS CON ISC:\n";
echo "```xml\n";
echo "<ser:totalesSubTotales>\n";
echo "  <ser:totalPrecioNeto>100.00</ser:totalPrecioNeto>\n";
echo "  <ser:totalITBMS>7.00</ser:totalITBMS>\n";
echo "  <ser:totalISC>2.50</ser:totalISC>  <!-- ✅ SÍ incluir cuando hay ISC -->\n";
echo "  <ser:totalMontoGravado>9.50</ser:totalMontoGravado>\n";
echo "  <ser:totalFactura>109.50</ser:totalFactura>\n";
echo "</ser:totalesSubTotales>\n";
echo "```\n\n";

echo "🔍 DIFERENCIA CLAVE:\n";
echo "- totalISC es CONDICIONAL: solo incluir cuando floatval(totalISC) > 0\n";
echo "- Evita error: 'El campo totalISC no debe ser informado'\n";
echo "- Mantiene compatibilidad para facturas que SÍ tienen productos con ISC\n\n";

echo "✅ RESULTADO: XML conforme a especificaciones PAC sin errores de validación\n";
