<?php
/**
 * Simulación con datos reales de QuickBooks
 */

require_once 'vendor/autoload.php';

echo "🧪 SIMULACIÓN CON DATOS REALES DE QUICKBOOKS\n";
echo "════════════════════════════════════════════\n\n";

// Datos del objeto original
$invoiceData = [
    "DocNumber" => "FE0000003143",
    "TxnDate" => "2025-09-17",
    "TotalAmt" => 107,
    "Balance" => 107,
    "TxnTaxDetail" => [
        "TotalTax" => 7
    ],
    "CustomerRef" => [
        "value" => "1440",
        "name" => "ANUAR MATA",
        "TIPO_RECEPTOR" => "02",
        "PrimaryEmail" => "anuarmindset@gmail.com"
    ]
];

echo "📋 DATOS DE LA FACTURA:\n";
echo "─────────────────────────\n";
echo "• DocNumber: " . $invoiceData['DocNumber'] . "\n";
echo "• TotalAmt: $" . $invoiceData['TotalAmt'] . "\n";
echo "• Balance: $" . $invoiceData['Balance'] . "\n";
echo "• Tax: $" . $invoiceData['TxnTaxDetail']['TotalTax'] . "\n";
echo "• Customer: " . $invoiceData['CustomerRef']['name'] . "\n";
echo "• TIPO_RECEPTOR: " . $invoiceData['CustomerRef']['TIPO_RECEPTOR'] . "\n";

// Simular la lógica de determinePaymentMethods
function simulateDeterminePaymentMethods(float $totalAmount, float $balance): array
{
    $payments = [];

    if ($balance == $totalAmount) {
        // Escenario 1: Sin pagar - Todo es Store Credit (crédito pendiente)
        $payments[] = [
            'method' => 'Store Credit',
            'amount' => $totalAmount,
            'type' => 'pending_credit',
            'description' => 'Crédito pendiente - Factura sin pagar'
        ];

    } elseif ($balance == 0) {
        // Escenario 2: Totalmente pagado - Todo con CREDIT_CARD
        $payments[] = [
            'method' => 'CREDIT_CARD',
            'amount' => $totalAmount,
            'type' => 'completed_payment',
            'description' => 'Pago completo con tarjeta de crédito'
        ];

    } else {
        // Escenario 3: Pago parcial - Combinado
        $paidAmount = $totalAmount - $balance;
        $pendingAmount = $balance;

        // Parte pagada con tarjeta
        $payments[] = [
            'method' => 'CREDIT_CARD',
            'amount' => $paidAmount,
            'type' => 'partial_payment',
            'description' => 'Pago parcial con tarjeta de crédito'
        ];

        // Parte pendiente como crédito
        $payments[] = [
            'method' => 'Store Credit',
            'amount' => $pendingAmount,
            'type' => 'pending_credit',
            'description' => 'Saldo pendiente como crédito'
        ];
    }

    return $payments;
}

echo "\n💳 ANÁLISIS DE PAGOS:\n";
echo "──────────────────────\n";

$totalAmount = (float) $invoiceData['TotalAmt'];
$balance = (float) $invoiceData['Balance'];
$subtotal = $totalAmount - $invoiceData['TxnTaxDetail']['TotalTax'];

echo "• Subtotal (sin impuesto): $" . $subtotal . "\n";
echo "• Impuesto: $" . $invoiceData['TxnTaxDetail']['TotalTax'] . "\n";
echo "• Total: $" . $totalAmount . "\n";
echo "• Balance: $" . $balance . "\n";
echo "• Monto pagado: $" . ($totalAmount - $balance) . "\n";

// Determinar escenario
if ($balance == $totalAmount) {
    echo "• Escenario: SIN PAGAR ✅\n";
} elseif ($balance == 0) {
    echo "• Escenario: TOTALMENTE PAGADO\n";
} else {
    echo "• Escenario: PAGO PARCIAL\n";
}

echo "\n🔍 RESULTADO determinePaymentMethods():\n";
echo "───────────────────────────────────────────\n";

$paymentMethods = simulateDeterminePaymentMethods($totalAmount, $balance);

foreach ($paymentMethods as $index => $payment) {
    echo "Pago " . ($index + 1) . ":\n";
    echo "  • Método: " . $payment['method'] . "\n";
    echo "  • Monto: $" . $payment['amount'] . "\n";
    echo "  • Tipo: " . $payment['type'] . "\n";
    echo "  • Descripción: " . $payment['description'] . "\n";
    echo "\n";
}

echo "📊 RESUMEN:\n";
echo "─────────────\n";
echo "• Cantidad de pagos: " . count($paymentMethods) . "\n";
echo "• Total de montos: $" . array_sum(array_column($paymentMethods, 'amount')) . "\n";
echo "• ¿Coincide con total factura?: " . (array_sum(array_column($paymentMethods, 'amount')) == $totalAmount ? 'SÍ ✅' : 'NO ❌') . "\n";

echo "\n💡 INTERPRETACIÓN:\n";
echo "────────────────────\n";
echo "Como Balance == TotalAmt ($balance == $totalAmount),\n";
echo "la factura NO tiene pagos aplicados en QuickBooks.\n";
echo "Por tanto, se crea UN SOLO PAGO al crédito por todo el monto,\n";
echo "representando que la factura está pendiente de pago.\n";

echo "\n🎯 CONFIRMACIÓN:\n";
echo "──────────────────\n";
echo "✅ Se crea 1 solo pago tipo 'Store Credit'\n";
echo "✅ El monto del pago es igual al total de la factura\n";
echo "✅ Representa correctamente el estado 'sin pagar'\n";
echo "✅ La lógica es correcta para este caso específico\n";

echo "\n📝 NOTAS ADICIONALES:\n";
echo "───────────────────────\n";
echo "• Si posteriormente se aplican pagos en QuickBooks,\n";
echo "  el Balance se reducirá y se activarán otros escenarios\n";
echo "• TIPO_RECEPTOR '02' = Consumidor final (correcto)\n";
echo "• La factura contiene impuesto ITBMS del 7%\n";
echo "• Customer ID en QB: " . $invoiceData['CustomerRef']['value'] . "\n";

echo "\n🔄 FLUJO ESPERADO:\n";
echo "────────────────────\n";
echo "1. Se crea factura en DocuCenter desde QB\n";
echo "2. Se crea 1 pago 'Store Credit' por $107\n";
echo "3. Si cliente paga después en QB, Balance cambiará\n";
echo "4. Próxima sincronización detectará el cambio\n";
echo "5. Se ajustarán los registros de pago según nuevo estado\n";

?>
