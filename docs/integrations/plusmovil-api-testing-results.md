# Resultados de Pruebas: PlusMovil API

**Fecha:** 31 de octubre de 2025  
**Ambiente:** QA  
**Estado:** Autenticación funcionando, algunos endpoints con errores del servidor

---

## Resumen Ejecutivo

Se completó con éxito la integración de autenticación con AWS Cognito para la API de PlusMovil. El token de acceso se obtiene correctamente y es aceptado por el API Gateway. Sin embargo, se identificaron problemas de configuración en el servidor que impiden el uso de ciertos endpoints.

---

## Configuración Confirmada

### Endpoints por Ambiente

| Ambiente | Base URL | Client ID |
|----------|----------|-----------|
| **QA** | `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa` | `7t3s7lb4tfg6ssal586l929ovl` |
| **Producción** | `https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod` | `771vv2q1ararj5u1f084opsdg7` |

### Autenticación

**Método:** AWS Cognito con User Pool  
**Región:** us-east-1  
**Flow:** USER_PASSWORD_AUTH  
**Header:** `Authorization: Bearer {ACCESS_TOKEN}`  
**Expiración:** 60 minutos (3600 segundos)

---

## Resultados de Pruebas

### Autenticación Cognito

```bash
aws cognito-idp initiate-auth \
    --region us-east-1 \
    --auth-flow USER_PASSWORD_AUTH \
    --client-id 7t3s7lb4tfg6ssal586l929ovl \
    --auth-parameters USERNAME=xxx,PASSWORD=xxx
```

**Estado:** **EXITOSO**  
**Respuesta:** Token de acceso obtenido correctamente  
**Duración:** ~2 segundos

---

### Endpoint: `/sys-logs`

**URL:** `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/sys-logs`  
**Método:** GET  
**Autenticación:** Token aceptado  

**Parámetros Soportados:**
- `limit` - Número de registros (default: 10)
- `offset` - Paginación (default: 0)
- `module_name_like` - Búsqueda parcial por módulo
- `action_type_in` - Múltiples tipos de acción
- `id_gte`, `id_lte` - Rangos numéricos
- `created_at_between` - Rangos de fecha
- `order_by`, `order_dir` - Ordenamiento

**Resultado:**
```json
{
  "code": -1,
  "module": "SysLogController",
  "message": "Error al obtener los Logs",
  "error": "Association with alias \"created_user\" does not exist on sys_log",
  "data": null
}
```

**HTTP Status:** 500 Internal Server Error

**Análisis:**
- El token es **válido y aceptado**
- Error de configuración en el modelo `sys_log` del servidor
- Falta definir la relación `created_user` en el modelo Eloquent/Sequelize
- **Acción requerida:** Equipo de PlusMovil debe corregir la configuración del modelo

---

### Endpoint: `/invoices`

**URL:** `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/invoices`  
**Método:** GET  
**Autenticación:** Token rechazado  

**Resultado:**
```json
{
  "message": "Invalid key=value pair (missing equal-sign) in Authorization header (hashed with SHA-256 and encoded with Base64): 'VA7TJRPbAsXU3QrW46d3h8l61BHLHjXf7IqJX4it2CE='."
}
```

**HTTP Status:** 403 Forbidden

**Análisis:**
- El endpoint rechaza el Bearer token de Cognito
-  Mensaje sugiere que espera **AWS Signature Version 4** (formato key=value)
-  Posible configuración mixta: algunos endpoints con Cognito, otros con AWS IAM
- **Acción requerida:** Confirmar con equipo de PlusMovil el método de autenticación para este endpoint

---

### Endpoint: `/inv-products`

**URL:** `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/inv-products`  
**Método:** GET  
**Autenticación:** Token aceptado  

**Parámetros Soportados:**
- `limit` - Número máximo de resultados (default: sin límite)
- `offset` - Paginación (default: 0)
- `field_like` - Búsqueda parcial (ej: `code_like=ABC`)
- `field_in` - Múltiples valores (ej: `status_in=1,2,3`)
- `field_gte`, `field_lte`, `field_gt`, `field_lt` - Rangos numéricos
- `field_ne` - Valores distintos
- `field_between` - Rangos entre dos valores
- `order_by` - Campos para ordenar (separados por coma)
- `order_dir` - Dirección de orden (asc/desc por campo)

