<?php

// Texto de prueba
$texto = 'EXW PANAMA-PANAMA "INCOTERMS CCI 2020"-03-1901-564-2401-PHT NUDE T MNRL SPF50+ TRES CLAIRE F40ML';

// Ancho de la columna Description
$width = 60;

// Función actual (factor 1.5)
$truncateToWidth = function($text, $width) {
    $maxChars = (int)($width * 1.5);
    return strlen($text) > $maxChars ? substr($text, 0, $maxChars) : $text;
};

echo "=== PRUEBA DE TRUNCAMIENTO ===\n";
echo "Texto original: " . $texto . "\n";
echo "Longitud original: " . strlen($texto) . " caracteres\n";
echo "Ancho columna: " . $width . " unidades\n";
echo "Factor actual: 1.5\n";
echo "Límite calculado: " . (int)($width * 1.5) . " caracteres\n";
echo "\n";

$resultado = $truncateToWidth($texto, $width);
echo "Texto truncado: " . $resultado . "\n";
echo "Longitud truncada: " . strlen($resultado) . " caracteres\n";
echo "\n";

// Probar con diferentes factores
echo "=== PRUEBAS CON DIFERENTES FACTORES ===\n";
$factores = [1.0, 0.8, 0.6, 0.5, 0.4];

foreach ($factores as $factor) {
    $limite = (int)($width * $factor);
    $textoTruncado = strlen($texto) > $limite ? substr($texto, 0, $limite) : $texto;
    echo "Factor {$factor}: límite {$limite} → " . substr($textoTruncado, 0, 50) . "...\n";
}

?>
