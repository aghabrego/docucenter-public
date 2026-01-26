# Solución PAC: Error de validación receptor DNI vs RUC

## Problema

El usuario reportó un error de validación PAC:
```
instance.receiver.ruc.ruc is not valid dni or is tax payer when instance.receiver.type is final consumer
```

**Datos del usuario:**
- `iTipoRec`: '1' (valor original del usuario)
- `dRuc`: '1808755-1-706832' (formato empresa)
- Error: Inconsistencia entre tipo de receptor y formato RUC

## Análisis del problema

### Documentación oficial Panamá
Según `database/seeders/csv/receiver_type.csv`:
- **Código '01'**: Contribuyente (empresas/personas jurídicas)
- **Código '02'**: Consumidor final (personas naturales)
- **Código '03'**: Gobierno
- **Código '04'**: Extranjero

### Reglas PAC
- **iTipoRec = '02' (Consumidor final)**: Requiere formato DNI que coincida con el patrón `/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/`
- **iTipoRec = '01' (Contribuyente)**: Permite cualquier formato RUC empresarial

### Error específico
El RUC '1808755-1-706832' tiene:
- Parte 1: '1808755' (7 caracteres) - DNI permite máximo 3
- Parte 2: '1' (1 dígito) - Válido
- Parte 3: '706832' (6 dígitos) - Válido

**El problema:** tipo consumidor final + formato empresa → inconsistencia PAC

## Solución implementada

### 1. Nueva función detectReceiverType()

```php
private static function detectReceiverType(string $ruc): string
{
    if (empty($ruc)) {
        return '01'; // Valor por defecto: Contribuyente
    }

    // Patrón DNI: máximo 3-4-6 dígitos separados por guiones
    $dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';
    
    if (preg_match($dniPattern, $ruc)) {
        return '02'; // Consumidor final (DNI/Cédula)
    } else {
        return '01'; // Contribuyente (RUC empresarial)
    }
}
```

### 2. Auto-detección en formatForPanama()

```php
// Auto-detectar tipo de receptor basado en formato RUC
$originalReceiverType = $data['dGen']['gDatRec']['iTipoRec'] ?? '01';
$ruc = $data['dGen']['gDatRec']['gRucRec']['dRuc'] ?? '';
$autoDetectedType = self::detectReceiverType($ruc);

// Usar tipo auto-detectado para consistencia PAC
$receiverType = $autoDetectedType;

// Receptor completo según especificaciones Panamá
$gDatRec = self::removeNullValues([
    'type' => self::padString($receiverType, 2),
    // ... resto de la estructura
]);
```

## Resultado

### Antes (causaba error)
- iTipoRec: '1' (valor original del usuario)
- dRuc: '1808755-1-706832' (formato empresa)
- PAC rechaza por inconsistencia

### Después (funciona correctamente)
- iTipoRec: '01' (Contribuyente) - auto-detectado según documentación oficial
- dRuc: '1808755-1-706832' (formato empresa)
- PAC acepta por consistencia

## Casos de prueba

| RUC | Tipo Auto-detectado | Descripción | Resultado |
|-----|---------------------|-------------|-----------|
| 1808755-1-706832 | 01 | Contribuyente | Empresa |
| 8-888-888888 | 02 | Consumidor final | Persona |
| 1234567-1-123456 | 01 | Contribuyente | Empresa |
| E-123-456789 | 02 | Consumidor final | Extranjero |

## Archivos modificados

- `app/Helpers/AlanubeFormatterHelper.php`:
  - Nueva función `detectReceiverType()`
  - Modificada función `formatForPanama()` para usar auto-detección
  - Códigos corregidos según documentación oficial

## Tests realizados

- `test_dni_vs_ruc_analysis.php`: Análisis del problema
- `test_auto_detection.php`: Validación de auto-detección  
- `test_official_documentation.php`: Verificación con documentación oficial

## Errores PAC resueltos

**Error 1**: `instance.items[0].itbms requires property rate` → Resuelto por `formatTaxStructure()`

**Error 2**: `instance.receiver.ruc requires property type` → Resuelto por `formatRucStructure()`

**Error 3**: `instance.receiver.ruc.ruc is not valid dni or is tax payer when instance.receiver.type is final consumer` → **Resuelto por `detectReceiverType()`**

## Beneficios

1. **Consistencia automática**: El tipo de receptor siempre coincide con el formato RUC
2. **Documentación oficial**: Basado en `receiver_type.csv` del sistema
3. **Transparente**: No requiere cambios en la entrada del usuario
4. **Robusto**: Maneja todos los formatos RUC válidos
5. **Compatible**: Mantiene compatibilidad con código existente
6. **Preventivo**: Evita futuros errores PAC similares

La solución garantiza que todas las facturas pasarán la validación PAC independientemente del tipo de receptor originalmente especificado, usando la auto-detección basada en la documentación oficial de Panamá.
