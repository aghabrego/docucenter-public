# Digifact NUC - Tipos de Documentos Soportados

## Fecha de Análisis
30 de diciembre de 2025

## Fuente
Documentacion Tecnica NUC Panama.pdf - Version 1.01

---

## Tipos de Documentos (Campo DocType)

| Código | Tipo de Documento | Descripción |
|--------|------------------|-------------|
| **01** | Factura de Operación Interna | Factura estándar para operaciones dentro de Panamá |
| **02** | Factura de Importación | Factura por mercancías importadas |
| **03** | Factura de Exportación | Factura por mercancías exportadas |
| **04** | Nota de Crédito Referente a FE | NC que referencia una o varias facturas electrónicas |
| **05** | Nota de Débito Referente a FE | ND que referencia una o varias facturas electrónicas |
| **06** | Nota de Crédito Genérica | NC sin referencia a factura específica |
| **07** | Nota de Débito Genérica | ND sin referencia a factura específica |
| **08** | Factura de Zona Franca | Factura para operaciones en zona franca |
| **09** | Reembolso | Documento de reembolso |

---

## Estructura Base Común

Todos los documentos comparten esta estructura base:

```xml
<Root>
  <Version>1.00</Version>
  <CountryCode>PA</CountryCode>
  <Header>
    <DocType>XX</DocType>
    <IssuedDateTime>...</IssuedDateTime>
    <AdditionalIssueType>...</AdditionalIssueType>
    <AdditionalIssueDocInfo>...</AdditionalIssueDocInfo>
  </Header>
  <Seller>...</Seller>
  <Buyer>...</Buyer>
  <Items>...</Items>
  <Totals>...</Totals>
  <Payments>...</Payments>
  <AdditionalDocumentInfo>...</AdditionalDocumentInfo>
</Root>
```

---

## Características Específicas por Tipo

### 01 - Factura de Operación Interna

**Naturaleza Operación Típica**: 01 (Venta)  
**Destino Operación**: 1 (Panamá)  
**Campos Requeridos**:
- Header completo
- Seller (Emisor)
- Buyer (Receptor)
- Items (Productos/Servicios)
- Totals (Totales con ITBMS)
- Payments (Formas de pago)

**Campos Opcionales**:
- ThirdParty (Terceros como transportistas)
- AdditionalDocumentInfo.Data (Información adicional)

---

### 02 - Factura de Importación

**Naturaleza Operación Típica**: 21 (Importación)  
**Destino Operación**: 2 (Extranjero)  
**Campos Adicionales**:
- Currency (Moneda extranjera si aplica)
- ExchangeRate (Tipo de cambio obligatorio)

**Consideraciones**:
- Puede incluir información de agente aduanal en ThirdParty
- Impuestos adicionales de importación en Items.Taxes

---

### 03 - Factura de Exportación

**Naturaleza Operación Típica**: 02 (Exportación)  
**Destino Operación**: 2 (Extranjero)  
**Campos Requeridos Adicionales**:
- Currency (Moneda de la operación)
- ExchangeRate (Tipo de cambio)
- Buyer.AddressInfo.Country (País destino)

**Campos Opcionales Adicionales**:
- Header.AdditionalIssueDocInfo → FechaSalida (Fecha salida mercancías)
- ThirdParty (Agente de carga, transportista)

**Consideraciones**:
- ITBMS puede ser 0% (exento para exportación)
- Información de INCOTERMS en AdditionalDocumentInfo

---

### 04 - Nota de Crédito Referente a FE

**Uso**: Ajuste negativo sobre una o varias facturas electrónicas previamente emitidas

**Campos Obligatorios Adicionales**:
- AdditionalDocumentInfo.Data (Documentos referenciados)

**Estructura de Referencia**:

```xml
<AdditionalDocumentInfo>
  <AdditionalInfo>
    <Data>
      <!-- Opción 1: Referencia por CUFE -->
      <DataEl Name="NombEmRef" Value="NOMBRE EMISOR ORIGINAL"/>
      <DataEl Name="FechaDFRef" Value="2025-12-15T10:00:00-05:00"/>
      <DataEl Name="CUFERef" Value="0110...64 caracteres..."/>
      
      <!-- Opción 2: Referencia por Número de Factura Papel -->
      <DataEl Name="NombEmRef" Value="NOMBRE EMISOR ORIGINAL"/>
      <DataEl Name="FechaDFRef" Value="2025-12-15T10:00:00-05:00"/>
      <DataEl Name="NroFacPap" Value="0000000123"/>
      
      <!-- Opción 3: Referencia por Factura Impresora Fiscal -->
      <DataEl Name="NombEmRef" Value="NOMBRE EMISOR ORIGINAL"/>
      <DataEl Name="FechaDFRef" Value="2025-12-15T10:00:00-05:00"/>
      <DataEl Name="NroFacIE" Value="IE-0000000123"/>
    </Data>
  </AdditionalInfo>
</AdditionalDocumentInfo>
```

