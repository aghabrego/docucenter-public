# API Facturación Electrónica (FE) - DocuCenter

## Introducción

Las APIs de Facturación Electrónica (FE) permiten la integración con el sistema de facturación electrónica de Panamá, facilitando la emisión de facturas electrónicas desde diferentes plataformas y sistemas ERP.

## Documentación Complementaria

- **[Sistema Tax Code QuickBooks](./quickbooks-tax-code-system.md)** - Documentación técnica del sistema híbrido de extracción de Tax Code en QuickBooks: 4 niveles de prioridad, logging detallado, prevención de loops y ejemplos prácticos

---

## Emitir Factura desde ACICloud

**Endpoint:** `POST /api/v1/fe/create_sale_acicloud`  
**Controller:** `FeController::createSaleAciCloud`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes del sistema Sage ACICloud.

### Campos de Request

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `dGen.gDatRec.iTipoRec` | string | | Tipo de receptor |
| `dGen.gDatRec.gRucRec.dTipoRuc` | string | | Tipo de RUC (default: "1") |
| `gTot.gFormaPago[].iFormaPago` | string | | Forma de pago |
| `gItem[].gITBMSItem.dTasaITBMS` | string | | Tasa de ITBMS |

### Ejemplo de Request

```json
{
  "dGen": {
    "gDatRec": {
      "iTipoRec": "01",
      "gRucRec": {
        "dTipoRuc": "1"
      }
    }
  },
  "gTot": {
    "gFormaPago": [
      {
        "iFormaPago": "01",
        "dMontoFPago": "118.00"
      }
    ]
  },
  "gItem": [
    {
      "dCodProd": "PROD001",
      "dDesProd": "Producto ejemplo",
      "dCantProd": "1.00",
      "dPrUnit": "100.00",
      "gITBMSItem": {
        "dTasaITBMS": "07"
      }
    }
  ]
}
```

---

## Emitir Factura desde Quickbooks

**Endpoint:** `POST /api/v1/fe/create_sale_quickbooks`  
**Controller:** `FeController::createSaleQuickbooks`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Quickbooks Online.

---

## Emitir Factura desde Shopify

**Endpoint:** `POST /api/v1/fe/create_sale_shopify`  
**Controller:** `FeController::createSaleShopify`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Shopify.

---

## Emitir Factura desde Lightspeed

**Endpoint:** `POST /api/v1/fe/create_sale_lightspeed`  
**Controller:** `FeController::createSaleLightspeed`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes de Lightspeed.

---

## Importar XML

**Endpoint:** `POST /api/v1/fe/import_xml`  
**Controller:** `FeController::importXml`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Importa y procesa archivos XML para generar facturas electrónicas.

---

## Emitir Objeto JSON

**Endpoint:** `POST /api/v1/fe/emit_json_object`  
**Controller:** `FeController::emitObject`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Emite una factura electrónica a partir de un objeto JSON estructurado.

---

## Descargar Documento Base64

**Endpoint:** `GET /api/v1/fe/download_base64_document/{id}`  
**Controller:** `FeController::downloadBase64Document`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Descarga un documento de factura electrónica en formato Base64.

### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | integer | | ID del documento FE |

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Factura procesada exitosamente |
| 201 | Factura creada y emitida exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Notas Técnicas

- **Formato de fechas**: ISO 8601 (YYYY-MM-DDTHH:MM:SS)
- **Monedas**: Soporte para USD y PAB
- **ITBMS**: Cálculo automático de impuestos
- **Validaciones**: Validación estricta según normativas DGI
- **Asincronía**: Procesamiento en background con Jobs
- **Multi-tenant**: Respeta el contexto de organización
