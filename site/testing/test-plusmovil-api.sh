#!/bin/bash

###############################################################################
# Script de Prueba: PlusMovil API con AWS Cognito
#
# Este script prueba la autenticación y consulta del endpoint /sys-logs
# QA API: https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa
# PROD API: https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod
#
# IMPORTANTE: Este script NO requiere Docker ni Sail
# Se ejecuta directamente en tu sistema local usando curl
#
# Requisitos:
#   - curl (ya instalado en Linux/macOS)
#   - jq (opcional para formatear JSON): sudo apt install jq
#
# Uso:
#   chmod +x docs/testing/test-plusmovil-api.sh
#   ./docs/testing/test-plusmovil-api.sh
###############################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir headers
print_header() {
    echo -e "\n${BLUE}==========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}==========================================${NC}\n"
}

# Función para imprimir success
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Función para imprimir error
print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Función para imprimir warning
print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Función para imprimir info
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

###############################################################################
# CONFIGURACIÓN
###############################################################################

# Ambiente (qa o prod)
ENVIRONMENT="qa"

# Configuraciones por ambiente
if [ "$ENVIRONMENT" = "qa" ]; then
    CLIENT_ID="7t3s7lb4tfg6ssal586l929ovl"
    BASE_URL="https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa"
else
    CLIENT_ID="771vv2q1ararj5u1f084opsdg7"
    BASE_URL="https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod"
fi

###############################################################################
# INICIO
###############################################################################

print_header "PRUEBA DE API PLUSMOVIL"

print_info "Ambiente: $ENVIRONMENT"
print_info "Client ID: $CLIENT_ID"
print_info "Base URL: $BASE_URL"

###############################################################################
# PASO 1: SOLICITAR TOKEN
###############################################################################

print_header "PASO 1: CONFIGURACIÓN DE TOKEN"

print_warning "Para autenticación con AWS Cognito necesitarás:"
echo "  1. User Pool ID de AWS Cognito"
echo "  2. Usuario y contraseña válidos"
echo "  3. AWS CLI instalado (opcional)"
echo ""

print_info "Puedes obtener el token de dos formas:"
echo "  A) Usar AWS CLI:"
echo "     aws cognito-idp initiate-auth \\"
echo "       --auth-flow USER_PASSWORD_AUTH \\"
echo "       --client-id $CLIENT_ID \\"
echo "       --auth-parameters USERNAME=<user>,PASSWORD=<pass>"
echo ""
echo "  B) Desde la aplicación web (inspeccionar token en localStorage/cookies)"
echo ""

print_warning "IMPORTANTE: El Access Token es DIFERENTE del Client ID"
echo "  ❌ Client ID: $CLIENT_ID (NO usar como token)"
echo "  ✅ Access Token: Un string JWT largo (ej: eyJraWQiOiJ...)"
echo ""

read -p "¿Ya tienes un token de acceso? (s/n): " HAS_TOKEN

if [ "$HAS_TOKEN" != "s" ]; then
    print_warning "Necesitas obtener un token primero."
    echo ""
    print_info "OPCIÓN 1: Usar AWS CLI (si tienes acceso)"
    echo "  Instalar AWS CLI: https://aws.amazon.com/cli/"
    echo ""
    echo "  Comando para obtener token:"
    echo "  aws cognito-idp initiate-auth \\"
    echo "    --region us-east-1 \\"
    echo "    --auth-flow USER_PASSWORD_AUTH \\"
    echo "    --client-id $CLIENT_ID \\"
    echo "    --auth-parameters USERNAME=tu_usuario,PASSWORD=tu_password"
    echo ""
    print_info "OPCIÓN 2: Usar la web de PlusMovil"
    echo "  1. Acceder a la aplicación web con tu usuario"
    echo "  2. Abrir DevTools del navegador (F12)"
    echo "  3. Ir a la pestaña 'Application' o 'Storage'"
    echo "  4. Buscar en localStorage o sessionStorage"
    echo "  5. Buscar claves como: accessToken, token, idToken, etc."
    echo "  6. Copiar el valor (será un JWT largo que empieza con 'eyJ')"
    echo ""
    print_info "OPCIÓN 3: Solicitar al equipo de PlusMovil"
    echo "  - User Pool ID completo"
    echo "  - Región de AWS"
    echo "  - Credenciales de usuario de prueba"
    echo ""
    exit 0
