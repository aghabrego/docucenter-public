# Implementación de Columnas Source para Consolidación

**Fecha:** 9 de febrero, 2026  
**Estado:** ✅ Implementado y listo para despliegue  
**Prioridad:** 🔥 CRÍTICO

---

## 📋 Resumen

Se implementó la solución completa para el problema de colisión de Primary Keys en el sistema de consolidación multi-organización. La solución incluye:

1. ✅ **14 columnas source_*** para preservar PKs originales (6 headers + 8 details)
2. ✅ **UNIQUE constraints** en (source_*, org_source_id) para prevenir duplicados
3. ✅ **Sincronización en 2 fases** para mantener integridad referencial Header-Detail
4. ✅ **Scripts automatizados** para despliegue en producción

---

## 🔥 Problema Resuelto

### Antes (Sistema Vulnerable)
```sql
-- Org1: GJE_Header_Imp tiene TransactionID=100
-- Org2: GJE_Header_Imp tiene TransactionID=100
-- Consolidación: ERROR - Duplicate PK '100'

-- Org1: GJE_Detail_Imp tiene DetailID=1, FK TransactionID=100
-- Org2: GJE_Detail_Imp tiene DetailID=1, FK TransactionID=100
-- Consolidación: 
--   - DetailID=1 colisiona
--   - FK apunta a header inexistente (100 cambió a 500)
--   - Integridad referencial ROTA
```

### Después (Sistema Protegido)
```sql
-- FASE 1: Headers
-- Org1 → source_transaction_id=100, TransactionID=500 (nuevo)
-- Org2 → source_transaction_id=100, TransactionID=501 (nuevo)
-- Mapeo: {100_org1 => 500, 100_org2 => 501}

-- FASE 2: Details
-- Org1 Detail → source_detail_id=1, DetailID=1000 (nuevo), FK=500 ✅
-- Org2 Detail → source_detail_id=1, DetailID=1001 (nuevo), FK=501 ✅
-- Integridad referencial PRESERVADA
```

---

## 📦 Componentes Implementados

### 1. Comandos Artisan

#### `db:update-stub-source-column`
**Ubicación:** `app/Console/Commands/Configuration/UpdateStubSourceColumnCommand.php`

**Propósito:** Actualizar stubs SQL para incluir columnas source_* y UNIQUE constraints

**Uso:**
```bash
php artisan db:update-stub-source-column {table} {column} [--after=org_source_id]
```

**Ejemplo:**
```bash
php artisan db:update-stub-source-column Sales_Header_Imp source_id
```

#### `db:add-column-to-organizations-table` (Actualizado)
**Ubicación:** `app/Console/Commands/Configuration/AddColumnToOrganizationsTableCommand.php`

**Mejora:** Ahora detecta automáticamente columnas `source_*` y actualiza el stub antes de agregar a BDs organizacionales

### 2. Job de Sincronización

#### `SyncOrganizationToCompanyJob` (Actualizado)
**Ubicación:** `app/Jobs/SyncOrganizationToCompanyJob.php`

**Mejoras:**
- ✅ Nuevo método `syncTableWithHeaderDetailRelation()`: Sincronización en 2 fases
- ✅ Nuevo método `getHeaderDetailPairs()`: Define 8 pares Header-Detail
- ✅ Nuevo método `getSourceDetailColumn()`: Mapeo de nombres de columnas
- ✅ `handle()` actualizado: Ejecuta pares Header-Detail primero, luego tablas independientes

**Flujo de Sincronización:**
1. **FASE 1:** Sincronizar Headers → Crear mapa de IDs (oldId => newId)
2. **FASE 2:** Sincronizar Details → Actualizar FKs usando mapa + Preservar PK original

### 3. Scripts de Despliegue

#### `add-source-columns-consolidation.sh`
**Ubicación:** `scripts/add-source-columns-consolidation.sh`

**Propósito:** Agregar 14 columnas source_* a TODAS las BDs organizacionales existentes

**Ejecución:**
```bash
./scripts/add-source-columns-consolidation.sh
```

**Duración estimada:** 30-60 minutos (depende del número de organizaciones)

**Qué hace:**
1. Actualiza stub SQL automáticamente (comando lo hace)
2. Agrega columna a cada BD organizacional
3. Reporta progreso en tiempo real

#### `add-unique-constraints-source-columns.sh`
**Ubicación:** `scripts/add-unique-constraints-source-columns.sh`

**Propósito:** Agregar UNIQUE constraints a BDs de consolidación existentes

**Ejecución:**
```bash
./scripts/add-unique-constraints-source-columns.sh
```

**Duración estimada:** 5-10 minutos

**Qué hace:**
1. Obtiene compañías con `enable_consolidation=true`
2. Aplica ALTER TABLE ADD UNIQUE KEY en cada BD consolidada
3. Ignora errores si constraint ya existe

---

## 🚀 Guía de Despliegue

### Paso 1: Backup (OBLIGATORIO)
```bash
# Backup de BDs organizacionales
docker exec docucenter-app-1 php artisan backup:database:all

# Backup de BDs de consolidación
docker exec docucenter-app-1 php artisan backup:database:consolidation
```

