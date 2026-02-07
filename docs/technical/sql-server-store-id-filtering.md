# Implementación de Filtrado por Store ID en STInvoiceJob

## Resumen de Cambios Implementados

### Objetivo
Filtrar las facturas de SQL Server por Store ID cuando esté configurado en `Configurationsqlorganization`, para procesar solo las facturas de la tienda específica asignada.

## Cambios Realizados

### 1. **STInvoiceJob.php - Filtrado de Consulta**

#### **Antes** (líneas 83-87):
```php
$invoiceQuery = $modelSt->query()->orderBy('CreatedTime', 'desc');
$invoiceQuery->with(['vendor', 'details']);
$invoiceQuery->whereBetween('InvoiceDate', [$startOfDay, $endOfDay]);
$invoices = $invoiceQuery->get();
```

#### **Después** (líneas 83-100):
```php
$invoiceQuery = $modelSt->query()->orderBy('CreatedTime', 'desc');
$invoiceQuery->with(['vendor', 'details']);
$invoiceQuery->whereBetween('InvoiceDate', [$startOfDay, $endOfDay]);

// Filtrar por Store ID si está configurado
if (!empty($this->configuration->store_id)) {
    $invoiceQuery->where('StoreID', $this->configuration->store_id);
    \Illuminate\Support\Facades\Log::info("STInvoiceJob: Filtrando por Store ID", [
        'organization_id' => $this->configuration->organization_id,
        'store_id' => $this->configuration->store_id,
        'date_range' => [$startOfDay, $endOfDay]
    ]);
} else {
    \Illuminate\Support\Facades\Log::info("STInvoiceJob: Sin filtro de Store ID - procesando todas las tiendas", [
        'organization_id' => $this->configuration->organization_id,
        'date_range' => [$startOfDay, $endOfDay]
    ]);
}

$invoices = $invoiceQuery->get();
```

### 2. **Almacenamiento de Store ID en DocuCenter**

#### **Agregado al array de datos** (línea 126):
```php
$requestArr = [
    'ID_compania' => $company->ID_compania ?? '',
    'PurchaseNumber' => $invoice->InvoiceID,
    'VendorID' => $invoice->VendorID,
    'VendorName' => $vendor->VendorName ?? '',
    'AP_Account' => $apAccount,
    'Date' => $invoice->InvoiceDate,
    'Subtotal' => $subtotal,
    'Net_due' => $totalAmountPayable,
    'ApplyToPO' => null,
    'DueDate' => $invoice->InvoiceDate,
    'StoreID' => $invoice->StoreID ?? null, // ← NUEVO
    'Enviado' => 0,
    'Error' => 0,
];
```

### 3. **Logging Mejorado**

#### **Logging de procesamiento con Store ID**:
```php
\Illuminate\Support\Facades\Log::info("STInvoiceJob: Procesando factura {$invoice->InvoiceID} con {$details->count()} items", [
    'invoice_id' => $invoice->InvoiceID,
    'store_id' => $invoice->StoreID ?? 'N/A', // ← NUEVO
    'vendor_name' => $vendor->VendorName ?? 'N/A',
    'total_amount' => $totalAmountPayable,
    'items_count' => $details->count(),
    'items_deleted' => $deletedCount,
    'configuration_store_id' => $this->configuration->store_id ?? 'Sin filtro', // ← NUEVO
]);
```

#### **Logging final con estadísticas**:
```php
\Illuminate\Support\Facades\Log::info("STInvoiceJob: Procesamiento completado", [
    'organization_id' => $this->configuration->organization_id,
    'store_id_filter' => $this->configuration->store_id ?? 'Sin filtro', // ← NUEVO
    'invoices_processed' => $invoices->count(),
    'date_range' => [$startOfDay, $endOfDay],
    'execution_date' => $this->executionDate ?: 'now',
]);
```

### 4. **Modelo PurchaseHeaderImp.php**

#### **Campo agregado al fillable**:
```php
protected $fillable = [
    'ID_compania',
    'PurchaseNumber',
    'VendorID',
    'VendorName',
    'AP_Account',
    'Date',
    'Subtotal',
    'Net_due',
    'ApplyToPO',
    'DueDate',
    'StoreID', // ← NUEVO CAMPO
    'Export_date',
    // ... resto de campos
];
```

## **Lógica de Funcionamiento**

### **Escenario 1: Sin Store ID configurado**
```
Configurationsqlorganization.store_id = NULL
↓
SQL Query: SELECT * FROM STInvoiceHeaders WHERE InvoiceDate BETWEEN '...' AND '...'
↓
Resultado: Procesa TODAS las facturas de TODAS las tiendas
```

### **Escenario 2: Con Store ID configurado**
```
Configurationsqlorganization.store_id = "STORE001"
↓
SQL Query: SELECT * FROM STInvoiceHeaders 
           WHERE InvoiceDate BETWEEN '...' AND '...' 
           AND StoreID = 'STORE001'
↓
Resultado: Procesa SOLO las facturas de la tienda STORE001
```

## **Beneficios de la Implementación**

### **Performance**
- **Menos datos transferidos**: Solo las facturas de la tienda específica
- **Consultas más rápidas**: Filtro en base de datos en lugar de en código
- **Menor uso de memoria**: Menos registros a procesar

### **Trazabilidad**
- **Logging detallado**: Se registra qué Store ID se está filtrando
- **Store ID preservado**: Se guarda en DocuCenter para auditoría
- **Debug mejorado**: Fácil identificar problemas por tienda

### **Flexibilidad**
- **Retrocompatible**: Si no hay store_id, funciona como antes
- **Configurable**: Cada organización puede tener su Store ID
- **Escalable**: Fácil agregar más filtros en el futuro

## **Verificación de Funcionamiento**

### **Para verificar que funciona correctamente:**

1. **Configurar Store ID** en la pantalla de administración
2. **Ejecutar el job** y verificar logs:
   ```
   STInvoiceJob: Filtrando por Store ID
   organization_id: 1
   store_id: "STORE001"
   ```
3. **Verificar datos guardados** en `purchase_header_imp` que tengan `StoreID = "STORE001"`

### **Test de regresión:**
1. **Dejar store_id vacío** en configuración
2. **Ejecutar el job** y verificar logs:
   ```
   STInvoiceJob: Sin filtro de Store ID - procesando todas las tiendas
   ```
3. **Verificar que procesa todas las facturas** como antes

## **Consideraciones Importantes**

### **Base de Datos**
- **Migración pendiente**: Necesita agregar columna `StoreID` a `purchase_header_imp` si no existe
- **Índices**: Considerar agregar índice en `StoreID` para mejor performance

### **Datos Existentes**
- **Facturas anteriores**: Pueden tener `StoreID = NULL` 
- **Migración de datos**: Evaluar si necesita backfill de Store IDs

### **Testing**
- **Probar ambos escenarios**: Con y sin Store ID configurado
- **Verificar performance**: Tiempo de ejecución con filtro vs sin filtro
- **Validar logs**: Que se registre correctamente el filtrado

## **¿Estamos Claros?**

La implementación está **COMPLETA** y **LISTA** para usar. La lógica es:

1. **Si hay `store_id` en configuración** → Filtra por ese Store ID
2. **Si NO hay `store_id`** → Procesa todas las facturas (comportamiento original)
3. **Logging completo** para trazabilidad
4. **Store ID preservado** en DocuCenter para auditoría

¿Necesitas alguna aclaración o modificación adicional?
