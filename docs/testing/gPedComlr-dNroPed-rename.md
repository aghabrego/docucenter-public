# Renombrado de Campo: gPedComGl_dNroPed → gPedComlr_dNroPed

**Fecha:** 20 de febrero de 2026  
**Estado:** Completado  
**Tipo:** Refactorización de nomenclatura

## Resumen

Cambio de nombre del campo de `gPedComGl_dNroPed` a `gPedComlr_dNroPed` en el modelo y base de datos, manteniendo la extracción del XML con el nombre original según especificación DGI.

## Razón del Cambio

El nombre de la columna en la base de datos se corrigió de `gPedComGl_dNroPed` a `gPedComlr_dNroPed` para mejor claridad o alineación con nomenclatura interna.

## Cambios Realizados

### 1. Modelo FeHeader
**Archivo:** `app/Models/FeHeader.php`

**PHPDoc actualizado:**
```php
/**
 * @property string $gPedComlr_dNroPed
 */
```

**Array fillable actualizado:**
```php
protected $fillable = [
    // ...
    'gPedComlr_dNroPed',
];
```

### 2. Stub de Tabla
**Archivo:** `app/Models/stubs/fe_header.sql.stub`

**Columna renombrada:**
```sql
`gPedComlr_dNroPed` varchar(12) DEFAULT NULL,
```

### 3. Lógica de Importación
**Archivo:** `app/Utils/ImportData.php`

#### Extracción del XML (SIN CAMBIOS)
```php
// Línea ~291 - La extracción del XML mantiene el nombre original
$gPedComGlDNroPed = $this->getFirstfilterData($data, '/gPedComGl1_dNroPed1/i');
```

#### Mapeo al Modelo (ACTUALIZADO)
```php
// Línea ~322 - xml()
'gPedComlr_dNroPed' => $gPedComGlDNroPed,

// Línea ~775 - xmlGT()
'gPedComlr_dNroPed' => array_get($data, 'gPedComGl_dNroPed'),

// Línea ~1877 - getDataXML()
'gPedComlr_dNroPed' => $gPedComGlDNroPed,

// Línea ~2039 - setDataFile()
'gPedComlr_dNroPed' => array_get($data, 'gPedComGl_dNroPed'),
```

### 4. Comando para Renombrar Columna
**Archivo:** `app/Console/Commands/Configuration/RenameColumnInOrganizationsTableCommand.php`

Nuevo comando Artisan para renombrar columnas en todas las bases de datos de organizaciones:

**Uso:**
```bash
php artisan db:rename-column-in-organizations-table {tabla} {columnaVieja} {columnaNueva}
```

**Características:**
- Itera sobre todas las organizaciones con campo `database` ≠ NULL
- Verifica existencia de tabla y columnas
- Usa `ALTER TABLE ... CHANGE` para renombrar
- Muestra resumen con exitosas, omitidas y errores
- Progreso visual con barra de progreso

### 5. Script de Ejecución
**Archivo:** `scripts/rename-gpedcomgl-to-gpedcomlr.sh`

**Uso:**
```bash
# Desde el host
./scripts/rename-gpedcomgl-to-gpedcomlr.sh

# Desde dentro del contenedor
docker exec -it docucenter-app-1 bash scripts/rename-gpedcomgl-to-gpedcomlr.sh
```

## Flujo de Datos

### Antes del Cambio
```
XML (gPedComGl) → Extracción → gPedComGl_dNroPed (Modelo) → gPedComGl_dNroPed (BD)
```

### Después del Cambio
```
XML (gPedComGl) → Extracción → gPedComlr_dNroPed (Modelo) → gPedComlr_dNroPed (BD)
        ↑                              ↑
   Sin cambios              Nombre actualizado
```

## Estructura XML (Sin Cambios)

El campo sigue extrayéndose de la misma estructura XML:

```xml
<rFE xmlns="http://dgi-fep.mef.gob.pa">
    <gPedComGl>
        <dNroPed>1234</dNroPed>
    </gPedComGl>
</rFE>
```

