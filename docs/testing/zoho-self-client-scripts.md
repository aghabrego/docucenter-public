# 📋 Scripts de Testing para Zoho Self Client

## 🚀 Script de Testing Completo

### Script Principal: `test-zoho-self-client.sh`

```bash
#!/bin/bash

# Configuración
APP_URL="http://localhost"
CONTAINER_NAME="laravel.test"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para logs
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Función para ejecutar comandos en Docker
docker_exec() {
    docker-compose exec "$CONTAINER_NAME" "$@"
}

# Función para testing de conexión
test_connection_creation() {
    log_info "Testing Zoho Self Client Connection Creation..."
    
    # Verificar que los archivos existen
    if docker_exec test -f "app/Http/Livewire/Admin/Connection/Create.php"; then
        log_success "Create.php exists"
    else
        log_error "Create.php not found"
        return 1
    fi
    
    # Verificar sintaxis
    if docker_exec php -l "app/Http/Livewire/Admin/Connection/Create.php" > /dev/null 2>&1; then
        log_success "Create.php syntax OK"
    else
        log_error "Create.php syntax error"
        return 1
    fi
    
    # Verificar método authorizeWithDirectCode
    if docker_exec grep -q "authorizeWithDirectCode" "app/Http/Livewire/Admin/Connection/Create.php"; then
        log_success "authorizeWithDirectCode method found"
    else
        log_error "authorizeWithDirectCode method not found"
        return 1
    fi
    
    return 0
}

# Función para testing de actualización
test_connection_update() {
    log_info "Testing Zoho Self Client Connection Update..."
    
    # Verificar Update.php
    if docker_exec test -f "app/Http/Livewire/Admin/Connection/Update.php"; then
        log_success "Update.php exists"
    else
        log_error "Update.php not found"
        return 1
    fi
    
    # Verificar sintaxis
    if docker_exec php -l "app/Http/Livewire/Admin/Connection/Update.php" > /dev/null 2>&1; then
        log_success "Update.php syntax OK"
    else
        log_error "Update.php syntax error"
        return 1
    fi
    
    # Verificar método reauthorizeWithDirectCode
    if docker_exec grep -q "reauthorizeWithDirectCode" "app/Http/Livewire/Admin/Connection/Update.php"; then
        log_success "reauthorizeWithDirectCode method found"
    else
        log_error "reauthorizeWithDirectCode method not found"
        return 1
    fi
    
    return 0
}

# Función para testing de vistas
test_blade_views() {
    log_info "Testing Blade Views..."
    
    # Create view
    if docker_exec test -f "resources/views/livewire/admin/connection/create.blade.php"; then
        log_success "create.blade.php exists"
        
        if docker_exec grep -q "zoho_direct_code" "resources/views/livewire/admin/connection/create.blade.php"; then
            log_success "Direct code field found in create view"
        else
            log_warning "Direct code field not found in create view"
        fi
    else
        log_error "create.blade.php not found"
    fi
    
    # Update view
    if docker_exec test -f "resources/views/livewire/admin/connection/update.blade.php"; then
        log_success "update.blade.php exists"
        
        if docker_exec grep -q "reauthorizeWithDirectCode" "resources/views/livewire/admin/connection/update.blade.php"; then
            log_success "Re-authorization method found in update view"
        else
            log_warning "Re-authorization method not found in update view"
        fi
    else
        log_error "update.blade.php not found"
    fi
}

# Función para testing de servicios
test_services() {
    log_info "Testing Zoho Services..."
    
    # ZohoOAuthController
    if docker_exec test -f "app/Http/Controllers/ZohoOAuthController.php"; then
        log_success "ZohoOAuthController exists"
        
        if docker_exec php -l "app/Http/Controllers/ZohoOAuthController.php" > /dev/null 2>&1; then
            log_success "ZohoOAuthController syntax OK"
        else
            log_error "ZohoOAuthController syntax error"
        fi
    else
        log_warning "ZohoOAuthController not found (optional)"
    fi
    
    # ZohoSelfClientService
    if docker_exec test -f "app/Services/ZohoSelfClientService.php"; then
        log_success "ZohoSelfClientService exists"
        
        if docker_exec php -l "app/Services/ZohoSelfClientService.php" > /dev/null 2>&1; then
            log_success "ZohoSelfClientService syntax OK"
        else
            log_error "ZohoSelfClientService syntax error"
        fi
    else
        log_warning "ZohoSelfClientService not found (optional)"
    fi
}

# Función para testing interactivo
interactive_test() {
    log_info "=== Interactive Zoho Self Client Testing ==="
    
    echo ""
    echo "Este test te ayudará a probar la funcionalidad paso a paso."
    echo ""
    
    # Solicitar datos
    read -p "¿Tienes un Client ID de Zoho Self Client? (y/n): " has_client_id
    if [[ $has_client_id == "y" ]]; then
        read -p "Client ID: " client_id
        read -p "Client Secret: " client_secret
        read -p "Zoho Environment (com/eu/in): " zoho_env
        
        log_info "Configuración ingresada:"
        echo "  Client ID: $client_id"
        echo "  Client Secret: ****"
        echo "  Environment: $zoho_env"
    else
        log_warning "Para testing completo necesitas credenciales de Zoho Self Client"
        log_info "Puedes obtenerlas en: https://api-console.zoho.com/"
    fi
    
    echo ""
    read -p "¿Quieres generar un código de autorización ahora? (y/n): " generate_code
    if [[ $generate_code == "y" ]]; then
        log_info "Pasos para generar código:"
        echo "1. Ve a: https://api-console.zoho.$zoho_env/"
        echo "2. Selecciona tu aplicación Self Client"
        echo "3. Ve a la sección 'Self Client'"
        echo "4. Haz clic en 'Generate Code'"
        echo "5. Scope: ZohoBooks.fullaccess.all"
        echo "6. Copia el código (válido 3 minutos)"
        echo ""
        
        read -p "¿Ya generaste el código? Pégalo aquí: " auth_code
        if [[ ! -z "$auth_code" ]]; then
            log_success "Código recibido: ${auth_code:0:20}..."
            log_info "Ahora puedes probarlo en: $APP_URL/admin/connections/create"
        fi
    fi
    
    echo ""
    log_info "URLs de testing:"
    echo "  - Crear conexión: $APP_URL/admin/connections/create"
    echo "  - Listar conexiones: $APP_URL/admin/connections"
    echo "  - Logs: docker-compose logs laravel.test"
}

# Función para mostrar ayuda
show_help() {
    echo "Uso: $0 [OPTION]"
    echo ""
    echo "Opciones:"
    echo "  connection     Test de creación de conexiones"
    echo "  update         Test de actualización de conexiones"
    echo "  views          Test de vistas Blade"
    echo "  services       Test de servicios Zoho"
    echo "  interactive    Test interactivo paso a paso"
    echo "  all            Ejecutar todos los tests"
    echo "  help           Mostrar esta ayuda"
    echo ""
    echo "Ejemplos:"
    echo "  $0 all"
    echo "  $0 interactive"
    echo "  $0 connection"
}

# Función principal
main() {
    case "$1" in
        "connection")
            test_connection_creation
            ;;
        "update")
            test_connection_update
            ;;
        "views")
            test_blade_views
            ;;
        "services")
            test_services
            ;;
        "interactive")
            interactive_test
            ;;
        "all")
            log_info "=== Running All Zoho Self Client Tests ==="
            test_connection_creation && \
            test_connection_update && \
            test_blade_views && \
            test_services && \
            log_success "All tests completed!" || \
            log_error "Some tests failed"
            ;;
        "help"|"--help"|"-h"|"")
            show_help
            ;;
        *)
            log_error "Opción desconocida: $1"
            show_help
            exit 1
            ;;
    esac
}

# Verificar que Docker Compose está corriendo
if ! docker-compose ps | grep -q "Up"; then
    log_error "Docker Compose no está corriendo. Ejecuta: docker-compose up -d"
    exit 1
fi

# Ejecutar función principal
main "$@"
```

