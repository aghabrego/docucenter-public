# Analisis: Migracion de Tenders a customer_receipt_*

**Fecha:** 2026-07-05
**Estado:** Documento informativo / propuesta
**Modulo afectado:** `CreateFuelSale` (turnos de gasolinera) y `DailyReportService` (reporte diario)

---

## 1. Contexto actual

En `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`, al guardar un turno de gasolinera la factura del turno (`M-{id}`, `T-{id}`, `N-{id}`) mezcla **ventas y pagos** en `Sales_Header_Imp` / `Sales_Detail_Imp`. Esto ocurre en dos bloques:

1. **Tenders fijos** (`$this->pagos`): efectivo, tarjetas, Yappy, etc. Cada uno genera una linea negativa en `Sales_Detail_Imp` con `Category_ID = 'TENDER'` y resta del balance del turno.
2. **Otros pagos** (`$this->otrosPagos`): ventas a credito a clientes. Generan una linea negativa con `Item_Code = 'CREDIT_PAYMENT'` y ademas crean una **factura aparte** `MC-{id}` en `Sales_Header_Imp`.

Este analisis cubre **solo el primer caso** (tenders). El credito a clientes NO se migra: se mantiene tal como esta hoy (factura `MC-*` + linea `CREDIT_PAYMENT` en el turno padre).

El problema con los tenders es que estan almacenados como lineas negativas en la misma tabla donde estan las ventas. Eso obliga al reporte diario a hacer JOIN con `Sales_Detail_Imp` y filtrar por `Category_ID = 'TENDER'` para extraer el desglose por forma de pago. El sistema canonico para registrar pagos es `customer_receipt_header_imp` / `customer_receipt_detail_imp` (es la misma tabla que ya usan los clientes no-gasolineros).

---

## 2. Propuesta

Migrar los **tenders fijos** (BRINKS, VISA, MASTERCARD, etc.) desde `Sales_Detail_Imp.Category_ID='TENDER'` hacia `customer_receipt_*`. El credito a clientes **no se toca**.

### 2.1 Que cambia

| Pieza | Antes | Despues |
|-------|-------|---------|
| Lineas de tenders en `Sales_Detail_Imp` del turno | Filas con `Category_ID='TENDER'`, monto negativo | **Se eliminan** |
| Recibo por cada tender | (no existe) | `customer_receipt_header_imp` con `Method_of_payment = BRINKS/VISA/MASTERCARD/...` + un detalle |
| Factura del turno (`M-{id}`) | Ventas + tenders mezclados | **Solo ventas** (combustible + mercancia) |
| Factura de credito `MC-*` | Existe, se crea al guardar el turno | **Sin cambios** |
| Linea `CREDIT_PAYMENT` en turno padre | Existe | **Sin cambios** |

### 2.2 Que NO cambia

- **Estructura de la factura del turno**: sigue siendo `Sales_Header_Imp` con `InvoiceNumber = {turnoCode}-{id}`.
- **Facturas `MC-*` de credito**: se siguen creando igual que hoy.
- **Lineas `CREDIT_PAYMENT` en el turno padre**: se siguen creando igual que hoy.
- **`AR_Account`**: se sigue leyendo de `ImportConfigurationMagaya` y se sigue guardando en la factura `MC-*`.
- **QuickBooks**, **Sage 50**, **FE/PAC**, **GJE**: no se tocan.
- **Top empleados, proyecciones, monthly totals**: siguen leyendo `Sales_Header_Imp` con `origin = 'fuel-station'`.

---

## 3. Diseno del recibo de tender

### 3.1 Patron: una factura, varios recibos

