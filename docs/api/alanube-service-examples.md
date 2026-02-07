# Alanube Service - Ejemplos Prácticos

## Ejemplos por Casos de Uso

### 1. E-commerce - Factura de Venta Online (República Dominicana)

```php
<?php

use App\Services\AlanubeService;
use App\Models\Pacconnection;

// Configuración para tienda online
class EcommerceInvoiceExample
{
    public function emitOnlineSaleInvoice($order)
    {
        $alanubeService = new AlanubeService();
        $pacConnection = Pacconnection::where('organization_id', $order->organization_id)
                                    ->where('pac_type', 'alanube')
                                    ->first();

        $documentData = [
            'header' => [
                'numero_comprobante_fiscal' => $this->generateInvoiceNumber('B01'),
                'tipo_documento' => 'FISCAL',
                'fecha_comprobante' => now()->format('Y-m-d'),
                'fecha_vencimiento' => now()->addDays(30)->format('Y-m-d'),
                'moneda' => 'DOP',
                'tipo_cambio' => 1.00,
                'observaciones' => 'Venta realizada através de tienda online',
                'cliente' => [
                    'tipo_identificacion' => $order->customer->id_type, // 1=RNC, 2=Cédula
                    'numero_identificacion' => $order->customer->tax_id,
                    'razon_social' => $order->customer->business_name,
                    'nombre_comercial' => $order->customer->trade_name,
                    'direccion' => $order->billing_address->street,
                    'municipio' => $order->billing_address->city,
                    'provincia' => $order->billing_address->state,
                    'pais' => 'DO',
                    'telefono' => $order->customer->phone,
                    'email' => $order->customer->email
                ]
            ],
            'items' => $this->formatOrderItems($order->items),
            'totales' => [
                'subtotal' => $order->subtotal,
                'descuento_total' => $order->discount_amount,
                'impuesto_total' => $order->tax_amount,
                'total' => $order->total_amount
            ],
            'informacion_adicional' => [
                'numero_orden' => $order->order_number,
                'metodo_pago' => $order->payment_method,
                'canal_venta' => 'ONLINE',
                'vendedor' => $order->sales_rep->name ?? 'Sistema Automatizado'
            ]
        ];

        return $alanubeService->emitDocument($documentData, $pacConnection);
    }

    private function formatOrderItems($items)
    {
        return $items->map(function ($item) {
            return [
                'codigo_producto' => $item->sku,
                'descripcion' => $item->product_name,
                'cantidad' => $item->quantity,
                'precio_unitario' => $item->unit_price,
                'descuento' => $item->discount_amount,
                'tipo_impuesto' => 'ITBIS',
                'tasa_impuesto' => 18.00,
                'categoria' => $item->product_category,
                'unidad_medida' => $item->unit_of_measure ?? 'UND'
            ];
        })->toArray();
    }
}
```

### 2. Servicios Profesionales - Factura de Consultoría (Panamá)

