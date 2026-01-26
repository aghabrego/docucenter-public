#!/bin/bash

# Test: Validación Campo tipoContribuyente según Regla TheFactoryHKA
# Propósito: Verificar que extranjeros NO incluyan tipoContribuyente
# Referencia: https://felwiki.thefactoryhka.com.pa/

echo "🔍 TEST: Campo tipoContribuyente según Regla Oficial TheFactoryHKA"
echo "================================================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo -e "${BLUE}📋 Casos de Prueba:${NC}"
echo "1. ✅ Cliente Nacional + RUC → tipoContribuyente PRESENTE"
echo "2. ✅ Cliente Extranjero → tipoContribuyente AUSENTE (null)"
echo "3. ✅ Factura Exportación + Extranjero → tipoContribuyente AUSENTE"

echo ""
echo -e "${YELLOW}🧪 Ejecutando Pruebas...${NC}"

# Test 1: Cliente Nacional
echo ""
echo "=== TEST 1: Cliente Nacional (tipoClienteFE=02) ==="
cat > /tmp/test_nacional.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use App\Services\HKAService;
use App\Models\Organization;

// Simular cliente nacional con RUC
$docData = (object) [
    'dGen' => (object) [
        'gDatRec' => (object) [
            'iTipoRec' => '02', // Cliente nacional
            'gRucRec' => (object) [
                'dRuc' => '1234567890123',
                'dTipoRuc' => '1',
                'dDV' => '12'
            ],
            'dNombRec' => 'Cliente Nacional SA'
        ]
    ]
];

$organization = Organization::first();
$hkaService = new HKAService($organization);

// Usar reflection para acceder al método privado
$reflection = new ReflectionClass($hkaService);
$method = $reflection->getMethod('createFeXML');
$method->setAccessible(true);

$documento = $method->invoke($hkaService, $docData);
$cliente = $documento->datosTransaccion->cliente;

echo "tipoClienteFE: {$cliente->tipoClienteFE}\n";
echo "numeroRUC: {$cliente->numeroRUC}\n";
echo "tipoContribuyente: " . ($cliente->tipoContribuyente ?? 'NULL') . "\n";

if ($cliente->tipoClienteFE === '02' && !empty($cliente->tipoContribuyente)) {
    echo "✅ CORRECTO: Cliente nacional tiene tipoContribuyente\n";
    exit(0);
} else {
    echo "❌ ERROR: Cliente nacional debería tener tipoContribuyente\n";
    exit(1);
}
EOF

cd /home/weirdolabs/code/docucenter
php /tmp/test_nacional.php
nacional_result=$?

# Test 2: Cliente Extranjero
echo ""
echo "=== TEST 2: Cliente Extranjero (tipoClienteFE=04) ==="
cat > /tmp/test_extranjero.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use App\Services\HKAService;
use App\Models\Organization;

// Simular cliente extranjero SIN RUC
$docData = (object) [
    'dGen' => (object) [
        'gDatRec' => (object) [
            'iTipoRec' => '04', // Cliente extranjero
            'gRucRec' => (object) [
                'dRuc' => null, // Sin RUC
                'dDV' => null
            ],
            'gIdExt' => (object) [
                'tipoIdentificacion' => '01',
                'dIdExt' => 'US123456789',
                'dPaisExt' => 'Estados Unidos'
            ],
            'dNombRec' => 'Cliente Extranjero USA',
            'cPaisRec' => 'US'
        ]
    ]
];

$organization = Organization::first();
$hkaService = new HKAService($organization);

// Usar reflection para acceder al método privado
$reflection = new ReflectionClass($hkaService);
$method = $reflection->getMethod('createFeXML');
$method->setAccessible(true);

$documento = $method->invoke($hkaService, $docData);
$cliente = $documento->datosTransaccion->cliente;

echo "tipoClienteFE: {$cliente->tipoClienteFE}\n";
echo "numeroRUC: '{$cliente->numeroRUC}'\n";
echo "tipoContribuyente: " . ($cliente->tipoContribuyente ?? 'NULL') . "\n";

if ($cliente->tipoClienteFE === '04' && $cliente->tipoContribuyente === null) {
    echo "✅ CORRECTO: Cliente extranjero NO tiene tipoContribuyente\n";
    exit(0);
} else {
    echo "❌ ERROR: Cliente extranjero NO debería tener tipoContribuyente\n";
    exit(1);
}
EOF

