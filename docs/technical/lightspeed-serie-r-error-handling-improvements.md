# Mejoras en Manejo de Errores - Lightspeed Serie R

## **Implementación de Error Handling Detallado**

### **Mejoras Implementadas**

#### **1. Error Handling en `refreshAccessToken()`**

**Antes:**
```php
throw new Exception('Error al refrescar el token: ' . $e->getMessage(), $statusCode);
```

**Después:**
```php
// Decodificación de respuesta JSON para obtener detalles específicos
$errorDetails = [];
if (!empty($responseBody)) {
    $decodedResponse = json_decode($responseBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
        $errorDetails = $decodedResponse;
    }
}

// Mensaje específico según el tipo de error
switch ($errorDetails['error'] ?? '') {
    case 'invalid_grant':
        if (str_contains($errorDetails['error_description'] ?? '', 'refresh token is invalid')) {
            $errorMessage = "Token de refresco de Lightspeed Serie R ha sido revocado o expirado. Se requiere re-autorización manual.";
        }
        break;
    case 'invalid_client':
        $errorMessage = "Client ID o Client Secret de Lightspeed Serie R inválidos";
        break;
    // ... más casos específicos
}

throw new \Exception($errorMessage . " | Detalles: " . json_encode($detailedError), $statusCode);
```

#### **2. Error Handling en `executeWithRateLimit()`**

**Características Agregadas:**
- **Decodificación JSON** de respuestas de error
- **Logging detallado** con contexto de conexión y organización
- **Manejo específico** de errores de refresh token
- **Información completa** en excepciones

#### **3. Error Handling en `executeWithRateLimitAdvanced()`**

**Características Mejoradas:**
- **Headers HTTP** incluidos en logs
- **Retry-After** header respetado
- **Contexto completo** de debugging
- **Mensajes de error** más descriptivos

### **Tipos de Error Manejados**

#### **Token Errors (OAuth)**
```json
{
  "error": "invalid_grant",
  "error_description": "The refresh token is invalid.",
  "hint": "Token has been revoked"
}
```

**Respuesta Mejorada:**
- **Mensaje claro**: "Token de refresco ha sido revocado - se requiere re-autorización manual"
- **Código HTTP**: Preservado (400, 401, etc.)
- **Contexto**: Connection ID, Organization ID incluidos

#### **Rate Limit Errors (429)**
```json
{
  "error": "rate_limit_exceeded",
  "error_description": "Too many requests",
  "retry_after": 60
}
```

**Respuesta Mejorada:**
- **Retry-After header**: Respetado automáticamente
- **Backoff exponencial**: Implementado
- **Logging detallado**: Con attempt number y wait time

#### **API Errors (4xx, 5xx)**
```json
{
  "error": "invalid_request",
  "error_description": "Missing required parameter",
  "hint": "Check your request parameters"
}
```

**Respuesta Mejorada:**
- **Error type**: Identificado y categorizado
- **Description**: Error específico de la API
- **Hint**: Sugerencias cuando están disponibles
- **Raw response**: Incluido para debugging

### **Información de Debugging Incluida**

#### **En Logs:**
```json
{
  "http_status": 400,
  "method": "refreshAccessToken",
  "error_type": "invalid_grant",
  "error_description": "The refresh token is invalid.",
  "hint": "Token has been revoked",
  "raw_response": "{\"error\":\"invalid_grant\"...}",
  "connection_id": 123,
  "organization_id": 16,
  "timestamp": "2025-10-01T06:48:32.000Z"
}
```

#### **En Excepciones:**
```php
$detailedError = [
    'http_status' => $statusCode,
    'error_type' => $errorDetails['error'] ?? 'unknown_error',
    'error_description' => $errorDetails['error_description'] ?? $e->getMessage(),
    'hint' => $errorDetails['hint'] ?? null,
    'raw_response' => $responseBody,
    'connection_id' => $this->connection ? $this->connection->id : null,
    'organization_id' => $this->organization ? $this->organization->id : null,
    'timestamp' => now()->toISOString()
];

throw new \Exception($errorMessage . " | Detalles: " . json_encode($detailedError), $statusCode);
```

### **Beneficios de las Mejoras**

#### **1. Debugging Mejorado**
- **Información completa** sobre el error
- **Contexto de organización** y conexión
- **Raw response** para análisis detallado
- **Timestamp** para correlación temporal

#### **2. Manejo Específico de Errores**
- **Token revocado**: Mensaje claro sobre re-autorización
- **Credenciales inválidas**: Identificación específica
- **Rate limits**: Manejo automático con tiempos de espera
- **API errors**: Descripción detallada del problema

#### **3. Logging Estructurado**
- **JSON format**: Fácil parsing y análisis
- **Context fields**: Connection ID, Organization ID
- **Error categorization**: Por tipo de error
- **Attempt tracking**: Para reintentos y debugging

#### **4. Experiencia de Usuario**
- **Mensajes claros**: En español, descriptivos
- **Acciones sugeridas**: Qué hacer en cada caso
- **Información técnica**: Para soporte técnico
- **Preservación de códigos**: HTTP status codes

### **Casos de Uso Mejorados**

#### **Caso 1: Token Revocado**
**Antes:**
```
Error al refrescar el token: Client error: POST https://cloud.lightspeedapp.com/auth/oauth/token resulted in a 400 Bad Request
```

**Después:**
```
Token de refresco de Lightspeed Serie R ha sido revocado o expirado. Se requiere re-autorización manual. | Detalles: {"http_status":400,"error_type":"invalid_grant","error_description":"The refresh token is invalid.","hint":"Token has been revoked","connection_id":123,"organization_id":16}
```

#### **Caso 2: Rate Limit**
**Antes:**
```
Error al obtener las ventas: Too Many Requests
```

**Después:**
```
Log: Rate limit en LightspeedSerieRService - method: getTodaySales, wait_time: 60, retry_after_header: 60
Excepción: Manejo automático con reintento después de 60 segundos
```

#### **Caso 3: API Error**
**Antes:**
```
Error al obtener el cliente: Bad Request
```

**Después:**
```
Error al obtener información de la tienda: Invalid shop ID provided | Status: 400 | Type: invalid_request | Detalles: {"error":"invalid_request","error_description":"Shop ID does not exist","hint":"Check your shop ID parameter"}
```

### **Configuración Recomendada**

#### **Log Level Configuration**
```php
// En .env para diferentes ambientes
LOG_LEVEL=debug  // Desarrollo - información completa
LOG_LEVEL=info   // Staging - información importante
LOG_LEVEL=error  // Producción - solo errores críticos
```

#### **Monitoring Alerts**
```php
// Configurar alertas para errores específicos
- invalid_grant errors -> Notificar para re-autorización
- rate_limit_exceeded -> Monitorear frecuencia de requests
- api_errors -> Investigar problemas de integración
```

Las mejoras implementadas proporcionan **diagnóstico completo** de errores de Lightspeed Serie R, facilitando el debugging y mejorando la experiencia de resolución de problemas.
