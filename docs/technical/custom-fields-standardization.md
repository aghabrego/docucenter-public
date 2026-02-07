# Estandarización de Custom Fields en DocuCenter

**Fecha:** 2026-01-26  
**Estado:** Implementado  
**Versión:** 3.0

---

## Resumen Ejecutivo

Este documento detalla la implementación del **estándar global para los 5 campos custom** utilizados en DocuCenter para almacenar información crítica de facturación electrónica en Panamá. La estandarización garantiza consistencia entre todas las integraciones (QuickBooks, Shopify, Lightspeed, Maxgym, Meypar, Kart21, ACIcloud) y los módulos de emisión de documentos.

## Definición del Estándar Global

### Campos Custom en Tablas Customers_Imp y Customers_Exp

```php
// ESTÁNDAR GLOBAL DocuCenter - Campos Custom:
'Custom_field1' => RUC o PASAPORTE según tipo
'Custom_field2' => DV (solo para clientes con RUC panameño)
'Custom_field3' => TIPO_RECEPTOR ID (1, 2, 3, 4)
'Custom_field4' => Tipo Contribuyente (1=Natural, 2=Jurídica) - SOLO para clientes CON RUC
'Custom_field5' => Código ubicación (provincia-distrito-corregimiento)
```

### Mapeo TIPO_RECEPTOR

| ID | Code | Nombre | Descripción | Custom_field4 |
|----|------|--------|-------------|---------------|
| 1 | 01 | Contribuyente | Empresa con RUC | 1 o 2 |
| 2 | 02 | Consumidor Final | Persona sin RUC o RUC básico | 1 o 2 |
| 3 | 04 | Extranjero | Clientes con pasaporte | **null** |
| 4 | 03 | Gobierno | Entidades gubernamentales | 1 o 2 |

!!! warning "Importante: Inconsistencia CODE-ID"
    Los CODEs (01-04) son estables según normativa PAC, pero no coinciden directamente con los IDs de base de datos. El código 03 corresponde al ID 4 (Gobierno) y el código 04 corresponde al ID 3 (Extranjero).

---

## Reglas de Negocio

### Regla 1: Custom_field4 SOLO para clientes CON RUC

**Todas las integraciones cumplen:**
- Si tiene RUC válido → Detectar o asignar tipo contribuyente
- Si NO tiene RUC → `Custom_field4 = null`

### Regla 2: Extranjeros (TIPO_RECEPTOR = 3) NUNCA tienen Custom_field4

**Razón:**
- Custom_field4 representa "Tipo de contribuyente PANAMEÑO"
- Extranjeros NO son contribuyentes panameños
- No tienen RUC panameño
- Por tanto: `Custom_field4 = null`

**Ejemplo de cliente extranjero:**
```php
[
    'Custom_field1' => 'ABC123456',      // PASAPORTE
    'Custom_field2' => null,              // Sin DV
    'Custom_field3' => 3,                 // Extranjero
    'Custom_field4' => null,              // Sin tipo contribuyente
    'Custom_field5' => null,              // Sin ubicación
]
```

### Regla 3: Auto-detección cuando es posible

**Servicios que auto-detectan:**
- Lightspeed: `PanamaRucHelper::detectContributorType()`
- Maxgym: `PanamaRucHelper::detectContributorType()`
- QuickBooks: Lógica propia + `PanamaRucHelper` en algunos casos

**Servicios que NO auto-detectan:**
- Shopify: Confía en datos del cliente
- Kart21: No maneja tipo contribuyente
- Meypar: Mapeo especial propio

---

## Implementación por Integración

### 1. QuickBooksOnlineService

**Archivo:** `app/Services/QuickBooksOnlineService.php`

**Características:**
- Detección automática de tipo receptor
- Manejo especial para extranjeros
- Validación de RUC usando PanamaRucHelper

**Implementación:**
```php
// Línea 685-686
'Custom_field3' => $tipoReceptor,
// TIPO_RECEPTOR ID: 1=Contribuyente, 2=Consumidor, 3=Extranjero, 4=Gobierno
'Custom_field4' => $tipoContribuyente,
// Tipo contribuyente: 1=Persona Natural, 2=Persona Jurídica
```

**Regla Especial para Extranjeros:**
```php
// Para extranjeros (TIPO_RECEPTOR = 3):
'Custom_field1' => PASAPORTE
'Custom_field2' => null
'Custom_field3' => 3
'Custom_field4' => null  // NO tienen tipo contribuyente panameño
'Custom_field5' => null
```

