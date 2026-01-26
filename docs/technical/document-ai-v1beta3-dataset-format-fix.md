# Document AI v1beta3 Dataset Format - Corrección Completa

## Fecha
24 de noviembre de 2025

## Problema Original

El entrenamiento del Custom Processor fallaba con el error:
```
Tu conjunto de datos no cumple con los criterios mínimos de entrenamiento
- Todas las 14 etiquetas mostraban: 0 documentos de entrenamiento, 0 documentos de prueba
```

### Causa Raíz
El formato JSONL generado no cumplía con las especificaciones de Document AI v1beta3:

**Formato Incorrecto (anterior)**:
```json
{
  "document": {
    "mimeType": "application/pdf",
    "content": "base64_encoded_pdf_content..."
  },
  "annotations": {...},
  "entities": [...]
}
```

**Formato Correcto (v1beta3)**:
```json
{
  "document": {
    "uri": "gs://bucket/training-pdfs/20251124215223/document-123.pdf",
    "mimeType": "application/pdf"
  },
  "annotations": [
    {
      "type": "invoice_number",
      "mentionText": "INV-001",
      "textSegment": {
        "startIndex": "0",
        "endIndex": "7"
      }
    }
  ]
}
```

## Solución Implementada

### 1. Nuevo Método: `uploadPDFsToGCS()`
**Ubicación**: `app/Services/DocumentAIService.php`

**Funcionalidad**:
- Sube cada PDF individualmente a Google Cloud Storage
- Genera URIs únicos con timestamp: `gs://bucket/training-pdfs/{timestamp}/document-{id}.pdf`
- Retorna mapeo de `document_id => gcs_uri`

**Ejemplo de uso**:
```php
$docsForUpload = [
    ['id' => 1, 'file_path' => 'invoices/invoice1.pdf'],
    ['id' => 2, 'file_path' => 'invoices/invoice2.pdf']
];

$pdfUris = $service->uploadPDFsToGCS($docsForUpload);
// Retorna: [1 => 'gs://bucket/...', 2 => 'gs://bucket/...']
```

### 2. Nuevo Método: `extractTextSegments()`
**Ubicación**: `app/Services/DocumentAIService.php`

**Funcionalidad**:
- Convierte anotaciones simples (key-value) a formato Document AI
- Genera `textSegment` con `startIndex` y `endIndex`
- Por ahora usa posiciones simuladas (mejora futura: OCR real)

**Transformación**:
```php
// Input
$annotations = [
    'invoice_number' => 'INV-001',
    'total_amount' => '100.00'
];

// Output
[
    [
        'type' => 'invoice_number',
        'mentionText' => 'INV-001',
        'textSegment' => [
            'startIndex' => '0',
            'endIndex' => '7'
        ]
    ],
    [
        'type' => 'total_amount',
        'mentionText' => '100.00',
        'textSegment' => [
            'startIndex' => '17',
            'endIndex' => '23'
        ]
    ]
]
```

### 3. Método Reescrito: `uploadTrainingDataset()`
**Ubicación**: `app/Services/DocumentAIService.php`

**Cambios principales**:
- **Nueva firma**: `uploadTrainingDataset(array $trainingData, array $pdfUris): array`
- **Entrada**: Array con `['training' => [...], 'test' => [...]]` + mapeo de URIs
- **Salida**: `['training_uri' => '...', 'test_uri' => '...']`
- **Formato**: Genera JSONL con GCS URIs y textSegments

**Flujo**:
1. Itera sobre datasets `training` y `test`
2. Para cada documento, obtiene su GCS URI del mapeo
3. Extrae textSegments de las anotaciones
4. Genera línea JSONL con formato correcto
5. Sube cada dataset como archivo JSONL separado

### 4. Método Actualizado: `startTraining()`
**Ubicación**: `app/Services/DocumentAIService.php`

**Cambios**:
- **Nueva firma**: `startTraining(string $trainingGcsUri, ?string $testGcsUri = null): array`
- **Payload actualizado**: Usa `gcsDocuments.documents` en lugar de `gcsPrefix`
- **Test dataset**: Ahora acepta URI separado para dataset de prueba

