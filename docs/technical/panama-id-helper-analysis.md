# Análisis del Método panamaID - Soporte para RUCs Gubernamentales

**Fecha:** 2026-02-19  
**Archivo:** `vendor/weirdo/helper/src/Helper/Traits/HelperArray.php`  
**Método:** `panamaID()`  
**Objetivo:** Agregar soporte para RUCs gubernamentales de 4 partes (ej: `4-NT-1-8346`)

---

## 📋 Estado Actual

### Método Existente (líneas 798-822)

```php
/**
 * Accepted patterns:
 * - Regular (provincia-libro-tomo). Ej: 1-1234-12345
 * - Panameño nacido en el extranjero (PE-libro-tomo). Ej: PE-1234-12345
 * - Extranjero con cédula (E-libro-tomo). Ej: E-1234-123456
 * - Naturalizado (N-libro-tomo). Ej: N-1234-12345
 * - Panameños nacidos antes de la vigencia (provinciaAV-libro-tomo). Ej: 1AV-1234-12345
 * - Población indigena (provinciaPI-libro-tomo). Ej: 1PI-1234-12345
 *
 * @param string $id
 * @param int $tomo
 * @param int $folio
 * @param string $delimiter
 * @return array
 */
public function panamaID($id, $tomo = 4, $folio = 6, $delimiter = '')
{
    $onlyFirst = "^(?:PE|E|N|[23456789]|[23456789](?:A|P)?|1[0123]?|1[0123]?(?:A|P)?)$|^(?:PE|E|N|[23456789]|[23456789](?:AV|PI)?|1[0123]?|1[0123]?(?:AV|PI)?)$delimiter?$";
    $bookAndTome = "^(?:PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)$delimiter(?:\d{1,$tomo})$delimiter?$";
    $fullId = "^(PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})";

    preg_match("/^P$|{$onlyFirst}|{$bookAndTome}|{$fullId}/i", $id, $matches);

    return $matches;
}
```

---

## ❌ Problemas Detectados

### 1. Expresión `$onlyFirst` - Inconsistencia
```php
// Línea 815 - Tiene DOS patrones diferentes repetidos:
"^(?:PE|E|N|[23456789]|[23456789](?:A|P)?|..."  // ❌ Patrón 1: (?:A|P)? incompleto
"|^(?:PE|E|N|[23456789]|[23456789](?:AV|PI)?|..." // ✅ Patrón 2: (?:AV|PI)? correcto
```

**Problema:** Duplicación innecesaria y patrón incorrecto `(?:A|P)?` que debería ser `(?:AV|PI)?`

### 2. NO soporta RUCs Gubernamentales (4 partes)

**Formato actual soportado (3 partes):**
```
provincia-libro-tomo
8-123-456 ✅
PE-123-456 ✅
8AV-123-456 ✅
```

**Formato gubernamental NO soportado (4 partes):**
```
provincia-TIPO-componente-secuencia
4-NT-1-8346 ❌
8-PA-2-1234 ❌
1-GO-3-5678 ❌
```

### 3. Sufijos Gubernamentales Faltantes

**Actualmente soportados:**
- ✅ `AV` (Antes de la Vigencia)
- ✅ `PI` (Población Indígena)

**NO soportados:**
- ❌ `NT` (No Tributario)
- ❌ `PA` (Panamá especial)
- ❌ `GO` (Gobierno)
- ❌ `ED` (Educación)
- ❌ `SA` (Salud)

---

## 📊 Análisis de Expresiones Regulares

### Expresión Correcta para Provincias 1-13

**Patrón actual (problemático):**
```regex
[23456789]|1[0123]?
```

**Problemas:**
- No es consistente con el usado en `ACIcloudService::determineRucType()`
- Puede generar confusión

**Patrón correcto (usado en ACIcloudService):**
```regex
1[0123]?|[23456789]
```

**Explicación:**
- `1[0123]?` → Captura: 1, 10, 11, 12, 13
- `[23456789]` → Captura: 2, 3, 4, 5, 6, 7, 8, 9

---

## 🎯 Propuesta de Mejora

### Nuevos Patrones a Agregar

#### 1. Patrón para RUC Gubernamental (4 partes)

```php
// Formato: provincia-TIPO-componente-secuencia
// Ejemplo: 4-NT-1-8346
$governmentalId = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})";
```

**Captura:**
- `$matches[1]` → Provincia (1-13)
- `$matches[2]` → Tipo gubernamental (NT, PA, GO, ED, SA)
- `$matches[3]` → Componente (hasta 4 dígitos por defecto)
- `$matches[4]` → Secuencia (hasta 6 dígitos por defecto)

#### 2. Patrón para Solo Provincia + Tipo Gubernamental

```php
// Validación parcial: provincia-TIPO
// Ejemplo: 4-NT
$governmentalPrefix = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter?$";
```

