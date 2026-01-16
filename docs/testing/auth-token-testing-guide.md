# Guía de Testing - API de Autenticación y Renovación de Token

## Descripción

Esta guía proporciona instrucciones detalladas para probar la API de autenticación y renovación de tokens con validación de licencia remota en DocuCenter.

## Scripts de Prueba

### Script Interactivo

**Ubicación**: `scripts/test-auth-token.sh`

Script bash completo para probar todos los flujos de autenticación.

#### Uso Básico

```bash
# Modo interactivo (menú)
./scripts/test-auth-token.sh

# Solo login
./scripts/test-auth-token.sh login

# Solo renovación de token
./scripts/test-auth-token.sh refresh

# Test completo (login + renovación)
./scripts/test-auth-token.sh complete

# Especificar organization_id
./scripts/test-auth-token.sh login 2
./scripts/test-auth-token.sh complete 5
```

#### Configuración con Variables de Entorno

```bash
# Configurar credenciales y endpoints
export BASE_URL="http://localhost"
export TEST_EMAIL="admin@docucenter.com"
export TEST_PASSWORD="password"
export TEST_ORG_ID=1
export TEST_POINT_SALE=1

# Ejecutar pruebas
./scripts/test-auth-token.sh interactive
```

#### Características del Script

- **Modo interactivo**: Menú con 6 opciones
- **Almacenamiento de token**: Guarda token en `/tmp/docucenter_test_token.txt`
- **Códigos de color**: Output visual con ✓, ✗, ⚠, ℹ
- **Manejo de errores**: Detecta token expirado, licencia expirada, etc.
- **Verificación de salud**: Comprueba que el servidor esté accesible
- **Información detallada**: Muestra token info, días restantes, advertencias

---

## Casos de Prueba

### 1. Login Exitoso con Licencia Vigente

**Objetivo**: Verificar autenticación correcta y generación de token

**Pasos**:
1. Asegurar que la licencia esté vigente en API remota
2. Ejecutar: `./scripts/test-auth-token.sh login`
3. Verificar HTTP 200
4. Validar que se recibe token
5. Validar que `token_info.expires_at` coincide con `FechaVencimiento` de licencia

**Resultado Esperado**:
```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "token": "1|abc...",
  "token_info": {
    "expires_at": "2024-12-31T23:59:59.000000Z",
    "days_until_expiration": 30
  }
}
```

**Manual con curl**:
```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@docucenter.com",
    "password": "password",
    "organization_id": 1,
    "point_sale": 1
  }' | jq
```

---

### 2. Login con Credenciales Incorrectas

**Objetivo**: Verificar validación de credenciales

**Pasos**:
1. Exportar password incorrecto: `export TEST_PASSWORD="wrong_password"`
2. Ejecutar: `./scripts/test-auth-token.sh login`
3. Verificar HTTP 401
4. Validar mensaje de error

**Resultado Esperado**:
```json
{
  "success": false,
  "message": "Credenciales inválidas",
  "code": "INVALID_CREDENTIALS"
}
```

---

### 3. Login con Licencia Expirada

**Objetivo**: Verificar bloqueo cuando licencia está vencida

**Pre-requisito**: Configurar API remota para retornar `FechaVencimiento` en el pasado

**Pasos**:
1. Ejecutar: `./scripts/test-auth-token.sh login`
2. Verificar HTTP 403
3. Validar error `LICENSE_EXPIRED`

**Resultado Esperado**:
```json
{
  "success": false,
  "message": "La licencia ha expirado",
  "code": "LICENSE_EXPIRED",
  "license_info": {
    "expired_at": "2024-01-01T00:00:00.000000Z",
    "expired_days_ago": 15
  }
}
```

---

### 4. Renovación de Token Vigente

**Objetivo**: Verificar renovación exitosa

**Pasos**:
1. Hacer login: `./scripts/test-auth-token.sh login`
2. Renovar token: `./scripts/test-auth-token.sh refresh`
3. Verificar HTTP 200
4. Verificar que se recibe nuevo token
5. Verificar que token anterior ya no funciona

