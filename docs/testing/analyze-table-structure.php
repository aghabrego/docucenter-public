<?php

/**
 * Script de Análisis de Estructura de Tablas vs Validaciones
 *
 * Analiza los stubs SQL para SalesOrder_Header_Imp y SalesOrder_Detail_Imp
 * y compara con las validaciones actuales del API
 */

echo "=== ANÁLISIS ESTRUCTURA DE TABLAS vs VALIDACIONES ===\n\n";

// 1. ESTRUCTURA SalesOrder_Header_Imp (según stub SQL)
echo "1. ESTRUCTURA SalesOrder_Header_Imp (según stub SQL):\n";

$headerStubFields = [
    'ID' => ['type' => 'int(11)', 'null' => false, 'auto_increment' => true, 'key' => 'PRIMARY'],
    'ID_compania' => ['type' => 'int(11)', 'null' => false, 'key' => 'INDEX'],
    'FUSION' => ['type' => 'varchar(20)', 'null' => true],
    'SalesOrderNumber' => ['type' => 'varchar(20)', 'null' => false, 'key' => 'INDEX'],
    'CustomerID' => ['type' => 'varchar(50)', 'null' => false],
    'CustomerPO' => ['type' => 'varchar(20)', 'null' => true],
    'CustomerName' => ['type' => 'varchar(29)', 'null' => false],
    'Subtotal' => ['type' => 'decimal(18,4)', 'null' => false],
    'TaxID' => ['type' => 'varchar(8)', 'null' => false],
    'OrderTax' => ['type' => 'decimal(18,4)', 'null' => false, 'default' => '0.0000'],
    'Net_due' => ['type' => 'decimal(18,4)', 'null' => false],
    'Export_date' => ['type' => 'datetime', 'null' => true],
    'Enviado' => ['type' => 'tinyint(1)', 'null' => false, 'default' => '0'],
    'Error' => ['type' => 'tinyint(1)', 'null' => false, 'default' => '0'],
    'ErrorPT' => ['type' => 'varchar(1024)', 'null' => true],
    'user' => ['type' => 'varchar(45)', 'null' => true],
    'date' => ['type' => 'datetime', 'null' => true],
    'AR_Account' => ['type' => 'varchar(15)', 'null' => true],
    'LAST_CHANGE' => ['type' => 'datetime', 'null' => false],
    'saletax' => ['type' => 'int(11)', 'null' => false, 'default' => '0'],
    'tipo_licitacion' => ['type' => 'varchar(30)', 'null' => true],
    'entrega' => ['type' => 'varchar(30)', 'null' => true],
    'termino_pago' => ['type' => 'varchar(30)', 'null' => true],
    'observaciones' => ['type' => 'varchar(255)', 'null' => true],
    'ShipToName' => ['type' => 'varchar(100)', 'null' => true],
    'ShipToAddressLine1' => ['type' => 'varchar(30)', 'null' => true],
    'ShipToAddressLine2' => ['type' => 'varchar(30)', 'null' => true],
    'ShipToCity' => ['type' => 'varchar(20)', 'null' => true],
    'ShipToState' => ['type' => 'varchar(2)', 'null' => true],
    'ShipToZip' => ['type' => 'varchar(12)', 'null' => true],
    'ShipToCountry' => ['type' => 'varchar(15)', 'null' => true],
    'Emitida' => ['type' => 'tinyint(1)', 'null' => true, 'default' => '0'],
    'fecha_entrega' => ['type' => 'varchar(20)', 'null' => true],
    'lugar_despacho' => ['type' => 'varchar(45)', 'null' => true],
    'DispachPrinted' => ['type' => 'int(11)', 'null' => false, 'default' => '0'],
    'blocked' => ['type' => 'int(11)', 'null' => true],
    'canceled' => ['type' => 'smallint(6)', 'null' => false, 'default' => '0'],
    'SalesRepID' => ['type' => 'varchar(20)', 'null' => true],
    'InvoiceNote' => ['type' => 'text', 'null' => true],
    'PrintInvoiceNoteAfterItems' => ['type' => 'tinyint(1)', 'null' => true],
    'Batch' => ['type' => 'varchar(100)', 'null' => true],
    'Expire' => ['type' => 'varchar(10)', 'null' => true],
    'Hs_Code' => ['type' => 'varchar(100)', 'null' => true],
    'Weight' => ['type' => 'varchar(100)', 'null' => true],
    'Palet' => ['type' => 'varchar(100)', 'null' => true],
    'Package' => ['type' => 'varchar(100)', 'null' => true],
    'CoordRec' => ['type' => 'varchar(50)', 'null' => true],
    'WithinRadius' => ['type' => 'tinyint(1)', 'null' => true, 'default' => '0'],
];

