# Pipeline

The current fast-spin workflow is:

1. choose a brand preset
2. initialize the active site config
3. edit page manifests
4. validate the pipeline
5. build and ship

This keeps section choice manual while reducing repetitive setup work.

## Files

- Brand presets: `config/brand-presets.json`
- Active site config: `config/site.config.json`
- Page manifests: `manifests/pages/*.json`
- Init script: `scripts/spin-init.mjs`
- Validation script: `scripts/pipeline-validate.mjs`

## Commands

Initialize a site quickly:

```bash
pnpm spin:init --name "Northshore Advisory" --slug northshore-advisory --preset editorial-signal --tagline "Operational clarity for growing service teams"
```

Validate the current config and page manifests:

```bash
pnpm pipeline:validate
```

## Why this matters

The starter no longer needs a heavy composer to decide sections.

Instead:

- sections are chosen manually
- the pipeline validates the chosen system
- the active site branding is controlled from one place
- the same section/page-family contracts can be reused across spin-offs

## Current Scope

Today the pipeline does:

- validate the active brand preset
- validate page manifest structure
- validate referenced section types
- make the active site branding switcheable from config

It does not yet:

- seed WordPress content automatically
- create pages in WP
- sync manifests into ACF or builder rows
- generate screenshots or visual QA output
