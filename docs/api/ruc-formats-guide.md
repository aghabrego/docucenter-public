# Guía de Formatos de RUC en Panamá

## Tipos de RUC Soportados por la API

La API de consulta de RUC de DocuCenter soporta todos los formatos oficiales de RUC utilizados en Panamá:

### 1. 🧑 Persona Natural (Cédula)
**Formato**: `D-DDD-DDD`
**Ejemplo**: `8-123-456`
**Descripción**: RUC basado en la cédula de identidad panameña

```bash
curl -X GET "https://tu-dominio.com/api/v1/fe/check_ruc/8-123-456" \
  -H "Authorization: Bearer tu-token" \
  -H "Accept: application/json"
```

### 2. Persona Extranjera
**Formato**: `PE-D-DDDDDD`
**Ejemplo**: `PE-8-123456`
**Descripción**: RUC para personas extranjeras residentes

```bash
curl -X GET "https://tu-dominio.com/api/v1/fe/check_ruc/PE-8-123456" \
  -H "Authorization: Bearer tu-token" \
  -H "Accept: application/json"
```

### 3. Empresa (RUC Jurídico)
**Formato**: `DDDDDDDDD-D-YYYY`
**Ejemplo**: `155750453-2-2024`
**Descripción**: RUC empresarial que incluye el año de constitución
**Desglose**:
- `155750453`: Número de identificación de la empresa
- `2`: Dígito verificador
- `2024`: Año de constitución/registro

```bash
curl -X GET "https://tu-dominio.com/api/v1/fe/check_ruc/155750453-2-2024" \
  -H "Authorization: Bearer tu-token" \
  -H "Accept: application/json"
```

## Validación Automática

La API valida automáticamente todos estos formatos usando la expresión regular:
```regex
/^[0-9\-PE]+$/
```

### Ejemplos Válidos
- `8-123-456` (persona natural)
- `PE-8-123456` (persona extranjera)
- `155750453-2-2024` (empresa)
- `8-123-456789` (formato extendido)

### Ejemplos Inválidos
- `123456789` (sin guiones)
- `8-123-456@` (caracteres especiales)
- `ABC-123-456` (letras no permitidas excepto PE)
- `8.123.456` (puntos en lugar de guiones)

## 🧪 Testing de Formatos

### Script Automatizado
El script de testing incluye casos para todos los formatos:

```bash
# Ejecutar todas las pruebas
./scripts/test-check-ruc.sh

# Casos específicos incluidos:
# - Persona natural: 8-123-456
# - Persona extranjera: PE-8-123456  
# - Empresa: 155750453-2-2024
# - Formatos inválidos para validación
```

### Comando Artisan
```bash
# Probar persona natural
php artisan test:check-ruc 1 "8-123-456"

# Probar persona extranjera  
php artisan test:check-ruc 1 "PE-8-123456"

# Probar empresa
php artisan test:check-ruc 1 "155750453-2-2024"
```

## Respuesta de la API

La API retorna la misma estructura para todos los tipos de RUC:

```json
{
  "success": true,
  "message": "Consulta de RUC exitosa",
  "data": {
    "ruc": "155750453-2-2024",
    "digit": "2",
    "valid": true,
    "company_name": "Empresa Ejemplo S.A.",
    "status": "active",
    "type": "juridico"
  },
  "ruc_consulted": "155750453-2-2024",
  "country": "PA",
  "pac_type": "alanube_panama"
}
```

## Casos de Uso por Tipo

### Persona Natural
- Facturación a consumidores finales
- Servicios profesionales independientes  
- Pequeños comercios individuales

### Persona Extranjera
- Residentes extranjeros con actividad económica
- Inversionistas foráneos
- Profesionales expatriados

### Empresa
- Sociedades anónimas
- Sociedades de responsabilidad limitada
- Corporaciones y grandes empresas
- Entidades jurídicas en general

## Integración en Sistemas

### Validación en Frontend
```javascript
function validateRucFormat(ruc) {
  const rucPattern = /^[0-9\-PE]+$/;
  
  if (!rucPattern.test(ruc)) {
    return {
      valid: false,
      message: 'Formato de RUC inválido'
    };
  }
  
  if (ruc.length < 3 || ruc.length > 50) {
    return {
      valid: false,
      message: 'Longitud de RUC inválida'
    };
  }
  
  return { valid: true };
}

// Ejemplos de uso
console.log(validateRucFormat('8-123-456'));        // válido
console.log(validateRucFormat('PE-8-123456'));      // válido  
console.log(validateRucFormat('155750453-2-2024')); // válido
console.log(validateRucFormat('123456789'));        // inválido
```

### Validación en Backend PHP
```php
function validateRucFormat(string $ruc): array
{
    if (!preg_match('/^[0-9\-PE]+$/', $ruc)) {
        return [
            'valid' => false,
            'message' => 'Formato de RUC inválido'
        ];
    }
    
    if (strlen($ruc) < 3 || strlen($ruc) > 50) {
        return [
            'valid' => false,
            'message' => 'Longitud de RUC inválida'
        ];
    }
    
    return ['valid' => true];
}
```

## Referencias

- **API Principal**: `docs/api/check-ruc-api.md`
- **Testing**: `docs/testing/README.md`
- **Implementación**: `docs/api/check-ruc-implementation-summary.md`
- **Scripts**: `scripts/test-check-ruc.sh`
- **Comando**: `app/Console/Commands/Testing/TestCheckRucCommand.php`
