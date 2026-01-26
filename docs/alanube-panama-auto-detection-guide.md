# Guía de Detección Automática - Alanube Panamá

## 🤖 Descripción General

El servicio de Alanube Panamá incluye un sistema de **detección automática** que puede determinar si un conjunto de datos representa una **factura electrónica** o una **nota de crédito** sin necesidad de especificar manualmente el tipo de documento.

## Objetivo

Permitir que el sistema procese documentos automáticamente detectando su tipo basándose en el análisis de los datos proporcionados, simplificando la integración y reduciendo errores humanos.

## Algoritmo de Detección

El sistema utiliza **7 criterios** para determinar si un documento es una nota de crédito:

### 1. Documentos Referenciados 
- **Criterio más fuerte**: Presencia del campo `referencedDocuments`
- Las notas de crédito **siempre** deben referenciar documentos anteriores
- Si existe este campo, **automáticamente** se considera nota de crédito

### 2. Tipo de Documento Explícito 
- Verificación del campo `documentType = '04'`
- Tipo 04 corresponde específicamente a notas de crédito en Panamá

### 3. Naturaleza de Devolución 
- Campo `generalInformation.nature = '11'`
- Naturaleza 11 indica operaciones de devolución/reverso

### 4. Palabras Clave en Items 
- Análisis de descripciones de productos/servicios
- Palabras detectadas:
  - `devolución`
  - `anulación`
  - `nota de crédito`
  - `credit note`
  - `reverso`
  - `cancelación`

### 5. Cantidades o Precios Negativos 
- Verificación en items:
  - `quantity < 0`
  - `unitPrice < 0`
  - `totalPrice < 0`
- Verificación en totales:
  - `subtotal < 0`
  - `totalAmount < 0`
  - `taxTotal < 0`

### 6. Palabras Clave Generales 
- Análisis en campos de información general
- Búsqueda en `notes`, `comments`, `observations`
- Mismas palabras clave que criterio #4

### 7. Tipo de Receptor 
- Campo `receiver.type = 'credit_note'`
- Indicador específico para módulos externos

## Métodos Disponibles

### AlanubeService

```php
// Emisión automática (punto de entrada principal)
$response = $alanubeService->emitDocumentAuto($organization, $documentData);

// Verificar si es nota de crédito
$isCredit = $alanubeService->isCreditNoteDocument($documentData);

// Obtener resumen de detección
$summary = $alanubeService->getDetectionSummary($documentData);
```

### AlanubeEmissionHelper

```php
// Emisión automática con helper
$response = AlanubeEmissionHelper::emitDocumentAuto($organization, $documentData);

// Emisión automática asíncrona
$response = AlanubeEmissionHelper::emitDocumentAuto($organization, $documentData, null, true);
```

## Estructura de Respuesta

```php
[
    'success' => true,
    'document_type' => 'invoice|credit_note',
    'data' => [
        'id' => 'doc_id',
        'cufe' => 'document_cufe',
        // ... otros campos de respuesta
    ],
    'detected_as' => 'Factura|Nota de Crédito',
    'detection_criteria' => [
        'Documentos Referenciados' => true,
        'Tipo Documento 04' => false,
        // ... otros criterios
    ]
]
```

## 🧪 Testing

### Comando Artisan

```bash
# Pruebas de detección automática
php artisan test:alanube 123 --auto-detect --test-data

# Pruebas asíncronas
php artisan test:alanube 123 --auto-detect --test-data --async
```

### Script Shell

```bash
# Modo detección automática
./scripts/test-alanube-panama.sh auto-detect 123

# Modo interactivo
./scripts/test-alanube-panama.sh interactive
```

## Ejemplos de Uso

### Ejemplo 1: Documento de Factura

```php
$invoiceData = [
    'generalInformation' => [
        'number' => 'F-12345',
        'currency' => 'PAB',
        'date' => '2024-01-15',
        'nature' => '01' // Operación interna
    ],
    'items' => [
        [
            'quantity' => 2,
            'description' => 'Producto regular',
            'unitPrice' => 100.00,
            'totalPrice' => 200.00
        ]
    ]
    // ... resto de campos
];

// Detección automática -> Factura
$response = $alanubeService->emitDocumentAuto($organization, $invoiceData);
```

### Ejemplo 2: Documento de Nota de Crédito

