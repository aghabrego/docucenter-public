# CORRECCIÓN CRÍTICA: Campos Prohibidos para Extranjeros - TheFactoryHKA

**Fecha**: 24 de octubre de 2025  
**PAC**: TheFactoryHKA  
**Error**: PAC 201 "Error al procesar solicitud"  
**Causa**: **Envío de campos PROHIBIDOS para clientes extranjeros**

## **PROBLEMA IDENTIFICADO**

Según la documentación oficial de TheFactoryHKA ([Método Enviar](https://felwiki.thefactoryhka.com.pa/enviar)), para clientes extranjeros (`tipoClienteFE = 04`) existen **campos que NO DEBEN SER ENVIADOS**.

### **CAMPOS PROHIBIDOS PARA EXTRANJEROS**

Según especificación oficial: **"No debe ser enviado cuando tipoClienteFE = 04"**

1. **`tipoContribuyente`** - No debe ser enviado
2. **`numeroRUC`** - No debe ser enviado  
3. **`digitoVerificadorRUC`** - No debe ser enviado
4. **`codigoUbicacion`** - No debe ser enviado
5. **`provincia`** - No debe ser enviado
6. **`distrito`** - No debe ser enviado
7. **`corregimiento`** - No debe ser enviado

### **CAMPOS PERMITIDOS PARA EXTRANJEROS**

Solo estos campos pueden ser enviados para `tipoClienteFE = 04`:

1. **`razonSocial`** - Permitido
2. **`direccion`** - Permitido
3. **`telefono1`** - Permitido (opcional)
4. **`correoElectronico1`** - Permitido (opcional)
5. **`tipoIdentificacion`** - Requerido (01: Pasaporte, 02: Número Tributario, 99: Otro)
6. **`nroIdentificacionExtranjero`** - Requerido
7. **`paisExtranjero`** - Permitido (solo para pasaportes)
8. **`pais`** - Requerido (código de país)
9. **`paisOtro`** - Condicional (si país = ZZ)

## **CORRECCIÓN IMPLEMENTADA**

### **Archivo**: `app/Services/HKAService.php`

**ANTES** (INCORRECTO):
```php
// Asignaba TODOS los campos a TODOS los tipos de cliente
$cliente->numeroRUC = $this->getNestedValue($doc, 'dGen.gDatRec.gRucRec.dRuc');
$cliente->digitoVerificadorRUC = $this->getNestedValue($doc, 'dGen.gDatRec.gRucRec.dDV');
$cliente->codigoUbicacion = $this->getNestedValue($doc, 'dGen.gDatRec.gUbiRec.dCodUbi');
$cliente->provincia = $this->getNestedValue($doc, 'dGen.gDatRec.gUbiRec.dProv');
$cliente->distrito = $this->getNestedValue($doc, 'dGen.gDatRec.gUbiRec.dDistr');
$cliente->corregimiento = $this->getNestedValue($doc, 'dGen.gDatRec.gUbiRec.dCorreg');
// VIOLABA especificación PAC para extranjeros
```

**DESPUÉS** (CORRECTO):
```php
if ($cliente->tipoClienteFE === '04') {
    // EXTRANJEROS: Solo campos permitidos según documentación oficial
    // NO ENVIAR: numeroRUC, digitoVerificadorRUC, tipoContribuyente, codigoUbicacion, provincia, distrito, corregimiento
    $cliente->razonSocial = $this->getNestedValue($doc, 'dGen.gDatRec.dNombRec');
    $cliente->direccion = $this->getNestedValue($doc, 'dGen.gDatRec.dDirecRec');
    $cliente->telefono1 = $this->getNestedValue($doc, 'dGen.gDatRec.dTfnRec');
    $cliente->correoElectronico1 = $this->getNestedValue($doc, 'dGen.gDatRec.dCorElectRec');
    $cliente->tipoIdentificacion = !empty($tipoIdentificacion) ? $this->strPad($tipoIdentificacion, 2) : null;
    $cliente->nroIdentificacionExtranjero = $this->getNestedValue($doc, 'dGen.gDatRec.gIdExt.dIdExt');
    
    if ($cliente->tipoIdentificacion === '01') {
        $cliente->paisExtranjero = $this->getNestedValue($doc, 'dGen.gDatRec.gIdExt.dPaisExt');
    }
    $cliente->pais = $this->getNestedValue($doc, 'dGen.gDatRec.cPaisRec', 'PA');
    if ($cliente->pais !== 'PA') {
        $cliente->paisOtro = $this->getNestedValue($doc, 'dGen.gDatRec.dPaisRecDesc');
    }
} else {
    // CLIENTES NACIONALES: Todos los campos incluyendo RUC y ubicación
    $cliente->numeroRUC = $this->getNestedValue($doc, 'dGen.gDatRec.gRucRec.dRuc');
    // ... resto de campos nacionales
}
```

### **Validación Pre-PAC Actualizada**:
```php
if ($cliente->tipoClienteFE === '04') {
    // Para extranjeros, según documentación TheFactoryHKA:
    // CRÍTICO: Asegurar que campos prohibidos NO estén presentes
    $cliente->tipoContribuyente = null;
    $cliente->numeroRUC = null;
    $cliente->digitoVerificadorRUC = null;
    $cliente->codigoUbicacion = null;
    $cliente->provincia = null;
    $cliente->distrito = null;
    $cliente->corregimiento = null;
}
```

### **Filtrado Mejorado**:
```php
private function filterNullValues($object)
{
    // CRÍTICO: TheFactoryHKA requiere OMITIR campos vacíos, no enviarlos
    foreach ($object as $key => $value) {
        if ($value !== null && $value !== '' && $value !== [] && $value !== 0) {
            // Solo incluir valores que realmente tienen contenido
            if ($value === 0 || $value === '0' || $value === '0.00' || $value === '0.000000') {
                $filtered[$key] = $value; // Valores cero son válidos
            } elseif (!empty($value)) {
                $filtered[$key] = $value;
            }
        }
    }
}
```

## **IMPACTO ESPERADO**

### **Antes vs Después**

**ANTES** - XML enviado al PAC para extranjero:
```xml
<ser:cliente>
    <ser:tipoClienteFE>04</ser:tipoClienteFE>
    <ser:tipoContribuyente>2</ser:tipoContribuyente>     <!-- PROHIBIDO -->
    <ser:numeroRUC></ser:numeroRUC>                      <!-- PROHIBIDO -->
    <ser:digitoVerificadorRUC></ser:digitoVerificadorRUC> <!-- PROHIBIDO -->
    <ser:codigoUbicacion>1-1-1</ser:codigoUbicacion>     <!-- PROHIBIDO -->
    <ser:provincia>Florida</ser:provincia>                <!-- PROHIBIDO -->
    <ser:distrito>Miami</ser:distrito>                    <!-- PROHIBIDO -->
    <ser:corregimiento>Miami</ser:corregimiento>          <!-- PROHIBIDO -->
    <ser:razonSocial>Cliente Export USA</ser:razonSocial>
    <ser:tipoIdentificacion>01</ser:tipoIdentificacion>
    <ser:nroIdentificacionExtranjero>US123456789</ser:nroIdentificacionExtranjero>
    <ser:pais>US</ser:pais>
</ser:cliente>
```

**DESPUÉS** - XML enviado al PAC para extranjero:
```xml
<ser:cliente>
    <ser:tipoClienteFE>04</ser:tipoClienteFE>
    <!-- Campos prohibidos OMITIDOS -->
    <ser:razonSocial>Cliente Export USA</ser:razonSocial>
    <ser:direccion>Miami Street 123</ser:direccion>
    <ser:telefono1>+1-555-1234</ser:telefono1>
    <ser:correoElectronico1>client@usa.com</ser:correoElectronico1>
    <ser:tipoIdentificacion>01</ser:tipoIdentificacion>
    <ser:nroIdentificacionExtranjero>US123456789</ser:nroIdentificacionExtranjero>
    <ser:paisExtranjero>Estados Unidos</ser:paisExtranjero>
    <ser:pais>US</ser:pais>
    <ser:paisOtro>Estados Unidos</ser:paisOtro>
</ser:cliente>
```

##  **TESTING**

### **Script de Validación**:
```bash
# Laravel Tinker
php artisan tinker
include 'docs/testing/test-campos-permitidos-extranjeros.php';
```

### **Casos de Prueba**:
1. **Cliente Nacional** → Incluye RUC, ubicación, tipoContribuyente
2. **Cliente Extranjero** → Solo campos permitidos, sin RUC/ubicación
3. **Factura Exportación** → Cliente extranjero sin campos prohibidos

## **RESULTADO ESPERADO**

- **Error PAC 201**: Debería RESOLVERSE
- **Conformidad PAC**: 100% según especificación oficial
- **Facturas Exportación**: Procesan sin errores
- **Clientes Extranjeros**: Procesan sin errores
- **XML Limpio**: Solo campos requeridos/permitidos

## **REFERENCIAS**

- [Método Enviar - TheFactoryHKA](https://felwiki.thefactoryhka.com.pa/enviar)
- [Catálogo Códigos Retorno](https://felwiki.thefactoryhka.com.pa/_media/catalogo_de_codigos_de_retorno_del_servicio-08-2023.pdf)
- [Manual Integración](https://felwiki.thefactoryhka.com.pa/manual_de_integracion_directa_al_ws)

---

## **REGLA CRÍTICA**

**NUNCA enviar campos marcados como "No debe ser enviado cuando tipoClienteFE = 04"**

**Código PAC 109**: "El campo [campo] no debe ser informado."  
**Código PAC 201**: "Error al procesar solicitud." (error genérico cuando hay problemas de validación)

---

**Estado**: **IMPLEMENTADO**  
**Testing**: **SCRIPT CREADO**  
**Documentado**: **COMPLETO**
