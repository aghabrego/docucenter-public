# Formatos de RUC para Personas Naturales en Panamá - Análisis Completo

**Fecha:** 2026-02-19  
**Fuente:** Especificación oficial DGI Panamá  
**Objetivo:** Documentar todos los formatos oficiales de RUC para personas naturales y validar cobertura en el código

---

## 🔑 Aclaración Fundamental

### Solo Existen 2 Tipos de RUC según DGI Panamá:

```
dTipoRuc = "1" → PERSONA NATURAL / CONSUMIDOR FINAL
dTipoRuc = "2" → PERSONA JURÍDICA / EMPRESA
```

**⚠️ IMPORTANTE:**
- **NO existe tipo "gubernamental" separado**
- Los RUCs gubernamentales (NT, PA, GO, ED, SA) son **tipo "1" (PERSONA NATURAL)**
- Los RUCs con formato especial (P, PI, N, R, SR, F, TPH, PC) son **tipo "1" (PERSONA NATURAL)**

---

## 📋 Tabla Completa de Formatos de Personas Naturales

| Tipo | Descripción | Ejemplo | Expresión Regular | Partes |
|------|-------------|---------|-------------------|--------|
| **Cédula Regular** | Provincia-Libro-Tomo | `8-765-1234` | `^[0-9]{1,2}-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **Extranjero (E)** | Extranjero-Libro-Tomo | `E-8-123456` | `^E-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **No Tributario (NT)** | Provincia-NT-Componente-Secuencia | `13-NT-2-700400` | `^[0-9]{1,3}-NT-[0-9]{1,3}-[0-9]{1,10}$` | 4 |
| **Pasaporte (P)** | Pasaporte-Número | `P-1234567` | `^P-[0-9]{3,10}$` | 2 |
| **Población Indígena (PI)** | PI-Número | `PI-9876543` | `^PI-[0-9]{3,10}$` | 2 |
| **Naturalizado (N)** | Naturalizado-Libro-Tomo | `N-1234-5678` | `^N-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **Residencia (R)** | Residencia-Libro-Tomo | `R-1234-5678` | `^R-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **Sin Renta (SR)** | Sin Renta-Libro-Tomo | `SR-1234-5678` | `^SR-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **Formalizado (F)** | Formalizado-Libro-Tomo | `F-1234-5678` | `^F-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **Trabajador Por Horas (TPH)** | TPH-Libro-Tomo | `TPH-1234-5678` | `^TPH-[0-9]{1,4}-[0-9]{1,6}$` | 3 |
| **Profesional Contratista (PC)** | PC-Libro-Tomo | `PC-1234-5678` | `^PC-[0-9]{1,4}-[0-9]{1,6}$` | 3 |

---

## 🔧 Expresión Regular Unificada (DGI Oficial)

```regex
^(?:[0-9]{1,2}-[0-9]{1,4}-[0-9]{1,6}|E-[0-9]{1,4}-[0-9]{1,6}|[0-9]{1,3}-NT-[0-9]{1,3}-[0-9]{1,10}|P[I]?-[0-9]{3,10}|N-[0-9]{1,4}-[0-9]{1,6}|R-[0-9]{1,4}-[0-9]{1,6}|SR-[0-9]{1,4}-[0-9]{1,6}|F-[0-9]{1,4}-[0-9]{1,6}|TPH-[0-9]{1,4}-[0-9]{1,6}|PC-[0-9]{1,4}-[0-9]{1,6})$
```

**Desglose:**
1. `[0-9]{1,2}-[0-9]{1,4}-[0-9]{1,6}` → Cédula regular (provincias 1-13)
2. `E-[0-9]{1,4}-[0-9]{1,6}` → Extranjero
3. `[0-9]{1,3}-NT-[0-9]{1,3}-[0-9]{1,10}` → No Tributario (gubernamental)
4. `P[I]?-[0-9]{3,10}` → Pasaporte o Población Indígena (P o PI)
5. `N-[0-9]{1,4}-[0-9]{1,6}` → Naturalizado
6. `R-[0-9]{1,4}-[0-9]{1,6}` → Residencia
7. `SR-[0-9]{1,4}-[0-9]{1,6}` → Sin Renta
8. `F-[0-9]{1,4}-[0-9]{1,6}` → Formalizado
9. `TPH-[0-9]{1,4}-[0-9]{1,6}` → Trabajador Por Horas
10. `PC-[0-9]{1,4}-[0-9]{1,6}` → Profesional Contratista

---

## ✅ Validación del Código Actual

### Método `determineRucType()` en ACIcloudService.php

**Código Actual (líneas 2391-2462):**

