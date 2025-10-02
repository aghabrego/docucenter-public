# Copilot Instructions: Docucenter Documentation

## Project Overview

**Docucenter** is a comprehensive electronic invoicing and business management system developed by APCON. This repository contains the **public documentation site** built with MkDocs Material, deployed to GitHub Pages.

## Documentation Guidelines

### Content and Style Rules
- **NO incluir texto**: "Implementado por: [cualquier entidad]" en documentaciones
- **NO usar iconos**: Evitar emojis y símbolos en títulos y contenido
- **Language**: Spanish (es) - all content and UI
- **Technical terms**: Keep English for code/API terms (e.g., "RUC", "PaymentCalculationHelper")
- **User-facing**: Translate to Spanish ("Validación de RUC", not "RUC Validation")

### ⚠️ IMPORTANT: Source Repository
**Main Repository**: `git@github.com:aghabrego/docucenter.git` (PRIVATE)  
- **Always consult this repository first** for latest code, documentation, and business logic
- This public repo only contains the generated static documentation
- All documentation updates must reference the private repository for accuracy

## Architecture & Key Concepts

### Documentation Architecture
- **Static Site Generator**: MkDocs Material (v9.6.16)
- **Deploy Target**: GitHub Pages (`gh-pages` branch)
- **Language**: Spanish (es) - all content and UI
- **Build Tool**: Generated HTML from Markdown sources (not present in this repo)

### Core Business Domain
Docucenter is a **multi-tenant Laravel application** for:
- **Electronic invoicing** (Panama tax compliance)  
- **Multi-country integrations** (Colombia MEYPAR, others)
- **Point-of-sale systems** (Lightspeed, Maxgym, Shopify integration)
- **Payment processing** with precision decimal handling
- **RUC validation** (Panama tax ID format validation)
- **Sage 50cloud integration** (ACICloud API for accounting sync)
- **Electronic document management** (FE API for fiscal documents)

## Key Patterns & Conventions

### Documentation Structure
```
/[feature-name]/index.html  # Individual feature documentation
/mkdocs.yml                 # MkDocs configuration
/search/search_index.json   # Search index (auto-generated)
```

### Content Patterns
- Each major feature has its own directory: `createSale-ruc-validation/`, `mysql-optimization-circuit-breaker/`, etc.
- Technical documentation includes **specific code examples** from the Laravel app
- **Problem-solution format**: "Problema identificado" → "Solución implementada" → "Resultados esperados"
- **Bilingual code comments**: Spanish descriptions with English variable names

### Business Logic Examples (from docs)
```php
// RUC Validation Pattern - Panama tax ID formats
$rucValidation = $this->validateAndCleanRuc($data['member']['documentNumber']);
// RUC types: 'company' (155757563-2-2024), 'person' (8-123-456), 'consumer' (0-0-0)

// Payment Calculation Pattern - Exact decimal handling  
$maxTotal = $this->numberFormat(max([$dTotRec, $dVTot]), 2);
$dVTot = $maxTotal; // KEY: Synchronize to avoid cent discrepancies

// FE API Pattern - Electronic document emission
POST /api/v1/fe/emit_json_object
{
  "document_type": "01", // 01=Factura, 02=Nota Crédito
  "customer": {
    "identification_type": "04", // RUC
    "identification": "1234567890-1-2024"
  }
}

// Sage ACICloud Integration - Customer sync
POST /api/acicloud/customer
{
  "customer_code": "CUST001",
  "ruc": "1234567890-1-2024",
  "credit_limit": 5000.00
}
```

## Development Workflows

### Documentation Updates
1. **Source Repository**: Markdown files and documentation sources live in private repository `git@github.com:aghabrego/docucenter.git`
2. **Always Consult Source**: When adding or updating documentation, ALWAYS reference the private repository for:
   - Latest Laravel code examples and patterns
   - Current business logic implementations
   - Updated API endpoints and integrations
   - Recent bug fixes and optimizations
3. **Build Process**: MkDocs generates static HTML → pushes to this `gh-pages` branch
4. **Deploy**: GitHub Pages auto-deploys from `gh-pages` branch
5. **Assets**: Material theme assets in `/assets/` (stylesheets, JS, search)

