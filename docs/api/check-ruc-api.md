# API Consulta de RUC - DocuCenter

## Descripción
API para consultar el dígito verificador de RUC utilizando el PAC Alanube configurado en la organización. Esta funcionalidad permite validar y obtener información de RUCs panameños mediante el servicio oficial de Alanube.

## Endpoint
```
GET /api/v1/fe/check_ruc/{ruc}
```

## Autenticación
- **Requerida**: Sí (Bearer Token)
- **Middleware**: `auth:sanctum`, `check.activate.organization`

## Parámetros

### Parámetro de Ruta
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `ruc` | string | Sí | RUC a consultar (formatos: 8-123-456, PE-8-123456, 155750453-2-2024) |

### Validaciones de RUC
- **Mínimo**: 3 caracteres
- **Máximo**: 50 caracteres  
- **Formato**: Solo números, guiones y letras P, E
- **Expresión Regular**: `/^[0-9\-PE]+$/`
- **Tipos soportados**:
  - **Persona Natural**: `8-123-456` (cédula)
  - **Persona Extranjera**: `PE-8-123456`
  - **Empresa**: `155750453-2-2024` (RUC empresarial con año)

## Configuración Requerida

### Organización
- Debe tener una organización activa configurada
- La organización debe tener conexión PAC configurada

### PAC Connection
- **Tipo**: Debe ser de Alanube (`alanube_panama` o variantes de `alanube`)
- **País**: Solo disponible para Panamá (PA)
- **Token**: Debe estar configurado y válido
- **Endpoint**: Debe apuntar a servicios de Alanube Panamá

### Ejemplos de Endpoints Válidos
```
Testing:    https://sandbox-api.alanube.co/pan/v1
Production: https://api.alanube.co/pan/v1
```

## Respuestas

### Respuesta Exitosa (200)
```json
{
  "success": true,
  "message": "Consulta de RUC exitosa",
  "data": {
    "ruc": "8-123-456",
    "digit": "78",
    "valid": true,
    "company_name": "Empresa de Ejemplo S.A.",
    "status": "active"
  },
  "ruc_consulted": "8-123-456",
  "country": "PA",
  "pac_type": "alanube_panama"
}
```

### Error - Organización no encontrada (400)
```json
{
  "success": false,
  "message": "No hay organización activa configurada",
  "error": "ORGANIZATION_NOT_FOUND"
}
```

### Error - PAC no configurado (400)
```json
{
  "success": false,
  "message": "No hay configuración PAC disponible para la organización",
  "error": "PAC_NOT_CONFIGURED"
}
```

### Error - PAC no soportado (400)
```json
{
  "success": false,
  "message": "La consulta de RUC solo está disponible para conexiones PAC de Alanube",
  "error": "PAC_NOT_SUPPORTED",
  "pac_type": "thefactoryhka"
}
```

### Error - País no soportado (400)
```json
{
  "success": false,
  "message": "La consulta de dígito verificador solo está disponible para Panamá",
  "error": "COUNTRY_NOT_SUPPORTED",
  "country": "DO"
}
```

### Error - API PAC (4xx/5xx)
```json
{
  "success": false,
  "message": "Error en la consulta de RUC con el PAC",
  "error": "PAC_API_ERROR",
  "status_code": 404,
  "pac_error": {
    "code": "RUC_NOT_FOUND",
    "message": "RUC no encontrado en la base de datos"
  },
  "ruc_consulted": "8-123-456"
}
```

### Error - Validación (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "ruc": [
      "El RUC solo puede contener números, guiones y las letras P y E (formatos: 8-123-456, PE-8-123456, 155750453-2-2024)"
    ]
  }
}
```

### Error - Interno (500)
```json
{
  "success": false,
  "message": "Error interno al consultar RUC",
  "error": "INTERNAL_ERROR",
  "details": "Detalles específicos del error"
}
```

## Ejemplos de Uso

### cURL - RUC Válido
```bash
curl -X GET "https://tu-dominio.com/api/v1/fe/check_ruc/8-123-456" \
  -H "Authorization: Bearer tu-token-aqui" \
  -H "Accept: application/json"
```

### cURL - RUC con Formato PE
```bash
curl -X GET "https://tu-dominio.com/api/v1/fe/check_ruc/PE-8-123456" \
  -H "Authorization: Bearer tu-token-aqui" \
  -H "Accept: application/json"
```

### cURL - RUC Empresarial con Año
```bash
curl -X GET "https://tu-dominio.com/api/v1/fe/check_ruc/155750453-2-2024" \
  -H "Authorization: Bearer tu-token-aqui" \
  -H "Accept: application/json"
```

### JavaScript/Fetch
```javascript
const response = await fetch('/api/v1/fe/check_ruc/8-123-456', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
});

const result = await response.json();
console.log(result);
```

### PHP/Laravel HTTP Client
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->get('/api/v1/fe/check_ruc/8-123-456');

$result = $response->json();
```

## Códigos de Error

| Código | Error | Descripción |
|--------|-------|-------------|
| `ORGANIZATION_NOT_FOUND` | Organización no configurada |
| `PAC_NOT_CONFIGURED` | Sin configuración PAC |
| `PAC_NOT_SUPPORTED` | PAC no es Alanube |
| `COUNTRY_NOT_SUPPORTED` | País no es Panamá |
| `PAC_API_ERROR` | Error en API de Alanube |
| `INTERNAL_ERROR` | Error interno del sistema |
| `INVALID_RUC_FORMAT` | Formato de RUC inválido |

## Logging
El sistema registra automáticamente:
- Consultas exitosas con detalles de RUC y respuesta
- Errores de PAC con códigos de estado
- Excepciones internas con stack trace
- Información de debugging para troubleshooting

## Casos de Uso

1. **Validación de Clientes**: Verificar RUCs de clientes antes de crear facturas
2. **Autocompletado**: Obtener datos de empresa desde RUC
3. **Integración de Formularios**: Validar RUCs en tiempo real
4. **Auditoría**: Verificar existencia de RUCs para reportes
5. **Compliance**: Validar contribuyentes según DGI Panamá

## Consideraciones Técnicas

- **Timeout**: 30 segundos para consultas PAC
- **Rate Limiting**: Sujeto a límites de la API de Alanube
- **Cache**: No se implementa cache - consultas en tiempo real
- **Concurrent**: Soporte para múltiples consultas simultáneas
- **Retry**: No implementado - fallas se reportan inmediatamente

## Dependencias

- `App\Services\AlanubeService`: Servicio principal
- `App\Http\Requests\CheckRucRequest`: Validación de entrada
- `App\Facades\OrganizationFacade`: Gestión de organización
- Configuración PAC válida en base de datos
- Token de autenticación Alanube válido

## Notas de Implementación

- Solo funciona con conexiones PAC de Alanube Panamá
- Requiere token válido configurado en `pacconnection.token`
- El endpoint se construye automáticamente según configuración PAC
- Respeta configuración sandbox vs producción según endpoint PAC
- Logging detallado para troubleshooting y auditoría
