# Validación de RUC en CreateSaleMaxgymRequest

## Descripción

Se ha implementado validación automática de RUC en el `CreateSaleMaxgymRequest` que:

1. **Valida el RUC** antes de procesar el request
2. **Limpia automáticamente** RUCs inválidos convirtiéndolos a `null`
3. **Registra logs** cuando se detectan RUCs inválidos
4. **Agrega metadata** sobre el tipo de RUC detectado

## Flujo de Validación

### 1. Entrada del Request
```json
{
  "data": {
    "member": {
      "documentNumber": "155757563-2-2024",
      "name": "Empresa Test"
    }
  }
}
```

### 2. Procesamiento Automático
El método `prepareForValidation()` ejecuta automáticamente:

```php
// Valida y limpia el RUC
$rucValidation = $this->validateAndCleanRuc($data['member']['documentNumber']);

// Aplica el RUC limpio (válido o convertido a consumidor final)
$data['member']['documentNumber'] = $rucValidation['cleaned'];

// Agrega tipo de RUC para referencia
$data['member']['rucType'] = $rucValidation['type'];
```

### 3. Resultado Procesado

#### RUC Válido de Empresa:
```json
{
  "data": {
    "member": {
      "documentNumber": "155757563-2-2024",
      "rucType": "company",
      "name": "Empresa Test"
    }
  }
}
```

#### RUC Inválido (Array Limpio):
```json
{
  "data": {
    "member": {
      "documentNumber": null,
      "name": "Cliente Test"
    }
  }
}
```
*Nota: RUCs inválidos se convierten a `null` y no se agrega `rucType` para mantener el array limpio.*
```json
{
  "data": {
    "member": {
      "documentNumber": "INVALID-RUC-123"
    }
  }
}
```

### Request Procesado Automáticamente:
```json
{
  "data": {
    "member": {
      "documentNumber": null
    }
  }
}
```
*Nota: Array limpio - no se agrega `rucType` para RUCs inválidos.*
```
*Nota: No se agrega `rucType` para mantener el array limpio cuando el RUC original era inválido.*

## Casos de Uso

### RUC de Empresa Válido
**Entrada:** `155757563-2-2024`
**Resultado:** Se mantiene como `155757563-2-2024`, tipo `company`

### RUC de Persona Válido
**Entrada:** `8-123-456`
**Resultado:** Se mantiene como `8-123-456`, tipo `person`

### RUC Inválido - Auto-Corrección
**Entrada:** `123-INVALID-456`
**Resultado:** Se convierte a `null`, **sin** `rucType`
**Log:** Se registra warning con detalles del RUC original
**Beneficio:** Array limpio sin campos innecesarios

### Consumidor Final
**Entrada:** `0-0-0` o cadena vacía
**Resultado:** Se normaliza a `0-0-0`, tipo `consumer`

### RUC con Espacios
**Entrada:** `  8-123-456  `
**Resultado:** Se limpia a `8-123-456`, tipo `person`

## Tipos de RUC Soportados

### RUC de Empresa
- **Formato:** `[RegistroPublico]-[TipoSociedad]-[NumeroActividad]`
- **Ejemplos:** `155757563-2-2024`, `1-1-1`, `999999999-99-9999`
- **Tipo resultante:** `company`

### RUC de Persona
- **Formato:** `[Provincia]-[Tomo]-[Asiento]`
- **Provincias válidas:** 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, PE
- **Ejemplos:** `8-123-456`, `PE-789-012`, `13-555-888`
- **Tipo resultante:** `person`

### Consumidor Final
- **Formatos válidos:** `0-0-0`, `""`, `"   "`
- **Tipo resultante:** `consumer`

## Logging Automático

Cuando se detecta un RUC inválido, se registra automáticamente:

```php
Log::warning('Invalid RUC detected and converted to null', [
    'original_ruc' => '123-INVALID-456',
    'cleaned_ruc' => null,
    'member_name' => 'Cliente Test',
    'request_id' => 'ORDER-123'
]);
```

## Metadata Agregada

El request procesado incluye información adicional **solo para RUCs válidos**:

```php
// Solo se agrega si el RUC es válido
if ($rucValidation['valid']) {
    $data['member']['rucType'] = 'company|person|consumer';
}
// Si es inválido, no se agrega nada para mantener el array limpio
```

**Tipos agregados:**
- `company`: RUC de empresa válido
- `person`: RUC de persona válido  
- `consumer`: Consumidor final (0-0-0 o vacío original)

**No se agrega nada cuando:**
- RUC original era inválido (se convierte a `null` silenciosamente)

## Ventajas de esta Implementación

### 🛡**Prevención de Errores**
- Evita que lleguen RUCs inválidos al servicio
- Convierte automáticamente a consumidor final

### **Auto-Corrección**
- No rechaza requests con RUCs inválidos
- Los convierte silenciosamente a consumidor final
- Mantiene la funcionalidad del sistema

### **Trazabilidad**
- Registra todos los casos de RUCs inválidos
- Permite auditoría y análisis de calidad de datos

### **Compatibilidad**
- No rompe la funcionalidad existente
- Se integra transparentemente en el flujo

## Ejemplo de Integración

### Antes (sin validación)
```php
// Request llegaba directo al servicio
$service->createDefaultClient($organization, [
    'Ruc' => '123-INVALID-456', // Podía causar error
    'Customer_Bill_Name' => 'Cliente Test'
]);
```

### Después (con validación automática)
```php
// Request se valida automáticamente en prepareForValidation()
// Si RUC es inválido, se convierte a null
// Service recibe datos ya limpios
$service->createDefaultClient($organization, [
    'Ruc' => null, // Siempre válido
    'Customer_Bill_Name' => 'Cliente Test'
]);
```

## Tests Incluidos

### Cobertura de Tests
- RUC de empresa válido se preserva con `rucType`
- RUC de persona válido se preserva con `rucType`
- RUC inválido se convierte a consumidor final **sin** `rucType`
- RUC vacío se normaliza a consumidor final con `rucType: 'consumer'`
- RUC con espacios se limpia correctamente
- Todas las provincias de Panamá (1-13, PE)
- Array limpio para casos inválidos
- Request sin documentNumber no falla

### Ejecutar Tests
```bash
# Test básico de validación
docker-compose exec laravel.test vendor/bin/phpunit tests/Unit/Http/Requests/CreateSaleMaxgymRequestRucTest.php

# Test específico de array limpio
docker-compose exec laravel.test vendor/bin/phpunit tests/Unit/Http/Requests/CreateSaleMaxgymRequestCleanArrayTest.php
```

**Resultado:** 
- Test básico: 3 tests, 38 assertions - 
- Test array limpio: 7 tests, 33 assertions - 

## Flujo Completo

```mermaid
graph TD
    A[Request Recibido] --> B[prepareForValidation()]
    B --> C{¿Existe documentNumber?}
    C -->|No| D[Continúa sin cambios]
    C -->|Sí| E[validateAndCleanRuc()]
    E --> F{¿RUC Válido?}
    F -->|Sí| G[Mantener RUC + Tipo]
    F -->|No| H[Convertir a null + Log Warning]
    G --> I[Agregar rucType]
    H --> I
    I --> J[Request Procesado]
    J --> K[Enviar a Servicio]
```

Esta implementación asegura que el `MaxgymService` siempre reciba RUCs válidos, mejorando la robustez y confiabilidad del sistema.
