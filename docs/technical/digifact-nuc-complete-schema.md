# Schema Completo NUC Digifact Panamá - Documentación Oficial

## Fuente
**Documento**: Documentacion Tecnica NUC Panama.pdf (Version 1.01)  
**Fecha de Extracción**: 30 de diciembre de 2025  
**Endpoint**: https://pactest.digifact.com.pa/pa.com.apinuc/api/transform/nuc

## Formato: XML NUC (Non-Uniform Commerce)

**IMPORTANTE**: Digifact usa un formato XML propietario llamado **NUC**, NO el formato JSON DGI estándar. El objeto debe ser transformado completamente.

---

## Estructura General del NUC

```xml
<Root>
  <Version>1.00</Version>
  <CountryCode>PA</CountryCode>
  <Header>...</Header>
  <Seller>...</Seller>
  <Buyer>...</Buyer>
  <ThirdParty>...</ThirdParty> <!-- Opcional -->
  <Items>...</Items>
  <Totals>...</Totals>
  <Payments>...</Payments>
  <AdditionalDocumentInfo>...</AdditionalDocumentInfo>
</Root>
```

---

## Tabla Completa de Campos

### Convenciones
- **C**: Conjunto de campos (R=Root, A=Header, B=Seller, C=Buyer, etc.)
- **ID**: Identificador único del campo
- **P**: Padre (ID del campo contenedor)
- **T**: Tipo de dato (G=Grupo, A=Alfanumérico, N=Numérico, F=Fecha, I=Info)
- **Tam**: Tamaño (x=exacto, x-y=rango, xpn=decimales)
- **Ocu**: Ocurrencias (1-1=obligatorio, 0-1=opcional, 1-n=lista obligatoria)

### Tipos de Datos
- **G**: Grupo de elementos
- **A**: Alfanumérico
- **N**: Numérico (ver formatos: 1-11p2-4 = 1-11 enteros, 2-4 decimales)
- **F**: Fecha en formato UTC (AAAA-MM-DDThh:mm:ssTZH)
- **I**: Elemento Info con atributos Name, Data, Value
- **C**: Coordenada geográfica

---

## R - Root (Raíz del NUC)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| R | R01 | Root | Raíz | G | - | 1-1 | Elemento raíz del XML |
| R | R02 | Version | R01 | A | 1-12 | 1-1 | **Valor fijo**: "1.00" |
| R | R03 | CountryCode | R01 | A | 2 | 1-1 | **PA** (Panamá), GT (Guatemala) |

---

## A - Header (Información General)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| A | A01 | Header | R01 | G | - | 1-1 | Información general de la FE |
| A | A02 | DocType | A01 | A | 2 | 1-1 | **01**: Factura Interna<br>**02**: Factura Importación<br>**03**: Factura Exportación<br>**04**: NC Referente a FE<br>**05**: ND Referente a FE<br>**06**: NC Genérica<br>**07**: ND Genérica<br>**08**: Factura Zona Franca<br>**09**: Reembolso |
| A | A03 | IssuedDateTime | A01 | F | 25 | 1-1 | Formato: AAAA-MM-DDThh:mm:ssTZH<br>Ejemplo: 2017-04-17T14:23:00-05:00 |
| A | A04 | AdditionalIssueType | A01 | N | 1 | 1-1 | **1**: Pruebas<br>**2**: Producción |
| A | A05 | ExchangeRate | A01 | N | 1-11p2-4 | 0-1 | Tipo de cambio (obligatorio si A06 existe y es exportación) |
| A | A06 | Currency | A01 | A | 3 | 0-1 | Código ISO 4217 (USD, EUR, etc.)<br>Solo si es diferente de USD |
| A | A07 | AdditionalIssueDocInfo | A01 | G | - | 1-1 | Grupo de información adicional |
| A | A071 | Info | A07 | I | - | 1-25 | Elemento Info con Name/Value |

