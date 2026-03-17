#!/usr/bin/env python3
"""Seed page content (Carbon Fields) via WordPress REST API.

Reads manifests/pages/*.json and writes section data as per-field post_meta
via /starter/v1/page-meta (uses carbon_set_post_meta).

Pages with a field map (config/section-field-maps.json) get per-field storage.
All pages get JSON blob in starter_page_sections as fallback.

Usage:
  python scripts/seed-content-rest.py
  python scripts/seed-content-rest.py --dry-run
  python scripts/seed-content-rest.py --page about
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.parse
from pathlib import Path
from typing import Any

SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parent
sys.path.insert(0, str(SCRIPT_DIR))

from wp_rest_common import api, api_try, auth_token, fail, load_project_env, require_env

MANIFESTS_DIR = PROJECT_ROOT / "manifests" / "pages"
FIELD_MAPS_PATH = PROJECT_ROOT / "config" / "section-field-maps.json"


def load_field_maps() -> dict[str, list[dict[str, Any]]]:
    if not FIELD_MAPS_PATH.exists():
        return {}
    data = json.loads(FIELD_MAPS_PATH.read_text(encoding="utf-8"))
    pages = data.get("pages", {})
    normalized: dict[str, list[dict[str, Any]]] = {}
    for slug, sections in pages.items():
        norm_sections = []
        for section in sections:
            norm_fields: dict[str, str] = {}
            for fname, fdef in section.get("fields", {}).items():
                norm_fields[fname] = fdef.get("storage", "text") if isinstance(fdef, dict) else str(fdef)
            norm_sections.append({
                "type": section.get("type", ""),
                "prefix": section.get("prefix", ""),
                "fields": norm_fields,
            })
        normalized[slug] = norm_sections
    return normalized


PAGE_FIELD_MAPS = load_field_maps()


def load_manifests(only_page: str | None = None) -> list[dict[str, Any]]:
    manifests: list[dict[str, Any]] = []
    for json_path in sorted(MANIFESTS_DIR.glob("*.json")):
        try:
            data = json.loads(json_path.read_text(encoding="utf-8"))
        except json.JSONDecodeError as exc:
            fail(f"Invalid JSON in {json_path.name}: {exc}")
        if not isinstance(data, dict) or "slug" not in data:
            continue
        if only_page and data["slug"] != only_page:
            continue
        manifests.append(data)
    return manifests


def find_page_by_slug(slug: str, base_url: str, token: str) -> dict[str, Any] | None:
    query = urllib.parse.quote(slug)
    result = api(base_url=base_url, auth_basic_token=token, method="GET",
                 path=f"/wp/v2/pages?slug={query}&context=edit&per_page=100")
    if not isinstance(result, list) or not result:
        return None
    return result[0]


def find_front_page(base_url: str, token: str) -> dict[str, Any] | None:
    settings = api(base_url=base_url, auth_basic_token=token, method="GET", path="/wp/v2/settings")
    if not isinstance(settings, dict):
        return None
    front_id = int(settings.get("page_on_front") or 0)
    if front_id <= 0:
        return None
    result = api(base_url=base_url, auth_basic_token=token, method="GET",
                 path=f"/wp/v2/pages/{front_id}?context=edit")
    return result if isinstance(result, dict) else None


def resolve_page_id(slug: str, base_url: str, token: str) -> int:
    if slug == "home":
        page = find_front_page(base_url, token)
        if page:
            return int(page["id"])
    page = find_page_by_slug(slug, base_url, token)
    return int(page["id"]) if page else 0


def strip_internal_keys(obj: Any) -> Any:
    if isinstance(obj, dict):
        return {k: strip_internal_keys(v) for k, v in obj.items() if not k.startswith("_")}
    if isinstance(obj, list):
        return [strip_internal_keys(item) for item in obj]
    return obj


def flatten_sections_perfield(slug: str, sections: list[dict[str, Any]]) -> dict[str, str]:
    field_map = PAGE_FIELD_MAPS.get(slug)
    if not field_map:
        return {}
    meta: dict[str, str] = {}
    for i, sdef in enumerate(field_map):
        if i >= len(sections):
            break
        section = strip_internal_keys(sections[i])
        for fname, ftype in sdef["fields"].items():
            val = section.get(fname)
            if val is None:
                continue
            mk = f"{sdef['prefix']}_{fname}"
            if ftype == "json" and isinstance(val, (list, dict)):
                meta[mk] = json.dumps(val, ensure_ascii=False)
            else:
                meta[mk] = str(val)
    return meta


def flatten_sections_to_meta(manifest: dict[str, Any]) -> dict[str, Any]:
    meta: dict[str, Any] = {}
    slug = manifest.get("slug", "")

    template = manifest.get("template", "")
    if template:
        meta["starter_page_family"] = template

    sections = manifest.get("sections", [])

    if slug in PAGE_FIELD_MAPS and sections:
        meta.update(flatten_sections_perfield(slug, sections))

    if sections:
        meta["starter_page_sections"] = json.dumps(strip_internal_keys(sections), ensure_ascii=False)

    return meta


def seed_page_content(manifest: dict[str, Any], base_url: str, token: str, dry_run: bool) -> None:
    slug = manifest["slug"]
    page_id = resolve_page_id(slug, base_url, token)
    if page_id <= 0:
        print(f"  SKIP {slug} — page not found")
        return

    meta = flatten_sections_to_meta(manifest)
    if not meta:
        print(f"  SKIP {slug} — no content")
        return

    has_perfield = slug in PAGE_FIELD_MAPS
    mode = "per-field" if has_perfield else "blob"

    if dry_run:
        print(f"  DRY {slug} (id {page_id}) — {len(meta)} fields [{mode}]")
        for k, v in sorted(meta.items()):
            print(f"    {k} = {str(v)[:80]}")
        return

    # All fields via Carbon Fields endpoint
    ok, result = api_try(
        base_url=base_url, auth_basic_token=token, method="POST",
        path="/starter/v1/page-meta",
        payload={"updates": [{"path": slug, "fields": meta}]},
    )
    if ok:
        n = len(result.get("updated", [])) if isinstance(result, dict) else 0
        print(f"  OK {slug} — {n} fields [{mode}]")
    else:
        # Fallback to WP REST
        print(f"  WARN {slug} — CF endpoint unavailable, using WP REST fallback")
        ok2, _ = api_try(base_url=base_url, auth_basic_token=token, method="POST",
                         path=f"/wp/v2/pages/{page_id}", payload={"meta": meta})
        print(f"  {'OK' if ok2 else 'FAIL'} {slug} (fallback) — {len(meta)} fields")


def main() -> None:
    os.chdir(PROJECT_ROOT)
    load_project_env()

    parser = argparse.ArgumentParser(description="Seed page content via REST API")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--page", type=str, default=None)
    args = parser.parse_args()

    base_url = require_env("WP_BASE_URL")
    token = auth_token(require_env("WP_USER"), require_env("WP_APP_PASSWORD"))

    api(base_url=base_url, auth_basic_token=token, method="GET", path="/wp/v2/users/me?context=edit")
    print(f"authenticated → {base_url}")

    manifests = load_manifests(only_page=args.page)
    print(f"loaded {len(manifests)} manifests")

    for manifest in manifests:
        seed_page_content(manifest, base_url, token, args.dry_run)

    print("seed-content complete")


if __name__ == "__main__":
    main()
