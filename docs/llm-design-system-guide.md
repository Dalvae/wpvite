# LLM Design System Guide

This file is the repo-local source of truth for UI implementation decisions by agents and LLMs.

Use it when working on:

- `src/css/*`
- `components/`
- `template-parts/`
- `inc/template-helpers.php`
- `inc/page-helpers.php`
- design-system documentation

## Default Stack Assumption

Assume:

- WordPress theme
- PHP templates
- Tailwind CSS v4
- daisyUI v5
- AlpineJS
- Turbo

Do not default to React, CVA, `shadcn/ui`, Astro, or app-library patterns unless the task explicitly requires them.

## Mental Model

The repo is not trying to be a generic frontend playground.

It is trying to be a reusable spin-off system built from:

- design tokens
- starter UI primitives
- section composition APIs
- page-family scaffolds
- pipeline-driven packaging

So the goal is not "make this page look good quickly".
The goal is "leave behind a reusable contract that the next site can reuse".

## Placement Rules

Put changes in the highest reusable layer that fits:

- `src/css/tokens.css`
  Primitives and semantic tokens: color, type, spacing, radius, motion, elevation.

- `src/css/daisyui-theme.css`
  daisyUI adapter layer from local tokens to daisyUI theme variables.

- `src/css/design-system.css`
  Base HTML defaults, typography, accessibility baselines, prose behavior.

- `src/css/ui-components.css`
  Reusable component adapters and refinements.

- `src/css/page-primitives.css`
  Reusable page shells and composition primitives.

- `inc/template-helpers.php`
  Semantic helper APIs for classes, variants, attributes, buttons, panels, badges, scenes.

- `inc/page-helpers.php`
  Page-family and content-contract helpers.

- `components/`
  Render-only reusable PHP components.

- `template-parts/page/`
  Generic page primitives.

- `template-parts/page-families/`
  Reusable page-family compositions.

- `template-parts/sections/`
  Reusable section compositions.

## daisyUI Policy

Use daisyUI as the component engine because it speeds up spin-offs.

That means:

- keep `daisyUI v5`
- theme it from local tokens
- reuse its stable component/state model
- hide repeated bundles behind starter helpers and wrapper classes

Do not treat raw daisyUI classes as the design-system API.

Preferred pattern:

1. define or extend semantic tokens
2. bridge them in `src/css/daisyui-theme.css` when needed
3. expose starter-safe variants in helpers
4. render through PHP components or page primitives

## Tailwind Rules

- Keep Tailwind CSS v4 CSS-first.
- Prefer `@theme` tokens in CSS over adding a `tailwind.config.*` file.
- Prefer semantic utilities and wrapper classes over arbitrary values.
- Use arbitrary values only for truly local one-offs.
- Prefer fluid `clamp()` scales over breakpoint-only jumps when the pattern is reusable.

## Typography Rules

Prefer semantic text roles over ad hoc bundles.

Use the repo text roles when possible:

- `text-display`
- `text-h1`
- `text-h2`
- `text-h3`
- `text-h4`
- `text-lead`
- `text-body`
- `text-body-sm`
- `text-label`
- `text-overline`

Or the matching helper classes:

- `.type-display`
- `.type-h1`
- `.type-h2`
- `.type-h3`
- `.type-h4`
- `.type-lead`
- `.type-body`
- `.type-body-sm`
- `.type-label`
- `.type-overline`

When adding a reusable text role:

1. define it in `src/css/tokens.css`
2. bridge it to Tailwind `@theme`
3. add a reusable helper class in `src/css/design-system.css` if the full bundle will be reused

## PHP Component Rules

- Components in `components/` must stay render-only.
- Pass normalized args into components.
- Move class-assembly logic into helpers when it branches.
- Do not mix query logic, heavy mapping logic, and rendering in a small reusable component.

## Responsive Rules

- Work mobile-first.
- Prefer intrinsic grid/flex patterns before hardcoded layouts.
- Use container queries when a component should adapt to its parent width.
- Keep reading measure near `65ch` to `68ch` for long-form text.
- Use balanced wrapping for headings and pretty wrapping for body copy where appropriate.

## Anti-Patterns

Avoid:

- React/CVA examples copied into PHP work
- `shadcn/ui`-style abstractions for default starter work
- inline `style=""` for reusable concerns
- page-specific CSS for patterns that belong to the system
- hardcoded hex values in templates
- repeated raw daisyUI class strings across multiple templates
- brand-specific helper names in shared code

## Definition Of Done

UI work is not done when it only fixes one page.

It is done when:

- the abstraction lives in the right layer
- the API is semantic
- brand leakage is avoided
- repeated class soup is reduced
- the docs are updated if the reusable contract changed