**Resultado Esperado**:
```json
{
  "success": true,
  "message": "Token renovado exitosamente",
  "token": "2|xyz...",
  "token_info": {
    "expires_at": "2024-12-31T23:59:59.000000Z",
    "days_until_expiration": 30
  }
}
```

**Verificación manual**:
```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@docucenter.com","password":"password","organization_id":1,"point_sale":1}' \
  | jq -r '.token')

echo "Token original: $TOKEN"

# 2. Renovar
NEW_TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/refresh_token \
  -H "Authorization: Bearer $TOKEN" \
  | jq -r '.token')

echo "Nuevo token: $NEW_TOKEN"

# 3. Verificar que token anterior no funciona
curl -X POST http://localhost/api/v1/auth/refresh_token \
  -H "Authorization: Bearer $TOKEN" \
  | jq

# Debería retornar error 401
```

---

### 5. Renovación con Token Expirado

**Objetivo**: Verificar que tokens expirados no se pueden renovar

**Pasos**:
1. Crear token en BD con `expires_at` en el pasado:
```sql
INSERT INTO personal_access_tokens 
(tokenable_type, tokenable_id, name, token, abilities, expires_at, created_at, updated_at, organization_id, point_sale)
VALUES 
('App\\Models\\User', 1, 'Token Expirado', 'hash_token_expirado', '["*"]', '2024-01-01 00:00:00', NOW(), NOW(), 1, 1);
```
2. Guardar token manualmente en `/tmp/docucenter_test_token.txt`
3. Ejecutar: `./scripts/test-auth-token.sh refresh`
4. Verificar HTTP 401
5. Verificar error `TOKEN_EXPIRED`

**Resultado Esperado**:
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

---

### 6. Advertencia de Licencia Próxima a Vencer

**Objetivo**: Verificar advertencia cuando licencia vence en ≤7 días

**Pre-requisito**: Configurar API remota para retornar `FechaVencimiento` en 5 días

**Pasos**:
1. Ejecutar: `./scripts/test-auth-token.sh login`
2. Verificar HTTP 200
3. Verificar que response incluye `warning` y `warning_code`

**Resultado Esperado**:
```json
{
  "success": true,
  "token": "1|abc...",
  "warning": "La licencia vence en 5 días",
  "warning_code": "LICENSE_EXPIRING_SOON"
}
```

---

### 7. Login con Organización sin Acceso

**Objetivo**: Verificar validación de permisos de organización

**Pasos**:
1. Usar organization_id donde el usuario NO tiene acceso
2. Ejecutar: `./scripts/test-auth-token.sh login 999`
3. Verificar HTTP 403

**Resultado Esperado**:
```json
{
  "success": false,
  "message": "Usuario no tiene acceso a esta organización",
  "code": "ORGANIZATION_ACCESS_DENIED"
}
```

---

### 8. API de Licencia No Disponible

**Objetivo**: Verificar manejo cuando API remota no responde

**Pasos**:
1. Detener API remota o configurar URL incorrecta
2. Ejecutar: `./scripts/test-auth-token.sh login`
3. Verificar HTTP 503
4. Verificar error `LICENSE_VERIFICATION_ERROR`

**Resultado Esperado**:
```json
{
  "success": false,
  "message": "No se pudo verificar la licencia. Intente nuevamente.",
  "code": "LICENSE_VERIFICATION_ERROR"
}
```

---

## Testing con Docker

### Ejecutar dentro del contenedor

```bash
# Acceder al contenedor
docker exec -it docucenter-app-1 bash

# Ejecutar pruebas
./scripts/test-auth-token.sh interactive
```

### Configurar para ambiente Docker

```bash
# Si el contenedor usa puerto diferente
export BASE_URL="http://localhost:8080"

# Ejecutar pruebas
./scripts/test-auth-token.sh complete
```

---

## Verificación de Base de Datos

### Revisar tokens creados

```bash
docker exec -it docucenter-mariadb-1 mysql -u root -p
```

```sql
USE docucenter;

-- Ver todos los tokens
SELECT id, tokenable_id, name, expires_at, organization_id, point_sale, created_at
FROM personal_access_tokens
ORDER BY id DESC
LIMIT 10;

-- Ver tokens expirados
SELECT id, name, expires_at, DATEDIFF(NOW(), expires_at) as days_expired
FROM personal_access_tokens
WHERE expires_at < NOW();

-- Ver tokens vigentes
SELECT id, name, expires_at, DATEDIFF(expires_at, NOW()) as days_remaining
FROM personal_access_tokens
WHERE expires_at > NOW();
```

