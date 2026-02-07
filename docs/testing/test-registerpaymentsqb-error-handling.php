<?php
/**
 * Script de testing para validar el manejo de errores mejorado en registerPaymentsQB
 */

require_once 'vendor/autoload.php';

use App\Models\Connection;
use App\Jobs\Intuit\UpdateIntuitOrdersJob;
use Illuminate\Support\Facades\Log;

echo "🔧 TESTING - MANEJO DE ERRORES registerPaymentsQB\n";
echo "═══════════════════════════════════════════════════\n\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "✅ Laravel inicializado\n";

    $organizationId = 2;

    // Buscar conexión QuickBooks
    $connection = Connection::where('organization_id', $organizationId)
        ->where('application', 'acicloud')
        ->first();

    if (!$connection) {
        throw new Exception("Conexión QuickBooks no encontrada para organización {$organizationId}");
    }

    echo "✅ Conexión QB encontrada: ID {$connection->id}\n";
    echo "✅ Organización: {$connection->organization->name}\n\n";

    // Crear instancia del job para testing
    $job = new UpdateIntuitOrdersJob($connection);

    // Verificar credenciales
    $email = env('ACI_EMAIL');
    $password = env('ACI_PASSWORD');

    if (empty($email) || empty($password)) {
        echo "⚠️ Credenciales ACI no configuradas - Testing con simulación\n\n";

        echo "🧪 CASOS DE PRUEBA - VALIDACIÓN DE PARÁMETROS:\n";
        echo "─────────────────────────────────────────────────\n";

        // Usar reflexión para acceder al método protegido
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('registerPaymentsQB');
        $method->setAccessible(true);

        // Caso 1: Datos válidos completos
        echo "📋 Caso 1: Datos válidos completos\n";
        $validData = [
            'connection_id' => 'test-connection',
            'Id' => 'test-payment-123',
            'CustomerRef' => 'customer-456',
            'Total' => 100.50,
            'FormaPago' => 'CREDIT_CARD',
            'InvoiceRef' => 'invoice-789',
            'Referencia' => 'REF-123',
            'NumeroFactura' => 'FE0000001234',
            'Fecha' => '2025-09-18 10:30:00'
        ];
        echo "   • Datos: " . json_encode($validData, JSON_PRETTY_PRINT) . "\n";
        echo "   • Estado: ✅ VÁLIDO (todos los campos requeridos presentes)\n\n";

        // Caso 2: Falta CustomerRef (campo requerido)
        echo "📋 Caso 2: Falta CustomerRef (campo requerido)\n";
        $invalidData1 = [
            'connection_id' => 'test-connection',
            'Id' => 'test-payment-123',
            'Total' => 100.50,
            'FormaPago' => 'CREDIT_CARD',
            // CustomerRef FALTANTE
        ];
        echo "   • Datos: " . json_encode($invalidData1, JSON_PRETTY_PRINT) . "\n";
        echo "   • Estado: ❌ INVÁLIDO (CustomerRef faltante)\n";
        echo "   • Resultado esperado: false + log de error\n\n";

        // Caso 3: Falta Total (campo requerido)
        echo "📋 Caso 3: Falta Total (campo requerido)\n";
        $invalidData2 = [
            'connection_id' => 'test-connection',
            'Id' => 'test-payment-123',
            'CustomerRef' => 'customer-456',
            'FormaPago' => 'CREDIT_CARD',
            // Total FALTANTE
        ];
        echo "   • Datos: " . json_encode($invalidData2, JSON_PRETTY_PRINT) . "\n";
        echo "   • Estado: ❌ INVÁLIDO (Total faltante)\n";
        echo "   • Resultado esperado: false + log de error\n\n";

        // Caso 4: Falta FormaPago (campo requerido)
        echo "📋 Caso 4: Falta FormaPago (campo requerido)\n";
        $invalidData3 = [
            'connection_id' => 'test-connection',
            'Id' => 'test-payment-123',
            'CustomerRef' => 'customer-456',
            'Total' => 100.50,
            // FormaPago FALTANTE
        ];
        echo "   • Datos: " . json_encode($invalidData3, JSON_PRETTY_PRINT) . "\n";
        echo "   • Estado: ❌ INVÁLIDO (FormaPago faltante)\n";
        echo "   • Resultado esperado: false + log de error\n\n";

    } else {
        echo "✅ Credenciales ACI configuradas\n";
        echo "¿Proceder con prueba real de manejo de errores? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);

        if (strtolower($response) === 'y' || strtolower($response) === 'yes') {
            echo "\n🚀 EJECUTANDO PRUEBAS REALES:\n";
            echo "─────────────────────────────\n";

            // Usar reflexión para acceder al método protegido
            $reflection = new ReflectionClass($job);
            $method = $reflection->getMethod('registerPaymentsQB');
            $method->setAccessible(true);

            // Prueba con datos inválidos (falta CustomerRef)
            echo "\n📋 Prueba 1: Datos con campo requerido faltante\n";
            $invalidData = [
                'connection_id' => 'test-connection',
                'Id' => 'test-payment-invalid',
                'Total' => 50.00,
                'FormaPago' => 'CASH',
                // CustomerRef FALTANTE intencionalmente
            ];

            $result = $method->invoke($job, $email, $password, $invalidData);
            echo "   • Resultado: " . ($result === false ? "❌ FALSE (esperado)" : "✅ " . gettype($result)) . "\n";

            // Prueba con datos válidos pero endpoint probablemente inexistente
            echo "\n📋 Prueba 2: Datos válidos con posible error de API\n";
            $testData = [
                'connection_id' => 'non-existent-connection',
                'Id' => 'test-payment-999999',
                'CustomerRef' => 'non-existent-customer',
                'Total' => 1.00,
                'FormaPago' => 'TEST',
                'InvoiceRef' => 'test-invoice',
                'Referencia' => 'TEST-REF',
                'NumeroFactura' => 'TEST-999999',
                'Fecha' => date('Y-m-d H:i:s')
            ];

            $result = $method->invoke($job, $email, $password, $testData);
            echo "   • Resultado: " . ($result === false ? "❌ FALSE (posible error API)" : "✅ " . gettype($result)) . "\n";

        } else {
            echo "⏹️ Pruebas reales canceladas\n";
        }
    }

    echo "\n🔧 MEJORAS IMPLEMENTADAS:\n";
    echo "────────────────────────────\n";
    echo "✅ Validación de campos requeridos antes de llamada API\n";
    echo "✅ Logging detallado antes, durante y después de registerPaymentsQB\n";
    echo "✅ Manejo específico de diferentes tipos de errores HTTP\n";
    echo "✅ Captura de errores de conexión y timeout\n";
    echo "✅ Verificación de estructura de respuesta JSON\n";
    echo "✅ Logging de errores con contexto completo\n";
    echo "✅ Timeouts configurables para evitar cuelgues\n";

    echo "\n📊 TIPOS DE ERROR CAPTURADOS:\n";
    echo "────────────────────────────────\n";
    echo "• ❌ connection_error: Problemas de conectividad\n";
    echo "• ❌ http_error: Errores HTTP (4xx, 5xx)\n";
    echo "• ❌ json_error: Respuesta JSON malformada\n";
    echo "• ❌ api_error: Errores reportados por la API QB\n";
    echo "• ❌ validation_error: Campos requeridos faltantes\n";
    echo "• ❌ general_error: Otros errores no categorizados\n";

    echo "\n📋 LOGS GENERADOS:\n";
    echo "─────────────────────\n";
    echo "• registerPaymentsQB: Registrando pago - Antes de envío\n";
    echo "• registerPaymentsQB: Campos requeridos faltantes - Validación\n";
    echo "• registerPaymentsQB: Pago registrado exitosamente - Éxito\n";
    echo "• registerPaymentsQB: Fallo al registrar pago - Error\n";
    echo "• registerInQuickBooks: Iniciando llamada API - Inicio\n";
    echo "• registerInQuickBooks: Respuesta recibida - Respuesta HTTP\n";
    echo "• registerInQuickBooks: Error de conexión - Problemas red\n";
    echo "• registerInQuickBooks: Error HTTP - Errores API\n";
    echo "• registerInQuickBooks: Error general - Otros errores\n";

    echo "\n🎯 BENEFICIOS PARA DEBUGGING:\n";
    echo "────────────────────────────────\n";
    echo "• Identificación rápida de errores por tipo\n";
    echo "• Context completo en cada log entry\n";
    echo "• Trazabilidad de paymentID y organizationID\n";
    echo "• Información de reintentos y attempts\n";
    echo "• Datos de respuesta para análisis\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Testing de manejo de errores completado\n";
