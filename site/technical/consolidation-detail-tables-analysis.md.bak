# Análisis Completo: Colisión de PKs en Tablas Header Y Detail

**Fecha:** 9 de febrero de 2026  
**Problema:** Doble colisión - Headers Y Details tienen PKs auto-incrementales  
**Estado:** ⚠️ CRÍTICO - Sistema Vulnerable

---

## 🔥 El Problema es DOBLE

### Problema 1: Headers con PKs Colisionan ❌
### Problema 2: Details con PKs TAMBIÉN Colisionan ❌❌

---

## 📊 Inventario Completo de Tablas Header-Detail

| # | Header Table | Header PK | Header FK? | Detail Table | Detail PK | Detail FK Column | Relación |
|---|--------------|-----------|------------|--------------|-----------|------------------|----------|
| 1 | GJE_Header_Imp | TransactionID (AI) | ❌ | GJE_Detail_Imp | DetailID (AI) | TransactionID | 1:N |
| 2 | Sales_Header_Imp | ID (AI) | ❌ | Sales_Detail_Imp | SalesDetailId (AI) | ID | 1:N |
| 3 | Purchase_Header_Imp | TransactionID (AI) | ❌ | Purchase_Detail_Imp | DetailID (AI) | TransactionID | 1:N |
| 4 | Customer_Credit_Memo_Header_Imp | TransactionID (AI) | ❌ | Customer_Credit_Memo_Detail_Imp | DetailID (AI) | TransactionID | 1:N |
| 5 | customer_receipt_header_imp | UniqueReceiptID (AI) | ❌ | customer_receipt_detail_imp | DetailID (AI) | UniqueReceiptID | 1:N |
| 6 | vendor_payment_header_imp | UniquePaymentID (AI) | ❌ | vendor_payment_detail_imp | DetailID (AI) | UniquePaymentID | 1:N |
| 7 | fe_header | id (AI) | ❌ | fe_detail | id (AI) | feHeaderId (FK!) | 1:N |
| 8 | fe_header | id (AI) | ❌ | fe_payment | id (AI) | feHeaderId (FK!) | 1:N |

**Leyenda:**
- AI = AUTO_INCREMENT
- FK = Foreign Key Constraint (con ON DELETE CASCADE en fe_*)
- 1:N = Un header puede tener múltiples details

---

## 🎯 Escenario Completo de Colisión

### Datos Originales

```sql
-- ========================================
-- Organización 1 (BD: 9_734_1672_56)
-- ========================================
GJE_Header_Imp:
  TransactionID=100, Reference="JE-001", Date="2025-01-15"

GJE_Detail_Imp:
  DetailID=1, TransactionID=100, GL_Account="1000", Amount=500.00
  DetailID=2, TransactionID=100, GL_Account="2000", Amount=-500.00

-- ========================================
-- Organización 2 (BD: 9_734_1672_57)
-- ========================================
GJE_Header_Imp:
  TransactionID=100, Reference="JE-002", Date="2025-01-20"  -- MISMO ID!

GJE_Detail_Imp:
  DetailID=1, TransactionID=100, GL_Account="3000", Amount=800.00  -- MISMO DetailID!
  DetailID=2, TransactionID=100, GL_Account="4000", Amount=-800.00 -- MISMO DetailID!
```

### ❌ Consolidación SIN Solución Correcta

```sql
-- ========================================
-- BD Compañía Consolidada (company_100)
-- ========================================

-- PASO 1: Intentar insertar Headers
GJE_Header_Imp:
  TransactionID=100, Reference="JE-001", org_source_id=1  ✅ OK
  TransactionID=100, Reference="JE-002", org_source_id=2  ❌ ERROR: Duplicate PK '100'

-- FALLA AQUÍ - No continúa a details
```

### ⚠️ Consolidación Actual (Solo Header Resuelto)

