# Solución de Problemas - Vendor Payment API

## Problema 1: "El check number field is required"

### Síntoma
Al hacer POST a `/api/acicloud/vendor_payment` con JSON válido, se recibe:
```json
{
  "message": "El check number field is required. (and 1 more error)",
  "errors": {
    "CheckNumber": ["El check number field is required."],
    "VendorID": ["El vendor i d field is required."]
  }
}
```

### Causas Posibles

#### 1. Content-Type incorrecto
**Problema**: No se está enviando `Content-Type: application/json`

**Solución**:
```bash
# ✗ Incorrecto (sin Content-Type)
curl -X POST http://localhost/api/acicloud/vendor_payment \
  -H "Authorization: Bearer token" \
  -d '{"CheckNumber": "CHK-001"}'

# ✓ Correcto (con Content-Type)
curl -X POST http://localhost/api/acicloud/vendor_payment \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"CheckNumber": "CHK-001"}'
```

#### 2. Datos enviados como form-data
**Problema**: Enviando datos como `application/x-www-form-urlencoded` en lugar de JSON

**Solución en Postman**:
1. Seleccionar pestaña "Body"
2. Elegir "raw"
3. Seleccionar "JSON" en el dropdown
4. Pegar el JSON

**Solución en código**:
```javascript
// ✗ Incorrecto
fetch('/api/acicloud/vendor_payment', {
  method: 'POST',
  body: formData  // FormData object
})

// ✓ Correcto
fetch('/api/acicloud/vendor_payment', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify(data)
})
```

#### 3. Header Accept faltante
**Problema**: Laravel puede devolver HTML en lugar de JSON sin el header Accept

**Solución**:
```bash
curl -X POST http://localhost/api/acicloud/vendor_payment \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \  # ← Agregar este header
  -d '{"CheckNumber": "CHK-001"}'
```

---

## Problema 2: UniquePaymentID = 0

### Síntoma
El vendor payment se crea pero `UniquePaymentID` es 0 en lugar de un número incremental.

### Causa
La tabla `vendor_payment_header_imp` no tiene `AUTO_INCREMENT` configurado correctamente.

### Diagnóstico
```sql
SHOW CREATE TABLE vendor_payment_header_imp;

-- Si ves esto, la tabla está MAL:
-- UniquePaymentID bigint(20) NOT NULL,

-- Debería ser así:
-- UniquePaymentID bigint(20) NOT NULL AUTO_INCREMENT,
-- PRIMARY KEY (UniquePaymentID)
```

### Solución
Ejecutar el script de corrección:

```bash
./scripts/fix-vendor-payment-table.sh <organization_id>

# Ejemplo para organización 2:
./scripts/fix-vendor-payment-table.sh 2
```

O ejecutar manualmente:
```sql
USE db_18257061709732_90;  -- Reemplazar con tu base de datos

ALTER TABLE vendor_payment_header_imp 
MODIFY COLUMN UniquePaymentID BIGINT NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY (UniquePaymentID);
```

---

## Problema 3: Tabla no existe

### Síntoma
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'vendor_payment_header_imp' doesn't exist
```

### Causa
Las tablas no se crearon al crear la organización.

### Solución
Las tablas se crean automáticamente desde los stubs cuando:
1. Se crea una nueva organización
2. Se ejecuta el proceso de creación de base de datos

**Stubs ubicación**:
- `app/Models/stubs/vendor_payment_header_imp.sql.stub`
- `app/Models/stubs/vendor_payment_detail_imp.sql.stub`

**Crear manualmente**:
```bash
docker exec -it docucenter_laravel.test php artisan tinker

$org = App\Models\Organization::find(2);
DB::connection()->useDatabase($org->database);

$stub = file_get_contents(app_path('Models/stubs/vendor_payment_header_imp.sql.stub'));
DB::unprepared($stub);

$stub = file_get_contents(app_path('Models/stubs/vendor_payment_detail_imp.sql.stub'));
DB::unprepared($stub);
```

---

## Checklist de Prueba

Antes de hacer una request a la API, verificar:

- [ ] Token de autenticación válido
- [ ] Header `Authorization: Bearer {token}`
- [ ] Header `Content-Type: application/json`
- [ ] Header `Accept: application/json`
- [ ] Header `X-Organization-Id: {org_id}`
- [ ] JSON válido en el body
- [ ] Tabla tiene AUTO_INCREMENT configurado
- [ ] CheckNumber único (no existe en la BD)

---

## Prueba Completa Paso a Paso

### 1. Obtener Token
```bash
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$user = App\Models\User::first();
echo \$user->createToken('test')->plainTextToken;
"
```

### 2. Probar Creación
```bash
# Guardar token en variable
TOKEN="tu-token-aqui"
ORG_ID=2

# Hacer request
curl -X POST "http://localhost/api/acicloud/vendor_payment" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Organization-Id: $ORG_ID" \
  -d '{
    "CheckNumber": "CHK-TEST-001",
    "VendorID": "V123",
    "VendorName": "Proveedor Test",
    "Date": "2025-11-25",
    "Total": 1000.00,
    "Memo": "Pago test",
    "CashAccountID": "1100",
    "Method_of_payment": "CHECK",
    "PrePayment": false,
    "Items": [
      {
        "Item_Id": "ITEM001",
        "Description": "Producto 1",
        "GL_Acct": "5100",
        "Quantity": 2.0,
        "Unit_Price": 250.00,
        "Net_Line": 500.00,
        "ApplyTo": 123,
        "InvoiceNumber": "INV-001"
      }
    ]
  }'
```

### 3. Verificar Resultado
Si funciona correctamente, deberías ver:
```json
{
  "UniquePaymentID": 1,
  "ID_compania": 1,
  "CheckNumber": "CHK-TEST-001",
  "VendorID": "V123",
  "VendorName": "Proveedor Test",
  "Date": "2025-11-25 00:00:00",
  "Total": "1000.0000",
  ...
  "Items": [...]
}
```

---

## Scripts de Ayuda

### Probar desde línea de comandos (sin API)
```bash
docker exec -it docucenter_laravel.test php artisan test:vendor-payment 2
```

### Corregir tabla
```bash
./scripts/fix-vendor-payment-table.sh 2
```

### Prueba completa con API
```bash
./scripts/test-vendor-payment-api-complete.sh 2
```

---

## Contacto y Soporte

Si ninguna de estas soluciones funciona:

1. Verificar logs de Laravel: `storage/logs/laravel.log`
2. Verificar que el middleware de autenticación esté activo
3. Verificar que la organización exista y esté activa
4. Revisar permisos de base de datos

```bash
# Ver últimos logs
tail -f storage/logs/laravel.log
```
