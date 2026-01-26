# Reorganización de Comandos Artisan por Tipo de Operación

## **Comandos Incluidos en Kernel.php**

Los siguientes comandos ya están programados en el `App\Console\Kernel`:

### **Mantenimiento del Sistema**
- `word:clear-log` - Limpieza de logs (Dom 7:00 AM)
- `maintenance:clean-transactions --days=90` - Limpieza transacciones (Dom 3:00 AM)
- `maintenance:clean-archives --days=90` - Limpieza archivos (Dom 4:00 AM)
- `queue:prune-batches` - Limpieza lotes de cola (Diario)

### **Gestión de Tokens**
- `word:create-access-token` - Token Invupos (Cada 5 min)
- `word:create-access-token-apc` - Token PAC (Cada 5 min)

### **🏪 Integraciones POS**
- `word:type-payment-lightspeed` - Tipos pago Lightspeed (1:00 AM)
- `word:update-invu-pos-module` - Lotes Invupos (Cada 30 min)
- `word:update-lightspeed-serie-r` - Lightspeed Serie R (Cada 5 min)
- `word:update-lightspeed-module` - Lightspeed general (Cada hora)
- `word:update-sql-server-module` - SQL Server (6:00 AM)
- `word:update-intuit-orders` - QuickBooks (2:00 AM)

### **Configuración Automática**
- `word:extract-organization-configuration-emails` - Config emails (Cada 5 min)
- `word:extract-organization-configuration-pac` - Config PAC (Cada hora)

### ** Facturación Electrónica**
- `fe:verify-or-issue-faith-from-issuance` - Verificar/Emitir FE (Cada 5 min)

---

## **Comandos NO Incluidos en Kernel - Organizados por Tipo**

### **1. COMANDOS DE DIAGNÓSTICO Y DEBUG**

#### **🏎Kart21 Diagnosis**
- `DiagnoseKartInvoiceCommand.php` → `kart:diagnose-invoice`
- `EmitKartOrderCommand.php` → `kart:emit-order`
- `EmitKartWithCashPayment.php` → `kart:emit-cash-payment`
- `EmitKartWithCreateFastJobCommand.php` → `kart:emit-with-job`
- `ForceKartInvoiceEmissionCommand.php` → `kart:force-emission`
- `TestKartRealApiCommand.php` → `kart:test-real-api`
- `TestKartRealDataCommand.php` → `kart:test-real-data`
- `TestKart21ServiceCommand.php` → `kart21:test-service`
- `TestKart21DecimalPrecision.php` → `kart21:test-precision`

#### **🏭 PAC y Facturación Debug**
- `DebugCreateFastJobVuelto.php` → `debug:createfastjob-vuelto`
- `DebugTheFactoryHKARequest.php` → `debug:thefactoryhka-request`
- `TestTheFactoryHKAPayload.php` → `test:thefactoryhka-payload`
- `CorrectElectronicInvoicesWithErrors.php` → `word:correct-electronic-invoices-with-errors`
- `FixACIcloudReceptorIssues.php` → `fix:acicloud-receptor-issues`
- `TraceInvoiceNumberFlow.php` → `trace:invoice-number-flow`
- `DebugInvoiceNumberMapping.php` → `debug:invoice-number-mapping`

#### **Testing General**
- `TestACIcloudApiWithEmission.php` → `test:acicloud-api-emission`
- `TestACIcloudRealData.php` → `test:acicloud-real-data`
- `TestACIcloudRegistration.php` → `test:acicloud-registration`
- `TestACIcloudService.php` → `test:acicloud-service`
- `TestAlanubeConnection.php` → `test:alanube-connection`
- `TestAlanubeDomService.php` → `test:alanube-dom-service`
- `TestAlanubeService.php` → `test:alanube-service`
- `TestApiReceptorProblem.php` → `test:api-receptor-problem`

### **2. COMANDOS DE INTEGRACIONES EMPRESARIALES**

#### **Zoho Books**
- `TestZohoConnection.php` → `zoho:test-connection`
- `TestKartToZoho.php` → `kart:send-to-zoho`

#### **QuickBooks/Intuit**
- `UploadSalesGeneralDiaryIntuitCommand.php` → `intuit:upload-sales-diary`
- `UploadSalesIntuitCommand.php` → `intuit:upload-sales`
- `IntuitSyncStatusCommand.php` → `intuit:sync-status`

