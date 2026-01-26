# API Facturación Electrónica - Integración Shopify

## Crear Venta desde Shopify

**Endpoint:** `POST /api/v1/fe/create_sale_shopify`  
**Request Class:** `CreateSaleShopifyRequest`  
**Autenticación:** Bearer Token requerido  
**Procesamiento:** Asíncrono con Job

### Características del Endpoint

- **Integración Shopify**: Específicamente diseñado para órdenes de Shopify
- **Procesamiento Asíncrono**: Utiliza `CreateSaleShopifyJob` 
- **Webhook Compatible**: Ideal para webhooks de Shopify
- **Multi-tenant**: Compatible con múltiples organizaciones
- **Logging Detallado**: Tracking con order_number específico

### Parámetros Principales de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `order_number` | string | | Número de orden de Shopify | `"SH-1001"` |
| `order_id` | integer | | ID interno de Shopify | `12345678` |
| `created_at` | string | | Fecha de creación de la orden | `"2025-08-12T10:30:00Z"` |
| `total_price` | string | | Total de la orden | `"107.00"` |
| `subtotal_price` | string | | Subtotal sin impuestos | `"100.00"` |
| `total_tax` | string | | Total de impuestos | `"7.00"` |
| `currency` | string | | Moneda de la transacción | `"PAB"` |
| `financial_status` | string | | Estado financiero | `"paid"` |
| `fulfillment_status` | string | | Estado de cumplimiento | `"fulfilled"` |

### Datos del Cliente

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `customer.id` | integer | | ID del cliente en Shopify | `67890` |
| `customer.email` | string | | Email del cliente | `"cliente@email.com"` |
| `customer.first_name` | string | | Nombre del cliente | `"María"` |
| `customer.last_name` | string | | Apellido del cliente | `"García"` |
| `customer.phone` | string | | Teléfono del cliente | `"+507 1234-5678"` |
| `customer.verified_email` | boolean | | Email verificado | `true` |

### Dirección de Facturación

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `billing_address.first_name` | string | | Nombre en dirección | `"María"` |
| `billing_address.last_name` | string | | Apellido en dirección | `"García"` |
| `billing_address.address1` | string | | Dirección línea 1 | `"Calle 50, Edificio Torre"` |
| `billing_address.address2` | string | | Dirección línea 2 | `"Piso 10, Oficina 1001"` |
| `billing_address.city` | string | | Ciudad | `"Ciudad de Panamá"` |
| `billing_address.province` | string | | Provincia | `"Panamá"` |
| `billing_address.country` | string | | País | `"PA"` |
| `billing_address.zip` | string | | Código postal | `"0000"` |
| `billing_address.phone` | string | | Teléfono | `"+507 1234-5678"` |

### Items de la Orden (Line Items)

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `line_items[].id` | integer | | ID del item en Shopify | `987654321` |
| `line_items[].product_id` | integer | | ID del producto | `123456789` |
| `line_items[].variant_id` | integer | | ID de la variante | `555666777` |
| `line_items[].title` | string | | Título del producto | `"Camiseta Premium - Talla M"` |
| `line_items[].name` | string | | Nombre completo | `"Camiseta Premium - Talla M - Azul"` |
| `line_items[].sku` | string | | SKU del producto | `"CAM-PREM-M-AZUL"` |
| `line_items[].quantity` | integer | | Cantidad | `2` |
| `line_items[].price` | string | | Precio unitario | `"35.00"` |
| `line_items[].total_discount` | string | | Descuento total | `"5.00"` |
| `line_items[].fulfillment_status` | string | | Estado de envío | `"fulfilled"` |
| `line_items[].taxable` | boolean | | Sujeto a impuestos | `true` |

### Información de Pago

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `payment_details.credit_card_bin` | string | | BIN de tarjeta | `"424242"` |
| `payment_details.credit_card_company` | string | | Compañía de tarjeta | `"Visa"` |
| `payment_details.credit_card_number` | string | | Últimos 4 dígitos | `"XXXX-XXXX-XXXX-4242"` |
| `gateway` | string | | Gateway de pago usado | `"shopify_payments"` |

### Ejemplo de Request Completo

```json
{
  "order_number": "SH-1001",
  "order_id": 12345678,
  "created_at": "2025-08-12T10:30:00Z",
  "updated_at": "2025-08-12T10:35:00Z",
  "total_price": "142.00",
  "subtotal_price": "135.00",
  "total_tax": "7.00",
  "currency": "PAB",
  "financial_status": "paid",
  "fulfillment_status": "fulfilled",
  "customer": {
    "id": 67890,
    "email": "maria.garcia@email.com",
    "first_name": "María",
    "last_name": "García",
    "phone": "+507 6789-0123",
    "verified_email": true,
    "created_at": "2024-01-15T08:00:00Z"
  },
  "billing_address": {
    "first_name": "María",
    "last_name": "García",
    "company": "Empresa García S.A.",
    "address1": "Avenida Balboa, Torre Financial Center",
    "address2": "Piso 25, Oficina 2501",
    "city": "Ciudad de Panamá",
    "province": "Panamá",
    "country": "PA",
    "zip": "0000",
    "phone": "+507 6789-0123"
  },
  "shipping_address": {
    "first_name": "María",
    "last_name": "García",
    "address1": "Calle 50, Residencial Torres",
    "address2": "Torre A, Apto 15B",
    "city": "Ciudad de Panamá",
    "province": "Panamá",
    "country": "PA",
    "zip": "0000"
  },
  "line_items": [
    {
      "id": 987654321,
      "product_id": 123456789,
      "variant_id": 555666777,
      "title": "Camiseta Premium",
      "name": "Camiseta Premium - Talla M - Color Azul",
      "sku": "CAM-PREM-M-AZUL",
      "quantity": 2,
      "price": "35.00",
      "total_discount": "0.00",
      "fulfillment_status": "fulfilled",
      "taxable": true,
      "properties": [
        {
          "name": "Color",
          "value": "Azul"
        },
        {
          "name": "Talla", 
          "value": "M"
        }
      ]
    },
    {
      "id": 987654322,
      "product_id": 123456790,
      "variant_id": 555666778,
      "title": "Pantalón Deportivo",
      "name": "Pantalón Deportivo - Talla L - Color Negro",
      "sku": "PANT-DEP-L-NEGRO",
      "quantity": 1,
      "price": "65.00",
      "total_discount": "0.00",
      "fulfillment_status": "fulfilled",
      "taxable": true
    }
  ],
  "payment_details": {
    "credit_card_bin": "424242",
    "credit_card_company": "Visa",
    "credit_card_number": "XXXX-XXXX-XXXX-4242"
  },
  "gateway": "shopify_payments",
  "source_name": "web",
  "tags": "vip,primera-compra",
  "note": "Cliente solicitó entrega rápida",
  "discount_codes": [
    {
      "code": "WELCOME10",
      "amount": "10.00",
      "type": "percentage"
    }
  ]
}
```