```
M-123 (factura, solo ventas)
  ├── linea 1: DIESEL  $X   (Item_id=DIESEL, Category=FUEL)
  ├── linea 2: 91      $Y   (Item_id=91,    Category=FUEL)
  ├── linea 3: Prod 5  $Z   (Item_id=PROD5, Category=MERCHANDISE)
  ├── linea 4: CREDIT  $20  (Item_Code=CREDIT_PAYMENT, Category=TENDER)   <- se mantiene
  │
  ├── customer_receipt 1: BRINKS  $50  (CheckNumber=M-123, Method_of_payment=BRINKS)
  ├── customer_receipt 2: VISA    $80  (CheckNumber=M-123, Method_of_payment=VISA)
  └── customer_receipt 3: YAPPY   $10  (CheckNumber=M-123, Method_of_payment=YAPPY)
```

Los recibos de tenders comparten la misma `CheckNumber = M-{id}` que la factura del turno. El campo `CheckNumber` ya existe en `customer_receipt_header_imp` y `SalesHeaderImp` ya tiene la relacion inversa `receipts()`.

### 3.2 Cabecera: `customer_receipt_header_imp`

| Campo | Valor | Origen |
|-------|-------|--------|
| `UniqueReceiptID` | (autoincrement) | BD |
| `ID_compania` | `$company->ID_compania` | organizacion |
| `org_source_id` | `$this->organization->id` | organizacion |
| `ID_companiaOrigen` | `$company->ID_compania` | organizacion |
| `Reference` | `''` (vacio, no aplica a tenders) | constante |
| `CheckNumber` | `$invoiceNumber` (ej: `M-123`) | turno padre |
| `ReceiptNumber` | `$invoiceNumber` | turno padre |
| `ParentTransactionId` | `$header->getKey()` | turno padre |
| `CustomerID` | `''` (vacio, no hay cliente en tender) | constante |
| `CustomerName` | `''` | constante |
| `Total` | `$monto` (positivo) | UI |
| `Rurned` | `0.0` | constante |
| `Tips` | `0.0` | constante |
| `Method_of_payment` | `'BRINKS'`, `'VISA'`, `'CREDIT'`, etc. | UI |
| `TaxID` | `null` | default |
| `SalesRepID` | `$this->vendedor` | sesion |
| `Date` | `$fecha` | UI |
| `CashAccountID` | `''` | constante |
| `Prepayment` | `0` | constante |
| `Created` | `now()` | timestamp |
| `LAST_CHANGE` | `now()` | timestamp |
| `Enviado` | `0` | flag |
| `Error` | `0` | flag |
| `user` | `auth()->id()` | sesion |
| `user_id` | `auth()->id()` | sesion |
| `PaymentTypeID` | `'FUEL'` | discriminador |
| `Exported` | `0` | flag |
| `EzeeReservationNo` | `null` | default |
| `DepositTicketID` | `null` | default |

### 3.3 Detalle: `customer_receipt_detail_imp`

| Campo | Valor | Origen |
|-------|-------|--------|
| `ID` | (autoincrement) | BD |
| `ID_compania` | `$company->ID_compania` | organizacion |
| `org_source_id` | `$this->organization->id` | organizacion |
| `UniqueReceiptID` | `$receipt->UniqueReceiptID` | FK al recibo |
| `InvoiceNumber` | `$invoiceNumber` (ej: `M-123`) | turno padre |
| `ApplyTo` | `0` | constante |
| `Description` | label del tender (ej: `'Visa'`, `'Brinks'`) | derivado |
| `Quantity` | `1` | constante |
| `Item_id` | `strtoupper($tipo)` (ej: `BRINKS`) | derivado |
| `Unit_Price` | `$monto` (positivo) | UI |
| `Net_line` | `$monto` (positivo) | UI |
| `Taxable` | `0` | constante |
| `user_id` | `auth()->id()` | sesion |

### 3.4 Factura del turno limpia

`Sales_Header_Imp` con:

- `InvoiceNumber = M-{id}` (maestra)
- `Subtotal = suma de Net_line de las lineas de venta`
- `Net_due = 0` (porque la factura ya no incluye tenders; estos se registran aparte en `customer_receipt_*`)
- `AR_Account` se mantiene (sigue usandose para las facturas `MC-*` de credito que se siguen creando)
- `origin = 'fuel-station'`

`Sales_Detail_Imp` con:

- Solo lineas de venta (combustible + mercancia) con `Item_id` poblado.
- Lineas `CREDIT_PAYMENT` para cada "otro pago" (sin cambios).
- **Ninguna** linea con `Category_ID = 'TENDER'` (estas se eliminan, ahora son recibos).

> Nota: al poner `Net_due = 0`, las queries del reporte diario que filtran `Net_due > 0` dejaran de contar las facturas de gasolinera. Hay que ajustar el discriminador (ver seccion 5).

---

## 4. Cambios de esquema requeridos

### 4.1 No se requieren columnas nuevas

`customer_receipt_header_imp` ya tiene `CheckNumber`, `ParentTransactionId`, `Method_of_payment` y todos los indices necesarios en el stub `app/Models/stubs/customer_receipt_header_imp.sql.stub`.

`customer_receipt_detail_imp` ya tiene `UniqueReceiptID`, `InvoiceNumber` y `Item_id`.

**Conclusion:** no hay que agregar columnas. La estructura ya soporta el patron factura + N recibos.

### 4.2 Comando a ejecutar

Ninguno. Solo despliegue del codigo nuevo.

---

## 5. Cambios en codigo

### 5.1 `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php`

#### 5.1.1 Cambiar `Net_due` a 0 en la factura del turno

```php
$header = SalesHeaderImp::query()->create([
    'ID_compania'   => $company->ID_compania ?? null,
    'InvoiceNumber' => 'TMP',
    'CustomerID'    => $this->customer_id,
    'CustomerName'  => $this->customer_name,
    'CustomerPO'    => null,
    'SalesRepID'    => $this->vendedor,
    'IdStore'       => $this->estacion,
    'DeviceId'      => $deviceId,
    'AR_Account'    => $arAccount ?: null,
    'Date'          => $fecha,
    'DueDate'       => $fecha,
    'Subtotal'      => $totalVentas,
    'Net_due'       => 0.00,  // <- los tenders ya no estan aqui
    'Enviado'       => 0,
    'Error'         => 0,
    'origin'        => 'fuel-station',
]);
```

#### 5.1.2 Reemplazar el bloque de tenders: de `Sales_Detail_Imp` a `customer_receipt_*`

**Codigo actual a eliminar** (en el `foreach ($this->pagos as $tipo => $monto)`):

```php
foreach ($this->pagos as $tipo => $monto) {
    $monto = (float) ($monto ?? 0);

    if ($monto <= 0) {
        continue;
    }

    $label = self::TENDER_LABELS[$tipo] ?? $tipo;

    SalesDetailImp::query()->create([
        'ID'           => $header->getKey(),
        'ID_compania'  => $header->ID_compania,
        'InvoiceNumber' => $invoiceNumber,
        'Item_Code'    => $tipo,
        'Sequential'   => (string) $sequential,
        'Description'  => $label,
        'JobID'        => null,
        'JobPhaseID'   => null,
        'Quantity'     => 1,
        'Unit_Price'   => -$monto,
        'Sub_Total'    => -$monto,
        'Net_line'     => -$monto,
        'Itbms'        => 0,
        'Taxable'      => 0,
        'Category_ID'  => 'TENDER',
    ]);

    $sequential++;
}
```

**Codigo nuevo:**

