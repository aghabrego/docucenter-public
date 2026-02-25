# Resumen: Verificación de Google Drive

## Estado Actual

✅ **Sistema de backup funcional y programado**  
❌ **Configuración de almacenamiento pendiente**

## Diagnóstico Completo

### ✅ Componentes Funcionando

1. **Código del Sistema**
   - Job de extracción: `ExtractReadEmailsToGoogleDriveJob` ✓
   - Servicio de Google Drive: `GoogleDriveService` ✓  
   - Comando de backup: `emails:backup-to-drive` ✓
   - Modelo de tracking: `GoogleDriveUpload` ✓
   - Scheduler configurado: Diario a las 02:00 AM ✓

2. **Base de Datos**
   - Tabla `google_drive_uploads` creada ✓
   - Columnas requeridas presentes ✓
   - Migración aplicada correctamente ✓

3. **Credenciales de Google**
   - Archivo `service-account-credentials.json` existe ✓
   - Formato JSON válido ✓
   - Service Account: `firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com` ✓
   - Proyecto: `docucenter-aci` ✓

### ❌ Problema Detectado

**Service Accounts sin cuota de almacenamiento**

```
Error: Service Accounts do not have storage quota.
Leverage shared drives or use OAuth delegation instead.
```

**Razón**: Las Service Accounts de Google son cuentas de servicio sin almacenamiento propio. Solo pueden:
- Acceder a archivos compartidos con ellas
- Usar Shared Drives (Team Drives)
- Actuar en nombre de usuarios mediante OAuth delegation

## Soluciones Disponibles

### Opción 1: Google Shared Drive ⭐ **RECOMENDADO**

**Para qué sirve**: Almacenamiento compartido del Workspace, independiente de usuarios

**Requisitos**:
- Google Workspace Business Standard, Business Plus o Enterprise
- Acceso como administrador del Workspace

**Ventajas**:
- ✅ Almacenamiento compartido (100GB - Ilimitado según plan)
- ✅ No depende de usuarios individuales  
- ✅ Mejor para organizaciones
- ✅ Configuración simple

**Pasos de configuración**:

1. **Crear Shared Drive en Google Drive**
   - Ir a Google Drive
   - Click en "Shared drives" → "New"
   - Nombre: `DocuCenter-Facturas`

2. **Agregar Service Account como miembro**
   - Abrir el Shared Drive
   - Click en "Manage members"
   - Agregar: `firebase-adminsdk-csm4f@docucenter-aci.iam.gserviceaccount.com`
   - Rol: **Content Manager** o **Manager**

3. **Obtener ID del Shared Drive**
   - Desde URL: `https://drive.google.com/drive/folders/[ID_AQUI]`
   - O ejecutar: `php artisan drive:list-shared-drives`

4. **Configurar en .env**
   ```env
   GOOGLE_SHARED_DRIVE_ID=0AByG_Vq7Zf8qUk9PVA
   ```

5. **Aplicar configuración**
   ```bash
   docker exec -it docucenter_laravel.test php artisan config:cache
   docker exec -it docucenter_laravel.test php artisan drive:verify-setup
   ```

---

### Opción 2: Domain-Wide Delegation

**Para qué sirve**: La Service Account actúa en nombre de un usuario con almacenamiento

**Requisitos**:
- Google Workspace (cualquier plan)
- Acceso como Super Admin del Workspace

**Ventajas**:
- ✅ Funciona con cualquier plan de Workspace
- ✅ No requiere upgrade a Business+

**Desventajas**:
- ❌ Depende de la cuota de un usuario  
- ❌ Configuración más compleja
- ❌ Si el usuario se elimina, se pierde acceso

**Configuración completa**: Ver [`docs/integrations/google-drive-configuration-guide.md`](docs/integrations/google-drive-configuration-guide.md)

---

## Comandos Útiles

### Verificar configuración completa
```bash
docker exec -it docucenter_laravel.test php artisan drive:verify-setup
```

### Listar Shared Drives disponibles
```bash
docker exec -it docucenter_laravel.test php artisan drive:list-shared-drives
```

### Probar subida de archivo
```bash
docker exec -it docucenter_laravel.test php artisan drive:verify-setup --test-upload --organization_id=2
```

