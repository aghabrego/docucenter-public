<?php

/**
 * Test para verificar descarga de exportables HKA
 *
 * Uso dentro de Docker:
 * docker exec -it docucenter-app-1 php docs/testing/test-hka-export-download.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;
use App\Services\HKAService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "================================================\n";
echo "Test de Descarga de Exportables HKA\n";
echo "================================================\n\n";

// Parámetros de prueba
$params = [
    'cufe' => 'FE0120000155627441-2-2016-0900012025112600000036421000111306859559',
    'codigoSucursalEmisor' => '0001',
    'numeroDocumentoFiscal' => 3642,
    'puntoFacturacionFiscal' => '100',
    'tipoDocumento' => 1,
    'tipoEmision' => '01'
];

echo "Parámetros de prueba:\n";
foreach ($params as $key => $value) {
    echo "  - {$key}: {$value}\n";
}
echo "\n";

// Buscar una organización con PAC HKA
DB::connection()->useDatabase(env('DB_DATABASE'));

$organization = Organization::query()
    ->whereHas('pacConnection', function($query) {
        $query->where('name', 'TheFactoryHKA');
    })
    ->first();

if (!$organization) {
    echo "❌ No se encontró ninguna organización con PAC TheFactoryHKA\n";
    exit(1);
}

echo "✓ Organización encontrada: {$organization->name} (ID: {$organization->id})\n";
echo "  Database: {$organization->database}\n\n";

// Obtener la conexión PAC
$pacConnection = $organization->pacConnection;
echo "✓ Conexión PAC encontrada:\n";
echo "  Nombre: {$pacConnection->name}\n";
echo "  Endpoint: {$pacConnection->endpoint}\n";
echo "  Pac Type: {$pacConnection->pac_type}\n\n";

// Crear el servicio HKA
try {
    $hkaService = new HKAService($organization);
    echo "✓ Servicio HKA creado correctamente\n\n";
} catch (\Exception $e) {
    echo "❌ Error al crear servicio HKA: {$e->getMessage()}\n";
    exit(1);
}

// Test 1: Descargar PDF
echo "================================================\n";
echo "Test 1: Descargar PDF\n";
echo "================================================\n";

try {
    echo "Creando objeto Exportable...\n";

    // Crear el objeto Exportable necesario con el formato correcto de HKA
    $exportable = new \App\Utils\hka\Exportable();
    $exportable->codigoSucursalEmisor = $params['codigoSucursalEmisor'];
    $exportable->numeroDocumentoFiscal = $params['numeroDocumentoFiscal'];
    $exportable->puntoFacturacionFiscal = str_pad($params['puntoFacturacionFiscal'], 3, '0', STR_PAD_LEFT); // Pad a 3 dígitos
    $exportable->tipoDocumento = str_pad($params['tipoDocumento'], 2, '0', STR_PAD_LEFT); // Pad a 2 dígitos
    $exportable->tipoEmision = $params['tipoEmision'];

    echo "✓ Objeto Exportable creado\n";
    echo "  tipoDocumento (con padding): {$exportable->tipoDocumento}\n";
    echo "  puntoFacturacionFiscal (con padding): {$exportable->puntoFacturacionFiscal}\n";
    echo "Llamando a wsConnExport(\$exportable, 'DescargaPDF')...\n";    $pdfResponse = $hkaService->wsConnExport($exportable, 'DescargaPDF', false);

    // Convertir objeto a array si es necesario
    if (is_object($pdfResponse)) {
        $pdfResponse = json_decode(json_encode($pdfResponse), true);
    }

    if (isset($pdfResponse['resultado']) && $pdfResponse['resultado'] === 'procesado') {
        echo "✓ Respuesta exitosa del PAC\n";
        echo "  Código: {$pdfResponse['codigo']}\n";
        echo "  Mensaje: {$pdfResponse['mensaje']}\n";

        if (isset($pdfResponse['pdf'])) {
            $pdfSize = strlen($pdfResponse['pdf']);
            echo "  PDF recibido: " . number_format($pdfSize) . " bytes\n";

            // Verificar que es un PDF válido (inicia con %PDF)
            $pdfDecoded = base64_decode($pdfResponse['pdf']);
            if (strpos($pdfDecoded, '%PDF') === 0) {
                echo "  ✓ PDF válido (header verificado)\n";
            } else {
                echo "  ⚠ PDF posiblemente inválido (header no encontrado)\n";
            }
        } else {
            echo "  ⚠ No se recibió contenido PDF en la respuesta\n";
        }
    } else {
        echo "❌ Error en la respuesta del PAC:\n";
        print_r($pdfResponse);
    }
} catch (\Exception $e) {
    echo "❌ Error al descargar PDF: {$e->getMessage()}\n";
    echo "  Trace: {$e->getTraceAsString()}\n";
}

echo "\n";

// Test 2: Descargar XML
echo "================================================\n";
echo "Test 2: Descargar XML\n";
echo "================================================\n";

try {
    echo "Creando objeto Exportable...\n";

    // Crear el objeto Exportable necesario con el formato correcto de HKA
    $exportable = new \App\Utils\hka\Exportable();
    $exportable->codigoSucursalEmisor = $params['codigoSucursalEmisor'];
    $exportable->numeroDocumentoFiscal = $params['numeroDocumentoFiscal'];
    $exportable->puntoFacturacionFiscal = str_pad($params['puntoFacturacionFiscal'], 3, '0', STR_PAD_LEFT); // Pad a 3 dígitos
    $exportable->tipoDocumento = str_pad($params['tipoDocumento'], 2, '0', STR_PAD_LEFT); // Pad a 2 dígitos
    $exportable->tipoEmision = $params['tipoEmision'];

    echo "✓ Objeto Exportable creado\n";
    echo "  tipoDocumento (con padding): {$exportable->tipoDocumento}\n";
    echo "  puntoFacturacionFiscal (con padding): {$exportable->puntoFacturacionFiscal}\n";
    echo "Llamando a wsConnExport(\$exportable, 'DescargaXML')...\n";    $xmlResponse = $hkaService->wsConnExport($exportable, 'DescargaXML', false);

    // Convertir objeto a array si es necesario
    if (is_object($xmlResponse)) {
        $xmlResponse = json_decode(json_encode($xmlResponse), true);
    }

    if (isset($xmlResponse['resultado']) && $xmlResponse['resultado'] === 'procesado') {
        echo "✓ Respuesta exitosa del PAC\n";
        echo "  Código: {$xmlResponse['codigo']}\n";
        echo "  Mensaje: {$xmlResponse['mensaje']}\n";

        if (isset($xmlResponse['xml'])) {
            $xmlSize = strlen($xmlResponse['xml']);
            echo "  XML recibido: " . number_format($xmlSize) . " bytes\n";

            // Verificar que es un XML válido
            $xmlDecoded = base64_decode($xmlResponse['xml']);
            if (strpos($xmlDecoded, '<?xml') === 0) {
                echo "  ✓ XML válido (header verificado)\n";

                // Intentar parsear el XML
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($xmlDecoded);
                if ($xml !== false) {
                    echo "  ✓ XML parseado correctamente\n";
                } else {
                    echo "  ⚠ Error al parsear XML\n";
                }
            } else {
                echo "  ⚠ XML posiblemente inválido (header no encontrado)\n";
            }
        } else {
            echo "  ⚠ No se recibió contenido XML en la respuesta\n";
        }
    } else {
        echo "❌ Error en la respuesta del PAC:\n";
        print_r($xmlResponse);
    }
} catch (\Exception $e) {
    echo "❌ Error al descargar XML: {$e->getMessage()}\n";
    echo "  Trace: {$e->getTraceAsString()}\n";
}

echo "\n";
echo "================================================\n";
echo "Test Completado\n";
echo "================================================\n\n";

echo "Siguiente paso: Probar las rutas web\n";
echo "URL PDF: https://apconpanama.me/hka/download_pdf_document?" . http_build_query($params) . "\n";
echo "URL XML: https://apconpanama.me/hka/download_xml_document?" . http_build_query($params) . "\n\n";
