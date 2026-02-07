<?php
/**
 * Análisis y mejora sugerida para determinePaymentMethods
 */

// PROBLEMA ACTUAL: Asume que todo lo pagado es CREDIT_CARD
// MEJORA: Analizar datos reales de QuickBooks para determinar métodos exactos

/**
 * Versión mejorada que podría considerar datos reales de QB
 */
function determinePaymentMethodsEnhanced(
    float $totalAmount,
    float $balance,
    array $quickbooksPaymentData = []
): array {
    $payments = [];

    if ($balance == $totalAmount) {
        // Sin pagar - Todo pendiente
        $payments[] = [
            'method' => 'Store Credit',
            'amount' => $totalAmount,
            'type' => 'pending_credit',
            'description' => 'Crédito pendiente - Factura sin pagar'
        ];

    } elseif ($balance == 0) {
        // Totalmente pagado - Analizar datos reales de QB si están disponibles
        if (!empty($quickbooksPaymentData)) {
            // Procesar pagos reales de QuickBooks
            foreach ($quickbooksPaymentData as $payment) {
                $payments[] = [
                    'method' => mapQuickBooksPaymentMethod($payment['method'] ?? 'CREDIT_CARD'),
                    'amount' => $payment['amount'] ?? $totalAmount,
                    'type' => 'completed_payment',
                    'description' => $payment['description'] ?? 'Pago procesado en QuickBooks',
                    'qb_payment_id' => $payment['id'] ?? null
                ];
            }
        } else {
            // Fallback: asumir CREDIT_CARD
            $payments[] = [
                'method' => 'CREDIT_CARD',
                'amount' => $totalAmount,
                'type' => 'completed_payment',
                'description' => 'Pago completo (método no especificado)'
            ];
        }

    } else {
        // Pago parcial - Combinado
        $paidAmount = $totalAmount - $balance;

        if (!empty($quickbooksPaymentData)) {
            // Procesar pagos reales
            foreach ($quickbooksPaymentData as $payment) {
                $payments[] = [
                    'method' => mapQuickBooksPaymentMethod($payment['method'] ?? 'CREDIT_CARD'),
                    'amount' => $payment['amount'],
                    'type' => 'partial_payment',
                    'description' => $payment['description'] ?? 'Pago parcial procesado',
                    'qb_payment_id' => $payment['id'] ?? null
                ];
            }
        } else {
            // Fallback: asumir CREDIT_CARD para lo pagado
            $payments[] = [
                'method' => 'CREDIT_CARD',
                'amount' => $paidAmount,
                'type' => 'partial_payment',
                'description' => 'Pago parcial (método no especificado)'
            ];
        }

        // Saldo pendiente
        $payments[] = [
            'method' => 'Store Credit',
            'amount' => $balance,
            'type' => 'pending_credit',
            'description' => 'Saldo pendiente como crédito'
        ];
    }

    return $payments;
}

/**
 * Mapear métodos de pago de QuickBooks a nuestros métodos internos
 */
function mapQuickBooksPaymentMethod(string $qbMethod): string
{
    $mapping = [
        'CreditCard' => 'CREDIT_CARD',
        'Cash' => 'CASH',
        'Check' => 'CHECK',
        'BankTransfer' => 'BANK_TRANSFER',
        'Other' => 'OTHER'
    ];

    return $mapping[$qbMethod] ?? 'CREDIT_CARD';
}

echo "ANÁLISIS COMPLETO:\n";
echo "==================\n\n";

echo "📊 RESPUESTA A LA PREGUNTA:\n";
echo "NO, determinePaymentMethods NO siempre devuelve un solo pago\n";
echo "• Escenarios 1 y 2: 1 pago\n";
echo "• Escenario 3 (parcial): 2 pagos\n\n";

echo "🔍 CASOS DE USO:\n";
echo "• Balance = Total → 1 pago (Store Credit)\n";
echo "• Balance = 0 → 1 pago (CREDIT_CARD)\n";
echo "• 0 < Balance < Total → 2 pagos (CREDIT_CARD + Store Credit)\n\n";

echo "💡 OPORTUNIDADES DE MEJORA:\n";
echo "• Obtener métodos de pago reales de QuickBooks\n";
echo "• Mapear métodos QB a métodos internos\n";
echo "• Considerar múltiples formas de pago en facturas complejas\n";
echo "• Validar que la suma de pagos = total pagado\n\n";

echo "🎯 RECOMENDACIÓN:\n";
echo "El método actual es funcional pero básico.\n";
echo "Para mayor precisión, considerar integrar datos\n";
echo "reales de pagos desde la API de QuickBooks.\n";
?>