### Ejemplo de Request Mínimo

```json
{
  "order_number": "SH-1002",
  "order_id": 12345679,
  "created_at": "2025-08-12T11:00:00Z",
  "total_price": "53.50",
  "subtotal_price": "50.00",
  "total_tax": "3.50",
  "currency": "PAB",
  "financial_status": "paid",
  "customer": {
    "id": 67891,
    "email": "cliente@email.com",
    "first_name": "Cliente",
    "last_name": "Ejemplo"
  },
  "billing_address": {
    "first_name": "Cliente",
    "last_name": "Ejemplo",
    "address1": "Dirección de ejemplo",
    "city": "Ciudad de Panamá",
    "province": "Panamá",
    "country": "PA"
  },
  "line_items": [
    {
      "id": 987654323,
      "product_id": 123456791,
      "variant_id": 555666779,
      "title": "Producto Básico",
      "name": "Producto Básico",
      "quantity": 1,
      "price": "50.00"
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "response": "HTTP: 201 OK",
  "success": true,
  "message": "Venta Shopify enviada para procesamiento. Se procesará en segundo plano.",
  "job_id": "shopify-job-uuid-12345",
  "order_number": "SH-1001",
  "estimated_processing_time": "1-3 minutos"
}
```

### Respuestas de Error

#### Error de Validación
```json
{
  "response": "HTTP: 201 OK",
  "success": false,
  "message": "Datos de orden Shopify inválidos.",
  "errors": {
    "order_number": [
      "The order_number field is required."
    ],
    "customer.email": [
      "The customer.email field must be a valid email address."
    ],
    "line_items": [
      "At least one line item is required."
    ]
  }
}
```

#### Error de Cliente Duplicado
```json
{
  "response": "HTTP: 201 OK",
  "success": false,
  "message": "La orden Shopify ya ha sido procesada.",
  "error_code": "DUPLICATE_ORDER",
  "order_number": "SH-1001"
}
```

#### Error de Organización
```json
{
  "response": "HTTP: 201 OK", 
  "success": false,
  "message": "No se encontró organización activa para procesar la orden.",
  "error_code": "ORGANIZATION_NOT_FOUND"
}
```

### Estados Financieros Válidos

| Estado | Descripción |
|--------|-------------|
| `pending` | Pago pendiente |
| `authorized` | Pago autorizado |
| `partially_paid` | Parcialmente pagado |
| `paid` | Completamente pagado |
| `partially_refunded` | Parcialmente reembolsado |
| `refunded` | Completamente reembolsado |
| `voided` | Anulado |

### Estados de Cumplimiento

| Estado | Descripción |
|--------|-------------|
| `null` | Sin procesar |
| `partial` | Parcialmente enviado |
| `fulfilled` | Completamente enviado |

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Orden procesada exitosamente |
| 400 | Datos de orden inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 429 | Demasiadas solicitudes |
| 500 | Error interno del servidor |

### Configuración de Webhook Shopify

Para configurar el webhook en Shopify Admin:

1. **URL del Webhook**: `https://api.docucenter.com/api/v1/fe/create_sale_shopify`
2. **Evento**: `Order creation` y `Order payment`
3. **Formato**: JSON
4. **Headers**: 
   ```
   Authorization: Bearer YOUR_API_TOKEN
   Content-Type: application/json
   ```

### Ejemplos de cURL

#### Simular Webhook de Shopify
```bash
curl -X POST "https://api.docucenter.com/api/v1/fe/create_sale_shopify" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_number": "SH-1001",
    "order_id": 12345678,
    "created_at": "2025-08-12T10:30:00Z",
    "total_price": "107.00",
    "currency": "PAB",
    "customer": {
      "email": "cliente@email.com",
      "first_name": "Cliente",
      "last_name": "Ejemplo"
    },
    "line_items": [
      {
        "id": 987654321,
        "title": "Producto Test",
        "quantity": 1,
        "price": "100.00"
      }
    ]
  }'
```

### Notas Técnicas

- **Procesamiento Asíncrono**: Utiliza Jobs de Laravel para procesamiento en background
- **Logging Específico**: Incluye order_number en todos los logs para tracking
- **Webhook Compatible**: Diseñado específicamente para webhooks de Shopify
- **Multi-moneda**: Soporta diferentes monedas (PAB, USD, etc.)
- **Validación de Estados**: Valida estados financieros y de cumplimiento de Shopify
- **Mapeo Automático**: Convierte automáticamente datos de Shopify a formato DocuCenter
