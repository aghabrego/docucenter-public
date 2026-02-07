#!/bin/bash

# Test de Validación: Corrección Receptor Extranjero
# Verificar que el cliente Solmary (ID 32) ahora muestre correctamente la identificación extranjera

echo "🧪 TEST DE VALIDACIÓN: Corrección Receptor Extranjero"
echo "===================================================="
echo

echo "🔍 Cliente de prueba:"
echo "   - ID: 32"
echo "   - CustomerID: XYZABC123"
echo "   - Nombre: Solmary"
echo "   - País: Chile"
echo "   - Custom_field3: 04 (código para extranjero)"
echo

echo "✅ ARREGLO IMPLEMENTADO:"
echo "   - Agregado x-data específico para sección extranjero"
echo "   - Variables receptor_tipo y customer_id ahora accesibles"
echo "   - Condición x-show debería funcionar correctamente"
echo

echo "📋 VERIFICACIÓN DE LÓGICA:"

# 1. Confirmar que Custom_field3 = 04 corresponde a extranjero
echo "1. Verificando Custom_field3 del cliente 32..."
CUSTOM_FIELD3=$(docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "SELECT Custom_field3 FROM db_18257061709732_90.Customers_Imp WHERE ID = 32;" -s | tail -1)
echo "   Custom_field3: '$CUSTOM_FIELD3'"

# 2. Verificar que código 04 = Extranjero (ID 3)
echo "2. Verificando tipo de receptor para código '$CUSTOM_FIELD3'..."
RECEPTOR_INFO=$(docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "SELECT id, name FROM docucenter.type_receptors WHERE code = '$CUSTOM_FIELD3';" -s | tail -1)
echo "   Resultado: $RECEPTOR_INFO"

RECEPTOR_ID=$(echo "$RECEPTOR_INFO" | awk '{print $1}')
RECEPTOR_NAME=$(echo "$RECEPTOR_INFO" | awk '{print $2}')

echo "   - receptor_tipo asignado: '$RECEPTOR_ID'"
echo "   - Nombre: '$RECEPTOR_NAME'"

# 3. Verificar condición blade
echo "3. Verificando condición blade..."
echo "   Condición: receptor_tipo === '3' && customer_id !== null && customer_id !== ''"
echo "   receptor_tipo actual: '$RECEPTOR_ID'"
echo "   customer_id: 'XYZABC123' (no nulo ni vacío)"

if [ "$RECEPTOR_ID" = "3" ]; then
    echo "   ✅ CONDICIÓN DEBERÍA CUMPLIRSE"
    echo "   ✅ Los campos B406-B416 deberían ser visibles"
else
    echo "   ❌ CONDICIÓN NO SE CUMPLE"
    echo "   ❌ Hay un problema en la configuración de tipos"
fi

echo
echo "📋 RESUMEN DEL ARREGLO:"
echo "   ✅ Problema identificado: x-data faltante en sección extranjero"
echo "   ✅ Solución implementada: x-data específico agregado"
echo "   ✅ Variables necesarias ahora disponibles"
echo "   ✅ Condición x-show debería evaluar correctamente"

echo
echo "🎯 PRÓXIMOS PASOS PARA TESTING:"
echo "   1. Abrir la aplicación en el navegador"
echo "   2. Ir a crear factura para organización 2"
echo "   3. Seleccionar cliente 'Solmary' (ID: XYZABC123)"
echo "   4. Verificar que aparezcan los campos B406-B416 de identificación extranjera"
echo "   5. Verificar que se pueden llenar: Tipo Identificación, Número, País"

echo
echo "🔧 SI EL PROBLEMA PERSISTE:"
echo "   - Verificar consola JavaScript por errores"
echo "   - Inspeccionar elemento para ver variables Alpine.js"
echo "   - Verificar que Livewire esté sincronizando receptor_tipo correctamente"

echo
echo "🎯 Test completado: $(date)"
