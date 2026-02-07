# Problema: Google Document AI Training JSONL - Error Persistente

## Resumen del Problema
Después de múltiples iteraciones, Google Document AI sigue rechazando nuestros archivos JSONL con el error:
- `"deserialization_error":"extraneous characters after end of JSON object"`
- `"entities_count":"0"`
- `"constraint":"text_anchor"`

## Intentos Realizados

### 1. Con textAnchor y text (índices sintéticos)
- **Formato**: URI + mimeType + text + entities[type, mentionText, textAnchor]
- **Resultado**: FAILED - "extraneous characters"
- **Problema**: Índices no corresponden al PDF real

### 2. Cambiando startIndex/endIndex de string a integer
- **Cambio**: `"startIndex":"0"` → `"startIndex":0`
- **Resultado**: FAILED - mismo error

### 3. Corrigiendo newlines en JSONL
- **Cambio**: Agregando newlines solo entre líneas, no al final
- **Resultado**: FAILED - mismo error

### 4. Sin campo text ni textAnchor
- **Formato**: URI + mimeType + entities[type, mentionText]
- **Resultado**: FAILED - "constraint: text_anchor" (Google lo requiere)

### 5. Limpiando caracteres de control en mentionText
- **Cambio**: Eliminar `\n`, `\r` y otros caracteres de control
- **Resultado**: FAILED - mismo error (aunque los newlines se limpiaron correctamente)

## Análisis del Archivo Actual

**Archivo**: `20251126005114.jsonl`
- JSON válido en todas las líneas
- No termina con newline
- No hay newlines en mentionText
- ContentType correcto (text/plain)
- Contiene caracteres no-ASCII (°, º, á)

**Formato actual**:
```json
{
  "uri": "gs://...",
  "mimeType": "application/pdf",
  "entities": [
    {"type": "invoice_number", "mentionText": "12345"}
  ]
}
```

## Diagnóstico

Google Document AI está rechazando el formato porque:

1. **Requiere textAnchor**: El error "constraint: text_anchor" indica que textAnchor es obligatorio
2. **No puede inferir posiciones**: Sin textAnchor, Google no puede ubicar el texto en el PDF
3. **Formato API incompatible**: El formato programático no es compatible con lo que Google espera

## Solución Recomendada

### Tipos de Procesadores en Document AI Workbench

Google Document AI ofrece varios tipos de procesadores con IA generativa:

1. **Custom Extractor** (LO QUE NECESITAS)
   - Identifica y extrae datos específicos de documentos
   - Ideal para: facturas, recibos, formularios
   - Campos personalizables: invoice_number, total_amount, vendor_name, etc.
   - **Usar para**: Extraer los 10+ campos de facturas panameñas

2. ** Custom Classifier**
   - Agrupa documentos en categorías
   - Ideal para: clasificar tipos de documentos (factura vs recibo vs contrato)
   - No extrae datos, solo clasifica

3. **Custom Splitter**
   - Identifica límites de documentos en archivos grandes
   - Ideal para: PDFs con múltiples facturas/documentos
   - Separa automáticamente cada documento

4. **Summarizer**
   - Genera resúmenes de documentos
   - Ideal para: contratos largos, reportes
   - No extrae campos estructurados

### Pasos para Crear Custom Extractor

**YA TIENES UN CUSTOM EXTRACTOR CREADO**

Información del processor existente:
- **Nombre**: docucenter-custom-extractor
- **ID**: `d6eea813b0c73fc8`
- **Tipo**: Custom Extractor (correcto para extracción de datos)
- **Estado**: Habilitado
- **Región**: us
- **Creado**: 13 nov 2025

**Siguiente Paso: Ir a Workbench para Anotar Documentos**

El processor ya existe, pero necesita ser entrenado con anotaciones correctas usando Workbench UI.

