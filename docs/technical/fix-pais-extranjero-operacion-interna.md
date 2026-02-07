# Fix: Error Validación paisExtranjero en Operación Interna a Cliente Extranjero

## Problema

Al emitir una factura de operación interna (`iDest` = 1) a un cliente extranjero (`iTipoRec` = 4), se presentaba el error:

```
Error validación: El campo paisExtranjero es requerido.
```

## Análisis del Problema

### Contexto de Facturación
- **Cliente extranjero**: `iTipoRec` = 4 (persona/empresa extranjera)
- **Operación interna**: `iDest` = 1 (operación dentro de Panamá)
- **País receptor**: Debe ser 'PA' para operación interna según normativas DGI

### Conflicto de Validación
La validación previa requería:
```php
'paisExtranjero' => 'nullable|string|size:2|not_in:PA',
'receptor_paisNacionalidad' => 'required_without:paisExtranjero|nullable',
```

Esto causaba conflicto porque:
1. Para operación interna, el país receptor debe ser 'PA'
2. Pero `paisExtranjero` no podía ser 'PA' (`not_in:PA`)
3. Sin `paisExtranjero`, se requería `receptor_paisNacionalidad`

## Solución Implementada

### 1. Validación Condicional Mejorada

**Archivo**: `app/Http/Livewire/Admin/Einvoice/Create.php`

**Cambio en línea ~1642**:
```php
// País extranjero: Para operación interna permitir PA, para exportación requerir país extranjero
'paisExtranjero' => $this->destinoOperacion == '1' ? 'nullable|string|size:2' : 'nullable|string|size:2|not_in:PA',
'receptor_paisNacionalidad' => $this->destinoOperacion == '1' ? 'nullable' : 'required_without:paisExtranjero|nullable',
```

### 2. Lógica Automática de Asignación

**Agregado en línea ~1622**:
```php
// Para operación interna a cliente extranjero, asegurar que receptor_paisNacionalidad esté establecido
if ($this->destinoOperacion == '1' && empty($this->paisExtranjero) && empty($this->receptor_paisNacionalidad)) {
    // Buscar Panamá para operación interna
    $panama = \App\Models\Destinationcountryoperation::query()
        ->where('code', 'PA')
        ->orWhere('name', 'like', '%Panama%')
        ->orWhere('name', 'like', '%Panamá%')
        ->first();
    
    if ($panama) {
        $this->receptor_paisNacionalidad = $panama->id;
    } else {
        $this->receptor_paisNacionalidad = 'PA'; // Fallback
    }
}
```

## Reglas de Negocio Aplicadas

### Operación Interna (iDest = 1)
- `paisExtranjero`: **Opcional** - Puede ser 'PA' o cualquier código de 2 caracteres
- `receptor_paisNacionalidad`: **Opcional** - Se asigna automáticamente si está vacío
- `cPaisRec`: Siempre 'PA' (país receptor de la operación)

### Operación de Exportación (iDest = 2)
- `paisExtranjero`: **Restricción** - No puede ser 'PA' (`not_in:PA`)
- `receptor_paisNacionalidad`: **Requerido** si no hay `paisExtranjero`
- `cPaisRec`: Código del país extranjero de destino

## Casos de Uso Resueltos

### Caso 1: Operación Interna a Cliente Extranjero
```json
{
  "iTipoRec": 4,
  "iDest": "1",
  "cPaisRec": "PA",
  "paisExtranjero": null,
  "receptor_paisNacionalidad": "PA"
}
```
**Resultado**: Validación exitosa, asignación automática de receptor_paisNacionalidad

### Caso 2: Exportación a Cliente Extranjero
```json
{
  "iTipoRec": 4,
  "iDest": "2", 
  "cPaisRec": "US",
  "paisExtranjero": "US",
  "receptor_paisNacionalidad": null
}
```
**Resultado**: Validación exitosa con país extranjero específico

## Objeto de Datos Típico Después del Fix

```php
array:5 [
  "dGen" => array:11 [
    "iDoc" => "01"
    "iDest" => "1"  // Operación interna
    "gDatRec" => array:6 [
      "iTipoRec" => 4  // Cliente extranjero
      "cPaisRec" => "PA"  // País receptor: Panamá (operación interna)
      "dNombRec" => "Angel Hidalgo"
    ]
  ]
]
```

## Archivos Modificados

- `app/Http/Livewire/Admin/Einvoice/Create.php`
  - Validación condicional de `paisExtranjero` (línea ~1642)
  - Validación condicional de `receptor_paisNacionalidad` (línea ~1643)
  - Lógica automática de asignación (línea ~1622)

## Testing

### Comando de Verificación
```bash
# Verificar que la validación funciona correctamente
cd /home/weirdolabs/code/docucenter
# Crear factura de operación interna a cliente extranjero
# Confirmar que no hay error de validación paisExtranjero
```

### Casos de Prueba
1. **Operación interna + Cliente extranjero**: Debe validar correctamente
2. **Exportación + Cliente extranjero**: Debe requerir país extranjero válido
3. **Operación interna + Cliente nacional**: No debe verse afectado

## Normativas DGI Cumplidas

- **B410 - cPaisRec**: Código país receptor obligatorio
- **Operación Interna**: País receptor siempre 'PA' independiente del cliente
- **Cliente Extranjero**: `iTipoRec` = 4 permite identificar nacionalidad sin afectar destino de operación

## Commit

```
commit: fix: resolver error validación paisExtranjero en operación interna a cliente extranjero
```

## Fecha de Implementación

20 de Octubre, 2025