```php
private function determineRucType(?string $ruc): string
{
    // Si no hay RUC o está vacío, asumir consumidor final
    if (empty($ruc) || $ruc === '0-0-0') {
        return '1'; // ✅ CORRECTO - Persona natural/Consumidor final
    }

    $parts = explode('-', $ruc);
    if (count($parts) < 2) {
        return '1'; // ✅ CORRECTO
    }

    $firstPart = $parts[0];

    // === PERSONA NATURAL (retorna '1') ===

    // 1. PE (Panameño Extranjero), E (Extranjero), N (Naturalizado)
    if (preg_match("/^(PE|E|N)$/", $firstPart)) {
        return '1'; // ✅ CORRECTO - Cubre E y N
    }

    // 2. Patrones gubernamentales (NT, PA, GO, ED, SA)
    if (count($parts) >= 4 && preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
        $secondPart = $parts[1];
        if (preg_match("/^(NT|PA|GO|ED|SA)$/i", $secondPart)) {
            return '1'; // ✅ CORRECTO - Gubernamental es tipo '1'
        }
    }

    // 3. Provincias con AV (Antes de la Vigencia) o PI (Población Indígena)
    if (preg_match("/^(1[0123]?|[23456789])(AV|PI)$/", $firstPart)) {
        return '1'; // ✅ CORRECTO
    }

    // 4. Solo provincias (1-13)
    if (preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
        return '1'; // ✅ CORRECTO
    }

    // === PERSONA JURÍDICA/EMPRESA (retorna '2') ===

    // 5. RUC de empresa: patrones numéricos de 2+ dígitos
    if (preg_match("/^\d{2,}$/", $firstPart) && !preg_match("/^[1-9]$|^1[0-3]$/", $firstPart)) {
        return '2'; // ✅ CORRECTO
    }

    return '1'; // Por defecto: persona natural
}
```

---

## 📊 Análisis de Cobertura

### Formatos CUBIERTOS por el Código Actual

| Formato | Ejemplo | Cubierto | Regla |
|---------|---------|----------|-------|
| ✅ Cédula Regular | `8-765-1234` | SÍ | Regla #4 (provincias 1-13) |
| ✅ Extranjero (E) | `E-8-123456` | SÍ | Regla #1 (`PE|E|N`) |
| ✅ No Tributario (NT) | `13-NT-2-700400` | SÍ | Regla #2 (gubernamental) |
| ✅ Naturalizado (N) | `N-1234-5678` | SÍ | Regla #1 (`PE|E|N`) |
| ⚠️ Pasaporte (P) | `P-1234567` | **NO** | No detectado |
| ⚠️ Población Indígena (PI) | `PI-9876543` | **PARCIAL** | Solo como sufijo provinciaPI |
| ❌ Residencia (R) | `R-1234-5678` | **NO** | No detectado |
| ❌ Sin Renta (SR) | `SR-1234-5678` | **NO** | No detectado |
| ❌ Formalizado (F) | `F-1234-5678` | **NO** | No detectado |
| ❌ Trabajador Por Horas (TPH) | `TPH-1234-5678` | **NO** | No detectado |
| ❌ Profesional Contratista (PC) | `PC-1234-5678` | **NO** | No detectado |

**Cobertura:** 4 de 11 formatos = **36.4%**

---

## 🔧 Corrección Propuesta para `determineRucType()`

### Código Mejorado

```php
private function determineRucType(?string $ruc): string
{
    // Si no hay RUC o está vacío, asumir consumidor final
    if (empty($ruc) || $ruc === '0-0-0') {
        return '1'; // Persona natural/Consumidor final
    }

    $parts = explode('-', $ruc);
    if (count($parts) < 2) {
        return '1';
    }

    $firstPart = $parts[0];

    // === VALIDACIONES DE PERSONA NATURAL (retorna '1') ===

    // 1. Prefijos especiales de letra para personas naturales
    // E (Extranjero), N (Naturalizado), P (Pasaporte), PI (Población Indígena),
    // R (Residencia), SR (Sin Renta), F (Formalizado), TPH (Trabajador Por Horas),
    // PC (Profesional Contratista), PE (Panameño Extranjero)
    if (preg_match("/^(PE|E|N|P|PI|R|SR|F|TPH|PC)$/i", $firstPart)) {
        return '1';
    }

    // 2. Patrones gubernamentales (NT, PA, GO, ED, SA) - 4 partes
    if (count($parts) >= 4) {
        $secondPart = $parts[1];
        if (preg_match("/^(NT|PA|GO|ED|SA)$/i", $secondPart)) {
            return '1'; // Gubernamental es tipo PERSONA NATURAL
        }
    }

    // 3. Provincias con AV (Antes de la Vigencia) o PI (Población Indígena) como sufijo
    if (preg_match("/^(1[0123]?|[23456789])(AV|PI)$/i", $firstPart)) {
        return '1';
    }

    // 4. Solo provincias (1-13) sin sufijos
    if (preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
        return '1';
    }

    // === VALIDACIONES DE PERSONA JURÍDICA/EMPRESA (retorna '2') ===

    // 5. RUC de empresa/jurídica: patrones numéricos de 2+ dígitos
    // (excluye provincias 1-13 que ya fueron identificadas)
    if (preg_match("/^\d{2,}$/", $firstPart) && !preg_match("/^[1-9]$|^1[0-3]$/", $firstPart)) {
        return '2';
    }

    // Por defecto, asumir persona natural para casos no identificados
    return '1';
}
```

