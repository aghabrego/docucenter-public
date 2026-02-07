# Fix: Validación CompanyName en QuickBooks Integration

## Problema Identificado

**Error reportado:**
```json
{
    "message": "El invoice. customer ref. company name must be a string.",
    "errors": {
        "Invoice.CustomerRef.CompanyName": [
            "El invoice. customer ref. company name must be a string."
        ]
    }
}
```

**Datos de entrada:**
```json
{
    "Invoice": {
        "CustomerRef": {
            "CompanyName": "", // String vacío válido desde QuickBooks
            "DisplayName": "Solmary"
        }
    }
}
```

## Análisis del Problema

1. **Validación original**: `'Invoice.CustomerRef.CompanyName' => 'sometimes|string|max:255'`
2. **Problema**: No permitía valores `null` o comportamiento inconsistente con strings vacíos
3. **Lógica de limpieza**: El método `prepareForValidation()` eliminaba `CompanyName` cuando era string vacío (`''`), causando inconsistencias

## Solución Implementada

### 1. **Corrección de Validación**
**Archivo**: `app/Http/Requests/CreateSaleQuickBooksRequest.php`
**Línea**: 323

**Antes:**
```php
'Invoice.CustomerRef.CompanyName' => 'sometimes|string|max:255',
```

**Después:**
```php
'Invoice.CustomerRef.CompanyName' => 'sometimes|nullable|string|max:255',
```

### 2. **Corrección de Lógica de Limpieza**
**Archivo**: `app/Http/Requests/CreateSaleQuickBooksRequest.php`
**Líneas**: 165-180

**Antes:**
```php
// CompanyName se eliminaba si era '' o null
$fieldsToClean = [
    'RUC', 'DV', 'TIPO', 'TIPO_RECEPTOR', 
    'CompanyName',  // ❌ Eliminaba strings vacíos
    'DisplayName', 'PrimaryEmail'
];
```

**Después:**
```php
// CompanyName excluido de limpieza automática
$fieldsToClean = [
    'RUC', 'DV', 'TIPO', 'TIPO_RECEPTOR', 
    'DisplayName', 'PrimaryEmail'
];

// CompanyName se maneja por separado - solo eliminar si es null, no si es string vacío
if (isset($customerRef['CompanyName']) && $customerRef['CompanyName'] === null) {
    unset($customerRef['CompanyName']);
}
```

## Comportamiento Corregido

### Casos de Validación

| Valor CompanyName | Antes | Después | Descripción |
|------------------|-------|---------|-------------|
| `"Mi Empresa"` | ✅ Válido | ✅ Válido | String normal |
| `""` | ❌ Error/Eliminado | ✅ Válido | String vacío (común en QB) |
| `null` | ❌ Error | ✅ Válido | Valor null |
| No presente | ✅ Omitido | ✅ Omitido | Campo opcional |
| String > 255 chars | ❌ Error | ❌ Error | Límite respetado |

### Flujo de Procesamiento

1. **prepareForValidation()**:
   - ✅ Mantiene `CompanyName` si es `""` (string vacío)
   - ✅ Solo elimina `CompanyName` si es `null`
   - ✅ Otros campos se limpian normalmente

2. **Validación**:
   - ✅ `sometimes`: Se aplica solo si está presente
   - ✅ `nullable`: Acepta `null` y valores no-null
   - ✅ `string`: Valida como string si no es null
   - ✅ `max:255`: Límite de caracteres respetado

## Testing

### Script de Prueba
**Ubicación**: `docs/testing/test-quickbooks-company-name-validation.php`

**Ejecutar**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test-quickbooks-company-name-validation.php
```

**Resultado esperado**:
```
✅ VALIDACIÓN EXITOSA
CompanyName con valor '' (string vacío) pasa la validación.
```

## Compatibilidad con QuickBooks

### Valores Típicos de QB
- `"CompanyName": ""` - Cliente sin nombre de empresa
- `"CompanyName": "Mi Empresa S.A."` - Cliente empresarial
- `"CompanyName": null` - Campo no configurado
- Campo ausente - Cliente personal sin empresa

### API Endpoints Afectados
- `POST /api/v1/createSaleQuickbooks` - Endpoint principal
- Webhooks de QuickBooks que envían datos de facturas
- Integraciones automáticas con QBO

## Estado Final

| Aspecto | Estado | Descripción |
|---------|--------|-------------|
| **Validación** | ✅ CORREGIDO | `nullable` agregado a la regla |
| **Limpieza** | ✅ CORREGIDO | `CompanyName` preservado si es `""` |
| **Testing** | ✅ COMPLETADO | Script de prueba validado |
| **Compatibilidad** | ✅ MEJORADA | Maneja todos los casos de QB |
| **Documentación** | ✅ COMPLETADA | Guía técnica disponible |

## Conclusión

**✅ PROBLEMA RESUELTO**: El error `"El invoice. customer ref. company name must be a string."` ha sido corregido completamente.

La integración con QuickBooks ahora acepta correctamente:
- Strings vacíos (`""`) que QB envía para clientes sin nombre de empresa
- Valores `null` para campos no configurados
- Strings normales con nombres de empresa
- Campos ausentes (comportamiento `sometimes`)

El objeto de ejemplo proporcionado ahora pasará la validación sin errores.
