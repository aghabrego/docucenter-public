# Uso del Sparse Update en QuickBooks - Guía Práctica

## ¿Qué es Sparse Update?

El sparse update permite actualizar solo campos específicos de una factura en QuickBooks sin sobrescribir otros campos que puedan haber sido modificados directamente en QuickBooks.

## Cuándo se Activa Automáticamente

El sistema determina automáticamente cuándo usar sparse update en los siguientes casos:

### 1. Flag Explícito
```php
// En la base de datos
$invoice->intuit_sync_mode = 'note_only';
```

### 2. Status de Sincronización
```php
// Cuando solo la nota necesita actualización
$invoice->intuit_sync_status = 'note_update_pending';
```

### 3. Facturas Actualizadas Recientemente
- Si la factura fue actualizada en QB en las últimas 2 horas
- Evita conflictos de concurrencia

### 4. Orígenes Seguros
- Facturas con origin: `quickbooks` o `manual_entry`
- Reduce riesgo de sobrescribir cambios

## Campos que se Pueden Actualizar con Sparse

### PrivateNote
```php
// Se actualiza automáticamente con CUFE
$baseData['PrivateNote'] = "CUFE: {$cufe}\nComprobantes: https://dgi-fep.mef.gob.pa/Consultas/FacturasPorCUFE/{$cufe}\nActualizado desde DocuCenter";
```

### CustomerMemo
```php
// Opcional: Memo visible para el cliente
$baseData['CustomerMemo'] = "Factura actualizada desde DocuCenter - CUFE: {$cufe}";
```

### CustomField
```php
// Campos personalizados
$baseData['CustomField'] = [
    [
        'DefinitionId' => '1', // ID del campo en QB
        'Name' => 'CUFE',
        'StringValue' => $cufe
    ]
];
```

## Uso Manual

### Actualización Solo de Nota
```php
// Crear job para actualizar solo nota
$job = new UpdateQuickBooksInvoicesJob($connection, $organizationId, [$invoiceId]);

// Marcar factura para sparse update
$invoice = SalesHeaderImp::find($invoiceId);
$invoice->intuit_sync_mode = 'note_only';
$invoice->save();

// Ejecutar job
dispatch($job);
```

### Forzar Update Completo
```php
// Limpiar flags que activan sparse update
$invoice->intuit_sync_mode = null;
$invoice->intuit_sync_status = 'pending';
$invoice->save();
```

## Logs y Debugging

### Identificar Tipo de Update
```php
// En los logs aparecerá:
"UpdateQuickBooksInvoicesJob: Modo note_only detectado"
// o
"UpdateQuickBooksInvoicesJob: Usando update completo"
```

### Verificar Datos Enviados
```php
// Log con datos sparse:
"UpdateQuickBooksInvoicesJob: Datos sparse para actualización de nota"
// Incluye: sparse_data, has_sparse_flag
```

## Estructura de Datos Sparse vs Completa

### Sparse Update (Solo Nota)
```json
{
    "connection_id": "123456789",
    "Id": "145",
    "SyncToken": "2",
    "sparse": "true",
    "PrivateNote": "CUFE: FE123...\nComprobantes: https://..."
}
```

### Update Completo
```json
{
    "connection_id": "123456789",
    "Id": "145",
    "SyncToken": "2",
    "CustomerRef": {"value": "1"},
    "Line": [...],
    "TxnDate": "2025-09-29",
    "DocNumber": "FE-001",
    "PrivateNote": "CUFE: FE123..."
}
```

## Validaciones en Cloud Function

El endpoint helper debe modificarse para soportar `sparse=true`:

```javascript
// En /aciv2/update_invoice_quickbooks/{invoiceId}
const isSparse = invoiceData.sparse === 'true';
const qboApiUrl = `${baseUrl}/v3/company/${realmId}/invoice?operation=update${isSparse ? '&sparse=true' : ''}`;
```

## Beneficios

### Preserva Cambios
- No sobrescribe información modificada en QuickBooks
- Mantiene datos de líneas, fechas, clientes, etc.

### Mejor Performance
- Transfiere menos datos
- Procesamiento más rápido
- Menor uso de ancho de banda

### Menor Riesgo
- Reduce errores por campos faltantes
- Evita conflictos de concurrencia
- Mayor compatibilidad con QB

## Resolución de Problemas

### Error: "Sparse parameter not supported"
- Verificar que la Cloud Function pase `sparse=true` en query string
- Asegurar que el endpoint de QB reciba el parámetro

### Error: "Invalid SyncToken"
- El SyncToken debe obtenerse antes de cada update
- Usar `getSyncTokenQB()` para obtener versión actual

### Nota No Se Actualiza
- Verificar que `PrivateNote` esté en `fieldsToUpdate`
- Revisar logs para confirmar que se envía sparse=true

## Monitoreo

### Métricas Importantes
```php
// Contadores por tipo de update
$sparseUpdates = 0;
$fullUpdates = 0;

// Success rate por tipo
$sparseSuccessRate = 0;
$fullSuccessRate = 0;
```

### Dashboard Query
```sql
SELECT 
    origin,
    intuit_sync_mode,
    COUNT(*) as total,
    SUM(CASE WHEN intuit_sync_status = 'updated' THEN 1 ELSE 0 END) as successful
FROM sales_header_imp 
WHERE intuit_invoice_id IS NOT NULL 
AND updated_at >= NOW() - INTERVAL 24 HOUR
GROUP BY origin, intuit_sync_mode;
```
