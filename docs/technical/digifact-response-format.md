# Formato de Respuesta Digifact PAC - Panamá

## Overview

Digifact puede retornar respuestas en dos formatos:
1. **JSON** (más común para errores y certificaciones modernas)
2. **XML** (formato alternativo)

El servicio **DigifactService.php** detecta automáticamente el formato y lo procesa.

## Estructura de Respuesta Exitosa

### JSON (Exitosa - Código 1 o 200)

```json
{
  "codigo": "1",
  "mensaje": "Certificación exitosa",
  "descripcion": "Documento certificado correctamente",
  "CUFE": "FE0120000155704849-2-2021320001...",
  "pdf_base64": "JVBERi0xLjQK...",
  "xml_base64": "PD94bWwgdmVyc2lvbj0iMS4wIj8+...",
  "linkQR": "https://dgi-fep.mef.gob.pa:40001/...",
  "otros_campos": "..."
}
```

**Campos principales:**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo` | string | "1" = éxito, "200" = éxito alternativo |
| `mensaje` | string | Mensaje descriptivo del resultado |
| `descripcion` | string | Descripción adicional (opcional) |
| `CUFE` | string | Código Único de Factura Electrónica |
| `pdf_base64` | string | PDF certificado en base64 |
| `xml_base64` | string | XML certificado en base64 |
| `linkQR` | string | Enlace al código QR (opcional) |

### XML (Exitosa - Código 1 o 200)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <codigo>1</codigo>
    <mensaje>Certificación exitosa</mensaje>
    <descripcion>Documento certificado correctamente</descripcion>
    <CUFE>FE0120000155704849-2-2021320001...</CUFE>
    <pdf_base64>JVBERi0xLjQK...</pdf_base64>
    <xml_base64>PD94bWwgdmVyc2lvbj0iMS4wIj8+...</xml_base64>
</response>
```

---

## Estructura de Respuesta de Error

### JSON (Error)

```json
{
  "codigo": "9026",
  "mensaje": "Error de validación",
  "descripcion": "FE_ValidacionUsoContenedor: El campo EnvioContenedor contiene un valor no permitido",
  "status": 400,
  "error_code": "3000"
}
```

**Códigos de Error Comunes:**

| Código | Mensaje | Causa |
|--------|---------|-------|
| 9026 | FE_ValidacionUsoContenedor | EnvioContenedor inválido (debe ser 3 para electrónico) |
| 9000 | Error de validación XML | XML mal formado o campos requeridos faltantes |
| 9001 | Error de autenticación | Token inválido o expirado |
| 9002 | Error de autorización | Usuario sin permisos para este documento |
| 3000 | FE_ValidacionUsoContenedor | Validación fallida del contenedor |
| 401 | Unauthorized | Token ausente o inválido |

### XML (Error)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <codigo>9026</codigo>
    <mensaje>Error de validación</mensaje>
    <descripcion>FE_ValidacionUsoContenedor: El campo EnvioContenedor contiene un valor no permitido</descripcion>
</response>
```

---

## Procesamiento de Respuesta en DocuCenter

### DigifactService.php - Método certifyDocument()

```php
/**
 * La respuesta se procesa así:
 * 
 * 1. Detectar formato (JSON o XML)
 * 2. Parsear respuesta
 * 3. Extraer campos clave: codigo, mensaje, descripcion, CUFE
 * 4. Validar código de éxito (1 o 200)
 * 5. Retornar estructura unificada
 */

// Respuesta exitosa retornada por DigifactService
return [
    'success' => true,
    'codigo' => '1',
    'mensaje' => 'Certificación exitosa',
    'cufe' => 'FE0120000155704849-2-2021320001...',
    'pdf_base64' => '...',
    'xml_base64' => '...',
    'qr_code' => 'https://...',  // JSON solamente
    'data' => [
        // Respuesta original de Digifact
    ],
];

