# Guía de Uso del Servicio AlanubeDomService

## Descripción General

El `AlanubeDomService` es el servicio principal para la emisión de documentos electrónicos a través del proveedor PAC Alanube DOM. Este servicio incluye **detección automática de tipos de documento** y manejo específico para cada tipo de factura según las normativas fiscales panameñas.

## Características Principales

### Detección Automática de Tipos
- **Análisis inteligente** de datos de entrada
- **Selección automática** del endpoint correcto
- **Validaciones específicas** por tipo de documento
- **Formateo automático** según especificaciones

### Tipos de Documentos Soportados
- **31** - Factura de Crédito Fiscal (FCF)
- **32** - Factura de Consumo
- **45** - Factura Gubernamental
- **46** - Factura de Exportación Electrónica
- **33** - Nota de Débito
- **34** - Nota de Crédito
- **41** - Compras
- **43** - Gastos Menores
- **44** - Regímenes Especiales
- **47** - Pagos al Exterior

## Uso Básico del Servicio

### 1. Importación y Configuración

```php
use App\Services\AlanubeDomService;
use App\Models\Organization;

// Crear instancia del servicio
$service = new AlanubeDomService();

// Obtener organización
$organization = Organization::find($organizationId);
```

### 2. Método Principal con Detección Automática

```php
/**
 * Emisión con detección automática de tipo
 */
$result = $service->emitInvoice($organization, $invoiceData);

if ($result['success']) {
    $encf = $result['encf'];              // e-NCF generado
    $stampDate = $result['stamp_date'];   // Fecha de timbre
    $documentType = $result['document_type']; // Tipo detectado
    $typeCode = $result['type_code'];     // Código numérico
    
    echo "Factura emitida: {$encf}";
    echo " Tipo: {$documentType} ({$typeCode})";
} else {
    echo "Error: {$result['message']}";
    echo "Detalle: {$result['error']}";
}
```

## Detección Automática de Tipos

### Factura de Exportación (46)

**Indicadores de Detección**:
- Campos de información adicional de exportación
- Datos de transporte internacional
- Comprador con identificador extranjero
- Indicador de facturación 3 (ITBIS 0%)

```php
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'BL-001-2025',        // Documento de embarque
        'destinationCountry' => 'US',               // País destino
        'transportMean' => '01',                    // Medio transporte (01=Marítimo)
        'departurePort' => 'Puerto de Balboa',      // Puerto salida
        'loadingPort' => 'Puerto de Colón',         // Puerto carga
        'dischargePort' => 'Puerto de Miami',       // Puerto descarga
        'exportCurrency' => 'USD',                  // Moneda exportación
        'exportValue' => 5000.00,                   // Valor exportación
        'customsInformation' => 'Productos electrónicos para exportación'
    ],
    'buyer' => [
        'foreignIdentifier' => 'US123456789',       // ID extranjero
        'companyName' => 'Import Solutions Inc.',
        'contactName' => 'John Smith',
        'email' => 'export@importsolutions.com',
        'phone' => '+1-555-0123',
        'address' => '123 Export Street',
        'country' => 'US',                          // Código país
        'city' => 'Miami',
        'postalCode' => '33101'
    ],
    'itemDetails' => [
        [
            'lineNumber' => 1,
            'billingIndicator' => 3,                 // ITBIS 0% (obligatorio)
            'itemName' => 'Producto exportación',
            'goodServiceIndicator' => 1,            // Bien
            'itemDescription' => 'Producto electrónico',
            'quantityItem' => 10,
            'unitMeasure' => 1,                     // Unidad
            'unitPriceItem' => 500.00,
            'itemAmount' => 5000.00
        ]
    ],
    'totals' => [
        'totalAmount' => 5000.00,
        'itbisTotal' => 0.00,                       // Sin ITBIS
        'i3AmountTaxed' => 5000.00                  // Todo en tasa 0%
    ]
];

// Se detecta automáticamente como EXPORT_SUPPORT (46)
$result = $service->emitInvoice($organization, $exportData);
```

### 🏛Factura Gubernamental (45)

**Indicadores de Detección**:
- RNC gubernamental (prefijos 10, 11)
- Campos específicos gubernamentales
- Información de transporte

