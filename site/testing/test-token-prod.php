<?php
// Archivo simple para probar token en producción sin bootstrap completo

require 'vendor/autoload.php';

$client = new \GuzzleHttp\Client();

echo "\n=== Test Token Producción Digifact ===\n\n";

$endpoint = "https://nucpa.digifact.com/api/login/get_token"; // v2.0.4
$username = "PA.155770712-2-2025.155770712-2-2025";
$password = "Freskura123*";

echo "Endpoint: $endpoint\n";
echo "Username: $username\n\n";

try {
    $response = $client->post($endpoint, [
        'json' => [
            'Username' => $username,
            'Password' => $password,
        ],
        'timeout' => 30,
    ]);

    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();

    echo "Status Code: $statusCode\n\n";
    echo "Response:\n";
    echo $body . "\n\n";

    $data = json_decode($body, true);

    if ($data && isset($data['Token'])) {
        echo "✓ Token generado exitosamente\n";
        echo "Token: " . substr($data['Token'], 0, 50) . "...\n";
        echo "Longitud: " . strlen($data['Token']) . " caracteres\n";
        if (isset($data['Expires'])) {
            echo "Expires: " . $data['Expires'] . "\n";
        }
    } else {
        echo "✗ Sin token en respuesta\n";
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
