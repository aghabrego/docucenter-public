# Análisis: Almacenamiento de Token AWS Cognito en Connection Model

**Fecha:** 9 de noviembre de 2025  
**Estrategia:** Almacenar token y expiración en `settings` del modelo Connection

---

## Ventajas de Almacenar Token en Connection

### Beneficios
1. **Persistencia**: Token sobrevive a reinicios del servidor
2. **Sin Cache Externa**: No dependemos de Redis/Cache
3. **Por Organización**: Cada organización tiene su token independiente
4. **Auditable**: Podemos ver cuándo se actualizó el token
5. **Más Simple**: Menos dependencias, todo en el modelo

### Sin Cache
- Token se pierde al reiniciar servidor
- Requiere Redis/Memcached
- Más complejo de gestionar

---

## Estructura de Settings Actualizada

### Settings para PlusMóvil con Token

```php
[
    // Credenciales base
    'client_id' => '7t3s7lb4tfg6ssal586l929ovl',
    'username' => 'usuario@empresa.com',
    'password' => 'eyJpdiI6Ik...',  // Encriptado
    'environment' => 'qa',
    'base_url' => 'https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa',
    
    // Token almacenado
    'access_token' => 'eyJraWQiOiJc...',  // Token de Cognito
    'token_expires_at' => '2025-11-09 15:30:00',  // Timestamp de expiración
    'refresh_token' => 'eyJjdHk6...',  // Token de refresh (opcional)
]
```

---

## Métodos Necesarios en Connection Model

### 1. Helper para verificar si token es válido

```php
/**
 * Verifica si el token de PlusMóvil es válido
 *
 * @return bool
 */
public function hasPlusMovilValidToken(): bool
{
    if ($this->application !== 'plusmovil') {
        return false;
    }
    
    $settings = $this->settings;
    
    // Verificar que existe el token
    if (empty($settings['access_token'])) {
        return false;
    }
    
    // Verificar que no ha expirado
    if (empty($settings['token_expires_at'])) {
        return false;
    }
    
    $expiresAt = \Carbon\Carbon::parse($settings['token_expires_at']);
    
    // Token válido si expira en más de 5 minutos
    return $expiresAt->isFuture() && $expiresAt->diffInMinutes(now()) > 5;
}
```

### 2. Helper para actualizar token

```php
/**
 * Actualiza el token de acceso de PlusMóvil
 *
 * @param string $accessToken
 * @param int $expiresIn Segundos hasta expiración
 * @param string|null $refreshToken
 * @return void
 */
public function updatePlusMovilToken(string $accessToken, int $expiresIn, ?string $refreshToken = null): void
{
    if ($this->application !== 'plusmovil') {
        return;
    }
    
    $settings = $this->settings;
    
    $settings['access_token'] = $accessToken;
    $settings['token_expires_at'] = now()->addSeconds($expiresIn)->toDateTimeString();
    
    if ($refreshToken) {
        $settings['refresh_token'] = $refreshToken;
    }
    
    $this->settings = $settings;
    $this->save();
    
    \Log::info('PlusMóvil token actualizado', [
        'connection_id' => $this->id,
        'organization_id' => $this->organization_id,
        'expires_at' => $settings['token_expires_at'],
    ]);
}
```

### 3. Helper para obtener token válido

```php
/**
 * Obtiene el token de acceso de PlusMóvil (lo genera si es necesario)
 *
 * @return string|null
 */
public function getPlusMovilToken(): ?string
{
    if ($this->application !== 'plusmovil') {
        return null;
    }
    
    // Si el token es válido, retornarlo
    if ($this->hasPlusMovilValidToken()) {
        return $this->settings['access_token'];
    }
    
    // Si no es válido, regenerarlo
    return $this->refreshPlusMovilToken();
}
```

### 4. Helper para refrescar token

```php
/**
 * Regenera el token de PlusMóvil usando Cognito
 *
 * @return string|null
 */
public function refreshPlusMovilToken(): ?string
{
    if ($this->application !== 'plusmovil') {
        return null;
    }
    
    try {
        $settings = $this->settings;
        
        $client = new \Aws\CognitoIdentityProvider\CognitoIdentityProviderClient([
            'version' => 'latest',
            'region' => 'us-east-1',
        ]);
        
        $result = $client->initiateAuth([
            'AuthFlow' => 'USER_PASSWORD_AUTH',
            'ClientId' => $settings['client_id'],
            'AuthParameters' => [
                'USERNAME' => $settings['username'],
                'PASSWORD' => decrypt($settings['password']),
            ],
        ]);
        
        $accessToken = $result['AuthenticationResult']['AccessToken'];
        $expiresIn = $result['AuthenticationResult']['ExpiresIn'];
        $refreshToken = $result['AuthenticationResult']['RefreshToken'] ?? null;
        
        $this->updatePlusMovilToken($accessToken, $expiresIn, $refreshToken);
        
        return $accessToken;
        
    } catch (\Exception $e) {
        \Log::error('Error al refrescar token PlusMóvil', [
            'connection_id' => $this->id,
            'error' => $e->getMessage(),
        ]);
        
        return null;
    }
}
```

