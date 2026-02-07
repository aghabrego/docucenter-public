#!/usr/bin/env php
<?php

/**
 * Script para limpiar configuraciones de webhook duplicadas en Firestore
 * Ejecutar desde DENTRO del contenedor: php docs/testing/quickbooks-webhook-cleanup-duplicate-internal.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$projectId = 'zoho-books-edocs-integracion';

echo "==================================================\n";
echo "🧹 LIMPIEZA DE CONFIGURACIONES WEBHOOK DUPLICADAS\n";
echo "==================================================\n\n";

try {
    echo "📊 Conectando a Firestore...\n\n";

    $firestore = new FirestoreClient([
        'projectId' => $projectId,
    ]);

    echo "🔍 Buscando configuraciones duplicadas por RealmId...\n\n";

    // Obtener todas las configuraciones de webhook
    $collection = $firestore->collection('quickbooks_webhook_processing_config');
    $documents = $collection->documents();

    $configsByRealm = [];

    foreach ($documents as $document) {
        if ($document->exists()) {
            $data = $document->data();
            $docId = $document->id();
            $realmId = $data['RealmId'] ?? null;
            $orgId = $data['OrganizationId'] ?? null;

            if ($realmId && $orgId) {
                if (!isset($configsByRealm[$realmId])) {
                    $configsByRealm[$realmId] = [];
                }
                $configsByRealm[$realmId][] = [
                    'docId' => $docId,
                    'organizationId' => $orgId,
                    'isNumeric' => is_numeric($orgId),
                    'createdAt' => $data['CreatedAt'] ?? 'N/A',
                    'updatedAt' => $data['UpdatedAt'] ?? 'N/A',
                    'enabled' => $data['DocucenterEnabled'] ?? false,
                ];
            }
        }
    }

    $duplicatesFound = 0;
    $toDelete = [];

    foreach ($configsByRealm as $realmId => $configs) {
        if (count($configs) > 1) {
            $duplicatesFound++;
            echo "\n⚠️  RealmId: {$realmId} tiene " . count($configs) . " configuraciones:\n";
            echo "══════════════════════════════════════════════════════════════\n";

            $numericConfigs = [];
            $stringConfigs = [];

            foreach ($configs as $idx => $config) {
                $num = $idx + 1;
                $type = $config['isNumeric'] ? '❌ NUMÉRICO (INCORRECTO)' : '✅ STRING ID (CORRECTO)';
                $status = $config['enabled'] ? '✅ Enabled' : '❌ Disabled';

                echo "  Config #{$num}:\n";
                echo "    Doc ID: {$config['docId']}\n";
                echo "    OrganizationId: {$config['organizationId']} - {$type}\n";
                echo "    Created: {$config['createdAt']}\n";
                echo "    Updated: {$config['updatedAt']}\n";
                echo "    Estado: {$status}\n";
                echo "\n";

                if ($config['isNumeric']) {
                    $numericConfigs[] = $config;
                } else {
                    $stringConfigs[] = $config;
                }
            }

            // Si hay configuración con ID numérico y con string ID, marcar la numérica para eliminar
            if (!empty($numericConfigs) && !empty($stringConfigs)) {
                echo "  💡 RECOMENDACIÓN: Eliminar configuración(es) con ID numérico\n";
                foreach ($numericConfigs as $config) {
                    $toDelete[] = [
                        'docId' => $config['docId'],
                        'realmId' => $realmId,
                        'organizationId' => $config['organizationId'],
                    ];
                }
            } else if (!empty($numericConfigs) && empty($stringConfigs)) {
                echo "  ⚠️  ATENCIÓN: Solo hay configuraciones con ID numérico\n";
                echo "  ⚠️  Necesitas reconfigurar el webhook desde la interfaz\n";
            }

            echo "══════════════════════════════════════════════════════════════\n";
        }
    }

    echo "\n\n📊 RESUMEN:\n";
    echo "══════════════════════════════════════════════════════════════\n";
    echo "Total de RealmIds con duplicados: {$duplicatesFound}\n";
    echo "Configuraciones marcadas para eliminar: " . count($toDelete) . "\n";

    if (!empty($toDelete)) {
        echo "\n🗑️  CONFIGURACIONES A ELIMINAR:\n";
        echo "══════════════════════════════════════════════════════════════\n";
        foreach ($toDelete as $item) {
            echo "  • Doc ID: {$item['docId']}\n";
            echo "    RealmId: {$item['realmId']}\n";
            echo "    OrganizationId: {$item['organizationId']}\n\n";
        }

        echo "\n⚠️  ¿Deseas eliminar estas configuraciones? (s/n): ";
        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        $confirm = trim($line);
        fclose($handle);

        if (strtolower($confirm) === 's' || strtolower($confirm) === 'y') {
            echo "\n🗑️  Eliminando configuraciones...\n";
            foreach ($toDelete as $item) {
                try {
                    $collection->document($item['docId'])->delete();
                    echo "  ✅ Eliminado: {$item['docId']}\n";
                } catch (Exception $e) {
                    echo "  ❌ Error eliminando {$item['docId']}: " . $e->getMessage() . "\n";
                }
            }
            echo "\n✅ Limpieza completada\n";
        } else {
            echo "\n❌ Operación cancelada\n";
        }
    } else {
        echo "\n✅ No hay configuraciones duplicadas para eliminar\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "==================================================\n";
echo "✅ Análisis completado\n";
echo "==================================================\n";
