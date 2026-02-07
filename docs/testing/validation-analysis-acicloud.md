# Análisis de Validación - CreateSaleAciCloudRequest

## Objeto JSON Analizado

```json
{
  "key": null,
  "dGen": {
    "iDoc": "1",
    "dNroDF": "000976",
    "dPtoFacDF": "200",
    "dFechaEm": "2025-08-21T00:00:00-05:00",
    "iNatOp": "1",
    "iTipoOp": "1",
    "iDest": 1,
    "iTipoTranVenta": "1",
    "dInfEmFE": "INV-000976",
    "dNroDFZ": "INV-000976",
    "dNroDFIDZ": "6027930000003905001",
    "gEmis": {
      "gRucEmi": {
        "dTipoRuc": "1",
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
      "dDirecEm": "DAVID, AVE. OBALDIA, EDF. DOÃ'A EMELDA, LOCAL 3",
      "dTfnEm": "730-2365",
      "dCorElectEmi": "ginarosemena@chyju.com"
    },
    "gDatRec": {
      "iTipoRec": "1",
      "gRucRec": {
        "dTipoRuc": "2",
        "dRuc": "1808755-1-706832",
        "dDV": "97"
      },
      "dNombRec": "CLINICA HOSPITALSAN JUAN DE DIOS",
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
      "dDescProd": "Diazepam 10mg/2ml. LOTE 75TL1965 VENCE 30/11/2026",
      "cUnidad": "und",
      "dCantCodInt": 100,
      "cUnidadCPBS": "und",
      "dCodProd": "007800620035",
      "dInfEmFE": null,
      "gPrecios": {
        "dPrUnit": "3.500000",
        "dPrUnitDesc": "0.000000",
        "dPrItem": "350.000000",
        "dValTotItem": "350.000000"
      },
      "gITBMSItem": {
        "dTasaITBMS": 0,
        "dValITBMS": "0.000000"
      },
      "gISCItem": {
        "dTasaISC": "0.00",
        "dValISC": "0.00"
      },
      "dSecItem": 1
    },
    {
      "dDescProd": "MIDAZOLAM NORMON 15MG/3ML SOLUCION INYECTABLE EFG CX5 AMPOLLAS. LOTE A0041 FECHA 31/01/2027",
      "cUnidad": "und",
      "dCantCodInt": 30,
      "cUnidadCPBS": "und",
      "dCodProd": "8435232319026",
      "dInfEmFE": null,
      "gPrecios": {
        "dPrUnit": "2.500000",
        "dPrUnitDesc": "0.000000",
        "dPrItem": "75.000000",
        "dValTotItem": "75.000000"
      },
      "gITBMSItem": {
        "dTasaITBMS": 0,
        "dValITBMS": "0.000000"
      },
      "gISCItem": {
        "dTasaISC": "0.00",
        "dValISC": "0.00"
      },
      "dSecItem": 2
    }
  ],
  "gTot": {
    "dTotNeto": "425.00",
    "dTotITBMS": "0.00",
    "dTotISC": "0.00",
    "dTotGravado": "0.00",
    "dTotDesc": "0.00",
    "dTotAcar": "0.00",
    "dTotSeg": "0.00",
    "dVTot": "425.00",
    "dTotRec": "425.00",
    "dVuelto": "0.00",
    "iPzPag": 1,
    "dNroItems": 2,
    "dVTotItems": "425.00",
    "gFormaPago": [
      {
        "iFormaPago": "02",
        "dVlrCuota": "425.00"
      }
    ],
    "gRetenc": null,
    "gPagPlazo": null
  },
  "gPedComGl": {
    "dNroPed": 1,
    "dNumAcept": 1,
    "dInfEmPedGI": "Gracias por su confianza..."
  }
}
```

## Análisis de Validaciones

### ❌ ERRORES CRÍTICOS ENCONTRADOS

#### 1. **iTipoRec**: Valor inválido
- **Actual**: `"1"`
- **Requerido**: `"01"` (debe ser string con formato de 2 dígitos)
- **Regla**: `'in:01,02,03,04'`

