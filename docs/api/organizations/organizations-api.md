# API de Organizaciones

## Descripción

API para gestionar organizaciones a las que tiene acceso el usuario autenticado. Incluye consulta de organizaciones asignadas, información detallada por RUC y registro de facturación electrónica.

## Base URL

```
/api/v1/organizations
```

**Autenticación requerida**: Bearer Token (Sanctum)

---

## Endpoints

### 1. Obtener Organizaciones del Usuario

**GET** `/api/v1/organizations`

Obtiene todas las organizaciones asignadas al usuario autenticado, incluyendo información del token actual.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "data": [
    {
      "id": 1,
      "name": "Empresa Demo S.A.",
      "ruc": "1234567-8-123456"
    },
    {
      "id": 2,
      "name": "Comercial XYZ",
      "ruc": "9876543-1-654321"
    }
  ],
  "token_organization": {
    "organization_id": 1,
    "point_sale": "001-001",
    "name": "Token Principal"
  }
}
```

### 2. Obtener Información de Organización por RUC

**GET** `/api/v1/organizations/{ruc}`

Obtiene información detallada de una organización específica usando su RUC.

#### Parámetros de Ruta
- `ruc` (string, requerido): RUC de la organización (ej: "1234567-8-123456")

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "id": 1,
  "codigo_provincia_distrito": "8-1",
  "coordenadas_geograficas": "8.9824,-79.5199",
  "provincia_id": 8,
  "distrito_id": 1,
  "corregimiento_id": 1,
  "province_name": "PANAMA",
  "district_name": "PANAMA",
  "corregimiento_name": "SAN FELIPE",
  "nombre": "Empresa Demo S.A.",
  "ruc": "1234567-8-123456",
  "dv": "8",
  "tipo_contribuyente_id": 1,
  "telefono": "507-1234-5678",
  "direccion_sucursal": "Calle 50, Edificio Torre del Sol",
  "email_empresa": "contacto@empresademo.com",
  "id_empresa": "EMP001",
  "province": {
    "id": 8,
    "nombre": "PANAMA",
    "codigo": "8"
  },
  "district": {
    "id": 1,
    "nombre": "PANAMA",
    "codigo": "8"
  },
  "corregimiento": {
    "id": 1,
    "nombre": "SAN FELIPE",
    "codigo": "1"
  },
  "contributor_type": {
    "id": 1,
    "nombre": "Natural"
  },
  "company": {
    "id": 1,
    "nombre": "Matriz Principal"
  },
  "type_contribution_name": "Natural",
  "token_organization": {
    "organization_id": 1,
    "point_sale": "001-001",
    "name": "Token Principal"
  }
}
```

#### Respuesta de Error (404)

```json
{
  "token_organization": {
    "organization_id": 1,
    "point_sale": "001-001",
    "name": "Token Principal"
  }
}
```

### 3. Registrar Emisión de Facturación Electrónica

**POST** `/api/v1/organizations/emission_fe`

Registra una factura para verificación de emisión desde ACI-CLOUD.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Cuerpo de la Petición

```json
{
  "aci_organizacion_id": "ORG123456",
  "zoho_invoice_id": "INV789012",
  "date": "2025-01-15",
  "status": "pending",
  "type": "invoice",
  "message": "Factura pendiente de procesamiento"
}
```

#### Respuesta Exitosa (200)

```json
{
  "id": 1,
  "aci_organizacion_id": "ORG123456",
  "zoho_invoice_id": "INV789012",
  "date": "2025-01-15T00:00:00.000000Z",
  "status": "pending",
  "type": "invoice",
  "message": "Factura pendiente de procesamiento",
  "created_at": "2025-01-15T10:30:00.000000Z",
  "updated_at": "2025-01-15T10:30:00.000000Z"
}
```

---

## Estructura de Datos

### Organización (Lista)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único de la organización |
| `name` | string | Nombre comercial de la organización (pretty_name) |
| `ruc` | string | RUC de la organización |

### Organización (Detallada)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único de la organización |
| `codigo_provincia_distrito` | string | Código de provincia-distrito |
| `coordenadas_geograficas` | string | Coordenadas GPS (latitud,longitud) |
| `provincia_id` | integer | ID de la provincia |
| `distrito_id` | integer | ID del distrito |
| `corregimiento_id` | integer | ID del corregimiento |
| `province_name` | string | Nombre de la provincia |
| `district_name` | string | Nombre del distrito |
| `corregimiento_name` | string | Nombre del corregimiento |
| `nombre` | string | Razón social completa |
| `ruc` | string | RUC completo |
| `dv` | string | Dígito verificador |
| `tipo_contribuyente_id` | integer | ID del tipo de contribuyente |
| `telefono` | string | Teléfono de contacto |
| `direccion_sucursal` | string | Dirección física |
| `email_empresa` | string | Email corporativo |
| `id_empresa` | string | Identificador interno |

