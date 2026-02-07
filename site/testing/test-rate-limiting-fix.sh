#!/bin/bash

# Script para testing de Rate Limiting HTTP 429 en UpdateIntuitFEJob
# Ubicación: /home/weirdolabs/code/docucenter/docs/testing/test-rate-limiting-fix.sh

echo "=== Test Rate Limiting HTTP 429 - UpdateIntuitFEJob ==="
echo "Fecha: $(date)"
echo ""

# Configuración
CONTAINER_NAME="docucenter-app-1"
ORG_ID=${1:-1}  # ID de organización (default: 1)
FE_ID=${2:-697}  # ID de FE a probar (default del error reportado: 697)

echo "📋 Configuración del Test:"
echo "  - Organización ID: $ORG_ID"
echo "  - FE ID: $FE_ID"
echo "  - Container: $CONTAINER_NAME"
echo ""

# Función para ejecutar comandos en Docker
run_docker() {
    local cmd="$1"
    echo "🐳 Ejecutando: $cmd"
    docker exec -it $CONTAINER_NAME bash -c "$cmd"
}

# Función para verificar logs específicos
check_rate_limit_logs() {
    echo "📊 Verificando logs de rate limiting..."
    run_docker "tail -50 /var/log/laravel.log | grep -E '(Rate limit|HTTP 429|Too Many Requests|UpdateIntuitFEJob)' | tail -10"
}

# Función para verificar estado del job
check_job_status() {
    echo "📈 Verificando estado de jobs..."
    run_docker "php artisan queue:failed | head -10"
}

# Función para verificar FE específica
check_fe_status() {
    local fe_id="$1"
    echo "🔍 Verificando estado de FE ID: $fe_id"
    run_docker "php artisan tinker --execute=\"
        \\\$fe = App\\Models\\FeHeader::find($fe_id);
        if (\\\$fe) {
            echo 'FE ID: ' . \\\$fe->id . PHP_EOL;
            echo 'Sync Status: ' . (\\\$fe->fe_sync_status ?? 'null') . PHP_EOL;
            echo 'Sync Step: ' . (\\\$fe->fe_sync_step ?? 'null') . PHP_EOL;
            echo 'Customer ID: ' . (\\\$fe->fe_customer_id ?? 'null') . PHP_EOL;
            echo 'Invoice ID: ' . (\\\$fe->fe_invoice_id ?? 'null') . PHP_EOL;
            echo 'ImportIntuit: ' . (\\\$fe->ImportIntuit ? 'true' : 'false') . PHP_EOL;
            if (\\\$fe->fe_sync_errors) {
                echo 'Errors: ' . \\\$fe->fe_sync_errors . PHP_EOL;
            }
        } else {
            echo 'FE no encontrada con ID: $fe_id' . PHP_EOL;
        }
    \""
}

# Función para simular reintento manual
retry_fe_job() {
    local fe_id="$1"
    echo "🔄 Simulando reintento manual para FE ID: $fe_id"
    run_docker "php artisan tinker --execute=\"
        \\\$fe = App\\Models\\FeHeader::find($fe_id);
        if (\\\$fe) {
            // Resetear estado para reintento
            \\\$fe->update([
                'fe_sync_status' => 'pending',
                'fe_sync_step' => 0,
                'fe_sync_errors' => null
            ]);
            echo 'FE reseteada para reintento' . PHP_EOL;

            // Opcional: Dispatch del job nuevamente
            App\\Jobs\\Intuit\\UpdateIntuitFEJob::dispatch(\\\$fe->id, 1);
            echo 'Job dispatched nuevamente' . PHP_EOL;
        } else {
            echo 'FE no encontrada' . PHP_EOL;
        }
    \""
}

