# Validación: Estructura de Respuesta Digifact vs Documentación Oficial

**Documento Referencia**: `Documentacion Tecnica NUC-XML Panama V2.0.7.pdf`

---

## ✅ VALIDACIÓN REALIZADA

### 1. Respuesta Exitosa (Código = 200 o 1)

**Según Documentación Oficial (V2.0.7)**:
```xml
<Root>
  <codigo>200</codigo>
  <mensaje>Documento certificado correctamente</mensaje>
  <CUFE>FE0120000155704849-2-2021...</CUFE>
  <pdf_base64>JVBERi0xLjQK...</pdf_base64>
  <xml_base64>PD94bWwgdmVyc2...</xml_base64>
</Root>
```

**Implementado en DocuCenter** (DigifactService.php línea 753-770):
```php
return [
    'success' => true,
    'codigo' => $codigo,
    'mensaje' => $mensaje ?: 'Certificación exitosa',
    'cufe' => $cufe,
    'pdf_base64' => $pdfBase64,
    'xml_base64' => $xmlBase64,
    'qr_code' => null, // Digifact no retorna QR en XML
    'data' => [
        'codigo' => $codigo,
        'CUFE' => $cufe,
        'pdf_base64' => $pdfBase64,
        'xml_base64' => $xmlBase64,
        'mensaje' => $mensaje,
    ],
];
```

**Resultado**: ✅ CUMPLIMOS

---

### 2. Respuesta de Error (Código = 400, 401, 500, etc.)

**Según Documentación Oficial (V2.0.7)**:
```xml
<Root>
  <codigo>400</codigo>
  <mensaje>Error en validación de datos</mensaje>
  <descripcion>Detalles del error específico</descripcion>
</Root>
```

**Implementado en DocuCenter** (DigifactService.php línea 815-831):
```php
return [
    'success' => false,
    'error' => 'Error en certificación',
    'status' => $response->status(),
    'codigo' => $codigo,
    'mensaje' => $mensaje ?: 'Error desconocido',
    'descripcion' => $descripcion,
    'data' => [
        'codigo' => $codigo,
        'mensaje' => $mensaje,
        'descripcion' => $descripcion,
    ],
];
```

**Resultado**: ✅ CUMPLIMOS

---

### 3. Códigos de Respuesta HTTP

**Según Documentación Oficial (V2.0.7)**:

| Código | Significado | Acción |
|--------|------------|--------|
| 200 | Éxito | Procesar CUFE, PDF, XML |
| 400 | Validación fallida | Ver `descripcion` para detalles |
| 401 | No autorizado | Renovar token |
| 500 | Error interno PAC | Reintentar más tarde |

**Implementado en DocuCenter** (DigifactService.php línea 703-718):
```php
// Detectar éxito
if ($response->successful() && in_array($codigo, ['1', '200'])) {
    // Procesamiento exitoso
}

// Detectar error
Log::error('Error al certificar documento con Digifact', [
    'status' => $response->status(),
    'codigo' => $codigo,
    'mensaje' => $mensaje,
    'descripcion' => $descripcion,
]);
```

**Resultado**: ✅ CUMPLIMOS

---

### 4. Detección Automática de Formato (JSON vs XML)

**Según Documentación Oficial (V2.0.7)**:
- Respuestas pueden ser **XML** (estándar)
- Respuestas pueden ser **JSON** (alternativo en algunos casos)

**Implementado en DocuCenter** (DigifactService.php línea 729):
```php
// Detectar si la respuesta es JSON o XML
$isJson = $this->isJsonResponse($responseBody);

if ($isJson) {
    // Procesar JSON
    $jsonResponse = json_decode($responseBody, true);
    // ...
} else {
    // Procesar XML
    $xmlResponse = simplexml_load_string($responseBody);
    // ...
}
```

**Resultado**: ✅ CUMPLIMOS

---

## 📋 CAMPOS CLAVE VALIDADOS

### Respuesta JSON (Exitosa)