### 2. LightspeedService

**Archivo:** `app/Services/LightspeedService.php`

**Características:**
- AUTO-DETECCIÓN de tipo contribuyente usando `PanamaRucHelper`
- Solo asigna `Custom_field4` si hay RUC válido
- Si no hay RUC → `Custom_field4 = null`

**Implementación:**
```php
// Línea 106-107
'Custom_field3' => $tipoReceptor,
// TIPO_RECEPTOR ID (1, 2, 3, 4)
'Custom_field4' => !empty($ruc) 
    ? (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc) 
    : null,
```

### 3. ShopifyService

**Archivo:** `app/Services/ShopifyService.php`

**Características:**
- Extrae valores desde `OtherValue` usando regex
- Confía en que el cliente envía valores correctos
- No valida ni auto-detecta

**Implementación:**
```php
// Línea 95-96
'Custom_field3' => $tipoReceptor,
// TIPO_RECEPTOR ID (1, 2, 3, 4)
'Custom_field4' => $tipoContribuyente,
// Tipo contribuyente (1=Natural, 2=Jurídica)
```

### 4. MaxgymService

**Archivo:** `app/Services/MaxgymService.php`

**Características:**
- AUTO-DETECCIÓN automática del tipo contribuyente
- Solo maneja tipos 1 y 2 (no extranjeros)
- Siempre detecta desde RUC

**Implementación:**
```php
// Línea 331-332
'Custom_field3' => $receiverType,
// TIPO_RECEPTOR ID (1, 2)
'Custom_field4' => (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc),
// Tipo contribuyente AUTO-DETECTADO
```

### 5. MeyparService

**Archivo:** `app/Services/MeyparService.php`

**Características:**
- Mapeo especial Meypar: Empresa (1) → Contribuyente tipo 2 (Jurídica)
- En ventas NO asigna tipo contribuyente (null)

**Implementación:**
```php
// Línea 738-739 (createDefaultClient para compras)
'Custom_field3' => $receiverType,
// TIPO_RECEPTOR ID (1=Empresa, 2=Persona)
'Custom_field4' => $receiverType == 1 ? '2' : '1',
// Tipo contribuyente: 1=Empresa→2, 2=Persona→1

// Línea 1242-1243 (storeOrder para ventas)
'Custom_field3' => $receiverType,
// TIPO_RECEPTOR ID (1=Empresa, 2=Persona)
'Custom_field4' => null,
// Tipo contribuyente (no disponible en Meypar)
```

### 6. Kart21Service

**Archivo:** `app/Services/Kart21Service.php`

**Características:**
- Siempre asume Consumidor Final
- NO maneja tipo contribuyente

**Implementación:**
```php
// Línea 224-225
'Custom_field3' => 2,
// TIPO_RECEPTOR ID: 2=Consumidor final (default)
'Custom_field4' => null,
// Tipo contribuyente (no disponible)
```

---

## Helpers de Validación

### ReceiverTypeHelper

**Archivo:** `app/Helpers/ReceiverTypeHelper.php`

**Propósito:** Helper centralizado para manejo de tipos de receptor y conversión entre CODEs e IDs.

**Funciones Principales:**

```php
// Mapeo CODE → ID (con cache)
ReceiverTypeHelper::mapCodeToId('04') // Retorna 3 (Extranjero)

// Mapeo ID → CODE (con cache)
ReceiverTypeHelper::mapIdToCode(3) // Retorna '04'

// Obtener IDs específicos
ReceiverTypeHelper::getContribuyenteId()    // 1
ReceiverTypeHelper::getConsumidorFinalId()  // 2
ReceiverTypeHelper::getExtranjeroId()       // 3
ReceiverTypeHelper::getGobiernoId()         // 4

// Validaciones
ReceiverTypeHelper::needsTipoContribuyente(3) // false (extranjero)
ReceiverTypeHelper::isExtranjero(3)          // true
ReceiverTypeHelper::needsRuc(1)              // true (contribuyente)
ReceiverTypeHelper::needsPasaporte(3)        // true (extranjero)

// Auto-detección
ReceiverTypeHelper::detectReceiverType($ruc, $pasaporte, $country)
```

**Características:**
- Cache de 1 hora para consultas a BD
- Mapeo consistente entre CODEs (normativa PAC) e IDs (base de datos)
- Validaciones de negocio centralizadas

### CustomFieldsValidator

