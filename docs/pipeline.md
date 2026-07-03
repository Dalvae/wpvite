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
- Media map: `manifests/media-map.json` (written by `scripts/upload-images-staging.py`)
- Carbon/WPML field map: `config/section-field-maps.json`
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

Upload images into WordPress and write the filename → attachment ID map used by
manifests:

```bash
python3 scripts/upload-images-staging.py --dry-run
python3 scripts/upload-images-staging.py --input brief/images --output manifests/media-map.json
python3 scripts/upload-images-staging.py --write-config-map
```

The upload script follows the same REST conventions as the seed scripts: it loads
`.env` files with `load_project_env()`, authenticates with `WP_BASE_URL`/`WP_USER`/
`WP_APP_PASSWORD` (or staging aliases), and supports `--dry-run`.

SVG files are intentionally excluded by default. If a project needs SVG uploads,
sanitize them first and make that project-specific policy explicit.

Media source folders and automation scripts are development inputs only. Theme
release zips must not include `scripts/`, `src/`, `brief/`, or source `images/`;
content media should be uploaded to WordPress and referenced through the media
map/attachment IDs instead of shipping inside the theme package.

Composer `vendor/` is the exception: release zips must include production
Composer dependencies because the theme loads `vendor/autoload.php` at runtime.
Run `composer install --no-dev --prefer-dist --optimize-autoloader
--no-interaction` before packaging, or let `scripts/build-zip.sh` run it when
Composer is available.

## Manifest → Carbon Fields → WPML contract

Reusable section content must travel through the full contract:

1. Add fields to `config/section-field-maps.json` for each page/section slot.
2. Put content in `manifests/pages/*.json` using those same field names.
3. Run the content seeder so manifest values become per-field Carbon Fields meta.
4. Regenerate/check `wpml-config.xml` from the field map.

If a field only exists in a manifest JSON blob and is not mapped in
`config/section-field-maps.json`, Carbon Fields will not expose it as a normal
field and WPML will not reliably see or translate it.

For fields stored as `json`, the current WPML config exposes the whole Carbon
meta value. Do not assume nested keys inside JSON blobs are independently safe
for translators until a real WPML XLIFF export/import has been verified. The
pipeline now fails if `config/section-field-maps.json` and the manifest section
order/types drift, because otherwise content can be seeded into the wrong meta
prefix silently.

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
- seed content from manifests with the Python REST scripts when explicitly run
- upload media from `brief/images/` and write mapping JSON when explicitly run

It does not yet:

- create pages in WP
- sync manifests into ACF or builder rows
- generate screenshots or visual QA output
