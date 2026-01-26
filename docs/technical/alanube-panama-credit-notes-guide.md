# Guía de Notas de Crédito - Alanube Panamá

## Descripción General

Las **notas de crédito electrónicas** en Panamá son documentos fiscales que permiten anular o corregir facturas previamente emitidas. El servicio AlanubeService incluye soporte completo para la emisión de notas de crédito según las normativas de la DGI de Panamá.

### Características Principales
- **Documentos referenciados obligatorios**
- **4 tipos de referencia** (ID, CUFE, PAPER, PRINTER)
- **Validaciones DGI** específicas para notas de crédito
- **Procesamiento síncrono y asíncrono**
- **Endpoint dedicado** `/pan/v1/credit-notes`

## Diferencias con Facturas

| Aspecto | Facturas | Notas de Crédito |
|---------|----------|------------------|
| **Endpoint** | `/pan/v1/invoices` | `/pan/v1/credit-notes` |
| **Documentos Referenciados** | Opcional | **Obligatorio** |
| **Naturaleza por Defecto** | `01` (Venta) | `11` (Devolución) |
| **Tipo de Documento** | `01-03, 08-10` | `04` |
| **Validaciones Adicionales** | - | Validación de referencias |

## Uso Básico

### 1. Método Principal del Servicio

```php
use App\Services\AlanubeService;
use App\Models\Organization;

$service = new AlanubeService();
$organization = Organization::find($organizationId);

$creditNoteData = [
    'information' => [
        'issueType' => '01',
        'numeration' => '0000000001',
        'billingPoint' => '001',
        'nature' => '11', // Devolución
        'operationType' => 1,
        'destination' => '1',
        'issueDate' => now()->toISOString()
    ],
    'receiver' => [
        'type' => '01',
        'name' => 'Cliente para Nota de Crédito',
        'ruc' => [
            'type' => 1,
            'ruc' => '8-123-456'
        ],
        'country' => 'PA'
    ],
    'items' => [
        [
            'description' => 'Devolución - Producto',
            'quantity' => 1,
            'prices' => ['transfer' => 100.00],
            'itbms' => ['rate' => '01']
        ]
    ],
    'totals' => [
        'paymentTime' => 1,
        'change' => 0
    ],
    'paymentMethods' => [
        [
            'type' => '02', // Efectivo
            'amount' => 107.00
        ]
    ],
    'referencedDocuments' => [ // OBLIGATORIO
        [
            'issueDate' => '2025-01-21T10:00:00Z',
            'emissionType' => 'ID',
            'apiIdentification' => 'ALANUBE_ID_123456789012345678'
        ]
    ]
];

$result = $service->emitCreditNote($organization, $creditNoteData);

if ($result['success']) {
    echo "Nota de crédito emitida: " . $result['data']['id'];
} else {
    echo "Error: " . $result['message'];
}
```

### 2. Usando el Helper para Módulos Externos

```php
use App\Utils\AlanubeEmissionHelper;

// Emisión síncrona
$result = AlanubeEmissionHelper::emitCreditNote($organization, $creditNoteData, $userId, false);

// Emisión asíncrona
$result = AlanubeEmissionHelper::emitCreditNote($organization, $creditNoteData, $userId, true);

// Transformar desde formato DocuCenter
$referencedDocs = [
    [
        'issueDate' => '2025-01-21T10:00:00Z',
        'emissionType' => 'PAPER',
        'otherIdentification' => '001-001-0000000123'
    ]
];

$creditNoteData = AlanubeEmissionHelper::transformDocuCenterToCreditNote($docuCenterData, $referencedDocs);
$result = AlanubeEmissionHelper::emitCreditNote($organization, $creditNoteData);
```

##  Documentos Referenciados

Las notas de crédito **DEBEN** incluir al menos un documento referenciado. Hay 4 tipos de referencia:

### 1. Referencia por ID de Alanube (ID)

```php
'referencedDocuments' => [
    [
        'issueDate' => '2025-01-21T10:00:00Z',
        'emissionType' => 'ID',
        'apiIdentification' => 'ALANUBE_INVOICE_ID_26_CHARS' // ID de 26 caracteres
    ]
]
```

### 2. Referencia por CUFE (CUFE)

