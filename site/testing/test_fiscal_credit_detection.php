<?php

// Script independiente para probar la detección de facturas de crédito fiscal
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AlanubeDomService;

echo "🧪 Probando detección de facturas de crédito fiscal...\n\n";

// Crear instancia del servicio
$service = new AlanubeDomService();

// Prueba 1: Factura de consumo (consumidor final)
echo "1. Probando factura de consumo:\n";
$consumerData = [
    'customer' => [
        'id' => 'Consumidor Final',
        'name' => 'Consumidor Final'
    ],
    'total' => 15000
];

$type = $service->determineDocumentType($consumerData);
echo "   Tipo detectado: $type (Esperado: " . AlanubeDomService::INVOICE . ")\n\n";

// Prueba 2: Factura gubernamental (RNC 10...)
echo "2. Probando factura gubernamental:\n";
$governmentalData = [
    'customer' => [
        'id' => '10123456789',
        'name' => 'Ministerio de Educación',
        'tax_id' => '10123456789'
    ],
    'total' => 50000
];

$type = $service->determineDocumentType($governmentalData);
echo "   Tipo detectado: $type (Esperado: " . AlanubeDomService::GOVERNMENTAL . ")\n\n";

// Prueba 3: Factura de crédito fiscal (RNC empresarial + campos complejos)
echo "3. Probando factura de crédito fiscal:\n";
$fiscalCreditData = [
    'customer' => [
        'id' => '40123456789',
        'name' => 'Empresa Comercial SRL',
        'tax_id' => '40123456789'
    ],
    'paymentFormsTable' => [
        ['paymentMethod' => 4, 'paymentAmount' => 10000],
        ['paymentMethod' => 1, 'paymentAmount' => 5000]
    ],
    'retention' => ['typeOfRetention' => '01', 'retentionAmount' => 500],
    'additionalTaxes' => [
        ['typeOfTax' => 'A34', 'taxableAmount' => 10000, 'taxAmount' => 1000]
    ],
    'total' => 100000
];

$type = $service->determineDocumentType($fiscalCreditData);
echo "   Tipo detectado: $type (Esperado: " . AlanubeDomService::FISCAL_INVOICE . ")\n\n";

// Prueba 4: Empresa simple (RNC empresarial sin campos complejos)
echo "4. Probando empresa simple:\n";
$simpleBusinessData = [
    'customer' => [
        'id' => '40987654321',
        'name' => 'Empresa Simple SRL',
        'tax_id' => '40987654321'
    ],
    'total' => 25000
];

$type = $service->determineDocumentType($simpleBusinessData);
echo "   Tipo detectado: $type (Esperado: " . AlanubeDomService::FISCAL_INVOICE . ")\n\n";

// Prueba específica de métodos de detección
echo "5. Probando métodos específicos de detección:\n";

// Test usando solo el método público determineDocumentType
$complexData = [
    'paymentFormsTable' => [
        ['paymentMethod' => 4, 'paymentAmount' => 10000],
        ['paymentMethod' => 1, 'paymentAmount' => 5000]
    ],
    'retention' => ['typeOfRetention' => '01', 'retentionAmount' => 500],
    'perception' => ['typeOfPerception' => '05', 'perceptionAmount' => 300],
    'additionalTaxes' => [
        ['typeOfTax' => 'A34', 'taxableAmount' => 10000, 'taxAmount' => 1000]
    ],
    'otherCurrency' => ['currencyCode' => 'USD', 'exchangeRate' => 58.50],
    'customer' => ['tax_id' => '40123456789']
];

$type = $service->determineDocumentType($complexData);
echo "   Datos complejos - Tipo detectado: $type (Esperado: " . AlanubeDomService::FISCAL_INVOICE . ")\n";

// Test con RNC empresarial pero sin indicadores complejos
$simpleEnterprise = [
    'customer' => ['tax_id' => '40123456789'],
    'total' => 1000
];

$type = $service->determineDocumentType($simpleEnterprise);
echo "   RNC empresarial simple - Tipo detectado: $type (Esperado: " . AlanubeDomService::FISCAL_INVOICE . ")\n";

// Test con RNC gubernamental
$governmentalRnc = [
    'customer' => ['tax_id' => '10123456789'],
    'total' => 1000
];

$type = $service->determineDocumentType($governmentalRnc);
echo "   RNC gubernamental - Tipo detectado: $type (Esperado: " . AlanubeDomService::GOVERNMENTAL . ")\n";

echo "\n✅ Pruebas de detección completadas.\n";
