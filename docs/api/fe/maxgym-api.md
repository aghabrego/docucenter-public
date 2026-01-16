# API Facturación Electrónica - Integración Maxgym

## Crear Venta Maxgym

**Endpoint:** `POST /api/v1/fe/create_sale_maxgym`  
**Controller:** `FeController::createSaleMaxgym`  
**Request Class:** `CreateSaleMaxgymRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Maxgym (sistema de gestión para gimnasios y centros deportivos).

### Estructura del Request

#### Evento Principal

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `idEvent` | string | ✅ | ID único del evento (UUID) |
| `dateCreated` | string | ✅ | Fecha de creación (ISO 8601) |
| `eventType` | string | ✅ | Tipo de evento ("payment.succeeded") |

#### Datos de la Transacción

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.id` | string | ✅ | ID único de la transacción |
| `data.idMember` | string | ✅ | ID del miembro/cliente |
| `data.status` | integer | ✅ | Estado del pago (1 = approved) |
| `data.statusName` | string | ✅ | Nombre del estado |
| `data.paymentDate` | string | ✅ | Fecha de pago (ISO 8601) |
| `data.transactionDate` | string | ✅ | Fecha de transacción |
| `data.basePrice` | string | ✅ | Precio base |
| `data.price` | string | ✅ | Precio final |
| `data.discount` | number | ✅ | Descuento aplicado |
| `data.units` | integer | ✅ | Cantidad de unidades |

#### Datos del Miembro/Cliente

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.member.name` | string | ✅ | Nombre del cliente |
| `data.member.lastName` | string | ✅ | Apellido del cliente |
| `data.member.email` | string | ✅ | Email del cliente |
| `data.member.documentNumber` | string | ✅ | Número de documento/RUC |
| `data.member.address` | string | ❌ | Dirección del cliente |
| `data.member.cp` | string | ❌ | Código postal |
| `data.member.countryCode` | string | ❌ | Código del país |

#### Líneas/Productos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.lines[].idProduct` | string | ✅ | ID del producto/servicio |
| `data.lines[].name` | string | ✅ | Nombre del producto |
| `data.lines[].price` | string | ✅ | Precio del producto |

#### Método de Pago

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.paymentMethod.name` | string | ❌ | Nombre del método |
| `data.paymentMethod.last4` | string | ❌ | Últimos 4 dígitos (tarjetas) |
| `data.paymentMethod.brand` | string | ❌ | Marca de la tarjeta |
| `data.paymentMethod.type` | integer | ✅ | Tipo de pago (2 = card) |
| `data.paymentMethod.typeName` | string | ✅ | Nombre del tipo |

### Ejemplo de Request (Basado en Test)

```json
{
  "idEvent": "3918fcfc-f50f-4fb1-b003-cf964afce7bd",
  "dateCreated": "2025-02-13T17:52:32Z",
  "eventType": "payment.succeeded",
  "data": {
    "id": "Yaed",
    "idMember": "Zav",
    "member": {
      "name": "sera 4",
      "lastName": "abc",
      "email": "smm034@gmail.com",
      "documentNumber": "8-123-456",
      "address": "aaaa 44 5",
      "cp": "04769",
      "countryCode": "PA"
    },
    "lines": [
      {
        "idProduct": "Z9l",
        "name": "Membresía Mensual Premium",
        "price": "63.45"
      },
      {
        "idProduct": "MXR",
        "name": "Entrenamiento Personal",
        "price": "75.00"
      },
      {
        "idProduct": "Bw1",
        "name": "Clase de Spinning",
        "price": "25.00"
      }
    ],
    "paymentMethod": {
      "name": null,
      "last4": "4242",
      "brand": "visa",
      "type": 2,
      "typeName": "card"
    },
    "status": 1,
    "statusName": "approved",
    "paymentDate": "2025-02-13T17:24:00",
    "transactionDate": "2025-02-13T17:25:00",
    "basePrice": "163.45",
    "price": "163.45",
    "unitPrice": "0",
    "idCoupon": null,
    "discount": 0,
    "units": 1,
    "type": 5,
    "typeName": "combo"
  }
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Pago Maxgym procesado exitosamente",
  "job_id": "uuid-generated-job-id",
  "event_id": "3918fcfc-f50f-4fb1-b003-cf964afce7bd",
  "member_id": "Zav"
}
```

### Validaciones Específicas

#### Validación de RUC

- **Persona Natural**: Formato `P-T-A` (ej: `8-123-456` o `PE-123-456`)
  - Provincia: 1-13 o PE (extranjeros)
  - Tomo: 1-6 dígitos
  - Asiento: 1-6 dígitos

- **Empresa**: Formato `RP-TS-NA` (ej: `155757563-2-2024`)
  - Registro Público: 1-9 dígitos
  - Tipo de Sociedad: 1-2 dígitos
  - Número de Actividad: 1-4 dígitos

#### Otras Validaciones

- **Event Type**: Debe ser "payment.succeeded"
- **Status**: Debe ser 1 (approved)
- **Lines**: Mínimo 1 producto/servicio
- **Member**: Información completa del cliente
- **Payment Method**: Método de pago válido

### Casos de Uso

- **Gimnasios**: Membresías y servicios deportivos
- **Centros de Fitness**: Clases y entrenamientos
- **Deportes**: Alquiler de equipos y espacios
- **Wellness**: Servicios de bienestar y salud

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Pago procesado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Notas Técnicas

- **Procesamiento**: Asíncrono usando `CreateSaleMaxgymJob`
- **Webhook**: Diseñado para recibir webhooks de Maxgym
- **RUC**: Validación específica para RUC panameños
- **Monedas**: USD, PAB soportadas
- **Eventos**: Solo procesa eventos "payment.succeeded"
- **Combos**: Soporte para paquetes y servicios combinados
