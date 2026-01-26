# Guía de Uso del Servicio AlanubeService (Panamá)

## Descripción General

El `AlanubeService` es el servicio principal para la emisión de documentos electrónicos a través del proveedor PAC Alanube Panamá. Este servicio incluye **detección automática de tipos de documento** y manejo específico para cada tipo de factura según las normativas fiscales panameñas de la DGI.

### Características Principales
- **Detección automática** de tipo de documento
- **6 tipos de factura** soportados
- **Validaciones DGI** completas
- **Procesamiento asíncrono** opcional
- **Transformación automática** de datos
- **Helper para módulos externos**

## Uso Básico del Servicio

### 1. Importación y Configuración

```php
use App\Services\AlanubeService;
use App\Models\Organization;

// Crear instancia del servicio
$service = new AlanubeService();

// Obtener organización
$organization = Organization::find($organizationId);
```

### 2. Método Principal con Detección Automática

```php
// El servicio detecta automáticamente el tipo y emite la factura correspondiente
$result = $service->emitInvoice($organization, $invoiceData);

if ($result['success']) {
    echo "Factura emitida: " . $result['data']['id'];
    echo "Tipo: " . $result['document_type'];
    echo "Código: " . $result['type_code'];
} else {
    echo "Error: " . $result['message'];
}
```

## Detección Automática de Tipos

### Factura de Exportación (03)

**Indicadores de Detección**:
- Datos de exportación presentes
- Receptor extranjero
- País destino diferente a 'PA'
- Destino = '2' (Extranjero)

```php
$exportData = [
    'information' => [
        'destination' => '2',           // Extranjero
        'nature' => '02'                // Exportación
    ],
    'receiver' => [
        'type' => '04',                 // Extranjero
        'country' => 'US',              // Estados Unidos
        'foreign' => [
            'number' => 'US123456789',  // ID extranjero
            'country' => 'Estados Unidos'
        ]
    ],
    'exportation' => [
        'incoterm' => 'FOB',
        'currency' => 'USD',
        'exchangeRate' => 1.0,
        'amount' => 1000.00,
        'port' => 'Puerto de Balboa'
    ],
    'items' => [
        [
            'description' => 'Producto de Exportación',
            'quantity' => 10,
            'prices' => ['transfer' => 100.00],
            'itbms' => ['rate' => '00']  // 0% para exportación
        ]
    ]
];

$result = $service->emitInvoice($organization, $exportData);
```

### 🏭 Factura de Importación (02)

**Indicadores de Detección**:
- Naturaleza de operación = '21' (NATURE_IMPORT)
- Documentación de importación

```php
$importData = [
    'information' => [
        'nature' => '21',               // Importación
        'operationType' => 2            // Entrada o compra
    ],
    'receiver' => [
        'type' => '01',                 // Contribuyente
        'ruc' => [
            'type' => 2,                // Jurídico
            'ruc' => '123456789-1-123456'
        ],
        'name' => 'Empresa Importadora S.A.'
    ],
    'items' => [
        [
            'description' => 'Producto Importado',
            'quantity' => 5,
            'prices' => ['transfer' => 200.00],
            'itbms' => ['rate' => '01']  // 7%
        ]
    ]
];

$result = $service->emitInvoice($organization, $importData);
```

### Factura de Operación Interna (01)

**Indicadores de Detección**:
- Tipo por defecto para ventas locales
- No cumple criterios de otros tipos

```php
$internalData = [
    'information' => [
        'nature' => '01',               // Venta
        'operationType' => 1,           // Salida o venta
        'destination' => '1'            // Panamá
    ],
    'receiver' => [
        'type' => '01',                 // Contribuyente
        'country' => 'PA',
        'ruc' => [
            'type' => 1,                // Natural
            'ruc' => '8-123-456'
        ],
        'name' => 'Juan Pérez'
    ],
    'items' => [
        [
            'description' => 'Producto Local',
            'quantity' => 2,
            'prices' => ['transfer' => 150.00],
            'itbms' => ['rate' => '01']  // 7%
        ]
    ]
];

$result = $service->emitInvoice($organization, $internalData);
```

### 🏭 Factura de Zona Franca (08)

**Indicadores de Detección**:
- Información de zona franca presente
- Operación en zona franca

```php
$freeZoneData = [
    'information' => [
        'freeZone' => true,
        'nature' => '01'                // Venta
    ],
    'receiver' => [
        'type' => '01',
        'name' => 'Empresa Zona Franca S.A.'
    ],
    'items' => [
        [
            'description' => 'Producto Zona Franca',
            'quantity' => 3,
            'prices' => ['transfer' => 120.00],
            'itbms' => ['rate' => '00']  // 0% zona franca
        ]
    ]
];

$result = $service->emitInvoice($organization, $freeZoneData);
```