### Source Repository Access Pattern
```bash
# Always check the main repository for latest documentation sources
git clone git@github.com:aghabrego/docucenter.git
# Look for documentation in: docs/, README files, code comments, tests
```

### Multi-Tenant Context  
The main Docucenter app uses:
- **Docker Compose**: `laravel.test` service for development
- **Database per tenant**: MySQL 8.0 with tenant-specific schemas
- **Queue Jobs**: Redis-backed Laravel jobs (CreateSaleLightspeedJob, etc.)
- **Circuit Breaker**: Database connection reliability patterns

## Critical Integration Points

### External Systems
- **PAC providers**: TheFactoryHKA, alanube (Panama e-invoicing)
- **POS integrations**: Lightspeed Serie R, Maxgym, Shopify  
- **MEYPAR Colombia**: Parking management + e-invoicing API
- **Payment processors**: Multi-payment-method support
- **Sage 50cloud**: Bidirectional accounting sync via ACICloud API
- **DGI Panama**: Electronic fiscal document validation and emission

### Database Optimizations
```php
// Customer search optimization pattern (from docs)
if (strlen($this->searchTerm) < 2) return collect(); // Minimum search length
return DB::table(...)->limit(50)->get(); // Limit results for performance
```

### Job Processing Patterns
```php
// Circuit breaker pattern for database reliability
if (!$circuitBreaker->canExecute()) {
    $this->release(60); // Retry in 1 minute
    return;
}
```

## Testing & Quality

### Test Execution
```bash
# From documentation examples
docker-compose exec laravel.test vendor/bin/phpunit tests/Unit/Http/Requests/CreateSaleMaxgymRequestRucTest.php
```

### Key Test Coverage Areas (from docs)
- RUC validation for all Panama provinces (1-13, PE)
- Payment calculation precision (avoid cent discrepancies)
- Multi-tenant database connections
- Queue job reliability with circuit breaker

## Content Guidelines

### Language & Localization
- **Primary language**: Spanish
- **Technical terms**: Keep English for code/API terms (e.g., "RUC", "PaymentCalculationHelper")
- **User-facing**: Translate to Spanish ("Validación de RUC", not "RUC Validation")

### Documentation Format
- **Headers**: Use emoji prefixes (🚨 Problema, 🔧 Solución, 📊 Resultados)
- **Code blocks**: Include context comments in Spanish
- **Examples**: Real business scenarios (parking tickets, e-invoicing)

## Brand & Assets

- **Company**: APCON  
- **Logo URL**: `https://home.apconpanama.me/wp-content/uploads/2024/03/logo3.png`
- **GitHub**: `aghabrego/docucenter` (main app repo)
- **Colors**: Material theme with custom CSS for cards/grids

Remember: This is the **documentation site**, not the main application. Focus on clear technical communication for developers implementing or integrating with Docucenter's business logic patterns.

## Critical Workflow for AI Agents

### Before Any Documentation Task:
1. **ALWAYS ACCESS** the private repository: `git@github.com:aghabrego/docucenter.git` 
2. **EXTRACT** latest code examples, patterns, and business logic
3. **VERIFY** current implementations before documenting
4. **UPDATE** this public documentation with accurate information from the source

### Documentation Sources (in private repo):
- `/docs/` - Primary documentation files
  - `fe-api.md` - Electronic invoicing API (DGI Panama compliance)
  - `sage-acicloud-api.md` - Sage 50cloud integration endpoints  
  - `acicloud-api.md` - General ACICloud API documentation
  - `meypar-colombia-api.md` - Colombia parking system integration
  - `createSale-ruc-validation.md` - RUC validation implementation
  - `mysql-optimization-circuit-breaker.md` - Database reliability patterns
  - `payment-calculation-fix.md` - Precision decimal handling fixes
- `/app/` - Laravel code with business logic
- `/tests/` - Test examples and patterns  
- `/README.md` - Setup and architecture notes
- Code comments - Implementation details and reasoning
