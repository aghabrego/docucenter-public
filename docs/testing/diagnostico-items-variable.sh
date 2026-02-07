#!/bin/bash

# Script de diagnóstico específico para la variable $items
# Investiga por qué no se visualizan los items sin hacer cambios destructivos

echo "🔍 DIAGNÓSTICO ESPECÍFICO - VARIABLE \$items"
echo "========================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "\n${BLUE}1. INVESTIGANDO CONDICIÓN EN PASO 5${NC}"
echo "------------------------------------"

# Verificar la condición exacta
if grep -n "@if(!empty(\$items) && count(\$items) > 0)" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
    echo -e "✅ Condición encontrada en la línea mostrada arriba"
else
    echo -e "❌ Condición NO encontrada - verificando alternativas..."
    grep -n "@if.*items.*>" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"
fi

echo -e "\n${BLUE}2. VERIFICANDO INICIALIZACIÓN DE \$items${NC}"
echo "---------------------------------------------"

if grep -n "public \$items" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
    echo -e "✅ Propiedad \$items declarada en componente"
else
    echo -e "❌ Propiedad \$items NO declarada"
fi

# Verificar si hay algún método mount() que inicialice items
if grep -n "public function mount" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
    echo -e "✅ Método mount() encontrado"
    echo -e "🔍 Verificando si mount() inicializa \$items..."
    if grep -A 20 "public function mount" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php" | grep -q "items"; then
        echo -e "✅ \$items se menciona en mount()"
    else
        echo -e "❌ \$items NO se inicializa en mount()"
    fi
else
    echo -e "❌ Método mount() NO encontrado"
fi

echo -e "\n${BLUE}3. BUSCANDO INICIALIZACIÓN DE ITEMS DESDE SALES DETAILS${NC}"
echo "--------------------------------------------------------"

# Verificar si hay código que cargue items desde salesDetails
if grep -n "salesDetails" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
    echo -e "✅ salesDetails encontrado en componente"
    echo -e "🔍 Líneas donde se menciona salesDetails:"
    grep -n "salesDetails" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php" | head -5
else
    echo -e "❌ salesDetails NO encontrado"
fi

# Verificar transformación de salesDetails a items
if grep -n "foreach.*salesDetails" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
    echo -e "✅ Transformación salesDetails -> items encontrada"
else
    echo -e "❌ Transformación salesDetails -> items NO encontrada"
fi

echo -e "\n${BLUE}4. VERIFICANDO MÉTODO addItem()${NC}"
echo "-----------------------------------"

if grep -A 10 "public function addItem" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
    echo -e "✅ Método addItem() implementado"
else
    echo -e "❌ Método addItem() NO implementado"
fi

echo -e "\n${BLUE}5. ANALIZANDO LA LÓGICA DE VISUALIZACIÓN${NC}"
echo "-------------------------------------------"

echo -e "📋 LÓGICA ACTUAL EN BLADE:"
echo "  - Condición: @if(!empty(\$items) && count(\$items) > 0)"
echo "  - Si TRUE: Muestra tabla completa con items"
echo "  - Si FALSE: Muestra mensaje 'No hay ítems en esta factura'"

echo -e "\n🤔 POSIBLES CAUSAS DE NO VISUALIZACIÓN:"
echo "  1. \$items está NULL (no inicializado)"
echo "  2. \$items está vacío [] (array vacío)"
echo "  3. \$items no se está cargando desde salesDetails"
echo "  4. salesDetails está vacío en la venta asociada"
echo "  5. Hay un error PHP que impide la evaluación"

echo -e "\n${YELLOW}6. PRUEBA DIAGNÓSTICA SIN CAMBIOS${NC}"
echo "-----------------------------------"

echo -e "📝 RECOMENDACIÓN DE PRUEBA:"
echo "1. Abrir la aplicación en el navegador"
echo "2. Ir a Crear Factura y avanzar hasta el Paso 5"
echo "3. Abrir DevTools (F12) y ir a Console"
echo "4. Verificar si hay errores JavaScript"
echo "5. En Network, verificar si hay errores de Livewire"

echo -e "\n${BLUE}7. VERIFICANDO POSIBLE SOLUCIÓN TEMPORAL${NC}"
echo "-----------------------------------------------"

echo -e "💡 SOLUCIÓN TEMPORAL SUGERIDA (sin dañar):"
echo "Cambiar la condición de:"
echo "  @if(!empty(\$items) && count(\$items) > 0)"
echo "A:"
echo "  @if(isset(\$items) && is_array(\$items) && count(\$items) > 0)"
echo ""
echo "O más simple:"
echo "  @if(count(\$items ?? []) > 0)"

echo -e "\n${BLUE}8. VERIFICANDO SI HAY ERRORES DE SINTAXIS${NC}"
echo "--------------------------------------------"

# Verificar errores de sintaxis que puedan interferir
php -l "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "✅ Sintaxis PHP correcta en componente"
else
    echo -e "❌ ERRORES DE SINTAXIS DETECTADOS:"
    php -l "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"
fi

echo -e "\n${GREEN}9. CONCLUSIÓN DIAGNÓSTICA${NC}"
echo "----------------------------"

echo -e "🎯 PRÓXIMOS PASOS RECOMENDADOS:"
echo "1. Verificar que \$items se inicialice correctamente"
echo "2. Si \$items está vacío, verificar carga desde salesDetails"
echo "3. Cambiar condición Blade por una más robusta"
echo "4. Agregar debugging temporal para ver el valor de \$items"

echo -e "\n${YELLOW}❗ IMPORTANTE: Este diagnóstico NO hace cambios${NC}"
echo "Solo analiza el estado actual para identificar el problema exacto."

echo -e "\n${GREEN}DIAGNÓSTICO COMPLETO ✓${NC}"
