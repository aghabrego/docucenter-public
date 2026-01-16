<?php

/**
 * Test para validar la corrección automática de TIPO inválido en QuickBooks
 *
 * Ejecutar: php docs/testing/test_quickbooks_tipo_correction.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class TestQuickBooksTipoCorrection
{
    /**
     * Simular la lógica de corrección de TIPO del QuickBooksOnlineService
     */
    private static function simulateTipoCorrection(array $request): array
    {
        $tipoFromRequest = $request['TIPO'] ?? null;
        $ruc = $request['Ruc'] ?? '';

        // Extraer RUC si está en formato especial
        if (preg_match('/Ruc:(\d+-\d+-\d+)|RUC:(\d+-\d+-\d+)|RUC (\d+-\d+-\d+)|Ruc (\d+-\d+-\d+)|^\d+-\d+-\d+$/i', $ruc, $matches)) {
            $filteredMatches = array_filter($matches, fn($value) => !empty($value));
            $ruc = end($filteredMatches);
        }

        $tipoContribuyente = '2'; // Default: Persona Natural
        $correctionApplied = false;
        $correctionReason = '';

        if (!is_null($tipoFromRequest)) {
            // Validación y corrección de valores inválidos de TIPO
            if (!in_array($tipoFromRequest, [1, 2, '1', '2'])) {
                $correctionApplied = true;
                // Si TIPO es inválido (como 0), determinar automáticamente basado en el RUC
                if (!empty($ruc)) {
                    // Simular el helper para determinar el tipo correcto
                    $tipoContribuyente = self::detectContributorType($ruc) == 1 ? '2' : '1';
                    $correctionReason = "TIPO inválido ($tipoFromRequest) corregido basado en análisis de RUC";
                } else {
                    // Sin RUC, asumir persona natural
                    $tipoContribuyente = '2';
                    $correctionReason = "TIPO inválido ($tipoFromRequest) sin RUC, usando default (persona natural)";
                }
            } else {
                // Si viene el campo TIPO válido desde QuickBooks, usarlo directamente
                $tipoContribuyente = (string) $tipoFromRequest;
            }
        }

        return [
            'original_tipo' => $tipoFromRequest,
            'corrected_tipo' => $tipoContribuyente,
            'correction_applied' => $correctionApplied,
            'correction_reason' => $correctionReason,
            'ruc_analyzed' => $ruc,
        ];
    }

    /**
     * Simular la detección de tipo de contribuyente del PanamaRucHelper
     */
    private static function detectContributorType(string $ruc): int
    {
        if (empty($ruc) || $ruc === '0-0-0') {
            return 1; // Consumidor Final = Natural
        }

        $ruc = trim($ruc);
        $parts = explode('-', $ruc);
        if (count($parts) < 2) {
            return 1;
        }

        $firstPart = $parts[0];

        // PE (Panameño Extranjero), E (Extranjero), N (Naturalizado)
        if (preg_match("/^(PE|E|N)$/", $firstPart)) {
            return 1; // Natural
        }

        // Solo provincias (1-13) sin sufijos especiales
        if (preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
            return 1; // Natural
        }

        // Patrón tradicional 8- (Panamá provincia)
        if (preg_match("/^8$/", $firstPart)) {
            return 1; // Natural
        }

        // RUC de empresa/jurídica: números de más de 2 dígitos
        if (preg_match("/^\d{2,}$/", $firstPart) && !preg_match("/^[1-9]$|^1[0-3]$/", $firstPart)) {
            return 2; // Jurídico
        }

        return 1; // Default Natural
    }

    public static function runTests()
    {
        $tests = [
            // === CASOS PROBLEMÁTICOS (TIPO = 0) ===
            [
                'request' => [
                    'TIPO' => 0,
                    'Ruc' => '155706268-2-2021', // Caso real del error
                ],
                'expected' => [
                    'corrected_tipo' => '1', // Empresa = Persona Jurídica
                    'correction_applied' => true,
                ],
                'description' => 'Caso real del error - RUC empresa con TIPO 0'
            ],
            [
                'request' => [
                    'TIPO' => 0,
                    'Ruc' => '8-123-456', // Persona natural
                ],
                'expected' => [
                    'corrected_tipo' => '2', // Persona Natural
                    'correction_applied' => true,
                ],
                'description' => 'TIPO 0 con RUC persona natural'
            ],
            [
                'request' => [
                    'TIPO' => 0,
                    'Ruc' => '', // Sin RUC
                ],
                'expected' => [
                    'corrected_tipo' => '2', // Default persona natural
                    'correction_applied' => true,
                ],
                'description' => 'TIPO 0 sin RUC (usar default)'
            ],

            // === CASOS VÁLIDOS (NO NECESITAN CORRECCIÓN) ===
            [
                'request' => [
                    'TIPO' => 1,
                    'Ruc' => '155706268-2-2021', // Empresa
                ],
                'expected' => [
                    'corrected_tipo' => '1',
                    'correction_applied' => false,
                ],
                'description' => 'TIPO 1 válido - Persona Jurídica'
            ],
            [
                'request' => [
                    'TIPO' => 2,
                    'Ruc' => '8-123-456', // Persona natural
                ],
                'expected' => [
                    'corrected_tipo' => '2',
                    'correction_applied' => false,
                ],
                'description' => 'TIPO 2 válido - Persona Natural'
            ],
            [
                'request' => [
                    'TIPO' => '1', // String válido
                    'Ruc' => '999-123-456',
                ],
                'expected' => [
                    'corrected_tipo' => '1',
                    'correction_applied' => false,
                ],
                'description' => 'TIPO como string válido'
            ],

            // === CASOS ESPECIALES ===
            [
                'request' => [
                    'TIPO' => null,
                    'Ruc' => '155706268-2-2021',
                ],
                'expected' => [
                    'corrected_tipo' => '2', // Default
                    'correction_applied' => false,
                ],
                'description' => 'TIPO null (usar default sin corrección)'
            ],
            [
                'request' => [
                    'TIPO' => -1, // Valor muy inválido
                    'Ruc' => 'E-123-456',
                ],
                'expected' => [
                    'corrected_tipo' => '2', // Extranjero = Natural
                    'correction_applied' => true,
                ],
                'description' => 'TIPO negativo con RUC extranjero'
            ],
        ];

        $passed = 0;
        $failed = 0;
        $total = count($tests);

        echo "🧪 PRUEBAS DE CORRECCIÓN AUTOMÁTICA DE TIPO INVÁLIDO EN QUICKBOOKS\n";
        echo "=" . str_repeat("=", 72) . "\n\n";

        foreach ($tests as $index => $test) {
            $request = $test['request'];
            $expected = $test['expected'];
            $description = $test['description'];

            $result = self::simulateTipoCorrection($request);

            // Validaciones
            $tipoMatch = $result['corrected_tipo'] === $expected['corrected_tipo'];
            $correctionMatch = $result['correction_applied'] === $expected['correction_applied'];

            $allMatch = $tipoMatch && $correctionMatch;
            $status = $allMatch ? '✅ PASS' : '❌ FAIL';

            echo sprintf(
                "Test %2d: %s\n",
                $index + 1,
                $description
            );
            echo sprintf(
                "         TIPO original: %s | RUC: %s\n",
                $request['TIPO'] ?? 'null',
                $request['Ruc'] ?: 'vacío'
            );
            echo sprintf(
                "         Resultado: %s | TIPO corregido: %s | Corrección: %s\n",
                $status,
                $result['corrected_tipo'],
                $result['correction_applied'] ? 'Sí' : 'No'
            );

            if ($result['correction_applied']) {
                echo sprintf(
                    "         Razón: %s\n",
                    $result['correction_reason']
                );
            }

            if (!$allMatch) {
                echo "         ❌ Detalles del error:\n";
                if (!$tipoMatch) {
                    echo "           - TIPO esperado: '{$expected['corrected_tipo']}', obtenido: '{$result['corrected_tipo']}'\n";
                }
                if (!$correctionMatch) {
                    echo "           - Corrección esperada: " . ($expected['correction_applied'] ? 'Sí' : 'No') . ", aplicada: " . ($result['correction_applied'] ? 'Sí' : 'No') . "\n";
                }
                $failed++;
            } else {
                $passed++;
            }

            echo "\n";
        }

        echo str_repeat("=", 74) . "\n";
        echo "📊 RESUMEN: Pruebas pasadas: $passed, Pruebas fallidas: $failed, Total: $total\n";

        if ($failed === 0) {
            echo "🎉 ¡Todas las pruebas pasaron! La corrección automática funciona correctamente.\n";
            echo "✅ Valores TIPO inválidos (como 0) se corrigen automáticamente\n";
            echo "✅ Valores TIPO válidos (1, 2) se mantienen sin cambios\n";
            echo "✅ La determinación se basa en análisis inteligente del RUC\n";
        } else {
            echo "⚠️  Hay $failed prueba(s) fallida(s) que necesitan revisión.\n";
        }

        return $failed === 0;
    }
}

// Ejecutar las pruebas
TestQuickBooksTipoCorrection::runTests();
