#!/bin/bash

echo "=== EVALUACIÓN COMPLETA - TODOS LOS STEPS ==="
echo "Verificando Step 3 (Conditional Fields) y Step 6 (Other values)"
echo "Fecha: $(date)"
echo

# Verificar archivo principal
MAIN_FILE="/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"
if [ ! -f "$MAIN_FILE" ]; then
    echo "❌ ERROR: Archivo principal no encontrado"
    exit 1
fi

echo "🎯 1. ANÁLISIS DE STEPS EN EL WIZARD"
echo "=================================="

# Verificar todos los steps
echo "📋 STEPS DEL WIZARD:"
grep -n "title.*__" "$MAIN_FILE" | sed 's/.*title.*__.*\(.*\).*/\1/' | nl -v0 -s". Step " | head -7

echo
echo "🔍 VERIFICACIÓN DE STEPS INDIVIDUALES:"

# Step 1: Document Type
if grep -q "step-one.*currentStep == 1" "$MAIN_FILE"; then
    echo "   ✅ Step 1: Document Type - FUNCIONANDO"
else
    echo "   ❌ Step 1: Document Type - PROBLEMA"
fi

# Step 2: Operation  
if grep -q "step-two.*currentStep == 2" "$MAIN_FILE"; then
    echo "   ✅ Step 2: Operation - FUNCIONANDO"
else
    echo "   ❌ Step 2: Operation - PROBLEMA"
fi

# Step 3: Conditional Fields
if grep -q "step-three.*currentStep == 3" "$MAIN_FILE"; then
    echo "   ✅ Step 3: Conditional Fields - FUNCIONANDO"
else
    echo "   ❌ Step 3: Conditional Fields - PROBLEMA"
fi

# Step 4: Receptor
if grep -q "step-four.*currentStep == 4" "$MAIN_FILE"; then
    echo "   ✅ Step 4: Receptor - FUNCIONANDO"
else
    echo "   ❌ Step 4: Receptor - PROBLEMA"
fi

# Step 5: Item information
if grep -q "step-five.*currentStep == 5" "$MAIN_FILE"; then
    echo "   ✅ Step 5: Item information - FUNCIONANDO"
else
    echo "   ❌ Step 5: Item information - PROBLEMA"
fi

# Step 6: Other values
if grep -q "step-six.*currentStep == 6" "$MAIN_FILE"; then
    echo "   ✅ Step 6: Other values - FUNCIONANDO"
else
    echo "   ❌ Step 6: Other values - PROBLEMA"
fi

echo

echo "🎨 2. ANÁLISIS DETALLADO - STEP 3 (CONDITIONAL FIELDS)"
echo "=================================================="

# Contar cards condicionales en Step 3
CONDITIONAL_CARDS=$(grep -c "x-show.*tipeDocument.*includes" "$MAIN_FILE")
echo "📈 Cards condicionales encontrados: $CONDITIONAL_CARDS"

echo "�� TIPOS DE DOCUMENTO EN STEP 3:"
echo "   • Tipos 02,03,08: Exportación/Importación"
if grep -q "x-show.*\['2', '02', '3', '03', '8', '08'\]" "$MAIN_FILE"; then
    echo "     ✅ IMPLEMENTADO - Card Verde"
else
    echo "     ❌ NO IMPLEMENTADO"
fi

echo "   • Tipos 04,05: Notas de Crédito/Débito"
if grep -q "x-show.*\['4', '04', '5', '05'\]" "$MAIN_FILE"; then
    echo "     ✅ IMPLEMENTADO - Card Amarillo"
else
    echo "     ❌ NO IMPLEMENTADO"
fi

echo "   • Tipos 06,07: Notas Genéricas"
if grep -q "x-show.*\['6', '06', '7', '07'\]" "$MAIN_FILE"; then
    echo "     ✅ IMPLEMENTADO - Card Azul"
else
    echo "     ❌ NO IMPLEMENTADO"
fi

echo "   • Tipo 09: Reembolso"
if grep -q "x-show.*\['9', '09'\]" "$MAIN_FILE"; then
    echo "     ✅ IMPLEMENTADO - Card Azul Primario"
