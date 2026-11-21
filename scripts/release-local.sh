#!/usr/bin/env bash
# LOCAL: test, build, verify and fingerprint the release zip.
# Usage: bash scripts/release-local.sh
#        SKIP_TESTS=1 bash scripts/release-local.sh
#        CHECK_STRING="app-mobile-menu" bash scripts/release-local.sh
set -euo pipefail

SRC="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$HOME/release.zip"
INFO="$HOME/release.info"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

fail() { echo "FAIL: $*" >&2; exit 1; }

cd "$SRC"
for tool in rsync zip unzip composer npm php git sha256sum; do
  command -v "$tool" >/dev/null || fail "missing tool: $tool"
done

if [ -n "$(git status --porcelain)" ]; then
  echo "WARNING: uncommitted changes:"; git status --short
  read -r -p "Build anyway? [y/N] " ok; [ "$ok" = "y" ] || fail "aborted"
fi

if [ "${SKIP_TESTS:-0}" != "1" ]; then
  echo "---- running tests"; php artisan test
fi

echo "---- building assets"; npm run build

echo "---- copying source"
rsync -a \
  --exclude '.env' --exclude '.env.save' --exclude '.git' --exclude 'node_modules' \
  --exclude 'tests' --exclude 'docs' --exclude '.claude' --exclude '.mcp.json' \
  --exclude 'boost.json' --exclude 'CLAUDE.md' --exclude 'AGENTS.md' \
  --exclude 'database/database.sqlite' --exclude 'storage/*' --exclude '*.log' \
  --exclude 'release.zip' ./ "$TMP/"

echo "---- production vendor"
( cd "$TMP" && composer install --no-dev --optimize-autoloader --no-interaction >/dev/null 2>&1 ) \
  || fail "composer install failed"

rm -f "$OUT" "$INFO"
( cd "$TMP" && find . -mindepth 1 -printf '%P\0' | xargs -0 zip -rq "$OUT" )

echo "---- verifying zip"
LIST="$(unzip -Z1 "$OUT")"
has() { printf '%s\n' "$LIST" | grep -E "$1" >/dev/null; }

has '(^|\./)public/build/manifest\.json$' || fail "manifest missing"
has '^vendor/autoload\.php$'        || fail "vendor/autoload.php missing"
has '^artisan$'                     || fail "artisan missing"
if has '^\.env$'; then fail ".env is in the zip"; fi
if has '^vendor/phpunit/'; then fail "dev packages in zip"; fi
if has '^storage/.+'; then fail "files under storage/ in zip"; fi
if has '^(\.claude/|\.mcp\.json$|boost\.json$|database/database\.sqlite$)'; then fail "dev files in zip"; fi

MANIFEST_JS="$(unzip -p "$OUT" public/build/manifest.json | grep -o 'assets/app-[^"]*\.js' | head -n 1)"
[ -n "$MANIFEST_JS" ]                || fail "no app JS in manifest"
has "^public/build/${MANIFEST_JS}\$" || fail "manifest JS not in zip"

if [ -n "${CHECK_STRING:-}" ]; then
  unzip -p "$OUT" "public/build/${MANIFEST_JS}" | grep -q "$CHECK_STRING" \
    || fail "string '$CHECK_STRING' not found in built JS"
fi

SIZE="$(stat -c %s "$OUT")"
SHA="$(sha256sum "$OUT" | cut -d' ' -f1)"
{
  echo "built:    $(date '+%F %T')"
  echo "commit:   $(git rev-parse --short HEAD)"
  echo "size:     $SIZE"
  echo "sha256:   $SHA"
  echo "manifest: $MANIFEST_JS"
} > "$INFO"

echo
echo "================ RELEASE READY ================"
cat "$INFO"
echo
echo "NEXT STEPS"
echo "1. Upload $OUT to ~/domains/oneyardoutfitters/ (File Manager)."
echo "2. On the HOST:"
echo "     cd ~/domains/oneyardoutfitters"
echo "     unzip -oj release.zip scripts/deploy-server.sh"
echo "     bash deploy-server.sh $SHA"
echo "REMINDER: new migrations need explicit short index names (MySQL 64-char limit)."
