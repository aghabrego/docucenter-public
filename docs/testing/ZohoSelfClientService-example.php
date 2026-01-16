<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\RequestException;

/**
 * Servicio para integración con Zoho Books usando Self Client OAuth 2.0
 *
 * Este servicio maneja:
 * - Autenticación OAuth 2.0
 * - Refresh automático de tokens
 * - Llamadas API autenticadas
 * - Rate limiting y manejo de errores
 */
class ZohoSelfClientService
{
    protected $client;
    protected $connection;
    protected $config;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);

        $this->config = config('zoho');
    }

    /**
     * Establecer la conexión a usar
     */
    public function setConnection(Connection $connection): self
    {
        if ($connection->application !== 'zoho-self-client') {
            throw new \InvalidArgumentException('Connection must be of type zoho-self-client');
        }

        $this->connection = $connection;
        return $this;
    }

    /**
     * Generar URL de autorización OAuth
     */
    public function getAuthorizationUrl(string $state = null): string
    {
        $settings = $this->connection->settings;
        $domain = $settings['domain'] ?? 'com';

        $params = [
            'response_type' => 'code',
            'client_id' => $settings['client_id'],
            'scope' => $settings['scope'] ?? 'ZohoBooks.fullaccess.all',
            'redirect_uri' => $settings['redirect_uri'],
            'access_type' => 'offline',
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $authUrl = $this->config['domains'][$domain] . $this->config['oauth']['authorize_url'];

        return $authUrl . '?' . http_build_query($params);
    }

    /**
     * Intercambiar código de autorización por tokens
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $settings = $this->connection->settings;
        $domain = $settings['domain'] ?? 'com';

        $tokenUrl = $this->config['domains'][$domain] . $this->config['oauth']['token_url'];

        try {
            $response = $this->client->post($tokenUrl, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $settings['client_id'],
                    'client_secret' => $settings['client_secret'],
                    'redirect_uri' => $settings['redirect_uri'],
                    'code' => $code,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Guardar tokens en la conexión
            $this->saveTokens($data);

            return $data;

        } catch (RequestException $e) {
            Log::error('Zoho OAuth token exchange failed', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);

            throw new \Exception('Failed to exchange code for tokens: ' . $e->getMessage());
        }
    }

    /**
     * Refrescar tokens automáticamente
     */
    public function refreshTokens(): array
    {
        $settings = $this->connection->settings;
        $domain = $settings['domain'] ?? 'com';

        if (empty($settings['refresh_token'])) {
            throw new \Exception('No refresh token available');
        }

        $tokenUrl = $this->config['domains'][$domain] . $this->config['oauth']['token_url'];

        try {
            $response = $this->client->post($tokenUrl, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $settings['client_id'],
                    'client_secret' => $settings['client_secret'],
                    'refresh_token' => $settings['refresh_token'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Guardar nuevos tokens
            $this->saveTokens($data);

            return $data;

        } catch (RequestException $e) {
            Log::error('Zoho token refresh failed', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to refresh tokens: ' . $e->getMessage());
        }
    }

    /**
     * Realizar llamada API autenticada
     */
    public function apiCall(string $method, string $endpoint, array $data = []): array
    {
        // Verificar si necesitamos refrescar el token
        $this->ensureValidToken();

        $settings = $this->connection->settings;
        $domain = $settings['domain'] ?? 'com';
        $baseUrl = $this->config['api_base'][$domain];

        // Agregar organization_id a los parámetros si existe
        if (!empty($settings['organization_id'])) {
            if ($method === 'GET') {
                $data['organization_id'] = $settings['organization_id'];
            } else {
                // Para POST/PUT, puede ir en query params o headers según endpoint
                $endpoint .= (strpos($endpoint, '?') !== false ? '&' : '?') . 'organization_id=' . $settings['organization_id'];
            }
        }

        $options = [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $settings['access_token'],
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
        ];

        if ($method === 'GET') {
            $options['query'] = $data;
        } else {
            $options['json'] = $data;
        }

        try {
            $response = $this->client->request($method, $baseUrl . $endpoint, $options);
            $responseData = json_decode($response->getBody()->getContents(), true);

            // Log para debugging
            Log::info('Zoho API call', [
                'connection_id' => $this->connection->id,
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $response->getStatusCode(),
                'response_size' => strlen($response->getBody()),
            ]);

            // Manejar errores de API
            if ($response->getStatusCode() >= 400) {
                throw new \Exception('API Error: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            return $responseData;

        } catch (RequestException $e) {
            Log::error('Zoho API call failed', [
                'connection_id' => $this->connection->id,
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('API call failed: ' . $e->getMessage());
        }
    }

    /**
     * Métodos específicos de Zoho Books API
     */

    public function getOrganizations(): array
    {
        return $this->apiCall('GET', '/organizations');
    }

    public function getSalesOrders(array $params = []): array
    {
        return $this->apiCall('GET', '/salesorders', $params);
    }

    public function createSalesOrder(array $data): array
    {
        return $this->apiCall('POST', '/salesorders', $data);
    }

    public function getItems(array $params = []): array
    {
        return $this->apiCall('GET', '/items', $params);
    }

    public function createItem(array $data): array
    {
        return $this->apiCall('POST', '/items', $data);
    }

    public function getContacts(array $params = []): array
    {
        return $this->apiCall('GET', '/contacts', $params);
    }

    public function createContact(array $data): array
    {
        return $this->apiCall('POST', '/contacts', $data);
    }

    /**
     * Métodos helper privados
     */

    private function saveTokens(array $tokenData): void
    {
        $settings = $this->connection->settings;

        if (isset($tokenData['access_token'])) {
            $settings['access_token'] = $tokenData['access_token'];
        }

        if (isset($tokenData['refresh_token'])) {
            $settings['refresh_token'] = $tokenData['refresh_token'];
        }

        if (isset($tokenData['expires_in'])) {
            $settings['expires_at'] = now()->addSeconds($tokenData['expires_in'])->toDateTimeString();
        }

        $this->connection->settings = $settings;
        $this->connection->save();

        Log::info('Zoho tokens saved', [
            'connection_id' => $this->connection->id,
            'expires_at' => $settings['expires_at'] ?? 'unknown',
        ]);
    }

    private function ensureValidToken(): void
    {
        $settings = $this->connection->settings;

        // Verificar si el token está próximo a expirar (5 minutos de buffer)
        if (isset($settings['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($settings['expires_at']);
            if ($expiresAt->subMinutes(5)->isPast()) {
                Log::info('Zoho token expired, refreshing', [
                    'connection_id' => $this->connection->id,
                    'expires_at' => $settings['expires_at'],
                ]);

                $this->refreshTokens();
            }
        }
    }

    /**
     * Revocar tokens
     */
    public function revokeTokens(): bool
    {
        $settings = $this->connection->settings;
        $domain = $settings['domain'] ?? 'com';

        if (empty($settings['refresh_token'])) {
            return true; // Ya no hay tokens que revocar
        }

        $revokeUrl = $this->config['domains'][$domain] . $this->config['oauth']['revoke_url'];

        try {
            $this->client->post($revokeUrl, [
                'form_params' => [
                    'token' => $settings['refresh_token'],
                ],
            ]);

            // Limpiar tokens de la conexión
            $settings['access_token'] = null;
            $settings['refresh_token'] = null;
            $settings['expires_at'] = null;

            $this->connection->settings = $settings;
            $this->connection->save();

            Log::info('Zoho tokens revoked', [
                'connection_id' => $this->connection->id,
            ]);

            return true;

        } catch (RequestException $e) {
            Log::error('Zoho token revocation failed', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
