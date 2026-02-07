# Resumen de Mejoras en Manejo de Errores de QuickBooks

## Problema Original

El error en `intuit_sync_errors` era truncado y poco claro:

```json
{
  "error": "API Error [http_error]: Error HTTP 400: ... (truncated...)",
  "step": 3
}
```

## Solución Implementada

### 1. Captura Completa de Errores HTTP

**Ubicación**: `app/Traits/UpdateIntuitOrdersTrait.php` → `registerInQuickBooks()`

**Antes**:
```php
return [
    'success' => false,
    'error' => "Error HTTP {$statusCode}: " . $e->getMessage(),
    'error_type' => 'http_error',
    'status_code' => $statusCode,
    'response_body' => $responseBody
];
```

**Ahora**:
```php
// Decodificar JSON completo
$responseData = json_decode($responseBody, true);

// Extraer información detallada
$detailedError = $responseData['error'] ?? null;
if (isset($responseData['details'])) {
    $detailedError .= ' | Detalles: ' . json_encode($responseData['details']);
}

return [
    'success' => false,
    'error' => "Error HTTP {$statusCode}: " . $e->getMessage() . " | API Response: {$detailedError}",
    'error_type' => 'http_error',
    'status_code' => $statusCode,
    'response_body' => $responseBody,
    'response_data' => $responseData,      // NUEVO
    'detailed_error' => $detailedError     // NUEVO
];
```

### 2. Nuevo Método Helper: formatIntuitError()

**Ubicación**: `app/Traits/UpdateIntuitOrdersTrait.php`

```php
protected function formatIntuitError(
    array $errorResponse, 
    string $context = '', 
    string|null $identifier = null
): string
```

**Ejemplo de salida**:
```
Contexto: Registro de Factura | 
ID/Número: FE0000006238 | 
Tipo: http_error | 
HTTP Status: 400 | 
Error: Error HTTP 400: Client error | 
Detalles API: Request failed with status code 400 - Missing field CustomerRef | 
Response Data: {"success":false,"error":"..."} | 
Timestamp: 2026-01-23T15:30:45.123Z
```

### 3. Actualización de Jobs

#### UpdateIntuitOrdersJob
```php
// ANTES
throw new \Exception("API Error [{$errorType}]: {$errorMessage} - Invoice: {$invoiceNumber}");

// AHORA
$formattedError = $this->formatIntuitError(
    $resultUpdateInvoice,
    'Registro de Factura',
    $invoiceNumber
);
throw new \Exception($formattedError);
```

#### UploadSalesIntuitJob
```php
// ANTES
Log::error("Error registrando factura", [
    'error_message' => $errorMessage,
    'api_response' => $resultUpdateInvoice
]);

// AHORA
$formattedError = $this->formatIntuitError(
    $resultUpdateInvoice,
    'Registro de Factura',
    $invoiceNumber
);
Log::error("Error registrando factura", [
    'formatted_error' => $formattedError,
    'api_response' => $resultUpdateInvoice
]);
```

#### SendSaleToQuickBooksJob
```php
// ANTES
throw new \Exception("API Error [{$errorType}]: {$errorMessage} - Sale: {$this->sale->getKey()}");

// AHORA
$formattedError = $this->formatIntuitError(
    $qbResponse,
    'Registro de Factura',
    $invoiceNumber
);
throw new \Exception($formattedError);
```

#### UpdateIntuitFEJob
```php
// ANTES
$this->handleRateLimitError($resultUpdateInvoice, 'feStepRegisterInvoice', $fe->id);

// AHORA
$formattedError = $this->formatIntuitError(
    $resultUpdateInvoice,
    'Registro de Factura FE',
    $invoiceNumber
);
$this->handleRateLimitError($resultUpdateInvoice, 'feStepRegisterInvoice', $fe->id, $formattedError);
```

### 4. Mejoras en handleSyncError()

**Ubicación**: `app/Jobs/Intuit/UpdateIntuitOrdersJob.php`

**Antes**:
```php
$salesHeader->update([
    'intuit_sync_status' => 'failed',
    'intuit_sync_errors' => json_encode([
        'error' => $errorMessage,
        'step' => $salesHeader->intuit_sync_step,
        'trace' => $e->getTraceAsString()
    ])
]);
```

