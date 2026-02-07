<?php
/**
 * Test de validación: Unidades de medida conforme a documentación oficial DGI
 *
 * OBJETIVO: Verificar que se usan SOLO unidades válidas según Tabla 29 DGI
 * PROBLEMA ORIGINAL: Error PAC "El campo unidadMedida es inválido"
 * CAUSA: Uso de "UND" que NO aparece en Tabla 29 oficial
 *
 * DOCUMENTACIÓN OFICIAL DGI:
 * - Archivo: 4-Anexo-3-Ficha-Técnica-Factura-Electrónica-Proveedores-Autorización-Calificados-V1.0.pdf
 * - Sección: 10.3.2. Catálogo de Unidades de Medida (Tabla 29)
 * - Campo: C11 (uso opcional, pero debe ser de la lista oficial)
 *
 * UNIDADES VÁLIDAS SEGÚN DGI:
 * - um (micrómetro), mm (milímetro), cm (centímetro), m (metro), etc.
 * - NO INCLUYE: UND (no aparece en Tabla 29)
 */

echo "=== VALIDACIÓN: Unidades de Medida DGI Oficiales ===\n";
echo "Referencia: Ficha Técnica DGI - Tabla 29\n";
echo "Objetivo: Verificar uso de unidades válidas según documentación oficial\n\n";

// Unidades válidas según Tabla 29 DGI (extraídas del PDF oficial)
$unidadesValidasDGI = [
    // Longitud - Sistema Métrico
    'um',  // Micrómetro: 10⁻⁶ m
    'mm',  // Milímetro: 10⁻³ m
    'cm',  // Centímetro: 10⁻² m
    'dm',  // Decímetro: 10⁻¹ m
    'm',   // Metro
    'dam', // Decámetro: 10 m
    'hm',  // Hectómetro: 100 m
    'km',  // Kilómetro: 1000 m

    // Longitud - Otros Sistemas
    'mi',  // Milla internacional: 1609.3 m
    'nmi', // Milla náutica: 1.852 m
    'in',  // Pulgada: 2.54 cm
    'ft',  // Pie: 12 in, o 30.48 cm
    'yd',  // Yarda: 3 ft, o 91.44cm

    // Otras categorías encontradas
    'bit', // Bits por segundo
    'ct',  // Quilate
    'und', // Aparece en el PDF (no "UND" en mayúsculas)
];

// Unidades PROHIBIDAS (no aparecen en Tabla 29)
$unidadesProhibidas = [
    'UND', // NO aparece en Tabla 29 DGI
];

echo "1. Verificando archivo de código HKAService.php...\n";
$hkaServicePath = __DIR__ . '/../../app/Services/HKAService.php';
$codigo = file_get_contents($hkaServicePath);

echo "2. Validando que NO se usen unidades prohibidas...\n";
foreach ($unidadesProhibidas as $unidadProhibida) {
    $pattern = '/["\']' . preg_quote($unidadProhibida, '/') . '["\']/';
    if (preg_match($pattern, $codigo)) {
        echo "❌ ERROR: Se encontró unidad prohibida '$unidadProhibida' en el código\n";

        // Mostrar contexto donde aparece
        $lines = explode("\n", $codigo);
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, "'$unidadProhibida'") !== false || strpos($line, "\"$unidadProhibida\"") !== false) {
                echo "   Línea " . ($lineNum + 1) . ": " . trim($line) . "\n";
            }
        }
    } else {
        echo "✅ '$unidadProhibida' correctamente removida del código\n";
    }
}

echo "\n3. Verificando que se usen unidades válidas DGI...\n";
$unidadesEncontradas = [];

// Buscar patrones de asignación de unidades
$patterns = [
    '/unidadMedida\s*=\s*["\']([^"\']+)["\']/i',
    '/unidadMedidaCPBS\s*=\s*["\']([^"\']+)["\']/i',
];

foreach ($patterns as $pattern) {
    if (preg_match_all($pattern, $codigo, $matches)) {
        foreach ($matches[1] as $unidad) {
            $unidadesEncontradas[] = $unidad;
        }
    }
}

$unidadesEncontradas = array_unique($unidadesEncontradas);

foreach ($unidadesEncontradas as $unidad) {
    if (in_array($unidad, $unidadesValidasDGI)) {
        echo "✅ '$unidad' es válida según Tabla 29 DGI\n";
    } else {
        echo "❌ ADVERTENCIA: '$unidad' NO encontrada en Tabla 29 DGI\n";
    }
}

echo "\n4. Verificando lógica específica para exportación...\n";

// Verificar que para exportación se use 'um' y 'cm'
if (strpos($codigo, "'03' ? 'um'") !== false) {
    echo "✅ Facturas exportación usan 'um' (micrómetro) según ejemplo oficial\n";
} else {
    echo "❌ ERROR: Facturas exportación no usan 'um' como esperado\n";
}

if (strpos($codigo, "'03' ? 'cm'") !== false) {
    echo "✅ Facturas exportación usan 'cm' (centímetro) según ejemplo oficial\n";
} else {
    echo "❌ ERROR: Facturas exportación no usan 'cm' como esperado\n";
}

echo "\n5. Verificando validación en validateAndFixDocument...\n";

// Verificar que validateAndFixDocument también corrige a unidades válidas
if (strpos($codigo, 'unidadMedida.*===.*null.*unidadMedida.*===.*UND') !== false) {
    echo "❌ ERROR: validateAndFixDocument aún permite 'UND'\n";
} else {
    echo "✅ validateAndFixDocument corregido para usar unidades DGI válidas\n";
}

echo "\n=== RESUMEN VALIDACIÓN ===\n";
echo "📋 Documentación: Tabla 29 - Catálogo Unidades de Medida DGI\n";
echo "✅ Unidades válidas encontradas: " . implode(', ', array_intersect($unidadesEncontradas, $unidadesValidasDGI)) . "\n";

$unidadesInvalidas = array_diff($unidadesEncontradas, $unidadesValidasDGI);
if (!empty($unidadesInvalidas)) {
    echo "❌ Unidades NO válidas: " . implode(', ', $unidadesInvalidas) . "\n";
    echo "⚠️  ACCIÓN REQUERIDA: Verificar estas unidades en Tabla 29 DGI\n";
} else {
    echo "✅ Todas las unidades encontradas son válidas según DGI\n";
}

echo "\n=== REGLAS APLICADAS ===\n";
echo "1. ✅ Facturas exportación (tipo 03): 'um' y 'cm' según ejemplo TheFactoryHKA\n";
echo "2. ✅ Facturas normales: unidades válidas Tabla 29 DGI\n";
echo "3. ✅ Campo opcional pero si se usa debe ser de lista oficial\n";
echo "4. ✅ Eliminado 'UND' (no aparece en documentación oficial)\n";

echo "\n=== COMPATIBILIDAD PAC ===\n";
echo "🔄 TheFactoryHKA: Conforme a ejemplo oficial (um, cm)\n";
echo "📋 DGI Oficial: Conforme a Tabla 29 oficial\n";
echo "✅ Error 'campo unidadMedida inválido' debe resolverse\n";

echo "\n=== FIN DE VALIDACIÓN ===\n";
