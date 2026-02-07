# Troubleshooting - Resolución de Problemas

Documentación de problemas comunes y sus soluciones en DocuCenter.

## 📋 Índice de Problemas

### Proveedores de Certificación (PAC)

#### Digifact
- **[Error 401 - No Autorizado](./digifact-401-error.md)** ⭐
  - Problema: Error 401 al certificar documentos
  - Causa: Token JWT no persistido o expirado
  - Solución: Validación mejorada de persistencia de token
  - Herramientas: Comando de diagnóstico `php artisan digifact:test-connection`

- **[Resumen de Correcciones - Error 401](./digifact-401-fix-summary.md)** 🎯
  - Resumen ejecutivo de todas las correcciones aplicadas
  - Checklist de validación post-corrección
  - Herramientas de testing disponibles

#### Alanube
- **[Validación de Conexión](../validations/alanube-connection-validation-improvement.md)**
  - Mejoras en validación de endpoint PAC
  - Detección automática de configuración

### Integraciones

#### QuickBooks
- **[Manejo de Errores](../optimizations/quickbooks-error-handling-improvements.md)**
  - Sistema mejorado de captura de errores
  - Formateo de mensajes de error

#### Zoho
- Problemas comunes en integración con Zoho Books
- Errores de autenticación OAuth

### Sistema

#### Base de Datos
- Problemas de conexión multi-tenant
- Errores en cambio de base de datos

#### Redis/Colas
- Jobs fallidos
- Timeouts en procesamiento

## 🛠️ Herramientas de Diagnóstico

### Comandos Artisan

```bash
# Digifact - Probar conexión PAC
docker exec -it docucenter-app-1 php artisan digifact:test-connection {org_id}

# Ver logs en tiempo real
docker exec -it docucenter-app-1 tail -f storage/logs/laravel.log | grep "KEYWORD"

# Limpiar cache
docker exec -it docucenter-app-1 php artisan cache:clear
docker exec -it docucenter-app-1 php artisan config:clear
```

### Scripts Bash

```bash
# Digifact - Script interactivo de prueba
./scripts/test-digifact-connection.sh [org_id]

# Ver logs de un servicio específico
docker-compose logs -f app | grep "PAC"
```

### Consultas SQL Útiles

```sql
-- Verificar estado de conexión PAC
SELECT id, organization_id, pac_type, 
       LENGTH(token) as token_length,
       expiration,
       CASE
           WHEN expiration IS NULL THEN 'SIN FECHA'
           WHEN expiration < NOW() THEN 'EXPIRADO'
           ELSE CONCAT('Válido por ', DATEDIFF(expiration, NOW()), ' días')
       END as status
FROM pacconnections
WHERE organization_id = {org_id};

-- Ver últimas transacciones fiscales
SELECT id, sale_id, cufe, estado,
       created_at, updated_at
FROM transition_fiscal
WHERE organization_id = {org_id}
ORDER BY created_at DESC
LIMIT 10;

-- Verificar jobs fallidos
SELECT id, queue, exception, failed_at
FROM failed_jobs
WHERE failed_at >= NOW() - INTERVAL 24 HOUR
ORDER BY failed_at DESC;
```

## 📖 Metodología de Diagnóstico

### 1. Identificar el Problema

- Revisar logs de Laravel
- Verificar errores en interfaz de usuario
- Consultar jobs fallidos
- Revisar estado de servicios externos

### 2. Recopilar Información

- ID de organización afectada
- Timestamp del error
- Stack trace completo
- Estado de la base de datos
- Configuración actual

### 3. Diagnóstico

- Usar comandos de diagnóstico disponibles
- Verificar conectividad con servicios externos
- Validar configuración
- Revisar datos en base de datos

### 4. Aplicar Solución

- Seguir guía específica del problema
- Validar que la solución funciona
- Documentar cambios realizados
- Monitorear post-solución

### 5. Prevención

- Implementar validaciones adicionales
- Mejorar logging
- Agregar alertas
- Actualizar documentación

## 🔍 Patrón de Búsqueda en Logs

### Por Servicio

```bash
# Digifact
grep "Digifact" storage/logs/laravel.log | tail -100

# QuickBooks
grep "QuickBooks\|Intuit" storage/logs/laravel.log | tail -100

# Alanube
grep "Alanube" storage/logs/laravel.log | tail -100
```

### Por Tipo de Error

```bash
# Errores críticos
grep "ERROR" storage/logs/laravel.log | tail -100

# Excepciones
grep "Exception" storage/logs/laravel.log | tail -100

# Autenticación
grep "autenticación\|authentication\|token\|401" storage/logs/laravel.log | tail -100
```

### Por Organización

```bash
# Filtrar por organización específica
grep '"organization_id":122' storage/logs/laravel.log | tail -100
```

## 📝 Template para Nueva Guía de Troubleshooting

Al documentar un nuevo problema, usar este template:

```markdown
# Título del Problema

## Problema

Descripción clara del problema observable por el usuario.

## Síntomas

- Síntoma 1
- Síntoma 2
- Síntoma 3

## Causa Raíz

Explicación técnica de qué causa el problema.

## Diagnóstico

### Paso 1: Verificar X
### Paso 2: Confirmar Y
### Paso 3: Validar Z

## Solución

### Opción 1: [Título]
Pasos detallados...

### Opción 2: [Título] (alternativa)
Pasos detallados...

## Validación

Cómo confirmar que el problema está resuelto:
- [ ] Checklist item 1
- [ ] Checklist item 2

## Prevención

Cómo evitar que el problema ocurra nuevamente.

## Referencias

- Archivo 1
- Archivo 2
- Documentación externa
```

## 🚨 Problemas Críticos Conocidos

### Alto Impacto

1. **Error 401 en PACs** - Impide certificación de documentos
   - Solución: [Guía Digifact 401](./digifact-401-error.md)

2. **Jobs bloqueados** - Procesamiento de colas detenido
   - Solución: Reiniciar workers de Redis

3. **Timeout en conexión DB** - Afecta operaciones críticas
   - Solución: Verificar configuración de pool de conexiones

### Medio Impacto

1. **Sincronización lenta con integraciones** - Delays en importación
2. **Expiración de tokens OAuth** - Requiere re-autenticación
3. **Errores de validación de documentos** - Rechazo por PAC

### Bajo Impacto

1. **Logs excesivos** - Uso de espacio en disco
2. **Caché no invalidado** - Datos desactualizados en UI
3. **Warnings de deprecación** - No afecta funcionalidad

## 📞 Escalación

Si el problema no se puede resolver con estas guías:

1. Verificar en [Issues de GitHub](https://github.com/aghabrego/docucenter/issues)
2. Crear nuevo issue con template de troubleshooting
3. Incluir toda la información de diagnóstico
4. Adjuntar logs relevantes
5. Describir pasos de reproducción

---

**Última actualización:** 2026-01-25
