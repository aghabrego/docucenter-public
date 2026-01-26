# Payment Lookup Integration - QuickBooks

## Descripción General

Se implementó funcionalidad de búsqueda de pagos existentes en QuickBooks para prevenir duplicaciones durante el proceso de sincronización en `UpdateIntuitOrdersJob`.

## **FUNCIONALIDAD REMOVIDA**

Esta funcionalidad de payment lookup ha sido **removida del sistema** para simplificar el flujo de sincronización. 

### Cambios Realizados:

### 1. ~~Método getPaymentByRefNum() eliminado~~

**Estado**: **REMOVIDO** de `app/Traits/UpdateIntuitOrdersTrait.php`

### 2. Integración simplificada en stepRegisterPayments()

**Ubicación**: `app/Jobs/Intuit/UpdateIntuitOrdersJob.php`

```php
// Flujo simplificado - Solo getSyncTokenPaymentQB
foreach ($payments as $payment) {
    $syncTokenPayment = $this->getSyncTokenPaymentQB($email, $password, [
        'payment_id' => $payment->UniqueReceiptID,
        'connection_id' => array_get($settings, 'App'),
        'doc_number' => $invoiceNumber,
    ]);
    if ($syncTokenPayment === false) {
        $syncTokenPayment = '0';
    }
    
    // Continuar con registerPaymentsQB...
}
```

**Flujo de Trabajo Simplificado**:
1. Procesar pagos directamente sin consulta previa
2. Obtener SyncToken usando getSyncTokenPaymentQB() tradicional
3. Continuar con el flujo normal de sincronización

## Parámetros API

### Endpoint: `/aciv2/get_payment_by_ref_num`

**Parámetros Requeridos**:
- `connection_id`: ID de la conexión QuickBooks
- `payment_ref_num`: Número de referencia del pago (invoice_number)

**Respuesta**:
- `array`: Datos de pagos encontrados con estructura completa
- `false`: No se encontraron pagos o error

**Estructura de Respuesta Exitosa**:
```json
{
    "success": true,
    "message": "Pago encontrado exitosamente",
    "data": {
        "id": "311",
        "paymentRefNum": "FC0000000068",
        "totalAmt": 148.6,
        "txnDate": "2024-03-15",
        "customerId": "2",
        "customerName": "APCON CONSULTING INC",
        "syncToken": "0",
        "fullPayment": {
            "Id": "311",
            "SyncToken": "0",
            "PaymentRefNum": "FC0000000068",
            "TotalAmt": 148.6,
            "MetaData": {
                "CreateTime": "2025-09-09T19:22:51-07:00",
                "LastUpdatedTime": "2025-09-09T19:22:51-07:00"
            }
        }
    }
}
```

## Testing

### Scripts de Prueba

#### 1. Test Principal: `docs/testing/test-get-payment-by-ref-num.php`
**Funcionalidades**:
- Validación de conexión QuickBooks
- Verificación de credenciales ACI
- Simulación para desarrollo sin credenciales
- Pruebas reales con facturas de ejemplo
- Validación de estructura de respuesta

#### 2. Test SyncToken: `docs/testing/test-synctoken-extraction.php`
**Funcionalidades**:
- Validación de extracción de SyncToken
- Casos de prueba para diferentes estructuras de respuesta
- Simulación de respuestas reales del API
- Validación de lógica de fallback

**Ejecución**:
```bash
cd /home/weirdolabs/code/docucenter

# Test principal de payment lookup
php docs/testing/test-get-payment-by-ref-num.php

# Test específico de extracción SyncToken
php docs/testing/test-synctoken-extraction.php
```

### Casos de Prueba

1. **Factura con Pagos Existentes**
   - Número: FE0000003143
   - Resultado Esperado: Array con datos de pagos

2. **Factura sin Pagos**
   - Número: FE0000999999
   - Resultado Esperado: false

## Logging y Debugging

### Logs Generados

```
[INFO] Consultando pago existente: {"connection_id":"123","payment_ref_num":"FE0000003143"}
[INFO] Pagos encontrados para ref_num: FE0000003143: {"payment_count":2}
```

### Identificación de Problemas

- **Parámetros faltantes**: Log nivel WARNING
- **No pagos encontrados**: Log nivel INFO
- **Pagos encontrados**: Log nivel INFO con conteo

## Razones para la Remoción

### 1. Simplificación del Flujo
- Elimina complejidad innecesaria en el proceso de sincronización
- Reduce puntos de fallo potenciales
- Mantiene el flujo tradicional bien probado

### 2. Performance Consistente
- Evita llamadas API adicionales que pueden fallar
- Flujo más predecible y confiable
- Menos dependencias externas

### 3. Mantenimiento Simplificado
- Menos código para mantener y debuggear
- Flujo más directo y fácil de entender
- Reduce superficie de ataque para errores

## Consideraciones de Producción

### Performance
- Llamada API adicional por factura procesada
- Impacto mínimo dado el throughput actual (400-500 jobs/hora)
- Logging configurable para reducir overhead

### Monitoreo
- Revisar logs para patrones de pagos duplicados
- Análizar efectividad de prevención
- Monitorear tiempo de respuesta API

### Mantenimiento
- Sincronizado con updates del trait principal
- Compatible con estructura ACI Cloud existente
- No requiere cambios en base de datos

## Estado del Sistema

### Flujo Actual (Simplificado)
1. **stepRegisterPayments()** procesa pagos directamente
2. **getSyncTokenPaymentQB()** obtiene SyncToken usando método tradicional
3. **registerPaymentsQB()** registra el pago en QuickBooks
4. Flujo continúa normalmente

### Impacto de la Remoción
- **Menos complejidad**: Código más simple y directo
- **Mayor confiabilidad**: Menos puntos de fallo
- **Mantenimiento reducido**: Menos código para debuggear

## Referencias

- **UpdateIntuitOrdersJob**: Job principal de sincronización QB
- **UpdateIntuitOrdersTrait**: Trait con métodos de comunicación API
- **ACI Cloud Documentation**: Endpoint specifications
- **Testing Scripts**: docs/testing/ para validación completa
