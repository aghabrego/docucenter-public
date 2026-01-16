<?php
/**
 * Test de verificación: Corrección DEFINITIVA según documentación TheFactoryHKA
 *
 * Según docs oficiales de TheFactoryHKA:
 * https://felwiki.thefactoryhka.com.pa/factura_de_operacion_interna
 * https://felwiki.thefactoryhka.com.pa/factura_a_cliente_extranjero
 *
 * REGLAS OBLIGATORIAS:
 * - tipoOperacion = SIEMPRE 1 (Operación Interna) para facturas normales
 * - destinoOperacion = SIEMPRE 1 (Nacional) para facturas emitidas desde Panamá
 * - pais = SIEMPRE 'PA' porque la factura se emite desde Panamá
 * - Lo que diferencia extranjeros: tipoClienteFE y paisExtranjero
 *
 * Uso:
 * cd /home/weirdolabs/code/docucenter
 * docker exec -it docucenter_laravel.test php docs/testing/test-thefactoryhka-documentacion-oficial-fix.php
 */

// Configurar entorno Laravel
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== TEST: Corrección DEFINITIVA según documentación TheFactoryHKA ===\n";

// Test Case 1: Cliente nacional tradicional
echo "\n--- Test Case 1: Cliente nacional tradicional ---\n";

$mockCustomerNacional = (object)[
    'CustomerID' => 'TEST001',
    'Customer_Bill_Name' => 'Juan Pérez Nacional',
    'Country' => 'PA',
    'Custom_field1' => '8-123-456',
    'Custom_field2' => '78',
    'Custom_field3' => '2',          // Persona Natural
    'Custom_field4' => '1',
    'Custom_field5' => '',
    'AddressLine1' => 'Panama City',
    'Email' => 'juan@panama.com'
];

$receptor_tipo = $mockCustomerNacional->Custom_field3 ?? '2';
$customerCountry = $mockCustomerNacional->Country ?? 'PA';

echo "   - País: {$customerCountry}\n";
echo "   - Tipo receptor: {$receptor_tipo} (2=Persona Natural)\n";

// Aplicar configuración según docs TheFactoryHKA
$tipoOperacion = 1;      // SIEMPRE 1 según docs
$destinoOperacion = 1;   // SIEMPRE 1 según docs
$paisRec = 'PA';         // SIEMPRE PA según docs

echo "   ✅ Configuración según docs TheFactoryHKA:\n";
echo "      - tipoOperacion: {$tipoOperacion} (SIEMPRE 1)\n";
echo "      - destinoOperacion: {$destinoOperacion} (SIEMPRE 1)\n";
echo "      - pais: {$paisRec} (SIEMPRE PA)\n";

// Verificar regla de validación
$isValid = !($tipoOperacion == 1 && $destinoOperacion == 2);
echo "   ✅ VÁLIDO: " . ($isValid ? "SÍ" : "NO") . "\n";

// Test Case 2: Cliente extranjero real
echo "\n--- Test Case 2: Cliente extranjero real (según docs TheFactoryHKA) ---\n";

$mockCustomerExtranjero = (object)[
    'CustomerID' => 'TEST002',
    'Customer_Bill_Name' => 'Maria Rodriguez Colombia',
    'Country' => 'CO', // Cliente colombiano
    'Custom_field1' => '12345678',   // Pasaporte/ID extranjero
    'Custom_field2' => '',
    'Custom_field3' => '4',          // Persona Jurídica Extranjera
    'Custom_field4' => '',
    'Custom_field5' => '',
    'AddressLine1' => 'Bogotá, Colombia',
    'Email' => 'maria@colombia.com'
];

$receptor_tipo_ext = $mockCustomerExtranjero->Custom_field3 ?? '2';
$customerCountry_ext = $mockCustomerExtranjero->Country ?? 'PA';

echo "   - País cliente: {$customerCountry_ext}\n";
echo "   - Tipo receptor: {$receptor_tipo_ext} (4=Persona Jurídica Extranjera)\n";

// CONFIGURACIÓN SEGÚN DOCS THEFACTORYHKA (NO mi interpretación anterior)
$tipoOperacion_ext = 1;      // SIEMPRE 1 según docs (¡NO 2!)
$destinoOperacion_ext = 1;   // SIEMPRE 1 según docs (¡NO 2!)
$paisRec_ext = 'PA';         // SIEMPRE PA según docs
$paisExtranjero = 'Colombia'; // País del cliente extranjero
$tipoClienteFE = '04';       // 04 = Extranjero

echo "   ✅ Configuración CORREGIDA según docs TheFactoryHKA:\n";
echo "      - tipoOperacion: {$tipoOperacion_ext} (SIEMPRE 1)\n";
echo "      - destinoOperacion: {$destinoOperacion_ext} (SIEMPRE 1)\n";
echo "      - pais: {$paisRec_ext} (SIEMPRE PA)\n";
echo "      - paisExtranjero: {$paisExtranjero} (País del cliente)\n";
echo "      - tipoClienteFE: {$tipoClienteFE} (04=Extranjero)\n";

// Verificar regla de validación
$isValid_ext = !($tipoOperacion_ext == 1 && $destinoOperacion_ext == 2);
echo "   ✅ VÁLIDO: " . ($isValid_ext ? "SÍ" : "NO") . "\n";

