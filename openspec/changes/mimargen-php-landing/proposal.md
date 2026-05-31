# Proposal: Migrate Astro Landing to PHP landing.php

## Intent

Migrate the existing Astro SSG landing page for MiMargen to a single `landing.php` template that integrates with the existing PHP backend architecture (dynamic subdomain routing, `PlatformSettings`, `JsonStorage` for leads). The visual design, copy, SEO, and calculator widget must be preserved exactly. The Astro project becomes the source of truth for CSS and HTML generation — the PHP template consumes the built assets.

## Scope

### In Scope
- Single `landing.php` template that replaces `dist/index.html` as the served page for UNKNOWN context (apex domain `mimargen.cl`)
- Extract Tailwind CSS v4 output from Astro build into a standalone CSS file served by the PHP backend
- Convert all 10 Astro section components into PHP template sections (pure HTML + `<?php echo ?>` for dynamic values)
- Integrate `PlatformSettings` variables into the template (contact_email, contact_phone, contact_whatsapp, contact_city, hero_title, hero_lead, social_linkedin, social_instagram)
- Lead capture form POST integration (already exists in PHP backend — just wire the form action)
- Preserve all SEO: meta tags, OG, Twitter Cards, JSON-LD, sitemap.xml, robots.txt
- Calculator widget remains as vanilla JS (no changes needed — already framework-agnostic)
- Header/Footer dynamic content from PlatformSettings
- Inter Variable font served as static files (already in Astro dist)

### Out of Scope
- Modifying the PHP backend routing (`core/bootstrap.php`) — assumed to already route UNKNOWN context to `landing.php`
- Modifying `PlatformSettings::load()` or `JsonStorage` — assumed to exist
- Subdomain tenant landing pages — this proposal covers ONLY the apex domain landing
- Astro project deletion — the Astro project remains as the CSS/design source of truth
- Backend lead processing — already exists, just needs form action wiring

## Capabilities

### New Capabilities
- `php-landing-template`: Single `landing.php` file with all 10 sections as PHP template sections, PlatformSettings integration, and lead capture form
- `extracted-css-pipeline`: Build process to extract Tailwind CSS from Astro build output into a standalone file the PHP backend can serve
- `dynamic-settings-binding`: PHP echo statements that inject PlatformSettings values into the approved copy

### Modified Capabilities
- `calculator-widget`: No changes to logic — only the HTML wrapper becomes PHP template syntax instead of Astro
- `seo-foundation`: Meta tags become PHP echo statements for dynamic URL/OG image paths; JSON-LD remains static
- `site-shell`: BaseLayout becomes the `<head>` section of landing.php with PHP variable injection

## Approach

### CSS Strategy: Extract from Astro Build

Tailwind CSS v4 uses the Vite plugin (`@tailwindcss/vite`) which compiles CSS at build time. The Astro build already produces a single CSS file in `dist/_astro/index.*.css` (~36KB). This is the **source of truth** for all styles.

**Approach**: After `pnpm build`, copy the CSS file from `dist/_astro/` to the PHP backend's public assets directory (e.g., `public/css/landing.css`). The filename hash changes on every build, so we use a build script or a fixed symlink.

```bash
# Build script (runs after astro build)
cp dist/_astro/index.*.css ../php-backend/public/css/landing.css
cp dist/_astro/*.woff2 ../php-backend/public/fonts/
cp dist/favicon.svg ../php-backend/public/
cp dist/og-image.png ../php-backend/public/  # if exists
```

**Alternative considered**: Re-implement Tailwind in the PHP project. **Rejected** — Tailwind v4 requires Node.js/Vite build step. The PHP backend doesn't have Node. Extracting from Astro build is the only viable path.

**Alternative considered**: CDN-hosted Tailwind. **Rejected** — Tailwind v4 CDN doesn't support custom theme tokens (brand colors, cream backgrounds, Inter font). We need the custom `@theme` block from `global.css`.

### Template Structure

The `landing.php` is a single file (no partials needed for MVP) with this structure:

```php
<?php
// landing.php — served when host === BASE_DOMAIN (UNKNOWN context)
require_once __DIR__ . '/core/bootstrap.php';
$settings = PlatformSettings::load($rootDataDir);
?>
<!DOCTYPE html>
<html lang="es-CL">
<head>
  <!-- Meta tags with PHP echo for dynamic values -->
  <link rel="stylesheet" href="/css/landing.css">
  <!-- JSON-LD (static) -->
</head>
<body class="font-sans antialiased text-slate-800 bg-cream-50">
  <!-- Header with dynamic social links -->
  <header>...</header>

  <main>
    <!-- Hero with PlatformSettings: hero_title, hero_lead -->
    <section id="hero">...</section>

    <!-- PainPoints (static copy) -->
    <section id="producto">...</section>

    <!-- HowItWorks (static copy) -->
    <section id="como-funciona">...</section>

    <!-- Features (static copy) -->
    <section id="features">...</section>

    <!-- WhoItIsFor (static copy) -->
    <section id="para-quien">...</section>

    <!-- Calculator (vanilla JS, no changes) -->
    <section id="calculadora">...</section>

    <!-- Testimonials (static copy) -->
    <section id="testimonios">...</section>

    <!-- Pricing (static copy) -->
    <section id="precios">...</section>

    <!-- FAQ (static copy) -->
    <section id="faq">...</section>

    <!-- FinalCTA with dynamic contact links -->
    <section id="cta-final">...</section>
  </main>

  <!-- Footer with dynamic contact info -->
  <footer>
    <!-- PlatformSettings: contact_email, contact_phone, contact_whatsapp, contact_city, social_linkedin, social_instagram -->
  </footer>

  <!-- Mobile menu JS (inline, from Astro build) -->
  <script>...</script>

  <!-- Calculator JS (inline, from Astro build) -->
  <script>...</script>
</body>
</html>
```

### PlatformSettings Integration Map

| PlatformSetting | Landing Location | Fallback |
|---|---|---|
| `hero_title` | Hero `<h1>` | Approved copy: "Conoce cuánto ganas realmente con cada producto" |
| `hero_lead` | Hero `<p>` lead text | Approved copy (the long paragraph) |
| `contact_email` | Footer email link, support link | `hola@mimargen.cl` |
| `contact_phone` | Footer phone link | Not displayed |
| `contact_whatsapp` | FinalCTA WhatsApp button, Footer WhatsApp | `https://wa.me/569XXXXXXXX` |
| `contact_city` | Footer location text | "Chile" |
| `social_linkedin` | Footer LinkedIn link | `https://linkedin.com/company/mimargen` |
| `social_instagram` | Footer Instagram link | `https://instagram.com/mimargen` |

### Lead Capture Form

The PHP backend already handles lead capture via POST to `landing.php`. The form needs:
- `action="landing.php"` (or current URL)
- `method="POST"`
- Fields: `name`, `email`, `phone` (whatever the backend expects)
- Hidden CSRF token if the backend requires it

The form should be placed in the **FinalCTA section** as a replacement for the current "Empezar gratis" button, OR as a separate section between Pricing and FinalCTA.

**Recommended**: Add a lead capture form in the FinalCTA section — replace the "Empezar gratis" button with an inline email input + submit button. This matches the existing PHP backend expectation.

### SEO Preservation

| SEO Element | Astro Approach | PHP Approach |
|---|---|---|
| Meta tags | Static in BaseLayout | PHP echo with `htmlspecialchars()` |
| Canonical URL | `Astro.url.pathname` | `$_SERVER['REQUEST_URI']` or hardcoded `/` |
| OG Image | `/og-image.png` | `/og-image.png` (static file) |
| JSON-LD | `set:html={JSON.stringify(jsonLd)}` | `<script type="application/ld+json"><?= json_encode($jsonLd) ?></script>` |
| Sitemap | `@astrojs/sitemap` generates | Static `sitemap.xml` file (copy from Astro dist) |
| Robots | Static `robots.txt` | Static `robots.txt` (copy from Astro dist) |
| Font files | Astro copies woff2 to dist | Copy woff2 from Astro dist to PHP public/fonts/ |

### Calculator Widget

