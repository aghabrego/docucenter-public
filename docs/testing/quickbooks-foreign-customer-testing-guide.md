# Testing Script: QuickBooks Foreign Customer Creation

## Comando de Testing

### Crear el comando
```bash
# El comando ya está creado en:
# app/Console/Commands/TestForeignCustomerCreation.php

# Ejecutar testing básico
php artisan test:foreign-customer

# Testing con parámetros específicos
php artisan test:foreign-customer --org-id=1 --country=Chile --passport=XYZABC123 --name="Juan Pérez"

# Testing interactivo
php artisan test:foreign-customer --interactive
```

## Casos de Prueba

### Test 1: País en CustomerRef (nivel superior)
```json
{
  "CustomerRef": {
    "value": "TEST-001",
    "name": "Juan Perez",
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "XYZABC123",
    "Country": "Chile",
    "PrimaryEmail": "test@example.com",
    "BillAddr": {
      "Line1": "Test Address 123",
      "City": "Test City",
      "PostalCode": "12345"
    }
  }
}
```

**Expected Result**: `Country = "Chile"`

### Test 2: País en BillAddr
```json
{
  "CustomerRef": {
    "value": "TEST-002",
    "name": "Juan Perez (BillAddr)",
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "XYZABC1232",
    "PrimaryEmail": "test2@example.com",
    "BillAddr": {
      "Line1": "Test Address 456",
      "City": "Test City 2",
      "Country": "Chile",
      "PostalCode": "67890"
    }
  }
}
```

**Expected Result**: `Country = "Chile"`

### Test 3: Sin País (default)
```json
{
  "CustomerRef": {
    "value": "TEST-003",
    "name": "Juan Perez (No Country)",
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "XYZABC1233",
    "PrimaryEmail": "test3@example.com",
    "BillAddr": {
      "Line1": "Test Address 789",
      "City": "Test City 3",
      "PostalCode": "99999"
    }
  }
}
```

**Expected Result**: `Country = "PA"` (default)

## Validaciones

### Extracción de Datos
- ✅ **País**: `array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA'))`
- ✅ **Pasaporte**: `array_get($customerRef, 'PASAPORTE')`
- ✅ **Tipo Receptor**: `array_get($customerRef, 'TIPO_RECEPTOR', '02')`
- ✅ **Dirección**: `array_get($customerRef, 'BillAddr.Line1', '')`

### Estructura de Salida
```php
[
    'CustomerID' => 'TEST-001',
    'Customer_Bill_Name' => 'Juan Perez',
    'Ruc' => null,
    'Dv' => null,
    'TIPO' => null,
    'TIPO_RECEPTOR' => '04',
    'ReceiverType' => '2',
    'LocationCode' => '12345',
    'Email' => 'test@example.com',
    'AddressLine1' => 'Test Address 123',
    'AddressLine2' => '',
    'Country' => 'Chile',           // ✅ Extraído correctamente
    'PASAPORTE' => 'XYZABC123',     // ✅ Extraído correctamente
]
```

## Comando de Testing Manual

### Testing Directo en Base de Datos
```bash
# Conectar a la organización específica
php artisan tinker

# Simular creación de cliente extranjero
$org = \App\Models\Organization::find(1);
DB::connection()->useDatabase($org->database);

$testData = [
    'CustomerID' => 'TEST-FOREIGN',
    'Customer_Bill_Name' => 'Testing Foreign Customer',
    'Country' => 'Chile',
    'PASAPORTE' => 'XYZABC123',
    'TIPO_RECEPTOR' => '04'
];

// Verificar que la extracción funcione
$customerRef = [
    'Country' => 'Chile',
    'PASAPORTE' => 'XYZABC123',
    'BillAddr' => ['Country' => 'Argentina']
];

// Test extraction
$country = array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA'));
echo "Extracted country: " . $country; // Should be "Chile"
```

## Verificación en Producción

### Consulta para Clientes Extranjeros
```sql
-- Verificar clientes extranjeros creados
SELECT 
    CustomerID,
    Customer_Bill_Name,
    Country,
    Custom_field1 as PASAPORTE_RUC,
    Custom_field3 as TIPO_RECEPTOR,
    created_at
FROM CustomersImp 
WHERE Custom_field3 = '04'  -- TIPO_RECEPTOR extranjero
ORDER BY created_at DESC
LIMIT 10;
```

### Verificar País Correcto
```sql
-- Contar clientes extranjeros por país
SELECT 
    Country,
    COUNT(*) as total_customers,
    Custom_field3 as TIPO_RECEPTOR
FROM CustomersImp 
WHERE Custom_field3 = '04'
GROUP BY Country, Custom_field3;
```

## Debugging

### Log de Extracción
```php
// En QuickBooksOnlineService.php, agregar logs temporales:
Log::info('QuickBooks Customer Data Extract', [
    'country_root' => array_get($customerRef, 'Country'),
    'country_billaddr' => array_get($customerRef, 'BillAddr.Country'),
    'passport' => array_get($customerRef, 'PASAPORTE'),
    'final_country' => array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA'))
]);
```

### Verificar Estructura de Datos
```bash
# Ver estructura completa del CustomerRef en logs
tail -f storage/logs/laravel.log | grep "Customer Data Extract"
```

## Casos Edge

### 1. Múltiples Estructuras de País
- CustomerRef.Country existe → usar ese
- CustomerRef.Country no existe, pero BillAddr.Country sí → usar BillAddr.Country  
- Ninguno existe → usar 'PA' como default

### 2. Pasaporte Vacío
- Si TIPO_RECEPTOR = '04' pero PASAPORTE está vacío → cambiar a consumidor final ('02')

### 3. País Inválido
- Validar que el país sea un código ISO válido
- Considerar mapeo de países completos a códigos ISO

## Métricas de Éxito

### ✅ Criterios de Aceptación
1. **País extraído correctamente** de CustomerRef.Country
2. **País extraído correctamente** de CustomerRef.BillAddr.Country  
3. **Default 'PA'** cuando no hay país especificado
4. **Pasaporte extraído correctamente** de CustomerRef.PASAPORTE
5. **TIPO_RECEPTOR '04'** para clientes extranjeros
6. **Sin errores** en la creación de clientes

### 📊 KPIs
- **0 clientes extranjeros** con Country = 'PA' (a menos que sean realmente de Panamá)
- **100% clientes extranjeros** con PASAPORTE no vacío  
- **100% clientes extranjeros** con TIPO_RECEPTOR = '04'

---

*Testing Guide actualizado: $(date)*
