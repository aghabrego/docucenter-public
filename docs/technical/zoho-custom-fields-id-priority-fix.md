# Fix: Prioridad de IDs en Creación de Vendors/Customers de Zoho

## Problema Identificado

El sistema estaba creando vendors y customers usando siempre el `vendor_id`/`customer_id` de Zoho en lugar de priorizar el valor del campo personalizado `cf_sagevendorid`/`cf_sagecustomerid` cuando estaba disponible.

### Comportamiento Anterior (Incorrecto)
```sql
-- Vendor creado usando vendor_id de Zoho directamente
INSERT INTO Vendors_Imp (VendorID, Custom_field1, ...) 
VALUES ('6088114000000487410', 'SAGE_VENDOR_123', ...);
--      ↑ ID de Zoho        ↑ SageVendorID en custom field
```

**Problema**: El `VendorID` en la BD local siempre usaba el ID de Zoho, ignorando el `cf_sagevendorid`.

## Solución Implementada

### Nueva Lógica de Prioridad
1. **Primera prioridad**: Usar `cf_sagevendorid`/`cf_sagecustomerid` si está disponible y no está vacío
2. **Fallback**: Usar `vendor_id`/`customer_id` de Zoho solo si el campo personalizado está vacío

### Comportamiento Nuevo (Correcto)
```sql
-- Caso 1: Con custom field disponible
INSERT INTO Vendors_Imp (VendorID, Custom_field1, ...) 
VALUES ('SAGE_VENDOR_123', 'SAGE_VENDOR_123', ...);
--      ↑ SageVendorID     ↑ SageVendorID en custom field

-- Caso 2: Sin custom field (fallback)
INSERT INTO Vendors_Imp (VendorID, Custom_field1, ...) 
VALUES ('6088114000000487410', '', ...);
--      ↑ ID de Zoho        ↑ Custom field vacío
```

## Cambios Implementados

### 1. Método `createLocalVendor()` - Mejorado

#### Antes:
```php
$vendorData = [
    'ID_compania' => $companyId,
    'VendorID' => $zohoData['vendor_id'],  // ❌ Siempre ID de Zoho
    'VendrName' => $vendorName,
    // ...
];
```

#### Después:
```php
// Extraer campos personalizados
$customFields = $this->extractCustomFields($vendorDetails);

// Determinar qué ID usar: SageVendorID si está disponible, sino vendor_id como fallback
$sageVendorId = $customFields['cf_sagevendorid'] ?? null;
$finalVendorId = !empty($sageVendorId) ? $sageVendorId : $zohoData['vendor_id'];

Log::info('Determinando VendorID final para crear vendor local', [
    'vendor_id_zoho' => $zohoData['vendor_id'],
    'cf_sagevendorid' => $sageVendorId ?? 'N/A',
    'final_vendor_id' => $finalVendorId,
    'using_custom_field' => !empty($sageVendorId)
]);

$vendorData = [
    'ID_compania' => $companyId,
    'VendorID' => $finalVendorId,  // ✅ Prioriza SageVendorID
    'VendrName' => $vendorName,
    // ...
];
```

### 2. Método `createLocalCustomer()` - Mejorado

#### Lógica Similar Aplicada:
```php
// Determinar qué ID usar: SageCustomerID si está disponible, sino customer_id como fallback
$sageCustomerId = $customFields['cf_sagecustomerid'] ?? null;
$finalCustomerId = !empty($sageCustomerId) ? $sageCustomerId : $zohoData['customer_id'];

$customerData = [
    'ID_compania' => $companyId,
    'CustomerID' => $finalCustomerId,  // ✅ Prioriza SageCustomerID
    'Customer_Bill_Name' => $customerName,
    // ...
];
```

### 3. Logging Mejorado

#### Nuevos Logs de Decisión:
```php
Log::info('Determinando VendorID final para crear vendor local', [
    'vendor_id_zoho' => $zohoData['vendor_id'],
    'cf_sagevendorid' => $sageVendorId ?? 'N/A',
    'final_vendor_id' => $finalVendorId,
    'using_custom_field' => !empty($sageVendorId)
]);
```

