# Design: Migrate Astro Landing to PHP landing.php

## Technical Approach

Single `landing.php` file that replaces the Astro-generated `dist/index.html`. CSS is extracted from the Astro build output (Tailwind v4 produces a single compiled CSS file). All 10 section components become inline PHP template sections. The calculator widget's vanilla JS is copied verbatim. PlatformSettings values are injected via PHP echo statements with fallback to approved copy.

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|---|---|---|---|
| **Template structure** | Single `landing.php` file | Multiple PHP includes/partials | Simpler deployment, fewer files to manage. Can refactor to partials later if the file grows too large (~800-1000 lines expected). |
| **CSS delivery** | Extract from Astro build output | Re-implement Tailwind in PHP, CDN | Tailwind v4 requires Node.js/Vite. PHP backend has no Node. Astro build already produces optimized CSS (~36KB). Extract and serve as static file. |
| **Font delivery** | Copy woff2 from Astro dist | Google Fonts CDN, system fonts only | Astro already self-hosts Inter Variable with latin subset. Copy the woff2 files to PHP public/fonts/. Avoids third-party DNS lookup. |
| **Dynamic content** | PHP echo with fallback defaults | Template engine (Twig, Blade), JS hydration | PHP echo is simplest. No template engine dependency. Fallbacks ensure the page renders even if PlatformSettings is missing a field. |
| **Calculator JS** | Inline `<script>` in landing.php | Separate .js file | Astro already inlines the calculator JS. Keeping it inline avoids an extra HTTP request. The JS is ~3KB minified. |
| **Lead form placement** | Inline in FinalCTA section | Separate section, modal, redirect | Replaces the "Empezar gratis" CTA button with email + submit. Matches existing PHP backend expectation for lead capture. |
| **Build script** | Shell script + package.json postbuild | PHP-based asset copier, manual copy | Shell script is simplest. Can be run as `pnpm build && ./build-assets.sh`. Postbuild hook in package.json automates it. |
| **SEO meta tags** | PHP echo with htmlspecialchars() | Static HTML, JS injection | PHP echo allows dynamic canonical URL and OG image path. htmlspecialchars() prevents XSS from PlatformSettings values. |

## Data Flow

```
Development:
  Astro src/ ──→ pnpm build ──→ dist/
                                    ├── _astro/index.*.css  (extracted → PHP public/css/landing.css)
                                    ├── _astro/*.woff2      (copied → PHP public/fonts/)
                                    ├── favicon.svg         (copied → PHP public/)
                                    ├── sitemap.xml         (copied → PHP public/)
                                    └── robots.txt          (copied → PHP public/)

Deployment:
  User requests mimargen.cl (apex domain)
    ──→ core/bootstrap.php detects UNKNOWN context
    ──→ serves landing.php
    ──→ landing.php loads PlatformSettings::load($rootDataDir)
    ──→ renders HTML with settings + fallbacks
    ──→ browser loads /css/landing.css, /fonts/*.woff2, /favicon.svg

Lead capture:
  User submits form in FinalCTA
    ──→ POST to landing.php
    ──→ PHP backend stores via JsonStorage in data/_platform/leads
    ──→ Redirect or success message
```

## File Changes

| File | Action | Description |
|---|---|---|
| `landing.php` (PHP backend root) | Create | Single template: head, header, 10 sections, footer, inline JS |
| `public/css/landing.css` (PHP backend) | Create | Extracted Tailwind CSS from Astro build (~36KB) |
| `public/fonts/inter-*.woff2` (PHP backend) | Copy | 8 Inter Variable woff2 files from Astro dist |
| `public/favicon.svg` (PHP backend) | Copy | From Astro dist |
| `public/sitemap.xml` (PHP backend) | Copy | From Astro dist |
| `public/robots.txt` (PHP backend) | Copy | From Astro dist |
| `build-assets.sh` (Astro project root) | Create | Shell script to copy build output to PHP backend |
| `package.json` postbuild (Astro project) | Modify | Add `"postbuild": "./build-assets.sh"` |

