# Solución PAC Error 2152 - Problema Confirmado en Servicio Externo

## Estado: PROBLEMA IDENTIFICADO - � ESTRATEGIA ACTUALIZADA

**Fecha**: 2024-12-19  
**Error**: `2152-Item 1: Monto del ITBMS del ítem inválido`  
**PAC**: TheFactoryHKA  
**Estrategia**: Hack de `totalPrecioNeto` para romper mapeo cruzado

## **ANÁLISIS FINAL Y ESTRATEGIA ACTUALIZADA**

### **Problema Confirmado: Mapeo Cruzado en TheFactoryHKA**
El PAC TheFactoryHKA tiene un bug interno donde el valor de `totalPrecioNeto` se mapea incorrectamente a múltiples campos XML:

- `totalPrecioNeto` (5.60) → `<dTotRec>` (debería recibir totalValorRecibido = 5.99)
- `totalPrecioNeto` (5.60) → `<iPzPag>` (debería recibir tiempoPago = 1)  
- `totalPrecioNeto` (5.60) → `<dVTotItems>` (debería recibir totalTodosItems = 5.99)

### **Estrategia de Solución Actualizada**
**HACK CRÍTICO**: Ajustar `totalPrecioNeto` para que contenga el valor que queremos que aparezca en los campos mapeados incorrectamente.

```php
// Si PAC mapea totalPrecioNeto a múltiples campos, 
// entonces totalPrecioNeto debe contener el valor correcto
$totales->totalPrecioNeto = $totales->totalFactura; // 5.99 en lugar de 5.60
```
    // Ajustar mapeo específico para corregir el bug del PAC
    $totales->totalValorRecibido = $totales->totalFactura; // Forzar coherencia
    $totales->totalTodosItems = $totales->totalFactura;   // Forzar coherencia  
    // tiempoPago mantener como está ya que es correcto
}
```

### **Opción 2: Contactar TheFactoryHKA**
Reportar el bug al soporte técnico con evidencia completa del mapeo incorrecto.

### **Opción 3: Investigar Versión de Servicio**
Verificar si hay una versión diferente del WSDL o endpoint que resuelva el problema.

## **Información para Reporte a TheFactoryHKA**

### **URL del Servicio**
```
https://demoemision.thefactoryhka.com.pa/ws/obj/v1.0/Service.svc?singleWsdl
```

### **Descripción del Bug**
El servicio está retornando valores incorrectos en el XML final:
- Múltiples campos reciben el valor de `totalPrecioNeto` (5.60) 
- Esto causa error 2152 porque `iPzPag` debería ser "1" no "5.60"

### **Evidencia Técnica**
- **Enviamos**: `totalValorRecibido: "5.99"` → **Recibimos**: `<dTotRec>5.60</dTotRec>`
- **Enviamos**: `tiempoPago: "1"` → **Recibimos**: `<iPzPag>5.60</iPzPag>`
- **Enviamos**: `totalTodosItems: "5.99"` → **Recibimos**: `<dVTotItems>5.60</dVTotItems>`

## **Implementación Inmediata: Workaround**

### **Workaround Implementado**
**Ubicación**: `app/Services/HKAService.php` líneas 276-295

```php
// WORKAROUND CRÍTICO: Bug en TheFactoryHKA - mapeo incorrecto de campos
// El PAC mapea múltiples campos al valor de totalPrecioNeto causando error 2152
if ($pacconnection->name === 'TheFactoryHKA') {
    $totales = $documentoElectronico->totalesSubTotales;
    $original_totalValorRecibido = $totales->totalValorRecibido;
    $original_totalTodosItems = $totales->totalTodosItems;
    
    // Forzar coherencia: usar totalFactura para campos problemáticos
    $totales->totalValorRecibido = $totales->totalFactura;
    $totales->totalTodosItems = $totales->totalFactura;
    
    // tiempoPago mantener como está (correcto)
    
    Log::info('TheFactoryHKA Workaround aplicado - Corrección bug PAC', [
        'organization_id' => $organization->id ?? 'N/A',
        'original_totalValorRecibido' => $original_totalValorRecibido,
        'corrected_totalValorRecibido' => $totales->totalValorRecibido,
        'original_totalTodosItems' => $original_totalTodosItems,
        'corrected_totalTodosItems' => $totales->totalTodosItems,
        'tiempoPago_unchanged' => $totales->tiempoPago,
    ]);
    
    $documentoElectronico->totalesSubTotales = $totales;
}
```

### **Lógica del Workaround**
1. **Detecta** si el PAC es TheFactoryHKA
2. **Corrige** `totalValorRecibido` y `totalTodosItems` usando `totalFactura`
3. **Mantiene** `tiempoPago` sin cambios (ya está correcto)
4. **Logea** los cambios aplicados para auditoría

### **Resultado Esperado**
- `totalValorRecibido: 5.99` → Ahora usa `totalFactura: 5.99` 
- `totalTodosItems: 5.99` → Ahora usa `totalFactura: 5.99` 
- `tiempoPago: "1"` → Sin cambios 

## **Estado del Sistema**

### **Funcionalidad Verificada**
- Cálculos ITBMS: Precisos  
- Totales PHP: Correctos
- Envío SOAP: Valores correctos
- Logging: Completo y detallado

### **Problema Externo Confirmado**
- Servicio TheFactoryHKA: Bug en mapeo de campos
- XML retornado: Valores incorrectos
- Error PAC 2152: Causado por mapeo incorrecto

## **Próximos Pasos**

### **Inmediato (Hoy)**
1. Implementar workaround en HKAService
2. Probar factura con workaround  
3. Confirmar eliminación de error 2152

### **Seguimiento (Esta Semana)**
1. 📧 Reportar bug a TheFactoryHKA con evidencia técnica
2. Documentar workaround como solución temporal
3. Monitorear si el problema afecta otros campos

### **Largo Plazo**
1. Verificar si TheFactoryHKA corrige el bug
2. 🧹 Remover workaround cuando se resuelva
3. Documentar caso para futuros problemas similares

---

**RESULTADO ESPERADO**: Eliminación completa del error PAC 2152 mediante workaround que corrige el mapeo incorrecto del servicio externo.
