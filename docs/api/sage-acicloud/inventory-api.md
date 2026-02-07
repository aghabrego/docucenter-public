# API Inventario - Sage ACICloud

## Crear Ajuste de Inventario

**Endpoint:** `POST /api/acicloud/inventory_adjust`  
**Request Class:** `InventoryadjustImpRequest`  
**Controller:** `ACIcloudController::inventoryAdjust`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Validación | Ejemplo |
|-------|------|-----------|-------------|------------|---------|
| `ItemID` | string | ✅ | ID del producto | Máx. 20 characters | `"PROD001"` |
| `Reference` | string | ✅ | Referencia del ajuste | Máx. 20 characters | `"ADJ-2025-001"` |
| `ReasonToAdjust` | string | ❌ | Razón del ajuste | Máx. 35 characters | `"Inventory recount adjustment"` |
| `Account` | string | ❌ | Cuenta contable | Máx. 15 characters | `"1200"` |
| `UnitCost` | decimal | ❌ | Costo unitario | Decimal (4 decimales) | `850.0000` |
| `Quantity` | decimal | ✅ | Cantidad a ajustar | Decimal (4 decimales) | `5.0000` |
| `Date` | string | ✅ | Fecha del ajuste | YYYY-MM-DD | `"2025-01-29"` |
| `JobID` | string | ❌ | ID del trabajo | Máx. 50 characters | `"JOB001"` |
| `JobPhaseID` | string | ❌ | ID de fase del trabajo | Máx. 20 characters | `"PHASE01"` |
| `JobCostCodeID` | string | ❌ | ID código de costo | Máx. 20 characters | `"COST001"` |

### Reglas de Validación Detalladas

#### ItemID
- **Requerido**: Sí
- **Tipo**: String
- **Máximo**: 20 caracteres
- **Descripción**: Debe corresponder a un producto existente en el sistema
- **Ejemplo válido**: `"PROD001"`, `"LAPTOP-001"`

#### Reference  
- **Requerido**: Sí
- **Tipo**: String  
- **Máximo**: 20 caracteres
- **Descripción**: Número de referencia único para el ajuste
- **Ejemplo válido**: `"ADJ-2025-001"`, `"INV-ADJ-001"`

#### Quantity
- **Requerido**: Sí
- **Tipo**: Decimal con hasta 4 decimales
- **Descripción**: Cantidad a ajustar (positiva para incrementar, negativa para decrementar)
- **Ejemplo válido**: `5.0000` (agregar 5), `-3.0000` (quitar 3)

#### Date
- **Requerido**: Sí
- **Formato**: YYYY-MM-DD
- **Descripción**: Fecha cuando ocurre el ajuste
- **Ejemplo válido**: `"2025-01-29"`

### Tipos Comunes de Ajustes

#### Incremento de Inventario
```json
{
  "ItemID": "PROD001",
  "Reference": "ADJ-INC-001",
  "ReasonToAdjust": "Inventory found during recount",
  "Quantity": 5.0000,
  "Date": "2025-01-29",
  "UnitCost": 850.0000
}
```

#### Decremento de Inventario
```json
{
  "ItemID": "PROD001", 
  "Reference": "ADJ-DEC-001",
  "ReasonToAdjust": "Damaged goods write-off",
  "Quantity": -3.0000,
  "Date": "2025-01-29",
  "UnitCost": 850.0000
}
```

### Ejemplo de Request Completo

```json
{
  "ItemID": "LAPTOP001",
  "Reference": "ADJ-2025-001",
  "ReasonToAdjust": "Physical inventory adjustment",
  "Account": "1200",
  "UnitCost": 850.0000,
  "Quantity": 2.0000,
  "Date": "2025-01-29",
  "JobID": "JOB001",
  "JobPhaseID": "PHASE01",
  "JobCostCodeID": "COST001"
}
```

### Ejemplo de Request Mínimo

```json
{
  "ItemID": "PROD002",
  "Reference": "ADJ-MIN-001",
  "Quantity": -1.0000,
  "Date": "2025-01-29"
}
```

### Respuesta de Éxito

