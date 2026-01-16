# Actualización Automática de Stubs SQL

**Fecha:** 9 de diciembre de 2025  
**Objetivo:** Automatizar la actualización de archivos stub cuando se agregan columnas a bases de datos de organizaciones

## Resumen

Se ha mejorado el comando `db:add-column-to-organizations-table` para que automáticamente actualice el archivo stub SQL correspondiente antes de modificar las bases de datos de organizaciones.

## Nuevo Comando Creado

### `db:update-stub-column`

Actualiza un archivo stub SQL agregando una nueva columna con la definición correcta.

**Ubicación**: `app/Console/Commands/Configuration/UpdateStubColumnCommand.php`

**Sintaxis**:
```bash
php artisan db:update-stub-column {table} {column} {type} [opciones]
```

**Opciones**:
- `--unsigned=0|1` - Columna unsigned (para int/bigint)
- `--length=N` - Longitud de cadena (varchar)
- `--nullable=0|1` - Permitir NULL (default: 1)
- `--default=valor` - Valor por defecto
- `--autoIncrement=0|1` - Auto incremento
- `--total=N` - Total de dígitos (decimal/float/double)
- `--places=N` - Decimales (decimal/float/double)
- `--precision=N` - Precisión (datetime/time/timestamp)
- `--index=0|1` - Crear índice
- `--after=columna` - Insertar después de esta columna (default: ID_compania)

**Ejemplos**:
```bash
# Agregar columna BIGINT unsigned con índice
php artisan db:update-stub-column \
  Sales_Header_Imp \
  org_source_id \
  bigint \
  --unsigned=1 \
  --nullable=1 \
  --index=1

# Agregar columna VARCHAR
php artisan db:update-stub-column \
  Customers_Imp \
  external_id \
  string \
  --length=50 \
  --nullable=1 \
  --index=1

# Agregar columna DECIMAL
php artisan db:update-stub-column \
  Products_Imp \
  discount_rate \
  decimal \
  --total=5 \
  --places=2 \
  --nullable=1 \
  --default=0

# Agregar columna DATETIME
php artisan db:update-stub-column \
  Sales_Header_Imp \
  synced_at \
  datetime \
  --nullable=1 \
  --after=DateModified
```

## Modificación del Comando Existente

### `db:add-column-to-organizations-table` (Mejorado)

Ahora ejecuta automáticamente estos pasos:

1. **Actualiza el stub SQL** primero
2. **Agrega la columna** a todas las bases de datos de organizaciones

**Flujo de trabajo**:
```
Usuario ejecuta: php artisan db:add-column-to-organizations-table ...
    ↓
1. Actualizar stub SQL (db:update-stub-column)
    ↓
2. Confirmar actualización de stub
    ↓
3. Agregar columna a todas las organizaciones
    ↓
4. Completado
```

**Ejemplo de uso**:
```bash
# Un solo comando actualiza stub Y organizaciones
docker exec -it docucenter_laravel.test php artisan \
  db:add-column-to-organizations-table \
  Sales_Header_Imp \
  intuit_sync_status \
  string \
  --length=20 \
  --nullable=1 \
  --index=1
```

**Output esperado**:
```
=== ACTUALIZANDO STUB SQL ===
✅ Stub actualizado: /var/www/html/app/Models/stubs/Sales_Header_Imp.sql.stub
   Columna 'intuit_sync_status' agregada después de 'ID_compania'

=== ACTUALIZANDO ORGANIZACIONES ===
Columna 'intuit_sync_status' de tipo 'string' agregada a la tabla 'Sales_Header_Imp': 'db_15570208122021_26'.
Columna 'intuit_sync_status' de tipo 'string' agregada a la tabla 'Sales_Header_Imp': 'db_18257061709732_90'.
...
```

## Tipos de Datos Soportados

| Tipo Laravel | Tipo SQL | Opciones Relevantes |
|-------------|----------|---------------------|
| `string` | `varchar(N)` | `--length` |
| `integer` / `int` | `int(11)` | `--unsigned`, `--autoIncrement` |
| `bigint` / `bigInteger` | `bigint(20)` | `--unsigned`, `--autoIncrement` |
| `float` | `float(T,P)` | `--total`, `--places` |
| `double` | `double(T,P)` | `--total`, `--places` |
| `decimal` | `decimal(T,P)` | `--total`, `--places` |
| `boolean` / `tinyint` | `tinyint(1)` | - |
| `date` | `date` | - |
| `datetime` | `datetime(P)` | `--precision` |
| `time` | `time(P)` | `--precision` |
| `timestamp` | `timestamp(P)` | `--precision` |
| `text` | `text` | - |
| `longtext` | `longtext` | - |

## Lógica de Inserción de Columna

El comando inserta la nueva columna **después de**:
1. La columna especificada en `--after=columna`
2. Si no se especifica, después de `ID_compania`
3. Como fallback, después de `ID`

