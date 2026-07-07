#!/usr/bin/env node

import { mkdir, readFile, writeFile, readdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');
const distDir = resolve(root, 'dist');
const manifestsDir = resolve(root, 'manifests', 'pages');
const criticalDir = resolve(distDir, 'critical');

const args = process.argv.slice(2);
const verifyOnly = args.includes('--verify-only');
const baseUrl = (process.env.CRITICAL_BASE_URL || process.env.WP_STAGING_URL || process.env.PERF_URL || 'http://localhost:8000').replace(/\/$/, '');
const onlySlug = process.env.CRITICAL_ONLY || '';
const explicitTargets = process.env.CRITICAL_TARGETS || '';
const artifactRoot = resolve(root, process.env.CRITICAL_ARTIFACT_DIR || 'docs/qa-artifacts/critical');
const runId = process.env.CRITICAL_RUN_ID || new Date().toISOString().replace(/[:.]/g, '-');
const runDir = resolve(artifactRoot, runId);
const maxDiffRatio = Number(process.env.CRITICAL_MAX_DIFF_RATIO || '0.025');
const minBytes = Number(process.env.CRITICAL_MIN_BYTES || '5000');
const maxBytes = Number(process.env.CRITICAL_MAX_BYTES || '130000');
const viewports = [{ name: 'mobile', width: 393, height: 873, deviceScaleFactor: 2, isMobile: true, hasTouch: true }];

async function discoverLandingPages() {
  const pages = [];
  if (existsSync(manifestsDir)) {
    const files = (await readdir(manifestsDir)).filter((file) => file.endsWith('.json'));
    for (const file of files) {
      let manifest;
      try { manifest = JSON.parse(await readFile(resolve(manifestsDir, file), 'utf8')); } catch { continue; }
      if (!manifest?.landing) continue;
      const slug = manifest.slug || file.replace(/\.json$/, '');
      const path = (manifest.url_path || `/${slug}`).replace(/\/?$/, '/');
      pages.push({ slug, url: `${baseUrl}${path}` });
    }
  }
  if (!pages.length && explicitTargets) {
    for (const target of explicitTargets.split(',').map((item) => item.trim()).filter(Boolean)) {
      const [slugPart, pathPart] = target.split('=');
      const slug = (slugPart || '').replace(/^\/+|\/+$/g, '');
      const path = (pathPart || `/${slug}`).replace(/\/?$/, '/');
      if (slug) pages.push({ slug, url: /^https?:\/\//.test(path) ? path : `${baseUrl}${path.startsWith('/') ? '' : '/'}${path}` });
    }
  }
  return pages.filter((page) => !onlySlug || page.slug === onlySlug);
}

async function getBuiltCss() {
  const manifest = JSON.parse(await readFile(resolve(distDir, 'manifest.json'), 'utf8'));
  const entryKey = Object.keys(manifest).find((key) => key.endsWith('/theme.js') || key === 'src/theme.js') || Object.keys(manifest).find((key) => manifest[key]?.css?.length);
  const cssFile = manifest[entryKey]?.css?.[0];
  if (!cssFile) throw new Error('No CSS entry found in dist/manifest.json');
  return { file: cssFile, css: await readFile(resolve(distDir, cssFile), 'utf8') };
}

function extractBlock(css, start) {
  let depth = 0;
  let i = start;
  while (i < css.length) {
    if (css[i] === '{') depth++;
    else if (css[i] === '}') { depth--; if (depth === 0) return { end: i + 1 }; }
    i++;
  }
  return { end: css.length };
}

function parseTopLevelBlocks(css) {
  const blocks = [];
  let i = 0;
  while (i < css.length) {
    while (i < css.length && /\s/.test(css[i])) i++;
    if (i >= css.length) break;
    if (css.startsWith('/*', i)) { const end = css.indexOf('*/', i); if (end === -1) break; i = end + 2; continue; }
    const start = i;
    if (css.startsWith('@import', i)) { const semi = css.indexOf(';', i); if (semi === -1) break; blocks.push({ start, end: semi + 1, raw: css.slice(start, semi + 1), type: 'import' }); i = semi + 1; continue; }
    const brace = css.indexOf('{', i);
    const semi = css.indexOf(';', i);
    if (semi !== -1 && (brace === -1 || semi < brace)) { i = semi + 1; continue; }
    if (brace === -1) break;
    const prelude = css.slice(i, brace).trim();
    const { end } = extractBlock(css, brace);
    blocks.push({ start, end, raw: css.slice(start, end), type: prelude.startsWith('@') ? 'at' : 'rule', prelude });
    i = end;
  }
  return blocks;
}

function splitLayerPrelude(prelude) {
  const match = /^@layer\s+([^\s{]+)/i.exec(prelude || '');
  return match ? match[1].split(',').map((part) => part.trim()) : [];
}

function overlaps(aStart, aEnd, bStart, bEnd) { return aStart < bEnd && bStart < aEnd; }

function filterNestedCss(css, ranges, baseOffset = 0) {
  const out = [];
  let i = 0;
  while (i < css.length) {
    while (i < css.length && /\s/.test(css[i])) i++;
    if (i >= css.length) break;
    if (css.startsWith('/*', i)) { const endComment = css.indexOf('*/', i); if (endComment === -1) break; i = endComment + 2; continue; }
    const start = i;
    if (css.startsWith('@import', i)) { const semi = css.indexOf(';', i); if (semi === -1) break; i = semi + 1; continue; }
    const brace = css.indexOf('{', i);
    const semi = css.indexOf(';', i);
    if (semi !== -1 && (brace === -1 || semi < brace)) { i = semi + 1; continue; }
    if (brace === -1) break;
    const prelude = css.slice(i, brace).trim();
    const { end } = extractBlock(css, brace);
    if (/^@font-face/i.test(prelude)) { i = end; continue; }
    if (/^@property\s+/i.test(prelude) || /^@theme\b/i.test(prelude)) { out.push(css.slice(start, end)); i = end; continue; }
    if (prelude.startsWith('@')) {
      const filtered = filterNestedCss(css.slice(brace + 1, end - 1), ranges, baseOffset + brace + 1);
      if (filtered) out.push(`${prelude}{${filtered}}`);
      i = end;
      continue;
    }
    if (ranges.some((range) => overlaps(baseOffset + start, baseOffset + end, range.startOffset, range.endOffset))) out.push(css.slice(start, end));
    i = end;
  }
  return out.join('\n');
}

function normalizeCss(css) {
  return css.replace(/@import[^;]+;/gi, '').replace(/@font-face\s*{[^}]*}/gi, '').replace(/\/\*# sourceMappingURL=.*?\*\//g, '');
}

function safetyPatch() {
  return ['html{background:#fff}', 'body{margin:0}', '@media (prefers-reduced-motion:reduce){*,::before,::after{animation-duration:1ms!important;transition-duration:1ms!important;scroll-behavior:auto!important}}'].join('');
}

function buildCriticalCss(fullCss, usedRanges) {
  const keep = [];
  for (const block of parseTopLevelBlocks(fullCss)) {
    if (block.type === 'import' || /^@font-face/i.test(block.prelude || '')) continue;
    if (/^@property\s+/i.test(block.prelude || '') || /^@theme\b/i.test(block.prelude || '')) { keep.push(block.raw); continue; }
    const layerNames = splitLayerPrelude(block.prelude);
    if (layerNames.length) {
      const innerStart = block.raw.indexOf('{') + 1;
      if (layerNames.some((name) => name === 'properties' || name === 'theme')) keep.push(block.raw);
      else {
        const filtered = filterNestedCss(block.raw.slice(innerStart, -1), usedRanges, block.start + innerStart);
        if (filtered) keep.push(`${block.prelude}{${filtered}}`);
      }
      continue;
    }
    if (usedRanges.some((range) => overlaps(block.start, block.end, range.startOffset, range.endOffset))) keep.push(block.raw);
  }
  return normalizeCss(keep.join('\n') + safetyPatch());
}

function cssUrlMatchers(cssFile) {
  const escaped = cssFile.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return { glob: `**/${cssFile}`, regex: new RegExp(`/${escaped}(?:\\?[^#]*)?(?:#.*)?$`) };
}

async function installLocalCssRoute(page, cssFile, css) {
  await page.route(cssUrlMatchers(cssFile).glob, async (route) => route.fulfill({ status: 200, contentType: 'text/css; charset=utf-8', body: css }));
}

async function collectThemeUsage(browser, pageInfo, viewport, cssFile, fullCss) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: viewport.deviceScaleFactor, isMobile: viewport.isMobile, hasTouch: viewport.hasTouch });
  await installLocalCssRoute(page, cssFile, fullCss);
  const cdp = await page.context().newCDPSession(page);
  const themeSheetIds = new Set();
  const { regex } = cssUrlMatchers(cssFile);
  cdp.on('CSS.styleSheetAdded', ({ header }) => { if (header?.sourceURL && regex.test(header.sourceURL)) themeSheetIds.add(header.styleSheetId); });
  await cdp.send('DOM.enable');
  await cdp.send('CSS.enable');
  await cdp.send('CSS.startRuleUsageTracking');
  await page.goto(pageInfo.url, { waitUntil: 'networkidle', timeout: 60000 });
  await page.addStyleTag({ content: '*,::before,::after{animation-duration:1ms!important;transition-duration:1ms!important;scroll-behavior:auto!important}' });
  await page.waitForTimeout(250);
  const usage = await cdp.send('CSS.stopRuleUsageTracking');
  await page.close();
  const maxRuleSpan = Math.max(12000, Math.floor(fullCss.length * 0.08));
  return usage.ruleUsage.filter((rule) => rule.used && themeSheetIds.has(rule.styleSheetId) && (rule.endOffset - rule.startOffset) <= maxRuleSpan);
}

async function captureFull(browser, pageInfo, viewport, cssFile, fullCss, outPath) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: viewport.deviceScaleFactor, isMobile: viewport.isMobile, hasTouch: viewport.hasTouch });
  await installLocalCssRoute(page, cssFile, fullCss);
  const errors = [];
  const failed = [];
  page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });
  page.on('requestfailed', (request) => failed.push(request.url()));
  await page.goto(pageInfo.url, { waitUntil: 'networkidle', timeout: 60000 });
  await page.addStyleTag({ content: '*,::before,::after{animation-duration:1ms!important;transition-duration:1ms!important;scroll-behavior:auto!important}' });
  await page.waitForTimeout(250);
  await page.screenshot({ path: outPath, fullPage: false });
  await page.close();
  return { errors, failed: failed.filter((url) => !url.includes('google-analytics.com/g/collect')) };
}

