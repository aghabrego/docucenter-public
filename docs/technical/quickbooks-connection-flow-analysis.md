# Análisis del Flujo de Conexión QuickBooks - ACI Cloud

## 📊 Flujo Completo de Datos

### 1. Creación de Conexión (Create.php)

**Archivo**: `app/Http/Livewire/Admin/Connection/Create.php`

```php
// Líneas 195-203: Obtiene datos de la API de QuickBooks
if (isset($customer['organizations']) && !empty($customer['organizations'])) {
    $this->settings['Organizations'] = array_map(function ($org) {
        return [
            'Id' => $org['id'],              // ❌ ID NUMÉRICO de QuickBooks (ej: 119)
            'Name' => $org['Nombre'],         // ✅ Nombre de la org
            'RealmId' => $org['realmId'],     // ✅ RealmId de QuickBooks
        ];
    }, $customer['organizations']);
}
```

#### 📝 Estructura de la Respuesta de la API QuickBooks:

```json
{
  "Uid": "DcEUMVoXy51XFdMSQ52L",
  "IdCliente": "9341454854054771",
  "organizations": [
    {
      "id": 119,                           // ❌ ID INTERNO DE QUICKBOOKS
      "Nombre": "Sandbox Company_US_1",
      "realmId": "9341454854054771"
    }
  ]
}
```

#### 💾 Guardado en Base de Datos:

```php
// La conexión se guarda así:
Connection::create([
    'organization_id' => $this->organization_id,  // ✅ ID DE DOCUCENTER (Rw8DunJnEnxY1MS2QHVH)
    'name' => $this->name,
    'application' => 'acicloud',
    'settings' => [
        'Module' => 'quickbooks',
        'App' => 'DcEUMVoXy51XFdMSQ52L',
        'IdCliente' => '9341454854054771',
        'Organizations' => [
            [
                'Id' => 119,                      // ❌ ID DE QUICKBOOKS (numérico)
                'Name' => 'Sandbox Company_US_1',
                'RealmId' => '9341454854054771'
            ]
        ]
    ]
]);
```

---

### 2. Obtención de Conexión (connection_application_filtered)

**Archivo**: `app/Http/helper.php`

```php
function connection_application_filtered(string $applicationDefault = '', string $settingsModule = '') {
    $auth = getAuthenticatedUser();
    $organization = $auth->organization;
    
    // Obtiene las conexiones de la organización
    $query = $organization->connection();
    $query->where('application', $applicationDefault);  // 'acicloud'
    
    $connections = $query->get()->toArray();
    
    // Filtra por Module = 'quickbooks'
    return $connections;
}
```

#### 📤 Retorna:

```php
[
    'id' => 25,
    'organization_id' => 'Rw8DunJnEnxY1MS2QHVH',  // ✅ SID de Firestore de DocuCenter
    'name' => 'QuickBooks Connection',
    'application' => 'acicloud',
    'settings' => [
        'Module' => 'quickbooks',
        'IdCliente' => '9341454854054771',
        'Organizations' => [
            [
                'Id' => 119,                      // ❌ ID DE QUICKBOOKS (numérico)
                'Name' => 'Sandbox Company_US_1',
                'RealmId' => '9341454854054771'
            ]
        ]
    ]
]
```

---

### 3. Configuración de Webhook (Read.php - ANTES del Fix)

**Archivo**: `app/Http/Livewire/Admin/Einvoice/Read.php`

#### ❌ PROBLEMA (Código Anterior):

```php
$quickbooksConnections = connection_application_filtered('acicloud', 'quickbooks');
$connectionData = collect($quickbooksConnections)->first();
$settings = $connectionData['settings'];

// Busca en el array Organizations
foreach ($settings['Organizations'] as $org) {
    if ($org['RealmId'] === $this->selectedOrganizationRealmId) {
        $targetRealmId = $org['RealmId'];
        $targetOrganizationId = $org['Id'];  // ❌ USA ID DE QUICKBOOKS (119)
        break;
    }
}

// Fallback cuando no hay Organizations
$targetOrganizationId = $connectionData['organization_id'];  // ✅ USA SID DE DOCUCENTER
```

#### 🚨 CONSECUENCIA:

Se enviaban **2 valores diferentes** a la API del webhook:

1. **Caso 1**: Cuando hay array `Organizations` → Enviaba `119` (ID de QuickBooks)
2. **Caso 2**: Cuando NO hay array → Enviaba `Rw8DunJnEnxY1MS2QHVH` (SID de DocuCenter)

Esto creaba **2 configuraciones en Firestore**:

```json
// Configuración #1 (correcta)
{
  "DocId": "TMxcK8mY1DwJ0UQQeWma",
  "RealmId": "9341454854054771",
  "OrganizationId": "Rw8DunJnEnxY1MS2QHVH",  // ✅ SID de DocuCenter
  "DocucenterEnabled": true
}

// Configuración #2 (incorrecta)
{
  "DocId": "z8ywLjyJKX4FzQnWBWYY",
  "RealmId": "9341454854054771",
  "OrganizationId": "119",                    // ❌ ID de QuickBooks
  "DocucenterEnabled": true
}
```

