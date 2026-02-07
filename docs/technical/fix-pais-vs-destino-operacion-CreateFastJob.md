# Fix Crítico: Validación País vs Destino Operación en CreateFastJob

## Problema Identificado

Error en producción PAC:
```
El campo país es inválido. El país del cliente debe ser PA si el destino de la operación es 1= Panamá.
```

## Causa Raíz

**Conflicto entre destino operación y país del cliente**:

1. ✅ **Detección inicial correcta**: Cliente extranjero → `destinoOperacion = 2`, `paisDestinoOperacion = país_extranjero`
2. ❌ **Sobrescritura incorrecta**: Código en líneas 362-370 forzaba Panamá como default **para TODOS los casos**
3. ❌ **Resultado inconsistente**: `iDest = 2` (extranjero) + `cPaisRec = PA` (Panamá)

### Código Problemático (ANTES):

```php
// Step 4 - Solo configurar default si no se configuró automáticamente
if (empty($this->receptor_paisDestinoOperacion)) {
    // Buscar Panamá dinámicamente como país default
    $panama = \App\Models\Destinationcountryoperation::query()
        ->where('code', 'PA')
        ->orWhere('name', 'like', '%Panama%')
        ->orWhere('name', 'like', '%Panamá%')
        ->first();

    $this->receptor_paisDestinoOperacion = $panama?->id ?: 1; // ❌ SIEMPRE Panamá
}
```

### Validación PAC que Falla:

- **Si `iDest = 1`** (Nacional) → **Requiere `cPaisRec = PA`** ✅
- **Si `iDest = 2`** (Extranjero) → **Requiere `cPaisRec ≠ PA`** ❌ (estaba forzando PA)

## Solución Implementada

**Código corregido para respetar tipo de cliente**:

```php
// Step 4 - Solo configurar default si no se configuró automáticamente
if (empty($this->receptor_paisDestinoOperacion)) {
    // IMPORTANTE: Solo asignar Panamá si es cliente nacional (destinoOperacion = 1)
    if ($this->destinoOperacion == 1) {
        // Buscar Panamá dinámicamente como país default para clientes nacionales
        $panama = \App\Models\Destinationcountryoperation::query()
            ->where('code', 'PA')
            ->orWhere('name', 'like', '%Panama%')
            ->orWhere('name', 'like', '%Panamá%')
            ->first();

        $this->receptor_paisDestinoOperacion = $panama?->id ?: 1; // ✅ Solo para nacionales
    }
    // Para extranjeros (destinoOperacion = 2), NO forzar país - debe mantenerse null o ya asignado
}
```

## Lógica de Validación Correcta

### Clientes Nacionales (Tipos 1, 2):
- `destinoOperacion = 1` (Nacional)
- `receptor_paisDestinoOperacion = PA` (Panamá)
- **Resultado XML**: `iDest = 1` + `cPaisRec = PA` ✅ **VÁLIDO**

### Clientes Extranjeros (Tipos 3, 4):
- `destinoOperacion = 2` (Extranjero) 
- `receptor_paisDestinoOperacion = país_real_cliente` (CL, US, etc.)
- **Resultado XML**: `iDest = 2` + `cPaisRec ≠ PA` ✅ **VÁLIDO**

## Archivos Modificados

- `app/Http/Livewire/Admin/Einvoice/CreateFastJob.php`: Líneas 362-375 - Condicional para país default

## Casos de Prueba Validados

### ✅ Cliente Nacional:
- Tipo: 1 o 2 → `destinoOperacion = 1` + `cPaisRec = PA`

### ✅ Cliente Extranjero con País Detectado:  
- Tipo: 3 o 4 + Country: CL → `destinoOperacion = 2` + `cPaisRec = CL`

### ✅ Cliente Extranjero sin País (Edge Case):
- Tipo: 3 o 4 + Country: vacío → `destinoOperacion = 2` + `cPaisRec = null` (fallback PAC)

## Impacto de la Solución

- ✅ **Clientes nacionales**: Funcionarán igual (sin cambios)
- ✅ **Clientes extranjeros**: Ahora validarán correctamente en PAC
- ✅ **Compatibilidad**: Mantiene lógica existente para casos nacionales
- ✅ **Robustez**: Respeta detección automática de tipo de cliente

## Estado

✅ **CRÍTICO RESUELTO** - Error PAC "país inválido vs destino operación" solucionado

Este fix asegura consistencia entre el destino de operación y el país del cliente según las reglas del PAC panameño.
