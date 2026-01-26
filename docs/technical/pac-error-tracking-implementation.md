# Sistema de Tracking de Errores PAC - Guía de Implementación

## Resumen

Sistema comprehensivo para capturar, almacenar y analizar errores de emisión PAC (tanto masivas como individuales).

## Componentes Implementados

### 1. Base de Datos

**Archivo**: `database/migrations/2025_12_17_000001_create_pac_emission_errors_table.php`

**Estructura**:
- `organization_id`: FK a organizations
- `invoice_id`: FK a sales_header_imp (nullable)
- `invoice_number`: Número de factura
- `pac_provider`: TheFactoryHKA, alanube, edocs
- `emission_type`: 'single' o 'bulk'
- `error_code`: Código del error del PAC
- `error_message`: Mensaje de error (TEXT)
- `error_details`: JSON con detalles adicionales
- `request_data`: JSON con datos enviados al PAC
- `response_data`: JSON con respuesta del PAC
- `attempt_number`: Número de intento
- `http_status_code`: Código HTTP de la respuesta
- `user_email`: Email del usuario que realizó la acción
- `occurred_at`: Timestamp del error

**Índices Optimizados**:
- `organization_id`
- `pac_provider`
- `occurred_at`
- Compuestos para búsquedas comunes

**Ejecutar Migración**:
```bash
# Iniciar contenedores Docker
docker-compose up -d

# Ejecutar migración
docker exec -it <nombre_contenedor_app> php artisan migrate --path=database/migrations/2025_12_17_000001_create_pac_emission_errors_table.php
```

### 2. Modelo Eloquent

**Archivo**: `app/Models/PacEmissionError.php`

**Relaciones**:
- `organization()`: BelongsTo Organization
- `invoice()`: BelongsTo SalesHeaderImp (nullable)

**Scopes**:
```php
// Filtrar por organización
PacEmissionError::forOrganization($orgId)->get();

// Filtrar por proveedor PAC
PacEmissionError::forProvider('TheFactoryHKA')->get();

// Filtrar por tipo de emisión
PacEmissionError::forEmissionType('bulk')->get();

// Errores recientes (últimas 24 horas por defecto)
PacEmissionError::recent(48)->get();

// Rango de fechas
PacEmissionError::dateRange('2025-01-01', '2025-01-31')->get();
```

**Métodos Helper**:
```php
$error = PacEmissionError::find($id);

// Resumen del error "[CODE] message"
$summary = $error->getErrorSummary();

// ¿Es recuperable? (timeout, network, 50x)
$isRetryable = $error->isRetryable();

// Campo específico que falló
$failedField = $error->getFailedField();

// Fecha formateada dd/mm/yyyy HH:ii:ss
$formatted = $error->getFormattedOccurredAt();
```

### 3. Servicio Centralizado

**Archivo**: `app/Services/PacErrorCaptureService.php`

**Métodos Principales**:

#### `captureError(array $params)`
Captura genérica de error con todos los detalles.

```php
PacErrorCaptureService::captureError([
    'organization_id' => 123,
    'invoice_id' => 456,
    'invoice_number' => 'FE-001-00001234',
    'pac_provider' => 'TheFactoryHKA',
    'emission_type' => 'single',
    'error_code' => 'VAL_001',
    'error_message' => 'Error en validación',
    'error_details' => ['field' => 'receptor_ruc'],
    'request_data' => [...],
    'response_data' => [...],
    'attempt_number' => 1,
    'http_status_code' => 400,
]);
```

#### `parseErrorFromException(\Exception $e)`
Extrae detalles de una excepción PHP.

```php
try {
    // Código que puede fallar
} catch (\Exception $e) {
    $parsed = PacErrorCaptureService::parseErrorFromException($e, 'TheFactoryHKA');
    // $parsed contiene: error_code, error_message, http_status_code, error_details
}
```

#### `parseErrorFromResponse($response, string $pacProvider)`
Parsea respuesta del PAC específico.