**Archivo:** `app/Helpers/CustomFieldsValidator.php`

**Propósito:** Validador centralizado para asegurar consistencia entre todas las integraciones.

**Funciones Principales:**

```php
// Validar y normalizar campos
$validated = CustomFieldsValidator::validateCustomerFields([
    'ruc' => '123456-7-890',
    'dv' => '12',
    'tipo_receptor' => '01',
    'tipo_contribuyente' => '2',
    'ubicacion' => '8-1-1'
], 'QuickBooks');

// Resultado:
[
    'Custom_field1' => '123456-7-890',
    'Custom_field2' => '12',
    'Custom_field3' => 1,              // Normalizado a ID
    'Custom_field4' => '2',
    'Custom_field5' => '8-1-1'
]

// Verificar consistencia
$report = CustomFieldsValidator::checkConsistency($customFields);
[
    'is_consistent' => true,
    'issues' => [],
    'receiver_type' => 'Contribuyente',
    'receiver_type_id' => 1
]

// Corrección automática
$fixed = CustomFieldsValidator::autoFix($customFields);
```

**Validaciones Implementadas:**
- Custom_field3 (TIPO_RECEPTOR) no puede estar vacío
- Extranjeros no deben tener DV, tipo contribuyente ni ubicación
- Contribuyentes y Gobierno deben tener RUC y tipo contribuyente
- Auto-detección cuando faltan datos

---

## Módulos de Emisión

### Create.php

**Archivo:** `app/Http/Livewire/Admin/Einvoice/Create.php`

**Lectura de BD:**
```php
// Línea 507
// ESTÁNDAR GLOBAL: Custom_field4 = tipo contribuyente
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4'])
    ? $customer->Custom_field4
    : null;
```

**Escritura en BD:**
```php
// Líneas 2330-2352
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
        $customerData['Custom_field4'] = null; // NO tipo contribuyente
        break;
}
```

### CreateFast.php

**Archivo:** `app/Http/Livewire/Admin/Einvoice/CreateFast.php`

**Implementación idéntica a Create.php:**
```php
// Línea 138
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4'])
    ? $this->customer->Custom_field4
    : null;
```

### CreateFastJob.php

**Archivo:** `app/Jobs/CreateFastJob.php`

**Implementación consistente:**
```php
// Línea 278
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4'])
    ? $this->customer->Custom_field4
    : null;
```

---

## Jobs Asíncronos

### CreateSaleQuickBooksJob

**Archivo:** `app/Jobs/CreateSaleQuickBooksJob.php`

**Comportamiento:**
- Delega completamente a QuickBooksOnlineService
- No manipula Custom_fields directamente
- Usa la lógica validada del servicio

### UpdateIntuitOrdersJob

**Archivo:** `app/Jobs/UpdateIntuitOrdersJob.php`

**Problema Identificado:**
```php
// Custom_field1='auto_created'
// NO establece Custom_field3/4/5
```

**Impacto:** Clientes auto-creados NO pueden emitir facturas correctamente

**Solución Implementada:**
- Usar ReceiverTypeHelper::detectReceiverType() para auto-crear
- Establecer Custom_field3 según detección
- Aplicar CustomFieldsValidator para consistencia

### Invupos CustomerJob

**Archivo:** Similar a UpdateIntuitOrdersJob

**Problema:**
- Custom_field3=null
- Falta detección TIPO_RECEPTOR

**Solución:**
- Aplicar detección automática usando helpers

---

## Testing y Validación

### Tests Unitarios

**ReceiverTypeHelperTest.php**
```php
// Test mapeo CODE → ID
public function test_mapCodeToId()
{
    $this->assertEquals(1, ReceiverTypeHelper::mapCodeToId('01'));
    $this->assertEquals(3, ReceiverTypeHelper::mapCodeToId('04'));
    $this->assertEquals(4, ReceiverTypeHelper::mapCodeToId('03'));
}

// Test validaciones
public function test_needsTipoContribuyente()
{
    $this->assertTrue(ReceiverTypeHelper::needsTipoContribuyente(1));
    $this->assertFalse(ReceiverTypeHelper::needsTipoContribuyente(3));
}
```

**CustomFieldsValidatorTest.php**
```php
// Test validación extranjero
public function test_validateExtranjero()
{
    $result = CustomFieldsValidator::validateCustomerFields([
        'pasaporte' => 'ABC123',
        'tipo_receptor' => '04',
    ], 'test');
    
    $this->assertEquals('ABC123', $result['Custom_field1']);
    $this->assertNull($result['Custom_field2']);
    $this->assertEquals(3, $result['Custom_field3']);
    $this->assertNull($result['Custom_field4']);
}
```