```php
'referencedDocuments' => [
    [
        'issueDate' => '2025-01-21T10:00:00Z',
        'emissionType' => 'CUFE',
        'cufeIdentification' => 'CUFE_66_CARACTERES_DE_LA_FACTURA_ELECTRONICA_ORIGINAL' // 66 caracteres
    ]
]
```

### 3. Referencia a Factura en Papel (PAPER)

```php
'referencedDocuments' => [
    [
        'issueDate' => '2025-01-21T10:00:00Z',
        'emissionType' => 'PAPER',
        'otherIdentification' => '001-001-0000000123' // Número de factura en papel (22 caracteres)
    ]
]
```

### 4. Referencia a Impresora Fiscal (PRINTER)

```php
'referencedDocuments' => [
    [
        'issueDate' => '2025-01-21T10:00:00Z',
        'emissionType' => 'PRINTER',
        'otherIdentification' => '001-001-0000000123' // Número de impresora fiscal (22 caracteres)
    ]
]
```

## Validaciones Automáticas

El sistema valida automáticamente:

### 1. Validaciones de Documentos Referenciados

```php
// El helper incluye validación automática
$validation = AlanubeEmissionHelper::validateCreditNoteData($creditNoteData);

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "Error: $error\n";
    }
}
```

### 2. Validaciones por Tipo de Emisión

- **ID**: Requiere `apiIdentification` de 26 caracteres
- **CUFE**: Requiere `cufeIdentification` de 66 caracteres
- **PAPER/PRINTER**: Requiere `otherIdentification` de 22 caracteres

### 3. Validaciones Generales

- Receptor con nombre obligatorio
- Al menos un ítem requerido
- Documentos referenciados obligatorios
- Configuración PAC válida

## Procesamiento Asíncrono

### Job Específico para Notas de Crédito

```php
use App\Jobs\CreateInvoiceAlanubeJob;

// Crear job para nota de crédito
CreateInvoiceAlanubeJob::dispatch(
    $creditNoteData,
    $organizationId,
    $userId,
    'credit_note' // Tipo específico
)->onQueue('pac_integrations');
```

### Estados del Job

1. **INIT** - Inicializando
2. **VALIDATING** - Validando documentos referenciados
3. **PROCESSING** - Enviando a Alanube
4. **FINALIZING** - Procesando respuesta
5. **COMPLETED** - Completado exitosamente

## 🧪 Testing Completo

### 1. Comando Artisan

```bash
# Probar nota de crédito síncrona
php artisan test:alanube 1 --credit-note

# Probar nota de crédito asíncrona
php artisan test:alanube 1 --credit-note --async
```

### 2. Script Shell Interactivo

```bash
# Ejecutar pruebas específicas de notas de crédito
./scripts/test-alanube-panama.sh credit-note 1

# Modo interactivo (opción 8)
./scripts/test-alanube-panama.sh interactive
```

### 3. Suite Completa

```bash
# Incluye pruebas de notas de crédito
./scripts/test-alanube-panama.sh complete 1
```

## Ejemplos de Datos Completos

### Nota de Crédito Básica

```php
$basicCreditNote = [
    'information' => [
        'issueType' => '01',
        'numeration' => '0000000001',
        'billingPoint' => '001',
        'securityCode' => '123456789',
        'cafe' => [
            'format' => 1,
            'delivery' => 1
        ],
        'nature' => '11', // Devolución
        'operationType' => 1,
        'destination' => '1',
        'receiverContainer' => 1,
        'issueDate' => '2025-01-22T10:30:00Z'
    ],
    'receiver' => [
        'type' => '01', // Contribuyente
        'name' => 'Empresa Cliente S.A.',
        'address' => 'Avenida Balboa, Ciudad de Panamá',
        'telephones' => ['+507-123-4567'],
        'emails' => ['cliente@empresa.com'],
        'country' => 'PA',
        'ruc' => [
            'type' => 2, // Jurídico
            'ruc' => '123456789-1-123456'
        ],
        'location' => [
            'code' => '08010001' // Provincia, distrito, corregimiento
        ]
    ],
    'items' => [
        [
            'number' => '0001',
            'description' => 'Devolución - Producto de Software',
            'code' => 'SOFT001',
            'unit' => 'UND',
            'quantity' => 2,
            'prices' => [
                'transfer' => 150.00,
                'discount' => 0,
                'transport' => 0,
                'insurance' => 0
            ],
            'itbms' => [
                'rate' => '01' // 7%
            ]
        ]
    ],
    'totals' => [
        'change' => 0,
        'transport' => 0,
        'insurance' => 0,
        'otherExpenses' => 0,
        'paymentTime' => 1 // Contado
    ],
    'paymentMethods' => [
        [
            'type' => '02', // Efectivo
            'amount' => 321.00 // 300 + 21 ITBMS
        ]
    ],
    'referencedDocuments' => [
        [
            'issueDate' => '2025-01-21T15:20:00Z',
            'emissionType' => 'ID',
            'apiIdentification' => 'ALANUBE_ID_123456789012345678'
        ]
    ]
];
```

