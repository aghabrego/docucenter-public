# API Facturación Electrónica - Integración Meypar

## Crear Venta Meypar

**Endpoint:** `POST /api/v1/fe/create_sale_meypar`  
**Controller:** `FeController::createSaleMeypar`  
**Request Class:** `CreateSaleMeyparRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y emite una factura electrónica basada en datos provenientes del sistema Meypar (estacionamientos y servicios de parqueo).

### Estructura del Request

#### Campos Principales (Estructura Oficial MEYPAR)

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `idFacturador` | integer | ✅ | ID del obligado a facturar | - |
| `ambiente` | integer | ✅ | Ambiente (1=Producción, 2=Pruebas) | - |
| `documento` | string | ✅ | **NIT del adquiriente** (usuario del parking) | **50 caracteres** |
| `tipoDocumento` | integer | ✅ | Tipo de documento (1=FE, 2=DEE) | - |
| `prefijo` | string | ✅ | Prefijo de numeración | **10 caracteres** |
| `numero` | integer | ✅ | **Número de factura** | - |
| `medioPago` | integer | ✅ | Medio de pago (0-4) | - |
| `fechaFactura` | string | ✅ | Fecha de factura (YYYY-MM-DD) | - |

**Notas Importantes:**
- **`documento`**: Es el NIT/documento del **cliente** (adquiriente), NO el número de factura
- **`numero`**: Es el **número de la factura**, se convierte en `InvoiceNumber`
- **Consumidor Final**: Usar `documento = "222222222222"` para consumidor final en Panamá

#### Emisor

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.emisor.nombre` | string | ✅ | Nombre del emisor | **100 caracteres** |
| `documento.emisor.nit` | string | ✅ | NIT del emisor | **50 caracteres** |
| `documento.emisor.direccion` | string | ❌ | Dirección del emisor | - |
| `documento.emisor.telefono` | string | ❌ | Teléfono del emisor | - |
| `documento.emisor.email` | string | ❌ | Email del emisor | - |

#### Adquiriente (Estructura Simplificada - Opcional)

**⚠️ Nota**: Esta es una estructura alternativa simplificada de DocuCenter. La estructura oficial MEYPAR usa el campo raíz `documento` para el NIT del cliente.

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.adquiriente.nombre` | string | ❌ | Nombre del cliente | **39 caracteres** |
| `documento.adquiriente.nit` | string | ❌ | NIT del cliente (redundante con campo `documento`) | **20 caracteres** |
| `documento.adquiriente.email` | string | ❌ | Email del cliente | **100 caracteres** |
| `documento.adquiriente.direccion` | string | ❌ | Dirección del cliente | **200 caracteres** |
| `documento.adquiriente.telefono` | string | ❌ | Teléfono del cliente | **20 caracteres** |

**Referencia Oficial MEYPAR:**
- El campo `documento` (raíz) es el NIT del adquiriente según PDF técnico LogTech
- Formato: String alfanumérico, máximo 50 caracteres
- Consumidor Final Panamá: `"222222222222"`

#### Terminal

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.terminal.CodigoTerminal` | integer | ❌ | Código del terminal | - |
| `documento.terminal.CodigoExterno` | string | ❌ | Código externo del terminal | **20 caracteres** |
| `documento.terminal.NombreTerminal` | string | ❌ | Nombre del terminal | **100 caracteres** |

#### Items del Documento

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.items.*.cantidad` | decimal | ✅ | Cantidad del item | **decimal(12,2)** |
| `documento.items.*.descripcion` | string | ✅ | Descripción del item | **255 caracteres** |
| `documento.items.*.precioUnitario` | decimal | ✅ | Precio unitario | **decimal(16,4)** |
| `documento.items.*.codigoProducto` | string | ✅ | Código del producto | **50 caracteres** |
| `documento.items.*.unidadMedida` | string | ❌ | Unidad de medida | **10 caracteres** |
| `documento.items.*.precioTotalFinalDetalle` | decimal | ✅ | Precio total final | **decimal(16,4)** |

#### Medios de Pago

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.medios_pago.*.codigoMedioPago` | integer | ✅ | 1=Efectivo, 2=Tarjeta, 3=Cheque, 4=Transferencia | - |
| `documento.medios_pago.*.importeMedioPago` | decimal | ✅ | Importe del medio de pago | **decimal(16,4)** |

