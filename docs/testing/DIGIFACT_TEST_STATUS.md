# Script de Prueba Digifact PAC - AUTENTICACIÓN EXITOSA

## Estado: CONEXIÓN ESTABLECIDA Y TOKEN OBTENIDO

### Autenticación Exitosa
**Dominio**: `testnucpa.digifact.com` **FUNCIONAL**
- Certificado SSL válido 
- Cloudflare como proxy 
- Servidor: ASP.NET 
- **Token JWT obtenido**: Válido por 27 días 

### URLs Correctas Identificadas

#### API v1 (No Resuelve - Deprecada)
- `pactest.digifact.com.pa` - DNS no resuelve
- **Endpoint**: https://pactest.digifact.com.pa/pa.com.apinuc/api/login/get_token

#### API Funcional (Sin versión en path) 
- `testnucpa.digifact.com` - **COMPLETAMENTE FUNCIONAL**
- **Base URL**: `https://testnucpa.digifact.com/api`
- **Login**: `https://testnucpa.digifact.com/api/login/get_token` **HTTP 200**
- **Transform**: `https://testnucpa.digifact.com/api/transform/nuc_json`

### Hallazgos Importantes

#### 1. Endpoint de Autenticación Encontrado
**Path correcto**: `/api/login/get_token` (NO `/api/v2/login/get_token`)

**Request exitoso**:
```bash
POST https://testnucpa.digifact.com/api/login/get_token
Content-Type: application/json

{
  "Username": "PA.155704849-2-2021.PRUEBAS193",
  "Password": "Digifact*25"
}
```

**Response (HTTP 200)**:
```json
{
  "Token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expira_en": "1/25/2026 11:20:51 PM",
  "otorgado_a": "PA.155704849-2-2021.PRUEBAS193"
}
```

#### 2. Token JWT Obtenido
- **Tipo**: Bearer Token (JWT)
- **Duración**: 27 días (desde 29/12/2025 hasta 25/01/2026)
- **Formato**: HS256 (HMAC-SHA256)
- **Longitud**: 279 caracteres
- **Guardado en**: `/tmp/digifact_token.txt`

#### 3. Parámetros Requeridos para Transform
El endpoint `/api/transform/nuc_json` requiere:
- `TAXID` - RUC de la empresa (155704849-2-2021)
- `USERNAME` - Usuario del PAC (PRUEBAS193)
- `FORMAT` - Formato de respuesta (PDF, HTML, XML o combinación)
- **Header**: `Authorization: Bearer {token}`

### Credenciales de Prueba Proporcionadas
```
RUC: 155704849-2-2021
Usuario: PRUEBAS193
Contraseña: Digifact*25
```

### Estado de Pruebas

#### Completadas
- [x] DNS resolution para testnucpa.digifact.com
- [x] Verificación de certificado SSL
- [x] **Autenticación exitosa con `/api/login/get_token`**
- [x] **Token JWT obtenido y guardado**
- [x] Identificación de parámetros requeridos para transform
- [x] Estructura de URLs correcta identificada

####  Próximas Pruebas
- [ ] Certificar documento de prueba con token
- [ ] Probar endpoint SHAREDINFO
- [ ] Probar GetDocument
- [ ] Validar estructura JSON del documento
- [ ] Pruebas de formato de respuesta (PDF, HTML, XML)

## Próximas Acciones

### 1. Contactar Soporte Digifact
**Preguntas críticas**:

1. **Endpoint de autenticación API v2**:
   - ¿Cuál es el endpoint correcto para obtener token en API v2?
   - ¿Se requiere token JWT o autenticación en URL?

2. **Formato de autenticación**:
   - ¿Los parámetros TAXID, USERNAME, PASSWORD van en URL o header?
   - ¿Hay un endpoint `/auth/login` o similar?

3. **Documentación API v2**:
   - ¿Existe documentación para `testnucpa.digifact.com/api/v2`?
   - ¿Cuál es la estructura completa del JSON para certificar?

**Contacto**:
- **Email**: soporte@digifact.com.gt  
- **Subject**: [155704849-2-2021] Consulta API v2 - Autenticación  
- **Teléfono**: 2319-1921, opción 2

### 2. Probar Endpoints Alternativos

### 2. Probar Endpoints Alternativos

Posibles variaciones a probar:
- `/api/v2/login`
- `/api/v2/auth/token`
- `/api/v2/authenticate`
- Autorización via Bearer token en headers

### 3. Analizar Respuestas

