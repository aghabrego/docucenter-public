# Documentación Técnica - DocuCenter

Este directorio contiene documentación técnica sobre implementaciones específicas, soluciones a problemas técnicos y análisis de funcionalidades del sistema DocuCenter.

## Índice de Documentación

### Estandarización

#### [custom-fields-standardization.md](./custom-fields-standardization.md)
**Descripción**: Implementación del estándar global para los 5 campos custom
**Contenido**:
- Definición del estándar global de Custom Fields
- Mapeo TIPO_RECEPTOR (CODEs vs IDs)
- Reglas de negocio para cada tipo de receptor
- Implementación por integración (QuickBooks, Shopify, Lightspeed, etc.)
- Helpers de validación: ReceiverTypeHelper y CustomFieldsValidator
- Módulos de emisión y Jobs asíncronos
- Testing y casos de prueba críticos
- Script de migración de datos

### Integraciones - QuickBooks

#### [quickbooks-webhook-organizationid-fix.md](./quickbooks-webhook-organizationid-fix.md)
**Descripción**: Fix de configuraciones duplicadas de webhook por uso inconsistente de IDs
**Contenido**:
- Análisis de causa raíz: ID numérico de QuickBooks vs String ID de DocuCenter
- Correcciones en Read.php y helper.php
- Script de limpieza de duplicados en Firestore
- Reglas de prevención y code review checklist
- Ejemplo real: RealmId 9341454854054771 con 2 configs (119 vs Rw8DunJnEnxY1MS2QHVH)

### Document AI (Google Cloud)

#### [document-ai-import-to-workbench.md](./document-ai-import-to-workbench.md)
**Descripción**: Sistema de importación de documentos anotados a Google Cloud Workbench
**Contenido**:
- Implementación completa de Import API
- Upload automático de PDFs a GCS
- Monitoreo de operaciones con polling
- División automática training/test (80/20)
- Guía de troubleshooting y debugging

#### [document-ai-import-implementation-summary.md](./document-ai-import-implementation-summary.md)
**Descripción**: Resumen ejecutivo de implementación Import to Workbench
**Contenido**:
- 7 archivos modificados/creados
- 4 nuevos métodos implementados
- Flujo de datos completo
- Comando de testing interactivo
- Métricas y estado final

### Soluciones de Problemas Técnicos

#### [ezeeissued-solution-summary.md](./ezeeissued-solution-summary.md)
**Descripción**: Solución completa al problema de campo EzeeIssued no actualizado tras emisión
**Contenido**:
- Análisis de causa raíz: problemas de conexión multi-tenant durante emisión
- Implementación de patrón de instancia única para consistencia de BD
- Herramientas de testing: script bash y comando Artisan
- Validación completa con datos reales

#### [getsaleproperty-optimizations-summary.md](./getsaleproperty-optimizations-summary.md)
**Descripción**: Optimizaciones completas del patrón getSaleProperty() en Create.php
**Contenido**:
- Aplicación de patrón de instancia única en 5 áreas críticas
- Optimización de flujos de emisión PAC (TheFactoryHKA, Alanube, Default)
- Mejoras en saveFileAndLog y extractAndStoreCufeAfterEmission
- Eliminación de problemas de conexión multi-tenant durante operaciones

#### [ezeeissued-field-correction-summary.md](./ezeeissued-field-correction-summary.md)
**Descripción**: Resumen técnico detallado de la corrección del campo EzeeIssued
**Contenido**:
- Optimizaciones implementadas en SalesHeaderImp model
- Cambios en flujos de emisión (TheFactoryHKA, Alanube, Default PAC)
- Patrones de conexión multi-tenant aplicados
- Documentación de testing y validación

### Integraciones Alanube DOM (República Dominicana)

#### [alanube-dom-service-usage.md](./alanube-dom-service-usage.md)
**Descripción**: Guía completa de uso del servicio AlanubeDomService con detección automática
**Contenido**:
- Detección automática de tipos de documento
- Uso por tipo específico (exportación, gubernamental, consumo, fiscal)
- Ejemplos de implementación y casos de uso
- Manejo de errores y mejores prácticas

