# SQL Server Web Interface Module - Implementation Summary

## Overview
Se ha creado un módulo completo de interfaz web para la visualización y gestión de datos SQL Server, siguiendo el patrón del módulo Sage 50 existente.

## Componentes Implementados

### 1. STInvoices (Facturas SQL Server)
**Archivo**: `app/Http/Livewire/Admin/SqlServer/STInvoices.php`
**Vista**: `resources/views/livewire/admin/sql-server/st-invoices.blade.php`
**Ruta**: `/admin/sqlserver/st_invoices`

**Características**:
- Visualización de facturas desde el modelo `STInvoiceHeaders`
- Filtros avanzados: rango de fechas, vendor ID, store ID, invoice ID
- Selección de organización y configuración SQL Server
- Conexión directa a SQL Server usando configuración dinámica
- Tabla responsiva con información de facturas

### 2. STVendorsList (Proveedores SQL Server)
**Archivo**: `app/Http/Livewire/Admin/SqlServer/STVendorsList.php`
**Vista**: `resources/views/livewire/admin/sql-server/st-vendors-list.blade.php`
**Ruta**: `/admin/sqlserver/st_vendors`

**Características**:
- Listado de proveedores desde el modelo `STVendors`
- Filtros por: Vendor ID, nombre, email, teléfono
- Información de contacto completa (email, teléfono, dirección)
- Estado activo/excluido de proveedores
- Enlaces directos de email para contacto

### 3. STCostOfGoodsList (Costos de Mercancía)
**Archivo**: `app/Http/Livewire/Admin/SqlServer/STCostOfGoodsList.php`
**Vista**: `resources/views/livewire/admin/sql-server/st-cost-of-goods-list.blade.php`
**Ruta**: `/admin/sqlserver/st_cost_of_goods`

**Características**:
- Análisis de costos desde el modelo `STCostOfGoods`
- Filtros por: Item ID, nombre, categoría, sub-categoría, store ID, fechas
- Información de costos unitarios y totales
- Visualización de cantidades y fechas de transacción
- Categorización de productos

## Sidebar Navigation

### Estructura del Menú SQL Server
Se agregó un nuevo grupo "SQL Server" al sidebar con los siguientes elementos:

1. **Configuration Management** → `/admin/sqlserver/configuration`
2. **ST Invoices** → `/admin/sqlserver/st_invoices`
3. **ST Vendors** → `/admin/sqlserver/st_vendors`
4. **Cost of Goods** → `/admin/sqlserver/st_cost_of_goods`

### Detección de Módulo
```php
$isModuleSqlServer = hasTable([
    'configuration_sql_server_organizations', 
    'Purchase_Header_Imp', 
    'Purchase_Detail_Imp', 
    'Vendors_Imp', 
    'Products_Imp'
]);
```

## Rutas Configuradas

En `routes/web.php`:
```php
Route::prefix('sqlserver')->middleware(['dynamicAcl', 'check.active.organization'])->name('sqlserver.')->group(function () {
    Route::get('/configuration', \App\Http\Livewire\Admin\SqlServer\ManagementConfiguration::class)->name('configuration');
    Route::get('/st_invoices', \App\Http\Livewire\Admin\SqlServer\STInvoices::class)->name('st_invoices');
    Route::get('/st_vendors', \App\Http\Livewire\Admin\SqlServer\STVendorsList::class)->name('st_vendors');
    Route::get('/st_cost_of_goods', \App\Http\Livewire\Admin\SqlServer\STCostOfGoodsList::class)->name('st_cost_of_goods');
});
```

## Características Técnicas

### Conexión SQL Server Dinámica
Todos los componentes implementan conexión directa a SQL Server:
```php
// Configurar conexión SQL Server
$connectionName = 'sqlsrv_temp';
Config::set("database.connections.{$connectionName}", [
    'driver' => 'sqlsrv',
    'host' => $configuration->host,
    'port' => $configuration->port,
    'database' => $configuration->database,
    'username' => $configuration->username,
    'password' => Crypt::decryptString($configuration->password),
    'charset' => 'utf8',
    'prefix' => '',
]);
```

### Gestión de Organizaciones
- Selección dinámica de organización
- Carga automática de configuraciones SQL Server disponibles
- Cambio de contexto al seleccionar organización

### Sistema de Filtros
- Filtros específicos por cada tipo de dato
- Búsqueda en tiempo real con `wire:model`
- Función de limpiar filtros
- Limitación a 100 resultados para performance

### Manejo de Estados
- Estados de carga con spinners
- Mensajes flash para éxito/error
- Validación de conexiones SQL Server
- Logging detallado de operaciones

## Arquitectura de Vistas

### Patrón de Diseño Consistente
Todas las vistas siguen la misma estructura:
1. Mensajes flash (éxito/error)
2. Selector de organización y configuración
3. Panel de filtros expandible
4. Tabla de resultados responsiva
5. Indicadores de estado y contadores

### Responsive Design
- Uso de Bootstrap grid system
- Tablas responsivas con scroll horizontal
- Cards colapsables para filtros
- Iconografía consistente con Font Awesome

## Integración con Sistema Existente

### Permisos y Middleware
- Uso del middleware `dynamicAcl` para control de acceso
- Validación `check.active.organization` para contexto organizacional
- Verificación de permisos específicos por funcionalidad

### Logging y Monitoreo
```php
Log::info('SQL Server invoices cargadas', [
    'organization_id' => $this->selectedOrganization,
    'configuration_id' => $this->selectedConfiguration,
    'total_records' => count($this->invoices),
    'filters' => $filters
]);
```

### Multi-tenant Support
- Conexiones por organización
- Configuraciones específicas de SQL Server
- Aislamiento de datos por contexto organizacional

## Próximos Pasos Recomendados

1. **Testing**: Crear tests unitarios para componentes Livewire
2. **Theoretical Costs**: Implementar componente para `STTheoreticalCostofGoodsByDate`
3. **Process Monitoring**: Crear interfaz para monitoreo de procesos SQL Server
4. **Export Functions**: Agregar funcionalidad de exportación de datos
5. **Permissions Setup**: Configurar permisos específicos en base de datos
6. **Performance Optimization**: Implementar paginación para datasets grandes

## Archivos Modificados/Creados

### Nuevos Componentes Livewire
- `app/Http/Livewire/Admin/SqlServer/STInvoices.php`
- `app/Http/Livewire/Admin/SqlServer/STVendorsList.php`
- `app/Http/Livewire/Admin/SqlServer/STCostOfGoodsList.php`

### Nuevas Vistas Blade
- `resources/views/livewire/admin/sql-server/st-invoices.blade.php`
- `resources/views/livewire/admin/sql-server/st-vendors-list.blade.php`
- `resources/views/livewire/admin/sql-server/st-cost-of-goods-list.blade.php`

### Archivos Modificados
- `routes/web.php` - Rutas del módulo SQL Server
- `resources/views/vendor/admin/layouts/child-sidebar-menu-custom.blade.php` - Navegación sidebar

Esta implementación proporciona una base sólida para la gestión de datos SQL Server con una interfaz web moderna y funcional, siguiendo las mejores prácticas del framework Laravel y patrones establecidos en el sistema DocuCenter.
