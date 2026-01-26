# Referencia Rápida - Custom Fields

Esta es una guía de referencia rápida para el estándar de Custom Fields en DocuCenter. Para documentación completa, ver [custom-fields-standardization.md](./custom-fields-standardization.md).

---

## Los 5 Campos Custom

**ESTÁNDAR GLOBAL:**

- Custom_field1: RUC o PASAPORTE según tipo
- Custom_field2: DV (solo clientes panameños)
- Custom_field3: TIPO_RECEPTOR ID (1, 2, 3, 4)
- Custom_field4: Tipo Contribuyente (1, 2) - solo RUC
- Custom_field5: Código ubicación (provincia-dist-correg)

---

## Tipos de Receptor

| ID | CODE | Nombre | Custom_field1 | Custom_field2 | Custom_field4 | Custom_field5 |
|----|------|--------|---------------|---------------|---------------|---------------|
| 1  | 01   | Contribuyente | RUC | DV | 1 o 2 | Ubicación |
| 2  | 02   | Consumidor Final | RUC | DV | 1 o 2 | Ubicación |
| 3  | 04   | Extranjero | PASAPORTE | **null** | **null** | **null** |
| 4  | 03   | Gobierno | RUC | DV | 1 o 2 | Ubicación |

!!! warning "Atención"
    - CODE 03 = ID 4 (Gobierno)
    - CODE 04 = ID 3 (Extranjero)

---

## Reglas Rápidas

### Clientes Panameños (IDs: 1, 2, 4)
```php
[
    'Custom_field1' => '123456-7-890',  // RUC
    'Custom_field2' => '12',            // DV
    'Custom_field3' => 1,               // Contribuyente
    'Custom_field4' => '2',             // Jurídica
    'Custom_field5' => '8-1-1',         // Panamá-Panamá-Bella Vista
]
```

### Clientes Extranjeros (ID: 3)
```php
[
    'Custom_field1' => 'ABC123456',     // PASAPORTE
    'Custom_field2' => null,            // Sin DV
    'Custom_field3' => 3,               // Extranjero
    'Custom_field4' => null,            // Sin tipo contribuyente
    'Custom_field5' => null,            // Sin ubicación
]
```

---

## Helpers Disponibles

### ReceiverTypeHelper

```php
// Conversión CODE ↔ ID
ReceiverTypeHelper::mapCodeToId('04')      // → 3 (Extranjero)
ReceiverTypeHelper::mapIdToCode(3)         // → '04'

// Obtener IDs
ReceiverTypeHelper::getContribuyenteId()   // → 1
ReceiverTypeHelper::getExtranjeroId()      // → 3

// Validaciones
ReceiverTypeHelper::needsTipoContribuyente(3)  // → false
ReceiverTypeHelper::isExtranjero(3)            // → true
ReceiverTypeHelper::needsPasaporte(3)          // → true
```

### CustomFieldsValidator

```php
// Validar campos
$validated = CustomFieldsValidator::validateCustomerFields([
    'ruc' => '123456-7-890',
    'tipo_receptor' => '01',
], 'QuickBooks');

// Verificar consistencia
$report = CustomFieldsValidator::checkConsistency($customFields);

// Auto-corregir
$fixed = CustomFieldsValidator::autoFix($customFields);
```

---

## Integración por Servicio

| Servicio | Auto-detecta CF4 | Mapea CODE→ID | Estado |
|----------|------------------|---------------|--------|
| QuickBooks | Parcial | Sí | OK |
| Lightspeed | Sí | - | OK |
| Shopify | No | - | OK |
| Maxgym | Sí | - | OK |
| Meypar | No | - | OK |
| Kart21 | No | - | OK |
| ACIcloud | No | - | OK |

---

## Casos de Uso Comunes

### Crear Cliente desde QuickBooks

```php
// QuickBooks envía: TIPO_RECEPTOR = "04"
$tipoReceptor = ReceiverTypeHelper::mapCodeToId('04');  // → 3

if (ReceiverTypeHelper::isExtranjero($tipoReceptor)) {
    $customFields = [
        'Custom_field1' => $pasaporte,
        'Custom_field2' => null,
        'Custom_field3' => $tipoReceptor,
        'Custom_field4' => null,
        'Custom_field5' => null,
    ];
}
```

### Validar Cliente Existente

```php
$report = CustomFieldsValidator::checkConsistency([
    'Custom_field1' => 'ABC123',
    'Custom_field2' => null,
    'Custom_field3' => 3,
    'Custom_field4' => null,
    'Custom_field5' => null,
]);

// Resultado:
[
    'is_consistent' => true,
    'issues' => [],
    'receiver_type' => 'Extranjero',
]
```

### Emitir Factura

```php
// En Create.php / CreateFast.php
$this->receptor_tipoContribuyente = in_array($this->receptor_tipo, ['1', '2', '4'])
    ? $this->customer->Custom_field4
    : null;  // null para extranjeros (tipo 3)
```

---

## Flujo de Datos Completo

**Flujo del proceso:**

1. **Integración** (QuickBooks, Shopify, etc.) - Envía CODE='04'
2. **ReceiverTypeHelper** - Mapea CODE → ID ('04' → 3 Extranjero)
3. **CustomFieldsValidator** - Valida y normaliza, aplica reglas de negocio
4. **Base de Datos** - Guarda en Customers_Imp/Exp (Custom_field3=3, Custom_field4=null)
5. **Create.php / CreateFast.php** - Lee y valida (receptor_tipo='3')
6. **Emisión FE** - Envía a PAC (convierte ID → CODE='04')

---

## Troubleshooting Común

### Error: "Extranjero con tipo contribuyente"

**Problema:** Custom_field4 tiene valor para un extranjero

**Solución:**
```php
$fixed = CustomFieldsValidator::autoFix($customFields);
// O manualmente:
if (ReceiverTypeHelper::isExtranjero($tipoReceptor)) {
    $customFields['Custom_field4'] = null;
}
```

### Error: "CODE no coincide con ID"

**Problema:** Confusión entre CODE 03/04 e ID 3/4

**Solución:**
```php
// SIEMPRE usar helper para conversión
$id = ReceiverTypeHelper::mapCodeToId($code);
// NUNCA hacer: $id = (int)$code;
```

### Error: "Contribuyente sin tipo"

**Problema:** Custom_field4 es null para tipo 1, 2 o 4

**Solución:**
```php
if (ReceiverTypeHelper::needsTipoContribuyente($tipoReceptor)) {
    $tipoContribuyente = PanamaRucHelper::detectContributorType($ruc);
}
```

---

## Links Relacionados

- [Documentación Completa](./custom-fields-standardization.md)
- [ReceiverTypeHelper Source](https://github.com/aghabrego/docucenter/blob/main/app/Helpers/ReceiverTypeHelper.php)
- [CustomFieldsValidator Source](https://github.com/aghabrego/docucenter/blob/main/app/Helpers/CustomFieldsValidator.php)

---

**Última actualización:** 2026-01-26  
**Versión:** 1.0