#### **Lightspeed**
- `AnalyzeLightspeedCreditNoteCommand.php` → `lightspeed:analyze-credit-note`
- `TestLightspeedCreditNoteCommand.php` → `test:lightspeed-credit-note`
- `TestLightspeedPacEmission.php` → `test:lightspeed-pac-emission`
- `TestLightspeedPacEmissionReal.php` → `test:lightspeed-pac-real`
- `ValidateLightspeedCreditNoteFix.php` → `validate:lightspeed-credit-note-fix`

### **🏪 3. COMANDOS DE SINCRONIZACIÓN POS**

#### **Invupos**
- `CategoryInvuposCommand.php` → `word:category-invupos`
- `CustomerInvuposCommand.php` → `word:customer-invupos`
- `ProductInvuposCommand.php` → `word:product-invupos`
- `ProviderInvuposCommand.php` → `word:provider-invupos`
- `PurchaseCategoryInvupos.php` → `word:purchase-category-invupos`
- `SubCategoryInvuposCommand.php` → `word:subcategory-invupos`
- `TypePaymentInvuposCommand.php` → `word:type-payment-invupos`

#### **General POS**
- `CreditNotesCommand.php` → `word:credit-notes`
- `ItemMenuCommand.php` → `word:item-menu`
- `PurchaseOrdersCommand.php` → `word:purchase-orders`
- `SalesOrdersCommand.php` → `word:sales-orders`

### **4. COMANDOS DE ANÁLISIS Y REPORTES**

#### **Resúmenes y Análisis**
- `CreateCreditNotesSummaryCommand.php` → `create:credit-notes-summary`
- `CreateSalesOrderSummary.php` → `create:sales-order-summary`
- `PanamaDailyEntryCommand.php` → `panama:daily-entry`

#### **🧮 Testing de Cálculos**
- `TestCreateFastJobCalculation.php` → `test:createfastjob-calculation`
- `TestInvoiceQuantityNormalization.php` → `test:invoice-quantity-normalization`

### **5. COMANDOS DE CONFIGURACIÓN Y MANTENIMIENTO**

#### **Configuración de Base de Datos**
- `AddColumnToOrganizationsTableCommand.php` → `config:add-column-organizations`
- `AlterColumnIncrementCommand.php` → `config:alter-column-increment`
- `CreateTableFromStubCommand.php` → `config:create-table-from-stub`
- `RemoveColumnToOrganizationsTableCommand.php` → `config:remove-column-organizations`
- `RemoveColumnsGjeHeaderImp.php` → `config:remove-columns-gje-header`

#### **🧹 Limpieza Manual**
- `CleanOldArchivesCommand.php` → `maintenance:clean-archives`
- `CleanOldTransactionsCommand.php` → `maintenance:clean-transactions`
- `ClearLogFile.php` → `word:clear-log`

#### ** Importación/Exportación**
- `ExportData.php` → `export:data`
- `ImportData.php` → `import:data`

### **6. COMANDOS ESPECÍFICOS DE TESTING**

#### **🧪 Testing de Meypar**
- `DebugMeyparEmission.php` → `debug:meypar-emission`
- `MeyparTestComplete.php` → `meypar:test-complete`
- `TestMeyparApiNormalization.php` → `test:meypar-api-normalization`
- `TestMeyparNormalization.php` → `test:meypar-normalization`
- `TestMeyparOnlyProcessing.php` → `test:meypar-only-processing`
- `TestMeyparPayments.php` → `test:meypar-payments`
- `TestMeyparQuantityOnly.php` → `test:meypar-quantity-only`
- `TestMeyparRealFlow.php` → `test:meypar-real-flow`
- `TestMeyparSpecificData.php` → `test:meypar-specific-data`

#### **🔬 Testing de Procesos**
- `CreateAlanubeConfigAndTestMeypar.php` → `create:alanube-config-test-meypar`
- `CreateFastJobWrapper.php` → `create:fastjob-wrapper`
- `CreateFastJobWrapperFixed.php` → `create:fastjob-wrapper-fixed`
- `TestOriginalProblem.php` → `test:original-problem`
- `TestTransactionSystem.php` → `test:transaction-system`
- `TestingIndex.php` → `testing:index`

### ** 7. COMANDOS DE PROCESOS PERIÓDICOS**

#### ** Actualizaciones de Estado**
- `UpdateAppointmentStatus.php` → `update:appointment-status`
- `UpdateBooqableModule.php` → `word:update-booqable-module`

