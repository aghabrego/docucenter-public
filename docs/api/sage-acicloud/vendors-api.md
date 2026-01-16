# API Proveedores - Sage ACICloud

## Obtener Lista de Proveedores

**Endpoint:** `GET /api/acicloud/vendors`  
**Controller:** `ACIcloudController::vendors`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Características del Endpoint

- ✅ **Filtrado Avanzado**: Soporte para múltiples filtros y operadores
- ✅ **Paginación**: Resultados paginados automáticamente
- ✅ **Ordenamiento**: Ordenar por cualquier campo
- ✅ **Multi-tenant**: Respeta el contexto de organización activa

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `page` | integer | ❌ | Página a obtener | `2` |
| `per_page` | integer | ❌ | Registros por página (máx 100) | `50` |
| `sort` | string | ❌ | Campo para ordenar | `"name"` |
| `order` | string | ❌ | Dirección del ordenamiento | `"asc"` o `"desc"` |
| `filter` | object | ❌ | Filtros aplicados (ver sección de filtros) | `{}` |

### Sistema de Filtros Avanzados

#### Operadores Disponibles
- `equals` - Igual a
- `not_equals` - No igual a
- `contains` - Contiene texto
- `starts_with` - Empieza con
- `ends_with` - Termina con
- `greater_than` - Mayor que
- `less_than` - Menor que
- `between` - Entre dos valores
- `in` - En lista de valores
- `not_in` - No en lista de valores
- `is_null` - Es nulo
- `is_not_null` - No es nulo

### Ejemplo de Request

```bash
GET /api/acicloud/vendors?page=1&per_page=25&sort=name&order=asc
```

### Ejemplo con Filtros

```bash
GET /api/acicloud/vendors?filter[name][operator]=contains&filter[name][value]=Distribuidora&filter[active][operator]=equals&filter[active][value]=1
```

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "ID_compania": 2,
      "VendorID": "0001",
      "Name": "vendor",
      "Contact": "tony",
      "Phone_Number1": "123-123",
      "Email": "tony@gmail.com",
      "AddressLine1": "123-321",
      "AddressLine2": "098-456",
      "City": "San Francisco",
      "State": "CA",
      "Zip": "123",
      "Country": "PA",
      "IsActive": "1",
      "Custom_field1": "qwe",
      "Custom_field2": null,
      "Custom_field3": null,
      "Custom_field4": null,
      "Custom_field5": "dsa",
      "ExpensesAccountId": null,
      "VendorType": null
    }
  ],
  "first_page_url": "https://api.docucenter.com/api/acicloud/vendors?page=1",
  "from": 1,
  "last_page": 6,
  "last_page_url": "https://api.docucenter.com/api/acicloud/vendors?page=6",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/vendors?page=1",
      "label": "1",
      "active": true
    }
  ],
  "next_page_url": "https://api.docucenter.com/api/acicloud/vendors?page=2",
  "path": "https://api.docucenter.com/api/acicloud/vendors",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 150
}
```

---

## Crear Proveedor

**Endpoint:** `POST /api/acicloud/vendor`  
**Request Class:** `VendorsImpRequest`  
**Controller:** `ACIcloudController::vendorsImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `VendorID` | string | ✅ | ID único del proveedor | Único, máx. 20 chars | `"VENDOR001"` |
| `VendrName` | string | ✅ | Nombre del proveedor | Máx. 39 characters | `"Distribuidora Nacional S.A."` |
| `PhoneNumber` | string | ❌ | Teléfono del proveedor | Máx. 20 characters | `"+507 6789-1234"` |
| `Email` | string | ❌ | Email del proveedor | Email válido, único, máx. 64 | `"compras@distribuidora.com"` |
| `AddressLine1` | string | ❌ | Dirección línea 1 | - | `"Calle 50, Edificio Global Bank"` |
| `AddressLine2` | string | ❌ | Dirección línea 2 | - | `"Piso 12, Oficina 1201"` |
| `City` | string | ✅ | Ciudad | Requerido | `"Ciudad de Panamá"` |
| `State` | string | ✅ | Estado/Provincia | Requerido, máx. 2 chars | `"PA"` |
| `Zip` | string | ❌ | Código postal | Máx. 12 characters | `"0833-01234"` |
| `Country` | string | ✅ | País | Requerido | `"Panamá"` |
| `RUC` | string | ❌ | RUC del proveedor | Alfanumérico, máx. 40 | `"87654321-1-123456"` |
| `DV` | string | ❌ | Dígito verificador | Alfanumérico, máx. 40 | `"56"` |
| `VendorType` | string | ✅ | Tipo de proveedor | Requerido, alfanumérico, máx. 40 | `"Regular"` |
| `TaxpayerType` | string | ✅ | Tipo de contribuyente | Requerido, alfanumérico, máx. 40 | `"Contributor"` |
| `Custom_field5` | string | ✅ | Campo personalizado 5 | Requerido, alfanumérico, máx. 40 | `"Activo"` |

### Reglas de Validación Detalladas

#### VendorID
- **Requerido**: Sí
- **Tipo**: String
- **Máximo**: 20 caracteres
- **Único**: Debe ser único en la base de datos
- **Ejemplo válido**: `"VENDOR001"`, `"PROV-2025-001"`

