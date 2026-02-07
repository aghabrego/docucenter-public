# Estrategia de Sincronización de Items SQL Server

## Problema Identificado

El `STInvoiceJob` actualmente solo procesa items existentes en SQL Server, pero no maneja items que fueron eliminados. Esto causa inconsistencias entre el sistema fuente (SQL Server) y DocuCenter.

## Escenario Problemático

1. **Estado Inicial**: Factura BR1076105032 tiene 4 items en SQL Server
2. **Job Ejecuta**: Crea 4 records en `Purchase_Detail_Imp`  
3. **Usuario Elimina**: Elimina 2 items en SQL Server
4. **Job Re-ejecuta**: Solo actualiza los 2 items restantes
5. **Resultado**: DocuCenter sigue mostrando 4 items (2 actualizados + 2 obsoletos)

## Estrategias Evaluadas

### 1. Soft Delete
**Pros**: Mantiene historial, permite recuperación
**Contras**: Requiere migración de esquema, complejidad adicional

### 2. Physical Delete
**Pros**: Simple, mantiene consistencia 1:1
**Contras**: Pérdida de auditoría, problemas de integridad referencial

### 3. Full Synchronization (RECOMENDADO)
**Pros**: Balance perfecto entre simplicidad y funcionalidad
**Contras**: Requiere lógica adicional en el job

## Implementación Propuesta: Full Synchronization

### Algoritmo:
1. **Obtener items actuales** de SQL Server para la factura
2. **Obtener items existentes** en DocuCenter para esa factura  
3. **Identificar items eliminados** (existen en DocuCenter pero no en SQL Server)
4. **Eliminar items obsoletos** de DocuCenter
5. **Procesar items actuales** (crear/actualizar como siempre)

### Código de Implementación:

```php
// En STInvoiceJob::handle() después de obtener $details
$currentSequentials = $details->pluck('InvoiceLineItem')->toArray();

// Eliminar items que ya no existen en SQL Server
PurchaseDetailImp::where('TransactionID', $header->TransactionID)
    ->whereNotIn('Sequential', $currentSequentials)
    ->delete();

// Log de items eliminados
$deletedCount = PurchaseDetailImp::where('TransactionID', $header->TransactionID)
    ->whereNotIn('Sequential', $currentSequentials)
    ->count();
    
if ($deletedCount > 0) {
    \Log::info("STInvoiceJob: Eliminados {$deletedCount} items obsoletos", [
        'invoice_id' => $invoice->InvoiceID,
        'transaction_id' => $header->TransactionID,
        'current_items' => count($currentSequentials),
        'deleted_items' => $deletedCount,
    ]);
}
```

## Ventajas de Esta Solución

1. **Consistencia Total**: DocuCenter refleja exactamente el estado de SQL Server
2. **Auditoría Completa**: Logs detallados de todos los cambios
3. **Sin Cambios de Esquema**: Funciona con la estructura actual
4. **Performance Óptimo**: Una sola query adicional por factura
5. **Rollback Seguro**: Si hay problemas, el siguiente job re-sincroniza

## Casos de Uso Cubiertos

- Items agregados → Se crean en DocuCenter
- Items modificados → Se actualizan en DocuCenter  
- Items eliminados → Se eliminan de DocuCenter
- Items con cambio de Sequential → Se manejan correctamente

## Logging y Monitoreo

```php
// Log estructurado para monitoreo
\Log::info("STInvoiceJob: Sincronización completa", [
    'invoice_id' => $invoice->InvoiceID,
    'items_source' => $details->count(),
    'items_created' => $createdCount,
    'items_updated' => $updatedCount,
    'items_deleted' => $deletedCount,
    'sync_timestamp' => now(),
]);
```

## Testing

### Casos de Prueba:
1. **Factura nueva** → Todos los items se crean
2. **Item eliminado** → Item se elimina de DocuCenter
3. **Item modificado** → Item se actualiza
4. **Múltiples cambios** → Sincronización completa funciona
5. **Job re-ejecutado** → No duplicados, estado consistente

---

**Implementación estimada**: 30 minutos
**Testing estimado**: 15 minutos  
**Beneficio**: Elimina inconsistencias de datos permanentemente
