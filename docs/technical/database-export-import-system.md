# Sistema de Exportación/Importación con Links Temporales

## Descripción

Sistema mejorado de exportación e importación de base de datos con generación de links de descarga temporales firmados que expiran automáticamente.

## Comando: word:export-data

### Características
- Genera exportación SQL completa de la base de datos
- Crea URL firmada con expiración configurable (default: 60 minutos)
- Limpieza automática de archivos antiguos (> 2 horas)
- Almacenamiento seguro en `storage/app/exports/`
- Modo legacy compatible con sistema anterior

### Uso Básico

```bash
# Exportación con link temporal (60 minutos) - BD principal
php artisan word:export-data

# Exportación con expiración personalizada (120 minutos)
php artisan word:export-data --expiration=120

# Exportación modo legacy (sin link temporal)
php artisan word:export-data --legacy

# Exportar base de datos específica
php artisan word:export-data --database=db_weirdolabs_14

# Exportar BD de organización específica con 2 horas de expiración
php artisan word:export-data --database=db_18257061709732_90 --expiration=120
```

### Exportación Multi-Tenant

Para sistemas multi-tenant como DocuCenter, puedes exportar bases de datos específicas de organizaciones:

```bash
# Exportar BD consolidada
php artisan word:export-data --database=db_weirdolabs_14

# Exportar BD de organización específica
php artisan word:export-data --database=db_18257061709732_90

# Exportar múltiples organizaciones (script bash)
for db in db_18257061709732_90 db_18257061709732_91 db_18257061709732_92; do
    php artisan word:export-data --database=$db --expiration=240
done
```

### Salida Ejemplo

```
Iniciando exportación de base de datos...
Generando exportación...
Ejecutando: mysqldump...
Exportación generada exitosamente (145.23 MB)

═══════════════════════════════════════════════════════
        EXPORTACIÓN COMPLETADA
═══════════════════════════════════════════════════════
Archivo: docucenter_2026_02_11_14_30_45.sql
Tamaño: 145.23 MB
Expira en: 60 minutos (2026-02-11 15:30:45)

Link de descarga temporal:
https://docucenter.com/database/export/download/docucenter_2026_02_11_14_30_45.sql?expires=1707666645&signature=abc123...

IMPORTANTE: Este link expirará en 60 minutos
═══════════════════════════════════════════════════════
```

## Comando: word:import-data

### Características
- Importación desde múltiples fuentes
- Confirmación interactiva antes de importar
- Detección automática del archivo más reciente
- Soporte para rutas personalizadas

### Uso Básico

```bash
# Importar archivo específico - BD principal
php artisan word:import-data /ruta/al/archivo.sql

# Importar exportación más reciente de exports/
php artisan word:import-data --latest

# Importar desde database/dumps/ (legacy)
php artisan word:import-data --dumps

# Importar desde schema (comportamiento por defecto)
php artisan word:import-data

# Importar a base de datos específica
php artisan word:import-data --database=db_weirdolabs_14 --latest

# Importar archivo específico a BD de organización
php artisan word:import-data /path/to/backup.sql --database=db_18257061709732_90
```

### Importación Multi-Tenant

Para restaurar bases de datos específicas de organizaciones:

```bash
# Restaurar BD consolidada desde archivo específico
php artisan word:import-data /backups/db_weirdolabs_14_2026_02_11.sql --database=db_weirdolabs_14

# Restaurar organización desde exportación más reciente
php artisan word:import-data --latest --database=db_18257061709732_90

# Restaurar BD completa de una organización
php artisan word:import-data /backups/org_backup.sql --database=db_18257061709732_91
```

### Confirmación Interactiva

```
ADVERTENCIA: Esta operación sobrescribirá la base de datos actual
Archivo: docucenter_2026_02_11_14_30_45.sql
Tamaño: 145.23 MB

¿Deseas continuar con la importación? (yes/no) [no]:
```

## Seguridad

