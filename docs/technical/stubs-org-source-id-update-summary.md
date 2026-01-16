# Actualización de Stubs SQL - org_source_id

**Fecha:** 5 de diciembre de 2025  
**Objetivo:** Agregar columna `org_source_id` a todos los stubs SQL para soportar arquitectura de sincronización multi-organización

## Resumen Ejecutivo

Se actualizaron exitosamente **38 stubs SQL** (29 automáticamente + 9 manualmente) para incluir la columna `org_source_id`, necesaria para la sincronización de datos entre múltiples organizaciones y la BD consolidada de compañía.

## Cambios Implementados

### Columna Agregada

```sql
`org_source_id` bigint(20) unsigned NULL
```

**Características:**
- Tipo: `BIGINT(20) UNSIGNED`
- Nullable: `NULL` (en BDs de organizaciones queda NULL, solo se usa en BD de compañía)
- Posición: Después de `ID_compania` o variantes (`ID_Compania`, `id_compania`)

### Índice Agregado

```sql
KEY `{tabla}_org_source_id_index` (`org_source_id`)
```

**Características:**
- Nombrado dinámicamente según tabla: `{nombre_tabla}_org_source_id_index`
- Posición: Después del índice de `ID_compania`
- Propósito: Optimizar consultas por organización de origen

## Archivos Actualizados

### Procesados Manualmente (9)

Tablas críticas procesadas primero manualmente:

1. ✅ `Customers_Imp.sql.stub`
2. ✅ `Vendors_Imp.sql.stub`
3. ✅ `Products_Imp.sql.stub`
4. ✅ `Sales_Header_Imp.sql.stub`
5. ✅ `Sales_Detail_Imp.sql.stub`
6. ✅ `Purchase_Header_Imp.sql.stub`
7. ✅ `Purchase_Detail_Imp.sql.stub`
8. ✅ `SalesOrder_Header_Imp.sql.stub`
9. ✅ `Jobs_Imp.sql.stub`

### Procesados Automáticamente (29)

Procesados con script Python `update-stubs-org-source-id.py`:

1. ✅ `Chart_Exp.sql.stub`
2. ✅ `CompanySession.sql.stub`
3. ✅ `Customer_Credit_Memo_Detail_Imp.sql.stub`
4. ✅ `Customer_Credit_Memo_Header_Imp.sql.stub`
5. ✅ `Customers_Exp.sql.stub`
6. ✅ `GJE_Detail_Imp.sql.stub`
7. ✅ `GJE_Header_Imp.sql.stub`
8. ✅ `InventoryAdjust_Imp.sql.stub`
9. ✅ `Job_Cost_Codes_Exp.sql.stub`
10. ✅ `Job_Phases_Exp.sql.stub`
11. ✅ `Jobs_Exp.sql.stub`
12. ✅ `Products_Exp.sql.stub`
13. ✅ `PurOrdr_Detail_Exp.sql.stub`
14. ✅ `PurOrdr_Header_Exp.sql.stub`
15. ✅ `Purchase_Detail_Exp.sql.stub`
16. ✅ `Purchase_Header_Exp.sql.stub`
17. ✅ `SageConnectTransferSummary.sql.stub`
18. ✅ `SalesInvoice_Detail_Exp.sql.stub`
19. ✅ `SalesInvoice_Header_Exp.sql.stub`
20. ✅ `SalesOrder_Detail_Exp.sql.stub`
21. ✅ `SalesOrder_Detail_Imp.sql.stub`
22. ✅ `SalesOrder_Header_Exp.sql.stub`
23. ✅ `Sales_Representative_Exp.sql.stub`
24. ✅ `Vendors_Exp.sql.stub`
25. ✅ `customer_receipt_detail_imp.sql.stub`
26. ✅ `customer_receipt_header_imp.sql.stub`
27. ✅ `fe_header.sql.stub`
28. ✅ `vendor_payment_detail_imp.sql.stub`
29. ✅ `vendor_payment_header_imp.sql.stub`

### Archivos No Procesados (7)

Stubs que no requieren cambios (no tienen `ID_compania`):

1. ⏭️ `Create_Database.sql.stub` - Script de creación de BD
2. ⏭️ `Sales_Detail_Imp_Discounts.sql.stub` - Sin ID_compania
3. ⏭️ `customer_note.sql.stub` - Tabla auxiliar
4. ⏭️ `fe_detail.sql.stub` - Relacionado con facturación electrónica
5. ⏭️ `fe_payment.sql.stub` - Relacionado con pagos
6. ⏭️ `migrations.sql.stub` - Laravel migrations
7. ⏭️ `sessions.sql.stub` - Sesiones PHP

## Scripts Creados

### 1. Script Bash - Agregar columna a BDs de Organizaciones

**Archivo:** `scripts/add-org-source-id-to-organizations.sh`

```bash
bash scripts/add-org-source-id-to-organizations.sh
```

**Función:** Usa el comando Artisan `AddColumnToOrganizationsTableCommand` para agregar `org_source_id` a TODAS las tablas en TODAS las BDs de organizaciones.

**Tablas procesadas:** 35+ tablas por organización

### 2. Script Bash - Agregar columna a BD de Compañía

**Archivo:** `scripts/add-org-source-id-to-company.sh`

