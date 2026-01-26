# Sistema de Control de Acceso Organizacional para Configuraciones

## Resumen Ejecutivo

Este documento describe la implementación del sistema de control de acceso organizacional para los componentes de configuración en `App\Http\Livewire\Setting`, manteniendo la simetría del diseño mientras proporciona verificación granular de permisos basada en planes de organización.

## Arquitectura del Sistema

### 1. Trait OrganizationAccessControl

**Ubicación**: `app/Traits/OrganizationAccessControl.php`

**Propósito**: Proporcionar métodos reutilizables para verificar acceso a configuraciones basado en el plan de la organización.

#### Métodos Principales:

```php
// Verificar si el usuario puede acceder a una configuración específica
public function checkConfigurationAccess(string $configKey): void

// Verificar si una organización específica puede acceder a una configuración
public function canOrganizationAccessConfiguration(int $organizationId, string $configKey): bool

// Obtener organizaciones que tienen acceso a una configuración específica
public function getOrganizationsWithAccess(string $configKey): Collection

// Obtener todas las configuraciones disponibles para el usuario actual
public function getAvailableConfigurations(): array

// Obtener configuración del menú de settings filtrada por acceso
public function getSettingsMenuConfiguration(): array
```

#### Matriz de Acceso por Plan:

| Configuración | Basic | Professional | Premium | Enterprise |
|---------------|-------|--------------|---------|------------|
| Profile |  |  |  |  |
| General |  |  |  |  |
| Login Security | - |  |  |  |
| Import SQL Server | - |  |  |  |
| Import Magaya | - | - |  |  |
| Import Fikable | - | - |  |  |
| Extraction Invupos | - |  |  |  |
| Extraction Lightspeed | - | - |  |  |
| Integration QuickBooks | - | - |  |  |
| Integration Shopify | - | - | - |  |
| Advanced Settings | - | - | - |  |

## Implementación en Componentes

### 2. Patrón de Integración

Cada componente Livewire en `Setting` debe seguir este patrón:

```php
<?php

namespace App\Http\Livewire\Setting;

use Livewire\Component;
use App\Traits\OrganizationAccessControl;

class ConfigurationComponent extends Component
{
    use OrganizationAccessControl;

    public function mount()
    {
        // Verificar acceso al montar el componente
        $this->checkConfigurationAccess('configuration.key');
    }

    public function onSelectOrganization()
    {
        if (!empty($this->organization_id)) {
            // Verificar acceso de organización específica
            if (!$this->canOrganizationAccessConfiguration($this->organization_id, 'configuration.key')) {
                $this->addError('organization_id', $this->getRestrictionMessage('configuration.key'));
                return;
            }
            // ... resto de la lógica
        }
    }

    public function update()
    {
        // Verificar acceso antes de actualizar
        $this->checkConfigurationAccess('configuration.key');
        
        if (!$this->canOrganizationAccessConfiguration($this->organization_id, 'configuration.key')) {
            $this->addError('organization_id', $this->getRestrictionMessage('configuration.key'));
            return;
        }
        // ... resto de la lógica de actualización
    }

    public function render()
    {
        $organizationsWithAccess = $this->getOrganizationsWithAccess('configuration.key');
        $availableConfigurations = $this->getAvailableConfigurations();
        $settingsMenu = $this->getSettingsMenuConfiguration();

        return view('livewire.setting.component', [
            'organizationsWithAccess' => $organizationsWithAccess,
            'availableConfigurations' => $availableConfigurations,
            'settingsMenu' => $settingsMenu,
            'title' => __('Component Title')
        ]);
    }
}
```

### 3. Claves de Configuración

Sistema jerárquico de claves para identificar configuraciones:

```php
private array $configurationAccess = [
    // Configuraciones básicas (disponibles para todos los planes)
    'profile' => ['basic', 'professional', 'premium', 'enterprise'],
    'general' => ['basic', 'professional', 'premium', 'enterprise'],
    
    // Configuraciones profesionales
    'security.login' => ['professional', 'premium', 'enterprise'],
    'import.sqlserver' => ['professional', 'premium', 'enterprise'],
    'extraction.invupos' => ['professional', 'premium', 'enterprise'],
    
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

## Modificaciones en Componentes Existentes

### 4. Profile.php - Implementación Base

```php
<?php

namespace App\Http\Livewire\Setting;

use Livewire\Component;
use App\Traits\SetLocale;
use App\Traits\OrganizationAccessControl;

class Profile extends Component
{
    use SetLocale, OrganizationAccessControl;

    public function mount()
    {
        $this->setCurrentLocale();
        $this->checkConfigurationAccess('profile');
    }

    public function render()
    {
        $availableConfigurations = $this->getAvailableConfigurations();
        $settingsMenu = $this->getSettingsMenuConfiguration();

        return view('livewire.setting.profile', [
            'availableConfigurations' => $availableConfigurations,
            'settingsMenu' => $settingsMenu,
            'title' => __('Profile Settings')
        ]);
    }
}
```

### 5. Import.php - Control de Acceso Avanzado

```php
public function mount()
{
    $this->setCurrentLocale();
    $this->checkConfigurationAccess('import.sqlserver');
}

