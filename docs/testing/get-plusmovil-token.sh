#!/bin/bash

###############################################################################
# Script para Obtener Token de PlusMovil usando AWS Cognito
#
# Este script facilita la obtención del Access Token necesario para llamar
# a la API de PlusMovil
#
# Requisitos:
#   - AWS CLI instalado: https://aws.amazon.com/cli/
#   - Credenciales de usuario PlusMovil
#
# Uso:
#   chmod +x docs/testing/get-plusmovil-token.sh
#   ./docs/testing/get-plusmovil-token.sh
###############################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "\n${BLUE}==========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}==========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

###############################################################################
# VERIFICAR AWS CLI
###############################################################################

print_header "OBTENER TOKEN DE PLUSMOVIL"

if ! command -v aws &> /dev/null; then
    print_error "AWS CLI no está instalado"
    echo ""
    print_info "Instalar AWS CLI:"
    echo "  Ubuntu/Debian: sudo apt install awscli"
    echo "  macOS: brew install awscli"
    echo "  O visitar: https://aws.amazon.com/cli/"
    echo ""
    exit 1
fi

print_success "AWS CLI detectado"

###############################################################################
# SELECCIONAR AMBIENTE
###############################################################################

print_header "PASO 1: SELECCIONAR AMBIENTE"

echo "¿Qué ambiente deseas usar?"
echo "  1) QA"
echo "  2) Producción"
echo ""
read -p "Selecciona opción (1 o 2): " ENV_OPTION

if [ "$ENV_OPTION" = "1" ]; then
    ENVIRONMENT="QA"
    CLIENT_ID="7t3s7lb4tfg6ssal586l929ovl"
elif [ "$ENV_OPTION" = "2" ]; then
    ENVIRONMENT="PROD"
    CLIENT_ID="771vv2q1ararj5u1f084opsdg7"
else
    print_error "Opción inválida"
    exit 1
fi

print_success "Ambiente seleccionado: $ENVIRONMENT"
print_info "Client ID: $CLIENT_ID"

###############################################################################
# SOLICITAR CREDENCIALES
###############################################################################

print_header "PASO 2: INGRESAR CREDENCIALES"

print_warning "IMPORTANTE: Estas son las credenciales de tu usuario web de PlusMovil"
echo ""

read -p "Usuario: " USERNAME
read -s -p "Contraseña: " PASSWORD
echo ""

if [ -z "$USERNAME" ] || [ -z "$PASSWORD" ]; then
    print_error "Usuario y contraseña son requeridos"
    exit 1
fi

###############################################################################
# OBTENER TOKEN
###############################################################################

print_header "PASO 3: SOLICITAR TOKEN A AWS COGNITO"

print_info "Enviando petición a AWS Cognito..."
echo ""

# Construir el comando
AUTH_PARAMS="USERNAME=$USERNAME,PASSWORD=$PASSWORD"

# Ejecutar petición
RESPONSE=$(aws cognito-idp initiate-auth \
    --region us-east-1 \
    --auth-flow USER_PASSWORD_AUTH \
    --client-id "$CLIENT_ID" \
    --auth-parameters "$AUTH_PARAMS" \
    2>&1)

if [ $? -ne 0 ]; then
    print_error "Error al obtener token"
    echo ""
    echo "Respuesta de AWS:"
    echo "$RESPONSE"
    echo ""
    print_warning "Posibles causas:"
    echo "  1. Credenciales incorrectas"
    echo "  2. Usuario no tiene acceso al ambiente seleccionado"
    echo "  3. User Pool ID incorrecto"
    echo "  4. AWS CLI no configurado correctamente"
    echo ""
    exit 1
fi

# Extraer tokens
ACCESS_TOKEN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.AccessToken' 2>/dev/null)
ID_TOKEN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.IdToken' 2>/dev/null)
REFRESH_TOKEN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.RefreshToken' 2>/dev/null)
EXPIRES_IN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.ExpiresIn' 2>/dev/null)

