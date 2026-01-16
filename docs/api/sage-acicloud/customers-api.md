# API Sage ACICloud - Módulo Clientes

## Crear Clie```json
{
  "CustomerID": "12345",
  "Customer_Bill_Name": "John Doe",
  "AddressLine1": "123 Main St",
  "AddressLine2": "Apt 4B",
  "City": "Some City",
  "State": "CA",
  "Zip": "12345",
  "Country": "US",
  "Telephone1": "1234567890",
  "Email": "john@example.com",
  "RUC": "AB12345",
  "DV": "XYZ123",
  "Custom_field3": "ABC123",
  "Custom_field4": "DEF456",
  "Custom_field5": "GHI789"
}
```

### Respuesta de Éxito

```json
{
  "CustomerID": "12345",
  "Customer_Bill_Name": "John Doe",
  "AddressLine1": "123 Main St",
  "AddressLine2": "Apt 4B", 
  "City": "Some City",
  "State": "CA",
  "Zip": "12345",
  "Country": "US",
  "Telephone1": "1234567890",
  "Email": "john@example.com",
  "Custom_field1": "AB12345",
  "Custom_field2": "XYZ123",
  "Custom_field3": "ABC123",
  "Custom_field4": "DEF456",
  "Custom_field5": "GHI789"
}* `POST /api/acicloud/customer`  
**Request Class:** `CustomersImpRequest`  
**Autenticación:** Bearer Token requerido

### Parámetros de Request

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `CustomerID` | string | ✅ | ID único del cliente | Máx. 20 caracteres, único | `"CUST001"` |
| `Customer_Bill_Name` | string | ✅ | Nombre de facturación | Máx. 39 caracteres | `"Empresa Ejemplo S.A."` |
| `AddressLine1` | string | ❌ | Dirección línea 1 | - | `"Calle 50, Edificio Torre"` |
| `AddressLine2` | string | ❌ | Dirección línea 2 | - | `"Piso 15, Oficina 1501"` |
| `City` | string | ❌ | Ciudad | - | `"Ciudad de Panamá"` |
| `State` | string | ❌ | Estado/Provincia | Máx. 2 caracteres | `"PA"` |
| `Zip` | string | ❌ | Código postal | Máx. 12 caracteres | `"0000"` |
| `Country` | string | ❌ | País | - | `"Panamá"` |
| `Telephone1` | string | ❌ | Teléfono principal | Máx. 20 caracteres | `"+507 1234-5678"` |
| `Email` | string | ❌ | Correo electrónico | Máx. 64 caracteres | `"cliente@empresa.com"` |
| `RUC` | string | ❌ | RUC del cliente | Alfanumérico, máx. 40 | `"1234567890123"` |
| `DV` | string | ❌ | Dígito verificador | Alfanumérico, máx. 40 | `"12"` |
| `Custom_field3` | string | ❌ | Campo personalizado 3 | Alfanumérico, máx. 40 | `"Sector Financiero"` |
| `Custom_field4` | string | ❌ | Campo personalizado 4 | Alfanumérico, máx. 40 | `"VIP"` |
| `Custom_field5` | string | ❌ | Campo personalizado 5 | Alfanumérico, máx. 40 | `"Corporativo"` |

### Reglas de Validación Detalladas

#### CustomerID
- **Requerido**: Sí
- **Tipo**: String
- **Máximo**: 20 caracteres
- **Único**: Debe ser único en la base de datos
- **Ejemplo válido**: `"CUST001"`, `"EMP-2025-001"`

#### Customer_Bill_Name
- **Requerido**: Sí
- **Tipo**: String
- **Máximo**: 39 caracteres
- **Ejemplo válido**: `"Empresa Ejemplo S.A."`

#### RUC y DV
- **Patrón**: `/^[a-zA-Z0-9_-]*$/`
- **Descripción**: Solo letras, números, guiones y guiones bajos
- **Ejemplo válido RUC**: `"1234567890123"`
- **Ejemplo válido DV**: `"12"`

### Ejemplo de Request Completo

```json
{
  "CustomerID": "CUST001",
  "Customer_Bill_Name": "Empresa Ejemplo S.A.",
  "AddressLine1": "Calle 50, Edificio Torre Global",
  "AddressLine2": "Piso 15, Oficina 1501",
  "City": "Ciudad de Panamá",
  "State": "PA",
  "Zip": "0000",
  "Country": "Panamá",
  "Telephone1": "+507 1234-5678",
  "Email": "contacto@empresaejemplo.com",
  "RUC": "1234567890123",
  "DV": "12",
  "Custom_field3": "Sector_Financiero",
  "Custom_field4": "Cliente_VIP",
  "Custom_field5": "Corporativo"
}
```

