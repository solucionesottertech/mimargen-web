# Calculator Widget Specification

## Purpose

Defines the client-side recipe cost calculator island: inputs, computation logic, rate limiting via localStorage, and lead-capture behavior. Built with vanilla JS — no framework. This is the primary lead magnet.

## Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| CW-01 | Widget MUST be an Astro island using vanilla JS with no framework dependency | Must |
| CW-02 | Users MUST be able to add up to 10 ingredients (name, quantity, unit, price/unit) | Must |
| CW-03 | Computation MUST accept labor cost, hours/batch, waste %, desired margin %, and output total recipe cost, cost/unit, suggested sale price, margin % | Must |
| CW-04 | Rate limiting MUST enforce 3 calculations/day via localStorage, resetting on rolling window; weekly/monthly caps also enforced | Must |
| CW-05 | When rate limit is reached, widget MUST show a message encouraging signup for unlimited calculations | Must |
| CW-06 | All inputs MUST validate on blur: positive numbers, no empty required fields, ≤ 10 ingredients | Must |

### Requirement: Vanilla JS Island (CW-01)

The calculator widget MUST be an Astro island (`client:load`) with all logic in a vanilla JS module. Zero framework imports. DOM manipulation uses native APIs only.

#### Scenario: Widget hydrates without a framework

- GIVEN the page loads with `Calculator.astro` rendering the widget island
- WHEN checking the browser's JS bundle
- THEN no React, Vue, Svelte, or other framework runtime is present
- AND the calculator is interactive after hydration

#### Scenario: Widget works in all modern browsers

- GIVEN browsers: Chrome 100+, Firefox 100+, Safari 16+, Edge 100+
- WHEN the calculator loads and the user interacts with it
- THEN all inputs, computations, and rate-limit messages work correctly

### Requirement: Ingredient Inputs (CW-02)

The widget MUST render a dynamic list of ingredient rows. Each row has: name (text), quantity (number), unit (dropdown: g, kg, ml, L, unidad), price per unit (number, CLP). Users can add rows up to 10, and remove rows down to 1.

#### Scenario: Add ingredient rows up to the limit

- GIVEN the calculator renders with 1 empty ingredient row
- WHEN the user clicks "Agregar ingrediente" 9 times
- THEN 10 rows are visible and the add button is disabled
- AND the button shows "Máximo 10 ingredientes"

#### Scenario: Remove ingredient rows down to the minimum

- GIVEN the calculator has 3 ingredient rows
- WHEN the user clicks the remove button on row 2
- THEN row 2 is removed and rows re-index
- WHEN the user removes down to 1 row
- THEN the remove button on the last row is hidden or disabled

### Requirement: Cost Computation (CW-03)

The calculator MUST compute: total ingredient cost (sum of qty × price/unit), add labor cost × hours, apply waste %, then compute cost per unit, suggested sale price (cost × (1 + margin%)), and effective margin %.

**Formulas:**
- Total ingredient cost = Σ(qty × price_per_unit)
- Cost including labor = total ingredient cost + (labor_cost_per_hour × hours_per_batch)
- Cost after waste = cost including labor / (1 − waste_percentage)
- Suggested sale price = cost after waste × (1 + desired_margin_percentage)
- Effective margin = (suggested price − cost) / suggested price × 100

#### Scenario: Calculator produces correct results

- GIVEN 2 ingredients: Harina (1 kg, 1200 CLP/kg) + Azúcar (0.5 kg, 900 CLP/kg)
- AND labor: 5000 CLP/h × 2h, waste: 5%, margin: 30%
- WHEN the user clicks "Calcular"
- THEN total ingredient cost = 1650 CLP
- AND cost including labor = 11650 CLP
- AND cost after waste = 12263 CLP (rounded)
- AND suggested sale price = 15942 CLP (rounded)
- AND effective margin ≈ 30%

#### Scenario: Empty or invalid inputs show validation errors

- GIVEN an ingredient row has an empty name field
- WHEN the user clicks "Calcular"
- THEN the input gets a red border and an error message "Campo obligatorio"
- AND no calculation runs

### Requirement: Rate Limiting (CW-04)

The widget MUST track calculations in localStorage using a rolling-window counter. Limits: 3 per day, 10 per week, 30 per month. When any limit is hit, the calculator locks until the window resets.

#### Scenario: Daily rate limit enforced

- GIVEN localStorage records 3 calculations in the current rolling 24-hour window
- WHEN the user fills in valid inputs and clicks "Calcular"
- THEN the calculator does not compute
- AND a message displays: "Llegaste al límite diario de cálculos gratuitos"

#### Scenario: Rate limit resets on rolling window

- GIVEN the last calculation was 24 hours and 1 minute ago
- AND localStorage has 3 calculations stored (all outside the 24h window)
- WHEN the user clicks "Calcular"
- THEN the calculator computes normally (rolling window excludes old entries)

#### Scenario: Weekly/monthly caps also checked

- GIVEN 10 calculations in the current calendar week but only 1 in the rolling 24h window
- WHEN the user clicks "Calcular"
- THEN the weekly cap blocks the calculation with an appropriate message

### Requirement: Rate Limit Message (CW-05)

When rate-limited, the widget MUST replace the form with a call-to-action message encouraging signup. The message MUST link to the signup/external app URL.

#### Scenario: Rate-limit state shows signup CTA

- GIVEN the daily rate limit is reached
- WHEN the widget renders
- THEN the ingredient form is hidden
- AND the message "¿Quieres cálculos ilimitados? Regístrate en MiMargen y accede sin límites." displays
- AND a "Registrarme" button links to the external signup URL

### Requirement: Input Validation (CW-06)

All numeric inputs MUST validate on blur: positive numbers only, no NaN, no negative values. Required text fields (ingredient name) must be non-empty. Waste percentage and margin percentage must be 0–100.

#### Scenario: Negative quantity rejected on blur

- GIVEN the user enters "-5" in a quantity field
- WHEN the field loses focus (blur)
- THEN the input border turns red
- AND an inline error "El valor debe ser positivo" appears

#### Scenario: Waste percentage above 100 rejected

- GIVEN the user enters "150" in the waste percentage field
- WHEN the field loses focus
- THEN the input border turns red
- AND an inline error "Debe ser entre 0 y 100" appears
