# Debug Plan: Diagnosing Destination Foreign Error

## Status: Error Persiste
**Error**: "The destination of the operation cannot be Foreign if the Document Type is Internal Operation Invoice"

## Hallazgos del Debug

### 1. Campo de Tipo de Documento Corregido
- **Antes**: Buscando `iTipoDoc` (era null)
- **Ahora**: Buscando `iDoc` (es "01")

### 2. Logs Anteriores Mostraban:
```json
{
  "country_detected": "PA",
  "document_type": "01",
  "document_type_internal": "N/A",
  "original_destination": "1",
  "country_receptor": "CL"
}
```

### 3. Problemas Identificados:
- Campo equivocado en `determineDestination()` CORREGIDO
- Debug logging agregado en `AlanubeService` AGREGADO
- Verificación de constantes AGREGADO

## Cambios Aplicados

### AlanubeFormatterHelper.php
```php
// ANTES (Campo incorrecto)
$documentType = $data['dGen']['iTipoDoc'] ?? null; // null

// AHORA (Campo correcto)
$documentType = $data['dGen']['iDoc'] ?? null; // "01"
```

### AlanubeService.php
```php
// Debug logging agregado
\Illuminate\Support\Facades\Log::info('[AlanubeService] buildInformation debug', [
    'documentType' => $documentType,
    'INTERNAL_OPERATION_const' => self::INTERNAL_OPERATION,
    'original_destination' => $info['destination'] ?? 'N/A',
    'comparison_result' => ($documentType === self::INTERNAL_OPERATION) ? 'MATCH' : 'NO_MATCH'
]);
```

## Próximo Test

### Expectativas para el siguiente intento:
1. **AlanubeFormatterHelper** logs:
   ```
   [AlanubeFormatterHelper] determineDestination {"documentType_iDoc":"01",...}
   [AlanubeFormatterHelper] Forcing destination=1 for Internal Operation (iDoc=01)
   ```

2. **AlanubeService** logs:
   ```
   [AlanubeService] buildInformation debug {"documentType":"01","INTERNAL_OPERATION_const":"01",...}
   [AlanubeService] Forcing destination=1 for Internal Operation
   ```

3. **PAC Response**: Sin error AP3040

### Si el error persiste después de estos cambios:
- Confirmar que ambas capas están forzando destination=1
- Investigar si hay transformación posterior que revierte el cambio
- Verificar JSON enviado al PAC para confirmar destination=1

## Status: LISTO PARA RETRY
**Confianza**: 85% - Campo corregido + debug completo
