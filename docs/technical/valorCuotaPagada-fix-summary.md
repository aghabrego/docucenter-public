# Solución al Error PAC: Campo valorCuotaPagada Requerido

## Problema Reportado

Error PAC de TheFactoryHKA: **"En la ocurrencia [2] de FormaPago, El campo valorCuotaPagada es requerido"**

Este error ocurría específicamente en la emisión de facturas desde Lightspeed Serie R.

## Análisis del Problema

### 1. Estructura de Pagos en DGI Panamá

Según la documentación de DGI y TheFactoryHKA PAC, cada forma de pago requiere:

```php
[
    'iFormaPago' => '01',           // Código de forma de pago (requerido)
    'dVlrCuota' => 100.00,          // Valor de la cuota (requerido)
    'valorCuotaPagada' => 100.00,   // Valor pagado (requerido por TheFactoryHKA)
    'dFormaPagoDesc' => 'Efectivo', // Descripción (opcional, requerido si iFormaPago='99')
    'cNroAutoriza' => '',           // Número de autorización (opcional)
]
```

**Nota importante**: `valorCuotaPagada` es un campo requerido por **TheFactoryHKA PAC**, pero **NO por Alanube** (que usa estructura diferente).

### 2. Causa Raíz Encontrada

El error "En la ocurrencia [2]" indicaba que el problema estaba en el **segundo pago** del array, que típicamente es el pago adicional agregado para equiparar los descuentos.

Se identificaron **DOS problemas**:

#### Problema A: Campo valorCuotaPagada faltante inicialmente
- El campo `valorCuotaPagada` no se incluía en la construcción inicial de pagos
- Solo se incluía `dVlrCuota`

#### Problema B: HKAService sobrescribía el campo
El problema crítico estaba en `/app/Services/HKAService.php` línea 317:

```php
// CÓDIGO ANTERIOR (INCORRECTO)
$totales->listaFormaPago[] = [
    'formaPagoFact' => $this->getNestedValue($formaPago, 'iFormaPago'),
    'valorCuotaPagada' => $this->normalizeNumericValueToTwoDecimals($this->getNestedValue($formaPago, 'dVlrCuota')),
    // ⬆Siempre usaba dVlrCuota, ignorando valorCuotaPagada del array original
    'descFormaPago' => $this->getNestedValue($formaPago, 'dFormaPagoDesc'),
];
```

**El servicio siempre tomaba el valor de `dVlrCuota` y lo asignaba a `valorCuotaPagada`**, ignorando el campo `valorCuotaPagada` que estábamos agregando en los componentes.

#### Problema C: HKAService usaba arrays en vez de objetos FormaPago
**CRÍTICO**: Aunque corregimos la lectura del campo `valorCuotaPagada`, el servicio usaba **arrays** en vez de crear instancias de la clase `FormaPago`. Esto causaba que el campo **NO se escribiera en el XML** enviado al PAC.

```php
// INCORRECTO: Usar array
$totales->listaFormaPago[] = [
    'formaPagoFact' => '01',
    'valorCuotaPagada' => '100.00',
];
// Resultado: XML solo incluye <dVlrCuota>, NO <valorCuotaPagada>

// CORRECTO: Usar objeto FormaPago
$formaPago = new FormaPago();
$formaPago->formaPagoFact = '01';
$formaPago->valorCuotaPagada = '100.00';
$totales->listaFormaPago[] = $formaPago;
// Resultado: XML incluye <valorCuotaPagada>
```

## Solución Implementada

### 1. Agregar valorCuotaPagada en Construcción de Pagos

**Archivos modificados:**
- `/app/Http/Livewire/Admin/Einvoice/CreateFastJob.php`
- `/app/Http/Livewire/Admin/Einvoice/CreateFast.php`
- `/app/Http/Livewire/Admin/Einvoice/Create.php`

**Cambios aplicados:**

```php
// En foreach de pagos principales
$payments[] = $this->validArray([
    'iFormaPago' => $type->code,
    ...$type->code === '99' ? ['dFormaPagoDesc' => $type->name] : [],
    'dVlrCuota' => $this->numberFormat($totalValue),
    'valorCuotaPagada' => $this->numberFormat($totalValue), // AGREGADO
    'cNroAutoriza' => '', // AGREGADO
]);

// En pago adicional para descuentos
$payments[] = $this->validArray([
    'iFormaPago' => $type->code,
    ...$type->code === '99' ? ['dFormaPagoDesc' => $type->name] : [],
    'dVlrCuota' => $this->numberFormat($montoDescuentos),
    'valorCuotaPagada' => $this->numberFormat($montoDescuentos), // AGREGADO
    'cNroAutoriza' => '', // AGREGADO
]);
```

