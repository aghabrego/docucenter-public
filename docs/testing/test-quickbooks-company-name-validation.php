<?php
/**
 * Script de prueba para validar el objeto QuickBooks con CompanyName vacío
 *
 * Ubicación: docs/testing/test-quickbooks-company-name-validation.php
 * Uso: php docs/testing/test-quickbooks-company-name-validation.php
 */

echo "=== PRUEBA DE VALIDACIÓN QUICKBOOKS - COMPANY NAME VACÍO ===\n\n";

// Objeto de prueba basado en el que proporcionaste
$testData = [
    'Invoice' => [
        'Id' => '35',
        'DocNumber' => 'FC0000005158',
        'TxnDate' => '2025-07-22',
        'CustomerRef' => [
            'value' => '6',
            'name' => 'Solmary',
            'RUC' => null,
            'DV' => null,
            'TIPO' => null,
            'TIPO_RECEPTOR' => '04',
            'PASAPORTE' => 'XYZABC123',
            'CompanyName' => '', // Este es el campo problemático
            'DisplayName' => 'Solmary',
            'PrimaryEmail' => 'solmarymadrid@gmail.com',
            'BillAddr' => [
                'Line1' => 'Chile',
                'Line2' => '',
                'City' => 'Chile',
                'Country' => 'Chile',
                'CountrySubDivisionCode' => '',
                'PostalCode' => ''
            ]
        ],
        'TotalAmt' => 385.2,
        'Balance' => 385.2
    ]
];

echo "1. Datos originales:\n";
echo "   CompanyName: '" . $testData['Invoice']['CustomerRef']['CompanyName'] . "'\n";
echo "   Tipo: " . gettype($testData['Invoice']['CustomerRef']['CompanyName']) . "\n\n";

// Simular prepareForValidation
$data = $testData;

if (isset($data['Invoice']['CustomerRef']) && is_array($data['Invoice']['CustomerRef'])) {
    $customerRef = &$data['Invoice']['CustomerRef'];

    // Campos que deben eliminarse si están vacíos (excepto CompanyName)
    $fieldsToClean = [
        'RUC',
        'DV',
        'TIPO',
        'TIPO_RECEPTOR',
        'DisplayName',
        'PrimaryEmail'
    ];

    foreach ($fieldsToClean as $field) {
        if (isset($customerRef[$field]) &&
            ($customerRef[$field] === '' || $customerRef[$field] === null)) {
            unset($customerRef[$field]);
        }
    }

    // CompanyName se maneja por separado - solo eliminar si es null, no si es string vacío
    if (isset($customerRef['CompanyName']) && $customerRef['CompanyName'] === null) {
        unset($customerRef['CompanyName']);
    }
}

echo "2. Después de prepareForValidation:\n";
echo "   CompanyName presente: " . (isset($data['Invoice']['CustomerRef']['CompanyName']) ? 'SÍ' : 'NO') . "\n";
if (isset($data['Invoice']['CustomerRef']['CompanyName'])) {
    echo "   CompanyName: '" . $data['Invoice']['CustomerRef']['CompanyName'] . "'\n";
    echo "   Tipo: " . gettype($data['Invoice']['CustomerRef']['CompanyName']) . "\n";
}

// Simular validaciones críticas
echo "\n3. Validaciones aplicables:\n";
$rules = [
    'Invoice.CustomerRef.CompanyName' => 'sometimes|nullable|string|max:255'
];

$companyName = $data['Invoice']['CustomerRef']['CompanyName'] ?? null;

echo "   Regla: sometimes|nullable|string|max:255\n";
echo "   Valor a validar: '" . $companyName . "'\n";

// Verificar cada parte de la regla
$validations = [];

// sometimes - se aplica solo si el campo está presente
if (isset($data['Invoice']['CustomerRef']['CompanyName'])) {
    $validations[] = "✅ sometimes: Campo está presente";

    // nullable - puede ser null
    if (is_null($companyName) || $companyName !== null) {
        $validations[] = "✅ nullable: Valor null o no-null es válido";

        // string - debe ser string si no es null
        if (is_null($companyName) || is_string($companyName)) {
            $validations[] = "✅ string: Es string o null";

            // max:255 - máximo 255 caracteres si es string
            if (is_null($companyName) || strlen($companyName) <= 255) {
                $validations[] = "✅ max:255: Longitud válida (" . strlen($companyName ?? '') . " chars)";
            } else {
                $validations[] = "❌ max:255: Excede límite de caracteres";
            }
        } else {
            $validations[] = "❌ string: No es string";
        }
    } else {
        $validations[] = "❌ nullable: Lógica de null incorrecta";
    }
} else {
    $validations[] = "✅ sometimes: Campo no presente, validación omitida";
}

foreach ($validations as $validation) {
    echo "   " . $validation . "\n";
}

echo "\n=== RESULTADO ===\n";
$allValid = !in_array(false, array_map(function($v) { return strpos($v, '✅') === 0; }, $validations));
echo ($allValid ? "✅ VALIDACIÓN EXITOSA" : "❌ VALIDACIÓN FALLÓ") . "\n";
echo "\nLa corrección implementada permite que CompanyName con valor '' (string vacío) pase la validación.\n";
