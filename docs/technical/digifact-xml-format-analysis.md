# Análisis del Formato XML de Digifact

## Fecha de Análisis
30 de diciembre de 2025

## Origen del Código
Función: `configureFormatterDigifact(datos, test)`
Tipo: Transformer JavaScript que convierte objeto DGI a XML Digifact

## Estructura del XML Generado

### 1. Root y Metadatos
```xml
<Root>
  <Version>1.00</Version>
  <CountryCode>PA</CountryCode>
```

### 2. Header - Información del Documento
```javascript
// Mapeo DGI → XML Digifact
dGen.iDoc              → Header.DocType
dGen.dFechaEm          → Header.IssuedDateTime
test (boolean)         → Header.AdditionalIssueType (1=test, 2=prod)
dGen.gFExp.dCambio     → Header.ExchangeRate
dGen.gFExp.cMoneda     → Header.Currency
```

#### AdditionalIssueDocInfo (Elementos Info con Name/Value)
```javascript
dGen.iTpEmis           → Info[Name="TipoEmision"]
dGen.dNroDF            → Info[Name="NumeroDF"]
dGen.dPtoFacDF         → Info[Name="PtoFactDF"]
dGen.dSeg              → Info[Name="CodigoSeguridad"]
dGen.iNatOp            → Info[Name="NaturalezaOperacion"]
dGen.iTipoOp           → Info[Name="TipoOperacion"]
dGen.iDest             → Info[Name="DestinoOperacion"]
dGen.iFormCAFE         → Info[Name="FormatoGeneracion"]
dGen.iEntCAFE          → Info[Name="ManeraEntrega"]
dGen.dEnvFE            → Info[Name="EnvioContenedor"]
dGen.iProGen           → Info[Name="ProcesoGeneracion"]
dGen.iTipoTranVenta    → Info[Name="TipoTransaccion"]
dGen.iTipoSuc          → Info[Name="TipoSucursal"]
```

### 3. Seller - Información del Emisor
```javascript
// Identificación
dGen.gEmis.gRucEmi.dRuc       → Seller.TaxID
dGen.gEmis.gRucEmi.dTipoRuc   → Seller.TaxIDType
dGen.gEmis.gRucEmi.dDV        → Seller.TaxIDAdditionalInfo.Info[Name="DigitoVerificador"]
dGen.gEmis.dNombEm            → Seller.Name

// Contacto
dGen.gEmis.dTfnEm             → Seller.Contact.PhoneList.Phone
dGen.gEmis.dCorElectEmi       → Seller.Contact.EmailList.Email

// Sucursal
dGen.gEmis.dSucEm             → Seller.BranchInfo.Code
dGen.gEmis.dDirecEm           → Seller.BranchInfo.AddressInfo.Address
dGen.gEmis.gUbiEm.dCorreg     → Seller.BranchInfo.AddressInfo.City
dGen.gEmis.gUbiEm.dDistr      → Seller.BranchInfo.AddressInfo.District
dGen.gEmis.gUbiEm.dProv       → Seller.BranchInfo.AddressInfo.State
"PA" (hardcoded)              → Seller.BranchInfo.AddressInfo.Country

// Información Adicional
dGen.gEmis.dCoordEm           → Seller.BranchInfo.AdditionalBranchInfo.Info[Name="CoordEm"]
dGen.gEmis.gUbiEm.dCodUbi     → Seller.BranchInfo.AdditionalBranchInfo.Info[Name="CodUbi"]
```

