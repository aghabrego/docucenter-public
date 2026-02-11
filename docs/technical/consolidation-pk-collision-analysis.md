# Análisis de Colisión de PKs Auto-Incrementales en Consolidación

**Fecha:** 9 de febrero de 2026  
**Problema:** Colisión de Primary Keys auto-incrementales al consolidar múltiples organizaciones  
**Estado:** ⚠️ CRÍTICO - Parcialmente Resuelto (1 de 9 tablas)

---

## 🎯 Identificación del Problema

### Escenario Real de Colisión

```
┌─────────────────────────────────────────────────────────────────┐
│  BD Organización 1 (9_734_1672_56)                              │
├─────────────────────────────────────────────────────────────────┤
│  Sales_Header_Imp:                                              │
│    ID=100, InvoiceNumber="INV-001", CustomerID="CUST-A"        │
│  Sales_Detail_Imp:                                              │
│    SalesDetailId=1, ID=100, Item_id="PROD-X"                   │
│    SalesDetailId=2, ID=100, Item_id="PROD-Y"                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  BD Organización 2 (9_734_1672_57)                              │
├─────────────────────────────────────────────────────────────────┤
│  Sales_Header_Imp:                                              │
│    ID=100, InvoiceNumber="INV-002", CustomerID="CUST-B"        │
│  Sales_Detail_Imp:                                              │
│    SalesDetailId=1, ID=100, Item_id="PROD-Z"                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  BD Compañía Consolidada (company_100)                          │
│  ❌ PROBLEMA: COLISIÓN DE PKs                                    │
├─────────────────────────────────────────────────────────────────┤
│  Sales_Header_Imp: (Intento actual)                            │
│    ID=100, InvoiceNumber="INV-001", org_source_id=1  ✅        │
│    ID=100, InvoiceNumber="INV-002", org_source_id=2  ❌ ERROR   │
│         └─> ERROR: Duplicate entry '100' for key 'PRIMARY'     │
│                                                                │
│  Sales_Detail_Imp: (Si headers funcionaran)                    │
│    SalesDetailId=1, ID=100 (¿Cuál 100? ¿Org 1 o 2?)  ❌       │
│    SalesDetailId=2, ID=100                             ❌       │
│    SalesDetailId=1, ID=100                             ❌ DUP!  │
│         └─> ERROR: Duplicate entry '1' for key 'PRIMARY'      │
└─────────────────────────────────────────────────────────────────┘
```

### Por Qué Ocurre

1. **Cada organización tiene su BD independiente** con secuencias auto-increment separadas
2. **Org 1 y Org 2 pueden tener MISMO ID** porque sus auto-increment empiezan desde 1
3. **Al consolidar en una sola BD**, los PKs COLISIONAN
4. **Relaciones FK rompen** porque `Sales_Detail_Imp.ID` no sabe a qué header apunta

---

## 📊 Estado Actual de Implementación

### ✅ Tabla CORRECTAMENTE Implementada

#### GJE_Header_Imp (General Journal Entry)

**Estructura:**
```sql
CREATE TABLE `GJE_Header_Imp` (
    `TransactionID` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `ID_compania` BIGINT(20) NOT NULL DEFAULT '0',
    `org_source_id` bigint(20) unsigned NULL,
    `source_transaction_id` bigint(20) NULL COMMENT 'TransactionID del sistema origen',
    `Reference` VARCHAR(45) DEFAULT NULL,
    -- ... otros campos ...
    PRIMARY KEY (`TransactionID`),
    KEY `gje_header_imp_org_source_id_index` (`org_source_id`),
    UNIQUE KEY `idx_unique_record` (`source_transaction_id`, `org_source_id`)
)
```

**✅ Solución Implementada:**
```php
// En SyncOrganizationToCompanyJob.php líneas 266-271
if ($table === 'GJE_Header_Imp' && isset($recordArray['TransactionID'])) {
    // Guardar TransactionID original en source_transaction_id
    $filteredRecord['source_transaction_id'] = $recordArray['TransactionID'];
    // NO enviar TransactionID para que auto-increment genere uno nuevo
    unset($filteredRecord['TransactionID']);
}
```

**Flujo Correcto:**
```
Org 1: TransactionID=100 (original)
└─> Consolidado: TransactionID=500 (nuevo), source_transaction_id=100

Org 2: TransactionID=100 (original, mismo número!)
└─> Consolidado: TransactionID=501 (nuevo), source_transaction_id=100

✅ Sin colisiones porque auto-increment genera nuevos IDs
✅ source_transaction_id + org_source_id preservan referencia original
✅ UNIQUE (source_transaction_id, org_source_id) previene duplicados
```

