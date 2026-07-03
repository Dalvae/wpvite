# WPML Translation Pipeline

Reusable WPML/XLIFF flow for spin-off sites based on this starter.

## Commands

```bash
pnpm wpml:generate
pnpm wpml:check
pnpm wpml:send:dry
pnpm wpml:send
pnpm wpml:export:extract
pnpm translate:init translations/xliff-source/<exported-file>.zip
pnpm translate:qa
pnpm translate:pack translations/translated-import.zip
pnpm wpml:import --zip translations/translated-import.zip
```

## Flow

1. Generate `wpml-config.xml` from field maps and manifests.
2. Send pages to the WPML translation queue.
3. Export XLIFF jobs.
4. Initialize a local translation workdir under `tmp/translation-pipeline/`.
5. Translate files in `tmp/translation-pipeline/translated/` using the prompt in
   `prompts/translation/translator.prompt.md`.
6. Run QA to preserve XML structure, trans-unit IDs, and PHP serialized wrappers.
7. Pack translated XLIFF files.
8. Import into WPML.

## Policy

- Keep this as the single reusable translation pipeline.
- Do not seed Spanish pages manually. Spanish pages/translations are created by
  WPML jobs and XLIFF import, not by separate `seed-spanish-*` scripts.
- Forms should use Fluent Forms. Form labels/messages and notification emails
  should be translated through WPML, not by custom Spanish seed scripts.
- Prefer WP-CLI local on the VPS for WordPress operations whenever possible.
- Avoid ad hoc scripts per language/site unless they later become reusable.
- Prefer REST/WP-CLI for content sync. Keep Playwright limited to WPML screens
  where no stable programmatic path exists.

## Environment

The Playwright-backed WPML scripts read credentials from `.env`, `.env.local`,
or `.env.live`:

```bash
WP_URL=https://example.com
WP_ADMIN_URL=https://example.com/wp-admin
WP_USER=automation-user
WP_PASSWORD=application-or-login-password
```

Legacy `WP_STAGING_*` names are still accepted as aliases.
