#!/bin/bash

# Script de Testing: EzeeIssued Field Update
# Proposito: Verificar que el campo EzeeIssued se actualiza correctamente después de la emisión
# Uso: ./test-ezeeissued-field-update.sh [org_id] [sale_id]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCUCENTER_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "=== Test EzeeIssued Field Update ==="
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
SALE_ID=${2:-"1"}

# Obtener la base de datos real de la organización
echo "🔍 Obteniendo información de la organización..."
DATABASE=$(docker exec -i docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "USE docucenter; SELECT database FROM organizations WHERE id = $ORG_ID;" 2>/dev/null | tail -n +2)

if [ -z "$DATABASE" ]; then
    echo "❌ Error: No se encontró la organización con ID $ORG_ID"
    exit 1
fi

echo "🔍 Verificando estado de factura..."
echo "Organization ID: $ORG_ID"
echo "Sale ID: $SALE_ID"
echo "Database: $DATABASE"
echo

# Función para verificar estado actual
check_ezeeissued_status() {
    echo "📊 Estado actual del campo EzeeIssued:"

    QUERY="SELECT Id, EzeeIssued, CAST(EzeeIssued AS UNSIGNED) as EzeeIssued_Int, InvoiceNote
           FROM Sales_Header_Imp
           WHERE Id = $SALE_ID
           LIMIT 1;"

    run_mysql -e "USE $DATABASE; $QUERY" 2>/dev/null || {
        echo "❌ Error ejecutando consulta en base de datos $DATABASE"
        return 1
    }
}

# Función para simular actualización del campo
simulate_field_update() {
    echo "🔄 Simulando actualización del campo EzeeIssued..."

    UPDATE_QUERY="UPDATE Sales_Header_Imp
                  SET EzeeIssued = 1,
                      InvoiceNote = JSON_OBJECT('test', 'field_update_simulation', 'timestamp', NOW())
                  WHERE Id = $SALE_ID;"

    run_mysql -e "USE $DATABASE; $UPDATE_QUERY" 2>/dev/null && {
        echo "✅ Campo actualizado exitosamente"
        return 0
    } || {
        echo "❌ Error actualizando campo"
        return 1
    }
}

# Función para verificar casting del modelo
test_model_casting() {
    echo "🧪 Verificando casting del modelo SalesHeaderImp..."

    # Crear comando Artisan temporal para probar el casting
    cat > /tmp/test_casting.php << 'EOF'
<?php

use App\Models\SalesHeaderImp;
use Illuminate\Support\Facades\DB;

// Establecer conexión a la base de datos específica
DB::connection()->useDatabase('{{ DATABASE }}');

// Obtener el registro
$sale = SalesHeaderImp::find({{ SALE_ID }});

if ($sale) {
    echo "ID: " . $sale->Id . "\n";
    echo "EzeeIssued (raw): " . var_export($sale->getRawOriginal('EzeeIssued'), true) . "\n";
    echo "EzeeIssued (cast): " . var_export($sale->EzeeIssued, true) . "\n";
    echo "EzeeIssued (type): " . gettype($sale->EzeeIssued) . "\n";
    echo "EzeeIssued === 1: " . var_export($sale->EzeeIssued === 1, true) . "\n";
    echo "EzeeIssued == 1: " . var_export($sale->EzeeIssued == 1, true) . "\n";
} else {
    echo "❌ No se encontró la factura con ID $SALE_ID\n";
}
EOF

    # Reemplazar variables en el template
    sed -i "s/{{ DATABASE }}/$DATABASE/g" /tmp/test_casting.php
    sed -i "s/{{ SALE_ID }}/$SALE_ID/g" /tmp/test_casting.php

    # Ejecutar test de casting
    docker exec -i docucenter-app-1 php -r "$(cat /tmp/test_casting.php)" 2>/dev/null || {
        echo "❌ Error ejecutando test de casting"
        return 1
    }

    # Limpiar archivo temporal
    rm -f /tmp/test_casting.php
}

# Función para verificar conexión de la organización
test_organization_connection() {
    echo "🔗 Verificando conexión de organización..."

    run_artisan tinker --execute="
        use App\Models\Organization;
        use Illuminate\Support\Facades\DB;

        \$org = Organization::find($ORG_ID);
        if (\$org) {
            echo 'Organization ID: ' . \$org->id . PHP_EOL;
            echo 'Database: ' . \$org->database . PHP_EOL;
            echo 'Database exists: ' . (DB::connection()->select('SHOW DATABASES LIKE \"' . \$org->database . '\"') ? 'Yes' : 'No') . PHP_EOL;

            // Test connection switch
            DB::connection()->useDatabase(\$org->database);
            echo 'Current database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
        } else {
            echo 'Organization not found' . PHP_EOL;
        }
    " 2>/dev/null
}

# Función principal de testing
main() {
    echo "🚀 Iniciando test completo..."
    echo

    # Test 1: Verificar conexión de organización
    test_organization_connection
    echo

    # Test 2: Estado actual
    check_ezeeissued_status
    echo

    # Test 3: Verificar casting del modelo
    test_model_casting
    echo

    # Test 4: Simular actualización (opcional)
    if [ "$3" = "simulate" ]; then
        simulate_field_update
        echo
        echo "📊 Estado después de simulación:"
        check_ezeeissued_status
    fi

    echo "✅ Test completado"
}

# Función de ayuda
show_help() {
    echo "Uso: $0 [org_id] [sale_id] [simulate]"
    echo
    echo "Parámetros:"
    echo "  org_id     ID de la organización (default: 14034741628418)"
    echo "  sale_id    ID de la factura (default: 67)"
    echo "  simulate   Agregar 'simulate' para simular actualización del campo"
    echo
    echo "Ejemplos:"
    echo "  $0                           # Test con valores por defecto"
    echo "  $0 14034741628418 67         # Test con IDs específicos"
    echo "  $0 14034741628418 67 simulate # Test con simulación de actualización"
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