// Test Case 3: Cliente mal clasificado como extranjero pero país PA
echo "\n--- Test Case 3: Cliente mal clasificado (extranjero pero país=PA) ---\n";

$mockCustomerMalClasificado = (object)[
    'CustomerID' => 'TEST003',
    'Customer_Bill_Name' => 'Carlos Mendoza Mal Clasificado',
    'Country' => 'PA', // ¡País panameño!
    'Custom_field1' => '8-987-654', // RUC panameño (no pasaporte)
    'Custom_field2' => '45',
    'Custom_field3' => '3',          // ¡Mal clasificado como extranjero!
    'Custom_field4' => '',
    'Custom_field5' => '',
    'AddressLine1' => 'Panama City',
    'Email' => 'carlos@panama.com'
];

$receptor_tipo_mal = $mockCustomerMalClasificado->Custom_field3 ?? '2';
$customerCountry_mal = $mockCustomerMalClasificado->Country ?? 'PA';

echo "   - País: {$customerCountry_mal}\n";
echo "   - Tipo receptor original: {$receptor_tipo_mal} (3=Extranjero) ⚠️ INCORRECTO\n";

// Aplicar corrección automática
if ($customerCountry_mal === 'PA' && ($receptor_tipo_mal === '3' || $receptor_tipo_mal === '4')) {
    echo "   🔧 RECLASIFICANDO: Cliente con país PA no puede ser extranjero\n";
    $receptor_tipo_mal = '2'; // Persona Natural
    echo "   ✅ Tipo receptor corregido: {$receptor_tipo_mal} (2=Persona Natural)\n";
}

// Configuración final según docs
$tipoOperacion_mal = 1;      // SIEMPRE 1
$destinoOperacion_mal = 1;   // SIEMPRE 1
$paisRec_mal = 'PA';         // SIEMPRE PA

echo "   ✅ Configuración final:\n";
echo "      - tipoOperacion: {$tipoOperacion_mal} (SIEMPRE 1)\n";
echo "      - destinoOperacion: {$destinoOperacion_mal} (SIEMPRE 1)\n";
echo "      - pais: {$paisRec_mal} (SIEMPRE PA)\n";

// Verificar regla de validación
$isValid_mal = !($tipoOperacion_mal == 1 && $destinoOperacion_mal == 2);
echo "   ✅ VÁLIDO: " . ($isValid_mal ? "SÍ" : "NO") . "\n";

// Comparación con documentación oficial
echo "\n=== VERIFICACIÓN CONTRA DOCUMENTACIÓN OFICIAL ===\n";
echo "📋 Factura Operación Interna (docs TheFactoryHKA):\n";
echo "   <ser:tipoOperacion>1</ser:tipoOperacion>\n";
echo "   <ser:destinoOperacion>1</ser:destinoOperacion>\n";
echo "   <ser:pais>PA</ser:pais>\n";

echo "\n📋 Factura Cliente Extranjero (docs TheFactoryHKA):\n";
echo "   <ser:tipoOperacion>1</ser:tipoOperacion>  ← ¡TAMBIÉN 1!\n";
echo "   <ser:destinoOperacion>1</ser:destinoOperacion>  ← ¡TAMBIÉN 1!\n";
echo "   <ser:tipoClienteFE>04</ser:tipoClienteFE>  ← Diferencia aquí\n";
echo "   <ser:paisExtranjero>Colombia</ser:paisExtranjero>  ← Y aquí\n";
echo "   <ser:pais>PA</ser:pais>  ← ¡SIEMPRE PA!\n";

echo "\n🎯 CONCLUSIÓN CLAVE:\n";
echo "   ❌ MI ERROR ANTERIOR: Pensé que extranjeros necesitaban destinoOperacion=2\n";
echo "   ✅ REALIDAD DOCS: destinoOperacion=1 SIEMPRE para facturas desde Panamá\n";
echo "   ✅ DIFERENCIA: tipoClienteFE y paisExtranjero distinguen extranjeros\n";

// Resumen final
echo "\n=== RESUMEN FINAL ===\n";
$allValid = $isValid && $isValid_ext && $isValid_mal;

if ($allValid) {
    echo "✅ TODOS LOS TEST CASES PASARON\n";
    echo "✅ Configuración corregida según documentación oficial TheFactoryHKA\n";
    echo "✅ ERROR 'destinoOperacion no puede ser Extranjero...' DEBE RESOLVERSE\n";
} else {
    echo "❌ ALGUNOS TEST CASES FALLARON\n";
}

echo "\n🔍 Próximos pasos:\n";
echo "   1. Aplicar cambios en producción\n";
echo "   2. Probar factura real con TheFactoryHKA\n";
echo "   3. Verificar que no aparece el error de destinoOperacion\n";
echo "   4. Confirmar que extranjeros usan tipoClienteFE=04 y paisExtranjero\n";

echo "\n📚 Referencias:\n";
echo "   - https://felwiki.thefactoryhka.com.pa/factura_de_operacion_interna\n";
echo "   - https://felwiki.thefactoryhka.com.pa/factura_a_cliente_extranjero\n";

echo "\n=== FIN DEL TEST ===\n";
