# Documentación Técnica - DocuCenter

## Índice de Documentación

### Resolución de Problemas Arquitecturales

#### Livewire UI/UX Improvements
- **[Preservación de Datos del Customer al Cambiar Tipo de Receptor](receptor-type-data-preservation.md)** - Preservación inteligente de datos en formulario ⭐ **NUEVO**
  - Problema: Cambiar tipo de receptor borraba completamente datos del customer
  - Solución: Preservación selectiva con limpieza solo de campos incompatibles  
  - Auto-re-llenado automático según nuevo tipo de receptor
  - Mejora significativa en experiencia de usuario (80% menos re-trabajo)

#### Customers Multi-Tenant Navigation
- **[Resolución de Dependencia Circular en Customer Property](customer-property-circular-dependency-resolution.md)** - Corrección de inconsistencia lógica en inicialización de customer ⭐ **NUEVO**
  - Identificación y análisis de dependencia circular en getCustomerProperty()
  - Implementación de obtención directa desde relaciones de venta
  - Corrección de errores de layout y compilación
  - Testing y validación de arquitectura multi-tenant

#### PAC Integration Errors
- **[Fix: Error "datosFacturaExportacion es requerido"](fix-datos-factura-exportacion-error.md)** - Corrección de nombre de campo para PAC Alanube ⭐ **NUEVO**
  - Error: PAC esperaba campo 'datosFacturaExportacion' pero recibía 'exportation'
  - Fix aplicado en AlanubeFormatterHelper para Panamá y República Dominicana
  - Facturas de exportación (tipo 03) ahora procesan correctamente
  - Scripts de testing y verificación incluidos

- **[Fix: Requerimiento dPaisExt de TheFactoryHKA](fix-thefactoryhka-dpaisext-requirement.md)** - Corrección para campo paisExtranjero requerido por PAC ⭐ **NUEVO**
  - Error: "El campo paisExtranjero es requerido" en operación interna a cliente extranjero
  - TheFactoryHKA requiere dPaisExt en gIdExt para todos los clientes extranjeros
  - Lógica automática para incluir dPaisExt según tipo de operación y nacionalidad
  - Testing completo con 5/5 casos de uso verificados

- **[Fix: Error Validación paisExtranjero en Operación Interna](fix-pais-extranjero-operacion-interna.md)** - Corrección para clientes extranjeros en operación interna ⭐ **IMPLEMENTADO**
  - Resolución de conflicto de validación para operación interna (iDest=1) a cliente extranjero (iTipoRec=4)
  - Validación condicional de paisExtranjero basada en tipo de operación
  - Lógica automática de asignación de receptor_paisNacionalidad para operación interna
  - Cumplimiento de normativas DGI para facturas a clientes extranjeros en territorio panameño

- **[Análisis Oficial Validaciones DGI](dgi-official-validation-analysis.md)** - Análisis de ficha técnica DGI vs error 2152 ⭐ **CRÍTICO**
  - Error 2152 NO EXISTE en especificación oficial DGI (232 páginas)
  - Código fuera de rangos oficiales DGI (0800-1059)
  - Confirmado: Bug propietario de TheFactoryHKA
  - Nuestro sistema cumple 100% estándares DGI
  
- **[Solución Definitiva PAC Error 2152](pac-2152-solution-external-service-bug.md)** - Workaround implementado para bug en servicio externo TheFactoryHKA ⭐ **IMPLEMENTADO**
  - Problema confirmado en servicio externo: mapeo incorrecto de campos totales
  - Workaround implementado en HKAService líneas 276-295
  - Corrección automática para totalValorRecibido y totalTodosItems
  - Logging detallado de correcciones aplicadas

- **[Corrección PAC Campos ISC Condicionales](thefactoryhka-isc-conditional-fields.md)** - Implementación de lógica condicional ISC según especificación oficial ⭐ **IMPLEMENTADO**
  - Corrección de "El campo valorISC es inválido" y "El campo totalISC no debe ser informado"
  - Lógica condicional para tasaISC, valorISC en items solo cuando tasa > 0
  - totalISC en totales solo cuando existe ISC real en items
  - Validación contra especificación oficial TheFactoryHKA
  
- **[Investigación Transformación SOAP PAC 2152](pac-2152-soap-transformation-investigation.md)** - Investigación del punto exacto de transformación incorrecta ⭐ **COMPLETADO**
  - Identificación de cadena de transformación Create.php → HKAService → SOAP → XML
  - Logging detallado agregado antes del envío SOAP
  - Análisis de hipótesis: filterNullValues, servicio externo, array conversion
  - Evidencia de mapeo cruzado donde múltiples campos toman valor totalPrecioNeto

