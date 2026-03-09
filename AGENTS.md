# AGENTS.md

## Mission

This repository is evolving from a minimal WordPress + Vite starter into a reusable spin-off platform for branded marketing and services sites.

The main objective is not to add more frameworks. The main objective is to build a reusable system of:

- design foundations
- UI primitives
- section composition APIs
- page-family scaffolds
- content seeding and packaging workflows

Future changes must preserve that direction.

## Current Stack Position

Default stack:

- WordPress theme
- Vite
- Tailwind CSS v4
- daisyUI v5
- PHP templates
- AlpineJS
- Turbo

Do not introduce React, `shadcn/ui`, Astro, or Sage as defaults unless the task explicitly requires them and the architectural tradeoff is documented.

## Source of Truth

For roadmap and extraction strategy, read:

- `docs/starter-system-roadmap.md`
- `docs/infinitalent-extraction-map.md`
- `docs/section-library.md`
- `docs/pipeline.md`
- `docs/site-composer.md`
- `docs/llm-design-system-guide.md`

Repository-local docs outrank generic external design guidance. Do not rely on global frontend/design skills as the source of truth for this repo.

## Local Skills

- `.codex/skills/wpvite-design-system/SKILL.md`
  Use this as the repo-local design-system skill for UI work in this starter.

## Architecture Rules

### 1. Build layers, not one-off pages

When adding a new capability, place it in the highest reusable layer that still makes sense:

- `src/css/tokens.css`: primitives and semantic token assignments
- `src/css/daisyui-theme.css`: adapter from local tokens to the global daisyUI theme
- `src/css/design-system.css`: global foundations and accessibility baselines
- `src/css/ui-components.css`: reusable UI adapters and component refinements
- `src/css/page-primitives.css`: reusable page shells and composition primitives
- `inc/template-helpers.php`: helper APIs for classes, attributes, scenes, presets, primitives
- `inc/page-helpers.php`: page-family and content-contract helpers
- `components/`: render-only reusable PHP components
- `template-parts/page/`: generic page primitives
- `template-parts/page-families/`: reusable page-family compositions
- `template-parts/sections/`: reusable section compositions

Do not place reusable abstractions directly inside a single template file if they are likely to be reused.

### 2. Extract the system, not the brand

When porting from `infinitalent` or any future project:

- keep semantic tokens
- keep layout APIs
- keep helper contracts
- keep reusable components
- keep operational tooling

Do not copy:

- brand names
- brand-specific copy
- one-off image assumptions
- page-specific CSS as a system dependency
- content parsing logic that only fits one project

### 3. Prefer semantic APIs

Components and helpers must consume semantic values.

Prefer:

- `surface = muted`
- `variant = primary`
- `layout = centered`
- `tone = inverse`

Avoid:

- hardcoded hex colors
- magic spacing values repeated across templates
- helper names tied to one business domain

### 4. Keep components render-only

Reusable components in `components/` should:

- receive normalized args
- render markup
- delegate class logic to helpers when complexity grows

Do not bury business logic, query logic, or heavy content mapping inside small components.

### 5. Page families compose primitives

`template-parts/page-families/` should compose:

- page primitives
- section components
- helper contracts

They should not become another layer of duplicated styling.

### 6. Package policy

Default-safe packages:

- Tailwind CSS
- daisyUI
- AlpineJS
- Turbo
- `@phosphor-icons/core`
- `hero-patterns`

Optional packages need justification:

- `Embla Carousel`
- `Floating UI`
- screenshot regression tooling
- content seeding utilities

Avoid adding packages that only make sense in a React-first architecture unless the task explicitly asks for that architecture.

### 7. Documentation is part of the change

If you add a new reusable abstraction, update the docs that explain where it belongs.

Minimum expectation:

- update `README.md` if the public workflow changes
- update `docs/starter-system-roadmap.md` if a new layer or phase changes
- update `docs/infinitalent-extraction-map.md` if extraction scope changes
- update `docs/site-composer.md` if manifest or composer behavior changes

### 8. Site Composer

The repository now includes a declarative site composer layer:

- presets registry in `config/site-composer.presets.json`
- example manifest in `config/site-composer.example.json`
- CLI normalizer in `scripts/compose-site.mjs`

When introducing a new implemented design-system preset, page family, or addon, update the composer registry so new spin-offs can be declared instead of hand-assembled.

### 9. Pipeline

The current preferred fast-spin path is pipeline-driven:

- `config/brand-presets.json` defines switchable brand themes
- `config/site.config.json` defines the active site
- `manifests/pages/*.json` define page-level structure
- `scripts/spin-init.mjs` initializes the active site config
- `scripts/pipeline-validate.mjs` validates the chosen setup

Treat this pipeline as more important than the site composer when the task is about speeding up spin-offs inside this starter.

### 10. Naming

Use neutral starter naming for shared helpers and classes.

Good:

- `starter_merge_classes`
- `starter_get_scene_shell_classes`
- `starter_get_page_section_header_args`

Bad:

- helper names that preserve an old client/brand name

### 11. Definition of done for reusable work

A change is not complete if it only works for one page and leaves no reusable contract behind.

Reusable work should:

- use semantic tokens
- avoid brand leakage
- document the API surface
- fit one of the architecture layers above

### 12. LLM UI Execution

When implementing UI changes in this repo:

- assume WordPress + PHP templates + Tailwind CSS v4 + daisyUI v5 unless the task explicitly says otherwise
- treat daisyUI as the component engine, not the public API
- prefer starter helpers and wrapper classes over repeated raw daisyUI class strings in templates
- prefer semantic tokens and text roles over ad hoc Tailwind bundles
- do not import React-first patterns like CVA, `shadcn/ui`, or component APIs that do not fit PHP templates
- do not add inline styles or page-specific CSS for reusable concerns
- move branching class logic into helpers when templates start getting noisy

## Default Decision Heuristics

If there is a choice between:

- changing the stack
- improving the reusable system inside the current stack

Prefer improving the reusable system unless the user explicitly requests a different stack.

If there is a choice between:

- copying a polished section wholesale
- extracting its primitives and composition logic first

Prefer extracting primitives and composition logic first.
