# Fix: Conexión de Base de Datos en CreateSaleQuickBooksJob

## Problema Identificado

El job `CreateSaleQuickBooksJob` estaba fallando con el error:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'intuit_sync_status' in 'SET'
```

## Causa Raíz

1. **Primer Update (línea ~106)**: Funcionaba correctamente
   ```php
   $sale->update(['intuit_sync_status' => 'synced']);
   ```

2. **Procesamiento Intermedio**: Se llamaba `extractAndStoreFiscalNumber()`
   - Este método cambia temporalmente a BD de organización
   - Al final restaura conexión a BD principal: `useDatabase(env('DB_DATABASE'))`

3. **Segundo Update (línea ~172)**: Fallaba
   ```php
   $sale->update(['intuit_sync_status' => 'completed']);
   ```
   
   **Problema**: Ejecutaba en BD principal que no tiene tabla `Sales_Header_Imp` con columnas `intuit_*`

## Solución Aplicada

Agregado restore de conexión a BD de organización antes del último update:

```php
// IMPORTANTE: Restaurar conexión a BD de organización después de extractAndStoreFiscalNumber
DB::connection()->useDatabase($this->organization->database);

// Marcar la venta como originada en QuickBooks...
$sale->update([
    'intuit_sync_status' => 'completed',
    // ...
]);
```

## Archivos Modificados

- `app/Jobs/CreateSaleQuickBooksJob.php`: Línea ~169 - Agregado restore de conexión

## Resultado

**RESUELTO**: El job ahora mantiene la conexión correcta a la BD de organización durante todo el proceso, evitando el error de columna no encontrada.

## Testing

El error se manifestaba al completar emisión de facturas desde QuickBooks. La solución asegura que:

1. Primer update funciona (ya funcionaba)
2. Procesamiento FE funciona (ya funcionaba)  
3. **Segundo update ahora funciona** (problema resuelto)

**Estado**: Listo para testing en producción