echo "   Total campos en stub: " . count($headerStubFields) . "\n";

// Campos obligatorios (NOT NULL)
$requiredHeaderFields = [];
foreach ($headerStubFields as $field => $props) {
    if (!$props['null'] && !isset($props['auto_increment'])) {
        $requiredHeaderFields[] = $field;
    }
}

echo "   Campos obligatorios (NOT NULL): " . count($requiredHeaderFields) . "\n";
echo "   Lista obligatorios: " . implode(', ', $requiredHeaderFields) . "\n\n";

// 2. ESTRUCTURA SalesOrder_Detail_Imp (según stub SQL)
echo "2. ESTRUCTURA SalesOrder_Detail_Imp (según stub SQL):\n";

$detailStubFields = [
    'DetailId' => ['type' => 'int(10) unsigned', 'null' => false, 'auto_increment' => true, 'key' => 'PRIMARY'],
    'ID' => ['type' => 'int(11)', 'null' => true], // Relación con header
    'ID_compania' => ['type' => 'int(11)', 'null' => false, 'key' => 'INDEX'],
    'SalesOrderNumber' => ['type' => 'varchar(20)', 'null' => false, 'key' => 'INDEX'],
    'ItemOrd' => ['type' => 'varchar(20)', 'null' => true],
    'Item_id' => ['type' => 'varchar(20)', 'null' => false],
    'Description' => ['type' => 'varchar(160)', 'null' => false],
    'Quantity' => ['type' => 'decimal(11,5)', 'null' => false],
    'Unit_Price' => ['type' => 'decimal(18,4)', 'null' => false],
    'Net_line' => ['type' => 'decimal(18,4)', 'null' => false],
    'INVOICED' => ['type' => 'int(10) unsigned', 'null' => false, 'default' => '0'],
    'Taxable' => ['type' => 'int(11)', 'null' => false],
    'LAST_CHANGE' => ['type' => 'timestamp', 'null' => false],
    'REMARK' => ['type' => 'varchar(200)', 'null' => true],
    'PK_CHICO' => ['type' => 'varchar(50)', 'null' => true],
    'PK_GRANDE' => ['type' => 'varchar(50)', 'null' => true],
    'JobId' => ['type' => 'varchar(20)', 'null' => true],
    'JobPhaseID' => ['type' => 'varchar(20)', 'null' => true],
    'JobCostCodeID' => ['type' => 'varchar(20)', 'null' => true],
];

echo "   Total campos en stub: " . count($detailStubFields) . "\n";

// Campos obligatorios (NOT NULL)
$requiredDetailFields = [];
foreach ($detailStubFields as $field => $props) {
    if (!$props['null'] && !isset($props['auto_increment'])) {
        $requiredDetailFields[] = $field;
    }
}

echo "   Campos obligatorios (NOT NULL): " . count($requiredDetailFields) . "\n";
echo "   Lista obligatorios: " . implode(', ', $requiredDetailFields) . "\n\n";

// 3. ANÁLISIS DE VALIDACIONES ACTUALES vs ESTRUCTURA DE TABLA
echo "3. ANÁLISIS CRÍTICO - Header:\n";

// Campos actualmente usados en headerData (simular desde el servicio actual)
$currentHeaderData = [
    'ID_compania', 'SalesOrderNumber', 'CustomerID', 'CustomerName',
    'Subtotal', 'TaxID', 'OrderTax', 'Net_due', 'LAST_CHANGE',
    'ShipToName', 'ShipToAddressLine1', 'ShipToAddressLine2',
    'ShipToCity', 'ShipToState', 'ShipToZip', 'ShipToCountry'
];

echo "   Campos requeridos faltantes en headerData:\n";
$missingRequiredHeader = array_diff($requiredHeaderFields, $currentHeaderData);
foreach ($missingRequiredHeader as $field) {
    $type = $headerStubFields[$field]['type'];
    echo "   ❌ {$field} ({$type}) - OBLIGATORIO FALTANTE\n";
}

echo "\n4. ANÁLISIS CRÍTICO - Detail:\n";

// Campos actualmente usados en detailData
$currentDetailData = [
    'ID', 'ID_compania', 'SalesOrderNumber', 'ItemOrd', 'Item_id',
    'Description', 'Quantity', 'Unit_Price', 'Net_line', 'INVOICED',
    'Taxable', 'LAST_CHANGE', 'REMARK', 'JobId', 'JobPhaseID', 'JobCostCodeID'
];