#### Totales

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.totales.total` | decimal | ✅ | Total del documento | **decimal(16,4)** |
| `documento.totales.subtotal` | decimal | ❌ | Subtotal (antes de impuestos) | **decimal(16,4)** |
| `documento.totales.impuestos` | decimal | ❌ | Total de impuestos | **decimal(16,4)** |
| `documento.totales.descuentos` | decimal | ❌ | Total de descuentos | **decimal(16,4)** |

#### Autorización de Prefijo (Opcional)

| Campo | Tipo | Requerido | Descripción | Límite |
|-------|------|-----------|-------------|---------|
| `documento.autorizacion_prefijo.PrefijoId` | string | ❌ | ID del prefijo | **10 caracteres** |
| `documento.autorizacion_prefijo.ResolucionNumero` | string | ❌ | Número de resolución | **50 caracteres** |

### Ejemplo de Request (Estructura Oficial MEYPAR)

```json
{
  "idFacturador": 12345678,
  "ambiente": 1,
  "documento": "900123456-1",
  "codigo": 2,
  "tipoDocumento": 1,
  "prefijo": "A132",
  "numero": 2025,
  "medioPago": 2,
  "referenciaPago": "123456789",
  "fechaFactura": "2024-02-06",
  "mensaje": "Estacionamiento - 2 horas",
  "observacion": null,
  "autorizacionPrefijo": {
    "PrefijoId": "C1PQ",
    "ResoluciónNumero": "132343423423432",
    "VigenciaFecha": "2025-01-15",
    "DuracionFecha": 24,
    "CorrelativoInicialNum": 1,
    "CorrelativoFinalNum": 30000
  },
  "terminalPagoID": {
    "CodigoTerminal": 4,
    "CodigoExterno": null,
    "NombreTerminal": "PP1-Automatica3"
  },
  "detalleMedioPagoList": [{
    "codigoMedioPago": 2,
    "importeMedioPago": 1.03
  }],
  "detalleFacturaList": [{
    "objectName": "WADetalleFactura",
    "cantidad": 1,
    "descripcion": "Cobro Ticket",
    "precioUnitario": 1.23,
    "codigoProducto": 2,
    "codigoVehiculo": 1,
    "unidadMedida": "UNI",
    "precioTotalSinDescuento": 1.23,
    "precioTotalFinalDetalle": 1.03
  }]
}
```
    },
    "terminal": {
      "CodigoTerminal": 7,
      "CodigoExterno": "TERM-BOG-001",
      "NombreTerminal": "Terminal Centro Comercial Unicentro"
    },
    "items": [
      {
        "objectName": "WADetalleFactura",
        "cantidad": 1,
        "descripcion": "Cobro Estancia Vehiculo - 2 horas 30 min",
        "precioUnitario": 4500.00,
        "codigoProducto": "EST-VEHICULO",
        "codigoVehiculo": 1,
        "unidadMedida": "UNI",
        "precioTotalSinDescuento": 4500.00,
        "precioTotalFinalDetalle": 4500.00,
        "observacion": "150min - Entrada: 14:30, Salida: 17:00"
      },
      {
        "objectName": "WADetalleFactura",
        "cantidad": 1,
        "descripcion": "Recarga Electrica Vehiculo",
        "precioUnitario": 8000.00,
        "codigoProducto": "RECARGA-ELECT",
        "codigoVehiculo": 1,
        "unidadMedida": "KWH",
        "precioTotalSinDescuento": 8000.00,
        "precioTotalFinalDetalle": 8000.00,
        "observacion": "15 KWH - Tiempo: 45min"
      }
    ],
    "medios_pago": [
      {
        "objectName": "WAMedioPagoFactura",
        "codigoMedioPago": 2,
        "importeMedioPago": 12500.00
      }
    ],
    "totales": {
      "total": 12500.00,
      "subtotal": 12500.00,
      "impuestos": 0.00,
      "descuentos": 0.00,
      "moneda": "COP"
    },
    "autorizacion_prefijo": {
      "objectName": "WAAutorizacionPrefijo",
      "PrefijoId": "UNI1",
      "ResolucionNumero": "18764000000123",
      "VigenciaFecha": "2026-12-31",
      "VigenciaDuracion": 24,
      "CorrelativoInicialNum": 1,
      "CorrelativoFinalNum": 50000
    }
  }
}
```

### Respuesta de Éxito (Test)

