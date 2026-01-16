# Job para Actualizar Facturas en QuickBooks

## Descripción

El `UpdateQuickBooksInvoicesJob` permite actualizar facturas existentes en QuickBooks usando la API de Intuit. Este job está diseñado para sincronizar cambios realizados en DocuCenter hacia QuickBooks, manteniendo ambos sistemas actualizados.

## Arquitectura

### Estados del Job

El job maneja 5 estados granulares para tracking detallado:

```php
const STATE_INIT = 1;              // Inicialización
const STATE_PROCESSING = 2;        // Procesando facturas
const STATE_UPDATING_INVOICES = 3; // Actualizando en QuickBooks
const STATE_VALIDATING = 4;        // Validando resultados
const STATE_COMPLETED = 5;         // Completado
```

### Flujo de Procesamiento

1. **Inicialización**: Configuración de conexión DB y validaciones
2. **Obtención de facturas**: Query de facturas que necesitan actualización
3. **Actualización**: Llamadas a API de QuickBooks para cada factura
4. **Validación**: Verificación de resultados y manejo de errores
5. **Finalización**: Logging y limpieza

## Uso

### Comando Artisan

```bash
# Actualizar todas las facturas elegibles
php artisan quickbooks:update-invoices 123

# Actualizar facturas específicas
php artisan quickbooks:update-invoices 123 --invoice_ids=1,2,3

# Dry-run para ver qué se actualizaría
php artisan quickbooks:update-invoices 123 --dry-run

# Usar conexión específica
php artisan quickbooks:update-invoices 123 --connection_id=456
```

### Programáticamente

```php
use App\Jobs\Intuit\UpdateQuickBooksInvoicesJob;
use App\Models\Connection;

$connection = Connection::where('organization_id', 123)
    ->where('service', 'quickbooks')
    ->where('status', 'active')
    ->first();

// Actualizar todas las facturas elegibles
$job = new UpdateQuickBooksInvoicesJob($connection, 123);
dispatch($job);

// Actualizar facturas específicas
$job = new UpdateQuickBooksInvoicesJob($connection, 123, [1, 2, 3]);
dispatch($job);
```

## Criterios de Selección

### Facturas Elegibles

El job actualiza facturas que cumplan **TODOS** estos criterios:

- ✅ `origin = 'quickbooks'` (originadas en QuickBooks)
- ✅ `EzeeIssued = 1` (facturas emitidas)
- ✅ `InvoiceNote IS NOT NULL` (con datos de facturación)
- ✅ `intuit_invoice_id IS NOT NULL` (ya existen en QuickBooks)

### Filtros Adicionales

Si no se especifican IDs específicos, se aplican estos filtros:

- Estado de sincronización diferente a 'synced'
- Sin estado de sincronización (facturas nuevas)
- Último intento hace más de 1 hora (para reintentos)

## Formato de Datos para QuickBooks

### Estructura de la Factura

```json
{
  "Invoice": {
    "Id": "QB_INVOICE_ID",
    "SyncToken": "SYNC_TOKEN",
    "DocNumber": "FAC-001",
    "TxnDate": "2025-09-15",
    "DueDate": "2025-10-15",
    "CustomerRef": {
      "value": "QB_CUSTOMER_ID"
    },
    "Line": [
      {
        "DetailType": "SalesItemLineDetail",
        "Amount": 100.00,
        "SalesItemLineDetail": {
          "ItemRef": {
            "value": "QB_ITEM_ID",
            "name": "Producto ABC"
          },
          "Qty": 2,
          "UnitPrice": 50.00,
          "TaxCodeRef": {
            "value": "TAX"
          }
        }
      }
    ],
    "TotalAmt": 100.00,
    "CustomField": [
      {
        "DefinitionId": "1",
        "Name": "DocuCenter ID",
        "StringValue": "123"
      }
    ]
  }
}
```

## API de QuickBooks

### Endpoint

```
POST https://sandbox-quickbooks.api.intuit.com/v3/company/{companyId}/invoice
```

### Headers

```
Authorization: Bearer {ACCESS_TOKEN}
Accept: application/json
Content-Type: application/json
```

### Documentación Oficial

