<?php

// Test directo de formatTaxStructure sin dependencias de Laravel

class TestAlanubeFormatter
{
    /**
     * Método copiado de AlanubeFormatterHelper para testing
     */
    private static function formatTaxStructure(array $tax): array
    {
        $result = [];
        foreach ($tax as $key => $value) {
            // Preservar valores 0 para impuestos, solo remover nulls y strings vacíos
            if ($value !== null && $value !== '') {
                $result[$key] = $value;
            } elseif (($key === 'rate' || $key === 'amount') && is_numeric($value)) {
                // Para rate y amount, siempre incluir incluso si es 0
                $result[$key] = (float)$value;
            }
        }
        return $result;
    }

    public static function testTaxStructureWithZeroRate()
    {
        echo "=== TESTING TAX STRUCTURE WITH ZERO RATE ===\n\n";

        // Test case 1: Rate is 0 (numeric zero)
        $test1 = [
            'rate' => 0,
            'amount' => 0.0
        ];

        $result1 = self::formatTaxStructure($test1);
        echo "Test 1 - Numeric zero rate:\n";
        echo "Input: " . json_encode($test1) . "\n";
        echo "Output: " . json_encode($result1) . "\n";

        if (isset($result1['rate'])) {
            echo "✅ Rate preserved with value: " . $result1['rate'] . "\n";
        } else {
            echo "❌ Rate was removed - this causes PAC validation error!\n";
        }
        echo "\n";

        // Test case 2: Rate is "0" (string zero)
        $test2 = [
            'rate' => "0",
            'amount' => "0.000000"
        ];

        $result2 = self::formatTaxStructure($test2);
        echo "Test 2 - String zero rate:\n";
        echo "Input: " . json_encode($test2) . "\n";
        echo "Output: " . json_encode($result2) . "\n";

        if (isset($result2['rate'])) {
            echo "✅ Rate preserved with value: " . $result2['rate'] . "\n";
        } else {
            echo "❌ Rate was removed - this causes PAC validation error!\n";
        }
        echo "\n";

        // Test case 3: Rate is null
        $test3 = [
            'rate' => null,
            'amount' => 0
        ];

        $result3 = self::formatTaxStructure($test3);
        echo "Test 3 - Null rate:\n";
        echo "Input: " . json_encode($test3) . "\n";
        echo "Output: " . json_encode($result3) . "\n";

        if (isset($result3['rate'])) {
            echo "✅ Rate handled correctly with value: " . $result3['rate'] . "\n";
        } else {
            echo "❌ Rate was removed when null\n";
        }
        echo "\n";

        // Test case 4: Rate is empty string
        $test4 = [
            'rate' => '',
            'amount' => 0
        ];

        $result4 = self::formatTaxStructure($test4);
        echo "Test 4 - Empty string rate:\n";
        echo "Input: " . json_encode($test4) . "\n";
        echo "Output: " . json_encode($result4) . "\n";

        if (isset($result4['rate'])) {
            echo "✅ Rate handled correctly with value: " . $result4['rate'] . "\n";
        } else {
            echo "❌ Rate was removed when empty string\n";
        }
        echo "\n";

        echo "=== SIMULATING REAL FACTURA DATA ===\n\n";

        // Simular datos reales de la factura del usuario
        $realData = [
            'rate' => 0,  // dTasaITBMS from gITBMSItem
            'amount' => 0.0  // dValITBMS from gITBMSItem
        ];

        $realResult = self::formatTaxStructure($realData);
        echo "Real factura ITBMS data:\n";
        echo "Input: " . json_encode($realData) . "\n";
        echo "Output: " . json_encode($realResult) . "\n";

        if (isset($realResult['rate']) && isset($realResult['amount'])) {
            echo "✅ SUCCESS: Both rate and amount properties preserved\n";
            echo "✅ PAC validation should pass - no 'requires property rate' error\n";
        } else {
            echo "❌ FAILURE: Missing properties would cause PAC validation error\n";
            if (!isset($realResult['rate'])) {
                echo "   - Missing 'rate' property\n";
            }
            if (!isset($realResult['amount'])) {
                echo "   - Missing 'amount' property\n";
            }
        }

        echo "\n=== CONCLUSION ===\n";
        echo "The formatTaxStructure method now preserves zero values for 'rate' and 'amount' properties,\n";
        echo "which should resolve the PAC validation error: 'instance.items[0].itbms requires property \"rate\"'\n";
    }
}

// Ejecutar el test
TestAlanubeFormatter::testTaxStructureWithZeroRate();
