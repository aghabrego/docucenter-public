<?php
// Test standalone para token Digifact PRODUCCIÓN sin Laravel
// Documentación Técnica v2.0.4
require 'vendor/autoload.php';

$username = "PA.155770712-2-2025.155770712-2-2025";
$password = "Freskura123*";
$ruc = "155770712-2-2025";
$endpoint = "https://nucpa.digifact.com/api/login/get_token";

echo "\n=== Test Token Producción Digifact (standalone) ===\n\n";
echo "Endpoint: $endpoint\n";
echo "Username: $username\n\n";

try {
    $client = new \GuzzleHttp\Client();
    $response = $client->post($endpoint, [
        'json' => [
            'Username' => $username,
            'Password' => $password,
        ],
        'timeout' => 30,
        'verify' => true,
    ]);
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    echo "Status Code: $statusCode\n";
    echo "Response:\n$body\n\n";
    $data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($data['Token'])) {
            echo "✓ Token generado exitosamente\n";
            echo "Token: " . substr($data['Token'], 0, 50) . "...\n";
            echo "Longitud: " . strlen($data['Token']) . " caracteres\n";
            if (isset($data['Expires'])) {
                echo "Expires: " . $data['Expires'] . "\n";
            }
        } else {
            echo "✗ Token no encontrado en respuesta\n";
            print_r($data);
        }
    } else {
        echo "No es JSON válido\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n=== Test completado ===\n";
