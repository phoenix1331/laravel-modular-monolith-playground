#!/usr/bin/env bash
set -e

# A fresh clone has no vendor/ or node_modules/, so install them on first boot
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ]; then
    npm install
fi

# Development only: a leftover production build makes Laravel serve stale
# JavaScript whenever public/hot is missing. Clearing both on boot means the
# dev server always writes a fresh hot file, and a missing one fails loudly
# ("Vite manifest not found") instead of showing an old UI.
rm -rf public/build public/hot

exec "$@"
