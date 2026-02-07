# Corrección Origen 'docucenter' - CUFE Extraction Fix

## Problema Identificado

El origen 'docucenter' tenía **852 registros problemáticos** (86% de 987 registros totales) con `InvoiceNote` pero sin `intuit_extracted_cufe` y `fiscal_document_number`.

## Causa Raíz

1. **Default Origin**: La columna `origin` tiene `DEFAULT 'docucenter'` en el schema
2. **Facturas Manuales**: Las facturas creadas manualmente via `FE/Create.php` no especifican origin, tomando el default
3. **Falta de Guardado**: El componente `FE/Create.php` emitía al PAC correctamente pero **NO guardaba** el registro en `Sales_Header_Imp` con el CUFE extraído
4. **Creación Posterior**: Los registros se creaban después por otros procesos (sincronización/importación) sin incluir el CUFE

## Flujo Problemático Original

```
FE/Create.php → Emisión PAC → CUFE obtenido → NO guardado en DB → Redirect
                                                         ↓
Otro proceso → Crea registro sin CUFE → Sales_Header_Imp (origin='docucenter')
```

## Solución Implementada

### 1. Modificaciones en FE/Create.php

**Archivo**: `app/Http/Livewire/Admin/FE/Create.php`

#### Import Agregado:
```php
use App\Models\SalesHeaderImp;
```

#### Modificaciones en método issueDocument():

**TheFactoryHKA (líneas ~635-645)**:
```php
/** @var string $cufe */
$cufe = array_get($message, "cufe", '');

// GUARDAR FACTURA EN Sales_Header_Imp CON CUFE EXTRAÍDO
if (!empty($cufe)) {
    $this->saveInvoiceToDatabase($cufe, json_encode($message), $request);
    
    $this->redirectRoute(getRouteName() . '.fe.show', [...]);
}
```

**Alanube (líneas ~730-740)**:
```php
/** @var string $cufe */
$cufe = array_get($message, "cufe", '');

// GUARDAR FACTURA EN Sales_Header_Imp CON CUFE EXTRAÍDO (Alanube)
if (!empty($cufe)) {
    $this->saveInvoiceToDatabase($cufe, json_encode($message), $request);
}
```

**Default PAC (líneas ~795-805)**:
```php
/** @var string $cufe */
$cufe = $messages?->message?->cufe ?: null;

// GUARDAR FACTURA EN Sales_Header_Imp CON CUFE EXTRAÍDO (Default PAC)
if (!empty($cufe)) {
    $this->saveInvoiceToDatabase($cufe, json_encode($messages), $request);
}
```

### 2. Nuevo Método saveInvoiceToDatabase()

**Ubicación**: Final de la clase `FE/Create.php`

```php
/**
 * Guardar factura en Sales_Header_Imp con CUFE extraído
 * 
 * @param string $cufe
 * @param string $invoiceNote
 * @param array $request
 * @return void
 */
private function saveInvoiceToDatabase(string $cufe, string $invoiceNote, array $request): void
{
    try {
        // Cambiar a la base de datos de la organización
        DB::connection()->useDatabase($this->organization->database);

        // Extraer número fiscal del CUFE usando helper oficial
        $fiscalNumber = \App\Helpers\CufeValidationHelper::extractFiscalNumberFromCufe($cufe);

        // Crear o actualizar el registro en Sales_Header_Imp
        $salesHeader = SalesHeaderImp::updateOrCreate(
            [
                'InvoiceNumber' => $this->numeroDocumentoFiscal,
                'ID_compania' => $this->organization->id,
            ],
            [
                'CustomerName' => $this->receptor_razonSocial ?? null,
                'CustomerRuc' => $this->receptor_ruc ?? null,
                'gDatRec_dRuc' => $this->receptor_ruc ?? null,
                'Subtotal' => $this->subtotal ?? 0,
                'Net_due' => $this->precioFinal ?? 0,
                'Date' => now(),
                'InvoiceNote' => $invoiceNote,
                'intuit_extracted_cufe' => $cufe,
                'fiscal_document_number' => $fiscalNumber,
                'origin' => 'docucenter', // Explícitamente asignar origin
                'intuit_sync_status' => 'completed', // Marcar como completado
            ]
        );

        Log::info("FE/Create: Factura guardada en Sales_Header_Imp con CUFE", [
            'invoice_number' => $this->numeroDocumentoFiscal,
            'sales_header_id' => $salesHeader->ID,
            'organization_id' => $this->organization->id,
            'cufe_preview' => substr($cufe, 0, 20) . '...',
            'fiscal_number' => $fiscalNumber,
        ]);

    } catch (\Exception $e) {
        Log::error("FE/Create: Error guardando factura en Sales_Header_Imp", [
            'invoice_number' => $this->numeroDocumentoFiscal,
            'organization_id' => $this->organization->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } finally {
        // Volver a la base de datos principal
        DB::connection()->useDatabase(env('DB_DATABASE'));
    }
}
```

