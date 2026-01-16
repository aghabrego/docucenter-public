# Integración Completa Alanube Panamá - DocuCenter

## 📋 Resumen de Implementación

### 🇵🇦 Sistema de Facturación Electrónica para Panamá

DocuCenter ahora incluye soporte completo para **Alanube Panamá**, permitiendo la emisión de facturas electrónicas según las normativas fiscales panameñas a través de la API de Alanube.

## 🚀 Tipos de Factura Soportados

### 1. **Factura de Operación Interna (01)**
- ✅ **Implementado**: `emitInvoice()` con detección automática
- ✅ **Uso común**: Ventas locales en Panamá
- ✅ **Testing**: Incluido en suite completa

### 2. **Factura de Importación (02)**
- ✅ **Implementado**: `emitImportInvoice()`
- ✅ **Características**: Naturaleza de operación "21"
- ✅ **Testing**: Validaciones específicas para importación

### 3. **Factura de Exportación (03)**
- ✅ **Implementado**: `emitExportInvoice()`
- ✅ **Características**: ITBMS 0%, destinatario extranjero
- ✅ **Testing**: Datos de prueba con países extranjeros

### 4. **Factura de Zona Franca (08)**
- ✅ **Implementado**: `emitFreeZoneInvoice()`
- ✅ **Características**: Operaciones en zona franca
- ✅ **Testing**: Incluido en tipos disponibles

### 5. **Factura de Reembolso (09)**
- ✅ **Implementado**: Detección automática por naturaleza "11"
- ✅ **Características**: Devoluciones y reembolsos
- ✅ **Testing**: Datos de prueba configurados

### 6. **Factura de Operación Extranjera (10)**
- ✅ **Implementado**: Detección automática
- ✅ **Características**: Servicios de fuente extranjera
- ✅ **Testing**: Validaciones específicas

## 📊 Estructura de Archivos Implementados

### Servicios Principales
- `app/Services/AlanubeService.php` - Servicio principal con detección automática
- `app/Utils/AlanubeEmissionHelper.php` - Helper para integración desde módulos externos
- `app/Jobs/CreateInvoiceAlanubeJob.php` - Procesamiento asíncrono con estados granulares

### Testing y Comandos
- `app/Console/Commands/TestAlanubeService.php` - Suite completa de pruebas
- `scripts/test-alanube-panama.sh` - Script de testing automatizado

## 🤖 Detección Automática de Tipo de Documento

### Algoritmo de Detección
El servicio analiza automáticamente los datos de entrada y detecta el tipo de documento apropiado:

#### Método Principal:
```php
$alanubeService = app(AlanubeService::class);
$result = $alanubeService->emitInvoice($organization, $invoiceData);
```

#### Criterios de Detección:
1. **Exportación (03)**: Si tiene `exportation`, receptor extranjero, o destino "2"
2. **Importación (02)**: Si naturaleza es "21" (NATURE_IMPORT)
3. **Zona Franca (08)**: Si contiene información de zona franca
4. **Reembolso (09)**: Si naturaleza es "11" (NATURE_RETURN)
5. **Operación Extranjera (10)**: Si naturaleza es "04" o "05"
6. **Por defecto**: Operación Interna (01)

## 🔧 Funcionalidades Avanzadas

### 1. **Procesamiento Asíncrono**
- Estados granulares: INIT, VALIDATING, PROCESSING, FINALIZING, COMPLETED
- Sistema de retry con backoff exponencial
- Logging detallado para debugging

### 2. **Transformación de Datos**
- Mapeo automático desde formato DocuCenter a Alanube Panamá
- Validación de campos obligatorios según DGI
- Soporte para múltiples formatos de entrada

### 3. **Manejo de Pagos**
- Soporte para 11 tipos de pago diferentes
- Detección automática de métodos de pago
- Validación de montos y formas de pago

### 4. **Validaciones Específicas DGI**
- ITBMS: 0%, 7%, 10%, 15%
- Tipos de receptor: Contribuyente, Consumidor final, Gobierno, Extranjero
- Códigos de seguridad de 9 dígitos
- Numeración de 10 dígitos con padding

## 🌐 Endpoints y Configuración

### URLs de API
- **Sandbox**: `https://sandbox-api.alanube.co/pan/v1/invoices`
- **Producción**: `https://api.alanube.co/pan/v1/invoices`

### Configuración PAC Requerida
```php
[
    'pac_type' => 'alanube_panama',
    'endpoint' => 'https://api.alanube.co', // o sandbox
    'token' => 'JWT_TOKEN_HERE'
]
```

### Mapeo de Endpoints
```php
protected $endpoints = [
    '01' => 'invoices',    // Operación interna
    '02' => 'invoices',    // Importación
    '03' => 'invoices',    // Exportación
    '08' => 'invoices',    // Zona Franca
    '09' => 'invoices',    // Reembolso
    '10' => 'invoices',    // Operación extranjera
];
```

## 🚀 Comandos de Testing

