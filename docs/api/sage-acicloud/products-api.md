# API Productos - Sage ACICloud

## Obtener Lista de Productos

**Endpoint:** `GET /api/acicloud/products`  
**Controller:** `ACIcloudController::products`  
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
| `sort` | string | | Campo para ordenar | `"code"` |
| `order` | string | | Dirección del ordenamiento | `"asc"` |
| `filter` | object | | Filtros aplicados | `{}` |

### Ejemplo de Request con Filtros

```bash
GET /api/acicloud/products?filter[description][operator]=contains&filter[description][value]=Laptop&filter[active][operator]=equals&filter[active][value]=1
```

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "ProductID": "PROD001",
      "Description": "Producto de prueba estándar",
      "QtyOnHand": "50.00000",
      "Price1": "49.9900",
      "Price2": "45.9900",
      "ItemType": "physical",
      "Location": "Almacén Pr",
      "Weight": "1.5000",
      "SalesDescription": "Este es un producto de prueba para ventas",
      "PartNumber": "PN-10001",
      "Price3": "40.9900",
      "Price4": "38.5000",
      "Price5": "35.7500",
      "Price6": "33.9900",
      "Price7": "30.9900",
      "Price8": "28.7500",
      "Price9": "25.5000",
      "Price10": "22.9900",
      "TaxType": 10,
      "UnitMeasure": "Unidad",
      "IsActive": 1,
      "ItemClass": 0,
      "id_compania": 2,
      "status": "Disponible",
      "link_foto": "https://example.com/images/producto001.jpg",
      "id_location": "5",
      "Custom_field1": "Dato personalizado 1",
      "Custom_field2": "Dato personalizado 2",
      "Custom_field3": "Dato personalizado 3",
      "Custom_field4": "Dato personalizado 4",
      "Custom_field5": "Dato personalizado 5",
      "UPC_SKU": "SKU-001-ABC",
      "GL_Sales_Acct": "ACC-202401",
      "LAST_CHANGE": "2024-01-01 10:00:00",
      "LastUnitCost": "19.9900"
    },
  "first_page_url": "https://api.docucenter.com/api/acicloud/products?page=1",
  "from": 1,
  "last_page": 20,
  "last_page_url": "https://api.docucenter.com/api/acicloud/products?page=20",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/products?page=1",
      "label": "1",
      "active": true
    }
  ],
  "next_page_url": "https://api.docucenter.com/api/acicloud/products?page=2",
  "path": "https://api.docucenter.com/api/acicloud/products",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 500
}
```

---

## Crear Producto

**Endpoint:** `POST /api/acicloud/product`  
**Request Class:** `ProductImpRequest`  
**Controller:** `ACIcloudController::productsImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `code` | string | | Código único del producto | `"PROD001"` |
| `description` | string | | Descripción del producto | `"Laptop Dell Inspiron 15"` |
| `type` | string | | Tipo de producto | `"inventory"` |
| `category_code` | string | | Código de categoría | `"ELEC"` |
| `unit_of_measure` | string | | Unidad de medida | `"EA"` |
| `cost_price` | decimal | | Precio de costo | `850.00` |
| `sales_price` | decimal | | Precio de venta | `1200.00` |
| `tax_info` | object | | Información de impuestos | `{}` |
| `tax_info.tax_code` | string | | Código de impuesto | `"STD"` |
| `tax_info.tax_rate` | decimal | | Tasa de impuesto | `0.07` |
| `tax_info.taxable` | boolean | | Es gravable | `true` |
| `inventory` | object | | Información de inventario | `{}` |
| `inventory.current_stock` | decimal | | Stock actual | `25.00` |
| `inventory.minimum_stock` | decimal | | Stock mínimo | `5.00` |
| `inventory.maximum_stock` | decimal | | Stock máximo | `100.00` |
| `accounting` | object | | Cuentas contables | `{}` |
| `accounting.income_account` | string | | Cuenta de ingresos | `"4100"` |
| `accounting.expense_account` | string | | Cuenta de gastos | `"5100"` |
| `accounting.inventory_account` | string | | Cuenta de inventario | `"1300"` |
| `supplier` | object | | Información del proveedor | `{}` |
| `supplier.vendor_id` | string | | ID del proveedor principal | `"VENDOR001"` |
| `supplier.supplier_code` | string | | Código del proveedor | `"DELL-INSP-15"` |
| `dimensions` | object | | Dimensiones del producto | `{}` |
| `dimensions.length` | decimal | | Largo | `35.5` |
| `dimensions.width` | decimal | | Ancho | `24.0` |
| `dimensions.height` | decimal | | Alto | `2.5` |
| `dimensions.weight` | decimal | | Peso | `2.1` |
| `active` | boolean | | Estado activo (default: true) | `true` |

### Tipos de Producto Válidos

