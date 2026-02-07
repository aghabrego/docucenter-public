# Análisis y Mapeo de Órdenes de Compra Zoho

## Resumen Ejecutivo

Este documento proporciona un análisis detallado de la estructura de datos recibida del webhook de Zoho Books para órdenes de compra y su mapeo hacia los modelos `PurchaseHeaderImp` y `PurchaseDetailImp` de DocuCenter.

**Fecha de Análisis**: 2025-10-02  
**Origen de Datos**: Log del endpoint `/api/acicloud/create_purchase_order_zoho`  
**Tipo de Webhook**: Zoho Books Purchase Order / Bill  

## Estructura de Datos de Zoho

### Datos del Header (Cabecera de la Orden)

El webhook de Zoho envía los siguientes campos principales para la cabecera:

```json
{
  "bill_id": "6088114000000487426",
  "bill_number": "88931",
  "reference_number": "22831",
  "vendor_id": "6088114000000487410",
  "vendor_name": "Medtrition",
  "date": "2025-10-02",
  "due_date": "2025-10-02",
  "currency_code": "USD",
  "currency_symbol": "$",
  "exchange_rate": "1.0",
  "sub_total": "60.0",
  "tax_total": "0.0",
  "total": "60.0",
  "balance": "60.0",
  "adjustment": "0.0",
  "adjustment_description": "Ajuste",
  "status": "open",
  "payment_terms": "0",
  "payment_made": "0.0",
  "discount_amount": "0.0",
  "is_discount_before_tax": "true"
}
```

### Datos de Line Items (Detalles de la Orden)

Los detalles vienen en un array JSON dentro del campo `line_items`:

```json
[
  {
    "line_item_id": "6088114000000487433",
    "item_id": "6088114000000463033",
    "sku": "V504413",
    "name": "DIENAT G Vainilla",
    "description": "",
    "quantity": 20,
    "unit": "und",
    "rate": 3,
    "item_total": 60,
    "account_id": "6088114000000034001",
    "account_name": "Activo de inventario",
    "location_id": "6088114000000091182",
    "location_name": "Oficina principal",
    "item_type": "inventory",
    "tax_id": "",
    "tax_name": "",
    "tax_percentage": 0,
    "discount": 0,
    "discount_amount": 0,
    "line_item_taxes": []
  }
]
```

### Estructura con Impuestos (ITBMS)

Para órdenes con impuestos, la estructura incluye campos adicionales:

**Header con impuestos:**
```json
{
  "sub_total": "5.0",
  "tax_total": "0.35", 
  "total": "5.35",
  "discount_amount": "0.0",
  "is_discount_before_tax": "true"
}
```

**Line item con impuestos:**
```json
{
  "sku": "11525",
  "name": "PRO SOURCE NO CARB",
  "quantity": 1,
  "rate": 5,
  "item_total": 5,
  "tax_id": "6088114000000466001",
  "tax_name": "ITBMS",
  "tax_percentage": 7,
  "line_item_taxes": [
    {
      "tax_amount": 0.35,
      "tax_name": "ITBMS (7%)",
      "tax_amount_formatted": "$0.35",
      "tax_id": "6088114000000466001"
    }
  ]
}
```

## Mapeo hacia Modelos DocuCenter

### PurchaseHeaderImp - Mapeo de Campos

| Campo DocuCenter | Campo Zoho | Transformación | Notas |
|------------------|------------|----------------|-------|
| `PurchaseNumber` | `bill_number` | Directo | Número de factura/orden |
| `VendorID` | `vendor_id` | Directo | ID único del proveedor en Zoho |
| `VendorName` | `vendor_name` | Directo | Nombre del proveedor |
| `Date` | `date` | `Carbon::parse()` | Convertir a formato fecha |
| `DueDate` | `due_date` | `Carbon::parse()` | Fecha de vencimiento |
| `Subtotal` | `sub_total` | `(float)` | Subtotal sin impuestos |
| `Net_due` | `total` | `(float)` | Total de la orden (incluye impuestos) |
| `AP_Account` | `account_name` | Tomar del primer line_item | Cuenta contable |
| `ErrorPT` | `tax_info` | `buildTaxInfo()` | Información de impuestos en JSON |
| `Export_date` | - | `now()` | Fecha de importación |
| `Enviado` | - | `0` | Estado inicial |
| `Error` | - | `0` | Sin errores inicialmente |

