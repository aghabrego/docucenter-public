#!/bin/bash

# Test de Validación CreateSaleQuickBooksRequest - TIPO
# Ubicación: docs/testing/test-request-tipo-validation.sh
# Ejecutar: ./docs/testing/test-request-tipo-validation.sh

echo "=== Test de Validación Request TIPO ==="
echo "Verificando que CreateSaleQuickBooksRequest acepta todos los formatos TIPO"
echo ""

CONTAINER_NAME="docucenter_laravel.test"

echo "Ejecutando test de validación Request TIPO en contenedor Docker..."

docker exec -it $CONTAINER_NAME php -r "
require 'vendor/autoload.php';

use App\Http\Requests\CreateSaleQuickBooksRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

echo \"=== Test de Validación CreateSaleQuickBooksRequest TIPO ===\n\";

// Crear instancia del request para obtener las reglas
\$requestInstance = new CreateSaleQuickBooksRequest();
\$rules = \$requestInstance->rules();

// Test data template
\$baseData = [
    'Invoice' => [
        'TotalAmt' => 100.00,
        'CustomerRef' => [
            'value' => '123',
            'name' => 'Test Customer'
        ]
    ]
];

// Test cases para TIPO
\$tipoTestCases = [
    ['tipo' => 0, 'description' => 'TIPO: 0 (legacy)'],
    ['tipo' => 1, 'description' => 'TIPO: 1 (Persona Jurídica)'],
    ['tipo' => 2, 'description' => 'TIPO: 2 (Persona Natural)'],
    ['tipo' => '01', 'description' => 'TIPO: \"01\" (QB format)'],
    ['tipo' => '02', 'description' => 'TIPO: \"02\" (QB format)'],
];

\$successCount = 0;
\$totalTests = count(\$tipoTestCases);

foreach (\$tipoTestCases as \$test) {
    \$testData = \$baseData;
    \$testData['Invoice']['CustomerRef']['TIPO'] = \$test['tipo'];

    \$validator = Validator::make(\$testData, \$rules);

    echo \"Test: {\$test['description']}\n\";
    echo \"  Input: \" . var_export(\$test['tipo'], true) . \"\n\";

    if (\$validator->passes()) {
        echo \"  ✅ VÁLIDO - Request acepta este formato\n\n\";
        \$successCount++;
    } else {
        echo \"  ❌ INVÁLIDO - Request rechaza este formato\n\";
        echo \"  Errores: \" . json_encode(\$validator->errors()->get('Invoice.CustomerRef.TIPO')) . \"\n\n\";
    }
}

echo \"=== RESUMEN ===\n\";
echo \"✅ Válidos: {\$successCount}/{\$totalTests}\n\";
echo \"❌ Inválidos: \" . (\$totalTests - \$successCount) . \"/{\$totalTests}\n\";

if (\$successCount === \$totalTests) {
    echo \"🎉 TODOS LOS FORMATOS SON ACEPTADOS POR EL REQUEST\n\";
} else {
    echo \"⚠️  ALGUNOS FORMATOS FALLAN EN LA VALIDACIÓN DEL REQUEST\n\";
}

// Test específico del caso reportado
echo \"\n=== CASO ESPECÍFICO: TIPO '02' ===\n\";
\$reportedData = \$baseData;
\$reportedData['Invoice']['CustomerRef']['TIPO'] = '02';

\$validator = Validator::make(\$reportedData, \$rules);

if (\$validator->passes()) {
    echo \"✅ CASO REPORTADO RESUELTO: Request acepta TIPO:'02'\n\";
} else {
    echo \"❌ CASO REPORTADO AÚN FALLA: Request rechaza TIPO:'02'\n\";
    echo \"Errores: \" . json_encode(\$validator->errors()->all()) . \"\n\";
}
"

echo ""
echo "=== Verificación en Archivo de Validación ==="
echo "Comprobando que la regla está actualizada..."

if docker exec $CONTAINER_NAME grep -q "in:0,1,2,01,02" app/Http/Requests/CreateSaleQuickBooksRequest.php; then
    echo "✅ Regla de validación expandida encontrada"
    echo "Regla actual:"
    docker exec $CONTAINER_NAME grep -A 1 "TIPO.*in:" app/Http/Requests/CreateSaleQuickBooksRequest.php | head -1
else
    echo "❌ Regla de validación expandida NO encontrada"
fi

echo ""
echo "=== Test Completado ==="
echo "Si todos los tests pasaron, el Request ahora acepta todos los formatos TIPO."
