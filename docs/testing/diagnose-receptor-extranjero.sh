#!/bin/bash

# Test de Diagnóstico: Cliente Extranjero Solmary
# Problema: Cliente 32 (Solmary, Chile) es extranjero pero no se visualiza la identificación

echo "🔍 DIAGNÓSTICO: Cliente Extranjero Solmary (ID: 32)"
echo "=============================================="
echo

# 1. Información del cliente
echo "📋 1. INFORMACIÓN DEL CLIENTE"
echo "------------------------------"
echo "Consultando cliente ID 32 en organización 2..."

CUSTOMER_INFO=$(docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "SELECT ID, CustomerID, Customer_Bill_Name, Country, Custom_field1, Custom_field2, Custom_field3, Custom_field4, Custom_field5 FROM db_18257061709732_90.Customers_Imp WHERE ID = 32;" -s)

echo "Cliente encontrado:"
echo "$CUSTOMER_INFO"
echo

# Extraer Custom_field3 para análisis
CUSTOM_FIELD3=$(echo "$CUSTOMER_INFO" | awk '{print $6}')
echo "Custom_field3 detectado: '$CUSTOM_FIELD3'"
echo

# 2. Tipos de receptor disponibles
echo "📋 2. TIPOS DE RECEPTOR DISPONIBLES"
echo "-----------------------------------"
echo "Consultando tipos de receptor..."

RECEPTOR_TYPES=$(docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "SELECT id, name, code FROM docucenter.type_receptors ORDER BY id;" -s)

echo "Tipos disponibles:"
echo "$RECEPTOR_TYPES"
echo

# 3. Lógica de detección
echo "📋 3. LÓGICA DE DETECCIÓN"
echo "-------------------------"
echo "Custom_field3 del cliente: '$CUSTOM_FIELD3'"

# Simular padding (strPad)
if [ ${#CUSTOM_FIELD3} -eq 1 ]; then
    PADDED_CODE="0$CUSTOM_FIELD3"
else
    PADDED_CODE="$CUSTOM_FIELD3"
fi

echo "Código con padding: '$PADDED_CODE'"

# Buscar el tipo de receptor correspondiente
MATCHING_TYPE=$(docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "SELECT id, name, code FROM docucenter.type_receptors WHERE code = '$PADDED_CODE';" -s)

echo "Tipo de receptor encontrado:"
echo "$MATCHING_TYPE"

if [ -n "$MATCHING_TYPE" ]; then
    RECEPTOR_ID=$(echo "$MATCHING_TYPE" | awk '{print $1}')
    RECEPTOR_NAME=$(echo "$MATCHING_TYPE" | awk '{print $2}')
    echo "receptor_tipo asignado: '$RECEPTOR_ID' ($RECEPTOR_NAME)"
    echo

    # 4. Verificar condición blade
    echo "📋 4. VERIFICACIÓN CONDICIÓN BLADE"
    echo "----------------------------------"
    echo "Condición blade: receptor_tipo === '3'"
    echo "Valor actual: receptor_tipo = '$RECEPTOR_ID'"

    if [ "$RECEPTOR_ID" = "3" ]; then
        echo "✅ CONDICIÓN CUMPLIDA: Debería mostrar campos extranjeros"
    else
        echo "❌ CONDICIÓN NO CUMPLIDA: NO mostrará campos extranjeros"
        echo "   - Esperado: '3'"
        echo "   - Actual: '$RECEPTOR_ID'"
    fi
else
    echo "❌ NO se encontró receptor con código '$PADDED_CODE'"
    echo "   - Se usaría Custom_field3 original: '$CUSTOM_FIELD3'"

    if [ "$CUSTOM_FIELD3" = "3" ]; then
        echo "✅ Aún así cumple condición blade"
    else
        echo "❌ NO cumple condición blade"
    fi
fi

echo
echo "📋 5. CONCLUSIÓN"
echo "----------------"

if [ "$RECEPTOR_ID" = "3" ]; then
    echo "✅ La lógica DEBERÍA funcionar correctamente"
    echo "✅ El cliente debería detectarse como extranjero"
    echo "✅ Los campos B406-B416 deberían ser visibles"
    echo
    echo "🔍 POSIBLES CAUSAS DEL PROBLEMA:"
    echo "1. Problema en JavaScript del blade (condición no evaluándose)"
    echo "2. customer_id no está siendo asignado correctamente"
    echo "3. Problemas de caché en el frontend"
    echo "4. Error en la lógica de Alpine.js/Livewire"
    echo "5. La condición en blade podría tener un problema de sintaxis"
else
    echo "❌ PROBLEMA ENCONTRADO en la lógica de detección"
    echo "   - El sistema NO detectará este cliente como extranjero"
    echo "   - Custom_field3 = '$CUSTOM_FIELD3' no corresponde a extranjero"
    echo "   - Verificar configuración del cliente en QuickBooks/sistema origen"
fi

echo
echo "🎯 Diagnóstico completado: $(date)"