#### VendrName
- **Requerido**: Sí
- **Tipo**: String
- **Máximo**: 39 caracteres
- **Ejemplo válido**: `"Distribuidora Nacional S.A."`

#### Email
- **Requerido**: No
- **Tipo**: String con formato email
- **Máximo**: 64 caracteres
- **Único**: Debe ser único en la tabla VendorsImp
- **Ejemplo válido**: `"compras@distribuidora.com"`

#### RUC y DV
- **Patrón**: `/^[a-zA-Z0-9_-]*$/`
- **Descripción**: Solo letras, números, guiones y guiones bajos
- **Ejemplo válido RUC**: `"87654321-1-123456"`
- **Ejemplo válido DV**: `"56"`

### Ejemplo de Request Completo

```json
```json
{
  "VendorID": "VEN12345",
  "VendrName": "Vendor Example",
  "PhoneNumber": "1234567890",
  "Email": "vendor@example.com",
  "AddressLine1": "123 Main St",
  "AddressLine2": "Suite 200",
  "City": "Sample City",
  "State": "CA",
  "Zip": "12345",
  "Country": "US",
  "RUC": "RUC12345678",
  "DV": "DV1234",
  "VendorType": "Type123",
  "TaxpayerType": "Tax123",
  "Custom_field5": "CustomValue5"
}
```

### Respuesta de Éxito

```json
{
  "VendorID": "VEN12345",
  "VendrName": "Vendor Example",
  "Telephone1": "1234567890",
  "Email": "vendor@example.com",
  "AddressLine1": "123 Main St",
  "AddressLine2": "Suite 200",
  "City": "Sample City",
  "State": "CA",
  "Zip": "12345",
  "Country": "US",
  "Custom_field1": "RUC12345678",
  "Custom_field2": "DV1234",
  "Custom_field3": "Type123",
  "Custom_field4": "Tax123"
}
```

### Ejemplo de Request Mínimo

```json
{
  "VendorID": "VENDOR002",
  "VendrName": "Proveedor Básico",
  "City": "Panamá",
  "State": "PA",
  "Country": "Panamá",
  "VendorType": "Basic",
  "TaxpayerType": "Regular",
  "Custom_field5": "Nuevo"
}
```

### Respuesta de Éxito

```json
{
  "ID": 1,
  "ID_compania": 1,
  "VendorID": "VENDOR001",
  "VendrName": "Distribuidora Nacional S.A.",
  "Contact": null,
  "Telephone1": "+507 6789-1234",
  "Email": "compras@distribuidora.com",
  "AddressLine1": "Calle 50, Edificio Global Bank",
  "AddressLine2": "Piso 12, Oficina 1201",
  "City": "Ciudad de Panamá",
  "State": "PA",
  "Zip": "0833-01234",
  "Country": "Panamá",
  "IsActive": null,
  "Custom_field1": "87654321-1-123456",
  "Custom_field2": "56",
  "Custom_field3": "Regular",
  "Custom_field4": "Contributor",
  "Custom_field5": "Activo",
  "Export_date": null,
  "Enviado": 0,
  "Error": 0,
  "ErrorPT": null,
  "ExpensesAccount": null,
  "created_at": "2025-01-29T21:30:00.000000Z",
  "updated_at": "2025-01-29T21:30:00.000000Z"
}
```

### Respuestas de Error

#### Error de Validación
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "VendorID": [
      "The VendorID has already been taken."
    ],
    "VendrName": [
      "The VendrName field is required."
    ],
    "Email": [
      "The Email has already been taken.",
      "The Email must be a valid email address."
    ]
  }
}
```

#### Error de Duplicado
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "VendorID": [
      "The VendorID has already been taken."
    ]
  }
}
```

---

## Obtener Proveedores Importados

**Endpoint:** `GET /api/acicloud/vendors_imp`  
**Controller:** `ACIcloudController::getVendorsImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Obtiene la lista de proveedores que han sido importados/creados a través de la API.

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "vendor_id": "VENDOR001",
      "name": "Distribuidora Nacional S.A.",
      "sage_id": "SAGE_VEN_001",
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "updated_at": "2025-01-29 21:30:00",
      "errors": null
    },
    {
      "id": 2,
      "vendor_id": "VENDOR002",
      "name": "Proveedora del Este",
      "sage_id": "SAGE_VEN_002",
      "import_status": "pending",
      "created_at": "2025-01-29 21:25:00",
      "updated_at": "2025-01-29 21:25:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 45,
    "total_pages": 2
  }
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Proveedor creado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 409 | Proveedor duplicado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

#### Obtener Lista de Proveedores
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/vendors?page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Crear Proveedor
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/vendor" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "vendor_id": "VENDOR001",
    "name": "Distribuidora Nacional S.A.",
    "email": "compras@distribuidora.com",
    "credit_limit": 50000.00
  }'
```

#### Obtener Proveedores Importados
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/vendors_imp" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Notas Técnicas

- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Sincronización**: Los proveedores se sincronizan automáticamente con Sage
- **Validación RUC**: Implementa validación específica para RUC panameño
- **Monedas**: Soporte para USD y PAB
- **Estados**: Los proveedores pueden estar activos o inactivos
- **Límites de Crédito**: Control de límites de crédito por proveedor

### Integración con Sage ACICloud

Los proveedores creados a través de la API se sincronizan automáticamente con Sage ACICloud, creando registros correspondientes en el sistema ERP.
