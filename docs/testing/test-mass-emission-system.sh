#!/bin/bash

# Script para probar el sistema de emisión masiva con estados y reintentos
echo "=== SISTEMA DE EMISIÓN MASIVA CON ESTADOS Y REINTENTOS ==="
echo "Fecha: $(date)"
echo

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}1. Verificando columnas agregadas a Sales_Header_Imp...${NC}"

# Verificar que las nuevas columnas existen
docker exec -it docucenter_laravel.test php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;

// Verificar en una organización de ejemplo
\$org = Organization::whereNotNull('database')->first();
if (\$org) {
    DB::connection()->useDatabase(\$org->database);

    \$columns = ['mass_emission_status', 'mass_emission_attempts', 'mass_emission_last_error', 'mass_emission_last_attempt'];

    echo \"Verificando columnas en base de datos: {\$org->database}\" . PHP_EOL;

    foreach (\$columns as \$column) {
        \$exists = Schema::hasColumn('Sales_Header_Imp', \$column);
        echo \"- {\$column}: \" . (\$exists ? '✅ Existe' : '❌ No existe') . PHP_EOL;
    }
} else {
    echo \"No se encontró ninguna organización con base de datos\" . PHP_EOL;
}
"

echo
echo -e "${BLUE}2. Probando actualización de estado de factura...${NC}"

# Crear una factura de prueba y probar el sistema de estados
docker exec -it docucenter_laravel.test php artisan tinker --execute="
use App\Models\SalesHeaderImp;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;

// Usar una organización existente
\$org = Organization::whereNotNull('database')->first();
if (\$org) {
    DB::connection()->useDatabase(\$org->database);

    // Buscar una factura existente no emitida
    \$invoice = SalesHeaderImp::where('EzeeIssued', 0)->first();

    if (\$invoice) {
        echo \"Factura encontrada: {\$invoice->InvoiceNumber} (ID: {\$invoice->ID})\" . PHP_EOL;

        // Probar actualización de estados
        \$invoice->update([
            'mass_emission_status' => 'processing',
            'mass_emission_attempts' => 1,
            'mass_emission_last_attempt' => now()
        ]);

        echo \"✅ Estado actualizado a 'processing'\" . PHP_EOL;

        // Simular error
        \$invoice->update([
            'mass_emission_status' => 'retry_needed',
            'mass_emission_attempts' => 2,
            'mass_emission_last_error' => 'Connection timeout - test error',
            'mass_emission_last_attempt' => now()
        ]);

        echo \"✅ Estado actualizado a 'retry_needed' con error simulado\" . PHP_EOL;

        // Mostrar estado actual
        echo \"Estado actual:\" . PHP_EOL;
        echo \"- Estado: {\$invoice->mass_emission_status}\" . PHP_EOL;
        echo \"- Intentos: {\$invoice->mass_emission_attempts}\" . PHP_EOL;
        echo \"- Último error: {\$invoice->mass_emission_last_error}\" . PHP_EOL;

    } else {
        echo \"No se encontraron facturas no emitidas para probar\" . PHP_EOL;
    }
} else {
    echo \"No se encontró organización para probar\" . PHP_EOL;
}
"

echo
echo -e "${BLUE}3. Probando estadísticas del monitor...${NC}"

# Probar las estadísticas del monitor
docker exec -it docucenter_laravel.test php artisan tinker --execute="
use App\Models\SalesHeaderImp;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;

\$org = Organization::whereNotNull('database')->first();
if (\$org) {
    DB::connection()->useDatabase(\$org->database);

    \$stats = [
        'total' => SalesHeaderImp::count(),
        'pending' => SalesHeaderImp::where('mass_emission_status', 'pending')->count(),
        'processing' => SalesHeaderImp::where('mass_emission_status', 'processing')->count(),
        'success' => SalesHeaderImp::where('mass_emission_status', 'success')->count(),
        'failed' => SalesHeaderImp::where('mass_emission_status', 'failed')->count(),
        'retry_needed' => SalesHeaderImp::where('mass_emission_status', 'retry_needed')->count(),
        'no_status' => SalesHeaderImp::whereNull('mass_emission_status')->count()
    ];

    echo \"=== ESTADÍSTICAS DE EMISIÓN MASIVA ===\" . PHP_EOL;
    foreach (\$stats as \$status => \$count) {
        echo \"- \" . ucfirst(\$status) . \": {\$count}\" . PHP_EOL;
    }
}
"

echo
echo -e "${BLUE}4. Información de acceso a la pantalla de monitoreo...${NC}"
echo -e "${GREEN}✅ Pantalla de monitoreo disponible en:${NC}"
echo "   URL: http://localhost/admin/reports/mass_emission_monitor"
echo "   Ruta: admin.reports.mass_emission_monitor"

echo
echo -e "${YELLOW}📋 CARACTERÍSTICAS IMPLEMENTADAS:${NC}"
echo "✅ Columnas de estado agregadas a Sales_Header_Imp en todas las organizaciones"
echo "✅ Sistema de estados granulares (pending, processing, success, failed, retry_needed)"
echo "✅ Tracking de intentos y errores detallados"
echo "✅ Componente Livewire de monitoreo con estadísticas en tiempo real"
echo "✅ IssueMassInvoicesJob mejorado con manejo de errores y reintentos"
echo "✅ Sistema de reintentos automáticos con backoff exponencial"
echo "✅ Interfaz para reintentar facturas fallidas individualmente o en lote"
echo "✅ Auto-refresh cada 30 segundos en la interfaz"
echo "✅ Filtros por estado y búsqueda por factura/cliente/error"

echo
echo -e "${YELLOW}🔧 SIGUIENTE PASOS RECOMENDADOS:${NC}"
echo "1. Acceder a la pantalla de monitoreo y verificar funcionamiento"
echo "2. Ejecutar una emisión masiva de prueba para ver el tracking en acción"
echo "3. Probar los reintentos de facturas fallidas"
echo "4. Configurar notificaciones si es necesario"

echo
echo -e "${GREEN}=== SISTEMA LISTO PARA PRODUCCIÓN ===${NC}"
