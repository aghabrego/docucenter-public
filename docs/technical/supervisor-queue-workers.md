# Configuración de Supervisor para Laravel Queue Workers - DocuCenter

Esta guía documenta la configuración optimizada de Supervisor para gestionar los workers de colas de Laravel en DocuCenter.

## Parámetros Clave de Configuración

### --max-jobs: Prevención de Memory Leaks

Cada worker se reinicia automáticamente después de procesar un número determinado de jobs:

- **High Priority y Default**: `--max-jobs=50`
- **Batch**: `--max-jobs=30` (jobs más pesados requieren reinicio más frecuente)

**¿Por qué es importante?**

1. **Previene Memory Leaks**: PHP puede acumular memoria con el tiempo, especialmente en multi-tenant
2. **Mantiene Workers Frescos**: Reinicio regular garantiza estado limpio
3. **Evita Crecimiento Descontrolado**: Limita el uso de memoria por worker
4. **Mejora Estabilidad**: Workers no saturados son más confiables
5. **Mejor para Conexiones DB**: Cierra y reinicia conexiones periódicamente

**Sin --max-jobs**: Un worker podría crecer de 50MB a 500MB+ después de días corriendo.

**Con --max-jobs=50**: Worker nunca excede su límite base y se mantiene estable.

### --max-time: Tiempo Máximo de Ejecución

- **High/Default**: 1 hora (3600s)
- **Batch**: 2 horas (7200s)

Worker se reinicia después de este tiempo sin importar cuántos jobs ha procesado.

### --memory: Límite de Memoria

- **High/Default**: 512MB
- **Batch**: 1024MB (emisión masiva requiere más memoria)

Worker se reinicia si excede este límite de memoria.

## Arquitectura de Workers

La configuración está dividida en **3 grupos de workers** según prioridad y recursos:

### 1. High Priority Workers (Críticos)
- **Colas**: `high`
- **Procesos**: 2
- **Memoria**: 512MB
- **Timeout**: 1 hora
- **Uso**: Operaciones críticas que requieren respuesta rápida

### 2. Default Workers (Estándar)
- **Colas**: `default, sales, create_sale_maxgym, create_sale_kart`
- **Procesos**: 3
- **Memoria**: 512MB
- **Timeout**: 1 hora
- **Uso**: Operaciones normales de ventas y procesamiento

### 3. Batch Workers (Pesados)
- **Colas**: `issue_mass_invoices, maintenance`
- **Procesos**: 2
- **Memoria**: 1024MB
- **Timeout**: 2 horas
- **Uso**: Emisión masiva de facturas y tareas de mantenimiento

**Total de Procesos**: 7 workers (optimizado para servidor 2GB RAM)

## Archivos de Configuración

### Ubicación
```
/etc/supervisor/conf.d/
├── laravel-worker-high.conf      # Workers de alta prioridad
├── laravel-worker-default.conf   # Workers estándar
└── laravel-worker-batch.conf     # Workers de batch/mantenimiento
```

## 1. High Priority Workers

**Archivo**: `/etc/supervisor/conf.d/laravel-worker-high.conf`

```ini
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /home/desarrollo/code/docucenter/artisan queue:work redis --queue=high --sleep=1 --tries=3 --timeout=3600 --max-time=3600 --max-jobs=50 --memory=512 --backoff=30,60,180
directory=/home/desarrollo/code/docucenter
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=desarrollo
numprocs=2
redirect_stderr=true
stdout_logfile=/home/desarrollo/code/docucenter/storage/logs/worker-high.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5
stderr_logfile=/home/desarrollo/code/docucenter/storage/logs/worker-high-error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=5
stopwaitsecs=60
startsecs=5
startretries=3
priority=100
```

### Características:
- **Sleep 1 segundo**: Respuesta más rápida para jobs críticos
- **Max-jobs 50**: Worker se reinicia automáticamente después de 50 jobs (previene memory leaks)
- **Backoff exponencial**: 30s, 60s, 180s entre reintentos
- **2 procesos**: Suficiente para alta prioridad
- **Rotación de logs**: Máximo 50MB por archivo, 5 backups
- **Prioridad 100**: Inicia primero

## 2. Default Workers

**Archivo**: `/etc/supervisor/conf.d/laravel-worker-default.conf`

```ini
[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /home/desarrollo/code/docucenter/artisan queue:work redis --queue=default,sales,create_sale_maxgym,create_sale_kart --sleep=3 --tries=3 --timeout=3600 --max-time=3600 --max-jobs=50 --memory=512 --backoff=60,180,300
directory=/home/desarrollo/code/docucenter
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=desarrollo
numprocs=3
redirect_stderr=true
stdout_logfile=/home/desarrollo/code/docucenter/storage/logs/worker-default.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5
stderr_logfile=/home/desarrollo/code/docucenter/storage/logs/worker-default-error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=5
stopwaitsecs=60
startsecs=5
startretries=3
priority=200
```

