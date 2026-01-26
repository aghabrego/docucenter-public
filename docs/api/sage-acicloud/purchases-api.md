# API Compras - Sage ACICloud

## Obtener Lista de Compras

**Endpoint:** `GET /api/acicloud/purchases`  
**Controller:** `ACIcloudController::purchases`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Características del Endpoint

- **Filtrado Avanzado**: Múltiples filtros y operadores
- **Paginación**: Resultados paginados automáticamente  
- **Ordenamiento**: Ordenar por cualquier campo
- **Multi-tenant**: Respeta el contexto de organización activa

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `page` | integer | | Página a obtener | `2` |
| `per_page` | integer | | Registros por página (máx 100) | `50` |
| `sort` | string | | Campo para ordenar | `"date"` |
| `order` | string | | Dirección del ordenamiento | `"desc"` |
| `filter` | object | | Filtros aplicados | `{}` |

### Respuesta de Éxito

```json
{
  "data": [
    {
      "ID_compania": 1,
      "PurchaseID": "PUR001",
      "PurchaseNumber": "PO-2025-001",
      "VendorID": "VENDOR001",
      "VendorName": "Distribuidora Nacional S.A.",
      "Date": "2025-01-29",
      "AccountID": "5100",
      "CustomerSO": null,
      "Total": 1605.00,
      "ApplyToPurchaseOrder": "Y",
      "ApplyToPurOrderNumber": "PO-2025-001",
      "Detalles": [
        {
          "ID": 1,
          "TransactionID": "PUR001",
          "ID_compania": 1,
          "Item_id": "PROD001",
          "Description": "Laptop Dell Inspiron 15",
          "GL_Acct": "5100",
          "Quantity": 10.00,
          "Unit_Price": 150.00,
          "Net_line": 1500.00,
          "JobId": null,
          "JobPhaseID": null,
          "JobCostCodeID": null
        }
      ]
    }
  ],
  "links": {
    "first": "https://api.docucenter.com/api/acicloud/purchases?page=1",
    "last": "https://api.docucenter.com/api/acicloud/purchases?page=4",
    "prev": null,
    "next": "https://api.docucenter.com/api/acicloud/purchases?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 4,
    "path": "https://api.docucenter.com/api/acicloud/purchases",
    "per_page": 10,
    "to": 10,
    "total": 85
  }
}
```

---

## Crear Compra

