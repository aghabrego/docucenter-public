# Análisis de Replicación de Bases de Datos para Consolidación de Compañía

**Fecha:** 5 de diciembre de 2025  
**Versión:** 1.0  
**Estado:** Análisis Técnico

---

## 📋 Índice

1. [Problema a Resolver](#problema-a-resolver)
2. [Arquitectura Propuesta](#arquitectura-propuesta)
3. [Opciones de Replicación](#opciones-de-replicación)
4. [Análisis Comparativo](#análisis-comparativo)
5. [Solución Recomendada](#solución-recomendada)
6. [Plan de Implementación](#plan-de-implementación)

---

## Problema a Resolver

### Limitación de Sage Connector

**Restricción:**
- Sage Connector solo puede conectarse a **UNA base de datos**
- Solo puede trabajar con **UN solo ID_compania**
- Actualmente: Cada organización tiene su BD con diferente ID_compania

### Arquitectura Actual

```
BD Principal (mysql/docucenter)
├─> CompanySession (tabla centralizada)
├─> Organizations
│   ├─> Org 1: database = "9_734_1672_56"  (id_empresa: 100)
│   ├─> Org 2: database = "9_734_1672_57"  (id_empresa: 100)
│   └─> Org 3: database = "9_734_1672_58"  (id_empresa: 101)

BD Organización 1 (9_734_1672_56)
├─> CompanySession (ID_compania: 100)
├─> Customers_Imp (datos org 1)
└─> Sales_Header_Imp (datos org 1)

BD Organización 2 (9_734_1672_57)
├─> CompanySession (ID_compania: 100)
├─> Customers_Imp (datos org 2)
└─> Sales_Header_Imp (datos org 2)

BD Organización 3 (9_734_1672_58)
├─> CompanySession (ID_compania: 101)
├─> Customers_Imp (datos org 3)
└─> Sales_Header_Imp (datos org 3)

❌ PROBLEMA: Sage no puede acceder a datos de Org 1 + Org 2 + Org 3
```

### Arquitectura Deseada

```
BD Principal (mysql/docucenter)
├─> companies (NUEVA tabla)
│   ├─> Company 100: database = "company_100"
│   └─> Company 101: database = "company_101"
│
├─> CompanySession (mantener para compatibilidad)
├─> Organizations
│   ├─> Org 1: company_id = 100, database = "9_734_1672_56"
│   ├─> Org 2: company_id = 100, database = "9_734_1672_57"
│   └─> Org 3: company_id = 101, database = "9_734_1672_58"

BD Organización 1 (9_734_1672_56) - ORIGEN
├─> Customers_Imp (100 registros)
└─> Sales_Header_Imp (500 ventas)

BD Organización 2 (9_734_1672_57) - ORIGEN
├─> Customers_Imp (80 registros)
└─> Sales_Header_Imp (300 ventas)

BD Compañía 100 (company_100) - DESTINO CONSOLIDADO
├─> Customers_Imp (180 registros: 100 de org1 + 80 de org2)
│   ├─> Registros de Org 1: org_source_id = 1, ID_compania = 100
│   └─> Registros de Org 2: org_source_id = 2, ID_compania = 100
│
└─> Sales_Header_Imp (800 ventas: 500 de org1 + 300 de org2)
    ├─> Ventas de Org 1: org_source_id = 1, ID_compania = 100
    └─> Ventas de Org 2: org_source_id = 2, ID_compania = 100

✅ SOLUCIÓN: Sage Connector se conecta a company_100 y ve TODO
```

---

## Arquitectura Propuesta

### Flujo de Datos

```
┌─────────────────────────────────────────────────────────────────┐
│  BD Organizaciones (ORIGEN - Operación Diaria)                  │
├─────────────────────────────────────────────────────────────────┤
│  - DocuCenter crea facturas aquí                                │
│  - Usuarios trabajan aquí                                       │
│  - Cambio de BD por organización (actual)                       │
└──────────────────┬──────────────────────────────────────────────┘
                   │
                   │ REPLICACIÓN (cada N minutos)
                   ▼
┌─────────────────────────────────────────────────────────────────┐
│  BD Compañía (DESTINO - Solo Lectura para Sage)                │
├─────────────────────────────────────────────────────────────────┤
│  - Sage Connector LEE desde aquí                                │
│  - Datos consolidados de TODAS las organizaciones               │
│  - Mismo ID_compania para todos los registros                   │
│  - Campo org_source_id identifica origen                        │
└─────────────────────────────────────────────────────────────────┘
```

### Campos Adicionales Necesarios

**SOLUCIÓN SIMPLIFICADA:** Agregar `org_source_id` en **TODAS** las BDs

**Ventajas de esta aproximación:**
- ✅ Modelos Eloquent compatibles entre BDs
- ✅ Misma estructura en org y company
- ✅ Migraciones más simples
- ✅ Testing más fácil
- ✅ Sin problemas de esquema

**En BDs de Organizaciones:**

```bash
# Usar comando Artisan existente
php artisan db:add-column-to-organizations-table \
    Customers_Imp \
    org_source_id \
    bigint \
    --unsigned=1 \
    --nullable=1 \
    --index=1

# Script automatizado para todas las organizaciones
bash scripts/add-org-source-id-to-organizations.sh
```

**En BD de Compañía:**

```sql
-- Misma columna pero con PRIMARY KEY compuesto
ALTER TABLE Customers_Imp 
    ADD COLUMN org_source_id BIGINT UNSIGNED NULL AFTER ID_compania,
    ADD INDEX idx_org_source (org_source_id),
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (ID, org_source_id);
```

**Uso de la columna:**

```
BD Organización 1 (9_734_1672_56):
  - Customers_Imp:
    * ID=100, ID_compania=100, org_source_id=NULL ← No se usa en org
    
BD Organización 2 (9_734_1672_57):
  - Customers_Imp:
    * ID=100, ID_compania=100, org_source_id=NULL ← No se usa en org

BD Compañía (company_100):
  - Customers_Imp:
    * ID=100, ID_compania=100, org_source_id=1 ← Asignado por Job
    * ID=100, ID_compania=100, org_source_id=2 ← Asignado por Job
```

**Propósito:**
- Misma estructura en todas las BDs (compatibilidad de modelos)
- En BDs de organizaciones: `org_source_id` queda NULL (no se usa)
- En BD de compañía: `org_source_id` identifica origen
- Job de sincronización asigna el valor al copiar

---

## Opciones de Replicación

### Opción 1: Replicación Nativa MySQL (Master-Slave)

#### Descripción
Usar el sistema de replicación binlog de MySQL/MariaDB.

#### Cómo Funciona
```
BD Org 1 (Master) ──binlog──> BD Company (Slave)
BD Org 2 (Master) ──binlog──> BD Company (Slave)
BD Org 3 (Master) ──binlog──> BD Company (Slave)
```

#### Configuración
```ini
# En my.cnf de cada BD Organización (Master)
[mysqld]
server-id = 1  # Único por BD
log-bin = mysql-bin
binlog-do-db = 9_734_1672_56
binlog-format = ROW

# En my.cnf de BD Compañía (Slave)
[mysqld]
server-id = 100
relay-log = mysql-relay-bin
```

#### Ventajas
✅ Replicación en tiempo real (segundos de delay)
✅ Nativo de MySQL, estable y probado
✅ Automático, no requiere intervención manual

#### Desventajas
❌ **NO soporta múltiples masters a un slave con misma tabla**
❌ Conflictos de PRIMARY KEY entre organizaciones
❌ Complejo de mantener con N organizaciones dinámicas
❌ Requiere configuración de servidor MySQL avanzada

#### Viabilidad
**⚠️ NO RECOMENDADO** - MySQL no soporta nativamente multi-master a single-slave para mismas tablas

---

### Opción 2: ETL con Laravel Jobs Asíncronos

#### Descripción
Crear Jobs de Laravel que copien datos periódicamente.

#### Cómo Funciona
```php
// Job: SyncOrganizationToCompanyJob
public function handle()
{
    // 1. Conectar a BD de organización (origen)
    DB::connection()->useDatabase($this->organization->database);
    
    // 2. Leer cambios recientes (últimos N minutos)
    $customers = CustomersImp::where('updated_at', '>', $lastSync)->get();
    
    // 3. Conectar a BD de compañía (destino)
    DB::connection()->useDatabase($this->company->database);
    
    // 4. Insertar/actualizar con org_source_id
    foreach ($customers as $customer) {
        CustomersImp::updateOrCreate(
            ['ID' => $customer->ID, 'org_source_id' => $this->organization->id],
            $customer->toArray() + ['org_source_id' => $this->organization->id]
        );
    }
}
```

#### Scheduler
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Cada 5 minutos sincronizar todas las organizaciones
    $schedule->command('company:sync-organizations')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
```

#### Ventajas
✅ Control total sobre el proceso
✅ Puede transformar datos antes de copiar
✅ Maneja conflictos de forma explícita
✅ Fácil de debuggear y mantener
✅ Puede agregar org_source_id automáticamente
✅ No requiere configuración de MySQL

#### Desventajas
⚠️ Delay de hasta N minutos (no tiempo real)
⚠️ Requiere desarrollo de lógica de sincronización
⚠️ Consume recursos del servidor Laravel

#### Viabilidad
**✅ RECOMENDADO** - Solución pragmática y controlable

---

### Opción 3: Triggers MySQL para Sincronización

#### Descripción
Crear triggers en cada tabla de cada BD org que copien a BD company.

#### Cómo Funciona
```sql
-- En BD Organización 1
DELIMITER $$
CREATE TRIGGER sync_customer_to_company
AFTER INSERT ON Customers_Imp
FOR EACH ROW
BEGIN
    -- Insertar en BD de compañía
    INSERT INTO company_100.Customers_Imp (
        ID, CustomerID, CustomerName, org_source_id
    ) VALUES (
        NEW.ID, NEW.CustomerID, NEW.CustomerName, 1
    ) ON DUPLICATE KEY UPDATE
        CustomerName = NEW.CustomerName,
        updated_at = NOW();
END$$
DELIMITER ;
```

#### Ventajas
✅ Tiempo real (instantáneo)
✅ No requiere Jobs externos
✅ Automático, transparente para aplicación

#### Desventajas
❌ **MUY COMPLEJO de mantener** (43+ tablas × N organizaciones)
❌ Difícil debuggear problemas
❌ Triggers pueden fallar silenciosamente
❌ No soporta transformaciones complejas
❌ Performance degradado en escrituras

#### Viabilidad
**❌ NO RECOMENDADO** - Complejidad muy alta

---

### Opción 4: Vistas Federadas (FEDERATED Storage Engine)

#### Descripción
Usar tablas FEDERATED que apuntan a tablas remotas.

#### Cómo Funciona
```sql
-- En BD Compañía
CREATE TABLE Customers_Imp_Org1 (
    ID INT, CustomerID VARCHAR(50), CustomerName VARCHAR(255)
) ENGINE=FEDERATED
CONNECTION='mysql://user:pass@host:3306/9_734_1672_56/Customers_Imp';

-- Vista consolidada
CREATE VIEW Customers_Imp AS
SELECT *, 1 as org_source_id FROM Customers_Imp_Org1
UNION ALL
SELECT *, 2 as org_source_id FROM Customers_Imp_Org2;
```

#### Ventajas
✅ Datos siempre actualizados (tiempo real)
✅ No duplicación de datos
✅ Queries transparentes para Sage

#### Desventajas
❌ **FEDERATED no viene habilitado por defecto en MariaDB**
❌ Performance muy pobre en queries complejos
❌ Requiere conexión permanente entre BDs
❌ No soporta transacciones
❌ Sage puede tener problemas con VIEWS

#### Viabilidad
**⚠️ NO RECOMENDADO** - Performance y estabilidad cuestionables

---

### Opción 5: Replicación Manual con Comandos Artisan

#### Descripción
Comando Artisan que ejecuta replicación bajo demanda.

#### Cómo Funciona
```bash
# Sincronizar organización específica
php artisan company:sync --organization=1

# Sincronizar todas las organizaciones de una compañía
php artisan company:sync --company=100

# Sincronización completa
php artisan company:sync --all
```

#### Implementación
```php
// app/Console/Commands/SyncOrganizationsToCompany.php
class SyncOrganizationsToCompany extends Command
{
    protected $signature = 'company:sync {--organization=} {--company=} {--all}';
    
    public function handle()
    {
        $organizations = $this->getOrganizationsToSync();
        
        foreach ($organizations as $org) {
            $this->syncOrganization($org);
        }
    }
    
    protected function syncOrganization(Organization $org)
    {
        $tables = $this->getTablesToSync();
        
        foreach ($tables as $table) {
            $this->syncTable($org, $table);
        }
    }
}
```

#### Ventajas
✅ Control manual total
✅ Puede ejecutarse en horarios específicos (cron)
✅ Útil para debugging
✅ Bajo riesgo, ejecución explícita

#### Desventajas
⚠️ Requiere ejecución manual/programada
⚠️ No es automático

#### Viabilidad
**✅ COMPLEMENTO** - Usar junto con Jobs automáticos

---

## Análisis Comparativo

### Matriz de Decisión

| Criterio | Replicación Nativa | Laravel Jobs | Triggers | FEDERATED | Manual |
|----------|-------------------|--------------|----------|-----------|--------|
| **Tiempo Real** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐ |
| **Facilidad Implementación** | ⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Mantenibilidad** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Performance** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Estabilidad** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Debugging** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Escalabilidad** | ⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Viabilidad Técnica** | ❌ | ✅ | ⚠️ | ❌ | ✅ |

### Recomendación Final

**🏆 SOLUCIÓN RECOMENDADA: Laravel Jobs Asíncronos (Opción 2) + Manual (Opción 5)**

**Razones:**
1. ✅ **Control total**: Laravel maneja toda la lógica
2. ✅ **Mantenible**: Código PHP estándar, fácil de debuggear
3. ✅ **Flexible**: Puede transformar datos antes de copiar
4. ✅ **Escalable**: Agregar nuevas organizaciones es trivial
5. ✅ **No requiere cambios en MySQL**: Usa configuración actual
6. ✅ **Testeable**: Unit tests y feature tests fáciles
7. ✅ **Monitoreable**: Logs, estados, reintentos incorporados

---

## Solución Recomendada

### Arquitectura de Sincronización con Laravel Jobs

#### 1. Tabla de Control de Sincronización

```sql
CREATE TABLE company_sync_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    organization_id BIGINT NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    records_synced INT DEFAULT 0,
    last_sync_at TIMESTAMP NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_company (company_id),
    INDEX idx_organization (organization_id),
    INDEX idx_status (status),
    INDEX idx_last_sync (last_sync_at),
    
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id)
);
```

#### 2. Job Principal de Sincronización

```php
// app/Jobs/SyncOrganizationToCompanyJob.php
namespace App\Jobs;

use App\Models\Organization;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOrganizationToCompanyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutos

    protected $organizationId;
    protected $companyId;
    protected $lastSyncTimestamp;

    // Tablas a sincronizar (43+ tablas con ID_compania)
    protected $tablesToSync = [
        'Customers_Imp',
        'Vendors_Imp',
        'Products_Imp',
        'Sales_Header_Imp',
        'Sales_Detail_Imp',
        'Purchase_Header_Imp',
        'Purchase_Detail_Imp',
        'Customer_Receipt_Header_Imp',
        'Vendor_Payment_Header_Imp',
        // ... agregar TODAS las 43 tablas
    ];

    public function __construct(int $organizationId, int $companyId, $lastSyncTimestamp = null)
    {
        $this->organizationId = $organizationId;
        $this->companyId = $companyId;
        $this->lastSyncTimestamp = $lastSyncTimestamp ?? now()->subMinutes(10);
    }

    public function handle()
    {
        try {
            $organization = Organization::findOrFail($this->organizationId);
            $company = Company::findOrFail($this->companyId);

            Log::info("Iniciando sincronización", [
                'organization_id' => $this->organizationId,
                'company_id' => $this->companyId,
                'company_database' => $company->database,
            ]);

            foreach ($this->tablesToSync as $table) {
                $this->syncTable($organization, $company, $table);
            }

            $this->updateSyncLog($organization, $company, 'completed');

            Log::info("Sincronización completada", [
                'organization_id' => $this->organizationId,
                'company_id' => $this->companyId,
            ]);

        } catch (\Exception $e) {
            Log::error("Error en sincronización", [
                'organization_id' => $this->organizationId,
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
            ]);

            $this->updateSyncLog($organization ?? null, $company ?? null, 'failed', $e->getMessage());
            throw $e;
        }
    }

    protected function syncTable(Organization $org, Company $company, string $table)
    {
        // 1. Cambiar a BD de organización (origen)
        DB::connection()->useDatabase($org->database);

        // 2. Obtener registros modificados desde última sincronización
        // org_source_id en origen estará NULL (no se usa en orgs)
        $records = DB::table($table)
            ->where('updated_at', '>', $this->lastSyncTimestamp)
            ->get();

        if ($records->isEmpty()) {
            Log::debug("No hay cambios en {$table} para org {$org->id}");
            return;
        }

        Log::info("Sincronizando {$table}", [
            'organization_id' => $org->id,
            'records_count' => $records->count(),
        ]);

        // 3. Cambiar a BD de compañía (destino)
        DB::connection()->useDatabase($company->database);

        // 4. Insertar/actualizar registros
        foreach ($records as $record) {
            $data = (array) $record;
            
            // ⭐ IMPORTANTE: Asignar org_source_id (está NULL en origen)
            $data['org_source_id'] = $org->id;
            
            // Construir unique key compuesto (ID + org_source_id)
            $uniqueKey = [
                'ID' => $record->ID ?? null,
                'org_source_id' => $org->id,
            ];

            // Upsert (insert or update)
            // Si existe el registro (mismo ID y org_source_id), actualiza
            // Si no existe, inserta nuevo
            DB::table($table)->updateOrInsert($uniqueKey, $data);
        }

        Log::info("{$table} sincronizada", [
            'organization_id' => $org->id,
            'records_synced' => $records->count(),
        ]);
    }

    protected function updateSyncLog($org, $company, string $status, string $error = null)
    {
        if (!$org || !$company) return;

        DB::connection()->useDatabase(config('database.connections.mysql.database'));

        DB::table('company_sync_logs')->insert([
            'company_id' => $company->id,
            'organization_id' => $org->id,
            'table_name' => 'all',
            'status' => $status,
            'error_message' => $error,
            'last_sync_at' => now(),
        ]);
    }
}
```

#### 3. Comando Artisan para Sincronización Manual

```php
// app/Console/Commands/SyncOrganizationsToCompany.php
namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Organization;
use App\Jobs\SyncOrganizationToCompanyJob;
use Illuminate\Console\Command;

class SyncOrganizationsToCompany extends Command
{
    protected $signature = 'company:sync 
                            {--organization=* : IDs de organizaciones específicas}
                            {--company= : ID de compañía específica}
                            {--all : Sincronizar todas las organizaciones}';

    protected $description = 'Sincroniza datos de organizaciones a su BD de compañía consolidada';

    public function handle()
    {
        $this->info("🚀 Iniciando sincronización de organizaciones a compañía...\n");

        $organizations = $this->getOrganizationsToSync();

        if ($organizations->isEmpty()) {
            $this->error("❌ No se encontraron organizaciones para sincronizar");
            return 1;
        }

        $this->info("📊 Organizaciones a sincronizar: {$organizations->count()}\n");

        $bar = $this->output->createProgressBar($organizations->count());

        foreach ($organizations as $org) {
            $this->syncOrganization($org);
            $bar->advance();
        }

        $bar->finish();

        $this->info("\n\n✅ Sincronización completada");
        return 0;
    }

    protected function getOrganizationsToSync()
    {
        if ($this->option('all')) {
            return Organization::whereNotNull('company_id')->get();
        }

        if ($orgIds = $this->option('organization')) {
            return Organization::whereIn('id', $orgIds)->get();
        }

        if ($companyId = $this->option('company')) {
            return Organization::where('company_id', $companyId)->get();
        }

        // Si no se especifica, sincronizar todas
        return Organization::whereNotNull('company_id')->get();
    }

    protected function syncOrganization(Organization $org)
    {
        if (!$org->company_id) {
            $this->warn("⚠️  Organización {$org->id} no tiene compañía asignada");
            return;
        }

        // Despachar Job asíncrono
        SyncOrganizationToCompanyJob::dispatch($org->id, $org->company_id);
    }
}
```

#### 4. Scheduler Automático

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Sincronizar cada 5 minutos todas las organizaciones
    $schedule->command('company:sync --all')
        ->everyFiveMinutes()
        ->withoutOverlapping(10) // Timeout de 10 min
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/company-sync.log'));
}
```

#### 5. Modificación de Tablas

**ENFOQUE SIMPLIFICADO:** Agregar columna en TODAS las BDs (org + company)

**a) Agregar `org_source_id` a BDs de Organizaciones:**

```bash
# Usar comando Artisan existente
php artisan db:add-column-to-organizations-table \
    Customers_Imp \
    org_source_id \
    bigint \
    --unsigned=1 \
    --nullable=1 \
    --index=1

# Script automatizado para todas las 43 tablas
bash scripts/add-org-source-id-to-organizations.sh
```

**b) Agregar `org_source_id` a BD de Compañía:**

```sql
-- Misma columna pero con PRIMARY KEY compuesto
ALTER TABLE Customers_Imp 
    ADD COLUMN org_source_id BIGINT UNSIGNED NULL AFTER ID_compania,
    ADD INDEX idx_org_source (org_source_id);

-- Cambiar PRIMARY KEY a compuesto
ALTER TABLE Customers_Imp
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (ID, org_source_id);
```

**c) Script para BD de Compañía:**

