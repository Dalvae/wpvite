#!/usr/bin/env python3
"""Seed pages via WordPress REST API.

Reads manifests/pages/*.json and creates/updates WordPress pages
with the correct hierarchy, templates, and slugs.

Usage:
  python scripts/seed-pages-rest.py
  python scripts/seed-pages-rest.py --dry-run
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.parse
from pathlib import Path
from typing import Any, cast

SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parent
sys.path.insert(0, str(SCRIPT_DIR))

from wp_rest_common import api, api_try, auth_token, fail, load_project_env, require_env

MANIFESTS_DIR = PROJECT_ROOT / "manifests" / "pages"

TEMPLATE_MAP: dict[str, str] = {
    "section-stack": "template-sections.php",
}


def load_page_defs() -> list[dict[str, Any]]:
    pages: list[dict[str, Any]] = []
    if not MANIFESTS_DIR.is_dir():
        fail(f"Manifests directory not found: {MANIFESTS_DIR}")
    for json_path in sorted(MANIFESTS_DIR.glob("*.json")):
        try:
            data = json.loads(json_path.read_text(encoding="utf-8"))
        except json.JSONDecodeError as exc:
            fail(f"Invalid JSON in {json_path.name}: {exc}")
        if not isinstance(data, dict) or "slug" not in data:
            continue
        pages.append(data)
    return pages


def find_page_by_slug(slug: str, base_url: str, token: str) -> dict[str, Any] | None:
    query = urllib.parse.quote(slug)
    result = api(base_url=base_url, auth_basic_token=token, method="GET",
                 path=f"/wp/v2/pages?slug={query}&context=edit&per_page=100")
    if not isinstance(result, list) or not result:
        return None
    return result[0]


def find_page_by_id(page_id: int, base_url: str, token: str) -> dict[str, Any] | None:
    if page_id <= 0:
        return None
    result = api(base_url=base_url, auth_basic_token=token, method="GET",
                 path=f"/wp/v2/pages/{page_id}?context=edit")
    return result if isinstance(result, dict) else None


def get_settings(base_url: str, token: str) -> dict[str, Any]:
    settings = api(base_url=base_url, auth_basic_token=token, method="GET",
                   path="/wp/v2/settings")
    if not isinstance(settings, dict):
        fail(f"Unexpected settings response: {settings}")
    return cast(dict[str, Any], settings)


def upsert_page(
    page_def: dict[str, Any],
    page_ids: dict[str, int],
    settings: dict[str, Any],
    base_url: str,
    token: str,
    dry_run: bool,
) -> int:
    slug = page_def["slug"]
    title = page_def.get("title", slug.replace("-", " ").title())
    wp_template = TEMPLATE_MAP.get(page_def.get("template", ""), "")
    parent_slug = page_def.get("parent_slug", "")
    parent_id = page_ids.get(parent_slug, 0) if parent_slug else 0

    payload: dict[str, Any] = {"title": title, "slug": slug, "status": "publish", "parent": parent_id}
    if wp_template:
        payload["template"] = wp_template

    existing = None
    if slug == "home":
        existing = find_page_by_id(int(settings.get("page_on_front") or 0), base_url, token)

    if not existing:
        existing = find_page_by_slug(slug, base_url, token)

    if existing:
        page_id = int(existing["id"])
        if dry_run:
            print(f"  exists: {slug} (id {page_id})")
            return page_id
        ok, _ = api_try(base_url=base_url, auth_basic_token=token,
                        method="POST", path=f"/wp/v2/pages/{page_id}", payload=payload)
        print(f"  {'updated' if ok else 'kept'}: {slug} (id {page_id})")
        return page_id

    if dry_run:
        print(f"  would create: {slug}")
        return -1

    created = api(base_url=base_url, auth_basic_token=token,
                  method="POST", path="/wp/v2/pages", payload=payload)
    page_id = int(created["id"]) if isinstance(created, dict) else 0
    print(f"  created: {slug} (id {page_id})")
    return page_id


def main() -> None:
    os.chdir(PROJECT_ROOT)
    load_project_env()

    parser = argparse.ArgumentParser(description="Seed pages via REST API")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    base_url = require_env("WP_BASE_URL")
    token = auth_token(require_env("WP_USER"), require_env("WP_APP_PASSWORD"))

    api(base_url=base_url, auth_basic_token=token, method="GET", path="/wp/v2/users/me?context=edit")
    print(f"authenticated → {base_url}")

    settings = get_settings(base_url, token)
    page_defs = load_page_defs()
    page_defs.sort(key=lambda d: (1 if d.get("parent_slug") else 0, d["slug"]))
    print(f"loaded {len(page_defs)} page manifests")

    page_ids: dict[str, int] = {}
    for pd in page_defs:
        page_ids[pd["slug"]] = upsert_page(pd, page_ids, settings, base_url, token, args.dry_run)

    # Set front page
    home_id = page_ids.get("home", 0)
    if home_id > 0:
        if not args.dry_run:
            api(base_url=base_url, auth_basic_token=token, method="POST",
                path="/wp/v2/settings", payload={"show_on_front": "page", "page_on_front": home_id})
        print(f"  front page = {home_id}")

    print("seed-pages complete")


if __name__ == "__main__":
    main()
