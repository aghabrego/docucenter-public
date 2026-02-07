# Script de Test: Extracción de CUFE desde Log Real

## Descripción
Script PHP que extrae automáticamente los valores `responseData1` del log de producción y decodifica los CUFEs de Digifact sin necesidad de hardcodear valores.

## Ubicación
```
docs/testing/test-cufe-from-log.php
```

## Uso

### Opción 1: Log por defecto (laravel.log)
```bash
php docs/testing/test-cufe-from-log.php
```

### Opción 2: Log específico
```bash
php docs/testing/test-cufe-from-log.php storage/logs/laravel-2026-02-04.log
```

## Qué hace el script

1. **Lee el archivo de log** especificado
2. **Busca todos los campos `responseData1`** usando regex
3. **Decodifica cada base64** a XML
4. **Extrae los CUFEs** del elemento `<dId>` con regex
5. **Reporta estadísticas** y lista de CUFEs válidos

## Ejemplo de Salida

```
=== EXTRACCIÓN DE CUFE DESDE LOG REAL ===

📄 Log utilizado: storage/logs/laravel.log
📦 Tamaño del log: 1,243,259 bytes

🔍 Buscando 'responseData1' en el log...
✅ Se encontraron 3 valores de responseData1

─────────────────────────────────────────
RESPUESTA #1
─────────────────────────────────────────
📊 Tamaño del base64: 14132 caracteres
📊 Preview del base64 (primeros 100 chars):
   PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48ckNvbnRGZSB4bWxucz0iaHR0cDovL2RnaS1mZXAubWVmLmdv...

🔄 DECODIFICANDO XML...
✅ XML decodificado: 10597 bytes

🔍 BUSCANDO CUFE CON REGEX...
✅ CUFE ENCONTRADO:
   FE0120000155770712-2-2025-3600002026020300000000110020113194207472
   Longitud: 66 caracteres

📋 ESTRUCTURA DEL CUFE:
   - Tipo: FE (Factura Electrónica)
   - Formato: Digifact
   - Valor completo: FE0120000155770712-2-2025-3600002026020300000000110020113194207472
   - Estado: ✅ VÁLIDO

=== RESUMEN FINAL ===
Total de responseData1 procesados: 3
Total de CUFEs extraídos: 3

📋 CUFEs VÁLIDOS ENCONTRADOS:
   1. FE0120000155770712-2-2025-3600002026020300000000110020113194207472
   2. FE0120000155770712-2-2025-3600002026020300000000110020113194207472
   3. FE0120000155770712-2-2025-3600002026020300000000110020113194207472
```

## Cómo funciona la extracción

### Paso 1: Buscar responseData1 en el log
```regex
/"responseData1":"([^"]{100,})"/
```
Busca el campo `responseData1` con más de 100 caracteres (para evitar valores vacíos).

### Paso 2: Decodificar base64
```php
$xmlDecoded = base64_decode($base64Data);
```
Convierte el base64 a XML UTF-8.

### Paso 3: Extraer CUFE con regex
```regex
/<dId>([^<]+)<\/dId>/
```
Busca el elemento `dId` que contiene el CUFE del documento.

## Estructura del CUFE de Digifact

```
FE0120000155770712-2-2025-3600002026020300000000110020113194207472
^^                 ^^^^^^  ^^^^
├─ FE: Factura Electrónica
└─ 01: Tipo de documento
   └─ 2000: Tipo de comprobante
      └─ 01557707122-2-2025: RUC del emisor
         └─ 36000020260203...: Datos de la emisión
```

## Validaciones

El script valida:
- ✅ Que el archivo de log existe
- ✅ Que contiene datos de `responseData1`
- ✅ Que el base64 decodifica correctamente
- ✅ Que se encuentra el elemento `<dId>` en el XML

## Errores comunes

### "No se encontró responseData1 en el log"
**Solución**: El log no contiene respuestas de Digifact. Asegúrate de:
1. Haber emitido documentos con Digifact
2. Usar el log correcto (fecha actual)

### "Error al decodificar el base64"
**Solución**: El base64 está corrupto. Esto puede indicar problemas en la respuesta de Digifact.

## Ver también
- [test-cufe-extraction.php](test-cufe-extraction.php) - Test con base64 hardcodeado
- [DigifactService.php](../../app/Services/DigifactService.php#L779) - Extracción de CUFE en el código real