```php
foreach ($this->pagos as $tipo => $monto) {
    $montoT = (float) ($monto ?? 0);

    if ($montoT <= 0) {
        continue;
    }

    $label = self::TENDER_LABELS[$tipo] ?? $tipo;

    $receipt = CustomerReceiptHeaderImp::query()->create([
        'ID_compania'         => $company->ID_compania ?? null,
        'org_source_id'       => $this->organization->id,
        'ID_companiaOrigen'   => $company->ID_compania ?? null,
        'Reference'           => '',
        'CheckNumber'         => $invoiceNumber,
        'ReceiptNumber'       => $invoiceNumber,
        'ParentTransactionId' => $header->getKey(),
        'CustomerID'          => '',
        'CustomerName'        => '',
        'Total'               => $montoT,
        'Rurned'              => 0.0,
        'Tips'                => 0.0,
        'Method_of_payment'   => strtoupper($tipo),
        'TaxID'               => null,
        'SalesRepID'          => $this->vendedor,
        'Date'                => $fecha,
        'CashAccountID'       => '',
        'Prepayment'          => 0,
        'Created'             => now(),
        'LAST_CHANGE'         => now(),
        'Enviado'             => 0,
        'Error'               => 0,
        'user'                => auth()->id(),
        'user_id'             => auth()->id(),
        'PaymentTypeID'       => 'FUEL',
        'Exported'            => 0,
        'EzeeReservationNo'   => null,
        'DepositTicketID'     => null,
    ]);

    CustomerReceiptDetailImp::query()->create([
        'ID_compania'     => $company->ID_compania ?? null,
        'org_source_id'   => $this->organization->id,
        'UniqueReceiptID' => $receipt->UniqueReceiptID,
        'InvoiceNumber'   => $invoiceNumber,
        'ApplyTo'         => 0,
        'Description'     => $label,
        'Net_line'        => $montoT,
        'Quantity'        => 1,
        'Item_id'         => strtoupper($tipo),
        'Unit_Price'      => $montoT,
        'Taxable'         => 0,
        'user_id'         => auth()->id(),
    ]);
}
```

#### 5.1.3 El bloque de "otros pagos" (credito) NO se modifica

El `foreach ($this->otrosPagos as $otroPago)` se mantiene exactamente como esta hoy: crea la linea `CREDIT_PAYMENT` en el turno padre y la factura `MC-*` aparte.

#### 5.1.4 Agregar imports

```php
use App\Models\CustomerReceiptHeaderImp;
use App\Models\CustomerReceiptDetailImp;
```

`ImportConfigurationMagaya` se mantiene (sigue usandose para `AR_Account` de la factura `MC-*`).

#### 5.1.5 `validarJornadaDuplicada()`

**No requiere cambios.** La validacion cuenta facturas por `LEFT(InvoiceNumber,1) = {turnoCode}`, lo cual sigue funcionando porque la factura del turno mantiene su `InvoiceNumber = M-{id}`.

### 5.2 `app/Services/DailyReportService.php`

#### 5.2.1 `getPaymentMethods()` para gasolineras

Reemplazar la lectura desde `Sales_Detail_Imp` por la lectura desde `customer_receipt_header_imp` (tenders), y mantener la lectura de `Sales_Detail_Imp` solo para `Item_Code='CREDIT_PAYMENT'` (credito, que no se migra).

**Codigo actual:**

```php
if ($this->isFuelStation()) {
    if (! $this->tableExists('Sales_Detail_Imp') || ! $this->tableExists('Sales_Header_Imp')) {
        return [];
    }

    $rows = DB::table('Sales_Detail_Imp as d')
        ->join('Sales_Header_Imp as h', 'h.InvoiceNumber', '=', 'd.InvoiceNumber')
        ->whereDate('h.Date', $date->toDateString())
        ->whereIn('d.Category_ID', ['TENDER', 'CREDIT_PAYMENT'])
        ->selectRaw('d.Item_Code as payment_method, COUNT(*) as count, SUM(ABS(d.Net_line)) as total')
        ->groupBy('d.Item_Code')
        ->orderByDesc('total')
        ->get();

    return $rows->map(fn($r) => [...])->toArray();
}
```

**Codigo nuevo:**

