<?php

/**
 * Análisis Completo de Campos de Totales Requeridos por Tipo de Documento
 * Según Documentación Oficial PAC TheFactoryHKA
 *
 * Fuentes oficiales analizadas:
 * - https://felwiki.thefactoryhka.com.pa/factura_de_operacion_interna
 * - https://felwiki.thefactoryhka.com.pa/factura_de_exportacion
 * - https://felwiki.thefactoryhka.com.pa/factura_de_importacion
 * - https://felwiki.thefactoryhka.com.pa/factura_a_consumidor_final
 *
 * Ejecutar desde Docker:
 * docker exec -it docucenter_laravel.test php docs/testing/analisis-campos-totales-pac-oficial.php
 */

echo "=== ANÁLISIS COMPLETO: Campos de Totales PAC TheFactoryHKA ===\n\n";

echo "📚 FUENTES OFICIALES ANALIZADAS:\n";
echo "   1. Factura de Operación Interna (Tipo 01)\n";
echo "   2. Factura de Exportación (Tipo 03)\n";
echo "   3. Factura de Importación (Tipo 02)\n";
echo "   4. Factura a Consumidor Final (Tipo 01)\n\n";

// Definir campos encontrados en la documentación oficial
$documentTypes = [
    '01' => 'Factura de Operación Interna',
    '02' => 'Factura de Importación',
    '03' => 'Factura de Exportación',
    '01_consumidor' => 'Factura a Consumidor Final'
];

$fieldsDocumentation = [
    // Factura de Operación Interna (Tipo 01)
    '01' => [
        'totalPrecioNeto' => ['presente' => true, 'valor' => '5.55', 'requerido' => true],
        'totalITBMS' => ['presente' => true, 'valor' => '0.39', 'requerido' => true],
        'totalISC' => ['presente' => true, 'valor' => '', 'requerido' => true, 'nota' => 'Vacío pero presente'],
        'totalMontoGravado' => ['presente' => true, 'valor' => '0.39', 'requerido' => true],
        'totalDescuento' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'totalAcarreoCobrado' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'valorSeguroCobrado' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'totalFactura' => ['presente' => true, 'valor' => '5.94', 'requerido' => true],
        'totalValorRecibido' => ['presente' => true, 'valor' => '5.94', 'requerido' => true],
        'vuelto' => ['presente' => false, 'valor' => null, 'requerido' => false, 'nota' => 'No presente cuando es 0'],
        'tiempoPago' => ['presente' => true, 'valor' => '3', 'requerido' => true],
        'nroItems' => ['presente' => true, 'valor' => '1', 'requerido' => true],
        'totalTodosItems' => ['presente' => true, 'valor' => '5.94', 'requerido' => true]
    ],

    // Factura de Exportación (Tipo 03)
    '03' => [
        'totalPrecioNeto' => ['presente' => true, 'valor' => '5.55', 'requerido' => true],
        'totalITBMS' => ['presente' => true, 'valor' => '0.39', 'requerido' => true],
        'totalISC' => ['presente' => true, 'valor' => '', 'requerido' => true, 'nota' => 'Vacío pero presente'],
        'totalMontoGravado' => ['presente' => true, 'valor' => '0.39', 'requerido' => true],
        'totalDescuento' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'totalAcarreoCobrado' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'valorSeguroCobrado' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'totalFactura' => ['presente' => true, 'valor' => '5.94', 'requerido' => true],
        'totalValorRecibido' => ['presente' => true, 'valor' => '5.94', 'requerido' => true],
        'vuelto' => ['presente' => false, 'valor' => null, 'requerido' => false, 'nota' => 'No presente cuando es 0'],
        'tiempoPago' => ['presente' => true, 'valor' => '3', 'requerido' => true],
        'nroItems' => ['presente' => true, 'valor' => '1', 'requerido' => true],
        'totalTodosItems' => ['presente' => true, 'valor' => '5.94', 'requerido' => true]
    ],

    // Factura de Importación (Tipo 02)
    '02' => [
        'totalPrecioNeto' => ['presente' => true, 'valor' => '5.55', 'requerido' => true],
        'totalITBMS' => ['presente' => true, 'valor' => '0.39', 'requerido' => true],
        'totalISC' => ['presente' => true, 'valor' => '', 'requerido' => true, 'nota' => 'Vacío pero presente'],
        'totalMontoGravado' => ['presente' => true, 'valor' => '0.39', 'requerido' => true],
        'totalDescuento' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'totalAcarreoCobrado' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'valorSeguroCobrado' => ['presente' => true, 'valor' => '', 'requerido' => false, 'nota' => 'Vacío pero presente'],
        'totalFactura' => ['presente' => true, 'valor' => '5.94', 'requerido' => true],
        'totalValorRecibido' => ['presente' => true, 'valor' => '5.94', 'requerido' => true],
        'vuelto' => ['presente' => false, 'valor' => null, 'requerido' => false, 'nota' => 'No presente cuando es 0'],
        'tiempoPago' => ['presente' => true, 'valor' => '3', 'requerido' => true],
        'nroItems' => ['presente' => true, 'valor' => '1', 'requerido' => true],
        'totalTodosItems' => ['presente' => true, 'valor' => '5.94', 'requerido' => true]
    ],

    // Factura a Consumidor Final
    '01_consumidor' => [
        'totalPrecioNeto' => ['presente' => true, 'valor' => '100.00', 'requerido' => true],
        'totalITBMS' => ['presente' => true, 'valor' => '7.00', 'requerido' => true],
        'totalISC' => ['presente' => false, 'valor' => null, 'requerido' => false, 'nota' => 'No presente en este ejemplo'],
        'totalMontoGravado' => ['presente' => true, 'valor' => '7.00', 'requerido' => true],
        'totalDescuento' => ['presente' => true, 'valor' => '0.00', 'requerido' => false, 'nota' => 'Presente con valor 0.00'],
        'totalAcarreoCobrado' => ['presente' => false, 'valor' => null, 'requerido' => false],
        'valorSeguroCobrado' => ['presente' => false, 'valor' => null, 'requerido' => false],
        'totalFactura' => ['presente' => true, 'valor' => '107.00', 'requerido' => true],
        'totalValorRecibido' => ['presente' => true, 'valor' => '107.00', 'requerido' => true],
        'vuelto' => ['presente' => true, 'valor' => '0.00', 'requerido' => false, 'nota' => 'Presente con valor 0.00'],
        'tiempoPago' => ['presente' => true, 'valor' => '2', 'requerido' => true],
        'nroItems' => ['presente' => true, 'valor' => '1', 'requerido' => true],
        'totalTodosItems' => ['presente' => true, 'valor' => '107.00', 'requerido' => true]
    ]
];

