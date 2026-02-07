<?php
/**
 * Test de Diagnóstico: Cliente Extranjero Solmary
 *
 * Problema reportado: Cliente 32 (Solmary, Chile) es extranjero pero no se visualiza la identificación
 */

require_once __DIR__ . '/../../vendor/autoload.php';

echo "🔍 DIAGNÓSTICO: Cliente Extranjero Solmary (ID: 32)\n";
echo "===========================================\n\n";

try {
    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Conectar a la base de datos de la organización 2
    DB::connection()->useDatabase('db_18257061709732_90');

    echo "✅ Conectado a base de datos: db_18257061709732_90\n\n";

    // 1. Obtener información del cliente
    echo "📋 1. INFORMACIÓN DEL CLIENTE\n";
    echo "----------------------------\n";

    $customer = DB::table('Customers_Imp')->where('ID', 32)->first();

    if (!$customer) {
        echo "❌ Cliente 32 no encontrado!\n";
        exit(1);
    }

    echo "ID: {$customer->ID}\n";
    echo "CustomerID: {$customer->CustomerID}\n";
    echo "Nombre: {$customer->Customer_Bill_Name}\n";
    echo "País: {$customer->Country}\n";
    echo "Custom_field1: {$customer->Custom_field1}\n";
    echo "Custom_field2: {$customer->Custom_field2}\n";
    echo "Custom_field3: {$customer->Custom_field3}\n";
    echo "Custom_field4: {$customer->Custom_field4}\n";
    echo "Custom_field5: {$customer->Custom_field5}\n\n";

    // 2. Verificar tipos de receptor
    echo "📋 2. TIPOS DE RECEPTOR DISPONIBLES\n";
    echo "----------------------------------\n";

    // Volver a la base principal para consultar tipos
    DB::connection()->useDatabase('docucenter');

    $receiverTypes = DB::table('type_receptors')->orderBy('id')->get();
    foreach ($receiverTypes as $type) {
        echo "ID: {$type->id} | Código: {$type->code} | Nombre: {$type->name}\n";
    }
    echo "\n";

    // 3. Simular la lógica de detección
    echo "📋 3. LÓGICA DE DETECCIÓN\n";
    echo "------------------------\n";

    $receptorTipo = !empty($customer->Custom_field3) ? $customer->Custom_field3 : '2';
    echo "receptorTipo inicial: '{$receptorTipo}'\n";

    // Función strPad simulada
    $paddedCode = str_pad($receptorTipo, 2, '0', STR_PAD_LEFT);
    echo "Código con padding: '{$paddedCode}'\n";

    $receptor = DB::table('type_receptors')->where('code', $paddedCode)->first();

    if ($receptor) {
        echo "✅ Receptor encontrado:\n";
        echo "   - ID: {$receptor->id}\n";
        echo "   - Nombre: {$receptor->name}\n";
        echo "   - Código: {$receptor->code}\n";

        $receptor_tipo_final = (string)$receptor->id;
        echo "   - receptor_tipo asignado: '{$receptor_tipo_final}'\n\n";

        // 4. Verificar condición del blade
        echo "📋 4. VERIFICACIÓN CONDICIÓN BLADE\n";
        echo "---------------------------------\n";
        echo "Condición blade: receptor_tipo === '3'\n";
        echo "Valor actual: receptor_tipo = '{$receptor_tipo_final}'\n";

        if ($receptor_tipo_final === '3') {
            echo "✅ CONDICIÓN CUMPLIDA: Debería mostrar campos extranjeros\n";
        } else {
            echo "❌ CONDICIÓN NO CUMPLIDA: NO mostrará campos extranjeros\n";
            echo "   - Esperado: '3'\n";
            echo "   - Actual: '{$receptor_tipo_final}'\n";
        }

    } else {
        echo "❌ NO se encontró receptor con código '{$paddedCode}'\n";
        echo "   - Se usaría receptorTipo original: '{$receptorTipo}'\n";

        if ($receptorTipo === '3') {
            echo "✅ Aún así cumple condición blade\n";
        } else {
            echo "❌ NO cumple condición blade\n";
        }
    }

    echo "\n";

    // 5. Diagnóstico país
    echo "📋 5. DIAGNÓSTICO PAÍS\n";
    echo "---------------------\n";
    echo "País del cliente: {$customer->Country}\n";

    if (strtolower($customer->Country) !== 'panama') {
        echo "✅ Cliente es extranjero por país\n";
    } else {
        echo "⚠️  Cliente parece ser de Panamá\n";
    }

    echo "\n";

    // 6. Conclusión
    echo "📋 6. CONCLUSIÓN DEL DIAGNÓSTICO\n";
    echo "--------------------------------\n";

    if ($receptor && $receptor->id == 3) {
        echo "✅ La lógica DEBERÍA funcionar correctamente\n";
        echo "✅ El cliente debería detectarse como extranjero\n";
        echo "✅ Los campos B406-B416 deberían ser visibles\n\n";

        echo "🔍 POSIBLES CAUSAS DEL PROBLEMA:\n";
        echo "1. Problema en JavaScript del blade (condición no evaluándose)\n";
        echo "2. customer_id no está siendo asignado correctamente\n";
        echo "3. Problemas de caché en el frontend\n";
        echo "4. Error en la lógica de Alpine.js/Livewire\n";
    } else {
        echo "❌ PROBLEMA ENCONTRADO en la lógica de detección\n";
        echo "   - El sistema NO detectará este cliente como extranjero\n";
        echo "   - Verificar configuración de tipos de receptor\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🎯 Test completado: " . date('Y-m-d H:i:s') . "\n";
?>