**Campos de Referencia**:

| ID | Campo | Tipo | Tam | Ocu | Descripción |
|----|-------|------|-----|-----|-------------|
| HI01 | NombEmRef | A | 2-100 | 1-1 | Razón social del emisor del documento referenciado |
| HI02 | FechaDFRef | F | 25 | 1-1 | Fecha de emisión del documento referenciado |
| HI03 | CUFERef | A | 66 | 0-1 | CUFE de la FE referenciada (66 posiciones) |
| HI04 | NroFacPap | A | 22 | 0-1 | Número de factura en papel |
| HI05 | NroFacIE | A | 22 | 0-1 | Número de factura impresora fiscal |

**Restricciones**:
- Solo informar **UNA** de las tres opciones de referencia: CUFERef **O** NroFacPap **O** NroFacIE
- Puede referenciar **múltiples documentos** (repetir estructura Data)

**Items**:
- Pueden ser **negativos** (montos con signo negativo)
- O usar descripciones que indiquen devolución/ajuste

---

### 05 - Nota de Débito Referente a FE

**Uso**: Ajuste positivo sobre una o varias facturas electrónicas previamente emitidas

**Campos y Estructura**: Idénticos a Nota de Crédito (04)

**Diferencia con NC**:
- Los montos son **positivos** (incrementos)
- Usualmente por conceptos como:
  - Intereses moratorios
  - Gastos administrativos adicionales
  - Ajustes por incremento en precio

---

### 06 - Nota de Crédito Genérica

**Uso**: Ajuste negativo sin referencia a factura específica

**Características**:
- **NO requiere** AdditionalDocumentInfo.Data con referencias
- Puede incluir información adicional descriptiva
- Items con montos negativos o descripción de ajuste

**Casos de Uso**:
- Descuentos globales
- Bonificaciones
- Ajustes sin factura específica asociada

---

### 07 - Nota de Débito Genérica

**Uso**: Ajuste positivo sin referencia a factura específica

**Características**:
- **NO requiere** AdditionalDocumentInfo.Data con referencias
- Items con montos positivos
- Usualmente por cargos adicionales no facturados previamente

**Casos de Uso**:
- Cargos por servicios adicionales
- Penalidades
- Ajustes diversos

---

### 08 - Factura de Zona Franca

**Uso**: Operaciones dentro de zonas francas panameñas

**Características Especiales**:
- Naturaleza Operación: Variable según operación
- Destino Operación: Puede ser 1 (Panamá) o 2 (Extranjero)
- **Consideraciones fiscales especiales** según zona franca

**Campos Adicionales Recomendados**:
- AdditionalDocumentInfo → Código de zona franca
- Seller.BranchInfo → Ubicación en zona franca

**Impuestos**:
- Pueden tener tratamiento especial de ITBMS
- Verificar regulaciones específicas de cada zona franca

---

### 09 - Reembolso

**Uso**: Documentos de reembolso de gastos

**Características**:
- Emisor solicita reembolso al receptor
- Items describen gastos realizados a nombre de terceros

**Estructura Típica**:
- Seller: Quien solicita el reembolso
- Buyer: Quien debe reembolsar
- Items: Conceptos de gastos con documentación soporte

**Información Adicional**:
- Adjuntar referencias a facturas originales de los gastos
- Incluir documentación soporte en AdditionalDocumentInfo

---

## Campos Info Adicionales Comunes

### Header.AdditionalIssueDocInfo

Todos los tipos de documentos pueden usar estos campos Info:

| Name | Value | Ocu | Descripción |
|------|-------|-----|-------------|
| TipoEmision | 01-04 | 1-1 | **01**: Normal, **02**: Contingencia Previa, **03**: Posterior Normal, **04**: Posterior Contingencia |
| NumeroDF | 0000000001-9999999999 | 1-1 | Número de documento (10 dígitos) |
| PtoFactDF | 001-999 | 1-1 | Punto de facturación (3 dígitos) |
| CodigoSeguridad | 9 dígitos | 1-1 | Código aleatorio de seguridad |
| NaturalezaOperacion | 01-21 | 1-1 | Ver tabla de naturalezas |
| TipoOperacion | 1-2 | 1-1 | **1**: Salida/Venta, **2**: Entrada/Compra |
| DestinoOperacion | 1-2 | 1-1 | **1**: Panamá, **2**: Extranjero |
| FormatoOperacion | 1-3 | 1-1 | Formato CAFE |
| ManeraEntrega | 1-3 | 1-1 | Forma de entrega CAFE |
| EnvioContenedor | 1-3 | 1-1 | Envío de contenedor |
| ProcesoGeneracion | 1-3 | 1-1 | **1**: Manual, **2**: POS, **3**: Sistema |
| TipoTransaccion | 01-03 | 0-1 | **01**: Bienes, **02**: Servicios, **03**: Mixto |
| TipoSucursal | 00-01 | 0-1 | **00**: Casa matriz, **01**: Sucursal |

### Naturalezas de Operación (iNatOp)

| Código | Descripción | Tipos de Doc Aplicables |
|--------|-------------|-------------------------|
| **01** | Venta | 01, 06, 07 |
| **02** | Exportación | 03 |
| **10** | Transferencia | 01 |
| **11** | Devolución | 04, 06 |
| **12** | Consignación | 01 |
| **13** | Remesa | 01 |
| **14** | Entrega gratuita | 01 |
| **20** | Compra | 01 |
| **21** | Importación | 02 |

---

## AdditionalDocumentInfo.AditionalInfo

Campos Info opcionales para todos los documentos:

| Name | Value | Descripción |
|------|-------|-------------|
| TiempoPago | 1-999 | Tiempo de pago en días |
| OrdenCompra | String | Número de orden de compra |
| CodigoProyecto | String | Código del proyecto asociado |
| Observaciones | String | Observaciones adicionales (hasta 500 caracteres) |
| REFERENCIA_INTERNA | String | Identificador de referencia interna |
| FECHA_REFERENCIA_INTERNA | Fecha | Fecha de referencia interna |
| VALIDAR_REFERENCIA | Boolean | Validar referencia interna |

---

## Resumen de Implementación

### Documentos que Requieren Referencias

| DocType | Requiere Referencias | Campo Obligatorio |
|---------|---------------------|-------------------|
| 04 | SÍ | AdditionalDocumentInfo.Data |
| 05 | SÍ | AdditionalDocumentInfo.Data |
| 06 | NO | - |
| 07 | NO | - |

### Documentos con Moneda Extranjera

| DocType | Requiere Currency/ExchangeRate |
|---------|-------------------------------|
| 02 | Recomendado |
| 03 | Obligatorio |
| Otros | Opcional |

### Documentos con Terceros (ThirdParty)

| DocType | Uso Común de ThirdParty |
|---------|------------------------|
| 02 | Agente aduanal |
| 03 | Transportista, agente de carga |
| 01, 08 | Transportista (opcional) |

---

## Validaciones Especiales por Tipo

### Factura Exportación (03)
```php
// Validaciones requeridas
if ($docType === '03') {
    assert(!empty($data['Header']['Currency']), 'Currency requerido para exportación');
    assert(!empty($data['Header']['ExchangeRate']), 'ExchangeRate requerido para exportación');
    assert($data['Header']['AdditionalIssueDocInfo']['DestinoOperacion'] === '2', 'Destino debe ser Extranjero');
}
```

### Notas de Crédito/Débito Referentes (04, 05)
```php
// Validaciones requeridas
if (in_array($docType, ['04', '05'])) {
    assert(
        !empty($data['AdditionalDocumentInfo']['AdditionalInfo']['Data']),
        'Debe incluir documentos referenciados'
    );
    
    // Validar que tenga al menos una referencia
    $hasReference = !empty($cufeRef) || !empty($nroFacPap) || !empty($nroFacIE);
    assert($hasReference, 'Debe incluir CUFERef, NroFacPap o NroFacIE');
}
```

---

