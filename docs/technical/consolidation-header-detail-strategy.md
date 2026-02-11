# Estrategia de Consolidación: Tablas Header-Detail con Auto-Increment

**Documento de Análisis y Planificación** 
**Fecha:** 18 de diciembre, 2025 
**Estado:** En Planificación 
**Ubicación:** `docs/technical/consolidation-header-detail-strategy.md`

---

## Índice

1. [Problema Identificado](#problema-identificado)
2. [Tablas Afectadas](#tablas-afectadas)
3. [Análisis del Código Actual](#análisis-del-código-actual)
4. [Escenario de Colisión](#escenario-de-colisión)
5. [Solución Propuesta](#solución-propuesta)
6. [Implementación Necesaria](#implementación-necesaria)
7. [Consideraciones Adicionales](#consideraciones-adicionales)
8. [Plan de Acción](#plan-de-acción)
9. [Testing](#testing)
10. [Riesgos y Mitigaciones](#riesgos-y-mitigaciones)

---

## Problema Identificado

Cuando se consolidan datos de múltiples organizaciones en una sola compañía (company), las tablas con relaciones **Header → Detail** tienen IDs auto-incrementales que **COLISIONARÁN** entre organizaciones diferentes.

### Contexto

- **Job Actual:** `app/Jobs/SyncOrganizationToCompanyJob.php`
- **Operación:** Mover datos de BD de organización → BD de compañía consolidada
- **Problema:** Los `TransactionID`, `UniqueReceiptID`, etc., se repiten entre organizaciones

---

## Tablas Afectadas

### Pares Header-Detail Identificados (9 pares)

| # | Header Table | Detail Table(s) | Columna Relación | Auto Increment | Prioridad |
|---|--------------|-----------------|------------------|----------------|-----------|
| 1 | `Customer_Credit_Memo_Header_Imp` | `Customer_Credit_Memo_Detail_Imp` | `TransactionID` | SÍ | Alta |
| 2 | `GJE_Header_Imp` | `GJE_Detail_Imp` | `TransactionID` | SÍ | **Implementado** |
| 3 | `Purchase_Header_Imp` | `Purchase_Detail_Imp` | `TransactionID` | SÍ | Alta |
| 4 | `customer_receipt_header_imp` | `customer_receipt_detail_imp` | `UniqueReceiptID` | SÍ | Alta |
| 5 | `vendor_payment_header_imp` | `vendor_payment_detail_imp` | `UniquePaymentID` | SÍ | Alta |
| 6 | `fe_header` | `fe_detail`, `fe_payment` | `feHeaderId` / `id` | SÍ | Media |
| 7 | `PurOrdr_Header_Exp` | `PurOrdr_Detail_Exp` | `TransactionID` | **Por verificar** | Baja |
| 8 | `SalesInvoice_Header_Exp` | `SalesInvoice_Detail_Exp` | `TransactionID` | **Por verificar** | Baja |
| 9 | `SalesOrder_Header_Exp` | `SalesOrder_Detail_Exp` | `TransactionID` | **Por verificar** | Baja |

### Primary Keys por Modelo

```php
// Verificado en app/Models/
CustomerCreditMemoHeaderImp::$primaryKey = 'TransactionID'; // auto_increment = true
GJEHeaderImp::$primaryKey = 'TransactionID'; // auto_increment = true YA IMPLEMENTADO
PurchaseHeaderImp::$primaryKey = 'TransactionID'; // auto_increment = true
CustomerReceiptHeaderImp::$primaryKey = 'UniqueReceiptID'; // auto_increment = true
VendorPaymentHeaderImp::$primaryKey = 'UniquePaymentID'; // auto_increment = true
FeHeader::$primaryKey = 'id'; // auto_increment = true
PurOrdrHeaderExp::$primaryKey = 'ID'; // verificar
SalesInvoiceHeaderExp::$primaryKey = 'id'; // verificar
SalesOrderHeaderExp::$primaryKey = 'ID'; // verificar
```

---

## Análisis del Código Actual

### Implementación Parcial Existente

**Ubicación:** `app/Jobs/SyncOrganizationToCompanyJob.php` (Líneas 144-166)

```php
// Para GJE_Header_Imp: mapear TransactionID origen a source_transaction_id
if ($table === 'GJE_Header_Imp' && isset($recordArray['TransactionID'])) {
 $filteredRecord['source_transaction_id'] = $recordArray['TransactionID'];
 // No enviar TransactionID para que sea AUTO_INCREMENT en consolidación
 unset($filteredRecord['TransactionID']);
}
```

**ESTA ES LA ESTRATEGIA CORRECTA**

**PROBLEMA:** Solo está aplicada a `GJE_Header_Imp`, faltan 5 tablas más (o 8 si incluimos las Export).

### Comportamiento Actual del Chunk Processing

```php
DB::table($table)->orderBy($orderByColumn)->chunk($chunkSize, function ($records) use (...) {
 DB::connection()->useDatabase($company->database);
 $batchData = [];
 
 foreach ($records as $record) {
 $recordArray = (array) $record;
 $filteredRecord = [];
 
 // Copia columnas...
 $filteredRecord['org_source_id'] = $organization->id;
 $batchData[] = $filteredRecord;
 }
 
 if (!empty($batchData)) {
 $this->insertBatch($table, $batchData); // No captura IDs generados
 $syncedCount += count($batchData);
 }
});
```

**Problema:** `insertBatch()` usa `INSERT ... ON DUPLICATE KEY UPDATE` pero no retorna los IDs generados.

---

## Escenario de Colisión

### Ejemplo Práctico: Customer_Credit_Memo

#### Organización A (BD: `9_734_1672_56`, org_id: 100)

```sql
-- Customer_Credit_Memo_Header_Imp
TransactionID | ID_compania | CreditNumber | CustomerID | org_source_id
1 | 50 | CM001 | CUST001 | NULL
2 | 50 | CM002 | CUST002 | NULL

-- Customer_Credit_Memo_Detail_Imp
DetailID | TransactionID | Item_id | Description | org_source_id
1 | 1 | PROD001 | Product A | NULL
2 | 1 | PROD002 | Product B | NULL
3 | 2 | PROD003 | Product C | NULL
```

#### Organización B (BD: `9_734_1672_57`, org_id: 101)

```sql
-- Customer_Credit_Memo_Header_Imp
TransactionID | ID_compania | CreditNumber | CustomerID | org_source_id
1 | 51 | CM100 | CUST100 | NULL
2 | 51 | CM101 | CUST101 | NULL

-- Customer_Credit_Memo_Detail_Imp
DetailID | TransactionID | Item_id | Description | org_source_id
1 | 1 | SERV001 | Service X | NULL
2 | 1 | SERV002 | Service Y | NULL
3 | 2 | SERV003 | Service Z | NULL
```

#### Resultado Sin Estrategia (Colisión)

```sql
-- Company BD Consolidada
-- Customer_Credit_Memo_Header_Imp
TransactionID | ID_compania | CreditNumber | org_source_id | source_transaction_id
1 | 50 | CM001 | 100 | NULL
ERROR: Duplicate entry '1' for key 'PRIMARY' al intentar insertar Org B

-- Customer_Credit_Memo_Detail_Imp
DetailID | TransactionID | Item_id | org_source_id
1 | 1 | PROD001 | 100
2 | 1 | PROD002 | 100
Detail de Org B no se puede insertar porque header falló
```

#### Resultado Con Estrategia (Correcto)

```sql
-- Company BD Consolidada
-- Customer_Credit_Memo_Header_Imp
TransactionID | ID_compania | CreditNumber | org_source_id | source_transaction_id
1 | 50 | CM001 | 100 | 1 -- Original de Org A
2 | 50 | CM002 | 100 | 2 -- Original de Org A
3 | 51 | CM100 | 101 | 1 -- Original de Org B (NUEVO ID!)
4 | 51 | CM101 | 101 | 2 -- Original de Org B (NUEVO ID!)

-- Customer_Credit_Memo_Detail_Imp
DetailID | TransactionID | Item_id | org_source_id
1 | 1 | PROD001 | 100
2 | 1 | PROD002 | 100
3 | 2 | PROD003 | 100
4 | 3 | SERV001 | 101 -- Usa NUEVO TransactionID (3)
5 | 3 | SERV002 | 101 -- Usa NUEVO TransactionID (3)
6 | 4 | SERV003 | 101 -- Usa NUEVO TransactionID (4)
```

---

## Solución Propuesta

### Estrategia: Generar Nuevos IDs + Columna Source Tracking

#### Para HEADER Tables:

1. **Agregar columna** `source_*` para guardar ID original
2. **NO enviar** el PK al insertar (dejar auto-increment generar nuevo ID)
3. **Capturar** el nuevo ID generado
4. **Mapear** `ID_nuevo → ID_original` en memoria

#### Para DETAIL Tables:

1. **Esperar** a que headers se inserten primero
2. **Usar mapeo** para actualizar FK con nuevo ID
3. **Insertar** details con nuevo ID de header

### Flujo de Sincronización

```
1. Detectar si tabla es Header con auto-increment
 ↓
2. Procesar Headers:
 - Guardar TransactionID original en source_transaction_id
 - Omitir TransactionID (auto-increment generará nuevo)
 - Capturar nuevo ID: DB::getPdo()->lastInsertId()
 - Guardar mapeo: [ID_original] => ID_nuevo
 ↓
3. Procesar Details relacionados:
 - Buscar details con FK = ID_original
 - Actualizar FK con ID_nuevo del mapeo
 - Insertar en BD consolidada
```

---

## Implementación Necesaria

### Fase 1: Migraciones de Base de Datos

#### Script SQL para Columnas Source

```sql
-- 1. Customer_Credit_Memo_Header_Imp
ALTER TABLE Customer_Credit_Memo_Header_Imp 
ADD COLUMN source_transaction_id BIGINT NULL 
COMMENT 'TransactionID original del sistema fuente'
AFTER org_source_id;

CREATE INDEX idx_source_transaction_id 
ON Customer_Credit_Memo_Header_Imp(source_transaction_id, org_source_id);

-- 2. Purchase_Header_Imp
ALTER TABLE Purchase_Header_Imp 
ADD COLUMN source_transaction_id BIGINT NULL 
COMMENT 'TransactionID original del sistema fuente'
AFTER org_source_id;

CREATE INDEX idx_purchase_source_transaction 
ON Purchase_Header_Imp(source_transaction_id, org_source_id);

-- 3. customer_receipt_header_imp
ALTER TABLE customer_receipt_header_imp 
ADD COLUMN source_receipt_id BIGINT NULL 
COMMENT 'UniqueReceiptID original del sistema fuente'
AFTER org_source_id;

CREATE INDEX idx_receipt_source_id 
ON customer_receipt_header_imp(source_receipt_id, org_source_id);

-- 4. vendor_payment_header_imp
ALTER TABLE vendor_payment_header_imp 
ADD COLUMN source_payment_id BIGINT NULL 
COMMENT 'UniquePaymentID original del sistema fuente'
AFTER org_source_id;

CREATE INDEX idx_payment_source_id 
ON vendor_payment_header_imp(source_payment_id, org_source_id);

-- 5. fe_header
ALTER TABLE fe_header 
ADD COLUMN source_fe_id BIGINT NULL 
COMMENT 'ID original del sistema fuente'
AFTER org_source_id;

CREATE INDEX idx_fe_source_id 
ON fe_header(source_fe_id, org_source_id);

-- Nota: GJE_Header_Imp YA TIENE source_transaction_id
```

#### Comando Artisan para Ejecutar

```bash
docker exec -it docucenter-app-1 php artisan make:command Database:AddSourceColumnsToConsolidation
```

### Fase 2: Actualizar Modelos

#### Agregar a $fillable en cada modelo

```php
// app/Models/CustomerCreditMemoHeaderImp.php
protected $fillable = [
 'ID_compania',
 'org_source_id',
 'source_transaction_id', // ← AGREGAR
 'CreditNumber',
 // ... resto de campos
];

// app/Models/PurchaseHeaderImp.php
protected $fillable = [
 'ID_compania',
 'org_source_id',
 'source_transaction_id', // ← AGREGAR
 'PurchaseNumber',
 // ... resto de campos
];

// app/Models/CustomerReceiptHeaderImp.php
protected $fillable = [
 'ID_compania',
 'org_source_id',
 'source_receipt_id', // ← AGREGAR
 'ReceiptNumber',
 // ... resto de campos
];

// app/Models/VendorPaymentHeaderImp.php
protected $fillable = [
 'ID_compania',
 'org_source_id',
 'source_payment_id', // ← AGREGAR
 'CheckNumber',
 // ... resto de campos
];

// app/Models/FeHeader.php
protected $fillable = [
 'ID_compania',
 'org_source_id',
 'source_fe_id', // ← AGREGAR
 'cufe',
 // ... resto de campos
];
```

### Fase 3: Modificar SyncOrganizationToCompanyJob

#### A. Agregar Método de Configuración

```php
/**
 * Configuración de tablas Header con auto-increment
 * 
 * @param string $table
 * @return array
 */
protected function getHeaderConfig(string $table): array
{
 $configs = [
 'Customer_Credit_Memo_Header_Imp' => [
 'pk' => 'TransactionID',
 'source_col' => 'source_transaction_id',
 'detail_table' => 'Customer_Credit_Memo_Detail_Imp',
 'detail_fk' => 'TransactionID',
 'detail_pk' => 'DetailID'
 ],
 'GJE_Header_Imp' => [
 'pk' => 'TransactionID',
 'source_col' => 'source_transaction_id',
 'detail_table' => 'GJE_Detail_Imp',
 'detail_fk' => 'TransactionID',
 'detail_pk' => 'DetailID'
 ],
 'Purchase_Header_Imp' => [
 'pk' => 'TransactionID',
 'source_col' => 'source_transaction_id',
 'detail_table' => 'Purchase_Detail_Imp',
 'detail_fk' => 'TransactionID',
 'detail_pk' => 'DetailID'
 ],
 'customer_receipt_header_imp' => [
 'pk' => 'UniqueReceiptID',
 'source_col' => 'source_receipt_id',
 'detail_table' => 'customer_receipt_detail_imp',
 'detail_fk' => 'UniqueReceiptID',
 'detail_pk' => 'ID'
 ],
 'vendor_payment_header_imp' => [
 'pk' => 'UniquePaymentID',
 'source_col' => 'source_payment_id',
 'detail_table' => 'vendor_payment_detail_imp',
 'detail_fk' => 'UniquePaymentID',
 'detail_pk' => 'ID'
 ],
 'fe_header' => [
 'pk' => 'id',
 'source_col' => 'source_fe_id',
 'detail_tables' => ['fe_detail', 'fe_payment'], // Múltiples details
 'detail_fk' => 'feHeaderId',
 'detail_pk' => 'id'
 ],
 ];
 
 return $configs[$table] ?? [];
}
```

#### B. Método para Insertar Header y Capturar ID

```php
/**
 * Insertar header individual y obtener nuevo ID generado
 * 
 * @param string $table
 * @param array $data
 * @return int
 */
protected function insertHeaderAndGetId(string $table, array $data): int
{
 try {
 DB::table($table)->insert($data);
 $newId = DB::getPdo()->lastInsertId();
 
 Log::debug("Header inserted", [
 'table' => $table,
 'new_id' => $newId,
 'source_id' => $data['source_transaction_id'] ?? $data['source_receipt_id'] ?? $data['source_payment_id'] ?? $data['source_fe_id'] ?? 'N/A'
 ]);
 
 return (int) $newId;
 } catch (\Exception $e) {
 Log::error("Error inserting header", [
 'table' => $table,
 'error' => $e->getMessage(),
 'data' => $data
 ]);
 throw $e;
 }
}
```

#### C. Método para Sincronizar Details

```php
/**
 * Sincronizar tablas detail con nuevos IDs de header
 * 
 * @param Organization $organization
 * @param Companysession $company
 * @param string $headerTable
 * @param array $config
 * @param array $idMapping
 * @return int
 */
protected function syncDetails(Organization $organization, Companysession $company, 
 string $headerTable, array $config, array $idMapping): int
{
 if (empty($idMapping)) {
 Log::warning("No ID mapping provided for {$headerTable} details");
 return 0;
 }
 
 $detailTables = $config['detail_tables'] ?? [$config['detail_table']];
 $totalSynced = 0;
 
 foreach ($detailTables as $detailTable) {
 // Cambiar a BD de organización
 DB::connection()->useDatabase($organization->database);
 
 if (!$this->tableExists($detailTable)) {
 Log::warning("Detail table {$detailTable} does not exist in org {$organization->id}");
 continue;
 }
 
 // Obtener columnas de destino
 DB::connection()->useDatabase($company->database);
 $destinationColumns = DB::getSchemaBuilder()->getColumnListing($detailTable);
 
 // Volver a origen para obtener details
 DB::connection()->useDatabase($organization->database);
 
 // Obtener details relacionados en chunks
 $chunkSize = 500;
 $syncedInTable = 0;
 
 DB::table($detailTable)
 ->whereIn($config['detail_fk'], array_keys($idMapping))
 ->orderBy($config['detail_pk'] ?? 'id')
 ->chunk($chunkSize, function ($details) use ($company, $organization, $detailTable, 
 $config, $idMapping, &$syncedInTable, 
 $destinationColumns) {
 
 DB::connection()->useDatabase($company->database);
 
 $batchData = [];
 foreach ($details as $detail) {
 $detailArray = (array) $detail;
 $oldHeaderId = $detailArray[$config['detail_fk']] ?? null;
 $newHeaderId = $idMapping[$oldHeaderId] ?? null;
 
 if (!$newHeaderId) {
 Log::warning("No mapping found for {$detailTable}", [
 'old_header_id' => $oldHeaderId,
 'detail_id' => $detailArray[$config['detail_pk']] ?? 'N/A'
 ]);
 continue;
 }
 
 $filteredDetail = [];
 
 // Copiar solo columnas que existen en destino
 foreach ($detailArray as $column => $value) {
 if (in_array($column, $destinationColumns)) {
 // No copiar PK de detail (auto-increment)
 if ($column === $config['detail_pk']) {
 continue;
 }
 $filteredDetail[$column] = $value;
 }
 }
 
 // Actualizar FK con nuevo ID de header
 $filteredDetail[$config['detail_fk']] = $newHeaderId;
 $filteredDetail['org_source_id'] = $organization->id;
 
 $batchData[] = $filteredDetail;
 }
 
 if (!empty($batchData)) {
 try {
 // Insertar batch de details
 DB::table($detailTable)->insert($batchData);
 $syncedInTable += count($batchData);
 Log::info("Synced batch of details", [
 'table' => $detailTable,
 'count' => count($batchData),
 'org_id' => $organization->id
 ]);
 } catch (\Exception $e) {
 Log::error("Error inserting detail batch", [
 'table' => $detailTable,
 'error' => $e->getMessage()
 ]);
 
 // Fallback: insertar uno por uno
 foreach ($batchData as $data) {
 try {
 DB::table($detailTable)->insert($data);
 $syncedInTable++;
 } catch (\Exception $e2) {
 Log::error("Error inserting individual detail", [
 'table' => $detailTable,
 'error' => $e2->getMessage()
 ]);
 continue;
 }
 }
 }
 }
 
 DB::connection()->useDatabase($organization->database);
 });
 
 $totalSynced += $syncedInTable;
 Log::info("Completed syncing details", [
 'table' => $detailTable,
 'total_synced' => $syncedInTable,
 'org_id' => $organization->id
 ]);
 }
 
 return $totalSynced;
}
```

#### D. Modificar Método syncTable()

```php
protected function syncTable(Organization $organization, Companysession $company, string $table): int
{
 try {
 // Cambiar a BD de organización
 DB::connection()->useDatabase($organization->database);

 // Verificar si la tabla existe
 if (!$this->tableExists($table)) {
 Log::warning("Table {$table} does not exist in organization {$organization->id}");
 return 0;
 }

 // Contar registros en la tabla de origen
 $totalInSource = DB::table($table)->count();
 Log::info("Found {$totalInSource} records in {$table} (Org DB: {$organization->database})");

 if ($totalInSource === 0) {
 return 0;
 }

 $syncedCount = 0;

 // NUEVO: Detectar si es tabla Header con auto-increment
 $headerConfig = $this->getHeaderConfig($table);
 $isHeader = !empty($headerConfig);
 $idMapping = []; // Para mapear IDs viejos → nuevos

 // Volver a BD de organización
 DB::connection()->useDatabase($organization->database);

 // Determinar columna para ordenar
 $orderByColumn = $this->getOrderByColumn($table, $organization->database);

 // Usar chunk size más pequeño para tablas con datos grandes
 $chunkSize = in_array($table, ['Sales_Header_Imp', 'Purchase_Header_Imp', 'fe_header']) ? 10 : 500;
 Log::info("Processing {$table} with chunk size: {$chunkSize}");

 // Obtener columnas de destino ANTES de procesar
 DB::connection()->useDatabase($company->database);
 $destinationColumns = DB::getSchemaBuilder()->getColumnListing($table);
 Log::info("Table {$table} has " . count($destinationColumns) . " columns in destination");

 // Volver a origen para chunk
 DB::connection()->useDatabase($organization->database);

 // Procesar en chunks
 DB::table($table)->orderBy($orderByColumn)->chunk($chunkSize, function ($records) 
 use ($company, $organization, $table, &$syncedCount, $destinationColumns, 
 $isHeader, $headerConfig, &$idMapping) {
 
 // Cambiar a BD de compañía para insertar
 DB::connection()->useDatabase($company->database);

 $batchData = [];
 
 foreach ($records as $record) {
 $recordArray = (array) $record;
 $filteredRecord = [];

 // Copiar TODAS las columnas que existen en destino
 foreach ($recordArray as $column => $value) {
 if (in_array($column, $destinationColumns)) {
 // Convertir NULL a string vacío para columnas específicas
 if ($value === null && in_array($column, ['VendorType', 'ExpensesAccountId'])) {
 $value = '';
 }
 $filteredRecord[$column] = $value;
 }
 }

 // NUEVO: Manejo especial para Headers con auto-increment
 if ($isHeader) {
 $oldId = $recordArray[$headerConfig['pk']] ?? null;
 if ($oldId) {
 // Guardar ID original en columna source_*
 $filteredRecord[$headerConfig['source_col']] = $oldId;
 // NO enviar PK para que auto-increment genere nuevo ID
 unset($filteredRecord[$headerConfig['pk']]);
 }
 }

 // Agregar org_source_id
 $filteredRecord['org_source_id'] = $organization->id;
 $batchData[] = $filteredRecord;
 }

 if (!empty($batchData)) {
 // NUEVO: Si es header, insertar uno por uno para capturar IDs
 if ($isHeader) {
 foreach ($batchData as $data) {
 try {
 $newId = $this->insertHeaderAndGetId($table, $data);
 $oldId = $data[$headerConfig['source_col']];
 $idMapping[$oldId] = $newId; // Guardar mapeo
 $syncedCount++;
 } catch (\Exception $e) {
 Log::error("Failed to insert header record", [
 'table' => $table,
 'old_id' => $data[$headerConfig['source_col']] ?? 'N/A',
 'error' => $e->getMessage()
 ]);
 }
 }
 } else {
 // Para tablas normales (no headers), usar batch insert
 $this->insertBatch($table, $batchData);
 $syncedCount += count($batchData);
 }
 }

 // Volver a BD de organización para siguiente chunk
 DB::connection()->useDatabase($organization->database);
 });

 Log::info("Synced {$syncedCount} records from {$table} (Org: {$organization->id})");

 // NUEVO: Si es header, sincronizar sus details
 if ($isHeader && !empty($idMapping)) {
 $detailsSynced = $this->syncDetails($organization, $company, $table, $headerConfig, $idMapping);
 Log::info("Synced {$detailsSynced} detail records for {$table}");
 }

 return $syncedCount;

 } catch (\Exception $e) {
 Log::error("Error syncing table {$table}: " . $e->getMessage());
 return 0;
 }
}
```

---

## Consideraciones Adicionales

### 1. Tablas Export (PurOrdr, SalesInvoice, SalesOrder)

**ACCIÓN REQUERIDA:** Verificar en stubs SQL si tienen `AUTO_INCREMENT`:

```bash
# Buscar en stubs
grep -r "AUTO_INCREMENT" app/Models/stubs/*.sql.stub | grep -E "(PurOrdr|SalesInvoice|SalesOrder)"
```

**Si NO tienen auto-increment:**
- No necesitan este tratamiento
- Los TransactionID son asignados manualmente por Sage50
- Ya son únicos globalmente

**Si SÍ tienen auto-increment:**
- Agregar a la configuración `getHeaderConfig()`
- Seguir mismo proceso que Import tables

### 2. Tabla fe_header Especial

**Particularidades:**
- Tiene **2 tablas detail**: `fe_detail` y `fe_payment`
- Ambas usan `feHeaderId` como FK
- Ya está considerado en `getHeaderConfig()` con `detail_tables` (array)

### 3. Índices Únicos en Companysession

**Ubicación:** `app/Models/Companysession.php` método `getUniqueIndexConfig()`

**Actualizar para incluir columnas source_*:**

```php
// Import tables - Headers
'Customer_Credit_Memo_Header_Imp' => ['source_transaction_id', 'org_source_id'],
'GJE_Header_Imp' => ['source_transaction_id', 'org_source_id'],
'Purchase_Header_Imp' => ['source_transaction_id', 'org_source_id'],
'customer_receipt_header_imp' => ['source_receipt_id', 'org_source_id'],
'vendor_payment_header_imp' => ['source_payment_id', 'org_source_id'],
'fe_header' => ['source_fe_id', 'org_source_id'],
```

### 4. Performance y Memoria

**Preocupación:** El array `$idMapping` puede crecer mucho con muchos registros.

**Solución:**
1. Procesar headers en chunks pequeños (ya implementado: 10-500)
2. Sincronizar details inmediatamente después de cada chunk de headers
3. Liberar memoria del mapeo después de procesar details

**Alternativa avanzada (si es necesario):**
- Guardar mapeos temporalmente en Redis o tabla temporal
- Clave: `sync:{org_id}:{table}:{old_id} => {new_id}`

---

## Plan de Acción

### Sprint 1: Preparación y Testing (Estimado: 3-4 horas)

- [ ] **Tarea 1.1:** Verificar auto-increment en tablas Export
 - Revisar stubs SQL
 - Documentar hallazgos
 - Actualizar lista de tablas afectadas

- [ ] **Tarea 1.2:** Crear script de migración para columnas source_*
 - Crear comando Artisan
 - Generar SQL para cada tabla
 - Testing en BD de desarrollo

- [ ] **Tarea 1.3:** Actualizar modelos con nuevos campos fillable
 - 5 modelos Header
 - Verificar casting si es necesario
 - Commit: "feat: agregar columnas source para consolidación"

### Sprint 2: Implementación Core (Estimado: 4-5 horas)

- [ ] **Tarea 2.1:** Implementar `getHeaderConfig()`
 - Agregar al job
 - Configurar 6 tablas confirmadas
 - Testing unitario de configuración

- [ ] **Tarea 2.2:** Implementar `insertHeaderAndGetId()`
 - Manejo de errores
 - Logging detallado
 - Testing con transacciones

- [ ] **Tarea 2.3:** Implementar `syncDetails()`
 - Soporte para múltiples detail tables (fe_header)
 - Chunk processing
 - Validación de FK

- [ ] **Tarea 2.4:** Modificar `syncTable()`
 - Integrar nueva lógica
 - Mantener backward compatibility
 - Testing con datos reales

### Sprint 3: Testing y Validación (Estimado: 3-4 horas)

- [ ] **Tarea 3.1:** Testing con datos de prueba
 - Crear 2 organizaciones con IDs duplicados
 - Ejecutar consolidación
 - Verificar mapeos correctos

- [ ] **Tarea 3.2:** Verificar integridad de datos
 - Contar registros origen vs destino
 - Validar relaciones Header-Detail
 - Verificar org_source_id en todos los registros

- [ ] **Tarea 3.3:** Testing de edge cases
 - Headers sin details
 - Details huérfanos
 - TransactionID NULL
 - Registros muy grandes

- [ ] **Tarea 3.4:** Performance testing
 - Medir tiempo con 1000+ headers
 - Monitorear uso de memoria
 - Optimizar si es necesario

### Sprint 4: Documentación y Deploy (Estimado: 2-3 horas)

- [ ] **Tarea 4.1:** Documentar cambios
 - Actualizar este documento con resultados
 - Crear guía de troubleshooting
 - Documentar en docs/technical/

- [ ] **Tarea 4.2:** Code review
 - Revisión de lógica
 - Verificar convenciones
 - Testing de rollback

- [ ] **Tarea 4.3:** Deploy a producción
 - Backup de BD
 - Ejecutar migraciones
 - Monitoring post-deploy

---

## Testing

### Test Case 1: Headers con IDs Duplicados

**Setup:**
```sql
-- Org A (ID: 100)
INSERT INTO Customer_Credit_Memo_Header_Imp (TransactionID, CreditNumber, org_source_id)
VALUES (1, 'CM001', NULL);

-- Org B (ID: 101) 
INSERT INTO Customer_Credit_Memo_Header_Imp (TransactionID, CreditNumber, org_source_id)
VALUES (1, 'CM100', NULL);
```

**Ejecución:**
```bash
docker exec -it docucenter-app-1 php artisan sync:organization-to-company 100 5
docker exec -it docucenter-app-1 php artisan sync:organization-to-company 101 5
```

**Verificación:**
```sql
SELECT TransactionID, CreditNumber, org_source_id, source_transaction_id 
FROM Customer_Credit_Memo_Header_Imp 
ORDER BY TransactionID;

-- Esperado:
-- TransactionID | CreditNumber | org_source_id | source_transaction_id
-- 1 | CM001 | 100 | 1
-- 2 | CM100 | 101 | 1 ← Nuevo ID, source preservado
```

### Test Case 2: Relaciones Header-Detail

**Setup:**
```sql
-- Org A
INSERT INTO Customer_Credit_Memo_Header_Imp (TransactionID, CreditNumber)
VALUES (5, 'CM005');

INSERT INTO Customer_Credit_Memo_Detail_Imp (DetailID, TransactionID, Item_id)
VALUES (1, 5, 'ITEM001'), (2, 5, 'ITEM002');
```

**Verificación:**
```sql
-- Verificar que details usen el NUEVO TransactionID
SELECT h.TransactionID as new_id, h.source_transaction_id as old_id, 
 d.DetailID, d.TransactionID as detail_fk, d.Item_id
FROM Customer_Credit_Memo_Header_Imp h
JOIN Customer_Credit_Memo_Detail_Imp d ON d.TransactionID = h.TransactionID
WHERE h.org_source_id = 100;

-- Esperado: detail_fk == new_id (no old_id)
```

### Test Case 3: fe_header con Múltiples Details

**Setup:**
```sql
-- fe_header con id=10
INSERT INTO fe_header (id, cufe, org_source_id) VALUES (10, 'CUFE123', NULL);

-- 2 tablas detail
INSERT INTO fe_detail (id, feHeaderId, dCodProd) VALUES (1, 10, 'PROD1');
INSERT INTO fe_payment (id, feHeaderId, iFormaPago) VALUES (1, 10, 1);
```

**Verificación:**
```sql
SELECT 
 h.id as new_header_id, 
 h.source_fe_id as old_header_id,
 d.feHeaderId as detail_fk,
 p.feHeaderId as payment_fk
FROM fe_header h
LEFT JOIN fe_detail d ON d.feHeaderId = h.id
LEFT JOIN fe_payment p ON p.feHeaderId = h.id
WHERE h.org_source_id = 100;

-- Verificar que ambos details usen nuevo ID
```

### Test Case 4: Performance con Volumen

**Setup:**
```php
// Crear 1000 headers con 5 details cada uno
for ($i = 1; $i <= 1000; $i++) {
 DB::table('Customer_Credit_Memo_Header_Imp')->insert([
 'TransactionID' => $i,
 'CreditNumber' => "CM{$i}",
 'ID_compania' => 50
 ]);
 
 for ($j = 1; $j <= 5; $j++) {
 DB::table('Customer_Credit_Memo_Detail_Imp')->insert([
 'TransactionID' => $i,
 'Item_id' => "ITEM{$i}_{$j}"
 ]);
 }
}
```

**Métricas a medir:**
- Tiempo de ejecución
- Uso de memoria (pico)
- Cantidad de queries ejecutadas
- Registros sincronizados vs esperados

**Límites aceptables:**
- Tiempo: < 5 minutos para 1000 headers + 5000 details
- Memoria: < 512MB
- Éxito: 100% de registros sincronizados

---

## Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|--------|--------------|---------|------------|
| 1 | Pérdida de referencia a ID original | Baja | Alto | Columnas `source_*` preservan ID original |
| 2 | Colisión de IDs en consolidación | Alta | Crítico | Auto-increment genera nuevos IDs |
| 3 | Details huérfanos (sin header) | Media | Alto | Validar FK antes de insertar, logging de errores |
| 4 | Mapeo incorrecto de IDs | Media | Crítico | Testing exhaustivo, logs detallados |
| 5 | Performance degradado con muchos registros | Media | Medio | Chunk processing, liberar memoria |
| 6 | Fallo en medio de sincronización | Media | Alto | Transacciones por chunk, retry logic |
| 7 | Sage50 Connector no encuentra registros | Baja | Alto | Usar `source_*` + `org_source_id` para búsqueda |
| 8 | Columnas `source_*` no existen en BD antigua | Baja | Medio | Migración verifica existencia, agrega si falta |

### Plan de Rollback

Si algo falla durante implementación:

1. **Backup completo** de BD de company antes de sincronizar
2. **Logging detallado** de cada operación para debugging
3. **Comando de rollback**:
 ```bash
 docker exec -it docucenter-app-1 php artisan sync:rollback-company 5
 ```
4. **Restaurar desde backup** si es necesario

---

## Métricas de Éxito

### Criterios de Aceptación

- Todos los headers se sincronizan sin colisiones de PK
- Todos los details mantienen relación correcta con nuevos headers
- 100% de registros incluyen `org_source_id` correcto
- Columnas `source_*` contienen IDs originales
- No hay details huérfanos
- Performance aceptable (< 5 min para 1000 headers)
- Logging completo para troubleshooting
- Tests pasan al 100%

### KPIs Post-Implementación

| Métrica | Objetivo | Cómo Medir |
|---------|----------|------------|
| Tiempo de sincronización | < 5 min / 1000 headers | Logs del job |
| Tasa de éxito | 100% | count(headers) origen == destino |
| Integridad de relaciones | 100% | count(details) con FK válido |
| Memoria usada | < 512MB | Monitoring del job |
| Errores en logs | 0 errors críticos | Verificar logs después |

---

## Referencias

### Código Relacionado

- `app/Jobs/SyncOrganizationToCompanyJob.php` - Job principal de consolidación
- `app/Models/Companysession.php` - Configuración de índices únicos
- `app/Models/*HeaderImp.php` - Modelos de tablas header
- `app/Models/*DetailImp.php` - Modelos de tablas detail

### Documentación Relacionada

- [Consolidation System](./consolidation-system.md) - Sistema completo de consolidación
- [Multi-Tenant Architecture](./multi-tenant-architecture.md) - Arquitectura multi-tenant
- [Database Switching Pattern](./database-switching.md) - Patrón de cambio de BD

---

## Historial de Cambios

| Fecha | Versión | Cambios | Autor |
|-------|---------|---------|-------|
| 2025-12-18 | 1.0 | Documento inicial de análisis y estrategia | Sistema |

---

## Próximos Pasos

1. **Validar tablas Export** - Verificar auto-increment en stubs SQL
2. **Crear migraciones** - Script para agregar columnas source_*
3. **Actualizar modelos** - Agregar campos a $fillable
4. **Implementar código** - Modificar SyncOrganizationToCompanyJob
5. **Testing exhaustivo** - Ejecutar test cases documentados
6. **Code review** - Revisión antes de merge
7. **Deploy gradual** - Primero en staging, luego producción
8. **Monitorear** - Verificar métricas post-deploy

---

**Documento vivo:** Este documento se actualizará conforme avance la implementación.
