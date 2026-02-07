# UpdateFiscalDocumentNumberJob - Migración de Números Fiscales

Sistema para migrar y completar `fiscal_document_number` en facturas existentes que ya tienen CUFE pero no tienen el número fiscal extraído.

## Propósito

Este Job es útil para:
- **Migrar datos históricos**: Facturas procesadas antes de la implementación del campo `fiscal_document_number`
- **Completar datos faltantes**: Facturas que tienen CUFE en `intuit_extracted_cufe` o `InvoiceNote` pero no el número fiscal
- **Reparar inconsistencias**: Datos incompletos o corruptos en el campo fiscal

## Arquitectura

### UpdateFiscalDocumentNumberJob
**Ubicación**: `app/Jobs/UpdateFiscalDocumentNumberJob.php`

**Funcionalidades**:
- Procesa facturas por organización específica
- Extrae números fiscales de CUFEs existentes usando `CufeValidationHelper`
- Evita duplicaciones de números fiscales
- Procesamiento eficiente usando `cursor()` para grandes volúmenes
- Logging detallado y estadísticas de procesamiento

### UpdateFiscalDocumentNumbers Command
**Ubicación**: `app/Console/Commands/UpdateFiscalDocumentNumbers.php`

**Funcionalidades**:
- Interface de línea de comandos para ejecutar el Job
- Soporte para procesamiento individual o en lote
- Modo dry-run para ver qué se procesaría sin ejecutar
- Batch processing con seguimiento de progreso

## Uso

### Comando Artisan

```bash
# Procesar una organización específica
php artisan quickbooks:update-fiscal-numbers --organization-id=123

# Procesar todas las organizaciones en lote
php artisan quickbooks:update-fiscal-numbers --batch

# Modo dry-run (ver qué se procesaría sin ejecutar)
php artisan quickbooks:update-fiscal-numbers --organization-id=123 --dry-run
php artisan quickbooks:update-fiscal-numbers --batch --dry-run
```

### Programático (desde código)

```php
use App\Jobs\UpdateFiscalDocumentNumberJob;

// Procesar una organización específica
UpdateFiscalDocumentNumberJob::dispatch($organizationId, $organizationDatabase);

// Batch processing
$jobs = [];
foreach ($organizations as $org) {
    $jobs[] = new UpdateFiscalDocumentNumberJob($org->id, $org->database);
}

Bus::batch($jobs)
   ->name('Update Fiscal Numbers Batch')
   ->dispatch();
```

## Lógica de Procesamiento

### Criterios de Selección
El Job procesa facturas que cumplan:

1. **Tienen CUFE pero no número fiscal**:
   ```sql
   WHERE intuit_extracted_cufe IS NOT NULL 
     AND intuit_extracted_cufe != ''
     AND (fiscal_document_number IS NULL OR fiscal_document_number = '')
   ```

2. **O tienen InvoiceNote con CUFE pero no número fiscal**:
   ```sql
   WHERE InvoiceNote IS NOT NULL 
     AND InvoiceNote LIKE '%FE%'
     AND (fiscal_document_number IS NULL OR fiscal_document_number = '')
   ```

3. **Y están relacionadas con QuickBooks**:
   ```sql
   WHERE (origin = 'quickbooks' 
      OR intuit_extracted_cufe IS NOT NULL 
      OR intuit_customer_id IS NOT NULL)
   ```

4. **Y han sido emitidas exitosamente**:
   ```sql
   WHERE EzeeIssued = true
   ```

### Proceso de Extracción

1. **Obtener CUFE**:
   - Prioridad 1: Campo `intuit_extracted_cufe`
   - Prioridad 2: Extraer del `InvoiceNote` usando regex y parsing JSON

2. **Extraer Número Fiscal**:
   - Usar `CufeValidationHelper::extractFiscalNumberFromCufe()`
   - Extrae caracteres de posiciones 41-44 del CUFE

3. **Validar Duplicación**:
   - Verificar que el número fiscal no exista en otras facturas
   - Omitir si ya existe para evitar conflictos

4. **Actualizar Base de Datos**:
   - Almacenar `fiscal_document_number`
   - Si se extrajo CUFE del InvoiceNote, también almacenar `intuit_extracted_cufe`

## Monitoreo y Logging

### Logs Principales

```php
// Inicio del procesamiento
"UpdateFiscalDocumentNumberJob: Iniciando procesamiento"

// Progreso cada 100 registros
"UpdateFiscalDocumentNumberJob: Progreso"

// Estadísticas finales
"UpdateFiscalDocumentNumberJob: Procesamiento completado"
```

### Estadísticas Disponibles

```php
[
    'processed' => 1500,  // Total de facturas procesadas
    'updated' => 1200,    // Facturas actualizadas exitosamente
    'skipped' => 250,     // Facturas omitidas (sin CUFE, duplicados, etc.)
    'errors' => 50        // Errores durante procesamiento
]
```

### Niveles de Log

- **INFO**: Inicio, progreso, finalización
- **DEBUG**: Detalles de facturas individuales
- **WARNING**: Duplicaciones detectadas
- **ERROR**: Errores procesando facturas específicas
- **CRITICAL**: Fallo completo del Job

## Consideraciones

### Performance
- Usa `cursor()` para eficiencia de memoria en grandes volúmenes
- Procesa registro por registro para evitar timeouts
- Timeout de 5 minutos por Job
- Logs de progreso cada 100 registros

### Seguridad
- Validación de duplicados antes de actualizar
- Rollback automático en caso de error crítico
- No modifica facturas que ya tienen `fiscal_document_number`
- Continúa procesando aunque falle una factura individual

### Mantenimiento
- 3 intentos automáticos en caso de fallo
- Logs detallados para debugging
- Estadísticas completas de cada ejecución

## Casos de Uso Típicos

### 1. Migración Inicial Post-Implementación
```bash
# Después de implementar fiscal_document_number, miglar datos históricos
php artisan quickbooks:update-fiscal-numbers --batch --dry-run
php artisan quickbooks:update-fiscal-numbers --batch
```

### 2. Reparación de Datos Específicos
```bash
# Organización con problemas específicos
php artisan quickbooks:update-fiscal-numbers --organization-id=456
```

### 3. Verificación Antes de Cambios
```bash
# Ver qué se procesaría antes de ejecutar
php artisan quickbooks:update-fiscal-numbers --organization-id=789 --dry-run
```

## Integración

### Con Sistemas Existentes
- Compatible con `CufeValidationHelper`
- Respeta los mismos patrones de extracción que `UpdateIntuitOrdersJob` y `CreateSaleQuickBooksJob`
- Usa la misma lógica de validación de duplicados

### Base de Datos
- Actualiza tabla `Sales_Header_Imp`
- Respeta arquitectura multi-tenant
- Conexiones dinámicas por organización

## Métricas de Éxito

- **Tasa de extracción**: % de CUFEs procesados exitosamente
- **Precisión**: % de números fiscales extraídos correctamente
- **Rendimiento**: Facturas procesadas por minuto
- **Confiabilidad**: % de ejecuciones sin errores críticos

El sistema está diseñado para ser robusto, eficiente y fácil de monitorear para operaciones de migración de datos críticos en entornos de producción.
