# Corrección Condicional de Estructura de Exportación en AlanubeFormatterHelper

## Problema Identificado
El PAC Alanube reportaba el error de validación:
```
"instance requires property exportation"
```

**Problema Principal**: La estructura `exportation` se incluía **siempre** para todos los tipos de documento, pero según las normativas DGI, solo debe incluirse para tipos específicos.

## Análisis del Error

### Error Original (Resuelto)
El error se originaba porque la estructura de datos de exportación estaba siendo mapeada desde la ruta incorrecta:
- **Ruta incorrecta**: `$data['dGen']['gFExp']`
- **Ruta correcta**: `$data['gFExp']`

### Problema Actual (Resuelto)
**Inclusión Incorrecta**: La estructura `exportation` se incluía para **todos** los documentos
**Solución**: Incluir `exportation` solo para tipos de documento que la requieren según DGI

## Reglas DGI para Estructura gFExp (exportation)

### ✅ Tipos que REQUIEREN exportation:
- **Tipo 02**: Factura de importación
- **Tipo 03**: Factura de exportación

### ❌ Tipos que NO DEBEN incluir exportation:
- **Tipo 01**: Factura de operación interna (incluso con receptor extranjero)
- **Tipo 04-09**: Notas de crédito, débito, zona franca, reembolso

## Archivos Modificados
- `app/Helpers/AlanubeFormatterHelper.php`
  - Método `formatForPanama()` (línea ~165)
  - Método `formatForDominicana()` (línea ~554)

## Cambios Realizados

### Antes (Problemático):
```php
// Siempre se incluía exportation para todos los tipos
$gDatRec = self::removeNullValues([
    // ... otros campos
    'exportation' => self::removeNullValues([
        'incoterm' => $data['gFExp']['cCondEntr'] ?? null,
        // ... más campos
    ]),
]);
```

### Después (Condicional):
```php
// Estructura base sin exportation
$gDatRec = self::removeNullValues([
    // ... otros campos sin exportation
]);

// Solo agregar estructura exportation para tipos específicos
$documentType = $data['dGen']['iDoc'] ?? '01';
$exportationRequiredTypes = ['02', '03']; // Importación y exportación

if (in_array($documentType, $exportationRequiredTypes) && isset($data['gFExp'])) {
    $gDatRec['exportation'] = self::removeNullValues([
        'incoterm' => $data['gFExp']['cCondEntr'] ?? null,
        'currency' => $data['gFExp']['cMoneda'] ?? null,
        'otherCurrency' => $data['gFExp']['cMonedaDesc'] ?? null,
        'exchangeRate' => $data['gFExp']['dCambio'] ?? null,
        'amount' => $data['gFExp']['dVTotEst'] ?? null,
        'port' => $data['gFExp']['dPuertoEmbarq'] ?? null,
    ]);
}
```

## Impacto
- ✅ **CRÍTICO**: Resuelve error PAC para facturas internas con receptores extranjeros
- ✅ Permite procesar facturas tipo 01 con clientes extranjeros sin error
- ✅ Mantiene funcionalidad completa para exportación (tipo 03) e importación (tipo 02)
- ✅ Cumple estrictamente con normativas DGI sobre cuándo incluir gFExp
- ✅ Compatibilidad para Panamá y República Dominicana

## Casos de Uso Específicos

### 🇵🇦 Panamá
| Tipo | Descripción | Receptor Extranjero | ¿Incluir exportation? |
|------|-------------|-------------------|----------------------|
| 01   | Operación interna | Sí (ej: cliente USA) | ❌ **NO** |
| 02   | Importación | Cualquiera | ✅ **SÍ** |
| 03   | Exportación | Sí (extranjero) | ✅ **SÍ** |

### 🇩🇴 República Dominicana
| Tipo | Descripción | ¿Incluir exportation? |
|------|-------------|----------------------|
| 1    | Factura estándar | ❌ **NO** |
| 2    | Importación | ✅ **SÍ** |
| 3    | Exportación | ✅ **SÍ** |
    'amount' => $data['gFExp']['dVTotEst'] ?? null,
    'port' => $data['gFExp']['dPuertoEmbarq'] ?? null,
]),
```

## Impacto
- ✅ Resuelve error de validación PAC para documentos de exportación (tipo 03)
- ✅ Permite procesar correctamente facturas de exportación 
- ✅ Mapeo correcto de datos gFExp según estructura DGI Panamá
- ✅ Compatibilidad con ambos países (Panamá y República Dominicana)

## Campos de Exportación Afectados
- `cCondEntr` (Incoterm)
- `cMoneda` (Código de moneda)
- `cMonedaDesc` (Descripción de moneda)
- `dCambio` (Tipo de cambio)
- `dVTotEst` (Total en moneda extranjera)
- `dPuertoEmbarq` (Puerto de embarque)

## Testing
Para probar la corrección:
1. Crear factura de exportación (documento tipo 03)
2. Completar campos de información de exportación en el paso 3
3. Verificar que la factura se procese correctamente sin errores PAC
4. Confirmar que los datos de exportación aparezcan correctamente en el XML enviado

## Fecha de Corrección
2024-12-19

## Estado
✅ COMPLETADO - Errores de validación PAC resueltos para documentos de exportación
