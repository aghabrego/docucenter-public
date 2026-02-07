# Tests de Procesamiento JSON de Digifact

## Descripción

Tests para validar que el procesamiento de respuestas JSON de Digifact funciona correctamente, incluyendo:
- Extracción de datos básicos (code, message)
- Decodificación de base64 en `responseData1`
- Extracción de valores XML usando regex
- Construcción de respuesta completa

## Tests Disponibles

### 1. test-digifact-json-response.php

Prueba el procesamiento de una respuesta JSON de ejemplo (hardcodeada).

```bash
php docs/testing/test-digifact-json-response.php
```

**Qué valida:**
- Parseo de JSON
- Decodificación de base64 en responseData1
- Extracción de 12 campos XML con regex
- Generación de respuesta estructurada

**Resultado esperado:**
```
✓ PASO 1: Parseando respuesta JSON
✓ PASO 2: Extrayendo datos del XML embebido en responseData1
✓ PASO 3: Validando resultado
✓ PASO 4: Respuesta final (simulada)
```

---

### 2. test-digifact-log-parser.php ⭐ (RECOMENDADO)

**Procesa datos REALES del log de producción**.

```bash
php docs/testing/test-digifact-log-parser.php
```

**Qué valida:**
- Busca todas las respuestas JSON en el log
- Decodifica base64 de cada una
- Extrae CUFE y otros campos
- Valida que el procesamiento funciona con datos reales

**Resultado esperado:**
```
✓ Se encontraron 3 respuestas JSON
✓ CUFEs extraídos: 3

CUFE encontrado: FE0120000155770712-2-2025-3600002026020300000000110020113194207472
```

---

## Campos XML Extraídos

El script extrae automáticamente estos campos del XML dentro de `responseData1`:

```
dId           → CUFE del documento
dVerForm      → Versión del formato
iAmb          → Ambiente (1=Producción)
iTpEmis       → Tipo de emisión
iDoc          → Tipo de documento
dNroDF        → Número fiscal del documento
dPtoFacDF     → Punto de facturación
iNatOp        → Naturaleza de la operación
tiPOp         → Tipo de operación (FE=Factura Electrónica)
iDest         → Destino (1=Local)
dFechaEm      → Fecha de emisión
dHoraEm       → Hora de emisión
```

---

## Estructura de Respuesta

Cuando el procesamiento es exitoso, se retorna:

```php
[
    'success' => true,
    'codigo' => '1',  // código de respuesta de Digifact
    'mensaje' => 'Proceso de certificacion realizado correctamente!',
    'cufe' => 'FE0120000155770712-2-2025-3600002026020300000000110020113194207472',
    'xml_values' => [  // Todos los valores extraídos
        'dId' => 'FE0120000155770712-2-2025-3600002026020300000000110020113194207472',
        'dNroDF' => '00000000011',
        'dFechaEm' => '2026-02-03',
        'dHoraEm' => '19:29:03',
        // ... más campos
    ],
    'data' => [ ... ]  // JSON original completo
]
```

---

## Método de Extracción

### Estrategia de Regex

Se usa regex en lugar de SimpleXML porque:
1. El XML de Digifact puede tener encoding UTF-8 corrupto
2. SimpleXML falla en esos casos
3. Regex es más robusto para XML malformado

```php
// Patrón regex genérico
$pattern = '/<fieldName>([^<]+)<\/fieldName>/';

// Ejemplo para CUFE
preg_match('/<dId>([^<]+)<\/dId>/', $xmlDecoded, $matches);
$cufe = trim($matches[1]);  // FE0120000155770712-2-2025-...
```

---

## Ejecución en CI/CD

Para automatizar la validación:

```bash
#!/bin/bash
cd /ruta/a/docucenter

# Test básico (hardcodeado)
php docs/testing/test-digifact-json-response.php

# Test con datos reales (si el log existe)
if [ -f "storage/logs/laravel.log" ]; then
    php docs/testing/test-digifact-log-parser.php
fi
```

---

## Solución de Problemas

### ❌ "No se encontraron respuestas JSON"
- Verificar que `storage/logs/laravel.log` existe
- Confirmar que se han emitido documentos con Digifact
- El log debe contener líneas con `"data":{"code"`

### ❌ "Error al decodificar base64"
- El base64 podría estar corrupto
- Verificar que es válido base64: `echo $base64 | base64 -d`

### ❌ "No se extrajeron campos XML"
- El XML podría no tener los tags buscados
- Verificar estructura con: `echo $xmlDecoded | head -500`

---

## Integración con DigifactService

Los tests validan el código en `app/Services/DigifactService.php`:

- **Método**: `certifyDocument()`
- **Ubicación**: Lines 740-825 (respuesta JSON), 853-902 (respuesta XML)
- **Función auxiliar**: `extractAllXmlValues()` - Lines 1180+

El método `extractAllXmlValues()` es lo que ejecutan estos tests internamente.

---

## Ver También

- [README-CUFE-EXTRACTION.md](README-CUFE-EXTRACTION.md) - Extracción de CUFE desde base64
- [DigifactService.php](../../app/Services/DigifactService.php) - Implementación completa
- [test-cufe-from-log.php](test-cufe-from-log.php) - Test específico de CUFE

---

## Últimas Pruebas (2026-02-05)

✓ **test-digifact-json-response.php**: Procesamiento exitoso
- 12 campos extraídos correctamente
- CUFE: `FE0120000155770712-2-2025-3600002026020300000000110020113194207472`

✓ **test-digifact-log-parser.php**: 3 respuestas procesadas
- 3 CUFEs extraídos del log real
- Estructura de respuesta completa y validada

