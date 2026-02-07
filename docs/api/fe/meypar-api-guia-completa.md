# API MEYPAR OFICIAL - Guía Completa de Uso

## Estado Actual: **COMPLETAMENTE IMPLEMENTADA Y VALIDADA**

La API MEYPAR está **100% funcional** y coincide exactamente con la documentación oficial de MEYPAR encontrada en el análisis del PDF.

---

##  **Endpoints Disponibles**

### 1. **Crear Venta MEYPAR (Solo Almacenar)**
```http
POST /api/v1/fe/create_sale_meypar
```
- **Función**: Crea la venta en la base de datos **sin emitir** factura electrónica
- **Uso**: Para almacenar datos y emitir después manualmente

### 2. **Crear Venta MEYPAR con Emisión Automática** 
```http
POST /api/v1/fe/create_sale_meypar_with_emission
```
- **Función**: Crea la venta **Y emite automáticamente** la factura electrónica
- **Uso**: Proceso completo automatizado con reintentos inteligentes
- **Recomendado**: Para integración de producción

**Headers Requeridos (Ambos Endpoints):**
```http
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

---

## **Estructura Oficial de la Request**

### Ejemplo Completo (Estructura Oficial MEYPAR)

```json
{
  "idFacturador": "12345678",
  "ambiente": 1,
  "documento": "32323",
  "codigo": 2,
  "tipoDocumento": 1,
  "prefijo": "A132",
  "numero": 2,
  "medioPago": 2,
  "referenciaPago": "123456789",
  "fechaFactura": "2024-02-06",
  "mensaje": "Factura de estacionamiento",
  "observacion": "Cliente frecuente",
  
  "terminalPagoID": {
    "CodigoTerminal": 4,
    "CodigoExterno": "TERM-001",
    "NombreTerminal": "Terminal Principal Parking"
  },
  
  "autorizacionPrefijo": {
    "PrefijoId": "C1PQ",
    "ResoluciónNumero": "132343423423432",
    "VigenciaFecha": "2025-01-15",
    "DuracionFecha": 24,
    "CorrelativoInicialNum": 1,
    "CorrelativoFinalNum": 30000
  },
  
  "detalleMedioPagoList": [
    {
      "codigoMedioPago": 2,
      "importeMedioPago": 15.75
    }
  ],
  
  "detalleFacturaList": [
    {
      "objectName": "WADetalleFactura",
      "cantidad": 1,
      "descripcion": "Estacionamiento 3 horas",
      "precioUnitario": 15.75,
      "codigoProducto": 1,
      "codigoVehiculo": 101,
      "unidadMedida": "HRA",
      "precioTotalSinDescuento": 15.75,
      "precioTotalFinalDetalle": 15.75
    }
  ]
}
```

---

## **Campos Requeridos y Opcionales**

### **Campos Principales (Obligatorios)**

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `idFacturador` | string | ID único del facturador | `"12345678"` |
| `ambiente` | integer | Ambiente de ejecución: 1=Producción, 2=Pruebas | `1` |
| `documento` | string | Documento de identificación del adquiriente (NIT del adquirente/usuario del parking) | `"32323"` |
| `codigo` | integer | Código de operación | `2` |
| `tipoDocumento` | integer | Tipo de documento fiscal | `1` |
| `prefijo` | string | Prefijo del documento | `"A132"` |
| `numero` | integer | Número consecutivo de la factura | `123` |
| `medioPago` | integer | 1=Efectivo, 2=Tarjeta, 3=Cheque, 4=Transferencia | `2` |
| `fechaFactura` | string | Fecha formato YYYY-MM-DD | `"2024-02-06"` |

### **Campos Opcionales**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `referenciaPago` | string | Referencia del pago |
| `mensaje` | string | Mensaje adicional |
| `observacion` | string | Observaciones |

---

## **Objetos Anidados**

### **Terminal de Pago (terminalPagoID)**

```json
{
  "terminalPagoID": {
    "CodigoTerminal": 4,        // Código numérico del terminal
    "CodigoExterno": "TERM-001", // Código alfanumérico personalizado
    "NombreTerminal": "Terminal Principal"
  }
}
```

###  **Autorización de Prefijo (autorizacionPrefijo)**

```json
{
  "autorizacionPrefijo": {
    "PrefijoId": "C1PQ",
    "ResoluciónNumero": "132343423423432",
    "VigenciaFecha": "2025-01-15",
    "DuracionFecha": 24,
    "CorrelativoInicialNum": 1,
    "CorrelativoFinalNum": 30000
  }
}
```

---

##  **Array: Medios de Pago (detalleMedioPagoList)**

**Obligatorio:** Mínimo 1 elemento

```json
{
  "detalleMedioPagoList": [
    {
      "codigoMedioPago": 1,        // 1=Efectivo, 2=Tarjeta, 3=Cheque, 4=Transferencia
      "importeMedioPago": 10.50    // Decimal con máximo 4 decimales
    },
    {
      "codigoMedioPago": 2,
      "importeMedioPago": 5.25
    }
  ]
}
```

### Códigos de Medio de Pago
- **1** = Efectivo
- **2** = Tarjeta de crédito/débito  
- **3** = Cheque
- **4** = Transferencia bancaria

---

##  **Array: Detalles de Factura (detalleFacturaList)**

**Obligatorio:** Mínimo 1 elemento

```json
{
  "detalleFacturaList": [
    {
      "objectName": "WADetalleFactura",           // Siempre este valor
      "cantidad": 2,                              // Cantidad de unidades
      "descripcion": "Estacionamiento por horas", // Descripción del servicio
      "precioUnitario": 5.00,                     // Precio por unidad
      "codigoProducto": 1,                        // Código del producto/servicio
      "codigoVehiculo": 101,                      // Código tipo vehículo (opcional)
      "unidadMedida": "HRA",                      // UNI, HRA, MIN, etc.
      "precioTotalSinDescuento": 10.00,           // Total antes descuentos
      "precioTotalFinalDetalle": 9.50,            // Total final con descuentos
      "observacion": "Observación del item"       // Observación opcional del detalle
    }
  ]
}
```

### Campos Obligatorios en Detalle
- `objectName`: Siempre `"WADetalleFactura"`
- `cantidad`: Cantidad numérica
- `descripcion`: Texto descriptivo
- `precioUnitario`: Precio por unidad
- `codigoProducto`: Código del producto
- `precioTotalSinDescuento`: Total sin descuento
- `precioTotalFinalDetalle`: Total final

### Campos Opcionales en Detalle
-  `codigoVehiculo`: Tipo de vehículo
-  `unidadMedida`: Por defecto "UNI"
-  `observacion`: Observación específica del item

---

## **Ejemplo de Request Mínimo**

```json
{
  "idFacturador": "12345678",
  "ambiente": 1,
  "documento": "32323",
  "codigo": 1,
  "tipoDocumento": 1,
  "prefijo": "A001",
  "numero": 1,
  "medioPago": 1,
  "fechaFactura": "2024-02-06",
  
  "detalleMedioPagoList": [
    {
      "codigoMedioPago": 1,
      "importeMedioPago": 5.00
    }
  ],
  
  "detalleFacturaList": [
    {
      "objectName": "WADetalleFactura",
      "cantidad": 1,
      "descripcion": "Ticket de estacionamiento",
      "precioUnitario": 5.00,
      "codigoProducto": 1,
      "precioTotalSinDescuento": 5.00,
      "precioTotalFinalDetalle": 5.00
    }
  ]
}
```

---

## **Identificación de Fuente (Origin)**

Todas las ventas creadas desde la API MEYPAR se marcan automáticamente con el campo `origin` en la base de datos para identificar su procedencia.

### **Campo Origin en SalesHeaderImp**

```php
'origin' => 'meypar'
```

### **Valores de Origin por Sistema:**
- `'meypar'` - Ventas provenientes de MEYPAR
- `'quickbooks'` - Ventas provenientes de QuickBooks Online
- `'shopify'` - Ventas provenientes de Shopify
- `'lightspeed'` - Ventas provenientes de Lightspeed
- `'acicloud'` - Ventas provenientes de ACI Cloud ERP
- `'maxgym'` - Ventas provenientes de Maxgym
- `'kart21'` - Ventas provenientes de Kart21
- `'docucenter'` - Ventas creadas nativamente (default)

### **Propósito del Campo Origin**

1. **Tracking de origen**: Identificar de qué sistema proviene cada venta
2. **Prevención de loops**: Evitar re-procesamiento de ventas
3. **Auditoría**: Facilitar rastreo y debugging
4. **Filtrado**: Permitir consultas específicas por origen

**Nota:** Este campo se asigna automáticamente por el sistema y no requiere ser especificado en el request.

---

## **Respuestas de la API**

### **Respuesta Exitosa - create_sale_meypar (200)**

```json
{
  "success": true,
  "message": "Venta MEYPAR creada exitosamente",
  "data": {
    "sale": {
      "id": 12345,
      "invoice_number": "A001-123",
      "customer_name": "Cliente Test",
      "subtotal": 15.75,
      "net_due": 15.75,
      "date": "2024-02-06",
      "issued": false,
      "origin": "meypar"
    }
  }
}
```

### **Respuesta Exitosa - create_sale_meypar_with_emission (200)**

```json
{
  "success": true,
  "message": "Venta MEYPAR creada y emitida exitosamente",
  "attempt": 1,
  "data": {
    "sale": {
      "id": 12345,
      "invoice_number": "A001-123", 
      "customer_name": "Cliente Test",
      "subtotal": 15.75,
      "net_due": 15.75,
      "date": "2024-02-06",
      "issued": true,
      "origin": "meypar"
    },
    "emission": {
      "document_id": 67890,
      "cufe": "abc123def456...",
      "qr_code": "data:image/png;base64,...",
      "pdf_url": "https://example.com/invoice.pdf",
      "xml_url": "https://example.com/invoice.xml",
      "status": "emitted"
    }
  }
}
```

### **Respuesta de Error (422)**

```json
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "idFacturador": [
      "El ID del facturador es requerido"
    ],
    "detalleFacturaList": [
      "Los detalles de la factura son requeridos"
    ],
    "detalleFacturaList.0.cantidad": [
      "La cantidad del detalle es requerida"
    ]
  }
}
```

---

##  **Ejemplos de Uso con cURL**

### **Solo Almacenar (create_sale_meypar)**

```bash
curl -X POST \
  https://tu-dominio.com/api/v1/fe/create_sale_meypar \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "idFacturador": "12345678",
    "codigo": 1,
    "tipoDocumento": 1,
    "prefijo": "A001",
    "numero": 1,
    "medioPago": 1,
    "fechaFactura": "2024-02-06",
    "detalleMedioPagoList": [
      {
        "codigoMedioPago": 1,
        "importeMedioPago": 5.00
      }
    ],
    "detalleFacturaList": [
      {
        "objectName": "WADetalleFactura",
        "cantidad": 1,
        "descripcion": "Parking ticket",
        "precioUnitario": 5.00,
        "codigoProducto": 1,
        "precioTotalSinDescuento": 5.00,
        "precioTotalFinalDetalle": 5.00
      }
    ]
  }'