```bash
bash scripts/add-org-source-id-to-company.sh
```

**Ventajas:**
- ✅ Misma estructura de columnas en todas las BDs
- ✅ Modelos Eloquent funcionan en ambos contextos
- ✅ Migraciones más simples
- ✅ En BDs org: columna queda NULL (no se usa)
- ✅ En BD company: columna se llena por Job sync

---

## Plan de Implementación

### Fase 1: Preparación (1 semana)

**Estado:** ✅ Stubs actualizados (38/45) | ⏳ BD modifications pendientes

#### 1.1 Actualizar Stubs SQL ✅ COMPLETADO

Los stubs SQL ya incluyen la columna `org_source_id`:

```bash
# Ver resumen detallado
cat docs/technical/stubs-org-source-id-update-summary.md

# Resultados:
# - 38 stubs procesados exitosamente
# - 29 automáticamente (script Python)
# - 9 manualmente (tablas críticas)
# - 0 errores
```

#### 1.2 Crear Modelo y Migración de `companies`

```bash
php artisan make:model Company -m
php artisan make:migration add_company_id_to_organizations_table
```

#### 1.3 Crear Sistema de Logs

```bash
php artisan make:migration create_company_sync_logs_table
```

#### 1.4 Agregar columna `org_source_id` a TODAS las BDs ⏳ PENDIENTE

