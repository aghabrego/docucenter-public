# Logo de Organización en Exportables PDF (Sage50)

Permite subir un logo (PNG/JPG) por organización y mostrarlo en la cabecera de los exportables PDF del módulo Sage50.

---

## Alcance

Aplica únicamente a los exportables del módulo `admin/sage50/*`:

| Ruta | Clase |
|------|-------|
| `sales_orders/{order}/export_document` | `SaleOrdenExport` |
| `daily_entries/{order}/export` | `DailyEntryExport` |
| `sales_seats/{order}/export` | `SaleSeatExport` |
| `purchases_seats/{order}/export` | `PurchaseSeatExport` |
| `sales_orders/{order}/export` | `SaleOrderExport` |
| `purchases_orders/{order}/export` | `PurchaseOrderExport` |

No aplica (por ahora) a Facturación Electrónica (`fe_sage50`, `e_invoice`, `e_invoice_dom`).

---

## Arquitectura

```
+--------------------+        +----------------------------+        +---------------------+
|  Form Organization |        |   OrganizationLogoService  |        |   6 Exportables     |
|  (Create/Update)   | -----> |  - storeLogo()             | -----> |   TCPDF Image()     |
|  Logo (Upload)     |        |  - getLogoAbsolutePath()   |        |   - Header (Sale*)  |
+--------------------+        |  - getLogoUrl()            |        |   - Body (resto)    |
                              |  - deleteLogo()            |        +---------------------+
                              +----------------------------+
                                       |
                                       v
                              +------------------+
                              |  organizations   |
                              |  logo_path       |
                              +------------------+
                                       |
                                       v
                              +---------------------------------+
                              |  storage/app/public/            |
                              |  organizations/logos/           |
                              |  org_{id}_{timestamp}.png       |
                              +---------------------------------+
```

---

## Archivos modificados / creados

### Nuevos
- `database/migrations/2026_07_01_000000_add_logo_path_to_organizations_table.php`
- `app/Services/OrganizationLogoService.php`
- `docs/technical/organization-logo.md` (este documento)

### Modificados
- `app/Models/Organization.php` — fillable, docblock
- `app/Http/Livewire/Admin/Organization/Create.php` — propiedad `$logo`, validación, guardado
- `app/Http/Livewire/Admin/Organization/Update.php` — propiedad `$logo`, `$eliminarLogo`, validación, reemplazo y borrado
- `resources/views/livewire/admin/organization/create.blade.php` — input de carga con preview
- `resources/views/livewire/admin/organization/update.blade.php` — input + logo actual + opción de eliminar
- `app/Http/Livewire/Admin/Sage50/SaleOrderExport.php` — `->Image()` en header callback (primera página)
- `app/Http/Livewire/Admin/Sage50/SaleOrdenExport.php` — `->Image()` en header callback (primera página)
- `app/Http/Livewire/Admin/Sage50/DailyEntryExport.php` — `->Image()` en body
- `app/Http/Livewire/Admin/Sage50/SaleSeatExport.php` — `->Image()` en body
- `app/Http/Livewire/Admin/Sage50/PurchaseSeatExport.php` — `->Image()` en body
- `app/Http/Livewire/Admin/Sage50/PurchaseOrderExport.php` — `->Image()` en body

---

## Migración

```php
// filepath: database/migrations/2026_07_01_000000_add_logo_path_to_organizations_table.php
$table->string('logo_path', 255)
    ->nullable()
    ->after('email_empresa')
    ->comment('Ruta relativa en storage/app/public del logo de la empresa (PNG/JPG) usado en exportables PDF.');
```

### Ejecutar en Docker
```bash
docker exec -it docucenter_laravel.test php artisan migrate
docker exec -it docucenter_laravel.test php artisan storage:link
```

---

## Servicio `OrganizationLogoService`

API expuesta:

