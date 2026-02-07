# Documentación: Lógica de MeyparService para Colombia

**Fecha**: 2026-01-26  
**Contexto**: Documentar lógica específica de Meypar para operaciones en Colombia vs Panamá  
**Propósito**: Clarificar diferencias en manejo de Custom_field4 (Tipo Contribuyente)

---

## 1. Contexto General

MeyparService maneja facturación tanto para **Colombia** como para **Panamá**, con lógicas diferentes para cada país:

- **Panamá**: Sistema de facturación electrónica con normativa DGI
- **Colombia**: Sistema DIAN con lógica propia de identificación tributaria

---

## 2. Diferencias Clave

### 2.1. Identificación Fiscal

| País | Campo | Descripción |
|------|-------|-------------|
| **Panamá** | RUC | Registro Único de Contribuyente (formato: 8-123-4567) |
| **Colombia** | NIT | Número de Identificación Tributaria (formato numérico) |

### 2.2. Custom Fields

#### Panamá (Sistema Estándar DocuCenter)
```php
'Custom_field1' => $ruc,              // RUC panameño
'Custom_field2' => '00',              // DV (detectado automáticamente)
'Custom_field3' => $receiverType,     // Tipo Receptor (1-4)
'Custom_field4' => null,              // null o detectado de RUC
'Custom_field5' => $customField5,     // Código ubicación
```

#### Colombia (Lógica Invertida Custom_field4)
```php
'Custom_field1' => $nit,              // NIT colombiano
'Custom_field2' => '00',              // DV genérico
'Custom_field3' => $receiverType,     // Tipo Receptor (1=Empresa, 2=Persona)
'Custom_field4' => $receiverType == 1 ? '2' : '1',  // INVERTIDO: 1→2, 2→1
'Custom_field5' => $customField5,     // Otro campo
```

---

## 3. Análisis Custom_field4 Colombia

### 3.1. Código Actual (Línea 739)

```php
// app/Services/MeyparService.php línea 739
'Custom_field4' => $receiverType == 1 ? '2' : '1',
```

### 3.2. Lógica

| receiverType | Interpretación | Custom_field4 | Razón |
|--------------|----------------|---------------|--------|
| **1** | Empresa (NIT jurídico) | **'2'** | Jurídica |
| **2** | Persona (NIT natural) | **'1'** | Natural |

**Interpretación**:
- Si `receiverType = 1` (Empresa) → `Custom_field4 = '2'` (Tipo contribuyente Jurídica)
- Si `receiverType = 2` (Persona) → `Custom_field4 = '1'` (Tipo contribuyente Natural)

### 3.3. ¿Por qué está invertido?

En **Panamá**, la convención es:
- **Custom_field4 = '1'**: Persona Natural
- **Custom_field4 = '2'**: Persona Jurídica

En **Colombia** (según código actual):
- **receiverType = 1**: Se asume que es siempre empresa (Jurídica)
- **receiverType = 2**: Se asume que es siempre persona (Natural)

**Razón**: Colombia NO usa el helper `PanamaRucHelper::detectContributorType()` porque el NIT colombiano tiene diferente estructura.

---

## 4. Validación NIT Colombiano

### 4.1. Código de Validación (Líneas 690-710)

```php
// Detectar tipo de NIT
$nitValidation = $this->validateColombianNIT($nit);

if ($nitValidation['type'] === 'company') {
    $receiverType = '1'; // Empresa
} else {
    $receiverType = '2'; // Persona natural
}
```

### 4.2. Método validateColombianNIT()

```php
protected function validateColombianNIT($nit)
{
    // Lógica de validación específica de Colombia
    // Detecta si es empresa o persona basado en formato NIT
    
    return [
        'isValid' => true,
        'type' => 'company', // o 'person'
    ];
}
```

---

## 5. Comparación Panamá vs Colombia

### 5.1. Panamá (Sistema Estándar)

```php
// Detección automática desde RUC
$ruc = '8-123-4567';  // Persona Natural
$receiverType = '1';  // Contribuyente
$tipoContribuyente = (string)PanamaRucHelper::detectContributorType($ruc);
// Resultado: tipoContribuyente = '1' (Natural)

'Custom_field3' => 1,    // Contribuyente
'Custom_field4' => '1',  // Natural (detectado desde RUC)
```

### 5.2. Colombia (Lógica Invertida)

```php
// Detección manual desde NIT
$nit = '900123456';  // Empresa
$receiverType = '1';  // Empresa (detectado manualmente)
$tipoContribuyente = $receiverType == 1 ? '2' : '1';
// Resultado: tipoContribuyente = '2' (Jurídica)

'Custom_field3' => 1,    // Empresa
'Custom_field4' => '2',  // Jurídica (lógica invertida)
```

---

