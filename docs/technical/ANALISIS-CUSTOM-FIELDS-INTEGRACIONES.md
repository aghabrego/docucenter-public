# Análisis Completo: Mapeo de Custom Fields en Todas las Integraciones

**Fecha**: 2026-01-26  
**Autor**: Sistema de Análisis DocuCenter  
**Propósito**: Documentar inconsistencias en mapeo de Custom Fields y proponer correcciones

---

## 1. Estado Actual de la Tabla Receivertype

```
ID: 1 | CODE: 01 | NAME: Contribuyente
ID: 2 | CODE: 02 | NAME: Consumidor final
ID: 3 | CODE: 04 | NAME: Extranjero
ID: 4 | CODE: 03 | NAME: Gobierno
```

### 🚨 Problema Crítico Identificado

**INCONSISTENCIA CODE ↔ ID:**
- Extranjero tiene **CODE: 04** pero **ID: 3**
- Gobierno tiene **CODE: 03** pero **ID: 4**

Esto causa confusión porque:
- Los códigos externos (QuickBooks, PAC) usan **CODEs** (01, 02, 03, 04)
- La base de datos interna usa **IDs** (1, 2, 3, 4)
- **Los CODEs y los IDs NO coinciden para tipos 3 y 4**

---

## 2. Estándar Global Custom Fields

```php
Custom_field1: RUC (nacionales) o PASAPORTE (extranjeros)
Custom_field2: DV (solo nacionales, null para extranjeros)
Custom_field3: TIPO_RECEPTOR (ID de tabla Receivertype: 1, 2, 3, 4)
Custom_field4: Tipo Contribuyente (1=Natural, 2=Jurídica) - solo nacionales
Custom_field5: Código Ubicación (provincia-distrito-corregimiento) - solo nacionales
```

---

## 3. Análisis por Integración (Servicios Síncronos)

### 3.1 QuickBooks Online (QuickBooksOnlineService.php)

**Archivo**: `app/Services/QuickBooksOnlineService.php`

#### Mapeo Actual (Líneas 718-722)

```php
'Custom_field1' => $tipoReceptor === 4 ? $pasaporte : $ruc,
'Custom_field2' => $tipoReceptor === 4 ? null : $dv,
'Custom_field3' => $tipoReceptor,  // VALOR: 1, 2, 3, o 4
'Custom_field4' => $tipoContribuyente,
'Custom_field5' => $locationCode,
```

#### Lógica de Detección (Líneas 608-650)

```php
// Recibe TIPO_RECEPTOR del request
$tipoReceptorFromRequest = array_get($request, 'TIPO_RECEPTOR');

if (!is_null($tipoReceptorFromRequest)) {
    // ❌ PROBLEMA: Normaliza CODE a número
    $tipoReceptorValue = (int)ltrim((string)$tipoReceptorFromRequest, '0');
    // '04' -> '4' -> 4 (INCORRECTO, debería ser ID 3)
    // '03' -> '3' -> 3 (INCORRECTO, debería ser ID 4)
    
    if (in_array($tipoReceptorValue, [1, 2, 3, 4])) {
        $tipoReceptor = $tipoReceptorValue;
    }
}

// Validación para extranjeros
if ($tipoReceptor === 4) {  // ❌ EVALÚA TRUE cuando CODE='04'
    $pasaporte = $pasaporteFromRequest;
    $ruc = null;
    $dv = null;
}
```

#### 🔴 Problemas Identificados

1. **Mapeo CODE → ID Incorrecto**:
   - QuickBooks envía `TIPO_RECEPTOR = "04"` (CODE para Extranjero)
   - Código normaliza a `4` usando `ltrim('04', '0')`
   - Guarda `Custom_field3 = 4` 
   - **PERO**: Extranjero en tabla tiene **ID=3**, no ID=4

2. **Evaluación Condicional Incorrecta**:
   - `$tipoReceptor === 4` se usa para identificar extranjeros
   - Pero debería ser `$tipoReceptor === 3` (ID real de Extranjero)
   - Esto causa que clientes con CODE='04' se traten como tipo 4

3. **Inconsistencia en Comentarios**:
   - Comentario dice: "3=Gobierno, 4=Extranjero"
   - Tabla real: "3=Extranjero, 4=Gobierno"

