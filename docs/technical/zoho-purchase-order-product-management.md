# Gestión Automática de Productos en Órdenes de Compra Zoho

## Resumen

Se implementó la gestión automática de productos en el flujo de órdenes de compra de Zoho para que siga el mismo patrón que las ventas, asegurando consistencia en el manejo de inventario.

## Cambios Implementados

### 1. ZohoPurchaseOrderImporter - Gestión Automática de Productos

**Archivo**: `/app/Services/Zoho/ZohoPurchaseOrderImporter.php`

#### Nuevas Dependencias
```php
use App\Models\ProductsImp;
use App\Facades\OrganizationFacade;
```

#### Método Modificado: `createPurchaseDetails()`

**ANTES** (solo creaba detalles):
```php
private function createPurchaseDetails(array $zohoData, int $transactionId, int $organizationId): int
{
    $lineItems = $this->processLineItems($zohoData['line_items']);
    $detailsData = $this->transformer->transformLineItems($lineItems, $transactionId);

    $detailsCount = 0;
    foreach ($detailsData as $detail) {
        $detail['ID_compania'] = $organizationId;
        PurchaseDetailImp::create($detail);
        $detailsCount++;
    }
    return $detailsCount;
}
```

**AHORA** (crea productos + detalles):
```php
private function createPurchaseDetails(array $zohoData, int $transactionId, int $organizationId): int
{
    $lineItems = $this->processLineItems($zohoData['line_items']);
    $company = OrganizationFacade::getCompanyActiveOrganization();
    
    foreach ($lineItems as $index => $lineItem) {
        // 1. CREAR/ACTUALIZAR PRODUCTO AUTOMÁTICAMENTE
        $productId = $this->getProductIdentifier($lineItem);
        
        $productData = [
            'ProductID' => $productId,
            'Description' => $lineItem['name'],
            'Price1' => $lineItem['rate'],
            'TaxType' => $lineItem['tax_name'] ?? 'standard',
            'Custom_field1' => $lineItem['sku'] ?? '',
            'Custom_field2' => $lineItem['item_type'] ?? '',
            'Custom_field3' => $lineItem['group_name'] ?? '',
            'Custom_field4' => $lineItem['account_name'] ?? '',
            'UnitMeasure' => $lineItem['unit'] ?? 'und',
            'ItemType' => $lineItem['product_type'] ?? 'goods',
            'UPC_SKU' => $lineItem['sku'] ?? '',
            'ID_compania' => $company->ID_compania,
            'GL_Sales_Acct' => $lineItem['account_name'] ?? '',
            'GL_Inventory_Acct' => $lineItem['account_name'] ?? '',
            'GL_CostOfSales_Acct' => $lineItem['account_name'] ?? '',
        ];

        // Crear o actualizar usando ProductID
        $product = ProductsImp::updateOrCreate(['ProductID' => $productId], $productData);

        // 2. CREAR DETALLE vinculado
        $detailData = $this->transformer->transformLineItems([$lineItem], $transactionId)[0];
        $detailData['ID_compania'] = $organizationId;
        $detailData['Item_id'] = $productId; // Asegurar vinculación
        
        PurchaseDetailImp::create($detailData);
        $detailsCount++;
    }
    return $detailsCount;
}
```

#### Nuevos Métodos Auxiliares

```php
/**
 * Obtiene identificador del producto siguiendo lógica de ventas
 * Prioridad: SKU > item_id > nombre truncado
 */
private function getProductIdentifier(array $lineItem): string

/**
 * Trunca texto para límites de BD
 */
private function truncateText(string $text, int $maxLength): string
```

## Beneficios Implementados

### 1. **Consistencia con Ventas**
- Mismo patrón de creación automática de productos
- Misma lógica de identificación (SKU > item_id > nombre)
- Mismo mapeo de custom fields

### 2. **Gestión Completa de Inventario**
- **Antes**: Solo referencias por `Item_id` en detalles
- **Ahora**: Productos completos en tabla `Products_Imp`

### 3. **Mapeo Exhaustivo de Campos**
| Campo | Fuente Zoho | Descripción |
|-------|-------------|-------------|
| `ProductID` | SKU o item_id | Identificador único |
| `Description` | name | Descripción del producto |
| `Price1` | rate | Precio unitario |
| `TaxType` | tax_name | Tipo de impuesto |
| `Custom_field1` | sku | SKU del producto |
| `Custom_field2` | item_type | Tipo de item |
| `Custom_field3` | group_name | Grupo/categoría |
| `Custom_field4` | account_name | Cuenta contable |
| `UnitMeasure` | unit | Unidad de medida |
| `ItemType` | product_type | Tipo de producto |
| `UPC_SKU` | sku | Código de barras |
| `GL_*_Acct` | account_name | Cuentas contables |

### 4. **Logging Detallado**
- Log de cada producto procesado
- Indicador de creación vs actualización
- Trazabilidad completa del proceso

### 5. **Robustez y Validación**
- Manejo de campos opcionales con fallbacks
- Truncado automático para límites de BD
- Consistencia en vinculación producto-detalle

## Impacto en el Sistema

### Tablas Afectadas
1. **`Products_Imp`** - Ahora se puebla automáticamente desde compras Zoho
2. **`Purchase_Detail_Imp`** - `Item_id` ahora vincula correctamente con `ProductID`

### Flujo Actualizado
```
Zoho Purchase Order → ProcessZohoPurchaseOrderJob → ZohoPurchaseOrderImporter
                                                          ↓
                                                   1. Create/Update Product
                                                   2. Create Purchase Detail
                                                   3. Link via ProductID
```

### Compatibilidad
- **Totalmente compatible** con datos existentes
- **No afecta** órdenes de compra ya procesadas
- **Mantiene** toda la funcionalidad existente

## Testing Recomendado

1. **Crear orden de compra** con productos nuevos
2. **Verificar** que se crean en `Products_Imp`
3. **Crear orden** con productos existentes 
4. **Verificar** que se actualizan, no duplican
5. **Validar** vinculación correcta en `Purchase_Detail_Imp`

## Monitoreo

Los logs incluyen:
- `ZohoPurchaseOrderImporter - Datos del producto para compra`
- `ZohoPurchaseOrderImporter - Producto procesado`
- `ZohoPurchaseOrderImporter - Todos los productos y detalles procesados`

Buscar en logs por `bill_number` específico para rastrear procesamiento.
