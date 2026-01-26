# Testing de Facturas de Exportación Electrónica (46) - Alanube DOM

## Descripción General

Esta documentación describe el sistema de testing completo para las **Facturas de Exportación Electrónica (46)** implementadas en DocuCenter para el servicio Alanube DOM. El sistema incluye validaciones específicas, pruebas de integración y testing automatizado.

## Estructura de Testing

### Archivos Principales
- **Script principal**: `/scripts/test-export-invoices.sh`
- **Documentación técnica**: `/docs/technical/alanube-dom-export-invoices.md`
- **Clases bajo prueba**:
  - `AlanubeDomExportInvoiceEnhancement.php`
  - `AlanubeDomService.php` (métodos de exportación)
  - `CreateExportInvoiceAlanubeDomJob.php`

### Directorio de Resultados
- **Ubicación**: `/scripts/results/`
- **Logs automáticos**: `export_test_YYYYMMDD_HHMMSS.log`
- **Comandos ejecutados**: `export_commands_YYYYMMDD_HHMMSS.log`

## Uso del Script de Testing

### Sintaxis Básica
```bash
./scripts/test-export-invoices.sh [OPCIÓN] [PARÁMETROS]
```

### Opciones Disponibles

#### 1. Testing de Validaciones
```bash
./scripts/test-export-invoices.sh validation
```
**Incluye**:
- Validación de estructura del documento
- Verificación de límites de ítems (máximo 1,000)
- Validación de indicadores de facturación (ITBIS 0%)
- Validación de información específica de exportación

#### 2. Testing del Enhancement
```bash
./scripts/test-export-invoices.sh enhancement
```
**Incluye**:
- Generación de datos de prueba
- Preparación de documentos completos
- Construcción de compradores extranjeros
- Verificación de constantes

#### 3. Testing del Servicio
```bash
./scripts/test-export-invoices.sh service
```
**Incluye**:
- Detección inteligente de facturas de exportación
- Determinación de tipo de documento
- Preparación para emisión (sin envío real)

#### 4. Testing del Job
```bash
./scripts/test-export-invoices.sh job
```
**Incluye**:
- Verificación de estructura del job
- Creación de instancias
- Validación de métodos requeridos

#### 5. Testing Completo
```bash
./scripts/test-export-invoices.sh complete
```
**Ejecuta**: Todas las pruebas anteriores en secuencia

#### 6. Modo Interactivo
```bash
./scripts/test-export-invoices.sh interactive [org_id]
```
**Características**:
- Menú interactivo con opciones numeradas
- Selección de organización específica
- Ejecución paso a paso de pruebas

### Parámetros Opcionales

#### Organización Específica
```bash
./scripts/test-export-invoices.sh validation --org-id=5
```

#### Modo Verbose
```bash
./scripts/test-export-invoices.sh complete --verbose
```
**Funcionalidad**: Muestra output detallado de cada comando

#### Guardar Resultados
```bash
./scripts/test-export-invoices.sh complete --save-results
```
**Funcionalidad**: Guarda logs detallados en archivos

#### Combinación de Parámetros
```bash
./scripts/test-export-invoices.sh complete --org-id=3 --verbose --save-results
```

## Casos de Prueba Específicos

### 1. Validación de Estructura
**Objetivo**: Verificar que el documento de exportación cumple con la estructura requerida

**Validaciones**:
- Presencia de campos obligatorios
- Formato de información adicional
- Estructura de ítems de exportación
- Datos del comprador extranjero

**Comando**:
```bash
./scripts/test-export-invoices.sh validation
```

**Resultado Esperado**:
```
[INFO]  Validación de estructura del documento de exportación - EXITOSO
[INFO]  Validación de límites de ítems - EXITOSO
[INFO]  Validación de indicadores de facturación para exportación - EXITOSO
[INFO]  Validación de información específica de exportación - EXITOSO
```

### 2. Generación de Datos de Prueba
**Objetivo**: Verificar que se pueden generar datos de prueba válidos

**Características de los datos**:
- Múltiples ítems con ITBIS 0%
- Información de transporte marítimo
- Comprador extranjero con identificador válido
- Moneda USD por defecto

**Comando**:
```bash
./scripts/test-export-invoices.sh enhancement
```

**Datos Generados**:
```json
{
  "additionalInformation": {
    "shippingDocument": "BL-001-2025",
    "destinationCountry": "US",
    "transportMean": "01",
    "departurePort": "Puerto de Balboa",
    "exportCurrency": "USD"
  },
  "buyer": {
    "foreignIdentifier": "US123456789",
    "companyName": "Test Export Company Inc.",
    "country": "US"
  },
  "itemDetails": [
    {
      "billingIndicator": "3",
      "description": "Producto de exportación",
      "taxRate": "0.00"
    }
  ]
}
```

