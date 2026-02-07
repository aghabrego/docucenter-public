# Alanube Service - Guía de Uso

## Descripción General

El servicio Alanube proporciona emisión de documentos electrónicos para dos países: **República Dominicana** y **Panamá**. El servicio detecta automáticamente el país basado en la configuración PAC y utiliza los endpoints oficiales correspondientes.

## Arquitectura del Servicio

### Países Soportados
- **República Dominicana**: 4 tipos de documentos (Fiscal, Consumo, Gubernamental, Exportación)
- **Panamá**: 2 tipos de documentos (Factura, Nota de Crédito)

### Detección Automática de País
El servicio detecta automáticamente el país basado en el endpoint de la conexión PAC:
- URLs con `/dom/v1` → República Dominicana
- URLs con `/pan/v1` → Panamá

## Configuración PAC

### República Dominicana
```php
// Ambiente de Pruebas
'endpoint' => 'https://sandbox.alanube.co/dom/v1'

// Ambiente de Producción  
'endpoint' => 'https://api.alanube.co/dom/v1'
```

### Panamá
```php
// Ambiente de Pruebas
'endpoint' => 'https://sandbox-api.alanube.co/pan/v1'

// Ambiente de Producción
'endpoint' => 'https://api.alanube.co/pan/v1'
```

## Uso del Servicio

### Inicialización

```php
use App\Services\AlanubeService;

// El servicio detecta automáticamente el país desde la configuración PAC
$alanubeService = new AlanubeService();
```

### Emisión de Facturas

#### República Dominicana - Factura Fiscal
```php
$documentData = [
    'header' => [
        'numero_comprobante_fiscal' => 'B01000000001',
        'tipo_documento' => 'FISCAL',
        'fecha_comprobante' => '2025-08-23',
        'fecha_vencimiento' => '2025-09-23',
        'moneda' => 'DOP',
        'tipo_cambio' => 1.00,
        'cliente' => [
            'tipo_identificacion' => 1, // RNC
            'numero_identificacion' => '123456789',
            'razon_social' => 'Cliente Ejemplo S.A.',
            'direccion' => 'Calle Principal #123',
            'municipio' => 'Santo Domingo',
            'provincia' => 'Distrito Nacional',
            'pais' => 'DO'
        ]
    ],
    'items' => [
        [
            'codigo_producto' => 'PROD001',
            'descripcion' => 'Producto de Ejemplo',
            'cantidad' => 2,
            'precio_unitario' => 1000.00,
            'descuento' => 0.00,
            'tipo_impuesto' => 'ITBIS',
            'tasa_impuesto' => 18.00
        ]
    ],
    'totales' => [
        'subtotal' => 2000.00,
        'descuento_total' => 0.00,
        'impuesto_total' => 360.00,
        'total' => 2360.00
    ]
];

$response = $alanubeService->emitDocument($documentData, $pacConnection);
```

#### República Dominicana - Factura de Consumo
```php
$documentData = [
    'header' => [
        'numero_comprobante_fiscal' => 'B02000000001',
        'tipo_documento' => 'CONSUMO',
        'fecha_comprobante' => '2025-08-23',
        'moneda' => 'DOP',
        'cliente' => [
            'tipo_identificacion' => 2, // Cédula
            'numero_identificacion' => '00112345678',
            'nombre' => 'Juan Pérez',
            'direccion' => 'Av. Winston Churchill #456'
        ]
    ],
    'items' => [
        [
            'descripcion' => 'Servicio de Consultoría',
            'cantidad' => 1,
            'precio_unitario' => 5000.00,
            'tipo_impuesto' => 'ITBIS',
            'tasa_impuesto' => 18.00
        ]
    ]
];

$response = $alanubeService->emitDocument($documentData, $pacConnection);
```