function htmlWithCriticalOnly(html, cssFile, criticalCss) {
  const { regex } = cssUrlMatchers(cssFile);
  let out = html.replace(/<link\b[^>]+href=["']([^"']+)["'][^>]*>\s*/gi, (tag, href) => (regex.test(href) ? '' : tag));
  const criticalTag = `<style id="starter-critical-css-test">${criticalCss}</style>`;
  if (/<style\b[^>]*id=["']starter-critical-css-[^"']+["'][\s\S]*?<\/style>/i.test(out)) out = out.replace(/<style\b[^>]*id=["']starter-critical-css-[^"']+["'][\s\S]*?<\/style>/i, criticalTag);
  else out = out.replace('</head>', `${criticalTag}</head>`);
  return out;
}

async function captureCriticalOnly(browser, pageInfo, viewport, cssFile, criticalCss, outPath) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: viewport.deviceScaleFactor, isMobile: viewport.isMobile, hasTouch: viewport.hasTouch });
  await page.route(pageInfo.url, async (route) => {
    const response = await route.fetch();
    await route.fulfill({ status: response.status(), contentType: 'text/html; charset=utf-8', body: htmlWithCriticalOnly(await response.text(), cssFile, criticalCss) });
  });
  await page.route(cssUrlMatchers(cssFile).glob, async (route) => route.abort());
  await page.goto(pageInfo.url, { waitUntil: 'networkidle', timeout: 60000 });
  await page.addStyleTag({ content: '*,::before,::after{animation-duration:1ms!important;transition-duration:1ms!important;scroll-behavior:auto!important}' });
  await page.waitForTimeout(250);
  await page.screenshot({ path: outPath, fullPage: false });
  await page.close();
}

