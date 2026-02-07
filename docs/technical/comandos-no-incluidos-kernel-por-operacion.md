# Comandos NO Incluidos en el Kernel - Organizados por Tipo de Operación

## Resumen

Este documento categoriza todos los comandos **NO incluidos** en el Kernel (`app/Console/Kernel.php`) por tipo de operación. Estos comandos están disponibles para ejecución manual o programática según necesidades específicas.

### Comandos Ya Incluidos en el Kernel (19 comandos)
Los siguientes comandos YA están incluidos en el scheduler automático:
- `word:clear-log`
- `maintenance:clean-transactions`
- `maintenance:clean-archives`
- `queue:prune-batches`
- `word:create-access-token`
- `word:create-access-token-apc`
- `word:create-access-token-serie-r`
- `word:type-payment-lightspeed`
- `word:update-invu-pos-module`
- `word:update-lightspeed-serie-r`
- `word:update-lightspeed-module`
- `word:update-sql-server-module`
- `word:update-intuit-orders`
- `word:extract-organization-configuration-emails`
- `word:extract-organization-configuration-pac`
- `fe:verify-or-issue-faith-from-issuance`
- `word:correct-electronic-invoices-with-errors`
- `update:appointment-status`
- `ezete:process`
- `word:update-booqable-module` (condicional)

---

## 1. DIAGNÓSTICO Y DEBUG (32 comandos)

### Comandos de Análisis de Facturas
- **`app:diagnose-kart-invoice`** - Diagnóstico detallado de facturas Kart
- **`app:trace-invoice-number-flow`** - Rastreo del flujo de numeración de facturas
- **`app:debug-invoice-number-mapping`** - Debug del mapeo de números de factura
- **`app:analyze-lightspeed-credit-note`** - Análisis de notas de crédito Lightspeed
- **`app:validate-lightspeed-credit-note-fix`** - Validación de correcciones de notas de crédito

### Comandos de Debug de Integraciones
- **`app:debug-create-fast-job-vuelto`** - Debug de jobs rápidos con vuelto
- **`app:debug-the-factory-hka-request`** - Debug de requests a TheFactoryHKA
- **`app:test-the-factory-hka-payload`** - Testing de payloads TheFactoryHKA
- **`app:fix-acicloud-receptor-issues`** - Corrección de problemas con receptores ACICloud

