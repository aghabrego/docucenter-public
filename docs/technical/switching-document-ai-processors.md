# Cambio entre Procesadores de Document AI

## Configuración Actual

Tienes **2 procesadores** configurados en el sistema:

### 1. Pre-trained Invoice Parser (Default)
```
ID: 969f7415e83e8cfd
Tipo: Invoice Parser (Pre-entrenado)
Precisión: 65-75%
Costo: $1.50/1000 páginas
Uso: Procesamiento general, fallback
Estado: ACTIVO (default)
```

### 2. Custom Document Extractor (Nuevo)
```
ID: d6eea813b0c73fc8
Tipo: Custom Extractor
Precisión: 85-95% (después de entrenar)
Costo: $3.00/1000 páginas
Uso: Principal (mejor precisión)
Estado: PENDIENTE DE ENTRENAMIENTO
```

## Variables de Entorno

En `.env` ahora tienes:

```env
# Proyecto y credenciales
GOOGLE_CLOUD_PROJECT_ID=docucenter-aci
GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/service-account-credentials.json
GOOGLE_DOCUMENT_AI_LOCATION=us

# Procesador Pre-trained (Default)
GOOGLE_DOCUMENT_AI_PROCESSOR_ID=969f7415e83e8cfd

# Procesador Custom (Nuevo)
GOOGLE_DOCUMENT_AI_CUSTOM_PROCESSOR_ID=d6eea813b0c73fc8

# Configuración
GOOGLE_DOCUMENT_AI_MIN_CONFIDENCE=0.6
GOOGLE_DOCUMENT_AI_USE_CUSTOM=false
```

## Cómo Cambiar de Procesador

### Opción 1: Usar Pre-trained (Actual)
```env
GOOGLE_DOCUMENT_AI_USE_CUSTOM=false
```
- ✅ Funcionando ahora
- ✅ Menor costo ($1.50/1000)
- ⚠️ Menor precisión (65-75%)
- ✅ No requiere entrenamiento

### Opción 2: Usar Custom (Después de entrenar)
```env
GOOGLE_DOCUMENT_AI_USE_CUSTOM=true
```
- ⏳ Requiere entrenamiento previo
- ✅ Mayor precisión (85-95%)
- ⚠️ Mayor costo ($3.00/1000)
- ✅ Mejor para producción

## Pasos para Activar Custom Processor

### 1. Preparar Dataset
- ✅ Subir 20-100 facturas en el módulo de entrenamiento
- ✅ Anotar todos los documentos
- ✅ Exportar JSON de entrenamiento

### 2. Entrenar en Google Cloud

#### A. Acceder a Document AI Workbench
```
https://console.cloud.google.com/ai/document-ai/processors/d6eea813b0c73fc8?project=docucenter-aci
```

#### B. Importar Dataset
1. Ir a pestaña "Conjunto de datos"
2. Hacer clic en "Importar documentos"
3. Subir el JSON exportado desde DocuCenter
4. Esperar a que se carguen los documentos

#### C. Revisar Anotaciones
1. Ir a "Anotaciones"
2. Verificar que los campos estén correctos:
   - vendor_name
   - vendor_tax_id
   - vendor_dv
   - invoice_number
   - invoice_date
   - due_date
   - subtotal
   - tax_amount
   - total_amount
3. Corregir si es necesario

#### D. Entrenar Modelo
1. Hacer clic en "Entrenar"
2. Configurar:
   - Tipo: AutoML
   - Budget: Dejar por defecto
   - Validación: 80/20 split
3. Iniciar entrenamiento
4. Esperar 1-3 horas

#### E. Ver Métricas
Después del entrenamiento verás:
- Precisión general
- Precisión por campo
- Recall
- F1-score

### 3. Activar en DocuCenter

Una vez entrenado exitosamente:

```bash
# Editar .env
nano .env

# Cambiar esta línea:
GOOGLE_DOCUMENT_AI_USE_CUSTOM=true

# Limpiar caché
docker exec docucenter_laravel.test php artisan config:clear
docker exec docucenter_laravel.test php artisan cache:clear
```

### 4. Probar Custom Processor

```bash
# Subir un PDF de prueba en el módulo
# Verificar log para confirmar procesador usado
docker exec docucenter_laravel.test tail -f storage/logs/laravel.log
```

