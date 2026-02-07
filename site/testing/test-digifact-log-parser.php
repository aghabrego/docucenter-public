<?php

/**
 * Test Final: Procesar JSON real extraído del log
 *
 * Busca y procesa respuestas JSON de Digifact del log de producción
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║       TEST: Procesar JSON Real de Digifact desde el Log            ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// Leer el log
$logFile = __DIR__ . '/../../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "✗ No se encontró: $logFile\n";
    exit(1);
}

echo "📄 Leyendo log: " . basename($logFile) . "\n";
$logContent = file_get_contents($logFile);
echo "📊 Tamaño: " . number_format(strlen($logContent)) . " bytes\n\n";

// Buscar secciones que contienen "data":{"code"
echo "🔍 Buscando respuestas JSON con formato 'data':{...}\n\n";

// Regex para encontrar el patrón "data":{"code": seguido del contenido hasta "responseData1"
$pattern = '/"data"\s*:\s*\{\s*"code"\s*:\s*(\d+)[^}]*"responseData1"\s*:\s*"([^"]{100,})"/';

if (!preg_match_all($pattern, $logContent, $matches, PREG_OFFSET_CAPTURE)) {
    echo "✗ No se encontraron respuestas JSON con responseData1\n";
    exit(1);
}

echo "✓ Se encontraron " . count($matches[0]) . " respuestas JSON\n\n";

$totalProcessed = 0;
$cufeArray = [];

for ($idx = 0; $idx < count($matches[0]); $idx++) {
    echo str_repeat("═", 70) . "\n";
    echo "RESPUESTA #" . ($idx + 1) . " DE " . count($matches[0]) . "\n";
    echo str_repeat("═", 70) . "\n\n";

    $codigo = $matches[1][$idx][0];
    $base64Data = $matches[2][$idx][0];

    echo "✓ Código: $codigo\n";
    echo "✓ Base64 tamaño: " . strlen($base64Data) . " caracteres\n\n";

    try {
        // Decodificar base64
        $xmlDecoded = base64_decode($base64Data);

        if ($xmlDecoded === false) {
            throw new \Exception('No se pudo decodificar base64');
        }

        echo "✓ XML decodificado: " . strlen($xmlDecoded) . " bytes\n\n";

        // Extraer campos del XML
        $fields = [
            'dId' => '/<dId>([^<]+)<\/dId>/',
            'dNroDF' => '/<dNroDF>([^<]+)<\/dNroDF>/',
            'dFechaEm' => '/<dFechaEm>([^<]+)<\/dFechaEm>/',
            'dHoraEm' => '/<dHoraEm>([^<]+)<\/dHoraEm>/',
            'tiPOp' => '/<tiPOp>([^<]+)<\/tiPOp>/',
            'iAmb' => '/<iAmb>([^<]+)<\/iAmb>/',
            'dPtoFacDF' => '/<dPtoFacDF>([^<]+)<\/dPtoFacDF>/',
        ];

        $xmlData = [];
        echo "✓ Extrayendo campos del XML:\n";

        foreach ($fields as $fieldName => $fieldPattern) {
            if (preg_match($fieldPattern, $xmlDecoded, $fieldMatches)) {
                $value = trim($fieldMatches[1]);
                $xmlData[$fieldName] = $value;

                if ($fieldName === 'dId') {
                    echo "    📋 $fieldName: " . $value . "\n";
                } else {
                    echo "    ✓ $fieldName: $value\n";
                }
            }
        }

        if (!empty($xmlData['dId'])) {
            $cufeArray[] = [
                'numero' => ($idx + 1),
                'codigo' => $codigo,
                'cufe' => $xmlData['dId'],
                'documento' => $xmlData['dNroDF'] ?? 'N/A',
                'fecha' => $xmlData['dFechaEm'] ?? 'N/A',
            ];
            $totalProcessed++;
        }

    } catch (\Exception $e) {
        echo "⚠ Error procesando: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// Resumen
echo str_repeat("═", 70) . "\n";
echo "RESUMEN FINAL\n";
echo str_repeat("═", 70) . "\n\n";

echo "✓ Respuestas procesadas: " . count($matches[0]) . "\n";
echo "✓ CUFEs extraídos: $totalProcessed\n\n";

if (count($cufeArray) > 0) {
    echo "📋 CUFEs ENCONTRADOS EN EL LOG:\n";
    echo str_repeat("─", 70) . "\n\n";

    foreach ($cufeArray as $item) {
        echo "Respuesta #" . $item['numero'] . ":\n";
        echo "  • Código: " . $item['codigo'] . "\n";
        echo "  • CUFE: " . $item['cufe'] . "\n";
        echo "  • Documento: " . $item['documento'] . "\n";
        echo "  • Fecha: " . $item['fecha'] . "\n\n";
    }
}

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                   TEST COMPLETADO ✓                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
