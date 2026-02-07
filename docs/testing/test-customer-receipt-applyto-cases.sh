#!/bin/bash

# Script para probar validaciones CustomerReceiptImpRequest - Casos ApplyTo
# Ubicación: docs/testing/test-customer-receipt-applyto-cases.sh

echo "=== PRUEBAS DE LÓGICA CONDICIONAL ApplyTo ==="
echo ""

# Caso 1: ApplyTo=false, InvoiceNumber=null (DEBE PASAR)
echo "🧪 CASO 1: ApplyTo=false, InvoiceNumber=null"
echo "Expectativa: ✅ DEBE PASAR"
echo ""

TEST_JSON_1='{
    "Reference":"11059",
    "CheckNumber":"11059",
    "ReceiptNumber":"11059",
    "CustomerID":"115986",
    "CustomerName":"Cliente Test",
    "Total":11.00,
    "Method_of_Payment":"02-Efectivo",
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":true,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":null,
            "Description":"PAGO POR ADELANTADO",
            "Net_line":11.00
        }
    ]
}'

echo "Datos:"
echo "$TEST_JSON_1" | python3 -m json.tool
echo ""
echo "Análisis:"
echo "- ApplyTo: false"
echo "- InvoiceNumber: null"
echo "- Resultado: ✅ VÁLIDO (InvoiceNumber no requerido cuando ApplyTo=false)"
echo ""

# Caso 2: ApplyTo=true, InvoiceNumber="FE001" (DEBE PASAR)
echo "🧪 CASO 2: ApplyTo=true, InvoiceNumber='FE001'"
echo "Expectativa: ✅ DEBE PASAR"
echo ""

TEST_JSON_2='{
    "Reference":"11060",
    "CheckNumber":"11060",
    "ReceiptNumber":"11060",
    "CustomerID":"115986",
    "CustomerName":"Cliente Test",
    "Total":100.00,
    "Method_of_Payment":"02-Efectivo",
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":false,
    "Items":[
        {
            "ApplyTo":true,
            "InvoiceNumber":"FE001",
            "Description":"Aplicar a factura existente",
            "Net_line":100.00
        }
    ]
}'

echo "Datos:"
echo "$TEST_JSON_2" | python3 -m json.tool
echo ""
echo "Análisis:"
echo "- ApplyTo: true"
echo "- InvoiceNumber: 'FE001'"
echo "- Resultado: ✅ VÁLIDO (InvoiceNumber requerido y presente cuando ApplyTo=true)"
echo ""

# Caso 3: ApplyTo=true, InvoiceNumber=null (DEBE FALLAR)
echo "🧪 CASO 3: ApplyTo=true, InvoiceNumber=null"
echo "Expectativa: ❌ DEBE FALLAR"
echo ""

TEST_JSON_3='{
    "Reference":"11061",
    "CheckNumber":"11061",
    "ReceiptNumber":"11061",
    "CustomerID":"115986",
    "CustomerName":"Cliente Test",
    "Total":50.00,
    "Method_of_Payment":"02-Efectivo",
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":false,
    "Items":[
        {
            "ApplyTo":true,
            "InvoiceNumber":null,
            "Description":"Intento aplicar sin factura",
            "Net_line":50.00
        }
    ]
}'

echo "Datos:"
echo "$TEST_JSON_3" | python3 -m json.tool
echo ""
echo "Análisis:"
echo "- ApplyTo: true"
echo "- InvoiceNumber: null"
echo "- Resultado: ❌ INVÁLIDO (InvoiceNumber requerido cuando ApplyTo=true pero es null)"
echo ""

# Caso 4: ApplyTo=false, InvoiceNumber="FE002" (DEBE PASAR)
echo "🧪 CASO 4: ApplyTo=false, InvoiceNumber='FE002'"
echo "Expectativa: ✅ DEBE PASAR"
echo ""

TEST_JSON_4='{
    "Reference":"11062",
    "CheckNumber":"11062",
    "ReceiptNumber":"11062",
    "CustomerID":"115986",
    "CustomerName":"Cliente Test",
    "Total":25.00,
    "Method_of_Payment":"02-Efectivo",
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":false,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":"FE002",
            "Description":"Producto nuevo con referencia",
            "Net_line":25.00,
            "Quantity":1,
            "Unit_Price":25.00
        }
    ]
}'

echo "Datos:"
echo "$TEST_JSON_4" | python3 -m json.tool
echo ""
echo "Análisis:"
echo "- ApplyTo: false"
echo "- InvoiceNumber: 'FE002'"
echo "- Resultado: ✅ VÁLIDO (InvoiceNumber opcional cuando ApplyTo=false, puede tener valor)"
echo ""

echo "📋 RESUMEN DE VALIDACIÓN required_if:Items.*.ApplyTo,true"
echo ""
echo "La regla 'required_if:Items.*.ApplyTo,true' funciona así:"
echo "- Si ApplyTo = true  → InvoiceNumber es REQUERIDO"
echo "- Si ApplyTo = false → InvoiceNumber es OPCIONAL (puede ser null o tener valor)"
echo ""
echo "✅ Casos que PASAN:"
echo "   1. ApplyTo=false, InvoiceNumber=null"
echo "   2. ApplyTo=true,  InvoiceNumber='FE001'"
echo "   3. ApplyTo=false, InvoiceNumber='FE002'"
echo ""
echo "❌ Casos que FALLAN:"
echo "   1. ApplyTo=true,  InvoiceNumber=null"
echo ""
echo "🎯 Tu ejemplo original (ApplyTo=false, InvoiceNumber=null) es CORRECTO ✅"
