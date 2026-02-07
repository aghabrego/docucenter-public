# Validaciones MEYPAR Colombia - DocuCenter

## Descripción

Documentación técnica de las validaciones implementadas para la integración con MEYPAR Colombia, incluyendo validaciones de documentos colombianos, límites de base de datos y optimizaciones de totales.

## Validaciones de Documentos de Identificación

### 🏢 NIT Empresarial Colombiano

**Formato Válido**: `XXXXXXXXX-X`
- 9 dígitos numéricos
- Guión separador
- 1 dígito verificador

**Ejemplos**:
- ✅ `900123456-1`
- ✅ `123456789-0`
- ✅ `800123456-7`
- ❌ `12345678` (falta guión y DV)
- ❌ `90012345-12` (DV debe ser 1 dígito)

**Implementación**:
```php
private function validateColombianNIT(string $nit): bool
{
    $pattern = '/^\d{9}-\d{1}$/';
    return preg_match($pattern, $nit) === 1;
}
```

**Resultado**:
- ReceiverType: `'1'` (Contribuyente)
- Custom_field5: Código de ubicación de la organización

### 👤 Cédula Colombiana

**Formato Válido**: Solo números de 6 a 10 dígitos

**Ejemplos**:
- ✅ `123456` (6 dígitos)
- ✅ `79123456` (8 dígitos)
- ✅ `1234567890` (10 dígitos)
- ❌ `12345` (menos de 6 dígitos)
- ❌ `12345678901` (más de 10 dígitos)
- ❌ `79-123-456` (con guiones)

**Implementación**:
```php
private function validateColombianCedula(string $cedula): bool
{
    return preg_match('/^\d{6,10}$/', $cedula) === 1;
}
```

**Resultado**:
- ReceiverType: `'2'` (Persona natural)
- Custom_field5: `null`

### 🛒 Consumidor Final

**Formatos Válidos**:
- `'0-0-0'`
- Cadena vacía: `''`
- Solo espacios: `'   '`
- Documento inválido (auto-corrección)

**Resultado**:
- ReceiverType: `'2'` (Consumidor final)
- Custom_field5: `null`

### ⚙️ Auto-corrección de Documentos

```php
if (!$nitValidation['valid']) {
    Log::warning("NIT/Cédula colombiana inválida en MEYPAR - usando consumidor final", [
        'original_nit' => $nit,
        'customer_name' => array_get($clientData, 'Customer_Bill_Name'),
        'validation_message' => $nitValidation['message']
    ]);
    // Auto-corrección a consumidor final
    $nit = '0-0-0';
    $nitValidation = ['valid' => true, 'type' => 'consumer'];
}
```

**Ventajas**:
- ✅ No interrumpe el procesamiento
- ✅ Logging detallado para auditoría
- ✅ Conversión silenciosa a consumidor final
- ✅ Mantiene la funcionalidad del sistema

## Validaciones de Límites de Base de Datos

### 📏 Campos de Texto

| Campo | Límite BD | Validación Request | Campo BD |
|-------|-----------|-------------------|-----------|
| `documento.numero` | 20 caracteres | `max:20` | InvoiceNumber |
| `documento.emisor.nombre` | 100 caracteres | `max:100` | - |
| `documento.emisor.nit` | 50 caracteres | `max:50` | - |
| `documento.adquiriente.nombre` | 39 caracteres | `max:39` | CustomerName |
| `documento.adquiriente.nit` | 20 caracteres | `max:20` | CustomerID |
| `documento.adquiriente.email` | 100 caracteres | `max:100` | Email |
| `documento.adquiriente.direccion` | 200 caracteres | `max:200` | AddressLine1 |
| `documento.adquiriente.telefono` | 20 caracteres | `max:20` | - |
| `documento.terminal.CodigoExterno` | 20 caracteres | `max:20` | DeviceId |
| `documento.terminal.NombreTerminal` | 100 caracteres | `max:100` | IdStore |
| `documento.items.*.descripcion` | 255 caracteres | `max:255` | Description |
| `documento.items.*.codigoProducto` | 50 caracteres | `max:50` | Item_id, ProductID |
| `documento.items.*.unidadMedida` | 10 caracteres | `max:10` | UnitMeasure |
| `autorizacion_prefijo.PrefijoId` | 10 caracteres | `max:10` | - |
| `autorizacion_prefijo.ResolucionNumero` | 50 caracteres | `max:50` | - |