**Patrón de extracción:** `/gPedComGl1_dNroPed1/i` (sin cambios)

## Migración en Bases de Datos

### Opción 1: Usando el Comando (Recomendado)

```bash
# Ejecutar el comando que renombra en todas las organizaciones
docker exec -it docucenter-app-1 php artisan db:rename-column-in-organizations-table \
    fe_header \
    gPedComGl_dNroPed \
    gPedComlr_dNroPed
```

**Salida esperada:**
```
Renombrando columna 'gPedComGl_dNroPed' a 'gPedComlr_dNroPed' en tabla 'fe_header'
Este proceso afectará TODAS las bases de datos de organizaciones.

¿Deseas continuar? (yes/no) [no]: yes

Total de organizaciones a procesar: 50

[████████████████████████████] 50/50

Resumen de la operación:
+----------+----------+
| Estado   | Cantidad |
+----------+----------+
| Exitosas | 48       |
| Omitidas | 2        |
| Errores  | 0        |
| Total    | 50       |
+----------+----------+
```

### Opción 2: SQL Directo (Manual)

Para cada base de datos de organización:

```sql
ALTER TABLE fe_header 
CHANGE `gPedComGl_dNroPed` `gPedComlr_dNroPed` VARCHAR(12) DEFAULT NULL;
```

## Compatibilidad

### ✅ Mantiene Compatibilidad
- Extracción desde XML (mismo patrón)
- Procesamiento de facturas existentes
- Importaciones automáticas
- Jobs y comandos existentes

### ⚠️ Requiere Actualización
- Queries directos que usen el nombre antiguo de columna
- Reportes o vistas que referencien `gPedComGl_dNroPed`
- Scripts personalizados que accedan al campo

## Archivos Afectados

1. ✅ `app/Models/FeHeader.php`
2. ✅ `app/Models/stubs/fe_header.sql.stub`
3. ✅ `app/Utils/ImportData.php` (4 ubicaciones)
4. 🆕 `app/Console/Commands/Configuration/RenameColumnInOrganizationsTableCommand.php`
5. 🆕 `scripts/rename-gpedcomgl-to-gpedcomlr.sh`

## Verificación Post-Migración

### 1. Verificar estructura de tabla en una organización
```bash
docker exec -it docucenter-mariadb-1 mysql -u root -proot -e \
  "USE 9_734_1672_56; DESCRIBE fe_header;" | grep gPed
```

**Salida esperada:**
```
gPedComlr_dNroPed    varchar(12)    YES        NULL
```

### 2. Verificar importación de XML
```bash
# Probar con XML real que contenga gPedComGl
docker exec docucenter-app-1 php artisan test:import-xml /path/to/test.xml
```

### 3. Verificar modelo
```bash
docker exec docucenter-app-1 php artisan tinker
>>> $header = \App\Models\FeHeader::first();
>>> $header->gPedComlr_dNroPed;
"1234"
```

## Commit

```
refactor: renombrar campo gPedComGl_dNroPed a gPedComlr_dNroPed en FeHeader

Cambios realizados:
- Actualizado modelo FeHeader con nuevo nombre de campo
- Modificado stub de tabla fe_header con nueva columna
- Actualizados mapeos en ImportData para usar gPedComlr_dNroPed
- Extracción desde XML mantiene nombre original gPedComGl1_dNroPed1
```

**Hash:** `2e982393`

## Documentación Relacionada

- [Implementación Original](gPedComGl-dNroPed-implementation.md) - Documentación de la implementación inicial
- [Comando db:rename-column-in-organizations-table](../technical/db-rename-column-command.md) - Guía del comando de renombrado

## Conclusión

✅ **Campo renombrado correctamente en código**  
✅ **Comando de migración creado y probado**  
✅ **Script automatizado disponible**  
✅ **Extracción del XML sin cambios**  
✅ **Compatibilidad mantenida**  
⏳ **Pendiente: Ejecutar migración en bases de datos**

---

*Última actualización: 20 de febrero de 2026*
