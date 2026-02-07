# Implementación de Zoho Organization ID Fix

## Fecha de Implementación
**14 de Octubre, 2025**

## Problema Identificado

### Error Original
```
"This user is not associated with the CompanyID/CompanyName:104"
```

### Causa Raíz
- **DocuCenter usa**: `organization_id: 104` (ID interno de DocuCenter)
- **Zoho Books espera**: `organization_id: "6088114000000000001"` (ID real de Zoho Books)

## Solución Implementada

### 1. Nuevos Métodos en ZohoSelfClientService

#### `getAndStoreZohoOrganizations()`
```php
public function getAndStoreZohoOrganizations()
```
- **Propósito**: Obtener organizaciones disponibles desde Zoho API
- **Funcionalidad**: 
  - Consulta `/organizations` endpoint
  - Si hay una sola organización, la configura automáticamente
  - Retorna array de organizaciones disponibles

#### `updateConnectionWithZohoOrganization($zohoOrganization)`
```php
public function updateConnectionWithZohoOrganization($zohoOrganization)
```
- **Propósito**: Actualizar conexión con organización de Zoho seleccionada
- **Almacena**:
  - `zoho_organization_id`: ID real de Zoho Books
  - `zoho_organization_name`: Nombre de la organización
  - `zoho_company_id`: Company ID (si está disponible)

### 2. Refactorización de Métodos Existentes

Los siguientes métodos fueron actualizados para usar `zoho_organization_id`:
- `searchVendorByName()`
- `searchCustomerByName()` 
- `getVendorDetails()`
- `getCustomerDetails()`

### 3. Estructura de Settings Actualizada

**Antes:**
```json
{
  "organization_id": 104,
  "access_token": "...",
  "refresh_token": "..."
}
```

**Después:**
```json
{
  "organization_id": 104,
  "zoho_organization_id": "6088114000000000001",
  "zoho_organization_name": "F.P. LATAM, S. A.",
  "zoho_company_id": "6088114000000000001",
  "access_token": "...",
  "refresh_token": "..."
}
```

## Comando de Migración

### UpdateZohoOrganizationIds
```bash
# Ver qué se actualizaría (dry run)
php artisan zoho:update-organization-ids --dry-run

# Actualizar todas las conexiones
php artisan zoho:update-organization-ids

# Actualizar conexión específica
php artisan zoho:update-organization-ids --connection-id=23

# Forzar actualización (aunque ya tengan zoho_organization_id)
php artisan zoho:update-organization-ids --force
```

**Funcionalidades del comando:**
- Dry run para ver cambios sin aplicar
- Filtro por connection-id específico  
- Skip conexiones ya configuradas (a menos que --force)
- Logging detallado de errores
- Resumen de operaciones

## Flujo de Configuración

### Para Nuevas Conexiones
1. Usuario completa OAuth con Zoho
2. Sistema obtiene access_token y refresh_token
3. **NUEVO**: Sistema llama `getAndStoreZohoOrganizations()`
4. Si hay una organización → configuración automática
5. Si hay múltiples → usuario debe seleccionar

### Para Conexiones Existentes
1. Ejecutar comando: `php artisan zoho:update-organization-ids`
2. Sistema consulta organizaciones disponibles para cada conexión
3. Actualiza settings con zoho_organization_id correcto
4. Logs detallados de proceso

## Impacto en API Calls

### Antes (Con Error)
```php
$queryParams = [
    'organization_id' => 104, // ID interno de DocuCenter
    'contact_type' => 'vendor'
];
// Resultado: Error 6041
```

### Después (Correcto)
```php
$queryParams = [
    'organization_id' => '6088114000000000001', // ID real de Zoho
    'contact_type' => 'vendor'  
];
// Resultado: Éxito
```

## Compatibilidad Backwards

- Mantiene `organization_id` original para compatibilidad interna
- Agrega `zoho_organization_id` para API calls
- Métodos existentes siguen funcionando
- Gradual rollout via comando de migración

## Testing y Validación

### Script de Validación Inmediata
```bash
# Para organización específica (ejemplo: 104)
docker exec -it docucenter-app-1 php artisan tinker --execute="
\$connection = App\Models\Connection::where('organization_id', 104)->where('type', 'zoho_books')->first();
if (\$connection) {
    \$service = new App\Services\ZohoSelfClientService(\$connection);
    \$orgs = \$service->getAndStoreZohoOrganizations();
    dd([
        'connection_id' => \$connection->id,
        'organizations_found' => count(\$orgs ?? []),
        'settings_updated' => \$connection->fresh()->settings
    ]);
}
"

# Probar llamada a vendor details después de actualización
docker exec -it docucenter-app-1 php artisan tinker --execute="
\$connection = App\Models\Connection::find(23);
\$service = new App\Services\ZohoSelfClientService(\$connection);
\$details = \$service->getVendorDetails('6088114000000487410');
echo 'Success: ' . (!is_null(\$details) ? 'YES' : 'NO') . PHP_EOL;
"
```

### Monitoreo Post-Implementación
```bash
# Verificar que no hay más errores 6041
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep -E "(6041|organization_id.*404|zoho_organization_id)"
```

## Plan de Implementación

### Fase 1: Desarrollo 
- [x] Implementar métodos en ZohoSelfClientService
- [x] Crear comando de migración
- [x] Documentación técnica

### Fase 2: Testing
```bash
# 1. Dry run para ver impacto
php artisan zoho:update-organization-ids --dry-run

# 2. Actualizar conexión de prueba específica
php artisan zoho:update-organization-ids --connection-id=23

# 3. Probar llamadas API
# Ver scripts de validación arriba
```

### Fase 3: Producción
```bash
# 1. Backup de connections table
# 2. Actualizar todas las conexiones
php artisan zoho:update-organization-ids

# 3. Monitorear logs por 24h
# 4. Validar que webhooks funcionan sin error 6041
```

## Resolución del Error 6041

### Root Cause
El error 6041 ocurría porque estábamos enviando el `organization_id` interno de DocuCenter (104) en lugar del `organization_id` real de Zoho Books.

### Fix Implementado
1. **Obtención Automática**: Al crear/actualizar conexión, obtener organizaciones de Zoho API
2. **Storage Correcto**: Almacenar `zoho_organization_id` en settings
3. **Uso Apropiado**: Usar `zoho_organization_id` en todas las llamadas API
4. **Migración Gradual**: Comando para actualizar conexiones existentes

### Resultado Esperado
- Error 6041: "This user is not associated with the CompanyID/CompanyName:104"
- Llamadas exitosas con custom fields disponibles
- SageVendorID funcionando correctamente en lugar de fallback

## Próximos Pasos

1. **Ejecutar migración** en conexiones de staging/producción
2. **Monitorear logs** para confirmar resolución de error 6041
3. **Validar custom fields** ahora funcionen correctamente
4. **Actualizar documentación** de configuración de conexiones Zoho

## Notas Técnicas

- **Multi-tenant compatible**: Maneja cambio de BD durante actualización de conexiones
- **Error handling robusto**: Logs detallados y recovery graceful
- **Idempotente**: Comando se puede ejecutar múltiples veces sin problemas
- **Flexible**: Soporte para múltiples organizaciones por conexión (futuro)
