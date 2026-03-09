# Design System Base (WPVite)

Base para convertir este theme en un starter reusable para spin-offs WordPress.

La decisión actual es:

- `daisyUI` es el motor base de componentes
- los tokens locales siguen siendo la fuente de verdad
- los helpers PHP y page families siguen siendo la capa de composición propia

## Layering

La arquitectura visual ahora se divide en cuatro capas:

- `src/css/tokens.css`
  Source of truth para color, typography, spacing, layout, radius, shadow y motion.
- `src/css/daisyui-theme.css`
  Adapter entre nuestros tokens y el theme global `starterspin` de daisyUI.
- `src/css/design-system.css`
  Baselines globales, tipografía, accesibilidad y prose.
- `src/css/ui-components.css`
  Overrides y adapters finos encima de daisyUI para botones, badges, panels, brand lockup, contact list, section header y stat tiles.
- `src/css/page-primitives.css`
  Shells, scenes, grids, cards, navigation chrome y page-family composition rules.

La fuente machine-readable sigue siendo:

- `src/tokens/design-tokens.json`

## Naming Rules

- Tokens globales con prefijo `--ds-`
- Helpers PHP compartidos con prefijo `starter_`
- Nada de nombres amarrados a una marca o cliente en la base reusable

## PHP Contracts

Helpers compartidos:

- `inc/icons.php`
- `inc/template-helpers.php`
- `inc/page-helpers.php`

Primitives compartidas:

- `components/button.php`
- `components/icon-button.php`
- `components/brand-lockup.php`
- `components/contact-list.php`
- `components/link-row.php`
- `components/stat-tile.php`
- `components/section-header.php`
- `components/section-scene.php`

## Page Primitives

Templates reutilizables:

- `template-parts/page/intro.php`
- `template-parts/page/empty-state.php`
- `template-parts/page/summary-card.php`
- `template-parts/page/summary-grid.php`
- `template-parts/page/contact-panel.php`

Page families iniciales:

- `template-parts/page-families/services-hub.php`
- `template-parts/page-families/about-detail.php`
- `template-parts/page-families/service-detail.php`
- `template-parts/page-families/contact-detail.php`

## Icon Policy

- Nada de SVG hardcoded dentro de helpers PHP para el sistema base
- Íconos locales en `assets/icons/phosphor/`
- API única: `starter_icon()`
- Alias permitidos a nombres reales de Phosphor solo como conveniencia (`mail`, `menu`, `globe`, etc.)

## Component Policy

- Usa daisyUI para primitives base: `btn`, `badge`, `card`, `input`, `textarea`, `select`
- Usa helpers `starter_*` para no acoplar templates directo al vocabulario de daisyUI
- Si el theme de daisyUI cambia, el contrato de los templates debe seguir estable

## Design System for Agents

Regla operativa:

```txt
Usa tokens y helpers existentes antes de inventar clases o valores.
Si una nueva abstracción es reusable, súbela de template a primitive o helper.
Si un cambio es de marca, mantenlo fuera del core reusable.
```
