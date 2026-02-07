# Comando de Actualización de Estados de Emisión Masiva

## Descripción

El comando `mass-emission:update-states` sincroniza los estados del sistema de emisión masiva con el campo `EzeeIssued` de las facturas, asegurando coherencia de datos y previniendo reintentos innecesarios en facturas ya emitidas exitosamente.

## Funcionalidad Principal

### 1. Actualización de Estados Automática

**Facturas ya emitidas (EzeeIssued = true):**
- Se marcan como `mass_emission_status = 'success'`
- Se previene cualquier reintento futuro
- Se limpia `mass_emission_last_error`

**Facturas no emitidas que pueden reintentar:**
- Estado `processing` → `retry_needed`
- Estado `failed` → `retry_needed`
- Solo si `EzeeIssued = false`

**Facturas sin estado de emisión masiva:**
- Se mantienen como `mass_emission_status = NULL`
- Solo entran al sistema cuando se procesan por emisión masiva

### 2. Filtrado en Pantalla de Monitoreo

**Filosofía de diseño:**
- **Solo muestra facturas procesadas** por emisión masiva (`mass_emission_status IS NOT NULL`)
- **Facturas nuevas permanecen invisibles** hasta que entren al sistema de emisión masiva
- **Evita saturar** la pantalla con todas las facturas de la organización
- **Facilita el seguimiento** específico de procesos de emisión masiva

## Uso del Comando

### Sintaxis Básica

```bash
php artisan mass-emission:update-states [opciones]
```

### Opciones Disponibles

| Opción | Descripción | Ejemplo |
|--------|-------------|---------|
| `--organization=ID` | Procesar solo una organización específica | `--organization=1` |
| `--environment=ENV` | Filtrar por entorno (production/development) | `--environment=production` |
| `--force` | Ejecutar sin confirmaciones interactivas | `--force` |

### Ejemplos de Uso

**Modo interactivo (recomendado para primera ejecución):**
```bash
php artisan mass-emission:update-states
```

**Actualizar todas las organizaciones sin confirmación:**
```bash
php artisan mass-emission:update-states --force
```

**Actualizar solo organizaciones de producción:**
```bash
php artisan mass-emission:update-states --environment=production --force
```

**Actualizar una organización específica:**
```bash
php artisan mass-emission:update-states --organization=1 --force
```

## Lógica de Actualización

### Transacción de Base de Datos

Cada organización se procesa en una transacción independiente:

```php
DB::beginTransaction();
try {
    // 1. Actualizar facturas exitosas
    SalesHeaderImp::where('EzeeIssued', true)
        ->whereNot('mass_emission_status', 'success')
        ->update([
            'mass_emission_status' => 'success',
            'mass_emission_last_attempt' => now(),
            'mass_emission_last_error' => null
        ]);

    // 2. Marcar para reintento facturas no exitosas
    SalesHeaderImp::where('EzeeIssued', false)
        ->whereIn('mass_emission_status', ['processing', 'failed'])
        ->update([
            'mass_emission_status' => 'retry_needed',
            'mass_emission_last_error' => 'Estado actualizado - listo para reintento'
        ]);

    // Las facturas sin estado (NULL) se mantienen así hasta que sean procesadas
    // por el sistema de emisión masiva mediante Jobs

    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Validaciones de Seguridad

**Verificación de estructura:**
- Existe tabla `Sales_Header_Imp`
- Existen columnas necesarias para emisión masiva
- Base de datos accesible

**Confirmaciones interactivas:**
- Muestra estadísticas ANTES y DESPUÉS
- Solicita confirmación para cada tipo de cambio
- Se puede cancelar en cualquier momento (sin `--force`)

## Prevención de Reintentos Incorrectos

### En el Componente Livewire

```php
public function retrySelected()
{
    // Validar facturas ya exitosas
    $successfullyIssued = SalesHeaderImp::whereIn('ID', $this->retryIds)
        ->where(function($query) {
            $query->where('mass_emission_status', 'success')
                  ->orWhere('EzeeIssued', true);
        })
        ->count();

    if ($successfullyIssued > 0) {
        session()->flash('error', 
            "No se pueden reintentar {$successfullyIssued} facturas ya emitidas exitosamente."
        );
        return;
    }

    // Solo procesar facturas válidas para reintento
    $retryableInvoices = SalesHeaderImp::whereIn('ID', $this->retryIds)
        ->where('mass_emission_status', '!=', 'success')
        ->where('EzeeIssued', false)
        ->pluck('ID')
        ->toArray();
}
```

## Filtros de Visualización

### Estadísticas

**Antes:**
```php
'total' => SalesHeaderImp::count(), // Todas las facturas
```

**Después:**
```php
// Solo facturas procesadas por emisión masiva
$massEmissionQuery = SalesHeaderImp::whereNotNull('mass_emission_status');
'total' => $massEmissionQuery->count(),
```

### Lista de Facturas

**Antes:**
```php
$query = SalesHeaderImp::query() // Todas las facturas
```

**Después:**
```php
$query = SalesHeaderImp::query()
    ->whereNotNull('mass_emission_status') // Solo facturas procesadas
