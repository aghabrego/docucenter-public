# Análisis de Implementación: PlusMóvil con AWS Cognito

**Fecha:** 9 de noviembre de 2025  
**Objetivo:** Actualizar la integración de PlusMóvil para usar autenticación AWS Cognito

---

## Estado Actual

### 1. **PlusMovilInvoiceService** 
**Ubicación:** `app/Services/PlusMovilInvoiceService.php`

**Estado actual:**
- Tiene método `setAccessToken()` para recibir el token
- Usa Bearer token en headers
- **NO tiene implementación de Cognito**
- Solo valida que exista token, no lo genera
- Consulta endpoint `/com-invoices` correctamente

**Métodos disponibles:**
```php
- setOrganization(Organization $organization)
- setAccessToken(string $accessToken)
- getProformas(array $params = [])
- validateParams(array $params)
- processProformasResponse(array $response)
```

**Problema identificado:**
- El servicio espera recibir el token externamente
- No hay lógica para autenticarse con Cognito
- No hay método para obtener items individuales (`/com-invoices/{id}`)

---

## Modelo Connection

### Estructura Actual
**Tabla:** `connections`

**Columnas:**
```sql
- id (bigint)
- organization_id (bigint)
- name (varchar)
- application (varchar) - Índice
- settings (text) - Serializado
- created_at (timestamp)
- updated_at (timestamp)
```

**Aplicaciones soportadas actualmente:**
- `ezee`
- `lightspeed`
- `acicloud`
- `booqable`
- `lightspeed-serie-r`
- `other-panama-forms`
- `zoho-self-client`

**Settings actuales por aplicación:**
- **Ezee**: `PrivateKey`, `HotelCode`, `Code`
- **Lightspeed**: `Token`, `DomainPrefix`
- **Booqable**: `Token`, `DomainPrefix`
- **ACICloud**: `Module`, `App`
- **Lightspeed-R**: `LightspeedClientId`, `LightspeedClientSecret`
- **Other Panama Forms**: `Token`, `Company`
- **Zoho Self-Client**: `client_id`, `client_secret`

---

## Requerimientos para PlusMóvil

### Settings necesarios (campo serializado):

```php
'settings' => [
    // Autenticación Cognito
    'client_id' => 'xxxx',           // Client ID de Cognito
    'username' => 'usuario@email',    // Usuario para autenticación
    'password' => 'encrypted_pass',   // Password (encriptado)
    'environment' => 'qa|prod',       // qa o prod
    
    // URLs base (calculadas según environment)
    // QA: https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa
    // Prod: https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod
]
```

### Client IDs por ambiente:
- **QA**: `7t3s7lb4tfg6ssal586l929ovl`
- **Prod**: `771vv2q1ararj5u1f084opsdg7`

---

## Cambios Necesarios

### 1. **Actualizar `Connection` Create Component**

**Archivo:** `app/Http/Livewire/Admin/Connection/Create.php`

**Cambios requeridos:**

#### A. Agregar 'plusmovil' a aplicaciones soportadas
```php
'application' => 'required|string|in:ezee,lightspeed,acicloud,booqable,lightspeed-serie-r,other-panama-forms,zoho-self-client,plusmovil',
```

#### B. Agregar reglas de validación para PlusMóvil
```php
'settings.environment' => 'required_if:application,plusmovil|in:qa,prod',
'settings.username' => 'required_if:application,plusmovil|email',
'settings.password' => 'required_if:application,plusmovil|string|min:6',
```

#### C. Agregar propiedad para environment
```php
/**
 * @var string
 */
public $plusmovil_environment = 'qa'; // qa o prod
```

#### D. Método para calcular client_id automáticamente
```php
protected function getPlusMovilClientId($environment)
{
    return $environment === 'prod' 
        ? '771vv2q1ararj5u1f084opsdg7'
        : '7t3s7lb4tfg6ssal586l929ovl';
}
```

#### E. Método para calcular base_url
```php
protected function getPlusMovilBaseUrl($environment)
{
    $suffix = $environment === 'prod' ? 'prod' : 'dev';
    return "https://28cwop8rj6.execute-api.us-east-1.amazonaws.com/{$suffix}";
}
```

---

### 2. **Actualizar `PlusMovilInvoiceService`**

**Cambios requeridos:**

#### A. Agregar dependencias
```php
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Support\Facades\Cache;
```

#### B. Agregar propiedades
```php
protected $clientId;
protected $username;
protected $password;
protected $cognitoRegion = 'us-east-1';
protected $tokenCacheKey;
```

#### C. Método para configurar credenciales desde Connection
```php
public function setCredentialsFromConnection(Connection $connection)
{
    $settings = $connection->settings;
    
    $this->clientId = $settings['client_id'] ?? null;
    $this->username = $settings['username'] ?? null;
    $this->password = decrypt($settings['password'] ?? '');
    $this->baseUrl = $settings['base_url'] ?? $this->baseUrl;
    $this->tokenCacheKey = "plusmovil_token_{$connection->organization_id}";
}
```

