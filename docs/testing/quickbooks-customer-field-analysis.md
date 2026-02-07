# Análisis de Procesamiento Custom_field3 y Custom_field4 en QuickBooks

**Fecha:** 2026-01-26  
**Contexto:** Análisis del procesamiento de campos personalizados para cliente extranjero

---

## 📋 Objeto de Prueba - Invoice 1930

```json
{
  "CustomerRef": {
    "value": "575",
    "name": "116",
    "RUC": null,
    "DV": null,
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "CNBZT153H920",
    "CompanyName": null,
    "DisplayName": "116",
    "PrimaryEmail": "mv@jtcpac.com",
    "BillAddr": []
  },
  "TotalAmt": 1632.55
}
```

### Características del Cliente:
- ✅ **PASAPORTE:** CNBZT153H920
- ✅ **TIPO_RECEPTOR:** "04" (Extranjero)
- ❌ **RUC:** null
- ❌ **DV:** null
- ❌ **TIPO:** No presente en CustomerRef
- ❌ **CompanyName:** null

---

## 🔍 Flujo de Procesamiento Actual

### Paso 1: Extracción de Datos (storeOrder)

```php
// Líneas 814-824
$customerRef = $invoiceData['CustomerRef'] ?? [];
$customerName = !empty($customerRef['CompanyName'])
    ? $customerRef['CompanyName']
    : ($customerRef['name'] ?? 'QuickBooks Customer');
```

**Resultado:**
- `$customerName` = "116" (porque CompanyName es null, usa 'name')

### Paso 2: Determinación de TIPO_RECEPTOR (storeOrder)

```php
// Líneas 826-836
$originalCountry = array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA'));
$tipoReceptorRaw = array_get($customerRef, 'TIPO_RECEPTOR', 2);

// Normalizar a ID si viene como código '01','02','03','04'
$tipoReceptor = is_string($tipoReceptorRaw) ? (int)ltrim($tipoReceptorRaw, '0') : (int)$tipoReceptorRaw;
```

**Resultado:**
- `$tipoReceptorRaw` = "04"
- `$tipoReceptor` = **4** (Extranjero) ✅

**Lógica de País:**
```php
// Líneas 837-839
$isNationalClient = in_array($tipoReceptor, [1, 2, 3]);
$correctedCountry = $isNationalClient ? 'PA' : $originalCountry;
```

**Resultado:**
- `$isNationalClient` = false
- `$correctedCountry` = País original (no 'PA') ✅

### Paso 3: Llamada a createDefaultClient

```php
// Líneas 860-873
$customer = $this->createDefaultClient($organization, [
    'CustomerID' => $customerId,
    'Customer_Bill_Name' => $customerName,
    'AddressLine1' => array_get($customerRef, 'BillAddr.Line1', ''),
    'AddressLine2' => array_get($customerRef, 'BillAddr.Line2', ''),
    'Country' => $correctedCountry,
    'Email' => $email,
    'Ruc' => array_get($customerRef, 'RUC'),
    'Dv' => array_get($customerRef, 'DV'),
    'TIPO' => array_get($customerRef, 'TIPO'),
    'TIPO_RECEPTOR' => array_get($customerRef, 'TIPO_RECEPTOR'),
    'LocationCode' => array_get($customerRef, 'LocationCode'),
    'PASAPORTE' => array_get($customerRef, 'PASAPORTE'),
]);
```

**Datos enviados:**
```php
[
    'Ruc' => null,
    'Dv' => null,
    'TIPO' => null,
    'TIPO_RECEPTOR' => "04",
    'PASAPORTE' => "CNBZT153H920",
    'Country' => '?' // Depende del BillAddr
]
```

---

## 🔧 Procesamiento en createDefaultClient

### Fase 1: Inicialización de Variables

```php
// Líneas 480-486
$ruc = '';
$dv = '';
$tipoContribuyente = '2'; // ⚠️ Default: Persona Natural
$locationCode = null;
$pasaporte = null;
```

**Estado Inicial:**
- `$tipoContribuyente` = **'2'** (Persona Natural - Default)

### Fase 2: Extracción de RUC/DV (Regex)

```php
// Líneas 488-503
if (preg_match('/Ruc:(\d+-\d+-\d+)|.../', $rucOriginal, $matches)) {
    $ruc = end($filteredMatches);
}
if (preg_match('/Dv:(\d{2})|.../', $dvOriginal, $matches)) {
    $dv = end($filteredMatches);
}
```

**Resultado:**
- `$ruc` = '' (vacío, porque RUC es null)
- `$dv` = '' (vacío, porque DV es null)

### Fase 3: Extracción TIPO desde PrimaryTaxIdentifier

```php
// Líneas 505-526
if (is_null($tipoFromRequest)) {
    $primaryTaxIdentifier = array_get($request, 'PrimaryTaxIdentifier', '');
    if (!empty($primaryTaxIdentifier) && preg_match('/TIPO:(\d{2})/', $primaryTaxIdentifier, $matches)) {
        // Lógica de extracción...
    }
}
```

