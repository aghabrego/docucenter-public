# Auditoría de Configuración de Jobs

**Fecha**: 2026-02-14  
**Generado por**: Sistema de análisis automático  
**Total de jobs analizados**: 71

---

## Resumen Ejecutivo

De los 71 jobs analizados en el proyecto DocuCenter:
-  **1 job (1.4%)** tiene configuración completa de límites
-  **70 jobs (98.6%)** necesitan mejoras de configuración
-  **2 jobs (2.8%)** tienen bucles `while(true)` potencialmente peligrosos

## Métricas Detalladas

| Configuración | Jobs Sin | % |
|---------------|----------|---|
| `public $tries` | 43 | 60.6% |
| `public $timeout` | 49 | 69.0% |
| `public $maxExceptions` | 67 | 94.4% |

---

##  Prioridad CRÍTICA: Jobs con `while(true)`

Estos jobs tienen bucles infinitos que pueden causar problemas de rendimiento severos:

### 1. app/Jobs/Booqable/CustomerJob.php
- **Riesgo**: Alto
- **Problema**: Bucle `while(true)` sin límite de páginas
- **Acción**: Convertir a `while($page <= $maxPages)` + agregar configuración de límites

### 2. app/Jobs/CleanOldTransactionsJob.php
- **Riesgo**: Alto
- **Problema**: Bucle `while(true)` sin límite
- **Acción**: Implementar límite de iteraciones + agregar timeouts

---

##  Jobs Sin `$tries` (43 jobs)

Sin límite de reintentos, estos jobs pueden reintentar indefinidamente:

<details>
<summary>Ver lista completa (43 jobs)</summary>

- app/Jobs/CreateSaleAlanubeDomJob.php
- app/Jobs/CreateGovernmentalInvoiceAlanubeDomJob.php
- app/Jobs/CreateSaleMeyparJob.php
- app/Jobs/CleanOldArchivesJob.php
- app/Jobs/Invupos/PurchaseOrdersJob.php
- app/Jobs/Invupos/SubCategoryJob.php
- app/Jobs/Invupos/CustomerJob.php
- app/Jobs/Invupos/CategoryJob.php
- app/Jobs/Invupos/UploadSalesIntuitJob.php
- app/Jobs/Invupos/PurchaseCategoryJob.php
- app/Jobs/Invupos/SalesOrdersJob.php
- app/Jobs/Invupos/TypePaymentJob.php
- app/Jobs/Invupos/CreditNotesJob.php
- app/Jobs/Invupos/ProductJob.php
- app/Jobs/Invupos/ItemMenuJob.php
- app/Jobs/Invupos/UploadSalesGeneralDiaryIntuitJob.php
- app/Jobs/Invupos/CreateAccessTokenJob.php
- app/Jobs/Invupos/ProviderJob.php
- app/Jobs/ImportXmlJob.php
- app/Jobs/CorrectElectronicInvoicesWithErrorsJob.php
- app/Jobs/IssueMassInvoicesJob.php
- app/Jobs/SqlServer/STInvoiceJob.php
- app/Jobs/SqlServer/STCostOfGoodsOfCategoryJob.php
- app/Jobs/SqlServer/STVendorsJob.php
- app/Jobs/SqlServer/STCostOfGoodsJob.php
- app/Jobs/SqlServer/STCreateSummaryJob.php
- app/Jobs/TypepaymentlightspeedJob.php
- app/Jobs/Ezee/SalesJob.php
- app/Jobs/ImportZipXmlInvoicesJob.php
- app/Jobs/CleanFailedJobsJob.php
- app/Jobs/Booqable/CustomerJob.php
- app/Jobs/Booqable/SalesOrdersV4Job.php
- app/Jobs/Booqable/SalesOrdersJob.php
- app/Jobs/Booqable/ProductJob.php
- app/Jobs/CleanOldTransactionsJob.php
- app/Jobs/Others/PanamaPaymentsMadeJob.php
- app/Jobs/Others/PanamaDailyEntryJob.php
- app/Jobs/Others/PanamaSettlementJob.php
- app/Jobs/ACI/VerifyOrIssueFaithFromIssuanceJob.php
- app/Jobs/Pac/ImportXMLAccordingToCreationDateJob.php
- app/Jobs/Pac/CreateAccessTokenJob.php
- app/Jobs/CreateFiscalCreditInvoiceAlanubeDomJob.php
- app/Jobs/CreateInvoiceAlanubeJob.php

