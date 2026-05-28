# Design: MiMargen Landing Page

## Technical Approach

Static Astro site (SSG) with island architecture. Only the calculator widget hydrates client-side JS — all other sections compile to zero-JS HTML/CSS. Tailwind CSS v4 via `@astrojs/tailwind`. TypeScript strict mode. Deploys as static files to Vercel/Netlify/Cloudflare Pages.

Maps to proposal: 10 section components + footer compose `index.astro` inside `BaseLayout.astro`. Calculator is the single interactive island. SEO foundation baked into the layout shell.

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|----------|--------|-------------|-----------|
| **Framework** | Astro SSG | Next.js, Remix, plain HTML | Zero-JS output for static sections, island architecture for the one interactive piece. Best perf/SEO tradeoff for a landing page. |
| **Calculator runtime** | Vanilla JS (`<script>` in `.astro` file) | React island, Preact island, Alpine.js | Simple arithmetic + DOM manipulation. No framework needed. Saves ~30-40KB of hydration JS. Bundle stays under 5KB. |
| **CSS** | Tailwind CSS v4 via `@astrojs/tailwind` | Vanilla CSS, CSS Modules, UnoCSS | Utility-first matches rapid landing page development. Built-in purge keeps bundle small. Team familiarity. |
| **Font loading** | `@fontsource/inter` (self-hosted) | Google Fonts `<link>`, system fonts only | Self-hosted avoids third-party DNS lookup + connection. `font-display: swap` prevents FOIT. Latin subset only (~20KB woff2). |
| **Rate limiting** | localStorage with rolling-window timestamps | Server-side API, cookies, no limiting | Client-side is acceptable for MVP lead magnet. No backend needed. Rolling window (not calendar day) prevents midnight edge cases. |
| **FAQ accordion** | Native `<details>`/`<summary>` | JS-powered accordion, headless UI lib | Zero JS, keyboard-accessible by default, screen-reader friendly. Matches spec SE-05/LS-04. |
| **SEO schema** | Inline `<script type="application/ld+json">` in BaseLayout | Astro integration, external JSON file | Simplest approach. FAQ schema generated from component data at build time via Astro frontmatter. |
| **Sitemap** | `@astrojs/sitemap` integration | Manual sitemap, custom build script | Auto-generates from page routes. Standard Astro ecosystem tool. |

## Data Flow

```
Build time:
  Astro pages ──→ Static HTML + CSS (zero JS for sections)
       │
       └──→ Calculator.astro ──→ HTML shell + inline <script> (vanilla JS)
       └──→ BaseLayout ──→ <head> with meta, OG, JSON-LD, font imports
       └──→ @astrojs/sitemap ──→ dist/sitemap.xml

Runtime (client):
  User loads page ──→ HTML renders instantly (no JS blocking)
       │
       └──→ Calculator island hydrates (client:load)
              │
              ├──→ User adds ingredients (DOM manipulation)
              ├──→ User clicks "Calcular"
              │       ├──→ Validate inputs
              │       ├──→ Check localStorage rate limits
              │       │       ├──→ Under limit → compute → display results → store timestamp
              │       │       └──→ Over limit → hide form → show signup CTA
              │       └──→ Render results panel
              └──→ localStorage key: "mimargen_calc_timestamps" (JSON array of ISO dates)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/pages/index.astro` | Create | Single page composing all 11 section components in order |
