# Implementación de campo gPedComGl_dNroPed en FeHeader

**Fecha:** 19 de febrero de 2026  
**Estado:**  Completado y Verificado

## Resumen

Implementación del campo `gPedComGl_dNroPed` (varchar 12) para almacenar el número de pedido de compra global desde facturas electrónicas de Panamá según especificación DGI.

## Cambios Realizados

### 1. Base de Datos (Schema)
**Archivo:** `app/Models/stubs/fe_header.sql.stub`
- Agregada columna: `gPedComGl_dNroPed varchar(12) DEFAULT NULL`
- Ubicación: Antes de la declaración PRIMARY KEY

### 2. Modelo Eloquent
**Archivo:** `app/Models/FeHeader.php`
- Agregada propiedad en PHPDoc: `@property string $gPedComGl_dNroPed`
- Agregada al array `$fillable` para asignación masiva

### 3. Lógica de Importación
**Archivo:** `app/Utils/ImportData.php`

#### Método xml() - Línea ~291
```php
$gPedComGlDNroPed = $this->getFirstfilterData($data, '/gPedComGl1_dNroPed1/i');
```

#### Método xml() - Línea ~327 (request array)
```php
'gPedComGl_dNroPed' => $gPedComGlDNroPed,
```

#### Método xmlGT() - Línea ~777 (Guatemala format)
```php
'gPedComGl_dNroPed' => array_get($data, 'gPedComGl_dNroPed'),
```

#### Método getDataXML() - Línea ~1842
```php
// Extracción del campo
$gPedComGlDNroPed = $this->getFirstfilterData($data, '/gPedComGl1_dNroPed1/i');

// En el array de retorno
'gPedComGl_dNroPed' => $gPedComGlDNroPed,
```

#### Método setDataFile() - Línea ~2031
```php
// En el array $request
'gPedComGl_dNroPed' => array_get($data, 'gPedComGl_dNroPed'),
```

### 4. Fix Crítico del Parser XML
**Archivo:** `app/Utils/XML.php` - Método `XMLToArrayFlat()`

**Problema:** El parser solo extraía 2 claves del XML en lugar de toda la estructura (~92 claves).

**Causa Raíz:** Cuando el xpath `//dgi:feDatosMsg` fallaba, asignaba `null` a `$children` y el método retornaba inmediatamente sin procesar el resto del documento XML.

**Solución Implementada:**
```php
// ANTES (línea 80):
$data = $xml->xpath($xpath);
$children = array_first($data);  // Si falla xpath, $children = null

// DESPUÉS:
$data = $xml->xpath($xpath);
if (!empty($data) && is_array($data)) {
    $firstElement = array_first($data);
    if ($firstElement instanceof SimpleXMLElement) {
        $children = $firstElement;
    }
}
// Mantiene el valor original de $children si xpath falla
```

**Impacto:** Este fix resuelve un problema crítico que afectaba el parsing de TODOS los archivos XML que no contienen el nodo `feDatosMsg`, mejorando la extracción de 2 claves a 92+ claves.

## Estructura XML Esperada

El campo se extrae de la siguiente estructura en el XML de factura electrónica:

```xml
<rFE xmlns="http://dgi-fep.mef.gob.pa">
    <dVerForm>1.00</dVerForm>
    <dId>FE012000...</dId>
    <gDGen>...</gDGen>
    <gItem>...</gItem>
    <gTot>...</gTot>
    <gPedComGl>
        <dNroPed>1234</dNroPed>
    </gPedComGl>
    ...
</rFE>
```

**Patrón de búsqueda:** `/gPedComGl1_dNroPed1/i` (case-insensitive)  
**Ruta en array plano:** `xFe1_rFE1_gPedComGl1_dNroPed1`

## Verificación y Testing

### Scripts de Prueba Creados

Todos los scripts están ubicados en `/scripts/` para facilitar el acceso:

#### 1. `test-real-xml.php`
- **Propósito:** Verificar parsing básico del XML real
- **Resultado:**  92 claves parseadas, campo encontrado con valor "1234"

#### 2. `test-full-import-gped.php`
- **Propósito:** Verificar extracción usando `getFirstfilterData()`
- **Pruebas:** 
  - Patrón exacto `/gPedComGl1_dNroPed1/i`
  - Patrón flexible `/gPedComGl.*dNroPed/i`
  - Campos comunes (CUFE, número factura, total)
- **Resultado:**  Todas las pruebas pasaron

#### 3. `test-import-simulation.php`
- **Propósito:** Simular flujo completo de importación
- **Verifica:** Array completo que se enviaría a `FeHeader::create()`
- **Resultado:**  Implementación lista para producción

#### 4. `test-complete-flow-gped.php` 🆕
- **Propósito:** Verificar implementación en todos los métodos de ImportData.php
- **Verifica:**
  -  Método `xml()` - Extracción implementada
  -  Método `xmlGT()` - Formato Guatemala
  -  Método `getDataXML()` - Array de retorno
  -  Método `setDataFile()` - Request array
  -  Modelo FeHeader - PHPDoc y $fillable
  -  Schema stub SQL - Columna agregada
