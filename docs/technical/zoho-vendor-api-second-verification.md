# Segunda Verificación Custom Fields de Zoho desde API

## Resumen

Se implementó una segunda verificación para obtener campos personalizados de Zoho (`cf_sagevendorid` y `cf_sagecustomerid`) consultando directamente la API de Zoho Books cuando estos campos no están presentes en el `custom_field_hash` de los webhooks.

## Problema Identificado

En algunos casos, los webhooks de Zoho (Purchase Orders y Sales Orders) no incluyen los campos personalizados `cf_sagevendorid` o `cf_sagecustomerid` en el `custom_field_hash`, pero estos campos sí están configurados en los vendors/customers correspondientes. Esto causaba que el sistema usara los IDs de Zoho como fallback en lugar de los IDs correctos de Sage.

## Solución Implementada

### 1. Flujo de Verificación

#### Para Purchase Orders (Vendors)
**Primera Verificación**: Buscar `cf_sagevendorid` en `custom_field_hash` del webhook
```php
$customFieldHash = $zohoData['custom_field_hash'] ?? [];
$sageVendorId = $customFieldHash['cf_sagevendorid'] ?? null;
```

**Segunda Verificación**: Si no se encuentra, consultar vendor desde API de Zoho
```php
if (empty($sageVendorId) && !empty($zohoData['vendor_id'])) {
    $sageVendorId = $this->customFieldsHelper->getSageVendorId($zohoData, $contextInfo);
}
```

#### Para Sales Orders (Customers)
**Primera Verificación**: Buscar `cf_sagecustomerid` en `custom_field_hash` del webhook
```php
$customFieldHash = $zohoData['custom_field_hash'] ?? [];
$sageCustomerId = $customFieldHash['cf_sagecustomerid'] ?? null;
```

**Segunda Verificación**: Si no se encuentra, consultar customer desde API de Zoho
```php
if (empty($sageCustomerId) && !empty($zohoData['customer_id'])) {
    $sageCustomerId = $this->customFieldsHelper->getSageCustomerId($zohoData, $contextInfo);
}
```

**Fallback**: Si ninguna verificación funciona, usar `vendor_id` o `customer_id` original

### 2. Archivos Modificados

#### ZohoSelfClientService.php
**Ubicación**: `app/Services/ZohoSelfClientService.php`

**Métodos Agregados**:
```php
// Para vendors
public function getVendorDetails($vendorId)
{
    $settings = $this->connection->settings;
    $organizationId = $settings['organization_id'] ?? null;

    $endpoint = "/contacts/{$vendorId}";
    $queryParams = ['organization_id' => $organizationId];

    $response = $this->apiRequest('GET', $endpoint, $queryParams);
    
    if ($response && isset($response['contact'])) {
        return $response['contact'];
    }
    
    return null;
}

// Para customers
public function getCustomerDetails($customerId)
{
    $settings = $this->connection->settings;
    $organizationId = $settings['organization_id'] ?? null;

    $endpoint = "/contacts/{$customerId}";
    $queryParams = ['organization_id' => $organizationId];

    $response = $this->apiRequest('GET', $endpoint, $queryParams);
    
    if ($response && isset($response['contact'])) {
        return $response['contact'];
    }
    
    return null;
}
```

#### ZohoCustomFieldsHelper.php (NUEVO)
**Ubicación**: `app/Services/Zoho/ZohoCustomFieldsHelper.php`

**Clase Helper centralizada** para manejar ambos tipos de campos personalizados:

```php
class ZohoCustomFieldsHelper
{
    protected $zohoService;

    public function __construct(ZohoSelfClientService $zohoService = null)
    {
        $this->zohoService = $zohoService;
    }

    // Obtener cf_sagevendorid con doble verificación
    public function getSageVendorId(array $zohoData, ?string $contextInfo = null): ?string
    {
        // Primera verificación: custom_field_hash
        $customFieldHash = $zohoData['custom_field_hash'] ?? [];
        $sageVendorId = $customFieldHash['cf_sagevendorid'] ?? null;

        if (!empty($sageVendorId)) {
            return $sageVendorId;
        }

        // Segunda verificación: vendor API
        if (!empty($zohoData['vendor_id'])) {
            return $this->getCustomFieldFromContactApi($zohoData['vendor_id'], 'cf_sagevendorid', 'vendor');
        }

        return null;
    }

    // Obtener cf_sagecustomerid con doble verificación
    public function getSageCustomerId(array $zohoData, ?string $contextInfo = null): ?string
    {
        // Primera verificación: custom_field_hash
        $customFieldHash = $zohoData['custom_field_hash'] ?? [];
        $sageCustomerId = $customFieldHash['cf_sagecustomerid'] ?? null;

        if (!empty($sageCustomerId)) {
            return $sageCustomerId;
        }

        // Segunda verificación: customer API
        if (!empty($zohoData['customer_id'])) {
            return $this->getCustomFieldFromContactApi($zohoData['customer_id'], 'cf_sagecustomerid', 'customer');
        }

        return null;
    }
}
```