## Ejemplo Completo: Nota de Crédito Referente (04)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <Version>1.00</Version>
  <CountryCode>PA</CountryCode>
  
  <Header>
    <DocType>04</DocType>
    <IssuedDateTime>2025-12-30T14:00:00-05:00</IssuedDateTime>
    <AdditionalIssueType>2</AdditionalIssueType>
    <AdditionalIssueDocInfo>
      <Info Name="TipoEmision" Value="01"/>
      <Info Name="NumeroDF" Value="0000000050"/>
      <Info Name="PtoFactDF" Value="001"/>
      <Info Name="CodigoSeguridad" Value="987654321"/>
      <Info Name="NaturalezaOperacion" Value="11"/>
      <Info Name="TipoOperacion" Value="1"/>
      <Info Name="DestinoOperacion" Value="1"/>
      <Info Name="FormatoOperacion" Value="3"/>
      <Info Name="ManeraEntrega" Value="3"/>
      <Info Name="EnvioContenedor" Value="3"/>
      <Info Name="ProcesoGeneracion" Value="3"/>
    </AdditionalIssueDocInfo>
  </Header>
  
  <Seller>...</Seller>
  <Buyer>...</Buyer>
  
  <Items>
    <Item>
      <Description>Devolución Producto X</Description>
      <Qty>-1.0000</Qty>
      <UnitOfMeasure>UND</UnitOfMeasure>
      <Price>-100.0000</Price>
      <Discounts><Discount><Amount>0.0000</Amount></Discount></Discounts>
      <Taxes>
        <Tax>
          <Code>01</Code>
          <Description>ITBMS</Description>
          <Rate>0.0700</Rate>
          <Amount>-7.0000</Amount>
        </Tax>
      </Taxes>
      <Totals>
        <TotalBDiscount>-100.0000</TotalBDiscount>
        <TotalWDiscount>-100.0000</TotalWDiscount>
        <TotalBTaxes>-100.0000</TotalBTaxes>
        <TotalWTaxes>-107.0000</TotalWTaxes>
        <SpecificTotal>-107.0000</SpecificTotal>
        <TotalItem>-107.0000</TotalItem>
      </Totals>
    </Item>
  </Items>
  
  <Totals>
    <QtyItems>1</QtyItems>
    <TotalDiscounts><Discount><Amount>0.0000</Amount></Discount></TotalDiscounts>
    <GrandTotal>
      <TotalBDiscounts>-100.0000</TotalBDiscounts>
      <TotalWDiscounts>-100.0000</TotalWDiscounts>
      <InvoiceTotal>-107.0000</InvoiceTotal>
    </GrandTotal>
  </Totals>
  
  <Payments>
    <Payment>
      <Type>7</Type>
      <Description>Crédito</Description>
      <Amount>-107.0000</Amount>
    </Payment>
  </Payments>
  
  <AdditionalDocumentInfo>
    <AdditionalInfo>
      <Data>
        <DataEl Name="NombEmRef" Value="EMPRESA DE PRUEBA SA"/>
        <DataEl Name="FechaDFRef" Value="2025-12-15T10:00:00-05:00"/>
        <DataEl Name="CUFERef" Value="0110000815570484922021280000201512150000000010010117450183430"/>
      </Data>
      <AditionalInfo>
        <Info Name="Observaciones" Value="Devolución por defecto de fabricación"/>
      </AditionalInfo>
    </AdditionalInfo>
  </AdditionalDocumentInfo>
</Root>
```

---

## Próximos Pasos de Implementación

### 1. Crear DigifactXmlBuilder con Soporte para Todos los Tipos
```php
class DigifactXmlBuilder
{
    public function buildNucXml(array $dgiData, bool $isTest = true): string
    {
        $docType = $dgiData['dGen']['iDoc'];
        
        // Construir estructura base
        $xml = $this->buildBaseStructure($dgiData, $isTest);
        
        // Agregar secciones específicas según tipo
        if (in_array($docType, ['04', '05'])) {
            $this->addReferencedDocuments($xml, $dgiData);
        }
        
        if ($docType === '03') {
            $this->validateExportRequirements($dgiData);
        }
        
        return $xml->asXML();
    }
}
```

### 2. Validaciones por Tipo de Documento
- Implementar validador para cada DocType
- Verificar campos obligatorios según tipo
- Validar montos (positivos/negativos) según tipo

### 3. Testing
- Crear casos de prueba para cada uno de los 9 tipos
- Validar XML generado contra XSD de Digifact
- Probar certificación con endpoint de pruebas

---

## Referencias
- **Documentación**: Documentacion Tecnica NUC Panama.pdf v1.01
- **Endpoint Test**: https://testnucpa.digifact.com/api/transform/nuc
- **Soporte**: soporte@digifact.com.gt
