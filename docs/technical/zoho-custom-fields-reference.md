# Zoho Books - Resolucion automatica por Organizacion

Como cada organizacion conectada a DocuCenter puede tener su propia
cuenta de Zoho Books con sus propios IDs de impuestos, custom fields
y monedas, la resolucion de esos IDs se hace **automaticamente en
runtime** consultando a la API de Zoho de la connection.

Ya no se mantiene un archivo de configuracion global.

## Como funciona

### Cache por connection

Las llamadas a `/settings/preferences`, `/settings/taxes` y
`/settings/currencies` se cachean en `connections.settings` con TTL
de 7 dias. Pasado ese plazo, se renueva la cache automaticamente.

Estructura del cache:

```
connections.settings = [
    'zoho_organization_id' => '...',
    'zoho_environment' => 'com',
    'zoho_company_id' => '...',
    'zoho_taxes_cache' => [
        'fetched_at' => '2026-07-20T...',
        'taxes' => [
            ['tax_id' => '...', 'tax_name' => 'ITBMS 7%', 'tax_percentage' => 7, ...],
            ...
        ],
    ],
    'zoho_customfields_cache' => [
        'fetched_at' => '2026-07-20T...',
        'customfields' => [
            'bill' => [...],
            'invoice' => [...],
            'vendor' => [...],
            ...
        ],
    ],
    'zoho_currencies_cache' => [
        'fetched_at' => '2026-07-20T...',
        'currencies' => [
            ['currency_id' => '...', 'currency_code' => 'USD', ...],
            ...
        ],
    ],
]
```

### Metodos del Service

`ZohoSelfClientService` expone los siguientes metodos publicos para
resolver IDs en runtime:

| Metodo | Endpoint Zoho | TTL Cache | Devuelve |
|---|---|---|---|
| `getTaxes(bool $forceRefresh = false): array` | `GET /settings/taxes` | 7 dias | Lista de impuestos |
| `getCustomFields(bool $forceRefresh = false): array` | `GET /settings/preferences` | 7 dias | Custom fields por modulo |
| `getCurrencies(bool $forceRefresh = false): array` | `GET /settings/currencies` | 7 dias | Monedas disponibles |
| `getConnection(): Connection` | (local) | - | La Connection asociada |

### Consumidores

| Componente | Que resuelve | Como |
|---|---|---|
| `ZohoFeBillTransformer::resolveItbmsTaxId($tasa)` | `tax_id` para el `line_item.tax_id` | Busca en cache de taxes por `tax_percentage` |
| `ZohoFeBillTransformer::resolveCurrencyId()` | `currency_id` para `bill.currency_id` | Busca en cache de currencies por `currency_code = USD` |
| `ZohoFeBillTransformer::buildCustomFields($feHeader)` | `customfield_id` para CUFE, CUFE Ref, tipo_doc | Busca en cache de custom fields por `api_name` en modulo `bill` |

### api_name de custom fields

Los custom fields se buscan en el modulo Zoho por `api_name` (no por
label ni por ID). El codigo busca estos `api_name`:

| api_name | Campo FE | Cuando se incluye |
|---|---|---|
| `cf_cufe` | `$feHeader->cufe` | Cuando `cufe` no esta vacio |
| `cf_cufe_ref` | `$feHeader->gDFRef_dCUFERef` | Cuando es factura fiscal recibida |
| `cf_tipo_doc` | `$feHeader->iDoc` | Cuando `iDoc` no esta vacio |

Para agregar soporte a una org Zoho nueva, basta con crear los custom
fields en Zoho con esos mismos `api_name` (recomendado). Si la org ya
tiene custom fields con api_names diferentes, hay que ajustar la
constante en `ZohoFeBillTransformer::buildCustomFields`.

## Fallback automatico para error 120100

`ZohoSelfClientService::createBillWithCustomFieldFallback()` recibe
la lista de `api_name` opcionales (por defecto solo `cf_cufe`):

1. Primer intento: envia el payload con todos los custom fields.
2. Si Zoho responde con `code = 120100`, el mensaje suele incluir el
   `api_name` del campo rechazado. El metodo extrae los api_names
   opcionales mencionados en el mensaje y reintenta sin ellos.
3. Si no se puede extraer ninguno del mensaje, omite todos los
   opcionales y reintenta.
4. Si el segundo intento tambien falla, registra el error y retorna null.

Esto permite que `cf_cufe` sea opcional: si una org Zoho no lo tiene
creado, no se envia y la bill se crea sin ese campo.