#### ZohoPurchaseOrderTransformer.php
**Ubicación**: `app/Services/Zoho/ZohoPurchaseOrderTransformer.php`

**Constructor Actualizado**:
```php
public function __construct(ZohoSelfClientService $zohoService = null)
{
    $this->zohoService = $zohoService;
    $this->customFieldsHelper = new ZohoCustomFieldsHelper($zohoService);
}
```

**Método transformHeader Simplificado**:
```php
public function transformHeader(array $zohoData): array
{
    // Usar helper para obtener cf_sagevendorid con doble verificación
    $sageVendorId = $this->customFieldsHelper->getSageVendorId($zohoData, $zohoData['bill_number'] ?? 'N/A');

    return [
        'VendorID' => $this->truncateText($sageVendorId ?? $zohoData['vendor_id'] ?? '', 20),
        // ... resto de campos
    ];
}
```

#### ACIcloudService.php
**Ubicación**: `app/Services/ACIcloudService.php`

**Método createSaleOrderZoho Actualizado**:
```php
public function createSaleOrderZoho(CreateSaleOrderZohoRequest $request)
{
    // Obtener conexión de Zoho para consultas adicionales
    $zohoConnection = Connection::where('application', 'zoho-self-client')
        ->where('organization_id', $this->organization->id ?? null)
        ->first();

    $customFieldsHelper = null;
    if ($zohoConnection) {
        $zohoService = new ZohoSelfClientService($zohoConnection);
        $customFieldsHelper = new ZohoCustomFieldsHelper($zohoService);
    }

    // Usar helper para obtener cf_sagecustomerid con doble verificación
    $sageCustomerId = null;
    if ($customFieldsHelper) {
        $sageCustomerId = $customFieldsHelper->getSageCustomerId($request->all(), $request->input('salesorder_number'));
    } else {
        // Fallback: solo custom_field_hash
        $customFieldHash = $request->input('custom_field_hash', []);
        $sageCustomerId = $customFieldHash['cf_sagecustomerid'] ?? null;
    }

    $customerData = [
        'CustomerID' => $sageCustomerId ?? $request->input('customer_id'),
        // ... resto de campos
    ];
}
```

#### ACIcloudController.php
**Ubicación**: `app/Http/Controllers/Sage/ACIcloudController.php`

**Inyección de Servicio**:
```php
// Obtener conexión de Zoho para la organización
$zohoConnection = \App\Models\Connection::where('application', 'zoho-self-client')
    ->where('organization_id', $organizationId)
    ->where('active', true)
    ->first();

$zohoService = null;
if ($zohoConnection) {
    $zohoService = new ZohoSelfClientService($zohoConnection);
}

// Crear transformer con servicio
$importer = new ZohoPurchaseOrderImporter(
    new ZohoPurchaseOrderTransformer($zohoService)
);
```

#### ProcessZohoPurchaseOrderJob.php
**Ubicación**: `app/Jobs/ProcessZohoPurchaseOrderJob.php`

**Mismo patrón de inyección que el controlador**

### 3. Logging Detallado

El sistema ahora registra detalladamente el proceso de verificación para ambos tipos:

#### Para Vendors:
```php
Log::info('cf_sagevendorid obtenido exitosamente', [
    'cf_sagevendorid' => $sageVendorId,
    'vendor_id' => $zohoData['vendor_id'],
    'bill_number' => $zohoData['bill_number'],
    'source' => 'custom_field_hash' // o 'vendor_api'
]);
```

#### Para Customers:
```php
Log::info('cf_sagecustomerid obtenido exitosamente', [
    'cf_sagecustomerid' => $sageCustomerId,
    'customer_id' => $zohoData['customer_id'],
    'salesorder_number' => $zohoData['salesorder_number'],
    'source' => 'custom_field_hash' // o 'customer_api'
]);
```

## Compatibilidad

### Backward Compatibility
- El transformer sigue funcionando sin `ZohoSelfClientService` (fallback)
- Si no hay conexión Zoho configurada, usa el comportamiento anterior
- Los métodos existentes no cambiaron su signature

### Casos de Uso
1. **Purchase Orders con Conexión Zoho**: Consulta vendor API como segunda verificación para cf_sagevendorid
2. **Sales Orders con Conexión Zoho**: Consulta customer API como segunda verificación para cf_sagecustomerid
3. **Sin Conexión Zoho**: Usa solo custom_field_hash + fallback a vendor_id/customer_id
4. **Error en API**: Logs del error y continúa con fallback

### Testing

