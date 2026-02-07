```php
// Versión refactorizada sugerida
protected function shouldAutoEmitFe(): bool
{
    try {
        $connection = $this->getQuickBooksConnection();
        if (!$connection) {
            return true; // Default: emitir FE
        }
        
        $realmId = $this->extractRealmId($connection);
        if (!$realmId) {
            return true; // Default: emitir FE
        }
        
        return $this->getAutoEmitConfiguration($realmId);
        
    } catch (\Exception $e) {
        Log::warning("Job CreateSaleQuickBooks: Error verificando auto-emit FE", [
            'organization_id' => $this->organization->id,
            'error' => $e->getMessage()
        ]);
        return true; // Default: emitir FE en caso de error
    }
}

private function getQuickBooksConnection(): ?\App\Models\Connection
{
    $connection = \App\Models\Connection::where('organization_id', $this->organization->id)
        ->where('application', 'acicloud')
        ->first();
        
    if (!$connection || !$this->isQuickBooksConnection($connection)) {
        return null;
    }
    
    return $connection;
}

private function isQuickBooksConnection(\App\Models\Connection $connection): bool
{
    $settings = $connection->getSettingsAttribute();
    return isset($settings['Module']) && $settings['Module'] === 'quickbooks';
}

private function extractRealmId(\App\Models\Connection $connection): ?string
{
    $settings = $connection->getSettingsAttribute();
    
    if (isset($settings['Organizations']) && is_array($settings['Organizations'])) {
        $firstOrg = $settings['Organizations'][0] ?? null;
        if ($firstOrg) {
            return $firstOrg['RealmId'] ?? null;
        }
    }
    
    return $settings['IdCliente'] ?? ($settings['realmId'] ?? ($settings['RealmId'] ?? null));
}

private function getAutoEmitConfiguration(string $realmId): bool
{
    $config = \App\Models\QuickbooksConfiguration::getConfiguration($this->organization->id, $realmId);
    
    if (!$config) {
        Log::debug("Job CreateSaleQuickBooks: No hay configuración local", [
            'organization_id' => $this->organization->id,
            'realmId' => $realmId
        ]);
        return true; // Default: emitir FE
    }
    
    $localSettings = $config->getSettingsAttribute();
    $autoEmitFeEnabled = $localSettings['autoEmitFeEnabled'] ?? true;
    
    Log::info("Job CreateSaleQuickBooks: Configuración auto-emit FE obtenida", [
        'organization_id' => $this->organization->id,
        'realmId' => $realmId,
        'autoEmitFeEnabled' => $autoEmitFeEnabled,
        'lastConfiguredAt' => $localSettings['lastConfiguredAt'] ?? null,
        'lastConfiguredBy' => $localSettings['lastConfiguredBy'] ?? null
    ]);
    
    return $autoEmitFeEnabled;
}
```
