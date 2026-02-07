# API Pagos y Créditos - Sage ACICloud

## Obtener Estado de Órdenes de Venta

**Endpoint:** `GET /api/acicloud/sales_order_status/{salesOrderNumber}`  
**Controller:** `ACIcloudController::salesOrderStatus`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `salesOrderNumber` | string | | Número de orden de venta | `"SO-2025-001"` |

### Respuesta de Éxito

```json
{
  "success": true,
  "data": {
    "sales_order_number": "SO-2025-001",
    "status": "partially_shipped",
    "customer": {
      "id": "CUST001",
      "name": "ABC Corporation S.A.",
      "ruc": "12345678-1-123456"
    },
    "order_date": "2025-01-15",
    "requested_date": "2025-01-25",
    "promised_date": "2025-01-22",
    "total_amount": 2500.00,
    "shipped_amount": 1500.00,
    "remaining_amount": 1000.00,
    "payment_status": "pending",
    "shipping_info": {
      "total_quantity": 10,
      "shipped_quantity": 6,
      "remaining_quantity": 4,
      "shipments": [
        {
          "shipment_number": "SHIP-001",
          "date": "2025-01-20",
          "quantity": 6,
          "amount": 1500.00,
          "tracking_number": "TRK123456789"
        }
      ]
    },
    "items_status": [
      {
        "line_number": 1,
        "product_code": "PROD001",
        "description": "Laptop Dell Inspiron 15",
        "ordered_quantity": 5,
        "shipped_quantity": 3,
        "remaining_quantity": 2,
        "unit_price": 1200.00,
        "line_status": "partially_shipped"
      },
      {
        "line_number": 2,
        "product_code": "PROD002",
        "description": "Mouse Inalámbrico",
        "ordered_quantity": 5,
        "shipped_quantity": 3,
        "remaining_quantity": 2,
        "unit_price": 25.00,
        "line_status": "partially_shipped"
      }
    ]
  }
}
```

### Estados Posibles

- `pending` - Pendiente
- `confirmed` - Confirmada
- `in_production` - En producción
- `ready_to_ship` - Lista para envío
- `partially_shipped` - Parcialmente enviada
- `shipped` - Enviada
- `delivered` - Entregada
- `completed` - Completada
- `cancelled` - Cancelada

---

## Crear Orden de Venta

**Endpoint:** `POST /api/acicloud/sales_order`  
**Request Class:** `SalesOrderRequest`  
**Controller:** `ACIcloudController::salesOrder`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request (Header)

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `SalesOrderNumber` | string | | Número único de orden | Único, máx. 20 chars | `"SO-2025-001"` |
| `CustomerID` | string | | ID del cliente | Máx. 50 characters | `"CUST001"` |
| `CustomerPO` | string | | Orden de compra del cliente | Máx. 50 characters | `"PO-ABC-2025-001"` |
| `CustomerName` | string | | Nombre del cliente | Máx. 39 characters | `"ABC Corporation S.A."` |
| `Subtotal` | decimal | | Subtotal de la orden | Decimal (4 decimales) | `2500.0000` |
| `TaxID` | string | | ID del impuesto | Máx. 8 characters | `"TAX001"` |
| `OrderTax` | decimal | | Impuesto de la orden | Decimal (4 decimales) | `175.0000` |
| `NetDue` | decimal | | Total neto | Decimal (4 decimales) | `2675.0000` |
| `ARAccount` | string | | Cuenta contable AR | Máx. 15 characters | `"1200"` |
| `ShipToName` | string | | Nombre para envío | Máx. 100 characters | `"ABC Corp Warehouse"` |
| `ShipToAddressLine1` | string | | Dirección línea 1 | - | `"Calle 50, Torre Global"` |
| `ShipToAddressLine2` | string | | Dirección línea 2 | - | `"Piso 15"` |
| `ShipToCity` | string | | Ciudad de envío | - | `"Ciudad de Panamá"` |
| `ShipToState` | string | | Estado/Provincia | Máx. 2 characters | `"PA"` |
| `ShipToZip` | string | | Código postal | Máx. 12 characters | `"0833"` |
| `ShipToCountry` | string | | País de envío | - | `"Panamá"` |
| `SalesRepID` | string | | ID representante de ventas | Máx. 20 characters | `"REP001"` |

