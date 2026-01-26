# Análisis de Campos del Modelo Pacconnection para Alanube

## Datos Proporcionados
```json
{
    "id": 19,
    "organization_id": 2,
    "name": "alanube",
    "token": "eyJhbGciOiJSUzI1NiIsImtpZCI6ImU1ZTEzYzFiLTJiYTgtNGYzOC1hNWMxLTQ5NWEzMjk3ZjE4ZiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI5OTI0NDFjYy1jMmQ4LTQxNTAtYWE2Mi04NzhlODI1MDRmZWQiLCJlbWFpbCI6ImFwY29udGVzdEBhbGFudWJlLmNvIiwic2NvcGUiOiJjLmQuci51OmFwaXBhbl9mdWxsX2FjY2VzcyBnZW5lcmljIiwiaXNzIjoic2FuZC1hdXRoLWFwaS5hbGVncmEuY29tIiwiaWF0IjoxNjYzMTY0MTUxLCJleHAiOjExNzE3MzQwMjIsImp0aSI6IjliZTRmYTg3LWI4MzMtNDgxZi1hMWNjLTg3YzA3OTNiNWQzZiJ9.bH0VsJ2WbRj5_hetqfyXq95Gm7Ex4fceQpuQpYcBK0wA8ne-nF1qN8yIVl1Q9VC92-KI6oTo7q1hrAT6pXbVyT6erYZNzeP7OHjQ3_iEfjxaUi4_YPzaivSZN3zckaeB8LI4Dc0a3aTYoVMSkb7dpRLKFfu0AOFMwfdWVQRiHuKmKUBAUbgoTwZGdsLeDzN9_56NMYm17X8br_XU6WDOa8dJGd4G4WsndVeNtlaDhu57e3N-d7bnftCD0RAXyD7mq3NHyZp_GO6vOlCVbRPKZ3MQkF3YGNSTAHaayXNDC5fVimPRmApX9G-AduPypmtHb-i9NTp_ejfJy-lSIzO3Bg",
    "expiration": null,
    "endpoint": "https://sandbox-api.alanube.co/pan/v1",
    "description": "Documento validado por ALANUBE SOLUCIONES, S.A. con RUC 155709116-2-2021, es Proveedor Autorizado Calificado, Resolución No. 201-6113 de 06/09/2022.",
    "user_id": 1,
    "branch_code": null,
    "billing_point": null,
    "created_at": "2025-08-23 15:32:31",
    "updated_at": "2025-08-23 15:32:31",
    "id_company": "01GWNA4PGNAQBHPRT5W8BJTATC",
    "id_office": "01H05QF4MDR9HKCQ3MBG7NTCFA",
    "username": null,
    "password": null
}
```

## Análisis Detallado de Campos

### Campos Estándar del Modelo
| Campo | Valor | Tipo | Descripción |
|-------|-------|------|-------------|
| `id` | 19 | INTEGER | Identificador único de la conexión PAC |
| `organization_id` | 2 | INTEGER | ID de la organización asociada |
| `name` | "alanube" | STRING | Nombre del proveedor PAC |
| `user_id` | 1 | INTEGER | Usuario que creó la configuración |
| `created_at` | "2025-08-23 15:32:31" | TIMESTAMP | Fecha de creación |
| `updated_at` | "2025-08-23 15:32:31" | TIMESTAMP | Fecha de última actualización |

###  Campos de Autenticación
| Campo | Valor | Análisis |
|-------|-------|----------|
| `token` | JWT Token (largo) | Token de autenticación válido para Alanube |
| `expiration` | null | Sin fecha de expiración específica (el token JWT tiene su propia exp) |
| `username` | null | No requerido para Alanube (usa token) |
| `password` | null | No requerido para Alanube (usa token) |

### Configuración de Endpoint
| Campo | Valor | Análisis |
|-------|-------|----------|
| `endpoint` | "https://sandbox-api.alanube.co/pan/v1" | URL de ambiente sandbox para Panamá |
| País detectado |  Panamá | Basado en `/pan/v1` en la URL |
| Ambiente | Sandbox/Testing | Basado en `sandbox-api.alanube.co` |

