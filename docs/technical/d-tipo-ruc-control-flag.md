# Control de dTipoRuc por organizacion (ACI Cloud)

## Contexto

El sistema de facturacion electronica de Panama exige que el campo
`dTipoRuc` (tipo de contribuyente, valores `1` Natural o `2` Juridico)
sea consistente con el RUC declarado. Para minimizar errores
operativos, varios servicios auto-correccionan este campo cuando el
patron del RUC contradice el valor enviado por el cliente.

En ciertos casos este comportamiento no es deseable: el cliente declara
un valor que difiere del patron y aun asi necesita que ese valor se
respete.

## Solucion implementada

Se agrego la columna `respect_declared_d_tipo_ruc` (boolean) a la
tabla `organizations`.

| Valor | Comportamiento |
|-------|----------------|
| `false` (default) | Auto-correccion activa (historico) |
| `true` | Respeta el valor declarado, solo registra inconsistencia en log |

El alcance de esta modificacion es **`ACIcloudService::storeOrder`**.
Los demas servicios que aplican el mismo patron
(`LightspeedService`, `MaxgymService`, `MeyparService`,
`AlanubeService`, `QuickBooksOnlineService`) mantienen el
comportamiento por defecto.

## Archivos clave

| Archivo | Proposito |
|---------|-----------|
| `database/migrations/2026_06_29_120000_add_respect_declared_d_tipo_ruc_to_organizations_table.php` | Migracion que crea la columna |
| `app/Services/ACIcloudService.php` (lineas ~1366-1395) | Logica condicional segun el flag |
| `app/Models/Organization.php` | Atributo `respect_declared_d_tipo_ruc` declarado en `$fillable` y `$casts` |
| `database/factories/OrganizationFactory.php` | Default `false` en el factory |

## Activacion por organizacion

```php
$organization = Organization::find($id);
$organization->respect_declared_d_tipo_ruc = true;
$organization->save();
```

O directo en SQL:

```sql
UPDATE organizations SET respect_declared_d_tipo_ruc = 1 WHERE id = ?;
```

## Auditoria

Independientemente del flag, ambos caminos registran en log una entrada
con:

- `organization_id`
- `ruc` (receptor)
- `dTipoRuc_original` (declarado por el cliente)
- `dTipoRuc_corregido` (calculado por PanamaRucHelper)

Esto permite reconstruir cualquier inconsistencia sin perder trazabilidad.

## Compatibilidad hacia atras

- Toda organizacion existente tendra `respect_declared_d_tipo_ruc = false`
  tras la migracion, preservando el comportamiento previo.
- No hay cambios en la firma del endpoint ni en la estructura del payload
  de entrada/salida.
- La unica diferencia observable es en `customers_imp.Custom_field4`, que
  dejara de ser sobreescrito cuando la organizacion tenga el flag activo.

## Tests

Ver `docs/testing/create-sale-acicloud-tipo-ruc.md` para instrucciones
de ejecucion.

## Trabajo futuro (no implementado)

- Extender el flag a otros servicios (`LightspeedService`,
  `MaxgymService`, etc.) que actualmente auto-corrigen siempre.
- Considerar mover este control a `pac_connections` en lugar de
  `organizations` si la decision debe ser por PAC en lugar de por
  organizacion.
- Exponer el flag en la UI administrativa para no requerir SQL directo.
