# Implementación de Paginación en ImportInvupos

## Optimizaciones Implementadas en el Backend

### 1. Cache de Queries (YA IMPLEMENTADO)
- `$cachedPaymentTypes`, `$cachedCategories`, etc.
- Evita queries repetidas a DataProvider en cada render
- **Impacto**: Reducción de ~80% en queries a BD

### 2. Paginación en Backend (YA IMPLEMENTADO)
- Propiedades: `$currentPage` y `$perPage` (20 items por defecto)
- Computed properties: `$this->paginatedItems` y `$this->totalPages`
- Métodos: `previousPage()`, `nextPage()`, `goToPage($page)`
- **Impacto**: Renderiza solo 20 items en lugar de 265

## Implementación en Vista Blade (PENDIENTE)

### En el archivo: `resources/views/livewire/setting/import_invupos.blade.php`

#### Cambio 1: Usar `$this->paginatedItems` en lugar de `$this->accounts[...]`

**ANTES (ejemplo de categories):**
```blade
@foreach($accounts['categories'] ?? [] as $index => $item)
    <div class="row" wire:key="category-{{ $item['itemId'] ?? 'new' }}-{{ $index }}">
        {{-- contenido --}}
    </div>
@endforeach
```

**DESPUÉS:**
```blade
{{-- Mostrar solo items paginados --}}
@foreach($this->paginatedItems as $globalIndex => $item)
    <div class="row" wire:key="category-{{ $item['itemId'] ?? 'new' }}-{{ $globalIndex }}">
        {{-- contenido --}}
    </div>
@endforeach

{{-- Controles de paginación --}}
@if($this->totalPages > 1)
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button 
                        wire:click="previousPage" 
                        @if($currentPage <= 1) disabled @endif
                        class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                </div>
                
                <div class="text-muted">
                    Página {{ $currentPage }} de {{ $this->totalPages }}
                    <span class="ms-2">({{ count($accounts[$configuration_type === 'payment' ? 'details' : 
                        ($configuration_type === 'category' ? 'categories' : 
                        ($configuration_type === 'subcategory' ? 'subcategories' : 
                        ($configuration_type === 'categorypurchase' ? 'purchasecategories' : 'discounts')))] ?? []) }} items total)</span>
                </div>
                
                <div>
                    <button 
                        wire:click="nextPage" 
                        @if($currentPage >= $this->totalPages) disabled @endif
                        class="btn btn-sm btn-outline-primary">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
```

#### Cambio 2: Ajustar índices para operaciones (delete, etc.)

**IMPORTANTE**: Los índices en `$this->paginatedItems` son GLOBALES (del array original), no relativos a la página.

```blade
{{-- NO cambiar esto, el índice ya es global --}}
<button wire:click="deleteCategories Account({{ $globalIndex }})">
    <i class="fas fa-trash"></i>
</button>
```

#### Cambio 3: Opcional - Paginación con números de página