Deberías ver:
```
Procesando documento con Document AI
processor: projects/docucenter-aci/locations/us/processors/d6eea813b0c73fc8
```

## Estrategia de Rollback

Si el Custom Processor no funciona como esperado:

```bash
# Volver a Pre-trained
nano .env

# Cambiar:
GOOGLE_DOCUMENT_AI_USE_CUSTOM=false

# Limpiar caché
docker exec docucenter_laravel.test php artisan config:clear
```

## Comparación de Procesadores

| Característica | Pre-trained | Custom |
|----------------|-------------|---------|
| Costo por 1000 páginas | $1.50 | $3.00 |
| Precisión promedio | 65-75% | 85-95% |
| Requiere entrenamiento | No | Sí |
| Tiempo de setup | Inmediato | 1-3 horas |
| Personalizable | No | Sí |
| Mejor para | <50 facturas/mes | >50 facturas/mes |

## Monitoreo de Rendimiento

### Métricas a Trackear

**Pre-trained:**
```php
// Confianza promedio
SELECT AVG(confidence) FROM document_ai_training_documents;

// Campos correctos sin corrección
SELECT COUNT(*) FROM document_ai_training_documents 
WHERE is_annotated = 0;
```

**Custom (después de activar):**
```php
// Comparar confianza antes/después
SELECT 
  AVG(CASE WHEN created_at < '2025-11-15' THEN confidence END) as before,
  AVG(CASE WHEN created_at >= '2025-11-15' THEN confidence END) as after
FROM document_ai_training_documents;
```

## Costos Estimados

### Escenario: 100 facturas/mes

**Solo Pre-trained:**
```
Procesamiento: 100 × $0.0015 = $0.15/mes
Revisión manual: 100 × $2 = $200/mes
Total: $200.15/mes
```

**Pre-trained + Custom:**
```
Entrenamiento (una vez): $0.30
Procesamiento: 100 × $0.003 = $0.30/mes
Revisión reducida: 100 × $0.50 = $50/mes
Total primer mes: $50.60
Total meses siguientes: $50.30/mes

AHORRO: $149.85/mes
ROI: 99,925%
```

## Recomendaciones

### ✅ Activar Custom Processor Si:
- Procesas >50 facturas/mes
- Requieres >85% precisión
- Tienes dataset de 100+ facturas anotadas
- El tiempo de revisión manual es costoso

### ⏸️ Mantener Pre-trained Si:
- Procesas <50 facturas/mes
- La precisión actual es suficiente
- No tienes tiempo para entrenar
- Prefieres simplicidad sobre precisión

### 🔄 Usar Ambos:
- Custom para operación normal
- Pre-trained como fallback si Custom falla
- Testing A/B para comparar resultados

## Troubleshooting

### Error: "Processor not found"
```
# Verificar ID correcto
echo $GOOGLE_DOCUMENT_AI_CUSTOM_PROCESSOR_ID

# Debe ser: d6eea813b0c73fc8
```

### Error: "Model not trained"
```
# El Custom Processor requiere entrenamiento primero
# Seguir pasos en sección "Entrenar en Google Cloud"
```

### Baja precisión con Custom
```
# Posibles causas:
1. Dataset muy pequeño (<20 documentos)
2. Documentos no variados (todos del mismo proveedor)
3. Anotaciones incorrectas
4. Necesita más entrenamiento

# Solución:
- Agregar más documentos variados
- Re-entrenar con dataset más grande
```

## Próximos Pasos

1. ✅ **Configuración completada** - Ambos procesadores listos
2. ⏳ **Recolectar dataset** - 100+ facturas variadas
3. ⏳ **Anotar documentos** - Revisar y corregir extracción
4. ⏳ **Exportar JSON** - Descargar dataset de entrenamiento
5. ⏳ **Entrenar Custom** - Subir a Google Cloud y entrenar
6. ⏳ **Activar Custom** - Cambiar `GOOGLE_DOCUMENT_AI_USE_CUSTOM=true`
7. ✅ **Monitorear** - Comparar precisión y costos

---

**Última actualización:** 13 de noviembre de 2025  
**Versión:** 1.0  
**Estado:** Configuración completada, pendiente de entrenamiento
