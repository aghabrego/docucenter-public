# Guía de Testing Manual en Navegador - Campos Condicionales DGI

## 🌐 Testing Manual del Sistema de Campos Condicionales

**Fecha**: 23 de Septiembre, 2025  
**Versión**: 98% DGI Compliance  
**Estado**: Ready for Manual Testing

---

## 📋 Pre-requisitos

### Antes de Comenzar
1. ✅ **Sistema funcionando**: Servidor Laravel activo
2. ✅ **Base de datos**: Conexión establecida
3. ✅ **Organización**: PAC configurado (Alanube Panamá preferido)
4. ✅ **Usuario**: Sesión activa con permisos de facturación

### Acceso al Sistema
```
URL: http://localhost/admin/einvoice/create
Ruta: Admin → Facturación Electrónica → Crear Factura
```

---

## 🎯 Plan de Testing por Tipo de Documento

### **Test 1: Tipo 01 - Factura de Operación Interna**

#### Pasos a Seguir:
1. **Paso 1**: Seleccionar "Factura de operación interna" (01)
2. **Paso 2**: Completar campos básicos (sucursal, fecha, etc.)
3. **Paso 3**: Verificar campos condicionales

#### ✅ Resultados Esperados:
- **Card visible**: "Información Adicional Factura Interna"
- **Campos mostrados**:
  - Número de Orden de Compra (opcional)
  - Condiciones de Pago Especiales (textarea)
- **Color del card**: Gris (bg-secondary)
- **Icono**: fa-file-invoice
- **Alert**: Información sobre campos opcionales

#### 📝 Verificaciones:
- [ ] Card se muestra al seleccionar tipo 01
- [ ] Campos son opcionales (sin asterisco rojo)
- [ ] Placeholder correcto en campos
- [ ] Textarea funciona correctamente
- [ ] Card se oculta al cambiar a otro tipo

---

### **Test 2: Tipos 02,03,08 - Exportación/Importación/Zona Franca**

#### Pasos a Seguir:
1. **Paso 1**: Seleccionar "Factura de importación" (02), "Factura de exportación" (03), o "Factura de Zona Franca" (08)
2. **Paso 2**: Completar campos básicos
3. **Paso 3**: Verificar campos condicionales

#### ✅ Resultados Esperados:
- **Card visible**: "Campos de Exportación e Importación"
- **Campos mostrados**:
  - Condiciones de Entrega (INCOTERMS) - **OBLIGATORIO**
  - Moneda de Exportación
  - Descripción Moneda Personalizada (condicional)
  - Tipo de Cambio (condicional)
  - Monto en Moneda Extranjera (condicional)
  - Puerto de Embarque
  - País Origen Mercancía (B507) - **OBLIGATORIO**
  - País Destino Mercancía (B508) - **OBLIGATORIO**
  - Terminal Embarque (B509)
  - Número Contenedor (B510)
  - Peso Total Mercancía (B511)
- **Color del card**: Verde (bg-success)
- **Icono**: fa-ship

#### 📝 Verificaciones:
- [ ] Card se muestra para tipos 02, 03, 08
- [ ] INCOTERMS dropdown funciona
- [ ] Campos condicionales aparecen según moneda seleccionada
- [ ] País destino excluye "PA" (Panamá)
- [ ] Campos numéricos aceptan decimales
- [ ] Validaciones de campos obligatorios funcionan

---

### **Test 3: Tipos 04,05 - Notas de Crédito/Débito**

#### Pasos a Seguir:
1. **Paso 1**: Seleccionar "Nota de Crédito referente a una o varias FE" (04) o "Nota de Débito referente a una o varias FE" (05)
2. **Paso 2**: Completar campos básicos
3. **Paso 3**: Verificar campos condicionales

#### ✅ Resultados Esperados:
- **Card visible**: "Campos de Referencia (Notas de Crédito/Débito)"
- **Campos mostrados**:
  - CUFE del Documento Referenciado - **OBLIGATORIO**
  - Fecha del Documento Referenciado - **OBLIGATORIO**
  - Número del Documento Referenciado - **OBLIGATORIO**
  - RUC del Emisor Referenciado - **OBLIGATORIO**
  - Nombre del Emisor Referenciado (B602) - opcional
- **Color del card**: Amarillo (bg-warning)
- **Icono**: fa-link

#### 📝 Verificaciones:
- [ ] Card se muestra para tipos 04, 05
- [ ] Campo CUFE acepta exactamente 96 caracteres
- [ ] Fecha no permite fechas futuras
- [ ] Fecha no permite fechas > 180 días atrás
- [ ] Todos los campos obligatorios tienen asterisco rojo
- [ ] Campo B602 es opcional

---

### **Test 4: Tipos 06,07 - Notas Genéricas**

#### Pasos a Seguir:
1. **Paso 1**: Seleccionar "Nota de Crédito genérica" (06) o "Nota de Débito genérica" (07)
2. **Paso 2**: Completar campos básicos
3. **Paso 3**: Verificar campos condicionales

#### ✅ Resultados Esperados:
- **Card visible**: "Campos para Notas Genéricas"
- **Campos mostrados**:
  - Concepto de la Nota (textarea)
  - Período que Aplica (text)
- **Color del card**: Azul (bg-info)
- **Icono**: fa-file-alt
- **Alert**: Información sobre notas genéricas

#### 📝 Verificaciones:
- [ ] Card se muestra para tipos 06, 07
- [ ] Textarea de concepto funciona correctamente
- [ ] Campo período tiene placeholder adecuado
- [ ] Alert informativo visible
- [ ] Ambos campos son opcionales

