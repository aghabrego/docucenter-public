# Test de Extracción de CUFE

## Descripción

Este documento describe los tests implementados para validar el método `extractCufeFromInvoiceNote` del `UpdateIntuitOrdersJob`, que es responsable de extraer códigos CUFE (Código Único de Facturación Electrónica) desde diferentes formatos de `InvoiceNote`.

## Ubicación de Tests

- **Test Principal**: `tests/Unit/Jobs/ExtractCufeFromInvoiceNoteTest.php`
- **Test Simplificado**: `docs/testing/simple-extract-cufe-test.php` (para depuración sin Laravel)

## Estrategias de Extracción Validadas

### 1. Extracción desde JSON Parseado
- **Clave principal**: `cufe`
- **PAC Response**: `pac_response.full_content.EnviarResult.cufe`
- **Processed Message**: `pac_response.processed_message.cufe`

### 2. Extracción con Regex
- **Patrón**: `/FE[0-9-]{40,}/`
- **Descripción**: Busca patrones que inicien con "FE" seguido de dígitos y guiones, mínimo 40 caracteres

### 3. Reparación de JSON
- Intenta reparar JSON truncado agregando llaves faltantes
- Re-parsea y extrae CUFE del JSON reparado

## Casos de Test Implementados

### Test Suite Completo (`ExtractCufeFromInvoiceNoteTest.php`)

1. **JSON Completo**: Valida extracción desde JSON bien formado
2. **JSON Incompleto**: Valida extracción con regex de JSON truncado
3. **PAC Response**: Valida extracción desde estructura `pac_response`
4. **Processed Message**: Valida extracción desde `processed_message`
5. **Texto Plano**: Valida extracción con regex de texto sin JSON
6. **JSON Reparado**: Valida reparación y extracción de JSON malformado
7. **Sin CUFE**: Valida retorno `null` cuando no hay CUFE
8. **Datos Vacíos**: Valida manejo de `InvoiceNote` vacío o `null`
9. **Validación Regex**: Valida patrones CUFE válidos e inválidos
10. **Múltiples CUFEs**: Valida extracción del primer CUFE encontrado

## Formato CUFE Validado

**Patrón Real**: `FE0120000155757563-2-2024-4800002025090100000028680010112112653159`

**Estructura**:
- `FE`: Prefijo fijo
- `01`: Código de tipo
- `20000155757563`: Identificador de emisor (14 dígitos)
- `-2-`: Separador y número secuencial
- `2024`: Año
- `-4800002025090100000028680010112112653159`: Resto del código único

## Ejecución de Tests

### Test Completo (Laravel)
```bash
# Ejecutar test específico
php artisan test tests/Unit/Jobs/ExtractCufeFromInvoiceNoteTest.php

# Ejecutar con cobertura
php artisan test tests/Unit/Jobs/ExtractCufeFromInvoiceNoteTest.php --coverage

# Ejecutar grupo de tests intuit
php artisan test --group=intuit

# Ejecutar grupo cufe-extraction
php artisan test --group=cufe-extraction
```

### Test Simplificado (Sin Laravel)
```bash
# Test independiente para depuración rápida
cd /home/weirdolabs/code/docucenter
php docs/testing/simple-extract-cufe-test.php
```

## Resultados Esperados

Todos los tests deben pasar exitosamente:
- ✅ 10/10 tests en `ExtractCufeFromInvoiceNoteTest.php`
- ✅ 7/7 tests en `simple-extract-cufe-test.php`

## Casos Edge Manejados

1. **JSON Truncado**: Por timeouts o límites de almacenamiento
2. **JSON Malformado**: Llaves faltantes al final
3. **Múltiples Estructuras**: Diferentes formatos de respuesta PAC
4. **Texto Plano**: CUFE embebido en mensajes de texto
5. **Datos Vacíos**: Manejo robusto de casos null/vacíos

## Integración con CI/CD

Los tests están configurados para ejecutarse automáticamente en el pipeline de CI:

```yaml
# En .github/workflows o equivalente
- name: Run CUFE Extraction Tests
  run: php artisan test tests/Unit/Jobs/ExtractCufeFromInvoiceNoteTest.php --teamcity
```

## Debugging

Para depurar problemas específicos:

1. **Usar test simplificado**: Ejecutar `simple-extract-cufe-test.php` para validación rápida
2. **Logs detallados**: El método incluye logging para troubleshooting
3. **Datos reales**: Usar formato exacto de `InvoiceNote` de producción

## Mantenimiento

- **Actualizar regex**: Si cambia el formato CUFE panameño
- **Nuevos casos**: Agregar tests para nuevos formatos PAC
- **Performance**: Monitorear tiempo de extracción en datasets grandes
