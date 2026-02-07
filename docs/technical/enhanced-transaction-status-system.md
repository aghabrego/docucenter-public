# Sistema de Estado Mejorado para Transacciones FE

## Resumen Ejecutivo

Se ha implementado un sistema de estado mejorado para el seguimiento completo del ciclo de vida de las transacciones en las APIs de Facturación Electrónica (FE). Este sistema proporciona visibilidad granular del estado de procesamiento, mensajes de éxito detallados y timestamps de finalización para todas las transacciones.

## Arquitectura del Sistema

### Nuevas Columnas en la Tabla `transactions`

Se agregaron dos nuevas columnas para mejorar el seguimiento de estado:

```sql
-- Columna para mensajes de éxito detallados
success_message TEXT NULL AFTER error_message

-- Timestamp de finalización (éxito o fallo)
completed_at TIMESTAMP NULL AFTER last_attempt_at

-- Índices para optimizar consultas
INDEX transactions_status_org_idx (status, organization_id)
INDEX transactions_completed_at_idx (completed_at)
```

### Estados de Transacción

El sistema mantiene los estados existentes con funcionalidad mejorada:

- **`pending`**: Estado inicial al crear la transacción
- **`processing`**: Transacción en curso de procesamiento activo
- **`success`**: Transacción completada exitosamente
- **`failed`**: Transacción falló con error
- **`retrying`**: Transacción en reintento automático

## Métodos del OrganizationService Mejorados

### `markTransactionAsProcessing($organizationId, $reference)`

Nuevo método para marcar una transacción como en procesamiento activo:

```php
public function markTransactionAsProcessing(int $organizationId, string $reference): bool
{
    return Transaction::where('organization_id', $organizationId)
        ->where('reference', $reference)
        ->update([
            'status' => 'processing',
            'updated_at' => now()
        ]) > 0;
}
```

### `markTransactionAsSuccessful($organizationId, $reference, $successMessage = null)`

Método mejorado con soporte para mensajes de éxito:

```php
public function markTransactionAsSuccessful(int $organizationId, string $reference, string $successMessage = null): bool
{
    $updateData = [
        'status' => 'success',
        'completed_at' => now(),
        'updated_at' => now()
    ];

    if ($successMessage !== null) {
        $updateData['success_message'] = $successMessage;
    }

    return Transaction::where('organization_id', $organizationId)
        ->where('reference', $reference)
        ->update($updateData) > 0;
}
```

### `markTransactionAsFailed($organizationId, $reference, $errorMessage)`

Método mejorado con timestamp de finalización:

```php
public function markTransactionAsFailed(int $organizationId, string $reference, string $errorMessage): bool
{
    return Transaction::where('organization_id', $organizationId)
        ->where('reference', $reference)
        ->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'updated_at' => now()
        ]) > 0;
}
```

## Modelo Transaction Actualizado

### Nuevos Campos Fillable

```php
protected $fillable = [
    // ... campos existentes
    'success_message',
    'completed_at'
];
```

### Casts Mejorados

```php
protected $casts = [
    // ... casts existentes
    'completed_at' => 'datetime'
];
```

## Jobs FE Implementados

Se aplicó el patrón de estado mejorado a todos los Jobs de FE:

### 1. CreateSaleAciCloudJob

**Características especiales:**
- Soporte para emisión condicional (`shouldIssue`)
- Manejo de contadores de intentos
- Mensajes de éxito diferenciados

**Patrón implementado:**
```php
// Al inicio del procesamiento
$organizationService->markTransactionAsProcessing($organizationId, $number);

// En caso de éxito
$organizationService->markTransactionAsSuccessful(
    $organizationId, 
    $number, 
    'Factura emitida exitosamente en AciCloud'
);

// En caso de error
$organizationService->markTransactionAsFailed($organizationId, $number, $e->getMessage());
```

### 2. CreateSaleShopifyJob

**Características especiales:**
- Procesamiento de órdenes de Shopify
- Manejo simplificado sin emisión

**Mensajes de éxito:**
- `"Orden de Shopify creada exitosamente"`

### 3. CreateSaleKart21Job

**Características especiales:**
- Manejo de excepción `CufeAlreadyExistsException`
- Procesamiento omitido para CUFEs duplicados

**Casos especiales:**
```php
// Para CUFE existente
$organizationService->markTransactionAsSuccessful(
    $organizationId, 
    $number, 
    'CUFE ya existe - procesamiento omitido'
);
```

### 4. CreateSaleMaxgymJob

**Características especiales:**
- Procesamiento de datos con validaciones complejas
- Uso de reflection para limpieza de datos
- Contadores de intentos

**Mensajes de éxito:**
- `"Factura de Maxgym emitida exitosamente"`

