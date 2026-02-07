# Investigación: Items/Detalles de Facturas en PlusMovil API

**Fecha:** 4 de noviembre de 2025  
**Endpoint Principal:** `/com-invoices`  
**Pregunta:** ¿Las facturas incluyen los items/líneas de detalle?

---

## Hallazgos

### NO Incluidos en `/com-invoices`

El endpoint `/com-invoices` **NO incluye** los items/detalles de las facturas en la respuesta.

**Campos disponibles en factura:**
```json
{
  "id": 303,
  "invoice_number": "0",
  "total": "4.9200",
  "sub_total": "4.5981",
  "discount": "0.0000",
  "tax_amount": "0.3200",
  "total_quantity": null,  // <- Campo existe pero null
  "com_order_id": null,
  "com_order": null
}
```

**Observaciones:**
- Tiene `total_quantity` pero generalmente es `null`
- Tiene referencia `com_order_id` pero el objeto `com_order` no incluye items
- No tiene campo `com_invoice_items`, `items`, `details`, etc.

---

## Endpoints Probados

### 1. `/com-invoice-details`
**URL:** `/com-invoice-details?com_invoice_id=303`  
**Resultado:** **Error 403** - Requiere AWS Signature (no Cognito Bearer token)

```json
{
  "message": "Invalid key=value pair (missing equal-sign) in Authorization header"
}
```

### 2. `/com-invoice-items`
**URL:** `/com-invoice-items?com_invoice_id=303`  
**Resultado:** **Error 403** - Requiere AWS Signature (no Cognito Bearer token)

```json
{
  "message": "Invalid key=value pair (missing equal-sign) in Authorization header"
}
```

### 3. `/com-orders`
**URL:** `/com-orders?id=30`  
**Resultado:** Funciona con Cognito, pero **NO incluye items**

```json
{
  "id": 30,
  "order_date": "2025-07-25T00:54:14.000Z",
  "total": "110.2500",
  "sub_total": "...",
  "tax_amount": "...",
  // ... otros campos pero NO items
}
```

---

## Conclusiones

### Situación Actual

1. **Endpoint de facturas (`/com-invoices`):**
   - Funciona con Cognito Bearer token
   - Incluye información completa de la factura (header)
   - NO incluye items/líneas de detalle

2. **Endpoints de detalles:**
   - `/com-invoice-details` - Bloqueado (requiere AWS Signature)
   - `/com-invoice-items` - Bloqueado (requiere AWS Signature)

3. **Patrón detectado:**
   - Algunos endpoints usan **Cognito** (Bearer token)
   - Otros endpoints usan **AWS Signature V4** (IAM authentication)

---

## Recomendaciones

### Opción 1: Solicitar a PlusMovil

**Contactar al equipo de PlusMovil para:**

1. Confirmar el endpoint correcto para obtener items de factura
2. Solicitar acceso/documentación para endpoints de detalles
3. Verificar si pueden habilitar autenticación Cognito para `/com-invoice-items`
4. Obtener Swagger completo con todos los endpoints disponibles

**Preguntas específicas:**
```
Hola,

Estamos integrando la API de PlusMovil y necesitamos obtener los items/detalles 
de las facturas del endpoint /com-invoices.

¿Cuál es el endpoint correcto para obtener los items de una factura?
- /com-invoice-items?
- /com-invoice-details?
- Otro endpoint?

Actualmente obtenemos error 403 en estos endpoints con nuestro token de Cognito.
¿Requieren autenticación diferente o permisos adicionales?

Gracias.
```

### Opción 2: Verificar Documentación Swagger

**Acciones:**
1. Acceder a la aplicación web de PlusMovil
2. Buscar documentación Swagger interna
3. Identificar endpoint de items/detalles
4. Verificar método de autenticación requerido

### Opción 3: Endpoint Alternativo (si existe)

**Posibles alternativas a probar cuando PlusMovil responda:**

