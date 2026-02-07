<?php
/**
 * Script de Prueba - Validación APIs Customer Receipt y Credit Memo
 *
 * Este script prueba las validaciones de ParentTransactionId para:
 * - customer_receipt_imp
 * - customer_credit_memo
 *
 * Ubicación: docs/testing/test-customer-apis-validation.php
 * Propósito: Verificar validación de integridad de datos con tabla sales_header_imp
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Requests\CustomerReceiptImpRequest;
use App\Http\Requests\CustomerCreditMemoImpRequest;
use Illuminate\Validation\ValidationException;

class CustomerAPIValidationTester
{
    public function testCustomerReceiptValidation()
    {
        echo "=== TESTING Customer Receipt Imp Validation ===\n\n";

        // Test 1: Datos válidos (asumiendo que existe ID 1 en sales_header_imp)
        echo "Test 1: Datos válidos con ParentTransactionId existente\n";
        $validData = [
            'CustomerID' => 'CUST001',
            'ReceiptNumber' => 'REC001',
            'ParentTransactionId' => 1, // Debe existir en sales_header_imp
            'Details' => [
                ['item' => 'Test Item', 'amount' => 100.00]
            ]
        ];

        $this->validateData(CustomerReceiptImpRequest::class, $validData);

        // Test 2: ParentTransactionId inexistente
        echo "\nTest 2: ParentTransactionId inexistente\n";
        $invalidData = [
            'CustomerID' => 'CUST001',
            'ReceiptNumber' => 'REC002',
            'ParentTransactionId' => 999999, // ID que no debe existir
            'Details' => [
                ['item' => 'Test Item', 'amount' => 100.00]
            ]
        ];

        $this->validateData(CustomerReceiptImpRequest::class, $invalidData);

        // Test 3: Sin ParentTransactionId (nullable)
        echo "\nTest 3: Sin ParentTransactionId (campo nullable)\n";
        $nullData = [
            'CustomerID' => 'CUST001',
            'ReceiptNumber' => 'REC003',
            'Details' => [
                ['item' => 'Test Item', 'amount' => 100.00]
            ]
        ];

        $this->validateData(CustomerReceiptImpRequest::class, $nullData);
    }

    public function testCustomerCreditMemoValidation()
    {
        echo "\n=== TESTING Customer Credit Memo Imp Validation ===\n\n";

        // Test 1: Datos válidos
        echo "Test 1: Datos válidos con ParentTransactionId existente\n";
        $validData = [
            'CustomerID' => 'CUST001',
            'CreditNumber' => 'CR001',
            'ParentTransactionId' => 1, // Debe existir en sales_header_imp
            'Details' => [
                ['item' => 'Credit Item', 'amount' => 50.00]
            ]
        ];

        $this->validateData(CustomerCreditMemoImpRequest::class, $validData);

        // Test 2: ParentTransactionId inexistente
        echo "\nTest 2: ParentTransactionId inexistente\n";
        $invalidData = [
            'CustomerID' => 'CUST001',
            'CreditNumber' => 'CR002',
            'ParentTransactionId' => 999999, // ID que no debe existir
            'Details' => [
                ['item' => 'Credit Item', 'amount' => 50.00]
            ]
        ];

        $this->validateData(CustomerCreditMemoImpRequest::class, $invalidData);
    }

    private function validateData($requestClass, $data)
    {
        try {
            // Crear instancia del Request
            $request = new $requestClass();

            // Simular datos del request
            $request->replace($data);

            // Obtener reglas de validación
            $rules = $request->rules();
            $messages = method_exists($request, 'messages') ? $request->messages() : [];

            // Crear validator manualmente para testing
            $validator = \Illuminate\Support\Facades\Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                echo "❌ VALIDACIÓN FALLÓ:\n";
                foreach ($validator->errors()->all() as $error) {
                    echo "   • $error\n";
                }
            } else {
                echo "✅ VALIDACIÓN EXITOSA\n";
                echo "   • Todos los campos son válidos\n";
            }

        } catch (\Exception $e) {
            echo "❌ ERROR EN VALIDACIÓN:\n";
            echo "   • " . $e->getMessage() . "\n";
        }
    }

    public function checkSalesHeaderTable()
    {
        echo "\n=== VERIFICANDO TABLA sales_header_imp ===\n\n";

        try {
            $count = \Illuminate\Support\Facades\DB::table('sales_header_imp')->count();
            echo "✅ Tabla sales_header_imp encontrada\n";
            echo "   • Total de registros: $count\n";

            if ($count > 0) {
                $firstId = \Illuminate\Support\Facades\DB::table('sales_header_imp')->first()->ID ?? null;
                echo "   • Primer ID disponible: $firstId\n";

                $lastId = \Illuminate\Support\Facades\DB::table('sales_header_imp')->orderBy('ID', 'desc')->first()->ID ?? null;
                echo "   • Último ID disponible: $lastId\n";
            }

        } catch (\Exception $e) {
            echo "❌ ERROR AL ACCEDER A TABLA:\n";
            echo "   • " . $e->getMessage() . "\n";
            echo "   • Verifica que la tabla sales_header_imp exista\n";
        }
    }

    public function runAllTests()
    {
        echo "INICIANDO PRUEBAS DE VALIDACIÓN PARA APIs CUSTOMER\n";
        echo str_repeat("=", 60) . "\n\n";

        $this->checkSalesHeaderTable();
        $this->testCustomerReceiptValidation();
        $this->testCustomerCreditMemoValidation();

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "PRUEBAS COMPLETADAS\n\n";

        echo "NOTAS IMPORTANTES:\n";
        echo "• Asegúrate de que la tabla sales_header_imp tenga datos de prueba\n";
        echo "• Los IDs 999999 se usan para probar validación de inexistencia\n";
        echo "• Los campos nullable pueden omitirse sin error\n";
        echo "• Los mensajes personalizados deben aparecer en español\n";
    }
}

// Ejecutar si se llama directamente
if (basename($_SERVER['PHP_SELF']) === 'test-customer-apis-validation.php') {

    // Verificar si estamos en contexto Laravel
    if (!class_exists('Illuminate\Foundation\Application')) {
        echo "❌ ERROR: Este script debe ejecutarse en contexto Laravel\n";
        echo "Usa: php artisan tinker y luego incluye este archivo\n";
        exit(1);
    }

    $tester = new CustomerAPIValidationTester();
    $tester->runAllTests();
}
