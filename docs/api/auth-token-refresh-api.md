# API de Autenticación y Renovación de Token

## Descripción General

Sistema de autenticación API con renovación de tokens basado en verificación de licencia remota. Implementa seguridad robusta donde tokens expirados requieren autenticación completa con credenciales.

## Endpoints

### 1. POST /api/v1/auth/login

Autenticación con credenciales para obtener nuevo token con validación de licencia.

**Autenticación**: No requiere (público)

**Request Body**:
```json
{
  "email": "usuario@example.com",
  "password": "password123",
  "organization_id": 1,
  "point_sale": 1,
  "token_name": "Mi Token API" // Opcional
}
```

**Validaciones**:
- `email`: required, email válido
- `password`: required, string
- `organization_id`: required, integer, debe existir en tabla organizations
- `point_sale`: required, integer, entre 0-999
- `token_name`: opcional, string, máximo 255 caracteres

**Respuesta Exitosa** (200):
```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "token": "1|abcd1234...",
  "token_info": {
    "name": "Mi Token API",
    "expires_at": "2024-12-31T23:59:59.000000Z",
    "organization_id": 1,
    "point_sale": 1,
    "days_until_expiration": 30
  },
  "user": {
    "id": 1,
    "name": "Usuario Ejemplo",
    "email": "usuario@example.com"
  },
  "warning": "La licencia vence en 7 días", // Solo si está próxima a vencer
  "warning_code": "LICENSE_EXPIRING_SOON" // Solo si está próxima a vencer
}
```

**Errores**:

- **401 Unauthorized** - Credenciales inválidas:
```json
{
  "success": false,
  "message": "Credenciales inválidas",
  "code": "INVALID_CREDENTIALS"
}
```

- **403 Forbidden** - Usuario sin acceso a organización:
```json
{
  "success": false,
  "message": "Usuario no tiene acceso a esta organización",
  "code": "ORGANIZATION_ACCESS_DENIED"
}
```

- **403 Forbidden** - Licencia expirada:
```json
{
  "success": false,
  "message": "La licencia ha expirado",
  "code": "LICENSE_EXPIRED",
  "license_info": {
    "expired_at": "2024-01-01T00:00:00.000000Z",
    "expired_days_ago": 30
  }
}
```

- **422 Unprocessable Entity** - Datos inválidos:
```json
{
  "success": false,
  "message": "Datos de entrada inválidos",
  "errors": {
    "email": ["El campo email debe ser una dirección válida"],
    "organization_id": ["El campo organization_id seleccionado no existe"]
  }
}
```

- **503 Service Unavailable** - Error verificando licencia:
```json
{
  "success": false,
  "message": "No se pudo verificar la licencia. Intente nuevamente.",
  "code": "LICENSE_VERIFICATION_ERROR"
}
```

---

### 2. POST /api/v1/auth/refresh_token

Renovar token vigente con validación de licencia activa.

**Autenticación**: Bearer Token (auth:sanctum)

**Headers**:
```
Authorization: Bearer 1|abcd1234...
```

**Request Body**: Vacío (no requiere parámetros)

**Respuesta Exitosa** (200):
```json
{
  "success": true,
  "message": "Token renovado exitosamente",
  "token": "2|xyz5678...",
  "token_info": {
    "name": "API Token - 2024-01-15 10:30:00",
    "expires_at": "2024-12-31T23:59:59.000000Z",
    "organization_id": 1,
    "point_sale": 1,
    "days_until_expiration": 30
  },
  "warning": "La licencia vence en 5 días", // Solo si está próxima a vencer
  "warning_code": "LICENSE_EXPIRING_SOON" // Solo si está próxima a vencer
}
```

**Errores**:

- **401 Unauthorized** - Token expirado (requiere login):
```json
{
  "success": false,
  "message": "El token ha expirado. Debe autenticarse nuevamente.",
  "code": "TOKEN_EXPIRED",
  "action_required": "login",
  "login_endpoint": "/api/v1/auth/login",
  "expired_at": "2024-01-01T00:00:00.000000Z"
}
```

- **401 Unauthorized** - Token no encontrado:
```json
{
  "success": false,
  "message": "No se encontró token de autenticación",
  "code": "TOKEN_NOT_FOUND"
}
```

- **400 Bad Request** - Token sin datos completos:
```json
{
  "success": false,
  "message": "Token no tiene organización o punto de venta asignado",
  "code": "INVALID_TOKEN_DATA"
}
```