### Nota de Crédito con Múltiples Referencias

```php
$multipleRefCreditNote = [
    // ... información y receptor igual ...
    'referencedDocuments' => [
        [
            'issueDate' => '2025-01-20T10:00:00Z',
            'emissionType' => 'ID',
            'apiIdentification' => 'ALANUBE_ID_123456789012345678'
        ],
        [
            'issueDate' => '2025-01-19T14:30:00Z',
            'emissionType' => 'PAPER',
            'otherIdentification' => '001-001-0000000999'
        ],
        [
            'issueDate' => '2025-01-18T09:15:00Z',
            'emissionType' => 'CUFE',
            'cufeIdentification' => 'CUFE123456789012345678901234567890123456789012345678901234567890123456'
        ]
    ]
];
```

### Nota de Crédito de Exportación

```php
$exportCreditNote = [
    'information' => [
        // ... campos básicos ...
        'nature' => '11', // Devolución
        'destination' => '2' // Extranjero
    ],
    'receiver' => [
        'type' => '04', // Extranjero
        'name' => 'International Company LLC',
        'country' => 'US',
        'foreign' => [
            'number' => 'US123456789',
            'country' => 'Estados Unidos'
        ]
    ],
    'exportation' => [
        'incoterm' => 'FOB',
        'currency' => 'USD',
        'exchangeRate' => 1.0,
        'amount' => 500.00,
        'port' => 'Puerto de Balboa'
    ],
    'items' => [
        [
            'description' => 'Devolución - Producto de Exportación',
            'quantity' => 5,
            'prices' => ['transfer' => 100.00],
            'itbms' => ['rate' => '00'] // 0% para exportación
        ]
    ],
    'referencedDocuments' => [
        [
            'issueDate' => '2025-01-15T12:00:00Z',
            'emissionType' => 'CUFE',
            'cufeIdentification' => 'EXPORT_CUFE_66_CHARS_1234567890123456789012345678901234567890123456'
        ]
    ]
];
```

## Integración con Módulos Existentes

### Para Módulos de Ventas

```php
// En el módulo de ventas, cuando se requiere una nota de crédito
class SalesController extends Controller 
{
    public function createCreditNote(Request $request)
    {
        $saleData = $request->all();
        $organization = auth()->user()->organization;
        
        // Agregar documentos referenciados
        $saleData['referencedDocuments'] = [
            [
                'issueDate' => $request->original_invoice_date,
                'emissionType' => $request->reference_type, // ID, CUFE, PAPER, PRINTER
                'apiIdentification' => $request->alanube_invoice_id // Si es tipo ID
            ]
        ];
        
        // Emitir nota de crédito
        $result = AlanubeEmissionHelper::emitCreditNote(
            $organization,
            $saleData,
            auth()->id(),
            true // async
        );
        
        return response()->json($result);
    }
}
```

### Para Módulos de Devoluciones

```php
// En el módulo de devoluciones
class ReturnsController extends Controller
{
    public function processReturn(Request $request)
    {
        $returnData = $request->all();
        $organization = auth()->user()->organization;
        
        // Transformar datos de devolución a nota de crédito
        $referencedDocs = [
            [
                'issueDate' => $returnData['original_invoice_date'],
                'emissionType' => 'ID',
                'apiIdentification' => $returnData['alanube_invoice_id']
            ]
        ];
        
        $creditNoteData = AlanubeEmissionHelper::transformDocuCenterToCreditNote(
            $returnData,
            $referencedDocs
        );
        
        // Validar antes de emitir
        $validation = AlanubeEmissionHelper::validateCreditNoteData($creditNoteData);
        
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors']
            ], 400);
        }
        
        // Emitir nota de crédito
        $result = AlanubeEmissionHelper::emitCreditNote(
            $organization,
            $creditNoteData,
            auth()->id(),
            false // sync para devoluciones inmediatas
        );
        
        return response()->json($result);
    }
}
```

