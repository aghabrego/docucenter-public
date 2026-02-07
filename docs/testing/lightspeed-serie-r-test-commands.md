#  Comandos de Testing para Lightspeed Serie R

## **Número de Ticket Real para Testing**

### **Datos de Testing Identificados**

**Organización de Prueba:**
- **Nombre**: VOGLIA MULTIPLAZA, S.A.
- **Account ID**: `192176`
- **Ticket Number**: `220000038728`
- **Sale ID**: `38728`

### **Comandos de Testing Disponibles**

#### **1. Comando Principal con Ticket Específico**
```bash
# Procesar ticket específico en organización específica
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --organization_id=[ORG_ID] \
  --ticket_number=220000038728 \
  --pause_at_every_step=1

# Ejemplo con organización de VOGLIA MULTIPLAZA
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --organization_id=[ID_DE_VOGLIA] \
  --ticket_number=220000038728 \
  --pause_at_every_step=1
```

#### **2. Testing Completo sin Filtros**
```bash
# Procesar todas las organizaciones con Lightspeed Serie R
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --pause_at_every_step=2

# Con organización específica pero todos los tickets
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --organization_id=[ORG_ID] \
  --pause_at_every_step=2
```

#### **3. Testing de Conexión y Token**
```bash
# Generar token de acceso para Serie R
docker exec -it docucenter-app-1 php artisan word:create-access-token-serie-r \
  --organization_id=[ORG_ID]
```

### **Verificación de Datos de Testing**

#### **Buscar Organization ID de VOGLIA MULTIPLAZA**
```bash
# Buscar en la base de datos la organización
docker exec -it docucenter-app-1 php artisan tinker
# Ejecutar en tinker:
\App\Models\Organization::where('nombre', 'LIKE', '%VOGLIA%')->get(['id', 'nombre', 'database']);
```

#### **Verificar Conexiones Serie R**
```bash
# Ver todas las conexiones de Lightspeed Serie R
docker exec -it docucenter-app-1 php artisan tinker
# Ejecutar en tinker:
\App\Models\Connection::where('application', 'lightspeed-serie-r')
  ->with('organization:id,nombre')
  ->get(['id', 'organization_id', 'application', 'created_at']);
```

### **Datos de Testing Verificados en el Código**

#### **Archivo**: `tests/Unit/LightspeedTest.php`
```php
// Línea 88: Organización de prueba
'nombre' => 'VOGLIA MULTIPLAZA, S.A.',

// Línea 144: Account ID hardcodeado
$accountId = 192176;

// Línea 146: Ticket de prueba
'ticketNumber' => "220000038728",

// Línea 169: Sale ID de prueba  
$saleId = 38728;
```

#### **Archivo**: `app/Jobs/LightspeedSerieR/SetSalesOrdersJob.php`
- **Account ID hardcodeado**: `192176` (múltiples líneas)
- **Soporte para ticket_number**: Líneas 269-270

### **Casos de Uso para Testing**

#### **Caso 1: Testing de Ticket Específico**
```bash
# Validar que un ticket específico se procese correctamente
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --ticket_number=220000038728 \
  --pause_at_every_step=1
```

#### **Caso 2: Testing de Shop Information (Nueva Funcionalidad)**
```bash
# Verificar que se obtenga información de tienda con el ticket de prueba
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --ticket_number=220000038728 \
  --organization_id=[ID_VOGLIA] \
  --pause_at_every_step=1

# Revisar logs después para confirmar shop_name
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "shop_name"
```

#### **Caso 3: Testing de Jobs en Chain**
```bash
# El comando ejecuta automáticamente:
# 1. CreateAccessTokenSerieRJob
# 2. SetSalesOrdersJob (con ticket específico si se proporciona)
```

### **Logging y Debugging**

#### **Monitorear Ejecución**
```bash
# Ver logs en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log

# Filtrar logs específicos de Serie R
docker exec -it docucenter-app-1 grep "SetSalesOrdersJob-Serie-R" storage/logs/laravel.log

# Ver información de shop incluida en los logs
docker exec -it docucenter-app-1 grep "shop_name" storage/logs/laravel.log
```

#### **Verificar Jobs en Queue**
```bash
# Ver jobs en cola Redis
docker exec -it docucenter-redis-1 redis-cli
# En Redis CLI:
KEYS *jobs*
LLEN default
```

### **Consideraciones Importantes**

#### **Account ID Hardcodeado**
**NOTA CRÍTICA**: El Job `SetSalesOrdersJob.php` tiene el Account ID `192176` hardcodeado en múltiples líneas. Esto significa que:

1. **Solo funciona para la organización VOGLIA MULTIPLAZA**
2. **Otras organizaciones Serie R pueden fallar**
3. **Es necesario refactorizar para usar Account ID dinámico**

#### **Datos de Testing Reales**
**CONFIRMADO**: Los datos de testing son reales:
- Ticket `220000038728` existe en el sistema
- Account ID `192176` es válido
- Sale ID `38728` corresponde al ticket

### **Comando de Testing Recomendado**

```bash
# Comando completo para testing con datos reales
docker exec -it docucenter-app-1 php artisan word:update-lightspeed-serie-r \
  --organization_id=$(docker exec -it docucenter-app-1 php artisan tinker --execute="\App\Models\Organization::where('nombre', 'LIKE', '%VOGLIA%')->value('id')") \
  --ticket_number=220000038728 \
  --pause_at_every_step=1
```

Este comando:
1. Usa datos de testing reales verificados
2. Procesa un ticket específico conocido
3. Incluye pausa para debugging
4. Funciona con la configuración actual hardcodeada

---

## Referencias

- **Archivo de Test**: `tests/Unit/LightspeedTest.php`
- **Job Principal**: `app/Jobs/LightspeedSerieR/SetSalesOrdersJob.php`
- **Comando**: `app/Console/Commands/POS/UpdateLightspeedSerierModule.php`
- **Kernel**: Programado cada 5 minutos en producción