#### [alanube-dom-export-invoices.md](./alanube-dom-export-invoices.md)
**Descripción**: Implementación completa de Facturas de Exportación Electrónica (46)
**Contenido**:
- Validaciones específicas de exportación (ITBIS 0%, transporte, aduanas)
- Procesamiento asíncrono con estados granulares
- Sistema de testing completo
- Documentación técnica y casos de uso

#### [ALANUBE_DOM_COMPLETE_INTEGRATION.md](./ALANUBE_DOM_COMPLETE_INTEGRATION.md)
**Descripción**: Documentación completa de la integración con Alanube DOM para República Dominicana
**Contenido**:
- Implementación de facturas de consumo (32)
- Implementación de facturas gubernamentales (45)
- Configuración de endpoints y autenticación
- Validaciones específicas por tipo de documento

#### [FISCAL_CREDIT_IMPLEMENTATION_SUMMARY.md](./FISCAL_CREDIT_IMPLEMENTATION_SUMMARY.md)
**Descripción**: Resumen completo de implementación de Facturas de Crédito Fiscal (31)
**Contenido**:
- Clase de enhancement AlanubeDomFiscalCreditEnhancement
- Servicio AlanubeDomService actualizado
- Job asíncrono CreateFiscalCreditInvoiceAlanubeDomJob
- Comando de testing interactivo
- Validaciones específicas de API
- Detección automática inteligente

### Sistema de Transacciones

#### [TRANSACTION_SYSTEM_COMPLETE.md](./TRANSACTION_SYSTEM_COMPLETE.md)
**Descripción**: Documentación completa del sistema de transacciones
**Contenido**:
- Arquitectura de transacciones
- Modelos y relaciones
- Validaciones y procesamiento

### Componentes Livewire y Frontend

#### [solucion-tabla-items-factura-electronica.md](./solucion-tabla-items-factura-electronica.md)
**Descripción**: Solución completa para visualización y gestión de ítems en facturación electrónica
**Contenido**:
- Problema: Tabla de ítems no visible sin datos previos
- Implementación de gestión manual de ítems (agregar/eliminar)
- Tabla completamente editable con recálculo automático
- Compatibilidad con facturas existentes y nuevas
- Casos de uso cubiertos y testing implementado
- Integración con sistemas de pago

### Gestión de Pagos y Cálculos

- **[payment-calculation-fix.md](./payment-calculation-fix.md)** - Análisis y solución de discrepancias en cálculo de pagos y vueltos
- **[solucion-sincronizacion-pagos.md](./solucion-sincronizacion-pagos.md)** - Implementación de sincronización de pagos entre sistemas

### Integración QuickBooks

#### [quickbooks-bills-job-graceful-validation.md](./quickbooks-bills-job-graceful-validation.md)
**Descripción**: Fix para validación graceful de tablas en CreateIntuitBillsJob
**Contenido**:
- Problema: Organizaciones sin módulo de compras generaban errores innecesarios
- Solución: Validación no-destructiva que retorna silenciosamente si faltan tablas
- Patrón de consistencia con UpdateIntuitOrdersJob
- Testing para organizaciones con/sin tablas de compras
- Logs informativos en lugar de errores de stack trace

#### [quickbooks-fiscal-number-validation-system.md](./quickbooks-fiscal-number-validation-system.md)
**Descripción**: Sistema completo de validación de números fiscales para prevenir duplicación de documentos QuickBooks
**Contenido**:
- Extracción automática de números fiscales desde CUFE panameño
- Validación de duplicados por cliente y número fiscal
- Integración en CreateSaleQuickBooksJob y FeController
- Comando de migración para bases de datos cliente
- Tests unitarios y de integración completos
- Arquitectura multi-tenant con manejo de conexiones dinámicas

## Implementaciones por Tipo de Documento

### Facturas Alanube DOM

| Tipo | Documento | Estado | Archivo Enhancement |
|------|-----------|--------|-------------------|
| 31 | Factura de Crédito Fiscal | Implementado | `AlanubeDomFiscalCreditEnhancement.php` |
| 32 | Factura de Consumo | Implementado | `AlanubeDomConsumerInvoiceEnhancement.php` |
| 45 | Factura Gubernamental | Implementado | `AlanubeDomGovernmentalEnhancement.php` |
| 46 | Factura de Exportación | Implementado | `AlanubeDomExportInvoiceEnhancement.php` |

