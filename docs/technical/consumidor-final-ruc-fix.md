# Solución: Error RUC en Consumidor Final

## Problema

```
instance.receiver.ruc requires property "ruc"
```

**Contexto**: Al emitir facturas para consumidores finales (tipo receptor = 2) con RUC vacío, el PAC rechaza la factura porque espera la propiedad "ruc" dentro de "gRucRec".

## Causa Raíz

En el método `issueDocument()` del componente Livewire, para consumidores finales se estaba enviando siempre la estructura `gRucRec` incluso cuando `$this->receptor_ruc` estaba vacío:

```php
// CÓDIGO PROBLEMÁTICO (ANTES)
case '2':
    $dGen['gDatRec'] = array_merge($dGen['gDatRec'], $this->validArray(['gRucRec' => $this->validArray([
        'dRuc' => $this->receptor_ruc, // Podía ser null/vacío
    ])]));
```

## Solución Implementada

**Archivo**: `app/Http/Livewire/Admin/Einvoice/Create.php`  
**Línea**: ~1644-1656

```php
// CÓDIGO CORREGIDO (DESPUÉS)
case '2':
    // Para consumidor final, solo incluir gRucRec si hay RUC
    if (!empty($this->receptor_ruc) && $this->receptor_ruc !== '0-0-0') {
        $dGen['gDatRec'] = array_merge($dGen['gDatRec'], ['gRucRec' => [
            'dRuc' => $this->receptor_ruc,
        ]]);
    }
    $dGen['gDatRec'] = array_merge($dGen['gDatRec'], $this->validArray([
        'dNombRec' => $this->receptor_razonSocial,
        'dTfnRec' => $this->receptor_telefono,
        'dCorElectRec' => $this->receptor_correoElectronico,
        'cPaisRec' => $receptorPaisDestinoOperacion?->code ?: 'PA',
        'dPaisRecDesc' => $receptorPaisDestinoOperacion?->name ?: 'Panama',
    ]));
```

## Lógica de la Solución

1. **RUC vacío o null**: NO incluir `gRucRec` en la estructura XML
2. **RUC = "0-0-0"**: NO incluir `gRucRec` (es un RUC genérico/placeholder)
3. **RUC válido**: SÍ incluir `gRucRec` con `dRuc`

## Beneficios

- ✅ **Compatibilidad PAC**: Evita errores de validación en TheFactoryHKA y Alanube
- ✅ **Flexibilidad**: Permite facturas a consumidores sin RUC
- ✅ **Cumplimiento DGI**: Mantiene estructura correcta según ficha técnica
- ✅ **Robustez**: Maneja casos edge como RUC genérico "0-0-0"

## Casos de Uso

### ✅ Caso 1: Consumidor sin RUC
```php
$this->receptor_ruc = null; // o ''
// Resultado: NO se incluye gRucRec en XML
```

### ✅ Caso 2: Consumidor con RUC genérico  
```php
$this->receptor_ruc = '0-0-0';
// Resultado: NO se incluye gRucRec en XML
```

### ✅ Caso 3: Consumidor con RUC válido
```php
$this->receptor_ruc = '8-123-456';
// Resultado: SÍ se incluye gRucRec con dRuc: '8-123-456'
```

## Testing

Para verificar la solución:

1. **Frontend**: Crear factura a consumidor final sin RUC
2. **Validar**: Que no aparezca error "requires property ruc"
3. **XML generado**: Verificar que `gRucRec` no esté presente cuando RUC es vacío

## Notas de Implementación

- **Fecha**: 2025-09-23
- **Ubicación**: Caso '2' en método `issueDocument()`
- **Impacto**: Solo afecta a consumidores finales (tipo receptor = 2)
- **Compatibilidad**: Mantiene compatibilidad con otros tipos de receptor

---

**Resultado**: Error "instance.receiver.ruc requires property ruc" solucionado para consumidores finales.
