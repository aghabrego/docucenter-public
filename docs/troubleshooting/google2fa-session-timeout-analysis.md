# Análisis del Problema: Google2FA Session Timeout

## Problema Identificado

El sistema está pidiendo el código 2FA después de un tiempo o redirigiendo al login, incluso cuando el usuario marcó "Recordar sesión".

## Configuración Actual

### 1. Google2FA Config (`config/google2fa.php`)
```php
'lifetime' => env('OTP_LIFETIME', 0),     // 0 = eternal (CORRECTO)
'keep_alive' => env('OTP_KEEP_ALIVE', true),  // Renovar en cada request (CORRECTO)
```

### 2. Session Config (`config/session.php`)
```php
'lifetime' => env('SESSION_LIFETIME', 120),  // 120 minutos = 2 horas
'expire_on_close' => false,
```

### 3. Variables de Entorno (.env)
```env
SESSION_LIFETIME=120  # ← PROBLEMA: Solo 2 horas
# OTP_LIFETIME no está definido (usa default 0 = eternal)
# OTP_KEEP_ALIVE no está definido (usa default true)
```

## Causa Raíz del Problema

**El problema NO es con Google2FA, sino con la SESIÓN de Laravel:**

1. Google2FA está configurado para durar eternamente (`lifetime = 0`)
2. Google2FA renueva en cada request (`keep_alive = true`)
3. **La sesión de Laravel expira a las 2 horas** (`SESSION_LIFETIME=120`)

### Flujo del Problema:
```
Usuario inicia sesión → 
Usuario valida 2FA →  (se guarda en sesión como 'google2fa')
Usuario usa el sistema por 2+ horas → Sesión de Laravel expira
Middleware 2FA verifica sesión → Sesión expiró, no encuentra 'google2fa'
Sistema pide 2FA nuevamente o redirige a login
```

##  Documentación de Google2FA Laravel

Según la documentación oficial de `pragmarx/google2fa-laravel`:

### Lifetime Configuration
- `lifetime`: Tiempo en **minutos** que el 2FA permanece válido
- `0` = eternal (no expira)
- Si `keep_alive = true`, el lifetime se renueva en cada request

### Importante:
**El lifetime de 2FA depende de la sesión de Laravel**. Si la sesión expira, el estado de 2FA también se pierde, sin importar el `lifetime` configurado.

## Solución

### Opción 1: Remember Me Dinámico (RECOMENDADO)

Modificar el sistema para que:
- **Con "Recordar sesión"**: SESSION_LIFETIME = 43200 (30 días en minutos)
- **Sin "Recordar sesión"**: SESSION_LIFETIME = 120 (2 horas)

**Implementación:**

1. **Modificar el login para configurar lifetime dinámicamente:**

```php
// En el controlador de login después de autenticar
if ($request->has('remember')) {
    // Recordar sesión: 30 días
    config(['session.lifetime' => 43200]);
    session()->put('_token', session()->token()); // Regenerar token
} else {
    // Sesión normal: 2 horas
    config(['session.lifetime' => 120]);
}
```

2. **O mejor: Usar el sistema de "remember" de Laravel:**

Laravel ya tiene un sistema integrado:
```php
Auth::attempt($credentials, $remember = true);
```

Esto crea una cookie "remember_me" que dura **5 años** por defecto.

### Opción 2: Aumentar SESSION_LIFETIME Global

**Para todos los usuarios:**

`.env`:
```env
SESSION_LIFETIME=43200  # 30 días en minutos
```

**Pros:**
- Simple
- Todos los usuarios tienen sesión larga

**Contras:**
- No respeta la opción "Recordar sesión"
- Sesiones activas por mucho tiempo (puede ser problema de seguridad)

### Opción 3: Combinar Remember Cookie + Session Storage

**Configuración recomendada:**

`.env`:
```env
SESSION_LIFETIME=525600           # 1 año en minutos
SESSION_DRIVER=database           # Usar base de datos
SESSION_EXPIRE_ON_CLOSE=false     # No expirar al cerrar navegador
OTP_LIFETIME=0                    # 2FA eterno
OTP_KEEP_ALIVE=true               # Renovar 2FA en cada request
```

`config/session.php`:
```php
'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
```

## Solución Recomendada

### Implementación Completa

**1. Actualizar `.env`:**
```env
SESSION_LIFETIME=43200    # 30 días
SESSION_DRIVER=database   # Más persistente que file
OTP_LIFETIME=0            # Eterno
OTP_KEEP_ALIVE=true       # Renovar en cada request
```

**2. Verificar que Google2FA usa la sesión correctamente:**

El middleware `Google2FAAuthenticator` ya verifica:
```php
protected function twoFactorAuthStillValid()
{
    return
        !is_null($this->getSessionKey()) &&
        $this->sessionLifetimeIsNotOver();
}
```

Esto depende del `lifetime` de sesión de Laravel.

**3. Si quieres lógica dinámica según "remember":**

Crear un middleware personalizado:

```php
// app/Http/Middleware/ConfigureSessionLifetime.php
class ConfigureSessionLifetime
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::viaRemember()) {
            // Usuario tiene cookie "remember_me"
            config(['session.lifetime' => 43200]); // 30 días
        } else {
            // Usuario normal
            config(['session.lifetime' => 120]); // 2 horas
        }
        
        return $next($request);
    }
}
```

Registrar en `app/Http/Kernel.php`:
```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\ConfigureSessionLifetime::class,
    ],
];
```

## Verificación

### Comprobar configuración actual:
```bash
php artisan tinker

>>> config('google2fa.lifetime')
=> 0

>>> config('session.lifetime')
=> 120

>>> config('google2fa.keep_alive')
=> true
```

### Verificar sesión activa:
```bash
php artisan tinker

>>> session()->get('google2fa')
=> "timestamp_aquí" (si está autenticado)

>>> session()->get('login_web_*')  # Sesión de auth
```

## Conclusión

El problema NO es con Google2FA, sino con **SESSION_LIFETIME de Laravel**.

**Soluciones en orden de preferencia:**

1. **MEJOR**: Aumentar `SESSION_LIFETIME=43200` (30 días) en `.env`
2. **ALTERNATIVA**: Crear middleware para lifetime dinámico según "remember"
3. **ADICIONAL**: Cambiar a `SESSION_DRIVER=database` para mayor persistencia

**Cambios mínimos requeridos:**

```env
# .env
SESSION_LIFETIME=43200    # De 120 a 43200 (30 días)
SESSION_DRIVER=database   # De file a database (opcional pero recomendado)
```

Después de hacer el cambio:
```bash
php artisan config:cache
php artisan session:table  # Si usas database driver
php artisan migrate
```

---

**Fecha:** 2025-11-25
**Componente:** Google2FA Laravel
**Estado:** Diagnosticado - Requiere ajuste de SESSION_LIFETIME
