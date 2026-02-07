<?php

// Script simple de validación del FormRequest sin Laravel bootstrap completo

echo "🧪 Testing CreatePurchaseOrderZohoRequest Validation\n";
echo "===============================================\n";

// Datos de ejemplo simples
$sampleDataSimple = [
    "bill_number" => "PO-" . date('Ymd'),  // Acortado para cumplir max 20 chars
    "vendor_name" => "Proveedor Test S.A.",
    "vendor_id" => "test-vendor-001",
    "date" => date('Y-m-d'),
    "due_date" => date('Y-m-d', strtotime('+30 days')),
    "status" => "open",
    "total" => 1000.00,
    "sub_total" => 1000.00,
    "tax_total" => 0.00,
    "line_items" => [
        [
            "line_item_id" => "line-001",
            "item_id" => "item-001",
            "name" => "Producto Test 1",
            "description" => "Descripción del producto test",
            "quantity" => 5,
            "rate" => 100.00,
            "amount" => 500.00,
            "unit" => "unidad"
        ],
        [
            "line_item_id" => "line-002",
            "item_id" => "item-002",
            "name" => "Producto Test 2",
            "description" => "Segundo producto de testing",
            "quantity" => 10,
            "rate" => 50.00,
            "amount" => 500.00,
            "unit" => "unidad"
        ]
    ]
];

// Datos con impuestos
$sampleDataWithTaxes = [
    "bill_number" => "PO-TAX-" . date('md'),  // Acortado para cumplir max 20 chars
    "vendor_name" => "Proveedor con ITBMS S.A.",
    "vendor_id" => "tax-vendor-001",
    "date" => date('Y-m-d'),
    "due_date" => date('Y-m-d', strtotime('+30 days')),
    "status" => "open",
    "total" => 1070.00,
    "sub_total" => 1000.00,
    "tax_total" => 70.00,
    "taxes" => [
        [
            "tax_name" => "ITBMS",
            "tax_percentage" => 7,
            "tax_amount" => 70.00
        ]
    ],
    "line_items" => [
        [
            "line_item_id" => "tax-line-001",
            "item_id" => "tax-item-001",
            "name" => "Producto Gravado 1",
            "description" => "Producto sujeto a ITBMS 7%",
            "quantity" => 5,
            "rate" => 100.00,
            "amount" => 500.00,
            "unit" => "unidad",
            "taxes" => [
                [
                    "tax_name" => "ITBMS",
                    "tax_percentage" => 7,
                    "tax_amount" => 35.00
                ]
            ]
        ]
    ]
];

// Simular las reglas de validación directamente
$rules = [
    // Header fields
    'bill_number' => 'required|string|max:20',
    'vendor_id' => 'required|string|max:20',
    'vendor_name' => 'required|string|max:50',
    'total' => 'required|numeric|between:0,99999999999999.9999',
    'sub_total' => 'required|numeric|between:0,99999999999999.9999',

    // Line items
    'line_items' => 'required|array|min:1',
    'line_items.*.line_item_id' => 'required|string',
    'line_items.*.item_id' => 'required|string|max:20',
    'line_items.*.name' => 'required|string|max:50',
    'line_items.*.quantity' => 'required|numeric|between:0,999999999.99999',
    'line_items.*.rate' => 'required|numeric|between:0,99999999999999.9999',
    'line_items.*.amount' => 'required|numeric|between:0,99999999999999.9999',
];

