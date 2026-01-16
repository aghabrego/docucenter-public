# API Facturación Electrónica - Import XML

## Importar Archivo XML

**Endpoint:** `POST /api/v1/fe/import_xml`  
**Controller:** `FeController::importXml`  
**Request Class:** `ImportXmlRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Permite importar archivos XML de facturación electrónica para procesamiento masivo. Los archivos se almacenan y procesan de forma asíncrona.

### Estructura del Request

#### Multipart Form Data

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `files[]` | file | ✅ | Uno o más archivos XML/TXT |

### Validaciones de Archivo

#### Tipos de Archivo Permitidos
- **XML**: `.xml`
- **TXT**: `.txt`

#### Restricciones
- **Tamaño máximo**: 2048 KB (2MB) por archivo
- **Múltiples archivos**: Soportado
- **MIME Types**: 
  - `text/xml`
  - `application/xml`
  - `text/plain`

### Ejemplo de Request (cURL)

```bash
curl -X POST "http://localhost:8000/api/v1/fe/import_xml" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "files[]=@/path/to/factura1.xml" \
  -F "files[]=@/path/to/factura2.xml" \
  -F "files[]=@/path/to/datos.txt"
```

### Ejemplo de Request (JavaScript/Axios)

```javascript
const formData = new FormData();
formData.append('files[]', xmlFile1);
formData.append('files[]', xmlFile2);
formData.append('files[]', txtFile);

const response = await axios.post('/api/v1/fe/import_xml', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
    'Authorization': `Bearer ${token}`
  }
});
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Empezando a importar archivos XML."
}
```

### Respuesta de Error (Validación)

```json
{
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "files.0": [
      "El archivo debe ser de tipo: xml, txt."
    ],
    "files.1": [
      "El archivo no puede ser mayor a 2048 kilobytes."
    ]
  }
}
```

### Procesamiento Asíncrono

#### ImportXmlJob

El procesamiento se realiza mediante `ImportXmlJob` que:

1. **Almacena** los archivos en el sistema
2. **Valida** el contenido XML/TXT
3. **Extrae** la información de facturación
4. **Procesa** los datos según el formato
5. **Registra** en la base de datos

#### Estados de Procesamiento

- **Encolado**: Archivo recibido y en cola
- **Procesando**: Extrayendo datos del archivo
- **Completado**: Datos importados exitosamente
- **Error**: Problema en el procesamiento

### Formatos de Archivo Soportados

#### XML de Facturación Electrónica

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rFE xmlns="https://dgi-fep.mef.gob.pa">
  <dGen>
    <dId>01-001-001-00000001-01-0</dId>
    <dFechaEm>28/01/2025</dFechaEm>
    <dSalCond>1</dSalCond>
  </dGen>
  <gDatRec>
    <dNombRec>Cliente Ejemplo</dNombRec>
    <dRucRec>8-123-456</dRucRec>
  </gDatRec>
  <!-- Más elementos según estándar DGI -->
</rFE>
```

#### TXT Estructurado

```text
FACTURA|001|28/01/2025|Cliente|8-123-456|100.00
ITEM|PROD001|Producto 1|1|50.00
ITEM|PROD002|Producto 2|2|25.00
TOTAL|100.00|7.00|107.00
```

### Casos de Uso

- **Migración de Datos**: Importar facturas de otros sistemas
- **Procesamiento Masivo**: Cargar múltiples documentos
- **Integración Legacy**: Sistemas que exportan XML/TXT
- **Respaldo**: Restaurar facturas desde archivos

### Validaciones Específicas

#### Estructura XML
- **Namespace**: Debe coincidir con estándar DGI
- **Elementos**: Validación de elementos requeridos
- **Formato**: Fechas, números y códigos válidos

#### Contenido TXT
- **Delimitador**: Pipe (|) o coma (,)
- **Estructura**: Filas con formato consistente
- **Datos**: Validación de tipos de datos

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Archivos encolados para procesamiento |
| 400 | Tipo de archivo no válido |
| 401 | No autorizado |
| 413 | Archivo demasiado grande |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Notas Técnicas

- **Almacenamiento**: Archivos se guardan temporalmente
- **Procesamiento**: Asíncrono usando Redis queues
- **Seguridad**: Validación de tipos MIME
- **Límites**: 2MB por archivo, sin límite de cantidad
- **Cleanup**: Archivos temporales se eliminan después del procesamiento
- **Logging**: Registro detallado del proceso de importación

### Monitoreo del Proceso

Para verificar el estado del procesamiento, se puede consultar:

```bash
# Ver jobs en cola
php artisan queue:work --queue=default

# Ver logs
tail -f storage/logs/laravel.log | grep ImportXmlJob
```