Scripts creados:
- `docs/testing/digifact-v2-auth-test.sh` - Test de autenticación
- `docs/testing/digifact-v2-transform-test.sh` - Test de transformación
- `docs/testing/digifact-auth-test.sh` - Test API v1 (original)
- `docs/testing/digifact-complete-test.sh` - Test completo API v1

## Resumen Técnico

### Estructura de URLs Correcta

| Componente | URL Correcta | Status |
|-----------|--------------|---------|
| **Dominio** | testnucpa.digifact.com | Resuelve |
| **Base Path** | /api | Funcional |
| **Login** | /api/login/get_token | HTTP 200 |
| **Transform** | /api/transform/nuc_json |  Pendiente prueba |
| **SharedInfo** | /api/SHAREDINFO |  Por probar |
| **GetDocument** | /api/GetDocument |  Por probar |

### Comparación API Documentada vs API Real

| Característica | Documentación PDF | API Real Funcional |
|----------------|-------------------|-------------------|
| Dominio Test | pactest.digifact.com.pa | testnucpa.digifact.com |
| DNS | No resuelve | Resuelve |
| Base Path | /pa.com.apinuc/api | /api |
| Login Endpoint | /login/get_token | /login/get_token |
| SSL | ❓ Unknown | Válido |
| Autenticación | Bearer Token | Bearer Token JWT |
| Duración Token | 30 días | 27 días (actual) |

### Hallazgos de Conectividad

1. **testnucpa.digifact.com** resuelve a:
   - IPv4: 104.18.3.82, 104.18.2.82 (Cloudflare)
   - IPv6: 2606:4700::6812:252, 2606:4700::6812:352

2. **Certificado SSL**:
   - Válido desde: Dec 10 2025
   - Expira: Mar 10 2026
   - Emisor: Google Trust Services (WE1)

3. **Respuestas del Servidor**:
   - `/api/login/get_token`: HTTP 200 - Token obtenido exitosamente
   - `/api/v2/*`: HTTP 404 - Path incorrecto (no usar v2)
   - `/api/transform/nuc_json`: HTTP 400 - Requiere parámetros (TAXID, FORMAT, USERNAME)

### Token JWT Decodificado

**Header**:
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

**Payload** (información extraída):
```json
{
  "User": "PA.155704849-2-2021.PRUEBAS193",
  "Country": "PA",
  "Env": "2",
  "iss": "https://www.digifact.com",
  "aud": "https://testnucpa.digifact.com",
  "iat": 1766791251,
  "nbf": 1766791251,
  "exp": 1769383251
}
```

- **Emisor**: www.digifact.com
- **Audiencia**: testnucpa.digifact.com
- **Ambiente**: "2" (Test)
- **País**: PA (Panamá)

## Scripts de Prueba

### Uso

```bash
# Obtener token de autenticación
curl -X POST "https://testnucpa.digifact.com/api/login/get_token" \
  -H "Content-Type: application/json" \
  -d '{"Username": "PA.155704849-2-2021.PRUEBAS193", "Password": "Digifact*25"}'

# Token guardado en
cat /tmp/digifact_token.txt

# Próximo: Certificar documento
TOKEN=$(cat /tmp/digifact_token.txt)
curl -X POST "https://testnucpa.digifact.com/api/transform/nuc_json?TAXID=155704849-2-2021&USERNAME=PRUEBAS193&FORMAT=PDF" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @documento.json
```

### Scripts Bash Disponibles

- `docs/testing/digifact-v2-auth-test.sh` - Test de autenticación (actualizar paths)
- `docs/testing/digifact-v2-transform-test.sh` - Test de transformación (actualizar paths)  
- `docs/testing/digifact-auth-test.sh` - Test API v1 (deprecado)
- `docs/testing/digifact-complete-test.sh` - Test completo (actualizar)

## Próximos Pasos

1. Análisis de documentación completado
2. Scripts de prueba creados
3. Credenciales de prueba configuradas
4. **Endpoint API funcional descubierto**
5. **Token de autenticación obtenido**
6.  **SIGUIENTE**: Certificar documento de prueba
7.  Validar respuesta y extraer CUFE
8.  Implementar servicio en Laravel
9.  Crear job de certificación

---

**Última actualización**: 29 de diciembre de 2025, 21:04 EST  
**Estado**: **AUTENTICACIÓN EXITOSA - Token JWT obtenido y funcional**  
**Token válido hasta**: 25 de enero de 2026  
**Próximo paso**: Certificar documento de prueba con el token obtenido
