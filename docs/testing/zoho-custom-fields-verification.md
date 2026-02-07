# Verificación de Custom Fields en Zoho Books API

## Resumen de Cambios Implementados

Basándose en la documentación oficial de Zoho Books API, se han implementado correcciones importantes para el manejo adecuado de campos personalizados (custom fields) en vendors y customers.

### Problemas Identificados y Corregidos

1. **Estructura incorrecta de custom_fields**: El código anterior usaba `customfield_id` pero la documentación oficial especifica que usa `index`, `value` y `label`
2. **Búsqueda mejorada de campos**: Ahora busca por label en lugar de ID inexistente
3. **Mapeo flexible de campos**: Permite múltiples labels para encontrar el mismo campo (ej: "SageVendorID", "Sage Vendor ID", "Vendor ID")
4. **Verificación de contact_type**: Los métodos ahora verifican correctamente si es vendor o customer

### Estructura Oficial de Custom Fields según Zoho Books API

```json
{
  "custom_fields": [
    {
      "index": 1,
      "value": "GBGD078",
      "label": "VAT ID"
    }
  ]
}
```

### Métodos Implementados

#### 1. ZohoCustomFieldsHelper - Métodos mejorados:
- `getSageVendorId()` - Extracción mejorada de cf_sagevendorid
- `getSageCustomerId()` - Extracción mejorada de cf_sagecustomerid  
- `ensureVendorExists()` - Verificación/creación de vendors locales
- `ensureCustomerExists()` - Verificación/creación de customers locales
- `findOrCreateVendorBySageId()` - Búsqueda inteligente por SageVendorID
- `findOrCreateCustomerBySageId()` - Búsqueda inteligente por SageCustomerID

#### 2. ZohoSelfClientService - Métodos mejorados:
- `createCustomer()` - Asegura contact_type='customer' y logging mejorado
- `createVendor()` - Asegura contact_type='vendor' y logging mejorado

### Flujo de Verificación/Creación

1. **Búsqueda local**: Primero busca en la BD local por Custom_field1 (SageVendorID/SageCustomerID)
2. **Búsqueda en Zoho**: Si no existe localmente, busca en Zoho por nombre
3. **Creación en Zoho**: Si no existe en ningún lado, lo crea en Zoho con custom fields
4. **Creación local**: Finalmente crea el registro local con los datos completos

## Scripts de Prueba

### 1. Verificar Estructura de Custom Fields en Contacto Existente

```bash
# Ejecutar dentro del contenedor Docker
docker exec -it docucenter-app-1 php artisan tinker
```

```php
// En Tinker - Verificar conexión y custom fields
use App\Models\Connection;
use App\Services\ZohoSelfClientService;
use App\Services\Zoho\ZohoCustomFieldsHelper;

// Obtener una conexión Zoho activa
$connection = Connection::where('type', 'zoho')->where('status', 'active')->first();

if ($connection) {
    $zohoService = new ZohoSelfClientService($connection);
    $helper = new ZohoCustomFieldsHelper($zohoService);
    
    // Buscar un vendor/customer para verificar estructura
    $contacts = $zohoService->apiRequest('GET', '/contacts', [
        'organization_id' => $connection->settings['organization_id'],
        'contact_type' => 'vendor',
        'per_page' => 1
    ]);
    
    if ($contacts && !empty($contacts['contacts'])) {
        $contactId = $contacts['contacts'][0]['contact_id'];
        echo "Testing con Contact ID: {$contactId}\n";
        
        // Obtener detalles completos
        $details = $zohoService->getVendorDetails($contactId);
        
        if ($details && isset($details['custom_fields'])) {
            echo "Custom Fields encontrados:\n";
            foreach ($details['custom_fields'] as $field) {
                echo "- Index: " . ($field['index'] ?? 'N/A') . "\n";
                echo "  Label: " . ($field['label'] ?? 'N/A') . "\n";
                echo "  Value: " . ($field['value'] ?? 'N/A') . "\n\n";
            }
        } else {
            echo "No se encontraron custom fields\n";
        }
    }
} else {
    echo "No se encontró conexión Zoho activa\n";
}
```

### 2. Probar Creación de Vendor con Custom Fields

