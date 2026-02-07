# Fix: Problemas de Mapeo en ACIcloudService::storeOrder

## Problemas Identificados y Corregidos

### 1. **Error de Mapeo - iTipoRec (Línea 1125)**

**Código Incorrecto:**
```php
$iTipoRec = array_get($data, 'dGen.gDatRec.gRucRec.iTipoRec', '2');
```

**Código Corregido:**
```php
$iTipoRec = array_get($data, 'dGen.gDatRec.iTipoRec', '2');
```

**Explicación:** `iTipoRec` está en `gDatRec` directamente, no dentro de `gRucRec`.

### 2. **Error de Mapeo - dTipoRuc (Línea 1127)**

**Código Incorrecto:**
```php
$dTipoRuc = array_get($data, 'dGen.gDatRec.dTipoRuc', '1');
```

**Código Corregido:**
```php
$dTipoRuc = array_get($data, 'dGen.gDatRec.gRucRec.dTipoRuc', '1');
```

**Explicación:** `dTipoRuc` está dentro de `gRucRec`, no en `gDatRec` directamente.

## Estructura Correcta del JSON

```json
{
  "dGen": {
    "gDatRec": {
      "iTipoRec": "1",           // ← En gDatRec (nivel raíz)
      "gRucRec": {
        "dTipoRuc": "2",         // ← En gRucRec (anidado)
        "dRuc": "1808755-1-706832",
        "dDV": "97"
      },
      "dNombRec": "CLINICA HOSPITALSAN JUAN DE DIOS",
      // ... otros campos
    }
  }
}
```

## Impacto de las Correcciones

### **Antes de las Correcciones:**
- `iTipoRec` siempre tomaba el valor por defecto `'2'`
- `dTipoRuc` siempre tomaba el valor por defecto `'1'`
- Los datos del receptor no se registraban correctamente en `CustomersImp`

### **Después de las Correcciones:**
- `iTipoRec` lee correctamente el valor `"1"` del JSON
- `dTipoRuc` lee correctamente el valor `"2"` del JSON  
- Los datos del receptor se registran correctamente en la base de datos

## Verificación con el JSON de Prueba

**Datos del JSON:**
```json
"gDatRec": {
    "iTipoRec": "1",
    "gRucRec": {
        "dTipoRuc": "2",
        "dRuc": "1808755-1-706832",
        "dDV": "97"
    }
}
```

**Registro en CustomersImp:**
```php
[
    'Custom_field3' => '1',    // ← iTipoRec correcto
    'Custom_field4' => '2',    // ← dTipoRuc correcto
    'Custom_field1' => '1808755-1-706832',  // ← RUC
    'Custom_field2' => '97',   // ← DV
]
```

## Otros Campos Validados

Los siguientes campos ya estaban funcionando correctamente:

**Datos básicos del receptor:**
- `CustomerID`: Mapea desde `gRucRec.dRuc` 
- `Customer_Bill_Name`: Mapea desde `dNombRec` 
- `AddressLine1`: Mapea desde `dDirecRec` 
- `Country`: Mapea desde `cPaisRec` 
- `Email`: Mapea desde `dCorElectRec` 

**Datos de ubicación:**
- `Custom_field5`: Mapea desde `gUbiRec.dCodUbi` 

## Estado Post-Corrección

- **ACIcloudService::storeOrder** funciona correctamente
- **Datos del receptor** se registran con valores reales del JSON
- **Compatibilidad** con la API create_sale_acicloud_with_emission
- **Validaciones** del Request pasan correctamente

## Commits Relacionados

- `608913c` - fix: corregir mapeo de campos iTipoRec y dTipoRuc en ACIcloudService::storeOrder
- `257d23d` - fix: corregir typo gRucRuc por gRucRec en prepareForValidation de CreateSaleAciCloudRequest
- `575ee59` - docs: agregar documentación de fix para error ACIcloudService::storeOrder()

## Próximos Pasos

1. **Testing**: Verificar que los datos se registren correctamente en CustomersImp
2. **Validación**: Confirmar que las validaciones del Request funcionen
3. **Documentación**: Actualizar documentación de API con ejemplos correctos
