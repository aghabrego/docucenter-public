# Document AI - Importación a Workbench

## Descripción General

El sistema de importación a Workbench permite cargar documentos anotados desde DocuCenter directamente a Google Cloud Document AI Workbench, donde Google se encarga del procesamiento OCR y validación de formato.

## Ventajas de Este Enfoque

1. **Formato Validado**: Google maneja internamente el formato de datos
2. **OCR Automático**: Workbench extrae el texto automáticamente
3. **Interfaz de Anotación**: Permite revisar/corregir anotaciones en Workbench
4. **División Automática**: Separa automáticamente datos de entrenamiento (80%) y prueba (20%)

## Arquitectura

```
DocuCenter Panel → Upload PDFs → GCS Bucket → Import API → Workbench
                                                                ↓
                                                           Entrenamiento
```

### Componentes

1. **DocumentAIService.php**
   - `importToWorkbench()`: Sube PDFs y llama a Import API
   - `checkImportStatus()`: Monitorea el estado de la operación
   - `uploadPDFsToGCS()`: Sube archivos a Cloud Storage

2. **TrainingManager.php (Livewire)**
   - `importToWorkbench()`: Validaciones y llamada al servicio
   - `checkImportStatus()`: Polling del estado de importación
   - UI para botón "Import to Workbench"

3. **training-manager.blade.php**
   - Botón "Importar a Workbench"
   - Estados de progreso de importación
   - Mensajes de éxito/error

## Requisitos

### Mínimos
- 20+ documentos anotados
- GCS bucket configurado: `docucenter-aci-document-ai-training`
- Custom Extractor creado en Document AI
- Credenciales de servicio con permisos:
  - `documentai.datasets.importDocuments`
  - `storage.objects.create`

### Recomendados
- 100+ documentos para mejor precisión
- Validación de anotaciones antes de importar
- Tipos de documento configurados (purchase/sale)

## Proceso de Importación

### 1. Preparación de Documentos

```bash
# Verificar documentos anotados
docker exec -it docucenter-app-1 php artisan documentai:test-import --org-id=1
```

### 2. Desde el Panel

1. Acceder a `/admin/sage50/document-ai-training`
2. Verificar que hay 20+ documentos anotados (tarjeta verde "Ready")
3. Click en botón "Importar a Workbench"
4. Monitorear progreso en la alerta de estado

### 3. Desde Línea de Comando

```bash
# Prueba completa con monitoreo
docker exec -it docucenter-app-1 php artisan documentai:test-import --org-id=1

# Verificar en Google Cloud Console
# https://console.cloud.google.com/ai/document-ai/processors?project=docucenter-aci
```

## API Reference

### DocumentAIService::importToWorkbench()

```php
/**
 * Importa documentos anotados a Google Cloud Workbench
 *
 * @param array $documents Array de documentos con estructura:
 *   [
 *     'id' => int,
 *     'filename' => string,
 *     'pdf_path' => string,
 *     'gcs_uri' => string|null,
 *     'annotations' => array,
 *     'document_type' => string
 *   ]
 * @param float $trainingSplitRatio Ratio para training/test (default: 0.8)
 * @return array ['operation_name' => string]
 * @throws \RuntimeException Si falla la importación
 */
public function importToWorkbench(array $documents, float $trainingSplitRatio = 0.8): array
```

### DocumentAIService::checkImportStatus()

```php
/**
 * Verifica estado de operación de importación
 *
 * @param string $operationName Nombre de operación de Import API
 * @return array [
 *   'done' => bool,
 *   'error' => array|null,
 *   'metadata' => array|null
 * ]
 * @throws \RuntimeException Si falla la consulta
 */
public function checkImportStatus(string $operationName): array
```

## Estructura de Request a Import API

```json
{
  "batchDocumentsImportConfigs": [
    {
      "batchInputConfig": {
        "gcsDocuments": {
          "documents": [
            {
              "gcsUri": "gs://docucenter-aci-document-ai-training/training_doc_1.pdf",
              "mimeType": "application/pdf"
            }
          ]
        }
      },
      "autoSplitConfig": {
        "trainingSplitRatio": 0.8
      }
    }
  ]
}
```

## Estructura de Response

### Inicio de Importación (202 Accepted)