### URLs Firmadas
- Las URLs generadas utilizan firma criptográfica de Laravel
- No se puede modificar la URL sin invalidar la firma
- Expiración automática después del tiempo configurado
- Validación en cada descarga mediante middleware `signed`

### Ruta de Descarga
```php
Route::get('/database/export/download/{filename}', [DatabaseExportController::class, 'download'])
    ->middleware('signed')
    ->name('database.export.download');
```

### Validación
El controlador valida:
1. Firma de URL válida
2. URL no expirada
3. Archivo existe en storage
4. Solo permite descargar archivos de `exports/`

## Limpieza Automática

### Task Programada
```php
// En app/Console/Kernel.php
$schedule->call(function () {
    $controller = new \App\Http\Controllers\DatabaseExportController();
    $controller->cleanup();
})->hourly();
```

### Reglas de Limpieza
- Se ejecuta cada hora automáticamente
- Elimina archivos mayores a 2 horas
- Previene acumulación de exportaciones antiguas
- Libera espacio en disco automáticamente

## Estructura de Almacenamiento

```
storage/
└── app/
    └── exports/                          # Exportaciones temporales
        ├── docucenter_2026_02_11_14_30_45.sql
        ├── docucenter_2026_02_11_13_15_22.sql
        └── ...
        
database/
└── dumps/                                # Modo legacy (opcional)
    └── docucenter_2026_02_10_10_00_00.sql
```

## Flujo de Trabajo Recomendado

### 1. Exportación
```bash
# Generar exportación con link de 2 horas
docker exec docucenter_laravel.test php artisan word:export-data --expiration=120
```

### 2. Compartir Link
- Copiar la URL generada
- Enviar al destinatario
- El link expirará automáticamente

### 3. Descarga
- Abrir URL en navegador
- Descarga automática del archivo
- Si la URL expiró: Error 401 "URL expirada"

### 4. Importación
```bash
# Importar la exportación más reciente
docker exec docucenter_laravel.test php artisan word:import-data --latest

# O importar archivo específico
docker exec docucenter_laravel.test php artisan word:import-data /path/to/file.sql
```

## Panel de Administración

Los comandos están disponibles en el panel web:
- **Admin > Process Management > Respaldo y Migración**
- Exportar Base de Datos (genera link temporal)
- Importar Base de Datos (con opciones de fuente)

## Casos de Uso DocuCenter Multi-Tenant

### 1. Backup de Base de Datos Consolidada
```bash
# Exportar BD consolidada con link de 4 horas
docker exec docucenter_laravel.test php artisan word:export-data \
    --database=db_weirdolabs_14 \
    --expiration=240

# Importar backup de BD consolidada
docker exec docucenter_laravel.test php artisan word:import-data \
    /backups/db_weirdolabs_14_2026_02_11.sql \
    --database=db_weirdolabs_14
```

### 2. Migración de Organización Individual
```bash
# Exportar organización específica
docker exec docucenter_laravel.test php artisan word:export-data \
    --database=db_18257061709732_90 \
    --expiration=120

# Importar a otra organización (migración)
docker exec docucenter_laravel.test php artisan word:import-data \
    /backups/org_90.sql \
    --database=db_18257061709732_95
```

### 3. Backup Completo de Todas las Organizaciones
```bash
#!/bin/bash
# Script para backup de todas las organizaciones

# Exportar BD principal
docker exec docucenter_laravel.test php artisan word:export-data \
    --database=docucenter \
    --expiration=360

# Exportar BD consolidada
docker exec docucenter_laravel.test php artisan word:export-data \
    --database=db_weirdolabs_14 \
    --expiration=360

# Exportar cada organización
for db in $(docker exec docucenter-mariadb-1 mysql -u sail -psecret -e "SHOW DATABASES LIKE 'db_%';" -s --skip-column-names); do
    echo "Exportando: $db"
    docker exec docucenter_laravel.test php artisan word:export-data \
        --database=$db \
        --expiration=360
done
```

