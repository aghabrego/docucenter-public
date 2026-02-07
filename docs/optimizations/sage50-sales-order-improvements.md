# Optimización y Mejoras: Creación de Órdenes de Venta Sage50

**Fecha**: 10 de diciembre de 2025  
**Módulo**: `admin/sage50/create_sales_order`  
**Componente**: `App\Http\Livewire\Admin\Sage50\CreateSaleOrder`

## Problemas Identificados

### 1. Rendimiento Lento con Órdenes de 50+ Items

#### Síntoma
El sistema se vuelve extremadamente lento al agregar productos cuando la orden tiene más de 50 items.

#### Causa Raíz
**Archivo**: `app/Http/Livewire/Admin/Sage50/CreateSaleOrder.php`

**Método `addProduct()` (línea 600)**:
```php
public function addProduct(array $detail)
{
    // ... lógica de agregar item ...
    
    $this->items[] = [
        'codigo' => array_get($detail, 'ProductID', 0),
        'cantidad' => $this->cantidad,
        // ... más campos ...
    ];

    // PROBLEMA: Recalcula TODOS los items cada vez
    $this->calcularTotales();  // Línea 661
    
    $this->dispatchBrowserEvent('close-modal-add-product');
}
```

**Método `calcularTotales()` (línea 679)**:
```php
public function calcularTotales()
{
    // Reiniciar los totales
    $this->totalSubtotal = 0;
    $this->totalITBMS = 0;
    $this->totalISC = 0;
    $this->totalPrecioFinal = 0;
    $this->otroPrecioFinal = 0;
    $this->totalDescuento = 0;
    
    // PROBLEMA: Loop sobre TODOS los items
    foreach ($this->items as $item) {
        $this->totalPrecioFinal = $this->totalPrecioFinal + $item['precioFinal'];
        $this->totalDescuento = $this->totalDescuento + $item['descuento'];
        $this->totalSubtotal = $this->totalSubtotal + $item['subtotal'];
        $this->totalITBMS = $this->totalITBMS + $item['ITBMS'];
        $this->totalISC = $this->totalISC + $item['ISC'];
    }
    $this->otroPrecioFinal = $this->totalPrecioFinal;
}
```

#### Análisis de Complejidad
- **Por cada item agregado**: O(n) donde n = cantidad de items
- **50 items**: 1 + 2 + 3 + ... + 50 = **1,275 iteraciones totales**
- **100 items**: 1 + 2 + 3 + ... + 100 = **5,050 iteraciones totales**
- **Complejidad total**: O(n²) - Cuadrática

#### Comportamiento del Usuario
```
Usuario agrega Item 1 → Calcula 1 item
Usuario agrega Item 2 → Calcula 2 items (recalcula Item 1 nuevamente)
Usuario agrega Item 3 → Calcula 3 items (recalcula Items 1 y 2 nuevamente)
...
Usuario agrega Item 50 → Calcula 50 items (recalcula Items 1-49 OTRA VEZ)
```

#### Llamadas Adicionales en Livewire
- Cada `addProduct()` dispara re-renderizado completo del componente
- El método `getProductsExp()` ejecuta query con paginación en cada actualización
- La vista re-renderiza la tabla de items (líneas 148-226 de `create_sale_order.blade.php`)

---

### 2. Sistema de Borradores Ausente

#### Problema
No existe forma de guardar órdenes como "borradores" para continuar trabajando después.

#### Estado Actual
**Método `createOrder()` (línea 891)**:
```php
$header = SalesOrderHeaderImp::query()->create([
    'ID_compania' => $company->ID_compania ?? null,
    'SalesOrderNumber' => $this->strPad($this->sales_order_number, 10),
    'CustomerID' => $this->customer_id,
    // ... más campos ...
    'Enviado' => 0,  // Siempre 0 al crear
    'Error' => 0,    // Siempre 0 al crear
]);
```

#### Campos Disponibles en BD
**Archivo**: `app/Models/stubs/SalesOrder_Header_Imp.sql.stub`
```sql
`Enviado` tinyint(1) NOT NULL DEFAULT 0,
`Error` tinyint(1) NOT NULL DEFAULT 0,
`ErrorPT` varchar(1024) DEFAULT NULL,
```

