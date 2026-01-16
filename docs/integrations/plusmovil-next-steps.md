# Próximos Pasos: Implementación PlusMóvil

**Fecha:** 9 de noviembre de 2025  
**Estado:** ✅ Implementación Completa - Listo para Testing

---

## ✅ Completado

### 1. **Modelo Connection**
- ✅ Métodos de autenticación Cognito implementados
- ✅ Imports agregados (Log, CognitoIdentityProviderClient)
- ✅ Documentación completa

### 2. **AWS SDK**
- ✅ Instalado: `aws/aws-sdk-php` v3.359.8
- ✅ Clase `CognitoIdentityProviderClient` disponible

### 3. **Componente Livewire Create**
- ✅ Agregado 'plusmovil' a aplicaciones válidas
- ✅ Validaciones para environment, username, password
- ✅ Propiedad `$plusmovil_environment = 'qa'`
- ✅ Métodos helpers: `getPlusMovilClientId()`, `getPlusMovilBaseUrl()`
- ✅ Auto-completar client_id y base_url en método `create()`
- ✅ Encriptación de password con `encrypt()`

### 4. **Vista del Formulario**
- ✅ Sección completa para PlusMóvil
- ✅ Select de ambiente (QA/Prod)
- ✅ Input de username (email)
- ✅ Input de password (type password)
- ✅ Alert informativo con detalles

### 5. **PlusMovilInvoiceService**
- ✅ Importar modelo Connection
- ✅ Método `setConnection()` implementado
- ✅ Auto-obtención de token desde Connection
- ✅ Manejo de 401 con auto-refresh en `getProformas()`
- ✅ Método `getInvoiceWithItems()` implementado con auto-refresh
- ✅ Logging detallado de operaciones

### 6. **Comando de Testing**
- ✅ Comando `plusmovil:test-connection` creado
- ✅ Test de autenticación
- ✅ Test de consulta de facturas
- ✅ Test de obtención de items
- ✅ Tablas informativas

---

## 📋 Testing - Siguiente Fase

### Preparación de Testing

#### 1. **Crear Conexión de Prueba (QA)**

Opción A - Via Interface Web:
1. Ir a Conexiones → Crear Nueva
2. Seleccionar aplicación: **PlusMóvil**
3. Seleccionar ambiente: **QA**
4. Ingresar credenciales:
   - Usuario: `tu-usuario@email.com`
   - Contraseña: `tu-password`
5. Guardar

Opción B - Via Tinker:
```php
docker exec -it docucenter_laravel.test php artisan tinker

$connection = \App\Models\Connection::create([
    'organization_id' => 1, // Tu organización ID
    'name' => 'PlusMóvil QA',
    'application' => 'plusmovil',
    'settings' => [
        'client_id' => '7t3s7lb4tfg6ssal586l929ovl',
        'username' => 'tu-usuario@email.com',
        'password' => encrypt('tu-password'),
        'environment' => 'qa',
        'base_url' => 'https://xka96gucj8.execute-api.us-east-1.amazonaws.com/qa',
    ]
]);
```

#### 2. **Ejecutar Comando de Testing**

```bash
# Test básico (últimos 7 días)
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection {connection_id}

# Test con rango de fechas personalizado
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection {connection_id} \
  --start-date=2025-10-01 \
  --end-date=2025-11-09
```

El comando mostrará:
- ✅ Info de la conexión
- ✅ Validación de token
- ✅ Lista de facturas obtenidas
- ✅ Items de factura individual (opcional)

#### 3. **Verificaciones Esperadas**

**Primera Ejecución:**
- ✅ Token se genera automáticamente
- ✅ Token se guarda en `settings.access_token`
- ✅ `settings.token_expires_at` se establece
- ✅ Facturas se obtienen exitosamente

**Segunda Ejecución (Token válido):**
- ✅ Token se reutiliza desde settings
- ✅ No se hace llamada a Cognito
- ✅ Facturas se obtienen inmediatamente