### Token Organization

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `organization_id` | integer | ID de la organización del token |
| `point_sale` | string | Punto de venta asignado |
| `name` | string | Nombre del token |

### Emisión FE

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `aci_organizacion_id` | string | ID de organización en ACI |
| `zoho_invoice_id` | string | ID de factura en Zoho |
| `date` | date | Fecha de la emisión |
| `status` | string | Estado de la factura |
| `type` | string | Tipo de documento |
| `message` | string | Mensaje descriptivo (opcional) |

---

## Validaciones

### EmissionFERequest

| Campo | Tipo | Validación |
|-------|------|------------|
| `aci_organizacion_id` | string | Requerido, máximo 20 caracteres |
| `zoho_invoice_id` | string | Requerido, máximo 20 caracteres |
| `date` | date | Requerido, formato fecha válido |
| `status` | string | Requerido, máximo 15 caracteres |
| `type` | string | Requerido, máximo 15 caracteres |
| `message` | string | Opcional |

---

## Casos de Uso

### 1. Listar Organizaciones del Usuario
```bash
curl -X GET "http://localhost:8000/api/v1/organizations" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 2. Consultar Organización por RUC
```bash
curl -X GET "http://localhost:8000/api/v1/organizations/1234567-8-123456" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 3. Registrar Emisión FE
```bash
curl -X POST "http://localhost:8000/api/v1/organizations/emission_fe" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "aci_organizacion_id": "ORG123456",
    "zoho_invoice_id": "INV789012",
    "date": "2025-01-15",
    "status": "pending",
    "type": "invoice",
    "message": "Factura pendiente"
  }'
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 401 | No autenticado |
| 403 | Sin permisos |
| 404 | Organización no encontrada |
| 422 | Error de validación |
| 500 | Error interno del servidor |

## Notas Adicionales

- **Autenticación obligatoria**: Todos los endpoints requieren Bearer Token
- **Multi-tenant**: Cada usuario solo ve sus organizaciones asignadas
- **Token Organization**: Siempre se incluye información del token actual
- **RUC único**: Cada organización tiene un RUC único en formato panameño
- **Ubicación geográfica**: Incluye relaciones con provincia, distrito y corregimiento
- **UpdateOrCreate**: El endpoint emission_fe actualiza o crea registros basado en aci_organizacion_id y zoho_invoice_id
- **Transformer específico**: La respuesta detallada usa OrganizationTransform que incluye relaciones anidadas
- **Lista simplificada**: La lista usa OrganizationArrTransform que solo devuelve campos básicos
- **Middleware**: Incluye check.activate.organization para validación adicional
    }
  ],
  "token_organization": {
    "organization_id": 1,
    "point_sale": "001",
    "name": "Token Principal"
  }
}
```

### 2. Obtener Información de Organización por RUC

**GET** `/api/v1/organizations/{ruc}`

Obtiene los detalles de una organización específica por su RUC.

