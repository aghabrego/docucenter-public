# API de Vendor Payment - AciCloud

## Descripción General

API RESTful para gestión de pagos a proveedores (Vendor Payments) en el sistema DocuCenter, integrada con AciCloud/Sage 100. Permite crear y consultar pagos a proveedores con sus líneas de detalle asociadas.

## Endpoints

### 1. Crear Vendor Payment

**Endpoint:** `POST /api/acicloud/vendor_payment`

**Autenticación:** Bearer Token + Organization ID Header

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
X-Organization-Id: {organization_id}
```

**Request Body:**
```json
{
  "CheckNumber": "CHK-001",           // Requerido, único, max 45 caracteres
  "VendorID": "V123",                 // Requerido, max 45 caracteres
  "VendorName": "Proveedor Test",     // Opcional, max 45 caracteres
  "Date": "2025-11-25",               // Opcional, formato: YYYY-MM-DD
  "Total": 1000.00,                   // Opcional, decimal(18,4)
  "Memo": "Pago proveedor",           // Opcional, max 30 caracteres
  "CashAccountID": "1100",            // Opcional, max 45 caracteres
  "Method_of_payment": "CHECK",       // Opcional, max 20 caracteres
  "PrePayment": false,                // Opcional, boolean
  "Items": [                          // Array de líneas de detalle
    {
      "Item_Id": "ITEM001",           // Opcional, max 45 caracteres
      "Description": "Producto 1",    // Opcional, max 255 caracteres
      "GL_Acct": "5100",              // Opcional, max 45 caracteres
      "Quantity": 2.0,                // Opcional, decimal(11,4)
      "Unit_Price": 250.00,           // Opcional, decimal(16,4)
      "Net_Line": 500.00,             // Opcional, decimal(16,2)
      "ApplyTo": 123,                 // Opcional, integer
      "InvoiceNumber": "INV-001"      // Opcional, max 20 caracteres
    }
  ]
}
```

**Response Success (200):**
```json
{
  "UniquePaymentID": 1,
  "ID_compania": 1,
  "CheckNumber": "CHK-001",
  "VendorID": "V123",
  "VendorName": "Proveedor Test",
  "Date": "2025-11-25 00:00:00",
  "Total": "1000.0000",
  "Memo": "Pago proveedor",
  "CashAccountID": "1100",
  "Method_of_payment": "CHECK",
  "PrePayment": 0,
  "Enviado": 0,
  "Error": 0,
  "ErrorPT": null,
  "Export_date": "2025-11-25 10:30:00",
  "Items": [
    {
      "ID": 1,
      "ID_compania": 1,
      "UniquePaymentID": 1,
      "Item_Id": "ITEM001",
      "Description": "Producto 1",
      "GL_Acct": "5100",
      "Quantity": "2.0000",
      "Unit_Price": "250.0000",
      "Net_Line": "500.00",
      "ApplyTo": 123,
      "InvoiceNumber": "INV-001"
    }
  ]
}
```

**Validaciones:**
- `CheckNumber` debe ser único en la base de datos de la organización
- Si `Date` no se proporciona, se usa la fecha actual con la zona horaria del usuario
- Si `Export_date` no se proporciona, se genera automáticamente
- Transacción atómica: si falla algún item, se hace rollback completo

---

### 2. Listar Vendor Payments

**Endpoint:** `GET /api/acicloud/vendor_payment_imp`

**Autenticación:** Bearer Token + Organization ID Header

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
X-Organization-Id: {organization_id}
```

**Query Parameters:**
- `limit`: Número de registros por página (default: 10)
- `page`: Número de página (default: 1)

**Filtros Disponibles:**
- `UniquePaymentID`: ID único del pago
- `CheckNumber`: Número de cheque
- `VendorID`: ID del proveedor
- `VendorName`: Nombre del proveedor
- `Date`: Fecha del pago (soporta rangos: `Date[from]` y `Date[to]`)
- `CashAccountID`: Cuenta de efectivo
- `Total`: Total del pago
- `Memo`: Nota del pago
- `PrePayment`: Pago anticipado (0 o 1)
- `Method_of_payment`: Método de pago
- `Export_date`: Fecha de exportación
- `Enviado`: Estado de envío (0 o 1)
- `Error`: Estado de error (0 o 1)

**Ordenamiento:**
- Soporta ordenamiento por todos los campos de filtros
- Uso: `order_by=CheckNumber&order_dir=asc`

**Ejemplos de Uso:**

```bash
# Listar todos (paginado)
GET /api/acicloud/vendor_payment_imp?limit=20

# Filtrar por proveedor
GET /api/acicloud/vendor_payment_imp?VendorID=V123

# Filtrar por rango de fechas
GET /api/acicloud/vendor_payment_imp?Date[from]=2025-11-01&Date[to]=2025-11-30

# Filtrar solo pagos no enviados
GET /api/acicloud/vendor_payment_imp?Enviado=0

# Filtrar por método de pago
GET /api/acicloud/vendor_payment_imp?Method_of_payment=CHECK

# Combinación de filtros con ordenamiento
GET /api/acicloud/vendor_payment_imp?VendorID=V123&Enviado=0&order_by=Date&order_dir=desc
```

**Response Success (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "UniquePaymentID": 1,
      "ID_compania": 1,
      "CheckNumber": "CHK-001",
      "VendorID": "V123",
      "VendorName": "Proveedor Test",
      "Date": "2025-11-25 00:00:00",
      "Total": "1000.0000",
      "vendor_payment_detail": [
        {
          "ID": 1,
          "UniquePaymentID": 1,
          "Item_Id": "ITEM001",
          "Description": "Producto 1",
          "Net_Line": "500.00"
        }
      ]
    }
  ],
  "first_page_url": "http://localhost/api/acicloud/vendor_payment_imp?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost/api/acicloud/vendor_payment_imp?page=1",
  "next_page_url": null,
  "path": "http://localhost/api/acicloud/vendor_payment_imp",
  "per_page": 10,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

