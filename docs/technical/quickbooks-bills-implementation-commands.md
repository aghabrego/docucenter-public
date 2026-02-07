# Comandos para Implementar Job de Bills QuickBooks

## 1. Agregar campos QuickBooks a Purchase_Header_Imp

### Verificar estado actual
```bash
cd /home/weirdolabs/code/docucenter
./scripts/add-quickbooks-fields-to-purchase-header.sh check
```

### Ejecutar adición de campos en todas las organizaciones
```bash
cd /home/weirdolabs/code/docucenter
./scripts/add-quickbooks-fields-to-purchase-header.sh execute
```

### Ver ayuda del script
```bash
./scripts/add-quickbooks-fields-to-purchase-header.sh help
```

## 2. Probar el Job de creación de Bills

### Ejecutar el comando para crear Bills en QuickBooks
```bash
php artisan word:create-intuit-bills --organization_id=1
```

### Ejecutar para todas las organizaciones
```bash
php artisan word:create-intuit-bills
```

## 3. Verificación y Testing

### Verificar que los campos se agregaron correctamente
Conectarse a una BD de organización y ejecutar:
```sql
DESCRIBE Purchase_Header_Imp;
```

Buscar estos campos:
- quickbooks_sync_status
- quickbooks_bill_id
- quickbooks_sync_error
- quickbooks_sync_attempts
- quickbooks_sync_started_at
- quickbooks_sync_completed_at

### Probar con datos de prueba
```sql
-- Insertar una compra de prueba
INSERT INTO Purchase_Header_Imp (
    ID_compania, PurchaseNumber, VendorID, VendorName, 
    AP_Account, Date, Subtotal, Net_due
) VALUES (
    1, 'TEST-001', 'VENDOR001', 'Proveedor de Prueba',
    '2000', NOW(), 100.00, 100.00
);
```

## 4. Monitoreo y Logs

### Ver logs del job
```bash
tail -f storage/logs/laravel.log | grep -i "intuit\|quickbooks\|bill"
```

### Verificar cola de trabajos
```bash
php artisan queue:work --verbose
```

## 5. Rollback (si es necesario)

### Ver comandos de rollback
```bash
./scripts/add-quickbooks-fields-to-purchase-header.sh rollback
```

**Nota**: El rollback debe ejecutarse manualmente en cada base de datos.

## 6. Archivos Creados/Modificados

### Jobs
- `app/Jobs/Intuit/CreateIntuitBillsJob.php` - Job principal para crear Bills

### Commands
- `app/Console/Commands/Integrations/CreateIntuitBills.php` - Comando para ejecutar el job

### Scripts
- `scripts/add-quickbooks-fields-to-purchase-header.sh` - Script para agregar campos DB

### Stubs SQL
- `app/Models/stubs/Purchase_Header_Imp_QuickBooks_Fields.sql.stub` - Campos QuickBooks

### Modelos
- `app/Models/PurchaseHeaderImp.php` - Ya actualizado con campos QuickBooks

## 7. Configuración Adicional Necesaria

### Variables de Entorno
Asegúrate de que estén configuradas:
```env
ACI_EMAIL=tu_email@ejemplo.com
ACI_PASSWORD=tu_password
```

### Conexiones QuickBooks
Verificar que las organizaciones tengan conexiones QuickBooks configuradas en la tabla `connections` con:
- application = 'acicloud'
- settings contenga Module y App con tokens válidos

## 8. Testing Paso a Paso

1. **Agregar campos**: `./scripts/add-quickbooks-fields-to-purchase-header.sh execute`
2. **Verificar campos**: Conectar a BD y hacer DESCRIBE
3. **Crear datos de prueba**: Insertar Purchase_Header_Imp y Purchase_Detail_Imp
4. **Ejecutar job**: `php artisan word:create-intuit-bills --organization_id=X`
5. **Verificar resultado**: Revisar logs y estado de sincronización
6. **Verificar en QuickBooks**: Confirmar que el Bill se creó correctamente

---

**Nota**: Todos los archivos ya están creados y listos para usar. Solo necesitas ejecutar los comandos en orden.
