# Layer Map

Use the highest reusable layer that still fits the task.

## CSS Layers

- `src/css/tokens.css`
  Primitive and semantic tokens: colors, type, spacing, radii, motion, elevation.

- `src/css/daisyui-theme.css`
  Adapter from local tokens to daisyUI theme variables.

- `src/css/design-system.css`
  Global HTML defaults, accessibility baselines, prose behavior, and typography helpers.

- `src/css/ui-components.css`
  Reusable component adapters and refinements.

- `src/css/page-primitives.css`
  Reusable page shells, section primitives, and composition helpers.

## PHP Layers

- `inc/template-helpers.php`
  Semantic helper APIs for classes, variants, attributes, buttons, badges, panels, links, scenes.

- `inc/page-helpers.php`
  Page-family and content-contract helpers.

- `components/`
  Render-only reusable PHP components with normalized args.

- `template-parts/page/`
  Generic page primitives.

- `template-parts/page-families/`
  Reusable page-family compositions.

- `template-parts/sections/`
  Reusable section compositions.

## Placement Rules

- If more than one template will want it, extract it.
- If the concern is visual language, prefer tokens or design-system CSS before local template classes.
- If class logic branches by variant, move it to helpers.
- If a component starts mixing business logic and rendering, split that logic out before extending it.
