# Conexiones Persistentes de Base de Datos (PDO::ATTR_PERSISTENT)

## Descripción

Las conexiones persistentes PDO permiten reutilizar conexiones de base de datos existentes en lugar de crear nuevas en cada petición HTTP, lo que puede mejorar significativamente el rendimiento en aplicaciones de alto tráfico.

## Configuración

### Variable de Entorno

```bash
# En .env
DB_PERSISTENT=false  # Default: deshabilitado
DB_PERSISTENT=true   # Habilitar conexiones persistentes
```

### Archivo de Configuración

La configuración se encuentra en [config/database.php](../../config/database.php):

```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),  // ← Nueva opción
]) : [],
```

## Ventajas

✅ **Mejor Rendimiento**: Reduce overhead de crear/destruir conexiones
✅ **Menor Latencia**: Conexiones ya establecidas = respuestas más rápidas
✅ **Reducción de Carga**: Menos trabajo para el servidor de BD
✅ **Ideal para Alto Tráfico**: Aplicaciones con muchas peticiones concurrentes

## Desventajas y Consideraciones

⚠️ **Transacciones No Cerradas**: Pueden persistir entre peticiones
⚠️ **Locks de BD**: Locks no liberados pueden causar problemas
⚠️ **Cambios de Estado**: Variables de sesión de MySQL persisten
⚠️ **Consumo de Memoria**: Conexiones abiertas consumen recursos
⚠️ **Pool Limitado**: El servidor tiene un límite de conexiones

## Consideraciones para DocuCenter Multi-Tenant

### ⚠️ IMPORTANTE: Multi-Tenant con Cambio Dinámico de BD

DocuCenter usa un patrón multi-tenant donde **cambia frecuentemente de base de datos** mediante:

```php
DB::connection()->useDatabase($organization->database);
```

**Riesgos con Conexiones Persistentes:**

1. **Estado de BD Compartido**: Si una petición cambia a `db_org_1` y la conexión persiste, la siguiente petición podría empezar en `db_org_1` en lugar de la BD principal

2. **Contaminación de Datos**: Riesgo de mezclar datos entre organizaciones si no se maneja correctamente el cambio de BD

3. **Transacciones Abiertas**: Una transacción iniciada en una BD podría persistir cuando se cambia a otra

### Recomendaciones para DocuCenter

#### ❌ NO Recomendado Para:
- **Entornos de producción multi-tenant** (riesgo de contaminación de datos)
- **Aplicaciones que cambian frecuentemente de BD** (como DocuCenter)
- **Sistemas con muchas organizaciones activas simultáneamente**

#### ✅ Recomendado Para:
- **Ambientes de desarrollo/testing** con una sola organización
- **Jobs de consola** que procesan una sola BD (ej: `company:sync` con `--organization`)
- **APIs dedicadas** que no cambian de BD

#### 🟡 Usar con Precaución:
- **Production**: Solo si se implementan controles estrictos
- **Monitoreo constante** de conexiones y estado de BD
- **Testing exhaustivo** de cambios de BD entre organizaciones

## Implementación Segura en Multi-Tenant

Si decides habilitar conexiones persistentes en DocuCenter, implementa estas medidas:

### 1. Reset Explícito de Conexión

```php
// En cada cambio de organización
protected function setDatabaseConnection(\App\Models\Organization $orgActive = null)
{
    $orgActive = $orgActive ?? $this->organization;
    if (empty($orgActive)) return;
    
    // Cerrar cualquier transacción abierta
    DB::rollBack();
    
    // Cambiar de BD
    DB::connection()->useDatabase($orgActive->database);
    
    // Verificar cambio
    $currentDb = DB::connection()->getDatabaseName();
    if ($currentDb !== $orgActive->database) {
        throw new \Exception("Error al cambiar de BD");
    }
}
```

### 2. Middleware de Verificación

```php
// Middleware para verificar BD correcta
public function handle($request, Closure $next)
{
    $expectedDb = env('DB_DATABASE');
    $currentDb = DB::connection()->getDatabaseName();
    
    if ($currentDb !== $expectedDb) {
        // Reset a BD principal
        DB::connection()->useDatabase($expectedDb);
    }
    
    return $next($request);
}
```

### 3. Monitoring de Estado

```php
// Logging de cambios de BD
Log::info('DB Switch', [
    'from' => DB::connection()->getDatabaseName(),
    'to' => $organization->database,
    'user' => auth()->id(),
]);
```

## Testing

### Verificar Estado de Conexiones

```bash
# En MySQL/MariaDB
SHOW PROCESSLIST;

# Conexiones persistentes activas
SELECT * FROM information_schema.PROCESSLIST 
WHERE COMMAND != 'Sleep';

# Total de conexiones
SHOW STATUS WHERE Variable_name = 'Threads_connected';
```

### Script de Prueba

```php
// Test de conexiones persistentes
php artisan tinker

>>> DB::connection()->useDatabase('docucenter');
>>> $db1 = DB::connection()->getDatabaseName();
>>> echo "DB1: $db1\n";

>>> DB::connection()->useDatabase('db_weirdolabs_14');
>>> $db2 = DB::connection()->getDatabaseName();
>>> echo "DB2: $db2\n";

>>> // Verificar si persiste entre cambios
>>> DB::connection()->useDatabase('docucenter');
>>> $db3 = DB::connection()->getDatabaseName();
>>> echo "DB3: $db3\n";
```

## Configuración de MariaDB/MySQL

Para soportar más conexiones persistentes, ajusta el servidor:

```ini
# my.cnf o my.ini
[mysqld]
max_connections = 300          # Aumentar límite de conexiones
wait_timeout = 28800          # Timeout de conexiones inactivas (8 horas)
interactive_timeout = 28800   # Timeout sesiones interactivas
```

## Monitoreo y Logs

### Laravel

```php
// Log de conexiones
DB::listen(function ($query) {
    Log::info('Query', [
        'database' => DB::connection()->getDatabaseName(),
        'time' => $query->time,
    ]);
});
```

### MariaDB

```sql
-- Habilitar query log
SET GLOBAL general_log = 'ON';
SET GLOBAL general_log_file = '/var/log/mysql/queries.log';

-- Ver conexiones activas
SELECT 
    ID,
    USER,
    HOST,
    DB,
    COMMAND,
    TIME
FROM information_schema.PROCESSLIST
WHERE DB IS NOT NULL
ORDER BY TIME DESC;
```

## Recomendación Final para DocuCenter

**Mantener `DB_PERSISTENT=false` (deshabilitado) por defecto** debido a:

1. ❌ Arquitectura multi-tenant con cambios frecuentes de BD
2. ❌ Riesgo de contaminación de datos entre organizaciones
3. ❌ Complejidad de mantener estado consistente
4. ❌ Jobs que procesan múltiples organizaciones (`company:sync --all`)

**Considerar habilitar solo en casos específicos:**
- ✅ Entornos de desarrollo con una sola organización
- ✅ Servidores dedicados a procesamiento batch de una sola BD
- ✅ APIs aisladas sin cambio de BD

## Referencias

- [PDO Persistent Connections](https://www.php.net/manual/en/pdo.connections.php)
- [Laravel Database Connections](https://laravel.com/docs/9.x/database#configuration)
- [MySQL Connection Pooling](https://dev.mysql.com/doc/refman/8.0/en/connection-management.html)
