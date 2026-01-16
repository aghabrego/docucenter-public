# 🔧 INSTRUCCIONES - ACTUALIZAR TIPOS DE DOCUMENTO

## ❌ **PROBLEMA IDENTIFICADO**

Solo estás viendo 2 tipos de documento en el dropdown:
- `value="01"` → Factura de operación interna
- `value="06"` → Nota de Crédito genérica

**Deberían aparecer 9 tipos JSch09 oficiales.**

---

## 🎯 **CAUSA RAÍZ**

El seeder no estaba guardando el campo `code` en la tabla `typedocuments`. Solo guardaba el `name`, por lo que el DataProvider no podía usar los códigos JSch09.

---

## ✅ **SOLUCIONES IMPLEMENTADAS**

### 1. **Seeder Corregido**
**Archivo**: `database/seeders/TypedocumentSeeder.php`
```php
// ANTES (incorrecto)
Typedocument::firstOrCreate(['name' => $name], ['name' => $name]);

// AHORA (correcto)
Typedocument::firstOrCreate(['code' => $code], [
    'name' => $name,
    'code' => $code, // ¡Guarda el código JSch09!
]);
```

### 2. **Script SQL Manual**
**Archivo**: `database/sql/update-typedocuments-jsch09.sql`
- INSERT de los 9 tipos JSch09 con códigos correctos
- Listo para ejecutar directamente en la base de datos

### 3. **Comando Artisan**
**Archivo**: `app/Console/Commands/UpdateTypedocuments.php`
- Comando: `php artisan typedocuments:update`
- Actualiza automáticamente la tabla con códigos JSch09

---

## 🚀 **PASOS PARA RESOLVER**

### **Opción 1: Comando Artisan (Recomendado)**
```bash
# Ver qué se actualizaría
php artisan typedocuments:update --show

# Actualizar la base de datos
php artisan typedocuments:update

# Forzar actualización si hay conflictos
php artisan typedocuments:update --force
```

### **Opción 2: Script SQL Manual**
```sql
-- Ejecutar el archivo database/sql/update-typedocuments-jsch09.sql
-- en tu cliente MySQL/MariaDB
```

### **Opción 3: Seeder Laravel**
```bash
php artisan db:seed --class=TypedocumentSeeder
```

---

## 🎉 **RESULTADO ESPERADO**

Después de ejecutar cualquiera de las opciones, el dropdown mostrará:

```html
<select id="input-tipeDocument" wire:model.lazy="tipeDocument">
    <option value="">--- Seleccionar ---</option>
    <option value="01">Factura de operación interna</option>
    <option value="02">Factura de importación</option>
    <option value="03">Factura de exportación</option>
    <option value="04">Nota de Crédito referente a una o varias FE</option>
    <option value="05">Nota de Débito referente a una o varias FE</option>
    <option value="06">Nota de Crédito genérica</option>
    <option value="07">Nota de Débito genérica</option>
    <option value="08">Factura de Zona Franca</option>
    <option value="09">Reembolso</option>
</select>
```

---

## ✅ **VERIFICACIÓN**

1. **Base de datos**: Tabla `typedocuments` debe tener 9 registros con columna `code` llena
2. **Dropdown**: Debe mostrar 9 opciones con values '01', '02', etc.
3. **Conditional Fields**: Deben funcionar correctamente al seleccionar cada tipo

---

**¡Ejecuta una de las opciones y el problema estará resuelto!** 🎯
