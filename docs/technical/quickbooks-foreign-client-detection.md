# Detección de Clientes Extranjeros en QuickBooks - Documentación Técnica

## Resumen Ejecutivo

Este documento explica el flujo completo de detección y manejo de clientes extranjeros en la integración con QuickBooks Online, desde la recepción del JSON hasta la aplicación de validaciones DGI en los componentes de facturación.

## Arquitectura del Sistema

### 1. Flujo de Datos Completo

```
QuickBooks JSON → QuickBooksOnlineService → CustomersImp Model → Componentes Livewire
```

### 2. Mapping de Campos

| QuickBooks CustomerRef | Función QuickBooksOnlineService | CustomersImp Campo | Descripción |
|------------------------|--------------------------------|-------------------|-------------|
| `TIPO_RECEPTOR` | `array_get($customerRef, 'TIPO_RECEPTOR', '02')` | `Custom_field3` | Tipo de receptor DGI |
| `PASAPORTE` | `array_get($customerRef, 'PASAPORTE')` | `Custom_field1` (si extranjero) | Pasaporte para tipo 04 |
| `RUC` | `array_get($customerRef, 'RUC')` | `Custom_field1` (si nacional) | RUC para tipos 01-03 |
| `DV` | `array_get($customerRef, 'DV')` | `Custom_field2` | Dígito verificador |
| `TIPO` | `array_get($customerRef, 'TIPO')` | `Custom_field4` | Tipo contribuyente |

## Código Fuente Detallado

### QuickBooksOnlineService.php

#### Función: `findOrCreateCustomerFromQuickBooks()`
**Ubicación**: Líneas 738-750
```php
$customer = $this->createDefaultClient($organization, [
    'CustomerID' => $customerId,
    'Customer_Bill_Name' => trim($customerName),
    'Ruc' => array_get($customerRef, 'RUC'),
    'Dv' => array_get($customerRef, 'DV'),
    'TIPO' => array_get($customerRef, 'TIPO'),
    'TIPO_RECEPTOR' => array_get($customerRef, 'TIPO_RECEPTOR', '02'),
    'ReceiverType' => array_get($customerRef, 'ReceiverType', '2'),
    'LocationCode' => array_get($customerRef, 'BillAddr.PostalCode', null),
    'Email' => array_get($customerRef, 'PrimaryEmail', $email),
    'AddressLine1' => array_get($customerRef, 'BillAddr.Line1', ''),
    'AddressLine2' => array_get($customerRef, 'BillAddr.Line2', ''),
    'Country' => $correctedCountry, // País corregido para operaciones internas
]);
```

#### Función: `createDefaultClient()`
**Ubicación**: Líneas 466-610

**Lógica de Detección de Extranjeros**:
```php
// Determinar TIPO_RECEPTOR independientemente del tipoContribuyente
$tipoReceptor = '02'; // Default: Consumidor final

// Verificar si viene TIPO_RECEPTOR directamente en el request
$tipoReceptorFromRequest = array_get($request, 'TIPO_RECEPTOR');
if (!is_null($tipoReceptorFromRequest)) {
    // Normalizar formato: asegurar que tenga cero al inicio
    $tipoReceptorNormalized = str_pad((string)$tipoReceptorFromRequest, 2, '0', STR_PAD_LEFT);
    // Validar que sea un valor permitido
    if (in_array($tipoReceptorNormalized, ['01', '02', '03', '04'])) {
        $tipoReceptor = $tipoReceptorNormalized;
    }
} else {
    // Lógica automática basada en criterios específicos:
    if (!empty($pasaporteFromRequest)) {
        $tipoReceptor = '04'; // Extranjero (tiene PASAPORTE)
        $pasaporte = $pasaporteFromRequest;
    } elseif (!empty($ruc) && !empty($dv)) {
        $tipoReceptor = '01'; // Contribuyente (tiene RUC)
    }
}

// Lógica de validación cruzada para extranjeros (TIPO_RECEPTOR: 04)
if ($tipoReceptor === '04') {
    // Para extranjeros: PASAPORTE debe estar presente, RUC/DV/TIPO deben ser null
    if (!empty($pasaporteFromRequest)) {
        $pasaporte = $pasaporteFromRequest;
        // Limpiar campos que no aplican para extranjeros
        $ruc = null;
        $dv = null;
        $tipoFromRequest = null;
    } else {
        // Si es extranjero pero no tiene pasaporte, cambiar a consumidor final
        $tipoReceptor = '02';
    }
} else {
    // Para no extranjeros: PASAPORTE debe ser null
    $pasaporte = null;
}
```

