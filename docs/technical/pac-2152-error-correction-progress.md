# Corrección Error PAC 2152 - "Monto del ITBMS del ítem inválido"

## Estado: EN PROCESO

**Fecha**: 2024-12-19  
**Error**: `2152-Item 1: Monto del ITBMS del ítem inválido`  
**Organización**: 155757563-2-2024  
**PAC**: TheFactoryHKA (ID: 8)  

## Análisis del Problema

### Causa Raíz Identificada

El error no estaba en el cálculo del ITBMS del ítem individual, sino en inconsistencias en los campos totales enviados al PAC:

```xml
<!-- XML PROBLEMÁTICO -->
<dValTotItem>5.990000</dValTotItem>  <!--  Correcto -->
<dValITBMS>0.390000</dValITBMS>      <!--  Correcto -->
<dTotRec>5.60</dTotRec>              <!-- INCORRECTO -->
<iPzPag>5.60</iPzPag>                <!-- INCORRECTO -->
<dVTotItems>5.60</dVTotItems>        <!-- INCORRECTO -->
```

### Log de Diagnóstico

```json
{
  "base_price_dPrItem": 5.6,
  "original_itbms_from_db": 0.39, 
  "calculated_itbms": 0.39,
  "dValTotItem_calculated": 5.989999999999999,
  "manual_sum_verification": 5.99,
  "coherence_check": true
}
```

Los cálculos individuales eran correctos, pero los totales enviados al PAC estaban inconsistentes.

## Correcciones Implementadas

### 1. Campo `dVTotItems` (Línea 2456)

**Problema**: Se asignaba `$dVTot` (total general) en lugar de `$dVTotItems` (suma de ítems).

```php
// ANTES (INCORRECTO)
'dVTotItems' => $this->numberFormat($dVTot),

// DESPUÉS (CORREGIDO)
'dVTotItems' => $this->numberFormat($dVTotItems),
```

### 2. Cálculo ITBMS con Precisión DGI

**Métodos añadidos**:
- `calculateCorrectITBMS()`: Recalculo preciso usando tasas estándar (7%, 10%, 15%)
- `validateITBMSCoherence()`: Validación matemática pre-envío

**Integración en `issueDocument()`** (líneas 2235-2275):
```php
// Recalcular ITBMS con precisión DGI
if ($taxable && $dPrItem > 0) {
    $originalITBMS = $this->filterVar(array_get($item, 'Itbms', 0), FILTER_VALIDATE_FLOAT);
    $valITBMS = $this->calculateCorrectITBMS($dPrItem, $item);
    
    // Logging detallado para debugging
    Log::info('DEBUG ITBMS - Ítem ' . ($index + 1), [
        'base_price_dPrItem' => $dPrItem,
        'original_itbms_from_db' => $originalITBMS,
        'calculated_itbms' => $valITBMS,
        'tax_rate_original' => round(($originalITBMS / $dPrItem) * 100, 2),
        'tax_rate_calculated' => round(($valITBMS / $dPrItem) * 100, 2),
    ]);
}
```

### 3. Logging Mejorado

**Nuevos logs para diagnosticar**:
- Debug detallado por ítem individual
- Logging de totales finales antes del envío PAC
- Validación de coherencia matemática

## Estado de Testing

### Comando de Prueba Creado
```bash
docker exec -it docucenter_laravel.test php artisan test:itbms-calculation 155757563-2-2024
```

**Resultado**: Cálculos matemáticos correctos confirmados

### Próximas Validaciones Requeridas

1. **Validar corrección `dVTotItems`**: Verificar que ahora envía 5.60 en lugar de 5.99
2. **Testing con PAC real**: Probar con organización 155757563-2-2024 
3. **Verificar otros campos totales**: Confirmar coherencia de `dTotRec` e `iPzPag`

## Archivos Modificados

### `app/Http/Livewire/Admin/Einvoice/Create.php`
- Línea 2456: Corrección `dVTotItems`
- Líneas 1865-1895: Método `calculateCorrectITBMS()`
- Líneas 1904-1910: Método `validateITBMSCoherence()`
- Líneas 2235-2275: Integración en `issueDocument()`
- Logging mejorado para debugging

### `app/Console/Commands/TestITBMSCalculation.php`
- Comando de testing independiente para validación

## Validaciones Pendientes

### Campos que Requieren Verificación

En el XML problemático detectamos inconsistencias en:

1. **`dTotRec`**: Debe ser 5.99, pero aparece como 5.60
2. **`iPzPag`**: Debe ser el código de tiempo de pago (1), pero aparece como 5.60
3. **`dVTotItems`**: Debe ser 5.60 (suma sin impuestos), ahora corregido

### Plan de Testing

1. **Ejecutar nueva factura**: Con los cambios implementados
2. **Revisar logs nuevos**: Verificar valores finales en gTotData
3. **Comparar XML generado**: Confirmar campos corregidos
4. **Validación PAC**: Probar envío real a TheFactoryHKA

## Próximos Pasos

1. **Logging mejorado**: Implementado para capturar valores exactos
2. **Testing con datos reales**: Pendiente validación con organización específica
3.  **Validación PAC**: Confirmar eliminación del error 2152
4. **Documentación final**: Actualizar cuando se confirme la solución

---

**Impacto Esperado**: Eliminación completa del error PAC 2152 mediante corrección de inconsistencias en totales enviados al servicio TheFactoryHKA.
