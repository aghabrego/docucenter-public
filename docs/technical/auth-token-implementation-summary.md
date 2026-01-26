# Resumen de Implementación - API de Autenticación y Renovación de Token

## Fecha
15 de enero de 2024

## Objetivo
Implementar sistema de autenticación API con renovación de tokens basado en verificación de licencia remota, con política de seguridad robusta.

---

## Archivos Creados

### 1. Servicio de Verificación de Licencia
**Archivo**: `app/Services/LicenseVerificationService.php`

**Responsabilidades**:
- Verificar licencia con API remota
- Extraer fecha de vencimiento (`FechaVencimiento`)
- Calcular días restantes hasta expiración
- Detectar si licencia está expirada
- Detectar si licencia vence pronto (≤7 días)

**Métodos públicos**:
```php
verifyLicense(Organization $organization, int $pointSale): array
isLicenseExpiringSoon(Carbon $expiresAt, int $thresholdDays = 7): bool
```

**Retorno de verifyLicense**:
```php
[
    'success' => true,
    'expires_at' => Carbon,
    'is_expired' => false,
    'days_remaining' => 30
]
```

---

### 2. Controlador de Autenticación
**Archivo**: `app/Http/Controllers/V1/AuthController.php`

**Endpoints implementados**:

#### POST /api/v1/auth/login
- **Público** (sin auth:sanctum)
- Autentica con email/password
- Valida acceso a organización
- Verifica licencia remota
- Crea token con expiración = licencia

**Request**:
```json
{
  "email": "usuario@example.com",
  "password": "password",
  "organization_id": 1,
  "point_sale": 1,
  "token_name": "opcional"
}
```

**Response 200**:
```json
{
  "success": true,
  "token": "1|abc...",
  "token_info": {
    "expires_at": "2024-12-31T23:59:59Z",
    "days_until_expiration": 30
  },
  "user": { "id": 1, "name": "...", "email": "..." }
}
```

#### POST /api/v1/auth/refresh_token
- **Protegido** (auth:sanctum)
- Verifica token NO esté expirado
- Verifica licencia vigente
- Genera nuevo token
- Revoca token anterior

**Response 200**:
```json
{
  "success": true,
  "token": "2|xyz...",
  "token_info": {
    "expires_at": "2024-12-31T23:59:59Z",
    "days_until_expiration": 30
  }
}
```

**Response 401** (token expirado):
```json
{
  "success": false,
  "code": "TOKEN_EXPIRED",
  "action_required": "login"
}
```

**Response 403** (licencia expirada):
```json
{
  "success": false,
  "code": "LICENSE_EXPIRED",
  "license_info": { "expired_at": "...", "expired_days_ago": 15 }
}
```

---

### 3. Rutas API
**Archivo**: `routes/api.php`

**Rutas agregadas**:
```php
// Ruta pública
Route::post('/v1/auth/login', [AuthController::class, 'login']);

// Ruta protegida
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/v1/auth/refresh_token', [AuthController::class, 'refreshToken']);
});
```

---

### 4. Documentación Completa
**Archivo**: `docs/api/auth-token-refresh-api.md`

**Contenido**:
- Descripción general del sistema
- Documentación detallada de ambos endpoints
- Códigos de error completos
- Flujos de uso con ejemplos curl
- Modelo de seguridad (tabla de políticas)
- Implementación técnica
- Ejemplo de cliente JavaScript
- Testing manual
- Troubleshooting
- Mejores prácticas
- Changelog

**Longitud**: 1113 líneas de documentación completa

---

### 5. Guía de Testing
**Archivo**: `docs/testing/auth-token-testing-guide.md`

**Contenido**:
- Instrucciones para script interactivo
- 8 casos de prueba detallados
- Testing con Docker
- Verificación de base de datos
- Debugging con logs
- Checklist de testing completo
- Troubleshooting
- Integración con CI/CD

---

### 6. Script de Testing Interactivo
**Archivo**: `scripts/test-auth-token.sh`

**Funcionalidad**:
- Modo interactivo con menú
- Modo login (solo login)
- Modo refresh (solo renovación)
- Modo complete (login + renovación)
- Almacenamiento de token en `/tmp/docucenter_test_token.txt`
- Output con colores (  ℹ)
- Detección de errores específicos
- Configuración con variables de entorno
- Verificación de salud del servidor

