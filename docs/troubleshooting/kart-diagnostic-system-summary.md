# 🎯 Sistema de Diagnóstico Kart → Facturación Electrónica

## 📋 Resumen de Implementación

Se ha creado un sistema completo de diagnóstico y reparación para resolver problemas cuando las órdenes de Kart21 no se emiten automáticamente como facturas electrónicas en DocuCenter.

### 🚀 Herramientas Implementadas

#### 1. Comandos Artisan

| Comando | Propósito | Uso |
|---------|-----------|-----|
| `kart:diagnose-invoice` | Diagnóstico completo de facturación | `php artisan kart:diagnose-invoice <org_id> [kart_order_id]` |
| `kart:force-emission` | Forzar emisión de facturas | `php artisan kart:force-emission <org_id> [invoice_id] [--all] [--dry-run]` |
| `kart:send-to-zoho` | Enviar orden Kart a Zoho Books | `php artisan kart:send-to-zoho <connection_id> [--dry-run]` |

#### 2. Scripts de Diagnóstico

| Script | Propósito | Características |
|--------|-----------|-----------------|
| `diagnose-kart-invoice.sh` | Script principal de diagnóstico | 🔍 Diagnóstico completo, 🚀 Forzar emisión, 📄 Análisis de logs |
| `test-kart-order-12720.sh` | Prueba específica orden #12720 | 🧪 Datos reales, 🔍 Búsqueda múltiple, 🛠️ Simulación |
| `test-kart-to-zoho.sh` | Integración Kart → Zoho | 🔗 Testing de integración, 📊 Transformación de datos |

### 🎯 Caso Específico: Orden Kart #12720

#### Datos de la Orden Problemática
```json
{
  "id": 12720,
  "customer": "Marcos Bohnen (marcos.bohnen@gmail.com)",
  "items": [
    "Promo Vacaciones ($19.00 + $1.33 tax)",
    "Licencia por 1 dia ($0.00 + $0.00 tax)"
  ],
  "total": "$20.33",
  "status": "CLOSED",
  "fecha": "2025-08-29"
}
```

#### Comandos de Diagnóstico Rápido

```bash
# Diagnóstico específico de la orden
./scripts/test-kart-order-12720.sh [org_id]

# Diagnóstico completo
./scripts/diagnose-kart-invoice.sh diagnose 1 12720

# Simular procesamiento con datos reales
./scripts/diagnose-kart-invoice.sh simulate 1
```

### 🔍 Proceso de Diagnóstico Automatizado

#### Paso 1: Verificación de Configuración
- ✅ Organización existe y está activa
- 🔗 Configuración PAC disponible y activa
- 🔑 Token PAC válido y configurado
- 📊 Base de datos específica accesible

#### Paso 2: Búsqueda de Factura en Sistema
```sql
-- Múltiples criterios de búsqueda:
SELECT * FROM Sales_Header_Imp WHERE InvoiceNumber LIKE '%12720%';
SELECT * FROM Sales_Header_Imp WHERE CustomerName LIKE '%Marcos%' OR CustomerName LIKE '%Bohnen%';
SELECT * FROM Sales_Header_Imp WHERE Net_due = 20.33;
SELECT * FROM Sales_Header_Imp WHERE Date >= '2025-08-29' AND Date < '2025-08-30';
```

#### Paso 3: Análisis de Logs Automatizado
```bash
# Patrones automáticamente buscados:
grep -i "CreateSaleKart21Job.*12720" storage/logs/laravel.log
grep -i "Kart21Service.*12720" storage/logs/laravel.log
grep -i "storeOrder.*12720" storage/logs/laravel.log
grep -i "error.*kart.*12720" storage/logs/laravel.log
```

#### Paso 4: Verificación de Emisión Automática
- 🔧 Verifica si `Kart21Service` tiene trait `CreateFastJob`
- 📋 Comprueba método `issueInvoice` disponible
- 🚀 Analiza estado `EzeeIssued` de facturas
- ⏳ Revisa jobs en cola y fallidos

### 💡 Soluciones Identificadas

#### Problema 1: Configuración PAC Faltante
**Síntomas:**
- ❌ Error: "No se encontró configuración PAC"
- Facturas se crean pero no se emiten

**Solución Automática:**
```bash
./scripts/diagnose-kart-invoice.sh diagnose 1
# → Identifica y reporta configuración PAC faltante
```

#### Problema 2: Kart21Service Sin Emisión Automática
**Síntomas:**
- ✅ Factura creada en DocuCenter
- ❌ `EzeeIssued = 0` (no emitida)

