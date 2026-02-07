# Análisis: Actualización PlusMovil API con AWS Cognito

## NOTA IMPORTANTE: URL Correcta

**URL que funciona:**
- QA: `https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa` 
- Prod: `https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod` 

**URL que NO funciona** (DNS no resuelve):
- `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa`
- `https://xka96gucj8.execute-api.us-east-1.amazonaws.com/prod`

Verificado en pruebas del 31 de octubre de 2025. Ver `plusmovil-test-results.md`.

---

## Información de la Nueva API

### Endpoints
- **Base URL QA**: `https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa`
- **Base URL Prod**: `https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod`

### Autenticación

**Tipo**: AWS Cognito Bearer Token

**Client IDs**:
- QA: `7t3s7lb4tfg6ssal586l929ovl`
- Prod: `771vv2q1ararj5u1f084opsdg7`

**Proceso de autenticación**:
1. Usuario debe tener credenciales de acceso a la web
2. Autenticación con AWS Cognito usando `USER_PASSWORD_AUTH`
3. Cognito retorna Access Token para usar en las APIs
4. Todas las peticiones usan: `Authorization: Bearer {token}`

## Endpoint Analizado: GET /sys-logs

### Descripción
Retorna una lista de syslogs del sistema con soporte completo para filtros dinámicos vía querystring.

### Estructura de Respuesta

```json
[
  {
    "id": 0,
    "com_distributor_id": 0,
    "com_branch_id": 0,
    "com_seller_id": 0,
    "user_id": 0,
    "distributor_name": "string",
    "branch_name": "string",
    "seller_name": "string",
    "user_name": "string",
    "module_icon": "string",
    "module_type": "string",
    "module_name": "string",
    "module_id": 0,
    "action_type": "string",
    "description": "string",
    "details": "string",
    "created_at": "string"
  }
]
```

### Filtros Soportados

#### 1. Búsqueda Parcial (`_like`)
```
?name_like=phone
?module_name_like=invoice
?user_name_like=admin
```

#### 2. Múltiples Valores (`_in`)
```
?status_in=1,2,3
?action_type_in=create,update,delete
?module_type_in=sales,inventory
```

#### 3. Rangos Numéricos (`_gte`, `_lte`, `_gt`, `_lt`)
```
?price_gte=100&price_lte=500
?id_gt=1000
?user_id_lte=50
```

#### 4. Rangos de Fechas (`_between`)
```
?created_at_between=2024-01-01,2024-12-31
?updated_at_between=2024-06-01,2024-06-30
```

#### 5. Valores Distintos (`_ne`)
```
?status_ne=inactive
?module_type_ne=test
```

#### 6. Ordenamiento
```
?order_by=created_at&order_dir=desc
?order_by=created_at,name&order_dir=desc,asc
```

#### 7. Paginación
```
?limit=10&offset=20
?limit=50&offset=0
```

### Limitaciones
- Máximo 50 filtros por consulta
- Algunos campos sensibles no pueden ser filtrados
- Paginación opcional (sin límite si no se especifica)

### Ejemplos de Uso

```bash
# Consulta básica
GET /sys-logs

# Con filtros múltiples
GET /sys-logs?module_name_like=invoice&action_type_in=create,update&limit=10

# Con ordenamiento
GET /sys-logs?order_by=created_at&order_dir=desc&limit=20

# Con paginación
GET /sys-logs?limit=10&offset=40

# Búsqueda por rango de fechas
GET /sys-logs?created_at_between=2024-01-01,2024-12-31&order_by=created_at&order_dir=desc
```

## Cambios Requeridos en PlusMovilInvoiceService

### 1. Actualizar Configuración

**Archivo**: `config/services.php`

```php
'plusmovil' => [
    'base_url' => env('PLUSMOVIL_BASE_URL', 'https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa'),
    'client_id' => env('PLUSMOVIL_CLIENT_ID', '7t3s7lb4tfg6ssal586l929ovl'),
    'cognito' => [
        'region' => env('PLUSMOVIL_COGNITO_REGION', 'us-east-1'),
        'user_pool_id' => env('PLUSMOVIL_COGNITO_USER_POOL_ID'),
        'username' => env('PLUSMOVIL_USERNAME'),
        'password' => env('PLUSMOVIL_PASSWORD'),
    ],
],
```