Para agregar mas custom fields opcionales al fallback, pasar el array
de `api_name` al constructor o al metodo directamente:

```php
$svc->createBillWithCustomFieldFallback($billData, ['cf_cufe', 'cf_otro_opcional']);
```

## Forzar renovacion de cache

Si se agregan custom fields, taxes o currencies en la org Zoho, llamar
con `$forceRefresh = true` para invalidar la cache y volver a
consultar a Zoho:

```php
$svc->getCustomFields(true);
$svc->getTaxes(true);
$svc->getCurrencies(true);
```

O borrar la entrada correspondiente en `connections.settings` y dejar
que la siguiente llamada la reconstruya.

## Como descubrir los IDs de una org Zoho

Desde tinker:

```php
$svc = new \App\Services\ZohoSelfClientService(\App\Models\Connection::find(<ID>));
$taxes = $svc->getTaxes();
$customFields = $svc->getCustomFields();
$currencies = $svc->getCurrencies();
```

O consultar via HTTP directo:

```
GET https://www.zohoapis.com/books/v3/settings/taxes?organization_id={ORG_ID}
GET https://www.zohoapis.com/books/v3/settings/preferences?organization_id={ORG_ID}
GET https://www.zohoapis.com/books/v3/settings/currencies?organization_id={ORG_ID}
```

Con header:
```
Authorization: Zoho-oauthtoken {ACCESS_TOKEN}
```

## Organizaciones registradas

### Connection 53 - osanic bliss restaurante (org Zoho: pendiente configurar)

Descubierto el 2026-07-20 con cache automatica.

**Taxes:**

| tax_id | tax_name | tax_percentage |
|---|---|---|
| 88754000000107138 | ITBMS 7% | 7 |
| 88754000000107142 | ITBMS 10% | 10 |

**Currencies:** USD disponible.

**Custom fields (modulo bill):**

| customfield_id | api_name | label | data_type |
|---|---|---|---|
| 88754000000206001 | `cf_cufe` | CUFE | string |

**Custom fields de modulos relacionados** (referencia):

| Modulo | api_name | customfield_id | label | data_type |
|---|---|---|---|---|
| item | `cf_categoria` | 88754000000254004 | Categoria | string |
| vendor | `cf_ruc_o_c_dula` | 88754000000107483 | RUC o Cedula | string |
| vendor | `cf_dv` | 88754000000107487 | DV | string |
| vendor | `cf_tipo_de_contribuyente` | 88754000000107496 | Tipo de Contribuyente | dropdown |
| vendor | `cf_concepto` | 88754000000107519 | Concepto | string |
| contact | `cf_ruc_o_c_dula` | 88754000000107482 | RUC o Cedula | string |
| contact | `cf_dv` | 88754000000107486 | DV | string |
| contact | `cf_tipo_de_contribuyente` | 88754000000107495 | Tipo de Contribuyente | dropdown |
| contact | `cf_concepto` | 88754000000107518 | Concepto | string |
| estimate | `cf_fecha_y_hora_del_evento` | 88754000000107280 | Fecha y Hora del Evento | date_time |
| estimate | `cf_cantidad_de_personas` | 88754000000107282 | Cantidad de Personas | number |
| customer_payment | `cf_cotizaci_n` | 88754000000107326 | Cotizacion | lookup |
| customer_payment | `cf_notas_al_clientes` | 88754000000107349 | Notas al clientes | multiline |

**Nota**: la respuesta de `/settings/preferences` se obtuvo sin
enviar `organization_id` (el campo `zoho_organization_id` en settings
de la connection 53 estaba vacio). El listado devolvio los custom
fields igualmente porque el token de Self Client tiene acceso a las
organizaciones asociadas. Para otras orgs, asegurarse de poblar
primero `zoho_organization_id` en `connections.settings`.

## Como agregar soporte para una org nueva

1. Asegurarse de que la connection tenga `zoho_organization_id`
   configurado en sus settings (lo hace automaticamente
   `getAndStoreZohoOrganizations` la primera vez).
2. Verificar que la org Zoho tenga los custom fields con los
   `api_name` esperados (`cf_cufe`, `cf_cufe_ref`, `cf_tipo_doc`).
   Si no los tiene, crearlos en Zoho o ajustar las constantes en
   `ZohoFeBillTransformer::buildCustomFields`.
3. Verificar que la org tenga taxes configurados para las tasas
   esperadas (7%, 10%, etc.).
4. Listo. La primera vez que se envie una FE se consultara a Zoho
   automaticamente y se resolveran los IDs correctos. La cache
   se renueva cada 7 dias.