if [ -z "$ACCESS_TOKEN" ] || [ "$ACCESS_TOKEN" = "null" ]; then
    print_error "No se pudo extraer el Access Token"
    echo ""
    echo "Respuesta completa:"
    echo "$RESPONSE"
    exit 1
fi

print_success "Token obtenido exitosamente"

###############################################################################
# MOSTRAR RESULTADOS
###############################################################################

print_header "TOKENS OBTENIDOS"

echo "Access Token (usar para API calls):"
echo "$ACCESS_TOKEN"
echo ""

echo "ID Token:"
echo "$ID_TOKEN"
echo ""

if [ -n "$EXPIRES_IN" ] && [ "$EXPIRES_IN" != "null" ]; then
    EXPIRES_MINUTES=$((EXPIRES_IN / 60))
    print_info "El token expira en: $EXPIRES_MINUTES minutos ($EXPIRES_IN segundos)"
fi

###############################################################################
# GUARDAR TOKEN
###############################################################################

print_header "GUARDAR TOKEN"

read -p "¿Deseas guardar el token en un archivo? (s/n): " SAVE_TOKEN

if [ "$SAVE_TOKEN" = "s" ]; then
    TOKEN_FILE="docs/testing/.plusmovil-token-${ENVIRONMENT}.txt"

    echo "ACCESS_TOKEN=$ACCESS_TOKEN" > "$TOKEN_FILE"
    echo "ID_TOKEN=$ID_TOKEN" >> "$TOKEN_FILE"
    echo "EXPIRES_AT=$(date -d "+${EXPIRES_IN} seconds" '+%Y-%m-%d %H:%M:%S')" >> "$TOKEN_FILE"

    print_success "Token guardado en: $TOKEN_FILE"
    print_warning "IMPORTANTE: Este archivo contiene información sensible, no lo subas a git"
    echo ""

    # Agregar a .gitignore si no existe
    if ! grep -q ".plusmovil-token-" .gitignore 2>/dev/null; then
        echo "docs/testing/.plusmovil-token-*.txt" >> .gitignore
        print_info "Agregado a .gitignore"
    fi
fi

###############################################################################
# PROBAR TOKEN
###############################################################################

print_header "PROBAR TOKEN"

read -p "¿Deseas probar el token ahora con la API? (s/n): " TEST_TOKEN

if [ "$TEST_TOKEN" = "s" ]; then
    if [ "$ENVIRONMENT" = "QA" ]; then
        BASE_URL="https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa"
    else
        BASE_URL="https://0m2jyxhl41.execute-api.us-east-1.amazonaws.com/prod"
    fi

    print_info "Probando endpoint: ${BASE_URL}/sys-logs"
    echo ""

    TEST_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/sys-logs?limit=5" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" 2>&1)

    HTTP_CODE=$(echo "$TEST_RESPONSE" | tail -n1)
    BODY=$(echo "$TEST_RESPONSE" | sed '$d')

    echo "HTTP Status Code: $HTTP_CODE"
    echo ""

    if [ "$HTTP_CODE" = "200" ]; then
        print_success "Token funciona correctamente"
        echo ""
        echo "Respuesta (primeros 5 registros):"
        echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    else
        print_error "Error al probar token"
        echo ""
        echo "Respuesta:"
        echo "$BODY"
    fi
fi

###############################################################################
# RESUMEN
###############################################################################

print_header "RESUMEN"

print_success "Proceso completado"
echo ""
print_info "Puedes usar el Access Token en el script test-plusmovil-api.sh"
echo ""
print_info "Ejemplo de uso:"
echo "  export PLUSMOVIL_TOKEN='$ACCESS_TOKEN'"
echo "  ./docs/testing/test-plusmovil-api.sh"
echo ""

print_warning "Recordar: El token expira, deberás obtener uno nuevo cuando expire"