```json
{
  "response": "HTTP: 201 OK",
  "success": true,
  "message": "Factura Meypar procesada exitosamente",
  "job_id": "uuid-generated-job-id"
}
```

### Validaciones Específicas (Estructura Oficial MEYPAR)

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `idFacturador` | integer | required | ID del obligado a facturar |
| `ambiente` | integer | required, in:1,2 | Ambiente (1=Producción, 2=Pruebas) |
| `documento` | string | required, max:50 | **NIT del adquiriente** (cliente/usuario parking) |
| `codigo` | integer | nullable | Código de operación |
| `tipoDocumento` | integer | required, in:1,2 | Tipo documento (1=Factura, 2=DEE) |
| `prefijo` | string | required, max:10 | Prefijo del documento |
| `numero` | integer | required | **Número de factura** |
| `medioPago` | integer | required, in:0,1,2,3,4 | Medio de pago principal |
| `referenciaPago` | string | nullable, max:50 | Referencia de pago |
| `fechaFactura` | string | required, Y-m-d | Fecha de la factura |
| `mensaje` | string | nullable, max:255 | Mensaje adicional |
| `observacion` | string | nullable, max:500 | Observación general |

**⚠️ Diferencia Crítica:**
- **`documento`**: NIT del **cliente** (ej: `"900123456-1"` o `"222222222222"` para consumidor final)
- **`numero`**: **Número de la factura** (ej: `2025`, `10001`, etc.)
- El `numero` se convierte en `InvoiceNumber` en la base de datos
| `medioPago` | integer | required, in:1,2,3,4 | Medio de pago principal |
| `fechaFactura` | string | required, Y-m-d | Fecha de la factura |

#### Autorización de Prefijo (Opcional)

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `autorizacionPrefijo.PrefijoId` | string | nullable, max:10 | ID del prefijo |
| `autorizacionPrefijo.ResoluciónNumero` | string | nullable, max:50 | Número de resolución |
| `autorizacionPrefijo.VigenciaFecha` | string | nullable, Y-m-d | Fecha de vigencia |
| `autorizacionPrefijo.DuracionFecha` | integer | nullable, 1-9999 | Duración en días |
| `autorizacionPrefijo.CorrelativoInicialNum` | integer | nullable, min:1 | Número correlativo inicial |
| `autorizacionPrefijo.CorrelativoFinalNum` | integer | nullable, min:1 | Número correlativo final |

#### Detalles de Factura (Requeridos)

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `detalleFacturaList[].cantidad` | numeric | required, min:0 | Cantidad del producto |
| `detalleFacturaList[].descripcion` | string | required, max:255 | Descripción del producto |
| `detalleFacturaList[].precioUnitario` | numeric | required, decimal(16,4) | Precio unitario |
| `detalleFacturaList[].codigoProducto` | integer | required | Código del producto |
| `detalleFacturaList[].codigoVehiculo` | integer | nullable | Código del vehículo (estacionamientos) |
| `detalleFacturaList[].precioTotalSinDescuento` | numeric | required, decimal(16,4) | Precio sin descuento |
| `detalleFacturaList[].precioTotalFinalDetalle` | numeric | required, decimal(16,4) | Precio final |

#### Medios de Pago (Requeridos)

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `detalleMedioPagoList[].codigoMedioPago` | integer | required, in:1,2,3,4 | 1=Efectivo, 2=Tarjeta, 3=Cheque, 4=Transferencia |
| `detalleMedioPagoList[].importeMedioPago` | numeric | required, decimal(16,4) | Importe del medio de pago |

**📏 Campos de Texto**
- Todos los campos de texto tienen límites específicos según la estructura de BD
- Si un campo excede el límite, se trunca automáticamente
- Se registra un log de advertencia con el valor original y truncado

**💰 Campos Decimales**
- Todos los precios y cantidades usan regex específicos para validar precisión
- Formato esperado: hasta 12 dígitos enteros con máximo 4 decimales
- Regex: `/^\d{1,12}(\.\d{1,4})?$/`

#### Validaciones de Negocio

- **Items**: Mínimo 1 item requerido
- **Medios de Pago**: Al menos un medio de pago
- **Terminal**: Validación opcional pero recomendada
- **Moneda**: Soporte para COP, USD, PAB
- **Fecha**: Formato YYYY-MM-DD
- **Totales**: Coherencia entre totales globales e items individuales

#### Mensajes de Error en Español

