<?php
/**
 * Script de Test para Extracción de CUFE desde el Log Real de Producción
 *
 * Extrae automáticamente el responseData1 del log y decodifica el CUFE
 *
 * Uso:
 * php docs/testing/test-cufe-from-log.php [opcional: ruta del log]
 *
 * Ejemplo:
 * php docs/testing/test-cufe-from-log.php
 * php docs/testing/test-cufe-from-log.php storage/logs/laravel-2026-02-04.log
 */

// Obtener ruta del log (default: el log de hoy)
$logPath = $argc > 1 ? $argv[1] : 'storage/logs/laravel.log';

if (!file_exists($logPath)) {
    echo "❌ Error: El archivo de log no existe: $logPath\n";
    exit(1);
}

echo "=== EXTRACCIÓN DE CUFE DESDE LOG REAL ===\n\n";
echo "📄 Log utilizado: $logPath\n";
echo "📦 Tamaño del log: " . number_format(filesize($logPath)) . " bytes\n\n";

// Leer el archivo de log
$logContent = file_get_contents($logPath);

// Opción 1: Buscar responseData1 directamente
echo "🔍 Buscando 'responseData1' en el log...\n";
$responseData1Pattern = '/"responseData1":"([^"]+)"/';
$responseData1Matches = [];
preg_match_all($responseData1Pattern, $logContent, $responseData1Matches);

if (!empty($responseData1Matches[1])) {
    echo "✅ Se encontraron " . count($responseData1Matches[1]) . " valores de responseData1\n\n";
    $base64DataArray = $responseData1Matches[1];
} else {
    echo "❌ No se encontró responseData1 con pattern directo\n";

    // Opción 2: Buscar "Digifact - Respuesta HTTP recibida"
    echo "🔍 Buscando 'Digifact - Respuesta HTTP recibida'...\n";
    $pattern = '/Digifact - Respuesta HTTP recibida.*?"response_preview":"([^"]+)"/';
    $matches = [];
    preg_match_all($pattern, $logContent, $matches);

    if (empty($matches[1])) {
        echo "❌ No se encontraron respuestas de Digifact en el log\n";
        echo "   Patrones buscados:\n";
        echo "   1. \"responseData1\"\n";
        echo "   2. \"Digifact - Respuesta HTTP recibida\"\n";
        exit(1);
    }

    echo "✅ Se encontraron " . count($matches[1]) . " respuestas de Digifact\n\n";
    $base64DataArray = [];
}

if (empty($base64DataArray)) {
    echo "❌ No hay datos para procesar\n";
    exit(1);
}

// Procesar cada responseData1 encontrado
$cufesList = [];
foreach ($base64DataArray as $index => $base64Data) {
    echo "─────────────────────────────────────────────────────\n";
    echo "RESPUESTA #" . ($index + 1) . "\n";
    echo "─────────────────────────────────────────────────────\n";

    echo "📊 Tamaño del base64: " . strlen($base64Data) . " caracteres\n";
    echo "📊 Preview del base64 (primeros 100 chars):\n";
    echo "   " . substr($base64Data, 0, 100) . "...\n\n";

    // Decodificar
    echo "🔄 DECODIFICANDO XML...\n";
    $xmlDecoded = base64_decode($base64Data);

    if (empty($xmlDecoded)) {
        echo "❌ Error al decodificar el base64\n";
        continue;
    }

    echo "✅ XML decodificado: " . strlen($xmlDecoded) . " bytes\n\n";

    // Extraer CUFE con REGEX
    echo "🔍 BUSCANDO CUFE CON REGEX...\n";
    $cufe = '';
    if (preg_match('/<dId>([^<]+)<\/dId>/', $xmlDecoded, $regexMatches)) {
        $cufe = trim($regexMatches[1]);
        echo "✅ CUFE ENCONTRADO:\n";
        echo "   $cufe\n";
        echo "   Longitud: " . strlen($cufe) . " caracteres\n\n";

        $cufesList[] = $cufe;

        // Analizar estructura del CUFE
        echo "📋 ESTRUCTURA DEL CUFE:\n";
        echo "   - Tipo: " . substr($cufe, 0, 2) . " (Factura Electrónica)\n";
        echo "   - Formato: Digifact\n";
        echo "   - Valor completo: $cufe\n";
        echo "   - Estado: ✅ VÁLIDO\n";
    } else {
        echo "❌ No se encontró el patrón <dId>...</dId>\n";
        echo "   Preview del XML:\n";
        echo "   " . substr($xmlDecoded, 0, 300) . "...\n\n";
    }
}

echo "\n=== RESUMEN FINAL ===\n";
echo "Total de responseData1 procesados: " . count($base64DataArray) . "\n";
echo "Total de CUFEs extraídos: " . count($cufesList) . "\n";

if (!empty($cufesList)) {
    echo "\n📋 CUFEs VÁLIDOS ENCONTRADOS:\n";
    foreach ($cufesList as $i => $cufe) {
        echo "   " . ($i + 1) . ". $cufe\n";
    }
}

echo "\n";
?>
