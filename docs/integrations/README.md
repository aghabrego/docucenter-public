# Integraciones Disponibles

Documentación de todas las integraciones con sistemas externos disponibles en DocuCenter.

## Integraciones Activas

### [Zoho Self Client](./zoho-self-client.md)
**Estado**: Implementada y funcional
- Integración completa con Zoho Books API
- Autorización directa con código (sin OAuth tradicional)
- Re-autorización sencilla para renovar tokens
- Testing automatizado completo

###  Lightspeed POS
**Estado**: Implementada
- Integración con sistema POS Lightspeed
- Sincronización de productos y ventas
- Soporte para múltiples ubicaciones

### QuickBooks
**Estado**: Implementada  
- Sincronización bidireccional
- Importación de órdenes y clientes
- Manejo de pagos y facturas

###  Shopify
**Estado**: Implementada
- Importación automática de órdenes
- Sincronización de productos
- Webhooks para actualizaciones en tiempo real

### PlusMóvil ERP
**Estado**: En Producción (Con errores reportados)
- Integración con sistema ERP PlusMóvil para facturación
- Autenticación mediante AWS Cognito
- Importación de facturas, clientes y productos
- **[Ver reporte de errores en producción](../testing/PLUSMOVIL-PRODUCTION-ERROR-REPORT.md)**
- Documentación disponible:
  - [Resumen de implementación](plusmovil-implementation-summary.md)
  - [Análisis de implementación](plusmovil-implementation-analysis.md)
  - [Resultados de pruebas API](plusmovil-api-testing-results.md)
  - [Estrategia de almacenamiento de tokens](plusmovil-token-storage-strategy.md)
  - [Plan de continuación](plusmovil-continuation-plan.md)
  - [Próximos pasos](plusmovil-next-steps.md)

## Estadísticas de Integraciones

| Integración | Estado | Tipo | Complejidad | Mantenimiento |
|-------------|--------|------|-------------|---------------|
| **Zoho Self Client** | Activa | API REST | Baja | Mínimo |
| **Lightspeed** | Activa | API REST | Media | Regular |
| **QuickBooks** | Activa | OAuth + API | Alta | Regular |
| **Shopify** | Activa | Webhooks + API | Media | Mínimo |
| **PlusMóvil** | Con errores | AWS Cognito + API | Media | En investigación |

## Próximas Integraciones

### En Desarrollo
- **WooCommerce** - Integración con tiendas WordPress
- **Square** - Sistema POS y pagos
- **Sage 50** - ERP empresarial

### Planificadas
- **Magento** - E-commerce enterprise
- **Stripe** - Procesamiento de pagos
- **PayPal** - Gateway de pagos

## Guías de Desarrollo

### Para Desarrolladores
1. **[Zoho Self Client](./zoho-self-client.md)** - Ejemplo completo de implementación
2. Revisar patrones de autenticación existentes
3. Seguir convenciones de testing establecidas

### Para Administradores
1. Configurar credenciales en variables de entorno
2. Verificar conectividad y permisos
3. Monitorear logs de integración

## Herramientas de Testing

Cada integración incluye:
- Scripts de testing automatizado
- Comandos Artisan personalizados  
- Documentación de troubleshooting
- Ejemplos de configuración

---

**¿Necesitas una nueva integración?** Revisa la documentación de Zoho Self Client como referencia para implementaciones similares.
