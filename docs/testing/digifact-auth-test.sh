#!/bin/bash

# Script de prueba para autenticación Digifact PAC
# Ubicación: docs/testing/digifact-auth-test.sh
# Uso: ./docs/testing/digifact-auth-test.sh

set -e

echo "======================================"
echo "  Digifact PAC - Test de Autenticación"
echo "======================================"
echo ""

# Credenciales de prueba
RUC="155704849-2-2021"
USUARIO="PRUEBAS193"
PASSWORD="Digifact*25"

# Construir username según formato Digifact: PA.{RUC}.{USUARIO}
USERNAME="PA.${RUC}.${USUARIO}"

# URL del endpoint de test (exacta según documentación PDF)
URL="https://pactest.digifact.com.pa/pa.com.apinuc/api/login/get_token"

echo "→ Configuración:"
echo "  RUC:      $RUC"
echo "  Usuario:  $USUARIO"
echo "  Username: $USERNAME"
echo "  Endpoint: $URL"
echo ""

echo "→ Enviando solicitud de autenticación..."
echo ""

# Hacer la petición POST con más detalles de error
HTTP_CODE=$(curl -s -o /tmp/digifact_response.txt -w "%{http_code}" -X POST "$URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"Username\": \"$USERNAME\",
    \"Password\": \"$PASSWORD\"
  }")

RESPONSE=$(cat /tmp/digifact_response.txt)

echo "→ Código HTTP: $HTTP_CODE"
echo ""

# Verificar si hubo error de conexión
if [ -z "$HTTP_CODE" ] || [ "$HTTP_CODE" = "000" ]; then
    echo "✗ Error de conexión"
    echo "  No se pudo conectar al servidor Digifact"
    echo "  Posibles causas:"
    echo "    - Sin conexión a internet"
    echo "    - Servidor no disponible"
    echo "    - Firewall bloqueando la conexión"
    echo ""
    echo "→ Intentando conexión simple..."
    curl -v "$URL" 2>&1 | head -20
    exit 1
fi

# Verificar si la respuesta contiene un token
if echo "$RESPONSE" | grep -q "token"; then
    echo "✓ Autenticación exitosa"
    echo ""
    echo "→ Respuesta completa:"
    echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
    echo ""

    # Extraer y mostrar solo el token
    TOKEN=$(echo "$RESPONSE" | jq -r '.token' 2>/dev/null)
    if [ "$TOKEN" != "null" ] && [ -n "$TOKEN" ]; then
        echo "→ Token obtenido:"
        echo "$TOKEN"
        echo ""

        # Mostrar primeros y últimos caracteres del token
        TOKEN_LENGTH=${#TOKEN}
        echo "→ Información del token:"
        echo "  Longitud: $TOKEN_LENGTH caracteres"
        echo "  Preview:  ${TOKEN:0:50}..."
        echo ""

        # Guardar token en archivo temporal para uso posterior
        TOKEN_FILE="/tmp/digifact_token.txt"
        echo "$TOKEN" > "$TOKEN_FILE"
        echo "→ Token guardado en: $TOKEN_FILE"
        echo "  (Válido por 30 días desde ahora)"
        echo ""

        # Calcular fecha de expiración (30 días)
        EXPIRY_DATE=$(date -d "+30 days" "+%Y-%m-%d %H:%M:%S")
        echo "  Expira: $EXPIRY_DATE"
        echo ""
    fi
else
    echo "✗ Error en autenticación"
    echo ""
    echo "→ Respuesta del servidor:"
    echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
    echo ""
    exit 1
fi

echo "======================================"
echo "  Test completado exitosamente"
echo "======================================"
echo ""
echo "Próximos pasos:"
echo "  1. Usar el token para certificar un documento"
echo "  2. El token es válido por 30 días"
echo "  3. Ver guía completa en: docs/technical/digifact-panama-pac-implementation-guide.md"
echo ""