</details>

**Recomendación**: Agregar `public $tries = 3;` a todos estos jobs.

---

## ⏱️ Jobs Sin `$timeout` (49 jobs)

Sin timeout, estos jobs pueden ejecutarse indefinidamente:

<details>
<summary>Ver lista completa (49 jobs)</summary>

- app/Jobs/CreateSaleAlanubeDomJob.php
- app/Jobs/Lightspeed/SetSalesOrdersJob.php
- app/Jobs/CreateGovernmentalInvoiceAlanubeDomJob.php
- app/Jobs/CreateSaleMeyparJob.php
- app/Jobs/CleanOldArchivesJob.php
- app/Jobs/Invupos/PurchaseOrdersJob.php
- app/Jobs/Invupos/SubCategoryJob.php
- app/Jobs/Invupos/CustomerJob.php
- app/Jobs/Invupos/CategoryJob.php
- app/Jobs/Invupos/CreateSalesOrderSummaryJob.php
- app/Jobs/Invupos/UploadSalesIntuitJob.php
- app/Jobs/Invupos/PurchaseCategoryJob.php
- app/Jobs/Invupos/SalesOrdersJob.php
- app/Jobs/Invupos/TypePaymentJob.php
- app/Jobs/Invupos/CreditNotesJob.php
- app/Jobs/Invupos/ProductJob.php
- app/Jobs/Invupos/ItemMenuJob.php
- app/Jobs/Invupos/UploadSalesGeneralDiaryIntuitJob.php
- app/Jobs/Invupos/CreateAccessTokenJob.php
- app/Jobs/Invupos/CreateCreditNotesSummaryJob.php
- app/Jobs/Invupos/ProviderJob.php
- app/Jobs/ImportXmlJob.php
- app/Jobs/CorrectElectronicInvoicesWithErrorsJob.php
- app/Jobs/IssueMassInvoicesJob.php
- app/Jobs/SqlServer/STInvoiceJob.php
- app/Jobs/SqlServer/STCostOfGoodsOfCategoryJob.php
- app/Jobs/SqlServer/STVendorsJob.php
- app/Jobs/SqlServer/STCostOfGoodsJob.php
- app/Jobs/SqlServer/STCreateSummaryJob.php
- app/Jobs/TypepaymentlightspeedJob.php
- app/Jobs/Intuit/UpdateIntuitFEJob.php
- app/Jobs/Intuit/CreateIntuitBillsJob.php
- app/Jobs/Intuit/UpdateQuickBooksInvoicesJob.php
- app/Jobs/Intuit/UpdateIntuitOrdersJob.php
- app/Jobs/Ezee/SalesJob.php
- app/Jobs/ImportZipXmlInvoicesJob.php
- app/Jobs/CleanFailedJobsJob.php
- app/Jobs/Booqable/CustomerJob.php
- app/Jobs/Booqable/SalesOrdersV4Job.php
- app/Jobs/Booqable/SalesOrdersJob.php
- app/Jobs/Booqable/ProductJob.php
- app/Jobs/Others/PanamaPaymentsMadeJob.php
- app/Jobs/Others/PanamaDailyEntryJob.php
- app/Jobs/Others/PanamaSettlementJob.php
- app/Jobs/ACI/VerifyOrIssueFaithFromIssuanceJob.php
- app/Jobs/Pac/ImportXMLAccordingToCreationDateJob.php
- app/Jobs/Pac/CreateAccessTokenJob.php
- app/Jobs/CreateInvoiceAlanubeJob.php
- app/Jobs/ExtractOrganizationConfigurationEmailsJob.php