```bash
bash scripts/add-org-source-id-to-company.sh
```

**Función:** Usa `ALTER TABLE` directamente en BD `company_100` para:
1. Agregar columna `org_source_id`
2. Cambiar PRIMARY KEY: `(ID)` → `(ID, org_source_id)`

### 3. Script Python - Actualizar Stubs SQL

**Archivo:** `scripts/update-stubs-org-source-id.py`

```bash
python3 scripts/update-stubs-org-source-id.py
```

**Función:** Procesamiento automático de stubs SQL para agregar:
- Columna `org_source_id` después de `ID_compania`
- Índice correspondiente

**Características:**
- Maneja variantes: `ID_compania`, `ID_Compania`, `id_compania`
- Detecta si ya tiene `org_source_id` (skip)
- Crea backups antes de modificar
- Restaura en caso de error
- Procesó 29 stubs exitosamente

## Ejemplo de Cambio

### Antes

```sql
CREATE TABLE `Customers_Imp` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ID_compania` int(11) DEFAULT NULL,
  `CustomerID` varchar(20) DEFAULT NULL,
  ...
  PRIMARY KEY (`ID`),
  KEY `customers_imp_id_compania_index` (`ID_compania`),
  KEY `customers_imp_customerid_index` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Después

```sql
CREATE TABLE `Customers_Imp` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ID_compania` int(11) DEFAULT NULL,
  `org_source_id` bigint(20) unsigned NULL,
  `CustomerID` varchar(20) DEFAULT NULL,
  ...
  PRIMARY KEY (`ID`),
  KEY `customers_imp_id_compania_index` (`ID_compania`),
  KEY `customers_imp_org_source_id_index` (`org_source_id`),
  KEY `customers_imp_customerid_index` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Arquitectura de Uso

### En BDs de Organizaciones

```
BD: 9_734_1672_56 (Org 1)
Customers_Imp:
  ID=100, ID_compania=100, org_source_id=NULL
```

- Columna existe pero **NO se usa** (queda NULL)
- PRIMARY KEY simple: `(ID)`
- Propósito: Compatibilidad de modelos Eloquent

### En BD de Compañía

```
BD: company_100
Customers_Imp:
  ID=100, ID_compania=100, org_source_id=1  (de Org 1)
  ID=100, ID_compania=100, org_source_id=2  (de Org 2)
```

- Columna **POBLADA por Job** con `org->id`
- PRIMARY KEY compuesto: `(ID, org_source_id)`
- Propósito: Identificar origen de cada registro

## Ventajas del Enfoque

1. **Compatibilidad de Modelos:** Mismo Eloquent model funciona en orgs y company
2. **Testing Simplificado:** Pruebas unitarias funcionan en cualquier BD
3. **Migraciones Uniformes:** Cambios de schema se aplican igual en todas partes
4. **Debugging Más Fácil:** Queries idénticas en ambos contextos
5. **Mantenibilidad:** Reducción de complejidad al tener schema unificado

## Próximos Pasos

1. ✅ **Stubs actualizados** - 38 de 45 archivos procesados
2. ⏳ **Ejecutar scripts de modificación BD:**
   - Ejecutar `add-org-source-id-to-organizations.sh` en producción
   - Ejecutar `add-org-source-id-to-company.sh` en producción
3. ⏳ **Actualizar modelos Eloquent:**
   - Agregar `org_source_id` al `$fillable` array
   - Documentar uso en docblocks
4. ⏳ **Implementar SyncOrganizationToCompanyJob:**
   - Job que sincroniza datos de orgs → company
   - Popula `org_source_id` durante sync
5. ⏳ **Testing:**
   - Crear factories con `org_source_id`
   - Tests de sincronización
   - Validar PRIMARY KEY compuesto

## Comandos Útiles

### Crear tabla desde stub actualizado

```bash
php artisan db:create-table-from-stub Customers_Imp --organization_id=1
```

### Verificar estructura de tabla

```bash
docker exec -it docucenter-mariadb-1 mysql -u root -proot -e \
  "USE 9_734_1672_56; DESCRIBE Customers_Imp;"
```

### Agregar columna a organizaciones existentes

```bash
docker exec -it docucenter-app-1 php artisan \
  db:add-column-to-organizations-table \
  Customers_Imp \
  org_source_id \
  bigint \
  --unsigned=1 \
  --nullable=1 \
  --index=1
```

## Notas Técnicas

- **Tipo de dato:** `BIGINT(20) UNSIGNED` para soportar IDs grandes
- **NULL permitido:** Columna nullable en organizaciones (no se usa)
- **Índice requerido:** Para performance en consultas de compañía
- **Naming convention:** `{tabla}_org_source_id_index` consistente
- **Compatibilidad:** MariaDB 10.x compatible con tipo BIGINT UNSIGNED

## Referencias

- Documentación técnica: `docs/technical/database-replication-analysis.md`
- Scripts de migración: `scripts/add-org-source-id-*.sh`
- Script de actualización: `scripts/update-stubs-org-source-id.py`

---

**Estado:** ✅ Completado  
**Total procesado:** 38 de 45 stubs SQL  
**Errores:** 0  
**Siguiente fase:** Ejecución de scripts en producción