---

## ❌ Tablas SIN Solución (8 tablas)

### 1. Sales_Header_Imp / Sales_Detail_Imp

**Estructura Actual:**
```sql
CREATE TABLE `Sales_Header_Imp` (
    `ID` bigint NOT NULL AUTO_INCREMENT,
    `ID_compania` bigint NOT NULL,
    `org_source_id` bigint(20) unsigned NULL,  -- ❌ NO tiene source_id
    `InvoiceNumber` varchar(20) NOT NULL,
    -- ...
    PRIMARY KEY (`ID`),
    KEY `sales_header_imp_org_source_id_index` (`org_source_id`)
    -- ❌ NO tiene UNIQUE constraint
)

CREATE TABLE `Sales_Detail_Imp` (
    `SalesDetailId` bigint NOT NULL AUTO_INCREMENT,
    `ID` bigint NOT NULL,  -- FK a Sales_Header_Imp.ID ❌ Se romperá
    -- ...
    PRIMARY KEY (`SalesDetailId`),
    KEY `sales_detail_imp_id_index` (`ID`)
)
```

**Problema:**
```php
// Código actual en SyncOrganizationToCompanyJob.php
// Líneas 253-261: NO hay manejo especial para Sales_Header_Imp

foreach ($recordArray as $column => $value) {
    if (in_array($column, $destinationColumns)) {
        $filteredRecord[$column] = $value;  // ❌ Copia ID=100 tal cual
    }
}

// ❌ ID=100 de Org1 colisiona con ID=100 de Org2
// ❌ Details quedan huérfanos porque ID cambia pero FK no se actualiza
```

### 2. Purchase_Header_Imp / Purchase_Detail_Imp

**Mismo Problema:**
```sql
CREATE TABLE `Purchase_Header_Imp` (
    `TransactionID` bigint NOT NULL AUTO_INCREMENT,  -- ❌ Colisiona
    `ID_compania` bigint NOT NULL,
    `org_source_id` bigint(20) unsigned NULL,  -- ❌ NO tiene source_transaction_id
    `PurchaseNumber` varchar(20) NOT NULL,
    -- ...
)
```

### 3. Customer_Credit_Memo_Header_Imp / Detail

```sql
CREATE TABLE `Customer_Credit_Memo_Header_Imp` (
    `TransactionID` bigint NOT NULL AUTO_INCREMENT,  -- ❌ Colisiona
    `org_source_id` bigint(20) unsigned NULL,  -- ❌ NO tiene source_transaction_id
    -- ...
)
```

### 4. customer_receipt_header_imp / detail

```sql
CREATE TABLE `customer_receipt_header_imp` (
    `UniqueReceiptID` bigint NOT NULL AUTO_INCREMENT,  -- ❌ Colisiona
    `org_source_id` bigint(20) unsigned NULL,  -- ❌ NO tiene source_receipt_id
    -- ...
)
```

### 5. vendor_payment_header_imp / detail

```sql
CREATE TABLE `vendor_payment_header_imp` (
    `UniquePaymentID` bigint NOT NULL AUTO_INCREMENT,  -- ❌ Colisiona
    `org_source_id` bigint(20) unsigned NULL,  -- ❌ NO tiene source_payment_id
    -- ...
)
```

### 6. fe_header / fe_detail / fe_payment

```sql
CREATE TABLE `fe_header` (
    `id` bigint NOT NULL AUTO_INCREMENT,  -- ❌ Colisiona
    `org_source_id` bigint(20) unsigned NULL,  -- ❌ NO tiene source_fe_id
    -- ...
)

CREATE TABLE `fe_detail` (
    `feDetailId` bigint NOT NULL AUTO_INCREMENT,
    `feHeaderId` bigint NOT NULL,  -- FK a fe_header.id ❌ Se romperá
    -- ...
)
```

---

## 🔧 Solución Propuesta

### Paso 1: Agregar Columnas `source_*` a Stubs SQL

#### Sales_Header_Imp.sql.stub
```sql
ALTER TABLE `Sales_Header_Imp` ADD COLUMN 
    `source_id` bigint NULL COMMENT 'ID del sistema origen'
    AFTER `org_source_id`;

ALTER TABLE `Sales_Header_Imp` ADD UNIQUE KEY 
    `idx_unique_sale` (`source_id`, `org_source_id`);
```

