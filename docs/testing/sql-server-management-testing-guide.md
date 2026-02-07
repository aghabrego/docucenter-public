# Guía de Testing - Gestión SQL Server

## Comandos de Prueba

### 1. Testing Interactivo Completo
```bash
docker exec -it docucenter_laravel.test php artisan test:sql-server-management
```
Este comando abre un menú interactivo con todas las opciones disponibles.

### 2. Listar Organizaciones y Configuraciones
```bash
docker exec -it docucenter_laravel.test php artisan test:sql-server-management list
```
Muestra todas las organizaciones y sus configuraciones SQL Server asociadas.

### 3. Crear Configuración de Prueba
```bash
# Requiere especificar ID de organización
docker exec -it docucenter_laravel.test php artisan test:sql-server-management create --org-id=1
```
Crea una configuración de prueba con datos predefinidos.

### 4. Eliminar Configuración
```bash
# Requiere especificar ID de configuración
docker exec -it docucenter_laravel.test php artisan test:sql-server-management delete --config-id=1
```
Elimina una configuración específica con confirmación.

## Escenarios de Prueba

### Escenario 1: Flujo Completo Básico
1. **Listar organizaciones**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management list
   ```

2. **Crear configuración de prueba**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management create --org-id=1
   ```

3. **Verificar creación**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management list
   ```

4. **Eliminar configuración**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management delete --config-id=[ID_OBTENIDO]
   ```

### Escenario 2: Testing Interactivo Avanzado
1. **Iniciar modo interactivo**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management
   ```

2. **Seguir secuencia**:
   - Seleccionar "list" para ver estado actual
   - Seleccionar "create" para crear nueva configuración
   - Proporcionar datos cuando se solicite
   - Seleccionar "show" para ver detalles
   - Seleccionar "update" para modificar Store ID
   - Seleccionar "test" para simular conexión
   - Seleccionar "delete" para limpiar
   - Seleccionar "exit" para salir

### Escenario 3: Validación de Errores
1. **Organización inexistente**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management create --org-id=99999
   ```
   Debe mostrar error de organización no encontrada.

2. **Configuración inexistente**:
   ```bash
   docker exec -it docucenter_laravel.test php artisan test:sql-server-management delete --config-id=99999
   ```
   Debe mostrar error de configuración no encontrada.

## Testing de la Interfaz Web

### 1. Acceso a la Interfaz
1. Abrir navegador en `http://localhost/admin/sql_server_management`
2. Verificar que se carga la página sin errores
3. Confirmar que aparece el título "Gestión de Configuraciones SQL Server"

### 2. Selección de Organización
1. Verificar que el dropdown muestra organizaciones disponibles
2. Seleccionar una organización
3. Confirmar que se cargan las configuraciones existentes (si las hay)
4. Verificar que aparece el botón "Nueva Configuración"

### 3. Crear Nueva Configuración
1. Click en "Nueva Configuración"
2. Verificar que se abre el formulario
3. Llenar todos los campos obligatorios:
   - Host: `192.168.1.100`
   - Puerto: `1433`
   - Base de Datos: `TestDB`
   - Usuario: `testuser`
   - Contraseña: `testpass123`
   - Store ID: `STORE_TEST_001` (opcional)
4. Click en "Guardar"
5. Verificar mensaje de éxito
6. Confirmar que aparece en la tabla

### 4. Validaciones del Formulario
1. Intentar guardar con campos vacíos
2. Verificar que aparecen mensajes de error
3. Verificar que se marcan los campos con error
4. Llenar campos gradualmente y verificar validación en tiempo real

### 5. Editar Configuración
1. Click en el botón de editar (icono de lápiz)
2. Verificar que se cargan los datos existentes
3. Modificar algunos campos
4. Guardar y verificar actualización
5. Confirmar cambios en la tabla

### 6. Prueba de Conexión
1. Click en el botón de prueba (icono de enchufe)
2. Verificar mensaje de simulación exitosa
3. Confirmar que no hay errores en consola

