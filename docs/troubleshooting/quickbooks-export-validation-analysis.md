# Análisis de Error: "datosFacturaExportacion es requerido" - QuickBooks Export

## 🚨 Error Identificado
**Mensaje**: "El campo datosFacturaExportacion es requerido"  
**Contexto**: Factura de exportación para cliente extranjero (Guatemala)  
**Causa**: Faltan campos obligatorios de exportación según DGI Panamá

## 📋 Objeto QuickBooks Analizado
```json
{
  "Line": [{"Amount": 107, "DetailType": "SalesItemLineDetail"}],
  "TotalAmt": 107,
  "CustomerRef": {"value": "60", "name": "Coors Light Honduras"},
  "CurrencyRef": {"value": "USD", "name": "United States Dollar"},
  "BillAddr": {"Country": "Guatemala"}
}
```

## 🔍 Análisis Técnico

### Clasificación del Documento
- **Tipo de Documento**: 03 (Factura de Exportación)
- **Receptor**: TIPO_RECEPTOR "04" (Extranjero/Gobierno)
- **País Destino**: Guatemala (extranjero)
- **Moneda**: USD

### ❌ Campos Faltantes Obligatorios

#### **Estructura gFExp Requerida (datosFacturaExportacion)**
```php
'gFExp' => [
    'cCondEntr' => null,        // ❌ INCOTERM FALTANTE (OBLIGATORIO)
    'cMoneda' => 'USD',         // ✅ Moneda disponible
    'dCambio' => null,          // ❌ Tipo cambio FALTANTE
    'dVTotEst' => '107.00',     // ✅ Monto disponible
    'dPuertoEmbarq' => null     // ❌ Puerto embarque FALTANTE
]
```

#### **Campos Adicionales de Exportación**
```php
'paisOrigenMercancia' => null,      // ❌ B507 - País origen FALTANTE
'paisDestinoMercancia' => 'GT',     // ✅ Guatemala (extraible de BillAddr)
'terminalEmbarque' => null,         // ❌ B509 - Terminal FALTANTE
'numeroContenedor' => null,         // ❌ B510 - Contenedor FALTANTE
'pesoTotalMercancia' => null        // ❌ B511 - Peso FALTANTE
```

## 🔧 Soluciones Propuestas

### **Opción 1: Valores por Defecto (Recomendada)**
```php
// En QuickBooksContactValidator o similar
public function addExportDefaults($invoiceData) {
    if ($this->isExportInvoice($invoiceData)) {
        return array_merge($invoiceData, [
            'condicionesEntrega' => 'FOB',           // INCOTERM por defecto
            'tipoCambio' => 1.000000,                // USD = 1:1 con PAB
            'puertoEmbarque' => 'Puerto de Balboa',  // Puerto principal Panamá
            'paisOrigenMercancia' => 'PA',           // Panamá como origen
            'paisDestinoMercancia' => $this->extractCountryFromAddress($invoiceData)
        ]);
    }
    return $invoiceData;
}
```

### **Opción 2: Configuración por Organización**
```php
// En modelo Organization o configuración
'export_defaults' => [
    'default_incoterm' => 'FOB',
    'default_port' => 'Puerto de Balboa',
    'default_origin_country' => 'PA'
]
```

### **Opción 3: Mapeo desde QuickBooks**
```php
// Extraer información disponible del objeto QB
private function mapQuickBooksToExport($qbInvoice) {
    return [
        'condicionesEntrega' => $qbInvoice['CustomField']['INCOTERM'] ?? 'FOB',
        'paisDestinoMercancia' => $this->getCountryCode($qbInvoice['BillAddr']['Country']),
        'monedaExportacion' => $qbInvoice['CurrencyRef']['value'],
        'montoMonedaExtranjera' => $qbInvoice['TotalAmt']
    ];
}
```

## 📝 Validaciones DGI Aplicables

### **Para Tipo 03 (Exportación)**
```php
// Validaciones críticas según ficha técnica DGI
$rules = [
    'receptor_tipo' => 'required|in:4',                    // SOLO extranjero
    'destinoOperacion' => 'required|in:2',                 // SOLO destino extranjero
    'condicionesEntrega' => 'required|string|max:50',      // INCOTERM obligatorio
    'paisOrigenMercancia' => 'required|string|size:2',     // País origen obligatorio
    'paisDestinoMercancia' => 'required|string|size:2|not_in:PA', // País destino ≠ PA
    'tipoCambio' => 'required_if:monedaExportacion,!=,USD|numeric|min:0.01',
    'montoMonedaExtranjera' => 'required_if:monedaExportacion,!=,USD|numeric|min:0.01'
];
```

## 🎯 Implementación Inmediata

### **1. Modificar QuickBooksContactValidator.php**
```php
public function validateForElectronicInvoicing($contact, $invoice = null) {
    $errors = [];
    
    // Validación existente de moneda
    if (!in_array($currency, $this->allowedCurrencies)) {
        $errors[] = "Moneda {$currency} no soportada para facturación electrónica";
    }
    
    // NUEVA: Validación campos exportación si es cliente extranjero
    if ($this->isExportCustomer($contact, $invoice)) {
        $exportErrors = $this->validateExportRequirements($invoice);
        $errors = array_merge($errors, $exportErrors);
    }
    
    return $errors;
}

private function validateExportRequirements($invoice) {
    $errors = [];
    
    // Verificar que se puedan extraer/asignar campos obligatorios
    if (empty($this->getDefaultIncoterm())) {
        $errors[] = "INCOTERM requerido para factura de exportación";
    }
    
    if (empty($this->getDestinationCountry($invoice))) {
        $errors[] = "País de destino requerido para exportación";
    }
    
    return $errors;
}
```

### **2. Agregar Helper para Exportación**
```php
// En app/Helpers/ExportValidationHelper.php
class ExportValidationHelper {
    public static function addRequiredExportFields($invoiceData, $organization) {
        if (self::isExportInvoice($invoiceData)) {
            return array_merge($invoiceData, [
                'condicionesEntrega' => self::getDefaultIncoterm($organization),
                'paisOrigenMercancia' => 'PA',
                'puertoEmbarque' => self::getDefaultPort($organization),
                'tipoCambio' => self::getExchangeRate($invoiceData['currency']),
            ]);
        }
        return $invoiceData;
    }
}
```

## 🏆 Resultado Esperado

Después de implementar las correcciones:

✅ **Facturas de QuickBooks con clientes extranjeros** procesarán correctamente  
✅ **Campos de exportación obligatorios** se completarán automáticamente  
✅ **Validación DGI** pasará sin errores  
✅ **PAC Alanube** aceptará la estructura completa  

## 📚 Referencias Técnicas

- **Ficha Técnica DGI**: Campos B50x (exportación) obligatorios para tipo 03
- **Documentación DocuCenter**: `/docs/technical/panama-document-conditional-fields-mapping.md`
- **Validaciones Implementadas**: `app/Http/Livewire/Admin/Einvoice/Create.php` líneas 1265-1290
- **Estructura gFExp**: `app/Services/FEXmlService.php` método `gFExp()`

---
**Fecha de Análisis**: 2025-01-27  
**Estado**: Pendiente de implementación  
**Prioridad**: Alta - Bloquea facturación de exportación desde QuickBooks