**Resultado:**
- `$tipoFromRequest` = **null** (no está presente en CustomerRef)

### Fase 4: Determinación de tipoContribuyente

```php
// Líneas 528-596
if (!is_null($tipoFromRequest)) {
    // ❌ Este bloque NO se ejecuta porque $tipoFromRequest es null
} else {
    // ✅ Entra aquí - Lógica legacy
    if (!empty($ruc) && !empty($dv)) {
        // ❌ NO entra porque $ruc y $dv están vacíos
        $tipoContribuyente = (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc);
    }
    // ⚠️ $tipoContribuyente queda con el valor default: '2'
}
```

**Resultado:**
- `$tipoContribuyente` = **'2'** (Persona Natural - Default) ⚠️

### Fase 5: Determinación de TIPO_RECEPTOR

```php
// Líneas 598-620
$tipoReceptor = 2; // Default: Consumidor final

$tipoReceptorFromRequest = array_get($request, 'TIPO_RECEPTOR'); // "04"
if (!is_null($tipoReceptorFromRequest)) {
    $tipoReceptorValue = (int)ltrim((string)$tipoReceptorFromRequest, '0'); // 4
    if (in_array($tipoReceptorValue, [1, 2, 3, 4])) {
        $tipoReceptor = $tipoReceptorValue; // 4 ✅
    }
}
```

**Resultado:**
- `$tipoReceptor` = **4** (Extranjero) ✅

### Fase 6: Validación Cruzada para Extranjeros

```php
// Líneas 622-635
if ($tipoReceptor === 4) {
    if (!empty($pasaporteFromRequest)) {
        $pasaporte = $pasaporteFromRequest; // "CNBZT153H920"
        // Limpiar campos que no aplican para extranjeros
        $ruc = null;
        $dv = null;
        $tipoFromRequest = null;
    } else {
        $tipoReceptor = 2;
    }
} else {
    $pasaporte = null;
}
```

**Resultado:**
- `$pasaporte` = **"CNBZT153H920"** ✅
- `$ruc` = **null** ✅
- `$dv` = **null** ✅
- `$tipoFromRequest` = **null** ✅
- ⚠️ **$tipoContribuyente permanece en '2' (NO se limpia)**

### Fase 7: País por Defecto

```php
// Líneas 637-645
$countryFromRequest = array_get($request, 'Country');
$defaultCountry = ($tipoReceptor === 4 ? 'US' : 'PA');
$finalCountry = $countryFromRequest ?? $defaultCountry;
```

**Resultado:**
- `$defaultCountry` = **'US'** (porque tipoReceptor === 4) ✅
- `$finalCountry` = Depende si viene Country en request

---

## 📊 Resultado Final en CustomersImp

```php
// Líneas 655-674
$customer = $modelCustomer->updateOrCreate(
    [
        'CustomerID' => !empty($ruc) ? $ruc : (!empty($pasaporte) ? $pasaporte : ...),
    ],
    [
        'ID_compania' => $company->ID_compania,
        'CustomerID' => 'CNBZT153H920', // ✅ PASAPORTE
        'Customer_Bill_Name' => '116',
        'Country' => 'US', // ✅ o el que venga en request
        'Email' => 'mv@jtcpac.com',
        
        // CAMPOS CRÍTICOS:
        'Custom_field1' => 'CNBZT153H920', // ✅ PASAPORTE (correcto)
        'Custom_field2' => null,           // ✅ null (correcto para extranjero)
        'Custom_field3' => 4,              // ✅ Extranjero (correcto)
        'Custom_field4' => '2',            // ⚠️ Persona Natural (INCORRECTO)
        'Custom_field5' => null,           // ✅ null (correcto, no tiene ubicación PA)
    ]
);
```

---

## 🚨 PROBLEMAS IDENTIFICADOS

### Problema 1: Custom_field4 para Extranjeros

**Estado Actual:**
- Para clientes extranjeros (TIPO_RECEPTOR = 4)
- `Custom_field4` se asigna con valor **'2'** (Persona Natural)
- Este valor proviene del default inicial del método

**Lógica Incorrecta:**
```php
$tipoContribuyente = '2'; // Default al inicio del método

// Más adelante, en la validación de extranjeros:
if ($tipoReceptor === 4) {
    $pasaporte = $pasaporteFromRequest;
    $ruc = null;
    $dv = null;
    $tipoFromRequest = null;
    // ⚠️ FALTA: $tipoContribuyente = null;
}
```

**Problema:**
- NO se está limpiando `$tipoContribuyente` para extranjeros
- Los extranjeros NO tienen tipo contribuyente panameño (no tienen RUC)
- Debería ser **null** para extranjeros

### Problema 2: Inconsistencia Semántica

**Custom_field4** representa "Tipo de contribuyente" panameño:
- 1 = Persona Natural (con RUC panameño)
- 2 = Persona Jurídica (con RUC panameño)

