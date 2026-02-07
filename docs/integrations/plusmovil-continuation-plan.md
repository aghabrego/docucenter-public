# Plan de Continuación: Integración PlusMóvil

**Fecha:** 9 de noviembre de 2025  
**Estado Actual:** ✅ Implementación completa, 1 conexión creada  
**Siguiente Fase:** Testing y Importación Automática

---

## 📊 Estado Actual

### ✅ Completado (Fases 1-2)
- ✅ Modelo Connection con métodos AWS Cognito
- ✅ AWS SDK instalado (`aws/aws-sdk-php`)
- ✅ Componente Livewire para crear conexiones
- ✅ Vista de formulario PlusMóvil
- ✅ Servicio PlusMovilInvoiceService mejorado
- ✅ Comando de testing (`plusmovil:test-connection`)
- ✅ URLs corregidas (xka96gucj8/qa y /prod)
- ✅ **1 conexión PlusMóvil creada en BD**

---

## 🎯 Fase 3: Testing de Conexión Existente

### Paso 1: Verificar Datos de la Conexión

```bash
# Ver información completa de la conexión
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::where('application', 'plusmovil')->first();
echo '═══════════════════════════════════════' . PHP_EOL;
echo 'INFORMACIÓN DE CONEXIÓN PLUSMÓVIL' . PHP_EOL;
echo '═══════════════════════════════════════' . PHP_EOL;
echo 'ID: ' . \$conn->id . PHP_EOL;
echo 'Nombre: ' . \$conn->name . PHP_EOL;
echo 'Organización ID: ' . \$conn->organization_id . PHP_EOL;
echo 'Ambiente: ' . (\$conn->settings['environment'] ?? 'N/A') . PHP_EOL;
echo 'Usuario: ' . (\$conn->settings['username'] ?? 'N/A') . PHP_EOL;
echo 'Base URL: ' . (\$conn->settings['base_url'] ?? 'N/A') . PHP_EOL;
echo 'Client ID: ' . substr(\$conn->settings['client_id'] ?? 'N/A', 0, 20) . '...' . PHP_EOL;
echo '───────────────────────────────────────' . PHP_EOL;
echo 'Tiene token: ' . (!empty(\$conn->settings['access_token']) ? 'Sí' : 'No') . PHP_EOL;
echo 'Token válido: ' . (\$conn->hasPlusMovilValidToken() ? 'Sí ✅' : 'No ❌') . PHP_EOL;
if (!empty(\$conn->settings['token_expires_at'])) {
    echo 'Expira en: ' . \$conn->settings['token_expires_at'] . PHP_EOL;
}
echo '═══════════════════════════════════════' . PHP_EOL;
"
```

**Resultado esperado:**
- ✅ Muestra todos los datos de configuración
- ✅ Indica si tiene token válido
- ✅ Muestra fecha de expiración

### Paso 2: Ejecutar Test Completo

```bash
# Test de autenticación y consulta de facturas
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection 1

# Con rango de fechas específico
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection 1 \
  --start-date=2025-10-01 \
  --end-date=2025-11-09
```

**Resultado esperado:**
```
🔍 Probando conexión PlusMóvil ID: 1
📅 Rango de fechas: 2025-10-01 a 2025-11-09

✅ Conexión encontrada: PlusMóvil QA
┌────────────┬──────────────────────────────────┐
│ Campo      │ Valor                            │
├────────────┼──────────────────────────────────┤
│ ID         │ 1                                │
│ Nombre     │ PlusMóvil QA                     │
│ Ambiente   │ qa                               │
│ Usuario    │ usuario@empresa.com              │
│ Token      │ Sí ✅                            │
└────────────┴──────────────────────────────────┘

🔐 Probando autenticación...
✅ Token obtenido exitosamente
   Token (primeros 20 caracteres): eyJraWQiOiJc...

📋 Consultando facturas...
✅ Facturas obtenidas: 25

┌────┬────────────┬────────────┬──────────────┬─────────┬─────────┐
│ ID │ Número     │ Fecha      │ Cliente      │ Total   │ Estado  │
├────┼────────────┼────────────┼──────────────┼─────────┼─────────┤
│ 86 │ INV-00086  │ 2025-10-15 │ Cliente A    │ 1,250.00│ paid    │
│ 87 │ INV-00087  │ 2025-10-16 │ Cliente B    │ 850.00  │ pending │
...
```

### Paso 3: Verificar Logs

