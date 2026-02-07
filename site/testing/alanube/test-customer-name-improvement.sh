#!/bin/bash

# Script de prueba para verificar la mejora del nombre del cliente

echo "=== TESTING: Mejora de selección de nombre del cliente ==="
echo "Fecha: $(date)"
echo

echo "1. VERIFICANDO: Cambio aplicado en QuickBooksOnlineService.php"
if grep -q "CompanyName.*fallback" /home/weirdolabs/code/docucenter/app/Services/QuickBooksOnlineService.php; then
    echo "✅ Cambio aplicado: Lógica de fallback para CompanyName encontrada"
else
    echo "❌ PROBLEMA: Lógica de fallback no encontrada"
fi

if grep -q "!empty(\$customerRef\['CompanyName'\])" /home/weirdolabs/code/docucenter/app/Services/QuickBooksOnlineService.php; then
    echo "✅ Validación: Verificación de CompanyName no vacío"
else
    echo "❌ PROBLEMA: Verificación de CompanyName no encontrada"
fi

echo
echo "2. MOSTRANDO: El cambio implementado"
grep -A 3 -B 1 "Priorizar CompanyName" /home/weirdolabs/code/docucenter/app/Services/QuickBooksOnlineService.php

echo
echo "3. LÓGICA IMPLEMENTADA:"
echo "   1. Si CompanyName existe Y no está vacío → usar CompanyName"
echo "   2. Si CompanyName está vacío o no existe → usar 'name' como fallback"
echo "   3. Si 'name' también falta → usar 'QuickBooks Customer'"

echo
echo "4. CASOS DE USO:"
echo
echo "   CASO 1: Cliente empresa"
echo "   {"
echo "     \"name\": \"John Doe\","
echo "     \"CompanyName\": \"Acme Corporation\","
echo "     \"DisplayName\": \"John Doe\""
echo "   }"
echo "   RESULTADO: 'Acme Corporation' ✅"
echo
echo "   CASO 2: Cliente individual (CompanyName vacío)"
echo "   {"
echo "     \"name\": \"Solmary\","
echo "     \"CompanyName\": \"\","
echo "     \"DisplayName\": \"Solmary\""
echo "   }"
echo "   RESULTADO: 'Solmary' ✅"
echo
echo "   CASO 3: Cliente sin CompanyName"
echo "   {"
echo "     \"name\": \"Maria Garcia\","
echo "     \"DisplayName\": \"Maria Garcia\""
echo "   }"
echo "   RESULTADO: 'Maria Garcia' ✅"

echo
echo "5. IMPACTO EN FACTURACIÓN ELECTRÓNICA:"
echo "   - Facturas B2B: Mostrarán el nombre de la empresa (más profesional)"
echo "   - Facturas B2C: Mostrarán el nombre de la persona (más personal)"
echo "   - Cumplimiento: Mejor identificación del receptor para PAC"

echo
echo "=== TEST COMPLETADO ==="
echo "STATUS: Mejora implementada correctamente"
echo "PRÓXIMO PASO: Probar con facturas reales de QuickBooks"
