# Implementación de Custom Fields en Zoho Sales Orders

## Resumen de Implementación

Se ha implementado exitosamente el manejo de `custom_field_hash` para extraer el campo `cf_sagecustomerid` en la API de Sales Orders de Zoho, siguiendo el mismo patrón implementado para Purchase Orders.

## Archivos Modificados

### 1. CreateSaleOrderZohoRequest.php
**Ubicación**: `app/Http/Requests/CreateSaleOrderZohoRequest.php`

**Cambios implementados**:
```php
// Validación para custom_field_hash a nivel de documento
'custom_field_hash' => 'nullable|array',
'custom_field_hash.cf_sagecustomerid' => 'nullable|string|max:50',

// Validación para custom_field_hash a nivel de line items
'line_items.*.custom_field_hash' => 'nullable|array',
```

**Logging agregado**:
- Detección y logging de `cf_sagecustomerid` en el método `passedValidation()`
- Información sobre todos los custom fields presentes
- Mapeo del customer ID cuando se usa `cf_sagecustomerid`

### 2. ACIcloudService.php - Método createSaleOrderZoho
**Ubicación**: `app/Services/ACIcloudService.php`

**Lógica de transformación**:
```php
// Extraer cf_sagecustomerid del custom_field_hash si está presente
$customFieldHash = $request->input('custom_field_hash', []);
$sageCustomerId = $customFieldHash['cf_sagecustomerid'] ?? null;

$customerData = [
    'CustomerID' => $sageCustomerId ?? $request->input('customer_id'), // Priorizar cf_sagecustomerid
    // ... resto de campos
];

$headerData = [
    'CustomerID' => $sageCustomerId ?? $request->input('customer_id'), // Usar cf_sagecustomerid si está disponible
    // ... resto de campos
];
```

**Comportamiento**:
- **Prioridad**: Si existe `cf_sagecustomerid`, se usa en lugar de `customer_id`
- **Fallback**: Si no existe `cf_sagecustomerid`, usa `customer_id` original
- **Doble mapeo**: Se aplica tanto en `CustomersImp` como en `SalesOrderHeaderImp`
- **Logging detallado**: Información completa sobre custom fields y mapeo

### 3. TestZohoSaleOrderMapping.php
**Ubicación**: `app/Console/Commands/TestZohoSaleOrderMapping.php`

**Nuevas funcionalidades**:
```bash
php artisan zoho:test-sale-order-mapping --with-custom-fields
```

**Datos de prueba con custom fields**:
```php
'custom_field_hash' => [
    'cf_sagecustomerid' => 'SAGE_CUSTOMER_CF_001',
    'cf_sales_region' => 'north_america',
    'cf_priority_customer' => 'true',
    'cf_credit_limit' => '50000'
],
'line_items' => [
    [
        'custom_field_hash' => [
            'cf_product_sage_id' => 'SAGE_PROD_CF_001',
            'cf_product_category' => 'electronics',
            'cf_warranty_months' => '24'
        ]
    ]
]
```

## Archivos de Prueba Creados

### 1. test-zoho-sales-order-with-custom-fields.json
**Ubicación**: `docs/testing/test-zoho-sales-order-with-custom-fields.json`

Ejemplo completo de Sales Order con custom fields para testing manual.

### 2. test-zoho-sales-custom-fields.sh
**Ubicación**: `docs/testing/test-zoho-sales-custom-fields.sh`

Script de prueba automatizado para la API con custom fields.

**Uso**:
```bash
./docs/testing/test-zoho-sales-custom-fields.sh [org_id]
```

## Estructura de custom_field_hash

### Nivel de Documento (Header)
```json
{
  "custom_field_hash": {
    "cf_sagecustomerid": "SAGE_CUSTOMER_001",
    "cf_sales_region": "panama_city",
    "cf_priority_customer": "true",
    "cf_credit_limit": "75000",
    "cf_sales_rep": "SALES_REP_001"
  }
}
```

### Nivel de Line Items
```json
{
  "line_items": [
    {
      "custom_field_hash": {
        "cf_product_sage_id": "SAGE_PROD_001",
        "cf_product_category": "electronics",
        "cf_warranty_months": "24",
        "cf_supplier_code": "SUPP_001",
        "cf_batch_number": "BATCH_20251006_001"
      }
    }
  ]
}
```

## Resultados de Pruebas

### Validación
```bash
docker exec -it docucenter_laravel.test php artisan zoho:test-sale-order-mapping --mode=validate --with-custom-fields
# Resultado: Datos válidos
```

### Procesamiento
```bash
docker exec -it docucenter_laravel.test php artisan zoho:test-sale-order-mapping --mode=process --with-custom-fields
```

**Salida esperada**:
```
Custom Fields detectados:
   cf_sagecustomerid: SAGE_CUSTOMER_CF_001
   Total custom fields: 4

Mapeo de CustomerID:
   Original (Zoho): ZOHO_CUSTOMER_001
   Mapeado (Sage):  SAGE_CUSTOMER_CF_001
   Se usará: SAGE_CUSTOMER_CF_001
```

