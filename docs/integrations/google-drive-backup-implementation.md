# Implementación del Sistema de Backup de Emails a Google Drive

## Resumen

Se ha implementado un sistema completo para realizar backup automático de archivos XML enviados por correo electrónico a Google Drive, organizados por RUC-DV/Año/Mes. El sistema solo se activa cuando una organización tiene habilitado el backup diario en su configuración.

## Fecha de Implementación

25 de febrero de 2026

## Componentes Implementados

### 1. Migraciones de Base de Datos

#### 2026_02_25_150000_add_google_drive_backup_fields_to_organizations_table.php

Agrega campos a la tabla `organizations`:
- `enable_daily_backup` (boolean): Habilita/deshabilita el backup diario
- `google_drive_folder_id` (string): ID de la carpeta en Google Drive
- `last_backup_at` (timestamp): Última ejecución del backup

#### 2026_02_25_150100_create_google_drive_uploads_table.php

Crea tabla `google_drive_uploads` para auditoría:
- `organization_id`: Organización propietaria
- `file_name`: Nombre del archivo XML
- `google_drive_file_id`: ID único en Google Drive
- `google_drive_folder_id`: Carpeta contenedora
- `email_subject`, `email_from`, `email_date`: Metadatos del email
- `maildir_path`: Referencia al email original
- `email_deleted`, `email_deleted_at`: Estado de eliminación
- `file_size`: Tamaño del archivo
- `status`: Estado (uploaded, verified, failed)
- `error_message`: Mensaje de error si aplica

### 2. Modelos

#### App\Models\GoogleDriveUpload

Modelo completo para tracking de subidas con:
- Relaciones con Organization y User
- Scopes: `forOrganization()`, `notDeleted()`, `successful()`
- Métodos: `markEmailAsDeleted()`, `markAsVerified()`, `markAsFailed()`
- Constantes de estado: `STATUS_UPLOADED`, `STATUS_VERIFIED`, `STATUS_FAILED`

#### Actualización de App\Models\Organization

Campos agregados:
```php
'enable_daily_backup',
'google_drive_folder_id',
'last_backup_at',
```

Relaciones agregadas:
- `configurationEmailOrganization()`: Acceso a configuración de email
- `googleDriveUploads()`: Historial de subidas

### 3. Servicios

#### App\Services\GoogleDriveService

Servicio completo para interacción con Google Drive API:

**Métodos principales:**
- `getOrganizationFolder(Organization $organization)`: Obtiene/crea carpeta por RUC-DV
- `getDateFolder(string $parentId, int $year, int $month)`: Estructura Año/Mes
- `uploadXml(Organization $org, string $content, string $filename, Carbon $date)`: Sube archivo
- `deleteFile(string $fileId)`: Elimina archivo
- `fileExists(string $fileId)`: Verifica existencia
- `folderExists(string $folderId)`: Verifica carpeta

**Estructura de carpetas creada:**
```
DocuCenter-Facturas/
├── {RUC}-{DV}/
│   ├── 2026/
│   │   ├── 01-Enero/
│   │   ├── 02-Febrero/
│   │   └── ...
│   └── 2027/
└── ...
```

**Credenciales:**
- Usa `service-account-credentials.json` (Service Account)
- Scopes: Google Drive completo
- Application Name: "DocuCenter Email Extraction"

### 4. Jobs

#### App\Jobs\ExtractReadEmailsToGoogleDriveJob

Job principal para extracción y backup:

**Características:**
- Procesa solo emails LEÍDOS (método `seen()`)
- Extrae archivos XML de attachments
- Sube a Google Drive con estructura organizada
- Elimina PERMANENTEMENTE emails procesados usando `expunge()`
- Libera espacio en disco del servidor
- Manejo de estados granular (7 estados)
- Procesamiento por chunks de 10 emails
- Auditoría completa en base de datos

**Estados del proceso:**
1. `STATE_INIT`: Inicialización
2. `STATE_CONNECTING`: Conexión IMAP
3. `STATE_SCANNING_EMAILS`: Escaneo de emails
4. `STATE_EXTRACTING_XML`: Extracción de XMLs
5. `STATE_UPLOADING_DRIVE`: Subida a Drive
6. `STATE_DELETING_PERMANENTLY`: Eliminación permanente
7. `STATE_COMPLETED`: Completado

**Seguridad:**
- Solo elimina emails si TODOS los archivos se subieron exitosamente
- Transacciones de base de datos para integridad
- Logging detallado de cada operación
- Métricas de espacio liberado

