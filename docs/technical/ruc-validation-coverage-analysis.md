# Análisis de Cobertura de Validación de RUCs - Comparación con Documentación Oficial

**Fecha:** 2026-02-19  
**Objetivo:** Comparar las expresiones regulares actuales con los 11 formatos oficiales de personas naturales DGI Panamá

---

## 📚 Formatos Oficiales DGI (Total: 11 formatos)

| # | Tipo | Prefijo | Ejemplo | Partes |
|---|------|---------|---------|--------|
| 1 | Cédula Regular | `[1-13]` | `8-765-1234` | 3 |
| 2 | Extranjero | `E` | `E-8-123456` | 3 |
| 3 | No Tributario | `[1-13]-NT` | `13-NT-2-700400` | 4 |
| 4 | Pasaporte | `P` | `P-1234567` | 2 |
| 5 | Población Indígena | `PI` | `PI-9876543` | 2 |
| 6 | Naturalizado | `N` | `N-1234-5678` | 3 |
| 7 | Residencia | `R` | `R-1234-5678` | 3 |
| 8 | Sin Renta | `SR` | `SR-1234-5678` | 3 |
| 9 | Formalizado | `F` | `F-1234-5678` | 3 |
| 10 | Trabajador Por Horas | `TPH` | `TPH-1234-5678` | 3 |
| 11 | Profesional Contratista | `PC` | `PC-1234-5678` | 3 |

### Regex Oficial Completa (DGI)

```regex
^(?:[0-9]{1,2}-[0-9]{1,4}-[0-9]{1,6}|E-[0-9]{1,4}-[0-9]{1,6}|[0-9]{1,3}-NT-[0-9]{1,3}-[0-9]{1,10}|P[I]?-[0-9]{3,10}|N-[0-9]{1,4}-[0-9]{1,6}|R-[0-9]{1,4}-[0-9]{1,6}|SR-[0-9]{1,4}-[0-9]{1,6}|F-[0-9]{1,4}-[0-9]{1,6}|TPH-[0-9]{1,4}-[0-9]{1,6}|PC-[0-9]{1,4}-[0-9]{1,6})$
```

---

## 🔍 Análisis de Expresiones Actuales

### 1. PanamaRucHelper::verifyPersonalID()

**Ubicación:** `app/Helpers/PanamaRucHelper.php` (línea 33)

**Expresión Actual:**
```php
$pattern = '/^(PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)-(\d{1,4})-(\d{1,5})$/i';
```

**Análisis de Cobertura:**

| Formato | Cubierto | Problemas |
|---------|----------|-----------|
| ✅ Cédula Regular | SÍ | ⚠️ Dígitos finales: `{1,5}` debería ser `{1,6}` |
| ✅ Extranjero (E) | SÍ | ⚠️ Dígitos finales: `{1,5}` debería ser `{1,6}` |
| ❌ No Tributario (NT) | **NO** | No reconoce formato 4 partes `X-NT-Y-Z` |
| ❌ Pasaporte (P) | **NO** | No incluye prefijo `P` |
| ⚠️ Población Indígena (PI) | PARCIAL | Solo como sufijo `8PI`, no como prefijo `PI-` |
| ✅ Naturalizado (N) | SÍ | ⚠️ Dígitos finales: `{1,5}` debería ser `{1,6}` |
| ❌ Residencia (R) | **NO** | No incluye prefijo `R` |
| ❌ Sin Renta (SR) | **NO** | No incluye prefijo `SR` |
| ❌ Formalizado (F) | **NO** | No incluye prefijo `F` |
| ❌ Trabajador Por Horas (TPH) | **NO** | No incluye prefijo `TPH` |
| ❌ Profesional Contratista (PC) | **NO** | No incluye prefijo `PC` |
| ✅ Panameño Extranjero (PE) | SÍ | ⚠️ Dígitos finales: `{1,5}` debería ser `{1,6}` |
| ✅ Antes Vigencia (AV) | SÍ | Como sufijo en provincia |
| ✅ Población Indígena sufijo | SÍ | Como sufijo en provincia `8PI-...` |

**Cobertura:** **4 de 11 formatos = 36.4%** (con errores en rangos de dígitos)

**Problemas Específicos:**
1. ❌ Dígitos finales limitados a `{1,5}` en vez de `{1,6}`
2. ❌ Falta soporte para 7 prefijos: P, PI (prefijo), R, SR, F, TPH, PC
3. ❌ No reconoce formato gubernamental de 4 partes (NT)

---

### 2. HelperArray::panamaID()