**Mapping a CustomersImp**:
```php
$customer = $modelCustomer->updateOrCreate([
    // ... campos de identificación
], [
    // Campos personalizados para facturación electrónica Panama
    'Custom_field1' => $tipoReceptor === '04' ? $pasaporte : $ruc,    // RUC para nacionales, PASAPORTE para extranjeros
    'Custom_field2' => $tipoReceptor === '04' ? null : $dv,           // DV solo para nacionales, null para extranjeros
    'Custom_field3' => $tipoReceptor,                                  // TIPO_RECEPTOR: 01=Contribuyente, 02=Consumidor final, 03=Gobierno, 04=Extranjero
    'Custom_field4' => $tipoContribuyente,                            // Tipo de contribuyente: 1=Persona Natural, 2=Contribuyente
    'Custom_field5' => $locationCode,                                 // Código de provincia/distrito
]);
```

### Detección en Componentes Livewire

#### Patrón Implementado en Todos los Componentes
**Archivos**: `CreateFastJob.php`, `CreateFast.php`, `Create.php`, `FE/Create.php`

```php
// Detectar cliente extranjero desde Custom_field3 (TIPO_RECEPTOR)
$tipoReceptor = $customer->Custom_field3 ?? '02';

if ($tipoReceptor === '04') {
    // ✅ CLIENTE EXTRANJERO DETECTADO
    Log::info('Cliente extranjero detectado', [
        'customer_id' => $customer->CustomerID,
        'customer_name' => $customer->Customer_Bill_Name,
        'tipo_receptor' => $tipoReceptor,
        'pasaporte' => $customer->Custom_field1
    ]);

    // Configurar nacionalidad basado en Country del cliente
    $countryCode = $customer->Country ?? 'US'; // Default a US si no está definido
    $paisDestino = \App\Models\Destinationcountryoperation::query()
        ->where('code', $countryCode)
        ->orWhere('name', 'like', '%' . $countryCode . '%')
        ->first();

    if ($paisDestino) {
        // CORRECCIÓN DGI: Para extranjeros
        // - Nacionalidad del Receptor = País del cliente (ej: Chile)
        // - País Destino de la Operación = Panamá (donde se emite la factura)
        // - Destino de la Operación = 1 (Panamá - porque la operación es en Panamá)
        $this->receptor_paisNacionalidad = $paisDestino->id;  // Nacionalidad: país del cliente
        
        // Buscar Panamá para destino de operación
        $panama = \App\Models\Destinationcountryoperation::query()
            ->where('code', 'PA')
            ->orWhere('name', 'like', '%Panamá%')
            ->orWhere('name', 'like', '%Panama%')
            ->first();
        
        if ($panama) {
            $this->receptor_paisDestinoOperacion = $panama->id;  // Destino: Panamá
            $this->destinoOperacion = 1; // Panamá (la operación se realiza en Panamá)
        }

        Log::info('País configurado para extranjero CORREGIDO', [
            'nacionalidad_pais_id' => $this->receptor_paisNacionalidad,
            'nacionalidad_pais_name' => $paisDestino->name,
            'destino_operacion_pais_id' => $this->receptor_paisDestinoOperacion,
            'destino_operacion_pais_name' => $panama->name ?? 'No encontrado',
            'destinoOperacion' => $this->destinoOperacion
        ]);
    }
}
```

## Validaciones DGI

### Criterios de Validación PAC
**Error Original**: "El país del cliente debe ser PA si el destino de la operación es 1= Panamá"

**Solución Implementada**:
1. **Detección Automática**: Usar `Custom_field3` para identificar TIPO_RECEPTOR = '04'
2. **Configuración de País**: Asignar país basado en campo `Country` del cliente
3. **Destino de Operación**: Para extranjeros, siempre usar `destinoOperacion = 2`

### Tipos de Receptor DGI
| Código | Descripción | Validaciones |
|--------|-------------|--------------|
| `01` | Contribuyente | Requiere RUC/DV válidos |
| `02` | Consumidor Final | Default para clientes sin RUC |
| `03` | Gobierno | Entidades gubernamentales |
| `04` | Extranjero | Requiere PASAPORTE, país != PA |

## Casos de Uso

### Caso 1: Cliente Chileno (Solmary)
**JSON QuickBooks**:
```json
{
  "CustomerRef": {
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "12345678",
    "Country": "CL"
  }
}
```

