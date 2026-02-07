# Análisis de Unidades de Medida según Ficha Técnica DGI Panamá

## Documento Analizado
**Archivo**: `4-Anexo-3-Ficha-Técnica-Factura-Electrónica-Proveedores-Autorización-Calificados-V1.0.pdf`
**Fuente**: Dirección General de Ingresos (DGI) de Panamá
**Sección**: 10.3.2. Catálogo de Unidades de Medida (Tabla 29)

## Hallazgos Críticos

### 1. Uso Opcional de Unidades de Medida
> "La Tabla 29 contiene las unidades de medida en la Codificación Panameña de Bienes y Servicios, para utilización en el campo C11. **Este campo es de uso opcional**, pero cuando es utilizado, deberá contener obligatoriamente una de las secuencias de caracteres definidas en la columna "Símbolo" de aquella tabla."

### 2. Unidades de Medida Válidas Encontradas

#### Longitud (Sistema Métrico)
| Nombre | Símbolo | Comentario |
|--------|---------|------------|
| Micrómetro | **um** | 10⁻⁶ m |
| Milímetro | **mm** | 10⁻³ m |
| Centímetro | **cm** | 10⁻² m |
| Decímetro | **dm** | 10⁻¹ m |
| Metro | **m** | - |
| Decámetro | **dam** | 10 m |
| Hectómetro | **hm** | 100 m |
| Kilómetro | **km** | 1000 m |

#### Longitud (Otros Sistemas)
| Nombre | Símbolo | Comentario |
|--------|---------|------------|
| Milla internacional | **mi** | 1609.3 m |
| Milla náutica | **nmi** | 1.852 m |
| Pulgada | **in** | 2.54 cm |
| Pie | **ft** | 12 in, o 30.48 cm |
| Yarda | **yd** | 3 ft, o 91.44cm |

#### Otras Unidades Encontradas
| Categoría | Símbolo | Comentario |
|-----------|---------|------------|
| Peso | **und** | Aparece como unidad genérica |
| Información | **bit** | Bits por segundo |
| Otros | **ct** | Quilate |

## Problema Identificado

### Error PAC vs. Documentación Oficial

**Error PAC**: "El campo unidadMedida es inválido"
**Código actual**: Usaba "UND" por defecto
**Ejemplo TheFactoryHKA**: Usa "um" para exportación
**Documentación DGI**: Lista "um" como válido para micrómetro

### Discrepancia Encontrada
1. **TheFactoryHKA usa "um"** en su ejemplo oficial de factura de exportación
2. **DGI define "um"** como símbolo válido para micrómetro (10⁻⁶ m)
3. **Nuestro código usaba "UND"** que NO aparece en la lista oficial de símbolos DGI

## Conclusión Crítica

**CORRECCIÓN NECESARIA**: El código debe usar **"um"** para facturas de exportación según:
1. **Ejemplo oficial TheFactoryHKA**
2. **Documentación oficial DGI** (Tabla 29)
3. **Campo es opcional** pero si se usa debe ser de la lista oficial

**PROBLEMA**: "UND" NO aparece en la Tabla 29 oficial de la DGI como símbolo válido

## Recomendaciones

### Para Facturas de Exportación (Tipo 03)
- `unidadMedida`: **"um"** (micrómetro, según ejemplo oficial)
- `unidadMedidaCPBS`: **"cm"** (centímetro, según ejemplo oficial)

### Para Facturas Normales
- Usar unidades de la Tabla 29 oficial DGI
- Considerar **"um", "cm", "m"** como alternativas seguras
- Evitar "UND" ya que no aparece en documentación oficial

### Implementación Actual Correcta
El código actualizado usa:
```php
// Para exportación: 'um' y 'cm' según ejemplo oficial
$item->unidadMedida = $datos->tipoDocumento === '03' ? 'um' : 'UND';
$item->unidadMedidaCPBS = $datos->tipoDocumento === '03' ? 'cm' : 'UND';
```

**NOTA**: Considerar cambiar "UND" por una unidad oficial de la Tabla 29 DGI.