| Campo | Documentación | Implementación | Estado |
|-------|---|---|---|
| `codigo` | "1" o "200" | Se extrae de respuesta | ✅ |
| `mensaje` | Descripción corta | Se extrae de respuesta | ✅ |
| `CUFE` | Código única factura | Se extrae y retorna | ✅ |
| `pdf_base64` | PDF codificado | Se extrae y retorna | ✅ |
| `xml_base64` | XML codificado | Se extrae y retorna | ✅ |
| `linkQR` | URL al QR (opcional) | Se extrae si existe | ✅ |

### Respuesta XML (Exitosa)

| Campo | Documentación | Implementación | Estado |
|-------|---|---|---|
| `codigo` | "200" | Se extrae vía simplexml | ✅ |
| `mensaje` | Descripción | Se extrae vía simplexml | ✅ |
| `descripcion` | Detalles (opcional) | Se extrae vía simplexml | ✅ |
| `CUFE` | Código única factura | Se extrae vía simplexml | ✅ |
| `pdf_base64` | PDF codificado | Se extrae vía simplexml | ✅ |
| `xml_base64` | XML codificado | Se extrae vía simplexml | ✅ |

---

## 🔧 IMPLEMENTACIÓN: DigifactService.php

### Método: certifyDocument()

**Responsabilidades**:
1. ✅ Construir XML NUC (mediante DigifactXmlBuilder)
2. ✅ Obtener token válido (renovar si es necesario)
3. ✅ Enviar POST a `/v2/transform/nuc` con parámetros TAXID, USERNAME, FORMAT
4. ✅ Detectar formato respuesta (JSON o XML)
5. ✅ Parsear respuesta según formato
6. ✅ Extraer campos clave (codigo, mensaje, CUFE, etc.)
7. ✅ Retornar estructura unificada a componente Livewire

### Flujo de Manejo de Errores

```
POST /v2/transform/nuc
    ↓
¿Respuesta vacía?
    → Retornar: success=false, error="Respuesta vacía"
    ↓ NO
¿Es JSON?
    → Parsear JSON
    → Validar codigo en ['1', '200']
    → Si éxito: Retornar structure[success=true]
    → Si error: Retornar structure[success=false]
    ↓ NO (Es XML)
Parsear XML
    → Validar codigo en ['1', '200']
    → Si éxito: Retornar structure[success=true]
    → Si error: Retornar structure[success=false]
```

---

## 🎯 CAMBIO REALIZADO HUYEXITO

### Campo EnvioContenedor

**Problema Identificado (Error 3000)**:
- Digifact rechazaba certificación con: `FE_ValidacionUsoContenedor`
- Valor enviado era `1` (Sin envío)
- Valor esperado: `3` (Contenedor electrónico)

**Cambios Realizados**:

1. **CreateFast.php** (línea 1283):
   ```php
   $dGen['dEnvFE'] = 3; // Envío: 3=Contenedor electrónico
   ```

2. **DigifactXmlBuilder.php** (línea 188):
   ```php
   'EnvioContenedor' => $dGen['dEnvFE'] ?? 3, // default 3
   ```

3. **FEXmlService.php** (línea 424):
   ```php
   $dEnvFE = $this->getNestedValue($dGen, 'dEnvFE') ?? "3"; // default 3
   ```

**Resultado Esperado**: 
- ❌ Error 3000 (`FE_ValidacionUsoContenedor`) debe desaparecer
- ✅ Respuesta exitosa con código **200 o 1** y CUFE

---

## 📝 CONCLUSIÓN

✅ **DocuCenter cumple completamente con la estructura de respuesta definida en la Documentación Técnica NUC-XML Panama V2.0.7**

**Archivos Validados**:
- [DigifactService.php](app/Services/DigifactService.php) - Manejo de respuestas
- [DigifactXmlBuilder.php](app/Services/DigifactXmlBuilder.php) - Construcción XML
- [CreateFast.php](app/Http/Livewire/Admin/Einvoice/CreateFast.php) - Inicialización datos

**Estado de Certificación**:
- Cambio de `EnvioContenedor` de 1 a 3: ✅ Comiteado y pusheado
- Próximo paso: Probar certificación real con Digifact

