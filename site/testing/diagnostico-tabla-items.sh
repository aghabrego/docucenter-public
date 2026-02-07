#!/bin/bash

echo "=== TESTING: Tabla de Ítems - Diagnóstico Detallado ==="
echo "Fecha: $(date)"
echo "========================================================"

echo ""
echo "🔍 1. VERIFICANDO MÉTODOS REQUERIDOS EN COMPONENTE LIVEWIRE"
echo ""

REQUIRED_METHODS=(
    "getMoneyFormat"
    "getCalculatedItemTotal"
    "getCalculatedFinalPrice"
    "getCalculatedUnitPrice"
    "getCalculatedUnitDiscount"
    "addItem"
    "removeItem"
    "updatedItems"
)

for method in "${REQUIRED_METHODS[@]}"; do
    if grep -q "function $method" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
        echo "  ✅ Método $method() encontrado"
    else
        echo "  ❌ Método $method() FALTANTE"
    fi
done

echo ""
echo "🔍 2. VERIFICANDO INICIALIZACIÓN DE ITEMS EN MOUNT"
echo ""

if grep -q "if (\$this->sale->salesDetails && \$this->sale->salesDetails->count() > 0)" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Verificación de salesDetails implementada"
else
    echo "  ❌ Verificación de salesDetails FALTANTE"
fi

if grep -q "\$this->items = \[\];" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Inicialización de items vacíos implementada"
else
    echo "  ❌ Inicialización de items vacíos FALTANTE"
fi

echo ""
echo "🔍 3. VERIFICANDO ESTRUCTURA DE VISTA"
echo ""

if grep -q "@if(count(\$items) > 0)" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Validación de items en vista encontrada"
else
    echo "  ❌ Validación de items en vista FALTANTE"
fi

# Verificar que los campos editables estén correctamente configurados
if grep -q "wire:model.lazy='items.{{.*}}.Description'" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Campo Description editable configurado"
else
    echo "  ❌ Campo Description editable NO configurado"
fi

echo ""
echo "🔍 4. VERIFICANDO POSIBLES ERRORES DE SINTAXIS EN VISTA"
echo ""

# Buscar posibles errores en las llamadas a métodos
if grep -q "\$this->getMoneyFormat(" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Llamadas a getMoneyFormat encontradas"
    MONEY_FORMAT_COUNT=$(grep -c "\$this->getMoneyFormat(" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php)
    echo "  📊 Total de llamadas: $MONEY_FORMAT_COUNT"
else
    echo "  ❌ NO se encontraron llamadas a getMoneyFormat"
fi

if grep -q "\$this->getCalculatedItemTotal(" /home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php; then
    echo "  ✅ Llamadas a getCalculatedItemTotal encontradas"
else
    echo "  ❌ NO se encontraron llamadas a getCalculatedItemTotal"
fi

echo ""
echo "🔍 5. VERIFICANDO INICIALIZACIÓN DE VARIABLE ITEMS"
echo ""

if grep -q "public \$items = \[\];" /home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php; then
    echo "  ✅ Variable \$items inicializada como array vacío"
else
    echo "  ❌ Variable \$items NO inicializada correctamente"
fi

echo ""
echo "🔍 6. GENERANDO SCRIPT DE PRUEBA ESPECÍFICO"
echo ""

cat > /tmp/test_items_debug.php << 'EOF'
<?php
// Script para verificar que los items se están inicializando correctamente

// Simular la lógica del mount
$items = [];

// Caso 1: salesDetails existe y tiene datos
echo "Caso 1: Con salesDetails\n";
$mockSalesDetails = collect([
    (object)['Description' => 'Item 1', 'Quantity' => 1, 'Unit_Price' => 100],
    (object)['Description' => 'Item 2', 'Quantity' => 2, 'Unit_Price' => 50]
]);

if ($mockSalesDetails && $mockSalesDetails->count() > 0) {
    $items = $mockSalesDetails->map(fn($item) => [
        'Description' => $item->Description,
        'Quantity' => $item->Quantity,
        'Unit_Price' => $item->Unit_Price,
    ])->toArray();
    echo "Items cargados: " . count($items) . "\n";
    print_r($items);
} else {
    $items = [];
    echo "Items inicializado como array vacío\n";
}

// Caso 2: salesDetails vacío
echo "\nCaso 2: Sin salesDetails\n";
$mockSalesDetails = collect([]);

if ($mockSalesDetails && $mockSalesDetails->count() > 0) {
    $items = $mockSalesDetails->map(fn($item) => ['Description' => $item->Description])->toArray();
} else {
    $items = [];
    echo "Items inicializado como array vacío (correcto)\n";
}

echo "Count de items: " . count($items) . "\n";
EOF

php /tmp/test_items_debug.php

echo ""
echo "🎯 DIAGNÓSTICO PRINCIPAL"
echo "======================"
echo ""
echo "POSIBLES CAUSAS DEL PROBLEMA:"
echo ""
echo "1. ❓ El método getMoneyFormat podría tener errores internos"
echo "2. ❓ Los items no se están inicializando correctamente en mount"
echo "3. ❓ Hay un error de sintaxis en la vista que impide renderizar"
echo "4. ❓ Los métodos de cálculo tienen dependencias faltantes"
echo ""

echo "PRÓXIMOS PASOS RECOMENDADOS:"
echo "1. Verificar que no hay errores de PHP en logs"
echo "2. Simplificar temporalmente la vista para identificar el error"
echo "3. Agregar debugging al método mount"
echo ""

echo "✅ DIAGNÓSTICO COMPLETADO"