#### Logs de Resultado Actualizados:
```php
Log::info('Vendor creado exitosamente en base de datos local', [
    'vendor_id_local' => $vendor->ID,
    'vendor_id_final' => $vendor->VendorID,  // ✅ ID final usado
    'vendor_name' => $vendor->VendrName,
    'company_id' => $vendor->ID_compania,
    'cf_sagevendorid' => $vendor->Custom_field1
]);
```

## Casos de Uso Cubiertos

### ✅ Caso 1: Vendor con SageVendorID
```json
{
  "vendor_id": "6088114000000487410",
  "vendor_name": "EA LOGISTIC ULTD",
  "custom_field_hash": {
    "cf_sagevendorid": "SAGE_VENDOR_EA_001"
  }
}
```
**Resultado**: `VendorID = "SAGE_VENDOR_EA_001"`, `Custom_field1 = "SAGE_VENDOR_EA_001"`

### ✅ Caso 2: Vendor sin SageVendorID (fallback)
```json
{
  "vendor_id": "6088114000000487410",
  "vendor_name": "EA LOGISTIC ULTD"
}
```
**Resultado**: `VendorID = "6088114000000487410"`, `Custom_field1 = ""`

### ✅ Caso 3: Vendor con SageVendorID vacío (fallback)
```json
{
  "vendor_id": "6088114000000487410",
  "vendor_name": "EA LOGISTIC ULTD",
  "custom_field_hash": {
    "cf_sagevendorid": ""
  }
}
```
**Resultado**: `VendorID = "6088114000000487410"`, `Custom_field1 = ""`

## Validación del Fix

### Verificar en Base de Datos:
```sql
-- Vendors con SageVendorID
SELECT ID, VendorID, VendrName, Custom_field1, Export_date 
FROM Vendors_Imp 
WHERE Custom_field1 != '' 
  AND VendorID = Custom_field1  -- ✅ Deben coincidir
ORDER BY Export_date DESC;

-- Vendors sin SageVendorID (fallback a Zoho ID)
SELECT ID, VendorID, VendrName, Custom_field1, Export_date 
FROM Vendors_Imp 
WHERE Custom_field1 = '' 
  AND VendorID LIKE '60881%'  -- ✅ ID típico de Zoho
ORDER BY Export_date DESC;
```

### Monitorear Logs:
```bash
# Ver decisiones de ID en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep -E "(Determinando.*ID final|using_custom_field)"
```

### Testing Script:
```bash
# Usar script de testing existente
./scripts/test-zoho-custom-fields.sh
# Opción 6: "Probar búsqueda/creación por SageVendorID"
```

## Beneficios del Fix

### 🎯 **Consistencia de Datos**
- Los vendors ahora usan su SageVendorID real cuando está disponible
- Mejor sincronización entre Sage y Zoho
- IDs más legibles y consistentes

### 🔍 **Trazabilidad Mejorada**
- Logs claros sobre qué ID se está usando y por qué
- Fácil identificación de vendors con custom fields vs fallback

### 🛡️ **Compatibilidad Mantenida**
- Fallback automático a Zoho ID cuando no hay custom field
- No rompe vendors existentes
- Retrocompatibilidad completa

## Impacto en Sistema Existente

### ✅ **Sin Breaking Changes**
- Vendors existentes continúan funcionando
- Solo afecta vendors/customers nuevos
- Lógica de búsqueda existente intacta

### ✅ **Mejora Incremental**
- Nuevos vendors usan la lógica mejorada automáticamente
- Datos más limpios y consistentes en adelante
- Base para futuras optimizaciones

## Estado: ✅ IMPLEMENTADO

El fix está completo y garantiza que:
1. **SageVendorID tiene prioridad** cuando está disponible
2. **Fallback robusto** a Zoho ID cuando es necesario  
3. **Logging transparente** del proceso de decisión
4. **Compatibilidad total** con sistema existente

Los vendors y customers ahora se crean con la lógica de prioridad correcta, mejorando la integridad y consistencia de datos en el sistema multi-tenant.