### Características:
- **Sleep 3 segundos**: Balance entre respuesta y CPU
- **Max-jobs 50**: Reinicio automático después de 50 jobs
- **3 procesos**: Maneja carga estándar de ventas
- **Backoff 60s, 180s, 300s**: Reintentos más espaciados
- **Prioridad 200**: Inicia después de high

## 3. Batch Workers

**Archivo**: `/etc/supervisor/conf.d/laravel-worker-batch.conf`

```ini
[program:laravel-worker-batch]
process_name=%(program_name)s_%(process_num)02d
command=php /home/desarrollo/code/docucenter/artisan queue:work redis --queue=issue_mass_invoices,maintenance --sleep=5 --tries=2 --timeout=7200 --max-time=7200 --max-jobs=30 --memory=1024 --backoff=300,600
directory=/home/desarrollo/code/docucenter
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=desarrollo
numprocs=2
redirect_stderr=true
stdout_logfile=/home/desarrollo/code/docucenter/storage/logs/worker-batch.log
stdout_logfile_maxbytes=100MB
stdout_logfile_backups=10
stderr_logfile=/home/desarrollo/code/docucenter/storage/logs/worker-batch-error.log
stderr_logfile_maxbytes=100MB
stderr_logfile_backups=10
stopwaitsecs=120
startsecs=10
startretries=2
priority=300
```

### Características:
- **Sleep 5 segundos**: Menor frecuencia para jobs pesados
- **Timeout 2 horas**: Jobs de emisión masiva requieren más tiempo
- **Max-jobs 30**: Reinicio después de 30 jobs (menos porque son más pesados)
- **Memoria 1024MB**: Procesamiento de muchas facturas
- **2 reintentos**: Menos intentos para jobs pesados
- **Stopwaitsecs 120**: Más tiempo para graceful shutdown
- **Logs 100MB**: Batch genera más logs
- **Prioridad 300**: Inicia último

## Instalación

### 1. Remover Configuración Antigua

```bash
sudo rm -f /etc/supervisor/conf.d/laravel-worker.conf
```

### 2. Crear Nuevas Configuraciones

```bash
# High Priority
sudo nano /etc/supervisor/conf.d/laravel-worker-high.conf
# Pegar contenido de laravel-worker-high

# Default
sudo nano /etc/supervisor/conf.d/laravel-worker-default.conf
# Pegar contenido de laravel-worker-default

# Batch
sudo nano /etc/supervisor/conf.d/laravel-worker-batch.conf
# Pegar contenido de laravel-worker-batch
```

### 3. Crear Directorios de Logs

```bash
mkdir -p /home/desarrollo/code/docucenter/storage/logs
touch /home/desarrollo/code/docucenter/storage/logs/worker-high.log
touch /home/desarrollo/code/docucenter/storage/logs/worker-default.log
touch /home/desarrollo/code/docucenter/storage/logs/worker-batch.log
chmod 755 /home/desarrollo/code/docucenter/storage/logs
```

### 4. Recargar Supervisor

```bash
# Releer configuraciones
sudo supervisorctl reread

# Actualizar cambios
sudo supervisorctl update

# Ver estado
sudo supervisorctl status
```

### 5. Iniciar Workers

```bash
# Iniciar todos
sudo supervisorctl start laravel-worker-high:*
sudo supervisorctl start laravel-worker-default:*
sudo supervisorctl start laravel-worker-batch:*

# O iniciar todos a la vez
sudo supervisorctl start all
```

## Comandos de Gestión

### Ver Estado
```bash
sudo supervisorctl status
```

### Reiniciar Workers
```bash
# Reiniciar todos
sudo supervisorctl restart all

# Reiniciar grupo específico
sudo supervisorctl restart laravel-worker-high:*
sudo supervisorctl restart laravel-worker-default:*
sudo supervisorctl restart laravel-worker-batch:*

# Reiniciar proceso específico
sudo supervisorctl restart laravel-worker-high:laravel-worker-high_00
```

### Detener Workers
```bash
# Detener todos
sudo supervisorctl stop all

# Detener grupo
sudo supervisorctl stop laravel-worker-high:*
```

### Ver Logs en Tiempo Real
```bash
# Logs de high priority
sudo supervisorctl tail -f laravel-worker-high

# Ver últimas 100 líneas
sudo supervisorctl tail -100 laravel-worker-high

# Logs directos
tail -f /home/desarrollo/code/docucenter/storage/logs/worker-high.log
tail -f /home/desarrollo/code/docucenter/storage/logs/worker-default.log
tail -f /home/desarrollo/code/docucenter/storage/logs/worker-batch.log
```