---

## Arquitectura

### Modelos

**VendorPaymentHeaderImp** (`app/Models/VendorPaymentHeaderImp.php`)
- Tabla: `vendor_payment_header_imp`
- Primary Key: `UniquePaymentID`
- Relaciones:
  - `vendorPaymentDetail()`: HasMany → VendorPaymentDetailImp
  - `vendor()`: BelongsTo → VendorsExp
  - `vendorImp()`: BelongsTo → VendorsImp
- Traits: HasFactory, DataViewer, ActiveCreateTables, CustomConnection, UpdateSentAndError

**VendorPaymentDetailImp** (`app/Models/VendorPaymentDetailImp.php`)
- Tabla: `vendor_payment_detail_imp`
- Primary Key: `ID`
- Relación con header por: `UniquePaymentID`
- Traits: HasFactory, ActiveCreateTables, CustomConnection, Helper

### Servicios

**ACIcloudService** (`app/Services/ACIcloudService.php`)
- `vendorPaymentImp()`: Crea vendor payment con transacción atómica
- `getVendorPaymentImp()`: Lista vendor payments con filtros y paginación

### Controladores

**ACIcloudController** (`app/Http/Controllers/Sage/ACIcloudController.php`)
- `vendorPaymentImp()`: Endpoint POST para crear
- `getVendorPaymentImp()`: Endpoint GET para listar

### Requests

**VendorPaymentHeaderImpRequest** (`app/Http/Requests/VendorPaymentHeaderImpRequest.php`)
- Validaciones para creación
- Preparación de datos (fechas automáticas)

**ACIcloudVendorPaymentImpRequest** (`app/Http/Requests/ACIcloudVendorPaymentImpRequest.php`)
- Filtros permitidos
- Campos ordenables
- Usa trait `DataViewer` para filtrado avanzado

---

## Base de Datos

### Stubs SQL

Los stubs se crean automáticamente al crear la base de datos de una organización:

- `app/Models/stubs/vendor_payment_header_imp.sql.stub`
- `app/Models/stubs/vendor_payment_detail_imp.sql.stub`

### Estructura de Tablas

**vendor_payment_header_imp:**
```sql
CREATE TABLE `vendor_payment_header_imp` (
    UniquePaymentID BIGINT NOT NULL AUTO_INCREMENT,
    ID_compania INT NOT NULL,
    CheckNumber VARCHAR(45) NOT NULL,
    VendorID VARCHAR(45) NOT NULL,
    VendorName VARCHAR(45) NULL,
    Date DATETIME NULL,
    CashAccountID VARCHAR(45) NULL,
    Total DECIMAL(18,4) NULL,
    Memo VARCHAR(30) NULL,
    PrePayment TINYINT(1) NULL,
    Method_of_payment VARCHAR(20) NULL,
    Enviado TINYINT(1) NULL DEFAULT '0',
    Error TINYINT(1) NULL DEFAULT '0',
    ErrorPT VARCHAR(255) NULL,
    Export_date DATETIME NULL,
    PRIMARY KEY (UniquePaymentID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**vendor_payment_detail_imp:**
```sql
CREATE TABLE vendor_payment_detail_imp (
    ID INT NOT NULL AUTO_INCREMENT,
    ID_compania INT NOT NULL,
    UniquePaymentID BIGINT NOT NULL,
    Item_Id VARCHAR(45) NULL,
    Description VARCHAR(255) NULL,
    GL_Acct VARCHAR(45) NULL,
    Quantity DECIMAL(11,4) NULL,
    Unit_Price DECIMAL(16,4) NULL,
    Net_Line DECIMAL(16,2) NULL,
    ApplyTo INT NULL,
    InvoiceNumber VARCHAR(20) NULL,
    PRIMARY KEY (ID,ID_compania,UniquePaymentID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Testing

### Script de Prueba

Ubicación: `docs/testing/vendor-payment-api-test.sh`

```bash
# Configurar variables de entorno
export BASE_URL="http://localhost"
export TOKEN="your-token-here"
export ORG_ID="1"

# Ejecutar pruebas
./docs/testing/vendor-payment-api-test.sh
```

### Unit Tests

Ubicación: `tests/Unit/Jobs/PanamaPaymentsMadeJobTest.php`
- Pruebas de creación de tablas
- Pruebas de inserción de datos
- Pruebas de relaciones
- Pruebas de validaciones

---

## Notas Importantes

### Multi-Tenant
- Cada organización tiene su propia base de datos
- El sistema cambia automáticamente de conexión según la organización activa
- Usar siempre header `X-Organization-Id` para especificar la organización

### Convenciones
- **NO usar emojis** en logs, commits o mensajes
- Seguir patrón de AciCloud existente
- Usar transacciones para operaciones críticas
- Implementar rollback en caso de errores

### Seguridad
- Autenticación requerida (Sanctum)
- Validación de organización activa
- CheckNumber debe ser único por organización
- Validación de campos según reglas de negocio

---

## Changelog

### v1.0.0 - 2025-11-25
- Implementación inicial de API Vendor Payment
- Endpoints POST y GET
- Validaciones y filtros
- Documentación completa
- Scripts de prueba

---

## Referencias

- Patrón base: Purchase API (`purchaseImp` / `getPurchasesImp`)
- Convenciones: `.github/guias-desarrollo.md`
- Testing: `tests/Unit/Jobs/PanamaPaymentsMadeJobTest.php`
