# API Facturación Electrónica - Integración Shopify

## Crear Venta Shopify

**Endpoint:** `POST /api/v1/fe/create_sale_shopify`  
**Controller:** `FeController::createSaleShopify`  
**Request Class:** `CreateSaleShopifyRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Shopify (órdenes de comercio electrónico).

### Estructura del Request

#### Orden Principal

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `order.id` | integer | ✅ | ID único de la orden en Shopify |
| `order.admin_graphql_api_id` | string | ✅ | ID de GraphQL API |
| `order.number` | integer | ✅ | Número de la orden |
| `order.email` | string | ✅ | Email del cliente |
| `order.created_at` | string | ✅ | Fecha de creación ISO 8601 |
| `order.currency` | string | ✅ | Moneda (USD, PAB) |
| `order.current_total_price` | string | ✅ | Total de la orden |
| `order.current_subtotal_price` | string | ✅ | Subtotal de la orden |
| `order.current_total_tax` | string | ✅ | Total de impuestos |

#### Cliente

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `order.customer.id` | integer | ✅ | ID del cliente en Shopify |
| `order.customer.email` | string | ✅ | Email del cliente |
| `order.customer.first_name` | string | ❌ | Nombre del cliente |
| `order.customer.last_name` | string | ❌ | Apellido del cliente |
| `order.customer.phone` | string | ❌ | Teléfono del cliente |

#### Dirección de Facturación

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `order.billing_address.first_name` | string | ✅ | Nombre |
| `order.billing_address.last_name` | string | ✅ | Apellido |
| `order.billing_address.company` | string | ❌ | Empresa |
| `order.billing_address.address1` | string | ✅ | Dirección línea 1 |
| `order.billing_address.address2` | string | ❌ | Dirección línea 2 |
| `order.billing_address.city` | string | ✅ | Ciudad |
| `order.billing_address.province` | string | ✅ | Provincia/Estado |
| `order.billing_address.country` | string | ✅ | País |
| `order.billing_address.zip` | string | ✅ | Código postal |
| `order.billing_address.phone` | string | ❌ | Teléfono |

### Ejemplo de Request (Basado en Test)

```json
{
  "order": {
    "id": 6185225683222,
    "admin_graphql_api_id": "gid://shopify/Order/6185225683222",
    "app_id": 1354745,
    "browser_ip": "190.219.44.79",
    "buyer_accepts_marketing": false,
    "checkout_id": 38597498962198,
    "checkout_token": "2e0ec2517fa60929a47d335f075d1b3d",
    "confirmation_number": "8JWCH5NMW",
    "confirmed": true,
    "contact_email": "larmuelles@gmail.com",
    "created_at": "2024-12-05T09:23:23-05:00",
    "currency": "USD",
    "current_subtotal_price": "300.00",
    "current_subtotal_price_set": {
      "shop_money": {
        "amount": "300.00",
        "currency_code": "USD"
      },
      "presentment_money": {
        "amount": "300.00",
        "currency_code": "USD"
      }
    },
    "current_total_discounts": "0.00",
    "current_total_price": "330.00",
    "current_total_price_set": {
      "shop_money": {
        "amount": "330.00",
        "currency_code": "USD"
      },
      "presentment_money": {
        "amount": "330.00",
        "currency_code": "USD"
      }
    },
    "current_total_tax": "30.00",
    "current_total_tax_set": {
      "shop_money": {
        "amount": "30.00",
        "currency_code": "USD"
      },
      "presentment_money": {
        "amount": "30.00",
        "currency_code": "USD"
      }
    },
    "customer_locale": "en-PA",
    "email": "larmuelles@gmail.com",
    "estimated_taxes": false,
    "financial_status": "paid",
    "fulfillment_status": null,
    "line_items": [
      {
        "id": 14972048113942,
        "admin_graphql_api_id": "gid://shopify/LineItem/14972048113942",
        "fulfillable_quantity": 1,
        "fulfillment_service": "manual",
        "fulfillment_status": null,
        "gift_card": false,
        "grams": 5000,
        "name": "Producto Ejemplo - Variante A",
        "price": "300.00",
        "price_set": {
          "shop_money": {
            "amount": "300.00",
            "currency_code": "USD"
          },
          "presentment_money": {
            "amount": "300.00",
            "currency_code": "USD"
          }
        },
        "product_exists": true,
        "product_id": 8793806913798,
        "properties": [],
        "quantity": 1,
        "requires_shipping": true,
        "sku": "PROD-EJEMPLO-001",
        "taxable": true,
        "title": "Producto Ejemplo",
        "total_discount": "0.00",
        "total_discount_set": {
          "shop_money": {
            "amount": "0.00",
            "currency_code": "USD"
          },
          "presentment_money": {
            "amount": "0.00",
            "currency_code": "USD"
          }
        },
        "variant_id": 47524892803334,
        "variant_inventory_management": "shopify",
        "variant_title": "Variante A",
        "vendor": "Mi Tienda"
      }
    ],
    "billing_address": {
      "first_name": "Luis",
      "last_name": "Armuelles",
      "company": null,
      "address1": "Calle 50, Edificio Global Bank",
      "address2": "Piso 15",
      "city": "Panama City",
      "province": "Panama",
      "country": "Panama",
      "zip": "0833",
      "phone": "+507 6789-1234",
      "name": "Luis Armuelles",
      "country_code": "PA",
      "province_code": "PA"
    },
    "shipping_address": {
      "first_name": "Luis",
      "last_name": "Armuelles",
      "company": null,
      "address1": "Calle 50, Edificio Global Bank",
      "address2": "Piso 15",
      "city": "Panama City",
      "province": "Panama",
      "country": "Panama",
      "zip": "0833",
      "phone": "+507 6789-1234",
      "name": "Luis Armuelles",
      "country_code": "PA",
      "province_code": "PA"
    }
  }
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Orden Shopify procesada exitosamente",
  "job_id": "uuid-generated-job-id",
  "order_id": 6185225683222
}
```

### Validaciones Específicas

- **Line Items**: Mínimo 1 producto requerido
- **Currency**: Solo USD y PAB soportados
- **Email**: Validación de formato email
- **Addresses**: Dirección de facturación y envío requeridas
- **Financial Status**: Debe ser "paid" para procesar

### Casos de Uso

- **E-commerce**: Órdenes de tiendas online
- **Multi-producto**: Soporte para múltiples productos por orden
- **Envíos**: Manejo de direcciones de envío diferentes
- **Descuentos**: Soporte para descuentos y promociones

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Orden procesada exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Notas Técnicas

- **Procesamiento**: Asíncrono usando `CreateSaleShopifyJob`
- **Webhook**: Diseñado para recibir webhooks de Shopify
- **Monedas**: USD, PAB soportadas
- **Impuestos**: Cálculo automático basado en ubicación
- **Inventario**: Integración con manejo de inventario Shopify
