#!/bin/bash

# Script de prueba para la API de Vendor Payment
# Ubicación: docs/testing/vendor-payment-api-test.sh

echo "========================================"
echo "Prueba de API Vendor Payment - AciCloud"
echo "========================================"
echo ""

# Configuración
BASE_URL="${BASE_URL:-http://localhost}"
TOKEN="${TOKEN:-your-token-here}"
ORG_ID="${ORG_ID:-1}"

echo "Configuración:"
echo "  URL: $BASE_URL"
echo "  Organization ID: $ORG_ID"
echo ""

# Test 1: Crear Vendor Payment
echo "Test 1: Crear Vendor Payment"
echo "POST /api/acicloud/vendor_payment"
echo ""

curl -X POST "$BASE_URL/api/acicloud/vendor_payment" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Organization-Id: $ORG_ID" \
  -d '{
    "CheckNumber": "CHK-001-TEST",
    "VendorID": "V123",
    "VendorName": "Proveedor Test",
    "Date": "2025-11-25",
    "Total": 1000.00,
    "Memo": "Pago a proveedor test",
    "CashAccountID": "1100",
    "Method_of_payment": "CHECK",
    "PrePayment": false,
    "Items": [
      {
        "Item_Id": "ITEM001",
        "Description": "Producto 1",
        "GL_Acct": "5100",
        "Quantity": 2.0,
        "Unit_Price": 250.00,
        "Net_Line": 500.00,
        "ApplyTo": 123,
        "InvoiceNumber": "INV-001"
      },
      {
        "Item_Id": "ITEM002",
        "Description": "Producto 2",
        "GL_Acct": "5200",
        "Quantity": 1.0,
        "Unit_Price": 500.00,
        "Net_Line": 500.00,
        "ApplyTo": 124,
        "InvoiceNumber": "INV-002"
      }
    ]
  }'

echo ""
echo ""
echo "========================================"
echo ""

# Test 2: Listar Vendor Payments
echo "Test 2: Listar Vendor Payments"
echo "GET /api/acicloud/vendor_payment_imp"
echo ""

curl -X GET "$BASE_URL/api/acicloud/vendor_payment_imp?limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Organization-Id: $ORG_ID"

echo ""
echo ""
echo "========================================"
echo ""

# Test 3: Filtrar por VendorID
echo "Test 3: Filtrar por VendorID"
echo "GET /api/acicloud/vendor_payment_imp?VendorID=V123"
echo ""

curl -X GET "$BASE_URL/api/acicloud/vendor_payment_imp?VendorID=V123&limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Organization-Id: $ORG_ID"

echo ""
echo ""
echo "========================================"
echo ""

# Test 4: Filtrar por rango de fechas
echo "Test 4: Filtrar por rango de fechas"
echo "GET /api/acicloud/vendor_payment_imp?Date[from]=2025-11-01&Date[to]=2025-11-30"
echo ""

curl -X GET "$BASE_URL/api/acicloud/vendor_payment_imp?Date[from]=2025-11-01&Date[to]=2025-11-30&limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Organization-Id: $ORG_ID"

echo ""
echo ""
echo "========================================"
echo "Pruebas completadas"
echo "========================================"
