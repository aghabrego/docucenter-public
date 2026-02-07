# Guía de Uso: Comando db:add-column-to-organizations-table

## Descripción
El comando `db:add-column-to-organizations-table` es una herramienta genérica para agregar columnas a tablas en todas las bases de datos de organizaciones del sistema DocuCenter.

## Sintaxis
```bash
php artisan db:add-column-to-organizations-table {table} {column} {type} [opciones]
```

## Parámetros Requeridos
- `{table}` - Nombre de la tabla donde agregar la columna
- `{column}` - Nombre de la nueva columna
- `{type}` - Tipo de dato de la columna

## Opciones Disponibles
- `--unsigned=0` - Si la columna es unsigned (solo números positivos)
- `--length=` - Longitud de la columna (para strings)
- `--nullable=1` - Si la columna permite valores NULL (1=sí, 0=no)
- `--default=` - Valor por defecto de la columna
- `--autoIncrement=0` - Si la columna es auto incrementable
- `--total=` - Total de dígitos (para decimales/floats)
- `--places=` - Dígitos después del punto decimal
- `--precision=` - Precisión (para datetime/time/timestamp)
- `--index=0` - Si crear índice en la columna (1=sí, 0=no)

## Tipos de Datos Soportados
- `string` - Cadena de texto (varchar)
- `integer` - Número entero
- `bigint` - Número entero grande
- `float` - Número decimal flotante
- `double` - Número decimal doble precisión
- `decimal` - Número decimal exacto
- `boolean` - Verdadero/Falso
- `date` - Fecha (YYYY-MM-DD)
- `datetime` - Fecha y hora
- `time` - Hora
- `timestamp` - Timestamp

## Ejemplo: Agregar columna fiscal_document_number

### Usando el comando directamente:
```bash
php artisan db:add-column-to-organizations-table \
  Sales_Header_Imp \
  fiscal_document_number \
  string \
  --length=20 \
  --nullable=1 \
  --index=1
```

### Usando el script automatizado:
```bash
./scripts/add-fiscal-document-number-column.sh
```

## Funcionalidad

### Proceso Automático:
1. **Itera** todas las organizaciones que tienen base de datos asignada
2. **Verifica** si la tabla existe en cada base de datos
3. **Comprueba** si la columna ya existe (evita duplicados)
4. **Agrega** la columna con las especificaciones dadas
5. **Crea índice** si se especifica (mejora performance)
6. **Reporta** el resultado para cada organización

### Características de Seguridad:
- **No duplica columnas** - Verifica existencia antes de crear
- **Validación de tablas** - Solo procesa si la tabla existe
- **Rollback automático** - Si falla en una BD, continúa con las demás
- **Logs detallados** - Informa éxito/error para cada organización

## Ventajas sobre Comandos Específicos

### Comando Genérico (`db:add-column-to-organizations-table`):
- **Reutilizable** para cualquier tabla y columna
- **Flexible** con múltiples tipos de datos y opciones
- **Mantenible** - Un solo código para todas las necesidades
- **Probado** en producción con múltiples columnas

### Comandos Específicos (como `AddFiscalDocumentNumberColumnCommand`):
- Código duplicado para cada columna
- Difícil de mantener múltiples archivos
- Menos flexible para cambios futuros
- Mayor complejidad del proyecto

## Casos de Uso Comunes

### Agregar columna de texto:
```bash
php artisan db:add-column-to-organizations-table \
  tabla_ejemplo \
  nueva_columna \
  string \
  --length=100 \
  --nullable=1
```

### Agregar columna numérica con índice:
```bash
php artisan db:add-column-to-organizations-table \
  tabla_ejemplo \
  contador \
  integer \
  --nullable=0 \
  --default=0 \
  --index=1
```

### Agregar columna de fecha:
```bash
php artisan db:add-column-to-organizations-table \
  tabla_ejemplo \
  fecha_proceso \
  datetime \
  --nullable=1
```

## Aplicación: fiscal_document_number

La columna `fiscal_document_number` se agregará a `Sales_Header_Imp` con estas características:
- **Tipo**: `string` con longitud 20 caracteres
- **Nullable**: Sí (facturas existentes sin número fiscal)
- **Índice**: Sí (para búsquedas rápidas de duplicados)
- **Propósito**: Validación de duplicados en integración QuickBooks

Esta columna será poblada automáticamente por:
- `CreateSaleQuickBooksJob` (facturas nuevas desde API)
- `UpdateIntuitOrdersJob` (facturas existentes en sincronización)