```php
$creditNoteData = [
    'documentType' => '04', // Explicit credit note
    'generalInformation' => [
        'number' => 'NC-12345',
        'currency' => 'PAB',
        'date' => '2024-01-15',
        'nature' => '11' // Devolución
    ],
    'referencedDocuments' => [
        [
            'type' => 'ID',
            'number' => 'F-12345',
            'date' => '2024-01-10'
        ]
    ],
    'items' => [
        [
            'quantity' => -1, // Cantidad negativa
            'description' => 'Devolución de producto defectuoso',
            'unitPrice' => 100.00,
            'totalPrice' => -100.00
        ]
    ]
    // ... resto de campos
];

// Detección automática -> Nota de Crédito
$response = $alanubeService->emitDocumentAuto($organization, $creditNoteData);
```

### Ejemplo 3: Documento Ambiguo

```php
$ambiguousData = [
    'generalInformation' => [
        'number' => 'DOC-12345',
        'currency' => 'PAB',
        'date' => '2024-01-15'
    ],
    'receiver' => [
        'type' => 'credit_note', // Indicador en receiver
        'ruc' => '123456-1-123456',
        'businessName' => 'Cliente SA'
    ],
    'items' => [
        [
            'quantity' => 1,
            'description' => 'Anulación de cargo anterior', // Palabra clave
            'unitPrice' => 50.00,
            'totalPrice' => 50.00
        ]
    ]
];

// Detección automática -> Nota de Crédito (por palabras clave y tipo receptor)
$response = $alanubeService->emitDocumentAuto($organization, $ambiguousData);
```

## Procesamiento Asíncrono

La detección automática también funciona con el sistema de colas:

```php
// El job determinará automáticamente el tipo
CreateInvoiceAlanubeJob::dispatch($organizationId, $documentData);

// O usando el helper
AlanubeEmissionHelper::emitDocumentAuto($organization, $documentData, null, true);
```

## Consideraciones de Seguridad

1. **Validación**: Siempre se validan los datos antes de la detección
2. **Logging**: Todas las detecciones se registran con criterios utilizados
3. **Fallback**: Si falla la detección, se trata como factura por defecto
4. **Auditoría**: Resumen de criterios disponible para debugging

## Logging y Debugging

```php
// Ver resumen de detección
$summary = $alanubeService->getDetectionSummary($documentData);
/*
[
    'Documentos Referenciados' => true,
    'Tipo Documento 04' => true,
    'Naturaleza de Devolución' => true,
    'Palabras Clave en Items' => false,
    'Cantidades/Precios Negativos' => true,
    'Palabras Clave Generales' => false,
    'Tipo Receptor Credit Note' => false
]
*/

// Verificar solo si es nota de crédito
$isCredit = $alanubeService->isCreditNoteDocument($documentData);
```

## Casos de Uso

### 1. Integración con Módulos Externos
- Los módulos no necesitan especificar el tipo de documento
- Envían los datos y el sistema detecta automáticamente

### 2. Importación de Datos
- Procesar lotes de documentos sin clasificación manual
- Detección automática en masa

### 3. APIs Genéricas
- Endpoint único que maneja facturas y notas de crédito
- Simplifica la integración para desarrolladores externos

### 4. Migración de Datos
- Procesar documentos históricos sin metadata de tipo
- Clasificación automática basada en contenido

## 🚧 Limitaciones

1. **Dependencia de Datos**: La precisión depende de la calidad de los datos
2. **Documentos Atípicos**: Casos edge pueden requerir especificación manual
3. **Idioma**: Palabras clave optimizadas para español principalmente
4. **Contexto**: No considera contexto de negocio, solo datos técnicos

## Flujo de Trabajo

```mermaid
graph TD
    A[Datos de Documento] --> B[emitDocumentAuto()]
    B --> C[isCreditNoteDocument()]
    C --> D{¿Es Nota de Crédito?}
    D -->|Sí| E[emitCreditNote()]
    D -->|No| F[emitInvoice()]
    E --> G[Respuesta con document_type='credit_note']
    F --> H[Respuesta con document_type='invoice']
```

## Mejores Prácticas

1. **Usar Indicadores Claros**: Incluir campos como `documentType` cuando sea posible
2. **Documentos Referenciados**: Siempre incluir para notas de crédito
3. **Descripciones Descriptivas**: Usar términos claros en descripciones de items
4. **Testing Completo**: Probar casos edge con datos reales
5. **Monitoreo**: Revisar logs de detección para optimizar criterios

---

##  Soporte

Para dudas sobre la detección automática:
- Revisar logs de la aplicación
- Usar el método `getDetectionSummary()` para debugging
- Probar con el comando `test:alanube --auto-detect`

---

*Documentación generada para DocuCenter - Sistema de Facturación Electrónica*
*Última actualización: Enero 2024*
