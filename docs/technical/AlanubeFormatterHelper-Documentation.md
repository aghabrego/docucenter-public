# AlanubeFormatterHelper - Documentación Completa

## Resumen

El `AlanubeFormatterHelper` es un helper completo que maneja el formateo de datos de DocuCenter para el servicio Alanube, soportando tanto **República Dominicana** como **Panamá** con mapeos específicos basados en la documentación oficial de cada país.

## Características Principales

### **Detección Automática de País**
- Analiza el endpoint de la conexión PAC
- Fallback por nombre de conexión  
- Por defecto República Dominicana para compatibilidad

### **Mapeos Completos por País**
- **República Dominicana**: Lógica migrada completa del sistema existente
- **Panamá**: Mapeo según documentación oficial DGI

### **Compatibilidad Total**
- Sin breaking changes
- Funciona con código existente
- Se activa solo cuando se pasa conexión PAC

## Uso

### Básico (automático)
```php
use App\Helpers\AlanubeFormatterHelper;

// República Dominicana por defecto
$resultado = AlanubeFormatterHelper::format($datos);

// Con conexión PAC (detección automática)
$resultado = AlanubeFormatterHelper::format($datos, $connection);
```

### Integración con FeHeader
```php
// En FeHeader.php
public function configureFormatterAlanube(array $datos, ?Pacconnection $connection = null): array
{
    if ($connection !== null) {
        return AlanubeFormatterHelper::format($datos, $connection);
    }
    // Lógica legacy...
}
```

## Estructura de Datos

### República Dominicana
```php
[
    'information' => [...],  // 15+ campos mapeados
    'receiver' => [...],     // 11+ campos mapeados  
    'items' => [...],        // Array completo de productos
    'totals' => [...],       // 7+ campos de totales
    'global' => [...],       // 4+ campos globales
    'logistic' => [...],     // Información logística
    'delivery' => [...]      // Datos de entrega
]
```

### Panamá  
```php
[
    'information' => [...],  // 15+ campos según DGI
    'receiver' => [...],     // 11+ campos según DGI
    'items' => [...],        // Array de productos
    'totals' => [...],       // 7+ campos de totales
    'commercial' => [...]    // Datos comerciales
]
```

## Detección de País

### Por Endpoint
```php
// Detecta Panamá
'https://api.panama.alanube.com'
'https://alanube.pa'

// Detecta República Dominicana  
'https://api.dominicana.alanube.com'
'https://alanube.do'
```

### Por Nombre
```php
// Detecta Panamá
$connection->name = 'Alanube Panama'
$connection->name = 'PAC Panama'

// Por defecto República Dominicana
$connection->name = 'Alanube'
```

## Constantes por País

### Tipos de Documento - Panamá
```php
const DOCUMENT_TYPES_PANAMA = [
    '01' => 'Factura de Operación Interna',
    '02' => 'Factura de Importación', 
    '03' => 'Factura de Exportación',
    '04' => 'Nota de Crédito',
    '05' => 'Nota de Débito',
    '06' => 'Nota de Crédito Genérica',
    '07' => 'Nota de Débito Genérica',
    '08' => 'Factura de Zona Franca',
    '09' => 'Factura de Reembolso de Gastos',
    '10' => 'Factura - Sociedad'
];
```

### Tipos de Documento - República Dominicana
```php
const DOCUMENT_TYPES_DOMINICANA = [
    '01' => 'Factura de Crédito Fiscal',
    '02' => 'Factura de Consumo',
    '03' => 'Nota de Débito',
    '04' => 'Nota de Crédito',
    '11' => 'Factura de Regímenes Especiales',
    '12' => 'Factura Gubernamental',
    '13' => 'Factura de Exportación',
    '14' => 'Factura para Pagos al Exterior',
    '15' => 'Factura de Operaciones Designadas'
];
```

## Validaciones

### Panamá
```php
$errores = AlanubeFormatterHelper::validateForPanama($datos);
// Valida: tipo documento, número documento, nombre receptor
```

### República Dominicana
```php
$errores = AlanubeFormatterHelper::validateForDominicana($datos);
// Valida: tipo documento, número documento
```

## Mapeos Específicos Implementados

### Información General
- Tipo de emisión (`iTpEmis`)
- Tipo de documento (`iDoc`) 
- Numeración (`dNroDF`)
- Punto de facturación (`dPtoFacDF`)
- Código de seguridad (`dSeg`)
- Fecha de emisión (`dFechaEm`)
- Naturaleza de operación (`iNatOp`)
- Tipo de operación (`iTipoOp`)
- Destino (`iDest`)

### Receptor
- Tipo de receptor (`iTipoRec`)
- Nombre (`dNombRec`)
- RUC/Cédula (`gRucRec`)
- Dirección (`dDirecRec`)
- Ubicación (`gUbiRec`)
- Contacto (teléfono, email)
- País (`cPaisRec`)
- Exportación (`gFExp`)

### Items
- Descripción (`dDescProd`)
- Código (`dCodProd`)
- Cantidad (`dCantCodInt`)
- Unidad (`cUnidad`)
- Precios (`gPrecios`)
- ITBMS (`gITBMSItem`)
- ISC (`gISCItem`)
- GTIN (`gCodItem`)
- Fechas fabricación/caducidad

### Totales
- Subtotal, descuentos, impuestos
- Formas de pago (`gFormaPago`)
- Pagos a plazo (`gPagPlazo`)
- Retenciones (`gRetenc`)
- Transporte y seguros

### Datos Específicos
- Vehículos nuevos (`gVehicNuevo`)
- Medicamentos (`gMedicina`)
- Información logística (`gInfoLog`)
- Lugar de entrega (`gLcEntr`)
- Pedidos comerciales (`gPedComGl`)

## Testing

### Ejecutar Pruebas
```bash
# Con Docker
docker-compose exec -T laravel.test php artisan tinker --execute="
use App\\Helpers\\AlanubeFormatterHelper;
// ... código de prueba
"
```

### Casos de Prueba
1. República Dominicana por defecto
2. Panamá con conexión específica  
3. Detección automática de país
4. Mapeo completo de todos los campos
5. Validaciones por país

## Estado Actual

### Completado
- [x] Helper completo implementado
- [x] Detección automática de país
- [x] Mapeos completos para ambos países
- [x] Integración con FeHeader.php
- [x] Integración con Create.php
- [x] Compatibilidad hacia atrás
- [x] Constantes por país
- [x] Validaciones específicas
- [x] Testing completo
- [x] Documentación

### Resultado Final
- **República Dominicana**: Mapeo completo migrado del sistema existente
- **Panamá**: Mapeo completo según documentación oficial DGI
- **Detección**: Automática basada en conexión PAC
- **Compatibilidad**: 100% con código existente
- **Production Ready**: Listo para uso en producción

## Archivos Modificados

1. `app/Helpers/AlanubeFormatterHelper.php` - Nuevo helper completo
2. `app/Models/FeHeader.php` - Integración con helper
3. `app/Http/Livewire/Admin/FE/Create.php` - Pasar conexión PAC

El sistema ahora maneja automáticamente las diferencias entre países basándose en la conexión PAC utilizada! 
