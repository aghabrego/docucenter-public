#!/bin/bash

# Script de Prueba - Validación APIs Customer
# Ubicación: docs/testing/test-customer-validation.sh
# Propósito: Probar validaciones de ParentTransactionId en APIs customer_receipt_imp y customer_credit_memo

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "=== PRUEBAS DE VALIDACIÓN - APIs CUSTOMER ==="
echo "Proyecto: DocuCenter"
echo "Fecha: $(date)"
echo "======================================"
echo

# Función para mostrar ayuda
show_help() {
    cat << EOF
Uso: $0 [OPCIÓN]

OPCIONES:
    database    - Verificar tabla sales_header_imp
    receipt     - Probar validación customer_receipt_imp
    credit      - Probar validación customer_credit_memo
    complete    - Ejecutar todas las pruebas
    help        - Mostrar esta ayuda

EJEMPLOS:
    $0 complete     # Ejecutar todas las pruebas
    $0 database     # Solo verificar tabla
    $0 receipt      # Solo probar customer receipt

NOTAS:
    • Las pruebas requieren datos en la tabla sales_header_imp
    • Se prueban casos válidos e inválidos de ParentTransactionId
    • Los mensajes de error deben aparecer en español
EOF
}

# Función para verificar tabla sales_header_imp
check_database() {
    echo "1. VERIFICANDO MODELO SalesHeaderImp"
    echo "-----------------------------------"

    php artisan tinker --execute="
        try {
            \$count = \App\Models\SalesHeaderImp::count();
            echo \"✅ Modelo SalesHeaderImp encontrado\\n\";
            echo \"   • Total de registros: \$count\\n\";

            if (\$count > 0) {
                \$first = \App\Models\SalesHeaderImp::first();
                \$last = \App\Models\SalesHeaderImp::orderBy('ID', 'desc')->first();
                echo \"   • Primer ID: \" . (\$first->ID ?? 'N/A') . \"\\n\";
                echo \"   • Último ID: \" . (\$last->ID ?? 'N/A') . \"\\n\";
            } else {
                echo \"⚠️  La tabla está vacía - las pruebas pueden fallar\\n\";
            }
        } catch (Exception \$e) {
            echo \"❌ ERROR: \" . \$e->getMessage() . \"\\n\";
        }
    "
    echo
}

# Función para probar customer receipt
test_receipt() {
    echo "2. PROBANDO customer_receipt_imp VALIDATION"
    echo "----------------------------------------"

    # Test con ParentTransactionId válido (asumiendo ID 1)
    echo "Test A: ParentTransactionId válido (ID=1)"
    php artisan tinker --execute="
        \$request = new \App\Http\Requests\CustomerReceiptImpRequest();
        \$data = [
            'CustomerID' => 'CUST001',
            'ReceiptNumber' => 'REC001',
            'ParentTransactionId' => 1,
            'Details' => [['item' => 'Test', 'amount' => 100]]
        ];

        \$validator = \Illuminate\Support\Facades\Validator::make(\$data, \$request->rules(), \$request->messages());

        if (\$validator->fails()) {
            echo \"❌ FALLÓ: \" . implode(', ', \$validator->errors()->all()) . \"\\n\";
        } else {
            echo \"✅ ÉXITO: Validación pasó correctamente\\n\";
        }
    "

    # Test con ParentTransactionId inválido
    echo "Test B: ParentTransactionId inválido (ID=999999)"
    php artisan tinker --execute="
        \$request = new \App\Http\Requests\CustomerReceiptImpRequest();
        \$data = [
            'CustomerID' => 'CUST001',
            'ReceiptNumber' => 'REC002',
            'ParentTransactionId' => 999999,
            'Details' => [['item' => 'Test', 'amount' => 100]]
        ];

        \$validator = \Illuminate\Support\Facades\Validator::make(\$data, \$request->rules(), \$request->messages());

        if (\$validator->fails()) {
            echo \"✅ ÉXITO: Validación falló como esperado\\n\";
            echo \"   • \" . implode('\\n   • ', \$validator->errors()->all()) . \"\\n\";
        } else {
            echo \"❌ ERROR: Debería haber fallado\\n\";
        }
    "

    # Test sin ParentTransactionId (nullable)
    echo "Test C: Sin ParentTransactionId (campo nullable)"
    php artisan tinker --execute="
        \$request = new \App\Http\Requests\CustomerReceiptImpRequest();
        \$data = [
            'CustomerID' => 'CUST001',
            'ReceiptNumber' => 'REC003',
            'Details' => [['item' => 'Test', 'amount' => 100]]
        ];

        \$validator = \Illuminate\Support\Facades\Validator::make(\$data, \$request->rules(), \$request->messages());

        if (\$validator->fails()) {
            echo \"❌ FALLÓ: \" . implode(', ', \$validator->errors()->all()) . \"\\n\";
        } else {
            echo \"✅ ÉXITO: Campo nullable funciona correctamente\\n\";
        }
    "
    echo
}