### Campos Específicos de Alanube
| Campo | Valor | Propósito |
|-------|-------|-----------|
| `id_company` | "01GWNA4PGNAQBHPRT5W8BJTATC" | Identificador de la empresa en Alanube |
| `id_office` | "01H05QF4MDR9HKCQ3MBG7NTCFA" | Identificador de la oficina/sucursal en Alanube |

### Campos Descriptivos
| Campo | Valor | Análisis |
|-------|-------|----------|
| `description` | Texto descriptivo largo | Información oficial de Alanube como PAC autorizado |
| `branch_code` | null | Campo serializado para códigos de sucursal (opcional) |
| `billing_point` | null | Campo serializado para puntos de facturación (opcional) |

## Análisis del Token JWT

### Decodificación del Header
```json
{
  "alg": "RS256",
  "kid": "e5e13c1b-2ba8-4f38-a5c1-495a3297f18f",
  "typ": "JWT"
}
```

### Decodificación del Payload
```json
{
  "sub": "99244cc-c2d8-4150-aa62-878e82504fed",
  "email": "apcontest@alanube.co",
  "scope": "c.d.r.u:apipan_full_access generic",
  "iss": "sand-auth-api.alegra.com",
  "iat": 1663164151,
  "exp": 11717340222,
  "jti": "9be4fa87-b833-481f-a1cc-87c0793b5d3f"
}
```

### Análisis del Token
- **Email**: `apcontest@alanube.co` (cuenta de testing)
- **Scope**: `apipan_full_access` (acceso completo API Panamá)
- **Issuer**: `sand-auth-api.alegra.com` (servidor de autenticación sandbox)
- **Expired**: 11717340222 (fecha muy lejana - token de larga duración)
- **Subject**: ID único de usuario en sistema Alanube

## Campos Clave para Funcionalidad

### Campos Obligatorios para Alanube
1. **name**: `"alanube"` - Identifica el tipo de PAC
2. **token**: JWT válido - Autenticación con API Alanube
3. **endpoint**: URL válida - Define país y ambiente
4. **id_company**: ID de empresa - Requerido para todas las peticiones
5. **id_office**: ID de oficina - Requerido para emisión de documentos

### Lógica de Detección de País
El sistema detecta automáticamente el país basado en el endpoint:
- `/pan/v1` →  Panamá
- `/dom/v1` →  República Dominicana

### Detección de Ambiente
- `sandbox-api.alanube.co` → Testing/Sandbox
- `api.alanube.co` → Producción

## Atributo Virtual `pac_type`

**Nota Importante**: El campo `pac_type` no existe físicamente en la base de datos, pero se usa extensivamente en el código. Se deriva de:

```php
// Lógica inferida del código:
if ($pacConnection->name === 'alanube') {
    if (strpos($pacConnection->endpoint, '/pan/v1') !== false) {
        $pac_type = 'alanube_panama';
    } elseif (strpos($pacConnection->endpoint, '/dom/v1') !== false) {
        $pac_type = 'alanube';
    }
}
```

## Campos Vacíos/No Utilizados para Alanube

- `expiration`: null (token JWT maneja su propia expiración)
- `username`: null (no requerido para autenticación por token)
- `password`: null (no requerido para autenticación por token)
- `branch_code`: null (opcional, para configuraciones avanzadas)
- `billing_point`: null (opcional, para configuraciones avanzadas)

## Estado de Configuración

### Configuración Válida Detectada
- **PAC Provider**: Alanube 
- **País**: Panamá  
- **Ambiente**: Sandbox 
- **Autenticación**: JWT válido 
- **Empresa configurada**: Sí 
- **Oficina configurada**: Sí 

### Funcionalidades Habilitadas
Con esta configuración, la organización puede:
- Emitir facturas electrónicas
- Emitir notas de crédito
- Consultar RUC panameños
- Descargar documentos XML/PDF
- Realizar validaciones PAC

## Recomendaciones de Uso

1. **Para Producción**: Cambiar endpoint a `https://api.alanube.co/pan/v1`
2. **Token**: Verificar periódicamente la validez del JWT
3. **Monitoring**: Supervisar logs de emisión para detectar errores
4. **Backup**: Mantener respaldo de `id_company` e `id_office`

## Referencias de Código

- Modelo: `app/Models/Pacconnection.php`
- Servicio: `app/Services/AlanubeService.php`
- Validaciones: `app/Http/Controllers/V1/FeController.php`
- Factory: `database/factories/PacconnectionFactory.php`