#### 3. Patrón para Provincia + Tipo + Componente

```php
// Validación parcial: provincia-TIPO-componente
// Ejemplo: 4-NT-1
$governmentalPartial = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter(\d{1,$tomo})$delimiter?$";
```

---

## 🔧 Expresiones Mejoradas

### Expresión `$onlyFirst` - CORREGIDA

**Antes:**
```php
$onlyFirst = "^(?:PE|E|N|[23456789]|[23456789](?:A|P)?|1[0123]?|1[0123]?(?:A|P)?)$|^(?:PE|E|N|[23456789]|[23456789](?:AV|PI)?|1[0123]?|1[0123]?(?:AV|PI)?)$delimiter?$";
```

**Después (simplificada y corregida):**
```php
$onlyFirst = "^(?:PE|E|N|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter?$";
```

**Cambios:**
- ✅ Eliminada duplicación innecesaria
- ✅ Corregido patrón `(?:A|P)?` a `(?:AV|PI)?`
- ✅ Reordenado para consistencia (provincias 1-13 primero)

### Expresión `$bookAndTome` - MEJORADA

**Antes:**
```php
$bookAndTome = "^(?:PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)$delimiter(?:\d{1,$tomo})$delimiter?$";
```

**Después (mismo orden que onlyFirst):**
```php
$bookAndTome = "^(?:PE|E|N|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter?$";
```

**Cambios:**
- ✅ Reordenado para consistencia
- ✅ Cambiado a grupo de captura `(\d{1,$tomo})` en vez de no captura `(?:\d{1,$tomo})`

### Expresión `$fullId` - MEJORADA

**Antes:**
```php
$fullId = "^(PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})";
```

**Después:**
```php
$fullId = "^(PE|E|N|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})";
```

**Cambios:**
- ✅ Reordenado para consistencia

---

## 📝 Método Completo Propuesto

```php
/**
 * Accepted patterns:
 * - Regular (provincia-libro-tomo). Ej: 1-1234-12345
 * - Panameño nacido en el extranjero (PE-libro-tomo). Ej: PE-1234-12345
 * - Extranjero con cédula (E-libro-tomo). Ej: E-1234-123456
 * - Naturalizado (N-libro-tomo). Ej: N-1234-12345
 * - Panameños nacidos antes de la vigencia (provinciaAV-libro-tomo). Ej: 1AV-1234-12345
 * - Población indigena (provinciaPI-libro-tomo). Ej: 1PI-1234-12345
 * - RUC Gubernamental (provincia-TIPO-componente-secuencia). Ej: 4-NT-1-8346
 *
 * @param string $id
 * @param int $tomo
 * @param int $folio
 * @param string $delimiter
 * @return array
 */
public function panamaID($id, $tomo = 4, $folio = 6, $delimiter = '')
{
    // 1. Solo primera parte (provincia, PE, E, N, o provincia con AV/PI)
    $onlyFirst = "^(?:PE|E|N|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter?$";
    
    // 2. Provincia + libro (2 partes)
    $bookAndTome = "^(?:PE|E|N|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter?$";
    
    // 3. Cédula completa (provincia-libro-tomo) - 3 partes
    $fullId = "^(PE|E|N|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})$";
    
    // 4. RUC Gubernamental - Solo provincia + tipo (2 partes)
    $governmentalPrefix = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter?$";
    
    // 5. RUC Gubernamental - Provincia + tipo + componente (3 partes)
    $governmentalPartial = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter(\d{1,$tomo})$delimiter?$";
    
    // 6. RUC Gubernamental completo (provincia-TIPO-componente-secuencia) - 4 partes
    $governmentalId = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})$";

    preg_match(
        "/^P$|{$onlyFirst}|{$bookAndTome}|{$fullId}|{$governmentalPrefix}|{$governmentalPartial}|{$governmentalId}/i",
        $id,
        $matches
    );

    return $matches;
}
```

---

## 🧪 Casos de Prueba

### Cédulas Estándar (3 partes) - Deben seguir funcionando

```php
$helper->panamaID('8-123-456', 4, 6, '-');
// ✅ Debe capturar: ['8-123-456', '8', '123', '456']

$helper->panamaID('PE-123-456', 4, 6, '-');
// ✅ Debe capturar: ['PE-123-456', 'PE', '123', '456']

$helper->panamaID('8AV-123-456', 4, 6, '-');
// ✅ Debe capturar: ['8AV-123-456', '8AV', '123', '456']

$helper->panamaID('1PI-123-456', 4, 6, '-');
// ✅ Debe capturar: ['1PI-123-456', '1PI', '123', '456']
```

### RUCs Gubernamentales (4 partes) - NUEVO SOPORTE

