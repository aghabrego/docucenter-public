# Testing Scripts - Alanube PAC Integration

Este directorio contiene scripts de testing específicos para las correcciones y mejoras implementadas en la integración con el PAC Alanube.

## Scripts Incluidos

### 1. test-exportation-fix.sh
**Propósito**: Verifica la corrección de la estructura exportation
- **Problema resuelto**: "instance.exportation should not be specified" para documentType=01
- **Corrección**: Solo incluir exportation para documentType=02/03
- **Archivos modificados**: `AlanubeFormatterHelper.php`

### 2. test-country-destination-fix.sh  
**Propósito**: Verifica la corrección de inconsistencia país-destino en QuickBooksOnlineService
- **Problema resuelto**: "instance.receiver.country and instance.information.destination do not match"
- **Corrección**: Forzar country='PA' para documentType=01 (operaciones internas)
- **Archivos modificados**: `QuickBooksOnlineService.php`

### 3. test-customer-name-improvement.sh
**Propósito**: Verifica la mejora en selección del nombre del cliente
- **Mejora implementada**: Priorizar CompanyName sobre name cuando esté disponible
- **Beneficio**: Mejor identificación B2B vs B2C
- **Archivos modificados**: `QuickBooksOnlineService.php`

### 4. test-alanube-service-country-fix.sh
**Propósito**: Verifica la corrección de país en AlanubeService
- **Problema resuelto**: Segundo nivel de protección para país-destino
- **Corrección**: buildReceiver() ahora recibe documentType y corrige país
- **Archivos modificados**: `AlanubeService.php`

### 5. test-alanube-formatter-country-fix.sh
**Propósito**: Verifica la corrección de país en AlanubeFormatterHelper
- **Problema resuelto**: Tercer nivel de protección para requests directos
- **Corrección**: formatForPanama/formatForDominicana corrigen país según documentType
- **Archivos modificados**: `AlanubeFormatterHelper.php`

### 6. test-othercountry-type-fix.sh
**Propósito**: Verifica la corrección del tipo de dato otherCountry
- **Problema resuelto**: "instance.receiver.otherCountry is not of a type(s) string"
- **Corrección**: Validar tipo string y omitir campo si null/vacío
- **Archivos modificados**: `AlanubeFormatterHelper.php`

## Uso de los Scripts

```bash
# Ejecutar todos los tests
cd /home/weirdolabs/code/docucenter
for script in docs/testing/alanube/*.sh; do
    echo "=== Ejecutando $script ==="
    chmod +x "$script"
    "$script"
    echo
done

# Ejecutar test específico
./docs/testing/alanube/test-exportation-fix.sh
```

## Resumen de Problemas Resueltos

1. ✅ **Estructura exportation incorrecta**: Solo para import/export
2. ✅ **Inconsistencia país-destino**: Triple nivel de protección
3. ✅ **Nombre del cliente**: Mejor selección B2B/B2C
4. ✅ **Tipo de dato otherCountry**: Validación y omisión correcta

## Archivos Principales Modificados

- `app/Helpers/AlanubeFormatterHelper.php`: Formateo y validaciones
- `app/Services/AlanubeService.php`: Construcción de datos para PAC
- `app/Services/QuickBooksOnlineService.php`: Procesamiento QuickBooks

Todos los cambios incluyen logging detallado para debugging y seguimiento.