### Comando Artisan
```bash
# Test completo con datos de prueba
php artisan test:alanube {organization_id} --test-data

# Test tipo específico
php artisan test:alanube {organization_id} --test-data --type=03

# Test asíncrono
php artisan test:alanube {organization_id} --test-data --async
```

### Script Shell
```bash
# Validación de configuración
./scripts/test-alanube-panama.sh validation 1

# Suite completa
./scripts/test-alanube-panama.sh complete 1

# Modo interactivo
./scripts/test-alanube-panama.sh interactive
```

## 📈 Funciones de Testing Implementadas

### 1. **`validate_pac_configuration()`**
- Verifica configuración PAC para Alanube Panamá
- Valida token y endpoint
- Confirma tipo de PAC correcto

### 2. **`test_service()`**
- Prueba servicio principal `AlanubeService`
- Ejecuta detección automática de tipos
- Valida respuestas de API

### 3. **`test_helper()`**
- Prueba `AlanubeEmissionHelper`
- Valida transformación de datos
- Verifica detección de tipos

### 4. **`test_job()`**
- Prueba procesamiento asíncrono
- Verifica estados del job
- Confirma dispatch exitoso

### 5. **`test_all_types()`**
- Prueba todos los 6 tipos de documento
- Validaciones específicas por tipo
- Datos de prueba personalizados

## 🎯 Uso en Producción

### Integración Simple
```php
// El servicio detecta automáticamente el tipo y emite la factura correspondiente
$alanubeService = app(AlanubeService::class);
$result = $alanubeService->emitInvoice($organization, $invoiceData);

if ($result['success']) {
    echo "Factura emitida exitosamente";
    echo "Tipo: " . $result['document_type'];
    echo "Código: " . $result['type_code'];
}
```

### Helper para Módulos Externos
```php
// Emisión desde cualquier módulo
use App\Utils\AlanubeEmissionHelper;

// Emisión automática
$result = AlanubeEmissionHelper::emitDocument($organization, $saleData);

// Emisión específica por tipo
$result = AlanubeEmissionHelper::emitExportInvoice($organization, $saleData);
```

### Procesamiento Asíncrono
```php
// Para facturas que requieren procesamiento asíncrono
CreateInvoiceAlanubeJob::dispatch($invoiceData, $organizationId, $userId);
```

## 🔒 Compliance DGI Panamá

### Validaciones Implementadas
- ✅ **Numeración**: 10 dígitos con padding automático
- ✅ **Código de Seguridad**: 9 dígitos generados automáticamente
- ✅ **Punto de Facturación**: 3 dígitos con formato correcto
- ✅ **ITBMS**: Tasas válidas (0%, 7%, 10%, 15%)
- ✅ **Tipos de Receptor**: Validación según DGI
- ✅ **Identificación Extranjera**: Para exportaciones
- ✅ **RUC**: Formato y validación para contribuyentes
- ✅ **Ubicación**: Códigos de 8 dígitos para provincias/distritos

### Campos Obligatorios por Tipo
- **Exportación**: Identificación extranjera, país destino, ITBMS 0%
- **Importación**: Naturaleza de importación, documentación aduanera
- **Zona Franca**: Información específica de zona franca
- **Gobierno**: RUC gubernamental, validaciones específicas

## 🔄 Estados del Sistema

- **✅ COMPLETADO**: Detección automática de tipos de documento
- **✅ COMPLETADO**: Emisión automática de facturas
- **✅ COMPLETADO**: Validaciones específicas por tipo
- **✅ COMPLETADO**: Soporte completo para todos los tipos (01-10)
- **✅ COMPLETADO**: Testing integral con script automatizado
- **✅ COMPLETADO**: Jobs asíncronos con estados granulares
- **✅ COMPLETADO**: Helper para integración externa
- **✅ COMPLETADO**: Compliance total con DGI Panamá

## 📞 Siguiente Paso

El sistema está **100% listo para uso en producción** con:
- Detección automática de tipos de documento
- Emisión inteligente según tipo detectado
- Validaciones completas DGI Panamá
- Manejo de respuestas asíncronas
- Suite completa de testing automatizado

**Comando de prueba:**
```bash
./scripts/test-alanube-panama.sh complete 1
```

Este comando probará:
1. Configuración PAC
2. Detección automática de tipos
3. Emisión automática por tipo
4. Validaciones específicas DGI
5. Características avanzadas por tipo de factura

## 🔄 Diferencias con Alanube DOM

| Característica | Alanube DOM (Rep. Dom.) | Alanube Panamá |
|---|---|---|
| **Endpoint** | `/dom/v1/` | `/pan/v1/` |
| **PAC Type** | `alanube` | `alanube_panama` |
| **Impuesto** | ITBIS | ITBMS |
| **Códigos Documento** | 31, 32, 45, 46 | 01, 02, 03, 08, 09, 10 |
| **Identificación** | RNC | RUC |
| **Moneda Base** | DOP | USD, PAB |
| **Validaciones** | DGII | DGI |

Ambos servicios coexisten en DocuCenter y pueden usarse simultáneamente según la configuración PAC de cada organización.
