# Configuración de Google Document AI para DocuCenter

## Descripción

Google Document AI proporciona capacidades avanzadas de extracción de datos de documentos usando modelos de inteligencia artificial pre-entrenados. En DocuCenter, se utiliza específicamente para extraer datos estructurados de facturas PDF en formato panameño.

## Ventajas sobre Cloud Vision OCR

| Característica | Cloud Vision | Document AI |
|---------------|--------------|-------------|
| Tipo | OCR genérico | Parser especializado de facturas |
| Precisión | 85-90% | 98-99% |
| Extracción estructurada | Manual (regex) | Automática |
| Entidades reconocidas | Texto plano | Campos específicos de factura |
| Costo por página | $0.0015 | $0.010 |
| Mantenimiento | Alto (patrones regex) | Bajo (modelo pre-entrenado) |

## Configuración Inicial

### 1. Configurar Google Cloud Project

1. Acceder a [Google Cloud Console](https://console.cloud.google.com)
2. Crear o seleccionar un proyecto existente
3. Habilitar la API de Document AI:
   ```bash
   gcloud services enable documentai.googleapis.com
   ```

### 2. Crear un Invoice Parser Processor

1. Navegar a **Document AI** > **Processors**
2. Hacer clic en **Create Processor**
3. Seleccionar **Invoice Parser** (pre-trained model)
4. Configurar:
   - **Processor name**: `docucenter-invoice-parser`
   - **Region**: `us` (o la región más cercana)
5. Anotar el **Processor ID** y la **Location**

El formato del recurso será:
```
projects/{PROJECT_ID}/locations/{LOCATION}/processors/{PROCESSOR_ID}
```

### 3. Crear Service Account y Credenciales

1. Navegar a **IAM & Admin** > **Service Accounts**
2. Crear una nueva cuenta de servicio:
   - **Name**: `docucenter-document-ai`
   - **Role**: `Document AI API User`
3. Crear una clave JSON:
   - Hacer clic en la cuenta creada
   - **Keys** > **Add Key** > **Create new key** > **JSON**
4. Descargar el archivo JSON y guardarlo como `service-account-credentials.json` en el root del proyecto

### 4. Configurar Variables de Entorno

Agregar al archivo `.env`:

```env
# Google Cloud Document AI
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/service-account-credentials.json
GOOGLE_DOCUMENT_AI_LOCATION=us
GOOGLE_DOCUMENT_AI_PROCESSOR_ID=your-processor-id
GOOGLE_DOCUMENT_AI_MIN_CONFIDENCE=0.7
```

**Importante**: En Docker, la ruta de credenciales debe apuntar al path dentro del contenedor.

### 5. Verificar Permisos del Service Account

Asegurarse de que la cuenta de servicio tenga los siguientes permisos:

- `documentai.processors.processOnline`
- `documentai.processors.get`

## Uso en la Aplicación

### Endpoint de Upload

Los usuarios pueden subir archivos PDF en la pantalla de **Purchases Seats**:
```
admin/sage50/purchases_seats
```

### Flujo de Procesamiento

1. **Upload**: Usuario sube archivo PDF
2. **Detección**: Sistema detecta extensión `.pdf`
3. **Extracción**: Document AI procesa el PDF
4. **Validación**: Verifica campos requeridos y nivel de confianza
5. **Almacenamiento**: Crea registros en `Purchase_Header_Imp` y `Purchase_Detail_Imp`

### Entidades Extraídas

Document AI extrae automáticamente:

| Entidad | Campo en BD | Descripción |
|---------|-------------|-------------|
| `supplier_name` | `VendorName` | Nombre del proveedor |
| `supplier_tax_id` | `VendorID` | RUC del proveedor |
| `invoice_id` | `PurchaseNumber` | Número de factura |
| `invoice_date` | `Date` | Fecha de emisión |
| `due_date` | `DueDate` | Fecha de vencimiento |
| `net_amount` | `Subtotal` | Subtotal sin impuestos |
| `total_tax_amount` | `Tax` | Impuestos totales |
| `total_amount` | `Net_due` | Total a pagar |
| `line_item[]` | `PurchaseDetailImp` | Items individuales |

### Campos de Tracking

Registros creados desde PDF incluyen:

- `Origin`: `'document_ai'`
- `extraction_confidence`: Confianza promedio (0.0 - 1.0)

## Pruebas

### Ejecutar Tests

```bash
docker exec -it docucenter_laravel.test php artisan test --filter DocumentAIServiceTest
```

### Test Manual con PDF de Ejemplo

1. Navegar a `admin/sage50/purchases_seats`
2. Subir un PDF de factura panameña
3. Verificar registro creado en `Purchase_Header_Imp`
4. Revisar logs en `storage/logs/laravel.log`:
   ```bash
   docker exec -it docucenter_laravel.test tail -f storage/logs/laravel.log
   ```

## Validación y Errores

### Validación de Datos Extraídos

El sistema valida automáticamente:

1. **Campos requeridos**: `vendor_name`, `invoice_number`, `total_amount`
2. **Confianza mínima**: 70% (configurable en `.env`)
3. **Formato de datos**: Números, fechas válidas

### Manejo de Errores

Si la extracción falla:
- Se registra en logs con detalles completos
- Se muestra mensaje al usuario
- No se crea registro en base de datos
- Archivo Excel sigue funcionando normalmente

### Logs de Debugging

```bash
# Ver logs en tiempo real
docker exec -it docucenter_laravel.test tail -f storage/logs/laravel.log | grep "Document AI"

# Buscar errores específicos
docker exec -it docucenter_laravel.test grep "Error procesando documento" storage/logs/laravel.log
```

## Limitaciones

### Límites de Document AI

- **Tamaño máximo**: 5MB por documento
- **Páginas máximas**: 15 páginas por documento
- **Formato**: PDF, PNG, JPG, TIFF, GIF
- **Quotas**: 600 requests/min por proyecto

### Consideraciones de Costo

- **Costo base**: $0.010 por página procesada
- **Primeras 1,000 páginas/mes**: Gratis
- Monitorear uso en Google Cloud Console

## Monitoreo

### Verificar Uso

1. Acceder a **Cloud Console** > **Document AI** > **Processors**
2. Seleccionar el processor
3. Ver métricas de uso:
   - Número de requests
   - Latencia promedio
   - Tasa de error

### Métricas en Logs

Buscar en logs:
```bash
docker exec -it docucenter_laravel.test grep "confidence" storage/logs/laravel.log
```

Analizar confianza promedio para ajustar `GOOGLE_DOCUMENT_AI_MIN_CONFIDENCE`.

## Troubleshooting

### Error: "Processor not found"

**Causa**: ID de processor incorrecto o región incorrecta
**Solución**: Verificar `GOOGLE_DOCUMENT_AI_PROCESSOR_ID` y `GOOGLE_DOCUMENT_AI_LOCATION` en `.env`

### Error: "Permission denied"

**Causa**: Service account sin permisos adecuados
**Solución**: Asignar rol `Document AI API User` a la cuenta de servicio

### Error: "Invalid credentials"

**Causa**: Archivo de credenciales incorrecto o ruta incorrecta
**Solución**: 
1. Verificar que `service-account-credentials.json` existe
2. Verificar `GOOGLE_APPLICATION_CREDENTIALS` apunta al path correcto dentro del contenedor

### Baja Confianza en Extracción

**Causa**: PDF de baja calidad o formato no estándar
**Solución**:
1. Mejorar calidad del escaneo (300 DPI mínimo)
2. Asegurar texto legible en PDF
3. Ajustar `GOOGLE_DOCUMENT_AI_MIN_CONFIDENCE` si es necesario

### Line Items No Detectados

**Causa**: Tabla de items con formato complejo
**Solución**: Document AI puede requerir entrenamiento adicional para formatos específicos. Considerar crear un Custom Processor si los formatos estándar no funcionan.

## Recursos Adicionales

- [Documentación oficial de Document AI](https://cloud.google.com/document-ai/docs)
- [Invoice Parser Documentation](https://cloud.google.com/document-ai/docs/processors-list#processor_invoice-processor)
- [Best Practices](https://cloud.google.com/document-ai/docs/best-practices)
- [Pricing Calculator](https://cloud.google.com/products/calculator)

## Próximos Pasos

1. **Configurar processor en ambiente de desarrollo**
2. **Probar con facturas reales panameñas**
3. **Ajustar confianza mínima según resultados**
4. **Implementar en producción**
5. **Monitorear métricas y costos**
6. **Considerar Custom Processor si es necesario mejorar precisión**
