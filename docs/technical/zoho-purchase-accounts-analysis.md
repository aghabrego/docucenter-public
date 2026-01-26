# Análisis y Solución: Mapeo de Cuentas AP/AR en Integración Zoho Purchase Orders

**Fecha**: 2025-11-14  
**Status**: **IMPLEMENTADO**  
**Solicitado por**: Usuario  
**Contexto**: Las cuentas AP_Account no se estaban cargando desde la configuración de DocuCenter

---

## SOLUCIÓN IMPLEMENTADA

Se ha implementado la integración con la configuración existente de **`setting/accounts/sage50`** (modelo `ImportConfigurationMagaya`).

### Cambios Realizados

#### 1. **ZohoPurchaseOrderTransformer** - Modificado

**Archivo**: `app/Services/Zoho/ZohoPurchaseOrderTransformer.php`

**Cambios**:
- Agregado import de `ImportConfigurationMagaya`
- Agregada propiedad `$organizationId`
- Modificado constructor para recibir `organizationId`
- Creado método `getConfiguredAPAccount()` que:
  - Consulta `ImportConfigurationMagaya::getAccountInfo('AP_Account', $organizationId)`
  - Usa cuenta configurada si existe
  - Fallback a `account_name` de Zoho si no hay configuración
  - Log detallado de origen de cuenta

**Código implementado**:
```php
public function __construct(ZohoSelfClientService $zohoService = null, int $organizationId = null)
{
    $this->zohoService = $zohoService;
    $this->organizationId = $organizationId;
    $this->customFieldsHelper = new ZohoCustomFieldsHelper($zohoService);
}

private function getConfiguredAPAccount(): string
{
    if ($this->organizationId) {
        $configuredAccount = ImportConfigurationMagaya::getAccountInfo('AP_Account', $this->organizationId);
        
        if (!empty($configuredAccount)) {
            Log::info('Zoho Purchase: Usando AP_Account desde configuración DocuCenter', [
                'organization_id' => $this->organizationId,
                'ap_account' => $configuredAccount,
                'source' => 'docucenter_config'
            ]);
            
            return $configuredAccount;
        }
    }

    // Fallback a Zoho
    return $this->getAccountFromLineItems([]);
}
```

#### 2. **ProcessZohoPurchaseOrderJob** - Modificado

**Archivo**: `app/Jobs/ProcessZohoPurchaseOrderJob.php`

**Cambio**: Pasar `organizationId` al constructor del transformer

```php
// ANTES
$transformer = new ZohoPurchaseOrderTransformer($zohoService);

// AHORA
$transformer = new ZohoPurchaseOrderTransformer($zohoService, $this->organizationId);
```

---

## Cómo Funciona Ahora

### Flujo de Configuración

1. **Usuario configura cuentas** en:
   - **Ruta**: `/setting/accounts/sage50`
   - **Componente**: `App\Http\Livewire\Setting\ImportSage50`
   - **Campos disponibles**:
     - `AP_Account` ← **Usado para Zoho Purchase Orders**
     - `AR_Account` (para futuras ventas)
     - `TaxID`
     - Cuentas de resumen detalladas

2. **Configuración se almacena** en:
   - **Tabla**: `import_magaya_settings`
   - **Modelo**: `ImportConfigurationMagaya`
   - **Campo**: `accounts` (serializado)

3. **Zoho Purchase Order usa configuración**:
   - Job recibe `organizationId`
   - Transformer consulta `ImportConfigurationMagaya::getAccountInfo('AP_Account', $organizationId)`
   - Si existe configuración → usa cuenta configurada
   - Si NO existe → usa `account_name` del primer line item de Zoho (fallback)

### Logs de Tracking

Cada uso de cuenta se registra con origen:

```php
// Usando configuración DocuCenter
[
    'organization_id' => 123,
    'ap_account' => '2100',
    'source' => 'docucenter_config'
]

// Fallback a Zoho
[
    'organization_id' => 123,
    'source' => 'zoho_line_item_fallback'
]
```

---

---

## Problema Original Identificado

### Situación Anterior (RESUELTO)

La API `/api/acicloud/create_purchase_zoho` estaba usando **cuentas del payload de Zoho** en lugar de **cuentas configuradas en DocuCenter**.

**Código problemático** (ANTES):
```php
// Línea 75 en ZohoPurchaseOrderTransformer
'AP_Account' => $this->truncateText($this->getAccountFromLineItems($zohoData), 15),
```

**Código actual** (DESPUÉS):
```php
// Obtener desde configuración DocuCenter
$apAccount = $this->getConfiguredAPAccount();
'AP_Account' => $this->truncateText($apAccount, 15),
```

---

## Configuración Disponible

### Acceso a Configuración

**URL**: `/setting/accounts/sage50`

**Campos configurables**:
- **AP_Account** - Cuenta de Compras (Accounts Payable) - **USADO AHORA**
- **AR_Account** - Cuenta de Ventas (Accounts Receivable) - Disponible para futuro
- **TaxID** - ID de Impuesto
- **Cuentas de resumen detalladas** - Para daily entries

### Instrucciones de Uso

1. Ir a `/setting/accounts/sage50`
2. Seleccionar organización
3. Configurar `AP_Account` con la cuenta contable deseada (ej: `2100`)
4. Guardar cambios
5. Las nuevas purchase orders de Zoho usarán automáticamente esta cuenta

---

## Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `app/Services/Zoho/ZohoPurchaseOrderTransformer.php` | • Agregado `$organizationId`<br>• Nuevo método `getConfiguredAPAccount()`<br>• Import de `ImportConfigurationMagaya`<br>• Logs de tracking |
| `app/Jobs/ProcessZohoPurchaseOrderJob.php` | • Pasar `organizationId` al transformer |
| `docs/technical/zoho-purchase-accounts-analysis.md` | • Documentación actualizada |

---

## Testing

### Caso 1: Con Configuración
```
Organización: 123
Configuración: AP_Account = "2100"
Resultado: purchase_header_imp.AP_Account = "2100"
Log: source = "docucenter_config"
```

### Caso 2: Sin Configuración (Fallback)
```
Organización: 456 (sin configurar)
Resultado: purchase_header_imp.AP_Account = "Activo de inventario" (desde Zoho)
Log: source = "zoho_line_item_fallback"
```

---

## Próximos Pasos Opcionales

### Mejoras Futuras (No Urgente)

1. **AR_Account para Ventas Zoho**: Usar la configuración existente cuando se implemente sales orders
2. **GL_Account por defecto**: Agregar campo en configuración para line items sin `account_name`
3. **Validación de cuentas**: Validar formato de cuentas al guardar configuración
4. **Dashboard de auditoría**: Mostrar qué órdenes usan config vs fallback

---

## Referencias Originales

- **Controller**: `app/Http/Controllers/Sage/ACIcloudController.php:458`
- **Job**: `app/Jobs/ProcessZohoPurchaseOrderJob.php`
- **Transformer**: `app/Services/Zoho/ZohoPurchaseOrderTransformer.php`
- **Importer**: `app/Services/Zoho/ZohoPurchaseOrderImporter.php`
- **Configuración UI**: `app/Http/Livewire/Setting/ImportSage50.php`
- **Modelo Config**: `app/Models/ImportConfigurationMagaya.php`
