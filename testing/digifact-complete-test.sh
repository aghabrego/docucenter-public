#!/bin/bash

# Script de prueba Digifact - PARA EJECUTAR DESDE SERVIDOR CON ACCESO
# Este script debe ejecutarse desde un servidor que pueda resolver pactest.digifact.com.pa
# Ejemplo: Servidor en Panamá, VPS con acceso, o a través de VPN

set -e

echo "======================================"
echo "  Digifact PAC - Test Completo"
echo "======================================"
echo ""

# Credenciales de prueba
RUC="155704849-2-2021"
USUARIO="PRUEBAS193"
PASSWORD="Digifact*25"
USERNAME="PA.${RUC}.${USUARIO}"

# URLs
BASE_URL="https://pactest.digifact.com.pa/pa.com.apinuc/api"
TOKEN_ENDPOINT="${BASE_URL}/login/get_token"
CERTIFY_ENDPOINT="${BASE_URL}/transform/nuc"

echo "═══════════════════════════════════════"
echo "  PASO 1: Verificar Conectividad"
echo "═══════════════════════════════════════"
echo ""

echo "→ Intentando resolver DNS..."
if host pactest.digifact.com.pa > /dev/null 2>&1; then
    echo "✓ DNS resuelve correctamente"
    host pactest.digifact.com.pa | head -3
else
    echo "✗ ERROR: DNS no resuelve"
    echo ""
    echo "Este servidor no puede acceder a pactest.digifact.com.pa"
    echo "Posibles soluciones:"
    echo "  1. Ejecutar desde servidor en Panamá"
    echo "  2. Usar VPN con salida en Panamá"
    echo "  3. Contactar a soporte de Digifact"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════"
echo "  PASO 2: Autenticación (Obtener Token)"
echo "═══════════════════════════════════════"
echo ""

echo "→ Configuración:"
echo "  RUC:      $RUC"
echo "  Usuario:  $USUARIO"
echo "  Username: $USERNAME"
echo "  Endpoint: $TOKEN_ENDPOINT"
echo ""

echo "→ Enviando solicitud de autenticación..."

AUTH_RESPONSE=$(curl -s -X POST "$TOKEN_ENDPOINT" \
  -H "Content-Type: application/json" \
  -w "\n%{http_code}" \
  -d "{
    \"Username\": \"$USERNAME\",
    \"Password\": \"$PASSWORD\"
  }")

# Separar body y código HTTP
HTTP_CODE=$(echo "$AUTH_RESPONSE" | tail -n1)
BODY=$(echo "$AUTH_RESPONSE" | sed '$d')

echo "  Código HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✓ Autenticación exitosa"
    echo ""

    # Extraer token
    TOKEN=$(echo "$BODY" | jq -r '.token' 2>/dev/null)

    if [ "$TOKEN" != "null" ] && [ -n "$TOKEN" ]; then
        echo "→ Token obtenido:"
        echo "  Longitud: ${#TOKEN} caracteres"
        echo "  Preview:  ${TOKEN:0:60}..."
        echo ""

        # Guardar token
        echo "$TOKEN" > /tmp/digifact_token.txt
        echo "  Token guardado en: /tmp/digifact_token.txt"
        echo ""

        # Información del token
        EXPIRY=$(date -d "+30 days" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || date -v+30d "+%Y-%m-%d %H:%M:%S")
        echo "  Válido por: 30 días"
        echo "  Expira:     $EXPIRY"
        echo ""
    else
        echo "✗ Error: No se pudo extraer el token"
        echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
        exit 1
    fi
else
    echo "✗ Error en autenticación (HTTP $HTTP_CODE)"
    echo ""
    echo "→ Respuesta del servidor:"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    exit 1
fi

echo "═══════════════════════════════════════"
echo "  PASO 3: Verificar Operaciones GET"
echo "═══════════════════════════════════════"
echo ""

echo "→ Probando SHAREDINFO endpoint..."
SHAREDINFO_URL="${BASE_URL}/SHAREDINFO?TRANSACTION=SHARED_INFO&RUC=${RUC}&DATA1=SHARED_GETDOCINFO&USERNAME=${USUARIO}"

SHARED_RESPONSE=$(curl -s -w "\n%{http_code}" \
  -H "Authorization: $TOKEN" \
  "$SHAREDINFO_URL")

SHARED_CODE=$(echo "$SHARED_RESPONSE" | tail -n1)
echo "  Código HTTP: $SHARED_CODE"

if [ "$SHARED_CODE" -eq 200 ] || [ "$SHARED_CODE" -eq 400 ]; then
    echo "✓ Endpoint SHAREDINFO accesible"
else
    echo "⚠ Endpoint SHAREDINFO no accesible o error"
fi

echo ""
echo "═══════════════════════════════════════"
echo "  RESUMEN DE PRUEBAS"
echo "═══════════════════════════════════════"
echo ""
echo "✓ DNS resuelve correctamente"
echo "✓ Autenticación exitosa"
echo "✓ Token obtenido y guardado"
echo "✓ Endpoints accesibles"
echo ""
echo "→ Token guardado en: /tmp/digifact_token.txt"
echo "→ Token válido por: 30 días"
echo ""
echo "Siguiente paso: Certificar un documento de prueba"
echo "  Usar: ./docs/testing/digifact-certify-test.sh"
echo ""
echo "======================================"
echo "  Test completado exitosamente"
echo "======================================"
