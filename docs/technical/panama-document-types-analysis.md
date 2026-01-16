# Análisis de Tipos de Documento para Emisión FE - Panamá

## 📋 Situación Actual

Según la documentación oficial (JSch09 iDoc), los tipos de documento soportados oficialmente son:

| Código | Descripción | Estado Actual |
|--------|-------------|---------------|
| **01** | Factura de operación interna | ✅ Implementado |
| **02** | Factura de importación | ⚠️ Parcial |
| **03** | Factura de exportación | ⚠️ Parcial |
| **04** | Nota de Crédito referente a una o varias FE | ⚠️ Parcial |
| **05** | Nota de Débito referente a una o varias FE | ❌ No implementado |
| **06** | Nota de Crédito genérica | ✅ Implementado |
| **07** | Nota de Débito genérica | ❌ No implementado |
| **08** | Factura de Zona Franca | ⚠️ Parcial |
| **09** | Reembolso | ⚠️ Parcial |

## 🔍 Análisis del Código Actual

### Implementación en AlanubeService.php
```php
// Tipos actualmente definidos
const INTERNAL_OPERATION = '01';      // ✅ Completo
const IMPORT = '02';                  // ⚠️ Definido pero validación limitada
const EXPORT = '03';                  // ⚠️ Definido pero validación limitada
const CREDIT_NOTE = '04';             // ⚠️ Definido pero falta lógica de referencia
const FREE_ZONE = '08';               // ⚠️ Definido pero validación limitada
const REIMBURSEMENT = '09';           // ⚠️ Definido pero validación limitada
const FOREIGN_OPERATION = '10';       // ⚠️ Extensión no oficial
```

### Faltantes Críticos
1. **Nota de Débito FE (05)**: No existe constante ni lógica
2. **Nota de Débito Genérica (07)**: No existe constante ni lógica

## 🎯 Plan de Implementación

### Fase 1: Completar Constantes Faltantes

#### 1.1 Actualizar AlanubeService.php
```php
// Agregar constantes faltantes
const CREDIT_NOTE_REFERENCE = '04';   // Nota de Crédito referente a FE
const DEBIT_NOTE_REFERENCE = '05';    // Nota de Débito referente a FE  
const CREDIT_NOTE_GENERIC = '06';     // Nota de Crédito genérica
const DEBIT_NOTE_GENERIC = '07';      // Nota de Débito genérica
```

#### 1.2 Actualizar AlanubeFormatterHelper.php
Complementar el array `DOCUMENT_TYPES_PANAMA` con los tipos faltantes.

### Fase 2: Validaciones Específicas por Tipo

#### 2.1 Notas de Débito/Crédito con Referencia (04, 05)
- **Validación**: Debe incluir referencia a factura electrónica original
- **Campos requeridos**: 
  - Número de documento referenciado
  - Fecha de documento referenciado
  - Motivo de la nota

#### 2.2 Notas Genéricas (06, 07)
- **Validación**: NO requiere referencia a documento específico
- **Campos requeridos**:
  - Motivo general de la nota
  - Descripción detallada

#### 2.3 Facturas Especializadas

**Factura de Importación (02)**:
- Validar datos de importación
- Documentos aduaneros
- Agentes aduaneros

**Factura de Exportación (03)**:
- Validar país de destino
- Documentos de exportación
- Incoterms

**Factura de Zona Franca (08)**:
- Validar empresa de zona franca
- Régimen especial
- Documentación específica

**Reembolso (09)**:
- Validar gastos reembolsables
- Documentación soporte
- Límites legales

### Fase 3: Actualización de Livewire

#### 3.1 Componente Create.php
- Ampliar validaciones condicionales por tipo
- Agregar campos específicos según tipo de documento
- Implementar lógica de referencia para notas 04/05

#### 3.2 Vista create.blade.php
- Mostrar/ocultar campos según tipo seleccionado
- Validaciones dinámicas en frontend
- UX mejorada para cada tipo

#### 3.3 DataProvider.php
Actualizar método `tipeDocuments()` para filtrar tipos según:
- País de la organización
- Permisos del usuario
- Configuración PAC

### Fase 4: Base de Datos

#### 4.1 Seeder para Tipos de Documento
```php
// TypedocumentSeeder.php
$types = [
    ['name' => 'Factura de Operación Interna', 'code' => '01'],
    ['name' => 'Factura de Importación', 'code' => '02'],
    ['name' => 'Factura de Exportación', 'code' => '03'],
    ['name' => 'Nota de Crédito Referente a FE', 'code' => '04'],
    ['name' => 'Nota de Débito Referente a FE', 'code' => '05'],
    ['name' => 'Nota de Crédito Genérica', 'code' => '06'],
    ['name' => 'Nota de Débito Genérica', 'code' => '07'],
    ['name' => 'Factura de Zona Franca', 'code' => '08'],
    ['name' => 'Reembolso', 'code' => '09'],
];
```

#### 4.2 Migración para Campos Adicionales
Considerar agregar campos específicos a la tabla según tipo:
- `requires_reference` (bool)
- `document_category` (enum: invoice, credit_note, debit_note)
- `validation_rules` (json)

## 🚀 Prioridades de Implementación

### Alta Prioridad (Inmediata)
1. **Nota de Débito Referente a FE (05)** - Demanda legal
2. **Nota de Débito Genérica (07)** - Complemento de 06
3. **Validaciones de referencia para 04/05**

### Media Prioridad
1. **Mejorar validaciones de Importación (02)**
2. **Mejorar validaciones de Exportación (03)**
3. **Zona Franca (08) - validaciones específicas**

### Baja Prioridad
1. **Reembolso (09) - casos especiales**
2. **Optimizaciones de UX**
3. **Reportes por tipo de documento**

## 🔧 Consideraciones Técnicas

### Compatibilidad PAC
- **TheFactoryHKA**: Verificar soporte para todos los tipos
- **Alanube**: Confirmar endpoints específicos por tipo

### Multi-tenant
- Cada organización puede tener tipos habilitados diferentes
- Configuración por organización en `organizations` table

### Validaciones
- Implementar `TypedocumentValidator` class
- Reglas específicas por tipo de documento
- Validaciones cruzadas con datos del receptor

## 📊 Impacto Esperado

### Cumplimiento Legal
- ✅ 100% de tipos oficiales soportados
- ✅ Validaciones según normativas DGI Panamá
- ✅ Trazabilidad completa de documentos

### Experiencia Usuario
- 🎯 Interface más intuitiva por tipo
- 🎯 Validaciones en tiempo real
- 🎯 Menos errores de emisión

### Integraciones
- 🔄 QuickBooks: Mapeo automático de tipos
- 🔄 Shopify: Detección inteligente
- 🔄 APIs: Soporte completo de tipos

## 📝 Próximos Pasos

1. **Crear issue en GitHub** con este análisis
2. **Implementar constantes faltantes** (05, 07)
3. **Actualizar seeder de base de datos**
4. **Crear validadores específicos por tipo**
5. **Testing exhaustivo** con cada tipo
6. **Documentación de usuario** por tipo

---

**Fecha de análisis**: 2025-09-23  
**Estado**: Propuesta para implementación  
**Responsable**: Equipo DocuCenter
