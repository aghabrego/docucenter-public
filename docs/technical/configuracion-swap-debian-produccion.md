# Configuración de Memoria Swap en Debian 11 (Producción)

## ESTADO ACTUALIZADO - SWAP 8GB CONFIGURADO

**Servidor**: desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01
**Memoria disponible**: 287Mi de 3.8Gi total (mejora desde 108Mi)
**Memoria usada**: 3.2Gi (84.2% de uso)
**Swap configurado**: 8.0Gi (actualmente usando 1.1Gi)
**Archivo swap**: /swapfile.new

**ESTADO**: Sistema estable con swap de 8GB operativo

## Problema Identificado

**Error**: `SQLSTATE[HY000]: General error: 2006 MySQL server has gone away`
**Causa**: Memoria insuficiente en servidor de producción (87% usado, solo 119MB libres)
**Contexto**: Job `word:update-sql-server-module` ejecutándose diariamente a las 6 AM

## Estado Verificado del Sistema de Producción

**Información del servidor de producción** (desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01):

```bash
# Distribución confirmada
$ lsb_release -a
Distributor ID: Debian
Description:    Debian GNU/Linux 11 (bullseye)
Release:        11
Codename:       bullseye

# Estado crítico de memoria
$ free -h
               total        used        free      shared  buff/cache   available
Mem:           3.8Gi       3.3Gi       123Mi       222Mi       419Mi       108Mi
Swap:             0B          0B          0B

# Confirmación: sin swap configurado
$ cat /proc/swaps
Filename                                Type            Size            Used            Priority
(vacío - sin swap configurado)

# Comando swapon no disponible
$ swapon --show
-bash: swapon: command not found
```

**Estado CRÍTICO**: Solo 108Mi disponibles de 3.8Gi total
**Problema Confirmado**: Sin memoria swap configurada
**Impacto**: Errores "MySQL server has gone away" durante jobs pesados como `word:update-sql-server-module`

## Prerequisitos: Preparación del Servidor de Producción

**Servidor**: desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01
**Estado**: `swapon` no disponible (comando no encontrado)

```bash
# 1. Conectar al servidor de producción
ssh desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01

# 2. Verificar si util-linux está instalado
dpkg -l | grep util-linux

# 3. Instalar util-linux para obtener comandos swap
sudo apt update
sudo apt install util-linux

# 4. Verificar instalación exitosa
swapon --version
```

**Comandos de verificación** que ya funcionan en el servidor:
- `cat /proc/swaps` - Ver archivos swap activos 
- `grep -i swap /proc/meminfo` - Ver información de swap 
- `free -h` - Ver memoria total incluyendo swap 

## Solución: Configuración de Memoria Swap en Servidor de Producción

> **IMPORTANTE**: Los siguientes comandos deben ejecutarse en el servidor de producción Debian 11, NO en el ambiente local de desarrollo.

### Paso 1: Verificación Inicial del Sistema (Servidor de Producción)

```bash
# Conectar al servidor de producción primero
# ssh usuario@servidor-produccion

# Verificar memoria actual en producción
free -h

# Verificar si hay swap configurado (método principal)
cat /proc/swaps

# Método alternativo si swapon no está disponible
grep -i swap /proc/meminfo

# Instalar util-linux si swapon no está disponible
sudo apt update && sudo apt install util-linux

# Verificar espacio en disco disponible
df -h

# Verificar distribución
lsb_release -a
```

### Paso 2: Crear Archivo Swap (2GB Recomendado para Servidor 4GB)

```bash
# Verificar espacio disponible en disco
df -h

# Crear archivo de 2GB para servidor de 4GB RAM (desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01)
sudo fallocate -l 2G /swapfile

# Verificar que se creó correctamente
ls -lh /swapfile
```

**Nota**: Para el servidor actual con 3.8Gi RAM, 2GB swap es óptimo
**Alternativa** si `fallocate` no está disponible:
```bash
sudo dd if=/dev/zero of=/swapfile bs=1024 count=2097152
```

### Paso 3: Configurar Permisos de Seguridad

```bash
# Establecer permisos restrictivos (crítico para seguridad)
sudo chmod 600 /swapfile

# Verificar permisos correctos
ls -lh /swapfile
# Debe mostrar: -rw------- 1 root root 2.0G
```

### Paso 4: Activar el Archivo Swap

```bash
# Marcar el archivo como área de swap
sudo mkswap /swapfile

# Activar el swap inmediatamente
sudo swapon /swapfile

# Verificar activación exitosa
# Método principal (si swapon está disponible)
swapon --show

# Método alternativo de verificación
cat /proc/swaps
free -h
```