### Agregar/Quitar Procesos Dinámicamente

```bash
# Cambiar numprocs en archivo de configuración
sudo nano /etc/supervisor/conf.d/laravel-worker-default.conf
# Modificar numprocs=3 a numprocs=5

# Aplicar cambios
sudo supervisorctl reread
sudo supervisorctl update
```

## Monitoreo

### Script de Monitoreo de Workers

```bash
#!/bin/bash
# /home/desarrollo/scripts/monitor-workers.sh

echo "=== Estado de Workers Laravel ==="
echo ""

echo "High Priority Workers (2):"
sudo supervisorctl status laravel-worker-high:* | grep -E "RUNNING|FATAL|STOPPED"

echo ""
echo "Default Workers (3):"
sudo supervisorctl status laravel-worker-default:* | grep -E "RUNNING|FATAL|STOPPED"

echo ""
echo "Batch Workers (2):"
sudo supervisorctl status laravel-worker-batch:* | grep -E "RUNNING|FATAL|STOPPED"

echo ""
echo "=== Uso de Memoria ==="
ps aux | grep "queue:work" | grep -v grep | awk '{sum+=$6} END {print "Total: " sum/1024 " MB"}'

echo ""
echo "=== Jobs en Cola ==="
cd /home/desarrollo/code/docucenter
php artisan queue:monitor high,default,sales,create_sale_maxgym,create_sale_kart,issue_mass_invoices,maintenance
```

### Cron para Monitoreo Automático

```bash
# Agregar a crontab
crontab -e

# Verificar cada 5 minutos
*/5 * * * * /home/desarrollo/scripts/monitor-workers.sh >> /home/desarrollo/logs/worker-monitor.log 2>&1
```

## Optimización de Recursos

### Uso de Memoria Estimado
```
High Priority:   2 workers × 512MB = 1024MB
Default:         3 workers × 512MB = 1536MB
Batch:           2 workers × 1024MB = 2048MB
--------------------------------
Total:           7 workers = 4608MB (4.5GB)
```

** IMPORTANTE**: Para servidor con 2GB RAM, considera reducir:

### Configuración Ligera (Servidor 2GB RAM)

```bash
# Reducir a:
High Priority:   1 worker  × 256MB = 256MB
Default:         2 workers × 256MB = 512MB
Batch:           1 worker  × 512MB = 512MB
--------------------------------
Total:           4 workers = 1280MB (1.25GB)
```

Modificar en cada archivo:
- `numprocs`: Reducir según tabla superior
- `--memory`: Ajustar según tabla superior

## Troubleshooting

### Workers No Inician

```bash
# Ver logs de supervisor
sudo tail -100 /var/log/supervisor/supervisord.log

# Ver logs específicos del worker
sudo supervisorctl tail laravel-worker-high stderr

# Verificar permisos
ls -la /home/desarrollo/code/docucenter/artisan
sudo chown -R desarrollo:desarrollo /home/desarrollo/code/docucenter
```

### Workers Se Detienen Constantemente

```bash
# Verificar uso de memoria
free -h
ps aux | grep "queue:work" | grep -v grep

# Reducir numprocs si hay poca RAM
# Aumentar --memory si workers mueren por límite

# Ver errores PHP
tail -100 /home/desarrollo/code/docucenter/storage/logs/laravel.log
```

### Jobs Fallan Silenciosamente

```bash
# Verificar failed_jobs
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Limpiar jobs antiguos
php artisan queue:flush
```

### Alto Uso de CPU

```bash
# Aumentar --sleep para reducir polling
# En archivo de configuración: --sleep=5 (en lugar de 1)

# Reducir numprocs
```

## Mejores Prácticas

 **DO:**
- Monitorear uso de memoria regularmente
- Rotar logs automáticamente
- Usar queues específicas por tipo de job
- Configurar alertas para workers caídos
- Hacer backups de configuraciones

 **DON'T:**
- No usar más workers de los que la RAM soporta
- No mezclar jobs críticos con batch en la misma cola
- No ignorar jobs fallidos acumulados
- No usar `--daemon` (deprecated, usar supervisor)
- No exceder timeout de 2 horas sin buena razón

## Performance Tips

1. **Redis Tuning**: Configurar `maxmemory-policy allkeys-lru` en Redis
2. **PHP OPcache**: Habilitar para mejorar rendimiento
3. **Job Batching**: Agrupar jobs pequeños en batches
4. **Queue Priorities**: Siempre usar queues específicas con prioridades
5. **Monitoring**: Implementar `php artisan queue:monitor` en cron

## Referencias

- [Laravel Queue Documentation](https://laravel.com/docs/9.x/queues)
- [Supervisor Documentation](http://supervisord.org/configuration.html)
- [Redis Best Practices](https://redis.io/topics/admin)
