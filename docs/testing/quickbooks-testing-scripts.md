# Scripts de Testing para QuickBooks Integration

Scripts de prueba completos para validar la integración con QuickBooks Online, específicamente el sistema de cálculo de impuestos ITBMS.

## Scripts Disponibles

### 1. `test-qb-tax-validation.php`
**Propósito**: Validación simplificada de cálculos de impuestos  
**Uso**: `php docs/testing/test-qb-tax-validation.php`  
**Descripción**: Valida diferentes escenarios de impuestos (7%, 10%, 0%) y analiza objetos QB reales.

### 2. `test-qb-tax-real-data.php`
**Propósito**: Prueba con datos reales de QuickBooks  
**Uso**: `php docs/testing/test-qb-tax-real-data.php`  
**Descripción**: Procesa datos QB reales usando QuickBooksOnlineService y valida cálculos precisos.

### 3. `test-qb-online-service.php`
**Propósito**: Prueba específica del QuickBooksOnlineService  
**Uso**: `php docs/testing/test-qb-online-service.php`  
**Descripción**: Valida el almacenamiento correcto usando el mismo patrón que SendSaleToQuickBooksJob.

### 4. `test-qb-request-processing.php`
**Propósito**: Validación del CreateSaleQuickBooksRequest  
**Uso**: `php docs/testing/test-qb-request-processing.php`  
**Descripción**: Verifica que no se pierdan datos durante el procesamiento del form request.

### 5. `test-qb-invoice-creation-flow.php`
**Propósito**: Flujo completo de creación de facturas  
**Uso**: `php docs/testing/test-qb-invoice-creation-flow.php`  
**Descripción**: Prueba end-to-end que crea facturas en DocuCenter y las envía a QuickBooks.

### 6. `test-qb-direct-api.php`
**Propósito**: Prueba directa de API QuickBooks  
**Uso**: `php docs/testing/test-qb-direct-api.php`  
**Descripción**: Bypasea el CUFE requirement para testing directo de la API.

### 7. `test-qb-flow.sh`
**Propósito**: Script bash para testing rápido  
**Uso**: `./docs/testing/test-qb-flow.sh [organization_id]`  
**Descripción**: Ejecuta múltiples validaciones QB con un solo comando.

### 8. `test-intuit-tax-codes.php` 
**Propósito**: Testing de códigos de impuesto en UpdateIntuitOrdersJob  
**Uso**: `docker exec -it docucenter_laravel.test php docs/testing/test-intuit-tax-codes.php`  
**Descripción**: Valida la función determineTaxCode con múltiples escenarios de tasas ITBMS.

## Casos de Uso

### Testing Básico de Impuestos
```bash
# Validación rápida de cálculos
php docs/testing/test-qb-tax-validation.php
```

### Testing con Datos Reales
```bash
# Procesar datos QB reales
php docs/testing/test-qb-tax-real-data.php
```

### Testing Completo del Flujo
```bash
# Flujo completo interactivo
php docs/testing/test-qb-invoice-creation-flow.php

# O usando el script bash
./docs/testing/test-qb-flow.sh 2
```

### Debugging de Form Request
```bash
# Verificar preservación de datos
php docs/testing/test-qb-request-processing.php
```

## Configuración Requerida

### Variables de Entorno
```env
# Opcional: Para pruebas reales de API
ACI_EMAIL=tu_email@ejemplo.com
ACI_PASSWORD=tu_password

# Requeridas: Configuración de BD
DB_DATABASE=nombre_bd_principal
```

### Datos de Prueba
- **Organization ID**: Por defecto usa `2`, cambiar según necesidad
- **Conexión QB**: Debe existir conexión `acicloud` para la organización
- **Items QB**: Scripts usan item ID `48` (PUBLICIDAD EN PANTALLAS)
- **Cliente QB**: Scripts usan cliente ID `1440` (ANUAR MATA)

## Resultados Esperados

### Cálculos de Impuestos Correctos
- **Subtotal**: $100.00
- **Impuesto 7% ITBMS**: $7.00
- **Total**: $107.00

### Estados de Validación
- **CORRECTO**: Cálculos coinciden con valores esperados
- **INCORRECTO**: Discrepancias en cálculos (problema identificado)

## Resolución de Problemas

### Problema Original
**Síntoma**: Impuestos se almacenaban como $0.00 en lugar de $7.00  
**Causa**: Campos planos QB (TaxAmount: 0) sobrescribían valores anidados correctos  
**Solución**: Enhanced CreateSaleQuickBooksRequest y QuickBooksOnlineService

### Scripts de Diagnóstico
1. **test-qb-request-processing.php** - Verificar pérdida de datos
2. **test-qb-tax-real-data.php** - Validar cálculos con datos reales
3. **test-qb-online-service.php** - Confirmar almacenamiento correcto

## Notas de Implementación

### Datos QB Multi-Fuente
Los scripts validan múltiples fuentes de datos fiscales:
- `TaxCode.rateValue` (prioridad alta)
- `TaxAmount` (si > 0)
- `TaxLineDetail.TaxPercent`
- Campos planos (fallback)

### Preservación de Campos
Lista de campos QB protegidos en validación:
- Invoice level: `Id`, `DocNumber`, `TotalAmt`, `Balance`
- Line items: `Amount`, `DetailType`, `SalesItemLineDetail`
- Tax fields: `TotalTax`, `TaxLine`, `TaxCode`, `rateValue`

## Flujo de Testing Recomendado

1. **Validación Básica**: `test-qb-tax-validation.php`
2. **Datos Reales**: `test-qb-tax-real-data.php`
3. **Flujo Completo**: `test-qb-invoice-creation-flow.php`
4. **Diagnóstico**: `test-qb-request-processing.php` (si hay problemas)

## Estado Actual

**RESUELTO**: Los impuestos ITBMS se calculan y almacenan correctamente  
**VALIDADO**: Scripts confirman funcionalidad end-to-end  
**DOCUMENTADO**: Casos de uso y procedimientos completos  

Todos los scripts están listos para uso en testing y debugging de la integración QuickBooks.