---

## 📝 Cambios Realizados

### Antes (Regla #1):
```php
if (preg_match("/^(PE|E|N)$/", $firstPart)) {
    return '1';
}
```

### Después (Regla #1 MEJORADA):
```php
if (preg_match("/^(PE|E|N|P|PI|R|SR|F|TPH|PC)$/i", $firstPart)) {
    return '1';
}
```

**Agregados:**
- ✅ `P` - Pasaporte
- ✅ `PI` - Población Indígena (como prefijo independiente)
- ✅ `R` - Residencia
- ✅ `SR` - Sin Renta
- ✅ `F` - Formalizado
- ✅ `TPH` - Trabajador Por Horas
- ✅ `PC` - Profesional Contratista

---

## 🧪 Casos de Prueba

### Personas Naturales (debe retornar '1')

```php
$personasNaturales = [
    // Cédulas regulares
    '8-765-1234',         // Provincia 8
    '1-123-456',          // Provincia 1
    '13-1234-123456',     // Provincia 13
    
    // Extranjeros y Naturalizados
    'E-8-123456',         // Extranjero
    'N-1234-5678',        // Naturalizado
    'PE-123-456',         // Panameño Extranjero
    
    // Gubernamentales
    '13-NT-2-700400',     // No Tributario
    '4-NT-1-8346',        // Municipio
    '8-PA-2-1234',        // Panamá especial
    '1-GO-3-5678',        // Gobierno
    
    // Especiales nuevos
    'P-1234567',          // Pasaporte
    'PI-9876543',         // Población Indígena
    'R-1234-5678',        // Residencia
    'SR-1234-5678',       // Sin Renta
    'F-1234-5678',        // Formalizado
    'TPH-1234-5678',      // Trabajador Por Horas
    'PC-1234-5678',       // Profesional Contratista
    
    // Antes de Vigencia
    '8AV-123-456',        // Antes de vigencia
    '1PI-123-456',        // Población Indígena (sufijo)
    
    // Consumidor
    '0-0-0',              // Consumidor final
];

foreach ($personasNaturales as $ruc) {
    $tipo = determineRucType($ruc);
    assert($tipo === '1', "RUC {$ruc} debe ser tipo '1' (persona natural)");
}
```

### Empresas Jurídicas (debe retornar '2')

```php
$empresas = [
    // Empresas con diferentes longitudes
    '155591465-2-2015',   // 9 dígitos
    '1858809-1-715011',   // 7 dígitos
    '257995-1-404135',    // 6 dígitos
    '47861-59-308829',    // 5 dígitos
    '4131-20-56974',      // 4 dígitos
    '100-228-41048',      // 3 dígitos
    '17-129-557',         // 2 dígitos (14+)
    
    // Casos específicos
    '17092-2-161234',     // S.A.
    '284522-1-407802',    // INC
];

foreach ($empresas as $ruc) {
    $tipo = determineRucType($ruc);
    assert($tipo === '2', "RUC {$ruc} debe ser tipo '2' (empresa)");
}
```

### Casos Especiales para Validar

```php
// ⚠️ IMPORTANTE: Diferenciar entre formato similar
'1-PI-123-456'    // ❓ ¿Es 1PI (provincia con PI) o 1-PI-... (4 partes)?
'8-PI-123-456'    // ❓ Similar

// Solución: Validar número de partes
if (count($parts) === 3 && preg_match("/^\d+PI$/", $parts[0])) {
    // Es provinciaPI-libro-tomo
} elseif (count($parts) === 4 && $parts[1] === 'PI') {
    // Es provincia-PI-...-... (formato especial no documentado)
}
```

---

## 📏 Tamaño Máximo de RUCs Personas Naturales

### Análisis por Formato

| Formato | Ejemplo Máximo | Longitud |
|---------|---------------|----------|
| Cédula Regular | `13-1234-123456` | 15 |
| Extranjero | `E-1234-123456` | 14 |
| No Tributario | `123-NT-123-1234567890` | 23 ⚠️ |
| Pasaporte | `P-1234567890` | 13 |
| Población Indígena | `PI-1234567890` | 14 |
| Naturalizado | `N-1234-123456` | 14 |
| Residencia | `R-1234-123456` | 14 |
| Sin Renta | `SR-1234-123456` | 15 |
| Formalizado | `F-1234-123456` | 14 |
| TPH | `TPH-1234-123456` | 16 |
| PC | `PC-1234-123456` | 15 |

