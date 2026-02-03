# Sistema de Tax Code en QuickBooks

**Fecha:** 2026-02-03  
**Tipo:** Documentación Técnica  
**Estado:** Completo

## Introducción

Esta documentación explica en detalle cómo funciona el **sistema de extracción de Tax Code (códigos de impuesto) en QuickBooks**. QuickBooks utiliza un sistema híbrido de 4 niveles de prioridad para procesar impuestos, ya que envía datos en dos formatos diferentes dependiendo de la configuración.

---

## Campo Origin (Fuente)

### Identificación Automática

Todas las ventas provenientes de QuickBooks se marcan automáticamente con:

```php
'origin' => 'quickbooks'
```

### Propósito del Campo Origin

1. **Tracking de origen**: Identificar que la venta proviene de QuickBooks
2. **Prevención de loops**: Evitar que las ventas se re-envíen infinitamente a QB
3. **Auditoría**: Facilitar rastreo y debugging
4. **Filtrado**: Permitir consultas específicas

**Importante:** Este campo se asigna automáticamente por el sistema.

---

## Formatos de QuickBooks

QuickBooks envía datos de impuestos en **dos formatos diferentes**:

### Formato 1: TaxCode Completo (CON rateValue)

Cuando QuickBooks envía el objeto completo con la tasa de impuesto:

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
        "Qty": 1,
        "UnitPrice": 1400.00
      }
    }
  ]
}
```

**Características:**
- Incluye `rateValue` con el porcentaje exacto
- Permite cálculo directo: `Amount × (rateValue / 100)`
- Mayor precisión
- Método preferido cuando está disponible

---

### Formato 2: TaxCodeRef Mínimo (SIN rateValue)

Cuando QuickBooks solo envía la referencia del código:

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
    "TotalTax": 148.47,          // Total de impuestos de toda la factura
    "TaxLine": [
      {
        "Amount": 148.47,
        "DetailType": "TaxLineDetail"
      }
    ]
  }
}
```

**Características:**
- NO incluye `rateValue`
- Solo tiene referencia al código
- Incluye total de impuestos en `TxnTaxDetail`
- Requiere distribución proporcional

---

## Sistema de Prioridades (4 Niveles)

El sistema implementa **4 niveles de prioridad** para extraer impuestos de manera robusta:

### PRIORIDAD 1: TaxCode.rateValue (Método Preferido)

Cuando QuickBooks envía el objeto `TaxCode` completo:

```php
if (isset($salesItemDetail['TaxCode']['rateValue']) && 
    $salesItemDetail['TaxCode']['rateValue'] > 0) {
    
    $taxRate = (float) $salesItemDetail['TaxCode']['rateValue'];
    $tax = $lineAmount * ($taxRate / 100);
    $calculationMethod = 'tax_code_rate_value';
}
```

**Ejemplo:**
- Line Amount: $1,400.00
- Tax Rate: 7%
- Cálculo: $1,400.00 × 7% = $98.00

**Ventajas:**
- Más preciso
- Cálculo directo
- No depende de totales de factura

---

### PRIORIDAD 2: TaxAmount (Campo Directo)

Cuando hay un campo `TaxAmount` en el detalle:

```php
elseif (isset($salesItemDetail['TaxAmount']) && 
        $salesItemDetail['TaxAmount'] > 0) {
    
    $tax = (float) $salesItemDetail['TaxAmount'];
    $calculationMethod = 'tax_amount_field';
}
```

**Ventajas:**
- Valor directo
- Sin cálculos adicionales

---

### PRIORIDAD 3: Flat TaxAmount (Campo Plano)

Cuando el impuesto viene en formato plano:

```php
elseif (isset($line['SalesItemLineDetail.TaxAmount']) && 
        $line['SalesItemLineDetail.TaxAmount'] > 0) {
    
    $tax = (float) $line['SalesItemLineDetail.TaxAmount'];
    $calculationMethod = 'flat_tax_amount';
}
```

---

### PRIORIDAD 4: Distribución Proporcional (Fallback)

Cuando solo hay total de impuestos en la factura:

```php
elseif ($fullAmountTax > 0 && $subtotal > 0) {
    $tax = $fullAmountTax * ($lineAmount / $subtotal);
    $calculationMethod = 'proportional_distribution';
}
```