function testValidation($data, $title) {
    global $rules;

    echo "\n📊 Testing: $title\n";
    echo str_repeat("=", strlen($title) + 12) . "\n";

    // Verificar campos requeridos manualmente
    $errors = [];

    // Check required fields
    $requiredFields = ['bill_number', 'vendor_id', 'vendor_name', 'total', 'sub_total', 'line_items'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $errors[] = "Field '$field' is required";
        }
    }

    // Check string lengths
    if (isset($data['bill_number']) && strlen($data['bill_number']) > 20) {
        $errors[] = "bill_number too long (max 20 chars)";
    }
    if (isset($data['vendor_name']) && strlen($data['vendor_name']) > 50) {
        $errors[] = "vendor_name too long (max 50 chars)";
    }
    if (isset($data['vendor_id']) && strlen($data['vendor_id']) > 20) {
        $errors[] = "vendor_id too long (max 20 chars)";
    }

    // Check line items
    if (isset($data['line_items'])) {
        if (!is_array($data['line_items']) || count($data['line_items']) < 1) {
            $errors[] = "line_items must be array with at least 1 item";
        } else {
            foreach ($data['line_items'] as $index => $item) {
                $requiredItemFields = ['line_item_id', 'item_id', 'name', 'quantity', 'rate', 'amount'];
                foreach ($requiredItemFields as $field) {
                    if (!isset($item[$field]) || $item[$field] === '') {
                        $errors[] = "line_items[$index].$field is required";
                    }
                }

                // Check item field lengths
                if (isset($item['item_id']) && strlen($item['item_id']) > 20) {
                    $errors[] = "line_items[$index].item_id too long (max 20 chars)";
                }
                if (isset($item['name']) && strlen($item['name']) > 50) {
                    $errors[] = "line_items[$index].name too long (max 50 chars)";
                }

                // Check numeric values
                if (isset($item['quantity']) && (!is_numeric($item['quantity']) || $item['quantity'] < 0)) {
                    $errors[] = "line_items[$index].quantity must be positive number";
                }
                if (isset($item['rate']) && (!is_numeric($item['rate']) || $item['rate'] < 0)) {
                    $errors[] = "line_items[$index].rate must be positive number";
                }
                if (isset($item['amount']) && (!is_numeric($item['amount']) || $item['amount'] < 0)) {
                    $errors[] = "line_items[$index].amount must be positive number";
                }
            }
        }
    }

    // Check taxes if present
    if (isset($data['taxes']) && is_array($data['taxes'])) {
        foreach ($data['taxes'] as $index => $tax) {
            if (!isset($tax['tax_name']) || empty($tax['tax_name'])) {
                $errors[] = "taxes[$index].tax_name is required";
            }
            if (!isset($tax['tax_percentage']) || !is_numeric($tax['tax_percentage']) || $tax['tax_percentage'] < 0 || $tax['tax_percentage'] > 100) {
                $errors[] = "taxes[$index].tax_percentage must be between 0 and 100";
            }
            if (!isset($tax['tax_amount']) || !is_numeric($tax['tax_amount']) || $tax['tax_amount'] < 0) {
                $errors[] = "taxes[$index].tax_amount must be positive number";
            }
        }
    }

    if (empty($errors)) {
        echo "✅ VALIDATION PASSED\n";
        echo "All data is valid according to rules\n";

        // Show stats
        echo "\n📈 Data Statistics:\n";
        echo "• Bill Number: " . $data['bill_number'] . "\n";
        echo "• Vendor: " . $data['vendor_name'] . "\n";
        echo "• Total: $" . number_format($data['total'], 2) . "\n";
        echo "• Line Items: " . count($data['line_items']) . "\n";
        if (!empty($data['taxes'])) {
            echo "• Taxes: " . count($data['taxes']) . "\n";
            foreach ($data['taxes'] as $tax) {
                echo "  - " . $tax['tax_name'] . ": " . $tax['tax_percentage'] . "% ($" . number_format($tax['tax_amount'], 2) . ")\n";
            }
        }
    } else {
        echo "❌ VALIDATION FAILED\n";
        echo "Errors found:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
}

// Test both datasets
testValidation($sampleDataSimple, "Simple Purchase Order (No Taxes)");
testValidation($sampleDataWithTaxes, "Purchase Order with ITBMS Taxes");

// Test edge cases
echo "\n🧪 Testing Edge Cases:\n";
echo "======================\n";

$edgeCases = [
    'Bill number too long' => [
        "bill_number" => str_repeat('A', 25),
        "vendor_name" => "Test",
        "vendor_id" => "test",
        "total" => 100,
        "sub_total" => 100,
        "line_items" => []
    ],
    'Missing required fields' => [
        "bill_number" => "TEST-001"
        // Missing other required fields
    ],
    'Empty line items' => [
        "bill_number" => "TEST-002",
        "vendor_name" => "Test Vendor",
        "vendor_id" => "test",
        "total" => 100,
        "sub_total" => 100,
        "line_items" => []
    ]
];

foreach ($edgeCases as $caseName => $caseData) {
    echo "\nTesting: $caseName\n";
    testValidation($caseData, $caseName);
}

echo "\n🎯 All Tests Completed\n";

?>