#### Comportamiento Esperado
Los usuarios necesitan:
1. Guardar orden parcialmente completa
2. Cerrar sesión
3. Volver después y continuar editando
4. Solo cuando esté 100% completa → marcar como "lista para envío a Sage50"

---

## Soluciones Propuestas

### Solución 1: Optimización de Rendimiento

#### Opción 1A: Cálculo Incremental (Recomendado)

**Ventaja**: Mejor rendimiento sin cambiar UX  
**Complejidad**: O(1) por item agregado

```php
// En CreateSaleOrder.php

public function addProduct(array $detail)
{
    // ... código existente para calcular item individual ...
    
    $itemData = [
        'codigo' => array_get($detail, 'ProductID', 0),
        'cantidad' => $this->cantidad,
        'precioUnitario' => $this->precioUnitario,
        'ITBMS' => $this->ITBMS,
        'precioFinal' => $this->precioFinal,
        'informacionInteres' => $this->informacionInteres,
        'descripcion' => array_get($detail, 'SalesDescription', 0),
        'tasa_descuento' => 0,
        'descuento' => $this->descuento,
        'tipoImpuesto' => $this->tipoImpuesto,
        'tasaITBMS' => $this->tasaITBMS,
        'tasaISC' => $this->tasaISC,
        'ISC' => $this->ISC,
        'subtotal' => $this->subtotal,
        'unidad' => array_get($detail, 'UnitMeasure'),
    ];
    
    $this->items[] = $itemData;

    // MEJORA: Cálculo incremental en lugar de recalcular todo
    $this->calcularTotalesIncremental($itemData);

    $this->dispatchBrowserEvent('close-modal-add-product');
}

/**
 * Calcula totales de forma incremental (suma solo el nuevo item)
 * Complejidad: O(1) en lugar de O(n)
 */
public function calcularTotalesIncremental(array $item)
{
    $this->totalPrecioFinal += $item['precioFinal'];
    $this->totalDescuento += $item['descuento'];
    $this->totalSubtotal += $item['subtotal'];
    $this->totalITBMS += $item['ITBMS'];
    $this->totalISC += $item['ISC'];
    $this->otroPrecioFinal = $this->totalPrecioFinal;
}

/**
 * Mantener método original para cuando se necesite recalcular todo
 * (ej: al editar items, eliminar items, o cargar orden existente)
 */
public function calcularTotales()
{
    // Código existente sin cambios
    $this->totalSubtotal = 0;
    $this->totalITBMS = 0;
    $this->totalISC = 0;
    $this->totalPrecioFinal = 0;
    $this->otroPrecioFinal = 0;
    $this->totalDescuento = 0;
    
    foreach ($this->items as $item) {
        $this->totalPrecioFinal = $this->totalPrecioFinal + $item['precioFinal'];
        $this->totalDescuento = $this->totalDescuento + $item['descuento'];
        $this->totalSubtotal = $this->totalSubtotal + $item['subtotal'];
        $this->totalITBMS = $this->totalITBMS + $item['ITBMS'];
        $this->totalISC = $this->totalISC + $item['ISC'];
    }
    $this->otroPrecioFinal = $this->totalPrecioFinal;
}

/**
 * Actualizar removeItem para usar cálculo incremental negativo
 */
public function removeItem($index)
{
    if (isset($this->items[$index])) {
        $item = $this->items[$index];
        
        // Restar valores del item eliminado
        $this->totalPrecioFinal -= $item['precioFinal'];
        $this->totalDescuento -= $item['descuento'];
        $this->totalSubtotal -= $item['subtotal'];
        $this->totalITBMS -= $item['ITBMS'];
        $this->totalISC -= $item['ISC'];
        $this->otroPrecioFinal = $this->totalPrecioFinal;
        
        unset($this->items[$index]);
    }
}
```

**Impacto de Performance**:
- 50 items: **1,275 iteraciones** → **50 iteraciones** (96% mejora)
- 100 items: **5,050 iteraciones** → **100 iteraciones** (98% mejora)

#### Opción 1B: Cálculo Diferido con Toggle

**Ventaja**: Usuario controla cuándo recalcular  
**Uso**: Para órdenes muy grandes (100+ items)

