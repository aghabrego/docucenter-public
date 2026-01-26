# Resumen de Implementación: Prefijos de Factura Lightspeed X-Series

## Objetivo

Implementar sistema de prefijos configurables por tienda en Lightspeed X-Series para evitar colisiones de números de factura entre múltiples tiendas.

## Cambios Realizados

### 1. Actualización de Menú
**Archivo:** `resources/views/partials/menu/facturacion.blade.php`
- Cambiado nombre de "Lightspeed" a "Lightspeed X-Series"

### 2. Vista de Configuración
**Archivo:** `resources/views/livewire/admin/einvoice/lightspeed_setting.blade.php`
- Agregada columna "Invoice Prefix" en tabla de tiendas
- Campo input con máximo 10 caracteres
- Placeholder sugerido: "Ej: T1-"
- Texto de ayuda explicativo

### 3. Componente Livewire
**Archivo:** `app/Http/Livewire/Admin/Einvoice/AssignBranch.php`
- Regla de validación: `'configurations.outlets.*.invoice_prefix' => 'nullable|string|max:10'`
- Método `loadOutlets()` actualizado para incluir prefijos

### 4. Servicio Lightspeed
**Archivo:** `app/Services/LightspeedService.php`
- Import agregado: `use App\Models\Lightspeedconfiguration;`
- Método `storeOrder()`: Aplicación automática de prefijos
- Nuevo método: `getInvoicePrefixForOutlet()` para obtener prefijos configurados
- Lógica para evitar duplicación de prefijos

### 5. Traducciones
**Archivos:** `lang/es_panel.json`, `lang/en_panel.json`
- "Invoice Prefix": "Prefijo de Factura"
- "Prefix to avoid duplicate invoice numbers": "Prefijo para evitar números de factura duplicados"

### 6. Documentación
**Archivo:** `docs/technical/lightspeed-invoice-prefix.md`
- Documentación técnica completa
- Ejemplos de uso
- Guías de troubleshooting
- Mejores prácticas

## Cómo Funciona

```
1. API recibe venta con invoice_number y outlet_id
2. Sistema busca configuración de prefijo para ese outlet
3. Si existe prefijo y no está en el número:
   - Agrega prefijo al número de factura
4. Continúa con procesamiento normal
```

## Ejemplo

**Sin prefijos:**
- Tienda A: Factura "1001"
- Tienda B: Factura "1001" COLISIÓN

**Con prefijos:**
- Tienda A (T1-): Factura "T1-1001" 
- Tienda B (T2-): Factura "T2-1001" 

## Instrucciones de Uso

1. Acceder a: `admin/e_invoice/configuration/lightspeed_settings`
2. Seleccionar organización
3. Para cada tienda:
   - Asignar código de sucursal
   - Definir prefijo único (ej: "T1-", "T2-")
4. Guardar configuración
5. Los nuevos pedidos automáticamente usarán los prefijos

## Archivos Modificados

- `resources/views/partials/menu/facturacion.blade.php`
- `resources/views/livewire/admin/einvoice/lightspeed_setting.blade.php`
- `app/Http/Livewire/Admin/Einvoice/AssignBranch.php`
- `app/Services/LightspeedService.php`
- `lang/es_panel.json`
- `lang/en_panel.json`

##  Archivos Creados

- `docs/technical/lightspeed-invoice-prefix.md`
- `docs/LIGHTSPEED_PREFIX_IMPLEMENTATION.md` (este archivo)

## Características

- Configuración flexible por tienda
- Validación de longitud (máx 10 caracteres)
- Evita duplicación automática de prefijos
- Logging para debugging
- Manejo seguro de errores
- Compatible con facturas existentes
- Interfaz intuitiva en español e inglés

##  Testing

Para probar la funcionalidad:

```bash
# 1. Configurar prefijos en la interfaz
# 2. Crear venta de prueba desde API:

curl -X POST https://docucenter.test/api/lightspeed/sale \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_number": "1001",
    "outlet_id": "12345",
    "organization_id": 1,
    ...
  }'

# 3. Verificar en base de datos:
docker exec -it docucenter-app-1 php artisan tinker
>>> use App\Models\SalesHeaderImp;
>>> SalesHeaderImp::where('InvoiceNumber', 'LIKE', '%1001%')->get();
```

## Troubleshooting

### Verificar configuración:
```bash
docker exec -it docucenter-app-1 php artisan tinker
>>> $config = App\Models\Lightspeedconfiguration::where('organization_id', 1)->first();
>>> $config->getConfigurationsAttribute();
```

### Ver logs:
```bash
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "prefijo"
```

##  Soporte

Para más información:
- Documentación técnica: `docs/technical/lightspeed-invoice-prefix.md`
- Archivo de servicio: `app/Services/LightspeedService.php`
- Componente UI: `app/Http/Livewire/Admin/Einvoice/AssignBranch.php`

---

**Fecha:** Enero 10, 2026  
**Estado:** COMPLETADO  
**Versión:** 1.0
