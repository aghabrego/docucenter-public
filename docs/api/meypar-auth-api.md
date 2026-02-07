# API de Autenticación Meypar

## Descripción
API de autenticación específica para integración con Meypar Colombia. Usa nombres de parámetros personalizados pero mantiene la misma funcionalidad que la API estándar de DocuCenter.

## Endpoint

```
POST /api/v1/auth/meypar-login
```

## Headers

```
Content-Type: application/json
Accept: application/json
```

## Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Validación |
|-----------|------|-----------|-------------|------------|
| `userName` | string | Sí | Email del usuario | Formato email válido, máx 255 caracteres |
| `userPW` | string | Sí | Contraseña del usuario | Mínimo 6 caracteres |
| `parkingId` | integer | Sí | ID de la organización/parking | Debe existir en la base de datos |
| `ambiente` | integer | Sí | Punto de venta/ambiente | Entre 0 y 999 |
| `idFacturador` | string | No | Identificador del facturador | Máx 255 caracteres. Si no se envía, se genera automáticamente |

## Mapeo Interno

Los parámetros de Meypar se mapean internamente a:

| Meypar | DocuCenter Interno |
|--------|-------------------|
| `userName` | `email` |
| `userPW` | `password` |
| `parkingId` | `organization_id` |
| `ambiente` | `point_sale` |
| `idFacturador` | `token_name` |

## Ejemplo de Request

```bash
curl -X POST https://docucenter.com/api/v1/auth/meypar-login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "userName": "usuario@meypar.com",
    "userPW": "password123",
    "parkingId": 42,
    "ambiente": 1,
    "idFacturador": "FACTURADOR-001"
  }'
```

## Respuestas

### Éxito (200 OK)

```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "token": "1|abc123def456ghi789...",
  "token_info": {
    "name": "FACTURADOR-001",
    "expires_at": "2025-12-31T23:59:59.000000Z",
    "organization_id": 42,
    "point_sale": 1,
    "days_until_expiration": 365
  },
  "user": {
    "id": 123,
    "name": "Juan Pérez",
    "email": "usuario@meypar.com"
  }
}
```

### Éxito con Advertencia (200 OK)

Si la licencia está próxima a vencer (menos de 30 días):

```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "token": "1|abc123def456ghi789...",
  "token_info": {
    "name": "FACTURADOR-001",
    "expires_at": "2025-01-15T23:59:59.000000Z",
    "organization_id": 42,
    "point_sale": 1,
    "days_until_expiration": 15
  },
  "user": {
    "id": 123,
    "name": "Juan Pérez",
    "email": "usuario@meypar.com"
  },
  "warning": "La licencia vence en 15 días",
  "warning_code": "LICENSE_EXPIRING_SOON"
}
```

### Error: Credenciales Inválidas (401 Unauthorized)

```json
{
  "success": false,
  "message": "Credenciales inválidas",
  "code": "INVALID_CREDENTIALS"
}
```

### Error: Acceso Denegado (403 Forbidden)

```json
{
  "success": false,
  "message": "Usuario no tiene acceso a esta organización",
  "code": "ORGANIZATION_ACCESS_DENIED"
}
```

### Error: Licencia Expirada (403 Forbidden)

```json
{
  "success": false,
  "message": "La licencia ha expirado",
  "code": "LICENSE_EXPIRED",
  "license_info": {
    "expired_at": "2024-12-31T23:59:59.000000Z",
    "expired_days_ago": 30
  }
}
```

### Error: Validación (422 Unprocessable Entity)

```json
{
  "message": "Los datos proporcionados no son válidos",
  "errors": {
    "userName": [
      "El campo userName es requerido"
    ],
    "userPW": [
      "El campo userPW debe tener al menos 6 caracteres"
    ],
    "parkingId": [
      "El parkingId especificado no existe"
    ],
    "ambiente": [
      "El campo ambiente debe estar entre 0 y 999"
    ]
  }
}
```

