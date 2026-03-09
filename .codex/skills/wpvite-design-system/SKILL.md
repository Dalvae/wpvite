---
name: wpvite-design-system
description: Implement reusable UI in this WPVite starter using WordPress PHP templates, Tailwind CSS v4, daisyUI v5, semantic tokens, section primitives, and responsive layouts. Use when editing src/css, components, template-parts, inc/template-helpers.php, inc/page-helpers.php, or design-system docs in this repo.
---

# WPVite Design System

Use this skill for UI work in `wpvite`. It distills the useful parts of generic design-system skills into the actual repo stack: WordPress theme, PHP templates, Tailwind CSS v4, daisyUI v5, AlpineJS, and Turbo.

Default assumption: the goal is not a one-off polished page. The goal is a reusable starter abstraction that helps future spin-offs.

## Use This Skill When

- adding or refactoring tokens, type, spacing, radii, motion, or theme variables
- creating reusable UI primitives or section scaffolds
- cleaning up repeated Tailwind or daisyUI class soup in PHP templates
- deciding whether a change belongs in tokens, CSS foundations, UI components, page primitives, helpers, or template parts
- translating generic frontend advice into repo-safe WordPress/PHP patterns

## Core Workflow

1. Place the change in the highest reusable layer that fits.
   Read `references/layer-map.md`.

2. Keep daisyUI as the engine, not the public API.
   Read `references/component-rules.md`.

3. Prefer semantic tokens and text roles over ad hoc utility bundles.
   Read `references/token-and-theme.md` and `references/typography-and-responsive.md`.

4. Keep PHP components render-only and move branching class logic into helpers.

5. Update repo docs when the reusable contract changes.
   The repo-level companion doc is `docs/llm-design-system-guide.md`.

## Hard Constraints

- Do not default to React, CVA, `shadcn/ui`, Astro, or app-library patterns.
- Do not introduce `tailwind.config.*` when Tailwind v4 CSS-first config is enough.
- Do not put reusable concerns in inline styles or page-specific CSS.
- Do not paste raw daisyUI class strings across many templates when a helper or wrapper class should exist.
- Do not keep brand-specific names in starter helpers, tokens, or primitives.

## Reference Files

- `references/layer-map.md`
  Where code belongs in this repo.

- `references/component-rules.md`
  How to use daisyUI, Tailwind, helpers, and PHP components without creating system debt.

- `references/token-and-theme.md`
  Token hierarchy and theming rules distilled from the broader design-system material.

- `references/typography-and-responsive.md`
  Text-role, readability, fluid type, and responsive layout rules for this starter.
