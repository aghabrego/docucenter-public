#!/bin/bash
# Script de prueba completo para createPurchaseOrderZoho API
# Basado en datos reales del log del 13/10/2025

echo "🧪 Test API createPurchaseOrderZoho - Datos Reales"
echo "=================================================="

# Configuración
API_URL="https://apconpanama.me/api/acicloud/create_purchase_zoho"
AUTH_TOKEN="57|NAqpoN7IHEK2r2xxqtHsidKmfIdV5KP6WNQkiiH08b2a68ec"

echo "📋 Configuración:"
echo "  - API URL: $API_URL"
echo "  - Auth Token: ${AUTH_TOKEN:0:20}..."
echo ""

# Test 1: Objeto exacto del log real
echo "🚀 Test 1: Objeto exacto basado en log real"
echo "-------------------------------------------"

BILL_NUMBER_1="R$(date +%s | tail -c 6)"

curl -X POST "$API_URL" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "User-Agent: Deluge" \
  -H "Accept: application/json" \
  -d "date=2025-10-06" \
  -d "discount_amount_formatted=\$0.00" \
  -d "purchaseorder_ids=[]" \
  -d "due_date_formatted=06 oct 2025" \
  -d "tax_total=42.0" \
  -d "adjustment_formatted=\$0.00" \
  -d "is_discount_before_tax=true" \
  -d "balance_formatted=\$642.00" \
  -d "discount_amount=0.0" \
  -d "payments=[]" \
  -d "discount_account_id=6088114000000072001" \
  -d "discount=0.0" \
  -d 'billing_address={"zip":"","country":"","address":"","city":"","phone":"","attention":"","street2":"","state":"","fax":""}' \
  -d 'line_items=[{"line_item_id":"6088114000000497044","item_type":"inventory","item_type_formatted":"Artículos de inventario","discount":0,"receipt_line_item_id":"","project_name":"","location_id":"6088114000000091182","receive_id":"","item_matching_type":"","image_name":"","sales_rate_formatted":"$7.00","discounts":[],"project_id":"","invoice_id":"","sku":"V504413","invoice_number":"","purchaseorder_item_id":"","pricebook_id":"","image_type":"","bcy_rate_formatted":"$3.00","image_document_id":"","item_total":600,"tax_id":"6088114000000466001","tags":[],"location_name":"Oficina principal","is_dropshipped_item":false,"unit":"und","tax_type":"tax","name":"DIENAT G Vainilla","track_batch_number":false,"receive_item_id":"","markup_percent_formatted":"0.00%","is_storage_location_enabled":false,"purchase_request_items":[],"is_landedcost":false,"bcy_rate":3,"item_total_formatted":"$600.00","is_combo_product":false,"rate_formatted":"$3.00","discount_account_name":"","header_id":"","purchaseorder_id":"","discount_account_id":"","description":"","item_order":1,"batches":[],"rate":3,"sales_margin":"","account_name":"Activo de inventario","package_details":{"weight_unit":"kg","length":"","width":"","weight":"","dimension_unit":"cm","height":""},"sales_rate":7,"quantity":200,"item_id":"6088114000000463033","track_batch_for_receive":false,"tax_name":"ITBMS","is_billable":false,"header_name":"","item_custom_fields":[],"line_item_taxes":[{"tax_amount":42,"tax_name":"ITBMS (7%)","tax_amount_formatted":"$42.00","tax_id":"6088114000000466001"}],"markup_percent":0,"account_id":"6088114000000034001","tax_percentage":7,"customer_name":"","customer_id":""}]' \
  -d "payment_terms=0" \
  -d "currency_code=USD" \
  -d "payment_expected_date_formatted=" \
  -d "total=642.0" \
  -d "bill_number=$BILL_NUMBER_1" \
  -d "balance=642.0" \
  -d "tax_total_formatted=\$42.00" \
  -d "date_formatted=06 oct 2025" \
  -d "custom_field_hash={}" \
  -d "sub_total_formatted=\$600.00" \
  -d "bill_id=6088114000000497037" \
  -d "adjustment_description=Ajuste" \
  -d "exchange_rate=1.0" \
  -d "currency_symbol=\$" \
  -d "custom_fields=[]" \
  -d "due_date=2025-10-06" \
  -d "vendor_name=EA LOGISTIC ULTD" \
  -d "payment_made_formatted=\$0.00" \
  -d "vendor_credits=[]" \
  -d "status_formatted=Vencido" \
  -d "payment_expected_date=" \
  -d "reference_number=" \
  -d "purchaseorders=[]" \
  -d "payment_made=0.0" \
  -d "allocated_landed_costs=[]" \
  -d "recurring_bill_id=" \
  -d "vendor_id=6088114000000487410" \
  -d "sub_total=600.0" \
  -d "adjustment=0.0" \
  -d "total_formatted=\$642.00" \
  -d "status=overdue"

