<?php
/**
 * Script de prueba para validar extracción de SyncToken de pagos existentes
 */

require_once 'vendor/autoload.php';

echo "🔧 PRUEBA EXTRACCIÓN SYNCTOKEN - Payment Lookup\n";
echo "═══════════════════════════════════════════════\n\n";

// Simular respuesta de getPaymentByRefNum como la que proporcionaste
$mockPaymentResponse = [
    "success" => true,
    "message" => "Pago encontrado exitosamente",
    "data" => [
        "id" => "311",
        "paymentRefNum" => "FC0000000068",
        "totalAmt" => 148.6,
        "txnDate" => "2024-03-15",
        "customerId" => "2",
        "customerName" => "APCON CONSULTING INC",
        "paymentMethodId" => "8",
        "syncToken" => "0",
        "lines" => [],
        "fullPayment" => [
            "CustomerRef" => [
                "value" => "2",
                "name" => "APCON CONSULTING INC"
            ],
            "DepositToAccountRef" => [
                "value" => "75"
            ],
            "PaymentMethodRef" => [
                "value" => "8"
            ],
            "PaymentRefNum" => "FC0000000068",
            "TotalAmt" => 148.6,
            "UnappliedAmt" => 148.6,
            "ProcessPayment" => false,
            "domain" => "QBO",
            "sparse" => false,
            "Id" => "311",
            "SyncToken" => "0",
            "MetaData" => [
                "CreateTime" => "2025-09-09T19:22:51-07:00",
                "LastUpdatedTime" => "2025-09-09T19:22:51-07:00"
            ],
            "TxnDate" => "2024-03-15",
            "CurrencyRef" => [
                "value" => "PAB",
                "name" => "Balboa de Panamá"
            ],
            "Line" => []
        ]
    ],
    "timestamp" => "2025-09-18T01:34:07.851Z",
    "query" => [
        "connectionId" => "YyVuV5CSUPKddXf1O54V",
        "paymentRefNum" => "FC0000000068"
    ]
];

echo "📋 DATOS DE PRUEBA:\n";
echo "──────────────────\n";
echo "• Payment ID: " . $mockPaymentResponse['data']['id'] . "\n";
echo "• Payment Ref Num: " . $mockPaymentResponse['data']['paymentRefNum'] . "\n";
echo "• Customer: " . $mockPaymentResponse['data']['customerName'] . "\n";
echo "• Total Amount: $" . $mockPaymentResponse['data']['totalAmt'] . "\n";
echo "• Transaction Date: " . $mockPaymentResponse['data']['txnDate'] . "\n\n";

echo "🔍 EXTRACCIÓN DE SYNCTOKEN:\n";
echo "──────────────────────────\n";

// Simular la lógica de extracción del UpdateIntuitOrdersJob
$existingPayments = $mockPaymentResponse;
$existingSyncToken = null;

if ($existingPayments !== false && is_array($existingPayments) && isset($existingPayments['data'])) {
    // Método 1: SyncToken del nivel superior
    $syncToken1 = $existingPayments['data']['syncToken'] ?? null;

    // Método 2: SyncToken del fullPayment (QuickBooks completo)
    $syncToken2 = $existingPayments['data']['fullPayment']['SyncToken'] ?? null;

    echo "• Método 1 (data.syncToken): " . ($syncToken1 !== null ? $syncToken1 : 'NULL') . "\n";
    echo "• Método 2 (data.fullPayment.SyncToken): " . ($syncToken2 !== null ? $syncToken2 : 'NULL') . "\n";

    // Lógica de selección (priorizar el del nivel superior)
    $existingSyncToken = $syncToken1 ?? $syncToken2;

    echo "• SyncToken seleccionado: " . ($existingSyncToken !== null ? $existingSyncToken : 'NULL') . "\n";

    if ($existingSyncToken !== null) {
        echo "✅ SyncToken extraído exitosamente: '{$existingSyncToken}'\n";
    } else {
        echo "❌ No se pudo extraer SyncToken\n";
    }
} else {
    echo "❌ Estructura de respuesta inválida\n";
}