### Sanitización Automática en el Servicio

```php
private function sanitizeTextField(string $value, int $maxLength, string $fieldName = 'field'): string
{
    $value = trim($value);

    if (strlen($value) > $maxLength) {
        Log::warning("Campo MEYPAR excede límite de BD - truncado", [
            'field' => $fieldName,
            'original_length' => strlen($value),
            'max_length' => $maxLength,
            'original_value' => $value,
            'truncated_value' => substr($value, 0, $maxLength)
        ]);
        return substr($value, 0, $maxLength);
    }

    return $value;
}
```

### 💰 Validaciones Decimales

| Campo | Estructura BD | Validación Request | Regex |
|-------|---------------|-------------------|--------|
| `precioUnitario` | decimal(16,4) | `regex` | `/^\d{1,12}(\.\d{1,4})?$/` |
| `precioTotalFinalDetalle` | decimal(16,4) | `regex` | `/^\d{1,12}(\.\d{1,4})?$/` |
| `importeMedioPago` | decimal(16,4) | `regex` | `/^\d{1,12}(\.\d{1,4})?$/` |
| `totales.total` | decimal(16,4) | `regex` | `/^\d{1,12}(\.\d{1,4})?$/` |
| `cantidad` | decimal(12,2) | `max:9999999999.99` | - |

### Formateo de Números en el Servicio

```php
private function numberFormat(float $number, int $decimals = 4): float
{
    // Validar límites de BD (16,4)
    $maxValue = 999999999999.9999; // 12 dígitos enteros + 4 decimales

    if (abs($number) > $maxValue) {
        Log::warning("Número excede límite de BD decimal(16,4)", [
            'number' => $number,
            'max_allowed' => $maxValue,
            'truncated_to' => $maxValue * ($number < 0 ? -1 : 1)
        ]);
        $number = $maxValue * ($number < 0 ? -1 : 1);
    }

    return round($number, $decimals);
}
```

## Optimización de Totales

### 🎯 Estrategia de Totales Globales

**Antes** (Calculado):
```php
// Se calculaba sumando Net_line de detalles
$subtotalSum += $netLineAmount;
$header->update(['Subtotal' => $subtotalSum]);
```

**Después** (Directo de MEYPAR):
```php
// Usa totales directamente del JSON
'Subtotal' => $this->numberFormat($subtotalGlobal, 2),
'Net_due' => $this->numberFormat($totalGlobal, 2),
```

### 📊 Mapeo de Campos de Totales

| JSON MEYPAR | Campo BD | Descripción |
|-------------|-----------|-------------|
| `totales.subtotal` | `Subtotal` | Subtotal antes de impuestos |
| `totales.total` | `Net_due` | Total final de la factura |
| `totales.impuestos` | `TotalTaxInvupos` | Total de impuestos |
| `totales.descuentos` | `TotalDiscountInvupos` | Total de descuentos |

### 🔧 Mapeo de Líneas de Detalle

| Campo Origen | Campo BD | Cálculo |
|--------------|-----------|---------|
| `precioTotalFinalDetalle` | `Net_line` | Valor directo (para sumar al subtotal) |
| `cantidad × precioUnitario` | `Sub_Total` | Cálculo base sin modificaciones |
| `precioUnitario` | `Unit_Price` | Valor directo |
| `cantidad` | `Quantity` | Valor directo |

## Estructura de Campos Personalizados (CustomersImp)

### 🗂️ Sistema de Custom Fields

| Campo | Tipo Empresa | Tipo Persona | Consumidor Final |
|-------|--------------|--------------|------------------|
| `Custom_field1` | NIT validado | Cédula validada | `'0-0-0'` |
| `Custom_field2` | `'00'` | `'00'` | `'00'` |
| `Custom_field3` | `null` | `null` | `null` |
| `Custom_field4` | `'1'` | `'2'` | `'2'` |
| `Custom_field5` | Código ubicación org | `null` | `null` |