```php
$governmentalData = [
    'customer' => [
        'tax_id' => '10123456789',                  // RNC gubernamental
        'first_name' => 'MINISTERIO DE SALUD PUBLICA',
        'email' => 'compras@mispas.gob.pa',
        'address' => 'Avenida Gorgas, Ancón'
    ],
    'income_type' => '01',                          // Tipo ingreso específico
    'transport' => [
        'meansOfTransport' => '01',                 // Medio transporte
        'originCountry' => 'PA',
        'destinationCountry' => 'PA'
    ],
    'itemDetails' => [
        [
            'billingIndicator' => 1,                // ITBIS 18%
            'itemName' => 'Suministros médicos',
            'quantityItem' => 100,
            'unitPriceItem' => 50.00,
            'itemAmount' => 5000.00
        ]
    ],
    'totals' => [
        'totalAmount' => 5900.00,
        'itbisTotal' => 900.00,
        'i1AmountTaxed' => 5000.00
    ]
];

// Se detecta automáticamente como GOVERNMENTAL (45)
$result = $service->emitInvoice($organization, $governmentalData);
```

### 🛒 Factura de Consumo (32)

**Indicadores de Detección**:
- Cliente "Consumidor Final"
- Monto menor a $250,000 DOP
- Sin RNC o RNC genérico

```php
$consumerData = [
    'customer' => [
        'id' => 'Consumidor Final',                 // Cliente genérico
        'first_name' => 'Cliente',
        'last_name' => 'General'
    ],
    'total' => 150.00,                              // Monto bajo
    'subtotal' => 127.12,
    'tax' => 22.88,
    'items' => [
        [
            'product_name' => 'Producto consumo',
            'qty' => 1,
            'price' => 127.12,
            'discount' => 0
        ]
    ],
    'payment_method' => 'cash'                      // Efectivo típico
];

// Se detecta automáticamente como INVOICE (32)
$result = $service->emitInvoice($organization, $consumerData);
```

### Factura Fiscal/Crédito Fiscal (31)

**Indicadores de Detección**:
- RNC empresarial válido
- Campos fiscales complejos
- Formas de pago estructuradas

```php
$fiscalData = [
    'customer' => [
        'tax_id' => '40123456789',                  // RNC empresarial
        'first_name' => 'EMPRESA ABC',
        'last_name' => 'S.A.',
        'email' => 'contabilidad@empresaabc.com',
        'address' => 'Calle 50, Ciudad de Panamá'
    ],
    'paymentFormsTable' => [                        // Formas pago complejas
        [
            'paymentMethod' => 4,                   // Crédito
            'paymentAmount' => 800.00,
            'paymentAccountNumber' => 'CRE-001'
        ],
        [
            'paymentMethod' => 2,                   // Transferencia
            'paymentAmount' => 200.00,
            'bankPayment' => 'Banco Nacional'
        ]
    ],
    'paymentDeadline' => '2025-09-21',              // Fecha límite pago
    'paymentTerm' => 30,                            // Plazo días
    'itemDetails' => [
        [
            'billingIndicator' => 1,                // ITBIS 18%
            'itemName' => 'Servicio consultoria',
            'goodServiceIndicator' => 2,            // Servicio
            'quantityItem' => 1,
            'unitPriceItem' => 1000.00,
            'itemAmount' => 1000.00,
            'retention' => [                        // Retenciones
                'retentionType' => 'ISR',
                'retentionAmount' => 100.00
            ]
        ]
    ]
];

// Se detecta automáticamente como FISCAL_INVOICE (31)
$result = $service->emitInvoice($organization, $fiscalData);
```

## Métodos Específicos por Tipo

### Llamada Directa por Tipo

Si conoces el tipo exacto, puedes llamar directamente:

```php
// Factura de Exportación
$result = $service->emitExportInvoice($organization, $exportData);

// Factura Gubernamental  
$result = $service->emitGovernmentalInvoice($organization, $governmentalData);

// Factura de Consumo
$result = $service->emitConsumerInvoice($organization, $consumerData);

// Factura Fiscal
$result = $service->emitFiscalInvoice($organization, $fiscalData);

// Método legacy para crédito fiscal
$result = $service->emitFiscalCreditInvoice($organization, $fiscalData);
```

### Transformación desde Kart21

```php
// Transformar datos de Kart21 a formato Alanube
$alanubeData = $service->transformKart21ToAlanubeFiscal(
    $organization, 
    $kart21Data, 
    AlanubeDomService::FISCAL_INVOICE
);

// Emitir factura transformada
$result = $service->emitInvoice($organization, $alanubeData);
```

## Lógica de Detección Automática

### Orden de Prioridad

1. **Campo explícito**: `document_type` si está presente
2. **RNC gubernamental**: Prefijos 10, 11 → Gubernamental (45)
3. **Campos exportación**: Transport, foreign buyer → Exportación (46)
4. **Campos fiscales**: paymentFormsTable, retention → Fiscal (31)
5. **Cliente y monto**: Consumidor final + <250K → Consumo (32)
6. **RNC empresarial**: Prefijos 4,3,1,etc. → Fiscal (31)
7. **Fallback**: Factura de consumo por defecto