### PurchaseDetailImp - Mapeo de Campos

| Campo DocuCenter | Campo Zoho Line Item | Transformación | Notas |
|------------------|---------------------|----------------|-------|
| `Item_id` | `sku` o `item_id` | Priorizar `sku` | Código del producto |
| `Description` | `name` | Directo | Nombre/descripción del producto |
| `GL_Acct` | `account_name` | Directo | Cuenta contable del item |
| `Quantity` | `quantity` | `(float)` | Cantidad |
| `Unit_Price` | `rate` | `(float)` | Precio unitario |
| `Net_line` | `item_total` | `(float)` | Total de la línea |
| `JobID` | `tax_info` | `buildItemTaxInfo()` | Información de impuestos del item |
| `JobPhaseID` | `tax_id` | Directo | ID del impuesto en Zoho |
| `Sequential` | - | Auto-incremental | Número secuencial por orden |

## Implementación Sugerida

### 1. Clase de Transformación

```php
<?php

namespace App\Services\Zoho;

use Carbon\Carbon;
use App\Models\PurchaseHeaderImp;
use App\Models\PurchaseDetailImp;

class ZohoPurchaseOrderTransformer
{
    public function transformHeader(array $zohoData): array
    {
        return [
            'PurchaseNumber' => $zohoData['bill_number'] ?? '',
            'VendorID' => $zohoData['vendor_id'] ?? '',
            'VendorName' => $zohoData['vendor_name'] ?? '',
            'Date' => Carbon::parse($zohoData['date'])->format('Y-m-d H:i:s'),
            'DueDate' => Carbon::parse($zohoData['due_date'])->format('Y-m-d H:i:s'),
            'Subtotal' => (float) ($zohoData['sub_total'] ?? 0),
            'Net_due' => (float) ($zohoData['total'] ?? 0),
            'AP_Account' => $this->getAccountFromLineItems($zohoData),
            'Export_date' => now(),
            'Enviado' => 0,
            'Error' => 0,
        ];
    }

    public function transformLineItems(array $lineItems, int $transactionId): array
    {
        $details = [];
        $sequential = 1;

        foreach ($lineItems as $item) {
            $details[] = [
                'TransactionID' => $transactionId,
                'Item_id' => $item['sku'] ?: $item['item_id'],
                'Description' => $item['name'],
                'GL_Acct' => $item['account_name'],
                'Quantity' => (float) $item['quantity'],
                'Unit_Price' => (float) $item['rate'],
                'Net_line' => (float) $item['item_total'],
                'Sequential' => $sequential++,
            ];
        }

        return $details;
    }

    private function getAccountFromLineItems(array $zohoData): string
    {
        $lineItems = json_decode($zohoData['line_items'] ?? '[]', true);
        return $lineItems[0]['account_name'] ?? '';
    }
}
```

### 2. Servicio de Importación

