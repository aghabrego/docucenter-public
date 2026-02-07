# Sistema de Reintento Masivo de Transacciones con Fechas Personalizables

**Fecha:** 7 de febrero de 2026  
**Versión:** 1.0  
**Estado:** Propuesta de Implementación

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Análisis del Sistema Actual](#análisis-del-sistema-actual)
3. [Propuesta de Mejora](#propuesta-de-mejora)
4. [Arquitectura de la Solución](#arquitectura-de-la-solución)
5. [Mapeo de Fechas por Módulo](#mapeo-de-fechas-por-módulo)
6. [Especificaciones Técnicas](#especificaciones-técnicas)
7. [Implementación Detallada](#implementación-detallada)
8. [Casos de Uso](#casos-de-uso)
9. [Consideraciones de Seguridad](#consideraciones-de-seguridad)
10. [Testing](#testing)

---

## Resumen Ejecutivo

Este documento describe la implementación de un **sistema de reintento masivo de transacciones** con capacidades avanzadas de configuración de fechas para el módulo de Gestión de Transacciones de DocuCenter. El sistema permitirá:

- **Procesamiento masivo** de hasta 50 transacciones simultáneas
- **Configuración flexible de fechas** (Date y DueDate)
- **Feedback en tiempo real** del progreso de cada transacción
- **Manejo seguro de tokens** Sanctum de un solo uso
- **Validación por módulo** según especificaciones de cada API

---

## Análisis del Sistema Actual

### Ubicación del Código

**Componente Livewire:**
```
app/Http/Livewire/Admin/Transactions/Manage.php (912 líneas)
```

**Vista Blade:**
```
resources/views/livewire/admin/transactions/manage.blade.php (524 líneas)
```

**Modelo:**
```
app/Models/Transaction.php (258 líneas)
```

### Funcionalidades Existentes

#### 1. Gestión Individual de Transacciones

```php
// Métodos principales actuales
public function retryTransaction($transactionId)      // Reintentar individual
public function viewTransaction($transactionId)       // Ver detalles
public function confirmDelete($transactionId)         // Eliminar
public function executeRetry()                        // Ejecutar reintento
```

#### 2. Sistema de Tokens Sanctum

- **Generación de tokens de un solo uso** con expiración de 30 minutos
- **Validación automática** contra el endpoint `/api/user`
- **Limpieza automática** de tokens antiguos (>1 hora)
- **Cache de tokens** por sesión de usuario

```php
private function generateSingleUseToken($userId, $organizationId)
private function testTokenWithAPI($token)
private function cleanUpSingleUseTokens()
private function cleanUpOldSingleUseTokens($organizationId)
```

#### 3. Módulos y APIs Soportados

| Módulo | APIs Disponibles |
|--------|------------------|
| **Kart21** | `/api/v1/fe/create_sale_kart21` |
| **MaxGym** | `/api/v1/fe/create_sale_maxgym` |
| **Shopify** | `/api/v1/fe/create_sale_shopify` |
| **Lightspeed** | `/api/v1/fe/create_sale_lightspeed` |
| **ACI Cloud** | `/api/v1/fe/create_sale_acicloud`<br>`/api/v1/fe/create_sale_acicloud_without_issuing` |
| **QuickBooks** | `/api/v1/fe/create_sale_quickbooks` |
| **Meypar** | `/api/v1/fe/create_sale_meypar`<br>`/api/v1/fe/create_sale_meypar_with_emission` |

#### 4. Filtros y Búsqueda

- Búsqueda por referencia u organización
- Filtro por estado: `pending`, `processing`, `success`, `failed`
- Filtro por tipo de módulo
- Paginación con 15 registros por página

### Limitaciones Actuales

**No hay selección múltiple** de transacciones  
**No hay procesamiento masivo**  
**No hay configuración de fechas** Date/DueDate  
**No hay barra de progreso** para múltiples operaciones  
**No hay reporte consolidado** de resultados masivos  

---

## Propuesta de Mejora

### Nuevas Funcionalidades

#### 1. Selección Múltiple de Transacciones

```
┌─────────────────────────────────────────────┐
│ [ ] Seleccionar Todo  │  (5 seleccionadas) │
├─────────────────────────────────────────────┤
│ [✓] ID: 123 - Ref: ORD-001 │ [Acciones]   │
│ [✓] ID: 124 - Ref: ORD-002 │ [Acciones]   │
│ [✓] ID: 125 - Ref: ORD-003 │ [Acciones]   │
│ [ ] ID: 126 - Ref: ORD-004 │ [Acciones]   │
│ [✓] ID: 127 - Ref: ORD-005 │ [Acciones]   │
└─────────────────────────────────────────────┘
```

#### 2. Modal de Configuración Masiva

```
┌─────────────────────────────────────────────────┐
│  Procesar 5 Transacciones Seleccionadas        │
├─────────────────────────────────────────────────┤
│                                                 │
│  Usuario: [Juan Pérez (juan@example.com) ▼]   │
│  Módulo:  [MaxGym ▼]                           │
│  API:     [Crear Venta MaxGym ▼]               │
│                                                 │
│  ┌─ Configuración de Fechas ──────────────┐   │
│  │                                         │   │
│  │  (•) Usar fecha original de transacción │   │
│  │  ( ) Usar fecha actual (2026-02-07)     │   │
│  │  ( ) Usar fechas personalizadas:        │   │
│  │                                         │   │
│  │      Date:    [YYYY-MM-DD]          │   │
│  │      DueDate: [YYYY-MM-DD]          │   │
│  │                                         │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  [Cancelar]  [Procesar Transacciones →]       │
└─────────────────────────────────────────────────┘
```

#### 3. Barra de Progreso en Tiempo Real

```
┌─────────────────────────────────────────────────┐
│  Procesando Transacciones...                    │
├─────────────────────────────────────────────────┤
│                                                 │
│  Progreso: 3 / 5 (60%)                         │
│  ▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░ 60%                      │
│                                                 │
│  ORD-001: Exitoso                           │
│  ORD-002: Exitoso                           │
│  ORD-003: Procesando...                     │
│   ORD-004: Pendiente                         │
│   ORD-005: Pendiente                         │
│                                                 │
│  Tiempo estimado: 12 segundos                  │
└─────────────────────────────────────────────────┘
```

#### 4. Reporte de Resultados

```
┌─────────────────────────────────────────────────┐
│  Resultados del Procesamiento                   │
├─────────────────────────────────────────────────┤
│                                                 │
│  Total procesadas: 5                           │
│  Exitosas: 4 (80%)                          │
│  Fallidas: 1 (20%)                          │
│                                                 │
│  Detalle:                                      │
│  ORD-001 - Exitoso                          │
│  ORD-002 - Exitoso                          │
│  ORD-003 - Exitoso                          │
│  ORD-004 - Error: Invalid token             │
│  ORD-005 - Exitoso                          │
│                                                 │
│  [Descargar Reporte] [Cerrar]                 │
└─────────────────────────────────────────────────┘
```

---

## Arquitectura de la Solución

### Diagrama de Flujo

```
┌──────────────────────┐
│ Usuario Selecciona   │
│ Transacciones        │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Valida Selección     │
│ (Máx. 50)            │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Abre Modal Config    │
│ - Usuario            │
│ - Módulo/API         │
│ - Fechas             │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Genera Token Único   │
│ Sanctum (30 min)     │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Valida Token con     │
│ /api/user            │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Procesa Secuencial   │
│ cada transacción     │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Para cada una:       │
│ 1. Preparar data     │
│ 2. Aplicar fechas    │
│ 3. POST a API        │
│ 4. Actualizar estado │
│ 5. Emitir progreso   │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Limpia Token Único   │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Muestra Reporte      │
│ Final                │
└──────────────────────┘
```

### Componentes a Modificar

#### Backend (PHP/Livewire)

**Archivo:** `app/Http/Livewire/Admin/Transactions/Manage.php`

```php
// Nuevas properties
public $selectedTransactions = [];           // IDs seleccionados
public $selectAll = false;                   // Checkbox "Seleccionar todo"
public $showBulkModal = false;               // Modal procesamiento masivo
public $bulkProgress = [];                   // Progreso de cada transacción
public $bulkResults = [];                    // Resultados finales
public $showBulkResults = false;            // Modal de resultados

// Configuración de fechas
public $dateMode = 'original';              // 'original', 'current', 'custom'
public $customDate = null;                   // Fecha personalizada
public $customDueDate = null;               // Fecha vencimiento personalizada

// Límites
const MAX_BULK_TRANSACTIONS = 50;           // Límite de selección
```

#### Frontend (Blade/Alpine.js)

**Archivo:** `resources/views/livewire/admin/transactions/manage.blade.php`

```blade
<!-- Checkbox por fila -->
<td>
    <input type="checkbox" 
           wire:model="selectedTransactions" 
           value="{{ $transaction->id }}"
           @if(count($selectedTransactions) >= {{ MAX_BULK_TRANSACTIONS }} && !in_array($transaction->id, $selectedTransactions))
               disabled
           @endif>
</td>

<!-- Contador de seleccionados -->
<div x-data="{ count: @entangle('selectedTransactions').length }">
    <span x-text="count + ' seleccionada(s)'"></span>
</div>
```

---

## Mapeo de Fechas por Módulo

Análisis de cómo cada módulo utiliza los campos `Date` y `DueDate` en `SalesHeaderImp`:

### 1. MaxGym

**Servicio:** `app/Services/MaxgymService.php`

```php
// Línea 642-643
'Date' => $this->getOriginalDateFormat($paymentDate, 'Y-m-d H:i:s'),
'DueDate' => $this->getOriginalDateFormat($paymentDate, 'Y-m-d H:i:s'),
```

**Atributos usados:**
- `Date`: `paymentDate` del request
- `DueDate`: Misma que `paymentDate`

**Formato esperado:** `Y-m-d H:i:s`

### 2. Kart21

**Servicio:** `app/Services/Kart21Service.php`

```php
// Línea 321-322
'Date' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
'DueDate' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
```

**Atributos usados:**
- `Date`: Variable `$date` del request
- `DueDate`: Misma que `$date`

**Formato esperado:** `Y-m-d H:i:s`

### 3. Shopify

**Servicio:** `app/Services/ShopifyService.php`

```php
// Línea 295-296
'Date' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
'DueDate' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
```

**Atributos usados:**
- `Date`: Fecha de la orden Shopify
- `DueDate`: Misma que `$date`

**Formato esperado:** `Y-m-d H:i:s`

### 4. Lightspeed

**Servicio:** `app/Services/LightspeedService.php`

```php
// Línea 874-875
'Date' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
'DueDate' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
```

**Atributos usados:**
- `Date`: Fecha del sale
- `DueDate`: Misma que `$date`

**Formato esperado:** `Y-m-d H:i:s`

### 5. Meypar

**Servicio:** `app/Services/MeyparService.php`

```php
// Línea 262-263
'Date' => $fecha,
'DueDate' => $fecha,
```

**Atributos usados:**
- `Date`: Variable `$fecha` del request
- `DueDate`: Misma que `$fecha`

**Formato esperado:** Según formato del request

### 6. QuickBooks

**Servicio:** `app/Services/QuickBooksOnlineService.php`

```php
// Línea 944-945
'Date' => $date,
'DueDate' => $date, // Assuming due date is the same as invoice date
```

**Atributos usados:**
- `Date`: Fecha de la factura QuickBooks
- `DueDate`: Misma (según comentario: asunción)

**Formato esperado:** Según especificación QuickBooks

### 7. ACI Cloud

**Servicio:** `app/Services/ACIcloudService.php`

```php
// Línea 534, 1043, 1591
'DueDate' => $request->get('DueDate'),

// Línea 1324
'DueDate' => $this->getOriginalDateFormat($date, 'Y-m-d H:i:s'),
```

**Atributos usados:**
- `Date`: Del request directamente
- `DueDate`: Campo separado del request o misma fecha

**Formato esperado:** `Y-m-d H:i:s` o del request

### Resumen de Estrategia de Fechas

| Módulo | Date Origen | DueDate Origen | Notas |
|--------|-------------|----------------|-------|
| MaxGym | `paymentDate` | = `Date` | Misma fecha |
| Kart21 | `date` | = `Date` | Misma fecha |
| Shopify | Orden de Shopify | = `Date` | Misma fecha |
| Lightspeed | Sale date | = `Date` | Misma fecha |
| Meypar | `fecha` request | = `Date` | Misma fecha |
| QuickBooks | Invoice date | = `Date` | Asunción explícita |
| ACI Cloud | Request o generada | Request/Date | Puede ser diferente |

**Conclusión:** La mayoría de módulos usa la misma fecha para `Date` y `DueDate`. Solo ACI Cloud permite fechas diferentes explícitamente.

---

## 🛠️ Especificaciones Técnicas

### Nuevas Properties del Componente

```php
// app/Http/Livewire/Admin/Transactions/Manage.php

class Manage extends Component
{
    // ... properties existentes ...
    
    // ========== NUEVAS PROPERTIES PARA BULK ==========
    
    /**
     * IDs de transacciones seleccionadas
     * @var array
     */
    public $selectedTransactions = [];
    
    /**
     * Estado del checkbox "Seleccionar todo"
     * @var bool
     */
    public $selectAll = false;
    
    /**
     * Mostrar modal de procesamiento masivo
     * @var bool
     */
    public $showBulkModal = false;
    
    /**
     * Mostrar modal de resultados
     * @var bool
     */
    public $showBulkResults = false;
    
    /**
     * Progreso de procesamiento masivo
     * Estructura: ['transaction_id' => ['status' => 'processing', 'message' => '...']]
     * @var array
     */
    public $bulkProgress = [];
    
    /**
     * Resultados finales del procesamiento masivo
     * @var array
     */
    public $bulkResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    // ========== CONFIGURACIÓN DE FECHAS ==========
    
    /**
     * Modo de fecha: 'original', 'current', 'custom'
     * @var string
     */
    public $dateMode = 'original';
    
    /**
     * Fecha personalizada (Date)
     * @var string|null
     */
    public $customDate = null;
    
    /**
     * Fecha personalizada de vencimiento (DueDate)
     * @var string|null
     */
    public $customDueDate = null;
    
    /**
     * Límite máximo de transacciones para procesamiento masivo
     */
    const MAX_BULK_TRANSACTIONS = 50;
    
    // ... resto del código existente ...
}
```

### Nuevas Reglas de Validación

```php
/**
 * Reglas de validación para procesamiento masivo
 */
protected function getBulkRules(): array
{
    $rules = [
        'selectedTransactions' => [
            'required',
            'array',
            'min:1',
            'max:' . self::MAX_BULK_TRANSACTIONS
        ],
        'selectedTransactions.*' => 'exists:transactions,id',
        'selectedModule' => 'required|string',
        'selectedApi' => 'required|string',
        'selectedUser' => 'required|integer|exists:users,id',
        'dateMode' => 'required|in:original,current,custom'
    ];
    
    // Validación condicional para fechas personalizadas
    if ($this->dateMode === 'custom') {
        $rules['customDate'] = 'required|date_format:Y-m-d';
        $rules['customDueDate'] = 'required|date_format:Y-m-d';
    }
    
    return $rules;
}

/**
 * Mensajes de validación personalizados para bulk
 */
protected function getBulkMessages(): array
{
    return [
        'selectedTransactions.required' => 'Debe seleccionar al menos una transacción',
        'selectedTransactions.min' => 'Debe seleccionar al menos una transacción',
        'selectedTransactions.max' => 'No puede seleccionar más de ' . self::MAX_BULK_TRANSACTIONS . ' transacciones',
        'customDate.required' => 'La fecha es requerida cuando usa fechas personalizadas',
        'customDate.date_format' => 'La fecha debe estar en formato YYYY-MM-DD',
        'customDueDate.required' => 'La fecha de vencimiento es requerida cuando usa fechas personalizadas',
        'customDueDate.date_format' => 'La fecha de vencimiento debe estar en formato YYYY-MM-DD'
    ];
}
```

---

## 💻 Implementación Detallada

### Parte 1: Selección Múltiple

#### Backend - Métodos de Selección

```php
/**
 * Alternar selección individual
 */
public function toggleSelection($transactionId)
{
    if (in_array($transactionId, $this->selectedTransactions)) {
        // Deseleccionar
        $this->selectedTransactions = array_diff($this->selectedTransactions, [$transactionId]);
    } else {
        // Seleccionar si no se ha alcanzado el límite
        if (count($this->selectedTransactions) < self::MAX_BULK_TRANSACTIONS) {
            $this->selectedTransactions[] = $transactionId;
        } else {
            $this->dispatchBrowserEvent('showToast', [
                'type' => 'warning',
                'message' => 'Límite de ' . self::MAX_BULK_TRANSACTIONS . ' transacciones alcanzado'
            ]);
        }
    }
    
    $this->updateSelectAllState();
}

/**
 * Seleccionar todas las transacciones visibles
 */
public function selectAllVisible()
{
    $visibleIds = $this->transactions->pluck('id')->toArray();
    $availableSlots = self::MAX_BULK_TRANSACTIONS - count($this->selectedTransactions);
    
    if ($availableSlots <= 0) {
        $this->dispatchBrowserEvent('showToast', [
            'type' => 'warning',
            'message' => 'Límite de selección alcanzado'
        ]);
        return;
    }
    
    // Agregar solo las que caben
    $toAdd = array_slice(
        array_diff($visibleIds, $this->selectedTransactions),
        0,
        $availableSlots
    );
    
    $this->selectedTransactions = array_merge($this->selectedTransactions, $toAdd);
    $this->updateSelectAllState();
}

/**
 * Deseleccionar todas
 */
public function deselectAll()
{
    $this->selectedTransactions = [];
    $this->selectAll = false;
}

/**
 * Actualizar estado del checkbox "Seleccionar todo"
 */
private function updateSelectAllState()
{
    $visibleIds = $this->transactions->pluck('id')->toArray();
    $selectedVisibleIds = array_intersect($this->selectedTransactions, $visibleIds);
    $this->selectAll = count($selectedVisibleIds) === count($visibleIds) && count($visibleIds) > 0;
}

/**
 * Listener para actualizar estado cuando cambia la paginación
 */
public function updatedPage()
{
    $this->updateSelectAllState();
}
```

#### Frontend - Checkboxes

```blade
<!-- Checkbox en header de tabla -->
<thead>
    <tr>
        <th style="width: 40px;">
            <input type="checkbox" 
                   wire:model="selectAll"
                   wire:click="selectAll ? deselectAll() : selectAllVisible()"
                   @if(count($selectedTransactions) >= {{ Manage::MAX_BULK_TRANSACTIONS }})
                       disabled
                       title="Límite de selección alcanzado"
                   @endif>
        </th>
        <th>{{ __('ID') }}</th>
        <th>{{ __('Organización') }}</th>
        <!-- ... resto de headers ... -->
    </tr>
</thead>

<!-- Checkbox por cada fila -->
<tbody>
    @forelse($transactions as $transaction)
        <tr class="{{ in_array($transaction->id, $selectedTransactions) ? 'table-active' : '' }}">
            <td>
                <input type="checkbox"
                       wire:model="selectedTransactions"
                       value="{{ $transaction->id }}"
                       @if(count($selectedTransactions) >= {{ Manage::MAX_BULK_TRANSACTIONS }} && !in_array($transaction->id, $selectedTransactions))
                           disabled
                           title="Límite máximo de selección alcanzado"
                       @endif>
            </td>
            <td>{{ $transaction->id }}</td>
            <!-- ... resto de columnas ... -->
        </tr>
    @empty
        <!-- Sin transacciones -->
    @endforelse
</tbody>
```

#### Frontend - Barra de Acciones Masivas

```blade
<!-- Barra flotante con acciones masivas -->
@if(count($selectedTransactions) > 0)
    <div class="bulk-actions-bar" 
         style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); 
                background: white; padding: 15px 30px; border-radius: 8px; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1040;">
        <div class="d-flex align-items-center">
            <div class="mr-3">
                <strong>{{ count($selectedTransactions) }}</strong> transacción(es) seleccionada(s)
                @if(count($selectedTransactions) >= {{ Manage::MAX_BULK_TRANSACTIONS }})
                    <span class="badge badge-warning ml-2">Límite alcanzado</span>
                @endif
            </div>
            
            <button wire:click="openBulkModal" 
                    class="btn btn-success mr-2">
                <i class="fas fa-paper-plane"></i> 
                Procesar Seleccionadas
            </button>
            
            <button wire:click="deselectAll" 
                    class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> 
                Cancelar
            </button>
        </div>
    </div>
@endif
```

### Parte 2: Modal de Configuración Masiva

#### Backend - Abrir Modal

```php
/**
 * Abrir modal de configuración de procesamiento masivo
 */
public function openBulkModal()
{
    // Validar que hay transacciones seleccionadas
    if (empty($this->selectedTransactions)) {
        $this->dispatchBrowserEvent('showToast', [
            'type' => 'warning',
            'message' => 'No hay transacciones seleccionadas'
        ]);
        return;
    }
    
    // Validar límite
    if (count($this->selectedTransactions) > self::MAX_BULK_TRANSACTIONS) {
        $this->dispatchBrowserEvent('showToast', [
            'type' => 'error',
            'message' => 'Límite de ' . self::MAX_BULK_TRANSACTIONS . ' transacciones excedido'
        ]);
        return;
    }
    
    // Obtener primera transacción para configuración por defecto
    $firstTransaction = Transaction::with('organization')->find($this->selectedTransactions[0]);
    
    if (!$firstTransaction) {
        $this->dispatchBrowserEvent('showToast', [
            'type' => 'error',
            'message' => 'Error al cargar transacciones'
        ]);
        return;
    }
    
    // Configurar contexto para el modal
    $this->selectedTransaction = $firstTransaction;
    
    // Limpiar tokens antiguos
    if ($firstTransaction->organization_id) {
        $this->cleanUpOldSingleUseTokens($firstTransaction->organization_id);
    }
    
    // Cargar usuarios
    $this->loadAvailableUsers();
    
    // Resetear formulario
    $this->resetBulkForm();
    
    // Abrir modal
    $this->showBulkModal = true;
}

/**
 * Resetear formulario de procesamiento masivo
 */
private function resetBulkForm()
{
    $this->selectedModule = '';
    $this->selectedApi = '';
    $this->selectedUser = '';
    $this->availableApis = [];
    $this->dateMode = 'original';
    $this->customDate = null;
    $this->customDueDate = null;
    $this->bulkProgress = [];
    $this->bulkResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
}
```

#### Frontend - Modal

```blade
<!-- Modal de Procesamiento Masivo -->
@if($showBulkModal && $selectedTransaction)
    <div class="modal fade show" tabindex="-1" 
         style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks"></i> 
                        {{ __('Procesar') }} {{ count($selectedTransactions) }} {{ __('Transacciones') }}
                    </h5>
                    <button type="button" wire:click="closeModals" class="close">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- Info de organización -->
                    <div class="alert alert-info">
                        <strong>{{ __('Organización:') }}</strong> 
                        {{ $selectedTransaction->organization->nombre ?? 'N/A' }}<br>
                        <strong>{{ __('Transacciones:') }}</strong> 
                        {{ count($selectedTransactions) }}
                    </div>
                    
                    <form wire:submit.prevent="executeBulkRetry">
                        <!-- Usuario -->
                        <div class="form-group">
                            <label for="bulkUser">
                                <i class="fas fa-user"></i> {{ __('Usuario') }}
                            </label>
                            <select wire:model="selectedUser" 
                                    id="bulkUser" 
                                    class="form-control @error('selectedUser') is-invalid @enderror">
                                <option value="">{{ __('Seleccionar usuario...') }}</option>
                                @foreach($availableUsers as $userId => $userName)
                                    <option value="{{ $userId }}">{{ $userName }}</option>
                                @endforeach
                            </select>
                            @error('selectedUser')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Módulo -->
                        <div class="form-group">
                            <label for="bulkModule">
                                <i class="fas fa-cube"></i> {{ __('Módulo') }}
                            </label>
                            <select wire:model="selectedModule"
                                    id="bulkModule" 
                                    class="form-control @error('selectedModule') is-invalid @enderror">
                                <option value="">{{ __('Seleccionar módulo...') }}</option>
                                @foreach($modules as $key => $module)
                                    <option value="{{ $key }}">{{ $module['name'] }}</option>
                                @endforeach
                            </select>
                            @error('selectedModule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- API -->
                        @if($selectedModule && count($availableApis) > 0)
                            <div class="form-group">
                                <label for="bulkApi">
                                    <i class="fas fa-plug"></i> {{ __('API Endpoint') }}
                                </label>
                                <select wire:model="selectedApi" 
                                        id="bulkApi"
                                        class="form-control @error('selectedApi') is-invalid @enderror">
                                    <option value="">{{ __('Seleccionar API...') }}</option>
                                    @foreach($availableApis as $endpoint => $name)
                                        <option value="{{ $endpoint }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedApi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                        
                        <!-- Configuración de Fechas -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-calendar-alt"></i> 
                                {{ __('Configuración de Fechas') }}
                            </div>
                            <div class="card-body">
                                <!-- Opción: Fecha Original -->
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" 
                                           id="dateModeOriginal" 
                                           wire:model="dateMode"
                                           value="original"
                                           class="custom-control-input">
                                    <label class="custom-control-label" for="dateModeOriginal">
                                        <strong>{{ __('Usar fecha original de la transacción') }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Mantiene las fechas Date y DueDate tal como están en los datos guardados
                                        </small>
                                    </label>
                                </div>
                                
                                <!-- Opción: Fecha Actual -->
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" 
                                           id="dateModeCurrent" 
                                           wire:model="dateMode"
                                           value="current"
                                           class="custom-control-input">
                                    <label class="custom-control-label" for="dateModeCurrent">
                                        <strong>{{ __('Usar fecha actual') }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Fecha: {{ now()->format('Y-m-d H:i:s') }} (Date y DueDate iguales)
                                        </small>
                                    </label>
                                </div>
                                
                                <!-- Opción: Fechas Personalizadas -->
                                <div class="custom-control custom-radio mb-3">
                                    <input type="radio" 
                                           id="dateModeCustom" 
                                           wire:model="dateMode"
                                           value="custom"
                                           class="custom-control-input">
                                    <label class="custom-control-label" for="dateModeCustom">
                                        <strong>{{ __('Usar fechas personalizadas') }}</strong>
                                    </label>
                                </div>
                                
                                <!-- Campos de fecha personalizada -->
                                @if($dateMode === 'custom')
                                    <div class="ml-4 pl-3 border-left">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="customDate">
                                                        <i class="fas fa-calendar"></i> Date
                                                    </label>
                                                    <input type="date" 
                                                           wire:model="customDate"
                                                           id="customDate"
                                                           class="form-control @error('customDate') is-invalid @enderror">
                                                    @error('customDate')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="customDueDate">
                                                        <i class="fas fa-calendar-check"></i> DueDate
                                                    </label>
                                                    <input type="date" 
                                                           wire:model="customDueDate"
                                                           id="customDueDate"
                                                           class="form-control @error('customDueDate') is-invalid @enderror">
                                                    @error('customDueDate')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <button type="button"
                            wire:click="closeModals"
                            wire:loading.attr="disabled"
                            wire:target="executeBulkRetry"
                            class="btn btn-secondary">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="button"
                            wire:click="executeBulkRetry"
                            wire:loading.attr="disabled"
                            wire:target="executeBulkRetry"
                            class="btn btn-success"
                            @if(!$selectedUser || !$selectedModule || !$selectedApi || ($dateMode === 'custom' && (!$customDate || !$customDueDate)))
                                disabled
                            @endif>
                        <span wire:loading.remove wire:target="executeBulkRetry">
                            <i class="fas fa-tasks"></i> 
                            {{ __('Procesar') }} {{ count($selectedTransactions) }} {{ __('Transacciones') }}
                        </span>
                        <span wire:loading wire:target="executeBulkRetry">
                            <i class="fas fa-spinner fa-spin"></i> 
                            {{ __('Iniciando...') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
```

### Parte 3: Procesamiento Masivo

#### Backend - Ejecutar Procesamiento

```php
/**
 * Ejecutar procesamiento masivo de transacciones
 */
public function executeBulkRetry()
{
    // Validar con reglas específicas de bulk
    $this->validate($this->getBulkRules(), $this->getBulkMessages());
    
    // Inicializar resultados
    $this->bulkResults = [
        'total' => count($this->selectedTransactions),
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    try {
        // Generar token único para todas las transacciones
        Log::info("=== INICIANDO PROCESAMIENTO MASIVO ===");
        Log::info("Transacciones: " . count($this->selectedTransactions));
        Log::info("Usuario: {$this->selectedUser}");
        Log::info("Módulo: {$this->selectedModule}");
        Log::info("API: {$this->selectedApi}");
        Log::info("Modo de fecha: {$this->dateMode}");
        
        $singleUseToken = $this->generateSingleUseToken(
            $this->selectedUser,
            $this->selectedTransaction->organization_id
        );
        
        if (!$singleUseToken) {
            throw new \Exception('No se pudo generar token de autenticación');
        }
        
        // Validar token
        if (!$this->testTokenWithAPI($singleUseToken)) {
            throw new \Exception('Token de autenticación inválido');
        }
        
        Log::info("Token validado exitosamente");
        
        // Cerrar modal de configuración y abrir modal de progreso
        $this->showBulkModal = false;
        $this->showBulkResults = false;
        
        // Procesar cada transacción
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
        $apiUrl = $baseUrl . $this->selectedApi;
        
        foreach ($this->selectedTransactions as $index => $transactionId) {
            $this->processSingleTransaction(
                $transactionId,
                $singleUseToken,
                $apiUrl,
                $index + 1
            );
        }
        
        // Limpiar token
        $this->cleanUpSingleUseTokens();
        
        // Mostrar resultados
        $this->showBulkResults = true;
        
        // Toast de resumen
        $successRate = round(($this->bulkResults['success'] / $this->bulkResults['total']) * 100);
        $this->dispatchBrowserEvent('showToast', [
            'type' => $successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'error'),
            'message' => "Procesamiento completado: {$this->bulkResults['success']}/{$this->bulkResults['total']} exitosas ({$successRate}%)"
        ]);
        
        // Limpiar selección
        $this->selectedTransactions = [];
        $this->selectAll = false;
        
    } catch (\Exception $e) {
        Log::error('Error en procesamiento masivo: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        $this->dispatchBrowserEvent('showToast', [
            'type' => 'error',
            'message' => 'Error en procesamiento masivo: ' . $e->getMessage()
        ]);
        
        // Limpiar token en caso de error
        $this->cleanUpSingleUseTokens();
    }
}

/**
 * Procesar una sola transacción dentro del lote
 */
private function processSingleTransaction($transactionId, $token, $apiUrl, $currentNumber)
{
    try {
        // Obtener transacción
        $transaction = Transaction::with('organization')->find($transactionId);
        
        if (!$transaction) {
            throw new \Exception("Transacción {$transactionId} no encontrada");
        }
        
        // Inicializar progreso
        $this->bulkProgress[$transactionId] = [
            'status' => 'processing',
            'message' => 'Procesando...',
            'current' => $currentNumber,
            'total' => $this->bulkResults['total']
        ];
        
        // Emitir evento de progreso
        $this->emit('bulkProgressUpdate');
        
        Log::info("Procesando transacción {$currentNumber}/{$this->bulkResults['total']}: {$transaction->reference}");
        
        // Preparar datos
        $data = $this->prepareTransactionData($transaction);
        
        // Aplicar configuración de fechas
        $data = $this->applyDateConfiguration($data);
        
        // Limpiar UTF-8
        $data = $this->cleanUtf8Array($data);
        
        // Realizar llamada HTTP
        $response = Http::timeout(60)
            ->connectTimeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'X-Organization-Id' => $transaction->organization_id
            ])
            ->post($apiUrl, $data);
        
        // Procesar respuesta
        $success = $response->successful();
        
        // Actualizar transacción
        $transaction->update([
            'api_endpoint' => $this->selectedApi,
            'module_type' => $this->selectedModule,
            'attempts' => ($transaction->attempts ?? 0) + 1,
            'last_attempt_at' => now(),
            'status' => $success ? 'success' : 'failed',
            'error_message' => $success ? null : $response->body()
        ]);
        
        // Actualizar progreso
        $this->bulkProgress[$transactionId] = [
            'status' => $success ? 'success' : 'failed',
            'message' => $success ? 'Exitoso' : 'Error: ' . substr($response->body(), 0, 100),
            'current' => $currentNumber,
            'total' => $this->bulkResults['total']
        ];
        
        // Actualizar resultados
        if ($success) {
            $this->bulkResults['success']++;
        } else {
            $this->bulkResults['failed']++;
        }
        
        $this->bulkResults['details'][] = [
            'id' => $transaction->id,
            'reference' => $transaction->reference,
            'status' => $success ? 'success' : 'failed',
            'message' => $success ? 'Procesado exitosamente' : substr($response->body(), 0, 200),
            'http_status' => $response->status()
        ];
        
        Log::info("Transacción {$transaction->reference}: " . ($success ? 'EXITOSO' : 'FALLIDO'));
        
        // Pequeño delay entre transacciones para no sobrecargar
        usleep(100000); // 0.1 segundos
        
    } catch (\Exception $e) {
        Log::error("Error procesando transacción {$transactionId}: " . $e->getMessage());
        
        $this->bulkProgress[$transactionId] = [
            'status' => 'failed',
            'message' => 'Error: ' . $e->getMessage(),
            'current' => $currentNumber,
            'total' => $this->bulkResults['total']
        ];
        
        $this->bulkResults['failed']++;
        $this->bulkResults['details'][] = [
            'id' => $transactionId,
            'reference' => $transaction->reference ?? 'N/A',
            'status' => 'failed',
            'message' => $e->getMessage(),
            'http_status' => null
        ];
    }
}

/**
 * Preparar datos de la transacción
 */
private function prepareTransactionData($transaction): array
{
    $data = $transaction->data;
    
    // Asegurar que sea array
    if (is_string($data)) {
        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        $decoded = json_decode($data, true);
        $data = $decoded ?? [];
    } elseif (!is_array($data)) {
        $data = [];
    }
    
    return $data;
}

/**
 * Aplicar configuración de fechas según el modo seleccionado
 */
private function applyDateConfiguration(array $data): array
{
    switch ($this->dateMode) {
        case 'original':
            // No hacer nada, mantener fechas originales
            break;
            
        case 'current':
            // Usar fecha actual
            $currentDate = now()->format('Y-m-d H:i:s');
            $data = $this->injectDatesIntoData($data, $currentDate, $currentDate);
            break;
            
        case 'custom':
            // Usar fechas personalizadas
            if ($this->customDate && $this->customDueDate) {
                // Convertir a formato con hora
                $date = $this->customDate . ' 00:00:00';
                $dueDate = $this->customDueDate . ' 00:00:00';
                $data = $this->injectDatesIntoData($data, $date, $dueDate);
            }
            break;
    }
    
    return $data;
}

/**
 * Inyectar fechas en la estructura de datos según el módulo
 */
private function injectDatesIntoData(array $data, string $date, string $dueDate): array
{
    // Dependiendo del módulo, las fechas pueden estar en diferentes lugares
    switch ($this->selectedModule) {
        case 'maxgym':
            // MaxGym usa 'paymentDate'
            $data['paymentDate'] = $date;
            break;
            
        case 'kart21':
        case 'shopify':
        case 'lightspeed':
            // Estos usan 'date' directamente
            $data['date'] = $date;
            break;
            
        case 'meypar':
            // Meypar usa 'fecha'
            $data['fecha'] = $date;
            break;
            
        case 'acicloud':
            // ACI Cloud puede tener Date y DueDate separados
            $data['Date'] = $date;
            $data['DueDate'] = $dueDate;
            break;
            
        case 'quickbooks':
            // QuickBooks puede variar según el objeto
            $data['TxnDate'] = $date; // Formato QuickBooks
            break;
            
        default:
            // Fallback genérico
            $data['date'] = $date;
            $data['Date'] = $date;
            $data['DueDate'] = $dueDate;
            break;
    }
    
    return $data;
}
```

### Parte 4: Modal de Resultados

#### Frontend - Modal de Resultados

```blade
<!-- Modal de Resultados del Procesamiento Masivo -->
@if($showBulkResults)
    <div class="modal fade show" tabindex="-1" 
         style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-pie"></i> 
                        {{ __('Resultados del Procesamiento') }}
                    </h5>
                    <button type="button" wire:click="closeBulkResults" class="close">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- Resumen con gráfico -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="mb-0">{{ $bulkResults['total'] }}</h3>
                                    <small class="text-muted">Total Procesadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center bg-success text-white">
                                <div class="card-body">
                                    <h3 class="mb-0">{{ $bulkResults['success'] }}</h3>
                                    <small>
                                        Exitosas 
                                        ({{ $bulkResults['total'] > 0 ? round(($bulkResults['success'] / $bulkResults['total']) * 100) : 0 }}%)
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center bg-danger text-white">
                                <div class="card-body">
                                    <h3 class="mb-0">{{ $bulkResults['failed'] }}</h3>
                                    <small>
                                        Fallidas 
                                        ({{ $bulkResults['total'] > 0 ? round(($bulkResults['failed'] / $bulkResults['total']) * 100) : 0 }}%)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barra de progreso visual -->
                    <div class="progress mb-4" style="height: 30px;">
                        @php
                            $successPercent = $bulkResults['total'] > 0 
                                ? ($bulkResults['success'] / $bulkResults['total']) * 100 
                                : 0;
                            $failedPercent = $bulkResults['total'] > 0 
                                ? ($bulkResults['failed'] / $bulkResults['total']) * 100 
                                : 0;
                        @endphp
                        
                        <div class="progress-bar bg-success" 
                             style="width: {{ $successPercent }}%">
                            {{ round($successPercent) }}%
                        </div>
                        <div class="progress-bar bg-danger" 
                             style="width: {{ $failedPercent }}%">
                            {{ round($failedPercent) }}%
                        </div>
                    </div>
                    
                    <!-- Detalle de cada transacción -->
                    <h6 class="mb-3">
                        <i class="fas fa-list"></i> {{ __('Detalle de Transacciones') }}
                    </h6>
                    
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Referencia') }}</th>
                                    <th>{{ __('Estado') }}</th>
                                    <th>{{ __('Mensaje') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bulkResults['details'] as $detail)
                                    <tr>
                                        <td>{{ $detail['id'] }}</td>
                                        <td>
                                            <strong>{{ $detail['reference'] }}</strong>
                                        </td>
                                        <td>
                                            @if($detail['status'] === 'success')
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Exitoso
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times"></i> Fallido
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $detail['message'] }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" 
                            wire:click="exportBulkResults" 
                            class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> 
                        {{ __('Descargar Reporte') }}
                    </button>
                    <button type="button" 
                            wire:click="closeBulkResults" 
                            class="btn btn-secondary">
                        {{ __('Cerrar') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
```

#### Backend - Cerrar y Exportar

```php
/**
 * Cerrar modal de resultados
 */
public function closeBulkResults()
{
    $this->showBulkResults = false;
    $this->bulkProgress = [];
    $this->bulkResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
}

/**
 * Exportar resultados a CSV
 */
public function exportBulkResults()
{
    try {
        $filename = 'bulk_transaction_results_' . now()->format('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/public/exports/' . $filename);
        
        // Crear directorio si no existe
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $file = fopen($filepath, 'w');
        
        // Headers
        fputcsv($file, [
            'ID',
            'Referencia',
            'Estado',
            'Mensaje',
            'HTTP Status',
            'Fecha Proceso'
        ]);
        
        // Datos
        foreach ($this->bulkResults['details'] as $detail) {
            fputcsv($file, [
                $detail['id'],
                $detail['reference'],
                $detail['status'],
                $detail['message'],
                $detail['http_status'] ?? 'N/A',
                now()->format('Y-m-d H:i:s')
            ]);
        }
        
        fclose($file);
        
        // Descargar archivo
        return response()->download($filepath)->deleteFileAfterSend(true);
        
    } catch (\Exception $e) {
        Log::error('Error exportando resultados: ' . $e->getMessage());
        
        $this->dispatchBrowserEvent('showToast', [
            'type' => 'error',
            'message' => 'Error al exportar resultados'
        ]);
    }
}
```

---

## 📖 Casos de Uso

### Caso 1: Reprocesar Transacciones Fallidas con Fecha Actual

**Escenario:**
Un lote de 20 transacciones de MaxGym falló ayer por un problema temporal en el PAC. Hoy se necesita reprocesarlas con la fecha actual.

**Pasos:**
1. Filtrar transacciones con estado `failed` y módulo `maxgym`
2. Seleccionar las 20 transacciones
3. Clic en "Procesar Seleccionadas"
4. Configurar:
   - Usuario: Usuario con token válido
   - Módulo: MaxGym
   - API: Crear Venta MaxGym
   - Fechas: **Usar fecha actual**
5. Clic en "Procesar 20 Transacciones"
6. Ver progreso en tiempo real
7. Revisar reporte de resultados

**Resultado Esperado:**
- Todas las transacciones se procesan con `Date` y `DueDate` = fecha actual
- Reporte muestra éxito/fallo de cada una
- Transacciones exitosas actualizan su estado a `success`

### Caso 2: Reenviar con Fechas Personalizadas

**Escenario:**
5 facturas de Shopify se crearon con fecha incorrecta. Necesitan ser reenviadas con una fecha específica del pasado.

**Pasos:**
1. Buscar las 5 transacciones por referencia
2. Seleccionarlas individualmente
3. Abrir modal de procesamiento masivo
4. Configurar:
   - Usuario: Usuario autorizado
   - Módulo: Shopify
   - API: Crear Venta Shopify
   - Fechas: **Usar fechas personalizadas**
     - Date: 2026-01-15
     - DueDate: 2026-01-15
5. Procesar
6. Descargar reporte CSV

**Resultado Esperado:**
- Todas las facturas se regeneran con fecha 2026-01-15
- CSV contiene detalle de cada transacción procesada

### Caso 3: Mantener Fechas Originales

**Escenario:**
30 transacciones de Lightspeed necesitan ser reenviadas al PAC por un error de conexión, pero manteniendo sus fechas originales.

**Pasos:**
1. Filtrar por módulo Lightspeed y estado `failed`
2. Seleccionar las 30 transacciones
3. Configurar procesamiento masivo:
   - Fechas: **Usar fecha original de la transacción**
4. Procesar

**Resultado Esperado:**
- Cada transacción mantiene su fecha original de los datos guardados
- Se respetan las fechas específicas de cada orden de Lightspeed

---

## 🔒 Consideraciones de Seguridad

### 1. Límite de Selección

```php
const MAX_BULK_TRANSACTIONS = 50;
```

**Razón:** Prevenir sobrecarga del servidor y timeouts de PHP.

### 2. Tokens de Un Solo Uso

- **Generación única** por sesión de procesamiento
- **Expiración automática** en 30 minutos
- **Limpieza automática** después de uso o 1 hora
- **Tipo específico** marcado como `single-use-retry`

### 3. Validación de Permisos

```php
// Verificar que el usuario tiene tokens para la organización
$userIdsWithTokens = Personalaccesstoken::where('organization_id', $organizationId)
    ->pluck('tokenable_id')
    ->unique();
```

### 4. Timeout de Conexión

```php
Http::timeout(60)       // Timeout global
    ->connectTimeout(10) // Timeout de conexión
```

### 5. Rate Limiting

- Delay de 100ms entre transacciones: `usleep(100000)`
- Prevenir bloqueo por múltiples requests simultáneos

### 6. Validación de Fechas

```php
$rules['customDate'] = 'required|date_format:Y-m-d';
$rules['customDueDate'] = 'required|date_format:Y-m-d';
```

### 7. Logging Detallado

- Log de inicio/fin de procesamiento
- Log por cada transacción procesada
- Log de errores con stack trace

---

## 🧪 Testing

### Test Unitario - Selección Múltiple

```php
/** @test */
public function puede_seleccionar_hasta_50_transacciones()
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $transactions = Transaction::factory()->count(60)->create([
        'organization_id' => $org->id
    ]);
    
    Livewire::actingAs($user)
        ->test(Manage::class)
        ->set('selectedTransactions', $transactions->take(50)->pluck('id')->toArray())
        ->assertCount('selectedTransactions', 50);
}

/** @test */
public function no_puede_seleccionar_mas_de_50_transacciones()
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $transactions = Transaction::factory()->count(60)->create([
        'organization_id' => $org->id
    ]);
    
    Livewire::actingAs($user)
        ->test(Manage::class)
        ->set('selectedTransactions', $transactions->pluck('id')->toArray())
        ->call('toggleSelection', $transactions->first()->id)
        ->assertDispatchedBrowserEvent('showToast');
}
```

### Test de Integración - Procesamiento Masivo

```php
/** @test */
public function puede_procesar_multiples_transacciones_con_fecha_actual()
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    
    // Crear token válido
    $token = $user->createToken('api-token')->plainTextToken;
    DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->update(['organization_id' => $org->id]);
    
    // Crear transacciones
    $transactions = Transaction::factory()->count(5)->create([
        'organization_id' => $org->id,
        'status' => 'failed'
    ]);
    
    // Simular API exitosa
    Http::fake([
        '*' => Http::response(['success' => true], 200)
    ]);
    
    Livewire::actingAs($user)
        ->test(Manage::class)
        ->set('selectedTransactions', $transactions->pluck('id')->toArray())
        ->call('openBulkModal')
        ->set('selectedUser', $user->id)
        ->set('selectedModule', 'maxgym')
        ->set('selectedApi', '/api/v1/fe/create_sale_maxgym')
        ->set('dateMode', 'current')
        ->call('executeBulkRetry')
        ->assertSet('bulkResults.success', 5)
        ->assertSet('bulkResults.failed', 0);
    
    // Verificar que las transacciones se actualizaron
    $transactions->each(function ($transaction) {
        $transaction->refresh();
        $this->assertEquals('success', $transaction->status);
    });
}
```

### Test de Fechas Personalizadas

```php
/** @test */
public function aplica_fechas_personalizadas_correctamente()
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;
    
    DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->update(['organization_id' => $org->id]);
    
    $transactions = Transaction::factory()->count(3)->create([
        'organization_id' => $org->id,
        'data' => ['paymentDate' => '2026-01-01 00:00:00']
    ]);
    
    $customDate = '2026-02-10';
    $customDueDate = '2026-02-20';
    
    Http::fake([
        '*' => Http::response(function ($request) use ($customDate) {
            $body = $request->body();
            $data = json_decode($body, true);
            
            // Verificar que tiene la fecha personalizada
            $this->assertStringContainsString($customDate, $data['paymentDate']);
            
            return ['success' => true];
        }, 200)
    ]);
    
    Livewire::actingAs($user)
        ->test(Manage::class)
        ->set('selectedTransactions', $transactions->pluck('id')->toArray())
        ->call('openBulkModal')
        ->set('selectedUser', $user->id)
        ->set('selectedModule', 'maxgym')
        ->set('selectedApi', '/api/v1/fe/create_sale_maxgym')
        ->set('dateMode', 'custom')
        ->set('customDate', $customDate)
        ->set('customDueDate', $customDueDate)
        ->call('executeBulkRetry')
        ->assertSet('bulkResults.success', 3);
}
```

---

## Métricas de Performance

### Tiempos Estimados

| Transacciones | Tiempo Estimado | Notas |
|---------------|-----------------|-------|
| 5 | ~10-15 segundos | 2-3s por transacción + overhead |
| 10 | ~20-30 segundos | |
| 25 | ~50-75 segundos | |
| 50 | ~100-150 segundos | Máximo recomendado |

### Limitaciones Técnicas

- **Timeout PHP:** 120 segundos (configurar en `php.ini`)
- **Timeout HTTP:** 60 segundos por llamada
- **Memoria:** ~256MB recomendado
- **Conexiones simultáneas:** Procesamiento secuencial (no paralelo)

---

## Plan de Implementación

### Fase 1: Backend (2-3 horas)
- [ ] Agregar nuevas properties al componente Manage
- [ ] Implementar métodos de selección múltiple
- [ ] Crear método `executeBulkRetry()`
- [ ] Implementar `applyDateConfiguration()`
- [ ] Agregar validaciones específicas

### Fase 2: Frontend (2-3 horas)
- [ ] Agregar checkboxes a la tabla
- [ ] Crear barra flotante de acciones masivas
- [ ] Diseñar modal de configuración masiva
- [ ] Implementar controles de fecha (radio + date pickers)
- [ ] Crear modal de progreso
- [ ] Diseñar modal de resultados

### Fase 3: Testing (1-2 horas)
- [ ] Tests unitarios de selección
- [ ] Tests de integración de procesamiento
- [ ] Tests de fechas personalizadas
- [ ] Tests de límites y validaciones

### Fase 4: Documentación y Refinamiento (1 hora)
- [ ] Actualizar documentación de usuario
- [ ] Agregar tooltips y ayudas contextuales
- [ ] Optimizar UX según feedback
- [ ] Deployment a producción

**Tiempo Total Estimado:** 6-9 horas

---

## Conclusiones

Este sistema de reintento masivo de transacciones proporciona:

**Eficiencia operativa** - Procesar hasta 50 transacciones simultáneamente  
**Flexibilidad de fechas** - Múltiples opciones según necesidad del negocio  
**Seguridad robusta** - Tokens de un solo uso y validaciones múltiples  
**Feedback en tiempo real** - Progreso y resultados detallados  
**Trazabilidad completa** - Logs detallados y reportes exportables  
**Escalabilidad** - Base sólida para implementar Jobs asíncronos en el futuro

### Próximos Pasos

1. **Jobs Asíncronos (Fase 2)**
   - Implementar `ProcessBulkTransactionsJob` con Redis
   - Sistema de notificaciones al completar
   - Cola dedicada para procesamiento masivo

2. **Mejoras UI/UX**
   - Drag & drop para selección de rango
   - Previsualización de cambios antes de procesar
   - Plantillas de configuración guardadas

3. **Analytics**
   - Dashboard de métricas de procesamiento masivo
   - Reportes históricos
   - Detección de patrones de errores

---

**Documento creado por:** Sistema DocuCenter  
**Última actualización:** 7 de febrero de 2026  
**Versión:** 1.0
