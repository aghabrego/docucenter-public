# Digifact PAC - Integración Completa XML NUC

## Resumen Ejecutivo

Implementación completa del PAC Digifact usando formato XML NUC (Non-Uniform Commerce) oficial de Panamá. Soporta los 9 tipos de documentos fiscales con validaciones específicas por tipo.

## Arquitectura

### Componentes Principales

```
CreateFastJob.php (Livewire)
    ↓ construye $request (formato DGI)
    ↓
DigifactService.php
    ↓ certifyDocument($request)
    ↓ instancia DigifactXmlBuilder
    ↓
DigifactXmlBuilder.php
    ↓ buildNucXml($request)
    ↓ transforma DGI → XML NUC
    ↓
API Digifact (/transform/nuc)
    ↓ POST con XML
    ↓ responde XML
    ↓
DigifactService.php
    ↓ parsea respuesta XML
    ↓ extrae CUFE, PDF, XML
    ↓
CreateFastJob.php
    ↓ guarda archivos
    ↓ actualiza sale
```

## Formato XML NUC

### Estructura General

```xml
<Root>
    <Version>1.0</Version>
    <CountryCode>PA</CountryCode>
    
    <!-- Encabezado del documento -->
    <Header>
        <DocType>01</DocType>
        <IssuedDateTime>2025-01-10T15:30:00</IssuedDateTime>
        <AdditionalIssueDocInfo>
            <Info Name="TipoEmision" Value="01"/>
            <Info Name="TipoOperacion" Value="1"/>
            <Info Name="SecurityCode" Value="123456789"/>
        </AdditionalIssueDocInfo>
    </Header>
    
    <!-- Emisor -->
    <Seller>
        <TaxID>155704849-2-2021</TaxID>
        <TaxIDAdditionalInfo>
            <Info Name="DV" Value="22"/>
        </TaxIDAdditionalInfo>
        <Contact>
            <Info Name="Nombre" Value="EMPRESA SA"/>
            <Info Name="CorreoElectronico" Value="empresa@example.com"/>
        </Contact>
        <BranchInfo>
            <Info Name="Sucursal" Value="0001"/>
            <Info Name="PuntoFacturacion" Value="001"/>
        </BranchInfo>
    </Seller>
    
    <!-- Receptor -->
    <Buyer>
        <TaxID>8-888-8888</TaxID>
        <TaxIDAdditionalInfo>
            <Info Name="DV" Value="02"/>
        </TaxIDAdditionalInfo>
        <AddressInfo>
            <Info Name="RazonSocial" Value="CLIENTE SA"/>
            <Info Name="Direccion" Value="Panama, Panama"/>
            <Info Name="CorreoElectronico" Value="cliente@example.com"/>
        </AddressInfo>
    </Buyer>
    
    <!-- Items del documento -->
    <Items>
        <Item Sequence="1">
            <Codes>
                <Code Type="INT">PROD001</Code>
            </Codes>
            <Description>Producto de prueba</Description>
            <Quantity>1.0000</Quantity>
            <UnitPrice>100.0000</UnitPrice>
            <Discounts>
                <Discount Sequence="1">
                    <Amount>10.0000</Amount>
                </Discount>
            </Discounts>
            <Taxes>
                <Tax Sequence="1">
                    <TaxCode>01</TaxCode>
                    <TaxRate>7.0000</TaxRate>
                    <TaxAmount>6.3000</TaxAmount>
                </Tax>
            </Taxes>
            <Totals>
                <Total>
                    <Info Name="SubTotal" Value="90.0000"/>
                    <Info Name="TotalImpuesto" Value="6.3000"/>
                    <Info Name="Total" Value="96.3000"/>
                </Total>
            </Totals>
        </Item>
    </Items>
    
    <!-- Totales del documento -->
    <Totals>
        <Total>
            <Info Name="TotalGravado" Value="90.0000"/>
            <Info Name="TotalDescuento" Value="10.0000"/>
            <Info Name="TotalImpuesto" Value="6.3000"/>
            <Info Name="TotalVenta" Value="96.3000"/>
            <Info Name="TotalAPagar" Value="96.3000"/>
        </Total>
    </Totals>
    
    <!-- Formas de pago -->
    <Payments>
        <Payment Sequence="1">
            <PaymentMethodCode>1</PaymentMethodCode>
            <PaymentAmount>96.3000</PaymentAmount>
        </Payment>
    </Payments>
</Root>
```

