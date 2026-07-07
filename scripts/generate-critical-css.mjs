#!/usr/bin/env node

// Backward-compatible entrypoint for starter sites that previously called the
// legacy critical CSS generator. The deterministic Playwright implementation is
// now the canonical generator.
await import('./generate-critical-playwright.mjs');
