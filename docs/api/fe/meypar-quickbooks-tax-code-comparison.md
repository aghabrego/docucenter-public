# Comparación: Manejo de Tax Code - MEYPAR vs QuickBooks

**Fecha:** 2026-02-03  
**Tipo:** Documentación Técnica  
**Estado:** Completo

## Introducción

Esta documentación compara cómo se manejan los códigos de impuesto (Tax Code) en las integraciones de MEYPAR y QuickBooks, y explica las diferencias en el origen (campo `origin`) de las ventas.

---

## Campo Origin (Fuente)

### Identificación Automática de Origen

Todas las ventas provenientes de sistemas externos se marcan automáticamente con el campo `origin` para identificar su procedencia.

| Sistema | Valor Origin | Tabla | Campo |
|---------|--------------|-------|-------|
| MEYPAR | `'meypar'` | `Sales_Header_Imp` | `origin` |
| QuickBooks | `'quickbooks'` | `Sales_Header_Imp` | `origin` |
| Shopify | `'shopify'` | `Sales_Header_Imp` | `origin` |
| Lightspeed | `'lightspeed'` | `Sales_Header_Imp` | `origin` |
| ACI Cloud | `'acicloud'` | `Sales_Header_Imp` | `origin` |
| DocuCenter | `'docucenter'` | `Sales_Header_Imp` | `origin` (default) |

### Propósito del Campo Origin

1. **Tracking de origen**: Identificar de qué sistema proviene cada venta
2. **Prevención de loops**: Evitar re-procesamiento de ventas
3. **Auditoría**: Facilitar rastreo y debugging
4. **Filtrado**: Permitir consultas específicas por origen

**Importante:** Este campo se asigna automáticamente por el sistema y **NO** requiere ser especificado en el request.

---

## Manejo de Tax Code / Impuestos

### MEYPAR: Sistema Directo

MEYPAR envía los códigos de impuesto y montos de manera **directa y explícita** en la estructura de datos.

#### Estructura de Impuestos MEYPAR

```json
{
  "detalleMedioPagoList": [
    {
      "codigoMedioPago": 2,        // Código del medio de pago
      "importeMedioPago": 15.75    // Monto con impuesto incluido
    }
  ],
  "detalleFacturaList": [
    {
      "objectName": "WADetalleFactura",
      "cantidad": 1,
      "descripcion": "Estacionamiento 3 horas",
      "precioUnitario": 15.75,
      "precioTotalSinDescuento": 15.75,  // Precio sin descuento
      "precioTotalFinalDetalle": 15.75    // Precio final (incluye impuestos)
    }
  ]
}
```

#### Características MEYPAR

- ✅ **Montos finales directos**: MEYPAR calcula los impuestos en su sistema
- ✅ **No requiere extracción**: Los valores ya vienen calculados
- ✅ **Precisión garantizada**: Los montos son exactos desde el origen
- ✅ **Sin ambigüedad**: Estructura clara y predefinida

---

### QuickBooks: Sistema Híbrido de Extracción

QuickBooks envía datos de impuestos en **dos formatos diferentes**, requiriendo un sistema híbrido de extracción.

#### Formato 1: TaxCode Completo (CON rateValue)

```json
{
  "Line": [
    {
      "DetailType": "SalesItemLineDetail",
      "Amount": 1400.00,
      "SalesItemLineDetail": {
        "ItemRef": {
          "value": "5",
          "name": "Servicio de Marketing"
        },
        "TaxCode": {
          "id": "16",
          "name": "ITBMS7",
          "description": "ITBMS 7%",
          "rateValue": 7           // ← Valor directo del porcentaje
        },
        "Qty": 1
      }
    }
  ]
}
```

#### Formato 2: TaxCodeRef Mínimo (SIN rateValue)

```json
{
  "Line": [
    {
      "DetailType": "SalesItemLineDetail",
      "Amount": 2069.00,
      "SalesItemLineDetail": {
        "ItemRef": {
          "value": "8",
          "name": "Servicio de Estacionamiento"
        },
        "TaxCodeRef": {
          "value": "16"           // ← Solo código, NO porcentaje
        },
        "Qty": 1
      }
    }
  ],
  "TxnTaxDetail": {
    "TotalTax": 148.47           // Total de impuestos de toda la factura
  }
}
```

#### Sistema de Prioridades QuickBooks

El sistema implementa **4 niveles de prioridad** para extraer impuestos:

```php
// PRIORIDAD 1: TaxCode.rateValue (cuando QB envía objeto completo)
if (isset($salesItemDetail['TaxCode']['rateValue']) && $salesItemDetail['TaxCode']['rateValue'] > 0) {
    $taxRate = (float) $salesItemDetail['TaxCode']['rateValue'];
    $tax = $lineAmount * ($taxRate / 100);
    $calculationMethod = 'tax_code_rate_value';  // ← Método preferido
}
    
// PRIORIDAD 2: TaxAmount (campo directo)
elseif (isset($salesItemDetail['TaxAmount']) && $salesItemDetail['TaxAmount'] > 0) {
    $tax = (float) $salesItemDetail['TaxAmount'];
    $calculationMethod = 'tax_amount_field';
}
    
// PRIORIDAD 3: Flat TaxAmount (campo plano)
elseif (isset($line['SalesItemLineDetail.TaxAmount']) && ...) {
    $tax = (float) $line['SalesItemLineDetail.TaxAmount'];
    $calculationMethod = 'flat_tax_amount';
}
    
// PRIORIDAD 4 (FALLBACK): Distribución proporcional
elseif ($fullAmountTax > 0 && $subtotal > 0) {
    $tax = $fullAmountTax * ($lineAmount / $subtotal);
    $calculationMethod = 'proportional_distribution';  // ← Fallback robusto
}
```

#### Características QuickBooks

- ⚙️ **Sistema híbrido**: Múltiples métodos de extracción
- ⚙️ **Fallback robusto**: Distribución proporcional si no hay datos directos
- ⚙️ **Logging detallado**: Registra el método usado para cada línea
- ⚙️ **Compatibilidad total**: Funciona con ambos formatos de QB

---

## Comparación Técnica

| Aspecto | MEYPAR | QuickBooks |
|---------|--------|------------|
| **Estructura de datos** | Directa y explícita | Variable (2 formatos) |
| **Cálculo de impuestos** | En origen (MEYPAR) | En DocuCenter (extracción) |
| **Complejidad** | Baja | Alta (sistema híbrido) |
| **Precisión** | Excelente (directa) | Excelente (múltiples métodos) |
| **Métodos de extracción** | 1 (directo) | 4 (prioridades) |
| **Logging** | Básico | Detallado (con método usado) |
| **Campo Origin** | `'meypar'` | `'quickbooks'` |

---

## Ejemplos Comparativos

### Ejemplo 1: Venta Simple

#### MEYPAR Request
```json
{
  "idFacturador": "12345678",
  "ambiente": 1,
  "documento": "32323",
  "codigo": 1,
  "tipoDocumento": 1,
  "prefijo": "A001",
  "numero": 1,
  "medioPago": 1,
  "fechaFactura": "2024-02-06",
  "detalleMedioPagoList": [
    {
      "codigoMedioPago": 1,
      "importeMedioPago": 107.00  // Ya incluye ITBMS
    }
  ],
  "detalleFacturaList": [
    {
      "objectName": "WADetalleFactura",
      "cantidad": 1,
      "descripcion": "Estacionamiento",
      "precioUnitario": 100.00,
      "codigoProducto": 1,
      "precioTotalSinDescuento": 100.00,
      "precioTotalFinalDetalle": 107.00  // Incluye 7% ITBMS
    }
  ]
}
```

**Almacenado en DB:**
- `origin` = `'meypar'`
- Impuesto calculado = $7.00 (implícito: $107 - $100)

---

#### QuickBooks Request (Formato Completo)
```json
{
  "DocNumber": "1001",
  "CustomerRef": { "value": "123" },
  "TxnDate": "2024-02-06",
  "Line": [
    {
      "DetailType": "SalesItemLineDetail",
      "Amount": 100.00,
      "SalesItemLineDetail": {
        "ItemRef": { "value": "5", "name": "Estacionamiento" },
        "TaxCode": {
          "id": "16",
          "name": "ITBMS7",
          "rateValue": 7  // ← Tasa directa
        },
        "Qty": 1,
        "UnitPrice": 100.00
      }
    }
  ]
}
```

**Procesamiento:**
- Método usado: `tax_code_rate_value`
- Cálculo: $100.00 × 7% = $7.00
- `origin` = `'quickbooks'`

**Log generado:**
```json
{
  "calculation_method": "tax_code_rate_value",
  "tax_code_rate": 7,
  "tax_calculated": 7.00,
  "line_amount": 100.00
}
```

---

#### QuickBooks Request (Formato Mínimo)
```json
{
  "DocNumber": "1002",
  "CustomerRef": { "value": "123" },
  "TxnDate": "2024-02-06",
  "Line": [
    {
      "DetailType": "SalesItemLineDetail",
      "Amount": 100.00,
      "SalesItemLineDetail": {
        "ItemRef": { "value": "5", "name": "Estacionamiento" },
        "TaxCodeRef": {
          "value": "16"  // ← Solo código, sin tasa
        },
        "Qty": 1
      }
    }
  ],
  "TxnTaxDetail": {
    "TotalTax": 7.00  // Total de impuestos de toda la factura
  }
}
```

**Procesamiento:**
- Método usado: `proportional_distribution`
- Cálculo: ($100 / $100) × $7.00 = $7.00
- `origin` = `'quickbooks'`