### 4. Buyer - Información del Receptor
```javascript
// Identificación
dGen.gDatRec.gRucRec.dRuc     → Buyer.TaxID
dGen.gDatRec.gRucRec.dTipoRuc → Buyer.TaxIDType

// TaxIDAdditionalInfo (Elementos Info con Name/Value)
dGen.gDatRec.iTipoRec         → Info[Name="TipoReceptor"]
dGen.gDatRec.gRucRec.dDV      → Info[Name="DigitoVerificador"]
dGen.gDatRec.gUbiRec.dCodUbi  → Info[Name="CodUbi"]
dGen.gDatRec.gRucRec.dRuc     → Info[Name="CedulaCF"] (solo si iTipoRec === 2)
dGen.gDatRec.gIdExt.dIdExt    → Info[Name="NumIdTE"]
dGen.gDatRec.gIdExt.dIdExt    → Info[Name="NumPasaporte"]
dGen.gDatRec.gIdExt.dPaisExt  → Info[Name="PaisExt"]

// Datos Básicos
dGen.gDatRec.dNombRec         → Buyer.Name

// Contacto
dGen.gDatRec.dTfnRec          → Buyer.Contact.PhoneList.Phone
dGen.gDatRec.dCorElectRec     → Buyer.Contact.EmailList.Email

// Ubicación
dGen.gDatRec.cPaisRec         → Buyer.AdditionlInfo.Info[Name="PaisReceptorFE"]
dGen.gDatRec.dDirecRec        → Buyer.AddressInfo.Address
dGen.gDatRec.gUbiRec.dCorreg  → Buyer.AddressInfo.City
dGen.gDatRec.gUbiRec.dDistr   → Buyer.AddressInfo.District
dGen.gDatRec.gUbiRec.dProv    → Buyer.AddressInfo.State
dGen.gDatRec.cPaisRec         → Buyer.AddressInfo.Country
```

### 5. Items - Productos/Servicios
```javascript
// Por cada item en gItem[]
gItem[].dCodProd              → Item.Codes.Code[Name="CodigoProd"]
gItem[].dCodCPBSabr           → Item.Codes.Code[Name="CodCPBSabr"]
gItem[].dCodCPBScmp           → Item.Codes.Code[Name="CodCPBScmp"]
gItem[].cUnidadCPBS           → Item.Codes.Code[Name="UnidadCPBS"]

gItem[].dDescProd             → Item.Description
gItem[].dCantCodInt           → Item.Qty (formato: .toFixed(4))
gItem[].cUnidad               → Item.UnitOfMeasure
gItem[].gPrecios.dPrUnit      → Item.Price (formato: .toFixed(4))

// Descuentos
gItem[].gPrecios.dPrUnitDesc  → Item.Discounts.Discount.Amount

// Impuestos
gItem[].gITBMSItem.dTasaITBMS → Item.Taxes.Tax.Code (zeroFill 2 dígitos)
"ITBMS" (hardcoded)           → Item.Taxes.Tax.Description
dTasaITBMS / 100              → Item.Taxes.Tax.Rate (formato: .toFixed(4))
gItem[].gITBMSItem.dValITBMS  → Item.Taxes.Tax.Amount

// Totales del Item
dAPrecio (calculado)          → Item.Totals.TotalBDiscount (dPrUnit * dCantCodInt)
gItem[].gPrecios.dPrItem      → Item.Totals.TotalWDiscount
gItem[].gPrecios.dPrItem      → Item.Totals.TotalBTaxes
gItem[].gPrecios.dValTotItem  → Item.Totals.TotalWTaxes
gItem[].gPrecios.dValTotItem  → Item.Totals.SpecificTotal
gItem[].gPrecios.dValTotItem  → Item.Totals.TotalItem
```

### 6. Totals - Totales del Documento
```javascript
gItem.length                  → Totals.QtyItems

// Descuentos
gTot.dTotDesc                 → Totals.TotalDiscounts.Discount.Description
gTot.dTotDesc                 → Totals.TotalDiscounts.Discount.Amount

// Gran Total
gTot.dTotNeto                 → Totals.GrandTotal.TotalBDiscounts
gTot.dVTotItems               → Totals.GrandTotal.TotalWDiscounts
gTot.dVTot                    → Totals.GrandTotal.InvoiceTotal
```

### 7. Payments - Formas de Pago
```javascript
// Por cada formaPago en gTot.gFormaPago[]
gFormaPago[].iFormaPago       → Payment.Type
gFormaPago[].dFormaPagoDesc   → Payment.Description
gFormaPago[].dVlrCuota        → Payment.Amount (formato: .toFixed(4))
```

### 8. AdditionalDocumentInfo - Información Adicional
```javascript
// Dirección (repetida del emisor)
dGen.gEmis.dDirecEm           → AdditionalInfo.AddressInfo.Address
dGen.gEmis.gUbiEm.dCorreg     → AdditionalInfo.AddressInfo.City
dGen.gEmis.gUbiEm.dDistr      → AdditionalInfo.AddressInfo.District
dGen.gEmis.gUbiEm.dProv       → AdditionalInfo.AddressInfo.State
"PA" (hardcoded)              → AdditionalInfo.AddressInfo.Country

// Términos de Pago
gTot.iPzPag                   → AdditionalInfo.AditionalInfo.Info[Name="TiempoPago"]
```

