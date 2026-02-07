# Error 9026: FE_ValidacionUsoContenedor (Digifact)

## Descripción
Error 9026 ocurre cuando Digifact detecta una inconsistencia o error en la validación del uso del contenedor de la factura electrónica.

```
[9026] - FE_ValidacionUsoContenedor
Descripción: Falló la validación de algunos escenarios.
Sugerencia: Verifique la sección de EventosConsistencia.
```

## Causas Identificadas

### 1. **EnvioContenedor != ProcesoGeneracion (iProGen)** ⚠️ CRÍTICO
El campo `EnvioContenedor` DEBE coincidir **exactamente** con el valor de `ProcesoGeneracion` (iProGen).

**Enumeración de valores según Digifact v2.0.7:**

| Campo | Valor | Descripción |
|-------|-------|-------------|
| **iProGen (ProcesoGeneracion)** | 1 | Documento en papel |
| | 2 | Generación por tercero |
| | 3 | Sistema de facturación electrónica |
| **dEnvFE/EnvioContenedor** | 1 | Sin envío |
| | 2 | Envío físico |
| | 3 | Envío electrónico |

**Combinación correcta para facturación electrónica:**
```
iProGen = 3 (Sistema de facturación electrónica)
EnvioContenedor = 3 (Envío electrónico)
dEnvFE = 3 (debe coincidir con EnvioContenedor)
```

### 2. **Ubicación de EnvioContenedor en XML**
El elemento `EnvioContenedor` DEBE estar:
- ✅ **CORRECTO**: Dentro de `Header > AdditionalIssueDocInfo` como elemento `Info` con atributo `Name="EnvioContenedor"`
- ❌ **INCORRECTO**: Como elemento directo de `Header`

Estructura correcta:
```xml
<Header>
    <DocType>01</DocType>
    <IssuedDateTime>2026-02-02T17:52:06-05:00</IssuedDateTime>
    <AdditionalIssueDocInfo>
        <Info Name="EnvioContenedor" Value="3"/>
        <Info Name="ProcesoGeneracion" Value="3"/>
        ...otros campos...
    </AdditionalIssueDocInfo>
</Header>
```

### 3. **Configuración en Ambiente Producción**
Cuando se utiliza la URL de producción (`https://nucpa.digifact.com/`):
- Usar `AdditionalIssueType=1` (NO 2, que es para pruebas)
- Usar `iProGen=3` (Sistema electrónico, NO 1 que es para papel)
- Usar `EnvioContenedor=3` (Envío electrónico)

## Solución Implementada

**Archivos Modificados:**
1. `app/Services/DigifactXmlBuilder.php` - Líneas 179-195
2. `app/Http/Livewire/Admin/Einvoice/Create.php` - Línea 2878
3. `app/Http/Livewire/Admin/Einvoice/CreateFast.php` - Línea 1281
4. `app/Http/Livewire/Admin/Einvoice/CreateFastJob.php` - Línea 1778
5. `app/Services/FEXmlService.php` - Línea 421

**Cambio clave:**
```php
// Antes (INCORRECTO):
$dGen['iProGen'] = 1;  // Documento en papel
$dGen['dEnvFE'] = 1;   // Sin envío

// Después (CORRECTO):
$dGen['iProGen'] = 3;  // Sistema de facturación electrónica
$dGen['dEnvFE'] = 3;   // Envío electrónico
```

**En DigifactXmlBuilder:**
```php
$iProGen = $dGen['iProGen'] ?? '3';
$infoFields = [
    'EnvioContenedor' => $iProGen,      // Debe ser igual a ProcesoGeneracion
    'ProcesoGeneracion' => $iProGen,    // Controlado por iProGen
    ...
];
```

## Debugging

### Log Crítico
Se agregaron logs en nivel `WARNING` en `DigifactXmlBuilder::buildAdditionalIssueDocInfo()`:

```
DigifactXmlBuilder::buildAdditionalIssueDocInfo - CAMPOS Info A INCLUIR EN XML (Error 9026 Debug)
- EnvioContenedor: 3
- ProcesoGeneracion: 3
- son_iguales: SI
```

### Verificación
Para verificar que el error se ha resuelto:

1. Ejecutar certificación de factura
2. Revisar que Digifact retorne código 1000 (éxito) en lugar de 3000
3. Verificar en los logs:
   ```
   son_iguales: SI
   ```

## Referencias
- Digifact v2.0.7 - Especificación NUC para Panamá
- Endpoint Producción: `https://nucpa.digifact.com/api/v2/transform/nuc`
- Commit: `94db215c` - Cambio de iProGen a 3

## Historial de Intentos Fallidos
- **Intento 1** (Commit `5d6ace4f`): Cambiar dEnvFE de 3 a 1 - ❌ ERROR 9026 persiste
- **Intento 2** (Commit `347b914d`): Excluir EnvioContenedor cuando Sin CAFE - ❌ ERROR 9026 persiste
- **Intento 3** (Commit `da506dce`): Volver a dEnvFE=3 - ❌ ERROR 9026 persiste
- **Intento 4** (Commit `69e597a6`): Cambiar iProGen a 1, dEnvFE a 1 - ❌ ERROR 9026 persiste
- **Intento 5** (Commit `94db215c`): Cambiar iProGen a 3, dEnvFE a 3 - ⏳ PRUEBA PENDIENTE

## Siguiente Paso
Ejecutar certificación de factura con los valores actualizados (iProGen=3, dEnvFE=3) y monitorear logs para confirmar que Error 9026 se ha resuelto.
