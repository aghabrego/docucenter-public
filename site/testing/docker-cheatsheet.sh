#!/bin/bash

# Cheatsheet para Docker y Testing - DocuCenter
# Uso: bash docs/testing/docker-cheatsheet.sh [comando]

COLOR_GREEN='\033[0;32m'
COLOR_BLUE='\033[0;34m'
COLOR_YELLOW='\033[1;33m'
COLOR_NC='\033[0m' # No Color

echo -e "${COLOR_BLUE}=== DocuCenter - Docker Cheatsheet ===${COLOR_NC}"
echo ""

# Función para encontrar el contenedor correcto
find_container() {
    echo -e "${COLOR_YELLOW}Buscando contenedores de DocuCenter...${COLOR_NC}"

    # Mostrar todos los contenedores relacionados
    docker ps --format "table {{.ID}}\t{{.Names}}\t{{.Status}}" | grep -E "docucenter|CONTAINER"

    echo ""

    # Intentar encontrar el contenedor de la app
    APP_CONTAINER=$(docker ps --filter "name=docucenter" --filter "ancestor=*app*" --format "{{.Names}}" | head -1)

    if [ -z "$APP_CONTAINER" ]; then
        # Buscar cualquier contenedor con docucenter en el nombre
        APP_CONTAINER=$(docker ps --filter "name=docucenter" --format "{{.Names}}" | head -1)
    fi

    if [ -n "$APP_CONTAINER" ]; then
        echo -e "${COLOR_GREEN}✓ Contenedor encontrado: $APP_CONTAINER${COLOR_NC}"
        export DOCUCENTER_CONTAINER="$APP_CONTAINER"
    else
        echo -e "${COLOR_YELLOW}⚠ No se encontró contenedor activo${COLOR_NC}"
        echo "Intentar: docker-compose up -d"
        export DOCUCENTER_CONTAINER=""
    fi
}

# Comandos útiles
case "${1:-help}" in
    find|container)
        find_container
        ;;

    logs)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_GREEN}Mostrando logs de Laravel...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" tail -f storage/logs/laravel.log
        fi
        ;;

    logs-tax)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_GREEN}Filtrando logs de cálculo de impuestos...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" tail -f storage/logs/laravel.log | grep -A 10 "QuickBooks Line Tax Calculation"
        fi
        ;;

    tinker)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_GREEN}Abriendo Laravel Tinker...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" php artisan tinker
        fi
        ;;

    bash)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_GREEN}Abriendo shell en contenedor...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" bash
        fi
        ;;

    test-prueba01)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_GREEN}Ejecutando test PRUEBA01...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" php docs/testing/test-qb-multi-tax-prueba01.php
        fi
        ;;

    db-check)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_YELLOW}Introduce el nombre de la base de datos de la organización:${COLOR_NC}"
            read -r DB_NAME
            echo -e "${COLOR_GREEN}Consultando factura PRUEBA01...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" php artisan tinker --execute="
                DB::connection()->useDatabase('$DB_NAME');
                print_r(DB::select('SELECT Sequential, Description, Sub_Total, Itbms, Net_line FROM Sales_Detail_Imp WHERE InvoiceNumber = \"PRUEBA01\"'));
            "
        fi
        ;;

    queue-work)
        find_container
        if [ -n "$DOCUCENTER_CONTAINER" ]; then
            echo -e "${COLOR_GREEN}Procesando colas de Redis...${COLOR_NC}"
            docker exec -it "$DOCUCENTER_CONTAINER" php artisan queue:work --tries=3
        fi
        ;;

    help|*)
        echo "Uso: bash docs/testing/docker-cheatsheet.sh [comando]"
        echo ""
        echo "Comandos disponibles:"
        echo "  find          - Encontrar contenedor de DocuCenter"
        echo "  logs          - Ver logs de Laravel en tiempo real"
        echo "  logs-tax      - Ver logs filtrados de cálculo de impuestos"
        echo "  tinker        - Abrir Laravel Tinker"
        echo "  bash          - Abrir shell en contenedor"
        echo "  test-prueba01 - Ejecutar test de PRUEBA01"
        echo "  db-check      - Consultar factura PRUEBA01 en BD"
        echo "  queue-work    - Procesar colas de Redis"
        echo ""
        echo "Ejemplos:"
        echo "  bash docs/testing/docker-cheatsheet.sh find"
        echo "  bash docs/testing/docker-cheatsheet.sh logs-tax"
        echo "  bash docs/testing/docker-cheatsheet.sh tinker"
        echo ""

        # Mostrar contenedores actuales
        find_container
        ;;
esac