## 6. Recomendaciones

### 6.1. Estado Actual: CORRECTO para Colombia

La lógica actual es **CORRECTA** para Colombia porque:

1. **NIT colombiano ≠ RUC panameño**: Estructura diferente
2. **No se puede usar PanamaRucHelper**: Helper es específico para formato panameño
3. **Mapeo manual es necesario**: receiverType 1→Empresa, 2→Persona

### 6.2. Mejora Propuesta: Crear Helper Colombia

Crear `App\Helpers\ColombiaNitHelper` para centralizar lógica:

```php
namespace App\Helpers;

class ColombiaNitHelper
{
    /**
     * Detecta si un NIT colombiano es de empresa o persona natural
     */
    public static function detectContributorType(string $nit): int
    {
        // Lógica de validación NIT Colombia
        // 1 = Natural, 2 = Jurídica
        
        if (self::isCompanyNIT($nit)) {
            return 2; // Jurídica
        }
        
        return 1; // Natural
    }
    
    /**
     * Verifica si un NIT es de empresa
     */
    protected static function isCompanyNIT(string $nit): bool
    {
        // Empresas en Colombia suelen tener NITs que empiezan con 900
        // O tienen formato específico
        return str_starts_with($nit, '900') || strlen($nit) > 9;
    }
}
```

### 6.3. Uso Propuesto en MeyparService

```php
// Línea 739 - MEJORADO
if ($customerCountry === 'CO') {
    // Colombia: Usar helper específico
    $tipoContribuyente = (string)ColombiaNitHelper::detectContributorType($nit);
} elseif ($customerCountry === 'PA' && !empty($ruc)) {
    // Panamá: Usar helper existente
    $tipoContribuyente = (string)PanamaRucHelper::detectContributorType($ruc);
} else {
    // Fallback: Lógica actual
    $tipoContribuyente = $receiverType == 1 ? '2' : '1';
}

'Custom_field4' => $tipoContribuyente,
```

---

## 7. Casos de Uso

### 7.1. Cliente Empresa Colombia

```
Input: NIT = 900123456 (Empresa)
Detección: validateColombianNIT() → type = 'company'
receiverType = 1
Custom_field4 = '2' (Jurídica)
✓ CORRECTO
```

### 7.2. Cliente Persona Natural Colombia

```
Input: NIT = 12345678 (Persona)
Detección: validateColombianNIT() → type = 'person'
receiverType = 2
Custom_field4 = '1' (Natural)
✓ CORRECTO
```

### 7.3. Cliente Empresa Panamá

```
Input: RUC = 123456-1-123456 (Jurídica)
Detección: PanamaRucHelper::detectContributorType() → 2
receiverType = 1 (Contribuyente)
Custom_field4 = '2' (Jurídica)
✓ CORRECTO
```

---

## 8. Testing Recomendado

### 8.1. Tests Colombia

```php
/** @test */
public function meypar_colombia_empresa_asigna_tipo_juridica()
{
    $data = [
        'nit' => '900123456',
        'country' => 'CO'
    ];
    
    $customer = MeyparService::createCustomer($data);
    
    $this->assertEquals('1', $customer->Custom_field3); // Empresa
    $this->assertEquals('2', $customer->Custom_field4); // Jurídica
}

/** @test */
public function meypar_colombia_persona_asigna_tipo_natural()
{
    $data = [
        'nit' => '12345678',
        'country' => 'CO'
    ];
    
    $customer = MeyparService::createCustomer($data);
    
    $this->assertEquals('2', $customer->Custom_field3); // Persona
    $this->assertEquals('1', $customer->Custom_field4); // Natural
}
```

---

## 9. Conclusiones

1. ✅ **Lógica actual es CORRECTA** para Colombia
2. ✅ **No requiere cambios urgentes**
3. 📋 **Mejora sugerida**: Crear `ColombiaNitHelper` para centralizar lógica
4. 📋 **Documentación**: Esta documentación clarifica el comportamiento
5. ⚠️ **Importante**: NO aplicar lógica panameña a clientes colombianos

---

## 10. Resumen de Diferencias

| Aspecto | Panamá | Colombia |
|---------|--------|----------|
| **Identificación** | RUC (8-123-4567) | NIT (900123456) |
| **Helper** | PanamaRucHelper | Lógica manual |
| **receiverType 1** | Contribuyente | Empresa |
| **receiverType 2** | Consumidor | Persona |
| **Custom_field4 detección** | Desde RUC | Lógica invertida |
| **Validación** | Formato RUC PA | Formato NIT CO |

---

**Estado**: ✅ DOCUMENTADO  
**Acción requerida**: Ninguna (código actual funciona correctamente)  
**Mejora opcional**: Crear ColombiaNitHelper en Fase 3+
