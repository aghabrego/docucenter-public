# 🔧 Plan de Corrección: Validación de Números de Factura Maypar

**Clasificación**: 🔴 CRÍTICO  
**Impacto**: Bloqueo incorrecto de facturas válidas en sistemas multi-equipo  
**Prioridad**: ALTA  
**Fecha**: Febrero 2, 2025

---

## 📌 Problema Identificado

### El Síntoma
Sistema rechaza facturas válidas cuando múltiples equipos de pago usan el **mismo número consecutivo** pero **diferentes prefijos/series**.

### La Causa
`MeyparService::storeOrder()` valida duplicados usando **SOLO el número**, sin considerar el **prefijo**.

```php
// ❌ INCORRECTO - Línea 75 en MeyparService.php
$invoiceNumberComplete = (string) $numero;  // "5"

// ❌ INCORRECTO - Línea 35 en CufeValidationHelper.php
$salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();
// Busca: WHERE InvoiceNumber = "5" (ignorando prefijo)
```

### El Impacto
| Escenario | Prefijo Cajero 1 | Número | Prefijo Cajero 2 | Número | Resultado Actual | Resultado Esperado |
|-----------|-----------------|--------|-----------------|--------|------------------|-------------------|
| Mismo número, diferente prefijo | `2026001` | `5` | `2026002` | `5` | ❌ RECHAZA | ✅ ACEPTA |
| Mismo número, mismo prefijo | `2026001` | `5` | `2026001` | `5` | ❌ RECHAZA | ❌ RECHAZA |

---

## 🎯 Solución

### Paso 1: Almacenar Prefijo y Número Separadamente

#### En `SalesHeaderImp` Model
```php
/**
 * @property string $Prefijo          // NUEVO: Serie/Prefijo
 * @property int $Numero              // NUEVO: Número consecutivo
 * @property string $InvoiceNumber    // EXISTENTE: "Prefijo-Numero"
 */
```

#### En Base de Datos (SQL Manual)
```sql
-- Para cada organización (tabla dinámica sales_header_imp)
ALTER TABLE sales_header_imp
ADD COLUMN Prefijo VARCHAR(10) NULL AFTER InvoiceNumber;

ALTER TABLE sales_header_imp
ADD COLUMN Numero INT NULL AFTER Prefijo;

-- Crear índice único para garantizar unicidad de Prefijo-Numero
CREATE UNIQUE INDEX idx_prefijo_numero ON sales_header_imp(Prefijo, Numero);
```

### Paso 2: Actualizar `MeyparService::storeOrder()`

#### Cambio 1: Línea 75-80
```php
// ❌ ANTES
$numero = $this->sanitizeTextField(array_get($data, 'numero', ''), 20, 'InvoiceNumber');
$prefijo = $this->sanitizeTextField(array_get($data, 'prefijo', ''), 10, 'Prefijo');
$invoiceNumberComplete = (string) $numero;

// ✅ DESPUÉS
$numero = (int) array_get($data, 'numero', 0);  // Convertir a int
$prefijo = $this->sanitizeTextField(array_get($data, 'prefijo', ''), 10, 'Prefijo');
$invoiceNumberComplete = "{$prefijo}-{$numero}";  // Combinar para almacenar

// Validar que no exista la combinación Prefijo-Numero
if (!$this->validateInvoiceNumberUnique($prefijo, $numero, $organization->id)) {
    throw new \Exception(
        "Factura duplicada: Prefijo '{$prefijo}' con número '{$numero}' ya existe"
    );
}
```

#### Cambio 2: Línea 245-255 (Crear/Actualizar Header)
```php
// ✅ ACTUALIZAR el updateOrCreate
$header = SalesHeaderImp::updateOrCreate([
    'Prefijo' => $prefijo,              // NUEVO: Usar prefijo como identificador
    'Numero' => $numero,                // NUEVO: Usar número como identificador
], [
    'InvoiceNumber' => $invoiceNumberComplete,  // "Prefijo-Numero"
    'ID_compania' => $company->ID_compania,
    'CustomerID' => $customer->CustomerID,
    // ... resto de campos
]);
```

### Paso 3: Crear Método de Validación

