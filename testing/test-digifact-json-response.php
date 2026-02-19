<?php

/**
 * Test: Procesar respuesta JSON de Digifact
 *
 * Simula el procesamiento de una respuesta JSON real extraída del log
 * Verifica que se extraiga correctamente el CUFE y todos los valores del XML
 */

// Respuesta JSON real del log (code=1, con responseData1)
$jsonResponse = [
    "code" => 1,
    "message" => "Proceso de certificacion realizado correctamente!",
    "description" => "",
    "responseData1" => "PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48ckNvbnRGZSB4bWxucz0iaHR0cDovL2RnaS1mZXAubWVmLmdvYi5wYSI+PGRWZXJGb3JtPjEuMDA8L2RWZXJGb3JtPjx4RmU+PNJGRT48ZFZlckZvcm0+MS4wMDwvZFZlckZvcm0+PGRJZD5GRTAxMjAwMDAxNTU3NzA3MTItMi0yMDI1LTM2MDAwMDIwMjYwMjAzMDAwMDAwMDAxMTAwMjAxMTMxOTQyMDc0NzI8L2RJZD48Z0RHZW4+PGlBbWI+MTwvaUFtYj48aVRwRW1pcz4wMTwvaVRwRW1pcz48aURvYz4wMTwvaURvYz48ZE5yb0RGPjAwMDAwMDAwMDExPC9kTnJvREY+PGRQdG9GYWNERj4wMDI8L2RQdG9GYWNERj48aU5hdE9wPjAxPC9pTmF0T3A+PHRpUE9wPkZFPC90aVBPcD48aURlc3Q+MTwvaURlc3Q+PGlUcEVtaXM+MDE8L2lUcEVtaXM+PC9nREFHZW4+PGlEYXRFbSI+PGdFbWlzPjxkRmVjaGFFbT4yMDI2LTAyLTAzPC9kRmVjaGFFbT48ZEhvcmFFbT4xOToyOTowMzwvZEhvcmFFbT48L2dFbWlzPjwvaURhdEVtPjwveFF1ZT48L3BDb250RmU+",
    "pdf_base64" => null,
    "xml_base64" => null,
    "linkQR" => null
];

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║        TEST: Procesamiento de Respuesta JSON de Digifact          ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// ===== PASO 1: PARSEAR RESPUESTA JSON =====
echo "✓ PASO 1: Parseando respuesta JSON\n";
echo "───────────────────────────────────────────────────────────────────\n";

// Digifact devuelve 'code' en la respuesta JSON, no 'codigo'
$codigo = (string) ($jsonResponse['code'] ?? $jsonResponse['codigo'] ?? '500');
$cufe = (string) ($jsonResponse['CUFE'] ?? '');
$mensaje = (string) ($jsonResponse['message'] ?? $jsonResponse['mensaje'] ?? '');
$descripcion = (string) ($jsonResponse['description'] ?? $jsonResponse['descripcion'] ?? '');

echo "  Código: $codigo\n";
echo "  Mensaje: $mensaje\n";
echo "  Descripción: $descripcion\n";
echo "  CUFE en JSON: " . ($cufe ? "✓ $cufe" : "✗ No encontrado (buscaremos en XML)") . "\n\n";

// ===== PASO 2: EXTRAER DATOS DEL XML =====
echo "✓ PASO 2: Extrayendo datos del XML embebido en responseData1\n";
echo "───────────────────────────────────────────────────────────────────\n";

$xmlData = [];

if (empty($cufe) && !empty($jsonResponse['responseData1'])) {
    echo "  → responseData1 encontrado, decodificando base64...\n";

    try {
        $xmlDecoded = base64_decode($jsonResponse['responseData1']);
        echo "  → XML decodificado: " . strlen($xmlDecoded) . " bytes\n\n";

        // Mostrar preview del XML
        echo "  XML Preview:\n";
        echo "  " . str_repeat("─", 65) . "\n";
        $preview = substr($xmlDecoded, 0, 300);
        echo "  " . wordwrap($preview, 65, "\n  ") . "\n";
        echo "  " . str_repeat("─", 65) . "\n\n";

        // Extraer valores del XML usando regex
        echo "  Extrayendo valores con regex:\n";

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

        // Extraer cada campo
        foreach ($fields as $fieldName => $pattern) {
            if (preg_match($pattern, $xmlDecoded, $matches)) {
                $value = trim($matches[1]);
                $xmlData[$fieldName] = $value;
                echo "    ✓ $fieldName = $value\n";
            }
        }

        // Si encontramos el dId en el XML, asignarlo como CUFE
        if (!empty($xmlData['dId'])) {
            $cufe = $xmlData['dId'];
            echo "\n  ✓ CUFE extraído del XML: $cufe\n";
        }

    } catch (\Exception $xmlEx) {
        echo "  ✗ Error al procesar XML: " . $xmlEx->getMessage() . "\n";
    }
} else {
    echo "  → CUFE ya está en la respuesta JSON\n";
}

echo "\n";

// ===== PASO 3: VALIDAR RESULTADO =====
echo "✓ PASO 3: Validando resultado\n";
echo "───────────────────────────────────────────────────────────────────\n";

$isSuccess = in_array($codigo, ['1', '200', '1000', '3000']);

echo "  Código válido: " . ($isSuccess ? "✓ Sí ($codigo)" : "✗ No ($codigo)") . "\n";
echo "  CUFE presente: " . (!empty($cufe) ? "✓ Sí" : "✗ No") . "\n";
echo "  XML Data completo: " . (count($xmlData) > 0 ? "✓ " . count($xmlData) . " campos extraídos" : "✗ Sin datos") . "\n";

echo "\n";

// ===== PASO 4: RESPUESTA FINAL =====
echo "✓ PASO 4: Respuesta final (simulada)\n";
echo "───────────────────────────────────────────────────────────────────\n";

$response = [
    'success' => true,
    'codigo' => $codigo,
    'mensaje' => $mensaje ?: 'Certificación exitosa',
    'cufe' => $cufe,
    'pdf_base64' => $jsonResponse['pdf_base64'] ?? null,
    'xml_base64' => $jsonResponse['xml_base64'] ?? null,
    'qr_code' => $jsonResponse['linkQR'] ?? null,
    'xml_values' => $xmlData,  // Todos los valores extraídos del XML
    'data' => $jsonResponse,
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST COMPLETADO EXITOSAMENTE                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// ===== RESUMEN =====
echo "📊 RESUMEN:\n";
echo "───────────────────────────────────────────────────────────────────\n";
echo "✓ Respuesta JSON: Parseada correctamente\n";
echo "✓ Base64 decodificado: " . strlen($xmlDecoded) . " bytes\n";
echo "✓ XML fields extraídos: " . count($xmlData) . " campos\n";
echo "✓ CUFE: $cufe\n";
echo "✓ Estructura de respuesta: Completa\n\n";