[QuickBooks Invoice Update API](https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice#full-update-an-invoice)

## Campos de Tracking

### En SalesHeaderImp

```php
// Estado de sincronización con Intuit
'intuit_sync_status' => 'synced|error|pending',

// Token de sincronización de QuickBooks
'SyncToken' => 'TOKEN_VALUE',

// ID de la factura en QuickBooks
'intuit_invoice_id' => 'QB_INVOICE_ID',

// ID del cliente en QuickBooks
'intuit_customer_id' => 'QB_CUSTOMER_ID',

// Mensajes de error si falló
'intuit_sync_errors' => 'Error message',

// Timestamp de último intento (exitoso o fallido)
'intuit_last_attempt' => '2025-09-15 10:30:00'
```

## Monitoreo y Logging

### Logs del Job

```php
// Inicio del job
Log::info("UpdateQuickBooksInvoicesJob: Iniciado", [
    'organization_id' => 123,
    'connection_id' => 456,
    'state' => 1
]);

// Progreso de facturas
Log::info("UpdateQuickBooksInvoicesJob: Actualizando factura", [
    'invoice_id' => 789,
    'invoice_number' => 'FAC-001',
    'qb_invoice_id' => 'QB123'
]);

// Finalización
Log::info("UpdateQuickBooksInvoicesJob: Completado", [
    'organization_id' => 123,
    'counters' => [
        'total' => 10,
        'updated' => 8,
        'failed' => 2,
        'skipped' => 0
    ]
]);
```

### Cache de Estado

El job guarda su estado en cache para monitoreo:

```php
Cache::get("qb_update_job_123"); // Devuelve estado actual
```

## Manejo de Errores

### Tipos de Error

1. **Errores de API**: Problemas con QuickBooks API
2. **Errores de datos**: Datos inválidos o faltantes
3. **Errores de conexión**: Problemas de red o autenticación
4. **Errores de sincronización**: Conflictos de SyncToken

### Estrategia de Reintentos

- **3 intentos** por defecto
- **Backoff exponencial** entre reintentos
- **Logging detallado** de cada intento

### Recuperación

```bash
# Reintentar facturas con error
php artisan quickbooks:update-invoices 123 --invoice_ids=$(
    mysql -e "SELECT GROUP_CONCAT(ID) FROM sales_header_imp 
              WHERE intuit_sync_status='error'" -N
)
```

## Configuración

### Variables de Entorno

```env
# URL base de QuickBooks (sandbox o producción)
QUICKBOOKS_BASE_URL=https://sandbox-quickbooks.api.intuit.com

# Configuración de cola
QUEUE_CONNECTION=redis
```

### Configuración del Job

```php
// En el constructor del job
$this->tries = 3;              // Número de reintentos
$this->timeout = 300;          // Timeout en segundos
$this->onQueue('default');     // Cola a usar
```

## Mejores Prácticas

### Antes de Ejecutar

1. ✅ Verificar que la conexión QuickBooks esté activa
2. ✅ Confirmar que los tokens de acceso sean válidos
3. ✅ Hacer backup de datos críticos
4. ✅ Usar `--dry-run` para validar

### Durante la Ejecución

1. 📊 Monitorear logs en tiempo real
2. 🔍 Verificar estados en cache
3. ⚠️ Estar atento a errores de API
4. 📈 Supervisar uso de recursos

### Después de Ejecutar

1. ✅ Validar contadores de éxito/error
2. 🔍 Revisar facturas con estado 'error'
3. 📊 Verificar sincronización en QuickBooks
4. 📝 Documentar cualquier issue encontrado

## Troubleshooting

### Problemas Comunes

```bash
# Token expirado
Error: "Token expired" -> Renovar tokens en Connection

# SyncToken inválido
Error: "Invalid SyncToken" -> Refrescar desde QuickBooks

# Factura no encontrada
Error: "Invoice not found" -> Verificar qb_invoice_id

# Límites de API
Error: "Rate limit exceeded" -> Espaciar requests
```

### Comandos de Diagnóstico

```bash
# Ver estado de conexiones
php artisan tinker
>>> Connection::where('service', 'quickbooks')->get(['id', 'status', 'updated_at']);

# Ver facturas con errores
>>> SalesHeaderImp::where('intuit_sync_status', 'error')->count();

# Ver cache del job
>>> Cache::get('qb_update_job_123');

# Ver facturas pendientes de sincronización
>>> SalesHeaderImp::whereNull('intuit_sync_status')->where('origin', 'quickbooks')->count();
```

## Consideraciones de Rendimiento

- **Procesamiento por lotes**: Considera dividir en chunks grandes
- **Rate limiting**: QuickBooks tiene límites de API
- **Timeout**: Ajustar según volumen de facturas
- **Memoria**: Monitorear uso con muchas facturas

## Extensiones Futuras

1. **Sincronización bidireccional**: Traer cambios desde QuickBooks
2. **Webhook integration**: Actualizaciones en tiempo real
3. **Bulk operations**: Procesar múltiples facturas por request
4. **Dashboard**: Interface web para monitoreo
