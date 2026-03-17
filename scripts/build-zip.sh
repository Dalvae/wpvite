#!/usr/bin/env bash
#
# build-zip.sh — Build a deploy-ready theme zip.
#
# Usage:
#   ./scripts/build-zip.sh
#   ./scripts/build-zip.sh --slug mytheme
#   ./scripts/build-zip.sh --no-build
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

THEME_SLUG="starter"
OUTPUT_DIR="$ROOT_DIR"
RUN_BUILD="1"
CLEAN_PREVIOUS="1"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --slug)    THEME_SLUG="${2:-}"; shift 2 ;;
    --output)  OUTPUT_DIR="${2:-}"; shift 2 ;;
    --no-build) RUN_BUILD="0"; shift ;;
    --keep-old) CLEAN_PREVIOUS="0"; shift ;;
    *) echo "Unknown argument: $1" >&2; exit 1 ;;
  esac
done

mkdir -p "$OUTPUT_DIR"

DATE_TAG="$(date +%Y-%m-%d-%H%M)"
ZIP_NAME="${THEME_SLUG}-theme-${DATE_TAG}.zip"
ZIP_PATH="${OUTPUT_DIR%/}/${ZIP_NAME}"

TMP_DIR="$(mktemp -d)"
ASSEMBLY_DIR="$TMP_DIR/$THEME_SLUG"

cleanup() { rm -rf "$TMP_DIR"; }
trap cleanup EXIT

cd "$ROOT_DIR"

if [[ "$RUN_BUILD" == "1" ]]; then
  echo "==> Building production assets..."
  pnpm build
fi

[[ -d dist ]] || { echo "ERROR: dist/ not found." >&2; exit 1; }
[[ -d vendor ]] || { echo "ERROR: vendor/ not found." >&2; exit 1; }

[[ "$CLEAN_PREVIOUS" == "1" ]] && rm -f "${OUTPUT_DIR%/}/${THEME_SLUG}"-theme-*.zip

echo "==> Assembling theme files..."
mkdir -p "$ASSEMBLY_DIR"

cp -f ./*.php "$ASSEMBLY_DIR/"
cp -f style.css "$ASSEMBLY_DIR/"
[[ -f screenshot.png ]] && cp -f screenshot.png "$ASSEMBLY_DIR/"
[[ -f composer.json ]] && cp -f composer.json "$ASSEMBLY_DIR/"
[[ -f composer.lock ]] && cp -f composer.lock "$ASSEMBLY_DIR/"
[[ -f wpml-config.xml ]] && cp -f wpml-config.xml "$ASSEMBLY_DIR/"

for dir in components inc template-parts builder dist vendor images favicon languages assets config manifests; do
  [[ -d "$dir" ]] && cp -R "$dir" "$ASSEMBLY_DIR/"
done

rm -rf "$ASSEMBLY_DIR/assets/media" 2>/dev/null || true
rm -rf "$ASSEMBLY_DIR/reference" 2>/dev/null || true
rm -rf "$ASSEMBLY_DIR"/__pycache__ "$ASSEMBLY_DIR"/tmp
find "$ASSEMBLY_DIR" -type f -name "*.pyc" -delete
find "$ASSEMBLY_DIR" -type f -name "*.zip" -delete
find "$ASSEMBLY_DIR/dist" -type f -name "*.map" -delete || true
find "$ASSEMBLY_DIR/vendor" -type f -name "yarn.lock" -delete || true

# Carbon Fields cleanup
CF_DIR="$ASSEMBLY_DIR/vendor/htmlburger/carbon-fields"
if [[ -d "$CF_DIR" ]]; then
  rm -rf "$CF_DIR/bin" "$CF_DIR/packages" "$CF_DIR/.phpstorm.meta.php" || true
  find "$CF_DIR" -maxdepth 1 -type f \
    \( -name "*.md" -o -name "webpack.config.js" -o -name "package.json" -o -name "yarn.lock" \) \
    -delete || true
  find "$CF_DIR/build" -type f \
    \( -name "vendor.js" -o -name "core.js" -o -name "metaboxes.js" -o -name "blocks.js" \
       -o -name "core.css" -o -name "metaboxes.css" -o -name "blocks.css" \) \
    -delete || true
fi

echo "==> Creating zip: $ZIP_PATH"
(
  cd "$TMP_DIR"
  zip -rq "$ZIP_PATH" "$THEME_SLUG" \
    -x "*/__pycache__/*" -x "*.pyc" -x "*/tmp/*" -x "*/.git/*" \
    -x "*/.github/*" -x "*/node_modules/*" -x "*/docs/*" \
    -x "*/scripts/*" -x "*/src/*" -x "*/reference/*" -x "*.zip"
)

SIZE="$(du -h "$ZIP_PATH" | cut -f1)"
echo ""
echo "OK: $(basename "$ZIP_PATH") ($SIZE)"
echo "Path: $ZIP_PATH"
