# Landing Sections Specification

## Purpose

Defines the 10 content sections plus footer that compose the MiMargen landing page. Each section is a standalone Astro component using Tailwind CSS, rendering approved Chilean Spanish copy with responsive and accessible markup.

## Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| LS-01 | All 11 components (10 sections + Footer) MUST be standalone Astro files rendering approved copy | Must |
| LS-02 | Every section MUST be responsive (mobile-first) using Tailwind utility classes | Must |
| LS-03 | Heading hierarchy MUST be semantic: single `<h1>` in Hero, logical `<h2>`/`<h3>` elsewhere | Must |
| LS-04 | Interactive elements (CTAs, accordion, links) MUST be keyboard-accessible with visible focus states | Must |
| LS-05 | All copy MUST be Chilean Spanish, neutral register, matching the approved copy document exactly | Must |

### Requirement: Section Components (LS-01)

The landing page MUST consist of the following 11 standalone Astro components, each rendering its approved content via props or static markup:

| # | Component | Content |
|---|-----------|---------|
| 1 | `Hero.astro` | Headline, subheadline, primary CTA (Probar Calculadora), secondary CTA (Ver Precios) |
| 2 | `PainPoints.astro` | 3-4 pain points with icon + text (costs unknown, manual pricing, lost margins) |
| 3 | `HowItWorks.astro` | 3-step process: add ingredients → set costs → see price |
| 4 | `Features.astro` | 4-6 feature cards (recipe costing, margin calculator, batch scaling, reports) |
| 5 | `WhoItIsFor.astro` | 3-4 target personas (small manufacturers, bakers, food entrepreneurs) |
| 6 | `Calculator.astro` | Wrapper for calculator-widget island with section title and intro copy |
| 7 | `Testimonials.astro` | 3 testimonial cards (photo, quote, name, business) |
| 8 | `Pricing.astro` | 2-3 tier cards (Freemium, Pro, Empresa) with features and CTA per tier |
| 9 | `FAQ.astro` | 5-8 questions with `<details>`/`<summary>` accordion |
| 10 | `FinalCTA.astro` | Bold headline + primary CTA button, minimal layout |
| 11 | `Footer.astro` | Logo, links, copyright, social icons |

#### Scenario: All sections render on single page

- GIVEN `index.astro` imports and composes all 11 components in order
- WHEN the page loads at viewport 1280px
- THEN all sections render without overlap, clipping, or horizontal scroll
- AND the page scrolls vertically through each section

#### Scenario: Each component is self-contained

- GIVEN any section component (e.g., `Hero.astro`)
- WHEN imported and rendered in isolation
- THEN it renders its content correctly without depending on sibling components

### Requirement: Responsive Design (LS-02)

Every section MUST adapt to viewport widths from 320px to 1440px+ using Tailwind responsive prefixes (`sm:`, `md:`, `lg:`). Mobile-first approach: base styles target mobile, larger breakpoints override.

#### Scenario: Mobile layout stacks content vertically

- GIVEN viewport width 375px (mobile)
- WHEN the page loads
- THEN all multi-column layouts collapse to single-column
- AND text is readable without horizontal scrolling
- AND touch targets (CTAs, accordion toggles) are ≥ 44px

#### Scenario: Desktop layout uses multi-column where appropriate

- GIVEN viewport width 1280px (desktop)
- WHEN the page loads
- THEN Features cards display in a 3-column grid
- AND Pricing cards display side-by-side
- AND Testimonials display in a 2 or 3-column grid

### Requirement: Semantic Heading Hierarchy (LS-03)

The page MUST use exactly one `<h1>` (in Hero), with section titles as `<h2>` and subsection titles as `<h3>`. No heading levels skipped.

#### Scenario: Single h1 in Hero section

- GIVEN the page HTML is inspected
- WHEN searching for `<h1>` elements
- THEN exactly one `<h1>` exists and it contains the main headline

#### Scenario: Logical h2/h3 nesting

- GIVEN the page HTML is inspected
- WHEN checking the heading outline
- THEN every `<h3>` is preceded by an `<h2>` in the same section
- AND no heading level is skipped (h1 → h3 without h2)

### Requirement: Accessibility (LS-04)

All interactive elements MUST be operable via keyboard. Accordion toggles use `<details>`/`<summary>` (native keyboard support). Links have visible `:focus-visible` outlines. Images have `alt` text.

#### Scenario: FAQ accordion is keyboard-operable

- GIVEN the FAQ section renders
- WHEN the user presses Tab to focus a question
- AND presses Enter or Space
- THEN the answer expands
- AND pressing Enter/Space again collapses it

#### Scenario: CTAs have visible focus rings

- GIVEN any CTA button or link
- WHEN the user tabs to focus it
- THEN a visible outline (≥ 2px, contrast ≥ 3:1) appears

### Requirement: Copy Accuracy (LS-05)

All visible text in every section MUST match the approved copy document word-for-word. Language is Chilean Spanish, neutral register — no Argentine voseo, no colloquialisms.

#### Scenario: Copy matches approved document

- GIVEN the approved copy document for all 10 sections
- WHEN comparing rendered page text against the document
- THEN every headline, body paragraph, CTA label, and testimonial quote matches exactly

#### Scenario: Copy is neutral Chilean Spanish

- GIVEN all rendered text on the page
- WHEN reviewing for Argentine voseo markers (e.g., "vos", "tenés", "hacé")
- THEN zero instances are found
- AND the register is professional and neutral
