# Fix: Digifact Response Code Validation Error

**Date**: February 1, 2026  
**Status**: ✅ RESOLVED  
**Affected Component**: DigifactService.php  
**Issue**: Success Response Treated as Error  

## Problem

Digifact was successfully emitting invoices (HTTP 200, certificate generated), but the system was treating the success response as an error:

```
[2026-02-01 03:50:18] production.ERROR: Error al certificar documento con Digifact (JSON) 
{"status":200,"codigo":"1","mensaje":"Proceso de certificacion realizado correctamente!"}
```

Despite the message saying "**Certification process completed successfully!**", the system was returning an error to the user.

## Root Cause

**Incorrect Response Code Validation**

The code was checking:
```php
if ($response->successful() && $codigo == '200') {  // Looking for codigo == '200'
    // Success
}
```

But Digifact returns:
```json
{
  "codigo": 1,                                      // Integer 1, NOT string '200'
  "mensaje": "Proceso de certificacion realizado correctamente!",
  "status": 200                                     // HTTP status is 200
}
```

**The Mismatch**:
- HTTP Status: ✅ 200 (Success)
- Response Code (`codigo`): ✅ 1 (Digifact's success indicator)
- Expected Code: ❌ '200' (Wrong expectation)

Result: Valid success response was treated as error because `1 != '200'`

## Solution

Accept **both** response codes as success:

```php
// BEFORE (WRONG):
if ($response->successful() && $codigo == '200') {

// AFTER (CORRECT):
if ($response->successful() && in_array($codigo, ['1', '200'])) {
```

### Digifact Response Codes

Based on testing:
- **codigo: 1** = Success (document certified)
- **codigo: 200** = Alternative success response (possibly for different endpoints)
- Other codes = Error states

## Implementation

**File**: [app/Services/DigifactService.php](../../app/Services/DigifactService.php)

### JSON Response Handling (Line ~725)
```php
$codigo = (string) ($jsonResponse['codigo'] ?? '500');
$cufe = (string) ($jsonResponse['CUFE'] ?? '');
$mensaje = (string) ($jsonResponse['mensaje'] ?? '');
$descripcion = (string) ($jsonResponse['descripcion'] ?? '');

// Digifact usa codigo: 1 para éxito (no 200). Status HTTP 200 también indica éxito.
if ($response->successful() && in_array($codigo, ['1', '200'])) {
    // Success handling
}
```

### XML Response Handling (Line ~795)
```php
$codigo = (string) ($xmlResponse->codigo ?? '500');
$cufe = (string) ($xmlResponse->CUFE ?? '');
$pdfBase64 = (string) ($xmlResponse->pdf_base64 ?? '');
$xmlBase64 = (string) ($xmlResponse->xml_base64 ?? '');
$mensaje = (string) ($xmlResponse->mensaje ?? '');
$descripcion = (string) ($xmlResponse->descripcion ?? '');

// Digifact usa codigo: 1 para éxito (no 200). Status HTTP 200 también indica éxito.
if ($response->successful() && in_array($codigo, ['1', '200'])) {
    // Success handling
}
```

## Commit

**Commit 8a20ff58**: Accept codigo 1 as success response
```
fix: aceptar codigo 1 como respuesta exitosa de Digifact además de 200
```

## Verification

After deploying **8a20ff58**, the system will:

1. ✅ Accept HTTP Status 200
2. ✅ Accept Digifact `codigo: 1` as success
3. ✅ Return success response to user instead of error
4. ✅ Properly handle PDF and signed XML responses

### Before Fix Log
```
[ERROR] Error al certificar documento con Digifact (JSON)
{"status": 200, "codigo": "1", "mensaje": "Proceso de certificacion realizado correctamente!"}
```

### After Fix
```
[INFO] Documento certificado exitosamente con Digifact
{"organization_id": 121, "cufe": "..."}
```

## Deployment

After deploying **8a20ff58**, execute on server:

```bash
docker exec docucenter_laravel.test php artisan config:clear
docker exec docucenter_laravel.test php artisan cache:clear
```

Then retry invoice emission - should now show success.

## Learning Points

1. **API Response Codes Vary**: Don't assume standard HTTP conventions
   - HTTP 200 ≠ Application success code
   - PACs may use custom success indicators (1, 200, 0, etc.)

2. **Test with Real API**: Mock responses don't always match production
   - Testing must include actual PAC success responses
   - Document success indicators in API integration specs

3. **Flexible Validation**: Use `in_array()` for multiple valid codes
   - Allows future code variations without code changes
   - More maintainable than hard-coded comparisons

## Related Errors Already Fixed

This was the **3rd critical issue** in Digifact integration:

1. ✅ **Coordinate Precision** (ad47ac6f) - Decimals were being truncated
2. ✅ **Unit of Measure** (d85d2543) - Uppercase codes rejected by schema
3. ✅ **Response Code Validation** (8a20ff58) - Success treated as error

---

**Commit**: 8a20ff58  
**Date Fixed**: 2026-02-01  
**Impact**: Critical - Invoices were being emitted successfully but reported as failed