---

### 4. Fix Implementado (Read.php - DESPUÉS)

#### ✅ SOLUCIÓN:

```php
$quickbooksConnections = connection_application_filtered('acicloud', 'quickbooks');
$connectionData = collect($quickbooksConnections)->first();
$settings = $connectionData['settings'];

// IMPORTANTE: Siempre usar el organization_id de DocuCenter (sid de Firestore)
// NO usar el Id de QuickBooks que es numérico
$targetOrganizationId = $connectionData['organization_id'];  // ✅ SIEMPRE SID

// Solo buscar el RealmId y Name en Organizations
if (isset($settings['Organizations']) && is_array($settings['Organizations'])) {
    foreach ($settings['Organizations'] as $org) {
        if ($org['RealmId'] === $this->selectedOrganizationRealmId) {
            $targetRealmId = $org['RealmId'];          // ✅ RealmId de QuickBooks
            $targetOrganizationName = $org['Name'];    // ✅ Nombre
            break;
        }
    }
}
```

#### 📤 Enviado a la API:

```php
POST https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/quickbooks/webhook_processing_config

{
  "realmId": "9341454854054771",
  "organizationId": "Rw8DunJnEnxY1MS2QHVH",  // ✅ SIEMPRE SID de DocuCenter
  "docucenterEnabled": true,
  "reason": "Configuración desde interfaz para 'Sandbox Company_US_1' - Habilitado"
}
```

---

## 🔍 Tabla Comparativa

| Campo | Origen | Tipo | Uso Correcto | Uso Incorrecto |
|-------|--------|------|--------------|----------------|
| `organization_id` | BD DocuCenter | String | ✅ Enviar a API webhook | - |
| `$org['Id']` | API QuickBooks | Numérico | ❌ Solo para display | ❌ NO enviar a API |
| `RealmId` | API QuickBooks | String | ✅ Identificar org QB | ✅ Enviar a API |
| `Name` | API QuickBooks | String | ✅ Display en UI | ✅ Logs/mensajes |

---

## 📌 Reglas Clave

### ✅ HACER:

1. **Siempre usar `$connectionData['organization_id']`** para identificar la organización de DocuCenter
2. **Usar `$org['RealmId']`** para identificar la organización en QuickBooks
3. **Usar `$org['Name']`** para mostrar nombre amigable
4. **Validar que `organizationId` sea string** antes de enviar a API

### ❌ NO HACER:

1. **NUNCA usar `$org['Id']`** como identificador de organización de DocuCenter
2. **NO confundir** ID interno de QuickBooks con SID de Firestore
3. **NO asumir** que ID numérico = organization_id
4. **NO crear** múltiples configuraciones para el mismo RealmId

---

## 🔧 Campos en Diferentes Contextos

### BD DocuCenter (Tabla `connection_applications`):

```
id: 25
organization_id: "Rw8DunJnEnxY1MS2QHVH"  ← SID de Firestore (CORRECTO para API)
application: "acicloud"
settings: {
  Module: "quickbooks",
  Organizations: [{
    Id: 119,                              ← ID de QuickBooks (SOLO display)
    RealmId: "9341454854054771",          ← RealmId de QuickBooks
    Name: "Sandbox Company_US_1"
  }]
}
```

### Firestore (Colección `quickbooks_webhook_processing_config`):

```
DocId: "TMxcK8mY1DwJ0UQQeWma"
RealmId: "9341454854054771"              ← De QuickBooks
OrganizationId: "Rw8DunJnEnxY1MS2QHVH"  ← De DocuCenter (organization_id)
AppId: "DcEUMVoXy51XFdMSQ52L"
DocucenterEnabled: true
```

---

## 🎯 Validación

### Script de Verificación:

```bash
./docs/testing/quickbooks-webhook-cleanup-duplicate.sh
```

### Salida Esperada Después del Fix:

```
✅ No hay configuraciones duplicadas para eliminar
```

### Query Manual en Firestore:

```javascript
// Buscar duplicados por RealmId
db.collection('quickbooks_webhook_processing_config')
  .where('RealmId', '==', '9341454854054771')
  .get()
  .then(snapshot => {
    console.log(`Configs encontradas: ${snapshot.size}`);
    snapshot.forEach(doc => {
      console.log(doc.id, doc.data().OrganizationId);
    });
  });
```

Debe retornar **solo 1 configuración** con `OrganizationId` tipo string.

---

## 📚 Referencias

- **Fix implementado**: [quickbooks-webhook-organizationid-fix.md](./quickbooks-webhook-organizationid-fix.md)
- **Script de limpieza**: [quickbooks-webhook-cleanup-duplicate.sh](../testing/quickbooks-webhook-cleanup-duplicate.sh)
- **Archivos modificados**:
  - `app/Http/Livewire/Admin/Einvoice/Read.php` (líneas 2750-2785)
  - `app/Support/helper.php` (líneas 230-250)
