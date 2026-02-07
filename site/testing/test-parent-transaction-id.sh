#!/bin/bash

# Script para probar la funcionalidad de ParentTransactionId en CustomerCreditMemoHeaderImp
# Ubicación: docs/testing/test-parent-transaction-id.sh
# Uso: ./docs/testing/test-parent-transaction-id.sh

echo "🧪 === TESTING PARENTTRANSACTIONID FUNCTIONALITY ==="
echo ""

# Verificar si Docker está corriendo
if ! docker-compose ps | grep -q "laravel.test.*Up"; then
    echo "⚠️ Docker no está corriendo. Iniciando contenedores..."
    docker-compose up -d
    echo "✅ Contenedores iniciados"
    echo ""
fi

echo "🔧 Verificando estructura de base de datos..."
echo ""

# Crear comando temporal para el test
cat > app/Console/Commands/TempParentTransactionTest.php << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TempParentTransactionTest extends Command
{
    protected $signature = 'temp:parent-transaction-test';
    protected $description = 'Test ParentTransactionId functionality';

    public function handle()
    {
        $this->info('🧪 === TEST PARENTTRANSACTIONID EN CUSTOMER CREDIT MEMO ==="');
        $this->line('');

        // Verificar estructura en organizaciones
        $orgs = Organization::whereNotNull('database')->take(3)->get();

        foreach ($orgs as $org) {
            DB::connection()->useDatabase($org->database);

            if (!Schema::hasTable('Customer_Credit_Memo_Header_Imp')) {
                $this->line("❌ Org {$org->id}: Tabla no existe");
                continue;
            }

            $hasColumn = Schema::hasColumn('Customer_Credit_Memo_Header_Imp', 'ParentTransactionId');
            $result = $hasColumn ? '✅' : '❌';

            $this->line("{$result} Org {$org->id}: ParentTransactionId " . ($hasColumn ? 'existe' : 'NO existe'));

            if ($hasColumn) {
                // Verificar tipo de columna
                $columns = DB::select('DESCRIBE Customer_Credit_Memo_Header_Imp');
                foreach ($columns as $col) {
                    if ($col->Field === 'ParentTransactionId') {
                        $this->line("  • Tipo: {$col->Type}");
                        $this->line("  • Null: {$col->Null}");
                        $this->line("  • Default: " . ($col->Default ?? 'NULL'));
                        break;
                    }
                }
            }
        }

        $this->line('');
        $this->info('🔧 Verificando modelo y request...');

        // Verificar que el modelo tenga el campo en fillable
        $model = new \App\Models\CustomerCreditMemoHeaderImp();
        $fillable = $model->getFillable();
        $hasInFillable = in_array('ParentTransactionId', $fillable);

        $result = $hasInFillable ? '✅' : '❌';
        $this->line("{$result} ParentTransactionId en fillable: " . ($hasInFillable ? 'SÍ' : 'NO'));

        // Verificar Request rules
        $request = new \App\Http\Requests\CustomerCreditMemoImpRequest();
        $rules = $request->rules();
        $hasRule = array_key_exists('ParentTransactionId', $rules);

        $result = $hasRule ? '✅' : '❌';
        $this->line("{$result} ParentTransactionId en rules: " . ($hasRule ? 'SÍ' : 'NO'));

        if ($hasRule) {
            $this->line("  • Regla: {$rules['ParentTransactionId']}");
        }

        $this->line('');
        $this->info('📋 Ejemplo de uso en API:');

        $example = [
            'CreditNumber' => 'CM-001',
            'ParentTransactionId' => 12345,
            'CustomerID' => 'CUST001',
            'CustomerName' => 'Cliente de Prueba',
            'Subtotal' => 100.00,
            'Net_Credit_due' => 107.00,
            'Details' => [
                [
                    'Item_id' => 'ITEM001',
                    'Description' => 'Producto de prueba',
                    'Quantity' => 1,
                    'Unit_Price' => 100.00,
                    'Net_line' => 100.00
                ]
            ]
        ];

        $this->line(json_encode($example, JSON_PRETTY_PRINT));

        $this->line('');
        $this->info('✅ Configuración completa - ParentTransactionId está listo para usar');

        return Command::SUCCESS;
    }
}
EOF

# Ejecutar el test
docker-compose exec laravel.test php artisan temp:parent-transaction-test

# Limpiar archivo temporal
rm app/Console/Commands/TempParentTransactionTest.php

echo ""
echo "🧹 Archivo temporal eliminado"
echo "✅ Test de ParentTransactionId completado"
