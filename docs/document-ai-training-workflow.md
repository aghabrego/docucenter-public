# Guía de Entrenamiento de Document AI - Workflow Completo

## Resumen Ejecutivo

Este sistema permite entrenar un modelo custom de Google Document AI para extraer datos de facturas panameñas automáticamente, usando una interfaz web en DocuCenter.

**Estado actual**: Sistema funcional y optimizado
**Última actualización**: 26 de noviembre de 2025

---

## ¿Qué hace este sistema?

1. **Sube PDFs** de facturas a DocuCenter
2. **Anota manualmente** los campos importantes (vendor, tax ID, totales, etc.)
3. **Genera automáticamente** archivos JSON con las anotaciones mapeadas al texto OCR
4. **Sube todo a Google Cloud Storage** (PDFs + JSONs)
5. **Listo para importar** manualmente en Google Cloud Workbench Console

---

## Workflow Paso a Paso

### Paso 1: Subir Documentos (DocuCenter)

**Ubicación**: `/admin/sage50/document-ai-training`

1. Ir a la sección "Upload Training Documents"
2. Hacer clic en "Choose Files" o arrastrar PDFs
3. Subir facturas (máximo 12MB por archivo)
4. El sistema automáticamente:
   - Guarda el PDF localmente
   - Extrae texto con OCR
   - Detecta tipo de documento (venta/compra)

**Requisito mínimo**: 20 documentos anotados para entrenar

---

### Paso 2: Anotar Documentos (DocuCenter)

**Importante**: Debes anotar manualmente cada campo en los PDFs

#### Campos a anotar:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| `vendor_name` | Nombre del proveedor | "Supermercado El Rey" |
| `vendor_tax_id` | RUC sin DV | "123456" |
| `vendor_dv` | Dígito verificador | "78" |
| `invoice_number` | Número de factura | "FACT-001-00123" |
| `invoice_date` | Fecha de emisión | "2025-11-26" |
| `due_date` | Fecha de vencimiento | "2025-12-26" |
| `subtotal` | Subtotal sin impuestos | "100.00" |
| `tax_amount` | Monto de impuestos | "7.00" |
| `total_amount` | Total a pagar | "107.00" |
| `payment_method` | Método de pago | "Efectivo" |
| `document_type` | Tipo | "sale" o "purchase" |

#### Proceso de anotación:

1. Click en botón "Annotate" en cada documento
2. Revisar y corregir campos pre-llenados por OCR
3. Asegurarse que `document_type` sea correcto (sale/purchase)
4. Click "Save Annotation"

**Tip**: El sistema pre-llena con los valores extraídos por OCR, solo corrígelos si están mal.

---

### Paso 3: Generar JSONs (DocuCenter)

**Cuándo hacer esto**: Cuando tengas 20+ documentos anotados

1. Verificar en el panel de estadísticas:
   - "Annotated" debe ser ≥ 20
   - "Pending JSON" muestra cuántos faltan por generar

2. Click en botón **"Generate JSON Files (X)"**

3. El sistema ejecuta en **segundo plano** (Job):
   - Verifica qué PDFs ya están en GCS (no los sube de nuevo) 
   - Sube PDFs nuevos a GCS
   - Procesa cada PDF con OCR de Document AI
   - Mapea cada anotación a su posición exacta en el texto
   - Genera archivo JSON en formato Document proto
   - Sube JSON a GCS

4. **Duración aproximada**: 8-10 segundos por documento
   - 20 docs = ~3 minutos
   - 50 docs = ~7 minutos

5. **Seguimiento del progreso**:
   - La página se actualiza automáticamente cada 30 segundos
   - Verás un alert con el estado:
     - "QUEUED" → Job iniciado
     - "GENERATING" → Procesando X/Y documentos
     - "COMPLETED" → Listos para importar

6. **Al completar**, verás:
   - Ruta GCS donde están los JSONs
   - Link directo a Google Cloud Workbench Console

---

### Paso 4: Importar en Google Cloud Workbench (Manual)

**IMPORTANTE**: Este paso se hace en Google Cloud Console, NO en DocuCenter

#### 4.1 Acceder a Workbench

1. Ir a: https://console.cloud.google.com/ai/document-ai/workbench
2. Seleccionar proyecto: `docucenter-aci`
3. Seleccionar processor: `Custom Extractor` (ID: `d6eea813b0c73fc8`)

#### 4.2 Importar Documentos

1. En Workbench, click en **"Import"** (botón azul)

2. Seleccionar **"Import documents from Cloud Storage"**

3. **Configuración de la importación**:
   
   ** Ruta de acceso fuente**:
   ```
   gs://docucenter-aci-document-ai-training/training-pdfs/
   ```
   **MUY IMPORTANTE**: Usar la carpeta de PDFs, NO la carpeta `json/`
   
   **División de datos** (Dataset split):
   - **Training**: 80%
   - **Test**: 20%
   
   **🏷Etiquetado automático**:
   - **Activar** "Importar con etiquetado automático"
   - Esto usará los archivos JSON para etiquetar los PDFs automáticamente

