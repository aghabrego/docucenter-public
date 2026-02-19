# Mejora de Supervisor para Queue Workers - Resumen de Implementación

## Cambios Realizados

### 1. Separación de Workers por Prioridad

Se reemplazó la configuración monolítica de 4 workers por un sistema optimizado de **3 grupos**:

#### **High Priority Workers** (2 procesos)
- **Colas**: `high`
- **Memoria**: 512MB por worker
- **Sleep**: 1 segundo (respuesta rápida)
- **Timeout**: 1 hora
- **Max Jobs**: 50 jobs por worker antes de reiniciar
- **Uso**: Operaciones críticas que requieren atención inmediata

#### **Default Workers** (3 procesos)
- **Colas**: `default`, `sales`, `create_sale_maxgym`, `create_sale_kart`
- **Memoria**: 512MB por worker
- **Sleep**: 3 segundos (balance)
- **Timeout**: 1 hora
- **Max Jobs**: 50 jobs por worker antes de reiniciar
- **Uso**: Operaciones normales de ventas y procesamiento

#### **Batch Workers** (2 procesos)
- **Colas**: `issue_mass_invoices`, `maintenance`
- **Memoria**: 1024MB por worker
- **Sleep**: 5 segundos (baja frecuencia)
- **Timeout**: 2 horas
- **Max Jobs**: 30 jobs por worker (menos porque son más pesados)
- **Uso**: Emisión masiva de facturas y tareas de mantenimiento pesadas

**Total**: 7 workers (~4.6GB RAM estimado)

### 2. Configuración Opcional Ligera

Para servidores con **2GB RAM**, se incluye configuración alternativa:
- 1 High Priority worker (256MB)
- 2 Default workers (256MB c/u)
- 1 Batch worker (512MB)
- **Total**: 4 workers (~1.3GB RAM)

### 3. Mejoras en Gestión de Logs

- **Rotación automática**: Límite de 50MB por archivo (100MB para batch)
- **Backups**: 5 versiones antiguas (10 para batch)
- **Logs separados**: Por grupo de workers para troubleshooting fácil
- **Errores independientes**: stdout y stderr separados

### 4. Configuraciones Avanzadas

#### Backoff Exponencial
- **High**: 30s, 60s, 180s entre reintentos
- **Default**: 60s, 180s, 300s
- **Batch**: 300s, 600s

#### Graceful Shutdown
- **High/Default**: 60 segundos de espera
- **Batch**: 120 segundos (jobs más largos)

#### Prioridades de Inicio
- **High**: Prioridad 100 (inicia primero)
- **Default**: Prioridad 200
- **Batch**: Prioridad 300 (inicia último)

### 5. Herramientas de Gestión

#### Script de Monitoreo (`monitor-workers.sh`)
- Estado de todos los workers con emojis visuales
- Uso de memoria y CPU en tiempo real
- Estado de colas Redis
- Conteo de jobs fallidos
- Últimos errores de cada grupo
- **Uso**: `./scripts/monitor-workers.sh`

#### Script de Instalación (`install-supervisor-config.sh`)
- Instalación automatizada con backup de configuración anterior
- Modo `full` (7 workers) o `light` (4 workers)
- Creación automática de logs
- Verificación de estado post-instalación
- **Uso**: `sudo ./scripts/install-supervisor-config.sh [light|full]`

## Archivos Creados

```
config/supervisor/
├── laravel-worker-high.conf      # Workers alta prioridad
├── laravel-worker-default.conf   # Workers estándar
├── laravel-worker-batch.conf     # Workers batch/mantenimiento
└── laravel-worker-light.conf     # Configuración ligera (4 workers)

scripts/
├── monitor-workers.sh            # Script de monitoreo
└── install-supervisor-config.sh  # Script de instalación automática

docs/technical/
└── supervisor-queue-workers.md   # Documentación completa (390+ líneas)
```

## Instalación Rápida

### Opción 1: Instalación Automática (Recomendado)

```bash
# Configuración completa (7 workers, 4GB+ RAM)
cd /home/desarrollo/code/docucenter
sudo ./scripts/install-supervisor-config.sh full

# Configuración ligera (4 workers, 2GB RAM)
sudo ./scripts/install-supervisor-config.sh light
```

### Opción 2: Instalación Manual

```bash
# 1. Backup de configuración actual
sudo cp /etc/supervisor/conf.d/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf.backup

# 2. Remover configuración antigua
sudo rm -f /etc/supervisor/conf.d/laravel-worker.conf

# 3. Copiar nuevas configuraciones (completa)
sudo cp config/supervisor/laravel-worker-high.conf /etc/supervisor/conf.d/
sudo cp config/supervisor/laravel-worker-default.conf /etc/supervisor/conf.d/
sudo cp config/supervisor/laravel-worker-batch.conf /etc/supervisor/conf.d/

# O configuración ligera (alternativa)
sudo cp config/supervisor/laravel-worker-light.conf /etc/supervisor/conf.d/

# 4. Crear logs
mkdir -p storage/logs
touch storage/logs/worker-{high,default,batch}{,-error}.log
chmod -R 755 storage/logs

# 5. Recargar supervisor
sudo supervisorctl stop all
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all

# 6. Verificar
sudo supervisorctl status
```

