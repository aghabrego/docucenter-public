# Resumen Ejecutivo - Comandos NO Incluidos en Kernel

## Vista General

**Total de comandos analizados**: 91
**Comandos en Kernel (automatizados)**: 19 (21%)
**Comandos fuera del Kernel**: 72 (79%)

---

## Comandos por Categoría (NO incluidos en Kernel)

### 1. DIAGNÓSTICO Y DEBUG (32 comandos - 44%)

#### Análisis de Facturas (5 comandos)
```bash
php artisan app:diagnose-kart-invoice
php artisan app:trace-invoice-number-flow
php artisan app:debug-invoice-number-mapping
php artisan app:analyze-lightspeed-credit-note
php artisan app:validate-lightspeed-credit-note-fix
```

#### Debug de Integraciones (4 comandos)
```bash
php artisan app:debug-create-fast-job-vuelto
php artisan app:debug-the-factory-hka-request
php artisan app:test-the-factory-hka-payload
php artisan app:fix-acicloud-receptor-issues
```

#### Testing de APIs (6 comandos)
```bash
php artisan testing:test-alanube-connection
php artisan testing:test-acicloud-real-data
php artisan testing:test-acicloud-registration
php artisan testing:test-acicloud-service
php artisan testing:test-acicloud-api-with-emission
php artisan testing:test-aci-cloud-registration
```

#### Testing Kart/Meypar (17 comandos)
```bash
# Testing Kart
php artisan app:test-kart-real-data
php artisan app:test-kart-real-api
php artisan app:test-kart-to-zoho
php artisan testing:test-kart21-decimal-precision
php artisan testing:test-kart21-service

# Testing Meypar
php artisan testing:test-meypar-payments
php artisan testing:test-meypar-quantity-only
php artisan testing:test-meypar-real-flow
php artisan testing:meypar-test-complete
php artisan testing:debug-meypar-emission
php artisan testing:test-meypar-specific-data
php artisan testing:test-meypar-normalization
php artisan testing:test-meypar-api-normalization
php artisan testing:test-meypar-only-processing

# Testing Emisión
php artisan testing:test-lightspeed-pac-emission
php artisan testing:test-lightspeed-pac-emission-real
php artisan testing:test-lightspeed-credit-note
```

---

### 2. INTEGRACIONES EMPRESARIALES (8 comandos - 11%)

#### Zoho
```bash
php artisan app:test-zoho-connection
```

#### Intuit/QuickBooks
```bash
php artisan app:upload-sales-intuit
php artisan app:upload-sales-general-diary-intuit
php artisan app:intuit-sync-status
```

#### Kart
```bash
php artisan app:emit-kart-order
php artisan app:emit-kart-with-create-fast-job
php artisan app:emit-kart-with-cash-payment
php artisan app:force-kart-invoice-emission
```

---

###  3. SINCRONIZACIÓN POS (15 comandos - 21%)

#### Invupos
```bash
php artisan app:customer-invupos
php artisan app:category-invupos
php artisan app:sub-category-invupos
php artisan app:type-payment-invupos
php artisan app:product-invupos
php artisan app:purchase-category-invupos
php artisan app:provider-invupos
```

#### Lightspeed
```bash
php artisan app:typepayment-lightspeed
```

#### General POS
```bash
php artisan app:credit-notes
php artisan app:purchase-orders
php artisan app:sales-orders
php artisan app:item-menu
php artisan app:import-data
php artisan app:export-data
php artisan app:create-sales-order-summary
php artisan app:create-credit-notes-summary
```

---

### 4. CONFIGURACIÓN Y MANTENIMIENTO (5 comandos - 7%)

```bash
php artisan config:alter-column-increment
php artisan config:add-column-to-organizations-table
php artisan config:create-table-from-stub
php artisan config:remove-columns-gje-header-imp
php artisan config:remove-column-to-organizations-table
```

---

### 5. ANÁLISIS Y REPORTES (1 comando - 1%)

```bash
php artisan app:panama-daily-entry
```

---

## Comandos Candidatos para Automatización

### Alta Prioridad
```bash
# Monitoreo de conectividad
php artisan app:test-zoho-connection

# Procesamiento diario
php artisan app:panama-daily-entry

# Diagnóstico preventivo
php artisan app:diagnose-kart-invoice
```

### Media Prioridad
```bash
# Status de integraciones
php artisan app:intuit-sync-status

# Análisis de notas de crédito
php artisan app:analyze-lightspeed-credit-note
```

---

## Comandos de Solo Ejecución Manual

### Testing y Debug
- Todos los comandos en `testing:*`
- Comandos de debug específicos
- Comandos de análisis de problemas

### Configuración de BD
- Comandos `config:*` (modifican estructura)
- Requieren validación manual antes de ejecutar

### Integraciones Específicas
- Comandos de emisión Kart (requieren contexto específico)
- Comandos de sincronización manual POS

---

## Lista de Verificación para Automatización

Antes de agregar un comando al Kernel, verificar:

1. **¿Es seguro ejecutar automáticamente?**
2. **¿No requiere parámetros específicos?**
3. **¿Es idempotente (puede ejecutarse múltiples veces)?**
4. **¿No tiene efectos secundarios en testing?**
5. **¿Mejora la operación del sistema?**

---

## Referencias

- **Documentación completa**: [comandos-no-incluidos-kernel-por-operacion.md](./comandos-no-incluidos-kernel-por-operacion.md)
- **Comandos en Kernel**: [kernel-commands-verification-checklist.md](./kernel-commands-verification-checklist.md)
- **Análisis general**: [comando-reorganization-by-operation-type.md](./comando-reorganization-by-operation-type.md)

---

**Última actualización**: 31 de agosto de 2025
