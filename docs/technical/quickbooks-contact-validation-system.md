# Sistema de Validación de Contactos QuickBooks

## Descripción

Sistema completo para validar objetos de contacto de QuickBooks Online antes del procesamiento de facturación electrónica. Incluye validaciones específicas para el cumplimiento de normativas fiscales panameñas y requisitos DGI.

## Arquitectura

### Componentes Principales

1. **`QuickBooksContactValidator`** - Servicio principal de validación
2. **Validación automática en `create_sale_quickbooks`** - Integración transparente  
3. **Endpoint API independiente** - Para validaciones manuales
4. **Comando Artisan** - Para testing y debugging

## QuickBooksContactValidator

### Ubicación
```
app/Services/QuickBooks/QuickBooksContactValidator.php
```

### Métodos Principales

#### `validateContact(array $contact): array`
Validación completa del contacto con análisis detallado.

**Retorna:**
```php
[
    'is_valid' => bool,           // Estado general de validación
    'errors' => [],               // Errores críticos
    'warnings' => [],             // Advertencias no críticas
    'contact_summary' => [],      // Resumen estructurado del contacto
    'fiscal_validation' => []     // Análisis fiscal específico
]
```

#### `validateForElectronicInvoicing(array $contact): array`
Validación específica para facturación electrónica con requisitos DGI Panamá.

**Retorna:** Resultado base + campos FE:
```php
[
    // ... campos base de validateContact()
    'fe_ready' => bool,           // Listo para FE
    'fe_warnings' => [],          // Advertencias específicas FE  
    'fe_errors' => []            // Errores que bloquean FE
]
```

#### `logContactDetails(array $contact, string $context): void`
Genera logging detallado para debugging y auditoría.

### Validaciones Implementadas

#### 1. **Estructura Básica**
- ID del contacto presente
- Nombre (DisplayName o FullyQualifiedName) requerido
- Estado activo verificado
- SyncToken presente (advertencia si falta)
- MetaData con fechas de creación/actualización

#### 2. **Identificación Fiscal**
- Detección automática de persona natural vs jurídica
- Validación de nombres empresariales vs personales
- Análisis de balance financiero
- Verificación de configuración de impuestos (`Taxable`)

#### 3. **Direcciones**
- Validación de IDs de direcciones de facturación (`BillAddr`)
- Validación de IDs de direcciones de envío (`ShipAddr`)
- Detección de direcciones idénticas para facturación/envío

#### 4. **Cumplimiento Panamá**
- **CRÍTICO**: Moneda debe ser PAB (Balboa de Panamá)
- Método de entrega preferido configurado
- Detección de proyectos (`IsProject`)
- Verificación de facturación con entidad padre (`BillWithParent`)
- Identificación de trabajos/proyectos (`Job`)

#### 5. **Facturación Electrónica**
- Datos mínimos para DGI presentes
- Moneda PAB obligatoria
- Validaciones de proyectos/trabajos para FE

## Integración Automática

### En `create_sale_quickbooks`

La validación se ejecuta automáticamente antes del procesamiento:

```php
// En FeController::createSaleQuickbooks()
$this->validateQuickBooksContactForInvoice($arrRequest);
```

#### Comportamiento:
- **Éxito**: Procesamiento continúa normalmente
- **Error crítico**: Lanza excepción, retorna HTTP 500
- **Advertencias**: Se registran en logs, procesamiento continúa

#### Ubicación de Contacto
El sistema busca datos del contacto en múltiples ubicaciones:
- `Customer` - Datos directos
- `Invoice.Customer` - Contacto anidado en factura  
- `CustomerRef` - Referencia del contacto
- `Invoice.CustomerRef` - Referencia en factura

## Endpoint API Independiente

### Ruta
```
POST /api/v1/fe/validate_quickbooks_contact
```

### Uso
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"Id":"1634","DisplayName":"LUIS INFANTE","CurrencyRef":{"value":"PAB"}}' \
  http://localhost/api/v1/fe/validate_quickbooks_contact
```

### Respuesta Exitosa (HTTP 200)
```json
{
    "success": true,
    "message": "Validación de contacto completada",
    "data": {
        "is_valid": true,
        "fe_ready": true,
        "contact_summary": {
            "id": "1634",
            "display_name": "LUIS INFANTE",
            "currency": "PAB",
            "is_taxable": false,
            // ... más campos
        },
        "fiscal_validation": {
            "person_type": "natural",
            "currency_valid": true,
            "same_billing_shipping": true
        },
        "errors": [],
        "warnings": ["El contacto está marcado como NO gravado"],
        "fe_errors": [],
        "fe_warnings": []
    }
}
```

### Respuesta de Error (HTTP 400/500)
```json
{
    "success": false,
    "message": "Error durante la validación del contacto",
    "error": "Descripción del error"
}
```

## Comando Artisan

### Uso Básico
```bash
# Con datos de muestra
php artisan quickbooks:validate-contact --sample

