# API de Ubicaciones Geográficas

Documentación de la API para la gestión de ubicaciones geográficas (provincias, distritos, corregimientos) en el sistema DocuCenter.

## Documentación Disponible

- [**API de Ubicaciones**](locations-api.md) - Documentación completa de endpoints de ubicaciones

## Funcionalidades

### Consulta de Ubicaciones

- Listado de provincias
- Listado de distritos por provincia
- Listado de corregimientos por distrito
- Búsqueda y filtrado de ubicaciones

### Casos de Uso

- Autocompletado de direcciones
- Validación de ubicaciones en formularios
- Integración con sistemas de facturación
- Generación de reportes geográficos

## Estructura de Datos

Las ubicaciones están organizadas jerárquicamente:

```
Provincia
  └── Distrito
      └── Corregimiento
```

Esta estructura permite realizar consultas eficientes y mantener la consistencia de los datos geográficos en todo el sistema.
