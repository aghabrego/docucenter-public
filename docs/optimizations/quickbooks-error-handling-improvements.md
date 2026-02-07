# Mejoras en el Manejo de Errores de Integraciones con QuickBooks

**Fecha**: 23 de enero de 2026  
**Versión**: 1.0  
**Tipo**: Optimización de Error Handling

## Problema Identificado

Los errores reportados en `intuit_sync_errors` no proporcionaban suficiente información para diagnosticar problemas con el API de QuickBooks. Específicamente:

```json
{
  "error": "API Error [http_error]: Error HTTP 400: Client error: `POST https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/create_invoice_quickbooks` resulted in a `400 Bad Request` response:\n{\"success\":false,\"error\":\"Request failed with status code 400\",\"data\":null,\"timestamp\":\"2026-01-23T02:05:27.478Z\",\"detai (truncated...)\n - Invoice: FE0000006238",
  "step": 3,
  "timestamp": "2026-01-23T02:05:27.496161Z"
}
```

### Problemas:
1. **Mensaje truncado**: La respuesta del API se corta con "(truncated...)"
2. **Información incompleta**: No se muestra el detalle completo del error HTTP 400
3. **Falta de contexto**: No se incluye información estructurada sobre qué falló exactamente

## Solución Implementada

### 1. Captura Mejorada de Errores HTTP en UpdateIntuitOrdersTrait

**Archivo**: `app/Traits/UpdateIntuitOrdersTrait.php`

#### Cambios en `registerInQuickBooks()`:

```php
} catch (\GuzzleHttp\Exception\RequestException $e) {
    $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
    $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
    
    // Intentar decodificar el JSON del cuerpo de la respuesta
    $responseData = null;
    $detailedError = null;
    if (!empty($responseBody)) {
        $responseData = json_decode($responseBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($responseData)) {
            // Extraer información detallada del error
            $detailedError = $responseData['error'] ?? $responseData['message'] ?? null;
            
            // Capturar detalles adicionales si existen
            if (isset($responseData['details'])) {
                $detailedError .= ' | Detalles: ' . json_encode($responseData['details']);
            }
            if (isset($responseData['data']) && !empty($responseData['data'])) {
                $detailedError .= ' | Data: ' . json_encode($responseData['data']);
            }
        }
    }

    // Construir mensaje de error completo y claro
    $errorMessage = "Error HTTP {$statusCode}: " . $e->getMessage();
    if ($detailedError) {
        $errorMessage .= " | API Response: {$detailedError}";
    }

    return [
        'success' => false,
        'error' => $errorMessage,
        'error_type' => 'http_error',
        'status_code' => $statusCode,
        'response_body' => $responseBody,
        'response_data' => $responseData,
        'detailed_error' => $detailedError
    ];
}
```

**Beneficios**:
- ✅ Captura completa de la respuesta del API sin truncar
- ✅ Decodificación automática de respuestas JSON
- ✅ Extracción de mensajes de error anidados
- ✅ Preservación de toda la información para debugging

### 2. Nuevo Método Helper: formatIntuitError()

**Ubicación**: `app/Traits/UpdateIntuitOrdersTrait.php`

```php
/**
 * Formatea un error para almacenarlo en intuit_sync_errors con información completa y legible
 *
 * @param array $errorResponse Respuesta del error de registerInQuickBooks
 * @param string $context Contexto adicional (ej: "Invoice", "Payment", "Customer")
 * @param string|null $identifier Identificador del registro (ej: número de factura)
 * @return string Error formateado para almacenamiento
 */
protected function formatIntuitError(array $errorResponse, string $context = '', string|null $identifier = null): string
{
    $errorParts = [];
    
    // Agregar contexto si existe
    if (!empty($context)) {
        $errorParts[] = "Contexto: {$context}";
    }
    
    // Agregar identificador si existe
    if (!empty($identifier)) {
        $errorParts[] = "ID/Número: {$identifier}";
    }
    
    // Tipo de error
    $errorType = $errorResponse['error_type'] ?? 'unknown';
    $errorParts[] = "Tipo: {$errorType}";
    
    // Status code si existe
    if (isset($errorResponse['status_code'])) {
        $errorParts[] = "HTTP Status: {$errorResponse['status_code']}";
    }
    
    // Mensaje principal del error
    $mainError = $errorResponse['error'] ?? 'Error desconocido';
    $errorParts[] = "Error: {$mainError}";
    
    // Agregar error detallado si existe
    if (isset($errorResponse['detailed_error']) && !empty($errorResponse['detailed_error'])) {
        $errorParts[] = "Detalles API: {$errorResponse['detailed_error']}";
    }
    
    // Agregar datos de respuesta si existen
    if (isset($errorResponse['response_data']) && is_array($errorResponse['response_data']) && !empty($errorResponse['response_data'])) {
        $filteredData = array_filter($errorResponse['response_data'], function($key) {
            return !in_array($key, ['response_body', 'trace', 'full_response']);
        }, ARRAY_FILTER_USE_KEY);
        
        if (!empty($filteredData)) {
            $errorParts[] = "Response Data: " . json_encode($filteredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
    
    // Timestamp
    $errorParts[] = "Timestamp: " . now()->toISOString();
    
    return implode(" | ", $errorParts);
}
```