#### Purchase_Header_Imp.sql.stub
```sql
ALTER TABLE `Purchase_Header_Imp` ADD COLUMN 
    `source_transaction_id` bigint NULL COMMENT 'TransactionID del sistema origen'
    AFTER `org_source_id`;

ALTER TABLE `Purchase_Header_Imp` ADD UNIQUE KEY 
    `idx_unique_purchase` (`source_transaction_id`, `org_source_id`);
```

#### Customer_Credit_Memo_Header_Imp.sql.stub
```sql
ALTER TABLE `Customer_Credit_Memo_Header_Imp` ADD COLUMN 
    `source_transaction_id` bigint NULL COMMENT 'TransactionID del sistema origen'
    AFTER `org_source_id`;

ALTER TABLE `Customer_Credit_Memo_Header_Imp` ADD UNIQUE KEY 
    `idx_unique_credit_memo` (`source_transaction_id`, `org_source_id`);
```

#### customer_receipt_header_imp.sql.stub
```sql
ALTER TABLE `customer_receipt_header_imp` ADD COLUMN 
    `source_receipt_id` bigint NULL COMMENT 'UniqueReceiptID del sistema origen'
    AFTER `org_source_id`;

ALTER TABLE `customer_receipt_header_imp` ADD UNIQUE KEY 
    `idx_unique_receipt` (`source_receipt_id`, `org_source_id`);
```

#### vendor_payment_header_imp.sql.stub
```sql
ALTER TABLE `vendor_payment_header_imp` ADD COLUMN 
    `source_payment_id` bigint NULL COMMENT 'UniquePaymentID del sistema origen'
    AFTER `org_source_id`;

ALTER TABLE `vendor_payment_header_imp` ADD UNIQUE KEY 
    `idx_unique_payment` (`source_payment_id`, `org_source_id`);
```

#### fe_header.sql.stub
```sql
ALTER TABLE `fe_header` ADD COLUMN 
    `source_fe_id` bigint NULL COMMENT 'id del sistema origen'
    AFTER `org_source_id`;

ALTER TABLE `fe_header` ADD UNIQUE KEY 
    `idx_unique_fe` (`source_fe_id`, `org_source_id`);
```

### Paso 2: Crear Migración para BDs Existentes

```php
// database/migrations/2026_02_09_add_source_columns_consolidation.php
public function up()
{
    $tables = [
        'Sales_Header_Imp' => ['source_id', 'ID'],
        'Purchase_Header_Imp' => ['source_transaction_id', 'TransactionID'],
        'Customer_Credit_Memo_Header_Imp' => ['source_transaction_id', 'TransactionID'],
        'customer_receipt_header_imp' => ['source_receipt_id', 'UniqueReceiptID'],
        'vendor_payment_header_imp' => ['source_payment_id', 'UniquePaymentID'],
        'fe_header' => ['source_fe_id', 'id'],
    ];

    foreach ($tables as $table => [$sourceCol, $pkCol]) {
        if (!Schema::hasColumn($table, $sourceCol)) {
            Schema::table($table, function (Blueprint $table) use ($sourceCol) {
                $table->bigInteger($sourceCol)->nullable()
                    ->after('org_source_id')
                    ->comment('PK del sistema origen');
            });
        }

        // Agregar índice único
        DB::statement("
            ALTER TABLE `{$table}` 
            ADD UNIQUE KEY `idx_unique_{$table}` (`{$sourceCol}`, `org_source_id`)
        ");
    }
}
```

### Paso 3: Modificar SyncOrganizationToCompanyJob