### Campos de Request (Items)

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `Items[].SalesOrderNumber` | string | | Número de orden (debe coincidir) | Máx. 20 characters | `"SO-2025-001"` |
| `Items[].Taxable` | integer | | Si aplica impuesto | 1 o 2 | `1` |
| `Items[].ItemOrd` | string | | Orden del item | Máx. 20 characters | `"1"` |
| `Items[].ItemId` | string | | ID del producto | Máx. 20 characters | `"PROD001"` |
| `Items[].Description` | string | | Descripción del item | Máx. 160 characters | `"Laptop Dell Inspiron 15"` |
| `Items[].Quantity` | decimal | | Cantidad | Decimal (5 decimales) | `5.00000` |
| `Items[].UnitPrice` | decimal | | Precio unitario | Decimal (4 decimales) | `1200.0000` |
| `Items[].NetLine` | decimal | | Línea neta | Decimal (4 decimales) | `6000.0000` |
| `Items[].JobId` | string | | ID del trabajo | Máx. 20 characters | `"JOB001"` |
| `Items[].JobPhaseID` | string | | ID de fase del trabajo | Máx. 20 characters | `"PHASE01"` |
| `Items[].JobCostCodeID` | string | | ID código de costo | Máx. 20 characters | `"COST001"` |

### Ejemplo de Request

```json
{
  "SalesOrderNumber": "SO-2025-001",
  "CustomerID": "CUST001",
  "CustomerPO": "PO-ABC-2025-001",
  "CustomerName": "ABC Corporation S.A.",
  "Subtotal": 6125.0000,
  "TaxID": "TAX001",
  "OrderTax": 428.7500,
  "NetDue": 6553.7500,
  "ARAccount": "1200",
  "ShipToName": "ABC Corp Warehouse",
  "ShipToAddressLine1": "Calle 50, Torre Global",
  "ShipToAddressLine2": "Piso 15, Oficina 1501",
  "ShipToCity": "Ciudad de Panamá",
  "ShipToState": "PA",
  "ShipToZip": "0833",
  "ShipToCountry": "Panamá",
  "SalesRepID": "REP001",
  "Items": [
    {
      "SalesOrderNumber": "SO-2025-001",
      "Taxable": 1,
      "ItemOrd": "1",
      "ItemId": "PROD001",
      "Description": "Laptop Dell Inspiron 15",
      "Quantity": 5.00000,
      "UnitPrice": 1200.0000,
      "NetLine": 6000.0000,
      "JobId": null,
      "JobPhaseID": null,
      "JobCostCodeID": null
    },
    {
      "SalesOrderNumber": "SO-2025-001",
      "Taxable": 1,
      "ItemOrd": "2",
      "ItemId": "PROD002",
      "Description": "Mouse Inalámbrico",
      "Quantity": 5.00000,
      "UnitPrice": 25.0000,
      "NetLine": 125.0000,
      "JobId": null,
      "JobPhaseID": null,
      "JobCostCodeID": null
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "ID_compania": 1,
  "SalesOrderNumber": "SO-2025-001",
  "CustomerID": "CUST001",
  "CustomerPO": "PO-ABC-2025-001",
  "CustomerName": "ABC Corporation S.A.",
  "Subtotal": "6125.0000",
  "TaxID": "TAX001",
  "OrderTax": "428.7500",
  "Net_due": "6553.7500",
  "Export_date": "2025-01-29T21:30:00.000000Z",
  "Enviado": 0,
  "Error": 0,
  "Items": [
    {
      "ID": 1,
      "ID_compania": 1,
      "SalesOrderNumber": "SO-2025-001",
      "Taxable": 1,
      "ItemOrd": "1",
      "Item_id": "PROD001",
      "Description": "Laptop Dell Inspiron 15",
      "Quantity": "5.00000",
      "Unit_Price": "1200.0000",
      "Net_line": "6000.0000",
      "JobId": null,
      "JobPhaseID": null,
      "JobCostCodeID": null,
      "LAST_CHANGE": "2025-01-29T21:30:00.000000Z",
      "created_at": "2025-01-29T21:30:00.000000Z",
      "updated_at": "2025-01-29T21:30:00.000000Z"
    }
  ]
}
```

### Respuestas de Error

