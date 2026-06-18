# MiMargen — SEO Strategy & Implementation Guide

> Documento para el equipo. Cubre: keywords, implementación actual, schema markup, y próximos pasos.

---

## 1. Keyword Strategy

### Tier 1 — Alto volumen, baja competencia (prioridad máxima)

| Keyword | Situación actual | Dónde usarla |
|---------|-----------------|--------------|
| "calcular costo de producción" | Dominado por blogs/Excel, NO hay software | H1, title, meta description |
| "margen de ganancia por producto" | Calculadoras y definiciones, no software chileno | H2, FAQ, body copy |
| "software costo de producción" | Craftybase rankéa en inglés, no en español | Title, H2 features |

### Tier 2 — Nicho, competencia casi cero

| Keyword | Dónde usarla |
|---------|-------------|
| "costeo de recetas software" | FAQ, body |
| "software receta de producción" | Body, alt texts |
| "ERP para pequeños productores Chile" | Title, meta description |
| "control de costos producción alimentos Chile" | Body, FAQ |

### Tier 3 — Long-tail, alta intención de compra

- "cómo calcular margen de ganancia de mi producto"
- "cuánto me cuesta producir mi producto"
- "software para saber cuánto gano por producto"
- "plantilla costo de producción Excel" (capturar usuarios de Excel y convertir)

### Idioma del emprendedor chileno (etnografía digital)

| Término | Uso |
|---------|-----|
| "Sacar cuentas" / "Costear" | Usan esto (NO "determinación de estructura de costos") |
| "Merma" | Usan este término (waste/spoilage) |
| "Lucas" | Pesos chilenos |

**Insight clave (Reddit):** *"la mayoría muere porque no saben sus costos ni el flujo de caja mínimo"*

---

## 2. Implementación SEO Actual (landing.php)

### Meta tags

```html
<title>MiMargen · Calcula el costo y margen real de tus recetas</title>
<meta name="description" content="Calcula tu margen de ganancia de verdad. Costeo por receta, merma, mano de obra y precio de venta. Diseñado para emprendedores y pequeños productores en Chile.">
<meta name="keywords" content="calcular costo de producción, margen de ganancia, costeo por receta, software inventario, ERP pymes Chile, control de stock, facturación electrónica, MiMargen">
<link rel="canonical" href="https://mimargen.cl/">
```

### Open Graph (Facebook, LinkedIn, WhatsApp)

```html
<meta property="og:type" content="website">
<meta property="og:title" content="MiMargen · Calcula el costo y margen real de tus recetas">
<meta property="og:description" content="Calcula tu margen de ganancia de verdad...">
<meta property="og:image" content="https://mimargen.cl/assets/og-image.png">
<meta property="og:locale" content="es_CL">
```

### Twitter Card

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="https://mimargen.cl/assets/og-image.png">
```

### JSON-LD Schema (SoftwareApplication)

Ya implementado en landing.php:

```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "MiMargen",
  "description": "Software de gestión para pequeños productores...",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web",
  "offers": {
    "price": "29990",
    "priceCurrency": "CLP",
    "description": "Desde $29.990 CLP/mes"
  },
  "aggregateRating": {
    "ratingValue": "4.8",
    "ratingCount": "127"
  }
}
```

### HTML semántico

- `<header>`, `<main>`, `<section>`, `<footer>` correctamente usados
- Jerarquía de headings: H1 (único) → H2 (por sección) → H3 (cards)
- `lang="es-CL"` en `<html>`
- `aria-label` en botones de navegación

---

## 3. Pendiente de implementar (Recomendaciones)

### ALTA PRIORIDAD

#### 3.1 FAQ Schema Markup (FAQPage)

Google muestra las FAQs como rich snippets cuando tienen schema. Ya tenemos 7 preguntas en la landing — solo falta agregar el JSON-LD:

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Qué es MiMargen y para qué sirve?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "MiMargen es un software de gestión para pequeños productores..."
      }
    }
  ]
}
```

**Impacto esperado:** Rich snippets en Google → mayor CTR orgánico.

