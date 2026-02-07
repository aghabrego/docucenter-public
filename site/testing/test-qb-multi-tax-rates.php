<?php
/**
 * Script de Verificación: Soporte de Múltiples Tasas de Impuesto en QuickBooks
 *
 * Este script verifica si el servicio actual puede procesar correctamente
 * una factura de QuickBooks con diferentes tasas de impuesto por línea.
 *
 * Caso: Factura con ITBMS 10% y ITBMS 7% en diferentes líneas
 */

// ANÁLISIS DEL OBJETO QUICKBOOKS

$invoice = [
    "Invoice" => [
        "DocNumber" => "FE0025",
        "TotalAmt" => 1529,
        "Line" => [
            [
                "Id" => "1",
                "Description" => "Alojamiento CT del 15 al 18 de Enero 2026",
                "Amount" => 208.5,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => ["value" => "3", "name" => "41-10-1 ALOJAMIENTOS"],
                    "UnitPrice" => 208.5,
                    "Qty" => 1,
                    "TaxCodeRef" => ["value" => "15"] // TaxCode 15 = 10%
                ]
            ],
            [
                "Id" => "2",
                "Amount" => 285.79,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => ["value" => "4", "name" => "41-10-2 ALIMENTOS Y BEBIDAS"],
                    "UnitPrice" => 285.79,
                    "Qty" => 1,
                    "TaxCodeRef" => ["value" => "13"] // TaxCode 13 = 7%
                ]
            ],
            [
                "Id" => "3",
                "Amount" => 714.49,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => ["value" => "5", "name" => "41-20-1 TOURS"],
                    "UnitPrice" => 714.49,
                    "Qty" => 1,
                    "TaxCodeRef" => ["value" => "13"] // TaxCode 13 = 7%
                ]
            ],
            [
                "Id" => "4",
                "Amount" => 214.35,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => ["value" => "7", "name" => "41-20-3 TRANSPORTES"],
                    "UnitPrice" => 214.35,
                    "Qty" => 1,
                    "TaxCodeRef" => ["value" => "13"] // TaxCode 13 = 7%
                ]
            ]
        ],
        "TxnTaxDetail" => [
            "TotalTax" => 105.87,
            "TaxLine" => [
                [
                    "Amount" => 20.85,
                    "DetailType" => "TaxLineDetail",
                    "TaxLineDetail" => [
                        "TaxRateRef" => ["value" => "20"],
                        "PercentBased" => true,
                        "TaxPercent" => 10,
                        "NetAmountTaxable" => 208.5
                    ]
                ],
                [
                    "Amount" => 85.02,
                    "DetailType" => "TaxLineDetail",
                    "TaxLineDetail" => [
                        "TaxRateRef" => ["value" => "18"],
                        "PercentBased" => true,
                        "TaxPercent" => 7,
                        "NetAmountTaxable" => 1214.63
                    ]
                ]
            ]
        ]
    ]
];

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║  ANÁLISIS: Soporte de Múltiples Tasas de Impuesto en QuickBooks          ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// PASO 1: VALIDAR ESTRUCTURA
echo "📋 ESTRUCTURA DE LA FACTURA:\n";
echo "   DocNumber: {$invoice['Invoice']['DocNumber']}\n";
echo "   Total: \${$invoice['Invoice']['TotalAmt']}\n";
echo "   Líneas: " . count(array_filter($invoice['Invoice']['Line'], function($line) {
    return ($line['DetailType'] ?? '') === 'SalesItemLineDetail';
})) . "\n";
echo "   Total Impuesto: \${$invoice['Invoice']['TxnTaxDetail']['TotalTax']}\n";
echo "\n";