### 📝 Script de Documentación: `generate-zoho-docs.sh`

```bash
#!/bin/bash

# Script para generar documentación de Zoho Self Client

echo "🚀 Generando documentación de Zoho Self Client..."

# Crear directorio si no existe
mkdir -p docs/testing

# Generar README de testing
cat > docs/testing/zoho-self-client-testing.md << 'EOF'
# 🧪 Testing: Zoho Self Client Implementation

## 📋 Checklist de Testing

### ✅ Funcionalidades Implementadas

#### **Autorización Directa con Código**
- [ ] Campo `zoho_direct_code` en Create.php
- [ ] Método `authorizeWithDirectCode()` funcional
- [ ] Método `exchangeDirectCodeForToken()` implementado
- [ ] Validación de código en frontend
- [ ] Intercambio exitoso de código por tokens

#### **Re-autorización en Update**
- [ ] Método `reauthorizeWithDirectCode()` funcional
- [ ] Preservación de configuración existente
- [ ] Actualización de tokens
- [ ] UI para re-autorización

#### **Interfaces de Usuario**
- [ ] Campo de código en create.blade.php
- [ ] Botón "Autorizar con Código Directo"
- [ ] Sección de re-autorización en update.blade.php
- [ ] Feedback visual de estado

### 🔧 Testing Manual

#### **Caso 1: Crear Nueva Conexión**
```
1. Ir a /admin/connections/create
2. Seleccionar tipo "Zoho Self Client"
3. Completar datos básicos:
   - Client ID: 1000.XXXXXXXXX
   - Client Secret: xxxxxxxxx
   - Zoho Environment: com
4. Generar código en Zoho Console
5. Pegar código en campo "Código Directo"
6. Clic en "Autorizar con Código Directo"

✅ Resultado esperado: Conexión creada con tokens válidos
```

#### **Caso 2: Re-autorizar Conexión Existente**
```
1. Ir a /admin/connections
2. Editar conexión Zoho existente
3. Generar nuevo código en Zoho Console
4. Pegar en campo de re-autorización
5. Clic en "Re-autorizar con Código Directo"

