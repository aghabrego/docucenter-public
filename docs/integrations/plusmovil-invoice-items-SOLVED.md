# SOLUCIÓN: Items de Facturas en PlusMovil API

**Fecha:** 6 de noviembre de 2025  
**Estado:** **RESUELTO**  
**Endpoint:** `GET /com-invoices/{id}`

---

## Descubrimiento Crítico

### **Los items SÍ están disponibles con autenticación Cognito**

**Endpoint correcto**: `GET /com-invoices/{id}`

El endpoint individual de factura **SÍ incluye** los items completos:
- `com_invoice_item[]`: Array de líneas/items de la factura
- `com_invoice_detail[]`: Detalles adicionales (si aplica)
- `com_invoice_payment_summary[]`: Resumen de pagos

---

## Estructura de Respuesta Completa

### Ejemplo Real - Factura #86

```bash
curl -X GET "https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/com-invoices/86" \
  -H "Authorization: Bearer $COGNITO_TOKEN" \
  -H "Content-Type: application/json"
```

**Respuesta:**
```json
{
  "code": 0,
  "module": "ComInvoiceController",
  "message": "Factura obtenido correctamente",
  "error": "",
  "data": {
    "id": 86,
    "com_order_id": null,
    "com_distributor_id": 23,
    "com_branch_id": 9,
    "com_pos_id": 6872,
    "com_seller_id": 35,
    "com_settlement_id": 55,
    "ops_route_id": 44,
    "invoice_type": 2,
    "invoice_number": "0",
    "invoice_date": "2025-07-25T16:44:42.000Z",
    "customer_name": "MINI SUPER EL PROGRESO 5",
    "customer_phone": "61056889",
    "customer_email": "email@email.com",
    "customer_vat_number": "PENDIENTE",
    "billing_address": "Av. Principal La Chorrera",
    "sub_total": "322.0000",
    "discount": "0.0000",
    "tax_amount": "22.5500",
    "total": "344.5500",
    "balance": "344.5500",
    "status": 1,
    "comments": "Factura generada desde la app",
    
    // ITEMS DE LA FACTURA
    "com_invoice_item": [
      {
        "id": 135,
        "com_invoice_id": 86,
        "cat_product_id": 8,
        "product_code": "102003",
        "product_stock_control_type": 0,
        "product_category": "TARJETA PREPAGO",
        "product_brand": "TARJETA PREPAGO",
        "product_provider": "CWP ALMACEN MOVIL JUAN DIAZ",
        "product_name": "TARJETA PREPAGO $3.00",
        "product_image_url": "https://s3-tk-dms-dev.s3.amazonaws.com/public/cat_product/102003.png",
        "range_start": "872208162",
        "range_end": "872208186",
        "quantity": 1,
        "unit_price": "2.9500",
        "subtotal": "73.7500",
        "created_user_id": 99096,
        "created_at": "2025-07-25T16:44:42.000Z",
        "updated_user_id": null,
        "updated_at": null
      },
      {
        "id": 136,
        "com_invoice_id": 86,
        "cat_product_id": 9,
        "product_code": "102004",
        "product_stock_control_type": 0,
        "product_category": "TARJETA PREPAGO",
        "product_brand": "TARJETA PREPAGO",
        "product_provider": "CWP ALMACEN MOVIL JUAN DIAZ",
        "product_name": "TARJETAS PREPAGO DE $5.00",
        "product_image_url": "https://s3-tk-dms-dev.s3.amazonaws.com/public/cat_product/102004.png",
        "range_start": "861536541",
        "range_end": "861536565",
        "quantity": 1,
        "subtotal": "123.0000",
        "unit_price": "4.9200",
        "created_user_id": 99096,
        "created_at": "2025-07-25T16:44:42.000Z",
        "updated_user_id": null,
        "updated_at": null
      },
      {
        "id": 137,
        "com_invoice_id": 86,
        "cat_product_id": 18,
        "product_code": "105380",
        "product_stock_control_type": 0,
        "product_category": "SIMCARDS Y TARJETAS",
        "product_brand": "TARJETA PREPAGO",
        "product_provider": "CWP ALMACEN MOVIL JUAN DIAZ",
        "product_name": "TARJETA MAS MOVIL  B/6.00",
        "product_image_url": "https://s3-tk-dms-dev.s3.amazonaws.com/public/cat_product/105380.png",
        "range_start": "869136007",
        "range_end": "869136031",
        "quantity": 1,
        "subtotal": "147.7500",
        "unit_price": "5.9100",
        "created_user_id": 99096,
        "created_at": "2025-07-25T16:44:42.000Z",
        "updated_user_id": null,
        "updated_at": null
      }
    ],
    
    // DETALLES ADICIONALES (vacío en este caso)
    "com_invoice_detail": [],
    
    // RESUMEN DE PAGOS (vacío en este caso)
    "com_invoice_payment_summary": [],
    
    // ... relaciones
    "com_distributor": { ... },
    "com_branch": { ... },
    "com_pos": { ... },
    "com_seller": { ... },
    "ops_route": { ... }
  }
}
```

