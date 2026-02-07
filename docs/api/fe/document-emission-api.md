# API Facturación Electrónica - Emisión de Documentos

## Emitir Documento Fiscal

**Endpoint:** `POST /api/v1/fe/emit_json_object`  
**Request Class:** `EmitObjectRequest`  
**Autenticación:** Bearer Token requerido  
**Procesamiento:** Asíncrono con Job

### Características del Endpoint

- **Procesamiento Asíncrono**: Utiliza `EmitObjectJob` para procesamiento en segundo plano
- **Detección Automática de Ambiente**: Identifica automáticamente si es sandbox o producción
- **Validación PAC**: Verifica configuración PAC antes de procesar
- **Multi-tenant**: Compatible con múltiples organizaciones

### Validación Previa Requerida

| Validación | Descripción |
|------------|-------------|
| **Configuración PAC** | Debe existir `organization->pacConnection` |
| **Endpoint PAC** | Determina ambiente automáticamente |
| **Token Válido** | Bearer token activo requerido |

### Parámetros Principales

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `dGen` | object | | Datos generales del documento | Ver estructura detallada |
| `dGen.iAmb` | integer | | Ambiente (auto-detectado) | `1` (prod), `2` (test) |
| `dGen.dNroDF` | string | | Número del documento fiscal | `"001-001-000000123"` |
| `dGen.dPtoFac` | string | | Punto de facturación | `"001"` |
| `dGen.dSerie` | string | | Serie del documento | `"001"` |
| `dGen.dNroDocFis` | string | | Número documento fiscal | `"000000123"` |
| `dGen.dFeEm` | string | | Fecha de emisión | `"2025-08-12"` |
| `dGen.dTipoEm` | string | | Tipo de emisión | `"01"` |

### Estructura Completa del Request

```json
{
  "dGen": {
    "iAmb": 1,
    "iTpoDTE": "01",
    "dNroDF": "001-001-000000123",
    "dPtoFac": "001",
    "dSerie": "001", 
    "dNroDocFis": "000000123",
    "dFeEm": "2025-08-12",
    "dTipoEm": "01",
    "dSucEm": "0001",
    "dCoSucEm": "001"
  },
  "dDatosEmisor": {
    "dRucEm": "1234567890123",
    "dDVEm": "12",
    "dNombEm": "EMPRESA EMISORA S.A.",
    "dSucEm": "Casa Matrix",
    "dDirEm": "Calle 50, Edificio Torre",
    "dEMailEm": "facturacion@empresa.com"
  },
  "dDatosReceptor": {
    "iTipoRec": "01",
    "dRucRec": "9876543210987",
    "dDVRec": "21", 
    "dNombRec": "CLIENTE RECEPTOR S.A.",
    "dDirRec": "Avenida Balboa, Torre Financial"
  },
  "dDetalle": {
    "dDet": [
      {
        "iLinDet": 1,
        "dDesDet": "Servicio de Consultoría Empresarial",
        "dCantDet": 1,
        "dPrUniDet": 1000.00,
        "dPrTotDet": 1000.00,
        "dDescDet": 0.00,
        "dSubTot": 1000.00
      }
    ]
  },
  "dTotales": {
    "dSubTotal": 1000.00,
    "dTotDesc": 0.00,
    "dTotAcarr": 0.00,
    "dVTot": 1000.00,
    "dTotITBMS": 70.00,
    "dMontoTotal": 1070.00
  }
}
```

### Ejemplo de Request Mínimo

```json
{
  "dGen": {
    "iTpoDTE": "01",
    "dNroDF": "001-001-000000124",
    "dPtoFac": "001",
    "dSerie": "001",
    "dNroDocFis": "000000124",
    "dFeEm": "2025-08-12",
    "dTipoEm": "01"
  },
  "dDatosEmisor": {
    "dRucEm": "1234567890123",
    "dDVEm": "12",
    "dNombEm": "EMPRESA EMISORA S.A."
  },
  "dDatosReceptor": {
    "iTipoRec": "01", 
    "dRucRec": "9876543210987",
    "dDVRec": "21",
    "dNombRec": "CLIENTE RECEPTOR S.A."
  },
  "dDetalle": {
    "dDet": [
      {
        "iLinDet": 1,
        "dDesDet": "Producto de prueba",
        "dCantDet": 1,
        "dPrUniDet": 100.00,
        "dPrTotDet": 100.00
      }
    ]
  },
  "dTotales": {
    "dSubTotal": 100.00,
    "dVTot": 100.00,
    "dTotITBMS": 7.00,
    "dMontoTotal": 107.00
  }
}
```

### Detección Automática de Ambiente

El sistema detecta automáticamente el ambiente basado en el endpoint PAC:

| Endpoint PAC | Ambiente | iAmb |
|--------------|----------|------|
| `sandbox-api.alanube*` | Sandbox | `2` |
| `qa.api.edocspanama.com*` | Sandbox | `2` |
| Otros | Producción | `1` |

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Documento enviado para emisión. Se procesará en segundo plano.",
  "pac_provider": "TheFactoryHKA",
  "job_id": "emit-job-uuid-12345",
  "estimated_processing_time": "30-60 segundos"
}
```

### Respuestas de Error

#### Error de Configuración PAC
```json
{
  "success": false,
  "message": "No se encontró configuración PAC para la organización.",
  "error_code": "PAC_NOT_CONFIGURED"
}
```

#### Error de Validación
```json
{
  "success": false,
  "message": "Los datos del documento no son válidos.",
  "errors": {
    "dGen.dNroDF": [
      "El formato del número de documento fiscal es inválido."
    ],
    "dDatosEmisor.dRucEm": [
      "El RUC del emisor es requerido."
    ]
  }
}
```

#### Error Interno
```json
{
  "success": false,
  "message": "Error interno al procesar el documento.",
  "error_code": "PROCESSING_ERROR",
  "trace_id": "trace-uuid-67890"
}
```

---

## Importar Documentos XML

**Endpoint:** `POST /api/v1/fe/import_xml`  
**Request Class:** `ImportXmlRequest`  
**Autenticación:** Bearer Token requerido  
**Procesamiento:** Asíncrono con Job

### Características del Endpoint

- **Múltiples Archivos**: Soporte para subir varios archivos XML simultáneamente
- **Procesamiento Asíncrono**: Utiliza `ImportXmlJob` para procesamiento
- **Almacenamiento Seguro**: Archivos almacenados con `FileUploadService`
- **Validación XML**: Valida estructura y esquema de documentos fiscales

### Parámetros de Request

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `files` | array | | Array de archivos XML |
| `files.*` | file | | Archivo XML válido |

### Validaciones de Archivos

| Validación | Descripción |
|------------|-------------|
| **Formato** | Solo archivos .xml |
| **Tamaño** | Máximo 5MB por archivo |
| **Estructura** | XML válido y bien formado |
| **Esquema** | Compatible con esquema DGI |

### Ejemplo de Request (Multipart)

```bash
curl -X POST "https://api.docucenter.com/api/v1/fe/import_xml" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "files[]=@factura_001.xml" \
  -F "files[]=@factura_002.xml" \
  -F "files[]=@nota_credito_001.xml"
```

### Ejemplo con JavaScript (FormData)

```javascript
const formData = new FormData();
formData.append('files[]', xmlFile1);
formData.append('files[]', xmlFile2);

fetch('/api/v1/fe/import_xml', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: formData
});
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Empezando a importar archivos XML.",
  "files_count": 3,
  "job_id": "import-xml-job-uuid-54321",
  "estimated_processing_time": "2-5 minutos"
}
```

### Respuestas de Error

#### Error de Archivos
```json
{
  "success": false,
  "message": "Archivos no válidos.",
  "errors": {
    "files.0": [
      "El archivo debe ser un XML válido."
    ],
    "files.1": [
      "El archivo excede el tamaño máximo permitido."
    ]
  }
}
```

#### Error de Procesamiento
```json
{
  "success": false,
  "message": "Error al procesar archivos XML.",
  "error_code": "XML_PROCESSING_ERROR"
}
```

---

## Descargar Documento Base64

**Endpoint:** `GET /api/v1/fe/download_base64_document/{id}`  
**Request Class:** `DownloadBase64DocumentRequest`  
**Autenticación:** Bearer Token requerido

### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | integer | | ID del documento fiscal |

### Parámetros de Query

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `codigoSucursalEmisor` | string | | Código de sucursal emisora | `"001"` |
| `numeroDocumentoFiscal` | string | | Número del documento fiscal | `"000000123"` |
| `puntoFacturacionFiscal` | string | | Punto de facturación | `"001"` |
| `tipoDocumento` | string | | Tipo de documento | `"01"` |
| `tipoEmision` | string | | Tipo de emisión | `"01"` |

### PACs Soportados

| PAC | Soporte | Funcionalidad |
|-----|---------|---------------|
| **TheFactoryHKA** | Completo | Descarga XML via API |
| **alanube** |  En desarrollo | Funcionalidad básica |

### Ejemplo de Request

```bash
GET /api/v1/fe/download_base64_document/123?codigoSucursalEmisor=001&numeroDocumentoFiscal=000000123&puntoFacturacionFiscal=001&tipoDocumento=01&tipoEmision=01
```

### Respuesta de Éxito (TheFactoryHKA)

```json
{
  "success": true,
  "content": "PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN...",
  "pac_provider": "TheFactoryHKA",
  "document_type": "XML",
  "encoding": "base64"
}
```

### Respuesta de Error

#### PAC No Soportado
```json
{
  "success": false,
  "message": "No se puede descargar el documento desde la API.",
  "error_code": "PAC_NOT_SUPPORTED"
}
```

#### Documento No Encontrado
```json
{
  "success": false,
  "message": "Documento fiscal no encontrado.",
  "error_code": "DOCUMENT_NOT_FOUND"
}
```

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Documento descargado exitosamente |
| 400 | Parámetros de consulta inválidos |
| 401 | No autorizado |
| 404 | Documento no encontrado |
| 503 | Servicio PAC no disponible |

### Ejemplos de cURL

#### Emitir Documento
```bash
curl -X POST "https://api.docucenter.com/api/v1/fe/emit_json_object" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "dGen": {
      "iTpoDTE": "01",
      "dNroDF": "001-001-000000123",
      "dFeEm": "2025-08-12"
    },
    "dDatosEmisor": {
      "dRucEm": "1234567890123",
      "dNombEm": "EMPRESA EMISORA S.A."
    },
    "dDatosReceptor": {
      "dRucRec": "9876543210987",
      "dNombRec": "CLIENTE RECEPTOR S.A."
    }
  }'
```

#### Descargar Documento
```bash
curl -X GET "https://api.docucenter.com/api/v1/fe/download_base64_document/123?codigoSucursalEmisor=001&numeroDocumentoFiscal=000000123&puntoFacturacionFiscal=001&tipoDocumento=01&tipoEmision=01" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Notas Técnicas

- **Ambiente Automático**: El campo `iAmb` se establece automáticamente según el PAC
- **Validación PAC**: Se valida la existencia de configuración PAC antes del procesamiento
- **Jobs Asíncronos**: Emisión e importación utilizan procesamiento en background
- **Multi-tenant**: Compatible con arquitectura multi-organización
- **Logging**: Todas las operaciones incluyen logging detallado para auditoría
