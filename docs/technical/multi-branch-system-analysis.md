# Análisis del Sistema Multi-Sucursal con ID_compania

**Fecha:** 3 de diciembre de 2025  
**Versión:** 1.0  
**Estado:** Análisis para Implementación

---

## 📋 Índice

1. [Contexto General](#contexto-general)
2. [Arquitectura Actual](#arquitectura-actual)
3. [Relaciones Clave](#relaciones-clave)
4. [Modelos con ID_compania](#modelos-con-id_compania)
5. [Problema Identificado](#problema-identificado)
6. [Soluciones Propuestas](#soluciones-propuestas)
7. [Plan de Implementación](#plan-de-implementación)

---

## Contexto General

DocuCenter maneja un sistema multi-tenant donde:
- Una **Empresa/Matriz** (CompanySession) puede tener múltiples **Organizaciones**
- Cada **Organización** tiene su propia base de datos
- Dentro de cada base de datos, existen múltiples **Sucursales** identificadas por `ID_compania`
- Una organización puede gestionar múltiples sucursales de la misma empresa

**Caso de Uso Real:**
- Empresa: "Restaurantes XYZ S.A." (CompanySession con ID_compania: 100)
  - Organización 1: "RXY Regional Centro" (BD: rest_centro)
    - Sucursal 1: Local Centro (ID_compania: 1)
    - Sucursal 2: Local Plaza (ID_compania: 2)
  - Organización 2: "RXY Regional Norte" (BD: rest_norte)
    - Sucursal 3: Local Norte (ID_compania: 3)
    - Sucursal 4: Local Costa (ID_compania: 4)
  - Organización 3: "RXY Regional Sur" (BD: rest_sur)
    - Sucursal 5: Local Sur (ID_compania: 5)

---

## Arquitectura Actual

### Estructura de Base de Datos

```
┌─────────────────────────────────────────┐
│  Base de Datos Principal (mysql)       │
├─────────────────────────────────────────┤
│  - organizations                        │
│  - users                                │
│  - user_organizations                   │
│  - CompanySession ⭐ (PRINCIPAL)        │
│    └─> Modelo Companysession lee AQUÍ  │
└─────────────────────────────────────────┘
                    │
                    │ Cada org tiene su BD
                    ▼
┌─────────────────────────────────────────┐
│  Base de Datos Organización             │
│  (ej: 9_734_1672_56)                   │
├─────────────────────────────────────────┤
│  - CompanySession (copia del stub)      │
│    └─> ID_compania (datos locales)     │
│                                         │
│  - Customers_Imp (ID_compania)          │
│  - Sales_Header_Imp (ID_compania)       │
│  - Products_Imp (ID_compania)           │
│  - ... (43+ tablas más)                 │
└─────────────────────────────────────────┘
```

**⚠️ IMPORTANTE - Dualidad de CompanySession:**

La tabla `CompanySession` existe en DOS ubicaciones:

1. **Base de Datos Principal (`mysql`):**
   - Tabla centralizada para gestión
   - **El modelo `Companysession` SIEMPRE lee de aquí**
   - Usa `setDefaultConnection()` en constructor
   - Relación: `organizations()` via `id_empresa`

2. **Base de Datos de cada Organización:**
   - Creada desde `CompanySession.sql.stub`
   - Copia local con datos específicos
   - **NO es consultada por el modelo Eloquent**
   - Posiblemente para importación/exportación de datos

```php
// app/Models/Companysession.php
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);
    
    // ⭐ SIEMPRE usa BD principal
    $this->setDefaultConnection();
}
```

### Flujo de Conexión Correcto (Propuesto)

```php
// 1. Usuario se autentica
User::find(1)

// 2. Obtiene la organización actual
→ UserOrganizations
  → Organization (id: 25, nombre: "RXY Regional Centro", database: "9_734_1672_56")

// 3. Cambia a la BD de la organización
DB::connection()->useDatabase("9_734_1672_56")

// 4. La BD contiene datos de MÚLTIPLES sucursales
→ CompanySession (registros con ID_compania: 1, 2, 3, 4, 5)
  → Customers_Imp (mezclados: ID_compania 1, 2, 3, 4, 5)
  → Sales_Header_Imp (mezclados: ID_compania 1, 2, 3, 4, 5)

// 5. PROBLEMA ACTUAL: Usuario ve TODOS los datos mezclados
// 6. SOLUCIÓN: Filtrar por las sucursales asignadas al usuario
```

---

## Relaciones Clave

### ✅ Relación CORRECTA (Flujo Propuesto)

```
CompanySession (BD Principal)
    ├─> ID_compania: 100 (Empresa/Matriz "Restaurantes XYZ S.A.")
    ├─> CompanyNameSage50: "Restaurantes XYZ S.A."
    └─> organizations() → hasMany (UNA EMPRESA = MUCHAS ORGANIZACIONES)
          ├─> Organization 1: "RXY Regional Centro" (BD: rest_centro)
          ├─> Organization 2: "RXY Regional Norte" (BD: rest_norte)
          └─> Organization 3: "RXY Regional Sur" (BD: rest_sur)

Organization (BD Principal)
    ├─> id: 25
    ├─> nombre: "RXY Regional Centro"
    ├─> database: "9_734_1672_56"
    ├─> id_empresa: 100 (FK a CompanySession - Empresa Matriz)
    └─> belongsTo(Companysession) → Empresa Matriz

Dentro de BD Organización (9_734_1672_56):
    ├─> CompanySession (UN SOLO ID_compania por organización)
    │     └─> ID_compania: 100 (la organización pertenece a esta compañía)
    ├─> CompanySession (múltiples registros)
    │     ├─> ID_compania: 1 (Sucursal Centro)
    │     ├─> ID_compania: 2 (Sucursal Plaza)
    │     └─> ID_compania: 3 (Sucursal Mall)
    │
    ├─> Customers_Imp
    │     ├─> Record 1: ID_compania = 1 (clientes de Sucursal Centro)
    │     ├─> Record 2: ID_compania = 2 (clientes de Sucursal Plaza)
    │     └─> Record 3: ID_compania = 3 (clientes de Sucursal Mall)
    │
    └─> Sales_Header_Imp (ventas mezcladas de todas las sucursales)

**IMPORTANTE:** 
- UNA EMPRESA (CompanySession en BD principal) = MUCHAS ORGANIZACIONES
- UNA ORGANIZACIÓN (con su BD) = MUCHAS SUCURSALES (ID_compania en su BD)
- Usuario debe ver solo las sucursales que le fueron asignadas
```

### 📊 Modelo de Datos Actualizado (Flujo Correcto)

```
CompanySession (BD Principal) - Empresas Matriz
  └─> ID_compania: 100 ("Restaurantes XYZ S.A.")
        └─> hasMany Organizations
              ├─> Organization 1 (id_empresa: 100, BD: rest_centro)
              ├─> Organization 2 (id_empresa: 100, BD: rest_norte)
              └─> Organization 3 (id_empresa: 100, BD: rest_sur)

User (BD Principal)
  └─> UserOrganizations (N organizaciones)
        │
        ├─> Organization 1: "RXY Regional Centro" (BD: rest_centro)
        │     ├─> id_empresa: 100 (Empresa Matriz)
        │     └─> Dentro de BD rest_centro:
        │           ├─> CompanySession: ID_compania 1, 2, 3
        │           ├─> Customers_Imp: mezclado (ID_compania 1,2,3)
        │           └─> Sales_Header_Imp: mezclado (ID_compania 1,2,3)
        │
        ├─> Organization 2: "RXY Regional Norte" (BD: rest_norte)
        │     ├─> id_empresa: 100 (Empresa Matriz)
        │     └─> Dentro de BD rest_norte:
        │           ├─> CompanySession: ID_compania 4, 5
        │           ├─> Customers_Imp: mezclado (ID_compania 4,5)
        │           └─> Sales_Header_Imp: mezclado (ID_compania 4,5)
        │
        └─> Organization 3: "RXY Regional Sur" (BD: rest_sur)
              ├─> id_empresa: 100 (Empresa Matriz)
              └─> Dentro de BD rest_sur:
                    ├─> CompanySession: ID_compania 6, 7, 8
                    ├─> Customers_Imp: mezclado (ID_compania 6,7,8)
                    └─> Sales_Header_Imp: mezclado (ID_compania 6,7,8)

**NUEVO REQUERIMIENTO:**
User debe tener asignación de sucursales específicas:
  - Juan puede ver: Org 1 (sucursales 1,2) + Org 2 (sucursal 4)
  - María puede ver: Org 1 (sucursal 3) + Org 3 (sucursales 6,7)
```

### 🔑 Relación Organización → Sucursal

**En BD Principal (`organizations`):**
```php
class Organization extends Model
{
    protected $fillable = [
        'id',           // 25
        'nombre',       // "Restaurante XYZ - Sucursal Centro"
        'database',     // "9_734_1672_56"
        'id_empresa',   // 100 → FK a CompanySession.ID_compania
    ];
    
    public function company()
    {
        // Cambia a BD específica y busca por ID_compania
        return $this->belongsTo(Companysession::class, 'id_empresa', 'ID_compania');
    }
}
```

**En BD Principal (`CompanySession`):**
```php
class Companysession extends Model
{
    protected $table = 'CompanySession';
    
    protected $fillable = [
        'ID_compania',      // 100 (identificador de sucursal)
        'CompanyNameSage50' // "Restaurante XYZ - Centro"
    ];
    
    /**
     * ⭐ SIEMPRE usa la base de datos principal
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Forzar conexión a BD principal (mysql)
        $this->setDefaultConnection();
    }
    
    public function organizations()
    {
        // UNA sucursal puede estar asociada a UNA organización
        return $this->hasMany(Organization::class, 'id_empresa', 'ID_compania');
    }
}
```

**⚠️ ACLARACIONES IMPORTANTES:**

1. **Modelo de Relaciones Correcto:**
   - **1 CompanySession (BD Principal)** = 1 Empresa Matriz
   - **1 Empresa Matriz** → hasMany **Organizaciones** (N)
   - **1 Organización** → belongsTo **Empresa Matriz** (1)
   - **1 Organización (BD propia)** → Contiene **N Sucursales** (ID_compania)

2. **Dualidad de CompanySession:**
   - **BD Principal:** CompanySession almacena las empresas matriz
     * Modelo Eloquent lee de aquí (setDefaultConnection)
     * Gestión centralizada
   - **BD de Organización:** CompanySession almacena sucursales locales
     * Creada desde stub, contiene múltiples ID_compania
     * Datos operativos de cada sucursal
     * NO consultada por modelo Eloquent Companysession

3. **Problema Actual:**
   - Usuario con acceso a una Organización ve TODAS las sucursales mezcladas
   - Falta filtrado por sucursales asignadas al usuario
   - Necesidad: Tabla intermedia user-branch o campo JSON en user_organizations

4. **Solución Propuesta:**
   - Agregar asignación de sucursales por usuario
   - Filtrar automáticamente por sucursales permitidas
   - Mantener flexibilidad para reportes consolidados

---

## Modelos con ID_compania

### 📊 Inventario de Tablas (43 tablas en total)

#### ✅ Con Filtrado Implementado (7 modelos)

| Modelo | Scope | Método |
|--------|-------|--------|
| `CustomersImp` | `AddLocalFilterItems` | `whereIn('ID_compania', $companiesIds)` |
| `VendorsImp` | `AddLocalFilterItems` | `whereIn('ID_compania', $companiesIds)` |
| `ProductsImp` | `AddLocalFilterItems` | `whereIn('ID_compania', $companiesIds)` |
| `JobsImp` | `AddLocalFilterItems` | `whereIn('ID_compania', $companiesIds)` |
| `JobsExp` | `AddLocalFilterItems` | `whereIn('ID_Compania', $companiesIds)` |
| `GJEHeaderImp` | `AddLocalFilterItems` | `whereIn('ID_compania', $companiesIds)` |
| `CustomerCreditMemoHeaderImp` | `AddLocalFilterItems` | `whereIn('ID_compania', $companiesIds)` |

**Ejemplo de implementación:**
```php
public function scopeAddLocalFilterItems(Builder $query, Request $request)
{
    $companiesIds = OrganizationFacade::getCompanyIds();
    $query->whereIn('ID_compania', $companiesIds);
    return $query;
}
```

#### ❌ Sin Filtrado (36+ modelos pendientes)

**Ventas:**
- `SalesHeaderImp`
- `SalesDetailImp`
- `SalesDetailImpDiscounts`
- `SalesOrderHeaderImp`
- `SalesOrderDetailImp`
- `SalesOrderHeaderExp`
- `SalesOrderDetailExp`
- `SalesInvoiceHeaderExp`
- `SalesInvoiceDetailExp`

**Compras:**
- `PurchaseHeaderImp`
- `PurchaseDetailImp`
- `PurchaseHeaderExp`
- `PurchaseDetailExp`
- `PurOrdrHeaderExp`
- `PurOrdrDetailExp`

**Pagos y Recibos:**
- `CustomerReceiptHeaderImp`
- `CustomerReceiptDetailImp`
- `VendorPaymentHeaderImp`
- `VendorPaymentDetailImp`

**Facturación Electrónica:**
- `FeHeader`
- `FeDetail`
- `FePayment`

**Catálogos:**
- `CustomersExp`
- `VendorsExp`
- `ProductsExp`
- `ChartExp`

**Jobs y Contabilidad:**
- `JobPhasesExp`
- `JobCostCodesExp`
- `SalesRepresentativeExp`

**Inventario:**
- `InventoryAdjustImp`

**Otros:**
- `GJEDetailImp`
- `SageConnectTransferSummary`

---

## Problema Identificado - ACTUALIZADO

### ⚠️ ACLARACIÓN IMPORTANTE

**ARQUITECTURA REAL (Confirmada):**

Cada organización tiene **UN SOLO ID_compania** en su base de datos, NO múltiples:

```
BD Organización (9_734_1672_56):
  └─> CompanySession (ID_compania: 100)  ← UN SOLO VALOR
  └─> Customers_Imp (todos con ID_compania: 100)
  └─> Sales_Header_Imp (todos con ID_compania: 100)
```

**NO es como se documentó inicialmente:**
```
❌ INCORRECTO:
BD Organización (9_734_1672_56):
  └─> CompanySession (ID_compania: 1, 2, 3)  ← Múltiples valores
  └─> Datos mezclados de varias sucursales
```

### 🎯 Problema Real: Consolidación para Sage Connector

**Limitación de Sage:**
- Sage Connector solo puede conectarse a **UNA base de datos**
- Solo puede trabajar con **UN solo ID_compania**

**Problema Actual:**
```
Compañía 100 tiene 3 organizaciones:
  - Org 1: BD rest_centro (ID_compania: 100)
  - Org 2: BD rest_norte (ID_compania: 100)
  - Org 3: BD rest_sur (ID_compania: 100)

❌ Sage NO puede acceder a las 3 BDs simultáneamente
❌ Necesita UNA BD consolidada con TODOS los datos
```

**Solución Propuesta:**
```
BD Company_100 (nueva - consolidada)
  ├─> Customers_Imp (de Org 1 + Org 2 + Org 3)
  ├─> Sales_Header_Imp (de Org 1 + Org 2 + Org 3)
  └─> org_source_id (identifica de qué org vino cada registro)

✅ Sage se conecta AQUÍ y ve TODO consolidado
```

Ver análisis completo en: [database-replication-analysis.md](./database-replication-analysis.md)

### 🚨 Escenario Problemático ANTERIOR (Descartado)

**NOTA:** Esta sección ya NO aplica porque cada org tiene UN solo ID_compania

<details>
<summary>Ver escenario anterior (solo referencia histórica)</summary>

**Situación Anterior (incorrecta):**
```
Usuario: Juan
Organizaciones asignadas:
  - Org 1: "RXY Regional Centro"  → BD: rest_centro
    * Contiene sucursales: 1, 2, 3 (ID_compania)
  - Org 2: "RXY Regional Norte"   → BD: rest_norte
    * Contiene sucursales: 4, 5 (ID_compania)

Problema asumido (NO real):
  Juan selecciona Org 1 en el header
  Sistema cambia a BD rest_centro
  Al consultar Sales_Header_Imp ve TODAS las ventas:
    - Ventas de sucursal 1 ✅
    - Ventas de sucursal 2 ✅
    - Ventas de sucursal 3 ✅
  
  Pero Juan solo debería ver:
    - Ventas de sucursal 1 (asignada)
    - Ventas de sucursal 2 (asignada)
    NO la sucursal 3 (no asignada)

Escenario Deseado:
  Juan tiene asignadas específicamente:
    - Org 1: sucursales 1, 2 (NO la 3)
    - Org 2: sucursal 4 (NO la 5)
  
  Filtrado automático:
    WHERE ID_compania IN (1, 2) cuando usa Org 1
    WHERE ID_compania IN (4) cuando usa Org 2
```

### Código Actual vs Deseado

**❌ Código Actual:**
```php
// En SalesHeaderImp (sin filtrado)
SalesHeaderImp::all();
// Retorna: TODAS las ventas de TODAS las sucursales en la BD
// Problema: Usuario ve sucursales no asignadas

// Ejemplo: BD rest_centro tiene sucursales 1,2,3
// Usuario solo tiene asignadas: 1,2
// Pero ve también las ventas de sucursal 3 ❌
```

**✅ Código Deseado:**
```php
// Con filtrado automático
SalesHeaderImp::all();
// Retorna: Solo ventas de sucursales asignadas al usuario

// Ejemplo: BD rest_centro tiene sucursales 1,2,3
// Usuario tiene asignadas: 1,2
// Query automático: WHERE ID_compania IN (1, 2)
// Solo ve ventas de sucursales 1 y 2 ✅
```

### Flujo Incorrecto Actual

```
1. Usuario Juan tiene 2 organizaciones
   └─> Org 1: rest_centro (sucursales 1,2,3)
   └─> Org 2: rest_norte (sucursales 4,5)

2. Juan selecciona Org 1 en el header
   └─> Sistema: DB::useDatabase('rest_centro')

3. Query sin filtrado:
   └─> SalesHeaderImp::all()
   └─> ❌ PROBLEMA: Retorna TODAS las ventas (ID_compania 1,2,3)
   └─> Juan no debería ver sucursal 3

4. Método actual getCompanyIds() no ayuda:
   └─> Retorna [100] (ID empresa matriz de BD principal)
   └─> No distingue entre sucursales de la BD organizacional
```

### Flujo Correcto Propuesto

```
1. Usuario Juan tiene 2 organizaciones CON sucursales asignadas
   └─> Org 1: rest_centro
        └─> Sucursales asignadas: [1, 2] (NO la 3)
   └─> Org 2: rest_norte
        └─> Sucursales asignadas: [4] (NO la 5)

2. Juan selecciona Org 1 en el header
   └─> Sistema: DB::useDatabase('rest_centro')
   └─> Carga: assigned_branches = [1, 2]

3. Query con filtrado automático:
   └─> SalesHeaderImp::all()
   └─> CompanyIdFilterScope aplica:
        WHERE ID_compania IN (1, 2)
   └─> ✅ CORRECTO: Solo ventas de sucursales 1 y 2

4. Método mejorado getBranchIds():
   └─> Retorna [1, 2] (sucursales asignadas en org actual)
   └─> Filtrado granular por sucursal
```

---

## Soluciones Propuestas

### ⭐ Solución Recomendada: Sistema de Asignación de Sucursales

**Concepto Actualizado:**
- **SÍ necesitamos** tabla intermedia usuario-organización-sucursales
- 1 Organización (BD propia) = N Sucursales (múltiples ID_compania)
- Usuario tiene acceso a organizaciones Y sucursales específicas dentro de cada org
- Filtrar por los `ID_compania` asignados al usuario en la organización actual

**Opciones de Implementación:**

#### Opción A: Tabla Intermedia Dedicada (Recomendada)
```sql
CREATE TABLE user_organization_branches (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    organization_id BIGINT NOT NULL,
    branch_id INT NOT NULL, -- ID_compania
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, organization_id, branch_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id)
);
```

#### Opción B: Columna JSON en organizations_user
```sql
ALTER TABLE organizations_user 
ADD COLUMN assigned_branches JSON DEFAULT NULL;

-- Ejemplo de datos:
-- assigned_branches: [1, 2, 5] -- IDs de sucursales permitidas
```

### 1️⃣ Crear Tabla/Columna para Asignación de Sucursales

**Opción A - Tabla Dedicada:**
```php
// database/migrations/2025_12_03_create_user_organization_branches_table.php
Schema::create('user_organization_branches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->integer('branch_id'); // ID_compania
    $table->timestamps();
    
    $table->unique(['user_id', 'organization_id', 'branch_id']);
});
```

**Opción B - Columna JSON:**
```php
// database/migrations/2025_12_03_add_assigned_branches_to_organizations_user.php
Schema::table('organizations_user', function (Blueprint $table) {
    $table->json('assigned_branches')->nullable()->after('organization_id');
});
```

### 2️⃣ Modificar OrganizationService

```php
// app/Services/OrganizationService.php

/**
 * Obtiene los ID_compania (sucursales) asignadas al usuario
 * en la organización actual
 * 
 * @return array
 */
public function getAssignedBranchIds(): array
{
    $organization = $this->getOrganization();
    $user = auth()->user();
    
    if (!$organization || !$user) {
        return [];
    }
    
    // Opción A: Desde tabla dedicada
    $branches = \DB::table('user_organization_branches')
        ->where('user_id', $user->id)
        ->where('organization_id', $organization->id)
        ->pluck('branch_id')
        ->toArray();
    
    // Opción B: Desde columna JSON
    // $pivot = $user->organizations()
    //     ->where('organization_id', $organization->id)
    //     ->first()
    //     ?->pivot;
    // $branches = $pivot?->assigned_branches ?? [];
    
    return $branches;
}

/**
 * Verifica si el usuario tiene sucursales asignadas
 * Si no tiene, retorna TODAS las sucursales de la org
 * 
 * @return array
 */
public function getBranchIdsOrAll(): array
{
    $assignedBranches = $this->getAssignedBranchIds();
    
    // Si tiene asignación específica, usarla
    if (!empty($assignedBranches)) {
        return $assignedBranches;
    }
    
    // Si no, retornar todas las sucursales de la org
    $organization = $this->getOrganization();
    if (!$organization) {
        return [];
    }
    
    // Cambiar a BD de la org y obtener todos los ID_compania
    $previousDb = \DB::connection()->getDatabaseName();
    \DB::connection()->useDatabase($organization->database);
    
    $allBranches = \DB::table('CompanySession')
        ->pluck('ID_compania')
        ->unique()
        ->values()
        ->toArray();
    
    \DB::connection()->useDatabase($previousDb);
    
    return $allBranches;
}

/**
 * DEPRECADO: Mantener por compatibilidad
 */
public function getCompanyIds()
{
    return $this->getBranchIdsOrAll();
}
```

### 2️⃣ Crear Global Scope Reutilizable

```php
// app/Models/Scopes/CompanyIdFilterScope.php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyIdFilterScope implements Scope
{
    /**
     * Filtra automáticamente por ID_compania de las sucursales asignadas
     * en la organización actual
     */
    public function apply(Builder $builder, Model $model)
    {
        // Solo aplicar si hay usuario autenticado
        if (!auth()->check()) {
            return;
        }
        
        // Obtener IDs de sucursales asignadas al usuario
        $organizationService = app(\App\Contracts\OrganizationServiceContract::class);
        $branchIds = $organizationService->getAssignedBranchIds();
        
        // Si tiene sucursales asignadas, filtrar
        if (!empty($branchIds)) {
            $builder->whereIn('ID_compania', $branchIds);
        }
        // Si no tiene asignación, mostrar todas (comportamiento por defecto)
        // O podrías: $builder->where('ID_compania', -1); // No mostrar nada
    }
}
```

### 3️⃣ Aplicar Scope a Todos los Modelos

**Ejemplo de implementación:**

```php
// app/Models/SalesHeaderImp.php

namespace App\Models;

use App\Models\Scopes\CompanyIdFilterScope;
use Illuminate\Database\Eloquent\Model;

class SalesHeaderImp extends Model
{
    protected $table = 'Sales_Header_Imp';
    
    protected $fillable = [
        'ID_compania',
        'InvoiceNumber',
        'CustomerID',
        // ... otros campos
    ];
    
    /**
     * Bootstrap del modelo
     */
    protected static function booted()
    {
        // Aplicar filtrado automático por ID_compania
        static::addGlobalScope(new CompanyIdFilterScope);
    }
}
```

### 4️⃣ Modelos que Deben Implementar el Scope

**Prioridad ALTA (Transacciones):**
```php
// Ventas
SalesHeaderImp::class
SalesDetailImp::class
SalesOrderHeaderImp::class
SalesOrderDetailImp::class

// Compras
PurchaseHeaderImp::class
PurchaseDetailImp::class

// Pagos
CustomerReceiptHeaderImp::class
VendorPaymentHeaderImp::class

// Facturación Electrónica
FeHeader::class
FeDetail::class
FePayment::class
```

**Prioridad MEDIA (Catálogos):**
```php
// Ya implementados, pero cambiar de whereIn a where
CustomersImp::class
VendorsImp::class
ProductsImp::class

// Pendientes
CustomersExp::class
VendorsExp::class
ProductsExp::class
```

**Prioridad BAJA (Configuración):**
```php
JobPhasesExp::class
JobCostCodesExp::class
SalesRepresentativeExp::class
ChartExp::class
```

### 5️⃣ Casos Especiales: Reportes Consolidados

**Problema:** Algunos reportes necesitan ver TODAS las sucursales

**Solución:**
```php
// En controllers o servicios de reportes
use Illuminate\Database\Eloquent\Builder;

// Desactivar scope globalmente
SalesHeaderImp::withoutGlobalScope(CompanyIdFilterScope::class)
    ->whereIn('ID_compania', $allCompanyIds)
    ->get();

// O crear método en el modelo
class SalesHeaderImp extends Model
{
    public function scopeAllBranches(Builder $query)
    {
        return $query->withoutGlobalScope(CompanyIdFilterScope::class);
    }
}

// Uso:
SalesHeaderImp::allBranches()->get();
```

### 6️⃣ Jobs Asíncronos

**Problema:** Jobs no tienen `auth()->user()`

**Solución:**
```php
// En Jobs, deshabilitar scope y filtrar manualmente
class ProcessSalesJob implements ShouldQueue
{
    protected $organizationId;
    
    public function handle()
    {
        $organization = Organization::find($this->organizationId);
        $companyId = $organization->id_empresa;
        
        // Cambiar a BD de la organización
        DB::connection()->useDatabase($organization->database);
        
        // Query sin scope, filtrado manual
        $sales = SalesHeaderImp::withoutGlobalScopes()
            ->where('ID_compania', $companyId)
            ->get();
        
        // Procesar...
    }
}
```

---

## Plan de Implementación

### Fase 1: Preparación (2-3 días)

**1.1 Decidir Estrategia de Almacenamiento**
- Opción A: Tabla `user_organization_branches` (más flexible)
- Opción B: Columna JSON en `organizations_user` (más simple)
- **Recomendación:** Opción A para mejor performance y queries

**1.2 Crear Migration**
```bash
php artisan make:migration create_user_organization_branches_table
# O
php artisan make:migration add_assigned_branches_to_organizations_user_table
```

**1.3 Crear Scope Base**
```bash
php artisan make:scope CompanyIdFilterScope
```

**1.4 Modificar OrganizationService**
- Agregar `getAssignedBranchIds()`
- Agregar `getBranchIdsOrAll()`
- Decidir comportamiento por defecto (ver todas vs ninguna)

**1.5 Testing de Scope**
```php
// tests/Unit/CompanyIdFilterScopeTest.php
test('filters by assigned branches in current organization', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create(['database' => 'test_db']);
    $user->organizations()->attach($org->id);
    
    // Asignar sucursales 1 y 2 al usuario
    assignBranchesToUser($user->id, $org->id, [1, 2]);
    
    $this->actingAs($user);
    DB::connection()->useDatabase('test_db');
    
    // Crear datos de prueba
    SalesHeaderImp::factory()->create(['ID_compania' => 1]);
    SalesHeaderImp::factory()->create(['ID_compania' => 2]);
    SalesHeaderImp::factory()->create(['ID_compania' => 3]); // No asignada
    
    $sales = SalesHeaderImp::all();
    
    expect($sales)->toHaveCount(2);
    expect($sales->pluck('ID_compania')->toArray())->toBe([1, 2]);
});
```

### Fase 2: Implementación Progresiva (4-6 días)

**2.1 Crear UI para Asignación de Sucursales**
- Componente Livewire para gestión de sucursales
- Interface: checkboxes por organización
- Validación: al menos una sucursal asignada
- Bulk assignment para múltiples usuarios

**2.2 Modelos de Alta Prioridad**
1. `SalesHeaderImp` y `SalesDetailImp`
2. `CustomerReceiptHeaderImp` y `CustomerReceiptDetailImp`
3. `FeHeader`, `FeDetail`, `FePayment`

**2.3 Actualizar Modelos Existentes con Filtrado Manual**
- Cambiar de `whereIn('ID_compania', $companiesIds)` 
- A `CompanyIdFilterScope` automático
- Modelos: CustomersImp, VendorsImp, ProductsImp, etc.

**2.4 Testing por Módulo**
- Facturación Electrónica con sucursales mixtas
- Ventas e Inventario multi-sucursal
- Reportes consolidados
- Jobs asíncronos (sin auth)

### Fase 3: Migración y Despliegue (3-4 días)

**3.1 Migración de Datos Existentes**
```php
// Seeder para asignar todas las sucursales a usuarios existentes
// Mantener comportamiento actual (ver todas)
foreach (User::all() as $user) {
    foreach ($user->organizations as $org) {
        // Obtener todas las sucursales de la org
        DB::connection()->useDatabase($org->database);
        $branches = DB::table('CompanySession')->pluck('ID_compania');
        
        // Asignar todas al usuario
        foreach ($branches as $branchId) {
            UserOrganizationBranch::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'branch_id' => $branchId
            ]);
        }
    }
}
```

**3.2 Verificación de Jobs**
- Identificar jobs que usan modelos con scope
- Agregar `withoutGlobalScope()` donde sea necesario
- Pasar branch_ids explícitamente

**3.3 Actualizar Reportes**
- Identificar reportes consolidados
- Implementar `allBranches()` scope
- Manejar cross-organization reports

**3.4 Deploy Gradual**
```bash
# 1. Deploy migration en staging
php artisan migrate

# 2. Seed de asignaciones por defecto
php artisan db:seed --class=AssignAllBranchesToUsersSeeder

# 3. Testing completo con organizaciones multi-sucursal
# 4. Deploy en producción
# 5. Monitoreo de logs y queries
```

### Fase 4: Documentación (1 día)

**4.1 Actualizar Copilot Instructions**
```markdown
## Filtrado por Sucursal (ID_compania) - ACTUALIZADO

### Arquitectura Multi-Sucursal
- 1 Empresa Matriz → N Organizaciones (cada una con BD propia)
- 1 Organización (BD) → N Sucursales (múltiples ID_compania)
- Usuario → N Organizaciones → N Sucursales asignadas por org

### Asignación de Sucursales
- Tabla: `user_organization_branches`
- Columnas: user_id, organization_id, branch_id (ID_compania)
- Un usuario puede tener diferentes sucursales en cada organización

### Implementación en Modelos
TODOS los modelos con columna `ID_compania` DEBEN:
1. Implementar `CompanyIdFilterScope` en método `booted()`
2. Filtrado automático por sucursales asignadas al usuario
3. Usar `withoutGlobalScope()` solo en:
   - Jobs asíncronos (sin auth)
   - Reportes consolidados
   - Operaciones administrativas

Ejemplo:
```php
protected static function booted()
{
    static::addGlobalScope(new CompanyIdFilterScope);
}
```

### Para Jobs
```php
// Deshabilitar scope y filtrar manualmente
$sales = SalesHeaderImp::withoutGlobalScopes()
    ->whereIn('ID_compania', $assignedBranches)
    ->get();
```
```

**4.2 Crear Migration Guide**
- Para desarrolladores que agreguen nuevos modelos
- Checklist de validación

---

## Ventajas de Esta Solución

### ✅ Simplicidad
- No requiere tabla intermedia
- Usa relaciones existentes (Organization → Companysession)
- Mínimo impacto en código existente

### ✅ Seguridad
- Filtrado automático a nivel de modelo
- Imposible olvidar filtrar en queries
- Protección contra acceso a datos de otras sucursales

### ✅ Performance
- Un solo filtro: `WHERE ID_compania = 100`
- Índices ya existen en todas las tablas
- Queries más rápidos (menos filas)

### ✅ Mantenibilidad
- Comportamiento consistente en todos los modelos
- Scope reutilizable
- Fácil de testear

### ✅ Flexibilidad
- Reportes consolidados: `withoutGlobalScope()`
- Jobs: filtrado manual explícito
- Compatible con arquitectura actual

---

## Casos de Uso Específicos

### Caso 1: Usuario con 1 Organización, Múltiples Sucursales

```php
User: María
Organizaciones: 1
  └─> Org: "RXY Regional Centro" (BD: rest_centro)
      ├─> Sucursal 1: Local Centro (ID_compania: 1)
      ├─> Sucursal 2: Local Plaza (ID_compania: 2)
      └─> Sucursal 3: Local Mall (ID_compania: 3)

María tiene asignadas: sucursales 1 y 2 (NO la 3)

// Todas las queries automáticamente filtran por sucursales asignadas
SalesHeaderImp::all(); // WHERE ID_compania IN (1, 2)
CustomersImp::all();   // WHERE ID_compania IN (1, 2)

// María NO ve datos de sucursal 3
```

### Caso 2: Usuario con Múltiples Organizaciones y Sucursales Selectivas

```php
User: Juan
Organizaciones: 2
  ├─> Org 1: "RXY Regional Centro" (BD: rest_centro)
  │     ├─> Sucursales disponibles: 1, 2, 3
  │     └─> Asignadas a Juan: 1, 2 (NO la 3)
  │
  └─> Org 2: "RXY Regional Norte" (BD: rest_norte)
        ├─> Sucursales disponibles: 4, 5, 6
        └─> Asignadas a Juan: 4 (NO 5 ni 6)

// Juan selecciona Org 1 en el header
Session::put('organization_id', org1_id);

// Sistema cambia a BD rest_centro
DB::connection()->useDatabase('rest_centro');

// Queries filtran por sucursales asignadas en esa org
SalesHeaderImp::all(); // WHERE ID_compania IN (1, 2)
// Solo ve ventas de sucursales 1 y 2, NO la 3

// Juan selecciona Org 2
Session::put('organization_id', org2_id);
DB::connection()->useDatabase('rest_norte');

// Ahora filtra por sucursales de Org 2
SalesHeaderImp::all(); // WHERE ID_compania IN (4)
// Solo ve ventas de sucursal 4, NO 5 ni 6
```

### Caso 3: Reporte Consolidado Multi-Organización

```php
// Supervisor necesita ver consolidado de múltiples organizaciones
// Cada organización tiene múltiples sucursales

$totalSales = 0;
$salesByBranch = [];

foreach ($user->organizations as $org) {
    // Cambiar a BD de la organización
    DB::connection()->useDatabase($org->database);
    
    // Obtener sucursales asignadas al usuario en esta org
    $assignedBranches = getUserAssignedBranches($user->id, $org->id);
    
    // Query sin scope, con filtrado manual
    $sales = SalesHeaderImp::withoutGlobalScope(CompanyIdFilterScope::class)
        ->whereIn('ID_compania', $assignedBranches)
        ->selectRaw('ID_compania, SUM(Net_due) as total')
        ->groupBy('ID_compania')
        ->get();
    
    foreach ($sales as $sale) {
        $salesByBranch[$org->nombre][$sale->ID_compania] = $sale->total;
        $totalSales += $sale->total;
    }
}

return [
    'total' => $totalSales,
    'by_organization_and_branch' => $salesByBranch
];

// Resultado:
// [
//   'total' => 150000,
//   'by_organization_and_branch' => [
//     'RXY Regional Centro' => [
//       1 => 50000,  // Sucursal 1
//       2 => 30000   // Sucursal 2
//     ],
//     'RXY Regional Norte' => [
//       4 => 70000   // Sucursal 4
//     ]
//   ]
// ]
```

---

## Métricas de Éxito

### KPIs

1. **Seguridad:**
   - ✅ 0 queries sin filtro ID_compania
   - ✅ Auditoría: logs de acceso por sucursal

2. **Performance:**
   - ✅ Reducción de 60-80% en filas retornadas
   - ✅ Queries 2-3x más rápidos

3. **Calidad:**
   - ✅ 100% cobertura de tests en modelos críticos
   - ✅ 0 bugs relacionados con datos cruzados

4. **Desarrollo:**
   - ✅ 43 modelos con scope implementado
   - ✅ Documentación actualizada

---

## Contacto y Soporte

**Desarrollador Principal:** [Tu Nombre]  
**Fecha de Análisis:** 3 de diciembre de 2025  
**Última Actualización:** 3 de diciembre de 2025

**Referencias:**
- [Copilot Instructions](../../.github/copilot-instructions.md)
- [Organization Model](../../app/Models/Organization.php)
- [OrganizationService](../../app/Services/OrganizationService.php)

---

## Conclusión

El sistema actual necesita mejoras para manejar el escenario real:
- ✅ 1 Empresa Matriz (CompanySession en BD principal) → N Organizaciones
- ✅ 1 Organización (BD propia) → N Sucursales (múltiples ID_compania)
- ✅ Columna ID_compania existe en 43+ tablas
- ❌ Falta: Asignación de sucursales por usuario

**Necesitamos implementar:**
1. Tabla `user_organization_branches` o columna JSON `assigned_branches`
2. Modificar `OrganizationService::getAssignedBranchIds()`
3. Implementar `CompanyIdFilterScope` global con filtrado por sucursales asignadas
4. Aplicarlo a 43+ modelos
5. UI para asignar sucursales a usuarios
6. Manejar caso: usuario sin asignación (¿ver todas o ninguna?)

**NO necesitamos:**
- ❌ Cambios estructurales mayores en BD
- ❌ Modificar relación Organization → Companysession
- ❌ Cambiar arquitectura multi-tenant

**Impacto:** Riesgo medio, recompensa alta - requiere tabla intermedia y lógica de asignación 🎯
