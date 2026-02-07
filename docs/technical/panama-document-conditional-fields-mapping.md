# Mapeo de Campos Condicionales por Tipo de Documento JSch09 iDoc

Basado en la Ficha Técnica DGI Panamá v1.0 - Análisis de campos obligatorios y condicionales por tipo de documento.

## Tipos de Documento Oficiales (JSch09 iDoc)

### 01 - Factura de Operación Interna
**Restricciones principales:**
- B14 NO puede ser 2 (extranjero) - solo operaciones nacionales
- B50 (datos exportación) NO debe existir
- B606 (referencia) NO debe existir

**Campos específicos:**
- Receptor: B401 (01, 02, 03) - NO extranjero
- Destino: B14 = 1 (Panamá obligatorio)

### 02 - Factura para Consumidor Final con Tasa Cero
**Restricciones:**
- Similar a 01 pero con tratamiento especial de ITBMS

### 03 - Factura de Exportación
**Campos OBLIGATORIOS:**
- B401 = 04 (Extranjero obligatorio)
- B14 = 2 (destino extranjero obligatorio)
- B50 (Grupo datos exportación) OBLIGATORIO que incluye:
  - B501: Condiciones de entrega (INCOTERMS) - OBLIGATORIO
  - B502: Moneda operación exportación - OBLIGATORIO si diferente USD
  - B504: Tipo de cambio - OBLIGATORIO si existe B502
  - B505: Monto moneda extranjera - OBLIGATORIO si existe B502
  - B506: Puerto de embarque - OPCIONAL

**Campos del receptor extranjero:**
- B406 (identificación extranjero) OBLIGATORIO
- B408: Identificación extranjera
- B409: Número pasaporte o ID tributaria extranjera
- B410: País extranjero - NO puede ser PA

### 04 - Nota de Crédito
**Campos OBLIGATORIOS:**
- B606: CUFE de FE referenciada - OBLIGATORIO
- B60 (Grupo datos documento referenciado) OBLIGATORIO que incluye:
  - B601: RUC emisor documento referenciado
  - B602: Nombre emisor referenciado
  - B603: Fecha documento referenciado
  - B604: Número documento referenciado

**Validaciones especiales:**
- D09 NO puede ser > monto FE referenciada
- NO puede referenciar otra nota de crédito
- Fecha emisión NO puede superar 180 días de FE original

### 05 - Nota de Débito
**Campos OBLIGATORIOS:**
- Mismos que Nota de Crédito (B606, B60)

**Validaciones especiales:**
- NO puede referenciar otra nota de débito
- Resto similar a nota de crédito

### 06 - Nota de Crédito Genérica
**Restricciones:**
- B606 NO debe existir (no referencia FE específica)

### 07 - Nota de Débito Genérica
**Restricciones:**
- B606 NO debe existir (no referencia FE específica)

### 08 - Factura de Zona Franca
**Restricciones:**
- B606 NO debe existir
- Tratamiento especial para zona franca

### 09 - Factura de Reembolso
**Campos específicos:**
- Tratamiento especial para reembolsos

## Campos Condicionales Detallados

### Receptor según Tipo
```
B401 = 01 (Contribuyente): 
  - B402 (RUC) OBLIGATORIO
  - gUbiRec OBLIGATORIO
  
B401 = 02 (Consumidor Final):
  - B402 (Cédula) OBLIGATORIO
  
B401 = 03 (Gobierno):
  - B402 (RUC) OBLIGATORIO
  - gUbiRec OBLIGATORIO
  
B401 = 04 (Extranjero):
  - B406 (grupo identificación) OBLIGATORIO
  - B408, B409 según tipo identificación
  - B410 (país) OBLIGATORIO y ≠ PA
```

### Destino Operación
```
B14 = 1 (Panamá):
  - B410 debe ser PA
  - B50 NO debe existir
  
B14 = 2 (Extranjero):
  - B410 NO puede ser PA
  - B50 OBLIGATORIO si B06=03
```

### Datos Exportación (B50)
```
Obligatorio si B06=03 Y B14=2:
  - B501 (INCOTERMS): De tabla 32
  - B502 (Moneda): De tabla 33, si ≠ USD
  - B503 (Descripción moneda): Si B502=ZZZ
  - B504 (Tipo cambio): Si existe B502
  - B505 (Monto moneda extranjera): Si existe B502
  - B506 (Puerto embarque): Opcional
```

### Referencias (B60/B606)
```
Obligatorio si B06 = 04 o 05:
  - B606 (CUFE referenciado): OBLIGATORIO
  - B601 (RUC emisor ref): OBLIGATORIO
  - B602 (Nombre emisor ref): OBLIGATORIO
  - B603 (Fecha doc ref): OBLIGATORIO
  - B604 (Número doc ref): OBLIGATORIO

Prohibido si B06 = 01, 06, 07, 08:
  - B606 NO debe existir
```

## Implementación en Livewire

### Estructura Condicional Requerida
```blade
<!-- Campos base siempre visibles -->
<div class="form-group">
    <select wire:model="tipeDocument">
        <!-- Tipos 01-09 -->
    </select>
</div>

<!-- Campos exportación - Solo si B06=03 -->
<div x-show="$wire.tipeDocument === '3'">
    <!-- B50: Grupo exportación -->
    <!-- B501: INCOTERMS -->
    <!-- B502: Moneda -->
    <!-- B504: Tipo cambio -->
    <!-- B505: Monto extranjero -->
    <!-- B506: Puerto embarque -->
</div>

<!-- Campos referencia - Solo si B06=04 o B06=05 -->
<div x-show="['4','5'].includes($wire.tipeDocument)">
    <!-- B606: CUFE referenciado -->
    <!-- B60: Datos documento referenciado -->
</div>

<!-- Receptor extranjero - Solo si B06=03 o receptor_tipo=04 -->
<div x-show="$wire.tipeDocument === '3' || $wire.receptor_tipo === '4'">
    <!-- B406: Identificación extranjero -->
    <!-- B408, B409: Documentos extranjero -->
    <!-- B410: País extranjero -->
</div>
```

## Validaciones JavaScript/Alpine
```javascript
// Validar coherencia tipo documento vs destino
if (tipeDocument === '3' && destinoOperacion !== '2') {
    // Error: Exportación requiere destino extranjero
}

// Validar receptor vs tipo documento
if (tipeDocument === '3' && receptor_tipo !== '4') {
    // Error: Exportación requiere receptor extranjero
}

// Mostrar/ocultar campos dinámicamente
$watch('tipeDocument', value => {
    // Lógica de visibilidad de campos
});
```
