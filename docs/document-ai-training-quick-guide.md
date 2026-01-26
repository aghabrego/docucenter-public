# Guía Rápida: Entrenar Document AI con Documentos Anotados

## Estado Actual 

**20 documentos JSON con anotaciones** listos en GCS:
```
gs://docucenter-aci-document-ai-training/json/
 document-1.json
 document-2.json
 ...
 document-20.json
```

Cada JSON contiene:
- OCR completo del PDF
- Imagen Base64 (PNG)
- 4-8 entities anotadas por documento
- textAnchors con posiciones exactas

## Próximos Pasos (Manual)

### 1. Importar a Workbench (5 minutos)

1. Ir a [Document AI Console](https://console.cloud.google.com/ai/document-ai/workbench)

2. Seleccionar proyecto: `docucenter-aci`

3. Seleccionar processor: **Custom Extractor - Compras Panamá** (`d6eea813b0c73fc8`)

4. Click en **"IMPORT DOCUMENTS"**

5. Configurar importación:
   ```
   Source: Cloud Storage
   Path: gs://docucenter-aci-document-ai-training/json/
   Dataset split: TRAINING
   Import with auto-labeling: NO (ya están anotados)
   ```

6. Click **"IMPORT"**

### 2. Monitorear Importación (10-15 minutos)

La importación es asíncrona. Puedes:

**Opción A: Ver en Console**
- Ir a "Operations" en el menú lateral
- Buscar operación "ImportProcessorVersion"
- Ver progreso: "X of 20 documents processed"

**Opción B: Usar API (programático)**
```bash
docker exec -it docucenter_laravel.test php artisan docai:check-import-status <operation_name>
```

### 3. Distribuir Dataset (5 minutos)

Una vez importados todos:

1. En Workbench, ir a **"DOCUMENTS"**
2. Verificar que aparecen 20 documentos
3. Seleccionar 10 documentos → Mover a **TRAINING**
4. Seleccionar 10 documentos → Mover a **TESTING**

**Distribución recomendada**:
- Training: 10-12 documentos (50-60%)
- Testing: 8-10 documentos (40-50%)

### 4. Revisar Anotaciones (10-15 minutos)

**IMPORTANTE**: Verificar manualmente algunas anotaciones

1. Click en un documento
2. Verificar que las entities estén correctamente marcadas:
   - `vendor_name`: Nombre del proveedor
   - `vendor_tax_id`: RUC
   - `invoice_number`: Número de factura
   - `invoice_date`: Fecha
   - `subtotal`, `tax_amount`, `total_amount`: Montos
3. Corregir si es necesario (drag & drop en la UI)
4. Repetir con 3-5 documentos aleatorios

### 5. Entrenar Modelo (5 minutos + 30-60 min espera)

1. Click en **"TRAIN NEW VERSION"**

2. Configurar:
   ```
   Training method: Standard training
   Training set: TRAINING (10+ docs)
   Test set: TESTING (10 docs)
   ```

3. Click **"START TRAINING"**

4. **Esperar 30-60 minutos** (Google procesa automáticamente)

### 6. Evaluar Resultados (10 minutos)

Una vez completado el entrenamiento:

1. Ir a **"VERSIONS"**
2. Ver la nueva versión entrenada
3. Click para ver **métricas**:
   - **Precision**: % de predictions correctas
   - **Recall**: % de valores detectados
   - **F1 Score**: Promedio armónico
4. Revisar por entity type:
   ```
   vendor_name: precision=0.95, recall=0.90, f1=0.92
   invoice_number: precision=0.98, recall=0.95, f1=0.96
   total_amount: precision=0.85, recall=0.80, f1=0.82
   ```

**Métricas Aceptables**:
- **Excelente**: F1 > 0.90
- **Bueno**: F1 > 0.80
- **Insuficiente**: F1 < 0.80 (necesita más datos/ajustes)

### 7. Deploy del Modelo (2 minutos)

Si las métricas son buenas:

1. Click en la versión entrenada
2. Click **"DEPLOY"**
3. Esperar ~5 minutos
4. Estado cambiará a **"DEPLOYED"**

### 8. Probar en Producción (5 minutos)

```bash
# Probar con un PDF nuevo
docker exec -it docucenter_laravel.test php artisan tinker

# En tinker:
$service = app(\App\Services\DocumentAIService::class);
$pdf = file_get_contents('/path/to/test.pdf');
$result = $service->processInvoice($pdf);
print_r($result['entities']);
```

## Comandos Útiles

### Generar más documentos JSON
```bash
# Generar todos los documentos anotados
docker exec -it docucenter_laravel.test php artisan docai:generate-json-files

# Generar solo 10
docker exec -it docucenter_laravel.test php artisan docai:generate-json-files --limit=10
```

### Ver archivos en GCS
```bash
# Listar JSON files
gsutil ls gs://docucenter-aci-document-ai-training/json/

# Ver contenido de un JSON
gsutil cat gs://docucenter-aci-document-ai-training/json/document-1.json | jq .

# Ver resumen
gsutil cat gs://docucenter-aci-document-ai-training/json/document-1.json | jq '{
  uri,
  entities: .entities | map({type, mentionText})
}'
```

### Verificar importación
```bash
# Desde el panel de Livewire
# Ir a: /admin/sage50/document-ai-training
# Section: "Estado de Importación"
# Ver operation name y status
```

## Troubleshooting

### Problema: Importación falla

**Verificar**:
1. Los JSON files existen en GCS
2. Los PDFs referenciados en `uri` existen
3. El formato JSON es correcto (usar `jq` para validar)

**Solución**:
```bash
# Validar JSON
gsutil cat gs://docucenter-aci-document-ai-training/json/document-1.json | jq . > /dev/null
# Si hay error, el JSON está malformado
```

### Problema: Entities no aparecen en Workbench

**Causa**: textAnchors incorrectos o fuera de rango

**Solución**: Re-generar JSON files:
```bash
docker exec -it docucenter_laravel.test php artisan docai:generate-json-files
```

### Problema: Métricas bajas después de entrenar

**Causas posibles**:
1. Pocas muestras de entrenamiento (< 10)
2. Anotaciones inconsistentes
3. Variabilidad alta en formatos de documento

**Soluciones**:
1. Agregar más documentos anotados (objetivo: 30-50)
2. Revisar y corregir anotaciones manualmente
3. Agrupar documentos por tipo/formato

## Timeline Estimado

| Tarea | Tiempo | Tipo |
|-------|--------|------|
| Importar a Workbench | 5 min | Manual |
| Esperar importación | 10-15 min | Automático |
| Distribuir dataset | 5 min | Manual |
| Revisar anotaciones | 10-15 min | Manual |
| Iniciar entrenamiento | 5 min | Manual |
| **Esperar entrenamiento** | **30-60 min** | **Automático** |
| Evaluar resultados | 10 min | Manual |
| Deploy modelo | 2 min | Manual |
| Esperar deploy | 5 min | Automático |
| Probar en producción | 5 min | Manual |
| **TOTAL** | **~90-120 min** | |

**Tiempo activo**: ~45 minutos  
**Tiempo de espera**: ~45-75 minutos

## Checklist Final

Antes de dar por terminado el proceso:

- [ ] 20 documentos importados en Workbench
- [ ] 10+ documentos en TRAINING dataset
- [ ] 10 documentos en TESTING dataset
- [ ] Anotaciones revisadas manualmente (sample de 5)
- [ ] Modelo entrenado con versión nueva
- [ ] Métricas evaluadas (F1 > 0.80 para entities principales)
- [ ] Modelo deployado en processor
- [ ] Prueba exitosa con PDF nuevo en producción

## Referencias Rápidas

- **Console**: https://console.cloud.google.com/ai/document-ai/workbench
- **Processor ID**: `d6eea813b0c73fc8`
- **Bucket GCS**: `docucenter-aci-document-ai-training`
- **JSON Path**: `gs://docucenter-aci-document-ai-training/json/`
- **Panel Admin**: https://docucenter.test/admin/sage50/document-ai-training

## Siguientes Iteraciones

Para mejorar el modelo en el futuro:

1. **Agregar más documentos** (30-50 total)
   - Más variedad de proveedores
   - Diferentes formatos de factura
   - Casos edge (facturas con descuentos, impuestos especiales, etc.)

2. **Mejorar anotaciones**
   - Agregar `line_items` individuales
   - Incluir `payment_method`
   - Agregar `due_date` cuando esté disponible

3. **Automatizar re-entrenamiento**
   - Generar JSON files cada semana
   - Importar incrementalmente
   - Re-entrenar modelo mensualmente
   - Comparar métricas entre versiones

4. **Monitorear en producción**
   - Log de confidence scores
   - Revisar casos con baja confidence
   - Agregar esos casos al dataset de entrenamiento
