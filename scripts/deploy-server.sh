
#!/usr/bin/env bash

# Oneyard Outfitters - production release builder
# Run from any directory:
#   bash scripts/build-release.sh

SRC="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$HOME/release.zip"
NEW_OUT="$HOME/release.zip.new"
TMP=""

finish() {
    STATUS=$?

    if [ -n "$TMP" ] && [ -d "$TMP" ]; then
        rm -rf "$TMP"
    fi

    rm -f "$NEW_OUT"

    echo
    if [ "$STATUS" -eq 0 ]; then
        echo "RELEASE BUILD COMPLETED."
    else
        echo "RELEASE BUILD FAILED. Existing release.zip was preserved."
    fi

    echo
    read -r -p "Press Enter to close..."
    exit "$STATUS"
}

trap finish EXIT

fail() {
    echo "ERROR: $*" >&2
    exit 1
}

cd "$SRC" || fail "Cannot access project directory."

echo "========== BUILDING FRONTEND =========="

npm run build
BUILD_STATUS=$?

[ "$BUILD_STATUS" -eq 0 ] || fail "Vite build failed."

[ -f public/build/manifest.json ] || fail "Vite manifest is missing."

echo
echo "========== PREPARING PRODUCTION FILES =========="

TMP="$(mktemp -d)" || fail "Could not create temporary directory."

rsync -a \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='docs' \
    --exclude='.claude' \
    --exclude='.mcp.json' \
    --exclude='boost.json' \
    --exclude='CLAUDE.md' \
    --exclude='AGENTS.md' \
    --exclude='database/database.sqlite' \
    --exclude='storage/*' \
    --exclude='*.log' \
    --exclude='release.zip' \
    --exclude='release.zip.new' \
    --exclude='*.bak' \
    --exclude='*.bak.*' \
    --exclude='*.bak*' \
    --exclude='*.backup' \
    --exclude='*.old' \
    --exclude='*.orig' \
    --exclude='*.save' \
    --exclude='*.before_*' \
    --exclude='*.pre-*' \
    --exclude='*~' \
    --exclude='.oneyard-backup-*' \
    --exclude='scripts2' \
    --exclude='/-' \
    --exclude='/1000,' \
    --exclude='/1500.00,' \
    --exclude='/2000,' \
    --exclude='/2500.00,' \
    --exclude='/Funds required for store operations.,' \
    --exclude='/REQ-001,' \
    --exclude='/StoreFundAssignment::PURPOSES[0],' \
    --exclude='/StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,' \
    --exclude='/StoreFundAssignment::SOURCE_STORE_REQUEST,' \
    --exclude='/StoreFundAssignment::STATUS_APPROVED,' \
    --exclude='/StoreFundAssignment::STATUS_REQUESTED,' \
    --exclude='/TST-001,' \
    --exclude='/Test Store,' \
    --exclude='/direct_assigned,' \
    --exclude='/id,' \
    --exclude='/name' \
    --exclude='/null,' \
    --exclude='/requested,' \
    --exclude='/shutil' \
    --exclude='/store,' \
    --exclude='/store_daily_cash_record_id' \
    --exclude='/sys' \
    --exclude='/true,' \
    ./ "$TMP/"

RSYNC_STATUS=$?

[ "$RSYNC_STATUS" -eq 0 ] || fail "Could not prepare production files."

echo
echo "========== INSTALLING PRODUCTION DEPENDENCIES =========="

cd "$TMP" || fail "Cannot access temporary release directory."

composer install --no-dev --optimize-autoloader --no-interaction
COMPOSER_STATUS=$?

[ "$COMPOSER_STATUS" -eq 0 ] || fail "Production Composer installation failed."

[ -f vendor/autoload.php ] || fail "Production Composer autoloader is missing."
[ -f public/build/manifest.json ] || fail "Production manifest is missing."

if find . -type f \( \
    -name '.env' -o \
    -name '.env.*' -o \
    -name '*.bak' -o \
    -name '*.backup' -o \
    -name '*.before_*' \
\) | grep -q .; then
    fail "Sensitive or backup files were found in the release staging directory."
fi

echo
echo "========== CREATING RELEASE ZIP =========="

rm -f "$NEW_OUT"

zip -rq "$NEW_OUT" .
ZIP_STATUS=$?

[ "$ZIP_STATUS" -eq 0 ] || fail "ZIP creation failed."

unzip -tq "$NEW_OUT"
ZIP_TEST_STATUS=$?

[ "$ZIP_TEST_STATUS" -eq 0 ] || fail "ZIP integrity check failed."

MANIFEST_COUNT="$(unzip -l "$NEW_OUT" | grep -c 'public/build/manifest.json')"
AUTOLOAD_COUNT="$(unzip -l "$NEW_OUT" | grep -c 'vendor/autoload.php')"
PHPUNIT_COUNT="$(unzip -l "$NEW_OUT" | grep -c 'vendor/phpunit/')"
ENV_COUNT="$(unzip -Z1 "$NEW_OUT" | grep -Ec '(^|/)\.env($|\.)' || true)"
BACKUP_COUNT="$(unzip -Z1 "$NEW_OUT" | grep -Ec '(\.bak|\.backup|\.before_|\.pre-|~$)' || true)"

[ "$MANIFEST_COUNT" -eq 1 ] || fail "Expected exactly one Vite manifest."
[ "$AUTOLOAD_COUNT" -eq 1 ] || fail "Expected exactly one Composer autoloader."
[ "$PHPUNIT_COUNT" -eq 0 ] || fail "PHPUnit files found in production package."
[ "$ENV_COUNT" -eq 0 ] || fail "Environment files found in production package."
[ "$BACKUP_COUNT" -eq 0 ] || fail "Backup files found in production package."

echo
echo "========== FINALIZING RELEASE =========="

mv -f "$NEW_OUT" "$OUT"
MOVE_STATUS=$?

[ "$MOVE_STATUS" -eq 0 ] || fail "Could not move validated ZIP into final location."

echo
echo "================ RELEASE READY ================"
ls -lh "$OUT"

echo
echo "SHA-256:"
sha256sum "$OUT"

echo
echo "Vite manifest       : $MANIFEST_COUNT (expect 1)"
echo "Composer autoload   : $AUTOLOAD_COUNT (expect 1)"
echo "PHPUnit files       : $PHPUNIT_COUNT (expect 0)"
echo "Environment files   : $ENV_COUNT (expect 0)"
echo "Backup files        : $BACKUP_COUNT (expect 0)"
echo "ZIP integrity       : PASS"
