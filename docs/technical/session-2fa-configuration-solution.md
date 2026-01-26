# Configuración de Sesiones y 2FA - Solución Implementada

## Cambios Aplicados

### Problema Original
```env
# Producción (ANTES)
SESSION_LIFETIME=120      # Solo 2 horas
OTP_LIFETIME=120          # Solo 2 horas
```

**Síntoma**: El sistema pedía el código 2FA después de 2 horas, incluso con "Recordar sesión" marcado.

### Solución Implementada
```env
# Producción (AHORA - RECOMENDADO)
SESSION_DRIVER=database
SESSION_CONNECTION=mysql
SESSION_LIFETIME=43200           # 30 días (43200 minutos)
SESSION_DOMAIN="${APP_DOMAIN}"

OTP_LIFETIME=0                   # Eterno (no expira mientras sesión esté activa)
OTP_KEEP_ALIVE=true              # Se renueva en cada request
OTP_ENABLED=true
OTP_THROW_EXCEPTION=true
```

## Explicación Técnica

### ¿Por qué funcionaba mal?

1. **SESSION_LIFETIME=120** (2 horas)
   - La sesión de Laravel expiraba a las 2 horas
   - Al expirar la sesión, se perdía el estado de autenticación 2FA

2. **OTP_LIFETIME=120** (2 horas)
   - El token 2FA también expiraba a las 2 horas
   - Doble expiración innecesaria

### ¿Cómo funciona ahora?

1. **SESSION_LIFETIME=43200** (30 días)
   - La sesión dura 30 días
   - El usuario puede trabajar durante 30 días sin reiniciar sesión

2. **OTP_LIFETIME=0** (eterno)
   - El 2FA no expira mientras la sesión esté activa
   - Se valida una sola vez y permanece válido durante toda la sesión

3. **OTP_KEEP_ALIVE=true**
   - El timestamp de validación 2FA se actualiza en cada request
   - Mantiene el 2FA activo mientras el usuario esté usando el sistema

## Comportamiento Actual

### Flujo con "Recordar Sesión"
```
Usuario login → Valida 2FA → ✓
Trabaja 1 día → ✓ (sesión activa)
Trabaja 5 días → ✓ (sesión activa)
Trabaja 30 días → ✓ (sesión activa)
Día 31 → Pide login nuevamente
```

### Flujo sin "Recordar Sesión"
```
Usuario login → Valida 2FA → ✓
Cierra navegador → Sesión se mantiene
Abre navegador → ✓ (sesión activa hasta 30 días)
```

**Nota**: Laravel usa cookies de sesión. El comportamiento de "recordar" está controlado por:
- `SESSION_LIFETIME`: Tiempo máximo de sesión
- Cookie `laravel_session`: Almacena el ID de sesión
- Cookie `remember_web_*`: Para "Recordar sesión" (5 años)

## Migración en Producción

### Paso 1: Actualizar .env
```bash
# En el servidor de producción
nano .env
```

Cambiar:
```env
SESSION_DRIVER=database
SESSION_CONNECTION=mysql
SESSION_LIFETIME=43200
SESSION_DOMAIN="${APP_DOMAIN}"

OTP_LIFETIME=0
OTP_KEEP_ALIVE=true
OTP_ENABLED=true
OTP_THROW_EXCEPTION=true
```

### Paso 2: Crear tabla de sesiones (si no existe)
```bash
php artisan session:table
php artisan migrate
```

### Paso 3: Limpiar cache
```bash
php artisan config:cache
php artisan cache:clear
```

### Paso 4: Verificar
```bash
php artisan tinker

>>> config('session.lifetime')
=> 43200

>>> config('google2fa.lifetime')
=> 0

>>> config('google2fa.keep_alive')
=> true
```

## Ventajas de Esta Configuración

### Ventajas
1. **Sesión larga**: 30 días sin pedir login
2. **2FA único**: Solo se pide una vez por sesión
3. **Mejor UX**: Usuario no interrumpido constantemente
4. **Más seguro**: Usa base de datos para sesiones (más persistente que archivos)
5. **Keep-alive**: Se renueva automáticamente con actividad

### Consideraciones de Seguridad

1. **Sesión de 30 días**:
   - Pros: Mejor experiencia de usuario
   - Contras: Sesión activa por más tiempo
   - Mitigación: El 2FA ya validó la identidad

2. **2FA eterno**:
   - Solo es eterno DENTRO de la sesión
   - Si la sesión expira (30 días), se pide 2FA nuevamente
   - Si usuario cierra sesión manualmente, se pide 2FA nuevamente

3. **Recomendación adicional**:
   - Implementar logout automático por inactividad (opcional)
   - Monitorear sesiones activas
   - Logs de acceso con 2FA

## Opciones Alternativas

### Opción 1: Sesión Más Corta (7 días)
```env
SESSION_LIFETIME=10080  # 7 días en minutos
OTP_LIFETIME=0          # Eterno dentro de la sesión
```

### Opción 2: Sesión Moderada (14 días)
```env
SESSION_LIFETIME=20160  # 14 días en minutos
OTP_LIFETIME=0          # Eterno dentro de la sesión
```

### Opción 3: Mantener 2 horas pero mejorar UX
```env
SESSION_LIFETIME=120    # 2 horas
OTP_LIFETIME=1440       # 24 horas (1 día)
OTP_KEEP_ALIVE=true     # Renovar en cada request
```

Con esta última opción:
- Sesión expira en 2 horas de inactividad
- 2FA válido por 24 horas (se renueva con actividad)
- Usuario solo valida 2FA una vez al día

## Monitoreo Recomendado

### Ver sesiones activas:
```sql
SELECT 
    COUNT(*) as total_sesiones,
    FROM_UNIXTIME(MAX(last_activity)) as ultima_actividad
FROM sessions;
```

### Limpiar sesiones viejas:
```bash
php artisan session:gc
```

O configurar en cron:
```cron
0 0 * * * cd /path/to/project && php artisan session:gc
```

## Resultado Final

**Configuración actual (desarrollo y producción):**
```
SESSION_DRIVER=database
SESSION_LIFETIME=43200 minutos (30 días)
OTP_LIFETIME=0 (eterno dentro de sesión)
OTP_KEEP_ALIVE=true (se renueva)
```

**Comportamiento:**
- Usuario valida 2FA una sola vez
- Sesión dura 30 días
- No se pide 2FA constantemente
- Mejor experiencia de usuario
- Mantiene seguridad con 2FA inicial

---

**Fecha de Implementación**: 2025-11-25
**Ambiente**: Desarrollo y Producción
**Estado**: Implementado y Verificado
