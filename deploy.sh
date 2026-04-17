#!/bin/bash
#
# WPRobo DocuMerge Lite — Production Build Script
#
# Creates a clean production ZIP ready for WordPress.org submission.
# Reads version from the main plugin file header automatically.
#
# Usage:
#   bash deploy.sh
#
# Output:
#   production/wpr-documerge-{version}.zip
#
# What's INCLUDED:
#   - All PHP source files (src/, templates/, blocks/)
#   - Compiled CSS + JS (assets/css/, assets/js/)
#   - Vendor JS libraries (assets/vendor/)
#   - Composer PHP dependencies (vendor/ — PHPWord, mPDF)
#   - Images (assets/images/)
#   - Screenshots (screenshot-*.png)
#   - Languages (.pot file)
#   - readme.txt, uninstall.php
#   - Main plugin file (wprobo-documerge.php)
#
# What's EXCLUDED:
#   - SCSS source files (assets/src/)
#   - Node modules (node_modules/)
#   - Git files (.git/, .gitignore)
#   - GitHub workflows (.github/)
#   - Documentation (docs/)
#   - Dev config (package.json, package-lock.json, composer.json,
#     composer.lock, .editorconfig)
#   - Deploy script itself (deploy.sh)
#   - Dist ignore (.distignore)
#   - Markdown files (*.md)
#   - OS files (.DS_Store, Thumbs.db)
#   - Log files (*.log)
#   - Production directory (production/)
#

set -e

# ── Resolve paths ──────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$SCRIPT_DIR"

# ── Extract version from main plugin file ──────────────────────
VERSION=$(grep -m 1 "Version:" "$PLUGIN_DIR/wprobo-documerge.php" | sed 's/.*Version:[[:space:]]*//' | sed 's/[[:space:]]*$//')

if [ -z "$VERSION" ]; then
    echo "ERROR: Could not extract version from wprobo-documerge.php"
    exit 1
fi

echo "=========================================="
echo "  WPRobo DocuMerge Lite — Build v${VERSION}"
echo "=========================================="
echo ""

# ── Step 1: Install Composer dependencies ─────────────────────
echo "[1/8] Installing Composer dependencies..."

if command -v composer &> /dev/null; then
    cd "$PLUGIN_DIR"
    if [ -f "composer.json" ]; then
        composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1
        echo "   Composer: installed (production only, no dev)"
    else
        echo "   WARNING: No composer.json found. Skipping."
    fi
else
    echo "   WARNING: Composer not found. Checking for existing vendor/..."
    if [ ! -d "$PLUGIN_DIR/vendor" ]; then
        echo "   ERROR: No vendor/ directory and Composer not available."
        echo "   Install Composer: https://getcomposer.org/download/"
        echo "   Or run: composer install --no-dev"
        exit 1
    fi
    echo "   Using existing vendor/ directory."
fi

# ── Strip PclZip from PHPWord (WordPress core already bundles PclZip) ──
PHPWORD_PCLZIP_DIR="$PLUGIN_DIR/vendor/phpoffice/phpword/src/PhpWord/Shared/PCLZip"
PHPWORD_ZIPARCH="$PLUGIN_DIR/vendor/phpoffice/phpword/src/PhpWord/Shared/ZipArchive.php"
if [ -d "$PHPWORD_PCLZIP_DIR" ]; then
    rm -rf "$PHPWORD_PCLZIP_DIR"
    echo "   Stripped: vendor/phpoffice/phpword/.../PCLZip/"
fi
if [ -f "$PHPWORD_ZIPARCH" ] && grep -q "require_once 'PCLZip/pclzip.lib.php'" "$PHPWORD_ZIPARCH"; then
    # Force native ZipArchive backend so PHPWord never tries to require the
    # deleted PclZip file. Replaces the constructor body in-place.
    PHPWORD_ZIPARCH="$PHPWORD_ZIPARCH" python3 - <<'PYPATCH'
import os, re, sys
p = os.environ['PHPWORD_ZIPARCH']
with open(p) as f:
    src = f.read()
new_ctor = (
    "public function __construct()\n"
    "    {\n"
    "        // PclZip removed: WordPress core already bundles PclZip and the\n"
    "        // Plugin Directory disallows duplicates. PHP's native ZipArchive\n"
    "        // is required.\n"
    "        $this->usePclzip = false;\n"
    "        if (Settings::getZipClass() != 'ZipArchive' && !class_exists('ZipArchive', false)) {\n"
    "            throw new \\RuntimeException('PHPWord requires the PHP ZipArchive extension to be enabled.');\n"
    "        }\n"
    "    }"
)
src = re.sub(
    r"public function __construct\(\)\s*\{\s*\$this->usePclzip = \(Settings::getZipClass\(\) != 'ZipArchive'\);.*?require_once 'PCLZip/pclzip\.lib\.php';\s*\}\s*\}",
    new_ctor,
    src,
    count=1,
    flags=re.DOTALL,
)
with open(p, 'w') as f:
    f.write(src)
