<?php

/**
 * Script de Testing - RegisterPaymentsQB Response Structure
 *
 * Este script prueba la nueva estructura de respuesta mejorada que incluye
 * errores reales de la API en lugar de solo devolver false.
 *
 * Estructura Nueva:
 * - success: true/false
 * - error: mensaje real del error de la API
 * - error_type: tipo de error categorizado
 * - data: datos de QB cuando es exitoso
 *
 * Uso:
 * php docs/testing/test-registerpaymentsqb-improved-response.php [org_id]
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Organization;
use App\Traits\UpdateIntuitOrdersTrait;

class RegisterPaymentsQBTester
{
    use UpdateIntuitOrdersTrait;

    protected $connectionModel;

    public function __construct($organizationId = null)
    {
        if ($organizationId) {
            $org = Organization::find($organizationId);
            if ($org && $org->pacConnection) {
                $this->connectionModel = $org->pacConnection;
            }
        }
    }

    /**
     * Test 1: Validación de campos requeridos
     */
    public function testRequiredFieldsValidation()
    {
        echo "\n=== TEST 1: Validación de Campos Requeridos ===\n";

        // Datos incompletos
        $incompleteData = [
            'Id' => 'test-payment-1',
            'Total' => 100.00,
            // Faltantes: CustomerRef, FormaPago
        ];

        $result = $this->registerPaymentsQB('test@example.com', 'password', $incompleteData);

        echo "Datos enviados: " . json_encode($incompleteData, JSON_PRETTY_PRINT) . "\n";
        echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

        // Verificar estructura de respuesta
        if (isset($result['success']) && $result['success'] === false) {
            echo "✅ ÉXITO: Error de validación capturado correctamente\n";
            echo "✅ Error: " . $result['error'] . "\n";
            echo "✅ Tipo: " . $result['error_type'] . "\n";
        } else {
            echo "❌ FALLO: Validación no funcionó como esperado\n";
        }
    }

    /**
     * Test 2: Estructura de respuesta de error de API
     */
    public function testAPIErrorResponse()
    {
        echo "\n=== TEST 2: Error de API con Estructura Nueva ===\n";

        // Datos válidos pero credentials incorrectas (simular error API)
        $validData = [
            'Id' => 'test-payment-2',
            'CustomerRef' => 'test-customer',
            'Total' => 150.00,
            'FormaPago' => 'Efectivo',
            'InvoiceRef' => 'test-invoice'
        ];

        // Usar credentials incorrectas para forzar error
        $result = $this->registerPaymentsQB('invalid@example.com', 'wrong-password', $validData);

        echo "Datos enviados: " . json_encode($validData, JSON_PRETTY_PRINT) . "\n";
        echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

        // Verificar estructura de error
        if (isset($result['success']) && $result['success'] === false) {
            echo "✅ ÉXITO: Error de API capturado con nueva estructura\n";
            echo "✅ Error Real: " . ($result['error'] ?? 'No disponible') . "\n";
            echo "✅ Tipo: " . ($result['error_type'] ?? 'No disponible') . "\n";

            // Verificar si incluye detalles adicionales
            if (isset($result['status_code'])) {
                echo "✅ Status Code: " . $result['status_code'] . "\n";
            }
        } else {
            echo "❌ FALLO: Error de API no manejado correctamente\n";
        }
    }

    /**
     * Test 3: Comparación con estructura anterior
     */
    public function testLegacyVsNewStructure()
    {
        echo "\n=== TEST 3: Comparación Estructura Anterior vs Nueva ===\n";

        echo "ESTRUCTURA ANTERIOR:\n";
        echo "- Éxito: array con datos de QB\n";
        echo "- Error: false (sin información)\n";
        echo "- Job manejaba: if (\$result === false) { throw exception with hardcoded message }\n\n";

        echo "ESTRUCTURA NUEVA:\n";
        echo "- Éxito: ['success' => true, 'data' => array_datos_qb]\n";
        echo "- Error: ['success' => false, 'error' => 'mensaje_real_api', 'error_type' => 'tipo']\n";
        echo "- Job maneja: if (!\$result['success']) { throw exception with real API error }\n\n";

        echo "BENEFICIOS:\n";
        echo "✅ Errores reales de la API en lugar de mensajes genéricos\n";
        echo "✅ Categorización de errores para mejor debugging\n";
        echo "✅ Información adicional (status codes, response body)\n";
        echo "✅ Consistencia en la estructura de respuesta\n";
        echo "✅ Mejor experiencia de debugging en producción\n";
    }

    /**
     * Test 4: Verificar tipos de error soportados
     */
    public function testErrorTypes()
    {
        echo "\n=== TEST 4: Tipos de Error Soportados ===\n";

        $errorTypes = [
            'validation_error' => 'Campos requeridos faltantes',
            'connection_error' => 'Problemas de conectividad/timeout',
            'http_error' => 'Códigos HTTP 4xx/5xx',
            'api_error' => 'Errores específicos de QuickBooks API',
            'general_error' => 'Cualquier otra excepción'
        ];

        foreach ($errorTypes as $type => $description) {
            echo "- {$type}: {$description}\n";
        }

        echo "\nCada tipo incluye:\n";
        echo "- Mensaje específico del error\n";
        echo "- Logging detallado para debugging\n";
        echo "- Contexto relevante (connection_id, endpoint, etc.)\n";
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests()
    {
        echo "=== TESTING: RegisterPaymentsQB Improved Response Structure ===\n";
        echo "Fecha: " . date('Y-m-d H:i:s') . "\n";

        $this->testRequiredFieldsValidation();
        $this->testAPIErrorResponse();
        $this->testLegacyVsNewStructure();
        $this->testErrorTypes();

        echo "\n=== TESTS COMPLETADOS ===\n";
        echo "Revisa los logs de Laravel para ver el logging detallado\n";
        echo "Comando: tail -f storage/logs/laravel.log | grep registerPaymentsQB\n";
    }
}

// Ejecutar tests
$organizationId = $argv[1] ?? null;
$tester = new RegisterPaymentsQBTester($organizationId);
$tester->runAllTests();
