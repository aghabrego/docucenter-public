# CustomerReceiptImpRequest - Validaciones API

## Resumen

Validaciones para la API de Customer Receipt que maneja recibos de pago de clientes según estructura de tabla `customer_receipt_header_imp` y `customer_receipt_detail_imp`.

## Estructura de Validación

### Campos Principales (Header)

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `Reference` | string | required, max:40 | Referencia del recibo |
| `CheckNumber` | string | required, max:20 | Número de cheque |
| `ReceiptNumber` | string | required, max:20 | Número de recibo |
| `ParentTransactionId` | integer | nullable, exists:SalesHeaderImp,ID | ID de transacción padre |
| `CustomerID` | string | required, max:20 | ID del cliente |
| `CustomerName` | string | required, max:39 | Nombre del cliente |
| `Total` | numeric | required, 0-99999999999999.99 | Total del recibo |
| `Method_of_Payment` | string | required, max:20 | Método de pago |
| `TaxID` | string | nullable, max:8 | ID fiscal |
| `SalesRepID` | string | required, max:20 | ID del representante |
| `Date` | date | nullable | Fecha del recibo |
| `CashAccountID` | string | **required**, max:15 | Cuenta de efectivo |
| `Prepayment` | boolean | nullable | Es prepago |
| `DepositTicketID` | string | nullable, max:8 | ID del ticket de depósito |

### Items (Detail)

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `Items` | array | required | Array de items del recibo |
| `Items.*.ApplyTo` | boolean | required | Si se aplica a factura |
| `Items.*.InvoiceNumber` | string | required_if:ApplyTo,true, max:20 | Número de factura (si ApplyTo=true) |
| `Items.*.Description` | string | required, max:160 | Descripción del item |
| `Items.*.Net_line` | numeric | required, 0-99999999999999.99 | Monto neto |
| `Items.*.Quantity` | numeric | required_if:ApplyTo,false, nullable | Cantidad (si ApplyTo=false) |
| `Items.*.Item_id` | string | nullable, max:20 | ID del producto |
| `Items.*.Unit_Price` | numeric | required_if:ApplyTo,false, nullable | Precio unitario (si ApplyTo=false) |
| `Items.*.Taxable` | integer | nullable | Es gravable |

## Lógica Condicional

La validación de campos en `Items` depende de dos factores principales:
1. **`Prepayment`** (a nivel de header)  
2. **`ApplyTo`** (a nivel de item)

###  PREPAYMENT = true (Pago por adelantado)
**Características especiales para prepagos:**
- `Quantity`: **OPCIONAL** - Puede ser null
- `Item_id`: **OPCIONAL** - Puede ser null  
- `Unit_Price`: **OPCIONAL** - Puede ser null
- `Net_line`: **PUEDE SER NEGATIVO** - Permite devoluciones/ajustes
- `Total`: **PUEDE SER NEGATIVO** - Permite devoluciones
- `InvoiceNumber`: Depende de `ApplyTo`

**Ejemplo válido - Prepago positivo:**
```json
{
    "Prepayment": true,
    "Items": [{
        "ApplyTo": false,
        "InvoiceNumber": null,
        "Description": "PAGO POR ADELANTADO",
        "Net_line": 11.0,
        "Quantity": null,
        "Item_id": null,
        "Unit_Price": null
    }]
}
```

**Ejemplo válido - Devolución prepago:**
```json
{
    "Prepayment": true,
    "Total": -15.50,
    "Items": [{
        "ApplyTo": false,
        "InvoiceNumber": null,
        "Description": "DEVOLUCIÓN DE PREPAGO",
        "Net_line": -15.50,
        "Quantity": null,
        "Item_id": null,
        "Unit_Price": null
    }]
}
```

###  PREPAYMENT = false + ApplyTo = true (Aplicar a factura existente)
- `InvoiceNumber`: **REQUERIDO** - Debe especificar factura
- `Quantity`: **OPCIONAL** - No necesario para aplicar a factura
- `Item_id`: **OPCIONAL** - No necesario para aplicar a factura
- `Unit_Price`: **OPCIONAL** - No necesario para aplicar a factura

**Ejemplo válido:**
```json
{
    "Prepayment": false,
    "Items": [{
        "ApplyTo": true,
        "InvoiceNumber": "FE001",
        "Description": "Aplicar a Factura FE001",
        "Net_line": 100.0,
        "Quantity": null,
        "Unit_Price": null
    }]
}
```

###  PREPAYMENT = false + ApplyTo = false (Producto/servicio nuevo)
- `InvoiceNumber`: **OPCIONAL** - No aplica a factura existente
- `Quantity`: **REQUERIDO** - Cantidad del producto/servicio
- `Item_id`: **OPCIONAL** - ID del producto (nullable)
- `Unit_Price`: **REQUERIDO** - Precio unitario del producto/servicio

**Ejemplo válido:**
```json
{
    "Prepayment": false,
    "Items": [{
        "ApplyTo": false,
        "InvoiceNumber": null,
        "Description": "Producto Normal",
        "Net_line": 50.0,
        "Quantity": 2,
        "Item_id": "PROD001",
        "Unit_Price": 25.0
    }]
}
```

