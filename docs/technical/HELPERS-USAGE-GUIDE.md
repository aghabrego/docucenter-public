# Guía de Uso: ReceiverTypeHelper y CustomFieldsValidator

**Fecha**: 2026-01-26  
**Versión**: 1.0  
**Propósito**: Documentar uso de helpers para validación y normalización de Custom Fields

---

## 1. ReceiverTypeHelper

Helper centralizado para manejo de Tipos de Receptor en facturación electrónica panameña.

### 1.1. Características Principales

- ✅ **Mapeo CODE ↔ ID**: Convierte entre códigos DGI (01-04) e IDs de base de datos
- ✅ **Cache automático**: Consultas a BD con cache de 1 hora
- ✅ **Multi-ambiente**: Resiliente entre dev/staging/prod
- ✅ **Auto-detección**: Detecta tipo receptor basado en datos disponibles

### 1.2. Constantes Disponibles

```php
use App\Helpers\ReceiverTypeHelper;

ReceiverTypeHelper::CONTRIBUYENTE_CODE;      // '01'
ReceiverTypeHelper::CONSUMIDOR_FINAL_CODE;   // '02'
ReceiverTypeHelper::GOBIERNO_CODE;           // '03'
ReceiverTypeHelper::EXTRANJERO_CODE;         // '04'
```

### 1.3. Métodos Principales

#### Obtener IDs (consultan BD con cache)

```php
// Obtener ID de Contribuyente
$id = ReceiverTypeHelper::getContribuyenteId();  // Retorna: 1

// Obtener ID de Consumidor Final
$id = ReceiverTypeHelper::getConsumidorFinalId(); // Retorna: 2

// Obtener ID de Extranjero
$id = ReceiverTypeHelper::getExtranjeroId();  // Retorna: 3

// Obtener ID de Gobierno
$id = ReceiverTypeHelper::getGobiernoId();  // Retorna: 4
```

#### Mapeo CODE → ID

```php
// Normaliza CODEs a IDs de BD
$id = ReceiverTypeHelper::mapCodeToId('01');  // Retorna: 1
$id = ReceiverTypeHelper::mapCodeToId('04');  // Retorna: 3 (Extranjero)
$id = ReceiverTypeHelper::mapCodeToId('03');  // Retorna: 4 (Gobierno)

// Acepta valores sin padding
$id = ReceiverTypeHelper::mapCodeToId('1');   // Retorna: 1
$id = ReceiverTypeHelper::mapCodeToId('4');   // Retorna: 3

// Valores inválidos retornan Consumidor Final
$id = ReceiverTypeHelper::mapCodeToId('99');  // Retorna: 2
```

#### Mapeo ID → CODE

```php
// Convierte IDs a CODEs normativa DGI
$code = ReceiverTypeHelper::mapIdToCode(1);  // Retorna: '01'
$code = ReceiverTypeHelper::mapIdToCode(3);  // Retorna: '04' (Extranjero)
$code = ReceiverTypeHelper::mapIdToCode(4);  // Retorna: '03' (Gobierno)
```

#### Auto-Detección

```php
// Detecta tipo receptor automáticamente
$tipoReceptor = ReceiverTypeHelper::detectReceiverType(
    $ruc,        // RUC o null
    $pasaporte,  // Pasaporte o null
    $country     // País o null
);

// Ejemplos:
ReceiverTypeHelper::detectReceiverType(null, 'ABC123', null);     // 3 (Extranjero por pasaporte)
ReceiverTypeHelper::detectReceiverType(null, null, 'US');         // 3 (Extranjero por país)
ReceiverTypeHelper::detectReceiverType('8-123-4567', null, 'PA'); // 1 (Contribuyente por RUC)
ReceiverTypeHelper::detectReceiverType(null, null, null);         // 2 (Consumidor Final por defecto)
```

#### Validaciones

```php
// Verificar si requiere tipo contribuyente
$needsTipo = ReceiverTypeHelper::needsTipoContribuyente(1);  // true (Contribuyente)
$needsTipo = ReceiverTypeHelper::needsTipoContribuyente(3);  // false (Extranjero)

// Verificar si es extranjero
$isExtranjero = ReceiverTypeHelper::isExtranjero(3);  // true
$isExtranjero = ReceiverTypeHelper::isExtranjero(1);  // false

// Verificar si requiere RUC
$needsRuc = ReceiverTypeHelper::needsRuc(1);  // true (Contribuyente)
$needsRuc = ReceiverTypeHelper::needsRuc(2);  // false (Consumidor Final)

// Verificar si requiere pasaporte
$needsPasaporte = ReceiverTypeHelper::needsPasaporte(3);  // true (Extranjero)
$needsPasaporte = ReceiverTypeHelper::needsPasaporte(1);  // false (Contribuyente)
```

