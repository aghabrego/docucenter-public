# Configuración adicional para config/queue.php

# Agregar esta configuración en connections.redis:
'zoho-purchase-orders' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'zoho-purchase-orders',
    'retry_after' => 300,
    'block_for' => null,
],

# En .env agregar:
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis

# Para supervisor, agregar en supervisord.conf:
[program:zoho-purchase-orders-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=zoho-purchase-orders --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/zoho-purchase-orders.log
