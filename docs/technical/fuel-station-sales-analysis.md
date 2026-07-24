# Analisis: Registro de Ventas de Turno de Gasolinera

Fecha de analisis: 2026-05-18  
Modulo destino: Sage 50  
Fuente: Formulario fisico de turno (captura proporcionada por el cliente)

---

## Descripcion del Formulario

El cliente usa un formulario de papel para registrar el cierre de turno de una estacion de gasolina ("4 ALTOS"). El formulario tiene las siguientes secciones:

- Encabezado: vendedor, fecha, turno, estacion
- Tabla de bombas: surtidora, producto, lecturas de medidor (inicial/final), cantidad, litros, dinero
- Formas de pago: efectivo (Brinks/Chuta), tarjetas (Clave/Mastercard/Visa/Pris), creditos (Flota/Colven), Yappy, transferencia
- Productos adicionales: Tropigas, Lubricante
- Confirmacion Yappy y Transferencia Bancaria
- Gran Total

---

## Mapeo a la Base de Datos

### Sales_Header_Imp (encabezado de la venta)

| Campo del formulario | Campo en tabla        | Ejemplo / Logica                        |
|----------------------|-----------------------|-----------------------------------------|
| NOMBRE               | `SalesRepID`          | `Brayan`                                |
| FECHA                | `Date`                | `2026-01-13`                            |
| Estacion (4 ALTOS)   | `IdStore`             | `4 ALTOS`                               |
| TURNO                | `CustomerPO`          | `GA2`                                   |
| Cliente asignado     | `CustomerID` / `CustomerName` | seleccion en pantalla         |
| GRAN TOTAL ventas    | `Subtotal`            | `1075.44` (suma de lineas positivas)    |
| Net_due              | `Net_due`             | **`0.00`** (positivos + negativos = 0) |
| Origen del registro  | `origin`              | `fuel-station`                          |
| Numero de factura    | `InvoiceNumber`       | Auto: `TURNO-20260113-GA2`              |

### Sales_Detail_Imp (lineas de combustible)

Cada fila de la tabla de bombas genera un registro:

| Campo del formulario | Campo en tabla   | Logica                                         |
|----------------------|------------------|------------------------------------------------|
| SURTIDORA            | `JobID`          | Numero de bomba (1, 8)                         |
| PRODUCTO             | `Item_Code`      | DIESEL / 91 / 95                               |
| INICIAL + FINAL      | `Description`    | Concatenar: `"DIESEL (1669688-1669831)"`       |
| CANTIDAD             | `Quantity`       | Galones dispensados (143, 133, etc.)           |
| LITROS               | `JobPhaseID`     | Campo auxiliar reutilizado (o campo nuevo)     |
| DINERO               | `Sub_Total` / `Net_line` | Monto en USD (179.46, 155.84, etc.)  |
| Secuencia de linea   | `Sequential`     | Numero de fila (1, 2, 3...)                    |

**Regla clave para Description:**
```
Description = "{PRODUCTO} ({INICIAL}-{FINAL})"
Ejemplo:      "DIESEL (1669688-1669831)"
              "91 (7882273-7333367)"
              "95 (7587701-7590031)"
```

### Formas de pago → Sales_Detail_Imp (lineas negativas)

**Definicion de Apcon Consulting (2026-05-19):**
> "Se registra el monto de las ventas en positivo, luego las formas de pago en negativo, lo que debera totalizar en 0.00"

NO se usa tabla separada ni `customer_receipt_header_imp`. Las formas de pago se registran como **lineas adicionales en `Sales_Detail_Imp` con valores negativos**, de modo que la suma total de todas las lineas (combustible + pagos) sea `0.00`.

**IMPORTANTE: Captura en pantalla independiente**
- **En la UI**: El usuario captura las bombas en una tabla y las formas de pago en otra seccion independiente (campos de input separados).
- **En la BD**: Todo se guarda en `Sales_Detail_Imp`, las ventas como lineas positivas y los pagos como lineas negativas.
- **Flujo**: El backend toma los arrays separados (`bombas[]` y `pagos[]`) y los convierte en lineas de `Sales_Detail_Imp` con signos opuestos.

#### Regla de poblado

```
Lineas positivas  = ventas de combustible  → Sub_Total > 0
Lineas negativas  = formas de pago         → Sub_Total < 0
Suma de detalles  = 0.00
Sales_Header_Imp.Net_due = 0.00
Sales_Header_Imp.Subtotal = total ventas (suma positivos)
```