**Ejemplo:**
- Total Tax: $148.47
- Subtotal: $2,121.00
- Line Amount: $2,069.00
- Cálculo: $148.47 × ($2,069 / $2,121) = $144.83

**Características:**
- Funciona siempre que haya total de impuestos
- Distribución justa según monto de línea
- Puede tener pequeñas diferencias de redondeo

---

## Logging Detallado

El sistema registra el método usado en cada línea:

```php
Log::info('QuickBooks Line Tax Calculation', [
    'calculation_method' => $calculationMethod,
    'tax_code_rate' => $salesItemDetail['TaxCode']['rateValue'] ?? null,
    'tax_calculated' => $tax,
    'line_amount' => $lineAmount,
    'tax_percent_from_txn' => $taxPercent
]);
```

### Ejemplo de Log - Formato Completo:
```json
{
  "calculation_method": "tax_code_rate_value",
  "tax_code_rate": 7,
  "tax_calculated": 98.00,
  "line_amount": 1400.00
}
```

### Ejemplo de Log - Formato Mínimo:
```json
{
  "calculation_method": "proportional_distribution",
  "tax_code_rate": null,
  "tax_calculated": 144.83,
  "line_amount": 2069.00,
  "tax_percent_from_txn": 7
}
```

---

## Ejemplos Prácticos

### Ejemplo 1: Formato Completo (con rateValue)

**Request de QuickBooks:**
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
          "rateValue": 7
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

---

### Ejemplo 2: Formato Mínimo (solo TaxCodeRef)

**Request de QuickBooks:**
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
          "value": "16"
        },
        "Qty": 1
      }
    }
  ],
  "TxnTaxDetail": {
    "TotalTax": 7.00
  }
}
```

**Procesamiento:**
- Método usado: `proportional_distribution`
- Cálculo: ($100 / $100) × $7.00 = $7.00
- `origin` = `'quickbooks'`

---

### Ejemplo 3: Factura con Múltiples Líneas

**Invoice 10121 - Formato Mínimo**

```
DETALLE DE LÍNEAS:
+----+------------------+-------+--------------+-----------+---------+-----------+-------------------+
| ID | Descripción      | Cant. | Precio Unit. | Subtotal  | ITBMS   | Total     | Método            |
+----+------------------+-------+--------------+-----------+---------+-----------+-------------------+
| 1  | ACONDICIONAM...  | 1     | $2,069.00    | $2,069.00 | $144.83 | $2,213.83 | Proportional      |
| 2  | CAMBIO DE 18...  | 1     | $36.00       | $36.00    | $2.52   | $38.52    | Proportional      |
| 3  | MOVIMIENTO D...  | 1     | $16.00       | $16.00    | $1.12   | $17.12    | Proportional      |
+----+------------------+-------+--------------+-----------+---------+-----------+-------------------+

VALIDACIÓN: 
- ITBMS Total Factura: $148.47
- ITBMS Calculado: $144.83 + $2.52 + $1.12 = $148.47 MATCH
```

**Cálculos:**
```
Línea 1: ($2,069 / $2,121) × $148.47 = $144.83
Línea 2: ($36 / $2,121) × $148.47 = $2.52
Línea 3: ($16 / $2,121) × $148.47 = $1.12
Total: $148.47
```

---

## Sistema Anti-Loop

El campo `origin` previene loops infinitos entre QuickBooks y DocuCenter:

```php
// En UpdateIntuitOrdersJob.php
$salesToSync = SalesHeaderImp::where('organization_id', $orgId)
    ->where(function($query) {
        $query->where('origin', 'docucenter')
              ->orWhereNull('origin');
    })
    ->get();
```

**Lógica:**
- Ventas con `origin='docucenter'` → SÍ se envían a QuickBooks
- Ventas con `origin=NULL` → SÍ se envían a QuickBooks (legacy)
- Ventas con `origin='quickbooks'` → NO se re-envían (previene loop)

---

## Consultas SQL

### Filtrar ventas de QuickBooks

```sql
-- Todas las ventas de QuickBooks
SELECT * FROM Sales_Header_Imp 
WHERE origin = 'quickbooks';

