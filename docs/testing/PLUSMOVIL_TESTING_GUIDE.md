# Guía Rápida: Testing de PlusMovil API

## Inicio Rápido

### Credenciales Disponibles
```
Usuario: larosemena_el_nawal
Password: 7162384Li*
Client ID (QA): 7t3s7lb4tfg6ssal586l929ovl
```

## Método 1: Con AWS CLI (Recomendado)

### Paso 1: Instalar AWS CLI
```bash
sudo apt update
sudo apt install awscli
```

### Paso 2: Obtener Token
```bash
./docs/testing/get-cognito-token.sh
```

Este script:
- Se autentica automáticamente con las credenciales
- Obtiene el Access Token de AWS Cognito
- Lo guarda en `/tmp/plusmovil_access_token.txt`
- Te lo muestra para copiar
- Opcionalmente prueba el token con la API

### Paso 3: Probar API
```bash
./docs/testing/test-plusmovil-api.sh
```

Pega el token cuando te lo pida.

## Método 2: Sin AWS CLI

### Opción A: Script Helper
```bash
./docs/testing/get-token-simple.sh
```

Este script te ofrece instalar AWS CLI automáticamente.

### Opción B: Desde la Web
1. Acceder a la aplicación web de PlusMovil
2. Iniciar sesión con las credenciales de arriba
3. Abrir DevTools (F12)
4. Ir a: Application → Local Storage o Session Storage
5. Buscar keys: `accessToken`, `token`, `idToken`
6. Copiar el valor (JWT que empieza con `eyJ`)
7. Ejecutar: `./docs/testing/test-plusmovil-api.sh`
8. Pegar el token cuando te lo pida

## Método 3: Comando AWS CLI Manual

Si ya tienes AWS CLI instalado:

```bash
aws cognito-idp initiate-auth \
  --region us-east-1 \
  --auth-flow USER_PASSWORD_AUTH \
  --client-id 7t3s7lb4tfg6ssal586l929ovl \
  --auth-parameters USERNAME=larosemena_el_nawal,PASSWORD='7162384Li*'
```

Copia el `AccessToken` de la respuesta.

## Problemas Comunes

### Error: "ResourceNotFoundException"
**Causa**: Client ID incorrecto o User Pool no encontrado

**Solución**: Solicitar al equipo de PlusMovil:
- User Pool ID completo
- Región exacta de AWS Cognito

### Error: "NotAuthorizedException"
**Causa**: Credenciales incorrectas

**Solución**: Verificar usuario y contraseña

### Error: "command not found: aws"
**Causa**: AWS CLI no instalado

**Solución**: 
```bash
sudo apt install awscli
```

## Información Necesaria del Equipo PlusMovil

Para autenticación completa necesitamos:
- [ ] **User Pool ID** de AWS Cognito (formato: `us-east-1_XXXXXXXXX`)
- [ ] **Región** exacta (probablemente `us-east-1`)
- [ ] Confirmación de que las credenciales son correctas

Con esta información, el script `get-cognito-token.sh` funcionará al 100%.

## Estructura de Token Válido

Un Access Token de AWS Cognito se ve así:

```
eyJraWQiOiJxRzZtNHBzVCtcL3RQY3RtSW5CVFQ2TkNhVjBxa25ucGdxYzVNeGRPN1wvQT0iLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiI3YzQxNGUzZC0xZTY4LTRkYTUtYjczMC03YWIyZjE5MWU5NzQiLCJkZXZpY2Vfa2V5IjoiZXUtd2VzdC0xXzNhMzhlNzY3LWUwMGEtNDQzYy1iZTJmLTczZmQyNjQ1YzUyNSIsImNvZ25pdG86Z3JvdXBzIjpbImFkbWluIl0sInRva2VuX3VzZSI6ImFjY2VzcyIsInNjb3BlIjoiYXdzLmNvZ25pdG8uc2lnbmluLnVzZXIuYWRtaW4iLCJhdXRoX3RpbWUiOjE2OTg1MjM0NTYsImlzcyI6Imh0dHBzOlwvXC9jb2duaXRvLWlkcC5ldS13ZXN0LTEuYW1hem9uYXdzLmNvbVwvZXUtd2VzdC0xX3h4eHh4eHh4eCIsImV4cCI6MTY5ODUyNzA1NiwiaWF0IjoxNjk4NTIzNDU2LCJqdGkiOiJhMWIyYzNkNC1lNWY2LWc3aDgtaTlqMC1rMWwybTNuNG81cDYiLCJjbGllbnRfaWQiOiI3dDNzN2xiNHRmZzZzc2FsNTg2bDkyOW92bCIsInVzZXJuYW1lIjoibGFyb3NlbWVuYV9lbF9uYXdhbCJ9.abcdefghijklmnopqrstuvwxyz...
```

Características:
- Empieza con `eyJ`
- Muy largo (800-2000 caracteres)
- Tres partes separadas por puntos (`.`)
- Es un JWT (JSON Web Token)

## Qué Probar

Una vez tengas el token, el script de testing probará:

1. Endpoint `/sys-logs` sin filtros
2. Endpoint con paginación (`limit=10&offset=0`)
3. Filtros personalizados:
   - Búsqueda parcial: `module_name_like=invoice`
   - Múltiples valores: `action_type_in=create,update`
   - Rangos de fechas: `created_at_between=2024-01-01,2024-12-31`
   - Ordenamiento: `order_by=created_at&order_dir=desc`

## Seguridad

**IMPORTANTE**: Las credenciales en este documento son de prueba.

- NO commitear credenciales reales en git
- Usar variables de entorno en producción
- Rotar credenciales después de testing

## Notas

- Los tokens de Cognito expiran típicamente en 1 hora
- El script guarda el token en `/tmp/plusmovil_access_token.txt`
- Puedes reutilizar el token mientras esté válido
