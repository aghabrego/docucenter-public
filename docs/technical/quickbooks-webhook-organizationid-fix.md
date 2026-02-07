# Fix: QuickBooks Webhook OrganizationId Duplicado

## Problema Detectado

Se identificaron configuraciones duplicadas de webhook en Firestore para el mismo `RealmId`, causadas por el uso inconsistente de identificadores de organización.

### Causa Raíz

El código estaba enviando **dos tipos diferentes de ID de organización** a la API de webhooks:

1. **ID numérico de QuickBooks** (`$org['Id']`) - Ej: `119`
2. **String ID de DocuCenter** (`$connectionData['organization_id']`) - Ej: `Rw8DunJnEnxY1MS2QHVH`

### Ubicaciones del Problema

#### 1. En `app/Http/Livewire/Admin/Einvoice/Read.php`

**ANTES** (Líneas 2750-2777):
```php
// Si hay organizaciones y se seleccionó una específica
if (isset($settings['Organizations']) && is_array($settings['Organizations'])) {
    if (!empty($this->selectedOrganizationRealmId)) {
        foreach ($settings['Organizations'] as $org) {
            if (isset($org['RealmId']) && $org['RealmId'] === $this->selectedOrganizationRealmId) {
                $targetRealmId = $org['RealmId'];
                $targetOrganizationId = $org['Id'] ?? null; // ID NUMÉRICO DE QUICKBOOKS
                $targetOrganizationName = $org['Name'] ?? 'N/A';
                break;
            }
        }
    }
}
```

**DESPUÉS**:
```php
// IMPORTANTE: Siempre usar el organization_id de DocuCenter (sid de Firestore)
// NO usar el Id de QuickBooks que es numérico
$targetOrganizationId = $connectionData['organization_id']; // SID DE DOCUCENTER

// Si hay organizaciones y se seleccionó una específica
if (isset($settings['Organizations']) && is_array($settings['Organizations'])) {
    if (!empty($this->selectedOrganizationRealmId)) {
        foreach ($settings['Organizations'] as $org) {
            if (isset($org['RealmId']) && $org['RealmId'] === $this->selectedOrganizationRealmId) {
                $targetRealmId = $org['RealmId'];
                $targetOrganizationName = $org['Name'] ?? 'N/A';
                break;
            }
        }
    }
}
```

#### 2. En `app/Support/helper.php`

**ANTES** (Líneas 238-246):
```php
// Buscar el organizationId en el array Organizations
$orgId = null;
if (isset($settings['Organizations']) && is_array($settings['Organizations'])) {
    foreach ($settings['Organizations'] as $org) {
        if (isset($org['RealmId']) && $org['RealmId'] === $realmId) {
            $orgId = $org['Id'] ?? null; // ID NUMÉRICO DE QUICKBOOKS
            break;
        }
    }
}
```

**DESPUÉS**:
```php
// IMPORTANTE: Usar el organization_id de DocuCenter (sid de Firestore)
// NO usar el Id de QuickBooks que es numérico
$orgId = $connectionData['organization_id'] ?? null; // SID DE DOCUCENTER
```

## Consecuencias del Bug

### Ejemplo Real Detectado

Para `RealmId: 9341454854054771`:

| Config | Doc ID | OrganizationId | Tipo | Creado | Estado |
|--------|--------|----------------|------|--------|--------|
| #1 | TMxcK8mY1DwJ0UQQeWma | Rw8DunJnEnxY1MS2QHVH | String ID | 2026-01-07 | Enabled |
| #2 | z8ywLjyJKX4FzQnWBWYY | 119 | Numérico | 2026-01-07 | Enabled |

### Problemas Causados

1. **Configuraciones duplicadas**: Múltiples documentos en Firestore para el mismo webhook
2. **Inconsistencia de datos**: No se puede identificar unívocamente la organización
3. **Confusión en búsquedas**: `119` no es un ID válido de organización en DocuCenter
4. **Posibles conflictos**: Ambas configuraciones activas pueden causar comportamiento impredecible

## Solución Implementada

### Cambios en el Código

1. **[app/Http/Livewire/Admin/Einvoice/Read.php](../../app/Http/Livewire/Admin/Einvoice/Read.php)**
   - Líneas 2750-2785
   - Siempre usar `$connectionData['organization_id']`
   - Eliminada dependencia de `$org['Id']`

2. **[app/Support/helper.php](../../app/Support/helper.php)**
   - Líneas 230-250
   - Usar `$connectionData['organization_id']` en lugar de buscar en array
   - Simplificado el código eliminando loop innecesario