```

## Salida del Comando

### Estadísticas Mostradas

Para cada organización:
```
📁 Procesando organización: Empresa Demo (ID: 1)
  Estado ANTES:
    Total facturas: 1500
    EzeeIssued=true: 800
    EzeeIssued=false: 700
    Estados emisión masiva:
      - Pending: 200
      - Processing: 50
      - Success: 750
      - Failed: 100
      - Retry needed: 50
      - Sin estado: 350

  ✅ 800 facturas emitidas marcadas como 'success'
  ✅ 150 facturas no emitidas marcadas para 'retry_needed'  
  ✅ 350 facturas inicializadas como 'pending'

  Estado DESPUÉS:
    [estadísticas actualizadas...]
```

### Resumen Final

```
=== RESUMEN FINAL ===
Organizaciones procesadas: 5
Total registros actualizados: 1250
```

## Casos de Uso Principales

### 1. **Migración Inicial**
Cuando se implementa el sistema por primera vez:
```bash
php artisan mass-emission:update-states --force
```

### 2. **Corrección de Inconsistencias**
Cuando hay desincronización entre `EzeeIssued` y `mass_emission_status`:
```bash
php artisan mass-emission:update-states --organization=1
```

### 3. **Mantenimiento Programado**
Ejecución regular para mantener coherencia:
```bash
# En cron job
0 2 * * * php artisan mass-emission:update-states --force
```

### 4. **Despliegue por Entornos**
Separar actualizaciones por ambiente:
```bash
# Solo desarrollo
php artisan mass-emission:update-states --environment=development --force

# Solo producción  
php artisan mass-emission:update-states --environment=production --force
```

## Consideraciones de Rendimiento

### Multi-Tenant Optimizado
- Cada organización usa su propia conexión de BD
- Transacciones independientes por organización
- Fallo en una organización no afecta las demás

### Consultas Eficientes
- Uso de índices en `mass_emission_status` y `EzeeIssued`
- Actualizaciones por lotes, no individuales
- Contadores optimizados para estadísticas

### Manejo de Errores
- Rollback automático por organización en caso de error
- Logging detallado de errores
- Continuación del proceso aunque fallen organizaciones individuales

## Testing

### Script de Prueba Automatizado

```bash
# Ejecutar todas las pruebas
./docs/testing/test-mass-emission-update-states.sh
```

### Verificaciones Incluidas
1. ✅ Comando disponible en Artisan
2. ✅ Ayuda del comando funcional  
3. ✅ Modo dry-run (sin --force)
4. ✅ Actualización real con confirmación
5. ✅ Verificación de consistencia post-actualización
6. ✅ Prueba de filtros por organización

## Troubleshooting

### Errores Comunes

**Error: Tabla no encontrada**
```
⚠️  Tabla o columnas faltantes en db_organization_123
```
**Solución:** Ejecutar migraciones de emisión masiva en esa BD

**Error: Sin organizaciones**
```
No se encontraron organizaciones para procesar
```
**Solución:** Verificar filtros `--organization` o `--environment`

**Error: Sin permisos**
```
Access denied for user
```
**Solución:** Verificar permisos de BD para el usuario de Laravel

### Verificación Manual

**Comprobar consistencia:**
```sql
-- Facturas emitidas que no están marcadas como success
SELECT COUNT(*) FROM Sales_Header_Imp 
WHERE EzeeIssued = 1 AND mass_emission_status != 'success';

-- Debe retornar 0 después de ejecutar el comando
```

**Ver distribución de estados:**
```sql
SELECT mass_emission_status, COUNT(*) as total 
FROM Sales_Header_Imp 
GROUP BY mass_emission_status;
```

## Integración con Sistema Existente

### Componente Livewire Actualizado
- ✅ Filtros para mostrar solo facturas procesadas
- ✅ Validación anti-reintentos en facturas exitosas  
- ✅ Estadísticas precisas de emisión masiva

### Jobs de Emisión
- ✅ Compatible con `IssueMassInvoicesJob` existente
- ✅ Respeta estados actualizados por el comando
- ✅ No interfiere con lógica de reintentos automáticos

### Base de Datos
- ✅ No modifica estructura existente
- ✅ Solo actualiza datos basado en lógica de negocio
- ✅ Mantiene integridad referencial

## Próximos Pasos Recomendados

1. **Ejecutar en desarrollo** primero para validar
2. **Programar en cron** para mantenimiento automático
3. **Monitorear logs** después de la primera ejecución en producción
4. **Crear alertas** para inconsistencias detectadas
5. **Documentar procedimientos** específicos del cliente
