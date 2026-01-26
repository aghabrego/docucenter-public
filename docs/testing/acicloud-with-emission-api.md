# Nueva API ACI Cloud con Emisión Síncrona

## **API Creada: `/api/v1/fe/create_sale_acicloud_with_emission`**

### **Método**: `POST`
### **Headers**: 
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

### **Diferencias con APIs Existentes**

| API | Comportamiento | Retorna |
|-----|---------------|---------|
| `/create_sale_acicloud` | **Asíncrono** - Job en background | Confirmación inmediata |
| `/create_sale_acicloud_without_issuing` | **Asíncrono** - Solo guarda, no emite | Confirmación inmediata |
| `/create_sale_acicloud_with_emission` | ⏱**Síncrono** - Espera resultado completo | **Resultado de emisión** |

## **Funcionalidades Implementadas**

### 1. **Procesamiento Síncrono**
- Procesa la venta ACI Cloud inmediatamente
- Espera a que complete la emisión de FE
- Retorna el resultado completo

### 2. **Reutilización de Servicios Existentes**
- Usa `ACIcloudService` existente (sin duplicar código)
- Mismas validaciones que el Job asíncrono
- Misma lógica de emisión de FE

### 3. **Tracking y Auditoría**
- Almacena transacción para auditoría
- Logs detallados del proceso
- **Control de reintentos**: Máximo 2 intentos por documento
- Manejo de errores completo

### 4. **Formato de Respuesta**
```json
{
  "success": true,
  "message": "Venta ACI Cloud creada y emitida exitosamente",
  "attempt": 1,
  "data": {
    "sale": {
      "id": 12345,
      "invoice_number": "000976",
      "customer_name": "CLINICA HOSPITALSAN JUAN DE DIOS",
      "subtotal": 425.00,
      "net_due": 425.00,
      "date": "2025-08-21T00:00:00-05:00",
      "issued": true,
      "attempt_counter": 1
    },
    "emission": {
      "cufe": "abc123...",
      "status": "approved",
      "message": "Documento autorizado"
    }
  }
}
```

### 5. **Control de Reintentos**
- **Máximo 2 intentos** por documento
- **Verificación automática** del contador de intentos
- **Error específico** si se exceden los intentos:
```json
{
  "success": false,
  "message": "Se excede la cantidad de intentos de emisión para este documento. Intentos previos: 2",
  "error": "Error procesando venta ACI Cloud con emisión"
}
```

## **Script de Prueba con cURL**

```bash
#!/bin/bash

# API ACI Cloud con Emisión Síncrona
curl -X POST "https://docucenter.app/api/v1/fe/create_sale_acicloud_with_emission" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "dGen": {
      "iDoc": 1,
      "dNroDF": "000976",
      "dPtoFacDF": "200",
      "dFechaEm": "2025-08-21T00:00:00-05:00",
      "iNatOp": 1,
      "iTipoOp": 1,
      "iDest": 1,
      "iTipoTranVenta": 1,
      "dInfEmFE": "INV-000976",
      "gEmis": {
        "gRucEmi": {
          "dTipoRuc": 1,
          "dRuc": "4-717-1238",
          "dDV": "05"
        },
        "gUbiEm": {
          "dCodUbi": "4-6-1",
          "dCorreg": "DAVID (CABECERA)",
          "dDistr": "DAVID",
          "dProv": "CHIRIQUI"
        },
        "dNombEm": "C4-PHARMA",
        "dSucEm": "0003",
        "dCoordEm": "+8.43803,-82.4267",
        "dDirecEm": "DAVID, AVE. OBALDIA, EDF. DOÑA EMELDA, LOCAL 3",
        "dTfnEm": "730-2365",
        "dCorElectEmi": "ginarosemena@chyju.com"
      },
      "gDatRec": {
        "iTipoRec": "01",
        "gRucRec": {
          "dTipoRuc": 2,
          "dRuc": "1808755-1-706832",
          "dDV": "97"
        },
        "dNombRec": "CLINICA HOSPITALSAN JUAN DE DIOS",
        "dDirecRec": "SANTIAGO, VERAGUAS",
        "gUbiRec": {
          "dCodUbi": "9-10-1",
          "dCorreg": "SANTIAGO, VERAGUAS",
          "dDistr": "VERAGUAS",
          "dProv": "VERAGUAS"
        },
        "dCorElectRec": "clinicasanjuandedios_@hotmail.com",
        "cPaisRec": "PA",
        "dPaisRecDesc": "Panamá"
      }
    },
    "gItem": [
      {
        "dSecItem": 1,
        "dDescProd": "Diazepam 10mg/2ml. LOTE 75TL1965 VENCE 30/11/2026",
        "cUnidad": "und",
        "dCantCodInt": 100,
        "dCodProd": "007800620035",
        "gPrecios": {
          "dPrUnit": 3.5,
          "dPrUnitDesc": 0,
          "dPrItem": 350,
          "dValTotItem": 350
        },
        "gITBMSItem": {
          "dTasaITBMS": "00",
          "dValITBMS": 0
        },
        "gISCItem": {
          "dTasaISC": 0,
          "dValISC": 0
        }
      }
    ],
    "gTot": {
      "dTotNeto": 425,
      "dTotITBMS": 0,
      "dTotGravado": 0,
      "dTotDesc": 0,
      "dVTot": 425,
      "dTotRec": 425,
      "iPzPag": 1,
      "dNroItems": 1,
      "dVTotItems": 425,
      "gFormaPago": [
        {
          "iFormaPago": "02",
          "dVlrCuota": 425
        }
      ]
    }
  }'
```

## **Ventajas de la Nueva API**

### **Para Integraciones que Necesitan Confirmación Inmediata**
- **ERP/POS en tiempo real**: Saben inmediatamente si la FE fue exitosa
- **Workflows síncronos**: No necesitan polling/webhooks
- **Validación inmediata**: Errores detectados al momento

### **Para Desarrolladores**
- **Misma estructura de datos** que las APIs existentes
- **Mismas validaciones automáticas** (padding, campos requeridos)
- **Compatibilidad total** con el objeto JSON validado

### **Para Operaciones**
- **Logs detallados** del proceso completo
- **Tracking de transacciones** para auditoría
- **Control de reintentos** automático (máximo 2 intentos)
- **Manejo de errores** específicos

## **Cuándo Usar Cada API**

| Escenario | API Recomendada |
|-----------|-----------------|
| **Integración ERP/POS en tiempo real** | `create_sale_acicloud_with_emission` |
| **Procesamiento masivo/batch** | `create_sale_acicloud` |
| **Solo guardar para revisión** | `create_sale_acicloud_without_issuing` |
| **Workflows que requieren confirmación** | `create_sale_acicloud_with_emission` |

## **Estado de Implementación**

- **Controlador**: `FeController::createSaleAciCloudWithEmission()`
- **Ruta**: `POST /api/v1/fe/create_sale_acicloud_with_emission`
- **Validaciones**: Reutiliza `CreateSaleAciCloudRequest`
- **Servicio**: Usa `ACIcloudService` existente
- **Testing**: Compatible con objeto JSON validado

**La API está lista para usar!**