fi

read -p "Ingresa el Access Token: " ACCESS_TOKEN

if [ -z "$ACCESS_TOKEN" ]; then
    print_error "Token no proporcionado"
    exit 1
fi

# Validar que no sea el Client ID
if [ "$ACCESS_TOKEN" = "$CLIENT_ID" ]; then
    print_error "Has ingresado el Client ID, no el Access Token"
    echo ""
    echo "El Client ID es: $CLIENT_ID"
    echo "El Access Token es diferente, más largo y comienza con 'eyJ'"
    echo ""
    echo "Ejemplo de Access Token:"
    echo "eyJraWQiOiJxRzZtNHB..."
    exit 1
fi

# Validar que parezca un JWT (empieza con eyJ)
if [[ ! "$ACCESS_TOKEN" =~ ^eyJ ]]; then
    print_warning "El token no parece un JWT válido de AWS Cognito"
    echo "Los tokens de Cognito típicamente empiezan con 'eyJ'"
    echo ""
    read -p "¿Estás seguro que quieres continuar? (s/n): " CONTINUE
    if [ "$CONTINUE" != "s" ]; then
        exit 0
    fi
fi

print_success "Token configurado"

###############################################################################
# PASO 2: PROBAR ENDPOINT /sys-logs SIN FILTROS
###############################################################################

print_header "PASO 2: PROBANDO ENDPOINT /sys-logs (sin filtros)"

ENDPOINT="${BASE_URL}/sys-logs"
print_info "Endpoint: $ENDPOINT"

print_info "Realizando petición GET con timeout de 30 segundos..."

RESPONSE=$(curl -s -w "\n%{http_code}" --max-time 30 -X GET "$ENDPOINT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" 2>&1)

# Verificar si curl falló
if [ $? -ne 0 ]; then
    print_error "Error de conectividad o timeout"
    echo "Posibles causas:"
    echo "  1. Token inválido o expirado"
    echo "  2. Problemas de red"
    echo "  3. Servidor no responde"
    echo ""
    echo "Detalle del error:"
    echo "$RESPONSE"
    exit 1
fi

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo ""
echo "HTTP Status Code: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    print_success "Petición exitosa"
    echo ""
    echo "Respuesta:"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    echo ""

    # Contar registros si es un array
    RECORD_COUNT=$(echo "$BODY" | jq '. | length' 2>/dev/null || echo "0")
    print_info "Total de registros: $RECORD_COUNT"

    # Mostrar primer registro
    if [ "$RECORD_COUNT" -gt "0" ]; then
        echo ""
        print_info "Primer registro de ejemplo:"
        echo "$BODY" | jq '.[0]' 2>/dev/null || echo "No se pudo parsear"
    fi
else
    print_error "Error en la petición"
    echo ""
    echo "Respuesta del servidor:"
    echo "$BODY"
fi

###############################################################################
# PASO 3: PROBAR CON FILTROS
###############################################################################

print_header "PASO 3: PROBANDO CON FILTROS"

read -p "¿Deseas probar con filtros? (s/n): " TEST_FILTERS