---

## Flujo de Uso del Token

### En PlusMovilInvoiceService

```php
class PlusMovilInvoiceService
{
    protected $connection;
    
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        
        // Obtener token válido (lo genera automáticamente si es necesario)
        $this->accessToken = $connection->getPlusMovilToken();
        
        $settings = $connection->settings;
        $this->baseUrl = $settings['base_url'] ?? '';
    }
    
    public function getInvoices(array $params = [])
    {
        try {
            // El token ya está configurado y es válido
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/com-invoices", $params);
            
            // ... resto del código
            
        } catch (\Exception $e) {
            // Si hay error 401, refrescar token y reintentar
            if ($e->getCode() === 401) {
                $this->accessToken = $this->connection->refreshPlusMovilToken();
                return $this->getInvoices($params);
            }
            
            throw $e;
        }
    }
}
```

---

## Uso en Jobs

### Ejemplo: ImportPlusMovilInvoicesJob

```php
class ImportPlusMovilInvoicesJob implements ShouldQueue
{
    protected $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    public function handle()
    {
        // El servicio obtiene automáticamente el token válido
        $service = new PlusMovilInvoiceService();
        $service->setConnection($this->connection);
        
        // Si el token expiró, se regenera automáticamente
        $invoices = $service->getInvoices([
            'invoice_date_between' => '2025-01-01,2025-11-09'
        ]);
        
        // Procesar facturas...
    }
}
```

---

## Consideraciones de Seguridad

### 1. **Access Token en Settings**
- Se guarda en BD (ya es seguro)
- No se expone en respuestas API
- **Importante**: No incluir en logs completos

### 2. **Password Encriptado**
```php
// Al guardar
$settings['password'] = encrypt($password);

// Al usar
$password = decrypt($settings['password']);
```

### 3. **Logs Seguros**
```php
// MAL
Log::info('Settings', $connection->settings);

// BIEN
Log::info('Connection', [
    'id' => $connection->id,
    'application' => $connection->application,
    'has_token' => !empty($connection->settings['access_token']),
    'token_valid' => $connection->hasPlusMovilValidToken(),
]);
```

---

## Ventajas de Esta Estrategia

### 1. **Automático**
```php
// El desarrollador solo hace:
$service->setConnection($connection);
$invoices = $service->getInvoices();

// No necesita preocuparse por tokens
```

### 2. **Auto-renovación**
```php
// Si el token expiró, se renueva automáticamente
// Transparente para el usuario
```

### 3. **Persistente**
```php
// Token sobrevive a:
- Reinicios del servidor
- Despliegues
- Cambios de cola
```

### 4. **Por Organización**
```php
// Cada organización tiene su token independiente
// No hay conflictos entre organizaciones
```

### 5. **Auditable**
```php
// Podemos ver cuándo se actualizó
$connection->updated_at; // Última actualización de token
```

---

## Comparación: Cache vs Settings

| Aspecto | Cache | Settings (BD) |
|---------|-------|---------------|
| Persistencia | Se pierde al reiniciar | Permanente |
| Dependencias | Requiere Redis | Solo BD |
| Complejidad | Media | Simple |
| Por Organización | Sí | Sí |
| Auto-renovación | Manual | Automático |
| Auditoría | No | Sí (updated_at) |
| Performance | Más rápido | Query a BD |

**Conclusión**: Settings es mejor para tokens de larga duración con auto-renovación.

---

## Implementación

### Orden de Implementación

1. **Agregar métodos al modelo Connection**
   - `hasPlusMovilValidToken()`
   - `updatePlusMovilToken()`
   - `getPlusMovilToken()`
   - `refreshPlusMovilToken()`

2. **Actualizar PlusMovilInvoiceService**
   - Método `setConnection()`
   - Auto-renovación en errores 401

3. **Testing**
   - Crear conexión y verificar token
   - Esperar expiración y verificar auto-renovación
   - Verificar que token persiste después de reiniciar

---

## Ejemplo de Settings Final

```php
// Al crear la conexión
[
    'client_id' => '7t3s7lb4tfg6ssal586l929ovl',
    'username' => 'usuario@empresa.com',
    'password' => 'eyJpdiI6Ik...',  // Encriptado
    'environment' => 'qa',
    'base_url' => 'https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa',
]

// Después de primer uso (auto-generado)
[
    'client_id' => '7t3s7lb4tfg6ssal586l929ovl',
    'username' => 'usuario@empresa.com',
    'password' => 'eyJpdiI6Ik...',
    'environment' => 'qa',
    'base_url' => 'https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa',
    'access_token' => 'eyJraWQiOiJc...',           // Auto-generado
    'token_expires_at' => '2025-11-09 15:30:00',   // Auto-calculado
    'refresh_token' => 'eyJjdHk6...',              // Opcional
]
```

---

## Conclusión

**Esta estrategia es superior porque:**
- Más simple (sin cache externo)
- Más robusto (persiste reiniciados)
- Auto-renovación transparente
- Un solo lugar para gestionar credenciales
- Auditable vía `updated_at`

**Siguiente paso:** Implementar los métodos en el modelo Connection.