```json
{
  "ID": 1,
  "ID_compania": 1,
  "ItemID": "LAPTOP001",
  "Reference": "ADJ-2025-001",
  "ReasonToAdjust": "Physical inventory adjustment",
  "Account": "1200",
  "UnitCost": "850.0000",
  "Quantity": "2.0000",
  "Date": "2025-01-29",
  "JobID": "JOB001",
  "JobPhaseID": "PHASE01", 
  "JobCostCodeID": "COST001",
  "Export_date": null,
  "Enviado": 0,
  "Error": 0,
  "ErrorPT": null,
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
    "ItemID": [
      "The ItemID field is required."
    ],
    "Reference": [
      "The Reference field is required."
    ],
    "Quantity": [
      "The Quantity field is required."
    ],
    "Date": [
      "The Date does not match the format Y-m-d."
    ]
  }
}
```

#### Error de Producto No Encontrado
```json
{
  "message": "Product not found",
  "errors": {
    "ItemID": [
      "The specified product does not exist in the system."
    ]
  }
}
```

---

## Obtener Ajustes de Inventario Importados

**Endpoint:** `GET /api/acicloud/inventory_adjustment_imp`  
**Controller:** `ACIcloudController::getInventoryAdjustImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Obtiene la lista de ajustes de inventario que han sido importados/creados a través de la API.

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `item_id` | string | ❌ | Filtrar por producto | `"PROD001"` |
| `date_from` | string | ❌ | Fecha desde | `"2025-01-01"` |
| `date_to` | string | ❌ | Fecha hasta | `"2025-01-31"` |
| `page` | integer | ❌ | Página | `1` |
| `per_page` | integer | ❌ | Registros por página | `25` |

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "ID": 1,
      "ItemID": "LAPTOP001",
      "Reference": "ADJ-2025-001",
      "ReasonToAdjust": "Physical inventory adjustment",
      "Quantity": "2.0000",
      "Date": "2025-01-29",
      "import_status": "completed",
      "sage_sync_status": "synced",
      "created_at": "2025-01-29T21:30:00.000000Z",
      "errors": null
    },
    {
      "ID": 2,
      "ItemID": "PROD002",
      "Reference": "ADJ-2025-002", 
      "ReasonToAdjust": "Damaged goods write-off",
      "Quantity": "-1.0000",
      "Date": "2025-01-29",
      "import_status": "pending",
      "sage_sync_status": "pending",
      "created_at": "2025-01-29T21:25:00.000000Z",
      "errors": null
    }
  ],
  "first_page_url": "https://api.docucenter.com/api/acicloud/inventory_adjustment_imp?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "https://api.docucenter.com/api/acicloud/inventory_adjustment_imp?page=3",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/inventory_adjustment_imp?page=1",
      "label": "1", 
      "active": true
    }
  ],
  "next_page_url": "https://api.docucenter.com/api/acicloud/inventory_adjustment_imp?page=2",
  "path": "https://api.docucenter.com/api/acicloud/inventory_adjustment_imp",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 25
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Ajuste creado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

### Crear Ajuste de Inventario (Incremento)
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/inventory_adjust" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ItemID": "PROD001",
    "Reference": "ADJ-INC-001",
    "ReasonToAdjust": "Inventory found during recount",
    "Quantity": 5.0000,
    "Date": "2025-01-29",
    "UnitCost": 850.0000
  }'
```

### Crear Ajuste de Inventario (Decremento)
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/inventory_adjust" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ItemID": "PROD001",
    "Reference": "ADJ-DEC-001", 
    "ReasonToAdjust": "Damaged goods write-off",
    "Quantity": -3.0000,
    "Date": "2025-01-29"
  }'
```

### Obtener Ajustes Importados
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/inventory_adjustment_imp?page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Notas Técnicas

- **Cantidad Positiva**: Incrementa el inventario disponible
- **Cantidad Negativa**: Decrementa el inventario disponible  
- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Sincronización**: Los ajustes se sincronizan automáticamente con Sage ACICloud
- **Referencia Única**: Cada ajuste debe tener una referencia única para trazabilidad
- **Cuentas Contables**: Si se especifica Account, debe ser una cuenta válida en el plan contable

### Razones Comunes de Ajuste

- **Physical Count Variance**: Diferencias encontradas en conteo físico
- **Damaged Goods**: Productos dañados que se dan de baja
- **Theft/Loss**: Pérdidas por robo o extravío  
- **Expired Products**: Productos vencidos
- **Quality Issues**: Problemas de calidad
- **System Correction**: Correcciones del sistema

### Integración con Sage ACICloud

Los ajustes de inventario se procesan automáticamente en Sage ACICloud, actualizando los niveles de inventario y creando los asientos contables correspondientes según la configuración del sistema.