```bash
# Ver últimas líneas del log
docker exec -it docucenter_laravel.test tail -n 100 storage/logs/laravel.log

# Filtrar solo logs de PlusMóvil
docker exec -it docucenter_laravel.test grep -i "plusmovil" storage/logs/laravel.log | tail -n 50
```

### Paso 4: Troubleshooting (si hay errores)

#### Error: "Access token no configurado"
**Causa:** No se pudo obtener token de Cognito  
**Solución:**
1. Verificar credenciales en settings
2. Verificar que Client ID sea correcto
3. Revisar logs de AWS Cognito

```bash
# Forzar regeneración de token
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
\$token = \$conn->refreshPlusMovilToken();
echo 'Token regenerado: ' . (!empty(\$token) ? 'Sí ✅' : 'No ❌') . PHP_EOL;
"
```

#### Error 401: Unauthorized
**Causa:** Token inválido o expirado  
**Solución:** El sistema debería auto-refrescar, pero puedes forzarlo:

```bash
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
\$conn->settings = array_merge(\$conn->settings, [
    'access_token' => null,
    'token_expires_at' => null
]);
\$conn->save();
echo 'Token limpiado. Ejecuta el comando de test nuevamente.' . PHP_EOL;
"
```

#### Error: Could not resolve host
**Causa:** URL incorrecta en base_url  
**Solución:** Verificar que use xka96gucj8, no 28cwop8rj6

```bash
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
echo 'Base URL actual: ' . \$conn->settings['base_url'] . PHP_EOL;
echo 'Debería ser: https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa' . PHP_EOL;
"
```

---

## 🎯 Fase 4: Importación de Facturas

### Archivos Creados
- ✅ `app/Jobs/PlusMóvil/ImportInvoicesJob.php` - Job de importación
- ✅ `app/Console/Commands/PlusMóvil/ImportInvoicesCommand.php` - Comando para ejecutar

### Paso 1: Revisar Estructura de Tablas

Antes de importar, verifica que existan las tablas necesarias:

```bash
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
\$org = \$conn->organization;
\Illuminate\Support\Facades\DB::connection()->useDatabase(\$org->database);

echo 'Base de datos: ' . \$org->database . PHP_EOL;
echo 'Tabla SalesHeaderImp existe: ' . (\Illuminate\Support\Facades\Schema::hasTable('Sales_Header_Imp') ? 'Sí ✅' : 'No ❌') . PHP_EOL;
echo 'Tabla SalesDetailImp existe: ' . (\Illuminate\Support\Facades\Schema::hasTable('Sales_Detail_Imp') ? 'Sí ✅' : 'No ❌') . PHP_EOL;

\Illuminate\Support\Facades\DB::connection()->useDatabase(env('DB_DATABASE'));
"
```

### Paso 2: Ejecutar Importación de Prueba

```bash
# Importación síncrona (espera a que termine)
docker exec -it docucenter_laravel.test php artisan plusmovil:import-invoices 1 \
  --start-date=2025-11-01 \
  --end-date=2025-11-09

# Importación en cola (ejecución en background)
docker exec -it docucenter_laravel.test php artisan plusmovil:import-invoices 1 \
  --start-date=2025-11-01 \
  --end-date=2025-11-09 \
  --queue
```

**Resultado esperado:**
```
📥 Importando facturas PlusMóvil
🔍 Conexión ID: 1
📅 Rango: 2025-11-01 a 2025-11-09

✅ Conexión encontrada: PlusMóvil QA
🏢 Organización ID: 1

⏳ Ejecutando importación (puede tomar varios minutos)...

✅ Importación completada
📊 Revisa los detalles en: storage/logs/laravel.log
```

### Paso 3: Verificar Facturas Importadas

```bash
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
\$org = \$conn->organization;
\Illuminate\Support\Facades\DB::connection()->useDatabase(\$org->database);

\$count = \App\Models\SalesHeaderImp::count();
echo 'Total facturas importadas: ' . \$count . PHP_EOL;

if (\$count > 0) {
    \$recent = \App\Models\SalesHeaderImp::latest('created_at')->take(5)->get(['InvoiceNumber', 'CustomerName', 'Total', 'Date']);
    echo PHP_EOL . 'Últimas 5 facturas:' . PHP_EOL;
    foreach (\$recent as \$inv) {
        echo '- ' . \$inv->InvoiceNumber . ' | ' . \$inv->CustomerName . ' | \$' . number_format(\$inv->Total, 2) . ' | ' . \$inv->Date . PHP_EOL;
    }
}

\Illuminate\Support\Facades\DB::connection()->useDatabase(env('DB_DATABASE'));
"
```

### Paso 4: Ajustar Mapeo de Campos