El sistema proporciona mensajes de error específicos y descriptivos:

**Ejemplos de mensajes de validación:**
- `"El número de documento no puede exceder 20 caracteres"`
- `"El nombre del cliente no puede exceder 39 caracteres"`
- `"El precio unitario debe ser un decimal válido con máximo 4 decimales"`
- `"La cantidad no puede exceder 999,999,999,999.9999"`
- `"El importe del medio de pago debe ser un decimal válido con máximo 4 decimales"`

### Casos de Uso

- **Estacionamientos**: Cobro de estancia de vehículos
- **Servicios eléctricos**: Recarga de vehículos eléctricos
- **Servicios adicionales**: Lavado, servicios premium
- **Multi-terminal**: Soporte para múltiples terminales

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Venta procesada exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Notas Técnicas

- **Procesamiento**: Asíncrono usando `CreateSaleMeyparJob`
- **Formato de fecha**: YYYY-MM-DD
- **Monedas**: COP (pesos colombianos), USD, PAB
- **Validación terminal**: Código de terminal único por organización
- **Items específicos**: Soporte para códigos de vehículos y observaciones detalladas

#### Implementación del Servicio MEYPAR

**MeyparService.php**
- **Validación de documentos**: Automática para NITs y cédulas colombianas
- **Totales optimizados**: Usa totales globales de MEYPAR directamente (no calculados)
- **Sanitización de campos**: Límites automáticos según estructura de BD
- **Logging detallado**: Auditoría completa de validaciones y truncamientos
- **Auto-corrección**: Documentos inválidos se convierten a consumidor final

**Mapeo de Campos de Detalle**
- `Net_line`: Usa `precioTotalFinalDetalle` (valor final del item)
- `Sub_Total`: Calcula `unitario × cantidad` (valor base)
- `Subtotal` del header: Usa `totales.subtotal` del JSON MEYPAR
- `Net_due` del header: Usa `totales.total` del JSON MEYPAR

**Estructura de Base de Datos**
- Sales_Header_Imp: Algunos campos decimal(16,2), otros decimal(16,4)
- Sales_Detail_Imp: Campos decimal(16,4)
- CustomerReceiptHeaderImp: Campos decimal(18,4)
- CustomersImp: Custom_field1 (NIT), Custom_field4 (ReceiverType), Custom_field5 (código ubicación)

**Sistema de Custom Fields**
- `Custom_field1`: NIT/Cédula validado
- `Custom_field2`: DV por defecto ('00')
- `Custom_field3`: `null` (limpio)
- `Custom_field4`: ReceiverType automático ('1' empresa, '2' persona/consumidor)
- `Custom_field5`: Código de ubicación organización (solo empresas), `null` para otros

#### Performance y Optimización

**Validaciones Optimizadas**
- Validación de documentos en una sola pasada
- Uso directo de totales globales (elimina cálculos)
- Sanitización con límites exactos de BD
- Logs condicionales para evitar spam

**Manejo de Errores**
- Auto-corrección silenciosa para documentos inválidos
- Logging detallado sin interrumpir el flujo
- Valores por defecto para campos opcionales
- Formateo decimal con validación de límites

---

## Crear Venta Meypar con Emisión Directa

