# Digifact PAC - Análisis de Endpoints JSON vs XML

## Fecha de Análisis
30 de diciembre de 2025

## Hallazgos

### 1. Endpoints Disponibles

Según las pruebas realizadas, Digifact ofrece DOS endpoints diferentes:

#### Endpoint XML (Documentado Oficialmente)
```
POST https://testnucpa.digifact.com/api/transform/nuc
Content-Type: application/xml
Query Params: ?TAXID={ruc}&USERNAME={user}&FORMAT=PDF|XML|HTML
```

**Formato**: XML NUC (Non-Uniform Commerce) con estructura propietaria Digifact
- Nombres en inglés: `<Header>`, `<Seller>`, `<Buyer>`, `<Items>`
- Elementos Info: `<Info Name="..." Value="..."/>`
- **Documentado en**: Documentacion Tecnica NUC Panama.pdf (Version 1.01)

#### Endpoint JSON (Descubierto, No Documentado)
```
POST https://testnucpa.digifact.com/api/transform/nuc_json
Content-Type: application/json
Query Params: ?TAXID={ruc}&USERNAME={user}&FORMAT=PDF|XML|HTML
Authorization: Bearer {token}
```

**Estado**: 
- ⚠️ **Endpoint existe** pero requiere autenticación Bearer Token
- ⚠️ **No está documentado** en el PDF oficial
- ❓ **Formato desconocido**: ¿Acepta JSON DGI o JSON NUC?

---

## 2. Formato XML NUC (Oficial)

### Características
- **Completamente diferente** del formato DGI de Panamá
- Requiere **transformación completa** del objeto
- **Case sensitive**: Los nombres de campos deben respetar mayúsculas/minúsculas

### Ejemplo Mínimo

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <Version>1.00</Version>
  <CountryCode>PA</CountryCode>
  
  <Header>
    <DocType>01</DocType>
    <IssuedDateTime>2025-12-30T10:00:00-05:00</IssuedDateTime>
    <AdditionalIssueType>2</AdditionalIssueType>
    <AdditionalIssueDocInfo>
      <Info Name="TipoEmision" Value="01"/>
      <Info Name="NumeroDF" Value="0000000001"/>
      <Info Name="PtoFactDF" Value="001"/>
      <Info Name="CodigoSeguridad" Value="123456789"/>
      <Info Name="NaturalezaOperacion" Value="01"/>
      <Info Name="TipoOperacion" Value="1"/>
      <Info Name="DestinoOperacion" Value="1"/>
      <Info Name="FormatoOperacion" Value="3"/>
      <Info Name="ManeraEntrega" Value="3"/>
      <Info Name="EnvioContenedor" Value="3"/>
      <Info Name="ProcesoGeneracion" Value="3"/>
    </AdditionalIssueDocInfo>
  </Header>
  
  <Seller>
    <TaxID>155704849-2-2021</TaxID>
    <TaxIDType>2</TaxIDType>
    <TaxIDAdditionalInfo>
      <Info Name="DigitoVerificador" Value="-28"/>
    </TaxIDAdditionalInfo>
    <Name>EMPRESA DE PRUEBA SA</Name>
    <Contact>
      <PhoneList><Phone>507-1234-5678</Phone></PhoneList>
      <EmailList><Email>test@empresa.com</Email></EmailList>
    </Contact>
    <BranchInfo>
      <Code>0000</Code>
      <AddressInfo>
        <Address>Calle 50</Address>
        <City>San Francisco</City>
        <District>Panama</District>
        <State>Panama</State>
        <Country>PA</Country>
      </AddressInfo>
    </BranchInfo>
  </Seller>
  
  <Buyer>
    <TaxID>8-123-456</TaxID>
    <TaxIDType>1</TaxIDType>
    <TaxIDAdditionalInfo>
      <Info Name="TipoReceptor" Value="1"/>
    </TaxIDAdditionalInfo>
    <Name>CLIENTE PRUEBA</Name>
    <AddressInfo>
      <Country>PA</Country>
    </AddressInfo>
  </Buyer>
  
  <Items>
    <Item>
      <Description>Producto de Prueba</Description>
      <Qty>1.0000</Qty>
      <UnitOfMeasure>UND</UnitOfMeasure>
      <Price>100.0000</Price>
      <Discounts>
        <Discount><Amount>0.0000</Amount></Discount>
      </Discounts>
      <Taxes>
        <Tax>
          <Code>01</Code>
          <Description>ITBMS</Description>
          <Rate>0.0700</Rate>
          <Amount>7.0000</Amount>
        </Tax>
      </Taxes>
      <Totals>
        <TotalBDiscount>100.0000</TotalBDiscount>
        <TotalWDiscount>100.0000</TotalWDiscount>
        <TotalBTaxes>100.0000</TotalBTaxes>
        <TotalWTaxes>107.0000</TotalWTaxes>
        <SpecificTotal>107.0000</SpecificTotal>
        <TotalItem>107.0000</TotalItem>
      </Totals>
    </Item>
  </Items>
  
  <Totals>
    <QtyItems>1</QtyItems>
    <TotalDiscounts>
      <Discount><Amount>0.0000</Amount></Discount>
    </TotalDiscounts>
    <GrandTotal>
      <TotalBDiscounts>100.0000</TotalBDiscounts>
      <TotalWDiscounts>100.0000</TotalWDiscounts>
      <InvoiceTotal>107.0000</InvoiceTotal>
    </GrandTotal>
  </Totals>
  
  <Payments>
    <Payment>
      <Type>1</Type>
      <Description>Efectivo</Description>
      <Amount>107.0000</Amount>
    </Payment>
  </Payments>