### 5. CreateSaleMeyparJob

**Características especiales:**
- Manejo de transacciones de base de datos
- Procesamiento en método privado
- Memoria optimizada

**Mensajes de éxito:**
- `"Documento de MEYPAR Colombia procesado exitosamente"`

### 6. CreateSaleLightspeedJob

**Características especiales:**
- Validación de status específicos
- Productos ignorados configurables
- Múltiples puntos de salida temprana

**Casos especiales:**
```php
// Status no válido
$organizationService->markTransactionAsSuccessful(
    $organizationId, 
    $invoiceNumber, 
    "Status '{$status}' no válido - procesamiento omitido"
);

// Productos ignorados
$organizationService->markTransactionAsSuccessful(
    $organizationId, 
    $invoiceNumber, 
    'Productos ignorados encontrados - procesamiento omitido'
);
```

### 7. CreateSaleQuickBooksJob (Ya implementado)

**Características especiales:**
- Configuración local mejorada
- Optimización de consultas de base de datos

## Patrón de Implementación Estándar

### Estructura Común en Todos los Jobs

```php
public function handle()
{
    /** @var string $number */
    $number = // extraer número de referencia

    /** @var \App\Services\OrganizationService $organizationService */
    $organizationService = app()->make(\App\Contracts\OrganizationServiceContract::class);

    try {
        // 1. Almacenar transacción inicial
        $organizationService->storeTransaction(/* ... */);

        // 2. Validaciones tempranas con marcado de éxito si se omite
        if (/* condición de omisión */) {
            $organizationService->markTransactionAsSuccessful(
                $organizationId, 
                $number, 
                'Mensaje de omisión específico'
            );
            return;
        }

        // 3. Marcar como en procesamiento
        $organizationService->markTransactionAsProcessing($organizationId, $number);

        // 4. Lógica de procesamiento específica del Job
        // ... procesamiento ...

        // 5. Marcar como exitosa con mensaje específico
        $organizationService->markTransactionAsSuccessful(
            $organizationId, 
            $number, 
            'Mensaje de éxito específico'
        );

    } catch (\Exception $e) {
        // 6. Marcar como fallida en caso de error
        $organizationService->markTransactionAsFailed(
            $organizationId, 
            $number, 
            $e->getMessage()
        );

        throw $e; // Re-lanzar para reintentos automáticos
    }
}
```

## Beneficios del Sistema

### 1. Visibilidad Completa
- Estado granular de cada transacción
- Mensajes de éxito específicos por contexto
- Timestamps de finalización precisos

### 2. Debugging Mejorado
- Identificación rápida de transacciones en procesamiento
- Mensajes de error y éxito detallados
- Historial completo de estado

### 3. Monitoreo y Alertas
- Posibilidad de crear alertas basadas en estados
- Métricas de tiempo de procesamiento
- Identificación de cuellos de botella

### 4. Auditoría Completa
- Rastro completo del ciclo de vida
- Diferenciación entre fallas y omisiones
- Datos para análisis de rendimiento

## Consultas de Ejemplo

### Transacciones en Procesamiento por Organización
```sql
SELECT * FROM transactions 
WHERE organization_id = ? 
  AND status = 'processing'
ORDER BY updated_at DESC;
```

### Transacciones Completadas con Tiempo de Procesamiento
```sql
SELECT 
    reference,
    module_type,
    status,
    success_message,
    TIMESTAMPDIFF(SECOND, created_at, completed_at) as processing_time_seconds
FROM transactions 
WHERE organization_id = ? 
  AND completed_at IS NOT NULL
ORDER BY completed_at DESC;
```

### Estadísticas de Éxito por Módulo
```sql
SELECT 
    module_type,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
    ROUND((SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
FROM transactions 
WHERE organization_id = ?
GROUP BY module_type;
```

## Migración Implementada

**Archivo:** `2025_09_13_013942_add_enhanced_status_columns_to_transactions_table.php`

**Ejecutada:** Completada exitosamente

**Columnas agregadas:**
- `success_message` (TEXT, nullable)
- `completed_at` (TIMESTAMP, nullable)

**Índices creados:**
- `transactions_status_org_idx` (status, organization_id)
- `transactions_completed_at_idx` (completed_at)

## Conclusión

El sistema de estado mejorado proporciona una base sólida para el monitoreo, debugging y auditoría de todas las transacciones de FE en DocuCenter. La implementación consistente en todos los Jobs garantiza un comportamiento predecible y una experiencia de desarrollo mejorada.

---

**Fecha de implementación:** Septiembre 2025  
**Autor:** Sistema de Mejoras DocuCenter  
**Status:** Completado y Documentado