PYPATCH
    echo "   Patched: PHPWord ZipArchive.php (forced native ZipArchive backend)"
fi

# ── Strip dev-only files from vendor/ (WordPress.org review compliance) ──
# Round 2 reviewer flagged .github_changelog_generator and COPYING.LESSER in PHPWord.
# Per "search for any other similar occurrences" guidance, sweep broadly.
# Runs on SOURCE vendor/ so the BUILD_DIR copy starts clean and future composer
# installs do not re-introduce these files without this script running.
if [ -d "$PLUGIN_DIR/vendor" ]; then
    # Directories that never belong in a distributed plugin.
    find "$PLUGIN_DIR/vendor" -type d -name ".github"  -exec rm -rf {} + 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -type d -name "tests"    -exec rm -rf {} + 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -type d -name "test"     -exec rm -rf {} + 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -type d -name "docs"     -exec rm -rf {} + 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -type d -name "doc"      -exec rm -rf {} + 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -type d -name "examples" -exec rm -rf {} + 2>/dev/null || true

    # Dev/CI metadata files.
    find "$PLUGIN_DIR/vendor" -name ".github_changelog_generator" -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "COPYING.LESSER"              -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "CHANGELOG*"                  -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "CONTRIBUTING*"               -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "README*"                     -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.md"                        -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.yml"                       -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.yaml"                      -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.neon"                      -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.neon.dist"                 -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.xml"                       -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.xml.dist"                  -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "*.dist"                      -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "phpunit.xml*"                -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "Makefile"                    -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name ".php-cs-fixer*"              -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name ".travis.yml"                 -delete 2>/dev/null || true
    find "$PLUGIN_DIR/vendor" -name "psalm.xml"                   -delete 2>/dev/null || true
    echo "   Stripped: vendor/ dev files (.github, CHANGELOG, README, phpstan, etc.)"
fi

# ── Step 2: Build CSS + JS assets ─────────────────────────────
echo "[2/8] Building assets..."

