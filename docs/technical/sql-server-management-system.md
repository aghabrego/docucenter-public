# Gestión de Configuraciones SQL Server

## Descripción General
Sistema de gestión de configuraciones SQL Server que permite a los administradores configurar conexiones a bases de datos SQL Server para diferentes organizaciones con asignación de Store IDs.

## Funcionalidades Implementadas

### 1. Modelo de Datos
- **Tabla**: `configuration_sql_server_organizations`
- **Modelo**: `Configurationsqlorganization`
- **Campos principales**:
  - `organization_id`: ID de la organización
  - `host`: Servidor SQL Server
  - `port`: Puerto de conexión
  - `database`: Nombre de la base de datos
  - `username`: Usuario de conexión
  - `password`: Contraseña
  - `store_id`: Identificador único del almacén (nuevo campo)

### 2. Interfaz de Usuario
- **Ruta**: `/admin/sql_server_management`
- **Componente**: `ManagementConfiguration` (Livewire)
- **Ubicación**: Menú separado "Gestión SQL Server" en el panel admin

### 3. Funcionalidades Principales

#### Selección de Organización
- Dropdown con todas las organizaciones disponibles
- Carga dinámica de configuraciones al seleccionar organización

#### Gestión de Configuraciones
- **Crear**: Formulario para nueva configuración con todos los campos
- **Editar**: Modificar configuraciones existentes
- **Eliminar**: Borrar configuraciones con confirmación
- **Listar**: Vista en tabla con todas las configuraciones

#### Validaciones
- Campos obligatorios: host, puerto, base de datos, usuario, contraseña
- Campo opcional: Store ID
- Validación de organización existente

#### Acciones Disponibles
- **Probar Conexión**: Simulación de prueba de conectividad
- **Editar**: Modificar configuración existente
- **Eliminar**: Borrar configuración con confirmación

### 4. Características Técnicas

#### Arquitectura Multi-Tenant
- Uso del trait `CustomConnection` para manejo de bases de datos múltiples
- Conexión por defecto para operaciones administrativas
- Scope automático por `organization_id`

#### Validaciones y Seguridad
- FormRequest con validación de datos
- Sanitización de entrada
- Manejo de errores con logging

#### UI/UX
- Interfaz responsiva con Bootstrap
- Mensajes flash para feedback del usuario
- Iconos descriptivos para acciones
- Estados de carga para mejor experiencia

## Implementación Detallada

### 1. Migración de Base de Datos
```php
// 2025_10_08_024346_add_store_id_to_configuration_sql_server_organizations_table.php
Schema::table('configuration_sql_server_organizations', function (Blueprint $table) {
    $table->string('store_id')->nullable()->after('password');
});
```

### 2. Modelo Actualizado
```php
// app/Models/Configurationsqlorganization.php
protected $fillable = [
    'organization_id',
    'host',
    'port',
    'database',
    'username',
    'password',
    'store_id', // Nuevo campo
];
```

### 3. Relación en Organization
```php
// app/Models/Organization.php
public function configurationsqlorganizations(): HasMany
{
    return $this->hasMany(Configurationsqlorganization::class, 'organization_id', 'id');
}
```

### 4. Ruta Registrada
```php
// routes/web.php
Route::get('sql_server_management', \App\Http\Livewire\Admin\SqlServer\ManagementConfiguration::class)
    ->middleware('dynamicAcl')
    ->name('sql_server_management');
```

### 5. Entrada de Menú
```php
// resources/views/vendor/admin/layouts/sidebar.blade.php
@if(hasPermission(getRouteName().'.sql_server_management', true))
<li class="sidebar-item @isActive([getRouteName().'.sql_server_management'], 'selected')">
    <a class="sidebar-link @isActive([getRouteName().'.sql_server_management'], 'active') " href="@route(getRouteName().'.sql_server_management')" aria-expanded="false">
        <i data-feather="database" class="feather-icon"></i>
        <span class="hide-menu">{{ __('Gestión SQL Server') }}</span>
    </a>
</li>
@endif
```