| Método | Descripción |
|--------|-------------|
| `getLogoAbsolutePath(Organization $org): ?string` | Ruta absoluta al archivo en disco (usada por TCPDF). Retorna `null` si no hay logo o el archivo no existe. |
| `getLogoUrl(Organization $org): ?string` | URL pública (`Storage::disk('public')->url(...)`) usada en vistas Blade. |
| `getLogoDimensions(Organization $org): ?array` | Calcula ancho/alto finales (mm) respetando proporción original y los límites `MAX_WIDTH_MM = 50` / `MAX_HEIGHT_MM = 22`. |
| `getLogoHeight(Organization $org): float` | Alto final en mm. Útil para reservar espacio después del logo. |
| `renderLogo($cPDF, Organization $org, ?float $x, ?float $y): bool` | Dibuja el logo en la instancia TCPDF respetando proporción. Retorna `true` si dibujó, `false` si no había logo. |
| `storeLogo(Organization $org, $uploadedFile): string` | Elimina el logo anterior, guarda el nuevo en `organizations/logos/org_{id}_{ts}.{ext}` y devuelve la ruta relativa. |
| `deleteLogo(Organization $org): void` | Elimina el archivo físico si existe. |

### Constantes de diseño

| Constante | Valor | Significado |
|-----------|-------|-------------|
| `MAX_WIDTH_MM` | `50` | Ancho máximo del logo en mm |
| `MAX_HEIGHT_MM` | `22` | Alto máximo del logo en mm |
| `LOGO_X` | `15` | Coordenada X por defecto (margen izquierdo) |
| `LOGO_Y` | `12` | Coordenada Y por defecto (margen superior) |

### Algoritmo de escalado

1. Se lee el tamaño real del archivo con `getimagesize()`.
2. Se calcula el ratio `ancho / alto`.
3. Se parte de `ancho = MAX_WIDTH_MM`. Se calcula `alto = ancho / ratio`.
4. Si `alto > MAX_HEIGHT_MM`, se reescala desde el alto: `alto = MAX_HEIGHT_MM`, `ancho = alto * ratio`.
5. Esto preserva la proporción sin importar si el logo es horizontal, vertical o cuadrado.

Ejemplo: un logo cuadrado de 666x658 px se renderiza a **22.27mm x 22mm**. Un logo horizontal 1200x400 px se renderiza a **50mm x 16.67mm**.

---

## Integración en exportables

### Patrón recomendado (todos los exportables)

```php
use App\Services\OrganizationLogoService;

$logoService = app(OrganizationLogoService::class);

// Dibuja el logo respetando proporcion (lee dimensiones reales del archivo)
$logoDrawn  = $logoService->renderLogo($cPDF, $organization);
$logoHeight = $logoService->getLogoHeight($organization);

// Reservar espacio debajo del logo para que el contenido no se solape
if ($logoDrawn) {
    $cPDF->SetY($cPDF->GetY() + $logoHeight + 6);
}
```

El servicio hace internamente:

```php
$cPDF->Image(
    $path,
    $x ?? 15,        // margen izquierdo
    $y ?? 12,        // margen superior
    $width,          // calculado preservando ratio
    $height,         // calculado preservando ratio (max 22mm)
    '',
    '',
    'T',
    false,
    300,             // dpi
    '',
    false,
    false,
    0,
    false,
    false,
    true             // transparency
);
```

### Patrón con header callback (`SaleOrderExport`, `SaleOrdenExport`)

```php
$pdf->setHeaderCallback(function (\Weirdo\TCPDF\FpdiTCPDFHelper $pdf) use (..., $organization) {
    $page = $pdf->getPage();

    if ($page === 1) {
        $logoService = app(OrganizationLogoService::class);
        $logoService->renderLogo($pdf, $organization);
        $logoHeight = $logoService->getLogoHeight($organization);
        if ($logoHeight > 0) {
            $pdf->SetY($pdf->GetY() + $logoHeight + 2);
        }
    }
    // ... resto del header (bloque derecho con Tipo doc, N°, Cod. Cliente)
});
```

El logo se inserta solo en la primera página para evitar duplicación visual. El `SetY` posterior desplaza el cursor para que el bloque derecho de la cabecera no se solape con el logo.

### Patrón en body (DailyEntry, SaleSeat, PurchaseSeat, PurchaseOrder)