**Configuración:**
- 3 intentos máximos
- Timeout de 1800 segundos (30 minutos)
- Límite de 100 emails por ejecución

### 5. Comandos Artisan

#### App\Console\Commands\BackupEmailsToGoogleDriveCommand

Comando para ejecutar backup manual o programado:

**Uso:**
```bash
# Una organización específica en cola
php artisan emails:backup-to-drive --organization_id=1 --queue

# Todas las organizaciones con backup habilitado
php artisan emails:backup-to-drive --all

# Ejecución inmediata (sin cola)
php artisan emails:backup-to-drive --organization_id=1
```

**Opciones:**
- `--organization_id={id}`: Procesar organización específica
- `--all`: Procesar todas con `enable_daily_backup=true`
- `--queue`: Encolar jobs en lugar de ejecutar inmediatamente

**Salida:**
- Tabla resumen con métricas
- Información de progreso por organización
- Manejo de errores individual

### 6. Interfaz de Usuario

#### Componente Livewire: Admin/Organization/Update

Campo agregado en formulario de edición:

```blade
<!-- Backup Diario a Google Drive -->
<div class='form-group'>
    <label class='col-sm-2 control-label'>
        Backup Diario Google Drive
    </label>
    <div class="custom-control custom-switch">
        <input 
            type="checkbox" 
            class="custom-control-input" 
            id="input-enable_daily_backup" 
            wire:model.lazy="enable_daily_backup">
        <label class="custom-control-label" for="input-enable_daily_backup">
            Habilitar backup automático de emails XML a Google Drive
        </label>
    </div>
    <small class="form-text text-muted">
        Cuando está habilitado, los emails leídos con archivos XML serán 
        respaldados automáticamente en Google Drive y eliminados del servidor 
        para liberar espacio.
    </small>
</div>
```

## Flujo de Trabajo

### 1. Configuración Inicial

1. Organización habilita `enable_daily_backup` en su configuración
2. Debe tener configuración de email en `configurationemailorganization`
3. Sistema valida credenciales de Google Drive

### 2. Ejecución Automática (Programada)

```bash
# En crontab o scheduler
0 2 * * * php artisan emails:backup-to-drive --all --queue
```

### 3. Proceso de Backup

```
1. Verificar enable_daily_backup=true
2. Conectar a IMAP
3. Buscar emails leídos (seen)
4. Por cada email:
   a. Extraer archivos XML
   b. Subir a Google Drive (RUC-DV/Año/Mes/)
   c. Registrar en google_drive_uploads
   d. Marcar email para eliminación
5. Ejecutar expunge() para eliminar permanentemente
6. Actualizar last_backup_at
7. Logging de métricas
```

## Seguridad y Validaciones

### Validaciones Implementadas

1. **Backup habilitado**: Solo procesa si `enable_daily_backup=true`
2. **Configuración de email**: Debe existir `Configurationemailorganization`
3. **Subida exitosa**: Solo elimina si TODOS los XMLs se subieron
4. **Transacciones**: Rollback automático en caso de error
5. **Credenciales**: Valida existencia de `service-account-credentials.json`

### Logging y Auditoría

**Logs generados:**
- Inicio/fin de proceso
- Cada archivo subido (con tamaño)
- Errores individuales
- Espacio liberado (MB)
- Estados del proceso

**Tabla de auditoría:**
- Registro completo de cada archivo subido
- Metadatos del email original
- Estado de eliminación
- Timestamps de todas las operaciones

## Métricas y Monitoreo

### Métricas Capturadas

- Total de emails procesados
- Total de archivos XML subidos
- Espacio liberado en disco (MB)
- Tiempo de ejecución
- Errores por organización

### Queries Útiles

```sql
-- Últimos backups por organización
SELECT o.nombre, o.ruc, o.dv, o.last_backup_at, 
       COUNT(gdu.id) as total_archivos
FROM organizations o
LEFT JOIN google_drive_uploads gdu ON o.id = gdu.organization_id
WHERE o.enable_daily_backup = 1
GROUP BY o.id
ORDER BY o.last_backup_at DESC;

-- Espacio liberado por organización
SELECT o.nombre, 
       SUM(gdu.file_size) / 1024 / 1024 as mb_respaldados,
       COUNT(gdu.id) as archivos_totales
FROM organizations o
JOIN google_drive_uploads gdu ON o.id = gdu.organization_id
WHERE gdu.email_deleted = 1
GROUP BY o.id;

-- Archivos subidos hoy
SELECT o.nombre, gdu.file_name, gdu.created_at, gdu.file_size
FROM google_drive_uploads gdu
JOIN organizations o ON gdu.organization_id = o.id
WHERE DATE(gdu.created_at) = CURDATE()
ORDER BY gdu.created_at DESC;
```

