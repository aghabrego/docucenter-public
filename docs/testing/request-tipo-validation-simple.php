<?php

/**
 * Test Simple de Validación CreateSaleQuickBooksRequest - TIPO
 *
 * Ubicación: docs/testing/request-tipo-validation-simple.php
 * Uso:
 * 1. php artisan tinker
 * 2. include 'docs/testing/request-tipo-validation-simple.php';
 * 3. testRequestTipoValidation();
 */

function testRequestTipoValidation()
{
    echo "=== Test CreateSaleQuickBooksRequest TIPO Validation ===\n";

    // Datos base para el test
    $baseData = [
        'Invoice' => [
            'TotalAmt' => 100.00,
            'CustomerRef' => [
                'value' => '123',
                'name' => 'Test Customer'
            ]
        ]
    ];

    // Casos de prueba para TIPO
    $tipoTestCases = [
        ['tipo' => 0, 'description' => 'TIPO: 0 (legacy)'],
        ['tipo' => 1, 'description' => 'TIPO: 1 (Persona Jurídica)'],
        ['tipo' => 2, 'description' => 'TIPO: 2 (Persona Natural)'],
        ['tipo' => '01', 'description' => 'TIPO: "01" (QB format)'],
        ['tipo' => '02', 'description' => 'TIPO: "02" (QB format)'],
    ];

    $successCount = 0;
    $totalTests = count($tipoTestCases);

    foreach ($tipoTestCases as $test) {
        $testData = $baseData;
        $testData['Invoice']['CustomerRef']['TIPO'] = $test['tipo'];

        echo "Test: {$test['description']}\n";
        echo "  Input: " . var_export($test['tipo'], true) . "\n";

        try {
            // Crear instancia del request y validar
            $request = new \App\Http\Requests\CreateSaleQuickBooksRequest();
            $validator = \Illuminate\Support\Facades\Validator::make($testData, $request->rules());

            if ($validator->passes()) {
                echo "  ✅ VÁLIDO - Request acepta este formato\n\n";
                $successCount++;
            } else {
                echo "  ❌ INVÁLIDO - Request rechaza este formato\n";
                $tipoErrors = $validator->errors()->get('Invoice.CustomerRef.TIPO');
                if (!empty($tipoErrors)) {
                    echo "  Errores TIPO: " . implode(', ', $tipoErrors) . "\n";
                }
                echo "\n";
            }

        } catch (\Exception $e) {
            echo "  ❌ ERROR: {$e->getMessage()}\n\n";
        }
    }

    echo "=== RESUMEN ===\n";
    echo "✅ Válidos: {$successCount}/{$totalTests}\n";
    echo "❌ Inválidos: " . ($totalTests - $successCount) . "/{$totalTests}\n";

    if ($successCount === $totalTests) {
        echo "🎉 TODOS LOS FORMATOS SON ACEPTADOS POR EL REQUEST\n";
    } else {
        echo "⚠️  ALGUNOS FORMATOS FALLAN EN LA VALIDACIÓN DEL REQUEST\n";
    }

    // Test específico del caso reportado
    echo "\n=== CASO ESPECÍFICO: TIPO '02' ===\n";
    $reportedData = $baseData;
    $reportedData['Invoice']['CustomerRef']['TIPO'] = '02';

    try {
        $request = new \App\Http\Requests\CreateSaleQuickBooksRequest();
        $validator = \Illuminate\Support\Facades\Validator::make($reportedData, $request->rules());

        if ($validator->passes()) {
            echo "✅ CASO REPORTADO RESUELTO: Request acepta TIPO:'02'\n";
        } else {
            echo "❌ CASO REPORTADO AÚN FALLA: Request rechaza TIPO:'02'\n";
            echo "Errores: " . json_encode($validator->errors()->all()) . "\n";
        }

    } catch (\Exception $e) {
        echo "❌ ERROR en caso reportado: {$e->getMessage()}\n";
    }

    return $successCount === $totalTests;
}

echo "Test de validación Request cargado.\n";
echo "Para ejecutar: testRequestTipoValidation();\n";