```php
$response = json_decode($pacResponse, true);
$parsed = PacErrorCaptureService::parseErrorFromResponse($response, 'alanube');
```

#### `getErrorStatistics(int $organizationId, int $days = 30)`
Obtiene estadísticas para el dashboard.

```php
$stats = PacErrorCaptureService::getErrorStatistics($orgId, 30);
// Retorna:
// - total_errors
// - errors_by_provider
// - errors_by_type
// - most_common_errors
// - retryable_errors
// - period_days
```

#### `captureFromBulkEmission()`
Helper específico para emisiones masivas.

```php
PacErrorCaptureService::captureFromBulkEmission(
    $invoice,
    $exception,
    $organizationId,
    $pacProvider,
    $attemptNumber,
    $requestData
);
```

#### `captureFromSingleEmission()`
Helper específico para emisiones individuales.

```php
PacErrorCaptureService::captureFromSingleEmission(
    $organizationId,
    $invoiceNumber,
    $exception,
    $pacProvider,
    $requestData,
    $responseData
);
```

### 4. Integración en IssueMassInvoicesJob

**Archivo**: `app/Jobs/IssueMassInvoicesJob.php`

**Implementación**:
```php
use App\Services\PacErrorCaptureService;

// En el catch block (línea ~217):
try {
    // Código de emisión
} catch (\Exception $th) {
    // Capturar error en sistema de tracking
    try {
        PacErrorCaptureService::captureFromBulkEmission(
            $invoice,
            $th,
            $this->organizationId,
            $organization->pacConnection?->pac_type ?? 'unknown',
            ($invoice->mass_emission_attempts ?? 0) + 1,
            [
                'emission_date' => $this->emissionDate ?? now()->format('Y-m-d'),
                'invoice_data' => [
                    'id' => $invoice->ID,
                    'number' => $invoice->InvoiceNumber,
                    'customer' => $invoice->CustomerName,
                    'total' => $invoice->TotalAmount,
                ]
            ]
        );
    } catch (\Exception $captureException) {
        Log::error("Error al capturar error PAC", [
            'invoice_id' => $invoice->ID,
            'capture_error' => $captureException->getMessage()
        ]);
    }
    
    // Resto de la lógica de retry
}
```

### 5. Dashboard Livewire

**Archivo**: `app/Http/Livewire/Admin/Reports/PacErrorsDashboard.php`

**Características**:
- Tarjetas de estadísticas (total errores, errores recuperables, PAC más afectado, tipos de error)
- Filtros:
  - Rango de fechas
  - Proveedor PAC
  - Tipo de emisión (single/bulk)
  - Código de error
  - Número de factura
- Tabla de errores con:
  - Fecha, tipo, PAC, factura, cliente, error, estado, intentos
  - Badge de "Retryable" o "Failed"
  - Botón para ver detalles
- Exportación a CSV
- Modal con detalles completos del error (request_data, response_data, stack trace)

**Ruta**: `/admin/reports/pac_errors`

### 6. Vista Blade

**Archivo**: `resources/views/livewire/admin/reports/pac-errors-dashboard.blade.php`

**Componentes**:
- 4 cards de estadísticas en la parte superior
- Panel de filtros con 6 campos
- Tabla responsiva con paginación
- Modal Bootstrap para detalles
- JavaScript para cargar detalles vía AJAX

### 7. Rutas

**Web Route** (`routes/web.php`):
```php
Route::prefix('reports')->middleware(['dynamicAcl', 'check.active.organization'])->name('reports.')->group(function () {
    Route::get('/pac_errors', \App\Http\Livewire\Admin\Reports\PacErrorsDashboard::class)->name('pac_errors');
});
```

**API Route** (`routes/api.php`):
```php
Route::get('/pac-errors/{id}', function ($id) {
    $error = \App\Models\PacEmissionError::find($id);
    
    if (!$error) {
        return response()->json(['error' => 'Not found'], 404);
    }

    // Verificar que el usuario tenga acceso a esta organización
    if ($error->organization_id !== auth()->user()->organization->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return response()->json($error);
});
```