✅ Resultado esperado: Tokens renovados, configuración preservada
```

#### **Caso 3: OAuth Tradicional (Fallback)**
```
1. Configurar Redirect URI en Zoho Console
2. Completar configuración OAuth completa
3. Usar flujo tradicional con popup

✅ Resultado esperado: Ambos métodos funcionan
```

### 🔍 Testing Técnico

#### **Verificación de Archivos**
```bash
# Ejecutar script de testing
./scripts/test-zoho-self-client.sh all

# Testing interactivo
./scripts/test-zoho-self-client.sh interactive

# Testing específico
./scripts/test-zoho-self-client.sh connection
```

#### **Validación de Código**
```bash
# Verificar sintaxis
docker-compose exec laravel.test php -l app/Http/Livewire/Admin/Connection/Create.php
docker-compose exec laravel.test php -l app/Http/Livewire/Admin/Connection/Update.php

# Verificar métodos
grep -n "authorizeWithDirectCode" app/Http/Livewire/Admin/Connection/Create.php
grep -n "reauthorizeWithDirectCode" app/Http/Livewire/Admin/Connection/Update.php
```

### 📊 Casos de Error

#### **Error 1: Código Expirado**
```
Síntoma: Error 400 "invalid_grant"
Solución: Generar nuevo código (3 min validez)
```

#### **Error 2: Credenciales Incorrectas**
```
Síntoma: Error 401 "invalid_client"
Solución: Verificar Client ID y Secret
```

#### **Error 3: Scope Incorrecto**
```
Síntoma: Error 403 "access_denied"
Solución: Usar scope "ZohoBooks.fullaccess.all"
```

### 🎯 Métricas de Éxito

#### **Performance**
- ⏱️ Autorización directa: < 5 segundos
- ⏱️ Re-autorización: < 3 segundos
- 📦 Tamaño de respuesta: < 2KB

#### **Usabilidad**
- 👆 Clicks para autorizar: 1
- 📝 Campos requeridos: 4 (ID, Secret, Env, Code)
- 🔄 Tiempo de configuración: < 2 minutos

#### **Confiabilidad**
- ✅ Tasa de éxito: > 95%
- 🔁 Reintentos automáticos: 3
- 📝 Logging completo: Sí

### 🚀 Próximos Pasos

1. **Testing Automatizado**
   - Crear tests unitarios para métodos
   - Tests de integración con API Zoho
   - Tests de UI con Laravel Dusk

2. **Monitoreo**
   - Logs de autorización
   - Métricas de uso
   - Alertas de errores

3. **Documentación**
   - Video tutorial
   - FAQ de troubleshooting
   - Guía de migración

EOF

echo "✅ Documentación generada en docs/testing/zoho-self-client-testing.md"

# Generar script de uso rápido
cat > docs/testing/quick-start.md << 'EOF'
# ⚡ Quick Start: Zoho Self Client

## 🚀 Configuración en 2 Minutos

### 1. Obtener Credenciales
```
1. Ve a: https://api-console.zoho.com/
2. Crear aplicación "Self Client"
3. Anotar Client ID y Client Secret
```

### 2. Autorización Rápida
```
1. Ir a: /admin/connections/create
2. Tipo: "Zoho Self Client"
3. Completar: Client ID, Secret, Environment
4. Generar código en Zoho Console
5. Autorizar con código directo
```

### 3. Verificación
```
1. Ir a: /admin/connections
2. Verificar estado: "Conectado"
3. Test API: Sincronizar datos
```

## 🎯 Casos de Uso Rápidos

### Desarrollo Local
```bash
# Testing completo
./scripts/test-zoho-self-client.sh all

# Testing interactivo
./scripts/test-zoho-self-client.sh interactive
```

### Producción
```
1. Usar código directo para setup inicial
2. Configurar OAuth como respaldo
3. Monitorear tokens y renovar
```

EOF

echo "✅ Quick start generado en docs/testing/quick-start.md"
echo "📋 Scripts listos para usar!"
```

### 🎮 Comandos de Testing

```bash
# Hacer ejecutable
chmod +x scripts/test-zoho-self-client.sh

# Testing completo
./scripts/test-zoho-self-client.sh all

# Testing interactivo
./scripts/test-zoho-self-client.sh interactive

# Testing específico
./scripts/test-zoho-self-client.sh connection
./scripts/test-zoho-self-client.sh update
./scripts/test-zoho-self-client.sh views
./scripts/test-zoho-self-client.sh services
```

### 📊 Ejemplo de Output

```
[INFO] === Running All Zoho Self Client Tests ===
[SUCCESS] Create.php exists
[SUCCESS] Create.php syntax OK
[SUCCESS] authorizeWithDirectCode method found
[SUCCESS] Update.php exists
[SUCCESS] Update.php syntax OK
[SUCCESS] reauthorizeWithDirectCode method found
[SUCCESS] create.blade.php exists
[SUCCESS] Direct code field found in create view
[SUCCESS] update.blade.php exists
[SUCCESS] Re-authorization method found in update view
[SUCCESS] All tests completed!
```

¡Scripts completos para testing y documentación! 🎉
