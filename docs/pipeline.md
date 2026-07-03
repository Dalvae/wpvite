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
- Active site config / site globals: `config/site.config.json`
- Page manifests: `manifests/pages/*.json`
- Init script: `scripts/spin-init.mjs`
- Validation script: `scripts/pipeline-validate.mjs`

## Commands

Initialize a site quickly:

```bash
pnpm spin:init --name "Northshore Advisory" --slug northshore-advisory --preset editorial-signal --tagline "Operational clarity for growing service teams" --email hello@example.com --phone "+1 555 000 0000"
```

Apply the configured site globals to local WordPress options with WP-CLI:

```bash
WP_PATH=/var/www/site/current pnpm site:apply
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
- the active site branding/contact globals are controlled from one place
- the same section/page-family contracts can be reused across spin-offs

## Site globals

`config/site.config.json` is the single source of truth for site-level values:

- site name
- slug
- tagline
- primary email
- primary phone
- active brand preset

Templates read these through `starter_get_site_*()` helpers in
`inc/site-config.php`. Scripts read the same JSON file. `pnpm site:apply`
syncs the values into WordPress options via local WP-CLI so WP/admin/plugin
workflows can also use them.

Do not hardcode those values in templates, seeds, WPML scripts, form scripts, or
deployment scripts. Add new site-wide values to `config/site.config.json` first,
then expose them through helpers/scripts.

## Current Scope

Today the pipeline does:

- validate the active brand preset
- validate required site globals
- validate page manifest structure
- validate referenced section types
- make the active site branding switcheable from config

It does not yet:

- seed WordPress content automatically
- create pages in WP
- sync manifests into ACF or builder rows
- generate screenshots or visual QA output