### Scripts de Prueba
- **Vendors**: `docs/testing/test-zoho-vendor-api-cf-sagevendorid.sh`
- **Customers**: `docs/testing/test-zoho-customer-api-cf-sagecustomerid.sh`

**Funcionalidades Probadas**:
- ZohoCustomFieldsHelper con y sin servicio de Zoho
- Consulta a vendor/customer API
- Fallback cuando no hay conexión
- Logging de debugging

### Casos de Prueba
#### Para Purchase Orders:
1. **Webhook con cf_sagevendorid en custom_field_hash**: Usa valor directo
2. **Webhook sin cf_sagevendorid**: Consulta vendor API
3. **Vendor con cf_sagevendorid en custom_fields**: Obtiene valor correcto
4. **Vendor sin cf_sagevendorid**: Usa vendor_id como fallback
5. **Error en API**: Maneja excepción y continúa con fallback

#### Para Sales Orders:
1. **Webhook con cf_sagecustomerid en custom_field_hash**: Usa valor directo
2. **Webhook sin cf_sagecustomerid**: Consulta customer API
3. **Customer con cf_sagecustomerid en custom_fields**: Obtiene valor correcto
4. **Customer sin cf_sagecustomerid**: Usa customer_id como fallback
5. **Error en API**: Maneja excepción y continúa con fallback

## Beneficios

### 1. Precisión de Datos
- Obtiene el ID correcto de Sage incluso cuando el webhook no lo incluye
- Reduce errores de mapeo entre sistemas

### 2. Robustez
- Múltiples verificaciones garantizan que se obtenga el mejor dato disponible
- Manejo de errores gracioso con fallbacks

### 3. Debugging
- Logging detallado para troubleshooting
- Rastreo de fuente de datos (webhook vs API)

### 4. Flexibilidad
- Funciona con o sin conexión Zoho configurada
- Compatible con implementaciones existentes

## Configuración Requerida

### Conexión Zoho Books
Para que la segunda verificación funcione, la organización debe tener:

1. **Conexión configurada**: `application = 'zoho-self-client'`
2. **Autenticación OAuth2**: Access token válido
3. **Permisos API**: Lectura de contacts/vendors
4. **Estado activo**: `active = true`

### Campos Personalizados en Zoho
Los vendors y customers en Zoho Books deben tener:
- **Para vendors**: Campo personalizado con `customfield_id = 'cf_sagevendorid'`
- **Para customers**: Campo personalizado con `customfield_id = 'cf_sagecustomerid'`
- Valores configurados con los IDs correspondientes de Sage

## Monitoreo

### Logs a Revisar
```bash
# Verificaciones exitosas de vendors
grep "cf_sagevendorid obtenido exitosamente" storage/logs/laravel.log

# Verificaciones exitosas de customers
grep "cf_sagecustomerid obtenido exitosamente" storage/logs/laravel.log

# Consultas a vendor API
grep "Consultando vendor details desde Zoho API" storage/logs/laravel.log

# Consultas a customer API
grep "Consultando customer details desde Zoho API" storage/logs/laravel.log

# Fallbacks
grep "no encontrado en ninguna fuente" storage/logs/laravel.log
```

### Métricas Recomendadas
- Porcentaje de éxito en primera vs segunda verificación
- Tiempo de respuesta de consultas a vendor API
- Frecuencia de fallbacks a vendor_id

## Troubleshooting

### Problemas Comunes

1. **"ZohoSelfClientService no disponible"**
   - Verificar conexión Zoho configurada y activa
   - Verificar tokens OAuth2 válidos

2. **"Vendor sin custom_fields"**
   - Verificar que el vendor en Zoho tenga cf_sagevendorid configurado
   - Verificar permisos de API para lectura de custom fields

3. **"Error al consultar vendor details"**
   - Verificar conectividad con API de Zoho
   - Verificar que vendor_id existe en Zoho
   - Revisar logs detallados de excepción

## Conclusión

Esta implementación mejora significativamente la precisión del mapeo de vendors y customers entre Zoho Books y Sage, proporcionando una segunda oportunidad para obtener los IDs correctos cuando los webhooks iniciales no incluyen toda la información necesaria.

**Características principales**:
- ✅ **Soporte dual**: cf_sagevendorid (purchase orders) y cf_sagecustomerid (sales orders)
- ✅ **Helper centralizado**: ZohoCustomFieldsHelper maneja ambos tipos de campos
- ✅ **Doble verificación**: custom_field_hash + API lookup
- ✅ **Fallback robusto**: Funciona sin conexión Zoho configurada
- ✅ **Logging detallado**: Rastreo completo del proceso de verificación
- ✅ **Testing completo**: Scripts de prueba para ambos flujos

La arquitectura mantiene compatibilidad completa con implementaciones existentes mientras agrega capacidades avanzadas de consulta de datos para una integración más precisa entre sistemas.
