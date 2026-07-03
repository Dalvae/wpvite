#!/usr/bin/env python3
"""Export all WPML translation jobs as XLIFF 1.2 ZIP.

Usage:
  python scripts/wpml-export-xliff.py             # export all jobs
  python scripts/wpml-export-xliff.py --headful    # visible browser
  python scripts/wpml-export-xliff.py --extract    # also unzip into extracted/

Downloads go to translations/xliff-source/.
Env vars (from .env): WP_URL/WP_USER/WP_PASSWORD, or WP_STAGING_* aliases.
"""

from __future__ import annotations

import argparse
import sys
import zipfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from wpml_browser import fail, goto_queue, login_staging

XLIFF_DIR = Path(__file__).parent.parent / "translations" / "xliff-source"
EXTRACTED_DIR = XLIFF_DIR / "extracted"


def main() -> None:
    parser = argparse.ArgumentParser(description="WPML: export XLIFF from translation queue")
    parser.add_argument("--headful", action="store_true")
    parser.add_argument("--extract", action="store_true", help="Unzip into extracted/ after download")
    args = parser.parse_args()

    from playwright.sync_api import sync_playwright

    XLIFF_DIR.mkdir(parents=True, exist_ok=True)

    with sync_playwright() as pw:
        browser, page, admin_url = login_staging(pw, headful=args.headful)

        # ── Navigate to translations queue ──
        goto_queue(page, admin_url)

        # ── Scroll to bottom where Export XLIFF section lives ──
        page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        page.wait_for_timeout(2000)

        # ── Click Export button and capture download ──
        print("exporting XLIFF...")
        download_path = None

        try:
            with page.expect_download(timeout=60000) as download_info:
                page.evaluate("""() => {
                    const btn = document.getElementById('wpml_xliff_download');
                    if (btn) { btn.click(); return; }
                    // Fallback: find Export button near bottom
                    for (const b of document.querySelectorAll('button, input[type="submit"]')) {
                        const text = (b.textContent || b.value || '').trim();
                        if (text === 'Export' && b.getBoundingClientRect().top > 400) {
                            b.click();
                            return;
                        }
                    }
                }""")
                download = download_info.value
                filename = download.suggested_filename
                download_path = XLIFF_DIR / filename
                download.save_as(str(download_path))
        except Exception as e:
            page.screenshot(path="/tmp/wpml-export-error.png")
            fail(f"download failed: {e}\ncheck /tmp/wpml-export-error.png")

        browser.close()

    if not download_path or not download_path.exists():
        fail("no file downloaded")

    size_kb = download_path.stat().st_size / 1024
    print(f"saved: {download_path} ({size_kb:.1f} KB)")

    # Count files in ZIP
    with zipfile.ZipFile(download_path) as zf:
        names = zf.namelist()
        print(f"  {len(names)} XLIFF files in ZIP")

    # Extract if requested
    if args.extract:
        EXTRACTED_DIR.mkdir(parents=True, exist_ok=True)
        with zipfile.ZipFile(download_path) as zf:
            zf.extractall(EXTRACTED_DIR)
        print(f"  extracted to {EXTRACTED_DIR}")

    print("done!")


if __name__ == "__main__":
    main()
