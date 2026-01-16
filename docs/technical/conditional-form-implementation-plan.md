# Plan de Implementación de Campos Condicionales en Create.blade.php

## Análisis del Formulario Actual

### Estructura Actual por Pasos
1. **Paso 1**: Tipo de documento (tipeDocument) - ✅ Ya existe
2. **Paso 2**: Operación (naturalezaOperacion, tipoTransaccionVenta, tipoOperacion, destinoOperacion)
3. **Paso 4**: Receptor (receptor_tipo y campos asociados)
4. **Paso 5**: Items/productos
5. **Paso 6**: Otros valores (retenciones, pagos)
6. **Paso 7**: Resumen

### Campos Condicionales Necesarios

#### 1. Campos de Exportación (Solo tipo 03)
**Ubicación sugerida**: Nuevo paso 3 o dentro del paso 2

```blade
<!-- Campos exportación - Solo si tipeDocument = '3' -->
<div x-show="$wire.tipeDocument === '3'" class="export-fields">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-condicionesEntrega" class="control-label">{{ __('Delivery Conditions (INCOTERMS)') }}</label>
                <select id="input-condicionesEntrega" wire:model.lazy="condicionesEntrega" class="form-control @error('condicionesEntrega') is-invalid @enderror">
                    @foreach (\App\Utils\DataProvider::incoterms() ?? [] as $key => $option)
                        <option value="{{ $key }}">{{ $option }}</option>
                    @endforeach
                </select>
                @error('condicionesEntrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-monedaExportacion" class="control-label">{{ __('Export Currency') }}</label>
                <select id="input-monedaExportacion" wire:model.lazy="monedaExportacion" class="form-control @error('monedaExportacion') is-invalid @enderror">
                    @foreach (\App\Utils\DataProvider::exportCurrencies() ?? [] as $key => $option)
                        <option value="{{ $key }}">{{ $option }}</option>
                    @endforeach
                </select>
                @error('monedaExportacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    
    <div class="row" x-show="$wire.monedaExportacion && $wire.monedaExportacion !== 'USD'">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-tipoCambio" class="control-label">{{ __('Exchange Rate') }}</label>
                <input type="number" step="0.0001" id="input-tipoCambio" wire:model.lazy="tipoCambio" class="form-control @error('tipoCambio') is-invalid @enderror">
                @error('tipoCambio') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-montoMonedaExtranjera" class="control-label">{{ __('Amount in Foreign Currency') }}</label>
                <input type="number" step="0.01" id="input-montoMonedaExtranjera" wire:model.lazy="montoMonedaExtranjera" class="form-control @error('montoMonedaExtranjera') is-invalid @enderror">
                @error('montoMonedaExtranjera') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="input-puertoEmbarque" class="control-label">{{ __('Port of Shipment') }}</label>
                <input type="text" id="input-puertoEmbarque" wire:model.lazy="puertoEmbarque" class="form-control @error('puertoEmbarque') is-invalid @enderror">
                @error('puertoEmbarque') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>
```

#### 2. Campos de Referencia (Solo tipos 04 y 05)
**Ubicación sugerida**: Nuevo paso 3 o dentro del paso 2

```blade
<!-- Campos referencia - Solo si tipeDocument = '4' o '5' -->
<div x-show="['4','5'].includes($wire.tipeDocument)" class="reference-fields">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="input-cufeReferenciado" class="control-label">{{ __('Referenced CUFE') }}</label>
                <input type="text" id="input-cufeReferenciado" wire:model.lazy="cufeReferenciado" class="form-control @error('cufeReferenciado') is-invalid @enderror" maxlength="100">
                @error('cufeReferenciado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-rucEmisorReferenciado" class="control-label">{{ __('Referenced Issuer RUC') }}</label>
                <input type="text" id="input-rucEmisorReferenciado" wire:model.lazy="rucEmisorReferenciado" class="form-control @error('rucEmisorReferenciado') is-invalid @enderror">
                @error('rucEmisorReferenciado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-nombreEmisorReferenciado" class="control-label">{{ __('Referenced Issuer Name') }}</label>
                <input type="text" id="input-nombreEmisorReferenciado" wire:model.lazy="nombreEmisorReferenciado" class="form-control @error('nombreEmisorReferenciado') is-invalid @enderror">
                @error('nombreEmisorReferenciado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-fechaDocumentoReferenciado" class="control-label">{{ __('Referenced Document Date') }}</label>
                <input type="date" id="input-fechaDocumentoReferenciado" wire:model.lazy="fechaDocumentoReferenciado" class="form-control @error('fechaDocumentoReferenciado') is-invalid @enderror">
                @error('fechaDocumentoReferenciado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="input-numeroDocumentoReferenciado" class="control-label">{{ __('Referenced Document Number') }}</label>
                <input type="text" id="input-numeroDocumentoReferenciado" wire:model.lazy="numeroDocumentoReferenciado" class="form-control @error('numeroDocumentoReferenciado') is-invalid @enderror">
                @error('numeroDocumentoReferenciado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>
```

