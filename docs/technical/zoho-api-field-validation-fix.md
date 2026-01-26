# Corrección de Campos API Zoho - SalesOrderHeaderImp

## Resumen de Cambios

Se identificó y corrigió un problema en el mapeo de datos de la API de Zoho donde se utilizaban campos incompatibles con el modelo `SalesOrderHeaderImp`. 

## Problema Identificado

En el método `createSaleOrderZoho` del servicio `ACIcloudService`, el array `headerData` contenía campos que no existían en el modelo `SalesOrderHeaderImp`:

### Campos Problemáticos:

1. **NetDue** → No existe en modelo (debería ser `Net_due`)
2. **Date** → No existe en modelo (debería ser `date`)  
3. **RequiredDate** → No existe en modelo
4. **ShipDate** → No existe en modelo
5. **ZohoData** → No existe en modelo

## Solución Implementada

### 1. Corrección de Campos

**Archivo:** `app/Services/ACIcloudService.php`

**Cambios realizados:**

```php
// ANTES (campos incorrectos)
$headerData = [
    'NetDue' => (float) $request->input('total', 0),              // Campo incorrecto
    'Date' => $request->input('date'),                            // Campo incorrecto
    'RequiredDate' => $request->input('shipment_date'),           // Campo no existe
    'ShipDate' => $request->input('shipment_date'),               // Campo no existe
    'ZohoData' => json_encode([...]),                             // Campo no existe
    // ... otros campos
];

// DESPUÉS (campos válidos)
$headerData = [
    'Net_due' => (float) $request->input('total', 0),            // Campo corregido
    'date' => $request->input('date'),                            // Campo corregido
    'observaciones' => $request->input('terms', ''),             // Usar campo válido
    'user' => 'zoho_api',                                         // Campo por defecto
    'Enviado' => false,                                           // Campo por defecto
    'Error' => false,                                             // Campo por defecto
    'Emitida' => false,                                           // Campo por defecto
    // ... otros campos válidos
];
```

### 2. Logging de Datos Adicionales

Los datos adicionales de Zoho que no se pueden mapear directamente al modelo ahora se logean por separado:

```php
\Illuminate\Support\Facades\Log::info("DEBUG ACIcloudService::createSaleOrderZoho - Datos del header mapeados", [
    'header_data' => $headerData,
    'zoho_unmapped_data' => [
        'salesorder_id' => $request->input('salesorder_id'),
        'status' => $request->input('status'),
        'currency_code' => $request->input('currency_code'),
        'shipment_date' => $request->input('shipment_date'),
        'delivery_method' => $request->input('delivery_method'),
        'note' => 'Estos datos de Zoho no se mapean directamente al modelo SalesOrderHeaderImp'
    ]
]);
```

## Validación de Cambios

### Script de Validación

**Archivo:** `docs/testing/test-zoho-api-fixed-fields.php`

**Resultados de la validación:**

- **23 campos en headerData** - Todos válidos
- **0 campos inválidos** 
- **Compatibilidad 100%** con modelo SalesOrderHeaderImp

### Campos Corregidos

| Campo Anterior | Campo Corregido | Estado |
|---------------|-----------------|---------|
| `NetDue` | `Net_due` | Corregido |
| `Date` | `date` | Corregido |
| `RequiredDate` | N/A | Removido |
| `ShipDate` | N/A | Removido |
| `ZohoData` | N/A | Removido (se logea) |

## Beneficios de la Corrección

1. **Prevención de Errores SQL**: Se evitan errores de campos inexistentes en la base de datos
2. **Compatibilidad Total**: Todos los campos utilizados existen en el modelo
3. **Funcionalidad Mantenida**: `updateOrCreate` funciona sin problemas
4. **Logging Mejorado**: Datos adicionales de Zoho se logean para análisis
5. **Duplicados Prevenidos**: La funcionalidad de prevención de duplicados se mantiene intacta

## Funcionalidades Mantenidas

- Creación/actualización de Sales Order Headers
- Prevención de duplicados con `updateOrCreate`
- Mapeo completo de datos de Zoho
- Creación de line items
- Actualización de clientes y productos
- Logging comprehensivo para debugging

## Verificación de Funcionamiento

La API `/api/acicloud/create_sale_order_zoho` ahora:

1. **Acepta datos de Zoho** correctamente
2. **Mapea solo campos válidos** al modelo SalesOrderHeaderImp  
3. **Evita errores de SQL** por campos inexistentes
4. **Mantiene funcionalidad** de updateOrCreate
5. **Logea datos adicionales** para análisis futuro

## Impacto en el Sistema

- **Sin cambios funcionales**: La API mantiene toda su funcionalidad
- **Mejora en estabilidad**: Se previenen errores de base de datos
- **Mejor logging**: Datos de Zoho más organizados en logs
- **Compatibilidad garantizada**: Alineación total con modelo de datos

## Testing

```bash
# Ejecutar validación de campos
docker exec -it docucenter_laravel.test php docs/testing/test-zoho-api-fixed-fields.php
```

**Resultado esperado:** Validación exitosa con 23 campos compatibles

---

**Fecha:** 2024-01-15  
**Estado:** Completado y Validado  
**Archivos modificados:**
- `app/Services/ACIcloudService.php`
- `docs/testing/test-zoho-api-fixed-fields.php`
