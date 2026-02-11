
# 📚 Documentación de DocuCenter

Este directorio contiene la documentación técnica completa del sistema DocuCenter de facturación electrónica.

## 🔥 **Actualizaciones Recientes (Febrero 2026)**
- 🚀 [**Implementación Columnas Source**](technical/consolidation-source-columns-implementation.md) - Solución completa implementada y lista para deploy
- ✅ [**Sistema de Consolidación Multi-Organización**](technical/consolidation-documentation-status.md) - Verificación completa de documentación
- ✅ [**Estrategia Tablas Header-Detail**](technical/consolidation-header-detail-strategy.md) - Análisis y estrategia de consolidación
- ✅ [**Replicación de Bases de Datos**](technical/database-replication-analysis.md) - Arquitectura completa del sistema

## 📅 **Actualizaciones Septiembre 2025**
- ✅ [**Mejoras QuickBooks Completas**](QUICKBOOKS_IMPROVEMENTS_SUMMARY.md) - Simplificación payment lookup y reorganización
- ✅ [**Removal Notice**](technical/payment-lookup-removal-notice.md) - Documentación remoción funcionalidad
- ✅ [**Payment Methods Integration**](technical/quickbooks-payment-methods-integration.md) - Sistema análisis pagos QB

## �🗂️ Organización de la Documentación

### 📖 [Índice Principal](./index.md)
Portal principal de navegación con acceso a todas las secciones de documentación.

### 📁 Estructura de Directorios

```
docs/
├── 📖 index.md                    # Portal principal de documentación
├── 🔌 api/                        # Documentación de APIs
│   ├── fe/                        # APIs de Facturación Electrónica
│   ├── sage-acicloud/             # Integración Sage ACICloud
│   ├── organizations/             # APIs de Organizaciones
│   └── locations/                 # APIs de Ubicaciones
├── 📈 optimizations/              # Optimizaciones y mejoras
├── 🔧 technical/                  # Documentación técnica
│   └── TRANSACTION_SYSTEM_COMPLETE.md  # 🔄 Sistema de Transacciones y Reintentos
└── ✅ validations/                # Validaciones y pruebas
```

## 🚀 Inicio Rápido

### Para Desarrolladores
1. **[Portal Principal](./index.md)** - Comienza aquí para navegación completa
2. **[APIs](./api/)** - Documentación de endpoints y integraciones
3. **[🔄 Sistema de Transacciones](./technical/TRANSACTION_SYSTEM_COMPLETE.md)** - Sistema completo de reintentos y tracking
4. **[Validaciones](./validations/)** - Casos de prueba y validaciones

### Para Administradores
1. **[Configuraciones](./technical/)** - Configuración del sistema
2. **[Optimizaciones](./optimizations/)** - Mejoras de performance

## 🔄 Publicación Automática

Cada vez que hagas un _push_ a la rama principal (`master`), GitHub Actions generará y publicará el sitio web con MkDocs Material en GitHub Pages.

## ✏️ Cómo Contribuir

### Agregar Nueva Documentación
1. Crea un archivo `.md` en el directorio apropiado dentro de `docs/`
2. Añade la referencia en el archivo `mkdocs.yml` bajo la sección `nav`
3. Actualiza el índice correspondiente si es necesario
4. Haz _commit_ y _push_ a la rama principal
5. El sitio se actualizará automáticamente

### Estándares de Documentación
- **Uso de emojis**: Para facilitar navegación visual
- **Estructura clara**: Headers organizados jerárquicamente
- **Ejemplos prácticos**: Incluir código y casos de uso
- **Enlaces internos**: Facilitar navegación entre documentos

## ⚙️ Personalización

- **Favicon**: Sube tu favicon en el directorio `docs/` y actualiza `mkdocs.yml`
- **Logo**: Sube tu logo en el directorio `docs/` y actualiza `mkdocs.yml`
- **Tema**: Configuración en `mkdocs.yml` usando Material theme

## 🔗 Enlaces Útiles

- [MkDocs Material (Español)](https://squidfunk.github.io/mkdocs-material/setup/changing-language/#espa%C3%B1ol)
- [Guía de GitHub Pages](https://docs.github.com/es/pages/getting-started-with-github-pages/about-github-pages)
- [Portal Principal de Documentación](./index.md)