```php
$cPDF = $pdf->getPDF();

$logoService = app(OrganizationLogoService::class);
$logoDrawn  = $logoService->renderLogo($cPDF, $organization);
$logoHeight = $logoService->getLogoHeight($organization);

// ... SetFont, SetFillColor, etc.

if ($logoDrawn) {
    $cPDF->SetY($cPDF->GetY() + $logoHeight + 6);
}
```

`SetY` mueve el cursor vertical a la parte inferior del logo + 6mm de margen, asegurando que la primera fila de datos (Reference, Date, Organization, etc.) no se solape con la imagen.

---

## UI

### Create
- Input `type="file" wire:model.lazy="logo"` con `accept="image/png,image/jpeg"`.
- Texto de ayuda con formato y dimensiones recomendadas (300x100 px).
- Preview automático tras la selección usando `$logo->temporaryUrl()`.

### Update
- Si ya existe un logo, se muestra la imagen actual con un checkbox "Eliminar logo actual".
- Si el usuario sube un archivo nuevo, se reemplaza automáticamente.
- El checkbox "Eliminar logo" elimina el archivo del disco al guardar.

---

## Pruebas manuales sugeridas

1. **Carga básica**
   - Crear/editar una organización, subir un PNG de 300x100 px.
   - Verificar que el archivo aparece en `storage/app/public/organizations/logos/`.
   - Generar un PDF desde cualquier exportable Sage50 y confirmar que el logo se ve.

2. **Reemplazo**
   - Editar la misma organización, subir un JPG nuevo.
   - Confirmar que el archivo viejo fue eliminado y el nuevo aparece en el PDF.

3. **Eliminación**
   - Editar la organización, marcar "Eliminar logo", guardar.
   - Confirmar que el archivo desapareció del disco y el PDF ya no muestra logo.

4. **Fallback sin logo**
   - Crear una organización nueva sin subir logo.
   - Generar un PDF y confirmar que NO se rompe la generación (el bloque de imagen se omite).

5. **Validaciones**
   - Intentar subir un PDF de 5 MB: debe rechazarlo con error de validación.
   - Intentar subir un .gif: debe rechazarlo (solo se aceptan png/jpg/jpeg).

6. **Multi-tenant**
   - Crear logos distintos para dos organizaciones diferentes.
   - Cambiar entre organizaciones y generar PDFs: cada uno debe mostrar su logo correspondiente.

---

## Troubleshooting

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| El logo no aparece en el PDF | Archivo eliminado físicamente pero `logo_path` quedó registrado en BD | Limpiar el campo manualmente: `UPDATE organizations SET logo_path = NULL WHERE id = X;` |
| El logo no aparece y los archivos están en su lugar | Falta el enlace `public/storage` | `docker exec -it docucenter_laravel.test php artisan storage:link` |
| Imagen pixelada en el PDF | Logo original de baja resolución | Subir un PNG/JPG de al menos 300x100 px y 300 DPI |
| Error "mimes:png,jpg,jpeg" al subir | Archivo con extensión no permitida (SVG, GIF, WEBP) | Convertir a PNG/JPG. (SVG no soportado por TCPDF) |
| `mkdir(): Permission denied` al subir | Permisos de `storage/` | Ejecutar dentro del contenedor como `sail` (no root) o ajustar permisos |
| El logo se repite en todas las páginas | `SaleOrderExport` o `SaleOrdenExport` sin la guarda `if ($page === 1)` | Verificar que el callback tenga el condicional |

---

## Convenciones aplicadas (del proyecto)

- **Sin emojis/iconos** en logs ni en mensajes de commit.
- **Comandos dentro de Docker** (`docker exec -it docucenter_laravel.test ...`).
- **Organización de archivos**: este documento vive en `docs/technical/`.
- **Estilo de código**: PHP con `declare(strict_types=1)` no usado en el resto del proyecto, por lo que se siguió el mismo patrón.
- **Sin modificar layouts de Livewire** en producción (este cambio no toca ningún `->layout()`).
- **Multi-tenant**: el logo es por organización, el helper `OrganizationLogoService` consulta siempre el modelo `Organization` actual, no afecta las bases de datos dinámicas de cada tenant.