## Comandos Útiles

### Gestión de Workers

```bash
# Ver estado de todos los workers
sudo supervisorctl status

# Reiniciar todos
sudo supervisorctl restart all

# Reiniciar grupo específico
sudo supervisorctl restart laravel-worker-high:*
sudo supervisorctl restart laravel-worker-default:*
sudo supervisorctl restart laravel-worker-batch:*

# Detener todos
sudo supervisorctl stop all

# Ver logs en tiempo real
sudo supervisorctl tail -f laravel-worker-high
tail -f storage/logs/worker-high.log
```

### Monitoreo

```bash
# Script de monitoreo completo
./scripts/monitor-workers.sh

# Ver uso de memoria
free -h
ps aux | grep "queue:work" | grep -v grep

# Ver jobs en cola
php artisan queue:monitor high,default,sales,create_sale_maxgym,create_sale_kart,issue_mass_invoices,maintenance

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all
```

## Configuración por Cron (Opcional)

```bash
# Agregar a crontab para monitoreo automático
crontab -e

# Verificar cada 5 minutos y guardar log
*/5 * * * * /home/desarrollo/code/docucenter/scripts/monitor-workers.sh >> /home/desarrollo/logs/worker-monitor.log 2>&1
```

## Ventajas de la Nueva Configuración

###  Optimización de Recursos
- **Separación por prioridad**: Jobs críticos procesados primero
- **Memoria diferenciada**: Batch tiene más memoria (1GB vs 512MB)
- **Timeouts ajustados**: Batch permite hasta 2 horas
- **Max-jobs limit**: Workers se reinician después de 50 jobs (30 para batch) previniendo memory leaks

###  Mejor Manejo de Colas
- **Sleep optimizado**: 1s para high, 3s para default, 5s para batch
- **Backoff exponencial**: Reintentos más inteligentes
- **Prioridades**: High inicia primero, batch último

###  Gestión de Logs Mejorada
- **Rotación automática**: No se llenan los discos
- **Logs separados**: Troubleshooting más fácil
- **Errores independientes**: stdout/stderr separados

###  Escalabilidad
- **Fácil ajuste**: Cambiar `numprocs` en archivos `.conf`
- **Dos modos**: Full (7 workers) o Light (4 workers)
- **Por grupo**: Escalar solo lo necesario

###   Herramientas de Gestión
- **Monitoreo visual**: Script con emojis y colores
- **Instalación automática**: Con backup y verificación
- **Documentación completa**: 390+ líneas

## Comparación Antes vs Después

| Métrica | Antes | Después (Full) | Después (Light) |
|---------|-------|----------------|-----------------|
| Workers totales | 4 | 7 | 4 |
| RAM estimada | ~2GB | ~4.6GB | ~1.3GB |
| Archivos config | 1 | 3 | 1 |
| Colas separadas | No | Sí (3 grupos) | Sí (3 grupos) |
| Prioridades | No | Sí | Sí |
| Backoff exponencial | No | Sí | Sí |
| Max-jobs (anti leak) | No | Sí (50/30) | Sí (50/30) |
| Rotación logs | No | Sí | Sí |
| Monitoring | No | Sí | Sí |
| Escalabilidad | Baja | Alta | Media |

## Recomendaciones

### Para Servidor de 2GB RAM
- Usar configuración **light** (4 workers)
- Monitorear memoria con `free -h`
- Considerar reducir más si hay problemas

### Para Servidor de 4GB+ RAM
- Usar configuración **full** (7 workers)
- Monitorear rendimiento regularmente
- Ajustar `numprocs` según carga real

### En General
-  Monitorear uso de memoria semanalmente
-  Revisar jobs fallidos diariamente
-  Ajustar sleep/timeout según patrones de uso
-  Configurar alertas para workers caídos
-  No exceder capacidad RAM del servidor
-  No ignorar logs de error

## Troubleshooting

### Workers no inician
```bash
# Ver logs de supervisor
sudo tail -100 /var/log/supervisor/supervisord.log

# Verificar permisos
ls -la artisan
sudo chown -R desarrollo:desarrollo /home/desarrollo/code/docucenter
```

### Alto uso de memoria
```bash
# Reducir numprocs en archivos .conf
sudo nano /etc/supervisor/conf.d/laravel-worker-default.conf
# Cambiar numprocs=3 a numprocs=2

# Aplicar cambios
sudo supervisorctl reread
sudo supervisorctl update
```

### Jobs se acumulan
```bash
# Verificar workers corriendo
sudo supervisorctl status

# Aumentar workers temporalmente
sudo supervisorctl start laravel-worker-default:*

# O procesar manual
php artisan queue:work redis --queue=default --once
```

## Documentación Adicional

Ver [docs/technical/supervisor-queue-workers.md](docs/technical/supervisor-queue-workers.md) para:
- Configuración detallada de cada parámetro
- Ejemplos de uso avanzado
- Mejores prácticas de Redis
- Performance tuning
- Monitoring avanzado con cron

## Próximos Pasos

1.  Instalar configuración (light o full según servidor)
2.  Monitorear durante 24-48 horas
3. ⏳ Ajustar numprocs si es necesario
4. ⏳ Configurar cron para monitoreo automático
5. ⏳ Implementar alertas (Slack, email, etc.)
