# Pruebas con Datos Reales - KART 21, S.A.

## Descripción General

Este documento describe el sistema de pruebas con datos reales de la organización KART 21, S.A. para validar el funcionamiento completo del sistema DocuCenter con emisión al PAC (Proveedor Autorizado de Certificación).

## Componentes del Sistema

### 1. Comando Artisan: `TestKartRealDataCommand`

**Ubicación**: `app/Console/Commands/TestKartRealDataCommand.php`

**Propósito**: Ejecutar pruebas completas con datos realistas simulando órdenes reales de KART 21, S.A.

**Funcionalidades**:
- Verificación de organización y configuración PAC
- Conexión a base de datos específica de la organización
- Generación de datos de prueba realistas
- Procesamiento con `Kart21Service`
- Emisión opcional al PAC
- Análisis detallado de resultados

**Uso**:
```bash
# Prueba básica (solo crear factura)
php artisan kart:test-real-data 21

# Prueba con emisión al PAC
php artisan kart:test-real-data 21 --emit

# Simulación sin crear datos
php artisan kart:test-real-data 21 --dry-run
```

### 2. Script Shell: `test-kart-real-data.sh`

**Ubicación**: `scripts/test-kart-real-data.sh`

**Propósito**: Interfaz completa para gestión de pruebas con datos reales

**Funcionalidades**:
- Verificación de dependencias del sistema
- Estado del sistema (BD, Redis, colas)
- Listado de organizaciones KART disponibles
- Ejecución de pruebas con logging detallado
- Estadísticas post-ejecución

**Uso**:
```bash
# Ver ayuda completa
./scripts/test-kart-real-data.sh --help

# Listar organizaciones KART
./scripts/test-kart-real-data.sh --list

# Verificar estado del sistema
./scripts/test-kart-real-data.sh --status

# Prueba completa con organización 21
./scripts/test-kart-real-data.sh 21

# Prueba con emisión al PAC
./scripts/test-kart-real-data.sh 21 --emit

# Simulación sin crear datos
./scripts/test-kart-real-data.sh 21 --dry-run
```

## Datos de Prueba Realistas

### Estructura de Orden KART Simulada

```json
{
  "id": "KART-TEST-20241221-143022",
  "event": "order.close",
  "data": {
    "id": "KART-TEST-20241221-143022",
    "number": "KART-TEST-20241221-143022",
    "status": 2,
    "status_description": "CLOSED",
    "order_type_description": "REGULAR",
    "subtotal": 45.50,
    "tax": 3.19,
    "total": 48.69,
    "customer": {
      "first_name": "Cliente",
      "last_name": "Prueba Real",
      "email": "cliente.prueba@kart21.com",
      "phone": "507 6000-0000",
      "city": "Panama",
      "country": "PA"
    },
    "items": [
      {
        "product_name": "Sesión de Karting Adulto",
        "product_code": "100",
        "price": 25.00,
        "qty": 1,
        "tax": 1.75
      },
      {
        "product_name": "Alquiler de Casco",
        "product_code": "200", 
        "price": 15.00,
        "qty": 1,
        "tax": 1.05
      },
      {
        "product_name": "Bebida Refrescante",
        "product_code": "300",
        "price": 5.50,
        "qty": 1,
        "tax": 0.39
      }
    ],
    "payments": [
      {
        "amount": 48.69,
        "payment_method": "TARJETA",
        "payment_type_description": "CREDIT_CARD"
      }
    ]
  }
}
```

### Productos Típicos KART 21

| Código | Producto | Precio Base | Impuesto (7%) |
|--------|----------|-------------|---------------|
| 100 | Sesión de Karting Adulto | $25.00 | $1.75 |
| 101 | Sesión de Karting Junior | $15.00 | $1.05 |
| 200 | Alquiler de Casco | $15.00 | $1.05 |
| 201 | Alquiler de Guantes | $5.00 | $0.35 |
| 300 | Bebida Refrescante | $5.50 | $0.39 |
| 301 | Snack | $3.00 | $0.21 |

## Verificaciones del Sistema

### 1. Verificación de Organización

El sistema verifica automáticamente:
- Existencia de la organización en la base de datos
- Configuración PAC válida y activa
- Conexión a base de datos específica
- Permisos y configuraciones necesarias

### 2. Verificación de Configuración PAC

Para KART 21, S.A., el sistema valida:

```php
// Configuración PAC esperada
$pacConnection = [
    'name' => 'Configuración PAC KART',
    'pac_type' => 'thefactoryhka', // o 'alanube'
    'endpoint' => 'https://api.thefactoryhka.com/v1', // Producción
    'active' => true,
    'token' => 'JWT_TOKEN_VALIDO'
];
```

### 3. Estados de Emisión

| Estado | Descripción | Acción |
|--------|-------------|--------|
| `EzeeIssued = 0` | Factura creada, no emitida | Pendiente emisión |
| `EzeeIssued = 1` | Factura emitida exitosamente | Completa |
| `EzeeIssued = -1` | Error en emisión | Requiere revisión |

## Flujo de Procesamiento

### 1. Fase de Preparación
```
Verificar organización ID
Validar configuración PAC  
Conectar a BD específica
Generar datos de prueba realistas
```

### 2. Fase de Procesamiento
```
Ejecutar Kart21Service->storeOrder()
Crear registro en SalesHeaderImp
Procesar líneas de items
Calcular impuestos y totales
```

### 3. Fase de Emisión (Opcional)
```
Verificar trait CreateFastJob
Ejecutar emisión al PAC
Actualizar estado EzeeIssued
Registrar CUFE/CUF recibido
```

## Logs y Monitoreo

### Ubicación de Logs
```
scripts/logs/test-kart-real-YYYYMMDD_HHMMSS.log
```

### Información Registrada
- Timestamp de cada operación
- Datos de entrada y salida
- Errores y excepciones
- Tiempo de procesamiento
- Estados de emisión PAC

### Ejemplo de Log
```
[2024-12-21 14:30:22] [INFO] PRUEBA CON DATOS REALES - KART 21, S.A.
[2024-12-21 14:30:22] [INFO] Organización: KART 21, S.A.
[2024-12-21 14:30:23] [INFO] Configuración PAC encontrada: TheFactoryHKA
[2024-12-21 14:30:24] [INFO] Ejecutando Kart21Service->storeOrder()...
[2024-12-21 14:30:24] [INFO] Procesamiento completado en 1,245.67ms
[2024-12-21 14:30:25] [INFO] Emitiendo con trait CreateFastJob...
[2024-12-21 14:30:26] [INFO] Emisión exitosa al PAC
```

## Solución de Problemas

### Error: "Organización no encontrada"
```bash
# Listar organizaciones disponibles
./scripts/test-kart-real-data.sh --list

# Verificar ID específico
php artisan tinker
>>> App\Models\Organization::find(21)
```

### Error: "Sin configuración PAC"
```bash
# Verificar configuración PAC
php artisan tinker
>>> $org = App\Models\Organization::find(21)
>>> $org->pacConnection
```

### Error: "Base de datos no accesible"
```bash
# Verificar estado del sistema
./scripts/test-kart-real-data.sh --status

# Probar conexión manual
php artisan tinker
>>> DB::connection()->getPDO()
```

### Error: "Trait CreateFastJob no encontrado"
```bash
# Verificar traits en Kart21Service
php artisan tinker
>>> $reflection = new ReflectionClass(App\Services\Kart21Service::class)
>>> $reflection->getTraitNames()
```

## Métricas y Rendimiento

### Tiempos Esperados
- **Verificación inicial**: < 2 segundos
- **Procesamiento orden**: 1-3 segundos
- **Emisión PAC**: 3-10 segundos
- **Total**: < 15 segundos

### Estadísticas Típicas
```
Total de facturas: 1,234
Facturas emitidas: 1,180
 Facturas hoy: 45
 Facturas última hora: 3
Tasa de emisión: 95.62%
```

## Próximos Pasos

### Después de Ejecutar Prueba
1. **Verificar resultado**: Revisar logs generados
2. **Validar en panel**: Acceder a factura creada en administración
3. **Confirmar emisión**: Verificar estado en sistema PAC
4. **Análisis adicional**: Usar comando de diagnóstico

### Comandos de Seguimiento
```bash
# Diagnóstico detallado
php artisan kart:diagnose-invoice 21

# Forzar emisión de facturas pendientes
php artisan kart:force-emission 21

# Ver estado de colas
php artisan queue:monitor
```

## Referencias

- [Kart21Service Documentation](../technical/kart21-service.md)
- [PAC Integration Guide](../integrations/pac-providers.md)
- [Troubleshooting Guide](../troubleshooting/kart-common-issues.md)
- [Testing Framework](../testing/automated-testing.md)

---

**Última actualización**: $(date)  
**Versión**: 1.0  
**Estado**: Listo para uso en producción