### Validación

El valor correcto siempre es:
```php
$targetOrganizationId = $connectionData['organization_id'];
```

Donde:
- `$connectionData` = Registro de `connection_applications` en BD
- `organization_id` = Campo que contiene el `sid` de Firestore (String ID de DocuCenter)

## Script de Limpieza

Se creó un script para identificar y limpiar configuraciones duplicadas:

**Ubicación**: `docs/testing/quickbooks-webhook-cleanup-duplicate.sh`

### Uso

```bash
# Ejecutar análisis y limpieza interactiva
./docs/testing/quickbooks-webhook-cleanup-duplicate.sh
```

### Funcionalidades

1. Identifica configuraciones con mismo `RealmId`
2. Detecta IDs numéricos vs String IDs
3. Muestra información detallada de cada configuración
4. Recomienda cuál eliminar
5. Permite confirmar antes de eliminar
6. Elimina configuraciones duplicadas

### Salida del Script

```
Buscando configuraciones duplicadas por RealmId...

RealmId: 9341454854054771 tiene 2 configuraciones:

  Config #1:
    Doc ID: TMxcK8mY1DwJ0UQQeWma
    OrganizationId: Rw8DunJnEnxY1MS2QHVH - STRING ID (CORRECTO)
    Created: 2026-01-07
    Updated: 2026-01-07
    Estado: Enabled

  Config #2:
    Doc ID: z8ywLjyJKX4FzQnWBWYY
    OrganizationId: 119 - NUMÉRICO (INCORRECTO)
    Created: 2026-01-07
    Updated: 2026-01-12
    Estado: Enabled

  RECOMENDACIÓN: Eliminar configuración(es) con ID numérico


RESUMEN:

Total de RealmIds con duplicados: 1
Configuraciones marcadas para eliminar: 1

CONFIGURACIONES A ELIMINAR:

  • Doc ID: z8ywLjyJKX4FzQnWBWYY
    RealmId: 9341454854054771
    OrganizationId: 119

¿Deseas eliminar estas configuraciones? (s/n):
```

## Verificación Post-Fix

### 1. Verificar que no hay más duplicados

```bash
./docs/testing/quickbooks-webhook-cleanup-duplicate.sh
```

Debe mostrar:
```
No hay configuraciones duplicadas para eliminar
```

### 2. Verificar estructura correcta en Firestore

Cada documento en `quickbooks_webhook_processing_config` debe tener:

```json
{
  "RealmId": "9341454854054771",
  "OrganizationId": "Rw8DunJnEnxY1MS2QHVH",  // String ID
  "DocucenterEnabled": true,
  "AppId": "DcEUMVoXy51XFdMSQ52L",
  "CreatedAt": "2026-01-07T...",
  "UpdatedAt": "2026-01-12T..."
}
```

### 3. Probar desde la interfaz

1. Ir a E-Invoices → Configuración de Webhook
2. Habilitar/Deshabilitar webhook
3. Verificar en Firestore que solo se crea/actualiza UNA configuración
4. Confirmar que `OrganizationId` es string (no numérico)

## Prevención de Recurrencia

### Reglas de Código

1. **NUNCA usar `$org['Id']`** de QuickBooks para identificar organizaciones de DocuCenter
2. **SIEMPRE usar `$connectionData['organization_id']`** para webhooks
3. **Validar tipo de dato**: `OrganizationId` debe ser string, no numérico

### Code Review Checklist

- [ ] ¿Se está usando `organization_id` de la conexión?
- [ ] ¿NO se está usando `$org['Id']` de QuickBooks?
- [ ] ¿Se valida que `organizationId` sea string?
- [ ] ¿Se previene la creación de duplicados?

## Referencias

- **Issue**: QuickBooks Webhook OrganizationId Duplicado
- **Archivos modificados**:
  - `app/Http/Livewire/Admin/Einvoice/Read.php`
  - `app/Support/helper.php`
- **Script creado**:
  - `docs/testing/quickbooks-webhook-cleanup-duplicate.sh`
- **Fecha**: 2026-01-12

## Lecciones Aprendidas

1. **Claridad en identificadores**: Distinguir entre ID de sistema externo (QuickBooks) vs ID interno (DocuCenter)
2. **Validación de tipo**: Numeric vs String puede causar duplicados silenciosos
3. **Scripts de análisis**: Esenciales para detectar problemas de datos
4. **Documentación temprana**: Previene confusión futura sobre qué ID usar