4. Click **"Import"** (botón al final del formulario)

#### 4.3 Esperar Importación

- ⏱**Tiempo**: 5-15 minutos dependiendo de cantidad
- **Progreso**: Verás una barra de progreso en Workbench
- **Status**: Verificar que los documentos aparezcan en la lista con sus etiquetas

#### 4.4 Verificar Documentos Importados

1. Los documentos deben aparecer en la lista de Workbench
2. Cada documento debe mostrar:
   - Thumbnail del PDF
   - Etiquetas aplicadas (vendor_name, invoice_number, etc.)
   - Estado "Labeled" o "Etiquetado"
3. Verifica que el conteo sea correcto (21 documentos en tu caso)

**Nota sobre la estructura de carpetas**:
- Google Cloud Workbench busca los PDFs en la ruta que especifiques
- Automáticamente busca archivos JSON con el mismo nombre en la carpeta `json/`
- **Ejemplo**: 
  - PDF: `gs://.../training-pdfs/20251126230000/factura1.pdf`
  - JSON: `gs://.../json/factura1.json` (se encuentra automáticamente)

---

### Paso 5: Entrenar Modelo (Google Cloud Console)

**Este paso también es manual en Google Cloud**

#### 5.1 Iniciar Training

1. En Workbench, verificar que tienes:
   - Mínimo 20 documentos importados
   - Distribution: 80/20 (training/test)
2. Click en **"Train"**
3. Configurar:
   - **Training budget**: 1-4 horas
   - **Model version**: Nueva versión
4. Click **"Start Training"**

#### 5.2 Esperar Training

- ⏱**Duración**: 1-3 horas típicamente
- **Progreso**: Visible en Google Cloud Console
- **Notificación**: Email cuando termine

#### 5.3 Validar Resultados

1. Ver métricas de precision/recall
2. Probar con documentos de test
3. Si está bien → Desplegar a producción
4. Si está mal → Agregar más documentos y re-entrenar

---

## Panel de Estadísticas Explicado

### Total Documents
- **Qué es**: Total de PDFs subidos
- **Acción**: Ninguna

### Annotated
- **Qué es**: Documentos con anotaciones completas
- **Meta**: ≥ 20 para entrenar
- **Acción**: Anotar más documentos

### ☁PDFs in GCS
- **Qué es**: PDFs ya subidos a Google Cloud
- **Optimización**: No se suben de nuevo (ahorra tiempo y $)
- **Acción**: Ninguna

###  JSON Generated
- **Qué es**: JSONs con anotaciones ya en GCS
- **Estado**: Listos para importar en Workbench
- **Acción**: Importar en Google Cloud Console

###  Pending JSON
- **Qué es**: Documentos anotados sin JSON generado
- **Acción**: Click "Generate JSON Files"

### Training Status
- **Listo**: ≥ 20 anotados
- **Pendiente**: Muestra cuántos faltan

---

## Optimizaciones Implementadas

### 1. **Control de Subidas Duplicadas** 
- Los PDFs ya subidos a GCS NO se suben de nuevo
- Se guarda `gcs_pdf_uri` en base de datos
- Ahorra bandwidth y tiempo

### 2. **Procesamiento en Background** 
- Generación de JSON usa Laravel Jobs
- No bloquea la interfaz de usuario
- Progreso visible en tiempo real

### 3. **Tracking de Estado** 
- Saber qué documentos están en cada etapa
- Badges visuales: "Annotated", "En GCS", "JSON"
- Estadísticas actualizadas en tiempo real

### 4. **Mapeo Inteligente de Texto** 
- Busca cada valor en el texto OCR
- Genera `textAnchor` con posición exacta
- Permite a Document AI aprender ubicaciones

---

## Configuración Técnica

### Base de Datos

**Tabla**: `document_ai_training_documents`

```sql
- id: bigint
- filename: varchar
- file_path: varchar (storage/app/training-documents/)
- extracted_data: json (OCR + entities)
- annotations: json (valores manuales)
- is_annotated: boolean
- annotated_at: timestamp
- confidence: decimal
- document_type: enum(sale,purchase,unknown)
- type_auto_detected: boolean
- imported_at: timestamp (cuándo se generó JSON)
- json_file_path: varchar (ruta en GCS)
- gcs_pdf_uri: varchar (gs://bucket/path/file.pdf)
- pdf_uploaded_at: timestamp
```

### Google Cloud Storage

**Bucket**: `docucenter-aci-document-ai-training`

**Estructura**:
```
gs://docucenter-aci-document-ai-training/
├── training-pdfs/
│   └── 20251126230000/
│       ├── factura1.pdf
│       ├── factura2.pdf
│       └── ...
└── json/
    ├── factura1.json
    ├── factura2.json
    └── ...
```