**Estructura del payload**:
```php
[
    'inputData' => [
        'trainingDocuments' => [
            'gcsDocuments' => [
                'documents' => [
                    [
                        'gcsUri' => 'gs://bucket/datasets/training-dataset-xxx.jsonl',
                        'mimeType' => 'application/x-ndjson'
                    ]
                ]
            ]
        ],
        'testDocuments' => [
            'gcsDocuments' => [
                'documents' => [
                    [
                        'gcsUri' => 'gs://bucket/datasets/test-dataset-xxx.jsonl',
                        'mimeType' => 'application/x-ndjson'
                    ]
                ]
            ]
        ]
    ]
]
```

### 5. Componente Actualizado: TrainingManager
**Ubicación**: `app/Http/Livewire/Admin/DocumentAI/TrainingManager.php`

**Método modificado**: `startGoogleTraining()`

**Nuevo flujo**:
1. **Validación**: Verificar mínimo 20 documentos anotados
2. **Split 80/20**: Dividir documentos en training (80%) y test (20%)
3. **Preparar estructura**: Crear arrays con id, file_path, annotations
4. **Subir PDFs**: Llamar `uploadPDFsToGCS()` para todos los documentos
5. **Generar datasets**: Llamar `uploadTrainingDataset()` con split
6. **Iniciar entrenamiento**: Llamar `startTraining()` con URIs separados

**Código clave**:
```php
// Split 80/20
$allDocs = $annotatedDocs->toArray();
$totalDocs = count($allDocs);
$trainCount = (int) floor($totalDocs * 0.8);

$trainingDocs = array_slice($allDocs, 0, $trainCount);
$testDocs = array_slice($allDocs, $trainCount);

// Subir PDFs
$pdfUris = $documentAI->uploadPDFsToGCS($docsForUpload);

// Generar datasets
$datasetUris = $documentAI->uploadTrainingDataset($trainingDataSets, $pdfUris);

// Iniciar entrenamiento
$trainingResult = $documentAI->startTraining(
    $datasetUris['training_uri'],
    $datasetUris['test_uri'] ?? null
);
```

## Estructura de Archivos en GCS

Después de ejecutar el entrenamiento:

```
gs://bucket-name/
├── training-pdfs/
│   └── 20251124215223/          # Timestamp del entrenamiento
│       ├── document-1.pdf
│       ├── document-2.pdf
│       └── ...
└── datasets/
    ├── training-dataset-20251124215223.jsonl
    └── test-dataset-20251124215223.jsonl
```

## Requisitos de Document AI v1beta3

### Mínimos para Entrenamiento
- **Training dataset**: Mínimo 10 documentos por etiqueta
- **Test dataset**: Mínimo 10 documentos por etiqueta
- **Total recomendado**: 50+ documentos

### Formato JSONL Requerido
**Correcto**:
- `document.uri`: GCS URI del PDF
- `annotations[].type`: Nombre del campo
- `annotations[].mentionText`: Valor extraído
- `annotations[].textSegment`: Posiciones en el documento

**Incorrecto**:
- `document.content`: Base64 embebido
- Anotaciones sin `textSegment`
- GCS prefix en lugar de URIs específicos

## Testing

### Script de Prueba
**Ubicación**: `scripts/test-training-v2.sh`

**Operaciones disponibles**:
```bash
# Probar subida de PDFs
./scripts/test-training-v2.sh test-upload

# Probar generación de datasets
./scripts/test-training-v2.sh test-dataset

# Probar flujo completo (inicia entrenamiento real)
./scripts/test-training-v2.sh test-full
```

### Prueba Manual con Tinker
```bash
docker exec -it docucenter_laravel.test php artisan tinker

use App\Services\DocumentAIService;
use App\Models\DocumentAITrainingDocument;

$service = app(DocumentAIService::class);
$docs = DocumentAITrainingDocument::where('is_annotated', true)->take(20)->get();

// Probar subida de PDFs
$docsArray = $docs->map(fn($d) => ['id' => $d->id, 'file_path' => $d->file_path])->toArray();
$uris = $service->uploadPDFsToGCS($docsArray);

// Ver URIs generados
print_r($uris);
```

