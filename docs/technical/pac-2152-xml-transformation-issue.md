# Análisis PAC Error 2152 - Problema en Transformación XML

## Estado: PROBLEMA IDENTIFICADO

**Fecha**: 2024-12-19  
**Error**: `2152-Item 1: Monto del ITBMS del ítem inválido`  
**Organización**: 155757563-2-2024  

## Problema Real Identificado

### Los valores en Create.php están CORRECTOS:
```json
{
  "gTotData_dTotRec": 5.99,
  "gTotData_iPzPag": "1", 
  "dVTotItems": "5.99"
}
```

### Pero en el XML final están INCORRECTOS:
```xml
<dTotRec>5.60</dTotRec>        <!-- Debería ser 5.99 -->
<iPzPag>5.60</iPzPag>          <!-- Debería ser 1 -->
<dVTotItems>5.60</dVTotItems>  <!-- Debería ser 5.99 -->
```

## Ubicación del Problema

**El problema NO está en Create.php** - Los valores llegan correctamente al HKAService.

**El problema está en la transformación del PHP al XML** - Específicamente en el proceso que convierte el objeto DocumentoElectronico al XML final.

### Análisis del Log HKAService

En el log del HKAService vemos:
```json
"totalesSubTotales": {
  "totalValorRecibido": "5.99",  //  Correcto
  "tiempoPago": "1",             //  Correcto  
  "totalTodosItems": "5.99"      //  Correcto
}
```

Pero el XML final muestra valores diferentes, indicando que hay una transformación incorrecta.

## Investigación Requerida

### 1. Proceso de Serialización XML
- El objeto DocumentoElectronico se serializa correctamente
- Pero el XML final tiene valores intercambiados
- Probablemente hay un mapeo incorrecto en la transformación

### 2. Campos Afectados
- `dTotRec`: 5.99 → 5.60 (valor incorrecto)
- `iPzPag`: "1" → 5.60 (valor totalmente incorrecto)  
- `dVTotItems`: 5.99 → 5.60 (valor incorrecto)

### 3. Patrón del Error
Todos los campos incorrectos muestran **5.60**, que corresponde a `dTotNeto` (precio sin impuestos). Esto sugiere que hay un mapeo cruzado donde múltiples campos están tomando el valor de `dTotNeto`.

## Correcciones Implementadas (Verificadas)

### Create.php - Correcto
- Campo `dVTotItems` corregido para usar `$dVTotItems` en lugar de `$dVTot`
- Logging mejorado confirma valores correctos
- Cálculos ITBMS precisos implementados

### HKAService - Requiere Investigación
- Los valores llegan correctos al HKAService
- La transformación al XML está fallando
- Se agregó logging en línea 177 para capturar valores exactos

## Próximos Pasos

### 1. Ejecutar Nueva Factura
Probar con logging mejorado en HKAService para capturar:
- Valores de entrada exactos 
- Valores en objeto totales
- Detectar dónde ocurre la transformación incorrecta

### 2. Investigar Serialización XML
- Buscar el proceso que convierte DocumentoElectronico a XML
- Identificar mapeo incorrecto de campos
- Corregir transformación de `iPzPag`, `dTotRec`, `dVTotItems`

### 3. Validar Corrección
- Confirmar que XML final envía valores correctos
- Probar con PAC TheFactoryHKA  
- Verificar eliminación del error 2152

## Archivos Modificados

### `app/Services/HKAService.php`
- Logging agregado en línea 177 para debugging
- Investigación de transformación XML pendiente

### `app/Http/Livewire/Admin/Einvoice/Create.php`  
- Campo `dVTotItems` corregido
- Logging detallado implementado
- Cálculos ITBMS precisos funcionando

## Conclusión

**El problema está en la fase de serialización/transformación a XML**, no en los cálculos PHP. Los valores correctos llegan al HKAService pero se transforman incorrectamente al XML final.

La corrección requiere identificar y corregir el mapeo incorrecto que está asignando el valor `5.60` (dTotNeto) a múltiples campos que deberían tener valores diferentes.
