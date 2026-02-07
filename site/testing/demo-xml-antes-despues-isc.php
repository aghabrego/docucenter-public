<?php

/**
 * Demostración de diferencias XML antes y después de la corrección ISC
 * Para mostrar el cambio específico que resuelve el error PAC
 */

echo "=== DEMOSTRACIÓN XML: ANTES vs DESPUÉS DE LA CORRECCIÓN ===\n\n";

echo "❌ ANTES (Causaba error PAC):\n";
echo "```xml\n";
echo "<ser:item>\n";
echo "  <ser:descripcion>Muebles de oficina</ser:descripcion>\n";
echo "  <ser:codigo>MUE-001</ser:codigo>\n";
echo "  <ser:precioUnitario>100.000000</ser:precioUnitario>\n";
echo "  <ser:tasaITBMS>01</ser:tasaITBMS>\n";
echo "  <ser:valorITBMS>7.000000</ser:valorITBMS>\n";
echo "  <ser:tasaISC></ser:tasaISC>          <!-- ❌ PROBLEMA: Campo vacío innecesario -->\n";
echo "  <ser:valorISC>0.000000</ser:valorISC> <!-- ❌ PROBLEMA: Campo cero innecesario -->\n";
echo "</ser:item>\n";
echo "```\n\n";

echo "✅ DESPUÉS (PAC acepta):\n";
echo "```xml\n";
echo "<ser:item>\n";
echo "  <ser:descripcion>Muebles de oficina</ser:descripcion>\n";
echo "  <ser:codigo>MUE-001</ser:codigo>\n";
echo "  <ser:precioUnitario>100.000000</ser:precioUnitario>\n";
echo "  <ser:tasaITBMS>01</ser:tasaITBMS>\n";
echo "  <ser:valorITBMS>7.000000</ser:valorITBMS>\n";
echo "  <!-- ✅ SOLUCIÓN: No incluir campos ISC cuando no aplican -->\n";
echo "</ser:item>\n";
echo "```\n\n";

echo "📋 PARA PRODUCTOS CON ISC (ejemplo: tabaco):\n";
echo "```xml\n";
echo "<ser:item>\n";
echo "  <ser:descripcion>Cigarrillos Marlboro</ser:descripcion>\n";
echo "  <ser:codigo>TAB-001</ser:codigo>\n";
echo "  <ser:precioUnitario>5.000000</ser:precioUnitario>\n";
echo "  <ser:tasaITBMS>01</ser:tasaITBMS>\n";
echo "  <ser:valorITBMS>0.350000</ser:valorITBMS>\n";
echo "  <ser:tasaISC>15</ser:tasaISC>        <!-- ✅ SÍ incluir cuando aplica -->\n";
echo "  <ser:valorISC>0.750000</ser:valorISC> <!-- ✅ SÍ incluir cuando aplica -->\n";
echo "</ser:item>\n";
echo "```\n\n";

echo "🔍 DIFERENCIA CLAVE:\n";
echo "- Campos ISC son CONDICIONALES según documentación oficial PAC\n";
echo "- Solo incluir cuando el producto realmente tiene ISC aplicable\n";
echo "- Evita error: 'El campo valorISC es inválido. Informado valor del ISC en una operacion no valorada'\n\n";

echo "📊 IMPACTO EN DIFERENTES TIPOS DE PRODUCTOS:\n\n";

$products = [
    ['Muebles de oficina', 'NO', 'Producto normal sin impuestos especiales'],
    ['Equipos de computación', 'NO', 'Productos de tecnología sin ISC'],
    ['Servicios profesionales', 'NO', 'Servicios no gravados con ISC'],
    ['Cigarrillos', 'SÍ', 'Productos de tabaco gravados con ISC'],
    ['Bebidas alcohólicas', 'SÍ', 'Licores gravados con ISC'],
    ['Vehículos de lujo', 'SÍ', 'Según legislación vigente']
];

foreach ($products as $product) {
    $icon = $product[1] === 'SÍ' ? '🚬' : '📦';
    $status = $product[1] === 'SÍ' ? 'INCLUIR campos ISC' : 'NO incluir campos ISC';
    echo "$icon {$product[0]}: $status\n";
    echo "   Razón: {$product[2]}\n\n";
}

echo "✅ RESULTADO: XML más limpio, conforme a especificaciones PAC y sin errores de validación\n";
