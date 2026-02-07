# Tests Digifact - Enero 2025

## Documentación Oficial
- **Versión**: 2.0.4 (02/10/2025)
- **Archivo**: Documentacion Tecnica API Digifact Panama V2.0.4

## Endpoints Correctos (Según v2.0.4)
- **QA/Test**: `https://testnucpa.digifact.com/api/login/get_token` ✅
- **Producción**: `https://nucpa.digifact.com/api/login/get_token` ✅
- **Ruta**: `/api/login/get_token` (NO `/v2`, NO `/apinuc`)

### Nota Importante
- El endpoint de **producción es `nucpa`** (NO ~~apinuc~~)
- Esta corrección está en la documentación v2.0.4 de octubre 2025

## Procedimiento de Obtención de Token (v2.0.4)

### Formato Username
Concatenación separada por punto (.):
```
PA.<RUC>.<Nombre_usuario>
```

**Ejemplo:** `PA.155770712-2-2025.155770712-2-2025`

### Body Request (JSON)
```json
{
  "Username": "PA.155770712-2-2025.155770712-2-2025",
  "Password": "Freskura123*"
}
```

### Vigencia del Token
- **Validez**: 30 días desde la solicitud
- **Caducado**: API devuelve error 401 Unauthorized
- **Renovación**: Generar nuevo token cuando expire

### Uso del Token en Solicitudes
El token se incluye en el header `Authorization`:
```
Authorization: <TOKEN>
```

#### test-digifact-qa.php
Prueba de autenticación en ambiente QA.
```bash
php docs/testing/test-digifact-qa.php
```
**Resultado esperado**: `401 Unauthorized` con mensaje "Credenciales incorrectas"
- Username: `PA.155770712-2-2025.155770712-2-2025` (credencial de producción)
- Password: `Freskura123*`
- **Nota**: Las credenciales de producción no funcionan en QA. Se necesitan credenciales de QA para token exitoso.

#### test-token-prod.php
Test obsoleto - Intenta conectar a `apinuc.digifact.com` (endpoint antiguo).
**Usar `test-token-prod-standalone.php` en su lugar.**

#### test-token-prod-standalone.php  
Versión standalone sin dependencias de Laravel.
Conecta a endpoint correcto: `https://nucpa.digifact.com/api/login/get_token`
```bash
php docs/testing/test-token-prod-standalone.php
```
**Resultado**: Depende de acceso a red de producción desde tu máquina.

## Hallazgos de Formato XML

Desde las pruebas anteriores (DigifactRealCertificationTest.php en tests/):

### Campo FormatoGeneracion (AI11)
- **Corrección**: Cambio de `FormatoOperacion` a `FormatoGeneracion` (v2.0.2+)
- **Valores válidos**: 1 (Sin CAFE), 2 (Cinta papel) 
- **Nota**: Valor 3 es INVÁLIDO en versiones modernas
- **Estado**: Implementado en DigifactXmlBuilder.php línea 164

### Valores Predeterminados CAFE (Commit 32db4ba8)
- `FormatoGeneracion`: 1 (Sin CAFE)
- `ManeraEntrega`: 1
- `EnvioContenedor`: 1

### Códigos de Ambiente
- **AdditionalIssueType**: Invertido para Digifact (2=Test, 1=Producción)
- Implementado en DigifactXmlBuilder.php líneas 131-132

## Próximos Pasos

### ✅ Completado
1. **Token de Producción**: Generado exitosamente
   - Endpoint: `https://nucpa.digifact.com/api/login/get_token`
   - Vigencia: 30 días
   - Status: OK

2. **Endpoint de Certificación**: Identificado y validado
   - Test: `https://testnucpa.digifact.com/api/v2/transform/nuc`
   - Producción: `https://nucpa.digifact.com/api/v2/transform/nuc`
   - Parámetros: TAXID, FORMAT, USERNAME
   - Status: OK

### ⏳ En Progreso
1. **Validación de XML NUC**: Estructura correcta según Digifact v2.0.4
   - Status: Identificar schema completo de Seller, Buyer, Items, Totals, Payments
   - Ref: DigifactXmlBuilder.php (líneas 200+)

2. **Certificación End-to-End**: Token + XML → Documento Certificado
   - Test file: `test-token-certificacion-prod.php`
   - Status: Requiere estructura XML completa

### 🔍 Hallazgos Importantes

#### Corrección de Rutas (v2.0.4 - Octubre 2025)
- **ANTES (incorrecto)**: `apinuc.digifact.com`
- **AHORA (correcto)**: `nucpa.digifact.com`
- **Tipo de ruta**: `/api/v2/transform/nuc` para certificación

#### Estructura XML Info
Los elementos `Info` en `AdditionalIssueDocInfo` usan atributos, NO contenido:
```xml
<Info Name="TipoEmision" Value="01"/>
<Info Name="NumeroDF" Value="0000000001"/>
```

#### Campos Obligatorios en AdditionalIssueDocInfo
Mínimo requerido:
- TipoEmision
- NumeroDF
- PtoFactDF
- CodigoSeguridad
- NaturalezaOperacion
- TipoOperacion
- DestinoOperacion
- FormatoGeneracion (valor 1-2)
- ManeraEntrega
- EnvioContenedor
- ProcesoGeneracion

---
Fecha: 31 Enero 2025