**Ejemplo de resultado en stub**:
```sql
CREATE TABLE `Sales_Header_Imp` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ID_compania` int(11) DEFAULT NULL,
  `org_source_id` bigint(20) unsigned NULL,  -- ← Nueva columna
  `InvoiceNumber` varchar(20) DEFAULT NULL,
  ...
  PRIMARY KEY (`ID`),
  KEY `sales_header_imp_id_compania_index` (`ID_compania`),
  KEY `sales_header_imp_org_source_id_index` (`org_source_id`),  -- ← Nuevo índice
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Ventajas del Nuevo Sistema

### ✅ Consistencia Garantizada
- Stub y organizaciones siempre sincronizados
- No se puede olvidar actualizar el stub

### ✅ Un Solo Comando
- Antes: 2 comandos separados (stub manual + organizaciones)
- Ahora: 1 comando hace todo

### ✅ Prevención de Errores
- Si stub no existe, pregunta antes de continuar
- Verifica columnas duplicadas en stub

### ✅ Nuevas Organizaciones
- Los stubs actualizados se usan para crear nuevas BDs
- Estructuras consistentes desde el inicio

## Casos de Uso

### 1. Agregar columna de sincronización
```bash
docker exec -it docucenter_laravel.test php artisan \
  db:add-column-to-organizations-table \
  Sales_Header_Imp \
  last_sync_at \
  datetime \
  --nullable=1
```

### 2. Agregar ID externo de integración
```bash
docker exec -it docucenter_laravel.test php artisan \
  db:add-column-to-organizations-table \
  Customers_Imp \
  quickbooks_id \
  string \
  --length=50 \
  --nullable=1 \
  --index=1
```

### 3. Agregar campo booleano
```bash
docker exec -it docucenter_laravel.test php artisan \
  db:add-column-to-organizations-table \
  Products_Imp \
  is_active \
  boolean \
  --nullable=0 \
  --default=1
```

### 4. Solo actualizar stub (sin tocar organizaciones)
```bash
# Para testing o preparación
docker exec -it docucenter_laravel.test php artisan \
  db:update-stub-column \
  Sales_Header_Imp \
  test_column \
  string \
  --length=100
```

## Verificación Post-Actualización

### 1. Verificar stub actualizado
```bash
cat app/Models/stubs/Sales_Header_Imp.sql.stub | grep org_source_id
```

### 2. Verificar en una organización
```bash
docker exec -it docucenter-mariadb-1 mysql -u root -proot -e \
  "USE db_18257061709732_90; DESCRIBE Sales_Header_Imp;"
```

### 3. Crear tabla en nueva BD desde stub
```bash
docker exec -it docucenter_laravel.test php artisan \
  db:create-table-from-stub Sales_Header_Imp \
  --organization_id=1
```

## Limitaciones Conocidas

1. **Columnas complejas**: Para constraints complejos (UNIQUE multi-columna, FOREIGN KEY), editar stub manualmente
2. **Posicionamiento**: Solo inserta después de una columna, no antes
3. **Índices compuestos**: Solo crea índices de una columna
4. **Comentarios SQL**: No preserva comentarios en posiciones específicas

## Troubleshooting

### Problema: Stub no se actualiza
```bash
# Verificar que el archivo existe
ls -la app/Models/stubs/Sales_Header_Imp.sql.stub

# Verificar permisos
chmod 664 app/Models/stubs/*.stub
```

### Problema: Columna ya existe en stub
```bash
# El comando detecta duplicados y avisa
# Editar manualmente si es necesario
nano app/Models/stubs/Sales_Header_Imp.sql.stub
```

### Problema: Índice no se crea correctamente
```bash
# Editar stub manualmente para índice complejo
nano app/Models/stubs/Sales_Header_Imp.sql.stub

# Agregar línea después de PRIMARY KEY:
KEY `nombre_indice` (`columna1`, `columna2`),
```

## Scripts Relacionados

- `scripts/update-stubs-org-source-id.py` - Script Python para actualización masiva
- `scripts/update-all-stubs-org-source-id.sh` - Bash wrapper para Python
- `docs/technical/stubs-org-source-id-update-summary.md` - Documentación previa

## Próximos Pasos

1. **Testing**: Probar con diferentes tipos de columnas
2. **Validación**: Verificar que stubs generan tablas correctas
3. **Documentar**: Casos edge que requieren edición manual
4. **Automatizar**: Script para verificar consistencia stub vs organizaciones

---

**Estado:** ✅ Implementado  
**Versión:** 1.0  
**Laravel:** 9.52.20  
**Archivos modificados:**
- `app/Console/Commands/Configuration/UpdateStubColumnCommand.php` (nuevo)
- `app/Console/Commands/Configuration/AddColumnToOrganizationsTableCommand.php` (mejorado)
