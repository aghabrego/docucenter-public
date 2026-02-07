# Solución: Error "Table 'docucenter.Customers_Exp' doesn't exist"

## Problema

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'docucenter.Customers_Exp' doesn't exist
select * from `Customers_Exp` where `Customers_Exp`.`CustomerID` = 155705959-2-2021 limit 1
```

**Contexto**: Error en el componente `Single.php` al intentar acceder a la tabla `Customers_Exp` en la base de datos principal en lugar de la base de datos específica de la organización.

## Causa Raíz

El componente `App\Http\Livewire\Admin\Einvoice\Single` no estaba utilizando correctamente el sistema multi-tenant de DocuCenter. En el método `mount()`, se intentaba acceder a las relaciones `customerImp` y `customerExp` antes de establecer la conexión a la base de datos correcta de la organización.

### Flujo problemático:

1. `mount()` se ejecuta primero
2. Intenta acceder a `$this->sale->customerExp` 
3. Laravel busca en la BD principal (`docucenter`) en lugar de la BD de la organización
4. La tabla `Customers_Exp` no existe en la BD principal → Error

## Solución Implementada

**Archivo**: `app/Http/Livewire/Admin/Einvoice/Single.php`

### 1. Agregado el trait CustomConnection

```php
<?php

namespace App\Http\Livewire\Admin\Einvoice;

use Livewire\Component;
// ... otros imports
use App\Traits\CustomConnection;

class Single extends Component
{
    use CustomConnection;
```

### 2. Modificado el método mount()

```php
/**
 * @param \App\Models\SalesHeaderImp $sale
 * @return void
 */
public function mount(SalesHeaderImp $sale)
{
    // Establecer conexión a la base de datos de la organización PRIMERO
    $this->setOrganization();
    
    $this->sale = $sale;

    /** @var \App\Models\CustomersImp $customer */
    $customer = $this->sale->customerImp ?? $this->sale->customerExp;
    // ... resto del código
}
```

## Flujo Corregido

1. `mount()` llama primero a `setOrganization()`
2. `setOrganization()` establece la conexión correcta: `DB::connection()->useDatabase($organization->database)`
3. Ahora las consultas a `customerImp` y `customerExp` buscan en la BD de la organización
4. Las tablas `Customers_Imp` y `Customers_Exp` se encuentran correctamente

## Beneficios

- **Multi-tenant correcto**: Cada organización accede a su propia BD
- **Robustez**: No más errores de tabla no encontrada
- **Consistencia**: Mismo patrón que otros componentes del sistema
- **Compatibilidad**: Mantiene toda la funcionalidad existente

## Archivos Modificados

1. `app/Http/Livewire/Admin/Einvoice/Single.php`:
   - Agregado `use App\Traits\CustomConnection`
   - Agregado `use CustomConnection` al componente
   - Modificado `mount()` para llamar `setOrganization()` primero

## Testing

Para verificar la solución:

1. **Acceso a factura**: Navegar a una factura específica
2. **Verificar datos**: Que los datos del cliente se carguen correctamente
3. **No errores**: No debe aparecer el error de tabla no encontrada

## Notas Importantes

- **Orden crítico**: `setOrganization()` DEBE ejecutarse antes de cualquier consulta a la BD
- **Patrón establecido**: El método `setOrganization()` ya existía y manejaba correctamente la conexión
- **Reutilización**: Se aprovecha el método existente en lugar de duplicar lógica

---

**Resultado**: Error de tabla no encontrada solucionado mediante correcta configuración multi-tenant.