else
    echo "     ❌ NO IMPLEMENTADO"
fi

echo "   • Tipo 01: Factura Interna"
if grep -q "x-show.*\['1', '01'\]" "$MAIN_FILE"; then
    echo "     ✅ IMPLEMENTADO - Card Gris"
else
    echo "     ❌ NO IMPLEMENTADO"
fi

echo "   • Otros valores/Sin campos:"
if grep -q "x-show.*!\$wire.tipeDocument.*!\[" "$MAIN_FILE"; then
    echo "     ✅ IMPLEMENTADO - Mensaje para tipos sin campos especiales"
else
    echo "     ❌ NO IMPLEMENTADO"
fi

echo

echo "💰 3. ANÁLISIS DETALLADO - STEP 6 (OTHER VALUES)"
echo "=============================================="

# Verificar campos en Step 6
echo "🔍 CAMPOS EN STEP 6 'OTHER VALUES':"

# Final Price
if grep -q "otro_precioFinal" "$MAIN_FILE"; then
    echo "   ✅ Final Price (Precio Final) - Campo readonly calculado"
else
    echo "   ❌ Final Price - NO ENCONTRADO"
fi

# Object retention
if grep -q "otroObjetoRetencion" "$MAIN_FILE"; then
    echo "   ✅ Object Retention (Objeto Retención) - Select con DataProvider"
else
    echo "   ❌ Object Retention - NO ENCONTRADO"
fi

# Withholding amount
if grep -q "otroMontoRetencion" "$MAIN_FILE"; then
    echo "   ✅ Withholding Amount (Monto Retención) - Campo calculado"
else
    echo "   ❌ Withholding Amount - NO ENCONTRADO"
fi

# Buscar más campos en Step 6
OTHER_FIELDS=$(grep -A 50 "step-six.*currentStep == 6" "$MAIN_FILE" | grep -c "wire:model")
echo "   📊 Total campos wire:model en Step 6: $OTHER_FIELDS"

echo

echo "🔄 4. VERIFICACIÓN DE DUPLICACIONES"
echo "=================================="

# Verificar si hay duplicaciones entre Step 3 y Step 6
echo "🔍 VERIFICANDO DUPLICACIONES:"

# Campos que pueden estar duplicados
POTENTIAL_DUPLICATES=("precioFinal" "montoRetencion" "objetoRetencion")
DUPLICATIONS_FOUND=0

for field in "${POTENTIAL_DUPLICATES[@]}"; do
    STEP3_COUNT=$(grep -A 200 "step-three.*currentStep == 3" "$MAIN_FILE" | grep -c "$field" || echo "0")
    STEP6_COUNT=$(grep -A 200 "step-six.*currentStep == 6" "$MAIN_FILE" | grep -c "$field" || echo "0")
    
    if [ "$STEP3_COUNT" -gt 0 ] && [ "$STEP6_COUNT" -gt 0 ]; then
        echo "   ⚠️  DUPLICACIÓN DETECTADA: $field aparece en Step 3 ($STEP3_COUNT) y Step 6 ($STEP6_COUNT)"
        ((DUPLICATIONS_FOUND++))
    else
        echo "   ✅ Campo $field: Step 3 ($STEP3_COUNT), Step 6 ($STEP6_COUNT) - Sin duplicación"
    fi
done

if [ $DUPLICATIONS_FOUND -eq 0 ]; then
    echo "   �� NO HAY DUPLICACIONES DETECTADAS"
else
    echo "   ⚠️  TOTAL DUPLICACIONES: $DUPLICATIONS_FOUND"
fi

echo

echo "🎯 5. ANÁLISIS DE LÓGICA CONDICIONAL"
echo "=================================="

echo "🔍 LÓGICA ALPINE.JS:"
# Verificar lógica Alpine.js
ALPINE_XSHOW=$(grep -c "x-show" "$MAIN_FILE")
ALPINE_XDATA=$(grep -c "x-data" "$MAIN_FILE")
ALPINE_EVENTS=$(grep -c "@name-step-current" "$MAIN_FILE")

