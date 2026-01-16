# Testing: Lightspeed Serie R - Sincronización de Ventas con Filtro por Shop

## Comando de Prueba

```bash
php artisan lightspeed:test-serie-r-sync {organizationId} [opciones]
```

## Propósito

Probar la sincronización de ventas de Lightspeed Serie R verificando que:
1. La configuración de `shop_id` se carga correctamente
2. El filtro por `shopID` se aplica en la API
3. Solo se obtienen ventas de la tienda configurada
4. La sincronización procesa correctamente los datos

## Uso en Docker

```bash
# Ejecutar dentro del contenedor
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync {organizationId}
```

## Argumentos y Opciones

### Argumento Requerido

- `organizationId`: ID de la organización a probar

### Opciones

- `--ticket=`: Número de ticket específico para probar (opcional)
- `--shop-id=`: Shop ID personalizado para sobrescribir la configuración (opcional)
- `--sync`: Ejecutar sincronización real (por defecto solo muestra preview)

## Ejemplos de Uso

### 1. Preview Básico (Sin Sincronización)

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123
```

**Qué hace:**
- Muestra configuración de la organización
- Muestra configuración del shop
- Obtiene ventas de la API con el filtro configurado
- Muestra preview de las ventas (hasta 10)
- Muestra distribución por shop_id
- **NO** ejecuta la sincronización

### 2. Preview con Shop ID Personalizado

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123 --shop-id=9
```

**Qué hace:**
- Sobrescribe la configuración de shop_id con el valor 9
- Útil para probar diferentes shops sin modificar la configuración

### 3. Probar Ticket Específico

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123 --ticket=220000038538
```

**Qué hace:**
- Busca solo el ticket especificado
- Útil para debugging de ventas específicas

### 4. Ejecutar Sincronización Real

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123 --sync
```

**Qué hace:**
- Muestra preview
- Solicita confirmación
- Ejecuta el job `SetSalesOrdersJob`
- Procesa las ventas en la base de datos

### 5. Combinación: Shop Personalizado + Sincronización

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123 --shop-id=9 --sync
```

## Salida del Comando

El comando muestra:

```
═══════════════════════════════════════════════════════
  Test Lightspeed Serie R - Sincronización de Ventas
═══════════════════════════════════════════════════════

📋 Organización: VOGLIA (ID: 123)
   Base de datos: 9_734_1672_56

✅ Conexión encontrada (ID: 45)

🏪 Configuración de Shop:
   Shop ID: 9
   Shop Name: VOGLIA Multiplaza
   Account ID: 192176
   Estado: ✅ Activo

📡 Parámetros de API:
   Limit: 20
   Sort: -completeTime
   Completed: true
   Shop ID Filter: 9 ✅

🔍 Obteniendo preview de ventas...

📊 Ventas encontradas: 15

┌─────────────────────────────────────────────────────────────┐
│                    PREVIEW DE VENTAS                        │
└─────────────────────────────────────────────────────────────┘
+-----------------+-----------+----------+----------+---------------------+
| Ticket          | Sale ID   | Shop ID  | Total    | Fecha              |
+-----------------+-----------+----------+----------+---------------------+
| 220000038538    | 1234567   | 9        | $125.50  | 2025-11-06T10:30:00|
| 220000038539    | 1234568   | 9        | $89.99   | 2025-11-06T11:15:00|
+-----------------+-----------+----------+----------+---------------------+
... y 13 ventas más

📈 Distribución por Shop:
   ✅ Shop 9: 15 venta(s)

✅ Filtro por shop_id funcionando correctamente

💡 Para ejecutar la sincronización real, usa: --sync

═══════════════════════════════════════════════════════
  Test completado
═══════════════════════════════════════════════════════
```

## Validaciones que Realiza

### 1. Configuración de Organización
- ✅ Organización existe
- ✅ Tiene conexión Lightspeed Serie R
- ✅ Configuración de shop (si existe)

### 2. Filtro por Shop ID
- ✅ Parámetro `shopID` se agrega al request
- ✅ Solo se obtienen ventas del shop configurado
- ⚠️ Alerta si se encuentran ventas de múltiples shops (filtro no funciona)

### 3. Datos de Ventas
- 📊 Cantidad de ventas encontradas
- 📋 Preview de las primeras 10 ventas
- 📈 Distribución por shop_id

## Casos de Prueba

### Caso 1: Organización SIN configuración de shop

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 456
```

**Resultado Esperado:**
```
⚠️  No hay configuración de shop (se procesarán todas las tiendas)
Shop ID Filter: No aplicado (todas las tiendas)
```

### Caso 2: Organización CON configuración de shop

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123
```

**Resultado Esperado:**
```
Shop ID: 9
Shop ID Filter: 9 ✅
✅ Filtro por shop_id funcionando correctamente
```

### Caso 3: Sobrescribir shop_id

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123 --shop-id=15
```

**Resultado Esperado:**
```
⚡ Usando shop_id personalizado: 15 (sobrescribe configuración)
Shop ID Filter: 15 ✅
```

### Caso 4: No hay ventas

```bash
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync 123 --ticket=999999
```

**Resultado Esperado:**
```
⚠️  No se encontraron ventas con los parámetros especificados
```

## Monitoreo de Logs

Cuando ejecutas con `--sync`, puedes monitorear los logs:

```bash
# Log general del job
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "SetSalesOrdersJob-Serie-R"

# Log del filtro aplicado
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "FilteringByShop"
```

## Troubleshooting

### Error: "Organización no encontrada"

```bash
# Verificar organizaciones disponibles
docker exec -it docucenter-app-1 php artisan tinker
>>> \App\Models\Organization::pluck('name', 'id')->toArray()
```

### Error: "No se encontró conexión Lightspeed Serie R"

```bash
# Verificar conexiones de la organización
docker exec -it docucenter-app-1 php artisan tinker
>>> \App\Models\Connection::where('organization_id', 123)->where('application', 'lightspeed-serie-r')->first()
```

### Ventas de múltiples shops cuando debería filtrar

**Posibles causas:**
1. El API de Lightspeed no soporta el parámetro `shopID` (verificar documentación)
2. La configuración de shop_id es incorrecta
3. El shop_id no existe en la cuenta

**Solución:**
```bash
# Verificar configuración
docker exec -it docucenter-app-1 php artisan tinker
>>> \App\Models\LightspeedSerieRShopConfiguration::where('organization_id', 123)->first()
```

## Integración con CI/CD

Puedes usar este comando en tests automatizados:

```bash
# Script de prueba
#!/bin/bash
ORG_ID=123

# Ejecutar test sin sincronización
docker exec -it docucenter-app-1 php artisan lightspeed:test-serie-r-sync $ORG_ID

# Capturar exit code
if [ $? -eq 0 ]; then
    echo "✅ Test passed"
else
    echo "❌ Test failed"
    exit 1
fi
```

## Relacionado

- **Job**: `app/Jobs/LightspeedSerieR/SetSalesOrdersJob.php`
- **Modelo**: `app/Models/LightspeedSerieRShopConfiguration.php`
- **Helper**: `app/Support/helper.php` - `all_lightspeed_serie_r_shop_configuration()`
- **Documentación**: `docs/technical/lightspeed-serie-r-shop-management.md`
