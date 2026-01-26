# 🚨 Reporte de Error - API PlusMóvil Producción

**Fecha:** 2026-01-26  
**Reportado por:** DocuCenter Development Team  
**Severidad:** Alta  
**Estado:** Pendiente de Investigación

---

## 📋 Resumen Ejecutivo

El endpoint `/com-invoices` en el ambiente de **Producción** está devolviendo error 500 (Internal Server Error) en todas las variaciones de consulta probadas. El mismo endpoint funciona correctamente en el ambiente de **QA**.

---

## 🌍 Información del Ambiente

### Producción (AFECTADO)
- **Base URL:** `https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod`
- **Client ID:** `771vv2q1ararj5u1f084opsdg7`
- **User Pool:** `us-east-1_OJsrH1Z5F`
- **Usuario:** `larosemena_el_nawal`

### QA (FUNCIONANDO)
- **Base URL:** `https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa`
- **Client ID:** `7t3s7lb4tfg6ssal586l929ovl`
- **User Pool:** `us-east-1_xMaEibiru`
- **Usuario:** `larosemena_el_nawal`

---

## ✅ Estado de Autenticación

La autenticación mediante **AWS Cognito** funciona correctamente en ambos ambientes:

```json
{
  "authentication_status": "Exitoso",
  "token_obtained": true,
  "token_valid": true,
  "note": "El problema NO es de autenticación"
}
```

---

## ❌ Endpoint Afectado

```
GET /com-invoices
```

**Error Consistente:**
```json
{
  "message": "Internal server error"
}
```

**Status Code:** `500`

---

## 🧪 Casos de Prueba Ejecutados

### Test 1: Consulta Simple con Límite

**Request:**
```bash
curl -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices?limit=10&order_by=id&order_dir=desc" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "message": "Internal server error"
}
```

**Status:** ❌ Error 500

---

### Test 2: Consulta con Rango de Fechas

**Request:**
```bash
curl -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices?invoice_date_between=2025-12-01,2025-12-31" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "message": "Internal server error"
}
```

**Status:** ❌ Error 500

**Nota:** Este mismo parámetro funciona en QA

---

### Test 3: Consulta con Filtro de Monto

**Request:**
```bash
curl -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices?total_gte=100&limit=10" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "message": "Internal server error"
}
```

**Status:** ❌ Error 500

---

### Test 4: Consulta sin Parámetros

**Request:**
```bash
curl -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "message": "Internal server error"
}
```

**Status:** ❌ Error 500

---

### Test 5: Consulta de Factura Específica

**Request:**
```bash
curl -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices/1" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "message": "Internal server error"
}
```

**Status:** ❌ Error 500

---

## ✅ Comparación con QA (Funcionando)

### Ejemplo Exitoso en QA

**Request:**
```bash
curl -X GET "https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa/com-invoices?total_gte=100&limit=10&order_by=id&order_dir=desc" \
  -H "Authorization: Bearer <QA_TOKEN>" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "code": 0,
  "message": "success",
  "data": [
    {
      "id": 297,
      "invoice_number": "0",
      "invoice_date": "2025-09-02T15:47:13.000Z",
      "customer_name": "MINI MARKET MARIO",
      "customer_email": null,
      "customer_vat_number": "10-500-1400",
      "sub_total": "151.2300",
      "discount": "0.0000",
      "tax_amount": "16.1200",
      "total": "167.3500",
      "balance": "167.3500",
      "status": 1,
      "com_distributor": {
        "id": 1,
        "name": "DISTRIBUIDORA EL NAWAL S.A."
      },
      "com_branch": {
        "id": 1,
        "name": "PRINCIPAL"
      },
      "com_seller": {
        "id": 1,
        "name": "VENDEDOR GENERAL",
        "code": "VG01"
      }
    }
  ]
}
```

**Status:** ✅ 200 OK

---

## 📊 Impacto del Error

### Funcionalidades Afectadas
- ❌ Importación automática de facturas desde PlusMóvil a DocuCenter
- ❌ Sincronización de datos de facturación
- ❌ Consulta de facturas en tiempo real

### Impacto de Negocio
No es posible importar facturas desde el ambiente de producción al sistema DocuCenter.

### Usuarios Afectados
- **Cliente:** EL NAWAL
- **Organization ID:** 28
- **Connection ID:** 29

---

## 🔍 Análisis

### Observaciones Clave

1. ✅ **Autenticación:** Funciona correctamente en ambos ambientes
2. ❌ **Endpoint:** Falla consistentemente en Producción
3. ✅ **QA:** El mismo endpoint funciona en QA
4. ❌ **Consistencia:** Falla con todos los parámetros probados
5. ❌ **Error Genérico:** Solo devuelve "Internal server error" sin detalles

### Posibles Causas

1. **Base de Datos:**
   - Error de conexión a BD en producción
   - Esquema de BD diferente entre ambientes
   - Permisos insuficientes

2. **Configuración:**
   - Variables de entorno incorrectas
   - Configuración de AWS Lambda/API Gateway diferente

3. **Datos:**
   - Datos corruptos en BD de producción
   - Migración incompleta

4. **Código:**
   - Versión diferente del código entre ambientes
   - Dependencias faltantes

---

## 🛠️ Acciones Recomendadas

### Prioridad 1 - Urgente
- [ ] **Revisar logs del servidor** en ambiente de producción
- [ ] **Verificar conectividad** a base de datos de producción
- [ ] **Comparar versiones** de código entre QA y Producción

### Prioridad 2 - Alta
- [ ] **Verificar permisos** de base de datos
- [ ] **Revisar variables de entorno** en producción
- [ ] **Validar esquema de BD** entre ambientes

### Prioridad 3 - Media
- [ ] **Realizar pruebas de carga** en endpoint
- [ ] **Validar integridad de datos** en producción
- [ ] **Revisar configuración de AWS Lambda**

---

## 📎 Archivos Adjuntos

- `plusmovil-production-error-report.json` - Reporte completo en formato JSON
- `test-prod-complete.sh` - Script de pruebas ejecutadas
- Capturas de pantalla de respuestas (si disponibles)

---

## 📞 Información de Contacto

**Equipo DocuCenter**
- Email: desarrollo@docucenter.com
- Cliente Afectado: EL NAWAL
- Usuario: larosemena_el_nawal

---

## 🔄 Siguientes Pasos

1. Equipo PlusMóvil revisa logs del servidor
2. Se identifica causa raíz del error
3. Se implementa corrección en producción
4. DocuCenter valida que el endpoint funcione correctamente
5. Se reanuda importación de facturas

---

**Nota:** Este reporte fue generado automáticamente mediante pruebas exhaustivas del API. Se recomienda acceso a logs del servidor para diagnóstico detallado.