```php
// En syncTable() método, después de línea 261
private function mapHeaderTableSourceId(string $table, array $recordArray, array &$filteredRecord): void
{
    $headerMappings = [
        'GJE_Header_Imp' => ['source' => 'TransactionID', 'target' => 'source_transaction_id'],
        'Sales_Header_Imp' => ['source' => 'ID', 'target' => 'source_id'],
        'Purchase_Header_Imp' => ['source' => 'TransactionID', 'target' => 'source_transaction_id'],
        'Customer_Credit_Memo_Header_Imp' => ['source' => 'TransactionID', 'target' => 'source_transaction_id'],
        'customer_receipt_header_imp' => ['source' => 'UniqueReceiptID', 'target' => 'source_receipt_id'],
        'vendor_payment_header_imp' => ['source' => 'UniquePaymentID', 'target' => 'source_payment_id'],
        'fe_header' => ['source' => 'id', 'target' => 'source_fe_id'],
    ];

    if (isset($headerMappings[$table])) {
        $mapping = $headerMappings[$table];
        $sourceColumn = $mapping['source'];
        $targetColumn = $mapping['target'];

        if (isset($recordArray[$sourceColumn])) {
            // Guardar PK original en columna source_*
            $filteredRecord[$targetColumn] = $recordArray[$sourceColumn];
            
            // NO enviar PK original para que auto-increment genere uno nuevo
            unset($filteredRecord[$sourceColumn]);
            
            Log::debug("Mapped {$table}: {$sourceColumn}={$recordArray[$sourceColumn]} -> {$targetColumn}");
        }
    }
}

// Reemplazar líneas 266-271 con:
$this->mapHeaderTableSourceId($table, $recordArray, $filteredRecord);
```

### Paso 4: Manejo de Relaciones FK en Details

**Problema:** Details tienen FK a Header PK que ya no existe

**Solución:** Sincronizar Headers PRIMERO, mapear IDs, luego Details

```php
// NUEVO método en SyncOrganizationToCompanyJob

protected function syncTableWithHeaderDetailRelation(
    Organization $organization,
    Companysession $company,
    string $headerTable,
    string $detailTable,
    string $headerPkSource,
    string $headerSourceCol,
    string $detailFkCol
): int {
    // FASE 1: Sincronizar Headers y crear mapa de IDs
    DB::connection()->useDatabase($organization->database);
    $idMap = [];
    
    DB::table($headerTable)->chunk(500, function ($records) use (
        $company, $organization, $headerTable, $headerPkSource, 
        $headerSourceCol, &$idMap
    ) {
        DB::connection()->useDatabase($company->database);
        
        foreach ($records as $record) {
            $recordArray = (array) $record;
            $oldId = $recordArray[$headerPkSource];
            
            // Insertar header (sin PK, auto-increment genera nuevo)
            $filteredRecord = $recordArray;
            $filteredRecord[$headerSourceCol] = $oldId;
            unset($filteredRecord[$headerPkSource]);
            $filteredRecord['org_source_id'] = $organization->id;
            
            DB::table($headerTable)->insert($filteredRecord);
            
            // Obtener nuevo ID generado
            $newId = DB::getPdo()->lastInsertId();
            
            // Mapear: oldId => newId
            $idMap[$oldId] = $newId;
            
            Log::debug("{$headerTable}: oldId={$oldId} => newId={$newId}");
        }
        
        DB::connection()->useDatabase($organization->database);
    });
    
    // FASE 2: Sincronizar Details con FK actualizada
    DB::connection()->useDatabase($organization->database);
    $syncedDetails = 0;
    
    DB::table($detailTable)->chunk(500, function ($records) use (
        $company, $organization, $detailTable, $detailFkCol, 
        $idMap, &$syncedDetails
    ) {
        DB::connection()->useDatabase($company->database);
        
        $batchData = [];
        foreach ($records as $record) {
            $recordArray = (array) $record;
            
            // Actualizar FK con nuevo ID del header
            $oldHeaderId = $recordArray[$detailFkCol];
            if (isset($idMap[$oldHeaderId])) {
                $recordArray[$detailFkCol] = $idMap[$oldHeaderId];
                $recordArray['org_source_id'] = $organization->id;
                
                // Remover PK del detail para que auto-increment genere nuevo
                unset($recordArray['SalesDetailId']); // o el PK específico
                
                $batchData[] = $recordArray;
            } else {
                Log::warning("Orphan detail: {$detailTable} FK={$oldHeaderId} not found in map");
            }
        }
        
        if (!empty($batchData)) {
            DB::table($detailTable)->insert($batchData);
            $syncedDetails += count($batchData);
        }
        
        DB::connection()->useDatabase($organization->database);
    });
    
    Log::info("Synced {$headerTable}: " . count($idMap) . " headers, {$syncedDetails} details");
    
    return count($idMap) + $syncedDetails;
}
```

### Paso 5: Modificar Lista de Sincronización