### Paso 2: Agregar Columnas Source en BDs Organizacionales
```bash
./scripts/add-source-columns-consolidation.sh
```

**⏱ Tiempo:** ~30-60 minutos  
**Output esperado:**
```
✅ Completado: Sales_Header_Imp.source_id
✅ Completado: GJE_Detail_Imp.source_detail_id
...
✅ PARTE 2 COMPLETADA: 8 columnas source en Details
Total: 14 columnas agregadas
```

### Paso 3: Agregar UNIQUE Constraints en BDs de Consolidación
```bash
./scripts/add-unique-constraints-source-columns.sh
```

**⏱ Tiempo:** ~5-10 minutos  
**Output esperado:**
```
✅ Constraints agregados en: company_100_consolidation
✅ Constraints agregados en: company_101_consolidation
...
✅ UNIQUE constraints agregados en todas las BDs de consolidación
```

### Paso 4: Verificar Implementación
```bash
# Verificar columna en BD organizacional
docker exec -it docucenter-app-1 mysql -u root -p${MYSQL_ROOT_PASSWORD} 9_734_1672_56 \
  -e "DESCRIBE Sales_Header_Imp" | grep source_id

# Verificar UNIQUE constraint en BD consolidada
docker exec -it docucenter-app-1 mysql -u root -p${MYSQL_ROOT_PASSWORD} company_100 \
  -e "SHOW INDEX FROM Sales_Header_Imp WHERE Key_name LIKE 'uk_source%'"
```

**Output esperado:**
```
source_id | bigint | YES | | NULL |
uk_source_id_org_source_id | 1 | source_id | A | ...
```

### Paso 5: Probar Sincronización
```bash
# Disparar Job de sincronización manualmente
docker exec -it docucenter-app-1 php artisan tinker
>>> App\Jobs\SyncOrganizationToCompanyJob::dispatch(1, 100);
>>> exit
```

**Verificar logs:**
```bash
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "FASE"
```

**Output esperado:**
```
[INFO] FASE 1: Syncing header table Sales_Header_Imp...
[INFO] FASE 1 completada: Sales_Header_Imp con 150 registros mapeados
[INFO] FASE 2: Syncing detail table Sales_Detail_Imp...
[INFO] FASE 2 completada: Sales_Detail_Imp
[INFO] - Details sincronizados: 450
[INFO] Completed Header-Detail pair: Sales_Header_Imp + Sales_Detail_Imp = 600 records
```

---

## 📊 Estadísticas

### Columnas Agregadas

| Tipo | Tabla | Columna | Tipo Dato | Nullable | Index |
|------|-------|---------|-----------|----------|-------|
| Header | Sales_Header_Imp | source_id | bigint(20) | YES | UNIQUE |
| Header | Purchase_Header_Imp | source_transaction_id | bigint(20) | YES | UNIQUE |
| Header | Customer_Credit_Memo_Header_Imp | source_transaction_id | bigint(20) | YES | UNIQUE |
| Header | customer_receipt_header_imp | source_receipt_id | bigint(20) | YES | UNIQUE |
| Header | vendor_payment_header_imp | source_payment_id | bigint(20) | YES | UNIQUE |
| Header | fe_header | source_fe_id | bigint(20) | YES | UNIQUE |
| Detail | GJE_Detail_Imp | source_detail_id | bigint(20) | YES | UNIQUE |
| Detail | Sales_Detail_Imp | source_sales_detail_id | bigint(20) | YES | UNIQUE |
| Detail | Purchase_Detail_Imp | source_purchase_detail_id | bigint(20) | YES | UNIQUE |
| Detail | Customer_Credit_Memo_Detail_Imp | source_credit_detail_id | bigint(20) | YES | UNIQUE |
| Detail | customer_receipt_detail_imp | source_receipt_detail_id | bigint(20) | YES | UNIQUE |
| Detail | vendor_payment_detail_imp | source_payment_detail_id | bigint(20) | YES | UNIQUE |
| Detail | fe_detail | source_fe_detail_id | bigint(20) | YES | UNIQUE |
| Detail | fe_payment | source_fe_payment_id | bigint(20) | YES | UNIQUE |

**Total:** 14 columnas

### Pares Header-Detail Sincronizados

| # | Header Table | Detail Table | Header PK | Detail PK | FK Column |
|---|-------------|-------------|-----------|-----------|-----------|
| 1 | GJE_Header_Imp | GJE_Detail_Imp | TransactionID | DetailID | TransactionID |
| 2 | Sales_Header_Imp | Sales_Detail_Imp | ID | SalesDetailId | ID |
| 3 | Purchase_Header_Imp | Purchase_Detail_Imp | TransactionID | DetailID | TransactionID |
| 4 | Customer_Credit_Memo_Header_Imp | Customer_Credit_Memo_Detail_Imp | TransactionID | DetailID | TransactionID |
| 5 | customer_receipt_header_imp | customer_receipt_detail_imp | UniqueReceiptID | DetailID | UniqueReceiptID |
| 6 | vendor_payment_header_imp | vendor_payment_detail_imp | UniquePaymentID | DetailID | UniquePaymentID |
| 7 | fe_header | fe_detail | id | id | feHeaderId |
| 8 | fe_header | fe_payment | id | id | feHeaderId |

