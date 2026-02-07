<?php

echo "=== ANÁLISIS DEL ERROR PAC: DNI vs RUC ===\n\n";

// Datos exactos del usuario que causan el error
$userData = [
    'iTipoRec' => '1',  // CONSUMIDOR FINAL
    'dRuc' => '1808755-1-706832',  // FORMATO EMPRESA (problemático)
    'dDV' => '97'
];

echo "Datos del usuario:\n";
echo "iTipoRec: '{$userData['iTipoRec']}' (1 = Consumidor Final)\n";
echo "dRuc: '{$userData['dRuc']}'\n";
echo "dDV: '{$userData['dDV']}'\n\n";

// Error PAC
echo "Error PAC:\n";
echo "'instance.receiver.ruc.ruc is not valid dni or is tax payer when instance.receiver.type is final consumer'\n\n";

// Análisis del patrón DNI
$dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';
echo "Patrón DNI esperado: $dniPattern\n";

// Verificar si el RUC actual coincide con patrón DNI
if (preg_match($dniPattern, $userData['dRuc'])) {
    echo "✅ RUC actual coincide con patrón DNI\n";
} else {
    echo "❌ RUC actual NO coincide con patrón DNI\n";

    // Analizar por qué falla
    $parts = explode('-', $userData['dRuc']);
    echo "\nAnálisis de partes del RUC:\n";
    echo "Parte 1: '{$parts[0]}' (longitud: " . strlen($parts[0]) . ") - DNI permite: 1-3 caracteres\n";
    echo "Parte 2: '{$parts[1]}' (longitud: " . strlen($parts[1]) . ") - DNI permite: 1-4 dígitos\n";
    echo "Parte 3: '{$parts[2]}' (longitud: " . strlen($parts[2]) . ") - DNI permite: 1-6 dígitos\n\n";

    // Verificar cada parte
    echo "Validación por partes:\n";

    // Parte 1
    if (strlen($parts[0]) >= 1 && strlen($parts[0]) <= 3 && preg_match('/^[a-zA-Z0-9]+$/', $parts[0])) {
        echo "✅ Parte 1 válida\n";
    } else {
        echo "❌ Parte 1 inválida - Demasiado larga (" . strlen($parts[0]) . " chars, máximo 3)\n";
    }

    // Parte 2
    if (strlen($parts[1]) >= 1 && strlen($parts[1]) <= 4 && preg_match('/^[0-9]+$/', $parts[1])) {
        echo "✅ Parte 2 válida\n";
    } else {
        echo "❌ Parte 2 inválida\n";
    }

    // Parte 3
    if (strlen($parts[2]) >= 1 && strlen($parts[2]) <= 6 && preg_match('/^[0-9]+$/', $parts[2])) {
        echo "✅ Parte 3 válida\n";
    } else {
        echo "❌ Parte 3 inválida\n";
    }
}

echo "\n=== PROBLEMA RAÍZ ===\n";
echo "El problema es que tenemos:\n";
echo "- iTipoRec = '1' (Consumidor Final)\n";
echo "- Pero dRuc = '1808755-1-706832' (formato de empresa)\n\n";

echo "Reglas PAC:\n";
echo "- Si iTipoRec = '1' → debe ser DNI con patrón: X-XXXX-XXXXXX (máx 3-4-6 dígitos)\n";
echo "- Si iTipoRec = '2' → puede ser RUC empresarial (sin restricción de longitud)\n\n";

echo "=== SOLUCIONES POSIBLES ===\n\n";

// Solución 1: Cambiar tipo de receptor
echo "1. CAMBIAR TIPO DE RECEPTOR:\n";
echo "   Si el RUC es formato empresa → cambiar iTipoRec a '2'\n";
echo "   Ventaja: Mantiene el RUC original\n";
echo "   Desventaja: Cambia la semántica del receptor\n\n";

// Solución 2: Convertir RUC a DNI
echo "2. CONVERTIR RUC A DNI:\n";
$convertedDni = substr($userData['dRuc'], -11); // Últimos 11 caracteres
if (strlen($convertedDni) > 11) {
    $convertedDni = substr($convertedDni, 0, 11);
}
echo "   RUC original: {$userData['dRuc']}\n";
echo "   DNI convertido: $convertedDni\n";

// Verificar si la conversión funciona
if (preg_match($dniPattern, $convertedDni)) {
    echo "   ✅ Conversión válida para DNI\n";
} else {
    echo "   ❌ Conversión inválida para DNI\n";
}

// Solución 3: DNI genérico
echo "\n3. DNI GENÉRICO:\n";
$genericDni = "8-888-888888";
echo "   DNI genérico: $genericDni\n";
if (preg_match($dniPattern, $genericDni)) {
    echo "   ✅ DNI genérico válido\n";
} else {
    echo "   ❌ DNI genérico inválido\n";
}

echo "\n=== RECOMENDACIÓN ===\n";
echo "La mejor solución es AUTO-DETECTAR el tipo de receptor:\n";
echo "1. Si dRuc coincide con patrón DNI → iTipoRec = '1'\n";
echo "2. Si dRuc es formato empresa → iTipoRec = '2'\n";
echo "3. Esto asegura consistencia entre tipo y formato\n\n";

// Implementar la detección
function detectReceiverType($ruc) {
    $dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';
    return preg_match($dniPattern, $ruc) ? '1' : '2';
}

$detectedType = detectReceiverType($userData['dRuc']);
echo "DETECCIÓN AUTOMÁTICA:\n";
echo "RUC: {$userData['dRuc']}\n";
echo "Tipo detectado: $detectedType (" . ($detectedType == '1' ? 'Consumidor Final' : 'Empresa') . ")\n";
echo "Tipo actual: {$userData['iTipoRec']}\n";

if ($detectedType !== $userData['iTipoRec']) {
    echo "❌ INCONSISTENCIA DETECTADA - Requiere corrección\n";
} else {
    echo "✅ Consistencia correcta\n";
}

echo "\n=== IMPLEMENTACIÓN REQUERIDA ===\n";
echo "Modificar AlanubeFormatterHelper.php para:\n";
echo "1. Detectar tipo de receptor automáticamente\n";
echo "2. Ajustar iTipoRec según formato de RUC\n";
echo "3. Aplicar formato correcto (DNI vs RUC)\n";