</Root>
```

---

## 3. Formato JSON DGI (DocuCenter)

### Ejemplo del mismo documento en formato DGI

```json
{
  "dGen": {
    "iDoc": "01",
    "dFechaEm": "2025-12-30T10:00:00-05:00",
    "dNroDF": "0000000001",
    "dPtoFacDF": "001",
    "iTpEmis": "01",
    "dSeg": "123456789",
    "iNatOp": "01",
    "iTipoOp": 1,
    "iDest": 1,
    "iFormCAFE": 3,
    "iEntCAFE": 3,
    "dEnvFE": 1,
    "iProGen": 1,
    "gEmis": {
      "dNombEm": "EMPRESA DE PRUEBA SA",
      "dDirecEm": "Calle 50",
      "dTfnEm": "507-1234-5678",
      "dCorElectEmi": "test@empresa.com",
      "dSucEm": "0000",
      "gRucEmi": {
        "dRuc": "155704849-2-2021",
        "dTipoRuc": "2",
        "dDV": "-28"
      },
      "gUbiEm": {
        "dProv": "Panama",
        "dDistr": "Panama",
        "dCorreg": "San Francisco"
      }
    },
    "gDatRec": {
      "iTipoRec": 1,
      "dNombRec": "CLIENTE PRUEBA",
      "cPaisRec": "PA",
      "gRucRec": {
        "dRuc": "8-123-456",
        "dTipoRuc": "1"
      }
    }
  },
  "gItem": [
    {
      "dSecItem": 1,
      "dDescProd": "Producto de Prueba",
      "dCantCodInt": 1.0,
      "cUnidad": "UND",
      "gPrecios": {
        "dPrUnit": 100.00,
        "dPrUnitDesc": 0.00,
        "dPrItem": 100.00,
        "dValTotItem": 107.00
      },
      "gITBMSItem": {
        "dTasaITBMS": 7,
        "dValITBMS": 7.00
      }
    }
  ],
  "gTot": {
    "dTotNeto": 100.00,
    "dVTotItems": 100.00,
    "dTotDesc": 0.00,
    "dTotITBMS": 7.00,
    "dVTot": 107.00,
    "gFormaPago": [
      {
        "iFormaPago": "1",
        "dFormaPagoDesc": "Efectivo",
        "dVlrCuota": 107.00
      }
    ]
  }
}
```

---

## 4. Mapeo DGI → NUC XML

### Header (Información General)

| DGI | NUC XML |
|-----|---------|
| `dGen.iDoc` | `<Header><DocType>` |
| `dGen.dFechaEm` | `<IssuedDateTime>` |
| `test ? 1 : 2` | `<AdditionalIssueType>` |
| `dGen.iTpEmis` | `<Info Name="TipoEmision" Value=""/>` |
| `dGen.dNroDF` | `<Info Name="NumeroDF" Value=""/>` |
| `dGen.dPtoFacDF` | `<Info Name="PtoFactDF" Value=""/>` |
| `dGen.dSeg` | `<Info Name="CodigoSeguridad" Value=""/>` |
| `dGen.iNatOp` | `<Info Name="NaturalezaOperacion" Value=""/>` |
| `dGen.iTipoOp` | `<Info Name="TipoOperacion" Value=""/>` |
| `dGen.iDest` | `<Info Name="DestinoOperacion" Value=""/>` |
| `dGen.iFormCAFE` | `<Info Name="FormatoOperacion" Value=""/>` |
| `dGen.iEntCAFE` | `<Info Name="ManeraEntrega" Value=""/>` |
| `dGen.dEnvFE` | `<Info Name="EnvioContenedor" Value=""/>` |
| `dGen.iProGen` | `<Info Name="ProcesoGeneracion" Value=""/>` |

### Seller (Emisor)

| DGI | NUC XML |
|-----|---------|
| `dGen.gEmis.gRucEmi.dRuc` | `<Seller><TaxID>` |
| `dGen.gEmis.gRucEmi.dTipoRuc` | `<TaxIDType>` |
| `dGen.gEmis.gRucEmi.dDV` | `<TaxIDAdditionalInfo><Info Name="DigitoVerificador"/>` |
| `dGen.gEmis.dNombEm` | `<Name>` |
| `dGen.gEmis.dTfnEm` | `<Contact><PhoneList><Phone>` |
| `dGen.gEmis.dCorElectEmi` | `<EmailList><Email>` |
| `dGen.gEmis.dSucEm` | `<BranchInfo><Code>` |
| `dGen.gEmis.dDirecEm` | `<AddressInfo><Address>` |
| `dGen.gEmis.gUbiEm.dCorreg` | `<City>` |
| `dGen.gEmis.gUbiEm.dDistr` | `<District>` |
| `dGen.gEmis.gUbiEm.dProv` | `<State>` |
| `"PA"` | `<Country>` |

### Buyer (Receptor)

| DGI | NUC XML |
|-----|---------|
| `dGen.gDatRec.gRucRec.dRuc` | `<Buyer><TaxID>` |
| `dGen.gDatRec.gRucRec.dTipoRuc` | `<TaxIDType>` |
| `dGen.gDatRec.iTipoRec` | `<TaxIDAdditionalInfo><Info Name="TipoReceptor"/>` |
| `dGen.gDatRec.dNombRec` | `<Name>` |
| `dGen.gDatRec.dTfnRec` | `<Contact><PhoneList><Phone>` (opcional) |
| `dGen.gDatRec.dCorElectRec` | `<EmailList><Email>` (opcional) |
| `dGen.gDatRec.cPaisRec` | `<AddressInfo><Country>` |

### Items (Productos)

| DGI | NUC XML |
|-----|---------|
| `gItem[].dDescProd` | `<Item><Description>` |
| `gItem[].dCantCodInt` | `<Qty>` (formato: x.xxxx) |
| `gItem[].cUnidad` | `<UnitOfMeasure>` |
| `gItem[].gPrecios.dPrUnit` | `<Price>` (formato: x.xxxx) |
| `gItem[].gPrecios.dPrUnitDesc` | `<Discounts><Discount><Amount>` |
| `gItem[].gITBMSItem.dTasaITBMS` | `<Taxes><Tax><Code>` (con zero fill a 2 dígitos) |
| `"ITBMS"` | `<Description>` |
| `dTasaITBMS / 100` | `<Rate>` (formato: 0.xxxx) |
| `gItem[].gITBMSItem.dValITBMS` | `<Amount>` |
| `dPrUnit * dCantCodInt` | `<Totals><TotalBDiscount>` |
| `gItem[].gPrecios.dPrItem` | `<TotalWDiscount>` |
| `gItem[].gPrecios.dPrItem` | `<TotalBTaxes>` |
| `gItem[].gPrecios.dValTotItem` | `<TotalWTaxes>` |
| `gItem[].gPrecios.dValTotItem` | `<SpecificTotal>` |
| `gItem[].gPrecios.dValTotItem` | `<TotalItem>` |

### Totals (Totales)

| DGI | NUC XML |
|-----|---------|
| `gItem.length` | `<Totals><QtyItems>` |
| `gTot.dTotDesc` | `<TotalDiscounts><Discount><Amount>` |
| `gTot.dTotNeto` | `<GrandTotal><TotalBDiscounts>` |
| `gTot.dVTotItems` | `<TotalWDiscounts>` |
| `gTot.dVTot` | `<InvoiceTotal>` |

### Payments (Formas de Pago)

| DGI | NUC XML |
|-----|---------|
| `gTot.gFormaPago[].iFormaPago` | `<Payment><Type>` |
| `gTot.gFormaPago[].dFormaPagoDesc` | `<Description>` (opcional) |
| `gTot.gFormaPago[].dVlrCuota` | `<Amount>` |

---

## 5. Recomendación de Implementación

### Opción 1: XML Transformer (Recomendada - Documentada)
✅ **Ventajas**:
- Formato **oficialmente documentado**
- Endpoint **estable y probado**
- No requiere autenticación Bearer (solo query params)

❌ **Desventajas**:
- Requiere construcción completa de XML
- Mayor código de transformación

**Implementación**:
```php
class DigifactXmlBuilder {
    public function buildNucXml(array $dgiData, bool $isTest = true): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Root></Root>');
        
