# Resumen de Correcciones - Error 401 Digifact

## Problema Identificado

Error 401 durante certificación de documentos con Digifact PAC para la organización 122.

**Log de error:**
```
El servidor no devolvió contenido. Status: 401
```

## Causa Raíz

Problema en la persistencia y recuperación del token JWT después de la autenticación:

1. El token se obtenía correctamente de Digifact
2. Se intentaba guardar en base de datos
3. **PERO**: No se validaba que el `save()` fuera exitoso
4. **ADEMÁS**: No se refrescaba el modelo después de guardar
5. **RESULTADO**: Token vacío o inválido al momento de certificar → Error 401

## Correcciones Aplicadas

### 1. DigifactService::authenticate() - Líneas ~175-220

**Antes:**
```php
try {
    $saved = $this->connection->save();
    Log::info('Token guardado');
} catch (\Exception $saveException) {
    Log::error('Error al guardar');
    // Continuar de todas formas ❌
}
```

**Ahora:**
```php
try {
    $saved = $this->connection->save();
    
    if (!$saved) {
        Log::error('save() retornó false');
        return ['success' => false, 'error' => '...'];
    }
    
    // ✅ Refrescar para sincronizar
    $this->connection->refresh();
    
    // ✅ Validar que el token está presente
    if (strlen($this->connection->token ?? '') === 0) {
        Log::error('Token vacío después de guardar');
        return ['success' => false, 'error' => '...'];
    }
    
    Log::info('Token guardado y validado');
} catch (\Exception $saveException) {
    // ✅ Ahora retorna error en lugar de continuar
    return ['success' => false, 'error' => $saveException->getMessage()];
}
```

### 2. DigifactService::getValidToken() - Líneas ~278-320

**Antes:**
```php
protected function getValidToken(): string
{
    $this->connection->refresh();
    
    if (!$this->isTokenValid()) {
        $this->authenticate();
    }
    
    return $this->connection->token; // ❌ Puede estar vacío
}
```

**Ahora:**
```php
protected function getValidToken(): string
{
    $this->connection->refresh();
    
    Log::debug('Verificando validez del token', [
        'token_exists' => !empty($this->connection->token),
        'is_valid' => $this->isTokenValid(),
    ]);
    
    if (!$this->isTokenValid()) {
        $result = $this->authenticate();
        
        if (!$result['success']) {
            throw new \Exception('No se pudo renovar el token');
        }
        
        // ✅ Refresh adicional después de autenticar
        $this->connection->refresh();
    }
    
    $token = $this->connection->token;
    
    // ✅ Validación explícita
    if (empty($token)) {
        throw new \Exception('Token vacío después de validación');
    }
    
    return $token;
}
```

### 3. DigifactService::certifyDocument() - Líneas ~550-570

**Ahora incluye logging detallado del token:**

```php
Log::info('Certificando documento', [
    'token_present' => !empty($token),
    'token_length' => strlen($token),
    'token_first_10_chars' => substr($token, 0, 10) . '...',  // ✅ Para debug
    'token_last_10_chars' => '...' . substr($token, -10),     // ✅ Para debug
    'connection_expiration' => $this->connection->expiration,
]);
```

### 4. DigifactService::validateConnection() - Mejorado

**Ahora valida persistencia del token:**

```php
public function validateConnection(): array
{
    $result = $this->authenticate();
    
    if (!$result['success']) {
        return ['success' => false, 'message' => $result['error']];
    }
    
    // ✅ Verificar que el token se guardó
    $this->connection->refresh();
    
    if (empty($this->connection->token)) {
        return [
            'success' => false,
            'message' => 'Token no se persistió en base de datos',
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Conexión validada. Token válido hasta: ' . 
            $this->connection->expiration->format('Y-m-d H:i:s'),
    ];
}
```

### 5. Nuevo Método de Diagnóstico

```php
public function diagnosticInfo(): array
{
    return [
        'organization_id' => $this->organization->id,
        'token_exists' => !empty($this->connection->token),
        'token_length' => strlen($this->connection->token ?? ''),
        'is_token_valid' => $this->isTokenValid(),
        'expires_in_days' => ...,
        // ... más información
    ];
}
```

