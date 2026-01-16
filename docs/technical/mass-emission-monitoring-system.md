# Sistema de Monitoreo de Emisión Masiva - DocuCenter

## Resumen del Sistema Implementado

Este documento detalla el sistema completo de monitoreo y control de emisión masiva de facturas electrónicas implementado en DocuCenter.

## Componentes Implementados

### 1. Estructura de Base de Datos

#### Columnas agregadas a `Sales_Header_Imp` (todas las organizaciones)
```sql
ALTER TABLE Sales_Header_Imp ADD COLUMN mass_emission_status VARCHAR(20) DEFAULT 'pending';
ALTER TABLE Sales_Header_Imp ADD COLUMN mass_emission_attempts INT DEFAULT 0;
ALTER TABLE Sales_Header_Imp ADD COLUMN mass_emission_last_error TEXT NULL;
ALTER TABLE Sales_Header_Imp ADD COLUMN mass_emission_last_attempt TIMESTAMP NULL;
```

#### Estados del Sistema
- **pending**: Factura pendiente de emisión
- **processing**: Factura siendo procesada actualmente
- **success**: Factura emitida exitosamente
- **failed**: Factura falló después de máximos intentos
- **retry_needed**: Factura requiere reintento

### 2. Job Mejorado: `IssueMassInvoicesJob`

#### Ubicación
`app/Jobs/IssueMassInvoicesJob.php`

#### Funcionalidades Agregadas
- **Sistema de estados granulares** para tracking preciso
- **Manejo automático de reintentos** con backoff exponencial
- **Categorización de errores** para análisis detallado
- **Logging comprehensive** para debugging

#### Métodos Principales
```php
// Actualizar estado de factura específica
private function updateInvoiceStatus($invoiceId, $status, $error = null)

// Determinar si debe reintentar basado en el tipo de error
private function shouldRetry($error, $attempts)

// Categorizar tipos de errores para estadísticas
private function categorizeError($error)
```

### 3. Componente Livewire: `MassEmissionMonitor`

#### Ubicación
`app/Http/Livewire/Admin/Reports/MassEmissionMonitor.php`

#### Características
- **Estadísticas en tiempo real** actualizadas automáticamente
- **Filtrado avanzado** por estado, cliente, número de factura
- **Búsqueda de texto** en errores y detalles
- **Operaciones en lote** para reintentos masivos
- **Auto-refresh** cada 30 segundos
- **Paginación** para grandes volúmenes de datos

#### Propiedades Computadas
```php
public function getStatsProperty()    // Estadísticas en tiempo real
public function getInvoicesProperty() // Lista filtrada de facturas
```

#### Métodos de Acción
```php
public function retrySelected()       // Reintentar facturas seleccionadas
public function retryFailed()        // Reintentar todas las fallidas
public function resetFilters()       // Limpiar todos los filtros
```

### 4. Interfaz de Usuario

#### Ubicación
`resources/views/livewire/admin/reports/mass-emission-monitor.blade.php`

#### Características de la UI
- **Dashboard de estadísticas** con tarjetas informativas
- **Tabla interactiva** con selección múltiple
- **Filtros dinámicos** con búsqueda en tiempo real
- **Botones de acción masiva** para gestión eficiente
- **Diseño responsive** compatible con móviles
- **Iconografía intuitiva** para estados y acciones

#### Tarjetas de Estadísticas
1. **Total**: Total de facturas en el sistema
2. **Pendientes**: Facturas esperando procesamiento
3. **Procesando**: Facturas siendo emitidas actualmente
4. **Exitosas**: Facturas emitidas correctamente
5. **Fallidas**: Facturas que requieren atención
6. **Para Reintento**: Facturas listas para nuevo intento

### 5. Integración de Rutas

#### Ruta Principal
```php
Route::get('/admin/reports/mass_emission_monitor', MassEmissionMonitor::class)
     ->name('admin.reports.mass_emission_monitor');
```

