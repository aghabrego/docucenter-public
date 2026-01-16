<?php
/**
 * Script de Prueba Simplificado: Verificación de Métodos Helper
 *
 * Propósito: Verificar que los métodos helper estén implementados correctamente
 * en todos los archivos de facturación.
 *
 * Uso: php docs/testing/test-helper-implementation.php
 *
 * @author DocuCenter Team
 * @date 2025-01-20
 */

echo "\n=== VERIFICACIÓN DE IMPLEMENTACIÓN DE MÉTODOS HELPER ===\n";
echo "Validando que todos los archivos tengan los métodos necesarios\n\n";

$files = [
    'Create.php' => __DIR__ . '/../../app/Http/Livewire/Admin/Einvoice/Create.php',
    'CreateFast.php' => __DIR__ . '/../../app/Http/Livewire/Admin/Einvoice/CreateFast.php',
    'CreateFastJob.php' => __DIR__ . '/../../app/Http/Livewire/Admin/Einvoice/CreateFastJob.php'
];

$requiredMethods = [
    'getUnifiedForeignReceiverData',
    'getPaisFromNacionalidad',
    'getPaisCodeFromNacionalidad'
];

$totalFiles = count($files);
$passedFiles = 0;

foreach ($files as $fileName => $filePath) {
    echo "🔍 Analizando: $fileName\n";

    if (!file_exists($filePath)) {
        echo "   ❌ Archivo no encontrado: $filePath\n\n";
        continue;
    }

    $content = file_get_contents($filePath);
    $methodsFound = 0;
    $methodsImplemented = [];

    foreach ($requiredMethods as $method) {
        // Buscar la definición del método
        $pattern = '/private\s+function\s+' . preg_quote($method, '/') . '\s*\(/';

        if (preg_match($pattern, $content)) {
            $methodsFound++;
            $methodsImplemented[] = $method;
            echo "   ✅ $method - Encontrado\n";
        } else {
            echo "   ❌ $method - NO encontrado\n";
        }
    }

    // Verificar uso del método getUnifiedForeignReceiverData
    if (strpos($content, 'getUnifiedForeignReceiverData()') !== false) {
        echo "   ✅ getUnifiedForeignReceiverData() - Usado en el código\n";
    } else {
        echo "   ⚠️  getUnifiedForeignReceiverData() - NO usado en el código\n";
    }

    // Verificar campo dPaisExt
    if (strpos($content, 'dPaisExt') !== false) {
        echo "   ✅ dPaisExt - Campo presente\n";
    } else {
        echo "   ❌ dPaisExt - Campo NO presente\n";
    }

    // Verificar campo tipoIdentificacion para TheFactoryHKA
    if (strpos($content, "tipoIdentificacion") !== false) {
        echo "   ✅ tipoIdentificacion - Campo presente (TheFactoryHKA)\n";
    } else {
        echo "   ❌ tipoIdentificacion - Campo NO presente\n";
    }

    $isComplete = ($methodsFound === count($requiredMethods));

    if ($isComplete) {
        echo "   🎯 COMPLETO: Todos los métodos helper implementados\n";
        $passedFiles++;
    } else {
        echo "   ⚠️  INCOMPLETO: " . (count($requiredMethods) - $methodsFound) . " métodos faltantes\n";
    }

    echo "\n" . str_repeat("-", 60) . "\n\n";
}

// Verificar consistencia en el uso de dPaisExt
echo "🔍 VERIFICACIÓN DE CONSISTENCIA dPaisExt:\n\n";

$dPaisExtUsage = [];
foreach ($files as $fileName => $filePath) {
    $content = file_get_contents($filePath);

    // Contar ocurrencias de dPaisExt
    $occurrences = substr_count($content, 'dPaisExt');
    $dPaisExtUsage[$fileName] = $occurrences;

    echo "   📊 $fileName: $occurrences ocurrencias de 'dPaisExt'\n";

    // Verificar patrón de implementación
    if (strpos($content, "'dPaisExt' =>") !== false) {
        echo "   ✅ $fileName: Usa formato array asociativo para dPaisExt\n";
    } else {
        echo "   ⚠️  $fileName: NO usa formato array asociativo para dPaisExt\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

echo "🎯 RESUMEN FINAL:\n";
echo "   Archivos analizados: $totalFiles\n";
echo "   Archivos completos: $passedFiles\n";
echo "   Archivos incompletos: " . ($totalFiles - $passedFiles) . "\n\n";

// Verificar que todos tengan implementación de dPaisExt
$allHaveDPaisExt = true;
foreach ($dPaisExtUsage as $fileName => $count) {
    if ($count === 0) {
        $allHaveDPaisExt = false;
        echo "❌ $fileName no tiene implementación de dPaisExt\n";
    }
}

if ($passedFiles === $totalFiles && $allHaveDPaisExt) {
    echo "✅ ÉXITO TOTAL: Todos los archivos tienen implementación completa y consistente\n";
    echo "🔧 TheFactoryHKA dPaisExt fix implementado correctamente en todos los archivos\n";
    echo "🎯 Los métodos helper garantizan consistencia y robustez\n";
    exit(0);
} else {
    echo "⚠️  REVISIÓN NECESARIA: Algunos archivos requieren ajustes\n";

    if ($passedFiles === $totalFiles) {
        echo "✅ Todos los métodos helper están implementados\n";
    }

    if ($allHaveDPaisExt) {
        echo "✅ Todos los archivos manejan dPaisExt\n";
    }

    exit(1);
}
