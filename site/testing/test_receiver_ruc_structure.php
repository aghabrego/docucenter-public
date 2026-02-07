<?php

// Test específico para verificar receiver.ruc structure con el mismo objeto del usuario

class TestReceiverRucStructure
{
    // Método copiado de AlanubeFormatterHelper
    private static function removeNullValues(array $array): array
    {
        return array_filter($array, function ($value) {
            if (is_array($value)) {
                return !empty(self::removeNullValues($value));
            }
            return $value !== null && $value !== '';
        });
    }

    public static function testUserReceiverRuc()
    {
        echo "=== TESTING RECEIVER RUC STRUCTURE - USER SCENARIO ===\n\n";

        // Datos exactos del receptor del usuario
        $userData = [
            "dGen" => [
                "gDatRec" => [
                    "iTipoRec" => "1",
                    "gRucRec" => [
                        "dTipoRuc" => "2",          // ¡STRING "2"!
                        "dRuc" => "1808755-1-706832",
                        "dDV" => "97"
                    ],
                    "dNombRec" => "CLINICA HOSPITALSAN JUAN DE DIOS",
                    "gUbiRec" => [
                        "dCodUbi" => "9-10-1",
                        "dCorreg" => "SANTIAGO, VERAGUAS",
                        "dDistr" => "VERAGUAS",
                        "dProv" => "VERAGUAS"
                    ],
                    "dCorElectRec" => "clinicasanjuandedios_@hotmail.com",
                    "cPaisRec" => "PA",
                    "dPaisRecDesc" => "Panamá"
                ]
            ]
        ];

        echo "Original gRucRec data from user:\n";
        echo json_encode($userData['dGen']['gDatRec']['gRucRec'], JSON_PRETTY_PRINT) . "\n\n";

        // Extraer RUC como en AlanubeFormatterHelper
        $gRucRec = $userData['dGen']['gDatRec']['gRucRec'] ?? [];
        $ruc = $gRucRec['dRuc'] ?? '';

        echo "Extracted RUC value: '" . $ruc . "'\n\n";

        // Simular el procesamiento de authorizedGroup
        echo "=== TESTING AUTHORIZED GROUP ===\n";
        $authorizedGroup = [
            'type' => (int)($gRucRec['dTipoRuc'] ?? 1),
            'ruc' => $ruc,
        ];

        echo "Before removeNullValues:\n";
        echo json_encode($authorizedGroup, JSON_PRETTY_PRINT) . "\n";

        $finalAuthorizedGroup = self::removeNullValues($authorizedGroup);

        echo "After removeNullValues:\n";
        echo json_encode($finalAuthorizedGroup, JSON_PRETTY_PRINT) . "\n";

        // Verificar propiedades
        if (isset($finalAuthorizedGroup['type'])) {
            echo "✅ AuthorizedGroup 'type' present: " . $finalAuthorizedGroup['type'] . "\n";
        } else {
            echo "❌ AuthorizedGroup 'type' MISSING\n";
        }

        echo "\n=== TESTING RUC STRUCTURE ===\n";

        // Simular el procesamiento del RUC structure
        $rucStructure = [
            'type' => (int)($gRucRec['dTipoRuc'] ?? 1),
            'ruc' => $ruc,
            'verificationDigit' => $gRucRec['dDVRuc'] ?? null,  // Note: dDVRuc vs dDV
        ];

        echo "Before removeNullValues:\n";
        echo json_encode($rucStructure, JSON_PRETTY_PRINT) . "\n";

        $finalRucStructure = self::removeNullValues($rucStructure);

        echo "After removeNullValues:\n";
        echo json_encode($finalRucStructure, JSON_PRETTY_PRINT) . "\n";

        // Verificar propiedades críticas
        if (isset($finalRucStructure['type'])) {
            echo "✅ RUC 'type' present: " . $finalRucStructure['type'] . "\n";
        } else {
            echo "❌ RUC 'type' MISSING - this causes PAC error!\n";
        }

        if (isset($finalRucStructure['ruc'])) {
            echo "✅ RUC 'ruc' present: " . $finalRucStructure['ruc'] . "\n";
        } else {
            echo "❌ RUC 'ruc' MISSING\n";
        }

        if (isset($finalRucStructure['verificationDigit'])) {
            echo "✅ RUC 'verificationDigit' present: " . $finalRucStructure['verificationDigit'] . "\n";
        } else {
            echo "⚠️  RUC 'verificationDigit' missing (this might be expected if dDVRuc not found)\n";
        }

        echo "\n=== POTENTIAL ISSUE ANALYSIS ===\n";

        // Verificar mapeo de campos
        echo "Field mapping check:\n";
        echo "- dTipoRuc: '" . ($gRucRec['dTipoRuc'] ?? 'MISSING') . "' -> type: " . (int)($gRucRec['dTipoRuc'] ?? 1) . "\n";
        echo "- dRuc: '" . ($gRucRec['dRuc'] ?? 'MISSING') . "' -> ruc: '" . $ruc . "'\n";
        echo "- dDV: '" . ($gRucRec['dDV'] ?? 'MISSING') . "' (from user data)\n";
        echo "- dDVRuc: '" . ($gRucRec['dDVRuc'] ?? 'MISSING') . "' (what formatter expects)\n";

        // Buscar el problema real
        echo "\n=== VERIFICATION DIGIT ISSUE ===\n";
        if (isset($gRucRec['dDV']) && !isset($gRucRec['dDVRuc'])) {
            echo "🔍 FOUND ISSUE: User data has 'dDV' but formatter expects 'dDVRuc'\n";

            // Corregir usando el campo correcto
            $correctedRucStructure = [
                'type' => (int)($gRucRec['dTipoRuc'] ?? 1),
                'ruc' => $ruc,
                'verificationDigit' => $gRucRec['dDV'] ?? null,  // Usar dDV en lugar de dDVRuc
            ];

            echo "Corrected structure using 'dDV':\n";
            echo json_encode($correctedRucStructure, JSON_PRETTY_PRINT) . "\n";

            $finalCorrectedRuc = self::removeNullValues($correctedRucStructure);
            echo "After removeNullValues (corrected):\n";
            echo json_encode($finalCorrectedRuc, JSON_PRETTY_PRINT) . "\n";

            if (isset($finalCorrectedRuc['type'])) {
                echo "✅ CORRECTED: RUC 'type' present: " . $finalCorrectedRuc['type'] . "\n";
            }
            if (isset($finalCorrectedRuc['verificationDigit'])) {
                echo "✅ CORRECTED: RUC 'verificationDigit' present: " . $finalCorrectedRuc['verificationDigit'] . "\n";
            }
        }

        echo "\n=== PAC VALIDATION RESULT ===\n";
        if (isset($finalRucStructure['type'])) {
            echo "🎉 PAC validation should PASS\n";
            echo "receiver.ruc has required 'type' property\n";
        } else {
            echo "❌ PAC validation will FAIL\n";
            echo "Error: instance.receiver.ruc requires property \"type\"\n";
        }

        echo "\n=== FINAL RECEIVER STRUCTURE ===\n";
        $receiverStructure = [
            'authorizedGroup' => $finalAuthorizedGroup,
            'ruc' => $finalRucStructure
        ];
        echo json_encode($receiverStructure, JSON_PRETTY_PRINT) . "\n";
    }
}

TestReceiverRucStructure::testUserReceiverRuc();