### Características Técnicas

- **Detección Automática**: El sistema detecta automáticamente el tipo de documento basado en RNC y campos complejos
- **Validaciones Específicas**: Cada tipo tiene validaciones específicas según API de Alanube DOM
- **Procesamiento Asíncrono**: Jobs especializados para cada tipo de documento
- **Testing Interactivo**: Comando unificado con selección de tipo de documento

## Categorías de Documentación

### Soluciones Técnicas
Documentación que describe la resolución de problemas específicos encontrados en el sistema y las implementaciones técnicas correspondientes.

### Análisis de Funcionalidades
Análisis detallados de funcionalidades específicas del sistema, incluyendo casos edge y comportamientos especiales.

### Integraciones y Sincronizaciones
Documentación sobre procesos de sincronización entre diferentes sistemas y plataformas integradas.

### Integraciones SQL Server

#### [sql-server-complete-store-id-filtering.md](./sql-server-complete-store-id-filtering.md)
**Descripción**: Implementación completa de filtrado por Store ID en toda la cadena de jobs SQL Server
**Contenido**:
- Filtrado condicional en STInvoiceJob, STCostOfGoodsJob, STCostOfGoodsOfCategoryJob y STCreateSummaryJob
- Optimización de performance con reducción 70-90% de datos transferidos
- Sistema de logging detallado para debugging y monitoreo
- Compatibilidad total con configuraciones existentes
- Patrón de implementación reutilizable

**Características principales**:
- **Filtrado selectivo**: Procesa solo tienda específica cuando está configurada
- **Retrocompatibilidad**: Sin configuración procesa todas las tiendas
- **Performance optimizada**: Consultas SQL filtradas en origen
- **Logging granular**: Trazabilidad completa del procesamiento
- **Testing integrado**: Comandos de prueba y verificación

#### [sql-server-store-id-filtering.md](./sql-server-store-id-filtering.md)
**Descripción**: Implementación específica de filtrado por Store ID en STInvoiceJob
**Contenido**:
- Filtro condicional por store_id en facturas SQL Server
- Logging detallado para debugging y trazabilidad
- Preservación de Store ID en datos DocuCenter
- Optimización de consultas y transferencia de datos

## Estructura de Documentos

Cada documento técnico sigue esta estructura estándar:

1. **Problema Identificado** - Descripción del issue o requerimiento
2. **Análisis Técnico** - Investigación y análisis del problema
3. **Solución Implementada** - Detalles de la implementación
4. **Pruebas y Validación** - Casos de prueba y resultados
5. **Impacto y Consideraciones** - Efectos en el sistema

## Convenciones

- **Código de ejemplo**: Incluido para ilustrar implementaciones
- **Casos de prueba**: Documentados para validación futura
- **Referencias**: Enlaces a archivos de código relacionados
- **Logs de ejemplo**: Para facilitar debugging

### Implementación General de Documentos JSch09 iDoc

#### [general-document-types-implementation.md](./general-document-types-implementation.md)
**Descripción**: Implementación universal de tipos de documentos JSch09 iDoc para todos los PACs
**Contenido**:
- Soporte completo para 9 tipos oficiales DGI Panamá
- Compatibilidad universal con todos los PACs (Alanube, TheFactoryHKA, etc.)
- Sistema de detección automática de PAC y país
- Servicios de abstracción y validación
- Scripts de implementación y testing
- Arquitectura extensible para futuros PACs

**Características principales**:
- **PanamaDocumentTypesService**: Servicio central para tipos oficiales
- **ElectronicDocumentService**: Capa de abstracción PAC-agnóstica
- **Detección automática**: PAC y país detectados por configuración
- **Compatibilidad hacia atrás**: Mantiene funcionalidad existente
- **Extensibilidad**: Preparado para nuevos proveedores

**Script de implementación**:
```bash
./scripts/implement-general-document-types.sh
```

---

*Última actualización: Septiembre 2025*