#### D. Método para autenticarse con Cognito
```php
public function authenticate()
{
    // Verificar si hay token en caché
    $cachedToken = Cache::get($this->tokenCacheKey);
    if ($cachedToken) {
        $this->accessToken = $cachedToken;
        return $cachedToken;
    }
    
    // Autenticar con Cognito
    $client = new CognitoIdentityProviderClient([
        'version' => 'latest',
        'region' => $this->cognitoRegion,
    ]);
    
    $result = $client->initiateAuth([
        'AuthFlow' => 'USER_PASSWORD_AUTH',
        'ClientId' => $this->clientId,
        'AuthParameters' => [
            'USERNAME' => $this->username,
            'PASSWORD' => $this->password,
        ],
    ]);
    
    $accessToken = $result['AuthenticationResult']['AccessToken'];
    $expiresIn = $result['AuthenticationResult']['ExpiresIn'];
    
    // Cachear token (menos 5 minutos para renovar antes)
    Cache::put($this->tokenCacheKey, $accessToken, $expiresIn - 300);
    
    $this->accessToken = $accessToken;
    
    return $accessToken;
}
```

#### E. Método para obtener factura individual con items
```php
public function getInvoiceWithItems($invoiceId)
{
    try {
        if (empty($this->accessToken)) {
            $this->authenticate();
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/com-invoices/{$invoiceId}");

        if ($response->failed()) {
            throw new Exception('Error al consultar factura: ' . $response->body());
        }

        $data = $response->json();

        return $data['data'] ?? [];

    } catch (Exception $e) {
        Log::error('Error en getInvoiceWithItems', [
            'message' => $e->getMessage(),
            'invoice_id' => $invoiceId,
        ]);

        throw $e;
    }
}
```

---

### 3. **Vista del Formulario de Conexión**

**Ubicación:** `resources/views/livewire/admin/connection/create.blade.php`

**Agregar sección para PlusMóvil:**

```blade
@if($application === 'plusmovil')
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="plusmovil_environment">{{ __('Ambiente') }}</label>
                <select wire:model="plusmovil_environment" class="form-control" id="plusmovil_environment">
                    <option value="qa">QA (Pruebas)</option>
                    <option value="prod">Producción</option>
                </select>
                @error('plusmovil_environment')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="settings.username">{{ __('Usuario (Email)') }}</label>
                <input type="email" wire:model="settings.username" class="form-control" id="settings.username">
                @error('settings.username')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="settings.password">{{ __('Contraseña') }}</label>
                <input type="password" wire:model="settings.password" class="form-control" id="settings.password">
                @error('settings.password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Información:</strong>
        <ul class="mb-0">
            <li><strong>QA:</strong> Ambiente de pruebas</li>
            <li><strong>Producción:</strong> Datos reales</li>
            <li>El Client ID se asigna automáticamente según el ambiente seleccionado</li>
            <li>La contraseña se encripta antes de guardarse</li>
        </ul>
    </div>
@endif
```

---

## Seguridad

### Encriptación de Password

**En el método save() del componente Create:**
```php
if ($this->application === 'plusmovil') {
    // Agregar client_id y base_url según environment
    $this->settings['client_id'] = $this->getPlusMovilClientId($this->plusmovil_environment);
    $this->settings['base_url'] = $this->getPlusMovilBaseUrl($this->plusmovil_environment);
    $this->settings['environment'] = $this->plusmovil_environment;
    
    // Encriptar password
    if (!empty($this->settings['password'])) {
        $this->settings['password'] = encrypt($this->settings['password']);
    }
}
```

---

## Ejemplo de Settings Guardados

```php
[
    'client_id' => '7t3s7lb4tfg6ssal586l929ovl',
    'username' => 'usuario@empresa.com',
    'password' => 'eyJpdiI6Ik...',  // Encriptado
    'environment' => 'qa',
    'base_url' => 'https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa'
]
```

---

## Checklist de Implementación

### Fase 1: Modelo y Base de Datos
- [ ] Verificar que tabla `connections` soporta campo `settings` como text
- [ ] No requiere migración adicional (usa settings serializado)

### Fase 2: Componente Livewire
- [ ] Agregar 'plusmovil' a aplicaciones soportadas
- [ ] Agregar reglas de validación para PlusMóvil
- [ ] Agregar propiedad `$plusmovil_environment`
- [ ] Implementar métodos `getPlusMovilClientId()` y `getPlusMovilBaseUrl()`
- [ ] Encriptar password antes de guardar
- [ ] Actualizar vista con formulario PlusMóvil

### Fase 3: Servicio PlusMovilInvoiceService
- [ ] Instalar AWS SDK: `composer require aws/aws-sdk-php`
- [ ] Agregar método `setCredentialsFromConnection()`
- [ ] Implementar método `authenticate()` con Cognito
- [ ] Agregar método `getInvoiceWithItems($invoiceId)`
- [ ] Implementar cache de tokens
- [ ] Manejar renovación automática de tokens

### Fase 4: Testing
- [ ] Probar creación de conexión QA
- [ ] Probar creación de conexión Producción
- [ ] Verificar que password se encripta
- [ ] Probar autenticación Cognito
- [ ] Verificar cache de tokens
- [ ] Probar consulta de facturas
- [ ] Probar consulta de items de factura

---

## Próximos Pasos

1. **Implementar cambios en componente Create**
2. **Actualizar PlusMovilInvoiceService con Cognito**
3. **Crear vista de formulario para PlusMóvil**
4. **Probar autenticación en QA**
5. **Implementar Job de importación de facturas**
6. **Documentar proceso completo**

---

## Referencias

- [plusmovil-cognito-upgrade-analysis.md](./plusmovil-cognito-upgrade-analysis.md)
- [plusmovil-invoice-items-SOLVED.md](./plusmovil-invoice-items-SOLVED.md)
- [get-plusmovil-token.sh](../testing/get-plusmovil-token.sh)
- [test-plusmovil-cognito-auth.php](../testing/test-plusmovil-cognito-auth.php)