### Función `determineDocumentType()`

```php
public function determineDocumentType(array $request): int
{
    // 1. Tipo explícito
    if (isset($request['document_type'])) {
        return intval($request['document_type']);
    }

    $total = floatval(array_get($request, 'total', 0));
    $customer = array_get($request, 'customer', []);
    $customerRnc = array_get($customer, 'tax_id', '');

    // 2. RNC gubernamental
    if ($this->isGovernmentalRnc($customerRnc)) {
        return self::GOVERNMENTAL; // 45
    }

    // 3. Campos gubernamentales
    if (isset($request['income_type']) || isset($request['transport'])) {
        return self::GOVERNMENTAL; // 45
    }

    // 4. Campos fiscales complejos
    if ($this->isFiscalCreditIndicator($request)) {
        return self::FISCAL_INVOICE; // 31
    }

    // 5. Campos de exportación
    if ($this->isExportInvoiceIndicator($request)) {
        return self::EXPORT_SUPPORT; // 46
    }

    // 6. Consumidor final con monto bajo
    if (empty($customer) || $customer['id'] === 'Consumidor Final') {
        return $total > 250000 ? self::FISCAL_INVOICE : self::INVOICE;
    }

    // 7. RNC empresarial válido
    if ($this->hasValidBusinessRnc($customerRnc)) {
        return self::FISCAL_INVOICE; // 31
    }

    // 8. Fallback
    return self::INVOICE; // 32
}
```

## Estructura de Respuesta

### Respuesta Exitosa

```php
[
    'success' => true,
    'data' => [
        'encf' => 'E31000000001234567890',          // e-NCF generado
        'stamp_date' => '2025-08-22T10:30:00',     // Fecha timbre
        'status' => 'approved',
        // ... otros campos del PAC
    ],
    'encf' => 'E31000000001234567890',
    'stamp_date' => '2025-08-22T10:30:00',
    'document_type' => 'Factura de Exportación Electrónica',
    'type_code' => 46,
    'validation_details' => [                       // Solo para exportación
        'billing_indicator' => 'Todos los ítems con ITBIS tasa 0%',
        'export_specific' => 'Documento configurado para exportación'
    ]
]
```

### Respuesta de Error

```php
[
    'success' => false,
    'message' => 'Error al emitir factura de exportación',
    'error' => 'Detalles específicos del error',
    'document_type' => 'Factura de Exportación Electrónica',
    'type_code' => 46,
    'document_type_detected' => 'export'            // Tipo detectado
]
```

## Configuración de Endpoints

### Mapeo Interno de Endpoints

```php
protected $endpoints = [
    31 => 'fiscal-invoices',        // Factura Fiscal
    32 => 'invoices',              // Factura Consumo  
    33 => 'debitnotes',            // Nota Débito
    34 => 'creditnotes',           // Nota Crédito
    41 => 'purchases',             // Compras
    43 => 'minorexpenses',         // Gastos Menores
    44 => 'specialregimes',        // Regímenes Especiales
    45 => 'gubernamentals',        // Factura Gubernamental
    46 => 'export-supports',       // Factura Exportación
    47 => 'paymentabroadsupports'  // Pagos Exterior
];
```

### URLs Completas

```php
// Base URL: https://api.alanube.com/dom/v1/
// Ejemplo para exportación:
// https://api.alanube.com/dom/v1/export-supports
```

## Manejo de Errores

### Errores Comunes

```php
try {
    $result = $service->emitInvoice($organization, $invoiceData);
} catch (\Exception $e) {
    switch ($e->getMessage()) {
        case 'No se encontró configuración PAC':
            // Configurar conexión PAC para la organización
            break;
            
        case 'La configuración PAC no es para Alanube':
            // Verificar pac_type = 'alanube'
            break;
            
        case 'Tipo de documento no soportado':
            // Verificar que el tipo esté en $endpoints
            break;
            
        default:
            // Error de red o PAC
            Log::error('Error AlanubeDomService: ' . $e->getMessage());
    }
}
```

### Logging Automático

El servicio incluye logging automático:

```php
// Logs de información
Log::info('[AlanubeDomService] Tipo de documento detectado: export');
Log::info('[AlanubeDomService] Factura emitida exitosamente', ['encf' => $encf]);

// Logs de error
Log::error('[AlanubeDomService] Error emitiendo factura: ' . $error);
Log::warning('[AlanubeDomService] Error en respuesta', ['result' => $result]);
```

## Ejemplos de Implementación

### Controlador Laravel

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AlanubeDomService;
use App\Models\Organization;

class InvoiceController extends Controller
{
    protected $alanubeDomService;

