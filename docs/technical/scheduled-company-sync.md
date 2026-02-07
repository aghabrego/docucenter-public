# Sincronización Automática de Compañías - Configuración

## Resumen
El comando `company:sync` debe ejecutarse automáticamente en la madrugada para sincronizar datos de organizaciones a bases de datos consolidadas.

## Opción 1: Laravel Scheduler (Recomendado)

### 1. Modificar `app/Console/Kernel.php`

Agregar en el método `schedule()`:

```php
protected function schedule(Schedule $schedule)
{
    // Sincronización diaria a las 2:00 AM
    $schedule->command('company:sync --all')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/company-sync.log'));
    
    // O sincronización específica por organización/compañía
    // $schedule->command('company:sync --organization=2 --company=13')
    //     ->dailyAt('02:00')
    //     ->withoutOverlapping()
    //     ->appendOutputTo(storage_path('logs/company-sync.log'));
}
```

### 2. Configurar Cron (una sola vez)

Editar crontab del servidor:
```bash
crontab -e
```

Agregar esta línea:
```bash
* * * * * cd /home/weirdolabs/code/docucenter && docker exec docucenter_laravel.test php artisan schedule:run >> /dev/null 2>&1
```

Esta entrada ejecuta el scheduler de Laravel cada minuto, y Laravel se encarga de ejecutar los comandos programados.

## Opción 2: Cron Directo

Si prefieres no usar el Laravel Scheduler:

```bash
crontab -e
```

Agregar:
```bash
# Sincronización diaria a las 2:00 AM
0 2 * * * cd /home/weirdolabs/code/docucenter && docker exec docucenter_laravel.test php artisan company:sync --all >> /home/weirdolabs/code/docucenter/storage/logs/cron-sync.log 2>&1
```

### Horarios Alternativos

```bash
# A las 3:00 AM
0 3 * * * ...

# A las 1:30 AM
30 1 * * * ...

# Los domingos a las 2:00 AM
0 2 * * 0 ...

# De lunes a viernes a las 2:00 AM
0 2 * * 1-5 ...
```

## Opción 3: Supervisord para Jobs en Segundo Plano

Si usas Redis Queue (recomendado para mejor control):

### 1. Verificar configuración en `docker/supervisord.conf`

Debe incluir:
```ini
[program:laravel-worker]
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=sail
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
```

### 2. Programar en Laravel Scheduler

```php
protected function schedule(Schedule $schedule)
{
    // Despachar job a la cola a las 2:00 AM
    $schedule->call(function () {
        $companies = \App\Models\Companysession::where('enable_consolidation', true)
            ->whereNotNull('database')
            ->get();
        
        $organizations = \App\Models\Organization::all();
        
        foreach ($companies as $company) {
            foreach ($organizations as $organization) {
                \App\Jobs\SyncOrganizationToCompanyJob::dispatch(
                    $organization->id, 
                    $company->id
                );
            }
        }
    })->dailyAt('02:00')
      ->name('dispatch-company-sync-jobs')
      ->onOneServer();
}
```

## Monitoreo y Validación

### 1. Verificar que cron está activo
```bash
# Ver tareas programadas
crontab -l

# Ver logs del sistema
tail -f /var/log/syslog | grep CRON
```

### 2. Verificar ejecución del scheduler
```bash
# Probar manualmente
docker exec docucenter_laravel.test php artisan schedule:run

# Ver siguiente ejecución programada
docker exec docucenter_laravel.test php artisan schedule:list
```

### 3. Revisar logs de sincronización
```bash
# Log del comando
tail -f storage/logs/company-sync.log

# Log de Laravel
tail -f storage/logs/laravel.log | grep "SyncOrganizationToCompanyJob"

# Log de workers (si usas queue)
tail -f storage/logs/worker.log
```

### 4. Queries de validación post-sync
```sql
-- Conectar a base de datos consolidada
USE db_weirdolabs_13;

-- Ver última sincronización por organización
SELECT 
    org_source_id,
    COUNT(*) as total_headers,
    MAX(DateModified) as ultima_modificacion
FROM Sales_Header_Imp
GROUP BY org_source_id
ORDER BY org_source_id;

-- Verificar integridad headers vs details
SELECT 
    h.org_source_id,
    COUNT(DISTINCT h.InvoiceNumber) as headers,
    COUNT(d.SalesDetailId) as details
FROM Sales_Header_Imp h
LEFT JOIN Sales_Detail_Imp d ON h.InvoiceNumber = d.InvoiceNumber 
    AND h.org_source_id = d.org_source_id
GROUP BY h.org_source_id;
```

## Recomendaciones

1. **Usar Laravel Scheduler** (Opción 1) para mejor control y logging
2. **Configurar alertas** si la sincronización falla
3. **Horario recomendado**: 2:00 AM - 4:00 AM (baja actividad)
4. **Monitoreo**: Configurar script que verifique si se ejecutó correctamente
5. **Retention**: Rotar logs antiguos (después de 30 días)

## Troubleshooting

### Problema: Cron no ejecuta
```bash
# Verificar servicio cron activo
sudo systemctl status cron

# Reiniciar cron
sudo systemctl restart cron

# Ver logs de errores
grep CRON /var/log/syslog
```

### Problema: Scheduler no ejecuta
```bash
# Verificar que el comando funciona manualmente
docker exec docucenter_laravel.test php artisan company:sync --all

# Verificar configuración de timezone en config/app.php
'timezone' => 'America/Panama',
```

### Problema: Jobs no se procesan
```bash
# Verificar workers activos
docker exec docucenter_laravel.test supervisorctl status

# Reiniciar workers
docker exec docucenter_laravel.test supervisorctl restart laravel-worker:*

# Ver jobs en cola
docker exec docucenter_laravel.test php artisan queue:listen
```

## Script de Monitoreo Automático

Crear `/home/weirdolabs/code/docucenter/scripts/check-sync-status.sh`:

```bash
#!/bin/bash
LOG_FILE="/home/weirdolabs/code/docucenter/storage/logs/company-sync.log"
YESTERDAY=$(date -d "yesterday" +%Y-%m-%d)

# Verificar si se ejecutó ayer
if grep -q "$YESTERDAY" "$LOG_FILE"; then
    echo "Sync ejecutado correctamente el $YESTERDAY"
    exit 0
else
    echo "ALERTA: No se encontró ejecución del sync el $YESTERDAY"
    # Aquí puedes agregar notificación por email/Slack
    exit 1
fi
```

Hacer ejecutable:
```bash
chmod +x scripts/check-sync-status.sh
```

Programar verificación diaria:
```bash
0 8 * * * /home/weirdolabs/code/docucenter/scripts/check-sync-status.sh
```
