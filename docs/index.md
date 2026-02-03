# Bienvenido a la Documentación de DocuCenter

Esta es la documentación completa del sistema DocuCenter, organizada por categorías para facilitar la navegación y el mantenimiento.

## Estructura de la Documentación

### Guías de Usuario

- **[Workflow de Entrenamiento Document AI](./document-ai-training-workflow.md)** - Guía completa paso a paso para entrenar modelos custom de Google Document AI

###  [APIs](./api/)
Documentación completa de todas las APIs disponibles en el sistema:

- **[Facturación Electrónica (FE)](./api/fe/)** - APIs para emisión de documentos fiscales electrónicos
  - **[Comparación Tax Code: MEYPAR vs QuickBooks](./api/fe/meypar-quickbooks-tax-code-comparison.md)** - Documentación técnica comparativa del manejo de códigos de impuesto y campo origin
- **[Sage ACICloud](./api/sage-acicloud/)** - Integración con sistema ERP Sage
- **[Organizaciones](./api/organizations/)** - Gestión de organizaciones y configuraciones
- **[Ubicaciones](./api/locations/)** - Manejo de ubicaciones geográficas

### [Integraciones](./integrations/)
Documentación de integraciones con sistemas externos:

- **[Zoho Self Client](./integrations/zoho-self-client.md)** - Integración completa con Zoho Books API usando código directo
- **Lightspeed** - Integración con sistema POS
- **QuickBooks** - Sincronización bidireccional
- **Shopify** - Importación de órdenes

###  [Sistema de Testing](./testing/)
Documentación completa del sistema de testing y validaciones:

- **[Testing de Filtros de Configuración FE](./testing/test-configuration-filters.md)** - Testing de filtros de organización y usuario en configuración de facturación electrónica
- **[Testing de branch_code en Facturación](./testing/test-branch-code-implementation.md)** - Implementación y testing del campo branch_code para códigos de sucursal
- **[Testing de Clientes Extranjeros QuickBooks](./testing/quickbooks-foreign-customer-testing-guide.md)** - Guía completa de testing para creación de clientes extranjeros con pasaporte y país
- **[Comandos de Testing](../app/Console/Commands/Testing/)** - Comandos Artisan PHP y scripts bash centralizados
- **[Testing de Facturas de Exportación](./testing/alanube-dom-export-invoices-testing.md)** - Testing completo para Facturas de Exportación Electrónica (46)
- **[Testing de Webhooks](./testing/#-testing-de-webhooks)** - Pruebas de webhooks de pagos y integraciones
- **[Testing de Cálculos](./testing/#-testing-de-cálculos)** - Validación de cálculos de facturación sin PAC
- **[Script de Automatización Principal](../app/Console/Commands/Testing/testing.sh)** - Herramienta maestra para automatizar testing
- **[Configuración de Testing](./testing/#-configuración-avanzada)** - Configuración centralizada del sistema

### [Optimizaciones](./optimizations/)
Documentación sobre mejoras de performance y optimizaciones del sistema:

- Optimizaciones de consultas a base de datos
- Mejoras en el manejo de grandes volúmenes de datos
- Implementación de circuit breakers y patrones de resiliencia
- Optimizaciones de memoria y recursos

### [Validaciones](./validations/)
Documentación sobre validaciones específicas implementadas:

- Validaciones de documentos fiscales (RUC, DV)
- Reglas de negocio específicas
- Validaciones de formato y integridad de datos
- Controles de calidad de datos

### [Documentación Técnica](./technical/)
Análisis técnicos y soluciones a problemas específicos:

- **[Estandarización de Custom Fields](./technical/custom-fields-standardization.md)** - Implementación completa del estándar global para los 5 campos custom en facturación electrónica
- **[Sistema de Monitoreo del Servidor](./technical/server-monitoring-system.md)** - Sistema completo de monitoreo en tiempo real de recursos del servidor (disco, RAM, CPU)
- **[Resumen de Implementación - Monitoreo del Servidor](./technical/server-monitoring-implementation-summary.md)** - Resumen de implementación del sistema de monitoreo
- **[SOLUCIÓN COMPLETA: Cliente Solmary Identificación](./technical/receptor-extranjero-solmary-complete-solution.md)** - Solución completa con x-data + auto-llenado campos B406-B416
- **[SOLUCIONADO: Cliente Solmary No Mostraba Identificación](./technical/receptor-extranjero-solmary-fix.md)** - Fix x-data faltante para cliente extranjero
- **[RESUMEN FINAL COMPLETO - Unificación Receptor Extranjero](./technical/receptor-extranjero-complete-unification-summary.md)** - Estado final de toda la unificación backend + frontend
- **[Unificación Blade Receptor Extranjero B406-B416](./technical/blade-receptor-extranjero-unification.md)** - Eliminación de duplicación de campos similares en interfaz
- **[Unificación Backend Receptor Extranjero B406-B416](./technical/receptor-extranjero-b406-b416-unification.md)** - Implementación completa de campos DGI B406-B416 para receptores extranjeros con compatibilidad legacy
- **[Análisis de Unificación Receptor Extranjero](./technical/receptor-extranjero-unification-analysis.md)** - Análisis técnico de duplicación de campos y plan de unificación DGI
- **[Resumen Evaluación Receptor Extranjero](./technical/receptor-extranjero-evaluation-summary.md)** - Resumen completo de evaluación y resultados de unificación
- **[QuickBooks Foreign Customer Country Storage Fix](./technical/quickbooks-foreign-customer-country-storage-fix.md)** - Fix para almacenamiento correcto de país en clientes extranjeros de QuickBooks
- **[Control Auto-Emit FE en QuickBooks](./technical/auto-emit-fe-quickbooks-control.md)** - Sistema de control para emisión automática de FE desde create_sale_quickbooks
- **[Análisis de Números Fiscales](./technical/fiscal_number_analysis.md)** - Análisis técnico de extracción y validación de números fiscales del CUFE
- **[Facturas de Exportación Alanube DOM](./technical/alanube-dom-export-invoices.md)** - Implementación completa de Facturas de Exportación Electrónica (46)
- **[Reorganización de Comandos por Operación](./technical/comando-reorganization-by-operation-type.md)** - Análisis completo de 135+ comandos Artisan categorizados por tipo de operación
- **[Reorganización Física de Comandos](./technical/reorganizacion-fisica-comandos.md)** - Reorganización de 92 comandos en 7 directorios por tipo de operación
- **[Comandos NO Incluidos en Kernel](./technical/comandos-no-incluidos-kernel-por-operacion.md)** - 72 comandos organizados por tipo de operación que NO están en el scheduler automático
- **[Verificación de Comandos del Kernel](./technical/kernel-commands-verification-checklist.md)** - Lista de verificación y criterios para comandos incluidos en el scheduler del Kernel
- Correcciones de bugs y issues técnicos
- Implementaciones de funcionalidades complejas
- Análisis de casos edge y comportamientos especiales
- Soluciones de sincronización e integración

---

## Inicio Rápido

### Para Desarrolladores
1. Revisa la [documentación de APIs](./api/) para entender los endpoints disponibles
2. Consulta las [optimizaciones](./optimizations/) si trabajas con performance
3. Verifica las [validaciones](./validations/) para implementaciones que requieren validación de datos

### Para Integradores
1. Comienza con la [API de Facturación Electrónica](./api/fe/)
2. Revisa los ejemplos específicos para tu plataforma
3. Consulta la documentación técnica para casos especiales

### Para Administradores de Sistema
1. Revisa las [optimizaciones de MySQL](./optimizations/mysql-optimization-circuit-breaker.md)
2. Consulta la documentación de [organizaciones](./api/organizations/) para configuraciones
3. Verifica las validaciones para mantener integridad de datos

---

## ¿Cómo Contribuir?

1. **Nuevas APIs**: Agrega documentación en el directorio correspondiente dentro de `api/`
2. **Optimizaciones**: Documenta mejoras de performance en `optimizations/`
3. **Validaciones**: Agrega nuevas validaciones en `validations/`
4. **Issues Técnicos**: Documenta soluciones en `technical/`

Cada documento debe seguir las plantillas establecidas en cada directorio y incluir ejemplos prácticos.

---

**¿Dudas o sugerencias?** Abre un issue en [GitHub](https://github.com/aghabrego/docucenter-public/issues).