- **Resultado:**  Implementación completa verificada

### Archivo XML de Prueba
**Ubicación:** `tests/Feature/Resources/01_01_0000015680_001_0000.xml`
- CUFE: FE0120000155651535-2-2017-2500002026021000000156800010118734105753
- Emisor: PRAYA LOGISTICS S.A.
- Receptor: CONTADO
- Número de Pedido: **1234**

### Resultados de Pruebas

```bash
# Test 1: Parser básico
$ docker exec docucenter_laravel.test php /var/www/html/scripts/test-real-xml.php
 SUCCESS: Campo extraído correctamente: 1234

# Test 2: Extracción con getFirstfilterData
$ docker exec docucenter_laravel.test php /var/www/html/scripts/test-full-import-gped.php
 SUCCESS: Todas las pruebas pasaron

# Test 3: Simulación completa
$ docker exec docucenter_laravel.test php /var/www/html/scripts/test-import-simulation.php
 SUCCESS: Campo gPedComGl_dNroPed extraído correctamente
   Implementación lista para producción

# Test 4: Verificación completa de implementación
$ docker exec docucenter_laravel.test php /var/www/html/scripts/test-complete-flow-gped.php
 SUCCESS: Implementación completa verificada
    Método xml() - Extracción implementada
    Método xmlGT() - Formato Guatemala implementado
    Método getDataXML() - Array de retorno actualizado
    Método setDataFile() - Request array actualizado
    Modelo FeHeader - PHPDoc y $fillable actualizados
    Schema fe_header.sql.stub - Columna agregada
```

## Notas Técnicas

### Warning del Namespace
Durante el parsing aparece el siguiente warning (no afecta funcionalidad):
```
PHP Warning: SimpleXMLElement::xpath(): Undefined namespace prefix in /var/www/html/app/Utils/XML.php on line 80
```

Este warning es normal y ocurre cuando los archivos XML no contienen el namespace `dgi:feDatosMsg`. El parser ahora maneja correctamente este caso y continúa con el procesamiento normal del documento.

### Compatibilidad
-  Compatible con facturas electrónicas de Panamá (DGI)
-  Compatible con formato Guatemala (xmlGT)
-  Importación desde archivos XML (método xml)
-  Importación desde Livewire (método getDataXML)
-  Procesamiento de datos (método setDataFile)
-  No afecta importaciones existentes
-  Campo opcional (nullable)

## Flujo de Procesamiento

### Flujo 1: Importación desde Livewire Component
```
Usuario sube XML → getDataXML() → setDataFile() → FeHeader::create()
                    ↓
                    Extrae gPedComGl_dNroPed
                    ↓
                    Incluye en array de retorno
                                                    ↓
                                                    Campo guardado en BD
```

### Flujo 2: Importación desde Job/Comando
```
XML File → xml() → FeHeader::create()
           ↓
           Extrae gPedComGl_dNroPed
           ↓
           Incluye en request array
                                     ↓
                                     Campo guardado en BD
```

### Flujo 3: Formato Guatemala
```
XML GT → xmlGT() → FeHeader::create()
         ↓
         Extrae gPedComGl_dNroPed
         ↓
         Incluye en request array
                                   ↓
                                   Campo guardado en BD
```

## Migración a Producción

### Pasos Requeridos

1. **Ejecutar migración de base de datos** en todas las organizaciones:
   ```sql
   ALTER TABLE fe_header ADD COLUMN gPedComGl_dNroPed varchar(12) DEFAULT NULL;
   ```

2. **Desplegar código actualizado:**
   - `app/Models/FeHeader.php`
   - `app/Models/stubs/fe_header.sql.stub`
   - `app/Utils/ImportData.php`
   - `app/Utils/XML.php` (Fix crítico)

3. **Verificar en ambiente de producción:**
   ```bash
   docker exec docucenter-app-1 php /var/www/html/scripts/test-real-xml.php
   ```

### Beneficios del Fix del Parser

El fix del método `XMLToArrayFlat()` no solo permite extraer `gPedComGl_dNroPed`, sino que mejora significativamente el parsing de TODOS los archivos XML que no contienen el nodo `feDatosMsg`. Esto puede resolver potenciales problemas de extracción en otras importaciones.

## Referencias

- **Especificación DGI Panamá:** Campo `gPedComGl > dNroPed`
- **Tipo de dato:** varchar(12) según normativa
- **Uso:** Número del pedido de compra en transacciones B2B

## Conclusión

 **Implementación completa y verificada**  
 **Fix crítico del parser XML aplicado**  
 **Scripts de prueba disponibles para validación continua**  
 **Documentación actualizada**  
 **Lista para despliegue en producción**


**Autor**: Team de Docucenter  
---

**Archivo de Testing:** Este documento permanece en `docs/testing/` para referencia futura
