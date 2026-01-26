# Comandos a Verificar para Inclusión en Kernel

## **Comandos Identificados para Revisión**

Los siguientes comandos requieren verificación antes de ser incluidos automáticamente en el Kernel de Laravel:

### **Alta Prioridad - Revisar Inmediatamente**

#### 1. **CreateAccessTokenSerieRCommand.php**
```bash
# Comando: word:create-access-token-serie-r
# Estado: INCLUIDO en Kernel actualizado
# Frecuencia: Cada 5 minutos
# Propósito: Token de autenticación para Lightspeed Serie R
```

#### 2. **CorrectElectronicInvoicesWithErrors.php**
```bash
# Comando: word:correct-electronic-invoices-with-errors
# Estado: INCLUIDO en Kernel actualizado
# Frecuencia: Cada 30 minutos
# Propósito: Corregir automáticamente facturas electrónicas con errores
```

#### 3. **UpdateAppointmentStatus.php**
```bash
# Comando: update:appointment-status
# Estado: INCLUIDO en Kernel actualizado
# Frecuencia: Cada hora
# Propósito: Actualizar estado de citas/appointments
```

#### 4. **EzeeteCommand.php**
```bash
# Comando: ezete:process
# Estado: INCLUIDO en Kernel actualizado
# Frecuencia: Cada 10 minutos
# Propósito: Procesar operaciones Ezete
```

### **Media Prioridad - Evaluación Condicional**

#### 5. **UpdateBooqableModule.php**
```bash
# Comando: word:update-booqable-module
# Estado: INCLUIDO con condición
# Frecuencia: Cada 30 minutos (solo si está habilitado)
# Propósito: Sincronización con sistema Booqable
# Nota: Incluido con verificación condicional
```

#### 6. **UpdateLightspeedSerierModule.php** 🧪
```bash
# Comando: word:update-lightspeed-serie-r
# Estado: INCLUIDO - TESTING DISPONIBLE
# Frecuencia: Cada 5 minutos
# Propósito: Descarga de ventas Lightspeed Serie R
# Testing: Ticket real 220000038728 (VOGLIA MULTIPLAZA, Account ID: 192176)
# Comando de prueba: --ticket_number=220000038728
# Documentación: docs/testing/lightspeed-serie-r-test-commands.md
```

#### 7. **TestZohoConnection.php & TestKartToZoho.php**
```bash
# Comando: zoho:test-connection & kart:send-to-zoho
# Estado:  PENDIENTE evaluación
# Frecuencia: Por evaluar
# Propósito: Integración con Zoho Books
# Nota: Evaluar si las organizaciones usan Zoho activamente
```

### **Comandos de Análisis - Evaluación Manual**

#### 7. **CreateCreditNotesSummaryCommand.php**
```bash
# Comando: create:credit-notes-summary
# Estado: ❓ EVALUACIÓN manual
# Frecuencia: ¿Semanal?
# Propósito: Generar resúmenes de notas de crédito
```

#### 8. **CreateSalesOrderSummary.php**
```bash
# Comando: create:sales-order-summary
# Estado: ❓ EVALUACIÓN manual
# Frecuencia: ¿Diario?
# Propósito: Generar resúmenes de órdenes de venta
```

#### 9. **PanamaDailyEntryCommand.php**
```bash
# Comando: panama:daily-entry
# Estado: ❓ EVALUACIÓN manual
# Frecuencia: ¿Diario?
# Propósito: Procesamiento diario específico de Panamá
```

---

## **Criterios de Evaluación**

### **Incluir en Kernel si:**
- Es crítico para operaciones normales
- Requiere ejecución automática periódica
- No interfiere con rendimiento del sistema
- Maneja errores gracefully
- Tiene logging apropiado

### **NO incluir en Kernel si:**
- Es solo para testing/debugging
- Es de uso manual/administrativo
- Consume muchos recursos
- Es específico de desarrollo
- Requiere intervención manual

### **Incluir CONDICIONALMENTE si:**
- Solo algunas organizaciones lo necesitan
- Depende de configuraciones específicas
- Es opcional por plan de servicio
- Tiene horarios específicos de ejecución

---

## **Pasos de Verificación**

### **Para cada comando candidato:**

1. **Revisar propósito y frecuencia necesaria**
   ```bash
   # Examinar el archivo del comando
   head -50 app/Console/Commands/[CommandName].php
   ```

2. **Verificar si ya está en uso manual**
   ```bash
   # Buscar en logs si se ejecuta manualmente
   grep "command_name" storage/logs/laravel.log
   ```

3. **Evaluar impacto en recursos**
   ```bash
   # Ejecutar una vez y medir tiempo/memoria
   time php artisan command:name --dry-run
   ```

4. **Revisar dependencias**
   ```bash
   # Verificar que no dependa de servicios externos críticos
   ```

5. **Probar en staging**
   ```bash
   # Agregar temporalmente al Kernel en ambiente de pruebas
   ```

---

## **Checklist de Implementación**

### **Antes de agregar al Kernel:**

- [ ] Comando ejecuta sin errores
- [ ] Maneja excepciones apropiadamente
- [ ] Tiene logging para monitoreo
- [ ] No bloquea otros procesos
- [ ] Respeta timeouts configurados
- [ ] Puede ejecutarse en paralelo
- [ ] Documentado en `docs/technical/`

### **Después de agregar al Kernel:**

- [ ] Monitorear logs por 48 horas
- [ ] Verificar impacto en performance
- [ ] Confirmar ejecución exitosa
- [ ] Documentar horarios y propósito
- [ ] Configurar alertas si fallan
- [ ] Comunicar a equipo de soporte

---

## **Estado Actual Post-Reorganización**

### **Incluidos en Kernel Actualizado (Total: 19)**

#### **Mantenimiento (4):**
- `word:clear-log`
- `maintenance:clean-transactions`
- `maintenance:clean-archives`
- `queue:prune-batches`

#### **Tokens (3):**
- `word:create-access-token`
- `word:create-access-token-apc`
- `word:create-access-token-serie-r` **NUEVO**

#### **Integraciones POS (6):**
- `word:type-payment-lightspeed`
- `word:update-invu-pos-module`
- `word:update-lightspeed-serie-r`
- `word:update-lightspeed-module`
- `word:update-sql-server-module`
- `word:update-intuit-orders`

#### **Configuración (2):**
- `word:extract-organization-configuration-emails`
- `word:extract-organization-configuration-pac`

#### **Facturación Electrónica (2):**
- `fe:verify-or-issue-faith-from-issuance`
- `word:correct-electronic-invoices-with-errors` **NUEVO**

#### **Procesos de Negocio (2):**
- `update:appointment-status` **NUEVO**
- `ezete:process` **NUEVO**

#### **Condicionales (1):**
- `word:update-booqable-module` **NUEVO** (con condición)

### **Pendientes de Evaluación: ~115 comandos**

La mayoría son comandos de testing, debug, configuración manual o específicos de desarrollo que **NO** deben incluirse en ejecución automática.

---

## **Recomendación Final**

El Kernel ha sido **reorganizado y optimizado** con:
- **4 comandos nuevos críticos** agregados
- **1 comando condicional** para Booqable
- **Categorización clara** por tipo de operación
- **Comentarios descriptivos** mejorados
- **Estructura lógica** de ejecución

**Total: 19 comandos programados** (vs. 15 anteriores)

El resto de comandos deben permanecer como **ejecución manual** para propósitos específicos de testing, debugging, configuración y administración.