```bash
# Opción A: Items como recurso independiente
GET /com-invoice-items?com_invoice_id=303&limit=50

# Opción B: Detalles como subrecurso
GET /com-invoices/303/items

# Opción C: Items en la orden relacionada
GET /com-orders/30/items

# Opción D: Parámetro include
GET /com-invoices?id=303&include=items

# Opción E: Expand parameter
GET /com-invoices?id=303&expand=items
```

---

## Impacto en Integración DocuCenter

### Funcionalidad Limitada Actual

**Sin acceso a items de factura:**
- No podemos sincronizar líneas de detalle (`Sales_Detail_Imp`)
- No podemos validar totales por item
- No podemos sincronizar productos vendidos
- No podemos generar reportes de productos más vendidos
- SÍ podemos sincronizar headers de factura (`Sales_Header_Imp`)
- SÍ podemos sincronizar totales generales

### Implementación Sugerida (dos fases)

**Fase 1: Sin items (implementar ahora)**
```php
// app/Services/PlusMovilInvoiceService.php
public function syncInvoiceHeaders($fromDate) {
    // Sincronizar solo headers de facturas
    // Guardar en Sales_Header_Imp
    // Marcar como "sin_detalles" temporalmente
}
```

**Fase 2: Con items (cuando se resuelva acceso)**
```php
public function syncInvoiceDetails($invoiceId) {
    // Obtener items del endpoint correcto
    // Guardar en Sales_Detail_Imp
    // Actualizar flag "sin_detalles" = false
}
```

---

## Estructura Esperada de Items

**Basado en patrones comunes de APIs similares:**

```json
{
  "code": 0,
  "message": "Items obtenidos correctamente",
  "data": [
    {
      "id": 1,
      "com_invoice_id": 303,
      "inv_product_id": 62331,
      "line_number": 1,
      "product_code": "878812446",
      "product_name": "TARJETA MAS MOVIL B/6.00",
      "quantity": 1,
      "unit_price": "6.0000",
      "discount": "0.0000",
      "tax_rate": "7.0000",
      "tax_amount": "0.3200",
      "subtotal": "4.5981",
      "total": "4.9200",
      "comments": null
    }
  ]
}
```

---

## Próximos Pasos

### Inmediatos:
1. **Contactar a PlusMovil** para confirmar endpoint de items
2. **Solicitar acceso** si requiere permisos adicionales
3. **Documentar respuesta** cuando obtengamos acceso

### Mientras tanto:
1. Implementar sincronización de **headers** de facturas
2. Guardar referencia `external_invoice_id` para asociar items después
3. Implementar flag `has_details` en Sales_Header_Imp

### Cuando se resuelva:
1.  Crear script de prueba para endpoint de items
2.  Documentar estructura completa
3.  Implementar sincronización de detalles
4.  Actualizar facturas existentes con items

---

## Scripts de Prueba (para cuando tengamos acceso)

Crear `test-com-invoice-items-endpoint.sh`:
```bash
#!/bin/bash
# Probar endpoint de items cuando tengamos acceso

TOKEN=$(cat docs/testing/.plusmovil-token-QA.txt | grep ACCESS_TOKEN | cut -d'=' -f2- | tr -d ' \n\r\t')

# Probar diferentes formatos
curl -s "https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/com-invoice-items?com_invoice_id=303" \
  -H "Authorization: Bearer ${TOKEN}" | jq '.'

curl -s "https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa/com-invoices/303/items" \
  -H "Authorization: Bearer ${TOKEN}" | jq '.'
```

---

## Resumen

| Aspecto | Estado | Acción |
|---------|--------|--------|
| **Headers de factura** | Disponible | Implementar ya |
| **Items de factura** | Bloqueado | Contactar PlusMovil |
| **Autenticación Cognito** | Funciona | Para headers |
| **Endpoint de items** |  Por confirmar | Pendiente respuesta |

**Conclusión:** Podemos implementar la sincronización de facturas (headers) ahora, pero necesitamos confirmar con PlusMovil el endpoint correcto para obtener los items/detalles de cada factura.