```

### **Crear y Emitir Automáticamente (create_sale_meypar_with_emission)**

```bash
curl -X POST \
  https://tu-dominio.com/api/v1/fe/create_sale_meypar_with_emission \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "idFacturador": "12345678",
    "ambiente": 1,
    "documento": "32323",
    "codigo": 1,
    "tipoDocumento": 1,
    "prefijo": "A001",
    "numero": 1,
    "medioPago": 1,
    "fechaFactura": "2024-02-06",
    "detalleMedioPagoList": [
      {
        "codigoMedioPago": 1,
        "importeMedioPago": 5.00
      }
    ],
    "detalleFacturaList": [
      {
        "objectName": "WADetalleFactura",
        "cantidad": 1,
        "descripcion": "Parking ticket",
        "precioUnitario": 5.00,
        "codigoProducto": 1,
        "precioTotalSinDescuento": 5.00,
        "precioTotalFinalDetalle": 5.00
      }
    ]
  }'
```

### Ejemplo con Múltiples Items

```bash
curl -X POST \
  https://tu-dominio.com/api/v1/fe/create_sale_meypar \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "idFacturador": "87654321",
    "ambiente": 1,
    "documento": "45678",
    "codigo": 2,
    "tipoDocumento": 1,
    "prefijo": "B001",
    "numero": 456,
    "medioPago": 2,
    "fechaFactura": "2024-02-07",
    "detalleMedioPagoList": [
      {
        "codigoMedioPago": 1,
        "importeMedioPago": 15.00
      },
      {
        "codigoMedioPago": 2,
        "importeMedioPago": 10.00
      }
    ],
    "detalleFacturaList": [
      {
        "objectName": "WADetalleFactura",
        "cantidad": 3,
        "descripcion": "Estacionamiento por horas",
        "precioUnitario": 5.00,
        "codigoProducto": 1,
        "unidadMedida": "HRA",
        "precioTotalSinDescuento": 15.00,
        "precioTotalFinalDetalle": 15.00
      },
      {
        "objectName": "WADetalleFactura",
        "cantidad": 1,
        "descripcion": "Servicio de lavado",
        "precioUnitario": 10.00,
        "codigoProducto": 2,
        "unidadMedida": "SER",
        "precioTotalSinDescuento": 10.00,
        "precioTotalFinalDetalle": 10.00
      }
    ]
  }'