php /tmp/test_extranjero.php
extranjero_result=$?

# Test 3: Factura Exportación
echo ""
echo "=== TEST 3: Factura Exportación (tipo 03 + extranjero) ==="
cat > /tmp/test_exportacion.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use App\Services\HKAService;
use App\Models\Organization;

// Simular factura de exportación con cliente extranjero
$docData = (object) [
    'dGen' => (object) [
        'iDoc' => '03', // Factura de exportación
        'gDatRec' => (object) [
            'iTipoRec' => '04', // Cliente extranjero
            'gRucRec' => (object) [
                'dRuc' => '',
                'dDV' => ''
            ],
            'gIdExt' => (object) [
                'tipoIdentificacion' => '99',
                'dIdExt' => '123456789'
            ],
            'dNombRec' => 'TFHKA Export Client',
            'cPaisRec' => 'VE'
        ]
    ],
    'gFExp' => (object) [
        'cCondEntr' => 'EXW',
        'cMoneda' => 'USD',
        'dPuertoEmbarq' => 'Prueba'
    ],
    'gTot' => (object) [
        'dVTot' => '5.94'
    ]
];

$organization = Organization::first();
$hkaService = new HKAService($organization);

// Usar reflection para acceder al método privado
$reflection = new ReflectionClass($hkaService);
$method = $reflection->getMethod('createFeXML');
$method->setAccessible(true);

$documento = $method->invoke($hkaService, $docData);
$cliente = $documento->datosTransaccion->cliente;

echo "tipoDocumento: {$documento->datosTransaccion->tipoDocumento}\n";
echo "tipoClienteFE: {$cliente->tipoClienteFE}\n";
echo "tipoContribuyente: " . ($cliente->tipoContribuyente ?? 'NULL') . "\n";
echo "datosFacturaExportacion: " . (isset($documento->datosTransaccion->datosFacturaExportacion) ? 'PRESENTE' : 'AUSENTE') . "\n";

if ($documento->datosTransaccion->tipoDocumento === '03' &&
    $cliente->tipoClienteFE === '04' &&
    $cliente->tipoContribuyente === null) {
    echo "✅ CORRECTO: Factura exportación + extranjero SIN tipoContribuyente\n";
    exit(0);
} else {
    echo "❌ ERROR: Factura exportación con extranjero debe omitir tipoContribuyente\n";
    exit(1);
}
EOF

php /tmp/test_exportacion.php
exportacion_result=$?

# Limpiar archivos temporales
rm -f /tmp/test_nacional.php /tmp/test_extranjero.php /tmp/test_exportacion.php

echo ""
echo "=== RESULTADOS FINALES ==="

if [ $nacional_result -eq 0 ]; then
    echo -e "✅ ${GREEN}TEST 1 PASÓ${NC}: Cliente nacional con tipoContribuyente"
else
    echo -e "❌ ${RED}TEST 1 FALLÓ${NC}: Cliente nacional sin tipoContribuyente"
fi

if [ $extranjero_result -eq 0 ]; then
    echo -e "✅ ${GREEN}TEST 2 PASÓ${NC}: Cliente extranjero sin tipoContribuyente"
else
    echo -e "❌ ${RED}TEST 2 FALLÓ${NC}: Cliente extranjero con tipoContribuyente"
fi

if [ $exportacion_result -eq 0 ]; then
    echo -e "✅ ${GREEN}TEST 3 PASÓ${NC}: Factura exportación sin tipoContribuyente"
else
    echo -e "❌ ${RED}TEST 3 FALLÓ${NC}: Factura exportación con tipoContribuyente"
fi

echo ""
if [ $nacional_result -eq 0 ] && [ $extranjero_result -eq 0 ] && [ $exportacion_result -eq 0 ]; then
    echo -e "${GREEN}🎉 TODOS LOS TESTS PASARON${NC}"
    echo -e "${GREEN}✅ Regla tipoContribuyente implementada correctamente${NC}"
    echo -e "${GREEN}✅ Conformidad con documentación TheFactoryHKA${NC}"
    exit 0
else
    echo -e "${RED}❌ ALGUNOS TESTS FALLARON${NC}"
    echo -e "${RED}⚠️  Revisar implementación de regla tipoContribuyente${NC}"
    exit 1
fi
