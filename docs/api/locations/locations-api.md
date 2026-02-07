# API de Ubicaciones Geográficas

## Descripción

API para consultar información geográfica de Panamá, incluyendo provincias, distritos y corregimientos. No requiere autenticación.

## Base URL

```
/api/v1/locations
```

---

## Endpoints

### 1. Obtener Todas las Ubicaciones

**GET** `/api/v1/locations`

Obtiene todas las ubicaciones disponibles organizadas por provincia con formato específico.

#### Headers
```http
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "8": [
    [
      "PANAMA - SAN FELIPE - 8-8-1",
      "PANAMA - EL CHORRILLO - 8-8-2",
      "PANAMA - SANTA ANA - 8-8-3"
    ],
    [
      "SAN MIGUELITO - AMELIA DENIS DE ICAZA - 8-8-4",
      "SAN MIGUELITO - BELISARIO FRIAS - 8-8-5"
    ]
  ],
  "1": [
    [
      "BOCAS DEL TORO - BOCAS DEL TORO - 1-1-1",
      "BOCAS DEL TORO - BASTIMENTOS - 1-1-2"
    ]
  ]
}
```

### 2. Obtener Solo Provincias

**GET** `/api/v1/locations/provincies`

Obtiene la lista de todas las provincias de Panamá.

#### Headers
```http
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
[
  {
    "id": 13,
    "nombre": "PANAMA OESTE",
    "codigo": "13"
  },
  {
    "id": 12,
    "nombre": "COMARCA NGÄBERE-BUGLÉ",
    "codigo": "12"
  },
  {
    "id": 11,
    "nombre": "COMARCA GUNA YALA",
    "codigo": "11"
  },
  {
    "id": 10,
    "nombre": "COMARCA EMBERÁ-WOUNAAN",
    "codigo": "10"
  },
  {
    "id": 9,
    "nombre": "VERAGUAS",
    "codigo": "9"
  },
  {
    "id": 8,
    "nombre": "PANAMA",
    "codigo": "8"
  },
  {
    "id": 7,
    "nombre": "LOS SANTOS",
    "codigo": "7"
  },
  {
    "id": 6,
    "nombre": "HERRERA",
    "codigo": "6"
  },
  {
    "id": 5,
    "nombre": "DARIÉN",
    "codigo": "5"
  },
  {
    "id": 4,
    "nombre": "CHIRIQUÍ",
    "codigo": "4"
  },
  {
    "id": 3,
    "nombre": "COLÓN",
    "codigo": "3"
  },
  {
    "id": 2,
    "nombre": "COCLÉ",
    "codigo": "2"
  },
  {
    "id": 1,
    "nombre": "BOCAS DEL TORO",
    "codigo": "1"
  }
]
```

### 3. Obtener Detalles de Provincia (Básicos)

**GET** `/api/v1/locations/provincies/{codec}`

Obtiene los detalles básicos de una provincia específica.

#### Parámetros de Ruta
- `codec` (integer, requerido): Código de la provincia (1-13)

#### Headers
```http
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "nombre": "PANAMA",
  "codigo": "8"
}
```

### 4. Obtener Detalles de Provincia (Completos)

**GET** `/api/v1/locations/provincies/{codec}?full_details=1`

Obtiene los detalles completos de una provincia incluyendo distritos y corregimientos.

#### Parámetros de Ruta
- `codec` (integer, requerido): Código de la provincia (1-13)

#### Parámetros de Query
- `full_details` (string, opcional): Valores aceptados: `0`, `1`, `true`, `false`

#### Headers
```http
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "nombre": "PANAMA",
  "codigo": "8",
  "districts": [
    {
      "id": 1,
      "nombre": "PANAMA",
      "codigo": "8",
      "corregimientos": [
        {
          "id": 1,
          "nombre": "SAN FELIPE",
          "codigo": "1",
          "codigo_ubicacion": "8-8-1"
        },
        {
          "id": 2,
          "nombre": "EL CHORRILLO",
          "codigo": "2", 
          "codigo_ubicacion": "8-8-2"
        }
      ]
    },
    {
      "id": 2,
      "nombre": "SAN MIGUELITO",
      "codigo": "9",
      "corregimientos": [
        {
          "id": 3,
          "nombre": "AMELIA DENIS DE ICAZA",
          "codigo": "1",
          "codigo_ubicacion": "8-9-1"
        }
      ]
    }
  ]
}
```

