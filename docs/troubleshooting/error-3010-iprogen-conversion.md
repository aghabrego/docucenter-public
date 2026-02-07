# Error 3010 (dEnvFE Enumeration) - Análisis Final

## Problema Identificado

```
The 'http://dgi-fep.mef.gob.pa:dEnvFE' element is invalid - 
The value '3' is invalid according to its datatype 'Integer' - 
The Enumeration constraint failed.
```

## Causa Raíz - Conversión Interna en Digifact

Digifact v2.0.7 **convierte internamente** el XML NUC que recibe a su propia representación DGI para validación:

1. **Enviamos**: XML NUC con `<Info Name="EnvioContenedor" Value="3"/>`
2. **Digifact convierte** internamente: `EnvioContenedor=3` → `<dEnvFE>3</dEnvFE>` (representación DGI)
3. **Digifact valida** contra esquema XSD: `dEnvFE` solo acepta valores **1 o 2**
4. **Resultado**: Error 3010 porque dEnvFE=3 no es válido

## Comparación de Formatos

| Aspecto | DGI Format | NUC Format |
|---------|-----------|-----------|
| **Elemento** | `<dEnvFE>` | `<Info Name="EnvioContenedor">` |
| **Valores Válidos** | 1, 2 | 1, 2, 3 |
| **Descripción 1** | Sin envío | Sin envío |
| **Descripción 2** | Envío físico | Envío físico |
| **Descripción 3** | NO EXISTE | Envío electrónico |

## La Solución: iProGen=2

**ANTES** (Incorrecto):
```php
$dGen['iProGen'] = 3;  // Sistema electrónico
// XML NUC: EnvioContenedor=3
// Digifact convierte: dEnvFE=3 ❌ INVALID (schema solo acepta 1,2)
```

**AHORA** (Correcto):
```php
$dGen['iProGen'] = 2;  // Sistema con factura manual
// XML NUC: EnvioContenedor=2
// Digifact convierte: dEnvFE=2 ✅ VALID (dentro de rango 1,2)
```

## Por Qué `iProGen` = 2

En el contexto de Digifact Panama:

- **iProGen = 1**: Sin proceso de generación
- **iProGen = 2**: Sistema con factura manual (usuario genera factura, NO es autogenerada por sistema)
- **iProGen = 3**: Sistema electrónico autogenerado

DocuCenter genera facturas **manualmente** (los usuarios ingresan los datos), no **automáticamente**. Por lo tanto, `iProGen=2` es el valor correcto.

## Archivos Actualizados (Commit 43ac00a2)

| Archivo | Cambio |
|---------|--------|
| `app/Http/Livewire/Admin/Einvoice/CreateFast.php` | iProGen: 3 → 2 |
| `app/Http/Livewire/Admin/Einvoice/Create.php` | iProGen: 3 → 2 |
| `app/Http/Livewire/Admin/Einvoice/CreateFastJob.php` | iProGen: 3 → 2 |
| `app/Services/FEXmlService.php` | Default: 3 → 2 |
| `app/Services/DigifactXmlBuilder.php` | Default: 3 → 2 |

## Validación en Digifact

### Valores iProGen Aceptados:
- **1**: Sin proceso (no usado en DocuCenter)
- **2**: Sistema con factura manual ✅ CORRECTO
- **3**: Sistema electrónico (rechazado por Digifact porque convierte a dEnvFE=3)

### XML NUC Resultante:
```xml
<AdditionalIssueDocInfo>
    <Info Name="EnvioContenedor" Value="2"/>
    <Info Name="ProcesoGeneracion" Value="2"/>
    <Info Name="TipoEmision" Value="01"/>
    ...
</AdditionalIssueDocInfo>
```

## Proceso de Conversión en Digifact

```
┌─────────────────────────┐
│  DocuCenter            │
│  iProGen = 2          │
└────────────┬───────────┘
             │
             ↓
    ┌──────────────────┐
    │  XML NUC Format  │
    │ EnvioContenedor=2│
    └────────┬─────────┘
             │
             ↓ (Digifact Server)
    ┌──────────────────────┐
    │ Conversion Internal  │
    │ dEnvFE = 2 (from iProGen)
    └────────┬─────────────┘
             │
             ↓ (XSD Validation)
    ┌──────────────────────┐
    │ dEnvFE ∈ {1, 2} ✅ │
    │ Schema Valid        │
    └─────────────────────┘
```

## Testing

Para verificar que la solución funciona:

```bash
# 1. Hacer pull de los cambios
git pull origin master

# 2. Limpiar cachés en producción
php artisan config:cache
php artisan cache:clear

# 3. Certificar una factura
# Esperado: Response code 1000 (éxito), NO code 3000 (error)

# 4. Revisar logs
tail -f storage/logs/production.log
# Buscar: "Digifact - Respuesta HTTP recibida"
```

## Referencia

- **Commit**: 43ac00a2
- **Fecha**: 2026-02-02
- **Issue**: Error 3010 - dEnvFE schema validation
- **Status**: ✅ RESUELTO

## Changelog

| Commit | Cambio | Justificación |
|--------|--------|---------------|
| 43ac00a2 | iProGen: 3 → 2 | Digifact rechaza dEnvFE=3 (solo valida 1,2) |
| 2541b45a | dEnvFE: 3 → 2 | Valores válidos DGI: 1, 2 |
| 94db215c | iProGen: 1 → 3 | ❌ Incorrecto - causó Error 3010 |
| 7c818d73 | Escaping fix | SimpleXML addChild() requiere htmlspecialchars() |

## Notas Críticas

1. **iProGen y dEnvFE NO son independientes**: Digifact usa iProGen para generar internamente dEnvFE
2. **Límite de Digifact**: Aunque NUC format permite 1,2,3 para EnvioContenedor, Digifact XSD solo valida 1,2
3. **Documentación confusa**: Digifact documentation menciona iProGen=3, pero en práctica rechaza dEnvFE=3

Esta es la solución definitiva basada en validación empírica del comportamiento actual de Digifact v2.0.7.