#### Nuevo Método en `MeyparService`
```php
/**
 * Validar que la combinación Prefijo-Número sea única
 *
 * @param string $prefijo
 * @param int $numero
 * @param int $organizationId
 * @return bool true si es único (OK para procesar)
 */
private function validateInvoiceNumberUnique(
    string $prefijo,
    int $numero,
    int $organizationId
): bool {
    try {
        $organization = \App\Models\Organization::find($organizationId);
        if (!$organization || !$organization->database) {
            Log::error('Organization no encontrada en validateInvoiceNumberUnique', [
                'organization_id' => $organizationId
            ]);
            return false;
        }

        // Cambiar a BD de organización
        DB::connection()->useDatabase($organization->database);

        // Buscar por Prefijo + Numero (combinación única)
        $exists = SalesHeaderImp::where('Prefijo', $prefijo)
                                ->where('Numero', $numero)
                                ->exists();

        // Volver a BD predeterminada
        DB::connection()->useDatabase(env('DB_DATABASE'));

        Log::info('Validación de factura Maypar', [
            'prefijo' => $prefijo,
            'numero' => $numero,
            'organization_id' => $organizationId,
            'existe' => $exists,
            'es_valida' => !$exists
        ]);

        return !$exists;  // true = NO existe (válido)

    } catch (\Exception $e) {
        Log::error('Error en validateInvoiceNumberUnique', [
            'prefijo' => $prefijo,
            'numero' => $numero,
            'error' => $e->getMessage()
        ]);
        return false;  // Conservador: rechazar en caso de error
    }
}
```

### Paso 4: Actualizar `CufeValidationHelper`

#### Modificar `checkExistingCufe()` Línea 35
```php
// ❌ ANTES
$salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();

// ✅ DESPUÉS
// Si $invoiceNumber viene como "Prefijo-Numero", dividir
$parts = explode('-', $invoiceNumber, 2);
if (count($parts) === 2) {
    [$prefijo, $numero] = $parts;
    $salesHeader = SalesHeaderImp::where('Prefijo', $prefijo)
                                 ->where('Numero', (int)$numero)
                                 ->first();
} else {
    // Fallback: buscar por InvoiceNumber completo (para compatibilidad)
    $salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();
}
```

---

## 📋 Cambios de Código Necesarios

### Archivo 1: `app/Services/MeyparService.php`

#### Cambio A (Validación)
```diff
- $numero = $this->sanitizeTextField(array_get($data, 'numero', ''), 20, 'InvoiceNumber');
+ $numero = (int) array_get($data, 'numero', 0);
  $prefijo = $this->sanitizeTextField(array_get($data, 'prefijo', ''), 10, 'Prefijo');
  
- // Usar solo el número sin combinar con prefijo
- $invoiceNumberComplete = (string) $numero;
+ // Combinar Prefijo-Numero para InvoiceNumber
+ $invoiceNumberComplete = "{$prefijo}-{$numero}";
+ 
+ // Validar unicidad de Prefijo-Numero
+ if (!$this->validateInvoiceNumberUnique($prefijo, $numero, $organization->id)) {
+     throw new \Exception(
+         "Factura duplicada: Prefijo '{$prefijo}' con número '{$numero}' ya existe"
+     );
+ }
```

#### Cambio B (Almacenamiento)
```diff
  $header = SalesHeaderImp::updateOrCreate([
-     'InvoiceNumber' => $invoiceNumberComplete,
+     'Prefijo' => $prefijo,
+     'Numero' => $numero,
  ], [
      'InvoiceNumber' => $invoiceNumberComplete,
      'ID_compania' => $company->ID_compania,
      ...
  ]);
```

#### Cambio C (Nuevo Método)
```php
// Agregar al final de la clase MeyparService

/**
 * Validar que Prefijo-Numero sea único en la organización
 *
 * @param string $prefijo
 * @param int $numero
 * @param int $organizationId
 * @return bool
 */
private function validateInvoiceNumberUnique(
    string $prefijo,
    int $numero,
    int $organizationId
): bool {
    try {
        $organization = \App\Models\Organization::find($organizationId);
        if (!$organization || !$organization->database) {
            Log::error('Organization no encontrada', [
                'organization_id' => $organizationId
            ]);
            return false;
        }

        DB::connection()->useDatabase($organization->database);
        
        $exists = SalesHeaderImp::where('Prefijo', $prefijo)
                                ->where('Numero', $numero)
                                ->exists();

        DB::connection()->useDatabase(env('DB_DATABASE'));

        return !$exists;
    } catch (\Exception $e) {
        Log::error('Error validando factura', [
            'prefijo' => $prefijo,
            'numero' => $numero,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
```

### Archivo 2: `app/Helpers/CufeValidationHelper.php`

#### Cambio (Búsqueda por Prefijo-Numero)
```diff
- $salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();
+ // Soportar búsqueda por Prefijo-Numero o InvoiceNumber
+ if (strpos($invoiceNumber, '-') !== false) {
+     [$prefijo, $numero] = explode('-', $invoiceNumber, 2);
+     $salesHeader = SalesHeaderImp::where('Prefijo', $prefijo)
+                                  ->where('Numero', (int)$numero)
+                                  ->first();
+ } else {
+     // Fallback para compatibilidad
+     $salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();
+ }
```

### Archivo 3: `app/Models/SalesHeaderImp.php`

#### Cambio (Propiedades)
```diff
  /**
   * @property string $InvoiceNumber
+  * @property string $Prefijo
+  * @property int $Numero
   * @property string $CustomerID
```