        // Version y Country
        $xml->addChild('Version', '1.00');
        $xml->addChild('CountryCode', 'PA');
        
        // Header
        $header = $xml->addChild('Header');
        $header->addChild('DocType', str_pad($dgiData['dGen']['iDoc'], 2, '0', STR_PAD_LEFT));
        $header->addChild('IssuedDateTime', $dgiData['dGen']['dFechaEm']);
        $header->addChild('AdditionalIssueType', $isTest ? '1' : '2');
        
        // AdditionalIssueDocInfo con Info elements
        $additionalInfo = $header->addChild('AdditionalIssueDocInfo');
        $this->addInfoElement($additionalInfo, 'TipoEmision', $dgiData['dGen']['iTpEmis']);
        $this->addInfoElement($additionalInfo, 'NumeroDF', str_pad($dgiData['dGen']['dNroDF'], 10, '0', STR_PAD_LEFT));
        // ... más campos
        
        // Seller, Buyer, Items, etc.
        
        return $xml->asXML();
    }
    
    private function addInfoElement($parent, $name, $value) {
        if (!empty($value)) {
            $info = $parent->addChild('Info');
            $info->addAttribute('Name', $name);
            $info->addAttribute('Value', $value);
        }
    }
}
```

### Opción 2: JSON Endpoint (No Recomendada - Sin Documentar)
⚠️ **Advertencias**:
- Endpoint **no documentado oficialmente**
- Formato de entrada **desconocido**
- Requiere autenticación Bearer Token
- Puede cambiar sin aviso

**NO implementar** hasta confirmar con soporte de Digifact:
- Formato JSON que acepta (¿DGI nativo o JSON NUC?)
- Estabilidad del endpoint
- Documentación oficial

---

## 6. Próximos Pasos

### Implementar XML Transformer
1. ✅ Crear `DigifactXmlBuilder` en `app/Services/`
2. ✅ Mapear todos los campos DGI → NUC XML
3. ✅ Implementar helpers para:
   - Zero padding (NumeroDF, DocType, etc.)
   - Formato de decimales (4 y 6 decimales)
   - Elementos `<Info Name="" Value=""/>`
4. ⏳ Actualizar `DigifactService->certifyDocument()` para usar XML
5. ⏳ Probar certificación con endpoint `/transform/nuc`

### Investigar JSON Endpoint (Opcional)
1. ⏳ Contactar soporte Digifact (soporte@digifact.com.gt)
2. ⏳ Preguntar por documentación de `/transform/nuc_json`
3. ⏳ Confirmar formato JSON aceptado
4. ⏳ Evaluar implementación según respuesta

---

## 7. Código JavaScript Analizado

El código `configureFormatterDigifact` que compartiste es **correcto** para el formato XML NUC:
- Construye XML jerárquico con nombres en inglés
- Usa elementos `<Info Name="" Value=""/>`
- Transforma completamente la estructura DGI

**Necesitas** replicar esta lógica en PHP con un XML builder similar.

---

## Conclusión

**Digifact usa formato XML NUC**, NO JSON DGI directo. Debes implementar un transformer completo similar al código JavaScript que compartiste, pero en PHP. El endpoint `/transform/nuc_json` existe pero no está documentado y no sabemos qué formato acepta exactamente.

**Ruta recomendada**: Implementar XML transformer según documentación oficial.