### Ejemplo de Request Mínimo

```json
{
  "CustomerID": "CUST002",
  "Customer_Bill_Name": "Cliente Básico"
}
```

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "CustomerID": "CUST001",
      "Customer_Bill_Name": "John Doe",
      "PriceLevel": 2,
      "Phone_Number": "1234567890",
      "Contact": "John Contact",
      "Balance": "500.00",
      "CreditLimit": "1000.00",
      "Email": "johndoe@example.com",
      "AddressLine1": "123 Main St",
      "AddressLine2": "Suite 100",
      "City": "Sample City",
      "State": "CA",
      "Zip": "12345",
      "Country": "US",
      "IsActive": 1,
      "SalesRepID": "SR001",
      "SalesRepName": "Jane Smith",
      "Custom_field1": "Custom Value 1",
      "Custom_field2": "Custom Value 2",
      "Custom_field3": "Custom Value 3",
      "Custom_field4": "Custom Value 4",
      "Custom_field5": "Custom Value 5",
      "SalesAccountId": "SA001",
      "Fax_Number": "9876543210"
    }
  ],
  "first_page_url": "https://api.docucenter.com/api/acicloud/customers?page=1",
  "from": 1,
  "last_page": 15,
  "last_page_url": "https://api.docucenter.com/api/acicloud/customers?page=15",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/customers?page=1",
      "label": "1",
      "active": true
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/customers?page=2",
      "label": "2",
      "active": false
    }
  ],
  "next_page_url": "https://api.docucenter.com/api/acicloud/customers?page=2",
  "path": "https://api.docucenter.com/api/acicloud/customers",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 150
}

#### Error de Validación
```json
{
  "success": false,
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "CustomerID": [
      "The CustomerID has already been taken."
    ],
    "Customer_Bill_Name": [
      "The Customer_Bill_Name field is required."
    ],
    "Email": [
      "The Email field must be a valid email address."
    ]
  }
}
```

#### Error de Duplicado
```json
{
  "success": false,
  "message": "El CustomerID ya existe en el sistema.",
  "errors": {
    "CustomerID": [
      "The CustomerID has already been taken."
    ]
  }
}
```

---

## Obtener Clientes

**Endpoint:** `GET /api/acicloud/customers`  
**Request Class:** `ACIcloudCustomersExpRequest`  
**Autenticación:** Bearer Token requerido

### Parámetros de Consulta

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `order_column` | string | No | Columna para ordenamiento |
| `order_direction` | string | No | `asc` o `desc` |
| `limit` | integer | No | Registros por página |
| `f` | array | No | Filtros avanzados |

### Ejemplo de Request

```json
{
  "limit": 50,
  "order_column": "Customer_Bill_Name",
  "order_direction": "asc",
  "f": [
    {
      "column": "Customer_Bill_Name",
      "operator": "like",
      "query_1": "Empresa"
    }
  ]
}
```

### Respuesta

```json
{
  "success": true,
  "data": {
    "customers": [
      {
        "CustomerID": "CUST001",
        "Customer_Bill_Name": "Empresa Ejemplo S.A.",
        "Email": "contacto@empresaejemplo.com",
        "Telephone1": "+507 1234-5678",
        "City": "Ciudad de Panamá",
        "RUC": "1234567890123"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 150
    }
  }
}
```

---

## Obtener Clientes Importados

**Endpoint:** `GET /api/acicloud/customers_imp`  
**Request Class:** `ACIcloudCustomersImpRequest`  
**Autenticación:** Bearer Token requerido

Obtiene la lista de clientes que han sido importados desde DocuCenter a Sage ACICloud.

### Ejemplo de Respuesta

```json
{
  "success": true,
  "data": {
    "customers_imported": [
      {
        "CustomerID": "CUST001",
        "Customer_Bill_Name": "Empresa Ejemplo S.A.",
        "import_date": "2025-08-12T10:30:00Z",
        "status": "imported",
        "sage_sync_status": "synced"
      }
    ]
  }
}
```

### Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 201 | Cliente creado exitosamente |
| 400 | Datos de request inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Ejemplos de cURL

#### Crear Cliente
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/customer" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "CustomerID": "CUST001",
    "Customer_Bill_Name": "Empresa Ejemplo S.A.",
    "Email": "contacto@empresa.com",
    "RUC": "1234567890123"
  }'
```

#### Obtener Clientes
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/customers" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "limit": 10,
    "order_column": "Customer_Bill_Name"
  }'
```

### Notas Técnicas

- El `CustomerID` debe ser único en toda la organización
- Los campos personalizados (`Custom_field3-5`) son útiles para categorización
- La validación del RUC sigue el patrón panameño estándar
- Compatible con el sistema multi-tenant de DocuCenter
