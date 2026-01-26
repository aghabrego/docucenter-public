# APIs de Autenticación - Resumen

## Descripción General

Se implementaron dos nuevos endpoints de autenticación para el sistema DocuCenter que permiten login con credenciales y renovación de tokens con validación de licencia remota.

---

## 1. POST /api/v1/auth/login

**Propósito**: Autenticar usuario con credenciales y obtener token de acceso con fecha de expiración basada en la licencia.

**Acceso**: Público (no requiere autenticación previa)

### Parámetros Requeridos

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `email` | string | required, email, max:255 | Correo electrónico del usuario |
| `password` | string | required, min:6 | Contraseña del usuario |
| `organization_id` | integer | required, exists:organizations | ID de la organización |
| `point_sale` | integer | required, between:0-999 | Número de punto de venta |
| `token_name` | string | nullable, max:255 | Nombre personalizado para el token (opcional) |

### Proceso de Autenticación

1. Valida credenciales (email/password)
2. Verifica acceso del usuario a la organización
3. Consulta API remota de licencias para obtener fecha de vencimiento
4. Valida que la licencia no esté expirada
5. Crea token con `expires_at = FechaVencimiento` de la licencia
6. Guarda metadata: `organization_id` y `point_sale` en el token

### Respuesta Exitosa (200)

```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "token": "1|abc123xyz...",
  "token_info": {
    "name": "API Token - 2024-01-15 10:30:00",
    "expires_at": "2024-12-31T23:59:59.000000Z",
    "organization_id": 1,
    "point_sale": 1,
    "days_until_expiration": 350
  },
  "user": {
    "id": 1,
    "name": "Usuario Ejemplo",
    "email": "usuario@example.com"
  },
  "warning": "La licencia vence en 7 días",
  "warning_code": "LICENSE_EXPIRING_SOON"
}
```

### Errores Comunes

- **401**: Credenciales inválidas
- **403**: Usuario sin acceso a organización o licencia expirada
- **422**: Datos de entrada inválidos
- **503**: Error al verificar licencia remota

---

## 2. POST /api/v1/auth/refresh_token

**Propósito**: Renovar un token vigente verificando que la licencia siga activa.

**Acceso**: Protegido (requiere token válido en header)

### Parámetros

**Body**: Vacío (no requiere parámetros)

**Headers Requeridos**:
```
Authorization: Bearer {token_actual}
Content-Type: application/json
```

### Proceso de Renovación

1. Extrae token del header `Authorization`
2. Verifica que el token NO esté expirado
3. Obtiene `organization_id` y `point_sale` del token actual
4. Consulta API remota para verificar licencia actualizada
5. Valida que la licencia no esté expirada
6. Genera nuevo token con nueva fecha de expiración
7. **Revoca el token anterior** automáticamente

### Respuesta Exitosa (200)

```json
{
  "success": true,
  "message": "Token renovado exitosamente",
  "token": "2|xyz789abc...",
  "token_info": {
    "name": "API Token - 2024-01-15 10:30:00",
    "expires_at": "2024-12-31T23:59:59.000000Z",
    "organization_id": 1,
    "point_sale": 1,
    "days_until_expiration": 350
  },
  "warning": "La licencia vence en 5 días",
  "warning_code": "LICENSE_EXPIRING_SOON"
}
```

### Errores Comunes

- **401**: Token expirado (requiere hacer login nuevamente)
- **400**: Token sin organización asignada
- **403**: Licencia expirada
- **404**: Organización no encontrada
- **503**: Error al verificar licencia remota

---

## Flujo de Uso Típico

### Primera Vez
```bash
# 1. Login
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@example.com",
    "password": "password123",
    "organization_id": 1,
    "point_sale": 1
  }'
# Respuesta: token = "1|abc..."
```

### Renovación Proactiva
```bash
# 2. Renovar antes de que expire
curl -X POST http://localhost/api/v1/auth/refresh_token \
  -H "Authorization: Bearer 1|abc..." \
  -H "Content-Type: application/json"
# Respuesta: nuevo token = "2|xyz..."
# Token anterior (1|abc...) queda revocado
```

