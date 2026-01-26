# APIs de DocuCenter

Este directorio contiene toda la documentación de las APIs REST disponibles en el sistema DocuCenter.

##  APIs Disponibles

###  [Facturación Electrónica (FE)](./fe/)
APIs para la emisión de documentos fiscales electrónicos y integración con diferentes plataformas:

- **Emisión de documentos**: Crear facturas, notas de crédito, etc.
- **Integraciones**: Shopify, Lightspeed, MaxGym, MEYPAR, Kart21
- **Descargas**: Obtener documentos en formato PDF y XML
- **[Consulta de RUC](./check-ruc-api.md)**: Validación de RUCs panameños con Alanube
- **[Guía de Formatos RUC](./ruc-formats-guide.md)**: Tipos de RUC soportados (natural, extranjero, empresarial)

### [Alanube Service](./alanube-service-usage.md)
Servicio para emisión de documentos electrónicos en República Dominicana y Panamá:

- **[Guía de Uso](./alanube-service-usage.md)**: Documentación completa del servicio
- **[Ejemplos Prácticos](./alanube-service-examples.md)**: Casos de uso reales con código
- **Dual-Country**: Soporte automático para ambos países
- **Auto-Detection**: Detección automática de país por configuración PAC

### [Sage ACICloud](./sage-acicloud/)
API completa para integración con el sistema ERP Sage ACICloud:

- **Clientes**: Gestión de clientes y proveedores
- **Productos**: Manejo de inventario y catálogos
- **Ventas**: Órdenes de venta y facturación
- **Compras**: Órdenes de compra y gestión de proveedores
- **Pagos**: Procesamiento y conciliación de pagos
- **Inventario**: Control de stock y movimientos

### [Organizaciones](./organizations/)
APIs para la gestión de organizaciones y configuraciones del sistema.

###  [Ubicaciones](./locations/)
APIs para el manejo de ubicaciones geográficas y direcciones.

## Guía de Inicio Rápido

### Autenticación
La mayoría de las APIs utilizan autenticación Bearer Token (Laravel Sanctum):

```bash
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### URL Base
```
https://tu-dominio.com/api/{endpoint}
```

### Ejemplo de Petición
```bash
curl -X POST "https://tu-dominio.com/api/fe/create_sale" \
  -H "Authorization: Bearer tu-token-aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {
      "name": "Cliente Ejemplo",
      "email": "cliente@ejemplo.com"
    },
    "items": [
      {
        "description": "Producto 1",
        "quantity": 1,
        "price": 10.00
      }
    ]
  }'
```

## Convenciones de la API

### Códigos de Respuesta HTTP
- `200` - OK: Petición exitosa
- `201` - Created: Recurso creado exitosamente
- `400` - Bad Request: Error en los datos enviados
- `401` - Unauthorized: Token inválido o faltante
- `404` - Not Found: Recurso no encontrado
- `500` - Internal Server Error: Error interno del servidor

### Formato de Respuesta
```json
{
  "success": true,
  "data": {
    // ... datos de respuesta
  },
  "message": "Operación exitosa"
}
```

### Formato de Error
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Descripción del error",
    "details": {
      // ... detalles adicionales del error
    }
  }
}
```

## Recursos Útiles

- **Postman Collection**: Solicita acceso al equipo de desarrollo
- **Sandbox Environment**: Usa `https://sandbox.tu-dominio.com/api/` para pruebas
- **Rate Limiting**: Las APIs tienen límite de 1000 peticiones por hora por token
- **Webhooks**: Disponibles para notificaciones en tiempo real

##  Soporte

Para dudas específicas sobre las APIs:

1. Consulta la documentación específica de cada endpoint
2. Revisa los ejemplos de código incluidos
3. Contacta al equipo de desarrollo para casos especiales

---

*Última actualización: Agosto 2025*