### 4. Restauración de Emergencia
```bash
# Restaurar BD principal desde backup
docker exec docucenter_laravel.test php artisan word:import-data \
    /emergency-backups/docucenter_main.sql \
    --database=docucenter

# Restaurar organización corrupta desde backup más reciente
docker exec docucenter_laravel.test php artisan word:import-data \
    --latest \
    --database=db_18257061709732_90
```

### 5. Clone de Organización (Testing/Desarrollo)
```bash
# Exportar organización de producción
docker exec docucenter_laravel.test php artisan word:export-data \
    --database=db_18257061709732_90 \
    --legacy

# Importar a BD de testing
docker exec docucenter_laravel.test php artisan word:import-data \
    /storage/app/exports/db_18257061709732_90.sql \
    --database=db_testing_org
```

### 6. Consolidar Múltiples Organizaciones
```bash
# Exportar organizaciones individuales
docker exec docucenter_laravel.test php artisan word:export-data --database=db_org_1
docker exec docucenter_laravel.test php artisan word:export-data --database=db_org_2
docker exec docucenter_laravel.test php artisan word:export-data --database=db_org_3

# Luego usar company:sync para consolidar
docker exec docucenter_laravel.test php artisan company:sync --all
```

## Opciones Avanzadas

### Especificar Base de Datos
```bash
# Exportar BD específica
php artisan word:export-data --database=db_nombre_especifica

# Importar a BD específica
php artisan word:import-data /path/to/file.sql --database=db_destino

# Listar bases de datos disponibles
docker exec docucenter-mariadb-1 mysql -u sail -psecret -e "SHOW DATABASES;"
```

### Personalizar Tiempo de Expiración
```bash
# 30 minutos
php artisan word:export-data --expiration=30

# 4 horas
php artisan word:export-data --expiration=240

# 24 horas (máximo recomendado)
php artisan word:export-data --expiration=1440
```

### Modo Legacy (Sin Link Temporal)
Si prefieres el comportamiento anterior sin links temporales:
```bash
php artisan word:export-data --legacy
# Guarda en: database/dumps/docucenter_YYYY_MM_DD_HH_MM_SS.sql
```

## Troubleshooting

### Error: "URL expirada"
- La URL ha superado el tiempo de expiración
- Generar nueva exportación con `word:export-data`

### Error: "Archivo no encontrado"
- El archivo fue eliminado por limpieza automática (> 2 horas)
- Generar nueva exportación

### Error: "Firma inválida"
- La URL fue modificada manualmente
- No modificar ningún parámetro de la URL
- Generar nueva exportación si es necesario

## Ventajas del Sistema Mejorado

✅ **Seguridad**: URLs firmadas criptográficamente
✅ **Expiración**: Links temporales previenen accesos indefinidos
✅ **Automático**: Limpieza automática de archivos antiguos
✅ **Flexible**: Múltiples opciones de configuración
✅ **Multi-Tenant**: Soporte completo para bases de datos específicas
✅ **Compatible**: Modo legacy mantiene comportamiento anterior
✅ **Informativo**: Detalles completos de archivo y expiración
✅ **Interactivo**: Confirmación antes de sobrescribir datos
✅ **Personalizable**: Nombres de archivo basados en BD específica

## Notas Técnicas

- Los archivos se almacenan en `storage/app/exports/`
- La limpieza automática se ejecuta cada hora via cron scheduler
- Las URLs firmadas usan `Illuminate\Support\Facades\URL::temporarySignedRoute()`
- El middleware `signed` valida la firma y expiración
- Compatible con Docker y entornos de producción
- **Soporte Multi-Tenant**: Parámetro `--database` permite exportar/importar cualquier BD
- **Nombres inteligentes**: Archivos nombrados según BD exportada (ej: `db_org_90_2026_02_11.sql`)
- **Seguridad**: Solo accesible desde comandos Artisan con permisos apropiados
