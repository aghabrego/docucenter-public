# Corrección: pac_emission_errors en Base de Datos Principal

## Problema Detectado

La tabla `pac_emission_errors` se estaba intentando crear/consultar en la base de datos de cada organización, pero debe estar en la **base de datos principal**.

## Cambios Realizados

### 1. Modelo PacEmissionError

**Archivo**: `app/Models/PacEmissionError.php`

```php
class PacEmissionError extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     * IMPORTANTE: Forzar conexión mysql (BD principal)
     * @var string
     */
    protected $connection = 'mysql';
    
    // ... resto del modelo
}
```

**Efecto**: El modelo SIEMPRE usará la conexión `mysql` (base de datos principal), sin importar qué base de datos de organización esté activa.

### 2. Migración

**Archivo**: `database/migrations/2025_12_17_000001_create_pac_emission_errors_table.php`

```php
public function up()
{
    // IMPORTANTE: Esta tabla va en la base de datos PRINCIPAL (mysql)
    Schema::connection('mysql')->create('pac_emission_errors', function (Blueprint $table) {
        // ... definición de tabla
    });
}

public function down()
{
    Schema::connection('mysql')->dropIfExists('pac_emission_errors');
}
```

**Efecto**: La migración creará la tabla en la base de datos principal, no en las BD de organizaciones.

### 3. Dashboard PacErrorsDashboard

**Archivo**: `app/Http/Livewire/Admin/Reports/PacErrorsDashboard.php`

**Cambios**:
- ❌ Eliminado: `$this->setDatabaseConnection($this->organization)`
- ❌ Eliminados: métodos `setDatabaseConnection()` y `setDefaultConnection()`
- ✅ Agregado: Comentario explicativo

```php
public function mount()
{
    $this->organization = auth()->user()->organization;
    $this->organization_id = $this->organization->id;
    // NO cambiar conexión - pac_emission_errors está en la BD principal
    
    // ... resto del código
}
```

**Efecto**: El componente ya NO cambia la conexión de base de datos, porque el modelo maneja eso automáticamente.

## Por Qué Esta Solución

### Tabla en BD Principal

La tabla `pac_emission_errors` debe estar en la base de datos principal porque:

1. **Centralización**: Permite consultar errores de TODAS las organizaciones desde un solo lugar
2. **Análisis Global**: Facilita análisis y estadísticas generales del sistema
3. **Simplicidad**: No requiere sincronización entre múltiples bases de datos
4. **Escalabilidad**: Una sola tabla es más fácil de mantener y optimizar

### Filtrado por Organización

Aunque la tabla está centralizada, cada organización solo ve sus propios errores gracias a:

```php
$query = PacEmissionError::query()
    ->forOrganization($this->organization_id)  // Filtro automático
    ->with(['organization', 'invoice'])
    ->orderBy('occurred_at', 'desc');
```

El scope `forOrganization()` asegura que solo se consulten errores de la organización actual.

## Cómo Ejecutar la Migración

### Opción 1: Script Automático (Recomendado)

```bash
cd /home/weirdolabs/code/docucenter
./scripts/migrate-pac-errors.sh
```

Este script:
- Verifica que Docker esté corriendo
- Detecta automáticamente el contenedor
- Ejecuta la migración en la BD principal
- Verifica que la tabla se creó correctamente

### Opción 2: Manual

```bash
# Iniciar Docker si no está corriendo
docker-compose up -d

# Ejecutar migración
docker exec -it <nombre_contenedor> php artisan migrate --path=database/migrations/2025_12_17_000001_create_pac_emission_errors_table.php

# Limpiar cache
docker exec -it <nombre_contenedor> php artisan config:clear
docker exec -it <nombre_contenedor> php artisan route:clear
docker exec -it <nombre_contenedor> php artisan view:clear
```

## Verificación

Después de ejecutar la migración, verifica que funciona:

1. **Acceder al dashboard**: `http://localhost/admin/reports/pac_errors`

2. **Crear error de prueba** (en tinker):
```php
docker exec -it <contenedor> php artisan tinker

>>> \App\Services\PacErrorCaptureService::captureError([
    'organization_id' => 2,
    'invoice_number' => 'TEST-001',
    'pac_provider' => 'TheFactoryHKA',
    'emission_type' => 'single',
    'error_code' => 'TEST',
    'error_message' => 'Error de prueba',
    'attempt_number' => 1
]);
```

3. **Verificar en dashboard**: El error debe aparecer en la tabla

4. **Verificar base de datos correcta**:
```php
>>> \App\Models\PacEmissionError::query()->getConnection()->getDatabaseName();
// Debe retornar el nombre de la BD principal, NO la BD de organización
```

## Archivos Modificados

- ✅ `app/Models/PacEmissionError.php` - Agregado `$connection = 'mysql'`
- ✅ `database/migrations/2025_12_17_000001_create_pac_emission_errors_table.php` - Usa `Schema::connection('mysql')`
- ✅ `app/Http/Livewire/Admin/Reports/PacErrorsDashboard.php` - Eliminado cambio de conexión
- ✅ `scripts/migrate-pac-errors.sh` - Script de migración creado

## Notas Importantes

1. **Relaciones con otras tablas**: La relación con `SalesHeaderImp` (invoices) funciona porque Laravel maneja automáticamente las diferentes conexiones en las relaciones.

2. **Foreign Keys**: El foreign key a `organizations` está OK porque `organizations` también está en la BD principal.

3. **Performance**: Los índices en `organization_id` aseguran que las consultas filtradas por organización sean rápidas.

4. **Backup**: Considera hacer backup de la BD principal antes de ejecutar la migración en producción.