#### 3. Campos de Receptor Extranjero (Mejorar existente)
**Ubicación**: Modificar paso 4 existente

```blade
<!-- Extranjero Form - Mejorar existente -->
<div x-show="receptor_tipo === '4' || $wire.tipeDocument === '3'" @change="isFormComplete = checkFormCompletion()">
    <div class="form-group">
        <label for="input-tipoIdentificacionExtranjero" class="control-label">{{ __('Foreign ID Type') }}</label>
        <select id="input-tipoIdentificacionExtranjero" wire:model.lazy="tipoIdentificacionExtranjero" class="form-control @error('tipoIdentificacionExtranjero') is-invalid @enderror">
            <option value="1">{{ __('Passport') }}</option>
            <option value="2">{{ __('Foreign Tax ID') }}</option>
            <option value="3">{{ __('Other ID') }}</option>
        </select>
        @error('tipoIdentificacionExtranjero') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="form-group">
        <label for="input-numeroIdentificacionExtranjero" class="control-label">{{ __('Foreign ID Number') }}</label>
        <input type="text" id="input-numeroIdentificacionExtranjero" wire:model.lazy="numeroIdentificacionExtranjero" class="form-control @error('numeroIdentificacionExtranjero') is-invalid @enderror">
        @error('numeroIdentificacionExtranjero') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="form-group">
        <label for="input-paisExtranjero" class="control-label">{{ __('Foreign Country') }}</label>
        <select id="input-paisExtranjero" wire:model.lazy="paisExtranjero" class="form-control @error('paisExtranjero') is-invalid @enderror">
            @foreach (\App\Utils\DataProvider::foreignCountries() ?? [] as $key => $option)
                <option value="{{ $key }}">{{ $option }}</option>
            @endforeach
        </select>
        @error('paisExtranjero') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <!-- Resto de campos existentes -->
</div>
```

### Modificaciones en DataProvider Necesarias

#### 1. Nuevos métodos requeridos:
```php
// En App\Utils\DataProvider
public static function incoterms($component = null) {
    // Tabla 32 de la ficha técnica
    return [
        'EXW' => 'EX Works',
        'FCA' => 'Free Carrier',
        'FAS' => 'Free Alongside Ship',
        'FOB' => 'Free On Board',
        'CFR' => 'Cost and Freight',
        'CIF' => 'Cost, Insurance and Freight',
        'CPT' => 'Carriage Paid To',
        'CIP' => 'Carriage and Insurance Paid To',
        'DAF' => 'Delivered At Frontier',
        'DES' => 'Delivered Ex Ship',
        'DEQ' => 'Delivered Ex Quay',
        'DDU' => 'Delivered Duty Unpaid',
        'DDP' => 'Delivered Duty Paid'
    ];
}

public static function exportCurrencies($component = null) {
    // Tabla 33 de la ficha técnica
    return [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'ZZZ' => 'Other Currency'
        // ... más monedas
    ];
}

public static function foreignCountries($component = null) {
    // Tabla 31 - excluir Panamá
    $allCountries = self::countries($component);
    unset($allCountries['PA']);
    return $allCountries;
}
```

### Validaciones en el Componente Livewire

