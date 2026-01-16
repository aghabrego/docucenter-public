# Comandos de Producción - Solución Anti-Loop QuickBooks

## Resumen

Scripts para agregar las columnas `origin` e `intuit_sync_attempts` a la tabla `Sales_Header_Imp` en **todas las bases de datos de organizaciones** usando el comando `AddColumnToOrganizationsTableCommand`.

## Columnas a Agregar

### 1. **Columna `origin`**
```sql
`origin` varchar(20) NULL DEFAULT 'docucenter',
KEY `sales_header_imp_origin_index` (`origin`)
```

**Propósito**: Identificar el origen de cada factura
**Valores**:
- `'docucenter'`: Facturas creadas en DocuCenter (default)
- `'quickbooks'`: Facturas recibidas de QuickBooks
- `'shopify'`, `'lightspeed'`, etc.: Futuras integraciones

### 2. **Columna `intuit_sync_attempts`**
```sql
`intuit_sync_attempts` int unsigned NULL DEFAULT 0
```

**Propósito**: Rastrear intentos de sincronización QB
**Valores**: Contador de reintentos (0 = sin intentos/exitoso)

## Scripts de Ejecución

### 🔹 **Script Individual - Columna `origin`**
```bash
./scripts/add-origin-column-production.sh
```

**Comando equivalente**:
```bash
php artisan db:add-column-to-organizations-table \
  Sales_Header_Imp \
  origin \
  string \
  --length=20 \
  --nullable=1 \
  --default="docucenter" \
  --index=1
```

### 🔹 **Script Individual - Columna `intuit_sync_attempts`**
```bash
./scripts/add-intuit-sync-attempts-column-production.sh
```

**Comando equivalente**:
```bash
php artisan db:add-column-to-organizations-table \
  Sales_Header_Imp \
  intuit_sync_attempts \
  integer \
  --nullable=1 \
  --default=0 \
  --unsigned=1 \
  --index=0
```

### 🔹 **Script Combinado - Ambas Columnas** (RECOMENDADO)
```bash
./scripts/add-quickbooks-loop-columns-production.sh
```

**Características**:
- Ejecuta ambos comandos secuencialmente
- Incluye confirmación del usuario
- Validación de errores entre pasos
- Reporte final de éxito/fallo

### 🔹 **Script de Verificación**
```bash
./scripts/verify-quickbooks-loop-columns.sh
```

**Validaciones**:
- Verifica que ambas columnas existen en todas las organizaciones
- Reporte detallado por base de datos
- Estadísticas de éxito/fallo
- Queries adicionales para verificación manual

## Proceso de Ejecución en Producción

### **Paso 1: Backup** (CRÍTICO)
```bash
# Backup de bases de datos críticas antes de modificar estructura
mysqldump --single-transaction --routines --triggers [database_name] > backup_[database_name]_$(date +%Y%m%d_%H%M%S).sql
```

### **Paso 2: Ejecutar Script Combinado**
```bash
cd /path/to/docucenter
./scripts/add-quickbooks-loop-columns-production.sh
```

**Salida esperada**:
```
=== AGREGANDO COLUMNAS SOLUCIÓN ANTI-LOOP QUICKBOOKS EN PRODUCCIÓN ===

¿Continuar con la ejecución? (y/N): y

📋 PASO 1/2: Agregando columna 'origin'...
Columna 'origin' de tipo 'string' agregada a la tabla 'Sales_Header_Imp': 'org_1_db'.
Columna 'origin' de tipo 'string' agregada a la tabla 'Sales_Header_Imp': 'org_2_db'.
...
✅ Columna 'origin' agregada exitosamente

📋 PASO 2/2: Agregando columna 'intuit_sync_attempts'...
Columna 'intuit_sync_attempts' de tipo 'integer' agregada a la tabla 'Sales_Header_Imp': 'org_1_db'.
...
✅ Columna 'intuit_sync_attempts' agregada exitosamente

🎉 PROCESO COMPLETADO EXITOSAMENTE
```

### **Paso 3: Verificar Instalación**
```bash
./scripts/verify-quickbooks-loop-columns.sh
```

