# Panel de Monitoreo de Jobs - Resumen de Implementación

## 📋 Descripción

Panel administrativo completo para monitorear, gestionar y reintentar jobs del sistema de colas de Laravel/Redis.

## 🎯 Funcionalidades

### 1. **Dashboard con Estadísticas en Tiempo Real**
- ✅ Jobs pendientes en cola
- ✅ Jobs fallidos totales
- ✅ Jobs procesados hoy
- ✅ Tasa de éxito general

### 2. **Vista de Jobs Pendientes**
- ✅ Lista de jobs en cola Redis
- ✅ Cola asignada
- ✅ Posición en la cola
- ✅ Tiempo de espera

### 3. **Gestión de Jobs Fallidos**
- ✅ Tabla con todos los jobs fallidos
- ✅ Filtros por cola y búsqueda
- ✅ Ver detalles completos (payload + excepción)
- ✅ Reintentar jobs individuales
- ✅ Reintentar todos los jobs
- ✅ Eliminar jobs individuales
- ✅ Limpiar todos los jobs fallidos

### 4. **Información de Colas**
- ✅ Listado de colas disponibles con descripción
- ✅ Propósito de cada cola

## 📁 Archivos Creados

### 1. Componente Livewire
**Ubicación**: `app/Http/Livewire/Admin/System/JobMonitor.php`

**Métodos principales**:
```php
getStatistics()         // Estadísticas generales
getPendingJobs()        // Jobs en cola Redis
getFailedJobs()         // Jobs fallidos de DB
retryJob($id)          // Reintentar job individual
retryAllFailed()       // Reintentar todos
deleteFailedJob($id)   // Eliminar job
flushFailedJobs()      // Limpiar todos
viewJobDetails($id)    // Ver detalles completos
```

### 2. Vista Blade
**Ubicación**: `resources/views/livewire/admin/system/job-monitor.blade.php`

**Secciones**:
- 4 tarjetas de estadísticas con colores
- Tabs: Pendientes / Fallidos
- Filtros: Búsqueda y por cola
- Modal de detalles con payload y excepción
- Tabla de información de colas

### 3. Ruta
**Ubicación**: `routes/web.php`
```php
Route::prefix('system')->middleware(['dynamicAcl'])->name('system.')->group(function () {
    Route::get('/job_monitor', \App\Http\Livewire\Admin\System\JobMonitor::class)
          ->name('job_monitor');
});
```

### 4. Sidebar
**Ubicación**: `resources/views/partials/menu/administracion.blade.php`

Agregado después de "Process management":
```blade
@if(hasPermission(getRouteName().'.system.job_monitor', true))
<li class="sidebar-item-custom">
    <a href="@route(getRouteName().'.system.job_monitor')" class="menu-item">
        <i class="fas fa-clipboard-list"></i>
        <span class="hide-menu">{{ __('Job Monitor') }}</span>
    </a>
</li>
@endif
```

## 🚀 Uso

### Acceso
1. Iniciar sesión como administrador
2. Navegar a **Administración** → **Job Monitor**

### Monitorear Jobs
- Ver estadísticas en tiempo real
- Click en "Actualizar" para refrescar datos

### Gestionar Jobs Fallidos
1. Click en tab "Fallidos"
2. Usar filtros para buscar jobs específicos
3. Acciones disponibles:
   - **Ver**: Ver detalles completos del job
   - **Reintentar**: Volver a encolar el job
   - **Eliminar**: Eliminar el registro del job

### Acciones Masivas
- **Reintentar Todos**: Reintenta todos los jobs fallidos
- **Limpiar Todos**: Elimina todos los registros de jobs fallidos

## ⚙️ Configuración de Permisos

Agregar permiso en la tabla de permisos:
```sql
INSERT INTO permissions (name, display_name, description)
VALUES ('admin.system.job_monitor', 'Job Monitor', 'Acceso al panel de monitoreo de jobs');
```

Asignar al rol de administrador:
```sql
INSERT INTO permission_role (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name = 'admin.system.job_monitor';
```

## 🎨 Diseño

### Colores de Estadísticas
- **Pendientes**: Azul (info)
- **Fallidos**: Rojo (danger)
- **Procesados**: Verde (success)
- **Tasa de Éxito**: Primary

### Estados de Jobs
- **Pendientes**: Badge azul con cola
- **Fallidos**: Badge rojo con error
- **Completados**: Badge verde (futuro)

## 🔧 Colas Monitoreadas

| Cola | Descripción |
|------|-------------|
| `high` | Jobs de alta prioridad |
| `default` | Jobs generales del sistema |
| `sales` | Procesamiento de ventas |
| `create_sale_maxgym` | Ventas específicas de MaxGym |
| `create_sale_kart` | Ventas específicas de Kart21 |
| `create_sale_quickbooks` | Ventas de QuickBooks |
| `issue_mass_invoices` | Emisión masiva de facturas |
| `maintenance` | Tareas de mantenimiento |

## 📊 Comandos Artisan Relacionados

```bash
# Ver jobs pendientes
php artisan queue:work --once

# Ver jobs fallidos
php artisan queue:failed

# Reintentar job específico
php artisan queue:retry {job_id}

# Reintentar todos
php artisan queue:retry all

# Eliminar job fallido
php artisan queue:forget {job_id}

# Limpiar todos los fallidos
php artisan queue:flush
```

## 🐛 Troubleshooting

### Jobs no aparecen en pendientes
- Verificar que Redis esté corriendo: `redis-cli ping`
- Verificar supervisord: `sudo supervisorctl status`

### No se pueden reintentar jobs
- Verificar permisos de usuario
- Ver logs: `tail -f storage/logs/laravel.log`

### Estadísticas incorrectas
- Click en "Actualizar" para refrescar
- Verificar conexión a Redis
- Verificar tabla `failed_jobs` en BD

## 🔮 Mejoras Futuras

- [ ] Historial de jobs completados
- [ ] Gráficos de tendencias
- [ ] Alertas por email cuando hay muchos jobs fallidos
- [ ] Exportar jobs fallidos a CSV
- [ ] Programar ejecución de jobs
- [ ] Ver logs en tiempo real
- [ ] Integración con Laravel Horizon
- [ ] Notificaciones push cuando hay errores críticos

## 📝 Notas Técnicas

### Tabla failed_jobs
```sql
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### Redis Keys
- `queues:{queue_name}`: Lista de jobs en cola
- `queues:{queue_name}:reserved`: Jobs reservados
- `queues:{queue_name}:delayed`: Jobs con delay

## 🎓 Relacionado

- [Process Management](../processmanagement/) - Ejecutar comandos Artisan
- [Archive Monitor](../archive/) - Ver archivos del sistema
- [QuickBooks Sync Monitor](../reports/) - Monitor específico de QB

---

**Implementado**: 2026-02-13
**Versión**: 1.0.0
**Autor**: Sistema DocuCenter
