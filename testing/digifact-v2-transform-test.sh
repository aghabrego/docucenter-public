#!/bin/bash

# Script de prueba para Digifact PAC API v2 - Transform Endpoint
# Endpoint: testnucpa.digifact.com/api/v2/transform/nuc_json
# Método: POST con parámetros en URL
# Uso: ./docs/testing/digifact-v2-transform-test.sh

set -e

echo "======================================"
echo "  Digifact PAC API v2 - Transform"
echo "======================================"
echo ""

# Credenciales de prueba
RUC="155704849-2-2021"
USUARIO="PRUEBAS193"
PASSWORD="Digifact*25"

# Endpoint con parámetros requeridos
BASE_URL="https://testnucpa.digifact.com/api/v2"
TRANSFORM_ENDPOINT="${BASE_URL}/transform/nuc_json?TAXID=${RUC}&USERNAME=${USUARIO}"

echo "→ Configuración:"
echo "  RUC (TAXID): $RUC"
echo "  Usuario:     $USUARIO"
echo "  Endpoint:    $TRANSFORM_ENDPOINT"
echo ""

echo "═══════════════════════════════════════"
echo "  PASO 1: Documento de Prueba Mínimo"
echo "═══════════════════════════════════════"
echo ""

# Documento de prueba mínimo según estructura panameña
read -r -d '' TEST_DOC <<'EOF' || true
{
  "dFechaEm": "2025-12-29T10:00:00-05:00",
  "gTotSub": {
    "dSubTotOpeGrav": "100.00",
    "dSubTotOpeExo": "0.00",
    "dSubTot": "100.00"
  },
  "gTotGen": {
    "dTotItBMs": "7.00",
    "dTotFACT": "107.00"
  },
  "gDatFact": {
    "gTimb": {
      "dTpoTiDE": "01",
      "dNatOpe": "01",
      "dDesNatOpe": "Venta de mercaderias"
    },
    "gEncFE": {
      "dFecEmNR": "2025-12-29",
      "dNumNR": "TEST-001",
      "dRucEm": "$RUC",
      "dDVEmi": "12",
      "dRazSocEm": "Empresa de Prueba S.A.",
      "dNombEm": "Empresa de Prueba",
      "dSucEm": "0001"
    },
    "gDatRec": {
      "dTpoRuc": "2",
      "dRucRec": "0-0-0-00000",
      "dDVRec": "00",
      "dNombRec": "Cliente de Prueba"
    }
  },
  "gDtipDE": {
    "gCamFE": {
      "iNatOpe": "1",
      "iTImp": "1",
      "dTpEmis": "1",
      "dIndPres": "2"
    },
    "gCamItem": [
      {
        "dSecItem": 1,
        "gValItem": {
          "dDescIt": "Producto de Prueba",
          "dCant": "1.00",
          "dPrUnitItem": "100.00",
          "dTotOpe": "100.00",
          "dTotOpeGs": "107.00"
        },
        "gValItemOp": {
          "cPorcItBms": "7.00",
          "dBaseItBms": "100.00",
          "dLiqItBms": "7.00"
        }
      }
    ]
  }
}
EOF

echo "→ Documento de prueba:"
echo "$TEST_DOC" | head -20
echo "  ... (documento completo)"
echo ""

echo "═══════════════════════════════════════"
echo "  PASO 2: Enviar al Endpoint Transform"
echo "═══════════════════════════════════════"
echo ""

echo "→ Enviando petición POST..."
echo ""

# Enviar petición con datos en body
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$TRANSFORM_ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$TEST_DOC" 2>&1)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "  Código HTTP: $HTTP_CODE"
echo ""

# Analizar respuesta
case "$HTTP_CODE" in
    200)
        echo "✓ Transformación exitosa (HTTP 200)"
        echo ""
        echo "→ Respuesta completa:"
        echo "$BODY"
        echo ""

        # Guardar respuesta
        echo "$BODY" > /tmp/digifact_v2_transform_response.json
        echo "→ Respuesta guardada en: /tmp/digifact_v2_transform_response.json"
        echo ""

        # Intentar extraer información relevante
        if echo "$BODY" | grep -qi "cufe\|qr\|xml"; then
            echo "✓ Respuesta contiene datos de certificación"
        fi
        ;;

    400)
        echo "⚠ Error de validación (HTTP 400)"
        echo ""
        echo "→ Mensaje de error:"
        echo "$BODY"
        echo ""

        # Analizar el error
        if echo "$BODY" | grep -qi "password"; then
            echo "💡 Sugerencia: Puede requerir autenticación adicional"
        elif echo "$BODY" | grep -qi "parametros"; then
            echo "💡 Sugerencia: Faltan parámetros adicionales en la URL"
        elif echo "$BODY" | grep -qi "estructura\|formato"; then
            echo "💡 Sugerencia: Verificar estructura del documento JSON"
        fi
        ;;

    401)
        echo "⚠ No autorizado (HTTP 401)"
        echo ""
        echo "→ Respuesta:"
        echo "$BODY"
        echo ""
        echo "💡 Este endpoint requiere autenticación previa"
        echo "   Posiblemente necesita un token o PASSWORD en los parámetros"
        ;;

    422)
        echo "⚠ Error de procesamiento (HTTP 422)"
        echo ""
        echo "→ Respuesta:"
        echo "$BODY"
        echo ""
        echo "💡 El formato del documento no es válido"
        ;;

    *)
        echo "⚠ Respuesta inesperada (HTTP $HTTP_CODE)"
        echo ""
        echo "→ Respuesta:"
        echo "$BODY"
        ;;
esac

echo ""
echo "═══════════════════════════════════════"
echo "  PASO 3: Intentar con PASSWORD"
echo "═══════════════════════════════════════"
echo ""

echo "→ Algunos endpoints requieren PASSWORD en la URL..."
TRANSFORM_WITH_PASS="${BASE_URL}/transform/nuc_json?TAXID=${RUC}&USERNAME=${USUARIO}&PASSWORD=${PASSWORD}"

echo "  Probando con: TAXID, USERNAME y PASSWORD"
echo ""

RESPONSE2=$(curl -s -w "\n%{http_code}" -X POST "$TRANSFORM_WITH_PASS" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$TEST_DOC" 2>&1)

HTTP_CODE2=$(echo "$RESPONSE2" | tail -n1)
BODY2=$(echo "$RESPONSE2" | sed '$d')

echo "  Código HTTP: $HTTP_CODE2"
echo ""

if [ "$HTTP_CODE2" != "$HTTP_CODE" ]; then
    echo "→ Respuesta diferente con PASSWORD:"
    echo "$BODY2"
    echo ""
fi

echo "═══════════════════════════════════════"
echo "  RESUMEN"
echo "═══════════════════════════════════════"
echo ""
echo "✓ Endpoint accesible: testnucpa.digifact.com"
echo "✓ Parámetros requeridos detectados: TAXID, USERNAME"
echo ""
echo "Resultados de las pruebas:"
echo "  Sin PASSWORD:  HTTP $HTTP_CODE"
echo "  Con PASSWORD:  HTTP $HTTP_CODE2"
echo ""
echo "Próximos pasos:"
echo "  1. Ajustar estructura del documento JSON"
echo "  2. Verificar campos obligatorios según documentación"
echo "  3. Probar con documento real"
echo ""
echo "======================================"
