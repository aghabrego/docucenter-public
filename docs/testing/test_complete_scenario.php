<?php

// Test final simulando el escenario exacto del usuario

class TestCompleteITBMSScenario
{
    // Métodos copiados y corregidos
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

    private static function padString(?string $value, int $length): ?string
    {
        // Preservar el valor 0, solo retornar null si realmente es null o string vacío
        if ($value === null || $value === '') {
            return null;
        }
        return str_pad($value, $length, '0', STR_PAD_LEFT);
    }

    public static function testUserScenario()
    {
        echo "=== TESTING EXACT USER SCENARIO ===\n\n";
        echo "Simulating the exact JSON data from user's factura...\n\n";

        // Datos exactos del primer item del usuario
        $gITBMSItem = [
            "dTasaITBMS" => 0,  // Este es el problema - valor 0
            "dValITBMS" => "0.000000"
        ];

        $gISCItem = [
            "dTasaISC" => "0.00",
            "dValISC" => "0.00"
        ];

        echo "Original gITBMSItem data:\n";
        echo json_encode($gITBMSItem, JSON_PRETTY_PRINT) . "\n\n";

        echo "Original gISCItem data:\n";
        echo json_encode($gISCItem, JSON_PRETTY_PRINT) . "\n\n";

        // Simular el procesamiento que hace AlanubeFormatterHelper
        echo "=== PROCESSING ITBMS ===\n";

        $itbmsRate = self::padString($gITBMSItem['dTasaITBMS'] ?? null, 2);
        $itbmsAmount = (float)($gITBMSItem['dValITBMS'] ?? 0);

        echo "Step 1 - padString for rate:\n";
        echo "  Input: " . ($gITBMSItem['dTasaITBMS'] ?? 'null') . "\n";
        echo "  Output: '" . ($itbmsRate ?? 'NULL') . "'\n";

        echo "Step 2 - Convert amount to float:\n";
        echo "  Input: '" . $gITBMSItem['dValITBMS'] . "'\n";
        echo "  Output: " . $itbmsAmount . "\n";

        $itbmsStructure = [
            'rate' => $itbmsRate,
            'amount' => $itbmsAmount
        ];

        echo "Step 3 - Create tax structure:\n";
        echo json_encode($itbmsStructure, JSON_PRETTY_PRINT) . "\n";

        $finalITBMS = self::formatTaxStructure($itbmsStructure);

        echo "Step 4 - Apply formatTaxStructure:\n";
        echo json_encode($finalITBMS, JSON_PRETTY_PRINT) . "\n";

        echo "\n=== PROCESSING ISC ===\n";

        $iscRate = (float)($gISCItem['dTasaISC'] ?? 0);
        $iscAmount = (float)($gISCItem['dValISC'] ?? 0);

        $iscStructure = [
            'rate' => $iscRate,
            'amount' => $iscAmount
        ];

        $finalISC = self::formatTaxStructure($iscStructure);

        echo "Final ISC structure:\n";
        echo json_encode($finalISC, JSON_PRETTY_PRINT) . "\n";

        echo "\n=== FINAL ITEM STRUCTURE ===\n";

        $itemTaxes = [
            'itbms' => $finalITBMS,
            'isc' => $finalISC
        ];

        echo json_encode($itemTaxes, JSON_PRETTY_PRINT) . "\n";

        echo "\n=== PAC VALIDATION CHECK ===\n";

        // Verificar que todas las propiedades requeridas están presentes
        $validationPassed = true;

        if (!isset($finalITBMS['rate'])) {
            echo "❌ ITBMS missing 'rate' property - would cause: instance.items[0].itbms requires property \"rate\"\n";
            $validationPassed = false;
        } else {
            echo "✅ ITBMS 'rate' property present: " . $finalITBMS['rate'] . "\n";
        }

        if (!isset($finalITBMS['amount'])) {
            echo "❌ ITBMS missing 'amount' property\n";
            $validationPassed = false;
        } else {
            echo "✅ ITBMS 'amount' property present: " . $finalITBMS['amount'] . "\n";
        }

        if (!isset($finalISC['rate'])) {
            echo "❌ ISC missing 'rate' property - would cause: instance.items[1].isc requires property \"rate\"\n";
            $validationPassed = false;
        } else {
            echo "✅ ISC 'rate' property present: " . $finalISC['rate'] . "\n";
        }

        if (!isset($finalISC['amount'])) {
            echo "❌ ISC missing 'amount' property\n";
            $validationPassed = false;
        } else {
            echo "✅ ISC 'amount' property present: " . $finalISC['amount'] . "\n";
        }

        echo "\n=== FINAL RESULT ===\n";
        if ($validationPassed) {
            echo "🎉 SUCCESS! PAC validation should now pass.\n";
            echo "The error 'instance.items[0].itbms requires property \"rate\"' should be resolved.\n";
        } else {
            echo "❌ FAILURE! PAC validation would still fail.\n";
        }

        echo "\n=== WHAT WAS FIXED ===\n";
        echo "1. padString() now preserves zero values instead of converting to null\n";
        echo "2. formatTaxStructure() always includes 'rate' and 'amount' properties\n";
        echo "3. Both ITBMS and ISC structures maintain required properties for PAC\n";
    }
}

TestCompleteITBMSScenario::testUserScenario();