**Ejemplo de Resultado Exitoso:**
```json
{
  "code": 0,
  "message": "Productos en Inventario obtenidos correctamente",
  "error": "",
  "data": [
    {
      "id": 62331,
      "cat_product_id": 18,
      "inv_warehouse_id": 7756,
      "inv_batch_id": null,
      "batch_number": "16962",
      "code": "878812446",
      "barcode": "878812446",
      "sku": "878812446",
      "stock_control_type": 0,
      "status": 5,
      "created_user_id": 99068,
      "created_at": "2025-08-26T21:42:49.000Z",
      "updated_user_id": null,
      "updated_at": null,
      "cat_product": {
        "id": 18,
        "code": "105380",
        "name": "TARJETA MAS MOVIL  B/6.00",
        "stock_control_type": 0,
        "operator_unit_cost": "0.0300",
        "operator_list_price": "5.4000",
        "distributor_suggested_price": "5.9064",
        "public_price": "6.0000"
      },
      "inv_warehouse": {
        "id": 7756,
        "name": "Daniel Sang"
      }
    }
  ]
}
```

**HTTP Status:** 200 OK

**Análisis:**
- Token de Cognito **aceptado y funcionando**
- Respuesta incluye relaciones: `cat_product` y `inv_warehouse`
- Filtros dinámicos funcionan correctamente
- Ordenamiento simple y múltiple probado exitosamente
- Paginación operativa con `limit` y `offset`
- Sin filtros devuelve 502 (timeout o límite de datos)
- Con `limit` funciona perfectamente

**Campos Disponibles:**
- `id` - ID único del producto en inventario
- `cat_product_id` - Referencia a catálogo de productos
- `inv_warehouse_id` - ID del almacén/bodega
- `inv_batch_id` - ID del lote (nullable)
- `batch_number` - Número de lote
- `code` - Código del producto
- `barcode` - Código de barras
- `sku` - Stock Keeping Unit
- `stock_control_type` - Tipo de control (0=sin control, 1=con control)
- `status` - Estado (4=disponible?, 5=activo?)
- `created_user_id` - Usuario que creó
- `created_at` - Fecha de creación
- `updated_user_id` - Usuario que actualizó
- `updated_at` - Fecha de actualización

**Relaciones Incluidas:**
```json
"cat_product": {
  "id": integer,
  "code": string,
  "name": string,
  "stock_control_type": integer,
  "operator_unit_cost": decimal,
  "operator_list_price": decimal,
  "distributor_suggested_price": decimal,
  "public_price": decimal
},
"inv_warehouse": {
  "id": integer,
  "name": string
}
```

**Ejemplos de Uso:**
```bash
# Obtener últimos 10 productos
GET /inv-products?limit=10&order_by=id&order_dir=desc

# Buscar por código
GET /inv-products?code_like=878812&limit=10

# Filtrar por status
GET /inv-products?status_in=4,5&limit=20

# Filtrar por warehouse
GET /inv-products?inv_warehouse_id=7756&limit=10

# Rango de IDs
GET /inv-products?id_gte=60000&id_lte=65000&limit=50

# Ordenamiento múltiple
GET /inv-products?order_by=status,id&order_dir=asc,desc&limit=20

# Consulta compleja
GET /inv-products?status=5&inv_warehouse_id=7756&code_like=878&order_by=created_at&order_dir=desc&limit=15
```

**Notas Importantes:**
- **SIEMPRE usar `limit`** para evitar timeouts (502)
- Respuestas bien estructuradas con metadata (`code`, `message`, `error`, `data`)
- Incluye relaciones automáticamente (no requiere `include` param)
- Ideal para sincronización de inventario con DocuCenter

---

### Endpoint: `/com-invoices`

**URL:** `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/com-invoices`  
**Método:** GET  
**Autenticación:** Token aceptado  

**Parámetros Soportados:**
- `limit` - Número máximo de resultados
- `offset` - Paginación
- `field_like` - Búsqueda parcial (ej: `customer_name_like=maria`)
- `field_in` - Múltiples valores (ej: `status_in=1,2`)
- `field_gte`, `field_lte`, `field_gt`, `field_lt` - Rangos numéricos y fechas
- `field_ne` - Valores distintos
- `field_between` - Rangos (ej: `invoice_date_between=2025-01-01,2025-12-31`)
- `order_by` - Campos para ordenar
- `order_dir` - Dirección de orden (asc/desc)

