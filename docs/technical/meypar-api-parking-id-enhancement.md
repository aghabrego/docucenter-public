# Mejora API Meypar - Autenticación Flexible con parkingID Alfanumérico

**Fecha:** 4 de diciembre de 2025  
**Versión:** 1.0  
**Estado:** Análisis y Propuesta de Implementación  
**Módulo:** API Meypar Authentication

---

## Índice

1. [Contexto del Requerimiento](#contexto-del-requerimiento)
2. [Análisis de la Situación Actual](#análisis-de-la-situación-actual)
3. [Propuesta de Solución](#propuesta-de-solución)
4. [Cambios Necesarios](#cambios-necesarios)
5. [Plan de Implementación](#plan-de-implementación)
6. [Testing](#testing)
7. [Consideraciones de Seguridad](#consideraciones-de-seguridad)

---

## Contexto del Requerimiento

### Solicitud del Cliente

El cliente Meypar requiere autenticación flexible usando dos tipos de identificadores:

**Parámetros API Meypar → DocuCenter:**
```json
{
  "userName": "greysa@ffproperties.net",    // → email
  "userPW": "Admin.123$",                   // → password
  "parkingId": "ADCOL04880" o 101,          // → organization_id O código alfanumérico
  "ambiente": 1,                            // → point_sale
  "idFacturador": "110"                     // → token_name
}
```

### Casos de Uso

1. **Caso Numérico (Actual):**
   - `parkingId: 101` → Busca directamente por `organizations.id = 101`
   - Funciona correctamente en el sistema actual

2. **Caso Alfanumérico (Nuevo):**
   - `parkingId: "ADCOL04880"` → Debe buscar por nuevo campo `codigo_sistema`
   - Permite identificadores más descriptivos y memorizables
   - Facilita integración con sistemas externos

### Beneficios

- Mayor flexibilidad en identificación de organizaciones
- Códigos memorizables para clientes (ej: `ADCOL04880` vs `101`)
- Compatibilidad con sistemas legacy que usan códigos alfanuméricos
- Mantiene retrocompatibilidad con IDs numéricos existentes

---

## Análisis de la Situación Actual

### Endpoint Actual

**Ruta:** `POST /api/v1/meypar-login`  
**Ubicación:** `routes/api.php` línea 137  
**Controller:** `App\Http\Controllers\V1\AuthController::meyparLogin()`

### Request Actual

**Archivo:** `app/Http/Requests/Auth/MeyparLoginRequest.php`

```php
public function rules()
{
    return [
        'userName' => 'required|email|max:255',
        'userPW' => 'required|string|min:6',
        'parkingId' => 'required|integer|exists:organizations,id',  // Solo acepta integer
        'ambiente' => 'required|integer|between:0,999',
        'idFacturador' => 'nullable|string|max:255',
    ];
}
```

**Problema:** La regla `'parkingId' => 'required|integer|exists:organizations,id'` rechaza valores alfanuméricos.

### Flujo de Autenticación Actual

```php
// app/Http/Controllers/V1/AuthController.php - meyparLogin()

// 1. Validación (solo integer)
$credentials = $request->getMappedCredentials();

// 2. Mapeo directo
'organization_id' => $this->input('parkingId')  // Solo ID numérico

// 3. Verificación de acceso
$hasAccess = $user->organizations()
    ->where('organizations.id', $credentials['organization_id'])  // Busca por ID
    ->exists();

// 4. Obtención de organización
$organization = Organization::findOrFail($credentials['organization_id']);
```

---

## Propuesta de Solución

### 1⃣ Nueva Columna en Tabla `organizations`

**Campo:** `codigo_sistema` (Código Sistema / Parking Code)

**Características:**
- **Tipo:** `VARCHAR(50)`
- **Nullable:** `YES` (opcional para compatibilidad)
- **Único:** `YES` (índice unique para búsquedas rápidas)
- **Uso:** Identificador alfanumérico alternativo para APIs externas

```sql
ALTER TABLE organizations 
ADD COLUMN codigo_sistema VARCHAR(50) NULL UNIQUE AFTER id_empresa,
ADD INDEX idx_codigo_sistema (codigo_sistema);
```

**Ejemplos de valores:**
- `ADCOL04880` (Parqueo Colombia 04880)
- `PNPTY001` (Parqueo Panamá PTY 001)
- `RESTO123` (Restaurante 123)
- `NULL` (organizaciones sin código asignado)

### 2⃣ Lógica de Detección Automática

**Estrategia:** Detectar automáticamente si `parkingId` es numérico o alfanumérico

```php
// Pseudocódigo de detección
if (is_numeric($parkingId) && ctype_digit((string)$parkingId)) {
    // Es numérico → Buscar por organizations.id
    $organization = Organization::find($parkingId);
} else {
    // Es alfanumérico → Buscar por organizations.codigo_sistema
    $organization = Organization::where('codigo_sistema', $parkingId)->first();
}
```

### 3⃣ Ventajas de esta Solución

**Retrocompatibilidad:**
- Organizaciones sin `codigo_sistema` (NULL) siguen funcionando con ID numérico
- No rompe integraciones existentes

**Flexibilidad:**
- Cliente puede usar ID (`101`) o código (`ADCOL04880`)
- Sistema decide automáticamente

**Seguridad:**
- Validación unique evita duplicados
- Índice unique garantiza unicidad
- Búsquedas rápidas con índice

**Usabilidad:**
- Códigos descriptivos más fáciles de recordar
- Facilita debugging en logs
- Mejor documentación de APIs

---

## Cambios Necesarios

### 1. Migration

**Archivo:** `database/migrations/2025_12_04_create_codigo_sistema_column.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('codigo_sistema', 50)
                ->nullable()
                ->unique()
                ->after('id_empresa')
                ->comment('Codigo alfanumerico para identificacion en APIs externas (ej: Meypar)');
            
            // Índice para búsquedas rápidas
            $table->index('codigo_sistema', 'idx_org_codigo_sistema');
        });
    }

    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('idx_org_codigo_sistema');
            $table->dropColumn('codigo_sistema');
        });
    }
};
```

### 2. Modelo Organization

**Archivo:** `app/Models/Organization.php`

```php
protected $fillable = [
    // ... campos existentes ...
    'id_empresa',
    'codigo_sistema',  // AGREGAR
    'user_id',
    'database',
    // ... resto ...
];

/**
 * Buscar organización por parkingId (numérico o alfanumérico)
 * 
 * @param string|int $parkingId
 * @return Organization|null
 */
public static function findByParkingId($parkingId): ?Organization
{
    // Si es numérico puro, buscar por ID
    if (is_numeric($parkingId) && ctype_digit((string)$parkingId)) {
        return static::find((int)$parkingId);
    }
    
    // Si es alfanumérico, buscar por codigo_sistema
    return static::where('codigo_sistema', $parkingId)->first();
}

/**
 * Obtener el parkingId efectivo (ID o codigo_sistema)
 * 
 * @return string|int
 */
public function getParkingIdAttribute()
{
    return $this->codigo_sistema ?? $this->id;
}
```

### 3. MeyparLoginRequest

**Archivo:** `app/Http/Requests/Auth/MeyparLoginRequest.php`

```php
public function rules()
{
    return [
        'userName' => 'required|email|max:255',
        'userPW' => 'required|string|min:6',
        // CAMBIAR: Aceptar string o integer
        'parkingId' => [
            'required',
            'string',
            'max:50',
            function ($attribute, $value, $fail) {
                // Validar que exista la organización (por ID o codigo_sistema)
                $organization = \App\Models\Organization::findByParkingId($value);
                if (!$organization) {
                    $fail('El parkingId especificado no existe');
                }
            }
        ],
        'ambiente' => 'required|integer|between:0,999',
        'idFacturador' => 'nullable|string|max:255',
    ];
}

public function messages()
{
    return [
        // ... mensajes existentes ...
        'parkingId.required' => 'El campo parkingId es requerido',
        'parkingId.string' => 'El campo parkingId debe ser alfanumerico',
        'parkingId.max' => 'El campo parkingId no debe exceder 50 caracteres',
        // ... resto ...
    ];
}

/**
 * Mapea los parámetros de Meypar a los parámetros internos de DocuCenter
 * MODIFICADO: Obtener organización por parkingId flexible
 */
public function getMappedCredentials(): array
{
    // Buscar organización por parkingId (numérico o alfanumérico)
    $organization = \App\Models\Organization::findByParkingId($this->input('parkingId'));
    
    return [
        'email' => $this->input('userName'),
        'password' => $this->input('userPW'),
        'organization_id' => $organization?->id,  // Siempre retorna el ID interno
        'point_sale' => $this->input('ambiente'),
        'token_name' => $this->input('idFacturador') ?? 'Meypar API Token - ' . now()->format('Y-m-d H:i:s'),
    ];
}
```

### 4. Componente Livewire Create

**Archivo:** `app/Http/Livewire/Admin/Organization/Create.php`

```php
class Create extends Component
{
    // ... propiedades existentes ...
    public $codigo_sistema;  // AGREGAR
    
    protected $rules = [
        // ... reglas existentes ...
        'id_empresa' => 'nullable|numeric|max:255',
        'codigo_sistema' => 'nullable|string|max:50|unique:organizations,codigo_sistema',  // AGREGAR
    ];

    public function create()
    {
        if($this->getRules())
            $this->validate();

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CreatedMessage', ['name' => __('Organization') ])]);
        
        Organization::create([
            // ... campos existentes ...
            'id_empresa' => $this->id_empresa,
            'codigo_sistema' => $this->codigo_sistema,  // AGREGAR
            'user_id' => auth()->id(),
        ]);

        $this->reset();
    }
}
```

### 5. Componente Livewire Update

**Archivo:** `app/Http/Livewire/Admin/Organization/Update.php`

```php
class Update extends Component
{
    // ... propiedades existentes ...
    public $codigo_sistema;  // AGREGAR
    
    protected $rules = [
        // ... reglas existentes ...
        'id_empresa' => 'nullable|numeric|max:255',
        'codigo_sistema' => 'nullable|string|max:50|unique:organizations,codigo_sistema',  // AGREGAR
    ];

    public function mount(Organization $Organization){
        // ... asignaciones existentes ...
        $this->id_empresa = $this->organization->id_empresa;
        $this->codigo_sistema = $this->organization->codigo_sistema;  // AGREGAR
    }

    public function update()
    {
        if($this->getRules())
            $this->validate();

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('UpdatedMessage', ['name' => __('Organization') ]) ]);
        
        $this->organization->update([
            // ... campos existentes ...
            'id_empresa' => $this->id_empresa,
            'codigo_sistema' => $this->codigo_sistema,  // AGREGAR
            'user_id' => auth()->id(),
        ]);
    }
}
```

### 6. Vista Blade Create

**Archivo:** `resources/views/livewire/admin/organization/create.blade.php`

**Insertar después del campo "Compañías" (línea ~130):**

```blade
<!-- Código Sistema Input -->
<div class='form-group'>
    <label for='input-codigo_sistema' class='col-sm-2 control-label'> 
        {{ __('System Code') }} 
        <small class="text-muted">(Parking ID API)</small>
    </label>
    <input 
        type='text' 
        id='input-codigo_sistema' 
        wire:model.lazy='codigo_sistema' 
        class="form-control @error('codigo_sistema') is-invalid @enderror" 
        placeholder='Ej: ADCOL04880, PNPTY001' 
        autocomplete='off'
        maxlength="50">
    <small class="form-text text-muted">
        Código alfanumérico opcional para identificación en APIs externas (Meypar, etc.)
    </small>
    @error('codigo_sistema') <div class='invalid-feedback'>{{ $message ?? '' }}</div> @enderror
</div>
```

### 7. Vista Blade Update

**Archivo:** `resources/views/livewire/admin/organization/update.blade.php`

**Insertar en el mismo lugar (después del campo "Compañías"):**

```blade
<!-- Código Sistema Input -->
<div class='form-group'>
    <label for='input-codigo_sistema' class='col-sm-2 control-label'> 
        {{ __('System Code') }} 
        <small class="text-muted">(Parking ID API)</small>
    </label>
    <input 
        type='text' 
        id='input-codigo_sistema' 
        wire:model.lazy='codigo_sistema' 
        class="form-control @error('codigo_sistema') is-invalid @enderror" 
        placeholder='Ej: ADCOL04880, PNPTY001' 
        autocomplete='off'
        maxlength="50">
    <small class="form-text text-muted">
        Código alfanumérico opcional para identificación en APIs externas (Meypar, etc.)
    </small>
    @error('codigo_sistema') <div class='invalid-feedback'>{{ $message ?? '' }}</div> @enderror
</div>
```

### 8. Archivo de Traducciones

**Archivo:** `lang/es_panel.json` (o `lang/es/panel.php`)

```json
{
    "System Code": "Código Sistema",
    "Parking ID API": "ID Parking API"
}
```

---

## Plan de Implementación

### Fase 1: Base de Datos (30 min)

```bash
# 1. Crear migration
php artisan make:migration add_codigo_sistema_to_organizations_table

# 2. Ejecutar migration
php artisan migrate

# 3. Verificar en DB
docker exec -it docucenter_laravel.test php artisan tinker
>>> DB::select("SHOW COLUMNS FROM organizations LIKE 'codigo_sistema'");
```

### Fase 2: Modelo y Lógica (45 min)

1. Agregar `codigo_sistema` a `$fillable` en Organization
2. Crear método `findByParkingId()` estático
3. Crear accessor `getParkingIdAttribute()`
4. Modificar `MeyparLoginRequest::rules()`
5. Modificar `MeyparLoginRequest::getMappedCredentials()`

### Fase 3: Componentes Livewire (30 min)

1. Agregar propiedad `$codigo_sistema` en Create/Update
2. Agregar regla de validación
3. Agregar asignación en `mount()` (Update)
4. Agregar campo en `create()` y `update()`

### Fase 4: Vistas Blade (20 min)

1. Agregar campo input en `create.blade.php`
2. Agregar campo input en `update.blade.php`
3. Agregar traducciones

### Fase 5: Testing (1 hora)

1. Test unitario: `Organization::findByParkingId()`
2. Test feature: Login con ID numérico
3. Test feature: Login con código alfanumérico
4. Test validación: código duplicado
5. Test UI: crear/editar organización

### Fase 6: Migración de Datos (si necesario)

```php
// Script para asignar códigos a organizaciones existentes
foreach (Organization::all() as $org) {
    if (!$org->codigo_sistema) {
        $org->codigo_sistema = 'ORG' . str_pad($org->id, 6, '0', STR_PAD_LEFT);
        $org->save();
    }
}

// Resultado: ORG000001, ORG000002, etc.
```

---

## Testing

### Test 1: Método findByParkingId()

```php
// tests/Unit/OrganizationTest.php

test('encuentra organizacion por ID numerico', function () {
    $org = Organization::factory()->create(['id' => 123]);
    
    $found = Organization::findByParkingId('123');
    
    expect($found)->not->toBeNull();
    expect($found->id)->toBe(123);
});

test('encuentra organizacion por codigo alfanumerico', function () {
    $org = Organization::factory()->create([
        'codigo_sistema' => 'ADCOL04880'
    ]);
    
    $found = Organization::findByParkingId('ADCOL04880');
    
    expect($found)->not->toBeNull();
    expect($found->codigo_sistema)->toBe('ADCOL04880');
});

test('retorna null si no existe parkingId', function () {
    $found = Organization::findByParkingId('NOEXISTE999');
    
    expect($found)->toBeNull();
});
```

### Test 2: Login Meypar con Código Alfanumérico

```php
// tests/Feature/MeyparApiTest.php

test('login exitoso con parkingId alfanumerico', function () {
    $user = User::factory()->create([
        'email' => 'greysa@ffproperties.net',
        'password' => Hash::make('Admin.123$')
    ]);
    
    $org = Organization::factory()->create([
        'codigo_sistema' => 'ADCOL04880'
    ]);
    
    $user->organizations()->attach($org->id);
    
    $response = $this->postJson('/api/v1/meypar-login', [
        'userName' => 'greysa@ffproperties.net',
        'userPW' => 'Admin.123$',
        'parkingId' => 'ADCOL04880',  // Alfanumérico
        'ambiente' => 1,
        'idFacturador' => '110'
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'token',
            'data' => [
                'user',
                'organization',
                'license_info'
            ]
        ]);
});

test('login exitoso con parkingId numerico (retrocompatibilidad)', function () {
    // Similar al anterior pero con parkingId: 101
    $org = Organization::factory()->create(['id' => 101]);
    
    $response = $this->postJson('/api/v1/meypar-login', [
        'userName' => 'greysa@ffproperties.net',
        'userPW' => 'Admin.123$',
        'parkingId' => '101',  // Numérico (string)
        'ambiente' => 1,
    ]);
    
    $response->assertStatus(200);
});

test('login falla con parkingId inexistente', function () {
    $response = $this->postJson('/api/v1/meypar-login', [
        'userName' => 'greysa@ffproperties.net',
        'userPW' => 'Admin.123$',
        'parkingId' => 'NOEXISTE999',
        'ambiente' => 1,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['parkingId']);
});
```

### Test 3: Validación de Unicidad

```php
test('no permite codigo_sistema duplicado', function () {
    Organization::factory()->create(['codigo_sistema' => 'TEST001']);
    
    $this->expectException(ValidationException::class);
    
    Organization::create([
        'nombre' => 'Test Org 2',
        'codigo_sistema' => 'TEST001',  // Duplicado
        // ... otros campos requeridos ...
    ]);
});
```

---

## Consideraciones de Seguridad

### Validaciones Implementadas

1. **Unicidad:** Índice `UNIQUE` en BD + validación Laravel
2. **Longitud:** Máximo 50 caracteres
3. **Existencia:** Validación custom en `MeyparLoginRequest`
4. **Tipo:** Acepta alfanumérico (letras, números, guiones)

### Recomendaciones Adicionales

```php
// Agregar validación de formato en el modelo
public function setCodigoSistemaAttribute($value)
{
    // Limpiar y normalizar
    $this->attributes['codigo_sistema'] = $value 
        ? strtoupper(trim($value)) 
        : null;
}

// Validar formato alfanumérico (opcional)
'codigo_sistema' => 'nullable|string|max:50|unique:organizations,codigo_sistema|regex:/^[A-Z0-9\-_]+$/',
```

### Rate Limiting

```php
// routes/api.php
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/meypar-login', [AuthController::class, 'meyparLogin']);
});
```

---

## Ventajas del Approach Propuesto

### Técnicas

- **Retrocompatibilidad Total:** IDs numéricos siguen funcionando
- **Performance:** Índice en `codigo_sistema` para búsquedas rápidas
- **Flexibilidad:** Detección automática sin cambios en cliente
- **Mantenibilidad:** Lógica centralizada en `findByParkingId()`

### Negocio

- **UX Mejorado:** Códigos memorizables (`ADCOL04880` vs `101`)
- **Integración Simplificada:** Clientes externos usan sus códigos
- **Escalabilidad:** Soporta múltiples sistemas con diferentes esquemas
- **Documentación:** Códigos autodescriptivos

### Operacionales

- **Deployment Seguro:** Migration nullable, sin downtime
- **Testing Completo:** Cobertura de casos numéricos y alfanuméricos
- **Logs Mejorados:** Identificación clara en logs (`ADCOL04880` vs `101`)

---

## Resumen de Archivos Modificados

| Archivo | Cambios | Líneas |
|---------|---------|--------|
| **Migration** | Nueva columna `codigo_sistema` | +15 |
| **app/Models/Organization.php** | `findByParkingId()`, `$fillable`, accessor | +30 |
| **app/Http/Requests/Auth/MeyparLoginRequest.php** | Validación flexible, getMappedCredentials() | +20 |
| **app/Http/Livewire/Admin/Organization/Create.php** | Propiedad, validación, create() | +5 |
| **app/Http/Livewire/Admin/Organization/Update.php** | Propiedad, validación, mount(), update() | +7 |
| **resources/views/livewire/admin/organization/create.blade.php** | Campo input | +15 |
| **resources/views/livewire/admin/organization/update.blade.php** | Campo input | +15 |
| **lang/es_panel.json** | Traducciones | +2 |
| **tests/Unit/OrganizationTest.php** | Tests unitarios | +40 |
| **tests/Feature/MeyparApiTest.php** | Tests integración | +60 |

**Total:** ~210 líneas de código nuevo

---

## Ejemplo de Uso en Postman

### Request con ID Numérico (Actual)

```json
POST /api/v1/meypar-login
Content-Type: application/json

{
  "userName": "greysa@ffproperties.net",
  "userPW": "Admin.123$",
  "parkingId": "101",
  "ambiente": 1,
  "idFacturador": "110"
}
```

### Request con Código Alfanumérico (Nuevo)

```json
POST /api/v1/meypar-login
Content-Type: application/json

{
  "userName": "greysa@ffproperties.net",
  "userPW": "Admin.123$",
  "parkingId": "ADCOL04880",
  "ambiente": 1,
  "idFacturador": "110"
}
```

**Ambos funcionan sin cambios en la URL o headers** 

---

## Conclusión

La solución propuesta:

1. **Resuelve el requerimiento:** Soporta `parkingId` alfanumérico
2. **Mantiene compatibilidad:** IDs numéricos siguen funcionando
3. **Implementación sencilla:** ~210 líneas, sin breaking changes
4. **Performance óptima:** Índice unique, búsquedas rápidas
5. **UI intuitiva:** Campo claro en formularios de organización
6. **Testing completo:** Cobertura de casos edge
7. **Documentación clara:** Ejemplos y casos de uso

**Tiempo estimado:** 3-4 horas de implementación + testing  
**Riesgo:** Bajo (cambios aislados, migration segura)  
**Impacto:** Alto (mejora significativa en UX y flexibilidad)

**Recomendación:** Implementar en siguiente sprint
