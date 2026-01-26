#!/bin/bash

# Script de Testing: QuickBooks Item Code Extraction
# Proposito: Verificar que el campo Item_Code se extrae y almacena correctamente
# Uso: ./test-quickbooks-item-code-extraction.sh [org_id]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCUCENTER_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "=== Test QuickBooks Item Code Extraction ==="
echo "DocuCenter Root: $DOCUCENTER_ROOT"
echo

# Verificar si Docker está ejecutándose
if ! docker ps >/dev/null 2>&1; then
    echo "❌ Error: Docker no está ejecutándose"
    exit 1
fi

# Función para ejecutar comandos en el contenedor
run_artisan() {
    docker exec -it docucenter_laravel.test php artisan "$@"
}

run_mysql() {
    docker exec -i docucenter-mariadb-1 mysql -u weirdolabs -psecret "$@"
}

# Parámetros
ORG_ID=${1:-"5"}

# Obtener la base de datos real de la organización
echo "🔍 Obteniendo información de la organización..."
DATABASE=$(docker exec -i docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "USE docucenter; SELECT database FROM organizations WHERE id = $ORG_ID;" 2>/dev/null | tail -n +2)

if [ -z "$DATABASE" ]; then
    echo "❌ Error: No se encontró la organización con ID $ORG_ID"
    exit 1
fi

echo "🔍 Verificando columna Item_Code..."
echo "Organization ID: $ORG_ID"
echo "Database: $DATABASE"
echo

# Función para verificar que la columna existe
check_item_code_column() {
    echo "📊 Verificando existencia de columna Item_Code:"

    COLUMN_CHECK=$(run_mysql -e "USE $DATABASE; SHOW COLUMNS FROM Sales_Detail_Imp LIKE 'Item_Code';" 2>/dev/null | tail -n +2)

    if [ -n "$COLUMN_CHECK" ]; then
        echo "✅ Columna Item_Code existe en Sales_Detail_Imp"
        echo "$COLUMN_CHECK"
        return 0
    else
        echo "❌ Columna Item_Code NO existe en Sales_Detail_Imp"
        return 1
    fi
}

# Función para verificar datos existentes con Item_Code
check_existing_item_codes() {
    echo "📊 Verificando datos existentes con Item_Code:"

    QUERY="SELECT InvoiceNumber, Sequential, Item_Code, Description
           FROM Sales_Detail_Imp
           WHERE Item_Code IS NOT NULL AND Item_Code != ''
           LIMIT 10;"

    run_mysql -e "USE $DATABASE; $QUERY" 2>/dev/null || {
        echo "❌ Error ejecutando consulta en base de datos $DATABASE"
        return 1
    }
}

# Función para probar la extracción de código
test_item_code_extraction() {
    echo "🧪 Probando extracción de Item_Code..."

    # Crear comando Artisan temporal para probar la extracción
    cat > /tmp/test_item_code_extraction.php << 'EOF'
<?php

// Simular el método extractItemCode
function extractItemCode(string $itemRefName): ?string
{
    if (empty($itemRefName)) {
        return null;
    }

    // Extraer todo lo que está antes del primer espacio
    $parts = explode(' ', $itemRefName, 2);
    $itemCode = trim($parts[0]);

    // Verificar que no esté vacío después del trim
    return !empty($itemCode) ? $itemCode : null;
}

// Casos de prueba
$testCases = [
    'ResMed:37221 AirSense 10 AutoSet',
    'ACME:12345 Product Description',
    'SIMPLE123 Another Product',
    'CODE-456 Complex Product Name',
    'NoSpaceCode',
    ' SpaceAtStart Product',
    '',
    null
];

echo "Casos de prueba para extracción de Item_Code:\n";
echo str_repeat("=", 50) . "\n";

foreach ($testCases as $testCase) {
    $result = extractItemCode($testCase ?? '');
    $displayCase = $testCase ?? 'null';
    echo "Input: '$displayCase'\n";
    echo "Output: " . ($result ?? 'null') . "\n";
    echo str_repeat("-", 30) . "\n";
}
EOF

    # Ejecutar test de extracción
    docker exec -i docucenter_laravel.test php -r "$(cat /tmp/test_item_code_extraction.php)" 2>/dev/null || {
        echo "❌ Error ejecutando test de extracción"
        return 1
    }

    # Limpiar archivo temporal
    rm -f /tmp/test_item_code_extraction.php
}

# Función para verificar el fillable del modelo
test_model_fillable() {
    echo "🔍 Verificando fillable del modelo SalesDetailImp..."

    run_artisan tinker --execute="
        echo 'Fillable fields for SalesDetailImp:' . PHP_EOL;
        \$model = new App\Models\SalesDetailImp();
        \$fillable = \$model->getFillable();
        if (in_array('Item_Code', \$fillable)) {
            echo '✅ Item_Code está en fillable' . PHP_EOL;
        } else {
            echo '❌ Item_Code NO está en fillable' . PHP_EOL;
        }
        echo 'Todos los campos fillable:' . PHP_EOL;
        print_r(\$fillable);
    " 2>/dev/null
}

# Función principal de testing
main() {
    echo "🚀 Iniciando test completo..."
    echo

    # Test 1: Verificar columna existe
    check_item_code_column
    echo

    # Test 2: Verificar datos existentes
    check_existing_item_codes
    echo

    # Test 3: Probar extracción de código
    test_item_code_extraction
    echo

    # Test 4: Verificar fillable del modelo
    test_model_fillable
    echo

    echo "✅ Test completado"
}

# Función de ayuda
show_help() {
    echo "Uso: $0 [org_id]"
    echo
    echo "Parámetros:"
    echo "  org_id     ID de la organización (default: 5)"
    echo
    echo "Ejemplos:"
    echo "  $0                    # Test con organización ID=5"
    echo "  $0 3                  # Test con organización ID=3"
}

# Procesar argumentos
case "$1" in
    -h|--help)
        show_help
        exit 0
        ;;
    *)
        main "$@"
        ;;
esac