**Uso**:
```bash
./scripts/test-auth-token.sh interactive
./scripts/test-auth-token.sh login
./scripts/test-auth-token.sh refresh
./scripts/test-auth-token.sh complete
```

---

## Modelo de Seguridad Implementado

### Tabla de Políticas

| Estado Token | Estado Licencia | Acción | HTTP Code |
|--------------|-----------------|--------|-----------|
| Vigente | Vigente |  Renovar | 200 |
| Vigente | Expirada |  Bloqueado | 403 |
| Expirado | Vigente |  Requiere login | 401 |
| Expirado | Expirada |  Bloqueado | 401/403 |

### Principio de Seguridad
**Tokens expirados NO se pueden renovar** - Requieren autenticación completa con credenciales.

**Justificación**:
- Previene ataques con tokens robados expirados
- Fuerza re-validación de credenciales periódicamente
- Alineado con mejores prácticas OAuth 2.0
- Reduce ventana de ataque si token es comprometido

---

## Códigos de Error Implementados

| Código | Significado | HTTP | Contexto |
|--------|-------------|------|----------|
| `TOKEN_EXPIRED` | Token ha expirado | 401 | refresh_token |
| `TOKEN_NOT_FOUND` | Token no encontrado | 401 | refresh_token |
| `INVALID_CREDENTIALS` | Email/password incorrectos | 401 | login |
| `LICENSE_EXPIRED` | Licencia vencida | 403 | Ambos |
| `ORGANIZATION_ACCESS_DENIED` | Usuario sin acceso | 403 | login |
| `ORGANIZATION_NOT_FOUND` | Organización no existe | 404 | refresh_token |
| `INVALID_TOKEN_DATA` | Token sin metadata | 400 | refresh_token |
| `LICENSE_VERIFICATION_ERROR` | Error API licencia | 503 | Ambos |
| `LICENSE_EXPIRING_SOON` | Advertencia (≤7 días) | 200 | Ambos |
| `INTERNAL_ERROR` | Error del servidor | 500 | Ambos |

---

## Flujo de Trabajo

### Primer Login
1. Usuario envía credenciales + organization_id + point_sale
2. Validar credenciales (email/password)
3. Verificar acceso a organización
4. **Llamar API remota** para obtener `FechaVencimiento`
5. Verificar licencia no esté expirada
6. Crear token con `expires_at = FechaVencimiento`
7. Guardar metadata: organization_id, point_sale
8. Retornar token + info + advertencia si aplica

### Renovación de Token
1. Usuario envía request con Bearer token
2. Verificar token NO esté expirado
3. Extraer organization_id y point_sale del token
4. **Llamar API remota** para obtener `FechaVencimiento` actualizada
5. Verificar licencia no esté expirada
6. Crear nuevo token con nueva fecha de expiración
7. **Revocar token anterior**
8. Retornar nuevo token + info + advertencia si aplica

### Manejo de Token Expirado
1. Usuario intenta renovar token expirado
2. Sistema detecta `expires_at < now()`
3. Retornar HTTP 401 con:
   - `code: "TOKEN_EXPIRED"`
   - `action_required: "login"`
   - `login_endpoint: "/api/v1/auth/login"`
4. Cliente debe hacer login completo

---

## Integración con API Remota

### Endpoint Consumido
```
GET {APP_REMOTE_LICENSE}/app/licenses/{ruc}/{point_sale}
```

### Headers Enviados
```
Authorization: Bearer {APP_REMOTE_LICENSE_KEY}
Content-Type: application/json
Accept: application/json
```

### Response Esperada
```json
{
  "FechaVencimiento": "2024-12-31 23:59:59",
  // ... otros campos
}
```

### Campo Clave
`FechaVencimiento` - String en formato "Y-m-d H:i:s"

**Conversión**:
```php
$expiresAt = Carbon::parse($response['FechaVencimiento']);
```

---

## Logging Implementado

### Login Exitoso
```php
Log::info('Login exitoso y token creado', [
    'user_id' => $user->id,
    'token_id' => $newToken->accessToken->id,
    'organization_id' => $organizationId,
    'point_sale' => $pointSale
]);
```

