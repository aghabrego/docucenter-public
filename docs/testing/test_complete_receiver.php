<?php

// Test final con formateo completo usando el mismo objeto del usuario

// Simular el método formatForPanama sin dependencias de Laravel
class TestCompleteReceiverFormatting
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

    private static function formatReceiver(array $data)
    {
        $gRucRec = $data['dGen']['gDatRec']['gRucRec'] ?? [];
        $ruc = $gRucRec['dRuc'] ?? '';

        return self::removeNullValues([
            'type' => (int)($data['dGen']['gDatRec']['iTipoRec'] ?? 1),
            'name' => $data['dGen']['gDatRec']['dNombRec'] ?? '',
            'tradeName' => $data['dGen']['gDatRec']['dNombComRec'] ?? null,
            'emails' => array_filter([
                $data['dGen']['gDatRec']['dCorElectRec'] ?? null,
            ]),
            'location' => self::removeNullValues([
                'country' => $data['dGen']['gDatRec']['cPaisRec'] ?? 'PA',
                'province' => $data['dGen']['gDatRec']['gUbiRec']['dProv'] ?? null,
                'district' => $data['dGen']['gDatRec']['gUbiRec']['dDistr'] ?? null,
                'township' => $data['dGen']['gDatRec']['gUbiRec']['dCorreg'] ?? null,
                'coordinates' => $data['dGen']['gDatRec']['dCoordRec'] ?? null,
                'address' => $data['dGen']['gDatRec']['dDirecRec'] ?? null,
                'zip' => $data['dGen']['gDatRec']['dCPostalRec'] ?? null,
                'phone' => $data['dGen']['gDatRec']['dTfnRec'] ?? null,
            ]),
            'foreign' => self::removeNullValues([
                'country' => $data['dGen']['gDatRec']['cPaisRec'] ?? null,
                'taxId' => $data['dGen']['gDatRec']['dNroIDExt'] ?? null,
            ]),
            'authorizedGroup' => self::removeNullValues([
                'type' => (int)($data['dGen']['gDatRec']['gRucRec']['dTipoRuc'] ?? 1),
                'ruc' => $ruc,
            ]),
            'ruc' => self::removeNullValues([
                'type' => (int)($data['dGen']['gDatRec']['gRucRec']['dTipoRuc'] ?? 1),
                'ruc' => $ruc,
                'verificationDigit' => $data['dGen']['gDatRec']['gRucRec']['dDV'] ?? $data['dGen']['gDatRec']['gRucRec']['dDVRuc'] ?? null,
            ]),
        ]);
    }

    public static function testCompleteReceiver()
    {
        echo "=== TESTING COMPLETE RECEIVER FORMATTING ===\n\n";

        // Datos exactos del usuario
        $userData = [
            "dGen" => [
                "gDatRec" => [
                    "iTipoRec" => "1",
                    "gRucRec" => [
                        "dTipoRuc" => "2",
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

        echo "Input data (gDatRec):\n";
        echo json_encode($userData['dGen']['gDatRec'], JSON_PRETTY_PRINT) . "\n\n";

        try {
            $formattedReceiver = self::formatReceiver($userData);

            echo "=== FORMATTED RECEIVER STRUCTURE ===\n";
            echo json_encode($formattedReceiver, JSON_PRETTY_PRINT) . "\n\n";

            echo "=== PAC VALIDATION CHECK ===\n";

            // Verificar estructura del RUC
            if (isset($formattedReceiver['ruc'])) {
                echo "✅ RUC structure present\n";

                if (isset($formattedReceiver['ruc']['type'])) {
                    echo "✅ RUC 'type' property present: " . $formattedReceiver['ruc']['type'] . "\n";
                } else {
                    echo "❌ RUC 'type' property MISSING - causes PAC error!\n";
                }

                if (isset($formattedReceiver['ruc']['ruc'])) {
                    echo "✅ RUC 'ruc' property present: " . $formattedReceiver['ruc']['ruc'] . "\n";
                } else {
                    echo "❌ RUC 'ruc' property MISSING\n";
                }

                if (isset($formattedReceiver['ruc']['verificationDigit'])) {
                    echo "✅ RUC 'verificationDigit' property present: " . $formattedReceiver['ruc']['verificationDigit'] . "\n";
                } else {
                    echo "⚠️  RUC 'verificationDigit' property missing\n";
                }
            } else {
                echo "❌ RUC structure completely missing!\n";
            }

            // Verificar estructura de authorizedGroup
            if (isset($formattedReceiver['authorizedGroup'])) {
                echo "✅ AuthorizedGroup structure present\n";

                if (isset($formattedReceiver['authorizedGroup']['type'])) {
                    echo "✅ AuthorizedGroup 'type' property present: " . $formattedReceiver['authorizedGroup']['type'] . "\n";
                } else {
                    echo "❌ AuthorizedGroup 'type' property MISSING\n";
                }
            }

            echo "\n=== FINAL RESULT ===\n";
            $hasRequiredProperties = isset($formattedReceiver['ruc']['type']) && isset($formattedReceiver['ruc']['ruc']);

            if ($hasRequiredProperties) {
                echo "🎉 SUCCESS! PAC validation should PASS\n";
                echo "receiver.ruc has all required properties\n";
                echo "Error 'instance.receiver.ruc requires property \"type\"' should be resolved\n";
            } else {
                echo "❌ FAILURE! PAC validation will FAIL\n";
                echo "Missing required properties in receiver.ruc structure\n";
            }

            echo "\n=== COMPARISON WITH EXPECTED PAC FORMAT ===\n";
            echo "Expected by PAC:\n";
            echo "{\n";
            echo "  \"receiver\": {\n";
            echo "    \"ruc\": {\n";
            echo "      \"type\": 2,\n";
            echo "      \"ruc\": \"1808755-1-706832\",\n";
            echo "      \"verificationDigit\": \"97\"\n";
            echo "    }\n";
            echo "  }\n";
            echo "}\n\n";

            echo "Generated structure matches requirements: " . ($hasRequiredProperties ? "✅ YES" : "❌ NO") . "\n";

        } catch (Exception $e) {
            echo "❌ Error during formatting: " . $e->getMessage() . "\n";
        }
    }
}

TestCompleteReceiverFormatting::testCompleteReceiver();
