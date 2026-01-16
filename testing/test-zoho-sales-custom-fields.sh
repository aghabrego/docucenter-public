#!/bin/bash

# Script de prueba para API Zoho Sales Order con custom_field_hash
# Uso: ./test-zoho-sales-custom-fields.sh [org_id]

set -e

ORG_ID=${1:-1}
BASE_URL="http://localhost"
ENDPOINT="/api/acicloud/create_sale_order_zoho"

echo "=== Test Zoho Sales Order API con Custom Fields ==="
echo "URL: ${BASE_URL}${ENDPOINT}"
echo "Organization ID: ${ORG_ID}"
echo "Fecha: $(date)"
echo ""

# JSON con custom_field_hash incluyendo cf_sagecustomerid
JSON_DATA='{
  "salesorder_id": "TEST_CF_SALES_'$(date +%s)'",
  "salesorder_number": "SO_CF_001_'$(date +%s)'",
  "customer_id": "ZOHO_CUSTOMER_001",
  "customer_name": "Cliente con Custom Fields",
  "date": "'$(date -I)'",
  "shipment_date": "'$(date -I -d '+7 days')'",
  "currency_code": "USD",
  "sub_total": "200.0",
  "tax_total": "14.0",
  "total": "214.0",
  "status": "confirmed",
  "discount": "0.0",
  "adjustment": "0.0",
  "shipping_charge": "0.0",
  "reference_number": "REF_CF_SALES_001",
  "terms": "NET_30",
  "delivery_method": "Courier",
  "note": "Orden de venta con custom fields para testing",
  "custom_field_hash": {
    "cf_sagecustomerid": "SAGE_CUSTOMER_CF_001",
    "cf_sales_region": "panama_city",
    "cf_priority_customer": "true",
    "cf_credit_limit": "75000",
    "cf_sales_rep": "SALES_REP_001"
  },
  "billing_address": {
    "company_name": "Empresa Custom Fields S.A.",
    "address": "Avenida Balboa 123",
    "street2": "Edificio Torre del Mar, Piso 15",
    "city": "Ciudad de Panamá",
    "state": "Panamá",
    "zip": "00001",
    "country": "Panamá",
    "phone": "+507 123-4567",
    "fax": "+507 123-4568"
  },
  "shipping_address": {
    "company_name": "Empresa Custom Fields - Almacén Principal",
    "address": "Zona Industrial de Corozal 456",
    "street2": "Bodega 15-A",
    "city": "Panamá",
    "state": "Panamá",
    "zip": "00002",
    "country": "Panamá",
    "phone": "+507 234-5678"
  },
  "line_items": [
    {
      "line_item_id": "TEST_LI_CF_SALES_001_'$(date +%s)'",
      "item_id": "PROD_CF_001",
      "sku": "ELEC_CF_001",
      "name": "Producto Electrónico Premium",
      "description": "Dispositivo electrónico con custom fields",
      "item_order": 1,
      "quantity": 10,
      "rate": 15.0,
      "item_total": 150.0,
      "unit": "pcs",
      "tax_percentage": 7,
      "tax_id": "ITBMS_7",
      "tax_name": "ITBMS",
      "custom_field_hash": {
        "cf_product_sage_id": "SAGE_PROD_CF_001",
        "cf_product_category": "electronics",
        "cf_warranty_months": "24"
      },
      "item_custom_fields": []
    },
    {
      "line_item_id": "TEST_LI_CF_SALES_002_'$(date +%s)'",
      "item_id": "PROD_CF_002",
      "sku": "ACC_CF_002",
      "name": "Accesorio Compatible",
      "description": "Accesorio compatible",
      "item_order": 2,
      "quantity": 5,
      "rate": 10.0,
      "item_total": 50.0,
      "unit": "pcs",
      "tax_percentage": 7,
      "tax_id": "ITBMS_7",
      "tax_name": "ITBMS",
      "custom_field_hash": {
        "cf_product_sage_id": "SAGE_PROD_CF_002",
        "cf_product_category": "accessories"
      },
      "item_custom_fields": []
    }
  ],
  "taxes": [
    {
      "tax_name": "ITBMS (7%)",
      "tax_amount": 14.0,
      "tax_amount_formatted": "$14.00",
      "tax_id": "ITBMS_7"
    }
  ]
}'

echo "📝 JSON Request:"
echo "$JSON_DATA" | jq '.' 2>/dev/null || echo "$JSON_DATA"
echo ""

echo "🚀 Enviando request..."
echo ""

# Realizar el request
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}\nTIME_TOTAL:%{time_total}" \
  -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Organization-ID: ${ORG_ID}" \
  -d "$JSON_DATA" \
  "${BASE_URL}${ENDPOINT}")

# Extraer código de respuesta y tiempo
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
TIME_TOTAL=$(echo "$RESPONSE" | grep "TIME_TOTAL:" | cut -d: -f2)
RESPONSE_BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d' | sed '/TIME_TOTAL:/d')

echo "📊 Resultado:"
echo "HTTP Code: $HTTP_CODE"
echo "Tiempo: ${TIME_TOTAL}s"
echo ""

echo "📦 Response Body:"
echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
echo ""

# Verificar resultado
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "201" ]; then
    echo "✅ SUCCESS: Sales Order procesada correctamente"

    # Verificar si contiene información sobre cf_sagecustomerid en logs
    echo ""
    echo "📋 Verificando logs de custom fields..."
    echo "Buscar en logs: 'cf_sagecustomerid: SAGE_CUSTOMER_CF_001'"

elif [ "$HTTP_CODE" = "422" ]; then
    echo "❌ VALIDATION ERROR: Error de validación"
    echo "$RESPONSE_BODY" | jq '.errors' 2>/dev/null || echo "Error parsing validation errors"

else
    echo "❌ ERROR: HTTP $HTTP_CODE"
    echo "Response: $RESPONSE_BODY"
fi

echo ""
echo "=== Test completado ==="