### AI - AdditionalIssueDocInfo (Información Adicional del Header)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| AI01 | TipoEmision | A 2 | 1-1 | **01**: Normal<br>**02**: Contingencia Previa<br>**03**: Posterior Normal<br>**04**: Posterior Contingencia |
| AI02 | FechaContingencia | F 25 | 0-1 | Obligatorio si AI01=03 o 04 |
| AI03 | MotivoContingencia | A 15-250 | 0-1 | Obligatorio si AI01=03 o 04 |
| AI04 | NumeroDF | A 10 | 1-1 | Número de factura (0000000001-9999999999)<br>Llenar con ceros a la izquierda |
| AI05 | PtoFactDF | A 3 | 1-1 | Punto de facturación (001-999)<br>No admite "000" |
| AI06 | CodigoSeguridad | A 9 | 1-1 | Número aleatorio (000000001-999999999)<br>No puede ser "000000000" ni igual a AI04 |
| AI07 | FechaSalida | F 25 | 0-1 | Fecha de salida de mercancías |
| AI08 | NaturalezaOperacion | N 2 | 1-1 | **01**: Venta<br>**02**: Exportación<br>**10**: Transferencia<br>**11**: Devolución<br>**12**: Consignación<br>**13**: Remesa<br>**14**: Entrega gratuita<br>**20**: Compra<br>**21**: Importación |
| AI09 | TipoOperacion | N 1 | 1-1 | **1**: Salida/Venta<br>**2**: Entrada/Compra |
| AI10 | DestinoOperacion | N 1 | 1-1 | **1**: Panamá<br>**2**: Extranjero |
| AI11 | FormatoOperacion | N 1 | 1-1 | **1**: Sin CAFE<br>**2**: Cinta papel<br>**3**: Papel carta |
| AI12 | ManeraEntrega | N 1 | 1-1 | **1**: Sin CAFE<br>**2**: CAFE en papel<br>**3**: CAFE electrónico |
| AI13 | EnvioContenedor | N 1 | 1-1 | **1**: Sin envío<br>**2**: Envío físico<br>**3**: Envío electrónico |
| AI14 | ProcesoGeneracion | N 1 | 1-1 | **1**: Manual<br>**2**: POS<br>**3**: Sistema |
| AI15 | TipoTransaccion | N 2 | 0-1 | **01**: Bienes<br>**02**: Servicios<br>**03**: Mixto |
| AI16 | TipoSucursal | N 2 | 0-1 | **00**: Casa matriz<br>**01**: Sucursal |

---

## B - Seller (Emisor)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| B | B01 | Seller | R01 | G | - | 1-1 | Información del emisor |
| B | B02 | TaxID | B01 | A | 20 | 1-1 | RUC del emisor |
| B | B03 | TaxIDType | B01 | N | 1 | 1-1 | **1**: Natural<br>**2**: Jurídico |
| B | B04 | TaxIDAdditionalInfo | B01 | G | - | 1-1 | Información tributaria adicional |
| B | B041 | Info | B04 | I | - | 1-25 | Elementos Info |
| B | B05 | Name | B01 | A | 2-100 | 1-1 | Razón social o nombre |
| B | B06 | Contact | B01 | G | - | 1-1 | Información de contacto |
| B | B061 | PhoneList | B06 | G | - | 1-1 | Lista de teléfonos |
| B | B0611 | Phone | B061 | A | 1-20 | 1-25 | Número telefónico |
| B | B062 | EmailList | B06 | G | - | 1-1 | Lista de correos |
| B | B0621 | Email | B062 | A | 6-100 | 1-25 | Correo electrónico |
| B | B07 | BranchInfo | B01 | G | - | 1-1 | Información de sucursal |
| B | B071 | Code | B07 | A | 4 | 1-1 | Código de sucursal (0000=matriz) |
| B | B072 | AddressInfo | B07 | G | - | 1-1 | Información de dirección |
| B | B0721 | Address | B072 | A | 1-100 | 1-1 | Dirección física |
| B | B0722 | City | B072 | A | 1-50 | 1-1 | Ciudad/Corregimiento |
| B | B0723 | District | B072 | A | 1-50 | 1-1 | Distrito |
| B | B0724 | State | B072 | A | 1-50 | 1-1 | Provincia |
| B | B0725 | Country | B072 | A | 2 | 1-1 | Código ISO de país (PA) |
| B | B073 | AdditionalBranchInfo | B07 | G | - | 0-1 | Información adicional de sucursal |
| B | B0731 | Info | B073 | I | - | 1-25 | Elementos Info |