**Ejecución con Token Expirado:**
- ✅ Token se refresca automáticamente
- ✅ Nuevo token se guarda en settings
- ✅ Facturas se obtienen después del refresh

#### 4. **Verificar en Base de Datos**

```sql
-- Ver settings de la conexión
SELECT 
    id,
    name,
    application,
    JSON_EXTRACT(settings, '$.environment') as ambiente,
    JSON_EXTRACT(settings, '$.username') as usuario,
    JSON_EXTRACT(settings, '$.access_token') IS NOT NULL as tiene_token,
    JSON_EXTRACT(settings, '$.token_expires_at') as expira_en,
    updated_at
FROM connections
WHERE application = 'plusmovil';
```

---

## 🚀 Siguientes Pasos Opcionales

### 1. **Crear Job de Importación Automática**

**Archivo:** `app/Jobs/PlusMóvil/ImportInvoicesJob.php`

```php
<?php

namespace App\Jobs\PlusMóvil;

use App\Models\Connection;
use App\Services\PlusMovilInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $connection;
    protected $startDate;
    protected $endDate;

    public function __construct(Connection $connection, $startDate, $endDate)
    {
        $this->connection = $connection;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle()
    {
        Log::info('Iniciando importación PlusMóvil', [
            'connection_id' => $this->connection->id,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        $service = new PlusMovilInvoiceService();
        $service->setConnection($this->connection);

        // El token se obtiene automáticamente
        $result = $service->getProformas([
            'invoice_date_between' => "{$this->startDate},{$this->endDate}"
        ]);

        $invoices = $result['data'] ?? [];

        Log::info('Facturas obtenidas', ['count' => count($invoices)]);

        foreach ($invoices as $invoice) {
            try {
                // Obtener factura completa con items
                $fullInvoice = $service->getInvoiceWithItems($invoice['id']);

                // Importar header + items a SalesHeaderImp/SalesDetailImp
                $this->importInvoice($fullInvoice);

            } catch (\Exception $e) {
                Log::error('Error al importar factura', [
                    'invoice_id' => $invoice['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Importación completada', [
            'total_invoices' => count($invoices),
        ]);
    }

    protected function importInvoice($invoice)
    {
        // Implementar lógica de importación
        // Similar a otros Jobs de importación (Word, Zoho, etc)
    }
}
```

### 2. **Crear Comando para Disparar Importación**

```php
php artisan make:command PlusMóvil/ImportInvoicesCommand
```

### 3. **Agregar a Scheduler (Opcional)**

En `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Importar facturas PlusMóvil diariamente
    $schedule->call(function () {
        $connections = Connection::where('application', 'plusmovil')->get();
        
        foreach ($connections as $connection) {
            ImportInvoicesJob::dispatch(
                $connection,
                now()->subDay()->format('Y-m-d'),
                now()->format('Y-m-d')
            );
        }
    })->daily();
}
```

---

## 📋 Checklist Final

- [x] Modelo Connection con métodos Cognito
- [x] Imports y dependencias (Log, Cognito)
- [x] AWS SDK instalado (`aws/aws-sdk-php`)
- [x] Componente Create actualizado
- [x] Vista de formulario creada
- [x] PlusMovilInvoiceService actualizado
- [x] Comando de testing creado
- [ ] **Testing en QA** ⬅️ SIGUIENTE PASO
- [ ] Testing en Producción
- [ ] Crear Job de importación (opcional)

---

## 🧪 Comando de Testing

```bash
# Ver ayuda del comando
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection --help

# Ejecutar test
docker exec -it docucenter_laravel.test php artisan plusmovil:test-connection {connection_id}
```

---

## 📚 Referencias

- [plusmovil-implementation-analysis.md](./plusmovil-implementation-analysis.md)
- [plusmovil-token-storage-strategy.md](./plusmovil-token-storage-strategy.md)
- [plusmovil-cognito-upgrade-analysis.md](./plusmovil-cognito-upgrade-analysis.md)
