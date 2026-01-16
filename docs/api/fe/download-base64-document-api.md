# API Facturación Electrónica - Download Base64 Document

## Descargar Documento Base64

**Endpoint:** `GET /api/v1/fe/download_base64_document/{id}`  
**Controller:** `FeController::downloadBase64Document`  
**Request Class:** `DownloadBase64DocumentRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Descarga un documento de facturación electrónica en formato Base64 desde el proveedor PAC. Soporta facturas y notas de crédito con validación automática de parámetros.

### Parámetros de URL

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del documento en el sistema |

### Parámetros de Query String

#### Parámetros Requeridos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `path` | string | ✅ | Tipo de documento (`invoices` o `credit-notes`) |
| `cufe` | string | ✅ | CUFE del documento |
| `codigoSucursalEmisor` | string | ✅ | Código de sucursal (máx. 4 caracteres) |
| `numeroDocumentoFiscal` | integer | ✅ | Número del documento fiscal |
| `puntoFacturacionFiscal` | string | ✅ | Punto de facturación (máx. 3 caracteres) |
| `tipoDocumento` | string | ✅ | Tipo documento (`01`, `02`, `03`, `04`) |
| `tipoEmision` | string | ✅ | Tipo emisión (`01`, `02`) |

#### Parámetros Opcionales

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `transition` | string | Información adicional de transición |

### Formateo Automático

El sistema aplica formateo automático:

```php
// Auto-padding con ceros a la izquierda
'tipoDocumento' => '1' → '01'
'tipoEmision' => '2' → '02'
```

### Ejemplo de Request

```bash
GET /api/v1/fe/download_base64_document/12345?path=invoices&cufe=ABC123XYZ&codigoSucursalEmisor=001&numeroDocumentoFiscal=15&puntoFacturacionFiscal=001&tipoDocumento=01&tipoEmision=01
```

### Ejemplo con cURL

```bash
curl -X GET "http://localhost:8000/api/v1/fe/download_base64_document/12345" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -G \
  -d "path=invoices" \
  -d "cufe=ABC123XYZ456DEF" \
  -d "codigoSucursalEmisor=001" \
  -d "numeroDocumentoFiscal=15" \
  -d "puntoFacturacionFiscal=001" \
  -d "tipoDocumento=1" \
  -d "tipoEmision=1"
```

### Respuesta de Éxito (TheFactoryHKA)

```json
{
  "success": true,
  "content": "JVBERi0xLjQKJcOkw7zDtsO8CjIgMCBvYmoKPDwKL0xlbmd0aCAzIDAgUgovRmlsdGVyIC9GbGF0ZURlY29kZQo+PgpzdHJlYW0KeJzLSM3JyVcozy/KSU9NzStRyEkNyclXqOgoLU4tykvMTbVSUPBNLSguTVGwUvAA..."
}
```

### Validaciones por Tipo de Documento

#### Facturas (invoices)

| Tipo | Código | Descripción |
|------|--------|-------------|
| `01` | Factura de Venta | Documento de venta estándar |
| `02` | Factura de Exportación | Para exportaciones |
| `03` | Factura de Importación | Para importaciones |

#### Notas de Crédito (credit-notes)

| Tipo | Código | Descripción |
|------|--------|-------------|
| `04` | Nota de Crédito | Anulación o corrección |

### Tipos de Emisión

| Código | Descripción |
|--------|-------------|
| `01` | Normal | Emisión estándar |
| `02` | Contingencia | Emisión de contingencia |

### Validaciones Específicas

#### Formato de Códigos

```php
// Código de Sucursal: 4 caracteres máximo
'codigoSucursalEmisor' => '001'  // Válido
'codigoSucursalEmisor' => '12345'  // Inválido

// Punto de Facturación: 3 caracteres máximo  
'puntoFacturacionFiscal' => '001'  // Válido
'puntoFacturacionFiscal' => '1234'  // Inválido

// Número de Documento: Entero mínimo 1
'numeroDocumentoFiscal' => 15  // Válido
'numeroDocumentoFiscal' => 0   // Inválido
```

#### CUFE (Código Único de Facturación Electrónica)

- **Longitud**: Variable
- **Formato**: Alfanumérico
- **Único**: Por documento
- **Generado**: Por el PAC

### Proveedores PAC Soportados

#### TheFactoryHKA

- **Endpoint**: Servicio DescargaXML
- **Formato**: Base64 del XML
- **Método**: SOAP WebService

```php
$datos = $HKAService->createExportXML((object)[
    'codigoSucursalEmisor' => $codigoSucursalEmisor,
    'numeroDocumentoFiscal' => $numeroDocumentoFiscal,
    'puntoFacturacionFiscal' => $puntoFacturacionFiscal,
    'tipoDocumento' => $tipoDocumento,
    'tipoEmision' => $tipoEmision,
]);
```

#### Alanube

- **Estado**: En desarrollo
- **Formato**: Base64 del PDF/XML
- **Método**: REST API

### Casos de Error

#### Sin Configuración PAC

```json
{
  "success": false,
  "message": "No se encontró configuración PAC para la organización."
}
```

#### Documento No Encontrado

```json
{
  "success": false,
  "message": "No se puede descargar el documento desde la API."
}
```

#### Error de Validación

```json
{
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "tipoDocumento": [
      "El campo tipo documento es obligatorio.",
      "El campo tipo documento debe ser uno de: 01, 02, 03, 04."
    ],
    "codigoSucursalEmisor": [
      "El campo codigo sucursal emisor no puede ser mayor a 4 caracteres."
    ]
  }
}
```

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Documento descargado exitosamente |
| 400 | Error en parámetros o sin configuración PAC |
| 401 | No autorizado |
| 404 | Documento no encontrado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Casos de Uso

- **Descarga de Facturas**: PDF/XML de documentos emitidos
- **Archivos de Respaldo**: Almacenamiento local
- **Integración**: Envío por email o sistemas
- **Auditoría**: Verificación de documentos

### Formato del Contenido Base64

#### Decodificación

```javascript
// JavaScript
const binaryString = atob(response.content);
const bytes = new Uint8Array(binaryString.length);
for (let i = 0; i < binaryString.length; i++) {
    bytes[i] = binaryString.charCodeAt(i);
}
const blob = new Blob([bytes], { type: 'application/xml' });

// PHP  
$xmlContent = base64_decode($response['content']);
file_put_contents('documento.xml', $xmlContent);
```

#### Tipos de Contenido

- **XML**: Documento estructurado DGI
- **PDF**: Representación visual
- **ZIP**: Múltiples archivos (algunos PAC)

### Notas Técnicas

- **Autenticación**: Requerida en organización y PAC
- **Rate Limiting**: Aplicado según proveedor PAC
- **Cache**: Sin cache, descarga directa
- **Timeout**: Configurado por proveedor
- **Formato**: Base64 del documento original
- **Tipos MIME**: Detectados automáticamente

### Ejemplo de Implementación

```php
// Controller logic simplificado
$organization = OrganizationFacade::getOrganization();
$connection = $organization?->pacConnection;

switch ($connection->name) {
    case 'TheFactoryHKA':
        $HKAService = app()->make(HKAService::class, [
            'organization' => $organization
        ]);
        
        $export = $HKAService->wsConnExport($datos, 'DescargaXML');
        $content = $HKAService->getNestedValue(
            $export, 
            'DescargaXMLResult.documento'
        );
        
        return response()->json([
            'success' => true,
            'content' => $content
        ]);
}
```