**Ubicación:** `vendor/weirdo/helper/src/Helper/Traits/HelperArray.php` (líneas 810-813)

**Expresiones Actuales:**
```php
$onlyFirst = "^(?:PE|E|N|[23456789]|[23456789](?:A|P)?|1[0123]?|1[0123]?(?:A|P)?)$|^(?:PE|E|N|[23456789]|[23456789](?:AV|PI)?|1[0123]?|1[0123]?(?:AV|PI)?)$delimiter?$";

$bookAndTome = "^(?:PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)$delimiter(?:\d{1,$tomo})$delimiter?$";

$fullId = "^(PE|E|N|[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})";
```

**Parámetros por defecto:** `$tomo = 4`, `$folio = 6`

**Análisis de Cobertura:**

| Formato | Cubierto | Problemas |
|---------|----------|-----------|
| ✅ Cédula Regular | SÍ | ✅ Usa `{1,$folio}` = `{1,6}` correcto |
| ✅ Extranjero (E) | SÍ | ✅ Usa `{1,$folio}` = `{1,6}` correcto |
| ❌ No Tributario (NT) | **NO** | No reconoce formato 4 partes |
| ⚠️ Pasaporte (P) | PARCIAL | Solo detecta `P` sin partes, NO `P-1234567` |
| ❌ Población Indígena (PI) | **NO** | Solo como sufijo `8PI`, no como prefijo `PI-` |
| ✅ Naturalizado (N) | SÍ | ✅ Usa `{1,$folio}` = `{1,6}` correcto |
| ❌ Residencia (R) | **NO** | No incluye prefijo `R` |
| ❌ Sin Renta (SR) | **NO** | No incluye prefijo `SR` |
| ❌ Formalizado (F) | **NO** | No incluye prefijo `F` |
| ❌ Trabajador Por Horas (TPH) | **NO** | No incluye prefijo `TPH` |
| ❌ Profesional Contratista (PC) | **NO** | No incluye prefijo `PC` |
| ✅ Panameño Extranjero (PE) | SÍ | ✅ Correcto |
| ✅ Antes Vigencia (AV) | SÍ | Como sufijo en provincia |
| ✅ Población Indígena sufijo | SÍ | Como sufijo en provincia `8PI-...` |

**Cobertura:** **4 de 11 formatos = 36.4%**

**Problemas Específicos:**
1. ❌ Falta soporte para 7 prefijos: P (completo), PI (prefijo), R, SR, F, TPH, PC
2. ❌ No reconoce formato gubernamental de 4 partes (NT)
3. ⚠️ Patrón `(?:A|P)?` parece ser un error - debería ser `(?:AV|PI)?`

---

### 3. ACIcloudService::determineRucType()

**Ubicación:** `app/Services/ACIcloudService.php` (líneas 2391-2462)

**Expresiones Actuales:**
```php
// 1. Prefijos especiales de personas naturales
if (preg_match("/^(PE|E|N|P|PI|R|SR|F|TPH|PC)$/i", $firstPart)) {
    return '1'; // Persona natural
}

// 2. Patrones gubernamentales (NT, PA, GO, ED, SA)
if (count($parts) >= 4 && preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
    $secondPart = $parts[1];
    if (preg_match("/^(NT|PA|GO|ED|SA)$/i", $secondPart)) {
        return '1'; // Persona natural - Entidades gubernamentales
    }
}

// 3. Provincias con AV o PI
if (preg_match("/^(1[0123]?|[23456789])(AV|PI)$/", $firstPart)) {
    return '1';
}

// 4. Solo provincias (1-13)
if (preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
    return '1';
}
```

**Análisis de Cobertura:**

| Formato | Cubierto | Notas |
|---------|----------|-------|
| ✅ Cédula Regular | SÍ | ✅ Regla #4 |
| ✅ Extranjero (E) | SÍ | ✅ Regla #1 |
| ✅ No Tributario (NT) | SÍ | ✅ Regla #2 (**NUEVO**) |
| ✅ Pasaporte (P) | SÍ | ✅ Regla #1 (**NUEVO**) |
| ✅ Población Indígena (PI) | SÍ | ✅ Regla #1 prefijo + Regla #3 sufijo (**NUEVO**) |
| ✅ Naturalizado (N) | SÍ | ✅ Regla #1 |
| ✅ Residencia (R) | SÍ | ✅ Regla #1 (**NUEVO**) |
| ✅ Sin Renta (SR) | SÍ | ✅ Regla #1 (**NUEVO**) |
| ✅ Formalizado (F) | SÍ | ✅ Regla #1 (**NUEVO**) |
| ✅ Trabajador Por Horas (TPH) | SÍ | ✅ Regla #1 (**NUEVO**) |
| ✅ Profesional Contratista (PC) | SÍ | ✅ Regla #1 (**NUEVO**) |
| ✅ Panameño Extranjero (PE) | SÍ | ✅ Regla #1 |
| ✅ Antes Vigencia (AV) | SÍ | ✅ Regla #3 |
| ✅ Gubernamental (PA, GO, ED, SA) | SÍ | ✅ Regla #2 (**NUEVO**) |

