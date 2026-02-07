# Personalización de Google Document AI para Facturas Panameñas

## ¿Se puede personalizar la detección en Google Document AI?

**Sí, hay 3 niveles de personalización disponibles:**

## 1. Custom Document Extractor (Recomendado) 

### Características
- **Entrenamiento supervisado** con tus propios documentos
- **Extracción personalizada** de campos específicos (DV, RUC panameño, etc.)
- **Alta precisión** para formatos específicos
- **Aprende patrones únicos** de tus facturas

### Proceso de Implementación

#### Paso 1: Preparar Dataset
```bash
# Necesitas 20-100 facturas representativas
dataset/
   train/          # 80% para entrenamiento
      factura_001.pdf
      factura_002.pdf
      ...
   test/           # 20% para validación
       factura_020.pdf
       ...
```

#### Paso 2: Crear Custom Processor

**Opción A: Via Console (Más fácil)**
1. Ir a: https://console.cloud.google.com/ai/document-ai/processors
2. Click "CREATE PROCESSOR"
3. Seleccionar "Custom Document Extractor"
4. Región: `us` (mantener consistencia)
5. Nombre: "Panama Invoice Custom Extractor"

**Opción B: Via API**
```php
use Google\Cloud\DocumentAI\V1\ProcessorType;
use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;

$client = new DocumentProcessorServiceClient();
$parent = $client->locationName('docucenter-aci', 'us');

$processor = $client->createProcessor([
    'parent' => $parent,
    'processor' => [
        'type' => 'CUSTOM_EXTRACTION_PROCESSOR',
        'display_name' => 'Panama Invoice Extractor'
    ]
]);
```

#### Paso 3: Anotar Documentos

**Campos Personalizados para Panamá:**
```yaml
Schema:
  - vendor_name: string
  - vendor_ruc: string         # Formato: 123456789-1-2020
  - vendor_dv: string           # Dígito Verificador (1-2 dígitos)
  - invoice_number: string
  - invoice_date: date
  - due_date: date
  - subtotal: number
  - itbms: number              # Impuesto panameño
  - total_amount: number
  - payment_method: string     # CREDITO, CONTADO, etc.
  - line_items:                # Array de items
      - code: string           # Código del producto
      - description: string
      - quantity: number
      - unit_price: number
      - discount: number
      - amount: number
```

**Proceso de Anotación:**
1. Subir las 20-50 facturas a Document AI Workbench
2. Para cada documento:
   - Dibujar bounding box sobre el campo
   - Asignar label del schema
   - Verificar el valor extraído
3. Repetir para todos los documentos

#### Paso 4: Entrenar Modelo

```bash
# Via Console
1. Click "TRAIN NEW VERSION"
2. Esperar 1-3 horas (dependiendo del tamaño del dataset)
3. Evaluar métricas:
   - Precision: >90% ideal
   - Recall: >85% ideal
   - F1 Score: >87% ideal

# Via API
gcloud ai document-ai processors train \
  --processor=projects/docucenter-aci/locations/us/processors/YOUR_PROCESSOR_ID \
  --dataset=gs://your-bucket/training-data
```

#### Paso 5: Implementar en Código

```php
// En .env
GOOGLE_DOCUMENT_AI_PROCESSOR_ID=your_custom_processor_id

// El código existente funcionará automáticamente
// Los campos personalizados estarán disponibles en $invoiceData['entities']
```

## 2. Human-in-the-Loop (HITL) Review

Agregar validación humana antes de procesamiento final:

```php
// En DocumentAIService.php
public function processWithReview(string $pdfContent): array
{
    $invoiceData = $this->processInvoice($pdfContent);
    
    // Si confianza baja, marcar para revisión humana
    if ($invoiceData['confidence'] < 0.75) {
        // Guardar en tabla pending_review
        PendingInvoiceReview::create([
            'data' => json_encode($invoiceData),
            'pdf_path' => $pdfPath,
            'status' => 'pending_review',
            'confidence' => $invoiceData['confidence']
        ]);
        
        return [
            'success' => false,
            'requires_review' => true,
            'message' => 'Documento requiere revisión humana'
        ];
    }
    
    return $invoiceData;
}
```

## 3. Entity Enrichment (Implementado)

Post-procesamiento con regex para mejorar detección (ya lo tienes):

```php
// app/Services/DocumentAIService.php
protected function enhanceDataFromText(array $data): array
{
    // Ya implementado
    // - Número de factura
    // - RUC del proveedor
    // - DV (Dígito Verificador)
    // - Nombre del proveedor
    // - Totales y subtotales
    // - ITBMS
}
```

## Comparación de Enfoques

| Característica | Pre-trained | Custom Processor | HITL | Post-processing |
|----------------|-------------|------------------|------|-----------------|
| **Precisión** | 65-75% | 85-95% | 100% | 75-85% |
| **Costo Setup** | $0 | $25-50 | $0 | $0 |
| **Costo/1000 pág** | $1.50 | $3.00 | $5-10 | $1.50 |
| **Tiempo Setup** | 0 | 2-4 horas | 1 hora | 2 horas |
| **Mantenimiento** | Bajo | Medio | Alto | Bajo |
| **Flexibilidad** | Baja | Alta | Alta | Media |