## Tipos de Documentos

### 01 - Factura Operación Interna

**Uso más común**: Ventas locales en Panamá

**Características**:
- Cliente y emisor en Panamá
- ITBMS aplicable (7%)
- No requiere campos especiales
- Moneda: PAB (Balboa) o USD

**Validaciones**:
- RUC emisor válido
- RUC/Cédula receptor válido
- Items con precios y cantidades válidas
- ITBMS correctamente calculado

### 02 - Factura Importación

**Uso**: Compras a proveedores extranjeros (importación de bienes)

**Características**:
- Emisor extranjero (sin RUC panameño)
- Receptor en Panamá
- Puede incluir aduanas y transporte
- Moneda: USD u otra

**Validaciones**:
- Información completa del proveedor extranjero
- Datos de importación (aduana, transporte)
- Conversión de moneda si aplica

### 03 - Factura Exportación

**Uso**: Ventas a clientes extranjeros

**Características**:
- **REQUIERE moneda y tipo de cambio**
- Emisor en Panamá
- Receptor extranjero
- Generalmente exenta de ITBMS

**Validaciones**:
```php
if ($docType === '03') {
    if (empty($dgiData['dGen']['dInfoTransaMoneda'])) {
        throw new \InvalidArgumentException(
            'Las facturas de exportación (tipo 03) requieren información de moneda'
        );
    }
}
```

**Campos Requeridos**:
- `dGen.dInfoTransaMoneda.cMoneda`: Código de moneda (USD, EUR, etc.)
- `dGen.dInfoTransaMoneda.dTipoCambio`: Tipo de cambio del día

### 04 - Nota de Crédito Referente a FE

**Uso**: Anular o ajustar una factura electrónica previa

**Características**:
- **REQUIERE referencia a factura original**
- Debe tener CUFE de documento referenciado
- Montos generalmente negativos
- Misma moneda que documento original

**Validaciones**:
```php
if (in_array($docType, ['04', '05'])) {
    if (empty($dgiData['gDoc']['dInfoDoc']['gInfDoRef'])) {
        throw new \InvalidArgumentException(
            'Los documentos tipo 04 y 05 requieren referencias a documentos previos'
        );
    }
}
```

**Estructura de Referencia**:
```xml
<AdditionalDocumentInfo>
    <Data>
        <DataEl Name="CUFERef" Value="ABC123..."/>
        <DataEl Name="FechaRef" Value="2025-01-01T10:00:00"/>
        <DataEl Name="TipoDocRef" Value="01"/>
        <DataEl Name="NroDocRef" Value="FACT-001"/>
    </Data>
</AdditionalDocumentInfo>
```

### 05 - Nota de Débito Referente a FE

**Uso**: Agregar cargos adicionales a una factura electrónica previa

**Características**:
- **REQUIERE referencia a factura original** (igual que tipo 04)
- Montos positivos (cargos adicionales)
- Ejemplos: intereses de mora, ajustes por inflación

**Validaciones**: Mismas que tipo 04

### 06 - Nota de Crédito Genérica

**Uso**: Ajustes contables sin referencia específica

**Características**:
- No requiere referencia a documento previo
- Puede ser por descuentos globales, ajustes contables
- Más flexible que tipo 04

### 07 - Nota de Débito Genérica

**Uso**: Cargos adicionales sin referencia específica

**Características**:
- No requiere referencia a documento previo
- Cargos por servicios adicionales, penalizaciones

### 08 - Factura Zona Franca

**Uso**: Operaciones dentro de zonas francas

**Características**:
- Exenta de ITBMS
- Requiere información adicional de zona franca
- Emisor o receptor en zona franca

**Validaciones**:
- Código de zona franca
- Certificados de zona franca si aplica

### 09 - Reembolso

**Uso**: Devolución de pagos o gastos