**Formato de Salida**:
```
Contexto: Registro de Factura | ID/Número: FE0000006238 | Tipo: http_error | HTTP Status: 400 | Error: Error HTTP 400: Client error | Detalles API: Request failed with status code 400 | Data: {"validation_errors": [...]} | Timestamp: 2026-01-23T15:30:45.123Z
```

### 3. Actualización de Jobs de QuickBooks

Se actualizaron los siguientes jobs para usar el nuevo sistema:

#### UpdateIntuitOrdersJob
**Archivo**: `app/Jobs/Intuit/UpdateIntuitOrdersJob.php`

- ✅ `stepRegisterCustomer()` - Registro de clientes
- ✅ `stepRegisterInvoice()` - Registro de facturas
- ✅ `stepRegisterPayments()` - Registro de pagos
- ✅ `handleSyncError()` - Manejo centralizado de errores
- ✅ Agregado `getStepName()` - Nombres descriptivos de pasos

#### UploadSalesIntuitJob
**Archivo**: `app/Jobs/Invupos/UploadSalesIntuitJob.php`

- ✅ Registro de items
- ✅ Registro de facturas
- ✅ Registro de pagos

#### SendSaleToQuickBooksJob
**Archivo**: `app/Jobs/SendSaleToQuickBooksJob.php`

- ✅ Registro de facturas con error detallado

#### UpdateIntuitFEJob
**Archivo**: `app/Jobs/Intuit/UpdateIntuitFEJob.php`

- ✅ `feStepRegisterInvoice()` - Registro de facturas FE
- ✅ `feStepRegisterPayments()` - Registro de pagos FE
- ✅ `handleRateLimitError()` - Mejorado para usar errores formateados

### 4. Mejoras en handleSyncError()

**Antes**:
```php
protected function handleSyncError($salesHeader, \Exception $e)
{
    $salesHeader->update([
        'intuit_sync_status' => 'failed',
        'intuit_sync_errors' => json_encode([
            'error' => $e->getMessage(),
            'step' => $salesHeader->intuit_sync_step,
            'timestamp' => now()->toISOString(),
            'trace' => $e->getTraceAsString()
        ])
    ]);
}
```

**Después**:
```php
protected function handleSyncError($salesHeader, \Exception $e)
{
    $errorData = [
        'error' => $e->getMessage(),
        'step' => $salesHeader->intuit_sync_step,
        'timestamp' => now()->toISOString(),
        'invoice_number' => $salesHeader->InvoiceNumber ?? 'N/A'
    ];
    
    // Solo agregar trace si es necesario para debugging (limitado a 2000 caracteres)
    if (config('app.debug')) {
        $errorData['trace'] = substr($e->getTraceAsString(), 0, 2000);
    }

    $salesHeader->update([
        'intuit_sync_status' => 'failed',
        'intuit_sync_errors' => json_encode($errorData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    ]);

    // Log mejorado con más contexto
    Log::error("Intuit Sync Error for SalesHeader {$salesHeader->ID}", [
        'organization_id' => $this->connectionModel->organization->id ?? 'unknown',
        'invoice_number' => $salesHeader->InvoiceNumber ?? 'N/A',
        'step' => $salesHeader->intuit_sync_step,
        'step_name' => $this->getStepName($salesHeader->intuit_sync_step),
        'error' => $e->getMessage(),
        'customer_id' => $salesHeader->CustomerID ?? 'N/A',
        'has_intuit_customer_id' => !empty($salesHeader->intuit_customer_id),
        'has_intuit_invoice_id' => !empty($salesHeader->intuit_invoice_id)
    ]);
}
```

## Beneficios de las Mejoras

### 1. Diagnóstico Más Rápido
- **Antes**: Mensaje truncado sin información útil
- **Ahora**: Error completo con todos los detalles del API