#### Utilidades

```php
// Obtener nombre del tipo
$nombre = ReceiverTypeHelper::getName(1);  // 'Contribuyente'
$nombre = ReceiverTypeHelper::getName(3);  // 'Extranjero'

// Obtener país por defecto
$pais = ReceiverTypeHelper::getDefaultCountry(1);  // 'PA'
$pais = ReceiverTypeHelper::getDefaultCountry(3);  // 'US' (Extranjero)

// Limpiar cache (si se modifica tabla)
ReceiverTypeHelper::clearCache();
```

### 1.4. Ejemplos de Uso en Servicios

#### QuickBooksOnlineService

```php
// Normalizar TIPO_RECEPTOR desde request
$tipoReceptorFromRequest = $request->get('TIPO_RECEPTOR');  // '04'
$tipoReceptor = ReceiverTypeHelper::mapCodeToId($tipoReceptorFromRequest);  // 3

// Validar si es extranjero
if (ReceiverTypeHelper::isExtranjero($tipoReceptor)) {
    $ruc = null;
    $pasaporte = $request->get('PASAPORTE');
    $country = ReceiverTypeHelper::getDefaultCountry($tipoReceptor);  // 'US'
}

// Crear cliente
$customer = CustomersImp::create([
    'Custom_field1' => $pasaporte,
    'Custom_field2' => null,
    'Custom_field3' => $tipoReceptor,  // 3
    'Custom_field4' => null,
    'Custom_field5' => null,
    'Country' => $country
]);
```

#### UpdateIntuitOrdersJob

```php
// Crear cliente auto-creado como extranjero
$customer = Customer::create([
    'Name' => $invoiceData['CustomerRef']['name'],
    'Custom_field1' => 'QB-' . $invoiceData['CustomerRef']['value'],
    'Custom_field2' => null,
    'Custom_field3' => ReceiverTypeHelper::getExtranjeroId(),  // 3
    'Custom_field4' => null,
    'Custom_field5' => null,
    'Country' => 'US'
]);
```

---

## 2. CustomFieldsValidator

Validador centralizado para todos los Custom Fields de un cliente.

### 2.1. Características

- ✅ **Validación completa**: Verifica consistencia de todos los custom fields
- ✅ **Normalización automática**: Mapea CODEs a IDs usando ReceiverTypeHelper
- ✅ **Auto-detección**: Detecta valores faltantes basado en datos disponibles
- ✅ **Logging detallado**: Registra validaciones y normalizaciones
- ✅ **Auto-corrección**: Aplica correcciones automáticas a datos inconsistentes

### 2.2. Métodos Principales

#### validateCustomerFields()

Valida y normaliza todos los custom fields de un cliente.

```php
use App\Helpers\CustomFieldsValidator;

$data = [
    'ruc' => '8-123-4567',
    'dv' => '01',
    'tipo_receptor' => '01',  // CODE o ID
    'tipo_contribuyente' => '1',
    'ubicacion' => '8-1-1',
    'country' => 'PA'
];

$validated = CustomFieldsValidator::validateCustomerFields($data, 'QuickBooks');

// Resultado:
// [
//     'Custom_field1' => '8-123-4567',
//     'Custom_field2' => '01',
//     'Custom_field3' => 1,   // Normalizado a ID
//     'Custom_field4' => '1',
//     'Custom_field5' => '8-1-1'
// ]
```

**Parámetros**:
- `$data` (array): Datos del cliente con keys flexibles
- `$source` (string): Fuente de datos para logging (ej: 'QuickBooks', 'Shopify')

**Keys soportadas** (flexibles):
- `ruc` o `Custom_field1`
- `dv` o `Custom_field2`
- `tipo_receptor` o `Custom_field3`
- `tipo_contribuyente` o `Custom_field4`
- `ubicacion` o `Custom_field5`
- `pasaporte`
- `country` o `Country`

