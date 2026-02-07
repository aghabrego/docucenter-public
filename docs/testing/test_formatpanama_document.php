<?php

// Test específico para formatPanamaDocument con valores 0

class TestFormatPanamaDocument
{
    private static function removeNullValues(array $array): array
    {
        return array_filter($array, function ($value) {
            if (is_array($value)) {
                return !empty(self::removeNullValues($value));
            }
            return $value !== null && $value !== '';
        });
    }

    private static function formatPanamaDocument(array $gDatRec): array
    {
        $gRucRec = $gDatRec['gRucRec'] ?? [];

        return self::removeNullValues([
            'type' => $gRucRec['dTipoRuc'] ?? null,
            'number' => $gRucRec['dRuc'] ?? null,
            'verificationDigit' => $gRucRec['dDVRuc'] ?? null,
        ]);
    }

    private static function formatPanamaDocumentFixed(array $gDatRec): array
    {
        $gRucRec = $gDatRec['gRucRec'] ?? [];

        $result = [
            'type' => (int)($gRucRec['dTipoRuc'] ?? 1), // Garantizar valor por defecto
            'number' => $gRucRec['dRuc'] ?? null,
            'verificationDigit' => $gRucRec['dDV'] ?? $gRucRec['dDVRuc'] ?? null, // Buscar ambos campos
        ];

        // Aplicar removeNullValues pero preservar 'type'
        $filtered = self::removeNullValues($result);

        // Garantizar que 'type' esté presente incluso si fue removido
        if (!isset($filtered['type'])) {
            $filtered['type'] = (int)($gRucRec['dTipoRuc'] ?? 1);
        }

        return $filtered;
    }

    public static function testFormatPanamaDocumentIssues()
    {
        echo "=== TESTING formatPanamaDocument ISSUES ===\n\n";

        // Test case 1: dTipoRuc = 0 (problema principal)
        $testData1 = [
            'gRucRec' => [
                'dTipoRuc' => 0,  // ¡PROBLEMA! Será removido por removeNullValues
                'dRuc' => '123456789',
                'dDV' => '1'  // Campo correcto según datos del usuario
            ]
        ];

        echo "Test 1 - dTipoRuc = 0 (actual implementation):\n";
        echo "Input: " . json_encode($testData1, JSON_PRETTY_PRINT) . "\n";

        $result1 = self::formatPanamaDocument($testData1);
        echo "Output: " . json_encode($result1, JSON_PRETTY_PRINT) . "\n";

        if (isset($result1['type'])) {
            echo "✅ Type property present: " . $result1['type'] . "\n";
        } else {
            echo "❌ Type property MISSING - would cause PAC validation error!\n";
        }

        if (isset($result1['verificationDigit'])) {
            echo "✅ VerificationDigit property present: " . $result1['verificationDigit'] . "\n";
        } else {
            echo "❌ VerificationDigit property MISSING (looking for dDVRuc instead of dDV)\n";
        }
        echo "\n";

        // Test case 2: Con corrección aplicada
        echo "Test 2 - Fixed implementation:\n";
        $result2 = self::formatPanamaDocumentFixed($testData1);
        echo "Output: " . json_encode($result2, JSON_PRETTY_PRINT) . "\n";

        if (isset($result2['type'])) {
            echo "✅ Type property present: " . $result2['type'] . "\n";
        } else {
            echo "❌ Type property still missing\n";
        }

        if (isset($result2['verificationDigit'])) {
            echo "✅ VerificationDigit property present: " . $result2['verificationDigit'] . "\n";
        } else {
            echo "❌ VerificationDigit property still missing\n";
        }
        echo "\n";

        // Test case 3: Datos reales del usuario
        $realUserData = [
            'gRucRec' => [
                'dTipoRuc' => '2',  // String en lugar de int
                'dRuc' => '1808755-1-706832',
                'dDV' => '97'  // Nombre correcto del campo
            ]
        ];

        echo "Test 3 - Real user data (current implementation):\n";
        echo "Input: " . json_encode($realUserData, JSON_PRETTY_PRINT) . "\n";

        $result3 = self::formatPanamaDocument($realUserData);
        echo "Output: " . json_encode($result3, JSON_PRETTY_PRINT) . "\n";

        if (isset($result3['verificationDigit'])) {
            echo "✅ VerificationDigit found: " . $result3['verificationDigit'] . "\n";
        } else {
            echo "❌ VerificationDigit MISSING - looking for 'dDVRuc' but data has 'dDV'\n";
        }
        echo "\n";

        echo "Test 4 - Real user data (fixed implementation):\n";
        $result4 = self::formatPanamaDocumentFixed($realUserData);
        echo "Output: " . json_encode($result4, JSON_PRETTY_PRINT) . "\n";

        if (isset($result4['verificationDigit'])) {
            echo "✅ VerificationDigit found: " . $result4['verificationDigit'] . "\n";
        } else {
            echo "❌ VerificationDigit still missing\n";
        }

        echo "\n=== PROBLEMS IDENTIFIED ===\n";
        echo "1. removeNullValues() removes 'type' when dTipoRuc is 0\n";
        echo "2. Looking for 'dDVRuc' but user data has 'dDV'\n";
        echo "3. No default value for 'type' when missing\n";
        echo "4. formatPanamaDocument is called but main formatForPanama doesn't use it\n";

        echo "\n=== CONCLUSION ===\n";
        echo "formatPanamaDocument has the same issues as the main formatting logic.\n";
        echo "However, it appears that formatForPanama() doesn't actually call formatPanamaDocument(),\n";
        echo "so the main issue is still in the formatForPanama() method's receiver.ruc structure.\n";
    }
}

TestFormatPanamaDocument::testFormatPanamaDocumentIssues();
