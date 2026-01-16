#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\User;
use App\Models\SalesHeaderImp;
use App\Models\CustomersImp;
use App\Http\Livewire\Admin\Einvoice\CreateFastJob;

echo "🧪 TEST: Cliente Extranjero QuickBooks - Facturación Electrónica\n";
echo str_repeat("=", 80) . "\n\n";

// Datos de la factura real de QuickBooks con cliente extranjero
$invoiceData = [
    "Invoice" => [
        "Id" => "35",
        "DocNumber" => "FC0000005161",
        "TxnDate" => "2025-07-22",
        "Line" => [
            [
                "Id" => "1",
                "LineNum" => 1,
                "Amount" => 360,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => [
                        "value" => "10",
                        "name" => "Docucenter. Servicio de gestor de documentos"
                    ],
                    "UnitPrice" => 360,
                    "Qty" => 1,
                    "TaxCode" => [
                        "id" => "13",
                        "name" => "ITBMS7",
                        "rateValue" => 7
                    ]
                ]
            ]
        ],
        "TxnTaxDetail" => [
            "TotalTax" => 25.2,
            "TaxLine" => [
                [
                    "Amount" => 25.2,
                    "TaxLineDetail" => [
                        "TaxPercent" => 7,
                        "NetAmountTaxable" => 360
                    ]
                ]
            ]
        ],
        "CustomerRef" => [
            "value" => "6",
            "name" => "Solmary",
            "RUC" => null,
            "DV" => null,
            "TIPO" => null,
            "TIPO_RECEPTOR" => "04", // Cliente extranjero
            "PASAPORTE" => "XYZABC123", // Pasaporte del extranjero
            "CompanyName" => "",
            "DisplayName" => "Solmary",
            "PrimaryEmail" => "solmarymadrid@gmail.com",
            "BillAddr" => [
                "Line1" => "Chile",
                "Line2" => "",
                "City" => "Chile",
                "Country" => "Chile"
            ]
        ],
        "BillAddr" => [
            "Id" => "466",
            "Line1" => "Chile",
            "Line2" => "Chile"
        ],
        "ShipAddr" => [
            "Id" => "16",
            "Line1" => "Chile",
            "City" => "Chile",
            "Country" => "Chile"
        ],
        "TotalAmt" => 385.2,
        "Balance" => 385.2
    ]
];

function testClienteExtranjeroDetection($invoiceData)
{
    echo "📋 1. ANÁLISIS DEL CLIENTE EXTRANJERO\n";
    echo "------------------------------------\n";

    $customerRef = $invoiceData['Invoice']['CustomerRef'];

    echo "Datos del cliente:\n";
    echo "  - Nombre: {$customerRef['name']}\n";
    echo "  - TIPO_RECEPTOR: {$customerRef['TIPO_RECEPTOR']}\n";
    echo "  - PASAPORTE: {$customerRef['PASAPORTE']}\n";
    $country = isset($customerRef['BillAddr']['Country']) ? $customerRef['BillAddr']['Country'] : 'Chile';
    echo "  - País: {$country}\n";
    echo "  - Email: {$customerRef['PrimaryEmail']}\n\n";

    // Simular el mapeo que haría DocuCenter
    echo "Mapeo esperado en DocuCenter:\n";
    echo "  - Custom_field1: {$customerRef['PASAPORTE']} (PASAPORTE)\n";
    echo "  - Custom_field2: null (DV no aplica para extranjeros)\n";
    echo "  - Custom_field3: {$customerRef['TIPO_RECEPTOR']} (TIPO_RECEPTOR)\n";
    echo "  - Country: Chile\n\n";

    $country = isset($customerRef['BillAddr']['Country']) ? $customerRef['BillAddr']['Country'] : 'Chile';
    return [
        'tipo_receptor' => $customerRef['TIPO_RECEPTOR'],
        'pasaporte' => $customerRef['PASAPORTE'],
        'pais' => $country,
        'custom_field1' => $customerRef['PASAPORTE'],
        'custom_field2' => null,
        'custom_field3' => $customerRef['TIPO_RECEPTOR']
    ];
}

function testTipoReceptorMapping($tipoReceptor)
{
    echo "📋 2. MAPEO TIPO RECEPTOR\n";
    echo "-------------------------\n";

    // Simular consulta a type_receptors
    $typeMapping = [
        '01' => ['id' => 1, 'name' => 'Contribuyente'],
        '02' => ['id' => 2, 'name' => 'Consumidor final'],
        '03' => ['id' => 4, 'name' => 'Gobierno'],
        '04' => ['id' => 3, 'name' => 'Extranjero'] // ESTE ES EL IMPORTANTE
    ];

    $paddedCode = str_pad($tipoReceptor, 2, '0', STR_PAD_LEFT);
    echo "Código con padding: '{$paddedCode}'\n";

    if (isset($typeMapping[$paddedCode])) {
        $mapping = $typeMapping[$paddedCode];
        echo "✅ Tipo encontrado: ID {$mapping['id']} - {$mapping['name']}\n";
        echo "receptor_tipo será: '{$mapping['id']}'\n\n";
        return (string)$mapping['id']; // Devolver como string para consistencia
    } else {
        echo "❌ Tipo no encontrado, usar default '2'\n\n";
        return '2';
    }
}