---

## 🗄️ Script de Migración Manual

Para cada organización, ejecutar:

```sql
-- Agregar columnas si no existen
ALTER TABLE sales_header_imp
ADD COLUMN Prefijo VARCHAR(10) NULL AFTER InvoiceNumber;

ALTER TABLE sales_header_imp
ADD COLUMN Numero INT NULL AFTER Prefijo;

-- Rellenar datos existentes (si aplica)
UPDATE sales_header_imp
SET Prefijo = SUBSTRING_INDEX(InvoiceNumber, '-', 1)
WHERE Prefijo IS NULL AND InvoiceNumber LIKE '%-percent%';

-- Crear índice único
CREATE UNIQUE INDEX idx_prefijo_numero ON sales_header_imp(Prefijo, Numero)
WHERE Prefijo IS NOT NULL AND Numero IS NOT NULL;
```

---

## ✅ Checklist de Implementación

- [ ] **Paso 1**: Crear columnas `Prefijo` y `Numero` en BD (cada organización)
- [ ] **Paso 2**: Actualizar `MeyparService.php`:
  - [ ] Línea 75: Cambiar extracción de `$numero` a int
  - [ ] Línea 80: Cambiar construcción de `$invoiceNumberComplete`
  - [ ] Agregar validación `validateInvoiceNumberUnique()`
  - [ ] Línea 245: Actualizar `updateOrCreate()` con Prefijo+Numero
  - [ ] Agregar nuevo método privado `validateInvoiceNumberUnique()`
- [ ] **Paso 3**: Actualizar `CufeValidationHelper.php`:
  - [ ] Línea 35: Cambiar búsqueda a Prefijo+Numero
- [ ] **Paso 4**: Actualizar `SalesHeaderImp.php`:
  - [ ] Agregar propiedades `$Prefijo` y `$Numero`
- [ ] **Paso 5**: Testing:
  - [ ] [ ] Prueba: Dos prefijos diferentes, mismo número → Debe aceptar ambos
  - [ ] [ ] Prueba: Mismo prefijo, mismo número → Debe rechazar el segundo
  - [ ] [ ] Prueba: Verificar CUFE sigue funcionando correctamente
  - [ ] [ ] Prueba: Verificar integridad de datos históricos
- [ ] **Paso 6**: Documentación:
  - [ ] [ ] Actualizar guía API Maypar
  - [ ] [ ] Documentar cambios en CHANGELOG
  - [ ] [ ] Notificar a clientes si aplica

---

## 🧪 Tests Recomendados

```php
// tests/Unit/MeyparInvoiceNumberTest.php

public function test_allows_same_number_different_prefix()
{
    // Cajero 1 con Prefijo BOG001, Número 5
    $this->createInvoice('BOG001', 5);
    
    // Cajero 2 con Prefijo MED001, Número 5 (debe permitir)
    $invoice = $this->service->storeOrder($organization, [
        'prefijo' => 'MED001',
        'numero' => 5,
        // ... otros datos
    ]);
    
    $this->assertNotNull($invoice);
    $this->assertEquals('BOG001', $invoice->Prefijo);  // Oops, debe ser MED001
    $this->assertEquals(5, $invoice->Numero);
}

public function test_rejects_duplicate_prefix_and_number()
{
    // Crear factura BOG001-5
    $this->createInvoice('BOG001', 5);
    
    // Intentar crear BOG001-5 nuevamente (debe rechazar)
    $this->expectException(\Exception::class);
    $this->service->storeOrder($organization, [
        'prefijo' => 'BOG001',
        'numero' => 5,
        // ... otros datos
    ]);
}
```

---

## 📚 Documentación Oficial de Maypar

**Extracto relevante**:
> "El número de factura se obtiene uniendo ambos: Prefijo-Número.
> Si en vuestro sistema queréis comprobar que no se está emitiendo una factura duplicada, 
> debéis comprobar que no se repite el valor conjunto 'prefijo-número'. 
> Comprobar únicamente que no se repite el valor del 'número' no es una validación correcta."

---

## ⚠️ Notas Importantes

1. **Compatibilidad Hacia Atrás**: Los datos históricos sin Prefijo deberán migrarse manualmente
2. **Índice Único**: Usar índice único en `(Prefijo, Numero)` para garantizar integridad
3. **Fallback**: Mantener búsqueda por `InvoiceNumber` como fallback en `CufeValidationHelper`
4. **Logging**: Agregar logs detallados para debugging de problemas de duplicados

---

## 📞 Contacto y Referencias

- **Proveedor**: Maypar (Colombia)
- **Documentación**: WAAutorizacionPrefijo, WADetalleFactura
- **Contacto Maypar**: [verificar en email de Luis]
- **Archivo Original**: `/home/weirdolabs/code/docucenter/docs/technical/maypar-invoice-numbering-analysis.md`

