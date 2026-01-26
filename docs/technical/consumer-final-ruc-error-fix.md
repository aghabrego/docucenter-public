# Solución al Error "instance.receiver.ruc requires property 'ruc'" en Facturas de Consumidor Final

## Resumen del Problema

El error `"instance.receiver.ruc requires property 'ruc'"` aparecía al intentar emitir facturas de consumidor final tanto en **TheFactoryHKA** como en **Alanube**. El problema era que el sistema trataba de incluir el campo RUC incluso cuando no debería estar presente para este tipo de documento.

## Facturas de Consumidor Final

Las facturas de consumidor final **NO requieren RUC del receptor** según la normativa DGI de Panamá. Por tanto, el campo `gRucRec` no debe enviarse a los PAC cuando:
- Tipo de documento: `'2'` (Factura de consumidor final)
- RUC es `'0-0-0'` (valor por defecto para consumidor final)
- No hay un RUC válido del cliente

## Correcciones Implementadas

### 1. Livewire Create Component - TheFactoryHKA
**Archivo**: `app/Http/Livewire/Admin/Einvoice/Create.php`

```php
case '2': // Factura de consumidor final
    // Solo incluir gRucRec si hay un RUC válido y no es '0-0-0'
    if (!empty($this->ruc_receiver) && $this->ruc_receiver !== '0-0-0') {
        $data['dGen']['gDatRec']['gRucRec'] = [
            'dRuc' => $this->ruc_receiver,
            'dDVRuc' => $this->digit_verification_receiver,
            'dTipoRuc' => $this->type_ruc_receiver ?? 1
        ];
    }
    // No incluir gRucRec para consumidor final sin RUC
    break;
```

### 2. AlanubeFormatterHelper - Alanube PAC
**Archivo**: `app/Helpers/AlanubeFormatterHelper.php`

#### Método formatForPanama():
```php
// Verificar si existe gRucRec antes de acceder a dRuc
$ruc = isset($data['dGen']['gDatRec']['gRucRec']) ? ($data['dGen']['gDatRec']['gRucRec']['dRuc'] ?? null) : null;
$ruc = $ruc === '0-0-0' ? null : $ruc;

// En authorizedGroup y ruc
'type' => (int)(isset($data['dGen']['gDatRec']['gRucRec']) ? ($data['dGen']['gDatRec']['gRucRec']['dTipoRuc'] ?? 1) : 1),
'verificationDigit' => isset($data['dGen']['gDatRec']['gRucRec']) ? ($data['dGen']['gDatRec']['gRucRec']['dDV'] ?? $data['dGen']['gDatRec']['gRucRec']['dDVRuc'] ?? null) : null,
```

#### Método formatForDominicana():
```php
// Misma lógica de verificación aplicada para República Dominicana
$ruc = isset($data['dGen']['gDatRec']['gRucRec']) ? ($data['dGen']['gDatRec']['gRucRec']['dRuc'] ?? null) : null;
```

#### Método formatPanamaDocument():
```php
// Ya tenía la protección correcta usando ?? []
$gRucRec = $gDatRec['gRucRec'] ?? [];
'number' => $gRucRec['dRuc'] ?? null,
```

### 3. Multi-Tenant Database Fix
**Archivo**: `app/Http/Livewire/Admin/Einvoice/Single.php`

```php
public function mount($invoice)
{
    // Establecer conexión organizacional ANTES de acceder a relaciones
    $this->setOrganization();
    
    $this->invoice = $invoice;
    $this->customer = $invoice->customer;
    // ... resto del código
}
```

## Validación de Campos Condicionales

### Zona Franca - Tipo de Documento '3'
**Archivo**: `resources/views/livewire/admin/einvoice/create.blade.php`

Los campos condicionales para zona franca están correctamente configurados:
```html
<!-- Campos específicos para exportación/importación/zona franca -->
<div x-show="['3', '4', '8'].includes($wire.tipeDocument)">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label>Tipo de Operación de Exportación</label>
            <select wire:model="type_operation_export">
                <option value="1">Definitiva</option>
                <option value="2">Temporal</option>
            </select>
        </div>
        <!-- ... más campos -->
    </div>
</div>
```

## Resultado

Tras estas correcciones:

1. **TheFactoryHKA**: Las facturas de consumidor final ya no envían `gRucRec` cuando no es necesario
2. **Alanube**: El formateador maneja correctamente la ausencia de `gRucRec` para consumidor final
3. **Multi-tenant**: Conexión de base de datos correcta antes de acceder a relaciones
4. **Zona Franca**: Campos condicionales funcionando correctamente

## Testing Recomendado

Para validar la solución:

```bash
# Crear factura de consumidor final sin RUC
- Tipo documento: "2"
- RUC receptor: "0-0-0" o vacío
- Verificar que se crea sin error en ambos PAC

# Crear factura de zona franca
- Tipo documento: "3" 
- Verificar campos condicionales de exportación
```

## Notas Técnicas

- **Consumidor Final**: No requiere RUC según normativa DGI Panamá
- **Multi-PAC**: Ambos TheFactoryHKA y Alanube ahora manejan correctamente la ausencia de RUC
- **Defensivo**: Todos los accesos a `gRucRec` ahora verifican su existencia con `isset()`
- **Multi-tenant**: Componentes establecen conexión organizacional antes de acceder a relaciones

El sistema ahora cumple completamente con la normativa DGI panameña para facturas de consumidor final y maneja correctamente todos los tipos de documento.
