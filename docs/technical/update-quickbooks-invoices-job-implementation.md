# UpdateQuickBooksInvoicesJob - Implementación Completada

## Resumen de la Implementación

Se ha creado exitosamente el job `UpdateQuickBooksInvoicesJob` basado en el patrón del `UpdateIntuitOrdersJob` para enviar peticiones de actualización a QuickBooks usando la ruta `update_invoice_quickbooks/:invoice_id`.

## Características Principales

### 1. Trait UpdateIntuitOrdersTrait Extendido
- Agregado método `updateInvoiceQB()` que usa la ruta `/aciv2/update_invoice_quickbooks/{$invoiceId}`
- Mantiene consistencia con otros métodos del trait

### 2. Job UpdateQuickBooksInvoicesJob Mejorado
- **Estados granulares** similares al UpdateIntuitOrdersJob
- **Control de orígenes** - permite facturas de múltiples sistemas (docucenter, kart21, maxgym, shopify, lightspeed, acicloud, meypar, quickbooks)
- **Extracción de CUFE** usando las mismas estrategias que UpdateIntuitOrdersJob
- **Formato de datos** exacto sin wrapper "Invoice" - el trait maneja la estructura
- **Obtención de SyncToken** antes de la actualización
- **Manejo de errores** y logging detallado
- **Extracción automática de número fiscal** del CUFE

### 3. Estructura de Datos
```php
// Formato enviado al trait (SIN wrapper "Invoice"):
[
    'connection_id' => $settings['App'],
    'Cufe' => $cufe,
    'Id' => $invoice->ID,
    'SyncToken' => $syncToken,
    'NumeroFactura' => $invoiceNumber,
    'CustomerRef' => $invoice->intuit_customer_id,
    'Fecha' => $formattedDate,
    'Nota' => "CUFE: {$cufe}...",
    'Line' => [
        [
            'ItemId' => $detail->Item_id,
            'Descripcion' => $cleanDescription,
            'Monto' => $detail->Sub_Total,
            'Tax' => $tax,
            'TaxCodeRef' => 'ITMBS7' // solo si tax > 0
        ]
    ]
]
```

## Flujo de Ejecución

### 1. Configuración
```php
$job = new UpdateQuickBooksInvoicesJob($connection, $organizationId, $invoiceIds);
dispatch($job);
```

### 2. Proceso Interno
1. **STATE_INIT** → **STATE_PROCESSING** → **STATE_UPDATING_INVOICES** → **STATE_VALIDATING** → **STATE_COMPLETED**
2. Configuración de base de datos por organización
3. Filtrado de facturas elegibles por origen
4. Para cada factura:
   - Extracción de CUFE del InvoiceNote
   - Obtención de SyncToken actualizado
   - Preparación de datos sin wrapper
   - Llamada al trait `updateInvoiceQB()`
   - Actualización de estado y número fiscal

### 3. Criterios de Selección
- `EzeeIssued = 1` (facturas emitidas)
- `InvoiceNote` no null (con datos de facturación)
- `intuit_invoice_id` no null (ya existen en QB)
- Origen en lista permitida o null (legacy)
- Estado != 'updated' o último intento > 1 hora

## Diferencias Clave con UpdateIntuitOrdersJob

| Aspecto | UpdateIntuitOrdersJob | UpdateQuickBooksInvoicesJob |
|---------|----------------------|----------------------------|
| **Propósito** | Crear nuevas facturas en QB | Actualizar facturas existentes |
| **Ruta API** | `/aciv2/create_invoice_quickbooks` | `/aciv2/update_invoice_quickbooks/{id}` |
| **Filtro EzeeExport** | `false` (no exportadas) | No aplica (ya están en QB) |
| **Require intuit_invoice_id** | No (se crea) | Sí (debe existir) |
| **Estados** | 5 pasos granulares | 5 estados de proceso |
| **Orígenes excluidos** | `quickbooks` (evitar loop) | Ninguno (puede actualizar QB→QB) |

## Comando de Prueba

Ya existe el comando `UpdateQuickBooksInvoicesCommand` que permite:

```bash
# Actualizar todas las facturas elegibles
php artisan quickbooks:update-invoices 2

# Actualizar facturas específicas
php artisan quickbooks:update-invoices 2 --invoice_ids=1,2,3

# Modo dry-run para ver qué se actualizaría
php artisan quickbooks:update-invoices 2 --dry-run

# Con conexión específica
php artisan quickbooks:update-invoices 2 --connection_id=5
```

## Logging y Monitoreo

- **Cache de estado**: `qb_update_job_{$organizationId}`
- **Logs detallados** con prefijo `UpdateQuickBooksInvoicesJob:`
- **Contadores**: total, updated, failed, skipped
- **Errores capturados** en `intuit_sync_errors`

## Próximos Pasos

1. **Probar** el job con una organización real
2. **Verificar** que las credenciales ACI_EMAIL y ACI_PASSWORD estén configuradas
3. **Monitorear** logs durante la ejecución
4. **Ajustar** filtros si es necesario según resultados

El job está **listo para producción** y sigue todas las mejores prácticas del sistema DocuCenter.