---

### **Test 5: Tipo 09 - Reembolso**

#### Pasos a Seguir:
1. **Paso 1**: Seleccionar "Reembolso" (09)
2. **Paso 2**: Completar campos básicos
3. **Paso 3**: Verificar campos condicionales

#### ✅ Resultados Esperados:
- **Card visible**: "Campos de Reembolso"
- **Campos mostrados**:
  - Número Comprobante Original - **OBLIGATORIO**
  - Fecha Comprobante Original - **OBLIGATORIO**
  - Razón del Reembolso - **OBLIGATORIO**
- **Color del card**: Azul primario (bg-primary)
- **Icono**: fa-undo
- **Alert**: Advertencia sobre información específica

#### 📝 Verificaciones:
- [ ] Card se muestra para tipo 09
- [ ] Todos los campos son obligatorios
- [ ] Date picker funciona correctamente
- [ ] Textarea de razón funciona
- [ ] Alert de advertencia visible

---

### **Test 6: Otros Tipos - Sin Campos Adicionales**

#### Pasos a Seguir:
1. **Paso 1**: Seleccionar cualquier tipo no implementado específicamente
2. **Paso 2**: Completar campos básicos
3. **Paso 3**: Verificar mensaje por defecto

#### ✅ Resultados Esperados:
- **Card visible**: "Sin Campos Adicionales"
- **Mensaje**: "El tipo de documento seleccionado no requiere campos condicionales adicionales"
- **Color del card**: Gris claro (bg-light)
- **Icono**: fa-check-circle text-success

#### 📝 Verificaciones:
- [ ] Card por defecto se muestra
- [ ] Mensaje es claro y profesional
- [ ] Icono de check verde visible

---

## 🔄 Testing de Reactividad Alpine.js

### **Test de Cambio Dinámico**

#### Procedimiento:
1. Iniciar con tipo 01
2. Cambiar secuencialmente a cada tipo (02, 03, 04, 05, 06, 07, 08, 09)
3. Observar transiciones

#### ✅ Verificaciones:
- [ ] Cards aparecen/desaparecen instantáneamente
- [ ] No hay flickers o parpadeos
- [ ] Solo un card visible a la vez
- [ ] Transiciones suaves
- [ ] No errores de JavaScript en consola

---

## 🧪 Testing de Validaciones

### **Test de Campos Obligatorios**

#### Para Tipos con Campos Obligatorios (03, 04, 05, 09):
1. Completar todos los pasos hasta el final
2. Intentar enviar sin completar campos obligatorios
3. Verificar mensajes de error

#### ✅ Verificaciones:
- [ ] Campos obligatorios marcados con asterisco rojo
- [ ] Validación client-side funciona
- [ ] Mensajes de error claros
- [ ] Focus automático en campos con error

---

## 📱 Testing de Responsividad

### **Test Multi-dispositivo**

#### Procedimientos:
1. **Desktop**: Resolución 1920x1080
2. **Tablet**: Resolución 768x1024
3. **Mobile**: Resolución 375x667

#### ✅ Verificaciones:
- [ ] Cards se adaptan correctamente
- [ ] Campos mantienen usabilidad
- [ ] Grid responsive funciona (col-md-*)
- [ ] Texto legible en todos los tamaños
- [ ] Botones accesibles

---

## 🚨 Testing de Casos Extremos

### **Test de Datos Límite**

#### Casos a Probar:
1. **CUFE**: Exactamente 96 caracteres
2. **Fechas**: Límite de 180 días
3. **Campos numéricos**: Decimales y números grandes
4. **Textareas**: Texto muy largo

#### ✅ Verificaciones:
- [ ] Validaciones de longitud funcionan
- [ ] Campos numéricos aceptan formato correcto
- [ ] Fechas respetan límites
- [ ] No hay desbordamiento visual

---

## 📊 Checklist Final

### **Funcionalidad General**
- [ ] Paso 3 "Conditional Fields" ya no aparece vacío
- [ ] Todos los 9 tipos JSch09 tienen campos específicos
- [ ] Lógica condicional Alpine.js funciona
- [ ] Validaciones Livewire operativas
- [ ] UI profesional y consistente

### **Experiencia de Usuario**
- [ ] Navegación intuitiva entre pasos
- [ ] Campos claramente etiquetados
- [ ] Ayuda contextual disponible
- [ ] Colores distintivos por tipo
- [ ] Iconos apropiados

### **Compliance DGI**
- [ ] Campos B507-B511 para exportación
- [ ] Campos B602/B503 para referencias
- [ ] Validaciones críticas activas
- [ ] Formato de datos correcto

---

## 🎯 Resultado Esperado Final

Al completar este testing manual, deberías confirmar:

✅ **Sistema 100% Funcional**  
✅ **98% DGI Compliance Verificado**  
✅ **Ready for PAC Certification**  
✅ **Production Ready**

---

## 📞 Soporte

Si encuentras algún problema durante el testing:

1. **Verificar consola del navegador** para errores JavaScript
2. **Revisar logs de Laravel** para errores del servidor
3. **Documentar el problema** con pasos para reproducir
4. **Verificar configuración PAC** si hay errores específicos

---

**Fecha de Testing**: ___________  
**Testeado por**: ___________  
**Navegador**: ___________  
**Resultado**: ✅ APROBADO / ❌ REQUIERE CORRECCIONES