**Características**:
- Reembolso de gastos realizados por terceros
- Puede incluir múltiples conceptos
- Generalmente no lleva ITBMS

## Clases Principales

### DigifactXmlBuilder

**Ubicación**: `app/Services/DigifactXmlBuilder.php`

**Responsabilidad**: Transformar datos DGI a XML NUC

**Métodos Públicos**:

```php
/**
 * Construir XML NUC desde datos DGI
 *
 * @param array $dgiData Datos en formato DGI de DocuCenter
 * @param bool $isTest true para ambiente de pruebas
 * @return string XML NUC formateado
 * @throws \InvalidArgumentException Si faltan datos requeridos
 */
public function buildNucXml(array $dgiData, bool $isTest = false): string
```

**Métodos Privados**:

```php
// Validación por tipo de documento
private function validateDocumentType(string $docType, array $dgiData): void

// Construcción de secciones
private function buildHeader(SimpleXMLElement $root, array $dGen): void
private function buildSeller(SimpleXMLElement $root, array $gEmis): void
private function buildBuyer(SimpleXMLElement $root, array $gDatRec): void
private function buildThirdParty(SimpleXMLElement $root, array $dgiData): void
private function buildItems(SimpleXMLElement $root, array $gItem): void
private function buildTotals(SimpleXMLElement $root, array $gTot): void
private function buildPayments(SimpleXMLElement $root, array $gPag): void

// Referencias para NC/ND
private function buildAdditionalDocumentInfo(SimpleXMLElement $root, array $gInfDoRef): void
private function buildReferencedDocuments(SimpleXMLElement $data, array $references): void

// Helpers
private function addInfoElement(SimpleXMLElement $parent, string $name, $value): void
private function addCodeElement(SimpleXMLElement $codes, string $type, $value): void
private function generateSecurityCode(): string
```

**Ejemplo de Uso**:

```php
$builder = new DigifactXmlBuilder();

$dgiData = [
    'dGen' => [
        'iDoc' => '01',
        'dFechaEm' => '2025-01-10T15:30:00',
        // ... más campos DGI
    ],
    'gEmis' => [
        'dRuc' => '155704849-2-2021',
        'dNombEm' => 'EMPRESA SA',
        // ... más campos emisor
    ],
    'gDatRec' => [
        'dRucRec' => '8-888-8888',
        'dNombRec' => 'CLIENTE SA',
        // ... más campos receptor
    ],
    'gItem' => [
        [
            'dCodProd' => 'PROD001',
            'dDesProd' => 'Producto 1',
            'dCantCodInt' => 1,
            'dPrUnit' => 100.00,
            // ... más campos item
        ]
    ],
    'gTot' => [
        'dSubTotal' => 100.00,
        'dTotDesc' => 0,
        'dTotITBMS' => 7.00,
        'dTotNeto' => 107.00,
    ],
    'gPag' => [
        [
            'iFormaPago' => '1',
            'dMontoP' => 107.00,
        ]
    ]
];

try {
    $xml = $builder->buildNucXml($dgiData, true); // true = ambiente de prueba
    echo $xml;
} catch (\InvalidArgumentException $e) {
    echo "Error de validación: " . $e->getMessage();
}
```

### DigifactService

**Ubicación**: `app/Services/DigifactService.php`

**Responsabilidad**: Cliente HTTP para API Digifact

**Métodos Públicos**:

```php
/**
 * Autenticar y obtener token JWT
 *
 * @return array ['success' => bool, 'token' => string, 'expira' => datetime]
 */
public function authenticate(): array

/**
 * Obtener token válido (renueva si expiró)
 *
 * @return string Token JWT
 * @throws \Exception Si falla autenticación
 */
public function getValidToken(): string

/**
 * Certificar documento electrónico
 *
 * @param array $documentData Datos en formato DGI
 * @param string $format Formato respuesta: PDF, XML, HTML, PDF|XML
 * @return array Respuesta con success, cufe, pdf_base64, xml_base64
 * @throws \Exception Si falla certificación
 */
public function certifyDocument(array $documentData, string $format = 'PDF'): array

/**
 * Validar conexión PAC (test de credenciales)
 *
 * @return array ['success' => bool, 'message' => string]
 */
public function validateConnection(): array

/**
 * @deprecated Usar certifyDocument directamente
 */
public function formatDocument(array $request): array
```

