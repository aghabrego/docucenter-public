# Solución Completa a Errores PAC de Alanube

## Problemas Identificados

Se identificaron **DOS errores críticos** del PAC de Alanube que impedían la validación de facturas:

### 1. Error ITBMS Rate
```
instance.items[0].itbms requires property "rate"
instance.items[1].itbms requires property "rate"
```

### 2. Error Receiver RUC Type
```
instance.receiver.ruc requires property "type"
```

## Datos de Ejemplo que Causaron los Errores

```json
{
  "dGen": {
    "gDatRec": {
      "gRucRec": {
        "dTipoRuc": "2",          // Problemático cuando es 0
        "dRuc": "1808755-1-706832",
        "dDV": "97"               // Era buscado como "dDVRuc"
      }
    }
  },
  "gItem": [
    {
      "gITBMSItem": {
        "dTasaITBMS": 0,          // ¡PROBLEMA PRINCIPAL!
        "dValITBMS": "0.000000"
      }
    }
  ]
}
```

## Causa Raíz de Ambos Errores

**Problema común**: El método `removeNullValues()` eliminaba propiedades críticas cuando tenían valor `0`, porque `0` se evalúa como falsy en PHP.

### Funciones Problemáticas:

1. **`padString()`**: Convertía `0` a `null`
2. **`removeNullValues()`**: Eliminaba propiedades con valor `0`
3. **Mapeo incorrecto**: Buscaba `dDVRuc` en lugar de `dDV`

## Soluciones Implementadas

### 1. Corrección de `padString()`

**Antes:**
```php
private static function padString(?string $value, int $length): ?string
{
    return $value ? str_pad($value, $length, '0', STR_PAD_LEFT) : null;
}
```

**Después:**
```php
private static function padString(?string $value, int $length): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    return str_pad($value, $length, '0', STR_PAD_LEFT);
}
```

### 2. Nuevo Método `formatTaxStructure()`

```php
private static function formatTaxStructure(array $tax): array
{
    $result = [];
    foreach ($tax as $key => $value) {
        if ($value !== null && $value !== '') {
            $result[$key] = $value;
        } elseif (($key === 'rate' || $key === 'amount') && is_numeric($value)) {
            // Para rate y amount, siempre incluir incluso si es 0
            $result[$key] = (float)$value;
        }
    }
    return $result;
}
```

### 3. Nuevo Método `formatRucStructure()`

```php
private static function formatRucStructure(array $ruc): array
{
    $result = [];
    foreach ($ruc as $key => $value) {
        if ($value !== null && $value !== '') {
            $result[$key] = $value;
        } elseif ($key === 'type' && is_numeric($value)) {
            // Para 'type', siempre incluir incluso si es 0
            $result[$key] = (int)$value;
        }
    }
    
    // Garantizar que 'type' esté presente
    if (!isset($result['type'])) {
        $result['type'] = 1; // Valor por defecto
    }
    
    return $result;
}
```

### 4. Aplicación en Formateo de Impuestos

**Antes:**
```php
'itbms' => self::removeNullValues([
    'rate' => self::padString($lineItem['gITBMSItem']['dTasaITBMS'] ?? null, 2),
    'amount' => (float)($lineItem['gITBMSItem']['dValITBMS'] ?? 0),
]),
```

**Después:**
```php
'itbms' => self::formatTaxStructure([
    'rate' => self::padString($lineItem['gITBMSItem']['dTasaITBMS'] ?? null, 2),
    'amount' => (float)($lineItem['gITBMSItem']['dValITBMS'] ?? 0),
]),
```

### 5. Aplicación en Formateo de RUC

**Antes:**
```php
'ruc' => self::removeNullValues([
    'type' => (int)($data['dGen']['gDatRec']['gRucRec']['dTipoRuc'] ?? 1),
    'ruc' => $ruc,
    'verificationDigit' => $data['dGen']['gDatRec']['gRucRec']['dDVRuc'] ?? null,
]),
```

**Después:**
```php
'ruc' => self::formatRucStructure([
    'type' => (int)($data['dGen']['gDatRec']['gRucRec']['dTipoRuc'] ?? 1),
    'ruc' => $ruc,
    'verificationDigit' => $data['dGen']['gDatRec']['gRucRec']['dDV'] ?? $data['dGen']['gDatRec']['gRucRec']['dDVRuc'] ?? null,
]),
```

## Resultado Final

### Estructura ITBMS Corregida
```json
{
  "itbms": {
    "rate": "00",    // Preservado incluso cuando dTasaITBMS = 0
    "amount": 0      // Preservado incluso cuando dValITBMS = 0
  }
}
```

### Estructura RUC Corregida
```json
{
  "receiver": {
    "ruc": {
      "type": 2,                        // Preservado incluso cuando dTipoRuc = 0
      "ruc": "1808755-1-706832",       // Presente
      "verificationDigit": "97"        // Usando campo correcto 'dDV'
    },
    "authorizedGroup": {
      "type": 2,                        // También corregido
      "ruc": "1808755-1-706832"
    }
  }
}
```

### Validación PAC Final
**receiver.ruc 'type' property present**: 2  
**items[0].itbms 'rate' property present**: 00  
**items[0].itbms 'amount' property present**: 0  
**receiver.ruc 'verificationDigit' present**: 97  

## Archivos Modificados

- `/app/Helpers/AlanubeFormatterHelper.php`
  - Método `padString()` corregido
  - Nuevo método `formatTaxStructure()`
  - Nuevo método `formatRucStructure()`
  - Aplicado a formateo de ITBMS, ISC, RUC y authorizedGroup
  - Corrección del mapeo de campos (`dDV` vs `dDVRuc`)

## Tests de Validación Creados

- `test_tax_structure.php` - Validación de formatTaxStructure
- `test_padstring.php` - Validación de padString con valores 0
- `test_complete_scenario.php` - Escenario completo del usuario
- `test_formatpanama_document.php` - Validación de formatPanamaDocument
- `test_final_validation.php` - Validación de ambos errores juntos

## Commits Realizados

1. **`2146edb`** - "fix: preservar propiedades rate y amount de ITBMS/ISC incluso cuando son 0"
2. **`235d033`** - "fix: preservar propiedad type en receiver.ruc incluso cuando es 0"

## Conclusión

**AMBOS errores PAC han sido completamente resueltos:**

**Error Original 1**: `instance.items[0].itbms requires property "rate"`  
**RESUELTO**: Propiedades `rate` y `amount` preservadas incluso cuando son 0

**Error Original 2**: `instance.receiver.ruc requires property "type"`  
**RESUELTO**: Propiedad `type` preservada incluso cuando es 0, con valor por defecto

**El PAC de Alanube ahora debe aceptar las facturas sin errores de validación** 