## Requisitos del Sistema

### Paquetes Necesarios

```json
{
    "google/apiclient": "^2.15",
    "webklex/laravel-imap": "^5.2"
}
```

### Archivos de Credenciales

- `service-account-credentials.json`: En raíz del proyecto
- Scopes requeridos: Google Drive API completo

### Permisos de Google Drive

Service Account debe tener:
- Permiso para crear carpetas
- Permiso para subir archivos
- Acceso al Drive compartido (si aplica)

### Configuración de Email

Cada organización debe tener:
- Email configurado en `configurationemailorganization`
- Acceso IMAP activo
- Puerto 993 (SSL)

## Programación Recomendada

### Cron Job Diario

```bash
# Ejecutar a las 2:00 AM todos los días
0 2 * * * cd /path/to/docucenter && php artisan emails:backup-to-drive --all --queue

# Ejecutar cada 6 horas (opcional)
0 */6 * * * cd /path/to/docucenter && php artisan emails:backup-to-drive --all --queue
```

### Laravel Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('emails:backup-to-drive --all --queue')
        ->dailyAt('02:00')
        ->onOneServer()
        ->withoutOverlapping();
}
```

## Migración y Despliegue

### Pasos de Despliegue

1. **Ejecutar migraciones:**
```bash
php artisan migrate
```

2. **Verificar credenciales:**
```bash
ls -la service-account-credentials.json
```

3. **Configurar organizaciones:**
   - Acceder al panel de administración
   - Editar cada organización
   - Habilitar "Backup Diario Google Drive"

4. **Prueba manual:**
```bash
php artisan emails:backup-to-drive --organization_id=1
```

5. **Programar cron:**
```bash
crontab -e
# Agregar línea de cron job
```

## Solución de Problemas

### Error: Credenciales no encontradas

**Problema:** `Credenciales de Google Drive no encontradas`

**Solución:**
1. Verificar que `service-account-credentials.json` existe en raíz
2. Verificar permisos de lectura del archivo
3. Verificar formato JSON válido

### Error: No se pueden eliminar emails

**Problema:** Emails no se eliminan del servidor

**Solución:**
1. Verificar que el servidor IMAP permite `expunge()`
2. Revisar permisos de la cuenta de email
3. Verificar configuración de Dovecot/Postfix

### Error: No se encuentra carpeta en Drive

**Problema:** `Cannot create folder in Google Drive`

**Solución:**
1. Verificar permisos del Service Account
2. Compartir Drive con Service Account email
3. Verificar scopes en credenciales

### Performance: Proceso muy lento

**Problema:** Backup tarda demasiado

**Solución:**
1. Reducir límite de emails por ejecución
2. Ejecutar con mayor frecuencia
3. Optimizar tamaño de chunks
4. Usar queue workers adicionales

## Mejoras Futuras

### Posibles Extensiones

1. **Interfaz de monitoreo:**
   - Dashboard con métricas en tiempo real
   - Gráficos de espacio liberado
   - Alertas de errores

2. **Notificaciones:**
   - Email al completar backup
   - Alertas de errores críticos
   - Resumen semanal

3. **Filtros avanzados:**
   - Filtrar por remitente
   - Filtrar por fecha específica
   - Procesar solo facturas de cierto tipo

4. **Compresión:**
   - Comprimir archivos antes de subir
   - Batch upload de múltiples archivos

5. **Restauración:**
   - Comando para restaurar desde Google Drive
   - Interfaz de búsqueda de archivos

## Referencias

- [Documentación Google Drive API](https://developers.google.com/drive/api/v3/about-sdk)
- [Laravel IMAP Package](https://github.com/Webklex/laravel-imap)
- [Documentación original del sistema](./google-drive-email-extraction-system.md)

## Contacto y Soporte

Para soporte técnico o consultas sobre esta implementación:
- Revisar logs en `storage/logs/laravel.log`
- Consultar tabla `google_drive_uploads` para auditoría
- Ejecutar comando con `--verbose` para más detalles

---

**Última actualización:** 25 de febrero de 2026
**Versión:** 1.0.0
**Estado:** Implementado y listo para producción