**Log generado:**
```json
{
  "calculation_method": "proportional_distribution",
  "tax_code_rate": null,
  "tax_calculated": 7.00,
  "line_amount": 100.00,
  "tax_percent_from_txn": 7
}
```

---

## Consultas por Origen

### Filtrar ventas por sistema

```sql
-- Ventas de MEYPAR
SELECT * FROM Sales_Header_Imp WHERE origin = 'meypar';

-- Ventas de QuickBooks
SELECT * FROM Sales_Header_Imp WHERE origin = 'quickbooks';

-- Ventas creadas nativamente en DocuCenter
SELECT * FROM Sales_Header_Imp WHERE origin = 'docucenter' OR origin IS NULL;

-- Contar ventas por origen
SELECT origin, COUNT(*) as total 
FROM Sales_Header_Imp 
GROUP BY origin;
```

---

## Integración con Otros Sistemas

### Compatibilidad Multi-Sistema

Las ventas de diferentes orígenes son **compatibles** pero se procesan de manera diferente:

| Escenario | Comportamiento |
|-----------|----------------|
| Venta MEYPAR → QuickBooks | **NO** se sincroniza automáticamente |
| Venta QuickBooks → QuickBooks | **NO** se re-envía (prevención de loops) |
| Venta DocuCenter → QuickBooks | **SÍ** se sincroniza si está configurado |
| Venta Shopify → QuickBooks | **NO** se sincroniza automáticamente |

### Sistema Anti-Loop QuickBooks

El campo `origin` previene loops infinitos:

```php
// En UpdateIntuitOrdersJob.php
$salesToSync = SalesHeaderImp::where('organization_id', $orgId)
    ->where(function($query) {
        $query->where('origin', 'docucenter')
              ->orWhereNull('origin');
    })
    ->get();
```

**Resultado:** Solo las ventas creadas en DocuCenter se envían a QuickBooks.

---

## Logging y Debugging

### MEYPAR

```bash
# Ver ventas de MEYPAR
tail -f storage/logs/laravel.log | grep "Venta MEYPAR creada"

# Filtrar por organización
grep "MEYPAR.*org_id: 123" storage/logs/laravel.log
```

### QuickBooks

```bash
# Ver métodos de cálculo usados
tail -f storage/logs/laravel.log | grep "calculation_method"

# Ver distribución de métodos
grep "tax_code_rate_value" storage/logs/laravel.log | wc -l   # Formato completo
grep "proportional_distribution" storage/logs/laravel.log | wc -l  # Formato mínimo

# Ver logs de sincronización
tail -f storage/logs/laravel.log | grep "CreateSaleQuickBooksJob"
```

---

## Comandos de Testing

### MEYPAR

```bash
# Testing de API MEYPAR
curl -X POST \
  https://tu-dominio.com/api/v1/fe/create_sale_meypar_with_emission \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d @meypar-test-data.json
```

### QuickBooks

```bash
# Testing de extracción de ITBMS
php artisan qb:test-itbms-extraction

# Testing con JSON personalizado
php artisan qb:test-itbms-extraction --invoice-json=/path/to/invoice.json

# Con Docker
docker exec -it docucenter_laravel.test php artisan qb:test-itbms-extraction
```

---

## Recomendaciones

### Para Implementaciones MEYPAR

1. ✅ Siempre enviar montos finales calculados
2. ✅ Incluir `precioTotalSinDescuento` y `precioTotalFinalDetalle`
3. ✅ Verificar que los montos incluyan impuestos correctamente
4. ✅ No preocuparse por el campo `origin` (se asigna automáticamente)

### Para Implementaciones QuickBooks

1. ✅ Monitorear logs para ver qué método de extracción predomina
2. ✅ Preferir formato completo (con `rateValue`) cuando sea posible
3. ✅ Verificar el campo `origin` para prevenir loops
4. ✅ Ejecutar tests periódicos con `qb:test-itbms-extraction`

---

## Documentación Relacionada

### MEYPAR
- [API MEYPAR - Guía Completa](meypar-api-guia-completa.md)
- [Origin Field Implementation](../../technical/origin-field-implementation-summary.md)

### QuickBooks
- [QuickBooks ITBMS Hybrid System](../../technical/quickbooks-itbms-hybrid-system.md)
- [QuickBooks ITBMS Extraction Fix](../../technical/quickbooks-itbms-extraction-fix.md)
- [QuickBooks Loop Solution](../../technical/quickbooks-loop-solution.md)

---

## Conclusión

| Sistema | Fortaleza | Complejidad | Precisión |
|---------|-----------|-------------|-----------|
| **MEYPAR** | Estructura directa y clara | Baja | Excelente |
| **QuickBooks** | Sistema adaptable y robusto | Alta | Excelente |

Ambos sistemas funcionan **correctamente** y están **completamente implementados** con tracking de origen (`origin`) y manejo preciso de impuestos.

**Estado:** PRODUCCIÓN  
**Validación:** COMPLETA  
**Documentación:** ACTUALIZADA