**a) BDs de Organizaciones (usar comando Artisan):**

```bash
# Para cada tabla, ejecutar:
php artisan db:add-column-to-organizations-table \
    Customers_Imp \
    org_source_id \
    bigint \
    --unsigned=1 \
    --nullable=1 \
    --index=1

# O script automatizado:
bash scripts/add-org-source-id-to-organizations.sh
```

**b) Crear BD de Compañía:**

```bash
# 1. Crear base de datos
docker exec -it docucenter-mariadb-1 mysql -u root -p -e "CREATE DATABASE company_100"

# 2. Copiar estructura desde stubs (sin org_source_id todavía)
# Similar a creación de org normal
```

**c) Agregar `org_source_id` a BD de Compañía:**

```bash
# Script que agrega columna + cambia PRIMARY KEY
bash scripts/add-org-source-id-to-company.sh
```

**VENTAJAS de este enfoque:**
- ✅ Modelos Eloquent compatibles en ambos contextos
- ✅ Misma estructura facilita testing
- ✅ Columna nullable en orgs (queda sin usar)
- ✅ Columna populated en company (por Job sync)

### Fase 2: Desarrollo (1 semana)

#### 2.1 Implementar Job de Sincronización

```bash
php artisan make:job SyncOrganizationToCompanyJob
```