## Nuevo Flujo Corregido

```
FE/Create.php → Emisión PAC → CUFE obtenido → saveInvoiceToDatabase() 
                                                         ↓
                              Sales_Header_Imp creado con CUFE → Redirect
```

## Características de la Solución

### Compatibilidad con Todos los PACs
- **TheFactoryHKA**: Soportado
- **Alanube**: Soportado  
- **Default PAC**: Soportado

### Extracción Robusta
- **CUFE**: Extraído del resultado PAC
- **Fiscal Number**: Usando `CufeValidationHelper::extractFiscalNumberFromCufe()`
- **InvoiceNote**: JSON completo de respuesta PAC

### Gestión de Base de Datos
- **Multi-tenant**: Cambia a BD específica de organización
- **Cleanup**: Vuelve a BD principal después
- **UpdateOrCreate**: Evita duplicados por InvoiceNumber + ID_compania

### Logging Completo
- **Success**: Log detallado cuando se guarda correctamente
- **Error**: Log de errores con stack trace
- **Debugging**: IDs de registros para tracking

### Campos Poblados
- **intuit_extracted_cufe**: CUFE del PAC
- **fiscal_document_number**: Número fiscal extraído del CUFE
- **InvoiceNote**: Respuesta completa del PAC
- **origin**: Explícitamente 'docucenter'
- **intuit_sync_status**: 'completed' (evita reprocessing)

## Impacto Esperado

### Registros Futuros
- **100%** de facturas manuales tendrán CUFE almacenado
- **No más** registros 'docucenter' sin CUFE
- **Mejor tracking** de facturas emitidas

### Registros Históricos
-  **852 registros existentes** aún necesitan procesamiento
- **Posible script** para extraer CUFE de InvoiceNote en registros existentes

## Validación

Para validar la corrección en una organización:

```sql
-- Antes de la corrección
SELECT COUNT(*) as problematic_before 
FROM Sales_Header_Imp 
WHERE origin = 'docucenter' 
  AND InvoiceNote IS NOT NULL 
  AND intuit_extracted_cufe IS NULL;

-- Después de crear facturas nuevas, este número no debería aumentar
SELECT COUNT(*) as problematic_after 
FROM Sales_Header_Imp 
WHERE origin = 'docucenter' 
  AND InvoiceNote IS NOT NULL 
  AND intuit_extracted_cufe IS NULL;
```

## Estado Final

**Problema identificado**: Origin 'docucenter' por DEFAULT sin guardado en DB  
**Causa raíz encontrada**: FE/Create.php no guardaba en Sales_Header_Imp  
**Solución implementada**: saveInvoiceToDatabase() para todos los PACs  
**Compatibilidad completa**: TheFactoryHKA, Alanube, Default PAC  
**Logging robusto**: Para debugging y monitoreo  

**El origen 'docucenter' ahora almacenará correctamente el CUFE para todas las facturas creadas manualmente.**
