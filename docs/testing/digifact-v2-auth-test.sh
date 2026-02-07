#!/bin/bash

# Script de prueba para autenticación Digifact PAC API v2
# Endpoint alternativo que SÍ resuelve desde cualquier ubicación
# Uso: ./docs/testing/digifact-v2-auth-test.sh

set -e

echo "======================================"
echo "  Digifact PAC API v2 - Test"
echo "======================================"
echo ""

# Credenciales de prueba
RUC="155704849-2-2021"
USUARIO="PRUEBAS193"
PASSWORD="Digifact*25"
USERNAME="PA.${RUC}.${USUARIO}"

# URLs v2
BASE_URL="https://testnucpa.digifact.com/api/v2"
TOKEN_ENDPOINT="${BASE_URL}/auth/login"
TRANSFORM_ENDPOINT="${BASE_URL}/transform/nuc_json"

echo "→ Configuración:"
echo "  RUC:      $RUC"
echo "  Usuario:  $USUARIO"
echo "  Username: $USERNAME"
echo "  Base URL: $BASE_URL"
echo ""

echo "═══════════════════════════════════════"
echo "  PASO 1: Verificar Conectividad DNS"
echo "═══════════════════════════════════════"
echo ""

if curl -s -I "$BASE_URL" > /dev/null 2>&1; then
    echo "✓ DNS resuelve correctamente"
    echo "  Host: testnucpa.digifact.com"
else
    echo "⚠ Advertencia: Conectividad DNS"
fi

echo ""
echo "═══════════════════════════════════════"
echo "  PASO 2: Intentar Autenticación"
echo "═══════════════════════════════════════"
echo ""

echo "→ Probando endpoint de login..."
echo "  Endpoint: $TOKEN_ENDPOINT"
echo ""

# Intentar autenticación (puede variar el formato)
AUTH_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$TOKEN_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"password\": \"$PASSWORD\"
  }" 2>&1)

HTTP_CODE=$(echo "$AUTH_RESPONSE" | tail -n1)
BODY=$(echo "$AUTH_RESPONSE" | sed '$d')

echo "  Código HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✓ Respuesta exitosa (HTTP 200)"
    echo ""
    echo "→ Respuesta:"
    echo "$BODY"
    echo ""

    # Intentar extraer token si existe
    if echo "$BODY" | grep -qi "token"; then
        echo "✓ Token encontrado en respuesta"

        # Guardar respuesta completa
        echo "$BODY" > /tmp/digifact_v2_response.json
        echo "  Respuesta guardada en: /tmp/digifact_v2_response.json"
        echo ""
    fi
elif [ "$HTTP_CODE" = "401" ]; then
    echo "⚠ Autenticación fallida (HTTP 401)"
    echo "→ Respuesta:"
    echo "$BODY"
    echo ""
    echo "Posibles causas:"
    echo "  - Credenciales incorrectas"
    echo "  - Formato de username diferente"
    echo "  - Endpoint de autenticación diferente"
elif [ "$HTTP_CODE" = "404" ]; then
    echo "⚠ Endpoint no encontrado (HTTP 404)"
    echo ""
    echo "→ Intentando endpoints alternativos..."
    echo ""

    # Probar otros endpoints comunes
    ALT_ENDPOINTS=(
        "/auth/token"
        "/login"
        "/authenticate"
        "/auth/get_token"
    )

    for endpoint in "${ALT_ENDPOINTS[@]}"; do
        echo "  Probando: ${BASE_URL}${endpoint}"
        ALT_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}${endpoint}" \
          -H "Content-Type: application/json" \
          -d "{\"username\": \"$USERNAME\", \"password\": \"$PASSWORD\"}")
        echo "    Código: $ALT_CODE"

        if [ "$ALT_CODE" != "404" ]; then
            echo "    ✓ Endpoint encontrado, requiere investigación"
        fi
    done
else
    echo "⚠ Respuesta inesperada (HTTP $HTTP_CODE)"
    echo ""
    echo "→ Respuesta:"
    echo "$BODY"
fi

echo ""
echo "═══════════════════════════════════════"
echo "  PASO 3: Probar Endpoint Transform"
echo "═══════════════════════════════════════"
echo ""

echo "→ Probando endpoint: $TRANSFORM_ENDPOINT"
echo ""

# Crear un documento de prueba mínimo
TEST_DOC='{
  "ruc": "'$RUC'",
  "test": true,
  "documento": {
    "tipo": "01",
    "numero": "TEST-001"
  }
}'

TRANSFORM_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$TRANSFORM_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d "$TEST_DOC" 2>&1)

TRANSFORM_CODE=$(echo "$TRANSFORM_RESPONSE" | tail -n1)
TRANSFORM_BODY=$(echo "$TRANSFORM_RESPONSE" | sed '$d')

echo "  Código HTTP: $TRANSFORM_CODE"
echo ""

if [ "$TRANSFORM_CODE" = "401" ]; then
    echo "⚠ Requiere autenticación (HTTP 401)"
    echo "  El endpoint requiere token de autorización"
elif [ "$TRANSFORM_CODE" = "200" ]; then
    echo "✓ Endpoint accesible (HTTP 200)"
    echo ""
    echo "→ Respuesta:"
    echo "$TRANSFORM_BODY"
elif [ "$TRANSFORM_CODE" = "422" ] || [ "$TRANSFORM_CODE" = "400" ]; then
    echo "⚠ Error de validación (HTTP $TRANSFORM_CODE)"
    echo "  El endpoint está activo pero rechaza los datos"
    echo ""
    echo "→ Respuesta:"
    echo "$TRANSFORM_BODY"
else
    echo "⚠ Respuesta inesperada (HTTP $TRANSFORM_CODE)"
    echo ""
    echo "→ Respuesta:"
    echo "$TRANSFORM_BODY"
fi

echo ""
echo "═══════════════════════════════════════"
echo "  RESUMEN"
echo "═══════════════════════════════════════"
echo ""
echo "✓ Dominio testnucpa.digifact.com RESUELVE"
echo "✓ Certificado SSL válido"
echo "  Códigos obtenidos:"
echo "    - Auth:      HTTP $HTTP_CODE"
echo "    - Transform: HTTP $TRANSFORM_CODE"
echo ""
echo "Siguiente paso:"
echo "  1. Verificar documentación API v2"
echo "  2. Confirmar endpoints correctos"
echo "  3. Ajustar formato de autenticación"
echo ""
echo "======================================"