# Con JSON personalizado
php artisan quickbooks:validate-contact --json='{"Id":"1634","DisplayName":"LUIS INFANTE"}'
```

### Salida del Comando
```
=== Validación de Contacto QuickBooks ===

VALIDACIÓN GENERAL: PASÓ

RESUMEN DEL CONTACTO:
+----------------------+-------------------+
| Campo                | Valor             |
+----------------------+-------------------+
| ID                   | 1634              |
| Nombre               | LUIS INFANTE      |
| Activo               | Sí                |
| Moneda               | PAB (Balboa de P) |
+----------------------+-------------------+

🏛ANÁLISIS FISCAL:
+-----------------------------------+--------+
| Aspecto                           | Valor  |
+-----------------------------------+--------+
| Tipo de persona                   | natural|
| Moneda válida                     | Sí     |
+-----------------------------------+--------+

FACTURACIÓN ELECTRÓNICA: LISTO
```

## Objeto de Contacto de Ejemplo

Basado en el objeto proporcionado (ID: 1634 - LUIS INFANTE):

```json
{
    "Taxable": false,
    "BillAddr": {"Id": "5231"},
    "ShipAddr": {"Id": "5231"}, 
    "Job": false,
    "BillWithParent": false,
    "Balance": 0,
    "BalanceWithJobs": 0,
    "CurrencyRef": {
        "value": "PAB",
        "name": "Balboa de Panamá"
    },
    "PreferredDeliveryMethod": "None",
    "IsProject": false,
    "ClientEntityId": "0", 
    "domain": "QBO",
    "sparse": false,
    "Id": "1634",
    "SyncToken": "0",
    "MetaData": {
        "CreateTime": "2025-10-16T13:00:19-07:00",
        "LastUpdatedTime": "2025-10-16T13:01:15-07:00"
    },
    "FullyQualifiedName": "LUIS INFANTE",
    "DisplayName": "LUIS INFANTE", 
    "PrintOnCheckName": "LUIS INFANTE",
    "Active": true,
    "V4IDPseudonym": "002083308a2db067f14a9fa540ccb096d880c7"
}
```

## Validaciones Específicas del Ejemplo

### Validaciones Exitosas
- **ID presente**: "1634"
- **Nombre válido**: "LUIS INFANTE" 
- **Moneda correcta**: "PAB" (Balboa de Panamá)
- **Persona natural**: Detectada automáticamente
- **Contacto activo**: `Active: true`
- **Direcciones válidas**: BillAddr y ShipAddr con ID "5231"

### Advertencias Detectadas  
- **No gravado con impuestos**: `Taxable: false`
- **SyncToken básico**: "0" (podría indicar problemas de sincronización)
- **Método de entrega no configurado**: "None"

### Resultado Final
- **Validación general**: PASA
- **Listo para FE**: SÍ
- **Cumple DGI Panamá**: SÍ

## Scripts de Prueba

### Script API
```bash
./scripts/test-quickbooks-contact-api.sh
```

Ejecuta múltiples casos de prueba:
1. Contacto válido (LUIS INFANTE)
2. Datos incompletos
3. Moneda incorrecta (USD)
4. Petición vacía

## Logging y Debugging

### Ubicación de Logs
```
storage/logs/laravel.log
```

### Contextos de Log
- `QuickBooks Contact Validation` - Validación automática en API
- `API QuickBooks Contact Validation` - Endpoint independiente  
- `Comando Validación Manual` - Desde comando Artisan

### Ejemplo de Log
```
[2025-10-16 20:00:00] local.INFO: QuickBooks Contact Validation {
    "customer_id": "1634",
    "customer_name": "LUIS INFANTE", 
    "validation_results": {
        "is_valid": true,
        "fe_ready": true,
        "contact_summary": {...},
        "fiscal_validation": {...}
    }
}
```

## Casos de Uso

### 1. **Validación Preventiva**
Antes de procesar facturas, verificar que el contacto cumple requisitos DGI.

### 2. **Debugging de Integraciones**
Identificar por qué ciertas facturas fallan en el procesamiento.

### 3. **Auditoría de Calidad**  
Revisar la calidad de datos de contactos en QuickBooks.

### 4. **Migración de Datos**
Validar contactos antes de migraciones o sincronizaciones masivas.

## Extensibilidad

### Agregar Nuevas Validaciones
```php
// En QuickBooksContactValidator
private function validateCustomRule(array $contact, array &$results): void
{
    // Lógica de validación personalizada
    if ($customCondition) {
        $results['errors'][] = 'Mensaje de error';
        $results['is_valid'] = false;
    }
}
```

### Personalizar Validaciones por Organización
```php
// Extender para validaciones específicas por organización
public function validateContactForOrganization(array $contact, Organization $org): array
{
    $baseValidation = $this->validateContact($contact);
    
    // Validaciones específicas de la organización
    // ...
    
    return $baseValidation;
}
```

## Conclusión

Este sistema proporciona validación robusta y comprehensiva de contactos QuickBooks, asegurando que cumplan con todos los requisitos para facturación electrónica en Panamá antes del procesamiento, reduciendo errores y mejorando la calidad de los datos fiscales.