#### 2. **dTasaITBMS**: Formato incorrecto
- **Actual**: `0` (integer)
- **Requerido**: `"00"` (string con formato de 2 dígitos)
- **Regla**: `'in:00,01,02,03'`

#### 3. **Falta campo obligatorio**: `dDirecRec`
- **Requerido**: Campo obligatorio cuando `iTipoRec` es `"01"`
- **Actual**: Campo ausente

### ⚠️ PROBLEMAS DE FORMATO

#### 4. **Números como string vs integer**
- `iDoc`, `iNatOp`, `iTipoOp` deben ser integers
- `dCantCodInt` debe ser integer pero está correcto

#### 5. **Valores numéricos en strings**
- Los precios están como strings, deberían ser numeric

### ✅ CAMPOS CORRECTOS

- `dNroDF`: Correcto (máximo 10 caracteres)
- `dPtoFacDF`: Correcto (máximo 3 caracteres)
- `dFechaEm`: Formato de fecha correcto
- `iTipoTranVenta`: Valor válido (1-4)
- `gRucEmi`: Estructura correcta
- `cPaisRec`: Valor correcto ("PA")
- `gFormaPago.iFormaPago`: Valor válido ("02")

## Objeto JSON Corregido

```json
{
  "key": null,
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
    "dNroDFZ": "INV-000976",
    "dNroDFIDZ": "6027930000003905001",
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
      "cUnidadCPBS": "und",
      "dCodProd": "007800620035",
      "dInfEmFE": null,
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
    },
    {
      "dSecItem": 2,
      "dDescProd": "MIDAZOLAM NORMON 15MG/3ML SOLUCION INYECTABLE EFG CX5 AMPOLLAS. LOTE A0041 FECHA 31/01/2027",
      "cUnidad": "und",
      "dCantCodInt": 30,
      "cUnidadCPBS": "und",
      "dCodProd": "8435232319026",
      "dInfEmFE": null,
      "gPrecios": {
        "dPrUnit": 2.5,
        "dPrUnitDesc": 0,
        "dPrItem": 75,
        "dValTotItem": 75
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
    "dTotISC": 0,
    "dTotGravado": 0,
    "dTotDesc": 0,
    "dTotAcar": 0,
    "dTotSeg": 0,
    "dVTot": 425,
    "dTotRec": 425,
    "dVuelto": 0,
    "iPzPag": 1,
    "dNroItems": 2,
    "dVTotItems": 425,
    "gFormaPago": [
      {
        "iFormaPago": "02",
        "dVlrCuota": 425
      }
    ],
    "gRetenc": null,
    "gPagPlazo": null
  },
  "gPedComGl": {
    "dNroPed": 1,
    "dNumAcept": 1,
    "dInfEmPedGI": "Gracias por su confianza. Confeccionar pago por ACH o efectivo depósito cuenta CORRIENTE 03-42-01-133177-7 Banco General CHEQUE a nombre de C4 - PHARMA"
  }
}
```

## Recomendaciones

### 1. **Implementar validación automática en prepareForValidation()**
```php
// Agregar en prepareForValidation()
if (isset($data['dGen']['gDatRec']['iTipoRec'])) {
    $data['dGen']['gDatRec']['iTipoRec'] = str_pad($data['dGen']['gDatRec']['iTipoRec'], 2, '0', STR_PAD_LEFT);
}

// Agregar campo dDirecRec si no existe
if (!isset($data['dGen']['gDatRec']['dDirecRec']) && isset($data['dGen']['gDatRec']['gUbiRec']['dCorreg'])) {
    $data['dGen']['gDatRec']['dDirecRec'] = $data['dGen']['gDatRec']['gUbiRec']['dCorreg'];
}
```

### 2. **Revisar lógica de negocio**
- Validar que los totales calculados coincidan
- Implementar validación cruzada entre campos relacionados

### 3. **Mejorar manejo de tipos de datos**
- Asegurar que integers sean integers
- Asegurar que numerics sean numeric
- Asegurar que strings con formato específico cumplan el patrón