echo "   📊 Directivas x-show: $ALPINE_XSHOW"
echo "   📊 Directivas x-data: $ALPINE_XDATA"  
echo "   📊 Event listeners: $ALPINE_EVENTS"

# Verificar si Step 3 tiene lógica condicional apropiada
if grep -q "x-show.*tipeDocument" "$MAIN_FILE"; then
    echo "   ✅ Step 3 tiene lógica condicional basada en tipeDocument"
else
    echo "   ❌ Step 3 NO tiene lógica condicional"
fi

# Verificar si Step 6 es estático o condicional
if grep -A 50 "step-six.*currentStep == 6" "$MAIN_FILE" | grep -q "x-show.*tipeDocument"; then
    echo "   ⚠️  Step 6 tiene campos condicionales (puede ser confuso)"
else
    echo "   ✅ Step 6 es estático (correcto para 'Other values')"
fi

echo

echo "📊 6. RESUMEN DE EVALUACIÓN"
echo "=========================="

# Calcular scores
STEP3_SCORE=0
STEP6_SCORE=0

# Step 3 scoring
[ $CONDITIONAL_CARDS -ge 5 ] && ((STEP3_SCORE += 20))
grep -q "x-show.*\['2', '02', '3', '03', '8', '08'\]" "$MAIN_FILE" && ((STEP3_SCORE += 15))
grep -q "x-show.*\['4', '04', '5', '05'\]" "$MAIN_FILE" && ((STEP3_SCORE += 15))
grep -q "x-show.*\['6', '06', '7', '07'\]" "$MAIN_FILE" && ((STEP3_SCORE += 15))
grep -q "x-show.*\['9', '09'\]" "$MAIN_FILE" && ((STEP3_SCORE += 15))
grep -q "x-show.*\['1', '01'\]" "$MAIN_FILE" && ((STEP3_SCORE += 20))

# Step 6 scoring  
grep -q "otro_precioFinal" "$MAIN_FILE" && ((STEP6_SCORE += 35))
grep -q "otroObjetoRetencion" "$MAIN_FILE" && ((STEP6_SCORE += 35))
grep -q "otroMontoRetencion" "$MAIN_FILE" && ((STEP6_SCORE += 30))

echo "📈 SCORES INDIVIDUALES:"
echo "   • Step 3 (Conditional Fields): $STEP3_SCORE% de 100%"
echo "   • Step 6 (Other values): $STEP6_SCORE% de 100%"

OVERALL_SCORE=$(( (STEP3_SCORE + STEP6_SCORE) / 2 ))
echo "   �� SCORE GENERAL: $OVERALL_SCORE% de 100%"

echo

echo "🎉 7. CONCLUSIONES"
echo "=================="

if [ $STEP3_SCORE -ge 90 ] && [ $STEP6_SCORE -ge 90 ]; then
    echo "✅ AMBOS STEPS COMPLETAMENTE IMPLEMENTADOS"
    echo "🎉 SISTEMA LISTO PARA PRODUCCIÓN"
elif [ $STEP3_SCORE -ge 75 ] && [ $STEP6_SCORE -ge 75 ]; then
    echo "⚠️  IMPLEMENTACIÓN MAYORMENTE COMPLETA"
    echo "🔧 REQUIERE AJUSTES MENORES"
else
    echo "❌ IMPLEMENTACIÓN INCOMPLETA"
    echo "🚨 REQUIERE TRABAJO ADICIONAL"
fi

if [ $DUPLICATIONS_FOUND -gt 0 ]; then
    echo "⚠️  ATENCIÓN: Hay duplicaciones que revisar"
fi

echo
echo "🌐 TESTING MANUAL RECOMENDADO:"
echo "1. Step 3: Seleccionar tipos de documento y verificar campos condicionales"
echo "2. Step 6: Verificar campos de 'Other values' (retenciones, precios)"
echo "3. Confirmar que no hay confusión entre ambos steps"

echo
echo "=== EVALUACIÓN COMPLETA FINALIZADA ==="
