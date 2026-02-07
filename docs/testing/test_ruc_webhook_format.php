#!/usr/bin/env php
<?php
/**
 * TEST: Detección de formato RUC webhook "RUC:..." para QuickBooks
 *
 * PROPÓSITO:
 * - Verificar que las expresiones regulares actuales detectan el formato webhook "RUC:..."
 * - Validar conversión de TIPO_RECEPTOR de 1 dígito a 2 dígitos
 * - Verificar extracción correcta de RUC, DV, TIPO y TIPO_RECEPTOR
 *
 * CASOS DE PRUEBA:
 * - RUC:E-8-77740 DV:00 TIPO:01 TIPO_RECEPTOR:1 (extranjero)
 * - RUC:8-777-4063 DV:14 TIPO:01 TIPO_RECEPTOR:01 (nacional)
 * - RUC:9-734-1672 DV:56 TIPO:01 (sin TIPO_RECEPTOR, debe inferir)
 * - RUC:N-1-12345 DV:89 TIPO:01 TIPO_RECEPTOR:4 (naturalizado)
 * - RUC:1825706-1-709732 DV:90 TIPO:02 TIPO_RECEPTOR:01 (persona jurídica)
 * - XXXX9-734-1672 DV:56 (formato antiguo, sin conversión)
 */

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Convertir formato webhook "RUC:" al formato soportado "XXXX"
 * Entrada: "RUC:E-8-77740 DV:00 TIPO:01 TIPO_RECEPTOR:1"
 * Salida: "XXXXE-8-77740 DV:00 TIPO:01 TIPO_RECEPTOR:01"
 */
function convertWebhookFormatToSupported(string $webhookFormat): string
{
    if (empty($webhookFormat) || !preg_match('/^RUC:/i', $webhookFormat)) {
        return $webhookFormat; // No necesita conversión
    }

    // Extraer partes del formato webhook
    if (!preg_match('/^RUC:([EN]-\d+-\d+|\d+-\d+-\d+|[\d]+-\d+-\d+)\s+DV:(\d{1,2})(?:\s+TIPO:(\d{1,2}))?(?:\s+TIPO_RECEPTOR:(\d{1,2}))?/i', $webhookFormat, $match)) {
        echo "  [WARN] No se pudo parsear el formato webhook: {$webhookFormat}\n";
        return $webhookFormat;
    }

    $ruc = $match[1];
    $dv = $match[2];
    $tipo = $match[3] ?? null;
    $tipoReceptor = $match[4] ?? null;

    // Determinar si es persona natural o jurídica basándose en el RUC
    // Persona Natural: X-XXX-XXXX (1 dígito provincia) o letra-número-número
    // Persona Jurídica: XXXXXXX-X-XXXXXX (7+ dígitos)
    $isNatural = preg_match('/^[EN0-9]-\d+-\d+$/i', $ruc);

    $converted = "";

    if ($isNatural) {
        // Formato XXXX para persona natural (directo, sin dos puntos)
        $converted = "XXXX{$ruc} DV:{$dv}";
    } else {
        // Formato XXXX: para persona jurídica (con dos puntos)
        $converted = "XXXX:{$ruc} DV{$dv}";
    }

    // Agregar TIPO si existe
    if ($tipo) {
        $tipoPadded = str_pad($tipo, 2, '0', STR_PAD_LEFT);
        $converted .= " TIPO:{$tipoPadded}";
    }

    // Agregar TIPO_RECEPTOR si existe, normalizando a 2 dígitos
    if ($tipoReceptor) {
        $tipoReceptorPadded = str_pad($tipoReceptor, 2, '0', STR_PAD_LEFT);
        $converted .= " TIPO_RECEPTOR:{$tipoReceptorPadded}";
    }

    echo "  [CONVERSION] \"{$webhookFormat}\" → \"{$converted}\"\n";
    return $converted;
}

/**
 * Simular extracción de RUC/DV desde PrimaryTaxIdentifier
 */