## Recomendación por Volumen

### <100 facturas/mes
- **Pre-trained + Post-processing** (actual)
- Costo: ~$15/mes
- Precisión: 75-85%

### 100-500 facturas/mes
- **Custom Processor**
- Inversión inicial: $50
- Costo mensual: ~$150/mes
- Precisión: 85-95%
- ROI: 2-3 meses

### >500 facturas/mes
- **Custom Processor + HITL para casos complejos**
- Inversión inicial: $100
- Costo mensual: ~$300/mes
- Precisión: 95-98%
- ROI: 1-2 meses

## Mejoras Específicas para Panamá

### Campos Adicionales Detectables

```php
// Agregar al enhanceDataFromText()

// 1. Tipo de transacción
if (preg_match('/(CREDITO|CONTADO|CHEQUE)/i', $text, $matches)) {
    $data['entities']['payment_method'] = [
        'value' => strtoupper($matches[1]),
        'confidence' => 0.90,
        'type' => 'payment_method',
        'source' => 'text_extraction'
    ];
}

// 2. Cliente RUC
if (preg_match('/RUC:\s*(\d{1,2})/i', $text, $matches)) {
    $data['entities']['customer_ruc'] = [
        'value' => $matches[1],
        'confidence' => 0.85,
        'type' => 'customer_tax_id',
        'source' => 'text_extraction'
    ];
}

// 3. Cliente DV
if (preg_match('/DV:\s*(\d{1,2})/i', $text, $matches)) {
    $data['entities']['customer_dv'] = [
        'value' => $matches[1],
        'confidence' => 0.85,
        'type' => 'customer_verification_digit',
        'source' => 'text_extraction'
    ];
}

// 4. Cliente Nombre
if (preg_match('/Nombre:\s*([A-ZÀ-ÿ\s,\.&]+)/i', $text, $matches)) {
    $data['entities']['customer_name'] = [
        'value' => trim($matches[1]),
        'confidence' => 0.85,
        'type' => 'customer_name',
        'source' => 'text_extraction'
    ];
}

// 5. Códigos de productos
foreach ($lineItems as &$item) {
    if (preg_match('/^(\d{6})\s+' . preg_quote($item['description'], '/') . '/m', $text, $matches)) {
        $item['product_code'] = $matches[1];
    }
}
```

## Validación de Coherencia

```php
protected function validateDataCoherence(array &$data): void
{
    $subtotal = $data['entities']['subtotal']['value'] ?? 0;
    $tax = $data['entities']['tax_amount']['value'] ?? 0;
    $total = $data['entities']['total_amount']['value'] ?? 0;
    
    $expectedTotal = $subtotal + $tax;
    $difference = abs($expectedTotal - $total);
    
    // Si diferencia >1%, marcar para revisión
    if ($difference > ($total * 0.01)) {
        $data['validation_errors'][] = [
            'field' => 'total_amount',
            'error' => 'Inconsistencia en cálculo de totales',
            'expected' => $expectedTotal,
            'found' => $total,
            'difference' => $difference
        ];
    }
    
    // Verificar suma de line items
    $lineItemsTotal = array_sum(array_column(
        $data['entities']['line_items']['value'] ?? [],
        'amount'
    ));
    
    if (abs($lineItemsTotal - $subtotal) > ($subtotal * 0.01)) {
        $data['validation_errors'][] = [
            'field' => 'line_items',
            'error' => 'Suma de items no coincide con subtotal',
            'expected' => $subtotal,
            'found' => $lineItemsTotal
        ];
    }
}
```

## Monitoreo y Mejora Continua

```php
// Crear tabla para tracking
Schema::create('document_ai_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number')->nullable();
    $table->float('confidence');
    $table->json('entities_detected');
    $table->json('validation_errors')->nullable();
    $table->boolean('requires_review')->default(false);
    $table->boolean('manually_corrected')->default(false);
    $table->json('corrections')->nullable();
    $table->timestamps();
});

// Guardar métricas después de cada procesamiento
DocumentAIMetric::create([
    'invoice_number' => $invoiceData['entities']['invoice_number']['value'] ?? null,
    'confidence' => $invoiceData['confidence'],
    'entities_detected' => $invoiceData['entities'],
    'validation_errors' => $invoiceData['validation_errors'] ?? null,
    'requires_review' => $invoiceData['confidence'] < 0.75
]);
```

## Próximos Pasos

1. **Implementado**: Post-processing con regex (DV, RUC, vendor_name)
2. **En progreso**: Validación de coherencia de totales
3.  **Pendiente**: Decidir si crear Custom Processor (depende del volumen)
4.  **Futuro**: Sistema HITL para casos de baja confianza

## Recursos Adicionales

- [Document AI Custom Extractors](https://cloud.google.com/document-ai/docs/custom-extractors)
- [Document AI Workbench](https://cloud.google.com/document-ai/docs/workbench)
- [Pricing Calculator](https://cloud.google.com/products/calculator)
- [Best Practices](https://cloud.google.com/document-ai/docs/best-practices)
