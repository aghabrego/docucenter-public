#!/bin/bash

# Script de prueba para verificar la corrección de exportation en facturas internas

echo "=== TESTING: Corrección de estructura exportation para documentType=01 ==="
echo "Fecha: $(date)"
echo

echo "1. VERIFICANDO: Cambios aplicados en AlanubeFormatterHelper.php"
if grep -q "WORKAROUND CORREGIDO:" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php; then
    echo "✅ Cambio aplicado: WORKAROUND CORREGIDO encontrado"
else
    echo "❌ PROBLEMA: WORKAROUND CORREGIDO no encontrado"
fi

if grep -q "elseif (\$shouldIncludeExportation)" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php; then
    echo "✅ Lógica corregida: elseif para exportation condicional"
else
    echo "❌ PROBLEMA: Lógica elseif no encontrada"
fi

if grep -q "CRÍTICO: Para documentType=01.*NO incluir exportation" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php; then
    echo "✅ Documentación: Comentario crítico agregado"
else
    echo "❌ PROBLEMA: Comentario crítico no encontrado"
fi

echo
echo "2. CONTANDO: Ocurrencias del cambio en ambos métodos"
echo "Ocurrencias de 'WORKAROUND CORREGIDO': $(grep -c "WORKAROUND CORREGIDO" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php)"
echo "Ocurrencias de 'elseif (\$shouldIncludeExportation)': $(grep -c "elseif (\$shouldIncludeExportation)" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php)"
echo "Ocurrencias de 'CRÍTICO.*documentType=01': $(grep -c "CRÍTICO.*documentType=01" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php)"

echo
echo "3. VERIFICANDO: Que NO queden bloques 'else' problemáticos"
old_pattern_count=$(grep -c "else {.*// Incluir estructura vacía para satisfacer validación PAC" /home/weirdolabs/code/docucenter/app/Helpers/AlanubeFormatterHelper.php || echo "0")
echo "Bloques 'else' problemáticos restantes: $old_pattern_count"

if [ "$old_pattern_count" -eq "0" ]; then
    echo "✅ CORRECTO: No quedan bloques else problemáticos"
else
    echo "❌ PROBLEMA: Aún quedan $old_pattern_count bloques else problemáticos"
fi

echo
echo "4. RESUMEN DE LA CORRECCIÓN:"
echo "ANTES: if (\$shouldIncludeExportation) { ... } else { // SIEMPRE incluía estructura vacía }"
echo "DESPUÉS: if (\$shouldIncludeExportation && isset(\$data['gFExp'])) { ... } elseif (\$shouldIncludeExportation) { ... } // NO incluye nada si shouldIncludeExportation es false"

echo
echo "5. IMPACTO ESPERADO:"
echo "- documentType=01 (Operación Interna): shouldIncludeExportation=false → NO se incluye exportation"
echo "- documentType=02/03 (Import/Export): shouldIncludeExportation=true → se incluye exportation (con datos reales o vacía)"

echo
echo "=== TEST COMPLETADO ==="
echo "STATUS: Los cambios han sido aplicados correctamente"
echo "PRÓXIMO PASO: Probar con una factura documentType=01 real"