### Comandos de Testing de APIs
- **`testing:test-alanube-connection`** - Testing de conexión Alanube
- **`testing:test-acicloud-real-data`** - Testing con datos reales ACICloud
- **`testing:test-acicloud-registration`** - Testing de registro ACICloud
- **testing:test-acicloud-service`** - Testing del servicio ACICloud
- **`testing:test-acicloud-api-with-emission`** - Testing API ACICloud con emisión
- **`testing:test-aci-cloud-registration`** - Testing de registro ACICloud alternativo

### Comandos de Testing Específicos (Kart/Meypar)
- **`app:test-kart-real-data`** - Testing con datos reales de Kart
- **`app:test-kart-real-api`** - Testing de API real de Kart
- **`app:test-kart-to-zoho`** - Testing de integración Kart a Zoho
- **`testing:test-kart21-decimal-precision`** - Testing de precisión decimal Kart21
- **`testing:test-kart21-service`** - Testing del servicio Kart21
- **`testing:test-meypar-payments`** - Testing de pagos Meypar
- **`testing:test-meypar-quantity-only`** - Testing solo cantidades Meypar
- **`testing:test-meypar-real-flow`** - Testing de flujo real Meypar
- **`testing:meypar-test-complete`** - Testing completo Meypar
- **`testing:debug-meypar-emission`** - Debug de emisión Meypar
- **`testing:test-meypar-specific-data`** - Testing con datos específicos Meypar
- **`testing:test-meypar-normalization`** - Testing de normalización Meypar
- **`testing:test-meypar-api-normalization`** - Testing de normalización API Meypar
- **`testing:test-meypar-only-processing`** - Testing solo procesamiento Meypar

### Comandos de Testing de Emisión
- **`testing:test-lightspeed-pac-emission`** - Testing de emisión PAC Lightspeed
- **`testing:test-lightspeed-pac-emission-real`** - Testing real de emisión PAC Lightspeed
- **`testing:test-lightspeed-credit-note`** - Testing de notas de crédito Lightspeed
- **`testing:test-invoice-quantity-normalization`** - Testing de normalización de cantidades
- **`testing:test-transaction-system`** - Testing del sistema de transacciones
- **`testing:test-api-receptor-problem`** - Testing de problemas con API receptor
- **`testing:test-original-problem`** - Testing del problema original

### Comandos de Testing de Servicios
- **`testing:test-alanube-service`** - Testing del servicio Alanube
- **`testing:test-alanube-dom-service`** - Testing del servicio Alanube DOM
- **`testing:create-alanube-config-and-test-meypar`** - Configuración Alanube y testing Meypar

### Comandos de Testing Wrapper/Jobs
- **`testing:create-fast-job-wrapper`** - Wrapper para jobs rápidos
- **`testing:create-fast-job-wrapper-fixed`** - Wrapper corregido para jobs rápidos
- **`testing:test-create-fast-job-calculation`** - Testing de cálculos en jobs rápidos

### Comando de Índice de Testing
- **`testing:testing-index`** - Índice de todos los comandos de testing

---

## 2. INTEGRACIONES EMPRESARIALES (8 comandos)

### Zoho Integration
- **`app:test-zoho-connection`** - Testing de conexión con Zoho Books API

### Intuit/QuickBooks Integration
- **`app:upload-sales-intuit`** - Subida de ventas a Intuit QuickBooks
- **`app:upload-sales-general-diary-intuit`** - Subida de diario general de ventas a Intuit
- **`app:intuit-sync-status`** - Status de sincronización con Intuit

### Kart Integration
- **`app:emit-kart-order`** - Emisión de órdenes Kart
- **`app:emit-kart-with-create-fast-job`** - Emisión Kart con job rápido
- **`app:emit-kart-with-cash-payment`** - Emisión Kart con pago en efectivo
- **`app:force-kart-invoice-emission`** - Forzar emisión de facturas Kart

---

##  3. SINCRONIZACIÓN POS/INVENTARIO (15 comandos)

### Invupos Commands
- **`app:customer-invupos`** - Sincronización de clientes Invupos
- **`app:category-invupos`** - Sincronización de categorías Invupos
- **`app:sub-category-invupos`** - Sincronización de subcategorías Invupos
- **`app:type-payment-invupos`** - Sincronización de tipos de pago Invupos
- **`app:product-invupos`** - Sincronización de productos Invupos
- **`app:purchase-category-invupos`** - Sincronización de categorías de compra Invupos
- **`app:provider-invupos`** - Sincronización de proveedores Invupos

### Lightspeed Commands
- **`app:typepayment-lightspeed`** - Sincronización de tipos de pago Lightspeed (manual)

### General POS Commands
- **`app:credit-notes`** - Gestión de notas de crédito
- **`app:purchase-orders`** - Gestión de órdenes de compra
- **`app:sales-orders`** - Gestión de órdenes de venta
- **`app:item-menu`** - Gestión de elementos del menú

### Import/Export Commands
- **`app:import-data`** - Importación general de datos
- **`app:export-data`** - Exportación general de datos

### Summary Commands
- **`app:create-sales-order-summary`** - Crear resumen de órdenes de venta
- **`app:create-credit-notes-summary`** - Crear resumen de notas de crédito

---

## 4. CONFIGURACIÓN Y MANTENIMIENTO (5 comandos)

### Configuración de Base de Datos
- **`config:alter-column-increment`** - Alterar columnas con auto-incremento
- **`config:add-column-to-organizations-table`** - Agregar columnas a tabla de organizaciones
- **`config:create-table-from-stub`** - Crear tablas desde templates
- **`config:remove-columns-gje-header-imp`** - Remover columnas específicas GJE Header
- **`config:remove-column-to-organizations-table`** - Remover columnas de tabla de organizaciones

---

## 5. ANÁLISIS Y REPORTES (1 comando)

### Análisis de Datos Específicos
- **`app:panama-daily-entry`** - Análisis de entradas diarias de Panamá

---

##  6. CATEGORÍA ESPECIAL - TESTING CENTRALIZADO

Los comandos de testing están centralizados en el directorio `app/Console/Commands/Testing/` para facilitar su gestión y reutilización.

---

## Estadísticas de Comandos

- **Total de comandos en el proyecto**: 91
- **Comandos incluidos en Kernel**: 19 (21%)
- **Comandos NO incluidos**: 72 (79%)

### Distribución por Categoría (comandos NO incluidos)
- **Diagnóstico y Debug**: 32 comandos (44%)
- **Sincronización POS**: 15 comandos (21%)
- **Integraciones Empresariales**: 8 comandos (11%)
- **Configuración**: 5 comandos (7%)
- **Análisis y Reportes**: 1 comando (1%)
- **Otros**: 11 comandos (15%)

---

## Recomendaciones

### Comandos Candidatos para Automatización
Los siguientes comandos podrían considerarse para inclusión en el Kernel según necesidades específicas:

1. **`app:test-zoho-connection`** - Para monitoreo continuo de conectividad
2. **`app:panama-daily-entry`** - Para procesamiento diario automático
3. **`app:diagnose-kart-invoice`** - Para diagnóstico preventivo

### Comandos de Solo Ejecución Manual
Los comandos de testing y debug deben mantenerse como ejecución manual para evitar efectos secundarios en producción.

### Comandos de Configuración
Los comandos de configuración de BD deben ejecutarse solo cuando sea necesario realizar cambios estructurales.

---

## Notas de Implementación

- Todos los comandos de testing están centralizados en `Testing/` para mejor organización
- Los comandos de configuración modifican estructura de BD y requieren cuidado especial
- Los comandos de integración pueden requerir configuración específica por organización
- Los comandos de sincronización POS pueden tener dependencias externas

---

**Última actualización**: 31 de agosto de 2025
**Comandos analizados**: 91 total (19 en Kernel, 72 fuera del Kernel)
