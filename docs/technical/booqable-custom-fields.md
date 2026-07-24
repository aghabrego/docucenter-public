# Booqable - Custom Fields Requeridos

## Contexto

La API publica de Booqable v4 **no permite crear custom fields (properties) por codigo**:
- `GET /properties` funciona (lista los existentes)
- `POST /properties` devuelve **HTTP 500 Internal Server Error** en todos
  los formatos probados (con/sin `owner_type`, con `relationships.owner`
  apuntando al `shops`/`accounts`/`users`)

Esto significa que **los custom fields deben configurarse manualmente**
desde la UI de Booqable en **Settings > Custom fields** por cada
organizacion / tenant que se conecte a DocuCenter.

Este documento lista los campos que **deben existir** en Booqable para
que la integracion bidireccional (descarga + envio) funcione
correctamente.

## Custom Fields para `customers`

Booqable v4 expone los custom fields en `attributes.properties` como un
array de objetos `[{name, value}, ...]`. El `CustomerJob` de descarga
los lee con notacion de diccionario (`properties.ruc_cedula`), por lo
que es importante que los nombres coincidan exactamente.

| Nombre en Booqable | Campo origen en `Customers_Exp` | Descripcion |
|---|---|---|
| `ruc_cedula` | `Custom_field1` | RUC o cedula del cliente (formato DGI Panama, ej. `8-123-456`) |
| `dv` | `Custom_field2` | Digito verificador del RUC (ej. `12`) |
| `phone` | `Phone_Number` | Telefono del cliente (formato libre) |
| `tipo_de_cliente` | `Custom_field3` | Tipo de cliente. Formato esperado: `1-Natural` o `2-Juridica` (el `CustomerJob` parsea el numero con regex `^(\d+)-(.+)$`) |
| `codigo_ubicacion` | `Custom_field5` | Codigo de ubicacion geografica DGI Panama (formato `provincia-distrito`, ej. `8-1` para Panama Centro) |

> **Nota**: El campo `legal_type` (`person` / `commercial`) es un
> atributo **nativo** de Booqable, NO un custom field. El envio lo
> mapea desde `Customers_Exp.Custom_field4` (`'1'` -> `person`,
> `'2'` -> `commercial`).

## Custom Fields para `product_groups`

**No se requieren custom fields para productos.** El `ProductJob` de
descarga solo lee atributos nativos de Booqable:

| Atributo nativo | Campo origen en `Products_Exp` |
|---|---|
| `sku` | `ProductID` |
| `name` | `Description` |
| `base_price_in_cents` | `Price1 * 100` |
| `taxable` | derivado del campo `TaxType` |
| `product_type` | (fijo: `rental`) |

## Pasos para configurar los Custom Fields en Booqable

1. **Login** en `https://{DomainPrefix}.booqable.com` con las
   credenciales del tenant.

2. Ir a **Settings > Custom fields**.

3. En la seccion **Customers**, crear 5 campos (todos de tipo `Text`):

   - `ruc_cedula`
   - `dv`
   - `phone`
   - `tipo_de_cliente`
   - `codigo_ubicacion`

4. **Guardar** la configuracion.

5. Verificar consultando la API:

   ```bash
   curl -H "Authorization: Bearer {token}" \
        https://{DomainPrefix}.booqable.com/api/4/properties
   ```

   Debe devolver los 5 campos con `owner_type: customers`.

## Paridad entre descarga y envio

| Direccion | Job | Lee de Booqable | Escribe a Booqable |
|---|---|---|---|
| Descarga | `CustomerJob` | `attributes.properties.ruc_cedula` (etc.) | No escribe |
| Envio | `ExportCustomersToBooqableJob` | No lee | `attributes.tag_list` con prefijo del nombre (`ruc_cedula:8-123-456`, etc.) |

> **Limitacion actual**: El `tag_list` no es leido por el `CustomerJob`
> de descarga. La paridad completa se logra cuando Booqable permite
> crear los custom fields y estos se asignan a cada cliente. Mientras
> tanto, los custom fields existen como **dos copias paralelas**:
> `properties` (leido por descarga) y `tag_list` (escrito por envio).

## Tabla origen en DocuCenter

Los 2 jobs de envio (implementados en `app/Jobs/Booqable/`) leen de
las tablas `_Exp` (fuente canonica) de la base de datos de la
organizacion:

| Job | Tabla origen |
|---|---|
| `ExportCustomersToBooqableJob` | `Customers_Exp` |
| `ExportProductsToBooqableJob` | `Products_Exp` |

El comando `word:export-booqable-module` encadena ambos jobs en una
sola ejecucion.

## Verificacion rapida

Para verificar que el tenant de Booqable tiene los custom fields
configurados, ejecutar desde el workspace:

```bash
docker exec -it docucenter_laravel.test php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;
\$conn = \App\Models\Connection::find(54);
\$headers = ['Authorization' => 'Bearer '.\$conn->settings['Token']];
\$baseUrl = 'https://'.\$conn->settings['DomainPrefix'].'.booqable.com/api/4';
\$r = Http::withHeaders(\$headers)->timeout(10)->get(\"\$baseUrl/properties?page[size]=50\");
\$data = \$r->json('data') ?? [];
echo 'Total custom fields: '.count(\$data).PHP_EOL;
foreach (\$data as \$p) {
    echo '  - '.\$p['attributes']['name'].' (owner: '.\$p['attributes']['owner_type'].')'.PHP_EOL;
}
"
```

Salida esperada para una configuracion completa:

```
Total custom fields: 5
  - ruc_cedula (owner: customers)
  - dv (owner: customers)
  - phone (owner: customers)
  - tipo_de_cliente (owner: customers)
  - codigo_ubicacion (owner: customers)
```

## Limitaciones conocidas

1. **API no permite crear properties** - deben configurarse manualmente
   en la UI de Booqable.
2. **No se pueden asignar properties al crear clientes via API** - el
   campo `data.attributes.properties` devuelve error
   `unwritable_attribute` al hacer POST /customers.
3. **El envio solo escribe en `tag_list`** - preserva la informacion
   pero no la expone como property editable en la UI de Booqable.
4. **La paridad bidireccional requiere intervencion manual** - el
   operador debe poblar los custom fields en Booqable desde su UI
   despues de que el envio cree el cliente via API.

## Referencias

- Codigo de descarga: [app/Jobs/Booqable/CustomerJob.php](../../app/Jobs/Booqable/CustomerJob.php)
- Codigo de envio: [app/Jobs/Booqable/ExportCustomersToBooqableJob.php](../../app/Jobs/Booqable/ExportCustomersToBooqableJob.php)
- Servicio de envio: [app/Services/BooqableService.php](../../app/Services/BooqableService.php)
- Comandos artisan: `word:export-booqable-customers`, `word:export-booqable-products`, `word:export-booqable-module`
- Documentacion oficial de Booqable: https://help.booqable.com/en/collections/3547640-api-and-zapier
