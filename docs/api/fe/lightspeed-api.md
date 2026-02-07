# API Facturación Electrónica - Integración Lightspeed

## Crear Venta Lightspeed

**Endpoint:** `POST /api/v1/fe/create_sale_lightspeed`  
**Controller:** `FeController::createSaleLightspeed`  
**Request Class:** `CreateSaleLightspeedRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Lightspeed Retail (sistema POS y manejo de inventario).

### Configuración Previa

Antes de usar esta API, debe configurar:

1. **Lightspeed Configuration**: Configuración de conexión a Lightspeed
2. **Webhook Setup**: Configuración de webhook en Lightspeed
3. **Token Authorization**: Token de acceso válido

### Estructura del Request

#### Configuración Webhook

La API está diseñada para recibir webhooks de Lightspeed con el siguiente formato:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `retailer_id` | integer | ✅ | ID del retailer en Lightspeed |
| `active` | boolean | ✅ | Si el webhook está activo |
| `url` | string | ✅ | URL del webhook |
| `type` | string | ✅ | Tipo de evento ("sale.update") |

#### Datos de la Venta

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `sale.sale_id` | integer | ✅ | ID único de la venta |
| `sale.register_id` | integer | ✅ | ID del registro/caja |
| `sale.customer_id` | integer | ❌ | ID del cliente |
| `sale.employee_id` | integer | ✅ | ID del empleado |
| `sale.shop_id` | integer | ✅ | ID de la tienda |
| `sale.total` | string | ✅ | Total de la venta |
| `sale.subtotal` | string | ✅ | Subtotal antes de impuestos |
| `sale.tax_total` | string | ✅ | Total de impuestos |
| `sale.completed_at` | string | ✅ | Fecha de completado |

#### Items de la Venta

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `sale.items[].item_id` | integer | ✅ | ID del item |
| `sale.items[].quantity` | integer | ✅ | Cantidad vendida |
| `sale.items[].unit_price` | string | ✅ | Precio unitario |
| `sale.items[].total_price` | string | ✅ | Precio total del item |
| `sale.items[].tax_total` | string | ✅ | Impuestos del item |

### Ejemplo de Request (Basado en Webhook Lightspeed)

```json
{
  "event_type": "sale.update",
  "retailer_id": 12345,
  "data": {
    "sale": {
      "sale_id": 987654,
      "register_id": 1,
      "customer_id": 567890,
      "employee_id": 12,
      "shop_id": 1,
      "total": "125.50",
      "subtotal": "115.00",
      "tax_total": "10.50",
      "completed_at": "2025-08-12T14:30:00-05:00",
      "currency": "USD",
      "payment_method": "credit_card",
      "items": [
        {
          "item_id": 111,
          "product_id": 222,
          "quantity": 2,
          "unit_price": "25.00",
          "total_price": "50.00",
          "tax_total": "4.50",
          "item_name": "Producto A",
          "sku": "SKU-PROD-A-001"
        },
        {
          "item_id": 333,
          "product_id": 444,
          "quantity": 1,
          "unit_price": "65.00",
          "total_price": "65.00",
          "tax_total": "6.00",
          "item_name": "Producto B",
          "sku": "SKU-PROD-B-002"
        }
      ],
      "customer": {
        "customer_id": 567890,
        "first_name": "Juan",
        "last_name": "Pérez",
        "email": "juan.perez@email.com",
        "phone": "+507 6789-1234"
      },
      "payments": [
        {
          "payment_id": 789,
          "amount": "125.50",
          "payment_method": "credit_card",
          "card_type": "visa",
          "status": "approved"
        }
      ]
    }
  }
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Venta Lightspeed procesada exitosamente",
  "job_id": "uuid-generated-job-id",
  "sale_id": 987654,
  "lightspeed_config": {
    "token_status": "valid",
    "domain": "detailspanama.retail"
  }
}
```

### Configuración de Webhook en Lightspeed

Para configurar el webhook en Lightspeed, use la siguiente configuración:

```json
{
  "token": "Bearer YOUR_LIGHTSPEED_TOKEN",
  "domain": "your-domain.retail",
  "active": true,
  "url": "https://your-docucenter-domain.com/api/v1/fe/create_sale_lightspeed?token=YOUR_API_TOKEN",
  "type": "sale.update"
}
```

### Validaciones Específicas

- **Sale ID**: Debe ser único
- **Items**: Mínimo 1 item requerido
- **Employee**: Empleado debe estar registrado
- **Shop**: Tienda debe estar activa
- **Total**: Coherencia entre subtotal + impuestos = total

### Casos de Uso

- **Retail**: Ventas de tiendas físicas
- **Multi-tienda**: Soporte para múltiples ubicaciones
- **Inventory**: Integración con manejo de inventario
- **Customer Management**: Gestión de clientes integrada

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Venta procesada exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Configuración Requerida

#### Lightspeed Configuration

```php
// Modelo: Lightspeedconfiguration
'organization_id' => 'ID de la organización',
'token' => 'Token de acceso Lightspeed',
'domain' => 'Dominio del retailer (ej: detailspanama.retail)',
'webhook_url' => 'URL del webhook configurado',
'active' => true
```

### Notas Técnicas

- **Procesamiento**: Asíncrono usando `CreateSaleLightspeedJob`
- **Webhook**: Recibe webhooks tipo "sale.update"
- **Token**: Requiere Bearer token válido de Lightspeed
- **Monedas**: USD, PAB soportadas
- **Multi-tienda**: Soporte para múltiples shops por organización
- **Sincronización**: Sincronización bidireccional con inventario Lightspeed

### Testing

La API incluye métodos de testing para:
- **getWebhook()**: Verificar webhooks existentes
- **createWebhook()**: Crear nuevos webhooks
- **Validación de tokens**: Verificar tokens de acceso

### Troubleshooting

- **Token inválido**: Verificar Bearer token y permisos
- **Webhook no recibido**: Validar URL y configuración en Lightspeed
- **Items faltantes**: Verificar que productos existan en Lightspeed
- **Errores de conexión**: Verificar conectividad y dominio
