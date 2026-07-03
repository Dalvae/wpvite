#!/usr/bin/env python3
"""Send all pages to WPML translation as 'Translate myself'.

Usage:
  python scripts/wpml-send-to-translation.py             # send all pages
  python scripts/wpml-send-to-translation.py --headful    # visible browser
  python scripts/wpml-send-to-translation.py --dry-run    # login + select only

Env vars (from .env): WP_URL/WP_USER/WP_PASSWORD, or WP_STAGING_* aliases.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from wpml_browser import fail, login_staging


def main() -> None:
    parser = argparse.ArgumentParser(description="WPML: send all pages to translation")
    parser.add_argument("--headful", action="store_true")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    from playwright.sync_api import sync_playwright

    with sync_playwright() as pw:
        browser, page, admin_url = login_staging(pw, headful=args.headful)

        tm_url = f"{admin_url}/admin.php?page=wpml-translation-management/menu/main.php"
        page.goto(tm_url, wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(5000)

        # ── Set 100 items per page ──
        print("setting 100/page...")
        page.evaluate("""() => {
            for (const s of document.querySelectorAll('select')) {
                if (Array.from(s.options).some(o => o.value === '100' && o.text.includes('page'))) {
                    s.value = '100';
                    s.dispatchEvent(new Event('change', {bubbles: true}));
                    return;
                }
            }
        }""")
        page.wait_for_timeout(5000)

        # ── Close WP AI Studio popup if present ──
        print("closing any popups...")
        page.evaluate("""() => {
            const popup = document.getElementById('wp-ai-studio-container');
            if (popup) popup.remove();
            const closeBtns = document.querySelectorAll('.notice-dismiss, .sg-ai-close, [aria-label="Close"]');
            closeBtns.forEach(b => b.click());
        }""")
        page.wait_for_timeout(1000)

        # ── Select all pages ──
        print("selecting all pages...")
        page.locator("button:has-text('Select All')").click()
        page.wait_for_timeout(3000)

        count = page.evaluate(
            "() => document.querySelectorAll('input[type=\"checkbox\"]:checked').length"
        )
        print(f"  selected: {count} items")

        if args.dry_run:
            print("dry-run complete")
            browser.close()
            return

        # ── Close WP AI Studio popup if present ──
        print("closing any popups...")
        page.evaluate("""() => {
            const popup = document.getElementById('wp-ai-studio-container');
            if (popup) popup.remove();
            const closeBtns = document.querySelectorAll('.notice-dismiss, .sg-ai-close, [aria-label="Close"]');
            closeBtns.forEach(b => b.click());
        }""")
        page.wait_for_timeout(1000)

        # ── Open Step 2 panel ──
        print("opening translation panel...")
        page.locator("button:has-text('Translate your content')").click()
        page.wait_for_timeout(3000)

        # ── Set all method dropdowns to "Translate myself" ──
        print("setting 'Translate myself'...")
        set_count = page.evaluate("""() => {
            let n = 0;
            for (const s of document.querySelectorAll('select')) {
                if (Array.from(s.options).some(o => o.value === 'myself')) {
                    s.value = 'myself';
                    s.dispatchEvent(new Event('change', {bubbles: true}));
                    n++;
                }
            }
            return n;
        }""")
        print(f"  set {set_count} dropdown(s)")
        page.wait_for_timeout(2000)

        # ── Click "Translate content" ──
        print("submitting...")
        page.evaluate("""() => {
            for (const b of document.querySelectorAll('button')) {
                if (b.textContent.trim().includes('Translate content')) {
                    b.disabled = false;
                    b.click();
                    return;
                }
            }
        }""")
        page.wait_for_timeout(10000)
        page.wait_for_load_state("domcontentloaded", timeout=60000)

        body = page.inner_text("body")
        if any(w in body.lower() for w in ["sent", "queue", "progress", "translation"]):
            print("translation jobs created!")
        else:
            page.screenshot(path="/tmp/wpml-send-result.png")
            print("check /tmp/wpml-send-result.png")

        browser.close()
    print("done!")


if __name__ == "__main__":
    main()