## Métodos Específicos por Tipo

### Llamada Directa por Tipo

```php
// Factura de Exportación
$result = $service->emitExportInvoice($organization, $exportData);

// Factura de Importación
$result = $service->emitImportInvoice($organization, $importData);

// Factura de Zona Franca
$result = $service->emitFreeZoneInvoice($organization, $freeZoneData);
```

### Usando Helper para Módulos Externos

```php
use App\Utils\AlanubeEmissionHelper;

// Emisión automática con detección de tipo
$result = AlanubeEmissionHelper::emitDocument($organization, $saleData);

// Emisión asíncrona
$result = AlanubeEmissionHelper::emitDocument($organization, $saleData, $userId, null, true);

// Emisión de tipo específico
$result = AlanubeEmissionHelper::emitExportInvoice($organization, $saleData);
```

### Transformación desde Formato DocuCenter

```php
// Transformar datos de DocuCenter a formato Alanube
$docuCenterData = [
    'customer' => [
        'name' => 'Cliente Ejemplo',
        'tax_id' => '123456789-1-123456',
        'email' => 'cliente@ejemplo.com',
        'phone' => '+507-123-4567'
    ],
    'items' => [
        [
            'product_name' => 'Producto Ejemplo',
            'quantity' => 2,
            'price' => 100.00,
            'tax_rate' => 7
        ]
    ],
    'payments' => [
        [
            'payment_method' => 'cash',
            'pay_amount' => 214.00
        ]
    ],
    'total' => 214.00
];

$alanubeData = AlanubeEmissionHelper::transformDocuCenterToAlanube($docuCenterData);
$result = $service->emitInvoice($organization, $alanubeData);
```

## Estructura de Datos de Entrada

### Información General (information)

```php
'information' => [
    'issueType' => '01',                    // Tipo de emisión (01-04)
    'numeration' => '0000000001',           // Número de 10 dígitos
    'billingPoint' => '001',                // Punto de facturación (3 dígitos)
    'securityCode' => '123456789',          // Código de seguridad (9 dígitos)
    'cafe' => [
        'format' => 1,                      // Formato CAFE
        'delivery' => 1,                    // Entrega CAFE
        'information' => 'Info adicional'   // Opcional
    ],
    'nature' => '01',                       // Naturaleza operación
    'operationType' => 1,                   // Tipo operación (1-2)
    'destination' => '1',                   // Destino (1=Panamá, 2=Extranjero)
    'receiverContainer' => 1,               // Envío contenedor
    'issueDate' => '2025-01-22T10:30:00Z'  // Fecha emisión ISO
]
```

### Receptor (receiver)

```php
'receiver' => [
    'type' => '01',                         // Tipo receptor (01-04)
    'name' => 'Nombre del Receptor',
    'address' => 'Dirección completa',
    'telephones' => ['+507-123-4567'],
    'emails' => ['correo@receptor.com'],
    'country' => 'PA',                      // Código país (PA para Panamá)
    
    // Para contribuyentes panameños
    'ruc' => [
        'type' => 1,                        // 1=Natural, 2=Jurídico
        'ruc' => '8-123-456'               // RUC panameño
    ],
    
    // Para ubicación
    'location' => [
        'code' => '08010001'               // Código de 8 dígitos
    ],
    
    // Para extranjeros
    'foreign' => [
        'number' => 'US123456789',         // ID extranjero
        'country' => 'Estados Unidos'      // Nombre del país
    ]
]
```

### Ítems (items)

```php
'items' => [
    [
        'description' => 'Descripción del producto/servicio',
        'code' => 'PROD001',               // Código interno opcional
        'unit' => 'UND',                   // Unidad de medida
        'quantity' => 2,                   // Cantidad
        'prices' => [
            'transfer' => 100.00,          // Precio unitario
            'discount' => 5.00,            // Descuento opcional
            'transport' => 0.00,           // Transporte opcional
            'insurance' => 0.00            // Seguro opcional
        ],
        'itbms' => [
            'rate' => '01'                 // Tasa ITBMS (00,01,02,03)
        ],
        
        // Campos opcionales
        'cpbs' => [                        // Codificación Panameña
            'code' => 12345,
            'unit' => 'Unidad CPBS'
        ],
        'gtin' => [                        // Códigos GTIN
            'commercializationCode' => 1234567890123,
            'commercializationQuantity' => 2,
            'inventoryCode' => 1234567890124,
            'inventoryQuantity' => 2
        ]
    ]
]
```