### 2. Sincronizar valorCuotaPagada en Ajustes

Cuando se ajusta el último pago para equiparar totales:

```php
// Ajustar el último pago para que la suma sea exacta
$lastIndex = count($payments) - 1;
$adjustment = $dVTot - ($totalPayments - $payments[$lastIndex]['dVlrCuota']);
$payments[$lastIndex]['dVlrCuota'] = $this->numberFormat($adjustment, 2);
// CRÍTICO: Sincronizar valorCuotaPagada con el ajuste
$payments[$lastIndex]['valorCuotaPagada'] = $this->numberFormat($adjustment, 2); // AGREGADO
```

### 3. Corregir HKAService para Leer Campo Correcto

**Archivo:** `/app/Services/HKAService.php` líneas 314-327

```php
// CÓDIGO NUEVO (CORRECTO)
$gFormaPago = $this->getNestedValue($doc, 'gTot.gFormaPago', []);
foreach ($gFormaPago as $formaPago) {
    $valorCuotaPagada = $this->getNestedValue($formaPago, 'valorCuotaPagada');
    if ($valorCuotaPagada === null || $valorCuotaPagada === '') {
        // Fallback a dVlrCuota si valorCuotaPagada no existe
        $valorCuotaPagada = $this->getNestedValue($formaPago, 'dVlrCuota');
    }
    
    $totales->listaFormaPago[] = [
        'formaPagoFact' => $this->getNestedValue($formaPago, 'iFormaPago'),
        'valorCuotaPagada' => $this->normalizeNumericValueToTwoDecimals($valorCuotaPagada),
        'descFormaPago' => $this->getNestedValue($formaPago, 'dFormaPagoDesc'),
    ];
}
```

**Cambio clave:** Ahora primero intenta leer `valorCuotaPagada` del array, y solo usa `dVlrCuota` como fallback si no existe.

### 4. Usar Objetos FormaPago en Vez de Arrays

**Archivo:** `/app/Services/HKAService.php` líneas 5-7 y 314-328

**CRÍTICO**: La solución definitiva fue cambiar de usar arrays a usar objetos de la clase `FormaPago`:

```php
// Agregar import
use App\Utils\hka\FormaPago;

// Código corregido
$gFormaPago = $this->getNestedValue($doc, 'gTot.gFormaPago', []);
foreach ($gFormaPago as $formaPago) {
    $valorCuotaPagada = $this->getNestedValue($formaPago, 'valorCuotaPagada');
    if ($valorCuotaPagada === null || $valorCuotaPagada === '') {
        $valorCuotaPagada = $this->getNestedValue($formaPago, 'dVlrCuota');
    }

    $formaPagoObj = new FormaPago();
    $formaPagoObj->formaPagoFact = $this->getNestedValue($formaPago, 'iFormaPago');
    $formaPagoObj->valorCuotaPagada = $this->normalizeNumericValueToTwoDecimals($valorCuotaPagada);
    $formaPagoObj->descFormaPago = $this->getNestedValue($formaPago, 'dFormaPagoDesc');
    
    $totales->listaFormaPago[] = $formaPagoObj;
}
```

**Por qué es importante**: Solo al usar objetos de la clase `FormaPago`, el serializador XML incluye correctamente el tag `<valorCuotaPagada>` en el XML enviado al PAC. Con arrays, solo se incluía `<dVlrCuota>`.

### 5. Agregar Logging para Debugging

En `CreateFastJob.php`:

```php
// DEBUG: Logging para verificar estructura de pagos antes de ajuste
Log::info('CreateFastJob - Pagos antes del ajuste', [
    'payments' => $payments,
    'dTotRec' => $dTotRec,
    'dVTot' => $dVTot,
]);

// ... lógica de ajuste ...

// DEBUG: Logging para verificar estructura de pagos DESPUÉS del ajuste
Log::info('CreateFastJob - Pagos DESPUÉS del ajuste', [
    'payments' => $payments,
    'dTotRec' => $dTotRec,
    'dVTot' => $dVTot,
    'count' => count($payments),
]);
```

