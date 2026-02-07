# Job de Creación de Bills en QuickBooks - CreateIntuitBillsJob

## Resumen

Job para sincronizar compras desde DocuCenter hacia QuickBooks Online como Bills (facturas de proveedores). Procesa registros de `Purchase_Header_Imp` y `Purchase_Detail_Imp` para crear Bills estructurados en QuickBooks.

## Arquitectura

### Componentes Principales

1. **Job**: `App\Jobs\Intuit\CreateIntuitBillsJob`
2. **Command**: `App\Console\Commands\Integrations\CreateIntuitBills`
3. **Modelos**: `PurchaseHeaderImp`, `PurchaseDetailImp`, `VendorsImp`, `ProductsImp`
4. **Migration**: Campos de tracking de sincronización QuickBooks

## Flujo de Procesamiento

### 1. Estados del Job
```php
const STATE_INIT = 1;                 // Inicialización
const STATE_PROCESSING = 2;           // Procesando compras
const STATE_VENDOR_VALIDATION = 3;    // Validando vendors
const STATE_ITEMS_VALIDATION = 4;     // Validando items
const STATE_BILL_CREATION = 5;        // Creando Bills
const STATE_COMPLETED = 6;            // Completado
```

### 2. Criterios de Selección
- `Enviado = false` (No enviadas)
- `quickbooks_sync_status != 'completed'` (No completadas)
- `quickbooks_sync_attempts < 3` (Máximo 3 reintentos)
- Límite de 50 registros por lote

### 3. Validaciones

#### Tablas Requeridas:
- `Purchase_Header_Imp`
- `Purchase_Detail_Imp`
- `Vendors_Imp`
- `Products_Imp`

#### Vendors:
- Busca vendor existente por `VendorID`
- Crea vendor básico si no existe
- Mapea a `VendorRef` en QuickBooks

#### Items:
- Busca en `ProductsImp` y `ProductsExp`
- Crea producto automáticamente si no existe
- Mapea a `ItemRef` en las líneas del Bill

## Estructura de Datos

### Bill en QuickBooks
```json
{
  "VendorRef": {
    "value": "vendor_id",
    "name": "vendor_name"
  },
  "TxnDate": "2025-09-03",
  "DueDate": "2025-10-03",
  "DocNumber": "PURCHASE-001",
  "PrivateNote": "Factura importada desde DocuCenter - ID: 123",
  "Line": [
    {
      "Amount": 100.00,
      "DetailType": "ItemBasedExpenseLineDetail",
      "Description": "Producto XYZ",
      "ItemBasedExpenseLineDetail": {
        "ItemRef": {
          "value": "item_id",
          "name": "item_description"
        },
        "Qty": 1.0,
        "UnitPrice": 100.00
      }
    }
  ]
}
```

### Mapeo de Campos

| DocuCenter | QuickBooks Bill |
|------------|-----------------|
| `PurchaseNumber` | `DocNumber` |
| `VendorID` | `VendorRef.value` |
| `VendorName` | `VendorRef.name` |
| `Date` | `TxnDate` |
| `DueDate` | `DueDate` |
| `Item_id` | `Line.ItemRef.value` |
| `Description` | `Line.Description` |
| `Quantity` | `Line.Qty` |
| `Unit_Price` | `Line.UnitPrice` |
| `Net_line` | `Line.Amount` |

## Campos de Tracking

### Nuevos Campos en Purchase_Header_Imp:
```sql
quickbooks_sync_status VARCHAR(20)        -- pending, processing, completed, failed
quickbooks_bill_id VARCHAR(50)            -- ID del Bill en QuickBooks
quickbooks_sync_error TEXT                -- Mensaje de error
quickbooks_sync_attempts INT DEFAULT 0    -- Número de intentos
quickbooks_sync_started_at TIMESTAMP      -- Inicio de sincronización
quickbooks_sync_completed_at TIMESTAMP    -- Finalización exitosa
```

## Uso

### Command Manual
```bash
# Todas las organizaciones
php artisan word:create-intuit-bills

# Organización específica
php artisan word:create-intuit-bills --organization_id=123
```