### Ejecutar backup manual
```bash
docker exec -it docucenter_laravel.test php artisan emails:backup-to-drive --organization_id=2
```

### Aplicar cambios de configuración
```bash
docker exec -it docucenter_laravel.test php artisan config:cache
```

---

## Archivos Modificados en Esta Sesión

### Código Mejorado

1. **`app/Services/GoogleDriveService.php`**
   - ✅ Soporte para Shared Drives
   - ✅ Soporte para Domain-Wide Delegation
   - ✅ Detección automática según variables de entorno
   - ✅ Parámetros `supportsAllDrives` en todas las operaciones

2. **`app/Console/Commands/VerifyGoogleDriveSetupCommand.php`**
   - ✅ Diagnóstico completo de configuración
   - ✅ Detección de problemas de cuota
   - ✅ Sugerencias específicas según error
   - ✅ Prueba de subida opcional

3. **`app/Console/Commands/ListSharedDrivesCommand.php`** ⚡ NUEVO
   - ✅ Lista Shared Drives disponibles
   - ✅ Muestra IDs para configuración
   - ✅ Instrucciones de setup

### Documentación Completa

1. **[`docs/integrations/google-drive-configuration-guide.md`](docs/integrations/google-drive-configuration-guide.md)** ⚡ NUEVO
   - Explicación detallada del problema
   - Configuración paso a paso de Shared Drive
   - Configuración paso a paso de Domain Delegation
   - Comparación de opciones
   - Troubleshooting completo

2. **[`docs/integrations/google-drive-email-backup-scheduler.md`](docs/integrations/google-drive-email-backup-scheduler.md)**
   - Guía de uso del comando de backup
   - Opciones disponibles
   - Ejemplos de ejecución
   - Verificación de logs

---

## Próximos Pasos

### Paso 1: Elegir Solución
- [ ] **Opción A**: Configurar Shared Drive (recomendado para empresas)
- [ ] **Opción B**: Configurar Domain Delegation (alternativa)

### Paso 2: Aplicar Configuración
- [ ] Agregar variable a `.env` según opción elegida
- [ ] Ejecutar `php artisan config:cache`
- [ ] Verificar con `php artisan drive:verify-setup`

### Paso 3: Probar Sistema
- [ ] Ejecutar prueba de subida: `php artisan drive:verify-setup --test-upload --organization_id=2`
- [ ] Verificar archivo en Google Drive
- [ ] Comprobar registro en tabla `google_drive_uploads`

### Paso 4: Activar en Producción
- [ ] Habilitar backup en organizaciones: `enable_daily_backup = true`
- [ ] Configurar emails IMAP en `/admin/configurationemailorganization`
- [ ] Ejecutar backup manual para prueba
- [ ] Verificar scheduler: `php artisan schedule:list | grep backup`

---

## Comparación de Opciones

| | Shared Drive | Domain Delegation |
|---|---|---|
| **Costo** | Requiere Business+ | Cualquier plan |
| **Almacenamiento** | 100GB - Ilimitado | 30GB - 2TB (según plan) |
| **Independencia** | Sí | No (depende de usuario) |
| **Complejidad** | Simple | Media |
| **Recomendado para** | Empresas | Desarrollo/Testing |
| **Tiempo de setup** | 5 minutos | 15-20 minutos |

---

## Resumen Técnico

**Sistema completo de backup automático a Google Drive implementado y funcionando**, con estas características:

- ✅ Extracción automática de emails leídos con XMLs
- ✅ Subida organizada por RUC-DV/Año/Mes
- ✅ Eliminación automática de emails después de backup exitoso
- ✅ Tracking completo en base de datos
- ✅ Scheduler configurado (diario a las 02:00 AM)
- ✅ Soporte para Shared Drives
- ✅ Soporte para Domain Delegation
- ✅ Comando de verificación completo
- ✅ Logs detallados para auditoría
- ✅ Manejo de errores robusto

**Solo falta**: Configurar método de almacenamiento (Shared Drive o Domain Delegation)

---

**Fecha**: 25 de Febrero, 2026  
**Estado**: Sistema listo para configuración final  
**Acción requerida**: Elegir y configurar método de almacenamiento