```sql
-- ========================================
-- Con implementación actual de GJE_Header_Imp
-- ========================================

GJE_Header_Imp:
  TransactionID=500, source_transaction_id=100, org_source_id=1, Reference="JE-001"  ✅
  TransactionID=501, source_transaction_id=100, org_source_id=2, Reference="JE-002"  ✅

-- Pero los Details:
GJE_Detail_Imp:
  DetailID=1, TransactionID=500, org_source_id=1, GL_Account="1000"  ✅ FK actualizada
  DetailID=2, TransactionID=500, org_source_id=1, GL_Account="2000"  ✅
  DetailID=1, TransactionID=501, org_source_id=2, GL_Account="3000"  ❌ COLISIÓN PK!
  DetailID=2, TransactionID=501, org_source_id=2, GL_Account="4000"  ❌ COLISIÓN PK!
```

**Problema:** Los Details de Org 2 tienen DetailID=1,2 que colisionan con los de Org 1!

### ✅ Consolidación CORRECTA (Solución Completa)

```sql
-- ========================================
-- Con solución propuesta completa
-- ========================================

GJE_Header_Imp:
  TransactionID=500, source_transaction_id=100, org_source_id=1  ✅
  TransactionID=501, source_transaction_id=100, org_source_id=2  ✅

GJE_Detail_Imp:
  DetailID=1000, TransactionID=500, org_source_id=1  ✅ Nuevo PK por auto-increment
  DetailID=1001, TransactionID=500, org_source_id=1  ✅ Nuevo PK
  DetailID=1002, TransactionID=501, org_source_id=2  ✅ Nuevo PK - Sin colisión!
  DetailID=1003, TransactionID=501, org_source_id=2  ✅ Nuevo PK - Sin colisión!
```

**✅ Solución:** Los Details también usan auto-increment para generar nuevos PKs

---

## 📋 Análisis Detallado por Tabla Detail

### 1. GJE_Detail_Imp ✅ Parcialmente Resuelto

```sql
CREATE TABLE `GJE_Detail_Imp` (
    `DetailID` BIGINT(20) NOT NULL AUTO_INCREMENT,      -- ❌ Colisiona entre orgs
    `TransactionID` BIGINT(20) DEFAULT NULL,            -- ✅ Se actualiza con mapeo
    `org_source_id` bigint(20) unsigned NULL,           -- ✅ Existe
    -- ...
    PRIMARY KEY (`DetailID`),
    KEY `gje_detail_imp_transactionid_index` (`TransactionID`)
)
```

**Estado:**
- ✅ Header resuelto con `source_transaction_id`
- ✅ FK `TransactionID` se actualiza con mapeo de IDs
- ❌ **FALTA:** Detail PK también colisiona, necesita auto-increment

**Lo que pasa actualmente:**
```php
// En syncTable() NO hay manejo especial para Details
// Se copia DetailID tal cual → COLISIÓN
$filteredRecord['DetailID'] = $recordArray['DetailID']; // ❌ DetailID=1 de Org1 vs Org2
```

### 2. Sales_Detail_Imp ❌ Sin Protección

```sql
CREATE TABLE `Sales_Detail_Imp` (
    `SalesDetailId` bigint NOT NULL AUTO_INCREMENT,     -- ❌ Colisiona entre orgs
    `ID` bigint NOT NULL,                               -- ❌ FK a Sales_Header_Imp.ID
    `org_source_id` bigint(20) unsigned NULL,           -- ✅ Existe
    -- ...
    PRIMARY KEY (`SalesDetailId`),
    KEY `sales_detail_imp_id_index` (`ID`)
)
```

**Problema Doble:**
1. `SalesDetailId` colisiona (Org1 SalesDetailId=1 vs Org2 SalesDetailId=1)
2. `ID` (FK) apunta a header IDs que ya no existen