```json
{
  "name": "projects/123/locations/us/operations/456",
  "metadata": {
    "@type": "type.googleapis.com/google.cloud.documentai.v1beta3.ImportDocumentsMetadata",
    "state": "RUNNING",
    "createTime": "2025-01-15T10:00:00Z"
  }
}
```

### Estado de Operación (200 OK)

```json
{
  "name": "projects/123/locations/us/operations/456",
  "metadata": {
    "state": "SUCCEEDED",
    "totalDocumentCount": 21,
    "importedDocumentCount": 21
  },
  "done": true,
  "response": {
    "@type": "type.googleapis.com/google.cloud.documentai.v1beta3.ImportDocumentsResponse"
  }
}
```

## Estados de Importación

| Estado | Descripción | Acción UI |
|--------|-------------|-----------|
| `RUNNING` | Importación en progreso | Mostrar spinner y progreso |
| `SUCCEEDED` | Completado exitosamente | Mensaje de éxito |
| `FAILED` | Error en importación | Mostrar error y detalles |
| `CANCELLED` | Cancelado por usuario | Mostrar mensaje informativo |

## Flujo en UI

```
[Botón: Importar a Workbench]
         ↓
   Validar 20+ docs
         ↓
   Subir PDFs a GCS
         ↓
   Llamar Import API
         ↓
   Guardar operation_name en sesión
         ↓
   Polling cada 30s (wire:poll)
         ↓
   [Alerta de Estado]
   - En progreso: Azul con spinner
   - Completado: Verde con check
   - Error: Rojo con detalles
```

## Troubleshooting

### Error: "Processor not found"

**Causa**: Processor ID no configurado o incorrecto

**Solución**:
```bash
# Verificar configuración
grep DOCUMENT_AI_CUSTOM_PROCESSOR_ID .env

# Debe ser: d6eea813b0c73fc8
```

### Error: "Insufficient permissions"

**Causa**: Service account sin permisos necesarios

**Solución**:
```bash
# Verificar permisos en Google Cloud Console
# IAM & Admin → Service Accounts
# Debe tener: Document AI API User + Storage Admin
```

### Error: "GCS bucket not accessible"

**Causa**: Bucket no existe o sin permisos

**Solución**:
```bash
# Verificar bucket
gsutil ls gs://docucenter-aci-document-ai-training/

# Verificar permisos
gsutil iam get gs://docucenter-aci-document-ai-training/
```

### Importación se queda en "RUNNING"

**Causa**: Importación puede tomar varios minutos

**Acciones**:
1. Esperar 5-10 minutos
2. Verificar en Google Cloud Console
3. Revisar logs de la operación

## Próximos Pasos Después de Importación

1. **Verificar en Workbench**
   - Ir a Google Cloud Console
   - Document AI → Custom Extractors → Ver dataset
   - Confirmar que aparecen los 21 documentos

2. **Revisar Anotaciones**
   - Workbench muestra preview de campos extraídos
   - Corregir si es necesario
   - Validar división training/test (80/20)

3. **Iniciar Entrenamiento**
   - Desde Workbench UI: Click "Train New Version"
   - O programáticamente: `trainProcessorVersion` API

4. **Monitorear Entrenamiento**
   - Puede tomar 30-60 minutos
   - Verificar métricas de precisión
   - Evaluar con datos de prueba

5. **Deploy**
   - Una vez completado el entrenamiento
   - Deploy nueva versión a producción
   - Actualizar configuración en DocuCenter

## Logs y Debug

### Logs de Importación

```bash
# Ver logs del servicio
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "import"

# Logs específicos de Import API
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "importDocuments"
```

### Debug en Panel

El panel muestra información de debug cuando hay una operación activa:
- Operation name almacenado en sesión
- Estado actual de `$trainingStatus`
- Flag `$isTraining`

## Recursos Adicionales

- [Document AI Import API Documentation](https://cloud.google.com/document-ai/docs/reference/rest/v1beta3/projects.locations.processors.dataset/importDocuments)
- [Workbench UI Guide](https://cloud.google.com/document-ai/docs/workbench)
- [Training Best Practices](https://cloud.google.com/document-ai/docs/custom-classifier#best_practices)

## Changelog

### 2025-01-15
- Implementación inicial de Import API
- Métodos `importToWorkbench()` y `checkImportStatus()`
- UI con botón "Importar a Workbench"
- Comando de testing `documentai:test-import`
- Documentación completa del proceso
