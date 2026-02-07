# Test de Funcionalidad: Filtros de Organización y Usuario en Configuración FE

## Fecha de Implementación
20 de octubre de 2025

## Resumen de Cambios

Se implementaron dos filtros en cascada en la pantalla de configuración de facturación electrónica:

1. **Filtro de Organización**: Permite seleccionar cualquier organización
2. **Filtro de Usuario**: Muestra usuarios de la organización seleccionada
3. **Configuración**: Permite editar billing_point, device_id y branch_code del usuario seleccionado

### Archivos Modificados

1. **Configuration.php** - Componente Livewire con lógica de filtros
2. **setting.blade.php** - Vista con selects en cascada

### Nueva Funcionalidad

#### Filtros en Cascada
- Al seleccionar organización → se cargan usuarios de esa organización
- Al seleccionar usuario → se cargan sus datos actuales (billing_point, device_id, branch_code)
- Los campos están deshabilitados hasta seleccionar usuario

#### Campos Disponibles
- **Billing Point**: Punto de facturación (1-3 dígitos)
- **Device ID**: ID del dispositivo (máx 20 caracteres)  
- **Branch Code**: Código de sucursal (máx 10 caracteres) - ¡NUEVO!

## Casos de Prueba

### Prueba 1: Carga inicial
```bash
# Verificar que carga organizaciones
# Verificar que pre-selecciona organización actual del usuario
# Verificar que carga usuarios de la organización actual
# Verificar que pre-selecciona usuario actual
```

### Prueba 2: Cambio de organización
```bash
# Seleccionar otra organización
# Verificar que se cargan usuarios de la nueva organización
# Verificar que se resetean campos de configuración
# Verificar que botón Update está deshabilitado
```

### Prueba 3: Selección de usuario
```bash
# Seleccionar usuario de la organización
# Verificar que se cargan sus datos actuales
# Verificar que campos se habilitan
# Verificar que botón Update se habilita
```

### Prueba 4: Actualización de configuración
```bash
# Modificar billing_point, device_id, branch_code
# Hacer clic en Update
# Verificar mensaje de éxito
# Verificar que datos se guardaron en BD
```

## Validaciones Implementadas

### Frontend
- Select de organización requerido
- Select de usuario requerido  
- Campos deshabilitados sin usuario seleccionado
- Botón Update deshabilitado sin usuario seleccionado

### Backend
- `selected_organization_id`: required|exists:organizations,id
- `selected_user_id`: required|exists:users,id
- `billing_point`: nullable|integer|digits_between:1,3
- `device_id`: nullable|max:20
- `branch_code`: nullable|string|max:10

## Scripts de Validación

### Verificar estructura de BD
```bash
cd /home/weirdolabs/code/docucenter
docker exec -it docucenter_laravel.test php artisan tinker
```

```php
// En tinker:
// Verificar que branch_code existe en users
Schema::hasColumn('users', 'branch_code'); // Debe retornar true

// Verificar organizaciones disponibles
\App\Models\Organization::count(); // Debe retornar > 0

// Verificar usuarios con organizaciones
\App\Models\User::whereNotNull('organization_id')->count(); // Debe retornar > 0
```

### Testing Manual de UI

#### 1. Acceder a la pantalla
- Ir a `/admin/einvoice/configuration` (o la ruta correspondiente)
- Verificar que aparecen los dos selects

#### 2. Flujo completo
1. **Verificar carga inicial**:
   - Select organización debe mostrar organizaciones
   - Select usuario debe mostrar usuarios de org actual
   - Campos deben tener valores del usuario actual

2. **Cambiar organización**:
   - Seleccionar otra organización  
   - Verificar que usuarios cambian
   - Verificar que campos se limpian

3. **Seleccionar usuario**:
   - Elegir usuario de la lista
   - Verificar que campos se llenan con sus datos
   - Verificar que botón Update se habilita

4. **Actualizar configuración**:
   - Cambiar valores en los campos
   - Hacer clic en Update
   - Verificar mensaje de éxito

## Comportamientos Esperados

### Estado Inicial
✅ **ÉXITO**: Organización actual pre-seleccionada
✅ **ÉXITO**: Usuarios de organización actual cargados
✅ **ÉXITO**: Usuario actual pre-seleccionado
✅ **ÉXITO**: Campos con valores actuales del usuario

### Cambio de Organización  
✅ **ÉXITO**: Usuarios se actualizan dinámicamente
✅ **ÉXITO**: Campos se resetean
✅ **ÉXITO**: Usuario seleccionado se limpia
✅ **ÉXITO**: Botón Update se deshabilita

### Selección de Usuario
✅ **ÉXITO**: Campos se llenan automáticamente  
✅ **ÉXITO**: branch_code se carga desde BD
✅ **ÉXITO**: Formulario se habilita completamente

### Actualización
✅ **ÉXITO**: Validaciones frontend y backend
✅ **ÉXITO**: branch_code se guarda correctamente
✅ **ÉXITO**: Mensaje de confirmación
✅ **ÉXITO**: Datos persisten en BD

## Integración con Sistema Existente

### Compatibilidad
- ✅ Mantiene funcionalidad existente de billing_point y device_id
- ✅ Agrega soporte para branch_code recién implementado
- ✅ No rompe configuraciones existentes
- ✅ Workflow intuitivo para administradores

### Seguridad
- ✅ Validación de existencia de organización y usuario
- ✅ No permite modificar usuarios de otras organizaciones sin seleccionar
- ✅ Validaciones de tipos y longitudes de datos

## Notas Técnicas

- Usa Livewire para interactividad sin página completa
- `wire:change` para actualizar usuarios automáticamente  
- Método `updatedSelectedUserId()` para cargar datos del usuario
- Validaciones both client-side (disabled fields) y server-side
- branch_code incluido como parte de la configuración del usuario

## Seguimiento

- [ ] Probar en navegadores diferentes
- [ ] Verificar responsive design
- [ ] Validar con múltiples organizaciones
- [ ] Confirmar que branch_code se usa en facturación
- [ ] Documentar en manual de usuario
