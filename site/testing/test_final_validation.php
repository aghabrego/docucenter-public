<?php

// Test final combinando ambos problemas: ITBMS rate y receiver.ruc type

class TestFinalValidation
{
    // Métodos copiados y corregidos
    private static function formatTaxStructure(array $tax): array
    {
        $result = [];
        foreach ($tax as $key => $value) {
            if ($value !== null && $value !== '') {
                $result[$key] = $value;
            } elseif (($key === 'rate' || $key === 'amount') && is_numeric($value)) {
                $result[$key] = (float)$value;
            }
        }
        return $result;
    }

    private static function formatRucStructure(array $ruc): array
    {
        $result = [];
        foreach ($ruc as $key => $value) {
            if ($value !== null && $value !== '') {
                $result[$key] = $value;
            } elseif ($key === 'type' && is_numeric($value)) {
                $result[$key] = (int)$value;
            }
        }

        if (!isset($result['type'])) {
            $result['type'] = 1;
        }

        return $result;
    }

    private static function padString(?string $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return str_pad($value, $length, '0', STR_PAD_LEFT);
    }

    public static function testBothErrors()
    {
        echo "=== TESTING BOTH PAC VALIDATION ERRORS ===\n\n";

        // Datos exactos del usuario
        $userData = [
            'dGen' => [
                'gDatRec' => [
                    'gRucRec' => [
                        'dTipoRuc' => '2',
                        'dRuc' => '1808755-1-706832',
                        'dDV' => '97'
                    ]
                ]
            ],
            'gItem' => [
                [
                    'gITBMSItem' => [
                        'dTasaITBMS' => 0,  // Problema original
                        'dValITBMS' => '0.000000'
                    ]
                ]
            ]
        ];

        echo "User's original data:\n";
        echo json_encode($userData, JSON_PRETTY_PRINT) . "\n\n";

        // Procesar RUC del receptor
        $gDatRec = $userData['dGen']['gDatRec'];
        $ruc = $gDatRec['gRucRec']['dRuc'] ?? null;

        $receiverRuc = self::formatRucStructure([
            'type' => (int)($gDatRec['gRucRec']['dTipoRuc'] ?? 1),
            'ruc' => $ruc,
            'verificationDigit' => $gDatRec['gRucRec']['dDV'] ?? null,
        ]);

        // Procesar ITBMS
        $gITBMSItem = $userData['gItem'][0]['gITBMSItem'];
        $itbmsStructure = self::formatTaxStructure([
            'rate' => self::padString((string)($gITBMSItem['dTasaITBMS'] ?? 0), 2),
            'amount' => (float)($gITBMSItem['dValITBMS'] ?? 0),
        ]);

        echo "=== RESULTS ===\n";
        echo "Receiver RUC: " . json_encode($receiverRuc) . "\n";
        echo "ITBMS: " . json_encode($itbmsStructure) . "\n\n";

        echo "=== PAC VALIDATION ===\n";

        // Validar receiver.ruc.type
        if (isset($receiverRuc['type'])) {
            echo "✅ receiver.ruc.type present: " . $receiverRuc['type'] . "\n";
        } else {
            echo "❌ receiver.ruc.type MISSING\n";
        }

        // Validar itbms.rate
        if (isset($itbmsStructure['rate'])) {
            echo "✅ items[0].itbms.rate present: " . $itbmsStructure['rate'] . "\n";
        } else {
            echo "❌ items[0].itbms.rate MISSING\n";
        }

        echo "\n✅ Both PAC validation errors should now be resolved!\n";
    }
}

TestFinalValidation::testBothErrors();
