# Test de Implementación del Campo branch_code

## Fecha de Implementación
20 de octubre de 2025

## Resumen de Cambios

Se implementó el uso del campo `branch_code` del usuario en los componentes de facturación electrónica para reemplazar el código de sucursal hardcodeado.

### Archivos Modificados

1. **CreateFastJob.php** - Línea 232
2. **CreateFast.php** - Línea 99  
3. **Create.php** - Línea 435

### Lógica Implementada

```php
// ANTES (hardcodeado):
$this->emisor_codigoSucursal = $this->strPad(0, 4);

// DESPUÉS (usando branch_code del usuario):
$this->emisor_codigoSucursal = $this->strPad($this->user?->branch_code ?: 0, 4);
```

### Formateo Garantizado

Todos los códigos de sucursal ahora se formatean consistentemente a 4 dígitos:
- `0` → `0000`
- `1` → `0001`
- `25` → `0025`
- `123` → `0123`

### Precedencia de Valores

1. **Primera prioridad**: `branch_code` del usuario
2. **Fallback**: `0` (formateado como `0000`)
3. **Override**: Configuración de Lightspeed (si existe, también formateada a 4 dígitos)

## Casos de Prueba

### Prueba 1: Usuario sin branch_code
```bash
# Usuario con branch_code = null
# Resultado esperado: emisor_codigoSucursal = "0000"
```

### Prueba 2: Usuario con branch_code
```bash
# Usuario con branch_code = "5"
# Resultado esperado: emisor_codigoSucursal = "0005"
```

### Prueba 3: Override de Lightspeed
```bash
# Usuario con branch_code = "3"
# Lightspeed codigo_secursal = "12"
# Resultado esperado: emisor_codigoSucursal = "0012" (Lightspeed gana)
```

## Scripts de Validación

### Verificar cambios en código
```bash
cd /home/weirdolabs/code/docucenter
grep -n "branch_code" app/Http/Livewire/Admin/Einvoice/Create*.php
```

### Verificar formato strPad
```bash
cd /home/weirdolabs/code/docucenter
grep -n "strPad.*4" app/Http/Livewire/Admin/Einvoice/Create*.php
```

## Validación en Base de Datos

### Verificar que el campo existe
```bash
cd /home/weirdolabs/code/docucenter
docker exec -it docucenter_laravel.test php artisan tinker
```

```php
// En tinker:
\App\Models\User::first()->branch_code; // Debe ser accesible
Schema::hasColumn('users', 'branch_code'); // Debe retornar true
```

## Testing Manual

### 1. Crear factura sin branch_code configurado
- Ir a CreateFast
- Verificar que emisor_codigoSucursal = "0000"

### 2. Configurar branch_code y crear factura
```sql
UPDATE users SET branch_code = '25' WHERE id = 1;
```
- Crear nueva factura
- Verificar que emisor_codigoSucursal = "0025"

### 3. Verificar integración Lightspeed
- Configurar sucursal en Lightspeed
- Verificar que override funciona y mantiene formato

## Resultados Esperados

✅ **ÉXITO**: Todos los códigos de sucursal formatados a 4 dígitos
✅ **ÉXITO**: branch_code del usuario usado como base
✅ **ÉXITO**: Fallback a "0000" cuando branch_code está vacío
✅ **ÉXITO**: Override de Lightspeed funciona y mantiene formato
✅ **ÉXITO**: Compatibilidad con billing_point existente

## Notas Técnicas

- El operador `?:` asegura fallback cuando `branch_code` es null
- `strPad($value, 4)` garantiza formato consistente de 4 dígitos
- Lightspeed configuration mantiene precedencia pero ahora con formato correcto
- No se rompe funcionalidad existente

## Seguimiento

- [ ] Ejecutar migración en desarrollo
- [ ] Probar en facturas reales
- [ ] Validar con PAC (TheFactoryHKA/Alanube)
- [ ] Documentar cambio en manual de usuario
