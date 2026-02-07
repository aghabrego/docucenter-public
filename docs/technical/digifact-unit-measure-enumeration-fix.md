# Fix: Digifact Unit of Measure Enumeration Error 3010

**Date**: February 1, 2026  
**Status**: ✅ RESOLVED  
**Affected Component**: DigifactXmlBuilder.php  
**Error Code**: 3010 (XML_INVALID)  

## Problem

When emitting invoices through Digifact PAC, the system was failing with Error 3010:

```
The 'http://dgi-fep.mef.gob.pa:cUnidad' element is invalid - 
The value 'UND' is invalid according to its datatype 'http://dgi-fep.mef.gob.pa:unidadesMedida' - 
The Enumeration constraint failed.
```

**Root Cause**: The unit of measure code was being sent in **uppercase** (`UND`) when Digifact's XSD schema requires **lowercase** (`und`).

## Technical Analysis

### Enumeration Constraint

Digifact's XSD schema for `cUnidad` (unit of measure) has a strict enumeration constraint with valid values in lowercase:

```
Valid values: und, l, kg, m, m2, m3, etc. (ALL LOWERCASE)
Invalid: UND (uppercase) - ENUMERATION CONSTRAINT FAILED
```

### Root Cause Location

**File**: [app/Services/DigifactXmlBuilder.php](../../app/Services/DigifactXmlBuilder.php#L441-L443)

**Method**: `buildItems()` (lines 441-443)

**Original Code**:
```php
// UnitOfMeasure
$unit = $lineItem['cUnidad'] ?? 'UND';  // DEFAULT: 'UND' (WRONG!)
$item->addChild('UnitOfMeasure', $unit);
```

**Problem**: 
- Default value `'UND'` is uppercase
- `$lineItem['cUnidad']` might also be uppercase
- Digifact's XSD pattern rejects all uppercase unit codes

### Fixed Implementation

```php
// UnitOfMeasure
// Digifact require lowercase unit codes (und, l, kg, etc.) not uppercase (UND)
$unit = strtolower($lineItem['cUnidad'] ?? 'und');  // Normalize to lowercase
$item->addChild('UnitOfMeasure', $unit);
```

**Solution**: Use `strtolower()` to normalize unit codes to lowercase, and change default from `'UND'` to `'und'`

## Valid Unit Code Examples

Based on Digifact XSD enumeration:

```
und     - Unidad (Unit)
l       - Litro (Liter)
kg      - Kilogramo (Kilogram)
m       - Metro (Meter)
m2      - Metro cuadrado (Square Meter)
m3      - Metro cúbico (Cubic Meter)
h       - Hora (Hour)
...
```

## Commits

**Commit d85d2543**: Fix unit measure normalization to lowercase
```
fix: normalizar unidad de medida a minúsculas para validación Digifact
```

## Deployment

After deploying **d85d2543**, execute on server:

```bash
docker exec docucenter_laravel.test php artisan config:clear
docker exec docucenter_laravel.test php artisan cache:clear
```

## Verification

The fix ensures that:

1. ✅ Unit codes are always sent in lowercase
2. ✅ Default value is `'und'` not `'UND'`
3. ✅ Digifact's enumeration constraint passes validation
4. ✅ XML schema validation (Error 3010) is resolved

### Test Cases

**Before Fix**:
```xml
<UnitOfMeasure>UND</UnitOfMeasure>  <!-- ERROR: Enumeration constraint failed -->
```

**After Fix**:
```xml
<UnitOfMeasure>und</UnitOfMeasure>  <!-- OK: Matches enumeration -->
```

## Related Issues

- Digifact enforces strict XSD validation with enumeration constraints
- Unit codes must be in lowercase per DGI standards
- Error 3010 indicates schema validation failure
- Multiple enumeration constraints must pass: coordinates precision, unit codes, etc.

## Prevention Strategy

When integrating with Digifact:

1. **Always consult XSD schema** for enumeration constraints
2. **Normalize inputs** to required case/format (lowercase for units)
3. **Use strtolower() or strtoupper()** as needed
4. **Document valid enumeration values** in code comments
5. **Test with real Digifact server** before production deployment

---

**Commit**: d85d2543  
**Date Fixed**: 2026-02-01  
**Related To**: Error 3010, XML Schema Validation
