#!/bin/bash

source .plusmovil-token-PROD.txt

echo "======================================"
echo "REPORTE DE ERROR API PLUSMOVIL PROD"
echo "======================================"
echo ""
echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Usuario: larosemena_el_nawal"
echo ""

# Test 1: Simple query con limit
echo "Test 1: GET /com-invoices?limit=10"
echo "--------------------------------------"
curl -v -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices?limit=10" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" 2>&1 | tee test1-output.txt
echo ""
echo ""

# Test 2: Con rango de fechas (como en QA exitoso)
echo "Test 2: GET /com-invoices con invoice_date_between"
echo "--------------------------------------"
curl -v -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices?invoice_date_between=2025-12-01,2025-12-31" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" 2>&1 | tee test2-output.txt
echo ""
echo ""

# Test 3: Con filtro de total
echo "Test 3: GET /com-invoices con total_gte"
echo "--------------------------------------"
curl -v -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices?total_gte=100&limit=10" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" 2>&1 | tee test3-output.txt
echo ""
echo ""

# Test 4: Sin parámetros
echo "Test 4: GET /com-invoices (sin parámetros)"
echo "--------------------------------------"
curl -v -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" 2>&1 | tee test4-output.txt
echo ""
echo ""

# Test 5: Obtener factura específica (ID bajo)
echo "Test 5: GET /com-invoices/1"
echo "--------------------------------------"
curl -v -X GET "https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod/com-invoices/1" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" 2>&1 | tee test5-output.txt
echo ""
echo ""

echo "======================================"
echo "Tests completados. Archivos generados:"
echo "- test1-output.txt"
echo "- test2-output.txt"
echo "- test3-output.txt"
echo "- test4-output.txt"
echo "- test5-output.txt"
echo "======================================"
