# Sistema de Monitoreo del Servidor

## Descripción

Sistema de monitoreo en tiempo real de los recursos del servidor implementado para DocuCenter. Permite visualizar métricas de disco, RAM y CPU con actualización automática.

## Ubicación

- **Ruta**: `/admin/reports/server_monitor`
- **Componente Livewire**: `App\Http\Livewire\Admin\Reports\ServerMonitor`
- **Servicio**: `App\Services\ServerMetricsService`
- **Vista**: `resources/views/livewire/admin/reports/server-monitor.blade.php`

## Características

### 1. Métricas de Disco
- Espacio total, usado y libre
- Porcentaje de uso
- Alertas visuales según nivel de uso

### 2. Métricas de RAM
- Memoria total, usada y libre
- Información de buffers y caché
- Porcentaje de uso
- Basado en `/proc/meminfo`

### 3. Métricas de CPU
- Load average (1, 5 y 15 minutos)
- Porcentaje de uso calculado
- Número de CPUs detectados
- Desglose de uso: usuario, sistema, idle
- Basado en `sys_getloadavg()` y `/proc/stat`

### 4. Información del Sistema
- Hostname
- Sistema operativo y versión
- Versión de PHP
- Uptime del servidor

### 5. Actualización Automática
- **Auto-refresh**: Actualización cada 5 segundos (configurable)
- **Botón manual**: Refresh individual por métrica
- **Toggle**: Activar/desactivar auto-refresh
- **Timestamp**: Muestra última actualización

### 6. Alertas Visuales
- **Verde (Success)**: < 60% de uso - Normal
- **Amarillo (Warning)**: 60-80% de uso - Advertencia
- **Rojo (Danger)**: > 80% de uso - Crítico

## Implementación Técnica

### Servicio de Métricas

```php
App\Services\ServerMetricsService
```

**Métodos principales:**
- `getDiskMetrics()`: Métricas de disco usando `disk_free_space()` y `disk_total_space()`
- `getMemoryMetrics()`: Métricas de RAM desde `/proc/meminfo`
- `getCpuMetrics()`: Métricas de CPU usando `sys_getloadavg()` y `/proc/stat`
- `getSystemInfo()`: Información general del sistema

### Componente Livewire

```php
App\Http\Livewire\Admin\Reports\ServerMonitor
```

**Propiedades:**
- `$diskMetrics`: Array con métricas de disco
- `$memoryMetrics`: Array con métricas de RAM
- `$cpuMetrics`: Array con métricas de CPU
- `$systemInfo`: Array con información del sistema
- `$autoRefresh`: Boolean para auto-actualización
- `$refreshInterval`: Intervalo en segundos (default: 5)

**Métodos:**
- `loadAllMetrics()`: Carga todas las métricas
- `refreshDisk()`: Actualiza solo métricas de disco
- `refreshMemory()`: Actualiza solo métricas de RAM
- `refreshCpu()`: Actualiza solo métricas de CPU
- `toggleAutoRefresh()`: Activa/desactiva actualización automática

### Polling de Livewire

La vista usa Livewire polling para actualización automática:

```blade
<div wire:poll.{{ $autoRefresh ? $refreshInterval : 9999 }}s="loadAllMetrics">
```

## Compatibilidad

### Producción (Sin Docker)
- ✅ Acceso directo a `/proc/meminfo`, `/proc/stat`, `/proc/cpuinfo`
- ✅ Métricas reales del servidor físico/VPS
- ✅ Todas las funcionalidades disponibles

### Desarrollo (Con Docker)
- ✅ Métricas del contenedor Docker
- ⚠️ No muestra métricas del host físico
- ℹ️ Para ver métricas del host se requiere bind mount de `/proc` o Docker API

## Instalación y Uso

### 1. Archivos Creados

```
app/
  ├── Services/
  │   └── ServerMetricsService.php
  └── Http/
      └── Livewire/
          └── Admin/
              └── Reports/
                  └── ServerMonitor.php

resources/
  └── views/
      └── livewire/
          └── admin/
              └── reports/
                  └── server-monitor.blade.php

scripts/
  └── test-server-monitor.sh
```

### 2. Ruta Agregada

En `routes/web.php`:

```php
Route::get('/server_monitor', \App\Http\Livewire\Admin\Reports\ServerMonitor::class)
    ->name('server_monitor');
```

### 3. Traducciones

Agregadas en:
- `lang/es_panel.json`
- `lang/en_panel.json`

### 4. Acceso

URL: `http://tu-dominio/admin/reports/server_monitor`

**Requiere:**
- Autenticación
- Middleware: `dynamicAcl` y `check.active.organization`

## Testing

### Script de Prueba

```bash
./scripts/test-server-monitor.sh
```

**Funcionalidad del script:**
- Detecta si está en Docker o no
- Prueba obtención de métricas de disco
- Prueba obtención de métricas de RAM
- Prueba obtención de métricas de CPU
- Prueba información del sistema
- Verifica el servicio `ServerMetricsService`

### Prueba Manual

1. Acceder a `/admin/reports/server_monitor`
2. Verificar que todas las métricas se muestran
3. Probar botones de refresh individual
4. Verificar auto-refresh activando/desactivando
5. Observar cambios de colores según uso

## Consideraciones de Seguridad

### Permisos de Archivo
El sistema lee archivos del sistema operativo:
- `/proc/meminfo` - Información de memoria
- `/proc/stat` - Estadísticas de CPU
- `/proc/cpuinfo` - Información de CPUs
- `/proc/uptime` - Uptime del sistema

Estos archivos son de solo lectura y seguros de acceder.

### Middleware
- `dynamicAcl`: Control de acceso basado en roles
- `check.active.organization`: Verificación de organización activa

### Logging
Todos los errores se registran en logs de Laravel:
```php
Log::error('Error obteniendo métricas...', ['error' => $e->getMessage()]);
```

## Personalización

### Cambiar Intervalo de Actualización

En el componente Livewire:

```php
public $refreshInterval = 5; // Cambiar a segundos deseados
```

### Cambiar Umbrales de Alerta

En `ServerMetricsService::getStatus()`:

```php
protected function getStatus(float $percentage): string
{
    if ($percentage < 60) {      // Cambiar umbrales
        return 'success';
    } elseif ($percentage < 80) {
        return 'warning';
    } else {
        return 'danger';
    }
}
```

### Agregar Nuevas Métricas

1. Agregar método en `ServerMetricsService`
2. Agregar propiedad en componente Livewire
3. Agregar método de refresh en componente
4. Agregar tarjeta en vista Blade

## Troubleshooting

### Problema: No se muestran métricas

**Solución:**
1. Verificar logs: `storage/logs/laravel.log`
2. Verificar permisos de lectura en `/proc/`
3. Verificar que el servicio esté correctamente inyectado

### Problema: Auto-refresh no funciona

**Solución:**
1. Verificar que Livewire esté correctamente configurado
2. Verificar JavaScript en consola del navegador
3. Revisar que `wire:poll` esté en la vista

### Problema: Métricas incorrectas en Docker

**Explicación:**
En Docker, las métricas son del contenedor, no del host. Esto es comportamiento esperado en desarrollo.

## Referencias

- [Laravel Livewire - Polling](https://laravel-livewire.com/docs/polling)
- [PHP - Filesystem Functions](https://www.php.net/manual/en/ref.filesystem.php)
- [Linux /proc filesystem](https://www.kernel.org/doc/Documentation/filesystems/proc.txt)

## Mantenimiento

### Actualizaciones Futuras Sugeridas

1. **Gráficos históricos**: Almacenar métricas en BD para históricos
2. **Alertas por email**: Notificar cuando se superan umbrales
3. **Múltiples servidores**: Monitorear varios servidores
4. **Métricas de aplicación**: Agregar métricas de Laravel (jobs, cache, etc.)
5. **Exportación de datos**: Exportar métricas a CSV/PDF

### Compatibilidad

- ✅ Laravel 9+
- ✅ Livewire 2.x
- ✅ PHP 8.0+
- ✅ Linux (Ubuntu, Debian, CentOS)
- ⚠️ Windows: Funcionalidad limitada (solo disco)
- ⚠️ macOS: Funcionalidad limitada

## Autor

Sistema implementado siguiendo las convenciones de DocuCenter:
- NO se usan emojis en código o commits
- Comentarios y documentación en español
- Cumple con middleware de seguridad
- Integrado con sistema de traducciones existente
