# Diferencia entre Formato NUC y DGI en Digifact

## Problema Identificado

Al certificar documentos con Digifact v2.0.7 usando el endpoint `/transform/nuc`, se generaba Error 3010:

```
The 'http://dgi-fep.mef.gob.pa:dEnvFE' element is invalid - 
The value '3' is invalid according to its datatype 'Integer' - 
The Enumeration constraint failed.
```

## Causa Raíz

DocuCenter maneja **dos formatos de XML diferentes** para Digifact:

### 1. Formato DGI (XML DGI tradicional de Panamá)
- Estructura de elementos XML diseñada por la DGI de Panamá
- Usado por algunos sistemas integrados
- Campos como `dEnvFE`, `iProGen`, `dSeg` están en este formato
- **Valores válidos para dEnvFE: 1, 2 (NO 3)**
  - 1 = Sin envío
  - 2 = Envío físico
  - ~~3 = Envío electrónico~~ (NO válido en esquema XSD)

### 2. Formato NUC (Nuevo Formato Consolidado)
- Formato moderno de Digifact v2.0.7
- Endpoint: `https://nucpa.digifact.com/api/v2/transform/nuc`
- Estructura XML más simple y estandarizada
- Usa elementos Info con atributos Name y Value
- **Campos equivalentes en NUC:**
  - `EnvioContenedor` (Info element) → equivalente a dEnvFE
  - Valores válidos: 1, 2, 3
    - 1 = Sin envío
    - 2 = Envío físico
    - 3 = Envío electrónico

## Diferencias de Enumeración

| Campo | Formato | Valores Válidos | Observación |
|-------|---------|-----------------|-------------|
| **dEnvFE** | DGI | 1, 2 | NO acepta 3 |
| **EnvioContenedor** | NUC | 1, 2, 3 | Acepta 3 para electrónico |
| **iProGen** | Ambos | 1, 2, 3 | 3 = Sistema electrónico |

## Solución Implementada

Para facturación electrónica usando formato NUC:

```php
// En componentes Livewire (Create.php, CreateFast.php, CreateFastJob.php)
$dGen['dEnvFE'] = 2;      // Envío físico (valor válido para DGI format)
$dGen['iProGen'] = 3;     // Sistema de facturación electrónica

// En DigifactXmlBuilder (generador XML NUC)
$iProGen = $dGen['iProGen'] ?? '3';

$infoFields = [
    'EnvioContenedor' => $iProGen,      // 3 en formato NUC (válido)
    'ProcesoGeneracion' => $iProGen,    // Debe coincidir con EnvioContenedor
    // ...
];
```

## Configuración Correcta

### Producción (Formato NUC)
```php
// Endpoint: https://nucpa.digifact.com/api/v2/transform/nuc
$dGen['dEnvFE'] = 2;       // Válido para DGI (solo 1 o 2)
$dGen['iProGen'] = 3;      // Sistema electrónico
// XML NUC generado automáticamente por DigifactXmlBuilder
// EnvioContenedor = 3 (válido en NUC)
```

### Pruebas (Formato NUC)
```php
// Endpoint: https://testnucpa.digifact.com/api/v2/transform/nuc
// Misma configuración que producción
$dGen['dEnvFE'] = 2;
$dGen['iProGen'] = 3;
```

## Cambios Realizados

- **Commit 2541b45a**: Cambiar dEnvFE de 3 a 2
  - Files: Create.php, CreateFast.php, CreateFastJob.php, FEXmlService.php
  - Razón: dEnvFE solo acepta valores 1 o 2 según esquema XSD
  - EnvioContenedor en formato NUC acepta 1, 2, 3 (automático en DigifactXmlBuilder)

## Validación de Errores

Después de este cambio, debería resolver:
- ❌ Error 3010: Schema validation failed (dEnvFE)
- ❌ Error 9026: FE_ValidacionUsoContenedor (EnvioContenedor != iProGen)
- ✅ Certificación exitosa con Digifact

## Referencias

- Digifact v2.0.7 API Documentation
- DGI Panamá - Esquema XML para Facturación Electrónica
- DocuCenter DigifactXmlBuilder (app/Services/DigifactXmlBuilder.php)
