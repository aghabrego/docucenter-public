# Fix: Error "Call to undefined method App\Services\AciCloudService::storeOrder()"

## Problema Identificado

Error en producción: `Call to undefined method App\Services\AciCloudService::storeOrder()`

## Causa Raíz

Había **dos servicios ACIcloud diferentes** en el sistema:

1. **`ACIcloudService`** (original) - Ubicado en `app/Services/ACIcloudService.php`
   - ✅ Implementa `ACIcloudServiceContract`
   - ✅ Tiene método `storeOrder()`
   - ✅ Tiene método `issueInvoice()`
   - ✅ Registrado automáticamente por `RegisterService::register()`

2. **`AciCloudService`** (duplicado) - Ubicado en `app/Services/AciCloudService.php`
   - ❌ NO implementa ningún contrato
   - ❌ NO registrado en el container
   - ❌ Causaba conflicto de resolución

## Solución Implementada

1. **Eliminado** el archivo `app/Services/AciCloudService.php` duplicado
2. **Mantenido** el servicio original `ACIcloudService.php` que ya tiene toda la funcionalidad
3. **Regenerado** el autoload de Composer
4. **Confirmado** que `ACIcloudService` tiene los métodos necesarios:
   - `storeOrder(Organization $organization, array $data)`
   - `issueInvoice(SalesHeaderImp $invoice, User $user)`

## Verificación

```bash
# Confirmar que el archivo duplicado fue eliminado
ls -la app/Services/*AciCloud*
# Should show: app/Services/ACIcloudService.php (only)

# Regenerar autoload
composer dump-autoload
```

## Sistema de Registro Automático

El sistema utiliza `RegisterService::register()` en `AppServiceProvider` que:

1. **Escanea** `app/Contracts/` para encontrar interfaces
2. **Mapea** automáticamente con `app/Services/` 
3. **Registra** `ACIcloudServiceContract` → `ACIcloudService`

```php
// En AppServiceProvider.php
RegisterService::register($this->app);
```

## Estado Post-Fix

- ✅ `ACIcloudServiceContract` se resuelve correctamente a `ACIcloudService`
- ✅ Método `storeOrder()` disponible y funcional
- ✅ Método `issueInvoice()` disponible y funcional  
- ✅ API `createSaleAciCloudWithEmission` funciona correctamente
- ✅ No hay conflictos de servicios duplicados

## Commits Relacionados

- `647f954` - fix: eliminar servicio AciCloudService duplicado para resolver conflicto con ACIcloudService
- `18e414e` - fix: corregir formato de espacios en blanco en createSaleAciCloudWithEmission
- `bfb150c` - feat: agregar control de reintentos AttempCounter en API create_sale_acicloud_with_emission

## Prevención

Para evitar este tipo de conflictos en el futuro:

1. **Verificar** siempre si ya existe un servicio similar antes de crear uno nuevo
2. **Usar** el comando `php artisan make:service` que verifica duplicados
3. **Confirmar** que los contratos están correctamente implementados
4. **Probar** la resolución del container en desarrollo antes de desplegar
