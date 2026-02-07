#!/bin/bash

###############################################################################
# Helper: Obtener Access Token de AWS Cognito
#
# Este script usa AWS CLI para autenticarse y obtener el Access Token
#
# Requisitos:
#   - AWS CLI instalado: https://aws.amazon.com/cli/
#   - Credenciales de usuario
#
# Uso:
#   chmod +x docs/testing/get-cognito-token.sh
#   ./docs/testing/get-cognito-token.sh
###############################################################################

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

###############################################################################
# CONFIGURACIÓN
###############################################################################

ENVIRONMENT="qa"

if [ "$ENVIRONMENT" = "qa" ]; then
    CLIENT_ID="7t3s7lb4tfg6ssal586l929ovl"
else
    CLIENT_ID="771vv2q1ararj5u1f084opsdg7"
fi

REGION="us-east-1"

###############################################################################
# VERIFICAR AWS CLI
###############################################################################

print_header "OBTENER TOKEN DE AWS COGNITO"

if ! command -v aws &> /dev/null; then
    print_error "AWS CLI no está instalado"
    echo ""
    echo "Instalar AWS CLI:"
    echo "  Ubuntu/Debian: sudo apt install awscli"
    echo "  macOS: brew install awscli"
    echo "  O visita: https://aws.amazon.com/cli/"
    exit 1
fi

print_success "AWS CLI encontrado: $(aws --version)"

###############################################################################
# SOLICITAR CREDENCIALES
###############################################################################

print_info "Ambiente: $ENVIRONMENT"
print_info "Client ID: $CLIENT_ID"
print_info "Región: $REGION"
echo ""

# Solicitar credenciales
read -p "Usuario: " USERNAME
read -s -p "Contraseña: " PASSWORD
echo ""

if [ -z "$USERNAME" ] || [ -z "$PASSWORD" ]; then
    print_error "Usuario y contraseña son requeridos"
    exit 1
fi

echo ""

###############################################################################
# AUTENTICACIÓN
###############################################################################

print_header "AUTENTICANDO CON AWS COGNITO"

print_info "Ejecutando initiate-auth..."

# Ejecutar comando de autenticación
RESPONSE=$(aws cognito-idp initiate-auth \
    --region "$REGION" \
    --auth-flow USER_PASSWORD_AUTH \
    --client-id "$CLIENT_ID" \
    --auth-parameters USERNAME="$USERNAME",PASSWORD="$PASSWORD" \
    2>&1)

# Verificar si hubo error
if [ $? -ne 0 ]; then
    print_error "Error en autenticación"
    echo ""
    echo "Respuesta:"
    echo "$RESPONSE"
    echo ""

    # Diagnóstico común
    if echo "$RESPONSE" | grep -q "ResourceNotFoundException"; then
        print_error "User Pool no encontrado o Client ID incorrecto"
        echo "Necesitas obtener del equipo de PlusMovil:"
        echo "  - User Pool ID correcto"
        echo "  - Región correcta (actualmente: $REGION)"
    elif echo "$RESPONSE" | grep -q "NotAuthorizedException"; then
        print_error "Credenciales incorrectas o usuario no autorizado"
    elif echo "$RESPONSE" | grep -q "UserPoolClientMismatch"; then
        print_error "Client ID no pertenece al User Pool"
    fi

    exit 1
fi

###############################################################################
# EXTRAER TOKENS
###############################################################################

print_success "Autenticación exitosa"
echo ""

# Extraer tokens usando jq
if command -v jq &> /dev/null; then
    ACCESS_TOKEN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.AccessToken')
    ID_TOKEN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.IdToken')
    REFRESH_TOKEN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.RefreshToken')
    EXPIRES_IN=$(echo "$RESPONSE" | jq -r '.AuthenticationResult.ExpiresIn')

    print_success "Tokens obtenidos"
    echo ""
    echo "Access Token (primeros 50 caracteres):"
    echo "${ACCESS_TOKEN:0:50}..."
    echo ""
    echo "Expira en: $EXPIRES_IN segundos (~$((EXPIRES_IN / 60)) minutos)"
    echo ""

    # Guardar en archivo temporal
    TOKEN_FILE="/tmp/plusmovil_access_token.txt"
    echo "$ACCESS_TOKEN" > "$TOKEN_FILE"
    print_success "Token guardado en: $TOKEN_FILE"
    echo ""

    # Mostrar para copiar
    print_header "TOKEN PARA USAR EN EL SCRIPT"
    echo "Copia este token completo:"
    echo ""
    echo "$ACCESS_TOKEN"
    echo ""

    # Instrucciones
    print_info "Ahora puedes ejecutar:"
    echo "  ./docs/testing/test-plusmovil-api.sh"
    echo ""
    echo "Y pegar el token cuando te lo pida."

else
    print_success "Respuesta completa:"
    echo "$RESPONSE"
    echo ""
    print_info "Instala 'jq' para parsear automáticamente:"
    echo "  sudo apt install jq"
fi

###############################################################################
# PROBAR TOKEN
###############################################################################

if [ -n "$ACCESS_TOKEN" ]; then
    echo ""
    read -p "¿Deseas probar el token con la API ahora? (s/n): " TEST_NOW

    if [ "$TEST_NOW" = "s" ]; then
        print_header "PROBANDO TOKEN CON API"

        BASE_URL="https://28cwop8rj6.execute-api.us-east-1.amazonaws.com/dev"
        ENDPOINT="${BASE_URL}/sys-logs?limit=5"

        print_info "Endpoint: $ENDPOINT"

        RESPONSE=$(curl -s -w "\n%{http_code}" --max-time 30 -X GET "$ENDPOINT" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" 2>&1)

        HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
        BODY=$(echo "$RESPONSE" | sed '$d')

        echo ""
        echo "HTTP Status Code: $HTTP_CODE"
        echo ""

        if [ "$HTTP_CODE" = "200" ]; then
            print_success "Token válido - API respondió correctamente"
            echo ""
            echo "Respuesta:"
            echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
        else
            print_error "Error en la petición"
            echo ""
            echo "Respuesta:"
            echo "$BODY"
        fi
    fi
fi

print_header "COMPLETADO"