### 3. Detección Inteligente
**Objetivo**: Verificar que el sistema detecta automáticamente facturas de exportación

**Indicadores Analizados**:
- Presencia de `shippingDocument`
- Datos de transporte
- Identificadores extranjeros
- Indicador de facturación 3

**Comando**:
```bash
./scripts/test-export-invoices.sh service
```

**Lógica de Detección**:
```php
// Verificar información de envío
$hasShipping = !empty($additionalInfo['shippingDocument']) || 
               !empty($additionalInfo['transportMean']);

// Verificar comprador extranjero
$hasForeignBuyer = !empty($buyer['foreignIdentifier']);

// Verificar indicadores de exportación
$hasExportIndicators = $billingIndicator === '3';
```

### 4. Preparación de Documentos
**Objetivo**: Verificar que los documentos se preparan correctamente para envío

**Proceso**:
1. Validación de datos de entrada
2. Formateo según especificaciones Alanube
3. Cálculo de totales con ITBIS 0%
4. Construcción de estructura final

**Validaciones de Salida**:
- Todos los ítems tienen `billingIndicator: "3"`
- Total de ITBIS es 0.00
- Estructura cumple con API de Alanube
- Información de exportación completa

### 5. Validación de Límites
**Objetivo**: Verificar que se respetan los límites específicos de exportación

**Límites Verificados**:
- **Ítems**: Máximo 1,000 por factura
- **Formas de pago**: Máximo 7 por factura
- **Longitud de campos**: Según especificaciones
- **Formatos**: Fechas, monedas, códigos de país

**Comando de Verificación**:
```bash
php artisan tinker --execute="
$testData = \App\Services\AlanubeDomExportInvoiceEnhancement::generateTestExportInvoiceData();
echo 'Ítems: ' . count($testData['itemDetails']) . '/' . \App\Services\AlanubeDomExportInvoiceEnhancement::MAX_ITEMS;
echo 'Formas de pago: ' . count($testData['paymentFormsTable']) . '/' . \App\Services\AlanubeDomExportInvoiceEnhancement::MAX_PAYMENT_FORMS;
"
```

## Escenarios de Testing Específicos

### Escenario 1: Exportación Marítima a Estados Unidos
```bash
# Datos de prueba específicos
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'BL-001-2025',
        'destinationCountry' => 'US',
        'transportMean' => '01', // Marítimo
        'departurePort' => 'Puerto de Balboa',
        'loadingPort' => 'Puerto de Colón',
        'dischargePort' => 'Puerto de Miami',
        'exportCurrency' => 'USD'
    ],
    'buyer' => [
        'foreignIdentifier' => 'US123456789',
        'companyName' => 'Import Solutions Inc.',
        'country' => 'US',
        'city' => 'Miami'
    ]
];
```

### Escenario 2: Exportación Aérea a Canadá
```bash
# Datos de prueba específicos
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'AWB-567-2025',
        'destinationCountry' => 'CA',
        'transportMean' => '02', // Aéreo
        'departurePort' => 'Aeropuerto Tocumen',
        'exportCurrency' => 'CAD'
    ],
    'buyer' => [
        'foreignIdentifier' => 'CA987654321',
        'companyName' => 'Canadian Imports Ltd.',
        'country' => 'CA',
        'city' => 'Toronto'
    ]
];
```

### Escenario 3: Exportación Terrestre a Costa Rica
```bash
# Datos de prueba específicos
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'CRT-789-2025',
        'destinationCountry' => 'CR',
        'transportMean' => '03', // Terrestre
        'departurePort' => 'Frontera Paso Canoas',
        'exportCurrency' => 'USD'
    ],
    'buyer' => [
        'rnc' => '3-101-123456', // Empresa costarricense con RNC
        'companyName' => 'Importaciones CR S.A.',
        'country' => 'CR',
        'city' => 'San José'
    ]
];
```

## Testing de Job Asíncrono

### Prueba de Estados del Job
```bash
# Verificar constantes de estado
php artisan tinker --execute="
$reflection = new ReflectionClass('\App\Jobs\CreateExportInvoiceAlanubeDomJob');
$constants = $reflection->getConstants();
foreach ($constants as $name => $value) {
    if (strpos($name, 'STATE_') === 0) {
        echo $name . ': ' . $value;
    }
}
"
```

**Estados Esperados**:
- `STATE_INIT: 1`
- `STATE_VALIDATION: 2`
- `STATE_PREPARATION: 3`
- `STATE_EMISSION: 4`
- `STATE_COMPLETED: 5`

### Prueba de Creación de Job
```bash
# Crear instancia del job
php artisan tinker --execute="
$testData = \App\Services\AlanubeDomExportInvoiceEnhancement::generateTestExportInvoiceData();
$job = new \App\Jobs\CreateExportInvoiceAlanubeDomJob(1, $testData, 1);
echo 'Job creado exitosamente';
echo 'Estado inicial: ' . $job->getCurrentState();
echo 'Cola: ' . $job->queue;
"
```