**Ahora**:
```php
$errorData = [
    'error' => $errorMessage,
    'step' => $salesHeader->intuit_sync_step,
    'timestamp' => now()->toISOString(),
    'invoice_number' => $salesHeader->InvoiceNumber ?? 'N/A'
];

// Trace limitado solo en debug mode
if (config('app.debug')) {
    $errorData['trace'] = substr($e->getTraceAsString(), 0, 2000);
}

$salesHeader->update([
    'intuit_sync_status' => 'failed',
    'intuit_sync_errors' => json_encode($errorData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
]);

// Log mejorado
Log::error("Intuit Sync Error", [
    'organization_id' => $this->connectionModel->organization->id,
    'invoice_number' => $salesHeader->InvoiceNumber,
    'step_name' => $this->getStepName($salesHeader->intuit_sync_step),
    'has_intuit_customer_id' => !empty($salesHeader->intuit_customer_id)
]);
```

**Nuevo método agregado**:
```php
protected function getStepName($step): string
{
    $steps = [
        0 => 'Validación de datos',
        1 => 'Registro de cliente',
        2 => 'Registro de items',
        3 => 'Registro de factura',
        4 => 'Registro de pagos',
        5 => 'Finalización'
    ];
    
    return $steps[$step] ?? "Paso desconocido ({$step})";
}
```

## Comparación de Errores

### ANTES (Truncado y poco útil):
```json
{
  "error": "API Error [http_error]: Error HTTP 400: Client error: `POST https://...` resulted in a `400 Bad Request` response:\n{\"success\":false,\"error\":\"Request failed with status code 400\",\"data\":null,\"timestamp\":\"2026-01-23T02:05:27.478Z\",\"detai (truncated...)\n - Invoice: FE0000006238",
  "step": 3,
  "timestamp": "2026-01-23T02:05:27.496161Z"
}
```

### AHORA (Completo y estructurado):
```json
{
  "error": "Contexto: Registro de Factura | ID/Número: FE0000006238 | Tipo: http_error | HTTP Status: 400 | Error: Error HTTP 400: Client error: POST https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/create_invoice_quickbooks resulted in a 400 Bad Request response | Detalles API: Request failed with status code 400 - Missing required field: CustomerRef in request payload | Response Data: {\"success\":false,\"error\":\"Request failed with status code 400\",\"data\":null,\"timestamp\":\"2026-01-23T02:05:27.478Z\",\"details\":{\"field\":\"CustomerRef\",\"message\":\"Required field missing\"}} | Timestamp: 2026-01-23T02:05:27.496Z",
  "step": 3,
  "timestamp": "2026-01-23T02:05:27.496161Z",
  "invoice_number": "FE0000006238"
}
```

## Archivos Modificados

1. **app/Traits/UpdateIntuitOrdersTrait.php**
   - Mejorada captura de errores HTTP con decodificación JSON completa
   - Agregado método `formatIntuitError()`

2. **app/Jobs/Intuit/UpdateIntuitOrdersJob.php**
   - Uso de `formatIntuitError()` en `stepRegisterCustomer()`
   - Uso de `formatIntuitError()` en `stepRegisterInvoice()`
   - Uso de `formatIntuitError()` en `stepRegisterPayments()`
   - Mejorado `handleSyncError()` con más contexto
   - Agregado `getStepName()` para nombres descriptivos

3. **app/Jobs/Invupos/UploadSalesIntuitJob.php**
   - Uso de `formatIntuitError()` para items, facturas y pagos

4. **app/Jobs/SendSaleToQuickBooksJob.php**
   - Uso de `formatIntuitError()` en registro de facturas

5. **app/Jobs/Intuit/UpdateIntuitFEJob.php**
   - Uso de `formatIntuitError()` en `feStepRegisterInvoice()`
   - Uso de `formatIntuitError()` en `feStepRegisterPayments()`
   - Mejorado `handleRateLimitError()` para aceptar errores formateados

## Beneficios Clave

✅ **Errores completos**: No más mensajes truncados  
✅ **Información estructurada**: Contexto, tipo, status, detalles  
✅ **Mejor diagnóstico**: Identificar problema en segundos  
✅ **Logs mejorados**: Más contexto para debugging  
✅ **Compatibilidad**: No requiere cambios en BD  
✅ **Performance**: No afecta velocidad de jobs

## Testing

```bash
# Ver errores en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "Intuit"

# Consultar últimos errores en DB
docker exec -it docucenter-app-1 php artisan tinker
>>> $sale = \App\Models\SalesHeaderImp::whereNotNull('intuit_sync_errors')->latest()->first();
>>> echo $sale->intuit_sync_errors;
```

## Documentación

📄 **Guía completa**: `docs/optimizations/quickbooks-error-handling-improvements.md`
