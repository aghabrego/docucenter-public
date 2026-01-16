# API Trabajos/Jobs - Sage ACICloud

## Obtener Lista de Trabajos

**Endpoint:** `GET /api/acicloud/jobs`  
**Controller:** `ACIcloudController::jobs`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Características del Endpoint

- ✅ **Filtrado Avanzado**: Múltiples filtros y operadores
- ✅ **Paginación**: Resultados paginados automáticamente
- ✅ **Ordenamiento**: Ordenar por cualquier campo
- ✅ **Multi-tenant**: Respeta el contexto de organización activa

### Parámetros de Query (Opcionales)

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `page` | integer | ❌ | Página a obtener | `2` |
| `per_page` | integer | ❌ | Registros por página (máx 100) | `50` |
| `sort` | string | ❌ | Campo para ordenar | `"start_date"` |
| `order` | string | ❌ | Dirección del ordenamiento | `"desc"` |
| `filter` | object | ❌ | Filtros aplicados | `{}` |

### Respuesta de Éxito

```json
{
  "current_page": 1,
  "data": [
    {
      "ID_Compania": 2,
      "JobID": "JOB001",
      "Description": "Trabajo de construcción",
      "JobPhases": 3,
      "IsActive": 1,
      "EstimateExpenses": 15000.75
    },
    {
      "ID_Compania": 2,
      "JobID": "JOB002",
      "Description": "Mantenimiento Servidor XYZ Corp",
      "JobPhases": 2,
      "IsActive": 1,
      "EstimateExpenses": 5000.00
    }
  ],
  "first_page_url": "https://api.docucenter.com/api/acicloud/jobs?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "https://api.docucenter.com/api/acicloud/jobs?page=3",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.docucenter.com/api/acicloud/jobs?page=1",
      "label": "1",
      "active": true
    }
  ],
  "next_page_url": "https://api.docucenter.com/api/acicloud/jobs?page=2",
  "path": "https://api.docucenter.com/api/acicloud/jobs",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 75
}
```

---

## Crear Trabajo

**Endpoint:** `POST /api/acicloud/job`  
**Request Class:** `JobImpRequest`  
**Controller:** `ACIcloudController::jobsImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Campos de Request

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `job_number` | string | ✅ | Número único del trabajo | `"JOB-2025-001"` |
| `job_name` | string | ✅ | Nombre del trabajo | `"Instalación Sistema ERP Cliente ABC"` |
| `customer_id` | string | ✅ | ID del cliente | `"CUST001"` |
| `type` | string | ✅ | Tipo de trabajo | `"service"` |
| `priority` | string | ❌ | Prioridad del trabajo | `"high"` |
| `status` | string | ❌ | Estado inicial | `"planned"` |
| `description` | string | ❌ | Descripción detallada | `"Implementación completa de ERP"` |
| `start_date` | string | ✅ | Fecha de inicio (YYYY-MM-DD) | `"2025-01-15"` |
| `end_date` | string | ✅ | Fecha de finalización | `"2025-02-28"` |
| `estimated_completion` | string | ❌ | Fecha estimada de completación | `"2025-02-15"` |
| `budget` | object | ❌ | Presupuesto del trabajo | `{}` |
| `budget.estimated_cost` | decimal | ❌ | Costo estimado | `15000.00` |
| `budget.estimated_revenue` | decimal | ❌ | Ingresos estimados | `25000.00` |
| `team` | object | ❌ | Equipo de trabajo | `{}` |
| `team.project_manager` | string | ❌ | Gerente del proyecto | `"Juan Pérez"` |
| `team.members` | array | ❌ | Miembros del equipo | `[]` |
| `team.members[].name` | string | ❌ | Nombre del miembro | `"María González"` |
| `team.members[].role` | string | ❌ | Rol del miembro | `"Developer"` |
| `team.members[].hours_allocated` | integer | ❌ | Horas asignadas | `120` |
| `location` | object | ❌ | Ubicación del trabajo | `{}` |
| `location.address` | string | ❌ | Dirección | `"Calle 50, Torre Global Bank"` |
| `location.city` | string | ❌ | Ciudad | `"Ciudad de Panamá"` |
| `location.province` | string | ❌ | Provincia | `"Panamá"` |
| `milestones` | array | ❌ | Hitos del proyecto | `[]` |
| `milestones[].name` | string | ❌ | Nombre del hito | `"Phase 1 Complete"` |
| `milestones[].date` | string | ❌ | Fecha del hito | `"2025-01-30"` |
| `milestones[].description` | string | ❌ | Descripción del hito | `"Configuration completed"` |

### Tipos de Trabajo Válidos

- `service` - Servicio
- `project` - Proyecto
- `maintenance` - Mantenimiento
- `installation` - Instalación
- `consulting` - Consultoría

### Prioridades Válidas

- `low` - Baja
- `medium` - Media
- `high` - Alta
- `urgent` - Urgente

### Estados Válidos

- `planned` - Planificado
- `in_progress` - En Progreso
- `on_hold` - En Pausa
- `completed` - Completado
- `cancelled` - Cancelado

### Ejemplo de Request

```json
{
  "JobID": "JOB123",
  "CustomerID": "CUST456",
  "Description": "Proyecto de infraestructura vial",
  "UsePhases": 1,
  "IsActive": 1,
  "Supervisor": "Juan Pérez",
  "StartDate": "2024-01-15",
  "ProjectedEndDate": "2024-12-31",
  "ActualEndDate": "2025-02-03",
  "Status": "open",
  "Export_date": null,
  "Enviado": 0,
  "Error": 0,
  "ErrorPT": null
}
```

### Respuesta de Éxito

```json
{
  "JobID": "JOB123",
  "CustomerID": "CUST456",
  "Description": "Proyecto de infraestructura vial",
  "UsePhases": 1,
  "IsActive": 1,
  "Supervisor": "Juan Pérez",
  "StartDate": "2024-01-15",
  "ProjectedEndDate": "2024-12-31",
  "ActualEndDate": "2025-02-03",
  "Status": "open"
}
```

---

## Obtener Trabajos Importados

**Endpoint:** `GET /api/acicloud/jobs_imp`  
**Controller:** `ACIcloudController::getJobsImp`  
**Autenticación:** Bearer Token requerido  
**Middleware:** `check.activate.organization`

### Descripción

Obtiene la lista de trabajos que han sido importados/creados a través de la API.

### Respuesta de Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "job_number": "JOB-2025-001",
      "job_name": "Instalación Sistema ERP Cliente ABC",
      "customer_id": "CUST001",
      "customer_name": "ABC Corporation S.A.",
      "sage_id": "SAGE_JOB_001",
      "status": "in_progress",
      "estimated_revenue": 25000.00,
      "completion_percent": 65,
      "import_status": "completed",
      "created_at": "2025-01-29 21:30:00",
      "updated_at": "2025-01-29 21:30:00",
      "errors": null
    },
    {
      "id": 2,
      "job_number": "JOB-2025-002",
      "job_name": "Mantenimiento Servidor XYZ Corp",
      "customer_id": "CUST002",
      "customer_name": "XYZ Corporation",
      "sage_id": "SAGE_JOB_002",
      "status": "completed",
      "estimated_revenue": 5000.00,
      "completion_percent": 100,
      "import_status": "completed",
      "created_at": "2025-01-28 15:20:00",
      "updated_at": "2025-01-29 10:15:00",
      "errors": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 15,
    "total_pages": 1
  }
}
```