### BI - TaxIDAdditionalInfo (Seller)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| BI01 | DigitoVerificador | A 3 | 1-1 | Dígito verificador del RUC (incluye guion "-") |

### BI - AdditionalBranchInfo (Seller)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| BI02 | CoordEm | C - | 0-1 | Coordenadas geográficas (Lat,Lon)<br>Formato: +DD.DDDD,-DDD.DDDD |
| BI03 | CodUbi | A 6 | 0-1 | Código de ubicación geográfica |

---

## C - Buyer (Receptor/Comprador)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| C | C01 | Buyer | R01 | G | - | 1-1 | Información del receptor |
| C | C02 | TaxID | C01 | A | 20 | 1-1 | RUC del receptor |
| C | C03 | TaxIDType | C01 | N | 1 | 1-1 | **1**: Natural<br>**2**: Jurídico<br>**3**: Extranjero |
| C | C04 | TaxIDAdditionalInfo | C01 | G | - | 1-1 | Información tributaria adicional |
| C | C041 | Info | C04 | I | - | 1-25 | Elementos Info |
| C | C05 | Name | C01 | A | 2-100 | 1-1 | Razón social o nombre |
| C | C06 | Contact | C01 | G | - | 0-1 | Información de contacto |
| C | C061 | PhoneList | C06 | G | - | 1-1 | Lista de teléfonos |
| C | C0611 | Phone | C061 | A | 1-20 | 1-25 | Número telefónico |
| C | C062 | EmailList | C06 | G | - | 1-1 | Lista de correos |
| C | C0621 | Email | C062 | A | 6-100 | 1-25 | Correo electrónico |
| C | C07 | AdditionlInfo | C01 | G | - | 0-1 | Información adicional |
| C | C071 | Info | C07 | I | - | 1-25 | Elementos Info |
| C | C08 | AddressInfo | C01 | G | - | 1-1 | Información de dirección |
| C | C081 | Address | C08 | A | 1-100 | 0-1 | Dirección física |
| C | C082 | City | C08 | A | 1-50 | 0-1 | Ciudad/Corregimiento |
| C | C083 | District | C08 | A | 1-50 | 0-1 | Distrito |
| C | C084 | State | C08 | A | 1-50 | 0-1 | Provincia |
| C | C085 | Country | C08 | A | 2 | 1-1 | Código ISO de país |

### CI - TaxIDAdditionalInfo (Buyer)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| CI01 | TipoReceptor | N 1 | 1-1 | **1**: Contribuyente<br>**2**: Consumidor Final<br>**3**: Extranjero |
| CI02 | DigitoVerificador | A 3 | 0-1 | Dígito verificador del RUC |
| CI03 | CodUbi | A 6 | 0-1 | Código de ubicación geográfica |
| CI04 | CedulaCF | A 20 | 0-1 | Cédula si es consumidor final (iTipoRec=2) |
| CI05 | NumIdTE | A 1-20 | 0-1 | Número ID tarjeta de extranjería |
| CI06 | NumPasaporte | A 1-20 | 0-1 | Número de pasaporte |
| CI07 | PaisExt | A 2 | 0-1 | País del extranjero (código ISO) |

