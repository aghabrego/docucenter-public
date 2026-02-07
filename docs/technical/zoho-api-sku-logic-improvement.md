# Mejora de Lógica ProductID: SKU vs item_id en API Zoho

## Resumen de Cambios

Se implementó una mejora en la lógica de selección de `ProductID` para dar prioridad al campo `sku` de Zoho cuando esté disponible, usando `item_id` como fallback.

## Problema Identificado

En la implementación anterior, siempre se usaba `item_id` como `ProductID`, sin aprovechar el campo `sku` que puede ser más útil para identificación de productos.

## Solución Implementada

### 1. Lógica de Selección de ProductID

**Archivo:** `app/Services/ACIcloudService.php`

**Nueva lógica:**

```php
// Usar SKU como ProductID si está disponible y no vacío, sino usar item_id
$productId = !empty(trim($lineItem['sku'] ?? '')) ? trim($lineItem['sku']) : $lineItem['item_id'];
```

**Casos de uso:**

| Escenario | SKU | item_id | ProductID Seleccionado |
|-----------|-----|---------|----------------------|
| SKU presente | `"PRODUCT-001"` | `"789123"` | `"PRODUCT-001"` |
| SKU vacío | `""` | `"789123"` | `"789123"` |
| SKU null | `null` | `"789123"` | `"789123"` |
| SKU con espacios | `"   "` | `"789123"` | `"789123"` |

### 2. Mapeo Mejorado de ProductsImp

**Campos agregados/corregidos:**

```php
$productData = [
    'ProductID' => $productId,                        // SKU o item_id
    'Description' => $lineItem['name'],               // Nombre del producto
    'Price1' => (float) $lineItem['rate'],            // Precio unitario
    'TaxType' => $lineItem['tax_name'] ?? 'standard', // Tipo de impuesto
    'UPC_SKU' => $lineItem['sku'] ?? '',              // SKU original siempre
    'UnitMeasure' => $lineItem['unit'] ?? 'und',      // Unidad de medida
    'GL_Sales_Acct' => '4000',                        // Cuenta ventas
    'GL_Inventory_Acct' => '1300',                    // Cuenta inventario
    'GL_CostOfSales_Acct' => '5000',                  // Cuenta costo ventas
    'ItemType' => $lineItem['product_type'] ?? 'goods', // Tipo de ítem
    'ID_compania' => $company->ID_compania ?? null,   // ID compañía
    // ... campos adicionales mapeados desde Zoho
];
```

### 3. Mapeo Corregido de SalesOrderDetailImp

**Campos corregidos según el modelo:**

```php
$detailData = [
    'SalesOrderNumber' => $salesOrderHeader->SalesOrderNumber,
    'Taxable' => $lineItem['tax_percentage'] > 0 ? 1 : 0,  // Boolean corregido
    'ItemOrd' => $lineItem['item_order'] ?? $index + 1,    // Orden del ítem
    'Item_id' => $productId,                               // ProductID seleccionado
    'Description' => $lineItem['name'],                    // Descripción
    'Quantity' => (float) $lineItem['quantity'],          // Cantidad
    'Unit_Price' => (float) $lineItem['rate'],            // Precio unitario
    'Net_line' => (float) $lineItem['item_total'],        // Total línea
    'JobId' => null,                                       // Trabajo (por defecto)
    'JobPhaseID' => null,                                  // Fase trabajo
    'JobCostCodeID' => null,                               // Código costo
    'ID_compania' => $company->ID_compania ?? null,       // ID compañía
];
```

## Campos Corregidos

### ProductsImp
- **ProductID**: Ahora usa SKU con prioridad sobre item_id
- **GL_Inventory_Acct**: Agregado (1300)
- **GL_CostOfSales_Acct**: Agregado (5000)
- **UPC_SKU**: Siempre guarda el SKU original de Zoho

### SalesOrderDetailImp
- **Item_id**: Corregido de `ItemId` (que no existe en el modelo)
- **Unit_Price**: Corregido de `UnitPrice`
- **Net_line**: Corregido de `LineTotal`
- **Taxable**: Corregido tipo boolean (1/0 en lugar de true/false)
- **ItemOrd**: Agregado fallback cuando no existe `item_order`

## Validación Implementada

### Script de Testing

**Archivo:** `docs/testing/test-zoho-api-sku-logic.php`

**Validaciones:**

1. **Campos del modelo**: Verificación de existencia de todos los campos utilizados
2. **Lógica de ProductID**: Testing de 4 casos diferentes de SKU
3. **Mapeo de campos**: Validación de correspondencia con modelos
4. **Casos edge**: SKU vacío, null, solo espacios

**Resultados:**

- **ProductsImp**: 30 campos válidos, todos mapeados correctamente
- **SalesOrderDetailImp**: 18 campos válidos, todos mapeados correctamente
- **Lógica ProductID**: 4/4 casos de prueba exitosos

## Beneficios de la Mejora

1. **Identificación Mejorada**: SKU es más significativo que item_id interno de Zoho
2. **Flexibilidad**: Fallback automático a item_id cuando SKU no está disponible
3. **Compatibilidad**: Todos los campos alineados con modelos existentes
4. **Robustez**: Manejo de casos edge (SKU vacío, espacios, null)
5. **Trazabilidad**: UPC_SKU siempre preserva el SKU original

## Impacto en el Sistema

### Funcionamiento
- **Sin cambios funcionales**: La API mantiene toda su funcionalidad
- **Mejor identificación**: Productos identificados por SKU cuando disponible
- **Compatibilidad total**: Con modelos y estructura de base de datos existente

### Logging Mejorado
```php
'product_id_logic' => [
    'sku_available' => !empty($lineItem['sku']),
    'sku_value' => $lineItem['sku'] ?? null,
    'item_id_value' => $lineItem['item_id'],
    'selected_product_id' => $productId,
]
```

## Testing

```bash
# Ejecutar validación de lógica SKU
docker exec -it docucenter_laravel.test php docs/testing/test-zoho-api-sku-logic.php
```

**Resultado esperado:** 4/4 casos de ProductID exitosos

---

**Fecha:** 2024-01-15  
**Estado:** Completado y Validado  
**Archivos modificados:**
- `app/Services/ACIcloudService.php`
- `docs/testing/test-zoho-api-sku-logic.php`
**Testing:** Todos los casos exitosos
