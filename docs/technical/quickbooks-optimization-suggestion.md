```php
// Método optimizado sugerido para QuickbooksConfiguration
public static function getAutoEmitConfigForOrganization(int $organizationId): ?bool
{
    $result = DB::table('connections')
        ->join('quickbooks_configurations', function($join) {
            $join->on('connections.organization_id', '=', 'quickbooks_configurations.organization_id')
                 ->whereRaw("JSON_EXTRACT(connections.settings, '$.Organizations[0].RealmId') = quickbooks_configurations.realm_id");
        })
        ->where('connections.organization_id', $organizationId)
        ->where('connections.application', 'acicloud')
        ->whereRaw("JSON_EXTRACT(connections.settings, '$.Module') = 'quickbooks'")
        ->select('quickbooks_configurations.settings')
        ->first();
    
    if (!$result) {
        return null; // No hay configuración
    }
    
    $settings = unserialize($result->settings);
    return $settings['autoEmitFeEnabled'] ?? true;
}
```