**Resultado esperado** (con `cat /proc/swaps`):
```
Filename                Type        Size    Used    Priority
/swapfile               file        2097148 0       -2
```

**O con `swapon --show`** (si está disponible):
```
NAME      TYPE SIZE USED PRIO
/swapfile file   2G   0B   -2
```

### Paso 5: Hacer Configuración Permanente

```bash
# Respaldar archivo fstab actual
sudo cp /etc/fstab /etc/fstab.backup.$(date +%Y%m%d)

# Agregar entrada swap permanente
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Verificar que se agregó correctamente
grep swap /etc/fstab
```

## Optimización de Configuración Swap para Producción

### Configurar Swappiness (Crítico para Servidores)

```bash
# Ver valor actual (por defecto 60)
cat /proc/sys/vm/swappiness

# Configurar valor óptimo para servidores de bases de datos (10-20)
echo 'vm.swappiness=15' | sudo tee -a /etc/sysctl.conf

# Aplicar configuración inmediatamente
sudo sysctl vm.swappiness=15
```

**Valores recomendados**:
- `60` (default): Para desktops
- `10-20`: Para servidores de bases de datos
- `15`: Óptimo para DocuCenter con MySQL/MariaDB

### Configurar Cache Pressure

```bash
# Configurar para optimizar cache de archivos
echo 'vm.vfs_cache_pressure=50' | sudo tee -a /etc/sysctl.conf

# Aplicar inmediatamente
sudo sysctl vm.vfs_cache_pressure=50
```

### Configurar Dirty Ratio (Optimización de I/O)

```bash
# Configurar para escrituras más eficientes
echo 'vm.dirty_ratio=15' | sudo tee -a /etc/sysctl.conf
echo 'vm.dirty_background_ratio=5' | sudo tee -a /etc/sysctl.conf

# Aplicar configuración
sudo sysctl vm.dirty_ratio=15
sudo sysctl vm.dirty_background_ratio=5
```

## Configuración MySQL/MariaDB para Memoria Optimizada

### Optimizar Configuración de Base de Datos

Editar `/etc/mysql/mariadb.conf.d/50-server.cnf`:

```ini
[mysqld]
# === TIMEOUTS PARA JOBS LARGOS ===
wait_timeout = 28800                # 8 horas
interactive_timeout = 28800         # 8 horas
net_read_timeout = 300              # 5 minutos
net_write_timeout = 300             # 5 minutos

# === OPTIMIZACIÓN DE MEMORIA ===
innodb_buffer_pool_size = 1536M     # 40% de RAM disponible
innodb_buffer_pool_instances = 4    # Para buffer > 1GB
innodb_log_file_size = 256M         # 25% del buffer pool

# === LÍMITES DE CONEXIÓN ===
max_connections = 150               # Reducido para conservar memoria
max_allowed_packet = 256M           # Para queries grandes

# === OPTIMIZACIÓN DE QUERIES ===
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 32M

# === OPTIMIZACIÓN INNODB ===
innodb_flush_log_at_trx_commit = 2  # Mejor performance
innodb_flush_method = O_DIRECT      # Evita double buffering
innodb_file_per_table = 1           # Un archivo por tabla
```

### Reiniciar Servicios

```bash
# Verificar configuración antes de reiniciar
sudo mysqld --help --verbose | grep -A 1 "Default options"

# Reiniciar MariaDB/MySQL
sudo systemctl restart mariadb

# Verificar estado del servicio
sudo systemctl status mariadb

# Verificar configuración aplicada
mysql -u root -p -e "SHOW VARIABLES LIKE 'wait_timeout';"
mysql -u root -p -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
```

## Monitoreo y Validación

### Script de Monitoreo de Memoria

```bash
# Crear script de monitoreo
sudo tee /usr/local/bin/docucenter-memory-monitor.sh << 'EOF'
#!/bin/bash
LOGFILE="/var/log/docucenter-memory.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "=== MONITOREO DOCUCENTER - $TIMESTAMP ===" >> $LOGFILE

# Información de memoria
echo "--- Memoria del Sistema ---" >> $LOGFILE
free -h >> $LOGFILE

echo "--- Uso de Swap ---" >> $LOGFILE
cat /proc/swaps >> $LOGFILE

# Si swapon está disponible, usar también:
# swapon --show >> $LOGFILE 2>/dev/null || echo "swapon no disponible" >> $LOGFILE

# Procesos PHP que más consumen memoria
echo "--- Top Procesos PHP ---" >> $LOGFILE
ps aux | grep php | grep -v grep | sort -k4 -nr | head -5 >> $LOGFILE

# Conexiones MySQL activas
echo "--- Conexiones MySQL ---" >> $LOGFILE
mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT COUNT(*) as conexiones_activas FROM information_schema.PROCESSLIST;" 2>/dev/null >> $LOGFILE

# Estadísticas de swap
echo "--- Estadísticas Swap ---" >> $LOGFILE
cat /proc/swaps >> $LOGFILE

echo "" >> $LOGFILE
EOF

sudo chmod +x /usr/local/bin/docucenter-memory-monitor.sh
```