### Token Expirado
```bash
# 3. Si token expiró, renovación falla
curl -X POST http://localhost/api/v1/auth/refresh_token \
  -H "Authorization: Bearer 1|token_expirado..."
# Respuesta 401: "TOKEN_EXPIRED"
# Acción: Debe hacer login nuevamente
```

---

## Modelo de Seguridad

### Política de Tokens

| Estado Token | Estado Licencia | Resultado |
|--------------|-----------------|-----------|
| Vigente | Vigente | Renovación permitida |
| Vigente | Expirada | Bloqueado (403) |
| Expirado | Vigente | Requiere login (401) |
| Expirado | Expirada | Bloqueado (401/403) |

### Principio de Seguridad

**Tokens expirados NO se pueden renovar** - Requieren autenticación completa con credenciales. Esto previene:
- Uso de tokens robados después de expiración
- Ataques con tokens comprometidos
- Acceso no autorizado con tokens antiguos

---

## Advertencias de Licencia

Ambas APIs incluyen advertencia automática cuando la licencia vence en **≤ 7 días**:

```json
{
  "warning": "La licencia vence en 5 días",
  "warning_code": "LICENSE_EXPIRING_SOON"
}
```

Esto permite a los clientes API tomar acción proactiva antes de que la licencia expire.

---

## Integración con Sistema de Licencias

### API Remota Consultada

```
GET {APP_REMOTE_LICENSE}/app/licenses/{ruc}/{point_sale}
Authorization: Bearer {APP_REMOTE_LICENSE_KEY}
```

### Campo Clave
`FechaVencimiento` - Fecha de expiración de la licencia

### Sincronización
- **Login**: Consulta licencia al crear token
- **Refresh**: Consulta licencia al renovar token
- **Expiración del token**: Siempre = `FechaVencimiento` de licencia

---

## Códigos de Error

| Código | Significado | HTTP | Acción |
|--------|-------------|------|--------|
| `INVALID_CREDENTIALS` | Email/password incorrectos | 401 | Verificar credenciales |
| `TOKEN_EXPIRED` | Token expirado | 401 | Login con credenciales |
| `TOKEN_NOT_FOUND` | Token no encontrado | 401 | Login con credenciales |
| `LICENSE_EXPIRED` | Licencia vencida | 403 | Renovar licencia |
| `ORGANIZATION_ACCESS_DENIED` | Sin acceso a organización | 403 | Contactar administrador |
| `ORGANIZATION_NOT_FOUND` | Organización no existe | 404 | Verificar organization_id |
| `INVALID_TOKEN_DATA` | Token sin metadata | 400 | Crear nuevo token |
| `LICENSE_VERIFICATION_ERROR` | Error API licencia | 503 | Reintentar más tarde |
| `LICENSE_EXPIRING_SOON` | Advertencia (≤7 días) | 200 | Planear renovación |

---

## Archivos Implementados

### Servicios
- `app/Services/LicenseVerificationService.php` - Verificación de licencia remota

### Controladores
- `app/Http/Controllers/V1/AuthController.php` - Login y refresh token

### Requests
- `app/Http/Requests/Auth/LoginRequest.php` - Validación de login
- `app/Http/Requests/Auth/RefreshTokenRequest.php` - Validación de refresh

### Rutas
- `routes/api.php` - Rutas públicas y protegidas

### Documentación
- `docs/api/auth-token-refresh-api.md` - Documentación completa de API
- `docs/testing/auth-token-testing-guide.md` - Guía de testing
- `docs/technical/auth-token-implementation-summary.md` - Resumen técnico

### Testing
- `scripts/test-auth-token.sh` - Script interactivo de pruebas

---

## Testing Rápido

```bash
# Script interactivo
./scripts/test-auth-token.sh

# Test completo
./scripts/test-auth-token.sh complete

# Solo login
./scripts/test-auth-token.sh login

# Solo refresh
./scripts/test-auth-token.sh refresh
```

---

## Notas Importantes

1. **Token anterior se revoca**: Al renovar, el token antiguo deja de funcionar inmediatamente
2. **Licencia sincronizada**: Cada operación consulta la API remota para obtener fecha actualizada
3. **Sin expiración predeterminada**: Si licencia no tiene vencimiento, token tampoco expira
4. **Point sale requerido**: Login requiere especificar punto de venta
5. **Organización validada**: Usuario debe tener acceso a la organización especificada