// Respuesta de error retornada por DigifactService
return [
    'success' => false,
    'error' => 'Error en certificación',
    'status' => 400,
    'codigo' => '9026',
    'mensaje' => 'FE_ValidacionUsoContenedor',
    'descripcion' => 'El campo EnvioContenedor contiene un valor no permitido',
    'data' => [
        // Respuesta original de Digifact
    ],
];
```

---

## Detalles de Campos de Respuesta

### CUFE (Código Único de Factura Electrónica)

Formato: `FE{iDoc}{dTipoRuc}{dRuc}-{dDV}{dSucEm}{dFechaEm}{dNroDF}{dSeg}`

Ejemplo: `FE0120000155704849-2-20213000120250130001000123456789`

**Componentes:**
- `FE` - Prefijo literal
- `01` - Tipo de documento (01-09)
- `2` - Tipo de RUC (1=Natural, 2=Jurídico)
- `0000155704849` - RUC (13 dígitos)
- `-28` - Dígito verificador
- `0000` - Sucursal (4 dígitos)
- `20250130` - Fecha emisión (YYYYMMDD)
- `0001000000` - Número de factura (10 dígitos)
- `123456789` - Código de seguridad (9 dígitos)

### PDF y XML Base64

Los campos `pdf_base64` y `xml_base64` contienen:
- **pdf_base64**: PDF del documento certificado (decodificable con base64_decode())
- **xml_base64**: XML firmado y certificado por Digifact

```php
// Para decodificar
$pdfContent = base64_decode($response['pdf_base64']);
file_put_contents('documento.pdf', $pdfContent);
```

---

## Códigos de Estado HTTP

| Status | Significado | Notas |
|--------|------------|-------|
| 200 | OK - Certificación exitosa | `codigo` será 1 o 200 |
| 201 | Created | Documento creado exitosamente |
| 400 | Bad Request | Validación fallida, revisar `descripcion` |
| 401 | Unauthorized | Token inválido, renovar con authenticate() |
| 403 | Forbidden | Usuario sin permisos |
| 404 | Not Found | Endpoint no existe |
| 500 | Internal Server Error | Error en servidor Digifact |
| 503 | Service Unavailable | Digifact fuera de servicio |

---

## Ambiente de Pruebas vs Producción

### Respuesta Diferida en Pruebas

En ambiente de pruebas (`testnucpa.digifact.com`):
- La respuesta puede ser más lenta (hasta 30 segundos)
- El CUFE puede ser un UUID temporal
- Los campos `pdf_base64` y `xml_base64` pueden estar vacíos en algunos casos

### Ambiente de Producción

En ambiente de producción (`nucpa.digifact.com`):
- Respuesta más rápida (típicamente 2-5 segundos)
- CUFE formato estándar definitivo
- Documentos completamente procesados

---

## Manejo de Errores en CreateFast.php

```php
// En el componente Livewire CreateFast
$result = $digifactService->certifyDocument($documentData);

if (!$result['success']) {
    // Error en certificación
    Log::error('Certificación fallida:', [
        'codigo' => $result['codigo'],
        'mensaje' => $result['mensaje'],
        'descripcion' => $result['descripcion'],
    ]);
    
    // Mostrar al usuario
    $this->dispatch('notify', [
        'type' => 'error',
        'message' => 'Error: ' . $result['mensaje'],
        'details' => $result['descripcion'],
    ]);
    
    return;
}

// Éxito
Log::info('Documento certificado', ['cufe' => $result['cufe']]);
```

---

## Debugging de Respuestas

Para ver respuestas completas en los logs:

```bash
# Ver últimos logs de Digifact
tail -100 storage/logs/laravel.log | grep -i digifact

# Ver errores específicos
grep -A5 "Error al certificar" storage/logs/laravel.log
```

Buscar en logs:
- `Digifact - Respuesta HTTP recibida` - Respuesta HTTP raw
- `Error al certificar documento con Digifact` - Detalles de error
- `Documento certificado exitosamente` - Éxito con CUFE