**Endpoint:** `POST /api/v1/fe/create_sale_meypar_with_emission`  
**Controller:** `FeController::createSaleMeyparWithEmission`  
**Request Class:** `CreateSaleMeyparRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea y procesa una factura electrónica con emisión directa basada en datos del sistema Meypar. Este endpoint procesa la venta de manera síncrona incluyendo la emisión automática de la factura electrónica mediante el PAC configurado, con sistema de reintentos integrado.

### Diferencias con `createSaleMeypar`

| Característica | `createSaleMeypar` | `createSaleMeyparWithEmission` |
|----------------|-------------------|-------------------------------|
| **Procesamiento** | Asíncrono (Job) | Síncrono con emisión directa |
| **Emisión FE** | Separada | Automática integrada |
| **Respuesta** | Job despachado | Resultado completo |
| **Reintentos** | Manual | Automático con MeyparService |
| **Tiempo respuesta** | Inmediato | Variable (depende de PAC) |

### Estructura del Request

La estructura del request es **idéntica** a `createSaleMeypar`. Ver sección anterior para detalles completos.

### Ejemplo de Request

```json
{
  "documento": {
    "tipo_documento": "1",
    "numero": "MEYPAR-EMISSION-001",
    "fecha": "2025-08-12",
    "emisor": {
      "nombre": "Estacionamientos MEYPAR Colombia SAS",
      "nit": "900123456-1",
      "direccion": "Calle 123 #45-67, Bogotá D.C.",
      "telefono": "+57 1 2345678",
      "email": "emisor@meypar.co"
    },
    "adquiriente": {
      "nombre": "María Elena Rodríguez",
      "nit": "52123456-8",
      "email": "maria.rodriguez@hotmail.com",
      "direccion": "Carrera 20 #40-25, Medellín",
      "telefono": "+57 300 1234567"
    },
    "terminal": {
      "CodigoTerminal": 12,
      "CodigoExterno": "TERM-MED-002",
      "NombreTerminal": "Terminal Centro Comercial Santafé"
    },
    "items": [
      {
        "objectName": "WADetalleFactura",
        "cantidad": 1,
        "descripcion": "Estacionamiento VIP - 4 horas",
        "precioUnitario": 8000.00,
        "codigoProducto": "EST-VIP",
        "codigoVehiculo": 2,
        "unidadMedida": "UNI",
        "precioTotalSinDescuento": 8000.00,
        "precioTotalFinalDetalle": 8000.00,
        "observacion": "240min - Zona VIP Cubierta"
      }
    ],
    "medios_pago": [
      {
        "objectName": "WAMedioPagoFactura",
        "codigoMedioPago": 1,
        "importeMedioPago": 8000.00
      }
    ],
    "totales": {
      "total": 8000.00,
      "subtotal": 8000.00,
      "impuestos": 0.00,
      "descuentos": 0.00,
      "moneda": "COP"
    }
  }
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Venta MEYPAR creada y emitida exitosamente",
  "attempt": 1,
  "data": {
    "sale": {
      "id": 12345,
      "invoice_number": "MEYPAR-EMISSION-001",
      "customer_name": "María Elena Rodríguez",
      "total": "8000.0000",
      "invoice_date": "2025-08-12",
      "issued": true
    },
    "emission": {
      "success": true,
      "cufe": "FE0120000155702081-2-2021-2600002025081179999999990010127999999993",
      "transition_id": "01K2G9PGACC8BDC4NYP5D5HMZE",
      "numeroDocumentoFiscal": "0000001234",
      "pac_response": {
        "sale": 12345,
        "path": "invoices",
        "transition": "01K2G9PGACC8BDC4NYP5D5HMZE",
        "cufe": "FE0120000155702081-2-2021-2600002025081179999999990010127999999993",
        "codigoSucursalEmisor": "001",
        "numeroDocumentoFiscal": "0000001234",
        "puntoFacturacionFiscal": "001",
        "tipoDocumento": "01",
        "tipoEmision": "01",
        "signedXml": "PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4..."
      }
    }
  }
}
```

#### Campos de la respuesta:

| Campo | Descripción |
|-------|-------------|
| `success` | Indica si el proceso fue exitoso |
| `message` | Mensaje descriptivo del resultado |
| `attempt` | Número de intento que fue exitoso (1-3) |
| `data.sale.id` | ID interno de la venta en DocuCenter |
| `data.sale.invoice_number` | Número de factura MEYPAR |
| `data.sale.issued` | Indica si la factura fue emitida (`true`) |
| `data.emission.cufe` | Código Único de Facturación Electrónica |
| `data.emission.transition_id` | ID de transición del PAC |
| `data.emission.pac_response.signedXml` | XML firmado en base64 (solo Alanube) |

### Respuesta de Error

**Error después de 3 reintentos:**
```json
{
  "success": false,
  "message": "Falló después de 3 intentos",
  "error": "Error procesando venta MEYPAR con emisión"
}
```

#### Tipos de errores específicos:

**1. Error de configuración PAC:**
```json
{
  "success": false,
  "message": "No se encontró configuración PAC para la organización",
  "error": "Error procesando venta MEYPAR con emisión"
}
```

**2. Error de validación de documentos:**
```json
{
  "success": false,
  "message": "The document has already been issued.",
  "error": "Error procesando venta MEYPAR con emisión"
}
```

**3. Error de conexión PAC (Alanube):**
```json
{
  "success": false,
  "message": "Connection timeout",
  "error": "Error procesando venta MEYPAR con emisión"
}
```

**4. Error de respuesta PAC (400/401):**
```json
{
  "success": false,
  "message": "Error message from PAC response",
  "error": "Error procesando venta MEYPAR con emisión"
}
```

### Flujo de Procesamiento

1. **Validación de request**: Usando `CreateSaleMeyparRequest`
2. **Auditoría de transacción**: Almacenar datos originales para auditoría
3. **Creación de venta**: Procesamiento MEYPAR → `SalesHeaderImp` con líneas de detalle
4. **Inicialización del trait**: `mount1()` para configurar propiedades de emisión
5. **Emisión PAC**: `issueDocumentRun()` con validaciones de 6 pasos
6. **Procesamiento XML**: Descarga y codificación base64 (solo Alanube)
7. **Actualización BD**: `EzeeIssued = 1` y `InvoiceNote` con respuesta PAC
8. **Respuesta completa**: Estructura con datos de venta y emisión

### Configuraciones Requeridas

#### PAC Connection
- Configuración PAC activa en la organización
- Token válido y endpoint configurado
- Certificados digitales válidos

#### MeyparService
- Servicio configurado para la organización
- Mapeo de productos Meypar a códigos internos
- Configuración de impuestos y tarifas

### Casos de Uso Ideales

- **Punto de venta**: Emisión inmediata en terminales
- **Aplicaciones móviles**: Facturación instantánea
- **Integraciones críticas**: Cuando se requiere confirmación inmediata
- **Servicios premium**: Para clientes que requieren factura inmediata

### Consideraciones de Performance

| Aspecto | Impacto | Recomendación |
|---------|---------|---------------|
| **Tiempo de respuesta** | Variable (PAC + procesamiento) | Implementar timeout en cliente |
| **Reintentos automáticos** | Hasta 3 intentos con sleep(1-3s) | Mostrar indicador de progreso |
| **Conexión PAC** | Dependiente del proveedor PAC | Monitorear disponibilidad |
| **Procesamiento de datos** | Creación de registros BD | Optimizar queries de inserción |
| **Auditoría de transacciones** | +100ms por almacenamiento | Cache para transacciones repetidas |

### Códigos de Respuesta HTTP

| Código | Descripción | Acción Recomendada |
|--------|-------------|-------------------|
| 200 | Venta procesada y emitida exitosamente | Mostrar factura al usuario |
| 422 | Error de validación de request | Corregir campos según validaciones del CreateSaleMeyparRequest |
| 500 | Error interno del servidor/PAC | Revisar logs o usar método asíncrono como alternativa |

### Monitoreo y Logging

El sistema registra automáticamente:

- **Request ID**: organization_id + user_id + documento_numero
- **Processing attempts**: Número de intento actual (1-3)
- **Sale details**: ID de venta, número de factura, total, cliente
- **Emission results**: CUFE, transition_id, numeroDocumentoFiscal
- **Error tracking**: Mensajes de error específicos por intento
- **Audit trail**: Transacciones almacenadas antes del procesamiento

#### Logs clave a monitorear:

```
CreateSaleMeyparWithEmission: Iniciando procesamiento
MeyparService: Intento X de 3
MeyparService: Procesamiento completado exitosamente
Error en createSaleMeyparWithEmission: [error_message]
```

### Mejores Prácticas

1. **Timeout del cliente**: Configurar timeout mínimo de 30 segundos
2. **Manejo de errores**: Implementar fallback a método asíncrono si falla
3. **Cache de resultados**: Evitar reprocessamiento (verificar `EzeeIssued`)
4. **Monitoring**: Alertas por alta latencia o errores frecuentes
5. **Validación previa**: Verificar configuración PAC antes del envío
6. **XML firmado**: Para Alanube, el XML base64 se incluye en `signedXml`

### Características Especiales de Alanube

- **XML automático**: Descarga y codificación base64 automática del XML firmado
- **Almacenamiento**: XML se guarda como archivo usando `FileUploadService`
- **Auditoría**: Registro del archivo XML en modelo `Archive`
- **Campo `signedXml`**: Incluido en `pac_response` solo para Alanube

## Referencias

- [API MEYPAR Colombia - Documentación Oficial](https://api.meypar.com/docs)
- [DocuCenter - Sistema de Facturación Electrónica](/)
- [Validaciones MEYPAR Colombia - Documentación Técnica](../validations/meypar-validations.md)
- [Validaciones de Documentos - Weirdo Helper](../helpers/document-validation.md)
