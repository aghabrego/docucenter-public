# Facturas de Exportación Electrónica (46) - Alanube DOM

## Descripción General

Este documento describe la implementación completa de las **Facturas de Exportación Electrónica (46)** para el servicio Alanube DOM en DocuCenter. La implementación incluye validaciones específicas, procesamiento asíncrono y cumplimiento de normativas fiscales panameñas para operaciones de exportación.

## Características Principales

### Validaciones Específicas de Exportación
- **ITBIS tasa 0%**: Todos los ítems deben tener indicador de facturación 3
- **Información de transporte**: Validación de medios de transporte y detalles de envío
- **Datos aduaneros**: Información de exportación y documentos asociados
- **Comprador extranjero**: Soporte para RNC o identificadores internacionales
- **Múltiples monedas**: Manejo de monedas extranjeras y tipos de cambio

### Límites y Restricciones
- **Máximo de ítems**: 1,000 ítems por factura
- **Formas de pago**: Máximo 7 formas de pago
- **Monedas soportadas**: USD, EUR, CAD, CHF, GBP, JPY, DOP
- **Medios de transporte**: Marítimo, aéreo, terrestre, multimodal

## Arquitectura de la Implementación

```
AlanubeDomExportInvoiceEnhancement.php (Utility Class)
├── Validaciones específicas de exportación
├── Constantes de tipos de exportación
├── Preparación de documentos
└── Generación de datos de prueba

AlanubeDomService.php (Service Integration)
├── emitExportInvoice() - Método principal
├── isExportInvoiceIndicator() - Detección inteligente
├── Integración con endpoint 'export-supports'
└── Manejo de respuestas específicas

CreateExportInvoiceAlanubeDomJob.php (Async Processing)
├── Procesamiento asíncrono con estados granulares
├── Validaciones avanzadas de límites
├── Manejo de errores y reintentos
└── Logging detallado del proceso
```

## Componentes Implementados

### 1. AlanubeDomExportInvoiceEnhancement

**Ubicación**: `app/Services/AlanubeDomExportInvoiceEnhancement.php`

**Propósito**: Clase utilitaria para validaciones y preparación específica de facturas de exportación.

**Métodos Principales**:

#### `validateCompleteDocument(array $invoiceData): array`
Valida la estructura completa del documento de exportación:
- Información básica de la factura
- Datos de exportación específicos
- Validación de ítems con ITBIS 0%
- Información de transporte y aduanas

```php
$validation = AlanubeDomExportInvoiceEnhancement::validateCompleteDocument($invoiceData);
if (!$validation['valid']) {
    throw new Exception("Errores: " . implode(', ', $validation['errors']));
}
```

#### `validateExportInformation(array $additionalInformation): array`
Valida información específica de exportación:
- Documento de embarque
- País de destino
- Medio de transporte
- Información aduanera

#### `validateExportItems(array $items): array`
Valida ítems específicos para exportación:
- Indicador de facturación 3 (ITBIS 0%)
- Descripción de productos
- Clasificación arancelaria
- Valores de exportación

#### `buildExportBuyer(array $buyerData): array`
Construye datos del comprador para exportación:
- Soporte para RNC o identificador extranjero
- Validación de datos de empresa
- Información de contacto internacional

#### `prepareExportInvoiceDocument(array $invoiceData): array`
Prepara el documento completo para envío al PAC:
- Formateo según especificaciones Alanube
- Aplicación de reglas de exportación
- Cálculos de totales con ITBIS 0%

#### `generateTestExportInvoiceData(): array`
Genera datos de prueba para facturas de exportación:
- Estructura completa de factura
- Datos de exportación realistas
- Múltiples ítems con diferentes clasificaciones

### 2. Integración en AlanubeDomService

**Método Principal**: `emitExportInvoice(Organization $organization, array $invoiceData): array`

**Flujo de Procesamiento**:
1. **Validación inicial** usando `AlanubeDomExportInvoiceEnhancement`
2. **Preparación** del documento con formateo específico
3. **Envío** al endpoint `export-supports` de Alanube
4. **Procesamiento** de respuesta con manejo de errores
5. **Logging** detallado del proceso completo

**Detección Inteligente**: `isExportInvoiceIndicator(array $invoiceData): bool`
- Analiza campos de información adicional
- Detecta presencia de datos de transporte
- Identifica compradores extranjeros
- Verifica indicadores de facturación de exportación

### 3. Job Asíncrono

**Clase**: `CreateExportInvoiceAlanubeDomJob`

**Estados del Procesamiento**:
1. **STATE_INIT** (1): Inicialización
2. **STATE_VALIDATION** (2): Validación de datos
3. **STATE_PREPARATION** (3): Preparación del documento
4. **STATE_EMISSION** (4): Emisión al PAC
5. **STATE_COMPLETED** (5): Completado exitosamente

