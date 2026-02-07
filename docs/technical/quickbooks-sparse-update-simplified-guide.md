# QuickBooks Sparse Update - Guía de Uso Simplificada

## Resumen

Se implementó un parámetro simple en `UpdateQuickBooksInvoicesJob` para controlar si realizar un update completo o solo actualizar la nota (sparse update).

## Uso del Job

### Update Completo (Comportamiento por defecto)
```php
// Actualizar facturas completamente
UpdateQuickBooksInvoicesJob::dispatch($connection, $organizationId, $invoiceIds);

// O explícitamente
UpdateQuickBooksInvoicesJob::dispatch($connection, $organizationId, $invoiceIds, false);
```

### Update Solo Nota (Sparse Update)
```php
// Solo actualizar la nota de las facturas
UpdateQuickBooksInvoicesJob::dispatch($connection, $organizationId, $invoiceIds, true);
```

## Parámetros del Constructor

```php
public function __construct(
    Connection $connection,         // Conexión QuickBooks
    int $organizationId,           // ID de la organización  
    array $invoiceIds = [],        // IDs específicos de facturas (opcional)
    bool $updateNoteOnly = false   // true = solo nota, false = update completo
)
```

## Ejemplos de Uso

### 1. Actualizar solo nota de facturas específicas
```php
$connection = Connection::where('organization_id', $orgId)
                        ->where('name', 'QuickBooks')
                        ->first();

$invoiceIds = [123, 456, 789];

// Solo actualizar notas
UpdateQuickBooksInvoicesJob::dispatch($connection, $orgId, $invoiceIds, true);
```

### 2. Actualizar facturas completamente
```php
// Update completo de facturas específicas
UpdateQuickBooksInvoicesJob::dispatch($connection, $orgId, $invoiceIds, false);

// Update completo de todas las facturas pendientes
UpdateQuickBooksInvoicesJob::dispatch($connection, $orgId, [], false);
```

### 3. Usar en un controlador
```php
public function updateInvoiceNotes($organizationId, $invoiceIds)
{
    $connection = Connection::where('organization_id', $organizationId)
                            ->where('name', 'QuickBooks')
                            ->first();
    
    if (!$connection) {
        return response()->json(['error' => 'QuickBooks connection not found'], 404);
    }
    
    // Despachar job para solo actualizar notas
    UpdateQuickBooksInvoicesJob::dispatch($connection, $organizationId, $invoiceIds, true);
    
    return response()->json(['message' => 'Note update job dispatched']);
}
```

## Testing con Artisan Command

```bash
# Test sparse update (solo nota)
php artisan test:quickbooks-sparse-update 123 --organization=456 --mode=note_only

# Test update completo  
php artisan test:quickbooks-sparse-update 123 --organization=456 --mode=full

# Dry run para ver datos que se enviarían
php artisan test:quickbooks-sparse-update 123 --organization=456 --mode=note_only --dry-run
```

## Diferencias Técnicas

### Update Completo (`updateNoteOnly = false`)
- Envía todos los campos de la factura
- Incluye líneas de detalle, impuestos, cliente, etc.
- Sobrescribe datos existentes en QuickBooks
- Usado para sincronización completa

### Sparse Update (`updateNoteOnly = true`)
- Solo envía campos específicos (`PrivateNote`)
- Preserva otros campos ya existentes en QuickBooks
- Más seguro para actualizaciones menores
- Evita conflictos con cambios hechos directamente en QB

## Logging

El job registra el modo de actualización elegido:

```
UpdateQuickBooksInvoicesJob: Iniciado
- organization_id: 123
- connection_id: 456  
- update_note_only: true/false
```

## Ventajas del Enfoque Simplificado

1. **Simple**: Un solo parámetro controla el comportamiento
2. **Explícito**: El desarrollador decide conscientemente el tipo de update
3. **Predecible**: No hay lógica automática compleja que pueda fallar
4. **Flexible**: Se puede usar en diferentes contextos según la necesidad
5. **Mantenible**: Fácil de entender y modificar

## Casos de Uso Recomendados

### Usar Sparse Update cuando:
- Solo necesitas actualizar información de DocuCenter (CUFE, notas)
- La factura fue modificada recientemente en QuickBooks
- Quieres preservar cambios hechos directamente en QB
- Es una operación de "mantenimiento" o sincronización parcial

### Usar Update Completo cuando:
- Es una factura nueva que viene desde DocuCenter
- Necesitas sincronizar todos los campos
- Es una corrección completa de datos
- Es la sincronización inicial desde otro sistema
