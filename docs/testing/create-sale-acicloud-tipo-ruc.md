# Control de dTipoRuc en ACI Cloud - Tests y configuracion

## Resumen

El servicio `ACIcloudService::storeOrder` cambia automaticamente el campo
`dTipoRuc` del receptor segun el patron del RUC detectado. Esto puede
ser no deseado para ciertos clientes que necesitan respetar el dato
declarado.

Para hacerlo opt-in, se introdujo la columna:

`organizations.respect_declared_d_tipo_ruc` (boolean, default `false`)

- `false` (default historico): el sistema sobrescribe `dTipoRuc` si el
  patron del RUC no coincide con el valor declarado.
- `true`: el sistema respeta el valor declarado por el cliente y solo
  registra en log la inconsistencia para auditoria.

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `database/migrations/2026_06_29_120000_add_respect_declared_d_tipo_ruc_to_organizations_table.php` | Migracion nueva |
| `database/factories/OrganizationFactory.php` | Default `respect_declared_d_tipo_ruc = false` |
| `app/Models/Organization.php` | Campo en `$fillable` y `$casts` |
| `app/Services/ACIcloudService.php` | Bloque de correccion condicionado al flag |
| `tests/Unit/Helpers/PanamaRucHelperDetectContributorTypeTest.php` | Test unitario del helper |
| `tests/Unit/Helpers/PanamaRucHelperRespectDeclaredTipoRucTest.php` | Test del comportamiento con flag |

## Como activar para una organizacion

```sql
UPDATE organizations
SET respect_declared_d_tipo_ruc = 1
WHERE id = <organization_id>;
```

O bien desde codigo:

```php
$organization->respect_declared_d_tipo_ruc = true;
$organization->save();
```

## Como ejecutar los tests

```bash
# Test unitario del helper (rapido, sin BD)
./vendor/bin/sail exec laravel.test php artisan test \
    tests/Unit/Helpers/PanamaRucHelperDetectContributorTypeTest.php

./vendor/bin/sail exec laravel.test php artisan test \
    tests/Unit/Helpers/PanamaRucHelperRespectDeclaredTipoRucTest.php
```

## Resultado actual

```
PASS  Tests\Unit\Helpers\PanamaRucHelperDetectContributorTypeTest
  ✓ detecta persona natural con ruc provincia libro tomo
  ✓ detecta juridico con ruc formato empresa
  ✓ ruc vacio o placeholder es natural

PASS  Tests\Unit\Helpers\PanamaRucHelperRespectDeclaredTipoRucTest
  ✓ correccion automatica aplicaria cambio cuando flag inactivo
  ✓ bandera respect declared manti valor declarado intacto
  ✓ valor juridico no cambia con ningun flag

Tests:  6 passed
```

## Comportamiento esperado segun el flag

Para el payload de prueba con:
- RUC receptor = `2-221-78`
- `dTipoRuc` declarado = `"2"` (juridico)
- Patron detectado = `"1"` (natural)

| `respect_declared_d_tipo_ruc` | Valor persistido en `customers_imp.Custom_field4` | Log |
|-------------------------------|----------------------------------------------------|-----|
| `false` (default)             | `"1"` (corregido)                                  | `dTipoRuc corregido automaticamente por patron de RUC` |
| `true`                        | `"2"` (respeta declarado)                          | `dTipoRuc del receptor difiere del patron (respetado por flag)` |

## Limitaciones conocidas

El test Feature completo
(`tests/Feature/CreateSaleAciCloudWithEmissionTipoRucTest.php`) depende de
las migraciones del proyecto, las cuales usan operaciones que SQLite no
soporta nativamente, por lo que ese test queda pendiente de ejecutarse
contra MariaDB.

## Alcance de la modificacion

Solo se modifico `ACIcloudService::storeOrder`. El resto de servicios que
usan `PanamaRucHelper::detectContributorType` directamente
(`LightspeedService`, `MaxgymService`, `MeyparService`, `AlanubeService`,
`QuickBooksOnlineService`) mantiene el comportamiento auto-correccion
historico. Si se requiere extender la flag a esos servicios, es necesario
replicar el patron: leer `organization->respect_declared_d_tipo_ruc` y
actuar en consecuencia.
