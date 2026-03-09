# Typography And Responsive Rules

## Typography Model

Prefer semantic text roles over ad hoc bundles.

Use repo text roles when possible:

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

Or the matching helper classes when the full bundle is reusable:

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
3. add a helper class in `src/css/design-system.css` if the full bundle will be reused

## Readability

- Keep body text around `65ch` to `68ch`
- headings should use balanced wrapping
- body copy can use pretty wrapping
- heading line-height should stay tight
- body line-height should stay around `1.5` to `1.7`
- labels and overlines should only be uppercase when the role is UI metadata

## Responsive Rules

- work mobile-first
- prefer fluid `clamp()` scales over breakpoint-only jumps
- prefer intrinsic grid/flex patterns before hardcoded column counts
- use container queries when a component should adapt to its parent width
- keep breakpoints content-driven, not tool-driven

## Common Mistakes

- inventing one-off heading bundles in templates
- using `text-3xl md:text-5xl` everywhere instead of a shared role
- using arbitrary widths for reading measure
- making desktop the default and patching mobile later