#### Ejemplo con los datos de la captura

```
--- VENTAS (positivo) ---
Item_Code: DIESEL   Description: DIESEL (1669688-1669831)   Sub_Total: +179.46
Item_Code: 91       Description: 91 (7882273-7333367)       Sub_Total: +155.84
Item_Code: 95       Description: 95 (7587701-7590031)       Sub_Total: +316.41
Item_Code: DIESEL   Description: DIESEL (1004795-1006241)   Sub_Total: +161.89
Item_Code: 91       Description: 91 (4578963-4380091)       Sub_Total: +131.98
Item_Code: 95       Description: 95 (7252651-7253611)       Sub_Total: +130.36
                                              SUBTOTAL:    +1,075.94

--- FORMAS DE PAGO (negativo) ---
Item_Code: BRINKS        Description: Efectivo Brinks          Sub_Total: -434.00
Item_Code: CHUTA         Description: Chuta / Diferencia        Sub_Total:  -23.45
Item_Code: CLAVE         Description: Clave POS                 Sub_Total: -240.77
Item_Code: MASTERCARD    Description: Master Card               Sub_Total:  -82.53
Item_Code: VISA          Description: Visa                      Sub_Total: -165.00
Item_Code: FLOTA         Description: Transporte a credito Flota Sub_Total:  -70.00
Item_Code: COLVEN        Description: Transporte a credito Colven Sub_Total: -30.00
Item_Code: CREDITO_11    Description: Transporte a credito #11  Sub_Total:  -30.00
                                              SUBTOTAL:   -1,075.75

SUMA TOTAL DE LINEAS ≈ 0.00   → Sales_Header_Imp.Net_due = 0.00
```

#### Campos usados en Sales_Detail_Imp para las lineas de pago

| Campo          | Valor para lineas de pago                     |
|----------------|-----------------------------------------------|
| `Item_Code`    | Codigo del tender (BRINKS, VISA, CLAVE, etc.) |
| `Description`  | Descripcion legible del medio de pago         |
| `Quantity`     | `1`                                           |
| `Unit_Price`   | Monto negativo (-434.00)                      |
| `Sub_Total`    | Monto negativo (-434.00)                      |
| `Net_line`     | Monto negativo (-434.00)                      |
| `Taxable`      | `0` (no aplica impuesto)                      |
| `Category_ID`  | `TENDER` (para distinguir de ventas)          |

#### Tipos de tender identificados en el formulario

| Tender en formulario | `Item_Code`     | Grupo          | Notas                          |
|----------------------|-----------------|----------------|--------------------------------|
| BRINKS               | `BRINKS`        | efectivo       | Efectivo retirado por Brinks   |
| CHUTA                | `CHUTA`         | efectivo       | Diferencia / vuelto en caja    |
| CLAVE                | `CLAVE`         | tarjeta        | POS debito/credito local       |
| MASTER CARD          | `MASTERCARD`    | tarjeta        |                                |
| VISA                 | `VISA`          | tarjeta        |                                |
| PRIS CANJE           | `PRIS_CANJE`    | tarjeta        | Puntos de lealtad canjeados    |
| PRIS TARJETA         | `PRIS_TARJETA`  | tarjeta        | Tarjeta de lealtad PRIS        |
| FLOTA                | `FLOTA`         | credito        | Cuenta de flota vehicular      |
| (Numero de cuenta)   | `CREDITO_11`    | credito        | Cuenta credito interna         |
| COLVEN               | `COLVEN`        | credito        | Cuenta empresa Colven          |
| YAPPY                | `YAPPY`         | digital        | Pago movil Yappy (Panama)      |
| TRANSFERENCIA        | `TRANSFERENCIA` | banco          | Transferencia bancaria         |
| TROPIGAS             | `TROPIGAS`      | producto       | Venta de gas en cilindros      |
| LUBRICANTE           | `LUBRICANTE`    | producto       | Venta de aceite/lubricantes    |

---

## Pantalla Livewire Propuesta

**Ruta:** `/{customer}/sage50/fuel_sales`  
**Nombre de ruta:** `sage50.fuel_sales`  
**Componente:** `App\Http\Livewire\Admin\Sage50\CreateFuelSale`  
**Vista:** `resources/views/livewire/admin/sage50/create-fuel-sale.blade.php`

### Estructura de la pantalla (una sola vista, sin steps)