**Zero changes needed.** The calculator is vanilla JS with:
- DOM manipulation (no framework)
- localStorage rate limiting (client-side only)
- No server communication

The HTML structure and `<script>` block can be copied verbatim from the Astro build output into the PHP template. The only change is wrapping it in PHP template syntax for the surrounding section.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `landing.php` (PHP backend) | Create | Single template file with all 10 sections |
| `public/css/landing.css` (PHP backend) | Create | Extracted Tailwind CSS from Astro build |
| `public/fonts/` (PHP backend) | Create | Inter Variable woff2 files from Astro build |
| `public/favicon.svg` (PHP backend) | Copy | From Astro dist |
| `public/og-image.png` (PHP backend) | Copy | From Astro dist (if exists) |
| `public/sitemap.xml` (PHP backend) | Copy | From Astro dist |
| `public/robots.txt` (PHP backend) | Copy | From Astro dist |
| `build-assets.sh` (new script) | Create | Script to copy Astro build output to PHP backend |
| Astro project `src/` | No changes | Remains source of truth for design/CSS |

## Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **PHP not available locally for testing** | **Certain** | **High** | PHP code must be written but cannot be validated locally. Requires testing on the actual PHP server after deployment. All HTML/CSS/JS can be validated via Astro build output. |
| Tailwind CSS hash filename changes on every build | Certain | Medium | Build script must handle glob pattern or use a fixed rename step. Consider adding a postbuild script to `package.json`. |
| PlatformSettings structure differs from assumptions | Medium | High | The proposal assumes specific field names. Must verify against actual `PlatformSettings::load()` return structure before implementation. |
| Lead form field names don't match backend expectations | Medium | High | Must verify the exact field names the PHP backend expects (JsonStorage keys). |
| Font loading path differs in PHP context | Low | Medium | Astro builds with relative paths (`/_astro/inter-*.woff2`). PHP needs `/fonts/inter-*.woff2`. CSS may need path adjustment or fonts must be at the same relative path. |
| CSRF token requirement | Medium | Medium | If the PHP backend requires CSRF tokens on POST, the form must include a hidden field. Must verify. |
| Logo served as data URL in UNKNOWN context | Low | Low | The PHP backend serves logo as data URL. The header logo can use this or fall back to the SVG icon already in the Astro template. |
| Browser caching of CSS with changing hash | Low | Low | If we rename to fixed `landing.css`, browsers may cache stale CSS. Solution: add version query param `?v=BUILD_TIMESTAMP` or use cache-busting headers. |

## Rollback Plan

1. **Pre-deployment**: The PHP backend already serves something for UNKNOWN context. If `landing.php` doesn't exist or fails, the backend should fall back to its existing behavior.
2. **Post-deployment**: Rename `landing.php` to `landing.php.bak` — the backend routing should handle the missing file gracefully (or revert to previous landing).
3. **Asset rollback**: If CSS breaks, revert the `public/css/landing.css` file to the previous version. The Astro project remains unchanged as the source of truth.

## Dependencies

- **PHP backend** with `core/bootstrap.php`, `PlatformSettings`, `JsonStorage`, `Encryption` classes — must already exist
- **Astro project** build output — source of CSS, fonts, and static assets
- **Node.js/pnpm** — to run `astro build` and extract assets
- **PHP server** for testing — cannot test locally (PHP not installed on dev machine)

## Success Criteria

- [ ] `landing.php` renders identically to Astro `dist/index.html` for all 10 sections
- [ ] PlatformSettings values correctly override defaults in header/footer/hero
- [ ] Lead capture form POSTs successfully and stores via JsonStorage
- [ ] Calculator widget functions identically (rate limiting, computation, results display)
- [ ] All SEO meta tags render correctly (verified via view-source)
- [ ] JSON-LD validates via Google Rich Results Test
- [ ] Tailwind CSS loads correctly (no missing styles, no console errors)
- [ ] Inter Variable font loads from PHP public directory
- [ ] Mobile responsive layout works at 375px, 768px, 1280px
- [ ] Build script (`build-assets.sh`) successfully copies all assets from Astro dist to PHP public
