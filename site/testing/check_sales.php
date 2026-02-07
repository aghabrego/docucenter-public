<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

// Base connection
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'mariadb',
    'database' => 'docucenter',
    'username' => 'weirdolabs',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
], 'default');

// Organization specific connection
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'mariadb',
    'database' => 'db_15570208122021_26',
    'username' => 'weirdolabs',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
], 'org1');

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🔍 Verificando facturas con número 12720...\n\n";

try {
    $facturas = Capsule::connection('org1')
        ->table('Sales_Header_Imp')
        ->where('InvoiceNumber', 'like', '%12720%')
        ->orderBy('ID', 'desc')
        ->take(5)
        ->get(['ID', 'InvoiceNumber', 'CustomerName', 'Subtotal', 'Net_due', 'Date']);

    if ($facturas->count() > 0) {
        echo "✅ Facturas encontradas:\n";
        echo "┌─────┬─────────────┬─────────────────┬──────────┬──────────┬────────────┐\n";
        echo "│ ID  │ Número      │ Cliente         │ Subtotal │ Total    │ Fecha      │\n";
        echo "├─────┼─────────────┼─────────────────┼──────────┼──────────┼────────────┤\n";

        foreach ($facturas as $f) {
            printf("│ %-3s │ %-11s │ %-15s │ $%-7s │ $%-7s │ %-10s │\n",
                $f->ID,
                $f->InvoiceNumber,
                substr($f->CustomerName, 0, 15),
                $f->Subtotal,
                $f->Net_due,
                substr($f->Date, 0, 10)
            );
        }
        echo "└─────┴─────────────┴─────────────────┴──────────┴──────────┴────────────┘\n";
    } else {
        echo "⚠️  No se encontraron facturas con número 12720\n";
    }

    // Verificar últimas facturas
    echo "\n🔍 Últimas 3 facturas creadas:\n";
    $ultimas = Capsule::connection('org1')
        ->table('Sales_Header_Imp')
        ->orderBy('ID', 'desc')
        ->take(3)
        ->get(['ID', 'InvoiceNumber', 'CustomerName', 'Net_due', 'Date']);

    foreach ($ultimas as $f) {
        echo "• ID: {$f->ID}, Número: {$f->InvoiceNumber}, Cliente: {$f->CustomerName}, Total: \${$f->Net_due}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
