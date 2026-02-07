# API Contabilidad General - Sage ACICloud

## Crear Asiento Contable Individual

**Endpoint:** `POST /api/acicloud/general_journal_entry`  
**Controller:** `ACIcloudController::generalJournalEntry`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `entry_number` | string | | Número único del asiento | `"GJE-2025-001"` |
| `date` | string | | Fecha del asiento (YYYY-MM-DD) | `"2025-01-29"` |
| `description` | string | | Descripción del asiento | `"Ajuste de depreciación enero"` |
| `reference` | string | | Referencia externa | `"DEP-2025-01"` |
| `source` | string | | Origen del asiento | `"MANUAL"` |
| `lines` | array | | Líneas del asiento (mín. 2) | `[]` |
| `lines[].account_code` | string | | Código de cuenta contable | `"1200"` |
| `lines[].description` | string | | Descripción de la línea | `"Depreciación acumulada equipos"` |
| `lines[].debit_amount` | decimal | | Monto débito | `1500.00` |
| `lines[].credit_amount` | decimal | | Monto crédito | `0.00` |
| `lines[].department` | string | | Departamento | `"ADMIN"` |
| `lines[].project` | string | | Proyecto asociado | `"PROJ001"` |
| `lines[].memo` | string | | Memo adicional | `"Depreciación mensual"` |

### Validaciones

- **entry_number**: Único, máximo 50 caracteres
- **date**: Formato YYYY-MM-DD, no futuro
- **lines**: Mínimo 2 líneas requeridas
- **Balance**: Suma de débitos debe igualar suma de créditos
- **account_code**: Debe existir en el plan de cuentas
- **amounts**: Solo positivos, se requiere debit_amount O credit_amount

### Ejemplo de Request

