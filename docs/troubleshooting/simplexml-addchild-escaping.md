# SimpleXML addChild() Escaping - Guía Crítica

## Problema: "unterminated entity reference"

```
SimpleXMLElement::addChild(): unterminated entity reference  Arts Tower, Av. Vasco Núñez de Balboa
```

## Causa Raíz

**SimpleXML NO escapa automáticamente valores en `addChild()`**. 

Cuando pasas un valor a `addChild()` que contiene caracteres especiales XML (`&`, `<`, `>`, `"`, `'`), SimpleXML intenta interpretarlos como XML válido. Si el valor contiene estos caracteres sin escapar, genera el error "unterminated entity reference".

## Solución Correcta

**SIEMPRE usar `htmlspecialchars()` CON opciones XML ANTES de pasar a `addChild()`:**

```php
private function safeXmlValue(?string $value): string
{
    // Paso 1: Sanitizar caracteres de control (invalidos en XML)
    $sanitized = $this->sanitizeForXml($value);
    
    // Paso 2: Escapar caracteres especiales XML
    // ENT_QUOTES: escapa comillas dobles y simples
    // ENT_XML1: usa la definición XML 1.0 de caracteres especiales
    return htmlspecialchars($sanitized, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// Uso:
$addressInfo->addChild('Address', $this->safeXmlValue($gDatRec['dDirecRec']));
```

## Qué Hace htmlspecialchars()

| Carácter | Original | Escapado |
|----------|----------|----------|
| Ampersand | `&` | `&amp;` |
| Menor que | `<` | `&lt;` |
| Mayor que | `>` | `&gt;` |
| Comilla doble | `"` | `&quot;` |
| Comilla simple | `'` | `&#039;` |

## Opciones Importantes

- `ENT_QUOTES`: Escapa AMBOS tipos de comillas (doble y simple)
- `ENT_XML1`: Usa definición de caracteres especiales de XML 1.0
- `'UTF-8'`: Codificación (debe coincidir con archivo XML)

## Diferencia Entre

### ❌ INCORRECTO - Sin escapado

```php
$addressInfo->addChild('Address', $value);  // "Arts Tower, Av. Vasco Núñez de Balboa"
// Error: unterminated entity reference
```

### ✅ CORRECTO - Con escapado

```php
$addressInfo->addChild('Address', 
    htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8')
);
// Resultado: "Arts Tower, Av. Vasco Núñez de Balboa"
```

## Proceso en DigifactXmlBuilder

1. **sanitizeForXml()**: Remueve caracteres de control (0x00-0x08, etc.)
2. **safeXmlValue()**: Aplica htmlspecialchars() para escapar caracteres XML
3. **addChild()**: Recibe valor correctamente escapado, sin errores

## Caracteres que Causan Problemas

- `&` en "M&M" → Necesita ser `&amp;`
- `<` en valores numéricos → Necesita ser `&lt;`
- `"` en direcciones → Necesita ser `&quot;`
- `'` en nombres → Necesita ser `&#039;`
- Acentos como `ñ`, `á`, `é` → Válidos en UTF-8, NO necesitan escaparse

## Implementación en Digifact v2.0.7

**Archivo**: `app/Services/DigifactXmlBuilder.php`

**Función safeXmlValue()** (línea ~820-835):
```php
private function safeXmlValue(?string $value): string
{
    $sanitized = $this->sanitizeForXml($value);
    return htmlspecialchars($sanitized, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
```

**Uso en buildBuyerAddressInfo()** (línea ~399):
```php
if (!empty($gDatRec['dDirecRec'])) {
    $addressInfo->addChild('Address', $this->safeXmlValue($gDatRec['dDirecRec']));
}
```

## Testing

Para verificar que el escaping funciona correctamente:

```bash
# Crear factura con dirección que contenga caracteres especiales
# Ejemplo: "Arts Tower, Av. Vasco Núñez de Balboa"

# Si ve error "unterminated entity reference", significa que 
# safeXmlValue() NO está siendo usada

# Si funciona sin errores, significa que el escaping es correcto
```

## Referencias

- [PHP htmlspecialchars()](https://www.php.net/manual/en/function.htmlspecialchars.php)
- [SimpleXML addChild()](https://www.php.net/manual/en/simplexmlelement.addchild.php)
- [XML Special Characters](https://www.w3.org/TR/xml/#syntax)

## Changelog

| Fecha | Versión | Cambio |
|-------|---------|--------|
| 2026-02-02 | Final | Restaurar htmlspecialchars() en safeXmlValue() - commit d4b48e1d |
| 2026-02-02 | v1 | Documentar correctamente el proceso de escaping en SimpleXML |
