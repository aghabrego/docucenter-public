# WORKAROUND - Estructura exportation siempre presente

## 🚨 PROBLEMA IDENTIFICADO

**Error**: `"instance requires property exportation"`

**Diagnóstico**: Mediante los logs de debug se confirmó que:
- ✅ País detectado correctamente: `PA` (Panamá)
- ✅ Tipo documento correcto: `01` (operación interna)
- ✅ Lógica condicional funcionando: `should_include_exportation: NO`
- ❌ **PAC Alanube sigue requiriendo la propiedad aunque no debe incluirse**

## 🔧 SOLUCIÓN IMPLEMENTADA

### WORKAROUND: Incluir estructura exportation siempre

El PAC Alanube tiene validaciones adicionales no documentadas que requieren que la estructura `exportation` esté **siempre presente**, incluso para documentos que no la necesitan según las normativas DGI.

### Lógica Implementada

```php
// Para tipos que requieren exportation con datos (02, 03)
if ($shouldIncludeExportation) {
    $gDatRec['exportation'] = self::removeNullValues([
        'incoterm' => $data['gFExp']['cCondEntr'] ?? null,
        'currency' => $data['gFExp']['cMoneda'] ?? null,
        // ... datos reales
    ]);
} else {
    // Para tipos que NO requieren exportation (01, 04-09)
    $gDatRec['exportation'] = [
        'incoterm' => '',
        'currency' => '',
        'otherCurrency' => '',
        'exchangeRate' => null,
        'amount' => null,
        'port' => '',
    ];
}
```

## 📊 COMPORTAMIENTO POR TIPO

### 🇵🇦 Panamá

| Tipo | Descripción | Estructura exportation | Contenido |
|------|-------------|----------------------|-----------|
| 01   | Operación interna | ✅ **Presente** | Campos vacíos |
| 02   | Importación | ✅ **Presente** | Datos reales gFExp |
| 03   | Exportación | ✅ **Presente** | Datos reales gFExp |
| 04-09| Otros | ✅ **Presente** | Campos vacíos |

### 🇩🇴 República Dominicana

| Tipo | Descripción | Estructura exportation | Contenido |
|------|-------------|----------------------|-----------|
| 1    | Factura estándar | ✅ **Presente** | Campos vacíos |
| 2    | Importación | ✅ **Presente** | Datos reales gFExp |
| 3    | Exportación | ✅ **Presente** | Datos reales gFExp |

## ✅ ARCHIVOS MODIFICADOS

1. **`app/Helpers/AlanubeFormatterHelper.php`**
   - Método `formatForPanama()`: Líneas ~189-208
   - Método `formatForDominicana()`: Líneas ~608-627
   - Agregado: Estructura exportation siempre presente

2. **Logs de debug mantienen información detallada**:
   - País detectado
   - Tipo de documento
   - Si debe incluir datos reales o estructura vacía

## 🎯 IMPACTO

### ✅ RESUELTO
- Error PAC "instance requires property exportation" eliminado
- Operaciones internas con receptores extranjeros procesan correctamente
- Exportaciones e importaciones mantienen funcionalidad completa

### ⚠️ CONSIDERACIONES
- **Workaround temporal**: Basado en comportamiento no documentado del PAC
- **Estructura adicional**: Campos exportation vacíos para tipos que no los requieren
- **Sin impacto funcional**: Los campos vacíos no afectan el procesamiento fiscal

## 📋 VALIDACIÓN

### Casos de prueba confirmados:
1. **Factura tipo 01 + receptor extranjero**: ✅ Procesa sin error
2. **Factura tipo 03 + datos exportación**: ✅ Incluye datos reales
3. **Factura tipo 02 + datos importación**: ✅ Incluye datos reales
4. **Notas de crédito/débito**: ✅ Estructura vacía incluida

## 🔍 LOGS DE DEBUG DISPONIBLES

Para monitorear el comportamiento:
```bash
tail -f storage/logs/laravel.log | grep "AlanubeFormatterHelper"
```

**Información incluida en logs**:
- País detectado (PA/DO)
- Tipo de documento
- Si incluye datos reales o estructura vacía
- Datos gFExp cuando están disponibles

---

**Fecha**: 2024-12-19  
**Estado**: ✅ **IMPLEMENTADO Y VALIDADO**  
**Tipo**: Workaround para limitación PAC Alanube  
**Impacto**: Crítico - Resuelve bloqueo en facturación con receptores extranjeros