## Interfaces / Contracts

### PlatformSettings Interface (assumed)

```php
$settings = PlatformSettings::load($rootDataDir);
// Returns object or associative array with:
//   contact_email: string
//   contact_phone: string
//   contact_whatsapp: string
//   contact_city: string
//   hero_title: string
//   hero_lead: string
//   social_linkedin: string
//   social_instagram: string
```

### Helper Function for Settings with Fallback

```php
function setting($settings, $key, $default) {
    return isset($settings[$key]) && !empty($settings[$key])
        ? htmlspecialchars($settings[$key], ENT_QUOTES, 'UTF-8')
        : htmlspecialchars($default, ENT_QUOTES, 'UTF-8');
}
```

### Lead Form Contract (assumed)

```html
<form method="POST" action="landing.php">
  <input type="hidden" name="action" value="capture_lead">
  <input type="text" name="name" required>
  <input type="email" name="email" required>
  <input type="tel" name="phone">
  <button type="submit">Empezar gratis</button>
</form>
```

**Note**: Exact field names and CSRF token requirements must be verified against the actual PHP backend.

### CSS Path Mapping

```
Astro dist:  /_astro/index.CurmVEiO.css  →  PHP public: /css/landing.css
Astro dist:  /_astro/inter-latin-wght-normal.Dx4kXJAl.woff2  →  PHP public: /fonts/inter-latin-wght-normal.woff2
```

The CSS file contains `@font-face` rules referencing `/_astro/inter-*.woff2` paths. These must be updated to `/fonts/inter-*.woff2` during the copy step, OR the fonts must be placed at `/_astro/` in the PHP public directory (simpler — no CSS modification needed).

**Recommended**: Place fonts at `/fonts/` and add a `sed` step to the build script to update paths in the CSS:

```bash
sed -i '' 's|/_astro/inter-|/fonts/inter-|g' public/css/landing.css
```

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| **Visual parity** | PHP landing matches Astro dist/index.html | Side-by-side screenshot comparison at 375px, 768px, 1280px |
| **Dynamic settings** | PlatformSettings values render correctly | Mock PlatformSettings with test values, verify output |
| **Fallback behavior** | Page renders when PlatformSettings is empty | Remove settings file, verify all fallbacks activate |
| **Lead capture** | Form POST stores lead via JsonStorage | Submit test form, verify data/_platform/leads/ |
| **Calculator** | Widget computes accurately, rate limiting works | Same test matrix as Astro version (manual) |
| **SEO** | Meta tags, JSON-LD, sitemap, robots.txt | View source, Google Rich Results Test |
| **CSS loading** | All styles render, no missing classes | Browser dev tools → Network tab → verify 200 for landing.css |
| **Font loading** | Inter Variable renders, no FOIT | Browser dev tools → Network tab → verify 200 for woff2 files |

## Migration / Rollout

1. **Build assets**: Run `pnpm build` in Astro project → `./build-assets.sh` copies to PHP backend
2. **Deploy landing.php**: Upload to PHP backend root
3. **Verify**: Access `mimargen.cl` → verify visual parity with Astro staging
4. **Test lead capture**: Submit test form → verify JsonStorage
5. **Monitor**: Check error logs for PHP warnings/missing settings

Rollback: Rename `landing.php` to `landing.php.bak` — backend routing handles fallback.

## Open Questions

- [ ] **Exact PlatformSettings field names** — must verify against actual `PlatformSettings::load()` implementation
- [ ] **Lead form field names and CSRF requirements** — must verify against PHP backend's lead capture handler
- [ ] **PHP backend public directory path** — where should CSS/fonts be placed? (`public/`, `assets/`, `static/`?)
- [ ] **Logo data URL** — does the PHP backend provide a logo data URL for UNKNOWN context? If so, should it replace the SVG icon in the header?
- [ ] **OG image** — does `dist/og-image.png` exist? The Astro project references `/og-image.png` but it may not have been created yet.
- [ ] **PHP version** — what PHP version does the backend run? (affects available functions, array syntax)