if [ "$TEST_FILTERS" = "s" ]; then
    print_info "Aplicando filtros: limit=10, offset=0"

    ENDPOINT_WITH_FILTERS="${BASE_URL}/sys-logs?limit=10&offset=0"
    print_info "Endpoint: $ENDPOINT_WITH_FILTERS"

    RESPONSE=$(curl -s -w "\n%{http_code}" --max-time 30 -X GET "$ENDPOINT_WITH_FILTERS" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" 2>&1)

    if [ $? -ne 0 ]; then
        print_error "Error de conectividad o timeout"
        echo "$RESPONSE"
        exit 1
    fi    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    echo ""
    echo "HTTP Status Code: $HTTP_CODE"
    echo ""

    if [ "$HTTP_CODE" = "200" ]; then
        print_success "Petición con filtros exitosa"
        echo ""
        echo "Respuesta:"
        echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"

        RECORD_COUNT=$(echo "$BODY" | jq '. | length' 2>/dev/null || echo "0")
        print_info "Registros obtenidos con filtros: $RECORD_COUNT"
    else
        print_error "Error en la petición con filtros"
        echo ""
        echo "Respuesta del servidor:"
        echo "$BODY"
    fi
fi

###############################################################################
# PASO 4: PROBAR OTROS FILTROS DISPONIBLES
###############################################################################

print_header "PASO 4: EJEMPLOS DE FILTROS DISPONIBLES"

print_info "La API soporta los siguientes tipos de filtros:"
echo ""
echo "  1. Búsqueda parcial:"
echo "     ?module_name_like=invoice"
echo ""
echo "  2. Múltiples valores:"
echo "     ?action_type_in=create,update,delete"
echo ""
echo "  3. Rangos numéricos:"
echo "     ?id_gte=100&id_lte=500"
echo ""
echo "  4. Rangos de fechas:"
echo "     ?created_at_between=2024-01-01,2024-12-31"
echo ""
echo "  5. Valores distintos:"
echo "     ?status_ne=inactive"
echo ""
echo "  6. Ordenamiento:"
echo "     ?order_by=created_at&order_dir=desc"
echo ""
echo "  7. Paginación:"
echo "     ?limit=50&offset=0"
echo ""

read -p "¿Deseas probar algún filtro específico? (s/n): " TEST_CUSTOM

if [ "$TEST_CUSTOM" = "s" ]; then
    read -p "Ingresa los parámetros de filtro (ej: module_name_like=invoice&limit=5): " CUSTOM_FILTERS

    ENDPOINT_CUSTOM="${BASE_URL}/sys-logs?${CUSTOM_FILTERS}"
    print_info "Endpoint: $ENDPOINT_CUSTOM"

    RESPONSE=$(curl -s -w "\n%{http_code}" --max-time 30 -X GET "$ENDPOINT_CUSTOM" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" 2>&1)

    if [ $? -ne 0 ]; then
        print_error "Error de conectividad o timeout"
        echo "$RESPONSE"
        exit 1
    fi    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    echo ""
    echo "HTTP Status Code: $HTTP_CODE"
    echo ""

    if [ "$HTTP_CODE" = "200" ]; then
        print_success "Petición personalizada exitosa"
        echo ""
        echo "Respuesta:"
        echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    else
        print_error "Error en la petición personalizada"
        echo ""
        echo "Respuesta del servidor:"
        echo "$BODY"
    fi
fi

###############################################################################
# RESUMEN
###############################################################################

print_header "RESUMEN Y PRÓXIMOS PASOS"

print_success "Prueba completada"
echo ""

print_info "Configuración confirmada:"
echo "  ✅ Base URL: $BASE_URL"
echo "  ✅ Client ID: $CLIENT_ID"
echo "  ✅ Autenticación: AWS Cognito Bearer Token"
echo "  ✅ Endpoint probado: /sys-logs"
echo ""

print_info "Para implementar en PlusMovilInvoiceService:"
echo "  1. Actualizar base URL en config/services.php"
echo "  2. Implementar autenticación AWS Cognito"
echo "  3. Agregar método getSysLogs() con soporte de filtros"
echo "  4. Configurar credenciales en .env"
echo ""

print_info "Configuración requerida en .env:"
echo "  PLUSMOVIL_BASE_URL=$BASE_URL"
echo "  PLUSMOVIL_CLIENT_ID=$CLIENT_ID"
echo "  PLUSMOVIL_COGNITO_USER_POOL_ID=<solicitar>"
echo "  PLUSMOVIL_COGNITO_REGION=us-east-1"
echo "  PLUSMOVIL_USERNAME=<usuario>"
echo "  PLUSMOVIL_PASSWORD=<password>"
echo ""

print_success "Script finalizado"
