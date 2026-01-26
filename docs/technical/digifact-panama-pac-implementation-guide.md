# Guía de Implementación PAC Digifact Panamá

**Versión API**: 1.0.4  
**Fecha de Análisis**: 26 de diciembre de 2025  
**Sistema**: DocuCenter - Facturación Electrónica Panamá  
**Proveedor**: Digifact Servicios, S.A.

---

## Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura de la API](#arquitectura-de-la-api)
3. [Configuración y Credenciales](#configuración-y-credenciales)
4. [Autenticación](#autenticación)
5. [Endpoints Principales](#endpoints-principales)
6. [Flujo de Certificación](#flujo-de-certificación)
7. [Operaciones GET](#operaciones-get)
8. [Formatos de Respuesta](#formatos-de-respuesta)
9. [Integración con DocuCenter](#integración-con-docucenter)
10. [Plan de Implementación](#plan-de-implementación)
11. [Testing y Validación](#testing-y-validación)
12. [Consideraciones Técnicas](#consideraciones-técnicas)

---

## Resumen Ejecutivo

Digifact es un proveedor de certificación (PAC) autorizado por la DGI de Panamá para emitir, transmitir, certificar y conservar facturas electrónicas bajo el régimen establecido por la Ley N°256 del 26 de noviembre de 2021.

### Características Principales

- **Protocolo**: API REST con JSON y XML
- **Autenticación**: Bearer Token (JWT) con vigencia de 30 días
- **Ambientes**: Test y Productivo separados
- **Formatos Soportados**: XML (envío), PDF, HTML, XML (respuesta)
- **Throughput**: No especificado en documentación
- **Firma Electrónica**: Requerida para ambiente productivo

### Ventajas sobre Otros PACs

- API REST moderna y estándar
- Soporte para múltiples formatos de respuesta (PDF|HTML|XML)
- Token de larga duración (30 días)
- Operaciones GET para consultas de documentos
- Validación completa según normativas DGI

---

## Arquitectura de la API

### URLs Base

```
Test:       https://pactest.digifact.com.pa/pa.com.apinuc/api
Productivo: https://apinuc.digifact.com.pa/api
```

### Estructura de Endpoints

```
/login/get_token                  → Autenticación (POST)
/transform/nuc                    → Certificación de documentos (POST)
/SHAREDINFO                       → Consultas generales (GET)
/GetDocument                      → Obtener documento por CUFE (GET)
```

---

## Configuración y Credenciales

### Credenciales de Acceso

Las credenciales se solicitan vía:
- **Email**: soporte@digifact.com.gt con subject "Solicitud de credenciales TEST"
- **Teléfono**: 2319-1921, opción 2

**Formato de credenciales recibidas**:

```json
{
  "codigo_acceso": "23824-0930-212623",  // RUC del usuario
  "empresa": "LA LECHITA, SOCIEDAD ANÓNIMA",
  "usuario": "USER_TEST",
  "contrasena": "s142$%SAF"
}
```

### Requisitos para Ambiente Productivo

1. **Firma electrónica**: Archivo de firma digital obtenido en agencia virtual DGI
2. **Contraseña de firma**: Contraseña asignada a la firma electrónica
3. **RUC registrado**: Debe estar dado de alta en el sistema Digifact

**IMPORTANTE**: Los servicios productivos solo funcionan con credenciales productivas cuando el RUC está registrado en Digifact.

---

## Autenticación

### Obtener Token JWT

**Endpoint**:
```
POST /login/get_token
```

**Request**:
```http
POST /login/get_token
Content-Type: application/json

{
  "Username": "PA.155704849-2-2021.USER_TEST",
  "Password": "s142$%SAF"
}
```

**Formato de Username**:
```
PA + "." + <RUC_USUARIO> + "." + <NOMBRE_USUARIO>

Ejemplo: PA.155704849-2-2021.USER_TEST
```

**Response**:
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1bmlx..."
}
```

### Uso del Token

El token debe enviarse en cada request como header:

```http
Authorization: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1bmlx...
```

### Vigencia y Renovación

- **Vigencia**: 30 días desde la generación
- **Expiración**: Retorna código 401 (Unauthorized)
- **Renovación**: Generar nuevo token con mismo procedimiento

---

## Endpoints Principales

### 1. CERTIFICATE_FE_XML_TOSIGN - Certificación de Documentos

**Endpoint**:
```
POST /transform/nuc
```

**Headers**:
```http
Content-Type: application/xml
Authorization: Bearer {token}
```

**Parámetros Query String**:
```
TAXID: 23824-0930-212623        // RUC del usuario
FORMAT: PDF|HTML|XML            // Formatos deseados separados por pipe
USERNAME: La_Lechita            // Usuario asignado
```

**Body**: XML del documento según esquema DGI NUC

**Ejemplo de Request**:
```http
POST /transform/nuc?TAXID=23824-0930-212623&FORMAT=PDF|HTML|XML&USERNAME=La_Lechita
Content-Type: application/xml
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

<?xml version="1.0" encoding="utf-8"?>
<rFE xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <!-- Contenido XML según esquema DGI -->
</rFE>
```

**Response Structure**:
```json
{
  "codigo": "200",
  "mensaje": "Certificación exitosa",
  "descripcion": "Documento certificado correctamente",
  "acuseReciboDGI": "FE0120000155704849-2-2021320001...",
  "codigosDGI": "001",
  "responseData1": "base64_xml_content",
  "responseData2": "base64_html_content",
  "responseData3": "base64_pdf_content",
  "fel_faceId": "FE0120000155704849-2-2021320001...",
  "fel_infoDetails": "Información adicional",
  "fel_error": null,
  "suggestedFileName": "factura_001.xml",
  "suggestedFileName2": "factura_001.html",
  "serie": "A",
  "numero": "12345",
  "fecha_fe": "2024-12-26T10:30:00",
  "ruc_eface": "23824-0930-212623",
  "nombre_eface": "LA LECHITA, S.A.",
  "ruc_comprador": "11111-1-111111",
  "nombre_comprador": "CLIENTE EJEMPLO",
  "monto": "1500.00",
  "fecha_de_certificacion": "2024-12-26T10:30:15",
  "backprocessor": "DIGIFACT"
}
```

### 2. SHARED_GETDOCINFO - Consultar Documento

**Endpoint**:
```
GET /SHAREDINFO
```

**Parámetros**:
```
TRANSACTION: SHARED_INFO
RUC: 23824-0930-212623
DATA1: SHARED_GETDOCINFO
DATA2: AUTHNUMBER|FE0120000155704849-2-2021320001202205018539617752001120008136739
USERNAME: Test_User
```

**Headers**:
```http
Authorization: Bearer {token}
```

**Ejemplo de URL**:
```
https://pactest.digifact.com.pa/pa.com.apinuc/api/SHAREDINFO?
  TRANSACTION=SHARED_INFO&
  RUC=23824-0930-212623&
  DATA1=SHARED_GETDOCINFO&
  DATA2=AUTHNUMBER|FE0120000155704849-2-2021320001202205018539617752001120008136739&
  USERNAME=Test_User
```

**Response Structure**:
```json
{
  "REQUEST_DATA": [{
    "Respuesta": "success",
    "Codigo": "200",
    "Procesador": "DIGIFACT",
    "Mensaje": "Documento encontrado",
    "Descripcion": "Consulta exitosa",
    "Fecha": "2024-12-26T10:30:00"
  }],
  "RESPONSE": [{
    "cufe": "FE0120000155704849-2-2021320001...",
    "serie": "A",
    "numero": "12345",
    "fecha": "2024-12-26",
    "monto": "1500.00",
    "estado": "CERTIFICADO"
  }]
}
```

### 3. SHARED_GETINFOTAXcom - Información Fiscal

**Endpoint**:
```
GET /SHAREDINFO
```

**Parámetros**:
```
TRANSACTION: SHARED_INFO
RUC: 23824-0930-212623
DATA1: SHARED_GETFEINFOTAXcom
DATA2: RUC|00023824-0933-212623
USERNAME: Test_User
```

**Nota**: El RUC en DATA2 debe estar completado con ceros a la izquierda hasta 20 caracteres.

**Ejemplo**:
```
RUC original: 23824-0930-212623 (19 caracteres)
RUC formateado: 00023824-0930-212623 (20 caracteres)
```

### 4. GetDocument - Obtener Documento por CUFE

**Endpoint**:
```
GET /GetDocument
```

**Parámetros**:
```
CUFE: FE0120000155704849-2-2021320001202208048429617728001012000813673
RUC: 23824-0930-212623
FORMAT: HTML|PDF|XML
USERNAME: TESTUSER
```

**Response Structure**:
```json
{
  "REQUEST_DATA": [{
    "Respuesta": "success",
    "Codigo": "200",
    "Procesador": "DIGIFACT",
    "Mensaje": "Documento encontrado",
    "Descripcion": "Documento recuperado correctamente",
    "Fecha": "2024-12-26T10:30:00"
  }],
  "RESPONSE": [{
    "ResponseData1": "base64_xml_content",
    "ResponseData2": "base64_html_content",
    "ResponseData3": "base64_pdf_content"
  }]
}
```

**Nota**: Si un parámetro requerido no existe, RESPONSE será un arreglo vacío: `"RESPONSE": []`

---

## Flujo de Certificación

### Diagrama de Flujo

```
┌─────────────────────────────────────────────────────────┐
│ 1. Solicitar Credenciales                               │
│    → Email: soporte@digifact.com.gt                     │
│    → Tel: 2319-1921 opción 2                            │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ 2. Recibir Credenciales                                 │
│    → RUC (TAXID)                                        │
│    → Usuario                                            │
│    → Contraseña                                         │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ 3. Obtener Token JWT (Vigencia: 30 días)               │
│    POST /login/get_token                                │
│    Body: { Username, Password }                         │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ 4. Generar XML según Esquema DGI                        │
│    → Validar estructura XML                             │
│    → Incluir todos los campos requeridos                │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ 5. Enviar a Certificación                               │
│    POST /transform/nuc                                  │
│    Headers: Authorization, Content-Type                 │
│    Params: TAXID, FORMAT, USERNAME                      │
│    Body: XML del documento                              │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ 6. Recibir Respuesta de DGI                             │
│    → Acuse de Recibo (CUFE)                             │
│    → Documentos en formatos solicitados (base64)        │
│    → Códigos de validación                              │
└──────────────────────┬──────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│ 7. Procesar y Almacenar                                 │
│    → Decodificar base64                                 │
│    → Guardar archivos (XML, PDF, HTML)                  │
│    → Registrar CUFE y acuse de recibo                   │
└─────────────────────────────────────────────────────────┘
```

### Validaciones Realizadas por Digifact

Digifact ejecuta todas las validaciones según:
1. Documentación técnica DGI
2. Régimen FE publicado en Gaceta Oficial
3. Esquema XSD del NUC (Nuevo Formato Único de Comprobación)

---

## Operaciones GET

### Tabla Comparativa de Operaciones

| Operación | Endpoint | Propósito | Datos Requeridos |
|-----------|----------|-----------|------------------|
| **SHARED_GETDOCINFO** | /SHAREDINFO | Obtener info de documento específico | CUFE o Auth Number |
| **SHARED_GETINFOTAXcom** | /SHAREDINFO | Obtener info fiscal de contribuyente | RUC (20 chars) |
| **GetDocument** | /GetDocument | Descargar documento por CUFE | CUFE, FORMAT |

### Estructura Estándar de Respuesta GET

```json
{
  "REQUEST_DATA": [
    {
      "Respuesta": "success|error",
      "Codigo": "200|400|401|404|500",
      "Procesador": "DIGIFACT",
      "Mensaje": "Descripción corta",
      "Descripcion": "Descripción detallada",
      "Fecha": "2024-12-26T10:30:00"
    }
  ],
  "RESPONSE": [
    // Contenido variable según operación
    // Arreglo vacío si no hay resultados
  ]
}
```

---

## Formatos de Respuesta

### Códigos de Estado

Basado en la documentación del NUC (sección 5):

| Código | Significado | Acción |
|--------|-------------|--------|
| **200** | Certificación exitosa | Procesar documento |
| **401** | Token expirado/inválido | Renovar token |
| **400** | Error en request | Validar parámetros |
| **404** | Documento no encontrado | Verificar CUFE |
| **500** | Error servidor | Reintentar más tarde |

### Formatos de Documento

Los documentos se entregan en **base64** en los campos:

- `responseData1`: XML
- `responseData2`: HTML  
- `responseData3`: PDF

**Decodificación**:
```php
$xmlContent = base64_decode($response['responseData1']);
$htmlContent = base64_decode($response['responseData2']);
$pdfContent = base64_decode($response['responseData3']);
```

### CUFE (Código Único de Factura Electrónica)

**Formato**: `FE + Tipo + Serie + Número + Identificadores`

**Ejemplo**:
```
FE0120000155704849-2-2021320001202205018539617752001120008136739
│ │││                                                      │
│ │││                                                      └─ Checksum
│ ││└─ Serie y número
│ │└── RUC emisor
│ └─── Tipo de documento
└───── Prefijo FE (Factura Electrónica)
```

---

## Integración con DocuCenter

### Modelo: Pacconnection

Agregar nuevo tipo de PAC:

```php
// En migration
Schema::table('pacconnections', function (Blueprint $table) {
    // Campos específicos para Digifact
    $table->string('username')->nullable()->after('token');
    $table->string('taxid')->nullable()->after('username'); // RUC
    $table->timestamp('token_expires_at')->nullable()->after('token');
});

// En model
class Pacconnection extends Model
{
    const PAC_DIGIFACT = 'digifact';
    
    protected $casts = [
        'token_expires_at' => 'datetime',
    ];
    
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }
        return now()->greaterThan($this->token_expires_at);
    }
}
```

### Servicio: DigifactService

**Ubicación**: `app/Services/DigifactService.php`

**Estructura**:

```php
namespace App\Services;

use App\Models\Pacconnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigifactService
{
    protected Pacconnection $connection;
    protected string $baseUrl;
    
    public function __construct(Pacconnection $connection)
    {
        $this->connection = $connection;
        $this->baseUrl = $this->getBaseUrl();
    }
    
    protected function getBaseUrl(): string
    {
        return $this->connection->endpoint === 'production'
            ? 'https://apinuc.digifact.com.pa/api'
            : 'https://pactest.digifact.com.pa/pa.com.apinuc/api';
    }
    
    public function authenticate(): array
    {
        // Construir username: PA.{RUC}.{USERNAME}
        $username = "PA.{$this->connection->taxid}.{$this->connection->username}";
        
        $response = Http::post("{$this->baseUrl}/login/get_token", [
            'Username' => $username,
            'Password' => $this->connection->password,
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Actualizar token y fecha de expiración
            $this->connection->update([
                'token' => $data['token'],
                'token_expires_at' => now()->addDays(30),
            ]);
            
            return [
                'success' => true,
                'token' => $data['token'],
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->body(),
        ];
    }
    
    public function certifyDocument(string $xml, array $formats = ['PDF', 'HTML', 'XML']): array
    {
        // Verificar si el token está expirado
        if ($this->connection->isTokenExpired()) {
            $authResult = $this->authenticate();
            if (!$authResult['success']) {
                return $authResult;
            }
        }
        
        $formatString = implode('|', $formats);
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/xml',
            'Authorization' => $this->connection->token,
        ])->withQueryParameters([
            'TAXID' => $this->connection->taxid,
            'FORMAT' => $formatString,
            'USERNAME' => $this->connection->username,
        ])->send('POST', "{$this->baseUrl}/transform/nuc", [
            'body' => $xml,
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            return [
                'success' => true,
                'cufe' => $data['fel_faceId'] ?? null,
                'acuse_recibo' => $data['acuseReciboDGI'] ?? null,
                'xml_base64' => $data['responseData1'] ?? null,
                'html_base64' => $data['responseData2'] ?? null,
                'pdf_base64' => $data['responseData3'] ?? null,
                'codigo_dgi' => $data['codigosDGI'] ?? null,
                'fecha_certificacion' => $data['fecha_de_certificacion'] ?? null,
                'suggested_filename' => $data['suggestedFileName'] ?? null,
                'raw_response' => $data,
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->body(),
            'status' => $response->status(),
        ];
    }
    
    public function getDocumentInfo(string $authNumber): array
    {
        if ($this->connection->isTokenExpired()) {
            $this->authenticate();
        }
        
        $response = Http::withHeaders([
            'Authorization' => $this->connection->token,
        ])->get("{$this->baseUrl}/SHAREDINFO", [
            'TRANSACTION' => 'SHARED_INFO',
            'RUC' => $this->connection->taxid,
            'DATA1' => 'SHARED_GETDOCINFO',
            'DATA2' => "AUTHNUMBER|{$authNumber}",
            'USERNAME' => $this->connection->username,
        ]);
        
        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->body(),
        ];
    }
    
    public function getDocument(string $cufe, array $formats = ['PDF']): array
    {
        if ($this->connection->isTokenExpired()) {
            $this->authenticate();
        }
        
        $formatString = implode('|', $formats);
        
        $response = Http::withHeaders([
            'Authorization' => $this->connection->token,
        ])->get("{$this->baseUrl}/GetDocument", [
            'CUFE' => $cufe,
            'RUC' => $this->connection->taxid,
            'FORMAT' => $formatString,
            'USERNAME' => $this->connection->username,
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            if (!empty($data['RESPONSE'])) {
                $responseData = $data['RESPONSE'][0];
                
                return [
                    'success' => true,
                    'xml_base64' => $responseData['ResponseData1'] ?? null,
                    'html_base64' => $responseData['ResponseData2'] ?? null,
                    'pdf_base64' => $responseData['ResponseData3'] ?? null,
                    'request_info' => $data['REQUEST_DATA'][0] ?? null,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Documento no encontrado',
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->body(),
        ];
    }
    
    public function getTaxpayerInfo(string $ruc): array
    {
        if ($this->connection->isTokenExpired()) {
            $this->authenticate();
        }
        
        // Formatear RUC con ceros a la izquierda (20 caracteres)
        $formattedRuc = str_pad($ruc, 20, '0', STR_PAD_LEFT);
        
        $response = Http::withHeaders([
            'Authorization' => $this->connection->token,
        ])->get("{$this->baseUrl}/SHAREDINFO", [
            'TRANSACTION' => 'SHARED_INFO',
            'RUC' => $this->connection->taxid,
            'DATA1' => 'SHARED_GETFEINFOTAXcom',
            'DATA2' => "RUC|{$formattedRuc}",
            'USERNAME' => $this->connection->username,
        ]);
        
        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->body(),
        ];
    }
}
```

### Job: CertifyDigifactInvoiceJob

**Ubicación**: `app/Jobs/CertifyDigifactInvoiceJob.php`

```php
namespace App\Jobs;

use App\Models\Order;
use App\Models\Pacconnection;
use App\Services\DigifactService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertifyDigifactInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const STATE_INIT = 1;
    const STATE_GENERATING_XML = 2;
    const STATE_AUTHENTICATING = 3;
    const STATE_CERTIFYING = 4;
    const STATE_SAVING_FILES = 5;
    const STATE_COMPLETED = 6;
    const STATE_FAILED = 99;

    protected Order $order;
    protected Pacconnection $connection;
    protected int $state = self::STATE_INIT;

    public function __construct(Order $order, Pacconnection $connection)
    {
        $this->order = $order;
        $this->connection = $connection;
    }

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->state = self::STATE_GENERATING_XML;
            Log::info("Digifact Job - State: {$this->state}, Order: {$this->order->id}");

            // Generar XML según esquema DGI
            $xml = $this->generateDGIXml();

            $this->state = self::STATE_CERTIFYING;
            Log::info("Digifact Job - State: {$this->state}, Order: {$this->order->id}");

            // Certificar documento
            $service = new DigifactService($this->connection);
            $result = $service->certifyDocument($xml, ['PDF', 'HTML', 'XML']);

            if (!$result['success']) {
                throw new \Exception("Error en certificación: " . ($result['error'] ?? 'Unknown error'));
            }

            $this->state = self::STATE_SAVING_FILES;
            Log::info("Digifact Job - State: {$this->state}, Order: {$this->order->id}");

            // Guardar archivos
            $this->saveDocuments($result);

            // Actualizar orden
            $this->order->update([
                'cufe' => $result['cufe'],
                'acuse_recibo_dgi' => $result['acuse_recibo'],
                'fecha_certificacion' => $result['fecha_certificacion'],
                'estado_factura' => 'CERTIFICADA',
            ]);

            $this->state = self::STATE_COMPLETED;
            Log::info("Digifact Job - State: {$this->state}, Order: {$this->order->id}");

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->state = self::STATE_FAILED;
            
            Log::error("Digifact Job Failed - State: {$this->state}, Order: {$this->order->id}, Error: {$e->getMessage()}");
            
            $this->order->update([
                'estado_factura' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function generateDGIXml(): string
    {
        // Implementar generación de XML según esquema DGI NUC
        // Ver documentación técnica DGI para estructura completa
        
        return view('xml.digifact.invoice', [
            'order' => $this->order,
            'organization' => $this->order->organization,
        ])->render();
    }

    protected function saveDocuments(array $result): void
    {
        $basePath = "invoices/{$this->order->organization_id}/{$this->order->id}";

        // Guardar XML
        if (!empty($result['xml_base64'])) {
            $xmlContent = base64_decode($result['xml_base64']);
            Storage::put("{$basePath}/invoice.xml", $xmlContent);
        }

        // Guardar HTML
        if (!empty($result['html_base64'])) {
            $htmlContent = base64_decode($result['html_base64']);
            Storage::put("{$basePath}/invoice.html", $htmlContent);
        }

        // Guardar PDF
        if (!empty($result['pdf_base64'])) {
            $pdfContent = base64_decode($result['pdf_base64']);
            Storage::put("{$basePath}/invoice.pdf", $pdfContent);
        }
    }
}
```

---

## Plan de Implementación

### Fase 1: Configuración Inicial (2-3 días)

1. **Solicitar credenciales TEST**
   - Email a soporte@digifact.com.gt
   - Proporcionar información requerida
   - Esperar habilitación

2. **Crear migración para Pacconnection**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:migration add_digifact_fields_to_pacconnections
   ```

3. **Actualizar modelo Pacconnection**
   - Agregar constante `PAC_DIGIFACT`
   - Agregar campos específicos
   - Implementar método `isTokenExpired()`

### Fase 2: Desarrollo del Servicio (3-5 días)

1. **Crear DigifactService**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:class Services/DigifactService
   ```

2. **Implementar métodos**:
   - `authenticate()`: Obtener y renovar token
   - `certifyDocument()`: Certificar factura
   - `getDocument()`: Recuperar documento por CUFE
   - `getDocumentInfo()`: Consultar información
   - `getTaxpayerInfo()`: Información fiscal

3. **Testing unitario**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:test DigifactServiceTest
   ```

### Fase 3: Generación de XML DGI (5-7 días)

1. **Estudiar esquema DGI NUC**
   - Revisar documentación técnica DGI
   - Identificar campos obligatorios
   - Mapear estructura con Order model

2. **Crear template Blade para XML**
   ```
   resources/views/xml/digifact/invoice.blade.php
   ```

3. **Validaciones XML**
   - Crear validador contra XSD DGI
   - Implementar reglas de negocio

### Fase 4: Job de Certificación (2-3 días)

1. **Crear Job**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:job CertifyDigifactInvoiceJob
   ```

2. **Implementar estados granulares**
   - Similar a UpdateIntuitOrdersJob
   - Logging detallado
   - Manejo de transacciones

3. **Testing del Job**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:test CertifyDigifactInvoiceJobTest
   ```

### Fase 5: Interfaz de Usuario (3-4 días)

1. **Componente Livewire para configuración**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:livewire DigifactConnectionForm
   ```

2. **Formulario de configuración**:
   - Endpoint (Test/Productivo)
   - RUC (TAXID)
   - Usuario
   - Contraseña
   - Firma electrónica (archivo)
   - Contraseña de firma

3. **Validación de conexión**
   - Test de autenticación
   - Validación de credenciales
   - Feedback visual

### Fase 6: Testing Integral (3-5 días)

1. **Testing en ambiente TEST**
   - Crear script en `docs/testing/digifact-test.sh`
   - Probar todos los endpoints
   - Validar respuestas

2. **Command de prueba**
   ```bash
   docker exec -it docucenter-app-1 php artisan make:command DigifactTestCommand
   ```

3. **Casos de prueba**:
   - Autenticación exitosa
   - Token expirado
   - XML inválido
   - Certificación exitosa
   - Consulta de documentos

### Fase 7: Migración a Productivo (2-3 días)

1. **Solicitar credenciales productivas**
2. **Proporcionar firma electrónica**
3. **Configurar ambiente productivo**
4. **Pruebas con factura real**
5. **Monitoreo inicial

### Fase 8: Documentación (2 días)

1. **Documentación técnica**
   - Ubicar en `docs/technical/digifact/`
   - Incluir ejemplos de uso
   - Troubleshooting común

2. **Guía de usuario**
   - Ubicar en `docs/api/digifact/`
   - Screenshots de configuración
   - FAQ

---

## Testing y Validación

### Script de Testing

**Ubicación**: `docs/testing/digifact-test.sh`

```bash
#!/bin/bash

# Script de testing para Digifact PAC
# Ubicación: docs/testing/digifact-test.sh
# Uso: ./docs/testing/digifact-test.sh [detection|complete|interactive]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

CONTAINER_NAME="docucenter-app-1"
TEST_ORG_ID="${1:-1}"

echo "======================================"
echo "  Digifact PAC - Testing Suite"
echo "======================================"
echo ""

# Función: Test de autenticación
test_authentication() {
    echo "→ Probando autenticación..."
    docker exec -it $CONTAINER_NAME php artisan digifact:test --auth --org=$TEST_ORG_ID
}

# Función: Test de certificación
test_certification() {
    echo "→ Probando certificación de documento..."
    docker exec -it $CONTAINER_NAME php artisan digifact:test --certify --org=$TEST_ORG_ID --order=1
}

# Función: Test de consulta
test_query() {
    echo "→ Probando consulta de documentos..."
    docker exec -it $CONTAINER_NAME php artisan digifact:test --query --org=$TEST_ORG_ID --cufe=$2
}

# Función: Test completo
test_complete() {
    echo "→ Ejecutando suite completa de pruebas..."
    test_authentication
    test_certification
    test_query
}

case "$1" in
    auth)
        test_authentication
        ;;
    certify)
        test_certification
        ;;
    query)
        test_query $2
        ;;
    complete)
        test_complete
        ;;
    *)
        echo "Uso: $0 {auth|certify|query <cufe>|complete} [org_id]"
        exit 1
        ;;
esac

echo ""
echo "======================================"
echo "  Testing completado"
echo "======================================"
```

### Command: DigifactTestCommand

```php
namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Pacconnection;
use App\Services\DigifactService;
use Illuminate\Console\Command;

class DigifactTestCommand extends Command
{
    protected $signature = 'digifact:test
                            {--auth : Test authentication}
                            {--certify : Test certification}
                            {--query : Test document query}
                            {--org= : Organization ID}
                            {--order= : Order ID for certification}
                            {--cufe= : CUFE for query}';

    protected $description = 'Test Digifact PAC integration';

    public function handle()
    {
        $orgId = $this->option('org');
        $organization = Organization::find($orgId);

        if (!$organization) {
            $this->error("Organization not found");
            return 1;
        }

        $connection = Pacconnection::where('organization_id', $orgId)
            ->where('pac_type', Pacconnection::PAC_DIGIFACT)
            ->first();

        if (!$connection) {
            $this->error("Digifact connection not found for organization");
            return 1;
        }

        $service = new DigifactService($connection);

        if ($this->option('auth')) {
            return $this->testAuthentication($service);
        }

        if ($this->option('certify')) {
            return $this->testCertification($service, $this->option('order'));
        }

        if ($this->option('query')) {
            return $this->testQuery($service, $this->option('cufe'));
        }

        $this->info("No test option specified. Use --auth, --certify, or --query");
        return 0;
    }

    protected function testAuthentication(DigifactService $service): int
    {
        $this->info("Testing authentication...");

        $result = $service->authenticate();

        if ($result['success']) {
            $this->info("✓ Authentication successful");
            $this->line("Token: " . substr($result['token'], 0, 50) . "...");
            return 0;
        }

        $this->error("✗ Authentication failed");
        $this->line("Error: " . $result['error']);
        return 1;
    }

    protected function testCertification(DigifactService $service, ?int $orderId): int
    {
        if (!$orderId) {
            $this->error("Order ID is required for certification test");
            return 1;
        }

        $this->info("Testing certification for order {$orderId}...");

        // Generar XML de prueba
        $xml = $this->generateTestXml();

        $result = $service->certifyDocument($xml);

        if ($result['success']) {
            $this->info("✓ Certification successful");
            $this->line("CUFE: " . $result['cufe']);
            $this->line("Acuse Recibo: " . $result['acuse_recibo']);
            return 0;
        }

        $this->error("✗ Certification failed");
        $this->line("Error: " . $result['error']);
        return 1;
    }

    protected function testQuery(DigifactService $service, ?string $cufe): int
    {
        if (!$cufe) {
            $this->error("CUFE is required for query test");
            return 1;
        }

        $this->info("Testing document query for CUFE {$cufe}...");

        $result = $service->getDocument($cufe);

        if ($result['success']) {
            $this->info("✓ Query successful");
            $this->line("Document found and retrieved");
            return 0;
        }

        $this->error("✗ Query failed");
        $this->line("Error: " . $result['error']);
        return 1;
    }

    protected function generateTestXml(): string
    {
        // Generar XML de prueba básico
        return '<?xml version="1.0" encoding="utf-8"?>
<rFE xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <!-- Test XML -->
</rFE>';
    }
}
```

---

## Consideraciones Técnicas

### 1. Multi-Tenant con Digifact

```php
// En Job o Service
protected function setDatabaseConnection(Organization $organization)
{
    DB::connection()->useDatabase($organization->database);
}

protected function setDefaultConnection()
{
    DB::connection()->useDatabase(env('DB_DATABASE'));
}

// Uso
$this->setDatabaseConnection($organization);
// ... operaciones en BD específica
$this->setDefaultConnection();
```

### 2. Manejo de Token

- **Almacenar fecha de expiración**: `token_expires_at`
- **Verificar antes de cada request**: `isTokenExpired()`
- **Renovar automáticamente**: En método `certifyDocument()`

```php
if ($this->connection->isTokenExpired()) {
    $this->authenticate();
}
```

### 3. Formatos de Respuesta

Todos los documentos vienen en **base64**:

```php
// Decodificar y guardar
$pdfContent = base64_decode($result['pdf_base64']);
Storage::put($path, $pdfContent);

// O retornar directamente al navegador
return response($pdfContent)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'inline; filename="invoice.pdf"');
```

### 4. Validación de RUC

Para `SHARED_GETINFOTAXcom`, el RUC debe tener 20 caracteres:

```php
$formattedRuc = str_pad($ruc, 20, '0', STR_PAD_LEFT);
// 23824-0930-212623 → 00023824-0930-212623
```

### 5. Throughput y Colas Redis

- **Configurar worker dedicado** para Digifact
- **Rate limiting** si es necesario
- **Retry strategy** para errores transitorios

```php
// En Job
public $tries = 3;
public $backoff = [60, 300, 900]; // 1min, 5min, 15min
```

### 6. Logging y Debugging

```php
Log::channel('digifact')->info('Request', [
    'organization_id' => $this->organization->id,
    'order_id' => $this->order->id,
    'state' => $this->state,
    'endpoint' => $endpoint,
]);
```

Crear canal específico en `config/logging.php`:

```php
'digifact' => [
    'driver' => 'daily',
    'path' => storage_path('logs/digifact.log'),
    'level' => 'debug',
    'days' => 14,
],
```

### 7. Firma Electrónica (Productivo)

- **Archivo**: Formato .pfx o .p12
- **Contraseña**: Requerida para usar firma
- **Almacenamiento**: Cifrado en base de datos o storage seguro

```php
// Migration
$table->binary('firma_electronica')->nullable();
$table->string('firma_password')->nullable();

// Cifrar contraseña
'firma_password' => encrypt($request->firma_password)

// Descifrar al usar
decrypt($this->connection->firma_password)
```

### 8. Manejo de Errores

```php
try {
    $result = $service->certifyDocument($xml);
} catch (\Exception $e) {
    // Clasificar error
    if (strpos($e->getMessage(), '401') !== false) {
        // Token expirado - renovar y reintentar
        $service->authenticate();
        $result = $service->certifyDocument($xml);
    } elseif (strpos($e->getMessage(), '500') !== false) {
        // Error de servidor - reintentar más tarde
        throw $e;
    } else {
        // Error de validación - no reintentar
        Log::error("Validation error: " . $e->getMessage());
        throw $e;
    }
}
```

---

## Comparación con Otros PACs

### Digifact vs TheFactoryHKA

| Característica | Digifact | TheFactoryHKA |
|----------------|----------|---------------|
| **Protocolo** | REST (JSON/XML) | REST (JSON) |
| **Token** | 30 días | Variable |
| **Formatos** | XML, HTML, PDF | JSON, PDF |
| **Ambientes** | Test + Productivo | Test + Productivo |
| **Firma** | Requerida (producción) | Requerida |
| **Consultas** | GET endpoints | Similar |
| **Validación** | Completa por PAC | Completa por PAC |

### Digifact vs Alanube

| Característica | Digifact | Alanube |
|----------------|----------|---------|
| **Protocolo** | REST | SOAP/REST |
| **Complejidad** | Media | Alta |
| **Documentación** | Clara | Detallada |
| **Soporte** | Email + Tel | Email |
| **Respuestas** | base64 | XML directo |

---

## Recursos y Soporte

### Contacto Digifact

- **Email**: soporte@digifact.com.gt
- **Teléfono**: 2319-1921 (Panamá)
  - Opción 2: Soporte Técnico
- **Website**: https://www.digifact.com.pa

### Formato de Consultas por Email

**Subject**: `[RUC] Tipo Consulta_REST`

Ejemplo: `[23824-0930-212623] Tipo Consulta_REST`

### Tipos de Consulta

- **REST**: Dudas sobre API REST
- **GENERAL**: Consultas generales
- **VENTAS**: Aspectos comerciales
- **ADMON**: Aspectos contractuales

### Recursos DGI

- **Documentación Técnica**: https://dgi.mef.gob.pa/_7FacturaElectronica/source/GacetaNo_28608b_20180910FA.pdf
- **Proyecto Piloto**: https://dgi.mef.gob.pa/_7FacturaElectronica/ftpiloto.php
- **Agencia Virtual**: Para obtener firma electrónica

---

## Conclusión

Digifact representa una opción robusta y moderna para la certificación de facturas electrónicas en Panamá. Su API REST es estándar y bien documentada, facilitando la integración con DocuCenter.

### Ventajas Principales

1. **API REST moderna**: Fácil de consumir
2. **Token de larga duración**: Menos overhead de autenticación
3. **Múltiples formatos**: PDF, HTML, XML en una sola llamada
4. **Soporte bilingüe**: Email y teléfono
5. **Documentación clara**: Ejemplos concretos

### Próximos Pasos

1. Análisis de documentación completado
2.  Solicitar credenciales TEST
3.  Desarrollar DigifactService
4.  Implementar generación XML DGI
5.  Testing integral
6.  Migración a productivo

---

## Anexos

### A. Estructura XML Básica DGI

```xml
<?xml version="1.0" encoding="utf-8"?>
<rFE xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dFechaEmis>2024-12-26</dFechaEmis>
  <dGenera>
    <!-- Datos del emisor -->
  </dGenera>
  <dReceptor>
    <!-- Datos del receptor -->
  </dReceptor>
  <dFactura>
    <!-- Detalles de la factura -->
  </dFactura>
</rFE>
```

### B. Códigos de Respuesta Comunes

| Código | Descripción | Acción Recomendada |
|--------|-------------|-------------------|
| 200 | Éxito | Procesar documento |
| 400 | Request inválido | Revisar parámetros |
| 401 | No autorizado | Renovar token |
| 404 | No encontrado | Verificar CUFE/RUC |
| 500 | Error servidor | Reintentar más tarde |

### C. Checklist de Implementación

- [ ] Solicitar credenciales TEST
- [ ] Crear migración para Pacconnection
- [ ] Implementar DigifactService
- [ ] Crear Job de certificación
- [ ] Generar XML según esquema DGI
- [ ] Implementar UI de configuración
- [ ] Testing en ambiente TEST
- [ ] Solicitar credenciales productivas
- [ ] Proporcionar firma electrónica
- [ ] Testing en ambiente productivo
- [ ] Documentación final
- [ ] Capacitación a usuarios

---

**Documento generado**: 26 de diciembre de 2025  
**Autor**: Análisis técnico para implementación DocuCenter  
**Versión**: 1.0  
**Estado**: Análisis completo - Pendiente implementación