### CI - AdditionlInfo (Buyer)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| CI08 | PaisReceptorFE | A 2 | 0-1 | País del receptor para FE |

---

## D - ThirdParty (Terceros) - OPCIONAL

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| D | D01 | ThirdParty | R01 | G | - | 0-25 | Información de terceros (transportistas, etc.) |
| D | D02 | TaxID | D01 | A | 20 | 1-1 | RUC del tercero |
| D | D03 | TaxIDType | D01 | N | 1 | 1-1 | Tipo de contribuyente |
| D | D04 | TaxIDAdditionalInfo | D01 | G | - | 0-1 | Información adicional |
| D | D041 | Info | D04 | I | - | 1-25 | Elementos Info |
| D | D05 | Name | D01 | A | 2-100 | 1-1 | Razón social o nombre |

### DI - TaxIDAdditionalInfo (ThirdParty)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| DI01 | TipoTercero | N 1 | 1-1 | **1**: Transportista<br>**2**: Agente aduanal<br>**3**: Otro |
| DI02 | DigitoVerificador | A 3 | 0-1 | Dígito verificador del RUC |

---

## E - Items (Productos/Servicios)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| E | E01 | Items | R01 | G | - | 1-1 | Lista de items |
| E | E02 | Item | E01 | G | - | 1-9999 | Item individual |
| E | E03 | Codes | E02 | G | - | 0-1 | Códigos del producto |
| E | E031 | Code | E03 | I | - | 1-25 | Código con Name/Value |
| E | E04 | Description | E02 | A | 1-500 | 1-1 | Descripción del producto |
| E | E05 | Qty | E02 | N | 1-11p4 | 1-1 | Cantidad (con 4 decimales) |
| E | E06 | UnitOfMeasure | E02 | A | 1-3 | 1-1 | Unidad de medida (UND, KG, etc.) |
| E | E07 | Price | E02 | N | 1-11p0-6 | 1-1 | Precio unitario |
| E | E08 | Discounts | E02 | G | - | 0-1 | Grupo de descuentos |
| E | E081 | Discount | E08 | G | - | 1-25 | Descuento individual |
| E | E0811 | Description | E081 | A | 1-100 | 0-1 | Descripción del descuento |
| E | E0812 | Amount | E081 | N | 1-11p0-6 | 1-1 | Monto del descuento |
| E | E09 | Taxes | E02 | G | - | 1-1 | Grupo de impuestos |
| E | E091 | Tax | E09 | G | - | 1-25 | Impuesto individual |
| E | E0911 | Code | E091 | A | 2 | 1-1 | Código del impuesto (ITBMS) |
| E | E0912 | Description | E091 | A | 1-100 | 1-1 | Descripción ("ITBMS") |
| E | E0913 | Rate | E091 | N | 1p4 | 1-1 | Tasa del impuesto (0.0700 = 7%) |
| E | E0914 | Amount | E091 | N | 1-11p0-6 | 1-1 | Monto del impuesto |
| E | E10 | Totals | E02 | G | - | 1-1 | Totales del item |
| E | E101 | TotalBDiscount | E10 | N | 1-11p0-6 | 1-1 | Total antes de descuento |
| E | E102 | TotalWDiscount | E10 | N | 1-11p0-6 | 1-1 | Total con descuento |
| E | E103 | TotalBTaxes | E10 | N | 1-11p0-6 | 1-1 | Total antes de impuestos |
| E | E104 | TotalWTaxes | E10 | N | 1-11p0-6 | 1-1 | Total con impuestos |
| E | E105 | SpecificTotal | E10 | N | 1-11p0-6 | 1-1 | Total específico |
| E | E106 | TotalItem | E10 | N | 1-11p0-6 | 1-1 | Total del item |
| E | E11 | AdditionalInfo | E02 | G | - | 0-1 | Información adicional del item |
| E | E111 | Info | E11 | I | - | 1-25 | Elementos Info |

