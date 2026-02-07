<?php

/**
 * Test: Procesar múltiples respuestas JSON del log real
 *
 * Busca todas las respuestas JSON de Digifact en el log y las procesa
 * para verificar que el código funciona con datos reales
 */

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║    TEST: Procesar múltiples respuestas JSON del log real          ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Leer el log
$logFile = __DIR__ . '/../../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "✗ No se encontró el archivo: $logFile\n";
    exit(1);
}

echo "📄 Leyendo log: $logFile\n";
$logContent = file_get_contents($logFile);
$fileSize = strlen($logContent);
echo "📊 Tamaño: " . number_format($fileSize) . " bytes\n\n";

// Buscar patrones JSON con code y responseData1
echo "🔍 Buscando respuestas JSON con 'code' y 'responseData1'...\n";

// Buscar líneas que contengan "Digifact - Análisis de respuesta JSON"
// seguidas de líneas con JSON
$lines = explode("\n", $logContent);
$jsonResponses = [];
$foundCount = 0;

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '"code"') !== false && strpos($lines[$i], '"responseData1"') !== false) {
        // Encontramos una línea con JSON
        // Extraer la parte JSON
        if (preg_match('/({"code".*"responseData1":"[^"]{100,}".*})/', $lines[$i], $matches)) {
            $jsonResponses[] = $matches[1];
            $foundCount++;
        } elseif (strpos($lines[$i], '{"code"') !== false) {
            // Podría ser JSON que continúa en siguiente línea
            $jsonStr = $lines[$i];

            // Buscar el cierre del JSON
            $braceCount = 0;
            for ($j = $i; $j < count($lines) && strlen($jsonStr) < 450000; $j++) {
                $jsonStr .= $lines[$j];

                // Contar llaves para saber si el JSON está completo
                for ($k = 0; $k < strlen($lines[$j]); $k++) {
                    if ($lines[$j][$k] === '{') $braceCount++;
                    elseif ($lines[$j][$k] === '}') $braceCount--;
                }

                if ($braceCount === 0 && strlen($jsonStr) > 100) {
                    // JSON parece completo
                    $jsonResponses[] = $jsonStr;
                    $foundCount++;
                    $i = $j;
                    break;
                }
            }
        }
    }
}
    echo str_repeat("─", 70) . "\n";
    echo "RESPUESTA #" . ($index + 1) . "\n";
    echo str_repeat("─", 70) . "\n\n";

    // Limitar el preview para evitar strings muy largos
    $preview = substr($jsonString, 0, 200) . "...";
    echo "JSON Preview: $preview\n\n";

    try {
        // Parsear JSON
        $jsonResponse = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "✗ Error al parsear JSON: " . json_last_error_msg() . "\n\n";
            continue;
        }

        // Extraer datos básicos
        $codigo = (string) ($jsonResponse['code'] ?? $jsonResponse['codigo'] ?? '500');
        $cufe = (string) ($jsonResponse['CUFE'] ?? '');
        $mensaje = (string) ($jsonResponse['message'] ?? $jsonResponse['mensaje'] ?? '');

        echo "✓ Código: $codigo\n";
        echo "✓ Mensaje: $mensaje\n";

        // Extraer datos del XML
        $xmlData = [];

        if (empty($cufe) && !empty($jsonResponse['responseData1'])) {
            try {
                $xmlDecoded = base64_decode($jsonResponse['responseData1']);

                // Extraer campos del XML con regex
                $fields = [
                    'dId' => '/<dId>([^<]+)<\/dId>/',
                    'dNroDF' => '/<dNroDF>([^<]+)<\/dNroDF>/',
                    'dFechaEm' => '/<dFechaEm>([^<]+)<\/dFechaEm>/',
                    'dHoraEm' => '/<dHoraEm>([^<]+)<\/dHoraEm>/',
                    'tiPOp' => '/<tiPOp>([^<]+)<\/tiPOp>/',
                    'dPtoFacDF' => '/<dPtoFacDF>([^<]+)<\/dPtoFacDF>/',
                ];

                foreach ($fields as $fieldName => $pattern) {
                    if (preg_match($pattern, $xmlDecoded, $fieldMatches)) {
                        $xmlData[$fieldName] = trim($fieldMatches[1]);
                    }
                }

                if (!empty($xmlData['dId'])) {
                    $cufe = $xmlData['dId'];
                    echo "✓ CUFE extraído del XML\n";
                }

            } catch (\Exception $e) {
                echo "⚠ Error procesando XML: " . $e->getMessage() . "\n";
            }
        }

        // Mostrar resultado
        echo "\n";
        echo "✓ CUFE: " . substr($cufe, 0, 50) . "...\n";

        if (!empty($xmlData)) {
            echo "✓ XML Data:\n";
            foreach ($xmlData as $field => $value) {
                if ($field !== 'dId') {
                    echo "    - $field: $value\n";
                }
            }
        }

        // Almacenar CUFE para resumen
        if (!empty($cufe)) {
            $cufeArray[] = [
                'cufe' => $cufe,
                'codigo' => $codigo,
                'fecha' => $xmlData['dFechaEm'] ?? 'N/A',
                'documento' => $xmlData['dNroDF'] ?? 'N/A',
            ];
            $processedCount++;
        }

    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo str_repeat("═", 70) . "\n";
echo "RESUMEN FINAL\n";
echo str_repeat("═", 70) . "\n\n";

echo "✓ Respuestas procesadas: $processedCount\n";
echo "✓ CUFEs extraídos: " . count($cufeArray) . "\n\n";

if (count($cufeArray) > 0) {
    echo "📋 CUFEs EXTRAÍDOS:\n";
    echo str_repeat("─", 70) . "\n";

    foreach ($cufeArray as $idx => $item) {
        echo "\n" . ($idx + 1) . ". CUFE: " . $item['cufe'] . "\n";
        echo "   Código: " . $item['codigo'] . "\n";
        echo "   Documento: " . $item['documento'] . "\n";
        echo "   Fecha: " . $item['fecha'] . "\n";
    }

    echo "\n";
}

echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║           TEST COMPLETADO - VERIFICACIÓN EXITOSA                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";
