# Mejora de Validación de RUC en MeyparService

## Resumen de Cambios

Se refactorizó la lógica de validación y clasificación de RUC en `MeyparService` para implementar un sistema robusto que:

1. **Valida el formato del RUC** usando `PanamaRucHelper`
2. **Detecta automáticamente el tipo de contribuyente** (empresa, persona natural o consumidor final)
3. **Consulta el DV (Dígito Verificador)** cuando el PAC es Alanube
4. **Determina correctamente el ReceiverType** para emisión PAC

## Cambios Implementados

### 1. Nuevo Método: `analyzeAndValidateRuc()`

**Ubicación**: [app/Services/MeyparService.php](app/Services/MeyparService.php#L1167-L1282)

Este método centraliza toda la lógica de análisis y validación de RUC:

```php
/**
 * Analizar y validar RUC para determinar tipo de cliente
 *
 * @param Organization $organization
 * @param string $rucInput
 * @param string $invoiceNumber
 * @return array
 */
private function analyzeAndValidateRuc(Organization $organization, string $rucInput, string $invoiceNumber): array
```

#### Retorna:
```php
[
    'customer_id' => string,
    'customer_name' => string,
    'ruc' => string,             // RUC validado
    'type' => string,            // 'company', 'person', 'final_consumer'
    'receiver_type' => string,   // '01' o '02'
    'dv' => string,              // '00' o DV desde Alanube
]
```

#### Flujo de Validación:

1. **Validación de RUC vacío**:
   - Si el RUC está vacío o es `0-0-0`, se clasifica como **consumidor final**
   - Retorna RUC por defecto: `222222222222`

2. **Validación de formato**:
   - Usa `PanamaRucHelper::verifyPersonalID()` para validar formato
   - Si el formato es inválido, también se clasifica como consumidor final

3. **Detección de tipo de contribuyente**:
   - Usa `PanamaRucHelper::detectContributorType()` 
   - Retorna `1` para persona natural, `2` para empresa (jurídico)

4. **Consulta de DV (solo Alanube)**:
   - Si la conexión PAC es de tipo Alanube, consulta el DV real usando `AlanubeService::checkRucDigit()`
   - Si falla o no es Alanube, usa DV por defecto `00`

5. **Determinación de ReceiverType**:
   - `01` = Contribuyente (empresa o persona registrada)
   - `02` = Consumidor Final

### 2. Actualización del flujo principal en `storeOrder()`

**Cambio anterior**:
```php
// Lógica incorrecta
$isConsumidorFinal = !empty($documento); // ❌ Asumía que documento = consumidor final
```

**Cambio nuevo**:
```php
// Obtener el RUC del documento (puede ser idFacturador o documento según MEYPAR)
$documento = array_get($data, 'documento', '');
$rucInput = !empty($documento) ? $documento : $idFacturador;

// Validar y determinar el tipo de cliente
$clientData = $this->analyzeAndValidateRuc($organization, $rucInput, $numero);

$customerId = $clientData['customer_id'];
$customerName = $clientData['customer_name'];
$validRuc = $clientData['ruc'];
$customerType = $clientData['type'];
$receiverType = $clientData['receiver_type'];
$dv = $clientData['dv'];
```

### 3. Actualización de `createMeyparClientPanama()`

**Nuevos campos soportados**:
```php
$customer = $this->createMeyparClientPanama($organization, [
    'CustomerID' => $customerId,
    'Customer_Bill_Name' => $customerName,
    'Email' => '',
    'AddressLine1' => '',
    'RUC' => $validRuc,
    'CustomerType' => $customerType,
    'ReceiverType' => $receiverType,  // ✅ Nuevo
    'DV' => $dv,                       // ✅ Nuevo
]);
```

**Almacenamiento en CustomersImp**:
```php
[
    'Custom_field1' => $ruc,           // RUC panameño validado
    'Custom_field2' => $dv,            // DV (00 por defecto, o desde Alanube) ✅
    'Custom_field3' => $receiverType,  // TIPO_RECEPTOR (01=Contribuyente, 02=Consumidor Final) ✅
    'Custom_field4' => $contributorType, // Tipo contribuyente AUTO-DETECTADO (1=Natural, 2=Jurídico) ✅
    'Custom_field5' => $customField5,  // Código de ubicación para empresas
]
```

## Beneficios

### 1. ✅ Validación Automática de Formato
- Usa helpers oficiales de validación de RUC panameño
- Detecta automáticamente RUCs inválidos
- Fallback a consumidor final para casos problemáticos

### 2. ✅ Detección Inteligente de Tipo
- Distingue correctamente entre empresa, persona natural y consumidor final
- Basado en patrones oficiales de DGI Panamá
- No requiere configuración manual

### 3. ✅ Integración con Alanube
- Consulta automática del DV cuando el PAC es Alanube
- Manejo de errores en consulta (fallback a DV 00)
- Logging detallado para troubleshooting

### 4. ✅ ReceiverType Correcto
- `01` para contribuyentes registrados (empresa o persona)
- `02` para consumidores finales
- Compatible con normativa DGI Panamá

### 5. ✅ Tipo Contribuyente Auto-Detectado
- Usa `PanamaRucHelper::detectContributorType()` como MaxgymService
- `1` para Persona Natural
- `2` para Persona Jurídica
- `null` para consumidor final sin RUC
- Cumple con estándar global DocuCenter

### 6. ✅ Logging Detallado
- Logs informativos en cada paso de validación
- Trazabilidad completa del proceso
- Facilita debugging y auditoría

### 7. ✅ Cliente Consumidor Final Único
- Usa CustomerID fijo `'CONTADO'` para consumidor final
- No crea múltiples clientes con IDs únicos
- Sigue el patrón estándar del sistema (ImportData, LightspeedService, etc.)
- Evita duplicación de registros de consumidor final

## Casos de Uso Soportados

### Caso 1: RUC de Empresa
```php
// Input
$documento = "155750453-2-2024";

// Output
[
    'ruc' => "155750453-2-2024",
    'type' => 'company',
    'receiver_type' => '01',  // Contribuyente
    'dv' => '97', // Desde Alanube si aplica
    'contributor_type' => 2   // Jurídico (auto-detectado)
]
```

### Caso 2: RUC de Persona Natural
```php
// Input
$documento = "8-123-456";

// Output
[
    'ruc' => "8-123-456",
    'type' => 'person',
    'receiver_type' => '01',  // Contribuyente
    'dv' => '78', // Desde Alanube si aplica
    'contributor_type' => 1   // Natural (auto-detectado)
]
```

### Caso 3: Consumidor Final (RUC vacío)
```php
// Input
$documento = "";

// Output
[
    'ruc' => "222222222222",
    'type' => 'final_consumer',
    'receiver_type' => '02',  // Consumidor Final
    'dv' => '00',
    'customer_id' => 'CONTADO', // ✅ ID fijo, no se crea cliente nuevo cada vez
    'contributor_type' => null  // Sin tipo contribuyente
]
```

### Caso 4: RUC Inválido (formato incorrecto)
```php
// Input
$documento = "ABC123XYZ";

// Output (fallback a consumidor final)
[
    'ruc' => "222222222222",
    'type' => 'final_consumer',
    'receiver_type' => '02',
    'dv' => '00',
    'customer_id' => 'CONTADO', // ✅ ID fijo, no se crea cliente nuevo cada vez
    'contributor_type' => null  // Sin tipo contribuyente
]
```

## Archivos Modificados

- `app/Services/MeyparService.php`
  - ✅ Nuevo método `analyzeAndValidateRuc()`
  - ✅ Actualizado `storeOrder()` con nueva lógica de validación
  - ✅ Actualizado `createMeyparClientPanama()` con ReceiverType y DV

## Dependencias Utilizadas

1. **PanamaRucHelper**
   - `verifyPersonalID()`: Validación de formato
   - `detectContributorType()`: Detección de tipo (1=Natural, 2=Jurídico)

2. **AlanubeService**
   - `checkRucDigit()`: Consulta de DV para RUCs panameños

3. **Logging**
   - Logs informativos en cada paso
   - Logs de advertencia para RUCs inválidos
   - Logs de error para problemas críticos

## Testing Recomendado

### Pruebas Manuales
1. Factura con RUC de empresa válido
2. Factura con RUC de persona natural válida
3. Factura sin RUC (consumidor final)
4. Factura con RUC inválido

### Escenarios con Alanube
1. Organización con PAC Alanube configurado (debe consultar DV)
2. Organización sin PAC Alanube (debe usar DV 00)
3. Error en consulta Alanube (debe usar DV 00 como fallback)

### Logs a Verificar
```bash
# Ver logs de validación
tail -f storage/logs/laravel.log | grep "MEYPAR:"
```

## Próximos Pasos Recomendados

1. ✅ Testing en sandbox con diferentes tipos de RUC
2. ✅ Verificar emisión PAC con ReceiverType correcto
3. ✅ Validar DV desde Alanube en casos reales
4. ✅ Documentar casos de error encontrados

## Notas Importantes

- El DV solo se consulta cuando el PAC es Alanube
- Para otros PACs, se usa DV por defecto `00`
- Los RUCs inválidos se manejan como consumidor final para no bloquear emisión
- El ReceiverType `01`/`02` es compatible con normativa DGI Panamá
- **Consumidor Final usa CustomerID fijo `'CONTADO'`**: No se crean múltiples registros de consumidor final, se reutiliza el mismo cliente predeterminado

### CustomerID para Consumidor Final

Siguiendo el patrón estándar del sistema (usado en `ImportData`, `LightspeedService`, `ShopifyService`, etc.):

```php
// ❌ ANTES: Generaba ID único cada vez
'customer_id' => 'CF-' . uniqid()  // CF-abc123, CF-def456, CF-ghi789...

// ✅ AHORA: ID fijo reutilizable
'customer_id' => 'CONTADO'  // Siempre el mismo
```

**Ventajas**:
- ✅ No duplica registros de consumidor final en `CustomersImp`
- ✅ Todas las facturas a consumidor final apuntan al mismo cliente
- ✅ Facilita reportes y análisis de ventas a consumidor final
- ✅ Consistente con el resto del sistema DocuCenter

---

**Fecha de implementación**: 2026-01-27
**Autor**: GitHub Copilot
**Versión**: 1.0
