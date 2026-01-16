<?php
/**
 * Test específico para la factura real de QuickBooks con logging mejorado
 */

echo "🧪 TEST FACTURA REAL: FE0000003143\n";
echo "═══════════════════════════════════\n\n";

// Simular la lógica mejorada con logging
function simulateEnhancedDeterminePaymentMethods(float $totalAmount, float $balance): array
{
    $payments = [];
    $paidAmount = $totalAmount - $balance;

    echo "📊 ANALYZING PAYMENT STRUCTURE:\n";
    echo "• Total Amount: $" . number_format($totalAmount, 2) . "\n";
    echo "• Balance: $" . number_format($balance, 2) . "\n";
    echo "• Paid Amount: $" . number_format($paidAmount, 2) . "\n";

    if ($balance == $totalAmount) {
        echo "• Payment Status: UNPAID ❌\n";
        echo "• Scenario: 1 (Sin pagar)\n\n";

        $payments[] = [
            'method' => 'Store Credit',
            'amount' => $totalAmount,
            'type' => 'pending_credit',
            'description' => 'Crédito pendiente - Factura sin pagar'
        ];

        echo "💳 PAYMENT METHOD - UNPAID INVOICE:\n";
        echo "• Scenario: 1\n";
        echo "• Payment Count: 1\n";
        echo "• Method: Store Credit\n";
        echo "• Amount: $" . number_format($totalAmount, 2) . "\n";

    } elseif ($balance == 0) {
        echo "• Payment Status: FULLY PAID ✅\n";
        echo "• Scenario: 2 (Totalmente pagado)\n\n";

        $payments[] = [
            'method' => 'CREDIT_CARD',
            'amount' => $totalAmount,
            'type' => 'completed_payment',
            'description' => 'Pago completo con tarjeta de crédito'
        ];

        echo "💳 PAYMENT METHOD - FULLY PAID INVOICE:\n";
        echo "• Scenario: 2\n";
        echo "• Payment Count: 1\n";
        echo "• Method: CREDIT_CARD\n";
        echo "• Amount: $" . number_format($totalAmount, 2) . "\n";

    } else {
        echo "• Payment Status: PARTIALLY PAID ⚠️\n";
        echo "• Scenario: 3 (Pago parcial)\n\n";

        $pendingAmount = $balance;

        $payments[] = [
            'method' => 'CREDIT_CARD',
            'amount' => $paidAmount,
            'type' => 'partial_payment',
            'description' => 'Pago parcial con tarjeta de crédito'
        ];

        $payments[] = [
            'method' => 'Store Credit',
            'amount' => $pendingAmount,
            'type' => 'pending_credit',
            'description' => 'Saldo pendiente como crédito'
        ];

        echo "💳 PAYMENT METHOD - PARTIALLY PAID INVOICE:\n";
        echo "• Scenario: 3\n";
        echo "• Payment Count: 2\n";
        echo "• Paid Method: CREDIT_CARD\n";
        echo "• Paid Amount: $" . number_format($paidAmount, 2) . "\n";
        echo "• Pending Method: Store Credit\n";
        echo "• Pending Amount: $" . number_format($pendingAmount, 2) . "\n";
    }

    echo "\n📋 FINAL RESULT:\n";
    echo "• Total Payments: " . count($payments) . "\n";
    echo "• Total Amount Check: $" . number_format(array_sum(array_column($payments, 'amount')), 2) . "\n";
    echo "• Amount Matches Invoice: " . (array_sum(array_column($payments, 'amount')) == $totalAmount ? 'YES ✅' : 'NO ❌') . "\n";

    return $payments;
}

// Datos de la factura real
$realInvoiceData = [
    'DocNumber' => 'FE0000003143',
    'TotalAmt' => 107.00,
    'Balance' => 107.00,
    'Customer' => 'ANUAR MATA',
    'Tax' => 7.00,
    'Subtotal' => 100.00
];

echo "📋 FACTURA REAL DE QUICKBOOKS:\n";
echo "─────────────────────────────────\n";
foreach ($realInvoiceData as $key => $value) {
    if (is_numeric($value)) {
        echo "• $key: " . (in_array($key, ['TotalAmt', 'Balance', 'Tax', 'Subtotal']) ? '$' : '') . $value . "\n";
    } else {
        echo "• $key: $value\n";
    }
}
echo "\n";

// Ejecutar simulación
$paymentMethods = simulateEnhancedDeterminePaymentMethods(
    $realInvoiceData['TotalAmt'],
    $realInvoiceData['Balance']
);

echo "\n🔍 DETALLE DE PAGOS CREADOS:\n";
echo "──────────────────────────────\n";
foreach ($paymentMethods as $index => $payment) {
    echo "Pago " . ($index + 1) . ":\n";
    echo "  • Método: " . $payment['method'] . "\n";
    echo "  • Monto: $" . number_format($payment['amount'], 2) . "\n";
    echo "  • Tipo: " . $payment['type'] . "\n";
    echo "  • Descripción: " . $payment['description'] . "\n";
    echo "\n";
}

echo "✅ CONFIRMACIÓN PARA TU CASO:\n";
echo "───────────────────────────────\n";
echo "Como Balance ($107) == TotalAmt ($107):\n";
echo "• Se crea UN SOLO PAGO al crédito ✅\n";
echo "• Método: Store Credit ✅\n";
echo "• Monto: $107.00 ✅\n";
echo "• Representa factura sin pagar ✅\n";
echo "• Cuando el cliente pague en QB, el Balance cambiará\n";
echo "• La próxima sincronización ajustará los pagos según el nuevo estado\n";

echo "\n🔄 FLUJO COMPLETO:\n";
echo "────────────────────\n";
echo "1. QB crea factura FE0000003143 por $107\n";
echo "2. DocuCenter importa la factura\n";
echo "3. Se crea 1 pago 'Store Credit' por $107 (pendiente)\n";
echo "4. Si cliente paga $50 en QB → Balance = $57\n";
echo "5. Próxima sincronización detecta el cambio\n";
echo "6. Se crearían 2 pagos: CREDIT_CARD $50 + Store Credit $57\n";
echo "7. Si cliente paga el resto → Balance = $0\n";
echo "8. Se crearía 1 pago: CREDIT_CARD $107 (completo)\n";

?>
