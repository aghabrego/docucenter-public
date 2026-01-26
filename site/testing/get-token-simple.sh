#!/bin/bash

###############################################################################
# Helper: Obtener Access Token de AWS Cognito (sin AWS CLI)
#
# Este script usa curl directo para autenticarse con AWS Cognito
# No requiere AWS CLI instalado
#
# Uso:
#   chmod +x docs/testing/get-token-simple.sh
#   ./docs/testing/get-token-simple.sh
###############################################################################

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }

###############################################################################
# NOTA IMPORTANTE
###############################################################################

echo ""
print_warning "PROBLEMA IDENTIFICADO:"
echo "AWS Cognito requiere firma de peticiones (AWS Signature V4)"
echo "No se puede autenticar directamente con curl sin AWS CLI o SDK"
echo ""

print_info "SOLUCIONES DISPONIBLES:"
echo ""
echo "1️⃣  OPCIÓN RECOMENDADA: Instalar AWS CLI"
echo "   sudo apt update"
echo "   sudo apt install awscli"
echo "   Luego ejecutar: ./docs/testing/get-cognito-token.sh"
echo ""

echo "2️⃣  ALTERNATIVA: Obtener token desde la web"
echo "   a) Acceder a la aplicación web de PlusMovil"
echo "   b) Iniciar sesión con:"
echo "      Usuario: larosemena_el_nawal"
echo "      Password: 7162384Li*"
echo "   c) Abrir DevTools (F12) → Application → Local/Session Storage"
echo "   d) Buscar: accessToken, token, idToken"
echo "   e) Copiar el valor (JWT que empieza con 'eyJ')"
echo ""

echo "3️⃣  ALTERNATIVA: Solicitar al equipo de PlusMovil"
echo "   Pedir que ejecuten el comando de autenticación y te envíen el token"
echo ""

print_info "CREDENCIALES PARA USAR:"
echo "   Usuario: larosemena_el_nawal"
echo "   Password: 7162384Li*"
echo "   Client ID: 7t3s7lb4tfg6ssal586l929ovl"
echo ""

###############################################################################
# OPCIÓN: INSTALAR AWS CLI AHORA
###############################################################################

read -p "¿Deseas instalar AWS CLI ahora? (s/n): " INSTALL_CLI

if [ "$INSTALL_CLI" = "s" ]; then
    print_info "Instalando AWS CLI..."

    if command -v apt-get &> /dev/null; then
        sudo apt update
        sudo apt install -y awscli
    elif command -v yum &> /dev/null; then
        sudo yum install -y awscli
    elif command -v brew &> /dev/null; then
        brew install awscli
    else
        print_error "No se pudo detectar el gestor de paquetes"
        echo "Instala manualmente desde: https://aws.amazon.com/cli/"
        exit 1
    fi

    if command -v aws &> /dev/null; then
        print_success "AWS CLI instalado correctamente"
        echo ""
        print_info "Ahora ejecuta: ./docs/testing/get-cognito-token.sh"
    else
        print_error "Error en la instalación de AWS CLI"
        exit 1
    fi
else
    print_info "Sin AWS CLI, usa una de las alternativas mencionadas arriba"
fi

echo ""