#### 2.2 Implementar Comando Artisan

```bash
php artisan make:command SyncOrganizationsToCompany
```

#### 2.3 Testing

```bash
php artisan make:test CompanySyncTest
```

### Fase 3: Testing en Staging (3 días)

#### 3.1 Sincronización Manual

```bash
php artisan company:sync --organization=1
```

#### 3.2 Validar Datos

```sql
-- Verificar en BD de compañía
SELECT COUNT(*), org_source_id 
FROM Customers_Imp 
GROUP BY org_source_id;
```

#### 3.3 Probar Sage Connector

- Conectar Sage a BD de compañía
- Validar lectura de datos
- Verificar sincronización bidireccional

### Fase 4: Despliegue (2 días)

#### 4.1 Migración de Producción

```bash
# 1. Crear BD de compañías
php artisan migrate

# 2. Sincronización inicial (completa)
php artisan company:sync --all

# 3. Activar scheduler
# (ya está en Kernel.php)
```

#### 4.2 Monitoreo

```bash
# Ver logs de sincronización
tail -f storage/logs/company-sync.log

# Ver estado en BD
SELECT * FROM company_sync_logs ORDER BY id DESC LIMIT 20;
```

### Fase 5: Optimización (1 semana)

#### 5.1 Índices y Performance

