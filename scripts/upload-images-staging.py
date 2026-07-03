#!/usr/bin/env python3
"""Upload images to WordPress and write media mapping JSON files.

Default input is brief/images/. The primary output is manifests/media-map.json
({filename: media_id}) and an optional config/image-mapping.json can be written
for scripts that prefer config-scoped mappings.

Usage:
  python3 scripts/upload-images-staging.py --dry-run
  python3 scripts/upload-images-staging.py --input brief/images --output manifests/media-map.json
  python3 scripts/upload-images-staging.py --write-config-map
"""

from __future__ import annotations

import argparse
import json
import mimetypes
import os
import sys
import urllib.request
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parent
sys.path.insert(0, str(SCRIPT_DIR))

from wp_rest_common import api, auth_token, env, fail, load_project_env, require_env

DEFAULT_INPUT = PROJECT_ROOT / "brief" / "images"
DEFAULT_OUTPUT = PROJECT_ROOT / "manifests" / "media-map.json"
DEFAULT_CONFIG_OUTPUT = PROJECT_ROOT / "config" / "image-mapping.json"
IMAGE_EXTENSIONS = {".png", ".jpg", ".jpeg", ".webp", ".gif"}


def find_media(input_dir: Path) -> list[Path]:
    if not input_dir.exists():
        fail(f"Input directory does not exist: {input_dir}")
    return [path for path in sorted(input_dir.iterdir()) if path.is_file() and path.suffix.lower() in IMAGE_EXTENSIONS]


def load_existing(path: Path) -> dict[str, int]:
    if not path.exists():
        return {}
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except json.JSONDecodeError:
        return {}
    return {str(k): int(v) for k, v in data.items() if isinstance(v, int) or str(v).isdigit()}


def upload_file(path: Path, *, base_url: str, token: str, dry_run: bool) -> int:
    content_type = mimetypes.guess_type(path.name)[0] or "application/octet-stream"
    data = path.read_bytes()
    if dry_run:
        print(f"  DRY upload {path.name} ({len(data)} bytes)")
        return -1

    boundary = "----WPViteMediaUploadBoundary"
    body = bytearray()
    body.extend(f"--{boundary}\r\n".encode())
    body.extend(f'Content-Disposition: form-data; name="file"; filename="{path.name}"\r\n'.encode())
    body.extend(f"Content-Type: {content_type}\r\n\r\n".encode())
    body.extend(data)
    body.extend(b"\r\n")
    body.extend(f"--{boundary}--\r\n".encode())

    request = urllib.request.Request(f"{base_url.rstrip('/')}/wp-json/wp/v2/media", data=bytes(body), method="POST")
    request.add_header("Authorization", f"Basic {token}")
    request.add_header("Content-Type", f"multipart/form-data; boundary={boundary}")
    request.add_header("Accept", "application/json")

    try:
        with urllib.request.urlopen(request, timeout=120) as response:
            result = json.loads(response.read().decode("utf-8"))
            media_id = int(result.get("id", 0))
            print(f"  OK {path.name} -> {media_id}")
            return media_id
    except Exception as exc:
        print(f"  FAIL {path.name}: {exc}")
        return 0


def write_json(path: Path, data: dict[str, int], dry_run: bool) -> None:
    if dry_run:
        print(f"DRY would write {path}")
        return
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"wrote {path}")


def main() -> None:
    os.chdir(PROJECT_ROOT)
    load_project_env()

    parser = argparse.ArgumentParser(description="Upload images and write media mappings")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--input", type=Path, default=DEFAULT_INPUT)
    parser.add_argument("--output", type=Path, default=DEFAULT_OUTPUT)
    parser.add_argument("--config-output", type=Path, default=DEFAULT_CONFIG_OUTPUT)
    parser.add_argument("--write-config-map", action="store_true")
    args = parser.parse_args()

    base_url = (env("WP_BASE_URL") or env("WP_STAGING_URL")).rstrip("/")
    if base_url == "":
        fail("Missing required env var: WP_BASE_URL or WP_STAGING_URL")
    user = env("WP_USER") or require_env("WP_STAGING_USER")
    password = env("WP_APP_PASSWORD") or require_env("WP_STAGING_APP_PASSWORD")
    token = auth_token(user, password)

    api(base_url=base_url, auth_basic_token=token, method="GET", path="/wp/v2/users/me?context=edit")
    print(f"authenticated -> {base_url}")

    input_dir = args.input if args.input.is_absolute() else PROJECT_ROOT / args.input
    output_path = args.output if args.output.is_absolute() else PROJECT_ROOT / args.output
    config_output = args.config_output if args.config_output.is_absolute() else PROJECT_ROOT / args.config_output
    media_files = find_media(input_dir)
    if not media_files:
        fail(f"No images found in {input_dir}")

    media_map = load_existing(output_path)
    print(f"found {len(media_files)} files")
    for media_path in media_files:
        if media_path.name in media_map and media_map[media_path.name] > 0:
            print(f"  SKIP {media_path.name} (id {media_map[media_path.name]})")
            continue
        media_id = upload_file(media_path, base_url=base_url, token=token, dry_run=args.dry_run)
        if media_id > 0:
            media_map[media_path.name] = media_id

    write_json(output_path, media_map, args.dry_run)
    if args.write_config_map:
        write_json(config_output, media_map, args.dry_run)


if __name__ == "__main__":
    main()