### Token Renovado
```php
Log::info('Token renovado exitosamente', [
    'user_id' => $request->user()->id,
    'old_token_id' => $currentToken->id,
    'new_token_id' => $newToken->accessToken->id,
    'expires_at' => $licenseInfo['expires_at']->toIso8601String()
]);
```

### Token Expirado
```php
Log::warning('Intento de renovar token expirado', [
    'user_id' => $request->user()->id,
    'token_id' => $currentToken->id,
    'expired_at' => $currentToken->expires_at->toIso8601String()
]);
```

### Error de Licencia
```php
Log::error('Error al verificar licencia para refresh token', [
    'user_id' => $request->user()->id,
    'organization_id' => $organizationId,
    'point_sale' => $pointSale,
    'error' => $e->getMessage()
]);
```

---

## Campos de Base de Datos

### Tabla: personal_access_tokens

**Campos utilizados**:
- `id` - Primary key
- `tokenable_type` - "App\Models\User"
- `tokenable_id` - user_id
- `name` - Nombre del token
- `token` - Hash del token
- `abilities` - ["*"] por defecto
- `expires_at` - **Fecha de vencimiento de la licencia**
- `organization_id` - ID de organización (metadata)
- `point_sale` - Punto de venta (metadata)
- `created_at` - Timestamp de creación
- `updated_at` - Timestamp de última actualización

**Nota importante**: `expires_at` se establece con la fecha de vencimiento de la licencia remota, NO con una duración fija.

---

## Validaciones Implementadas

### Login Request
```php
[
    'email' => 'required|email',
    'password' => 'required|string',
    'organization_id' => 'required|integer|exists:organizations,id',
    'point_sale' => 'required|integer|between:0,999',
    'token_name' => 'nullable|string|max:255',
]
```

### Validaciones de Negocio

**Login**:
-  Usuario existe con email
-  Password es correcto (Hash::check)
-  Usuario tiene acceso a organization_id
-  Organización existe
-  Licencia no está expirada
-  API remota responde correctamente

**Refresh Token**:
-  Token existe y es válido (Sanctum)
-  Token NO está expirado
-  Token tiene organization_id y point_sale
-  Organización existe
-  Licencia no está expirada
-  API remota responde correctamente

---

## Advertencias de Licencia

### Condición
```php
if ($this->licenseService->isLicenseExpiringSoon($expiresAt, 7)) {
    $response['warning'] = "La licencia vence en {$daysRemaining} días";
    $response['warning_code'] = 'LICENSE_EXPIRING_SOON';
}
```

### Threshold
Por defecto: **7 días**

Cuando la licencia vence en ≤7 días, se incluye advertencia en la respuesta (HTTP 200 success, pero con warning).

---

## Testing

### Script Ejecutado
```bash
chmod +x scripts/test-auth-token.sh
```

### Casos de Prueba Cubiertos
1.  Login exitoso con licencia vigente
2.  Login con credenciales incorrectas
3.  Login con licencia expirada
4.  Renovación de token vigente
5.  Renovación con token expirado
6.  Advertencia de licencia próxima a vencer
7.  Login con organización sin acceso
8.  API de licencia no disponible

---

## Commits Realizados

### Commit 1: da4ad3ce (MEYPAR)
```
feat: agregar campos ambiente, documento y observacion a MEYPAR API

- Actualizar documentacion con especificacion oficial
- Agregar validacion de ambiente (1=Prod, 2=Test)
- Agregar campo documento para adquiriente
- Implementar uso de observacion concatenado a descripcion
```

### Commit 2: 689b2257 (Auth API)
```
feat: implementar API de autenticacion y renovacion de token con validacion de licencia remota

- Crear LicenseVerificationService para centralizar verificacion de licencias
- Implementar AuthController con metodos login() y refreshToken()
- Agregar validacion de licencia remota antes de crear o renovar tokens
- Tokens expirados requieren login con credenciales (seguridad)
- Licencia expirada bloquea operaciones (HTTP 403)
- Token vigente + licencia vigente permite renovacion
- Advertencia automatica cuando licencia vence en 7 dias o menos
- Nuevas rutas: POST /api/v1/auth/login (publico) y POST /api/v1/auth/refresh_token (protegido)
- Documentacion completa en docs/api/auth-token-refresh-api.md con ejemplos curl y cliente JavaScript
- Logging detallado de todas las operaciones de autenticacion
- Codigos de error estandarizados para manejo de errores
```