**Ejemplo Real:**
```
Org 1: SalesDetailId=1, ID=100 (header), Item="PROD-X"
Org 2: SalesDetailId=1, ID=100 (header), Item="PROD-Y"

Consolidado (MALO):
  SalesDetailId=1, ID=??? (¿100 de org1 o org2?), Item="PROD-X"  ❌
  SalesDetailId=1, ID=???, Item="PROD-Y"  ❌ COLISIÓN PK!

Consolidado (CORRECTO con mapeo):
  Header Org1 ID=100 → nuevo ID=500
  Header Org2 ID=100 → nuevo ID=501
  
  SalesDetailId=2000, ID=500, org_source_id=1, Item="PROD-X"  ✅
  SalesDetailId=2001, ID=501, org_source_id=2, Item="PROD-Y"  ✅
```

### 3. Purchase_Detail_Imp ❌ Sin Protección

```sql
CREATE TABLE `Purchase_Detail_Imp` (
    `DetailID` bigint(20) NOT NULL AUTO_INCREMENT,      -- ❌ Colisiona
    `TransactionID` bigint(20) NOT NULL,                -- ❌ FK sin actualizar
    `org_source_id` bigint(20) unsigned NULL,
    -- ...
    PRIMARY KEY (`DetailID`),
    KEY `purchase_detail_imp_transactionid_index` (`TransactionID`)
)
```

### 4-6. customer_receipt / vendor_payment / credit_memo ❌ Sin Protección

Todas tienen el mismo patrón:
```sql
DetailID BIGINT(20) NOT NULL AUTO_INCREMENT,    -- ❌ Colisiona
[Header_FK] BIGINT(20) NOT NULL,                -- ❌ FK sin actualizar
```

### 7-8. fe_detail / fe_payment ❌ Sin Protección + FK Constraints!

```sql
CREATE TABLE `fe_detail` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,              -- ❌ Colisiona
  `feHeaderId` bigint(20) DEFAULT NULL,                 -- ❌ FK sin actualizar
  `org_source_id` bigint(20) unsigned NULL,
  -- ...
  PRIMARY KEY (`id`),
  KEY `fe_detail_feheaderid_foreign` (`feHeaderId`),
  CONSTRAINT `fe_detail_feheaderid_foreign` 
    FOREIGN KEY (`feHeaderId`) 
    REFERENCES `fe_header` (`id`) 
    ON DELETE CASCADE                                    -- ⚠️ CONSTRAINT activo!
)

CREATE TABLE `fe_payment` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,              -- ❌ Colisiona
  `feHeaderId` bigint(20) DEFAULT NULL,                 -- ❌ FK sin actualizar
  -- ...
  CONSTRAINT `fe_payment_feheaderid_foreign` 
    FOREIGN KEY (`feHeaderId`) 
    REFERENCES `fe_header` (`id`) 
    ON DELETE CASCADE                                    -- ⚠️ CONSTRAINT activo!
)
```

**⚠️ PROBLEMA ADICIONAL:** Foreign Key Constraints activos!

Si intentamos insertar `fe_detail` con `feHeaderId` que no existe:
```
ERROR 1452: Cannot add or update a child row: 
a foreign key constraint fails (fe_detail_feheaderid_foreign)
```

---

## 🔧 Solución Completa para Header-Detail

### Estrategia de Sincronización en 2 Fases

