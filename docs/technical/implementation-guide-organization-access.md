# Guía de Implementación: Control de Acceso Organizacional

## Pasos para Implementar en Componentes Existentes

### 1. Preparación del Entorno

#### A. Verificar el Trait OrganizationAccessControl
```bash
# Verificar que el trait existe
ls -la app/Traits/OrganizationAccessControl.php

# Si no existe, crearlo con el contenido documentado
```

#### B. Identificar Componentes a Modificar
```bash
# Listar todos los componentes Setting
find app/Http/Livewire/Setting -name "*.php" -type f
```

### 2. Patrón de Modificación por Componente

#### A. Estructura Base de Modificación

Para cada componente en `app/Http/Livewire/Setting/`, seguir estos pasos:

```php
// 1. Agregar el trait al inicio de la clase
use App\Traits\OrganizationAccessControl;

class ExistingComponent extends Component
{
    // 2. Incluir el trait junto a los existentes
    use SetLocale, ExistingTraits, OrganizationAccessControl;
    
    // 3. Agregar propiedad para organización si no existe
    public $organization_id;
    
    // 4. Modificar mount() para incluir verificación
    public function mount()
    {
        $this->setCurrentLocale(); // Mantener lógica existente
        
        // Agregar verificación de acceso
        $this->checkConfigurationAccess('configuration.key');
        
        // ... resto de lógica existente
    }
    
    // 5. Agregar método para selección de organización si no existe
    public function onSelectOrganization()
    {
        if (!empty($this->organization_id)) {
            if (!$this->canOrganizationAccessConfiguration($this->organization_id, 'configuration.key')) {
                $this->addError('organization_id', $this->getRestrictionMessage('configuration.key'));
                return;
            }
            
            // Lógica específica del componente para cargar datos de la organización
        }
    }
    
    // 6. Modificar update() para incluir verificación
    public function update()
    {
        // Verificar acceso antes de cualquier operación
        $this->checkConfigurationAccess('configuration.key');
        
        if (!$this->canOrganizationAccessConfiguration($this->organization_id, 'configuration.key')) {
            $this->addError('organization_id', $this->getRestrictionMessage('configuration.key'));
            return;
        }
        
        // ... resto de lógica existente de validación y actualización
    }
    
    // 7. Modificar render() para incluir variables de acceso
    public function render()
    {
        $organizationsWithAccess = $this->getOrganizationsWithAccess('configuration.key');
        $availableConfigurations = $this->getAvailableConfigurations();
        $settingsMenu = $this->getSettingsMenuConfiguration();
        
        return view('livewire.setting.existing-component', [
            'organizationsWithAccess' => $organizationsWithAccess,
            'availableConfigurations' => $availableConfigurations,
            'settingsMenu' => $settingsMenu,
            'title' => __('Component Title')
        ]);
        // Nota: Remover ->layout() si causa errores de compilación
    }
}
```

### 3. Mapeo de Claves de Configuración

#### A. Matriz de Componentes y Claves

| Componente | Archivo | Clave de Configuración | Planes Requeridos |
|------------|---------|----------------------|-------------------|
| Profile | Profile.php | `profile` | basic, professional, premium, enterprise |
| Import | Import.php | `import.general` | professional, premium, enterprise |
| Import SQL Server | Import.php | `import.sqlserver` | professional, premium, enterprise |
| Import Magaya | Import.php | `import.magaya` | premium, enterprise |
| Import Fikable | Import.php | `import.fikable` | premium, enterprise |
| Invupos | Invupos.php | `extraction.invupos` | professional, premium, enterprise |
| Lightspeed | Lightspeed.php | `extraction.lightspeed` | premium, enterprise |
| LoginSecurity | LoginSecurity.php | `security.login` | professional, premium, enterprise |
| Extraction | Extraction.php | `extraction.general` | professional, premium, enterprise |

#### B. Configuración de Acceso en el Trait

```php
// En OrganizationAccessControl.php
private array $configurationAccess = [
    // Configuraciones básicas
    'profile' => ['basic', 'professional', 'premium', 'enterprise'],
    
    // Configuraciones profesionales
    'import.general' => ['professional', 'premium', 'enterprise'],
    'import.sqlserver' => ['professional', 'premium', 'enterprise'],
    'extraction.invupos' => ['professional', 'premium', 'enterprise'],
    'security.login' => ['professional', 'premium', 'enterprise'],
    'extraction.general' => ['professional', 'premium', 'enterprise'],
    
    // Configuraciones premium
    'import.magaya' => ['premium', 'enterprise'],
    'import.fikable' => ['premium', 'enterprise'],
    'extraction.lightspeed' => ['premium', 'enterprise'],
    'integration.quickbooks' => ['premium', 'enterprise'],
    
    // Configuraciones enterprise
    'integration.shopify' => ['enterprise'],
    'advanced.settings' => ['enterprise'],
];
```

### 4. Implementación Específica por Componente

#### A. Profile.php
```bash
# Comando para aplicar cambios
cp app/Http/Livewire/Setting/Profile.php app/Http/Livewire/Setting/Profile.backup.php

# Aplicar modificaciones según el patrón
```

**Modificaciones específicas**:
- Clave: `profile`
- Planes: Todos los planes
- Sin verificación de organización específica (configuración global de usuario)

#### B. Import.php
```bash
# Backup del archivo original
cp app/Http/Livewire/Setting/Import.php app/Http/Livewire/Setting/Import.backup.php
```

