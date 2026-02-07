#!/bin/bash

# Script de prueba simplificado para verificar cálculo de impuestos
# Ejecutar desde raíz del proyecto:
# bash docs/testing/test-qb-tax-calculation-simple.sh

echo "=== TEST: Verificación de Cálculo de Impuestos Multi-Tasa ==="
echo ""
echo "Datos de prueba:"
echo "  Línea 1: \$1.00 × 10% ITBMS = \$0.10"
echo "  Línea 2: \$1.00 × 7% ITBMS = \$0.07"
echo "  Total ITBMS esperado: \$0.17"
echo ""
echo "=== Estructura del Objeto QuickBooks ==="
cat <<'EOF'
{
    "Invoice": {
        "DocNumber": "PRUEBA01",
        "TotalAmt": 2.17,
        "Line": [
            {
                "Id": "1",
                "Description": "PRUEBA10%",
                "Amount": 1,
                "SalesItemLineDetail": {
                    "TaxCodeRef": {"value": "15"},
                    "TaxCode": {
                        "id": "15",
                        "name": "ITBMS10",
                        "rateValue": 10  ← PRIORIDAD 1
                    }
                }
            },
            {
                "Id": "2",
                "Description": "PRUEBA 7%",
                "Amount": 1,
                "SalesItemLineDetail": {
                    "TaxCodeRef": {"value": "13"},
                    "TaxCode": {
                        "id": "13",
                        "name": "ITBMS7",
                        "rateValue": 7  ← PRIORIDAD 1
                    }
                }
            }
        ],
        "TxnTaxDetail": {
            "TotalTax": 0.17
        }
    }
}
EOF

echo ""
echo "=== Lógica de Cálculo en QuickBooksOnlineService::calculateLineTax() ==="
echo ""
echo "PRIORIDAD 1: TaxCode.rateValue (✓ Presente en ambas líneas)"
echo "  if (isset(\$salesItemDetail['TaxCode']['rateValue']) && \$rateValue > 0)"
echo "  → Se usará esta prioridad"
echo ""
echo "PRIORIDAD 2: TaxAmount field (No presente)"
echo "PRIORIDAD 3: Tax Code Mapping via ACI Cloud (No necesario)"
echo "PRIORIDAD 4: Distribución proporcional (Fallback no usado)"
echo ""
echo "=== Cálculos Esperados ==="
echo ""
echo "Línea 1:"
echo "  lineAmount = 1.00"
echo "  rateValue = 10"
echo "  tax = 1.00 × (10 / 100) = 0.10 ✓"
echo "  method = 'tax_code_rate_value'"
echo "  percent = 10"
echo ""
echo "Línea 2:"
echo "  lineAmount = 1.00"
echo "  rateValue = 7"
echo "  tax = 1.00 × (7 / 100) = 0.07 ✓"
echo "  method = 'tax_code_rate_value'"
echo "  percent = 7"
echo ""
echo "=== Resultado en Sales_Detail_Imp ==="
echo ""
echo "┌──────────┬──────────────┬──────────┬──────────┬──────────┬──────────┐"
echo "│Sequential│ Description  │ Sub_Total│   Itbms  │ Net_line │ Taxable  │"
echo "├──────────┼──────────────┼──────────┼──────────┼──────────┼──────────┤"
echo "│    1     │ PRUEBA10%    │   1.00   │   0.10   │   1.10   │    1     │"
echo "│    2     │ PRUEBA 7%    │   1.00   │   0.07   │   1.07   │    1     │"
echo "└──────────┴──────────────┴──────────┴──────────┴──────────┴──────────┘"
echo ""
echo "Total ITBMS: \$0.17 ✓ (coincide con TxnTaxDetail.TotalTax)"
echo ""
echo "=== Logs Esperados en storage/logs/laravel.log ==="
echo ""
cat <<'EOF'
[INFO] QuickBooks Line Tax Calculation
{
    "line_id": "1",
    "line_amount": 1.0,
    "tax_code_ref": "15",
    "tax_calculated": 0.1,
    "tax_percent_applied": 10,
    "calculation_method": "tax_code_rate_value",
    "tax_code_rate": 10
}

[INFO] QuickBooks Line Tax Calculation
{
    "line_id": "2",
    "line_amount": 1.0,
    "tax_code_ref": "13",
    "tax_calculated": 0.07,
    "tax_percent_applied": 7,
    "calculation_method": "tax_code_rate_value",
    "tax_code_rate": 7
}
EOF

echo ""
echo "=== CONCLUSIÓN ==="
echo ""
echo "✓ El código actual DEBERÍA procesar correctamente este objeto"
echo "✓ NO requiere llamada a ACI Cloud (rateValue presente)"
echo "✓ Usa PRIORIDAD 1: tax_code_rate_value"
echo "✓ Valores de impuesto correctos por línea"
echo ""
echo "Para verificar en producción:"
echo "1. Procesar factura PRUEBA01 desde QuickBooks"
echo "2. Revisar logs: grep 'QuickBooks Line Tax Calculation' storage/logs/laravel.log | tail -20"
echo "3. Verificar BD: SELECT Sequential, Description, Sub_Total, Itbms, Net_line FROM Sales_Detail_Imp WHERE InvoiceNumber='PRUEBA01'"
echo ""