**Archivo**: `.env`

```env
# PlusMovil API Configuration
PLUSMOVIL_BASE_URL=https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa
PLUSMOVIL_CLIENT_ID=7t3s7lb4tfg6ssal586l929ovl
PLUSMOVIL_COGNITO_REGION=us-east-1
PLUSMOVIL_COGNITO_USER_POOL_ID=<solicitar>
PLUSMOVIL_USERNAME=<usuario>
PLUSMOVIL_PASSWORD=<password>
```

### 2. Instalar AWS SDK

```bash
composer require aws/aws-sdk-php
```

### 3. Implementar Autenticación AWS Cognito

```php
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;

protected function authenticateWithCognito()
{
    try {
        $client = new CognitoIdentityProviderClient([
            'region' => config('services.plusmovil.cognito.region'),
            'version' => 'latest',
        ]);

        $result = $client->initiateAuth([
            'AuthFlow' => 'USER_PASSWORD_AUTH',
            'ClientId' => config('services.plusmovil.client_id'),
            'AuthParameters' => [
                'USERNAME' => config('services.plusmovil.cognito.username'),
                'PASSWORD' => config('services.plusmovil.cognito.password'),
            ],
        ]);

        $this->accessToken = $result['AuthenticationResult']['AccessToken'];
        $this->idToken = $result['AuthenticationResult']['IdToken'];
        $this->refreshToken = $result['AuthenticationResult']['RefreshToken'];
        
        // Cachear token (expira típicamente en 1 hora)
        cache()->put(
            'plusmovil_access_token_' . $this->organization->id,
            $this->accessToken,
            now()->addMinutes(55)
        );

        return true;

    } catch (AwsException $e) {
        Log::error('Error en autenticación AWS Cognito', [
            'message' => $e->getMessage(),
            'code' => $e->getAwsErrorCode()
        ]);
        throw new Exception('Error de autenticación: ' . $e->getMessage());
    }
}
```

### 4. Agregar Método getSysLogs()

```php
/**
 * Obtener sys-logs del sistema DMS
 *
 * @param array $filters Filtros para la consulta
 * @return array
 */
public function getSysLogs(array $filters = [])
{
    try {
        if (empty($this->accessToken)) {
            $this->authenticateWithCognito();
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/sys-logs", $filters);

        if ($response->status() === 401) {
            // Token expirado, re-autenticar
            $this->authenticateWithCognito();
            
            // Reintentar petición
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/sys-logs", $filters);
        }

        if ($response->failed()) {
            throw new Exception('Error al consultar sys-logs: ' . $response->body());
        }

        $data = $response->json();

        Log::info('Sys-logs obtenidos exitosamente', [
            'count' => count($data ?? []),
            'filters' => $filters
        ]);

        return $data;

    } catch (Exception $e) {
        Log::error('Error en getSysLogs', [
            'message' => $e->getMessage(),
            'filters' => $filters,
            'organization_id' => $this->organization->id ?? null
        ]);
        throw $e;
    }
}
```

### 5. Helper para Construir Filtros

```php
/**
 * Construir filtros para sys-logs
 *
 * @param array $params
 * @return array
 */
protected function buildSysLogsFilters(array $params)
{
    $filters = [];
    
    // Búsqueda parcial
    if (isset($params['module_name'])) {
        $filters['module_name_like'] = $params['module_name'];
    }
    
    if (isset($params['user_name'])) {
        $filters['user_name_like'] = $params['user_name'];
    }
    
    // Múltiples valores
    if (isset($params['action_types'])) {
        $filters['action_type_in'] = implode(',', $params['action_types']);
    }
    
    // Rango de fechas
    if (isset($params['date_from']) && isset($params['date_to'])) {
        $filters['created_at_between'] = "{$params['date_from']},{$params['date_to']}";
    }
    
    // Ordenamiento
    if (isset($params['order_by'])) {
        $filters['order_by'] = $params['order_by'];
        $filters['order_dir'] = $params['order_dir'] ?? 'desc';
    }
    
    // Paginación
    if (isset($params['page'])) {
        $perPage = $params['per_page'] ?? 50;
        $filters['limit'] = $perPage;
        $filters['offset'] = ($params['page'] - 1) * $perPage;
    }
    
    return $filters;
}
```