```php
protected function syncTableWithHeaderDetailRelation(
    Organization $organization,
    Companysession $company,
    string $headerTable,
    string $detailTable,
    string $headerPkSource,
    string $headerSourceCol,
    string $detailPkColumn,
    string $detailFkColumn
): int {
    DB::connection()->useDatabase($organization->database);
    $idMap = [];
    
    // =========================================================
    // FASE 1: Sincronizar Headers y crear mapa de IDs
    // =========================================================
    Log::info("FASE 1: Syncing {$headerTable}...");
    
    DB::table($headerTable)->chunk(500, function ($records) use (
        $company, $organization, $headerTable, $headerPkSource, 
        $headerSourceCol, &$idMap
    ) {
        DB::connection()->useDatabase($company->database);
        
        foreach ($records as $record) {
            $recordArray = (array) $record;
            $oldId = $recordArray[$headerPkSource];
            
            // Preparar record sin PK original
            $filteredRecord = $recordArray;
            $filteredRecord[$headerSourceCol] = $oldId;  // Guardar ID original
            unset($filteredRecord[$headerPkSource]);     // Remover PK para auto-increment
            $filteredRecord['org_source_id'] = $organization->id;
            
            // Insertar y obtener nuevo ID generado
            DB::table($headerTable)->insert($filteredRecord);
            $newId = DB::getPdo()->lastInsertId();
            
            // Guardar mapeo: oldId => newId
            $idMap[$oldId] = $newId;
            
            Log::debug("{$headerTable}: old {$headerPkSource}={$oldId} => new={$newId}");
        }
        
        DB::connection()->useDatabase($organization->database);
    });
    
    Log::info("FASE 1 completada: {$headerTable} con " . count($idMap) . " registros mapeados");
    
    // =========================================================
    // FASE 2: Sincronizar Details con FK actualizada
    // =========================================================
    Log::info("FASE 2: Syncing {$detailTable}...");
    
    DB::connection()->useDatabase($organization->database);
    $syncedDetails = 0;
    $orphanedDetails = 0;
    
    DB::table($detailTable)->chunk(500, function ($records) use (
        $company, $organization, $detailTable, $detailPkColumn,
        $detailFkColumn, $idMap, &$syncedDetails, &$orphanedDetails
    ) {
        DB::connection()->useDatabase($company->database);
        
        $batchData = [];
        foreach ($records as $record) {
            $recordArray = (array) $record;
            
            // Obtener FK del header original
            $oldHeaderId = $recordArray[$detailFkColumn];
            
            // ❌ Si el header no existe en el mapa, es huérfano
            if (!isset($idMap[$oldHeaderId])) {
                Log::warning("Orphan detail: {$detailTable} FK {$detailFkColumn}={$oldHeaderId} not found");
                $orphanedDetails++;
                continue;
            }
            
            // ✅ Actualizar FK con el nuevo ID del header
            $recordArray[$detailFkColumn] = $idMap[$oldHeaderId];
            
            // 🔥 CRÍTICO: Guardar PK original del Detail ANTES de eliminarlo
            $sourceDetailColumn = 'source_' . strtolower($detailPkColumn);
            $recordArray[$sourceDetailColumn] = $recordArray[$detailPkColumn];
            
            // ✅ Remover PK del detail para que auto-increment genere uno nuevo
            unset($recordArray[$detailPkColumn]);
            
            // ✅ Agregar org_source_id
            $recordArray['org_source_id'] = $organization->id;
            
            $batchData[] = $recordArray;
        }
        
        // Insertar batch de details
        if (!empty($batchData)) {
            DB::table($detailTable)->insert($batchData);
            $syncedDetails += count($batchData);
            Log::debug("Inserted batch of " . count($batchData) . " details");
        }
        
        DB::connection()->useDatabase($organization->database);
    });
    
    // Reportar resultados
    Log::info("FASE 2 completada: {$detailTable}");
    Log::info("- Details sincronizados: {$syncedDetails}");
    if ($orphanedDetails > 0) {
        Log::warning("- Details huérfanos ignorados: {$orphanedDetails}");
    }
    
    return count($idMap) + $syncedDetails;
}
```

### Llamada al Método

