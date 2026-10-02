#!/usr/bin/env bash
set -e

# A fresh clone has no vendor/ or node_modules/, so install them on first boot
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ]; then
    npm install
fi

exec "$@"
