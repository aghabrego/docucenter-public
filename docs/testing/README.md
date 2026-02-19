# 🧪 Sistema de Testing DocuCenter

## 📋 Scrip### 🧪 Scripts de Prueba Reutilizables

### � **[Implementación gPedComGl_dNroPed](gPedComGl-dNroPed-implementation.md)** 🆕
Implementación y testing del campo número de pedido de compra global en facturas electrónicas.

**Scripts disponibles**:
- `test-real-xml.php` - Verificar parsing básico del XML
- `test-full-import-gped.php` - Verificar extracción con getFirstfilterData()
- `test-import-simulation.php` - Simular flujo completo de importación

**Cambios incluidos**:
- ✅ Campo `gPedComGl_dNroPed` agregado a FeHeader model y base de datos
- ✅ Extracción implementada en ImportData.php (métodos xml() y xmlGT())
- ✅ **Fix crítico del parser XML.php** - Resuelve extracción de solo 2 claves
- ✅ Documentación completa con casos de prueba

**Uso rápido**:
```bash
# Verificar extracción básica
docker exec docucenter_laravel.test php /var/www/html/scripts/test-real-xml.php

# Prueba completa
docker exec docucenter_laravel.test php /var/www/html/scripts/test-full-import-gped.php

# Simulación de importación
docker exec docucenter_laravel.test php /var/www/html/scripts/test-import-simulation.php
```

### �🔍 **[API Consulta de RUC](../api/check-ruc-api.md)** 🆕
Testing de la nueva API para consulta de RUCs panameños usando Alanube.

**Scripts disponibles**:
- `test-check-ruc.sh` - Script completo de testing con múltiples casos
- Incluye validación de formatos, errores y respuestas exitosas

**Uso rápido**:
```bash
# Prueba con configuración por defecto
./scripts/test-check-ruc.sh

# Prueba específica
./scripts/test-check-ruc.sh 1 "8-123-456" "tu-token"

# Prueba manual con curl
curl -X GET "http://localhost:8000/api/v1/fe/check_ruc/8-123-456" \
  -H "Authorization: Bearer tu-token" \
  -H "Accept: application/json"
```

### 🎯 **[iDoc Preservation Testing](test-idoc-6-preservation.md)** 🆕
Pruebas especializadas para verificar conservación del campo `iDoc` en API ACI Cloud.

**Scripts disponibles**:
- `test:idoc-preservation` - Comando Laravel para prueba completa
- `quick-test-idoc-6.sh` - Script rápido automatizado
- `test-idoc-preservation.sh` - Script principal con opciones
- `demo-idoc-test.sh` - Demostración interactiva

**Uso rápido**:
```bash
# Prueba rápida
./scripts/quick-test-idoc-6.sh {org_id}

# Prueba completa
php artisan test:idoc-preservation {org_id}

# Demostración interactiva
./scripts/demo-idoc-test.sh
```

### 📊 **[QuickBooks Integration Testing](quickbooks-testing-scripts.md)**
Suite completa de scripts para testing de integración QuickBooks con validación de impuestos ITBMS.

**Scripts disponibles**:
- `test-qb-tax-validation.php` - Validación simplificada de cálculos
- `test-qb-tax-real-data.php` - Pruebas con datos reales QB  
- `test-qb-online-service.php` - Testing del QuickBooksOnlineService
- `test-qb-request-processing.php` - Validación del form request
- `test-qb-invoice-creation-flow.php` - Flujo completo end-to-end
- `test-qb-direct-api.php` - Prueba directa de API QB
- `test-qb-flow.sh` - Script bash para testing rápido

**Uso rápido**:
```bash
# Validación básica
php docs/testing/test-qb-tax-validation.php

# Flujo completo
./docs/testing/test-qb-flow.sh 2
```

### 🎯 **[QuickBooks Multi-Tax con rateValue - PRUEBA01](PRUEBA01-test-results.md)** 🆕
Testing completo del sistema de multi-tasa ITBMS usando PRIORIDAD 1 (rateValue) sin dependencia de ACI Cloud API.

**Scripts disponibles**:
- `test-qb-multi-tax-prueba01.php` - Test automatizado completo con validación
- `test-qb-tax-calculation-simple.sh` - Script visual de verificación de lógica
- `docker-cheatsheet.sh` - Utilidad para ejecutar comandos en contenedores
- [PRUEBA01-verification-guide.md](PRUEBA01-verification-guide.md) - Guía paso a paso

**Resultado del Test**:
```
✅ Línea 1: $1.00 × 10% = $0.10 CORRECTO
✅ Línea 2: $1.00 × 7% = $0.07 CORRECTO
✅ Total ITBMS: $0.17 CORRECTO
✅ Método: tax_code_rate_value (PRIORIDAD 1)
✅ Sin llamadas a ACI Cloud API
```

