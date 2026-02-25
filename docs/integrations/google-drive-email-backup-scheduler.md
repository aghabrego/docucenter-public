# Sistema de Backup Automático de Emails a Google Drive

## 📋 Resumen

Sistema automatizado de backup de emails leídos con archivos XML adjuntos hacia Google Drive, organizado por RUC-DV, año y mes.

## ⏰ Programación Automática

### Configuración en Kernel

**Ubicación**: `app/Console/Kernel.php`

```php
// Backup de emails a Google Drive (todas las organizaciones con backup habilitado)
$schedule->command('emails:backup-to-drive --all --queue')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/email-backup.log'))
    ->runInBackground();
```

### Horario de Ejecución

- **Frecuencia**: Diaria
- **Hora**: 02:00 AM
- **Modo**: En cola (asíncrono)
- **Log**: `storage/logs/email-backup.log`

## 🎯 Comando Manual

### Sintaxis Completa

```bash
php artisan emails:backup-to-drive [opciones]
```

### Opciones Disponibles

| Opción | Tipo | Descripción | Ejemplo |
|--------|------|-------------|---------|
| `--organization_id` | int | ID de organización específica | `--organization_id=2` |
| `--all` | flag | Procesar todas las organizaciones con backup | `--all` |
| `--queue` | flag | Ejecutar en cola (recomendado) | `--queue` |
| `--days` | int | Límite de días hacia atrás (opcional) | `--days=30` |

### Ejemplos de Uso

**1. Backup de una organización específica (inmediato)**
```bash
php artisan emails:backup-to-drive --organization_id=2
```

**2. Backup de todas las organizaciones (en cola)**
```bash
php artisan emails:backup-to-drive --all --queue
```

**3. Backup de los últimos 7 días (una organización)**
```bash
php artisan emails:backup-to-drive --organization_id=2 --days=7
```

**4. Backup completo sin límite de fecha**
```bash
php artisan emails:backup-to-drive --organization_id=2
# Sin --days = procesa todos los emails leídos históricos
```

## 🔧 Configuración Requerida

### 1. Habilitar Backup en Organización

**Interfaz**: `/admin/organizations/update/{id}`

Campo: **"Habilitar Backup Diario a Google Drive"** ✅

### 2. Configurar Email IMAP

**Interfaz**: `/admin/configurationemailorganization`

Requisitos:
- Organización seleccionada
- Usuario asignado
- Credenciales de email (IMAP)
- Email adicional DocuCenter (opcional)

### 3. Credenciales Google Drive

**Archivo**: `service-account-credentials.json` (root del proyecto)

Permisos requeridos:
- Google Drive API habilitada
- Service Account con acceso a la carpeta raíz
- Folder ID configurado en la organización

## 📂 Estructura de Carpetas en Drive

```
DocuCenter-Facturas/
└── {RUC}-{DV}/              # Ej: 1825706-1-709732-90/
    └── {Año}/               # Ej: 2026/
        └── {Mes}/           # Ej: Febrero/
            ├── factura1.xml
            ├── factura2.xml
            └── ...
```

## 📊 Tracking de Uploads

### Tabla: `google_drive_uploads`

Registra cada archivo subido:
- ID del archivo en Google Drive
- Email de origen (subject, from, date)
- Organización y usuario
- Estado de eliminación del email

### Consulta de Status

```sql
SELECT 
    o.nombre,
    COUNT(*) as total_archivos,
    MAX(gdu.created_at) as ultimo_backup
FROM google_drive_uploads gdu
JOIN organizations o ON gdu.organization_id = o.id
WHERE gdu.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY o.id;
```

## 🔍 Verificación de Ejecución

### Ver Schedule Programado

```bash
docker exec -it docucenter_laravel.test php artisan schedule:list | grep backup
```

Salida esperada:
```
0 2 * * * php artisan emails:backup-to-drive --all --queue  Next Due: en X horas
```

### Ver Logs de Ejecución

```bash
# Logs del comando
tail -f storage/logs/email-backup.log

# Logs de Laravel (con detalles)
tail -f storage/logs/laravel.log | grep "Backup\|GoogleDrive"
```

### Verificar Jobs en Cola

```bash
# Ver jobs pendientes
docker exec -it docucenter_laravel.test php artisan queue:monitor

# Procesar cola manualmente
docker exec -it docucenter_laravel.test php artisan queue:work --once
```

## 🚨 Troubleshooting

### Error: "Sin configuración de email"

**Solución**: Crear configuración en `/admin/configurationemailorganization/create`

### Error: "Backup diario no habilitado"

**Solución**: Activar el toggle en la organización (`enable_daily_backup = true`)

### Error: "Google Drive credentials not found"

**Solución**: 
1. Verificar que existe `service-account-credentials.json`
2. Verificar permisos en Google Cloud Console
3. Verificar que la API de Google Drive está habilitada

### No se suben archivos

**Diagnóstico**:
```bash
# Ver emails leídos con attachments
php artisan tinker --execute="
\$client = App\Models\Configurationemailorganization::find(1)->clientManagerMake();
\$folder = \$client->getFolderByPath('INBOX');
\$messages = \$folder->query()->seen()->limit(10,1)->get();
echo 'Emails leídos: ' . \$messages->count() . PHP_EOL;
"
```

## 📈 Métricas del Sistema

El comando genera un reporte al finalizar:

```
Proceso completado:
+-------------------------+-------+
| Métrica                 | Valor |
+-------------------------+-------+
| Procesadas exitosamente | 1     |
| Errores                 | 0     |
| Total                   | 1     |
+-------------------------+-------+
```

### Logs Detallados

Información registrada:
- Estado de conexión IMAP
- Emails procesados por chunk
- XMLs extraídos y subidos
- Espacio liberado (MB)
- Errores específicos

## 🔐 Seguridad

### Eliminación de Emails

- **Cuando**: Solo después de upload exitoso a Google Drive
- **Tracking**: Registrado en `google_drive_uploads.email_deleted_at`
- **Irreversible**: Eliminación permanente (no papelera)

### Control de Acceso

- Solo organizaciones con `enable_daily_backup = true`
- Validación de permisos IMAP
- Service Account con acceso limitado a folder específico

## 📝 Notas Importantes

1. **Sin filtro de fecha**: Por defecto procesa todos los emails leídos históricos
2. **Uso de --days**: Recomendado para ejecuciones iniciales (ej: `--days=30`)
3. **Modo --queue**: Recomendado para evitar timeouts en grandes volúmenes
4. **Limit de 100 emails**: Por seguridad, procesa máximo 100 emails por ejecución

## 🔄 Mantenimiento

### Actualizar Horario de Ejecución

Editar `app/Console/Kernel.php`:
```php
// Cambiar hora de ejecución
->dailyAt('03:00')  // 3:00 AM

// O cada 12 horas
->twiceDaily(2, 14)  // 2:00 AM y 2:00 PM
```

### Limpiar Logs Antiguos

```bash
# Limpiar logs mayores a 30 días
find storage/logs -name "email-backup.log*" -mtime +30 -delete
```

---

**Fecha de Implementación**: 25 de Febrero, 2026  
**Desarrollador**: Sistema DocuCenter  
**Tipo**: Automatización de Backup