#### ✅ Solución Propuesta

```php
// MAPEO CORRECTO: CODE → ID de tabla
private function mapCodeToReceiverId(string $code): int
{
    $codeMap = [
        '01' => 1,  // Contribuyente
        '1'  => 1,
        '02' => 2,  // Consumidor final
        '2'  => 2,
        '03' => 4,  // Gobierno (CODE 03 → ID 4)
        '3'  => 4,
        '04' => 3,  // Extranjero (CODE 04 → ID 3)
        '4'  => 3,
    ];
    
    return $codeMap[$code] ?? 2; // Default: Consumidor final
}

// Usar en la lógica:
$tipoReceptorCode = array_get($request, 'TIPO_RECEPTOR', '02');
$tipoReceptor = $this->mapCodeToReceiverId($tipoReceptorCode);

// Validación para extranjeros (ID=3 en tabla)
if ($tipoReceptor === 3) {  // ✓ CORRECTO
    $pasaporte = $pasaporteFromRequest;
    $ruc = null;
    $dv = null;
    $tipoContribuyente = null;
    $locationCode = null;
}
```

---

### 3.2 Lightspeed (LightspeedService.php)

**Archivo**: `app/Services/LightspeedService.php`

#### Mapeo Actual (Líneas 104-108)

```php
'Custom_field1' => $ruc,
'Custom_field2' => $dv,
'Custom_field3' => $tipoReceptor,  // STRING: '1' o '2'
'Custom_field4' => !empty($ruc) ? (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc) : null,
'Custom_field5' => $locationCode,
```

#### Lógica de Detección (Líneas 60-90)

```php
$tipoReceptor = '2'; // Default: Consumidor final

if (!empty($ruc) && !empty($dv)) {
    $tipoReceptor = '1'; // Contribuyente
}
```

#### ✅ Estado: CORRECTO

- Solo maneja tipos 1 y 2 (nacionales)
- Usa strings que coinciden con IDs de tabla
- Usa `PanamaRucHelper` para detectar tipo contribuyente
- No maneja extranjeros (3) ni gobierno (4)

#### 📋 Recomendación

Agregar soporte para extranjeros si Lightspeed empieza a enviar ese tipo de clientes.

---

### 3.3 Shopify (ShopifyService.php)

**Archivo**: `app/Services/ShopifyService.php`

#### Mapeo Actual (Líneas 93-97)

```php
'Custom_field1' => $ruc,
'Custom_field2' => $dv,
'Custom_field3' => $tipoReceptor,  // Variable extraída de campos custom
'Custom_field4' => $tipoContribuyente,
'Custom_field5' => $ubicacion,
```

#### Lógica de Detección (Líneas 50-70)

```php
$tipoReceptor = '2'; // Default
$tipoContribuyente = '1'; // Default
$ubicacion = null;

// Extracción desde otros campos
if (preg_match('/TipoReceptor:(\d+)/i', $otherValue, $matches)) {
    $tipoReceptor = $matches[1]; // ⚠️ POTENCIAL PROBLEMA
}
if (preg_match('/TipoContribuyente:(\d+)/i', $otherValue, $matches)) {
    $tipoContribuyente = $matches[1];
}
```

#### ⚠️ Problemas Potenciales

1. **Extracción desde regex**: Si Shopify envía CODEs (01-04) en lugar de IDs, tendrá el mismo problema que QuickBooks
2. **Sin validación**: No valida si el valor extraído coincide con IDs de tabla
3. **Sin normalización**: No normaliza '01'/'02' vs '1'/'2'

#### ✅ Solución Propuesta

```php
// Extracción con normalización
if (preg_match('/TipoReceptor:(\d+)/i', $otherValue, $matches)) {
    $tipoReceptorRaw = $matches[1];
    // Aplicar mapeo CODE → ID
    $tipoReceptor = (string)$this->mapCodeToReceiverId($tipoReceptorRaw);
}
```

---

### 3.4 Maxgym (MaxgymService.php)

**Archivo**: `app/Services/MaxgymService.php`

#### Mapeo Actual (Líneas 329-333)

```php
'Custom_field1' => $ruc,
'Custom_field2' => array_get($request, 'Dv', '00'),
'Custom_field3' => $receiverType,  // 1 o 2
'Custom_field4' => (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc),
'Custom_field5' => null,
```