```php
<?php

namespace App\Services\Zoho;

use DB;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseHeaderImp;
use App\Models\PurchaseDetailImp;

class ZohoPurchaseOrderImporter
{
    protected $transformer;

    public function __construct(ZohoPurchaseOrderTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function importPurchaseOrder(array $zohoData, int $organizationId): array
    {
        try {
            DB::beginTransaction();

            // Verificar si ya existe
            $existingOrder = PurchaseHeaderImp::where('PurchaseNumber', $zohoData['bill_number'])
                ->where('VendorID', $zohoData['vendor_id'])
                ->first();

            if ($existingOrder) {
                return [
                    'success' => false,
                    'message' => 'Orden de compra ya existe',
                    'transaction_id' => $existingOrder->TransactionID
                ];
            }

            // Crear header
            $headerData = $this->transformer->transformHeader($zohoData);
            $headerData['ID_compania'] = $organizationId;
            
            $header = PurchaseHeaderImp::create($headerData);

            // Crear detalles
            $lineItems = json_decode($zohoData['line_items'], true);
            $detailsData = $this->transformer->transformLineItems($lineItems, $header->TransactionID);
            
            foreach ($detailsData as $detail) {
                $detail['ID_compania'] = $organizationId;
                PurchaseDetailImp::create($detail);
            }

            DB::commit();

            Log::info('Orden de compra Zoho importada exitosamente', [
                'transaction_id' => $header->TransactionID,
                'bill_number' => $zohoData['bill_number'],
                'vendor_name' => $zohoData['vendor_name'],
                'total' => $zohoData['total']
            ]);

            return [
                'success' => true,
                'message' => 'Orden de compra importada exitosamente',
                'transaction_id' => $header->TransactionID
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error importando orden de compra Zoho', [
                'error' => $e->getMessage(),
                'bill_number' => $zohoData['bill_number'] ?? 'N/A',
                'vendor_name' => $zohoData['vendor_name'] ?? 'N/A'
            ]);

            return [
                'success' => false,
                'message' => 'Error importando orden de compra: ' . $e->getMessage()
            ];
        }
    }
}
```

### 3. Actualización del Controller

```php
// En ACIcloudController.php - método createPurchaseOrderZoho()

public function createPurchaseOrderZoho(Request $request)
{
    // ... logging existente ...

    // Agregar procesamiento real
    $importer = new ZohoPurchaseOrderImporter(new ZohoPurchaseOrderTransformer());
    
    // Obtener organización del usuario autenticado
    $organizationId = auth()->user()->getORG();
    
    $result = $importer->importPurchaseOrder($request->all(), $organizationId);
    
    return response()->json($result);
}
```

## Campos Adicionales Disponibles en Zoho

### Datos del Proveedor
- `billing_address`: Dirección de facturación (JSON)
- `vendor_credits`: Créditos del proveedor

### Datos Financieros
- `payments`: Array de pagos realizados
- `payment_made`: Monto total pagado
- `balance`: Saldo pendiente
- `discount`: Descuentos aplicados
- `tax_total`: Total de impuestos

### Metadatos
- `custom_fields`: Campos personalizados
- `reference_number`: Número de referencia
- `status`: Estado de la orden (open, closed, etc.)
- `payment_terms`: Términos de pago

## Consideraciones Técnicas

### 1. Validaciones Requeridas
- Verificar que el `vendor_id` exista en el sistema
- Validar que los `item_id`/`sku` correspondan a productos válidos
- Confirmar que las cuentas contables existan

### 2. Manejo de Errores
- Duplicación de órdenes (por `bill_number` + `vendor_id`)
- Productos no encontrados
- Cuentas contables inválidas
- Errores de conexión a base de datos

### 3. Logging y Auditoría
- Registrar todas las importaciones exitosas
- Documentar errores con contexto completo
- Mantener trazabilidad de cambios

### 4. Performance
- Procesar en lotes para múltiples line items
- Usar transacciones para consistencia
- Cachear validaciones frecuentes

## Casos de Uso Identificados

### Escenario 1: Orden Simple
- 1 proveedor, múltiples productos
- Moneda USD, sin descuentos
- Estado "open"

### Escenario 2: Orden con Descuentos
- Descuentos por línea y/o totales
- Cálculos de impuestos

### Escenario 3: Orden Multi-Moneda
- Tipos de cambio
- Conversiones automáticas

## Próximos Pasos

1. **Implementar clases de transformación**
2. **Crear validaciones específicas**
3. **Agregar tests unitarios**
4. **Documentar API endpoints**
5. **Configurar monitoreo de errores**

## Testing

### Datos de Prueba del Log Analizado

```json
{
  "vendor_name": "Medtrition",
  "bill_number": "88931",
  "total": "60.0",
  "line_items": [
    {
      "sku": "V504413",
      "name": "DIENAT G Vainilla",
      "quantity": 20,
      "rate": 3,
      "item_total": 60
    }
  ]
}
```

Este análisis proporciona la base completa para implementar la integración de órdenes de compra desde Zoho Books hacia DocuCenter.
