#!/bin/bash

# Script para sincronizar estado de facturas emitidas
# Busca facturas que tienen CUFE pero EzeeIssued no está marcado como 1

ORGANIZATION_DATABASE="db_14034741628418_01"
INVOICE_NUMBER="5831"

echo "=== Análisis de Estado de Factura $INVOICE_NUMBER ==="
echo "Base de datos: $ORGANIZATION_DATABASE"
echo ""

echo "1. Estado actual en Sales_Header_Imp:"
docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "
USE $ORGANIZATION_DATABASE;
SELECT ID, InvoiceNumber, EzeeIssued, InvoiceNote, intuit_extracted_cufe, Date
FROM Sales_Header_Imp
WHERE InvoiceNumber = '$INVOICE_NUMBER';"

echo ""
echo "2. Verificar en fe_header:"
docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "
USE $ORGANIZATION_DATABASE;
SELECT id, dNroDF, cufe, Enviado, Error, ErrorPT, created_at
FROM fe_header
WHERE dNroDF = '$INVOICE_NUMBER';"

echo ""
echo "3. Buscar facturas con posible desincronización:"
docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "
USE $ORGANIZATION_DATABASE;
SELECT
    s.ID,
    s.InvoiceNumber,
    s.EzeeIssued,
    CASE
        WHEN s.InvoiceNote IS NOT NULL AND s.InvoiceNote != '' THEN 'HAS_NOTE'
        ELSE 'NO_NOTE'
    END as InvoiceNoteStatus,
    s.intuit_extracted_cufe,
    f.cufe as fe_cufe,
    f.Enviado as fe_enviado
FROM Sales_Header_Imp s
LEFT JOIN fe_header f ON s.InvoiceNumber = f.dNroDF
WHERE s.InvoiceNumber = '$INVOICE_NUMBER'
   OR (s.intuit_extracted_cufe IS NOT NULL AND s.intuit_extracted_cufe != '')
   OR f.cufe IS NOT NULL
ORDER BY s.InvoiceNumber;"

echo ""
echo "4. DIAGNÓSTICO:"
echo "Si la factura aparece con CUFE pero EzeeIssued != 1, necesita sincronización."
echo ""
echo "5. SOLUCIÓN PROPUESTA:"
echo "UPDATE Sales_Header_Imp"
echo "SET EzeeIssued = 1, InvoiceNote = CONCAT('{\"cufe\":\"', COALESCE(intuit_extracted_cufe, 'MANUAL_EMISSION'), '\"}') "
echo "WHERE InvoiceNumber = '$INVOICE_NUMBER' AND (intuit_extracted_cufe IS NOT NULL OR EXISTS(SELECT 1 FROM fe_header WHERE dNroDF = '$INVOICE_NUMBER'));"
