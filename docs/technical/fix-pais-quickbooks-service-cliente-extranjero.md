# Fix Crítico: País Incorrecto en QuickBooksOnlineService

## Problema Identificado

Error persistente en PAC después del fix anterior:
```
El campo pais es inválido. El país del cliente debe ser PA si el destino de la operación es 1= Panamá.
```

**Datos del cliente desde QuickBooks**:
```json
{
  "CustomerRef": {
    "TIPO_RECEPTOR": "04",
    "PASAPORTE": "XYZABC123", 
    "Country": "Chile",
    "BillAddr": {"Country": "Chile"}
  }
}
```

## Causa Raíz Real

**El problema estaba en `QuickBooksOnlineService`, NO en `CreateFastJob`**:

### Lógica Incorrecta (ANTES):
```php
// Para operaciones internas (documentType=01), el país debe ser PA para consistencia con destination=1
$originalCountry = array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA'));
$isInternalOperation = ($typeOfSale === 1); // ❌ INCORRECTO: typeOfSale=1 para montos positivos
$correctedCountry = $isInternalOperation ? 'PA' : $originalCountry; // ❌ Forzaba PA para TODOS
```

### Problema:
1. ✅ Cliente extranjero detectado: `TIPO_RECEPTOR = "04"`, `Country = "Chile"`
2. ❌ **Servicio sobrescribe**: `typeOfSale = 1` (monto positivo) → fuerza `Country = "PA"`
3. ❌ **Cliente guardado con**: `TIPO_RECEPTOR = "04"` + `Country = "PA"`
4. ❌ **Resultado XML**: `iTipoRec = 04` (extranjero) + `cPaisRec = PA` (Panamá) → **CONFLICTO**

### Error de Lógica:
- **`typeOfSale`** solo depende del monto (positivo=1, negativo=2)
- **NO tiene relación con si el cliente es nacional o extranjero**
- **Estaba forzando país = 'PA' para TODAS las facturas con montos positivos**

## Solución Implementada

### Lógica Corregida:
```php
// CORRECCIÓN CRÍTICA: Determinar país basado en TIPO_RECEPTOR, no en typeOfSale
$originalCountry = array_get($customerRef, 'Country', array_get($customerRef, 'BillAddr.Country', 'PA'));
$tipoReceptor = array_get($customerRef, 'TIPO_RECEPTOR', '02');

// Solo forzar PA para clientes NACIONALES (tipos 01, 02, 03)
// Para extranjeros (tipo 04) mantener el país original
$isNationalClient = in_array($tipoReceptor, ['01', '02', '03', '1', '2', '3']);
$correctedCountry = $isNationalClient ? 'PA' : $originalCountry;
```

### Matriz de Validación:

| TIPO_RECEPTOR | Descripción | País Original | País Corregido | Resultado XML |
|---------------|-------------|---------------|----------------|---------------|
| `01`, `02`, `03` | Nacional | Cualquiera | `PA` (forzado) | `iTipoRec=01/02/03` + `cPaisRec=PA` ✅ |
| `04` | Extranjero | `Chile` | `Chile` (mantiene) | `iTipoRec=04` + `cPaisRec=CL` ✅ |
| `04` | Extranjero | `US` | `US` (mantiene) | `iTipoRec=04` + `cPaisRec=US` ✅ |

## Archivos Modificados

- `app/Services/QuickBooksOnlineService.php`: Líneas 691-711 - Lógica de corrección de país basada en tipo de receptor

## Flujo Completo Corregido

### Caso: Cliente Extranjero desde QuickBooks
1. **Request QB**: `TIPO_RECEPTOR: "04"`, `Country: "Chile"`
2. **Servicio detecta**: `isNationalClient = false` (04 no está en [01,02,03])
3. **País mantiene**: `correctedCountry = "Chile"`
4. **Cliente guardado**: `Custom_field3 = "04"`, `Country = "Chile"`
5. **CreateFastJob detecta**: `receptor_tipo = '3'` (ID para código 04)
6. **Destino operación**: `destinoOperacion = 2` (extranjero)
7. **XML generado**: `iDest = 2` + `cPaisRec = CL` ✅ **VÁLIDO PAC**

### Caso: Cliente Nacional desde QuickBooks  
1. **Request QB**: `TIPO_RECEPTOR: "01"`, `Country: "US"` (error de captura)
2. **Servicio detecta**: `isNationalClient = true` (01 está en [01,02,03])
3. **País corrige**: `correctedCountry = "PA"` (forzado)
4. **Cliente guardado**: `Custom_field3 = "01"`, `Country = "PA"`
5. **CreateFastJob detecta**: `receptor_tipo = '1'` (ID para código 01)
6. **Destino operación**: `destinoOperacion = 1` (nacional)
7. **XML generado**: `iDest = 1` + `cPaisRec = PA` ✅ **VÁLIDO PAC**

## Logging Mejorado

```php
Log::info('QuickBooksOnlineService: Corrigiendo país para Cliente Nacional', [
    'original_country' => $originalCountry,
    'corrected_country' => $correctedCountry,
    'tipo_receptor' => $tipoReceptor,
    'customer_name' => $customerName,
    'is_national_client' => $isNationalClient
]);
```

## Estado

✅ **CRÍTICO RESUELTO** - Error PAC "país inválido vs destino operación" solucionado en la raíz

Este fix corrige la inconsistencia en el nivel de servicio, asegurando que los datos del cliente se guarden correctamente desde el primer momento, evitando conflictos posteriores en la validación PAC.
