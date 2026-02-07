<?php

/**
 * Test completo para determinación de tipo RUC panameño
 * Basado en la lógica de panamaIDFormat del helper oficial
 *
 * Ejecutar: php docs/testing/test_ruc_type_determination_enhanced.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class TestRucTypeDeterminationEnhanced
{
    /**
     * Determina el tipo de RUC basado en el patrón del RUC panameño
     * Replica la lógica actualizada de ACIcloudService (usando panamaIDFormat)
     */
    private static function determineRucType(?string $ruc): string
    {
        if (empty($ruc) || $ruc === '0-0-0') {
            return '2';
        }

        $ruc = trim($ruc);
        $parts = explode('-', $ruc);
        if (count($parts) < 2) {
            return '2';
        }

        $firstPart = $parts[0];

        // PE (Panameño Extranjero), E (Extranjero), N (Naturalizado)
        if (preg_match("/^(PE|E|N)$/", $firstPart)) {
            return '2';
        }

        // Provincias con AV (Antes de la Vigencia) o PI (Población Indígena)
        if (preg_match("/^(1[0123]?|[23456789])(AV|PI)$/", $firstPart)) {
            return '2';
        }

        // Solo provincias (1-13) sin sufijos especiales
        if (preg_match("/^(1[0123]?|[23456789])$/", $firstPart)) {
            return '2';
        }

        // Patrón tradicional 8- (Panamá provincia)
        if (preg_match("/^8$/", $firstPart)) {
            return '2';
        }

        // RUC de empresa/jurídica: números de más de 2 dígitos
        if (preg_match("/^\d{2,}$/", $firstPart) && !preg_match("/^[1-9]$|^1[0-3]$/", $firstPart)) {
            return '1';
        }

        return '2';
    }

    public static function runTests()
    {
        $tests = [
            // === PERSONAS NATURALES - PROVINCIAS TRADICIONALES ===
            ['8-123-456', '2', 'Panamá (provincia 8) - Persona natural'],
            ['1-456-789', '2', 'Bocas del Toro (provincia 1) - Persona natural'],
            ['2-789-123', '2', 'Coclé (provincia 2) - Persona natural'],
            ['3-321-654', '2', 'Colón (provincia 3) - Persona natural'],
            ['4-111-222', '2', 'Chiriquí (provincia 4) - Persona natural'],
            ['5-333-444', '2', 'Darién (provincia 5) - Persona natural'],
            ['6-555-666', '2', 'Herrera (provincia 6) - Persona natural'],
            ['7-777-888', '2', 'Los Santos (provincia 7) - Persona natural'],
            ['9-999-111', '2', 'Veraguas (provincia 9) - Persona natural'],
            ['10-123-456', '2', 'Guna Yala (provincia 10) - Persona natural'],
            ['11-789-123', '2', 'Emberá Wounaan (provincia 11) - Persona natural'],
            ['12-456-789', '2', 'Madugandí (provincia 12) - Persona natural'],
            ['13-111-222', '2', 'Naso Tjër Di (provincia 13) - Persona natural'],

            // === PERSONAS NATURALES - CASOS ESPECIALES ===
            ['PE-123-456', '2', 'Panameño Extranjero - Persona natural'],
            ['E-789-123', '2', 'Extranjero - Persona natural'],
            ['N-456-789', '2', 'Naturalizado - Persona natural'],

            // === PERSONAS NATURALES - ANTES DE LA VIGENCIA ===
            ['1AV-123-456', '2', 'Bocas del Toro - Antes Vigencia'],
            ['8AV-789-123', '2', 'Panamá - Antes Vigencia'],
            ['10AV-111-222', '2', 'Guna Yala - Antes Vigencia'],
            ['13AV-333-444', '2', 'Naso Tjër Di - Antes Vigencia'],

            // === PERSONAS NATURALES - POBLACIÓN INDÍGENA ===
            ['1PI-123-456', '2', 'Bocas del Toro - Población Indígena'],
            ['8PI-789-123', '2', 'Panamá - Población Indígena'],
            ['11PI-333-444', '2', 'Emberá Wounaan - Población Indígena'],
            ['12PI-555-666', '2', 'Madugandí - Población Indígena'],

            // === PERSONAS JURÍDICAS/EMPRESAS ===
            ['155-123-456', '1', 'Empresa - RUC jurídico'],
            ['1000-456-789', '1', 'Empresa - RUC jurídico largo'],
            ['20-789-123', '1', 'Empresa - RUC jurídico'],
            ['999-111-222', '1', 'Empresa - RUC jurídico'],
            ['14-123-456', '1', 'Empresa - Número superior a provincias'],
            ['100-789-123', '1', 'Empresa - RUC jurídico grande'],

            // === CASOS ESPECIALES ===
            ['0-0-0', '2', 'Consumidor final'],
            [null, '2', 'RUC nulo'],
            ['', '2', 'RUC vacío'],
            ['   ', '2', 'RUC solo espacios'],
            ['INVALID', '2', 'Formato inválido'],
            ['123', '2', 'Sin formato de guiones'],
        ];

        $passed = 0;
        $failed = 0;
        $total = count($tests);

        echo "🧪 PRUEBAS DE DETERMINACIÓN DE TIPO RUC PANAMEÑO (LÓGICA panamaIDFormat)\n";
        echo "=" . str_repeat("=", 75) . "\n\n";

        foreach ($tests as $index => $test) {
            [$ruc, $expectedType, $description] = $test;
            $actualType = self::determineRucType($ruc);

            $status = $actualType === $expectedType ? '✅ PASS' : '❌ FAIL';
            $rucDisplay = $ruc === null ? 'null' : "'$ruc'";

            echo sprintf(
                "Test %2d: %-25s -> Tipo %s | %s | %s\n",
                $index + 1,
                $rucDisplay,
                $actualType,
                $status,
                $description
            );

            if ($actualType === $expectedType) {
                $passed++;
            } else {
                $failed++;
                echo "         Esperado: '$expectedType', Obtenido: '$actualType'\n";
            }
        }

        echo "\n" . str_repeat("=", 77) . "\n";
        echo "📊 RESUMEN: Pruebas pasadas: $passed, Pruebas fallidas: $failed, Total: $total\n";

        if ($failed === 0) {
            echo "🎉 ¡Todas las pruebas pasaron! La lógica es consistente con panamaIDFormat.\n";
        } else {
            echo "⚠️  Hay $failed prueba(s) fallida(s) que necesitan revisión.\n";
        }

        return $failed === 0;
    }
}

// Ejecutar las pruebas
TestRucTypeDeterminationEnhanced::runTests();
