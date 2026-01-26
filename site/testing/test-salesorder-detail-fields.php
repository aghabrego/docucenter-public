<?php

/**
 * Script de Validación para Campos de SalesOrderDetailImp
 *
 * Este script valida que los campos utilizados en detailData coincidan con el modelo SalesOrderDetailImp
 *
 * Ubicación: docs/testing/test-salesorder-detail-fields.php
 * Propósito: Verificar compatibilidad de campos en SalesOrderDetailImp
 */

echo "=== VALIDACIÓN DE CAMPOS SalesOrderDetailImp ===\n\n";

try {
    // 1. CAMPOS VÁLIDOS DEL MODELO (basado en lectura del archivo)
    echo "1. Obteniendo campos válidos del modelo SalesOrderDetailImp...\n";

    // Estos campos son del array fillable del modelo SalesOrderDetailImp
    $validFields = [
        'ID',
        'ID_compania',
        'SalesOrderNumber',
        'ItemOrd',
        'Item_id',
        'Description',
        'Quantity',
        'Unit_Price',
        'Net_line',
        'INVOICED',
        'Taxable',
        'LAST_CHANGE',
        'REMARK',
        'PK_CHICO',
        'PK_GRANDE',
        'JobId',
        'JobPhaseID',
        'JobCostCodeID'
    ];

    echo "✓ Campos válidos encontrados: " . count($validFields) . "\n";
    echo "✓ Campos válidos: " . implode(', ', $validFields) . "\n";

    // 2. DEFINIR CAMPOS UTILIZADOS EN detailData (del servicio ACIcloudService)
    echo "\n2. Validando campos utilizados en detailData...\n";

    $detailDataFields = [
        'SalesOrderNumber',  // En datos de Zoho
        'Taxable',          // Calculado de tax_percentage
        'ItemOrd',          // De item_order o index + 1
        'Item_id',          // ProductID (SKU o item_id)
        'Description',      // De lineItem[name]
        'Quantity',         // De lineItem[quantity]
        'Unit_Price',       // De lineItem[rate]
        'Net_line',         // De lineItem[item_total]
        'JobId',            // Por defecto null
        'JobPhaseID',       // Por defecto null
        'JobCostCodeID',    // Por defecto null
        'ID_compania',      // De company->ID_compania
    ];

    // 3. VERIFICAR COMPATIBILIDAD
    echo "\n3. Verificando compatibilidad de campos...\n";

    $invalidFields = [];
    $validDetailFields = [];
    $missingFields = [];

    foreach ($detailDataFields as $field) {
        if (in_array($field, $validFields)) {
            $validDetailFields[] = $field;
            echo "✓ {$field}\n";
        } else {
            $invalidFields[] = $field;
            echo "❌ {$field} - NO EXISTE EN MODELO\n";
        }
    }

    // 4. VERIFICAR CAMPOS DEL MODELO NO UTILIZADOS
    echo "\n4. Campos del modelo no utilizados en detailData:\n";
    $unusedFields = array_diff($validFields, $detailDataFields);
    foreach ($unusedFields as $field) {
        echo "⚠️ {$field} - DISPONIBLE PERO NO USADO\n";
    }

    // 5. RESUMEN DE VALIDACIÓN
    echo "\n5. Resumen de validación:\n";
    echo "   - Total campos en detailData: " . count($detailDataFields) . "\n";
    echo "   - Campos válidos: " . count($validDetailFields) . "\n";
    echo "   - Campos inválidos: " . count($invalidFields) . "\n";
    echo "   - Campos disponibles no usados: " . count($unusedFields) . "\n";

    if (count($invalidFields) === 0) {
        echo "\n✅ VALIDACIÓN EXITOSA: Todos los campos son compatibles\n";

        // 6. SUGERENCIAS DE MEJORA
        echo "\n6. Análisis de campos disponibles:\n";

        $fieldAnalysis = [
            'ID' => 'Campo requerido - falta agregar (ID del header)',
            'INVOICED' => 'Campo disponible - indica si ya fue facturado',
            'LAST_CHANGE' => 'Campo disponible - timestamp de última modificación',
            'REMARK' => 'Campo disponible - para comentarios/observaciones',
            'PK_CHICO' => 'Campo disponible - clave específica del sistema',
            'PK_GRANDE' => 'Campo disponible - clave específica del sistema',
        ];

        foreach ($fieldAnalysis as $field => $description) {
            if (in_array($field, $unusedFields)) {
                echo "   📋 {$field}: {$description}\n";
            }
        }

        // 7. CAMPOS CRÍTICOS FALTANTES
        echo "\n7. Campos críticos que deberían agregarse:\n";

        if (in_array('ID', $unusedFields)) {
            echo "   🔴 CRÍTICO: 'ID' falta - debe referenciar al SalesOrderHeader\n";
        }

        if (in_array('INVOICED', $unusedFields)) {
            echo "   🟡 RECOMENDADO: 'INVOICED' - para tracking de facturación\n";
        }

    } else {
        echo "\n❌ VALIDACIÓN FALLIDA: Campos incompatibles encontrados\n";
        echo "   Campos que deben corregirse:\n";
        foreach ($invalidFields as $field) {
            echo "   - {$field}\n";
        }
    }

    // 8. VERIFICAR RELACIÓN CON HEADER
    echo "\n8. Verificando relación con SalesOrderHeaderImp...\n";

    echo "   - updateOrCreate usa: SalesOrderNumber, Item_id, ItemOrd\n";
    echo "   - Relación belongsTo: saleOrder() usa ('ID', 'ID')\n";
    echo "   - ⚠️ POSIBLE PROBLEMA: falta campo 'ID' para la relación\n";

    echo "\n🎉 ANÁLISIS COMPLETADO\n";
    echo "   Revisar campos faltantes para mejorar la integración\n";
    echo "   Especialmente el campo 'ID' para la relación con el header\n\n";

} catch (Exception $e) {
    echo "❌ ERROR DURANTE VALIDACIÓN: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n";
    exit(1);
}
