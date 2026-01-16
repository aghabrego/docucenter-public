# Módulo de Entrenamiento Document AI - Guía de Uso

## Descripción General

El **Módulo de Entrenamiento Document AI** permite a los usuarios de DocuCenter entrenar y mejorar el sistema de extracción de datos desde la aplicación web, sin necesidad de acceder directamente a Google Cloud Console.

## Características Principales

### 1. Gestión de Documentos de Entrenamiento
- Subir múltiples PDFs de facturas reales
- Ver estado de anotación (Anotado/Pendiente)
- Visualizar confianza de extracción automática
- Eliminar documentos no deseados

### 2. Sistema de Anotación Visual
- Vista previa del PDF lado a lado con formulario de anotación
- Pre-llenado con datos extraídos automáticamente
- Corrección manual de campos incorrectos
- Validación de campos obligatorios

### 3. Exportación de Datos
- Exporta conjunto de datos en formato JSON
- Mínimo 20 documentos anotados requeridos
- Incluye PDFs originales + anotaciones correctas
- Listo para importar en Google Document AI Workbench

### 4. Panel de Estadísticas
- Total de documentos subidos
- Documentos anotados vs pendientes
- Indicador de preparación para entrenamiento
- Progreso visual del dataset

## Acceso al Módulo

**Ruta:** `/admin/sage50/document-ai-training`

**Nombre de ruta:** `admin.sage50.document_ai_training`

**Middleware aplicado:**
- `auth` - Usuario autenticado
- `2fa` - Autenticación de dos factores
- `dynamicAcl` - Control de acceso
- `check.active.organization` - Organización activa

## Estructura de Base de Datos

### Tabla: `document_ai_training_documents`

```sql
CREATE TABLE document_ai_training_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    extracted_data JSON NULL,
    annotations JSON NULL,
    is_annotated BOOLEAN DEFAULT FALSE,
    annotated_at TIMESTAMP NULL,
    confidence DECIMAL(5,3) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Campos:**
- `filename`: Nombre original del PDF subido
- `file_path`: Ruta de almacenamiento en `storage/app/training-documents/`
- `extracted_data`: Datos extraídos automáticamente por Document AI (JSON)
- `annotations`: Datos corregidos manualmente por el usuario (JSON)
- `is_annotated`: Indica si el documento ha sido anotado
- `annotated_at`: Fecha de anotación
- `confidence`: Nivel de confianza de la extracción automática (0-1)

## Flujo de Trabajo

### Paso 1: Subir Documentos

1. Acceder al módulo desde el menú de administración
2. Seleccionar uno o varios PDFs de facturas reales
3. Hacer clic en "Subir Documentos"
4. El sistema automáticamente:
   - Guarda el PDF en `storage/app/training-documents/`
   - Procesa el documento con Document AI
   - Extrae datos automáticamente
   - Calcula nivel de confianza
   - Crea registro en base de datos

**Restricciones:**
- Formato: Solo PDFs
- Tamaño máximo: 12MB por archivo
- Cantidad: Sin límite por carga

### Paso 2: Anotar Documentos

1. En la lista de documentos, hacer clic en botón "Anotar" (documentos pendientes) o "Ver" (ya anotados)
2. Se abre vista de anotación con:
   - **Lado izquierdo:** Vista previa del PDF
   - **Lado derecho:** Formulario con campos pre-llenados
3. Verificar y corregir cada campo:
   - **Campos obligatorios (*):**
     - Nombre del Proveedor
     - RUC del Proveedor
     - Número de Factura
     - Total
   - **Campos opcionales:**
     - DV (Dígito Verificador)
     - Fecha de Factura
     - Fecha de Vencimiento
     - Subtotal
     - ITBMS (Impuesto)
     - Método de Pago
4. Hacer clic en "Guardar Anotación"
5. El documento se marca como "Anotado" y suma al contador

### Paso 3: Exportar Datos de Entrenamiento

**Requisito:** Mínimo 20 documentos anotados

1. El botón "Exportar Datos de Entrenamiento" se habilita cuando hay ≥20 documentos anotados
2. Hacer clic en el botón verde de exportación
3. Se genera archivo JSON con estructura:
   ```json
   {
     "documents": [
       {
         "file_path": "training-documents/factura1.pdf",
         "annotations": {
           "vendor_name": "EQUIPOS DE COCINA Y PANADERIA, S.A.",
           "vendor_tax_id": "155764420-2-2025",
           "vendor_dv": "19",
           "invoice_number": "60825",
           "invoice_date": "24/10/2025",
           "due_date": "23/11/2025",
           "subtotal": "285.00",
           "tax_amount": "0.00",
           "total_amount": "285.00",
           "payment_method": "CREDITO"
         },
         "extracted_data": { ... }
       }
     ],
     "schema": {
       "vendor_name": "string",
       "vendor_tax_id": "string",
       "vendor_dv": "string",
       "invoice_number": "string",
       "invoice_date": "date",
       "due_date": "date",
       "subtotal": "number",
       "tax_amount": "number",
       "total_amount": "number",
       "payment_method": "string"
     }
   }
   ```
4. El archivo se guarda en `storage/app/exports/training-data-[fecha].json`
5. Se descarga automáticamente al navegador

### Paso 4: Usar Datos en Google Document AI

Con el archivo exportado, puedes:

**Opción A: Custom Document Extractor (Recomendado para producción)**
1. Ir a Google Cloud Console → Document AI → Procesadores
2. Crear nuevo "Custom Document Extractor"
3. Importar JSON exportado
4. Revisar anotaciones en Document AI Workbench
5. Entrenar modelo (1-3 horas)
6. Actualizar processor ID en `.env`:
   ```env
   GOOGLE_DOCUMENT_AI_PROCESSOR_ID=tu-nuevo-processor-id
   ```

**Opción B: Mejorar Post-Processing (Sin costo adicional)**
1. Analizar campos con baja confianza en datos extraídos
2. Identificar patrones comunes en anotaciones correctas
3. Agregar regex patterns en `DocumentAIService::enhanceDataFromText()`
4. No requiere cambios en Google Cloud

## Interfaz de Usuario

### Panel Principal

```
┌─────────────────────────────────────────────────────────────┐
│  Document AI Training Manager                               │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────────┐ │
│  │ Total: 45│  │ Anotados│  │ Pendien- │  │ Listo para  │ │
│  │          │  │    32   │  │ tes: 13  │  │ entrenar ✓  │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────────┘ │
│                                                               │
│  Subir Documentos para Entrenamiento                         │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ [Seleccionar PDFs...]                   [Subir]         │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                               │
│  Documentos de Entrenamiento        [Exportar Datos ↓]       │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ Archivo           │ Fecha    │ Conf. │ Estado │ Acción  │ │
│  ├─────────────────────────────────────────────────────────┤ │
│  │ factura001.pdf   │ 10/11/25 │ 68.5% │ ✓Anotado│ [Ver]  │ │
│  │ factura002.pdf   │ 10/11/25 │ 72.3% │ ⏰Pend.│ [Anotar]│ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Modo Anotación