- `inventory` - Producto de inventario
- `service` - Servicio
- `non_inventory` - No inventariable
- `assembly` - Ensamblaje

### Validaciones

- **code**: Único, máximo 50 caracteres
- **description**: Requerido, máximo 255 caracteres
- **type**: Debe ser uno de los tipos válidos
- **unit_of_measure**: Máximo 10 caracteres
- **sales_price**: Valor positivo
- **cost_price**: Valor positivo si se proporciona
- **tax_rate**: Entre 0.00 y 1.00

### Ejemplo de Request

```json
```json
{
  "ProductID": "P1234567890",
  "Description": "Test Product Description",
  "Price1": 100.00,
  "Price2": 90.00,
  "Price3": 95.00,
  "TaxType": 1,
  "Custom_field1": "Custom value 1",
  "UnitMeasure": "Piece",
  "GL_Sales_Acct": "123456789"
}
```

### Respuesta de Éxito

```json
{
  "ProductID": "P1234567890",
  "Description": "Test Product Description",
  "Price1": 100.00,
  "Price2": 90.00,
  "Price3": 95.00,
  "TaxType": 1,
  "Custom_field1": "Custom value 1",
  "UnitMeasure": "Piece",
  "GL_Sales_Acct": "123456789"
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Producto creado exitosamente",
  "data": {
    "code": "PROD001",
    "description": "Laptop Dell Inspiron 15",
    "sage_id": "SAGE_PROD_001",
    "created_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Obtener Productos Importados

**Endpoint:** `GET /api/acicloud/products_imp`  
**Controller:** `ACIcloudController::getProductsImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Obtiene la lista de productos que han sido importados/creados a través de la API.

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "PROD001",
      "description": "Laptop Dell Inspiron 15",
      "sage_id": "SAGE_PROD_001",
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "updated_at": "2025-01-29 21:30:00",
      "errors": null
    },
    {
      "id": 2,
      "code": "PROD002",
      "description": "Mouse Inalámbrico Logitech",
      "sage_id": "SAGE_PROD_002",
      "import_status": "pending",
      "created_at": "2025-01-29 21:25:00",
      "updated_at": "2025-01-29 21:25:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 150,
    "total_pages": 6
  }
}
```

---

## Consultar Producto Específico

**Endpoint:** `GET /api/acicloud/product_imp/{identifier}`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

Obtiene los detalles de un producto específico por su identificador.

### Parámetros de URL

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `identifier` | string | ProductID o ID interno del producto |

### Ejemplo de Request

```bash
GET /api/acicloud/product_imp/PROD001
```

### Respuesta de Éxito

```json
{
  "success": true,
  "data": {
    "id": 1,
    "ProductID": "PROD001",
    "Description": "Laptop Dell Inspiron 15",
    "QtyOnHand": "50.00000",
    "Price1": "899.99",
    "Price2": "850.00",
    "Price3": "800.00",
    "ItemType": "physical",
    "Location": "Almacén Principal",
    "Weight": "2.5000",
    "SalesDescription": "Laptop Dell Inspiron 15, 8GB RAM, 256GB SSD",
    "PartNumber": "DELL-INS15-001",
    "sage_id": "SAGE_PROD_001",
    "import_status": "completed",
    "created_at": "2025-01-29 21:30:00",
    "updated_at": "2025-01-29 21:30:00"
  }
}
```

### Respuesta de Error

```json
{
  "success": false,
  "message": "Producto no encontrado"
}
```

---

## Actualizar Producto

**Endpoint:** `PUT /api/acicloud/product_imp/{id}`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

Actualiza los datos de un producto existente.

### Parámetros de URL

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID interno del producto |

### Parámetros de Request

Todos los campos son opcionales. Solo se actualizarán los campos enviados.

| Campo | Tipo | Descripción | Validación |
|-------|------|-------------|------------|
| `ProductID` | string | Código único del producto | Máx. 20 caracteres |
| `Description` | string | Descripción del producto | Máx. 40 caracteres |
| `QtyOnHand` | numeric | Cantidad en inventario | Decimal (8,5) |
| `Price1` | numeric | Precio nivel 1 | Decimal (8,4) |
| `Price2` | numeric | Precio nivel 2 | Decimal (8,4) |
| `Price3` | numeric | Precio nivel 3 | Decimal (8,4) |
| `ItemType` | string | Tipo de ítem | physical/service/non-inventory |
| `Location` | string | Ubicación | Máx. 15 caracteres |
| `Weight` | numeric | Peso del producto | Decimal (8,4) |
| `SalesDescription` | string | Descripción de ventas | - |
| `PartNumber` | string | Número de parte | Máx. 30 caracteres |

### Ejemplo de Request

```json
{
  "Description": "Laptop Dell Inspiron 15 - Actualizada",
  "Price1": "949.99",
  "QtyOnHand": "75.00000",
  "SalesDescription": "Laptop Dell Inspiron 15, 16GB RAM, 512GB SSD"
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Producto actualizado exitosamente",
  "data": {
    "id": 1,
    "ProductID": "PROD001",
    "Description": "Laptop Dell Inspiron 15 - Actualizada",
    "Price1": "949.99",
    "QtyOnHand": "75.00000",
    "SalesDescription": "Laptop Dell Inspiron 15, 16GB RAM, 512GB SSD",
    "updated_at": "2025-01-16 10:00:00"
  }
}
```

### Respuesta de Error

```json
{
  "success": false,
  "message": "Error al actualizar producto",
  "errors": {
    "Price1": [
      "El precio debe ser un valor numérico válido"
    ]
  }
}
```

---

## Ajuste de Inventario

**Endpoint:** `POST /api/acicloud/inventory_adjust`  
**Controller:** `ACIcloudController::inventoryAdjust`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `adjustment_number` | string | | Número de ajuste | `"ADJ-2025-001"` |
| `date` | string | | Fecha del ajuste (YYYY-MM-DD) | `"2025-01-29"` |
| `reason` | string | | Razón del ajuste | `"Physical count adjustment"` |
| `reference` | string | | Referencia adicional | `"COUNT-2025-01"` |
| `items` | array | | Lista de ajustes por producto | `[]` |
| `items[].product_code` | string | | Código del producto | `"PROD001"` |
| `items[].current_quantity` | decimal | | Cantidad actual en sistema | `25.00` |
| `items[].physical_quantity` | decimal | | Cantidad física contada | `23.00` |
| `items[].adjustment_quantity` | decimal | | Cantidad de ajuste (+/-) | `-2.00` |
| `items[].unit_cost` | decimal | | Costo unitario | `850.00` |
| `items[].reason` | string | | Razón específica del item | `"Damaged units"` |

### Ejemplo de Request

```json
{
  "adjustment_number": "ADJ-2025-001",
  "date": "2025-01-29",
  "reason": "Physical inventory count adjustment",
  "reference": "COUNT-2025-01",
  "items": [
    {
      "product_code": "PROD001",
      "current_quantity": 25.00,
      "physical_quantity": 23.00,
      "adjustment_quantity": -2.00,
      "unit_cost": 850.00,
      "reason": "2 damaged units found"
    },
    {
      "product_code": "PROD002", 
      "current_quantity": 50.00,
      "physical_quantity": 52.00,
      "adjustment_quantity": 2.00,
      "unit_cost": 25.00,
      "reason": "Found additional units in storage"
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Ajuste de inventario procesado exitosamente",
  "data": {
    "adjustment_number": "ADJ-2025-001",
    "sage_id": "SAGE_ADJ_001",
    "items_adjusted": 2,
    "total_value_impact": -1650.00,
    "processed_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Obtener Ajustes de Inventario

**Endpoint:** `GET /api/acicloud/inventory_adjustment_imp`  
**Controller:** `ACIcloudController::getInventoryAdjustImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "adjustment_number": "ADJ-2025-001",
      "date": "2025-01-29",
      "sage_id": "SAGE_ADJ_001",
      "items_count": 2,
      "total_value_impact": -1650.00,
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 12,
    "total_pages": 1
  }
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Producto/Ajuste creado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 404 | Producto no encontrado |
| 409 | Código de producto duplicado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

#### Obtener Lista de Productos
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/products?page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Crear Producto
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/product" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "PROD001",
    "description": "Laptop Dell Inspiron 15",
    "type": "inventory",
    "unit_of_measure": "EA",
    "sales_price": 1200.00
  }'
```

#### Ajustar Inventario
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/inventory_adjust" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "adjustment_number": "ADJ-2025-001",
    "date": "2025-01-29",
    "reason": "Physical count adjustment",
    "items": [
      {
        "product_code": "PROD001",
        "current_quantity": 25.00,
        "physical_quantity": 23.00,
        "adjustment_quantity": -2.00
      }
    ]
  }'
```

---

## Notas Técnicas

- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Sincronización**: Productos y ajustes se sincronizan con Sage ACICloud
- **Validación**: Validación estricta de códigos únicos y tipos
- **Inventario**: Control de stock en tiempo real
- **Contabilidad**: Integración con cuentas contables
- **Categorización**: Soporte para categorías de productos

### Gestión de Inventario

- **Stock Tracking**: Seguimiento de stock actual, reservado y disponible
- **Niveles Mínimos/Máximos**: Control de reorden automático
- **Ajustes**: Sistema completo de ajustes de inventario
- **Costeo**: Manejo de costos promedio y FIFO
- **Dimensiones**: Información física para logística

### Integración con Sage ACICloud

Los productos creados se sincronizan automáticamente con Sage ACICloud, manteniendo consistencia entre sistemas.