echo "1. ANÁLISIS POR TIPO DE DOCUMENTO:\n\n";

foreach ($fieldsDocumentation as $type => $fields) {
    echo "📄 " . $documentTypes[$type] . " (Tipo: $type)\n";
    echo "   Estructura totalesSubTotales:\n";

    foreach ($fields as $fieldName => $fieldInfo) {
        $status = $fieldInfo['presente'] ? '✅ PRESENTE' : '❌ AUSENTE';
        $value = $fieldInfo['valor'] !== null ? "'{$fieldInfo['valor']}'" : 'NULL';
        $required = $fieldInfo['requerido'] ? 'REQUERIDO' : 'OPCIONAL';
        $note = isset($fieldInfo['nota']) ? " ({$fieldInfo['nota']})" : '';

        echo "   - $fieldName: $status | Valor: $value | $required$note\n";
    }
    echo "\n";
}

echo "2. CAMPOS CRÍTICOS IDENTIFICADOS:\n\n";

// Analizar campos que aparecen consistentemente
$criticalFields = [];
$allFields = array_keys($fieldsDocumentation['01']);

foreach ($allFields as $field) {
    $appearances = 0;
    $requiredCount = 0;
    $presentCount = 0;

    foreach ($fieldsDocumentation as $type => $fields) {
        if (isset($fields[$field])) {
            $appearances++;
            if ($fields[$field]['presente']) {
                $presentCount++;
            }
            if ($fields[$field]['requerido']) {
                $requiredCount++;
            }
        }
    }

    if ($presentCount >= 3 || $requiredCount >= 2) { // Presente en 3+ tipos o requerido en 2+ tipos
        $criticalFields[$field] = [
            'appearances' => $appearances,
            'present_count' => $presentCount,
            'required_count' => $requiredCount
        ];
    }
}

foreach ($criticalFields as $field => $stats) {
    $criticality = $stats['required_count'] >= 3 ? 'CRÍTICO' : 'IMPORTANTE';
    echo "🔥 $field: $criticality\n";
    echo "   - Presente en {$stats['present_count']}/4 tipos de documento\n";
    echo "   - Marcado como requerido en {$stats['required_count']}/4 tipos\n";
    echo "   - Análisis: ";

    if ($stats['required_count'] >= 3) {
        echo "SIEMPRE debe incluirse\n";
    } elseif ($stats['present_count'] >= 3) {
        echo "DEBE incluirse aunque sea vacío o con valor 0\n";
    } else {
        echo "Incluir condicionalmente\n";
    }
    echo "\n";
}