#### ** Tokens Específicos**
- `CreateAccessTokenSerieRCommand.php` → `word:create-access-token-serie-r`

#### **🤖 Procesos Ezete**
- `EzeeteCommand.php` → `ezete:process`

---

## **Recomendaciones de Reorganización**

### **Comandos Críticos que Deberían Incluirse en Kernel:**

#### **Alta Prioridad - Producción**
```php
// Corrección automática de facturas con errores
$schedule->command('word:correct-electronic-invoices-with-errors')->everyThirtyMinutes();

// Verificación de estado de citas
$schedule->command('update:appointment-status')->hourly();

// Token Serie R
$schedule->command('word:create-access-token-serie-r')->everyFiveMinutes();

// Proceso Ezete
$schedule->command('ezete:process')->everyTenMinutes();
```

#### **Media Prioridad - Sincronización**
```php
// Sincronización Zoho para organizaciones que lo requieran
$schedule->command('kart:send-to-zoho')->hourly()->when(function () {
    // Solo si hay conexiones Zoho activas
    return \App\Models\Connection::where('application', 'zoho-self-client')->where('active', true)->exists();
});

// Módulo Booqable
$schedule->command('word:update-booqable-module')->everyThirtyMinutes();
```

### **🧪 Comandos de Testing - Solo para Desarrollo**
Los comandos en la carpeta `Testing/` y comandos de debug deberían:
- **Mantenerse** en desarrollo/staging
- **Excluirse** de producción via environment check
- **Documentarse** en `docs/testing/`

### **🗂Comandos Manuales - Administrativos**
Los comandos de configuración y análisis deberían:
- **Ejecutarse** manualmente por administradores
- **Documentarse** en `docs/technical/`
- **No programarse** automáticamente

---

## **Estructura Recomendada del Kernel**

```php
protected function schedule(Schedule $schedule)
{
    // === MANTENIMIENTO CRÍTICO ===
    $schedule->command('word:clear-log')->cron('0 7 * * 0');
    $schedule->command('maintenance:clean-transactions --days=90')->cron('0 3 * * 0');
    $schedule->command('maintenance:clean-archives --days=90')->cron('0 4 * * 0');
    $schedule->command('queue:prune-batches')->daily();

    // === TOKENS Y AUTENTICACIÓN ===
    $schedule->command('word:create-access-token')->everyFiveMinutes();
    $schedule->command('word:create-access-token-apc')->everyFiveMinutes();
    $schedule->command('word:create-access-token-serie-r')->everyFiveMinutes();

    // === INTEGRACIONES POS PRINCIPALES ===
    $schedule->command('word:type-payment-lightspeed')->cron('0 1 * * *');
    $schedule->command('word:update-invu-pos-module')->everyThirtyMinutes();
    $schedule->command('word:update-lightspeed-serie-r')->everyFiveMinutes();
    $schedule->command('word:update-lightspeed-module')->hourly();
    $schedule->command('word:update-sql-server-module')->cron('0 6 * * *');
    $schedule->command('word:update-intuit-orders')->cron('0 2 * * *');

    // === CONFIGURACIÓN AUTOMÁTICA ===
    $schedule->command('word:extract-organization-configuration-emails')->everyFiveMinutes();
    $schedule->command('word:extract-organization-configuration-pac')->hourly();

    // === FACTURACIÓN ELECTRÓNICA ===
    $schedule->command('fe:verify-or-issue-faith-from-issuance')->everyFiveMinutes();
    $schedule->command('word:correct-electronic-invoices-with-errors')->everyThirtyMinutes();

    // === PROCESOS DE NEGOCIO ===
    $schedule->command('update:appointment-status')->hourly();
    $schedule->command('ezete:process')->everyTenMinutes();
    
    // === INTEGRACIONES ESPECÍFICAS (CONDICIONALES) ===
    $schedule->command('word:update-booqable-module')->everyThirtyMinutes()->when(function () {
        return config('integrations.booqable.enabled', false);
    });
}
```

---

## **Resumen Ejecutivo**

- **Incluidos en Kernel**: 15 comandos programados
- **Disponibles**: ~120 comandos adicionales
- **Críticos para incluir**: 4 comandos
- **Opcionales para incluir**: 2 comandos
- **🧪 Solo testing**: ~30 comandos
- **Solo manuales**: ~70 comandos

**Total: ~135 comandos artisan en el sistema DocuCenter**