```php
if ($this->isFuelStation()) {
    if (! $this->tableExists('customer_receipt_header_imp')) {
        return [];
    }

    // Tenders: leer desde customer_receipt_header_imp
    $tenders = DB::table('customer_receipt_header_imp')
        ->whereDate('Date', $date->toDateString())
        ->where(function ($q) {
            $q->where('CheckNumber', 'LIKE', 'M-%')
              ->orWhere('CheckNumber', 'LIKE', 'T-%')
              ->orWhere('CheckNumber', 'LIKE', 'N-%');
        })
        ->selectRaw('Method_of_payment as payment_method, COUNT(*) as count, COALESCE(SUM(Total), 0) as total')
        ->groupBy('Method_of_payment');

    // Credito: leer desde Sales_Detail_Imp (no se migra a recibos)
    $credit = collect();
    if ($this->tableExists('Sales_Detail_Imp') && $this->tableExists('Sales_Header_Imp')) {
        $credit = DB::table('Sales_Detail_Imp as d')
            ->join('Sales_Header_Imp as h', 'h.InvoiceNumber', '=', 'd.InvoiceNumber')
            ->whereDate('h.Date', $date->toDateString())
            ->where('d.Item_Code', 'CREDIT_PAYMENT')
            ->selectRaw("'CREDIT' as payment_method, COUNT(*) as count, SUM(ABS(d.Net_line)) as total")
            ->groupBy('payment_method');
    }

    $rows = $tenders->get()->concat($credit)->sortByDesc('total')->values();

    return $rows->map(fn($r) => [
        'payment_method' => $r->payment_method,
        'count'          => (int)   $r->count,
        'total'          => (float) $r->total,
    ])->toArray();
}
```

> Esto mantiene el desglose completo: tenders desde `customer_receipt_*` + credito desde `Sales_Detail_Imp.Item_Code='CREDIT_PAYMENT'`.

#### 5.2.2 `getOrdersDetail()`

La factura del turno pasa a `Net_due = 0`, asi que el filtro `Net_due > 0` dejaria de contarla. Hay que reemplazarlo por `whereExists`:

**Codigo actual:**

```php
if ($this->isFuelStation()) {
    $query->where('Net_due', '>', 0);
}
```

**Codigo nuevo:**

```php
if ($this->isFuelStation()) {
    $query->where('origin', 'fuel-station')
        ->whereExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('Sales_Detail_Imp as d')
              ->whereColumn('d.InvoiceNumber', 'Sales_Header_Imp.InvoiceNumber')
              ->whereIn('d.Category_ID', ['FUEL', 'MERCHANDISE']);
        });
}
```

El resto de `getOrdersDetail()` (incluyendo el calculo de `net_total_cash` vs `net_total_credit` con filtro `MC-/TC-/NC-`) **no cambia**.

#### 5.2.3 `getCreditSalesSummary()`

**Sin cambios.** Sigue leyendo desde `Sales_Header_Imp` con filtro por prefijo `MC-/TC-/NC-`.

#### 5.2.4 `getSalesByEmployee()`

Mismo ajuste que en `getOrdersDetail()`: reemplazar `Net_due > 0` por `whereExists`:

**Codigo nuevo:**

```php
if ($this->isFuelStation()) {
    $query->where('origin', 'fuel-station')
        ->whereExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('Sales_Detail_Imp as d')
              ->whereColumn('d.InvoiceNumber', 'Sales_Header_Imp.InvoiceNumber')
              ->whereIn('d.Category_ID', ['FUEL', 'MERCHANDISE']);
        });
}
```

---

## 6. Datos historicos

### 6.1 Credito: NO se migra

Las 9+38 facturas `MC-*` existentes en BD **no se migran**. Siguen siendo validas y el reporte diario las sigue leyendo con el filtro por prefijo.

### 6.2 Tenders: comando de migracion historica

Para los tenders historicos (lineas `Category_ID = 'TENDER'` en `Sales_Detail_Imp` con monto negativo) se propone un **comando artisan** que mueva esas lineas a `customer_receipt_*` sin intervencion manual.

#### 6.2.1 Firma del comando

```
php artisan fuel-station:migrate-tenders-to-receipts {--dry-run} {--organization=ID} {--from=YYYY-MM-DD} {--to=YYYY-MM-DD}
```

Opciones:

| Opcion | Descripcion |
|--------|-------------|
| `--dry-run` | Recorre todas las lineas TENDER y muestra un resumen sin escribir nada. Util para estimar volumen antes de ejecutar. |
| `--organization=ID` | Procesa solo una organizacion especifica. Si se omite, itera todas. |
| `--from=YYYY-MM-DD` | (Opcional) Procesa solo lineas con `Date >= DATE` (sobre el `Date` del header). |
| `--to=YYYY-MM-DD` | (Opcional) Procesa solo lineas con `Date <= DATE` (sobre el `Date` del header). |

Namespace: `App\Console\Commands\Sage50\MigrateTendersToReceiptsCommand`

Archivo: `app/Console/Commands/Sage50/MigrateTendersToReceiptsCommand.php`

Convencion: sigue el patron de `app/Console/Commands/Configuration/AddColumnToOrganizationsTableCommand.php` (usa `Organization::query()->cursor()` y `DB::connection()->useDatabase($organization->database)` para iterar).

#### 6.2.2 Logica del comando (resumen)

Por cada organizacion:

1. Conectar a la BD de la organizacion con `setCustomConnectionWithoutUser($organization->database)`.
2. Verificar que existan las tablas `Sales_Detail_Imp`, `Sales_Header_Imp`, `customer_receipt_header_imp`, `customer_receipt_detail_imp`. Si falta alguna, abortar para esa organizacion con mensaje claro.
3. Para cada linea en `Sales_Detail_Imp` donde:
   - `Category_ID = 'TENDER'`
   - `Net_line < 0` (es un pago, no un ajuste)
   - el `InvoiceNumber` asociado en `Sales_Header_Imp` empieza con `M-`, `T-` o `N-` (turno de gasolinera) y `origin = 'fuel-station'`
   
   ejecutar:
   
   a. **Crear `customer_receipt_header_imp`** con los mismos campos que `CreateFuelSale` usaria (seccion 3.2). `Method_of_payment = Item_Code` de la linea original. `Total = ABS(Net_line)`. `CheckNumber = InvoiceNumber`. `ParentTransactionId = ID` del header. `Date = Date` del header.
   
   b. **Crear `customer_receipt_detail_imp`** vinculado al recibo anterior con `Item_id = Item_Code`, `Description = Description`, `Net_line = ABS(Net_line)`, `InvoiceNumber = InvoiceNumber`.
   
   c. **Eliminar la linea original** de `Sales_Detail_Imp`.
4. **NO tocar** las lineas con `Category_ID = 'CREDIT_PAYMENT'` (es el credito a clientes, se mantiene como esta).
5. **NO tocar** lineas TENDER de facturas que NO sean de gasolinera (otros origenes).
6. **NO tocar** lineas TENDER con `Net_line >= 0` (por seguridad, no deberia haber ninguna pero por si acaso).

Todo dentro de una transaccion por organizacion. Si falla una organizacion, se hace rollback solo a esa y se continua con las siguientes.

#### 6.2.3 Salida esperada

El comando imprime en consola:

```
Migrando tenders historicos a customer_receipt_*
[DRY-RUN] Modo lectura, no se escribiran cambios.

Organizacion: 9_734_1672_56
  - Lineas TENDER encontradas: 1234
  - Receipts a crear: 1234
  - Lineas a eliminar: 1234
  - Procesando... OK
    - Receipts creados: 1234
    - Detalles creados: 1234
    - Lineas eliminadas: 1234

Organizacion: 8_555_1234_78
  - Tabla customer_receipt_header_imp no existe. Saltando.

...

Resumen final:
  - Organizaciones procesadas: 14
  - Organizaciones saltadas (sin tablas): 3
  - Receipts creados: 8543
  - Detalles creados: 8543
  - Lineas eliminadas: 8543
```

#### 6.2.4 Consideraciones