```php
// Agregar propiedad en CreateSaleOrder.php
public $autoCalculate = true;

public function addProduct(array $detail)
{
    // ... código existente ...
    
    $this->items[] = $itemData;

    // Solo calcular si está habilitado
    if ($this->autoCalculate) {
        $this->calcularTotalesIncremental($itemData);
    }

    $this->dispatchBrowserEvent('close-modal-add-product');
}

public function toggleAutoCalculate()
{
    $this->autoCalculate = !$this->autoCalculate;
    
    if ($this->autoCalculate) {
        // Recalcular todo al reactivar
        $this->calcularTotales();
    }
}

public function recalcularManual()
{
    $this->calcularTotales();
    $this->dispatchBrowserEvent('show-message', [
        'type' => 'success', 
        'message' => __('Totales recalculados')
    ]);
}
```

**Blade** (`create_sale_order.blade.php`):
```blade
<div class="form-check mb-3">
    <input type="checkbox" class="form-check-input" id="autoCalc" 
           wire:model="autoCalculate" wire:change="toggleAutoCalculate">
    <label class="form-check-label" for="autoCalc">
        {{ __('Auto-calcular totales (desactivar para órdenes grandes)') }}
    </label>
</div>

@if (!$autoCalculate)
    <button type="button" wire:click="recalcularManual" class="btn btn-sm btn-info">
        <i class="fa fa-calculator"></i> {{ __('Recalcular Totales') }}
    </button>
@endif
```

#### Opción 1C: Virtualización de Lista (Avanzado)

Para órdenes extremadamente grandes (200+ items), considerar:
- Usar `wire:ignore` en tabla de items
- Implementar scroll virtual con Alpine.js
- Renderizar solo items visibles

---

### Solución 2: Sistema de Borradores

#### Opción 2A: Extender Campo `Enviado` (Recomendado)

**Ventajas**:
- No requiere ALTER TABLE
- Compatible con sistema actual
- Fácil de implementar

**Estados Propuestos**:
```php
// Agregar constantes en SalesOrderHeaderImp.php
const ESTADO_BORRADOR = 0;      // Orden incompleta, se puede editar
const ESTADO_COMPLETADO = 1;    // Orden completa, lista para Sage50
const ESTADO_ENVIADO = 2;       // Enviado exitosamente a Sage50
const ESTADO_PROCESANDO = 3;    // En proceso de envío a Sage50

// Helper methods
public function esBorrador(): bool
{
    return $this->Enviado === self::ESTADO_BORRADOR;
}

public function estaCompletado(): bool
{
    return $this->Enviado === self::ESTADO_COMPLETADO;
}

public function fueEnviado(): bool
{
    return $this->Enviado === self::ESTADO_ENVIADO;
}

public function puedeEditar(): bool
{
    return in_array($this->Enviado, [self::ESTADO_BORRADOR, self::ESTADO_COMPLETADO]);
}
```

