# Component Rules

## daisyUI Policy

Use daisyUI as the component engine because it speeds up spin-offs.

That means:

- keep `daisyUI v5`
- theme it from local tokens
- reuse its stable component/state model
- hide repeated bundles behind starter helpers and wrapper classes

Do not treat raw daisyUI classes as the design-system API.

## Preferred Pattern

1. define or extend semantic tokens
2. bridge them through `src/css/daisyui-theme.css` when needed
3. expose starter-safe variants in helpers
4. render through PHP components or page primitives

## PHP Rules

- Components in `components/` are render-only.
- Normalize args before rendering when complexity grows.
- Keep query logic, heavy mapping logic, and business rules outside small reusable components.
- Prefer helper-generated class bundles over repeated Tailwind or daisyUI strings in templates.

## Tailwind Rules

- Keep Tailwind CSS v4 CSS-first.
- Prefer semantic utilities backed by tokens.
- Prefer wrapper classes for repeated UI patterns.
- Use arbitrary values only for truly local one-offs.
- Do not import React-oriented variant abstractions just to manage PHP templates.

## Review Checklist

- Is the change reusable across more than one page?
- Does it use semantic names instead of brand names?
- Could a future spin-off reskin this mostly through tokens?
- Does the component stay render-only?
- Did repeated class soup go down?