```php
// En handle() método, reemplazar líneas 174-180:

// Sincronizar tablas individuales (sin relación header-detail)
$standaloneTables = [
    'Customers_Imp',
    'Vendors_Imp',
    'Products_Imp',
    'Jobs_Imp',
    // ... otras tablas sin relaciones
];

foreach ($standaloneTables as $table) {
    $records = $this->syncTable($organization, $company, $table);
    if ($records > 0) {
        $syncedTables++;
        $syncedRecords += $records;
    }
}

// Sincronizar tablas con relaciones header-detail
$headerDetailPairs = [
    ['header' => 'GJE_Header_Imp', 'detail' => 'GJE_Detail_Imp', 
     'pk' => 'TransactionID', 'source' => 'source_transaction_id', 'fk' => 'TransactionID'],
    ['header' => 'Sales_Header_Imp', 'detail' => 'Sales_Detail_Imp', 
     'pk' => 'ID', 'source' => 'source_id', 'fk' => 'ID'],
    ['header' => 'Purchase_Header_Imp', 'detail' => 'Purchase_Detail_Imp', 
     'pk' => 'TransactionID', 'source' => 'source_transaction_id', 'fk' => 'TransactionID'],
    // ... otros pares
];

foreach ($headerDetailPairs as $pair) {
    $records = $this->syncTableWithHeaderDetailRelation(
        $organization,
        $company,
        $pair['header'],
        $pair['detail'],
        $pair['pk'],
        $pair['source'],
        $pair['fk']
    );
    $syncedRecords += $records;
    $syncedTables += 2; // header + detail
}
```

---

## 🧪 Ejemplo Completo de Flujo

### Antes de Sincronización

```
Org 1 (BD: 9_734_1672_56):
Sales_Header_Imp:
  ID=100, InvoiceNumber="INV-001"
Sales_Detail_Imp:
  SalesDetailId=1, ID=100, Item_id="PROD-X"
  SalesDetailId=2, ID=100, Item_id="PROD-Y"

Org 2 (BD: 9_734_1672_57):
Sales_Header_Imp:
  ID=100, InvoiceNumber="INV-002"
Sales_Detail_Imp:
  SalesDetailId=1, ID=100, Item_id="PROD-Z"
```

### Después de Sincronización (Con Solución)

```
Company Consolidada (BD: company_100):

Sales_Header_Imp:
  ID=500, source_id=100, org_source_id=1, InvoiceNumber="INV-001"  ✅
  ID=501, source_id=100, org_source_id=2, InvoiceNumber="INV-002"  ✅

Sales_Detail_Imp:
  SalesDetailId=1000, ID=500, org_source_id=1, Item_id="PROD-X"  ✅
  SalesDetailId=1001, ID=500, org_source_id=1, Item_id="PROD-Y"  ✅
  SalesDetailId=1002, ID=501, org_source_id=2, Item_id="PROD-Z"  ✅

✅ Sin colisiones de PK
✅ Relaciones FK preservadas
✅ Trazabilidad completa con source_id + org_source_id
```

---

## 📋 Plan de Acción

### Fase 1: Preparación (1-2 días)
- [ ] Actualizar stubs SQL con columnas `source_*`
- [ ] Crear migración para BDs existentes
- [ ] Testing en BD de desarrollo

### Fase 2: Implementación (2-3 días)
- [ ] Implementar `mapHeaderTableSourceId()`
- [ ] Implementar `syncTableWithHeaderDetailRelation()`
- [ ] Modificar `handle()` con nueva lógica
- [ ] Unit testing de mapeo de IDs

### Fase 3: Testing (2-3 días)
- [ ] Test con 2 organizaciones pequeñas
- [ ] Validar integridad referencial
- [ ] Performance testing con volumen
- [ ] Validar Sage Connector acceso

### Fase 4: Deployment (1 día)
- [ ] Deploy a staging
- [ ] Testing completo en staging
- [ ] Deploy a producción
- [ ] Monitoreo post-deploy

---

## ⚠️ Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Mapeo FK incorrecto | Media | Crítico | Testing exhaustivo, validaciones SQL |
| Details huérfanos | Media | Alto | Logging detallado, script de verificación |
| Performance degradado | Baja | Medio | Chunk processing, índices optimizados |
| Pérdida de datos | Baja | Crítico | Backups completos antes de sync |

---

## 📊 Métricas de Éxito

- ✅ 0 colisiones de PK en BD consolidada
- ✅ 100% de details con FK válida
- ✅ Tiempo de sincronización < 10 min para 1000 headers
- ✅ Sage Connector funciona sin errores
- ✅ Trazabilidad completa con source_* + org_source_id

---

**Estado Actual:** 1/9 tablas implementadas (11%)  
**Meta:** 9/9 tablas implementadas (100%)  
**Estimado:** 8-10 días de desarrollo + testing