### Document AI Processor

- **Project**: `docucenter-aci`
- **Processor Type**: Custom Document Extractor
- **Processor ID**: `d6eea813b0c73fc8`
- **Location**: `us`

### Laravel Jobs

**Job**: `GenerateDocumentAIJSONFilesJob`
- **Queue**: `default`
- **Timeout**: 3600 segundos (1 hora)
- **Tracking**: Cache con key `json_generation_status_{sessionId}`

**Para ejecutar manualmente**:
```bash
php artisan queue:work redis --timeout=3600 --tries=3
```

---

## Troubleshooting

### Problema: "No hay documentos pendientes de generar JSON"

**Causa**: Todos los documentos anotados ya tienen JSON generado

**Solución**: 
1. Anotar más documentos
2. O re-generar borrando campo `imported_at` en BD

---

### Problema: "PDF no encontrado en GCS"

**Causa**: El PDF no existe en ninguna ruta conocida

**Solución**:
1. Verificar que `file_path` en BD sea correcto
2. Verificar que archivo existe en `storage/app/training-documents/`
3. El Job lo subirá automáticamente si existe localmente

---

### Problema: Job no procesa

**Causa**: Queue worker no está corriendo

**Solución**:
```bash
# En Docker
docker exec -it docucenter_laravel.test php artisan queue:work redis

# O verificar que Supervisor esté corriendo
docker exec -it docucenter_laravel.test supervisorctl status
```

---

### Problema: "Valor no encontrado en OCR"

**Causa**: El valor anotado no coincide exactamente con el texto OCR

**Solución**:
1. Verificar que la anotación sea igual al texto en el PDF
2. Revisar espacios, mayúsculas, caracteres especiales
3. El sistema busca coincidencia exacta

---

## Comandos Útiles

### Generar JSONs por línea de comandos

```bash
# Dentro del contenedor Docker
php artisan docai:generate-json-files

# Ver logs
tail -f storage/logs/laravel.log
```

### Limpiar cache

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Verificar Jobs en cola

```bash
php artisan queue:listen --queue=default
```

---

## Workflow Resumido (TL;DR)

```
1. DocuCenter: Subir PDFs ➜ Anotar campos
                           ⬇
2. DocuCenter: Click "Generate JSON Files"
                           ⬇
3. Job en background: PDF → OCR → JSON → GCS
                           ⬇
4. Google Cloud Workbench: Import JSON files
                           ⬇
5. Google Cloud Console: Train model (1-3 hrs)
                           ⬇
6. Modelo entrenado listo para producción
```

---

## Referencias Técnicas

### Documentación Google Document AI
- [Custom Extractors](https://cloud.google.com/document-ai/docs/custom-extractor)
- [Document Proto Format](https://cloud.google.com/document-ai/docs/document-proto)
- [Training Guide](https://cloud.google.com/document-ai/docs/train-model)

### Artículos Clave
- [Medium: Neil Kolban - Pre-labeled Import Method](https://medium.com/@neilkolban/google-document-ai-pre-labeled-data-import-bb0f9c9e3d2a)

### Código Fuente
- **Livewire Component**: `app/Http/Livewire/Admin/DocumentAI/TrainingManager.php`
- **Job**: `app/Jobs/GenerateDocumentAIJSONFilesJob.php`
- **Service**: `app/Services/DocumentAIService.php`
- **Modelo**: `app/Models/DocumentAITrainingDocument.php`
- **Vista**: `resources/views/livewire/admin/document-ai/training-manager.blade.php`

---

## Funcionalidades Destacadas

### Lo que el sistema HACE automáticamente:
- Extracción inicial con OCR
- Detección automática de tipo de documento
- Pre-llenado de campos en anotación
- Búsqueda y mapeo de texto en OCR
- Generación de textAnchor positions
- Subida a GCS (PDFs + JSONs)
- Tracking de estado
- Control de duplicados

### Lo que debes hacer MANUALMENTE:
- Revisar y corregir anotaciones
- Importar JSONs en Google Cloud Workbench
- Iniciar training del modelo
- Desplegar a producción

---

## 👥 Equipo y Soporte

**Desarrollador**: Sistema implementado en DocuCenter
**Fecha**: Noviembre 2025
**Versión Laravel**: 9.x
**Versión PHP**: 8.1+

**Para soporte técnico**:
1. Revisar logs en `storage/logs/laravel.log`
2. Verificar estado de Jobs en base de datos tabla `jobs`
3. Consultar documentación de Google Document AI

---

## Próximos Pasos Sugeridos

1. **Entrenar modelo inicial** con 20-50 documentos
2. **Evaluar precisión** con documentos de test
3. **Iterar**: Agregar más documentos donde el modelo falle
4. **Re-entrenar** para mejorar precisión
5. **Desplegar a producción** cuando precision > 90%

---

**¡Éxito entrenando tu modelo! **