```json
{
  "entry_number": "GJE-2025-001",
  "date": "2025-01-29",
  "description": "Ajuste de depreciación enero 2025",
  "reference": "DEP-2025-01",
  "source": "MANUAL",
  "lines": [
    {
      "account_code": "6200",
      "description": "Gasto por depreciación - Equipos",
      "debit_amount": 1500.00,
      "credit_amount": 0.00,
      "department": "ADMIN",
      "memo": "Depreciación mensual equipos de oficina"
    },
    {
      "account_code": "1250",
      "description": "Depreciación acumulada - Equipos",
      "debit_amount": 0.00,
      "credit_amount": 1500.00,
      "department": "ADMIN", 
      "memo": "Acumulación depreciación enero"
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Asiento contable creado exitosamente",
  "data": {
    "entry_number": "GJE-2025-001",
    "sage_id": "SAGE_GJE_001",
    "total_debit": 1500.00,
    "total_credit": 1500.00,
    "lines_count": 2,
    "balance_verified": true,
    "created_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Crear Múltiples Asientos Contables

**Endpoint:** `POST /api/acicloud/general_journal_entries`  
**Controller:** `ACIcloudController::generalJournalEntries`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `batch_number` | string | | Número de lote | `"BATCH-2025-001"` |
| `batch_date` | string | | Fecha del lote | `"2025-01-29"` |
| `batch_description` | string | | Descripción del lote | `"Asientos de cierre enero 2025"` |
| `entries` | array | | Lista de asientos contables | `[]` |

Cada elemento en `entries` debe tener la misma estructura que el endpoint individual.

### Ejemplo de Request

```json
{
  "batch_number": "BATCH-2025-001",
  "batch_date": "2025-01-29",
  "batch_description": "Asientos de cierre enero 2025",
  "entries": [
    {
      "entry_number": "GJE-2025-001",
      "date": "2025-01-29",
      "description": "Ajuste de depreciación enero",
      "lines": [
        {
          "account_code": "6200",
          "description": "Gasto por depreciación",
          "debit_amount": 1500.00,
          "credit_amount": 0.00
        },
        {
          "account_code": "1250",
          "description": "Depreciación acumulada",
          "debit_amount": 0.00,
          "credit_amount": 1500.00
        }
      ]
    },
    {
      "entry_number": "GJE-2025-002",
      "date": "2025-01-29",
      "description": "Provisión cuentas por cobrar",
      "lines": [
        {
          "account_code": "6300",
          "description": "Provisión cuentas incobrables",
          "debit_amount": 500.00,
          "credit_amount": 0.00
        },
        {
          "account_code": "1150",
          "description": "Provisión acumulada CxC",
          "debit_amount": 0.00,
          "credit_amount": 500.00
        }
      ]
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Lote de asientos contables procesado exitosamente",
  "data": {
    "batch_number": "BATCH-2025-001",
    "entries_processed": 2,
    "entries_successful": 2,
    "entries_failed": 0,
    "total_debit": 2000.00,
    "total_credit": 2000.00,
    "processing_time": "00:00:05",
    "created_at": "2025-01-29 21:30:00",
    "results": [
      {
        "entry_number": "GJE-2025-001",
        "sage_id": "SAGE_GJE_001",
        "status": "success",
        "message": "Asiento creado exitosamente"
      },
      {
        "entry_number": "GJE-2025-002",
        "sage_id": "SAGE_GJE_002",
        "status": "success",
        "message": "Asiento creado exitosamente"
      }
    ]
  }
}
```

---

## Obtener Asientos Importados

**Endpoint:** `GET /api/acicloud/general_journal_entry_imp`  
**Controller:** `ACIcloudController::getGeneralJournalEntryImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `page` | integer | | Página a obtener | `2` |
| `per_page` | integer | | Registros por página | `50` |
| `batch_number` | string | | Filtrar por lote | `"BATCH-2025-001"` |
| `date_from` | string | | Fecha desde | `"2025-01-01"` |
| `date_to` | string | | Fecha hasta | `"2025-01-31"` |

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "entry_number": "GJE-2025-001",
      "date": "2025-01-29",
      "description": "Ajuste de depreciación enero",
      "sage_id": "SAGE_GJE_001",
      "batch_number": "BATCH-2025-001",
      "total_debit": 1500.00,
      "total_credit": 1500.00,
      "lines_count": 2,
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "updated_at": "2025-01-29 21:30:00",
      "errors": null
    },
    {
      "id": 2,
      "entry_number": "GJE-2025-002",
      "date": "2025-01-29",
      "description": "Provisión cuentas por cobrar",
      "sage_id": "SAGE_GJE_002",
      "batch_number": "BATCH-2025-001",
      "total_debit": 500.00,
      "total_credit": 500.00,
      "lines_count": 2,
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "updated_at": "2025-01-29 21:30:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 25,
    "total_pages": 1
  },
  "summary": {
    "total_entries": 25,
    "total_debit": 125000.00,
    "total_credit": 125000.00,
    "successful_imports": 25,
    "failed_imports": 0
  }
}
```

---

## Crear Ventas Contables

**Endpoint:** `POST /api/acicloud/sales`  
**Controller:** `ACIcloudController::salesImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `sale_number` | string | | Número de venta | `"SALE-2025-001"` |
| `customer_id` | string | | ID del cliente | `"CUST001"` |
| `date` | string | | Fecha de la venta | `"2025-01-29"` |
| `due_date` | string | | Fecha de vencimiento | `"2025-02-28"` |
| `currency` | string | | Moneda | `"USD"` |
| `exchange_rate` | decimal | | Tipo de cambio | `1.00` |
| `payment_terms` | string | | Términos de pago | `"NET30"` |
| `reference` | string | | Referencia | `"ORD-001"` |
| `items` | array | | Items de venta | `[]` |
| `items[].product_code` | string | | Código del producto | `"PROD001"` |
| `items[].description` | string | | Descripción | `"Laptop Dell"` |
| `items[].quantity` | decimal | | Cantidad | `2.00` |
| `items[].unit_price` | decimal | | Precio unitario | `1200.00` |
| `items[].tax_rate` | decimal | | Tasa de impuesto | `0.07` |
| `items[].discount_percent` | decimal | | Descuento porcentual | `5.00` |

