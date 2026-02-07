# Sistema de Gestión de Asientos de Diario QuickBooks

## Descripción General

Sistema completo para gestión de asientos de diario (Journal Entries) pendientes de exportación a QuickBooks Online. Permite visualizar, editar cuentas GL, y reenviar asientos tanto individualmente como en lote.

## Ubicación

**Componente Livewire**: `app/Http/Livewire/Admin/Reports/AccountingMappingManager.php`  
**Vista Blade**: `resources/views/livewire/admin/reports/accounting-mapping-manager.blade.php`  
**Ruta**: `/accounting_mapping`  
**Menú**: Reportes → Cuentas Contables

## Arquitectura

### Modelos Principales

#### GJEHeaderImp (Cabecera de Asientos)
```php
TransactionID (PK)  // ID único del asiento
Reference          // Formato: vta-{ORG}-{ID}-{DATE}
Date              // Fecha del asiento
EzeeExport        // boolean - false = pendiente, true = exportado
```

#### GJEDetailImp (Líneas de Asiento)
```php
TransactionID (FK) // Relaciona con GJEHeaderImp
DetailID          // ID de la línea
GL_Account        // Código de cuenta contable QB (EDITABLE)
Description       // Descripción de la línea
Amount           // Monto (>0 = Débito, <0 = Crédito)
```

### Relación
```php
GJEHeaderImp::generalJournalEntryDetailds() -> HasMany(GJEDetailImp)
```

## Funcionalidades

### 1. Dashboard de Estadísticas

**Métricas mostradas**:
- Asientos Pendientes (EzeeExport = false)
- Asientos Exportados (EzeeExport = true)
- Total Débitos (últimos 100 asientos pendientes)
- Total Créditos (últimos 100 asientos pendientes)

### 2. Sistema de Filtros

**Filtros disponibles**:
- Fecha Desde/Hasta
- Referencia (búsqueda parcial)
- Estado (Todos/Pendientes/Exportados)

### 3. Edición de Cuentas GL

Permite modificar el código de cuenta GL de cualquier línea en asientos pendientes antes del reenvío a QuickBooks.

### 4. Vista Previa

Modal con resumen completo del asiento incluyendo totales y balance.

### 5. Reenvío a QuickBooks

- Individual: Botón en cada asiento
- Masivo: Selección múltiple con checkboxes

**Proceso**:
1. Marca asientos como EzeeExport = true
2. Dispara `UploadSalesGeneralDiaryIntuitJob`
3. Job envía a QB con retry (10 intentos)

## Flujo de Usuario

1. Acceso desde menú Reportes
2. Ver dashboard con estadísticas
3. Aplicar filtros si es necesario
4. Revisar asientos pendientes
5. Editar cuentas GL incorrectas
6. Ver vista previa para validar
7. Seleccionar asientos a reenviar
8. Confirmar reenvío a QuickBooks
9. Verificar actualización de estadísticas

## Testing Manual

```bash
# Verificar componente carga
docker exec -it docucenter-app-1 php artisan route:list | grep accounting_mapping

# Crear asiento de prueba
docker exec -it docucenter-app-1 php artisan tinker
>>> $header = GJEHeaderImp::create([
...     'Reference' => 'vta-TEST-001-' . now()->format('YmdHis'),
...     'Date' => now()->toDateString(),
...     'EzeeExport' => false,
... ]);
>>> GJEDetailImp::create([
...     'TransactionID' => $header->TransactionID,
...     'DetailID' => 1,
...     'GL_Account' => '1000',
...     'Description' => 'Prueba débito',
...     'Amount' => 100.00,
... ]);
```

## Solución de Problemas

### Asientos no aparecen
- Verificar filtros activos
- Hacer clic en "Limpiar" para resetear

### Error al editar GL
- Verificar conexión multi-tenant establecida
- Revisar permisos de organización

### Job no se ejecuta
- Verificar Redis: `docker exec -it docucenter-redis-1 redis-cli ping`
- Verificar queue worker: `docker exec -it docucenter-app-1 supervisorctl status`

---

**Versión**: 2.0 (Refactorización completa)  
**Última actualización**: 2024-01
