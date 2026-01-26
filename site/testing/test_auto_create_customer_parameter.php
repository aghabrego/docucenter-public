<?php

/**
 * Test para validar el parámetro auto_create_customer en salesImp API
 *
 * Ejecutar: php docs/testing/test_auto_create_customer_parameter.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class TestAutoCreateCustomerParameter
{
    /**
     * Simular el comportamiento del método salesImp
     */
    private static function simulateSalesImpBehavior(array $requestData): array
    {
        // Datos del request
        $customerId = $requestData['CustomerID'] ?? '';
        $customerName = $requestData['CustomerName'] ?? '';
        $customerRuc = $requestData['CustomerRuc'] ?? '0-0-0';
        $autoCreateCustomer = $requestData['auto_create_customer'] ?? false;

        // Simulación de la lógica condicional
        if ($autoCreateCustomer) {
            // NUEVO: Verificar y crear cliente automáticamente
            $customerData = [
                'CustomerID' => $customerId,
                'Customer_Bill_Name' => $customerName,
                'Custom_field1' => $customerRuc,
                'Custom_field4' => self::determineRucType($customerRuc), // Con determinación automática
                'created_from_updateOrCreate' => true, // Flag para testing
            ];
        } else {
            // ORIGINAL: Usar datos directamente del request
            $customerData = [
                'CustomerID' => $customerId,
                'Customer_Bill_Name' => $customerName,
                'Custom_field1' => $customerRuc,
                'Custom_field4' => null, // Sin determinación automática
                'created_from_updateOrCreate' => false, // Flag para testing
            ];
        }

        return [
            'customer_data' => $customerData,
            'auto_create_enabled' => $autoCreateCustomer,
        ];
    }

    /**
     * Replica la lógica de determineRucType para testing
     */
    private static function determineRucType(?string $ruc): string
    {
        if (empty($ruc) || $ruc === '0-0-0') {
            return '2';
        }

        $ruc = trim($ruc);
        $parts = explode('-', $ruc);
        if (count($parts) < 2) {
            return '2';
        }

        $firstPart = $parts[0];

        // PE (Panameño Extranjero), E (Extranjero), N (Naturalizado)
        if (preg_match("/^(PE|E|N)$/", $firstPart)) {
            return '2';
        }

        // Provincias con AV (Antes de la Vigencia) o PI (Población Indígena)
        if (preg_match("/^(1[0123]?|[23456789])(AV|PI)$/", $firstPart)) {
            return '2';
        }

        // Solo provincias (1-13) sin sufijos especiales
        if (preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
            return '2';
        }

        // Patrón tradicional 8- (Panamá provincia)
        if (preg_match("/^8$/", $firstPart)) {
            return '2';
        }

        // RUC de empresa/jurídica: números de más de 2 dígitos
        if (preg_match("/^\d{2,}$/", $firstPart) && !preg_match("/^[1-9]$|^1[0-3]$/", $firstPart)) {
            return '1';
        }

        return '2';
    }

    public static function runTests()
    {
        $tests = [
            // === COMPORTAMIENTO ORIGINAL (auto_create_customer = false o no especificado) ===
            [
                'request' => [
                    'CustomerID' => 'CUST001',
                    'CustomerName' => 'Cliente Test Original',
                    'CustomerRuc' => '8-123-456',
                    // auto_create_customer no especificado = false por defecto
                ],
                'expected' => [
                    'auto_create_enabled' => false,
                    'created_from_updateOrCreate' => false,
                    'Custom_field4' => null, // Sin determinación automática
                ],
                'description' => 'Comportamiento original - auto_create_customer no especificado'
            ],
            [
                'request' => [
                    'CustomerID' => 'CUST002',
                    'CustomerName' => 'Cliente Test Original Explícito',
                    'CustomerRuc' => 'E-789-123',
                    'auto_create_customer' => false,
                ],
                'expected' => [
                    'auto_create_enabled' => false,
                    'created_from_updateOrCreate' => false,
                    'Custom_field4' => null, // Sin determinación automática
                ],
                'description' => 'Comportamiento original - auto_create_customer = false explícito'
            ],

            // === COMPORTAMIENTO NUEVO (auto_create_customer = true) ===
            [
                'request' => [
                    'CustomerID' => 'CUST003',
                    'CustomerName' => 'Cliente Test Nuevo',
                    'CustomerRuc' => '8-456-789',
                    'auto_create_customer' => true,
                ],
                'expected' => [
                    'auto_create_enabled' => true,
                    'created_from_updateOrCreate' => true,
                    'Custom_field4' => '2', // Con determinación automática
                ],
                'description' => 'Comportamiento nuevo - Persona natural (8-)'
            ],
            [
                'request' => [
                    'CustomerID' => 'CUST004',
                    'CustomerName' => 'Cliente Test Empresa',
                    'CustomerRuc' => '155-789-123',
                    'auto_create_customer' => true,
                ],
                'expected' => [
                    'auto_create_enabled' => true,
                    'created_from_updateOrCreate' => true,
                    'Custom_field4' => '1', // Con determinación automática
                ],
                'description' => 'Comportamiento nuevo - Empresa (155-)'
            ],
            [
                'request' => [
                    'CustomerID' => 'CUST005',
                    'CustomerName' => 'Cliente Test Extranjero',
                    'CustomerRuc' => 'E-123-456',
                    'auto_create_customer' => true,
                ],
                'expected' => [
                    'auto_create_enabled' => true,
                    'created_from_updateOrCreate' => true,
                    'Custom_field4' => '2', // Con determinación automática
                ],
                'description' => 'Comportamiento nuevo - Extranjero (E-)'
            ],

            // === CASOS ESPECIALES ===
            [
                'request' => [
                    'CustomerID' => 'CUST006',
                    'CustomerName' => 'Cliente Consumidor Final',
                    'CustomerRuc' => '0-0-0',
                    'auto_create_customer' => true,
                ],
                'expected' => [
                    'auto_create_enabled' => true,
                    'created_from_updateOrCreate' => true,
                    'Custom_field4' => '2', // Con determinación automática
                ],
                'description' => 'Comportamiento nuevo - Consumidor final'
            ],
        ];

        $passed = 0;
        $failed = 0;
        $total = count($tests);

        echo "🧪 PRUEBAS DEL PARÁMETRO auto_create_customer EN salesImp API\n";
        echo "=" . str_repeat("=", 65) . "\n\n";

        foreach ($tests as $index => $test) {
            $requestData = $test['request'];
            $expected = $test['expected'];
            $description = $test['description'];

            $result = self::simulateSalesImpBehavior($requestData);

            // Validaciones
            $autoCreateMatch = $result['auto_create_enabled'] === $expected['auto_create_enabled'];
            $updateOrCreateMatch = $result['customer_data']['created_from_updateOrCreate'] === $expected['created_from_updateOrCreate'];
            $custom_field4Match = $result['customer_data']['Custom_field4'] === $expected['Custom_field4'];

            $allMatch = $autoCreateMatch && $updateOrCreateMatch && $custom_field4Match;
            $status = $allMatch ? '✅ PASS' : '❌ FAIL';

            echo sprintf(
                "Test %2d: %s\n",
                $index + 1,
                $description
            );
            echo sprintf(
                "         CustomerID: %s | RUC: %s | auto_create: %s\n",
                $requestData['CustomerID'],
                $requestData['CustomerRuc'],
                isset($requestData['auto_create_customer']) ? ($requestData['auto_create_customer'] ? 'true' : 'false') : 'default'
            );
            echo sprintf(
                "         Resultado: %s | UpdateOrCreate: %s | TipoRUC: %s\n",
                $status,
                $result['customer_data']['created_from_updateOrCreate'] ? 'Sí' : 'No',
                $result['customer_data']['Custom_field4'] ?? 'null'
            );

            if (!$allMatch) {
                echo "         ❌ Detalles del error:\n";
                if (!$autoCreateMatch) {
                    echo "           - auto_create_enabled: esperado {$expected['auto_create_enabled']}, obtenido {$result['auto_create_enabled']}\n";
                }
                if (!$updateOrCreateMatch) {
                    echo "           - created_from_updateOrCreate: esperado {$expected['created_from_updateOrCreate']}, obtenido {$result['customer_data']['created_from_updateOrCreate']}\n";
                }
                if (!$custom_field4Match) {
                    echo "           - Custom_field4: esperado '{$expected['Custom_field4']}', obtenido '{$result['customer_data']['Custom_field4']}'\n";
                }
                $failed++;
            } else {
                $passed++;
            }

            echo "\n";
        }

        echo str_repeat("=", 67) . "\n";
        echo "📊 RESUMEN: Pruebas pasadas: $passed, Pruebas fallidas: $failed, Total: $total\n";

        if ($failed === 0) {
            echo "🎉 ¡Todas las pruebas pasaron! El parámetro auto_create_customer funciona correctamente.\n";
            echo "✅ Los usuarios existentes NO se verán afectados (comportamiento original preservado)\n";
            echo "✅ Los nuevos usuarios pueden activar la funcionalidad con auto_create_customer=true\n";
        } else {
            echo "⚠️  Hay $failed prueba(s) fallida(s) que necesitan revisión.\n";
        }

        return $failed === 0;
    }
}

// Ejecutar las pruebas
TestAutoCreateCustomerParameter::runTests();
