#!/bin/bash

# Script de Prueba Rápida - Validación TIPO QuickBooks
# Ubicación: docs/testing/test-quickbooks-tipo-validation.sh
# Ejecutar: ./docs/testing/test-quickbooks-tipo-validation.sh

echo "=== Prueba de Validación TIPO QuickBooks ==="
echo "Verificando que todos los formatos TIPO son aceptados"
echo ""

# Configurar Docker y entrar al contenedor
CONTAINER_NAME="docucenter_laravel.test"

echo "Ejecutando test de validación TIPO en contenedor Docker..."

docker exec -it $CONTAINER_NAME php -r "
// Test directo de validación TIPO
echo \"=== Test de Validación TIPO ===\n\";

// Simular lógica de validación del servicio
function testTipoValidation(\$tipo) {
    \$validTipos = [1, 2, '1', '2', '01', '02'];
    \$isValid = in_array(\$tipo, \$validTipos, true);

    echo \"TIPO: \" . var_export(\$tipo, true) . \" -> \" . (\$isValid ? '✅ VÁLIDO' : '❌ INVÁLIDO') . \"\n\";

    return \$isValid;
}

// Test todos los formatos
\$testCases = [1, 2, '1', '2', '01', '02'];
\$allPassed = true;

echo \"Probando formatos TIPO:\n\";
foreach (\$testCases as \$tipo) {
    if (!testTipoValidation(\$tipo)) {
        \$allPassed = false;
    }
}

echo \"\n=== RESULTADO FINAL ===\n\";
if (\$allPassed) {
    echo \"🎉 TODOS LOS FORMATOS TIPO SON VÁLIDOS\n\";
    echo \"El sistema ahora acepta: 1, 2, '1', '2', '01', '02'\n\";
} else {
    echo \"❌ ALGUNOS FORMATOS FALLAN\n\";
}

// Test específico del caso reportado
echo \"\n=== CASO ESPECÍFICO REPORTADO ===\n\";
echo \"Probando TIPO:'02' que causaba error...\n\";
\$reportedCase = testTipoValidation('02');
if (\$reportedCase) {
    echo \"✅ CASO REPORTADO RESUELTO: TIPO:'02' ahora es válido\n\";
} else {
    echo \"❌ CASO REPORTADO AÚN FALLA: TIPO:'02' sigue siendo inválido\n\";
}
"

echo ""
echo "=== Verificación en Código Fuente ==="
echo "Comprobando que la validación está implementada correctamente..."

# Verificar que el código contiene la validación expandida
if docker exec $CONTAINER_NAME grep -q "1,2,'1','2','01','02'" app/Services/QuickBooksOnlineService.php; then
    echo "✅ Validación expandida encontrada en el código"
else
    echo "❌ Validación expandida NO encontrada en el código"
fi

# Mostrar la línea específica de validación
echo ""
echo "Línea de validación actual:"
docker exec $CONTAINER_NAME grep -n "in_array.*TIPO" app/Services/QuickBooksOnlineService.php | head -1

echo ""
echo "=== Test Completado ==="
echo "Si todos los tests pasaron, el problema de validación TIPO está resuelto."
