# Mapeo Completo de Estructuras por Tipo de Documento - Create.php

## Análisis Completo de Implementación

Se ha completado la implementación de **todas las estructuras específicas** requeridas por cada tipo de documento según normativas DGI de Panamá en el componente `app/Http/Livewire/Admin/Einvoice/Create.php`.

## Estructuras Implementadas

### **Ubicación en el Código**
**Archivo**: `app/Http/Livewire/Admin/Einvoice/Create.php`
**Método**: `issueDocument()`
**Líneas**: ~1695-1735 (después de `$request['dGen'] = $dGen;`)

### **Mapeo por Tipo de Documento**

| Tipo | Código | Nombre | Estructura Específica | Estado |
|------|--------|--------|----------------------|--------|
| 01 | 1 | Factura Nacional | Base (sin estructura adicional) | |
| 02 | 2 | Factura Simplificada | Base (sin estructura adicional) | |
| 03 | 3 | **Factura de Exportación** | `gFExp` | **IMPLEMENTADO** |
| 04 | 4 | **Nota de Crédito** | `gDocRef` | **IMPLEMENTADO** |
| 05 | 5 | **Nota de Débito** | `gDocRef` | **IMPLEMENTADO** |
| 06 | 6 | **Nota Genérica de Crédito** | `gNotaGen` | **IMPLEMENTADO** |
| 07 | 7 | **Nota Genérica de Débito** | `gNotaGen` | **IMPLEMENTADO** |
| 09 | 9 | **Reembolso** | `gCompOri` | **IMPLEMENTADO** |

## Detalles de Implementación

