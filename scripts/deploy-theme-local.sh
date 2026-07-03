#!/usr/bin/env bash
#
# deploy-theme-local.sh — Deploy a built theme ZIP on the same VPS.
#
# This script is intentionally local-only. It does not expose HTTP deploy
# endpoints, does not accept arbitrary commands, and does not need WordPress
# admin passwords. Use it from the VPS shell, a Cloudflare-protected admin
# workflow, or a fixed local job.
#
# Usage:
#   THEME_SLUG=starter WP_PATH=/var/www/site/current ./scripts/deploy-theme-local.sh
#   THEME_SLUG=starter WP_THEME_DIR=/var/www/html/wp-content/themes ./scripts/deploy-theme-local.sh --zip ./starter-theme.zip --activate
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

THEME_SLUG="${THEME_SLUG:-starter}"
ZIP_PATH=""
RUN_BUILD="1"
ACTIVATE="0"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --slug) THEME_SLUG="${2:-}"; shift 2 ;;
    --zip) ZIP_PATH="${2:-}"; RUN_BUILD="0"; shift 2 ;;
    --no-build) RUN_BUILD="0"; shift ;;
    --activate) ACTIVATE="1"; shift ;;
    *) echo "Unknown argument: $1" >&2; exit 1 ;;
  esac
done

[[ -n "$THEME_SLUG" ]] || { echo "ERROR: THEME_SLUG/--slug is required" >&2; exit 1; }

cd "$ROOT_DIR"

if [[ "$RUN_BUILD" == "1" ]]; then
  ./scripts/build-zip.sh --slug "$THEME_SLUG"
  ZIP_PATH="$(ls -t "${THEME_SLUG}"-theme-*.zip | head -n 1)"
fi

[[ -n "$ZIP_PATH" ]] || { echo "ERROR: --zip is required when --no-build is used" >&2; exit 1; }
[[ -f "$ZIP_PATH" ]] || { echo "ERROR: zip not found: $ZIP_PATH" >&2; exit 1; }

TMP_DIR="$(mktemp -d)"
cleanup() { rm -rf "$TMP_DIR"; }
trap cleanup EXIT

unzip -oq "$ZIP_PATH" -d "$TMP_DIR"
[[ -d "$TMP_DIR/$THEME_SLUG" ]] || { echo "ERROR: ZIP must contain top-level theme folder: $THEME_SLUG" >&2; exit 1; }

if [[ -n "${WP_THEME_DIR:-}" ]]; then
  TARGET_DIR="${WP_THEME_DIR%/}/$THEME_SLUG"
elif [[ -n "${WP_PATH:-}" ]]; then
  TARGET_DIR="${WP_PATH%/}/wp-content/themes/$THEME_SLUG"
else
  echo "ERROR: set WP_PATH or WP_THEME_DIR" >&2
  exit 1
fi

BACKUP_DIR="${DEPLOY_BACKUP_DIR:-$ROOT_DIR/.deploy-backups}"
mkdir -p "$BACKUP_DIR" "$(dirname "$TARGET_DIR")"

if [[ -d "$TARGET_DIR" ]]; then
  BACKUP_PATH="$BACKUP_DIR/${THEME_SLUG}-$(date +%Y-%m-%d-%H%M%S)"
  mv "$TARGET_DIR" "$BACKUP_PATH"
  echo "backup: $BACKUP_PATH"
fi

cp -R "$TMP_DIR/$THEME_SLUG" "$TARGET_DIR"
echo "deployed: $TARGET_DIR"

if [[ "$ACTIVATE" == "1" ]]; then
  if [[ -n "${WP_PATH:-}" ]] && command -v wp >/dev/null 2>&1; then
    wp --path="$WP_PATH" theme activate "$THEME_SLUG"
    wp --path="$WP_PATH" cache flush || true
  elif command -v docker >/dev/null 2>&1 && [[ -f "$ROOT_DIR/docker-compose.yml" ]]; then
    docker compose --profile cli run --rm wpcli wp theme activate "$THEME_SLUG"
    docker compose --profile cli run --rm wpcli wp cache flush || true
  else
    echo "WARN: could not activate automatically; wp CLI/docker compose unavailable" >&2
  fi
fi

echo "OK: local deploy complete"