```php
// En handle() del Job

$headerDetailPairs = [
    [
        'header' => 'GJE_Header_Imp',
        'detail' => 'GJE_Detail_Imp',
        'header_pk' => 'TransactionID',
        'header_source' => 'source_transaction_id',
        'detail_pk' => 'DetailID',
        'detail_fk' => 'TransactionID',
    ],
    [
        'header' => 'Sales_Header_Imp',
        'detail' => 'Sales_Detail_Imp',
        'header_pk' => 'ID',
        'header_source' => 'source_id',
        'detail_pk' => 'SalesDetailId',
        'detail_fk' => 'ID',
    ],
    [
        'header' => 'Purchase_Header_Imp',
        'detail' => 'Purchase_Detail_Imp',
        'header_pk' => 'TransactionID',
        'header_source' => 'source_transaction_id',
        'detail_pk' => 'DetailID',
        'detail_fk' => 'TransactionID',
    ],
    [
        'header' => 'fe_header',
        'detail' => 'fe_detail',
        'header_pk' => 'id',
        'header_source' => 'source_fe_id',
        'detail_pk' => 'id',
        'detail_fk' => 'feHeaderId',
    ],
    [
        'header' => 'fe_header',
        'detail' => 'fe_payment',
        'header_pk' => 'id',
        'header_source' => 'source_fe_id',
        'detail_pk' => 'id',  
        'detail_fk' => 'feHeaderId',
    ],
    // ... otros pares
];

foreach ($headerDetailPairs as $pair) {
    $records = $this->syncTableWithHeaderDetailRelation(
        $organization,
        $company,
        $pair['header'],
        $pair['detail'],
        $pair['header_pk'],
        $pair['header_source'],
        $pair['detail_pk'],
        $pair['detail_fk']
    );
    
    Log::info("Sync completed: {$pair['header']} + {$pair['detail']} = {$records} records");
    $syncedRecords += $records;
    $syncedTables += 2;
}
```

---

## 📊 Tabla Resumen de Estado

| Tabla Header | Tabla Detail | Header PK | Detail PK | Detail FK | Estado Header | Estado Detail |
|--------------|--------------|-----------|-----------|-----------|---------------|---------------|
| GJE_Header_Imp | GJE_Detail_Imp | TransactionID | DetailID | TransactionID | ✅ Implementado | ❌ Vulnerable |
| Sales_Header_Imp | Sales_Detail_Imp | ID | SalesDetailId | ID | ❌ Vulnerable | ❌ Vulnerable |
| Purchase_Header_Imp | Purchase_Detail_Imp | TransactionID | DetailID | TransactionID | ❌ Vulnerable | ❌ Vulnerable |
| Customer_Credit_Memo_Header_Imp | Customer_Credit_Memo_Detail_Imp | TransactionID | DetailID | TransactionID | ❌ Vulnerable | ❌ Vulnerable |
| customer_receipt_header_imp | customer_receipt_detail_imp | UniqueReceiptID | DetailID | UniqueReceiptID | ❌ Vulnerable | ❌ Vulnerable |
| vendor_payment_header_imp | vendor_payment_detail_imp | UniquePaymentID | DetailID | UniquePaymentID | ❌ Vulnerable | ❌ Vulnerable |
| fe_header | fe_detail | id | id | feHeaderId | ❌ Vulnerable | ❌ Vulnerable + FK! |
| fe_header | fe_payment | id | id | feHeaderId | ❌ Vulnerable | ❌ Vulnerable + FK! |

**Estadísticas:**
- ✅ Headers protegidos: 1 de 7 (14%)
- ❌ Headers vulnerables: 6 de 7 (86%)
- ❌ Details protegidos: 0 de 8 (0%)
- ❌ Details vulnerables: 8 de 8 (100%)

---

## ⚠️ Riesgos Críticos de Details

| Riesgo | Probabilidad | Impacto | Descripción |
|--------|--------------|---------|-------------|
| Colisión PK en Details | **100%** | Crítico | DetailID=1 de Org1 colisiona con Org2 |
| FK apunta a header inexistente | **100%** | Crítico | Detail.ID=100 pero header cambió a ID=500 |
| Details huérfanos | Alta | Crítico | Details sin header por FK incorrecta |
| Violación FK Constraint (fe_*) | **100%** | Crítico | MySQL rechaza insert por constraint |
| Pérdida de datos | Media | Crítico | Details no se insertan por errores |
| Integridad referencial rota | **100%** | Crítico | Relación header-detail perdida |

