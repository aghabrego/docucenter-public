<?php
/**
 * Script de Prueba: Autenticación AWS Cognito + PlusMovil API
 *
 * Objetivo: Probar autenticación con AWS Cognito y consulta de sys-logs
 * API Base: https://28cwop8rj6.execute-api.us-east-1.amazonaws.com/dev
 *
 * Uso:
 * php docs/testing/test-plusmovil-cognito-auth.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// ============================================
// CONFIGURACIÓN
// ============================================

$config = [
    'qa' => [
        'client_id' => '7t3s7lb4tfg6ssal586l929ovl',
        'base_url' => 'https://28cwop8rj6.execute-api.us-east-1.amazonaws.com/dev',
        'cognito_url' => 'https://cognito-idp.us-east-1.amazonaws.com/', // URL base de Cognito
    ],
    'prod' => [
        'client_id' => '771vv2q1ararj5u1f084opsdg7',
        'base_url' => 'https://28cwop8rj6.execute-api.us-east-1.amazonaws.com/prod',
        'cognito_url' => 'https://cognito-idp.us-east-1.amazonaws.com/',
    ],
];

// Ambiente a usar (cambiar según necesidad)
$environment = 'qa';
$currentConfig = $config[$environment];

// Credenciales (solicitar al usuario)
echo "===========================================\n";
echo "PRUEBA DE AUTENTICACIÓN AWS COGNITO + API\n";
echo "===========================================\n\n";
echo "Ambiente: {$environment}\n";
echo "Client ID: {$currentConfig['client_id']}\n";
echo "API Base URL: {$currentConfig['base_url']}\n\n";

// Solicitar credenciales
echo "Ingrese las credenciales de usuario:\n";
echo "Usuario (email): ";
$username = trim(fgets(STDIN));

echo "Password: ";
// Ocultar password en terminal
system('stty -echo');
$password = trim(fgets(STDIN));
system('stty echo');
echo "\n\n";

// ============================================
// PASO 1: AUTENTICACIÓN CON AWS COGNITO
// ============================================

echo "===========================================\n";
echo "PASO 1: AUTENTICACIÓN AWS COGNITO\n";
echo "===========================================\n\n";

try {
    // Método 1: Usando AWS SDK (si está disponible)
    // Para autenticación con Cognito, necesitamos usar InitiateAuth

    // Por ahora, simularemos con HTTP directo
    // Nota: AWS Cognito requiere firma de peticiones, típicamente se usa AWS SDK

    echo "Intentando autenticación con AWS Cognito...\n";

    // Simulación de token (en producción se obtendría de Cognito)
    // Nota: Para implementación real, necesitamos AWS SDK de PHP
    echo "⚠️  IMPORTANTE: Para autenticación real con AWS Cognito se requiere:\n";
    echo "   - AWS SDK for PHP (composer require aws/aws-sdk-php)\n";
    echo "   - User Pool ID del Cognito\n";
    echo "   - Región de AWS\n\n";

    // Ejemplo de autenticación con AWS SDK (comentado)
    /*
    use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

    $client = new CognitoIdentityProviderClient([
        'region' => 'us-east-1',
        'version' => 'latest',
    ]);

    $result = $client->initiateAuth([
        'AuthFlow' => 'USER_PASSWORD_AUTH',
        'ClientId' => $currentConfig['client_id'],
        'AuthParameters' => [
            'USERNAME' => $username,
            'PASSWORD' => $password,
        ],
    ]);

    $accessToken = $result['AuthenticationResult']['AccessToken'];
    $idToken = $result['AuthenticationResult']['IdToken'];
    $refreshToken = $result['AuthenticationResult']['RefreshToken'];
    */

    // Para propósitos de prueba, solicitar token manualmente
    echo "Para continuar con la prueba, necesitas obtener un token de Cognito.\n";
    echo "Opciones:\n";
    echo "1. Usar la consola web y copiar el token del navegador\n";
    echo "2. Usar AWS CLI: aws cognito-idp initiate-auth\n";
    echo "3. Implementar AWS SDK en el código\n\n";

    echo "¿Deseas ingresar un token manualmente para continuar la prueba? (s/n): ";
    $response = trim(fgets(STDIN));

    if (strtolower($response) !== 's') {
        echo "Prueba cancelada.\n";
        exit(0);
    }

    echo "\nIngresa el Access Token de AWS Cognito:\n";
    $accessToken = trim(fgets(STDIN));

    if (empty($accessToken)) {
        throw new Exception("Token no proporcionado");
    }

    echo "\n✅ Token configurado correctamente\n\n";

} catch (Exception $e) {
    echo "❌ Error en autenticación: {$e->getMessage()}\n";
    exit(1);
}

