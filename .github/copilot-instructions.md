# Instrucciones para Agentes de IA - DocuCenter Documentation

Este es un sitio de documentación **MkDocs Material** en español para DocuCenter, un sistema de facturación electrónica para Panamá. El proyecto contiene 456 archivos markdown organizados jerárquicamente.

## Arquitectura del Proyecto

```
docucenter-public/
├── docs/              # Archivos markdown fuente (EDITAR AQUÍ)
│   ├── api/          # Documentación de APIs (FE, Sage ACICloud, Organizations)
│   ├── technical/    # Documentación técnica y soluciones
│   ├── testing/      # Scripts de prueba y casos de uso
│   ├── integrations/ # Integraciones con sistemas externos
│   ├── optimizations/# Mejoras de performance
│   ├── validations/  # Validaciones y casos de prueba
│   └── index.md      # Portal principal de navegación
├── site/             # Sitio compilado por MkDocs (generado automáticamente)
├── [raíz]/           # Contenido de site/ copiado aquí para GitHub Pages
├── mkdocs.yml        # Configuración y navegación del sitio
├── fix_nav.sh        # Script de build automatizado
└── .venv/            # Entorno virtual Python con MkDocs
```

**CRÍTICO**: 
- Solo edita archivos en `docs/`
- El directorio `site/` se genera con `mkdocs build`
- El contenido se copia de `site/` a la raíz para despliegue en GitHub Pages
- Branch de trabajo: `gh-pages` (NO usar master/main)

## Workflow de Desarrollo Completo

### Opción 1: Build Automatizado (Recomendado)

```bash
# Ejecuta el script que hace todo el proceso
./fix_nav.sh
```

El script `fix_nav.sh`:
1. Configura `mkdocs.yml` 
2. Ejecuta `mkdocs build`
3. Copia contenido de `site/` a raíz con `cp -r site/* .`

### Opción 2: Build Manual

```bash
# 1. Activar entorno virtual
source .venv/bin/activate

# 2. Editar archivos en docs/
# Ejemplo: docs/testing/nueva-doc.md

# 3. Actualizar navegación en mkdocs.yml bajo 'nav:'
# Agregar: - Nueva Doc: testing/nueva-doc.md

# 4. Compilar sitio
mkdocs build

# 5. Copiar a raíz para GitHub Pages
cp -r site/* .

# ⚠️ IMPORTANTE: NO usar 'rsync --delete' porque borra .github/, docs/, etc.
```

### Verificación Local (Opcional)

```bash
source .venv/bin/activate
mkdocs serve  # Abre http://localhost:8000
```

### Publicar Cambios

```bash
# 1. Revisar cambios (incluye docs/ y archivos copiados de site/)
git status

# 2. Agregar TODOS los archivos necesarios
git add docs/testing/nueva-doc.md    # Fuente
git add testing/nueva-doc/           # HTML generado
git add site/testing/nueva-doc/      # En site/
git add search/search_index.json sitemap.xml sitemap.xml.gz  # Índices

# 3. Commit en ESPAÑOL, SIN emojis
git commit -m "docs: agregar documentacion de nueva funcionalidad"

# 4. Push a gh-pages
git push origin gh-pages

# La URL será: https://aghabrego.github.io/docucenter-public/testing/nueva-doc/
```

## Convenciones Estrictas

### Commits

```bash
# ✅ CORRECTO
git commit -m "docs: actualizar API de facturacion electronica"
git commit -m "fix: corregir enlaces rotos en seccion de testing"
git commit -m "feat: agregar documentacion de integracion QuickBooks"

# ❌ INCORRECTO
git commit -m "📝 Update docs"           # NO usar emojis en commits
git commit -m "Added new file"           # NO usar inglés
git commit -m "update"                   # Descripción poco clara
```

**Formato**: `tipo: descripción en español`
- `docs:` - Cambios de documentación
- `fix:` - Correcciones (enlaces, typos, errores)
- `feat:` - Nueva funcionalidad/documentación
- `refactor:` - Reorganización de contenido

### Documentación Markdown

**SÍ usar emojis** en archivos .md para navegación visual:

```markdown
# 🧪 Sistema de Testing DocuCenter

### 📦 **Campo Pedido de Compra Global**
### 🔍 **API Consulta de RUC**
### ✅ **Cambios Implementados**
```

**Estructura estándar de documentación técnica**:

```markdown
# Título del Cambio

**Fecha:** DD de mes de YYYY  
**Estado:** Completado/En Progreso/Pendiente  
**Tipo:** Implementación/Refactorización/Análisis

## Resumen
Descripción concisa del cambio o feature

## Cambios Realizados

### 1. Componente/Archivo Afectado
**Archivo:** `app/Models/NombreModelo.php`

Explicación detallada...

## Uso/Ejemplos
```bash
# Comandos de ejemplo
docker exec container comando
```

## Verificación
Pasos para validar los cambios
```

## Patrones y Convenciones

### Organización por Dominio

- **api/** - Endpoints REST, integraciones externas, ejemplos de uso
- **technical/** - Análisis técnicos, arquitectura, soluciones implementadas
- **testing/** - Scripts de prueba, casos de uso, validaciones funcionales
- **integrations/** - Documentación de conexiones (Sage, QuickBooks, Shopify, Lightspeed, Meypar, etc.)
- **optimizations/** - Mejoras de performance, índices DB, circuit breakers
- **validations/** - Reglas de negocio, validaciones RUC/documentos fiscales

### Referencias al Código Fuente

Esta documentación describe el sistema **DocuCenter** (Laravel/PHP) ubicado en `/home/weirdolabs/code/docucenter/`. Al documentar:

```markdown
**Archivo:** `app/Models/FeHeader.php`
**Archivo:** `app/Utils/ImportData.php`
**Comando:** `php artisan db:rename-column-in-organizations-table`
**Script:** `scripts/test-real-xml.php`
```

### Enlaces Internos

```markdown
# ✅ Usar enlaces relativos
Ver [Implementación Original](gPedComGl-dNroPed-implementation.md)
Ver [API de FE](../api/fe/fe-api.md)
Ver [Renombrado del Campo](gPedComlr-dNroPed-rename.md)

# ❌ Evitar URLs absolutas para contenido interno
Ver https://aghabrego.github.io/docucenter-public/testing/...
```

## Comandos Esenciales

```bash
# Entorno virtual
source .venv/bin/activate

# Build completo automatizado
./fix_nav.sh

# Build manual
mkdocs build
cp -r site/* .

# Servidor local
mkdocs serve

# Ver navegación configurada
cat mkdocs.yml | grep -A 100 "^nav:"

# Buscar en documentación
grep -r "término de búsqueda" docs/

# Contar archivos
find docs -name "*.md" | wc -l

# Ver archivos modificados después de build
git status --short | grep -E "(docs/|site/|testing/|api/)"
```

## Contexto del Dominio

**DocuCenter** - Sistema de facturación electrónica para Panamá que:

- Procesa XMLs de la DGI (Dirección General de Ingresos de Panamá)
- Gestiona facturas electrónicas multi-tenant por organización
- Valida RUCs panameños (formato: 8-123-456, E-8-123456, N-12-1234, etc.)
- Integra con:
  - **Sage ACICloud** - ERP
  - **QuickBooks** - Contabilidad
  - **Shopify, Lightspeed** - E-commerce
  - **MaxGym, Meypar, Kart21** - POS especializados

**Modelos principales**:
- `FeHeader` - Encabezado de factura electrónica
- `FeDetail` - Líneas de detalle
- `FePayment` - Formas de pago

**Campos recientes documentados**:
- `gPedComlr_dNroPed` - Número de pedido de compra global (renombrado de `gPedComGl_dNroPed`)

## URLs del Proyecto

- **Sitio publicado**: https://aghabrego.github.io/docucenter-public/
- **Repositorio**: https://github.com/aghabrego/docucenter-public
- **Código fuente DocuCenter**: `/home/weirdolabs/code/docucenter/` (repositorio diferente)

## Checklist Pre-Commit

Antes de hacer push, verifica:

1. ✅ Archivos editados en `docs/`, no directamente en raíz
2. ✅ `mkdocs.yml` actualizado con nuevos archivos en sección `nav:`
3. ✅ `mkdocs build` ejecutado sin errores
4. ✅ `cp -r site/* .` ejecutado para copiar a raíz
5. ✅ Enlaces internos funcionan (rutas relativas)
6. ✅ Mensaje de commit en español sin emojis
7. ✅ Se agregaron TODOS los archivos: docs/, site/, y archivos en raíz (testing/, api/, etc.)
8. ✅ Branch correcto: `gh-pages`

## Archivos Importantes a NO Borrar

- `.github/` - Instrucciones para agentes de IA
- `docs/` - Fuentes markdown
- `.venv/` - Entorno virtual Python
- `mkdocs.yml` - Configuración del sitio
- `fix_nav.sh` - Script de build
- `.git/` - Repositorio git

**Nunca usar `rsync --delete` o `rm -rf` en la raíz del proyecto**