**Máximo teórico:** 23 caracteres (formato NT con todos los dígitos máximos)

---

## 🎯 Validación en CreateSaleAciCloudRequest.php

### Estado Actual

```php
'dGen.gDatRec.gRucRec.dRuc' => [
    'string',
    'max:20',  // ⚠️ Puede ser insuficiente
]
```

### Recomendación

```php
'dGen.gDatRec.gRucRec.dRuc' => [
    'string',
    'max:30',  // ✅ Cubre todos los formatos con margen
]
```

**Justificación:**
- Máximo teórico personas naturales: 23 caracteres (NT)
- Máximo encontrado empresas: 25 caracteres (del ListadoRuc.pdf)
- Límite recomendado: 30 (margen de seguridad 20%)

---

## 📚 Actualización del Método `panamaID()` en HelperArray

### Propuesta para Helper

El método `panamaID()` en `vendor/weirdo/helper/src/Helper/Traits/HelperArray.php` debe actualizarse para incluir todos los formatos:

```php
/**
 * Accepted patterns for Panama Personal IDs:
 * - Regular (provincia-libro-tomo). Ej: 8-765-1234
 * - Extranjero (E-libro-tomo). Ej: E-8-123456
 * - Naturalizado (N-libro-tomo). Ej: N-1234-5678
 * - Pasaporte (P-número). Ej: P-1234567
 * - Población Indígena (PI-número). Ej: PI-9876543
 * - Residencia (R-libro-tomo). Ej: R-1234-5678
 * - Sin Renta (SR-libro-tomo). Ej: SR-1234-5678
 * - Formalizado (F-libro-tomo). Ej: F-1234-5678
 * - Trabajador Por Horas (TPH-libro-tomo). Ej: TPH-1234-5678
 * - Profesional Contratista (PC-libro-tomo). Ej: PC-1234-5678
 * - Gubernamental (provincia-NT-comp-sec). Ej: 13-NT-2-700400
 */
public function panamaID($id, $tomo = 4, $folio = 6, $delimiter = '')
{
    // Patrones base
    $onlyFirst = "^(?:PE|E|N|P|PI|R|SR|F|TPH|PC|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter?$";
    
    // 2 partes: P-1234567, PI-9876543
    $passportPattern = "^(P|PI)$delimiter(\d{3,10})$";
    
    // 3 partes: cédula regular y formatos especiales
    $threePartsPattern = "^(PE|E|N|R|SR|F|TPH|PC|1[0123]?(?:AV|PI)?|[23456789](?:AV|PI)?)$delimiter(\d{1,$tomo})$delimiter(\d{1,$folio})$";
    
    // 4 partes: gubernamental NT/PA/GO/ED/SA
    $governmentalPattern = "^(1[0123]?|[23456789])$delimiter(NT|PA|GO|ED|SA)$delimiter(\d{1,3})$delimiter(\d{1,10})$";

    preg_match(
        "/^P$|{$onlyFirst}|{$passportPattern}|{$threePartsPattern}|{$governmentalPattern}/i",
        $id,
        $matches
    );

    return $matches;
}
```

---

## ✅ Resumen de Correcciones Necesarias

### 1. ACIcloudService.php - Método `determineRucType()`

**Cambio en Regla #1:**
```php
// ANTES
if (preg_match("/^(PE|E|N)$/", $firstPart)) {

// DESPUÉS
if (preg_match("/^(PE|E|N|P|PI|R|SR|F|TPH|PC)$/i", $firstPart)) {
```

### 2. CreateSaleAciCloudRequest.php - Validación

**Cambio:**
```php
// ANTES
'max:20',

// DESPUÉS
'max:30',
```

### 3. HelperArray.php - Método `panamaID()`

**Agregar patrones:**
- ✅ Pasaporte (P)
- ✅ Población Indígena (PI) como prefijo
- ✅ Residencia (R)
- ✅ Sin Renta (SR)
- ✅ Formalizado (F)
- ✅ Trabajador Por Horas (TPH)
- ✅ Profesional Contratista (PC)

---

## 📊 Cobertura Final Esperada

| Categoría | Formatos Cubiertos | Total Formatos | % |
|-----------|-------------------|----------------|---|
| **Personas Naturales** | 11 / 11 | 11 | 100% ✅ |
| **Empresas Jurídicas** | Todas | Todas | 100% ✅ |
| **Tipos dTipoRuc** | 2 / 2 | 2 | 100% ✅ |

---

**Fecha de Análisis:** 2026-02-19  
**Basado en:** Especificación oficial DGI Panamá  
**Analizado por:** Team de Docucenter
