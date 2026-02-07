# ESTÁNDAR GLOBAL - Custom Fields en DocuCenter

**Fecha:** 2026-01-26  
**Revisado por:** Análisis completo de todas las integraciones

---

## 📋 ESTÁNDAR GLOBAL DEFINITIVO

### Campos Custom en Customers_Imp y Customers_Exp

```php
// ESTÁNDAR GLOBAL DocuCenter - Campos Custom:
'Custom_field1' => RUC o PASAPORTE según tipo
'Custom_field2' => DV (solo para clientes con RUC panameño)
'Custom_field3' => TIPO_RECEPTOR ID (1, 2, 3, 4)
'Custom_field4' => Tipo Contribuyente (1=Natural, 2=Jurídica) - SOLO para clientes CON RUC
'Custom_field5' => Código ubicación (provincia-distrito-corregimiento)
```

### Mapeo TIPO_RECEPTOR

| ID | Code | Nombre | Descripción |
|----|------|--------|-------------|
| 1 | 01 | Contribuyente | Empresa con RUC |
| 2 | 02 | Consumidor Final | Persona sin RUC o RUC básico |
| 3 | 03 | Gobierno | Entidades gubernamentales |
| 4 | 04 | Extranjero | Clientes con pasaporte |

---

## 🔍 Análisis por Integración

### 1. QuickBooksOnlineService

**Archivo:** `app/Services/QuickBooksOnlineService.php`

**Lógica:**
```php
// Línea 450-452
// - Custom_field3: TIPO_RECEPTOR ID (1=Contribuyente, 2=Consumidor final, 3=Gobierno, 4=Extranjero)
// - Custom_field4: Tipo de contribuyente (1=Persona Natural, 2=Persona Jurídica)

// Línea 685-686
'Custom_field3' => $tipoReceptor,              // TIPO_RECEPTOR ID: 1=Contribuyente, 2=Consumidor, 3=Gobierno, 4=Extranjero
'Custom_field4' => $tipoContribuyente,         // Tipo contribuyente: 1=Persona Natural, 2=Persona Jurídica
```

**Regla Especial:**
- Para extranjeros (TIPO_RECEPTOR = 4):
  - `Custom_field1` = PASAPORTE
  - `Custom_field2` = null
  - `Custom_field3` = 4
  - `Custom_field4` = **null** (NO tienen tipo contribuyente panameño)
  - `Custom_field5` = null

---

### 2. LightspeedService

**Archivo:** `app/Services/LightspeedService.php`

**Lógica:**
```php
// Línea 106-107
'Custom_field3' => $tipoReceptor,             // TIPO_RECEPTOR ID (1, 2, 3, 4)
'Custom_field4' => !empty($ruc) ? (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc) : null,
```

**Característica:**
- **AUTO-DETECCIÓN** de tipo contribuyente usando `PanamaRucHelper`
- Solo asigna `Custom_field4` si hay RUC válido
- Si no hay RUC → `Custom_field4` = **null**

---

### 3. ShopifyService

**Archivo:** `app/Services/ShopifyService.php`

**Lógica:**
```php
// Línea 95-96
'Custom_field3' => $tipoReceptor,             // TIPO_RECEPTOR ID (1, 2, 3, 4)
'Custom_field4' => $tipoContribuyente,        // Tipo contribuyente (1=Natural, 2=Jurídica)
```

**Característica:**
- Extrae valores desde `OtherValue` usando regex
- Confía en que el cliente envía valores correctos
- No valida ni auto-detecta

---

### 4. MaxgymService

**Archivo:** `app/Services/MaxgymService.php`

**Lógica:**
```php
// Línea 331-332
'Custom_field3' => $receiverType,             // TIPO_RECEPTOR ID (1, 2)
'Custom_field4' => (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc), // Tipo contribuyente AUTO-DETECTADO
```

**Característica:**
- **AUTO-DETECCIÓN** automática del tipo contribuyente
- Solo maneja tipos 1 y 2 (no extranjeros)
- Siempre detecta desde RUC

---

### 5. MeyparService

**Archivo:** `app/Services/MeyparService.php`

**Lógica:**
```php
// Línea 738-739 (createDefaultClient para compras)
'Custom_field3' => $receiverType,             // TIPO_RECEPTOR ID (1=Empresa, 2=Persona)
'Custom_field4' => $receiverType == 1 ? '2' : '1', // Tipo contribuyente: 1=Empresa→2, 2=Persona→1

// Línea 1242-1243 (storeOrder para ventas)
'Custom_field3' => $receiverType,             // TIPO_RECEPTOR ID (1=Empresa, 2=Persona)
'Custom_field4' => null,                      // Tipo contribuyente (no disponible en Meypar)
```

**Característica:**
- Mapeo especial Meypar: Empresa (1) → Contribuyente tipo 2 (Jurídica)
- En ventas NO asigna tipo contribuyente (null)

---

### 6. Kart21Service

**Archivo:** `app/Services/Kart21Service.php`

**Lógica:**
```php
// Línea 224-225
'Custom_field3' => 2,                     // TIPO_RECEPTOR ID: 2=Consumidor final (default)
'Custom_field4' => null,                  // Tipo contribuyente (no disponible)
```

**Característica:**
- Siempre asume Consumidor Final
- NO maneja tipo contribuyente

---

## 📊 Resumen de Reglas

