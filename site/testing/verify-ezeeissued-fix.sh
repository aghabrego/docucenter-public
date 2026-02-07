#!/bin/bash

# Script para verificar la corrección del problema de EzeeIssued
# Prueba la factura 5831 después de los cambios en el modelo y componente

ORGANIZATION_DATABASE="db_14034741628418_01"
INVOICE_NUMBER="5831"

echo "=== Verificación de Corrección EzeeIssued para Factura $INVOICE_NUMBER ==="
echo "Base de datos: $ORGANIZATION_DATABASE"
echo ""

echo "1. Estado actual en base de datos (raw data):"
docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "
USE $ORGANIZATION_DATABASE;
SELECT
    ID,
    InvoiceNumber,
    EzeeIssued + 0 as EzeeIssued_int,
    CASE
        WHEN InvoiceNote IS NOT NULL AND InvoiceNote != '' THEN 'HAS_NOTE'
        ELSE 'NO_NOTE'
    END as InvoiceNoteStatus,
    SUBSTRING(intuit_extracted_cufe, 1, 50) as cufe_preview,
    Date
FROM Sales_Header_Imp
WHERE InvoiceNumber = '$INVOICE_NUMBER';"

echo ""
echo "2. Verificación del InvoiceNote JSON:"
docker exec -it docucenter-mariadb-1 mysql -u weirdolabs -psecret -e "
USE $ORGANIZATION_DATABASE;
SELECT
    InvoiceNumber,
    JSON_VALID(InvoiceNote) as is_valid_json,
    JSON_EXTRACT(InvoiceNote, '$.cufe') as extracted_cufe
FROM Sales_Header_Imp
WHERE InvoiceNumber = '$INVOICE_NUMBER';"

echo ""
echo "3. Estado esperado después de la corrección:"
echo "   - EzeeIssued debería ser 1 (TRUE)"
echo "   - InvoiceNote debería contener JSON válido con CUFE"
echo "   - La vista debería mostrar botones de factura emitida"
echo ""
echo "4. Acciones disponibles según estado:"
echo "   Si EzeeIssued = 1:"
echo "   - ✅ Botón Ver (solo lectura) para facturas emitidas"
echo "   - ✅ Botón Exportar (PDF/XML) si tiene InvoiceNote"
echo "   - ❌ Botón Emitir (deshabilitado)"
echo "   - ❌ Botón Eliminar (deshabilitado)"
echo "   - ❌ Checkbox de selección (deshabilitado)"
echo ""
echo "   Si EzeeIssued = 0:"
echo "   - ✅ Botón Ver (con opción de emitir)"
echo "   - ✅ Botón Emitir"
echo "   - ✅ Botón Eliminar"
echo "   - ✅ Checkbox de selección"
echo ""
echo "5. Para aplicar los cambios:"
echo "   a) Reiniciar el servidor web/clear cache de Livewire"
echo "   b) Refrescar la página de facturas"
echo "   c) Verificar que los botones cambien según el estado real"