```

---

##  **¿Cuál Endpoint Usar?**

### **create_sale_meypar** - Solo Almacenar
**Usar cuando:**
- Necesitas guardar la venta para emitir después
- Quieres controlar manualmente el momento de emisión
- Necesitas validar datos antes de emitir
- Proceso de aprobación manual requerido

**Resultado:**
- Venta guardada en base de datos
- `"issued": false` en la respuesta
- Puedes emitir después desde la interfaz admin

### **create_sale_meypar_with_emission** - Proceso Completo
**Usar cuando:**
- Quieres proceso automatizado completo
- Producción con alta disponibilidad
- Necesitas emisión inmediata
- Integración con sistemas externos

**Características Avanzadas:**
- **Reintentos inteligentes**: Hasta 3 intentos automáticos
- **Auditoría completa**: Tracking de transacciones
- **Recuperación de errores**: Manejo de fallos PAC
- **Proceso robusto**: Ideal para producción

---

##  **Validaciones Implementadas**

### **Validaciones Principales**

1. **Campos obligatorios**: idFacturador, ambiente, documento, codigo, tipoDocumento, prefijo, numero, medioPago, fechaFactura
2. **Arrays obligatorios**: detalleMedioPagoList y detalleFacturaList (mínimo 1 elemento cada uno)
3. **Formato de fecha**: YYYY-MM-DD
4. **Códigos de medio de pago**: Solo 1, 2, 3, 4
5. **Ambiente**: 1 (Producción) o 2 (Pruebas)
5. **Decimales**: Formato correcto con máximo 4 decimales
6. **ObjectName**: Automáticamente se asigna "WADetalleFactura" si no se envía

### **Funciones Automáticas**

1. **Normalización de decimales**: 1.00 → 1, 1.50 → 1.5
2. **ObjectName automático**: Se agrega si no existe
3. **Validación de estructura**: Coincide 100% con documentación oficial MEYPAR

---

## **Estado de Implementación**

| Componente | Estado | Validado |
|------------|---------|----------|
| **create_sale_meypar** | Completo | PDF Oficial |
| **create_sale_meypar_with_emission** | Completo | PDF Oficial |
| Request Validation | Completo | PDF Oficial |
| Service Processing | Completo | PDF Oficial |  
| Reintentos Inteligentes | Completo | Producción |
| **Campo Origin (Fuente)** | Completo | Tracking |
| Auditoría de Transacciones | Completo | Tracking |
| Unit Tests | Completo | Estructura Oficial |
| Error Messages | Completo | Español |

---

## **Integración con Otros Sistemas**

### **Documentación Complementaria**

Para entender en detalle cómo funciona el sistema de Tax Code en QuickBooks (que es más complejo que el sistema directo de MEYPAR), consulta:

- **[Sistema Tax Code QuickBooks](./quickbooks-tax-code-system.md)** - Documentación técnica del sistema híbrido de QuickBooks:
  - ✅ 4 niveles de prioridad para extracción de impuestos
  - ✅ Dos formatos diferentes que maneja QB
  - ✅ Logging detallado con método usado
  - ✅ Sistema de prevención de loops con campo `origin`
  - ✅ Ejemplos prácticos y comandos de testing
  - ✅ Comparación: MEYPAR usa sistema directo simple, QB usa sistema híbrido complejo

### **Compatibilidad con QuickBooks**

Las ventas creadas desde MEYPAR (`origin='meypar'`) son **compatibles** con el sistema de sincronización de QuickBooks. Sin embargo:

- Ventas con `origin='meypar'` **NO se sincronizan** automáticamente a QuickBooks
- Ventas con `origin='quickbooks'` **NO se re-envían** a QuickBooks (prevención de loops)
- Ventas con `origin='docucenter'` **SÍ se sincronizan** a QuickBooks si está configurado

### **Tracking Multi-Sistema**

El campo `origin` permite:
- Identificar la fuente exacta de cada venta
- Prevenir duplicaciones entre sistemas
- Facilitar debugging y auditoría
- Aplicar lógica de negocio específica por origen

---

## **Conclusión**

La API MEYPAR está **completamente implementada y funcional** con **DOS ENDPOINTS**:

### **Básico**: `/api/v1/fe/create_sale_meypar`
- Solo almacena la venta
- Para control manual de emisión
- Marca automáticamente: `origin='meypar'`

### **Avanzado**: `/api/v1/fe/create_sale_meypar_with_emission` 
- Proceso completo automatizado
- Reintentos inteligentes
- **Recomendado para producción**
- Marca automáticamente: `origin='meypar'`

**Lista para producción**  
**Validada con documentación oficial**  
**Tests implementados**  
**Documentación completa**  
**Dos opciones de integración**  
**Tracking de origen implementado**

**Commits relacionados:**
- `863860e` - Optimizaciones de performance
- `86ed78f` - Implementación estructura oficial MEYPAR

¡Todo está listo para usar! 