| Tema | Detalle |
|------|---------|
| Idempotencia | El comando es idempotente: si una linea TENDER ya fue migrada (porque `customer_receipt_detail_imp` ya tiene `InvoiceNumber + Item_Code` igual), se debe saltar y NO eliminar la linea. Esto permite re-ejecuciones seguras. |
| Rollback | Antes de empezar, capturar un dump de la BD con `mysqldump` o equivalente. Si algo sale mal, restaurar el dump. |
| Tiempo estimado | Con ~9+38 facturas de credito y un numero mayor de tenders (depende del volumen historico), puede tomar varios minutos. Mostrar progreso. |
| Locking | Usar `DB::beginTransaction()` por organizacion y `SELECT ... FOR UPDATE` en las lineas a procesar para evitar conflictos con operaciones concurrentes. |
| Validacion previa | Antes de ejecutar, correr con `--dry-run` y revisar el conteo. Si el numero de receipts esperados coincide con el numero de lineas TENDER, proceder. |
| Fechas | Usar `Date` del header como `Date` del recibo. Si la factura tiene `Date` en NULL (no deberia), usar `LAST_CHANGE` o `created_at` del detalle. |
| Creditos | El comando SOLO migra tenders. NO toca lineas `CREDIT_PAYMENT` ni facturas `MC-*`. Esto se valida explicitamente en la query de seleccion. |
| Items_id desconocidos | El `Item_Code` de la linea TENDER historica (ej: `BRINKS`, `VISA`) se usa tal cual como `Method_of_payment` y `Item_id` del recibo. No requiere lookup. |

#### 6.2.5 Comandos relacionados (referencia)

Para comparar el patron de iteracion por organizacion, ver:

- `app/Console/Commands/Configuration/AddColumnToOrganizationsTableCommand.php` (líneas 78-93): itera con `Organization::query()->orderBy('id', 'desc')->whereNotNull('database')->cursor()` y aplica cambios por BD.
- `app/Console/Commands/Configuration/CreateTableFromStubCommand.php`: usa el mismo patron para crear tablas desde stubs.
- `app/Console/Commands/Configuration/UpdateStubColumnCommand.php`: otro ejemplo de iteracion por organizacion.

#### 6.2.6 Script de prueba antes de ejecutar en produccion

1. Seleccionar una organizacion con volumen bajo de tenders (e.g., una gasolinera de prueba).
2. Ejecutar con `--dry-run --organization=ID` y revisar el conteo.
3. Sacar un dump de la BD de esa organizacion: `mysqldump -h ... -u ... -p ... database_name > backup_pre_migration.sql`.
4. Ejecutar sin `--dry-run` solo para esa organizacion: `php artisan fuel-station:migrate-tenders-to-receipts --organization=ID`.
5. Verificar:
   - Lineas `Sales_Detail_Imp.Category_ID='TENDER'` reducidas al valor esperado.
   - `customer_receipt_header_imp` tiene los recibos esperados con `Method_of_payment` correcto.
   - `customer_receipt_detail_imp` tiene los detalles vinculados.
   - El reporte diario sigue mostrando los mismos totales de pagos.
6. Si algo falla, restaurar el dump: `mysql -h ... -u ... -p ... database_name < backup_pre_migration.sql`.

---

## 7. Verificaciones posteriores al cambio

1. Crear un turno nuevo con:
   - 1 linea de combustible $100 (Item_id=DIESEL).
   - 1 linea de mercancia $20 (Item_id=PROD5).
   - 1 tender BRINKS de $50.
   - 1 tender VISA de $40.
   - 1 otro pago (credito) a cliente `CLI-1` de $30.
2. Verificar en BD:
   - `Sales_Header_Imp`: 1 factura `M-{id}` (`Net_due = 0`) + 1 factura `MC-{id}` (credito, `Net_due = 30`).
   - `Sales_Detail_Imp` del turno `M-{id}`: 2 lineas de venta + 1 linea `CREDIT_PAYMENT` (-$30). **Cero** lineas con `Category_ID = 'TENDER'`.
   - `customer_receipt_header_imp`: 2 recibos con `CheckNumber = M-{id}`, `Method_of_payment` = `BRINKS` y `VISA`.
   - `customer_receipt_detail_imp`: 2 detalles vinculados a esos recibos.
3. Generar reporte diario:
   - `payment_methods` lista BRINKS $50, VISA $40, CREDIT $30.
   - Top 10 clientes a credito lista `CLI-1` con $30.
