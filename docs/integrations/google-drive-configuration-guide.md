# Configuración de Google Drive para DocuCenter

## Problema: Service Accounts sin Cuota de Almacenamiento

Las Service Accounts de Google no tienen cuota de almacenamiento propia. Este es el error que obtuviste:

```
Service Accounts do not have storage quota. Leverage shared drives
or use OAuth delegation instead.
```

## Soluciones Disponibles

### Opción 1: Google Shared Drive (Team Drive) ⭐ Recomendado

**Requisitos**:
- Google Workspace Enterprise, Business Standard o Business Plus
- Permisos de administrador del Workspace

**Ventajas**:
- ✅ Almacenamiento compartido del Workspace (ilimitado en Enterprise)
- ✅ No depende de usuarios individuales
- ✅ Mejor control de acceso y auditoría
- ✅ Ideal para organizaciones

**Pasos de Configuración**:

#### 1. Crear Shared Drive

En Google Drive:
1. Ir a "Shared drives" (Unidades compartidas)
2. Click en "New shared drive"
3. Nombre: `DocuCenter-Facturas` (o el que prefieras)
4. Click en "Create"

#### 2. Agregar Service Account al Shared Drive

1. Abrir el Shared Drive creado
2. Click en el icono de configuración (⚙️)
3. Click en "Manage members"
4. Agregar el email de la Service Account:
   ```
   firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com
   ```
5. Rol: **Content Manager** o **Manager**
6. Click en "Send"

#### 3. Obtener ID del Shared Drive

**Método 1 - Desde la URL**:
1. Abrir el Shared Drive en el navegador
2. La URL será algo como:
   ```
   https://drive.google.com/drive/folders/0AByG_Vq7Zf8qUk9PVA
   ```
3. El ID es la parte después de `/folders/`: `0AByG_Vq7Zf8qUk9PVA`

**Método 2 - Con API**:
```bash
# Usando el comando de verificación
docker exec -it docucenter_laravel.test php artisan drive:list-shared-drives
```

#### 4. Configurar en .env

Agregar en `.env`:

```env
# Google Drive - Shared Drive Configuration
GOOGLE_SHARED_DRIVE_ID=0AByG_Vq7Zf8qUk9PVA
```

Con esto, el sistema automáticamente usará el Shared Drive en lugar del espacio personal.

---

### Opción 2: Domain-Wide Delegation (OAuth Delegation)

**Requisitos**:
- Google Workspace (cualquier plan)
- Permisos de Super Admin del Workspace
- Usuario con espacio de almacenamiento suficiente

**Ventajas**:
- ✅ Funciona con cualquier plan de Workspace
- ✅ Usa el almacenamiento de un usuario específico

**Desventajas**:
- ❌ Depende de la cuota de un usuario específico
- ❌ Si el usuario se elimina, se pierde acceso
- ❌ Configuración más compleja

**Pasos de Configuración**:

#### 1. Habilitar Domain-Wide Delegation

En Google Cloud Console:

1. Ir a [https://console.cloud.google.com](https://console.cloud.google.com)
2. Seleccionar proyecto: `docucenter-aci`
3. Ir a "IAM & Admin" → "Service Accounts"
4. Click en tu Service Account
5. Click en "Show domain-wide delegation"
6. Click en "Enable G Suite Domain-wide Delegation"
7. Copiar el "Client ID" (número largo)

#### 2. Autorizar en Google Workspace Admin

En Google Admin Console:

1. Ir a [https://admin.google.com](https://admin.google.com)
2. "Security" → "API Controls" → "Manage Domain-wide Delegation"
3. Click en "Add new"
4. Client ID: (pegar el copiado anteriormente)
5. OAuth Scopes:
   ```
   https://www.googleapis.com/auth/drive
   ```
6. Click en "Authorize"

#### 3. Configurar en .env

Agregar en `.env`:

```env
# Google Drive - Domain-Wide Delegation
GOOGLE_DRIVE_DELEGATE_EMAIL=admin@tudominio.com
```

Donde `admin@tudominio.com` es el email del usuario que actuará como "dueño" de los archivos.

---

## Verificación de Configuración

### 1. Verificar Variables de Entorno

```bash
docker exec -it docucenter_laravel.test php artisan config:cache
```

### 2. Ejecutar Verificación Completa

```bash
docker exec -it docucenter_laravel.test php artisan drive:verify-setup
```

### 3. Prueba de Subida

```bash
docker exec -it docucenter_laravel.test php artisan drive:verify-setup --test-upload --organization_id=2
```

Si todo está bien, deberías ver:

```
✓ Archivo subido exitosamente
  File ID: 1abc123...
  Link: https://drive.google.com/file/d/...
```

---

## Comparación de Opciones

| Característica | Shared Drive | Domain Delegation |
|----------------|--------------|-------------------|
| **Costo** | Requiere plan Business+ | Funciona con cualquier plan |
| **Almacenamiento** | Compartido del Workspace | Cuota del usuario |
| **Dependencia** | Independiente | Depende del usuario |
| **Configuración** | Simple | Compleja |
| **Mantenimiento** | Bajo | Medio |
| **Recomendado para** | Empresas medianas/grandes | Pequeñas empresas |

---

## Recomendación

Para **DocuCenter en producción**, recomendamos **Opción 1: Shared Drive** por:

1. **Escalabilidad**: No depende de cuotas individuales
2. **Organización**: Mejor estructura de carpetas compartidas
3. **Continuidad**: No afectado por cambios de personal
4. **Auditoría**: Mejor control de acceso y tracking
5. **Costo**: Inversión justificada para operación empresarial

---

## Estructura de Carpetas Resultante

Con cualquiera de las dos opciones, la estructura será:

```
📁 DocuCenter-Facturas/                   (Raíz - en Shared Drive o espacio del usuario)
    └── 📁 1825706-1-709732-90/          (RUC-DV de la organización)
        └── 📁 2026/                      (Año)
            └── 📁 02-Febrero/            (Mes)
                ├── 📄 factura1.xml
                ├── 📄 factura2.xml
                ├── 📄 factura3.xml
                └── ...
```

---

## Troubleshooting

### Error: Cannot find shared drive

**Causa**: ID de Shared Drive incorrecto o Service Account no tiene permisos

**Solución**:
1. Verificar que el ID es correcto (desde la URL)
2. Verificar que la Service Account está agregada como miembro
3. Esperar 5-10 minutos después de agregar la Service Account

### Error: Insufficient permissions

**Causa**: Service Account no tiene rol adecuado en Shared Drive

**Solución**:
1. En el Shared Drive, verificar permisos de la Service Account
2. Debe tener rol "Content Manager" o "Manager"
3. Rol "Commenter" o "Viewer" NO es suficiente

### Error: Invalid delegation

**Causa**: Domain-Wide Delegation no configurado correctamente

**Solución**:
1. Verificar que Client ID es correcto
2. Verificar que scopes están autorizados
3. Verificar que GOOGLE_DRIVE_DELEGATE_EMAIL es correcto
4. El usuario debe existir y tener permisos de Drive

---

## Comandos Útiles

```bash
# Verificar configuración completa
docker exec -it docucenter_laravel.test php artisan drive:verify-setup

# Prueba de subida
docker exec -it docucenter_laravel.test php artisan drive:verify-setup --test-upload --organization_id=2

# Limpiar cache de configuración
docker exec -it docucenter_laravel.test php artisan config:cache

# Ver logs de Google Drive
docker exec -it docucenter_laravel.test tail -f storage/logs/laravel.log | grep "Google\|Drive"
```

---

## Próximos Pasos

Una vez configurado correctamente:

1. ✅ Ejecutar verificación
2. ✅ Hacer prueba de subida
3. ✅ Habilitar backup en organizaciones de prueba
4. ✅ Ejecutar comando de backup manual
5. ✅ Verificar archivos en Google Drive
6. ✅ Programar ejecución automática (ya está configurado)

---

**Fecha**: 25 de Febrero, 2026  
**Sistema**: DocuCenter Email Backup  
**Tipo**: Guía de Configuración