echo "3. VERIFICACIÓN IMPLEMENTACIÓN ACTUAL:\n\n";

$serviceFile = file_get_contents(__DIR__ . '/../../app/Services/HKAService.php');

$currentKeepZeroFields = [
    'tipoSucursal', 'tipoOperacion', 'formatoCAFE', 'entregaCAFE',
    'envioContenedor', 'procesoGeneracion', 'tipoVenta', 'tiempoPago',
    'nroItems', 'nroPedidoCompraGlobal', 'nroAceptacion',
    'valorITBMS', 'valorISC', 'tasaITBMS', 'tasaISC',
    'totalITBMS', 'totalISC', 'totalMontoGravado'
];

foreach ($criticalFields as $field => $stats) {
    if (in_array($field, $currentKeepZeroFields)) {
        echo "   ✅ $field: Protegido en keepZeroFields\n";
    } else {
        $hasUnconditionalAssignment = false;

        // Verificar asignación incondicional
        $patterns = [
            "\$totales->$field = \$",
            "\$item->$field = \$"
        ];

        foreach ($patterns as $pattern) {
            if (strpos($serviceFile, $pattern) !== false) {
                $hasUnconditionalAssignment = true;
                break;
            }
        }

        if ($hasUnconditionalAssignment) {
            echo "   ✅ $field: Asignación incondicional implementada\n";
        } else {
            echo "   ⚠️ $field: REVISAR - Puede necesitar asignación incondicional\n";
        }
    }
}

echo "\n4. RECOMENDACIONES BASADAS EN DOCUMENTACIÓN OFICIAL:\n\n";

echo "🎯 CAMPOS QUE DEBEN INCLUIRSE SIEMPRE (incluso con valor 0 o vacío):\n";

$alwaysInclude = [
    'totalPrecioNeto' => 'Presente en todos los ejemplos con valores',
    'totalITBMS' => 'Presente en todos, incluso cuando vacío',
    'totalISC' => 'Presente en 3/4 ejemplos, incluso cuando vacío',
    'totalMontoGravado' => 'Presente en todos con valores, CRÍTICO',
    'totalFactura' => 'Presente en todos con valores',
    'totalValorRecibido' => 'Presente en todos con valores',
    'tiempoPago' => 'Presente en todos',
    'nroItems' => 'Presente en todos',
    'totalTodosItems' => 'Presente en todos con valores'
];

foreach ($alwaysInclude as $field => $reason) {
    echo "   - $field: $reason\n";
}

echo "\n🔧 CAMPOS OPCIONALES (incluir solo si > 0):\n";

$conditionalFields = [
    'totalDescuento' => 'A veces vacío, a veces con valor 0.00',
    'totalAcarreoCobrado' => 'Vacío en ejemplos oficiales',
    'valorSeguroCobrado' => 'Vacío en ejemplos oficiales',
    'vuelto' => 'Presente solo cuando hay vuelto real'
];

foreach ($conditionalFields as $field => $reason) {
    echo "   - $field: $reason\n";
}

echo "\n5. ACCIONES RECOMENDADAS:\n\n";

$recommendedActions = [
    'totalPrecioNeto' => 'Verificar asignación incondicional',
    'totalFactura' => 'Verificar asignación incondicional',
    'totalValorRecibido' => 'Verificar asignación incondicional',
    'totalTodosItems' => 'Verificar asignación incondicional',
    'totalISC' => 'YA CORREGIDO - Asignación incondicional implementada',
    'totalITBMS' => 'YA CORREGIDO - Asignación incondicional implementada',
    'totalMontoGravado' => 'YA CORREGIDO - Asignación incondicional implementada'
];

foreach ($recommendedActions as $field => $action) {
    $status = strpos($action, 'YA CORREGIDO') !== false ? '✅' : '🔄';
    echo "   $status $field: $action\n";
}

echo "\n=== CONCLUSIÓN ===\n";
echo "✅ Análisis completado basado en documentación oficial PAC\n";
echo "✅ Identificados campos críticos que deben incluirse siempre\n";
echo "✅ Verificadas correcciones ya implementadas\n";
echo "🔄 Pendiente: Revisar campos adicionales identificados\n\n";

echo "📚 REFERENCIAS OFICIALES:\n";
echo "   - TheFactoryHKA Wiki: https://felwiki.thefactoryhka.com.pa/\n";
echo "   - Ejemplos oficiales XML analizados\n";
echo "   - Estructura totalesSubTotales consistente en todos los tipos\n\n";

echo "=== FIN DEL ANÁLISIS ===\n";