### Casos de Test Críticos

#### Test 1: QuickBooks Extranjero
```
Input: TIPO_RECEPTOR = "04", PASAPORTE = "ABC123"
Esperado:
  - Custom_field1 = "ABC123"
  - Custom_field2 = null
  - Custom_field3 = 3  (ID de Extranjero)
  - Custom_field4 = null
  - Custom_field5 = null
```

#### Test 2: QuickBooks Gobierno
```
Input: TIPO_RECEPTOR = "03", RUC = "1-1-1"
Esperado:
  - Custom_field1 = "1-1-1"
  - Custom_field2 = "01" (si viene)
  - Custom_field3 = 4  (ID de Gobierno)
  - Custom_field4 = "1" o "2"
  - Custom_field5 = código ubicación
```

#### Test 3: Emisión Extranjero
```
Given: Cliente con Custom_field3 = 3
Then:
  - receptor_tipo = "3"
  - receptor_tipoContribuyente = null
  - receptor_pasaporteIdentidadExtranjera = Custom_field1
  - receptor_ruc = null
```

---

## Script de Migración

**Archivo:** `scripts/migrate-custom-fields-global-standard.php`

**Propósito:** Migrar datos existentes al nuevo estándar

```php
// Detectar clientes extranjeros con datos inconsistentes
UPDATE Customers_Imp 
SET Custom_field2 = NULL,
    Custom_field4 = NULL,
    Custom_field5 = NULL
WHERE Custom_field3 = 3;

// Auto-detectar tipo contribuyente faltante
UPDATE Customers_Imp c
SET Custom_field4 = detectContributorType(Custom_field1)
WHERE Custom_field3 IN (1, 2, 4)
  AND Custom_field4 IS NULL
  AND Custom_field1 IS NOT NULL;
```

---

## Tabla de Referencia Rápida

| Integración | Custom_field3 | Auto-detecta CF4 | Prioridad | Estado |
|-------------|---------------|------------------|-----------|--------|
| QuickBooks  | Normaliza CODE→ID | Parcial | CRÍTICO | Implementado |
| Lightspeed  | ID correcto | Sí | - | OK |
| Shopify     | Sin validar | No | ALTO | Usa Helper |
| Maxgym      | ID correcto | Sí | - | OK |
| Kart21      | ID correcto | No | - | OK |
| Meypar      | ID correcto | No | MEDIO | OK |
| ACIcloud    | Sin validar | No | ALTO | Usa Helper |

---

## Documentación de Referencia

### Archivos Fuente Principales

1. `app/Helpers/ReceiverTypeHelper.php` - Mapeo CODEs/IDs
2. `app/Helpers/CustomFieldsValidator.php` - Validación centralizada
3. `app/Helpers/PanamaRucHelper.php` - Detección tipo contribuyente
4. `docs/technical/ESTANDAR-GLOBAL-CUSTOM-FIELDS.md` - Especificación completa
5. `docs/technical/ANALISIS-CUSTOM-FIELDS-INTEGRACIONES.md` - Análisis detallado

### Commits Relevantes

- `7a3f933c` - feat: validaciones Fase 2 completadas
- `141aabb5` - feat: crear ReceiverTypeHelper y corregir Jobs Fase 1
- `36100567` - docs: agregar analisis jobs asincronos custom fields
- `3f17be1a` - fix: aplicar estándar global receptor_tipoContribuyente

---

## Conclusiones

### Beneficios de la Estandarización

1. **Consistencia:** Todos los servicios usan el mismo formato
2. **Validación:** Helpers centralizados previenen errores
3. **Mantenibilidad:** Un solo punto de cambio para reglas de negocio
4. **Testing:** Tests automatizados garantizan corrección
5. **Documentación:** Clara separación entre CODEs (PAC) e IDs (BD)

### Próximos Pasos

1. Monitorear logs de CustomFieldsValidator en producción
2. Migrar datos existentes usando script de migración
3. Agregar validación en formularios de creación manual de clientes
4. Implementar auditoría de cambios en Custom_fields
5. Dashboard de consistencia de datos

---

**Última actualización:** 2026-01-26  
**Autor:** Equipo de DocuCenter  
**Revisado por:** Equipo de Desarrollo
