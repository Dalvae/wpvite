# Fluent Forms CLI Toolkit

Small Fluent Forms helpers for pre-change audits and controlled smoke testing.

Run through WP-CLI on the WordPress server:

```bash
wp eval-file scripts/fluentforms-cli/status.php
wp eval-file scripts/fluentforms-cli/list-forms.php limit=50 include-fields=1
wp eval-file scripts/fluentforms-cli/list-forms.php form-id=3
wp eval-file scripts/fluentforms-cli/smoke-test.php
wp eval-file scripts/fluentforms-cli/smoke-test.php apply=1 confirm=OMC_SMOKE_FLUENT
wp eval-file scripts/fluentforms-cli/smoke-test.php cleanup=1 apply=1 confirm=OMC_SMOKE_FLUENT
```

## Safety

- `status.php` and `list-forms.php` are read-only inspection commands: they do not insert, update, delete, create forms, update forms, delete entries, or mutate exports.
- `smoke-test.php` is intentionally write-capable for functionality testing: with `apply=1 confirm=OMC_SMOKE_FLUENT` it creates a temporary form, creates a temporary submission when the live schema supports it, reads them back, and deletes the test artifacts.
- Without `apply=1 confirm=OMC_SMOKE_FLUENT`, `smoke-test.php` defaults to dry-run and performs no writes.
- Smoke-test artifacts use the unique `OMC_SMOKE_FLUENT_` marker plus a timestamp/random `run_id` and the script only deletes rows matching that marker/run.
- `cleanup=1` removes orphan `OMC_SMOKE_FLUENT_*` smoke-test artifacts and still requires `apply=1 confirm=OMC_SMOKE_FLUENT` to mutate data.
- Output is JSON to stdout by default.
- Database table names use the active WordPress `$wpdb->prefix`; no live prefix is hardcoded.
- Scripts include guards for WordPress execution, Fluent Forms plugin detection, class/function readiness checks, table presence, and schema differences.

## Scripts

- `status.php` reports site/theme/runtime details, detected Fluent Forms plugins/classes/functions/hooks, likely Fluent Forms table presence/counts, and submission totals/latest timestamp when available.
- `list-forms.php` lists forms with basic metadata and submission counts. Optional `--include-fields=1` returns compact field summaries only, not full raw form configs.
- `smoke-test.php` plans or runs a self-cleaning smoke test: create a temporary form, read it back, optionally create a temporary submission when the schema supports it, delete all test artifacts, and report remaining marker rows.