### ReceiverType Mapping

```php
switch ($nitValidation['type']) {
    case 'company':
        $receiverType = '1'; // Contribuyente
        $customField5 = $organization->codigo_provincia_distrito ?? null;
        break;
    case 'person':
    case 'consumer':
    default:
        $receiverType = '2'; // Persona natural/Consumidor final
        $customField5 = null;
        break;
}
```

## Mensajes de Error Específicos

### 📝 Validaciones de Request

**Límites de Caracteres**:
- `"El número de documento no puede exceder 20 caracteres"`
- `"El nombre del cliente no puede exceder 39 caracteres"`
- `"El NIT del cliente no puede exceder 20 caracteres"`

**Formatos Decimales**:
- `"El precio unitario debe ser un decimal válido con máximo 4 decimales"`
- `"El precio total debe ser un decimal válido con máximo 4 decimales"`
- `"El importe del medio de pago debe ser un decimal válido con máximo 4 decimales"`

**Campos Requeridos**:
- `"Los datos del documento son requeridos"`
- `"El nombre del adquiriente es requerido"`
- `"Los items del documento son requeridos"`

### 🔍 Logging de Validaciones

**Documento Inválido**:
```
NIT/Cédula colombiana inválida en MEYPAR - usando consumidor final
{
  "original_nit": "123-INVALID-456",
  "customer_name": "Cliente Test",
  "validation_message": "Formato de documento inválido..."
}
```

**Campo Truncado**:
```
Campo MEYPAR excede límite de BD - truncado
{
  "field": "CustomerName",
  "original_length": 45,
  "max_length": 39,
  "original_value": "Nombre Muy Largo Que Excede El Límite De BD",
  "truncated_value": "Nombre Muy Largo Que Excede El Límite"
}
```

**Número Truncado**:
```
Número excede límite de BD decimal(16,4)
{
  "number": 9999999999999.99999,
  "max_allowed": 999999999999.9999,
  "truncated_to": 999999999999.9999
}
```

## Beneficios de la Implementación

### ✅ Robustez
- **Validación automática** de documentos colombianos
- **Auto-corrección** para datos inválidos
- **Límites exactos** según estructura de BD
- **Logging detallado** para auditoría

### ⚡ Performance
- **Totales directos** de MEYPAR (no calculados)
- **Validación en una pasada** de documentos
- **Formateo optimizado** con límites exactos
- **Sin operaciones de actualización** innecesarias

### 🔧 Mantenibilidad
- **Mensajes claros** en español
- **Logging estructurado** para debugging
- **Separación de responsabilidades** (Request vs Service)
- **Documentación completa** de validaciones

### 🎯 Precisión
- **Compatibilidad exacta** con estructura de BD
- **Validaciones específicas** para Colombia
- **Formato decimal preciso** (16,4 y 18,4)
- **Coherencia de datos** entre request y BD

## Testing de Validaciones

### 🧪 Casos de Prueba Documentos

**NITs Válidos**:
- `900123456-1` → Empresa
- `800234567-0` → Empresa
- `123456789-7` → Empresa

**Cédulas Válidas**:
- `79123456` → Persona
- `1234567890` → Persona
- `123456` → Persona

**Casos Inválidos (Auto-corrección)**:
- `123-INVALID` → Consumidor final
- `900123456` → Consumidor final (falta DV)
- `''` → Consumidor final

### 📊 Casos de Prueba Límites

**Campos de Texto**:
- Nombre 40 caracteres → Truncado a 39
- Descripción 300 caracteres → Truncada a 255
- Email 150 caracteres → Truncado a 100

**Decimales**:
- `1234567890123.12345` → Truncado a límites BD
- `-999.9999` → Válido
- `0.1` → Válido

Esta implementación asegura que todos los datos de MEYPAR Colombia sean procesados correctamente con validaciones robustas y compatibilidad total con la estructura de base de datos de DocuCenter.