```
┌──────────────────────────────────────────────────────────────┐
│  Anotación de Campos                                          │
├──────────────────┬───────────────────────────────────────────┤
│ Vista PDF        │  Formulario                                │
│                  │                                            │
│ ┌──────────────┐ │  Nombre del Proveedor *                   │
│ │              │ │  [EQUIPOS DE COCINA Y PANADERIA, S.A.]    │
│ │   FACTURA    │ │                                            │
│ │              │ │  RUC del Proveedor *                       │
│ │   Nro.60825  │ │  [155764420-2-2025                  ]     │
│ │              │ │                                            │
│ │  Total B/.   │ │  DV                                        │
│ │    285.00    │ │  [19]                                      │
│ │              │ │                                            │
│ └──────────────┘ │  Número de Factura *                       │
│                  │  [60825                             ]     │
│                  │                                            │
│                  │  [Cancelar]            [Guardar Anotación]│
└──────────────────┴───────────────────────────────────────────┘
```

## Código de Implementación

### Componente Livewire
**Ubicación:** `app/Http/Livewire/Admin/DocumentAI/TrainingManager.php`

**Métodos principales:**
- `uploadTrainingFiles()` - Procesa PDFs subidos
- `startAnnotation($documentId)` - Inicia modo anotación
- `saveAnnotation()` - Guarda correcciones del usuario
- `exportTrainingData()` - Genera JSON de entrenamiento
- `deleteDocument($documentId)` - Elimina documento

### Modelo
**Ubicación:** `app/Models/DocumentAITrainingDocument.php`

```php
protected $fillable = [
    'filename', 'file_path', 'extracted_data', 
    'annotations', 'is_annotated', 'annotated_at', 'confidence'
];

protected $casts = [
    'extracted_data' => 'array',
    'annotations' => 'array',
    'is_annotated' => 'boolean',
    'annotated_at' => 'datetime',
    'confidence' => 'float'
];
```

### Vista Blade
**Ubicación:** `resources/views/livewire/admin/document-ai/training-manager.blade.php`

## Mejores Prácticas

### Selección de Documentos para Entrenamiento

**✓ Incluir:**
- Facturas de diferentes proveedores (variedad)
- Diferentes formatos y diseños
- Casos que actualmente tienen baja confianza
- Documentos con todos los campos presentes
- Facturas con y sin impuestos
- Diferentes métodos de pago (CONTADO/CRÉDITO)

**✗ Evitar:**
- Documentos escaneados de muy baja calidad
- Facturas dañadas o ilegibles
- PDFs que no son facturas
- Duplicados exactos

### Cantidad Recomendada

| Propósito | Cantidad Mínima | Cantidad Óptima |
|-----------|----------------|-----------------|
| Prueba básica | 20 documentos | 30 documentos |
| Entrenamiento inicial | 50 documentos | 100 documentos |
| Producción | 100 documentos | 200+ documentos |

### Diversidad del Dataset

