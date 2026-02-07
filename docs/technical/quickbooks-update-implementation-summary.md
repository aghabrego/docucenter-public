# Implementación Completa: UpdateQuickBooksInvoicesJob

## 📋 Resumen de Implementación

Se ha implementado exitosamente un sistema completo para actualizar facturas de ventas originadas en QuickBooks usando la API de Intuit.

## 🚀 Archivos Creados

### 1. Job Principal
- **Archivo**: `app/Jobs/Intuit/UpdateQuickBooksInvoicesJob.php`
- **Propósito**: Job principal para sincronizar facturas con QuickBooks
- **Características**:
  - 5 estados granulares de procesamiento
  - Manejo robusto de errores
  - Logging detallado
  - Cache de estado para monitoreo
  - Soporte para facturas específicas o procesamiento masivo

### 2. Comando Artisan
- **Archivo**: `app/Console/Commands/UpdateQuickBooksInvoicesCommand.php`
- **Propósito**: Interface de línea de comandos para ejecutar el job
- **Características**:
  - Modo dry-run para testing
  - Validaciones de organización y conexión
  - Reportes detallados de estado
  - Confirmación para modo producción

### 3. Script de Testing
- **Archivo**: `scripts/test-quickbooks-update.sh`
- **Propósito**: Script automatizado para pruebas y monitoreo
- **Características**:
  - Múltiples modos de operación
  - Validaciones automáticas
  - Reportes de estado
  - Manejo de errores

### 4. Documentación Técnica
- **Archivo**: `docs/technical/quickbooks-invoice-update-job.md`
- **Propósito**: Documentación completa del sistema
- **Contenido**: API, configuración, troubleshooting, mejores prácticas

## 🔧 Campos Utilizados (SalesHeaderImp)

El sistema utiliza los campos existentes del modelo sin necesidad de migraciones:

```php
// Campos de identificación QuickBooks
'intuit_invoice_id'     // ID de la factura en QuickBooks
'intuit_customer_id'    // ID del cliente en QuickBooks
'SyncToken'             // Token de sincronización de QB

// Campos de control de sincronización
'intuit_sync_status'    // Estado: synced|error|pending
'intuit_sync_errors'    // Mensajes de error
'intuit_last_attempt'   // Timestamp del último intento

// Campos de filtrado
'origin'                // 'quickbooks' para facturas de QB
'EzeeIssued'           // 1 para facturas emitidas
'InvoiceNote'          // Datos de facturación
```

## 📊 Criterios de Selección

### Facturas Elegibles
- ✅ `origin = 'quickbooks'`
- ✅ `EzeeIssued = 1`
- ✅ `InvoiceNote IS NOT NULL`
- ✅ `intuit_invoice_id IS NOT NULL`

### Filtros de Procesamiento
- Estado diferente a 'synced'
- Sin estado (nuevas)
- Último intento hace más de 1 hora

## 🎯 Uso del Sistema

### Comando Básico
```bash
# Dry-run (recomendado primero)
php artisan quickbooks:update-invoices 123 --dry-run

# Ejecutar actualización
php artisan quickbooks:update-invoices 123

# Facturas específicas
php artisan quickbooks:update-invoices 123 --invoice_ids=1,2,3
```

### Script de Testing
```bash
# Verificar estado
./scripts/test-quickbooks-update.sh status 123

# Dry-run automático
./scripts/test-quickbooks-update.sh dry-run 123

# Pruebas con facturas específicas
./scripts/test-quickbooks-update.sh test 123
```

### Uso Programático
```php
use App\Jobs\Intuit\UpdateQuickBooksInvoicesJob;

$connection = Connection::where('service', 'quickbooks')
    ->where('organization_id', 123)
    ->where('status', 'active')
    ->first();

// Todas las facturas elegibles
dispatch(new UpdateQuickBooksInvoicesJob($connection, 123));

// Facturas específicas
dispatch(new UpdateQuickBooksInvoicesJob($connection, 123, [1, 2, 3]));
```

## 🔍 Monitoreo