#### Panamá - Factura Electrónica
```php
$documentData = [
    'header' => [
        'numero_documento' => 'FE-001-000000001',
        'tipo_documento' => 'FACTURA',
        'fecha_emision' => '2025-08-23T10:30:00',
        'moneda' => 'PAB',
        'cliente' => [
            'tipo_identificacion' => 'RUC',
            'numero_identificacion' => '1234567-1-123456',
            'razon_social' => 'Empresa Panameña S.A.',
            'direccion' => [
                'provincia' => 'Panamá',
                'distrito' => 'Panamá',
                'corregimiento' => 'Bella Vista',
                'direccion_detallada' => 'Calle 50 #123'
            ]
        ]
    ],
    'items' => [
        [
            'codigo_producto' => 'SERV001',
            'descripcion' => 'Servicio Profesional',
            'cantidad' => 1,
            'precio_unitario' => 1500.00,
            'descuento' => 0.00,
            'codigo_impuesto' => 'ITBMS',
            'tasa_impuesto' => 7.00
        ]
    ],
    'totales' => [
        'subtotal' => 1500.00,
        'descuento_total' => 0.00,
        'impuesto_total' => 105.00,
        'total' => 1605.00
    ]
];

$response = $alanubeService->emitDocument($documentData, $pacConnection);
```

### Emisión de Notas de Crédito

#### República Dominicana - Nota de Crédito
```php
$creditNoteData = [
    'header' => [
        'numero_comprobante_fiscal' => 'B04000000001',
        'tipo_documento' => 'NOTA_CREDITO',
        'fecha_comprobante' => '2025-08-23',
        'documento_referencia' => [
            'numero_comprobante' => 'B01000000001',
            'fecha_comprobante' => '2025-08-20',
            'tipo_documento' => 'FISCAL'
        ],
        'motivo' => 'Devolución por producto defectuoso',
        'cliente' => [
            'tipo_identificacion' => 1,
            'numero_identificacion' => '123456789',
            'razon_social' => 'Cliente Ejemplo S.A.'
        ]
    ],
    'items' => [
        [
            'codigo_producto' => 'PROD001',
            'descripcion' => 'Producto de Ejemplo',
            'cantidad' => 1,
            'precio_unitario' => 1000.00,
            'tipo_impuesto' => 'ITBIS',
            'tasa_impuesto' => 18.00
        ]
    ],
    'totales' => [
        'subtotal' => 1000.00,
        'impuesto_total' => 180.00,
        'total' => 1180.00
    ]
];

$response = $alanubeService->emitCreditNoteDocument($creditNoteData, $pacConnection);
```

#### Panamá - Nota de Crédito
```php
$creditNoteData = [
    'header' => [
        'numero_documento' => 'NC-001-000000001',
        'tipo_documento' => 'NOTA_CREDITO',
        'fecha_emision' => '2025-08-23T14:15:00',
        'documento_referencia' => [
            'numero_documento' => 'FE-001-000000001',
            'fecha_emision' => '2025-08-20',
            'tipo_documento' => 'FACTURA'
        ],
        'motivo' => 'Corrección de datos',
        'cliente' => [
            'tipo_identificacion' => 'RUC',
            'numero_identificacion' => '1234567-1-123456',
            'razon_social' => 'Empresa Panameña S.A.'
        ]
    ],
    'items' => [
        [
            'codigo_producto' => 'SERV001',
            'descripcion' => 'Ajuste de Servicio',
            'cantidad' => 1,
            'precio_unitario' => 200.00,
            'codigo_impuesto' => 'ITBMS',
            'tasa_impuesto' => 7.00
        ]
    ]
];

$response = $alanubeService->emitCreditNoteDocument($creditNoteData, $pacConnection);
```

## Ejemplos Avanzados

### Validación de Configuración PAC
```php
use App\Services\AlanubeService;

$alanubeService = new AlanubeService();

// Validar configuración antes de emitir
$isValid = $alanubeService->validatePacConfiguration($pacConnection);

if (!$isValid) {
    throw new Exception('Configuración PAC inválida');
}

// Detectar país automáticamente
$country = $alanubeService->detectCountryFromPacConnection($pacConnection);
echo "País detectado: " . $country; // 'panama' o 'dominican_republic'
```

### Construcción de URLs Dinámicas
```php
// El servicio construye automáticamente las URLs según el país y tipo de documento

// Para República Dominicana
$url = $alanubeService->buildApiUrl($pacConnection, 'fiscal-invoices');
// Resultado: https://sandbox.alanube.co/dom/v1/fiscal-invoices

// Para Panamá
$url = $alanubeService->buildApiUrl($pacConnection, 'credit-notes');
// Resultado: https://sandbox-api.alanube.co/pan/v1/credit-notes
```

