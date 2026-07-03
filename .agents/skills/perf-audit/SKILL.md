---
name: perf-audit
description: Run non-interactive Lighthouse/LHCI audits for WPVite WordPress/Vite sites and guide one-fix-at-a-time mobile performance optimization loops.
compatibility: opencode
metadata:
  workflow: performance
  stack: wordpress-vite
---

## What I do

- Audit WPVite performance on local, staging, or production URLs.
- Prefer mobile Lighthouse/LHCI runs because WPVite must stay mobile-first.
- Read generated reports instead of inventing metrics.
- Recommend one focused fix per iteration, then re-run the audit.

## When to use me

Use this skill when the task is to measure, debug, or improve WPVite web performance, especially:

- LCP, CLS, INP, TBT, or Speed Index regressions
- hero/media/font optimization
- comparing local vs staging/prod performance
- preparing a repeatable performance check before deploy

Do not use this for general UI redesign work; route visual design to a designer first, then use this skill to verify performance impact.

## Required inputs

Ask for or infer:

- target URL, for example `http://localhost:8000` or a staging URL
- environment: `local`, `staging`, or `production`
- optional focus metric: `lcp`, `cls`, `inp`, `tbt`, `speed-index`, or `overall`

If the target is local, confirm WordPress is running and reachable from the shell before auditing.

## Non-interactive commands

Use headless, non-interactive commands only.

WPVite includes repo-local `lighthouse` and `lhci` dev dependencies. Prefer the package scripts below over global installs.

Direct Lighthouse mobile audit:

```bash
PERF_URL="$URL" pnpm perf:lighthouse:local
```

Staging Lighthouse mobile audit:

```bash
WP_STAGING_URL="$URL" pnpm perf:lighthouse:staging
```

LHCI using `lighthouserc.cjs`:

```bash
pnpm perf:lhci
```

Local LHCI defaulting to `http://localhost:8000`:

```bash
pnpm perf:lhci:local
```

Set `LHCI_URL`, `WP_STAGING_URL`, or `WP_BASE_URL` to change the audited URL. Reports are written to `.lighthouseci/`, which is ignored by git.

## Workflow

1. Confirm the URL is reachable.
2. Run a mobile Lighthouse/LHCI audit.
3. Extract actual metrics from the generated JSON/report:
   - performance score
   - LCP
   - CLS
   - TBT/INP proxy where available
   - Speed Index
4. Identify the likely bottleneck.
5. Apply or recommend one small fix.
6. Re-run the same audit command.
7. Compare before/after and report only measured changes.

## WPVite-specific priorities

Check these first:

- hero media LCP: eager/priority image, correct mobile size, no mobile video download
- fonts: no CSS `@import`; use preconnect/enqueue or self-hosted fonts
- mobile paint cost: avoid `background-attachment: fixed`, heavy blur, and `backdrop-filter`
- CLS: width/height or aspect-ratio for logos, hero media, and visible cards
- JS/INP: avoid unnecessary observers/animations on mobile
- WordPress runtime: plugins, third-party scripts, TTFB, cache/CDN behavior

## Local vs staging/prod

- Local is good for fast iteration but often looks better than real hosting.
- Staging is preferred for deploy confidence.
- Production/CrUX/PageSpeed field data is the final truth when available.

Do not claim production improvement from local-only Lighthouse runs.

## Safety rules

- Do not use interactive browser UI.
- Do not add pre-commit hooks for Lighthouse; it is too slow and variable.
- Do not install global tools as the project workflow.
- Prefer repo-local dev dependencies and scripts when formalizing the workflow.
- Keep report artifacts out of git unless the user explicitly wants checked-in baselines.