### Regla 1: Custom_field4 SOLO para clientes CON RUC

**Todas las integraciones coinciden:**
- Si tiene RUC válido → Detectar o asignar tipo contribuyente
- Si NO tiene RUC → `Custom_field4` = **null**

### Regla 2: Extranjeros (TIPO_RECEPTOR = 4) NUNCA tienen Custom_field4

**Razón:**
- Custom_field4 representa "Tipo de contribuyente PANAMEÑO"
- Extranjeros NO son contribuyentes panameños
- No tienen RUC panameño
- Por tanto: `Custom_field4` = **null**

### Regla 3: Auto-detección cuando es posible

**Servicios que auto-detectan:**
- ✅ Lightspeed: `PanamaRucHelper::detectContributorType()`
- ✅ Maxgym: `PanamaRucHelper::detectContributorType()`
- ✅ QuickBooks: Lógica propia + `PanamaRucHelper` en algunos casos

**Servicios que NO auto-detectan:**
- ❌ Shopify: Confía en datos del cliente
- ❌ Kart21: No maneja tipo contribuyente
- ❌ Meypar: Mapeo especial propio

---

## 🚨 PROBLEMA IDENTIFICADO EN CREATEFAST

### Código INCORRECTO Actual (Después de mi cambio)

```php
// app/Http/Livewire/Admin/Einvoice/CreateFast.php - Línea 138
// Solo Contribuyentes (tipo 1) tienen tipoContribuyente panameño
// Extranjeros (tipos 3 y 4) NO tienen tipo contribuyente panameño
$this->receptor_tipoContribuyente = ($this->receptor_tipo === '1') 
    ? $this->customer->Custom_field4 
    : null;
```

**Problema:** Esto es INCORRECTO según el estándar

### Código CORRECTO según Estándar

```php
// Create.php - Línea 507 (CORRECTO)
// ESTÁNDAR GLOBAL: Custom_field4 = tipo contribuyente, Custom_field5 = ubicación
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '4']) 
    ? $customer->Custom_field4 
    : null;
```

### Análisis de Create.php vs CreateFast.php

**Create.php (app/Http/Livewire/Admin/Einvoice/Create.php):**
```php
// Línea 2330-2352
switch ($this->receptor_tipo) {
    case '1': // Contribuyente
    case '2': // Consumidor final
    case '4': // Gobierno
        $customerData['Custom_field1'] = $this->receptor_ruc;
        $customerData['Custom_field2'] = $this->receptor_DV;
        $customerData['Custom_field4'] = $this->receptor_tipoContribuyente;
        $customerData['Country'] = 'PA';
        // Código de ubicación...
        break;

    case '3': // Extranjero
        $customerData['Custom_field1'] = $this->receptor_pasaporteIdentidadExtranjera;
        $customerData['Custom_field2'] = null; // Extranjeros no tienen DV
        // NO asigna Custom_field4
        break;
}
```

**Diferencia entre tipos:**
- Tipo 1 (Contribuyente): ✅ Custom_field4 con tipoContribuyente
- Tipo 2 (Consumidor): ✅ Custom_field4 con tipoContribuyente
- Tipo 3 (Extranjero): ❌ NO Custom_field4 (null)
- Tipo 4 (Gobierno): ✅ Custom_field4 con tipoContribuyente

---

## ✅ CORRECCIÓN NECESARIA

### REVERTIR cambio en CreateFast.php línea 138

**De (INCORRECTO):**
```php
$this->receptor_tipoContribuyente = ($this->receptor_tipo === '1') 
    ? $this->customer->Custom_field4 
    : null;
```

**A (CORRECTO según estándar):**
```php
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4']) 
    ? $this->customer->Custom_field4 
    : null;
```

### MANTENER cambios en:

1. ✅ **Validación tipo 4** - Correcto: extranjeros solo necesitan pasaporte
2. ✅ **storeCustomerData()** - Correcto: manejo diferenciado por tipo
3. ✅ **QuickBooksOnlineService** - Correcto: null para extranjeros

---

## 📝 Conclusión Final

### Estándar DEFINITIVO para Custom_field4:

| TIPO_RECEPTOR | Nombre | Custom_field4 | Razón |
|---------------|--------|---------------|-------|
| 1 | Contribuyente | 1 o 2 | Tiene RUC panameño |
| 2 | Consumidor Final | 1 o 2 | Puede tener RUC panameño |
| 3 | Extranjero | **null** | NO tiene RUC panameño |
| 4 | Gobierno | 1 o 2 | Tiene RUC panameño |

### Regla General:
**Custom_field4 se asigna SI Y SOLO SI el cliente tiene RUC panameño válido**

- Tipos 1, 2, 4: Clientes PANAMEÑOS → Pueden tener Custom_field4
- Tipo 3: Clientes EXTRANJEROS → NUNCA tienen Custom_field4 (siempre null)

### En CreateFast/Create:
```php
// Leer de BD:
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4']) 
    ? $customer->Custom_field4 
    : null;

// Guardar en BD:
switch ($this->receptor_tipo) {
    case '1': // Contribuyente
    case '2': // Consumidor
    case '4': // Gobierno
        $customerData['Custom_field4'] = $this->receptor_tipoContribuyente;
        break;
    case '3': // Extranjero
        $customerData['Custom_field4'] = null;
        break;
}
```