## Comando de Testing

### Uso del Comando
```bash
# Modo interactivo
docker exec -it docucenter_laravel.test php artisan test:sql-server-management

# Listar configuraciones
docker exec -it docucenter_laravel.test php artisan test:sql-server-management list

# Crear configuración de prueba
docker exec -it docucenter_laravel.test php artisan test:sql-server-management create --org-id=1

# Eliminar configuración
docker exec -it docucenter_laravel.test php artisan test:sql-server-management delete --config-id=1
```

### Modos Disponibles
1. **interactive**: Menú interactivo completo
2. **list**: Listar organizaciones y configuraciones
3. **create**: Crear configuración de prueba
4. **delete**: Eliminar configuración específica

### Opciones
- `--org-id`: ID de organización específica
- `--config-id`: ID de configuración específica

## Archivos Creados/Modificados

### Nuevos Archivos
1. `database/migrations/2025_10_08_024346_add_store_id_to_configuration_sql_server_organizations_table.php`
2. `app/Http/Livewire/Admin/SqlServer/ManagementConfiguration.php`
3. `resources/views/livewire/admin/sql-server/management-configuration.blade.php`
4. `app/Console/Commands/TestSqlServerManagement.php`

### Archivos Modificados
1. `app/Models/Configurationsqlorganization.php` - Agregado campo store_id
2. `app/Models/Organization.php` - Agregada relación configurationsqlorganizations
3. `routes/web.php` - Agregada ruta sql_server_management
4. `resources/views/vendor/admin/layouts/sidebar.blade.php` - Agregado item de menú

## Estructura de Datos

### Tabla: configuration_sql_server_organizations
```sql
id (bigint, primary key)
organization_id (bigint, foreign key)
host (string)
port (string)
database (string)
username (string)
password (string)
store_id (string, nullable) -- Nuevo campo
created_at (timestamp)
updated_at (timestamp)
```

## Validaciones Implementadas

### Reglas de Validación
```php
protected $rules = [
    'selectedOrganization' => 'required|exists:organizations,id',
    'host' => 'required|string|max:255',
    'port' => 'required|string|max:10',
    'database' => 'required|string|max:255',
    'username' => 'required|string|max:255',
    'password' => 'required|string|max:255',
    'store_id' => 'nullable|string|max:255',
];
```

### Mensajes Personalizados
- Mensajes en español para todas las validaciones
- Feedback claro para el usuario
- Indicación de campos obligatorios vs opcionales

## Consideraciones de Seguridad

1. **Contraseñas**: Se almacenan en la base de datos (considerar encriptación)
2. **Validación**: Input sanitization en todos los campos
3. **Permisos**: Sistema ACL dinámico para acceso
4. **Logging**: Registro de errores para debugging

## Funcionalidades Futuras

1. **Encriptación de Contraseñas**: Implementar encriptación para credenciales
2. **Prueba Real de Conexión**: Conectar realmente a SQL Server para validación
3. **Importación Masiva**: Cargar múltiples configuraciones desde archivo
4. **Auditoría**: Registro de cambios en configuraciones
5. **Backup/Restore**: Exportar e importar configuraciones

## Mantenimiento

### Logs a Revisar
- Errores en `storage/logs/laravel.log`
- Logs específicos de conexiones SQL Server
- Validaciones fallidas en formularios

### Testing Recomendado
- Probar con diferentes organizaciones
- Validar campos obligatorios
- Verificar eliminación con confirmación
- Comprobar edición de configuraciones existentes

## Notas de Desarrollo

1. **Multi-Tenant**: El sistema respeta la arquitectura multi-tenant de DocuCenter
2. **Consistencia**: Sigue los patrones establecidos en PlanManagement
3. **Usabilidad**: Interfaz intuitiva con feedback visual
4. **Escalabilidad**: Estructura preparada para futuras mejoras