**Flujo de certifyDocument()**:

```php
public function certifyDocument(array $documentData, string $format = 'PDF'): array
{
    // 1. Construir XML usando el builder
    $builder = new DigifactXmlBuilder();
    $xml = $builder->buildNucXml($documentData, !$this->isProduction);
    
    // 2. Obtener token válido
    $token = $this->getValidToken();
    
    // 3. Construir URL con query params
    $url = $this->getBaseUrl() . '/transform/nuc';
    $queryParams = http_build_query([
        'TAXID' => $this->organization->ruc . '-' . $this->organization->dv,
        'USERNAME' => $this->connection->username,
        'FORMAT' => $format, // PDF, XML, HTML, PDF|XML
    ]);
    $url .= '?' . $queryParams;
    
    // 4. Enviar XML al PAC
    $response = Http::timeout(60)
        ->withHeaders([
            'Content-Type' => 'application/xml',
            'Accept' => 'application/xml',
            'Authorization' => 'Bearer ' . $token,
        ])
        ->withBody($xml, 'application/xml')
        ->post($url);
    
    // 5. Parsear respuesta XML
    $xmlResponse = simplexml_load_string($response->body());
    
    // 6. Extraer datos importantes
    return [
        'success' => $xmlResponse->codigo == '200',
        'codigo' => (string) $xmlResponse->codigo,
        'mensaje' => (string) $xmlResponse->mensaje,
        'cufe' => (string) $xmlResponse->CUFE,
        'pdf_base64' => (string) $xmlResponse->pdf_base64,
        'xml_base64' => (string) $xmlResponse->xml_base64,
    ];
}
```

**Códigos de Respuesta**:

- `200`: Éxito, documento certificado
- `400`: Error en datos del documento
- `401`: Token inválido o expirado
- `500`: Error interno del PAC

**Ejemplo de Respuesta Exitosa**:

```xml
<Root>
    <codigo>200</codigo>
    <mensaje>Documento procesado exitosamente</mensaje>
    <CUFE>PA01-155704849-2-2021-0001-001-00000001-01-202501101530-12345678901234567890123456789012</CUFE>
    <pdf_base64>JVBERi0xLjQKJeLjz9M...</pdf_base64>
    <xml_base64>PD94bWwgdmVyc2lvbj0i...</xml_base64>
</Root>
```

**Ejemplo de Respuesta con Error**:

```xml
<Root>
    <codigo>400</codigo>
    <mensaje>Error de validación</mensaje>
    <descripcion>El campo 'Currency' es requerido para facturas de exportación</descripcion>
</Root>
```

## Mapeo de Campos DGI → NUC

### Encabezado (Header)

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `dGen.iDoc` | `Header.DocType` | Directo (01-09) | ✅ |
| `dGen.dFechaEm` | `Header.IssuedDateTime` | Formato ISO8601 | ✅ |
| `dGen.iTipoEmision` | `AdditionalIssueDocInfo.Info[TipoEmision]` | Directo | ✅ |
| `dGen.iTipoOp` | `AdditionalIssueDocInfo.Info[TipoOperacion]` | Directo | ✅ |
| - | `AdditionalIssueDocInfo.Info[SecurityCode]` | Generado (9 dígitos) | ✅ |

### Emisor (Seller)

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `gEmis.dRuc` | `Seller.TaxID` | Normalizar RUC | ✅ |
| `gEmis.dDV` | `Seller.TaxIDAdditionalInfo.Info[DV]` | Zero-pad 2 dígitos | ✅ |
| `gEmis.dNombEm` | `Seller.Contact.Info[Nombre]` | Directo | ✅ |
| `gEmis.dEmail` | `Seller.Contact.Info[CorreoElectronico]` | Directo | ⚠️ |
| `gEmis.dSuc` | `Seller.BranchInfo.Info[Sucursal]` | Zero-pad 4 dígitos | ✅ |
| `gEmis.dPuntoFact` | `Seller.BranchInfo.Info[PuntoFacturacion]` | Zero-pad 3 dígitos | ✅ |