### Programación Automática
```php
// En app/Console/Kernel.php
$schedule->command('word:create-intuit-bills')->cron('0 3 * * *'); // 3 AM diario
```

## Logging y Monitoreo

### Niveles de Log:
- **INFO**: Inicio/fin de procesamiento, compras procesadas exitosamente
- **WARNING**: Configuración faltante, organizaciones sin datos
- **ERROR**: Errores de API, validaciones fallidas, excepciones

### Ejemplo de Logs:
```
[INFO] CreateIntuitBillsJob: Iniciando procesamiento de compras
   organization_id: 123, total_purchases: 5

[INFO] CreateIntuitBillsJob: Procesando compra
   purchase_id: 456, purchase_number: COMP-001, vendor_id: VENDOR123

[INFO] CreateIntuitBillsJob: Compra sincronizada exitosamente
   purchase_id: 456, quickbooks_bill_id: QB-BILL-789

[ERROR] CreateIntuitBillsJob: Error procesando compra individual
   purchase_id: 789, error: Vendor no encontrado en QuickBooks
```

## Manejo de Errores

### Estrategias de Recuperación:
1. **Reintentos Automáticos**: Máximo 3 intentos por compra
2. **Estado de Error**: Marcado en `quickbooks_sync_status = 'failed'`
3. **Continuidad**: Errores individuales no detienen el lote completo
4. **Logging Detallado**: Errores registrados en `quickbooks_sync_error`

### Errores Comunes:
- Vendor no existe en QuickBooks
- Item no válido o faltante
- Datos de fecha inválidos
- Problemas de conectividad con API
- Límites de rate limiting

## API Externa

### Endpoint Utilizado:
```
POST https://api.acicloud.com/quickbooks/bills
```

### Autenticación:
- Basic Auth con credenciales ACI
- Headers requeridos: `Content-Type`, `Accept`, `Authorization`

### Timeout: 30 segundos

## Performance y Escalabilidad

### Optimizaciones:
- Procesamiento en lotes de 50 registros
- Queries optimizadas con índices
- Lazy loading de relaciones
- Timeout configurado para API calls

### Recursos:
- Memory: ~50MB por lote
- Tiempo: ~2-5 segundos por compra
- Network: Depende de latencia de API externa

## Consideraciones de Seguridad

### Datos Sensibles:
- Credenciales ACI almacenadas en variables de entorno
- Tokens de autenticación no persistidos
- SSL/TLS para comunicación con API

### Validaciones:
- Sanitización de datos antes de envío
- Verificación de tipos de datos
- Límites de longitud para campos de texto

## Testing

### Configuración de Pruebas:
```php
// Variables de entorno requeridas
ACI_EMAIL=test@example.com
ACI_PASSWORD=test_password
ACI_API_URL=https://api.sandbox.acicloud.com
```

### Casos de Prueba:
1. Compra con vendor existente
2. Compra con vendor nuevo (auto-creación)
3. Compra con items faltantes (auto-creación)
4. Manejo de errores de API
5. Reintentos en fallos temporales

## Troubleshooting

### Problemas Frecuentes:

1. **"No hay compras pendientes"**
   - Verificar filtros de sincronización
   - Revisar campo `Enviado` en Purchase_Header_Imp

2. **"Error HTTP creando Bill"**
   - Validar credenciales ACI
   - Verificar conectividad de red
   - Revisar estructura de datos enviados

3. **"Vendor no encontrado"**
   - Verificar datos en tabla Vendors_Imp
   - Revisar mapeo de VendorID

4. **"Tabla no encontrada"**
   - Ejecutar migraciones: `php artisan migrate`
   - Verificar conexión de base de datos

### Comandos de Diagnóstico:
```bash
# Verificar configuración
php artisan tinker -c "dd(env('ACI_EMAIL'), env('ACI_PASSWORD'));"

# Verificar compras pendientes
php artisan tinker -c "App\Models\PurchaseHeaderImp::where('Enviado', false)->count()"

# Revisar logs
tail -f storage/logs/laravel.log | grep CreateIntuitBillsJob
```

---

**Autor**: Equipo DocuCenter  
**Fecha**: 2025-09-03  
**Versión**: 1.0  
**Estado**: Implementado