</details>

**Recomendación**: 
- Jobs rápidos (< 5 min): `public $timeout = 300;`
- Jobs normales (5-15 min): `public $timeout = 900;`
- Jobs largos (15-30 min): `public $timeout = 1800;`

---

## ️ Jobs Sin `$maxExceptions` (67 jobs)

Sin límite de excepciones, estos jobs pueden fallar repetidamente consumiendo recursos:

<details>
<summary>Ver lista completa (67 jobs)</summary>

- app/Jobs/CreateSaleQuickBooksJob.php
- app/Jobs/CreateSaleAlanubeDomJob.php
- app/Jobs/Lightspeed/SetSalesOrdersJob.php
- app/Jobs/CreateGovernmentalInvoiceAlanubeDomJob.php
- app/Jobs/CreateSaleMeyparJob.php
- app/Jobs/CleanOldArchivesJob.php
- app/Jobs/Invupos/PurchaseOrdersJob.php
- app/Jobs/Invupos/SubCategoryJob.php
- app/Jobs/Invupos/CustomerJob.php
- app/Jobs/Invupos/CategoryJob.php
- app/Jobs/Invupos/UploadSalesIntuitJob.php
- app/Jobs/Invupos/PurchaseCategoryJob.php
- app/Jobs/Invupos/SalesOrdersJob.php
- app/Jobs/Invupos/TypePaymentJob.php
- app/Jobs/Invupos/CreditNotesJob.php
- app/Jobs/Invupos/ProductJob.php
- app/Jobs/Invupos/ItemMenuJob.php
- app/Jobs/Invupos/UploadSalesGeneralDiaryIntuitJob.php
- app/Jobs/Invupos/CreateAccessTokenJob.php
- app/Jobs/Invupos/ProviderJob.php
- app/Jobs/ImportXmlJob.php
- app/Jobs/CorrectElectronicInvoicesWithErrorsJob.php
- app/Jobs/IssueMassInvoicesJob.php
- app/Jobs/CreateSaleShopifyJob.php
- app/Jobs/CleanExpiredTokensJob.php
- app/Jobs/ProcessZohoPurchaseOrderJob.php
- app/Jobs/SqlServer/STInvoiceJob.php
- app/Jobs/SqlServer/STCostOfGoodsOfCategoryJob.php
- app/Jobs/SqlServer/STVendorsJob.php
- app/Jobs/SqlServer/STCostOfGoodsJob.php
- app/Jobs/SqlServer/STCreateSummaryJob.php
- app/Jobs/UpdateFiscalDocumentNumberJob.php
- app/Jobs/GenerateDocumentAIJSONFilesJob.php
- app/Jobs/TypepaymentlightspeedJob.php
- app/Jobs/PlusMovil/ImportInvoicesJob.php
- app/Jobs/Intuit/UpdateIntuitFEJob.php
- app/Jobs/Intuit/CreateIntuitBillsJob.php
- app/Jobs/Intuit/UpdateQuickBooksInvoicesJob.php
- app/Jobs/Intuit/UpdateIntuitOrdersJob.php
- app/Jobs/Ezee/SalesJob.php
- app/Jobs/ImportZipXmlInvoicesJob.php
- app/Jobs/EmitObjectJob.php
- app/Jobs/CreateExportInvoiceAlanubeDomJob.php
- app/Jobs/CreateSaleLightspeedJob.php
- app/Jobs/SendSaleToQuickBooksJob.php
- app/Jobs/CleanFailedJobsJob.php
- app/Jobs/Booqable/CustomerJob.php
- app/Jobs/Booqable/SalesOrdersV4Job.php
- app/Jobs/Booqable/SalesOrdersJob.php
- app/Jobs/Booqable/ProductJob.php
- app/Jobs/Booqable/DownloadSalesOrdersV4Job.php
- app/Jobs/SyncOrganizationToCompanyJob.php
- app/Jobs/CleanOldTransactionsJob.php
- app/Jobs/MonitorDocumentAITraining.php
- app/Jobs/LightspeedSerieR/CreateAccessTokenSerieRJob.php
- app/Jobs/LightspeedSerieR/SetSalesOrdersJob.php
- app/Jobs/Others/PanamaPaymentsMadeJob.php
- app/Jobs/Others/PanamaDailyEntryJob.php
- app/Jobs/Others/PanamaSettlementJob.php
- app/Jobs/ACI/VerifyOrIssueFaithFromIssuanceJob.php
- app/Jobs/CreateSaleMaxgymJob.php
- app/Jobs/Pac/ImportXMLAccordingToCreationDateJob.php
- app/Jobs/Pac/CreateAccessTokenJob.php
- app/Jobs/CreateSaleAciCloudJob.php
- app/Jobs/CreateFiscalCreditInvoiceAlanubeDomJob.php
- app/Jobs/CreateInvoiceAlanubeJob.php
- app/Jobs/CreateSaleKart21Job.php

