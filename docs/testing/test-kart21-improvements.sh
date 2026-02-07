#!/bin/bash

# Script para validar mejoras en Kart21Service
# Ubicación: docs/testing/test-kart21-improvements.sh
# Uso: ./docs/testing/test-kart21-improvements.sh

echo "🧪 === VALIDACIÓN DE MEJORAS KART21SERVICE ==="
echo ""

# Verificar si Docker está corriendo
if ! docker-compose ps | grep -q "laravel.test.*Up"; then
    echo "⚠️ Docker no está corriendo. Iniciando contenedores..."
    docker-compose up -d
    echo "✅ Contenedores iniciados"
    echo ""
fi

echo "🔧 Testing mejoras implementadas..."
echo ""

echo "📊 === TEST 1: PRECISIÓN DECIMAL ==="
docker-compose exec laravel.test php artisan test:kart21-service 6 | grep -E "(Subtotal|Total|Impuesto|Pagado)"

echo ""
echo "🔍 === TEST 2: CONFIGURACIÓN DECIMAL ==="
docker-compose exec laravel.test php artisan test:kart21-service 6 | grep -A 10 "Configuración decimal actual"

echo ""
echo "⚡ === TEST 3: RENDIMIENTO ==="
docker-compose exec laravel.test php artisan test:kart21-service 6 | grep "Tiempo de ejecución"

echo ""
echo "📝 === TEST 4: VERIFICAR LOGS ==="
echo "Verificando logs de discrepancias..."
docker-compose exec laravel.test tail -n 20 storage/logs/laravel.log | grep -i "discrepancia" || echo "No se encontraron logs de discrepancias recientes"

echo ""
echo "✅ === RESUMEN DE MEJORAS IMPLEMENTADAS ==="
echo "✅ Precisión decimal corregida: totals 2→3 decimales"
echo "✅ Análisis automático de discrepancias"
echo "✅ Logging de inconsistencias para debugging"
echo "✅ Preservación de precisión original de datos"
echo ""
echo "🎯 === PRÓXIMAS MEJORAS SUGERIDAS ==="
echo "1. 🔐 Validación de RUC (basada en MaxgymService)"
echo "2. 🚀 Integración de emisión (CreateFastJob trait)"
echo "3. 🔄 Análisis automático de transacciones complejas"
echo "4. 📊 Dashboard de monitoreo de discrepancias"

echo ""
echo "✅ Validación completada"