#### checkConsistency()

Verifica si los custom fields son consistentes.

```php
$customFields = [
    'Custom_field1' => '8-123-4567',
    'Custom_field2' => '01',
    'Custom_field3' => 1,
    'Custom_field4' => '1',
    'Custom_field5' => '8-1-1'
];

$result = CustomFieldsValidator::checkConsistency($customFields);

// Resultado:
// [
//     'is_consistent' => true,
//     'issues' => [],
//     'receiver_type' => 'Contribuyente',
//     'receiver_type_id' => 1
// ]
```

**Validaciones realizadas**:
- Custom_field3 (TIPO_RECEPTOR) no está vacío
- Extranjeros tienen pasaporte en Custom_field1
- Extranjeros NO tienen DV, tipo contribuyente ni ubicación
- Nacionales tienen RUC y tipo contribuyente

#### autoFix()

Aplica correcciones automáticas a custom fields inconsistentes.

```php
$customFields = [
    'Custom_field1' => 'ABC123',  // Pasaporte
    'Custom_field2' => '01',      // ❌ Extranjero no debe tener DV
    'Custom_field3' => 3,         // Extranjero
    'Custom_field4' => '1',       // ❌ Extranjero no debe tener tipo contribuyente
    'Custom_field5' => '8-1-1'    // ❌ Extranjero no debe tener ubicación
];

$fixed = CustomFieldsValidator::autoFix($customFields);

// Resultado:
// [
//     'Custom_field1' => 'ABC123',
//     'Custom_field2' => null,  // ✓ Limpiado
//     'Custom_field3' => 3,
//     'Custom_field4' => null,  // ✓ Limpiado
//     'Custom_field5' => null   // ✓ Limpiado
// ]
```

### 2.3. Ejemplos de Uso

#### ACIcloudService

```php
// En método customersImp()
$customFields = CustomFieldsValidator::validateCustomerFields([
    'ruc' => $request->get('RUC'),
    'dv' => $request->get('DV'),
    'tipo_receptor' => $request->get('Custom_field3'),
    'tipo_contribuyente' => $request->get('Custom_field4'),
    'ubicacion' => $request->get('Custom_field5'),
    'country' => $request->get('Country')
], 'ACIcloud');

$customer = CustomersImp::create(array_merge([
    'ID_compania' => $company->ID_compania,
    'Customer_Bill_Name' => $request->get('Customer_Bill_Name'),
    'CustomerID' => $request->get('CustomerID'),
], $customFields));
```

#### Validar Cliente Existente

```php
// Obtener cliente
$customer = CustomersImp::find($customerId);

// Verificar consistencia
$result = CustomFieldsValidator::checkConsistency([
    'Custom_field1' => $customer->Custom_field1,
    'Custom_field2' => $customer->Custom_field2,
    'Custom_field3' => $customer->Custom_field3,
    'Custom_field4' => $customer->Custom_field4,
    'Custom_field5' => $customer->Custom_field5,
]);

if (!$result['is_consistent']) {
    Log::warning('Cliente con Custom Fields inconsistentes', [
        'customer_id' => $customer->CustomerID,
        'issues' => $result['issues']
    ]);
    
    // Aplicar corrección
    $fixed = CustomFieldsValidator::autoFix([...]);
    $customer->update($fixed);
}
```

---

## 3. Comando Artisan

### 3.1. Uso Básico

```bash
# Validar todos los clientes
php artisan customers:validate-fields

# Validar organización específica
php artisan customers:validate-fields --organization=1

# Validar con detalles
php artisan customers:validate-fields --verbose

# Aplicar correcciones automáticas
php artisan customers:validate-fields --fix

# Limitar número de clientes por organización
php artisan customers:validate-fields --limit=50
```

### 3.2. Ejemplos con Docker

```bash
# En desarrollo
docker exec docucenter_laravel.test php artisan customers:validate-fields --verbose

# En producción (validar primero sin fix)
docker exec docucenter_laravel.test php artisan customers:validate-fields --organization=5 --verbose

# Aplicar correcciones solo a una organización
docker exec docucenter_laravel.test php artisan customers:validate-fields --organization=5 --fix
```

### 3.3. Salida del Comando

