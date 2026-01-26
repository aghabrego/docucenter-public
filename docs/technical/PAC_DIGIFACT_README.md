# PAC Digifact Panamá - Documentación Rápida

## Documentos Principales

###  Guía Completa de Implementación
**Archivo**: [digifact-panama-pac-implementation-guide.md](digifact-panama-pac-implementation-guide.md)

Este es el documento principal que contiene:
- Análisis exhaustivo de la API Digifact v1.0.4
- Arquitectura y estructura de endpoints
- Autenticación JWT con token de 30 días
- Flujo completo de certificación
- Plan de implementación por fases
- Código de ejemplo para servicios y jobs
- Scripts de testing
- Consideraciones técnicas y mejores prácticas

## Resumen Rápido

### ¿Qué es Digifact?
Digifact es un proveedor de certificación (PAC) autorizado por la DGI de Panamá para emitir facturas electrónicas bajo el régimen de la Ley N°256 del 26 de noviembre de 2021.

### Características Clave
- **Protocolo**: API REST con JSON/XML
- **Autenticación**: Bearer Token JWT (30 días vigencia)
- **Ambientes**: Test y Productivo
- **Formatos**: XML (envío), PDF|HTML|XML (respuesta en base64)
- **Firma Electrónica**: Requerida para producción

### URLs
```
Test:       https://pactest.digifact.com.pa/pa.com.apinuc/api
Productivo: https://apinuc.digifact.com.pa/api
```

### Endpoints Principales
1. `POST /login/get_token` - Autenticación
2. `POST /transform/nuc` - Certificación de documentos
3. `GET /SHAREDINFO` - Consultas generales
4. `GET /GetDocument` - Obtener documento por CUFE

## Inicio Rápido

### 1. Solicitar Credenciales TEST
```
Email: soporte@digifact.com.gt
Subject: "Solicitud de credenciales TEST"
Tel: 2319-1921, opción 2
```

### 2. Formato de Username
```
PA.{RUC}.{USERNAME}
Ejemplo: PA.155704849-2-2021.USER_TEST
```

### 3. Obtener Token
```http
POST /login/get_token
Content-Type: application/json

{
  "Username": "PA.155704849-2-2021.USER_TEST",
  "Password": "s142$%SAF"
}
```

### 4. Certificar Documento
```http
POST /transform/nuc?TAXID=23824-0930-212623&FORMAT=PDF|HTML|XML&USERNAME=La_Lechita
Content-Type: application/xml
Authorization: Bearer {token}

{XML del documento según esquema DGI}
```

## Estructura de Implementación

```
app/
 Services/
    DigifactService.php          # Servicio principal
 Jobs/
    CertifyDigifactInvoiceJob.php # Job de certificación
 Console/Commands/
     DigifactTestCommand.php       # Command de testing

resources/views/xml/digifact/
 invoice.blade.php                 # Template XML DGI

docs/testing/
 digifact-test.sh                  # Script de testing

database/migrations/
 xxxx_add_digifact_fields_to_pacconnections.php
```

## Plan de Implementación

### Fase 1: Configuración (2-3 días)
- Análisis de documentación completado
-  Solicitar credenciales TEST
-  Crear migración para Pacconnection
-  Actualizar modelo

### Fase 2: Desarrollo del Servicio (3-5 días)
-  Implementar DigifactService
-  Testing unitario

### Fase 3: Generación XML (5-7 días)
-  Estudiar esquema DGI NUC
-  Crear template XML
-  Validaciones

### Fase 4: Job de Certificación (2-3 días)
-  Implementar job con estados granulares
-  Testing

### Fase 5: UI (3-4 días)
-  Formulario de configuración
-  Validación de conexión

### Fase 6: Testing Integral (3-5 días)
-  Testing en TEST
-  Scripts de prueba

### Fase 7: Productivo (2-3 días)
-  Credenciales productivas
-  Firma electrónica
-  Pruebas con facturas reales

### Fase 8: Documentación (2 días)
-  Documentación técnica
-  Guía de usuario

## Respuesta de Certificación

```json
{
  "codigo": "200",
  "mensaje": "Certificación exitosa",
  "cufe": "FE0120000155704849-2-2021320001...",
  "acuseReciboDGI": "FE0120000155704849-2-2021320001...",
  "responseData1": "base64_xml",
  "responseData2": "base64_html",
  "responseData3": "base64_pdf",
  "fecha_certificacion": "2024-12-26T10:30:15",
  "serie": "A",
  "numero": "12345",
  "monto": "1500.00"
}
```

## Diferencias con Otros PACs

| Característica | Digifact | TheFactoryHKA | Alanube |
|----------------|----------|---------------|---------|
| Protocolo | REST | REST | SOAP/REST |
| Token Duration | 30 días | Variable | Variable |
| Formatos | XML, HTML, PDF | JSON, PDF | XML |
| Complejidad | Media | Media | Alta |

## Soporte

### Digifact
- **Email**: soporte@digifact.com.gt
- **Tel**: 2319-1921 (Panamá)
- **Subject Format**: `[RUC] Tipo Consulta_REST`

### DGI
- **Documentación**: https://dgi.mef.gob.pa/_7FacturaElectronica/
- **Agencia Virtual**: Para firma electrónica

## Consideraciones Técnicas

### Multi-Tenant
```php
DB::connection()->useDatabase($organization->database);
// ... operaciones
DB::connection()->useDatabase(env('DB_DATABASE'));
```

### Token Management
- Almacenar `token_expires_at`
- Verificar antes de cada request
- Renovar automáticamente si expiró

### Formatos Base64
```php
$pdfContent = base64_decode($result['pdf_base64']);
Storage::put($path, $pdfContent);
```

### RUC Formatting
Para `SHARED_GETINFOTAXcom`, el RUC debe tener 20 caracteres:
```php
$formattedRuc = str_pad($ruc, 20, '0', STR_PAD_LEFT);
```

## Comandos Útiles

```bash
# Testing de autenticación
./docs/testing/digifact-test.sh auth

# Testing de certificación
./docs/testing/digifact-test.sh certify

# Testing de consulta
./docs/testing/digifact-test.sh query FE012000...

# Testing completo
./docs/testing/digifact-test.sh complete
```

## Referencias

- [Guía Completa de Implementación](digifact-panama-pac-implementation-guide.md)
- [Índice de Documentación Técnica](index.md)
- [Documentación DGI](https://dgi.mef.gob.pa/_7FacturaElectronica/)
- PDF Original: `/public/Documentacion Tecnica API Digifact Panama V1.0.4.pdf`

---

**Última actualización**: 26 de diciembre de 2025  
**Estado**: Análisis completo - Pendiente implementación  
**Versión API**: 1.0.4