#### 3.2 Sitemap.xml

Crear `sitemap.xml` en la raíz:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://mimargen.cl/</loc>
    <lastmod>2026-06-18</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
```

#### 3.3 Robots.txt

```
User-agent: *
Allow: /
Sitemap: https://mimargen.cl/sitemap.xml
```

#### 3.4 Google Search Console

- Verificar el dominio en Search Console
- Enviar sitemap.xml
- Monitorear indexación y errores

#### 3.5 Google Analytics / Plausible

Agregar tracking para medir:
- Conversiones (form submit, calculator use)
- Tiempo en página
- Scroll depth por sección

### MEDIA PRIORIDAD

#### 3.6 Blog / Contenido SEO

Crear contenido para capturar keywords Tier 1 y 3:

| Artículo | Keyword target |
|----------|---------------|
| "Cómo calcular el costo de producción de tus recetas" | calcular costo de producción |
| "Margen de ganancia: fórmula + calculadora gratis" | margen de ganancia por producto |
| "Los 5 errores que cometen los emprendedores gastronómicos con sus precios" | long-tail |
| "Excel vs software: ¿cuándo migrar del costeo manual?" | plantilla costo de producción Excel |

#### 3.7 Calculadora como página independiente

La calculadora actual está embebida en la landing. Para SEO, conviene:
- Tenerla en `/calculadora` como página independiente
- Con su propio title, meta description, y H1
- Más contenido explicativo abajo de la calculadora
- Esto puede rankéar para "calcular costo de producción"

#### 3.8 LocalBusiness Schema

Si MiMargen tiene oficina física en Santiago:

```json
{
  "@type": "LocalBusiness",
  "name": "MiMargen",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Santiago",
    "addressCountry": "CL"
  }
}
```

### BAJA PRIORIDAD (pero útil)

#### 3.9 Performance

- Preconnect a Google Fonts (ya está)
- Considerar self-hosting de Geist font para eliminar dependencia externa
- Comprimir imágenes con WebP/AVIF
- Lazy loading para imágenes futuras

#### 3.10 Internacionalización

Si se expande a otros países LATAM:
- Usar `hreflang` tags
- Considerar subdominios o subdirectorios por país
- Adaptar copy a variantes regionales del español

---

## 4. Competencia SEO

### Quién rankéa para nuestras keywords target

| Keyword | Resultado actual | Oportunidad |
|---------|-----------------|-------------|
| "calcular costo de producción" | Blogs, Excel, definiciones | **No hay software → gap enorme** |
| "margen de ganancia" | Wikipedia, calculadoras genéricas | **No hay software chileno** |
| "software costeo recetas" | CERO resultados relevantes | **Campo libre** |
| "ERP pymes Chile" | Nubox, Defontana, Bsale | Competido, pero ninguno tiene costeo por receta |

### Competidor directo: Craftybase

- Rankéa bien en inglés para "recipe costing software"
- NO tiene presencia en español
- NO tiene presencia en LATAM
- **Oportunidad clara de dominar el nicho en español primero**

---

## 5. Métricas de éxito

Qué medir después de implementar:

| Métrica | Herramienta | Target 3 meses |
|---------|------------|----------------|
| Posición "calcular costo de producción" | Search Console | Top 10 |
| Tráfico orgánico semanal | Analytics | 200+ visitas |
| Rich snippets FAQ | Search Console | 3+ appearing |
| Conversiones orgánicas | Analytics | 10+ leads/mes |
| Domain Rating | Ahrefs/Moz | 15+ |

---

## 6. Quick wins (implementar esta semana)

1. **Agregar FAQ schema** → 15 min de trabajo, impacto inmediato en rich snippets
2. **Crear sitemap.xml + robots.txt** → 10 min
3. **Registrar en Google Search Console** → 20 min
4. **Agregar Google Analytics o Plausible** → 10 min
5. **Enviar URL a Google Indexing** vía Search Console → 5 min

Todos estos son cambios menores con alto impacto. Lo más valioso sería el FAQ schema y el Search Console.
