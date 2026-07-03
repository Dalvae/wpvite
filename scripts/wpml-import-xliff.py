#!/usr/bin/env python3
"""Import translated XLIFF files back into WPML.

Usage:
  python scripts/wpml-import-xliff.py                           # import from translations/xliff-translated/
  python scripts/wpml-import-xliff.py --source path/to/dir      # custom source dir
  python scripts/wpml-import-xliff.py --zip path/to/file.zip    # import a ZIP directly
  python scripts/wpml-import-xliff.py --headful                  # visible browser

The import section is at the bottom of the Translations queue page.
WPML accepts a ZIP of XLIFF files or individual XLIFF uploads.
Env vars (from .env): WP_URL/WP_USER/WP_PASSWORD, or WP_STAGING_* aliases.
"""

from __future__ import annotations

import argparse
import sys
import zipfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from wpml_browser import fail, goto_queue, login_staging

TRANSLATED_DIR = Path(__file__).parent.parent / "translations" / "xliff-translated"


def main() -> None:
    parser = argparse.ArgumentParser(description="WPML: import translated XLIFF")
    parser.add_argument("--source", default="", help="Directory with translated XLIFF files")
    parser.add_argument("--zip", dest="zip_path", default="", help="ZIP file to import directly")
    parser.add_argument("--headful", action="store_true")
    args = parser.parse_args()

    from playwright.sync_api import sync_playwright

    # Determine the file to upload
    if args.zip_path:
        upload_file = Path(args.zip_path).resolve()
        if not upload_file.exists():
            fail(f"ZIP not found: {upload_file}")
    else:
        source_dir = Path(args.source).resolve() if args.source else TRANSLATED_DIR
        if not source_dir.exists():
            fail(f"source dir not found: {source_dir}")

        xliff_files = list(source_dir.glob("*.xliff")) + list(source_dir.glob("*.xlf"))
        if not xliff_files:
            fail(f"no XLIFF files in {source_dir}")

        # Package into a ZIP for upload
        upload_file = source_dir.parent / "translated-import.zip"
        with zipfile.ZipFile(upload_file, "w", zipfile.ZIP_DEFLATED) as zf:
            for f in xliff_files:
                zf.write(f, f.name)
        print(f"packaged {len(xliff_files)} XLIFF files into {upload_file.name}")

    size_kb = upload_file.stat().st_size / 1024
    print(f"uploading: {upload_file.name} ({size_kb:.1f} KB)")

    with sync_playwright() as pw:
        browser, page, admin_url = login_staging(pw, headful=args.headful)

        # ── Navigate to translations queue ──
        goto_queue(page, admin_url)

        # ── Scroll to bottom where Import/Export XLIFF section lives ──
        page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        page.wait_for_timeout(2000)

        # ── Find the file input in the Import section ──
        print("uploading XLIFF...")
        file_input = page.locator("input[type='file']")
        if file_input.count() == 0:
            page.screenshot(path="/tmp/wpml-import-nofile.png")
            fail("no file input found on queue page — check /tmp/wpml-import-nofile.png")

        file_input.first.set_input_files(str(upload_file))
        page.wait_for_timeout(2000)

        # ── Click the Import button ──
        imported = page.evaluate("""() => {
            for (const b of document.querySelectorAll('button, input[type="submit"]')) {
                const text = (b.textContent || b.value || '').trim();
                if (text === 'Import' || text.includes('Import')) {
                    const rect = b.getBoundingClientRect();
                    if (rect.top > 400) {
                        b.click();
                        return text;
                    }
                }
            }
            return null;
        }""")

        if not imported:
            page.screenshot(path="/tmp/wpml-import-nobtn.png")
            fail("no Import button found — check /tmp/wpml-import-nobtn.png")

        print(f"  clicked: {imported}")

        # Wait for upload to process
        page.wait_for_timeout(5000)
        page.wait_for_load_state("domcontentloaded", timeout=120000)
        page.wait_for_timeout(3000)

        # Check result
        body = page.inner_text("body")
        if "import" in body.lower() and ("complete" in body.lower() or "success" in body.lower()):
            print("XLIFF import successful!")
        else:
            page.screenshot(path="/tmp/wpml-import-result.png")
            print(f"import result page: {page.url}")
            print("check /tmp/wpml-import-result.png for status")
            messages = page.evaluate("""() => {
                const msgs = document.querySelectorAll('.notice, .updated, .error, .success, [class*="notice"], [class*="message"]');
                return Array.from(msgs).map(m => m.textContent.trim().substring(0, 200));
            }""")
            for msg in messages:
                if msg.strip():
                    print(f"  {msg}")

        browser.close()

    # Clean up temp ZIP if we created it
    if not args.zip_path and upload_file.name == "translated-import.zip":
        upload_file.unlink(missing_ok=True)

    print("done!")


if __name__ == "__main__":
    main()