```
🔍 Iniciando validación de Custom Fields...

📂 Organización: Mi Empresa (ID: 1)
   Base de datos: 9_734_1672_56
   Total clientes: 150

   ⚠️  Cliente: 8-123-4567 - Juan Pérez
      Tipo Receptor: Contribuyente (ID: 1)
      • Contribuyente/Gobierno sin tipo contribuyente en Custom_field4

   ⚠️  Cliente: ABC123 - Foreign Corp
      Tipo Receptor: Extranjero (ID: 3)
      • Extranjero no debe tener DV en Custom_field2

   📊 Resultados:
      - Problemas encontrados: 2
      - Corregidos: 2

═══════════════════════════════════════════════════
📈 RESUMEN FINAL
═══════════════════════════════════════════════════
Organizaciones procesadas: 1
Clientes revisados: 150
Problemas encontrados: 2
Correcciones aplicadas: 2
```

---

## 4. Best Practices

### 4.1. Al Crear Clientes

```php
// ✅ CORRECTO: Usar CustomFieldsValidator
$validated = CustomFieldsValidator::validateCustomerFields($data, 'Integration');
$customer = CustomersImp::create($validated);

// ❌ INCORRECTO: Guardar valores directos
$customer = CustomersImp::create([
    'Custom_field3' => $request->get('TipoReceptor')  // Puede ser CODE o ID
]);
```

### 4.2. Al Mapear CODEs

```php
// ✅ CORRECTO: Usar ReceiverTypeHelper
$tipoReceptor = ReceiverTypeHelper::mapCodeToId($tipoReceptorCode);

// ❌ INCORRECTO: Mapeo manual
$tipoReceptor = (int)ltrim($tipoReceptorCode, '0');  // '04' → 4 (INCORRECTO)
```

### 4.3. Al Validar Tipos

```php
// ✅ CORRECTO: Usar métodos del helper
if (ReceiverTypeHelper::isExtranjero($tipoReceptor)) {
    // Lógica extranjeros
}

// ❌ INCORRECTO: Comparación hardcoded
if ($tipoReceptor === 4) {  // Asume ID=4 es extranjero (INCORRECTO)
    // Lógica extranjeros
}
```

---

## 5. Troubleshooting

### 5.1. Cliente con TIPO_RECEPTOR incorrecto

**Síntoma**: Cliente extranjero guardado con Custom_field3=4 en lugar de 3

**Solución**:
```php
// Validar y corregir
$customFields = [
    'Custom_field1' => $customer->Custom_field1,
    'Custom_field2' => $customer->Custom_field2,
    'Custom_field3' => $customer->Custom_field3,
    'Custom_field4' => $customer->Custom_field4,
    'Custom_field5' => $customer->Custom_field5,
];

$fixed = CustomFieldsValidator::autoFix($customFields);
$customer->update($fixed);
```

### 5.2. Cache desactualizado

**Síntoma**: IDs no coinciden después de modificar tabla Receivertype

**Solución**:
```php
// Limpiar cache
ReceiverTypeHelper::clearCache();

// O desde artisan
php artisan cache:clear
```

---

## 6. Migración de Código Existente

### 6.1. Antes (QuickBooksOnlineService)

```php
// ❌ Código antiguo con mapeo incorrecto
$tipoReceptorValue = (int)ltrim((string)$tipoReceptorFromRequest, '0');

if ($tipoReceptor === 4) {  // Asume 4 es extranjero
    $pasaporte = $pasaporteFromRequest;
}
```

### 6.2. Después (con helpers)

```php
// ✅ Código nuevo con ReceiverTypeHelper
$tipoReceptor = ReceiverTypeHelper::mapCodeToId($tipoReceptorFromRequest);

if (ReceiverTypeHelper::isExtranjero($tipoReceptor)) {
    $pasaporte = $pasaporteFromRequest;
}
```

---

## 7. Referencias

- [ANALISIS-CUSTOM-FIELDS-INTEGRACIONES.md](ANALISIS-CUSTOM-FIELDS-INTEGRACIONES.md)
- [MEYPAR-COLOMBIA-LOGIC.md](MEYPAR-COLOMBIA-LOGIC.md)
- ReceiverTypeHelper: `app/Helpers/ReceiverTypeHelper.php`
- CustomFieldsValidator: `app/Helpers/CustomFieldsValidator.php`
- Comando validación: `app/Console/Commands/ValidateCustomerFieldsCommand.php`