**Modificaciones específicas**:
- Clave base: `import.general`
- Claves adicionales: `import.sqlserver`, `import.magaya`, `import.fikable`
- Planes: Profesional en adelante
- Verificación de organización específica requerida

#### C. Invupos.php
```bash
# Backup del archivo original
cp app/Http/Livewire/Setting/Invupos.php app/Http/Livewire/Setting/Invupos.backup.php
```

**Modificaciones específicas**:
- Clave: `extraction.invupos`
- Planes: Profesional en adelante
- Verificación de organización específica requerida

### 5. Modificaciones en Vistas Blade

#### A. Agregar Selector de Organización Filtrado

```blade
{{-- En cada vista de configuración --}}
@if(isset($organizationsWithAccess) && $organizationsWithAccess->count() > 0)
<div class="form-group">
    <label for="organization_id">{{ __('Organization') }}</label>
    <select wire:model="organization_id" wire:change="onSelectOrganization" class="form-control">
        <option value="">{{ __('Select Organization') }}</option>
        @foreach($organizationsWithAccess as $org)
            <option value="{{ $org->id }}">{{ $org->name }} ({{ __(ucfirst($org->plan_type)) }})</option>
        @endforeach
    </select>
    @error('organization_id') <span class="text-danger">{{ $message }}</span> @enderror
</div>
@endif
```

#### B. Menú de Configuraciones Filtrado

```blade
{{-- Sidebar o menú de navegación --}}
@if(isset($settingsMenu))
<nav class="settings-navigation">
    @foreach($settingsMenu as $section => $configs)
        <div class="menu-section">
            <h4>{{ __(ucfirst($section)) }}</h4>
            @foreach($configs as $config)
                <a href="{{ route('setting.' . $config['route']) }}" 
                   class="nav-link {{ $config['active'] ? 'active' : '' }}">
                    <i class="{{ $config['icon'] ?? 'fas fa-cog' }}"></i>
                    {{ $config['name'] }}
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
@endif
```

### 6. Testing de la Implementación

#### A. Test Manual por Componente

```bash
# Para cada componente modificado, probar:

# 1. Acceso con plan básico
# 2. Acceso con plan profesional
# 3. Acceso con plan premium
# 4. Acceso con plan enterprise
# 5. Cambio de organización
# 6. Mensaje de restricción cuando no tiene acceso
```

#### B. Script de Verificación

```bash
#!/bin/bash
# scripts/test-organization-access.sh

echo "Testing Organization Access Control"

# Test Profile access (should work for all plans)
echo "Testing Profile access..."
curl -s "http://localhost/setting/profile" | grep -q "Profile" && echo "✓ Profile accessible" || echo "✗ Profile not accessible"

# Test Import access (should work for professional+)
echo "Testing Import access..."
curl -s "http://localhost/setting/import" | grep -q "Import" && echo "✓ Import accessible" || echo "✗ Import not accessible"

# Add more tests for each component
```

### 7. Deployment y Rollback

#### A. Plan de Deployment

```bash
# 1. Backup de archivos originales
mkdir -p backups/setting-components/$(date +%Y%m%d_%H%M%S)
cp -r app/Http/Livewire/Setting/ backups/setting-components/$(date +%Y%m%d_%H%M%S)/

# 2. Aplicar trait
cp app/Traits/OrganizationAccessControl.php app/Traits/

# 3. Aplicar modificaciones componente por componente
# (Seguir el patrón documentado)

# 4. Verificar que no hay errores de compilación
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

#### B. Plan de Rollback

```bash
# En caso de problemas, revertir cambios
BACKUP_DIR="backups/setting-components/YYYYMMDD_HHMMSS"
cp -r $BACKUP_DIR/* app/Http/Livewire/Setting/

# Remover trait si es necesario
rm app/Traits/OrganizationAccessControl.php

# Limpiar cache
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### 8. Monitoreo Post-Implementación

#### A. Logs a Monitorear

```php
// En cada componente, agregar logging
Log::info('Organization access check', [
    'user_id' => auth()->id(),
    'organization_id' => $this->organization_id,
    'configuration' => 'configuration.key',
    'access_granted' => $hasAccess
]);
```

#### B. Métricas de Acceso

```sql
-- Query para monitorear uso por plan
SELECT 
    o.plan_type,
    COUNT(*) as access_attempts,
    COUNT(CASE WHEN access_granted = 1 THEN 1 END) as successful_access
FROM organization_access_logs oal
JOIN organizations o ON oal.organization_id = o.id
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY o.plan_type;
```

### 9. Documentación para Usuarios

#### A. Guía de Restricciones por Plan

| Plan | Configuraciones Disponibles |
|------|----------------------------|
| Básico | Profile |
| Profesional | Profile, Import General, Import SQL Server, Extraction Invupos, Login Security |
| Premium | Todo lo anterior + Import Magaya/Fikable, Extraction Lightspeed, QuickBooks |
| Enterprise | Todas las configuraciones |

#### B. Mensajes de Error Personalizados

```php
// Mensajes en resources/lang/es/validation.php
'organization_access_denied' => 'Su plan :plan no incluye acceso a :configuration. Actualice a :required_plan o superior.',
'configuration_restricted' => 'Esta configuración está restringida a planes: :plans',
'upgrade_required' => 'Se requiere actualización de plan para acceder a esta funcionalidad.',
```

Esta guía proporciona un enfoque sistemático para implementar el control de acceso organizacional manteniendo la simetría del diseño y sin afectar la funcionalidad existente.