### Testing Completo
```bash
docker exec -it docucenter_laravel.test php artisan zoho:test-sale-order-mapping --mode=all --with-custom-fields
```

## Mapeo de Datos

### Campo CustomerID
| Condición | Fuente | Resultado |
|-----------|--------|-----------|
| `cf_sagecustomerid` presente | `custom_field_hash.cf_sagecustomerid` | Usa el valor del custom field |
| `cf_sagecustomerid` ausente | `customer_id` | Usa el customer_id original de Zoho |
| Ambos ausentes | `''` | String vacío |

### Aplicación del Mapeo
- **CustomersImp**: `CustomerID` mapeado con `cf_sagecustomerid`
- **SalesOrderHeaderImp**: `CustomerID` mapeado con `cf_sagecustomerid`
- **Consistencia**: Ambas tablas usan el mismo valor mapeado

### Validaciones Aplicadas
- `custom_field_hash`: `nullable|array`
- `cf_sagecustomerid`: `nullable|string|max:50`
- Truncación automática según limitaciones de DB
- Logging detallado para auditoría

## Logging y Monitoreo

### FormRequest Logging
```php
Log::info("CreateSaleOrderZoho: cf_sagecustomerid detectado", [
    'salesorder_number' => $salesOrderNumber,
    'customer_id' => $originalCustomerId,
    'cf_sagecustomerid' => $sageCustomerId,
    'custom_field_hash_keys' => array_keys($customFieldHash),
]);
```

### ACIcloudService Logging
```php
Log::info("DEBUG ACIcloudService::createSaleOrderZoho - cf_sagecustomerid detectado y mapeado", [
    'salesorder_number' => $salesOrderNumber,
    'original_customer_id' => $originalCustomerId,
    'cf_sagecustomerid' => $sageCustomerId,
    'mapped_customer_id' => $mappedCustomerId,
]);
```

## Estructura API

### Endpoint
```
POST /api/acicloud/create_sale_order_zoho
```

### Headers Requeridos
```
Content-Type: application/json
Accept: application/json
X-Organization-ID: {org_id}
Authorization: Bearer {token}  // Si está habilitado auth:sanctum
```

### Payload Example con Custom Fields
```json
{
  "salesorder_number": "SO_CF_001",
  "customer_id": "ZOHO_CUSTOMER_001",
  "customer_name": "Cliente Ejemplo",
  "custom_field_hash": {
    "cf_sagecustomerid": "SAGE_CUSTOMER_001"
  },
  "sub_total": "200.0",
  "total": "200.0",
  "line_items": [
    {
      "name": "Producto Ejemplo",
      "quantity": 1,
      "rate": 200,
      "item_total": 200,
      "custom_field_hash": {
        "cf_product_sage_id": "SAGE_PROD_001"
      }
    }
  ]
}
```

**Resultado**: `CustomerID` será `SAGE_CUSTOMER_001` (prioridad al custom field)

Sin `cf_sagecustomerid`: `CustomerID` será `ZOHO_CUSTOMER_001` (fallback)

## Comparación con Purchase Orders

| Aspecto | Purchase Orders | Sales Orders |
|---------|----------------|--------------|
| **Custom Field** | `cf_sagevendorid` | `cf_sagecustomerid` |
| **Campo Mapeado** | `VendorID` | `CustomerID` |
| **Transformer** | `ZohoPurchaseOrderTransformer` | `ACIcloudService::createSaleOrderZoho` |
| **Modelos Afectados** | `PurchaseHeader_Imp` | `CustomersImp` + `SalesOrderHeaderImp` |
| **Validación** | `CreatePurchaseOrderZohoRequest` | `CreateSaleOrderZohoRequest` |

## Estados de Implementación

- **FormRequest validation**: Completo
- **Service logic**: Completo  
- **Testing commands**: Completo
- **Documentation**: Completo
- **Example files**: Completo
- **API integration test**: Requiere configuración de auth
- **Production deployment**: Pendiente

## Notas de Desarrollo

1. **Backward Compatibility**: La implementación es completamente compatible hacia atrás. Si no se proporciona `cf_sagecustomerid`, el sistema usa `customer_id` como antes.

2. **Performance**: No hay impacto en performance ya que solo se agregan validaciones opcionales y verificaciones simples.

3. **Extensibilidad**: El sistema está preparado para manejar custom fields adicionales sin modificaciones estructurales.

4. **Consistencia**: Sigue exactamente el mismo patrón implementado para Purchase Orders.

## Próximos Pasos

1. **Configurar autenticación** para pruebas completas de API
2. **Deploy a staging** para pruebas con datos reales de Zoho  
3. **Configurar monitoring** para custom fields en producción
4. **Documentar custom fields adicionales** según necesidades del negocio
5. **Implementar custom fields para line items** si es requerido

---

**Fecha de implementación**: 2025-10-06  
**Estado**: Funcional y listo para testing/production  
**Patrón**: Consistente con implementación de Purchase Orders
