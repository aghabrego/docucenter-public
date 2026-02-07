#!/bin/bash

# Script de Testing - Módulo SQL Server
# Ubicación: docs/testing/test-sql-server-module.sh

echo "🧪 Testing Módulo SQL Server - DocuCenter"
echo "=========================================="

# Cambiar al directorio del proyecto
cd /home/weirdolabs/code/docucenter

echo ""
echo "1. ✅ Verificando archivos de componentes Livewire..."
echo "----------------------------------------------------"

# Verificar componentes Livewire
components=(
    "app/Http/Livewire/Admin/SqlServer/STInvoices.php"
    "app/Http/Livewire/Admin/SqlServer/STVendorsList.php"
    "app/Http/Livewire/Admin/SqlServer/STCostOfGoodsList.php"
)

for component in "${components[@]}"; do
    if [ -f "$component" ]; then
        echo "✅ $component - EXISTS"
    else
        echo "❌ $component - MISSING"
    fi
done

echo ""
echo "2. ✅ Verificando vistas Blade..."
echo "--------------------------------"

# Verificar vistas Blade
views=(
    "resources/views/livewire/admin/sql-server/st-invoices.blade.php"
    "resources/views/livewire/admin/sql-server/st-vendors-list.blade.php"
    "resources/views/livewire/admin/sql-server/st-cost-of-goods-list.blade.php"
)

for view in "${views[@]}"; do
    if [ -f "$view" ]; then
        echo "✅ $view - EXISTS"
    else
        echo "❌ $view - MISSING"
    fi
done

echo ""
echo "3. ✅ Verificando rutas en web.php..."
echo "------------------------------------"

# Verificar rutas definidas
routes=(
    "sqlserver.configuration"
    "sqlserver.st_invoices"
    "sqlserver.st_vendors"
    "sqlserver.st_cost_of_goods"
)

for route in "${routes[@]}"; do
    if grep -q "$route" routes/web.php; then
        echo "✅ Route: admin.$route - DEFINED"
    else
        echo "❌ Route: admin.$route - MISSING"
    fi
done

echo ""
echo "4. ✅ Verificando modelos ST*..."
echo "------------------------------"

# Verificar modelos ST
models=(
    "app/Models/STInvoiceHeaders.php"
    "app/Models/STVendors.php"
    "app/Models/STCostOfGoods.php"
)

for model in "${models[@]}"; do
    if [ -f "$model" ]; then
        echo "✅ $model - EXISTS"
    else
        echo "❌ $model - MISSING"
    fi
done

echo ""
echo "5. ✅ Verificando sintaxis PHP..."
echo "--------------------------------"

# Verificar sintaxis de componentes
for component in "${components[@]}"; do
    if [ -f "$component" ]; then
        if php -l "$component" > /dev/null 2>&1; then
            echo "✅ Sintaxis OK: $component"
        else
            echo "❌ Error sintaxis: $component"
            php -l "$component"
        fi
    fi
done

echo ""
echo "6. ✅ Verificando configuración sidebar..."
echo "-----------------------------------------"

# Verificar configuración del sidebar
sidebar_file="resources/views/vendor/admin/layouts/child-sidebar-menu-custom.blade.php"
if grep -q "isModuleSqlServer" "$sidebar_file"; then
    echo "✅ Variable \$isModuleSqlServer definida en sidebar"
else
    echo "❌ Variable \$isModuleSqlServer NO encontrada en sidebar"
fi

if grep -q "SQL Server" "$sidebar_file"; then
    echo "✅ Grupo 'SQL Server' encontrado en sidebar"
else
    echo "❌ Grupo 'SQL Server' NO encontrado en sidebar"
fi

echo ""
echo "7. ✅ Testing Docker Container Status..."
echo "---------------------------------------"

# Verificar estado de contenedores Docker
if docker ps | grep -q "docucenter"; then
    echo "✅ Contenedores Docker activos"
    docker ps --format "table {{.Names}}\t{{.Status}}" | grep docucenter
else
    echo "❌ Contenedores Docker no están corriendo"
    echo "   Para iniciar: docker-compose up -d"
fi

echo ""
echo "8. 📋 Resumen Final"
echo "=================="

total_checks=0
passed_checks=0

# Contar archivos existentes
for file in "${components[@]}" "${views[@]}" "${models[@]}"; do
    total_checks=$((total_checks + 1))
    if [ -f "$file" ]; then
        passed_checks=$((passed_checks + 1))
    fi
done

# Contar rutas definidas
for route in "${routes[@]}"; do
    total_checks=$((total_checks + 1))
    if grep -q "$route" routes/web.php; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo "✅ Archivos verificados: $passed_checks/$total_checks"

if [ $passed_checks -eq $total_checks ]; then
    echo "🎉 ¡Todos los tests pasaron! El módulo SQL Server está listo."
    echo ""
    echo "📋 Próximos pasos recomendados:"
    echo "1. Configurar permisos en base de datos para roles de usuario"
    echo "2. Crear organizaciones de prueba con configuraciones SQL Server"
    echo "3. Testing funcional con datos reales"
    echo "4. Implementar componentes restantes (Theoretical Costs, Process Monitoring)"
else
    echo "⚠️  Algunos archivos faltan. Revisar la implementación."
fi

echo ""
echo "📖 Documentación: docs/technical/sql-server-web-interface-implementation.md"
echo "🌐 Rutas disponibles:"
echo "   - /admin/sqlserver/configuration"
echo "   - /admin/sqlserver/st_invoices"
echo "   - /admin/sqlserver/st_vendors"
echo "   - /admin/sqlserver/st_cost_of_goods"
echo ""
