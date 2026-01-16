## 🔍 Análisis del Problema y Solución

### **Problema Identificado**

Según los datos proporcionados, estamos teniendo facturas duplicadas del mismo cliente con diferentes números fiscales:

```
Registro 1: FE0120000155757563-2-2024-4800002025091200000031620010116838764925 (fiscal: 3162)
Registro 2: FE0120000155757563-2-2024-4800002025091200000031340010117623163690 (fiscal: 3134)
```

### **Estado Actual del Sistema**

✅ **Ya existe la columna**: `intuit_extracted_cufe` en `SalesHeaderImp`
✅ **Ya se almacena el CUFE**: En los Jobs de QuickBooks  
❌ **Falta extracción del número fiscal**: No se extrae el número específico del CUFE
❌ **Falta validación**: No se valida por número fiscal antes de crear

### **Solución Propuesta**

1. **Agregar nueva columna**: `fiscal_document_number` VARCHAR(20)
2. **Crear método extractor**: Extraer número fiscal del CUFE
3. **Validar duplicados**: Verificar por número fiscal antes de crear
4. **Actualizar Job**: Implementar validación en `CreateSaleQuickBooksJob`

### **Patrón del CUFE Analizado**

```
FE0120000155757563-2-2024-4800002025091200000031620010116838764925
                                         ^^^^
                                    posiciones 41-44: 3162

FE0120000155757563-2-2024-4800002025091200000031340010117623163690  
                                         ^^^^
                                    posiciones 41-44: 3134
```

### **Implementación Requerida**

#### 1. **Migración para nueva columna**
```sql
ALTER TABLE Sales_Header_Imp 
ADD COLUMN fiscal_document_number VARCHAR(20) NULL 
AFTER intuit_extracted_cufe;

CREATE INDEX idx_fiscal_document_number 
ON Sales_Header_Imp (fiscal_document_number);
```

#### 2. **Método extractor en Helper**
```php
public static function extractFiscalNumberFromCufe($cufe) 
{
    // Extraer posiciones 41-44 del CUFE
    if (strlen($cufe) >= 45) {
        return substr($cufe, 40, 4); // 0-indexed: posición 40-43
    }
    return null;
}
```

#### 3. **Validación en CreateSaleQuickBooksJob**
```php
// Verificar duplicado por número fiscal
$fiscalNumber = CufeHelper::extractFiscalNumberFromCufe($cufe);
if ($fiscalNumber) {
    $existing = SalesHeaderImp::where('fiscal_document_number', $fiscalNumber)->first();
    if ($existing) {
        throw new CufeAlreadyExistsException("Número fiscal {$fiscalNumber} ya existe");
    }
}
```

¿Procedemos con esta implementación?
