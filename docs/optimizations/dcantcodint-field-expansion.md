# Expansión del Campo dCantCodInt en fe_detail

**Fecha:** 19 de febrero de 2026  
**Estado:** ✅ Implementado

## Problema Detectado

El campo `dCantCodInt` en la tabla `fe_detail` tenía un tipo de dato `DECIMAL(11,6)` que limitaba las cantidades a:
- **Máximo:** 99999.999999 (5 dígitos enteros + 6 decimales)
- **Problema:** Facturas con cantidades mayores como `115001.09` generaban truncamiento o error

### Ejemplo Real
```xml
<!-- Factura TRISTAR LCS - 19/02/2026 -->
<dCantCodInt>115001.09</dCantCodInt>  <!-- ❌ 6 dígitos enteros - NO SOPORTADO -->
<dCantCodInt>29687.41</dCantCodInt>   <!-- ✅ 5 dígitos enteros - OK -->
```

## Solución Implementada

### 1. Actualización del Stub SQL
**Archivo:** [`app/Models/stubs/fe_detail.sql.stub`](../app/Models/stubs/fe_detail.sql.stub)

```sql
-- ANTES
`dCantCodInt` decimal(11,6) DEFAULT NULL,   -- Máximo: 99999.999999

-- DESPUÉS  
`dCantCodInt` decimal(18,6) DEFAULT NULL,   -- Máximo: 999,999,999,999.999999
```

**Beneficios:**
- ✅ Consistencia con otros campos monetarios (`dPrUnit`, `dPrItem`, etc.)
- ✅ Soporta cantidades de hasta 12 dígitos enteros
- ✅ Mantiene precisión de 6 decimales

### 2. Comando de Migración
**Archivo:** [`app/Console/Commands/Configuration/ExpandDCantCodIntField.php`](../app/Console/Commands/Configuration/ExpandDCantCodIntField.php)

```bash
# Dry-run (sin cambios)
php artisan db:expand-dcantcodint --dry-run

# Ejecución real
php artisan db:expand-dcantcodint

# Organización específica
php artisan db:expand-dcantcodint --org=123
```

**Características:**
- ✅ Itera todas las organizaciones automáticamente
- ✅ Preserva `NULL`/`NOT NULL` original
- ✅ Modo dry-run para validación
- ✅ Reporte detallado de cambios

### 3. Script de Ejecución
**Archivo:** [`scripts/expand-dcantcodint-field.sh`](../scripts/expand-dcantcodint-field.sh)

```bash
# Ejecutar dentro del contenedor Docker
./scripts/expand-dcantcodint-field.sh
```

**Flujo del script:**
1. Muestra explicación del cambio
2. Solicita confirmación
3. Ejecuta dry-run primero
4. Solicita confirmación final
5. Aplica cambios reales
6. Muestra query de verificación

## Comparación de Capacidades

| Tipo | Dígitos Enteros | Dígitos Decimales | Rango Máximo |
|------|-----------------|-------------------|--------------|
| DECIMAL(11,6) ❌ | 5 | 6 | 99,999.999999 |
| DECIMAL(18,6) ✅ | 12 | 6 | 999,999,999,999.999999 |

**Ejemplos de cantidades soportadas:**
```
29687.41      ✅ Ambos tipos
115001.09     ✅ Solo DECIMAL(18,6)
1234567.89    ✅ Solo DECIMAL(18,6)
999999999.00  ✅ Solo DECIMAL(18,6)
```

## Uso en Producción

### Paso 1: Ejecutar Script
```bash
# Dentro del contenedor Docker
docker exec -it docucenter-app-1 bash
./scripts/expand-dcantcodint-field.sh
```

### Paso 2: Verificar Cambios
```sql
-- Verificar tipo de dato en organizaciones
SELECT TABLE_SCHEMA, COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'fe_detail' 
  AND COLUMN_NAME = 'dCantCodInt'
LIMIT 10;

-- Resultado esperado:
-- COLUMN_TYPE: decimal(18,6)
```

### Paso 3: Validar Importación
```bash
# Importar factura con cantidad grande
# Verificar que 115001.09 se guarde correctamente
```

## Interfaz de Administración

El comando está disponible en la interfaz de Process Management:

**Ruta:** Admin → Process Management → Configuración BD  
**Comando:** `db:expand-dcantcodint`  
**Nombre:** Expandir Campo dCantCodInt  
**Descripción:** Expande dCantCodInt de DECIMAL(11,6) a DECIMAL(18,6) para soportar grandes cantidades

## Archivos Modificados

1. ✅ [`app/Models/stubs/fe_detail.sql.stub`](../app/Models/stubs/fe_detail.sql.stub) - Stub actualizado
2. ✅ [`app/Console/Commands/Configuration/ExpandDCantCodIntField.php`](../app/Console/Commands/Configuration/ExpandDCantCodIntField.php) - Comando nuevo
3. ✅ [`scripts/expand-dcantcodint-field.sh`](../scripts/expand-dcantcodint-field.sh) - Script de ejecución
4. ✅ [`app/Http/Livewire/Admin/Processmanagement/Manage.php`](../app/Http/Livewire/Admin/Processmanagement/Manage.php) - Registro en interfaz

## Notas Técnicas

- **Sin downtime:** El `ALTER TABLE ... MODIFY COLUMN` es rápido en tablas pequeñas/medianas
- **Compatibilidad:** DECIMAL(18,6) es compatible con DECIMAL(11,6) - no requiere conversión de datos
- **Índices:** No afecta índices existentes
- **Backups:** Recomendado hacer backup antes de ejecutar en producción

## Verificación Post-Implementación

```bash
# 1. Verificar stub
grep -A5 "dCantCodInt" app/Models/stubs/fe_detail.sql.stub
# Esperado: decimal(18,6)

# 2. Verificar BDs organizacionales (sample)
docker exec -it docucenter-mariadb-1 mysql -u root -proot <<EOF
SELECT TABLE_SCHEMA, COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'fe_detail' AND COLUMN_NAME = 'dCantCodInt'
ORDER BY TABLE_SCHEMA
LIMIT 5;
EOF

# 3. Test de importación
# Usar XML con <dCantCodInt>115001.09</dCantCodInt>
```

## Conclusión

✅ **Stub actualizado** para nuevas organizaciones  
✅ **Comando creado** para actualizar organizaciones existentes  
✅ **Script automatizado** para ejecución segura  
✅ **Interfaz administrativa** integrada  
✅ **Documentación completa** creada

El sistema ahora soporta cantidades de hasta 12 dígitos enteros con precisión de 6 decimales, alineado con los estándares de facturación electrónica panameña y consistente con otros campos monetarios del sistema.

---

**Autor:** Equipo DocuCenter  
**Referencia:** Factura TRISTAR LCS - CUFE FE0420000155677046-2-2019-9200002026021900000000020010111628133090
