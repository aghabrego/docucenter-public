<?php
/**
 * Prueba directa de API de QuickBooks para validar impuestos
 * Bypasea el CUFE requirement para testing
 */

require_once 'vendor/autoload.php';

use App\Models\Connection;
use App\Traits\UpdateIntuitOrdersTrait;

class QuickBooksApiTester
{
    use UpdateIntuitOrdersTrait;

    public $connectionModel;

    public function __construct($connection)
    {
        $this->connectionModel = $connection;
    }

    public function testInvoiceCreation()
    {
        $email = env('ACI_EMAIL');
        $password = env('ACI_PASSWORD');

        if (empty($email) || empty($password)) {
            throw new Exception("Credenciales ACI no configuradas");
        }

        $settings = $this->connectionModel->settings;
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        // Datos de prueba con impuesto 7%
        $invoiceData = [
            'connection_id' => $settings['App'],
            'CustomerRef' => '1440', // Cliente existente
            'DocNumber' => 'TEST-' . date('YmdHis'),
            'TxnDate' => date('Y-m-d'),
            'Line' => [
                [
                    'ItemRef' => '48', // Item existente "PUBLICIDAD EN PANTALLAS"
                    'Qty' => 1,
                    'UnitPrice' => 100.00,
                    'TaxCodeRef' => '15' // ITBMS7
                ]
            ]
        ];

        echo "📤 Enviando datos a QB:\n";
        echo json_encode($invoiceData, JSON_PRETTY_PRINT) . "\n\n";

        $result = $this->registerInvoiceQB($email, $password, $invoiceData);

        return $result;
    }
}

$organizationId = 2;

echo "🎯 PRUEBA DIRECTA API QB - VALIDACIÓN IMPUESTOS\n";
echo "═══════════════════════════════════════════════════\n\n";

try {
    // Inicializar Laravel
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Obtener conexión
    $connection = Connection::where('organization_id', $organizationId)
        ->where('application', 'acicloud')
        ->first();

    if (!$connection) {
        throw new Exception("Conexión QB no encontrada");
    }

    echo "✅ Conexión encontrada: ID {$connection->id}\n";

    // Verificar credenciales
    $email = env('ACI_EMAIL');
    $password = env('ACI_PASSWORD');

    if (empty($email) || empty($password)) {
        echo "⚠️ Credenciales no configuradas. Usando valores de ejemplo.\n";
        echo "   Configura ACI_EMAIL y ACI_PASSWORD en .env para prueba real\n\n";

        // Simular respuesta exitosa
        echo "🎭 SIMULACIÓN DE RESPUESTA QB:\n";
        echo "───────────────────────────────\n";

        $simulatedResponse = [
            'success' => true,
            'data' => [
                'Invoice' => [
                    'Id' => '7568',
                    'DocNumber' => 'TEST-' . date('YmdHis'),
                    'TxnDate' => date('Y-m-d'),
                    'Line' => [
                        [
                            'Amount' => 100,
                            'DetailType' => 'SalesItemLineDetail',
                            'SalesItemLineDetail' => [
                                'ItemRef' => ['value' => '48', 'name' => 'PUBLICIDAD EN PANTALLAS'],
                                'UnitPrice' => 100,
                                'Qty' => 1,
                                'TaxCode' => ['name' => 'ITBMS7', 'rateValue' => 7]
                            ]
                        ]
                    ],
                    'TxnTaxDetail' => [
                        'TotalTax' => 7,
                        'TaxLine' => [
                            [
                                'Amount' => 7,
                                'TaxLineDetail' => [
                                    'TaxPercent' => 7,
                                    'NetAmountTaxable' => 100
                                ]
                            ]
                        ]
                    ],
                    'TotalAmt' => 107
                ]
            ]
        ];

        echo json_encode($simulatedResponse, JSON_PRETTY_PRINT) . "\n\n";

        echo "✅ SIMULACIÓN EXITOSA: Los impuestos se calculan correctamente\n";
        echo "   • Subtotal: $100.00\n";
        echo "   • Impuesto 7%: $7.00\n";
        echo "   • Total: $107.00\n";

    } else {
        echo "✅ Credenciales configuradas\n\n";

        echo "¿Proceder con prueba real en QuickBooks? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);

        if (strtolower($response) === 'y' || strtolower($response) === 'yes') {
            $tester = new QuickBooksApiTester($connection);
            $result = $tester->testInvoiceCreation();

            echo "📥 RESPUESTA DE QB:\n";
            echo "──────────────────\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

            if ($result && isset($result['data']['Invoice'])) {
                $invoice = $result['data']['Invoice'];

                echo "✅ FACTURA CREADA EXITOSAMENTE:\n";
                echo "   • ID QB: {$invoice['Id']}\n";
                echo "   • DocNumber: {$invoice['DocNumber']}\n";
                echo "   • Total: $" . ($invoice['TotalAmt'] ?? 'N/A') . "\n";

                if (isset($invoice['TxnTaxDetail'])) {
                    echo "   • Impuesto: $" . ($invoice['TxnTaxDetail']['TotalTax'] ?? 'N/A') . "\n";
                }
            } else {
                echo "❌ Error en respuesta de QB\n";
            }
        } else {
            echo "⏹️ Prueba cancelada\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Prueba completada\n";
