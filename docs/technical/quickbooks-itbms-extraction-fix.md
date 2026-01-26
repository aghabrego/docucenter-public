# Corrección: Extracción de ITBMS desde QuickBooks Online

**Fecha:** 2025-01-10  
**Tipo:** Fix / Enhancement  
**Componente:** `QuickBooksOnlineService`  
**Severidad:** Alta (Cálculos fiscales incorrectos)

## Problema Identificado

El sistema **NO estaba extrayendo correctamente el ITBMS** de las facturas de QuickBooks Online. La implementación anterior buscaba el impuesto en campos que no existen en la estructura de datos de QB:

### Campos Buscados (INCORRECTOS):
```php
// NO EXISTEN en el objeto QB
$salesItemDetail['TaxAmount']          // No existe
$salesItemDetail['TaxCode']['rateValue']  // TaxCode no es un objeto completo
$salesItemDetail['TotalWithTax']       // No existe
```

### Datos Reales Disponibles (CORRECTOS):
```json
{
  "TxnTaxDetail": {
    "TotalTax": 148.47,           // Impuesto total de la factura
    "TaxLine": [{
      "Amount": 148.47,
      "TaxLineDetail": {
        "TaxRateRef": {"value": "23"},
        "TaxPercent": 7,          // Porcentaje real de ITBMS
        "NetAmountTaxable": 2121   // Base imponible
      }
    }]
  },
  "Line": [{
    "SalesItemLineDetail": {
      "TaxCodeRef": {"value": "16"}  // Solo código, no porcentaje
    }
  }]
}
```

## Solución Implementada

### 1. Extracción de Datos Fiscales Globales

Agregado antes del procesamiento de líneas:

```php
// Extraer información de impuestos del TxnTaxDetail para distribución proporcional
$taxPercent = 0;
$netAmountTaxable = 0;
if (isset($invoiceData['TxnTaxDetail']['TaxLine'][0]['TaxLineDetail'])) {
    $taxDetail = $invoiceData['TxnTaxDetail']['TaxLine'][0]['TaxLineDetail'];
    $taxPercent = (float) ($taxDetail['TaxPercent'] ?? 0);
    $netAmountTaxable = (float) ($taxDetail['NetAmountTaxable'] ?? 0);
}

Log::info('QuickBooks Tax Distribution Info', [
    'invoice_number' => $number,
    'total_tax' => $fullAmountTax,
    'tax_percent' => $taxPercent,
    'net_amount_taxable' => $netAmountTaxable,
    'subtotal' => $subtotal
]);
```

### 2. Distribución Proporcional del ITBMS

Reemplazado el método de cálculo de impuesto por línea:

```php
// MÉTODO ACTUALIZADO: Distribución proporcional del impuesto total
if ($fullAmountTax > 0 && $subtotal > 0) {
    // Calcular la proporción que representa esta línea del subtotal
    $lineProportionOfSubtotal = $lineAmount / $subtotal;
    
    // Aplicar esa proporción al impuesto total
    $tax = $fullAmountTax * $lineProportionOfSubtotal;
    
    // Validación cruzada con porcentaje directo
    if ($taxPercent > 0) {
        $taxByPercent = $lineAmount * ($taxPercent / 100);
        $difference = abs($tax - $taxByPercent);
        
        if ($difference > ($tax * 0.01)) {
            Log::warning('QuickBooks Tax Calculation Mismatch', [
                'line_id' => $line['Id'] ?? $index,
                'proportional_tax' => $tax,
                'percent_tax' => $taxByPercent,
                'difference' => $difference,
                'using' => 'proportional'
            ]);
        }
    }
}

$netLine = $lineAmount + $tax;
```

### 3. Logging Mejorado

