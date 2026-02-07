#!/bin/bash

# Script de prueba para el comando de actualización de estados de emisión masiva
# Ubicación: docs/testing/test-mass-emission-update-states.sh

echo "=== PRUEBA: ACTUALIZACIÓN DE ESTADOS DE EMISIÓN MASIVA ==="
echo

# Verificar que estemos en Docker
if ! docker ps > /dev/null 2>&1; then
    echo "❌ Docker no está ejecutándose"
    exit 1
fi

CONTAINER_NAME="docucenter_laravel.test"

echo "📋 PASO 1: Verificar comando está disponible"
docker exec -it $CONTAINER_NAME php artisan list | grep "mass-emission:update-states"
if [ $? -eq 0 ]; then
    echo "✅ Comando encontrado"
else
    echo "❌ Comando no encontrado"
    exit 1
fi

echo
echo "📋 PASO 2: Mostrar ayuda del comando"
docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --help

echo
echo "📋 PASO 3: Ejecutar en modo dry-run (sin --force)"
echo "Esto mostrará qué cambios se harían sin ejecutarlos realmente"
docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states

echo
echo "📋 PASO 4: Ejecutar con --force para aplicar cambios"
echo "⚠️  Este paso MODIFICARÁ datos reales"
read -p "¿Continuar con la actualización real? (y/N): " confirm

if [[ $confirm =~ ^[Yy]$ ]]; then
    docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --force
    echo "✅ Actualización completada"
else
    echo "⏭️  Actualización omitida"
fi

echo
echo "📋 PASO 5: Verificar estados después de la actualización"
docker exec -it $CONTAINER_NAME php -r "
use App\Models\Organization;
use App\Models\SalesHeaderImp;
use Illuminate\Support\Facades\DB;

// Obtener primera organización
\$org = Organization::whereNotNull('database')->first();
if (\$org) {
    DB::connection()->useDatabase(\$org->database);

    \$stats = [
        'ezee_issued_true' => SalesHeaderImp::where('EzeeIssued', true)->count(),
        'ezee_issued_false' => SalesHeaderImp::where('EzeeIssued', false)->count(),
        'status_success' => SalesHeaderImp::where('mass_emission_status', 'success')->count(),
        'status_pending' => SalesHeaderImp::where('mass_emission_status', 'pending')->count(),
        'status_retry_needed' => SalesHeaderImp::where('mass_emission_status', 'retry_needed')->count(),
        'status_null' => SalesHeaderImp::whereNull('mass_emission_status')->count(),
    ];

    echo \"=== ESTADÍSTICAS POST-ACTUALIZACIÓN ===\" . PHP_EOL;
    echo \"Organización: {\$org->name}\" . PHP_EOL;
    echo \"Base de datos: {\$org->database}\" . PHP_EOL;
    echo \"EzeeIssued=true: {\$stats['ezee_issued_true']}\" . PHP_EOL;
    echo \"EzeeIssued=false: {\$stats['ezee_issued_false']}\" . PHP_EOL;
    echo \"Estado 'success': {\$stats['status_success']}\" . PHP_EOL;
    echo \"Estado 'pending': {\$stats['status_pending']}\" . PHP_EOL;
    echo \"Estado 'retry_needed': {\$stats['status_retry_needed']}\" . PHP_EOL;
    echo \"Sin estado: {\$stats['status_null']}\" . PHP_EOL;

    // Verificar lógica: facturas EzeeIssued=true deberían tener status=success
    \$mismatch = SalesHeaderImp::where('EzeeIssued', true)
        ->where('mass_emission_status', '!=', 'success')
        ->count();

    if (\$mismatch > 0) {
        echo \"❌ INCONSISTENCIA: {\$mismatch} facturas con EzeeIssued=true pero status!=success\" . PHP_EOL;
    } else {
        echo \"✅ CONSISTENCIA: Todas las facturas emitidas tienen status=success\" . PHP_EOL;
    }
}
"

echo
echo "📋 PASO 6: Probar filtros del comando"
echo "Probando con una organización específica..."

# Obtener ID de la primera organización
ORG_ID=$(docker exec -it $CONTAINER_NAME php -r "
use App\Models\Organization;
\$org = Organization::whereNotNull('database')->first();
echo \$org ? \$org->id : '';
" | tr -d '\r')

if [ ! -z "$ORG_ID" ]; then
    echo "Probando con organización ID: $ORG_ID"
    docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --organization=$ORG_ID --force
else
    echo "⚠️  No se encontró organización para probar"
fi

echo
echo "=== PRUEBAS COMPLETADAS ==="
echo
echo "💡 COMANDOS ÚTILES:"
echo "  Ver todas las organizaciones:"
echo "    docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --help"
echo
echo "  Actualizar solo desarrollo:"
echo "    docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --environment=development --force"
echo
echo "  Actualizar solo producción:"
echo "    docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --environment=production --force"
echo
echo "  Actualizar organización específica:"
echo "    docker exec -it $CONTAINER_NAME php artisan mass-emission:update-states --organization=1 --force"