**Implementación en CreateSaleOrder.php**:
```php
// Agregar propiedad
public $guardarComoBorrador = false;

/**
 * Guardar orden como borrador
 */
public function guardarBorrador()
{
    $this->validateStep1();
    // No validar Step 2 y 3 para borradores
    
    $this->guardarComoBorrador = true;
    $this->guardarOrden();
}

/**
 * Crear orden completa (lógica actual)
 */
public function createOrder()
{
    $this->validateStep1();
    $this->validateStep2();
    $this->validateStep3();
    
    $this->guardarComoBorrador = false;
    $this->guardarOrden();
}

/**
 * Método unificado para guardar
 */
protected function guardarOrden()
{
    $this->setDefaultConnection();
    $whereClient = $this->compareLocation();

    DB::beginTransaction();

    try {
        $company = $this->organization->company;
        $this->sales_order_number = $this->organization->sales_order_number + 1;
        $this->sales_rep_id = $this->user->sales_rep_id ?? null;

        $organizationId = $this->organization->getKey();
        $taxId = ImportConfigurationMagaya::getAccountInfo('TaxID', $organizationId);
        $arAccount = ImportConfigurationMagaya::getAccountInfo('AR_Account', $organizationId);

        $this->setCustomConnectionWithoutUser($this->organization->database);

        $now = docucenter_date_format(now('America/Panama'), 'Y-m-d H:i:s', get_user_timezone());

        // Estado según si es borrador o completado
        $estadoEnviado = $this->guardarComoBorrador 
            ? SalesOrderHeaderImp::ESTADO_BORRADOR 
            : SalesOrderHeaderImp::ESTADO_COMPLETADO;

        $header = SalesOrderHeaderImp::query()->create([
            'ID_compania' => $company->ID_compania ?? null,
            'SalesOrderNumber' => $this->strPad($this->sales_order_number, 10),
            'CustomerID' => $this->customer_id,
            'CustomerName' => $this->customer_name,
            'CustomerPO' => $this->customer_po,
            'ShipToAddressLine1' => $this->customer_ship_to_address,
            'ShipToAddressLine2' => $this->customer_ship_to_address_2,
            'Subtotal' => $this->totalSubtotal,
            'TaxID' => empty($taxId) ? '' : $taxId,
            'OrderTax' => $this->totalITBMS,
            'Net_due' => $this->totalPrecioFinal,
            'SalesRepID' => $this->sales_rep_id,
            'InvoiceNote' => $this->invoice_note,
            'AR_Account' => $arAccount,
            'CoordRec' => "{$this->userLatitude},{$this->userLongitude}",
            'WithinRadius' => $whereClient === true ? 1 : 0,
            'LAST_CHANGE' => $now,
            'date' => $now,
            'Enviado' => $estadoEnviado,  // Borrador o Completado
            'Error' => 0,
        ]);

        foreach ($this->items as $lineNumber => $item) {
            // ... código existente para detalles ...
        }

        $this->setDefaultConnection();

        $this->organization->sales_order_number = $this->sales_order_number;
        $this->organization->save();

        DB::commit();

        $mensaje = $this->guardarComoBorrador 
            ? __('Borrador guardado exitosamente') 
            : __('CreatedMessage', ['name' => __('Sales Order')]);

        $this->dispatchBrowserEvent('show-message', [
            'type' => 'success', 
            'message' => $mensaje
        ]);
        
        if (!$this->guardarComoBorrador) {
            // Solo resetear si NO es borrador
            $this->reset(
                'customer_id',
                'customer_po',
                // ... resto de campos ...
            );
        } else {
            // Redirigir a edición del borrador
            return redirect()->route(
                getCustomerName().'.sage50.sales_orders.edit', 
                ['id' => $header->getKey()]
            );
        }

    } catch (\Throwable $exception) {
        DB::rollBack();
        report($exception);
        $this->dispatchBrowserEvent('show-message', [
            'type' => 'error',
            'message' => __('ErrorMessage')
        ]);
    }
}
```

**Actualización del Blade**:
```blade
<!-- En step 3, agregar botones -->
<div class="step-tree" x-show="currentStep == 3">
    <!-- ... campos existentes ... -->
    
    <div class="d-flex gap-2 mt-3">
        <!-- Guardar como borrador -->
        <button wire:click="guardarBorrador" 
                class="btn btn-secondary" 
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="guardarBorrador">
                <i class="fa fa-save"></i> {{ __('Guardar Borrador') }}
            </span>
            <span wire:loading wire:target="guardarBorrador">
                <i class="fas fa-spinner fa-spin"></i> {{ __('Guardando...') }}
            </span>
        </button>

        <!-- Crear orden completa -->
        <button wire:click="createOrder" 
                class="btn btn-primary" 
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="createOrder">
                {{ __('Create sales order') }}
            </span>
            <span wire:loading wire:target="createOrder">
                <i class="fas fa-spinner fa-spin"></i> {{ __('Creando...') }}
            </span>
        </button>
    </div>
</div>
```

#### Vista de Borradores

**Componente Livewire**: `App\Http\Livewire\Admin\Sage50\DraftSalesOrders`
```php
<?php

namespace App\Http\Livewire\Admin\Sage50;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SalesOrderHeaderImp;

class DraftSalesOrders extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $drafts = SalesOrderHeaderImp::query()
            ->where('Enviado', SalesOrderHeaderImp::ESTADO_BORRADOR)
            ->orderBy('LAST_CHANGE', 'desc')
            ->paginate(20);

        return view('livewire.admin.sage50.draft-sales-orders', [
            'drafts' => $drafts
        ])->layout('admin::layouts.app', ['title' => __('Draft Sales Orders')]);
    }

    public function eliminarBorrador($id)
    {
        $draft = SalesOrderHeaderImp::find($id);
        
        if ($draft && $draft->esBorrador()) {
            $draft->delete();
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'success',
                'message' => __('Borrador eliminado')
            ]);
        }
    }
}
```

