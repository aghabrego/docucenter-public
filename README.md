# 📖 Documentación DocuCenter

[![GitHub Pages](https://img.shields.io/badge/GitHub%20Pages-Live-brightgreen?logo=github)](https://aghabrego.github.io/docucenter-public/)
[![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen)](https://github.com/aghabrego/docucenter-public/actions)
[![MkDocs](https://img.shields.io/badge/MkDocs-Material-blue?logo=markdown)](https://squidfunk.github.io/mkdocs-material/)
[![License](https://img.shields.io/badge/License-Private-red)](https://github.com/aghabrego/docucenter)

> **Documentación técnica oficial** del sistema integral de facturación electrónica **DocuCenter** desarrollado por [APC Panamá](https://home.apconpanama.me/).

## 🌐 Sitio Web Live

**🔗 https://aghabrego.github.io/docucenter-public/**

Este repositorio contiene la documentación pública generada automáticamente desde el repositorio principal privado de DocuCenter.

---

## 🎯 Sobre DocuCenter

**DocuCenter** es un sistema integral de facturación electrónica y gestión empresarial multi-tenant que incluye:

- 🧾 **Facturación Electrónica** conforme a DGI Panamá
- 🌍 **Integración Multi-país** (Colombia MEYPAR, otros)
- 🏪 **Sistemas POS** (Lightspeed, Maxgym, Shopify)
- 💳 **Procesamiento de Pagos** con precisión decimal
- 🏢 **Gestión Multi-tenant** de organizaciones
- 📊 **Sincronización Sage 50cloud** via ACICloud API
- 🔐 **Validación RUC** para cumplimiento fiscal panameño

---

## 📚 Documentación Disponible

### 🔐 APIs Principales

| API | Descripción | Enlace |
|-----|-------------|--------|
| **🔐 Autenticación** | Sistema de login/logout y gestión de tokens | [Ver Docs](https://aghabrego.github.io/docucenter-public/authentication-api/) |
| **🏢 Organizaciones** | Gestión multi-tenant de empresas fiscales | [Ver Docs](https://aghabrego.github.io/docucenter-public/organizations-api/) |
| **📍 Ubicaciones** | Provincias, distritos y corregimientos de Panamá | [Ver Docs](https://aghabrego.github.io/docucenter-public/locations-api/) |
| **🧾 Facturación Electrónica** | Emisión de documentos fiscales DGI Panamá | [Ver Docs](https://aghabrego.github.io/docucenter-public/fe-api/) |
| **💼 Sage ACICloud** | Sincronización con Sage 50cloud | [Ver Docs](https://aghabrego.github.io/docucenter-public/sage-acicloud-api/) |
| **☁️ ACICloud General** | API REST principal de APC Panamá | [Ver Docs](https://aghabrego.github.io/docucenter-public/acicloud-api/) |

### 🛠️ Documentación Técnica

| Tema | Descripción | Enlace |
|------|-------------|--------|
| **🇨🇴 MEYPAR Colombia** | Integración con sistema de parqueaderos | [Ver Docs](https://aghabrego.github.io/docucenter-public/meypar-colombia-api/) |
| **💳 Sincronización Pagos** | Solución automática de pagos multi-sistema | [Ver Docs](https://aghabrego.github.io/docucenter-public/solucion-sincronizacion-pagos/) |
| **🏋️ Validación RUC MaxGym** | Sistema específico para integración MaxGym | [Ver Docs](https://aghabrego.github.io/docucenter-public/maxgym-ruc-validation/) |
| **📋 Validación RUC CreateSale** | Validación en proceso de ventas | [Ver Docs](https://aghabrego.github.io/docucenter-public/createSale-ruc-validation/) |
| **⚡ Circuit Breaker MySQL** | Optimización de conexiones de base de datos | [Ver Docs](https://aghabrego.github.io/docucenter-public/mysql-optimization-circuit-breaker/) |
| **🧮 Corrección Cálculo Pagos** | Mejoras en precisión decimal | [Ver Docs](https://aghabrego.github.io/docucenter-public/payment-calculation-fix/) |
| **👥 Optimización Cliente-Proveedor** | Mejoras de rendimiento en gestión | [Ver Docs](https://aghabrego.github.io/docucenter-public/customer-supplier-optimizations/) |

### 📋 Índice Completo
- **🗂️ [Índice de APIs](https://aghabrego.github.io/docucenter-public/apis/)** - Catálogo completo de endpoints organizados por categoría

---

## 🚀 Inicio Rápido

### 1. Autenticación

```bash
curl -X POST https://apconpanama.me/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "tu@email.com",
    "password": "tu_password",
    "device_name": "Mi App"
  }'
```

### 2. Obtener Token y Organizaciones

```javascript
// Login
const loginResponse = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    login: 'user@empresa.com',
    password: 'password123',
    device_name: 'Mi App Web'
  })
});

const { access_token } = loginResponse.data.data;

// Configurar headers para requests subsecuentes
const headers = {
  'Authorization': `Bearer ${access_token}`,
  'Content-Type': 'application/json'
};

// Obtener organizaciones
const orgsResponse = await fetch('/api/v1/organizations', { headers });
```

### 3. Crear Factura Electrónica

```javascript
// Emitir factura electrónica
const invoiceResponse = await fetch('/api/v1/fe/emit_json_object', {
  method: 'POST',
  headers: {
    ...headers,
    'X-Organization-ID': '1' // ID de tu organización
  },
  body: JSON.stringify({
    document_type: '01', // Factura
    customer: {
      identification_type: '04', // RUC
      identification: '1234567890-1-2024',
      name: 'Cliente Ejemplo S.A.'
    },
    items: [{
      code: 'PROD001',
      description: 'Producto de ejemplo',
      quantity: 2,
      unit_price: 50.00
    }]
  })
});
```

---

## 🏗️ Arquitectura Técnica

### Stack Tecnológico
- **Backend**: Laravel 9.x Multi-tenant
- **Base de Datos**: MySQL 8.0 con Circuit Breaker
- **Cache/Queues**: Redis
- **Documentación**: MkDocs Material
- **Deploy**: GitHub Pages
- **Integración**: Docker Compose

### Patrones Implementados
- **Multi-tenancy**: Organizaciones independientes
- **Circuit Breaker**: Resiliencia en conexiones DB
- **Queue Jobs**: Procesamiento asíncrono
- **RUC Validation**: Cumplimiento fiscal Panamá
- **Decimal Precision**: Cálculos exactos de pagos

---

## 🔐 Seguridad y Cumplimiento

### Certificaciones
- ✅ **DGI Panamá**: Facturación electrónica certificada
- ✅ **PAC Providers**: TheFactoryHKA, Alanube
- ✅ **PCI DSS**: Procesamiento seguro de pagos
- ✅ **Multi-tenant**: Aislamiento de datos por organización

### Proveedores Integrados
- **🏪 POS**: Lightspeed Serie R, Maxgym, Shopify
- **🧾 PAC**: TheFactoryHKA, Alanube (Panamá)
- **🇨🇴 Colombia**: MEYPAR (parqueaderos y facturación)
- **💼 Contabilidad**: Sage 50cloud via ACICloud API
- **🏛️ Fiscal**: DGI Panamá (documentos electrónicos)

---

## 🛠️ Desarrollo Local

### Repositorio Principal (Privado)
```bash
# Clonar repositorio principal
git clone git@github.com:aghabrego/docucenter.git
cd docucenter

# Configurar Docker
docker-compose up -d

# Instalar dependencias
docker-compose exec laravel.test composer install
docker-compose exec laravel.test php artisan migrate
```

### Documentación (Público)
```bash
# Clonar este repositorio
git clone https://github.com/aghabrego/docucenter-public.git
cd docucenter-public

# Ver documentación localmente
python -m http.server 8000
# Abrir: http://localhost:8000
```

---

## 📊 Casos de Uso Principales

### 🏪 Comercios y Tiendas
- Integración con POS Lightspeed
- Facturación electrónica automática
- Sincronización con Shopify
- Validación de clientes por RUC

### 🏋️ Gimnasios (MaxGym)
- Gestión de membresías
- Facturación recurrente
- Validación RUC específica
- Reportes de pagos

### 🅿️ Parqueaderos (Colombia)
- Sistema MEYPAR integrado
- Facturación electrónica Colombia
- Gestión de tarifas dinámicas
- Reportes operacionales

### 🏢 Empresas Contables
- Sincronización Sage 50cloud
- Multi-client management
- Reportes fiscales automáticos
- Cumplimiento DGI Panamá

---

## 🤝 Contribuir

### Para Desarrolladores Externos
Este es un repositorio de **documentación pública**. Para contribuir:

1. **Issues**: Reportar problemas de documentación
2. **Pull Requests**: Mejoras en la documentación
3. **Feedback**: Sugerencias de claridad y ejemplos

### Para Desarrolladores APC
El código fuente principal está en el repositorio privado:
- **Repositorio**: `git@github.com:aghabrego/docucenter.git`
- **Documentación Fuente**: `/docs/` en repositorio privado
- **Build**: Automático desde commit del repo principal

---

## 📞 Soporte y Contacto

### 🆘 Soporte Técnico
- **Email**: [soporte@apconpanama.me](mailto:soporte@apconpanama.me)
- **GitHub Issues**: [Reportar Problema](https://github.com/aghabrego/docucenter/issues)
- **Documentación**: [Centro de Ayuda](https://aghabrego.github.io/docucenter-public/)

### 🏢 APC Panamá
- **Sitio Web**: [home.apconpanama.me](https://home.apconpanama.me/)
- **GitHub**: [@aghabrego](https://github.com/aghabrego)
- **LinkedIn**: [APC Panamá](https://linkedin.com/company/apc-panama)

### 📋 Recursos Adicionales
- **Status Page**: [status.apconpanama.me](https://status.apconpanama.me)
- **API Reference**: [Documentación Completa](https://aghabrego.github.io/docucenter-public/apis/)
- **Changelog**: [Historial de Cambios](https://github.com/aghabrego/docucenter-public/commits/gh-pages)

---

## 📄 Licencia y Términos

- **Código Principal**: Propietario - APC Panamá
- **Documentación**: Pública para desarrolladores
- **APIs**: Requieren autenticación y suscripción
- **Uso Comercial**: Contactar a APC Panamá

### Disclaimer
Esta documentación es para fines informativos. El acceso a las APIs requiere autorización previa de APC Panamá.

---

<div align="center">

**Desarrollado con ❤️ por [APC Panamá](https://home.apconpanama.me/)**

[![APC Panamá](https://home.apconpanama.me/wp-content/uploads/2024/03/logo3.png)](https://home.apconpanama.me/)

*Facturación Electrónica • Gestión Empresarial • Cumplimiento Fiscal*

</div>
