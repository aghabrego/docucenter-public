# Links de Configuración de Google Cloud - DocuCenter

## Información del Proyecto

- **Proyecto Google Cloud**: `docucenter-aci`
- **Service Account Email**: `firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com`
- **Service Account Client ID**: `108189760262793992527`

---

## 🔗 Links Directos de Consolas

### Dashboard Principal
**URL**: [https://console.cloud.google.com/home/dashboard?project=docucenter-aci](https://console.cloud.google.com/home/dashboard?project=docucenter-aci)

Vista general del proyecto con métricas y servicios activos.

---

## Opción 1: Configurar Shared Drive (Recomendado)

### 1. Crear y Gestionar Shared Drive
**URL**: [https://drive.google.com/drive/shared-drives](https://drive.google.com/drive/shared-drives)

**Pasos de Configuración**:

1. Click en botón "New" o "+ Nuevo"
2. Nombre sugerido: `DocuCenter-Facturas`
3. Click en "Create"
4. En el Shared Drive recién creado:
   - Click en el icono de configuración (⚙️)
   - Seleccionar "Manage members"
5. Agregar miembro:
   - Email: `firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com`
   - Rol: **Content Manager** o **Manager** (NO usar Viewer o Commenter)
   - Click en "Send" o "Enviar"
6. Copiar el ID del Shared Drive desde la URL del navegador:
   - URL será algo como: `https://drive.google.com/drive/folders/0AByG_Vq7Zf8qUk9PVA`
   - El ID es: `0AByG_Vq7Zf8qUk9PVA` (la parte después de `/folders/`)

**Agregar a .env**:
```env
GOOGLE_SHARED_DRIVE_ID=0AByG_Vq7Zf8qUk9PVA
```

**Aplicar configuración**:
```bash
docker exec -it docucenter_laravel.test php artisan config:cache
docker exec -it docucenter_laravel.test php artisan drive:verify-setup
```

---

## Opción 2: Configurar Domain-Wide Delegation (Alternativa)

### 1. Gestionar Service Accounts
**URL**: [https://console.cloud.google.com/iam-admin/serviceaccounts?project=docucenter-aci](https://console.cloud.google.com/iam-admin/serviceaccounts?project=docucenter-aci)

**Pasos**:
1. Buscar y click en: `firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com`
2. Ir a pestaña "Details" o "Detalles"
3. Scroll down a "Advanced settings" o "Configuración avanzada"
4. Buscar "Domain-wide delegation"
5. Click en "Enable Google Workspace Domain-wide Delegation"
6. Copiar el **Client ID**: `108189760262793992527`

### 2. Autorizar en Google Workspace Admin Console
**URL**: [https://admin.google.com/ac/owl/domainwidedelegation](https://admin.google.com/ac/owl/domainwidedelegation)

**Requisitos**: Necesitas ser Super Admin del Google Workspace

**Pasos**:
1. Click en "Add new" o "Agregar nuevo"
2. **Client ID**: `108189760262793992527`
3. **OAuth scopes**: 
   ```
   https://www.googleapis.com/auth/drive
   ```
4. Click en "Authorize" o "Autorizar"
5. Especificar el email del usuario que actuará como "propietario":
   - Debe ser un usuario existente en tu Workspace
   - Este usuario debe tener espacio de almacenamiento suficiente

**Agregar a .env**:
```env
GOOGLE_DRIVE_DELEGATE_EMAIL=admin@tudominio.com
```

**Aplicar configuración**:
```bash
docker exec -it docucenter_laravel.test php artisan config:cache
docker exec -it docucenter_laravel.test php artisan drive:verify-setup
```

---

## Verificaciones Adicionales

### Verificar API de Google Drive está Habilitada
**URL**: [https://console.cloud.google.com/apis/library/drive.googleapis.com?project=docucenter-aci](https://console.cloud.google.com/apis/library/drive.googleapis.com?project=docucenter-aci)

**Debe mostrar**: "API enabled" (API habilitada)

Si no está habilitada, click en "Enable" o "Habilitar"

### Ver Todas las APIs Habilitadas
**URL**: [https://console.cloud.google.com/apis/dashboard?project=docucenter-aci](https://console.cloud.google.com/apis/dashboard?project=docucenter-aci)

Lista de todas las APIs activas en el proyecto.

### Credenciales del Proyecto
**URL**: [https://console.cloud.google.com/apis/credentials?project=docucenter-aci](https://console.cloud.google.com/apis/credentials?project=docucenter-aci)

Ver todas las credenciales (API Keys, OAuth 2.0, Service Accounts).

### IAM y Permisos
**URL**: [https://console.cloud.google.com/iam-admin/iam?project=docucenter-aci](https://console.cloud.google.com/iam-admin/iam?project=docucenter-aci)

Gestionar permisos y roles del proyecto.

### Logs y Monitoreo
**URL**: [https://console.cloud.google.com/logs/query?project=docucenter-aci](https://console.cloud.google.com/logs/query?project=docucenter-aci)

Ver logs de actividad de las APIs y servicios.

---

## Comandos de Verificación

### Listar Shared Drives Disponibles
```bash
docker exec -it docucenter_laravel.test php artisan drive:list-shared-drives
```

Muestra todos los Shared Drives a los que la Service Account tiene acceso.

### Verificar Configuración Completa
```bash
docker exec -it docucenter_laravel.test php artisan drive:verify-setup
```

Verifica credenciales, migración, conexión y configuración.

### Prueba de Subida Real
```bash
docker exec -it docucenter_laravel.test php artisan drive:verify-setup --test-upload --organization_id=2
```

Intenta subir un archivo de prueba a Google Drive.

### Limpiar Cache de Configuración
```bash
docker exec -it docucenter_laravel.test php artisan config:cache
```

Aplicar cambios del archivo `.env`.

---

## Troubleshooting

### Error: "Service Accounts do not have storage quota"

**Causa**: Service Account sin configuración de almacenamiento

**Solución**: Configurar Shared Drive o Domain Delegation (ver arriba)

### Error: "Permission denied" o "Insufficient permissions"

**Causa**: Service Account no tiene permisos en Shared Drive

**Verificar**:
1. Service Account está agregada como miembro del Shared Drive
2. Tiene rol "Content Manager" o "Manager" (NO "Viewer" o "Commenter")
3. Esperar 5-10 minutos después de agregar permisos

### Error: "Invalid delegation" o "Domain-wide delegation not enabled"

**Causa**: Domain-Wide Delegation no configurado correctamente

**Verificar**:
1. Domain-Wide Delegation está habilitado en Service Account
2. Client ID autorizado en Google Workspace Admin Console
3. Scopes correctos: `https://www.googleapis.com/auth/drive`
4. Email de delegación existe y es correcto

### No se muestran Shared Drives al ejecutar comando

**Causa**: Service Account no tiene acceso a ningún Shared Drive

**Solución**:
1. Crear Shared Drive si no existe
2. Agregar Service Account como miembro
3. Esperar unos minutos para propagación de permisos

---

## Configuración Actual

### Variables en .env

```env
# Google Cloud Project
GOOGLE_CLOUD_PROJECT_ID=docucenter-aci
GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/service-account-credentials.json

# Google Drive - Shared Drive (Opción 1)
GOOGLE_SHARED_DRIVE_ID=108189760262793992527

# Google Drive - Domain Delegation (Opción 2 - comentado)
#GOOGLE_DRIVE_DELEGATE_EMAIL=admin@tudominio.com
```

**Nota**: El valor actual `108189760262793992527` en `GOOGLE_SHARED_DRIVE_ID` parece ser el Client ID de la Service Account, no un ID de Shared Drive. Los IDs de Shared Drive suelen tener formato similar a `0AByG_Vq7Zf8qUk9PVA`.

Para obtener el ID correcto del Shared Drive, verificar la URL cuando estés dentro del Shared Drive en el navegador.

---

## Referencias Útiles

### Documentación de Google
- [Google Drive API Documentation](https://developers.google.com/drive/api/v3/about-sdk)
- [Shared Drives Guide](https://developers.google.com/drive/api/guides/about-shareddrives)
- [Domain-Wide Delegation Guide](https://developers.google.com/identity/protocols/oauth2/service-account#delegatingauthority)
- [Service Accounts Overview](https://cloud.google.com/iam/docs/service-accounts)

### Documentación Interna
- [Guía de Configuración Completa](docs/integrations/google-drive-configuration-guide.md)
- [Resumen de Setup](docs/integrations/google-drive-setup-summary.md)
- [Guía del Scheduler](docs/integrations/google-drive-email-backup-scheduler.md)

---

**Última actualización**: 25 de Febrero, 2026  
**Proyecto**: docucenter-aci  
**Service Account**: firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com
