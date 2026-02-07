# Soporte de Múltiples Tasas de Impuesto en QuickBooks - Implementación

## Problema Identificado

El servicio `QuickBooksOnlineService` **NO soportaba correctamente facturas con múltiples tasas de impuesto** (ej: ITBMS 7% e ITBMS 10% en la misma factura).

### Síntoma
Cuando QuickBooks enviaba una factura con:
- Línea 1: ITBMS 10%
- Líneas 2-4: ITBMS 7%

El sistema calculaba incorrectamente los impuestos por línea usando distribución proporcional, resultando en montos erróneos para cada línea individual.

### Causa Raíz
El código solo leía `TxnTaxDetail.TaxLine[0]` y distribuía el impuesto total proporcionalmente, sin considerar que cada línea podía tener una tasa diferente según su `TaxCodeRef`.

## Solución Implementada

### Flujo de Integración con ACI Cloud

```
QuickBooks Invoice
    ↓
TaxCodeRef por línea (ej: "15", "13")
    ↓
API ACI Cloud: GET /aciv2/quickbooks/taxcode/{taxCodeRef}/details
    ↓
Response.data.Name extraído (ej: "ITBMS10", "ITBMS7")
    ↓
Extraer número del nombre: ITBMS10 → 10%, ITBMS7 → 7%
    ↓
Mapeo TaxCodeRef → TaxPercent
    ↓
TaxPercent aplicado a cada línea
```

### Nuevos Métodos Agregados

#### 1. `getTaxCodeDetailsFromACICloud()`
Consulta la API de ACI Cloud para obtener detalles de un código de impuesto:

```php
protected function getTaxCodeDetailsFromACICloud(string $taxCodeRef, string $organizationId): ?array
```

**Endpoint consultado:**
```
GET https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/quickbooks/taxcode/{taxCodeRef}/details?organization_id={orgId}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "data": {
        "Name": "ITBMS10",
        "Description": "ITBMS10%",
        "Active": true,
        "TaxGroup": true,
        "SalesTaxRateList": {
            "TaxRateDetail": [{
                "TaxRateRef": {
                    "value": "20",
                    "name": "ITBMS10 (Ventas)"
                }
            }]
        }
    }
}
```

**Valores posibles del campo `Name`:**
- `ITBMS0` → 0% (exento)
- `ITBMS7` → 7%
- `ITBMS10` → 10%
- `ITBMS15` → 15%

#### 2. `buildTaxCodeMapping()`
Construye un mapeo de TaxCodeRef → TaxPercent extrayendo el porcentaje del nombre:

```php
protected function buildTaxCodeMapping(array $invoiceData, string $aciCloudOrgId): array
```

**Proceso:**
1. Extrae todos los `TaxCodeRef` únicos de las líneas
2. Para cada uno, consulta ACI Cloud
3. Obtiene el campo `Name` del response (ej: "ITBMS7")
4. Extrae el número usando regex: `/ITBMS(\d+)/`
5. Ese número es el porcentaje de impuesto

**Output:**
```php
[
    "15" => [
        "percent" => 10,
        "tax_code_name" => "ITBMS10"
    ],
    "13" => [
        "percent" => 7,
        "tax_code_name" => "ITBMS7"
    ]
]
```

#### 3. `calculateLineTax()`
Método unificado para calcular impuesto por línea con prioridades:

```php
protected function calculateLineTax(
    float $lineAmount,
    ?string $taxCodeRef,
    array $taxCodeMapping,
    array $salesItemDetail,
    float $fullAmountTax,
    float $subtotal
): array
```

**Orden de Prioridad:**
1. ✅ `TaxCode.rateValue` (si QB lo envía directamente)
2. ✅ `TaxAmount` (si está presente)
3. ✅ **`TaxCodeMapping`** (NUEVO - usando ACI Cloud)
4. ✅ Distribución proporcional (fallback)

**Output:**
```php
[
    'tax' => 20.85,
    'method' => 'tax_code_mapping',
    'percent' => 10
]
```

### Modificaciones al Método `storeOrder()`

#### Antes (líneas 956-974)
```php
// Solo usaba TaxLine[0]
$taxPercent = 0;
if ($taxDetail !== null && isset($taxDetail['TaxLine'][0]['TaxLineDetail'])) {
    $taxLineDetail = $taxDetail['TaxLine'][0]['TaxLineDetail'];
    $taxPercent = (float) ($taxLineDetail['TaxPercent'] ?? 0);
}

// Distribución proporcional (única opción)
$tax = $fullAmountTax * ($lineAmount / $subtotal);
```

#### Después (líneas 953-1000)
```php
// Construir mapeo usando ACI Cloud
$aciCloudOrgId = // obtener desde connection o organization
$taxCodeMapping = $this->buildTaxCodeMapping($invoiceData, $aciCloudOrgId);

// Usar nuevo método unificado
$taxResult = $this->calculateLineTax(
    $lineAmount,
    $taxCodeRef,
    $taxCodeMapping,
    $salesItemDetail,
    $fullAmountTax,
    $subtotal
);
```

## Configuración Requerida

### 1. ID de Organización de ACI Cloud

El sistema necesita el `aci_cloud_organization_id` para consultar la API. Se puede configurar en:

**Opción A: Settings de Connection**
```php
$connection->settings = [
    'aci_cloud_organization_id' => 'iXbnm1E7Z1TkX74mccUi',
    // ... otros settings
];
```

**Opción B: Campo en Organization** (recomendado)
```php
$organization->aci_cloud_organization_id = 'iXbnm1E7Z1TkX74mccUi';
```

### 2. Fallback Sin Configuración

Si no se configura `aci_cloud_organization_id`:
- Se registra warning en logs
- Se usa distribución proporcional (comportamiento anterior)
- Funciona para facturas con una sola tasa

## Testing

### Script de Verificación
```bash
./scripts/test-qb-multi-tax-implementation.sh
```

### Script de Análisis
```bash
docker exec -it docucenter_laravel.test php docs/testing/test-qb-multi-tax-rates.php
```

### Logs a Verificar

**Mapeo construido:**
```
QuickBooks Tax Code Mapping Built
  - mapping_count: 2
  - mapping: {"15": {...}, "13": {...}}
```

**Cálculo por línea:**
```
QuickBooks Line Tax Calculation
  - calculation_method: tax_code_mapping
  - tax_percent_applied: 10
  - has_mapping: true
```

## Casos de Uso

### Caso 1: Múltiples Tasas (NUEVO ✅)
```json
{
    "Line": [
        {"TaxCodeRef": {"value": "15"}}, // 10%
        {"TaxCodeRef": {"value": "13"}}, // 7%
        {"TaxCodeRef": {"value": "13"}}, // 7%
    ],
    "TxnTaxDetail": {
        "TaxLine": [
            {"TaxPercent": 10, "TaxRateRef": {"value": "20"}},
            {"TaxPercent": 7, "TaxRateRef": {"value": "18"}}
        ]
    }
}
```
**Resultado:** Cada línea recibe su tasa correcta (10% o 7%)

### Caso 2: Una Sola Tasa (Compatible ✅)
```json
{
    "Line": [
        {"TaxCodeRef": {"value": "13"}}, // 7%
        {"TaxCodeRef": {"value": "13"}}, // 7%
    ],
    "TxnTaxDetail": {
        "TaxLine": [
            {"TaxPercent": 7, "TaxRateRef": {"value": "18"}}
        ]
    }
}
```
**Resultado:** Funciona con mapeo o distribución proporcional

### Caso 3: Sin ACI Cloud Config (Fallback ✅)
```json
// Sin aci_cloud_organization_id configurado
```
**Resultado:** Usa distribución proporcional (comportamiento anterior)

## Beneficios

1. ✅ **Precisión**: Impuestos correctos por línea con múltiples tasas
2. ✅ **Compatibilidad**: Mantiene funcionamiento anterior si no hay config
3. ✅ **Escalabilidad**: Soporta N tasas diferentes en una factura
4. ✅ **Auditoría**: Logs detallados del proceso de mapeo
5. ✅ **Fallbacks**: Múltiples niveles de respaldo para cálculo

## Impacto en Código Existente

### Sin Cambios en:
- ✅ Modelos (`SalesHeaderImp`, `SalesDetailImp`)
- ✅ Base de datos (no requiere migraciones)
- ✅ API endpoints
- ✅ Validaciones (`CreateSaleQuickBooksRequest`)

### Modificaciones:
- ✅ `QuickBooksOnlineService::storeOrder()` - Lógica de cálculo mejorada
- ✅ Nuevos métodos helper agregados

### Retrocompatibilidad:
- ✅ **100% compatible** con facturas existentes
- ✅ Funciona sin configuración adicional (con fallback)
- ✅ No rompe funcionalidad actual

## Monitoreo

### Métricas a Observar
1. Conteo de facturas usando `tax_code_mapping` vs `proportional_distribution`
2. Errores en llamadas a ACI Cloud API
3. Diferencias entre impuesto calculado y total QB

### Alertas Recomendadas
- API ACI Cloud no disponible (usar cache/fallback)
- TaxCodeRef sin mapeo encontrado
- Diferencias > $0.10 entre calculado y QB

## Próximos Pasos

1. ✅ Configurar `aci_cloud_organization_id` en producción
2. ⏳ Monitorear logs de primeras facturas procesadas
3. ⏳ Validar con casos reales de clientes
4. ⏳ Considerar cache de mapeos TaxCode para performance
5. ⏳ Documentar proceso de configuración para nuevos clientes

## Archivos Modificados

```
app/Services/QuickBooksOnlineService.php
  + getTaxCodeDetailsFromACICloud() (líneas 1485-1521)
  + buildTaxCodeMapping() (líneas 1523-1585)
  + calculateLineTax() (líneas 1587-1641)
  ~ storeOrder() - Lógica de impuestos mejorada (líneas 953-1050)

docs/testing/test-qb-multi-tax-rates.php (NUEVO)
  - Script de análisis y verificación

scripts/test-qb-multi-tax-implementation.sh (NUEVO)
  - Script de validación de implementación
```

## Referencias

- API ACI Cloud: `https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2`
- Endpoint TaxCode: `/quickbooks/taxcode/{id}/details`
- QuickBooks API: `TxnTaxDetail` structure documentation