4. Validacion de duplicidad: intentar guardar otro turno con la misma surtidora/fecha/vendedor/turno debe seguir siendo rechazado.

---

## 8. Riesgos identificados

| # | Riesgo | Mitigacion |
|---|--------|------------|
| 1 | `getOrdersDetail`/`getSalesByEmployee` dejaban de contar la factura del turno al pasar a `Net_due = 0` | Cambiar el discriminador a `whereExists` sobre `Sales_Detail_Imp.Category_ID IN ('FUEL','MERCHANDISE')` |
| 2 | Alguien lee `Sales_Detail_Imp.Category_ID = 'TENDER'` fuera de `getPaymentMethods` | Buscar con grep todos los `where.*Category_ID.*TENDER` en el codigo y actualizar |
| 3 | Reporte de clientes no-gasolineros podria leer recibos de gasolinera | Filtrar recibos de gasolinera por `CheckNumber LIKE 'M-%' OR 'T-%' OR 'N-%'` |
| 4 | El credito sigue en `MC-*` + `CREDIT_PAYMENT`, manteniendo la convencion fragil de prefijos | Es el mismo esquema actual, no se introduce riesgo nuevo |
| 5 | `SendSaleToQuickBooksJob` u otros jobs podrian leer las lineas `TENDER` | Verificar que filtra por `Category_ID IN ('FUEL','MERCHANDISE')` |

---

## 9. Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `app/Http/Livewire/Admin/Sage50/CreateFuelSale.php` | Net_due=0 en factura del turno, tenders migran a `customer_receipt_*`, agregar imports |
| `app/Services/DailyReportService.php` | `getPaymentMethods()` lee tenders de `customer_receipt_*` + credito de `Sales_Detail_Imp`; `getOrdersDetail()` y `getSalesByEmployee()` cambian discriminador a `whereExists` |

## 10. Archivos NO modificados

- `app/Models/CustomerReceiptHeaderImp.php` (sin cambios)
- `app/Models/CustomerReceiptDetailImp.php` (sin cambios)
- `app/Models/ImportConfigurationMagaya.php` (sigue usandose para `AR_Account` de `MC-*`)
- Bloque de "otros pagos" en `CreateFuelSale` (se mantiene tal cual: `MC-*` + `CREDIT_PAYMENT`)
- `app/Jobs/SendSaleToQuickBooksJob.php` (verificar riesgo #5, pero no se modifica en este PR)
- Cualquier archivo Sage 50 / FE / GJE
- Stubs de tabla en `app/Models/stubs/`

---

## 11. Orden de implementacion sugerido

1. Modificar `CreateFuelSale::guardar()`:
   - Poner `Net_due = 0` en la factura del turno.
   - Reemplazar el `foreach ($this->pagos)` por la creacion de `customer_receipt_*`.
   - Agregar los imports.
2. Modificar `DailyReportService`:
   - `getPaymentMethods()`: tenders desde recibos, credito desde `Sales_Detail_Imp`.
   - `getOrdersDetail()`: discriminador con `whereExists`.
   - `getSalesByEmployee()`: mismo ajuste.
3. Probar con un turno de prueba (combustible + mercancia + tenders + credito).
4. Verificar el reporte diario completo.
5. Verificar que `SendSaleToQuickBooksJob` sigue funcionando.
6. Commitear y desplegar.

---

## 12. Validaciones pendientes antes de implementar

- Que `Method_of_payment` en `customer_receipt_header_imp` acepta los valores: `BRINKS`, `VISA`, `MASTERCARD`, `YAPPY`, `CHUTA`, `CLAVE`, `PRIS_CANJE`, `PRIS_TARJETA`, `TRANSFERENCIA_BANCARIA` (es `varchar(20)` libre).
- Que `SalesHeaderImp` no exige `Net_due NOT NULL` (ahora sera 0).
- Que no hay otros jobs o reportes que dependan de las lineas `Category_ID = 'TENDER'` en `Sales_Detail_Imp`.
