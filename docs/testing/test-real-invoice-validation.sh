#!/bin/bash

# Script para probar create_sale_quickbooks con objeto real
echo "=== TESTING CREATE_SALE_QUICKBOOKS CON OBJETO REAL ==="
echo "Fecha: $(date)"
echo "Objeto: FA0000004044 - LUIS INFANTE"
echo

# Ejecutar la validación con el objeto completo
docker exec -it docucenter_laravel.test php artisan tinker --execute="
// Datos del objeto de factura que está causando error
\$invoiceData = [
    'Invoice' => [
        'AllowIPNPayment' => false,
        'AllowOnlinePayment' => false,
        'AllowOnlineCreditCardPayment' => false,
        'AllowOnlineACHPayment' => false,
        'domain' => 'QBO',
        'sparse' => false,
        'Id' => '9704',
        'SyncToken' => '2',
        'MetaData' => [
            'CreateTime' => '2025-10-16T13:00:59-07:00',
            'LastModifiedByRef' => ['value' => '9341454172670938'],
            'LastUpdatedTime' => '2025-10-16T15:53:34-07:00'
        ],
        'CustomField' => [],
        'DocNumber' => 'FA0000004044',
        'TxnDate' => '2025-10-16',
        'CurrencyRef' => ['value' => 'PAB', 'name' => 'Balboa de Panamá'],
        'LinkedTxn' => [['TxnId' => '9705', 'TxnType' => 'Payment']],
        'Line' => [
            [
                'Id' => '1',
                'LineNum' => 1,
                'Amount' => 120,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => '1', 'name' => 'Services'],
                    'UnitPrice' => 120,
                    'Qty' => 1,
                    'TaxCodeRef' => ['value' => '2']
                ],
                'CustomExtensions' => []
            ],
            [
                'Amount' => 120,
                'DetailType' => 'SubTotalLineDetail',
                'SubTotalLineDetail' => []
            ]
        ],
        'TxnTaxDetail' => [
            'TotalTax' => 8.4,
            'TaxLine' => [
                [
                    'Amount' => 8.4,
                    'DetailType' => 'TaxLineDetail',
                    'TaxLineDetail' => [
                        'TaxRateRef' => ['value' => '2'],
                        'PercentBased' => true,
                        'TaxPercent' => 7,
                        'NetAmountTaxable' => 120
                    ]
                ]
            ]
        ],
        'CustomerRef' => [
            'value' => '1634',
            'name' => 'LUIS INFANTE',
            'RUC' => null,
            'DV' => null,
            'TIPO' => null,
            'TIPO_RECEPTOR' => '02',
            'PASAPORTE' => null,
            'CompanyName' => '',
            'DisplayName' => 'LUIS INFANTE',
            'PrimaryEmail' => '',
            'BillAddr' => [
                'Line1' => '',
                'Line2' => '',
                'City' => '',
                'Country' => '',
                'CountrySubDivisionCode' => '',
                'PostalCode' => ''
            ]
        ],
        'BillAddr' => [
            'Id' => '5236',
            'Line1' => 'LUIS INFANTE'
        ],
        'ShipAddr' => ['Id' => '5231'],
        'FreeFormAddress' => true,
        'SalesTermRef' => ['value' => '3'],
        'DueDate' => '2025-11-15',
        'GlobalTaxCalculation' => 'TaxExcluded',
        'TotalAmt' => 128.4,
        'PrintStatus' => 'NotSet',
        'EmailStatus' => 'NotSet',
        'Balance' => 0
    ]
];

echo \"=== VALIDACIÓN COMPLETA DEL OBJETO DE FACTURA ===\" . PHP_EOL;

// Probar extracción de datos del contacto
use App\Http\Controllers\V1\FeController;
\$controller = new FeController();
\$reflection = new ReflectionClass(\$controller);

// Método para extraer datos del contacto
\$extractMethod = \$reflection->getMethod('extractCustomerDataFromRequest');
\$extractMethod->setAccessible(true);
\$customerData = \$extractMethod->invoke(\$controller, \$invoiceData);

echo \"=== DATOS DEL CONTACTO EXTRAÍDOS ===\" . PHP_EOL;
echo json_encode(\$customerData, JSON_PRETTY_PRINT) . PHP_EOL;

// Método para validar el contacto
\$validateMethod = \$reflection->getMethod('validateQuickBooksContactForInvoice');
\$validateMethod->setAccessible(true);

echo PHP_EOL . \"=== RESULTADO DE VALIDACIÓN ===\" . PHP_EOL;
try {
    \$validateMethod->invoke(\$controller, \$invoiceData);
    echo \"✅ VALIDACIÓN EXITOSA\" . PHP_EOL;
    echo \"El objeto cumple todos los requisitos para facturación electrónica\" . PHP_EOL;
} catch (Exception \$e) {
    echo \"❌ ERROR: \" . \$e->getMessage() . PHP_EOL;
}

echo PHP_EOL . \"=== RESUMEN ===\" . PHP_EOL;
echo \"- Factura: FA0000004044\" . PHP_EOL;
echo \"- Cliente: LUIS INFANTE\" . PHP_EOL;
echo \"- Moneda: PAB (Balboa de Panamá)\" . PHP_EOL;
echo \"- Monto: USD 128.4\" . PHP_EOL;
echo \"- Tipo Receptor: 02\" . PHP_EOL;
echo \"- RUC: null (permitido)\" . PHP_EOL;
echo \"- DV: null (permitido)\" . PHP_EOL;
echo \"- Email: '' (convertido a null)\" . PHP_EOL;
"

echo
echo "=== PRUEBA COMPLETADA ==="
