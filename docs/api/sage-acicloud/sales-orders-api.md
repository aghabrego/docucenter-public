# API Sage ACICloud - Módulo Órdenes de Venta

## Crear Orden de Venta

**Endpoint:** `POST /api/acicloud/sales_order`  
**Request Class:** `SalesOrderRequest`  
**Autenticación:** Bearer Token requerido

### Parámetros del Header de Venta

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `SalesOrderNumber` | string | | Número de orden único | Máx. 20 caracteres, único | `"SO-2025-001"` |
| `CustomerID` | string | | ID del cliente | Máx. 50 caracteres | `"CUST001"` |
| `CustomerPO` | string | | Número de PO del cliente | Máx. 50 caracteres | `"PO-REF-123"` |
| `CustomerName` | string | | Nombre del cliente | Máx. 39 caracteres | `"Cliente Ejemplo"` |
| `Subtotal` | decimal | | Subtotal de la orden | 4 decimales | `100.00` |
| `TaxID` | string | | ID del impuesto | Máx. 8 caracteres | `"TAX001"` |
| `OrderTax` | decimal | | Total de impuestos | 4 decimales | `7.00` |
| `NetDue` | decimal | | Total neto a pagar | 4 decimales | `107.00` |
| `ARAccount` | string | | Cuenta de cuentas por cobrar | Máx. 15 caracteres | `"1200"` |
| `ShipToName` | string | | Nombre para envío | Máx. 100 caracteres | `"Cliente Ejemplo"` |
| `ShipToAddressLine1` | string | | Dirección de envío línea 1 | - | `"Calle Principal 123"` |
| `ShipToAddressLine2` | string | | Dirección de envío línea 2 | - | `"Apto 2B"` |
| `ShipToCity` | string | | Ciudad de envío | - | `"Ciudad de Panamá"` |
| `ShipToState` | string | | Estado de envío | Máx. 2 caracteres | `"PA"` |
| `ShipToZip` | string | | Código postal de envío | Máx. 12 caracteres | `"0000"` |
| `ShipToCountry` | string | | País de envío | - | `"Panamá"` |
| `SalesRepID` | string | | ID del representante de ventas | Máx. 20 caracteres | `"REP001"` |

### Parámetros de Items (Array)

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `SalesOrderNumber` | string | | Número de orden (debe coincidir) | Máx. 20 caracteres | `"SO-2025-001"` |
| `Taxable` | integer | | Si el item es gravable | 1 (gravable) o 2 (no gravable) | `1` |
| `ItemOrd` | string | | Orden del item | Máx. 20 caracteres | `"1"` |
| `ItemId` | string | | ID del producto/item | Máx. 20 caracteres | `"PROD001"` |
| `Description` | string | | Descripción del item | Máx. 160 caracteres | `"Producto Ejemplo"` |
| `Quantity` | decimal | | Cantidad | 5 decimales | `2.00000` |
| `UnitPrice` | decimal | | Precio unitario | 4 decimales | `50.0000` |
| `NetLine` | decimal | | Total de la línea | 4 decimales | `100.0000` |
| `JobId` | string | | ID del trabajo/proyecto | Máx. 20 caracteres | `"JOB001"` |
| `JobPhaseID` | string | | ID de la fase del trabajo | Máx. 20 caracteres | `"PHASE1"` |
| `JobCostCodeID` | string | | ID del código de costo | Máx. 20 caracteres | `"CODE1"` |

### Ejemplo de Request Completo

```json
{
  "SalesOrderNumber": "SO-2025-001",
  "CustomerID": "CUST001",
  "CustomerPO": "PO-REF-123",
  "CustomerName": "Cliente Ejemplo S.A.",
  "Subtotal": 150.0000,
  "TaxID": "TAX001",
  "OrderTax": 10.5000,
  "NetDue": 160.5000,
  "ARAccount": "1200",
  "ShipToName": "Cliente Ejemplo S.A.",
  "ShipToAddressLine1": "Calle Principal 123",
  "ShipToAddressLine2": "Edificio Torre, Piso 5",
  "ShipToCity": "Ciudad de Panamá",
  "ShipToState": "PA",
  "ShipToZip": "0000",
  "ShipToCountry": "Panamá",
  "SalesRepID": "REP001",
  "Items": [
    {
      "SalesOrderNumber": "SO-2025-001",
      "Taxable": 1,
      "ItemOrd": "1",
      "ItemId": "PROD001",
      "Description": "Producto Premium - Licencia Anual",
      "Quantity": 1.00000,
      "UnitPrice": 100.0000,
      "NetLine": 100.0000,
      "JobId": "JOB001",
      "JobPhaseID": "PHASE1",
      "JobCostCodeID": "CODE1"
    },
    {
      "SalesOrderNumber": "SO-2025-001",
      "Taxable": 1,
      "ItemOrd": "2", 
      "ItemId": "PROD002",
      "Description": "Servicio de Implementación",
      "Quantity": 2.00000,
      "UnitPrice": 25.0000,
      "NetLine": 50.0000,
      "JobId": "JOB001",
      "JobPhaseID": "PHASE2",
      "JobCostCodeID": "CODE2"
    }
  ]
}
```

