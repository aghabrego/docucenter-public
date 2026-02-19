<?php
/**
 * Script de Test para Extracción de CUFE desde responseData1 de Digifact
 *
 * Uso:
 * php docs/testing/test-cufe-extraction.php
 */

echo "=== TEST DE EXTRACCIÓN DE CUFE DESDE responseData1 ===\n\n";

// El base64 completo del responseData1 del log
$base64ResponseData1 = "PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48ckNvbnRGZSB4bWxucz0iaHR0cDovL2RnaS1mZXAubWVmLmdvYi5wYSI+PGRWZXJGb3JtPjEuMDA8L2RWZXJGb3JtPjx4RmU+PNJGRT48ZFZlckZvcm0+MS4wMDwvZFZlckZvcm0+PGRJZD5GRTAxMjAwMDAxNTU3NzA3MTItMi0yMDI1LTM2MDAwMDIwMjYwMjAzMDAwMDAwMDAxMTAwMjAxMTMxOTQyMDc0NzI8L2RJZD48Z0RHZW4+PGlBbWI+MTwvaUFtYj48aVRwRW1pcz4wMTwvaVRwRW1pcz48aURvYz4wMTwvaURvYz48ZE5yb0RGPjAwMDAwMDAwMDExPC9kTnJvREY+PGRQdG9GYWNERj4wMDI8L2RQdG9GYWNERj48aU5hdE9wPjAxPC9pTmF0T3A+PHRpUE9wPkZFPC90aVBPcD48aURlc3Q+MTwvaURlc3Q+PGlUcEVtaXM+MDE8L2lUcEVtaXM+PC9nREFHZW4+PGlEYXRFbSI+PGdFbWlzPjxkRmVjaGFFbT4yMDI2LTAyLTAzPC9kRmVjaGFFbT48ZEhvcmFFbT4xOToyOTowMzwvZEhvcmFFbT48L2dFbWlzPjwvaURhdEVtPjwveFF1ZT48L3BDb250RmU+";

echo "1. DECODIFICAR BASE64\n";
echo "─────────────────────────────────────\n";
$xmlDecoded = base64_decode($base64ResponseData1);
echo "Longitud del XML decodificado: " . strlen($xmlDecoded) . " bytes\n";
echo "Preview (primeros 500 caracteres):\n";
echo substr($xmlDecoded, 0, 500) . "\n\n";

echo "2. EXTRAER CUFE CON REGEX\n";
echo "─────────────────────────────────────\n";
$cufe = '';
if (preg_match('/<dId>([^<]+)<\/dId>/', $xmlDecoded, $matches)) {
    $cufe = trim($matches[1]);
    echo "✅ CUFE encontrado: $cufe\n";
    echo "Longitud del CUFE: " . strlen($cufe) . " caracteres\n\n";
} else {
    echo "❌ No se encontró el patrón <dId>...</dId>\n\n";
}

echo "3. ANALIZAR ESTRUCTURA DEL CUFE\n";
echo "─────────────────────────────────────\n";
if (!empty($cufe)) {
    // Estructura esperada del CUFE de Digifact:
    // FE + DocType(2) + RUC(14) + DV(1) + AÑO(4) + Otros(N)
    // Ejemplo: FE01 2000 01557707122-2-2025-3600002026020300000000110020113194207472

    echo "Estructura:\n";
    echo "- Tipo de Comprobante: FE (Factura Electrónica)\n";
    echo "- Formato: " . (substr($cufe, 0, 2) === 'FE' ? 'FE (Digifact)' : 'Desconocido') . "\n";
    echo "- Longitud total: " . strlen($cufe) . " caracteres\n";
    echo "- Valor completo: $cufe\n\n";
}

echo "4. COMPARAR CON ESTRATEGIA SIMPLEXML\n";
echo "─────────────────────────────────────\n";
$cufeSimplexml = '';
try {
    $xmlDoc = simplexml_load_string($xmlDecoded);
    if ($xmlDoc !== false) {
        // Intentar múltiples rutas
        $dId = (string) (
            $xmlDoc->xFe->rFE->dId ??
            $xmlDoc->rFE->dId ??
            $xmlDoc->dId ??
            $xmlDoc->rComprobante->dId ??
            ''
        );
        if (!empty($dId)) {
            $cufeSimplexml = $dId;
        }
    }
} catch (Exception $e) {
    echo "⚠️  SimpleXML falló: " . $e->getMessage() . "\n";
}

if (!empty($cufeSimplexml)) {
    echo "✅ SimpleXML extrajo: $cufeSimplexml\n";
    echo "   Coincide con Regex: " . ($cufe === $cufeSimplexml ? '✅ SÍ' : '❌ NO') . "\n";
} else {
    echo "❌ SimpleXML no pudo extraer el CUFE\n";
    echo "   Regex es la mejor estrategia para este caso\n";
}

echo "\n5. CONCLUSIÓN\n";
echo "─────────────────────────────────────\n";
if (!empty($cufe)) {
    echo "✅ EXTRACCIÓN EXITOSA\n";
    echo "   CUFE: $cufe\n";
    echo "   Método: Regex (más robusto)\n";
} else {
    echo "❌ No se pudo extraer el CUFE\n";
}

echo "\n";
?>
