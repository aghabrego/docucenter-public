<?php

/**
 * Script de Testing: Prevención de Duplicación QuickBooks
 *
 * Este script prueba las mejoras implementadas en validateQuickBooksInvoiceDuplication()
 * para prevenir duplicados entre API y webhook de QuickBooks.
 *
 * IMPORTANTE: Ejecutar dentro del contenedor Docker:
 * docker exec -it docucenter-app-1 php docs/testing/test-quickbooks-duplication-prevention.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Organization;
use App\Models\SalesHeaderImp;
use App\Http\Controllers\V1\FeController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuickBooksDuplicationTest
{
    private $organization;
    private $controller;
    private $testCustomer = 'Oriana Fernandez';
    private $testAmount = 115.00;

    public function __construct()
    {
        $this->controller = new FeController();
        echo "🧪 Testing: Prevención de Duplicación QuickBooks\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
    }

    public function run()
    {
        try {
            $this->setupTestOrganization();
            $this->cleanupTestData();

            echo "📋 EJECUTANDO PRUEBAS:\n";
            echo "-" . str_repeat("-", 30) . "\n";

            $this->testScenario1_NormalProcessing();
            $this->testScenario2_ExactDuplicateDetection();
            $this->testScenario3_QuickBooksIdDuplication();
            $this->testScenario4_WebhookTimingDuplication();
            $this->testScenario5_IntelligentSimilarCustomerDetection();
            $this->testScenario6_DocumentNumberLikeDetection(); // NUEVO: Prueba LIKE para números
            $this->testScenario7_RealWorldQuickBooksObject(); // NUEVO: Datos reales del usuario
            $this->testScenario8_ExactRealCaseOrianaDuplication(); // NUEVO: Caso exacto del usuario
            $this->testScenario9_CaseInsensitiveExactValidation(); // NUEVO: Validación exacta case-insensitive
            $this->testScenario10_QuickBooksPrefixDetection(); // NUEVO: Detección de prefijos FE/NC

            echo "\n✅ TODAS LAS PRUEBAS COMPLETADAS\n";        } catch (\Exception $e) {
            echo "❌ ERROR EN PRUEBAS: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
        } finally {
            $this->cleanupTestData();
        }
    }

    private function setupTestOrganization()
    {
        echo "🏢 Configurando organización de prueba...\n";

        // Buscar una organización activa para testing
        $this->organization = Organization::where('database', '!=', '')
            ->whereNotNull('database')
            ->first();

        if (!$this->organization) {
            throw new \Exception("No se encontró organización con base de datos configurada para testing");
        }

        echo "   Organización: {$this->organization->name} (ID: {$this->organization->id})\n";
        echo "   Base de datos: {$this->organization->database}\n\n";
    }

    private function cleanupTestData()
    {
        echo "🧹 Limpiando datos de prueba...\n";

        DB::connection()->useDatabase($this->organization->database);

        // Limpiar datos de todas las pruebas
        SalesHeaderImp::where('CustomerName', $this->testCustomer)
            ->where('origin', 'quickbooks')
            ->where(function($query) {
                $query->where('InvoiceNumber', 'LIKE', 'TEST_%')
                      ->orWhere('InvoiceNumber', 'LIKE', 'FE%')
                      ->orWhere('fiscal_document_number', 'LIKE', 'FISCAL-%')
                      ->orWhere('fiscal_document_number', 'LIKE', 'FE01-%');
            })
            ->delete();

        // Limpiar registros de prueba específicos (incluyendo los nuevos del escenario 10)
        SalesHeaderImp::where('origin', 'quickbooks')
            ->where(function($query) {
                $query->where('CustomerName', 'LIKE', '%ORIANA%')
                      ->orWhere('CustomerName', 'LIKE', '%MARIA GONZALEZ%')
                      ->orWhere('CustomerName', 'LIKE', '%EMPRESA TESTING%')
                      ->orWhere('InvoiceNumber', 'LIKE', 'TEST_%')
                      ->orWhere('fiscal_document_number', 'LIKE', '%3636%')
                      ->orWhere('fiscal_document_number', 'LIKE', '%3670%')
                      ->orWhereIn('intuit_invoice_id', ['3733', '3817', '9001', '9002', '9100', '9101']);
            })
            ->delete();

        DB::connection()->useDatabase(env('DB_DATABASE'));
        echo "   Datos de prueba eliminados\n\n";
    }

    public function testScenario1_NormalProcessing()
    {
        echo "🔍 Escenario 1: Procesamiento Normal (SIN duplicados)\n";

        $requestData = [
            'Invoice' => [
                'Id' => '3900',
                'DocNumber' => 'TEST_NORMAL_001',
                'CustomerRef' => [
                    'name' => $this->testCustomer,
                    'value' => '123'
                ],
                'TotalAmt' => $this->testAmount
            ]
        ];

        try {
            // Debe pasar sin excepciones
            $this->invokeValidation($requestData);
            echo "   ✅ Validación pasó correctamente - No hay duplicados\n\n";
        } catch (\Exception $e) {
            echo "   ❌ FALLO: Validación falló cuando no debería - " . $e->getMessage() . "\n\n";
        }
    }

    public function testScenario2_ExactDuplicateDetection()
    {
        echo "🔍 Escenario 2: Detección de Duplicado Exacto por Documento\n";

        // Crear una factura existente
        DB::connection()->useDatabase($this->organization->database);
        SalesHeaderImp::create([
            'InvoiceNumber' => 'TEST_EXACT_001',
            'CustomerName' => $this->testCustomer,
            'fiscal_document_number' => 'TEST_EXACT_001',
            'origin' => 'quickbooks',
            'Net_due' => $this->testAmount,
            'Date' => now()->format('Y-m-d'),
            'intuit_invoice_id' => 3901
        ]);
        DB::connection()->useDatabase(env('DB_DATABASE'));

        $requestData = [
            'Invoice' => [
                'Id' => '3902',
                'DocNumber' => 'TEST_EXACT_001', // Mismo número de documento
                'CustomerRef' => [
                    'name' => $this->testCustomer, // Mismo cliente
                    'value' => '123'
                ],
                'TotalAmt' => $this->testAmount
            ]
        ];

        try {
            $this->invokeValidation($requestData);
            echo "   ❌ FALLO: Validación pasó cuando debería haber bloqueado duplicado exacto\n\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "   ✅ Duplicado exacto detectado y bloqueado correctamente\n";
                echo "      Mensaje: " . substr($e->getMessage(), 0, 100) . "...\n\n";
            } else {
                echo "   ❌ FALLO: Excepción incorrecta - " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function testScenario3_QuickBooksIdDuplication()
    {
        echo "🔍 Escenario 3: Detección de Duplicado por QuickBooks ID\n";

        // Crear una factura con intuit_invoice_id específico
        DB::connection()->useDatabase($this->organization->database);
        SalesHeaderImp::create([
            'InvoiceNumber' => 'TEST_QB_ID_001',
            'CustomerName' => $this->testCustomer,
            'fiscal_document_number' => 'TEST_QB_ID_001',
            'origin' => 'quickbooks',
            'Net_due' => $this->testAmount,
            'Date' => now()->format('Y-m-d'),
            'intuit_invoice_id' => 3903
        ]);
        DB::connection()->useDatabase(env('DB_DATABASE'));

        $requestData = [
            'Invoice' => [
                'Id' => '3903', // Mismo QB ID
                'DocNumber' => 'TEST_QB_ID_002', // Diferente número
                'CustomerRef' => [
                    'name' => 'Cliente Diferente', // Diferente cliente
                    'value' => '456'
                ],
                'TotalAmt' => 200.00 // Diferente monto
            ]
        ];

        try {
            $this->invokeValidation($requestData);
            echo "   ❌ FALLO: Validación pasó cuando debería haber bloqueado por QB ID duplicado\n\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'QuickBooks ID') !== false) {
                echo "   ✅ Duplicado por QuickBooks ID detectado y bloqueado correctamente\n";
                echo "      Mensaje: " . substr($e->getMessage(), 0, 100) . "...\n\n";
            } else {
                echo "   ❌ FALLO: Excepción incorrecta - " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function testScenario4_WebhookTimingDuplication()
    {
        echo "🔍 Escenario 4: Detección de Múltiples Facturas Recientes (Advertencia)\n";

        // Crear 2 facturas recientes del mismo cliente para simular actividad webhook
        DB::connection()->useDatabase($this->organization->database);

        // Primera factura (hace 20 minutos)
        SalesHeaderImp::create([
            'InvoiceNumber' => 'TEST_WEBHOOK_001',
            'CustomerName' => $this->testCustomer,
            'fiscal_document_number' => 'TEST_WEBHOOK_001',
            'origin' => 'quickbooks',
            'Net_due' => 100.00,
            'Date' => now()->format('Y-m-d'),
            'intuit_invoice_id' => 3904,
            'created_at' => now()->subMinutes(20)
        ]);

        // Segunda factura (hace 10 minutos)
        SalesHeaderImp::create([
            'InvoiceNumber' => 'TEST_WEBHOOK_002',
            'CustomerName' => $this->testCustomer,
            'fiscal_document_number' => 'TEST_WEBHOOK_002',
            'origin' => 'quickbooks',
            'Net_due' => 200.00, // Monto diferente - ya no importa
            'Date' => now()->format('Y-m-d'),
            'intuit_invoice_id' => 3905,
            'created_at' => now()->subMinutes(10)
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        $requestData = [
            'Invoice' => [
                'Id' => '3906',
                'DocNumber' => 'TEST_WEBHOOK_003', // Diferente número
                'CustomerRef' => [
                    'name' => $this->testCustomer, // Mismo cliente
                    'value' => '123'
                ],
                'TotalAmt' => 300.00 // Monto completamente diferente
            ]
        ];

        try {
            $this->invokeValidation($requestData);
            echo "   ✅ Validación pasó correctamente - Solo genera warning, no bloquea\n";
            echo "      (Mejora: Ya no bloquea por monto similar, evita falsos positivos)\n\n";
        } catch (\Exception $e) {
            echo "   ❌ FALLO INESPERADO: Validación falló cuando debería solo advertir - " . $e->getMessage() . "\n\n";
        }
    }

    public function testScenario5_IntelligentSimilarCustomerDetection()
    {
        echo "🔍 Escenario 5: Detección Inteligente de Cliente Similar (Caso Real)\n";

        // Crear factura existente que simula el caso real
        DB::connection()->useDatabase($this->organization->database);
        SalesHeaderImp::create([
            'InvoiceNumber' => 'a7Myen',
            'CustomerName' => 'ORIANA FERNANDEZ', // Mayúsculas
            'fiscal_document_number' => '0000003636',
            'origin' => 'quickbooks',
            'Net_due' => 69.55, // $65.00 + $4.55 ITBMS
            'Date' => now()->format('Y-m-d'),
            'intuit_invoice_id' => 3733,
            'created_at' => now()->subMinutes(15)
        ]);
        DB::connection()->useDatabase(env('DB_DATABASE'));

        $requestData = [
            'Invoice' => [
                'Id' => '3817',
                'DocNumber' => 'FE0000003636', // Diferente número
                'CustomerRef' => [
                    'name' => 'Oriana Fernandez', // Minúsculas (diferente case)
                    'value' => '123'
                ],
                'TotalAmt' => 65.00 // Monto sin ITBMS (dentro del 15% de tolerancia)
            ]
        ];

        echo "   Debug: Cliente existente: 'ORIANA FERNANDEZ' (\$69.55)\n";
        echo "   Debug: Cliente nuevo: 'Oriana Fernandez' (\$65.00)\n";
        echo "   Debug: Diferencia de monto: " . abs(69.55 - 65.00) . " (" . round((abs(69.55 - 65.00) / 69.55) * 100, 1) . "%)\n";

        try {
            $this->invokeValidation($requestData);
            echo "   ❌ FALLO: Validación pasó cuando debería haber detectado cliente similar\n\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'muy similar') !== false || strpos($e->getMessage(), 'Similaridad') !== false) {
                echo "   ✅ Cliente similar detectado y bloqueado correctamente\n";
                echo "      Mensaje: " . substr($e->getMessage(), 0, 150) . "...\n\n";
            } else {
                echo "   ❌ FALLO: Excepción incorrecta - " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function testScenario6_DocumentNumberLikeDetection()
    {
        echo "🔍 Escenario 6: Detección LIKE de Números de Documento\n";

        // Crear una factura base
        DB::connection()->useDatabase($this->organization->database);

        SalesHeaderImp::create([
            'CustomerName' => $this->testCustomer,
            'InvoiceNumber' => 'INV-2024-001',
            'fiscal_document_number' => 'FISCAL-001',
            'intuit_invoice_id' => null,
            'origin' => 'quickbooks',
            'Net_due' => $this->testAmount,
            'Date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        // Probar con número parcial que debería ser detectado por LIKE
        $requestData = [
            'Invoice' => [
                'Id' => '3906',
                'DocNumber' => '001', // Número parcial que existe en 'FISCAL-001'
                'CustomerRef' => [
                    'name' => $this->testCustomer,
                    'value' => '123'
                ],
                'TotalAmt' => $this->testAmount
            ]
        ];

        try {
            $this->invokeValidation($requestData);
            echo "   ❌ FALLO: Validación pasó cuando debería detectar número similar\n\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "   ✅ Número de documento similar detectado correctamente por LIKE\n";
                echo "      DocNumber buscado: '001' encontrado en 'FISCAL-001'\n";
                echo "      Mensaje: " . substr($e->getMessage(), 0, 120) . "...\n\n";
            } else {
                echo "   ❌ FALLO: Excepción incorrecta - " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function testScenario7_RealWorldQuickBooksObject()
    {
        echo "🔍 Escenario 7: Objeto QuickBooks Real del Usuario\n";

        // Crear factura existente basada en los datos de prueba previos
        DB::connection()->useDatabase($this->organization->database);

        SalesHeaderImp::create([
            'CustomerName' => 'ORIANA FERNANDEZ', // Mayúsculas como en el sistema
            'InvoiceNumber' => 'FE0000003633', // Similar pero diferente
            'fiscal_document_number' => 'FE01-20000155757563-2-2024-48000020250930-00000036330',
            'intuit_invoice_id' => '8792', // ID diferente
            'origin' => 'quickbooks',
            'Net_due' => 69.55, // Monto ligeramente diferente (caso real)
            'Date' => '2025-09-30',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10)
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        // Probar con el objeto real que proporcionó el usuario
        $realWorldData = [
            'Invoice' => [
                'domain' => 'QBO',
                'Id' => '8795',
                'MetaData' => [
                    'CreateTime' => '2025-09-30T19:19:36-07:00',
                    'LastModifiedByRef' => [
                        'value' => '9341454172670938'
                    ],
                    'LastUpdatedTime' => '2025-09-30T19:19:36-07:00'
                ],
                'DocNumber' => 'FE0000003636',
                'TxnDate' => '2025-10-01',
                'CurrencyRef' => [
                    'value' => 'PAB',
                    'name' => 'Balboa de Panamá'
                ],
                'PrivateNote' => 'CUFE: FE0120000155757563-2-2024-4800002025093000000036360010112430811061',
                'CustomerRef' => [
                    'value' => '1170',
                    'name' => 'Oriana Fernandez', // Variación de case vs 'ORIANA FERNANDEZ'
                    'TIPO_RECEPTOR' => '02',
                    'CompanyName' => null,
                    'DisplayName' => 'Oriana Fernandez',
                    'PrimaryEmail' => 'fernandezoriana@gmail.com'
                ],
                'TotalAmt' => 65, // Diferente al existente ($69.55)
                'Balance' => 65
            ]
        ];

        try {
            $this->invokeValidation($realWorldData);
            echo "   ❌ FALLO: Validación pasó - Sistema debería detectar cliente similar\n";
            echo "      'Oriana Fernandez' vs 'ORIANA FERNANDEZ' existente\n";
            echo "      Montos: $65.00 vs $69.55 (6.5% diferencia)\n\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "   ✅ Cliente similar detectado correctamente\n";
                echo "      'Oriana Fernandez' ($65.00) vs 'ORIANA FERNANDEZ' ($69.55)\n";
                echo "      Similitud detectada por algoritmo inteligente\n";
                echo "      Diferencia de monto: 6.5% (dentro del 15% de tolerancia)\n";
                echo "      Mensaje: " . substr($e->getMessage(), 0, 150) . "...\n\n";
            } else {
                echo "   ❌ FALLO: Excepción incorrecta - " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function testScenario8_ExactRealCaseOrianaDuplication()
    {
        echo "🔍 Escenario 8: Caso Exacto del Usuario - Oriana Fernandez ID 3733 vs 3817\n";

        // Crear la factura ORIGINAL (API) - ID 3733
        DB::connection()->useDatabase($this->organization->database);

        $originalSale = SalesHeaderImp::create([
            'CustomerName' => 'ORIANA FERNANDEZ', // MAYÚSCULAS (como viene del API)
            'InvoiceNumber' => 'a7Myen', // Invoice Number original
            'fiscal_document_number' => '0000003636', // Documento fiscal
            'intuit_invoice_id' => '3733',
            'origin' => 'quickbooks',
            'Net_due' => 69.55, // $65.00 + $4.55 ITBMS
            'Subtotal' => 65.00,
            'Date' => '2025-09-30',
            'created_at' => now()->subMinutes(15), // Procesada hace 15 minutos
            'updated_at' => now()->subMinutes(15)
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        echo "   📝 Factura original creada: ID {$originalSale->ID}\n";
        echo "      Cliente: 'ORIANA FERNANDEZ' (MAYÚSCULAS)\n";
        echo "      Monto: $69.55 (con ITBMS)\n";
        echo "      Invoice: 'a7Myen'\n";
        echo "      Fiscal: '0000003636'\n\n";

        // Simular la factura DUPLICADA (Webhook) - ID 3817
        $webhookData = [
            'Invoice' => [
                'Id' => '3817',
                'DocNumber' => 'FE0000003636', // Contiene el documento fiscal "3636"
                'CustomerRef' => [
                    'name' => 'Oriana Fernandez', // Mixto mayúsculas/minúsculas
                    'value' => '1170'
                ],
                'TotalAmt' => 65.00 // Sin ITBMS
            ]
        ];

        echo "   🔄 Probando factura webhook entrante:\n";
        echo "      Cliente: 'Oriana Fernandez' (mixto case)\n";
        echo "      Monto: $65.00 (sin ITBMS)\n";
        echo "      DocNumber: 'FE0000003636' (contiene '3636')\n";
        echo "      QB ID: '3817'\n\n";

        try {
            $this->invokeValidation($webhookData);
            echo "   ❌ FALLO CRÍTICO: Sistema NO detectó la duplicación\n";
            echo "      ⚠️  Esto permitiría crear factura duplicada ID 3817\n";
            echo "      ⚠️  Cliente existente: 'ORIANA FERNANDEZ' vs 'Oriana Fernandez'\n";
            echo "      ⚠️  Documento: '0000003636' vs 'FE0000003636' (contiene '3636')\n";
            echo "      ⚠️  Diferencia de monto: 6.5% (dentro tolerancia)\n\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "   ✅ ÉXITO: Duplicación detectada correctamente\n";
                echo "      🛡️  Sistema bloqueó creación de factura ID 3817\n";
                echo "      🔍 Detección: 'ORIANA FERNANDEZ' ≈ 'Oriana Fernandez' (100% similitud)\n";
                echo "      💰 Montos: $69.55 vs $65.00 (6.5% diferencia, <15% tolerancia)\n";
                echo "      📄 Documentos: '0000003636' parcialmente en 'FE0000003636'\n";
                echo "      📋 Mensaje detallado: " . substr($e->getMessage(), 0, 200) . "...\n\n";
            } else {
                echo "   ❌ FALLO: Excepción incorrecta - " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function testScenario9_CaseInsensitiveExactValidation()
    {
        echo "🔍 Escenario 9: Validación Exacta Case-Insensitive\n";

        // Crear factura base con nombre en MAYÚSCULAS
        DB::connection()->useDatabase($this->organization->database);

        SalesHeaderImp::create([
            'CustomerName' => 'MARIA GONZALEZ', // MAYÚSCULAS COMPLETAS
            'InvoiceNumber' => 'TEST_EXACT_CASE_001',
            'fiscal_document_number' => 'FIS-CASE-001',
            'intuit_invoice_id' => '9001',
            'origin' => 'quickbooks',
            'Net_due' => 150.00,
            'Date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        echo "   📝 Factura base creada:\n";
        echo "      Cliente: 'MARIA GONZALEZ' (MAYÚSCULAS)\n";
        echo "      DocNumber: 'TEST_EXACT_CASE_001'\n";
        echo "      Fiscal: 'FIS-CASE-001'\n\n";

        // CASO 1: Probar con exactamente el mismo nombre en minúsculas
        echo "   🔄 CASO 1: Cliente en minúsculas + mismo documento\n";
        $caseTest1 = [
            'Invoice' => [
                'Id' => '9002',
                'DocNumber' => 'TEST_EXACT_CASE_001', // MISMO documento
                'CustomerRef' => [
                    'name' => 'maria gonzalez', // minúsculas
                    'value' => '800'
                ],
                'TotalAmt' => 150.00
            ]
        ];

        try {
            $this->invokeValidation($caseTest1);
            echo "      ❌ FALLO: No detectó duplicado con diferencia de case\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "      ✅ ÉXITO: Detectó duplicado case-insensitive\n";
                echo "         'MARIA GONZALEZ' = 'maria gonzalez' con mismo documento\n";
            } else {
                echo "      ❌ FALLO: " . $e->getMessage() . "\n";
            }
        }

        // CASO 2: Probar con nombres mixtos + documento parcial
        echo "   🔄 CASO 2: Cliente mixto + documento parcial\n";
        $caseTest2 = [
            'Invoice' => [
                'Id' => '9003',
                'DocNumber' => 'CASE-001', // Documento parcial (existe en 'FIS-CASE-001')
                'CustomerRef' => [
                    'name' => 'Maria Gonzalez', // Mixto mayúsculas/minúsculas
                    'value' => '801'
                ],
                'TotalAmt' => 150.00
            ]
        ];

        try {
            $this->invokeValidation($caseTest2);
            echo "      ❌ FALLO: No detectó duplicado con case mixto + documento parcial\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "      ✅ ÉXITO: Detectó duplicado case-insensitive + LIKE\n";
                echo "         'MARIA GONZALEZ' = 'Maria Gonzalez' + 'CASE-001' en 'FIS-CASE-001'\n";
            } else {
                echo "      ❌ FALLO: " . $e->getMessage() . "\n";
            }
        }

        // CASO 3: Probar caso que NO debería detectar (cliente diferente)
        echo "   🔄 CASO 3: Cliente completamente diferente (control)\n";
        $caseTest3 = [
            'Invoice' => [
                'Id' => '9004',
                'DocNumber' => 'TEST_DIFFERENT_001',
                'CustomerRef' => [
                    'name' => 'Juan Perez', // Cliente completamente diferente
                    'value' => '802'
                ],
                'TotalAmt' => 150.00
            ]
        ];

        try {
            $this->invokeValidation($caseTest3);
            echo "      ✅ ÉXITO: Cliente diferente NO bloqueado\n";
            echo "         'MARIA GONZALEZ' ≠ 'Juan Perez' - Validación correcta\n\n";
        } catch (\Exception $e) {
            echo "      ❌ FALLO: Bloqueó cliente diferente - " . $e->getMessage() . "\n\n";
        }
    }

    public function testScenario10_QuickBooksPrefixDetection()
    {
        echo "🔍 Escenario 10: Detección de Prefijos QuickBooks (FE/NC)\n";

        // Crear factura base sin prefijo en fiscal_document_number (como está en BD)
        DB::connection()->useDatabase($this->organization->database);

        SalesHeaderImp::create([
            'CustomerName' => 'EMPRESA TESTING SAC',
            'InvoiceNumber' => 'TEST_PREFIX_001',
            'fiscal_document_number' => '0000003670', // SIN prefijo (como viene en BD)
            'intuit_invoice_id' => '9100',
            'origin' => 'quickbooks',
            'Net_due' => 275.50,
            'Date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        echo "   📝 Factura base creada:\n";
        echo "      Cliente: 'EMPRESA TESTING SAC'\n";
        echo "      Fiscal Number: '0000003670' (SIN prefijo)\n";
        echo "      Invoice: 'TEST_PREFIX_001'\n\n";

        // CASO 1: QuickBooks envía con prefijo FE
        echo "   🔄 CASO 1: DocNumber con prefijo FE\n";
        $prefixTestFE = [
            'Invoice' => [
                'Id' => '9101',
                'DocNumber' => 'FE0000003670', // CON prefijo FE
                'CustomerRef' => [
                    'name' => 'EMPRESA TESTING SAC', // Mismo cliente
                    'value' => '900'
                ],
                'TotalAmt' => 275.50
            ]
        ];

        try {
            $this->invokeValidation($prefixTestFE);
            echo "      ❌ FALLO: No detectó duplicado con prefijo FE\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "      ✅ ÉXITO: Detectó duplicado quitando prefijo FE\n";
                echo "         'FE0000003670' normalizado a '0000003670' encontrado en BD\n";
            } else {
                echo "      ❌ FALLO: " . $e->getMessage() . "\n";
            }
        }

        // CASO 2: QuickBooks envía con prefijo NC (Nota de Crédito)
        echo "   🔄 CASO 2: DocNumber con prefijo NC\n";
        $prefixTestNC = [
            'Invoice' => [
                'Id' => '9102',
                'DocNumber' => 'NC0000003670', // CON prefijo NC
                'CustomerRef' => [
                    'name' => 'EMPRESA TESTING SAC', // Mismo cliente
                    'value' => '901'
                ],
                'TotalAmt' => 275.50
            ]
        ];

        try {
            $this->invokeValidation($prefixTestNC);
            echo "      ❌ FALLO: No detectó duplicado con prefijo NC\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "      ✅ ÉXITO: Detectó duplicado quitando prefijo NC\n";
                echo "         'NC0000003670' normalizado a '0000003670' encontrado en BD\n";
            } else {
                echo "      ❌ FALLO: " . $e->getMessage() . "\n";
            }
        }

        // CASO 3: DocNumber sin prefijo (debería funcionar igual)
        echo "   🔄 CASO 3: DocNumber sin prefijo (control)\n";
        $noPrefixTest = [
            'Invoice' => [
                'Id' => '9103',
                'DocNumber' => '0000003670', // SIN prefijo
                'CustomerRef' => [
                    'name' => 'EMPRESA TESTING SAC',
                    'value' => '902'
                ],
                'TotalAmt' => 275.50
            ]
        ];

        try {
            $this->invokeValidation($noPrefixTest);
            echo "      ❌ FALLO: No detectó duplicado exacto sin prefijo\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "      ✅ ÉXITO: Detectó duplicado exacto sin prefijo\n";
                echo "         '0000003670' encontrado directamente en BD\n";
            } else {
                echo "      ❌ FALLO: " . $e->getMessage() . "\n";
            }
        }

        // CASO 4: Casos reales del usuario FE0000003636 vs 0000003636
        echo "   🔄 CASO 4: Caso real usuario - FE0000003636\n";

        // Crear la factura real del usuario
        DB::connection()->useDatabase($this->organization->database);

        SalesHeaderImp::create([
            'CustomerName' => 'ORIANA FERNANDEZ',
            'InvoiceNumber' => 'a7Myen',
            'fiscal_document_number' => '0000003636', // Como está en BD (sin prefijo)
            'intuit_invoice_id' => '3733',
            'origin' => 'quickbooks',
            'Net_due' => 69.55,
            'Date' => '2025-09-30',
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20)
        ]);

        DB::connection()->useDatabase(env('DB_DATABASE'));

        // QuickBooks webhook envía con prefijo FE
        $realCaseTest = [
            'Invoice' => [
                'Id' => '3817',
                'DocNumber' => 'FE0000003636', // Con prefijo FE como viene de QB
                'CustomerRef' => [
                    'name' => 'Oriana Fernandez',
                    'value' => '1170'
                ],
                'TotalAmt' => 65.00
            ]
        ];

        try {
            $this->invokeValidation($realCaseTest);
            echo "      ❌ FALLO CRÍTICO: No detectó el caso real del usuario\n";
            echo "         QB: 'FE0000003636' vs BD: '0000003636'\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'DUPLICACIÓN QUICKBOOKS DETECTADA') !== false) {
                echo "      ✅ ÉXITO CRÍTICO: Detectó el caso real del usuario\n";
                echo "         QB 'FE0000003636' normalizado = '0000003636' encontrado en BD\n";
                echo "         Cliente 'ORIANA FERNANDEZ' ≈ 'Oriana Fernandez'\n";
                echo "         Diferencia monto: " . abs(69.55 - 65.00) . " (6.5% < 15%)\n";
            } else {
                echo "      ❌ FALLO: " . $e->getMessage() . "\n";
            }
        }

        echo "\n   ✅ Detección de prefijos QuickBooks completada\n\n";
    }

    private function invokeValidation($requestData)
    {
        // Usar reflection para acceder al método protected
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateQuickBooksInvoiceDuplication');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $this->organization, $requestData);
    }
}// Ejecutar las pruebas
echo "🚀 INICIANDO PRUEBAS DE PREVENCIÓN DE DUPLICACIÓN QUICKBOOKS\n";
echo "Timestamp: " . now()->format('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$test = new QuickBooksDuplicationTest();
$test->run();

echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 PRUEBAS FINALIZADAS\n";