```php
Log::info('QuickBooks Line Tax Calculation', [
    'line_id' => $line['Id'] ?? $index,
    'unit_price' => $unitPrice,
    'qty' => $qty,
    'line_amount' => $lineAmount,
    'tax_calculated' => $tax,
    'net_line' => $netLine,
    'tax_percent_used' => $taxPercent,
    'calculation_method' => $fullAmountTax > 0 ? 'proportional_distribution' : 'fallback'
]);
```

## Comando de Testing

Creado comando Artisan para validar la extracción:

```bash
# Usando invoice de prueba integrado
php artisan qb:test-itbms-extraction

# Usando archivo JSON personalizado
php artisan qb:test-itbms-extraction --invoice-json=/path/to/invoice.json

# Con Docker
docker exec -it docucenter_laravel.test php artisan qb:test-itbms-extraction
```

### Output del Comando:

```
=== QuickBooks ITBMS Extraction Test ===

RESUMEN DEL INVOICE:
+-------------+-----------+
| Campo       | Valor     |
+-------------+-----------+
| ID          | 10121     |
| DocNumber   | 1069      |
| Subtotal    | $2,121.00 |
| ITBMS Total | $148.47   |
| Total       | $2,269.47 |
+-------------+-----------+

INFORMACIÓN DE IMPUESTOS:
+--------------------+-----------+
| Campo              | Valor     |
+--------------------+-----------+
| Tax Percent        | 7%        |
| Net Amount Taxable | $2,121.00 |
| TaxRateRef         | 23        |
+--------------------+-----------+

DETALLE DE LÍNEAS:
+----+-------------------------------+-------+--------------+-----------+---------+-----------+---------+
| ID | Descripción                   | Cant. | Precio Unit. | Subtotal  | ITBMS   | Total     | TaxCode |
+----+-------------------------------+-------+--------------+-----------+---------+-----------+---------+
| 1  | ACONDICIONAMIENTO PARA CAM... | 1     | $2,069.00    | $2,069.00 | $144.83 | $2,213.83 | 16      |
| 2  | CAMBIO DE 18 CHAPAS ABLOY     | 1     | $36.00       | $36.00    | $2.52   | $38.52    | 16      |
| 3  | MOVIMIENTO DE TIERRA POR CO...| 1     | $16.00       | $16.00    | $1.12   | $17.12    | 16      |
+----+-------------------------------+-------+--------------+-----------+---------+-----------+---------+

VALIDACIÓN DE CÁLCULOS:
+-------------+----------+-----------+----------+
| Validación  | Esperado | Calculado | Status   |
+-------------+----------+-----------+----------+
| ITBMS Total | $148.47  | $148.47   | MATCH |
| Diferencia  | -        | $0.00     | OK    |
+-------------+----------+-----------+----------+

Extracción de ITBMS CORRECTA
Distribución proporcional VALIDADA
```

## Validación Matemática

### Invoice de Prueba (ID: 10121)

**Línea 1:**
- Subtotal: $2,069.00
- Proporción: $2,069 / $2,121 = 0.975
- ITBMS: $148.47 × 0.975 = $144.83 
- Total: $2,069.00 + $144.83 = $2,213.83 

**Línea 2:**
- Subtotal: $36.00
- Proporción: $36 / $2,121 = 0.017
- ITBMS: $148.47 × 0.017 = $2.52 
- Total: $36.00 + $2.52 = $38.52 

**Línea 3:**
- Subtotal: $16.00
- Proporción: $16 / $2,121 = 0.008
- ITBMS: $148.47 × 0.008 = $1.12 
- Total: $16.00 + $1.12 = $17.12 

**Totales:**
- Subtotal Total: $2,069 + $36 + $16 = $2,121.00 
- ITBMS Total: $144.83 + $2.52 + $1.12 = $148.47 
- Gran Total: $2,121.00 + $148.47 = $2,269.47 

## Ventajas del Método Proporcional

### Ventajas:

1. **Precisión**: Usa el impuesto real calculado por QuickBooks
2. **Confiabilidad**: No depende de códigos internos de QB (TaxCodeRef)
3. **Distribución exacta**: La suma de impuestos por línea = impuesto total
4. **Validación cruzada**: Compara con porcentaje directo para detectar anomalías
5. **Logging detallado**: Facilita debugging de discrepancias

### Consideraciones:

1. **Redondeo**: Puede haber diferencias de centavos por redondeo (se valida con tolerancia de 1%)
2. **Facturas mixtas**: Si hay líneas gravadas y exentas, el método distribuye proporcionalmente
3. **Múltiples tasas**: Si QB usa diferentes tasas por línea (raro), el método promedia

## Impacto en Cumplimiento Fiscal

### Antes (INCORRECTO):
```
ITBMS no se extraía correctamente
Facturas se generaban sin impuesto o con cálculo erróneo
Riesgo de incumplimiento fiscal con DGI Panamá
Discrepancias en reportes de impuestos
```

### Después (CORRECTO):
```
ITBMS se extrae de TxnTaxDetail.TotalTax
Porcentaje real (7%) disponible en logs
Distribución proporcional exacta por línea
Cumplimiento fiscal con normativas panameñas
Trazabilidad completa en logs
```

## Archivos Modificados

### `app/Services/QuickBooksOnlineService.php`
- **Líneas ~842-858**: Extracción de TxnTaxDetail
- **Líneas ~890-930**: Método de distribución proporcional
- **Cambios**: +35 líneas, -28 líneas

### `app/Console/Commands/TestQuickBooksItbmsExtraction.php`
- **Nuevo archivo**: Comando de testing completo
- **Líneas**: 320 líneas
- **Funcionalidad**: Validación automática de extracción de ITBMS

## Testing Recomendado

### 1. Testing Manual:
```bash
# Ejecutar comando de validación
docker exec -it docucenter_laravel.test php artisan qb:test-itbms-extraction

# Verificar logs de Laravel
tail -f storage/logs/laravel.log | grep "QuickBooks"
```

### 2. Testing con Datos Reales:
```bash
# Exportar invoice de QB a JSON
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.quickbooks.com/v3/company/REALM_ID/invoice/10121" \
  > invoice-10121.json

# Testear con el JSON exportado
php artisan qb:test-itbms-extraction --invoice-json=invoice-10121.json
```

### 3. Testing en Producción:
1. Activar logs detallados temporalmente
2. Procesar 5-10 facturas de prueba
3. Verificar logs: `QuickBooks Tax Distribution Info`
4. Comparar con facturas emitidas en QuickBooks
5. Validar reportes de impuestos en DGI

## Rollback Plan

Si se detectan problemas:

```php
// Revertir a método anterior (NO RECOMENDADO):
// git revert <commit_hash>

// O ajustar tolerancia de validación:
if ($difference > ($tax * 0.05)) { // 5% en lugar de 1%
    Log::warning('...');
}
```

## Próximos Pasos

1. **COMPLETADO**: Implementar extracción de TxnTaxDetail
2. **COMPLETADO**: Distribución proporcional por línea
3. **COMPLETADO**: Comando de testing
4.  **PENDIENTE**: Validar con facturas reales en sandbox QB
5.  **PENDIENTE**: Desplegar a producción con monitoreo
6.  **PENDIENTE**: Actualizar documentación de integración QB
7.  **PENDIENTE**: Training al equipo de soporte

## Referencias

- **QuickBooks API Docs**: https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice
- **Normativa DGI Panamá**: Resolución No. 201-2619 sobre Facturación Electrónica
- **ITBMS Panamá**: Tasa estándar 7%

## Contacto

Para reportar problemas relacionados con esta corrección:
- **Logs**: Revisar `QuickBooks Tax Distribution Info` y `QuickBooks Line Tax Calculation`
- **Testing**: Ejecutar `php artisan qb:test-itbms-extraction`
- **Support**: Incluir DocNumber e Id del invoice afectado
