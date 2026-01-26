# Opciones para Entrenar Document AI

## Opción 1: Workbench UI (RECOMENDADO - Más Rápido)

Usa la interfaz gráfica de Google con auto-labeling.

### Paso 1: Dar permisos al bucket

```bash
./scripts/fix-bucket-permissions.sh
```

O manualmente en consola:
```bash
PROJECT_NUMBER=$(gcloud projects describe docucenter-aci --format="value(projectNumber)")
gcloud storage buckets add-iam-policy-binding gs://docucenter-aci-document-ai-training \
    --member="serviceAccount:service-${PROJECT_NUMBER}@gcp-sa-prod-dai-core.iam.gserviceaccount.com" \
    --role="roles/storage.objectViewer"
```

### Paso 2: Importar en Workbench

1. Ir a: https://console.cloud.google.com/ai/document-ai/workbench/processors/d6eea813b0c73fc8
2. Menú lateral > **Compilación**
3. Click en **"Import documents"**
4. Seleccionar **"Import from Cloud Storage"**
5. Pegar: `gs://docucenter-aci-document-ai-training/training-pdfs/20251126005110/`
6. Click en **"Import"**

### Paso 3: Anotar documentos

1. Los PDFs aparecerán en la lista
2. Click en cada PDF
3. Usar **Auto-labeling**: Google sugiere campos automáticamente
4. Revisar y corregir si es necesario
5. Anotar 16 documentos para training, dejar 4 para test

### Paso 4: Entrenar

1. Menú lateral > **"Crear versiones"**
2. Click en **"Train new version"**
3. Seleccionar 16 docs de training, 4 de test
4. Click en **"Start training"**
5. Esperar 1-3 horas

**Ventajas:**
- Auto-labeling (IA sugiere campos)
- No necesitas crear annotations manualmente
- Interfaz visual fácil de usar
- textAnchor generado automáticamente

**Desventajas:**
- Manual (uno por uno)
- No automatizable

---

## Opción 2: Código (Programático)

Usa el comando Artisan con OCR para generar training data.

### Paso 1: Preparar estructura de annotations

```bash
./scripts/prepare-manual-annotations.sh
```

Esto crea:
- `/tmp/training-pdfs/` - Los 20 PDFs descargados
- `/tmp/training-annotations/` - 20 archivos JSON vacíos

### Paso 2: Llenar annotations manualmente

Abre cada PDF y llena su JSON correspondiente:

```bash
# Ejemplo: /tmp/training-annotations/document-1.pdf.json
{
  "invoice_number": "001-001-12345",
  "invoice_date": "2025-11-25",
  "invoice_type": "01",
  "vendor_name": "ACME Corporation S.A.",
  "vendor_tax_id": "123456789-1-2020",
  "vendor_dv": "12",
  "subtotal": "85.00",
  "tax_amount": "12.75",
  "total_amount": "97.75",
  "line_items": [
    {
      "line_item_description": "Servicio de consultoría",
      "line_item_quantity": "1",
      "line_item_unit_price": "85.00",
      "line_item_amount": "85.00"
    }
  ]
}
```

### Paso 3: Copiar annotations al contenedor

```bash
docker cp /tmp/training-annotations docucenter_laravel.test:/tmp/training-annotations
```

### Paso 4: Generar training data con OCR

```bash
sail artisan documentai:generate-training-with-ocr \
  --timestamp=20251126005110 \
  --limit=20
```

Esto:
- Lee cada PDF
- Hace OCR para obtener coordenadas
- Busca textAnchor de cada campo
- Genera JSONL correcto
- Sube a GCS

### Paso 5: Entrenar

```bash
sail artisan documentai:train --timestamp={nuevo_timestamp}
```

**Ventajas:**
- Automatizable (batch processing)
- Reutilizable para futuros trainings
- Control total del proceso

**Desventajas:**
- Requiere crear 20 archivos JSON manualmente
- Más lento (OCR de 20 PDFs)
- Más complejo

---

## Recomendación

**Usa Opción 1 (Workbench UI)** porque:
- Ya tienes el processor configurado
- El auto-labeling te ahorra mucho trabajo
- Es más rápido para 20 documentos
- No necesitas crear annotations manualmente

Solo usa Opción 2 si:
- Ya tienes las annotations en base de datos
- Necesitas procesar cientos de documentos
- Quieres automatizar el proceso completo