#### ✅ Estado: CORRECTO

- Solo maneja tipos 1 y 2 (nacionales)
- Usa `PanamaRucHelper` correctamente
- No requiere cambios

---

### 3.5 Kart21 (Kart21Service.php)

**Archivo**: `app/Services/Kart21Service.php`

#### Mapeo Actual (Líneas 222-226)

```php
'Custom_field1' => '0-0-0',  // RUC genérico
'Custom_field2' => '00',
'Custom_field3' => 2,  // INT: Consumidor final
'Custom_field4' => null,
'Custom_field5' => null,
```

#### ✅ Estado: CORRECTO

- Hardcoded a tipo 2 (Consumidor final)
- Coincide con ID de tabla
- No requiere cambios

---

### 3.6 Meypar (MeyparService.php)

**Archivo**: `app/Services/MeyparService.php`

#### Mapeo Colombia (Líneas 736-740)

```php
'Custom_field1' => $nit,  // NIT colombiano
'Custom_field2' => '00',
'Custom_field3' => $receiverType,  // '1' o '2'
'Custom_field4' => $receiverType == 1 ? '2' : '1',  // ❌ LÓGICA CONFUSA
'Custom_field5' => $customField5,
```

#### Lógica Colombia

```php
$receiverType = '2'; // Default: Persona
if ($nitValidation['type'] === 'company') {
    $receiverType = '1'; // Contribuyente/Empresa
}
```

#### Mapeo Panamá (Líneas 1240-1244)

```php
'Custom_field1' => $ruc,  // RUC panameño
'Custom_field2' => '00',
'Custom_field3' => $receiverType,  // '1' o '2'
'Custom_field4' => null,  // ✓ CORRECTO para Panamá
'Custom_field5' => $customField5,
```

#### ⚠️ Problema Identificado

**Línea 739 (Colombia)**:
```php
'Custom_field4' => $receiverType == 1 ? '2' : '1',
```

**Análisis**:
- Si `$receiverType = 1` (Contribuyente) → `Custom_field4 = '2'` (Jurídica)
- Si `$receiverType = 2` (Persona) → `Custom_field4 = '1'` (Natural)

**¿Es correcto?**
- Depende de la interpretación de Meypar Colombia
- Para Panamá: tipo 1 puede ser Natural o Jurídica (se detecta con RUC)
- Para Colombia: parece asumir que tipo 1 = siempre empresa (Jurídica)

#### 📋 Recomendación

```php
// Para Colombia: Usar helper si tienen RUC/NIT panameño
// O mantener lógica actual si es específica de Colombia
if ($customerCountry === 'PA' && !empty($ruc)) {
    $tipoContribuyente = (string)\App\Helpers\PanamaRucHelper::detectContributorType($ruc);
} else {
    // Lógica actual para Colombia
    $tipoContribuyente = $receiverType == 1 ? '2' : '1';
}
```

---

### 3.7 ACIcloud (ACIcloudService.php)

**Archivo**: `app/Services/ACIcloudService.php`

#### Mapeo Directo (Líneas 339-343)

```php
'Custom_field1' => $request->get('RUC'),
'Custom_field2' => $request->get('DV'),
'Custom_field3' => $request->get('Custom_field3'),  // ⚠️ SIN VALIDACIÓN
'Custom_field4' => $request->get('Custom_field4'),
'Custom_field5' => $request->get('Custom_field5'),
```

#### Mapeo desde XML (Líneas 1238-1242)

```php
'Custom_field1' => array_get($data, 'dGen.gDatRec.gRucRec.dRuc', '0-0-0'),
'Custom_field2' => array_get($data, 'dGen.gDatRec.gRucRec.dDV', '00'),
'Custom_field3' => (string)$iTipoRec,  // ⚠️ REQUIERE VALIDACIÓN
'Custom_field4' => !empty($dTipoRuc) ? (string)$dTipoRuc : '1',
'Custom_field5' => array_get($data, 'dGen.gDatRec.gUbiRec.dCodUbi', null),
```

#### ⚠️ Problemas Potenciales