**Ejemplo inválido:**
```json
{
    "Prepayment": false,
    "Items": [{
        "ApplyTo": false,
        "InvoiceNumber": null,
        "Description": "Falta Quantity y Unit_Price",
        "Net_line": 30.0,
        "Quantity": null,  // REQUERIDO cuando Prepayment=false + ApplyTo=false
        "Unit_Price": null // REQUERIDO cuando Prepayment=false + ApplyTo=false
    }]
}
```

### Regla `required_if:Items.*.ApplyTo,true`

La validación de `InvoiceNumber` funciona con lógica condicional básica:

```php
'Items.*.InvoiceNumber' => 'required_if:Items.*.ApplyTo,true|string|max:20'
```

### Comportamiento Confirmado

Tu ejemplo original **es completamente correcto** porque es un **PREPAGO**:
```json
{
    "ApplyTo": false,
    "InvoiceNumber": null,
    "Description": "PAGO POR ADELANTADO"
}
```

- `ApplyTo = false` → `InvoiceNumber` es opcional
- `InvoiceNumber = null` → Permitido cuando `ApplyTo = false`
- Validación pasa exitosamente

## Correcciones Aplicadas

### 1. CashAccountID
- **Antes**: `nullable|string|max:20`
- **Después**: `required|string|max:15`
- **Razón**: Según stub es NOT NULL y varchar(15)

### 2. Sintaxis corregida
- **Problema**: Comilla faltante en línea 56
- **Solución**: Agregada coma final en validación InvoiceNumber

## Ejemplo de Uso Válido

```json
{
    "Reference":"11059",
    "CheckNumber":"11059",
    "ReceiptNumber":"11059",
    "CustomerID":"115986",
    "CustomerName":"Dra. Luisa Cuddy de Los Mares del Alva",
    "Total":11.000000,
    "Method_of_Payment":"02-Efectivo",
    "TaxID":null,
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":true,
    "DepositTicketID":null,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":null,
            "Description":"PAGO POR ADELANTADO",
            "Net_line":11.000000,
            "Quantity":null,
            "Item_id":null,
            "Unit_Price":null,
            "Taxable":null
        }
    ]
}
```

## Casos de Prueba Recomendados

### Casos Válidos

#### 1. **Prepago positivo**: Prepayment=true, campos opcionales null
```json
{
    "Prepayment": true,
    "Items": [{"ApplyTo": false, "InvoiceNumber": null, "Quantity": null, "Unit_Price": null}]
}
```

#### 2. **Devolución prepago**: Prepayment=true, montos negativos
```json
{
    "Prepayment": true, 
    "Total": -15.50,
    "Items": [{"Net_line": -15.50, "Quantity": null, "Unit_Price": null}]
}
```

#### 3. **Aplicar a factura**: Prepayment=false, ApplyTo=true, con InvoiceNumber
```json
{
    "Prepayment": false,
    "Items": [{"ApplyTo": true, "InvoiceNumber": "FE001", "Quantity": null, "Unit_Price": null}]
}
```

#### 4. **Producto nuevo**: Prepayment=false, ApplyTo=false, con Quantity y Unit_Price
```json
{
    "Prepayment": false,
    "Items": [{"ApplyTo": false, "Quantity": 2, "Unit_Price": 25.0}]
}
```

### Casos Inválidos

#### 1. **Reference vacío**: Debe fallar (required)
#### 2. **ApplyTo=true sin InvoiceNumber**: Debe fallar (required_if)
#### 3. **Prepayment=false, ApplyTo=false sin Quantity**: Debe fallar (lógica condicional)
#### 4. **Prepayment=false, ApplyTo=false sin Unit_Price**: Debe fallar (lógica condicional)
#### 5. **CashAccountID vacío**: Debe fallar (required)
#### 6. **CustomerName >39 chars**: Debe fallar (max:39)

## Testing

Ejecutar scripts de pruebas:
```bash
# Prueba general del objeto de ejemplo
./docs/testing/test-customer-receipt-validations.sh

# Pruebas específicas de lógica ApplyTo
./docs/testing/test-customer-receipt-applyto-cases.sh
```

### Casos de Prueba Ejecutados

#### Tu Objeto Original - VÁLIDO
```json
{"ApplyTo": false, "InvoiceNumber": null} // Pasa validación
```

#### Otros Casos Válidos
```json
{"ApplyTo": true, "InvoiceNumber": "FE001"}   // Pasa validación  
{"ApplyTo": false, "InvoiceNumber": "REF001"} // Pasa validación
```

#### Caso Inválido
```json
{"ApplyTo": true, "InvoiceNumber": null} // Falla validación
```

## Referencias

- **Stub Header**: `app/Models/stubs/customer_receipt_header_imp.sql.stub`
- **Stub Detail**: `app/Models/stubs/customer_receipt_detail_imp.sql.stub`
- **Request**: `app/Http/Requests/CustomerReceiptImpRequest.php`