## Integración en Emisiones Individuales (Pendiente)

### Archivos a Modificar

1. **app/Http/Livewire/Admin/Einvoice/Create.php**
2. **app/Http/Livewire/Admin/Einvoice/CreateFast.php**
3. **app/Http/Livewire/Admin/Einvoice/CreateFastJob.php**

### Patrón Recomendado

**Para errores con excepciones**:
```php
use App\Services\PacErrorCaptureService;

try {
    // Emisión PAC
    $content = $HKAService->wsConn($objectInvoice);
} catch (\Exception $th) {
    // Capturar error
    PacErrorCaptureService::captureFromSingleEmission(
        $this->organization_id,
        $this->numeroDocumentoFiscal,
        $th,
        $connection->pac_type ?? 'unknown',
        json_decode(json_encode($request), true),
        null
    );
    
    throw $th; // Re-lanzar para manejo normal
}
```

**Para errores en respuestas (sin excepción)**:
```php
$status = array_get($message, "resultado", null);

if ($status !== 'procesado') {
    // Crear excepción para captura
    $exception = new \Exception($text ?? 'Error en emisión PAC');
    
    PacErrorCaptureService::captureFromSingleEmission(
        $this->organization_id,
        $this->numeroDocumentoFiscal,
        $exception,
        $connection->pac_type ?? 'unknown',
        json_decode(json_encode($request), true),
        (array) $content
    );
}

// Continuar con validación normal
$this->validate([
    'pacName' => 'required|in:procesado'
], [
    'pacName.in' => $text,
]);
```

## Sidebar Navigation (Pendiente)

Agregar entrada al sidebar de admin en `config/sidebar.php` o donde esté definido:

```php
[
    'title' => 'PAC Errors',
    'route' => 'admin.reports.pac_errors',
    'icon' => 'mdi mdi-alert-circle',
    'permission' => 'reports.pac_errors',
]
```

## Testing

### Prueba Manual

1. Iniciar Docker: `docker-compose up -d`
2. Ejecutar migración
3. Acceder a: `http://localhost/admin/reports/pac_errors`
4. Realizar emisión con error intencional para ver captura
5. Verificar dashboard muestra el error
6. Probar filtros
7. Exportar CSV
8. Ver detalles del error en modal

### Datos de Prueba

```php
// Crear error de prueba en tinker
PacErrorCaptureService::captureError([
    'organization_id' => 1,
    'invoice_number' => 'TEST-001-00001',
    'pac_provider' => 'TheFactoryHKA',
    'emission_type' => 'single',
    'error_code' => 'TEST_ERROR',
    'error_message' => 'Error de prueba del sistema',
    'error_details' => ['test' => true],
    'attempt_number' => 1,
]);
```

## Beneficios

1. **Visibilidad completa**: Todos los errores PAC en un solo lugar
2. **Análisis histórico**: Identificar patrones y problemas recurrentes
3. **Debugging mejorado**: Request/response completos almacenados
4. **Métricas**: Estadísticas por PAC, tipo, período
5. **Exportable**: CSV para análisis externo
6. **Recuperabilidad**: Identificación automática de errores recuperables

## Próximos Pasos

1. Migración ejecutada
2.  Integrar en emisiones individuales (Create.php, CreateFast.php, CreateFastJob.php)
3.  Agregar al sidebar de navegación
4.  Configurar permisos ACL
5.  Testing con errores reales
6.  Documentación de usuario final

## Notas Importantes

- **Conexión DB**: El sistema maneja multi-tenant automáticamente
- **Performance**: Índices optimizados para búsquedas frecuentes
- **Tamaño de datos**: JSON truncado automáticamente si excede 100KB
- **Seguridad**: API endpoint verifica organización del usuario
- **Resiliencia**: Si falla la captura del error, no afecta el flujo principal
