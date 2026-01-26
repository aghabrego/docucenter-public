# Resultados de Prueba: PlusMovil API + AWS Cognito

**Fecha**: 28 de octubre de 2025

## Éxitos Logrados

### 1. Instalación de AWS CLI
```bash
sudo snap install aws-cli --classic
```
- Instalado correctamente: `aws-cli/1.42.61`
- Funciona correctamente

### 2. Autenticación con AWS Cognito
```bash
aws cognito-idp initiate-auth \
  --region us-east-1 \
  --auth-flow USER_PASSWORD_AUTH \
  --client-id 7t3s7lb4tfg6ssal586l929ovl \
  --auth-parameters USERNAME=larosemena_el_nawal,PASSWORD='7162384Li*'
```

**Resultado**: **EXITOSO**

**Tokens obtenidos**:
- Access Token generado correctamente
- Expira en 60 minutos
- Token guardado en `/tmp/plusmovil_access_token.txt`

**Access Token** (primeros 50 caracteres):
```
eyJraWQiOiJJVEFMS1hTUmdFeStGWHJ2NjdcL2ZiUzlhTXRSR0...
```

**Información del Token** (decodificado):
```json
{
  "sub": "3458f418-50e1-70ca-45ba-c47244270
7ab",
  "iss": "https://cognito-idp.us-east-1.amazonaws.com/us-east-1_xMaEibiru",
  "client_id": "7t3s7lb4tfg6ssal586l929ovl",
  "token_use": "access",
  "scope": "aws.cognito.signin.user.admin",
  "auth_time": 1761700418,
  "exp": 1761704018,
  "iat": 1761700418,
  "username": "larosemena_el_nawal"
}
```

**User Pool ID encontrado**: `us-east-1_xMaEibiru`

## Problemas Identificados

### Problema: DNS no resuelve el endpoint

**Error**:
```
curl: (6) Could not resolve host: 28cwop8rj6.execute-api.us-east-1.amazonaws.com
```

**URL probada**:
```
https://28cwop8rj6.execute-api.us-east-1.amazonaws.com/dev/sys-logs
```

**Diagnóstico**:
1. Internet funciona correctamente (ping a google.com exitoso)
2. DNS no resuelve el hostname específico de la API
3. ❓ La URL podría ser incorrecta o el endpoint no existe

## Posibles Causas

### 1. URL Incorrecta
Es posible que la URL proporcionada no sea correcta o esté desactualizada.

**Solución**: Solicitar al equipo de PlusMovil la URL correcta del API Gateway.

### 2. Endpoint Privado
El API Gateway podría ser privado y solo accesible desde:
- VPN de PlusMovil
- Red interna
- IPs autorizadas

**Solución**: Verificar si se requiere VPN o whitelist de IPs.

### 3. Endpoint en Otra Región/Stage
El endpoint podría estar en otra región o stage.

**Solución**: Confirmar región y stage correcto (`dev` vs `prod`).

## Información Confirmada

### Credenciales Válidas 
```
Usuario: larosemena_el_nawal
Password: 7162384Li*
```

### AWS Cognito 
```
Client ID: 7t3s7lb4tfg6ssal586l929ovl
User Pool ID: us-east-1_xMaEibiru
Región: us-east-1
```

### Token de Acceso 
- Generación: Exitosa
- Validez: 60 minutos
- Formato: JWT válido
- Scope: `aws.cognito.signin.user.admin`

## Próximos Pasos

### 1. Verificar URL del Endpoint
Solicitar al equipo de PlusMovil:
- [ ] URL correcta del API Gateway
- [ ] Confirmar que el endpoint existe
- [ ] Verificar si es público o privado
- [ ] Documentación de Swagger/OpenAPI actualizada

### 2. Verificar Requisitos de Red
- [ ] ¿Se requiere VPN para acceder?
- [ ] ¿Hay whitelist de IPs?
- [ ] ¿El endpoint es público o privado?

### 3. Probar Endpoints Alternativos
Si tienen otros endpoints documentados:
- [ ] Probar con `/health` o `/ping`
- [ ] Verificar base URL diferente
- [ ] Confirmar stage (`/dev` vs `/prod`)

## Alternativas Inmediatas

### Opción A: Probar desde la Web
Si la aplicación web funciona:
1. Acceder a la web con las credenciales
2. Usar DevTools (F12) → Network
3. Ver qué URL real usa la aplicación
4. Copiar la URL correcta

### Opción B: Usar Postman/Insomnia
Si tienen colección de Postman:
1. Importar colección
2. Usar el token obtenido
3. Ver si los endpoints funcionan

### Opción C: Contacto Directo
Solicitar al equipo de Plus Movil:
- Documentación actualizada de API
- URL de Swagger/OpenAPI
- Ejemplos de curl funcionando

## Resumen

| Componente | Estado | Notas |
|------------|--------|-------|
| AWS CLI | Instalado | Versión 1.42.61 |
| Credenciales | Válidas | Usuario y password correctos |
| AWS Cognito | Funcional | Autenticación exitosa |
| Access Token | Generado | Token JWT válido por 60 min |
| User Pool | Confirmado | `us-east-1_xMaEibiru` |
| DNS Endpoint | No resuelve | URL podría ser incorrecta |
| API Gateway | ❓ Sin confirmar | No se pudo probar conectividad |

## Scripts Funcionales

Los siguientes scripts están listos y funcionando:

### 1. Obtener Token (Funciona Perfectamente)
```bash
./docs/testing/get-cognito-token.sh
```

### 2. Probar API (Requiere URL correcta)
```bash
./docs/testing/test-plusmovil-api.sh
```

### 3. Helper de Token
```bash
./docs/testing/get-token-simple.sh
```

## Token Actual

El token está guardado y disponible en:
```bash
cat /tmp/plusmovil_access_token.txt
```

Válido hasta: **~60 minutos desde generación** (28/10/2025 ~21:13)

## ✉Preguntas para el Equipo de PlusMovil

1. **¿Cuál es la URL correcta del API Gateway?**
   - URL actual no resuelve DNS: `28cwop8rj6.execute-api.us-east-1.amazonaws.com`

2. **¿El endpoint es público o requiere VPN/whitelist?**

3. **¿Tienen documentación Swagger/OpenAPI actualizada?**

4. **¿Hay endpoints de health check para probar conectividad?**

5. **¿El stage es correcto?** (`/dev` vs `/prod`)

## Conclusión

**Autenticación: 100% Exitosa** 
- Las credenciales son válidas
- AWS Cognito funciona perfectamente
- Token JWT generado correctamente

**API Testing: Bloqueado por DNS** 
- Endpoint no resuelve
- Necesitamos URL correcta del equipo

**Próximo paso crítico**: Obtener URL correcta del API Gateway de PlusMovil.