### Totales (totals)

```php
'totals' => [
    'change' => 0.00,                      // Vuelto
    'transport' => 0.00,                   // Transporte total
    'insurance' => 0.00,                   // Seguro total
    'otherExpenses' => 0.00,               // Otros gastos
    'paymentTime' => 1,                    // Tiempo pago (1=Contado, 2=Crédito)
    
    // Descuentos adicionales opcionales
    'discounts' => [
        [
            'description' => 'Descuento especial',
            'amount' => 10.00
        ]
    ]
]
```

### Métodos de Pago (paymentMethods)

```php
'paymentMethods' => [
    [
        'type' => '02',                    // Tipo pago (01-99)
        'amount' => 214.00,               // Monto
        'otherType' => 'Descripción'      // Si type='99'
    ]
]
```

## Configuración de Endpoints

### Mapeo Interno de Endpoints

```php
protected $endpoints = [
    '01' => 'invoices',    // Operación interna
    '02' => 'invoices',    // Importación
    '03' => 'invoices',    // Exportación
    '08' => 'invoices',    // Zona Franca
    '09' => 'invoices',    // Reembolso
    '10' => 'invoices',    // Operación extranjera
];
```

### URLs Completas

```php
// Base URL: https://api.alanube.co/pan/v1/
// Ejemplo: https://api.alanube.co/pan/v1/invoices
```

## Estructura de Respuesta

### Respuesta Exitosa

```php
[
    'success' => true,
    'message' => 'Factura emitida exitosamente',
    'data' => [
        'id' => 'ALN-PAN-123456',
        'status' => 'approved',
        'pdf_url' => 'https://api.alanube.co/documents/123456.pdf',
        // ... otros campos de respuesta de Alanube
    ],
    'document_type' => 'Factura de Exportación',
    'type_code' => '03',
    'url_used' => 'https://api.alanube.co/pan/v1/invoices'
]
```

### Respuesta de Error

```php
[
    'success' => false,
    'message' => 'Error al emitir factura electrónica',
    'error' => 'Detalles específicos del error',
    'error_details' => [
        // Detalles completos del error de la API
    ],
    'status_code' => 400,
    'document_type' => 'Factura de Operación Interna',
    'type_code' => '01'
]
```

## Manejo de Errores

### Errores Comunes

```php
try {
    $result = $service->emitInvoice($organization, $invoiceData);
} catch (\Exception $e) {
    switch ($e->getMessage()) {
        case 'No se encontró configuración PAC para la organización':
            // Configurar conexión PAC para la organización
            break;
            
        case 'La configuración PAC no es para Alanube Panamá':
            // Verificar pac_type = 'alanube_panama'
            break;
            
        case 'Token PAC no configurado':
            // Configurar token en pacconnection
            break;
            
        default:
            // Error de red o PAC
            Log::error('Error AlanubeService: ' . $e->getMessage());
    }
}
```

### Logging Automático

El servicio incluye logging automático:

```php
// Logs de información
Log::info('[AlanubeService] Tipo de documento detectado: export');
Log::info('[AlanubeService] Factura emitida exitosamente', ['data' => $data]);

// Logs de error
Log::error('[AlanubeService] Error emitiendo factura: ' . $error);
Log::warning('[AlanubeService] Error en respuesta', ['result' => $result]);
```

## Ejemplos de Implementación

### Controlador Laravel

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AlanubeService;
use App\Models\Organization;

class InvoiceController extends Controller
{
    protected $alanubeService;