- **403 Forbidden** - Licencia expirada:
```json
{
  "success": false,
  "message": "La licencia ha expirado. Contacte al administrador.",
  "code": "LICENSE_EXPIRED",
  "action_required": "renew_license",
  "license_info": {
    "expired_at": "2024-01-01T00:00:00.000000Z",
    "expired_days_ago": 15
  }
}
```

- **404 Not Found** - Organización no existe:
```json
{
  "success": false,
  "message": "Organización no encontrada",
  "code": "ORGANIZATION_NOT_FOUND"
}
```

- **503 Service Unavailable** - Error verificando licencia:
```json
{
  "success": false,
  "message": "No se pudo verificar la licencia. Intente nuevamente.",
  "code": "LICENSE_VERIFICATION_ERROR"
}
```

---

## Flujo de Uso

### Escenario 1: Primera Autenticación

```bash
# 1. Login con credenciales
curl -X POST https://api.docucenter.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@example.com",
    "password": "password123",
    "organization_id": 1,
    "point_sale": 1,
    "token_name": "Token Producción"
  }'

# Respuesta: token = "1|abcd1234..."
```

### Escenario 2: Renovación de Token Vigente

```bash
# 2. Renovar token antes de que expire
curl -X POST https://api.docucenter.com/api/v1/auth/refresh_token \
  -H "Authorization: Bearer 1|abcd1234..." \
  -H "Content-Type: application/json"

# Respuesta: nuevo token = "2|xyz5678..."
# Token anterior se revoca automáticamente
```

### Escenario 3: Token Expirado

```bash
# 3. Intentar renovar token expirado
curl -X POST https://api.docucenter.com/api/v1/auth/refresh_token \
  -H "Authorization: Bearer 1|token_expirado..." \
  -H "Content-Type: application/json"

# Respuesta 401: code = "TOKEN_EXPIRED", action_required = "login"

# 4. Login nuevamente con credenciales
curl -X POST https://api.docucenter.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@example.com",
    "password": "password123",
    "organization_id": 1,
    "point_sale": 1
  }'
```

---

## Modelo de Seguridad

### Políticas de Token

| Estado Token | Estado Licencia | Acción Permitida | HTTP Code |
|--------------|-----------------|------------------|-----------|
| Vigente | Vigente | Renovar token | 200 |
| Vigente | Expirada | Bloqueado | 403 |
| Expirado | Vigente | Requiere login | 401 |
| Expirado | Expirada | Bloqueado | 401/403 |
| Inválido | N/A | Bloqueado | 401 |

### Verificación de Licencia

- **API Remota**: Configurada en `APP_REMOTE_LICENSE` (env)
- **Endpoint**: `GET /app/licenses/{ruc}/{point_sale}`
- **Campo Clave**: `FechaVencimiento` (fecha de expiración)
- **Sincronización**: Cada renovación consulta API remota
- **Advertencia**: Si licencia vence en ≤7 días, incluye `warning` en respuesta

### Expiración de Tokens

- **Fuente**: Fecha de vencimiento de licencia (`FechaVencimiento`)
- **Campo**: `expires_at` en tabla `personal_access_tokens`
- **Validación**: Verificada en cada request por Sanctum
- **Renovación**: Token anterior se revoca, nuevo token hereda expiración de licencia

---

## Códigos de Error

| Código | Significado | HTTP Code | Acción Recomendada |
|--------|-------------|-----------|-------------------|
| `INVALID_CREDENTIALS` | Email/password incorrectos | 401 | Verificar credenciales |
| `TOKEN_EXPIRED` | Token ha expirado | 401 | Login con credenciales |
| `TOKEN_NOT_FOUND` | No se encontró token | 401 | Login con credenciales |
| `LICENSE_EXPIRED` | Licencia vencida | 403 | Renovar licencia |
| `ORGANIZATION_ACCESS_DENIED` | Usuario sin acceso | 403 | Contactar administrador |
| `ORGANIZATION_NOT_FOUND` | Organización no existe | 404 | Verificar organization_id |
| `INVALID_TOKEN_DATA` | Token sin metadata | 400 | Crear nuevo token |
| `LICENSE_VERIFICATION_ERROR` | Error API licencia | 503 | Reintentar más tarde |
| `LICENSE_EXPIRING_SOON` | Licencia vence pronto | 200 | Advertencia en response |
| `INTERNAL_ERROR` | Error del servidor | 500 | Contactar soporte |

