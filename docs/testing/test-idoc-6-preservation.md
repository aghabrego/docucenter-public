# Prueba de Conservación del Campo iDoc = "6" en API create_sale_acicloud_with_emission

## Objetivo
Verificar que el campo `iDoc` con valor "6" se mantiene correctamente a través de todo el proceso de creación y emisión de facturas electrónicas.

## Contexto
- **API**: `/api/v1/fe/create_sale_acicloud_with_emission`
- **Campo crítico**: `dGen.iDoc = "6"` (Nota de Crédito)
- **Proceso**: Crear venta + Emisión síncrona

## Datos de Prueba
```json
{
  "key": null,
  "dGen": {
    "iDoc": "6",
    "dNroDF": "00041",
    "dPtoFacDF": "200",
    "dFechaEm": "2025-09-18T00:00:00-05:00",
    "iNatOp": "11",
    "iTipoOp": "1",
    "iDest": 1,
    "iType": "NC"
  },
  // ... resto del objeto
}
```

## Puntos de Verificación

### 1. Entrada del Request
- Verificar que `$request->dGen['iDoc']` = "6"

### 2. Almacenamiento en SalesHeaderImp
- Verificar que el campo se guarda correctamente en BD
- Comprobar mapping en ACIcloudService->storeOrder()

### 3. Emisión PAC
- Verificar que el XML generado mantiene iDoc="6"
- Confirmar tipo de documento en respuesta PAC

### 4. Respuesta Final
- Validar que la respuesta incluye información correcta del documento

## Scripts de Prueba

### 1. Prueba Completa
```bash
php artisan test:idoc-preservation {org_id} [--interactive]
```

### 2. Prueba Rápida
```bash
./scripts/quick-test-idoc-6.sh {org_id}
```

### 3. Prueba Detallada
```bash
./scripts/test-idoc-preservation.sh {org_id} interactive
```

## Modificaciones Realizadas

### ACIcloudService.php
- **Almacenamiento del iDoc original**: Se modificó `storeOrder()` para capturar `dGen.iDoc` del JSON
- **Método inteligente**: Nuevo método `determineTypedocument()` que determina el tipo correcto
- **Campo TypeOfSale**: Se almacena el ID del tipo de documento correcto en `SalesHeaderImp.TypeOfSale`
- **Mapeo completo**: Soporte para todos los tipos DGI (01-10)

```php
// Nuevos métodos agregados
protected function determineTypedocument(array $data, float $total, string $originalIDoc): ?int
protected function mapIDocToCode(string $iDoc): ?string  
protected function determineByIType(string $iType, float $total): ?int

// Lógica mejorada en storeOrder()
$originalIDoc = array_get($data, 'dGen.iDoc', '1');
$typeOfSaleId = $this->determineTypedocument($data, $total, $originalIDoc);
'TypeOfSale' => $typeOfSaleId ?? intval($originalIDoc), // TIPO INTELIGENTE
```

### TestIDocPreservationCommand.php
- **Verificación en SalesHeaderImp**: Revisa campo `TypeOfSale` e `InvoiceNote`
- **Verificación en FeHeader**: Confirma que el `iDoc` se emite correctamente
- **Logs detallados**: Tracking completo del campo en todas las etapas

## Puntos de Verificación Actualizados

### 1. Entrada del Request
- `$request->dGen['iDoc']` = "6"  Verificado

### 2. Almacenamiento en SalesHeaderImp  
- `TypeOfSale` = "6"  **NUEVO: Ahora se almacena**
- `InvoiceNote` contiene `iDoc_original` = "6"  **NUEVO: Backup**

### 3. Procesamiento en CreateFast
- `$this->tipeDocument` obtiene valor de `TypeOfSale`  Verificado
- Se usa en `mount1()` para configurar emisión  Verificado

### 4. Emisión PAC
- `FeHeader.iDoc` = 6  Verificado
- XML generado mantiene tipo correcto  Verificado

### 5. Respuesta Final
- Información completa del documento procesado  Verificado

## Resultados Esperados
- **Campo preservado**: `iDoc` = "6" en todas las etapas
- **Tipo de documento**: Procesado como Nota de Crédito (tipo 6)
- **Sin transformaciones**: Valor original mantenido sin cambios
- **Trazabilidad completa**: Logs detallados en cada paso
- **Backup de datos**: JSON original conservado para auditoría

## Tipos de Documento Soportados

| iDoc | Código DGI | Descripción | Prioridad |
|------|------------|-------------|-----------|
| 1 | 01 | Factura de operación interna | |
| 2 | 02 | Factura de importación | |
| 3 | 03 | Factura de exportación | |
| 4 | 04 | Nota de Crédito referente a FE | |
| 5 | 05 | Nota de Débito referente a FE | |
| 6 | 06 | Nota de Crédito genérica | **PROBADO** |
| 7 | 07 | Nota de Débito genérica | |
| 8 | 08 | Factura de Zona Franca | |
| 9 | 09 | Reembolso | |
| 10 | 10 | Factura de Operación Extranjera | |

### Lógica de Determinación (por prioridad)

1. **ALTA**: Campo `dGen.iDoc` del JSON → Mapeo directo
2. **MEDIA**: Campo `dGen.iType` → Lógica contextual (NC, ND, FE, etc.)
3. **BAJA**: Total negativo/cero → Nota de Crédito genérica (06)
4. **DEFAULT**: Total positivo → Factura operación interna (01)

## Casos de Uso Validados
- **Nota de Crédito genérica** (iDoc = "6" → código "06") **CASO PRINCIPAL**
- Factura estándar (iDoc = "1" → código "01")
- Factura de exportación (iDoc = "3" → código "03")
- Notas de crédito con referencia (iDoc = "4" → código "04")
- Documentos con origen ACI Cloud
- Emisión síncrona con validación PAC
- Totales negativos → Automáticamente NC genérica
- Mapeo inteligente de tipos iType (NC, ND, FE, etc.)
