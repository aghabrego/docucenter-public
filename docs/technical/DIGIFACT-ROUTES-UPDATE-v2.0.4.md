# Actualización Módulo PAC Digifact - Rutas v2.0.4

## Cambios Realizados

### 1. DigifactService.php (app/Services/)
- **PROD_BASE_URL**: `https://apinuc.digifact.com/api` → `https://nucpa.digifact.com/api`
- **TRANSFORM_ENDPOINT**: `/transform/nuc_json` → `/v2/transform/nuc`
  - Cambio de formato JSON a XML/NUC según doc. oficial
- **Detección de Ambiente**: Busca ahora `nucpa.digifact.com` (era `apinuc.digifact.com`)
- **Documentación**: Actualizada a v2.0.4 (02/10/2025)

### 2. DigifactXmlBuilder.php (app/Services/)
- **Comentario línea 131**: Actualizado de `apinuc.digifact.com` a `nucpa.digifact.com`

### 3. TestDigifactProductionToken.php (app/Console/Commands/)
- **Endpoint**: `https://apinuc.digifact.com/api/v2/login/get_token` 
  → `https://nucpa.digifact.com/api/login/get_token`

### 4. test-token-prod.php (docs/testing/)
- **Endpoint**: Actualizado a `https://nucpa.digifact.com/api/login/get_token`

## Endpoints Finales (v2.0.4)

| Operación | QA/Test | Producción |
|-----------|---------|-----------|
| **Token** | `testnucpa.digifact.com/api/login/get_token` | `nucpa.digifact.com/api/login/get_token` |
| **Certificar** | `testnucpa.digifact.com/api/v2/transform/nuc` | `nucpa.digifact.com/api/v2/transform/nuc` |

## Cambios de Formato
- **Test (JSON)**: ❌ Descontinuado (`/transform/nuc_json`)
- **Nuevo (XML)**: ✅ `/v2/transform/nuc` con schema NUC/DGI

## Token y Headers
```
Authorization: {token}  (sin "Bearer", envío directo)
Content-Type: application/xml
```

---

**Referencia**: Documentación Técnica API Digifact Panama v2.0.4 (02/10/2025)
**Commits**: 
- 169c9a99: docs: actualizar endpoints según v2.0.4
- 38b2a677: refactor: actualizar rutas en módulo PAC
