# API Sage ACICloud - Módulo Facturas

## Obtener Facturas

**Endpoint:** `GET /api/acicloud/invoices`  
**Request Class:** `ACIcloudRequest`  
**Autenticación:** Bearer Token requerido

### Parámetros de Consulta

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `order_column` | string | No | Columna para ordenamiento | `"TransactionID"` |
| `order_direction` | string | No | Dirección del ordenamiento | `"desc"` |
| `limit` | integer | No | Límite de registros (mín: 1) | `50` |
| `filter_match` | string | No | Lógica de filtros | `"and"` |
| `f` | array | No | Array de filtros avanzados | Ver tabla de filtros |

### Columnas Disponibles para Filtrado

| Campo | Descripción |
|-------|-------------|
| `TransactionID` | ID de transacción |
| `SalesInvoiceNumber` | Número de factura de venta |
| `CustomerID` | ID del cliente |
| `CustomerName` | Nombre del cliente |
| `Date` | Fecha de la factura |
| `ShipDate` | Fecha de envío |
| `Total` | Total de la factura |
| `TaxID` | ID del impuesto |

### Columnas Ordenables

| Campo |
|-------|
| `TransactionID` |
| `SalesInvoiceNumber` |
| `Total` |
| `Date` |
| `ShipDate` |

### Operadores de Filtro

| Operador | Descripción | Requiere query_2 |
|----------|-------------|------------------|
| `eq` | Igual a | No |
| `ne` | No igual a | No |
| `gt` | Mayor que | No |
| `gte` | Mayor o igual que | No |
| `lt` | Menor que | No |
| `lte` | Menor o igual que | No |
| `like` | Contiene | No |
| `not_like` | No contiene | No |
| `between` | Entre | Sí |
| `not_between` | No entre | Sí |
| `in` | En lista | No |
| `not_in` | No en lista | No |

### Estructura de Filtros

```json
{
  "column": "string", // REQUERIDO - Una de las columnas disponibles
  "operator": "string", // REQUERIDO - Uno de los operadores válidos
  "query_1": "string", // REQUERIDO - Valor a filtrar
  "query_2": "string" // OPCIONAL - Solo requerido para between y not_between
}
```

### Ejemplo de Request

```json
{
  "order_column": "Date",
  "order_direction": "desc",
  "limit": 100,
  "filter_match": "and",
  "f": [
    {
      "column": "Date",
      "operator": "between",
      "query_1": "2025-01-01",
      "query_2": "2025-12-31"
    },
    {
      "column": "Total",
      "operator": "gte",
      "query_1": "100.00"
    },
    {
      "column": "CustomerName",
      "operator": "like",
      "query_1": "Cliente"
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "ID_compania": 1,
      "TransactionID": "INV001",
      "SalesInvoiceNumber": "FAC-2025-0001",
      "CustomerID": "CUST001",
      "CustomerName": "ABC Corporation S.A.",
      "Date": "2025-01-29",
      "CustomerPO": "PO-ABC-001",
      "Total": 2621.50,
      "TaxID": "TAX001",
      "ShipDate": "2025-01-30",
      "ShipToName": "ABC Corporation S.A.",
      "AmountPaid": 0.00,
      "ShipToAddress2": "Piso 15, Oficina 1501",
      "ShipToCity": "Ciudad de Panamá",
      "ShipToState": "Panamá",
      "ShipToZip": "0833-01234",
      "ShipToCountry": "Panamá",
      "details": [
        {
          "ID": 1,
          "TransactionID": "INV001",
          "ID_compania": 1,
          "Item_id": "PROD001",
          "Description": "Laptop Dell Inspiron 15",
          "Quantity": 2.00,
          "Unit_Price": 1200.00,
          "Net_line": 2400.00,
          "TaxType": 1,
          "Tax_amount": 168.00,
          "Line_total": 2568.00
        },
        {
          "ID": 2,
          "TransactionID": "INV001",
          "ID_compania": 1,
          "Item_id": "PROD002",
          "Description": "Mouse Inalámbrico Logitech",
          "Quantity": 2.00,
          "Unit_Price": 25.00,
          "Net_line": 50.00,
          "TaxType": 1,
          "Tax_amount": 3.50,
          "Line_total": 53.50
        }
      ]
    }
  ],
  "first_page_url": "https://api.docucenter.com/api/acicloud/invoices?page=1",
  "from": 1,
  "last_page": 25,
  "last_page_url": "https://api.docucenter.com/api/acicloud/invoices?page=25",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/invoices?page=1",
      "label": "1",
      "active": true
    }
  ],
  "next_page_url": "https://api.docucenter.com/api/acicloud/invoices?page=2",
  "path": "https://api.docucenter.com/api/acicloud/invoices",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 250
}
```

### Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 400 | Error en parámetros de consulta |
| 401 | Token de autenticación inválido |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Respuesta de Error

```json
{
  "success": false,
  "message": "Descripción del error",
  "errors": {
    "order_column": [
      "The order column field must be one of: TransactionID, SalesInvoiceNumber, Total, Date, ShipDate."
    ]
  }
}
```

### Ejemplos de cURL

#### Consulta Básica
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/invoices" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "limit": 10,
    "order_column": "Date",
    "order_direction": "desc"
  }'
```

#### Consulta con Filtros
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/invoices" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "f": [
      {
        "column": "CustomerName",
        "operator": "like",
        "query_1": "Empresa"
      }
    ]
  }'
```

### Notas Técnicas

- Utiliza el trait `DataViewer` para filtrado avanzado
- Soporta paginación automática
- Implementa caché de consultas para mejor rendimiento
- Compatible con conexiones multi-tenant de DocuCenter
