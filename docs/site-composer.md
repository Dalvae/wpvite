# Site Composer

`site composer` is the first declarative layer for spin-offs.

The goal is to stop starting each project from a blank theme plus ad-hoc decisions. Instead, a site can be described by:

- site identity
- design system preset
- page-family selection
- addon selection

## Files

- Presets registry: `config/site-composer.presets.json`
- Example manifest: `config/site-composer.example.json`
- CLI: `scripts/compose-site.mjs`

## Usage

Preview a site composition:

```bash
pnpm compose:site config/site-composer.example.json
```

Write the normalized output to `tmp/site-composer-output.json`:

```bash
pnpm compose:site:write config/site-composer.example.json
```

## Current Scope

Today the composer is a normalizer and validator. It does not yet:

- create WordPress pages automatically
- seed content into WP
- rewrite tokens or theme assets
- switch multiple design-system implementations

It is intentionally honest: one implemented design-system preset, implemented page-family presets, and a clear place to extend the workflow.

The current implemented preset assumes:

- local tokens as source of truth
- `starterspin` as the active daisyUI theme adapter
- starter helpers/components as the stable API for templates

## Expected Next Iterations

1. Connect composer output to local seed scripts.
2. Generate page-family stubs or seed manifests automatically.
3. Add multiple implemented design-system presets.
4. Allow brand token overrides from the manifest.