### Configurar Monitoreo Automático

```bash
# Agregar al crontab para monitoreo durante jobs críticos
sudo tee -a /etc/crontab << 'EOF'
# Monitoreo DocuCenter - Jobs SQL Server (6-8 AM)
*/15 6-8 * * * root /usr/local/bin/docucenter-memory-monitor.sh

# Monitoreo general cada hora
0 * * * * root /usr/local/bin/docucenter-memory-monitor.sh
EOF

# Reiniciar cron
sudo systemctl restart cron
```

### Script de Validación Post-Instalación

```bash
# Crear script de validación
sudo tee /usr/local/bin/validate-swap-config.sh << 'EOF'
#!/bin/bash
echo "=== VALIDACIÓN CONFIGURACIÓN SWAP DOCUCENTER ==="

echo "1. Verificando memoria total:"
free -h

echo -e "\n2. Verificando swap activo:"
# Usar método que funciona en todos los sistemas
cat /proc/swaps

# Si swapon está disponible
if command -v swapon &> /dev/null; then
    echo "Usando swapon:"
    swapon --show
else
    echo "swapon no disponible, usando /proc/meminfo:"
    grep -i swap /proc/meminfo
fi

echo -e "\n3. Verificando configuración swappiness:"
echo "Actual: $(cat /proc/sys/vm/swappiness)"
echo "Recomendado: 15"

echo -e "\n4. Verificando entrada en fstab:"
grep swap /etc/fstab

echo -e "\n5. Verificando configuración MySQL:"
systemctl is-active mariadb
mysql -u root -p -e "SHOW VARIABLES LIKE 'wait_timeout';" 2>/dev/null | grep wait_timeout

echo -e "\n6. Verificando logs DocuCenter:"
ls -la /var/log/docucenter-memory.log

echo -e "\n=== VALIDACIÓN COMPLETA ==="
EOF

sudo chmod +x /usr/local/bin/validate-swap-config.sh
```

## Validación de la Solución

### Ejecutar Validación Completa

```bash
# Ejecutar script de validación
sudo /usr/local/bin/validate-swap-config.sh

# Probar job problemático manualmente
cd /home/desarrollo/code/docucenter
sudo -u www-data php artisan word:update-sql-server-module

# Monitorear durante ejecución
watch -n 10 'free -h && echo "=== SWAP ===" && cat /proc/swaps'
```

### Verificación de Estado Óptimo

**Memoria esperada después de configuración**:
```
              total        used        free      shared  buff/cache   available
Mem:          3.8Gi       2.8Gi       0.5Gi        50Mi       500Mi       800Mi
Swap:         2.0Gi         0B       2.0Gi
```

**Configuración MySQL verificada**:
```sql
-- Verificar timeouts aumentados
SHOW VARIABLES LIKE 'wait_timeout';        -- Debe ser 28800
SHOW VARIABLES LIKE 'interactive_timeout'; -- Debe ser 28800

-- Verificar buffer pool
SHOW VARIABLES LIKE 'innodb_buffer_pool_size'; -- Debe ser ~1.5GB
```

## Mantenimiento y Monitoreo Continuo

### Alertas de Memoria Crítica

```bash
# Script de alerta (ejecutar cada 5 minutos)
sudo tee /usr/local/bin/memory-alert.sh << 'EOF'
#!/bin/bash
THRESHOLD=90
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100.0)}')

if [ $MEMORY_USAGE -gt $THRESHOLD ]; then
    echo "ALERTA: Uso de memoria crítico: ${MEMORY_USAGE}%" | \
    logger -t "DocuCenter-Memory"
    
    # Opcional: enviar email o notificación
    # echo "Memoria crítica en servidor DocuCenter" | mail -s "Alerta Memoria" admin@empresa.com
fi
EOF

sudo chmod +x /usr/local/bin/memory-alert.sh

# Agregar al cron
echo "*/5 * * * * root /usr/local/bin/memory-alert.sh" | sudo tee -a /etc/crontab
```

