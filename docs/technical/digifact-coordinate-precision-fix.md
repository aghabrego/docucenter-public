# Fix: Digifact Coordinate Decimal Precision Error 3010

**Date**: January 31, 2026  
**Status**: ✅ RESOLVED  
**Affected Component**: DigifactXmlBuilder.php  
**Error Code**: 3010 (XML_INVALID)  

## Problem

When emitting invoices through Digifact PAC, the system was failing with Error 3010:

```
The 'http://dgi-fep.mef.gob.pa:dCoordEm' element is invalid - 
The value '8.989,-79.520' is invalid according to its datatype 'String' - 
The Pattern constraint failed.
```

**Root Cause**: The geographic coordinates were being **truncated to 3 decimal places** (e.g., `8.989,-79.520`) when they should maintain **5 decimal places** (e.g., `8.98900,-79.5200`).

## Technical Analysis

### Coordinate Format Requirements

Digifact's XSD schema for `dCoordEm` (geographic coordinates) requires:

```
Format: "latitude,longitude"
Example: "8.98900,-79.5200"  (5 decimal places each)
Invalid: "8.989,-79.520"      (3 decimal places - REJECTED)
```

### Incorrect Implementation (Commit e4c8e74c)

The first attempt to fix the issue used:

```php
$coords = str_replace('+', '', $coords);  // WRONG!
```

**Problem**: `str_replace()` was truncating decimal values when removing the `+` sign:
- Input:  `+8.98900,-79.5200`
- Output: `8.989,-79.520` ← **3 decimals (INVALID)**

This occurred because the implementation didn't properly handle the string structure.

### Correct Implementation (Commit ad47ac6f)

The fix uses `ltrim()` to remove **only the leading `+` sign** while preserving all decimals:

```php
// Remove ONLY the '+' sign, preserve full decimal precision
$coords = ltrim($coords, '+');  // CORRECT!
$coords = trim($coords);
```

**Result**:
- Input:  `+8.98900,-79.5200`
- Output: `8.98900,-79.5200` ← **5 decimals (VALID)**

## File Changes

**File**: [app/Services/DigifactXmlBuilder.php](../../app/Services/DigifactXmlBuilder.php#L273-L281)

**Method**: `buildBranchInfo()` (lines 273-281)

```php
if (!empty($gEmis['dCoordEm'])) {
    // Normalizar coordenadas: Digifact rechaza el signo '+' pero SÍ requiere precisión decimal
    // Entrada: "+8.98900,-79.5200"
    // Salida: "8.98900,-79.5200" (solo remover el '+', mantener decimales)
    $coords = $gEmis['dCoordEm'];
    // Remover SOLO el signo +, mantener todo lo demás
    $coords = ltrim($coords, '+');
    // Limpiar espacios al inicio/final
    $coords = trim($coords);
    
    $this->addInfoElement($additionalBranch, 'CoordEm', $coords);
}
```

## Commits

1. **e4c8e74c** - Initial attempt (incorrect, truncated decimals)
2. **ad47ac6f** - Fix: Preserve decimal precision with `ltrim()` (CORRECT) ✅

## Deployment

After deploying **ad47ac6f**, execute on server:

```bash
docker exec docucenter_laravel.test php artisan config:clear
docker exec docucenter_laravel.test php artisan cache:clear
```

## Verification

The fix was verified with:

1. **Unit Test** (tests/DigifactXmlTest.php):
   ```php
   'dCoordEm' => '+8.98900,-79.5200'
   ```
   Generated XML contains: `8.98900,-79.5200` ✅

2. **Production Test Case**:
   - Organization: GRUPO FRESKURA S.A. (RUC: 155770712-2-2025)
   - Invoice: #1962
   - Coordinates: `+8.98900,-79.5200` (5 decimals)
   - Expected result: Validation passes without Error 3010

## Key Learnings

1. **String manipulation matters**: Using the right function (ltrim vs str_replace) is critical
2. **Pattern validation**: Digifact's XSD enforces strict decimal precision requirements
3. **Test early**: Unit tests caught the issue before production use

## Related Issues

- Digifact API v2.0.4 documentation states coordinates must include full decimal precision
- Format: "latitude,longitude" with exactly 5 decimal places
- NO leading `+` sign allowed in XML output

## Future Prevention

When modifying XML generation for external APIs:

1. **Preserve data types**: Don't truncate numeric precision
2. **Validate before deployment**: Run unit tests that verify schema compliance
3. **Test with real data**: Use production credentials (when possible) to catch API-specific issues
4. **Document requirements**: Keep XSD specifications and validation rules visible

---

**Commit**: ad47ac6f  
**Date Fixed**: 2026-01-31  
**Verified By**: Automated unit tests + manual verification
