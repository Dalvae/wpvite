# Infinitalent Extraction Map

Date: 2026-03-08

This document maps reusable patterns from `infinitalent` into the target `wpvite` starter architecture.

## Core Principle

Extract the system, not the brand.

That means:

- keep token and layout vocabulary
- keep component and helper contracts
- keep generic page-family wiring
- drop brand-specific naming, content assumptions, and visuals

## Immediate Extraction Candidates

## Foundations

Source:

- `/home/diego/projects/infinitalent/src/css/tokens.css`
- `/home/diego/projects/infinitalent/src/css/design-system.css`
- `/home/diego/projects/infinitalent/src/css/page-primitives.css`
- `/home/diego/projects/infinitalent/src/css/ui-components.css`

Target:

- `src/css/tokens.css`
- `src/css/design-system.css`
- `src/css/page-primitives.css`
- `src/css/ui-components.css`

Notes:

- rename `--it-*` aliases to neutral starter aliases where it improves portability
- keep the two-layer token model
- remove brand-specific colors, type choices, and hardcoded imagery references from the default layer

## Helper APIs

Source:

- `/home/diego/projects/infinitalent/inc/template-helpers.php`
- `/home/diego/projects/infinitalent/inc/page-helpers.php`

Target:

- `inc/template-helpers.php`
- `inc/page-helpers.php`

Extract first:

- class merging
- HTML attribute rendering
- button class builders
- badge class builders
- panel class builders
- scene shell/surface/spacing/layout helpers
- section header preset helpers
- page section header args
- collection grid helpers

Leave behind initially:

- content mappers tightly coupled to Infinitalent page structure
- service-specific parsing logic
- brand-specific visual fallbacks

## Reusable Components

Source:

- `/home/diego/projects/infinitalent/components/button.php`
- `/home/diego/projects/infinitalent/components/icon-button.php`
- `/home/diego/projects/infinitalent/components/brand-lockup.php`
- `/home/diego/projects/infinitalent/components/contact-list.php`
- `/home/diego/projects/infinitalent/components/link-row.php`
- `/home/diego/projects/infinitalent/components/stat-tile.php`
- `/home/diego/projects/infinitalent/components/section-header.php`
- `/home/diego/projects/infinitalent/components/section-scene.php`

Target:

- `components/button.php`
- `components/icon-button.php`
- `components/brand-lockup.php`
- `components/contact-list.php`
- `components/link-row.php`
- `components/stat-tile.php`
- `components/section-header.php`
- `components/section-scene.php`

Rules:

- normalize argument names
- keep components render-only
- move class decisions into helper functions where possible

## Page Primitives

Source:

- `/home/diego/projects/infinitalent/template-parts/page/intro.php`
- `/home/diego/projects/infinitalent/template-parts/page/empty-state.php`
- `/home/diego/projects/infinitalent/template-parts/page/summary-card.php`
- `/home/diego/projects/infinitalent/template-parts/page/summary-grid.php`
- `/home/diego/projects/infinitalent/template-parts/page/contact-panel.php`

Target:

- `template-parts/page/intro.php`
- `template-parts/page/empty-state.php`
- `template-parts/page/summary-card.php`
- `template-parts/page/summary-grid.php`
- `template-parts/page/contact-panel.php`

Rules:

- strip brand copy
- keep data contracts minimal and generic
- use helper APIs instead of inline class bundles

## Page Families

Source:

- `/home/diego/projects/infinitalent/template-parts/page-families/services-hub.php`
- `/home/diego/projects/infinitalent/template-parts/page-families/about-detail.php`
- `/home/diego/projects/infinitalent/template-parts/page-families/contact-detail.php`

Target:

- `template-parts/page-families/services-hub.php`
- `template-parts/page-families/about-detail.php`
- `template-parts/page-families/contact-detail.php`

Rules:

- only extract once the helper and primitive layer exists
- keep family templates as composition, not styling source of truth

## Optional Section Bodies

Potential future source:

- `/home/diego/projects/infinitalent/components/section-bodies/*`
- `/home/diego/projects/infinitalent/template-parts/sections/*`

Target:

- `components/section-bodies/*`
- `template-parts/sections/*`

Only extract the generic ones first:

- FAQ accordion
- structured list
- social proof
- proof/results
- offer cards
- packages/pricing
- step process
- callout panel

Do not extract immediately:

- home-page orchestration tied to one brand
- industry-specific differentiators
- service-context sections that assume one business model

## Operational Tooling

Source:

- `/home/diego/projects/infinitalent/scripts/sync-phosphor-icons.mjs`
- `/home/diego/projects/infinitalent/scripts/token-audit.js`
- `/home/diego/projects/infinitalent/scripts/build-theme-zip.sh`
- `/home/diego/projects/infinitalent/scripts/seed_local_pages_wpcli.php`

Target:

- `scripts/sync-icons.mjs`
- `scripts/token-audit.js`
- `scripts/build-theme-zip.sh`
- `scripts/seed_local_pages_wpcli.php`

Extraction order:

1. icon sync
2. theme zip packaging
3. token audit
4. local seed flow

## Recommended Rename Strategy

Use neutral project prefixes in starter helper names.

Examples:

- `infinitalent_merge_classes()` -> `starter_merge_classes()`
- `infinitalent_render_html_attributes()` -> `starter_render_html_attributes()`
- `infinitalent_get_scene_shell_classes()` -> `starter_get_scene_shell_classes()`
- `infinitalent_get_page_section_header_args()` -> `starter_get_page_section_header_args()`

Avoid leaking old brand names into the starter.

## Suggested Build Order

1. Foundations CSS split
2. Template helper file
3. UI primitive components
4. Scene and section-header system
5. Page helper contracts
6. Page primitives
7. Page families
8. Optional section body library
9. Tooling and packaging

## Red Flags During Extraction

Stop and refactor instead of copying if you see:

- repeated references to one brand name
- helper APIs that assume one content schema
- template arguments tied to one specific page
- CSS selectors that encode one page slug or campaign
- token names based on literal colors instead of semantics