-- Ventas de QuickBooks de un mes específico
SELECT * FROM Sales_Header_Imp 
WHERE origin = 'quickbooks' 
  AND DATE_FORMAT(created_at, '%Y-%m') = '2024-02';

-- Contar ventas por origen
SELECT origin, COUNT(*) as total 
FROM Sales_Header_Imp 
GROUP BY origin;

-- Ventas que SÍ se sincronizarían a QB
SELECT * FROM Sales_Header_Imp 
WHERE (origin = 'docucenter' OR origin IS NULL);
```

---

## Comandos de Testing

### Testing de Extracción de ITBMS

```bash
# Testing completo (ambos formatos)
php artisan qb:test-itbms-extraction

# Testing con JSON personalizado
php artisan qb:test-itbms-extraction --invoice-json=/path/to/invoice.json

# Con Docker
docker exec -it docucenter_laravel.test php artisan qb:test-itbms-extraction
```

**Output esperado:**
```
Test 1: Formato mínimo (Proportional) OK
Test 2: Formato completo (TaxCode.rateValue) OK
TODOS LOS TESTS PASARON
```

---

## Logging y Debugging

### Ver métodos de cálculo usados

```bash
# Ver en tiempo real
tail -f storage/logs/laravel.log | grep "calculation_method"

# Ver distribución de métodos
grep "tax_code_rate_value" storage/logs/laravel.log | wc -l       # Formato completo
grep "proportional_distribution" storage/logs/laravel.log | wc -l # Formato mínimo

# Ver logs de sincronización
tail -f storage/logs/laravel.log | grep "CreateSaleQuickBooksJob"

# Filtrar por organización específica
grep "QuickBooks.*org_id: 123" storage/logs/laravel.log
```

---

## Recomendaciones

### Para Implementaciones

1. **Monitorear logs**: Ver qué método de extracción predomina
2. **Preferir formato completo**: Cuando sea posible, configurar QB para enviar `rateValue`
3. **Verificar campo origin**: Prevenir loops de sincronización
4. **Testing periódico**: Ejecutar `qb:test-itbms-extraction` regularmente
5. **Validar totales**: Siempre comparar totales calculados vs totales de factura

### Para Debugging

1. **Revisar logs**: El `calculation_method` indica qué ruta se usó
2. **Verificar estructura**: Confirmar si viene `TaxCode` o solo `TaxCodeRef`
3. **Comparar totales**: Validar que la suma de impuestos coincida
4. **Filtrar por origin**: Asegurar que no haya loops

---

## Comparación de Métodos

| Método | Precisión | Disponibilidad | Complejidad | Recomendado |
|--------|-----------|----------------|-------------|-------------|
| **tax_code_rate_value** | Excelente | Formato completo | Baja | Sí |
| **tax_amount_field** | Excelente | Variable | Baja | Sí |
| **flat_tax_amount** | Excelente | Raro | Baja | Sí |
| **proportional_distribution** | Buena | Siempre (fallback) | Media | Fallback |

---

## Archivos Relacionados

### Código Fuente
- `app/Services/QuickBooksOnlineService.php` - Lógica de extracción
- `app/Jobs/CreateSaleQuickBooksJob.php` - Procesamiento de ventas
- `app/Jobs/Intuit/UpdateIntuitOrdersJob.php` - Sincronización a QB
- `app/Console/Commands/TestQuickBooksItbmsExtraction.php` - Testing

### Documentación
- [QuickBooks ITBMS Hybrid System](../../technical/quickbooks-itbms-hybrid-system.md)
- [QuickBooks ITBMS Extraction Fix](../../technical/quickbooks-itbms-extraction-fix.md)
- [QuickBooks Loop Solution](../../technical/quickbooks-loop-solution.md)
- [Origin Field Implementation](../../technical/origin-field-implementation-summary.md)

---

## Conclusión

El sistema de Tax Code de QuickBooks es **robusto y adaptable**:

- **Sistema Híbrido**: 4 niveles de prioridad  
- **Compatibilidad Total**: Funciona con ambos formatos de QB  
- **Logging Detallado**: Trazabilidad completa del método usado  
- **Prevención de Loops**: Campo `origin` evita re-procesamiento  
- **Testing Completo**: Comando dedicado para validación  

**Estado:** PRODUCCIÓN  
**Validación:** COMPLETA  
**Documentación:** ACTUALIZADA
