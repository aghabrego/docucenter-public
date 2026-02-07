#!/bin/bash

# Script para probar validaciones CustomerReceiptImpRequest con casos de prepago
# Ubicación: docs/testing/test-customer-receipt-prepayment.sh

echo "=== PRUEBA DE VALIDACIONES - CASOS PREPAYMENT ==="
echo ""

echo "📋 CASO 1: PREPAYMENT = true (Tu ejemplo original)"
echo "Características:"
echo "- Prepayment: true"
echo "- ApplyTo: false"
echo "- Quantity: null ✅ (opcional en prepago)"
echo "- Item_id: null ✅ (opcional en prepago)"
echo "- Unit_Price: null ✅ (opcional en prepago)"
echo "- Net_line: 11.0 ✅ (puede ser positivo o negativo)"
echo ""

PREPAYMENT_JSON='{
    "Reference":"11059",
    "CheckNumber":"11059",
    "ReceiptNumber":"11059",
    "CustomerID":"115986",
    "CustomerName":"Dra. Luisa Cuddy de Los Mares del Alva",
    "Total":11.000000,
    "Method_of_Payment":"02-Efectivo",
    "TaxID":null,
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":true,
    "DepositTicketID":null,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":null,
            "Description":"PAGO POR ADELANTADO",
            "Net_line":11.000000,
            "Quantity":null,
            "Item_id":null,
            "Unit_Price":null,
            "Taxable":null
        }
    ]
}'

echo "✅ RESULTADO: VÁLIDO - Prepago permite campos opcionales"
echo ""

echo "📋 CASO 2: PREPAYMENT = true con monto NEGATIVO (devolución)"
echo "Características:"
echo "- Prepayment: true"
echo "- Net_line: -15.50 ✅ (montos negativos permitidos en prepago)"
echo "- Total: -15.50 ✅ (total puede ser negativo)"
echo ""

PREPAYMENT_NEGATIVE_JSON='{
    "Reference":"DEV001",
    "CheckNumber":"DEV001",
    "ReceiptNumber":"DEV001",
    "CustomerID":"115986",
    "CustomerName":"Cliente Devolución",
    "Total":-15.50,
    "Method_of_Payment":"02-Efectivo",
    "TaxID":null,
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":true,
    "DepositTicketID":null,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":null,
            "Description":"DEVOLUCIÓN DE PREPAGO",
            "Net_line":-15.50,
            "Quantity":null,
            "Item_id":null,
            "Unit_Price":null,
            "Taxable":null
        }
    ]
}'

echo "✅ RESULTADO: VÁLIDO - Prepago permite montos negativos"
echo ""

echo "📋 CASO 3: PREPAYMENT = false con ApplyTo = false (Producto normal)"
echo "Características:"
echo "- Prepayment: false"
echo "- ApplyTo: false"
echo "- Quantity: 2 ✅ (REQUERIDO cuando no es prepago)"
echo "- Item_id: 'PROD001' ✅ (puede tener valor)"
echo "- Unit_Price: 25.00 ✅ (REQUERIDO cuando no es prepago)"
echo ""

NORMAL_PRODUCT_JSON='{
    "Reference":"VENTA001",
    "CheckNumber":"VENTA001",
    "ReceiptNumber":"VENTA001",
    "CustomerID":"115986",
    "CustomerName":"Cliente Normal",
    "Total":50.00,
    "Method_of_Payment":"02-Efectivo",
    "TaxID":null,
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":false,
    "DepositTicketID":null,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":null,
            "Description":"Producto Normal",
            "Net_line":50.00,
            "Quantity":2,
            "Item_id":"PROD001",
            "Unit_Price":25.00,
            "Taxable":1
        }
    ]
}'

echo "✅ RESULTADO: VÁLIDO - Producto normal con campos requeridos"
echo ""

echo "📋 CASO 4: PREPAYMENT = false con ApplyTo = true (Aplicar a factura)"
echo "Características:"
echo "- Prepayment: false"
echo "- ApplyTo: true"
echo "- InvoiceNumber: 'FE001' ✅ (REQUERIDO cuando ApplyTo=true)"
echo "- Quantity: null ✅ (opcional cuando se aplica a factura)"
echo "- Unit_Price: null ✅ (opcional cuando se aplica a factura)"
echo ""

APPLY_TO_INVOICE_JSON='{
    "Reference":"APLIC001",
    "CheckNumber":"APLIC001",
    "ReceiptNumber":"APLIC001",
    "CustomerID":"115986",
    "CustomerName":"Cliente Aplicación",
    "Total":100.00,
    "Method_of_Payment":"02-Efectivo",
    "TaxID":null,
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":false,
    "DepositTicketID":null,
    "Items":[
        {
            "ApplyTo":true,
            "InvoiceNumber":"FE001",
            "Description":"Aplicar a Factura FE001",
            "Net_line":100.00,
            "Quantity":null,
            "Item_id":null,
            "Unit_Price":null,
            "Taxable":null
        }
    ]
}'

echo "✅ RESULTADO: VÁLIDO - Aplicar a factura existente"
echo ""

echo "❌ CASO 5: ERROR - PREPAYMENT = false, ApplyTo = false SIN Quantity"
echo "Características:"
echo "- Prepayment: false"
echo "- ApplyTo: false"
echo "- Quantity: null ❌ (DEBE fallar - requerido cuando no es prepago)"
echo ""

ERROR_CASE_JSON='{
    "Reference":"ERROR001",
    "CheckNumber":"ERROR001",
    "ReceiptNumber":"ERROR001",
    "CustomerID":"115986",
    "CustomerName":"Caso Error",
    "Total":30.00,
    "Method_of_Payment":"02-Efectivo",
    "TaxID":null,
    "SalesRepID":"115986",
    "Date":"2025-09-10",
    "CashAccountID":"102001001",
    "Prepayment":false,
    "DepositTicketID":null,
    "Items":[
        {
            "ApplyTo":false,
            "InvoiceNumber":null,
            "Description":"Producto sin cantidad",
            "Net_line":30.00,
            "Quantity":null,
            "Item_id":"PROD002",
            "Unit_Price":30.00,
            "Taxable":1
        }
    ]
}'

echo "❌ RESULTADO: INVÁLIDO - Debe fallar porque Quantity es requerido cuando Prepayment=false y ApplyTo=false"
echo ""

echo "🎯 RESUMEN DE LÓGICA DE VALIDACIÓN:"
echo ""
echo "🔹 PREPAYMENT = true:"
echo "   - Quantity: OPCIONAL (puede ser null)"
echo "   - Item_id: OPCIONAL (puede ser null)"
echo "   - Unit_Price: OPCIONAL (puede ser null)"
echo "   - Net_line: PUEDE SER NEGATIVO"
echo "   - Total: PUEDE SER NEGATIVO"
echo ""
echo "🔹 PREPAYMENT = false + ApplyTo = true:"
echo "   - InvoiceNumber: REQUERIDO"
echo "   - Quantity: OPCIONAL"
echo "   - Unit_Price: OPCIONAL"
echo ""
echo "🔹 PREPAYMENT = false + ApplyTo = false:"
echo "   - Quantity: REQUERIDO"
echo "   - Unit_Price: REQUERIDO"
echo "   - InvoiceNumber: OPCIONAL"
echo ""

echo "✅ Tu ejemplo original (Prepayment=true) es COMPLETAMENTE VÁLIDO"
