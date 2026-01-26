#!/bin/bash

# Script simple para probar QuickBooks Item Code
# Uso: ./quick-test-item-code.sh

echo "🧪 Quick Test - QuickBooks Item Code Extraction"
echo "==============================================="

# Probar función PHP directamente
docker exec -it docucenter_laravel.test php -r "
\$testCases = [
    'ResMed:37221 AirSense 10 AutoSet' => 'ResMed:37221',
    'ACME:12345 Product Description' => 'ACME:12345',
    'SIMPLE123 Another Product' => 'SIMPLE123',
    'NoSpaceCode' => 'NoSpaceCode',
    ' SpaceAtStart Product' => 'SpaceAtStart',
    '' => null,
];

function extractItemCode(\$itemRefName) {
    if (empty(\$itemRefName)) {
        return null;
    }
    \$parts = explode(' ', \$itemRefName, 2);
    \$itemCode = trim(\$parts[0]);
    return !empty(\$itemCode) ? \$itemCode : null;
}

foreach (\$testCases as \$input => \$expected) {
    \$result = extractItemCode(\$input);
    \$status = (\$result === \$expected) ? '✅' : '❌';
    echo \$status . ' Input: \"' . \$input . '\" => Result: \"' . (\$result ?? 'null') . '\" (Expected: \"' . (\$expected ?? 'null') . '\")' . PHP_EOL;
}
"

echo
echo "✅ Quick test completado"
