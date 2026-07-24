# Fix: Error "--pause_at_every_step" en Process Management

## Problema

Al ejecutar el comando `word:upload-sales-general-diary-intuit` desde la pantalla de **Process Management**, se generaba el siguiente error:

```
Error: The "--pause_at_every_step" option does not exist.
```

## Causa

En `app/Http/Livewire/Admin/Processmanagement/Manage.php`, dentro del método `executeCommand()`, al preparar los parámetros del comando se inyectaba automáticamente `--pause_at_every_step = 0` para cualquier comando que tuviera `requires_date = true`:

```php
if ($commandInfo && $commandInfo['requires_date'] && !empty($this->execution_date)) {
    $params['--execution_date'] = $this->execution_date;
    $params['--pause_at_every_step'] = 0;
}
```

El problema es que no todos los comandos con `requires_date = true` declaran la opción `--pause_at_every_step` en su `$signature`. Ejemplos de comandos que NO la soportan:

- `word:upload-sales-general-diary-intuit`
- `word:purchase-orders`
- `word:credit-notes`
- `word:create-sales-order-summary`
- `word:create-credit-notes-summary`
- `word:upload-sales-intuit`

Al intentar pasar `--pause_at_every_step` a uno de estos comandos, Laravel/Symfony Console lanza la excepción:

> `The "--pause_at_every_step" option does not exist.`

## Solución

Se eliminó la inyección automática de `--pause_at_every_step` en `Manage.php`. Ahora solo se envía `--execution_date` cuando el comando lo requiere, dejando que cada comando declare en su propia signature las opciones que acepta.

```php
if ($commandInfo && $commandInfo['requires_date'] && !empty($this->execution_date)) {
    $params['--execution_date'] = $this->execution_date;
}
```

## Archivos modificados

- `app/Http/Livewire/Admin/Processmanagement/Manage.php`

## Impacto

- Comandos con `requires_date = true` que NO declaran `--pause_at_every_step` ya no fallan al ejecutarse desde Process Management.
- Comandos que sí declaran `--pause_at_every_step` (por ejemplo `word:update-invu-pos-module`, `word:update-sql-server-module`, `word:update-lightspeed-module`, `word:update-booqable-module`, `word:panama-daily-entry`, `word:extract-organization-configuration-pac`, `word:update-zoho-module`, `word:update-lightspeed-serie-r`) actualmente NO reciben ese parámetro desde esta pantalla. Si en el futuro se requiere enviarlos, deberá agregarse nuevamente la lógica pero solo para los comandos que lo soporten.
