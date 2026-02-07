#!/bin/bash

# Script de Validación Rápida - Campo EzeeIssued
# Uso: ./quick-ezeeissued-check.sh [org_id] [sale_id]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCUCENTER_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

ORG_ID=${1:-"5"}
SALE_ID=${2:-"1"}

echo "🔍 Quick EzeeIssued Check"
echo "========================="

# Verificar si Docker está ejecutándose
if ! docker ps -q --filter name=docucenter_laravel.test >/dev/null 2>&1; then
    echo "❌ DocuCenter container not running"
    exit 1
fi

echo "Organization ID: $ORG_ID"
echo "Sale ID: $SALE_ID"
echo

# Ejecutar comando Artisan
echo "📊 Running Artisan test..."
docker exec -it docucenter_laravel.test php artisan test:ezeeissued-field "$ORG_ID" "$SALE_ID" 2>/dev/null || {
    echo "❌ Error executing Artisan command"
    exit 1
}

echo
echo "✅ Quick check completed"