**Total:** 8 pares (16 tablas con sincronización especial)

---

## ✅ Validaciones Post-Despliegue

### 1. Verificar Columnas en Organizaciones
```sql
-- Ejecutar en cada BD organizacional
SELECT 
    TABLE_NAME, 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME LIKE 'source_%'
ORDER BY TABLE_NAME, COLUMN_NAME;
```

**Esperado:** 14+ filas (puede haber más si hay source_transaction_id ya existente)

### 2. Verificar UNIQUE Constraints en Consolidación
```sql
-- Ejecutar en cada BD consolidada
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS COLUMNS
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND CONSTRAINT_NAME LIKE 'uk_source%'
GROUP BY TABLE_NAME, CONSTRAINT_NAME
ORDER BY TABLE_NAME;
```

**Esperado:** 14 constraints (1 por tabla)

### 3. Verificar Integridad Referencial
```sql
-- Verificar que Details tienen FKs válidas (NO huérfanos)
SELECT 
    h.TransactionID AS header_new_id,
    h.source_transaction_id AS header_original_id,
    h.org_source_id,
    COUNT(d.DetailID) AS details_count
FROM GJE_Header_Imp h
LEFT JOIN GJE_Detail_Imp d ON d.TransactionID = h.TransactionID AND d.org_source_id = h.org_source_id
WHERE h.org_source_id IS NOT NULL
GROUP BY h.TransactionID, h.source_transaction_id, h.org_source_id
HAVING details_count > 0;
```

**Esperado:** Todas las relaciones FK válidas, sin NULL

### 4. Verificar Sincronización Funcionando
```bash
# Revisar últimos logs de sincronización
docker exec -it docucenter-app-1 php artisan tinker --execute="
\$jobs = \App\Models\Job::where('queue', 'consolidation')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'payload', 'attempts', 'reserved_at', 'available_at']);
\$jobs->each(function(\$j) {
    \$payload = json_decode(\$j->payload);
    echo 'Job #' . \$j->id . ': ' . \$payload->displayName . ' - Attempts: ' . \$j->attempts . PHP_EOL;
});
"
```

---

## 🔧 Troubleshooting

### Problema 1: "Column already exists"
**Síntoma:**
```
Error: Column 'source_id' already exists in table 'Sales_Header_Imp'
```

**Solución:**
```bash
# Continuar script - el comando detecta columnas existentes automáticamente
# El script mostrará "✓ La columna 'source_id' ya existe" en lugar de error fatal
```

### Problema 2: "Orphan details"
**Síntoma:**
```
[WARNING] Orphan detail: GJE_Detail_Imp FK TransactionID=100 not found in map
```

**Causa:** Details tienen FK a headers que no existen en la organización origen

**Solución:**
```sql
-- Identificar details huérfanos en BD origen
SELECT d.* 
FROM GJE_Detail_Imp d
LEFT JOIN GJE_Header_Imp h ON h.TransactionID = d.TransactionID
WHERE h.TransactionID IS NULL;

-- Eliminar o corregir en BD origen ANTES de consolidar
DELETE FROM GJE_Detail_Imp WHERE DetailID IN (...);
```

### Problema 3: Sincronización lenta
**Síntoma:** Job tarda más de 5 minutos

**Diagnóstico:**
```bash
# Ver progreso en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "chunk\|FASE"
```

**Optimización:**
- Reducir chunk size para tablas con XML/JSON pesados
- Aumentar timeout del Job si es necesario
- Verificar índices en tablas origen

---

## 📚 Documentación Relacionada

- [consolidation-detail-tables-analysis.md](./consolidation-detail-tables-analysis.md) - Análisis completo del problema
- [consolidation-pk-collision-analysis.md](./consolidation-pk-collision-analysis.md) - Análisis de colisión PKs Headers
- [consolidation-header-detail-strategy.md](./consolidation-header-detail-strategy.md) - Estrategia original
- [database-replication-analysis.md](./database-replication-analysis.md) - Arquitectura general

---

## 🎯 Próximos Pasos (Opcional)

### Mejoras Futuras
1. ⚠️ Agregar columnas source_* a tablas Export (PurOrdr, SalesInvoice, SalesOrder)
2. ⚠️ Implementar sincronización incremental (solo registros nuevos/modificados)
3. ⚠️ Agregar métricas de performance en Dashboard
4. ⚠️ Implementar rollback automático si falla sincronización

### Monitoreo Recomendado
```bash
# Crear alerta si aparecen orphaned details
docker exec -it docucenter-app-1 php artisan schedule:run consolidation:check-orphans
```

---

**Implementado por:** Sistema AI  
**Revisado por:** [Pendiente]  
**Aprobado para producción:** [Pendiente]  
**Fecha de despliegue:** [Pendiente]