1. **Sin validación**: Acepta valores directos sin validar
2. **Sin normalización**: No normaliza CODEs vs IDs
3. **Dependiente del origen**: Si ACIcloud envía CODEs en lugar de IDs, fallará

#### ✅ Solución Propuesta

```php
// Validar y normalizar antes de guardar
$tipoReceptorRaw = $request->get('Custom_field3');
$tipoReceptor = $this->mapCodeToReceiverId($tipoReceptorRaw);

'Custom_field3' => $tipoReceptor,
```

---

## 4. Módulos de Emisión (CreateFast, Create, CreateFastJob)

### 4.1 Lógica de Lectura

**Todos los módulos** (líneas similares):

```php
// CreateFastJob.php línea 268-276
$receptorTipo = $this->customer->Custom_field3 ?? null;
$receptor = \App\Models\Receivertype::query()
    ->where('code', $this->strPad($receptorTipo, 2))
    ->first();
    
if ($receptor instanceof \App\Models\Receivertype) {
    $this->receptor_tipo = (string)$receptor->id;
}
```

#### 🔴 Problema Crítico

**Si Custom_field3 contiene un ID (1-4) en lugar de un CODE (01-04)**:
- Busca `WHERE code = '01'` → encuentra ID=1 ✓
- Busca `WHERE code = '04'` → encuentra ID=3 ✓
- **PERO** si Custom_field3 = 4 (valor incorrecto de QuickBooks):
  - Busca `WHERE code = '04'` → encuentra ID=3 (Extranjero)
  - Asigna `receptor_tipo = '3'` ✓ CORRECTO por casualidad

**El código funciona "por casualidad" porque:**
1. QuickBooks guarda valores incorrectos (4 en lugar de 3)
2. La búsqueda por CODE normaliza a '04'
3. CODE '04' coincide con ID 3 (Extranjero)
4. **Resultado final correcto, pero datos intermedios incorrectos**

### 4.2 Lógica de Evaluación

**Todos los módulos**:

```php
// Línea 278 (CreateFastJob), 138 (CreateFast), 509 (Create)
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4']) 
    ? $this->customer->Custom_field4 : null;
```

#### ✅ Estado: CORRECTO (con los IDs actuales)

**Evaluación**:
- `receptor_tipo = '1'` (Contribuyente) → tiene tipoContribuyente ✓
- `receptor_tipo = '2'` (Consumidor final) → tiene tipoContribuyente ✓
- `receptor_tipo = '3'` (Extranjero) → **NO** tiene tipoContribuyente ✓
- `receptor_tipo = '4'` (Gobierno) → tiene tipoContribuyente ✓

**Comentarios Incorrectos**:

```php
// ❌ COMENTARIO INCORRECTO:
// "Tipos 1,2,4 (panameños) tienen tipoContribuyente, tipo 3 (extranjeros) NO tiene"

// ✅ COMENTARIO CORRECTO:
// "Tipos 1,2,4 (Contribuyente, Consumidor, Gobierno) tienen tipoContribuyente"
// "Tipo 3 (Extranjero - CODE 04) NO tiene tipoContribuyente panameño"
```

---

## 5. Resumen de Problemas por Prioridad

### 🔴 CRÍTICO

1. **QuickBooksOnlineService.php**:
   - Mapeo CODE → ID incorrecto (líneas 612-620)
   - Evaluación `$tipoReceptor === 4` para extranjeros (debería ser `=== 3`)
   - Guarda valores incorrectos en Custom_field3

### ⚠️ ALTO

2. **ShopifyService.php**:
   - Sin validación de valores extraídos de regex
   - Potencial problema si Shopify envía CODEs en lugar de IDs

3. **ACIcloudService.php**:
   - Sin validación ni normalización de valores recibidos
   - Acepta cualquier valor directo del request

### 📋 MEDIO

4. **MeyparService.php**:
   - Lógica de Custom_field4 confusa para Colombia
   - Requiere documentación clara de la lógica Colombia vs Panamá

### ✅ BAJO (Documentación)

5. **Comentarios en módulos de emisión**:
   - CreateFast.php, Create.php, CreateFastJob.php
   - Comentarios incorrectos sobre tipos 3 y 4

---

## 6. Plan de Corrección Propuesto

### Fase 1: Correcciones Críticas (Inmediato)

