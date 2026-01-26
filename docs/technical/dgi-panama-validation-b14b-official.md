# Validación DGI Panamá: Destino vs Tipo de Documento

## Regla Oficial DGI

**Fuente**: Anexo 3 - Ficha Técnica Factura Electrónica Proveedores Autorización Calificados V1.0

### Código de Validación B14b
- **Código**: 1534
- **Descripción**: "El destino de la operación no puede ser Extranjero si el Tipo de Documento es Factura de Operación Interna"
- **Condición**: B06=01 y B14=2
- **Tipo**: Error de Rechazo (R)
- **Versión**: 1.00

## Explicación Técnica

### Campos Involucrados

#### Campo B06 - Tipo de Documento
| Código | Descripción |
|--------|-------------|
| **01** | **Factura de operación interna** |
| 02 | Factura de importación |
| 03 | Factura de exportación |
| 04 | Nota de Crédito referente a una o varias FE |
| 05 | Nota de Débito referente a una o varias FE |
| 06 | Nota de Crédito Genérica |
| 07 | Nota de Débito Genérica |
| 08 | Factura de Zona Franca |
| 09 | Factura de Reembolso |

#### Campo B14 - Destino de la Operación
| Código | Descripción |
|--------|-------------|
| **1** | **Nacional (Panamá)** |
| **2** | **Extranjero (fuera de Panamá)** |

### Regla de Negocio DGI
```
SI Tipo_Documento = "01" (Factura de Operación Interna)
ENTONCES Destino_Operacion DEBE SER = "1" (Nacional)
```

**Violación**: Si B06=01 AND B14=2 → Error 1534 (Rechazo)

## 🏛Justificación Legal/Fiscal

### Concepto: "Operación Interna"
Una **Factura de Operación Interna** (tipo 01) por definición legal es:
- Operación comercial que ocurre **dentro del territorio panameño**
- Ambas partes (emisor y receptor) están sujetas a la jurisdicción fiscal de Panamá
- Aplicable al régimen tributario interno de Panamá
- **No puede** tener carácter internacional por definición

### Lógica Fiscal
1. **Operación Interna** = Dentro de jurisdicción fiscal panameña
2. **Destino Extranjero** = Fuera de jurisdicción fiscal panameña
3. **Contradicción** = Una operación no puede ser simultáneamente interna Y externa

## Implementación en DocuCenter

### Solución en AlanubeFormatterHelper.php
```php
private static function determineDestination(array $data): int
{
    // REGLA DGI OFICIAL: B06=01 → B14=1 (OBLIGATORIO)
    $documentType = $data['dGen']['iTipoDoc'] ?? null;
    if ($documentType === '01') {
        return 1; // Nacional (cumple validación DGI 1534)
    }
    
    // Resto de lógica para otros tipos de documento...
}
```

### Casos de Uso Cubiertos

#### Escenario Válido: Factura Interna Nacional
```json
{
  "iTipoDoc": "01",  // Factura Operación Interna
  "iDest": 1         // Nacional
}
// Estado: APROBADO por DGI
```

#### Escenario Inválido: Factura Interna Extranjera
```json
{
  "iTipoDoc": "01",  // Factura Operación Interna
  "iDest": 2         // Extranjero
}
// Estado: RECHAZADO por DGI (Error 1534)
```

#### Escenario Válido: Exportación Extranjera
```json
{
  "iTipoDoc": "03",  // Factura de Exportación
  "iDest": 2         // Extranjero
}
// Estado: APROBADO por DGI
```

## Matriz de Validaciones por Tipo

| Tipo Doc | Descripción | Destino=1 | Destino=2 | Validación DGI |
|----------|-------------|-----------|-----------|----------------|
| 01 | Op. Interna | Válido | Error 1534 | **FORZADO Nacional** |
| 02 | Importación | Válido | Válido | Según origen |
| 03 | Exportación | Válido | Válido | Según destino |
| 04 | Nota Crédito | Válido | Válido* | Hereda del original |
| 05 | Nota Débito | Válido | Válido* | Hereda del original |

*Dependiendo del documento referenciado

## 🚫 Casos de Error Relacionados

### Error 1533 - Exportación Nacional
- **Condición**: B06=03 Y B14=1
- **Descripción**: Factura de Exportación no puede tener destino Nacional
- **Lógica**: Exportación implica salida del país

### Error 1534 - Operación Interna Extranjera
- **Condición**: B06=01 Y B14=2
- **Descripción**: Factura de Operación Interna no puede tener destino Extranjero
- **Lógica**: Operación interna implica dentro del país

## Impacto en Integración PAC

### Antes de la Corrección
```
DocumentType=01 + Receptor_País=US
↓
Destination=2 (lógica por país)
↓
Error DGI 1534: "destination cannot be Foreign if Document Type is Internal Operation Invoice"
↓
Factura RECHAZADA
```

### Después de la Corrección
```
DocumentType=01 + Receptor_País=US
↓
Destination=1 (forzado por tipo documento)
↓
Pasa validación DGI 1534
↓
Factura APROBADA
```

## Notas de Implementación

### Prioridad de Reglas
1. **Tipo de Documento** (prioritario sobre país)
2. País de destino de mercancía
3. País del receptor
4. Campo iDest original

### Testing Requerido
- Factura tipo 01 con receptor panameño → destination=1
- Factura tipo 01 con receptor extranjero → destination=1 (forzado)
- Factura tipo 03 con receptor extranjero → destination=2
- Verificar logs de validación DGI

## Referencias Oficiales

- **Documento**: Anexo 3 - Ficha Técnica FE para PAC V1.0
- **Sección**: Validaciones de Estructura y Contenido
- **Página**: Códigos de validación 1533-1534
- **Autoridad**: Dirección General de Ingresos (DGI) - Panamá
- **Estado**: Vigente desde implementación SFE

---
**Conclusión**: Esta validación es una regla de negocio **oficial y obligatoria** de la DGI de Panamá, no una restricción específica del PAC Alanube. Todos los PACs autorizados deben implementar esta validación según la especificación técnica oficial.
