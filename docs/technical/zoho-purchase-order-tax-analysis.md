# Análisis de Segundo Log Zoho - Con Impuestos (ITBMS)

**Fecha**: 2025-10-02  
**Tipo**: Análisis adicional - Orden con impuestos  

## Datos Clave del Segundo Log

### Header con Impuestos
```json
{
  "bill_number": "9999",
  "vendor_name": "Medtrition",
  "vendor_id": "6088114000000487410", 
  "sub_total": "5.0",
  "tax_total": "0.35",
  "total": "5.35",
  "balance": "5.35"
}
```

### Line Item con Impuestos Detallados
```json
{
  "sku": "11525",
  "name": "PRO SOURCE NO CARB",
  "quantity": 1,
  "rate": 5,
  "item_total": 5,
  "tax_id": "6088114000000466001",
  "tax_name": "ITBMS",
  "tax_percentage": 7,
  "line_item_taxes": [
    {
      "tax_amount": 0.35,
      "tax_name": "ITBMS (7%)",
      "tax_amount_formatted": "$0.35",
      "tax_id": "6088114000000466001"
    }
  ]
}
```

## Nuevos Campos Identificados para Impuestos

### Header Level
- `tax_total`: Impuesto total de la orden
- `tax_total_formatted`: Formato con moneda
- `discount_account_id`: Cuenta de descuentos
- `discount_amount`: Monto de descuento
- `is_discount_before_tax`: Si descuento se aplica antes de impuestos

### Line Item Level  
- `tax_id`: ID del impuesto en Zoho
- `tax_name`: Nombre del impuesto (ej: "ITBMS")
- `tax_percentage`: Porcentaje del impuesto (7)
- `line_item_taxes`: Array detallado de impuestos por línea
  - `tax_amount`: Monto específico del impuesto
  - `tax_name`: Nombre completo (ej: "ITBMS (7%)")
  - `tax_amount_formatted`: Formato con moneda

## Comparación de Ejemplos

| Campo | Log 1 (Sin impuestos) | Log 2 (Con impuestos) |
|-------|----------------------|----------------------|
| `sub_total` | "60.0" | "5.0" |
| `tax_total` | "0.0" | "0.35" |
| `total` | "60.0" | "5.35" |
| `tax_percentage` | 0 | 7 |
| `line_item_taxes` | [] | [{"tax_amount":0.35,...}] |

## Implicaciones para el Mapeo

### 1. Modelos DocuCenter Necesitan Campos de Impuestos
Los modelos actuales `PurchaseHeaderImp` y `PurchaseDetailImp` necesitan campos para:
- Impuesto total del header
- Impuesto por línea de detalle
- Porcentaje de impuesto
- ID del impuesto

### 2. Validaciones Adicionales
- Validar que `sub_total + tax_total = total`
- Verificar coherencia de impuestos por línea
- Manejar casos con y sin impuestos

### 3. Transformación Mejorada
- Calcular impuestos por línea si no están presentes
- Manejar múltiples impuestos por línea
- Preservar información de impuestos para auditoría

## Próximas Acciones Sugeridas

1. **Actualizar Transformador** - Agregar manejo de impuestos
2. **Validar Modelos** - Verificar campos disponibles para impuestos  
3. **Agregar Tests** - Casos con y sin impuestos
4. **Documentar Mapping** - Actualizar mapeo con campos de impuestos
