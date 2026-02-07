# QuickBooks Sparse Update Implementation

## Problema Actual
El `UpdateQuickBooksInvoicesJob` actualiza TODA la factura cuando solo queremos actualizar la nota (`PrivateNote`), lo que puede sobrescribir información modificada en QuickBooks.

## Solución: Sparse Update

### 1. Modificar el método `prepareInvoiceDataForQuickBooks()`

Agregar un parámetro para indicar si es una actualización parcial:

```php
/**
 * Prepara datos de factura para QuickBooks con soporte para sparse update
 *
 * @param \App\Models\SalesHeaderImp $invoice
 * @param array $settings
 * @param bool $sparseUpdate Si true, solo envía campos específicos
 * @param array $fieldsToUpdate Campos específicos a actualizar
 * @return array
 */
protected function prepareInvoiceDataForQuickBooks($invoice, $settings, $sparseUpdate = false, $fieldsToUpdate = [])
{
    $syncToken = $this->getSyncTokenForInvoice($invoice, $settings);
    $cufe = $this->extractCufeFromInvoice($invoice);

    // Datos base requeridos para cualquier actualización
    $baseData = [
        'connection_id' => array_get($settings, 'App'),
        'Id' => $invoice->intuit_invoice_id,
        'SyncToken' => $syncToken,
        'sparse' => $sparseUpdate ? 'true' : 'false'
    ];

    // Si es sparse update, solo incluir campos específicos
    if ($sparseUpdate) {
        if (in_array('PrivateNote', $fieldsToUpdate)) {
            $baseData['PrivateNote'] = "CUFE: {$cufe}\nComprobantes: https://dgi-fep.mef.gob.pa/Consultas/FacturasPorCUFE/{$cufe}\nActualizado desde DocuCenter";
        }

        if (in_array('CustomField', $fieldsToUpdate)) {
            // Agregar campos personalizados si es necesario
            $baseData['CustomField'] = [
                [
                    'DefinitionId' => '1', // ID del campo personalizado
                    'Name' => 'CUFE',
                    'StringValue' => $cufe
                ]
            ];
        }

        return $baseData;
    }

    // Update completo (comportamiento actual)
    // ... resto del código actual
}
```

### 2. Crear método especializado para actualizar solo nota

```php
/**
 * Actualiza solo la PrivateNote de la factura en QuickBooks
 *
 * @param \App\Models\SalesHeaderImp $invoice
 * @return void
 */
protected function updateInvoiceNoteOnly($invoice)
{
    try {
        Log::info("UpdateQuickBooksInvoicesJob: Actualizando solo nota de factura", [
            'invoice_id' => $invoice->ID,
            'invoice_number' => $invoice->InvoiceNumber,
            'intuit_invoice_id' => $invoice->intuit_invoice_id
        ]);

        $email = env('ACI_EMAIL');
        $password = env('ACI_PASSWORD');
        $settings = $this->connectionModel->settings;

        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?: [];
        }

        // Preparar datos solo para la nota (sparse update)
        $invoiceData = $this->prepareInvoiceDataForQuickBooks(
            $invoice, 
            $settings, 
            true, // sparse update
            ['PrivateNote'] // solo actualizar la nota
        );

        Log::info("UpdateQuickBooksInvoicesJob: Datos sparse para actualización", [
            'invoice_id' => $invoice->ID,
            'sparse_data' => $invoiceData
        ]);

        // Llamar API con sparse update
        $response = $this->callQuickBooksUpdateAPI($invoice->intuit_invoice_id, $invoiceData, $email, $password);

        if ($response['success']) {
            $this->handleSuccessfulUpdate($invoice, $response['data']);
            $this->processCounters['updated']++;
            
            Log::info("UpdateQuickBooksInvoicesJob: Nota actualizada exitosamente", [
                'invoice_id' => $invoice->ID,
                'intuit_invoice_id' => $invoice->intuit_invoice_id
            ]);
        } else {
            $this->handleFailedUpdate($invoice, $response);
            $this->processCounters['failed']++;
        }

    } catch (\Exception $e) {
        Log::error("UpdateQuickBooksInvoicesJob: Error actualizando nota", [
            'invoice_id' => $invoice->ID,
            'error' => $e->getMessage()
        ]);
        $this->processCounters['failed']++;
    }
}
```

### 3. Modificar el endpoint del helper

El helper en Cloud Functions debe soportar el parámetro `sparse`:

```javascript
// En el endpoint /aciv2/update_invoice_quickbooks/{invoiceId}
app.put('/aciv2/update_invoice_quickbooks/:invoiceId', async (req, res) => {
  try {
    const { invoiceId } = req.params;
    const invoiceData = req.body;
    
    // Verificar si es sparse update
    const isSparse = invoiceData.sparse === 'true';
    
    // Construir URL con parámetro sparse
    const qboApiUrl = `${baseUrl}/v3/company/${realmId}/invoice?operation=update${isSparse ? '&sparse=true' : ''}`;
    
    // Enviar a QuickBooks API
    const response = await intuitOAuth.makeApiCall({
      url: qboApiUrl,
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(invoiceData)
    });
    
    res.json(response);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});
```

### 4. Uso en el Job

```php
public function handle()
{
    // ... código existente ...

    foreach ($invoices as $invoice) {
        // Verificar si solo necesitamos actualizar la nota
        if ($this->shouldUpdateNoteOnly($invoice)) {
            $this->updateInvoiceNoteOnly($invoice);
        } else {
            $this->updateInvoiceInQuickBooks($invoice); // Update completo
        }
    }
}

/**
 * Determina si solo se debe actualizar la nota
 */
protected function shouldUpdateNoteOnly($invoice)
{
    // Lógica para determinar si solo actualizar nota
    // Por ejemplo, si hay un flag específico o si ciertos campos no han cambiado
    return !empty($invoice->InvoiceNote) && 
           $invoice->intuit_sync_status === 'note_update_pending';
}
```

## Beneficios del Sparse Update

1. **Preserva cambios**: No sobrescribe información modificada en QuickBooks
2. **Más eficiente**: Transfiere menos datos
3. **Menor riesgo**: Reduce posibilidad de errores por campos faltantes
4. **Mejor performance**: Procesamiento más rápido

## Campos que se pueden actualizar individualmente

- `PrivateNote` - Nota privada
- `CustomerMemo` - Memo para el cliente
- `CustomField` - Campos personalizados
- `DocNumber` - Número de documento
- `TxnDate` - Fecha de transacción

## Consideraciones

- Siempre incluir `Id` y `SyncToken`
- El parámetro `sparse=true` debe ir en la query string
- Validar que el campo a actualizar existe en QB
- Manejar errores específicos de sparse update