#### Propiedades adicionales:
```php
// Campos exportación
public $condicionesEntrega;
public $monedaExportacion = 'USD';
public $tipoCambio;
public $montoMonedaExtranjera;
public $puertoEmbarque;

// Campos referencia
public $cufeReferenciado;
public $rucEmisorReferenciado;
public $nombreEmisorReferenciado;
public $fechaDocumentoReferenciado;
public $numeroDocumentoReferenciado;

// Campos extranjero
public $tipoIdentificacionExtranjero;
public $numeroIdentificacionExtranjero;
public $paisExtranjero;
```

#### Reglas de validación condicionales:
```php
protected function getRules()
{
    $rules = [
        // Reglas base...
    ];
    
    // Exportación (B06=03)
    if ($this->tipeDocument === '3') {
        $rules = array_merge($rules, [
            'receptor_tipo' => 'required|in:4', // Solo extranjero
            'destinoOperacion' => 'required|in:2', // Solo extranjero
            'condicionesEntrega' => 'required',
            'monedaExportacion' => 'required',
            'tipoCambio' => 'required_if:monedaExportacion,!=,USD',
            'montoMonedaExtranjera' => 'required_if:monedaExportacion,!=,USD',
            'tipoIdentificacionExtranjero' => 'required',
            'numeroIdentificacionExtranjero' => 'required',
            'paisExtranjero' => 'required|not_in:PA'
        ]);
    }
    
    // Notas de crédito/débito (B06=04,05)
    if (in_array($this->tipeDocument, ['4', '5'])) {
        $rules = array_merge($rules, [
            'cufeReferenciado' => 'required',
            'rucEmisorReferenciado' => 'required',
            'nombreEmisorReferenciado' => 'required',
            'fechaDocumentoReferenciado' => 'required|date',
            'numeroDocumentoReferenciado' => 'required'
        ]);
    }
    
    return $rules;
}
```

### Watchers y Lógica Reactiva

```php
public function updatedTipeDocument($value)
{
    // Limpiar campos condicionales al cambiar tipo
    if ($value !== '3') {
        $this->resetExportFields();
    }
    
    if (!in_array($value, ['4', '5'])) {
        $this->resetReferenceFields();
    }
    
    // Forzar receptor extranjero si es exportación
    if ($value === '3') {
        $this->receptor_tipo = '4';
        $this->destinoOperacion = '2';
    }
}

private function resetExportFields()
{
    $this->condicionesEntrega = null;
    $this->monedaExportacion = 'USD';
    $this->tipoCambio = null;
    $this->montoMonedaExtranjera = null;
    $this->puertoEmbarque = null;
}

private function resetReferenceFields()
{
    $this->cufeReferenciado = null;
    $this->rucEmisorReferenciado = null;
    $this->nombreEmisorReferenciado = null;
    $this->fechaDocumentoReferenciado = null;
    $this->numeroDocumentoReferenciado = null;
}
```

## Plan de Implementación

### Fase 1: Preparación
1. ✅ Análisis de ficha técnica completado
2. ✅ Mapeo de campos condicionales identificado
3. 🔄 Crear métodos DataProvider adicionales

### Fase 2: Backend
1. Agregar propiedades al componente Livewire
2. Implementar validaciones condicionales
3. Agregar watchers para lógica reactiva
4. Testing de validaciones

### Fase 3: Frontend
1. Modificar plantilla Blade con campos condicionales
2. Implementar lógica Alpine.js para visibilidad
3. Ajustar pasos del wizard si es necesario
4. Testing de UX

### Fase 4: Integración
1. Actualizar generación XML con nuevos campos
2. Testing con PACs (Alanube y TheFactoryHKA)
3. Validación con ficha técnica
4. Documentación de cambios

## Archivos a Modificar

1. **Componente Livewire**: `/app/Http/Livewire/Admin/Einvoice/Create.php`
2. **Plantilla Blade**: `/resources/views/livewire/admin/einvoice/create.blade.php`
3. **DataProvider**: `/app/Utils/DataProvider.php`
4. **Validaciones**: Implementar en el componente Livewire
5. **Testing**: Crear tests para validaciones condicionales

Esta implementación asegurará conformidad con la ficha técnica DGI Panamá y proporcionará una experiencia de usuario intuitiva con campos que aparecen dinámicamente según el tipo de documento seleccionado.
