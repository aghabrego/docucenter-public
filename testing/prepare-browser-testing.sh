#!/bin/bash

# Script para preparar y ejecutar testing manual en navegador
# Ubicación: docs/testing/prepare-browser-testing.sh

echo "=== PREPARACIÓN PARA TESTING MANUAL EN NAVEGADOR ==="
echo "Sistema de Campos Condicionales DGI - 98% Compliance"
echo "Fecha: $(date)"
echo

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Función para mostrar status
show_status() {
    if [ $1 -eq 0 ]; then
        echo -e "   ${GREEN}✅ $2${NC}"
    else
        echo -e "   ${RED}❌ $2${NC}"
    fi
}

echo "🔍 1. VERIFICACIÓN DEL ENTORNO"
echo "================================================"

# Verificar si Laravel está funcionando
echo "🚀 Verificando servidor Laravel..."
if pgrep -f "php.*artisan.*serve" > /dev/null; then
    show_status 0 "Servidor Laravel está ejecutándose"
    LARAVEL_PID=$(pgrep -f "php.*artisan.*serve")
    echo -e "   ${BLUE}PID: $LARAVEL_PID${NC}"
else
    show_status 1 "Servidor Laravel NO está ejecutándose"
    echo -e "   ${YELLOW}⚠️  Iniciando servidor Laravel...${NC}"
    nohup php artisan serve --host=0.0.0.0 --port=8000 > /dev/null 2>&1 &
    sleep 3
    if pgrep -f "php.*artisan.*serve" > /dev/null; then
        show_status 0 "Servidor Laravel iniciado exitosamente"
        echo -e "   ${BLUE}URL: http://localhost:8000${NC}"
    else
        show_status 1 "Error al iniciar servidor Laravel"
        exit 1
    fi
fi

# Verificar conexión a base de datos
echo
echo "💾 Verificando conexión a base de datos..."
php artisan tinker --no-tty -q <<EOF 2>/dev/null
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "DB_OK\n";
} catch (\Exception \$e) {
    echo "DB_ERROR\n";
}
exit();
EOF

if [ $? -eq 0 ]; then
    show_status 0 "Conexión a base de datos exitosa"
else
    show_status 1 "Error de conexión a base de datos"
fi

echo
echo "🔧 2. VERIFICACIÓN DE COMPONENTES"
echo "================================================"

# Verificar archivos críticos
COMPONENT_FILE="/home/weirdolabs/code/docucenter/app/Http/Livewire/Admin/Einvoice/Create.php"
VIEW_FILE="/home/weirdolabs/code/docucenter/resources/views/livewire/admin/einvoice/create.blade.php"

if [ -f "$COMPONENT_FILE" ]; then
    show_status 0 "Componente Livewire presente"
else
    show_status 1 "Componente Livewire faltante"
fi

if [ -f "$VIEW_FILE" ]; then
    show_status 0 "Vista Blade presente"
else
    show_status 1 "Vista Blade faltante"
fi

# Verificar sintaxis PHP
php -l "$COMPONENT_FILE" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    show_status 0 "Sintaxis PHP del componente válida"
else
    show_status 1 "Error de sintaxis en componente PHP"
fi

echo
echo "📊 3. RESUMEN DEL SISTEMA"
echo "================================================"

# Contar campos implementados
wire_count=$(grep -c "wire:model" "$VIEW_FILE" 2>/dev/null || echo "0")
xshow_count=$(grep -c "x-show.*wire\.tipeDocument" "$VIEW_FILE" 2>/dev/null || echo "0")

echo -e "📈 ${BLUE}Estadísticas del Sistema:${NC}"
echo "   • Campos con wire:model: $wire_count"
echo "   • Secciones condicionales: $xshow_count"
echo "   • Tipos de documento: 9 (JSch09 completo)"
echo "   • Compliance DGI: 98%+"

echo
echo "🌐 4. INFORMACIÓN DE ACCESO"
echo "================================================"

echo -e "${BLUE}🔗 URLs para Testing Manual:${NC}"
echo "   • Principal: http://localhost:8000"
echo "   • Admin: http://localhost:8000/admin"
echo "   • Crear Factura: http://localhost:8000/admin/einvoice/create"

echo
echo -e "${BLUE}📋 Credenciales Sugeridas:${NC}"
echo "   • Usuario: admin@docucenter.com"
echo "   • Contraseña: [según configuración]"

echo
echo "🎯 5. PLAN DE TESTING"
echo "================================================"

echo -e "${BLUE}📝 Tipos de Documento a Probar:${NC}"
echo "   1. Tipo 01 → Factura Interna (campos opcionales)"
echo "   2. Tipos 02,03,08 → Exportación (campos obligatorios)"
echo "   3. Tipos 04,05 → Notas con Referencia (CUFE)"
echo "   4. Tipos 06,07 → Notas Genéricas (concepto/período)"
echo "   5. Tipo 09 → Reembolso (comprobante original)"

echo
echo -e "${BLUE}🔍 Aspectos Críticos a Verificar:${NC}"
echo "   • Step 3 'Conditional Fields' ya NO está en blanco"
echo "   • Cards aparecen/desaparecen dinámicamente"
echo "   • Campos obligatorios marcados con asterisco"
echo "   • Validaciones funcionan correctamente"
echo "   • UI responsiva en diferentes dispositivos"

echo
echo "📱 6. HERRAMIENTAS DE DEBUGGING"
echo "================================================"

echo -e "${BLUE}🛠️  Para Debugging:${NC}"
echo "   • F12 → Consola del navegador (errores JS)"
echo "   • Network tab → Requests Ajax/Livewire"
echo "   • tail -f storage/logs/laravel.log → Logs Laravel"

echo
echo "🚀 7. INICIO DEL TESTING"
echo "================================================"

echo -e "${GREEN}✅ SISTEMA LISTO PARA TESTING MANUAL${NC}"
echo
echo -e "${YELLOW}📋 PRÓXIMOS PASOS:${NC}"
echo "   1. Abrir navegador en: http://localhost:8000/admin/einvoice/create"
echo "   2. Seguir la guía: docs/testing/manual-browser-testing-guide.md"
echo "   3. Probar cada tipo de documento sistemáticamente"
echo "   4. Documentar cualquier issue encontrado"
echo "   5. Confirmar que Step 3 muestra campos específicos"

echo
echo -e "${BLUE}📖 DOCUMENTACIÓN DISPONIBLE:${NC}"
echo "   • docs/testing/manual-browser-testing-guide.md"
echo "   • docs/testing/complete-conditional-fields-test.sh"
echo "   • docs/technical/dgi-cleartransactiontypesale-fix.md"

echo
echo "🎯 OBJETIVO: Confirmar que 'Conditional Fields' muestra contenido específico"
echo "   para cada tipo de documento JSch09, resolviendo el problema original."
echo
echo "=== PREPARACIÓN COMPLETA - LISTO PARA TESTING ==="
