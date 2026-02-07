#!/bin/bash

# Script de Prueba - Job CreateIntuitBillsJob
# Ubicación: docs/testing/test-create-intuit-bills.sh
# Propósito: Probar el job de creación de Bills en QuickBooks

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "=== PRUEBAS DE CREACIÓN DE BILLS QUICKBOOKS ==="
echo "Proyecto: DocuCenter"
echo "Job: CreateIntuitBillsJob"
echo "Fecha: $(date)"
echo "================================================"
echo

# Función para mostrar ayuda
show_help() {
    cat << EOF
Uso: $0 [OPCIÓN]

OPCIONES:
    setup       - Ejecutar migraciones y preparar entorno
    validate    - Validar configuración y datos de prueba
    test        - Ejecutar job de prueba (organización específica)
    monitor     - Monitorear logs del job en tiempo real
    cleanup     - Limpiar datos de prueba
    help        - Mostrar esta ayuda

EJEMPLOS:
    $0 setup       # Preparar entorno
    $0 validate    # Verificar configuración
    $0 test        # Probar con organización de prueba
    $0 monitor     # Ver logs en tiempo real

NOTAS:
    • Requiere configuración de variables ACI_EMAIL y ACI_PASSWORD
    • Usa datos de Purchase_Header_Imp y Purchase_Detail_Imp
    • Crea Bills en QuickBooks vía API externa
EOF
}

# Función para ejecutar setup
run_setup() {
    echo "1. CONFIGURANDO ENTORNO PARA PRUEBAS"
    echo "====================================="

    # Ejecutar migración
    echo "🔄 Ejecutando migraciones..."
    php artisan migrate --force

    # Verificar variables de entorno
    echo "🔍 Verificando variables de entorno..."
    php artisan tinker --execute="
        \$email = env('ACI_EMAIL');
        \$password = env('ACI_PASSWORD');
        \$apiUrl = env('ACI_API_URL');

        echo \"ACI_EMAIL: \" . (\$email ? '✅ Configurado' : '❌ Faltante') . \"\\n\";
        echo \"ACI_PASSWORD: \" . (\$password ? '✅ Configurado' : '❌ Faltante') . \"\\n\";
        echo \"ACI_API_URL: \" . (\$apiUrl ?: 'https://api.acicloud.com (default)') . \"\\n\";
    "

    echo "✅ Setup completado"
    echo
}

# Función para validar configuración
validate_config() {
    echo "2. VALIDANDO CONFIGURACIÓN Y DATOS"
    echo "==================================="

    # Verificar conexiones ACIcloud
    echo "🔍 Verificando conexiones ACIcloud..."
    php artisan tinker --execute="
        \$connections = \App\Models\Connection::where('application', 'acicloud')
            ->whereHas('organization')
            ->count();
        echo \"Conexiones ACIcloud encontradas: \$connections\\n\";

        if (\$connections === 0) {
            echo \"⚠️  No hay conexiones ACIcloud configuradas\\n\";
        }
    "

    # Verificar compras pendientes
    echo "🔍 Verificando compras pendientes por organización..."
    php artisan tinker --execute="
        \$connections = \App\Models\Connection::where('application', 'acicloud')
            ->whereHas('organization')
            ->with('organization')
            ->get();

        foreach (\$connections as \$conn) {
            \$org = \$conn->organization;
            echo \"\\nOrganización: {\$org->name} (ID: {\$org->id})\\n\";
            echo \"Database: {\$org->database}\\n\";

            try {
                DB::connection()->useDatabase(\$org->database);

                if (!Schema::hasTable('Purchase_Header_Imp')) {
                    echo \"❌ Tabla Purchase_Header_Imp no existe\\n\";
                    continue;
                }

                \$total = \App\Models\PurchaseHeaderImp::count();
                \$pending = \App\Models\PurchaseHeaderImp::where('Enviado', false)->count();
                \$syncPending = \App\Models\PurchaseHeaderImp::where('Enviado', false)
                    ->where(function(\$q) {
                        \$q->whereNull('quickbooks_sync_status')
                          ->orWhere('quickbooks_sync_status', '!=', 'completed');
                    })->count();

                echo \"  • Total compras: \$total\\n\";
                echo \"  • No enviadas: \$pending\\n\";
                echo \"  • Pendientes sync QB: \$syncPending\\n\";

            } catch (Exception \$e) {
                echo \"❌ Error accediendo a BD: \" . \$e->getMessage() . \"\\n\";
            }
        }

        // Volver a BD principal
        DB::connection()->useDatabase(env('DB_DATABASE'));
    "

    echo "✅ Validación completada"
    echo
}

# Función para ejecutar prueba
run_test() {
    echo "3. EJECUTANDO PRUEBA DEL JOB"
    echo "============================"

    # Solicitar ID de organización
    read -p "Ingresa el ID de organización para probar (Enter para todas): " org_id

    if [[ -n "$org_id" && "$org_id" =~ ^[0-9]+$ ]]; then
        echo "🚀 Ejecutando job para organización ID: $org_id"
        php artisan word:create-intuit-bills --organization_id=$org_id
    else
        echo "🚀 Ejecutando job para todas las organizaciones"
        php artisan word:create-intuit-bills
    fi

    echo "✅ Job despachado - revisar logs para seguimiento"
    echo
}