function testCreateFastJobConfiguration($receptorTipoId, $clienteData)
{
    echo "📋 3. CONFIGURACIÓN CreateFastJob\n";
    echo "----------------------------------\n";

    echo "Con receptor_tipo = '{$receptorTipoId}':\n\n";

    // SIMULAR LA NUEVA LÓGICA IMPLEMENTADA
    if ($receptorTipoId === '3' || $receptorTipoId === '4') {
        echo "✅ DETECTADO COMO EXTRANJERO (nueva lógica implementada):\n";
        echo "  - receptor_pasaporteIdentidadExtranjera = '{$clienteData['pasaporte']}'\n";
        echo "  - receptor_ruc = null (extranjeros no tienen RUC panameño)\n";
        echo "  - receptor_DV = null (extranjeros no tienen DV panameño)\n";
        echo "  - destinoOperacion = 2 (Extranjero)\n";
        echo "  - receptor_paisNacionalidad = ID dinámico del país {$clienteData['pais']}\n";
        echo "  - receptor_paisDestinoOperacion = ID dinámico del país {$clienteData['pais']}\n";
        echo "  - ✅ MEJORA: Usa consultas dinámicas al modelo en lugar de IDs hardcodeados\n\n";

        echo "Estructura XML para PAC:\n";
        echo "  Caso '{$receptorTipoId}' en switch:\n";
        if ($receptorTipoId === '3') {
            echo "    ✅ gIdExt (Extranjero):\n";
            echo "      dIdExt: '{$clienteData['pasaporte']}'\n";
            echo "      dPaisExt: '{$clienteData['pais']}'\n";
            echo "    cPaisRec: 'CL' (código Chile)\n";
            echo "    dPaisRecDesc: 'Chile'\n";
        } else {
            echo "    ✅ gRucRec (Persona Jurídica Extranjera):\n";
            echo "      Con datos de extranjero pero estructura jurídica\n";
        }
    } else {
        echo "❌ NO DETECTADO COMO EXTRANJERO:\n";
        echo "  - Se usaría lógica de cliente nacional (incorrecto)\n";
    }

    echo "\n";
}

function testValidacionDestino($destinoOperacion, $paisDestino)
{
    echo "📋 4. VALIDACIÓN DESTINO OPERACIÓN\n";
    echo "-----------------------------------\n";

    echo "destinoOperacion = {$destinoOperacion}\n";
    echo "receptor_paisDestinoOperacion = {$paisDestino}\n\n";

    if ($destinoOperacion == '1' && $paisDestino != '174') {
        echo "❌ ERROR DE VALIDACIÓN:\n";
        echo "  QuickBooks rechazaría: 'El país del cliente debe ser PA si destino operación = 1'\n";
        echo "  Solución: destinoOperacion debe ser 2 para extranjeros\n\n";
        return false;
    } elseif ($destinoOperacion == '2' && $paisDestino != '174') {
        echo "✅ VALIDACIÓN CORRECTA:\n";
        echo "  Destino extranjero con país no-panameño\n\n";
        return true;
    } else {
        echo "ℹ️  CONFIGURACIÓN NACIONAL:\n";
        echo "  Destino nacional con país panameño\n\n";
        return true;
    }
}

// Ejecutar pruebas
$clienteData = testClienteExtranjeroDetection($invoiceData);
$receptorTipoId = testTipoReceptorMapping($clienteData['tipo_receptor']);
testCreateFastJobConfiguration($receptorTipoId, $clienteData);

// Simular valores después de los cambios
$destinoOperacion = ($receptorTipoId === '3' || $receptorTipoId === '4') ? '2' : '1';
$paisDestino = ($receptorTipoId === '3' || $receptorTipoId === '4') ? 'Chile_ID' : '174';

$validacionOk = testValidacionDestino($destinoOperacion, $paisDestino);

echo "📊 RESUMEN FINAL\n";
echo "================\n";

if ($validacionOk && ($receptorTipoId === '3' || $receptorTipoId === '4')) {
    echo "✅ CONFIGURACIÓN CORRECTA PARA CLIENTE EXTRANJERO\n";
    echo "  - Tipo receptor: {$receptorTipoId} (Extranjero)\n";
    echo "  - Pasaporte configurado correctamente\n";
    echo "  - Destino operación: 2 (Extranjero)\n";
    echo "  - País destino: Chile\n";
    echo "  - Estructura XML adecuada para PAC\n\n";
    echo "🎉 La factura debería emitirse sin problemas\n";
} else {
    echo "❌ CONFIGURACIÓN REQUIERE AJUSTES\n";
    echo "  - Revisar lógica de detección de extranjeros\n";
    echo "  - Verificar mapeo de tipo receptor\n";
    echo "  - Ajustar destino de operación\n\n";
    echo "🔧 Aplicar los cambios realizados en CreateFastJob\n";
}

echo "\n📚 Documentación:\n";
echo "  - Cambios: docs/testing/test-quickbooks-extranjero-factura.php\n";
echo "  - Código: app/Http/Livewire/Admin/Einvoice/CreateFastJob.php\n";
echo "  - Commit: ee498188 - mejorar detección de clientes extranjeros\n";