**IMPORTANTE:** El Job `ImportInvoicesJob.php` tiene un mapeo genérico de campos. Deberás ajustarlo según la estructura real de:

1. **Respuesta de PlusMóvil API** - Ver qué campos devuelve realmente
2. **Estructura de SalesHeaderImp** - Tus campos específicos
3. **Estructura de SalesDetailImp** - Tus campos específicos

**Para ver la estructura real de una factura:**

```bash
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
\$service = new \App\Services\PlusMovilInvoiceService();
\$service->setConnection(\$conn);

\$result = \$service->getProformas(['limit' => 1]);
\$invoice = \$result['data'][0] ?? null;

if (\$invoice) {
    echo 'Factura ID: ' . \$invoice['id'] . PHP_EOL;
    \$full = \$service->getInvoiceWithItems(\$invoice['id']);
    echo PHP_EOL . 'Estructura completa:' . PHP_EOL;
    print_r(\$full);
}
"
```

Luego edita `ImportInvoicesJob.php` método `importInvoice()` para mapear correctamente.

---

## 🎯 Fase 5: Automatización (Opcional)

### Opción A: Programar Importación Diaria

Edita `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Importar facturas PlusMóvil diariamente a las 2 AM
    $schedule->call(function () {
        $connections = \App\Models\Connection::where('application', 'plusmovil')->get();
        
        foreach ($connections as $connection) {
            \App\Jobs\PlusMóvil\ImportInvoicesJob::dispatch(
                $connection,
                now()->subDay()->format('Y-m-d'), // Ayer
                now()->format('Y-m-d')            // Hoy
            );
        }
    })->dailyAt('02:00');
}
```

### Opción B: Importación Manual Programada

```bash
# Agregar a crontab del servidor
0 2 * * * cd /ruta/docucenter && docker exec -it docucenter_laravel.test php artisan plusmovil:import-invoices 1 --queue
```

---

## 📋 Checklist de Continuación

### Fase 3: Testing ⬅️ **EMPEZAR AQUÍ**
- [ ] Verificar datos de conexión existente
- [ ] Ejecutar comando `plusmovil:test-connection`
- [ ] Verificar que obtiene token correctamente
- [ ] Verificar que consulta facturas exitosamente
- [ ] Probar obtención de items de factura
- [ ] Revisar logs para errores

### Fase 4: Importación
- [ ] Verificar existencia de tablas Sales_Header_Imp y Sales_Detail_Imp
- [ ] Verificar estructura real de respuesta de API
- [ ] Ajustar mapeo de campos en ImportInvoicesJob
- [ ] Ejecutar importación de prueba (1 semana de datos)
- [ ] Verificar facturas importadas en BD
- [ ] Validar que items se importaron correctamente
- [ ] Probar importación en cola (background)

### Fase 5: Automatización (Opcional)
- [ ] Decidir frecuencia de importación (diaria, cada 6 horas, etc)
- [ ] Agregar schedule en Kernel.php
- [ ] Configurar supervisord para queue worker
- [ ] Monitorear logs de importaciones automáticas

---

## 🚀 Comandos Rápidos

```bash
# Testing de conexión
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection 1

# Importación síncrona (espera resultado)
docker exec -it docucenter_laravel.test php artisan plusmovil:import-invoices 1 \
  --start-date=2025-11-01 --end-date=2025-11-09

# Importación en cola (background)
docker exec -it docucenter_laravel.test php artisan plusmovil:import-invoices 1 \
  --start-date=2025-11-01 --end-date=2025-11-09 --queue

# Ver logs
docker exec -it docucenter_laravel.test tail -f storage/logs/laravel.log

# Verificar facturas importadas
docker exec -it docucenter_laravel.test php artisan tinker --execute="
\$conn = \App\Models\Connection::find(1);
\$org = \$conn->organization;
DB::connection()->useDatabase(\$org->database);
echo 'Total facturas: ' . \App\Models\SalesHeaderImp::count() . PHP_EOL;
DB::connection()->useDatabase(env('DB_DATABASE'));
"
```

---

## 📞 Soporte

Si encuentras problemas:

1. **Revisar logs:** `storage/logs/laravel.log`
2. **Verificar conexión:** `plusmovil:test-connection`
3. **Verificar estructura de API:** Ver respuesta real con Tinker
4. **Ajustar mapeo de campos:** Editar `ImportInvoicesJob::importInvoice()`

---

**Creado:** 9 de noviembre de 2025  
**Próximo paso:** Ejecutar `plusmovil:test-connection 1`