---

## 🔥 Columnas Source Necesarias para Details

### ❌ Problema Adicional Identificado

**TODAS las tablas Detail necesitan columna para preservar PK original**, similar a como los Headers tienen `source_transaction_id`.

### Columnas Requeridas por Tabla Detail

| Tabla Detail | PK Column | **Columna Source Necesaria** | Estado |
|-------------|-----------|------------------------------|--------|
| GJE_Detail_Imp | DetailID | `source_detail_id` | ❌ NO existe |
| Sales_Detail_Imp | SalesDetailId | `source_sales_detail_id` | ❌ NO existe |
| Purchase_Detail_Imp | DetailID | `source_purchase_detail_id` | ❌ NO existe |
| Customer_Credit_Memo_Detail_Imp | DetailID | `source_credit_detail_id` | ❌ NO existe |
| customer_receipt_detail_imp | DetailID | `source_receipt_detail_id` | ❌ NO existe |
| vendor_payment_detail_imp | DetailID | `source_payment_detail_id` | ❌ NO existe |
| fe_detail | id | `source_fe_detail_id` | ❌ NO existe |
| fe_payment | id | `source_fe_payment_id` | ❌ NO existe |

### ¿Por Qué Necesitamos Estas Columnas?

```php
// ❌ CÓDIGO ACTUAL (PIERDE EL PK ORIGINAL)
$recordArray['org_source_id'] = $organization->id;
unset($recordArray[$detailPkColumn]); // Se pierde el DetailID original (ej: 1, 2, 3)
DB::table($detailTable)->insert($recordArray); // Auto-increment genera nuevo ID (ej: 500, 501, 502)

// ✅ CÓDIGO CORRECTO (PRESERVA EL PK ORIGINAL)
$recordArray['source_detail_id'] = $recordArray[$detailPkColumn]; // Guardar original (1, 2, 3)
$recordArray['org_source_id'] = $organization->id;
unset($recordArray[$detailPkColumn]); // Eliminar para auto-increment
DB::table($detailTable)->insert($recordArray); // Nuevo ID (500) + source_detail_id (1)
```

**Beneficios:**
1. ✅ Trazabilidad: Sabemos qué DetailID original tenía cada registro
2. ✅ Debugging: Podemos rastrear problemas hasta la BD origen
3. ✅ Sincronización incremental: Detectar qué Details ya se sincronizaron
4. ✅ Auditoría: Verificar integridad de datos con BD origen
5. ✅ Unique constraint: `UNIQUE (source_detail_id, org_source_id)` previene duplicados

### Migración SQL Requerida

```sql
-- ================================================================
-- Agregar columnas source_*_id a todas las tablas Detail
-- ================================================================

-- GJE_Detail_Imp
ALTER TABLE `GJE_Detail_Imp` 
ADD COLUMN `source_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_detail_org` (`source_detail_id`, `org_source_id`);

-- Sales_Detail_Imp
ALTER TABLE `Sales_Detail_Imp` 
ADD COLUMN `source_sales_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_sales_detail_org` (`source_sales_detail_id`, `org_source_id`);

-- Purchase_Detail_Imp
ALTER TABLE `Purchase_Detail_Imp` 
ADD COLUMN `source_purchase_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_purchase_detail_org` (`source_purchase_detail_id`, `org_source_id`);

-- Customer_Credit_Memo_Detail_Imp
ALTER TABLE `Customer_Credit_Memo_Detail_Imp` 
ADD COLUMN `source_credit_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_credit_detail_org` (`source_credit_detail_id`, `org_source_id`);

-- customer_receipt_detail_imp
ALTER TABLE `customer_receipt_detail_imp` 
ADD COLUMN `source_receipt_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_receipt_detail_org` (`source_receipt_detail_id`, `org_source_id`);

-- vendor_payment_detail_imp
ALTER TABLE `vendor_payment_detail_imp` 
ADD COLUMN `source_payment_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_payment_detail_org` (`source_payment_detail_id`, `org_source_id`);

-- fe_detail
ALTER TABLE `fe_detail` 
ADD COLUMN `source_fe_detail_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_fe_detail_org` (`source_fe_detail_id`, `org_source_id`);

-- fe_payment
ALTER TABLE `fe_payment` 
ADD COLUMN `source_fe_payment_id` BIGINT NULL AFTER `org_source_id`,
ADD UNIQUE KEY `uk_source_fe_payment_org` (`source_fe_payment_id`, `org_source_id`);
```