**Solución:**
```php
// Agregar a app/Services/Kart21Service.php
class Kart21Service implements Kart21ServiceContract
{
    use Helper, CreateFastJob; // ← Agregar este trait
    
    // Implementar método requerido:
    public function issueInvoice($salesHeader, $user)
    {
        return $this->processEmission($salesHeader, $user);
    }
}
```

#### Problema 3: Webhook No Procesado
**Síntomas:**
- ❌ No hay factura en DocuCenter
- Sin logs de procesamiento

**Solución:**
```bash
# Simular webhook manualmente
./scripts/test-kart-order-12720.sh 1

# O enviar a integración externa
./scripts/test-kart-to-zoho.sh 21
```

#### Problema 4: Job Fallido
**Síntomas:**
- ⚠️ Jobs en estado `failed`
- Logs con excepciones

**Solución:**
```bash
# Diagnóstico automático de jobs
./scripts/diagnose-kart-invoice.sh queue-status

# Forzar emisión de facturas existentes
./scripts/diagnose-kart-invoice.sh force-emit-all 1 --dry-run
```

### 🛠️ Comandos de Uso Frecuente

#### Diagnóstico Rápido
```bash
# Orden específica problemática
./scripts/test-kart-order-12720.sh 1

# Configuración general
./scripts/diagnose-kart-invoice.sh diagnose 1
```

#### Reparación
```bash
# Ver qué se repararía
./scripts/diagnose-kart-invoice.sh force-emit-all 1 --dry-run

# Ejecutar reparación
./scripts/diagnose-kart-invoice.sh force-emit-all 1
```

#### Integración Externa
```bash
# Probar envío a Zoho Books
./scripts/test-kart-to-zoho.sh 21 --dry-run

# Envío real a Zoho Books
./scripts/test-kart-to-zoho.sh 21
```

### 📊 Métricas y Monitoreo

#### Indicadores de Salud del Sistema
- ✅ **Facturas Creadas**: Kart21Service.storeOrder() exitoso
- 🚀 **Facturas Emitidas**: EzeeIssued = 1
- ⏳ **Jobs Pendientes**: Cola de CreateSaleKart21Job
- ❌ **Jobs Fallidos**: Tabla failed_jobs

#### Comandos de Monitoreo
```bash
# Estado general
./scripts/diagnose-kart-invoice.sh queue-status

# Logs en tiempo real
tail -f storage/logs/laravel.log | grep -i 'kart\|CreateSaleKart21Job'

# Facturas pendientes de emisión
php artisan tinker --execute="
    DB::connection()->useDatabase('org_database');
    echo 'Pendientes: ' . App\Models\SalesHeaderImp::where('EzeeIssued', 0)->count();
"
```

### 🔗 Integración con Zoho Books

Se incluye integración completa para enviar órdenes de Kart a Zoho Books:

```bash
# Transformación Kart → Zoho Books
./scripts/test-kart-to-zoho.sh 21 --dry-run

# Datos transformados automáticamente:
# • Cliente: Marcos Bohnen → Zoho Contact
# • Items: Promo Vacaciones, Licencia → Zoho Line Items  
# • Orden: KART-12720 → Zoho Sales Order
```

### 📚 Documentación Creada

| Documento | Propósito | Ubicación |
|-----------|-----------|-----------|
| **Guía de Diagnóstico** | Troubleshooting completo | `docs/troubleshooting/kart-invoice-diagnosis.md` |
| **API Kart21** | Documentación técnica | `docs/api/fe/kart21-api.md` |
| **Comandos Testing** | Scripts ejecutables | `scripts/diagnose-kart-invoice.sh` |
| **Caso Específico** | Orden #12720 | `scripts/test-kart-order-12720.sh` |

### 🎯 Próximos Pasos Recomendados

1. **Ejecutar Diagnóstico:**
   ```bash
   ./scripts/test-kart-order-12720.sh 1
   ```

2. **Si no se encuentra la factura:**
   - Revisar webhook de Kart21
   - Verificar logs de CreateSaleKart21Job
   - Procesar manualmente

3. **Si la factura existe pero no está emitida:**
   - Agregar trait CreateFastJob a Kart21Service
   - Verificar configuración PAC
   - Forzar emisión manual

4. **Para integración con sistemas externos:**
   - Usar comandos de envío a Zoho Books
   - Implementar webhooks de confirmación
   - Monitorear métricas de emisión

Este sistema proporciona una solución completa y automatizada para diagnosticar y resolver problemas de facturación electrónica con Kart21, asegurando cumplimiento fiscal y procesamiento eficiente de transacciones.