**Salida esperada**:
```
📊 REPORTE DE VERIFICACIÓN:
Total organizaciones: 25
Errores encontrados: 0

✅ TODAS LAS ORGANIZACIONES TIENEN LAS COLUMNAS CORRECTAS

📊 ESTADÍSTICAS FINALES:
Organizaciones OK: 25/25
Porcentaje éxito: 100.00%
```

### **Paso 4: Verificación Manual SQL**
```sql
-- Verificar estructura
SELECT TABLE_SCHEMA, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Sales_Header_Imp'
  AND COLUMN_NAME IN ('origin', 'intuit_sync_attempts')
ORDER BY TABLE_SCHEMA, COLUMN_NAME;

-- Verificar índices
SELECT TABLE_SCHEMA, INDEX_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_NAME = 'Sales_Header_Imp'
  AND COLUMN_NAME = 'origin'
ORDER BY TABLE_SCHEMA;
```

## Rollback (En caso de problemas)

### **Comando para Remover Columnas**
```bash
# Si es necesario hacer rollback (NO existe comando automático)
# Usar SQL manual en cada base de datos:

ALTER TABLE Sales_Header_Imp DROP COLUMN origin;
ALTER TABLE Sales_Header_Imp DROP COLUMN intuit_sync_attempts;
```

### **Script de Rollback Manual**
```bash
# Crear script personalizado si es necesario
php artisan tinker --execute="
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

\$organizations = Organization::whereNotNull('database')->get();
foreach (\$organizations as \$org) {
    DB::connection()->useDatabase(\$org->database);
    DB::statement('ALTER TABLE Sales_Header_Imp DROP COLUMN IF EXISTS origin');
    DB::statement('ALTER TABLE Sales_Header_Imp DROP COLUMN IF EXISTS intuit_sync_attempts');
    echo \"Rollback en: {\$org->database}\n\";
}
DB::connection()->useDatabase(env('DB_DATABASE'));
"
```

## Consideraciones de Rendimiento

### **Tiempo Estimado**
- **Por organización**: ~2-5 segundos por columna
- **Total (100 orgs)**: ~10-15 minutos para ambas columnas
- **Operación**: Bloqueante durante ejecución por tabla

### **Recomendaciones**
1. **Horario**: Ejecutar en ventana de mantenimiento
2. **Monitoring**: Verificar carga del servidor durante ejecución
3. **Conexiones**: Confirmar que no hay procesos críticos corriendo
4. **Backup**: SIEMPRE hacer backup antes de ejecutar

## Troubleshooting

### **Error: "La columna ya existe"**
```
La columna 'origin' ya existe en la tabla 'Sales_Header_Imp': 'org_database'.
```
**Solución**: Normal si se ejecuta múltiples veces. El comando es seguro.

### **Error: "La tabla no existe"**
```
La tabla 'Sales_Header_Imp' no existe en la base de datos 'org_database'.
```
**Solución**: Organización sin tabla Sales_Header_Imp. Revisar si es correcto.

### **Error de Conexión DB**
**Solución**: Verificar credenciales y conectividad a todas las bases de datos.

## Post-Instalación

### **Activar Código**
Una vez agregadas las columnas, el código de la solución anti-loop se activará automáticamente:

1. **CreateSaleQuickBooksJob**: Comenzará a marcar `origin = 'quickbooks'`
2. **UpdateIntuitOrdersJob**: Filtrará solo facturas `origin = 'docucenter'`
3. **Loop eliminado**: Facturas QB no regresarán a QB

### **Monitoreo**
```sql
-- Monitorear distribución por origen
SELECT origin, COUNT(*) as total 
FROM Sales_Header_Imp 
GROUP BY origin;

-- Verificar que facturas QB no se procesan en comando
SELECT COUNT(*) as facturas_qb_no_reprocesadas
FROM Sales_Header_Imp 
WHERE origin = 'quickbooks' 
  AND intuit_sync_status = 'completed';
```

Esta implementación garantiza la instalación segura y verificable de la solución anti-loop en producción.