#### URL de Acceso
```
http://localhost/admin/reports/mass_emission_monitor
```

## Flujo de Funcionamiento

### 1. Proceso de Emisión
```
Factura → pending → processing → success/failed/retry_needed
```

### 2. Sistema de Reintentos
- **Intento 1**: Inmediato
- **Intento 2**: Después de 5 minutos
- **Intento 3**: Después de 30 minutos
- **Intento 4+**: Después de 2 horas
- **Máximo**: 5 intentos antes de marcar como 'failed'

### 3. Categorización de Errores
- **Conexión**: Problemas de red, timeouts
- **Validación**: Errores de datos, formato incorrecto
- **PAC**: Problemas específicos del proveedor
- **Sistema**: Errores internos de la aplicación

## Casos de Uso

### 1. Monitoreo en Tiempo Real
Los usuarios pueden observar el progreso de emisiones masivas con actualizaciones automáticas cada 30 segundos.

### 2. Gestión de Errores
Identificación rápida de facturas problemáticas con detalles específicos del error para resolución eficiente.

### 3. Reintentos Inteligentes
Sistema automático de reintentos con backoff exponencial para optimizar recursos y evitar sobrecarga.

### 4. Análisis de Rendimiento
Estadísticas detalladas para evaluar la eficiencia del proceso de emisión y identificar cuellos de botella.

## Comandos de Testing

### Script de Prueba Completo
```bash
bash /home/weirdolabs/code/docucenter/docs/testing/test-mass-emission-system.sh
```

### Comando para Datos de Prueba
```bash
docker exec -it docucenter_laravel.test php artisan test:mass-emission-states
```

## Configuración y Mantenimiento

### Variables de Entorno Relevantes
```env
QUEUE_CONNECTION=redis
REDIS_HOST=docucenter_redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Permisos de Archivos
```bash
# Asegurar permisos correctos para componentes Livewire
sudo chown -R weirdolabs:weirdolabs resources/views/livewire/
sudo chmod -R 664 resources/views/livewire/
```

## Beneficios del Sistema

### 1. Visibilidad Completa
- Tracking en tiempo real del progreso de emisión
- Identificación inmediata de problemas
- Estadísticas detalladas para análisis

### 2. Gestión Eficiente de Errores
- Reintentos automáticos inteligentes
- Categorización de errores para resolución dirigida
- Operaciones en lote para correcciones masivas

### 3. Optimización de Recursos
- Sistema de backoff exponencial evita sobrecarga
- Procesamiento asíncrono mantiene responsividad
- Filtrado eficiente reduce carga de datos

### 4. Experiencia de Usuario Mejorada
- Interfaz intuitiva y responsiva
- Actualizaciones automáticas sin intervención manual
- Operaciones masivas con confirmación visual

## Próximos Pasos Recomendados

1. **Testing en Producción**: Ejecutar emisiones de prueba para validar funcionamiento
2. **Configuración de Notificaciones**: Implementar alertas para errores críticos
3. **Optimización de Performance**: Ajustar intervalos de refresh según carga
4. **Documentación de Usuario**: Crear guías para operadores del sistema

## Mantenimiento del Sistema

### Limpieza de Datos Históricos
```sql
-- Limpiar registros antiguos exitosos (opcional)
UPDATE Sales_Header_Imp 
SET mass_emission_status = NULL, 
    mass_emission_attempts = 0,
    mass_emission_last_error = NULL,
    mass_emission_last_attempt = NULL
WHERE mass_emission_status = 'success' 
AND mass_emission_last_attempt < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Monitoreo de Performance
- Supervisar el tiempo de respuesta de las consultas
- Verificar el uso de memoria durante picos de emisión
- Monitorear la cola Redis para evitar acumulación excesiva

---

**Fecha de Implementación**: Octubre 2025  
**Versión del Sistema**: Laravel 9+  
**Estado**: Listo para Producción ✅
