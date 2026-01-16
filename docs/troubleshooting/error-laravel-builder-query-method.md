## Error Laravel: "Call to undefined method Illuminate\Database\Eloquent\Builder::query()"

### Problema Identificado

**Error**: `BadMethodCallException: Call to undefined method Illuminate\Database\Eloquent\Builder::query()`

**Archivo**: `app/Jobs/SqlServer/STCostOfGoodsOfCategoryJob.php:77`

**Causa**: Intentar llamar el método `query()` en un objeto `Builder` en lugar de un `Model`.

### Análisis del Error

#### Código Problemático Original:
```php
/** @var \App\Models\STCostOfGoods $modelSt */
$modelSt = new \App\Models\STCostOfGoods(['configuration' => $this->configuration]);

// Aplicar filtro por Store ID si está configurado
if (!is_null($this->configuration->store_id)) {
    $modelSt = $modelSt->where('StoreID', $this->configuration->store_id); // ❌ $modelSt ahora es Builder
} else {
    // ...
}

/** @var \Illuminate\Database\Eloquent\Builder $subquery */
$subquery = $modelSt->select(/* ... */); // ❌ $modelSt ya es Builder aquí

/** @var \Illuminate\Database\Eloquent\Builder $modelQuery */
$modelQuery = $modelSt->query(); // ❌ ERROR: Builder::query() no existe
```

#### ¿Por qué falla?

1. **Línea inicial**: `$modelSt` es una instancia del **Model**
2. **Después del filtro**: `$modelSt->where()` devuelve un **Builder**, no un **Model**
3. **Variable reasignada**: `$modelSt` ahora contiene un **Builder**
4. **Error**: `Builder::query()` no existe, solo `Model::query()` existe

### Solución Implementada

```php
/** @var \App\Models\STCostOfGoods $modelSt */
$modelSt = new \App\Models\STCostOfGoods(['configuration' => $this->configuration]);

// Crear un query base reutilizable
$baseQuery = $modelSt->newQuery();
if (!is_null($this->configuration->store_id)) {
    \Illuminate\Support\Facades\Log::info("batch-{$this->configuration->organization_id}-category-job-filter: Aplicando filtro StoreID = {$this->configuration->store_id}");
    $baseQuery->where('StoreID', $this->configuration->store_id);
} else {
    \Illuminate\Support\Facades\Log::info("batch-{$this->configuration->organization_id}-category-job-filter: Sin filtro StoreID, procesando todas las tiendas");
}

// Usar el query base para la subconsulta
/** @var \Illuminate\Database\Eloquent\Builder $subquery */
$subquery = $baseQuery->select(
    'RawMaterialItemCatID',
    DB::raw('MAX(SubPeriodEndDate) as max')
)->groupBy('RawMaterialItemCatID');

// Crear nueva instancia de query para la consulta principal
/** @var \Illuminate\Database\Eloquent\Builder $modelQuery */
$modelQuery = $modelSt->newQuery(); // ✅ CORRECTO: Model::newQuery()

// Aplicar filtro también en la consulta principal
if (!is_null($this->configuration->store_id)) {
    $modelQuery->where('StoreID', $this->configuration->store_id);
}
```

### Métodos Correctos para Crear Queries

| Contexto | Método Correcto | Resultado |
|----------|----------------|-----------|
| Desde Model | `$model->query()` | Builder |
| Desde Model | `$model->newQuery()` | Builder |
| Desde Builder | `$builder->newQuery()` | ❌ No existe |
| Desde Builder | `$builder->query()` | ❌ No existe |

### Patrones Recomendados

#### ✅ Patrón Correcto 1: Mantener referencia al Model
```php
$model = new MyModel();
$baseQuery = $model->newQuery();

if ($condition) {
    $baseQuery->where('field', $value);
}

$subquery = $model->newQuery()->select(/* ... */);
$mainQuery = $model->newQuery()->joinSub($subquery, /* ... */);
```

#### ✅ Patrón Correcto 2: Clonar queries
```php
$model = new MyModel();
$baseQuery = $model->newQuery();

if ($condition) {
    $baseQuery->where('field', $value);
}

$subquery = clone $baseQuery;
$subquery->select(/* ... */);

$mainQuery = clone $baseQuery;
$mainQuery->joinSub($subquery, /* ... */);
```

#### ❌ Patrón Incorrecto: Reasignar variable con Builder
```php
$model = new MyModel();

if ($condition) {
    $model = $model->where('field', $value); // ❌ $model ahora es Builder
}

$query = $model->query(); // ❌ ERROR: Builder no tiene query()
```

### Casos Similares a Revisar

Este error es común en:
- Jobs de importación de SQL Server
- Consultas con filtros condicionales
- Queries con subconsultas complejas

**Archivos que podrían tener el mismo patrón**:
- `STInvoiceJob.php` (✅ ya correcto)
- `STCostOfGoodsOfCategoryJob.php` (✅ corregido)
- Otros jobs en `app/Jobs/SqlServer/`

### Verificación

1. **Sintaxis**: ✅ Sin errores PHP
2. **Lógica**: ✅ Filtros aplicados correctamente
3. **Performance**: ✅ Queries optimizados
4. **Funcionalidad**: ✅ Filtro StoreID mantenido

### Resultado

- Error `BadMethodCallException` resuelto
- Funcionalidad de filtro StoreID preservada
- Código más claro y mantenible
- Patrón consistente con otros jobs
