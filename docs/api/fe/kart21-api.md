# API Facturación Electrónica - Integración Kart21

## Crear Venta Kart21

**Endpoint:** `POST /api/v1/fe/create_sale_kart21`  
**Controller:** `FeController::createSaleKart21`  
**Request Class:** `CreateSaleKart21Request`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Kart21 (sistema POS para restaurantes y retail).

### Estructura del Request

#### Evento Principal

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | ✅ | ID del evento |
| `event` | string | ✅ | Tipo de evento ("order.close") |
| `object_id` | string | ✅ | ID del objeto relacionado |

#### Datos de la Orden

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.id` | string | ✅ | ID de la orden |
| `data.number` | string | ✅ | Número de la orden |
| `data.status` | integer | ✅ | Estado de la orden (2 = CLOSED) |
| `data.status_description` | string | ✅ | Descripción del estado |
| `data.order_type` | integer | ✅ | Tipo de orden (0 = REGULAR) |
| `data.order_dt` | string | ✅ | Fecha y hora de la orden |
| `data.closed_dt` | string | ✅ | Fecha y hora de cierre |
| `data.subtotal` | number | ✅ | Subtotal de la orden |
| `data.tax` | number | ✅ | Impuestos |
| `data.total` | number | ✅ | Total de la orden |
| `data.paid` | number | ✅ | Monto pagado |

#### Usuario

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.create_user.id` | integer | ✅ | ID del usuario que creó |
| `data.create_user.first_name` | string | ✅ | Nombre del usuario |
| `data.create_user.last_name` | string | ✅ | Apellido del usuario |
| `data.create_user.email` | string | ✅ | Email del usuario |

#### Items de la Orden

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `data.items[].id` | integer | ✅ | ID del item |
| `data.items[].lineitem_number` | integer | ✅ | Número de línea |
| `data.items[].active` | boolean | ✅ | Si está activo |
| `data.items[].status` | integer | ✅ | Estado del item |
| `data.items[].class_id` | integer | ✅ | ID de la clase |
| `data.items[].discount` | number | ✅ | Descuento aplicado |
| `data.items[].quantity` | number | ✅ | Cantidad |
| `data.items[].total` | number | ✅ | Total del item |

### Ejemplo de Request (Basado en Test)

```json
{
  "id": 1,
  "event": "order.close",
  "object_id": "0000000001",
  "data": {
    "id": "0000000001",
    "parent_order_id": null,
    "number": "0000000001",
    "status": 2,
    "status_description": "CLOSED",
    "order_type": 0,
    "order_type_description": "REGULAR",
    "order_dt": "2024-07-26T14:10:07.247",
    "closed_dt": "2024-07-26T14:10:18.027",
    "subtotal": 3.75,
    "tax": 0.2625,
    "discount": 0.0,
    "items_discount": 0.0,
    "fee": 0.0,
    "gratuity": 0.0,
    "adjustment": 0.0,
    "cancel_fee": 0.0,
    "total": 4.0125,
    "total_refunds": 0.0,
    "balance": 0.0,
    "paid": 4.01,
    "discount_id": null,
    "has_approvals": false,
    "custom1": "",
    "custom2": null,
    "custom3": null,
    "online_code": null,
    "taxable": true,
    "terminal": null,
    "event_id": null,
    "service_fee": 0.0,
    "service_fee_percent": null,
    "gratuity_percent": null,
    "disable_auto_gratuity": false,
    "eligible_for_commission": null,
    "commission_position_id": null,
    "deleted": false,
    "create_user": {
      "id": 2,
      "first_name": "Test",
      "last_name": "FIKABLE",
      "email": "support@fikable.com"
    },
    "update_user": {
      "id": 2,
      "first_name": "Test",
      "last_name": "FIKABLE",
      "email": "support@fikable.com"
    },
    "customer": null,
    "items": [
      {
        "id": 322,
        "lineitem_number": 1,
        "active": true,
        "status": 1,
        "status_description": "PERMANENT",
        "class_id": 11,
        "custom1": null,
        "custom2": null,
        "customer_id": null,
        "discount": 0.0,
        "discount_id": null,
        "discount_notes": null,
        "discount_name": null,
        "discount_single_instance_id": null,
        "discount_type": null,
        "price": 3.75,
        "quantity": 1.0,
        "total": 3.75,
        "tax": 0.2625,
        "tax_percent": 0.07,
        "item": {
          "id": 7,
          "name": "Café Americano",
          "short_name": "Café",
          "description": "Café americano regular",
          "price": 3.75,
          "cost": 1.25,
          "tax_percent": 0.07,
          "active": true,
          "category_id": 2
        }
      }
    ],
    "payments": [
      {
        "id": 123,
        "amount": 4.01,
        "type": 1,
        "type_description": "CASH",
        "reference": null,
        "status": 1,
        "status_description": "APPROVED"
      }
    ]
  }
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Orden Kart21 procesada exitosamente",
  "job_id": "uuid-generated-job-id",
  "order_number": "0000000001"
}
```

### Validaciones Específicas

- **Event Type**: Debe ser "order.close"
- **Status**: Debe ser 2 (CLOSED)
- **Items**: Mínimo 1 item requerido
- **Payments**: Al menos un pago requerido
- **Totals**: Validación de coherencia en totales

### Casos de Uso

- **Restaurantes**: Órdenes de comida y bebida
- **Retail**: Ventas de productos diversos  
- **POS**: Sistema punto de venta integrado
- **Multi-pago**: Soporte para múltiples formas de pago

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Orden procesada exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Notas Técnicas

- **Procesamiento**: Asíncrono usando `CreateSaleKart21Job`
- **Webhook**: Diseñado para recibir webhooks de Kart21
- **Monedas**: USD, PAB soportadas
- **Impuestos**: Cálculo basado en tax_percent de items
- **Estados**: Solo procesa órdenes con status "CLOSED"
- **Pagos**: Soporte para efectivo, tarjetas, y otros métodos
