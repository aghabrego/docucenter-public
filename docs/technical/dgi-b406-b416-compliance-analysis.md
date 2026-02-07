# Análisis de Cumplimiento DGI - Campos B406-B416

## 📋 Estado Actual vs Especificación DGI

### ✅ Campos OFICIALES según Ficha Técnica DGI:

#### Grupo B406 - Identificación Extranjera
- **B406** - gIdExt: Grupo de identificación extranjera (OBLIGATORIO si B401=04)
- **B4061** - dIdExt: Número de Pasaporte/Identificación Tributaria Extranjera (OBLIGATORIO, 1-50 caracteres)
- **B4062** - dPaisExt: País Extranjero (OPCIONAL, 2-100 caracteres, solo si B4061 es pasaporte)

#### Campos de Contacto
- **B408** - dTfnRec: Teléfono de contacto receptor (OPCIONAL, 0-3 ocurrencias, formato 999-9999 o 9999-9999)
- **B409** - dCorElectRec: Correo electrónico receptor (OPCIONAL, 0-3 ocurrencias, máximo 50 caracteres)

#### País del Receptor
- **B410** - cPaisRec: País del receptor (OBLIGATORIO, código de 2 caracteres de Tabla 31)
- **B411** - dPaisRecDesc: Descripción del país (OBLIGATORIO solo si B410 = "ZZ", 5-50 caracteres)

### ❌ Campos NO OFICIALES (implementados en DocuCenter pero NO en la especificación):

- **B412** - Código Distrito Extranjero (NO EXISTE en DGI)
- **B413** - Código Corregimiento Extranjero (NO EXISTE en DGI)
- **B414** - Urbanización Extranjero (NO EXISTE en DGI)
- **B415** - Dirección Extranjero (NO EXISTE en DGI)
- **B416** - Teléfono Extranjero (NO EXISTE en DGI)

## 🔧 Acciones Requeridas

### 1. Corrección de Implementación
Los campos B412-B416 deben ser **ELIMINADOS** o **MARCADOS COMO DEPRECADOS** ya que no existen en la especificación oficial de la DGI.

### 2. Mapeo Correcto DGI
```php
// CORRECTO según DGI
'gIdExt' => [
    'dIdExt' => $numeroIdentificacion,     // B4061 (OBLIGATORIO)
    'dPaisExt' => $paisExtranjero,         // B4062 (OPCIONAL, solo para pasaportes)
],
'dTfnRec' => $telefonoContacto,            // B408 (OPCIONAL)
'dCorElectRec' => $correoElectronico,      // B409 (OPCIONAL)
'cPaisRec' => $codigoPais,                 // B410 (OBLIGATORIO)
'dPaisRecDesc' => $nombrePais,             // B411 (OBLIGATORIO solo si B410="ZZ")

// INCORRECTO - ESTOS CAMPOS NO EXISTEN EN DGI:
'dDistrExt' => $distrito,                  // B412 - NO OFICIAL
'dCorregExt' => $corregimiento,            // B413 - NO OFICIAL
'dUrbanExt' => $urbanizacion,              // B414 - NO OFICIAL
'dDirExt' => $direccion,                   // B415 - NO OFICIAL
'dTfnExt' => $telefonoExtra,               // B416 - NO OFICIAL
```

### 3. Validaciones Oficiales
```php
// Validaciones que SÍ deben aplicarse según DGI
if ($receptorTipo === '04') { // Extranjero
    // B4061 - OBLIGATORIO
    'numeroIdentificacionExtranjero' => 'required|string|min:1|max:50',
    
    // B4062 - OPCIONAL (solo si B4061 es pasaporte)
    'paisExtranjero' => 'nullable|string|min:2|max:100',
    
    // B408 - OPCIONAL (0-3 ocurrencias)
    'telefonoContacto' => 'nullable|string|regex:/^\d{3}-\d{4}$|^\d{4}-\d{4}$/',
    
    // B409 - OPCIONAL (0-3 ocurrencias)
    'correoElectronico' => 'nullable|email|max:50',
    
    // B410 - OBLIGATORIO
    'codigoPaisReceptor' => 'required|string|size:2',
    
    // B411 - OBLIGATORIO solo si B410="ZZ"
    'nombrePaisReceptor' => 'required_if:codigoPaisReceptor,ZZ|string|min:5|max:50',
}
```

## 📋 Plan de Corrección

### Fase 1: Análisis de Impacto
1. Verificar si algún PAC requiere los campos B412-B416
2. Revisar facturas existentes que usen estos campos
3. Evaluar impacto en integraciones actuales

### Fase 2: Deprecación Gradual
1. Marcar campos B412-B416 como deprecados
2. Mantener compatibilidad hacia atrás
3. Agregar logging de uso de campos deprecados

### Fase 3: Implementación Oficial
1. Corregir mapeos XML según especificación DGI
2. Actualizar validaciones según campos oficiales
3. Migrar datos existentes si es necesario

### Fase 4: Testing y Validación
1. Probar con PACs oficiales (TheFactoryHKA, Alanube)
2. Validar rechazo/aceptación de facturas
3. Verificar cumplimiento normativo

## 🚨 Recomendación Inmediata

**MANTENER** la implementación actual por compatibilidad, pero **CORREGIR** el mapeo XML para enviar solo los campos oficiales a los PACs:

```php
// En getUnifiedForeignReceiverData()
return [
    'dIdExt' => $this->numeroIdentificacionExtranjero,
    'dPaisExt' => $this->paisExtranjero,
    // ELIMINAR envío de B412-B416 a PACs
    // Mantener campos en BD para compatibilidad interna
];
```

## 📚 Referencias
- Ficha Técnica DGI - Análisis PDF completado
- Campos B406-B416: Solo B406, B4061, B4062, B408, B409, B410, B411 son oficiales
- Validaciones según reglas 1610-1622 del documento DGI