## Monitoreo y Logging

### Logs Automáticos

El sistema genera logs automáticos para:

```php
// Logs de información
Log::info('[AlanubeService] Emitiendo nota de crédito', [
    'organization_id' => $organization->id,
    'referenced_docs_count' => count($creditNoteData['referencedDocuments'])
]);

// Logs de éxito
Log::info('Nota de crédito emitida exitosamente con Alanube Panamá', [
    'organization_id' => $organization->id,
    'response_data' => $responseData
]);

// Logs de error
Log::error('[AlanubeService] Error emitiendo nota de crédito: ' . $e->getMessage(), [
    'organization_id' => $organization->id,
    'error_trace' => $e->getTraceAsString()
]);
```

### Métricas Recomendadas

- **Tiempo de procesamiento** de notas de crédito
- **Tasa de éxito** vs errores
- **Tipos de referencia** más utilizados
- **Errores de validación** más comunes

## Manejo de Errores Comunes

### 1. Documentos Referenciados Faltantes

```php
// Error: "Documentos referenciados son obligatorios"
// Solución: Siempre incluir al menos un documento referenciado
$creditNoteData['referencedDocuments'] = [
    [
        'issueDate' => '2025-01-21T10:00:00Z',
        'emissionType' => 'ID',
        'apiIdentification' => 'INVOICE_ID_26_CHARACTERS'
    ]
];
```

### 2. Tipo de Emisión Inválido

```php
// Error: "Tipo de emisión inválido"
// Solución: Usar solo ID, CUFE, PAPER, PRINTER
$doc['emissionType'] = 'ID'; // Correcto
$doc['emissionType'] = 'INVALID'; // Incorrecto
```

### 3. Identificación Faltante

```php
// Error: "apiIdentification requerido cuando emissionType es ID"
// Solución: Incluir el campo correspondiente según el tipo
switch ($emissionType) {
    case 'ID':
        $doc['apiIdentification'] = 'ALANUBE_ID_26_CHARS';
        break;
    case 'CUFE':
        $doc['cufeIdentification'] = 'CUFE_66_CHARS';
        break;
    case 'PAPER':
    case 'PRINTER':
        $doc['otherIdentification'] = 'PAPER_ID_22_CHARS';
        break;
}
```

## Mejores Prácticas

### 1. Validación Previa

```php
// Siempre validar antes de emitir
$validation = AlanubeEmissionHelper::validateCreditNoteData($creditNoteData);

if (!$validation['valid']) {
    return response()->json([
        'success' => false,
        'message' => 'Datos de nota de crédito inválidos',
        'errors' => $validation['errors']
    ], 400);
}
```

### 2. Manejo de Referencias

```php
// Mantener registro de facturas originales para referencias futuras
class Invoice extends Model
{
    public function getCreditNoteReferences(): array
    {
        return [
            [
                'issueDate' => $this->issue_date,
                'emissionType' => 'ID',
                'apiIdentification' => $this->alanube_id
            ]
        ];
    }
}
```

### 3. Procesamiento Asíncrono para Volumen

```php
// Para múltiples notas de crédito, usar procesamiento asíncrono
if (count($creditNotes) > 5) {
    foreach ($creditNotes as $creditNote) {
        AlanubeEmissionHelper::emitCreditNote(
            $organization,
            $creditNote,
            $userId,
            true // async
        );
    }
} else {
    // Procesamiento síncrono para pocas notas
    foreach ($creditNotes as $creditNote) {
        AlanubeEmissionHelper::emitCreditNote(
            $organization,
            $creditNote,
            $userId,
            false // sync
        );
    }
}
```

## Checklist de Implementación

- [ ] Configuración PAC con `pac_type = 'alanube_panama'`
- [ ] Token de Alanube configurado
- [ ] Documentos referenciados incluidos
- [ ] Validación de datos implementada
- [ ] Manejo de errores configurado
- [ ] Logging habilitado
- [ ] Testing realizado
- [ ] Monitoreo configurado

---

Esta guía proporciona toda la información necesaria para implementar notas de crédito electrónicas con Alanube Panamá en DocuCenter. Para soporte adicional, consulte la documentación técnica principal o ejecute las pruebas automatizadas.