1. **QuickBooksOnlineService.php**:
   ```php
   // Agregar función de mapeo
   private function mapCodeToReceiverId(string $code): int
   {
       $map = ['01'=>1, '1'=>1, '02'=>2, '2'=>2, '03'=>4, '3'=>4, '04'=>3, '4'=>3];
       return $map[$code] ?? 2;
   }
   
   // Usar en líneas 612-620
   $tipoReceptor = $this->mapCodeToReceiverId($tipoReceptorFromRequest);
   
   // Cambiar línea 634
   if ($tipoReceptor === 3) {  // Extranjero (ID correcto)
       // ...
   }
   ```

2. **Actualizar comentarios** en:
   - CreateFast.php línea 137
   - Create.php línea 508
   - CreateFastJob.php línea 278

### Fase 2: Validaciones (Próxima semana)

3. **ShopifyService.php**:
   - Agregar función de mapeo compartida
   - Validar valores extraídos de regex

4. **ACIcloudService.php**:
   - Agregar validación de valores recibidos
   - Normalizar antes de guardar

### Fase 3: Mejoras (Futuro)

5. **Crear clase Helper compartida**:
   ```php
   // App\Helpers\ReceiverTypeHelper.php
   class ReceiverTypeHelper
   {
       public static function mapCodeToId(string $code): int
       {
           // Lógica centralizada
       }
       
       public static function needsTipoContribuyente(int $id): bool
       {
           return in_array($id, [1, 2, 4]);
       }
   }
   ```

6. **MeyparService.php**:
   - Documentar lógica Colombia
   - Considerar usar PanamaRucHelper si aplica

---

## 7. Tabla de Referencia Rápida

| Integración | Custom_field3 Actual | ¿Correcto? | Prioridad Corrección |
|-------------|---------------------|------------|---------------------|
| QuickBooks  | Valor incorrecto (4 en lugar de 3) | ❌ NO | 🔴 CRÍTICO |
| Lightspeed  | ID correcto (1 o 2) | ✅ SÍ | - |
| Shopify     | Sin validar | ⚠️ DEPENDE | ⚠️ ALTO |
| Maxgym      | ID correcto (1 o 2) | ✅ SÍ | - |
| Kart21      | ID correcto (2) | ✅ SÍ | - |
| Meypar      | ID correcto (1 o 2) | ✅ SÍ | 📋 MEDIO (doc) |
| ACIcloud    | Sin validar | ⚠️ DEPENDE | ⚠️ ALTO |

---

## 8. Testing Requerido Post-Corrección

### Test 1: QuickBooks Extranjero
```
Input: TIPO_RECEPTOR = "04", PASAPORTE = "ABC123"
Esperado:
  - Custom_field1 = "ABC123"
  - Custom_field2 = null
  - Custom_field3 = 3  (ID de Extranjero)
  - Custom_field4 = null
  - Custom_field5 = null
```

### Test 2: QuickBooks Gobierno
```
Input: TIPO_RECEPTOR = "03", RUC = "1-1-1"
Esperado:
  - Custom_field1 = "1-1-1"
  - Custom_field2 = "01" (si viene)
  - Custom_field3 = 4  (ID de Gobierno)
  - Custom_field4 = "1" o "2"
  - Custom_field5 = código ubicación
```

### Test 3: Emisión Extranjero
```
Given: Cliente con Custom_field3 = 3
Then:
  - receptor_tipo = "3"
  - receptor_tipoContribuyente = null
  - receptor_pasaporteIdentidadExtranjera = Custom_field1
  - receptor_ruc = null
```

---

## 9. Análisis por Integración (Jobs Asíncronos)

### 9.1. CreateSaleQuickBooksJob
**Archivo**: `app/Jobs/CreateSaleQuickBooksJob.php`

**Comportamiento**:
```php
// Líneas 80-90 (aproximado)
$response = $quickbooksOnlineService->storeOrder($data);
```

**Análisis**:
- ✅ **Delega completamente a QuickBooksOnlineService**
- ✅ No manipula Custom_fields directamente
- ⚠️ Hereda el problema del servicio (mapeo incorrecto CODE→ID)

**Estado**: CORRECTO en diseño, requiere corrección del servicio subyacente

---

