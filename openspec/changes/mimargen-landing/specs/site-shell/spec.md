# Site Shell Specification

## Purpose

Defines the Astro project scaffold, build tooling, global layout, design tokens, and responsive navigation system for the MiMargen landing page.

## Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| SH-01 | Project MUST use Astro with pnpm as package manager and TypeScript strict mode | Must |
| SH-02 | Tailwind CSS v4 MUST be configured with MiMargen custom theme (colors, fonts, spacing, breakpoints) | Must |
| SH-03 | `BaseLayout.astro` MUST provide `<slot>` for page content and `<slot name="head">` for per-page meta injection | Must |
| SH-04 | Header/nav MUST be responsive: hamburger menu on mobile (≤768px), full nav on desktop | Must |
| SH-05 | Inter font MUST be loaded via `@fontsource/inter` with optimal subset (latin) and `font-display: swap` | Must |

### Requirement: Project Scaffold (SH-01)

An Astro project scaffolded with pnpm that enforces TypeScript strict mode. The project MUST build as a fully static site with zero client-side framework overhead.

#### Scenario: Build produces static HTML with zero TS errors

- GIVEN the project is scaffolded with `pnpm create astro@latest`
- AND `tsconfig.json` has `"strict": true`
- WHEN `pnpm build` runs
- THEN the build completes with zero TypeScript errors
- AND output is static HTML, CSS, and JS (no SSR)

#### Scenario: pnpm manages all dependencies

- GIVEN `package.json` lists `astro`, `@astrojs/tailwind`, `@astrojs/sitemap`, `tailwindcss`, `@fontsource/inter`
- WHEN `pnpm install` runs
- THEN all dependencies resolve without warnings
- AND `pnpm-lock.yaml` is committed

### Requirement: Tailwind Custom Theme (SH-02)

Tailwind CSS v4 MUST extend the default theme with MiMargen brand tokens. The palette uses terracotta as primary accent, indigo as secondary, and crème as background base.

#### Scenario: Brand colors are available as Tailwind utilities

- GIVEN `tailwind.config.mjs` extends theme.colors with `terracotta` (500-700 shades), `indigo` (500-800), and `creme` (50-200)
- WHEN a section component uses `bg-terracotta-600` or `text-indigo-700`
- THEN the correct hex values render in the browser

#### Scenario: Custom breakpoints match design spec

- GIVEN Tailwind config defines screens: `sm: 640px`, `md: 768px`, `lg: 1024px`, `xl: 1280px`
- WHEN testing responsive behavior at each breakpoint
- THEN layout adapts correctly per mobile-first design

### Requirement: Base Layout with Slots (SH-03)

`BaseLayout.astro` MUST be the single layout wrapper for the entire site. It provides the HTML document shell, global `<head>`, header navigation, and named slots for page content and per-page meta tags.

#### Scenario: Page content renders inside BaseLayout

- GIVEN `index.astro` wraps all sections in `<BaseLayout>`
- WHEN the page renders
- THEN the output HTML contains the full `<head>` from BaseLayout
- AND all section HTML appears inside `<slot />`

#### Scenario: Per-page meta injected via head slot

- GIVEN `index.astro` passes `<meta name="description" content="...">` to `<slot name="head">`
- WHEN the page renders
- THEN the description meta tag appears in `<head>` before the closing `</head>`

### Requirement: Responsive Navigation (SH-04)

The header MUST include the MiMargen logo, a desktop nav bar, and a mobile hamburger toggle. Mobile nav opens/closes via a client-side `<script>` with no framework dependency.

#### Scenario: Desktop shows full navigation bar

- GIVEN viewport width > 768px
- WHEN the page loads
- THEN all nav links (Inicio, Calculadora, Precios, FAQ) are visible in a horizontal bar
- AND the hamburger button is hidden

#### Scenario: Mobile shows hamburger menu that toggles

- GIVEN viewport width ≤ 768px
- WHEN the page loads
- THEN nav links are hidden and the hamburger button is visible
- WHEN the user taps the hamburger button
- THEN nav links slide in as a vertical menu
- AND tapping again or tapping a link closes the menu

### Requirement: Typography Loading (SH-05)

The Inter font family MUST be the primary typeface, loaded without render-blocking. Latin subset only; CSS `font-display: swap` prevents FOIT.

#### Scenario: Inter font loads asynchronously

- GIVEN `BaseLayout.astro` imports `@fontsource/inter/latin.css`
- WHEN the page loads
- THEN text renders in a system fallback until Inter loads
- THEN text swaps to Inter without layout shift

#### Scenario: Tailwind font family extends to Inter

- GIVEN Tailwind config sets `fontFamily.sans` to `['Inter', 'system-ui', 'sans-serif']`
- WHEN any Tailwind class like `font-sans` is used
- THEN the computed `font-family` starts with `Inter`
