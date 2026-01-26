# Propuesta de Actualización: Livewire Create.php para Tipos de Documento

## Campos Adicionales Requeridos

### Para implementar soporte completo de los 9 tipos de documento oficiales, necesitamos agregar los siguientes campos al componente `Create.php`:

```php
// Campos para Notas de Crédito/Débito con Referencia (04, 05)
public $documento_referencia_numero = null;
public $documento_referencia_fecha = null;
public $documento_referencia_tipo = null;
public $motivo_nota = null;

// Campos para Importación (02)
public $importacion_documento_aduanero = null;
public $importacion_agente_aduanero = null;
public $importacion_aduana_origen = null;
public $importacion_fecha_despacho = null;

// Campos para Exportación (03)
public $exportacion_incoterm = null;
public $exportacion_puerto_embarque = null;
public $exportacion_puerto_destino = null;
public $exportacion_modalidad_transporte = null;

// Campos para Zona Franca (08)
public $zona_franca_registro = null;
public $zona_franca_nombre = null;
public $zona_franca_ubicacion = null;
public $zona_franca_licencia = null;

// Campos para Reembolso (09)
public $reembolso_concepto = null;
public $reembolso_documentos_soporte = null;
public $reembolso_beneficiario = null;
public $reembolso_periodo = null;
```

## Validaciones Dinámicas por Tipo

### Actualizar método `getRules()` para validaciones condicionales:

```php
protected function getRules()
{
    $rules = [
        'tipeDocument' => 'required',
        'numeroDocumentoFiscal' => 'required|numeric|min:1',
        'fechaEmisionDocumento' => 'required|date_format:Y-m-d',
        // ... reglas base existentes
    ];

    // Validaciones específicas por tipo de documento
    $documentType = $this->getDocumentTypeCode();
    
    switch ($documentType) {
        case '04': // Nota Crédito con Referencia
        case '05': // Nota Débito con Referencia
            $rules = array_merge($rules, [
                'documento_referencia_numero' => 'required|string|max:50',
                'documento_referencia_fecha' => 'required|date|before_or_equal:' . $this->fechaEmisionDocumento,
                'motivo_nota' => 'required|string|max:500'
            ]);
            break;
            
        case '06': // Nota Crédito Genérica
        case '07': // Nota Débito Genérica
            $rules = array_merge($rules, [
                'motivo_nota' => 'required|string|max:500'
            ]);
            break;
            
        case '02': // Importación
            $rules = array_merge($rules, [
                'importacion_documento_aduanero' => 'required|string|max:100',
                'importacion_agente_aduanero' => 'required|string|max:200',
                'importacion_fecha_despacho' => 'nullable|date|before_or_equal:' . $this->fechaEmisionDocumento
            ]);
            break;
            
        case '03': // Exportación
            $rules = array_merge($rules, [
                'exportacion_incoterm' => 'required|string|max:10',
                'receptor_paisDestinoOperacion' => 'required|not_in:PA', // No puede ser Panamá
                'exportacion_modalidad_transporte' => 'nullable|string|max:50'
            ]);
            break;
            
        case '08': // Zona Franca
            $rules = array_merge($rules, [
                'zona_franca_registro' => 'required|string|max:100',
                'zona_franca_nombre' => 'required|string|max:200'
            ]);
            break;
            
        case '09': // Reembolso
            $rules = array_merge($rules, [
                'reembolso_concepto' => 'required|string|max:500',
                'reembolso_documentos_soporte' => 'required|string|max:1000'
            ]);
            break;
            
        case '01': // Operación Interna
        default:
            // Validaciones para operación interna
            if ($documentType === '01') {
                $rules['receptor_paisDestinoOperacion'] = 'nullable|in:PA'; // Solo Panamá
            }
            break;
    }

    return $rules;
}

/**
 * Obtener código del tipo de documento seleccionado
 */
protected function getDocumentTypeCode(): ?string
{
    if (empty($this->tipeDocument)) {
        return null;
    }
    
    $typeDocument = Typedocument::find($this->tipeDocument);
    return $typeDocument?->code;
}
```

