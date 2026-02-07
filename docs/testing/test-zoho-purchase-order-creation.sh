#!/bin/bash

# Script para probar la creación de orden de compra de Zoho
# Datos basados en el log real proporcionado

ORGANIZATION_ID=${1:-1}
BASE_URL="http://localhost"

echo "=== Prueba de Creación de Orden de Compra Zoho ==="
echo "Organization ID: $ORGANIZATION_ID"
echo "URL: $BASE_URL"
echo ""

# Crear token de prueba (simulado - en producción necesitarías un token real)
echo "⚠ NOTA: Esta prueba requiere autenticación válida."
echo "En producción, necesitarías:"
echo "1. Un usuario autenticado"
echo "2. Token de API válido"
echo "3. Middleware configurado correctamente"
echo ""

# Datos de payload basados en el log real
cat > /tmp/zoho_purchase_order_payload.json << 'EOF'
{
    "date": "2025-10-14",
    "discount_amount_formatted": "$0.00",
    "purchaseorder_ids": "",
    "due_date_formatted": "14-11-2025",
    "tax_total": 42,
    "adjustment_formatted": "$0.00",
    "is_discount_before_tax": false,
    "balance_formatted": "$642.00",
    "discount_amount": 0,
    "payments": [],
    "discount_account_id": "",
    "discount": 0,
    "billing_address": "{\"zip\":\"\",\"country\":\"\",\"address\":\"\",\"city\":\"\",\"phone\":\"\",\"attention\":\"\",\"street2\":\"\",\"state\":\"\",\"fax\":\"\"}",
    "line_items": "[{\"line_item_id\":\"6088114000000497044\",\"item_type\":\"inventory\",\"item_type_formatted\":\"Artículos de inventario\",\"discount\":0,\"receipt_line_item_id\":\"\",\"project_name\":\"\",\"location_id\":\"6088114000000091182\",\"receive_id\":\"\",\"item_matching_type\":\"\",\"image_name\":\"\",\"sales_rate_formatted\":\"$7.00\",\"discounts\":[],\"project_id\":\"\",\"invoice_id\":\"\",\"sku\":\"V504413\",\"invoice_number\":\"\",\"purchaseorder_item_id\":\"\",\"pricebook_id\":\"\",\"image_type\":\"\",\"bcy_rate_formatted\":\"$3.00\",\"image_document_id\":\"\",\"item_total\":600,\"tax_id\":\"6088114000000466001\",\"tags\":[],\"location_name\":\"Oficina principal\",\"is_dropshipped_item\":false,\"unit\":\"und\",\"tax_type\":\"tax\",\"name\":\"DIENAT G Vainilla\",\"track_batch_number\":false,\"receive_item_id\":\"\",\"markup_percent_formatted\":\"0.00%\",\"is_storage_location_enabled\":false,\"purchase_request_items\":[],\"is_landedcost\":false,\"bcy_rate\":3,\"item_total_formatted\":\"$600.00\",\"is_combo_product\":false,\"rate_formatted\":\"$3.00\",\"discount_account_name\":\"\",\"header_id\":\"\",\"purchaseorder_id\":\"\",\"discount_account_id\":\"\",\"description\":\"\",\"item_order\":1,\"batches\":[],\"rate\":3,\"sales_margin\":\"\",\"account_name\":\"Activo de inventario\",\"package_details\":{\"weight_unit\":\"kg\",\"length\":\"\",\"width\":\"\",\"weight\":\"\",\"dimension_unit\":\"cm\",\"height\":\"\"},\"sales_rate\":7,\"quantity\":200,\"item_id\":\"6088114000000463033\",\"track_batch_for_receive\":false,\"tax_name\":\"ITBMS\",\"is_billable\":false,\"header_name\":\"\",\"item_custom_fields\":[],\"line_item_taxes\":[{\"tax_amount\":42,\"tax_name\":\"ITBMS (7%)\",\"tax_amount_formatted\":\"$42.00\",\"tax_id\":\"6088114000000466001\"}],\"markup_percent\":0,\"account_id\":\"6088114000000034001\",\"tax_percentage\":7,\"customer_name\":\"\",\"customer_id\":\"\"}]",
    "payment_terms": "",
    "currency_code": "PAB",
    "payment_expected_date_formatted": "14-11-2025",
    "total": 642,
    "bill_number": "883914",
    "balance": 642,
    "tax_total_formatted": "$42.00",
    "date_formatted": "14-10-2025",
    "custom_field_hash": "{}",
    "sub_total_formatted": "$600.00",
    "bill_id": "6088114000000497043",
    "adjustment_description": "",
    "exchange_rate": 1,
    "currency_symbol": "$",
    "custom_fields": [],
    "due_date": "2025-11-14",
    "vendor_name": "LAREPA TRADING",
    "payment_made_formatted": "$0.00",
    "vendor_credits": [],
    "status_formatted": "Abierto",
    "payment_expected_date": "2025-11-14",
    "reference_number": "",
    "purchaseorders": [],
    "payment_made": 0,
    "allocated_landed_costs": [],
    "recurring_bill_id": "",
    "vendor_id": "6088114000000414053",
    "sub_total": 600,
    "adjustment": 0,
    "total_formatted": "$642.00",
    "status": "open"
}
EOF

echo "📝 Payload preparado en /tmp/zoho_purchase_order_payload.json"
echo ""

echo "🔧 Para probar manualmente, puedes usar:"
echo ""
echo "curl -X POST \\"
echo "  $BASE_URL/api/acicloud/create_purchase_zoho \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'Authorization: Bearer YOUR_TOKEN' \\"
echo "  -d @/tmp/zoho_purchase_order_payload.json"
echo ""

echo "📊 También puedes usar el comando de diagnóstico directo:"
echo "docker exec -it docucenter_laravel.test php artisan zoho:diagnose-purchase-order $ORGANIZATION_ID"
echo ""

echo "🎯 La prueba más directa es ejecutar el importer en el contenedor:"
echo ""
cat << 'EOF'
# En el contenedor Docker:
php artisan tinker --execute="
\$organizationId = 1;
\$organization = App\Models\Organization::find(\$organizationId);
DB::connection()->useDatabase(\$organization->database);

\$testData = [
    'bill_number' => '883914',
    'vendor_id' => '6088114000000414053',
    'vendor_name' => 'LAREPA TRADING',
    'total' => 642,
    'sub_total' => 600,
    'date' => '2025-10-14',
    'due_date' => '2025-11-14',
    'custom_field_hash' => [],
    'line_items' => json_encode([
        [
            'item_id' => '6088114000000463033',
            'sku' => 'V504413',
            'name' => 'DIENAT G Vainilla',
            'quantity' => 200,
            'rate' => 3,
            'item_total' => 600,
            'account_name' => 'Activo de inventario'
        ]
    ])
];

\$transformer = new App\Services\Zoho\ZohoPurchaseOrderTransformer();
\$importer = new App\Services\Zoho\ZohoPurchaseOrderImporter(\$transformer);
\$result = \$importer->importPurchaseOrder(\$testData, \$organizationId);
print_r(\$result);
"
EOF

echo ""
echo "=== Script de Prueba Creado ==="
echo "Este script proporciona varias opciones para probar la funcionalidad"
echo "de creación de órdenes de compra desde Zoho."