### 2. Información Estructurada
```json
{
  "error": "Contexto: Registro de Factura | ID/Número: FE0000006238 | Tipo: http_error | HTTP Status: 400 | Error: Error HTTP 400: Client error | Detalles API: Request failed with status code 400 - Missing required field: CustomerRef | Timestamp: 2026-01-23T15:30:45.123Z",
  "step": 3,
  "timestamp": "2026-01-23T15:30:45.123456Z",
  "invoice_number": "FE0000006238"
}
```

### 3. Mejor Logging
```php
Log::error("UpdateIntuitOrdersJob: Fallo al registrar factura", [
    'organization_id' => 123,
    'sales_header_id' => 456,
    'invoice_number' => 'FE0000006238',
    'error_message' => 'Error HTTP 400...',
    'error_type' => 'http_error',
    'formatted_error' => 'Contexto: Registro de Factura | ...',
    'api_response' => [
        'success' => false,
        'status_code' => 400,
        'response_data' => [...]
    ]
]);
```

### 4. Trazabilidad Completa
- Paso específico donde falló (con nombre descriptivo)
- Número de factura/identificador afectado
- Contexto de la operación
- Respuesta completa del API
- Estado del registro en DB

## Uso del Nuevo Sistema

### Patrón en Jobs:

```php
// Llamar al método del API
$resultUpdateInvoice = $this->registerInvoiceQB($email, $password, $invoiceData);

// Verificar si falló
if (!$resultUpdateInvoice['success']) {
    // Formatear error con contexto
    $formattedError = $this->formatIntuitError(
        $resultUpdateInvoice,
        'Registro de Factura',
        $invoiceNumber
    );
    
    // Log detallado
    Log::error("Job: Fallo al registrar factura", [
        'invoice_number' => $invoiceNumber,
        'formatted_error' => $formattedError,
        'api_response' => $resultUpdateInvoice
    ]);
    
    // Lanzar excepción con error formateado
    throw new \Exception($formattedError);
}
```

## Testing

### Verificar Errores en Logs:
```bash
# Ver errores recientes de QuickBooks
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "Intuit"

# Buscar errores específicos
docker exec -it docucenter-app-1 php artisan tinker
>>> \App\Models\SalesHeaderImp::whereNotNull('intuit_sync_errors')->latest()->first()->intuit_sync_errors;
```

### Ejemplo de Error Formateado:
```
Contexto: Registro de Factura | ID/Número: FE0000006238 | Tipo: http_error | HTTP Status: 400 | Error: Error HTTP 400: Client error: POST https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/create_invoice_quickbooks resulted in a 400 Bad Request response | Detalles API: Request failed with status code 400 - Missing required field: CustomerRef in request payload | Response Data: {"success":false,"error":"Request failed with status code 400","data":null,"timestamp":"2026-01-23T02:05:27.478Z","details":{"field":"CustomerRef","message":"Required field missing"}} | Timestamp: 2026-01-23T02:05:27.496Z
```

## Próximos Pasos

1. ✅ Monitorear logs para validar que los errores ahora son claros
2. ⏳ Considerar agregar alertas automáticas para errores específicos
3. ⏳ Crear dashboard de errores de QuickBooks
4. ⏳ Documentar errores comunes y sus soluciones

## Archivos Modificados

- `app/Traits/UpdateIntuitOrdersTrait.php` - Captura mejorada y formatIntuitError()
- `app/Jobs/Intuit/UpdateIntuitOrdersJob.php` - Uso de formatIntuitError en todos los pasos
- `app/Jobs/Invupos/UploadSalesIntuitJob.php` - Uso de formatIntuitError
- `app/Jobs/SendSaleToQuickBooksJob.php` - Uso de formatIntuitError
- `app/Jobs/Intuit/UpdateIntuitFEJob.php` - Uso de formatIntuitError y mejoras en handleRateLimitError

## Notas de Implementación

- **Sin Emojis**: Siguiendo las convenciones del proyecto, no se usan emojis en logs ni mensajes
- **JSON_UNESCAPED_SLASHES**: Para mejorar legibilidad de URLs
- **JSON_UNESCAPED_UNICODE**: Para preservar caracteres especiales en español
- **Truncate trace**: Stack traces limitados a 2000 caracteres para no exceder límites de columna
- **Debug mode**: Stack traces completos solo en modo debug para reducir ruido en producción

## Compatibilidad

✅ Compatible con sistema existente  
✅ No requiere cambios en base de datos  
✅ Retrocompatible con código legacy  
✅ No afecta performance de jobs