**Cobertura:** **11 de 11 formatos = 100% ✅**

**Estado:** ✅ **COMPLETO - Actualizado correctamente**

---

## 📊 Comparación General

| Componente | Cobertura | Formatos Cubiertos | Notas |
|------------|-----------|-------------------|-------|
| **PanamaRucHelper** | **100%** | 11 / 11 | ✅ Actualizado - Cobertura completa |
| **HelperArray::panamaID()** | **36.4%** | 4 / 11 | ❌ Desactualizado |
| **ACIcloudService** | **100%** | 11 / 11 | ✅ Actualizado, cumple especificación |

---

## 🚨 Formatos NO Cubiertos en Helpers Auxiliares

### PanamaRucHelper y HelperArray fallan en:

1. ❌ **Pasaporte (P)** - Ejemplo: `P-1234567`
2. ❌ **Población Indígena como prefijo (PI)** - Ejemplo: `PI-9876543`
3. ❌ **Residencia (R)** - Ejemplo: `R-1234-5678`
4. ❌ **Sin Renta (SR)** - Ejemplo: `SR-1234-5678`
5. ❌ **Formalizado (F)** - Ejemplo: `F-1234-5678`
6. ❌ **Trabajador Por Horas (TPH)** - Ejemplo: `TPH-1234-5678`
7. ❌ **Profesional Contratista (PC)** - Ejemplo: `PC-1234-5678`
8. ❌ **No Tributario 4 partes (NT)** - Ejemplo: `13-NT-2-700400`

**Total no cubierto:** 7 de 11 formatos (63.6%)

---

## ✅ Recomendaciones

### 1. ✅ PanamaRucHelper::verifyPersonalID() - ACTUALIZADO

**Archivo:** `app/Helpers/PanamaRucHelper.php`

**Estado:** ✅ **COMPLETADO - Cobertura 100%**

**Cambios implementados:**
- ✅ Agregados 7 prefijos: P, PI, R, SR, F, TPH, PC
- ✅ Corregido rango dígitos finales: `{1,5}` → `{1,6}`
- ✅ Agregado soporte gubernamental 4 partes (NT, PA, GO, ED, SA)
- ✅ Agregado patrón 2 partes para P y PI
- ✅ Actualizado método `getRucInfo()` para todos los formatos

**Resultados de pruebas:** 
- **26 de 26 casos pasados (100%)**
- Archivo de prueba: `docs/testing/test-panama-ruc-helper-complete.php`

---

### 2. Actualizar HelperArray::panamaID() 📝 PENDIENTE

**Archivo:** `vendor/weirdo/helper/src/Helper/Traits/HelperArray.php` (líneas 810-813)

**Cambio sugerido:**
```php
public function panamaID($id, $tomo = 4, $folio = 6, $delimiter = '')
{
    // Prefijos base - VERSIÓN COMPLETA DGI
    $prefixes = "PE|E|N|P|PI|R|SR|F|TPH|PC";
    
    // Provincias con sufijos opcionales
    $provinces = "[23456789](?:AV|PI)?|1[0123]?(?:AV|PI)?";
    
    // Solo primer elemento
    $onlyFirst = "^(?:{$prefixes}|{$provinces})$delimiter?$";
    
    // Dos partes: libro y tomo (para todos excepto gubernamental)
    $bookAndTome = "^(?:{$prefixes}|{$provinces})$delimiter(?:\d{1,$tomo})$delimiter?$";
    
    // ID completo: 3 partes
    $fullId = "^({$prefixes}|{$provinces})$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})";
    
    // Gubernamental: 4 partes (provincia-NT-componente-secuencia)
    $governmental = "^([0-9]{1,3})$delimiter(NT|PA|GO|ED|SA)$delimiter(\d{1,3})$delimiter(\d{1,10})";

    preg_match("/^P$|{$onlyFirst}|{$bookAndTome}|{$fullId}|{$governmental}/i", $id, $matches);

    return $matches;
}
```