**Uso rápido**:
```bash
# Encontrar contenedor
bash docs/testing/docker-cheatsheet.sh find

# Ejecutar test completo
bash docs/testing/docker-cheatsheet.sh test-prueba01

# Ver logs de impuestos en tiempo real
bash docs/testing/docker-cheatsheet.sh logs-tax

# Script visual de lógica
bash docs/testing/test-qb-tax-calculation-simple.sh
```

**Documentación completa**: [PRUEBA01-test-results.md](PRUEBA01-test-results.md)

### 🎯 **Validaciones PAC - Alanube Panamá**PHP de Testing

### `check_sales.php`
Script de verificación de facturas para diagnóstico de base de datos.

**Uso**:
```bash
# Desde la raíz del proyecto
php docs/testing/check_sales.php
```

**Funcionalidad**:
- Conecta directamente a la base de datos de organización específica
- Busca facturas por número específico (12720)
- Muestra las últimas facturas creadas
- Útil para debugging de emisión PAC

### 🎯 [Sistema Principal de Testing](../../app/Console/Commands/Testing/README.md)
Documentación completa del sistema de testing implementado en `app/Console/Commands/Testing/`

### 📊 [Resumen Ejecutivo](../TESTING_SYSTEM_SUMMARY.md)
Resumen completo de la implementación del sistema de testing con todas las funcionalidades.

### 🚀 [Comandos de Testing](../../app/Console/Commands/Testing/README.md) ⭐
Colección completa de comandos Artisan PHP y scripts bash para testing automático.

### 🔧 [Script de Automatización Principal](../../app/Console/Commands/Testing/testing.sh)
Script bash maestro para automatizar flujos completos de testing con Docker.esting DocuCenter

Documentación completa del sistema de testing y validaciones de DocuCenter.

## 📋 Índice de Testing

### 🎯 [Sistema Principal de Testing](../app/Console/Commands/Testing/README.md)
Documentación completa del sistema de testing implementado en `app/Console/Commands/Testing/`

### 📊 [Resumen Ejecutivo](../TESTING_SYSTEM_SUMMARY.md)
Resumen completo de la implementación del sistema de testing con todas las funcionalidades.

### � [Comandos de Testing](commands/README.md) 🆕
Colección completa de scripts bash para testing específico y validaciones automáticas.

### �🔧 [Script de Automatización Principal](commands/testing.sh)
Script bash maestro para automatizar flujos completos de testing con Docker.

## 🧪 Scripts de Prueba Reutilizables

### � **Validaciones PAC - Alanube Panamá**

#### `test_dni_vs_ruc_analysis.php`
**Propósito**: Análisis detallado del problema de validación DNI vs RUC para tipos de receptor.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test_dni_vs_ruc_analysis.php
```

**Funcionalidades**:
- Análisis del patrón DNI vs formato RUC empresarial
- Identificación de inconsistencias tipo-formato
- Validación de longitudes por partes del RUC
- Propuestas de solución automática

#### `test_official_documentation.php`
**Propósito**: Verificación con documentación oficial de tipos de receptor según Panamá.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test_official_documentation.php
```

**Funcionalidades**:
- Consulta de tipos oficiales: 01=Contribuyente, 02=Consumidor final
- Validación de auto-detección corregida
- Casos de prueba con diferentes formatos RUC
- Verificación de consistencia PAC

#### `test_auto_detection.php`
**Propósito**: Testing de auto-detección de tipos de receptor en facturas.

### 🔧 **Testing de Transformadores**

#### `test-close-po-conversion.sh`
**Propósito**: Validación de conversión booleana en PurOrdrHeaderTransform.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
./docs/testing/test-close-po-conversion.sh
```

**Funcionalidades**:
- Test completo de conversión (bool) para campo Close_PO
- Validación de tipos: int, string, boolean, null
- Ejecución automática con Docker
- Limpieza automática de archivos temporales
- Verificación de salida JSON del transformer

**Casos de Prueba**:
- Integer 1 → true
- Integer 0 → false  
- String "1" → true
- String "0" → false
- Boolean true → true
- Boolean false → false
- NULL → false
- Empty string → false

#### `TestPurOrdrHeaderTransformSimple.php`
**Propósito**: Comando de test reutilizable para validación booleana.

### 🆔 **Testing de Identificadores de Transacción**

#### `test-parent-transaction-id.sh`
**Propósito**: Validación de funcionalidad ParentTransactionId en CustomerCreditMemoHeaderImp.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
./docs/testing/test-parent-transaction-id.sh
```