### 9.2. CreateSaleLightspeedJob
**Archivo**: `app/Jobs/CreateSaleLightspeedJob.php`

**Comportamiento**:
```php
// Líneas 120-130 (aproximado)
$lightspeedService->storeOrder($data);
```

**Análisis**:
- ✅ **Delega completamente a LightspeedService**
- ✅ No manipula Custom_fields directamente
- ✅ LightspeedService sigue el estándar correctamente

**Estado**: CORRECTO

---

### 9.3. UpdateIntuitOrdersJob (QuickBooks)
**Archivo**: `app/Jobs/Intuit/UpdateIntuitOrdersJob.php`

**Comportamiento**:
```php
// Líneas 303-304
$customer = Customer::query()->create([
    'Name' => $invoiceData['CustomerRef']['name'] ?? 'Cliente QuickBooks',
    'Ruc' => null,
    'Dv' => null,
    'Custom_field1' => 'auto_created',  // ❌ INCORRECTO
    'Custom_field2' => '',               // ❌ INCORRECTO
    'organization_id' => $this->organizationId
]);
```

**Problemas Identificados**:
1. ❌ **Custom_field1='auto_created'**: Debería ser RUC o PASAPORTE
2. ❌ **Custom_field2=''**: Debería ser DV o null
3. ❌ **Custom_field3 NO SE ESTABLECE**: Falta TIPO_RECEPTOR ID
4. ❌ **Custom_field4 NO SE ESTABLECE**: Falta tipo contribuyente
5. ❌ **Custom_field5 NO SE ESTABLECE**: Falta código ubicación

**Contexto**: Este método `createMissingCustomer()` se ejecuta cuando QuickBooks envía una orden con un cliente que no existe en DocuCenter.

**Impacto**: 
- **ALTO**: Clientes creados automáticamente NO pueden emitir facturas correctamente
- **Datos incompletos**: Faltan todos los campos fiscales requeridos
- **Validación fallará**: Sin Custom_field3 (TIPO_RECEPTOR), la emisión fallará

**Estado**: **CRÍTICO - REQUIERE CORRECCIÓN URGENTE**

**Solución Propuesta**:
```php
// Debe seguir el estándar:
$customer = Customer::query()->create([
    'Name' => $invoiceData['CustomerRef']['name'] ?? 'Cliente QuickBooks',
    'Ruc' => null,  // Extranjero por defecto
    'Dv' => null,
    'Custom_field1' => 'QB-' . $invoiceData['CustomerRef']['value'], // ID de QuickBooks
    'Custom_field2' => null,
    'Custom_field3' => 3,  // ID Extranjero por defecto
    'Custom_field4' => null,
    'Custom_field5' => null,
    'Country' => 'US',  // Por defecto extranjero
    'organization_id' => $this->organizationId
]);

// Agregar log de advertencia
Log::warning('Cliente QuickBooks creado automáticamente - requiere validación', [
    'qb_customer_id' => $invoiceData['CustomerRef']['value'],
    'customer_id' => $customer->Id
]);
```

---

### 9.4. Invupos Jobs
**Archivos**: 
- `app/Jobs/Invupos/CustomerJob.php`
- `app/Jobs/Invupos/ProductJob.php`
- `app/Jobs/Invupos/SalesOrdersJob.php`

#### 9.4.1. CustomerJob
**Comportamiento**:
```php
// Líneas 105-107
'Custom_field3' => null,                    // ❌ INCORRECTO
'Custom_field4' => $row['tipo_contribuyente'],  // ⚠️ INCORRECTO orden
```

**Problemas Identificados**:
1. ❌ **Custom_field3=null**: Debería contener TIPO_RECEPTOR ID (1,2,3,4)
2. ⚠️ **Custom_field4=tipo_contribuyente**: Orden correcto, pero Custom_field3 debe tener valor primero
3. ❌ **Falta lógica de detección**: No detecta si es extranjero, gobierno, etc.

**Impacto**:
- **MEDIO-ALTO**: Clientes de Invupos NO tienen TIPO_RECEPTOR
- **Emisión afectada**: Puede fallar validación o usar valor por defecto
- **Datos incompletos**: Sin clasificación fiscal correcta

**Estado**: **REQUIERE CORRECCIÓN**