echo "   Campos requeridos faltantes en detailData:\n";
$missingRequiredDetail = array_diff($requiredDetailFields, $currentDetailData);
foreach ($missingRequiredDetail as $field) {
    $type = $detailStubFields[$field]['type'];
    echo "   ❌ {$field} ({$type}) - OBLIGATORIO FALTANTE\n";
}

if (empty($missingRequiredDetail)) {
    echo "   ✅ Todos los campos obligatorios están cubiertos\n";
}

// 5. DIFERENCIAS CRÍTICAS ENCONTRADAS
echo "\n5. DIFERENCIAS CRÍTICAS ENCONTRADAS:\n";

echo "\n   A) CAMPO 'ID' EN DETAIL:\n";
if (isset($detailStubFields['ID'])) {
    $idProps = $detailStubFields['ID'];
    echo "     - Según stub: {$idProps['type']}, NULL=" . ($idProps['null'] ? 'YES' : 'NO') . "\n";
    echo "     - En implementación actual: AGREGADO como relación\n";
    if ($idProps['null']) {
        echo "     ✅ CORRECTO: Campo puede ser NULL\n";
    } else {
        echo "     ⚠️ ATENCIÓN: Campo debe tener valor\n";
    }
}

echo "\n   B) TIPOS DE DATOS CRÍTICOS:\n";
$criticalTypes = [
    'Quantity' => 'decimal(11,5)',
    'Unit_Price' => 'decimal(18,4)',
    'Net_line' => 'decimal(18,4)',
    'Subtotal' => 'decimal(18,4)',
    'OrderTax' => 'decimal(18,4)',
    'Net_due' => 'decimal(18,4)',
];

foreach ($criticalTypes as $field => $expectedType) {
    echo "     - {$field}: {$expectedType} (precisión importante para moneda)\n";
}

echo "\n   C) LONGITUDES DE CAMPO IMPORTANTES:\n";
$lengthLimits = [
    'SalesOrderNumber' => 'varchar(20)',
    'CustomerID' => 'varchar(50)',
    'CustomerName' => 'varchar(29)', // ⚠️ Solo 29 caracteres!
    'Item_id' => 'varchar(20)',
    'Description' => 'varchar(160)',
    'REMARK' => 'varchar(200)',
];

foreach ($lengthLimits as $field => $type) {
    echo "     - {$field}: {$type}\n";
    if ($field === 'CustomerName' && strpos($type, '29') !== false) {
        echo "       ⚠️ CRÍTICO: CustomerName solo 29 caracteres - puede truncar!\n";
    }
}

// 6. RECOMENDACIONES DE VALIDACIÓN
echo "\n6. RECOMENDACIONES PARA VALIDACIONES:\n";

echo "\n   A) VALIDACIONES DE LONGITUD REQUERIDAS:\n";
echo "     - 'salesorder_number' => 'required|string|max:20'\n";
echo "     - 'customer_name' => 'required|string|max:29'  ⚠️ CRÍTICO\n";
echo "     - 'customer_id' => 'required|string|max:50'\n";
echo "     - 'line_items.*.name' => 'required|string|max:160'\n";
echo "     - 'line_items.*.item_id' => 'required|string|max:20'\n";

echo "\n   B) VALIDACIONES DE TIPO NUMÉRICO:\n";
echo "     - 'sub_total' => 'required|numeric|between:0,999999999999.9999'\n";
echo "     - 'total' => 'required|numeric|between:0,999999999999.9999'\n";
echo "     - 'line_items.*.quantity' => 'required|numeric|between:0,999999.99999'\n";
echo "     - 'line_items.*.rate' => 'required|numeric|between:0,999999999999.9999'\n";

echo "\n   C) CAMPOS OBLIGATORIOS QUE FALTAN:\n";
if (!empty($missingRequiredHeader)) {
    echo "     Header: " . implode(', ', $missingRequiredHeader) . "\n";
}
if (!empty($missingRequiredDetail)) {
    echo "     Detail: " . implode(', ', $missingRequiredDetail) . "\n";
}

echo "\n🎉 ANÁLISIS COMPLETADO\n";
echo "   📋 Verificar validaciones de longitud especialmente CustomerName (29 chars)\n";
echo "   📋 Asegurar precisión decimal en campos monetarios\n";
echo "   📋 Validar campos obligatorios faltantes\n\n";