### Receptor (Buyer)

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `gDatRec.dRucRec` | `Buyer.TaxID` | Normalizar RUC/Cédula | ✅ |
| `gDatRec.dDVRec` | `Buyer.TaxIDAdditionalInfo.Info[DV]` | Zero-pad 2 dígitos | ✅ |
| `gDatRec.dNombRec` | `Buyer.AddressInfo.Info[RazonSocial]` | Directo | ✅ |
| `gDatRec.dDirecRec` | `Buyer.AddressInfo.Info[Direccion]` | Directo | ⚠️ |
| `gDatRec.dEmailRec` | `Buyer.AddressInfo.Info[CorreoElectronico]` | Directo | ⚠️ |

### Items

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `gItem[].dCodProd` | `Item.Codes.Code[Type=INT]` | Directo | ✅ |
| `gItem[].dCodEAN` | `Item.Codes.Code[Type=EAN]` | Directo | ⚠️ |
| `gItem[].dDesProd` | `Item.Description` | Directo | ✅ |
| `gItem[].dCantCodInt` | `Item.Quantity` | 4 decimales | ✅ |
| `gItem[].dPrUnit` | `Item.UnitPrice` | 4 decimales | ✅ |
| `gItem[].gDescItem[].dMontoDescIt` | `Item.Discounts.Discount.Amount` | 4 decimales | ⚠️ |
| `gItem[].gITBMSItem[].dTasaITBMS` | `Item.Taxes.Tax.TaxRate` | 4 decimales | ⚠️ |
| `gItem[].gITBMSItem[].dValorITBMS` | `Item.Taxes.Tax.TaxAmount` | 4 decimales | ⚠️ |

### Totales (Totals)

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `gTot.dSubTotal` | `Totals.Total.Info[TotalGravado]` | 4 decimales | ✅ |
| `gTot.dTotDesc` | `Totals.Total.Info[TotalDescuento]` | 4 decimales | ✅ |
| `gTot.dTotITBMS` | `Totals.Total.Info[TotalImpuesto]` | 4 decimales | ✅ |
| `gTot.dTotNeto` | `Totals.Total.Info[TotalVenta]` | 4 decimales | ✅ |
| `gTot.dTotNeto` | `Totals.Total.Info[TotalAPagar]` | 4 decimales | ✅ |

### Pagos (Payments)

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `gPag[].iFormaPago` | `Payment.PaymentMethodCode` | Directo | ✅ |
| `gPag[].dMontoP` | `Payment.PaymentAmount` | 4 decimales | ✅ |

### Referencias (AdditionalDocumentInfo) - Solo para tipos 04, 05

| Campo DGI | Campo NUC | Transformación | Requerido |
|-----------|-----------|----------------|-----------|
| `gInfDoRef[].dCUFERe` | `DataEl[Name=CUFERef]` | Directo | ✅ |
| `gInfDoRef[].dFechDoRe` | `DataEl[Name=FechaRef]` | ISO8601 | ✅ |
| `gInfDoRef[].iTipoDocRe` | `DataEl[Name=TipoDocRef]` | Directo | ✅ |
| `gInfDoRef[].dNumDocRe` | `DataEl[Name=NroDocRef]` | Directo | ⚠️ |

## Testing

### Credenciales de Prueba

```php
// Ambiente de pruebas
$baseUrl = 'https://testnucpa.digifact.com/api';

// Organización de prueba
$ruc = '155704849-2-2021';
$dv = '22';
$username = 'PRUEBAS193';
$password = 'PA.155704849-2-2021.PRUEBAS193'; // Formato: PA.{RUC}.{USERNAME}
```

### Documento de Prueba Tipo 01

