# Guía: Entrenar Google Document AI para Facturas Panameñas

## Opción 1: Custom Document Extractor (Recomendado)

### Paso 1: Preparar Dataset de Entrenamiento
```bash
# Recopilar 20-50 facturas representativas
facturas_entrenamiento/
  ├── factura_001.pdf
  ├── factura_002.pdf
  ├── ...
  └── factura_050.pdf
```

### Paso 2: Crear Processor Personalizado

1. **Acceder a Document AI Console**
   - https://console.cloud.google.com/ai/document-ai/processors
   - Proyecto: `docucenter-aci`

2. **Crear Custom Extractor**
   ```
   - Click "CREATE PROCESSOR"
   - Seleccionar "Document Splitter and Classifier" 
     O "Custom Document Extractor"
   - Región: us (mantener consistencia)
   - Nombre: "Panama Invoice Extractor"
   ```

3. **Anotar Documentos**
   - Subir las 50 facturas
   - Para cada factura, anotar:
     * `vendor_name`: Nombre del proveedor
     * `vendor_ruc`: RUC (formato: 123456789-1-2020)
     * `invoice_number`: Número de factura/compra
     * `invoice_date`: Fecha de emisión
     * `due_date`: Fecha de vencimiento
     * `subtotal`: Subtotal sin impuestos
     * `itbms`: Impuesto ITBMS
     * `total_amount`: Total a pagar
     * `line_items`: Items de línea (descripción, cantidad, precio, total)

4. **Entrenar Modelo**
   ```
   - Click "TRAIN NEW VERSION"
   - Esperar 1-2 horas
   - Evaluar precisión con test set
   ```

5. **Obtener Processor ID**
   ```
   Formato: projects/{project}/locations/{location}/processors/{processor-id}
   Ejemplo: projects/docucenter-aci/locations/us/processors/abc123def456
   ```

### Paso 3: Actualizar Código

```php
// En .env
GOOGLE_DOCUMENT_AI_PROCESSOR_ID=abc123def456  # Tu nuevo processor ID
```

## Opción 2: Post-Procesamiento Mejorado (Implementado)

Ya implementamos patrones regex que extraen del texto OCR:
- ✅ Número de factura: `COMPRA Nro.60825`
- ✅ RUC: `155764420-2-2025`
- ✅ Nombre proveedor: Detecta S.A., S.R.L., etc.
- ✅ Totales: `Total Operación: B/.285.00`

### Mejoras Adicionales Posibles

```php
// En app/Services/DocumentAIService.php
protected function enhanceDataFromText(array $data): array
{
    // Agregar más patrones específicos para facturas panameñas
    
    // Detectar DV (Dígito Verificador)
    if (preg_match('/DV\s*[:\.]?\s*(\d+)/', $text, $matches)) {
        $data['entities']['vendor_dv'] = [
            'value' => $matches[1],
            'confidence' => 0.90,
            'type' => 'verification_digit'
        ];
    }
    
    // Detectar nombre del cliente
    if (preg_match('/Nombre:\s*([A-ZÀ-ÿ\s,\.&]+)/i', $text, $matches)) {
        $data['entities']['customer_name'] = [
            'value' => trim($matches[1]),
            'confidence' => 0.85,
            'type' => 'customer_name'
        ];
    }
    
    // Más patrones según necesites...
}
```

## Opción 3: Hybrid Approach (Mejor Balance)

Combinar Document AI base + Post-procesamiento + Validaciones:

```php
protected function enhanceDataFromText(array $data): array
{
    $text = $data['text'] ?? '';
    
    // 1. Intentar con Document AI primero
    // 2. Si confianza baja o datos faltantes, usar regex
    // 3. Validar coherencia (subtotal + tax = total)
    
    $this->validateFinancialConsistency($data);
    
    return $data;
}

protected function validateFinancialConsistency(array &$data): void
{
    $subtotal = $data['entities']['subtotal']['value'] ?? 0;
    $tax = $data['entities']['tax_amount']['value'] ?? 0;
    $total = $data['entities']['total_amount']['value'] ?? 0;
    
    $calculatedTotal = $subtotal + $tax;
    
    // Si hay inconsistencia mayor al 1%, loguear advertencia
    if (abs($calculatedTotal - $total) > ($total * 0.01)) {
        Log::warning('Inconsistencia en totales de factura', [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total_declared' => $total,
            'total_calculated' => $calculatedTotal
        ]);
    }
}
```

## Costos Estimados

### Processor Pre-entrenado (Actual)
- $1.50 por 1,000 páginas
- Sin costo de entrenamiento

### Custom Processor
- Entrenamiento: $25-50 una vez
- Procesamiento: $3.00 por 1,000 páginas
- Mayor precisión: +20-30%

## Recomendación

**Corto plazo (Ahora):**
- Usar post-procesamiento mejorado (implementado)
- Monitorear logs de confianza
- Recopilar facturas problemáticas

**Mediano plazo (1-2 meses):**
- Si >100 facturas/mes: Entrenar custom processor
- Si <100 facturas/mes: Mantener approach híbrido

**Largo plazo:**
- Refinar modelo custom con feedback
- Agregar más tipos de documentos (notas de crédito, etc.)