```
+----------------------------------------------------------+
|  TURNO DE GASOLINERA                                      |
+----------------------------------------------------------+
|  Fecha [dd/mm/yyyy]  Turno [    ]  Vendedor [    ]       |
|  Estacion [          ]   Cliente [busqueda tipo-ahead]   |
+----------------------------------------------------------+
|  BOMBAS / COMBUSTIBLE                                     |
|  Surt | Producto | Inicial | Final | Cant | Litros | $   |
|   1   | DIESEL   | 1669688 |1669831|  143 | 1.255  |179.46|
|   1   | 91       | 7882273 |7333367|  133 | 1.168  |155.84|
|  [+ Agregar fila]                                         |
+----------------------------------------------------------+
|  FORMAS DE PAGO                                           |
|  BRINKS [$___]   CHUTA [$___]                            |
|  CLAVE [$___]   MASTERCARD [$___]   VISA [$___]          |
|  PRIS CANJE [$___]   PRIS TARJETA [$___]                 |
|  FLOTA [$___]   COLVEN [$___]                            |
|  YAPPY [$___]   TRANSFERENCIA [$___]                     |
+----------------------------------------------------------+
|  PRODUCTOS ADICIONALES                                    |
|  TROPIGAS [$___]   LUBRICANTE [$___]                     |
+----------------------------------------------------------+
|  Total combustible: $______   Total pagos: $______       |
|  GRAN TOTAL: $______                                      |
|                          [GUARDAR TURNO]                  |
+----------------------------------------------------------+
```

### Propiedades Livewire principales

```php
public $fecha;
public $turno;
public $vendedor;         // SalesRepID
public $estacion;         // IdStore
public $customerId;
public $customerName;

public $bombas = [];      // array de filas de combustible
/*
  cada fila:
  [
    'surtidora' => '1',
    'producto'  => 'DIESEL',
    'inicial'   => '1669688',
    'final'     => '1669831',
    'cantidad'  => 143,
    'litros'    => 1.255,
    'dinero'    => 179.46,
  ]
*/

public $pagos = [
    'BRINKS'        => 0,
    'CHUTA'         => 0,
    'CLAVE'         => 0,
    'MASTERCARD'    => 0,
    'VISA'          => 0,
    'PRIS_CANJE'    => 0,
    'PRIS_TARJETA'  => 0,
    'FLOTA'         => 0,
    'COLVEN'        => 0,
    'YAPPY'         => 0,
    'TRANSFERENCIA' => 0,
    'TROPIGAS'      => 0,
    'LUBRICANTE'    => 0,
];
```

---

## Archivos a Crear

NOTA: No se requiere tabla nueva para tenders. Todo se almacena en las tablas existentes.

| Archivo | Tipo | Notas |
|---------|------|-------|
| `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php` | Livewire | Componente principal |
| `resources/views/livewire/admin/sage50/create-fuel-sale.blade.php` | Vista Blade | |
| `routes/web.php` | Ruta | Agregar en grupo sage50 |
| `resources/views/partials/menu/sage50.blade.php` | Menu | Agregar entrada |

---

## Validaciones de negocio

1. **Balance cero:** La suma de todas las lineas de detalle (positivas + negativas) debe ser `0.00` (+/- tolerancia de $0.02). `Net_due` en el header debe viajar como `0.00`.
2. **Subtotal del header:** Debe reflejar la suma de las lineas positivas (ventas de combustible).
3. FINAL debe ser mayor o igual a INICIAL en cada bomba.
4. CANTIDAD debe coincidir aproximadamente con (FINAL - INICIAL).
5. Al menos una linea de bomba es requerida.
6. Fecha no puede ser futura.
7. Cliente es requerido para guardar.
8. Las lineas de pago deben usar `Category_ID = 'TENDER'` para distinguirlas de las lineas de venta al momento de poblar Sage 50.

---

## Relacion con API de Aloha

La API de Aloha (`/ords/panama/tenders/tendersbystore`) devuelve los totales de formas de pago por tienda y por fecha. Una vez que se resuelva el acceso (actualmente retorna 401), se podra:

1. Importar automaticamente los montos de tender desde Aloha a `Sales_Tenders_Imp`
2. Cruzar con los registros manuales ingresados en la pantalla
3. Detectar discrepancias entre lo reportado en Aloha y lo ingresado por el vendedor

**Comando de prueba disponible:**
```bash
docker exec docucenter_laravel.test php artisan aloha:test-api --from=20260516 --to=20260516
```