### Rotación de Logs

```bash
# Configurar rotación de logs de monitoreo
sudo tee /etc/logrotate.d/docucenter-memory << 'EOF'
/var/log/docucenter-memory.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 root root
}
EOF
```

## Resultado Esperado

Después de implementar esta configuración:

1. **Memoria disponible**: +8GB de swap como respaldo (actualizado desde 2GB)
2. **Timeouts MySQL**: Extendidos para jobs largos (8 horas)
3. **Monitoreo**: Alertas automáticas de uso de memoria
4. **Performance**: Jobs SQL Server ejecutándose sin errores de conexión
5. **Estabilidad**: Sistema más resistente a picos de memoria

**Error resuelto**: `MySQL server has gone away` durante ejecución de `word:update-sql-server-module`

## Validación Final - Swap 8GB Implementado

**Fecha de Implementación**: 29 de Octubre, 2025
**Servidor**: desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01

### Estado del Sistema Después de Actualización

```bash
$ cat /proc/swaps
Filename                Type      Size      Used      Priority
/swapfile.new          file    8388604   1126140           -2

$ free -h
               total        used        free      shared  buff/cache   available
Mem:           3.8Gi       3.2Gi       193Mi       155Mi       465Mi       287Mi
Swap:          8.0Gi       1.1Gi       6.9Gi

$ grep swap /etc/fstab
/swapfile.new none swap sw 0 0
```

### Mejoras Confirmadas

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Memoria disponible | 108Mi | 287Mi | +165% |
| Swap configurado | 0-2GB | 8GB | +300-800% |
| Swap en uso | N/A | 1.1Gi | Activo |
| Estado del sistema | Crítico | Estable | |

### Características de la Implementación

- **Sin reinicio**: Migración realizada con servidor en producción
- **Zero downtime**: Método dual-swap evitó interrupciones
- **Persistente**: Configuración en fstab garantiza permanencia
- **Optimizado**: Swappiness=15 configurado para bases de datos
- **Monitoreado**: Scripts de monitoreo activos

### Validación de Funcionalidad

```bash
# Swap siendo usado activamente por el sistema
$ cat /proc/swaps | grep swapfile.new
/swapfile.new          file    8388604   1126140           -2

# Sistema usando swap para aliviar presión de memoria
# 1.1Gi de swap en uso indica que el sistema está trabajando correctamente
```

**Estado**: Sistema de producción estabilizado con swap de 8GB completamente funcional.

## PLAN DE IMPLEMENTACIÓN RÁPIDA

### Implementación Inmediata (5 minutos)

```bash
# 1. Conectar al servidor crítico
ssh desarrollo@debian-s-1vcpu-2gb-amd-nyc1-01

# 2. Verificar espacio disponible
df -h

# 3. Crear swap inmediatamente (2GB)
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# 4. Verificar activación inmediata
cat /proc/swaps
free -h

# 5. Hacer permanente
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# 6. Optimizar para servidor de DB
echo 'vm.swappiness=15' | sudo tee -a /etc/sysctl.conf
sudo sysctl vm.swappiness=15
```

### Verificación de Éxito

```bash
# Estado esperado después de implementación:
$ free -h
               total        used        free      shared  buff/cache   available
Mem:           3.8Gi       2.8Gi       800Mi       222Mi       419Mi       1.2Gi
Swap:          2.0Gi         0B       2.0Gi

# Swap activo confirmado:
$ cat /proc/swaps
Filename                Type      Size      Used    Priority
/swapfile              file    2097148        0          -2
```

### Próximo Paso Crítico

**Ejecutar inmediatamente** en servidor de producción para resolver crisis de memoria y prevenir fallos del sistema durante jobs de SQL Server.

---

## Actualización Segura de Swap (2GB → 8GB)

### Escenario: Actualizar Swap Sin Reinicio en Servidor con Memoria Crítica

**Contexto**: Servidor con memoria crítica (<10% libre) donde `swapoff` falla por OOM killer.

**Método Dual-Swap**: Crear nuevo swap sin desactivar el existente, luego migrar.

### Paso 1: Verificar Estado Actual

```bash
# Verificar memoria y swap actuales
free -h
cat /proc/swaps
grep swap /etc/fstab

# Resultado esperado (antes de actualización):
# Mem:  3.8Gi total, 3.3Gi usado, 108Mi disponible
# Swap: 2.0Gi (o posiblemente 0B si swap original fue eliminado)
```

