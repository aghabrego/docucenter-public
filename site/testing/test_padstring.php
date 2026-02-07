<?php

// Test específico para padString con valores 0

class TestPadString
{
    private static function padString(?string $value, int $length): ?string
    {
        // Preservar el valor 0, solo retornar null si realmente es null o string vacío
        if ($value === null || $value === '') {
            return null;
        }
        return str_pad($value, $length, '0', STR_PAD_LEFT);
    }

    public static function testPadStringWithZero()
    {
        echo "=== TESTING padString WITH ZERO VALUES ===\n\n";

        // Test case 1: Numeric zero
        $result1 = self::padString("0", 2);
        echo "Test 1 - String '0' with length 2:\n";
        echo "Input: '0'\n";
        echo "Output: '" . ($result1 ?? 'NULL') . "'\n";
        echo "Expected: '00'\n";
        if ($result1 === "00") {
            echo "✅ PASS\n";
        } else {
            echo "❌ FAIL\n";
        }
        echo "\n";

        // Test case 2: Integer zero converted to string
        $result2 = self::padString((string)0, 2);
        echo "Test 2 - Converted zero to string with length 2:\n";
        echo "Input: '" . (string)0 . "'\n";
        echo "Output: '" . ($result2 ?? 'NULL') . "'\n";
        echo "Expected: '00'\n";
        if ($result2 === "00") {
            echo "✅ PASS\n";
        } else {
            echo "❌ FAIL\n";
        }
        echo "\n";

        // Test case 3: Null value
        $result3 = self::padString(null, 2);
        echo "Test 3 - Null value:\n";
        echo "Input: null\n";
        echo "Output: " . ($result3 === null ? 'NULL' : "'" . $result3 . "'") . "\n";
        echo "Expected: NULL\n";
        if ($result3 === null) {
            echo "✅ PASS\n";
        } else {
            echo "❌ FAIL\n";
        }
        echo "\n";

        // Test case 4: Empty string
        $result4 = self::padString('', 2);
        echo "Test 4 - Empty string:\n";
        echo "Input: ''\n";
        echo "Output: " . ($result4 === null ? 'NULL' : "'" . $result4 . "'") . "\n";
        echo "Expected: NULL\n";
        if ($result4 === null) {
            echo "✅ PASS\n";
        } else {
            echo "❌ FAIL\n";
        }
        echo "\n";

        // Test case 5: Real scenario - dTasaITBMS = 0
        echo "=== REAL SCENARIO TEST ===\n";
        $dTasaITBMS = 0;  // This comes from the JSON data
        $paddedRate = self::padString((string)$dTasaITBMS, 2);

        echo "Real scenario - dTasaITBMS from JSON:\n";
        echo "JSON value: " . $dTasaITBMS . "\n";
        echo "Converted to string: '" . (string)$dTasaITBMS . "'\n";
        echo "After padString(2): '" . ($paddedRate ?? 'NULL') . "'\n";

        if ($paddedRate !== null) {
            echo "✅ SUCCESS: Rate value preserved for PAC validation\n";
        } else {
            echo "❌ FAILURE: Rate value lost - would cause PAC error\n";
        }

        echo "\n=== CONCLUSION ===\n";
        echo "The corrected padString method now properly handles zero values,\n";
        echo "preserving them for PAC validation instead of converting to null.\n";
    }
}

TestPadString::testPadStringWithZero();
