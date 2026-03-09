# Token And Theme Rules

This starter should follow a layered token model:

1. primitive tokens
   Raw values like reference colors, spacing steps, font stacks, radii.

2. semantic tokens
   Meaningful aliases like background, foreground, line, primary, muted, surface, h1, lead, body.

3. component tokens or wrapper semantics
   Component-facing decisions like button variants, panel surfaces, badge tones, field states.

## Rules

- Keep the source of truth in `src/css/tokens.css`.
- Expose reusable Tailwind v4 values through `@theme`.
- Map local tokens into daisyUI in `src/css/daisyui-theme.css`.
- Prefer semantic names over appearance names.

Good:

- `--ds-color-primary`
- `--ds-color-surface`
- `--ds-type-h2-size`
- `surface=muted`
- `variant=primary`

Bad:

- `--blue-500` as a shared API
- `--big-heading`
- `variant=marketing-orange`

## Theming

- Default to multi-brand-ready semantic tokens.
- Brand changes should mostly happen by changing tokens, not component markup.
- If a new brand preset changes the public starter capability, update config/docs too.

## Accessibility

- Maintain readable foreground/background contrast.
- Do not introduce decorative colors as core semantic text colors without checking readability.
- Keep focus states explicit and token-driven.