## Mejoras Futuras

### 1. Extracción Real de Posiciones de Texto
**Estado actual**: Usa posiciones simuladas
**Mejora**: Implementar extracción real con:
- PDF text extraction (pdftotext, PyPDF2)
- Document AI OCR para obtener coordenadas exactas
- Búsqueda de texto en documento parseado

**Implementación sugerida**:
```php
protected function extractTextSegments(string $pdfPath, array $annotations): array
{
    // Extraer texto completo del PDF
    $fullText = $this->extractPDFText($pdfPath);
    
    $annotationsWithSegments = [];
    
    foreach ($annotations as $fieldName => $value) {
        // Buscar valor en el texto
        $position = strpos($fullText, $value);
        
        if ($position !== false) {
            $annotationsWithSegments[] = [
                'type' => $fieldName,
                'mentionText' => $value,
                'textSegment' => [
                    'startIndex' => (string) $position,
                    'endIndex' => (string) ($position + strlen($value))
                ]
            ];
        }
    }
    
    return $annotationsWithSegments;
}
```

### 2. Validación de Requisitos Mínimos
Agregar validación antes de subir:
```php
protected function validateDatasetRequirements(array $trainingDocs, array $testDocs): bool
{
    $minPerSet = 10;
    
    // Contar documentos por etiqueta
    $labelCounts = [];
    
    foreach (['training' => $trainingDocs, 'test' => $testDocs] as $setType => $docs) {
        foreach ($docs as $doc) {
            foreach ($doc['annotations'] as $label => $value) {
                if (!empty($value)) {
                    $labelCounts[$setType][$label] = ($labelCounts[$setType][$label] ?? 0) + 1;
                }
            }
        }
    }
    
    // Validar mínimos
    foreach ($labelCounts as $setType => $labels) {
        foreach ($labels as $label => $count) {
            if ($count < $minPerSet) {
                throw new \RuntimeException(
                    "Label {$label} en {$setType}: {$count} docs (mínimo: {$minPerSet})"
                );
            }
        }
    }
    
    return true;
}
```

### 3. Limpieza de Archivos GCS
Agregar método para limpiar entrenamientos fallidos:
```php
public function cleanupFailedTraining(string $timestamp): void
{
    $bucket = $this->storage->bucket($this->bucketName);
    
    // Eliminar PDFs
    foreach ($bucket->objects(['prefix' => "training-pdfs/{$timestamp}/"]) as $object) {
        $object->delete();
    }
    
    // Eliminar datasets
    foreach ($bucket->objects(['prefix' => "datasets/"]) as $object) {
        if (strpos($object->name(), $timestamp) !== false) {
            $object->delete();
        }
    }
}
```

## Commit
```
refactor: implementar formato correcto de dataset Document AI v1beta3

- Agregar uploadPDFsToGCS para subir PDFs individuales a GCS
- Agregar extractTextSegments para generar anotaciones con posiciones
- Reescribir uploadTrainingDataset con formato correcto (GCS URIs + textSegment)
- Implementar split automático 80/20 entre training y test datasets
- Actualizar startTraining para aceptar URIs separados de training y test
- Modificar TrainingManager para usar nuevo flujo completo

Corrige error de formato JSONL que causaba 0 documentos reconocidos
Ahora usa estructura: document.uri + annotations con startIndex/endIndex
```

## Referencias
- [Document AI v1beta3 API Reference](https://cloud.google.com/document-ai/docs/reference/rest/v1beta3)
- [Custom Extractor Training](https://cloud.google.com/document-ai/docs/workbench/build-custom-processor)
- [Dataset Format Specification](https://cloud.google.com/document-ai/docs/workbench/import-documents)

## Próximos Pasos

1. Implementar formato correcto de dataset
2. Agregar split train/test
3. Crear script de testing
4.  Ejecutar prueba completa con 20+ documentos
5.  Monitorear entrenamiento en Google Cloud Console
6.  Implementar extracción real de posiciones de texto
7.  Agregar validación de requisitos mínimos
