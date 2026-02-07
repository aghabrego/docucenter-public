# Análisis: Sistema de Numeración de Facturas en Maypar

**Fecha**: Febrero 2, 2025  
**Versión**: 1.0  
**Estado**: 🔴 CRÍTICO - Validación Incompleta

---

## 📋 Resumen Ejecutivo

Según la documentación oficial de **Maypar**, el número de factura es una **combinación de dos campos**:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Prefijo (serie)** | Identificador único por equipo de pago | `2026001`, `2026-Cajero1`, `A132` |
| **Número (consecutivo)** | Número secuencial dentro de cada prefijo | `1`, `2`, `3`, ..., `N` |

### 🔑 Identificador Único = `Prefijo-Número`

El identificador **ÚNICO** de una factura es la **concatenación** de ambos valores:

```
Factura Única = Prefijo + Número
Ejemplo: "2026001-0000001"
```

---

## ✅ Lo que DEBERÍA Validarse

### Validación CORRECTA ✓
```
Rechazar: Si existe Prefijo="2026001" Y Número="5"
Razón: La combinación "2026001-5" ya existe
```

### Validación INCORRECTA ❌
```
NO rechazar: Si existe Prefijo="2026001" Y Número="5"
              pero queremos insertar Prefijo="2026002" Y Número="5"
Razón: Son dos series diferentes, combinación "2026002-5" NO existe
```

---

## 🐛 PROBLEMA ACTUAL EN DOCUCENTER

### Ubicación: `app/Services/MeyparService.php` (línea 83)

```php
// LÍNEA 83 - VALIDACIÓN ACTUAL (INCOMPLETA)
$cufeValidation = \App\Helpers\CufeValidationHelper::checkExistingCufe(
    $invoiceNumberComplete,  // ← Solo el número, SIN el prefijo
    $organization->id
);
```

### ¿Por Qué Es Incorrecto?

1. **Solo valida el número**: `checkExistingCufe()` busca solo por `InvoiceNumber`
   ```php
   // Línea 35 en CufeValidationHelper.php
   $salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();
   ```

2. **No considera el prefijo**: La validación es:
   ```
   ❌ WHERE InvoiceNumber = "5"
   ✓ Debería ser: WHERE Prefijo = "2026001" AND Numero = "5"
   ```

3. **Resultado**: El sistema rechazaría correctamente:
   - ❌ Prefijo="2026001", Número="5" (ya existe)
   - ❌ Prefijo="2026002", Número="5" (INCORRECTO - debería aceptar)

---

## 📊 Ejemplo de Colisión

### Escenario
- Cajero 1 emitió factura: Prefijo=`2026001`, Número=`5` ✓
- Cajero 2 quiere emitir: Prefijo=`2026002`, Número=`5`

### Con Validación ACTUAL (INCOMPLETA)
```
DocuCenter busca: WHERE InvoiceNumber = "5"
Encuentra: Sí (del Cajero 1)
Resultado: ❌ RECHAZA LA FACTURA (ERROR)
```

### Con Validación CORRECTA
```
DocuCenter busca: WHERE Prefijo = "2026002" AND Numero = "5"
Encuentra: No
Resultado: ✅ ACEPTA LA FACTURA (CORRECTO)
```

---

## 🔧 Campos a Validar en DocuCenter

### En `MeyparService::storeOrder()` (línea 71-72)

```php
$numero = $this->sanitizeTextField(array_get($data, 'numero', ''), 20, 'InvoiceNumber');
$prefijo = $this->sanitizeTextField(array_get($data, 'prefijo', ''), 10, 'Prefijo');

// ❌ Línea 75 - ACTUAL (Incorrecto)
$invoiceNumberComplete = (string) $numero;  // Solo número

// ✅ DEBERÍA SER
$invoiceNumberComplete = "{$prefijo}-{$numero}";  // Prefijo-Número
```

### En `SalesHeaderImp` Model

```php
// BUSCAR SI EXISTE EN BD
SalesHeaderImp::where('Prefijo', $prefijo)
              ->where('Numero', $numero)
              ->first();
```

---

## 📍 Tabla de Base de Datos

### Columnas Necesarias en `sales_header_imp`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | int | ID principal |
| `InvoiceNumber` | varchar(50) | **Ahora debería ser `Prefijo-Número`** |
| `Prefijo` | varchar(10) | Nuevo campo - Serie/Prefijo |
| `Numero` | int | Nuevo campo - Número consecutivo |
| `intuit_extracted_cufe` | varchar(255) | CUFE emitido |

---