**Ejemplo de Resultado Exitoso:**
```json
{
  "code": 0,
  "message": "Facturas obtenidos correctamente",
  "error": "",
  "data": [
    {
      "id": 303,
      "com_order_id": null,
      "com_distributor_id": 23,
      "com_branch_id": 9,
      "com_pos_id": 6870,
      "com_seller_id": 86,
      "com_settlement_id": null,
      "ops_route_id": 41,
      "invoice_type": 2,
      "invoice_number": "0",
      "invoice_date": "2025-09-01T23:46:13.000Z",
      "customer_name": "Super centro la gran via",
      "customer_phone": "61056889",
      "customer_email": "email@email.com",
      "customer_vat_number": "PENDIENTE",
      "billing_address": "Auto pista Arraijan chorrers",
      "sub_total": "4.5981",
      "discount": "0.0000",
      "tax_amount": "0.3200",
      "total": "4.9200",
      "balance": "4.9200",
      "status": 1,
      "comments": "Factura generada desde la app",
      "liquidated_type": 0,
      "liquidated_at": null,
      "created_user_id": 99090,
      "created_at": "2025-09-01T23:46:13.000Z",
      "updated_user_id": null,
      "updated_at": null,
      "total_quantity": null,
      "created_user": {
        "id": 99090,
        "name": "Humberto Zambrano",
        "photo": "/images/profile/user-1.jpg",
        "username": "hzambrano_el_nawal",
        "email": "hzambrano@elnawal.com.pa"
      },
      "updated_user": null,
      "com_order": null,
      "com_distributor": {
        "id": 23,
        "name": "DISTRIBUIDORA EL NAWAL S.A.",
        "code": "1040798",
        "legal_name": "DISTRIBUIDORA EL NAWAL,S.A.",
        "vat_number": "155597688-2-2015",
        "address": "Via España, Edif Dorchester piso 4 ofic 409",
        "email": "larosemena@elnawal.com.pa",
        "phone": "204-5715"
      },
      "com_branch": {
        "id": 9,
        "name": "DISTRIBUIDORA EL NAWAL S.A. - LA CHORRERA",
        "code": "1040798 - 002"
      },
      "com_pos": {
        "id": 6870,
        "name": "Super centro la gran via",
        "code": "133-232"
      },
      "com_seller": {
        "id": 86,
        "name": "Humberto Zambrano ",
        "code": "013"
      },
      "ops_route": {
        "id": 41,
        "name": "13",
        "code": "13"
      },
      "com_invoice_payment_summary": []
    }
  ]
}
```

**HTTP Status:** 200 OK

**Análisis:**
- Token de Cognito **aceptado y funcionando**
- Respuesta incluye múltiples relaciones: `com_distributor`, `com_branch`, `com_pos`, `com_seller`, `ops_route`, `created_user`
- Filtros dinámicos funcionan correctamente
- Incluye resumen de pagos: `com_invoice_payment_summary`
- Campos financieros completos: `sub_total`, `discount`, `tax_amount`, `total`, `balance`
- Información de cliente completa

**Campos Principales:**
- `id` - ID único de la factura
- `invoice_number` - Número de factura (muchas con valor "0")
- `invoice_date` - Fecha de emisión
- `invoice_type` - Tipo de factura (0, 1, 2...)
- `customer_name` - Nombre del cliente
- `customer_phone` - Teléfono
- `customer_email` - Email
- `customer_vat_number` - RUC/NIT
- `billing_address` - Dirección de facturación
- `sub_total` - Subtotal antes de descuentos
- `discount` - Monto de descuento
- `tax_amount` - Monto de impuestos (ITBMS)
- `total` - Total de la factura
- `balance` - Saldo pendiente
- `status` - Estado (0=pendiente?, 1=activa?, 2=pagada?, 3=anulada?)
- `comments` - Comentarios

**Relaciones Incluidas:**
```json
"com_distributor": {
  "id": integer,
  "name": string,
  "code": string,
  "legal_name": string,
  "vat_number": string,
  "address": string,
  "email": string,
  "phone": string
},
"com_branch": {
  "id": integer,
  "name": string,
  "code": string
},
"com_pos": {
  "id": integer,
  "name": string,
  "code": string
},
"com_seller": {
  "id": integer,
  "name": string,
  "code": string
},
"ops_route": {
  "id": integer,
  "name": string,
  "code": string
},
"created_user": {
  "id": integer,
  "name": string,
  "username": string,
  "email": string,
  "photo": string
},
"com_invoice_payment_summary": []
```