| `src/components/layout/BaseLayout.astro` | Create | HTML shell: `<head>` (meta, OG, JSON-LD, fonts), header, `<slot>`, footer |
| `src/components/layout/Header.astro` | Create | Responsive nav: logo, desktop links, hamburger toggle with inline `<script>` |
| `src/components/layout/Footer.astro` | Create | Logo, links, copyright, social icons with `aria-label` |
| `src/components/sections/Hero.astro` | Create | Single `<h1>`, subheadline, 2 CTAs (primary: calculator, secondary: pricing) |
| `src/components/sections/PainPoints.astro` | Create | 3-4 pain point cards with icons |
| `src/components/sections/HowItWorks.astro` | Create | 3-step visual process |
| `src/components/sections/Features.astro` | Create | 4-6 feature cards in responsive grid |
| `src/components/sections/WhoItIsFor.astro` | Create | 3-4 persona cards |
| `src/components/sections/Calculator.astro` | Create | Section wrapper + vanilla JS island (`client:load`) for calculator widget |
| `src/components/sections/Testimonials.astro` | Create | 3 testimonial cards |
| `src/components/sections/Pricing.astro` | Create | 2-3 tier cards with CTAs |
| `src/components/sections/FAQ.astro` | Create | `<details>`/`<summary>` accordion + FAQPage JSON-LD generation |
| `src/components/sections/FinalCTA.astro` | Create | Bold headline + primary CTA |
| `src/components/ui/Button.astro` | Create | Reusable button with variant props (primary, secondary, ghost) |
| `src/components/ui/Card.astro` | Create | Reusable card container |
| `src/components/ui/Badge.astro` | Create | Small label/tag component |
| `src/components/ui/Section.astro` | Create | Section wrapper with consistent padding/max-width/heading slot |
| `src/styles/global.css` | Create | Tailwind directives (`@import "tailwindcss"`) + CSS custom properties |
| `public/robots.txt` | Create | Allow all crawlers, reference sitemap |
| `public/og-image.png` | Create | 1200×630px OG default image |
| `astro.config.mjs` | Create | Astro config: `@astrojs/tailwind`, `@astrojs/sitemap`, `output: 'static'` |
| `tailwind.config.mjs` | Create | Custom theme: terracotta/indigo/creme palette, Inter font, breakpoints |
| `tsconfig.json` | Create | `"strict": true`, Astro path aliases |
| `package.json` | Create | Dependencies: astro, @astrojs/tailwind, @astrojs/sitemap, tailwindcss, @fontsource/inter |

## Interfaces / Contracts

### Calculator Rate Limiting (localStorage)

```typescript
// Key: "mimargen_calc_timestamps"
// Value: JSON array of ISO 8601 timestamps
type CalcTimestamps = string[];

// Limits
const LIMITS = {
  daily: 3,    // rolling 24h window
  weekly: 10,  // rolling 7-day window
  monthly: 30, // rolling 30-day window
} as const;
```

### Tailwind Theme Tokens

```javascript
// tailwind.config.mjs — custom theme extension
colors: {
  terracotta: { 500: '#C75B3A', 600: '#B04E30', 700: '#8F3F27' },
  indigo:    { 500: '#4F46E5', 600: '#4338CA', 700: '#3730A3', 800: '#312E81' },
  creme:     { 50: '#FFFDF7', 100: '#FFF9E8', 200: '#FFF3D1' },
},
fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
```

### Section Component Props

```typescript
// All sections use static markup (no props) or minimal typed props:
interface SectionProps {
  id?: string;        // anchor ID for nav links
  className?: string; // additional Tailwind classes
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Build | Static output, zero TS errors | `pnpm build` in CI — fail on any error |
| Visual | Responsive layout at 375px, 768px, 1280px | Manual cross-browser + Lighthouse |
| Calculator | Computation accuracy, rate limiting, validation | Manual test matrix (spec scenarios CW-02 through CW-06) |
| SEO | Meta tags, JSON-LD, sitemap, heading hierarchy | Google Rich Results Test + Lighthouse SEO audit |
| A11y | Keyboard nav, focus states, heading levels, alt text | Lighthouse accessibility audit + axe-core |
| Performance | Lighthouse Performance ≥ 90 | Lighthouse CI in deploy pipeline |

## Migration / Rollout

No migration required — greenfield static site.

Rollout: deploy to staging URL first, validate Lighthouse scores and SEO schema, then promote to production domain. One-click rollback via deploy platform history.

## Open Questions

- [ ] Brand assets (logo SVG, exact hex values, product mockups) — needed before implementation
- [ ] External signup URL for CTA links — needed for FinalCTA, Pricing, and rate-limit message
- [ ] Testimonial content (photos, names, businesses) — confirm approved copy includes these
- [ ] Deploy platform choice (Vercel vs Netlify vs Cloudflare Pages) — affects adapter config
