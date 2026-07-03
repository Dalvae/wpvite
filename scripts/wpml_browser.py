"""Shared Playwright login helpers for WPML scripts."""

from __future__ import annotations

import re
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from wp_rest_common import env, fail as common_fail, load_project_env


def fail(msg: str) -> None:
    print(f"ERROR: {msg}", file=sys.stderr)
    sys.exit(1)


def first_env(*names: str) -> str:
    for name in names:
        value = env(name)
        if value:
            return value
    common_fail(f"Missing required env var. Set one of: {', '.join(names)}")
    return ""


def login_staging(pw, *, headful: bool = False):
    """Launch browser and login to WordPress admin.

    Returns (browser, page, admin_url).
    """
    load_project_env()
    base_url = first_env("WP_URL", "WP_STAGING_URL").rstrip("/")
    admin_url = env("WP_ADMIN_URL", env("WP_STAGING_ADMIN_URL", f"{base_url}/wp-admin")).rstrip("/")
    wp_user = first_env("WP_USER", "WP_STAGING_USER")
    wp_pass = first_env("WP_PASSWORD", "WP_STAGING_PASSWORD")

    browser = pw.chromium.launch(headless=not headful)
    context = browser.new_context(ignore_https_errors=True, viewport={"width": 1400, "height": 900})
    page = context.new_page()
    page.set_default_timeout(60000)

    print("logging in...")
    _goto(page, f"{base_url}/wp-login.php")
    page.fill("#user_login", wp_user)
    page.fill("#user_pass", wp_pass)
    page.click("#wp-submit")
    page.wait_for_load_state("domcontentloaded", timeout=45000)
    page.wait_for_timeout(3000)

    if "wp-login" in page.url:
        browser.close()
        fail("login failed — check credentials")

    # Some managed hosts show an onboarding wizard that blocks wp-admin.
    if "siteground-wizard" in page.url:
        html = page.content()
        m = re.search(r'"wp_nonce":"([a-zA-Z0-9]+)"', html)
        if m:
            page.evaluate(
                """async ({base, nonce}) => {
                    await fetch(base + '/wp-json/siteground-central/v1/exit-wizard', {
                        method: 'GET', credentials: 'include',
                        headers: {'X-WP-Nonce': nonce}
                    });
                }""",
                {"base": base_url, "nonce": m.group(1)},
            )

    print("authenticated")
    return browser, page, admin_url


def _goto(page, url: str, attempts: int = 3) -> None:
    for i in range(attempts):
        try:
            page.goto(url, wait_until="domcontentloaded", timeout=30000)
            return
        except Exception:
            if i < attempts - 1:
                time.sleep(2)
            else:
                raise


def goto_queue(page, admin_url: str) -> None:
    """Navigate to the WPML Translations queue page."""
    urls = [
        f"{admin_url}/admin.php?page=tm/menu/translations-queue.php",
        f"{admin_url}/admin.php?page=wpml-translation-management/menu/translations-queue.php",
    ]
    for url in urls:
        _goto(page, url)
        page.wait_for_timeout(3000)
        body = page.inner_text("body")
        if "not allowed" not in body.lower():
            return
    fail("cannot access translation queue — check user permissions")


def set_queue_pagination(page, per_page: int = 100) -> None:
    """Set the translations queue to show max items per page."""
    page.evaluate("""(perPage) => {
        for (const s of document.querySelectorAll('select')) {
            const opts = Array.from(s.options);
            const match = opts.find(o => o.value === String(perPage) || o.text.includes(String(perPage)));
            if (match) {
                s.value = match.value;
                s.dispatchEvent(new Event('change', {bubbles: true}));
                return;
            }
        }
        const sizeChanger = document.querySelector('.ant-pagination-options-size-changer');
        if (sizeChanger) sizeChanger.click();
    }""", per_page)
    page.wait_for_timeout(3000)