```sql
-- Agregar índices específicos según uso de Sage
CREATE INDEX idx_customer_id ON Customers_Imp(CustomerID);
CREATE INDEX idx_invoice_date ON Sales_Header_Imp(Date);
```

#### 5.2 Monitoreo de Queue

```bash
# Ver jobs pendientes
php artisan queue:work --queue=default --tries=3 --timeout=600
```

---

## Consideraciones Importantes

### 1. **Conflictos de IDs**

**Problema:** Org 1 tiene Customer ID=100, Org 2 también tiene Customer ID=100

**Solución:** Primary key compuesto `(ID, org_source_id)` solo en BD de Compañía

```sql
-- BD de Compañía (company_100) - PRIMARY KEY compuesto
Customers_Imp:
  - ID=100, ID_compania=100, org_source_id=1 (de Org 1)
  - ID=100, ID_compania=100, org_source_id=2 (de Org 2)
  └─> PRIMARY KEY (ID, org_source_id) permite IDs duplicados

-- BD de Organización 1 (9_734_1672_56) - PRIMARY KEY simple
Customers_Imp:
  - ID=100, ID_compania=100, org_source_id=NULL
  └─> PRIMARY KEY (ID) - columna existe pero no se usa

-- BD de Organización 2 (9_734_1672_57) - PRIMARY KEY simple
Customers_Imp:
  - ID=100, ID_compania=100, org_source_id=NULL
  └─> PRIMARY KEY (ID) - columna existe pero no se usa
```