#### Error de Validación
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "SalesOrderNumber": [
      "The SalesOrderNumber has already been taken."
    ],
    "Items.0.Quantity": [
      "The Items.0.Quantity field is required."
    ]
  }
}
```

---

## Obtener Órdenes de Venta

**Endpoint:** `GET /api/acicloud/sales_order`  
**Controller:** `ACIcloudController::getSalesOrder`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `status` | string | | Filtrar por estado | `"pending"` |
| `customer_id` | string | | Filtrar por cliente | `"CUST001"` |
| `date_from` | string | | Fecha desde | `"2025-01-01"` |
| `date_to` | string | | Fecha hasta | `"2025-01-31"` |
| `page` | integer | | Página | `1` |
| `per_page` | integer | | Registros por página | `25` |

### Respuesta de Éxito

```json
{
  "data": [
    {
      "ID": 1,
      "ID_compa": 1,
      "TransaID": "SO001",
      "SalesOrderNumber": "SO-2025-001",
      "CustomerID": "CUST001",
      "CustomerName": "ABC Corporation S.A.",
      "Date": "2025-01-15",
      "Close_SO": "N",
      "CustomerPO": "PO-ABC-2025-001",
      "Total": 2500.00,
      "TaxID": "TAX001",
      "Detalles": [
        {
          "ID": 1,
          "TransactionID": "SO001",
          "ID_compania": 1,
          "Item_id": "PROD001",
          "Description": "Laptop Dell Inspiron 15",
          "Quantity": 2.00,
          "Unit_Price": 1200.00,
          "Net_line": 2400.00,
          "TaxType": 1
        }
      ]
    },
    {
      "ID": 2,
      "ID_compa": 1,
      "TransaID": "SO002",
      "SalesOrderNumber": "SO-2025-002",
      "CustomerID": "CUST002",
      "CustomerName": "XYZ Company Ltd.",
      "Date": "2025-01-18",
      "Close_SO": "N",
      "CustomerPO": "PO-XYZ-2025-001",
      "Total": 3200.00,
      "TaxID": "TAX001",
      "Detalles": [
        {
          "ID": 2,
          "TransactionID": "SO002",
          "ID_compania": 1,
          "Item_id": "PROD002",
          "Description": "Servidor Dell PowerEdge",
          "Quantity": 1.00,
          "Unit_Price": 3200.00,
          "Net_line": 3200.00,
          "TaxType": 1
        }
      ]
    }
  ],
  "links": {
    "first": "https://api.docucenter.com/api/acicloud/sales_order?page=1",
    "last": "https://api.docucenter.com/api/acicloud/sales_order?page=4",
    "prev": null,
    "next": "https://api.docucenter.com/api/acicloud/sales_order?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 4,
    "path": "https://api.docucenter.com/api/acicloud/sales_order",
    "per_page": 10,
    "to": 10,
    "total": 85
  }
}
```

---

## Crear Recibo de Cliente

**Endpoint:** `POST /api/acicloud/customer_receipt_imp`  
**Controller:** `ACIcloudController::customerReceiptImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `receipt_number` | string | | Número del recibo | `"REC-2025-001"` |
| `customer_id` | string | | ID del cliente | `"CUST001"` |
| `date` | string | | Fecha del recibo | `"2025-01-29"` |
| `payment_method` | string | | Método de pago | `"check"` |
| `amount` | decimal | | Monto del pago | `2500.00` |
| `currency` | string | | Moneda | `"USD"` |
| `exchange_rate` | decimal | | Tipo de cambio | `1.00` |
| `reference` | string | | Referencia del pago | `"CHK-123456"` |
| `bank_account` | string | | Cuenta bancaria | `"1100"` |
| `memo` | string | | Memo del recibo | `"Pago factura SO-2025-001"` |
| `applied_invoices` | array | | Facturas aplicadas | `[]` |
| `applied_invoices[].invoice_number` | string | | Número de factura | `"INV-2025-001"` |
| `applied_invoices[].amount_applied` | decimal | | Monto aplicado | `2500.00` |
| `applied_invoices[].discount_taken` | decimal | | Descuento tomado | `0.00` |

### Métodos de Pago Válidos

- `cash` - Efectivo
- `check` - Cheque
- `credit_card` - Tarjeta de crédito
- `bank_transfer` - Transferencia bancaria
- `ach` - ACH
- `wire_transfer` - Transferencia wire
- `other` - Otros

### Ejemplo de Request

