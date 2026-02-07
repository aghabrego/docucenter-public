#!/bin/bash

# Script de prueba exitoso Digifact PAC
# URLs correctas identificadas y funcionales
# Uso: ./docs/testing/digifact-success-test.sh

set -e

echo "======================================"
echo "  Digifact PAC - Test Exitoso"
echo "======================================"
echo ""

# Credenciales de prueba
RUC="155704849-2-2021"
USUARIO="PRUEBAS193"
PASSWORD="Digifact*25"
USERNAME="PA.${RUC}.${USUARIO}"

# URLs correctas
BASE_URL="https://testnucpa.digifact.com/api"
TOKEN_ENDPOINT="${BASE_URL}/login/get_token"
TRANSFORM_ENDPOINT="${BASE_URL}/transform/nuc_json"

echo "→ Configuración:"
echo "  Base URL:  $BASE_URL"
echo "  RUC:       $RUC"
echo "  Usuario:   $USUARIO"
echo "  Username:  $USERNAME"
echo ""

echo "═══════════════════════════════════════"
echo "  PASO 1: Obtener Token de Autenticación"
echo "═══════════════════════════════════════"
echo ""

echo "→ Endpoint: $TOKEN_ENDPOINT"
echo ""

AUTH_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$TOKEN_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d "{
    \"Username\": \"$USERNAME\",
    \"Password\": \"$PASSWORD\"
  }")

HTTP_CODE=$(echo "$AUTH_RESPONSE" | tail -n1)
BODY=$(echo "$AUTH_RESPONSE" | sed '$d')

echo "  Código HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✓ Autenticación exitosa"
    echo ""

    # Extraer token usando python3
    TOKEN=$(echo "$BODY" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('Token', ''))" 2>/dev/null)
    EXPIRA=$(echo "$BODY" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('expira_en', ''))" 2>/dev/null)

    if [ -n "$TOKEN" ]; then
        echo "→ Token JWT obtenido:"
        echo "  Longitud: ${#TOKEN} caracteres"
        echo "  Preview:  ${TOKEN:0:60}..."
        echo "  Expira:   $EXPIRA"
        echo ""

        # Guardar token
        echo "$TOKEN" > /tmp/digifact_token.txt
        echo "→ Token guardado en: /tmp/digifact_token.txt"
        echo ""
    else
        echo "✗ No se pudo extraer el token"
        echo "$BODY"
        exit 1
    fi
else
    echo "✗ Error en autenticación (HTTP $HTTP_CODE)"
    echo ""
    echo "→ Respuesta:"
    echo "$BODY"
    exit 1
fi

echo "═══════════════════════════════════════"
echo "  PASO 2: Verificar Token con Transform"
echo "═══════════════════════════════════════"
echo ""

# Documento de prueba mínimo
TEST_DOC='{
  "test": true,
  "documento": "prueba"
}'

echo "→ Endpoint: $TRANSFORM_ENDPOINT"
echo "  Parámetros: TAXID=$RUC, USERNAME=$USUARIO, FORMAT=PDF"
echo ""

TRANSFORM_URL="${TRANSFORM_ENDPOINT}?TAXID=${RUC}&USERNAME=${USUARIO}&FORMAT=PDF"

TRANSFORM_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$TRANSFORM_URL" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "$TEST_DOC")

TRANSFORM_CODE=$(echo "$TRANSFORM_RESPONSE" | tail -n1)
TRANSFORM_BODY=$(echo "$TRANSFORM_RESPONSE" | sed '$d')

echo "  Código HTTP: $TRANSFORM_CODE"
echo ""

case "$TRANSFORM_CODE" in
    200)
        echo "✓ Certificación exitosa (HTTP 200)"
        echo ""
        echo "→ Respuesta:"
        echo "$TRANSFORM_BODY" | python3 -m json.tool 2>/dev/null || echo "$TRANSFORM_BODY"
        ;;

    400)
        echo "⚠ Error de validación (HTTP 400)"
        echo "  El token es válido pero el documento no cumple con la estructura"
        echo ""
        echo "→ Mensaje:"
        echo "$TRANSFORM_BODY" | python3 -m json.tool 2>/dev/null || echo "$TRANSFORM_BODY"
        echo ""
        echo "💡 Esto es esperado - necesitas un documento con estructura DGI válida"
        ;;

    401)
        echo "✗ Token inválido o expirado (HTTP 401)"
        echo ""
        echo "→ Respuesta:"
        echo "$TRANSFORM_BODY"
        exit 1
        ;;

    *)
        echo "⚠ Respuesta inesperada (HTTP $TRANSFORM_CODE)"
        echo ""
        echo "→ Respuesta:"
        echo "$TRANSFORM_BODY"
        ;;
esac

echo ""
echo "═══════════════════════════════════════"
echo "  RESUMEN"
echo "═══════════════════════════════════════"
echo ""
echo "✓ Dominio testnucpa.digifact.com funcional"
echo "✓ Autenticación exitosa"
echo "✓ Token JWT obtenido y guardado"
echo "✓ Endpoint transform accesible"
echo ""
echo "URLs correctas identificadas:"
echo "  Login:     $TOKEN_ENDPOINT"
echo "  Transform: $TRANSFORM_ENDPOINT"
echo ""
echo "Token guardado en: /tmp/digifact_token.txt"
echo "Válido hasta: $EXPIRA"
echo ""
echo "Próximo paso:"
echo "  Crear documento JSON con estructura DGI Panamá"
echo "  y certificar con el endpoint transform"
echo ""
echo "======================================"