**Ejemplos de Uso para DocuCenter:**
```bash
# Sincronización incremental (últimas facturas)
GET /com-invoices?invoice_date_gte=2025-10-01&limit=100&order_by=invoice_date&order_dir=asc

# Facturas pendientes de pago
GET /com-invoices?balance_gt=0&status_ne=3&limit=50

# Facturas de un cliente específico por RUC
GET /com-invoices?customer_vat_number=155597688-2-2015&limit=20

# Reporte diario
GET /com-invoices?invoice_date_between=2025-11-04,2025-11-04&limit=100

# Facturas por distribuidor
GET /com-invoices?com_distributor_id=23&limit=50

# Búsqueda por cliente
GET /com-invoices?customer_name_like=super&limit=20

# Facturas por vendedor
GET /com-invoices?com_seller_id=86&limit=30
```

**Observaciones Importantes:**
- Muchas facturas tienen `invoice_number: "0"` - posiblemente se genera después
- Campo `customer_vat_number` puede tener valor "PENDIENTE"
- `balance` permite identificar facturas pagadas/pendientes
- Incluye información completa de vendedor, sucursal y punto de venta
- Campo `comments` útil para rastrear origen ("Factura generada desde la app")
- `com_invoice_payment_summary` array para detalles de pagos

**Mapeo Sugerido a DocuCenter (`Sales_Header_Imp`):**

| PlusMovil Campo | DocuCenter Campo | Notas |
|-----------------|------------------|-------|
| `id` | `external_invoice_id` | Referencia externa |
| `invoice_number` | `fiscal_document_number` | Si no es "0" |
| `invoice_date` | `document_date` | Fecha de emisión |
| `customer_name` | Cliente o crear nuevo | |
| `customer_vat_number` | Buscar/crear cliente | Validar "PENDIENTE" |
| `sub_total` | `sub_total` | |
| `discount` | `discount` | |
| `tax_amount` | `tax_amount` | |
| `total` | `total` | |
| `balance` | Campo personalizado | Para CXC |
| `status` | `status` | Mapear valores |
| `com_distributor` | `organization_id` | Mapear distribuidor |
| `com_seller_id` | `seller_id` | Si existe en DocuCenter |

**Próximos Pasos:**
1. Mapear valores de `status` (0, 1, 2, 3...)
2. Mapear valores de `invoice_type`
3. **RESUELTO: Items disponibles en `/com-invoices/{id}`** (ver `plusmovil-invoice-items-SOLVED.md`)
4. Implementar sincronización de headers + items
5. Manejar facturas sin número ("0")
6. Integrar con módulo de CXC usando campo `balance`
7. Sincronizar información de clientes
8. Implementar expansión de rangos de series si se requiere

**Actualización Importante:**
- **SÍ se encontraron items/detalles en `/com-invoices/{id}`**
- Endpoint individual usa autenticación Cognito (NO requiere AWS Signature)
- Se puede sincronizar headers Y detalles completos
- Soporte para productos serializados con rangos
- Ver solución completa en: `docs/integrations/plusmovil-invoice-items-SOLVED.md`

---

## Configuración de Errores Identificados

### 1. Error de Relación `created_user` en `/sys-logs`

**Ubicación en servidor:** Modelo `sys_log`

**Causa probable:**
```php
// En el controlador o servicio se está haciendo:
$logs = SysLog::with('created_user')->get();

// Pero en el modelo SysLog falta:
public function createdUser() {
    return $this->belongsTo(User::class, 'created_by');
}
```

**Solución sugerida para PlusMovil:**
```php
// En app/Models/SysLog.php o equivalente
public function createdUser() {
    return $this->belongsTo(User::class, 'created_by', 'id');
}
```

### 2. Autenticación Mixta en API Gateway

**Problema:** Algunos endpoints aceptan Cognito Bearer token, otros requieren AWS Signature

**Endpoints con Cognito:**
- `/sys-logs` (con error del servidor, pero acepta token)
- `/inv-products` (funcionando correctamente)
- `/com-invoices` (funcionando correctamente)

**Endpoints con AWS Signature:**
- `/invoices`
- `/invoices/stats`
- `/health`

**Solución recomendada:**
1. **Opción A:** Unificar todos los endpoints con Cognito Bearer token
2. **Opción B:** Documentar claramente qué endpoints usan qué método
3. **Opción C:** Implementar ambos métodos en DocuCenter según necesidad

---

## Scripts Creados

### 1. `get-plusmovil-token.sh`
**Ubicación:** `docs/testing/get-plusmovil-token.sh`  
**Propósito:** Obtener token de AWS Cognito interactivamente  
**Uso:**
```bash
./docs/testing/get-plusmovil-token.sh
```

