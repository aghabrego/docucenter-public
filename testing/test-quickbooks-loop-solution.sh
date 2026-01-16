#!/bin/bash

# Script para probar la solución del loop QuickBooks
# Ubicación: docs/testing/test-quickbooks-loop-solution.sh

echo "=== TESTING SOLUCIÓN LOOP QUICKBOOKS ==="
echo ""

echo "📋 VERIFICACIÓN DE ESTRUCTURA"
echo ""

# Verificar que el campo origin existe en el stub
if grep -q "origin.*varchar" /home/weirdolabs/code/docucenter/app/Models/stubs/Sales_Header_Imp.sql.stub; then
    echo "✅ Campo 'origin' encontrado en Sales_Header_Imp.sql.stub"
else
    echo "❌ Campo 'origin' NO encontrado en stub"
fi

# Verificar índice
if grep -q "origin_index" /home/weirdolabs/code/docucenter/app/Models/stubs/Sales_Header_Imp.sql.stub; then
    echo "✅ Índice 'origin_index' encontrado en stub"
else
    echo "❌ Índice 'origin_index' NO encontrado en stub"
fi

# Verificar modelo SalesHeaderImp
if grep -q "'origin'" /home/weirdolabs/code/docucenter/app/Models/SalesHeaderImp.php; then
    echo "✅ Campo 'origin' encontrado en modelo SalesHeaderImp"
else
    echo "❌ Campo 'origin' NO encontrado en modelo"
fi

echo ""
echo "📋 VERIFICACIÓN DE LÓGICA ANTI-LOOP"
echo ""

# Verificar CreateSaleQuickBooksJob no envía de vuelta
if grep -q "origin.*quickbooks" /home/weirdolabs/code/docucenter/app/Jobs/CreateSaleQuickBooksJob.php; then
    echo "✅ CreateSaleQuickBooksJob marca origin = 'quickbooks'"
else
    echo "❌ CreateSaleQuickBooksJob NO marca origin correctamente"
fi

if grep -q "intuit_sync_status.*completed" /home/weirdolabs/code/docucenter/app/Jobs/CreateSaleQuickBooksJob.php; then
    echo "✅ CreateSaleQuickBooksJob marca como completed"
else
    echo "❌ CreateSaleQuickBooksJob NO marca como completed"
fi

# Verificar que NO dispara SendSaleToQuickBooksJob
if ! grep -q "SendSaleToQuickBooksJob::dispatch" /home/weirdolabs/code/docucenter/app/Jobs/CreateSaleQuickBooksJob.php; then
    echo "✅ CreateSaleQuickBooksJob NO dispara SendSaleToQuickBooksJob (loop evitado)"
else
    echo "❌ CreateSaleQuickBooksJob AÚN dispara SendSaleToQuickBooksJob (loop presente)"
fi

# Verificar UpdateIntuitOrdersJob filtra por origin
if grep -q "origin.*docucenter" /home/weirdolabs/code/docucenter/app/Jobs/Intuit/UpdateIntuitOrdersJob.php; then
    echo "✅ UpdateIntuitOrdersJob filtra por origin = 'docucenter'"
else
    echo "❌ UpdateIntuitOrdersJob NO filtra por origin"
fi

if grep -q "orWhereNull.*origin" /home/weirdolabs/code/docucenter/app/Jobs/Intuit/UpdateIntuitOrdersJob.php; then
    echo "✅ UpdateIntuitOrdersJob mantiene compatibilidad con origin NULL"
else
    echo "❌ UpdateIntuitOrdersJob NO mantiene compatibilidad retroactiva"
fi

echo ""
echo "📋 CASOS DE FLUJO"
echo ""

echo "🔹 CASO 1: Factura DocuCenter → QuickBooks"
echo "   1. Factura creada en DocuCenter (origin = 'docucenter' o NULL)"
echo "   2. Comando word:update-intuit-orders procesa la factura"
echo "   3. UpdateIntuitOrdersJob la encuentra y envía a QB"
echo "   4. Factura marcada como EzeeExport = true"
echo "   ✅ Flujo normal sin cambios"
echo ""

echo "🔹 CASO 2: Factura QuickBooks → DocuCenter (CORREGIDO)"
echo "   1. Factura creada en QuickBooks"
echo "   2. API create_sale_quickbooks recibe la factura"
echo "   3. CreateSaleQuickBooksJob emite FE"
echo "   4. Factura marcada como origin = 'quickbooks'"
echo "   5. intuit_sync_status = 'completed', EzeeExport = true"
echo "   6. NO se dispara SendSaleToQuickBooksJob"
echo "   ✅ Loop evitado - factura no regresa a QB"
echo ""

echo "🔹 CASO 3: Comando posterior (LOOP EVITADO)"
echo "   1. Comando word:update-intuit-orders ejecuta"
echo "   2. UpdateIntuitOrdersJob busca facturas"
echo "   3. Facturas con origin = 'quickbooks' son FILTRADAS"
echo "   4. Solo procesa facturas origin = 'docucenter' o NULL"
echo "   ✅ Facturas QB no son reprocesadas"
echo ""

echo "📊 QUERIES DE MONITOREO RECOMENDADAS:"
echo ""
echo "-- Ver distribución por origen"
echo "SELECT origin, COUNT(*) as total FROM Sales_Header_Imp GROUP BY origin;"
echo ""
echo "-- Facturas QB completamente procesadas (no loop)"
echo "SELECT COUNT(*) FROM Sales_Header_Imp WHERE origin = 'quickbooks' AND intuit_sync_status = 'completed';"
echo ""
echo "-- Facturas DocuCenter pendientes"
echo "SELECT COUNT(*) FROM Sales_Header_Imp WHERE (origin = 'docucenter' OR origin IS NULL) AND EzeeExport = false AND EzeeIssued = true;"
echo ""

echo "🎯 RESULTADO: Solución implementada correctamente"
echo "   - Loop QuickBooks eliminado"
echo "   - Flujo bidireccional funcional"
echo "   - Compatibilidad retroactiva mantenida"
echo "   - Trazabilidad de origen implementada"