---

## Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Consulta exitosa |
| 201 | Trabajo creado exitosamente |
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 404 | Cliente no encontrado |
| 409 | Número de trabajo duplicado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Ejemplos de cURL

#### Obtener Lista de Trabajos
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/jobs?page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Crear Trabajo
```bash
curl -X POST "https://api.docucenter.com/api/acicloud/job" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "job_number": "JOB-2025-001",
    "job_name": "Instalación Sistema ERP Cliente ABC",
    "customer_id": "CUST001",
    "type": "service",
    "start_date": "2025-01-15",
    "end_date": "2025-02-28",
    "budget": {
      "estimated_cost": 15000.00,
      "estimated_revenue": 25000.00
    }
  }'
```

#### Obtener Trabajos Importados
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/jobs_imp" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Filtrar Trabajos por Estado
```bash
curl -X GET "https://api.docucenter.com/api/acicloud/jobs?filter[status][operator]=equals&filter[status][value]=in_progress" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Casos de Uso Frecuentes

### 1. **Gestión de Proyectos de TI**
Crear trabajos para implementaciones de sistemas, con equipos asignados y milestones definidos.

### 2. **Servicios de Mantenimiento**
Programar trabajos recurrentes de mantenimiento con fechas específicas.

### 3. **Consultoría Especializada**
Gestionar proyectos de consultoría con presupuestos y equipos multidisciplinarios.

### 4. **Control de Rentabilidad**
Monitorear márgenes de ganancia comparando costos estimados vs reales.

---

## Notas Técnicas

- **Multi-tenant**: Todos los endpoints respetan el contexto de organización
- **Sincronización**: Los trabajos se sincronizan automáticamente con Sage ACICloud
- **Validación**: Validación estricta de clientes y fechas
- **Seguimiento**: Control de progreso y rentabilidad en tiempo real
- **Recursos**: Asignación de equipos y recursos por trabajo
- **Hitos**: Sistema de milestones para seguimiento de progreso

### Gestión de Proyectos

- **Planificación**: Definición de fechas, presupuestos y equipos
- **Seguimiento**: Monitoreo de progreso y cumplimiento de hitos
- **Control de Costos**: Comparación de estimados vs reales
- **Rentabilidad**: Análisis de márgenes por trabajo
- **Recursos**: Gestión de asignación de personal y equipos

### Integración con Sage ACICloud

Los trabajos creados se integran completamente con el módulo de gestión de proyectos de Sage ACICloud, permitiendo:

- **Time Tracking**: Registro de horas por trabajo
- **Cost Allocation**: Asignación de costos directos e indirectos
- **Invoicing**: Facturación basada en progreso o hitos
- **Reporting**: Reportes detallados de rentabilidad por proyecto
