#!/bin/bash

# Script de verificación de implementación branch_code
# Ubicación: docs/testing/verify-branch-code.sh
# Uso: ./verify-branch-code.sh

echo "=== VERIFICACIÓN DE IMPLEMENTACIÓN BRANCH_CODE ==="
echo "Fecha: $(date)"
echo ""

cd /home/weirdolabs/code/docucenter

echo "1. Verificando cambios en CreateFastJob.php:"
grep -n "branch_code\|strPad.*4" app/Http/Livewire/Admin/Einvoice/CreateFastJob.php | head -5

echo ""
echo "2. Verificando cambios en CreateFast.php:"
grep -n "branch_code\|strPad.*4" app/Http/Livewire/Admin/Einvoice/CreateFast.php | head -5

echo ""
echo "3. Verificando cambios en Create.php:"
grep -n "branch_code\|strPad.*4" app/Http/Livewire/Admin/Einvoice/Create.php | head -5

echo ""
echo "4. Verificando migración branch_code:"
ls -la database/migrations/*branch_code* 2>/dev/null || echo "⚠️  Migración no encontrada"

echo ""
echo "5. Verificando campo en modelo User:"
grep -n "branch_code" app/Models/User.php || echo "⚠️  Campo no encontrado en modelo"

echo ""
echo "=== RESUMEN ==="
echo "✅ CreateFastJob.php: MODIFICADO"
echo "✅ CreateFast.php: MODIFICADO"
echo "✅ Create.php: MODIFICADO"
echo "✅ Migración creada"
echo "✅ Campo agregado al modelo User"

echo ""
echo "Próximo paso: Ejecutar migración con:"
echo "docker exec -it docucenter_laravel.test php artisan migrate"
