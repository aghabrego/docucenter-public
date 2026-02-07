# Troubleshooting: Error 401 en Digifact PAC

## Problema

Error 401 (no autorizado) al intentar certificar documentos con Digifact:

```
El servidor no devolvió contenido. Status: 401
```

## Causas Comunes

### 1. Token Expirado o Inválido
- El token JWT de Digifact tiene vigencia de ~27-30 días
- Si el token expiró, debe renovarse automáticamente
- Verificar que la fecha de expiración es posterior a la fecha actual

### 2. Token No Persistido Correctamente
- El token debe guardarse en la base de datos después de autenticar
- Verificar que no hay errores al ejecutar `save()` en el modelo Pacconnection
- Confirmar que `refresh()` recupera el token correctamente

### 3. Formato Incorrecto del Header Authorization
- Digifact NO usa el prefijo "Bearer"
- El token se envía directamente: `Authorization: {token}`
- Verificar que no se agregó "Bearer " por error

### 4. Credenciales Incorrectas
- Username debe estar en formato: `PA.{RUC}.{USERNAME}`
- RUC debe incluir el dígito verificador: `{numero}-{dv}`
- Password debe ser la contraseña exacta proporcionada por Digifact

## Diagnóstico

### Paso 1: Verificar Token Actual

```bash
docker exec -it docucenter-app-1 php artisan digifact:test-connection {organization_id}
```

Este comando mostrará:
- Estado actual del token (vacío, presente, longitud)
- Fecha de expiración
- Validez del token
- Información de diagnóstico completa

### Paso 2: Revisar Logs

Buscar en los logs de Laravel:

```bash
# Ver logs recientes
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep Digifact

# Buscar errores específicos de autenticación
docker exec -it docucenter-app-1 grep "Digifact.*autenticación" storage/logs/laravel.log

# Ver información de tokens
docker exec -it docucenter-app-1 grep "Token Digifact" storage/logs/laravel.log
```

Buscar específicamente:
- `Token vacío en respuesta de Digifact`
- `Error al guardar token en base de datos`
- `Token vacío después de guardar y refrescar`
- `No se pudo renovar el token`

### Paso 3: Verificar Base de Datos

Conectarse a la base de datos de la organización:

```sql
-- Verificar conexión PAC
SELECT id, organization_id, pac_type, endpoint, username, 
       LENGTH(token) as token_length, 
       expiration, 
       created_at, updated_at
FROM pacconnections
WHERE organization_id = 122 AND pac_type = 'digifact';

-- Verificar si el token está vacío o NULL
SELECT id, 
       CASE 
           WHEN token IS NULL THEN 'NULL'
           WHEN token = '' THEN 'VACÍO'
           ELSE CONCAT('Presente (', LENGTH(token), ' chars)')
       END as token_status,
       expiration
FROM pacconnections
WHERE organization_id = 122 AND pac_type = 'digifact';
```

### Paso 4: Validar Conexión Manualmente

```bash
# Forzar nueva autenticación
docker exec -it docucenter-app-1 php artisan tinker

# Dentro de tinker:
$org = \App\Models\Organization::find(122);
\DB::connection()->useDatabase($org->database);
$conn = \App\Models\Pacconnection::where('organization_id', 122)->where('pac_type', 'digifact')->first();
$service = new \App\Services\DigifactService($conn, $org);

# Ver diagnóstico
$service->diagnosticInfo();

# Validar conexión
$result = $service->validateConnection();
dd($result);

# Verificar token después de validar
$conn->refresh();
echo "Token length: " . strlen($conn->token ?? '') . "\n";
echo "Expiration: " . $conn->expiration . "\n";
```

## Soluciones Implementadas

### 1. Mejora en Persistencia del Token (DigifactService.php)

- Validación de que `save()` retornó true
- `refresh()` inmediato después de guardar
- Verificación de que el token no está vacío después del refresh
- Error explícito si el token no se persistió correctamente

```php
$saved = $this->connection->save();
if (!$saved) {
    return ['success' => false, 'error' => 'No se pudo persistir el token'];
}

$this->connection->refresh();

if (strlen($this->connection->token ?? '') === 0) {
    return ['success' => false, 'error' => 'Token no se persistió correctamente'];
}
```

### 2. Mejora en getValidToken()

- Logging detallado del estado del token antes de usarlo
- Refresh adicional después de autenticar
- Validación de que el token no está vacío antes de retornar
- Exception si el token está vacío

```php
$token = $this->connection->token;

if (empty($token)) {
    throw new \Exception('Token vacío después de validación');
}
```

### 3. Logging Mejorado en certifyDocument()

- Incluye primeros y últimos 10 caracteres del token (para debug)
- Muestra longitud exacta del token
- Incluye fecha de expiración
- Facilita identificar si el token es diferente entre llamadas

### 4. Comando de Diagnóstico

- `php artisan digifact:test-connection {org_id}`
- Muestra información completa del estado de la conexión
- Permite probar autenticación de forma aislada
- Verifica persistencia del token post-autenticación

## Checklist de Verificación

Para resolver un error 401, verificar en orden:

- [ ] **Token existe en base de datos**: `SELECT LENGTH(token) FROM pacconnections WHERE ...`
- [ ] **Token no está expirado**: Fecha de expiración > fecha actual
- [ ] **Credenciales correctas**: Username incluye formato PA.RUC.USER
- [ ] **RUC correcto**: Incluye dígito verificador
- [ ] **Endpoint correcto**: Test vs Producción
- [ ] **Token se persiste**: Ejecutar authenticate() y verificar en BD
- [ ] **No hay errores de save()**: Revisar logs de "Error al guardar token"
- [ ] **Refresh funciona**: Token se recupera después de refresh()

## Acciones Correctivas

### Si el token está vacío en BD:

1. Revisar permisos de escritura en la tabla `pacconnections`
2. Verificar que no hay triggers o eventos que limpien el token
3. Ejecutar validación manual con tinker para ver el error exacto

### Si el token existe pero da 401:

1. Verificar que el token es el mismo que se envía (logging mejorado)
2. Confirmar que no se agregó "Bearer " al header
3. Revisar que el RUC y username en la URL son correctos
4. Forzar nueva autenticación

### Si authenticate() falla:

1. Verificar conectividad con el endpoint de Digifact
2. Confirmar credenciales (username, password)
3. Revisar formato del username: `PA.{RUC-DV}.{USERNAME}`
4. Verificar que la organización tiene RUC y DV configurados

## Testing

Después de aplicar correcciones:

1. Ejecutar comando de prueba:
   ```bash
   docker exec -it docucenter-app-1 php artisan digifact:test-connection 122
   ```

2. Verificar que el token se guarda correctamente

3. Intentar certificar un documento de prueba

4. Monitorear logs para confirmar que no hay errores 401

## Prevención

- Implementar monitoreo de expiración de tokens
- Alertas cuando quedan menos de 3 días de vigencia
- Logging automático de renovaciones de token
- Dashboard de estado de conexiones PAC

## Referencias

- [DigifactService.php](../app/Services/DigifactService.php)
- [TestConnectionCommand.php](../app/Console/Commands/Digifact/TestConnectionCommand.php)
- Documentación Digifact API (si está disponible)
