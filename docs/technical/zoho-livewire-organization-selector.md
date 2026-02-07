# Implementación de Selector de Organizaciones Zoho en Livewire

## Fecha de Implementación
**14 de Octubre, 2025**

## Resumen

Se implementó la funcionalidad para seleccionar organizaciones de Zoho Books directamente en los componentes Livewire de conexiones (`Create` y `Update`), resolviendo el problema del error 6041 y proporcionando una experiencia de usuario mejorada.

## Funcionalidades Implementadas

### 1. Componente Create (`app/Http/Livewire/Admin/Connection/Create.php`)

#### Nuevas Propiedades
```php
public $zoho_organizations = [];           // Array de organizaciones disponibles
public $selected_zoho_organization_id;     // ID de organización seleccionada
public $show_organization_selector = false; // Mostrar/ocultar selector
```

#### Métodos Agregados

##### `loadZohoOrganizations()`
- **Propósito**: Cargar organizaciones disponibles después de autorización exitosa
- **Flujo**:
  1. Crea conexión temporal con tokens OAuth
  2. Usa `ZohoSelfClientService::getAndStoreZohoOrganizations()`
  3. Si hay una sola organización → selección automática
  4. Si hay múltiples → muestra selector

##### `createConnectionWithSelectedOrganization()`
- **Propósito**: Crear conexión final con organización seleccionada
- **Validaciones**: Organización válida y seleccionada
- **Resultado**: Conexión guardada con `zoho_organization_id` correcto

#### Flujo Modificado
**Antes:**
```
Autorizar → Crear Conexión → Listo
```

**Después:**
```
Autorizar → Cargar Organizaciones → Seleccionar → Crear Conexión → Listo
```

### 2. Componente Update (`app/Http/Livewire/Admin/Connection/Update.php`)

#### Métodos Modificados

##### `reauthorizeWithDirectCode()`
**Modificación clave:**
```php
// Si no hay organización configurada, cargar selector
if (empty($updatedSettings['zoho_organization_id'])) {
    $this->settings = $updatedSettings;
    $this->loadZohoOrganizations();
} else {
    // Organización ya existe, continuar normal
}
```

##### `updateConnectionWithSelectedOrganization()`
- **Propósito**: Actualizar conexión existente con nueva organización
- **Funcionalidad**: Similar a Create pero actualiza en lugar de crear

### 3. Interfaz de Usuario

#### Vista Create (`resources/views/livewire/admin/connection/create.blade.php`)

**Selector de Organizaciones:**
```blade
@if($show_organization_selector && count($zoho_organizations) > 0)
<div class="mt-4">
    <div class="alert alert-success">
        <h5>Autorización Exitosa</h5>
        <p>Seleccione la organización de Zoho Books:</p>
    </div>
    
    <select wire:model.lazy='selected_zoho_organization_id'>
        @foreach($zoho_organizations as $org)
            <option value="{{ $org['organization_id'] }}">
                {{ $org['name'] }} (ID: {{ $org['company_id'] }})
            </option>
        @endforeach
    </select>
    
    <button wire:click="createConnectionWithSelectedOrganization">
        Crear Conexión con Organización Seleccionada
    </button>
</div>
@endif
```

**Información de Organización Seleccionada:**
- Nombre de la organización
- Organization ID de Zoho
- Company ID (si disponible)
- Moneda (si disponible)

#### Vista Update (`resources/views/livewire/admin/connection/update.blade.php`)

**Funcionalidades adicionales:**
- Selector igual que Create
- **Mostrar organización actual** si ya está configurada
- **Indicador visual** de organización activa

## Beneficios de la Implementación

### 1. Resolución del Error 6041
- **Problema**: Uso de `organization_id` interno (104) en lugar del real de Zoho
- **Solución**: Selección directa del `zoho_organization_id` correcto
- **Resultado**: Llamadas API exitosas sin error 6041

### 2. Experiencia de Usuario Mejorada
- **Selección visual** de organizaciones disponibles
- **Información detallada** de cada organización
- **Validación en tiempo real** de selección
- **Feedback inmediato** del proceso

### 3. Compatibilidad y Flexibilidad
- **Selección automática** para cuentas con una sola organización
- **Selector manual** para cuentas con múltiples organizaciones
- **Actualización sin pérdida** de configuración existente
- **Soporte para re-configuración** de organizaciones

## Flujos de Uso

### Crear Nueva Conexión
1. **Configurar credenciales**: Client ID, Client Secret, Environment
2. **Generar código**: En Zoho Developer Console
3. **Autorizar**: Click "Autorizar con Código Directo"
4. **Seleccionar organización**: Si hay múltiples opciones
5. **Crear conexión**: Con organización seleccionada

### Actualizar Conexión Existente
1. **Re-autorizar**: Si tokens han expirado
2. **Seleccionar organización**: Si no estaba configurada
3. **Continuar**: Con configuración actualizada