**Implementación:**
- Todas las BDs tienen columna `org_source_id` (compatibilidad de modelos)
- BDs de organizaciones: `org_source_id` NULL, PRIMARY KEY (ID)
- BD de compañía: `org_source_id` populated, PRIMARY KEY (ID, org_source_id)
- Job asigna valor solo al copiar a company

### 2. **Sincronización Bidireccional**

**Problema:** ¿Qué pasa si Sage modifica datos en BD de compañía?

**Solución 1 (Recomendada):** BD de compañía es **SOLO LECTURA** para Sage
- Sage solo **LEE** datos
- DocuCenter sigue siendo la fuente de verdad
- Sincronización es **unidireccional** (Org → Company)

**Solución 2 (Avanzada):** Sincronización bidireccional
- Requiere Job inverso: `SyncCompanyToOrganizationJob`
- Más complejo, mayor riesgo de conflictos
- **NO recomendado inicialmente**

### 3. **Delay de Sincronización**

**Delay:** 5 minutos (configurable)

**Impacto:**
- Factura creada en Org 1 a las 10:00
- Visible en BD de compañía a las 10:05
- Sage ve el cambio con máximo 5 min de retraso

**¿Es aceptable?** 
- ✅ SÍ para la mayoría de casos
- ⚠️ Si requiere tiempo real, reducir a 1 minuto