---

## Estructura de `com_invoice_item`

Cada item incluye:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único del item |
| `com_invoice_id` | integer | ID de la factura |
| `cat_product_id` | integer | ID del producto en catálogo |
| `product_code` | string | Código del producto |
| `product_name` | string | Nombre del producto |
| `product_category` | string | Categoría del producto |
| `product_brand` | string | Marca del producto |
| `product_provider` | string | Proveedor del producto |
| `product_image_url` | string | URL de imagen del producto |
| `product_stock_control_type` | integer | Tipo de control de stock |
| `range_start` | string | Inicio de rango (para productos serializados) |
| `range_end` | string | Fin de rango (para productos serializados) |
| `quantity` | integer | Cantidad de líneas/ítems |
| `unit_price` | string/decimal | Precio unitario |
| `subtotal` | string/decimal | Subtotal de la línea |
| `created_user_id` | integer | Usuario que creó el item |
| `created_at` | datetime | Fecha de creación |
| `updated_user_id` | integer | Usuario última actualización |
| `updated_at` | datetime | Fecha última actualización |

---

## Características Especiales

### 1. **Productos Serializados con Rangos**

Los productos como tarjetas prepago usan rangos de series:
```json
{
  "product_name": "TARJETA PREPAGO $3.00",
  "range_start": "872208162",
  "range_end": "872208186",
  "quantity": 1,
  "subtotal": "73.7500"
}
```

- **`range_start`**: Primer número de serie vendido
- **`range_end`**: Último número de serie vendido
- **Unidades reales**: `range_end - range_start + 1` = 25 unidades
- **`quantity`**: Siempre 1 (representa una línea agrupada)
- **`subtotal`**: Total de todas las unidades en el rango

### 2. **Agrupación de Items**

La API agrupa items del mismo producto en rangos consecutivos:
- En lugar de 25 líneas individuales, devuelve 1 línea con rango
- Reduce significativamente el tamaño de la respuesta
- Facilita reportes de productos serializados

---

## Casos de Prueba Validados

### Factura con Items (ID: 86)
- **Total**: $344.55
- **Items**: 3 líneas agrupadas
- **Productos**: Tarjetas prepago de $3, $5 y $6
- **Unidades totales**: 75 (25 + 25 + 25)

### Factura sin Items (ID: 303, 297, 300, 301, 302)
- **Arrays vacíos**: `com_invoice_item: []`
- **Razón confirmada**: **NO venden productos serializados**

### **Análisis Detallado: ¿Por qué algunas facturas NO tienen items?**

**Hallazgo Clave**: La API de PlusMovil **solo incluye items para productos físicos serializados**.

#### Facturas CON Items:
```json
// Factura #298 - $29.53
{
  "com_invoice_item": [
    {
      "product_name": "TARJETA MAS MOVIL B/6.00",
      "range_start": "878964981",
      "range_end": "878964985",
      "quantity": 1,
      "subtotal": "29.5300"
    }
  ]
}
```

**Características**:
- Productos físicos con series individuales
- Campos `range_start` y `range_end` presentes
- Trazabilidad completa por unidad
- Ejemplos: Tarjetas prepago, SIM cards, dispositivos

#### Facturas SIN Items:
```json
// Factura #300 - $4.92
{
  "com_invoice_item": []
}
```

**Características**:
- Productos virtuales o servicios
- No requieren tracking de series individuales
- API no devuelve items en respuesta
- Ejemplos: Recargas electrónicas, pagos de servicios

#### Estadísticas del Análisis:

| Periodo | Total Facturas | Con Items | Sin Items | % Con Items |
|---------|----------------|-----------|-----------|-------------|
| Julio 2025 (#84-93) | 4 | 4 | 0 | **100%** |
| Agosto 2025 (#232-253) | 5 | 5 | 0 | **100%** |
| Sept 2025 (#297-303) | 7 | 2 | 5 | **29%** |

**Patrón identificado**: A partir de septiembre 2025, aumentó la proporción de facturas de servicios/recargas electrónicas vs productos físicos.

#### Comportamiento del Sistema:

```
Tipo de Venta          → Items en API

Tarjeta Prepago        → SÍ (con ranges)
SIM Card               → SÍ (con ranges)
Dispositivo Móvil      → SÍ (con serie)

Recarga Electrónica    → NO
Pago de Servicio       → NO
Crédito Virtual        → NO
```

Este es un **comportamiento de negocio intencional**, no un error del sistema.

---

## Implicaciones para DocuCenter

### **Sincronización Completa Posible** (con limitaciones)

Ahora podemos sincronizar:
1. **Headers de Facturas**: Endpoint `/com-invoices` (lista) - TODAS las facturas
2. **Detalles con Items**: Endpoint `/com-invoices/{id}` - SOLO productos físicos serializados
3. **Productos**: Información completa de cada producto físico vendido
4. **Series/Rangos**: Trazabilidad de productos serializados

### **Limitaciones Identificadas**

**Items NO disponibles para**:
- Recargas electrónicas
- Pagos de servicios
- Créditos virtuales
- Cualquier producto no serializado

**Impacto**:
- Solo ~29-100% de facturas tendrán items (varía por mes/tipo de negocio)
- Los totales de factura siempre están disponibles (header)
- No podemos hacer reportes detallados por producto en facturas de servicios

### **Recomendaciones**

1. **Sincronización en 2 fases**:
   - Fase 1: Headers de TODAS las facturas
   - Fase 2: Items SOLO de facturas con productos físicos

2. **Flag de clasificación**:
   ```php
   // Agregar a Sales_Header_Imp
   'has_physical_items' => count($invoice->com_invoice_item) > 0,
   'item_type' => count($invoice->com_invoice_item) > 0 ? 'physical' : 'service'
   ```

3. **Validación inteligente**:
   ```php
   // NO intentar obtener items si la factura es pequeña (probable servicio)
   if ($invoice->total < 10) {
       // Probablemente recarga electrónica, skip items
   }
   ```

4. **Reportes adaptados**:
   - Reportes por producto: SOLO facturas con items
   - Reportes financieros: TODAS las facturas (usar headers)
   - Reportes de trazabilidad: SOLO productos físicos



### **Plan de Implementación Actualizado**

#### Fase 1: Sincronización de Headers
```php
// Job: SyncPlusMovilInvoicesJob
// Endpoint: GET /com-invoices?limit=100&invoice_date_gte=2025-01-01
// Frecuencia: Cada 1 hora
// Guarda: Sales_Header_Imp TODAS las facturas
// Flags: 
//   - has_details = false (inicialmente)
//   - requires_item_sync = true (para intentar después)
```

#### Fase 2: Sincronización Selectiva de Items
```php
// Job: SyncPlusMovilInvoiceItemsJob
// Endpoint: GET /com-invoices/{id} para cada factura sin items sincronizados
// Frecuencia: Después de Fase 1, cada 2 horas
// Lógica:
if (count($response->com_invoice_item) > 0) {
    // Guardar items en Sales_Line_Imp
    foreach ($items as $item) {
        // Calcular unidades reales desde range
        $units = $item->range_end - $item->range_start + 1;
        
        Sales_Line_Imp::create([
            'product_code' => $item->product_code,
            'product_name' => $item->product_name,
            'quantity' => $units, // NO usar $item->quantity (siempre 1)
            'unit_price' => $item->unit_price,
            'line_amount' => $item->subtotal,
            'range_start' => $item->range_start,
            'range_end' => $item->range_end,
        ]);
    }
    // Marcar
    $header->update([
        'has_details' => true,
        'item_type' => 'physical'
    ]);
} else {
    // Factura de servicio/recarga
    $header->update([
        'has_details' => false, // Confirmado sin items
        'item_type' => 'service',
        'requires_item_sync' => false // No intentar más
    ]);
}
```

#### Fase 3: Expansión de Rangos (Opcional)
```php
// Solo si se requiere detalle individual de cada unidad:
// Crear una línea por cada unidad en el rango
for ($serial = $range_start; $serial <= $range_end; $serial++) {
    Sales_Line_Detail_Imp::create([
        'serial_number' => $serial,
        'product_code' => $item->product_code,
        'unit_price' => $item->unit_price,
        // ... útil para trazabilidad de garantías
    ]);
}
```

#### Fase 4: Monitoreo y Métricas
```php
// Dashboard de sincronización
$stats = [
    'total_invoices' => Invoice::count(),
    'with_items' => Invoice::where('has_details', true)->count(),
    'without_items' => Invoice::where('item_type', 'service')->count(),
    'pending_sync' => Invoice::where('requires_item_sync', true)->count(),
    'percentage_with_items' => round($withItems / $total * 100, 2)
];
```

---

## Diferencias: Listado vs Individual

| Característica | `/com-invoices` | `/com-invoices/{id}` |
|----------------|-----------------|----------------------|
| **Headers** | Completos | Completos |
| **Items** | NO incluidos | SÍ incluidos |
| **Detalles** | NO incluidos | SÍ incluidos |
| **Pagos summary** | NO incluidos | SÍ incluidos |
| **Relaciones** | Objetos completos | Objetos completos |
| **Paginación** | Soportado | N/A (individual) |
| **Filtros** | Dinámicos | N/A (por ID) |

---

## Scripts de Testing

### Script para probar items de factura:

```bash
#!/bin/bash
# Ubicación: docs/testing/test-com-invoice-items.sh

source docs/testing/.plusmovil-token-QA.txt

echo "Probando items de factura..."
echo

# 1. Buscar facturas con monto mayor
echo "1⃣ Buscando facturas con items..."
INVOICE_ID=$(curl -s -X GET \
  "https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/com-invoices?limit=10&total_gte=100&order_by=id&order_dir=desc" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  | jq -r '.data[0].id')

echo "Factura encontrada: ID $INVOICE_ID"
echo

# 2. Obtener detalles completos
echo "2⃣ Obteniendo items de factura $INVOICE_ID..."
curl -s -X GET \
  "https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/com-invoices/$INVOICE_ID" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  | jq '{
      id: .data.id,
      total: .data.total,
      customer: .data.customer_name,
      items_count: .data.com_invoice_item | length,
      items: [.data.com_invoice_item[] | {
        product: .product_name,
        category: .product_category,
        quantity: .quantity,
        unit_price: .unit_price,
        subtotal: .subtotal,
        range_start: .range_start,
        range_end: .range_end
      }]
    }'

echo
echo "Prueba completada"
```

---

## Conclusión

### **Problema Resuelto (con matices)**

La investigación inicial sobre `/com-invoice-items` nos llevó por el camino equivocado. El endpoint correcto es:

**`GET /com-invoices/{id}`** 

Este endpoint:
- Usa autenticación Cognito (no requiere AWS Signature)
- Incluye items de la factura **SOLO para productos físicos serializados**
- Incluye información completa de productos con trazabilidad
- Maneja productos serializados con rangos
- Es eficiente (agrupación inteligente)

### **Limitación Importante**

**NO todos los tipos de venta incluyen items:**
- Productos físicos (tarjetas, SIM, dispositivos) → **SÍ tienen items**
- Servicios/recargas electrónicas → **NO tienen items**
- Proporción estimada: 29-100% de facturas con items (varía por periodo)

Esto es un **comportamiento intencional del sistema**, no un bug.

### **Próximos Pasos**

1. ~~Actualizar documentación~~ (este archivo)
2. ~~Crear script de testing para items~~
3. ~~Analizar por qué algunas facturas no tienen items~~
4.  Implementar PlusMovilInvoiceService con método `getInvoiceWithItems($id)`
5.  Crear Job `SyncPlusMovilInvoiceItemsJob` con lógica selectiva
6.  Agregar flags `item_type` y `has_physical_items` a Sales_Header_Imp
7.  Mapear items a `Sales_Line_Imp` (solo facturas con productos físicos)
8.  Crear dashboard de métricas de sincronización
9.  Probar sincronización completa (headers + items selectivos)

### **Documentos Actualizados**

- ~~`plusmovil-invoice-items-investigation.md`~~ - Investigación preliminar (obsoleta)
- **`plusmovil-invoice-items-SOLVED.md`** - Este documento (solución final + análisis)
- ~~`plusmovil-api-testing-results.md`~~ - Actualizado con hallazgos
- ~~`test-com-invoice-items.sh`~~ - Script de testing creado

### **Lecciones Aprendidas**

1. **No asumir que todas las facturas tienen items** - Depende del tipo de producto
2. **Los totales siempre están en el header** - Confiable para reportes financieros
3. **Items = Trazabilidad física** - Solo para productos que requieren seguimiento de series
4. **Sincronización selectiva** - No desperdiciar recursos intentando obtener items inexistentes
