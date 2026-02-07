# QuickBooks Payment Methods Integration

## Descripción General

El sistema `determinePaymentMethods()` en `QuickBooksOnlineService` analiza el balance de facturas de QuickBooks para crear registros de pago apropiados en DocuCenter.

## Lógica de Determinación de Pagos

### **Escenario 1: Factura Sin Pagar**
**Condición**: `Balance == TotalAmount`
- **Resultado**: 1 pago tipo "Store Credit" 
- **Interpretación**: Factura pendiente de pago
- **Ejemplo**: Factura $107, Balance $107 → Store Credit $107

### **Escenario 2: Factura Totalmente Pagada**
**Condición**: `Balance == 0`
- **Resultado**: 1 pago tipo "CREDIT_CARD"
- **Interpretación**: Factura completamente pagada
- **Ejemplo**: Factura $107, Balance $0 → CREDIT_CARD $107

### **Escenario 3: Pago Parcial**
**Condición**: `0 < Balance < TotalAmount`
- **Resultado**: 2 pagos (CREDIT_CARD + Store Credit)
- **Interpretación**: Parte pagada, parte pendiente
- **Ejemplo**: Factura $107, Balance $30 → CREDIT_CARD $77 + Store Credit $30

## Ejemplo Real: Factura FE0000003143

### Datos de QuickBooks:
```json
{
    "DocNumber": "FE0000003143",
    "TotalAmt": 107,
    "Balance": 107,
    "TxnTaxDetail": {"TotalTax": 7},
    "CustomerRef": {
        "name": "ANUAR MATA",
        "TIPO_RECEPTOR": "02"
    }
}
```

### Análisis de Pagos:
- **Total**: $107.00
- **Balance**: $107.00 (sin pagar)
- **Escenario**: 1 (Sin pagar)
- **Resultado**: 1 pago Store Credit por $107.00

### Logging Generado:
```
[INFO] QuickBooks determinePaymentMethods - Analyzing payment structure
    organization_id: 123
    total_amount: 107
    balance: 107
    paid_amount: 0
    payment_status: unpaid

[INFO] QuickBooks Payment Method - Unpaid invoice
    scenario: 1
    payment_count: 1
    method: Store Credit
    amount: 107
```

## Flujo de Sincronización

### 1. Creación Inicial
```
QB: Factura FE0000003143 creada (Balance = $107)
↓
DocuCenter: Importa factura
↓
Sistema: Crea 1 pago "Store Credit" $107
```

### 2. Pago Parcial en QB
```
QB: Cliente paga $50 (Balance = $57)
↓
DocuCenter: Próxima sincronización detecta cambio
↓
Sistema: Ajusta a 2 pagos (CREDIT_CARD $50 + Store Credit $57)
```

### 3. Pago Completo en QB
```
QB: Cliente paga resto (Balance = $0)
↓
DocuCenter: Próxima sincronización detecta cambio
↓
Sistema: Ajusta a 1 pago "CREDIT_CARD" $107
```

## Implementación Técnica

### Método Principal
```php
protected function determinePaymentMethods(float $totalAmount, float $balance): array
{
    // Análisis y logging mejorado
    $paidAmount = $totalAmount - $balance;
    
    Log::info('QuickBooks determinePaymentMethods - Analyzing payment structure', [
        'total_amount' => $totalAmount,
        'balance' => $balance,
        'paid_amount' => $paidAmount,
        'payment_status' => $balance == $totalAmount ? 'unpaid' : 
                           ($balance == 0 ? 'fully_paid' : 'partially_paid')
    ]);
    
    // Lógica de determinación...
}
```

### Creación de Registros
```php
protected function createPaymentRecords(
    array $paymentMethods,
    string $invoiceNumber,
    CustomersImp $customer,
    $company,
    SalesHeaderImp $salesHeader,
    string $date
): array
```

## Testing

### Scripts Disponibles:
- `docs/testing/test-real-invoice-fe3143.php` - Test con factura real
- `docs/testing/test-quickbooks-real-data.php` - Simulación completa
- `docs/testing/analyze-payment-methods.php` - Análisis detallado

### Ejecutar Tests:
```bash
cd /home/weirdolabs/code/docucenter

# Test factura específica
php docs/testing/test-real-invoice-fe3143.php

# Análisis general
php docs/testing/analyze-payment-methods.php
```

## Ventajas del Sistema

### ✅ Simplicidad
- Lógica clara basada en balance de QB
- Manejo automático de todos los escenarios

### ✅ Flexibilidad
- Se adapta a cambios en el estado de pago
- Soporte para pagos parciales

### ✅ Trazabilidad
- Logging detallado para debugging
- Identificación clara de escenarios

### ✅ Consistencia
- Suma de pagos siempre = total factura
- Estados coherentes con QuickBooks

## Limitaciones Actuales

### ⚠️ Asunciones Simplificadas
- Todo lo pagado se asume como "CREDIT_CARD"
- No distingue métodos de pago reales (efectivo, transferencia, etc.)

### ⚠️ Información Limitada
- QuickBooks puede tener más detalles sobre métodos de pago
- No se consultan datos de payments reales de la API

## Mejoras Futuras

### 1. Integración con API de Payments
- Consultar métodos de pago reales desde QB
- Mapear métodos específicos (Cash, Check, BankTransfer, etc.)

### 2. Validación Cruzada
- Verificar consistencia con datos de payments en QB
- Alertas en caso de discrepancias

### 3. Configuración Personalizable
- Permitir configurar métodos de pago por defecto
- Reglas específicas por organización

## Referencias

- **QuickBooksOnlineService**: Servicio principal
- **UpdateIntuitOrdersJob**: Job de sincronización de QB a DocuCenter
- **CreateSaleQuickBooksJob**: Job de creación desde DocuCenter a QB
- **Documentación QB API**: https://developer.intuit.com/app/developer/qbo/docs/api/accounting/payments
