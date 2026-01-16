# API Facturación Electrónica - Integración ACICloud FE

## Crear Venta ACICloud

**Endpoint:** `POST /api/v1/fe/create_sale_acicloud`  
**Controller:** `FeController::createSaleAcicloud`  
**Request Class:** `CreateSaleAciCloudRequest`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Crea una factura electrónica basada en la estructura dGen (Datos Generales) y gDatRec (Grupo de Datos del Receptor) del estándar de facturación electrónica panameño.

### Estructura del Request

#### Datos Generales (dGen)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `dGen.dFechaEm` | string | ✅ | Fecha de emisión (DD/MM/YYYY) |
| `dGen.dSalCond` | integer | ✅ | Condición de la operación (1=Contado, 2=Crédito) |
| `dGen.dTiOpe` | integer | ✅ | Tipo de operación |
| `dGen.iNatVen` | integer | ✅ | Naturaleza de la venta |

#### Datos del Receptor (gDatRec)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `gDatRec.iNatRec` | integer | ✅ | Naturaleza del receptor (1=Contribuyente, 2=No contribuyente) |
| `gDatRec.iTipoRec` | integer | ✅ | Tipo de receptor (1=Natural, 2=Jurídica) |
| `gDatRec.cPaisRec` | string | ✅ | País del receptor (código ISO) |
| `gDatRec.dNombRec` | string | ✅ | Nombre del receptor |
| `gDatRec.dRucRec` | string | ✅ | RUC del receptor |
| `gDatRec.dDVRec` | string | ✅ | Dígito verificador |
| `gDatRec.dTelRec` | string | ❌ | Teléfono del receptor |
| `gDatRec.dCorElecRec` | string | ✅ | Email del receptor |
| `gDatRec.dDirRec` | string | ❌ | Dirección del receptor |

#### Ítems/Productos (gItem)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `gItem[].dCodProd` | string | ✅ | Código del producto |
| `gItem[].dDesProd` | string | ✅ | Descripción del producto |
| `gItem[].cUnidad` | string | ✅ | Unidad de medida |
| `gItem[].dCantCodInt` | number | ✅ | Cantidad |
| `gItem[].dPrUnit` | number | ✅ | Precio unitario |
| `gItem[].dPrUnitDesc` | number | ❌ | Precio unitario con descuento |
| `gItem[].dPrItem` | number | ✅ | Precio total del ítem |
| `gItem[].dPrAcarItem` | number | ❌ | Precio acarreado del ítem |
| `gItem[].dValTotItem` | number | ✅ | Valor total del ítem |

#### Totales (gTot)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `gTot.dSub` | number | ✅ | Subtotal |
| `gTot.dDescGlobal` | number | ❌ | Descuento global |
| `gTot.dVTot` | number | ✅ | Valor total antes de impuestos |
| `gTot.dTotImp` | number | ✅ | Total de impuestos |
| `gTot.dVTotFact` | number | ✅ | Valor total de la factura |

### Ejemplo de Request (Estructura Completa)

```json
{
  "dGen": {
    "dFechaEm": "28/01/2025",
    "dSalCond": 1,
    "dTiOpe": 1,
    "iNatVen": 1
  },
  "gDatRec": {
    "iNatRec": 1,
    "iTipoRec": 1,
    "cPaisRec": "PA",
    "dNombRec": "Juan Pérez",
    "dRucRec": "8-123-456",
    "dDVRec": "1",
    "dTelRec": "6612-3456",
    "dCorElecRec": "juan.perez@email.com",
    "dDirRec": "Calle 50, Edificio Torre Global, Piso 15"
  },
  "gItem": [
    {
      "dCodProd": "PROD001",
      "dDesProd": "Software de Gestión Empresarial",
      "cUnidad": "UND",
      "dCantCodInt": 1,
      "dPrUnit": 150.00,
      "dPrUnitDesc": 135.00,
      "dPrItem": 135.00,
      "dPrAcarItem": 0.00,
      "dValTotItem": 135.00
    },
    {
      "dCodProd": "SERV001",
      "dDesProd": "Servicio de Implementación",
      "cUnidad": "HOR",
      "dCantCodInt": 8,
      "dPrUnit": 75.00,
      "dPrUnitDesc": 75.00,
      "dPrItem": 600.00,
      "dPrAcarItem": 0.00,
      "dValTotItem": 600.00
    }
  ],
  "gTot": {
    "dSub": 735.00,
    "dDescGlobal": 35.00,
    "dVTot": 700.00,
    "dTotImp": 49.00,
    "dVTotFact": 749.00
  }
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Factura ACICloud procesada exitosamente",
  "job_id": "uuid-generated-job-id",
  "fecha_emision": "28/01/2025",
  "ruc_receptor": "8-123-456",
  "valor_total": 749.00
}
```

### Validaciones Específicas

#### Formateo Automático

El sistema aplica formateo automático a los datos:

```php
// Fechas: Convierte Y-m-d a d/m/Y
"2025-01-28" → "28/01/2025"

// Números: Formatea con 2 decimales
"150" → "150.00"

// RUC: Valida formato panameño
"8-123-456" → Válido
"155757563-2-2024" → Válido (empresa)
```

#### Validación de RUC

- **Persona Natural**: `P-T-A` (ej: `8-123-456`)
- **Empresa**: `RP-TS-NA` (ej: `155757563-2-2024`)
- **Dígito Verificador**: Calculado automáticamente

#### Códigos Estándar

- **Condición Venta**: 1=Contado, 2=Crédito
- **Naturaleza Receptor**: 1=Contribuyente, 2=No contribuyente
- **Tipo Receptor**: 1=Natural, 2=Jurídica
- **País**: Códigos ISO (PA, US, CO, etc.)

### Estructura de Datos Técnica

#### Automatización de Campos

El sistema automatiza varios campos basándose en el contenido:

```php
// Auto-cálculo de dígito verificador
$dGen['dDVRec'] = $this->calculateDV($gDatRec['dRucRec']);

// Formateo de fechas
$dGen['dFechaEm'] = Carbon::parse($input['fecha'])->format('d/m/Y');

// Formateo de montos
$gTot['dVTotFact'] = number_format($total, 2, '.', '');
```

### Casos de Uso

- **Facturación Directa**: Emisión desde ACICloud
- **Integración B2B**: Conectores entre sistemas
- **Cumplimiento Fiscal**: Estructura estándar DGI
- **Automatización**: Procesos masivos de facturación

### Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Factura procesada exitosamente |
| 400 | Estructura de datos inválida |
| 401 | No autorizado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

### Notas Técnicas

- **Procesamiento**: Asíncrono usando `CreateSaleAciCloudJob`
- **Estructura**: Basada en estándar DGI panameño
- **Formateo**: Automático para fechas y números
- **Validación**: RUC y códigos estándar
- **Cálculos**: Automáticos para totales y DV
- **Compatibilidad**: Estructura dGen/gDatRec estándar
