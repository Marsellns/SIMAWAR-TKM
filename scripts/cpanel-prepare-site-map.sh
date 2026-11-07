#!/usr/bin/env bash
set -euo pipefail

# One-time preparation for the existing beta cPanel Git deployment. This does
# not activate a release or run migrations belonging to other modules.
repo="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
expected="$(cd "$HOME" && pwd -P)/beta.simawar.web.id"
[[ "$repo" == "$expected" ]] || { printf 'Expected checkout at %s\n' "$expected" >&2; exit 1; }

bash "$repo/scripts/cpanel-deploy-beta.sh" --prepare-only
sha="$(git -C "$repo" rev-parse HEAD)"
release="$HOME/simawar-beta-deploy/releases/$sha"
[[ -f "$release/artisan" ]] || { echo 'Prepared release is missing.' >&2; exit 1; }

php=''
for candidate in /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php "$(command -v php || true)"; do
    if [[ -n "$candidate" && -x "$candidate" ]] && "$candidate" "$repo/scripts/cpanel-deploy-check.php" version >/dev/null 2>&1; then
        php="$candidate"
        break
    fi
done
[[ -n "$php" ]] || { echo 'PHP CLI 8.2+ is unavailable.' >&2; exit 1; }

cd "$release"
"$php" artisan migrate --force \
    --path=database/migrations/2026_11_06_000000_create_site_locations_table.php \
    --path=database/migrations/2026_11_06_010000_add_administrative_details_to_site_locations.php

printf '\nSite map schema is ready for commit %s.\n' "$sha"
printf 'Import the private site_locations SQL through phpMyAdmin, then click Deploy HEAD Commit in cPanel.\n'