**Funcionalidades**:
- Verificación de columna ParentTransactionId en base de datos
- Validación de tipo de datos: bigint(20) NULL DEFAULT NULL
- Verificación de configuración en modelo y request
- Ejemplo de estructura JSON para API
- Testing automático con Docker

**Características Validadas**:
- ✅ Columna existe en tablas de organizaciones
- ✅ Tipo: bigint(20) nullable con default NULL
- ✅ Incluido en fillable del modelo
- ✅ Regla de validación: nullable|integer
- ✅ Compatibilidad con API ACIcloud

#### `test-kart21-improvements.sh`
**Propósito**: Validación de mejoras implementadas en Kart21Service para resolver discrepancias de precisión decimal.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
./docs/testing/test-kart21-improvements.sh
```

**Funcionalidades**:
- Validación de precisión decimal corregida (2→3 decimales)
- Verificación de análisis automático de discrepancias
- Testing de rendimiento y logging
- Confirmación de preservación de datos originales

**Mejoras Validadas**:
- ✅ Configuración decimal: `totals: 3 decimales` (antes 2)
- ✅ Análisis automático: `analyzeDataDiscrepancies()` implementado
- ✅ Logging: Registro automático de inconsistencias
- ✅ Precisión: Valores como `17.013` preservados correctamente
- ✅ Rendimiento: Tiempo de ejecución ~13-25ms

**Características Validadas**:
- ✅ Eliminación de pérdida de precisión en totales
- ✅ Detección automática de discrepancias > 0.001
- ✅ Logging estructurado para debugging
- ✅ Compatibilidad con datos reales de Kart21

#### `TestPurOrdrHeaderTransformSimple.php`
**Propósito**: Prueba del sistema de auto-detección de tipos de receptor.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test_auto_detection.php
```

**Funcionalidades**:
- Detección automática basada en formato RUC
- Casos de prueba: empresa, DNI, extranjero
- Validación de correcciones aplicadas
- Análisis del caso problemático específico

#### `test_user_data_analysis.php`
**Propósito**: Análisis específico de los datos exactos del usuario que causaban error PAC.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test_user_data_analysis.php
```

**Funcionalidades**:
- Procesamiento de JSON real del usuario
- Validación de auto-detección aplicada
- Verificación de estructura final para PAC
- Confirmación de resolución del error

### �📄 **Facturas de Crédito Fiscal - Alanube DOM**

#### `test_fiscal_credit_detection.php`
**Propósito**: Prueba de detección automática de tipos de documento para facturas de crédito fiscal.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test_fiscal_credit_detection.php
```

**Funcionalidades**:
- Detección automática de tipos: 31 (Crédito Fiscal), 32 (Consumo), 45 (Gubernamental)
- Validación de RNC empresarial vs gubernamental
- Pruebas de indicadores complejos de crédito fiscal

#### `test_fiscal_credit_complete.php`
**Propósito**: Prueba completa de validaciones y características de facturas de crédito fiscal.

**Uso**:
```bash
cd /home/weirdolabs/code/docucenter
php docs/testing/test_fiscal_credit_complete.php
```

**Funcionalidades**:
- Validación de RNC empresarial obligatorio
- Verificación de campos complejos (retenciones, percepciones, impuestos adicionales)
- Validación de límites de API (1000 items, 7 formas pago, 20 impuestos)
- Verificación de tipos de ingreso (01-06) y pago (1-3)
- Análisis de múltiples formas de pago y monedas extranjeras

**Datos de Prueba Incluidos**:
- RNC empresarial válido: 40112345678
- Múltiples formas de pago (transferencia, efectivo, cheque)
- Retenciones y percepciones fiscales
- Impuestos adicionales
- Soporte para monedas extranjeras (USD)

### 📝 **Instrucciones de Reutilización**

1. **Para desarrollo**: Copiar scripts a root temporalmente para pruebas rápidas
2. **Para CI/CD**: Referenciar desde `docs/testing/` en scripts de automatización
3. **Para documentación**: Mantener siempre en `docs/testing/` con instrucciones claras

---

## 🧪 Tipos de Testing Disponibles

### 🌐 **Testing de Webhooks**
- **[CreateSaleMaxgymJob](../app/Console/Commands/Testing/TestCreateSaleMaxgymJob.php)** - Testing de webhooks de pagos Maxgym
- **Datos de Prueba**: [test_payment_data.json](../storage/testing/data/test_payment_data.json)

### 🧮 **Testing de Cálculos**  
- **[CreateFastJobCalculation](../app/Console/Commands/Testing/TestCreateFastJobCalculation.php)** - Cálculos de facturación sin PAC
- **[Trait de Cálculos](../app/Traits/CreateFastJobCalculation.php)** - Lógica de cálculo independiente