### Código PHP Actualizado con Preservación de PK

```php
// En FASE 2 de syncTableWithHeaderDetailRelation()

foreach ($records as $record) {
    $recordArray = (array) $record;
    
    // Obtener FK del header original
    $oldHeaderId = $recordArray[$detailFkColumn];
    
    // ❌ Si el header no existe en el mapa, es huérfano
    if (!isset($idMap[$oldHeaderId])) {
        Log::warning("Orphan detail: {$detailTable} FK {$detailFkColumn}={$oldHeaderId}");
        $orphanedDetails++;
        continue;
    }
    
    // ✅ Actualizar FK con el nuevo ID del header
    $recordArray[$detailFkColumn] = $idMap[$oldHeaderId];
    
    // 🔥 CRÍTICO: Guardar PK original del Detail ANTES de eliminarlo
    $sourceDetailColumn = $this->getSourceDetailColumn($detailTable, $detailPkColumn);
    $recordArray[$sourceDetailColumn] = $recordArray[$detailPkColumn];
    
    // ✅ Remover PK del detail para que auto-increment genere uno nuevo
    unset($recordArray[$detailPkColumn]);
    
    // ✅ Agregar org_source_id\n    $recordArray['org_source_id'] = $organization->id;
    
    $batchData[] = $recordArray;
}
```

### Método Helper para Nombres de Columnas

```php
/**
 * Obtener nombre de columna source_* para una tabla Detail
 */
protected function getSourceDetailColumn(string $detailTable, string $pkColumn): string
{
    $sourceColumns = [
        'GJE_Detail_Imp' => 'source_detail_id',
        'Sales_Detail_Imp' => 'source_sales_detail_id',
        'Purchase_Detail_Imp' => 'source_purchase_detail_id',
        'Customer_Credit_Memo_Detail_Imp' => 'source_credit_detail_id',
        'customer_receipt_detail_imp' => 'source_receipt_detail_id',
        'vendor_payment_detail_imp' => 'source_payment_detail_id',
        'fe_detail' => 'source_fe_detail_id',
        'fe_payment' => 'source_fe_payment_id',
    ];
    
    return $sourceColumns[$detailTable] ?? 'source_' . strtolower($pkColumn);
}
```

---

## ✅ Métricas de Éxito

### Validaciones Post-Sincronización

