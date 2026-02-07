# API Check RUC - Campo Type (Tipo de Contribuyente)

## Descripción

Se ha agregado el campo `type` (tipo de contribuyente) a la API de consulta RUC que detecta automáticamente si el RUC corresponde a una persona natural o jurídica basándose en el patrón del número.

## Endpoint

```
GET /api/v1/checkRuc
```

## Respuesta Exitosa

```json
{
    "success": true,
    "message": "Consulta de RUC exitosa",
    "data": {
        // Respuesta del PAC Alanube
    },
    "ruc_consulted": "8-123-456",
    "country": "PA",
    "type": 1,
    "pac_name": "alanube"
}
```

## Campo `type` (Tipo de Contribuyente)

### Valores Permitidos

| Valor | Descripción | Formato RUC | Ejemplo |
|-------|-------------|-------------|---------|
| `1` | **Natural** (Persona física) | `[Provincia]-[Tomo]-[Asiento]` | `8-123-456`, `PE-789-012` |
| `2` | **Jurídico** (Persona jurídica/empresa) | `[Registro]-[Tipo]-[Número]` | `155757563-2-2024`, `1234567-1-123` |

### Reglas de Detección Automática

#### RUC de Persona Natural (type: 1)
**Patrones oficiales soportados:**
- **Regular**: `[provincia]-[libro]-[tomo]` (ej: `8-1234-56789`)
- **Panameño extranjero**: `PE-[libro]-[tomo]` (ej: `PE-123-456`)
- **Extranjero**: `E-[libro]-[tomo]` (ej: `E-123-456`)
- **Naturalizado**: `N-[libro]-[tomo]` (ej: `N-123-456`)
- **Antes vigencia**: `[provincia]AV-[libro]-[tomo]` (ej: `8AV-123-456`)
- **Población indígena**: `[provincia]PI-[libro]-[tomo]` (ej: `12PI-123-456`)

**Provincias válidas**: 1-13 (Bocas del Toro a Panamá Oeste)

#### RUC de Persona Jurídica (type: 2)
- **Formato empresa**: `[registro]-[tipo]-[número]` (ej: `155-41-85123`)
- **Cualquier formato que no coincida con patrones de persona natural**
- **Casos ambiguos resueltos**: RUCs como `1-2-3` o `12-34-567` que parecen provincia válida pero son empresas

#### Casos Especiales
- **RUC vacío o `0-0-0`**: Se considera tipo `1` (Natural)
- **Casos ambiguos**: Lógica heurística detecta empresas con formato aparentemente natural
- **Formato no reconocido**: Se asigna tipo `2` (Jurídico) por defecto para evitar errores PAC

## Implementación Técnica

### Servicio: AlanubeService

```php
/**
 * Detectar tipo de contribuyente basado en el patrón del RUC panameño
 * 
 * @param string $ruc
 * @return int 1=Natural, 2=Jurídico
 */
public function detectContributorType(string $ruc): int
{
    $ruc = trim($ruc);
    
    if (empty($ruc) || $ruc === '0-0-0') {
        return 1; // Consumidor Final = Natural
    }

    // RUC de persona natural: [Provincia]-[Tomo]-[Asiento]
    $personPattern = '/^(PE|[1-9]|1[0-3])-\d{1,6}-\d{1,6}$/';
    
    if (preg_match($personPattern, $ruc)) {
        return 1; // Natural (Persona física)
    }

    // RUC de empresa: [Registro Público]-[Tipo de Sociedad]-[Número de Actividad]
    $companyPattern = '/^\d{1,9}-\d{1,2}-\d{1,4}$/';
    
    if (preg_match($companyPattern, $ruc)) {
        return 2; // Jurídico (Persona jurídica/empresa)
    }

    // Por defecto jurídico para evitar errores PAC
    return 2;
}
```

### Controller: FeController

El campo `type` se incluye automáticamente en todas las respuestas exitosas de `checkRuc`.

## Casos de Prueba

### RUC de Persona Natural

**Request:**
```http
GET /api/v1/checkRuc?ruc=8-123-456
```

**Response:**
```json
{
    "success": true,
    "message": "Consulta de RUC exitosa",
    "data": { /* datos del PAC */ },
    "ruc_consulted": "8-123-456",
    "country": "PA",
    "type": 1,
    "pac_name": "alanube"
}
```

### RUC de Persona Jurídica

**Request:**
```http
GET /api/v1/checkRuc?ruc=155757563-2-2024
```

**Response:**
```json
{
    "success": true,
    "message": "Consulta de RUC exitosa",
    "data": { /* datos del PAC */ },
    "ruc_consulted": "155757563-2-2024",
    "country": "PA",
    "type": 2,
    "pac_name": "alanube"
}
```

## Compatibilidad

- ✅ **Compatible hacia atrás**: El campo se agrega sin afectar campos existentes
- ✅ **Detección automática**: No requiere parámetros adicionales en el request
- ✅ **Validación PAC**: Ayuda a prevenir errores de validación PAC por tipo incorrecto
- ✅ **Consistente**: Usa los mismos patrones de validación que otros servicios del sistema

## Beneficios

1. **Automatización**: Determina automáticamente el tipo sin intervención manual
2. **Prevención de errores**: Evita errores PAC por configuración incorrecta de tipo
3. **Consistencia**: Usa patrones de validación estandarizados en todo el sistema
4. **Cumplimiento**: Asegura cumplimiento con formatos oficiales panameños

## Referencias

- [Validación de RUC en MaxgymService](../validations/maxgym-ruc-validation.md)
- [Documentación API CheckRuc](../api/fe/checkruc-api.md)
- [Patrones RUC Panamá](../validations/createSale-ruc-validation.md)

---

**Fecha**: Diciembre 2024  
**Estado**: Implementado ✅  
**Versión API**: v1