- **[Resolución Error PAC 2152 - ITBMS Inválido](pac-2152-error-correction-progress.md)** - Corrección en progreso del error "Monto del ITBMS del ítem inválido" ⭐ **ARCHIVADO**
  - Análisis de inconsistencias en campos totales enviados al PAC
  - Corrección de campo dVTotItems (enviaba total general en lugar de suma de ítems)
  - Implementación de cálculo ITBMS con precisión DGI
  - Logging mejorado para debugging de valores enviados al PAC
  
- **[Análisis Original ITBMS TheFactoryHKA](itbms-calculation-correction.md)** - Documentación del análisis matemático inicial ⭐ **COMPLETADO**
  - Identificación de inconsistencia entre precio base (5.60), ITBMS (0.39) y total (5.99)
  - Métodos calculateCorrectITBMS() y validateITBMSCoherence() implementados
  - Testing con comando artisan test:itbms-calculation

- **[Resolución: Error "Attempt to assign property cufe on array"](cufe-property-assignment-error-resolution.md)** - Corrección de error de asignación de propiedades en HKAService ⭐ **COMPLETADO**
  - Análisis de conversión incompleta de array a objeto con (object)$request
  - Implementación de conversión recursiva con json_decode(json_encode())
  - Corrección en HKAService::writeXMLLog() y simplificación en Create.php
  - Validación de transmisión exitosa al PAC

### Análisis y Mapeos de Integración

#### Zoho Books Integration
- **[Zoho Purchase Order Mapping Analysis](zoho-purchase-order-mapping-analysis.md)** - Análisis completo del mapeo de órdenes de compra desde Zoho Books hacia modelos DocuCenter ⭐ **NUEVO**
  - Estructura de datos del webhook de Zoho
  - Mapeo hacia PurchaseHeaderImp y PurchaseDetailImp
  - Clases de transformación e importación
  - Scripts de testing y validación

- **[Segunda Verificación Custom Fields de Zoho desde API](zoho-vendor-api-second-verification.md)** - Implementación de consulta a API de Zoho para obtener cf_sagevendorid y cf_sagecustomerid ⭐ **ACTUALIZADO**
  - Doble verificación para vendors (cf_sagevendorid) y customers (cf_sagecustomerid)
  - Helper centralizado ZohoCustomFieldsHelper para ambos tipos de campos
  - Consulta directa a contacts API cuando custom_field_hash no incluye campos personalizados
  - Manejo robusto de errores con fallbacks y logging detallado

### Sistema de Control de Acceso Organizacional

1. **Control de Acceso para Configuraciones**
   - [Sistema de Control de Acceso Organizacional](organization-access-control-system.md) - Arquitectura completa del sistema
   - [Guía de Implementación](implementation-guide-organization-access.md) - Guía práctica paso a paso
   - [Resumen de Implementación](organization-access-implementation-summary.md) - Resumen ejecutivo y estado final

