<?php

/**
 * Test específico: Verificar detección de cliente extranjero Solmary
 *
 * Este test simula exactamente lo que hace el componente Livewire
 * cuando se selecciona el cliente 32 (Solmary)
 */

echo "🔍 TEST ESPECÍFICO: Detección Cliente Extranjero Solmary\n";
echo "====================================================\n\n";

try {
    // 1. Simular la carga del cliente desde la base de datos
    echo "1. 📋 SIMULANDO CARGA DEL CLIENTE\n";
    echo "--------------------------------\n";

    // Simulamos los datos del cliente como los obtendría Livewire
    $customer = new stdClass();
    $customer->ID = 32;
    $customer->CustomerID = 'XYZABC123';
    $customer->Customer_Bill_Name = 'Solmary';
    $customer->Country = 'Chile';
    $customer->Custom_field1 = 'XYZABC123';
    $customer->Custom_field2 = null;
    $customer->Custom_field3 = '04';  // ← Este es el campo clave
    $customer->Custom_field4 = '2';
    $customer->Custom_field5 = null;

    echo "Cliente cargado:\n";
    echo "  - ID: {$customer->ID}\n";
    echo "  - CustomerID: {$customer->CustomerID}\n";
    echo "  - Nombre: {$customer->Customer_Bill_Name}\n";
    echo "  - País: {$customer->Country}\n";
    echo "  - Custom_field3: '{$customer->Custom_field3}' ← Tipo receptor\n\n";

    // 2. Simular la lógica de determinación del receptor_tipo
    echo "2. 🧠 SIMULANDO LÓGICA DE RECEPTOR_TIPO\n";
    echo "--------------------------------------\n";

    // Esta es la lógica exacta del método fillCustomerFields() en Create.php
    $receptorTipo = !empty($customer->Custom_field3) ? $customer->Custom_field3 : '2';
    echo "receptorTipo inicial: '{$receptorTipo}'\n";

    // Simular strPad function
    $paddedCode = str_pad($receptorTipo, 2, '0', STR_PAD_LEFT);
    echo "Código con padding: '{$paddedCode}'\n";

    // Simular la búsqueda del Receivertype
    // En el código real, esto sería: Receivertype::query()->where('code', $paddedCode)->first()
    $receiverTypes = [
        '01' => ['id' => 1, 'name' => 'Contribuyente'],
        '02' => ['id' => 2, 'name' => 'Consumidor final'],
        '03' => ['id' => 4, 'name' => 'Gobierno'],
        '04' => ['id' => 3, 'name' => 'Extranjero']  // ← Este debería encontrarse
    ];

    if (isset($receiverTypes[$paddedCode])) {
        $receptor = $receiverTypes[$paddedCode];
        $receptor_tipo_final = (string)$receptor['id'];
        $receptor_tipo_texto = $receptor['name'];

        echo "✅ Receptor encontrado:\n";
        echo "  - ID: {$receptor['id']}\n";
        echo "  - Nombre: {$receptor['name']}\n";
        echo "  - receptor_tipo asignado: '{$receptor_tipo_final}'\n";
    } else {
        echo "❌ NO se encontró receptor con código '{$paddedCode}'\n";
        $receptor_tipo_final = $receptorTipo;
        echo "  - Se usaría receptorTipo original: '{$receptor_tipo_final}'\n";
    }

    echo "\n";

    // 3. Verificar condición del blade
    echo "3. 🎯 VERIFICACIÓN CONDICIÓN BLADE\n";
    echo "---------------------------------\n";

    $customer_id = $customer->CustomerID; // 'XYZABC123'

    echo "Variables que debería tener Alpine.js:\n";
    echo "  - receptor_tipo: '{$receptor_tipo_final}'\n";
    echo "  - customer_id: '{$customer_id}'\n\n";

    echo "Evaluando condición blade:\n";
    echo "  (receptor_tipo === '3') && (customer_id !== null && customer_id !== '')\n";

    $condicion1 = ($receptor_tipo_final === '3');
    $condicion2 = ($customer_id !== null && $customer_id !== '');
    $resultado_final = $condicion1 && $condicion2;

    echo "  - receptor_tipo === '3': " . ($condicion1 ? "✅ true" : "❌ false") . " (actual: '{$receptor_tipo_final}')\n";
    echo "  - customer_id válido: " . ($condicion2 ? "✅ true" : "❌ false") . " (actual: '{$customer_id}')\n";
    echo "  - RESULTADO FINAL: " . ($resultado_final ? "✅ MOSTRAR CAMPOS" : "❌ OCULTAR CAMPOS") . "\n\n";

    // 4. Diagnóstico de posibles problemas
    echo "4. 🔧 DIAGNÓSTICO DE POSIBLES PROBLEMAS\n";
    echo "--------------------------------------\n";

    if (!$resultado_final) {
        echo "❌ Los campos NO se mostrarían. Posibles causas:\n";

        if (!$condicion1) {
            echo "  - receptor_tipo no es '3'\n";
            echo "  - Verificar que Custom_field3 sea '04'\n";
            echo "  - Verificar mapeo de códigos en type_receptors\n";
        }

        if (!$condicion2) {
            echo "  - customer_id es null o vacío\n";
            echo "  - Verificar que CustomerID se asigne correctamente\n";
        }

    } else {
        echo "✅ Los campos DEBERÍAN mostrarse\n";
        echo "Si aún no se ven, el problema está en:\n";
        echo "  1. Sincronización de Livewire (@entangle no funciona)\n";
        echo "  2. JavaScript/Alpine.js tiene errores\n";
        echo "  3. Caché del navegador\n";
        echo "  4. La condición x-show no se está evaluando\n";
    }

    echo "\n";

    // 5. Campos específicos que deberían aparecer
    echo "5. 📝 CAMPOS QUE DEBERÍAN APARECER\n";
    echo "--------------------------------\n";

    if ($resultado_final) {
        echo "Para el cliente Solmary deberían verse:\n";
        echo "  ✅ Tipo Identificación (B408) - Select con opciones\n";
        echo "  ✅ Número Identificación (B409) - Input para XYZABC123\n";
        echo "  ✅ País Extranjero (B410) - Select con Chile\n";
        echo "  ✅ Campos opcionales B411-B416\n";
        echo "\nValores pre-llenados esperados:\n";
        echo "  - numeroIdentificacionExtranjero: 'XYZABC123'\n";
        echo "  - paisExtranjero: Chile (ID correspondiente)\n";
    }

} catch (Exception $e) {
    echo "❌ Error en test: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test completado: " . date('Y-m-d H:i:s') . "\n";
echo "\n💡 PRÓXIMOS PASOS:\n";
echo "  1. Verificar en navegador que receptor_tipo se asigna correctamente\n";
echo "  2. Inspeccionar elemento y ver variables Alpine.js\n";
echo "  3. Revisar consola JavaScript por errores\n";
echo "  4. Confirmar que @entangle está funcionando\n";

?>