```php
$helper->panamaID('4-NT-1-8346', 4, 6, '-');
// ✅ Debe capturar: ['4-NT-1-8346', '4', 'NT', '1', '8346']

$helper->panamaID('8-PA-2-1234', 4, 6, '-');
// ✅ Debe capturar: ['8-PA-2-1234', '8', 'PA', '2', '1234']

$helper->panamaID('1-GO-3-5678', 4, 6, '-');
// ✅ Debe capturar: ['1-GO-3-5678', '1', 'GO', '3', '5678']

$helper->panamaID('13-ED-1-9999', 4, 6, '-');
// ✅ Debe capturar: ['13-ED-1-9999', '13', 'ED', '1', '9999']
```

### Validaciones Parciales

```php
// Solo provincia
$helper->panamaID('8', 4, 6, '-');
// ✅ Debe capturar: ['8']

// Provincia + tipo gubernamental
$helper->panamaID('4-NT', 4, 6, '-');
// ✅ Debe capturar: ['4-NT', '4', 'NT']

// Provincia + tipo + componente
$helper->panamaID('4-NT-1', 4, 6, '-');
// ✅ Debe capturar: ['4-NT-1', '4', 'NT', '1']
```

### Casos Inválidos

```php
$helper->panamaID('14-123-456', 4, 6, '-');
// ❌ Debe retornar: [] (provincia 14 no existe)

$helper->panamaID('8-XX-123-456', 4, 6, '-');
// ❌ Debe retornar: [] (XX no es un tipo gubernamental válido)

$helper->panamaID('ABC-123-456', 4, 6, '-');
// ❌ Debe retornar: [] (ABC no es un prefijo válido)
```

---

## 📊 Matriz de Compatibilidad

| Patrón | Formato | Partes | Ejemplo | Soporte Actual | Soporte Propuesto |
|--------|---------|---------|---------|----------------|-------------------|
| **Cédula Regular** | `prov-libro-tomo` | 3 | `8-123-456` | ✅ | ✅ |
| **PE/E/N** | `tipo-libro-tomo` | 3 | `PE-123-456` | ✅ | ✅ |
| **AV/PI** | `provAV-libro-tomo` | 3 | `8AV-123-456` | ✅ | ✅ |
| **Gubernamental** | `prov-TIPO-comp-seq` | 4 | `4-NT-1-8346` | ❌ | ✅ |

---

## ⚠️ Consideraciones

### 1. Orden de Validación en Regex
El orden es importante en la alternancia `|`:
```php
// ✅ CORRECTO: De más específico a menos específico
$governmentalId      // 4 partes completas
$governmentalPartial // 3 partes
$governmentalPrefix  // 2 partes
$fullId              // 3 partes completas (cédula)
$bookAndTome         // 2 partes
$onlyFirst           // 1 parte
```

### 2. Grupos de Captura
Los grupos de captura `()` permiten extraer las partes individuales:
- Cédula: `['8-123-456', '8', '123', '456']` → [match completo, provincia, libro, tomo]
- Gubernamental: `['4-NT-1-8346', '4', 'NT', '1', '8346']` → [match completo, provincia, tipo, componente, secuencia]

### 3. Case-Insensitive
La bandera `/i` al final hace que sea case-insensitive:
- `4-nt-1-8346` ✅
- `4-NT-1-8346` ✅
- `4-Nt-1-8346` ✅

### 4. Delimitador Personalizable
El parámetro `$delimiter` permite usar diferentes separadores:
- Con `-`: `4-NT-1-8346`
- Con `_`: `4_NT_1_8346`
- Sin delimitador: `4NT18346` (menos común)

---

## 🎯 Próximos Pasos

1. ✅ **Revisión de documentación** - Este documento
2. ⏳ **Implementar cambios** en `HelperArray.php`
3. ⏳ **Crear tests unitarios** para validar todos los casos
4. ⏳ **Actualizar PHPDoc** con nuevos patrones
5. ⏳ **Probar integración** con sistemas existentes

---

## 📚 Referencias

### Documentos Relacionados
- [ACIcloudService RUC Gubernamental Support](../validations/acicloud-ruc-gubernamental-support.md)
- [Corrección Crítica dTipoRuc](../validations/acicloud-ruc-gubernamental-support.md#corrección-crítica-implementada)

### Código Relacionado
- `vendor/weirdo/helper/src/Helper/Traits/HelperArray.php` → Método `panamaID()`
- `app/Services/ACIcloudService.php` → Método `determineRucType()`
- `app/Helpers/PanamaRucHelper.php` → Validación de RUCs

### Patrones de Provincia Consistentes
```regex
// Patrón usado en ACIcloudService::determineRucType()
1[0123]?|[23456789]  // Provincias 1-13

// Este mismo patrón se debe usar en panamaID() para consistencia
```

---

**Fecha de Análisis:** 2026-02-19  
**Analizado por:** Team de Docucenter  
**Estado:** Listo para implementación