2. **Sistema de Gestión de Planes**
   - [Plan Management Complete Guide](plan-management-complete-guide.md) - Guía completa del sistema de gestión de planes
   - [Routes-as-Permissions Architecture](plan-management-complete-guide.md#system-architecture) - Arquitectura de rutas como permisos

### Integraciones PAC

1. **Eliminación del Campo Virtual pac_type**
   - [Resumen de Eliminación pac_type](pac-type-elimination-summary.md) - Eliminación sistemática del campo virtual pac_type

2. **API CheckRUC**
   - [Campo Type (Tipo de Contribuyente)](../api/fe/checkruc-type-field.md) - Detección automática de tipo de contribuyente

3. **Digifact Panamá**
   - [Guía de Implementación Digifact PAC](digifact-panama-pac-implementation-guide.md) - Análisis completo y guía de implementación para PAC Digifact ⭐ **NUEVO**
     - Análisis exhaustivo de API REST v1.0.4
     - Arquitectura de autenticación con JWT (30 días vigencia)
     - Endpoints de certificación y consulta
     - Plan de implementación por fases
     - Integración con arquitectura multi-tenant DocuCenter
     - Scripts de testing y validación

4. **Alanube República Dominicana**
   - [Mejoras DOM Consumer Invoice](alanube-dom-consumer-invoice-improvements.md) - Enhancements para el consumer DOM
   - [Testing System Summary](TESTING_SYSTEM_SUMMARY.md) - Framework de testing para Alanube DOM

5. **Alanube Panamá**
   - [Integración Completa Alanube Panamá](alanube-panama-complete-integration.md) - Implementación completa del servicio
   - [Guía de Uso del Servicio](alanube-panama-service-usage.md) - Manual de uso del AlanubeService
   - [Guía de Notas de Crédito](alanube-panama-credit-notes-guide.md) - Manual completo para notas de crédito
   - [Corrección Mapeo Datos Exportación](exportation-data-mapping-fix.md) - Fix para errores PAC en documentos de exportación ⭐ **NUEVO**

3. **QuickBooks Online**
   - [Job Creación de Bills](create-intuit-bills-job.md) - Sincronización de compras hacia QuickBooks como Bills
   - [Payment Lookup Integration](quickbooks-payment-lookup-integration.md) - Funcionalidad de búsqueda de pagos existentes para prevenir duplicados
   - [Payment Methods Integration](quickbooks-payment-methods-integration.md) - Sistema de determinación de métodos de pago basado en balance de QB
   - [Mejoras Manejo de Errores registerPaymentsQB](registerpayments-error-handling-improvements.md) - Sistema robusto de captura y manejo de errores ⭐ **NUEVO**
   - [Corrección Asignación País Clientes Extranjeros](quickbooks-country-assignment-fix.md) - Fix para preservar país original de clientes extranjeros ⭐ **NUEVO**
   - [Detección de Clientes Extranjeros](quickbooks-foreign-client-detection.md) - Sistema completo de detección y manejo de clientes extranjeros en QuickBooks ⭐ **NUEVO**
   - [Solución Rate Limiting HTTP 429](quickbooks-rate-limiting-solution.md) - Manejo robusto de rate limiting para APIs QuickBooks ⭐ **NUEVO**
   - [Sistema Inteligente de Prevención de Duplicación](quickbooks-intelligent-duplication-prevention.md) - Detección avanzada con algoritmo de similitud de nombres ⭐ **NUEVO**

4. **Sistemas de Validación**
   - [Validation System Overview](validation-system-overview.md) - Sistema de validaciones completo
   - [Alanube Validation](alanube-validation.md) - Validaciones específicas para Alanube
   - [Customer APIs Validation](customer-apis-validation.md) - Validación de integridad para APIs customer
   - [Customer APIs Validation Summary](customer-apis-validation-summary.md) - Resumen de implementación

### DGI Compliance & Formularios Condicionales

1. **Implementación 98% DGI Compliance**
   - [Formularios Condicionales JSch09](dgi-conditional-forms-jsch09.md) - Sistema completo de campos condicionales para 9 tipos de documento
   - [Validaciones Críticas DGI](dgi-critical-validations.md) - Validaciones automáticas obligatorias según ficha técnica
   - [Campos Adicionales Completos](dgi-additional-fields-complete.md) - 45+ campos adicionales para 98% compliance
   - [Fix clearTransactionTypeSale](dgi-cleartransactiontypesale-fix.md) - Solución a método faltante en componente Livewire ⭐ **NUEVO**

### Optimizaciones de Infraestructura

#### Laravel Framework & Job Management
- **[Error Laravel Builder::query() Correction](laravel-builder-query-error-correction.md)** - Corrección de patrones Eloquent Builder en SQL Server jobs ⭐ **IMPLEMENTADO**
  - Error: "Call to undefined method Illuminate\Database\Eloquent\Builder::query()"
  - Corrección de Builder::query() por Model::newQuery() en STCostOfGoodsOfCategoryJob
  - Patrones correctos para SQL Server data processing jobs
  - Validación de sintaxis y funcionalidad completa

#### Servidor de Producción
- **[Configuración Memoria Swap Debian 11](configuracion-swap-debian-produccion.md)** - Configuración completa de memoria swap para servidor de producción ⭐ **NUEVO**
  - Configuración de swap para resolver "MySQL server has gone away" errors
  - Optimización MySQL para ambiente de producción Debian 11 (bullseye)
  - Scripts de monitoreo de memoria y sistema
  - Guía específica para deployment sin Docker

### Optimizaciones

1. **Sistema de Búsqueda**
   - [Optimización Customer Search](customer-search-optimization.md) - Mejoras al search de customers
   - [Implementación Search Components](search-components-implementation.md) - Componentes de búsqueda

2. **Sistema de Estado de Transacciones**
   - [Enhanced Transaction Status System](enhanced-transaction-status-system.md) - Sistema mejorado de seguimiento de estado para transacciones FE

### Testing

1. **Framework de Testing**
   - [RegisterPaymentsQB Error Handling](registerpayments-error-handling-improvements.md) - Testing de manejo de errores registerPaymentsQB
   - Ver más documentación en la [sección Testing](../testing/README.md)

### Troubleshooting y Soluciones

1. **Errores de OrganizationService**
   - Ver documentación en la [sección Troubleshooting](../troubleshooting/README.md)

## Estructura de Archivos

Ver el índice principal [README.md](README.md) para la lista completa de documentación técnica organizada por categorías.

## Scripts de Automatización

Los scripts de automatización se encuentran en el repositorio principal del proyecto.

## Convenciones

- Toda la documentación técnica se mantiene en español
- Los archivos siguen el formato `feature-description.md`
- Se incluyen ejemplos de código y casos de uso
- Se documentan tanto implementación como testing
