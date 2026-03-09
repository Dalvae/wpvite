# WPVite Starter System Roadmap

Date: 2026-03-08

## Goal

Evolve `wpvite` from a minimal WordPress + Vite starter into a reusable spin-off platform for fast delivery of high-quality marketing, services, editorial, and expert-brand sites.

The primary source of reuse is not a new frontend framework. It is a layered system of:

- design tokens
- reusable UI primitives
- reusable section composition APIs
- reusable page-family contracts
- reusable content-seeding and packaging workflows

## Decision Summary

### Keep for the next iteration

- WordPress theme architecture
- Vite 7
- Tailwind CSS 4
- daisyUI 5 as the base component engine
- PHP templates and helper APIs
- AlpineJS and Turbo for light interaction

### Do not make default yet

- Roots Sage migration
- React-first architecture
- `shadcn/ui`
- Astro-first frontend split

These remain optional paths for projects with explicit product requirements, but they do not solve the main bottleneck for spin-offs: reusable design and content abstractions.

## North Star

A new branded site should be able to launch from the starter with:

- a coherent visual system in less than 1 day
- reusable page-family scaffolds in less than 2 days
- content seeding and theme packaging with minimal one-off work
- brand overrides isolated from the core starter

## Non-Goals

- Building a generic site builder
- Reproducing a React component library inside WordPress
- Optimizing for app-like dashboards before the marketing/site layer is mature
- Locking the starter to one visual style

## Target Architecture

### 1. Foundations

Source of truth for brand-agnostic design language.

- token primitives
- semantic aliases
- typography scale
- semantic text roles for display, headings, body, and labels
- spacing scale
- radii
- motion
- elevation
- surface recipes
- pattern/background recipes
- accessibility baselines

Target files:

- `src/css/tokens.css`
- `src/css/design-system.css`
- `src/tokens/design-tokens.json`

### 2. UI Primitives

Small reusable units with stable APIs.

- button
- icon button
- badge
- panel
- input
- textarea
- select
- stat tile
- avatar
- link row
- contact list
- brand lockup

Target files:

- `components/*.php`
- `src/css/ui-components.css`
- `inc/template-helpers.php`

### 3. Composition Layer

Reusable section wiring that avoids re-inventing layouts for every site.

- scene shell
- surface variants
- spacing variants
- divider variants
- layout variants
- section header presets
- content stack helpers
- collection grid helpers

Target files:

- `components/section-scene.php`
- `components/section-header.php`
- `src/css/page-primitives.css`
- `inc/template-helpers.php`
- `inc/page-helpers.php`

### 4. Page Families

Reusable scaffolds for recurring business-site page types.

- services hub
- service detail
- about detail
- contact detail
- speaking/expert detail
- recruitment/coaching detail
- post feed / summary grid
- empty state / fallback templates

Target files:

- `template-parts/page-families/*`
- `template-parts/page/*`
- `inc/page-helpers.php`

### 5. Content Contracts

Data conventions so templates can be seeded and mapped consistently.

- canonical section schema
- page hero schema
- CTA schema
- proof/social-proof schema
- FAQ schema
- pricing/packages schema
- structured list schema
- result/proof schema

Target files:

- `inc/acf.php` or equivalent schema registration
- `inc/page-helpers.php`
- `builder/`
- `scripts/`

### 6. Operations Layer

Everything needed to ship spin-offs reliably.

- local content seed
- demo content manifests
- icon sync
- zip packaging
- deployment helpers
- token audit
- optional screenshot QA

Target files:

- `scripts/`
- `specs/`
- `README.md`

## Phase Plan

## Recommended First Milestone

Suggested first implementation batch:

1. Create `src/css/tokens.css` and split the current CSS foundation into `tokens`, `design-system`, `ui-components`, and `page-primitives`.
2. Introduce `inc/template-helpers.php` with neutral starter helper names for class merging, HTML attributes, buttons, badges, and panels.
3. Port the first reusable primitives: button, icon button, brand lockup, contact list, link row, stat tile.
4. Add `components/section-scene.php` and `components/section-header.php` plus the matching scene/header helper functions.
5. Add `inc/page-helpers.php` plus the first page primitives: intro, empty state, summary card/grid, contact panel.

This first milestone should create the reusable spine of the starter before any project-specific page family is ported.

## Detailed Phases

### Phase 0: Documentation and Rules

Deliverables:

- `AGENTS.md` with architectural rules
- roadmap doc
- extraction map from `infinitalent`
- README links to the system docs

Done when:

- future contributors can place new abstractions without guessing
- the repo clearly distinguishes core vs brand-specific code