```php
<?php

use App\Services\AlanubeService;

class ConsultingInvoiceExample
{
    public function emitConsultingInvoice($project, $client)
    {
        $alanubeService = new AlanubeService();
        $pacConnection = $this->getPanamaConnection();

        $documentData = [
            'header' => [
                'numero_documento' => $this->generatePanamaInvoiceNumber(),
                'tipo_documento' => 'FACTURA',
                'fecha_emision' => now()->toISOString(),
                'fecha_vencimiento' => now()->addDays(15)->toISOString(),
                'moneda' => 'PAB',
                'actividad_economica' => '7020', // Consultoría en gestión empresarial
                'cliente' => [
                    'tipo_identificacion' => 'RUC',
                    'numero_identificacion' => $client->ruc,
                    'dv' => $client->verification_digit,
                    'razon_social' => $client->business_name,
                    'nombre_comercial' => $client->trade_name,
                    'direccion' => [
                        'provincia' => $client->address->province,
                        'distrito' => $client->address->district,
                        'corregimiento' => $client->address->corregimiento,
                        'direccion_detallada' => $client->address->detailed_address,
                        'codigo_postal' => $client->address->postal_code
                    ],
                    'telefono' => $client->phone,
                    'email' => $client->email
                ]
            ],
            'items' => [
                [
                    'codigo_producto' => 'CONS-001',
                    'descripcion' => "Consultoría para proyecto: {$project->name}",
                    'cantidad' => $project->hours_worked,
                    'unidad_medida' => 'HORA',
                    'precio_unitario' => $project->hourly_rate,
                    'descuento' => 0.00,
                    'codigo_impuesto' => 'ITBMS',
                    'tasa_impuesto' => 7.00,
                    'informacion_adicional' => [
                        'periodo_servicios' => $project->service_period,
                        'entregables' => $project->deliverables,
                        'metodologia' => $project->methodology
                    ]
                ]
            ],
            'forma_pago' => [
                'tipo' => 'TRANSFERENCIA',
                'plazo' => 15,
                'banco' => $this->getCompanyBankInfo()
            ]
        ];

        return $alanubeService->emitDocument($documentData, $pacConnection);
    }
}
```

### 3. Retail - Factura de Consumo Final (República Dominicana)

```php
<?php

class RetailInvoiceExample
{
    public function emitRetailSale($sale)
    {
        $alanubeService = new AlanubeService();
        $pacConnection = $this->getDominicanConnection();

        $documentData = [
            'header' => [
                'numero_comprobante_fiscal' => $this->generateConsumerInvoiceNumber('B02'),
                'tipo_documento' => 'CONSUMO',
                'fecha_comprobante' => now()->format('Y-m-d'),
                'hora_comprobante' => now()->format('H:i:s'),
                'moneda' => 'DOP',
                'caja' => $sale->register_number,
                'cajero' => $sale->cashier->name,
                'cliente' => [
                    'tipo_identificacion' => 2, // Cédula o consumidor final
                    'numero_identificacion' => $sale->customer->cedula ?? '00000000000',
                    'nombre' => $sale->customer->name ?? 'CONSUMIDOR FINAL',
                    'direccion' => $sale->customer->address ?? 'NO APLICA'
                ]
            ],
            'items' => $sale->items->map(function ($item) {
                return [
                    'codigo_producto' => $item->barcode,
                    'descripcion' => $item->product_name,
                    'cantidad' => $item->quantity,
                    'precio_unitario' => $item->unit_price,
                    'descuento' => $item->discount_amount,
                    'tipo_impuesto' => $item->tax_exempt ? 'EXENTO' : 'ITBIS',
                    'tasa_impuesto' => $item->tax_exempt ? 0.00 : 18.00,
                    'lote' => $item->lot_number,
                    'fecha_vencimiento' => $item->expiry_date
                ];
            })->toArray(),
            'totales' => [
                'subtotal' => $sale->subtotal,
                'descuento_total' => $sale->total_discount,
                'impuesto_total' => $sale->total_tax,
                'total' => $sale->total_amount,
                'efectivo_recibido' => $sale->cash_received,
                'cambio' => $sale->change_amount
            ],
            'forma_pago' => [
                'efectivo' => $sale->cash_amount,
                'tarjeta' => $sale->card_amount,
                'transferencia' => $sale->transfer_amount
            ]
        ];

        return $alanubeService->emitDocument($documentData, $pacConnection);
    }
}
```

### 4. Devoluciones - Nota de Crédito por Garantía