```json
{
  "receipt_number": "REC-2025-001",
  "customer_id": "CUST001",
  "date": "2025-01-29",
  "payment_method": "check",
  "amount": 2500.00,
  "currency": "USD",
  "reference": "CHK-123456",
  "bank_account": "1100",
  "memo": "Pago de factura SO-2025-001",
  "applied_invoices": [
    {
      "invoice_number": "INV-2025-001",
      "amount_applied": 2500.00,
      "discount_taken": 0.00
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Recibo de cliente creado exitosamente",
  "data": {
    "receipt_number": "REC-2025-001",
    "sage_id": "SAGE_REC_001",
    "amount": 2500.00,
    "customer_balance_updated": true,
    "invoices_applied": 1,
    "created_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Crear Nota de Crédito de Cliente

**Endpoint:** `POST /api/acicloud/customer_credit_memo`  
**Controller:** `ACIcloudController::customerCreditMemoImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `credit_memo_number` | string | | Número de nota de crédito | `"CM-2025-001"` |
| `customer_id` | string | | ID del cliente | `"CUST001"` |
| `date` | string | | Fecha de la nota | `"2025-01-29"` |
| `reason` | string | | Razón de la nota de crédito | `"Product return"` |
| `reference_invoice` | string | | Factura de referencia | `"INV-2025-001"` |
| `currency` | string | | Moneda | `"USD"` |
| `items` | array | | Items de la nota de crédito | `[]` |
| `items[].product_code` | string | | Código del producto | `"PROD001"` |
| `items[].description` | string | | Descripción | `"Laptop Dell devuelta"` |
| `items[].quantity` | decimal | | Cantidad | `1.00` |
| `items[].unit_price` | decimal | | Precio unitario | `1200.00` |
| `items[].tax_rate` | decimal | | Tasa de impuesto | `0.07` |
| `items[].reason` | string | | Razón específica del item | `"Defective unit"` |

### Ejemplo de Request

```json
{
  "credit_memo_number": "CM-2025-001",
  "customer_id": "CUST001",
  "date": "2025-01-29",
  "reason": "Product return - defective unit",
  "reference_invoice": "INV-2025-001",
  "currency": "USD",
  "items": [
    {
      "product_code": "PROD001",
      "description": "Laptop Dell Inspiron 15 - devuelta",
      "quantity": 1.00,
      "unit_price": 1200.00,
      "tax_rate": 0.07,
      "reason": "Defective screen"
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Nota de crédito creada exitosamente",
  "data": {
    "credit_memo_number": "CM-2025-001",
    "sage_id": "SAGE_CM_001",
    "subtotal": 1200.00,
    "tax_amount": 84.00,
    "total_amount": 1284.00,
    "customer_balance_updated": true,
    "created_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Obtener Notas de Crédito Importadas

**Endpoint:** `GET /api/acicloud/customer_credit_memo_imp`  
**Controller:** `ACIcloudController::getCustomerCreditMemoImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "credit_memo_number": "CM-2025-001",
      "customer_id": "CUST001",
      "customer_name": "ABC Corporation S.A.",
      "sage_id": "SAGE_CM_001",
      "date": "2025-01-29",
      "total_amount": 1284.00,
      "reason": "Product return - defective unit",
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 8,
    "total_pages": 1
  }
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Recibo/Nota de crédito creado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 404 | Orden/Cliente no encontrado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

#### Consultar Estado de Orden de Venta
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/sales_order_status/SO-2025-001" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Obtener Órdenes de Venta
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/sales_order?status=pending&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Crear Recibo de Cliente
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/customer_receipt_imp" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "receipt_number": "REC-2025-001",
    "customer_id": "CUST001",
    "date": "2025-01-29",
    "payment_method": "check",
    "amount": 2500.00,
    "reference": "CHK-123456"
  }'
```

#### Crear Nota de Crédito
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/customer_credit_memo" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "credit_memo_number": "CM-2025-001",
    "customer_id": "CUST001",
    "date": "2025-01-29",
    "reason": "Product return",
    "items": [
      {
        "product_code": "PROD001",
        "description": "Laptop devuelta",
        "quantity": 1.00,
        "unit_price": 1200.00
      }
    ]
  }'
```

---

## Notas Técnicas

- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Validación de Saldos**: Se verifica que los pagos no excedan saldos pendientes
- **Aplicación Automática**: Los recibos pueden aplicarse automáticamente a facturas
- **Control de Créditos**: Las notas de crédito actualizan automáticamente saldos
- **Trazabilidad**: Mantiene referencias a documentos originales
- **Monedas Múltiples**: Soporte para pagos en diferentes monedas

### Gestión de Cobros

- **Recibos**: Registro de pagos recibidos de clientes
- **Aplicación**: Aplicación de pagos a facturas específicas
- **Conciliación**: Proceso de conciliación bancaria
- **Seguimiento**: Control de antigüedad de saldos
- **Reportes**: Reportes de cobranza y flujo de caja

### Control de Créditos

- **Notas de Crédito**: Manejo de devoluciones y ajustes
- **Aplicación**: Aplicación automática a facturas pendientes
- **Inventario**: Actualización de inventario en devoluciones
- **Contabilidad**: Asientos contables automáticos
- **Aprobaciones**: Flujo de aprobación para notas de crédito

### Integración con Sage ACICloud

Los pagos y créditos se integran completamente con el módulo de cuentas por cobrar de Sage ACICloud, actualizando automáticamente balances y generando asientos contables.