#### Opción 2B: Agregar Columna `estado` (Más Robusto)

Si se prefiere mayor claridad semántica:

```bash
# Migration
php artisan db:add-column-to-organizations-table \
    SalesOrder_Header_Imp \
    estado \
    "ENUM('borrador','completado','enviado','error')" \
    --default=borrador \
    --index=1
```

```php
// Model
const ESTADO_BORRADOR = 'borrador';
const ESTADO_COMPLETADO = 'completado';
const ESTADO_ENVIADO = 'enviado';
const ESTADO_ERROR = 'error';
```

**Ventajas**:
- Más legible
- Extensible a futuro
- Evita "números mágicos"

**Desventajas**:
- Requiere migración en producción
- Cambios en stubs

---

## Recomendaciones Finales

### Para Implementar Inmediatamente

1. **Rendimiento**: Opción 1A (Cálculo Incremental)
   - Mejora crítica para UX
   - Sin cambios en BD
   - Backward compatible

2. **Borradores**: Opción 2A (Extender campo Enviado)
   - Sin migración
   - Funcionalidad completa
   - Implementación rápida

### Para Futuro (Opcional)

1. **Rendimiento**: Opción 1B (Toggle Auto-Calcular)
   - Para usuarios con órdenes 100+ items
   - Opcional, no crítico

2. **Borradores**: Opción 2B (Columna estado)
   - Si se planean más estados
   - Migración coordinada

### Validación Post-Implementación

```php
// Test de rendimiento
// Agregar 100 items y medir tiempo
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $this->addProduct($productData);
}
$time = microtime(true) - $start;
// Antes: ~5-10 segundos
// Después: ~0.5-1 segundo
```

### Jobs de Exportación a Sage50

**Importante**: Actualizar jobs que exportan a Sage50 para:
1. Ignorar órdenes con `Enviado = 0` (borradores)
2. Solo procesar órdenes con `Enviado = 1` (completadas)
3. Marcar como `Enviado = 2` al enviar exitosamente

```php
// En el Job de exportación
$ordenes = SalesOrderHeaderImp::query()
    ->where('Enviado', SalesOrderHeaderImp::ESTADO_COMPLETADO)
    ->where('Error', 0)
    ->get();
```

---

## Archivos Afectados

### Modificaciones Requeridas
1. `app/Http/Livewire/Admin/Sage50/CreateSaleOrder.php`
2. `app/Models/SalesOrderHeaderImp.php`
3. `resources/views/livewire/admin/sage50/create_sale_order.blade.php`

### Archivos Nuevos
1. `app/Http/Livewire/Admin/Sage50/DraftSalesOrders.php`
2. `resources/views/livewire/admin/sage50/draft-sales-orders.blade.php`

### Posibles Conflictos
- Jobs de exportación a Sage50 que usan campo `Enviado`
- Reportes que filtran por estado de órdenes
- Vistas que muestran listas de órdenes

---

## Testing

### Casos de Prueba

1. **Rendimiento**:
   - Crear orden con 10 items (baseline)
   - Crear orden con 50 items (problema reportado)
   - Crear orden con 100 items (stress test)
   - Editar item en orden de 50 items
   - Eliminar item en orden de 50 items

2. **Borradores**:
   - Guardar borrador sin items → debe fallar (mínimo 1 item)
   - Guardar borrador con 1 item → debe permitir
   - Editar borrador guardado
   - Completar borrador → cambiar estado a Completado
   - Eliminar borrador
   - Borrador NO debe aparecer en jobs de exportación

3. **Regresión**:
   - Crear orden completa (flujo actual) → debe seguir funcionando
   - Órdenes antiguas → deben seguir siendo compatibles

---

## Próximos Pasos

1. Revisar y aprobar propuestas
2.  Implementar Opción 1A (Rendimiento)
3.  Implementar Opción 2A (Borradores)
4.  Testing en desarrollo
5.  Documentar en manual de usuario
6.  Desplegar a producción