### EI - Codes (Item)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| EI01 | CodigoProd | A 1-50 | 0-1 | Código interno del producto |
| EI02 | CodCPBSabr | A 10 | 0-1 | Código CPBS abreviado |
| EI03 | CodCPBScmp | A 10 | 0-1 | Código CPBS completo |
| EI04 | UnidadCPBS | A 1-3 | 0-1 | Unidad CPBS |
| EI05 | CodGTIN | A 14 | 0-1 | Código GTIN/EAN |

### EI - AdditionalInfo (Item)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| EI06 | NumeroSerie | A 1-100 | 0-1 | Número de serie |
| EI07 | NumeroLote | A 1-100 | 0-1 | Número de lote |
| EI08 | FechaVencimiento | F 25 | 0-1 | Fecha de vencimiento |
| EI09 | PesoNeto | N 1-11p0-6 | 0-1 | Peso neto |
| EI10 | PesoBruto | N 1-11p0-6 | 0-1 | Peso bruto |

---

## F - Totals (Totales del Documento)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| F | F01 | Totals | R01 | G | - | 1-1 | Totales del documento |
| F | F02 | QtyItems | F01 | N | 1-5 | 1-1 | Cantidad de items (líneas) |
| F | F03 | TotalDiscounts | F01 | G | - | 0-1 | Grupo de descuentos totales |
| F | F031 | Discount | F03 | G | - | 1-25 | Descuento individual |
| F | F0311 | Description | F031 | A | 1-100 | 0-1 | Descripción del descuento |
| F | F0312 | Amount | F031 | N | 1-11p0-6 | 1-1 | Monto del descuento |
| F | F04 | TotalTaxes | F01 | G | - | 0-1 | Grupo de impuestos totales |
| F | F041 | Tax | F04 | G | - | 1-25 | Impuesto individual |
| F | F0411 | Code | F041 | A | 2 | 1-1 | Código del impuesto |
| F | F0412 | Description | F041 | A | 1-100 | 1-1 | Descripción del impuesto |
| F | F0413 | TaxableAmount | F041 | N | 1-11p0-6 | 1-1 | Base imponible |
| F | F0414 | Rate | F041 | N | 1p4 | 1-1 | Tasa del impuesto |
| F | F0415 | Amount | F041 | N | 1-11p0-6 | 1-1 | Monto del impuesto |
| F | F05 | GrandTotal | F01 | G | - | 1-1 | Gran total |
| F | F051 | TotalBDiscounts | F05 | N | 1-11p0-6 | 1-1 | Total antes de descuentos |
| F | F052 | TotalWDiscounts | F05 | N | 1-11p0-6 | 1-1 | Total con descuentos |
| F | F053 | InvoiceTotal | F05 | N | 1-11p0-6 | 1-1 | Total de la factura |

---

## G - Payments (Formas de Pago)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| G | G01 | Payments | R01 | G | - | 1-1 | Formas de pago |
| G | G02 | Payment | G01 | G | - | 1-25 | Forma de pago individual |
| G | G03 | Type | G02 | A | 1-2 | 1-1 | **1**: Efectivo<br>**2**: Cheque<br>**3**: Tarjeta débito<br>**4**: Tarjeta crédito<br>**5**: Transferencia<br>**6**: Depósito<br>**7**: Crédito<br>**8**: Otro |
| G | G04 | Description | G02 | A | 1-100 | 0-1 | Descripción del pago |
| G | G05 | Amount | G02 | N | 1-11p0-6 | 1-1 | Monto del pago |

---

## H - AdditionalDocumentInfo (Información Adicional del Documento)

