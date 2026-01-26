# Plan de Debug - Error "instance requires property exportation"

## Diagnóstico Paso a Paso

### 1. Verificar los logs añadidos
Los logs de debug se han agregado al código. Para ver qué está pasando exactamente:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep "AlanubeFormatterHelper"
```

### 2. Información que necesitamos confirmar

**Del formulario**:
- ¿Qué tipo de documento seleccionas? (debe ser "Factura de operación interna")
- ¿El valor `tipeDocument` es '1'?
- ¿El receptor es tipo extranjero?

**Del PAC**:
- ¿Qué endpoint PAC estás usando?
- ¿Es Panamá (.../pan/v1) o República Dominicana (.../dom/v1)?

### 3. Posibles causas del error

#### Causa 1: Detección incorrecta de país
```
SÍNTOMA: Se procesa como República Dominicana cuando debería ser Panamá
SOLUCIÓN: Verificar configuración PAC y endpoint
```

#### Causa 2: Tipo de documento incorrecto
```
SÍNTOMA: Se envía como tipo 02/03 cuando debería ser 01
SOLUCIÓN: Verificar mapeo de tipo de documento
```

#### Causa 3: Cache del PAC
```
SÍNTOMA: PAC tiene versión anterior cacheada
SOLUCIÓN: Limpiar cache o esperar expiración
```

#### Causa 4: Validación adicional del PAC
```
SÍNTOMA: PAC tiene validaciones no documentadas
SOLUCIÓN: Contactar soporte Alanube o revisar documentación actualizada
```

### 4. Comandos de debug

```bash
# 1. Limpiar logs para ver solo los nuevos
echo "" > storage/logs/laravel.log

# 2. Procesar la factura problemática

# 3. Ver logs específicos del formatter
grep "AlanubeFormatterHelper" storage/logs/laravel.log

# 4. Ver logs específicos de exportation
grep "exportation" storage/logs/laravel.log
```

### 5. Verificación inmediata

Ejecutar este comando para confirmar que la lógica condicional está activa:

```bash
cd /home/weirdolabs/code/docucenter
grep -A 5 -B 5 "should_include_exportation" app/Helpers/AlanubeFormatterHelper.php
```

Si no encuentra nada, significa que los logs de debug no están en el código actual.

### 6. Testing manual

Para probar sin procesar factura real:

```php
// En tinker o archivo de prueba
$testData = [
    'dGen' => [
        'iDoc' => '01', // Operación interna
        'gDatRec' => [/* datos receptor */]
    ]
    // Sin gFExp
];

$connection = new \App\Models\Pacconnection();
$connection->endpoint = 'https://api.alanube.co/pan/v1'; // Panamá

$result = \App\Helpers\AlanubeFormatterHelper::format($testData, $connection);

// Verificar si tiene exportation
var_dump(isset($result['receiver']['exportation']));
```

### 7. Próximos pasos según resultado

**Si los logs muestran que SÍ se incluye exportation para tipo 01**:
- Hay bug en la lógica condicional
- Necesita corrección adicional

**Si los logs muestran que NO se incluye exportation**:
- El problema está en el PAC o cache
- Contactar soporte Alanube

**Si no aparecen logs**:
- Los cambios no están aplicados
- Revisar deployment

---

## Acción Inmediata

1. **Procesar una factura problemática**
2. **Revisar logs**: `tail -f storage/logs/laravel.log | grep exportation`
3. **Reportar qué aparece en los logs**

Con esta información podremos identificar exactamente dónde está el problema.