#### Respuesta de Error (404)

```json
{
  "message": "No query results for model [App\\Models\\Province] 99"
}
```

### 5. Obtener Detalles de Ubicación Específica

**GET** `/api/v1/locations/{locationCode}`

Obtiene los detalles completos de una ubicación específica usando su código de ubicación.

#### Parámetros de Ruta
- `locationCode` (string, requerido): Código completo de la ubicación (ej: "8-8-1")

#### Headers
```http
Accept: application/json
```

#### Respuesta Exitosa (200)

```json
{
  "provincia": {
    "id": 8,
    "nombre": "PANAMA",
    "codigo": "8"
  },
  "distrito": {
    "id": 1,
    "nombre": "PANAMA", 
    "codigo": "8"
  },
  "corregimiento": {
    "id": 1,
    "nombre": "SAN FELIPE",
    "codigo": "1",
    "codigo_ubicacion": "8-8-1"
  }
}
```

#### Respuesta de Error (404)

```json
{
  "message": "No query results for model [App\\Models\\Corregimiento] 8-8-99"
}
```

#### Respuesta de Error (400) - Formato Inválido

```json
{
  "message": "Invalid location code format."
}
```

---

## Estructura de Datos

### Provincia

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único de la provincia |
| `nombre` | string | Nombre de la provincia (mayúsculas) |
| `codigo` | string | Código de la provincia (1-13) |

### Distrito

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único del distrito |
| `nombre` | string | Nombre del distrito (mayúsculas) |
| `codigo` | string | Código del distrito |

### Corregimiento

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único del corregimiento |
| `nombre` | string | Nombre del corregimiento (mayúsculas) |
| `codigo` | string | Código del corregimiento |
| `codigo_ubicacion` | string | Código completo de ubicación (provincia-distrito-corregimiento) |

---

## Validaciones

### LocationRequest

| Campo | Tipo | Validación |
|-------|------|------------|
| `full_details` | string | Opcional, valores: `0`, `1`, `true`, `false` |

---

## Casos de Uso

### 1. Listar Todas las Ubicaciones (Para Dropdown)
```bash
curl -X GET "http://localhost:8000/api/v1/locations" \
  -H "Accept: application/json"
```

### 2. Obtener Solo Provincias
```bash
curl -X GET "http://localhost:8000/api/v1/locations/provincies" \
  -H "Accept: application/json"
```

### 3. Consultar Provincia (Básico)
```bash
curl -X GET "http://localhost:8000/api/v1/locations/provincies/8" \
  -H "Accept: application/json"
```

### 4. Consultar Provincia (Completo)
```bash
curl -X GET "http://localhost:8000/api/v1/locations/provincies/8?full_details=1" \
  -H "Accept: application/json"
```

### 5. Obtener Ubicación por Código
```bash
curl -X GET "http://localhost:8000/api/v1/locations/8-8-1" \
  -H "Accept: application/json"
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 400 | Formato de código inválido |
| 404 | Ubicación no encontrada |
| 422 | Error de validación |
| 500 | Error interno del servidor |

## Notas Adicionales

- **Sin autenticación**: Todos los endpoints son públicos
- **Nombres en mayúsculas**: Todos los nombres vienen en mayúsculas desde la base de datos
- **Orden descendente**: Las provincias se devuelven ordenadas por ID descendente
- **Formato de código**: Los códigos de ubicación siguen el formato provincia-distrito-corregimiento
- **Validación de formato**: El código de ubicación debe tener exactamente 3 partes separadas por guiones
- **Datos de prueba**: El test confirma que "8-8-1" corresponde a "PANAMA - PANAMA - SAN FELIPE"