| C | ID | Campo | P | T | Tam | Ocu | Observaciones |
|---|----|----|---|---|-----|-----|---------------|
| H | H01 | AdditionalDocumentInfo | R01 | G | - | 0-1 | Información adicional |
| H | H02 | AdditionalInfo | H01 | G | - | 1-1 | Información adicional |
| H | H03 | Data | H02 | G | - | 0-1 | Datos adicionales |
| H | H031 | DataEl | H03 | A | 0-3900 | 1-25 | Elemento de datos |
| H | H04 | AddressInfo | H02 | G | - | 0-1 | Información de dirección adicional |
| H | H041 | Address | H04 | A | 1-100 | 0-1 | Dirección |
| H | H042 | City | H04 | A | 1-50 | 0-1 | Ciudad |
| H | H043 | District | H04 | A | 1-50 | 0-1 | Distrito |
| H | H044 | State | H04 | A | 1-50 | 0-1 | Provincia |
| H | H045 | Country | H04 | A | 2 | 0-1 | País |
| H | H05 | AditionalInfo | H02 | G | - | 0-1 | Información adicional variada |
| H | H051 | Info | H05 | I | - | 1-25 | Elementos Info |

### HI - AditionalInfo (Información Adicional Variada)

| Informacion | Name | Tam | Ocu | Observaciones |
|-------------|------|-----|-----|---------------|
| HI01 | TiempoPago | N 1-3 | 0-1 | Tiempo de pago en días |
| HI02 | OrdenCompra | A 1-50 | 0-1 | Número de orden de compra |
| HI03 | CodigoProyecto | A 1-50 | 0-1 | Código del proyecto |
| HI04 | Observaciones | A 1-500 | 0-1 | Observaciones adicionales |

---

## Códigos de Impuestos (ITBMS)

| Código | Descripción | Tasa |
|--------|-------------|------|
| 00 | Exento | 0% |
| 01 | ITBMS | 7% |
| 02 | ITBMS | 10% |
| 03 | ITBMS | 15% |

---

## Respuesta de la API

### Estructura de Respuesta Exitosa

```xml
<Root>
  <codigo>200</codigo>
  <mensaje>Documento certificado correctamente</mensaje>
  <CUFE>0110...</CUFE>
  <pdf_base64>JVBERi0xLjQK...</pdf_base64>
  <xml_base64>PD94bWwgdmVyc2...</xml_base64>
  <html_base64>PCFET0NUWVBFIGh0bWw+...</html_base64>
</Root>
```

### Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 200 | Éxito |
| 400 | Error en validación de datos |
| 401 | No autorizado |
| 500 | Error interno del servidor |

---

## Ejemplo Completo de NUC

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <Version>1.00</Version>
  <CountryCode>PA</CountryCode>
  
  <Header>
    <DocType>01</DocType>
    <IssuedDateTime>2025-12-30T14:30:00-05:00</IssuedDateTime>
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
      <Info Name="TipoTransaccion" Value="01"/>
    </AdditionalIssueDocInfo>
  </Header>
  
  <Seller>
    <TaxID>155704849-2-2021</TaxID>
    <TaxIDType>2</TaxIDType>
    <TaxIDAdditionalInfo>
      <Info Name="DigitoVerificador" Value="-28"/>
    </TaxIDAdditionalInfo>
    <Name>EMPRESA DE PRUEBA S.A.</Name>
    <Contact>
      <PhoneList>
        <Phone>507-1234-5678</Phone>
      </PhoneList>
      <EmailList>
        <Email>facturacion@empresa.com</Email>
      </EmailList>
    </Contact>
    <BranchInfo>
      <Code>0000</Code>
      <AddressInfo>
        <Address>Calle 50, Edificio XYZ</Address>
        <City>San Francisco</City>
        <District>Panamá</District>
        <State>Panamá</State>
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
    <Name>JUAN PEREZ</Name>
    <AddressInfo>
      <Country>PA</Country>
    </AddressInfo>
  </Buyer>
  
  <Items>
    <Item>
      <Description>Producto de Prueba</Description>
      <Qty>1.0000</Qty>
      <UnitOfMeasure>UND</UnitOfMeasure>
      <Price>100.00</Price>
      <Discounts>
        <Discount>
          <Amount>0.00</Amount>
        </Discount>
      </Discounts>
      <Taxes>
        <Tax>
          <Code>01</Code>
          <Description>ITBMS</Description>
          <Rate>0.0700</Rate>
          <Amount>7.00</Amount>
        </Tax>
      </Taxes>
      <Totals>
        <TotalBDiscount>100.00</TotalBDiscount>
        <TotalWDiscount>100.00</TotalWDiscount>
        <TotalBTaxes>100.00</TotalBTaxes>
        <TotalWTaxes>107.00</TotalWTaxes>
        <SpecificTotal>107.00</SpecificTotal>
        <TotalItem>107.00</TotalItem>
      </Totals>
    </Item>
  </Items>
  
  <Totals>
    <QtyItems>1</QtyItems>
    <TotalDiscounts>
      <Discount>
        <Amount>0.00</Amount>
      </Discount>
    </TotalDiscounts>
    <GrandTotal>
      <TotalBDiscounts>100.00</TotalBDiscounts>
      <TotalWDiscounts>100.00</TotalWDiscounts>
      <InvoiceTotal>107.00</InvoiceTotal>
    </GrandTotal>
  </Totals>
  
  <Payments>
    <Payment>
      <Type>1</Type>
      <Description>Efectivo</Description>
      <Amount>107.00</Amount>
    </Payment>
  </Payments>
  
