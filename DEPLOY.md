# MiMargen Landing — Deployment Guide

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