---

## Implementación Técnica

### Servicio de Verificación

**Clase**: `App\Services\LicenseVerificationService`

**Métodos**:
- `verifyLicense(Organization $org, int $pointSale): array`
  - Consulta API remota
  - Retorna: `success`, `expires_at`, `is_expired`, `days_remaining`
  
- `isLicenseExpiringSoon(Carbon $expiresAt, int $threshold = 7): bool`
  - Verifica si licencia vence en menos de N días

### Controlador

**Clase**: `App\Http\Controllers\V1\AuthController`

**Métodos**:
- `login(Request $request)`: Autenticación con credenciales
- `refreshToken(Request $request)`: Renovación de token vigente

### Rutas

```php
// Ruta protegida (requiere auth:sanctum)
Route::post('/api/v1/auth/refresh_token', [AuthController::class, 'refreshToken']);

// Ruta pública
Route::post('/api/v1/auth/login', [AuthController::class, 'login']);
```

### Base de Datos

**Tabla**: `personal_access_tokens`

**Campos Clave**:
- `expires_at`: Timestamp de expiración (de licencia)
- `organization_id`: ID de organización
- `point_sale`: Número de punto de venta (0-999)
- `abilities`: Permisos del token (default: `["*"]`)

---

## Logging

Todas las operaciones registran logs:

```php
// Login exitoso
Log::info('Login exitoso y token creado', [
    'user_id' => $user->id,
    'organization_id' => $organizationId,
    'point_sale' => $pointSale
]);

// Token renovado
Log::info('Token renovado exitosamente', [
    'user_id' => $userId,
    'old_token_id' => $oldTokenId,
    'new_token_id' => $newTokenId
]);

// Intento con token expirado
Log::warning('Intento de renovar token expirado', [
    'user_id' => $userId,
    'expired_at' => $expirationDate
]);

// Error verificando licencia
Log::error('Error al verificar licencia', [
    'organization_id' => $orgId,
    'error' => $exception->getMessage()
]);
```

---

## Mejores Prácticas

### Para Clientes API

1. **Almacenar Token Seguro**: Usar variables de entorno o vault
2. **Monitorear Expiración**: Revisar `expires_at` y `days_until_expiration`
3. **Renovar Proactivamente**: Renovar antes de que expire (ej: 3 días antes)
4. **Manejar 401**: Si recibe `TOKEN_EXPIRED`, hacer login automáticamente
5. **Manejar 403**: Si recibe `LICENSE_EXPIRED`, notificar administrador
6. **Respetar Rate Limits**: No hacer renovaciones innecesarias

### Ejemplo de Cliente (JavaScript)