```php
<?php

class RefundCreditNoteExample
{
    public function emitWarrantyRefund($originalInvoice, $refundItems, $reason)
    {
        $alanubeService = new AlanubeService();
        $pacConnection = $this->getConnectionByCountry($originalInvoice->country);

        if ($originalInvoice->country === 'panama') {
            return $this->emitPanamaCreditNote($originalInvoice, $refundItems, $reason);
        } else {
            return $this->emitDominicanCreditNote($originalInvoice, $refundItems, $reason);
        }
    }

    private function emitPanamaCreditNote($originalInvoice, $refundItems, $reason)
    {
        $creditNoteData = [
            'header' => [
                'numero_documento' => $this->generateCreditNoteNumber(),
                'tipo_documento' => 'NOTA_CREDITO',
                'fecha_emision' => now()->toISOString(),
                'documento_referencia' => [
                    'numero_documento' => $originalInvoice->document_number,
                    'fecha_emision' => $originalInvoice->issue_date,
                    'tipo_documento' => 'FACTURA',
                    'uuid' => $originalInvoice->uuid
                ],
                'motivo' => $reason,
                'tipo_operacion' => 'DEVOLUCION',
                'cliente' => [
                    'tipo_identificacion' => $originalInvoice->customer_id_type,
                    'numero_identificacion' => $originalInvoice->customer_tax_id,
                    'razon_social' => $originalInvoice->customer_name
                ]
            ],
            'items' => $refundItems->map(function ($item) {
                return [
                    'codigo_producto' => $item->product_code,
                    'descripcion' => "DEVOLUCIÓN: {$item->description}",
                    'cantidad' => $item->refund_quantity,
                    'precio_unitario' => $item->unit_price,
                    'motivo_devolucion' => $item->refund_reason,
                    'estado_producto' => $item->product_condition, // NUEVO, USADO, DEFECTUOSO
                    'codigo_impuesto' => 'ITBMS',
                    'tasa_impuesto' => 7.00
                ];
            })->toArray(),
            'proceso_devolucion' => [
                'autorizado_por' => auth()->user()->name,
                'fecha_autorizacion' => now()->toISOString(),
                'metodo_reembolso' => 'TRANSFERENCIA_BANCARIA',
                'numero_autorizacion' => $this->generateAuthorizationNumber()
            ]
        ];

        return $this->alanubeService->emitCreditNoteDocument($creditNoteData, $this->pacConnection);
    }
}
```

### 5. Exportación - Factura de Exportación (República Dominicana)

```php
<?php

class ExportInvoiceExample
{
    public function emitExportInvoice($exportOrder)
    {
        $alanubeService = new AlanubeService();
        $pacConnection = $this->getDominicanConnection();

        $documentData = [
            'header' => [
                'numero_comprobante_fiscal' => $this->generateExportInvoiceNumber('B16'),
                'tipo_documento' => 'EXPORTACION',
                'fecha_comprobante' => now()->format('Y-m-d'),
                'moneda' => $exportOrder->currency, // USD, EUR, etc.
                'tipo_cambio' => $exportOrder->exchange_rate,
                'incoterm' => $exportOrder->incoterm, // FOB, CIF, EXW, etc.
                'puerto_embarque' => $exportOrder->port_of_shipment,
                'puerto_destino' => $exportOrder->destination_port,
                'cliente' => [
                    'tipo_identificacion' => 3, // Pasaporte o documento extranjero
                    'numero_identificacion' => $exportOrder->customer->foreign_tax_id,
                    'razon_social' => $exportOrder->customer->business_name,
                    'direccion' => $exportOrder->customer->address,
                    'ciudad' => $exportOrder->customer->city,
                    'pais' => $exportOrder->customer->country_code,
                    'telefono' => $exportOrder->customer->phone,
                    'email' => $exportOrder->customer->email
                ]
            ],
            'items' => $exportOrder->items->map(function ($item) {
                return [
                    'codigo_producto' => $item->sku,
                    'descripcion' => $item->description,
                    'codigo_arancelario' => $item->hs_code,
                    'cantidad' => $item->quantity,
                    'unidad_medida' => $item->unit_of_measure,
                    'peso_unitario' => $item->unit_weight,
                    'precio_unitario' => $item->unit_price_usd,
                    'precio_unitario_dop' => $item->unit_price_dop,
                    'pais_origen' => $item->country_of_origin,
                    'tipo_impuesto' => 'EXENTO', // Exportaciones generalmente exentas
                    'tasa_impuesto' => 0.00
                ];
            })->toArray(),
            'transporte' => [
                'tipo' => $exportOrder->transport_type, // MARITIMO, AEREO, TERRESTRE
                'empresa_transportista' => $exportOrder->carrier_company,
                'numero_contenedor' => $exportOrder->container_number,
                'numero_precinto' => $exportOrder->seal_number,
                'peso_total' => $exportOrder->total_weight,
                'volumen_total' => $exportOrder->total_volume
            ],
            'documentos_aduaneros' => [
                'declaracion_exportacion' => $exportOrder->export_declaration,
                'certificado_origen' => $exportOrder->origin_certificate,
                'lista_empaque' => $exportOrder->packing_list
            ],
            'totales' => [
                'subtotal_usd' => $exportOrder->subtotal_usd,
                'total_usd' => $exportOrder->total_usd,
                'subtotal_dop' => $exportOrder->subtotal_dop,
                'total_dop' => $exportOrder->total_dop
            ]
        ];

        return $alanubeService->emitDocument($documentData, $pacConnection);
    }
}
```