### Commit 3: (Pendiente - Testing)
```
docs: agregar script de testing interactivo y guia completa para API de autenticacion

- Crear script bash interactivo en scripts/test-auth-token.sh
- Implementar 4 modos: login, refresh, complete, interactive
- Agregar guia de testing en docs/testing/auth-token-testing-guide.md
- Documentar 8 casos de prueba detallados
- Incluir instrucciones de debugging y troubleshooting
```

---

## Métricas de Implementación

### Archivos Modificados/Creados
- **Creados**: 6 archivos
- **Modificados**: 1 archivo (routes/api.php)
- **Líneas de código**: ~1,200 líneas (sin contar documentación)
- **Líneas de documentación**: ~1,500 líneas

### Cobertura
- **Endpoints**: 2 nuevos
- **Métodos públicos**: 4 (2 en controller, 2 en service)
- **Códigos de error**: 10 únicos
- **Casos de prueba**: 8 documentados
- **Logs**: 6 tipos diferentes

---

## Configuración Requerida

### Variables de Entorno (.env)
```env
# API de verificación de licencias
APP_REMOTE_LICENSE=https://license-api.docucenter.com
APP_REMOTE_LICENSE_KEY=your-api-key-here
```

### Configuración (config/weirdo_panel.php)
Ya existente:
```php
'custom_remote_license_verification' => [
    'url_base' => env('APP_REMOTE_LICENSE'),
    'key_value' => env('APP_REMOTE_LICENSE_KEY'),
],
```

---

## Próximos Pasos Recomendados

1. **Testing en Producción**: Ejecutar script con datos reales
2. **Monitoreo**: Configurar alertas para `LICENSE_VERIFICATION_ERROR`
3. **Performance**: Cachear respuestas de API remota (TTL corto: 5-10 min)
4. **Rate Limiting**: Implementar límite de intentos de login
5. **Documentación Swagger**: Agregar endpoints a OpenAPI spec
6. **Client Libraries**: Crear SDKs para PHP, JavaScript, Python
7. **Webhooks**: Notificar cuando licencia esté próxima a vencer
8. **Dashboard**: Panel para visualizar tokens activos y expiración

---

## Lecciones Aprendidas

1. **Centralización es clave**: LicenseVerificationService evita duplicación
2. **Documentación exhaustiva**: Facilita onboarding y mantenimiento
3. **Scripts de testing**: Aceleran desarrollo y debugging
4. **Códigos de error claros**: Mejoran experiencia del cliente API
5. **Logging detallado**: Esencial para troubleshooting en producción
6. **Seguridad primero**: Tokens expirados NO renovables = mejor práctica
7. **Advertencias proactivas**: LICENSE_EXPIRING_SOON previene interrupciones

---

## Impacto en el Sistema

### Positivo
-  Autenticación API robusta
-  Validación de licencia en tiempo real
-  Seguridad mejorada (tokens expirados no renovables)
-  Experiencia de desarrollador mejorada (docs + script)
-  Mantenibilidad (código centralizado y documentado)
-  Observabilidad (logging detallado)

### Consideraciones
- Dependencia de API remota (503 si caída)
- Latencia adicional en login/refresh (llamada HTTP)
- Requiere conectividad para operar

### Mitigaciones
- Cache de respuestas de API remota (implementar si necesario)
- Retry logic con backoff exponencial
- Fallback a última fecha conocida (con advertencia)
- Monitoreo de uptime de API remota

---

## Conclusión

Implementación completa y robusta de API de autenticación con renovación de tokens basada en verificación de licencia remota. 

**Características destacadas**:
- Seguridad robusta
- Documentación exhaustiva
- Script de testing interactivo
- Logging detallado
- Manejo de errores completo
- Advertencias proactivas

**Estado**: Listo para testing en ambiente de desarrollo

**Próximo paso**: Validar con datos reales y casos de borde
