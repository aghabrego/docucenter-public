<?php

require __DIR__ . '/vendor/autoload.php';

$username = "PA.155770712-2-2025.155770712-2-2025";
$password = "Freskura123*";

echo "\n=== Test Token Digifact QA ===\n";
echo "Probando con credenciales reales en QA...\n\n";

$endpoint = "https://testnucpa.digifact.com/api/login/get_token";

echo "Endpoint: $endpoint\n";

try {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->post($endpoint, [
        'json' => ['Username' => $username, 'Password' => $password],
        'timeout' => 30,
    ]);

    $code = $response->getStatusCode();
    $body = $response->getBody()->getContents();

    echo "✓ Status: $code\n";
    echo "Response: $body\n\n";

    $data = json_decode($body, true);
    if ($data && isset($data['Token'])) {
        echo "✓ Token exitoso: " . substr($data['Token'], 0, 40) . "...\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}