# Función para monitorear logs
monitor_logs() {
    echo "4. MONITOREANDO LOGS DEL JOB"
    echo "============================="
    echo "Presiona Ctrl+C para detener el monitoreo"
    echo

    # Seguir logs en tiempo real filtrando por el job
    tail -f storage/logs/laravel.log | grep --color=auto "CreateIntuitBillsJob\|create-intuit-bills"
}

# Función para limpiar datos de prueba
cleanup_test_data() {
    echo "5. LIMPIANDO DATOS DE PRUEBA"
    echo "============================="

    read -p "¿Resetear estados de sincronización QB? (y/N): " reset_confirm

    if [[ "$reset_confirm" =~ ^[Yy]$ ]]; then
        echo "🧹 Reseteando estados de sincronización..."

        php artisan tinker --execute="
            \$connections = \App\Models\Connection::where('application', 'acicloud')
                ->whereHas('organization')
                ->with('organization')
                ->get();

            foreach (\$connections as \$conn) {
                \$org = \$conn->organization;

                try {
                    DB::connection()->useDatabase(\$org->database);

                    if (Schema::hasTable('Purchase_Header_Imp')) {
                        \$updated = \App\Models\PurchaseHeaderImp::whereNotNull('quickbooks_sync_status')
                            ->update([
                                'quickbooks_sync_status' => null,
                                'quickbooks_bill_id' => null,
                                'quickbooks_sync_error' => null,
                                'quickbooks_sync_attempts' => 0,
                                'quickbooks_sync_started_at' => null,
                                'quickbooks_sync_completed_at' => null,
                                'Enviado' => false,
                                'Error' => false,
                                'ErrorPT' => null
                            ]);

                        echo \"Org {\$org->id}: \$updated registros reseteados\\n\";
                    }

                } catch (Exception \$e) {
                    echo \"Error en org {\$org->id}: \" . \$e->getMessage() . \"\\n\";
                }
            }

            DB::connection()->useDatabase(env('DB_DATABASE'));
            echo \"✅ Cleanup completado\\n\";
        "
    else
        echo "❌ Cleanup cancelado"
    fi

    echo
}

# Función para mostrar estadísticas
show_stats() {
    echo "📊 ESTADÍSTICAS DE SINCRONIZACIÓN"
    echo "=================================="

    php artisan tinker --execute="
        \$connections = \App\Models\Connection::where('application', 'acicloud')
            ->whereHas('organization')
            ->with('organization')
            ->get();

        \$totalOrgs = 0;
        \$totalCompras = 0;
        \$totalCompleted = 0;
        \$totalFailed = 0;
        \$totalPending = 0;

        foreach (\$connections as \$conn) {
            \$org = \$conn->organization;
            \$totalOrgs++;

            try {
                DB::connection()->useDatabase(\$org->database);

                if (Schema::hasTable('Purchase_Header_Imp')) {
                    \$compras = \App\Models\PurchaseHeaderImp::count();
                    \$completed = \App\Models\PurchaseHeaderImp::where('quickbooks_sync_status', 'completed')->count();
                    \$failed = \App\Models\PurchaseHeaderImp::where('quickbooks_sync_status', 'failed')->count();
                    \$pending = \App\Models\PurchaseHeaderImp::where('Enviado', false)
                        ->where(function(\$q) {
                            \$q->whereNull('quickbooks_sync_status')
                              ->orWhere('quickbooks_sync_status', '!=', 'completed');
                        })->count();

                    \$totalCompras += \$compras;
                    \$totalCompleted += \$completed;
                    \$totalFailed += \$failed;
                    \$totalPending += \$pending;
                }

            } catch (Exception \$e) {
                // Ignorar errores de acceso a BD
            }
        }

        echo \"📈 Resumen General:\\n\";
        echo \"   • Organizaciones: \$totalOrgs\\n\";
        echo \"   • Total compras: \$totalCompras\\n\";
        echo \"   • Sincronizadas: \$totalCompleted\\n\";
        echo \"   • Fallidas: \$totalFailed\\n\";
        echo \"   • Pendientes: \$totalPending\\n\";

        if (\$totalCompras > 0) {
            \$successRate = round((\$totalCompleted / \$totalCompras) * 100, 1);
            echo \"   • Tasa de éxito: \$successRate%\\n\";
        }

        DB::connection()->useDatabase(env('DB_DATABASE'));
    "
}

# Función principal
main() {
    # Verificar que estamos en el directorio correcto
    if [[ ! -f "$PROJECT_ROOT/artisan" ]]; then
        echo "❌ ERROR: No se encontró artisan en $PROJECT_ROOT"
        echo "Ejecuta este script desde el directorio docs/testing/"
        exit 1
    fi

    # Cambiar al directorio del proyecto
    cd "$PROJECT_ROOT"

    case "${1:-help}" in
        setup)
            run_setup
            ;;
        validate)
            validate_config
            ;;
        test)
            run_test
            ;;
        monitor)
            monitor_logs
            ;;
        cleanup)
            cleanup_test_data
            ;;
        stats)
            show_stats
            ;;
        help|*)
            show_help
            ;;
    esac
}

# Ejecutar función principal con argumentos
main "$@"
