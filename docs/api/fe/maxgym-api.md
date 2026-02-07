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
| `idEvent` | string | | ID único del evento (UUID) |
| `dateCreated` | string | | Fecha de creación (ISO 8601) |
| `eventType` | string | | Tipo de evento ("payment.succeeded") |

#### Datos de la Transacción

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.id` | string | | ID único de la transacción |
| `data.idMember` | string | | ID del miembro/cliente |
| `data.status` | integer | | Estado del pago (1 = approved) |
| `data.statusName` | string | | Nombre del estado |
| `data.paymentDate` | string | | Fecha de pago (ISO 8601) |
| `data.transactionDate` | string | | Fecha de transacción |
| `data.basePrice` | string | | Precio base |
| `data.price` | string | | Precio final |
| `data.discount` | number | | Descuento aplicado |
| `data.units` | integer | | Cantidad de unidades |

#### Datos del Miembro/Cliente

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.member.name` | string | | Nombre del cliente |
| `data.member.lastName` | string | | Apellido del cliente |
| `data.member.email` | string | | Email del cliente |
| `data.member.documentNumber` | string | | Número de documento/RUC |
| `data.member.address` | string | | Dirección del cliente |
| `data.member.cp` | string | | Código postal |
| `data.member.countryCode` | string | | Código del país |

#### Líneas/Productos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.lines[].idProduct` | string | | ID del producto/servicio |
| `data.lines[].name` | string | | Nombre del producto |
| `data.lines[].price` | string | | Precio del producto |

#### Método de Pago

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.paymentMethod.name` | string | | Nombre del método |
| `data.paymentMethod.last4` | string | | Últimos 4 dígitos (tarjetas) |
| `data.paymentMethod.brand` | string | | Marca de la tarjeta |
| `data.paymentMethod.type` | integer | | Tipo de pago (2 = card) |
| `data.paymentMethod.typeName` | string | | Nombre del tipo |

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

#### Detección Automática de Tipo de Precio (Webhook)

**Problema Identificado:** La API de MaxGym envía webhooks con **dos estructuras inconsistentes** donde `lines[].price` representa valores diferentes:

- **Webhook Tipo 1**: `lines[].price` = BASE (sin impuestos)
  - Ejemplo: Personal Trainer $425.00
  - `lines[].price` coincide con `data.basePrice`
  - Cálculo: base + tax = total

- **Webhook Tipo 2**: `lines[].price` = TOTAL (con impuestos)
  - Ejemplo: PYMES $58.85
  - `lines[].price` coincide con `data.price`
  - Cálculo: total - tax = base

**Solución Implementada:** Detección automática comparando `lines[].price` con `data.basePrice` y `data.price`:

```php
if (abs($linePrice - $finalReceivedBasePrice) < $tolerance) {
    // CASO 1: lines[].price es el BASE sin impuestos
    $baseAmount = $linePrice;
    $totalLinePrice = $linePrice + $finalTaxAmount;
    
} elseif (abs($linePrice - $receivedPrice) < $tolerance) {
    // CASO 2: lines[].price es el TOTAL con impuestos
    $baseAmount = $linePrice - $finalTaxAmount;
    $totalLinePrice = $linePrice;
}
```

**Ventajas**:
- Maneja ambos tipos automáticamente
- Usa campos consistentes del header como referencia
- Falla explícitamente con estructuras desconocidas
- Logs detallados para debugging

**Documentación Técnica**: Ver [MaxGym Amount Calculation Fix](../../technical/maxgym-amount-calculation-fix.md) para detalles completos.

---

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

---

## Identificación de Fuente (Origin)

Todas las ventas creadas desde la API MaxGym se marcan automáticamente con el campo `origin` en la base de datos para identificar su procedencia.

### Campo Origin en SalesHeaderImp

```php
'origin' => 'maxgym'
```

### Valores de Origin por Sistema

- `'maxgym'` - Ventas provenientes de MaxGym
- `'quickbooks'` - Ventas provenientes de QuickBooks Online
- `'shopify'` - Ventas provenientes de Shopify
- `'lightspeed'` - Ventas provenientes de Lightspeed
- `'kart21'` - Ventas provenientes de Kart21
- `'acicloud'` - Ventas provenientes de ACI Cloud ERP
- `'meypar'` - Ventas provenientes de Meypar Colombia
- `'docucenter'` - Ventas creadas nativamente (default)

### Propósito del Campo Origin

1. **Tracking de origen**: Identificar de qué sistema proviene cada venta
2. **Prevención de loops**: Evitar re-procesamiento de ventas
3. **Auditoría**: Facilitar rastreo y debugging
4. **Filtrado**: Permitir consultas específicas por origen

**Nota:** Este campo se asigna automáticamente por el sistema y no requiere ser especificado en el request.

---

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
- **Detección Automática de Precios**: Maneja dos tipos de estructuras de webhook inconsistentes
  - Tipo 1: `lines[].price` como BASE (sin impuestos)
  - Tipo 2: `lines[].price` como TOTAL (con impuestos)
  - Detección automática comparando con `data.basePrice` y `data.price`
- **RUC**: Validación específica para RUC panameños
- **Monedas**: USD, PAB soportadas
- **Eventos**: Solo procesa eventos "payment.succeeded"
- **Combos**: Soporte para paquetes y servicios combinados
- **Origin Tracking**: Todas las ventas se marcan automáticamente con `origin='maxgym'`
- **Precisión Decimal**: Sistema de control decimal configurable (items: 6, payments: 4, header: 2, taxes: 6)

---

## Referencias

### Documentación Relacionada

- **[Sistema de Origin - Implementación Completa](../../technical/origin-field-implementation-summary.md)** - Tracking unificado en 7 APIs FE
- **[MaxGym Amount Calculation Fix](../../technical/maxgym-amount-calculation-fix.md)** - Detección automática de tipos de precio en webhooks
- [QuickBooks Tax Code System](quickbooks-tax-code-system.md) - Sistema de origin en QuickBooks
- [MEYPAR API - Guía Completa](meypar-api-guia-completa.md) - Sistema de origin en MEYPAR
- [Validación de RUC en MaxGym](../../validations/maxgym-ruc-validation.md) - Validación RUC específica

### Código Fuente

- `app/Services/MaxgymService.php` - Servicio principal con campo origin
- `app/Jobs/CreateSaleMaxgymJob.php` - Job de procesamiento asíncrono
- `app/Http/Requests/CreateSaleMaxgymRequest.php` - Validación de requests

**Estado:** PRODUCCIÓN  
**Última Actualización:** 2026-02-07