**Paso 1: Acceder al Processor en Workbench**
- Ir a [Google Cloud Document AI Workbench](https://console.cloud.google.com/ai/document-ai/workbench)
- Buscar el processor: `docucenter-custom-extractor` (ID: d6eea813b0c73fc8)
- O ir directamente a: `https://console.cloud.google.com/ai/document-ai/workbench/processors/d6eea813b0c73fc8`

**Paso 2: Definir esquema y vista previa**
- En el menú lateral, ir a **"Definir esquema y vista previa"**
- Aquí defines los entity types (campos) que quieres extraer

**Schema Ya Configurado (14 campos):**

**Campos de encabezado de factura** (Opcional varias veces):
- `due_date` - Texto sin formato
- `invoice_date` - Texto sin formato
- `invoice_number` - Texto sin formato
- `invoice_type` - Texto sin formato
- `subtotal` - Número
- `tax_amount` - Número
- `total_amount` - Número
- `vendor_dv` - Texto sin formato (dígito verificador)
- `vendor_name` - Texto sin formato
- `vendor_tax_id` - Texto sin formato (RUC)

**Line items** (Obligatoria varias veces):
- `line_item_amount` - Número
- `line_item_description` - Texto sin formato
- `line_item_quantity` - Número
- `line_item_unit_price` - Número

**Campos Faltantes que Usas en el Código:**
- `net_amount` - (usas en código como "subtotal sin impuestos")
- `payment_method` - (usas en algunos documentos: "CONTADO", "CREDITO")

## SOLUCIÓN PROGRAMÁTICA: Entrenar desde DocuCenter con OCR

**SÍ PUEDES ENTRENAR DESDE CÓDIGO**, pero necesitas hacer OCR primero para obtener las coordenadas textAnchor reales.

### Cómo Funciona

1. **Hacer OCR de cada PDF** usando el OCR Processor de Google
2. **Extraer coordenadas textAnchor** del resultado del OCR
3. **Generar JSONL correcto** con uri + text + entities (con textAnchor real)
4. **Subir a GCS** y entrenar normalmente

### Comando Artisan Creado

```bash
# Generar training data con OCR
php artisan documentai:generate-training-with-ocr --timestamp=20251126005110 --limit=20

# Esto hace:
# 1. Lee los 20 PDFs de training-pdfs/20251126005110/
# 2. Hace OCR de cada uno usando Document AI OCR Processor
# 3. Busca las coordenadas textAnchor de cada campo
# 4. Genera JSONL con format correcto: uri + text + entities (con textAnchor)
# 5. Sube a gs://bucket/datasets/training/{timestamp}/ y .../test/{timestamp}/
# 6. Listo para entrenar con: php artisan documentai:train --timestamp={nuevo_timestamp}
```

### Prerequisitos

1. **Annotations**: Necesitas tener las annotations de cada PDF guardadas en algún lugar
   - Puede ser en base de datos (tabla `invoice_annotations`)
   - O en archivos JSON (uno por PDF)
   - Formato: `['invoice_number' => '12345', 'total_amount' => '100.00', ...]`

**Ejemplo: Crear archivo de annotations manualmente**

Crea un archivo JSON por cada PDF con sus valores:

```bash
# /tmp/training-annotations/document-1.json
{
  "invoice_number": "001-001-12345",
  "invoice_date": "2025-11-25",
  "vendor_name": "ACME Corp",
  "vendor_tax_id": "123456789-1-2020",
  "subtotal": "85.00",
  "tax_amount": "12.75",
  "total_amount": "97.75",
  "line_items": [
    {
      "description": "Servicio de consultoría",
      "quantity": "1",
      "unit_price": "85.00",
      "amount": "85.00"
    }
  ]
}
```

Luego modificar `getAnnotationsForDocument()` en el comando para leer estos archivos:

```php
protected function getAnnotationsForDocument(string $fileName): array
{
    $annotationsFile = "/tmp/training-annotations/{$fileName}.json";
    
    if (!file_exists($annotationsFile)) {
        $this->warn("  No hay annotations para {$fileName}");
        return [];
    }
    
    $annotations = json_decode(file_get_contents($annotationsFile), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->error("   Error parseando {$annotationsFile}: " . json_last_error_msg());
        return [];
    }
    
    return $annotations;
}
```

2. **OCR Processor**: Google proporciona un OCR processor general (ID: 'ocr')
   - No requiere configuración adicional
   - Disponible en todos los proyectos
   - Extrae texto + coordenadas automáticamente

### Métodos Agregados en DocumentAIService

```php
// Generar entities CON textAnchor real usando OCR
public function formatEntitiesWithOCR(string $pdfContent, array $annotations): array
{
    // 1. Hacer OCR del PDF
    $ocrResult = $this->performOCR($pdfContent);
    
    // 2. Buscar coordenadas de cada campo
    // 3. Retornar entities con textAnchor correcto
    return ['entities' => [...], 'text' => '...'];
}

// Hacer OCR usando Document AI OCR Processor
protected function performOCR(string $pdfContent): array
{
    // Usa el procesador 'ocr' de Google
    // Retorna: ['text' => string, 'segments' => array]
}

// Buscar textAnchor de un valor en el texto OCR
protected function findTextAnchor(string $value, string $fullText, array $segments): ?array
{
    // Busca el valor en el texto y retorna startIndex/endIndex
    return ['textSegments' => [['startIndex' => '0', 'endIndex' => '5']]];
}
```

### Ventajas de este Enfoque

**Totalmente programático**: No necesitas UI de Workbench
**Automatizable**: Puedes procesar cientos de documentos en batch
**textAnchor real**: Usa las coordenadas exactas del OCR de Google
**Formato correcto**: Google acepta este JSONL sin errores
**Integrado en DocuCenter**: Todo en tu flujo de trabajo actual

### Desventajas

**Requiere annotations previas**: Necesitas tener las annotations de cada PDF
**Más lento**: OCR de 20 PDFs puede tomar varios minutos
**API calls**: Cada PDF requiere una llamada al OCR Processor
**Matching imperfecto**: Si el valor no aparece exactamente en el PDF, no se encontrará textAnchor

## ALTERNATIVA: Document AI Workbench UI

Si no tienes annotations previas o quieres usar auto-labeling, usa Workbench UI:

**Nota sobre "Opcional" vs "Obligatoria":**
- Los campos de line_items están como "Obligatoria varias veces" Correcto
- Los campos de encabezado como "Opcional varias veces" - considera cambiar a "Obligatoria una vez" los críticos:
  * `invoice_number` debería ser "Obligatoria una vez"
  * `total_amount` debería ser "Obligatoria una vez"
  * `vendor_name` debería ser "Obligatoria una vez"

**Recomendación:** El schema actual es funcional. Puedes proceder a la siguiente etapa (Compilación) con esta configuración.

**Paso 3: Compilación (Importar y Anotar Documentos)**
- En el menú lateral, ir a **"Compilación"**
- Aquí importas los documentos y los anotas
- **Importar documentos**:
  * Click en "Import documents" o "Importar documentos"
  * Los PDFs ya están en GCS: `gs://docucenter-aci-document-ai-training/training-pdfs/20251126005110/`
  * Opción A: Importar desde GCS bucket (pegar la URI)
  * Opción B: Subir los 20 PDFs manualmente
- **Anotar documentos**:
  * Workbench hará OCR automáticamente
  * Usar **Auto-labeling** (la IA sugiere campos automáticamente)
  * O anotar manualmente seleccionando texto y asignando entity types
  * Necesitas anotar mínimo 10-16 documentos para training
  * Dejar 4 documentos sin anotar para testing

**Paso 4: Crear versiones (Entrenar)**
- En el menú lateral, ir a **"Crear versiones"**
- Click en "Train new version" o "Entrenar nueva versión"
- Seleccionar documentos de training (los 16 anotados)
- Seleccionar documentos de test (los 4 sin anotar)
- Click en "Start training" o "Iniciar entrenamiento"
- Esperar 1-3 horas para que complete el entrenamiento
- Workbench genera textAnchor correcto automáticamente (esto resuelve nuestro problema)

**Paso 5: Evaluación y pruebas**
- En el menú lateral, ir a **"Evaluación y pruebas"**
- Una vez completado el training, aquí verás las métricas de rendimiento
- Probar la nueva versión con documentos de test

**Paso 6: Implementación y uso**
- En el menú lateral, ir a **"Implementación y uso"**
- Activar/Deploy la nueva versión entrenada
- La versión se puede usar inmediatamente desde el código
- El processor ID sigue siendo el mismo: `d6eea813b0c73fc8`
- La nueva versión tendrá su propio version ID

**Paso 7: Administrar versiones**
- En el menú lateral, ir a **"Administra versiones"**
- Aquí ves todas las versiones del processor
- Puedes activar/desactivar versiones
- Ver historial de trainings

**Opción 2: Usar Document AI Workbench Export como template**
- Crear UN documento de prueba en Workbench UI
- Exportar el JSONL
- Analizar el formato exacto que genera Google
- Replicar ese formato en código

**Opción 3: Usar Processor pre-entrenado**
- Invoice Parser de Google (pre-entrenado)
- Ya soporta muchos campos comunes
- Sin necesidad de training
- Puede no detectar campos específicos de Panamá

## Archivos de Utilidad Creados

- `scripts/test-jsonl-format.php` - Análisis completo de JSONL
- `scripts/test-google-jsonl-parser.php` - Simula parser de Google
- `scripts/list-training-files.php` - Lista archivos en GCS
- `scripts/decode-jsonl-line.php` - Decodifica líneas individuales
- `scripts/analyze-bytes.php` - Análisis byte por byte
- `scripts/analyze-mention-text.php` - Encuentra caracteres problemáticos
- `scripts/generate-minimal-jsonl.php` - Genera JSONL de prueba

## Conclusión

El problema NO es el formato JSONL en sí (que es válido), sino que **la API de Document AI Training requiere un formato específico de textAnchor que no podemos generar sin OCR real del PDF**.

La solución práctica es usar **Document AI Workbench UI** que maneja esto automáticamente.