**Para extranjeros:**
- NO tienen RUC panameño
- NO son contribuyentes panameños
- El concepto de "tipo contribuyente" NO aplica

**Valor Esperado:**
- `Custom_field4` debería ser **null** para TIPO_RECEPTOR = 4

---

## ✅ SOLUCIÓN PROPUESTA

### Corrección en Fase 6

```php
// Validación cruzada para extranjeros (TIPO_RECEPTOR: 4)
if ($tipoReceptor === 4) {
    if (!empty($pasaporteFromRequest)) {
        $pasaporte = $pasaporteFromRequest;
        // Limpiar campos que no aplican para extranjeros
        $ruc = null;
        $dv = null;
        $tipoFromRequest = null;
        $tipoContribuyente = null; // ✅ AGREGAR ESTA LÍNEA
        $locationCode = null;      // ✅ OPCIONAL: También limpiar ubicación
    } else {
        $tipoReceptor = 2;
    }
} else {
    $pasaporte = null;
}
```

### Resultado Esperado Después de la Corrección

```php
[
    'Custom_field1' => 'CNBZT153H920', // ✅ PASAPORTE
    'Custom_field2' => null,           // ✅ null (no tiene DV)
    'Custom_field3' => 4,              // ✅ Extranjero
    'Custom_field4' => null,           // ✅ null (no es contribuyente PA) ← CORREGIDO
    'Custom_field5' => null,           // ✅ null (no tiene ubicación PA)
]
```

---

## 📝 Resumen de Campos Custom

### Estándar Global DocuCenter

| Campo | Descripción | Valor para Extranjeros |
|-------|-------------|------------------------|
| **Custom_field1** | RUC (nacionales) / PASAPORTE (extranjeros) | PASAPORTE ✅ |
| **Custom_field2** | DV (solo nacionales) | null ✅ |
| **Custom_field3** | TIPO_RECEPTOR (1-4) | 4 ✅ |
| **Custom_field4** | Tipo contribuyente (1-2) | **null** ⚠️ (actualmente '2') |
| **Custom_field5** | Código ubicación PA | null ✅ |

### Reglas de Validación

**Para TIPO_RECEPTOR = 4 (Extranjeros):**
- ✅ Custom_field1 = PASAPORTE (requerido)
- ✅ Custom_field2 = null
- ✅ Custom_field3 = 4
- ⚠️ Custom_field4 = null (no aplica tipo contribuyente panameño)
- ✅ Custom_field5 = null

**Para TIPO_RECEPTOR = 1, 2, 3 (Nacionales):**
- ✅ Custom_field1 = RUC (requerido)
- ✅ Custom_field2 = DV (requerido)
- ✅ Custom_field3 = TIPO_RECEPTOR (1, 2 o 3)
- ✅ Custom_field4 = Tipo contribuyente (1 o 2, según RUC)
- ✅ Custom_field5 = Código ubicación (provincia-distrito-corregimiento)

---

## 🎯 Caso de Prueba

### Input (Invoice 1930)
```json
{
  "CustomerRef": {
    "RUC": null,
    "DV": null,
    "TIPO": null,
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "CNBZT153H920",
    "name": "116"
  }
}
```

### Output Actual (INCORRECTO)
```php
CustomersImp {
  Custom_field1: "CNBZT153H920" ✅
  Custom_field2: null ✅
  Custom_field3: 4 ✅
  Custom_field4: "2" ❌ <- PROBLEMA
  Custom_field5: null ✅
}
```

### Output Esperado (CORRECTO)
```php
CustomersImp {
  Custom_field1: "CNBZT153H920" ✅
  Custom_field2: null ✅
  Custom_field3: 4 ✅
  Custom_field4: null ✅ <- CORREGIDO
  Custom_field5: null ✅
}
```

---

## 🔍 Logging para Debug

El método ya tiene logging extenso:

```php
Log::info('QuickBooksOnlineService.createDefaultClient: DEBUGGING Country Assignment', [
    'customer_name' => array_get($request, 'Customer_Bill_Name', ''),
    'tipo_receptor' => $tipoReceptor,
    'country_from_request' => $countryFromRequest,
    'final_country' => $finalCountry,
    // ⚠️ AGREGAR para debugging:
    'tipo_contribuyente' => $tipoContribuyente,
    'pasaporte' => $pasaporte,
    'ruc' => $ruc,
    'dv' => $dv
]);
```

---

## 🚀 Acción Recomendada

1. **Modificar línea 633** en `QuickBooksOnlineService.php`
2. Agregar: `$tipoContribuyente = null;` en el bloque de extranjeros
3. Agregar: `$locationCode = null;` para consistencia
4. Ejecutar test con Invoice 1930
5. Verificar que Custom_field4 sea null para extranjeros

**Impacto:**
- ✅ Corrige inconsistencia semántica
- ✅ Alinea con estándar de facturación electrónica panameña
- ✅ No afecta clientes nacionales
- ✅ Mejora validación de datos
