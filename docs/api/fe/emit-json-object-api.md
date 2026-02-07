# API Facturación Electrónica - Emit JSON Object

## Emitir Objeto JSON

**Endpoint:** `POST /api/v1/fe/emit_json_object`  
**Controller:** `FeController::emitObject`  
**Request Class:** `EmitObjectRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Emite un documento de facturación electrónica usando un objeto JSON estructurado. Detecta automáticamente la configuración PAC (Proveedor Autorizado de Certificación) y el ambiente (producción/sandbox).

### Estructura del Request

#### Configuración de Emisión

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `documento` | object | ✅ | Documento a emitir |
| `ambiente` | string | ❌ | Ambiente (auto-detectado) |
| `pac_provider` | string | ❌ | Proveedor PAC (auto-detectado) |

#### Estructura del Documento

El documento debe seguir la estructura estándar de facturación electrónica panameña:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `documento.dGen` | object | ✅ | Datos generales |
| `documento.gDatRec` | object | ✅ | Datos del receptor |
| `documento.gItem` | array | ✅ | Items/productos |
| `documento.gTot` | object | ✅ | Totales |

### Ejemplo de Request Completo

```json
{
  "documento": {
    "dGen": {
      "dId": "01-001-001-00000001-01-0",
      "dFechaEm": "28/01/2025",
      "dSalCond": 1,
      "dTiOpe": 1,
      "iNatVen": 1,
      "iTipoTranVenta": 1,
      "dComentario": "Venta de productos"
    },
    "gDatRec": {
      "iNatRec": 1,
      "iTipoRec": 1,
      "cPaisRec": "PA",
      "dNombRec": "Juan Pérez",
      "dRucRec": "8-123-456",
      "dDVRec": "1",
      "dTelRec": "6612-3456",
      "dCorElecRec": "juan.perez@email.com",
      "dDirRec": "Calle 50, Ciudad de Panamá"
    },
    "gItem": [
      {
        "dSecItem": 1,
        "dCodProd": "PROD001",
        "dDesProd": "Laptop Dell Inspiron 15",
        "cUnidad": "UND",
        "dCantCodInt": 1,
        "dPrUnit": 750.00,
        "dPrUnitDesc": 750.00,
        "dPrItem": 750.00,
        "dValTotItem": 750.00
      },
      {
        "dSecItem": 2,
        "dCodProd": "SERV001",
        "dDesProd": "Servicio de instalación",
        "cUnidad": "HOR",
        "dCantCodInt": 2,
        "dPrUnit": 50.00,
        "dPrUnitDesc": 50.00,
        "dPrItem": 100.00,
        "dValTotItem": 100.00
      }
    ],
    "gTot": {
      "dSub": 850.00,
      "dDescGlobal": 50.00,
      "dVTot": 800.00,
      "dTotImp": 56.00,
      "dVTotFact": 856.00
    }
  }
}
```

### Auto-detección de Configuración

#### Proveedor PAC

El sistema detecta automáticamente el proveedor:

```json
{
  "pac_provider": "TheFactoryHKA",  // Auto-detectado
  "ambiente": "sandbox"            // Auto-detectado
}
```

#### Ambientes Soportados

- **Producción**: `production`
- **Sandbox/Pruebas**: `sandbox`, `testing`
- **Desarrollo**: `development`

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Documento enviado para emisión. Se procesará en segundo plano.",
  "pac_provider": "TheFactoryHKA"
}
```

### Respuesta de Error (Sin PAC)

```json
{
  "success": false,
  "message": "No se encontró configuración PAC para la organización."
}
```

### Validaciones Específicas

#### Configuración PAC Requerida

El sistema verifica:

- **PAC Connection**: Debe existir configuración PAC
- **Credenciales**: Token/certificados válidos
- **Endpoint**: URL del servicio PAC
- **Ambiente**: Configuración coherente

#### Validaciones de Documento

```php
// Estructura mínima requerida
$rules = [
    'documento' => 'required|array',
    'documento.dGen' => 'required|array',
    'documento.gDatRec' => 'required|array',
    'documento.gItem' => 'required|array|min:1',
    'documento.gTot' => 'required|array'
];
```

### Procesamiento Asíncrono

#### EmitObjectJob

El procesamiento incluye:

1. **Validación**: Estructura y datos
2. **Transformación**: Formato según PAC
3. **Emisión**: Envío al proveedor PAC
4. **Registro**: Almacenamiento en BD
5. **Respuesta**: Callback o webhook

#### Estados del Job

- **Dispatched**: Job enviado a cola
- **Processing**: Procesando documento
- **Emitted**: Documento emitido
- **Failed**: Error en emisión

### Proveedores PAC Soportados

#### TheFactoryHKA

```json
{
  "pac_type": "thefactoryhka",
  "endpoint": "https://api.thefactoryhka.com/v1/documents",
  "ambiente": "production"
}
```

#### Alanube

```json
{
  "pac_type": "alanube",
  "endpoint": "https://api.alanube.co/fe/v1/emit",
  "ambiente": "sandbox"
}
```

### Casos de Uso

- **Emisión Directa**: Documentos desde aplicaciones
- **Integración B2B**: Conectores entre sistemas
- **API Gateway**: Centralización de emisión
- **Microservicios**: Servicios de facturación

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Documento encolado para emisión |
| 400 | Sin configuración PAC |
| 401 | No autorizado |
| 422 | Error de validación del documento |
| 500 | Error interno del servidor |

### Ejemplos por Tipo de Documento

#### Factura de Venta (01)

```json
{
  "documento": {
    "dGen": {
      "dTipoDE": "01",
      "dSalCond": 1
    }
  }
}
```

#### Nota de Crédito (04)

```json
{
  "documento": {
    "dGen": {
      "dTipoDE": "04",
      "dMotivo": "Devolución de mercancía"
    }
  }
}
```

### Notas Técnicas

- **Formato**: JSON estándar DGI panameño
- **Procesamiento**: Asíncrono con Redis
- **PAC**: Auto-detección de proveedor
- **Ambiente**: Auto-detección según configuración
- **Validación**: Estructura y reglas de negocio
- **Logging**: Registro completo del proceso
- **Timeout**: Configurado por proveedor PAC

### Monitoreo y Debug

```bash
# Ver jobs en procesamiento
php artisan queue:work --queue=default

# Logs específicos
tail -f storage/logs/laravel.log | grep EmitObjectJob

# Estado de PAC
curl -H "Authorization: Bearer TOKEN" \
  GET /api/v1/organizations/{id}/pac-connection
```
