# Validaciones - DocuCenter

Este directorio contiene documentación sobre validaciones específicas implementadas en el sistema DocuCenter, incluyendo validaciones de datos, reglas de negocio y controles de integridad.

## Documentos Disponibles

### 🔐 Validaciones PAC (Proveedores de Autorización Calificados)

- **[SOLUCION_PAC_RECEIVER_TYPE_VALIDATION.md](./SOLUCION_PAC_RECEIVER_TYPE_VALIDATION.md)** - Solución completa para error PAC "receiver.ruc.ruc is not valid dni" con auto-detección de tipos de receptor según documentación oficial de Panamá
- **[SOLUCION_PAC_ITBMS_RATE.md](./SOLUCION_PAC_ITBMS_RATE.md)** - Documentación sobre validaciones de tasas ITBMS en facturas PAC

### 🆔 Validaciones de Identificación

- **[createSale-ruc-validation.md](./createSale-ruc-validation.md)** - Validación de RUC en el proceso de creación de ventas
- **[maxgym-ruc-validation.md](./maxgym-ruc-validation.md)** - Validación específica de RUC para integración con MaxGym

### 🧮 Validaciones de Integración y Cálculos

- **[VALIDATION_REPORT_MAXGYM_TRAIT.md](./VALIDATION_REPORT_MAXGYM_TRAIT.md)** - Validación completa de compatibilidad entre MaxGym y CreateFastJobCalculation, incluyendo precisión fiscal y cálculos tributarios
- **[meypar-validations.md](./meypar-validations.md)** - Validaciones específicas para integración Meypar
- **[validation_comparison.md](./validation_comparison.md)** - Comparación de validaciones entre diferentes sistemas

## Tipos de Validaciones

### 📋 Validaciones de Negocio
Reglas específicas del dominio empresarial que deben cumplirse para mantener la integridad de los datos.

### 🔍 Validaciones de Formato
Verificaciones de formato de datos como RUC, cédulas, correos electrónicos, etc.

### ⚖️ Validaciones Fiscales
Validaciones específicas requeridas por regulaciones fiscales y tributarias.

### 🔗 Validaciones de Integración
Validaciones específicas para datos provenientes de sistemas externos o integraciones.

## Estructura de Documentos de Validación

Cada documento de validación incluye:

1. **Contexto** - Por qué es necesaria la validación
2. **Reglas de Validación** - Criterios específicos a validar
3. **Implementación** - Código y lógica implementada
4. **Casos de Prueba** - Escenarios válidos e inválidos
5. **Manejo de Errores** - Cómo se gestionan las validaciones fallidas

## Principios de Validación

- **Fail Fast**: Validar lo antes posible en el flujo
- **Mensajes Claros**: Errores comprensibles para el usuario
- **Consistencia**: Mismas reglas en toda la aplicación
- **Performance**: Validaciones eficientes que no impacten rendimiento

## Casos de Uso Comunes

- Validación de documentos fiscales (RUC, DV)
- Verificación de formato de campos obligatorios
- Validaciones de rangos y límites
- Verificación de integridad referencial
- Validaciones de reglas de negocio específicas

---

*Última actualización: Agosto 2025*