### 4. **Volumen de Datos**

**Estimación:**
- 10 organizaciones
- 1000 registros promedio por tabla
- 43 tablas
- **Total:** 430,000 registros

**Performance:**
- Sincronización inicial: ~10-15 minutos
- Sincronizaciones incrementales (cada 5 min): ~1-2 minutos
- Queue de Redis maneja sin problemas

### 5. **Backup y Recuperación**

**Estrategia:**
1. **BD de Organizaciones:** Backup diario (fuente de verdad)
2. **BD de Compañía:** Puede regenerarse desde orgs
3. **Logs de Sincronización:** Retention de 30 días

---

## Métricas de Éxito

### KPIs

1. **Sincronización:**
   - ✅ Delay promedio < 5 minutos
   - ✅ Success rate > 99%
   - ✅ 0 pérdida de datos

2. **Performance:**
   - ✅ Sage Connector conecta correctamente
   - ✅ Queries de Sage < 2 segundos
   - ✅ Jobs de sincronización < 5 minutos

3. **Estabilidad:**
   - ✅ 0 duplicados en BD de compañía
   - ✅ Integridad referencial mantenida
   - ✅ Logs sin errores críticos

---

## Contacto y Soporte

**Desarrollador:** [Tu Nombre]  
**Fecha de Análisis:** 5 de diciembre de 2025  
**Última Actualización:** 5 de diciembre de 2025

**Referencias:**
- [Guías de Desarrollo](../../.github/guias-desarrollo.md)
- [Multi-Branch System Analysis](./multi-branch-system-analysis.md)
- [Laravel Queue Documentation](https://laravel.com/docs/9.x/queues)

---

## Conclusión

**✅ SOLUCIÓN VIABLE Y RECOMENDADA:**

La replicación mediante **Laravel Jobs Asíncronos** es la solución más:
- ✅ **Pragmática**: No requiere configuración compleja de MySQL
- ✅ **Mantenible**: Código PHP estándar de Laravel
- ✅ **Escalable**: Agregar organizaciones es trivial
- ✅ **Flexible**: Control total sobre transformaciones
- ✅ **Debuggeable**: Logs, reintentos, monitoreo incorporado

**Próximo Paso:** Implementar Fase 1 - Preparación de estructura de BDs y modelos.