```php
$request = [
    'dGen' => [
        'iDoc' => '01',
        'dFechaEm' => '2025-01-10T15:30:00',
        'iTipoEmision' => '01',
        'iTipoOp' => '1',
    ],
    'gEmis' => [
        'dRuc' => '155704849-2-2021',
        'dDV' => '22',
        'dNombEm' => 'EMPRESA DE PRUEBA SA',
        'dSuc' => '0001',
        'dPuntoFact' => '001',
        'dEmail' => 'prueba@example.com',
    ],
    'gDatRec' => [
        'iTipoRec' => '02',
        'dRucRec' => '8-888-8888',
        'dDVRec' => '02',
        'dNombRec' => 'CLIENTE DE PRUEBA',
        'dDirecRec' => 'Panama, Panama',
        'dEmailRec' => 'cliente@example.com',
    ],
    'gItem' => [
        [
            'dSecItem' => 1,
            'dCodProd' => 'PROD001',
            'dDesProd' => 'Producto de prueba',
            'dCantCodInt' => 1,
            'dPrUnit' => 100.00,
            'dPrItem' => 100.00,
            'dPrTot' => 100.00,
            'dPrTotDesc' => 100.00,
            'gDescItem' => [],
            'gITBMSItem' => [
                [
                    'iCodITBMS' => '01',
                    'dTasaITBMS' => 7.00,
                    'dValorITBMS' => 7.00,
                ]
            ],
            'dValTotItem' => 107.00,
        ]
    ],
    'gTot' => [
        'dSubTotal' => 100.00,
        'dTotDesc' => 0,
        'dTotITBMS' => 7.00,
        'dTotNeto' => 107.00,
    ],
    'gPag' => [
        [
            'iFormaPago' => '1',
            'dMontoP' => 107.00,
        ]
    ]
];

// Crear servicio
$service = new DigifactService($connection, $organization);

// Certificar documento
$result = $service->certifyDocument($request, 'PDF|XML');

if ($result['success']) {
    echo "CUFE: " . $result['cufe'] . "\n";
    
    // Guardar PDF
    $pdfContent = base64_decode($result['pdf_base64']);
    file_put_contents('factura.pdf', $pdfContent);
    
    // Guardar XML
    $xmlContent = base64_decode($result['xml_base64']);
    file_put_contents('factura.xml', $xmlContent);
} else {
    echo "Error: " . $result['mensaje'] . "\n";
}
```

### Documento de Prueba Tipo 03 (Exportación)

```php
$requestExport = [
    'dGen' => [
        'iDoc' => '03', // Exportación
        'dFechaEm' => '2025-01-10T15:30:00',
        'iTipoEmision' => '01',
        'iTipoOp' => '1',
        'dInfoTransaMoneda' => [ // REQUERIDO para tipo 03
            'cMoneda' => 'USD',
            'dTipoCambio' => 1.00,
        ],
    ],
    'gEmis' => [
        'dRuc' => '155704849-2-2021',
        'dDV' => '22',
        'dNombEm' => 'EXPORTADORA SA',
        'dSuc' => '0001',
        'dPuntoFact' => '001',
    ],
    'gDatRec' => [
        'iTipoRec' => '03', // Cliente extranjero
        'dRucRec' => 'EXT-12345',
        'dDVRec' => '00',
        'dNombRec' => 'CLIENTE INTERNACIONAL INC',
        'dDirecRec' => 'Miami, FL, USA',
    ],
    'gItem' => [
        [
            'dSecItem' => 1,
            'dCodProd' => 'EXP001',
            'dDesProd' => 'Producto exportación',
            'dCantCodInt' => 10,
            'dPrUnit' => 50.00,
            'dPrItem' => 500.00,
            'dPrTot' => 500.00,
            'dPrTotDesc' => 500.00,
            'gDescItem' => [],
            'gITBMSItem' => [], // Exportaciones generalmente exentas
            'dValTotItem' => 500.00,
        ]
    ],
    'gTot' => [
        'dSubTotal' => 500.00,
        'dTotDesc' => 0,
        'dTotITBMS' => 0,
        'dTotNeto' => 500.00,
    ],
    'gPag' => [
        [
            'iFormaPago' => '2', // Transferencia
            'dMontoP' => 500.00,
        ]
    ]
];

$result = $service->certifyDocument($requestExport, 'PDF');
```

### Documento de Prueba Tipo 04 (NC Referente)