public function onSelectOrganization()
{
    if (!empty($this->organization_id)) {
        if (!$this->canOrganizationAccessConfiguration($this->organization_id, 'import.sqlserver')) {
            $this->addError('organization_id', $this->getRestrictionMessage('import.sqlserver'));
            return;
        }
        // ... resto de la lógica
    }
}
```

## Integración con Vistas

### 6. Variables Disponibles en Blade

Todas las vistas de configuración tendrán acceso a:

```php
@php
    $availableConfigurations = $availableConfigurations ?? [];
    $settingsMenu = $settingsMenu ?? [];
    $organizationsWithAccess = $organizationsWithAccess ?? collect();
@endphp

<!-- Mostrar solo organizaciones con acceso -->
<select wire:model="organization_id">
    @foreach($organizationsWithAccess as $org)
        <option value="{{ $org->id }}">{{ $org->name }}</option>
    @endforeach
</select>

<!-- Menú de configuraciones filtrado -->
<nav class="settings-menu">
    @foreach($settingsMenu as $section => $configs)
        <div class="menu-section">
            <h3>{{ __(ucfirst($section)) }}</h3>
            @foreach($configs as $config)
                <a href="{{ route('setting.' . $config['route']) }}" 
                   class="{{ $config['active'] ? 'active' : '' }}">
                    {{ $config['name'] }}
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
```

## Manejo de Errores y Restricciones

### 7. Mensajes de Error Personalizados

```php
private function getRestrictionMessage(string $configKey): string
{
    $planNames = [
        'basic' => 'Básico',
        'professional' => 'Profesional', 
        'premium' => 'Premium',
        'enterprise' => 'Empresarial'
    ];

    $requiredPlans = $this->configurationAccess[$configKey] ?? [];
    $planList = collect($requiredPlans)->map(fn($plan) => $planNames[$plan] ?? $plan)->implode(', ');

    return __('Esta configuración requiere un plan: :plans', ['plans' => $planList]);
}
```

### 8. Excepciones Personalizadas

```php
// En caso de acceso denegado crítico
if (!$this->hasConfigurationAccess($configKey)) {
    abort(403, __('No tienes permisos para acceder a esta configuración.'));
}
```

## Testing y Validación

### 9. Tests de Acceso

```php
/** @test */
public function basic_plan_can_only_access_basic_configurations()
{
    $organization = Organization::factory()->create(['plan_type' => 'basic']);
    $user = User::factory()->create();
    $user->organizations()->attach($organization->id);

    $this->actingAs($user);
    
    // Puede acceder a profile
    $response = $this->get(route('setting.profile'));
    $response->assertStatus(200);
    
    // No puede acceder a import
    $response = $this->get(route('setting.import'));
    $response->assertStatus(403);
}
```

## Beneficios del Sistema

### 10. Ventajas de la Implementación

1. **Mantenimiento de Simetría**: Todos los componentes mantienen la misma estructura y diseño
2. **Control Granular**: Verificación a nivel de organización y configuración específica
3. **Escalabilidad**: Fácil agregar nuevas configuraciones y planes
4. **Reutilización**: Trait compartido entre todos los componentes
5. **Seguridad**: Verificación en múltiples puntos (mount, update, organization selection)
6. **UX Mejorada**: Solo muestra opciones disponibles para cada plan

### 11. Flujo de Verificación

```mermaid
graph TD
    A[Usuario accede a Setting] --> B[mount() ejecuta checkConfigurationAccess()]
    B --> C{¿Tiene acceso?}
    C -->|No| D[Redirect con error 403]
    C -->|Sí| E[Renderizar componente]
    E --> F[Usuario selecciona organización]
    F --> G[onSelectOrganization() verifica acceso específico]
    G --> H{¿Organización tiene acceso?}
    H -->|No| I[Mostrar error en UI]
    H -->|Sí| J[Cargar configuración]
    J --> K[Usuario actualiza configuración]
    K --> L[update() verifica acceso nuevamente]
    L --> M[Guardar cambios]
```

## Mantenimiento y Extensión

### 12. Agregar Nueva Configuración

1. Agregar clave en `$configurationAccess` del trait
2. Especificar planes que tienen acceso
3. Implementar el trait en el componente Livewire
4. Agregar llamadas de verificación en métodos críticos
5. Actualizar tests correspondientes

### 13. Modificar Planes de Acceso

```php
// En OrganizationAccessControl trait
private array $configurationAccess = [
    'nueva.configuracion' => ['premium', 'enterprise'],
    // ... resto de configuraciones
];
```

Este sistema proporciona un control de acceso robusto y escalable mientras mantiene la consistencia del diseño y la funcionalidad existente.
