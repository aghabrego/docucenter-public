# Solución: Estructura gFExp Faltante en Facturas de Exportación

## Problema Identificado

Al intentar emitir una factura de exportación (tipo documento "03"), el array de datos no incluía la estructura `gFExp` obligatoria para documentos de exportación, causando que falten campos esenciales como INCOTERM, moneda, tipo de cambio y puerto de embarque.

## Análisis del Error

### Array Original (Sin gFExp)
```php
array:5 [
  "key" => null
  "dGen" => [...]
  "gItem" => [...]
  "gTot" => [...]
  "gPedComGl" => [...]
  // ❌ Falta "gFExp" => [...]
]
```

### Campos de Exportación Disponibles
El componente `Create.php` ya tenía definidos todos los campos necesarios:
- `$condicionesEntrega` (INCOTERM)
- `$monedaExportacion` (código de moneda)
- `$tipoCambio` (tipo de cambio)
- `$montoMonedaExtranjera` (monto en moneda extranjera)
- `$puertoEmbarque` (puerto de embarque)

## Solución Implementada

### 1. Agregar Construcción de gFExp
Se agregó la construcción de la estructura `gFExp` después de `$request['dGen'] = $dGen;` en el método `issueDocument()`:

```php
// Agregar estructura gFExp para documentos de exportación (tipo 03)
if ($this->tipeDocument === '3') {
    $request['gFExp'] = $this->validArray([
        'cCondEntr' => $this->condicionesEntrega, // INCOTERM
        'cMoneda' => $this->monedaExportacion ?: 'USD', // Código de moneda
        'dCambio' => $this->tipoCambio ? $this->numberFormat($this->tipoCambio, 6) : null, // Tipo de cambio
        'dVTotEst' => $this->montoMonedaExtranjera ? $this->numberFormat($this->montoMonedaExtranjera, 2) : null, // Monto en moneda extranjera
        'dPuertoEmbarq' => $this->puertoEmbarque, // Puerto de embarque
    ]);
}
```

### 2. Mapeo de Campos
| Campo Component | Campo gFExp | Descripción | Formato |
|---|---|---|---|
| `condicionesEntrega` | `cCondEntr` | INCOTERM (FOB, CIF, etc.) | String |
| `monedaExportacion` | `cMoneda` | Código moneda ISO | String (USD, EUR, etc.) |
| `tipoCambio` | `dCambio` | Tipo de cambio | Decimal 6 decimales |
| `montoMonedaExtranjera` | `dVTotEst` | Monto moneda extranjera | Decimal 2 decimales |
| `puertoEmbarque` | `dPuertoEmbarq` | Puerto de embarque | String |

### 3. Resultado Esperado
```php
array:6 [
  "key" => null
  "dGen" => [...]
  "gItem" => [...]
  "gTot" => [...]
  "gPedComGl" => [...]
  "gFExp" => [
    "cCondEntr" => "FOB"
    "cMoneda" => "USD"
    "dCambio" => "1.000000"
    "dVTotEst" => "107.00"
    "dPuertoEmbarq" => "Puerto de Balboa"
  ]
]
```

## Validaciones DGI

### Reglas de Validación Existentes
Ya estaban implementadas las validaciones según DGI:
```php
// Exportación (B06=03) - VALIDACIONES CRÍTICAS COMPLETAS
if ($this->tipeDocument === '3') {
    $rules['destinoOperacion'] = 'required|in:2'; // Solo destino extranjero
    $rules['condicionesEntrega'] = 'required|string|max:50'; // INCOTERM obligatorio
    $rules['monedaExportacion'] = 'nullable|string|max:3';
    $rules['tipoCambio'] = 'required_if:monedaExportacion,!=,USD|numeric|min:0.01|max:999999.99';
    $rules['montoMonedaExtranjera'] = 'required_if:monedaExportacion,!=,USD|numeric|min:0.01';
}
```

### Compatibilidad PAC
La estructura `gFExp` es compatible con:
- ✅ **TheFactoryHKA**: Acepta estructura completa
- ✅ **Alanube**: El `AlanubeFormatterHelper` mapea correctamente los campos
- ✅ **PACs genéricos**: Estructura estándar DGI

## Testing

### Script de Prueba
Se creó `docs/testing/test-export-structure.php` para verificar la construcción:

```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test-export-structure.php
```

### Casos de Prueba
1. ✅ **Documento tipo 03**: Genera estructura gFExp completa
2. ✅ **Documento tipo 01/02**: No genera gFExp (correcto)
3. ✅ **Campos nulos**: Se filtran correctamente con `validArray()`
4. ✅ **Formatos numéricos**: Aplica decimales correctos

## Notas Técnicas

### Ubicación del Código
- **Archivo**: `app/Http/Livewire/Admin/Einvoice/Create.php`
- **Método**: `issueDocument()`
- **Línea**: ~1700 (después de `$request['dGen'] = $dGen;`)

### Consideraciones
1. **Condición específica**: Solo se agrega para `$this->tipeDocument === '3'`
2. **Filtrado de nulos**: Usa `validArray()` para omitir campos vacíos
3. **Formatos correctos**: Aplica `numberFormat()` según requerimientos DGI
4. **Retrocompatibilidad**: No afecta otros tipos de documento

## Estado Final

✅ **COMPLETADO**: La estructura gFExp se genera correctamente para facturas de exportación
✅ **VALIDADO**: Compatible con todos los proveedores PAC
✅ **DOCUMENTADO**: Guía completa y script de prueba disponible

El problema original de la estructura `gFExp` faltante ha sido resuelto completamente.
