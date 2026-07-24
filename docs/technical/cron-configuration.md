# Configuracion de Cron para Laravel

Documentacion tecnica sobre la configuracion correcta del cron que ejecuta las tareas programadas de Laravel (`php artisan schedule:run`) en ambientes de produccion.

## Problema: Permission denied en storage/logs/

### Sintoma

La aplicacion lanza excepciones de Monolog con mensajes como:

```
The stream or file "/home/desarrollo/code/docucenter/storage/logs/laravel-2026-07-24.log"
could not be opened in append mode: Failed to open stream: Permission denied
```

Esto suele ocurrir de manera silenciosa: la peticion web falla al intentar loguear y la app puede romperse o perder informacion critica.

### Causa raiz

Laravel usa `Monolog\Handler\RotatingFileHandler` que crea un nuevo archivo de log por dia (formato `laravel-YYYY-MM-DD.log`). Al rotar:

1. El proceso que escribe el log crea el nuevo archivo.
2. El archivo hereda el propietario y grupo del usuario que lo creo.
3. Si el proceso web (`www-data`) y el cron corren con usuarios **distintos**, el archivo del nuevo dia queda con permisos que el servidor web no puede usar.

**Caso real observado en produccion:**

| Archivo | Propietario | Grupo | Permisos | Servible por web |
|---|---|---|---|---|
| laravel-2026-07-21.log | desarrollo | www-data | 777 | Si |
| laravel-2026-07-22.log | desarrollo | desarrollo | 644 | **No** |
| laravel-2026-07-23.log | desarrollo | desarrollo | 644 | **No** |
| laravel-2026-07-24.log | desarrollo | desarrollo | 644 | **No** |

El dia 21 fue el dia del deploy (creado por `www-data`); a partir de la rotacion del 22, el cron que corria como `desarrollo` tomo el control y dejo los archivos ilegibles para la app.

### Solucion recomendada

El cron de Laravel **debe correr como el mismo usuario que sirve la aplicacion web** (`www-data` en este entorno). Asi:

- El archivo rotado se crea con propietario `www-data`.
- El servidor web (PHP-FPM/Apache) puede escribir sin problemas.
- No hay desfase entre quien crea y quien lee los logs.

## Configuracion correcta

### Opcion A: archivo en `/etc/cron.d/` (recomendado)

Archivo `/etc/cron.d/docucenter`:

```cron
# DocuCenter - Laravel schedule runner
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

* * * * * www-data cd /home/desarrollo/code/docucenter && php artisan schedule:run >> /dev/null 2>&1
```

**Ventajas:**
- No depende del crontab de un usuario concreto.
- Facil de versionar y replicar entre servidores.
- Sobrevive a `crontab -e` accidentales.

### Opcion B: crontab del usuario `www-data`

```bash
sudo crontab -u www-data -e
```

Contenido:

```cron
* * * * * cd /home/desarrollo/code/docucenter && php artisan schedule:run >> /dev/null 2>&1
```

### Lo que NO se debe hacer

- Tener el cron en el crontab del usuario humano (`desarrollo` en este caso). Esto es justo lo que causo el bug.
- Usar `root`. Funciona para los logs, pero los Jobs se ejecutarian con privilegios indebidos.

## Setup automatizado

El repo incluye un script que crea el archivo `/etc/cron.d/docucenter` y reinicia el servicio:

```bash
sudo bash scripts/setup-production-cron.sh
```

Ver `scripts/README.md` para mas detalles.

## Diagnostico

### Verificar que cron esta activo

```bash
sudo systemctl status cron
```

Debe decir `active (running)`.

### Ver las tareas que se ejecutan

```bash
# Crontab del sistema
cat /etc/cron.d/docucenter

# Crontab de un usuario especifico
sudo crontab -u www-data -l
```

### Ver el log de ejecucion de cron

```bash
sudo grep CRON /var/log/syslog | tail -30
```

Lineas correctas (con la fix aplicado):

```
CRON[12345]: (www-data) CMD (cd /home/desarrollo/code/docucenter && php artisan schedule:run >> /dev/null 2>&1)
```

Si ves `(desarrollo) CMD` en lugar de `(www-data) CMD`, todavia hay un crontab viejo que eliminar.

### Probar el comando manualmente

```bash
sudo -u www-data bash -c "cd /home/desarrollo/code/docucenter && php artisan schedule:run"
```

Si esto se ejecuta sin errores, el cron configurado correctamente tampoco fallara.

### Verificar que el log de Laravel se actualiza

```bash
ls -la /home/desarrollo/code/docucenter/storage/logs/laravel-$(date +%Y-%m-%d).log
tail -5 /home/desarrollo/code/docucenter/storage/logs/laravel-$(date +%Y-%m-%d).log
```

Si la fecha/hora del archivo es reciente, el cron esta corriendo.

## Fix de emergencia (logs ya rotos)

Si la app esta caida por logs con permisos incorrectos, el fix inmediato es:

```bash
# Reasignar grupo y permisos a todos los logs
sudo chown desarrollo:www-data /home/desarrollo/code/docucenter/storage/logs/laravel-*.log
sudo chmod 664 /home/desarrollo/code/docucenter/storage/logs/laravel-*.log
```

Esto es solo un parche. El fix real es la configuracion del cron descrita arriba, para evitar que el problema vuelva a ocurrir en la siguiente rotacion diaria.

## Aplicaciones relacionadas en el mismo servidor

Si en el mismo servidor hay otras apps Laravel (por ejemplo `apconecta`), el mismo principio aplica:

| App | Comando cron | Usuario requerido |
|---|---|---|
| docucenter | `php artisan schedule:run` | www-data |
| apconecta | `php artisan schedule:run` | www-data |
| weirdbot (Python) | `python scripts/proactive_followup.py` | desarrollo (no comparte storage con Laravel) |

## Referencias

- [Laravel - Task Scheduling](https://laravel.com/docs/9.x/scheduling)
- [Monolog RotatingFileHandler](https://github.com/Seldaek/monolog/blob/main/src/Monolog/Handler/RotatingFileHandler.php)
- Script de setup: `scripts/setup-production-cron.sh`