Esto permite verificar en logs que el campo `valorCuotaPagada` se mantiene en todos los pagos.

## Archivos Modificados

1. **CreateFastJob.php** - Trait para emisión rápida desde Lightspeed
2. **CreateFast.php** - Componente de emisión rápida estándar
3. **Create.php** - Componente de emisión paso a paso
4. **HKAService.php** - Servicio de formateo para TheFactoryHKA PAC

## Commits Realizados

1. **Commit 1:** `fix: agregar campo valorCuotaPagada a todos los pagos en CreateFast y componentes relacionados`
2. **Commit 2:** `fix: sincronizar valorCuotaPagada al ajustar pagos para equiparar totales`
3. **Commit 3:** `fix: agregar campo cNroAutoriza y corregir lectura de valorCuotaPagada en HKAService`
4. **Commit 4 (CRÍTICO):** `fix: usar objeto FormaPago en vez de array para incluir valorCuotaPagada en XML PAC`
   - Este fue el commit definitivo que resolvió el problema
   - Cambió de usar arrays a objetos FormaPago para asegurar que el campo se incluya en el XML

## Validación

Para verificar que el problema está resuelto:

1. **Revisar logs** en `storage/logs/laravel.log`:
   ```bash
   tail -f storage/logs/laravel.log | grep "CreateFastJob - Pagos"
   ```

2. **Verificar estructura de pagos**: Debe incluir `valorCuotaPagada` en TODOS los pagos, especialmente en la ocurrencia [2].

3. **Verificar XML generado**: El log `SignedXml` debe mostrar el tag `<valorCuotaPagada>` en cada `<gFormaPago>`:
   ```xml
   <gFormaPago>
       <iFormaPago>03</iFormaPago>
       <dVlrCuota>20.97</dVlrCuota>
       <valorCuotaPagada>20.97</valorCuotaPagada>
   </gFormaPago>
   ```

4. **Probar emisión**: Emitir una factura desde Lightspeed Serie R con descuentos (para generar el segundo pago).

## Notas Importantes

### Diferencias entre PACs

- **TheFactoryHKA**: Requiere campo `valorCuotaPagada` separado de `dVlrCuota`
- **Alanube**: Usa estructura diferente con campo `amount` en vez de `valorCuotaPagada`

### Campo cNroAutoriza

El campo `cNroAutoriza` (número de autorización) se agrega como string vacío `''`. Aunque `validArray()` filtra strings vacíos, este campo es opcional y no causa problemas si se elimina.

### Método validArray()

El método `validArray()` (definido en `vendor/weirdo/helper/src/Helper/Traits/HelperArray.php`) filtra valores `null` y strings vacíos usando `!empty($value)`. Por eso:

- `'valorCuotaPagada' => '100.00'` - Se mantiene (tiene valor)
- `'cNroAutoriza' => ''` - Se elimina (string vacío)
- `'valorCuotaPagada' => 0` - Se elimina (0 es empty)

**Recomendación**: Para campos numéricos que pueden ser 0, no usar `validArray()` o agregar lógica especial.

## Lecciones Aprendidas

1. **Verificar formatters**: Los servicios de formateo (HKAService, AlanubeFormatterHelper) pueden sobrescribir o ignorar campos del array original
2. **Logging es crítico**: Agregar logging antes y después de transformaciones ayuda a identificar dónde se pierde información
3. **Sincronización de campos**: Si un campo representa el mismo concepto que otro, ambos deben actualizarse juntos
4. **Diferencias entre PACs**: Cada PAC puede tener requerimientos distintos; revisar documentación específica
5. **Arrays vs Objetos en XML**: **CRÍTICO** - Al serializar a XML, usar objetos de clases específicas en vez de arrays asegura que TODOS los campos se incluyan correctamente en el XML. Los arrays pueden causar que campos se omitan en la serialización.
6. **Revisar el XML generado**: Siempre verificar el XML final (log `SignedXml`) para confirmar que los campos esperados están presentes, no solo la estructura de datos en memoria

## Referencias

- Documentación DGI Panamá: Estructura gFormaPago
- Documentación TheFactoryHKA PAC: Validaciones de campos requeridos
- Archivo relacionado: `/docs/technical/pac-validation-fixes.md`
