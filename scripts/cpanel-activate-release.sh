#!/usr/bin/env bash
set -euo pipefail
umask 077

fail() {
    printf 'Deployment stopped: %s\n' "$*" >&2
    exit 1
}

check_only=false
if [[ $# -eq 1 && "$1" == '--check-only' ]]; then
    check_only=true
else
    [[ $# -eq 2 ]] || fail 'expected --check-only or commit SHA and ZIP SHA-256'
    commit="$1"
    expected_zip_hash="$2"
    [[ "$commit" =~ ^[0-9a-f]{40}$ ]] || fail 'invalid commit SHA'
    [[ "$expected_zip_hash" =~ ^[0-9a-f]{64}$ ]] || fail 'invalid ZIP SHA-256'
fi

home_dir="$(cd "$HOME" && pwd -P)"
deploy_root="$home_dir/simawars-deploy"
docroot="$home_dir/public_html/simawars.web.id"
shared="$deploy_root/shared"
current="$deploy_root/current"

[[ -d "$docroot" && ! -L "$docroot" ]] || fail "domain document root is missing or is a symlink: $docroot"
[[ "$(cd "$docroot" && pwd -P)" == "$docroot" ]] || fail 'domain document root resolves elsewhere'
[[ -w "$docroot" ]] || fail "domain document root is not writable: $docroot"
[[ -d "$deploy_root" && ! -L "$deploy_root" && -w "$deploy_root" ]] || fail "create a writable deployment directory: $deploy_root"
[[ -d "$shared" && ! -L "$shared" ]] || fail "create a regular shared directory: $shared"
[[ -f "$shared/.env" && ! -L "$shared/.env" && -r "$shared/.env" ]] || fail "configure readable $shared/.env before deployment"
[[ ! -e "$current" || -L "$current" ]] || fail 'current release path is not a symlink'

command -v unzip >/dev/null 2>&1 || fail 'unzip is unavailable on cPanel'
command -v sha256sum >/dev/null 2>&1 || fail 'sha256sum is unavailable on cPanel'
php_bin=''
for candidate in /opt/cpanel/ea-php*/root/usr/bin/php /opt/alt/php*/usr/bin/php \
    "$(command -v php || true)"; do
    [[ -n "$candidate" && -x "$candidate" ]] || continue
    version_line="$("$candidate" -v 2>/dev/null | head -n 1)" || continue
    if [[ "$version_line" =~ ^PHP[[:space:]]+([0-9]+)\.([0-9]+)\.([0-9]+) ]] &&
        (( 10#${BASH_REMATCH[1]} > 8 || (10#${BASH_REMATCH[1]} == 8 && 10#${BASH_REMATCH[2]} >= 2) )); then
        php_bin="$candidate"
        break
    fi
done
[[ -n "$php_bin" ]] || fail 'PHP CLI 8.2 or newer is unavailable; configure a compatible CLI runtime'

if "$check_only"; then
    printf 'cPanel prerequisites passed: document root, shared .env, PHP CLI, and archive tools are available.\n'
    exit 0
fi

archive="$deploy_root/inbox/$commit.zip"
release="$deploy_root/releases/$commit"
[[ -f "$archive" && ! -L "$archive" ]] || fail 'release ZIP is missing'

actual_zip_hash="$(sha256sum "$archive" | cut -d ' ' -f 1)"
[[ "$actual_zip_hash" == "$expected_zip_hash" ]] || fail 'release ZIP checksum mismatch'
unzip -tq "$archive" >/dev/null || fail 'release ZIP is damaged'

archive_entries="$(unzip -Z1 "$archive")" || fail 'cannot list release ZIP entries'
[[ -n "$archive_entries" ]] || fail 'release ZIP has no entries'
while IFS= read -r entry; do
    [[ "$entry" == 'RELEASE.json' || "$entry" == simaster/* ]] || fail "unexpected archive entry: $entry"
    [[ "$entry" != /* && "$entry" != *'/../'* && "$entry" != ../* && "$entry" != *'/..' ]] ||
        fail "unsafe archive entry: $entry"
done <<< "$archive_entries"

mkdir -p "$deploy_root/releases" "$shared"
if [[ -d "$release" && ! -L "$release" ]]; then
    [[ -f "$release/RELEASE.json" && -f "$release/vendor/autoload.php" ]] ||
        fail 'existing release is incomplete'
    [[ "$(readlink "$release/.env")" == "$shared/.env" ]] || fail 'existing release uses a different .env'
    [[ "$(readlink "$release/storage")" == "$shared/storage" ]] || fail 'existing release uses different storage'
else
    [[ ! -e "$release" ]] || fail 'release path exists but is not a regular directory'
    incoming="$(mktemp -d "$deploy_root/releases/.incoming-$commit-XXXXXXXX")"
    cleanup() {
        if [[ -n "${incoming:-}" && -d "$incoming" ]]; then
            rm -r -- "$incoming"
        fi
    }
    trap cleanup EXIT
    unzip -q "$archive" -d "$incoming"
    app="$incoming/simaster"
    [[ -f "$app/artisan" && -f "$app/composer.lock" && -f "$app/vendor/autoload.php" ]] ||
        fail 'release is missing Laravel files or production dependencies'
    [[ -f "$app/public/build/manifest.json" ]] || fail 'release has no Vite build'
    [[ ! -e "$app/.env" ]] || fail 'release unexpectedly contains .env'

    if [[ ! -d "$shared/storage" ]]; then
        mv -- "$app/storage" "$shared/storage"
    else
        [[ -d "$app/storage" && ! -L "$app/storage" ]] || fail 'release storage layout is invalid'
        [[ -z "$(find "$app/storage" -type f -print -quit)" ]] || fail 'release unexpectedly contains storage files'
        find "$app/storage" -depth -type d -exec rmdir -- {} \;
    fi
    mkdir -p "$shared/storage/app/private" "$shared/storage/app/public" \
        "$shared/storage/framework/cache/data" "$shared/storage/framework/sessions" \
        "$shared/storage/framework/views" "$shared/storage/logs"
    ln -s "$shared/storage" "$app/storage"
    ln -s "$shared/.env" "$app/.env"

    mv -- "$app" "$release"
    mv -- "$incoming/RELEASE.json" "$release/RELEASE.json"
    rmdir -- "$incoming"
    incoming=''
fi

[[ -f "$release/artisan" && -f "$release/public/build/manifest.json" ]] ||
    fail 'release is missing Laravel or Vite files'
"$php_bin" "$release/deploy/cpanel/deploy-check.php" release "$release/RELEASE.json" "$commit" ||
    fail 'package commit does not match the GitHub Actions commit'
(
    cd "$release"
    "$php_bin" deploy/cpanel/preflight.php
    "$php_bin" artisan optimize
    "$php_bin" artisan filament:optimize
)

while IFS= read -r -d '' source_file; do
    relative="${source_file#"$release/public/"}"
    [[ "$relative" != 'index.php' && "$relative" != .well-known/* ]] || continue
    destination="$docroot/$relative"
    mkdir -p "$(dirname "$destination")"
    install -m 0644 -- "$source_file" "$destination"
done < <(find "$release/public" -type f -print0)

index_next="$docroot/.index-next-$commit"
install -m 0644 -- "$release/deploy/cpanel/simawars-index.php" "$index_next"
link_next="$deploy_root/.current-next-$commit"
ln -s "$release" "$link_next"
mv -Tf -- "$link_next" "$current"
mv -f -- "$index_next" "$docroot/index.php"

rm -f -- "$archive"
printf 'Activated SIMAWARS release %s at %s\n' "$commit" "$current"