### 6. Manejo de Errores y Reintentos

```php
<?php

class AlanubeErrorHandlingExample
{
    private $maxRetries = 3;
    private $retryDelay = 5; // segundos

    public function emitDocumentWithRetry($documentData, $pacConnection)
    {
        $attempt = 1;
        
        while ($attempt <= $this->maxRetries) {
            try {
                $alanubeService = new AlanubeService();
                
                // Validar configuración antes de intentar
                if (!$alanubeService->validatePacConfiguration($pacConnection)) {
                    throw new Exception('Configuración PAC inválida');
                }

                $response = $alanubeService->emitDocument($documentData, $pacConnection);

                if ($response['success']) {
                    Log::info("Documento emitido exitosamente en intento {$attempt}");
                    return $response;
                }

                // Manejar errores específicos
                $this->handleSpecificErrors($response);
                
            } catch (ConnectException $e) {
                Log::warning("Error de conexión en intento {$attempt}: " . $e->getMessage());
                
                if ($attempt === $this->maxRetries) {
                    throw new Exception('No se pudo conectar con Alanube después de ' . $this->maxRetries . ' intentos');
                }
                
            } catch (ClientException $e) {
                $statusCode = $e->getResponse()->getStatusCode();
                
                if (in_array($statusCode, [400, 401, 403])) {
                    // Errores que no se deben reintentar
                    throw new Exception('Error de cliente Alanube: ' . $e->getMessage());
                }
                
                Log::warning("Error HTTP {$statusCode} en intento {$attempt}");
                
            } catch (Exception $e) {
                Log::error("Error general en intento {$attempt}: " . $e->getMessage());
                
                if ($attempt === $this->maxRetries) {
                    throw $e;
                }
            }

            $attempt++;
            if ($attempt <= $this->maxRetries) {
                sleep($this->retryDelay);
            }
        }

        throw new Exception('Se agotaron todos los intentos de emisión');
    }

    private function handleSpecificErrors($response)
    {
        $errorMessage = $response['message'] ?? '';
        
        // Errores comunes y sus soluciones
        if (strpos($errorMessage, 'token') !== false) {
            Log::error('Error de token - Verificar configuración PAC');
            throw new Exception('Token PAC inválido o expirado');
        }
        
        if (strpos($errorMessage, 'validation') !== false) {
            Log::error('Error de validación de datos', $response['errors'] ?? []);
            throw new Exception('Datos del documento inválidos: ' . $errorMessage);
        }
        
        if (strpos($errorMessage, 'duplicate') !== false) {
            Log::error('Documento duplicado detectado');
            throw new Exception('El número de documento ya existe');
        }
    }
}
```