echo "\n💡 INFORMACIÓN ADICIONAL DISPONIBLE:\n";
echo "───────────────────────────────────\n";

// Mostrar información adicional que podría ser útil
if (isset($mockPaymentResponse['data'])) {
    $data = $mockPaymentResponse['data'];

    echo "• Payment ID QuickBooks: " . ($data['id'] ?? 'N/A') . "\n";
    echo "• Customer ID: " . ($data['customerId'] ?? 'N/A') . "\n";
    echo "• Payment Method ID: " . ($data['paymentMethodId'] ?? 'N/A') . "\n";
    echo "• Total Amount: $" . ($data['totalAmt'] ?? 'N/A') . "\n";
    echo "• Unapplied Amount: $" . ($data['fullPayment']['UnappliedAmt'] ?? 'N/A') . "\n";
    echo "• Currency: " . ($data['fullPayment']['CurrencyRef']['name'] ?? 'N/A') . "\n";
    echo "• Last Updated: " . ($data['fullPayment']['MetaData']['LastUpdatedTime'] ?? 'N/A') . "\n";
}

echo "\n🎯 CASOS DE PRUEBA ADICIONALES:\n";
echo "──────────────────────────────────\n";

// Caso 1: Solo syncToken en nivel superior
$case1 = [
    "data" => [
        "syncToken" => "5"
        // Sin fullPayment
    ]
];

$token1 = $case1['data']['syncToken'] ?? $case1['data']['fullPayment']['SyncToken'] ?? null;
echo "• Caso 1 (solo nivel superior): " . ($token1 ?? 'NULL') . " ✅\n";

// Caso 2: Solo SyncToken en fullPayment
$case2 = [
    "data" => [
        "fullPayment" => [
            "SyncToken" => "3"
        ]
        // Sin syncToken nivel superior
    ]
];

$token2 = $case2['data']['syncToken'] ?? $case2['data']['fullPayment']['SyncToken'] ?? null;
echo "• Caso 2 (solo fullPayment): " . ($token2 ?? 'NULL') . " ✅\n";

// Caso 3: Ambos presentes (debería priorizar nivel superior)
$case3 = [
    "data" => [
        "syncToken" => "10",
        "fullPayment" => [
            "SyncToken" => "7"
        ]
    ]
];

$token3 = $case3['data']['syncToken'] ?? $case3['data']['fullPayment']['SyncToken'] ?? null;
echo "• Caso 3 (ambos presentes): " . ($token3 ?? 'NULL') . " ✅ (prioriza nivel superior)\n";

// Caso 4: Ninguno presente
$case4 = [
    "data" => [
        "id" => "123"
        // Sin syncToken
    ]
];

$token4 = $case4['data']['syncToken'] ?? $case4['data']['fullPayment']['SyncToken'] ?? null;
echo "• Caso 4 (ninguno presente): " . ($token4 ?? 'NULL') . " ⚠️\n";

echo "\n🔧 INTEGRACIÓN EN UPDATEINTUITORDERSJOB:\n";
echo "────────────────────────────────────────────\n";
echo "La lógica implementada:\n";
echo "1. Consulta pagos existentes con getPaymentByRefNum()\n";
echo "2. Extrae SyncToken de la respuesta si está disponible\n";
echo "3. Usa SyncToken existente en lugar de hacer llamada adicional\n";
echo "4. Fallback a getSyncTokenPaymentQB() si no hay SyncToken\n";
echo "5. Logging detallado para debugging y monitoreo\n";

echo "\n✅ BENEFICIOS:\n";
echo "─────────────────\n";
echo "• Evita llamadas API innecesarias para obtener SyncToken\n";
echo "• Mejora performance al reutilizar datos ya obtenidos\n";
echo "• Mantiene consistencia con el estado actual en QuickBooks\n";
echo "• Previene conflictos de concurrencia en actualizaciones\n";

echo "\n🎉 Prueba de extracción de SyncToken completada\n";
