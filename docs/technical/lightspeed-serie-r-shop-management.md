# Gestión de Tiendas - Lightspeed Serie R

## Implementación Completa

**Fecha:** 6 de noviembre de 2025  
**Estado:** ✅ Implementado  

---

## 📊 Descripción

Sistema de gestión de configuración de tiendas (Shop ID) para organizaciones con integración Lightspeed Serie R.

---

## 🗄️ Estructura de Base de Datos

### Tabla: `lightspeed_serie_r_shop_configurations`

```sql
CREATE TABLE lightspeed_serie_r_shop_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED UNIQUE NOT NULL,
    shop_id INT NOT NULL,
    shop_name VARCHAR(255) NULL,
    account_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_org_active (organization_id, is_active)
);
```

**Campos:**
- `organization_id`: FK única a la organización
- `shop_id`: ID de la tienda en Lightspeed Serie R API
- `shop_name`: Nombre descriptivo (opcional)
- `account_id`: ID de la cuenta (ej: 192176)
- `is_active`: Estado de la configuración

---

## 📁 Archivos Creados

### 1. Migration
```
database/migrations/2025_11_06_000001_create_lightspeed_serie_r_shop_configurations_table.php
```

### 2. Modelo
```php
app/Models/LightspeedSerieRShopConfiguration.php
```

**Métodos principales:**
- `getByOrganization($organizationId)`: Obtener configuración activa
- `getShopId($organizationId)`: Obtener solo el shop_id
- `getAccountId($organizationId)`: Obtener solo el account_id

### 3. Componente Livewire
```php
app/Http/Livewire/Admin/LightspeedSerieR/ManageShop.php
```

**Funcionalidades:**
- ✅ Crear configuración de tienda
- ✅ Actualizar configuración existente
- ✅ Eliminar configuración
- ✅ Validación de datos
- ✅ Manejo de transacciones
- ✅ Solo organizaciones con conexión Serie R

### 4. Vista Blade
```
resources/views/livewire/admin/lightspeed-serie-r/manage-shop.blade.php
```

**Características:**
- 📝 Formulario completo con validación
- 🎨 Diseño AdminLTE
- ℹ️ Card de ayuda e información
- 🔄 Loading states
- ⚠️ Confirmación de eliminación

### 5. Helper Function
```php
// app/Support/helper.php

get_lightspeed_serie_r_shop_config($organizationId)
```

Retorna la configuración activa de la tienda para una organización.

---

## 🔧 Integración con SetSalesOrdersJob

### Modificación en `app/Jobs/LightspeedSerieR/SetSalesOrdersJob.php`

**Antes:**
```php
$shopID = $this->filterVar(array_get($sale, 'shopID', 0), FILTER_VALIDATE_INT);
```

**Después:**
```php
// Obtener shopID desde configuración
$shopConfig = get_lightspeed_serie_r_shop_config($orgActive->id);
$configuredShopID = $shopConfig ? $shopConfig->shop_id : null;

// Validar coincidencia
if ($configuredShopID && $shopID && $configuredShopID !== $shopID) {
    \Log::warning("ShopIDMismatch", [
        'configured' => $configuredShopID,
        'from_sale' => $shopID
    ]);
}

// Usar configurado o de la venta
$shopID = $configuredShopID ?? $shopID;
```

**Beneficios:**
- ✅ Prioriza configuración manual
- ✅ Valida inconsistencias
- ✅ Fallback al shopID de la API
- ✅ Logging de discrepancias

---

## 🌐 Rutas

### Ruta Web
```php
Route::get(
    '/configuration/lightspeed-serie-r/shop',
    \App\Http\Livewire\Admin\LightspeedSerieR\ManageShop::class
)->name('einvoice.lightspeed_serie_r_shop');
```

**URL:** `/e_invoice/configuration/lightspeed-serie-r/shop`

**Middleware:**
- `dynamicAcl`: Control de acceso
- `check.active.organization`: Validar organización activa

---

## 📖 Uso

### 1. Acceder a la Pantalla

Navegar a: **Facturación Electrónica → Configuración → Gestión de Tienda Serie R**

### 2. Configurar Tienda

1. Seleccionar **Organización** (solo con conexión Serie R)
2. Ingresar **Shop ID** (número de la tienda)
3. Opcional: **Nombre de la Tienda**
4. Confirmar **Account ID** (por defecto: 192176)
5. Marcar **Configuración Activa**
6. Guardar