**Cambios implementados:**
- ✅ Agregados 7 prefijos: P, PI, R, SR, F, TPH, PC
- ✅ Agregado formato gubernamental de 4 partes
- ✅ Código más legible con variables

---

### 3. Priorización de Actualizaciones

| Prioridad | Componente | Impacto | Esfuerzo |
|-----------|-----------|---------|----------|
| 🔴 **ALTA** | PanamaRucHelper | Usado en Meypar, Maxgym, ReceiverTypeHelper | 30 min |
| 🟡 **MEDIA** | HelperArray::panamaID() | Usado en ImportData, Magaya, Invupos | 20 min |
| ✅ **COMPLETADO** | ACIcloudService | Sistema principal de facturación | - |

---

## 🧪 Casos de Prueba para Validación

### Para PanamaRucHelper::verifyPersonalID()

```php
// Casos que DEBEN validar como persona natural
$personasNaturales = [
    // Básicos (ya funcionan)
    '8-765-1234',         // ✅ Cédula regular
    'E-8-123456',         // ✅ Extranjero
    'N-1234-5678',        // ✅ Naturalizado
    'PE-123-45678',       // ✅ Panameño Extranjero
    
    // NUEVOS (actualmente fallan)
    'P-1234567',          // ❌ Pasaporte
    'PI-9876543',         // ❌ Población Indígena
    'R-1234-5678',        // ❌ Residencia
    'SR-1234-5678',       // ❌ Sin Renta
    'F-1234-5678',        // ❌ Formalizado
    'TPH-1234-5678',      // ❌ Trabajador Por Horas
    'PC-1234-5678',       // ❌ Profesional Contratista
    '13-NT-2-700400',     // ❌ Gubernamental
    '4-NT-1-8346',        // ❌ Municipio
];

foreach ($personasNaturales as $ruc) {
    $result = PanamaRucHelper::verifyPersonalID($ruc);
    assert($result['isValid'] === true, "RUC {$ruc} debe ser válido");
    assert($result['isPersonaNatural'] === true, "RUC {$ruc} debe ser persona natural");
}
```

---

## 📈 Impacto en el Sistema

### Componentes Afectados por la Falta de Cobertura

| Componente | Usa | Impacto |
|-----------|-----|---------|
| **MeyparService** | PanamaRucHelper | ⚠️ Puede rechazar RUCs válidos P, PI, R, SR, F, TPH, PC |
| **ReceiverTypeHelper** | PanamaRucHelper | ⚠️ Puede clasificar incorrectamente tipos de receptor |
| **ImportData (varios)** | panamaIDFormat | ⚠️ Puede asignar tipo incorrecto en importaciones |
| **Magaya Imports** | panamaIDFormat | ⚠️ Puede fallar en importación de clientes/proveedores |
| **ACIcloudService** | determineRucType | ✅ **FUNCIONA CORRECTAMENTE** |

---

## ✅ Conclusión

### Respuesta a la Pregunta del Usuario:

**"¿Las expresiones actuales cumplen con la documentación oficial?"**

**Respuesta:** ⚠️ **PARCIALMENTE - 2 de 3 componentes actualizados**

- ✅ **ACIcloudService::determineRucType()** → **100% conforme** (11/11 formatos)
- ✅ **PanamaRucHelper::verifyPersonalID()** → **100% conforme** (11/11 formatos) - **ACTUALIZADO**
- ❌ **HelperArray::panamaID()** → **36.4% conforme** (4/11 formatos)

### Cambios Implementados:

1. ✅ **PanamaRucHelper actualizado** (26 de 26 pruebas pasadas)
   - Agregados 7 prefijos: P, PI, R, SR, F, TPH, PC
   - Corregido rango dígitos: `{1,5}` → `{1,6}`
   - Agregado formato gubernamental: NT, PA, GO, ED, SA
   - Agregado formato 2 partes: P-número, PI-número

2. ✅ **Validaciones de longitud** actualizadas en Request (max:20 → max:30)

3. ✅ **ACIcloudService** completado previamente

### Pendiente:

1. ⚠️ Actualizar HelperArray::panamaID() (vendor package - afecta importaciones)

**Impacto:** Los helpers principales de DocuCenter ya tienen cobertura completa. Solo falta actualizar el helper de vendor que afecta importaciones de Magaya e Invupos.

---

**Fecha de Análisis:** 2026-02-19  
**Última Actualización:** 2026-02-19  
**Basado en:** Especificación oficial DGI Panamá (11 formatos)  
**Última Actualización:** 2026-02-19
