# QuickBooks Sync Monitor - Implementación

## Descripción

Panel de monitoreo para clientes que permite visualizar y gestionar el estado de sincronización de facturas con QuickBooks Online.

##  Archivos Creados

### 1. Componente Livewire
**Ubicación**: `app/Http/Livewire/Admin/Reports/QuickBooksSyncMonitor.php`

**Características**:
- Estadísticas en tiempo real de sincronización
- Filtros por fecha, estado y paso de sincronización
- Búsqueda por número de factura
- Reintento individual y masivo de facturas fallidas
- Paginación de resultados
- Multi-tenant (organización activa)

### 2. Vista Blade
**Ubicación**: `resources/views/livewire/admin/reports/quickbooks-sync-monitor.blade.php`

**Elementos**:
- 6 tarjetas de estadísticas (Total, Completadas, Fallidas, En proceso, Pendientes, % Éxito)
- Filtros interactivos
- Tabla responsive con estados y acciones
- Modales para ver detalles de errores
- Selección masiva para reintentos
- Mensajes flash de confirmación

### 3. Ruta
**Ubicación**: `routes/web.php`
```php
Route::get('/admin/reports/quickbooks_sync', QuickBooksSyncMonitor::class)
    ->name('admin.reports.quickbooks_sync');
```

## Funcionalidades

### Visualización
- **Estadísticas Generales**:
  - Total de facturas
  - Completadas exitosamente
  - Fallidas
  - En proceso
  - Pendientes
  - Tasa de éxito (%)

- **Detalles por Factura**:
  - Número de factura
  - Fecha
  - Cliente
  - Monto
  - Estado sync: `completed`, `failed`, `processing`, `null`
  - Paso actual (1-6)
  - Número de intentos
  - Fecha del último intento

### Filtros
- Rango de fechas (desde/hasta)
- Estado de sincronización
- Paso donde se encuentra
- Búsqueda por número de factura

### Acciones
1. **Reintentar Individual**: Botón por factura fallida
2. **Reintentar Seleccionadas**: Checkbox para selección múltiple
3. **Reintentar Todas las Fallidas**: Botón masivo con confirmación
4. **Ver Error Detallado**: Modal con información completa del error

## Pasos de Sincronización

| Paso | Descripción | Proceso |
|------|-------------|---------|
| 0 | No iniciado | Factura aún no procesada |
| 1 | Inicializando | Verificación inicial |
| 2 | Validando cliente | `ensureCustomerExists()` |
| 3 | Validando productos | `ensureProductsExist()` |
| 4 | Obteniendo SyncToken | `getSyncTokenQB()` |
| 5 | Enviando factura | `registerInvoiceQB()` |
| 6 | Completado | Sincronización exitosa |

## Estados de Sincronización

| Estado | Descripción | Badge |
|--------|-------------|-------|
| `null` | Sin sincronizar | Gris (secondary) |
| `processing` | En proceso | Amarillo (warning) |
| `completed` | Exitosa | Verde (success) |
| `failed` | Fallida | Rojo (danger) |

##  Seguridad y Permisos

- **Middleware**: `dynamicAcl`, `check.active.organization`
- **Alcance**: Solo facturas de la organización activa
- **Usuarios**: Clientes con acceso a reportes
- **Base de Datos**: Usa la BD específica de la organización

## Métodos Principales

### `loadStatistics()`
Carga estadísticas agregadas de sincronización.

### `retryInvoice($invoiceId)`
Reintenta una factura individual que falló.
- Verifica que esté en estado `failed`
- Reset de estado y contadores
- Dispatch de `SendSaleToQuickBooksJob`

### `retrySelected()`
Reintenta facturas seleccionadas mediante checkboxes.

### `retryAllFailed()`
Reintenta todas las facturas con estado `failed`.

### `getStepName($step)`
Retorna nombre descriptivo del paso de sincronización.

### `getStatusBadgeClass($status)`
Retorna clase CSS Bootstrap para el badge de estado.

## Columnas Requeridas en `Sales_Header_Imp`

```sql
intuit_sync_status VARCHAR(20) NULL     -- completed|failed|processing
intuit_sync_step INT NULL                -- 0-6
intuit_sync_errors TEXT NULL             -- Mensaje de error
intuit_sync_attempts INT NULL            -- Número de intentos
intuit_last_attempt DATETIME NULL        -- Fecha último intento
intuit_invoice_id BIGINT NULL            -- ID en QuickBooks
intuit_extracted_cufe VARCHAR(255) NULL  -- CUFE usado
```

## Uso

### Acceso
```
/admin/reports/quickbooks_sync
```

### Filtrar Fallidas
1. Seleccionar "Failed" en filtro de estado
2. Ver detalles del error en cada fila
3. Click en botón "Retry" para reintentar

### Reintento Masivo
1. Marcar checkboxes de facturas deseadas
2. Click en "Retry Selected"
3. O usar "Retry All Failed" para todas

## Consideraciones

1. **Columnas Faltantes**: Las columnas de tracking deben existir en la BD de la organización. Si no existen, ejecutar stub/migración correspondiente.

2. **Jobs en Cola**: Los reintentos se procesan en la cola `sales` con delay de 30 segundos.

3. **Límite de Intentos**: Configurado en `SendSaleToQuickBooksJob::$tries = 3`

4. **Timeout**: Configurado en `SendSaleToQuickBooksJob::$timeout = 600` (10 minutos)

5. **Logging**: Todos los eventos se registran en `storage/logs/laravel.log` con contexto completo.

## Troubleshooting

### No se muestran facturas
- Verificar que existan columnas `intuit_sync_*` en la tabla
- Verificar que haya facturas con `intuit_sync_status NOT NULL`
- Revisar filtros de fecha

### Error al reintentar
- Verificar que el Job `SendSaleToQuickBooksJob` exista
- Verificar que la cola `sales` esté activa
- Revisar logs en `storage/logs/laravel.log`

### Estadísticas en 0
- Verificar conexión a BD de la organización
- Verificar que existan datos en `Sales_Header_Imp`
- Revisar logs del componente

## Referencias

- Job Principal: `app/Jobs/SendSaleToQuickBooksJob.php`
- Trait: `app/Traits/UpdateIntuitOrdersTrait.php`
- Modelo: `app/Models/SalesHeaderImp.php`
- Documentación QB: `docs/technical/quickbooks-*`

## Testing

Para probar el panel:

1. Asegurarse que la organización tenga conexión QB activa
2. Tener facturas con estados de sync variados
3. Verificar que las columnas existan en la BD
4. Acceder a `/admin/reports/quickbooks_sync`
5. Probar filtros y reintentos

## Personalización

Para personalizar colores o estilos, editar:
- Badges de estado en `getStatusBadgeClass()`
- Tarjetas de estadísticas en la vista blade
- Clases CSS de Bootstrap 5

---

**Fecha de Implementación**: 26 de Diciembre, 2025
**Autor**: Equipo DocuCenter
**Versión**: 1.0.0