## Herramientas de Testing

### Comando Artisan Nuevo

```bash
docker exec -it docucenter-app-1 php artisan digifact:test-connection 122
```

**Funcionalidad:**
- Muestra estado actual de la conexión
- Información de diagnóstico completa
- Permite autenticar de forma aislada
- Verifica persistencia del token post-autenticación
- Logging detallado de cada paso

## Cómo Probar las Correcciones

### 1. Prueba de Conexión

```bash
docker exec -it docucenter-app-1 php artisan digifact:test-connection 122
```

Verificar que:
- [ ] Token se obtiene exitosamente
- [ ] Token se guarda en base de datos (length > 0)
- [ ] Token se puede recuperar después de refresh()
- [ ] Fecha de expiración es correcta (~27-30 días)

### 2. Verificación en Base de Datos

```sql
-- Antes de certificar
SELECT id, organization_id, 
       LENGTH(token) as token_length,
       expiration,
       updated_at
FROM pacconnections
WHERE organization_id = 122 AND pac_type = 'digifact';
```

Debe mostrar:
- `token_length` > 0 (típicamente ~500-1000 chars)
- `expiration` en el futuro
- `updated_at` reciente

### 3. Prueba de Certificación Real

1. Ir a la interfaz de facturación
2. Intentar emitir un documento
3. Monitorear logs:

```bash
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "Digifact"
```

Buscar:
- ✅ "Verificando validez del token"
- ✅ "Token válido obtenido"
- ✅ "Certificando documento con Digifact"
- ✅ "Documento certificado exitosamente"

### 4. Verificación de Logs

Debe aparecer:
```
[INFO] Verificando validez del token {"token_exists":true,"is_valid":true}
[DEBUG] Token válido obtenido {"token_length":789}
[INFO] Certificando documento {"token_length":789,"token_first_10_chars":"eyJhbGciOi..."}
[INFO] Documento certificado exitosamente {"cufe":"..."}
```

NO debe aparecer:
```
❌ Token vacío después de guardar
❌ Token vacío después de validación
❌ No se pudo renovar el token
❌ Status: 401
```

## Checklist de Validación

Después de aplicar las correcciones:

- [ ] Comando de prueba ejecuta sin errores
- [ ] Token se guarda en BD (verificado con SQL)
- [ ] Token tiene longitud > 0 después de authenticate()
- [ ] Token se recupera correctamente con refresh()
- [ ] Fecha de expiración es futura
- [ ] No hay errores 401 en logs
- [ ] Certificación de documento funciona
- [ ] PDF y XML se obtienen correctamente

## Archivos Modificados

1. `/app/Services/DigifactService.php`
   - authenticate() - Validación de persistencia
   - getValidToken() - Logging y validación
   - certifyDocument() - Logging mejorado
   - validateConnection() - Verificación de persistencia
   - diagnosticInfo() - Nuevo método

2. `/app/Console/Commands/Digifact/TestConnectionCommand.php` (NUEVO)
   - Comando para testing aislado
   - Diagnóstico completo
   - Verificación post-autenticación

3. `/docs/troubleshooting/digifact-401-error.md` (NUEVO)
   - Guía de troubleshooting
   - Checklist de verificación
   - Soluciones y prevención

## Próximos Pasos

1. **Ejecutar comando de prueba** en producción para org 122
2. **Revisar logs** para confirmar que el token se guarda
3. **Intentar certificar** un documento real
4. **Monitorear** por 24-48 horas para confirmar estabilidad
5. **Implementar alertas** de expiración de token (3 días antes)

## Notas Importantes

- ✅ Todas las correcciones son retrocompatibles
- ✅ No se modificó la lógica de negocio, solo validaciones
- ✅ Logging mejorado facilita debugging futuro
- ✅ El token ahora se valida en cada paso crítico
- ⚠️ Si persiste el error 401, verificar credenciales en Digifact
- ⚠️ Confirmar que el endpoint es correcto (test vs producción)
