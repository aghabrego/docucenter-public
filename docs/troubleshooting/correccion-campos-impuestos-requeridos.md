# Corrección de Campos de Impuestos Requeridos - PAC TheFactoryHKA

## Problema Identificado

**Error PAC**: "El campo valorITBMS es requerido", "El campo totalMontoGravado es requerido"

Este error indica que el PAC TheFactoryHKA requiere que ciertos campos de impuestos y totales estén presentes en el JSON, incluso cuando tienen valores en cero.

## Análisis del Problema

### Comportamiento Anterior
El sistema tenía lógica condicional que solo incluía campos de impuestos cuando tenían valores mayores a cero:

```php
// PROBLEMA: Lógica condicional eliminaba campos requeridos
$valorITBMS = $this->normalizeNumericValue($this->getNestedValue($detailItem, 'gITBMSItem.dValITBMS'));
if (floatval($valorITBMS) > 0) {
    $item->valorITBMS = $valorITBMS;
}
```

### Comportamiento Requerido por PAC
El PAC TheFactoryHKA requiere que campos específicos de impuestos y totales estén siempre presentes:
- `valorITBMS` - Valor del ITBMS por ítem
- `valorISC` - Valor del ISC por ítem
- `tasaITBMS` - Tasa del ITBMS por ítem
- `tasaISC` - Tasa del ISC por ítem
- `totalITBMS` - Total ITBMS en totales del documento
- `totalISC` - Total ISC en totales del documento
- `totalMontoGravado` - Total monto gravado en totales del documento

## Solución Implementada

### 1. Corrección de Lógica de Ítems

**Archivo**: `app/Services/HKAService.php` - Líneas ~223-235

```php
// CRÍTICO: valorITBMS siempre requerido según PAC (aunque sea cero)
$valorITBMS = $this->normalizeNumericValue($this->getNestedValue($detailItem, 'gITBMSItem.dValITBMS'));
$item->valorITBMS = $valorITBMS; // Siempre incluir, incluso si es "0.000000"

// CRÍTICO: valorISC siempre requerido según PAC (aunque sea cero)
$valorISC = $this->normalizeNumericValue($this->getNestedValue($detailItem, 'gISCItem.dValISC'));
$item->valorISC = $valorISC; // Siempre incluir, incluso si es "0.000000"
```

### 2. Corrección de Lógica de Totales

**Archivo**: `app/Services/HKAService.php` - Líneas ~243-250

```php
// CRÍTICO: totalITBMS siempre requerido según PAC (aunque sea cero)
$totalITBMS = $this->normalizeNumericValueToTwoDecimals($this->getNestedValue($doc, 'gTot.dTotITBMS'));
$totales->totalITBMS = $totalITBMS; // Siempre incluir, incluso si es "0.00"

// CRÍTICO: totalISC siempre requerido según PAC (aunque sea cero)
$totalISC = $this->normalizeNumericValueToTwoDecimals($this->getNestedValue($doc, 'gTot.dTotISC'));
$totales->totalISC = $totalISC; // Siempre incluir, incluso si es "0.00"
```

### 3. Corrección de Lógica de Totales Adicionales

**Archivo**: `app/Services/HKAService.php` - Líneas ~253-254

```php
// CRÍTICO: totalMontoGravado siempre requerido según PAC (aunque sea cero)
$totalGravado = $this->normalizeNumericValueToTwoDecimals($this->getNestedValue($doc, 'gTot.dTotGravado'));
$totales->totalMontoGravado = $totalGravado; // Siempre incluir, incluso si es "0.00"
```

### 4. Actualización del Array keepZeroFields

**Archivo**: `app/Services/HKAService.php` - Líneas ~366-370