### 7. Emisión Masiva de Documentos

```php
<?php

use Illuminate\Support\Collection;

class BulkInvoiceExample
{
    public function emitBulkInvoices(Collection $orders)
    {
        $results = collect();
        $alanubeService = new AlanubeService();
        
        // Agrupar por conexión PAC (país)
        $ordersByConnection = $orders->groupBy('pac_connection_id');
        
        foreach ($ordersByConnection as $connectionId => $connectionOrders) {
            $pacConnection = Pacconnection::find($connectionId);
            $country = $alanubeService->detectCountryFromPacConnection($pacConnection);
            
            Log::info("Procesando {$connectionOrders->count()} documentos para {$country}");
            
            foreach ($connectionOrders as $order) {
                try {
                    $documentData = $this->formatOrderData($order, $country);
                    $response = $alanubeService->emitDocument($documentData, $pacConnection);
                    
                    $results->push([
                        'order_id' => $order->id,
                        'success' => $response['success'],
                        'document_id' => $response['data']['id'] ?? null,
                        'message' => $response['message'] ?? 'Emitido correctamente'
                    ]);
                    
                    // Actualizar estado de la orden
                    $order->update([
                        'invoice_status' => $response['success'] ? 'emitted' : 'failed',
                        'alanube_document_id' => $response['data']['id'] ?? null
                    ]);
                    
                } catch (Exception $e) {
                    Log::error("Error emitiendo documento para orden {$order->id}: " . $e->getMessage());
                    
                    $results->push([
                        'order_id' => $order->id,
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    
                    $order->update(['invoice_status' => 'failed']);
                }
                
                // Pausa para evitar saturar la API
                usleep(500000); // 0.5 segundos
            }
        }
        
        return $results;
    }

    private function formatOrderData($order, $country)
    {
        if ($country === 'panama') {
            return $this->formatPanamaOrder($order);
        } else {
            return $this->formatDominicanOrder($order);
        }
    }
}
```

### 8. Testing y Validación

```php
<?php

class AlanubeTestingExample
{
    public function testDualCountrySupport()
    {
        $alanubeService = new AlanubeService();
        
        // Test República Dominicana
        $domConnection = $this->createTestConnection('https://sandbox.alanube.co/dom/v1');
        $domCountry = $alanubeService->detectCountryFromPacConnection($domConnection);
        assert($domCountry === 'dominican_republic');
        
        // Test Panamá
        $panConnection = $this->createTestConnection('https://sandbox-api.alanube.co/pan/v1');
        $panCountry = $alanubeService->detectCountryFromPacConnection($panConnection);
        assert($panCountry === 'panama');
        
        // Test construcción de URLs
        $domUrl = $alanubeService->buildApiUrl($domConnection, 'fiscal-invoices');
        assert($domUrl === 'https://sandbox.alanube.co/dom/v1/fiscal-invoices');
        
        $panUrl = $alanubeService->buildApiUrl($panConnection, 'credit-notes');
        assert($panUrl === 'https://sandbox-api.alanube.co/pan/v1/credit-notes');
        
        echo "Todos los tests de dual-country pasaron correctamente\n";
    }

    public function testDocumentValidation()
    {
        $alanubeService = new AlanubeService();
        
        // Test validación de datos mínimos
        $invalidData = ['header' => []]; // Datos incompletos
        
        try {
            $response = $alanubeService->emitDocument($invalidData, $this->getTestConnection());
            assert(false, 'Debería haber fallado con datos inválidos');
        } catch (Exception $e) {
            echo "Validación de datos funcionando correctamente\n";
        }
    }
}
```

---

*Estos ejemplos cubren casos de uso comunes en DocuCenter y demuestran la flexibilidad del servicio Alanube para manejar diferentes tipos de documentos en ambos países.*