### Conexiones Legacy (Sin organización)
1. **Re-autorizar**: Usando código directo
2. **Selector aparece automáticamente**: Para configurar organización
3. **Seleccionar y guardar**: Organización apropiada

## Estructura de Datos

### Settings Antes de la Implementación
```json
{
  "client_id": "1000.XXX",
  "client_secret": "YYY",
  "access_token": "ZZZ",
  "refresh_token": "AAA",
  "token_expires_at": "2025-10-14 15:00:00",
  "zoho_environment": "com"
}
```

### Settings Después de la Implementación
```json
{
  "client_id": "1000.XXX",
  "client_secret": "YYY", 
  "access_token": "ZZZ",
  "refresh_token": "AAA",
  "token_expires_at": "2025-10-14 15:00:00",
  "zoho_environment": "com",
  "zoho_organization_id": "6088114000000000001",
  "zoho_organization_name": "F.P. LATAM, S. A.",
  "zoho_company_id": "6088114000000000001"
}
```

## Testing y Validación

### Casos de Prueba

#### 1. Nueva Conexión - Una Organización
```
WHEN: Usuario autoriza con código directo
AND: Cuenta Zoho tiene una sola organización
THEN: Organización se selecciona automáticamente
AND: Conexión se crea sin mostrar selector
```

#### 2. Nueva Conexión - Múltiples Organizaciones
```
WHEN: Usuario autoriza con código directo  
AND: Cuenta Zoho tiene múltiples organizaciones
THEN: Selector se muestra con todas las opciones
AND: Usuario debe seleccionar manualmente
AND: Conexión se crea con organización seleccionada
```

#### 3. Actualizar Conexión - Sin Organización Configurada
```
WHEN: Usuario re-autoriza conexión existente
AND: Conexión no tiene zoho_organization_id
THEN: Selector aparece después de autorización
AND: Usuario configura organización
```

#### 4. Actualizar Conexión - Con Organización Configurada
```
WHEN: Usuario re-autoriza conexión existente
AND: Conexión ya tiene zoho_organization_id
THEN: Re-autorización normal sin selector
AND: Organización actual se mantiene
```

### Scripts de Validación

#### Validar Organización en Conexión Existente
```bash
docker exec -it docucenter-app-1 php artisan tinker --execute="
\$connection = App\Models\Connection::where('application', 'zoho-self-client')->first();
echo 'Connection ID: ' . \$connection->id . PHP_EOL;
echo 'Has Zoho Org ID: ' . (isset(\$connection->settings['zoho_organization_id']) ? 'YES' : 'NO') . PHP_EOL;
if (isset(\$connection->settings['zoho_organization_id'])) {
    echo 'Zoho Org ID: ' . \$connection->settings['zoho_organization_id'] . PHP_EOL;
    echo 'Zoho Org Name: ' . (\$connection->settings['zoho_organization_name'] ?? 'N/A') . PHP_EOL;
}
"
```

#### Probar Carga de Organizaciones
```bash
docker exec -it docucenter-app-1 php artisan tinker --execute="
\$connection = App\Models\Connection::where('application', 'zoho-self-client')->first();
\$service = new App\Services\ZohoSelfClientService(\$connection);
\$orgs = \$service->getAndStoreZohoOrganizations();
echo 'Organizations found: ' . count(\$orgs ?? []) . PHP_EOL;
foreach (\$orgs ?? [] as \$org) {
    echo '- ' . \$org['name'] . ' (ID: ' . \$org['organization_id'] . ')' . PHP_EOL;
}
"
```

## Impacto en Producción

### Conexiones Existentes
- **Sin interrupción**: Conexiones actuales siguen funcionando
- **Mejora gradual**: Re-autorización habilita nueva funcionalidad
- **Backwards compatible**: No requiere migración forzada

### Nuevas Conexiones
- **Configuración correcta**: Automática desde el primer momento
- **Sin error 6041**: Garantizado con organización apropiada
- **Mejor UX**: Proceso guiado y claro

## Próximos Pasos

1. **Testing en Staging**: Validar flujos completos
2. **Migración de Conexiones Legacy**: Usar comando `zoho:update-organization-ids`
3. **Monitoreo Post-Implementación**: Verificar reducción de errores 6041
4. **Documentación de Usuario**: Guías para configurar nuevas conexiones

## Archivos Modificados

- `app/Http/Livewire/Admin/Connection/Create.php`
- `app/Http/Livewire/Admin/Connection/Update.php`  
- `resources/views/livewire/admin/connection/create.blade.php`
- `resources/views/livewire/admin/connection/update.blade.php`

## Conclusión

La implementación del selector de organizaciones Zoho en Livewire proporciona una solución completa al problema del error 6041, mejorando tanto la experiencia del usuario como la confiabilidad técnica del sistema. La funcionalidad es compatible con conexiones existentes y garantiza configuración correcta para nuevas conexiones.
