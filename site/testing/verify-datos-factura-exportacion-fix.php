<?php
/**
 * Test simple para verificar el cambio en AlanubeFormatterHelper
 * Verifica que el código fuente tenga el campo correcto
 */

$filePath = '/home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php';

echo "=== VERIFICACIÓN SIMPLE DEL FIX ===\n\n";

if (!file_exists($filePath)) {
    echo "❌ Archivo no encontrado: $filePath\n";
    exit(1);
}

$content = file_get_contents($filePath);

// Verificar que el fix está aplicado
$hasNewField = strpos($content, "dGen['datosFacturaExportacion']") !== false;
$hasOldField = strpos($content, "dGen['exportation'] = \$exportationData") !== false;

echo "1. VERIFICACIÓN EN CÓDIGO FUENTE:\n";
echo "   ✅ Archivo existe: $filePath\n";
echo "   " . ($hasNewField ? "✅" : "❌") . " Contiene 'datosFacturaExportacion': " . ($hasNewField ? "SÍ" : "NO") . "\n";
echo "   " . ($hasOldField ? "❌" : "✅") . " Contiene código viejo 'exportation': " . ($hasOldField ? "SÍ (PROBLEMA)" : "NO") . "\n";

// Buscar la línea específica
$lines = explode("\n", $content);
$foundCorrectLine = false;
$foundProblemLine = false;

foreach ($lines as $lineNum => $line) {
    if (strpos($line, "dGen['datosFacturaExportacion']") !== false) {
        echo "\n2. LÍNEA CORREGIDA ENCONTRADA (línea " . ($lineNum + 1) . "):\n";
        echo "   " . trim($line) . "\n";
        $foundCorrectLine = true;
    }

    if (strpos($line, "dGen['exportation'] = \$exportationData") !== false) {
        echo "\n❌ LÍNEA PROBLEMÁTICA ENCONTRADA (línea " . ($lineNum + 1) . "):\n";
        echo "   " . trim($line) . "\n";
        $foundProblemLine = true;
    }
}

echo "\n3. RESULTADO:\n";
if ($foundCorrectLine && !$foundProblemLine) {
    echo "   🎉 ¡FIX APLICADO CORRECTAMENTE!\n";
    echo "   El error 'El campo datosFacturaExportacion es requerido' debería estar resuelto\n";
} elseif ($foundCorrectLine && $foundProblemLine) {
    echo "   ⚠️  FIX PARCIAL - Hay código viejo y nuevo\n";
} elseif (!$foundCorrectLine && $foundProblemLine) {
    echo "   ❌ FIX NO APLICADO - Solo existe código viejo\n";
} else {
    echo "   ⚠️  ESTADO INCIERTO - No se encontraron las líneas esperadas\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "EXPLICACIÓN DEL FIX:\n";
echo "- El PAC Alanube esperaba el campo 'datosFacturaExportacion'\n";
echo "- El código enviaba 'exportation'\n";
echo "- Fix: Cambiar el nombre del campo en AlanubeFormatterHelper::formatForPanama()\n";
echo "- Resultado: El PAC ahora recibe el campo con el nombre esperado\n";
