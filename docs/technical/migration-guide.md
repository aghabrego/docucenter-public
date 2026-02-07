# Guía de Migración: Actualizar Componentes al Nuevo Sidebar

## 📋 Objetivo
Migrar componentes Livewire progresivamente para usar el nuevo sidebar reorganizado sin afectar el resto del sistema.

## 🎯 Estrategia: Migración Progresiva

### ✅ Ventajas de este Enfoque
- No afecta componentes existentes
- Permite testing individual
- Rollback fácil por componente
- Sin riesgo para producción

## 🔧 Cómo Actualizar un Componente

### Antes (Layout Antiguo)
```php
// Ejemplo: app/Http/Livewire/Admin/Einvoice/LightspeedSerieR/ManageShop.php

public function render()
{
    return view('livewire.admin.einvoice.lightspeed-serie-r.manage-shop')
        ->layout('admin::layouts.app', ['title' => __('Gestión de Tienda')]);
}
```

### Después (Nuevo Layout)
```php
// Mismo archivo con UN cambio en el layout

public function render()
{
    return view('livewire.admin.einvoice.lightspeed-serie-r.manage-shop')
        ->layout('layouts.app-new', ['title' => __('Gestión de Tienda')]);
        // ↑ Solo cambia esto: admin::layouts.app → layouts.app-new
}
```

## 📝 Checklist de Migración

### Por Componente:
- [ ] Identificar archivo del componente Livewire
- [ ] Cambiar `admin::layouts.app` por `layouts.app-new`
- [ ] Probar la ruta en el navegador
- [ ] Verificar que el menú se ve correctamente
- [ ] Verificar que la navegación funciona
- [ ] Verificar permisos (si aplica)
- [ ] Commit del cambio

### Por Módulo:
- [ ] Migrar todos los componentes del módulo
- [ ] Testing completo del módulo
- [ ] Documentar cambios
- [ ] Marcar módulo como migrado

## 🗂️ Componentes por Migrar (Prioridad)

### 🟢 Alta Prioridad - Facturación Electrónica
```bash
# Componentes nuevos/recientes
app/Http/Livewire/Admin/Einvoice/LightspeedSerieR/ManageShop.php
app/Http/Livewire/Admin/Einvoice/Lists.php
app/Http/Livewire/Admin/Einvoice/ConsumerInvoice.php
app/Http/Livewire/Admin/Einvoice/Configuration.php
app/Http/Livewire/Admin/Einvoice/BranchClient.php
```

### 🟡 Media Prioridad - Integraciones
```bash
app/Http/Livewire/Admin/Connections/Lists.php
app/Http/Livewire/Admin/Databases/Lists.php
app/Http/Livewire/Admin/Tables/Lists.php
```

### 🔵 Baja Prioridad - Administración
```bash
app/Http/Livewire/Admin/Users/Lists.php
app/Http/Livewire/Admin/Role/Lists.php
app/Http/Livewire/Admin/Catalogos/Lists.php
app/Http/Livewire/Admin/PersonalAccess/Lists.php
```

## 🧪 Testing por Componente

### 1. Testing Visual
```bash
# Acceder a la ruta del componente
# Verificar:
- ✅ Sidebar reorganizado aparece
- ✅ Badge de organización visible
- ✅ Secciones agrupadas correctamente
- ✅ Item actual resaltado en verde
- ✅ Submenús funcionan
```

### 2. Testing Funcional
```bash
# Probar funcionalidad del componente
- ✅ CRUD funciona
- ✅ Formularios funcionan
- ✅ Navegación a otros módulos
- ✅ Permisos respetados
```

### 3. Testing de Navegación
```bash
# Desde el componente migrado:
- ✅ Navegar a Dashboard
- ✅ Navegar a otros módulos
- ✅ Logout funciona
- ✅ Cambio de organización (si aplica)
```

## 📊 Seguimiento de Migración

### Estado Actual
```
Total Componentes: ~50
Migrados: 0
Pendientes: 50
Progreso: 0%
```

### Componentes Migrados
- [ ] ManageShop (Lightspeed Serie R)
- [ ] [Agregar aquí según se migren]

## 🚀 Ejemplo Completo: Migrar ManageShop

### Paso 1: Localizar el Componente
```bash
cd /home/weirdolabs/code/docucenter
code app/Http/Livewire/Admin/Einvoice/LightspeedSerieR/ManageShop.php
```

### Paso 2: Actualizar el Layout
```php
// Buscar el método render()
public function render()
{
    return view('livewire.admin.einvoice.lightspeed-serie-r.manage-shop')
        ->layout('layouts.app-new', ['title' => __('Gestión de Tienda (Serie R)')]);
}
```

### Paso 3: Testing
```bash
# 1. Acceder a la ruta
http://localhost/admin/einvoice/lightspeed-serie-r-shop

# 2. Verificar visualmente
- Badge de organización: ✅
- Sidebar reorganizado: ✅
- Sección "Facturación Electrónica" visible: ✅
- Item "Lightspeed Serie R" resaltado: ✅

# 3. Probar funcionalidad
- Cargar organizaciones: ✅
- Guardar configuración: ✅
- Validaciones: ✅
```

### Paso 4: Commit
```bash
git add app/Http/Livewire/Admin/Einvoice/LightspeedSerieR/ManageShop.php
git commit -m "refactor: migrar ManageShop al nuevo sidebar reorganizado"
```

## ⚠️ Precauciones

### NO Hacer:
- ❌ NO cambiar todos los componentes al mismo tiempo
- ❌ NO eliminar el layout antiguo todavía
- ❌ NO modificar componentes sin testing
- ❌ NO afectar rutas en producción sin validar

### SÍ Hacer:
- ✅ Migrar componente por componente
- ✅ Testing individual de cada cambio
- ✅ Commit por cada componente migrado
- ✅ Documentar problemas encontrados
- ✅ Rollback si hay problemas

## 🔄 Rollback Individual

Si un componente presenta problemas después de migrar:

```php
// Simplemente revertir el cambio del layout
public function render()
{
    return view('livewire.admin.einvoice.lightspeed-serie-r.manage-shop')
        ->layout('admin::layouts.app', ['title' => __('Gestión de Tienda')]);
        // ↑ Volver al layout antiguo
}
```

O revertir el commit:
```bash
git revert HEAD
```

## 📞 Soporte

Si encuentras problemas durante la migración:
1. Verificar que el componente existe en el sidebar nuevo
2. Revisar permisos en `partials/menu/`
3. Verificar rutas en el archivo correspondiente
4. Consultar logs de Laravel
5. Hacer rollback si es necesario

## 🎯 Siguientes Pasos

1. **Comenzar con ManageShop** (ya está listo el código)
2. Testing completo
3. Si funciona bien, migrar siguiente componente
4. Repetir proceso
5. Cuando todos estén migrados, activar globalmente con `sidebar:toggle on`