    public function __construct(AlanubeDomService $alanubeDomService)
    {
        $this->alanubeDomService = $alanubeDomService;
    }

    /**
     * Emitir factura con detección automática
     */
    public function emit(Request $request)
    {
        try {
            $organization = Organization::find($request->organization_id);
            
            if (!$organization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organización no encontrada'
                ], 404);
            }

            // El servicio detecta automáticamente el tipo
            $result = $this->alanubeDomService->emitInvoice(
                $organization, 
                $request->all()
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'encf' => $result['encf'],
                        'document_type' => $result['document_type'],
                        'type_code' => $result['type_code'],
                        'stamp_date' => $result['stamp_date']
                    ],
                    'message' => 'Factura emitida exitosamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'],
                'detected_type' => $result['document_type_detected'] ?? null
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error en emisión de factura', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Emitir factura de exportación específica
     */
    public function emitExport(Request $request)
    {
        try {
            $organization = Organization::find($request->organization_id);
            
            // Llamada directa para factura de exportación
            $result = $this->alanubeDomService->emitExportInvoice(
                $organization,
                $request->all()
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error emitiendo factura de exportación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Job Asíncrono

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AlanubeDomService;
use App\Models\Organization;

class EmitInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $organizationId;
    protected $invoiceData;

    public function __construct(int $organizationId, array $invoiceData)
    {
        $this->organizationId = $organizationId;
        $this->invoiceData = $invoiceData;
    }

    public function handle(AlanubeDomService $service)
    {
        $organization = Organization::find($this->organizationId);
        
        if (!$organization) {
            throw new \Exception('Organización no encontrada');
        }

        // Emisión con detección automática
        $result = $service->emitInvoice($organization, $this->invoiceData);

        if (!$result['success']) {
            throw new \Exception('Error emitiendo factura: ' . $result['message']);
        }

        // Procesar resultado exitoso
        $this->processSuccessfulEmission($result);
    }

    protected function processSuccessfulEmission(array $result)
    {
        // Guardar en base de datos, enviar notificaciones, etc.
        Log::info('Factura emitida en job asíncrono', [
            'encf' => $result['encf'],
            'type' => $result['document_type'],
            'organization_id' => $this->organizationId
        ]);
    }
}
```

## Precisión Decimal

### Configuración Interna

```php
protected $decimalPrecision = [
    'items' => 2,      // Líneas de ítems con 2 decimales
    'totals' => 2,     // Totales con 2 decimales  
    'payments' => 2,   // Pagos con 2 decimales
    'taxes' => 2,      // Impuestos con 2 decimales
];
```

Todos los valores monetarios se formatean automáticamente con 2 decimales según los requerimientos de Alanube DOM.

## Mejores Prácticas

### 1. Detección Automática vs Manual

```php
// Recomendado: Detección automática
$result = $service->emitInvoice($organization, $data);

// Solo si conoces el tipo exacto
$result = $service->emitExportInvoice($organization, $data);
```

### 2. Manejo de Respuestas

```php
// Verificar success antes de usar datos
if ($result['success']) {
    $encf = $result['encf'];
    // Procesar datos...
} else {
    // Manejar error...
    Log::error('Error emisión', $result);
}
```

### 3. Logging y Monitoreo

```php
// El servicio incluye logging automático, pero puedes agregar más
Log::info('Iniciando emisión factura', [
    'organization_id' => $organization->id,
    'data_keys' => array_keys($invoiceData)
]);

$result = $service->emitInvoice($organization, $invoiceData);

Log::info('Resultado emisión', [
    'success' => $result['success'],
    'type_detected' => $result['type_code'] ?? null
]);
```

### 4. Validación de Configuración PAC

```php
// Verificar configuración antes de emitir
$pacConnection = $organization->pacConnection;

if (!$pacConnection || $pacConnection->pac_type !== 'alanube') {
    throw new \Exception('Configuración PAC Alanube requerida');
}

if (empty($pacConnection->token)) {
    throw new \Exception('Token PAC no configurado');
}
```

## Conclusiones

El `AlanubeDomService` proporciona:

- **Detección automática** de tipos de documento
- **Validaciones específicas** por tipo
- **Endpoints correctos** automáticamente
- **Logging detallado** para debugging
- **Manejo robusto** de errores
- **Formateo automático** de datos
- **Soporte completo** para todos los tipos

**Uso recomendado**: Utilizar siempre `emitInvoice()` con detección automática para máxima flexibilidad y menor complejidad en el código cliente.

---

**Fecha de creación**: 2025-08-22  
**Versión**: 1.0.0  
**Autor**: DocuCenter Development Team  
**Última actualización**: 2025-08-22
