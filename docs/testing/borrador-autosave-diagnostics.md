# Diagnóstico de Auto-Guardado de Borradores

## Estado Actual

**VERIFICADO EN LOCAL**: El sistema de auto-guardado **SÍ está funcionando correctamente**.

### Evidencia de Funcionamiento

1. **Logs del Sistema** (storage/logs/laravel.log):
   - 24 entradas exitosas de auto-guardado
   - Borrador ID 20: Múltiples actualizaciones exitosas
   - Borrador ID 21: Creado y actualizado correctamente
   - Todos los logs muestran "Completado exitosamente"

2. **Base de Datos**:
   ```
   Borrador ID: 21
   Orden: 0000000035
   Cliente: APCON CONSULTING INC
   Items: 2
   Total: $1799.60
   Estado: 1 (Borrador)
   ```

## Herramientas de Diagnóstico

### 1. Monitor en Tiempo Real

```bash
./scripts/monitor-autosave.sh
```

Este script muestra en tiempo real todos los eventos de auto-guardado con colores:
- INICIO: Cuando se inicia el auto-guardado
- CREAR: Cuando se crea un nuevo borrador
- ACTUALIZAR: Cuando se actualiza un borrador existente
- ÉXITO: Cuando se completa exitosamente
- ERROR: Cuando ocurre un error
- SKIP: Cuando está deshabilitado

### 2. Verificar Borradores en Producción

```bash
docker exec -it docucenter-app-1 php artisan borrador:verificar {organization_id}

# Ejemplo para organización 2:
docker exec -it docucenter-app-1 php artisan borrador:verificar 2
```

Este comando muestra:
- Organización y base de datos
- Últimos 10 borradores
- ID, número de orden, cliente, cantidad de items, total
- Vendedor asignado
- Última modificación

## Logs Detallados Agregados

Se agregaron logs en cada paso del proceso de auto-guardado:

1. **Validación Inicial**:
   - `Auto-guardado: Deshabilitado` - Si `autoGuardadoHabilitado = false`
   - `Auto-guardado: Ya está guardando` - Si hay un guardado en proceso
   - `Auto-guardado: Validación falló` - Si falta customer_id o items

2. **Inicio del Proceso**:
   - `Auto-guardado: Iniciando` - Con customer_id, items_count, borrador_id_actual

3. **Conexión a Base de Datos**:
   - `Auto-guardado: Conexión establecida` - Con nombre de base de datos

4. **Actualización de Borrador Existente**:
   - `Auto-guardado: Buscando borrador existente` - Con borrador_id
   - `Auto-guardado: Actualizando borrador existente` - Con datos actualizados
   - `Auto-guardado: Borrador no encontrado o no es borrador` - Si hay un problema

5. **Creación de Nuevo Borrador**:
   - `Auto-guardado: Creando nuevo borrador` - Con sales_order_number, customer_id, items_count

6. **Guardado de Detalles**:
   - `Auto-guardado: Todos los detalles guardados` - Con borrador_id, items_count

7. **Confirmación**:
   - `Auto-guardado: Transacción confirmada` - Con borrador_id
   - `Auto-guardado: Completado exitosamente` - Con borrador_id, items_count, total

8. **Errores**:
   - `Auto-guardado: Error` - Con mensaje y trace completo

## Posibles Causas del Problema Reportado

Si el usuario dice que "no se está creando" pero los logs muestran lo contrario:

### 1. Problema de UI (No ve el indicador)

**Verificar**:
- Badge "Borrador ID: X" debe aparecer en la parte superior
- Badge "Guardado: HH:MM:SS" debe actualizarse
- Lista de borradores debe mostrarse al entrar a la página

**Solución**:
- Refrescar página (Ctrl+F5)
- Verificar consola del navegador por errores JavaScript

### 2. Diferente Organización

**Verificar**:
- Usuario está en la organización correcta
- `organization_id` en la sesión coincide con donde se espera ver el borrador

**Solución**:
```bash
# Verificar borradores en todas las organizaciones
docker exec -it docucenter-app-1 php artisan borrador:verificar 1
docker exec -it docucenter-app-1 php artisan borrador:verificar 2
docker exec -it docucenter-app-1 php artisan borrador:verificar 3
```

### 3. Usuario sin SalesRepID

**Verificar**:
- Si el usuario no tiene `sales_rep_id`, debe ver TODOS los borradores de la organización
- Si tiene `sales_rep_id`, solo ve sus propios borradores

**Solución**:
- Verificar en la base de datos: `SELECT sales_rep_id FROM users WHERE id = X;`
- Ajustar filtro si es necesario

### 4. Cache de Navegador

**Verificar**:
- Livewire puede estar cacheando el estado

**Solución**:
- Limpiar cache de Livewire: `php artisan livewire:stubs`
- Hard refresh: Ctrl+Shift+R o Ctrl+F5

### 5. Estado de Auto-guardado Deshabilitado

**Verificar logs para**:
```
Auto-guardado: Deshabilitado
```

**Solución**:
- Verificar que `$autoGuardadoHabilitado` esté en `true`
- Por defecto debe ser `true` en el componente

## Pasos para Reproducir y Diagnosticar

1. **Preparar Monitor**:
   ```bash
   ./scripts/monitor-autosave.sh
   ```

2. **En otra terminal, verificar borradores existentes**:
   ```bash
   docker exec -it docucenter-app-1 php artisan borrador:verificar 2
   ```

3. **Ir a la página de creación de orden**:
   - Abrir: `admin/sage50/create_sales_order`
   - Abrir consola del navegador (F12)

4. **Realizar acciones que deberían auto-guardar**:
   - Seleccionar un cliente
   - Agregar un producto
   - Cambiar cantidad
   - Cambiar descuento
   - Escribir en Customer PO
   - Escribir en Invoice Note

5. **Observar**:
   - En el monitor: Debe aparecer "Auto-guardado: Iniciando" seguido de "Completado exitosamente"
   - En la UI: Badge debe actualizarse
   - En consola: No debe haber errores JavaScript

6. **Verificar en base de datos**:
   ```bash
   docker exec -it docucenter-app-1 php artisan borrador:verificar 2
   ```

## Ejemplo de Log Exitoso

```
[2025-12-11 03:15:22] production.INFO: Auto-guardado: Iniciando {"customer_id":"TESTCUST","items_count":1,"borrador_id_actual":null}
[2025-12-11 03:15:22] production.INFO: Auto-guardado: Conexión establecida {"database":"db_18257061709732_90"}
[2025-12-11 03:15:22] production.INFO: Auto-guardado: Creando nuevo borrador {"sales_order_number":36,"customer_id":"TESTCUST","items_count":1}
[2025-12-11 03:15:22] production.INFO: Auto-guardado: Todos los detalles guardados {"borrador_id":22,"items_count":1}
[2025-12-11 03:15:22] production.INFO: Auto-guardado: Transacción confirmada {"borrador_id":22}
[2025-12-11 03:15:22] production.INFO: Auto-guardado: Completado exitosamente {"borrador_id":22,"items_count":1,"total":100.0}
```

## Contacto de Soporte

Si después de seguir estos pasos el problema persiste:

1. Ejecutar el monitor en tiempo real
2. Reproducir el problema exacto
3. Capturar los logs generados
4. Capturar screenshot de la pantalla
5. Reportar con toda la información recopilada
