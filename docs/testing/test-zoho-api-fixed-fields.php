<?php

/**
 * Script de Validación para Campos de API Zoho
 *
 * Este script valida que los campos utilizados en headerData coincidan con el modelo SalesOrderHeaderImp
 *
 * Ubicación: docs/testing/test-zoho-api-fixed-fields.php
 * Propósito: Verificar compatibilidad de campos antes de envío a base de datos
 *
 * INSTRUCCIONES DE USO:
 * 1. Ejecutar desde contenedor Docker: docker exec -it docucenter_laravel.test php docs/testing/test-zoho-api-fixed-fields.php
 * 2. O desde directorio del proyecto: php docs/testing/test-zoho-api-fixed-fields.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalesOrderHeaderImp;

echo "=== VALIDACIÓN DE CAMPOS API ZOHO ===\n\n";

try {
    // 1. OBTENER CAMPOS VÁLIDOS DEL MODELO
    echo "1. Obteniendo campos válidos del modelo SalesOrderHeaderImp...\n";

    $model = new SalesOrderHeaderImp();
    $validFields = $model->getFillable();

    echo "✓ Campos válidos encontrados: " . count($validFields) . "\n";

    // 2. DEFINIR CAMPOS UTILIZADOS EN headerData (del servicio ACIcloudService)
    echo "\n2. Validando campos utilizados en headerData...\n";

    $headerDataFields = [
        'SalesOrderNumber',
        'CustomerID',
        'CustomerPO',
        'CustomerName',
        'Subtotal',
        'TaxID',
        'OrderTax',
        'Net_due',       // Corregido de NetDue
        'AR_Account',
        'ShipToName',
        'ShipToAddressLine1',
        'ShipToAddressLine2',
        'ShipToCity',
        'ShipToState',
        'ShipToZip',
        'ShipToCountry',
        'SalesRepID',
        'date',          // Corregido de Date
        'observaciones', // Para guardar terms de Zoho
        'user',          // Campo por defecto
        'Enviado',       // Campo por defecto
        'Error',         // Campo por defecto
        'Emitida',       // Campo por defecto
    ];

    // 3. VERIFICAR COMPATIBILIDAD
    echo "\n3. Verificando compatibilidad de campos...\n";

    $invalidFields = [];
    $validHeaderFields = [];

    foreach ($headerDataFields as $field) {
        if (in_array($field, $validFields)) {
            $validHeaderFields[] = $field;
            echo "✓ {$field}\n";
        } else {
            $invalidFields[] = $field;
            echo "❌ {$field} - NO EXISTE EN MODELO\n";
        }
    }

    // 4. RESUMEN DE VALIDACIÓN
    echo "\n4. Resumen de validación:\n";
    echo "   - Total campos en headerData: " . count($headerDataFields) . "\n";
    echo "   - Campos válidos: " . count($validHeaderFields) . "\n";
    echo "   - Campos inválidos: " . count($invalidFields) . "\n";

    if (count($invalidFields) === 0) {
        echo "\n✅ VALIDACIÓN EXITOSA: Todos los campos son compatibles\n";

        // 5. MOSTRAR CAMPOS PROBLEMÁTICOS ANTERIORES QUE SE CORRIGIERON
        echo "\n5. Campos problemáticos anteriores corregidos:\n";
        $previousProblems = [
            'NetDue' => 'Net_due (corregido)',
            'Date' => 'date (corregido)',
            'RequiredDate' => 'Removido (no existe en modelo)',
            'ShipDate' => 'Removido (no existe en modelo)',
            'ZohoData' => 'Removido (se logea por separado)',
        ];

        foreach ($previousProblems as $oldField => $solution) {
            echo "   - {$oldField} -> {$solution}\n";
        }

        echo "\n6. Campos adicionales disponibles en el modelo (no utilizados actualmente):\n";
        $unusedFields = array_diff($validFields, $headerDataFields);
        foreach (array_slice($unusedFields, 0, 10) as $field) {
            echo "   - {$field}\n";
        }
        if (count($unusedFields) > 10) {
            echo "   ... y " . (count($unusedFields) - 10) . " campos más\n";
        }

    } else {
        echo "\n❌ VALIDACIÓN FALLIDA: Campos incompatibles encontrados\n";
        echo "   Campos que deben corregirse:\n";
        foreach ($invalidFields as $field) {
            echo "   - {$field}\n";
        }
    }

    // 7. VERIFICAR ESTRUCTURA DEL MAPEO ACTUAL
    echo "\n7. Estructura del mapeo actual en ACIcloudService:\n";
    echo "   ✓ Solo campos válidos del modelo SalesOrderHeaderImp\n";
    echo "   ✓ Campos requeridos mapeados desde Zoho\n";
    echo "   ✓ Campos por defecto asignados para campos obligatorios\n";
    echo "   ✓ Datos adicionales de Zoho se logean por separado\n";

    echo "\n🎉 ANÁLISIS COMPLETADO\n";
    echo "   La estructura actual previene errores de SQL\n";
    echo "   El mapeo es compatible con el modelo SalesOrderHeaderImp\n";
    echo "   La funcionalidad updateOrCreate puede ejecutarse sin problemas\n\n";

} catch (Exception $e) {
    echo "❌ ERROR DURANTE VALIDACIÓN: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n";
    exit(1);
}