# Función para testing de detección HTTP 429
test_rate_limit_detection() {
    echo "🧪 Testing detección de HTTP 429..."
    run_docker "php artisan tinker --execute=\"
        // Simular array de respuesta HTTP 429
        \\\$mockResult = [
            'success' => false,
            'error' => 'Error HTTP 429: Client error: \`POST https://us-central1-zoho-books-edocs-integracion.cloudfunctions.net/aciv2/create_invoice_quickbooks\` resulted in a \`429 Too Many Requests\` response',
            'error_type' => 'http_error',
            'status_code' => 429
        ];

        // Test de detección
        \\\$errorMessage = \\\$mockResult['error'] ?? 'Error desconocido';
        \\\$statusCode = \\\$mockResult['status_code'] ?? null;
        \\\$errorType = \\\$mockResult['error_type'] ?? 'unknown';

        \\\$isRateLimit = \\\$statusCode === 429
            || \\\$statusCode === '429'
            || strpos(\\\$errorMessage, '429') !== false
            || strpos(\\\$errorMessage, 'Too Many Requests') !== false
            || strpos(\\\$errorMessage, 'Rate limit') !== false
            || strpos(\\\$errorMessage, 'rate limit') !== false
            || (\\\$errorType === 'http_error' && strpos(\\\$errorMessage, '429') !== false);

        echo 'Detección de Rate Limit: ' . (\\\$isRateLimit ? 'DETECTADO ✅' : 'NO DETECTADO ❌') . PHP_EOL;
        echo 'Status Code: ' . \\\$statusCode . PHP_EOL;
        echo 'Error Type: ' . \\\$errorType . PHP_EOL;
        echo 'Contiene 429: ' . (strpos(\\\$errorMessage, '429') !== false ? 'SÍ' : 'NO') . PHP_EOL;
        echo 'Contiene Too Many Requests: ' . (strpos(\\\$errorMessage, 'Too Many Requests') !== false ? 'SÍ' : 'NO') . PHP_EOL;
    \""
}

# Función para verificar configuración del job
check_job_config() {
    echo "⚙️ Verificando configuración del job..."
    run_docker "php artisan tinker --execute=\"
        \\\$job = new App\\Jobs\\Intuit\\UpdateIntuitFEJob(1, 1);
        echo 'Tries configurado: ' . \\\$job->tries . PHP_EOL;
        echo 'Backoff configurado: ' . json_encode(\\\$job->backoff()) . PHP_EOL;
    \""
}

# Menú principal
case "${3:-menu}" in
    "status")
        echo "🔍 === VERIFICACIÓN DE ESTADO ==="
        check_fe_status $FE_ID
        echo ""
        check_job_status
        echo ""
        check_rate_limit_logs
        ;;
    "retry")
        echo "🔄 === REINTENTO MANUAL ==="
        check_fe_status $FE_ID
        echo ""
        read -p "¿Proceder con el reintento? (y/N): " confirm
        if [[ $confirm == [yY] ]]; then
            retry_fe_job $FE_ID
        else
            echo "Reintento cancelado"
        fi
        ;;
    "test")
        echo "🧪 === TESTING DE DETECCIÓN ==="
        test_rate_limit_detection
        echo ""
        check_job_config
        ;;
    "logs")
        echo "📊 === LOGS DE RATE LIMITING ==="
        check_rate_limit_logs
        ;;
    "interactive")
        echo "🎯 === MODO INTERACTIVO ==="
        echo "Comandos disponibles:"
        echo "  1. Ver estado de FE"
        echo "  2. Ver logs de rate limiting"
        echo "  3. Test de detección"
        echo "  4. Reintentar job"
        echo "  5. Ver jobs fallidos"
        echo "  q. Salir"
        echo ""

        while true; do
            read -p "Seleccionar opción (1-5, q): " option
            case $option in
                1) check_fe_status $FE_ID ;;
                2) check_rate_limit_logs ;;
                3) test_rate_limit_detection ;;
                4) retry_fe_job $FE_ID ;;
                5) check_job_status ;;
                q) echo "Saliendo..."; break ;;
                *) echo "Opción inválida" ;;
            esac
            echo ""
        done
        ;;
    *)
        echo "📋 === RESUMEN COMPLETO ==="
        echo ""
        echo "1️⃣ Estado de FE:"
        check_fe_status $FE_ID
        echo ""
        echo "2️⃣ Test de detección:"
        test_rate_limit_detection
        echo ""
        echo "3️⃣ Configuración del job:"
        check_job_config
        echo ""
        echo "4️⃣ Logs recientes:"
        check_rate_limit_logs
        echo ""
        echo "5️⃣ Jobs fallidos:"
        check_job_status
        echo ""
        echo "📖 Uso del script:"
        echo "  $0 [org_id] [fe_id] [comando]"
        echo ""
        echo "  Comandos disponibles:"
        echo "    status      - Ver estado actual"
        echo "    retry       - Reintentar job manualmente"
        echo "    test        - Probar detección de rate limiting"
        echo "    logs        - Ver logs de rate limiting"
        echo "    interactive - Modo interactivo"
        echo ""
        echo "  Ejemplo: $0 1 697 status"
        ;;
esac

echo ""
echo "✅ Test completado - $(date)"
