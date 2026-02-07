# Correcciones de Validación PAC

## Problema de Precisión Decimal en Pagos - TheFactoryHKA

### Problema Identificado
- **Fecha**: 2025-08-31
- **Proveedor PAC**: TheFactoryHKA
- **Error**: Código 109 - "El valor del campo totalValorRecibido no coincide con la suma de las ocurrencias del campo valorCuotaPagada de formaPago"

### Descripción Técnica
El sistema presentaba discrepancias de 1 centavo entre el total de la factura (`dVTot`) y la suma de pagos (`dTotRec`) debido a problemas de precisión en operaciones de punto flotante:

```json
// Valores problemáticos
{
  "dVTot": "20.33",
  "dTotRec": "20.32",  // 1 centavo menos
  "dVuelto": "0.01"    // Vuelto inválido
}
```

### Causa Raíz
- `PaymentCalculationHelper` no logra precisión perfecta en cálculos de punto flotante
- Diferencias mínimas (< 2 centavos) causaban rechazos del PAC
- TheFactoryHKA es muy estricto con la validación de totales exactos

### Solución Implementada

**Archivos Corregidos**:
- `app/Http/Livewire/Admin/Einvoice/CreateFastJob.php` 
- `app/Http/Livewire/Admin/Einvoice/CreateFast.php` 
- `app/Http/Livewire/Admin/Einvoice/Create.php` 

```php
// Corrección de precisión decimal para pagos
// SOLUCION PARA DIFERENCIAS MINIMAS: Si la diferencia es menor a 2 centavos, forzar igualdad
$dVueltoRaw = abs($dTotRec - $dVTot);
if ($dVueltoRaw < 0.02) {
    // Forzar que dTotRec sea exactamente igual a dVTot para evitar problemas de precisión
    $dTotRec = $dVTot;
    $dVueltoRaw = 0;

    // TAMBIÉN AJUSTAR LOS PAGOS para que sumen exactamente dVTot
    if (!empty($payments)) {
        $totalPayments = array_sum(array_column($payments, 'dVlrCuota'));
        if (abs($totalPayments - $dVTot) > 0.001) {
            // Ajustar el último pago para que la suma sea exacta
            $lastIndex = count($payments) - 1;
            $adjustment = $dVTot - ($totalPayments - $payments[$lastIndex]['dVlrCuota']);
            $payments[$lastIndex]['dVlrCuota'] = $this->numberFormat($adjustment, 2);
        }
    }
}

$dVuelto = $this->numberFormat($dVueltoRaw, 2);

// Si la diferencia es menor a 1 centavo, no enviar vuelto
// EXCEPTO para TheFactoryHKA que requiere el campo vuelto siempre
$shouldIncludeVuelto = $dVuelto >= 0.01 || ($this->pacName === 'TheFactoryHKA');

// Lógica simplificada para inclusión del vuelto
$gTotData = [/* datos básicos */];

// Para TheFactoryHKA: siempre incluir (ya manejado en shouldIncludeVuelto)
// Para otros PACs: solo incluir si hay vuelto real (>= 0.01)
if ($shouldIncludeVuelto) {
    $gTotData['dVuelto'] = $dVuelto; // Será "0.00" para TheFactoryHKA sin vuelto real
}
```

### Resultados
```json
// Valores corregidos
{
  "dVTot": 20.33,
  "dTotRec": 20.33,   // Perfectamente alineado
  "dVuelto": "0.00"   // Sin vuelto inválido
}
```

### Validación
- **Antes**: Error PAC código 109 (problema de vuelto)
- **Después**: Error PAC código 102 (documento duplicado) - confirma que el problema de pagos está resuelto
- **Debug Log**: Muestra valores perfectamente alineados en cada emisión

### Aplicabilidad
Esta corrección es específica para:
- Proveedor PAC: TheFactoryHKA (principalmente)
- Problemas de precisión decimal < 2 centavos
- Facturas con totales que incluyen decimales
- Sistema DocuCenter multi-tenant
- **Componentes Livewire**: CreateFastJob, CreateFast, Create (todos corregidos)

### Archivos Actualizados
1. **CreateFastJob.php** - Componente principal para emisión rápida con datos de Kart
2. **CreateFast.php** - Componente de facturación rápida estándar  
3. **Create.php** - Componente de facturación completa con interfaz de usuario

### Beneficios
1. **Eliminación de rechazos PAC** por problemas de precisión
2. **Emisión exitosa** de facturas electrónicas en todos los componentes
3. **Cumplimiento fiscal** para Panamá
4. **Robustez** en cálculos de punto flotante
5. **Consistencia** entre todos los componentes de facturación

### Notas Técnicas
- La corrección solo se activa para diferencias menores a 2 centavos
- Preserva la integridad de los cálculos para diferencias significativas
- Incluye logging detallado para auditoría y debugging
- Compatible con el trait CreateFastJob existente
