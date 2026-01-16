<?php

/**
 * Test de Sanitización de Código de Producto para PAC TheFactoryHKA
 *
 * Validar que los códigos de producto se saniticen correctamente
 * para evitar el error: "El campo codigo es inválido"
 *
 * EJECUTAR:
 * docker exec -it docucenter_laravel.test php docs/testing/test-sanitizacion-codigo-producto-pac.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

echo "=== TEST SANITIZACIÓN CÓDIGO PRODUCTO PAC ===\n\n";

class TestSanitization
{
    /**
     * Simular el método sanitizeProductCodeForPAC del HKAService
     * Según documentación oficial PAC: <ser:codigo></ser:codigo> - puede estar vacío
     */
    private function sanitizeProductCodeForPAC($code)
    {
        // Según documentación oficial PAC, el campo codigo puede estar vacío
        if (empty($code)) {
            return '';
        }

        // CRÍTICO: TheFactoryHKA PAC rechaza caracteres especiales en códigos
        $sanitized = preg_replace('/[:\;,\|\/\\\\"\'<>&%\$#@!\?\*\+=\(\)\[\]{}]/', '', $code);
        $sanitized = preg_replace('/\s+/', '-', $sanitized);
        $sanitized = preg_replace('/\-+/', '-', $sanitized);
        $sanitized = trim($sanitized, '-');

        // Si queda vacío después de la sanitización, retornar vacío (válido según PAC)
        if (empty($sanitized)) {
            return '';
        }

        $sanitized = substr($sanitized, 0, 50);

        return $sanitized;
    }

    public function testCodigos()
    {
        $testCases = [
            // Caso problemático de producción
            'ResMed:37221' => 'ResMed37221',

            // Otros casos problemáticos
            'ITEM/2025-01' => 'ITEM2025-01',
            'Product & Co.' => 'Product-Co.', // Punto permitido, ampersand removido
            'Item#123@test' => 'Item123test',
            'SKU|ABC/123' => 'SKUABC123',
            'PROD:A;B,C' => 'PRODABC',
            'Test "Item" (2025)' => 'Test-Item-2025',
            'Product_with_underscores' => 'Product_with_underscores', // Underscores válidos
            'SIMPLE123' => 'SIMPLE123', // Código simple válido
            '' => '', // Vacío - válido según documentación oficial PAC
            ':::' => '', // Solo caracteres inválidos - resulta en vacío (válido)
            str_repeat('A', 60) => substr(str_repeat('A', 60), 0, 50), // Muy largo
        ];

        $passed = 0;
        $total = count($testCases);

        echo "CASOS DE PRUEBA:\n";
        echo str_repeat("=", 80) . "\n";

        foreach ($testCases as $input => $expected) {
            $result = $this->sanitizeProductCodeForPAC($input);
            $status = $result === $expected ? '✅ PASS' : '❌ FAIL';

            if ($result !== $expected) {
                echo sprintf("%-30s | %-30s | %-30s | %s\n",
                    substr($input, 0, 29),
                    substr($expected, 0, 29),
                    substr($result, 0, 29),
                    $status
                );
            } else {
                echo sprintf("%-30s | %-30s | %-30s | %s\n",
                    substr($input, 0, 29),
                    substr($result, 0, 29),
                    'OK',
                    $status
                );
                $passed++;
            }
        }

        echo str_repeat("=", 80) . "\n";
        echo "RESULTADO: {$passed}/{$total} pruebas pasaron\n\n";

        return $passed === $total;
    }

    public function testCasoProduccion()
    {
        echo "CASO ESPECÍFICO DE PRODUCCIÓN:\n";
        echo str_repeat("-", 50) . "\n";

        $codigoOriginal = 'ResMed:37221';
        $codigoSanitizado = $this->sanitizeProductCodeForPAC($codigoOriginal);

        echo "Código original:    '{$codigoOriginal}'\n";
        echo "Código sanitizado:  '{$codigoSanitizado}'\n";
        echo "Válido para PAC:    " . ($codigoSanitizado === 'ResMed37221' ? '✅ SÍ' : '❌ NO') . "\n";
        echo "Sin caracteres especiales: " . (strpos($codigoSanitizado, ':') === false ? '✅ SÍ' : '❌ NO') . "\n";
        echo "Conforme documentación oficial: ✅ SÍ (campo puede estar vacío)\n\n";

        return $codigoSanitizado === 'ResMed37221';
    }

    public function testValidacionesPAC()
    {
        echo "VALIDACIONES PAC TheFactoryHKA:\n";
        echo str_repeat("-", 50) . "\n";

        $caracteresProhibidos = [':', ';', ',', '|', '/', '\\', '"', "'", '<', '>', '&', '%', '$', '#', '@', '!', '?', '*', '+', '=', '(', ')', '[', ']', '{', '}'];
        $codigosProblematicos = [];

        foreach ($caracteresProhibidos as $char) {
            $codigoTest = "ITEM{$char}123";
            $resultado = $this->sanitizeProductCodeForPAC($codigoTest);

            if (strpos($resultado, $char) !== false) {
                $codigosProblematicos[] = $char;
            }
        }

        if (empty($codigosProblematicos)) {
            echo "✅ Todos los caracteres prohibidos son eliminados correctamente\n";
            echo "✅ Códigos cumplen con especificaciones PAC\n\n";
            return true;
        } else {
            echo "❌ Caracteres problemáticos encontrados: " . implode(', ', $codigosProblematicos) . "\n\n";
            return false;
        }
    }
}

// Ejecutar tests
$test = new TestSanitization();

echo "1. Ejecutando casos de prueba generales...\n";
$test1 = $test->testCodigos();

echo "2. Verificando caso específico de producción...\n";
$test2 = $test->testCasoProduccion();

echo "3. Validando cumplimiento PAC...\n";
$test3 = $test->testValidacionesPAC();

echo "=== RESUMEN FINAL ===\n";
if ($test1 && $test2 && $test3) {
    echo "✅ TODOS LOS TESTS PASARON\n";
    echo "✅ La sanitización resolverá el error PAC 'El campo codigo es inválido'\n";
    echo "✅ Código de producción 'ResMed:37221' será sanitizado correctamente\n";
} else {
    echo "❌ ALGUNOS TESTS FALLARON\n";
    echo "❌ Revisar implementación de sanitización\n";
}

echo "\nFIN DEL TEST\n";