### Ejemplo de Request

```json
{
  "sale_number": "SALE-2025-001",
  "customer_id": "CUST001",
  "date": "2025-01-29",
  "due_date": "2025-02-28",
  "currency": "USD",
  "payment_terms": "NET30",
  "reference": "ORD-001",
  "items": [
    {
      "product_code": "PROD001",
      "description": "Laptop Dell Inspiron 15",
      "quantity": 2.00,
      "unit_price": 1200.00,
      "tax_rate": 0.07,
      "discount_percent": 0.00
    },
    {
      "product_code": "PROD002",
      "description": "Mouse Inalámbrico",
      "quantity": 2.00,
      "unit_price": 25.00,
      "tax_rate": 0.07,
      "discount_percent": 0.00
    }
  ]
}
```

### Respuesta de Éxito

```json
{
  "success": true,
  "message": "Venta creada exitosamente",
  "data": {
    "sale_number": "SALE-2025-001",
    "sage_id": "SAGE_SALE_001",
    "subtotal": 2450.00,
    "tax_amount": 171.50,
    "total_amount": 2621.50,
    "created_at": "2025-01-29 21:30:00",
    "sync_status": "completed"
  }
}
```

---

## Obtener Ventas Importadas

**Endpoint:** `GET /api/acicloud/sales_imp`  
**Controller:** `ACIcloudController::getSalesImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sale_number": "SALE-2025-001",
      "customer_id": "CUST001",
      "customer_name": "ABC Corporation S.A.",
      "sage_id": "SAGE_SALE_001",
      "date": "2025-01-29",
      "total_amount": 2621.50,
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 45,
    "total_pages": 2
  }
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Asiento/Venta creado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 422 | Error de validación / Balance no cuadra |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

#### Crear Asiento Individual
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/general_journal_entry" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "entry_number": "GJE-2025-001",
    "date": "2025-01-29",
    "description": "Ajuste de depreciación",
    "lines": [
      {
        "account_code": "6200",
        "description": "Gasto depreciación",
        "debit_amount": 1500.00,
        "credit_amount": 0.00
      },
      {
        "account_code": "1250",
        "description": "Depreciación acumulada",
        "debit_amount": 0.00,
        "credit_amount": 1500.00
      }
    ]
  }'
```

#### Crear Lote de Asientos
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/general_journal_entries" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "batch_number": "BATCH-2025-001",
    "batch_date": "2025-01-29",
    "batch_description": "Asientos de cierre enero",
    "entries": [...]
  }'
```

#### Crear Venta
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/sales" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sale_number": "SALE-2025-001",
    "customer_id": "CUST001",
    "date": "2025-01-29",
    "items": [
      {
        "product_code": "PROD001",
        "description": "Laptop Dell",
        "quantity": 1.00,
        "unit_price": 1200.00
      }
    ]
  }'
```

---

## Notas Técnicas

- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Balance Contable**: Se valida que débitos = créditos automáticamente
- **Plan de Cuentas**: Se valida que las cuentas existan en el sistema
- **Numeración**: Los números de asiento deben ser únicos
- **Fechas**: No se permiten fechas futuras para asientos
- **Monedas**: Soporte para múltiples monedas con tipo de cambio

### Principios Contables

- **Partida Doble**: Cada asiento debe tener al menos una línea débito y crédito
- **Balance**: Suma de débitos debe igualar suma de créditos
- **Integridad**: Validación de cuentas contra plan de cuentas
- **Trazabilidad**: Cada asiento mantiene referencia a su origen
- **Inmutabilidad**: Los asientos no pueden modificarse una vez creados

### Integración con Sage ACICloud

Los asientos contables se integran directamente con el libro mayor de Sage ACICloud, manteniendo la integridad contable y permitiendo reportes consolidados.