## Plan de Implementación

### Fase 1: Testing (Antes de modificar servicio)
- [ ] Ejecutar `docs/testing/test-plusmovil-api.sh`
- [ ] Obtener credenciales de usuario de PlusMovil
- [ ] Obtener User Pool ID de AWS Cognito
- [ ] Probar autenticación manualmente
- [ ] Verificar respuesta del endpoint /sys-logs
- [ ] Documentar estructura de datos real

### Fase 2: Configuración
- [ ] Instalar AWS SDK: `composer require aws/aws-sdk-php`
- [ ] Agregar configuración en `config/services.php`
- [ ] Agregar variables de entorno en `.env`
- [ ] Solicitar User Pool ID y región de Cognito

### Fase 3: Implementación
- [ ] Actualizar `PlusMovilInvoiceService`
- [ ] Implementar `authenticateWithCognito()`
- [ ] Implementar `getSysLogs()`
- [ ] Implementar `buildSysLogsFilters()`
- [ ] Agregar cache de tokens
- [ ] Manejo de re-autenticación automática

### Fase 4: Testing Integrado
- [ ] Crear tests unitarios
- [ ] Probar autenticación
- [ ] Probar consulta sin filtros
- [ ] Probar consulta con filtros
- [ ] Probar paginación
- [ ] Validar manejo de errores

### Fase 5: Documentación
- [ ] Actualizar documentación del servicio
- [ ] Agregar ejemplos de uso
- [ ] Documentar filtros disponibles
- [ ] Crear guía de troubleshooting

## Scripts de Prueba

### Script Bash (Recomendado para testing rápido)
**NO requiere Docker** - Se ejecuta directamente en tu sistema local:

```bash
chmod +x docs/testing/test-plusmovil-api.sh
./docs/testing/test-plusmovil-api.sh
```

**Requisitos**:
- `curl` (ya instalado en Linux/macOS)
- `jq` (opcional, para formatear JSON): `sudo apt install jq`

### Script PHP (Para testing más detallado)
**Requiere Docker** - Se ejecuta dentro del contenedor:

```bash
docker exec -it docucenter-app-1 php docs/testing/test-plusmovil-cognito-auth.php
```

**Requisitos**:
- Contenedor Docker corriendo
- Laravel cargado

## Información Pendiente

Para completar la implementación necesitamos:

1. **User Pool ID** de AWS Cognito
2. **Región** específica de Cognito (probablemente us-east-1)
3. **Credenciales de usuario** válidas para testing
4. **Confirmación** de que el endpoint `/com-invoices` también está en la nueva URL
5. **Documentación** de otros endpoints disponibles en la nueva API

## Notas Importantes

- Los tokens de AWS Cognito típicamente expiran en 1 hora
- Implementar cache de tokens para no re-autenticar en cada petición
- Manejar re-autenticación automática cuando token expire (HTTP 401)
- La API limita a 50 filtros por consulta
- Sin límite de registros si no se especifica paginación
- Algunos campos pueden ser sensibles y no filtrables

## Próximo Paso Recomendado

**Ejecutar el script de prueba** para validar el acceso a la API antes de modificar el servicio:

```bash
chmod +x docs/testing/test-plusmovil-api.sh
./docs/testing/test-plusmovil-api.sh
```

Esto permitirá:
- Confirmar que las credenciales funcionan
- Ver la estructura real de datos
- Validar que los filtros funcionen correctamente
- Identificar cualquier problema antes de la implementación