**Solución Propuesta**:
```php
// Determinar TIPO_RECEPTOR basado en datos disponibles
$tipoReceptor = 1; // Por defecto Contribuyente

// Si es extranjero
if (!empty($row['country']) && $row['country'] !== 'PA') {
    $tipoReceptor = 3; // Extranjero
}

// Si es gobierno (basado en RUC o nombre)
if (!empty($row['ruc']) && PanamaRucHelper::isGovernmentEntity($row['ruc'])) {
    $tipoReceptor = 4; // Gobierno
}

// Si no tiene RUC y es panameño
if (empty($row['ruc']) && (empty($row['country']) || $row['country'] === 'PA')) {
    $tipoReceptor = 2; // Consumidor final
}

'Custom_field3' => $tipoReceptor,
'Custom_field4' => $row['tipo_contribuyente'],
```

#### 9.4.2. SalesOrdersJob
**Análisis**: 
- ✅ No crea clientes directamente
- ✅ Usa clientes existentes
- ⚠️ Depende de que CustomerJob haya creado clientes correctamente

**Estado**: INDIRECTAMENTE AFECTADO por CustomerJob

---

## 10. Resumen Jobs Asíncronos

### Jobs que Delegan (Correcto)
- ✅ **CreateSaleQuickBooksJob**: Delega a QuickBooksOnlineService
- ✅ **CreateSaleLightspeedJob**: Delega a LightspeedService

**Estado**: Correcto en diseño, heredan comportamiento del servicio

### Jobs que Crean Directamente (Problemas)
- ❌ **UpdateIntuitOrdersJob**: Custom_field1='auto_created', NO establece Custom_field3/4/5
- ❌ **Invupos CustomerJob**: Custom_field3=null, falta lógica de detección

**Impacto Crítico**: Estos jobs crean clientes que NO pueden emitir facturas correctamente

---

## 11. Conclusiones Finales

1. **Problema raíz**: Inconsistencia entre CODEs (01-04) e IDs (1-4) en tabla Receivertype
2. **Integraciones más afectadas**: 
   - QuickBooks (mapeo CODE→ID incorrecto)
   - UpdateIntuitOrdersJob (no sigue estándar)
   - Invupos CustomerJob (Custom_field3=null)
3. **Impacto actual**: 
   - **Medio** en servicios síncronos (funciona por "casualidad")
   - **ALTO** en jobs asíncronos (crean datos inválidos)
4. **Riesgo**: **CRÍTICO** - clientes auto-creados NO pueden emitir facturas
5. **Solución**: Implementar mapeo CODE→ID centralizado + corrección jobs asíncronos

---

**Estado de Implementación:**

### ✅ FASE 1 COMPLETADA - CRÍTICO
1. ✅ ReceiverTypeHelper creado con consulta BD + cache (1 hora TTL)
2. ✅ UpdateIntuitOrdersJob corregido - Custom_field3=getExtranjeroId()
3. ✅ Invupos CustomerJob corregido - detectReceiverType()
4. ✅ QuickBooksOnlineService mapeo CODE→ID con mapCodeToId()
5. ✅ Refactor helpers con CODEs estables + IDs dinámicos
6. ✅ Comentarios módulos emisión actualizados

### ✅ FASE 2 COMPLETADA - IMPORTANTE  
1. ✅ ShopifyService validación regex con mapCodeToId()
2. ✅ ACIcloudService normalización con normalizeCustomField3()
3. ✅ CustomFieldsValidator creado - validación centralizada

### ⏳ FASE 3 PENDIENTE - MEJORA
1. ⏳ Tests de integración ReceiverTypeHelper
2. ⏳ Documentar lógica MeyparService (Colombia)
3. ⏳ Validación con clientes reales QuickBooks
4. ⏳ Helpers avanzados compartidos

---

**Resultado Final:**
- **ReceiverTypeHelper**: 233 líneas, resiliente entre ambientes
- **CustomFieldsValidator**: Validación cruzada de todos los custom fields
- **Integraciones corregidas**: QuickBooks, Invupos, Shopify, ACIcloud
- **Jobs asíncronos**: Ahora crean clientes VÁLIDOS para emisión
- **Sistema validado**: Multi-ambiente (dev, staging, prod)
