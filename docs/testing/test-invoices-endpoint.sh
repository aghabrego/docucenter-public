#!/bin/bash

###############################################################################
# Script de Prueba Rápida: Endpoint /invoices de PlusMovil
###############################################################################

set -e

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Leer token del archivo
if [ ! -f "docs/testing/.plusmovil-token-QA.txt" ]; then
    echo -e "${RED}❌ Token no encontrado. Ejecuta primero get-plusmovil-token.sh${NC}"
    exit 1
fi

# Extraer token limpiando espacios y saltos de línea
ACCESS_TOKEN=$(grep "ACCESS_TOKEN=" docs/testing/.plusmovil-token-QA.txt | cut -d'=' -f2- | tr -d ' \n\r\t')

echo -e "${BLUE}Token extraído (longitud: ${#ACCESS_TOKEN})${NC}"
echo ""

# Base URL
BASE_URL="https://kg0zs65dq0.execute-api.us-east-1.amazonaws.com/qa"

###############################################################################
# Probar diferentes endpoints
###############################################################################

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}PROBANDO ENDPOINT: /invoices${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" "${BASE_URL}/invoices?limit=5" \
    -H "Authorization: Bearer ${ACCESS_TOKEN}" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "HTTP Status: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ Respuesta exitosa${NC}"
    echo ""
    echo "$BODY" | jq '.'
else
    echo -e "${RED}❌ Error${NC}"
    echo ""
    echo "$BODY"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}PROBANDO ENDPOINT: /invoices/stats${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" "${BASE_URL}/invoices/stats" \
    -H "Authorization: Bearer ${ACCESS_TOKEN}" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "HTTP Status: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ Respuesta exitosa${NC}"
    echo ""
    echo "$BODY" | jq '.'
else
    echo -e "${RED}❌ Error${NC}"
    echo ""
    echo "$BODY"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}PROBANDO ENDPOINT: /health (si existe)${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" "${BASE_URL}/health" \
    -H "Authorization: Bearer ${ACCESS_TOKEN}" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "HTTP Status: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ Respuesta exitosa${NC}"
    echo ""
    echo "$BODY" | jq '.'
else
    echo -e "${RED}ℹ️  Endpoint no disponible o requiere autenticación específica${NC}"
    echo ""
    echo "$BODY"
fi

echo ""
echo -e "${GREEN}Pruebas completadas${NC}"
