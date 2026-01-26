# Nueva Estructura del Sidebar - DocuCenter

##  Estructura de Archivos Creada

```
resources/views/
├── layouts/
│   ├── app-new.blade.php           # NUEVO Layout completo con sidebar reorganizado
│   ├── sidebar-new.blade.php       # Sidebar principal reorganizado
│   ├── sidebar-header.blade.php    # Componente: Header con org
│   ├── sidebar-section.blade.php   # Componente: Sección con título
│   └── sidebar-item.blade.php      # Componente: Item individual
│
└── partials/menu/
    ├── principal.blade.php         # Sección: Dashboard
    ├── facturacion.blade.php       # Sección: Facturas + Config
    ├── integraciones.blade.php     # Sección: Conexiones + Tiendas
    ├── reportes.blade.php          # Sección: Reportes
    └── administracion.blade.php    # Sección: Sistema + Usuarios

config/
└── sidebar.php                     # Configuración y feature flag

app/Console/Commands/
└── ToggleSidebar.php               # Comando para activar/desactivar
```

## Cómo Activar el Nuevo Sidebar

### Opción 1: Usar el Nuevo Layout (Recomendado para Componentes Nuevos)
Al crear o actualizar componentes Livewire, usar el nuevo layout:
```php
// En tu componente Livewire
public function render()
{
    return view('livewire.tu-componente')
        ->layout('layouts.app-new', ['title' => __('Título')]);
}
```

### Opción 2: Variable de Entorno (Activación Global)
Agregar en `.env`:
```env
USE_NEW_SIDEBAR=true
```

### Opción 3: Comando Artisan (Más Fácil)
```bash
docker exec -it docucenter_laravel.test php artisan sidebar:toggle on
```

### Opción 4: Archivo de Configuración
Editar `config/sidebar.php`:
```php
'use_new_sidebar' => true,
```

### Opción 5: Cache Config (Producción)
```bash
docker exec -it docucenter_laravel.test php artisan config:cache
```

## Características Implementadas

### Componentes Modulares
- **sidebar-header**: Badge con nombre de organización
- **sidebar-section**: Secciones con títulos
- **sidebar-item**: Items con soporte para submenús y badges

### Organización por Contexto
1. **Principal**: Dashboard
2. **Facturación Electrónica**: Facturas emitidas, recibidas, configuraciones
3. **Integraciones**: Conexiones, bases de datos, tiendas
4. **Reportes**: Monitoreo de emisión
5. **Administración**: Usuarios, roles, catálogos, tokens, CRUD

### Colores del Sistema Actual
- Verde principal: `#166053`
- Gradiente activo: `linear-gradient(to right, #166053, #166040, #166030, #166020, #166010)`
- Sidebar oscuro: `#2c3e50` (skin6)
- Sombras verdes: `rgba(22, 96, 83, 0.21)`

### Funcionalidades Mantenidas
- Permisos dinámicos (`hasPermission()`)
- Rutas dinámicas (`getRouteName()`)
- Traducción (`__()`)
- Items activos automáticos
- Submenús colapsables
- Responsive design
- Logout funcional

## 🧪 Testing

### Opción A: Testing con Componente Específico (Recomendado)
Actualizar un componente Livewire para usar el nuevo layout:

```php
// Ejemplo: app/Http/Livewire/Admin/Einvoice/LightspeedSerieR/ManageShop.php
public function render()
{
    return view('livewire.admin.einvoice.lightspeed-serie-r.manage-shop')
        ->layout('layouts.app-new', ['title' => __('Gestión de Tienda')]);
}
```

### Opción B: Activación Global

#### Paso 1: Activar con Comando
```bash
docker exec -it docucenter_laravel.test php artisan sidebar:toggle on
```

#### Paso 2: Verificar en el Navegador
1. Acceder al dashboard
2. Verificar que aparece el badge de organización en el header
3. Verificar que las secciones están agrupadas correctamente
4. Probar la navegación entre módulos
5. Verificar que los permisos funcionan correctamente

#### Paso 3: Rollback si es Necesario
```bash
docker exec -it docucenter_laravel.test php artisan sidebar:toggle off
```

## Personalización

### Agregar Nueva Sección
Crear archivo en `resources/views/partials/menu/nueva-seccion.blade.php`:
```blade
<x-layouts.sidebar-section title="Nueva Sección">
    <x-layouts.sidebar-item 
        route="ruta.ejemplo"
        icon="fas fa-icon"
        label="Nuevo Item"
    />
</x-layouts.sidebar-section>
```

Incluir en `sidebar-new.blade.php`:
```blade
@include('partials.menu.nueva-seccion')
```

### Agregar Badge a Item
```blade
<x-layouts.sidebar-item 
    route="ruta.ejemplo"
    icon="fas fa-icon"
    label="Item con Badge"
    :badge="5"
/>
```

### Item con Submenú
Ver ejemplo en `facturacion.blade.php` (sección Configuración)

## Notas Importantes

1. **Bootstrap Collapse**: Los submenús usan Bootstrap collapse nativo
2. **Componentes Blade**: Usar sintaxis `<x-layouts.sidebar-item />` 
3. **Permisos**: Se mantienen todos los checks de permisos existentes
4. **Rutas**: Compatible con sistema de rutas dinámicas actual
5. **CSS Inline**: Estilos incluidos en componentes para facilitar carga

## Troubleshooting

### El sidebar no cambia
```bash
# Limpiar todas las caches
docker exec -it docucenter-app-1 php artisan config:clear
docker exec -it docucenter-app-1 php artisan view:clear
docker exec -it docucenter-app-1 php artisan cache:clear
```

### Error de componente no encontrado
Verificar que los archivos están en:
- `resources/views/layouts/`
- `resources/views/partials/menu/`

### Permisos no funcionan
Verificar que las funciones helper están disponibles:
- `hasPermission()`
- `getRouteName()`

## Próximos Pasos

### Fase 1: Testing Individual (Actual)
1. Crear nuevo layout `app-new.blade.php`
2.  Actualizar componentes específicos para usar `layouts.app-new`
3.  Testing en pantallas actualizadas
4.  Verificar permisos y funcionalidad

### Fase 2: Migración Progresiva
1.  Actualizar componentes de facturación
2.  Actualizar componentes de integraciones
3.  Actualizar componentes de administración
4.  Verificar cada módulo migrado

### Fase 3: Activación Global
1.  Activar globalmente con `sidebar:toggle on`
2.  Testing completo en todos los módulos
3.  Ajustes finales de UI si es necesario
4.  Documentar cambios para el equipo
5.  Desplegar a producción

##  Soporte

Si encuentras algún problema o necesitas agregar funcionalidad:
1. Verificar logs de Laravel
2. Revisar permisos de usuario actual
3. Verificar rutas en `routes/web.php`
4. Consultar documentación de Bootstrap 5 collapse