---

## Logs de Debugging

### Ver logs de Laravel

```bash
# Seguir logs en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log

# Filtrar logs de autenticación
docker exec -it docucenter-app-1 grep "Login exitoso\|Token renovado\|Error al verificar" storage/logs/laravel.log
```

### Buscar errores específicos

```bash
# Errores de licencia
docker exec -it docucenter-app-1 grep "LICENSE_" storage/logs/laravel.log

# Errores de token
docker exec -it docucenter-app-1 grep "TOKEN_" storage/logs/laravel.log

# Todas las operaciones de login
docker exec -it docucenter-app-1 grep "\"login\"\|\"refreshToken\"" storage/logs/laravel.log | tail -20
```

---

## Checklist de Testing Completo

Antes de marcar como completo, verificar:

- [ ] Login exitoso con licencia vigente (HTTP 200)
- [ ] Login con credenciales incorrectas (HTTP 401)
- [ ] Login con licencia expirada (HTTP 403)
- [ ] Login con organización sin acceso (HTTP 403)
- [ ] Renovación de token vigente (HTTP 200)
- [ ] Renovación con token expirado (HTTP 401, requiere login)
- [ ] Renovación con licencia expirada (HTTP 403)
- [ ] Advertencia cuando licencia vence en ≤7 días
- [ ] API de licencia no disponible (HTTP 503)
- [ ] Token anterior se revoca después de renovación
- [ ] Nuevo token tiene misma fecha de expiración que licencia
- [ ] Logs se generan correctamente
- [ ] Script interactivo funciona en todos los modos
- [ ] Documentación API completa y precisa

---

## Troubleshooting

### Script no encuentra jq

```bash
# Instalar jq
sudo apt-get update
sudo apt-get install -y jq
```

### Servidor no accesible

```bash
# Verificar que Docker está corriendo
docker ps

# Si no hay contenedores, iniciar
cd /home/weirdolabs/code/docucenter
docker-compose up -d

# Verificar logs del contenedor
docker logs docucenter-app-1
```

### Token guardado no funciona

```bash
# Limpiar token guardado
rm /tmp/docucenter_test_token.txt

# Hacer login nuevamente
./scripts/test-auth-token.sh login
```

### Error "LICENSE_VERIFICATION_ERROR"

```bash
# Verificar configuración de API remota
docker exec -it docucenter-app-1 php artisan tinker

# En tinker:
config('weirdo_panel.custom_remote_license_verification')

# Verificar variables de entorno
docker exec -it docucenter-app-1 env | grep APP_REMOTE_LICENSE
```

---

## Integración con CI/CD

### GitHub Actions Example

```yaml
name: Test Auth API

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Start Docker
        run: docker-compose up -d
      
      - name: Wait for services
        run: sleep 30
      
      - name: Install dependencies
        run: |
          sudo apt-get update
          sudo apt-get install -y jq curl
      
      - name: Test Login
        run: |
          docker exec docucenter-app-1 ./scripts/test-auth-token.sh login
      
      - name: Test Complete Flow
        run: |
          docker exec docucenter-app-1 ./scripts/test-auth-token.sh complete
```

---

## Próximos Pasos

Después de validar la funcionalidad básica:

1. **Performance Testing**: Probar con múltiples requests simultáneos
2. **Load Testing**: Simular 100+ renovaciones concurrentes
3. **Security Testing**: Intentar exploits (token injection, replay attacks)
4. **Integration Testing**: Probar con todas las APIs (MEYPAR, QuickBooks, etc.)
5. **Documentation**: Actualizar Swagger/OpenAPI con nuevos endpoints

---

## Referencias

- Documentación API: `docs/api/auth-token-refresh-api.md`
- Código fuente AuthController: `app/Http/Controllers/V1/AuthController.php`
- Servicio de verificación: `app/Services/LicenseVerificationService.php`
- Rutas API: `routes/api.php`