</Root>
```

---

## Notas Importantes para Implementación

### 1. Case Sensitive
La API es **case sensitive**. Los nombres de campos deben respetar mayúsculas/minúsculas exactamente como se documentan.

### 2. Formato de Números
- Todos los decimales deben usar punto (.) no coma (,)
- Cantidades: 4 decimales (1.0000)
- Precios: hasta 6 decimales (100.000000)
- Tasas: 4 decimales (0.0700)

### 3. Formato de Fechas
```
AAAA-MM-DDThh:mm:ssTZH
2025-12-30T14:30:00-05:00
```

### 4. RUC Format
- Completar a 20 caracteres con ceros a la izquierda
- Ejemplo: 8-NT-000-00 → 0000000008-NT-000-00

### 5. Códigos con Ceros
- NumeroDF: 10 dígitos con ceros a la izquierda
- PtoFactDF: 3 dígitos, no puede ser "000"
- CodigoSeguridad: 9 dígitos aleatorios

### 6. Elemento Info
```xml
<Info Name="NombreCampo" Value="Valor"/>
<Info Name="NaturalezaOperacion" Value="01"/>
```

### 7. CUFE
El API genera el CUFE automáticamente. NO incluir en el NUC.

---

## Diferencias con Formato DGI

| Característica | Formato DGI (JSON) | Formato NUC (XML) |
|----------------|-------------------|-------------------|
| Estructura | JSON con objetos | XML jerárquico |
| Nombres | Español (dGen, gEmis) | Inglés (Header, Seller) |
| Info Adicional | Campos directos | Elementos <Info Name=""/> |
| Jerarquía | Anidada con prefijos g | Grupos XML explícitos |
| Envío | Directo (compatible) | Requiere transformación |

**CONCLUSIÓN**: El formato NUC requiere una transformación completa del objeto DGI de DocuCenter, similar a lo que hace `configureFormatterDigifact` en JavaScript.

---

## Referencias

- **Endpoint Test**: https://pactest.digifact.com.pa/pa.com.apinuc/api/transform/nuc
- **Endpoint Prod**: https://api.digifact.com.pa/pa.com.apinuc/api/transform/nuc
- **Soporte**: soporte@digifact.com.gt
- **Teléfono**: 2319-1921, opción 2
- **Credenciales Test**: 
  - RUC: 155704849-2-2021
  - Usuario: PRUEBAS193
  - Password: Digifact*25