### Paso 2: Crear Nuevo Swap de 8GB (Sin Desactivar el Actual)

```bash
# 1. Verificar espacio en disco
df -h

# 2. Crear archivo swap de 8GB con nombre temporal
sudo fallocate -l 8G /swapfile.new

# 3. Configurar permisos restrictivos
sudo chmod 600 /swapfile.new

# 4. Marcar como área de swap
sudo mkswap /swapfile.new

# 5. Activar el nuevo swap (ahora tendrás 2GB + 8GB = 10GB total)
sudo swapon /swapfile.new

# 6. Verificar que ambos swaps están activos
cat /proc/swaps
free -h
```

**Resultado esperado** (dual-swap activo):
```
Filename                Type      Size      Used    Priority
/swapfile              file    2097148        0          -2
/swapfile.new          file    8388604        0          -2
```

### Paso 3: Migrar a Swap Único de 8GB

**Opción A**: Si el archivo `/swapfile` original existe:

```bash
# 1. Intentar desactivar swap original
sudo swapoff /swapfile

# 2. Si el comando anterior falla con "Killed", el sistema tiene demasiada presión
# En ese caso, continuar con Opción B

# 3. Si swapoff tuvo éxito, eliminar el archivo viejo
sudo rm /swapfile

# 4. Renombrar el nuevo swap
sudo mv /swapfile.new /swapfile

# 5. Actualizar fstab
sudo cp /etc/fstab /etc/fstab.backup.$(date +%Y%m%d_%H%M%S)
sudo sed -i 's|/swapfile.new none swap sw 0 0|/swapfile none swap sw 0 0|g' /etc/fstab
```

**Opción B**: Si `/swapfile` no existe o swapoff falla (CASO COMÚN):

```bash
# 1. Actualizar fstab para apuntar al nuevo archivo directamente
sudo cp /etc/fstab /etc/fstab.backup.$(date +%Y%m%d_%H%M%S)
sudo sed -i 's|/swapfile none swap sw 0 0|/swapfile.new none swap sw 0 0|g' /etc/fstab

# 2. Verificar cambio en fstab
grep swap /etc/fstab
# Debe mostrar: /swapfile.new none swap sw 0 0

# 3. Verificar que el sistema está usando el nuevo swap
cat /proc/swaps
free -h
```

### Paso 4: Validación Final

```bash
# Verificar configuración completa
echo "=== FSTAB ==="
grep swap /etc/fstab

echo -e "\n=== SWAP ACTIVO ==="
cat /proc/swaps

echo -e "\n=== MEMORIA TOTAL ==="
free -h

echo -e "\n=== SWAPPINESS ==="
cat /proc/sys/vm/swappiness
```

**Estado esperado después de migración**:
```bash
$ grep swap /etc/fstab
/swapfile.new none swap sw 0 0

$ cat /proc/swaps
Filename                Type      Size      Used    Priority
/swapfile.new          file    8388604   1126140          -2

$ free -h
               total        used        free      shared  buff/cache   available
Mem:           3.8Gi       3.2Gi       193Mi       155Mi       465Mi       287Mi
Swap:          8.0Gi       1.1Gi       6.9Gi
```

### Resultado de la Actualización

**Antes**:
- Memoria disponible: 108Mi (crítico)
- Swap: 2GB o 0B
- Riesgo: OOM killer activo

**Después**:
- Memoria disponible: 287Mi (mejorado 165%)
- Swap: 8GB activo (usando 1.1Gi)
- Estado: Sistema estable con respaldo suficiente

### Notas Importantes

1. **Dual-Swap Temporal**: Es seguro tener dos archivos swap activos simultáneamente durante la migración
2. **OOM Killer**: Si `swapoff` falla con "Killed", simplemente mantén el nuevo archivo `/swapfile.new`
3. **Reinicio NO Requerido**: Todo el proceso se realiza sin reiniciar el servidor
4. **fstab**: Asegúrate de que apunte al archivo swap correcto para persistencia después de reinicios
5. **Monitoreo**: El sistema comenzará a usar el nuevo swap inmediatamente según necesidad

### Comandos de Verificación Rápida

```bash
# Ver swap activo y uso actual
cat /proc/swaps && echo "---" && free -h | grep Swap

# Verificar que fstab está correcto para próximo reinicio
grep swap /etc/fstab

# Monitorear uso de swap en tiempo real
watch -n 5 'cat /proc/swaps && echo "---" && free -h'
```

---

### Próximo Paso Crítico (COMPLETADO)
