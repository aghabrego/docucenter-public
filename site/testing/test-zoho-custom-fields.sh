#!/bin/bash

# Script de prueba para API Zoho Purchase Order con custom_field_hash
# Uso: ./test-zoho-custom-fields.sh [org_id]

set -e

ORG_ID=${1:-1}
BASE_URL="http://localhost"
ENDPOINT="/api/acicloud/create_purchase_zoho"

echo "=== Test Zoho Purchase Order API con Custom Fields ==="
echo "URL: ${BASE_URL}${ENDPOINT}"
echo "Organization ID: ${ORG_ID}"
echo "Fecha: $(date)"
echo ""

# JSON con custom_field_hash incluyendo cf_sagevendorid
JSON_DATA='{
  "bill_id": "TEST_CF_'$(date +%s)'",
  "bill_number": "TEST_CF_001_'$(date +%s)'",
  "reference_number": "REF_CF_001",
  "vendor_id": "ZOHO_VENDOR_001",
  "vendor_name": "Proveedor con Custom Fields",
  "date": "'$(date -I)'",
  "due_date": "'$(date -I -d '+30 days')'",
  "currency_code": "USD",
  "sub_total": "150.0",
  "tax_total": "10.5",
  "total": "160.5",
  "balance": "160.5",
  "status": "open",
  "discount_amount": "0.0",
  "is_discount_before_tax": "true",
  "custom_field_hash": {
    "cf_sagevendorid": "SAGE_VENDOR_CF_001",
    "cf_purchase_category": "medical_supplies",
    "cf_approval_required": "true",
    "cf_department": "procurement"
  },
  "billing_address": {
    "zip": "00001",
    "country": "Panama",
    "country_code": "PA",
    "address": "Zona Libre de Colon 123",
    "city": "Colon",
    "phone": "+507 321-4567",
    "county": "",
    "attention": "Departamento de Compras",
    "street2": "Edificio A, Piso 2",
    "state": "Colon",
    "state_code": "CL",
    "fax": ""
  },
  "line_items": [
    {
      "line_item_id": "TEST_LI_CF_001_'$(date +%s)'",
      "item_type": "inventory",
      "item_type_formatted": "Artículos de inventario",
      "discount": 0,
      "sku": "MED_CF_001",
      "name": "Producto con Custom Fields",
      "description": "Producto médico especializado",
      "item_order": 1,
      "rate": 10,
      "quantity": 15,
      "item_total": 150,
      "unit": "und",
      "tax_percentage": 7,
      "tax_name": "ITBMS",
      "account_name": "Inventario Médico",
      "item_custom_fields": [],
      "custom_field_hash": {
        "cf_item_sage_id": "SAGE_ITEM_CF_001",
        "cf_item_category": "medical_device",
        "cf_requires_prescription": "false"
      },
      "line_item_taxes": [
        {
          "tax_amount": 10.5,
          "tax_name": "ITBMS (7%)",
          "tax_amount_formatted": "$10.50",
          "tax_id": "TEST_TAX_ID"
        }
      ]
    }
  ],
  "taxes": [
    {
      "tax_name": "ITBMS (7%)",
      "tax_amount": 10.5,
      "tax_amount_formatted": "$10.50",
      "tax_id": "TEST_TAX_ID"
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
    echo "✅ SUCCESS: Purchase Order procesada correctamente"

    # Verificar si contiene información sobre cf_sagevendorid en logs
    echo ""
    echo "📋 Verificando logs de custom fields..."
    echo "Buscar en logs: 'cf_sagevendorid: SAGE_VENDOR_CF_001'"

elif [ "$HTTP_CODE" = "422" ]; then
    echo "❌ VALIDATION ERROR: Error de validación"
    echo "$RESPONSE_BODY" | jq '.errors' 2>/dev/null || echo "Error parsing validation errors"

else
    echo "❌ ERROR: HTTP $HTTP_CODE"
    echo "Response: $RESPONSE_BODY"
fi

echo ""
echo "=== Test completado ==="