if command -v npx &> /dev/null; then
    cd "$PLUGIN_DIR"

    # Compile SCSS.
    npx sass assets/src/css/admin/main.scss:assets/css/admin/main.min.css --style=compressed --no-source-map 2>/dev/null
    npx sass assets/src/css/frontend/form.scss:assets/css/frontend/form.min.css --style=compressed --no-source-map 2>/dev/null
    echo "   CSS: compiled (admin + frontend)"

    # Minify JS.
    for jsfile in assets/src/js/admin/*.js assets/src/js/frontend/*.js; do
        if [ -f "$jsfile" ]; then
            basename=$(basename "$jsfile" .js)
            subdir=$(echo "$jsfile" | grep -o 'admin\|frontend')
            npx terser "$jsfile" -o "assets/js/$subdir/$basename.min.js" -c -m 2>/dev/null
        fi
    done
    echo "   JS: minified (all source files)"
else
    echo "   WARNING: npx not found. Using existing compiled assets."
fi

# ── Step 3: Create production directory ────────────────────────
echo "[3/8] Creating production directory..."

PROD_DIR="$PLUGIN_DIR/production"
BUILD_DIR="$PROD_DIR/wprobo-documerge-lite"
ZIP_NAME="wpr-documerge-${VERSION}.zip"

# Clean previous build.
if [ -d "$PROD_DIR" ]; then
    rm -rf "$PROD_DIR"
fi

mkdir -p "$BUILD_DIR"

# ── Step 4: Copy production files ──────────────────────────────
echo "[4/8] Copying production files..."

# Main plugin files.
cp "$PLUGIN_DIR/wprobo-documerge.php" "$BUILD_DIR/"
cp "$PLUGIN_DIR/uninstall.php" "$BUILD_DIR/"
cp "$PLUGIN_DIR/readme.txt" "$BUILD_DIR/"
cp "$PLUGIN_DIR/composer.json" "$BUILD_DIR/"

# PHP source.
cp -R "$PLUGIN_DIR/src" "$BUILD_DIR/"

# Templates.
cp -R "$PLUGIN_DIR/templates" "$BUILD_DIR/"

# Blocks.
if [ -d "$PLUGIN_DIR/blocks" ]; then
    cp -R "$PLUGIN_DIR/blocks" "$BUILD_DIR/"
fi

# Compiled assets (CSS + JS only, not SCSS source).
mkdir -p "$BUILD_DIR/assets/css"
mkdir -p "$BUILD_DIR/assets/js"
cp -R "$PLUGIN_DIR/assets/css/" "$BUILD_DIR/assets/css/"
cp -R "$PLUGIN_DIR/assets/js/" "$BUILD_DIR/assets/js/"

# Vendor JS libraries (Select2, Flatpickr, intl-tel-input).
if [ -d "$PLUGIN_DIR/assets/vendor" ]; then
    cp -R "$PLUGIN_DIR/assets/vendor" "$BUILD_DIR/assets/"
fi

# Images.
if [ -d "$PLUGIN_DIR/assets/images" ]; then
    cp -R "$PLUGIN_DIR/assets/images" "$BUILD_DIR/assets/"
fi

# Composer PHP vendor (PHPWord, mPDF).
if [ -d "$PLUGIN_DIR/vendor" ]; then
    cp -R "$PLUGIN_DIR/vendor" "$BUILD_DIR/"
    echo "   vendor/: copied (PHPWord + mPDF)"
else
    echo "   WARNING: No vendor/ directory. Document generation will not work!"
fi

# Languages.
if [ -d "$PLUGIN_DIR/languages" ]; then
    cp -R "$PLUGIN_DIR/languages" "$BUILD_DIR/"
fi

# Screenshots (WordPress.org reads from plugin root).
for ss in "$PLUGIN_DIR"/screenshot-*.png "$PLUGIN_DIR"/screenshot-*.jpg; do
    if [ -f "$ss" ]; then
        cp "$ss" "$BUILD_DIR/"
    fi
done

echo "   Copied: src/, templates/, blocks/, assets/, vendor/, languages/"

# ── Step 5: Clean dev artifacts ────────────────────────────────
echo "[5/8] Cleaning dev artifacts..."

# Remove OS files.
find "$BUILD_DIR" -name ".DS_Store" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "Thumbs.db" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "desktop.ini" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "*.log" -delete 2>/dev/null || true
find "$BUILD_DIR" -name ".gitkeep" -delete 2>/dev/null || true
find "$BUILD_DIR" -name ".md" -delete 2>/dev/null || true

# Remove Composer dev files from vendor/.
if [ -d "$BUILD_DIR/vendor" ]; then
    find "$BUILD_DIR/vendor" -name "*.sh" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.phar" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.phar.pubkey*" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "Makefile" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.yml" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.xml" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.md" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.neon" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.neon.dist" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "*.dist" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name ".php-cs-fixer*" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -type d -name "dist" -exec rm -rf {} + 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "phpunit.xml*" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "CHANGELOG*" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "CONTRIBUTING*" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "README*" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "LICENSE*" -not -name "LICENSE" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name ".travis.yml" -delete 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name ".github" -type d -exec rm -rf {} + 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "test" -type d -exec rm -rf {} + 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "docs" -type d -exec rm -rf {} + 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "doc" -type d -exec rm -rf {} + 2>/dev/null || true
    find "$BUILD_DIR/vendor" -name "examples" -type d -exec rm -rf {} + 2>/dev/null || true
    echo "   Cleaned: vendor/ dev files (tests, docs, examples)"

    # ── Slim down mPDF fonts (87MB → ~3MB) ───────────────────
    # mPDF ships 83 font files for CJK, ancient scripts, etc.
    # Keep only DejaVu (core Latin/European) + FreeSans/FreeSerif (extended).
    # Users who need CJK can install the mpdf/mpdf-fonts package.
    MPDF_FONTS="$BUILD_DIR/vendor/mpdf/mpdf/ttfonts"
    if [ -d "$MPDF_FONTS" ]; then
        FONTS_BEFORE=$(du -sh "$MPDF_FONTS" | cut -f1)

        # Keep only DejaVu (covers Latin, Cyrillic, Greek — 3.5MB total):
        KEEP_PATTERN="DejaVu"

        # Move keepers to a temp dir, nuke the rest, move back.
        TMPFONTS="$BUILD_DIR/_tmpfonts"
        mkdir -p "$TMPFONTS"
        for f in "$MPDF_FONTS"/*; do
            fname=$(basename "$f")
            if echo "$fname" | grep -qE "$KEEP_PATTERN"; then
                mv "$f" "$TMPFONTS/"
            fi
        done
        rm -rf "$MPDF_FONTS"
        mv "$TMPFONTS" "$MPDF_FONTS"

        FONTS_AFTER=$(du -sh "$MPDF_FONTS" | cut -f1)
        FONT_COUNT=$(ls "$MPDF_FONTS" | wc -l | tr -d ' ')
        echo "   Fonts: $FONTS_BEFORE → $FONTS_AFTER ($FONT_COUNT files kept)"
    fi

    # Remove mPDF data files not needed for basic PDF generation.
    rm -rf "$BUILD_DIR/vendor/mpdf/mpdf/data/collations" 2>/dev/null || true
    rm -rf "$BUILD_DIR/vendor/mpdf/mpdf/data/codepages" 2>/dev/null || true
fi

echo "   Cleaned: .DS_Store, Thumbs.db, .gitkeep, *.log"

# ── Step 6: Verify critical files ─────────────────────────────
echo "[6/8] Verifying build..."

ERRORS=0

check_file() {
    if [ ! -f "$BUILD_DIR/$1" ]; then
        echo "   MISSING: $1"
        ERRORS=$((ERRORS + 1))
    fi
}

check_dir() {
    if [ ! -d "$BUILD_DIR/$1" ]; then
        echo "   MISSING DIR: $1"
        ERRORS=$((ERRORS + 1))
    fi
}

check_file "wprobo-documerge.php"
check_file "uninstall.php"
check_file "readme.txt"
check_dir "src"
check_dir "src/Core"
check_dir "src/Form"
check_dir "src/Admin"
check_dir "src/Document"
check_dir "src/Template"
check_dir "templates"
check_dir "assets/css"
check_dir "assets/js"
check_file "assets/css/admin/main.min.css"
check_file "assets/css/frontend/form.min.css"
check_file "assets/js/admin/main.min.js"
check_file "assets/js/admin/form-builder.min.js"
check_file "assets/js/admin/settings.min.js"
check_file "assets/js/frontend/form-renderer.min.js"
check_dir "vendor"
check_file "vendor/autoload.php"

if [ $ERRORS -gt 0 ]; then
    echo ""
    echo "ERROR: $ERRORS missing file(s). Build aborted."
    exit 1
fi

# Count files.
FILE_COUNT=$(find "$BUILD_DIR" -type f | wc -l | tr -d ' ')
echo "   Verified: $FILE_COUNT files in build."

# ── Step 7: Verify NO dev files leaked ─────────────────────────
echo "[7/8] Checking for dev file leaks..."

LEAKS=0

check_no_file() {
    if [ -f "$BUILD_DIR/$1" ] || [ -d "$BUILD_DIR/$1" ]; then
        echo "   LEAK: $1 should not be in production!"
        LEAKS=$((LEAKS + 1))
    fi
}

check_no_file "package.json"
check_no_file "package-lock.json"
check_no_file "composer.lock"
check_no_file ".gitignore"
check_no_file ".distignore"
check_no_file ".editorconfig"
check_no_file "deploy.sh"
check_no_file "docs"
check_no_file "scripts"
check_no_file "node_modules"
check_no_file ".git"
check_no_file ".github"
check_no_file "assets/src"

# Check for any .md files.
MD_FILES=$(find "$BUILD_DIR" -name "*.md" 2>/dev/null | wc -l | tr -d ' ')
if [ "$MD_FILES" -gt 0 ]; then
    echo "   LEAK: Found $MD_FILES .md file(s) in build!"
    find "$BUILD_DIR" -name "*.md" -delete 2>/dev/null || true
    LEAKS=$((LEAKS + 1))
fi

if [ $LEAKS -gt 0 ]; then
    echo "   WARNING: $LEAKS leak(s) cleaned."
else
    echo "   No dev files in production build."
fi

# ── Step 8: Create ZIP ─────────────────────────────────────────
echo "[8/8] Creating ZIP: $ZIP_NAME"

cd "$PROD_DIR"
zip -r -q "$ZIP_NAME" "wprobo-documerge-lite/"

# Get ZIP size.
ZIP_SIZE=$(du -h "$PROD_DIR/$ZIP_NAME" | cut -f1)

echo ""
echo "=========================================="
echo "  BUILD COMPLETE"
echo "=========================================="
echo ""
echo "  Plugin:   WPRobo DocuMerge Lite"
echo "  Version:  $VERSION"
echo "  ZIP:      production/$ZIP_NAME"
echo "  Size:     $ZIP_SIZE"
echo "  Files:    $FILE_COUNT"
echo ""

# ── Recreate symlink for local testing site ────────────────────
SYMLINK_TARGET="/Users/alishan/local-by-flywheel/wordpress-default/app/public/wp-content/plugins/wprobo-documerge-lite"
if [ -d "$(dirname "$SYMLINK_TARGET")" ]; then
    rm -f "$SYMLINK_TARGET" 2>/dev/null
    ln -s "$BUILD_DIR" "$SYMLINK_TARGET"
    echo "  Symlink: → wordpress-default/plugins/wprobo-documerge-lite"
fi

echo ""
echo "  Next steps:"
echo "  1. Test the ZIP on a fresh WordPress install"
echo "  2. Submit at: https://wordpress.org/plugins/developers/add/"
echo "  3. Validate readme: https://wordpress.org/plugins/developers/readme-validator/"
echo "=========================================="