```php
$requestNC = [
    'dGen' => [
        'iDoc' => '04', // NC Referente
        'dFechaEm' => '2025-01-10T16:00:00',
        'iTipoEmision' => '01',
        'iTipoOp' => '1',
    ],
    'gEmis' => [
        'dRuc' => '155704849-2-2021',
        'dDV' => '22',
        'dNombEm' => 'EMPRESA SA',
        'dSuc' => '0001',
        'dPuntoFact' => '001',
    ],
    'gDatRec' => [
        'iTipoRec' => '02',
        'dRucRec' => '8-888-8888',
        'dDVRec' => '02',
        'dNombRec' => 'CLIENTE SA',
    ],
    'gDoc' => [
        'dInfoDoc' => [
            'gInfDoRef' => [ // REQUERIDO para tipo 04/05
                [
                    'dCUFERe' => 'PA01-155704849-2-2021-0001-001-00000001-01-202501101530-ABC123',
                    'dFechDoRe' => '2025-01-10T15:30:00',
                    'iTipoDocRe' => '01',
                    'dNumDocRe' => 'FACT-001',
                ]
            ]
        ]
    ],
    'gItem' => [
        [
            'dSecItem' => 1,
            'dCodProd' => 'PROD001',
            'dDesProd' => 'Devolución producto',
            'dCantCodInt' => -1, // Cantidad negativa
            'dPrUnit' => 100.00,
            'dPrItem' => -100.00, // Monto negativo
            'dPrTot' => -100.00,
            'dPrTotDesc' => -100.00,
            'gDescItem' => [],
            'gITBMSItem' => [
                [
                    'iCodITBMS' => '01',
                    'dTasaITBMS' => 7.00,
                    'dValorITBMS' => -7.00, // ITBMS negativo
                ]
            ],
            'dValTotItem' => -107.00,
        ]
    ],
    'gTot' => [
        'dSubTotal' => -100.00,
        'dTotDesc' => 0,
        'dTotITBMS' => -7.00,
        'dTotNeto' => -107.00, // Total negativo
    ],
    'gPag' => [
        [
            'iFormaPago' => '1',
            'dMontoP' => -107.00, // Pago negativo (devolución)
        ]
    ]
];

$result = $service->certifyDocument($requestNC, 'PDF|XML');
```

## Manejo de Errores

### Errores de Validación

```php
try {
    $xml = $builder->buildNucXml($dgiData, true);
} catch (\InvalidArgumentException $e) {
    // Errores de datos faltantes o incorrectos
    Log::error('Error de validación Digifact', [
        'error' => $e->getMessage(),
        'doc_type' => $dgiData['dGen']['iDoc'] ?? 'unknown',
    ]);
    
    // Ejemplos de mensajes:
    // - "Las facturas de exportación (tipo 03) requieren información de moneda"
    // - "Los documentos tipo 04 y 05 requieren referencias a documentos previos"
    // - "El campo 'dGen.iDoc' es requerido"
}
```

### Errores de API

```php
$result = $service->certifyDocument($request, 'PDF');

if (!$result['success']) {
    $codigo = $result['codigo'] ?? '500';
    
    switch ($codigo) {
        case '400':
            // Error de validación del PAC
            Log::error('Digifact rechazó documento', [
                'mensaje' => $result['mensaje'],
                'descripcion' => $result['descripcion'] ?? null,
            ]);
            break;
            
        case '401':
            // Token expirado o inválido
            // getValidToken() ya maneja renovación automática
            Log::warning('Token Digifact expirado, renovando...');
            break;
            
        case '500':
            // Error interno del PAC
            Log::error('Error interno Digifact', [
                'mensaje' => $result['mensaje'],
            ]);
            break;
    }
}
```

### Errores de Red

```php
try {
    $result = $service->certifyDocument($request, 'PDF');
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    // Timeout o error de conexión
    Log::error('Error de conexión con Digifact', [
        'error' => $e->getMessage(),
        'timeout' => 60,
    ]);
    
    // Implementar retry con backoff exponencial
    $maxRetries = 3;
    $delay = 1; // segundos
    
    for ($i = 1; $i <= $maxRetries; $i++) {
        sleep($delay * $i);
        
        try {
            $result = $service->certifyDocument($request, 'PDF');
            break; // Éxito
        } catch (\Exception $e) {
            if ($i === $maxRetries) {
                throw $e; // Falló después de todos los intentos
            }
        }
    }
}
```

