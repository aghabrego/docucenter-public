# Corrección de Asignación de País para Clientes Extranjeros en QuickBooks

**Fecha**: 2024-01-XX  
**Componente**: `app/Services/QuickBooksOnlineService.php`  
**Tipo**: Corrección de Error (Bug Fix)  
**Severidad**: Alta - Impacta validación PAC

## Problema Identificado

El servicio `QuickBooksOnlineService` no preservaba correctamente el país original de los clientes extranjeros, causando errores de validación PAC:

```
El país del cliente debe ser PA si el destino de la operación es 1= Panamá
```

### Causa Raíz

En el método `createDefaultClient` (línea ~578), la lógica de asignación por defecto sobrescribía el país corregido:

```php
// Código problemático
'Country' => array_get($request, 'Country', ($tipoReceptor === '04' ? 'US' : 'PA'))
```

Esta línea asignaba automáticamente `US` como país por defecto para extranjeros, independientemente del país real que venía en los datos del cliente.

## Solución Implementada

### 1. Corrección de Lógica de País

```php
// Código corregido
$countryFromRequest = array_get($request, 'Country');
$defaultCountry = ($tipoReceptor === '04' ? 'US' : 'PA');
$finalCountry = $countryFromRequest ?? $defaultCountry;

// Usar el país del request si está presente, sino usar el default
'Country' => $finalCountry
```

### 2. Logging Detallado para Debugging

Se agregó logging completo en dos puntos críticos:

**Punto 1 - Determinación Inicial del País** (línea ~717):
```php
Log::info('QuickBooksOnlineService: DEBUGGING País y Tipo Receptor', [
    'invoice_id' => array_get($invoiceData, 'Id'),
    'customer_name' => $customerName,
    'customer_ref_data' => $customerRef,
    'original_country' => $originalCountry,
    'tipo_receptor' => $tipoReceptor,
    'is_national_client' => $isNationalClient,
    'corrected_country' => $correctedCountry,
    'type_of_sale' => $typeOfSale
]);
```

**Punto 2 - Creación del Cliente** (línea ~581):
```php
Log::info('QuickBooksOnlineService.createDefaultClient: DEBUGGING Country Assignment', [
    'customer_name' => array_get($request, 'Customer_Bill_Name', ''),
    'tipo_receptor' => $tipoReceptor,
    'country_from_request' => $countryFromRequest,
    'default_country' => $defaultCountry,
    'final_country' => $finalCountry,
    'all_request_data' => $request
]);
```

## Flujo de Procesamiento Corregido

1. **Determinación Inicial**: Se determina el país corregido basado en TIPO_RECEPTOR
2. **Paso al Método**: El país corregido se pasa a `createDefaultClient` en el array `$request`
3. **Preservación del País**: El método ahora preserva el país del request si está presente
4. **Creación del Cliente**: Se usa el país corregido en lugar del valor por defecto

## Casos de Uso Afectados

| TIPO_RECEPTOR | Tipo Cliente | País Original | País Final | Comportamiento |
|---------------|-------------|---------------|------------|----------------|
| 01, 02, 03 | Nacional | Cualquiera | PA | Forzar PA para nacionales |
| 04 | Extranjero | Chile | Chile | Preservar país original |
| 04 | Extranjero | Argentina | Argentina | Preservar país original |
| 04 | Extranjero | null/vacío | US | Usar default para extranjeros |

## Archivos Modificados

- `app/Services/QuickBooksOnlineService.php`: Corrección principal
- `docs/testing/test-quickbooks-country-processing.php`: Script de prueba

## Script de Prueba

Se creó un script de prueba para validar el comportamiento:

```bash
php docs/testing/test-quickbooks-country-processing.php [organization_id]
```

El script:
- Crea un cliente extranjero de prueba con país Chile
- Verifica que se preserve el país original
- Valida que el TIPO_RECEPTOR sea correcto (04)
- Limpia los datos de prueba automáticamente

## Impacto en Validación PAC

Esta corrección resuelve errores PAC del tipo:
- "El país del cliente debe ser PA si el destino de la operación es 1= Panamá"
- Permitiendo correctamente facturas a clientes extranjeros con `destinoOperacion = 2`

## Debugging

Para investigar problemas relacionados con países, revisar los logs:

```bash
tail -f storage/logs/laravel.log | grep "DEBUGGING País"
```

Los logs mostrarán:
- País original del cliente
- Tipo de receptor determinado
- País corregido aplicado
- Datos completos del request

## Testing

Validar la corrección con:

1. **Prueba Automática**:
   ```bash
   php docs/testing/test-quickbooks-country-processing.php
   ```

2. **Prueba Manual**:
   - Procesar una factura QuickBooks de cliente extranjero
   - Verificar logs para confirmar país correcto
   - Confirmar que no aparece error PAC de país

## Notas Técnicas

- **Backward Compatibility**: Mantiene compatibilidad con lógica existente
- **Performance**: Mínimo impacto, solo agregar logging
- **Database**: No requiere cambios de esquema
- **Configuration**: No requiere cambios de configuración

## Relacionado

- Commit: `d310f107` - Corrección principal
- Issue: Error PAC país cliente extranjero
- Componentes relacionados: `CreateFastJob.php`, `FEXmlService.php`

---

*Documentación técnica - DocuCenter Sistema de Facturación Electrónica*
