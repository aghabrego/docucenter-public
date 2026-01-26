# Fix: Error "datosFacturaExportacion es requerido"

## Problema Identificado

El PAC Alanube estaba devolviendo el error:
```
El campo datosFacturaExportacion es requerido
```

## Causa Raíz

El PAC Alanube esperaba el campo `datosFacturaExportacion` al nivel raíz del JSON, pero DocuCenter estaba enviando el campo como `exportation`.

### Análisis Técnico

1. **Código Original**: 
   ```php
   $dGen['exportation'] = $exportationData;
   ```

2. **Campo Esperado por PAC**:
   ```php
   $dGen['datosFacturaExportacion'] = $exportationData;
   ```

## Solución Implementada

### Archivos Modificados

- `app/Helpers/AlanubeFormatterHelper.php`

### Cambios Realizados

1. **Función formatForPanama()** (línea ~268):
   ```php
   // ANTES
   $dGen['exportation'] = $exportationData;
   
   // DESPUÉS
   $dGen['datosFacturaExportacion'] = $exportationData;
   ```

2. **Función formatForDominicana()** (línea ~728):
   ```php
   // ANTES
   $dGen['exportation'] = $exportationData;
   
   // DESPUÉS
   $dGen['datosFacturaExportacion'] = $exportationData;
   ```

### Logging Mejorado

Se agregó logging detallado para tracking:
```php
\Illuminate\Support\Facades\Log::info('[AlanubeFormatterHelper] Moved exportation to root level as datosFacturaExportacion', [
    'exportation_moved' => 'YES',
    'field_name' => 'datosFacturaExportacion',
    'exportation_data' => $exportationData
]);
```

## Impacto

### Resuelto
- Error "El campo datosFacturaExportacion es requerido" en facturas de exportación
- Compatibilidad con PAC Alanube para Panamá y República Dominicana
- Facturas de exportación (tipo 03) ahora se procesan correctamente

### Estructura JSON Resultado

**Antes del fix**:
```json
{
  "information": {...},
  "receiver": {...},
  "exportation": {
    "incoterm": "FOB",
    "currency": "USD",
    "exchangeRate": 1.0,
    "amount": 1000.50,
    "port": "Puerto de Balboa"
  }
}
```

**Después del fix**:
```json
{
  "information": {...},
  "receiver": {...},
  "datosFacturaExportacion": {
    "incoterm": "FOB",
    "currency": "USD", 
    "exchangeRate": 1.0,
    "amount": 1000.50,
    "port": "Puerto de Balboa"
  }
}
```

## Testing

### Scripts de Verificación

1. **docs/testing/test-datos-factura-exportacion-fix.php**
   - Test completo con datos de prueba
   - Verifica estructura JSON generada

2. **docs/testing/verify-datos-factura-exportacion-fix.php**
   - Verificación rápida en código fuente
   - Confirma que no hay código legacy

### Ejecución de Tests

```bash
# Verificación del código fuente
php docs/testing/verify-datos-factura-exportacion-fix.php

# Test completo (requiere ambiente Laravel)
php docs/testing/test-datos-factura-exportacion-fix.php
```

## Contexto de Desarrollo

### Issues Relacionados
- Fix previo de preservación de datos de customer 
- Validación de moneda USD/PAB para Panamá 
- Error "datosFacturaExportacion es requerido" 

### Proceso de Debug
1. Búsqueda del campo en código fuente → No encontrado
2. Análisis de logs PAC → Campo específico requerido
3. Identificación en AlanubeFormatterHelper → Nombre incorrecto
4. Fix aplicado en ambas funciones (Panamá y Dominicana)
5. Testing y verificación → Confirmado

## Mantenimiento

### Puntos de Atención
- El PAC Alanube es sensible al nombre exacto de campos
- Mantener consistencia entre Panamá y República Dominicana
- Monitorear logs para validar funcionamiento

### Futuras Mejoras
- Considerar configuración dinámica de nombres de campos por PAC
- Documentar especificaciones exactas de cada proveedor PAC
- Agregar validación previa al envío

## Commits

- `fix: corregir nombre de campo datosFacturaExportacion para PAC Alanube`
- `fix: corregir campo datosFacturaExportacion para República Dominicana y agregar tests`

---

**Estado**: RESUELTO  
**Fecha**: 2025-01-27  
**Responsable**: DocuCenter AI Assistant
