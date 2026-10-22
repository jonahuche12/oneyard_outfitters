#!/usr/bin/env bash
set -euo pipefail

SRC="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
OUT="$HOME/release.zip"

cd "$SRC"
npm run build

rsync -a \
  --exclude '.env' --exclude '.git' --exclude 'node_modules' --exclude 'tests' \
  --exclude 'docs' --exclude '.claude' --exclude '.mcp.json' --exclude 'boost.json' \
  --exclude 'CLAUDE.md' --exclude 'AGENTS.md' --exclude 'database/database.sqlite' \
  --exclude 'storage/*' --exclude '*.log' --exclude 'release.zip' \
  ./ "$TMP/"

cd "$TMP"
composer install --no-dev --optimize-autoloader --no-interaction

rm -f "$OUT"
zip -rq "$OUT" .

echo "---- release: $(ls -lh "$OUT" | awk '{print $5}') at $OUT"
echo "public/build entries : $(unzip -l "$OUT" | grep -c 'public/build')   (expect > 0)"
echo ".env files           : $(unzip -l "$OUT" | grep -c -E '(^|/)\.env$')   (expect 0)"
echo "vendor autoload      : $(unzip -l "$OUT" | grep -c 'vendor/autoload.php')   (expect 1)"
echo "phpunit              : $(unzip -l "$OUT" | grep -c 'vendor/phpunit/')   (expect 0)"
echo "storage entries      : $(unzip -l "$OUT" | grep -c ' storage/')   (expect 0)"
rm -rf "$TMP"
