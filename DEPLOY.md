# MiMargen Landing — Deployment Guide

## Integration Contract (OtterErp Backend)

**IMPORTANT**: `landing.php` is NOT called by direct URL. It is dispatched by `core/bootstrap.php`
when the resolved host matches `BASE_DOMAIN` (apex domain) and the context is `UNKNOWN`.

### PlatformSettings::load() Contract

```php
$rootDataDir = dirname(__DIR__) . '/data';
$settings    = PlatformSettings::load($rootDataDir);
```

| Key | Type | Default |
|-----|------|---------|
| `contact_email` | string\|null | null |
| `contact_phone` | string | `'+56 9 0000 0000'` |
| `contact_whatsapp` | string | `'56900000000'` (digits only, wa.me format) |
| `contact_city` | string | `'Santiago, Chile'` |
| `hero_title` | string | `'El sistema que ordena tu empresa de punta a punta.'` |
| `hero_lead` | string | (long paragraph) |
| `social_linkedin` | string\|null | null |
| `social_instagram` | string\|null | null |

**Never throws** — returns full defaults on corrupt/missing storage.

### Lead Form Contract

- **Method**: POST to same URL (`action="#"`)
- **Discriminator**: `<input type="hidden" name="action" value="lead">`
- **Honeypot**: `<input name="website">` — must be empty, silently discarded if filled
- **Required fields**: `nombre` (mb_strlen >= 2), `empresa` (mb_strlen >= 2), `email` (FILTER_VALIDATE_EMAIL)
- **Optional fields**: `telefono`, `mensaje`
- **No CSRF token** — protection is honeypot + action discriminator only

### Logo Methods

Logo is NOT in `load()`. Use separate methods:
```php
$brandLogo     = PlatformSettings::brandLogoDataUrl($rootDataDir); // data URL for inline
$logoInfo      = PlatformSettings::logoInfo($rootDataDir);         // ['mime' => ..., 'size' => ...]
$serveUrl      = PlatformSettings::brandLogoServeUrl($rootDataDir); // '/api/logo'
```

## Server Requirements

- **PHP**: 7.4+ (with `json`, `mbstring`, `openssl` extensions)
- **Web Server**: Apache 2.4+ with `mod_rewrite`, `mod_mime`, `mod_expires`, `mod_headers`
- **HTTPS**: Required for production (OG meta, SEO, form security)
- **Permissions**: Write access to `data/_platform/` directory

## Deploy Steps

### 1. Upload files to server

Upload all files to the public directory (usually `public_html/` or `www/`):

```
landing.php
.htaccess
core/
  ├── bootstrap.php
  ├── PlatformSettings.php
  ├── JsonStorage.php
  └── Encryption.php
assets/
  ├── landing.css
  ├── calculator.js
  └── fonts/
      └── *.woff2
data/_platform/
  └── .gitkeep
scripts/
  └── extract-assets.sh
```

### 2. Set directory permissions

```bash
chmod 750 data/_platform/
chown www-data:www-data data/_platform/  # or your web user
```

### 3. Configure PlatformSettings

In the backoffice (Ottertech admin panel), configure these settings for the apex domain (mimargen.cl):

| Setting | Value | Example |
|---------|-------|---------|
| `contact_email` | Email de contacto | `hola@mimargen.cl` |
| `contact_phone` | Teléfono | `+569XXXXXXXX` |
| `contact_whatsapp` | WhatsApp number | `569XXXXXXXX` |
| `contact_city` | Ciudad | `Santiago, Chile` |
| `hero_title` | Hero headline | `Conoce el margen real de cada producto que fabricas` |
| `hero_lead` | Hero subheadline | *(approved copy from Engram)* |
| `social_linkedin` | LinkedIn URL | `https://linkedin.com/...` |
| `social_instagram` | Instagram URL | `https://instagram.com/...` |
| Logo | Upload brand logo SVG/PNG | *(real MiMargen logo)* |

### 4. Configure APP_SECRET

In `core/bootstrap.php` or server environment, set `APP_SECRET` to a strong random string (used for lead encryption).

### 5. Verify DNS

Ensure `mimargen.cl` (apex domain) points to the server and resolves to the PHP backend.

## Post-Deploy Verification Checklist

### Visual
- [ ] All 10 sections render correctly on desktop (1440px)
- [ ] All 10 sections render correctly on mobile (375px)
- [ ] Header navigation works (desktop + mobile menu toggle)
- [ ] Footer displays correctly with social links
- [ ] CSS loads without errors (check browser dev tools Network tab)
- [ ] Fonts load correctly (Inter Variable, no fallback fonts)
- [ ] No 404 errors in browser console

### Functional
- [ ] Anchor navigation works (click nav links → scrolls to section)
- [ ] FAQ accordion opens/closes (native `<details>`)
- [ ] Calculator widget:
  - [ ] Add ingredient rows work
  - [ ] Remove ingredient rows work
  - [ ] Calculate button shows results
  - [ ] CLP formatting correct ($1.234.567)
  - [ ] Rate limiting works after 3 uses in a day
- [ ] Lead capture form:
  - [ ] Email validation works
  - [ ] Honeypot silently rejects bots
  - [ ] Success message shows after submission
  - [ ] Lead appears in backoffice / data storage

### SEO
- [ ] Canonical URL is correct (`https://mimargen.cl/`)
- [ ] OG meta tags render correctly (test with Facebook Sharing Debugger)
- [ ] Twitter Card meta tags render correctly
- [ ] JSON-LD schema present (check page source)
- [ ] Page title: "MiMargen · Calcula el costo y margen de tus recetas"
- [ ] Meta description present

### Performance
- [ ] CSS file loads (< 50KB)
- [ ] Fonts load (7 woff2 files)
- [ ] No render-blocking resources
- [ ] Lighthouse: Performance ≥ 90, Accessibility ≥ 95, SEO ≥ 95

## Troubleshooting

### CSS not loading
- Check `.htaccess` allows access to `assets/` directory
- Verify MIME type for `.css` is `text/css`
- Check file path: should be `/assets/landing.css`

### Fonts not loading (CORS error)
- Ensure CORS headers are set in `.htaccess` for `.woff2` files
- Check font paths in `landing.css` point to `/assets/fonts/`

### Form not saving leads
- Verify `data/_platform/` directory exists and is writable
- Check PHP error log for exceptions
- Verify `APP_SECRET` is set in bootstrap

### PlatformSettings not injecting values
- Verify backoffice settings are saved for the apex domain
- Check `PlatformSettings::load()` returns expected keys
- Fallback values should work if settings are missing

### Mobile menu not working
- Check JavaScript is loading (`calculator.js` should not interfere)
- Verify inline JS in header is not blocked by CSP

### Calculator not working
- Check `assets/calculator.js` loads (Network tab)
- Verify all element IDs match (`calc-ingredients`, `calc-calculate`, etc.)
- Check browser console for JavaScript errors
