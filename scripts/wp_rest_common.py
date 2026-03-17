#!/usr/bin/env python3
"""Shared helpers for WordPress REST seeding scripts."""

from __future__ import annotations

import base64
import json
import mimetypes
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def load_env_file(path: Path, *, override: bool = False, protected_keys: set[str] | None = None) -> None:
    if not path.exists():
        return
    if protected_keys is None:
        protected_keys = set()
    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if "=" not in line:
            continue
        key, value = line.split("=", 1)
        key = key.strip()
        if key == "" or key in protected_keys:
            continue
        if not override and key in os.environ and os.environ[key].strip() != "":
            continue
        value = value.strip()
        if len(value) >= 2 and ((value[0] == '"' and value[-1] == '"') or (value[0] == "'" and value[-1] == "'")):
            value = value[1:-1]
        os.environ[key] = value


def load_project_env() -> None:
    protected_keys = {key for key, value in os.environ.items() if value.strip() != ""}
    for index, env_file in enumerate((".env", ".env.local", ".env.live")):
        load_env_file(Path(env_file), override=index > 0, protected_keys=protected_keys)


def env(name: str, default: str = "") -> str:
    return os.getenv(name, default).strip()


def require_env(name: str) -> str:
    value = env(name)
    if value == "":
        fail(f"Missing required env var: {name}")
    return value


def auth_token(user: str, app_password: str) -> str:
    return base64.b64encode(f"{user}:{app_password}".encode("utf-8")).decode("ascii")


def api(
    *,
    base_url: str,
    auth_basic_token: str,
    method: str,
    path: str,
    payload: Any | None = None,
    timeout_seconds: int = 45,
) -> dict[str, Any] | list[Any]:
    url = f"{base_url.rstrip('/')}/wp-json{path}"
    data = None
    headers = {
        "Authorization": f"Basic {auth_basic_token}",
        "Accept": "application/json",
    }
    if payload is not None:
        if isinstance(payload, (bytes, bytearray)):
            data = bytes(payload)
        else:
            data = json.dumps(payload).encode("utf-8")
            headers["Content-Type"] = "application/json"

    request = urllib.request.Request(url=url, method=method, data=data, headers=headers)
    try:
        with urllib.request.urlopen(request, timeout=timeout_seconds) as response:
            body = response.read().decode("utf-8")
            if body == "":
                return {}
            decoded = json.loads(body)
            if isinstance(decoded, (dict, list)):
                return decoded
            fail(f"Unexpected non-JSON response at {method} {path}")
    except urllib.error.HTTPError as exc:
        error_body = exc.read().decode("utf-8", errors="replace")
        fail(f"{method} {path} failed ({exc.code}): {error_body}")
    except urllib.error.URLError as exc:
        fail(f"{method} {path} connection error: {exc}")
    return {}


def api_try(
    *,
    base_url: str,
    auth_basic_token: str,
    method: str,
    path: str,
    payload: Any | None = None,
    timeout_seconds: int = 45,
) -> tuple[bool, dict[str, Any] | list[Any] | str]:
    url = f"{base_url.rstrip('/')}/wp-json{path}"
    data = None
    headers = {
        "Authorization": f"Basic {auth_basic_token}",
        "Accept": "application/json",
    }
    if payload is not None:
        if isinstance(payload, (bytes, bytearray)):
            data = bytes(payload)
        else:
            data = json.dumps(payload).encode("utf-8")
            headers["Content-Type"] = "application/json"

    request = urllib.request.Request(url=url, method=method, data=data, headers=headers)
    try:
        with urllib.request.urlopen(request, timeout=timeout_seconds) as response:
            body = response.read().decode("utf-8")
            decoded = json.loads(body) if body else {}
            if isinstance(decoded, (dict, list)):
                return True, decoded
            return False, f"Unexpected non-JSON response at {method} {path}"
    except urllib.error.HTTPError as exc:
        return False, exc.read().decode("utf-8", errors="replace")
    except urllib.error.URLError as exc:
        return False, str(exc)


def slugify(value: str) -> str:
    lowered = value.strip().lower()
    lowered = re.sub(r"[^a-z0-9]+", "-", lowered)
    lowered = re.sub(r"-{2,}", "-", lowered)
    return lowered.strip("-")
