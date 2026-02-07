#!/bin/bash

# Script rápido para probar el flujo completo de QB
# Uso: ./test-qb-flow.sh [organization_id]

ORGANIZATION_ID=${1:-2}

echo "🧪 PRUEBA RÁPIDA: Flujo QB con Impuestos"
echo "Organization ID: $ORGANIZATION_ID"
echo "═══════════════════════════════════════════"

# Ejecutar el script de prueba completa
echo "🚀 Ejecutando script de prueba completa..."
php test-qb-invoice-creation-flow.php

echo ""
echo "🎯 VALIDACIONES ADICIONALES"
echo "───────────────────────────"

# Verificar configuración de impuestos en QB
echo "📋 Consultando configuración de impuestos en QuickBooks..."
docker exec -it docucenter_laravel.test php artisan tinker --execute="
use App\Models\Connection;
use App\Traits\UpdateIntuitOrdersTrait;

try {
    \$conn = Connection::where('organization_id', $ORGANIZATION_ID)
                      ->where('application', 'acicloud')
                      ->first();

    if (\$conn) {
        echo 'Conexión encontrada: ' . \$conn->id . \"\n\";

        \$settings = \$conn->settings;
        if (is_string(\$settings)) {
            \$settings = json_decode(\$settings, true);
        }

        echo 'App ID: ' . (\$settings['App'] ?? 'N/A') . \"\n\";
        echo 'Estado: ' . \$conn->status . \"\n\";
        echo 'Última actualización: ' . \$conn->updated_at . \"\n\";
    } else {
        echo 'No se encontró conexión QB para org $ORGANIZATION_ID\n';
    }
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . \"\n\";
}
"

echo ""
echo "📊 Verificando facturas recientes con impuestos..."
docker exec -it docucenter_laravel.test php artisan tinker --execute="
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

try {
    \$org = Organization::find($ORGANIZATION_ID);
    if (\$org && !empty(\$org->database)) {
        DB::connection()->useDatabase(\$org->database);

        \$recent = DB::table('Sales_Header_Imp')
                    ->where('EzeeIssued', 1)
                    ->whereNotNull('intuit_invoice_id')
                    ->orderBy('Date', 'desc')
                    ->limit(5)
                    ->get(['ID', 'InvoiceNumber', 'TotalAmount', 'intuit_invoice_id', 'Date']);

        echo 'Facturas recientes con QB ID:\n';
        foreach (\$recent as \$invoice) {
            echo '• ID: ' . \$invoice->ID . ' | Número: ' . \$invoice->InvoiceNumber . ' | Total: $' . \$invoice->TotalAmount . ' | QB ID: ' . \$invoice->intuit_invoice_id . ' | Fecha: ' . \$invoice->Date . \"\n\";
        }
    }
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . \"\n\";
}
"

echo ""
echo "✅ Prueba completa terminada"
