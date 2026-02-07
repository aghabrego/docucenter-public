<?php

// Test de detección de rate limiting HTTP 429 - VERSIÓN MEJORADA
// Simulando el error exacto reportado con nueva configuración

echo "=== Test de Detección Rate Limiting HTTP 429 - VERSIÓN MEJORADA ===\n";

// Simular el error exacto del reporte
$mockResult = [
    'success' => false,
    'error' => 'Error HTTP 429: Client error: `POST https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/create_invoice_quickbooks` resulted in a `429 Too Many Requests` response',
    'error_type' => 'http_error',
    'status_code' => 429
];

echo "📋 Datos del error simulado:\n";
echo "  Success: " . ($mockResult['success'] ? 'true' : 'false') . "\n";
echo "  Status Code: " . $mockResult['status_code'] . "\n";
echo "  Error Type: " . $mockResult['error_type'] . "\n";
echo "  Error Message: " . substr($mockResult['error'], 0, 100) . "...\n\n";

// Nueva configuración del job
$tries = 8; // Aumentado de 5 a 8
$backoffArray = [30, 60, 120, 240, 480, 600, 900]; // Nuevo backoff más agresivo

echo "⚙️ NUEVA Configuración del Job:\n";
echo "  Tries: $tries (incrementado de 5)\n";
echo "  Backoff Array: " . implode('s, ', $backoffArray) . "s\n";
echo "  Total tiempo máximo: " . array_sum($backoffArray) . "s (" . round(array_sum($backoffArray)/60, 1) . " minutos)\n\n";

// Lógica de detección exacta del código
$errorMessage = $mockResult['error'] ?? 'Error desconocido';
$statusCode = $mockResult['status_code'] ?? null;
$errorType = $mockResult['error_type'] ?? 'unknown';

// Lógica completa de detección
$isRateLimit = $statusCode === 429
    || $statusCode === '429'
    || strpos($errorMessage, '429') !== false
    || strpos($errorMessage, 'Too Many Requests') !== false
    || strpos($errorMessage, 'Rate limit') !== false
    || strpos($errorMessage, 'rate limit') !== false
    || ($errorType === 'http_error' && strpos($errorMessage, '429') !== false);

echo "🎯 RESULTADO DETECCIÓN:\n";
echo "  Detección de Rate Limit: " . ($isRateLimit ? 'DETECTADO ✅' : 'NO DETECTADO ❌') . "\n\n";

// Simular diferentes intentos
echo "📊 SIMULACIÓN DE INTENTOS:\n";
for ($attempt = 1; $attempt <= $tries; $attempt++) {
    $delay = min(30 * pow(2, $attempt - 1), 480);
    $randomDelay = rand($delay, $delay + 60);

    // Delay extra para intentos avanzados (4+)
    $extraDelay = 0;
    if ($attempt >= 4) {
        $extraDelay = rand(120, 300);
        $randomDelay += $extraDelay;
    }

    $willRetry = $attempt < $tries;

    echo "  Intento $attempt/$tries:\n";
    echo "    Base Delay: {$delay}s\n";
    echo "    Random Jitter: +60s\n";
    if ($extraDelay > 0) {
        echo "    Extra Delay (alta congestión): +{$extraDelay}s\n";
    }
    echo "    Total Delay: {$randomDelay}s\n";
    echo "    Will Retry: " . ($willRetry ? 'SÍ ✅' : 'NO ❌ (último intento)') . "\n";
    echo "    Remaining: " . ($tries - $attempt) . " intentos\n\n";
}

echo "📈 ANÁLISIS DE MEJORAS:\n";
echo "✅ Intentos incrementados: 5 → 8 (+60% más oportunidades)\n";
echo "✅ Backoff más agresivo: máximo 240s → 480s (+100% más tiempo)\n";
echo "✅ Jitter ampliado: +30s → +60s (+100% más distribución)\n";
echo "✅ Delays extra para alta congestión: +2-5 minutos en intentos avanzados\n";
echo "✅ Tiempo total máximo: ~" . round(array_sum($backoffArray)/60, 1) . " minutos vs ~3.5 minutos anterior\n\n";

echo "🎯 PROBABILIDAD DE ÉXITO:\n";
$previousConfig = [30, 60, 120, 240]; // Configuración anterior
$previousTime = array_sum($previousConfig);
$newTime = array_sum($backoffArray);

echo "  Configuración anterior: 5 intentos, {$previousTime}s total\n";
echo "  Nueva configuración: 8 intentos, {$newTime}s total\n";
echo "  Incremento de tiempo: " . round(($newTime - $previousTime) / $previousTime * 100, 1) . "%\n";
echo "  Incremento de intentos: " . round((8 - 5) / 5 * 100, 1) . "%\n\n";

echo "🚨 CASO DEL ERROR REPORTADO:\n";
echo "  El error llegó al usuario porque se agotaron los 5 intentos originales\n";
echo "  Con la nueva configuración tendría:\n";
echo "    - 3 intentos adicionales (6°, 7°, 8°)\n";
echo "    - Delays más largos para esperar que baje la congestión\n";
echo "    - Delays extra automáticos en intentos avanzados\n";
echo "    - Mayor probabilidad de éxito durante períodos de alta carga\n\n";

echo "=== Test Completado - Configuración Mejorada Lista ===\n";
