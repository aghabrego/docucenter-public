<?php

/**
 * Script de prueba para verificar la determinación del tipo de RUC
 * basado en patrones panameños
 */

class RucTypeTester
{
    /**
     * Determina el tipo de RUC basado en el patrón del RUC panameño
     *
     * @param string $ruc El RUC a analizar
     * @return string El tipo de RUC ('1' para jurídica, '2' para natural/consumidor final)
     */
    private function determineRucType(?string $ruc): string
    {
        // Si no hay RUC o está vacío, asumir consumidor final
        if (empty($ruc) || $ruc === '0-0-0') {
            return '2'; // Persona natural/Consumidor final
        }

        // Limpiar espacios y normalizar
        $ruc = trim($ruc);

        // Patrón para RUC de persona natural: comienza con 8- o E- (extranjeros)
        if (preg_match('/^[8E]-/', $ruc)) {
            return '2'; // Persona natural
        }

        // Patrón para RUC de empresa/jurídica: otros patrones numéricos
        // Como 155-123-456, 20-123-456, etc.
        if (preg_match('/^\d+-\d+-\d+$/', $ruc)) {
            return '1'; // Persona jurídica/Empresa
        }

        // Por defecto, asumir persona natural si no coincide con ningún patrón
        return '2';
    }

    public function runTests()
    {
        echo "=== PRUEBAS DE DETERMINACIÓN DE TIPO RUC ===\n\n";

        $testCases = [
            // Casos de persona natural
            ['ruc' => '8-123-456', 'expected' => '2', 'description' => 'RUC persona natural (8-)'],
            ['ruc' => '8-987-654', 'expected' => '2', 'description' => 'RUC persona natural (8-)'],
            ['ruc' => 'E-123-456', 'expected' => '2', 'description' => 'RUC extranjero (E-)'],

            // Casos de persona jurídica/empresa
            ['ruc' => '155-123-456', 'expected' => '1', 'description' => 'RUC empresa (155-)'],
            ['ruc' => '20-123-456', 'expected' => '1', 'description' => 'RUC empresa (20-)'],
            ['ruc' => '1-123-456', 'expected' => '1', 'description' => 'RUC empresa (1-)'],
            ['ruc' => '999-888-777', 'expected' => '1', 'description' => 'RUC empresa (999-)'],

            // Casos especiales
            ['ruc' => '0-0-0', 'expected' => '2', 'description' => 'Consumidor final'],
            ['ruc' => '', 'expected' => '2', 'description' => 'RUC vacío'],
            ['ruc' => null, 'expected' => '2', 'description' => 'RUC null'],
            ['ruc' => '  8-123-456  ', 'expected' => '2', 'description' => 'RUC con espacios'],

            // Casos edge
            ['ruc' => 'invalid-format', 'expected' => '2', 'description' => 'Formato inválido (default a natural)'],
            ['ruc' => '123', 'expected' => '2', 'description' => 'Formato incompleto'],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($testCases as $i => $case) {
            $result = $this->determineRucType($case['ruc']);
            $status = $result === $case['expected'] ? '✅ PASS' : '❌ FAIL';

            echo sprintf(
                "Test %d: %s\n  RUC: '%s'\n  Expected: '%s', Got: '%s' %s\n\n",
                $i + 1,
                $case['description'],
                $case['ruc'] ?? 'NULL',
                $case['expected'],
                $result,
                $status
            );

            if ($result === $case['expected']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo "=== RESUMEN ===\n";
        echo "Pruebas pasadas: $passed\n";
        echo "Pruebas fallidas: $failed\n";
        echo "Total: " . ($passed + $failed) . "\n\n";

        if ($failed === 0) {
            echo "🎉 ¡Todas las pruebas pasaron!\n";
        } else {
            echo "⚠️  Hay pruebas que fallaron. Revisar implementación.\n";
        }

        return $failed === 0;
    }
}

// Ejecutar las pruebas
$tester = new RucTypeTester();
$success = $tester->runTests();

exit($success ? 0 : 1);