**Características**:
- **Reintentos**: 3 intentos con backoff progresivo
- **Timeout**: 5 minutos por intento
- **Cola específica**: `alanube_dom`
- **Logging granular**: Cada estado registrado
- **Manejo de errores**: Rollback automático

## Constantes y Tipos

### Tipos de Ingresos (Income Types)
```php
const INCOME_TYPE_OPERATIONAL = '01';    // Ingresos por operaciones
const INCOME_TYPE_FINANCIAL = '02';      // Ingresos financieros
const INCOME_TYPE_EXTRAORDINARY = '03';  // Ingresos extraordinarios
const INCOME_TYPE_OTHER = '04';          // Otros ingresos
```

### Formas de Pago (Payment Methods)
```php
const PAYMENT_CASH = '01';               // Efectivo
const PAYMENT_CHECK = '02';              // Cheque
const PAYMENT_TRANSFER = '03';           // Transferencia
const PAYMENT_CARD = '04';               // Tarjeta
const PAYMENT_CREDIT = '05';             // Crédito
const PAYMENT_SWAP = '06';               // Permuta
const PAYMENT_CREDIT_NOTE = '07';        // Nota de crédito
const PAYMENT_OTHER = '08';              // Otros
```

### Medios de Transporte (Transport Means)
```php
const TRANSPORT_MARITIME = '01';         // Marítimo
const TRANSPORT_AIR = '02';             // Aéreo
const TRANSPORT_ROAD = '03';            // Terrestre
const TRANSPORT_RAIL = '04';            // Ferroviario
const TRANSPORT_POSTAL = '05';          // Postal
const TRANSPORT_MULTIMODAL = '06';      // Multimodal
const TRANSPORT_FIXED_INSTALLATION = '07'; // Instalación fija
const TRANSPORT_RIVER = '08';           // Fluvial
const TRANSPORT_OTHER = '09';           // Otros
```

### Monedas Soportadas
```php
const CURRENCY_USD = 'USD';              // Dólar estadounidense
const CURRENCY_EUR = 'EUR';              // Euro
const CURRENCY_CAD = 'CAD';              // Dólar canadiense
const CURRENCY_CHF = 'CHF';              // Franco suizo
const CURRENCY_GBP = 'GBP';              // Libra esterlina
const CURRENCY_JPY = 'JPY';              // Yen japonés
const CURRENCY_DOP = 'DOP';              // Peso dominicano
```

## Especificaciones API

### Endpoint de Exportación
- **URL**: `/export-supports`
- **Método**: POST
- **Autenticación**: Bearer Token (JWT)

### Estructura de Datos

#### Información Adicional (additionalInformation)
```php
[
    'shippingDocument' => 'string(50)',      // Documento de embarque
    'destinationCountry' => 'string(2)',     // Código país destino
    'transportMean' => 'string(2)',          // Medio de transporte
    'departurePort' => 'string(100)',        // Puerto de salida
    'customsInformation' => 'string(500)',   // Información aduanera
    'loadingPort' => 'string(100)',          // Puerto de carga
    'dischargePort' => 'string(100)',        // Puerto de descarga
    'exportDate' => 'YYYY-MM-DD',            // Fecha de exportación
    'exportValue' => 'decimal(10,2)',        // Valor de exportación
    'exportCurrency' => 'string(3)'          // Moneda de exportación
]
```

#### Comprador Extranjero
```php
[
    'rnc' => 'string(11)',                   // RNC (opcional para extranjeros)
    'foreignIdentifier' => 'string(50)',     // Identificador extranjero
    'companyName' => 'string(255)',          // Nombre de la empresa
    'contactName' => 'string(255)',          // Nombre de contacto
    'email' => 'email',                      // Email de contacto
    'phone' => 'string(20)',                 // Teléfono
    'address' => 'string(255)',              // Dirección completa
    'country' => 'string(2)',                // Código de país
    'city' => 'string(100)',                 // Ciudad
    'postalCode' => 'string(20)'             // Código postal
]
```

## Validaciones Específicas

### 1. Validación de Indicador de Facturación
- **Requerido**: Indicador 3 (ITBIS tasa 0%)
- **Aplicación**: Todos los ítems de la factura
- **Excepción**: No permitidos otros indicadores

### 2. Validación de Información de Exportación
- **Documento de embarque**: Obligatorio
- **País de destino**: Código ISO de 2 caracteres
- **Medio de transporte**: Valor válido según catálogo
- **Puertos**: Información completa de origen y destino

### 3. Validación de Comprador
- **Identificación**: RNC o identificador extranjero requerido
- **Empresa**: Nombre de compañía obligatorio
- **Contacto**: Email y teléfono requeridos
- **Ubicación**: País y ciudad obligatorios