### Logs Principales
```bash
# Seguir logs en tiempo real
tail -f storage/logs/laravel.log | grep "UpdateQuickBooksInvoicesJob"

# Ver solo errores
tail -f storage/logs/laravel.log | grep "UpdateQuickBooksInvoicesJob.*ERROR"
```

### Cache de Estado
```php
// Ver estado actual del job
Cache::get('qb_update_job_123');

// Resultado esperado:
[
    'state' => 5,
    'counters' => [
        'total' => 10,
        'updated' => 8,
        'failed' => 2,
        'skipped' => 0
    ],
    'updated_at' => '2025-09-15 10:30:00'
]
```

### Queries de Diagnóstico
```sql
-- Ver resumen de sincronización
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN intuit_sync_status = 'synced' THEN 1 ELSE 0 END) as synced,
    SUM(CASE WHEN intuit_sync_status = 'error' THEN 1 ELSE 0 END) as errors,
    SUM(CASE WHEN intuit_sync_status IS NULL THEN 1 ELSE 0 END) as pending
FROM sales_header_imp 
WHERE origin = 'quickbooks' AND EzeeIssued = 1;

-- Ver facturas con errores recientes
SELECT ID, InvoiceNumber, intuit_sync_errors, intuit_last_attempt 
FROM sales_header_imp 
WHERE intuit_sync_status = 'error' 
ORDER BY intuit_last_attempt DESC 
LIMIT 10;
```

## 🔥 API de QuickBooks

### Endpoint Usado
```
POST https://sandbox-quickbooks.api.intuit.com/v3/company/{companyId}/invoice
```

### Estructura de Datos Enviada
```json
{
  "Invoice": {
    "Id": "QB_INVOICE_ID",
    "SyncToken": "SYNC_TOKEN",
    "DocNumber": "FAC-001",
    "TxnDate": "2025-09-15",
    "CustomerRef": {"value": "QB_CUSTOMER_ID"},
    "Line": [
      {
        "DetailType": "SalesItemLineDetail",
        "Amount": 100.00,
        "SalesItemLineDetail": {
          "ItemRef": {"value": "QB_ITEM_ID"},
          "Qty": 2,
          "UnitPrice": 50.00
        }
      }
    ],
    "TotalAmt": 100.00
  }
}
```

## ⚠️ Consideraciones Importantes

### Prerrequisitos
1. ✅ Conexión QuickBooks activa en tabla `connections`
2. ✅ Tokens de acceso válidos
3. ✅ Facturas ya creadas en QuickBooks (`intuit_invoice_id` populated)
4. ✅ Cola de trabajos configurada

### Limitaciones
- ⚠️ Solo actualiza facturas existentes (no crea nuevas)
- ⚠️ Requiere `intuit_invoice_id` válido
- ⚠️ Limitado por rate limits de QuickBooks API
- ⚠️ No maneja renovación automática de tokens

### Mejores Prácticas
1. 🔍 **Siempre usar dry-run primero**
2. 📊 **Monitorear logs durante ejecución**
3. ⏰ **Ejecutar en horarios de bajo tráfico**
4. 🔄 **Verificar resultados en QuickBooks**
5. 📋 **Documentar cualquier problema encontrado**

## 🚀 Próximos Pasos

### Implementación Inmediata
1. Probar en ambiente de desarrollo
2. Validar con facturas de prueba
3. Ejecutar dry-run en producción
4. Implementar gradualmente

### Extensiones Futuras
1. **Renovación automática de tokens**
2. **Sincronización bidireccional**
3. **Webhook integration**
4. **Dashboard de monitoreo**
5. **Bulk operations optimization**

## 📝 Notas Técnicas

- **Patrón de estados granulares** para mejor tracking
- **Cache temporal** para monitoreo en tiempo real
- **Manejo robusto de errores** con reintentos automáticos
- **Logging estructurado** para debugging eficiente
- **Compatible con arquitectura multi-tenant** existente
- **Sin cambios de base de datos** - usa campos existentes

¡La implementación está completa y lista para usar! 🎉