```php
// En Tinker - Crear vendor con custom field
$vendorData = [
    'contact_name' => 'Test Vendor ' . date('Y-m-d H:i:s'),
    'contact_type' => 'vendor',
    'email' => 'test@vendor.com',
    'custom_fields' => [
        [
            'index' => 1,
            'value' => 'SAGE_VENDOR_' . time(),
            'label' => 'SageVendorID'
        ]
    ]
];

$result = $zohoService->createVendor($vendorData);

if ($result && isset($result['contact'])) {
    echo "Vendor creado exitosamente:\n";
    echo "ID: " . $result['contact']['contact_id'] . "\n";
    echo "Nombre: " . $result['contact']['contact_name'] . "\n";
    
    // Verificar custom fields en el vendor creado
    $details = $zohoService->getVendorDetails($result['contact']['contact_id']);
    if ($details && isset($details['custom_fields'])) {
        echo "Custom Fields del vendor creado:\n";
        foreach ($details['custom_fields'] as $field) {
            echo "- " . ($field['label'] ?? 'N/A') . ": " . ($field['value'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "Error creando vendor\n";
    var_dump($result);
}
```

### 3. Probar Helper de Custom Fields

```php
// En Tinker - Probar helper
$testData = [
    'vendor_id' => 'ID_DEL_VENDOR_CREADO_ARRIBA',
    'vendor_name' => 'Test Vendor',
    'custom_field_hash' => [
        'cf_sagevendorid' => 'SAGE_VENDOR_123'
    ]
];

$sageVendorId = $helper->getSageVendorId($testData, 'test_context');
echo "SageVendorID extraído: " . ($sageVendorId ?? 'NULL') . "\n";

// Probar método de búsqueda/creación
$organizationId = 1; // Ajustar según tu organización

$vendor = $helper->findOrCreateVendorBySageId(
    'SAGE_VENDOR_123',
    ['name' => 'Test Vendor', 'email' => 'test@vendor.com'],
    $organizationId
);

if ($vendor) {
    echo "Vendor procesado exitosamente:\n";
    echo "ID Local: " . $vendor->ID . "\n";
    echo "ID Zoho: " . $vendor->VendorID . "\n";
    echo "Nombre: " . $vendor->VendrName . "\n";
    echo "SageVendorID: " . $vendor->Custom_field1 . "\n";
} else {
    echo "Error procesando vendor\n";
}
```

## Validación de Resultados

### Verificar logs:
```bash
# Ver logs en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep -E "(ZohoCustomFieldsHelper|custom_field|SageVendorID|SageCustomerID)"
```

### Verificar base de datos:
```sql
-- Vendors creados
SELECT ID, VendorID, VendrName, Custom_field1, Custom_field2 
FROM Vendors_Imp 
WHERE Custom_field1 LIKE 'SAGE_VENDOR_%' 
ORDER BY ID DESC 
LIMIT 5;

-- Customers creados
SELECT ID, CustomerID, Customer_Bill_Name, Custom_field1, Custom_field2 
FROM Customers_Imp 
WHERE Custom_field1 LIKE 'SAGE_CUSTOMER_%' 
ORDER BY ID DESC 
LIMIT 5;
```

## Casos de Prueba Específicos

### 1. Webhook con Custom Field Hash
```json
{
  "vendor_id": "123456789",
  "vendor_name": "Test Vendor",
  "custom_field_hash": {
    "cf_sagevendorid": "SAGE_VENDOR_123"
  }
}
```

**Resultado esperado**: SageVendorID extraído directamente de custom_field_hash

### 2. Webhook sin Custom Field Hash
```json
{
  "vendor_id": "123456789",
  "vendor_name": "Test Vendor"
}
```

**Resultado esperado**: Consulta API de Zoho para obtener custom fields del contacto

### 3. Vendor no existe en Zoho
```php
$helper->findOrCreateVendorBySageId(
    'SAGE_VENDOR_NEW',
    ['name' => 'New Vendor', 'email' => 'new@vendor.com'],
    $organizationId
);
```

**Resultado esperado**: 
1. Búsqueda local - no encontrado
2. Búsqueda en Zoho - no encontrado  
3. Creación en Zoho con custom field
4. Creación en BD local

## Monitoreo y Debugging

### Verificar errores comunes:
1. **organization_id no configurado**: Verificar settings de Connection
2. **Custom fields no mapeados**: Revisar labels en Zoho vs mapeo en código  
3. **Contact_type incorrecto**: Verificar que vendors tengan contact_type='vendor'
4. **Token expirado**: El sistema debe auto-renovar tokens

### Logs importantes a monitorear:
- `ZohoCustomFieldsHelper inicializado`
- `cf_sagevendorid encontrado en custom_field_hash`
- `Custom field encontrado en vendor Zoho`
- `Vendor creado exitosamente en base de datos local`
- `Vendor creado en Zoho exitosamente`

## Siguiente Fase: Integración con Purchase Orders

Una vez verificado que el sistema de custom fields funciona correctamente, se puede integrar con el procesamiento de purchase orders para asegurar que todos los vendors tengan sus respectivos SageVendorIDs correctamente asignados.
