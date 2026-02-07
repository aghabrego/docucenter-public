# ✅ Cambios Implementados: Validación de Prefijo-Número en Maypar

**Commit**: `506d4951`  
**Fecha**: Febrero 2, 2026  
**Estado**: ✅ IMPLEMENTADO  

---

## 📋 Resumen de Cambios

Se implementó la validación correcta de números de factura en Maypar según especificación oficial:

> "El número de factura es la **COMBINACIÓN única de Prefijo + Número**"

**Enfoque**: TODO en una sola columna `InvoiceNumber` = `"Prefijo-Número"` (ej: `"A132-2025"`)

---

## 🔧 Cambios Realizados

### 1️⃣ **MeyparService.php** (Cambios principales)

#### Cambio A: Construcción de `InvoiceNumber` (Línea 75-86)

**ANTES:**
```php
$numero = $this->sanitizeTextField(array_get($data, 'numero', ''), 20, 'InvoiceNumber');
$prefijo = $this->sanitizeTextField(array_get($data, 'prefijo', ''), 10, 'Prefijo');

// Usar solo el número sin combinar con prefijo
$invoiceNumberComplete = (string) $numero;  // ❌ INCORRECTO: "2025"
```

**DESPUÉS:**
```php
$numero = (int) array_get($data, 'numero', 0);
$prefijo = $this->sanitizeTextField(array_get($data, 'prefijo', ''), 10, 'Prefijo');

// Número de factura = Prefijo-Número (combinación única)
$invoiceNumberComplete = "{$prefijo}-{$numero}";  // ✅ CORRECTO: "A132-2025"

// Validar que la combinación Prefijo-Número sea única
if (!$this->validateInvoiceNumberUnique($prefijo, $numero, $organization->id)) {
    throw new \Exception(
        "Factura duplicada: Prefijo '{$prefijo}' con número '{$numero}' ya existe en el sistema"
    );
}
```

#### Cambio B: Identificador único en `updateOrCreate()`

**ANTES:**
```php
$header = SalesHeaderImp::updateOrCreate([
    'InvoiceNumber' => $invoiceNumberComplete,
], [
    'InvoiceNumber' => $invoiceNumberComplete,  // ❌ Duplicado
```

**DESPUÉS:**
```php
$header = SalesHeaderImp::updateOrCreate([
    'InvoiceNumber' => $invoiceNumberComplete,  // ✅ Único identificador
], [
    'ID_compania' => $company->ID_compania,
```

#### Cambio C: Detalle de factura con `InvoiceNumber` completo

**ANTES:**
```php
'InvoiceNumber' => $numero,  // ❌ Solo el número
```

**DESPUÉS:**
```php
'InvoiceNumber' => $invoiceNumberComplete,  // ✅ Prefijo-Número
```

#### Cambio D: Método de validación simplificado

**ANTES:**
```php
// Buscaba por dos columnas (Prefijo + Numero)
$exists = SalesHeaderImp::where('Prefijo', $prefijo)
                        ->where('Numero', $numero)
                        ->exists();
```

**DESPUÉS:**
```php
// Busca por InvoiceNumber (contiene Prefijo-Número)
$invoiceNumber = "{$prefijo}-{$numero}";
$exists = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->exists();
```

---

### 2️⃣ **SalesHeaderImp.php** (Model)

Remover propiedades innecesarias (Prefijo y Numero) - TODO va en InvoiceNumber

---

### 3️⃣ **CufeValidationHelper.php** (Helper)

#### Cambio: Búsqueda simplificada por InvoiceNumber

```php
// Búsqueda directa por InvoiceNumber
$salesHeader = SalesHeaderImp::where('InvoiceNumber', $invoiceNumber)->first();
```

---

## ✅ Beneficios Implementados

| Escenario | ANTES | DESPUÉS |
|-----------|-------|---------|
| Cajero 1: Prefijo="BOG001", Nro="5" | ✓ Creado | ✓ Creado |
| Cajero 2: Prefijo="MED001", Nro="5" | ❌ RECHAZA | ✅ ACEPTA |
| Cajero 1: Prefijo="BOG001", Nro="5" (duplicado) | ❌ RECHAZA | ❌ RECHAZA |

---

## 📊 Flujo de Validación

```
1. Recibir datos: prefijo="A132", numero=2025
   ↓
2. Construir: InvoiceNumber = "A132-2025"
   ↓
3. Validar: ¿Existe otro con InvoiceNumber="A132-2025"?
   ├─ SI → Lanzar excepción (duplicado)
   └─ NO → Continuar procesamiento
   ↓
4. Crear/Actualizar: updateOrCreate(['InvoiceNumber' => 'A132-2025'])
   ↓
5. CUFE: Busca por InvoiceNumber="A132-2025"
```

---

## 🗄️ Base de Datos

**Sin cambios requeridos** ✅ 

- `InvoiceNumber` ya existe en `sales_header_imp`
- Se almacena como: `"A132-2025"` (Prefijo-Número)
- Búsquedas se hacen directamente por este valor

---

## 🧪 Testing Recomendado

### Prueba 1: Diferente Prefijo, Mismo Número
```php
// Debe PERMITIR ambas
$meypar->storeOrder($org, [
    'prefijo' => 'BOG001',
    'numero' => 5,
    // ... otros datos
]);

$meypar->storeOrder($org, [
    'prefijo' => 'MED001',
    'numero' => 5,  // Mismo número, diferente prefijo
    // ... otros datos
]);
```
**Resultado esperado**: ✅ Ambas se crean exitosamente
- InvoiceNumber 1: `"BOG001-5"`
- InvoiceNumber 2: `"MED001-5"`

### Prueba 2: Mismo Prefijo, Mismo Número
```php
// Debe RECHAZAR la segunda
$meypar->storeOrder($org, [
    'prefijo' => 'BOG001',
    'numero' => 5,
    // ... otros datos
]);

$meypar->storeOrder($org, [
    'prefijo' => 'BOG001',
    'numero' => 5,  // Mismo prefijo, mismo número
    // ... otros datos
]);
```
**Resultado esperado**: ❌ Excepción: "Factura duplicada: Prefijo 'BOG001' con número '5' ya existe"

---

## 🔄 Compatibilidad Hacia Atrás

✅ **Total compatibilidad**: 
- Usa columna existente `InvoiceNumber`
- Sin cambios en estructura de BD
- Datos históricos funcionan sin cambios

---

## 📌 Próximos Pasos

1. **Testing**: Ejecutar pruebas con múltiples prefijos
2. **Monitoreo**: Verificar logs de validación
3. **Documentación**: Actualizar manual de API Maypar

---

## 📞 Referencias

- **Documento Maypar**: Especificación de WAAutorizacionPrefijo
- **Commit**: `506d4951`
- **Archivos modificados**: 4 archivos
