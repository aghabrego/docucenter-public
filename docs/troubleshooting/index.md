# Índice de Troubleshooting - DocuCenter

## Errores Críticos Resueltos

### PAC Integration Errors
- **[Error PAC valorISC Inválido](error-pac-valorISC-invalido.md)** - Corrección de campos ISC condicionales TheFactoryHKA **RESUELTO**
  - Error: "En la ocurrencia [4] de Item, El campo valorISC es inválido"
  - Error: "El campo totalISC no debe ser informado"
  - Solución: Lógica condicional para campos ISC basada en especificación oficial
  - Implementado en HKAService con validación completa

### Laravel Framework Errors
- **[Error Laravel Builder::query() Method](error-laravel-builder-query-method.md)** - Corrección de patrones Eloquent incorrectos **RESUELTO**
  - Error: "Call to undefined method Illuminate\Database\Eloquent\Builder::query()"
  - Afectaba: SQL Server jobs (STCostOfGoodsOfCategoryJob, STCostOfGoodsJob)
  - Solución: Reemplazo de Builder::query() por Model::newQuery()
  - Patrón correcto para procesos de importación masiva

### PAC Country & Export Validation
- **[PAC Country Error Complete Resolution](pac-country-error-complete-resolution.md)** - Resolución completa errores de país **RESUELTO**
- **[Correcciones Críticas PAC 201](correcciones-criticas-pac-201.md)** - Suite de correcciones para error PAC 201 **RESUELTO**
- **[PAC Error 201 Final Resolution](pac-error-201-final-resolution.md)** - Resolución definitiva del error PAC 201 **RESUELTO**
- **[TheFactoryHKA Campos Prohibidos Extranjeros](thefactoryhka-campos-prohibidos-extranjeros.md)** - Campos que no se deben enviar a extranjeros **RESUELTO**

### Component & Property Errors
- **[Single Component Null Property Fix](single-component-null-property-fix.md)** - Fix para propiedades null en componentes únicos **RESUELTO**
- **[Organization Service Method Error Fix](organization-service-method-error-fix.md)** - Corrección de métodos faltantes en OrganizationService **RESUELTO**

### QuickBooks Integration
- **[QuickBooks Currency Validation Analysis](quickbooks-currency-validation-analysis.md)** - Análisis de validación de moneda QB **ANALIZADO**
- **[QuickBooks Export Validation Analysis](quickbooks-export-validation-analysis.md)** - Análisis de validación de exportación QB **ANALIZADO**

### System Diagnostics
- **[Kart Invoice Diagnosis](kart-invoice-diagnosis.md)** - Diagnóstico completo del sistema de facturas Kart **COMPLETADO**
- **[Kart Diagnostic System Summary](kart-diagnostic-system-summary.md)** - Resumen del sistema de diagnóstico Kart **DOCUMENTADO**

## Categorización por Tipo de Error

### Critical Production Issues
1. Error Laravel Builder::query() Method - **RESUELTO**
2. Error PAC valorISC Inválido - **RESUELTO**
3. PAC Error 201 Suite - **RESUELTO**

### PAC Integration Issues
1. TheFactoryHKA Campos Prohibidos - **RESUELTO**
2. PAC Country Error Resolution - **RESUELTO**
3. Corrección Campos Exportación - **RESUELTO**

### Component & Framework Issues
1. Single Component Null Property - **RESUELTO**
2. Organization Service Method Error - **RESUELTO**

### Diagnostic & Analysis
1. Kart System Diagnostics - **COMPLETADO**
2. QuickBooks Validation Analysis - **ANALIZADO**

## Patrones de Resolución Identificados

### Laravel Framework
- **Builder vs Model Patterns**: Usar Model::newQuery() en lugar de Builder::query()
- **Eloquent Relations**: Validar que las relaciones existan antes de usar métodos de query
- **Scope Application**: Verificar que los scopes se apliquen correctamente en queries complejas

### PAC Integration
- **Conditional Fields**: Implementar lógica condicional para campos opcionales según especificación
- **Country Validation**: Validar nacionalidad antes de incluir campos específicos de país
- **Export Rules**: Aplicar reglas específicas para facturas de exportación

### Component Architecture
- **Property Initialization**: Inicializar propiedades en mount() para evitar null references
- **Service Dependencies**: Verificar que todos los métodos de servicio estén disponibles
- **Multi-tenant Context**: Asegurar contexto organizacional correcto en todos los componentes

## Scripts de Diagnóstico Disponibles

### Testing & Validation
- `docs/testing/test-correccion-pac-totalISC-condicional.php` - Testing de corrección ISC condicional
- `docs/testing/test-builder-query-error-correction.php` - Testing de corrección Builder::query()

### Diagnostic Tools
- Scripts de diagnóstico Kart en `scripts/diagnose-kart-invoice.sh`
- Herramientas de análisis PAC en testing folder

## Notas de Implementación

### Commits Relevantes
- Corrección ISC condicional: Implementado en HKAService
- Builder::query() fix: Corregido en SQL Server jobs
- PAC compliance: Suite completa de validaciones

### Testing Strategy
- Validación de sintaxis en todos los cambios
- Testing específico para cada corrección PAC
- Verificación de funcionalidad en ambiente Docker

### Próximos Pasos
- Monitoreo de errores en producción
- Validación de performance en jobs SQL Server
- Seguimiento de compliance PAC en facturas reales