```php
// Campos específicos que DEBEN incluirse aunque sean cero
$keepZeroFields = [
    'tipoSucursal', 'tipoOperacion', 'formatoCAFE', 'entregaCAFE',
    'envioContenedor', 'procesoGeneracion', 'tipoVenta', 'tiempoPago',
    'nroItems', 'nroPedidoCompraGlobal', 'nroAceptacion',
    // CRÍTICO: Campos de impuestos SIEMPRE requeridos según PAC
    'valorITBMS', 'valorISC', 'tasaITBMS', 'tasaISC',
    'totalITBMS', 'totalISC', 'totalMontoGravado'
];
```

## Validación Implementada

### Script de Prueba
**Archivo**: `docs/testing/test-campos-impuestos-requeridos.php`

Este script valida:
1. ✅ Asignación incondicional de `valorITBMS`
2. ✅ Asignación incondicional de `valorISC`
3. ✅ Asignación incondicional de `totalITBMS`
4. ✅ Asignación incondicional de `totalISC`
5. ✅ Asignación incondicional de `totalMontoGravado`
6. ✅ Inclusión en array `keepZeroFields`

### Ejecución de Prueba
```bash
docker exec -it docucenter_laravel.test php docs/testing/test-campos-impuestos-requeridos.php
```

## Impacto de la Corrección

### Antes
```json
{
  "listaItems": [
    {
      "descripcionItem": "Producto sin impuestos",
      "cantidadItem": "1",
      // valorITBMS y valorISC ausentes cuando eran cero
    }
  ],
  "totales": {
    "totalPrecioNeto": "100.00"
    // totalITBMS y totalISC ausentes cuando eran cero
  }
}
```

### Después
```json
{
  "listaItems": [
    {
      "descripcionItem": "Producto sin impuestos",
      "cantidadItem": "1",
      "valorITBMS": "0.000000",
      "valorISC": "0.000000",
      "tasaITBMS": "0",
      "tasaISC": "0"
    }
  },
  "totales": {
    "totalPrecioNeto": "100.00",
    "totalITBMS": "0.00",
    "totalISC": "0.00",
    "totalMontoGravado": "0.00"
  }
}
```

## Consideraciones Técnicas

### Formato de Valores
- **Valores por ítem**: Formato `"0.000000"` (6 decimales)
- **Valores totales**: Formato `"0.00"` (2 decimales)
- **Tasas**: Formato `"0"` (entero)

### Compatibilidad
Esta corrección mantiene compatibilidad con:
- ✅ Facturas con impuestos normales
- ✅ Facturas sin impuestos (valores cero)
- ✅ Facturas de exportación
- ✅ Diferentes tipos de documentos

### Performance
La corrección no impacta significativamente el performance ya que:
- Elimina lógica condicional (menos procesamiento)
- Simplifica el proceso de construcción del JSON
- Reduce la complejidad del filtrado

## Resolución de Errores Relacionados

Esta corrección resuelve específicamente:

1. **Error 201**: "Error al procesar solicitud" (cuando faltaban campos requeridos)
2. **Errores de validación**: "El campo valorITBMS es requerido"
3. **Errores de validación**: "El campo valorISC es requerido"
4. **Errores de validación**: "El campo totalITBMS es requerido"
5. **Errores de validación**: "El campo totalISC es requerido"
6. **Errores de validación**: "El campo totalMontoGravado es requerido"

## Status

- ✅ **Implementado**: Corrección completa de lógica de impuestos
- ✅ **Validado**: Test de validación exitoso
- ✅ **Documentado**: Documentación técnica completa
- 🔄 **Pendiente**: Testing con PAC real en producción

## Próximos Pasos

1. **Testing Producción**: Probar con facturas reales en el PAC
2. **Monitoreo**: Verificar resolución completa del error 201
3. **Validación**: Confirmar aceptación de facturas sin impuestos
4. **Documentación**: Actualizar guías de troubleshooting

---

**Fecha**: 2024
**Autor**: AI Assistant
**Revisión**: Corrección crítica de campos requeridos PAC TheFactoryHKA