### Manejo de Respuestas
```php
$response = $alanubeService->emitDocument($documentData, $pacConnection);

if ($response['success']) {
    $documentId = $response['data']['id'];
    $pdfUrl = $response['data']['pdf_url'];
    $xmlUrl = $response['data']['xml_url'];
    
    echo "Documento emitido exitosamente: " . $documentId;
    echo "PDF disponible en: " . $pdfUrl;
    echo "XML disponible en: " . $xmlUrl;
} else {
    $errorMessage = $response['message'];
    $errorDetails = $response['errors'] ?? [];
    
    echo "Error al emitir documento: " . $errorMessage;
    foreach ($errorDetails as $field => $errors) {
        echo "Campo '{$field}': " . implode(', ', $errors);
    }
}
```

### Emisión con Jobs Asíncronos
```php
use App\Jobs\EmitAlanubeDocumentJob;

// Emitir documento de forma asíncrona
$job = new EmitAlanubeDocumentJob($documentData, $pacConnection, $documentType);
dispatch($job);

// Monitorear el progreso del job
$jobStatus = $job->getStatus();
echo "Estado del job: " . $jobStatus;
```

## Tipos de Documentos Soportados

### República Dominicana
- **FISCAL**: Factura con valor fiscal (B01)
- **CONSUMO**: Factura de consumo (B02)
- **GUBERNAMENTAL**: Comprobante gubernamental (B14)
- **EXPORTACION**: Comprobante de exportación (B16)

### Panamá
- **FACTURA**: Factura electrónica
- **NOTA_CREDITO**: Nota de crédito electrónica

## Endpoints Oficiales

### República Dominicana
```
Testing:
- Base: https://sandbox.alanube.co/dom/v1
- Fiscal: /fiscal-invoices
- Consumo: /invoices
- Gubernamental: /gubernamentals
- Exportación: /export-supports

Producción:
- Base: https://api.alanube.co/dom/v1
- [Mismos endpoints que testing]
```

### Panamá
```
Testing:
- Base: https://sandbox-api.alanube.co/pan/v1
- Facturas: /invoices
- Notas de Crédito: /credit-notes

Producción:
- Base: https://api.alanube.co/pan/v1
- [Mismos endpoints que testing]
```

## Logging y Debugging

El servicio incluye logging detallado para facilitar el debugging:

```php
// Los logs incluyen:
// - País detectado automáticamente
// - URL construida dinámicamente
// - Datos enviados a la API
// - Respuesta recibida
// - Errores de validación

// Para habilitar logging detallado en desarrollo:
Log::channel('alanube')->info('Detalles de emisión', [
    'country' => $country,
    'url' => $apiUrl,
    'document_type' => $documentType,
    'response' => $response
]);
```

## Migración desde AlanubeDomService

Para migrar código existente que usa `AlanubeDomService`:

```php
// Antes (AlanubeDomService)
$alanubeService = new AlanubeDomService();
$response = $alanubeService->emitDocument($data, $pacConnection);

// Después (AlanubeService - soporte dual)
$alanubeService = new AlanubeService();
$response = $alanubeService->emitDocument($data, $pacConnection);

// El nuevo servicio detecta automáticamente el país y mantiene compatibilidad
```

## Consideraciones de Seguridad

- **Tokens PAC**: Siempre usar variables de entorno para tokens de producción
- **Validación**: El servicio valida automáticamente la configuración PAC antes de emitir
- **Logging**: Los logs NO incluyen tokens de autenticación por seguridad
- **Ambientes**: Usar endpoints de testing durante desarrollo

## Testing

Para probar el servicio:

```bash
# Ejecutar tests específicos de Alanube
./scripts/test-alanube.sh detection

# Testing completo
./scripts/test-alanube.sh complete

# Testing interactivo
./scripts/test-alanube.sh interactive [org_id]
```

## Soporte y Documentación

- **Documentación Técnica**: `docs/technical/alanube-implementation.md`
- **Scripts de Testing**: `docs/testing/alanube-testing-guide.md`
- **Optimizaciones**: `docs/optimizations/alanube-performance.md`

---

*Documentación generada para DocuCenter - Sistema de Facturación Electrónica*
*Última actualización: Agosto 2025*