#### Parámetros de Ruta
- `ruc` (string, requerido): RUC de la organización

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "id": 1,
  "codigo_provincia_distrito": "8-8",
  "coordenadas_geograficas": "8.9936,-79.5197",
  "provincia_id": 8,
  "distrito_id": 1,
  "corregimiento_id": 1,
  "province_name": "Panamá",
  "district_name": "Panamá",
  "corregimiento_name": "Ancón",
  "nombre": "Empresa Ejemplo S.A.",
  "ruc": "155757563-2-2024",
  "dv": "75",
  "tipo_contribuyente_id": 1,
  "telefono": "+507 6000-0000",
  "direccion_sucursal": "Calle 50, Edificio Torre Global, Piso 15",
  "email_empresa": "contacto@empresa.com",
  "id_empresa": "EMP001",
  "province": {
    "id": 8,
    "name": "Panamá",
    "code": "8"
  },
  "district": {
    "id": 1,
    "name": "Panamá",
    "code": "8-1"
  },
  "corregimiento": {
    "id": 1,
    "name": "Ancón",
    "code": "8-1-1"
  },
  "contributor_type": {
    "id": 1,
    "name": "Contribuyente Ordinario"
  },
  "company": {
    "id": 1,
    "name": "Empresa Principal",
    "status": "active"
  },
  "type_contribution_name": "Persona Jurídica",
  "token_organization": {
    "organization_id": 1,
    "point_sale": "001",
    "name": "Token Principal"
  }
}
```

#### Respuesta de Error (404)

```json
{
  "error": "Organization not found",
  "message": "No se encontró organización con el RUC proporcionado"
}
```

### 3. Registrar Emisión de Facturación Electrónica

**POST** `/api/v1/organizations/emission_fe`

Registra una factura para verificación de emisión desde ACI-CLOUD.

#### Headers
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

#### Cuerpo de la Solicitud

```json
{
  "aci_organizacion_id": "ORG123456",
  "zoho_invoice_id": "INV789012",
  "date": "2024-01-20",
  "status": "pending",
  "type": "invoice",
  "message": "Factura registrada para emisión"
}
```

#### Validaciones

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|------------|
| `aci_organizacion_id` | string | ✅ | Máximo 20 caracteres |
| `zoho_invoice_id` | string | ✅ | Máximo 20 caracteres |
| `date` | string | ✅ | Fecha válida (YYYY-MM-DD) |
| `status` | string | ✅ | Máximo 15 caracteres |
| `type` | string | ✅ | Máximo 15 caracteres |
| `message` | string | ❌ | Mensaje opcional |

#### Respuesta Exitosa (200)

```json
{
  "id": 15,
  "aci_organizacion_id": "ORG123456",
  "zoho_invoice_id": "INV789012",
  "date": "2024-01-20T00:00:00.000000Z",
  "status": "pending",
  "type": "invoice",
  "message": "Factura registrada para emisión",
  "created_at": "2024-01-20T10:30:00.000000Z",
  "updated_at": "2024-01-20T10:30:00.000000Z"
}
```

#### Respuesta de Error (422)

```json
{
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "aci_organizacion_id": [
      "El campo aci organizacion id es obligatorio."
    ],
    "date": [
      "El campo date no corresponde con una fecha válida."
    ]
  }
}
```

### 4. Obtener Información del Usuario Autenticado

**GET** `/api/user`

Obtiene la información completa del usuario autenticado, incluyendo sus organizaciones asociadas.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "id": 1,
  "name": "Juan Pérez",
  "email": "juan.perez@empresa.com",
  "organization_id": 1,
  "organization": {
    "id": 1,
    "name": "Empresa Principal",
    "ruc": "155757563-2-2024"
  },
  "organizations": [
    {
      "id": 1,
      "name": "Empresa Principal"
    },
    {
      "id": 2,
      "name": "Sucursal Norte"
    }
  ],
  "import_configuration": {
    "auto_import": true,
    "default_format": "xml"
  },
  "token_organization": {
    "organization_id": 1,
    "point_sale": "001",
    "name": "Token Activo"
  }
}
```

---

## Estructura de Datos

### Organización Completa

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único de la organización |
| `codigo_provincia_distrito` | string | Código geográfico (ej: "8-8") |
| `coordenadas_geograficas` | string | Coordenadas GPS |
| `provincia_id` | integer | ID de la provincia |
| `distrito_id` | integer | ID del distrito |
| `corregimiento_id` | integer | ID del corregimiento |
| `province_name` | string | Nombre de la provincia |
| `district_name` | string | Nombre del distrito |
| `corregimiento_name` | string | Nombre del corregimiento |
| `nombre` | string | Nombre de la organización |
| `ruc` | string | RUC de la organización |
| `dv` | string | Dígito verificador |
| `tipo_contribuyente_id` | integer | ID del tipo de contribuyente |
| `telefono` | string | Teléfono de contacto |
| `direccion_sucursal` | string | Dirección de la sucursal |
| `email_empresa` | string | Email de la empresa |
| `id_empresa` | string | ID interno de la empresa |

### Token Organization

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `organization_id` | integer | ID de la organización del token |
| `point_sale` | string | Punto de venta (ej: "001") |
| `name` | string | Nombre del token |

---

## Casos de Uso

### 1. Obtener Organizaciones del Usuario
```bash
curl -X GET "http://localhost:8000/api/v1/organizations" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 2. Consultar Organización por RUC
```bash
curl -X GET "http://localhost:8000/api/v1/organizations/155757563-2-2024" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 3. Registrar Emisión FE
```bash
curl -X POST "http://localhost:8000/api/v1/organizations/emission_fe" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "aci_organizacion_id": "ORG123456",
    "zoho_invoice_id": "INV789012",
    "date": "2024-01-20",
    "status": "pending",
    "type": "invoice"
  }'
```

### 4. Obtener Información del Usuario
```bash
curl -X GET "http://localhost:8000/api/user" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 401 | No autorizado |
| 404 | Recurso no encontrado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

## Notas Adicionales

- Las organizaciones se obtienen según las asignadas al usuario autenticado
- El RUC debe seguir el formato panameño válido
- La información token_organization incluye datos del token actual
- Las emisiones FE se registran para tracking desde ACI-CLOUD
- Todas las fechas se manejan en formato ISO 8601
- El endpoint `/api/user` no requiere middleware de organización activa
