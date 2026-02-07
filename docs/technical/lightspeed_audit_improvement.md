# Mejora de Auditoría para SetSalesOrdersJob - Lightspeed Serie R

## Problema Original
En `SetSalesOrdersJob` solo se almacenaba datos básicos de la venta (`$sale`) en `storeTransaction`, faltando información crítica para auditoría:
- ❌ Items/líneas de venta incompletos
- ❌ Datos de pagos sin detalles de tipos
- ❌ Información parcial de productos
- ❌ Llamadas API duplicadas durante procesamiento

## Solución Implementada

### 1. Flujo Reorganizado 
```php
// ANTES: Almacenar datos básicos → Obtener items → Obtener pagos → Procesar
storeTransaction($orgActive->id, $invoiceNumber, $sale); // Solo datos básicos

// DESPUÉS: Obtener TODO → Consolidar → Almacenar → Procesar usando consolidado
$fullTransactionData = [
    'sale' => $sale,
    'items' => $enrichedSaleLines,      // Con itemDetails/lineDetails
    'payments' => $enrichedPaymentLines, // Con paymentTypeDetails  
    'customer' => $customerFromSale,
    'metadata' => [...]
];
storeTransaction($orgActive->id, $invoiceNumber, $fullTransactionData);
```

### 2. Datos Enriquecidos
**Items:** Incluye detalles completos de productos y líneas
```php
$enrichedLine['itemDetails'] = array_get($item, 'Item', []); // Para products
$enrichedLine['lineDetails'] = array_get($lineDetails, 'SaleLine', []); // Para notes
```

**Pagos:** Incluye tipos de pago completos
```php
$enrichedPayment['paymentTypeDetails'] = array_get($typePayment, 'PaymentType', []);
```

### 3. Metadata Detallado
```php
'metadata' => [
    'processed_at' => now()->toISOString(),
    'source' => 'lightspeed_serie_r',
    'organization_id' => $orgActive->id,
    'invoice_number' => $invoiceNumber,
    'sale_id' => $saleId,
    'items_count' => count($enrichedSaleLines),
    'payments_count' => count($enrichedPaymentLines),
    'has_customer' => !empty($customerFromSale),
    'api_calls_made' => [
        'getSaleLine' => true,
        'getPayments' => true,
        'getCustomer' => !empty($customerFromSale),
        'getItem_calls' => count(...),
        'getLine_calls' => count(...),
        'getPaymentTypes_calls' => count(...)
    ]
]
```

## Beneficios Logrados

### ✅ Auditoría Completa
- **Datos originales completos** almacenados antes del procesamiento
- **Trazabilidad total** de todas las operaciones API realizadas
- **Información enriquecida** para análisis posterior y debugging

### ✅ Eficiencia Mejorada  
- **Eliminación de llamadas API duplicadas** durante procesamiento
- **Una sola obtención de datos** consolidada al inicio
- **Uso de datos pre-obtenidos** en lugar de nuevas consultas

### ✅ Debugging Superior
- **Metadata detallado** con conteos y timestamps
- **Registro de API calls** realizadas por transacción  
- **Datos originales preservados** para troubleshooting

### ✅ Mantenibilidad
- **Separación clara** entre obtención, almacenamiento y procesamiento
- **Código más limpio** sin lógica duplicada
- **Fácil extensión** para agregar nuevos datos de auditoría

## Ejemplo de Objeto Almacenado
```json
{
  "sale": { /* datos básicos de venta */ },
  "items": [
    {
      "saleLineID": 123,
      "itemID": 456,
      "itemDetails": {
        "description": "Producto A",
        "systemSku": "SKU001"
      }
    }
  ],
  "payments": [
    {
      "salePaymentID": 789,
      "paymentTypeID": 1,
      "paymentTypeDetails": {
        "name": "Efectivo"
      }
    }
  ],
  "customer": { /* datos completos cliente */ },
  "metadata": {
    "processed_at": "2025-08-15T10:30:00.000Z",
    "source": "lightspeed_serie_r",
    "items_count": 3,
    "payments_count": 1,
    "api_calls_made": {
      "getItem_calls": 2,
      "getLine_calls": 1,
      "getPaymentTypes_calls": 1
    }
  }
}
```

## Impacto
- **Auditoría 360°**: Datos completos para análisis y compliance
- **Performance mejorado**: Menos llamadas API redundantes  
- **Debugging eficiente**: Información completa disponible
- **Escalabilidad**: Base sólida para futuras mejoras de auditoría

---
**Commit:** `b32913a` - feat(lightspeed): reorganizar flujo para almacenamiento completo de auditoría  
**Fecha:** 15 Agosto 2025