### 4. Validación de Límites
- **Ítems**: Máximo 1,000 por factura
- **Formas de pago**: Máximo 7 por factura
- **Descripción**: Máximo 1,000 caracteres por ítem
- **Campos de texto**: Según especificaciones de longitud

## Casos de Uso

### 1. Exportación Marítima
```php
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'BL-001-2025',
        'destinationCountry' => 'US',
        'transportMean' => '01', // Marítimo
        'departurePort' => 'Puerto de Balboa',
        'loadingPort' => 'Puerto de Colón',
        'dischargePort' => 'Puerto de Miami'
    ],
    'buyer' => [
        'foreignIdentifier' => 'US123456789',
        'companyName' => 'Import Solutions Inc.',
        'country' => 'US',
        'city' => 'Miami'
    ]
];
```

### 2. Exportación Aérea
```php
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'AWB-567-2025',
        'destinationCountry' => 'CA',
        'transportMean' => '02', // Aéreo
        'departurePort' => 'Aeropuerto Tocumen',
        'customsInformation' => 'Productos electrónicos'
    ]
];
```

### 3. Exportación Terrestre
```php
$exportData = [
    'additionalInformation' => [
        'shippingDocument' => 'CRT-789-2025',
        'destinationCountry' => 'CR',
        'transportMean' => '03', // Terrestre
        'departurePort' => 'Frontera Paso Canoas'
    ]
];
```

## Manejo de Errores

### Errores de Validación
- **Estructura incompleta**: Campos obligatorios faltantes
- **Indicador incorrecto**: ITBIS diferente a 0%
- **Límites excedidos**: Más de 1,000 ítems o 7 formas de pago
- **Datos inválidos**: Formatos incorrectos en campos específicos

### Errores de API
- **Conexión PAC**: Problemas de conectividad
- **Autenticación**: Token inválido o expirado
- **Formato**: Estructura no aceptada por Alanube
- **Business Logic**: Reglas de negocio específicas del PAC

### Estrategias de Recuperación
1. **Reintentos automáticos**: 3 intentos con backoff
2. **Logging detallado**: Para análisis post-error
3. **Rollback**: Reversión automática en fallos
4. **Notificaciones**: Alertas a usuarios y administradores

## Testing

### Datos de Prueba
La clase `AlanubeDomExportInvoiceEnhancement` incluye el método `generateTestExportInvoiceData()` que genera:
- Estructura completa de factura de exportación
- Múltiples ítems con diferentes clasificaciones
- Información de transporte realista
- Datos de comprador extranjero válidos

### Escenarios de Prueba
1. **Factura básica de exportación**: Validación estructura mínima
2. **Exportación con múltiples ítems**: Prueba de límites
3. **Diferentes medios de transporte**: Validación de catálogos
4. **Compradores extranjeros**: Diferentes tipos de identificación
5. **Manejo de errores**: Validación de casos de fallo

## Configuración

### Variables de Entorno
```env
# Configuración específica para exportación
ALANUBE_DOM_EXPORT_TIMEOUT=300
ALANUBE_DOM_EXPORT_MAX_RETRIES=3
ALANUBE_DOM_EXPORT_QUEUE=alanube_dom
```

### Cola Redis
```php
// Configuración en config/queue.php
'alanube_dom' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'alanube_dom',
    'retry_after' => 300,
    'block_for' => null,
],
```

## Monitoreo y Logs

### Logs Principales
- **CreateExportInvoiceAlanubeDomJob**: Procesamiento asíncrono
- **AlanubeDomService**: Emisión y respuestas API
- **AlanubeDomExportInvoiceEnhancement**: Validaciones específicas

### Métricas de Seguimiento
- **Tiempo de procesamiento**: Por estado del job
- **Tasa de éxito**: Facturas procesadas exitosamente
- **Errores comunes**: Análisis de patrones de fallo
- **Throughput**: Facturas procesadas por hora

## Próximos Pasos

### Mejoras Planificadas
1. **Cache de validaciones**: Optimización de rendimiento
2. **Webhooks**: Notificaciones en tiempo real
3. **Reportes avanzados**: Análisis de exportaciones
4. **Integración aduanera**: Conexión con sistemas aduaneros

### Documentación Adicional
- **Guía de usuario**: Interface de usuario para exportaciones
- **API Documentation**: Especificaciones completas
- **Troubleshooting**: Guía de resolución de problemas
- **Best Practices**: Recomendaciones de uso

---

**Fecha de creación**: 2025-01-22  
**Versión**: 1.0.0  
**Autor**: DocuCenter Development Team  
**Última actualización**: 2025-01-22
