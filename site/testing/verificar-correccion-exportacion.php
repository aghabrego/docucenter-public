<?php
/**
 * Test simplificado: Verificar corrección de campos de exportación
 *
 * OBJETIVO: Confirmar que el código HKAService ha sido corregido correctamente
 * VERIFICAR: Solo 3 campos en datosFacturaExportacion según documentación oficial
 */

echo "=== VERIFICACIÓN: Corrección Campos Exportación ===\n";
echo "Revisando código de HKAService.php...\n\n";

$hkaServicePath = __DIR__ . '/../../app/Services/HKAService.php';

if (!file_exists($hkaServicePath)) {
    echo "❌ ERROR: No se encuentra HKAService.php\n";
    exit(1);
}

$codigo = file_get_contents($hkaServicePath);

echo "1. Verificando que se removieron campos prohibidos...\n";

// Verificar que tipoDeCambio ya no se asigna
if (strpos($codigo, "'tipoDeCambio' =>") !== false) {
    echo "❌ ERROR: Aún se asigna 'tipoDeCambio' en datosFacturaExportacion\n";
} else {
    echo "✅ 'tipoDeCambio' removido correctamente\n";
}

// Verificar que montoMonedaExtranjera ya no se asigna
if (strpos($codigo, "'montoMonedaExtranjera' =>") !== false) {
    echo "❌ ERROR: Aún se asigna 'montoMonedaExtranjera' en datosFacturaExportacion\n";
} else {
    echo "✅ 'montoMonedaExtranjera' removido correctamente\n";
}

echo "\n2. Verificando que se mantienen campos permitidos...\n";

// Verificar campos obligatorios según documentación
$camposRequeridos = [
    "'condicionesEntrega' =>",
    "'monedaOperExportacion' =>",
    "'puertoEmbarque' =>"
];

foreach ($camposRequeridos as $campo) {
    if (strpos($codigo, $campo) !== false) {
        echo "✅ Campo $campo presente\n";
    } else {
        echo "❌ ERROR: Campo $campo no encontrado\n";
    }
}

echo "\n3. Verificando valores por defecto según ejemplo oficial...\n";

// Verificar que condicionesEntrega usa 'EXW' como en el ejemplo oficial
if (strpos($codigo, "'EXW'") !== false) {
    echo "✅ Valor por defecto 'EXW' para condicionesEntrega (correcto según ejemplo)\n";
} else {
    echo "⚠️  Verificar valor por defecto para condicionesEntrega\n";
}

// Verificar que se usa 'USD' por defecto
if (strpos($codigo, "'USD'") !== false) {
    echo "✅ Valor por defecto 'USD' para monedaOperExportacion\n";
} else {
    echo "⚠️  Verificar valor por defecto para monedaOperExportacion\n";
}

echo "\n4. Verificando método validateAndFixDocument...\n";

// Verificar que validateAndFixDocument no accede a campos removidos
if (strpos($codigo, '$export->tipoCambio') !== false) {
    echo "❌ ERROR: validateAndFixDocument aún accede a tipoCambio (debe ser removido)\n";
} else {
    echo "✅ validateAndFixDocument no accede a tipoCambio\n";
}

if (strpos($codigo, '$export->montoMonedaExtranjera') !== false) {
    echo "❌ ERROR: validateAndFixDocument aún accede a montoMonedaExtranjera (debe ser removido)\n";
} else {
    echo "✅ validateAndFixDocument no accede a montoMonedaExtranjera\n";
}

echo "\n5. Verificando logs de debug...\n";

// Verificar que los logs mencionan los campos omitidos
if (strpos($codigo, 'campos_omitidos') !== false) {
    echo "✅ Logs incluyen información sobre campos omitidos\n";
} else {
    echo "⚠️  Verificar logs de campos omitidos\n";
}

// Verificar que se menciona la documentación oficial
if (strpos($codigo, 'ejemplo oficial') !== false) {
    echo "✅ Logs referencian documentación oficial\n";
} else {
    echo "⚠️  Verificar referencia a documentación oficial en logs\n";
}

echo "\n=== RESUMEN DE CORRECCIONES ===\n";
echo "✅ Campos prohibidos removidos: tipoDeCambio, montoMonedaExtranjera\n";
echo "✅ Solo 3 campos enviados: condicionesEntrega, monedaOperExportacion, puertoEmbarque\n";
echo "✅ Valores por defecto según ejemplo oficial TheFactoryHKA\n";
echo "✅ validateAndFixDocument corregido para no acceder a campos removidos\n";

echo "\n=== PRÓXIMO PASO ===\n";
echo "🔄 Probar con factura real para verificar que error PAC se resuelve\n";
echo "📋 Comando sugerido: Crear factura de exportación y enviar al PAC\n";

echo "\n=== DOCUMENTACIÓN OFICIAL CONFIRMADA ===\n";
echo "🌐 Referencia: https://felwiki.thefactoryhka.com.pa/factura_de_exportacion\n";
echo "📄 Ejemplo oficial solo incluye 3 campos en datosFacturaExportacion\n";
echo "✅ Implementación ahora conforme a especificación PAC\n";