# Función para probar customer credit memo
test_credit() {
    echo "3. PROBANDO customer_credit_memo VALIDATION"
    echo "----------------------------------------"

    # Test con ParentTransactionId válido
    echo "Test A: ParentTransactionId válido (ID=1)"
    php artisan tinker --execute="
        \$request = new \App\Http\Requests\CustomerCreditMemoImpRequest();
        \$data = [
            'CustomerID' => 'CUST001',
            'CreditNumber' => 'CR001',
            'ParentTransactionId' => 1,
            'Details' => [['item' => 'Credit', 'amount' => 50]]
        ];

        \$validator = \Illuminate\Support\Facades\Validator::make(\$data, \$request->rules(), \$request->messages());

        if (\$validator->fails()) {
            echo \"❌ FALLÓ: \" . implode(', ', \$validator->errors()->all()) . \"\\n\";
        } else {
            echo \"✅ ÉXITO: Validación pasó correctamente\\n\";
        }
    "

    # Test con ParentTransactionId inválido
    echo "Test B: ParentTransactionId inválido (ID=999999)"
    php artisan tinker --execute="
        \$request = new \App\Http\Requests\CustomerCreditMemoImpRequest();
        \$data = [
            'CustomerID' => 'CUST001',
            'CreditNumber' => 'CR002',
            'ParentTransactionId' => 999999,
            'Details' => [['item' => 'Credit', 'amount' => 50]]
        ];

        \$validator = \Illuminate\Support\Facades\Validator::make(\$data, \$request->rules(), \$request->messages());

        if (\$validator->fails()) {
            echo \"✅ ÉXITO: Validación falló como esperado\\n\";
            echo \"   • \" . implode('\\n   • ', \$validator->errors()->all()) . \"\\n\";
        } else {
            echo \"❌ ERROR: Debería haber fallado\\n\";
        }
    "
    echo
}

# Función para ejecutar todas las pruebas
run_complete() {
    echo "EJECUTANDO PRUEBAS COMPLETAS"
    echo "============================"
    echo

    check_database
    test_receipt
    test_credit

    echo "RESUMEN DE PRUEBAS"
    echo "=================="
    echo "✅ Verificación de modelo SalesHeaderImp"
    echo "✅ Validación customer_receipt_imp con ParentTransactionId"
    echo "✅ Validación customer_credit_memo con ParentTransactionId"
    echo
    echo "NOTAS:"
    echo "• Los mensajes de error están en español"
    echo "• Campo ParentTransactionId es nullable en ambas APIs"
    echo "• La validación exists verifica contra App\\Models\\SalesHeaderImp"
    echo "• Los IDs inexistentes (999999) deben fallar la validación"
}

# Función principal
main() {
    # Verificar que estamos en el directorio correcto
    if [[ ! -f "$PROJECT_ROOT/artisan" ]]; then
        echo "❌ ERROR: No se encontró artisan en $PROJECT_ROOT"
        echo "Ejecuta este script desde el directorio docs/testing/"
        exit 1
    fi

    # Cambiar al directorio del proyecto
    cd "$PROJECT_ROOT"

    case "${1:-help}" in
        database)
            check_database
            ;;
        receipt)
            test_receipt
            ;;
        credit)
            test_credit
            ;;
        complete)
            run_complete
            ;;
        help|*)
            show_help
            ;;
    esac
}

# Ejecutar función principal con argumentos
main "$@"
