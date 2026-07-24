# Cambios Implementados - Formulario de Combustible

## Requerimientos Completados

### 1. ✅ Permitir monto en 0.00
**Archivo**: `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`
- Cambió validación de `min:0.01` a `min:0` en `bombas.*.dinero`
- El sistema ahora acepta montos iguales a 0.00 sin generar errores de validación

### 2. ✅ Evitar jornadas duplicadas por día
**Archivo**: `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`
- Agregada nueva función `validarJornadaDuplicada()`
- Se llama en `validateInputs()` antes de procesar el formulario
- Verifica que no exista jornada (turno) duplicada para la misma fecha
- Lanza excepción de validación con mensaje indicando: "Ya existe una jornada de [Mañana/Tarde/Noche] para el día [fecha]"

### 3. ✅ Control de sobrante o faltante
**Archivo**: `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`
- Se eliminó la validación estricta de balance (`validateBalance()`)
- Cuando hay diferencia (balance ≠ 0):
  - **Si SOBRANTE** (balance > 0.02):
    - Crea línea POSITIVA: "Sobrante en turno: $XXX.XX" (SOBRANTE)
    - Crea línea NEGATIVA: "Contraasiento de sobrante" (SOBRANTE_CONTRA)
  - **Si FALTANTE** (balance < -0.02):
    - Crea línea NEGATIVA: "Faltante en turno: $XXX.XX" (FALTANTE)
    - Crea línea POSITIVA: "Contraasiento de faltante" (FALTANTE_CONTRA)
- Ambas líneas se registran con Category_ID = 'ADJUSTMENT'
- Permite cuadre contable automático

### 4. ✅ Agregar sección de mercancía
**Archivo**: `app/Http/Livewire/Admin/Sage50/CreateFuelSale.blade.php`
**Archivo**: `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`

#### Cambios en PHP:
- Agregada propiedad pública `$mercancias = []`
- Agregada método `agregarMercancia()` - Crea fila vacía en mercancías
- Agregada método `eliminarMercancia($index)` - Elimina fila de mercancías
- Agregada método `updatedMercancias()` - Recalcula monto automáticamente (cantidad × precio)
- Agregada método `getTotalMercancias()` - Suma todos los montos
- Agregada método `getTotalGeneral()` - Suma combustible + mercancía
- Actualizado `getBalance()` - usa totalGeneral en vez de solo combustible
- Actualizado `resetFormulario()` - resetea mercancías en nuevo turno
- Actualizado `render()` - pasa totalMercancias y totalGeneral a la vista

#### Estructura de mercancía:
```php
$mercancias[] = [
    'producto_id'  => '',      // ID del producto (opcional)
    'producto_nom' => '',      // Nombre del producto
    'cantidad'     => '',      // Cantidad
    'precio'       => '',      // Precio unitario
    'monto'        => '',      // Cantidad × Precio (calculado automáticamente)
];
```

#### Cambios en Blade:
- Nueva sección "Mercancía" con tabla que contiene:
  - Columna: Producto (campo de texto)
  - Columna: Cantidad (input number)
  - Columna: Precio Unitario (input number con $)
  - Columna: Total (input number, READONLY, calculado automáticamente)
  - Columna: Botón eliminar fila
  - Footer: Muestra total de mercancía
- Botón "Agregar item" para añadir filas
- Mensaje "No merchandise items" cuando está vacío

#### Integración en guardado:
- Se procesan mercancías al guardar turno
- Cada mercancía se registra como línea detail con Category_ID = 'MERCHANDISE'
- El monto total (Combustible + Mercancía) se usa para calcular balance y sobrante/faltante

#### Actualización de resumen de totales:
- Ahora muestra 3 componentes principales:
  1. Total Combustible
  2. Total Mercancía
  3. Gran Total (Combustible + Mercancía)
- Se mantiene visualización de pagos y balance

---

## Archivos Modificados

### 1. `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`
- Agregadas propiedades: `$mercancias`, `$productosConPrecio`
- Nuevos métodos: `agregarMercancia()`, `eliminarMercancia()`, `updatedMercancias()`, `validarJornadaDuplicada()`, `getTotalMercancias()`, `getTotalGeneral()`
- Modificados: `validateInputs()`, `validateBalance()`, `getBalance()`, `guardar()`, `resetFormulario()`, `render()`
- Lógica de sobrante/faltante integrada en `guardar()`

### 2. `resources/views/livewire/admin/sage50/create_fuel_sale.blade.php`
- Nueva sección "Mercancía" agregada entre "Formas de Pago" y "Otros Pagos"
- Actualizado resumen de totales para mostrar combustible, mercancía y gran total

---

## Datos en Base de Datos

### Cambios en Sales_Detail_Imp para sobrante/faltante:
```
Item_Code: 'SOBRANTE' o 'FALTANTE'
Description: 'Sobrante en turno: $XXX.XX' o 'Faltante en turno: $XXX.XX'
Unit_Price: ±diferencia
Sub_Total: ±diferencia
Category_ID: 'ADJUSTMENT'
```

### Cambios en Sales_Detail_Imp para mercancía:
```
Item_Code: [ID del producto]
Description: [Nombre del producto]
Quantity: [cantidad]
Unit_Price: [precio unitario]
Sub_Total: [monto total]
Category_ID: 'MERCHANDISE'
```

---

## Validaciones

✅ Monto combustible puede ser 0.00
✅ No permite duplicar jornada para mismo día y turno
✅ Acepta balance desajustado (registra sobrante/faltante)
✅ Calcula automáticamente monto de mercancía (cantidad × precio)
✅ Almacena combustible y mercancía correctamente

---

## Notas Importantes

- El balance puede estar desajustado y se registrará automáticamente como sobrante o faltante
- Las líneas de sobrante/faltante se crean en pares (una positiva y una negativa) para cuadre contable
- El cálculo de balance ahora incluye mercancía: `(Combustible + Mercancía) - Pagos`
- La sección de mercancía es completamente opcional