### Ejemplo de Request Mínimo

```json
{
  "SalesOrderNumber": "SO-2025-002",
  "CustomerID": "CUST002",
  "CustomerName": "Cliente Básico",
  "Subtotal": 50.0000,
  "TaxID": "TAX001",
  "OrderTax": 3.5000,
  "NetDue": 53.5000,
  "ShipToName": "Cliente Básico",
  "Items": [
    {
      "SalesOrderNumber": "SO-2025-002",
      "Taxable": 1,
      "ItemId": "PROD003",
      "Description": "Servicio Básico",
      "Quantity": 1.00000,
      "UnitPrice": 50.0000,
      "NetLine": 50.0000
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Orden de venta creada exitosamente",
  "data": {
    "SalesOrderNumber": "SO-2025-001",
    "CustomerID": "CUST001",
    "NetDue": 160.5000,
    "ItemCount": 2,
    "Status": "created",
    "created_at": "2025-08-12T10:30:00Z"
  }
}
```

### Respuestas de Error

#### Error de Validación
```json
{
  "success": false,
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "SalesOrderNumber": [
      "The SalesOrderNumber has already been taken."
    ],
    "Items.0.ItemId": [
      "The Items.0.ItemId field is required."
    ],
    "Items.1.Quantity": [
      "The Items.1.Quantity field must be a number."
    ]
  }
}
```

#### Error de Lógica de Negocio
```json
{
  "success": false,
  "message": "Error en la lógica de negocio",
  "errors": {
    "Subtotal": [
      "El subtotal no coincide con la suma de los items"
    ],
    "CustomerID": [
      "El cliente especificado no existe"
    ]
  }
}
```

---

## Consultar Estado de Orden de Venta

**Endpoint:** `GET /api/acicloud/sales_order_status/{salesOrderNumber}`  
**Autenticación:** Bearer Token requerido

### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `salesOrderNumber` | string | | Número de la orden de venta |

### Ejemplo de Request

```bash
GET /api/acicloud/sales_order_status/SO-2025-001
```

### Respuesta de Éxito

```json
{
  "success": true,
  "data": {
    "SalesOrderNumber": "SO-2025-001",
    "Status": "confirmed",
    "CustomerID": "CUST001",
    "CustomerName": "Cliente Ejemplo S.A.",
    "NetDue": 160.5000,
    "OrderDate": "2025-08-12",
    "ShipDate": null,
    "InvoiceStatus": "pending",
    "Items": [
      {
        "ItemId": "PROD001",
        "Description": "Producto Premium - Licencia Anual",
        "Quantity": 1.00000,
        "UnitPrice": 100.0000,
        "Status": "confirmed"
      }
    ]
  }
}
```

### Respuesta de Error - Orden No Encontrada

```json
{
  "success": false,
  "message": "Orden de venta no encontrada",
  "error": "NOT_FOUND"
}
```

---

## Obtener Órdenes de Venta

**Endpoint:** `GET /api/acicloud/sales_order`  
**Request Class:** `ACIcloudSalesOrderHeaderExpRequest`  
**Autenticación:** Bearer Token requerido

### Parámetros de Consulta

Similar a otros endpoints GET, soporta filtrado avanzado y paginación.

### Ejemplo de Request

```json
{
  "limit": 20,
  "order_column": "SalesOrderNumber",
  "order_direction": "desc",
  "f": [
    {
      "column": "CustomerID",
      "operator": "eq",
      "query_1": "CUST001"
    }
  ]
}
```

### Respuesta

```json
{
  "success": true,
  "data": {
    "sales_orders": [
      {
        "SalesOrderNumber": "SO-2025-001",
        "CustomerID": "CUST001",
        "CustomerName": "Cliente Ejemplo S.A.",
        "NetDue": 160.5000,
        "Status": "confirmed",
        "OrderDate": "2025-08-12"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 45
    }
  }
}
```

### Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Orden creada exitosamente |
| 400 | Datos de request inválidos |
| 401 | No autorizado |
| 404 | Orden no encontrada |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Ejemplos de cURL

#### Crear Orden de Venta
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/sales_order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "SalesOrderNumber": "SO-2025-001",
    "CustomerID": "CUST001",
    "CustomerName": "Cliente Ejemplo",
    "Subtotal": 100.0000,
    "TaxID": "TAX001",
    "OrderTax": 7.0000,
    "NetDue": 107.0000,
    "ShipToName": "Cliente Ejemplo",
    "Items": [
      {
        "SalesOrderNumber": "SO-2025-001",
        "Taxable": 1,
        "ItemId": "PROD001",
        "Description": "Producto de prueba",
        "Quantity": 1.00000,
        "UnitPrice": 100.0000,
        "NetLine": 100.0000
      }
    ]
  }'
```

#### Consultar Estado
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/sales_order_status/SO-2025-001" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Notas Técnicas

- El `SalesOrderNumber` debe ser único en toda la organización
- Los items deben referenciar el mismo `SalesOrderNumber`
- El valor `Taxable` determina si se aplican impuestos: 1 = Sí, 2 = No
- Los campos de Job son opcionales y útiles para proyectos específicos
- El cálculo de totales debe ser consistente entre header e items
