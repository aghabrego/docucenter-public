# Implementación del Accessor pac_type en Modelo Pacconnection

## Problema Identificado

El código en `FeController.php` línea 1006 intentaba acceder a la propiedad `$pacConnection->pac_type`, pero este campo **no existe físicamente** en la base de datos del modelo `Pacconnection`.

```php
// ANTES - Error: Campo no existe
if (strpos($pacConnection->pac_type, 'alanube') === false) {
    // Error: Call to undefined property
}
```

## Solución Implementada

### 1. Accessor Virtual en el Modelo

**Archivo**: `app/Models/Pacconnection.php`

```php
/**
 * Accessor para pac_type - Determina el tipo de PAC basado en name y endpoint
 *
 * @return string|null
 */
public function getPacTypeAttribute(): ?string
{
    if ($this->name === 'alanube') {
        if (strpos($this->endpoint ?? '', '/pan/v1') !== false) {
            return 'alanube_panama';
        } elseif (strpos($this->endpoint ?? '', '/dom/v1') !== false) {
            return 'alanube';
        }
        // Fallback para configuraciones Alanube sin endpoint específico
        return 'alanube';
    }

    if ($this->name === 'TheFactoryHKA') {
        return 'thefactoryhka';
    }

    if ($this->name === 'edocs') {
        return 'edocs';
    }

    // Retornar el nombre si no coincide con tipos conocidos
    return $this->name;
}
```

### 2. Documentación del Modelo Actualizada

```php
/**
 * @property-read string|null $pac_type Tipo de PAC determinado automáticamente (accessor virtual)
 */
```

## Casos de Uso Soportados

| Name | Endpoint | pac_type Result |
|------|----------|-----------------|
| `alanube` | `*/pan/v1*` | `alanube_panama` |
| `alanube` | `*/dom/v1*` | `alanube` |
| `alanube` | `otros` | `alanube` |
| `TheFactoryHKA` | `cualquiera` | `thefactoryhka` |
| `edocs` | `cualquiera` | `edocs` |
| `otros` | `cualquiera` | `[nombre]` |

## Beneficios

1. **Retrocompatibilidad**: El código existente que usa `$pacConnection->pac_type` ahora funciona
2. **Detección automática**: Se determina el tipo basado en configuración existente
3. **Flexibilidad**: Soporta múltiples proveedores PAC
4. **Mantenibilidad**: Lógica centralizada en el modelo

## 🧪 Testing

### Casos Probados
```
alanube + /pan/v1 → alanube_panama
alanube + /dom/v1 → alanube
TheFactoryHKA → thefactoryhka
edocs → edocs
```

### Comando de Prueba
```bash
php artisan test:pac-type-accessor
```

## Uso en el Código

### Ahora Funciona Correctamente
```php
// En FeController.php línea 1006
if (strpos($pacConnection->pac_type, 'alanube') === false) {
    return response()->json([
        'success' => false,
        'message' => 'La consulta de RUC solo está disponible para conexiones PAC de Alanube',
        'error' => 'PAC_NOT_SUPPORTED',
        'pac_type' => $pacConnection->pac_type  // Ahora funciona
    ], 400);
}
```

### Detección de País
```php
switch ($pacConnection->pac_type) {
    case 'alanube_panama':
        // Lógica específica para Panamá
        break;
    case 'alanube':
        // Lógica para República Dominicana
        break;
    case 'thefactoryhka':
        // Lógica para TheFactoryHKA
        break;
}
```

## Datos de Ejemplo

Con la configuración proporcionada:
```json
{
    "name": "alanube",
    "endpoint": "https://sandbox-api.alanube.co/pan/v1"
}
```

**Resultado**: `$pacConnection->pac_type` = `"alanube_panama"`

## Estado Actual

- Accessor implementado en modelo
- Sintaxis verificada
- Testing completado
- Documentación actualizada
- Retrocompatibilidad garantizada

**El código `$pacConnection->pac_type` ahora funciona correctamente en toda la aplicación.**
