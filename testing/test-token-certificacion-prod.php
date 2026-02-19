<?php
/**
 * Test completo: Token + Certificación en Producción
 * Documentación Técnica v2.0.4
 */
require 'vendor/autoload.php';

$username = "PA.155770712-2-2025.155770712-2-2025";
$password = "Freskura123*";

echo "\n=== Test Completo Digifact - Producción ===\n";
echo "Step 1: Obtener Token\n";
echo "Step 2: Usar token para certificar documento\n\n";

try {
    $client = new \GuzzleHttp\Client(['verify' => false]);

    // STEP 1: Get Token
    echo "1. Solicitando token...\n";
    $response = $client->post('https://nucpa.digifact.com/api/login/get_token', [
        'json' => [
            'Username' => $username,
            'Password' => $password,
        ],
        'timeout' => 30,
    ]);

    $tokenData = json_decode($response->getBody()->getContents(), true);
    $token = $tokenData['Token'] ?? null;

    if (!$token) {
        echo "✗ Error: No se obtuvo token\n";
        echo json_encode($tokenData) . "\n";
        exit(1);
    }

    echo "✓ Token obtenido: " . substr($token, 0, 50) . "...\n";
    echo "✓ Expira: " . $tokenData['expira_en'] . "\n\n";

    // STEP 2: Certificate Document (usando XML mínimo)
    echo "2. Preparando documento para certificación...\n";

    $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Root>
    <Version>1.00</Version>
    <CountryCode>PA</CountryCode>
    <Header>
        <DocType>01</DocType>
        <IssuedDateTime>2025-01-31T10:30:00-05:00</IssuedDateTime>
        <AdditionalIssueType>1</AdditionalIssueType>
        <AdditionalIssueDocInfo>
            <Info Name="TipoEmision" Value="01"/>
            <Info Name="NumeroDF" Value="0000000001"/>
            <Info Name="PtoFactDF" Value="001"/>
            <Info Name="CodigoSeguridad" Value="000000001"/>
            <Info Name="NaturalezaOperacion" Value="01"/>
            <Info Name="TipoOperacion" Value="1"/>
            <Info Name="DestinoOperacion" Value="1"/>
            <Info Name="FormatoGeneracion" Value="1"/>
            <Info Name="ManeraEntrega" Value="1"/>
            <Info Name="EnvioContenedor" Value="1"/>
            <Info Name="ProcesoGeneracion" Value="3"/>
        </AdditionalIssueDocInfo>
    </Header>
    <Seller>
        <TaxID>155770712-2-2025</TaxID>
        <TaxIDType>2</TaxIDType>
        <Name>Empresa Test</Name>
    </Seller>
    <Buyer>
        <TaxID>8-000-000000</TaxID>
        <TaxIDType>01</TaxIDType>
        <Name>Cliente Test</Name>
    </Buyer>
    <Items>
        <Item>
            <LineNum>1</LineNum>
            <Description>Producto Test</Description>
            <Quantity>1</Quantity>
            <UnitPrice>100.00</UnitPrice>
            <Amount>100.00</Amount>
        </Item>
    </Items>
    <Totals>
        <SubTotal>100.00</SubTotal>
        <Tax>7.00</Tax>
        <TotalAmount>107.00</TotalAmount>
    </Totals>
    <Payments>
        <Payment>
            <PaymentMethod>01</PaymentMethod>
            <Amount>107.00</Amount>
        </Payment>
    </Payments>
</Root>
XML;

    echo "3. Certificando documento...\n";

    $response = $client->post('https://nucpa.digifact.com/api/v2/transform/nuc', [
        'headers' => [
            'Authorization' => $token,
            'Content-Type' => 'application/xml',
        ],
        'query' => [
            'TAXID' => '155770712-2-2025',
            'FORMAT' => '1',
            'USERNAME' => $username,
        ],
        'body' => $xmlContent,
        'timeout' => 30,
    ]);

    $statusCode = $response->getStatusCode();
    $responseBody = $response->getBody()->getContents();

    echo "✓ Status Code: $statusCode\n";
    echo "Response:\n";
    echo $responseBody . "\n\n";

    $data = json_decode($responseBody, true);
    if ($data && isset($data['XML'])) {
        echo "✓ Documento certificado exitosamente\n";
        echo "✓ XML con firma incluido\n";
    } elseif (isset($data['error']) || isset($data['Error'])) {
        echo "✗ Error en certificación:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test completado ===\n";
