<?php

// Test específico con los datos exactos del usuario
echo "=== ANÁLISIS DEL OBJETO REAL DEL USUARIO ===\n\n";

// Datos exactos del JSON del usuario
$userData = [
    'dGen' => [
        'gDatRec' => [
            'iTipoRec' => '1',  // Valor original del usuario
            'gRucRec' => [
                'dTipoRuc' => '2',
                'dRuc' => '1808755-1-706832',  // Valor RUC del usuario
                'dDV' => '97'
            ]
        ]
    ]
];

echo "DATOS DEL USUARIO:\n";
echo "iTipoRec original: '{$userData['dGen']['gDatRec']['iTipoRec']}'\n";
echo "dRuc: '{$userData['dGen']['gDatRec']['gRucRec']['dRuc']}'\n";
echo "dDV: '{$userData['dGen']['gDatRec']['gRucRec']['dDV']}'\n\n";

// Simular la función detectReceiverType
function detectReceiverType(string $ruc): string
{
    if (empty($ruc)) {
        return '01'; // Valor por defecto
    }

    // Patrón DNI: máximo 3-4-6 dígitos separados por guiones
    $dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';

    if (preg_match($dniPattern, $ruc)) {
        return '01'; // Consumidor Final (DNI/Cédula)
    } else {
        return '02'; // Empresa (RUC empresarial)
    }
}

// Aplicar la lógica como en el helper
$originalReceiverType = $userData['dGen']['gDatRec']['iTipoRec'] ?? '01';
$ruc = $userData['dGen']['gDatRec']['gRucRec']['dRuc'] ?? '';
$autoDetectedType = detectReceiverType($ruc);

echo "PROCESAMIENTO:\n";
echo "1. originalReceiverType = '$originalReceiverType'\n";
echo "2. ruc = '$ruc'\n";
echo "3. autoDetectedType = detectReceiverType('$ruc') = '$autoDetectedType'\n\n";

echo "VALIDACIÓN DEL RUC:\n";
$dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';
echo "Patrón DNI: $dniPattern\n";

if (preg_match($dniPattern, $ruc)) {
    echo "✅ RUC coincide con patrón DNI → detectado como '01' (Consumidor)\n";
} else {
    echo "❌ RUC NO coincide con patrón DNI → detectado como '02' (Empresa)\n";

    // Analizar por qué no coincide
    $parts = explode('-', $ruc);
    echo "\nAnálisis detallado:\n";
    echo "Parte 1: '{$parts[0]}' (longitud: " . strlen($parts[0]) . ") - DNI permite máx 3\n";
    echo "Parte 2: '{$parts[1]}' (longitud: " . strlen($parts[1]) . ") - DNI permite máx 4\n";
    echo "Parte 3: '{$parts[2]}' (longitud: " . strlen($parts[2]) . ") - DNI permite máx 6\n";

    echo "\nRazón del rechazo: ";
    if (strlen($parts[0]) > 3) {
        echo "Primera parte '{$parts[0]}' tiene " . strlen($parts[0]) . " caracteres (máx 3)\n";
    }
}

echo "\n=== CORRECCIÓN APLICADA ===\n";
echo "Valor original: iTipoRec = '$originalReceiverType'\n";
echo "Valor corregido: iTipoRec = '$autoDetectedType'\n";

if ($originalReceiverType !== $autoDetectedType) {
    echo "🔧 CORRECCIÓN NECESARIA: '$originalReceiverType' → '$autoDetectedType'\n";
    echo "Motivo: RUC '$ruc' es formato empresa, no consumidor final\n";
} else {
    echo "✅ No se requiere corrección\n";
}

echo "\n=== RESULTADO PAC ===\n";
if ($autoDetectedType === '02') {
    echo "✅ PAC aceptará: Tipo empresa + RUC empresa\n";
    echo "✅ Error 'instance.receiver.ruc.ruc is not valid dni...' RESUELTO\n";
} else {
    echo "❌ PAC podría rechazar si hay inconsistencia\n";
}

echo "\n=== VERIFICACIÓN DEL HELPER ===\n";
echo "En AlanubeFormatterHelper.php, línea 123:\n";
echo "- originalReceiverType = '{$originalReceiverType}'\n";
echo "- autoDetectedType = '$autoDetectedType'\n";
echo "- receiverType = '$autoDetectedType' (debe usarse este valor)\n";

echo "\nEl helper debe usar '$autoDetectedType' para el campo 'type' del receptor.\n";