### Phase 1: Foundations Extraction

Primary source: `infinitalent/src/css/tokens.css` and `infinitalent/src/css/design-system.css`

Deliverables:

- add layered token architecture to `wpvite`
- expand `design-tokens.json` to include typography, spacing, radii, elevation, motion
- add semantic typography role tokens bridged into Tailwind `text-*` utilities and reusable CSS helpers
- split base CSS into:
  - `tokens.css`
  - `design-system.css`
  - `page-primitives.css`
  - `ui-components.css`
- preserve current starter simplicity while making overrides possible

Done when:

- one brand can be re-skinned mostly through token and asset changes
- components consume semantic tokens instead of hardcoded colors/sizes

### Phase 2: UI Primitive API

Primary source: `infinitalent/inc/template-helpers.php` and reusable components

Deliverables:

- introduce `inc/template-helpers.php`
- standardize helper APIs for:
  - class merging
  - HTML attribute rendering
  - buttons
  - badges
  - panels
  - icon chips
  - text links
- port generic components from `infinitalent`
- keep brand naming neutral

Done when:

- page templates stop hand-assembling repeated class bundles
- primitives can be reused unchanged across multiple projects

### Phase 3: Composition System

Primary source: `infinitalent/components/section-scene.php`, `section-header.php`, and scene helper functions

Deliverables:

- add scene and section header components
- add section/header presets and layout variants
- add page shell and collection grid helpers
- codify content stack spacing variants

Done when:

- most new sections are assembled from stable layout APIs
- section scaffolding no longer lives as one-off markup per page

### Phase 4: Page Families

Primary source: reusable slices of `infinitalent/template-parts/page-families/*`

Deliverables:

- define initial page-family set:
  - services hub
  - detail page
  - contact page
  - summary grid/feed
  - empty state
- separate generic page families from brand-specific copy/patterns
- add helper contracts for intros, empty states, collections, cards

Done when:

- a new services site can be scaffolded mainly by selecting page families and feeding content

### Phase 5: Content Seeding and Builder Contracts

Primary source: `infinitalent/scripts/*` and structured page helper patterns

Deliverables:

- canonical content seed format
- seed command for local page data
- section schema definitions
- optional builder manifest conventions

Done when:

- a new spin-off can bootstrap content from JSON/manifests instead of manual admin entry

### Phase 6: Packaging and Quality Gates

Deliverables:

- theme zip script
- lean build option
- token audit
- basic architecture lint checklist
- optional visual regression workflow

Done when:

- handoff to staging or cPanel is repeatable
- starter regressions are caught early

## Extraction Rules

When porting from `infinitalent`, extract only code that satisfies at least one of these:

- reused across multiple page types
- controlled by semantic tokens instead of brand copy
- stable enough to become part of a starter contract

Do not extract:

- brand voice
- one-off imagery
- brand-specific service models
- copy-coupled section names
- highly specific content mappers that do not generalize

## Default Add-Ons

These are good candidates to keep or formalize as part of the starter:

- `daisyui` as the central component engine themed from local tokens
- `@phosphor-icons/core` for icon sync workflows
- `hero-patterns` for quick texture/background variation
- theme zip build script
- local seed commands
- token audit script

## Nice-to-Have Add-Ons

These should be optional modules, not hard starter dependencies:

- `Embla Carousel` for testimonials and media sliders
- `Floating UI` for advanced popovers/menus/tooltips
- screenshot regression checks with Playwright
- a `brand packs` directory with 2-3 preset palettes and typography pairings
- a small pattern registry with preview screenshots
- a starter content library for common sections
- optional block-editor integration for reusable sections
- optional REST endpoints for contact capture / lead forms

## Package Evaluation Policy

Adopt a package only if it improves at least one of:

- cross-project reuse
- shipping speed
- content authoring reliability
- deployment reliability

Reject or isolate packages that mainly add:

- framework lock-in
- React-only abstractions in a PHP-first starter
- visual sameness without improving composition speed

## Sage Evaluation Criteria

Revisit `Roots Sage` only if all of the following become true:

- at least 3 future projects would benefit from Blade/Acorn conventions
- the team explicitly wants Laravel-style template ergonomics
- build-stack churn is acceptable
- the composition system is already stable enough to survive a stack migration

Until then, system extraction has higher ROI than a theme-stack migration.

## Acceptance Criteria For Starter vNext

- Brand identity is mostly token-driven
- Reusable primitives live outside page-specific templates
- Reusable sections are assembled from helper contracts
- New page families can be shipped without inventing new structure each time
- Content seeding and packaging are repeatable
- Documentation explains where every new abstraction belongs
