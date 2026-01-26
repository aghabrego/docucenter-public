# Sistema de Extracción de Emails XML a Google Drive

## Índice
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Análisis de Componentes Existentes](#análisis-de-componentes-existentes)
4. [Diseño de la Solución](#diseño-de-la-solución)
5. [Implementación Técnica](#implementación-técnica)
6. [Flujo de Datos](#flujo-de-datos)
7. [Consideraciones de Seguridad](#consideraciones-de-seguridad)
8. [Plan de Implementación](#plan-de-implementación)

---

## Resumen Ejecutivo

### Objetivo
Crear un sistema automatizado que:
1. **Obtenga emails LEÍDOS** con archivos XML vía IMAP
2. **Extraiga archivos XML** de los emails procesados
3. **Suba archivos a Google Drive** organizados por RUC-DV
4. **Elimine PERMANENTEMENTE** los emails procesados (sin papelera)
5. **Libere espacio en disco** del servidor de correo
6. **Mantenga auditoría completa** de todas las operaciones

### Alcance
- **Origen**: Emails LEÍDOS vía IMAP (puerto 993)
- **Destino**: Google Drive organizado por estructura `{RUC}-{DV}/{Año}/{Mes}/`
- **Eliminación**: PERMANENTE usando `expunge()` para liberar espacio
- **Integración**: `webklex/laravel-imap ^5.2` (ya instalado)
- **Librería Google**: `google/apiclient ^2.15` (ya instalada)

---

## Arquitectura del Sistema

### Diagrama de Flujo

```

                    SERVIDOR DE CORREO                           
  /var/vmail/apconpanama.me/                                     
   organizacion1@apconpanama.me/Maildir/cur/                  
   organizacion2@apconpanama.me/Maildir/cur/                  
   organizacionN@apconpanama.me/Maildir/cur/                  

                          
                           (1) ExtractEmailAttachmentsJob
                               - Escanea Maildir
                               - Extrae archivos XML
                          

               PROCESAMIENTO LOCAL (Temporal)                     
  storage/app/xml_processing/{organization_id}/                  
   {random_hash}.xml                                          
   {random_hash}.xml                                          
   metadata.json (info del email original)                    

                          
                           (2) UploadToGoogleDriveJob
                               - Valida XML
                               - Identifica organización
                          

                      GOOGLE DRIVE                               
  DocuCenter-Facturas/                                           
   1825706-90/  (RUC-DV de APCON)                            
      2026/                                                  
         01-Enero/                                         
            FE-001-001-00001234.xml                      
            FE-001-001-00001235.xml                      
         02-Febrero/                                       
      metadata/                                             
          audit.log                                         
   155706268-07/  (RUC-DV de otra org)                       
      2026/                                                  
   _shared/  (Archivos compartidos)                           

                          
                           (3) CleanupMaildirJob
                               - Verifica subida exitosa
                               - Elimina email original
                          

                   REGISTRO DE AUDITORÍA                         
  database: google_drive_uploads                                 
  - organization_id                                              
  - file_name                                                    
  - google_drive_id                                              
  - maildir_path (para tracking)                                
  - uploaded_at                                                  
  - deleted_at (del servidor)                                    

```

---

## Análisis de Componentes Existentes

### 1. Sistema de Email IMAP (Ya Implementado)

#### Librería
- **Paquete**: `webklex/laravel-imap ^5.2`
- **Configuración**: [config/imap.php](../../config/imap.php)

#### Configuración Actual
```php
// config/imap.php
'accounts' => [
    'default' => [
        'host'  => 'apconpanama.me',
        'port'  => 993,
        'protocol'  => 'imap',
        'encryption'    => 'ssl',
        'validate_cert' => true,
    ]
]
```

#### Job Existente: ExtractOrganizationConfigurationEmailsJob

**Ubicación**: `app/Jobs/ExtractOrganizationConfigurationEmailsJob.php`

**Funcionalidad Actual**:
```php
// Extrae emails IMAP y procesa XML
public function handle()
{
    $client = $this->configuration->clientManagerMake();
    $folder = $client->getFolderByPath('INBOX');
    $query = $folder->query()->fetchOrderDesc()->limit(50, 1);
    $date = Carbon::now('America/Panama')->subHours(120);
    
    $query->unseen()->since($date)->chunked(function($messages, $chunk) use ($fullPath) {
        $messages->each(function($message) use ($fullPath) {
            if ($message->hasAttachments()) {
                $files = $message->attachments();
                $files->each(function($file) use (&$filesTmp, $message, $fullPath) {
                    if ($file->getExtension() === 'xml') {
                        $file->save($fullPath, $filname);
                        // Procesa el XML
                        $classXml = new ImportData();
                        $classXml->xml($filname, $fullPath, true, false);
                    }
                });
            }
        });
    });
}
```

**Limitaciones**:
- Solo accede vía IMAP (puerto 993)
- No tiene acceso directo a Maildir en filesystem
- Conexión limitada a 10 conexiones simultáneas (error común)

#### Modelo: Configurationemailorganization

**Ubicación**: `app/Models/Configurationemailorganization.php`

**Campos**:
```php
protected $fillable = [
    'organization_id',
    'email',
    'password',
    'user_id',
    'doccucenter_mail_as_additional',
];

public function clientManagerMake()
{
    $cm = new ClientManager(config('imap'));
    $client = $cm->make([
        'host'           => 'apconpanama.me',
        'port'           => 993,
        'encryption'     => 'ssl',
        'validate_cert'  => true,
        'username'       => $this->email,
        'password'       => $this->password,
        'protocol'       => 'imap',
        'authentication' => null,
    ]);
    $client->connect();
    return $client;
}
```

### 2. Modelo de Organización (RUC y DV)

#### Organization Model

**Ubicación**: `app/Models/Organization.php`

**Campos Relevantes**:
```php
/**
 * @property string $ruc       // Ejemplo: "1825706-1-709732"
 * @property string $dv        // Ejemplo: "90"
 * @property string $nombre    // Razón social
 * @property string $database  // Base de datos específica
 */

protected $fillable = [
    'ruc',
    'dv',
    'nombre',
    'email_empresa',
    'database',
    // ... otros campos
];
```

#### Estructura de RUC Panameño

**Formato**: `{Provincia/Registro}-{Libro}-{Tomo}`

**Ejemplos**:
- **Persona Natural**: `8-888-888888` (Cédula)
- **Persona Jurídica**: `155706268-2-2021` (RUC empresa)
- **APCON Consulting**: `1825706-1-709732` DV: `90`

#### Helper: PanamaRucHelper

**Ubicación**: `app/Helpers/PanamaRucHelper.php`

**Funciones Útiles**:
```php
// Detectar tipo de contribuyente
PanamaRucHelper::detectContributorType($ruc);
// Retorna: 1 = Natural, 2 = Jurídico

// Validar RUC
PanamaRucHelper::verifyPersonalID($ruc);
// Retorna: array con análisis completo

// Información detallada
PanamaRucHelper::getRucInfo($ruc);
// Retorna: ['ruc', 'type', 'typeName', 'isValid', 'parts']
```

### 3. Google Client API (Ya Instalada)

#### Librería Disponible
```json
// composer.json
"google/apiclient": "^2.15",
"google/cloud-document-ai": "^2.3",
"google/cloud-storage": "^1.48",
"google/cloud-vision": "^1.6"
```

#### Credenciales Disponibles
- `oauth2-account-credentials.json` (OAuth2 para acceso de usuario)
- `service-account-credentials.json` (Service Account para operaciones server-to-server)

### 4. Sistema de Correo (Postfix + Dovecot)

#### Estructura de Maildir

**Ruta Base**: `/var/vmail/apconpanama.me/`

**Estructura por Usuario**:
```
/var/vmail/apconpanama.me/
 usuario1@apconpanama.me/
    Maildir/
        cur/          # Emails leídos
        new/          # Emails nuevos (sin leer)
        tmp/          # Emails temporales
 usuario2@apconpanama.me/
    Maildir/
 usuarioN@apconpanama.me/
     Maildir/
```

**Formato de Archivo Email (Maildir)**:
```
1673954321.M123456P12345.apconpanama.me,S=12345,W=12567:2,S
                                                 
Timestamp  Unique ID       Hostname          Size    Flags
```

**Flags Comunes**:
- `S` = Seen (leído)
- `R` = Replied (respondido)
- `P` = Passed (reenviado)
- `F` = Flagged (marcado)
- `T` = Trashed (eliminado)
- `D` = Draft (borrador)

#### Dovecot: Acceso a Maildir

**Ventajas**:
- Acceso directo al filesystem
- No requiere conexiones IMAP limitadas
- Procesamiento más rápido
- Sin límites de conexiones simultáneas

**Desventajas**:
- Requiere permisos de lectura/escritura en `/var/vmail/`
- Debe ejecutarse con usuario apropiado (típicamente `vmail`)

#### Postfix: MTA (Mail Transfer Agent)

**Rol**:
- Recibe emails entrantes
- Entrega a Dovecot
- No es relevante para lectura de emails existentes

---

## Diseño de la Solución

### Opción Recomendada: Usar webklex/laravel-imap (Ya Implementado)

#### Ventajas
**Ya funciona**: Librería instalada y configurada  
**Código existente**: `ExtractOrganizationConfigurationEmailsJob` como base  
**Sin permisos especiales**: No requiere acceso al filesystem  
**Abstracción completa**: No necesita parsing manual de Maildir  
**Mantenible**: API documentada y soportada  
**Eliminar emails**: Método `delete()` integrado  

#### Consideraciones
Límite de 10 conexiones simultáneas (manejable con colas)  
Depende de servicio IMAP (ya funcionando en producción)  

#### Implementación - Aprovechar Código Existente

**Job Nuevo: ExtractReadEmailsToGoogleDriveJob**

Diferencias clave con el job existente:
1. **Procesa emails LEÍDOS** (no solo unseen)
2. **Elimina PERMANENTEMENTE** con `expunge()`
3. **Sube directo a Google Drive** (sin almacenar localmente)
4. **Libera espacio en disco** del servidor
5. **Auditoría completa** de cada operación

```php
// Diferencia clave: Obtener emails LEÍDOS con XML
$query = $folder->query()
    ->fetchOrderDesc()
    ->limit(100, 1); // Procesar hasta 100 emails por ejecución

// SIN filtro unseen() - obtiene todos los emails leídos
// SIN filtro since() - procesa todos los disponibles
$query->whereAttachment()->chunked(function($messages, $chunk) use ($googleDrive) {
    $messages->each(function($message) use ($googleDrive) {
        if ($message->hasAttachments()) {
            // Extraer XMLs
            // Subir a Google Drive
            // ELIMINAR PERMANENTEMENTE
            $message->delete(); // Marca para eliminación
        }
    });
});

// CRÍTICO: Expunge para eliminación permanente y liberar espacio
$folder->expunge(); // Elimina permanentemente los emails marcados
```

**Ventaja Principal**: 
- Limpia emails procesados del servidor
- Libera espacio en disco inmediatamente
- Mantiene copia segura en Google Drive

### Opción Alternativa: Acceso Directo a Maildir (Solo si IMAP falla)

#### Cuándo Considerar
- Solo si IMAP tiene problemas de rendimiento irresolubles
- Si necesitamos procesar emails antiguos sin conexión IMAP
- Para operaciones de recuperación/mantenimiento

#### Desventajas
Requiere permisos de sistema en `/var/vmail/`  
Parsing manual de formato Maildir  
Mayor complejidad de implementación  
Requiere librería adicional para parsing MIME  

**Recomendación**: Mantener esta opción como fallback, pero usar IMAP como solución principal.  

---

## Implementación Técnica

### 1. Service: GoogleDriveService

**Ubicación**: `app/Services/GoogleDriveService.php`

```php
<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected GoogleClient $client;
    protected GoogleDrive $drive;
    protected string $rootFolderId;
    
    public function __construct()
    {
        $this->initializeClient();
    }
    
    /**
     * Inicializar cliente de Google Drive
     */
    protected function initializeClient(): void
    {
        $this->client = new GoogleClient();
        
        // Usar Service Account para operaciones server-to-server
        $credentialsPath = base_path('service-account-credentials.json');
        
        if (!file_exists($credentialsPath)) {
            throw new \Exception("Credenciales de Google Drive no encontradas");
        }
        
        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope(GoogleDrive::DRIVE);
        $this->client->setApplicationName('DocuCenter Email Extraction');
        
        $this->drive = new GoogleDrive($this->client);
        
        // Obtener o crear carpeta raíz
        $this->rootFolderId = $this->ensureRootFolder();
    }
    
    /**
     * Asegurar que existe carpeta raíz "DocuCenter-Facturas"
     * 
     * @return string ID de la carpeta
     */
    protected function ensureRootFolder(): string
    {
        $folderName = 'DocuCenter-Facturas';
        
        // Buscar carpeta existente
        $response = $this->drive->files->listFiles([
            'q' => "name = '{$folderName}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);
        
        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }
        
        // Crear carpeta raíz
        $fileMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        
        $folder = $this->drive->files->create($fileMetadata, [
            'fields' => 'id',
        ]);
        
        Log::info("Carpeta raíz creada en Google Drive", [
            'folder_name' => $folderName,
            'folder_id' => $folder->getId()
        ]);
        
        return $folder->getId();
    }
    
    /**
     * Obtener o crear carpeta de organización por RUC-DV
     * 
     * @param Organization $organization
     * @return string ID de la carpeta
     */
    public function getOrganizationFolder(Organization $organization): string
    {
        $folderName = "{$organization->ruc}-{$organization->dv}";
        
        // Buscar carpeta existente
        $response = $this->drive->files->listFiles([
            'q' => "name = '{$folderName}' and '{$this->rootFolderId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);
        
        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }
        
        // Crear carpeta de organización
        $fileMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->rootFolderId],
            'description' => "Facturas electrónicas - {$organization->nombre}",
        ]);
        
        $folder = $this->drive->files->create($fileMetadata, [
            'fields' => 'id',
        ]);
        
        Log::info("Carpeta de organización creada", [
            'organization_id' => $organization->id,
            'ruc' => $organization->ruc,
            'dv' => $organization->dv,
            'folder_id' => $folder->getId()
        ]);
        
        return $folder->getId();
    }
    
    /**
     * Obtener o crear carpeta de año/mes
     * 
     * @param string $parentFolderId
     * @param int $year
     * @param int $month
     * @return string ID de la carpeta
     */
    public function getDateFolder(string $parentFolderId, int $year, int $month): string
    {
        // Crear estructura: 2026/01-Enero/
        $yearFolderId = $this->getOrCreateFolder($parentFolderId, (string)$year);
        
        $monthNames = [
            1 => '01-Enero', 2 => '02-Febrero', 3 => '03-Marzo',
            4 => '04-Abril', 5 => '05-Mayo', 6 => '06-Junio',
            7 => '07-Julio', 8 => '08-Agosto', 9 => '09-Septiembre',
            10 => '10-Octubre', 11 => '11-Noviembre', 12 => '12-Diciembre',
        ];
        
        $monthFolder = $monthNames[$month];
        
        return $this->getOrCreateFolder($yearFolderId, $monthFolder);
    }
    
    /**
     * Obtener o crear carpeta genérica
     */
    protected function getOrCreateFolder(string $parentId, string $name): string
    {
        // Buscar carpeta existente
        $response = $this->drive->files->listFiles([
            'q' => "name = '{$name}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);
        
        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }
        
        // Crear carpeta
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);
        
        $folder = $this->drive->files->create($fileMetadata, [
            'fields' => 'id',
        ]);
        
        return $folder->getId();
    }
    
    /**
     * Subir archivo XML a Google Drive
     * 
     * @param Organization $organization
     * @param string $xmlContent
     * @param string $filename
     * @param \Carbon\Carbon $date
     * @return array ['id' => string, 'webViewLink' => string]
     */
    public function uploadXml(
        Organization $organization,
        string $xmlContent,
        string $filename,
        \Carbon\Carbon $date
    ): array {
        // Obtener carpeta de organización
        $orgFolderId = $this->getOrganizationFolder($organization);
        
        // Obtener carpeta de fecha (año/mes)
        $dateFolderId = $this->getDateFolder(
            $orgFolderId,
            $date->year,
            $date->month
        );
        
        // Subir archivo
        $fileMetadata = new DriveFile([
            'name' => $filename,
            'parents' => [$dateFolderId],
            'description' => "Factura electrónica - {$organization->nombre}",
        ]);
        
        $file = $this->drive->files->create($fileMetadata, [
            'data' => $xmlContent,
            'mimeType' => 'application/xml',
            'uploadType' => 'multipart',
            'fields' => 'id, name, webViewLink, createdTime',
        ]);
        
        Log::info("Archivo XML subido a Google Drive", [
            'organization_id' => $organization->id,
            'filename' => $filename,
            'file_id' => $file->getId(),
            'folder_id' => $dateFolderId
        ]);
        
        return [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'webViewLink' => $file->getWebViewLink(),
            'createdTime' => $file->getCreatedTime(),
        ];
    }
    
    /**
     * Eliminar archivo de Google Drive
     * 
     * @param string $fileId
     * @return bool
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $this->drive->files->delete($fileId);
            
            Log::info("Archivo eliminado de Google Drive", [
                'file_id' => $fileId
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error eliminando archivo de Google Drive", [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Verificar si archivo existe en Google Drive
     * 
     * @param string $fileId
     * @return bool
     */
    public function fileExists(string $fileId): bool
    {
        try {
            $this->drive->files->get($fileId, ['fields' => 'id']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

### 2. Job: ExtractReadEmailsToGoogleDriveJob (Job Nuevo)

**Ubicación**: `app/Jobs/ExtractReadEmailsToGoogleDriveJob.php`

**Características Clave**:
- Procesa **emails LEÍDOS** (sin filtro unseen)
- Eliminación **PERMANENTE** con expunge()
- Libera **espacio en disco** inmediatamente
- Sin almacenamiento local temporal

```php
<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\Configurationemailorganization;
use App\Services\GoogleDriveService;
use App\Models\GoogleDriveUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ExtractReadEmailsToGoogleDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Configurationemailorganization $configuration;
    protected Organization $organization;
    
    // Estados del Job
    const STATE_INIT = 1;
    const STATE_CONNECTING_IMAP = 2;
    const STATE_READING_EMAILS = 3;
    const STATE_EXTRACTING_XML = 4;
    const STATE_UPLOADING_DRIVE = 5;
    const STATE_DELETING_PERMANENTLY = 6;
    const STATE_COMPLETED = 7;
    
    protected int $state = self::STATE_INIT;
    
    public $tries = 3;
    public $timeout = 900; // 15 minutos (más tiempo por procesamiento masivo)

    public function __construct(Configurationemailorganization $configuration)
    {
        $this->configuration = $configuration;
        $this->organization = $configuration->organization;
        $this->onQueue('email-to-drive');
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleDriveService $googleDrive): void
    {
        DB::beginTransaction();
        
        try {
            $this->state = self::STATE_CONNECTING_IMAP;
            
            Log::info("Iniciando extracción de emails LEÍDOS a Google Drive", [
                'organization_id' => $this->organization->id,
                'ruc' => $this->organization->ruc,
                'dv' => $this->organization->dv,
                'email' => $this->configuration->email,
                'state' => $this->state
            ]);
            
            // Conectar vía IMAP
            $client = null;
            try {
                $client = $this->configuration->clientManagerMake();
            } catch (Exception $e) {
                Log::error("Error en conexión IMAP", [
                    'email' => $this->configuration->email,
                    'error' => $e->getMessage()
                ]);
                DB::rollBack();
                return;
            }
            
            $this->state = self::STATE_READING_EMAILS;
            
            // Obtener carpeta INBOX
            $folder = $client->getFolderByPath('INBOX');
            
            // IMPORTANTE: Sin filtro unseen() - procesa TODOS los emails
            // Con filtro whereAttachment() - solo emails con archivos adjuntos
            $query = $folder->query()
                ->whereAttachment()
                ->fetchOrderDesc()
                ->limit(100, 1); // Procesar hasta 100 emails por ejecución
            
            $processed = 0;
            $errors = 0;
            $diskSpaceFreed = 0;
            
            // Procesar emails con attachments XML
            $query->chunked(function($messages, $chunk) use ($googleDrive, $folder, &$processed, &$errors, &$diskSpaceFreed) {
                $messages->each(function($message) use ($googleDrive, &$processed, &$errors, &$diskSpaceFreed) {
                    if ($message->hasAttachments()) {
                        try {
                            $messageSize = $message->getSize();
                            $this->processMessage($message, $googleDrive);
                            $diskSpaceFreed += $messageSize;
                            $processed++;
                        } catch (Exception $e) {
                            $errors++;
                            Log::error("Error procesando mensaje", [
                                'message_id' => $message->getMessageId(),
                                'subject' => $message->getSubject(),
                                'error' => $e->getMessage()
                            ]);
                            // Continuar con el siguiente mensaje
                        }
                    }
                });
                
                // CRÍTICO: Expunge después de cada chunk para liberar espacio
                $this->state = self::STATE_DELETING_PERMANENTLY;
                $folder->expunge();
                
                Log::info("Chunk procesado y emails eliminados permanentemente", [
                    'chunk' => $chunk,
                    'processed_in_chunk' => $processed,
                    'disk_space_freed_mb' => round($diskSpaceFreed / 1024 / 1024, 2)
                ]);
                
            }, 10, 1); // Procesar de 10 en 10
            
            $this->state = self::STATE_COMPLETED;
            
            Log::info("Extracción y limpieza completada", [
                'organization_id' => $this->organization->id,
                'ruc_dv' => "{$this->organization->ruc}-{$this->organization->dv}",
                'total_processed' => $processed,
                'total_errors' => $errors,
                'disk_space_freed_mb' => round($diskSpaceFreed / 1024 / 1024, 2),
                'state' => $this->state
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error crítico en ExtractReadEmailsToGoogleDriveJob", [
                'organization_id' => $this->organization->id,
                'state' => $this->state,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Procesar un mensaje individual
     */
    protected function processMessage($message, GoogleDriveService $googleDrive): void
    {
        $this->state = self::STATE_EXTRACTING_XML;
        
        $files = $message->attachments();
        $xmlFiles = [];
        
        // Recolectar archivos XML
        $files->each(function($file) use (&$xmlFiles) {
            $extension = strtolower($file->getExtension());
            
            if ($extension === 'xml') {
                $xmlFiles[] = [
                    'filename' => $file->getName(),
                    'content' => $file->getContent(),
                    'size' => $file->getSize(),
                ];
            }
        });
        
        if (empty($xmlFiles)) {
            Log::debug("Email sin archivos XML, omitiendo", [
                'message_id' => $message->getMessageId(),
                'subject' => $message->getSubject()
            ]);
            return;
        }
        
        $this->state = self::STATE_UPLOADING_DRIVE;
        
        $uploadedFiles = [];
        
        // Subir cada XML a Google Drive
        foreach ($xmlFiles as $xmlFile) {
            try {
                $uploadResult = $googleDrive->uploadXml(
                    $this->organization,
                    $xmlFile['content'],
                    $xmlFile['filename'],
                    $message->getDate() ?? now()
                );
                
                // Registrar en base de datos
                $upload = GoogleDriveUpload::create([
                    'organization_id' => $this->organization->id,
                    'file_name' => $xmlFile['filename'],
                    'google_drive_id' => $uploadResult['id'],
                    'google_drive_link' => $uploadResult['webViewLink'],
                    'maildir_path' => $message->getMessageId(),
                    'file_size' => $xmlFile['size'],
                    'uploaded_at' => now(),
                    'status' => 'uploaded'
                ]);
                
                $uploadedFiles[] = $upload->id;
                
                Log::info("Archivo XML subido a Google Drive", [
                    'filename' => $xmlFile['filename'],
                    'google_drive_id' => $uploadResult['id'],
                    'organization_id' => $this->organization->id,
                    'size_kb' => round($xmlFile['size'] / 1024, 2)
                ]);
                
            } catch (Exception $e) {
                Log::error("Error subiendo archivo XML", [
                    'filename' => $xmlFile['filename'],
                    'organization_id' => $this->organization->id,
                    'error' => $e->getMessage()
                ]);
                // No eliminar email si falló la subida
                throw $e;
            }
        }
        
        // SOLO eliminar si TODOS los archivos se subieron exitosamente
        if (count($uploadedFiles) === count($xmlFiles)) {
            $this->state = self::STATE_DELETING_PERMANENTLY;
            
            // Marcar para eliminación (expunge se ejecuta después del chunk)
            if ($message->delete()) {
                // Actualizar registros
                GoogleDriveUpload::whereIn('id', $uploadedFiles)
                    ->update([
                        'deleted_at' => now(),
                        'status' => 'completed'
                    ]);
                    
                Log::info("Email marcado para eliminación permanente", [
                    'message_id' => $message->getMessageId(),
                    'subject' => $message->getSubject(),
                    'xml_files_count' => count($xmlFiles)
                ]);
            }
        } else {
            Log::warning("No se eliminará email por fallos en subida", [
                'message_id' => $message->getMessageId(),
                'uploaded' => count($uploadedFiles),
                'total' => count($xmlFiles)
            ]);
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error("ExtractReadEmailsToGoogleDriveJob falló completamente", [
            'organization_id' => $this->organization->id,
            'email' => $this->configuration->email,
            'state' => $this->state,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### Diferencias Clave vs Job Existente

| Aspecto | Job Existente | Nuevo Job |
|---------|--------------|-----------|
| Emails | `unseen()` solo no leídos | Todos los leídos |
| Filtro fecha | `since($date)` | Sin filtro |
| Límite | 50 emails | 100 emails |
| Eliminación | `delete()` simple | `delete()` + `expunge()` |
| Espacio disco | No libera | Libera inmediatamente |
| Procesamiento | Chunks de 10 | Chunks de 10 + expunge |
| Auditoría | Básica | Incluye espacio liberado |

### 3. Modelo: GoogleDriveUpload

**Ubicación**: `app/Models/GoogleDriveUpload.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\CustomConnection;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $file_name
 * @property string $google_drive_id
 * @property string $google_drive_link
 * @property string $maildir_path
 * @property int $file_size
 * @property string $status
 * @property \Carbon\Carbon $uploaded_at
 * @property \Carbon\Carbon $deleted_at
 */
class GoogleDriveUpload extends Model
{
    use HasFactory, CustomConnection;

    protected $connection = 'mysql';
    protected $table = 'google_drive_uploads';
    
    protected $fillable = [
        'organization_id',
        'file_name',
        'google_drive_id',
        'google_drive_link',
        'maildir_path',
        'file_size',
        'uploaded_at',
        'deleted_at',
        'status',
    ];
    
    protected $casts = [
        'uploaded_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * Estados posibles:
     * - uploading: Subiendo a Google Drive
     * - uploaded: Subido exitosamente
     * - completed: Subido y email eliminado
     * - failed: Error en subida
     */
    const STATUS_UPLOADING = 'uploading';
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setDefaultConnection();
    }
    
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
```

### 4. Migración: create_google_drive_uploads_table

**Ubicación**: `database/migrations/YYYY_MM_DD_create_google_drive_uploads_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('file_name');
            $table->string('google_drive_id')->unique();
            $table->text('google_drive_link')->nullable();
            $table->text('maildir_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('status', ['uploading', 'uploaded', 'completed', 'failed'])->default('uploading');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->index('organization_id');
            $table->index('status');
            $table->index('uploaded_at');
            
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_uploads');
    }
};
```

### 5. Command: ExtractMaildirCommand

**Ubicación**: `app/Console/Commands/ExtractMaildirCommand.php`

```php
<?phpEmailToDriveCommand

**Ubicación**: `app/Console/Commands/ExtractEmailToDriveCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Configurationemailorganization;
use App\Jobs\ExtractEmailToGoogleDriveJob;
use Illuminate\Console\Command;

class ExtractEmailToDriveCommand extends Command
{
    protected $signature = 'email:extract-to-drive
                            {--org-id= : ID de organización específica}
                            {--all : Procesar todas las organizaciones}
                            {--queue : Ejecutar en cola (recomendado)}';

    protected $description = 'Extraer archivos XML de emails vía IMAP y subir a Google Drive';

    public function handle()
    {
        $this->info(" Iniciando extracción de emails a Google Drive");
        $this->newLine();
        
        $useQueue = $this->option('queue');
        $orgId = $this->option('org-id');
        $all = $this->option('all');
        
        // Determinar organizaciones a procesar
        if ($orgId) {
            $configurations = Configurationemailorganization::where('organization_id', $orgId)
                ->with('organization')
                ->get();
        } elseif ($all) {
            $configurations = Configurationemailorganization::with('organization')->get();
        } else {
            $this->error('Debe especificar --org-id o --all');
            return Command::FAILURE;
        }
        
        if ($configurations->isEmpty()) {
            $this->error('No se encontraron configuraciones de email');
            return Command::FAILURE;
        }
        
        $this->info("Configuraciones a procesar: {$configurations->count()}");
        $this->newLine();
        
        $queued = 0;
        $processed = 0;
        
        foreach ($configurations as $config) {
            $organization = $config->organization;
            
            if (!$organization) {
                $this->warn("Configuración {$config->id} sin organización asociada");
                continue;
            }
            
            $this->info("Organización: {$organization->nombre}");
            $this->info(" RUC: {$organization->ruc}-{$organization->dv}");
            $this->info(" Email: {$config->email}");
            
            if ($useQueue) {
                ExtractEmailToGoogleDriveJob::dispatch($config);
                $this->info("Job agregado a la cola");
                $queued++;
            } else {
                $this->info(" Ejecutando síncronamente...");
                try {
                    ExtractEmailToGoogleDriveJob::dispatchSync($config);
                    $this->info("Completado");
                    $processed++;
                } catch (\Exception $e) {
                    $this->error("Error: {$e->getMessage()}");
                }
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        if ($useQueue) {
            $this->info("{$queued} jobs agregados a la cola");
            $this->info("Monitorea el progreso en: storage/logs/laravel.log");
        } else {
            $this->info("{$processed} configuraciones procesadas");
        }

---

## Flujo de Datemail:extract-to-drive --all --queue
   ```

2. **ExtractEmailToGoogleDriveJob se ejecuta**
   - Conecta vía IMAP usando `webklex/laravel-imap`
   - Lee carpeta INBOX buscando emails no leídos con XML
   - Usa el mismo código probado del job existente

3. **Extracción de XMLs vía IMAP**
   - Detecta attachments con extensión `.xml`
   - Extrae contenido usando `$file->getContent()`
   - Todo en memoria, sin archivos temporales.me/{email}/Maildir/cur`
   - Identifica emails con archivos XML adjuntos

3. **Extracción de XMLs vía IMAP**
   - Detecta attachments con extensión `.xml`
   - Extrae contenido usando `$file->getContent()`
   - Todo en memoria, sin archivos temporales locales

4. **GoogleDriveService organiza y sube**
   - Obtiene/crea carpeta: `{RUC}-{DV}`
   - Obtiene/crea subcarpeta: `{Año}/{Mes}`
   - Sube archivo XML
   - Retorna ID y link del archivo

5. **GoogleDriveUpload registra operación**
   - Guarda metadata en base de datos
   - Estado: `uploaded`
IMAP

**No requiere permisos especiales de sistema**

Usa las credenciales existentes en `Configurationemailorganization`:
- Email: `$config->email`
- Password: `$config->password`
- Host: `apconpanama.me`
- Puerto: `993` (IMAP SSL)

**Ventaja**: Sin configuración adicional de permisosConsideraciones de Seguridad

### 1. Permisos de Sistema

**Usuario recomendado**: `vmail` (usuario de Dovecot)

```bash
# Agregar usuario web al grupo vmail
sudo usermod -a -G vmail www-data

# Permisos en Maildir
sudo chmod 750 /var/vmail/apconpanama.me
sudo chown -R vmail:vmail /var/vmail/apconpanama.me
```

### 2. Credenciales Google Drive

**Service Account** (Recomendado para server-to-server):
- No requiere interacción de usuario
- Permisos controlados por IAM
- Renovación automática de tokens

**Configuración**:
```json
{
  "type": "service_account",
  "project_id": "docucenter-xxxx",
  "private_key_id": "xxxx",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "docucenter@docucenter-xxxx.iam.gserviceaccount.com",
  "client_id": "xxxxxxxxxxxxx",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token"
}
```

### 3. Validación de XML

**Antes de subir**:
```php
// En GoogleDriveService::uploadXml()
protected function validateXml(string $xmlContent): bool
{
    try {
        $xml = new \SimpleXMLElement($xmlContent);
        
        // Validar estructura mínima
        if (!isset($xml->rFE) || !isset($xml->rFE->dGen)) {
            return false;
        }
        
        return true;
    } catch (\Exception $e) {
        Log::warning("XML inválido", ['error' => $e->getMessage()]);
        return false;
    }
}
```

### 4. Manejo de Errores y Rollback

**Estrategia de Reintentos**:
- 3 intentos automáticos
- Backoff exponencial: 1min, 5min, 15min
- Si falla 3 veces: mover a cola `failed_jobs`

**Rollback Crítico - Protección de Datos**:
```php
// REGLA: NUNCA eliminar email si no se subió a Google Drive
try {
    $uploadResult = $googleDrive->uploadXml($org, $xmlContent, $filename, $date);
    
    // Solo si la subida fue exitosa
    GoogleDriveUpload::create([...]);
    
    // SOLO AHORA eliminar email
    $message->delete();
    
} catch (\Exception $e) {
    // NO eliminar email si hubo error
    Log::error("Subida falló, email NO será eliminado", [
        'message_id' => $message->getMessageId(),
        'error' => $e->getMessage()
    ]);
    throw $e; // Propagar error para reintento
}

// Expunge solo si todos los emails del chunk se procesaron exitosamente
if ($allSuccess) {
    $folder->expunge(); // Eliminar permanentemente
}
```

**Política de Seguridad**:
1. Subir a Google Drive primero
2. Verificar subida exitosa
3. Crear registro en BD
4. SOLO ENTONCES marcar para eliminar
5. Expunge al final del chunk exitoso

**Nunca se perderán datos**: El email solo se elimina si está respaldado en Google Drive.

---

## Plan de ImpConfiguración IMAP
- [ ] Verificar configuraciones de email existentes
- [ ] Probar conexión IMAP con `clientManagerMake()`
- [ ] Validar acceso a carpeta INBOX
- [ ] Confirmar que `delete()` funciona correctamente
- [ ] Crear proyecto en Google Cloud Console
- [ ] Habilitar Google Drive API
- [ ] Crear Service Account
- [ ] Descargar credenciales JSON
- [ ] Ubicar en raíz del proyecto
- [ ] Probar autenticación

#### Día 3-4: Permisos de Sistema
- [ ] Configurar permisos en `/var/vmail/`
- [ ] Agregar usuario web a grupo `vmail`
- [ ] Probar acceso de lectura a Maildir
- [ ] Documentar configuración de permisos

#### Día 5: Base de Datos
- [ ] Crear migración `google_drive_uploads`
- [ ] Ejecutar migración
- [ ] Crear modelo ExtractEmailToGoogleDriveJob`
- [ ] Reutilizar lógica de `ExtractOrganizationConfigurationEmailsJob`
- [ ] Integrar subida a Google Drive
- [ ] Implementar eliminación vía `$message->delete()`
- [ ] Unit tests para el Job completo
#### Semana 2
- [ ] Desarrollar `GoogleDriveService`
- [ ] Implementar métodos de creación de carpetas
- [ ] Implementar método de subida de archivos
- [ ] Unit tests para Google Drive Service

#### Semana 3
- [ ] Desarrollar `MaildirReaderService`
- [ ] Implementar lectura de Maildir
- [ ] Implementar extracción de XMLs
- [ ] Implementar eliminación de emails
- [ ] Unit tests para Maildir Reader

### Fase 3: Jobs y Commands (Semana 4)

- [ ] Crear `ExtractMaildirXmlJob`
- [ ] Implementar estados granulares
- [ ] Implementar manejo de errores
- [ ] Crear `ExtractMaildirCommand`
- [ ] Probar ejecución síncrona
- [ ] Probar ejecución en cola

### Fase 4: Testing (Semana 5)

#### Testing Unitario
- [ ] Tests de `ExtractEmailToGoogleDriveJob`
- [ ] Tests de `GoogleDriveUpload` model
- [ ] Mock de conexión IMAP para tests
- [ ] Tests de `GoogleDriveUpload` model

#### Testing de Integración
- [ ] Test end-to-end con email real
- [ ] Test de subida a Google Drive
- [ ] Test de eliminación de Maildir
- [ ] Test de rollback en errores

#### Testing de Carga
- [ ] Procesar 100 emails simultáneos
- [ ] Verificar uso de memoria
- [ ] Verificar tiempos de ejecución
- [ ] Optimizar queries si es necesario

### Fase 5: Deployment (Semana 6)

#### Staging
- [ ] Desplegar en ambde última semana (test)
- [ ] Verificar estructura en Google Drive
- [ ] Validar logs y auditoría
- [ ] Confirmar que emails se eliminan correctamente

#### Producción
- [ ] Desplegar en producción
- [ ] Configurar límite de conexiones IMAP concurrentes (5-8) antes de deployment
- [ ] Desplegar en producción
- [ ] Configurar cron job
- [ ] Monitoreo de primera ejecución
- [ ] Documentación para equipo de soporte

### Fase 6: Automatización (Semana 7)

#### Scheduler
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{email:extract-to-drive --all --queue')
        ->everyTwoHours()
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/email-to-drive.log'));
        
    // Opcional: Ejecutar más frecuente para organizaciones específicas
    $schedule->command('email:extract-to-drive --org-id=2 --queue')
        ->hourly()
        ->withoutOverlapping();
}
```

#### Manejo de Límite de Conexiones IMAP

Para evitar el error de "Maximum number of connections exceeded":

```php
// config/queue.php
'connections' => [
    'email-to-drive' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'email-to-drive',
        'retry_after' => 600,
        'block_for' => null,
        'processes' => 5, // Máximo 5 workers concurrentes para IMAP
    ],
],       ->runInBackground()
        ->appendOutputTo(storage_path('logs/maildir-extraction.log'));
}
```

#### Monitoring
- [ ] Configurar alertas de fallos
- [ ] Dashboard de estadísticas
- [ ] Reportes semanales de procesamiento

---

## Scripts de Utilidad

### 1. Script de Testing Interactivo

**Ubicación**: `scripts/test-google-drive-extraction.sh`

```bash
#!/bin/bash

# Script de pruebas para extracción de Maildir a Google Drive

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "${BLUE}${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN} $1${NC}"
}

print_error() {
    echo -e "${RED} $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}$1${NC}"
}

# Función de ayuda
show_help() {
    cat << EOF
Uso: $0 [COMANDO] [OPCIONES]

Comandos:
    test-credentials    Probar credenciales de Google Drive
    test-folders        Probar creación de estructura de carpetas
    test-upload         Probar subida de archivo XML de prueba
    test-maildir        Probar lectura de Maildir
    test-extraction     Ejecutar extracción completa (test)
    interactive         Modo interactivo

Opciones:
    --org-id=ID         ID de organización para pruebas
    --email=EMAIL       Email específico para pruebas

Ejemplos:
    $0 test-credentials
    $0 test-upload --org-id=2
    $0 test-extraction --org-id=2 --email=test@apconpanama.me
    $0 interactive

EOF
}

# Test de credenciales
test_credentials() {
    print_header "Test de Credenciales Google Drive"
    
    if [ ! -f "service-account-credentials.json" ]; then
        print_error "No se encontró service-account-credentials.json"
        exit 1
    fi
    
    print_success "Archivo de credenciales encontrado"
    
    echo "Probando autenticación..."
    php artisan tinker --execute="
        \$service = new \App\Services\GoogleDriveService();
        echo 'Autenticación exitosa';
    "
    
    if [ $? -eq 0 ]; then
        print_success "Autenticación exitosa"
    else
        print_error "Fallo en autenticación"
        exit 1
    fi
}

# Test de carpetas
test_folders() {
    print_header "Test de Estructura de Carpetas"
    
    ORG_ID=$1
    
    if [ -z "$ORG_ID" ]; then
        print_error "Debe especificar --org-id"
        exit 1
    fi
    
    echo "Creando estructura de carpetas para organización ID: $ORG_ID"
    
    php artisan tinker --execute="
        \$org = \App\Models\Organization::findOrFail($ORG_ID);
        \$service = new \App\Services\GoogleDriveService();
        
        echo 'Organización: ' . \$org->nombre . PHP_EOL;
        echo 'RUC: ' . \$org->ruc . '-' . \$org->dv . PHP_EOL;
        
        \$folderId = \$service->getOrganizationFolder(\$org);
        echo 'Carpeta creada/encontrada: ' . \$folderId . PHP_EOL;
        
        \$dateFolderId = \$service->getDateFolder(\$folderId, 2026, 1);
        echo 'Carpeta de fecha: ' . \$dateFolderId . PHP_EOL;
    "
    
    if [ $? -eq 0 ]; then
        print_success "Estructura de carpetas creada exitosamente"
    else
        print_error "Error creando estructura de carpetas"
        exit 1
    fi
}

# Test de subida
test_upload() {
    print_header "Test de Subida de Archivo"
    
    ORG_ID=$1
    
    if [ -z "$ORG_ID" ]; then
        print_error "Debe especificar --org-id"
        exit 1
    fi
    
    # Crear XML de prueba
    cat > /tmp/test-invoice.xml << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<rFE>
    <dGen>
        <dFechaEm>2026-01-10</dFechaEm>
        <dNroDF>FE-001-001-00000001</dNroDF>
    </dGen>
</rFE>
EOF
    
    print_success "Archivo XML de prueba creado"
    
    echo "Subiendo archivo a Google Drive..."
    
    php artisan tinker --execute="
        \$org = \App\Models\Organization::findOrFail($ORG_ID);
        \$service = new \App\Services\GoogleDriveService();
        
        \$xmlContent = file_get_contents('/tmp/test-invoice.xml');
        \$result = \$service->uploadXml(
            \$org,
            \$xmlContent,
            'TEST-FE-001-001-00000001.xml',
            now()
        );
        
        echo 'Archivo subido' . PHP_EOL;
        echo 'ID: ' . \$result['id'] . PHP_EOL;
        echo 'Link: ' . \$result['webViewLink'] . PHP_EOL;
    "
    
    if [ $? -eq 0 ]; then
        print_success "Archivo subido exitosamente"
        rm /tmp/test-invoice.xml
    else
        print_error "Error subiendo archivo"
        exit 1
    fi
}

# Test de Maildir
test_maildir() {
    print_header "Test de Lectura de Maildir"
    
    EMAIL=$1
    
    if [ -z "$EMAIL" ]; then
        print_error "Debe especificar --email"
        exit 1
    fi
    
    echo "Escaneando Maildir para: $EMAIL"
    
    php artisan tinker --execute="
        \$service = new \App\Services\MaildirReaderService();
        \$emails = \$service->getXmlEmails('$EMAIL');
        
        echo 'Emails encontrados con XML: ' . count(\$emails) . PHP_EOL;
        
        foreach (\$emails as \$email) {
            echo PHP_EOL;
            echo 'Path: ' . \$email['path'] . PHP_EOL;
            echo 'Size: ' . \$email['size'] . ' bytes' . PHP_EOL;
            echo 'Folder: ' . \$email['folder'] . PHP_EOL;
        }
    "
}

# Modo interactivo
interactive_mode() {
    print_header "Modo Interactivo - Google Drive Extraction"
    
    echo "Seleccione una opción:"
    echo "1) Test de credenciales"
    echo "2) Test de estructura de carpetas"
    echo "3) Test de subida de archivo"
    echo "4) Test de lectura de Maildir"
    echo "5) Extracción completa (test)"
    echo "6) Salir"
    echo ""
    read -p "Opción: " option
    
    case $option in
        1)
            test_credentials
            ;;
        2)
            read -p "ID de organización: " org_id
            test_folders "$org_id"
            ;;
        3)
            read -p "ID de organización: " org_id
            test_upload "$org_id"
            ;;
        4)
            read -p "Email: " email
            test_maildir "$email"
            ;;
        5)
            read -p "ID de organización: " org_id
            read -p "Email: " email
            php artisan maildir:extract --org-id="$org_id" --email="$email"
            ;;
        6)
            exit 0
            ;;
        *)
            print_error "Opción inválida"
            exit 1
            ;;
    esac
}

# Main
COMMAND=${1:-interactive}

case $COMMAND in
    test-credentials)
        test_credentials
        ;;
    test-folders)
        ORG_ID=$(echo $2 | sed 's/--org-id=//')
        test_folders "$ORG_ID"
        ;;
    test-upload)
        ORG_ID=$(echo $2 | sed 's/--org-id=//')
        test_upload "$ORG_ID"
        ;;
    test-maildir)
        EMAIL=$(echo $2 | sed 's/--email=//')
        test_maildir "$EMAIL"
        ;;
    interactive)
        interactive_mode
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        print_error "Comando desconocido: $COMMAND"
        show_help
        exit 1
        ;;
esac

echo ""
print_success "Test completado"
```

---

## Documentación de Referencia

### APIs y Librerías
- [Google Drive API v3](https://developers.google.com/workspace/drive/api/reference/rest/v3)
- [Google API Client PHP](https://github.com/googleapis/google-api-php-client)
- [Webklex Laravel IMAP](https://github.com/Webklex/laravel-imap)

### Maildir
- [Maildir Format Specification](https://cr.yp.to/proto/maildir.html)
- [Dovecot Maildir](https://doc.dovecot.org/configuration_manual/mail_location/maildir/)

### Seguridad
- [Google Service Accounts](https://cloud.google.com/iam/docs/service-accounts)
- [OAuth 2.0 for Server to Server](https://developers.google.com/identity/protocols/oauth2/service-account)

---

## Conclusiones y Recomendaciones

### Recomendaciones Técnicas

1. **Usar Acceso Directo a Maildir** (Opción 1)
   - Mejor rendimiento
   - Sin límites de conexiones
   - Control total sobre archivos

2. **Service Account para Google Drive**
   - Operaciones desatendidas
   - Sin interacción de usuario
   - Renovación automática de tokens

3. **Procesamiento en Cola (Redis)**
   - Evita timeouts
   - Mejor manejo de errores
   - Monitoreo de progreso

4. **Auditoría Completa**
   - Tabla `google_drive_uploads`
   - Logs detallados
   - Trazabilidad completa

### Próximos Pasos

1. Instalar librería PHP MIME parser (si no existe)
2. Configurar Service Account en Google Cloud
3. Implementar `GoogleDriveService`
4. Implementar `MaildirReaderService`
5. Crear `ExtractMaildirXmlJob`
6. Probar en ambiente de desarrollo
7. Desplegar en producción

### Métricas de Éxito

- 100% de emails con XML procesados
- 0% pérdida de datos
- Estructura organizada por RUC-DV
- Auditoría completa de operaciones
- Emails eliminados solo después de subida exitosa

---

**Documento creado**: 2026-01-10  
**Autor**: DocuCenter Development Team  
**Versión**: 1.0
