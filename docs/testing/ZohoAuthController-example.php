<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Services\ZohoSelfClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controlador para manejar el flujo OAuth de Zoho Self Client
 */
class ZohoAuthController extends Controller
{
    protected $zohoService;

    public function __construct(ZohoSelfClientService $zohoService)
    {
        $this->zohoService = $zohoService;
    }

    /**
     * Iniciar el proceso de autorización OAuth
     *
     * @param string $connectionId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authorize($connectionId)
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            // Verificar que la conexión es del tipo correcto
            if ($connection->application !== 'zoho-self-client') {
                return redirect()->back()->with('error', 'Invalid connection type');
            }

            // Verificar que el usuario tiene permisos
            // TODO: Agregar verificación de permisos según lógica de DocuCenter

            // Generar state único para seguridad
            $state = Str::random(40);
            session(['zoho_oauth_state' => $state, 'zoho_connection_id' => $connectionId]);

            // Configurar servicio y generar URL
            $this->zohoService->setConnection($connection);
            $authUrl = $this->zohoService->getAuthorizationUrl($state);

            Log::info('Zoho OAuth authorization started', [
                'connection_id' => $connectionId,
                'user_id' => auth()->id(),
                'state' => $state,
            ]);

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('Zoho OAuth authorization failed', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'Failed to start authorization: ' . $e->getMessage());
        }
    }

    /**
     * Manejar el callback de OAuth desde Zoho
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            // Verificar state para prevenir CSRF
            $sessionState = session('zoho_oauth_state');
            $requestState = $request->get('state');

            if (empty($sessionState) || $sessionState !== $requestState) {
                throw new \Exception('Invalid state parameter');
            }

            // Verificar que tenemos el código de autorización
            $code = $request->get('code');
            if (empty($code)) {
                $error = $request->get('error', 'No authorization code received');
                throw new \Exception('Authorization failed: ' . $error);
            }

            // Obtener la conexión
            $connectionId = session('zoho_connection_id');
            $connection = Connection::findOrFail($connectionId);

            // Intercambiar código por tokens
            $this->zohoService->setConnection($connection);
            $tokenData = $this->zohoService->exchangeCodeForTokens($code);

            // Obtener información de organizaciones de Zoho
            try {
                $organizations = $this->zohoService->getOrganizations();

                // Si hay múltiples organizaciones, guardar la primera como default
                // TODO: Permitir al usuario seleccionar la organización
                if (!empty($organizations['organizations'])) {
                    $settings = $connection->settings;
                    $settings['organization_id'] = $organizations['organizations'][0]['organization_id'];
                    $settings['organization_name'] = $organizations['organizations'][0]['name'];
                    $connection->settings = $settings;
                    $connection->save();
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch Zoho organizations', [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Limpiar sesión
            session()->forget(['zoho_oauth_state', 'zoho_connection_id']);

            Log::info('Zoho OAuth callback successful', [
                'connection_id' => $connectionId,
                'user_id' => auth()->id(),
                'organization_count' => count($organizations['organizations'] ?? []),
            ]);

            return redirect()->route('admin.connections.show', $connectionId)
                ->with('success', 'Zoho connection authorized successfully!');

        } catch (\Exception $e) {
            // Limpiar sesión en caso de error
            session()->forget(['zoho_oauth_state', 'zoho_connection_id']);

            Log::error('Zoho OAuth callback failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ]);

            return redirect()->route('admin.connections.index')
                ->with('error', 'Authorization failed: ' . $e->getMessage());
        }
    }

    /**
     * Revocar tokens de una conexión
     *
     * @param string $connectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke($connectionId)
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            // Verificar permisos
            // TODO: Agregar verificación de permisos

            if ($connection->application !== 'zoho-self-client') {
                return response()->json(['error' => 'Invalid connection type'], 400);
            }

            $this->zohoService->setConnection($connection);
            $success = $this->zohoService->revokeTokens();

            if ($success) {
                Log::info('Zoho tokens revoked', [
                    'connection_id' => $connectionId,
                    'user_id' => auth()->id(),
                ]);

                return response()->json(['message' => 'Tokens revoked successfully']);
            } else {
                return response()->json(['error' => 'Failed to revoke tokens'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Zoho token revocation failed', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Probar la conexión (endpoint de debug)
     *
     * @param string $connectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function test($connectionId)
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            if ($connection->application !== 'zoho-self-client') {
                return response()->json(['error' => 'Invalid connection type'], 400);
            }

            $this->zohoService->setConnection($connection);

            // Probar con una llamada simple
            $organizations = $this->zohoService->getOrganizations();

            return response()->json([
                'status' => 'success',
                'message' => 'Connection test successful',
                'data' => [
                    'organization_count' => count($organizations['organizations'] ?? []),
                    'current_organization' => $connection->settings['organization_name'] ?? 'Unknown',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Zoho connection test failed', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de la conexión
     *
     * @param string $connectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function info($connectionId)
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            if ($connection->application !== 'zoho-self-client') {
                return response()->json(['error' => 'Invalid connection type'], 400);
            }

            $settings = $connection->settings;

            // No incluir información sensible
            $safeSettings = [
                'domain' => $settings['domain'] ?? 'unknown',
                'scope' => $settings['scope'] ?? 'unknown',
                'organization_name' => $settings['organization_name'] ?? 'Not set',
                'has_access_token' => !empty($settings['access_token']),
                'has_refresh_token' => !empty($settings['refresh_token']),
                'expires_at' => $settings['expires_at'] ?? null,
                'is_expired' => isset($settings['expires_at']) ?
                    \Carbon\Carbon::parse($settings['expires_at'])->isPast() : null,
            ];

            return response()->json([
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'application' => $connection->application,
                    'settings' => $safeSettings,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
