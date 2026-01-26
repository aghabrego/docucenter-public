# Validación de Integridad de Datos - APIs Customer

## Resumen

Se ha implementado validación de integridad de datos para las APIs `customer_receipt_imp` y `customer_credit_memo` utilizando el campo `ParentTransactionId` que debe existir en la tabla `sales_header_imp`.

## APIs Modificadas

### 1. customer_receipt_imp
- **Endpoint**: `POST /api/customer_receipt_imp`
- **Controller**: `ACIcloudController@customerReceiptImp`
- **Form Request**: `CustomerReceiptImpRequest`
- **Validación Nueva**: `ParentTransactionId` debe existir en `sales_header_imp.ID`

### 2. customer_credit_memo
- **Endpoint**: `POST /api/customer_credit_memo`
- **Controller**: `ACIcloudController@customerCreditMemoImp`
- **Form Request**: `CustomerCreditMemoImpRequest`
- **Validación Nueva**: `ParentTransactionId` debe existir en `sales_header_imp.ID`

## Implementación Técnica

### Reglas de Validación

**CustomerReceiptImpRequest.php**:
```php
public function rules()
{
    return [
        // ... reglas existentes
        'ParentTransactionId' => 'nullable|integer|exists:App\Models\SalesHeaderImp,ID',
    ];
}
```

**CustomerCreditMemoImpRequest.php**:
```php
public function rules()
{
    return [
        // ... reglas existentes
        'ParentTransactionId' => 'nullable|integer|exists:App\Models\SalesHeaderImp,ID',
    ];
}
```

### Mensajes Personalizados

Ambos Form Requests incluyen mensajes personalizados en español:

```php
public function messages()
{
    return [
        // ... mensajes existentes
        'ParentTransactionId.exists' => 'El ID de transacción padre debe existir en la tabla de encabezados de ventas.',
    ];
}
```

## Características de la Validación

### 1. Campo Nullable
- **Comportamiento**: El campo `ParentTransactionId` es opcional
- **Uso**: Permite crear transacciones sin referencia padre
- **Validación**: Solo se valida existencia si el campo tiene valor

### 2. Validación de Existencia
- **Modelo**: `App\Models\SalesHeaderImp`
- **Columna**: `ID`
- **Tipo**: Laravel `exists` rule
- **Propósito**: Garantizar integridad referencial
- **Ventaja**: Usa Eloquent ORM para mejor mantenimiento

### 3. Mensaje de Error
- **Idioma**: Español
- **Contexto**: Específico para cada API
- **Claridad**: Explica qué tabla debe contener el ID

## Archivos Modificados

### Form Requests
1. `app/Http/Requests/CustomerReceiptImpRequest.php`
   - Agregada regla de validación para `ParentTransactionId`
   - Agregado mensaje personalizado en español

2. `app/Http/Requests/CustomerCreditMemoImpRequest.php`
   - Agregada regla de validación para `ParentTransactionId`
   - Agregado mensaje personalizado en español

### Archivos de Testing
1. `docs/testing/test-customer-apis-validation.php`
   - Script PHP completo para pruebas unitarias
   - Casos de prueba para validaciones válidas e inválidas

2. `docs/testing/test-customer-validation.sh`
   - Script bash ejecutable para pruebas desde terminal
   - Diferentes modos de testing (database, receipt, credit, complete)

## Casos de Uso

### Caso 1: Transacción con Referencia Padre
```json
{
    "CustomerID": "CUST001",
    "ReceiptNumber": "REC001",
    "ParentTransactionId": 123,
    "Details": [...]
}
```
**Resultado**: Válido si ID 123 existe en `SalesHeaderImp`

### Caso 2: Transacción sin Referencia Padre
```json
{
    "CustomerID": "CUST001",
    "ReceiptNumber": "REC002",
    "Details": [...]
}
```
**Resultado**: Válido (campo nullable)

### Caso 3: Referencia Padre Inexistente
```json
{
    "CustomerID": "CUST001",
    "ReceiptNumber": "REC003",
    "ParentTransactionId": 999999,
    "Details": [...]
}
```
**Resultado**: Error - "El ID de transacción padre debe existir en la tabla de cabeceras de ventas."

## Testing

### Ejecutar Pruebas Completas
```bash
cd /home/weirdolabs/code/docucenter
./docs/testing/test-customer-validation.sh complete
```

### Pruebas Específicas
```bash
# Solo verificar tabla
./docs/testing/test-customer-validation.sh database

# Solo customer receipt
./docs/testing/test-customer-validation.sh receipt

# Solo customer credit memo
./docs/testing/test-customer-validation.sh credit
```

### Requisitos para Testing
1. **Tabla sales_header_imp debe existir**
2. **Debe tener al menos un registro con ID=1**
3. **Laravel debe estar configurado correctamente**

## Beneficios de la Implementación

### 1. Integridad de Datos
- Previene referencias a transacciones inexistentes
- Mantiene consistencia en la base de datos
- Evita errores en procesos posteriores

### 2. Validación Temprana
- Errores detectados en la API antes del procesamiento
- Respuestas claras al cliente sobre problemas de datos
- Reducción de debugging en capas posteriores

### 3. Flexibilidad
- Campo nullable permite transacciones independientes
- Validación solo cuando es necesario
- Compatible con flujos existentes

## Consideraciones Futuras

### 1. Performance
- La validación `exists` genera consulta adicional a BD
- Considerar cache para IDs válidos frecuentes
- Monitorear impacto en APIs de alto volumen

### 2. Extensibilidad
- Patrón replicable para otras APIs que requieran validación
- Base para validaciones más complejas (ej: estado de transacción)
- Framework para otras tablas de referencia

### 3. Monitoreo
- Logs de errores de validación para análisis
- Métricas de éxito/fallo por API
- Alertas para patrones de errores inusuales

## Documentación Relacionada

- [Testing System Summary](../TESTING_SYSTEM_SUMMARY.md)
- [API Documentation](../api/)
- [Database Schema](../technical/database-schema.md)

---

**Autor**: Equipo DocuCenter  
**Fecha**: $(date)  
**Versión**: 1.0  
**Estado**: Implementado y Probado
