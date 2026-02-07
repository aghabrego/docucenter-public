<?php

/**
 * Test simplificado: Validar procesamiento JSON
 *
 * Utiliza el primer JSON extraído del log
 */

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║      TEST: Validar Procesamiento de JSON desde el log real        ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Leer el log
$logFile = __DIR__ . '/../../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "✗ No se encontró: $logFile\n";
    exit(1);
}

echo "📄 Leyendo log...\n";
$logContent = file_get_contents($logFile);

// Buscar una línea con JSON que tenga code y responseData1
$lines = explode("\n", $logContent);
$jsonString = null;
$jsonCount = 0;

foreach ($lines as $line) {
    if (strpos($line, '"code":') !== false && strpos($line, '"responseData1":"') !== false) {
        // Encontramos una línea que parece tener JSON
        if (strpos($line, '{"code"') !== false) {
            // Extraer desde {"code" hasta el final
            $startPos = strpos($line, '{"code"');
            if ($startPos !== false) {
                // Buscar el último cierre de llave en esta línea
                $jsonPart = substr($line, $startPos);

                // Intentar decodificar para validar
                @json_decode($jsonPart);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $jsonString = $jsonPart;
                    $jsonCount++;

                    if ($jsonCount === 1) {
                        // Usar el primero
                        break;
                    }
                }
            }
        }
    }
}

if (empty($jsonString)) {
    echo "✗ No se encontró JSON válido en el log\n";
    exit(1);
}

echo "✓ Se encontró JSON válido en el log\n";
echo "✓ Tamaño del JSON: " . strlen($jsonString) . " bytes\n\n";

echo "────────────────────────────────────────────────────────────────────\n";
echo "PROCESANDO JSON ENCONTRADO\n";
echo "────────────────────────────────────────────────────────────────────\n\n";

// Procesar el JSON
try {
    $jsonResponse = json_decode($jsonString, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Error al parsear: ' . json_last_error_msg());
    }

    // Extraer datos básicos
    $codigo = (string) ($jsonResponse['code'] ?? $jsonResponse['codigo'] ?? '500');
    $cufe = (string) ($jsonResponse['CUFE'] ?? '');
    $mensaje = (string) ($jsonResponse['message'] ?? $jsonResponse['mensaje'] ?? '');
    $descripcion = (string) ($jsonResponse['description'] ?? $jsonResponse['descripcion'] ?? '');

    echo "✓ Código: $codigo\n";
    echo "✓ Mensaje: $mensaje\n";
    echo "✓ Descripción: " . ($descripcion ?: '(vacío)') . "\n\n";

    // Extraer del XML
    $xmlData = [];

    if (empty($cufe) && !empty($jsonResponse['responseData1'])) {
        echo "→ Extrayendo datos del XML embebido en responseData1...\n";

        try {
            $xmlDecoded = base64_decode($jsonResponse['responseData1']);
            echo "✓ Base64 decodificado: " . strlen($xmlDecoded) . " bytes\n";

            // Campos a extraer
            $fields = [
                'dId' => '/<dId>([^<]+)<\/dId>/',
                'dVerForm' => '/<dVerForm>([^<]+)<\/dVerForm>/',
                'iAmb' => '/<iAmb>([^<]+)<\/iAmb>/',
                'iTpEmis' => '/<iTpEmis>([^<]+)<\/iTpEmis>/',
                'iDoc' => '/<iDoc>([^<]+)<\/iDoc>/',
                'dNroDF' => '/<dNroDF>([^<]+)<\/dNroDF>/',
                'dPtoFacDF' => '/<dPtoFacDF>([^<]+)<\/dPtoFacDF>/',
                'iNatOp' => '/<iNatOp>([^<]+)<\/iNatOp>/',
                'tiPOp' => '/<tiPOp>([^<]+)<\/tiPOp>/',
                'iDest' => '/<iDest>([^<]+)<\/iDest>/',
                'dFechaEm' => '/<dFechaEm>([^<]+)<\/dFechaEm>/',
                'dHoraEm' => '/<dHoraEm>([^<]+)<\/dHoraEm>/',
            ];

            echo "\n✓ Extrayendo campos del XML:\n";
            foreach ($fields as $fieldName => $pattern) {
                if (preg_match($pattern, $xmlDecoded, $matches)) {
                    $value = trim($matches[1]);
                    $xmlData[$fieldName] = $value;
                    echo "    ✓ $fieldName = $value\n";
                }
            }

            if (!empty($xmlData['dId'])) {
                $cufe = $xmlData['dId'];
            }

        } catch (\Exception $e) {
            echo "⚠ Error procesando XML: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
    echo "────────────────────────────────────────────────────────────────────\n";
    echo "RESULTADO FINAL\n";
    echo "────────────────────────────────────────────────────────────────────\n\n";

    $response = [
        'success' => true,
        'codigo' => $codigo,
        'mensaje' => $mensaje ?: 'Certificación exitosa',
        'cufe' => $cufe,
        'pdf_base64' => $jsonResponse['pdf_base64'] ?? null,
        'xml_base64' => $jsonResponse['xml_base64'] ?? null,
        'qr_code' => $jsonResponse['linkQR'] ?? null,
        'xml_values' => $xmlData,
        'data' => $jsonResponse,
    ];

    echo "✓ Respuesta JSON generada correctamente:\n\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════════════╗\n";
    echo "║                     TEST EXITOSO ✓                               ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