</details>

**Recomendación**: Agregar `public $maxExceptions = 3;` a todos estos jobs.

---

##  Plan de Acción Recomendado

### Fase 1: URGENTE (Esta semana)
1.  **Arreglar jobs con `while(true)`**
   - [x] app/Jobs/Pac/DownloadPacFileJob.php ✓ (Ya completado)
   - [ ] app/Jobs/Booqable/CustomerJob.php
   - [ ] app/Jobs/CleanOldTransactionsJob.php

### Fase 2: PRIORITARIO (Próximas 2 semanas)
2. **Jobs de Facturación Electrónica** (críticos para negocio)
   - [ ] app/Jobs/CreateSaleAlanubeDomJob.php
   - [ ] app/Jobs/CreateInvoiceAlanubeJob.php
   - [ ] app/Jobs/CreateFiscalCreditInvoiceAlanubeDomJob.php
   - [ ] app/Jobs/CreateGovernmentalInvoiceAlanubeDomJob.php
   - [ ] app/Jobs/CreateExportInvoiceAlanubeDomJob.php
   - [ ] app/Jobs/IssueMassInvoicesJob.php
   - [ ] app/Jobs/EmitObjectJob.php

3. **Jobs de Integraciones Críticas**
   - [ ] app/Jobs/Intuit/UpdateIntuitOrdersJob.php
   - [ ] app/Jobs/ExtractOrganizationConfigurationEmailsJob.php
   - [ ] app/Jobs/Pac/ImportXMLAccordingToCreationDateJob.php

### Fase 3: IMPORTANTE (Próximo mes)
4. **Jobs de Invupos** (17 jobs)
5. **Jobs de SQL Server** (5 jobs)
6. **Jobs de Booqable** (5 jobs)
7. **Resto de jobs** (según prioridad de uso)

---

##  Template de Configuración Recomendada

```php
class MiJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de intentos del job
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tiempo máximo de ejecución (en segundos)
     * 
     * @var int
     */
    public $timeout = 900; // Ajustar según complejidad: 300, 900, 1800

    /**
     * Número máximo de excepciones no manejadas antes de fallar
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Límite de páginas a procesar (solo si usa paginación)
     *
     * @var int
     */
    protected $maxPages = 50; // Solo si aplica

    // ... resto del código

    /**
     * Manejar el fallo del job
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        \Illuminate\Support\Facades\Log::error(
            static::class . " falló completamente",
            [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }
}
```

---

##  Script de Verificación

El script `/tmp/check_jobs_config.php` puede ejecutarse periódicamente para verificar el progreso:

```bash
php /tmp/check_jobs_config.php
```

---

##  Métricas de Progreso

| Fecha | Jobs Configurados | % Completado |
|-------|-------------------|--------------|
| 2026-02-14 | 1 / 71 | 1.4% |
| ... | ... | ... |

---

**Última actualización**: 2026-02-14  
**Próxima revisión**: 2026-02-21