Para mejores resultados, asegurar:
- **5-10 proveedores diferentes** mínimo
- **3-5 ejemplos por proveedor** para patrones consistentes
- **Mezcla de montos:** Pequeños (<$100), medianos ($100-$1000), grandes (>$1000)
- **Diferentes fechas:** Distribuir a lo largo de varios meses
- **Casos edge:** Incluir facturas con descuentos, notas de crédito, etc.

## Métricas de Calidad

### Confianza de Extracción

| Rango | Interpretación | Acción Recomendada |
|-------|----------------|-------------------|
| > 80% | Excelente | Revisar rápidamente |
| 60-80% | Buena | Verificar campos críticos |
| 40-60% | Regular | Revisar todos los campos |
| < 40% | Baja | Anotar completamente |

### Indicadores de Progreso

El módulo muestra 4 indicadores clave:

1. **Total Documentos:** Cantidad total de PDFs subidos
2. **Anotados:** Documentos revisados y corregidos
3. **Pendientes:** Documentos que requieren anotación
4. **Estado:** Indica si hay suficientes datos para entrenar (≥20)

## Troubleshooting

### Problema: "No se puede subir el archivo"

**Posibles causas:**
- Archivo excede 12MB
- No es formato PDF
- Error de permisos en `storage/app/`

**Solución:**
```bash
# Verificar permisos
docker exec docucenter_laravel.test chmod -R 775 storage/app/
docker exec docucenter_laravel.test chown -R www-data:www-data storage/app/
```

### Problema: "Error al procesar documento con Document AI"

**Posibles causas:**
- Credenciales Google Cloud expiradas
- Límite de cuota excedido
- PDF corrupto

**Solución:**
```bash
# Verificar configuración
docker exec docucenter_laravel.test php artisan config:cache

# Revisar logs
docker exec docucenter_laravel.test tail -f storage/logs/laravel.log
```

### Problema: "Botón de exportar no aparece"

**Causa:** Menos de 20 documentos anotados

**Solución:** Anotar más documentos hasta alcanzar el mínimo requerido

### Problema: "Vista previa de PDF no se muestra en modo anotación"

**Causa:** Configuración de symlink de storage

**Solución:**
```bash
docker exec docucenter_laravel.test php artisan storage:link
```

## Costos y ROI

### Costos de Entrenamiento

**Custom Document Extractor:**
- Setup: $25-50 (una vez)
- Entrenamiento: $3 por 1000 páginas procesadas

**Ejemplo con 100 documentos:**
- Costo setup: $30
- Costo entrenamiento: 100 páginas × $0.003 = $0.30
- **Total inicial: ~$30.30**

**Costo operacional post-entrenamiento:**
- $3 por 1000 facturas procesadas
- vs $1.50 del modelo pre-entrenado
- Diferencia: $1.50 por 1000 facturas

### ROI Esperado

**Mejora de precisión:**
- Pre-trained: 65-75% campos correctos
- Custom trained: 85-95% campos correctos
- **Reducción de corrección manual: 50-60%**

**Ahorro de tiempo:**
- Sin entrenamiento: 2-3 min/factura (revisión completa)
- Con entrenamiento: 30 seg/factura (revisión rápida)
- **Ahorro: 1.5-2.5 min/factura**

**Break-even:**
- Inversión: $30
- Ahorro por factura: $2 (tiempo de personal)
- **Break-even: 15 facturas**

Para operaciones con >100 facturas/mes, el ROI es **inmediato**.

## Roadmap de Mejoras

### Versión 1.1 (Futuro cercano)
- [ ] Anotación de line items (productos individuales)
- [ ] Importación batch de múltiples PDFs vía ZIP
- [ ] Comparación lado a lado: extraído vs anotado
- [ ] Exportación directa a Google Cloud Storage

### Versión 1.2 (Mediano plazo)
- [ ] Validación automática de coherencia (subtotal + tax = total)
- [ ] Sugerencias inteligentes basadas en anotaciones previas
- [ ] Re-entrenamiento automático periódico
- [ ] Métricas de mejora de precisión

### Versión 2.0 (Largo plazo)
- [ ] Integración con Human-in-the-Loop (HITL)
- [ ] Active Learning (selección inteligente de documentos para anotar)
- [ ] Multi-organización (datasets compartidos)
- [ ] API para anotación externa

## Recursos Adicionales

### Documentación Relacionada
- [Guía de Entrenamiento Google Document AI](./google-document-ai-training-guide.md)
- [Opciones de Personalización](./google-document-ai-customization.md)
- [DocumentAIService API](../api/document-ai-service.md)

### Enlaces Externos
- [Google Document AI Documentation](https://cloud.google.com/document-ai/docs)
- [Custom Document Extractor Guide](https://cloud.google.com/document-ai/docs/custom-document-extractor)
- [Document AI Workbench](https://cloud.google.com/document-ai/docs/workbench)

## Soporte

Para problemas o preguntas sobre el módulo de entrenamiento:

1. Revisar logs en `storage/logs/laravel.log`
2. Verificar configuración de Google Cloud
3. Consultar documentación técnica en `docs/technical/`
4. Contactar al equipo de desarrollo

---

**Última actualización:** 13 de noviembre de 2025  
**Versión del módulo:** 1.0  
**Autor:** Equipo DocuCenter
