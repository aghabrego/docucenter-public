# QuickBooks Foreign Customer Country Storage Fix

## Problema Identificado

En la creación de clientes extranjeros desde QuickBooks, el sistema no estaba extrayendo correctamente el país del objeto `CustomerRef`, resultando en que todos los clientes extranjeros se almacenaran con país 'PA' (Panamá) por defecto, en lugar del país real.

## Estructura de Datos QuickBooks

### Ejemplo de CustomerRef con Cliente Extranjero
```json
{
  "CustomerRef": {
    "value": "123",
    "name": "Juan Perez",
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "XYZABC123",
    "Country": "Chile",
    "BillAddr": {
      "Id": "123",
      "Line1": "Av. Providencia 1234",
      "Line2": "Depto 5B",
      "City": "Santiago",
      "Country": "Chile",
      "PostalCode": "7500000"
    }
  }
}
```

## Problema Original

En `app/Services/QuickBooksOnlineService.php` línea 715:

```php
// ANTES - País hardcodeado
'Country' => 'PA', // Default country, adjust as needed
```

## Solución Implementada

### 1. Extracción de País de CustomerRef

```php
// DESPUÉS - Extracción dinámica de país
'Country' => array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA')),
```

### 2. Extracción de PASAPORTE

```php
// Agregado - Extracción de PASAPORTE
'PASAPORTE' => array_get($customerRef, 'PASAPORTE'),
```

## Lógica de Extracción de País

El sistema ahora maneja **múltiples estructuras de datos** de QuickBooks:

### Opción 1: País en nivel superior de CustomerRef
```php
array_get($customerRef, 'Country') // "Chile"
```

### Opción 2: País dentro de BillAddr
```php
array_get($customerRef, 'BillAddr.Country') // "Chile"
```

### Opción 3: Default para casos sin país
```php
'PA' // Panamá como default
```

## Proceso Completo de Cliente Extranjero

### 1. Detección Automática
```php
// Detectar si es extranjero por PASAPORTE
if (!empty($pasaporteFromRequest)) {
    $tipoReceptor = '04'; // Extranjero
    $pasaporte = $pasaporteFromRequest;
}
```

### 2. Almacenamiento en CustomersImp
```php
[
    'Custom_field1' => $tipoReceptor === '04' ? $pasaporte : $ruc,    // PASAPORTE para extranjeros
    'Custom_field2' => $tipoReceptor === '04' ? null : $dv,           // DV solo para nacionales
    'Custom_field3' => $tipoReceptor,              // '04' para extranjeros
    'Custom_field4' => $tipoContribuyente,         // Tipo de contribuyente
    'Custom_field5' => $locationCode,              // Código de provincia/distrito
    'Country' => $paisExtraido,                    // País real del cliente
]
```

## Validación del Fix

### Comando de Testing
```bash
# Crear comando para testing de clientes extranjeros
php artisan make:command TestForeignCustomerCreation --command=test:foreign-customer

# Testing manual desde consola
php artisan test:foreign-customer --org-id=1 --country=Chile --passport=XYZABC123
```

### Casos de Prueba

1. **Cliente con Country en CustomerRef**
   - Input: `CustomerRef.Country = "Chile"`
   - Expected: `Country = "Chile"`

2. **Cliente con Country en BillAddr**
   - Input: `CustomerRef.BillAddr.Country = "Argentina"`
   - Expected: `Country = "Argentina"`

3. **Cliente sin Country especificado**
   - Input: Sin campo Country
   - Expected: `Country = "PA"` (default)

4. **Cliente con PASAPORTE**
   - Input: `TIPO_RECEPTOR = "04"`, `PASAPORTE = "XYZABC123"`
   - Expected: `Custom_field1 = "XYZABC123"`, `Custom_field3 = "04"`

## Impacto

### Beneficios
- **Precisión de datos**: País real del cliente extranjero
- **Compatibilidad**: Maneja múltiples estructuras de QuickBooks
- **Cumplimiento**: Datos correctos para facturación electrónica internacional
- **Trazabilidad**: Información completa para reportes fiscales

### Monitoreo
- Verificar que clientes extranjeros tengan el país correcto
- Validar que TIPO_RECEPTOR '04' funcione con PACs
- Confirmar que el país se use correctamente en facturación electrónica

## Archivos Modificados

- `app/Services/QuickBooksOnlineService.php`
  - Línea ~715: Extracción dinámica de país
  - Línea ~716: Extracción de PASAPORTE
  - Método: `createInvoiceFromQuickBooks()`

## Dependencias

- **Modelos**: `CustomersImp`
- **Helpers**: `array_get()` para acceso seguro a arrays anidados
- **PACs**: TheFactoryHKA y Alanube para facturación internacional
- **DGI Panama**: Cumplimiento con TIPO_RECEPTOR '04' para extranjeros

## Testing Adicional Recomendado

1. **Pruebas unitarias** para extracción de país
2. **Pruebas de integración** con datos reales de QuickBooks
3. **Validación PAC** con clientes extranjeros
4. **Pruebas de facturación electrónica** internacional

---

*Actualizado: $(date)*
*Responsable: DocuCenter Development Team*