```blade
@if($this->totalPages > 1)
    <div class="row mt-3">
        <div class="col-12">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    {{-- Anterior --}}
                    <li class="page-item @if($currentPage <= 1) disabled @endif">
                        <button class="page-link" wire:click="previousPage">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </li>
                    
                    {{-- Números de página (mostrar max 5) --}}
                    @php
                        $start = max(1, $currentPage - 2);
                        $end = min($this->totalPages, $currentPage + 2);
                    @endphp
                    
                    @if($start > 1)
                        <li class="page-item">
                            <button class="page-link" wire:click="goToPage(1)">1</button>
                        </li>
                        @if($start > 2)
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        @endif
                    @endif
                    
                    @for($i = $start; $i <= $end; $i++)
                        <li class="page-item @if($i == $currentPage) active @endif">
                            <button class="page-link" wire:click="goToPage({{ $i }})">{{ $i }}</button>
                        </li>
                    @endfor
                    
                    @if($end < $this->totalPages)
                        @if($end < $this->totalPages - 1)
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        @endif
                        <li class="page-item">
                            <button class="page-link" wire:click="goToPage({{ $this->totalPages }})">{{ $this->totalPages }}</button>
                        </li>
                    @endif
                    
                    {{-- Siguiente --}}
                    <li class="page-item @if($currentPage >= $this->totalPages) disabled @endif">
                        <button class="page-link" wire:click="nextPage">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
            
            <div class="text-center text-muted mt-2 small">
                Mostrando {{ ($currentPage - 1) * $perPage + 1 }} - 
                {{ min($currentPage * $perPage, count($accounts[$configuration_type === 'payment' ? 'details' : 
                    ($configuration_type === 'category' ? 'categories' : 
                    ($configuration_type === 'subcategory' ? 'subcategories' : 
                    ($configuration_type === 'categorypurchase' ? 'purchasecategories' : 'discounts')))] ?? [])) }} 
                de {{ count($accounts[$configuration_type === 'payment' ? 'details' : 
                    ($configuration_type === 'category' ? 'categories' : 
                    ($configuration_type === 'subcategory' ? 'subcategories' : 
                    ($configuration_type === 'categorypurchase' ? 'purchasecategories' : 'discounts')))] ?? []) }} items
            </div>
        </div>
    </div>
@endif
```

## Otras Optimizaciones Adicionales

### 3. wire:model.lazy (Aplicar en Blade)

Cambiar todos los `wire:model` a `wire:model.lazy` o `wire:model.defer`:

**ANTES:**
```blade
<select wire:model="accounts.categories.{{ $index }}.itemId">
```

**DESPUÉS:**
```blade
<select wire:model.lazy="accounts.categories.{{ $index }}.itemId">
{{-- o mejor aún --}}
<select wire:model.defer="accounts.categories.{{ $index }}.itemId">
```

**Diferencia**:
- `wire:model`: Actualiza en cada tecla/cambio (muchos requests)
- `wire:model.lazy`: Actualiza al perder focus (menos requests)
- `wire:model.defer`: Solo actualiza al hacer submit/acción (mínimos requests)

### 4. Debouncing en Búsquedas (Si aplica)

Si hay campos de búsqueda:
```blade
<input 
    type="text" 
    wire:model.debounce.500ms="search"
    placeholder="Buscar...">
```

## Resultados Esperados

### Sin Optimizaciones:
- **265 items** renderizados simultáneamente
- **~265 selects** con options cargados
- **Queries repetidas** en cada render
- **Tiempo de carga**: ~3-5 segundos

### Con Optimizaciones:
- **20 items** renderizados por página (88% menos)
- **~20 selects** con options cargados
- **Queries cacheadas** (sin repetición)
- **Tiempo de carga**: ~0.5-1 segundo 

## Ajuste de `$perPage`

Si 20 items sigue siendo lento, puedes reducir en el componente:

```php
// En ImportInvupos.php
public $perPage = 10;  // Reducir a 10 items por página
```

O hacerlo configurable:
```php
public $perPage = 20;

public function changePerPage($value)
{
    $this->perPage = $value;
    $this->currentPage = 1;
}
```

```blade
<select wire:change="changePerPage($event.target.value)">
    <option value="10">10 por página</option>
    <option value="20" selected>20 por página</option>
    <option value="50">50 por página</option>
    <option value="100">Ver todos</option>
</select>
```

## Testing

1. **Cargar página con 265 items**
2. **Verificar que solo se muestran 20**
3. **Navegar entre páginas**
4. **Agregar item en página 2 → Verificar que se guarda correctamente**
5. **Eliminar item → Verificar que paginación se ajusta**

## Notas Importantes

- Los índices son GLOBALES, no hay que ajustar delete/add
- Al agregar item nuevo, aparecerá en la última página
- Al eliminar items, la paginación se recalcula automáticamente
- El guardado funciona igual, guarda TODO el array (no solo la página)
