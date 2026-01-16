# Investigación PAC Error 2152 - Punto de Transformación Identificado

## Estado: 🔍 INVESTIGANDO TRANSFORMACIÓN SOAP

**Fecha**: 2024-12-19  
**Error**: `2152-Item 1: Monto del ITBMS del ítem inválido`  

## Análisis de la Cadena de Transformación

### ✅ **1. Create.php → HKAService (CORRECTO)**
```json
"gTotData": {
  "dTotRec": "5.99",
  "iPzPag": "1", 
  "dVTotItems": "5.99"
}
```

### ✅ **2. HKAService → DocumentoElectronico (CORRECTO)**
```json
"totalesSubTotales": {
  "totalValorRecibido": "5.99",
  "tiempoPago": "1",
  "totalTodosItems": "5.99"
}
```

### 🔍 **3. DocumentoElectronico → SOAP Request (INVESTIGANDO)**
- Método: `filterNullValues((array) $documentoElectronico)` en línea 278
- Convierte objeto a array antes del envío SOAP
- **Nuevo logging agregado** para capturar valores exactos

### ❌ **4. SOAP Response → XML Final (INCORRECTO)**
```xml
<dTotRec>5.60</dTotRec>        <!-- ❌ Debería ser 5.99 -->
<iPzPag>5.60</iPzPag>          <!-- ❌ Debería ser 1 -->
<dVTotItems>5.60</dVTotItems>  <!-- ❌ Debería ser 5.99 -->
```

## Hipótesis del Problema

### **Hipótesis 1: Transformación filterNullValues**
El método `filterNullValues` podría estar alterando la estructura de datos antes del envío SOAP.

### **Hipótesis 2: Servicio SOAP TheFactoryHKA**
El servicio externo podría tener un mapeo incorrecto donde:
- `totalValorRecibido` → se mapea incorrectamente a `totalPrecioNeto` (5.60)
- `tiempoPago` → se mapea incorrectamente a `totalPrecioNeto` (5.60)  
- `totalTodosItems` → se mapea incorrectamente a `totalPrecioNeto` (5.60)

### **Hipótesis 3: Array Conversion Issues**
El `(array) $documentoElectronico` podría estar creando un array mal estructurado.

## Investigación Implementada

### **Nuevo Logging en HKAService (Línea 285)**
```php
Log::info('HKAService - DEBUG documento filtrado antes SOAP', [
    'dTotRec_filtered' => $filteredDocumentoElectronico['totalesSubTotales']['totalValorRecibido'],
    'iPzPag_filtered' => $filteredDocumentoElectronico['totalesSubTotales']['tiempoPago'],
    'dVTotItems_filtered' => $filteredDocumentoElectronico['totalesSubTotales']['totalTodosItems'],
]);
```

### **Parámetros SOAP**
```php
$parametros = array(
    'tokenEmpresa' => $pacconnection->username,
    'tokenPassword' => $pacconnection->password,
    'documento' => $filteredDocumentoElectronico,
);
$response = $client->__soapCall('Enviar', array($parametros));
```

## Próximos Pasos

### **1. Ejecutar Nueva Factura** 
- Capturar nuevo log de `documento filtrado antes SOAP`
- Verificar si los valores siguen correctos antes del SOAP

### **2. Si valores están correctos antes SOAP:**
- El problema está en el servicio TheFactoryHKA externo
- Podría requerir contactar al proveedor PAC
- O investigar documentación de mapeo de campos

### **3. Si valores están incorrectos antes SOAP:**
- El problema está en `filterNullValues` o array conversion
- Investigar transformación específica
- Corregir mapeo interno

## Archivos Investigados

### **`app/Services/HKAService.php`**
- ✅ Logging agregado en línea 285
- 🔍 Método `filterNullValues` (línea 231)
- 🔍 Array conversion `(array) $documentoElectronico` (línea 278)

### **`app/Utils/hka/Totales.php`**
- ✅ Estructura de clase verificada - sin problemas aparentes

### **`app/Utils/hka/DocumentoElectronico.php`**
- ✅ Estructura de clase verificada - sin problemas aparentes

## Evidencia Acumulada

### **Patrón del Error**
Todos los campos incorrectos muestran `5.60` que corresponde a:
- `dTotNeto` / `totalPrecioNeto` (precio sin impuestos)
- Sugiere mapeo cruzado donde múltiples campos toman valor de precio neto

### **Campos Afectados**
- `dTotRec` (Total Recibido): 5.99 → 5.60
- `iPzPag` (Tiempo Pago): "1" → 5.60  
- `dVTotItems` (Total Items): 5.99 → 5.60

**Conclusión Preliminar**: El problema parece estar en el mapeo del servicio SOAP externo, pero necesitamos confirmar con el nuevo logging si los valores llegan correctos al SOAP.