### 7. Eliminar Configuración
1. Click en el botón de eliminar (icono de basura)
2. Verificar que aparece confirmación JavaScript
3. Confirmar eliminación
4. Verificar mensaje de éxito
5. Confirmar que desaparece de la tabla

## Verificaciones de Base de Datos

### 1. Verificar Migración
```sql
DESCRIBE configuration_sql_server_organizations;
```
Debe mostrar el campo `store_id` como nullable varchar(255).

### 2. Verificar Datos Creados
```sql
SELECT * FROM configuration_sql_server_organizations;
```
Debe mostrar las configuraciones creadas durante las pruebas.

### 3. Verificar Relaciones
```sql
SELECT o.name, c.host, c.port, c.database, c.store_id 
FROM organizations o 
LEFT JOIN configuration_sql_server_organizations c ON o.id = c.organization_id;
```
Debe mostrar organizaciones con sus configuraciones asociadas.

## Testing de Permisos

### 1. Usuario Sin Permisos
1. Crear/usar usuario sin permisos `sql_server_management`
2. Verificar que no aparece en el menú lateral
3. Intentar acceso directo a la URL
4. Confirmar redirección o error 403

### 2. Usuario Con Permisos
1. Usar usuario con permisos administrativos
2. Verificar acceso completo a todas las funcionalidades
3. Confirmar operaciones CRUD funcionan correctamente

## Debugging y Logs

### 1. Verificar Logs de Aplicación
```bash
docker exec -it docucenter_laravel.test tail -f storage/logs/laravel.log
```
Observar logs mientras se ejecutan las pruebas.

### 2. Logs Específicos del Comando
Durante la ejecución del comando de testing, verificar:
- Mensajes de éxito/error claros
- Formateo correcto de salida
- Manejo apropiado de excepciones

### 3. Debugging de la Interfaz
1. Abrir herramientas de desarrollador
2. Verificar que no hay errores JavaScript
3. Confirmar requests AJAX exitosos
4. Verificar respuestas del servidor

## Casos de Prueba Específicos

### Store ID Opcional
1. **Con Store ID**:
   - Crear configuración con Store ID
   - Verificar que se guarda correctamente
   - Confirmar que se muestra como badge en la tabla

2. **Sin Store ID**:
   - Crear configuración sin Store ID
   - Verificar que se guarda como NULL
   - Confirmar que se muestra "No asignado" en la tabla

### Validaciones de Campos
1. **Host**: Probar con IPs válidas e inválidas
2. **Puerto**: Probar con números válidos e inválidos
3. **Base de datos**: Probar con nombres válidos e inválidos
4. **Usuario**: Probar con diferentes formatos
5. **Store ID**: Probar con diferentes formatos y longitudes

## Resultados Esperados

### Pruebas Exitosas
- Todas las operaciones CRUD funcionan correctamente
- Validaciones funcionan como se espera
- UI responde adecuadamente
- No hay errores en logs
- Base de datos se actualiza correctamente

### Problemas Comunes
- **Error 500**: Verificar logs de aplicación
- **Campos no validados**: Revisar reglas de validación
- **UI no responde**: Verificar JavaScript y CSS
- **Permisos**: Confirmar configuración ACL
- **Base de datos**: Verificar migración ejecutada

## Limpieza Post-Testing

### 1. Limpiar Datos de Prueba
```sql
DELETE FROM configuration_sql_server_organizations WHERE host = '192.168.1.100' AND database = 'TestDatabase';
```

### 2. Verificar Estado Limpio
```bash
docker exec -it docucenter_laravel.test php artisan test:sql-server-management list
```
Confirmar que no quedan datos de prueba.

## Notas Importantes

1. **Multi-tenant**: Las pruebas respetan la arquitectura multi-tenant
2. **Conexiones**: Usar el trait `CustomConnection` apropiadamente
3. **Permisos**: Verificar ACL dinámico en todas las pruebas
4. **Logs**: Mantener logging activo durante pruebas para debugging
