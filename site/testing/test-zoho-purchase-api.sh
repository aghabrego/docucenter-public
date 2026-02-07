#!/bin/bash
# Test Script para Zoho Purchase Order API
# Basado en los datos reales del log del 13/10/2025

echo "🧪 Testing Zoho Purchase Order API"
echo "=================================="

# Configuración
API_URL="https://apconpanama.me/api/acicloud/create_purchase_zoho"
AUTH_TOKEN="57|NAqpoN7IHEK2r2xxqtHsidKmfIdV5KP6WNQkiiH08b2a68ec"

# Datos de prueba (basados en log real)
BILL_NUMBER="883914-TEST-$(date +%s)"
VENDOR_ID="6088114000000487410"
VENDOR_NAME="EA LOGISTIC ULTD"

echo "📋 Datos de prueba:"
echo "  - Bill Number: $BILL_NUMBER"
echo "  - Vendor: $VENDOR_NAME"
echo "  - API URL: $API_URL"
echo ""

# Test 1: Purchase Order básica
echo "🚀 Test 1: Purchase Order básica"
echo "--------------------------------"

curl -X POST "$API_URL" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "User-Agent: Deluge" \
  -H "Accept: application/json" \
  -d "date=2025-10-13" \
  -d "bill_number=$BILL_NUMBER" \
  -d "vendor_id=$VENDOR_ID" \
  -d "vendor_name=$VENDOR_NAME" \
  -d "sub_total=600.0" \
  -d "total=642.0" \
  -d "tax_total=42.0" \
  -d "currency_code=USD" \
  -d "exchange_rate=1.0" \
  -d "due_date=2025-10-20" \
  -d "status=draft" \
  -d "custom_field_hash={}" \
  -d 'billing_address={"zip":"","country":"","address":"","city":"","phone":"","attention":"","street2":"","state":"","fax":""}' \
  -d 'line_items=[{"line_item_id":"6088114000000497044","item_id":"6088114000000463033","sku":"V504413","name":"DIENAT G Vainilla","quantity":200,"rate":3,"item_total":600,"unit":"und","tax_percentage":7,"tax_name":"ITBMS"}]' \
  | jq '.' 2>/dev/null || echo "Respuesta recibida (sin formato JSON)"

echo ""
echo "✅ Test 1 completado"
echo ""

# Test 2: Purchase Order con custom fields
echo "🚀 Test 2: Purchase Order con custom fields"
echo "-------------------------------------------"

BILL_NUMBER_2="883915-CUSTOM-$(date +%s)"

curl -X POST "$API_URL" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "User-Agent: Deluge" \
  -H "Accept: application/json" \
  -d "date=2025-10-13" \
  -d "bill_number=$BILL_NUMBER_2" \
  -d "vendor_id=$VENDOR_ID" \
  -d "vendor_name=$VENDOR_NAME" \
  -d "sub_total=1200.0" \
  -d "total=1284.0" \
  -d "tax_total=84.0" \
  -d "currency_code=USD" \
  -d "exchange_rate=1.0" \
  -d "due_date=2025-10-20" \
  -d "status=draft" \
  -d 'custom_field_hash={"cf_sagevendorid":"SAGE_VENDOR_123"}' \
  -d 'billing_address={"zip":"0000","country":"Panama","address":"Ciudad de Panama","city":"Panama","phone":"507-1234-5678","attention":"Gerencia","street2":"Edificio Torre","state":"PA","fax":""}' \
  -d 'line_items=[{"line_item_id":"6088114000000497045","item_id":"6088114000000463034","sku":"V504414","name":"DIENAT G Chocolate","quantity":400,"rate":3,"item_total":1200,"unit":"und","tax_percentage":7,"tax_name":"ITBMS"}]' \
  | jq '.' 2>/dev/null || echo "Respuesta recibida (sin formato JSON)"

echo ""
echo "✅ Test 2 completado"
echo ""

# Test 3: Verificar logs
echo "📊 Verificando logs recientes..."
echo "------------------------------"
echo "Buscar en logs: $BILL_NUMBER y $BILL_NUMBER_2"
echo ""
echo "🏁 Tests completados!"
echo "Revisa los logs de Laravel para ver el procesamiento detallado."