```sql
-- 1. Verificar que NO hay colisiones de PK en Details
SELECT 
    d.org_source_id,
    COUNT(*) as registros,
    COUNT(DISTINCT d.DetailID) as details_unicos,
    CASE WHEN COUNT(*) = COUNT(DISTINCT d.DetailID) THEN '✅ OK' ELSE '❌ FAIL' END as estado
FROM GJE_Detail_Imp d
WHERE d.org_source_id IS NOT NULL
GROUP BY d.org_source_id;

-- 2. Verificar que TODOS los details tienen header válido
SELECT 
    d.org_source_id,
    COUNT(*) as total_details,
    COUNT(h.TransactionID) as con_header_valido,
    SUM(CASE WHEN h.TransactionID IS NULL THEN 1 ELSE 0 END) as detalles_huerfanos
FROM GJE_Detail_Imp d
LEFT JOIN GJE_Header_Imp h ON d.TransactionID = h.TransactionID AND d.org_source_id = h.org_source_id
WHERE d.org_source_id IS NOT NULL
GROUP BY d.org_source_id;

-- Debe retornar detalles_huerfanos = 0

-- 3. Verificar conteo de registros por organización
SELECT 
    org_source_id,
    'Headers' as tipo,
    COUNT(*) as total
FROM GJE_Header_Imp
WHERE org_source_id IS NOT NULL
GROUP BY org_source_id

UNION ALL

SELECT 
    org_source_id,
    'Details' as tipo,
    COUNT(*) as total
FROM GJE_Detail_Imp
WHERE org_source_id IS NOT NULL
GROUP BY org_source_id
ORDER BY org_source_id, tipo;
```

---

## 🎯 Conclusión

### El Problema es MÁS GRAVE de lo Documentado

1. ❌ **Headers colisionan** (ya documentado en consolidation-pk-collision-analysis.md)
2. ❌ **Details TAMBIÉN colisionan** (añadido ahora)
3. ❌ **FKs se rompen** (añadido ahora)
4. ❌ **FK Constraints activos en fe_*** (añadido ahora)
5. ❌ **Columnas source_detail_id NO existen** (añadido ahora)

### Estado Real de Implementación

**Headers:**
- Solo **1 de 7 headers** (14%) tiene `source_transaction_id` implementado
- **6 de 7 headers** (86%) necesitan columnas source_*

**Details:**
- **0 de 8 details** (0%) tienen columna `source_detail_id`
- **0 de 8 details** (0%) tienen protección contra colisión PK
- **8 de 8 details** (100%) pierden su PK original al sincronizar
- **100% de las relaciones FK** se romperán sin la solución

### Columnas Faltantes

**En Headers (6 de 7):**
- `source_id` para Sales_Header_Imp
- `source_transaction_id` para Purchase_Header_Imp, Customer_Credit_Memo_Header_Imp
- `source_receipt_id` para customer_receipt_header_imp  
- `source_payment_id` para vendor_payment_header_imp
- `source_fe_id` para fe_header

**En Details (8 de 8):**
- `source_detail_id` para GJE_Detail_Imp
- `source_sales_detail_id` para Sales_Detail_Imp
- `source_purchase_detail_id` para Purchase_Detail_Imp
- `source_credit_detail_id` para Customer_Credit_Memo_Detail_Imp
- `source_receipt_detail_id` para customer_receipt_detail_imp
- `source_payment_detail_id` para vendor_payment_detail_imp
- `source_fe_detail_id` para fe_detail
- `source_fe_payment_id` para fe_payment

**Total:** 14 columnas faltantes (6 headers + 8 details)

### Solución Propuesta

**REQUISITO CRÍTICO:** Sincronización en **2 FASES obligatorias**:
1. **FASE 1:** Headers primero, generar mapa de IDs (oldId => newId)
2. **FASE 2:** Details después, actualizar FKs con mapa + guardar source_detail_id

**Pasos de Implementación:**
1. ✅ Crear migración para agregar 14 columnas source_*
2. ✅ Agregar UNIQUE constraints en (source_*, org_source_id)
3. ✅ Implementar método `syncTableWithHeaderDetailRelation()`
4. ✅ Implementar método helper `getSourceDetailColumn()`
5. ✅ Actualizar código para preservar PKs originales antes de unset()
6. ✅ Testing exhaustivo de integridad referencial

**Sin esta solución completa, el sistema de consolidación NO FUNCIONA correctamente.**

---

**Prioridad:** 🔥 CRÍTICA  
**Impacto:** Sistema completamente vulnerable - Pérdida de datos e integridad  
**Estimado:** 12-15 días con testing exhaustivo (aumentado por columnas adicionales)