// ============================================
// PASO 2: PRUEBA DE ENDPOINT /sys-logs
// ============================================

echo "===========================================\n";
echo "PASO 2: CONSULTA DE SYS-LOGS\n";
echo "===========================================\n\n";

try {
    $endpoint = "{$currentConfig['base_url']}/sys-logs";

    echo "Endpoint: {$endpoint}\n";
    echo "Realizando petición GET...\n\n";

    // Realizar petición sin filtros primero
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$accessToken}",
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ])->get($endpoint);

    echo "Status Code: {$response->status()}\n";
    echo "Headers:\n";
    foreach ($response->headers() as $key => $values) {
        echo "  {$key}: " . implode(', ', $values) . "\n";
    }
    echo "\n";

    if ($response->successful()) {
        $data = $response->json();

        echo "✅ Petición exitosa\n\n";
        echo "Estructura de respuesta:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        if (is_array($data) && count($data) > 0) {
            echo "Total de registros obtenidos: " . count($data) . "\n";
            echo "\nPrimer registro de ejemplo:\n";
            echo json_encode($data[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }

    } else {
        echo "❌ Error en la petición\n";
        echo "Body: {$response->body()}\n";
    }

} catch (Exception $e) {
    echo "❌ Error en consulta: {$e->getMessage()}\n";
    exit(1);
}

// ============================================
// PASO 3: PRUEBA CON FILTROS
// ============================================

echo "\n===========================================\n";
echo "PASO 3: PRUEBA CON FILTROS\n";
echo "===========================================\n\n";

echo "¿Deseas probar con filtros? (s/n): ";
$testFilters = trim(fgets(STDIN));

if (strtolower($testFilters) === 's') {
    $filters = [
        'limit' => 10,
        'offset' => 0,
        // Puedes agregar más filtros según la API
        // 'created_at_between' => '2024-01-01,2024-12-31',
        // 'order_by' => 'created_at',
        // 'order_dir' => 'desc',
    ];

    echo "Filtros a aplicar:\n";
    echo json_encode($filters, JSON_PRETTY_PRINT) . "\n\n";

    try {
        $endpoint = "{$currentConfig['base_url']}/sys-logs";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($endpoint, $filters);

        echo "Status Code: {$response->status()}\n\n";

        if ($response->successful()) {
            $data = $response->json();

            echo "✅ Petición con filtros exitosa\n\n";
            echo "Total de registros: " . count($data) . "\n\n";

            if (is_array($data) && count($data) > 0) {
                echo "Registros obtenidos:\n";
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }

        } else {
            echo "❌ Error en la petición con filtros\n";
            echo "Body: {$response->body()}\n";
        }

    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

// ============================================
// RESUMEN
// ============================================

echo "\n===========================================\n";
echo "RESUMEN DE LA PRUEBA\n";
echo "===========================================\n\n";

echo "Ambiente probado: {$environment}\n";
echo "API Base URL: {$currentConfig['base_url']}\n";
echo "Client ID: {$currentConfig['client_id']}\n\n";

echo "Próximos pasos para implementación:\n";
echo "1. ✅ Instalar AWS SDK: composer require aws/aws-sdk-php\n";
echo "2. ✅ Configurar credenciales de Cognito en .env\n";
echo "3. ✅ Implementar método de autenticación en PlusMovilInvoiceService\n";
echo "4. ✅ Actualizar base URL del servicio\n";
echo "5. ✅ Probar integración completa\n\n";

echo "Configuración necesaria en .env:\n";
echo "PLUSMOVIL_BASE_URL={$currentConfig['base_url']}\n";
echo "PLUSMOVIL_CLIENT_ID={$currentConfig['client_id']}\n";
echo "PLUSMOVIL_COGNITO_REGION=us-east-1\n";
echo "PLUSMOVIL_COGNITO_USER_POOL_ID=<solicitar>\n";
echo "PLUSMOVIL_USERNAME=<usuario>\n";
echo "PLUSMOVIL_PASSWORD=<password>\n\n";

echo "Prueba completada.\n";
