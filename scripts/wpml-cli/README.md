# WPML CLI Toolkit (Read-Only)

Small diagnostics for inspecting WPML state through WP-CLI. These scripts are intentionally read-only: they only inspect WordPress options, plugin/runtime availability, and WPML database tables. They do not insert, update, delete, create jobs, import translations, or export files.

Run these before any future write-capable WPML commands.

## Commands

```bash
wp eval-file scripts/wpml-cli/status.php
wp eval-file scripts/wpml-cli/list-jobs.php limit=50
```

Optional job filters:

```bash
wp eval-file scripts/wpml-cli/list-jobs.php -- --status=10 --language=es --limit=50
wp eval-file scripts/wpml-cli/list-jobs.php status=10 language=es limit=50
```

Output is JSON to stdout. Table names use the active WordPress database prefix via `$wpdb->prefix`; no site-specific prefix is hardcoded.

## Controlled write smoke: language-linking only

This does **not** create, export, import, or complete Translation Management jobs. It only creates two draft pages with an `OMC_SMOKE_WPML_*` marker, links them with documented WPML language hooks, verifies the shared translation group, and deletes the marked pages.

```bash
wp eval-file scripts/wpml-cli/smoke-link-pages.php
wp eval-file scripts/wpml-cli/smoke-link-pages.php apply=1 confirm=OMC_SMOKE_WPML
wp eval-file scripts/wpml-cli/smoke-link-pages.php cleanup=1 confirm=OMC_SMOKE_WPML
```

Run a DB backup before `apply=1` on any shared environment.
