# TheFactoryHKA: Regla Oficial para Campo tipoContribuyente

**Fecha**: 24 de octubre de 2025
**PAC**: TheFactoryHKA
**Fuente**: [Documentación Oficial PAC](https://felwiki.thefactoryhka.com.pa/)

## 🚨 Problema Identificado

El sistema DocuCenter estaba enviando el campo `tipoContribuyente` para clientes extranjeros (`tipoClienteFE = '04'`), causando errores PAC 201 "Error al procesar solicitud".

## 📋 Regla Oficial TheFactoryHKA

### ✅ Campo tipoContribuyente DEBE estar presente para:
- **Clientes Nacionales**: `tipoClienteFE ∈ ['01', '02', '03']`
- **Con RUC válido**: `numeroRUC` no vacío

### ❌ Campo tipoContribuyente NO DEBE estar presente para:
- **Clientes Extranjeros**: `tipoClienteFE = '04'`
- **Facturas de Exportación**: `tipoDocumento = '03'`
- **Facturas a Clientes Extranjeros**: `tipoDocumento = '01'` + `tipoClienteFE = '04'`

## 🔍 Evidencia Documental

### Ejemplo 1: Factura de Exportación
**URL**: https://felwiki.thefactoryhka.com.pa/factura_de_exportacion

```xml
<ser:cliente>
    <ser:tipoClienteFE>04</ser:tipoClienteFE>
    <!-- ❌ NO HAY tipoContribuyente -->
    <ser:razonSocial>TFHKA</ser:razonSocial>
    <ser:direccion>Ave. La Paz</ser:direccion>
    <ser:tipoIdentificacion>99</ser:tipoIdentificacion>
    <ser:nroIdentificacionExtranjero>123456789</ser:nroIdentificacionExtranjero>
    <ser:pais>VE</ser:pais>
</ser:cliente>
```

### Ejemplo 2: Factura a Cliente Extranjero
**URL**: https://felwiki.thefactoryhka.com.pa/factura_a_cliente_extranjero

```xml
<ser:cliente>
    <ser:tipoClienteFE>04</ser:tipoClienteFE>
    <!-- ❌ NO HAY tipoContribuyente -->
    <ser:razonSocial>Cliente Extranjero</ser:razonSocial>
    <ser:tipoIdentificacion>01</ser:tipoIdentificacion>
    <ser:nroIdentificacionExtranjero>123456789</ser:nroIdentificacionExtranjero>
    <ser:paisExtranjero>Colombia</ser:paisExtranjero>
    <ser:pais>PA</ser:pais>
</ser:cliente>
```

## 🔧 Corrección Implementada

### Archivo: `app/Services/HKAService.php`

**ANTES:**
```php
// ❌ INCORRECTO: Asignaba tipoContribuyente a TODOS los clientes
if (!empty($cliente->numeroRUC)) {
    $cliente->tipoContribuyente = $this->getNestedValue($doc, 'dGen.gDatRec.gRucRec.dTipoRuc', 1);
} else {
    $cliente->tipoContribuyente = "2"; // ❌ Error para extranjeros
}
```

**DESPUÉS:**
```php
// ✅ CORRECTO: Solo asignar tipoContribuyente a clientes nacionales
if ($cliente->tipoClienteFE !== '04' && !empty($cliente->numeroRUC)) {
    $cliente->tipoContribuyente = $this->getNestedValue($doc, 'dGen.gDatRec.gRucRec.dTipoRuc', 1);
}
// Para extranjeros: NO asignar tipoContribuyente (campo no debe estar presente)
```

### Validación Pre-PAC:
```php
if ($cliente->tipoClienteFE === '04') {
    // Para extranjeros, según documentación TheFactoryHKA:
    // - NO debe incluir tipoContribuyente (campo no debe estar presente)
    $cliente->tipoContribuyente = null;
}
```

## 🎯 Resultado Esperado

- ✅ **Facturas a Extranjeros**: Ya no incluyen `tipoContribuyente`
- ✅ **Facturas de Exportación**: Cliente extranjero sin `tipoContribuyente`
- ✅ **Error PAC 201**: Resuelto para casos de clientes extranjeros
- ✅ **Conformidad PAC**: 100% según documentación oficial

## 📊 Casos de Prueba

### Test 1: Factura Exportación (Tipo 03)
```php
$doc->tipoDocumento = '03';
$doc->tipoClienteFE = '04'; // Extranjero
// Resultado: tipoContribuyente = null ✅
```

### Test 2: Factura a Cliente Extranjero (Tipo 01)
```php
$doc->tipoDocumento = '01';
$doc->tipoClienteFE = '04'; // Extranjero
// Resultado: tipoContribuyente = null ✅
```

### Test 3: Factura a Cliente Nacional (Tipo 01)
```php
$doc->tipoDocumento = '01';
$doc->tipoClienteFE = '02'; // Nacional
$doc->numeroRUC = '1234567890123';
// Resultado: tipoContribuyente = '1' ✅
```

## 🔗 Referencias

- [Documentación TheFactoryHKA](https://felwiki.thefactoryhka.com.pa/)
- [Factura de Exportación](https://felwiki.thefactoryhka.com.pa/factura_de_exportacion)
- [Factura a Cliente Extranjero](https://felwiki.thefactoryhka.com.pa/factura_a_cliente_extranjero)
- [Manual de Integración PAC](https://felwiki.thefactoryhka.com.pa/manual_de_integracion_directa_al_ws)

---

**REGLA CRÍTICA**: **NUNCA** enviar `tipoContribuyente` para `tipoClienteFE = '04'` con TheFactoryHKA PAC.