## Lógica de Vista Dinámica

### JavaScript para mostrar/ocultar campos según tipo:

```javascript
// En la vista Blade, agregar JavaScript para manejo dinámico
<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentTypeSelect = document.getElementById('input-tipeDocument');
    
    // Mapeo de códigos de tipo a comportamiento
    const typeFieldsMap = {
        '4': ['reference_fields', 'note_fields'], // Nota Crédito Ref
        '5': ['reference_fields', 'note_fields'], // Nota Débito Ref  
        '6': ['note_fields'], // Nota Crédito Genérica
        '7': ['note_fields'], // Nota Débito Genérica
        '8': ['import_fields'], // Importación
        '9': ['export_fields'], // Exportación
        '10': ['free_zone_fields'], // Zona Franca
        '11': ['reimbursement_fields'] // Reembolso
    };
    
    function toggleFieldsVisibility() {
        const selectedType = documentTypeSelect.value;
        
        // Ocultar todos los campos específicos
        document.querySelectorAll('.type-specific-fields').forEach(el => {
            el.style.display = 'none';
        });
        
        // Mostrar campos relevantes
        if (typeFieldsMap[selectedType]) {
            typeFieldsMap[selectedType].forEach(fieldGroup => {
                const element = document.getElementById(fieldGroup);
                if (element) {
                    element.style.display = 'block';
                }
            });
        }
    }
    
    // Ejecutar al cambiar tipo
    documentTypeSelect.addEventListener('change', toggleFieldsVisibility);
    
    // Ejecutar al cargar
    toggleFieldsVisibility();
});
</script>
```

## Estructura de Campos en Vista

### Agregar secciones específicas en `create.blade.php`:

```html
<!-- Campos para Notas con Referencia (04, 05) -->
<div id="reference_fields" class="type-specific-fields" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-documento_referencia_numero">{{ __('Número Documento Referencia') }} <span class="text-danger">*</span></label>
                <input type="text" id="input-documento_referencia_numero" wire:model.lazy="documento_referencia_numero" 
                       class="form-control @error('documento_referencia_numero') is-invalid @enderror">
                @error('documento_referencia_numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-documento_referencia_fecha">{{ __('Fecha Documento Referencia') }} <span class="text-danger">*</span></label>
                <input type="date" id="input-documento_referencia_fecha" wire:model.lazy="documento_referencia_fecha"
                       class="form-control @error('documento_referencia_fecha') is-invalid @enderror">
                @error('documento_referencia_fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

<!-- Campos para Notas (04, 05, 06, 07) -->
<div id="note_fields" class="type-specific-fields" style="display: none;">
    <div class="form-group">
        <label for="input-motivo_nota">{{ __('Motivo de la Nota') }} <span class="text-danger">*</span></label>
        <textarea id="input-motivo_nota" wire:model.lazy="motivo_nota" rows="3"
                  class="form-control @error('motivo_nota') is-invalid @enderror"
                  placeholder="Ingrese el motivo detallado de la nota..."></textarea>
        @error('motivo_nota') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<!-- Campos para Importación (02) -->
<div id="import_fields" class="type-specific-fields" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-importacion_documento_aduanero">{{ __('Documento Aduanero') }} <span class="text-danger">*</span></label>
                <input type="text" id="input-importacion_documento_aduanero" wire:model.lazy="importacion_documento_aduanero"
                       class="form-control @error('importacion_documento_aduanero') is-invalid @enderror">
                @error('importacion_documento_aduanero') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-importacion_agente_aduanero">{{ __('Agente Aduanero') }} <span class="text-danger">*</span></label>
                <input type="text" id="input-importacion_agente_aduanero" wire:model.lazy="importacion_agente_aduanero"
                       class="form-control @error('importacion_agente_aduanero') is-invalid @enderror">
                @error('importacion_agente_aduanero') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

<!-- Similar para otros tipos... -->
```

## Método de Construcción de Documento

### Actualizar método `buildDocumentData()`:

```php
protected function buildDocumentData(): array
{
    // ... código base existente ...
    
    $documentType = $this->getDocumentTypeCode();
    
    // Agregar campos específicos según tipo
    switch ($documentType) {
        case '04': // Nota Crédito con Referencia
        case '05': // Nota Débito con Referencia
            $dGen['gNotRef'] = [
                'dNumDocRef' => $this->documento_referencia_numero,
                'dFechaRef' => $this->documento_referencia_fecha,
                'dMotivoNota' => $this->motivo_nota
            ];
            break;
            
        case '06': // Nota Crédito Genérica  
        case '07': // Nota Débito Genérica
            $dGen['gNotGen'] = [
                'dMotivoNota' => $this->motivo_nota
            ];
            break;
            
        case '02': // Importación
            $dGen['gImp'] = [
                'dDocAduanero' => $this->importacion_documento_aduanero,
                'dAgenteAduanero' => $this->importacion_agente_aduanero,
                'dFechaDespacho' => $this->importacion_fecha_despacho
            ];
            break;
            
        case '03': // Exportación
            $dGen['gExp'] = [
                'dIncoterm' => $this->exportacion_incoterm,
                'dPuertoEmb' => $this->exportacion_puerto_embarque,
                'dModalidadTransp' => $this->exportacion_modalidad_transporte
            ];
            break;
            
        case '08': // Zona Franca
            $dGen['gZF'] = [
                'dRegZonaFranca' => $this->zona_franca_registro,
                'dNomZonaFranca' => $this->zona_franca_nombre,
                'dUbicZonaFranca' => $this->zona_franca_ubicacion
            ];
            break;
            
        case '09': // Reembolso
            $dGen['gReemb'] = [
                'dConceptoReemb' => $this->reembolso_concepto,
                'dDocsSoporte' => $this->reembolso_documentos_soporte,
                'dPeriodoReemb' => $this->reembolso_periodo
            ];
            break;
    }
    
    return $request;
}
```

## 🧪 Testing

### Crear casos de prueba específicos:

```php
// En CreateTest.php
public function test_document_type_specific_validations()
{
    // Test para cada tipo de documento
    $testCases = [
        '04' => [
            'required_fields' => ['documento_referencia_numero', 'documento_referencia_fecha', 'motivo_nota'],
            'valid_data' => ['documento_referencia_numero' => 'FE-001234', 'documento_referencia_fecha' => '2025-09-20', 'motivo_nota' => 'Descuento'],
        ],
        // ... otros casos
    ];
    
    foreach ($testCases as $type => $case) {
        // Verificar que faltan campos requeridos
        $component = Livewire::test(Create::class);
        $component->set('tipeDocument', $type);
        
        foreach ($case['required_fields'] as $field) {
            $component->call('validateStep', 1);
            $component->assertHasErrors($field);
        }
        
        // Verificar que con datos válidos no hay errores
        foreach ($case['valid_data'] as $field => $value) {
            $component->set($field, $value);
        }
        
        $component->call('validateStep', 1);
        foreach ($case['required_fields'] as $field) {
            $component->assertHasNoErrors($field);
        }
    }
}
```

## Resumen de Impacto

### Cambios Requeridos:
1. **AlanubeService.php** - Constantes y endpoints (COMPLETADO)
2. **TypedocumentValidator.php** - Validaciones específicas (COMPLETADO)  
3. **TypedocumentSeeder.php** - Datos base (COMPLETADO)
4. **Create.php** - Campos y validaciones (PROPUESTO)
5. **create.blade.php** - Vista dinámica (PROPUESTO)
6. **Testing** - Casos de prueba (PROPUESTO)

### Beneficios:
- 100% cumplimiento con JSch09 iDoc oficiales
- Validaciones dinámicas por tipo
- UX mejorada con campos contextuales
- Compatibilidad con PAC providers
- Testing exhaustivo por tipo

### Riesgos Mitigados:
- Backwards compatibility mantenida
- Datos existentes no afectados
- Validación progresiva por pasos
- Rollback disponible si es necesario