### Prueba de Configuración del Job
**Características del Job**:
- **Intentos**: 3 reintentos
- **Timeout**: 300 segundos (5 minutos)
- **Backoff**: [30, 60, 120] segundos
- **Cola**: `alanube_dom`

## Interpretación de Resultados

### Resultados Exitosos
```
[INFO]  Validación de estructura del documento de exportación - EXITOSO
[INFO]  Generación de datos de prueba - EXITOSO
[INFO]  Detección inteligente de factura de exportación - EXITOSO
[INFO]  Creación de instancia del job - EXITOSO
```

### Errores Comunes y Soluciones

#### Error: "Indicador de facturación incorrecto"
**Causa**: Ítems con indicador diferente a 3
**Solución**: Verificar que todos los ítems tengan `billingIndicator: "3"`

#### Error: "Excede límite de ítems"
**Causa**: Más de 1,000 ítems en la factura
**Solución**: Dividir en múltiples facturas

#### Error: "Información de exportación faltante"
**Causa**: Campos obligatorios vacíos en `additionalInformation`
**Solución**: Completar campos de transporte y destino

#### Error: "Comprador extranjero inválido"
**Causa**: Falta RNC o identificador extranjero
**Solución**: Proporcionar al menos un identificador válido

### Warnings Comunes
- **Moneda no especificada**: Usa USD por defecto
- **Puerto de descarga faltante**: Para transporte marítimo
- **Información aduanera vacía**: Recomendada para exportaciones

## Automatización de Testing

### Ejecución en CI/CD
```bash
# Script para integración continua
#!/bin/bash
cd /path/to/docucenter
./scripts/test-export-invoices.sh complete --save-results

if [ $? -eq 0 ]; then
    echo "Testing de exportación exitoso"
    exit 0
else
    echo "Testing de exportación falló"
    exit 1
fi
```

### Testing Programado
```bash
# Crontab para testing diario
0 2 * * * /path/to/docucenter/scripts/test-export-invoices.sh complete --save-results >> /var/log/export-test.log 2>&1
```

### Monitoring de Resultados
```bash
# Verificar logs de resultados
tail -f /scripts/results/export_test_$(date +%Y%m%d)*.log
```

## Integración con Testing Framework

### PHPUnit Tests
**Ubicación sugerida**: `tests/Feature/AlanubeDom/ExportInvoiceTest.php`

```php
<?php

namespace Tests\Feature\AlanubeDom;

use Tests\TestCase;
use App\Services\AlanubeDomExportInvoiceEnhancement;
use App\Services\AlanubeDomService;
use App\Jobs\CreateExportInvoiceAlanubeDomJob;

class ExportInvoiceTest extends TestCase
{
    public function test_export_invoice_validation()
    {
        $testData = AlanubeDomExportInvoiceEnhancement::generateTestExportInvoiceData();
        $validation = AlanubeDomExportInvoiceEnhancement::validateCompleteDocument($testData);
        
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }
    
    public function test_export_invoice_detection()
    {
        $testData = AlanubeDomExportInvoiceEnhancement::generateTestExportInvoiceData();
        $service = new AlanubeDomService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('isExportInvoiceIndicator');
        $method->setAccessible(true);
        
        $isExport = $method->invoke($service, $testData);
        $this->assertTrue($isExport);
    }
    
    public function test_export_job_creation()
    {
        $testData = AlanubeDomExportInvoiceEnhancement::generateTestExportInvoiceData();
        $job = new CreateExportInvoiceAlanubeDomJob(1, $testData, 1);
        
        $this->assertEquals(1, $job->getCurrentState());
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
    }
}
```

## Conclusiones

### Cobertura de Testing
- **Validaciones**: Estructura, límites, indicadores
- **Enhancement**: Generación, preparación, constantes
- **Servicio**: Detección, integración, formateo
- **Job**: Estados, configuración, métodos
- **Integración**: Flujo completo end-to-end

### Calidad del Testing
- **Automatizado**: Script ejecutable con múltiples opciones
- **Interactivo**: Modo para testing manual paso a paso
- **Logging**: Registro detallado de resultados
- **Escalable**: Fácil agregar nuevos casos de prueba

### Próximos Pasos
1. **Integración CI/CD**: Automatizar en pipeline
2. **Testing Performance**: Pruebas de carga con múltiples facturas
3. **Testing Integration**: Pruebas con PAC real (sandbox)
4. **Monitoring**: Alertas automáticas en fallos

---

**Fecha de creación**: 2025-01-22  
**Versión**: 1.0.0  
**Autor**: DocuCenter Development Team  
**Última actualización**: 2025-01-22