```javascript
class DocuCenterClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
    this.token = null;
  }

  async login(credentials) {
    const response = await fetch(`${this.baseUrl}/api/v1/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials)
    });
    
    const data = await response.json();
    
    if (data.success) {
      this.token = data.token;
      this.tokenExpiry = new Date(data.token_info.expires_at);
      
      // Advertencia si vence pronto
      if (data.warning_code === 'LICENSE_EXPIRING_SOON') {
        console.warn(data.warning);
      }
    }
    
    return data;
  }

  async refreshToken() {
    const response = await fetch(`${this.baseUrl}/api/v1/auth/refresh_token`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    // Token expirado, requiere login
    if (data.code === 'TOKEN_EXPIRED') {
      console.log('Token expirado, requiere autenticación completa');
      return { requiresLogin: true };
    }
    
    // Licencia expirada
    if (data.code === 'LICENSE_EXPIRED') {
      console.error('Licencia expirada:', data.license_info);
      throw new Error('LICENSE_EXPIRED');
    }
    
    if (data.success) {
      this.token = data.token;
      this.tokenExpiry = new Date(data.token_info.expires_at);
    }
    
    return data;
  }

  shouldRefreshToken() {
    if (!this.tokenExpiry) return false;
    
    const now = new Date();
    const daysUntilExpiry = (this.tokenExpiry - now) / (1000 * 60 * 60 * 24);
    
    // Renovar si vence en menos de 3 días
    return daysUntilExpiry < 3;
  }

  async makeRequest(endpoint, options = {}) {
    // Renovar token si es necesario
    if (this.shouldRefreshToken()) {
      const result = await this.refreshToken();
      
      if (result.requiresLogin) {
        throw new Error('Requiere autenticación completa');
      }
    }
    
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...options,
      headers: {
        ...options.headers,
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    
    // Manejar token expirado en requests normales
    if (response.status === 401) {
      const data = await response.json();
      
      if (data.code === 'TOKEN_EXPIRED') {
        throw new Error('TOKEN_EXPIRED');
      }
    }
    
    return response.json();
  }
}

// Uso
const client = new DocuCenterClient('https://api.docucenter.com');

// Login inicial
await client.login({
  email: 'usuario@example.com',
  password: 'password123',
  organization_id: 1,
  point_sale: 1
});

// Hacer requests (renovación automática)
const data = await client.makeRequest('/api/v1/fe/create_sale_meypar', {
  method: 'POST',
  body: JSON.stringify({ /* datos venta */ })
});
```

---

## Testing

### Test Manual con cURL

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@docucenter.com",
    "password": "password",
    "organization_id": 1,
    "point_sale": 1
  }' | jq -r '.token')

echo "Token: $TOKEN"

# 2. Renovar token
curl -X POST http://localhost/api/v1/auth/refresh_token \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq

# 3. Verificar información del usuario
curl -X GET http://localhost/api/user \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Casos de Prueba

1. **Login exitoso con licencia vigente**
   - Credenciales correctas
   - Organización existe
   - Usuario tiene acceso
   - Licencia activa
   - Resultado esperado: 200, token generado

2. **Login con licencia expirada**
   - Credenciales correctas
   - Licencia vencida
   - Resultado esperado: 403, `LICENSE_EXPIRED`

3. **Renovar token vigente**
   - Token no expirado
   - Licencia vigente
   - Resultado esperado: 200, nuevo token

4. **Renovar token expirado**
   - Token expirado
   - Resultado esperado: 401, `TOKEN_EXPIRED`, requiere login

5. **Renovar con licencia expirada**
   - Token vigente
   - Licencia expirada
   - Resultado esperado: 403, `LICENSE_EXPIRED`

6. **API de licencia no disponible**
   - API remota caída
   - Resultado esperado: 503, `LICENSE_VERIFICATION_ERROR`

---

## Configuración

### Variables de Entorno

```env
# API de verificación de licencias
APP_REMOTE_LICENSE=https://license-api.docucenter.com
APP_REMOTE_LICENSE_KEY=your-api-key-here

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=docucenter
DB_USERNAME=root
DB_PASSWORD=secret
```

### Configuración en config/weirdo_panel.php

```php
'custom_remote_license_verification' => [
    'url_base' => env('APP_REMOTE_LICENSE'),
    'key_value' => env('APP_REMOTE_LICENSE_KEY'),
],
```

---

## Troubleshooting

### Token no se renueva

**Síntoma**: Error 401 al renovar token

**Posibles causas**:
- Token ya expiró → Hacer login
- Token revocado manualmente → Crear nuevo token
- API remota no responde → Verificar conectividad

### Licencia siempre aparece expirada

**Síntoma**: Error 403 `LICENSE_EXPIRED` con licencia vigente

**Verificar**:
- Fecha del servidor: `date` (debe estar sincronizada)
- Response de API remota: Verificar formato `FechaVencimiento`
- Logs de LicenseVerificationService

### Error 503 al verificar licencia

**Síntoma**: `LICENSE_VERIFICATION_ERROR`

**Verificar**:
- Conectividad a API remota
- Variables `APP_REMOTE_LICENSE` y `APP_REMOTE_LICENSE_KEY`
- Formato de response de API remota
- Logs en `storage/logs/laravel.log`

---

## Migración desde Sistema Anterior

Si tienes tokens creados sin `expires_at`:

```sql
-- Identificar tokens sin expiración
SELECT id, tokenable_id, name, created_at, expires_at 
FROM personal_access_tokens 
WHERE expires_at IS NULL;

-- Actualizar con fecha de licencia (ejemplo manual)
UPDATE personal_access_tokens 
SET expires_at = '2024-12-31 23:59:59'
WHERE id = 123 AND expires_at IS NULL;
```

**Recomendación**: Crear nuevos tokens usando `/api/v1/auth/login` para sincronizar con licencias.

---

## Changelog

### v1.0.0 (2024-01-15)
- Implementación inicial
- Endpoint `/api/v1/auth/login`
- Endpoint `/api/v1/auth/refresh_token`
- Servicio `LicenseVerificationService`
- Validación de licencia remota
- Política de seguridad: tokens expirados requieren login
- Advertencia de licencia próxima a vencer (≤7 días)
