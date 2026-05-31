#!/usr/bin/env bash
set -euo pipefail

# MiMargen Asset Extraction Pipeline
# Extracts compiled CSS and fonts from Astro dist into assets/

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DIST_DIR="$PROJECT_ROOT/dist"
ASSETS_DIR="$PROJECT_ROOT/assets"

echo "=== MiMargen Asset Extraction ==="

# Step 1: Build Astro
echo "Building Astro..."
cd "$PROJECT_ROOT"
pnpm build

# Step 2: Ensure assets directories exist
mkdir -p "$ASSETS_DIR/fonts"

# Step 3: Copy CSS
CSS_SRC=$(ls -1 "$DIST_DIR/_astro"/*.css | head -n 1)
CSS_DEST="$ASSETS_DIR/landing.css"

cp "$CSS_SRC" "$CSS_DEST"
echo "Copied CSS: $CSS_SRC -> $CSS_DEST"

# Step 4: Copy fonts
FONTS_FOUND=0
for font in "$DIST_DIR/_astro"/*.woff2; do
    if [ -f "$font" ]; then
        cp "$font" "$ASSETS_DIR/fonts/"
        FONTS_FOUND=$((FONTS_FOUND + 1))
    fi
done
echo "Copied $FONTS_FOUND font files to assets/fonts/"

# Step 5: Replace font paths in CSS
sed -i.bak 's|url(/_astro/|url(/assets/fonts/|g' "$CSS_DEST"
rm -f "$CSS_DEST.bak"
echo "Replaced font paths in CSS"

# Step 6: Report sizes
echo ""
echo "=== File Sizes ==="
CSS_SIZE=$(wc -c < "$CSS_DEST" | awk '{print $1}')
echo "landing.css: ${CSS_SIZE} bytes ($(numfmt --to=iec-i --suffix=B "$CSS_SIZE" 2>/dev/null || echo "${CSS_SIZE}B"))"

TOTAL_FONT_SIZE=0
for font in "$ASSETS_DIR/fonts"/*.woff2; do
    if [ -f "$font" ]; then
        FSIZE=$(wc -c < "$font" | awk '{print $1}')
        TOTAL_FONT_SIZE=$((TOTAL_FONT_SIZE + FSIZE))
        FNAME=$(basename "$font")
        echo "fonts/$FNAME: ${FSIZE} bytes"
    fi
done
echo "Total font size: ${TOTAL_FONT_SIZE} bytes"

echo ""
echo "=== Extraction Complete ==="
