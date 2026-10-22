#!/usr/bin/env bash
# HOST: verify the uploaded zip, then deploy.
# Usage (from ~/domains/oneyardoutfitters):  bash deploy-server.sh <expected-sha256>
set -euo pipefail

EXPECTED="${1:-}"
PUBLIC="$HOME/domains/oneyardoutfitters.com/public_html"
DOWN=0

fail() { echo "FAIL: $*" >&2; exit 1; }
trap '[ "$DOWN" = "1" ] && echo ">>> ERROR: site is still DOWN. Fix, finish the remaining steps by hand, then: php artisan up" >&2' ERR

[ -n "$EXPECTED" ] || fail "usage: bash deploy-server.sh <sha256 from the local script>"
[ -f release.zip ] || fail "release.zip not found in $(pwd)"
[ -f .env ]        || fail ".env not found - wrong folder?"

echo "---- verifying upload"
ACTUAL="$(sha256sum release.zip | cut -d' ' -f1)"
echo "expected: $EXPECTED"
echo "actual:   $ACTUAL"
[ "$ACTUAL" = "$EXPECTED" ] || fail "checksum mismatch - delete release.zip and upload again"
MANIFEST_JS="$(unzip -p release.zip public/build/manifest.json | grep -o 'assets/app-[^"]*\.js' | head -n 1)"
echo "manifest JS: $MANIFEST_JS"

read -r -p "Back up the database first? [Y/n] " bk
if [ "$bk" != "n" ]; then
  DBU="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d "\"'")"
  DBN="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d "\"'")"
  BK="$HOME/db-backup-$(date +%F-%H%M).sql"
  echo "Enter the database password when asked."
  mariadb-dump -u "$DBU" -p "$DBN" > "$BK"
  [ "$(stat -c %s "$BK")" -gt 2000 ] || fail "backup looks empty: $BK"
  echo "backup OK: $BK"
fi

php artisan down --retry=10; DOWN=1

unzip -oq release.zip && rm release.zip
rm -rf .claude; rm -f .mcp.json boost.json database/database.sqlite

echo "---- pending migrations:"
php artisan migrate:status | grep -i pending || echo "(none)"
read -r -p "Continue with migrate? [y/N] " ok
[ "$ok" = "y" ] || { echo "Aborted. Site is still DOWN."; exit 1; }

php artisan migrate --force
php artisan db:seed --class=RbacSeeder --force
php artisan earnings:seed-weights

cp -a public/build/. "$PUBLIC/build/"
cp -a public/images/. "$PUBLIC/images/"
[ -f "$PUBLIC/build/$MANIFEST_JS" ] || fail "built JS $MANIFEST_JS not in $PUBLIC/build"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

php artisan up; DOWN=0

echo
echo "================ DEPLOYED ================"
echo "live JS: $MANIFEST_JS"
echo "pending migrations now: $(php artisan migrate:status | grep -ci pending) (expect 0)"
echo "Check: tail -n 30 storage/logs/laravel.log (read timestamps)"
echo "Then test in a PRIVATE browser tab."