async function compareScreenshots(browser, beforePath, afterPath) {
  const beforeData = `data:image/png;base64,${(await readFile(beforePath)).toString('base64')}`;
  const afterData = `data:image/png;base64,${(await readFile(afterPath)).toString('base64')}`;
  const page = await browser.newPage({ viewport: { width: 100, height: 100 } });
  const result = await page.evaluate(async ({ beforePath, afterPath }) => {
    const load = (src) => new Promise((resolve, reject) => { const img = new Image(); img.onload = () => resolve(img); img.onerror = reject; img.src = src; });
    const [a, b] = await Promise.all([load(beforePath), load(afterPath)]);
    const width = Math.min(a.naturalWidth, b.naturalWidth);
    const height = Math.min(a.naturalHeight, b.naturalHeight);
    const canvas = document.createElement('canvas');
    canvas.width = width; canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(a, 0, 0, width, height); const ad = ctx.getImageData(0, 0, width, height).data;
    ctx.clearRect(0, 0, width, height); ctx.drawImage(b, 0, 0, width, height); const bd = ctx.getImageData(0, 0, width, height).data;
    let changed = 0; let sum = 0;
    for (let i = 0; i < ad.length; i += 4) { const delta = Math.abs(ad[i] - bd[i]) + Math.abs(ad[i + 1] - bd[i + 1]) + Math.abs(ad[i + 2] - bd[i + 2]); if (delta > 45) changed++; sum += delta; }
    return { width, height, changedPixels: changed, totalPixels: width * height, diffRatio: changed / (width * height), meanDelta: sum / (width * height * 3) };
  }, { beforePath: beforeData, afterPath: afterData });
  await page.close();
  return result;
}

