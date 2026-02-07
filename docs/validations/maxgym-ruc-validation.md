# Validación de RUC en MaxgymService

## Descripción

Se ha implementado validación automática de RUC (Registro Único de Contribuyente) en el servicio `MaxgymService` para validar los números de identificación fiscal de Panamá.

## Formatos Soportados

### RUC de Empresa
**Formato:** `[Registro Público]-[Tipo de Sociedad]-[Número de Actividad]`

**Ejemplos válidos:**
- `155757563-2-2024`
- `123456789-1-123`
- `1-1-1`

### RUC de Persona Natural
**Formato:** `[Provincia]-[Tomo]-[Asiento]`

**Ejemplos válidos:**
- `8-123-456` (Provincia 8)
- `1-456-789` (Provincia 1)
- `PE-123-456` (Panameños en el exterior)
- `13-999-888` (Provincia 13)

**Provincias válidas:** 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, PE

### Consumidor Final
**Formatos válidos:**
- `0-0-0`
- Cadena vacía
- Solo espacios

## Funcionalidades Implementadas

### 1. Métodos de Validación

#### `validateCompanyRuc(string $ruc): bool`
Valida el formato de RUC de empresa.

#### `validatePersonRuc(string $ruc): bool`  
Valida el formato de RUC de persona natural.

#### `validateRuc(string $ruc): array`
Método principal que valida cualquier tipo de RUC y retorna:
```php
[
    'valid' => bool,           // Si el RUC es válido
    'type' => string,          // 'company', 'person', 'consumer', 'unknown'
    'message' => string        // Mensaje descriptivo
]
```

### 2. Integración en createDefaultClient()

El método `createDefaultClient()` ahora:

1. **Valida automáticamente el RUC** antes de crear el cliente
2. **Lanza excepción** si el RUC es inválido
3. **Determina automáticamente el ReceiverType** basado en el tipo de RUC:
   - Empresa (`company`) → ReceiverType = '1' (Contribuyente)
   - Persona (`person`) → ReceiverType = '2' (Persona natural)
   - Consumidor (`consumer`) → ReceiverType = '2' (Consumidor final)
4. **Almacena el tipo de RUC** en `Custom_field5` para referencia

## Uso

### Ejemplo Básico
```php
$maxgymService = new MaxgymService();
$maxgymService->setOrganization($organization);

try {
    $customer = $maxgymService->createDefaultClient($organization, [
        'CustomerID' => 'CUST001',
        'Customer_Bill_Name' => 'Empresa Test S.A.',
        'Ruc' => '155757563-2-2024',  // RUC de empresa
        'Email' => 'test@empresa.com',
        'AddressLine1' => 'Calle Principal 123'
    ]);
    
    // Cliente creado exitosamente
    echo "Cliente creado: " . $customer->Customer_Bill_Name;
    echo "Tipo de RUC: " . $customer->Custom_field5; // 'company'
    echo "ReceiverType: " . $customer->Custom_field4; // '1'
    
} catch (\Exception $e) {
    // Error en validación de RUC
    echo "Error: " . $e->getMessage();
}
```

### Ejemplo con RUC de Persona
```php
$customer = $maxgymService->createDefaultClient($organization, [
    'CustomerID' => 'PERS001',
    'Customer_Bill_Name' => 'Juan Pérez',
    'Ruc' => '8-123-456',  // RUC de persona
    'Email' => 'juan@email.com'
]);

// ReceiverType será automáticamente '2' (Persona natural)
```

### Ejemplo con Consumidor Final
```php
$customer = $maxgymService->createDefaultClient($organization, [
    'CustomerID' => 'CASH',
    'Customer_Bill_Name' => 'Consumidor Final',
    'Ruc' => '0-0-0',  // Consumidor final
]);

// ReceiverType será automáticamente '2' (Consumidor final)
```

## Manejo de Errores

### RUC Inválido
```php
try {
    $customer = $maxgymService->createDefaultClient($organization, [
        'Ruc' => '123-INVALID-456'  // Formato inválido
    ]);
} catch (\Exception $e) {
    echo $e->getMessage(); 
    // "Formato de RUC inválido. Use: [RP]-[TS]-[NA] para empresas o [Provincia]-[Tomo]-[Asiento] para personas"
}
```

## Tests Incluidos

El archivo `tests/Unit/Services/MaxgymServiceRucValidationTest.php` incluye tests para:

- ✅ Validación de RUC de empresa válido
- ✅ Validación de RUC de persona válido  
- ✅ Validación de RUC inválido
- ✅ Validación de consumidor final
- ✅ Casos límite y formatos específicos de Panamá
- ✅ Determinación automática de ReceiverType
- ✅ Validación de todas las provincias de Panamá

### Ejecutar Tests
```bash
docker-compose exec laravel.test vendor/bin/phpunit tests/Unit/Services/MaxgymServiceRucValidationTest.php
```

## Beneficios

1. **Validación Automática:** No es necesario validar manualmente el RUC
2. **Prevención de Errores:** Evita crear clientes con RUCs inválidos
3. **Tipo de Cliente Automático:** Determina automáticamente si es empresa o persona
4. **Cumplimiento Fiscal:** Asegura que los RUCs cumplan con los formatos oficiales de Panamá
5. **Trazabilidad:** Almacena el tipo de RUC para auditoría

## Campos de Cliente Modificados

- `Custom_field1`: RUC validado
- `Custom_field4`: ReceiverType determinado automáticamente
- `Custom_field5`: Tipo de RUC ('company', 'person', 'consumer')

Esta implementación asegura que todos los clientes creados tengan RUCs válidos según los estándares de Panamá.