function extractRUCFromPrimaryTaxIdentifier(string $primaryTaxIdentifier): array
{
    $result = [
        'RUC' => null,
        'DV' => null,
        'TIPO' => null,
        'TIPO_RECEPTOR' => null,
        'TIPO_RECEPTOR_DESC' => null,
        'Source' => null
    ];

    if (empty($primaryTaxIdentifier)) {
        return $result;
    }

    // Detectar formato persona natural (XXXX directo, sin dos puntos)
    if (preg_match('/^XXXX([EN0-9]-\d+-\d+)\s+DV:(\d{1,2})(?:\s+TIPO:(\d{2}))?(?:\s+TIPO_RECEPTOR:(\d{2}))?/i', $primaryTaxIdentifier, $match)) {
        $result['RUC'] = $match[1];
        $result['DV'] = $match[2];
        $result['TIPO'] = isset($match[3]) ? (int)$match[3] : 1;
        $result['TIPO_RECEPTOR'] = $match[4] ?? '02'; // Default: Consumidor Final
        $result['Source'] = 'PrimaryTaxIdentifier_Natural';

        echo "extractRUCFromPrimaryTaxIdentifier: Detected Natural Person (XXXX direct pattern)\n";
    }
    // Detectar formato persona jurídica (XXXX: con dos puntos)
    elseif (preg_match('/^XXXX:([\d]+-\d+-\d+)\s+DV(\d{1,2})(?:\s+TIPO:(\d{2}))?(?:\s+TIPO_RECEPTOR:(\d{2}))?/i', $primaryTaxIdentifier, $match)) {
        $result['RUC'] = $match[1];
        $result['DV'] = $match[2];
        $result['TIPO'] = isset($match[3]) ? (int)$match[3] : 2;
        $result['TIPO_RECEPTOR'] = $match[4] ?? '01'; // Default: Contribuyente
        $result['Source'] = 'PrimaryTaxIdentifier_Juridical';

        echo "extractRUCFromPrimaryTaxIdentifier: Detected Juridical Person (XXXX: pattern)\n";
    }
    // Formato legacy o directo
    elseif (preg_match('/Ruc:(\d+-\d+-\d+)|RUC:(\d+-\d+-\d+)|RUC (\d+-\d+-\d+)|Ruc (\d+-\d+-\d+)|^(\d+-\d+-\d+)$/i', $primaryTaxIdentifier, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $result['RUC'] = end($filteredMatches);
        $result['Source'] = 'Legacy_Format';
    }

    // Extraer DV si no se capturó aún
    if (is_null($result['DV']) && preg_match('/Dv:(\d{2})|DV:(\d{2})|Dv (\d{2})|DV (\d{2})|^\d{2}/i', $primaryTaxIdentifier, $matches)) {
        $filteredMatches = array_filter($matches, fn($value) => !empty($value));
        $result['DV'] = end($filteredMatches);
    }

    // Extraer TIPO si no se capturó aún
    if (is_null($result['TIPO']) && preg_match('/TIPO:(\d{2})/i', $primaryTaxIdentifier, $matches)) {
        $result['TIPO'] = (int)$matches[1];
    }

    // Extraer TIPO_RECEPTOR si no se capturó aún
    if (is_null($result['TIPO_RECEPTOR']) && preg_match('/TIPO_RECEPTOR:(\d{2})/i', $primaryTaxIdentifier, $matches)) {
        $result['TIPO_RECEPTOR'] = $matches[1];
    }

    // Normalizar TIPO_RECEPTOR y agregar descripción
    if (!is_null($result['TIPO_RECEPTOR'])) {
        $tipoReceptorDescriptions = [
            '01' => 'Contribuyente',
            '02' => 'Consumidor Final',
            '03' => 'Gobierno',
            '04' => 'Extranjero'
        ];
        $result['TIPO_RECEPTOR_DESC'] = $tipoReceptorDescriptions[$result['TIPO_RECEPTOR']] ?? 'Desconocido';
    }

    return $result;
}

// ===========================
// CASOS DE PRUEBA
// ===========================

$testCases = [
    [
        'name' => 'Caso 1: Webhook - RUC extranjero E-8-77740 con TIPO_RECEPTOR de 1 dígito',
        'webhookFormat' => 'RUC:E-8-77740 DV:00 TIPO:01 TIPO_RECEPTOR:1',
        'expected' => [
            'RUC' => 'E-8-77740',
            'DV' => '00',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '01',
            'TIPO_RECEPTOR_DESC' => 'Contribuyente'
        ]
    ],
    [
        'name' => 'Caso 2: Webhook - RUC nacional 8-777-4063 con TIPO_RECEPTOR de 2 dígitos',
        'webhookFormat' => 'RUC:8-777-4063 DV:14 TIPO:01 TIPO_RECEPTOR:01',
        'expected' => [
            'RUC' => '8-777-4063',
            'DV' => '14',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '01'
        ]
    ],
    [
        'name' => 'Caso 3: Webhook - RUC nacional sin TIPO_RECEPTOR (debe inferir)',
        'webhookFormat' => 'RUC:9-734-1672 DV:56 TIPO:01',
        'expected' => [
            'RUC' => '9-734-1672',
            'DV' => '56',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '02' // Debe inferir Consumidor Final
        ]
    ],
    [
        'name' => 'Caso 4: Webhook - RUC extranjero N-1-12345 (Naturalizado)',
        'webhookFormat' => 'RUC:N-1-12345 DV:89 TIPO:01 TIPO_RECEPTOR:4',
        'expected' => [
            'RUC' => 'N-1-12345',
            'DV' => '89',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '04' // Debe normalizar "4" a "04"
        ]
    ],
    [
        'name' => 'Caso 5: Webhook - RUC persona jurídica formato largo',
        'webhookFormat' => 'RUC:1825706-1-709732 DV:90 TIPO:02 TIPO_RECEPTOR:01',
        'expected' => [
            'RUC' => '1825706-1-709732',
            'DV' => '90',
            'TIPO' => 2,
            'TIPO_RECEPTOR' => '01'
        ]
    ],
    [
        'name' => 'Caso 6: Webhook - RUC con TIPO_RECEPTOR:2 (Consumidor Final)',
        'webhookFormat' => 'RUC:8-777-4063 DV:14 TIPO:01 TIPO_RECEPTOR:2',
        'expected' => [
            'RUC' => '8-777-4063',
            'DV' => '14',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '02'
        ]
    ],
    [
        'name' => 'Caso 7: Webhook - RUC con TIPO_RECEPTOR:3 (Gobierno)',
        'webhookFormat' => 'RUC:2-123-456 DV:25 TIPO:01 TIPO_RECEPTOR:3',
        'expected' => [
            'RUC' => '2-123-456',
            'DV' => '25',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '03'
        ]
    ],
    [
        'name' => 'Caso 8: Formato antiguo XXXX (ya soportado, sin conversión)',
        'webhookFormat' => 'XXXX9-734-1672 DV:56',
        'expected' => [
            'RUC' => '9-734-1672',
            'DV' => '56',
            'TIPO' => 1,
            'TIPO_RECEPTOR' => '02'
        ]
    ]
];

echo "=====================================================\n";
echo "TEST: extractRUC - Conversión Formato Webhook a Soportado (PHP)\n";
echo "=====================================================\n\n";

$totalTests = count($testCases);
$passedTests = 0;

foreach ($testCases as $index => $testCase) {
    echo "\n--- Test " . ($index + 1) . ": {$testCase['name']} ---\n";
    echo "Input Webhook Format: {$testCase['webhookFormat']}\n";

    // Convertir formato webhook al formato soportado
    $convertedFormat = convertWebhookFormatToSupported($testCase['webhookFormat']);

    if (isset($testCase['expected'])) {
        echo "\nValor Esperado:\n";
        echo "  RUC: {$testCase['expected']['RUC']}\n";
        echo "  DV: {$testCase['expected']['DV']}\n";
        echo "  TIPO: {$testCase['expected']['TIPO']}\n";
        echo "  TIPO_RECEPTOR: {$testCase['expected']['TIPO_RECEPTOR']}\n";
    }

    try {
        $result = extractRUCFromPrimaryTaxIdentifier($convertedFormat);

        echo "\nResultado Obtenido:\n";
        echo "  RUC: " . ($result['RUC'] ?? '(null)') . "\n";
        echo "  DV: " . ($result['DV'] ?? '(null)') . "\n";
        echo "  TIPO: " . ($result['TIPO'] ?? '(null)') . "\n";
        echo "  TIPO_RECEPTOR: " . ($result['TIPO_RECEPTOR'] ?? '(null)') . "\n";
        echo "  TIPO_RECEPTOR_DESC: " . ($result['TIPO_RECEPTOR_DESC'] ?? '(null)') . "\n";
        echo "  Source: " . ($result['Source'] ?? '(null)') . "\n";

        // Validar contra valores esperados
        if (isset($testCase['expected'])) {
            $allMatch = true;
            $mismatches = [];

            foreach ($testCase['expected'] as $key => $expectedValue) {
                if (isset($result[$key])) {
                    // Comparar como string para evitar problemas de tipo
                    if ((string)$result[$key] !== (string)$expectedValue) {
                        $allMatch = false;
                        $mismatches[] = "{$key}: esperado \"{$expectedValue}\", obtenido \"{$result[$key]}\"";
                    }
                } else {
                    $allMatch = false;
                    $mismatches[] = "{$key}: esperado \"{$expectedValue}\", no encontrado en resultado";
                }
            }

            if ($allMatch) {
                echo "\n✓ TEST PASADO - Todos los valores coinciden\n";
                $passedTests++;
            } else {
                echo "\n✗ TEST FALLIDO - Valores no coinciden:\n";
                foreach ($mismatches as $msg) {
                    echo "  - {$msg}\n";
                }
            }
        }

    } catch (Exception $e) {
        echo "\n✗ Error en el test: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }

    echo "\n" . str_repeat("-", 80) . "\n";
}

echo "\n=====================================================\n";
echo "RESUMEN: {$passedTests}/{$totalTests} tests pasaron\n";
echo "=====================================================\n";

if ($passedTests === $totalTests) {
    echo "🎉 ¡Todos los tests pasaron!\n";
    exit(0);
} else {
    echo "⚠️ Algunos tests fallaron. Revisar expresiones regulares.\n";
    exit(1);
}