**Características:**
- Selección de ambiente (QA/Prod)
- Ingreso seguro de credenciales
- Validación de token
- Guardado automático en archivo `.plusmovil-token-{ENV}.txt`
- Agregado automático a `.gitignore`
- Prueba opcional del token con endpoint

### 2. `test-plusmovil-api.sh`
**Ubicación:** `docs/testing/test-plusmovil-api.sh`  
**Propósito:** Probar endpoints de PlusMovil con diferentes filtros  
**Uso:**
```bash
./docs/testing/test-plusmovil-api.sh
```

**Características:**
- Pruebas de endpoints con y sin filtros
- Ejemplos de todos los tipos de filtros soportados
- Documentación interactiva de parámetros

### 3. `test-invoices-endpoint.sh`
**Ubicación:** `docs/testing/test-invoices-endpoint.sh`  
**Propósito:** Pruebas específicas de endpoints de invoices  
**Uso:**
```bash
./docs/testing/test-invoices-endpoint.sh
```

### 4. `test-inv-products-endpoint.sh`
**Ubicación:** `docs/testing/test-inv-products-endpoint.sh`  
**Propósito:** Pruebas completas del endpoint /inv-products con filtros dinámicos  
**Uso:**
```bash
./docs/testing/test-inv-products-endpoint.sh
```

**Características:**
- Pruebas de paginación (limit, offset)
- Búsquedas parciales (code_like, barcode_like)
- Filtros por status y warehouse
- Rangos numéricos y de fechas
- Ordenamiento simple y múltiple
- Consultas complejas personalizadas
- Modo interactivo con ejemplos

---

## Próximos Pasos

### Acciones Inmediatas

1. **Reportar a PlusMovil:**
   - Error de relación `created_user` en endpoint `/sys-logs`
   - Inconsistencia de autenticación en endpoint `/invoices`
   - Solicitar documentación completa de endpoints disponibles
   - Solicitar Swagger/OpenAPI actualizado

3. **Implementación en DocuCenter:**
   - Scripts de autenticación listos para uso
   - Endpoint `/inv-products` validado y documentado
   -  Pendiente: Implementar servicio PHP para `/inv-products`
   -  Pendiente: Implementar sincronización de inventario

### Acciones Pendientes (cuando PlusMovil corrija)

3. **Crear Service en Laravel:**
```php
// app/Services/PlusMovilService.php
class PlusMovilService {
    public function authenticate() {
        // Usar AWS Cognito SDK
    }
    
    public function getSysLogs($filters = []) {
        // Llamar endpoint /sys-logs (cuando se corrija)
    }
    
    public function getInventoryProducts($filters = []) {
        // Llamar endpoint /inv-products
        // Soportar filtros: limit, offset, code_like, status_in, etc.
    }
    
    public function getInvoices($filters = []) {
        // Llamar endpoint /invoices (cuando funcione)
    }
}
```

4. **Configurar en `.env`:**
```env
PLUSMOVIL_ENVIRONMENT=qa
PLUSMOVIL_CLIENT_ID=7t3s7lb4tfg6ssal586l929ovl
PLUSMOVIL_USERNAME=xxx
PLUSMOVIL_PASSWORD=xxx
PLUSMOVIL_BASE_URL=https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa
```

5. **Testing automatizado:**
   - Crear Feature Tests en Laravel
   - Mockear respuestas de Cognito
   - Probar manejo de errores

---

## Contacto con PlusMovil

**Puntos a aclarar en próxima comunicación:**

1. ¿Cuándo estará corregido el error de `created_user` en `/sys-logs`?
2. ¿Los endpoints `/invoices` y similares deben usar Bearer token o AWS Signature?
3. ¿Existe documentación Swagger actualizada disponible?
4. ¿Qué otros endpoints están disponibles además de `/sys-logs` e `/invoices`?
5. ¿Hay rate limiting configurado? ¿Cuáles son los límites?
6. ¿Existe endpoint de health check para monitoreo?

---

## Referencias

- **AWS Cognito Documentation:** https://docs.aws.amazon.com/cognito/
- **AWS CLI Installation:** https://aws.amazon.com/cli/
- **API Gateway Authorization:** https://docs.aws.amazon.com/apigateway/latest/developerguide/apigateway-control-access-to-api.html
- **Scripts de Prueba:** `docs/testing/`

---

## Changelog

| Fecha | Cambio |
|-------|--------|
| 2025-10-31 | Documentación inicial de resultados de pruebas |
| 2025-10-31 | Confirmada autenticación Cognito funcionando |
| 2025-10-31 | Identificados errores del servidor en `/sys-logs` e `/invoices` |
