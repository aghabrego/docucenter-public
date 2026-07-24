# Solución del Error de País en QuickBooks API

## Problema Identificado

**Error**: `CreditMemo.CustomerRef.BillAddr.Country: El credit memo. customer ref. bill addr. country must not be greater than 2 characters.`

QuickBooks Online requiere que el campo `Country` en la dirección de un cliente tenga máximo 2 caracteres, específicamente códigos ISO-3166-1 Alpha-2 (ej: "PA" para Panamá, "US" para Estados Unidos).

Sin embargo, el sistema estaba enviando valores como "Panamá", "Panama", o nombres completos de países, lo que causaba el rechazo de la API.

## Solución Implementada

### 1. Creación del Helper `CountryCodeHelper`

Se creó un nuevo helper en `app/Helpers/CountryCodeHelper.php` que:

- Convierte nombres de países en cualquier idioma (español, inglés, etc.) a códigos ISO de 2 caracteres
- Maneja acentos y caracteres especiales correctamente
- Incluye un mapeo exhaustivo de más de 200 variaciones de nombres de países

**Ejemplo de uso:**
```php
use App\Helpers\CountryCodeHelper;

CountryCodeHelper::toIsoCode('Panamá');      // Devuelve 'PA'
CountryCodeHelper::toIsoCode('México');      // Devuelve 'MX'
CountryCodeHelper::toIsoCode('United States'); // Devuelve 'US'
CountryCodeHelper::toIsoCode('PA');          // Devuelve 'PA' (código ya válido)
```

### 2. Integración en Request Classes

Se modificaron dos request classes para sanitizar el campo `Country` automáticamente:

- **[CreateSaleQuickBooksRequest.php](CreateSaleQuickBooksRequest.php)**: Para facturas (invoices)
- **[CreateCreditNoteQuickBooksRequest.php](CreateCreditNoteQuickBooksRequest.php)**: Para notas de crédito (credit memos)

Ambas ahora normalizan estos campos:
- `Invoice.CustomerRef.Country`
- `Invoice.CustomerRef.BillAddr.Country`
- `Invoice.BillAddr.Country`
- `Invoice.ShipAddr.Country`
- `CreditMemo.CustomerRef.Country`
- `CreditMemo.CustomerRef.BillAddr.Country`

### 3. Manejo de Acentos

El helper utiliza `iconv()` con transliteración ASCII para convertir caracteres acentuados de manera confiable:

```php
// Entrada: "México"
// Paso 1: Remover acentos -> "Mexico"
// Paso 2: Convertir a mayúsculas -> "MEXICO"
// Paso 3: Buscar en mapa -> "MX"
```

**Importante**: El orden de operaciones es crítico. Los acentos deben removerse ANTES de convertir a mayúsculas, porque `strtoupper()` en PHP no maneja correctamente caracteres acentuados como "é".

## Archivos Modificados

1. **[app/Helpers/CountryCodeHelper.php](app/Helpers/CountryCodeHelper.php)** - NUEVO
   - Helper con lógica de conversión de países

2. **[app/Http/Requests/CreateSaleQuickBooksRequest.php](app/Http/Requests/CreateSaleQuickBooksRequest.php)** - MODIFICADO
   - Agregada sanitización de campos `Country` en método `prepareForValidation()`

3. **[app/Http/Requests/CreateCreditNoteQuickBooksRequest.php](app/Http/Requests/CreateCreditNoteQuickBooksRequest.php)** - MODIFICADO
   - Agregada sanitización de campos `Country` en método `prepareForValidation()`

4. **[tests/Unit/Helpers/CountryCodeHelperTest.php](tests/Unit/Helpers/CountryCodeHelperTest.php)** - NUEVO
   - Tests exhaustivos del helper (11 tests, todos pasando)

## Validación

Todos los tests pasan exitosamente:
```
Tests: 11 passed
```

### Casos de Prueba Incluidos

- Conversión de nombres de países en español
- Conversión de nombres en inglés
- Manejo de códigos ISO de 2 caracteres
- Manejo de valores vacíos/nulos
- Validación de códigos ISO
- Manejo de caracteres acentuados
- Nombres compuestos (ej: "Costa Rica", "United States")
- Espacios en blanco

## Impacto en la API

Con esta solución:

1. **Las facturas (invoices)** enviadas a QuickBooks ya no fallarán por campo `Country` con más de 2 caracteres
2. **Las notas de crédito (credit memos)** se procesarán correctamente
3. El sistema es **agnóstico del idioma** - acepta nombres de países en español, inglés, o con acentos
4. La conversión es **automática** - los desarrolladores no necesitan modificar su código

## Ejemplo Real de Uso

**Antes (Error):**
```json
{
  "CreditMemo": {
    "CustomerRef": {
      "BillAddr": {
        "Country": "Panamá"  // ❌ Error: > 2 caracteres
      }
    }
  }
}
```

**Después (Correcto):**
```json
{
  "CreditMemo": {
    "CustomerRef": {
      "BillAddr": {
        "Country": "PA"  // ✅ Convertido automáticamente a código ISO
      }
    }
  }
}
```

## Mantenimiento Futuro

Si se necesita agregar nuevos países o variaciones:
1. Editar el array `$countryMap` en [CountryCodeHelper.php](CountryCodeHelper.php)
2. Agregar los nuevos casos de prueba en [CountryCodeHelperTest.php](CountryCodeHelperTest.php)
3. Ejecutar: `docker exec -it docucenter_laravel.test php artisan test tests/Unit/Helpers/CountryCodeHelperTest.php`