async function main() {
  const pages = await discoverLandingPages();
  if (!pages.length) throw new Error('No landing manifests found. Add landing=true to manifests/pages/*.json or set CRITICAL_TARGETS="slug=/path/,other=https://example.test/path/".');
  const { css: fullCss, file: cssFile } = await getBuiltCss();
  await mkdir(criticalDir, { recursive: true });
  await mkdir(runDir, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const report = { generatedAt: new Date().toISOString(), baseUrl, cssFile, maxDiffRatio, verifyOnly, pages: [] };
  let failed = false;
  for (const pageInfo of pages) {
    for (const viewport of viewports) {
      const name = `${pageInfo.slug}.${viewport.name}`;
      const legacyName = pageInfo.slug;
      const artifactBase = resolve(runDir, name);
      let criticalCss;
      if (verifyOnly) {
        const candidate = resolve(criticalDir, `${name}.css`);
        const legacy = resolve(criticalDir, `${legacyName}.css`);
        criticalCss = await readFile(existsSync(candidate) ? candidate : legacy, 'utf8');
      } else {
        const ranges = await collectThemeUsage(browser, pageInfo, viewport, cssFile, fullCss);
        criticalCss = buildCriticalCss(fullCss, ranges);
        const bytes = Buffer.byteLength(criticalCss);
        if (bytes < minBytes || bytes > maxBytes) throw new Error(`${name} critical size out of range: ${bytes}`);
        await writeFile(resolve(criticalDir, `${name}.css`), criticalCss, 'utf8');
        if (viewport.name === 'mobile') await writeFile(resolve(criticalDir, `${legacyName}.css`), criticalCss, 'utf8');
      }
      const criticalPath = `${artifactBase}.critical.png`;
      const fullPath = `${artifactBase}.full.png`;
      await captureCriticalOnly(browser, pageInfo, viewport, cssFile, criticalCss, criticalPath);
      const fullInfo = await captureFull(browser, pageInfo, viewport, cssFile, fullCss, fullPath);
      const diff = await compareScreenshots(browser, criticalPath, fullPath);
      const bytes = Buffer.byteLength(criticalCss);
      const result = { slug: pageInfo.slug, url: pageInfo.url, viewport: viewport.name, criticalCss: `dist/critical/${name}.css`, productionCss: viewport.name === 'mobile' ? `dist/critical/${legacyName}.css` : null, bytes, diff, consoleErrors: fullInfo.errors.length, failedRequests: fullInfo.failed.length, artifacts: { critical: criticalPath, full: fullPath } };
      report.pages.push(result);
      const ok = diff.diffRatio <= maxDiffRatio && fullInfo.errors.length === 0 && fullInfo.failed.length === 0;
      if (!ok) failed = true;
      console.log(`${ok ? 'OK' : 'FAIL'} ${name}: ${(bytes / 1024).toFixed(1)} KB, diff ${(diff.diffRatio * 100).toFixed(2)}%, console ${fullInfo.errors.length}, failed ${fullInfo.failed.length}`);
    }
  }
  await browser.close();
  await writeFile(resolve(criticalDir, 'manifest.json'), JSON.stringify(report, null, 2) + '\n');
  await writeFile(resolve(runDir, 'report.json'), JSON.stringify(report, null, 2) + '\n');
  console.log(`Artifacts: ${runDir}`);
  if (failed) process.exit(1);
}

main().catch((error) => { console.error(error); process.exit(1); });
