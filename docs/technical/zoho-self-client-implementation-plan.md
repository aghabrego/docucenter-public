# Plan de Implementación - Zoho Self Client Integration

## Resumen Ejecutivo

Implementar integración completa con Zoho usando patrón Self Client OAuth 2.0, integrándose al sistema de conexiones existente de DocuCenter.

## Arquitectura del Sistema

### Componentes Nuevos a Crear

```
app/Services/ZohoSelfClientService.php     // Servicio principal
app/Http/Controllers/ZohoAuthController.php //  Manejo OAuth
config/zoho.php                            // Configuración
resources/views/livewire/admin/connection/zoho-setup.blade.php // UI
```

### Modificaciones a Archivos Existentes

```
app/Http/Livewire/Admin/Connection/Create.php //  Agregar zoho-self-client
app/Models/Connection.php                      //  Métodos helper para Zoho
routes/web.php                                //  Rutas OAuth callback
```

## Flujo de Implementación

### 1. Configuración Base

**Archivo:** `config/zoho.php`
```php
return [
    'domains' => [
        'com' => 'https://accounts.zoho.com',
        'eu' => 'https://accounts.zoho.eu', 
        'in' => 'https://accounts.zoho.in',
    ],
    'api_base' => [
        'com' => 'https://books.zoho.com/api/v3',
        'eu' => 'https://books.zoho.eu/api/v3',
        'in' => 'https://books.zoho.in/api/v3',
    ],
    'oauth' => [
        'authorize_url' => '/oauth/v2/auth',
        'token_url' => '/oauth/v2/token',
        'revoke_url' => '/oauth/v2/token/revoke',
    ],
    'scopes' => [
        'books_full' => 'ZohoBooks.fullaccess.all',
        'books_read' => 'ZohoBooks.fullaccess.READ',
    ]
];
```

### 2. Servicio Principal

**Archivo:** `app/Services/ZohoSelfClientService.php`

**Funcionalidades principales:**
- Generar URL de autorización OAuth
- Intercambiar código por tokens
- Refresh automático de tokens
- Realizar llamadas API autenticadas
- Manejo de errores y rate limiting
- Integración con sistema de conexiones

### 3. Controlador OAuth

**Archivo:** `app/Http/Controllers/ZohoAuthController.php`

**Endpoints:**
- `GET /zoho/auth/{connection_id}` - Redirigir a Zoho OAuth
- `GET /zoho/callback` - Manejar callback de Zoho
- `POST /zoho/revoke/{connection_id}` - Revocar tokens

### 4. Extensión del Sistema de Conexiones

**Modificaciones en:** `app/Http/Livewire/Admin/Connection/Create.php`

```php
// Agregar a la validación
'application' => '...zoho-self-client',

// Nuevas reglas
'settings.client_id' => 'required_if:application,zoho-self-client',
'settings.client_secret' => 'required_if:application,zoho-self-client',
'settings.redirect_uri' => 'required_if:application,zoho-self-client',
'settings.domain' => 'required_if:application,zoho-self-client|in:com,eu,in',
'settings.scope' => 'required_if:application,zoho-self-client',
```

## Flujo de Autenticación OAuth 2.0

### Paso 1: Configuración Inicial
```
Usuario → Crear Conexión Zoho → Ingresar credenciales Self Client
```

### Paso 2: Autorización
```
Sistema → Generar URL OAuth → Redirigir a Zoho → Usuario autoriza
```

### Paso 3: Callback
```
Zoho → Callback con código → Sistema intercambia por tokens → Guarda en Connection
```

### Paso 4: Uso
```
API calls → Auto-refresh si expira → Llamadas autenticadas a Zoho
```

## Estructura de Base de Datos

### Tabla `connections` (existente)
```sql
-- Campo settings (serializado) contendrá:
{
  "client_id": "xxx",
  "client_secret": "xxx", 
  "redirect_uri": "xxx",
  "domain": "com",
  "scope": "ZohoBooks.fullaccess.all",
  "access_token": "xxx",
  "refresh_token": "xxx", 
  "expires_at": "2024-12-31 23:59:59",
  "organization_id": "zoho_org_id"
}
```

## APIs Zoho a Integrar

### Endpoints Principales
```
GET /organizations - Listar organizaciones
GET /salesorders - Listar sales orders  
POST /salesorders - Crear sales order
GET /items - Listar productos
POST /items - Crear productos
GET /contacts - Listar contactos
POST /contacts - Crear contactos
```

### Mapeo con Modelo DocuCenter
```php
// Zoho Sales Order → SalesOrderHeaderImp
// Zoho Line Items → SalesOrderDetailImp
// Zoho Contacts → CustomersImp/CustomersExp
// Zoho Items → InventoryMasterListImp
```

## Ventajas del Enfoque Self Client

### Beneficios
1. **Control Total**: Sin dependencias de servicios intermedios
2. **Seguridad**: Tokens gestionados directamente
3. **Flexibilidad**: Acceso completo a API de Zoho
4. **Escalabilidad**: Sin límites de terceros
5. **Integración Nativa**: Aprovecha sistema de conexiones existente

### Consideraciones
1. **Configuración OAuth**: Requiere setup en Zoho Developer Console
2. **Manejo de Tokens**: Refresh automático necesario
3. **Rate Limiting**: Respetar límites de API de Zoho
4. **Multi-Organización**: Soporte para múltiples orgs Zoho

## Cronograma de Desarrollo

### Fase 1: Base (1-2 días)
- Configuración base y servicio principal
- Controlador OAuth y rutas
- Extensión sistema de conexiones

### Fase 2: Autenticación (1 día) 
- Flujo OAuth completo
- Manejo de tokens y refresh
- UI para configuración

### Fase 3: APIs Core (2-3 días)
- Integración Sales Orders
- Sincronización de productos y contactos
- Mapeo completo de datos

### Fase 4: Testing y Optimización (1-2 días)
- Testing integral
- Manejo de errores
- Documentación

## Testing Strategy

### Testing Manual
```bash
# 1. Crear conexión Zoho Self Client
# 2. Completar flujo OAuth
# 3. Verificar tokens guardados
# 4. Probar APIs (GET/POST)
# 5. Verificar refresh automático
```

### Testing Automatizado
```php
// Tests a crear:
ZohoSelfClientServiceTest.php
ZohoAuthControllerTest.php  
ZohoConnectionIntegrationTest.php
```

## Seguridad y Mejores Prácticas

###  Seguridad
- Client Secret encriptado en DB
- Tokens con expiración controlada
- Validación de redirect_uri
- Rate limiting en requests

### Mejores Prácticas
- Logging comprehensivo
- Manejo de errores robusto
- Fallbacks para API failures
- Configuración por ambiente

## Recursos Necesarios

### Configuración Zoho
1. **Crear Self Client** en Zoho Developer Console
2. **Configurar redirect URIs** para cada ambiente
3. **Obtener credenciales** (Client ID/Secret)

### Configuración DocuCenter
1. **Variables de entorno** para credenciales
2. **Rutas de callback** configuradas
3. **Permisos de conexión** por organización

---

**Estado**: Plan Listo para Implementación  
**Complejidad**: Intermedia  
**Tiempo Estimado**: 5-8 días de desarrollo  
**Dependencias**: Configuración Zoho Developer Console
