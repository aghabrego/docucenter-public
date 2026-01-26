# Análisis de Endpoints Alanube - Necesidades de Mejora

## Situación Actual vs. Documentación Oficial

### Alanube República Dominicana CORRECTO
**Implementación actual en AlanubeDomService.php:**
- Usa correctamente `/dom/v1/{endpoint}`
- Mapeo específico por tipo de documento
- Endpoints oficiales implementados

**Endpoints actuales:**
```php
protected $endpoints = [
    31 => 'fiscal-invoices',        // Factura de Crédito Fiscal (31)
    32 => 'invoices',              // Factura de Consumo (32) 
    45 => 'gubernamentals',        // Factura Gubernamental (45)
    46 => 'export-supports',       // Factura de Exportación (46)
];
```

### Alanube Panamá NECESITA ACTUALIZACIÓN
**Implementación actual en AlanubeService.php:**
```php
protected $endpoints = [
    '01' => 'invoices',    // Genérico, debería ser específico
    '02' => 'invoices',    // Genérico
    '03' => 'invoices',    // Genérico
    '08' => 'invoices',    // Genérico
    '09' => 'invoices',    // Genérico
    '10' => 'invoices',    // Genérico
];

protected $creditNoteEndpoints = [
    '04' => 'credit-notes',  // Correcto pero incompleto
];
```

## Endpoints Oficiales que DEBEN Implementarse

### Alanube República Dominicana (YA CORRECTO)
```
Factura de Crédito Fiscal (31):
- Prueba: https://sandbox.alanube.co/dom/v1/fiscal-invoices
- Producción: https://api.alanube.co/dom/v1/fiscal-invoices

Factura de Consumo (32):
- Prueba: https://sandbox.alanube.co/dom/v1/invoices  
- Producción: https://api.alanube.co/dom/v1/invoices

Factura Gubernamental (45):
- Prueba: https://sandbox.alanube.co/dom/v1/gubernamentals
- Producción: https://api.alanube.co/dom/v1/gubernamentals

Factura de Exportación (46):
- Prueba: https://sandbox.alanube.co/dom/v1/export-supports
- Producción: https://api.alanube.co/dom/v1/export-supports
```

### Alanube Panamá (NECESITA CORRECCIÓN)
```
Factura Electrónica:
- Prueba: https://sandbox-api.alanube.co/pan/v1/invoices
- Producción: https://api.alanube.co/pan/v1/invoices

Nota de Crédito Electrónica:
- Prueba: https://sandbox-api.alanube.co/pan/v1/credit-notes  
- Producción: https://api.alanube.co/pan/v1/credit-notes
```

## Problemas Identificados

### 1. AlanubeService de Panamá usa URLs incorrectas
**Actual:**
```php
// INCORRECTO - No especifica /pan/v1/
$url = rtrim($pacConnection->endpoint, '/') . '/' . $endpoint;
```

**Debería ser:**
```php
// CORRECTO 
$url = rtrim($pacConnection->endpoint, '/') . '/pan/v1/' . $endpoint;
```

### 2. No distingue entre sandbox y producción
- Los endpoints actuales no cambian entre prueba y producción
- Falta lógica para detectar si es sandbox o producción

### 3. AlanubeFormatterHelper no considera endpoints
- El helper detecta país pero no valida endpoints
- No hay validación de configuración PAC vs endpoints

## Mejoras Requeridas

### 1. Actualizar AlanubeService.php (Panamá)
- Cambiar construcción de URL para incluir `/pan/v1/`
- Implementar detección sandbox vs producción
- Validar configuración de endpoints

### 2. Mejorar detección de ambiente
- Sandbox: `sandbox-api.alanube.co`
- Producción: `api.alanube.co`

### 3. Validar consistencia en configuraciones PAC
- Verificar que endpoint coincida con país detectado
- Validar formato de URL según documentación oficial

## Plan de Implementación

### Paso 1: Corregir AlanubeService (Panamá)
1. Actualizar construcción de URL
2. Implementar detección de ambiente
3. Validar configuración PAC

### Paso 2: Mejorar AlanubeFormatterHelper
1. Agregar validación de endpoints
2. Verificar consistencia país vs endpoint
3. Detectar ambiente automáticamente

### Paso 3: Testing completo
1. Probar con endpoints de sandbox
2. Validar respuestas de ambos países
3. Verificar funcionamiento del helper

---
**Fecha**: 2025-08-23
**Estado**: Análisis completado - Necesita implementación
**Prioridad**: Alta - Endpoints incorrectos afectan funcionalidad
