<?php
/**
 * TEST: Verificación de traducciones mejoradas para Item Code
 *
 * PROPÓSITO:
 * - Verificar que la traducción "Item Code" se ha actualizado correctamente
 * - Confirmar que el cambio a "Código de Bienes y Servicios" es consistente
 * - Validar que las traducciones se cargan correctamente en ambos idiomas
 *
 * MEJORA IMPLEMENTADA:
 * - ES: "Item Code" → "Código de Bienes y Servicios" (antes: "Código de ítem")
 * - EN: "Item Code" → "Goods & Services Code" (antes: "Item Code")
 * - Consistente con "Panamanian Codification of Goods and Services"
 */

require_once __DIR__ . '/../../vendor/autoload.php';

echo "🧪 TEST: Verificación de Traducciones Item Code\n";
echo str_repeat("=", 60) . "\n\n";

// Función para simular la carga de traducciones
function loadTranslations($lang) {
    $filePath = __DIR__ . "/../../lang/{$lang}_panel.json";
    if (!file_exists($filePath)) {
        return null;
    }

    $content = file_get_contents($filePath);
    return json_decode($content, true);
}

// Función para simular la función __() de Laravel
function __($key, $replacements = [], $locale = 'es') {
    static $translations = [];

    if (!isset($translations[$locale])) {
        $translations[$locale] = loadTranslations($locale);
    }

    if (!$translations[$locale] || !isset($translations[$locale][$key])) {
        return $key; // Retornar la clave si no se encuentra traducción
    }

    $translation = $translations[$locale][$key];

    // Aplicar reemplazos si existen
    foreach ($replacements as $search => $replace) {
        $translation = str_replace(":$search", $replace, $translation);
    }

    return $translation;
}

echo "📋 VERIFICACIÓN DE TRADUCCIONES ACTUALIZADAS\n";
echo str_repeat("-", 50) . "\n\n";

// Test casos
$testCases = [
    [
        'key' => 'Item Code',
        'locale' => 'es',
        'expected' => 'Código de Bienes y Servicios',
        'description' => 'Español - Traducción original (retrocompatibilidad)'
    ],
    [
        'key' => 'Goods and Services Code',
        'locale' => 'es',
        'expected' => 'Código de Bienes y Servicios',
        'description' => 'Español - Nueva traducción específica'
    ],
    [
        'key' => 'Abbreviated Item Code',
        'locale' => 'es',
        'expected' => 'Código Abreviado de Bienes y Servicios',
        'description' => 'Español - Traducción abreviada'
    ],
    [
        'key' => 'Item Code',
        'locale' => 'en',
        'expected' => 'Goods & Services Code',
        'description' => 'Inglés - Traducción original (retrocompatibilidad)'
    ],
    [
        'key' => 'Goods and Services Code',
        'locale' => 'en',
        'expected' => 'Goods & Services Code',
        'description' => 'Inglés - Nueva traducción específica'
    ],
    [
        'key' => 'Abbreviated Item Code',
        'locale' => 'en',
        'expected' => 'Abbreviated Goods & Services Code',
        'description' => 'Inglés - Traducción abreviada'
    ]
];

$totalTests = count($testCases);
$passedTests = 0;

foreach ($testCases as $index => $testCase) {
    echo "Test " . ($index + 1) . ": {$testCase['description']}\n";
    echo "Clave: '{$testCase['key']}'\n";
    echo "Idioma: {$testCase['locale']}\n";

    $result = __($testCase['key'], [], $testCase['locale']);

    echo "Resultado: '$result'\n";
    echo "Esperado: '{$testCase['expected']}'\n";

    if ($result === $testCase['expected']) {
        echo "✅ PASSED\n";
        $passedTests++;
    } else {
        echo "❌ FAILED\n";
    }
    echo "\n";
}

// Verificar consistencia con traducciones relacionadas
echo "📋 VERIFICACIÓN DE CONSISTENCIA\n";
echo str_repeat("-", 50) . "\n\n";

$consistencyTests = [
    [
        'key' => 'Panamanian Codification of Goods and Services',
        'locale' => 'es',
        'expected' => 'Codificación Panameña de Bienes y Servicios',
        'description' => 'Consistencia con codificación panameña (ES)'
    ],
    [
        'key' => 'Panamanian Codification of Goods and Services',
        'locale' => 'en',
        'expected' => 'Panamanian Codification of Goods and Services',
        'description' => 'Consistencia con codificación panameña (EN)'
    ]
];

foreach ($consistencyTests as $index => $testCase) {
    echo "Consistencia " . ($index + 1) . ": {$testCase['description']}\n";

    $result = __($testCase['key'], [], $testCase['locale']);

    echo "Resultado: '$result'\n";
    echo "Esperado: '{$testCase['expected']}'\n";

    if ($result === $testCase['expected']) {
        echo "✅ CONSISTENTE\n";
        $passedTests++;
        $totalTests++;
    } else {
        echo "❌ INCONSISTENTE\n";
        $totalTests++;
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "RESUMEN: $passedTests/$totalTests tests pasaron\n";

if ($passedTests === $totalTests) {
    echo "🎉 ¡Todas las traducciones están correctas!\n\n";
    echo "📋 MEJORAS IMPLEMENTADAS:\n";
    echo "- ✅ ES: 'Goods and Services Code' → 'Código de Bienes y Servicios'\n";
    echo "- ✅ EN: 'Goods and Services Code' → 'Goods & Services Code'\n";
    echo "- ✅ Retrocompatibilidad: 'Item Code' mantiene misma traducción\n";
    echo "- ✅ Consistencia con nomenclatura fiscal panameña\n";
    echo "- ✅ Terminología más descriptiva y profesional\n";
    echo "- ✅ Coherencia con 'Codificación Panameña de Bienes y Servicios'\n";
} else {
    echo "⚠️ Algunos tests fallaron. Revisar archivos de traducción.\n";
}

echo "\n📍 UBICACIÓN DE CAMBIOS:\n";
echo "- Archivo ES: lang/es_panel.json (línea ~510: nueva clave 'Goods and Services Code')\n";
echo "- Archivo EN: lang/en_panel.json (línea ~510: nueva clave 'Goods and Services Code')\n";
echo "- Vista einvoice: create.blade.php (líneas 665, 917) - actualizada\n";
echo "- Vista ezee: create.blade.php (líneas 415, 660) - actualizada\n";
echo "- Retrocompatibilidad: 'Item Code' mantiene funcionamiento existente\n";
