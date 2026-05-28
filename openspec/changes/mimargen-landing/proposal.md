# Proposal: MiMargen Landing Page

## Intent

Build a high-converting, SEO-optimized static landing page for MiMargen — a recipe-based product costing SaaS for small manufacturers and entrepreneurs in Chile (primary) and Spanish-speaking markets (secondary). The landing must convert visitors into free calculator users and paid subscribers, while ranking organically for cost-calculation and recipe-pricing keywords from day one.

## Scope

### In Scope
- Static Astro site with 10 content sections + footer
- Free calculator widget (lead magnet) with client-side rate limiting (3 actions/day, weekly/monthly caps via localStorage)
- Full SEO foundation: meta tags, OG/Twitter cards, JSON-LD schema (SoftwareApplication + FAQPage), sitemap.xml, robots.txt
- Responsive design (mobile-first) with Tailwind CSS
- TypeScript strict mode throughout
- Semantic HTML with proper heading hierarchy (single h1, logical h2/h3)
- Chilean Spanish copy (neutral enough for international audience)
- Performance-optimized static build (zero client-side framework overhead)

### Out of Scope
- Backend/API (calculator is client-side only)
- Authentication or user accounts
- Blog or content marketing pages (future phase)
- Multi-language / i18n support (future phase)
- Payment integration (CTAs link to external signup)
- Analytics dashboard (tracking pixel integration only)

## Capabilities

### New Capabilities
- `landing-sections`: 10 Astro components rendering the approved copy — Hero, Pain Points, How It Works, Features, Who It's For, Calculator, Testimonials, Pricing, FAQ, Final CTA, plus Footer
- `calculator-widget`: Vanilla JS calculator with recipe cost computation, localStorage-based rate limiting (daily/weekly/monthly caps), and lead-capture form
- `seo-foundation`: Meta tags, Open Graph, Twitter Cards, JSON-LD schemas, sitemap.xml, robots.txt, semantic HTML structure, heading hierarchy, keyword-rich alt texts
- `site-shell`: Astro layout, global styles, Tailwind config, TypeScript config, responsive navigation, font loading

### Modified Capabilities
None — this is a greenfield project with no existing capabilities.

## Approach

**Architecture**: Astro static site with island architecture for the calculator widget. All sections are server-rendered Astro components (zero JS). The calculator is the only interactive island — built with vanilla JS to avoid framework hydration cost.

**Component structure**:
```
src/
├── components/
│   ├── sections/        # 10 section components (Hero.astro, PainPoints.astro, etc.)
│   ├── calculator/      # Calculator widget (vanilla JS + Astro wrapper)
│   └── ui/              # Shared UI primitives (Button, Card, Badge)
├── layouts/
│   └── BaseLayout.astro # HTML shell, meta tags, font loading
├── pages/
│   └── index.astro      # Single page composing all sections
├── styles/
│   └── global.css       # Tailwind directives + custom properties
└── assets/              # Images, mockups, icons
```

**Styling**: Tailwind CSS with custom design tokens (colors, spacing, typography) matching MiMargen brand. Mobile-first responsive breakpoints.

**Calculator rate limiting**: localStorage-based counter system tracking daily actions (max 3), weekly cap, and monthly cap. Resets on rolling window. Acceptable for MVP — server-side enforcement deferred to post-launch.

**SEO**: All meta tags generated via Astro's built-in `<Head>` management. JSON-LD injected as `<script type="application/ld+json">`. Sitemap via `@astrojs/sitemap` integration.

**Build & Deploy**: `pnpm build` produces static output. Deploy to Vercel/Netlify/Cloudflare Pages (adapter TBD at deploy time).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/components/sections/` | New | 10 Astro section components |
| `src/components/calculator/` | New | Calculator widget with rate limiting |
| `src/components/ui/` | New | Shared UI primitives (Button, Card, Badge) |
| `src/layouts/` | New | Base layout with SEO head management |
| `src/pages/` | New | Single index page composing all sections |
| `src/styles/` | New | Global CSS with Tailwind config |
| `public/` | New | Static assets, sitemap, robots.txt |
| `astro.config.mjs` | New | Astro configuration with integrations |
| `tailwind.config.mjs` | New | Tailwind theme with MiMargen design tokens |
| `tsconfig.json` | New | TypeScript strict configuration |
| `package.json` | New | Dependencies: astro, @astrojs/sitemap, @astrojs/tailwind, tailwindcss |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Calculator widget scope creep (users want more features) | Medium | Strict MVP scope: basic cost calculation only. Feature requests go to backlog. |
| localStorage rate limiting bypass (users clear storage) | Low | Acceptable for MVP lead magnet. Server-side enforcement planned for post-launch. |
| SEO performance below expectations | Low | Semantic HTML + schema + sitemap from day one. Monitor via Search Console post-launch. |
| Tailwind CSS bundle size grows with many utility classes | Low | PurgeCSS built into Tailwind v4. Audit bundle size before launch. |
| Copy changes after approval delay development | Medium | Component architecture isolates copy in props/slots — changes don't touch logic. |
| Image optimization for product mockups | Low | Use Astro's `<Image>` component for automatic optimization (WebP/AVIF). |

## Rollback Plan

This is a greenfield static site — rollback is trivial:
1. **Pre-launch**: Delete the `openspec/changes/mimargen-landing/` directory and all `src/` files. No existing functionality is affected.
2. **Post-launch**: Revert to previous deploy via Vercel/Netlify/Cloudflare deploy history (one-click rollback). Static sites have no database state to corrupt.
3. **Partial rollback**: Individual sections can be removed from `index.astro` without affecting other sections.

## Dependencies

- **Astro** (static site framework) — core build tool
- **Tailwind CSS v4** (utility-first CSS) — styling
- **@astrojs/sitemap** — sitemap generation
- **@astrojs/tailwind** — Tailwind integration
- **pnpm** — package manager
- **Approved copy document** — all 10 sections content (already completed)
- **Brand assets** — logo, color palette, product mockups (needed before implementation)
- **Vercel/Netlify/Cloudflare account** — deploy target (needed at deploy time, not build time)

## Success Criteria

- [ ] All 10 sections render correctly with approved copy on desktop and mobile
- [ ] Calculator widget computes recipe costs accurately and enforces rate limits
- [ ] Lighthouse scores: Performance ≥ 90, Accessibility ≥ 95, SEO ≥ 95, Best Practices ≥ 90
- [ ] JSON-LD schema validates via Google Rich Results Test
- [ ] Sitemap.xml and robots.txt are accessible and correctly configured
- [ ] Site builds with zero TypeScript errors in strict mode
- [ ] Static build completes in under 30 seconds
- [ ] All CTAs link to correct destinations (signup, calculator, pricing)
- [ ] FAQ section renders with proper schema for rich snippet eligibility
- [ ] Mobile-first responsive design works on 320px–1440px+ viewports
