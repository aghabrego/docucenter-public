# Implementación de Custom Fields en Zoho Purchase Orders

## Resumen de Implementación

Se ha implementado exitosamente el manejo de `custom_field_hash` para extraer el campo `cf_sagevendorid` en la API de Purchase Orders de Zoho.

## Archivos Modificados

### 1. CreatePurchaseOrderZohoRequest.php
**Ubicación**: `app/Http/Requests/CreatePurchaseOrderZohoRequest.php`

**Cambios implementados**:
```php
// Validación para custom_field_hash a nivel de documento
'custom_field_hash' => 'nullable|array',
'custom_field_hash.cf_sagevendorid' => 'nullable|string|max:50',

// Validación para custom_field_hash a nivel de line items
'line_items.*.custom_field_hash' => 'nullable|array',
```

**Logging agregado**:
- Detección y logging de `cf_sagevendorid` en el método `passedValidation()`
- Información sobre todos los custom fields presentes
- Mapeo del vendor ID cuando se usa `cf_sagevendorid`

### 2. ZohoPurchaseOrderTransformer.php
**Ubicación**: `app/Services/Zoho/ZohoPurchaseOrderTransformer.php`

**Lógica de transformación**:
```php
public function transformHeader(array $zohoData): array
{
    // Extraer cf_sagevendorid del custom_field_hash si está presente
    $customFieldHash = $zohoData['custom_field_hash'] ?? [];
    $sageVendorId = $customFieldHash['cf_sagevendorid'] ?? null;

    return [
        // Priorizar cf_sagevendorid sobre vendor_id original
        'VendorID' => $this->truncateText($sageVendorId ?? $zohoData['vendor_id'] ?? '', 20),
        // ... resto de campos
    ];
}
```

**Comportamiento**:
- **Prioridad**: Si existe `cf_sagevendorid`, se usa en lugar de `vendor_id`
- **Fallback**: Si no existe `cf_sagevendorid`, usa `vendor_id` original
- **Validación de longitud**: Trunca a 20 caracteres según spec de DB

### 3. TestZohoPurchaseOrderMapping.php
**Ubicación**: `app/Console/Commands/TestZohoPurchaseOrderMapping.php`

**Nuevas funcionalidades**:
```bash
php artisan zoho:test-purchase-order-mapping --with-custom-fields
```

**Datos de prueba con custom fields**:
```php
'custom_field_hash' => [
    'cf_sagevendorid' => 'SAGE_VENDOR_CF_001',
    'cf_purchase_category' => 'medical_supplies',
    'cf_approval_required' => 'true'
],
'line_items' => [
    [
        'custom_field_hash' => [
            'cf_item_sage_id' => 'SAGE_ITEM_CF_001',
            'cf_requires_prescription' => 'false'
        ]
    ]
]
```

## Archivos de Prueba Creados

### 1. test-zoho-purchase-order-with-custom-fields.json
**Ubicación**: `docs/testing/test-zoho-purchase-order-with-custom-fields.json`

Ejemplo completo de Purchase Order con custom fields para testing manual.

### 2. test-zoho-custom-fields.sh
**Ubicación**: `docs/testing/test-zoho-custom-fields.sh`

Script de prueba automatizado para la API con custom fields.

**Uso**:
```bash
./docs/testing/test-zoho-custom-fields.sh [org_id]
```

## Estructura de custom_field_hash

### Nivel de Documento (Header)
```json
{
  "custom_field_hash": {
    "cf_sagevendorid": "SAGE_VENDOR_001",
    "cf_purchase_category": "medical_supplies",
    "cf_approval_required": "true",
    "cf_department": "procurement"
  }
}
```

### Nivel de Line Items
```json
{
  "line_items": [
    {
      "custom_field_hash": {
        "cf_item_sage_id": "SAGE_ITEM_001",
        "cf_item_category": "medical_device",
        "cf_requires_prescription": "false"
      }
    }
  ]
}
```

## Resultados de Pruebas

### Validación
```bash
docker exec -it docucenter_laravel.test php artisan zoho:test-purchase-order-mapping --mode=validate --with-custom-fields
# Resultado: Datos válidos
```

### Transformación
```bash
docker exec -it docucenter_laravel.test php artisan zoho:test-purchase-order-mapping --mode=transform --with-custom-fields
```

**Salida esperada**:
```
Custom Fields detectados:
   cf_sagevendorid: SAGE_VENDOR_CF_001
   VendorID mapeado: SAGE_VENDOR_CF_001
   Total custom fields: 3
```

## Mapeo de Datos

### Campo VendorID
| Condición | Fuente | Resultado |
|-----------|--------|-----------|
| `cf_sagevendorid` presente | `custom_field_hash.cf_sagevendorid` | Usa el valor del custom field |
| `cf_sagevendorid` ausente | `vendor_id` | Usa el vendor_id original de Zoho |
| Ambos ausentes | `''` | String vacío |

### Validaciones Aplicadas
- `custom_field_hash`: `nullable|array`
- `cf_sagevendorid`: `nullable|string|max:50`
- Truncación a 20 caracteres en transformer (spec DB)
- Logging detallado para auditoría

## Logging y Monitoreo

### FormRequest Logging
```php
Log::info("CreatePurchaseOrderZoho: cf_sagevendorid detectado", [
    'bill_number' => $billNumber,
    'vendor_id' => $originalVendorId,
    'cf_sagevendorid' => $sageVendorId,
    'custom_field_hash_keys' => array_keys($customFieldHash),
]);
```

### Transformer Logging
- Mapeo de VendorID cuando se usa `cf_sagevendorid`
- Información sobre custom fields procesados
- Auditoría de transformación de datos

## Estructura API

### Endpoint
```
POST /api/acicloud/create_purchase_zoho
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
  "bill_number": "TEST_CF_001",
  "vendor_id": "ZOHO_VENDOR_001",
  "vendor_name": "Proveedor Ejemplo",
  "custom_field_hash": {
    "cf_sagevendorid": "SAGE_VENDOR_001"
  },
  "sub_total": "100.0",
  "total": "100.0",
  "line_items": [
    {
      "name": "Producto Ejemplo",
      "quantity": 1,
      "rate": 100,
      "item_total": 100,
      "custom_field_hash": {
        "cf_item_sage_id": "SAGE_ITEM_001"
      }
    }
  ]
}
```

## Estados de Implementación

- **FormRequest validation**: Completo
- **Transformer logic**: Completo  
- **Testing commands**: Completo
- **Documentation**: Completo
- **Example files**: Completo
- **API integration test**: Requiere configuración de auth
- **Production deployment**: Pendiente

## Notas de Desarrollo

1. **Backward Compatibility**: La implementación es completamente compatible hacia atrás. Si no se proporciona `cf_sagevendorid`, el sistema usa `vendor_id` como antes.

2. **Performance**: No hay impacto en performance ya que solo se agregan validaciones opcionales y una verificación simple en el transformer.

3. **Extensibilidad**: El sistema está preparado para manejar custom fields adicionales sin modificaciones estructurales.

4. **Logging**: Se ha implementado logging detallado para facilitar debugging y auditoría en producción.

## Próximos Pasos

1. **Configurar autenticación** para pruebas completas de API
2. **Deploy a staging** para pruebas con datos reales de Zoho  
3. **Configurar monitoring** para custom fields en producción
4. **Documentar custom fields adicionales** según necesidades del negocio

---

**Fecha de implementación**: 2025-10-06  
**Estado**: Funcional y listo para testing/production