## Logging

### Niveles de Log

```php
// Inicio de certificación
Log::info('Digifact - Iniciando certificación', [
    'organization_id' => $organization->id,
    'invoice_number' => $sale->InvoiceNumber,
    'document_type' => $request['dGen']['iDoc'],
    'xml_length' => strlen($xml),
]);

// Respuesta exitosa
Log::info('Documento certificado exitosamente con Digifact', [
    'organization_id' => $organization->id,
    'cufe' => $result['cufe'],
    'invoice_number' => $sale->InvoiceNumber,
]);

// Error de certificación
Log::error('Error al certificar documento con Digifact', [
    'status' => $response->status(),
    'codigo' => $result['codigo'],
    'mensaje' => $result['mensaje'],
    'descripcion' => $result['descripcion'] ?? null,
    'organization_id' => $organization->id,
]);

// Error de parseo XML
Log::error('Error al parsear respuesta XML de Digifact', [
    'error' => $parseException->getMessage(),
    'response_body' => $responseBody,
    'organization_id' => $organization->id,
]);
```

## Rendimiento

### Optimizaciones

**Token Caching**: Tokens JWT tienen validez de 27 días, se cachean automáticamente:

```php
$cacheKey = "digifact_token_{$this->organization->id}_{$this->connection->id}";
$token = Cache::remember($cacheKey, now()->addDays(27), function () {
    return $this->authenticate()['token'];
});
```

**Timeout Apropiado**: 60 segundos para certificación:

```php
Http::timeout(60)->post($url);
```

**Compresión**: XML puede comprimirse para reducir bandwidth:

```php
$compressedXml = gzencode($xml, 9);
Http::withHeaders(['Content-Encoding' => 'gzip'])
    ->withBody($compressedXml, 'application/xml')
    ->post($url);
```

### Métricas

```php
// Tiempo de construcción XML
$startBuild = microtime(true);
$xml = $builder->buildNucXml($dgiData, true);
$buildTime = microtime(true) - $startBuild;

// Tiempo de certificación
$startCertify = microtime(true);
$result = $service->certifyDocument($request, 'PDF');
$certifyTime = microtime(true) - $startCertify;

Log::info('Digifact performance', [
    'build_time_ms' => round($buildTime * 1000, 2),
    'certify_time_ms' => round($certifyTime * 1000, 2),
    'total_time_ms' => round(($buildTime + $certifyTime) * 1000, 2),
    'xml_size_kb' => round(strlen($xml) / 1024, 2),
]);
```

## Checklist de Implementación

### Fase 1: Setup Básico ✅
- [x] DigifactService con autenticación
- [x] DigifactXmlBuilder con transformación completa
- [x] Validaciones por tipo de documento
- [x] Integración en CreateFastJob

### Fase 2: Testing 🔄
- [ ] Test con documento tipo 01 (Factura Interna)
- [ ] Test con documento tipo 03 (Exportación)
- [ ] Test con documento tipo 04 (NC Referente)
- [ ] Validar CUFE generado
- [ ] Validar PDF generado
- [ ] Validar XML generado

### Fase 3: Producción ⏳
- [ ] Obtener credenciales de producción
- [ ] Configurar endpoint producción
- [ ] Migrar clientes de prueba
- [ ] Monitoreo y alertas
- [ ] Documentación para usuarios

## Referencias

- **API Digifact**: https://testnucpa.digifact.com/api
- **Documentación Técnica**: `docs/technical/digifact-nuc-complete-schema.md`
- **Tipos de Documentos**: `docs/technical/digifact-all-document-types.md`
- **Formato XML**: `docs/technical/digifact-xml-format-analysis.md`

## Soporte

Para problemas con Digifact:
- **Ambiente de pruebas**: testnucpa.digifact.com
- **Email soporte**: (pendiente)
- **Logs**: Revisar `storage/logs/laravel.log` con keyword "Digifact"

---

**Última actualización**: 2025-01-10  
**Versión**: 1.0.0  
**Estado**: Implementación completa, testing pendiente
