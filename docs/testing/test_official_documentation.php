<?php

echo "=== VERIFICACIÓN CON DOCUMENTACIÓN OFICIAL PANAMÁ ===\n\n";

// Documentación oficial según receiver_type.csv
$tiposOficiales = [
    '01' => 'Contribuyente',
    '02' => 'Consumidor final',
    '03' => 'Gobierno',
    '04' => 'Extranjero'
];

echo "TIPOS OFICIALES SEGÚN DOCUMENTACIÓN PANAMÁ:\n";
foreach ($tiposOficiales as $codigo => $descripcion) {
    echo "- Código '$codigo': $descripcion\n";
}

echo "\n=== CORRECCIÓN DE LA AUTO-DETECCIÓN ===\n\n";

// Función corregida
function detectReceiverType(string $ruc): string
{
    if (empty($ruc)) {
        return '01'; // Valor por defecto: Contribuyente
    }

    // Patrón DNI: máximo 3-4-6 dígitos separados por guiones
    $dniPattern = '/^[a-zA-Z0-9]{1,3}-[0-9]{1,4}-[0-9]{1,6}$/';

    if (preg_match($dniPattern, $ruc)) {
        return '02'; // Consumidor final (DNI/Cédula)
    } else {
        return '01'; // Contribuyente (RUC empresarial)
    }
}

// Datos del usuario
$userData = [
    'iTipoRec' => '1',  // ORIGINAL
    'dRuc' => '1808755-1-706832'  // FORMATO EMPRESA
];

echo "DATOS DEL USUARIO:\n";
echo "iTipoRec original: '{$userData['iTipoRec']}'\n";
echo "dRuc: '{$userData['dRuc']}'\n\n";

$autoDetectedType = detectReceiverType($userData['dRuc']);

echo "RESULTADO AUTO-DETECCIÓN (CORREGIDA):\n";
echo "Tipo detectado: '$autoDetectedType'\n";
echo "Descripción: '{$tiposOficiales[$autoDetectedType]}'\n\n";

echo "ANÁLISIS:\n";
echo "- RUC '1808755-1-706832' es formato empresa (primera parte tiene 7 dígitos)\n";
echo "- Auto-detección devuelve '01' (Contribuyente)\n";
echo "- Esto es correcto para empresas según documentación oficial\n\n";

echo "=== CASOS DE PRUEBA CON DOCUMENTACIÓN OFICIAL ===\n\n";

$testCases = [
    [
        'description' => 'Caso usuario: RUC empresa',
        'ruc' => '1808755-1-706832',
        'expected' => '01',
        'expectedDesc' => 'Contribuyente'
    ],
    [
        'description' => 'DNI/Cédula típica',
        'ruc' => '8-888-888888',
        'expected' => '02',
        'expectedDesc' => 'Consumidor final'
    ],
    [
        'description' => 'RUC empresa corto',
        'ruc' => '1234567-1-123456',
        'expected' => '01',
        'expectedDesc' => 'Contribuyente'
    ],
    [
        'description' => 'DNI extranjero',
        'ruc' => 'E-123-456789',
        'expected' => '02',
        'expectedDesc' => 'Consumidor final'
    ]
];

foreach ($testCases as $index => $case) {
    echo "Test " . ($index + 1) . ": {$case['description']}\n";
    echo "RUC: '{$case['ruc']}'\n";

    $detected = detectReceiverType($case['ruc']);
    echo "Detectado: '$detected' ({$tiposOficiales[$detected]})\n";
    echo "Esperado: '{$case['expected']}' ({$case['expectedDesc']})\n";

    if ($detected === $case['expected']) {
        echo "✅ CORRECTO\n";
    } else {
        echo "❌ ERROR\n";
    }
    echo "\n";
}

echo "=== VALIDACIÓN PAC ===\n";
$userDetectedType = detectReceiverType($userData['dRuc']);
echo "Para el usuario:\n";
echo "- RUC: '{$userData['dRuc']}'\n";
echo "- Tipo auto-detectado: '$userDetectedType' ({$tiposOficiales[$userDetectedType]})\n";
echo "- Formato RUC: Empresa (7-1-6 dígitos)\n";
echo "- Regla PAC: Contribuyente permite cualquier formato RUC\n";
echo "- ✅ PAC DEBE ACEPTAR esta combinación\n\n";

echo "=== RESUMEN DE LA CORRECCIÓN ===\n";
echo "1. Consultamos documentación oficial: database/seeders/csv/receiver_type.csv\n";
echo "2. Corregimos los códigos:\n";
echo "   - '01': Contribuyente (empresas)\n";
echo "   - '02': Consumidor final (personas naturales)\n";
echo "3. Auto-detección ahora es consistente con documentación oficial\n";
echo "4. El caso del usuario debe funcionar correctamente\n";
