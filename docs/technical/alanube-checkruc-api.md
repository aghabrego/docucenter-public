# Alanube checkRuc API - Especificación

## Endpoint
```
GET /pan/v1/check-digit
```

## Parámetros Requeridos
- `ruc`: string - RUC a consultar
- `type`: integer - Tipo de contribuyente (1=Natural, 2=Jurídico)

## Implementación
```php
// AlanubeService::checkRucDigit()
$contributorType = $this->detectContributorType($ruc); // 1 o 2
$response = Http::get($url, [
    'ruc' => $ruc,
    'type' => $contributorType  // OBLIGATORIO
]);
```

## Detección Automática
- RUC formato persona natural (PE-, E-, N-, 1-13): type = 1
- RUC formato empresa: type = 2
- Helper: `PanamaRucHelper::detectContributorType($ruc)`

## Response
```json
{
  "success": true,
  "data": {...},
  "type": 1
}
```

**CRÍTICO**: El parámetro `type` es OBLIGATORIO según documentación Alanube.