### 🏗️ **Testing de Infraestructura**
- **[CreateTableFromStub](../app/Console/Commands/Configuration/CreateTableFromStubCommand.php)** - Creación automática de tablas
- **[Configuración](../config/testing.php)** - Configuración centralizada de testing

---

## 🚀 Inicio Rápido

### Opción 1: Script de Automatización (Recomendado)
```bash
# Configurar entorno completo
./scripts/testing.sh setup 1

# Ejecutar todas las pruebas
./scripts/testing.sh test-all 1

# Ver estado del sistema
./scripts/testing.sh status
```

### Opción 2: Comandos Individuales
```bash
# Ver índice de comandos
docker-compose exec laravel.test php artisan testing:index

# Testing de webhook Maxgym
docker-compose exec laravel.test php artisan test:create-sale-maxgym --sync

# Testing de cálculos de facturación
docker-compose exec laravel.test php artisan test:create-fast-calculation --show_details
```

---

## 📁 Validaciones Específicas

### ✅ [Validaciones MEYPAR](./validations/meypar-validations.md)
Documentación completa de validaciones para la integración MEYPAR.

### ✅ [Validaciones RUC](./validations/createSale-ruc-validation.md)
Validaciones de RUC en el proceso de creación de ventas.

### ✅ [Validaciones Maxgym](./validations/maxgym-ruc-validation.md)
Validaciones específicas para webhooks de Maxgym.

### ✅ [Comparación de Validaciones](./validations/validation_comparison.md)
Análisis comparativo de diferentes sistemas de validación.

---

## 🔄 Flujos de Testing

### 🎯 **Testing de Desarrollo**
1. **Configuración**: `./scripts/testing.sh setup 1`
2. **Prueba específica**: `./scripts/testing.sh test-maxgym 1`
3. **Verificación**: `./scripts/testing.sh test-calculations 1`

### 🧪 **Testing Completo**
1. **Estado del sistema**: `./scripts/testing.sh status`
2. **Todas las pruebas**: `./scripts/testing.sh test-all 1`
3. **Revisión de logs**: Verificar `storage/logs/laravel.log`

### 🔍 **Debugging Avanzado**
1. **Detalles completos**: `--show_details` en comandos
2. **Logs específicos**: Revisar output de comandos individuales
3. **Tablas faltantes**: `./scripts/testing.sh create-table [tabla] 1`

---

## 📊 Reportes de Testing

### 🎯 **Resultados Esperados**

#### ✅ Testing Exitoso de Webhooks
```
✓ Datos JSON cargados correctamente
✓ Organización encontrada
✓ Usuario encontrado
✓ Job ejecutado exitosamente
```

#### ✅ Testing Exitoso de Cálculos
```
✓ Cálculos completados exitosamente
✓ Total general coincide
✓ Cantidad de items coincide
✓ ITBMS coincide
✓ Todos los cálculos son consistentes
```

---

## 🛠️ Configuración Avanzada

### ⚙️ [Configuración de Testing](../config/testing.php)
- Configuraciones predeterminadas
- Rutas de archivos de datos
- Configuración de debugging
- Mocks para testing
- Validaciones de estructura

### 🔧 Variables de Entorno
```bash
TESTING_DEFAULT_ORG_ID=1
TESTING_SYNC_EXECUTION=true
TESTING_SHOW_DETAILS=false
TESTING_LOG_LEVEL=debug
```

---

## 🐛 Troubleshooting

### ❌ Errores Comunes

#### "Tabla no existe"
```bash
./scripts/testing.sh create-table [nombre_tabla] 1
```

#### "Organización no encontrada"
```bash
# Verificar organizaciones disponibles
docker-compose exec laravel.test php artisan tinker
>>> App\Models\Organization::all(['id', 'name', 'database'])
```

#### "Error de conexión PAC"
Los comandos de testing están diseñados para **NO** usar PAC. Verificar que se usen los comandos del directorio Testing.

---

## 📚 Documentación Adicional

- **[README Principal](../README.md)** - Información general del proyecto
- **[Copilot Instructions](../.github/copilot-instructions.md)** - Guías para AI/Copilot
- **[Scripts Disponibles](../scripts/README.md)** - Documentación de todos los scripts

---

## 🏆 Beneficios del Sistema

### 🛡️ **Seguridad**
- Testing sin afectar producción
- Sin conexiones PAC durante testing
- Validaciones robustas

### 📈 **Productividad**
- Configuración automática
- Scripts simplificados
- Documentación completa

### 🔧 **Mantenibilidad**
- Código organizado
- Configuración centralizada
- Documentación actualizada
