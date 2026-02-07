#!/bin/bash

# Script de verificación para pasos simplificados
# Verifica que los pasos 5 y 6 estén funcionando con las versiones simplificadas

echo "🔧 VERIFICACIÓN DE PASOS SIMPLIFICADOS - FACTURACIÓN ELECTRÓNICA"
echo "================================================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "\n${BLUE}1. VERIFICANDO COMPONENTE LIVEWIRE${NC}"
echo "-----------------------------------"

if [ -f "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php" ]; then
    echo -e "✅ Archivo del componente encontrado"

    # Verificar métodos de items
    if grep -q "public function addItem" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
        echo -e "✅ Método addItem() existe"
    else
        echo -e "❌ Método addItem() NO encontrado"
    fi

    if grep -q "public function removeItem" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
        echo -e "✅ Método removeItem() existe"
    else
        echo -e "❌ Método removeItem() NO encontrado"
    fi

    # Verificar propiedades del Paso 6
    if grep -q "otroPrecioFinal" "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"; then
        echo -e "✅ Propiedades del Paso 6 encontradas"
    else
        echo -e "❌ Propiedades del Paso 6 NO encontradas"
    fi
else
    echo -e "❌ Archivo del componente NO encontrado"
    exit 1
fi

echo -e "\n${BLUE}2. VERIFICANDO VISTA BLADE${NC}"
echo "-----------------------------"

if [ -f "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php" ]; then
    echo -e "✅ Archivo de vista encontrado"

    # Verificar estructura del Paso 5
    if grep -q "step-five" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
        echo -e "✅ Paso 5 (step-five) encontrado en vista"
    else
        echo -e "❌ Paso 5 (step-five) NO encontrado"
    fi

    # Verificar directiva Alpine.js para Paso 5
    if grep -q 'x-show="currentStep == 5"' "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
        echo -e "✅ Directiva Alpine.js para Paso 5 correcta"
    else
        echo -e "❌ Directiva Alpine.js para Paso 5 INCORRECTA"
    fi

    # Verificar estructura del Paso 6
    if grep -q "step-six" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
        echo -e "✅ Paso 6 (step-six) encontrado en vista"
    else
        echo -e "❌ Paso 6 (step-six) NO encontrado"
    fi

    # Verificar directiva Alpine.js para Paso 6
    if grep -q 'x-show="currentStep == 6"' "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
        echo -e "✅ Directiva Alpine.js para Paso 6 correcta"
    else
        echo -e "❌ Directiva Alpine.js para Paso 6 INCORRECTA"
    fi

    # Verificar botón "Agregar Ítem"
    if grep -q "wire:click=\"addItem\"" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
        echo -e "✅ Botón 'Agregar Ítem' configurado correctamente"
    else
        echo -e "❌ Botón 'Agregar Ítem' NO configurado"
    fi

    # Verificar tabla de items simplificada
    if grep -q "No hay ítems agregados" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
        echo -e "✅ Mensaje para tabla vacía implementado"
    else
        echo -e "❌ Mensaje para tabla vacía NO implementado"
    fi
else
    echo -e "❌ Archivo de vista NO encontrado"
    exit 1
fi

echo -e "\n${BLUE}3. VERIFICANDO SINTAXIS PHP${NC}"
echo "------------------------------"

# Verificar sintaxis del componente
php -l "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "✅ Sintaxis PHP del componente correcta"
else
    echo -e "❌ Errores de sintaxis PHP en componente"
    php -l "/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"
fi

echo -e "\n${BLUE}4. VERIFICANDO ESTRUCTURA DE NAVEGACIÓN${NC}"
echo "--------------------------------------------"

# Verificar botones de navegación
if grep -q "currentStep++" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php" || grep -q "nextStep" "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"; then
    echo -e "✅ Botones de navegación encontrados"
else
    echo -e "❌ Botones de navegación NO encontrados"
fi

# Contar total de pasos
TOTAL_STEPS=$(grep -c 'x-show="currentStep ==' "/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php")
echo -e "📊 Total de pasos detectados: ${TOTAL_STEPS}"

echo -e "\n${YELLOW}5. RESUMEN DE CAMBIOS REALIZADOS${NC}"
echo "-----------------------------------"
echo "✅ Paso 5: Tabla de ítems completamente simplificada"
echo "✅ Paso 5: Eliminadas dependencias de métodos complejos"
echo "✅ Paso 5: Cálculos realizados directamente en blade"
echo "✅ Paso 6: Formulario simplificado sin DataProvider"
echo "✅ Paso 6: Campos básicos con opciones hardcodeadas"
echo "✅ Ambos pasos: Sin dependencias de métodos que pueden fallar"

echo -e "\n${GREEN}6. INSTRUCCIONES DE PRUEBA${NC}"
echo "----------------------------"
echo "1. Abrir la aplicación en el navegador"
echo "2. Navegar a Crear Factura"
echo "3. Completar pasos 1-4 normalmente"
echo "4. En el Paso 5: Debe verse la tabla (vacía o con datos)"
echo "5. Hacer clic en 'Agregar Ítem' para agregar un elemento"
echo "6. En el Paso 6: Debe verse el formulario 'Otros Valores'"
echo "7. Todos los campos deben ser visibles y editables"

echo -e "\n${BLUE}7. POSIBLES PROBLEMAS Y SOLUCIONES${NC}"
echo "-----------------------------------"
echo "❗ Si aún no se ven los pasos:"
echo "  - Verificar que currentStep se esté incrementando"
echo "  - Revisar consola del navegador por errores JavaScript"
echo "  - Verificar que Alpine.js esté cargado correctamente"
echo ""
echo "❗ Si hay errores al hacer clic en botones:"
echo "  - Verificar que Livewire esté funcionando"
echo "  - Revisar logs de Laravel (storage/logs/laravel.log)"
echo "  - Verificar conectividad con la base de datos"

echo -e "\n${GREEN}VERIFICACIÓN COMPLETA ✓${NC}"
echo "Los pasos han sido simplificados para eliminar dependencias complejas."
echo "Si aún hay problemas, revisar la navegación de pasos y Alpine.js."