## Características del Transformer

### 1. Validaciones con `Invoice.checkVariable()`
- Verifica que el valor no sea null/undefined antes de agregarlo al XML
- Omite campos opcionales si no tienen valor

### 2. Formateo de Números
```javascript
// Cantidades y precios: 4 decimales
dCantCodInt.toFixed(4)
dPrUnit.toFixed(4)

// Cálculos
dAPrecio = dPrUnit * dCantCodInt
```

### 3. Zero Fill
```javascript
Invoice.zeroFill(value, length)
// Ejemplos:
// iDoc → 2 dígitos: "01", "02", etc.
// dNroDF → 10 dígitos: "0000000001"
// dSeg → 9 dígitos: "000000001"
// iTpEmis → 2 dígitos: "01"
// iNatOp → 2 dígitos: "01"
// dTasaITBMS → 2 dígitos: "07" (para 7%)
```

### 4. Lógica Condicional
```javascript
// AdditionalIssueType según ambiente
const additionalIssueType = test === false ? 2 : 1;
// 1 = Ambiente de pruebas
// 2 = Ambiente de producción

// CedulaCF solo si iTipoRec === 2
Value: (iTipoRec === 2) ? get(datos, "dGen.gDatRec.gRucRec.dRuc", null) : null
```

### 5. Valores por Defecto
```javascript
dGen.iDoc → default "1"
dGen.iTpEmis → default "1"
dGen.iNatOp → default "1"
dGen.iFormCAFE → default 3
dGen.iEntCAFE → default 3
dGen.dEnvFE → default 1
dGen.iProGen → default 1
dGen.dSeg → Invoice.generateRandomNumber() si no existe
```

## Observaciones Importantes

### 1. Formato Completamente Diferente
Este XML **NO es el formato DGI estándar**. Es una estructura propietaria de Digifact que:
- Usa nombres en inglés (Seller, Buyer, Items)
- Tiene estructura jerárquica diferente
- Requiere transformación completa del objeto DGI

### 2. Información Duplicada
Algunos datos se repiten en diferentes secciones:
- Dirección del emisor aparece en Seller.BranchInfo y AdditionalDocumentInfo
- País del receptor en Buyer.AdditionlInfo y Buyer.AddressInfo

### 3. Cálculos en el Transformer
```javascript
// Total antes de descuento por ítem
dAPrecio = dPrUnit * dCantCodInt

// Acumulador de totales
quantityItems = quantityItems + dCantCodInt
totalDPrAcarItem = totalDPrAcarItem + dPrAcarItem
```

### 4. Campos con Múltiples Nombres
El código mapea el mismo valor a múltiples campos Info:
```javascript
dGen.gDatRec.gIdExt.dIdExt → Info[Name="NumIdTE"]
dGen.gDatRec.gIdExt.dIdExt → Info[Name="NumPasaporte"]
```

## Preguntas Críticas

1. **¿Este XML es para qué endpoint?**
   - ¿Es para `/transform/nuc`? (XML endpoint)
   - ¿Es diferente de `/transform/nuc_json`? (JSON endpoint)

2. **¿Cuál es la versión correcta de la API?**
   - Este código sugiere un formato XML personalizado
   - La documentación menciona `/transform/nuc_json` que acepta JSON DGI
   - ¿Coexisten ambos formatos?

3. **¿Dónde está este código actualmente?**
   - ¿Es código JavaScript del frontend?
   - ¿Existe una versión PHP equivalente?
   - ¿Se está usando actualmente en producción?

## Siguientes Pasos

1. **Ubicar el código fuente completo**
   - Buscar en el proyecto el archivo que contiene `configureFormatterDigifact`
   - Verificar si existe versión PHP

2. **Identificar el endpoint correcto**
   - Confirmar si este XML va a `/transform/nuc` (XML)
   - O si existe otro endpoint que use este formato

3. **Decidir estrategia de implementación**
   - ¿Mantener el formato JSON DGI con `/transform/nuc_json`?
   - ¿Implementar transformer XML como TheFactoryHKA?
   - ¿Soportar ambos formatos?

4. **Comparar con documentación oficial**
   - Verificar si este formato XML está documentado
   - Confirmar campos requeridos vs opcionales