### Error: Servicio No Disponible (503 Service Unavailable)

```json
{
  "success": false,
  "message": "Error al verificar licencia: Servicio de licencias no disponible",
  "code": "LICENSE_VERIFICATION_ERROR"
}
```

### Error: Interno (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Error interno en autenticación",
  "code": "INTERNAL_ERROR"
}
```

## Uso del Token

Una vez obtenido el token, debe incluirse en todas las solicitudes subsiguientes:

```bash
curl -X POST https://docucenter.com/api/v1/fe/create_sale_meypar \
  -H "Authorization: Bearer 1|abc123def456ghi789..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{ ... }'
```

## Notas Importantes

1. **Seguridad**: El token debe almacenarse de forma segura y nunca exponerse en código del lado del cliente
2. **Expiración**: El token expira junto con la licencia de la organización
3. **Renovación**: Cuando el token esté próximo a vencer, use el endpoint `/api/v1/auth/refresh_token`
4. **Logs**: Todos los intentos de login se registran con el identificador `client: 'meypar'`
5. **Validación**: Los parámetros se validan según las reglas de DocuCenter pero con nombres de Meypar

## Diferencias con API Estándar

| Característica | API Estándar (`/login`) | API Meypar (`/meypar-login`) |
|----------------|-------------------------|------------------------------|
| Parámetros | email, password, organization_id, point_sale | userName, userPW, parkingId, ambiente |
| Funcionalidad | Idéntica | Idéntica |
| Validaciones | Idénticas | Idénticas |
| Respuestas | Idénticas | Idénticas |
| Token | Mismo formato | Mismo formato |
| Cliente Log | 'standard' | 'meypar' |

## Ejemplo de Integración (PHP)

```php
<?php

function meyparLogin($userName, $userPW, $parkingId, $ambiente, $idFacturador = null) {
    $url = 'https://docucenter.com/api/v1/auth/meypar-login';
    
    $data = [
        'userName' => $userName,
        'userPW' => $userPW,
        'parkingId' => $parkingId,
        'ambiente' => $ambiente,
    ];
    
    if ($idFacturador) {
        $data['idFacturador'] = $idFacturador;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && $result['success']) {
        return [
            'success' => true,
            'token' => $result['token'],
            'expires_at' => $result['token_info']['expires_at']
        ];
    }
    
    return [
        'success' => false,
        'error' => $result['message'] ?? 'Error desconocido',
        'code' => $result['code'] ?? 'UNKNOWN'
    ];
}

// Uso
$auth = meyparLogin(
    'usuario@meypar.com',
    'password123',
    42,
    1,
    'FACTURADOR-001'
);

if ($auth['success']) {
    echo "Token: " . $auth['token'];
} else {
    echo "Error: " . $auth['error'];
}
```

## Testing

### Caso 1: Login Exitoso

```bash
curl -X POST http://localhost/api/v1/auth/meypar-login \
  -H "Content-Type: application/json" \
  -d '{
    "userName": "test@example.com",
    "userPW": "password",
    "parkingId": 1,
    "ambiente": 1,
    "idFacturador": "TEST-001"
  }'
```

### Caso 2: Credenciales Inválidas

```bash
curl -X POST http://localhost/api/v1/auth/meypar-login \
  -H "Content-Type: application/json" \
  -d '{
    "userName": "test@example.com",
    "userPW": "wrongpassword",
    "parkingId": 1,
    "ambiente": 1
  }'
```

### Caso 3: Validación de Parámetros

```bash
curl -X POST http://localhost/api/v1/auth/meypar-login \
  -H "Content-Type: application/json" \
  -d '{
    "userName": "invalid-email",
    "userPW": "123",
    "parkingId": 999999,
    "ambiente": 1000
  }'
```

---

**Última actualización**: 1 de diciembre de 2025  
**Versión**: 1.0  
**Cliente**: Meypar Colombia