**Procesamiento**:
1. `QuickBooksOnlineService`: Detecta TIPO_RECEPTOR = "04"
2. `CustomersImp`: Almacena en Custom_field3 = "04"
3. `CreateFastJob`: Lee Custom_field3, detecta extranjero
4. **Resultado CORREGIDO**: 
   - `receptor_paisNacionalidad` = Chile
   - `receptor_paisDestinoOperacion` = Panamá
   - `destinoOperacion = 1` (Panamá)

### Caso 2: Cliente Panameño Contribuyente
**JSON QuickBooks**:
```json
{
  "CustomerRef": {
    "TIPO_RECEPTOR": "01",
    "RUC": "8-1234-567",
    "DV": "12",
    "Country": "PA"
  }
}
```

**Procesamiento**:
1. `QuickBooksOnlineService`: Detecta TIPO_RECEPTOR = "01"
2. `CustomersImp`: Almacena en Custom_field3 = "01"
3. `CreateFastJob`: Lee Custom_field3, detecta nacional
4. **Resultado**: 
   - `receptor_paisNacionalidad` = Panamá
   - `receptor_paisDestinoOperacion` = Panamá
   - `destinoOperacion = 1` (Panamá)

## Debugging y Troubleshooting

### Logs Importantes
```php
// En QuickBooksOnlineService.createDefaultClient()
Log::info('QuickBooksOnlineService.createDefaultClient: DEBUGGING Country Assignment', [
    'customer_name' => array_get($request, 'Customer_Bill_Name', ''),
    'tipo_receptor' => $tipoReceptor,
    'country_from_request' => $countryFromRequest,
    'default_country' => $defaultCountry,
    'final_country' => $finalCountry,
    'all_request_data' => $request
]);

// En CreateFastJob (y otros componentes)
Log::info('Cliente extranjero detectado', [
    'customer_id' => $customer->CustomerID,
    'customer_name' => $customer->Customer_Bill_Name,
    'tipo_receptor' => $tipoReceptor,
    'pasaporte' => $customer->Custom_field1
]);
```

### Verificación de Datos
**SQL para verificar datos del cliente**:
```sql
SELECT 
    CustomerID,
    Customer_Bill_Name,
    Country,
    Custom_field1 AS 'RUC_o_PASAPORTE',
    Custom_field2 AS 'DV',
    Custom_field3 AS 'TIPO_RECEPTOR',
    Custom_field4 AS 'TIPO_CONTRIBUYENTE',
    Custom_field5 AS 'LOCATION_CODE'
FROM CustomersImp 
WHERE Customer_Bill_Name LIKE '%Solmary%';
```

## Estado Actual del Sistema

### ✅ Componentes Corregidos
- **CreateFast.php**: Detección automática implementada
- **Create.php**: Detección automática implementada  
- **FE/Create.php**: Detección automática implementada
- **CreateFastJob.php**: Detección automática implementada

### ✅ Commit Aplicado
**Hash**: `af454e9d`
**Mensaje**: `feat: implementar detección automática de clientes extranjeros en componentes de facturación`

### 📋 Validaciones Pendientes
- [ ] Testing con datos reales de QuickBooks
- [ ] Verificación de países no estándar (códigos personalizados)
- [ ] Manejo de casos edge (clientes sin país definido)

## Conclusiones

El sistema ahora maneja correctamente:

1. **Recepción de Datos**: QuickBooks envía TIPO_RECEPTOR y PASAPORTE en CustomerRef
2. **Almacenamiento**: Se guarda en CustomersImp.Custom_field3 (TIPO_RECEPTOR) y Custom_field1 (PASAPORTE)
3. **Detección**: Todos los componentes leen Custom_field3 para identificar extranjeros
4. **Configuración CORREGIDA**: Para extranjeros se asigna nacionalidad del cliente + destino Panamá + destinoOperacion = 1
5. **Validación DGI**: Se cumple con "todas las operaciones son en Panamá (destino = 1)"

### **🔄 CORRECCIÓN CRÍTICA APLICADA**

**ANTES (Incorrecto)**:
- Extranjeros: `destinoOperacion = 2` + país destino = país del cliente
- Validación DGI fallaba para ciertos casos

**DESPUÉS (Correcto)**:
- **TODAS las operaciones**: `destinoOperacion = 1` (Panamá)
- **Extranjeros**: Nacionalidad = país del cliente, Destino = Panamá
- **Nacionales**: Nacionalidad = Panamá, Destino = Panamá
- **Validación DGI**: "todas las operaciones son en territorio panameño"

El error original "El país del cliente debe ser PA si el destino de la operación es 1= Panamá" se resuelve porque ahora el sistema correctamente interpreta que todas las operaciones ocurren en Panamá, pero distingue la nacionalidad del receptor.