**Endpoint:** `POST /api/acicloud/purchase`  
**Request Class:** `PurchaseImpRequest`  
**Controller:** `ACIcloudController::purchaseImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `purchase_order_number` | string | | Número de orden de compra | `"PO-2025-001"` |
| `vendor_id` | string | | ID del proveedor | `"VENDOR001"` |
| `date` | string | | Fecha de la compra (YYYY-MM-DD) | `"2025-01-29"` |
| `due_date` | string | | Fecha de vencimiento | `"2025-02-28"` |
| `currency` | string | | Moneda (USD, PAB) | `"USD"` |
| `reference` | string | | Referencia adicional | `"REF-001"` |
| `notes` | string | | Notas adicionales | `"Compra urgente"` |
| `items` | array | | Lista de productos/servicios | `[]` |
| `items[].item_code` | string | | Código del producto | `"PROD001"` |
| `items[].description` | string | | Descripción del item | `"Producto Ejemplo"` |
| `items[].quantity` | decimal | | Cantidad | `10.00` |
| `items[].unit_price` | decimal | | Precio unitario | `150.00` |
| `items[].tax_rate` | decimal | | Tasa de impuesto (0.07 = 7%) | `0.07` |
| `items[].account_code` | string | | Código de cuenta contable | `"5100"` |

### Validaciones

- **purchase_order_number**: Único, máximo 50 caracteres
- **vendor_id**: Debe existir en el sistema
- **date**: Formato YYYY-MM-DD
- **currency**: Solo "USD" o "PAB"
- **items**: Mínimo 1 item requerido
- **quantity**: Valor positivo
- **unit_price**: Valor positivo

### Ejemplo de Request

```json
{
  "purchase_order_number": "PO-2025-001",
  "vendor_id": "VENDOR001",
  "date": "2025-01-29",
  "due_date": "2025-02-28",
  "currency": "USD",
  "reference": "REF-001",
  "notes": "Compra de productos para inventario",
  "items": [
    {
      "item_code": "PROD001",
      "description": "Producto Ejemplo A",
      "quantity": 10.00,
      "unit_price": 150.00,
      "tax_rate": 0.07,
      "account_code": "5100"
    },
    {
      "item_code": "PROD002", 
      "description": "Producto Ejemplo B",
      "quantity": 5.00,
      "unit_price": 200.00,
      "tax_rate": 0.07,
      "account_code": "5100"
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Compra creada exitosamente",
  "data": {
    "purchase_order_number": "PO-2025-001",
    "sage_id": "SAGE_PO_001",
    "subtotal": 2500.00,
    "tax_amount": 175.00,
    "total_amount": 2675.00,
    "status": "pending",
    "created_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Obtener Compras Importadas

**Endpoint:** `GET /api/acicloud/purchases_imp`  
**Controller:** `ACIcloudController::getPurchasesImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Obtiene la lista de compras que han sido importadas/creadas a través de la API.

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "TransactionID": "PUR_IMP_001",
      "ID_compania": 1,
      "PurchaseNumber": "PO-2025-001",
      "VendorID": "VENDOR001",
      "VendorName": "Distribuidora Nacional S.A.",
      "AP_Account": "2100",
      "Date": "2025-01-29 00:00:00",
      "Subtotal": "2500.00",
      "Net_due": "2675.00",
      "ApplyToPO": "Y",
      "DueDate": "2025-02-28 00:00:00",
      "Export_date": "2025-01-29 21:30:00",
      "Enviado": 1,
      "Error": 0,
      "ErrorPT": null,
      "Batch": "BATCH001",
      "Expire": null,
      "Hs_Code": null,
      "Weight": null,
      "Palet": null,
      "Package": null,
      "created_at": "2025-01-29T21:30:00.000000Z",
      "updated_at": "2025-01-29T21:30:00.000000Z"
    }
  ],
  "from": 1,
  "to": 1,
  "total": 1,
  "per_page": 10,
  "current_page": 1,
  "last_page": 1,
  "first_page_url": "https://api.docucenter.com/api/acicloud/purchases_imp?page=1",
  "last_page_url": "https://api.docucenter.com/api/acicloud/purchases_imp?page=1",
  "next_page_url": null,
  "prev_page_url": null,
  "path": "https://api.docucenter.com/api/acicloud/purchases_imp"
}
```

---

## Obtener Órdenes de Compra

**Endpoint:** `GET /api/acicloud/purchase_order`  
**Controller:** `ACIcloudController::getPurchaseOrder`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Obtiene las órdenes de compra del sistema Sage ACICloud.

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `status` | string | | Filtrar por estado | `"pending"` |
| `vendor_id` | string | | Filtrar por proveedor | `"VENDOR001"` |
| `date_from` | string | | Fecha desde (YYYY-MM-DD) | `"2025-01-01"` |
| `date_to` | string | | Fecha hasta (YYYY-MM-DD) | `"2025-01-31"` |

### Estados Posibles

- `pending` - Pendiente
- `approved` - Aprobada  
- `partially_received` - Parcialmente recibida
- `completed` - Completada
- `cancelled` - Cancelada

### Respuesta de Éxito

```json
{
  "data": [
    {
      "ID": 1,
      "ID_compa": 1,
      "TransactionID": "PO001",
      "PurchaseOrderNumber": "PO-2025-001",
      "VendorID": "VENDOR001",
      "VendorName": "Distribuidora Nacional S.A.",
      "Date": "2025-01-29",
      "Close_PO": "N",
      "Total": 2675.00,
      "TaxID": "TAX001",
      "GoodThruDate": "2025-02-28",
      "Detalles": [
        {
          "ID": 1,
          "TransactionID": "PO001",
          "ID_compania": 1,
          "Item_id": "PROD001",
          "Description": "Laptop Dell Inspiron 15",
          "Quantity": 10.00,
          "Unit_Price": 150.00,
          "Net_line": 1500.00,
          "TaxType": 1
        },
        {
          "ID": 2,
          "TransactionID": "PO001",
          "ID_compania": 1,
          "Item_id": "PROD002",
          "Description": "Mouse Inalámbrico",
          "Quantity": 5.00,
          "Unit_Price": 200.00,
          "Net_line": 1000.00,
          "TaxType": 1
        }
      ]
    }
  ],
  "links": {
    "first": "https://api.docucenter.com/api/acicloud/purchase_order?page=1",
    "last": "https://api.docucenter.com/api/acicloud/purchase_order?page=2",
    "prev": null,
    "next": "https://api.docucenter.com/api/acicloud/purchase_order?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "path": "https://api.docucenter.com/api/acicloud/purchase_order",
    "per_page": 10,
    "to": 10,
    "total": 15
  }
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Compra creada exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 404 | Proveedor no encontrado |
| 409 | Número de orden duplicado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

#### Obtener Lista de Compras
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/purchases?page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Crear Compra
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/purchase" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "purchase_order_number": "PO-2025-001",
    "vendor_id": "VENDOR001",
    "date": "2025-01-29",
    "items": [
      {
        "item_code": "PROD001",
        "description": "Producto Ejemplo",
        "quantity": 10.00,
        "unit_price": 150.00,
        "tax_rate": 0.07
      }
    ]
  }'
```

#### Obtener Órdenes de Compra
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/purchase_order?status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Obtener Compras Importadas
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/purchases_imp" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Notas Técnicas

- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Sincronización**: Las compras se sincronizan automáticamente con Sage
- **Validación**: Validación estricta de proveedores y productos
- **Monedas**: Soporte para USD y PAB
- **Estados**: Control de estados del flujo de compras
- **Contabilidad**: Integración con cuentas contables

### Flujo de Compras

1. **Creación**: Crear orden de compra con items
2. **Aprobación**: Proceso de aprobación interno
3. **Envío al Proveedor**: Notificación al proveedor
4. **Recepción**: Marcar items como recibidos
5. **Facturación**: Asociar con facturas del proveedor
6. **Pago**: Procesamiento de pagos

### Integración con Sage ACICloud

Las compras creadas se sincronizan automáticamente con Sage ACICloud, creando las transacciones correspondientes en el sistema ERP.
