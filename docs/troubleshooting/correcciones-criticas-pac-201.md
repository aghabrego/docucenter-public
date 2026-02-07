# Correcciones Críticas Error PAC 201 - TheFactoryHKA

## Resumen de Problema

**Error**: PAC 201 "Error al procesar solicitud" persistía después de corregir campos prohibidos para extranjeros.

**Análisis**: El problema no era solo los campos prohibidos, sino múltiples validaciones PAC que causaban rechazo.

## Problemas Identificados

### 1. Código de Item Vacío
- **Problema**: Items con `codigo: ""` (cadena vacía)
- **Impacto**: PAC requiere código válido para cada item
- **Solución**: Asignar código por defecto `ITEM001` cuando esté vacío

### 2. Descripción de Item Excesiva
- **Problema**: Descripciones > 100 caracteres (ej: 109 caracteres)
- **Impacto**: PAC rechaza descripciones que exceden límites
- **Solución**: Truncar automáticamente a 100 caracteres

### 3. Campos Numéricos Cero Innecesarios
- **Problema**: Envío de campos con valores `0.000000` que deberían omitirse
- **Impacto**: PAC interpreta como datos innecesarios que causan error
- **Solución**: Omitir completamente campos cero excepto los esenciales

### 4. Filtrado Incorrecto de Campos
- **Problema**: `filterNullValues()` incluía todos los valores cero
- **Impacto**: Estructura enviada contenía campos innecesarios
- **Solución**: Filtrado inteligente con lista blanca de campos cero permitidos

## Correcciones Implementadas

### HKAService.php - Validación de Items

```php
// CRÍTICO: Validar descripción del item (max 100 caracteres según PAC)
$descripcionCompleta = $this->getNestedValue($detailItem, 'dDescProd');
$item->descripcion = !empty($descripcionCompleta) ? 
    substr($descripcionCompleta, 0, 100) : 'PRODUCTO';

// CRÍTICO: Asegurar código del item no esté vacío (requerido por PAC)
$codigoItem = $this->getNestedValue($detailItem, 'dCodProd');
$item->codigo = !empty($codigoItem) ? $codigoItem : 'ITEM001';

// CRÍTICO: Solo incluir descuentos si > 0 para evitar campos innecesarios
$descuento = $this->normalizeNumericValue($this->getNestedValue($detailItem, 'gPrecios.dPrUnitDesc'));
if (floatval($descuento) > 0) {
    $item->precioUnitarioDescuento = $descuento;
}
```

### HKAService.php - Filtrado Inteligente

```php
private function filterNullValues($object)
{
    // CRÍTICO: Omitir campos con valores cero que pueden causar error PAC
    $isZeroValue = (
        $value === 0 || 
        $value === '0' || 
        $value === '0.00' || 
        $value === '0.000000' ||
        $value === 0.0
    );
    
    // Campos específicos que DEBEN incluirse aunque sean cero
    $keepZeroFields = [
        'tipoSucursal', 'tipoOperacion', 'formatoCAFE', 'entregaCAFE',
        'envioContenedor', 'procesoGeneracion', 'tipoVenta', 'tiempoPago',
        'nroItems', 'nroPedidoCompraGlobal', 'nroAceptacion'
    ];
    
    if ($isZeroValue && !in_array($key, $keepZeroFields)) {
        // Omitir campos cero que pueden causar problemas PAC
        continue;
    }
}
```

### HKAService.php - Totales Condicionales

```php
// CRÍTICO: Solo incluir totales si > 0 para evitar campos innecesarios
$totalITBMS = $this->normalizeNumericValueToTwoDecimals($this->getNestedValue($doc, 'gTot.dTotITBMS'));
if (floatval($totalITBMS) > 0) {
    $totales->totalITBMS = $totalITBMS;
}
```

##  Validación de Correcciones

Se creó script de testing integral: `docs/testing/test-correcciones-pac-201.php`

### Resultados de Testing:
- **Campos prohibidos extranjeros**: Verificados como null
- **Código item vacío**: Corregido a 'ITEM001'
- **Descripción larga**: Truncada de 109 a 100 caracteres
- **Campos cero items**: Omitidos correctamente
- **Campos cero totales**: Omitidos correctamente

## Archivos Modificados

### Principales:
- `app/Services/HKAService.php`: Correcciones en validación de items y filtrado
- `docs/testing/test-correcciones-pac-201.php`: Script de validación completo
- `docs/testing/debug-pac-201-estructura-exacta.php`: Análisis detallado del problema

### Testing:
- Validación de estructura antes/después de correcciones
- Verificación de omisión correcta de campos cero
- Comprobación de campos prohibidos para extranjeros

## Próximos Pasos

1. **Testing en Producción**: Probar factura de exportación con cliente extranjero
2. **Validación PAC**: Confirmar que error 201 se resuelve
3. **Monitoreo**: Supervisar otras transacciones para efectos secundarios

##  Referencias

- **Documentación PAC**: https://felwiki.thefactoryhka.com.pa/
- **Catálogo Errores**: `public/catalogo_de_codigos_de_retorno_del_servicio-08-2023.pdf`
- **Código 109**: "El campo [campo] no debe ser informado"
- **Código 201**: "Error al procesar solicitud"

## Commits Relacionados

- Commit anterior: Corrección campos prohibidos extranjeros
- Este commit: Correcciones adicionales para error 201 completo

---

*Fecha: 2025-10-24*  
*Autor: Sistema de debugging PAC TheFactoryHKA*
