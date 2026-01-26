# AlanubeFormatterHelper - Documentación Completa

## Resumen

El `AlanubeFormatterHelper` es un helper completo que formatea datos de DocuCenter al formato requerido por Alanube para **República Dominicana** y **Panamá**. Implementa mapeos específicos basados en la documentación oficial de cada país.

## Estado de Implementación

**COMPLETO** - Ambos países totalmente implementados según documentación oficial:

- **República Dominicana**: Migrada toda la lógica existente del `FeHeader.php`
- **Panamá**: Implementado según documentación oficial DGI

## Características Principales

### **Detección Automática de País**

```php
// Por endpoint
https://api-test.alanube.do/v1 → República Dominicana (DO)
https://api-test.alanube.pa/v1 → Panamá (PA)

// Por nombre de conexión
"Alanube Panama" → Panamá (PA)
"Alanube DOM" → República Dominicana (DO)

// Fallback
Sin conexión → República Dominicana (DO) - compatibilidad
```

### **Estructura de Datos Completa**

Ambos países retornan estructura completa:
```json
{
  "information": { /* Datos generales del documento */ },
  "receiver": { /* Datos del receptor */ },
  "items": [ /* Array de productos/servicios */ ],
  "totals": { /* Totales e impuestos */ },
  "global": { /* Datos comerciales globales */ },
  "logistic": { /* Información logística */ },
  "delivery": { /* Datos de entrega */ }
}
```

###  **Panamá - Mapeo DGI Oficial**

- Información general con tipos de documento 01-10
- Receptor con RUC y ubicación por provincia/distrito/corregimiento
- Items con ITBMS/ISC según tasas panameñas
- Totales con métodos de pago locales
- Validaciones según normativa DGI

###  **República Dominicana - Migración Completa**

- Toda la lógica existente del `FeHeader.php` migrada
- Tipos de documento 01-15 (incluye regímenes especiales)
- Manejo completo de RUC dominicano
- Items con precios, descuentos, vehículos, medicinas
- Formas de pago y pagos a plazos
- Información logística y de entrega completa

## Uso

### Básico
```php
use App\Helpers\AlanubeFormatterHelper;

// Sin conexión (DO por defecto)
$result = AlanubeFormatterHelper::format($datos);

// Con conexión específica
$result = AlanubeFormatterHelper::format($datos, $connection);
```

### En FeHeader.php
```php
public function configureFormatterAlanube(array $datos, ?Pacconnection $connection = null): array
{
    if ($connection !== null) {
        return AlanubeFormatterHelper::format($datos, $connection);
    }
    // Lógica legacy...
}
```

### En Create.php (Livewire)
```php
$datos = $feModel->configureFormatterAlanube($request, $connection);
```

## Endpoints de Desarrollo

### Panamá
```
Desarrollo: https://api-test.alanube.pa/v1
Producción: https://api.alanube.pa/v1
```

### República Dominicana
```
Desarrollo: https://api-test.alanube.do/v1
Producción: https://api.alanube.do/v1
```

## Validaciones

### Panamá
```php
$errors = AlanubeFormatterHelper::validateForPanama($data);
// Valida: documento, número, receptor según DGI
```

### República Dominicana
```php
$errors = AlanubeFormatterHelper::validateForDominicana($data);
// Valida: documento, número según normativa local
```

## Constantes de Documentos

### Panamá (01-10)
```php
const DOCUMENT_TYPES_PANAMA = [
    '01' => 'Factura de Operación Interna',
    '02' => 'Factura de Importación',
    '03' => 'Factura de Exportación',
    '04' => 'Nota de Crédito',
    '05' => 'Nota de Débito',
    // ...hasta '10'
];
```

### República Dominicana (01-15)
```php
const DOCUMENT_TYPES_DOMINICANA = [
    '01' => 'Factura de Crédito Fiscal',
    '02' => 'Factura de Consumo',
    '03' => 'Nota de Débito',
    '04' => 'Nota de Crédito',
    // ...hasta '15'
];
```

## Compatibilidad

- **Sin breaking changes**: Código existente sigue funcionando
- **Backward compatible**: Fallback a lógica original
- **Forward compatible**: Fácil agregar nuevos países
- **Progressive enhancement**: Nueva funcionalidad se activa automáticamente

## Testing con Docker

```bash
# Levantar contenedores
docker-compose up -d

# Probar helper
docker-compose exec laravel.test php artisan tinker --execute="
use App\\Helpers\\AlanubeFormatterHelper;
use App\\Models\\Pacconnection;

// Test República Dominicana
\$connDO = new Pacconnection();
\$connDO->endpoint = 'https://api-test.alanube.do/v1';
\$country = AlanubeFormatterHelper::detectCountryFromConnection(\$connDO);
echo 'País: ' . \$country; // DO

// Test Panamá
\$connPA = new Pacconnection();
\$connPA->endpoint = 'https://api-test.alanube.pa/v1';
\$country = AlanubeFormatterHelper::detectCountryFromConnection(\$connPA);
echo 'País: ' . \$country; // PA
"
```

## Integración en Producción

1. **FeHeader.php**: Ya integrado con parámetro opcional
2. **Create.php**: Ya actualizado para pasar conexión
3. **AlanubeFormatterHelper.php**: Completo y operativo
4. **Autoload**: Regenerado con `composer dump-autoload`

## Próximos Pasos

- Implementación completa
- Testing básico funcionando
-  Testing con datos reales de producción
-  Validación con endpoints de desarrollo
-  Migración gradual de código legacy

---

**Estado**: **COMPLETADO** - Helper funcional con mapeo completo para ambos países según documentación oficial.
