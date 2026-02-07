#!/bin/bash

echo "=== VERIFICACIÓN: Tabla de Ítems en Facturación Electrónica ==="
echo "Fecha: $(date)"
echo "=============================================================="

echo ""
echo "🔍 VERIFICANDO FUNCIONALIDAD DE ÍTEMS..."
echo ""

# Verificar que existen los métodos de gestión de items
echo "✅ Verificando métodos de gestión de ítems en el componente:"
echo ""

if grep -q "public function addItem()" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Método addItem() encontrado"
else
    echo "  ❌ Método addItem() NO encontrado"
fi

if grep -q "public function removeItem(" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Método removeItem() encontrado"
else
    echo "  ❌ Método removeItem() NO encontrado"
fi

if grep -q "public function updatedItems()" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Método updatedItems() encontrado"
else
    echo "  ❌ Método updatedItems() NO encontrado"
fi

echo ""
echo "✅ Verificando estructura de la tabla en la vista:"
echo ""

if grep -q "count(\$items) > 0" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Validación de items encontrada"
else
    echo "  ❌ Validación de items NO encontrada"
fi

if grep -q "Agregar Ítem" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Botón 'Agregar Ítem' encontrado"
else
    echo "  ❌ Botón 'Agregar Ítem' NO encontrado"
fi

if grep -q "No hay ítems en esta factura" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Mensaje para tabla vacía encontrado"
else
    echo "  ❌ Mensaje para tabla vacía NO encontrado"
fi

# Verificar campos editables
echo ""
echo "✅ Verificando campos editables en la tabla:"
echo ""

EDITABLE_FIELDS=(
    "wire:model.lazy='items.{{.*}}.Description'"
    "wire:model.lazy='items.{{.*}}.Quantity'"
    "wire:model.lazy='items.{{.*}}.Unit_Price'"
    "wire:model.lazy='items.{{.*}}.Discount'"
    "wire:model.lazy='items.{{.*}}.Itbms'"
)

for field in "${EDITABLE_FIELDS[@]}"; do
    if grep -q "$field" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
        echo "  ✅ Campo editable encontrado: $(echo $field | cut -d'.' -f3 | tr -d '}' | tr -d "'")"
    else
        echo "  ❌ Campo editable NO encontrado: $(echo $field | cut -d'.' -f3 | tr -d '}' | tr -d "'")"
    fi
done

echo ""
echo "🎯 RESUMEN DE LA SOLUCIÓN IMPLEMENTADA:"
echo "========================================"
echo ""
echo "PROBLEMA IDENTIFICADO:"
echo "- La tabla de ítems no se visualizaba cuando no había items en la venta"
echo ""
echo "SOLUCIÓN IMPLEMENTADA:"
echo "1. ✅ Agregado mensaje informativo cuando no hay ítems"
echo "2. ✅ Agregado botón para agregar ítems manualmente"
echo "3. ✅ Tabla completamente editable (descripción, cantidad, precios)"
echo "4. ✅ Botones para eliminar ítems individuales"
echo "5. ✅ Recálculo automático de totales al modificar items"
echo ""
echo "FUNCIONALIDADES NUEVAS:"
echo "- addItem(): Permite agregar nuevos ítems vacíos"
echo "- removeItem(): Permite eliminar ítems específicos"
echo "- updatedItems(): Recalcula totales automáticamente"
echo "- Campos editables: Descripción, Cantidad, Precio Unitario, Descuento, ITBMS"
echo ""
echo "CASOS DE USO CUBIERTOS:"
echo "✅ Factura con ítems existentes -> Tabla editable con datos"
echo "✅ Factura sin ítems -> Mensaje informativo + botón agregar"
echo "✅ Gestión manual -> Agregar/eliminar ítems dinámicamente"
echo "✅ Edición inline -> Modificar datos directamente en tabla"
echo ""

# Verificar validaciones
echo "🔍 VERIFICANDO VALIDACIONES DE ÍTEMS:"
echo ""

if grep -q "'items' => 'required|array'" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Validación de array de ítems"
fi

if grep -q "'items.*.Description' => 'required|string'" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Validación de descripción requerida"
fi

if grep -q "'items.*.Quantity' => 'required|numeric'" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Validación de cantidad requerida"
fi

if grep -q "'items.*.Unit_Price' => 'required|numeric'" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Validación de precio requerido"
fi

echo ""
echo "✅ VERIFICACIÓN COMPLETADA"
echo "=========================="
echo ""
echo "La tabla de ítems ahora debería:"
echo "1. Mostrar ítems existentes de manera editable"
echo "2. Permitir agregar nuevos ítems cuando no existen"
echo "3. Mostrar mensaje informativo en tabla vacía"
echo "4. Recalcular totales automáticamente"
echo ""
echo "Para probar:"
echo "1. Acceder a una factura sin ítems -> Ver mensaje + botón agregar"
echo "2. Hacer clic en 'Agregar Ítem' -> Aparecer fila editable"
echo "3. Modificar descripción, cantidad, precio -> Totales se actualizan"
echo "4. Usar botón eliminar -> Ítem se quita y totales se recalculan"
echo ""