echo ""
echo "✅ Test 1 completado"
echo ""

# Test 2: Con custom fields poblados
echo "🚀 Test 2: Con custom fields (cf_sagevendorid)"
echo "----------------------------------------------"

BILL_NUMBER_2="T$(date +%s | tail -c 6)"

curl -X POST "$API_URL" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "User-Agent: Deluge" \
  -H "Accept: application/json" \
  -d "date=2025-10-13" \
  -d "bill_number=$BILL_NUMBER_2" \
  -d "vendor_id=6088114000000487410" \
  -d "vendor_name=EA LOGISTIC ULTD" \
  -d "sub_total=1000.0" \
  -d "total=1070.0" \
  -d "tax_total=70.0" \
  -d "currency_code=USD" \
  -d "exchange_rate=1.0" \
  -d "due_date=2025-10-20" \
  -d "status=draft" \
  -d 'custom_field_hash={"cf_sagevendorid":"SAGE_V001"}' \
  -d 'billing_address={"zip":"0001","country":"Panama","address":"Calle 50","city":"Panama","phone":"507-1234-5678","attention":"Compras","street2":"Torre Global","state":"PA","fax":""}' \
  -d 'line_items=[{"line_item_id":"test_item_001","item_id":"6088114000000463033","sku":"V504413","name":"DIENAT G Vainilla TEST","quantity":250,"rate":4,"item_total":1000,"unit":"und","tax_percentage":7,"tax_name":"ITBMS","line_item_taxes":[{"tax_amount":70,"tax_name":"ITBMS (7%)","tax_amount_formatted":"$70.00","tax_id":"6088114000000466001"}]}]'

echo ""
echo "✅ Test 2 completado"
echo ""

# Test 3: Múltiples line items
echo "🚀 Test 3: Múltiples productos (line items)"
echo "-------------------------------------------"

BILL_NUMBER_3="M$(date +%s | tail -c 6)"

curl -X POST "$API_URL" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "User-Agent: Deluge" \
  -H "Accept: application/json" \
  -d "date=2025-10-13" \
  -d "bill_number=$BILL_NUMBER_3" \
  -d "vendor_id=6088114000000487410" \
  -d "vendor_name=EA LOGISTIC ULTD" \
  -d "sub_total=1800.0" \
  -d "total=1926.0" \
  -d "tax_total=126.0" \
  -d "currency_code=USD" \
  -d "exchange_rate=1.0" \
  -d "due_date=2025-10-25" \
  -d "status=pending" \
  -d 'custom_field_hash={"cf_sagevendorid":"SAGE_V002","cf_department":"Compras"}' \
  -d 'billing_address={"zip":"0002","country":"Panama","address":"Via España","city":"Panama","phone":"507-2345-6789","attention":"Gerencia","street2":"Centro Comercial","state":"PA","fax":"507-2345-6790"}' \
  -d 'line_items=[{"line_item_id":"item_001","item_id":"6088114000000463033","sku":"V504413","name":"DIENAT G Vainilla","quantity":200,"rate":4,"item_total":800,"unit":"und","tax_percentage":7,"tax_name":"ITBMS"},{"line_item_id":"item_002","item_id":"6088114000000463034","sku":"V504414","name":"DIENAT G Chocolate","quantity":250,"rate":4,"item_total":1000,"unit":"und","tax_percentage":7,"tax_name":"ITBMS"}]'

echo ""
echo "✅ Test 3 completado"
echo ""

echo "📊 Resumen de pruebas:"
echo "  - Test 1: Objeto exacto del log real"
echo "  - Test 2: Con custom fields cf_sagevendorid"
echo "  - Test 3: Múltiples productos y campos adicionales"
echo ""
echo "🔍 Para verificar resultados:"
echo "  - Bill Numbers creados: $BILL_NUMBER_1, $BILL_NUMBER_2, $BILL_NUMBER_3"
echo "  - Revisar logs de Laravel para tracking IDs"
echo ""
echo "🏁 Tests completados!"