// PASO 2: ANALIZAR TASAS DE IMPUESTO
echo "💰 TASAS DE IMPUESTO DETECTADAS:\n";
$taxLines = $invoice['Invoice']['TxnTaxDetail']['TaxLine'];
foreach ($taxLines as $idx => $taxLine) {
    $detail = $taxLine['TaxLineDetail'];
    echo "   TaxLine " . ($idx + 1) . ":\n";
    echo "     - Porcentaje: {$detail['TaxPercent']}%\n";
    echo "     - Base gravable: \${$detail['NetAmountTaxable']}\n";
    echo "     - Impuesto: \${$taxLine['Amount']}\n";
    echo "     - TaxRateRef: {$detail['TaxRateRef']['value']}\n";
    echo "\n";
}

// PASO 3: SIMULAR PROCESAMIENTO ACTUAL
echo "⚙️  SIMULACIÓN DEL PROCESAMIENTO ACTUAL:\n";
echo "\n";

$totalTax = $invoice['Invoice']['TxnTaxDetail']['TotalTax'];
$subtotal = $invoice['Invoice']['TotalAmt'] - $totalTax;

// El código actual solo toma TaxLine[0]
$firstTaxLine = $taxLines[0]['TaxLineDetail'];
$taxPercentUsed = $firstTaxLine['TaxPercent'];

echo "   Subtotal calculado: \${$subtotal}\n";
echo "   Porcentaje usado (TaxLine[0]): {$taxPercentUsed}%\n";
echo "   Total impuesto: \${$totalTax}\n";
echo "\n";

// PASO 4: VERIFICAR CADA LÍNEA
echo "🔍 VERIFICACIÓN POR LÍNEA:\n";
echo "\n";

$lines = array_filter($invoice['Invoice']['Line'], function($line) {
    return ($line['DetailType'] ?? '') === 'SalesItemLineDetail';
});

$results = [];
foreach ($lines as $line) {
    $detail = $line['SalesItemLineDetail'];
    $lineAmount = $detail['UnitPrice'] * $detail['Qty'];
    $taxCodeRef = $detail['TaxCodeRef']['value'];

    // MÉTODO ACTUAL: Distribución proporcional con TotalTax
    $lineProportionOfSubtotal = $lineAmount / $subtotal;
    $taxCalculated = $totalTax * $lineProportionOfSubtotal;

    // DETERMINAR IMPUESTO CORRECTO según TaxCodeRef
    $correctTaxPercent = null;
    $correctTax = null;

    foreach ($taxLines as $taxLine) {
        $taxDetail = $taxLine['TaxLineDetail'];
        // Buscar si esta línea debería usar esta tasa
        // (En QB, TaxCodeRef apunta a un código que tiene asociada una tasa)
        // Para este análisis, asumimos:
        // TaxCodeRef 15 = 10% (TaxRateRef 20)
        // TaxCodeRef 13 = 7% (TaxRateRef 18)

        if ($taxCodeRef == "15" && $taxDetail['TaxRateRef']['value'] == "20") {
            $correctTaxPercent = $taxDetail['TaxPercent'];
            $correctTax = $lineAmount * ($correctTaxPercent / 100);
        } elseif ($taxCodeRef == "13" && $taxDetail['TaxRateRef']['value'] == "18") {
            $correctTaxPercent = $taxDetail['TaxPercent'];
            $correctTax = $lineAmount * ($correctTaxPercent / 100);
        }
    }

    $difference = $correctTax !== null ? abs($taxCalculated - $correctTax) : null;
    $status = $difference !== null && $difference > 0.01 ? "❌ INCORRECTO" : "✅ CORRECTO";

    $results[] = [
        'line_id' => $line['Id'],
        'description' => substr($line['Description'] ?? $detail['ItemRef']['name'], 0, 40),
        'amount' => $lineAmount,
        'tax_code_ref' => $taxCodeRef,
        'correct_percent' => $correctTaxPercent,
        'correct_tax' => $correctTax,
        'calculated_tax' => $taxCalculated,
        'difference' => $difference,
        'status' => $status
    ];

    echo "   Línea {$line['Id']}: " . substr($line['Description'] ?? '', 0, 40) . "...\n";
    echo "     Monto línea: \${$lineAmount}\n";
    echo "     TaxCodeRef: {$taxCodeRef}\n";
    if ($correctTaxPercent !== null) {
        echo "     Tasa correcta: {$correctTaxPercent}%\n";
        echo "     Impuesto correcto: \$" . number_format($correctTax, 2) . "\n";
    }
    echo "     Impuesto calculado (proporcional): \$" . number_format($taxCalculated, 2) . "\n";
    if ($difference !== null) {
        echo "     Diferencia: \$" . number_format($difference, 2) . "\n";
        echo "     Estado: {$status}\n";
    }
    echo "\n";
}