### 1. **gFExp - Factura de Exportación (Tipo 03)**
```php
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

**Campos Mapeados:**
- `condicionesEntrega` → `cCondEntr` (INCOTERM: FOB, CIF, etc.)
- `monedaExportacion` → `cMoneda` (Código ISO: USD, EUR, etc.)
- `tipoCambio` → `dCambio` (Decimal 6 decimales)
- `montoMonedaExtranjera` → `dVTotEst` (Decimal 2 decimales)
- `puertoEmbarque` → `dPuertoEmbarq` (Texto libre)

### 2. **gDocRef - Notas de Crédito/Débito (Tipos 04, 05)**
```php
if (in_array($this->tipeDocument, ['4', '5'])) {
    $request['gDocRef'] = $this->validArray([
        'dNroDF' => $this->numeroDocumentoReferenciado, // Número documento referenciado
        'dCufeRef' => $this->cufeReferenciado, // CUFE del documento referenciado
        'dFechaRef' => $this->fechaDocumentoReferenciado ? docucenter_date_format($this->fechaDocumentoReferenciado, 'Y-m-d', get_user_timezone()) : null, // Fecha documento referenciado
        'dNombEmiRef' => $this->nombreEmisorReferenciado, // Nombre emisor referenciado
        'dRucEmiRef' => $this->rucEmisorReferenciado, // RUC emisor referenciado
    ]);
}
```

**Campos Mapeados:**
- `numeroDocumentoReferenciado` → `dNroDF` (Número del documento original)
- `cufeReferenciado` → `dCufeRef` (CUFE de 96 caracteres)
- `fechaDocumentoReferenciado` → `dFechaRef` (Fecha formato Y-m-d)
- `nombreEmisorReferenciado` → `dNombEmiRef` (Nombre del emisor original)
- `rucEmisorReferenciado` → `dRucEmiRef` (RUC del emisor original)

### 3. **gNotaGen - Notas Genéricas (Tipos 06, 07)**
```php
if (in_array($this->tipeDocument, ['6', '7'])) {
    $request['gNotaGen'] = $this->validArray([
        'dConcNota' => $this->conceptoNota, // Concepto de la nota
        'dPeriodoNota' => $this->periodoNota, // Período de la nota
    ]);
}
```

**Campos Mapeados:**
- `conceptoNota` → `dConcNota` (Descripción del concepto)
- `periodoNota` → `dPeriodoNota` (Período aplicable)

### 4. **gCompOri - Reembolso (Tipo 09)**
```php
if ($this->tipeDocument === '9') {
    $request['gCompOri'] = $this->validArray([
        'dNumCompOri' => $this->numeroComprobanteOriginal, // Número comprobante original
        'dFechaCompOri' => $this->fechaComprobanteOriginal ? docucenter_date_format($this->fechaComprobanteOriginal, 'Y-m-d', get_user_timezone()) : null, // Fecha comprobante original
        'dRazonReemb' => $this->razonReembolso, // Razón del reembolso
    ]);
}
```

**Campos Mapeados:**
- `numeroComprobanteOriginal` → `dNumCompOri` (Número del comprobante original)
- `fechaComprobanteOriginal` → `dFechaCompOri` (Fecha formato Y-m-d)
- `razonReembolso` → `dRazonReemb` (Justificación del reembolso)

## Flujo Completo de Construcción

### Orden de Construcción en `issueDocument()`
1. **dGen** - Datos generales (siempre presente)
2. **Estructuras específicas por tipo**:
   - `gFExp` (solo tipo 03)
   - `gDocRef` (tipos 04, 05)
   - `gNotaGen` (tipos 06, 07)
   - `gCompOri` (solo tipo 09)
3. **gItem** - Items del documento
4. **gTot** - Totales (con gRetenc y gPagPlazo si aplica)
5. **gPedComGl** - Pedido comercial global

### Array Final Esperado

**Para Exportación (Tipo 03):**
```php
array:6 [
  "key" => null
  "dGen" => [...] // Datos generales
  "gFExp" => [...] // DATOS DE EXPORTACIÓN
  "gItem" => [...] // Items
  "gTot" => [...] // Totales
  "gPedComGl" => [...] // Pedido comercial
]
```

**Para Nota de Crédito (Tipo 04):**
```php
array:6 [
  "key" => null
  "dGen" => [...] // Datos generales
  "gDocRef" => [...] // REFERENCIA DOCUMENTO ORIGINAL
  "gItem" => [...] // Items
  "gTot" => [...] // Totales
  "gPedComGl" => [...] // Pedido comercial
]
```

## Validaciones DGI Incluidas

### Campos Obligatorios por Tipo
- **Tipo 03**: `condicionesEntrega` (INCOTERM obligatorio)
- **Tipos 04,05**: `cufeReferenciado`, `rucEmisorReferenciado`, `fechaDocumentoReferenciado`, `numeroDocumentoReferenciado`
- **Tipos 06,07**: `conceptoNota` (opcional), `periodoNota` (opcional)
- **Tipo 09**: `numeroComprobanteOriginal`, `fechaComprobanteOriginal`, `razonReembolso`

### Reglas de Negocio
- **Exportación**: Solo destino extranjero (`destinoOperacion = 2`)
- **Notas**: Fecha máximo 180 días atrás
- **Reembolso**: Fecha máximo 1 año atrás
- **CUFE**: Exactamente 96 caracteres

## Compatibilidad PAC

### Verificación por Proveedor
- **TheFactoryHKA**: Acepta todas las estructuras
- **Alanube**: `AlanubeFormatterHelper` mapea correctamente
- **PACs Genéricos**: Estructuras estándar DGI

## Estado Final

| Aspecto | Estado | Descripción |
|---------|--------|-------------|
| **Implementación** | COMPLETO | Todas las estructuras específicas implementadas |
| **Validaciones** | COMPLETO | Reglas DGI implementadas en `getConditionalRules()` |
| **Campos** | COMPLETO | Todas las propiedades definidas en el componente |
| **Compatibilidad** | COMPLETO | Compatible con todos los PACs |
| **Documentación** | COMPLETO | Guías técnicas y scripts de prueba disponibles |

## Conclusión

**PROBLEMA RESUELTO COMPLETAMENTE**: Ahora **todos los tipos de documento** mapean correctamente sus datos específicos al array principal para la emisión:

1. **Facturas base (01,02)**: Solo estructura base
2. **Exportación (03)**: Estructura `gFExp` con datos de comercio internacional
3. **Notas crédito/débito (04,05)**: Estructura `gDocRef` con referencia al documento original
4. **Notas genéricas (06,07)**: Estructura `gNotaGen` con concepto y período
5. **Reembolso (09)**: Estructura `gCompOri` con datos del comprobante original

La implementación sigue el estándar DGI de Panamá y es compatible con todos los proveedores PAC del sistema.