    public function __construct(AlanubeService $alanubeService)
    {
        $this->alanubeService = $alanubeService;
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
            $result = $this->alanubeService->emitInvoice(
                $organization, 
                $request->all()
            );

            return response()->json($result);

        } catch (\Exception $e) {
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
            $result = $this->alanubeService->emitExportInvoice(
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

use App\Services\AlanubeService;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $organizationId;
    protected $invoiceData;

    public function __construct($organizationId, $invoiceData)
    {
        $this->organizationId = $organizationId;
        $this->invoiceData = $invoiceData;
    }

    public function handle()
    {
        $organization = Organization::find($this->organizationId);
        $alanubeService = new AlanubeService();
        
        $result = $alanubeService->emitInvoice($organization, $this->invoiceData);
        
        if ($result['success']) {
            // Procesar resultado exitoso
            // Enviar notificaciones, actualizar BD, etc.
        } else {
            // Manejar error
            // Logging, notificaciones de error, etc.
        }
    }
}
```

## Mejores Prácticas

### 1. Detección Automática vs Manual

```php
// Recomendado: Usar detección automática
$result = $service->emitInvoice($organization, $invoiceData);

// Solo usar métodos específicos cuando sea necesario forzar el tipo
$result = $service->emitExportInvoice($organization, $exportData);
```

### 2. Manejo de Respuestas

```php
$result = $service->emitInvoice($organization, $invoiceData);

if ($result['success']) {
    // Procesar éxito
    $documentId = $result['data']['id'];
    $documentType = $result['document_type'];
    
    // Guardar en BD, enviar notificaciones, etc.
} else {
    // Manejar error
    $errorMessage = $result['message'];
    $errorDetails = $result['error_details'] ?? [];
    
    // Logging, notificaciones de error, retry si aplica
}
```

### 3. Logging y Monitoreo

```php
// Logging antes de emisión
Log::info('Iniciando emisión Alanube Panamá', [
    'organization_id' => $organization->id,
    'data_summary' => $this->getSummary($invoiceData)
]);

// Logging después de emisión
Log::info('Resultado emisión Alanube Panamá', [
    'organization_id' => $organization->id,
    'success' => $result['success'],
    'document_type' => $result['document_type'] ?? 'N/A'
]);
```

### 4. Validación de Configuración PAC

```php
// Verificar configuración antes de emitir
$pacConnection = $organization->pacConnection;

if (!$pacConnection || $pacConnection->pac_type !== 'alanube_panama') {
    throw new \Exception('Configuración PAC Alanube Panamá requerida');
}

if (empty($pacConnection->token)) {
    throw new \Exception('Token PAC no configurado');
}
```

### 5. Procesamiento Asíncrono para Volumen

```php
// Para múltiples facturas o facturas complejas
use App\Jobs\CreateInvoiceAlanubeJob;

CreateInvoiceAlanubeJob::dispatch($invoiceData, $organizationId, $userId)
    ->onQueue('pac_integrations')
    ->delay(now()->addSeconds(5));
```

## Constantes Disponibles

### Tipos de Documento
```php
AlanubeService::INTERNAL_OPERATION = '01';
AlanubeService::IMPORT = '02';
AlanubeService::EXPORT = '03';
AlanubeService::FREE_ZONE = '08';
AlanubeService::REIMBURSEMENT = '09';
AlanubeService::FOREIGN_OPERATION = '10';
```

### Tipos de Emisión
```php
AlanubeService::ISSUE_TYPE_PRIOR_AUTH_NORMAL = '01';
AlanubeService::ISSUE_TYPE_PRIOR_AUTH_CONTINGENCY = '02';
AlanubeService::ISSUE_TYPE_POSTERIOR_AUTH_NORMAL = '03';
AlanubeService::ISSUE_TYPE_POSTERIOR_AUTH_CONTINGENCY = '04';
```

### Naturaleza de Operación
```php
AlanubeService::NATURE_SALE = '01';
AlanubeService::NATURE_EXPORT = '02';
AlanubeService::NATURE_IMPORT = '21';
// ... y más
```

### Tipos de Receptor
```php
AlanubeService::RECEIVER_CONTRIBUTOR = '01';
AlanubeService::RECEIVER_FINAL_CONSUMER = '02';
AlanubeService::RECEIVER_GOVERNMENT = '03';
AlanubeService::RECEIVER_FOREIGN = '04';
```

### Tasas ITBMS
```php
AlanubeService::ITBMS_0_PERCENT = '00';
AlanubeService::ITBMS_7_PERCENT = '01';
AlanubeService::ITBMS_10_PERCENT = '02';
AlanubeService::ITBMS_15_PERCENT = '03';
```

### Métodos de Pago
```php
AlanubeService::PAYMENT_CASH = '02';
AlanubeService::PAYMENT_CREDIT_CARD = '03';
AlanubeService::PAYMENT_BANK_TRANSFER = '08';
// ... y más
```

## Testing y Debugging

### Comando de Testing
```bash
# Test básico
php artisan test:alanube 1 --test-data

# Test tipo específico  
php artisan test:alanube 1 --test-data --type=03

# Test asíncrono
php artisan test:alanube 1 --test-data --async
```

### Script de Testing Completo
```bash
# Suite completa
./scripts/test-alanube-panama.sh complete 1

# Modo interactivo
./scripts/test-alanube-panama.sh interactive
```

Esta guía proporciona toda la información necesaria para implementar y usar el servicio AlanubeService para emisión de facturas electrónicas en Panamá a través de DocuCenter.
