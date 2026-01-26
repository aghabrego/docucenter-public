# Optimización de Memoria - ImportInvupos

**Fecha**: 2024-12-19  
**Componente**: `app/Http/Livewire/Setting/ImportInvupos.php`  
**Objetivo**: Reducir uso de memoria cargando solo el tab actual, preservando todos los datos en BD

## Problema Original

Cuando trabajabas en un tab (ej: "category"), el componente cargaba **TODOS** los arrays simultáneamente en `$this->accounts`:

```php
$this->accounts = [
    'glSalesAccount' => '...',
    'details' => [...100 items...],
    'categories' => [...100 items...],
    'subcategories' => [...100 items...],
    'purchasecategories' => [...100 items...],
    'discounts' => [...50 items...],
]
```

**Impacto**: Con organizaciones que tienen 100+ items por tab, esto causaba lentitud significativa.

## Solución Implementada

### 1. Nuevo Método: `unloadPreviousTab()`

Descarga de memoria el tab anterior cuando cambias a otro tab:

```php
private function unloadPreviousTab(string $previousTab)
{
    $tabKey = $this->getTabKeyName($previousTab);
    
    if ($tabKey && isset($this->accounts[$tabKey])) {
        \Log::channel('daily')->info('ImportInvupos: Descargando tab de memoria', [
            'tab' => $previousTab,
            'key' => $tabKey,
            'count_before_unload' => count($this->accounts[$tabKey]),
        ]);
        
        // Eliminar el array del tab de $this->accounts para liberar memoria
        unset($this->accounts[$tabKey]);
        
        \Log::channel('daily')->info('ImportInvupos: Tab descargado exitosamente', [
            'remaining_keys' => array_keys($this->accounts),
        ]);
    }
}
```

### 2. Modificación en `activeTab()`

Flujo optimizado cuando cambias de tab:

```php
public function activeTab(string $type = null)
{
    if (isset($this->configuration_type) && $this->configuration_type !== $type) {
        // 1. Respaldar tab actual
        $this->syncCurrentTabToBackup($this->configuration_type);
        
        // 2. Guardar en BD con MERGE (preserva otros tabs)
        $this->updateCurrentTabOnly($this->configuration_type);
        
        // 3. NUEVO: Descargar tab anterior de memoria
        $this->unloadPreviousTab($this->configuration_type);
    }
    
    // 4. Cargar nuevo tab
    $this->loadTabData($type);
    
    // ... resto del código
}
```

### 3. Preservación de Datos en BD

El método `updateCurrentTabOnly()` **YA hace merge correctamente** (no modificado):

```php
// Cargar TODOS los datos existentes de BD
$dbAccounts = $config->accounts ?? [];

// Actualizar SOLO el tab actual
$dbAccounts[$tabKey] = $this->accounts[$tabKey];

// Guardar TODO de vuelta (preserva tabs no modificados)
$config->accounts = $dbAccounts;
$config->save();
```

## Resultado

### Antes de la Optimización

**En Memoria** (`$this->accounts`):
- `glSalesAccount`, `glInventoryAccount`, etc.
- `details` → 100 items
- `categories` → 100 items
- `subcategories` → 80 items
- `purchasecategories` → 120 items
- `discounts` → 50 items

**Total en memoria**: ~450 items simultáneamente

### Después de la Optimización

**En Memoria** (`$this->accounts`) cuando estás en tab "category":
- `glSalesAccount`, `glInventoryAccount`, etc.
- `categories` → 100 items

**Total en memoria**: ~100 items (solo el tab actual)

**En Base de Datos** (serializado en columna `accounts`):
- TODOS los tabs preservados: `details`, `categories`, `subcategories`, `purchasecategories`, `discounts`
- Nada se pierde
- Se carga on-demand

## Diagrama de Flujo

```
Usuario cambia de "payment" → "category"

 1. syncCurrentTabToBackup('payment')
   $accountsBackup['details'] = $accounts['details']

 2. updateCurrentTabOnly('payment')
   Carga TODOS de BD: $dbAccounts = BD[all tabs]
   Actualiza solo: $dbAccounts['details'] = $accounts['details']
   Guarda TODO: BD[all tabs] = $dbAccounts

 3. unloadPreviousTab('payment') ← NUEVO
   unset($accounts['details']) ← Libera memoria

 4. loadTabData('category')
    $accounts['categories'] = BD['categories']

Resultado:
- Memoria: Solo campos generales + ['categories']
- BD: TODOS los tabs preservados
```

## Beneficios

1. **Reducción de Memoria**: ~80% menos datos en memoria (4-5 tabs menos)
2. **Mejor Performance**: Menos datos para serializar/deserializar en cada request Livewire
3. **Seguridad de Datos**: Merge en BD garantiza que no se pierden tabs no cargados
4. **Escalabilidad**: Funciona igual con 10 o 1000 items por tab
5. **Sin Cambios en UI**: Funcionalidad exactamente igual para el usuario

## Verificación

### Script de Verificación Automática

```bash
./scripts/test-invupos-memory-optimization.sh
```

Verifica:
- Método `unloadPreviousTab()` existe
- Se llama en `activeTab()`
- Usa `unset()` para liberar memoria
- `updateCurrentTabOnly()` hace merge con BD
- Preserva datos de todos los tabs

### Comando Artisan de Diagnóstico

```bash
docker exec -it docucenter-app-1 php artisan invupos:verify-memory {organization_id}
```

Muestra:
- Cuentas generales configuradas
- Tabs existentes en BD con conteo de items
- Tamaño aproximado de datos serializados
- Cálculo de ahorro de memoria

## Logs de Debugging

Para verificar el comportamiento en producción, revisar logs:

```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "ImportInvupos"
```

Mensajes clave:
- `ImportInvupos: Descargando tab de memoria` → Se descarga tab anterior
- `ImportInvupos: Tab descargado exitosamente` → Muestra keys restantes en memoria
- `ImportInvupos: Actualizado tab en BD` → Se guardó con merge
- `ImportInvupos: Cargado {tab} desde BD` → Se cargó nuevo tab

## Compatibilidad

**Sin Breaking Changes**: La funcionalidad es idéntica desde la perspectiva del usuario  
**Retrocompatible**: Funciona con datos existentes en BD  
**Sin Migraciones**: No requiere cambios en estructura de BD  
**Lazy Loading**: Los tabs se cargan solo cuando se acceden  

## Testing Recomendado

1. **Prueba básica**: Cambiar entre tabs y verificar que los datos persisten
2. **Prueba de guardado**: Agregar items en un tab, cambiar a otro, volver y verificar
3. **Prueba de BD**: Revisar con comando artisan que todos los tabs están en BD
4. **Prueba de logs**: Verificar que los logs muestran descarga de tabs

## Notas Técnicas

- **Propiedad `$loadedTabs`**: Trackea qué tabs ya fueron cargados (evita recargas)
- **Propiedad `$accountsBackup`**: Backup temporal para restaurar si hay error
- **Serialización**: Laravel maneja automáticamente con `$casts` en modelo
- **Livewire State**: `$accounts` es public, Livewire sincroniza solo lo presente

## Próximas Mejoras Posibles

1. **Cache de Redis**: Cachear tabs frecuentemente accedidos
2. **Paginación**: Para tabs con 500+ items, implementar paginación
3. **Compresión**: Comprimir datos serializados en BD si superan cierto tamaño
4. **Lazy Properties**: Usar Livewire lazy properties para tabs no activos

---

**Autor**: Equipo DocuCenter  
**Revisión**: Pendiente de testing en producción  
**Estado**: Implementado y verificado con script automatizado
