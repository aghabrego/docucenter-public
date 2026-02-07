#!/bin/bash

# Wrapper para ejecutar el script de limpieza de webhooks usando sail
# Ejecutar desde el HOST: ./docs/testing/quickbooks-webhook-cleanup-wrapper.sh

echo "=================================================="
echo "🧹 LIMPIEZA DE CONFIGURACIONES WEBHOOK DUPLICADAS"
echo "=================================================="
echo ""
echo "Ejecutando dentro del contenedor..."
echo ""

# Detectar la ruta de sail
SAIL_PATH=""
if [ -f "./vendor/bin/sail" ]; then
    SAIL_PATH="./vendor/bin/sail"
elif [ -f "vendor/bin/sail" ]; then
    SAIL_PATH="vendor/bin/sail"
elif command -v sail &> /dev/null; then
    SAIL_PATH="sail"
else
    echo "❌ Error: sail no encontrado. Intentando con docker compose..."
    # Fallback: usar docker compose directamente
    CONTAINER_NAME=$(docker compose ps -q laravel.test 2>/dev/null || docker compose ps -q app 2>/dev/null)
    if [ -z "$CONTAINER_NAME" ]; then
        echo "❌ Error: No se pudo encontrar el contenedor. Asegúrate de que Docker esté corriendo."
        exit 1
    fi
    docker compose exec laravel.test php docs/testing/quickbooks-webhook-cleanup-duplicate-internal.php
    exit $?
fi

# Ejecutar el script PHP dentro del contenedor usando sail
$SAIL_PATH php docs/testing/quickbooks-webhook-cleanup-duplicate-internal.php