## 🚀 Solución Recomendada

### Paso 1: Migración de Base de Datos
```php
// database/migrations/add_prefijo_numero_to_sales_header_imp.php
Schema::table('sales_header_imp', function (Blueprint $table) {
    $table->string('Prefijo', 10)->nullable()->after('InvoiceNumber');
    $table->integer('Numero')->nullable()->after('Prefijo');
    $table->unique(['Prefijo', 'Numero']); // Índice único
});
```

### Paso 2: Actualizar Validación
```php
// app/Services/MeyparService.php - línea 83
public function validateInvoiceNumber(string $prefijo, int $numero, int $organizationId): bool
{
    DB::connection()->useDatabase($organization->database);
    
    $exists = SalesHeaderImp::where('Prefijo', $prefijo)
                            ->where('Numero', $numero)
                            ->exists();
    
    return !$exists; // true si NO existe (validación OK)
}
```

### Paso 3: Actualizar Almacenamiento
```php
// app/Services/MeyparService.php - línea 77-80
$header = SalesHeaderImp::updateOrCreate([
    'Prefijo' => $prefijo,
    'Numero' => (int)$numero,
], [
    'InvoiceNumber' => "{$prefijo}-{$numero}",
    // ... resto de campos
]);
```

---

## 📌 Validación de Datos Actuales

### ¿Dónde está el `prefijo` en la API de Maypar?

```json
{
  "prefijo": "A132",         // ← Campo recibido
  "numero": 2025,            // ← Campo recibido
  "idFacturador": 12345678,
  "documento": "900123456-1",
  "tipoDocumento": 1,
  "autorizacionPrefijo": {
    "PrefijoId": "A132",     // Información complementaria
    "CorrelativoInicialNum": 1,
    "CorrelativoFinalNum": 50000
  }
}
```

### En Código Actual (Línea 71-75)
```php
$numero = array_get($data, 'numero', '');       // ✓ Extrae "2025"
$prefijo = array_get($data, 'prefijo', '');     // ✓ Extrae "A132"

// ❌ PERO SOLO USA EL NÚMERO
$invoiceNumberComplete = (string) $numero;  // "2025" (pierde prefijo)
```

---

## ⚠️ Impacto Actual

### Riesgo CRÍTICO

```
Si dos equipos de pago usan el mismo número consecutivo:

Equipo 1: Prefijo="BOG001", Número="1" → Factura 1
Equipo 2: Prefijo="MED001", Número="1" → Factura 1

Sistema DocuCenter: ❌ RECHAZA LA SEGUNDA (ERROR)
Sistema Real Maypar: ✅ ACEPTA (son diferentes series)
```

### Consecuencias

- ❌ Bloqueo innecesario de facturas válidas
- ❌ Reportes de "números duplicados" incorrectos
- ❌ Pérdida de ventas de múltiples equipos
- ❌ Inconsistencia con comportamiento de Maypar

---

## ✅ Checklist de Corrección

- [ ] Agregar columnas `Prefijo` y `Numero` a `sales_header_imp`
- [ ] Crear miración de BD
- [ ] Actualizar `MeyparService::storeOrder()` para extraer ambos campos
- [ ] Actualizar validación de duplicados (prefijo+número)
- [ ] Actualizar `CufeValidationHelper` para usar prefijo+número
- [ ] Crear índice único en (Prefijo, Numero)
- [ ] Actualizar `SalesHeaderImp` model
- [ ] Testear con múltiples equipos (prefijos diferentes)
- [ ] Documentar en guía de API Maypar
- [ ] Verificar retrocompatibilidad con datos existentes

---

## 📚 Referencias

**Documento de Maypar**:
> "En todos los modelos tributarios, el número que identifica de manera única una factura se compone de dos partes:
> - número de serie (propiedad "prefijo")
> - número de consecutivo de factura (propiedad "número")
>
> El número de factura se obtiene uniendo ambos: Prefijo-Número"

**Validación Correcta**:
> "Si en vuestro sistema queréis comprobar que no se está emitiendo una factura duplicada, debéis comprobar que no se repite el valor conjunto 'prefijo-número'. Comprobar únicamente que no se repite el valor del 'número' no es una validación correcta."

---

## 🎯 Próximas Acciones

1. **Confirmar**: ¿Los datos históricos tienen almacenado el prefijo?
2. **Verificar**: ¿Cómo se está guardando actualmente `Prefijo` en BD?
3. **Mapear**: ¿Dónde se genera/obtiene el prefijo en cada integración?
4. **Ajustar**: Actualizar lógica de validación y almacenamiento