// PASO 5: RESUMEN Y DIAGNÓSTICO
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNÓSTICO                                                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$incorrectCount = count(array_filter($results, function($r) {
    return strpos($r['status'], '❌') !== false;
}));

if ($incorrectCount > 0) {
    echo "❌ PROBLEMA DETECTADO:\n";
    echo "\n";
    echo "   El método de distribución proporcional NO funciona correctamente cuando\n";
    echo "   hay múltiples tasas de impuesto en la misma factura.\n";
    echo "\n";
    echo "   Razón: El código actual solo usa TaxLine[0] ({$taxPercentUsed}%) y distribuye\n";
    echo "   el impuesto total proporcionalmente, sin considerar que cada línea\n";
    echo "   puede tener una tasa diferente según su TaxCodeRef.\n";
    echo "\n";
    echo "🔧 SOLUCIÓN REQUERIDA:\n";
    echo "\n";
    echo "   1. Crear un mapeo de TaxCodeRef → TaxRateRef → TaxPercent\n";
    echo "   2. Para cada línea, buscar su TaxCodeRef en TxnTaxDetail.TaxLine[]\n";
    echo "   3. Aplicar el porcentaje específico de esa tasa a esa línea\n";
    echo "\n";
    echo "   Ejemplo de lógica:\n";
    echo "   - Si TaxCodeRef = 15 → buscar TaxLine con TaxRateRef = 20 → aplicar 10%\n";
    echo "   - Si TaxCodeRef = 13 → buscar TaxLine con TaxRateRef = 18 → aplicar 7%\n";
    echo "\n";
} else {
    echo "✅ El método actual funciona correctamente para este caso.\n";
    echo "\n";
}

// PASO 6: VALIDACIÓN ADICIONAL
echo "📊 VALIDACIÓN DE TOTALES:\n";
echo "\n";

$totalTaxCalculated = array_sum(array_column($results, 'calculated_tax'));
$totalTaxCorrect = array_sum(array_filter(array_column($results, 'correct_tax'), function($v) {
    return $v !== null;
}));

echo "   Total impuesto (QB): \${$totalTax}\n";
echo "   Total calculado (proporcional): \$" . number_format($totalTaxCalculated, 2) . "\n";
if ($totalTaxCorrect > 0) {
    echo "   Total correcto (por tasa): \$" . number_format($totalTaxCorrect, 2) . "\n";
}
echo "\n";

$totalDifference = abs($totalTax - $totalTaxCalculated);
echo "   Diferencia total: \$" . number_format($totalDifference, 2) . "\n";
echo "\n";

// PASO 7: CONSIDERACIONES
echo "💡 CONSIDERACIONES:\n";
echo "\n";
echo "   1. QuickBooks NO envía TaxCode.rateValue en este formato\n";
echo "   2. Solo envía TaxCodeRef.value (referencia, no tasa)\n";
echo "   3. Las tasas reales están en TxnTaxDetail.TaxLine[]\n";
echo "   4. NO hay un vínculo directo entre TaxCodeRef y TaxRateRef\n";
echo "\n";
echo "   El código actual usa distribución proporcional como fallback,\n";
echo "   lo cual es correcto SOLO cuando todas las líneas tienen la misma tasa.\n";
echo "\n";

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "\n";