### 3. Consultar desde Código

```php
// Obtener configuración completa
$config = \App\Models\LightspeedSerieRShopConfiguration::getByOrganization($orgId);

// Solo shop_id
$shopId = \App\Models\LightspeedSerieRShopConfiguration::getShopId($orgId);

// Usando helper
$config = get_lightspeed_serie_r_shop_config($orgId);
$shopId = $config->shop_id;
```

---

## 🔍 Validaciones

### Reglas de Validación

```php
'organization_id' => 'required|exists:organizations,id',
'shop_id' => 'required|integer|min:1',
'shop_name' => 'nullable|string|max:255',
'account_id' => 'required|integer|min:1',
'is_active' => 'boolean',
```

### Constraints de Base de Datos

- ✅ `organization_id` es **UNIQUE** (una configuración por organización)
- ✅ Foreign Key con `CASCADE DELETE`
- ✅ Índice compuesto en `(organization_id, is_active)`

---

## 🧪 Testing

### Verificar Migration

```bash
docker exec -it docucenter-app-1 php artisan migrate
```

### Probar en Tinker

```php
docker exec -it docucenter-app-1 php artisan tinker

// Crear configuración
$config = \App\Models\LightspeedSerieRShopConfiguration::create([
    'organization_id' => 1,
    'shop_id' => 123,
    'shop_name' => 'Tienda Principal',
    'account_id' => 192176,
    'is_active' => true
]);

// Obtener configuración
$config = \App\Models\LightspeedSerieRShopConfiguration::getByOrganization(1);

// Obtener solo shop_id
$shopId = \App\Models\LightspeedSerieRShopConfiguration::getShopId(1);
```

### Probar Helper

```php
$config = get_lightspeed_serie_r_shop_config(1);
echo $config->shop_id; // 123
```

---

## 📊 Estructura del Proyecto

```
app/
├── Http/Livewire/Admin/
│   └── LightspeedSerieR/
│       └── ManageShop.php
├── Jobs/LightspeedSerieR/
│   └── SetSalesOrdersJob.php (modificado)
├── Models/
│   └── LightspeedSerieRShopConfiguration.php
└── Support/
    └── helper.php (actualizado)

database/migrations/
└── 2025_11_06_000001_create_lightspeed_serie_r_shop_configurations_table.php

resources/views/livewire/admin/
└── lightspeed-serie-r/
    └── manage-shop.blade.php

routes/
└── web.php (actualizado)
```

---

## ✅ Checklist de Implementación

- [x] Migration creada
- [x] Modelo con métodos helper
- [x] Componente Livewire funcional
- [x] Vista con diseño AdminLTE
- [x] Helper function global
- [x] Integración con SetSalesOrdersJob
- [x] Ruta web configurada
- [x] Validaciones implementadas
- [x] Documentación completa

---

## 🔄 Próximos Pasos

1. ⏳ Ejecutar migration en producción
2. ⏳ Configurar shop_id para cada organización
3. ⏳ Monitorear logs de discrepancias
4. ⏳ Agregar tests unitarios (opcional)
5. ⏳ Agregar al menú de navegación

---

## 📝 Notas

- **shopID** es específico de cada tienda en Lightspeed Serie R
- Una organización = Una tienda (constraint UNIQUE)
- El sistema prioriza configuración manual sobre API
- Logs de advertencia cuando no coinciden los IDs
- Solo organizaciones con `application = 'lightspeed-serie-r'`

---

## 🆘 Troubleshooting

### Error: "organization_id ya existe"

**Causa:** Ya hay una configuración para esa organización  
**Solución:** Editar la existente o eliminarla primero

### Shop ID no se usa en el Job

**Verificar:**
1. Configuración está activa (`is_active = true`)
2. Helper function está importada
3. Revisar logs de advertencia

### No aparecen organizaciones en el select

**Causa:** No tienen conexión Serie R  
**Solución:** Crear conexión con `application = 'lightspeed-serie-r'`

---

## 📞 Referencias

- **API Endpoint**: `GET /API/V3/Account/{accountId}/Shop/{shopId}.json`
- **Documentación API**: Lightspeed Serie R API V3
- **Modelo relacionado**: `SetSalesOrdersJob.php` línea 364
